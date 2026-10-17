
<?php

session_start();

require_once "includes/db.php";

/* ========================================
   Authentication
======================================== */

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

$userId = (int) $_SESSION["user_id"];

/* ========================================
   Security Token
======================================== */

if (empty($_SESSION["investments_csrf"])) {
    $_SESSION["investments_csrf"] = bin2hex(random_bytes(32));
}

$csrfToken = $_SESSION["investments_csrf"];

/* ========================================
   Helper Functions
======================================== */

function investment_escape($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8");
}

function investment_money($value) {
    return "$" . number_format((float) $value, 2);
}

function investment_date($value) {

    if (empty($value)) {
        return "Flexible Term";
    }

    return date("M j, Y", strtotime($value));

}

function investment_maturity_date($startDate, $months) {

    $start = new DateTimeImmutable($startDate);

    $day = (int) $start->format("d");

    $firstDay = $start->modify("first day of this month");

    $target = $firstDay->modify("+" . (int) $months . " months");

    $lastDay = (int) $target->format("t");

    $targetDay = min($day, $lastDay);

    return $target->setDate(
        (int) $target->format("Y"),
        (int) $target->format("m"),
        $targetDay
    )->format("Y-m-d");

}

function investment_maturity_status($maturityDate) {

    if (empty($maturityDate)) {
        return "Flexible Term";
    }

    $today = new DateTimeImmutable("today");
    $maturity = new DateTimeImmutable($maturityDate);

    if ($maturity <= $today) {
        return "Matured";
    }

    $days = (int) $today->diff($maturity)->days;

    if ($days <= 30) {
        return "Maturing Soon";
    }

    return "Active";

}

/* ========================================
   Page Messages
======================================== */

$error = "";
$success = "";

$messages = [
    "added" => "Your investment was added successfully.",
    "updated" => "Your investment status was updated.",
    "deleted" => "Your investment was deleted successfully."
];

if (isset($_GET["success"])) {
    $success = $messages[$_GET["success"]] ?? "";
}

/* ========================================
   Load Available Products
======================================== */

$products = [];

try {

    $result = $conn->query(
        "SELECT product_id, product_name, product_type,
                interest_rate, term_months, minimum_deposit
         FROM products
         WHERE is_active = 1
         ORDER BY product_name ASC"
    );

    $products = $result->fetch_all(MYSQLI_ASSOC);

} catch (mysqli_sql_exception $exception) {

    error_log($exception->getMessage());

    $error = "Unable to load investment products.";

}

/* ========================================
   Process Investment Forms
======================================== */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $token = (string) ($_POST["csrf_token"] ?? "");

    if (!hash_equals($csrfToken, $token)) {

        $error = "Your session could not be verified. Please refresh the page.";

    } else {

        $action = (string) ($_POST["action"] ?? "");

        /* ========================================
           Add Investment
        ======================================== */

        if ($action === "add") {

            $productId = filter_var(
                $_POST["product_id"] ?? null,
                FILTER_VALIDATE_INT
            );

            $deposit = filter_var(
                $_POST["deposit_amount"] ?? null,
                FILTER_VALIDATE_FLOAT
            );

            $startDate = (string) ($_POST["start_date"] ?? "");

            $date = DateTimeImmutable::createFromFormat(
                "!Y-m-d",
                $startDate
            );

            $validDate = $date &&
                $date->format("Y-m-d") === $startDate;

            if (
                $productId === false ||
                $productId === null ||
                $productId < 1 ||
                $deposit === false ||
                $deposit === null ||
                !is_finite((float) $deposit) ||
                $deposit <= 0 ||
                $deposit > 9999999999.99 ||
                !$validDate
            ) {

                $error = "Please enter valid investment details.";

            } else {

                try {

                    /* ========================================
                       Verify Selected Product
                    ======================================== */

                    $stmt = $conn->prepare(
                        "SELECT interest_rate, term_months,
                                minimum_deposit
                         FROM products
                         WHERE product_id = ?
                         AND is_active = 1"
                    );

                    $stmt->bind_param("i", $productId);
                    $stmt->execute();

                    $product = $stmt->get_result()->fetch_assoc();

                    $stmt->close();

                    if (!$product) {

                        $error = "The selected product is not available.";

                    } elseif ($deposit < (float) $product["minimum_deposit"]) {

                        $error = "Your deposit is below the product minimum of " .
                            investment_money($product["minimum_deposit"]) . ".";

                    } else {

                        $rate = (float) $product["interest_rate"];

                        $months = $product["term_months"] === null
                            ? null
                            : (int) $product["term_months"];

                        if ($months !== null && $months < 1) {

                            $error = "This product has an invalid investment term.";

                        } else {

                            $maturityDate = $months === null
                                ? null
                                : investment_maturity_date($startDate, $months);

                            $status = "Active";

                            /* ========================================
                               Save Investment
                            ======================================== */

                            $stmt = $conn->prepare(
                                "INSERT INTO investments
                                 (user_id, product_id, deposit_amount,
                                  interest_rate, start_date,
                                  maturity_date, status)
                                 VALUES (?, ?, ?, ?, ?, ?, ?)"
                            );

                            $stmt->bind_param(
                                "iiddsss",
                                $userId,
                                $productId,
                                $deposit,
                                $rate,
                                $startDate,
                                $maturityDate,
                                $status
                            );

                            $stmt->execute();
                            $stmt->close();

                            header("Location: investments.php?success=added");
                            exit;

                        }

                    }

                } catch (mysqli_sql_exception $exception) {

                    error_log($exception->getMessage());

                    $error = "Unable to save your investment.";

                }

            }

        }

        /* ========================================
           Update Investment Status
        ======================================== */

        elseif ($action === "update") {

            $investmentId = filter_var(
                $_POST["investment_id"] ?? null,
                FILTER_VALIDATE_INT
            );

            $status = (string) ($_POST["status"] ?? "");

            if (
                !$investmentId ||
                !in_array($status, ["Active", "Completed", "Cancelled"], true)
            ) {

                $error = "Please select a valid investment status.";

            } else {

                try {

                    $stmt = $conn->prepare(
                        "UPDATE investments
                         SET status = ?
                         WHERE investment_id = ?
                         AND user_id = ?"
                    );

                    $stmt->bind_param(
                        "sii",
                        $status,
                        $investmentId,
                        $userId
                    );

                    $stmt->execute();
                    $stmt->close();

                    header("Location: investments.php?success=updated");
                    exit;

                } catch (mysqli_sql_exception $exception) {

                    error_log($exception->getMessage());

                    $error = "Unable to update your investment.";

                }

            }

        }

        /* ========================================
           Delete Investment
        ======================================== */

        elseif ($action === "delete") {

            $investmentId = filter_var(
                $_POST["investment_id"] ?? null,
                FILTER_VALIDATE_INT
            );

            if (!$investmentId) {

                $error = "Invalid investment selected.";

            } else {

                try {

                    $stmt = $conn->prepare(
                        "DELETE FROM investments
                         WHERE investment_id = ?
                         AND user_id = ?"
                    );

                    $stmt->bind_param(
                        "ii",
                        $investmentId,
                        $userId
                    );

                    $stmt->execute();
                    $stmt->close();

                    header("Location: investments.php?success=deleted");
                    exit;

                } catch (mysqli_sql_exception $exception) {

                    error_log($exception->getMessage());

                    $error = "Unable to delete your investment.";

                }

            }

        } else {

            $error = "Invalid form action.";

        }

    }

}

/* ========================================
   Load User Investments
======================================== */

$investments = [];

try {

    $stmt = $conn->prepare(
        "SELECT i.investment_id, i.deposit_amount,
                i.interest_rate, i.start_date,
                i.maturity_date, i.status,
                p.product_name, p.product_type
         FROM investments AS i
         INNER JOIN products AS p
         ON i.product_id = p.product_id
         WHERE i.user_id = ?
         ORDER BY i.created_at DESC, i.investment_id DESC"
    );

    $stmt->bind_param("i", $userId);
    $stmt->execute();

    $investments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    $stmt->close();

} catch (mysqli_sql_exception $exception) {

    error_log($exception->getMessage());

    $error = "Unable to load your investments.";

}

/* ========================================
   Investment Summary
======================================== */

$totalInvested = 0;
$activeCount = 0;
$maturingSoonCount = 0;
$maturedCount = 0;

foreach ($investments as $investment) {

    if ($investment["status"] !== "Cancelled") {
        $totalInvested += (float) $investment["deposit_amount"];
    }

    if ($investment["status"] === "Active") {

        $maturityStatus = investment_maturity_status(
            $investment["maturity_date"]
        );

        $activeCount++;

        if ($maturityStatus === "Maturing Soon") {
            $maturingSoonCount++;
        }

        if ($maturityStatus === "Matured") {
            $maturedCount++;
        }

    }

}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Investment Tracking | SheRock Financial</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@500;600;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="css/style.css?v=5">
</head>

<body>

    <!-- Header -->
    <header>

        <div class="logo">
            <h1>SheRock <span>Financial</span></h1>
        </div>

        <nav aria-label="Main navigation">

            <a href="index.php">Home</a>
            <a href="dashboard.php">Dashboard</a>
            <a href="savings_goals.php">Savings Goals</a>
            <a href="products.php">Products</a>
            <a href="calculator.php">Calculator</a>
            <a href="investments.php" aria-current="page">Investments</a>
            <a href="logout.php" class="btn">Logout</a>

        </nav>

    </header>

    <main>

        <!-- Investment Tracking Hero -->
        <section class="investments-hero">

            <p class="section-label">
                BUILD YOUR FINANCIAL FUTURE
            </p>

            <h2>Investment Maturity Tracking</h2>

            <p>
                Monitor your investments, track upcoming maturity
                dates, and stay informed about your financial growth.
            </p>

        </section>

        <!-- Investment Tracking Section -->
        <section class="investments-section">

            <div class="investments-container">

                <p class="section-label">
                    SAVE SMARTER. GROW STRONGER.
                </p>

                <h2>My Investments</h2>

                <p class="investments-intro">
                    Keep track of your investment products, deposits,
                    expected returns, and maturity dates in one place.
                </p>

                <?php if ($success !== ""): ?>

                    <p class="investments-success" role="status">
                        <?php echo investment_escape($success); ?>
                    </p>

                <?php endif; ?>

                <?php if ($error !== ""): ?>

                    <p class="investments-error" role="alert">
                        <?php echo investment_escape($error); ?>
                    </p>

                <?php endif; ?>

                <!-- Investment Summary Cards -->
                <div class="investments-summary">

                    <div class="investments-summary-card">
                        <span>Total Recorded Deposits</span>
                        <strong><?php echo investment_money($totalInvested); ?></strong>
                        <small>Excludes cancelled investments</small>
                    </div>

                    <div class="investments-summary-card">
                        <span>Active Investments</span>
                        <strong><?php echo $activeCount; ?></strong>
                        <small>Including investments past maturity</small>
                    </div>

                    <div class="investments-summary-card">
                        <span>Maturing Soon</span>
                        <strong><?php echo $maturingSoonCount; ?></strong>
                        <small>Within the next 30 days</small>
                    </div>

                    <div class="investments-summary-card">
                        <span>Past Maturity</span>
                        <strong><?php echo $maturedCount; ?></strong>
                        <small>Still recorded as Active</small>
                    </div>

                </div>

                <!-- Add Investment -->
                <section class="investments-form-card">

                    <p class="section-label">START TRACKING</p>

                    <h3>Add New Investment</h3>

                    <p>
                        Choose an investment product and enter your
                        deposit details to begin tracking its maturity.
                    </p>

                    <form method="POST" action="investments.php">

                        <input type="hidden"
                               name="csrf_token"
                               value="<?php echo investment_escape($csrfToken); ?>">

                        <input type="hidden"
                               name="action"
                               value="add">

                        <div class="investments-form-grid">

                            <div class="investments-field">

                                <label for="productId">
                                    Investment Product
                                </label>

                                <select name="product_id"
                                        id="productId"
                                        required>

                                    <option value="">
                                        Select an investment product
                                    </option>

                                    <?php foreach ($products as $product): ?>

                                        <option value="<?php echo (int) $product["product_id"]; ?>">

                                            <?php echo investment_escape($product["product_name"]); ?>
                                            (<?php echo number_format((float) $product["interest_rate"], 2); ?>%)

                                        </option>

                                    <?php endforeach; ?>

                                </select>

                            </div>

                            <div class="investments-field">

                                <label for="depositAmount">
                                    Deposit Amount (CAD)
                                </label>

                                <input type="number"
                                       name="deposit_amount"
                                       id="depositAmount"
                                       min="0.01"
                                       max="9999999999.99"
                                       step="0.01"
                                       placeholder="1000.00"
                                       required>

                            </div>

                            <div class="investments-field">

                                <label for="startDate">
                                    Investment Start Date
                                </label>

                                <input type="date"
                                       name="start_date"
                                       id="startDate"
                                       value="<?php echo date("Y-m-d"); ?>"
                                       required>

                            </div>

                        </div>

                        <p class="investments-form-note">
                            The annual interest rate comes from the selected
                            product. Fixed-term maturity dates are calculated
                            automatically. Flexible products have no fixed
                            maturity date.
                        </p>

                        <button type="submit"
                                class="btn investments-submit"
                                <?php echo empty($products) ? "disabled" : ""; ?>>
                            Add Investment
                        </button>

                    </form>

                </section>

                <!-- Investment Cards -->
                <section class="investments-list">

                    <p class="section-label">
                        YOUR INVESTMENT PORTFOLIO
                    </p>

                    <h2>Track Your Investments</h2>

                    <?php if (empty($investments)): ?>

                        <div class="investments-empty">

                            <h3>No Investments Yet</h3>

                            <p>
                                Add your first investment above to start
                                tracking your financial progress.
                            </p>

                        </div>

                    <?php else: ?>

                        <div class="investments-grid">

                            <?php foreach ($investments as $investment): ?>

                                <?php

                                    $deposit = (float) $investment["deposit_amount"];

                                    $rate = (float) $investment["interest_rate"];

                                    $maturityDate = $investment["maturity_date"];

                                    $maturityStatus = investment_maturity_status(
                                        $maturityDate
                                    );

                                    $recordStatus = $investment["status"];

                                    $estimatedTotal = null;
                                    $estimatedInterest = null;
                                    $progress = null;
                                    $remainingDays = null;

                                    if (!empty($maturityDate)) {

                                        $start = new DateTimeImmutable(
                                            $investment["start_date"]
                                        );

                                        $maturity = new DateTimeImmutable(
                                            $maturityDate
                                        );

                                        $today = new DateTimeImmutable("today");

                                        $termDays = max(
                                            1,
                                            (int) $start->diff($maturity)->days
                                        );

                                        $elapsedDays = $today < $start
                                            ? 0
                                            : (int) $start->diff($today)->days;

                                        $progress = min(
                                            100,
                                            max(0, ($elapsedDays / $termDays) * 100)
                                        );

                                        $remainingDays = $today >= $maturity
                                            ? 0
                                            : (int) $today->diff($maturity)->days;

                                        $termYears = $termDays / 365.2425;

                                        // Illustrative annual compounding.
                                        $estimatedTotal = $deposit * pow(
                                            1 + ($rate / 100),
                                            $termYears
                                        );

                                        $estimatedInterest = $estimatedTotal - $deposit;

                                    }

                                ?>

                                <article class="investment-card">

                                    <div class="investment-card-top">

                                        <span class="investment-type">
                                            <?php echo investment_escape($investment["product_type"]); ?>
                                        </span>

                                        <span class="investment-status
                                            <?php
                                                echo investment_escape(
                                                    strtolower(
                                                        str_replace(" ", "-", $maturityStatus)
                                                    )
                                                );
                                            ?>">
                                            <?php echo investment_escape($maturityStatus); ?>
                                        </span>

                                    </div>

                                    <h3>
                                        <?php echo investment_escape($investment["product_name"]); ?>
                                    </h3>

                                    <div class="investment-amount">

                                        <span>Initial Investment</span>

                                        <strong>
                                            <?php echo investment_money($deposit); ?>
                                        </strong>

                                    </div>

                                    <div class="investment-details">

                                        <p>
                                            <span>Annual Interest Rate</span>
                                            <strong><?php echo number_format($rate, 2); ?>%</strong>
                                        </p>

                                        <p>
                                            <span>Start Date</span>
                                            <strong>
                                                <?php echo investment_escape(
                                                    investment_date($investment["start_date"])
                                                ); ?>
                                            </strong>
                                        </p>

                                        <p>
                                            <span>Maturity Date</span>
                                            <strong>
                                                <?php echo investment_escape(
                                                    investment_date($maturityDate)
                                                ); ?>
                                            </strong>
                                        </p>

                                        <p>
                                            <span>Recorded Status</span>
                                            <strong>
                                                <?php echo investment_escape($recordStatus); ?>
                                            </strong>
                                        </p>

                                    </div>

                                    <?php if ($progress !== null): ?>

                                        <!-- Maturity Progress -->
                                        <div class="investment-progress">

                                            <div class="investment-progress-heading">

                                                <span>Maturity Progress</span>

                                                <strong>
                                                    <?php echo number_format($progress, 0); ?>%
                                                </strong>

                                            </div>

                                            <div class="investment-progress-track"
                                                 role="progressbar"
                                                 aria-label="Maturity progress"
                                                 aria-valuemin="0"
                                                 aria-valuemax="100"
                                                 aria-valuenow="<?php echo round($progress); ?>">

                                                <div class="investment-progress-fill"
                                                     style="width: <?php echo number_format($progress, 2, ".", ""); ?>%;">
                                                </div>

                                            </div>

                                            <small>
                                                <?php if ($remainingDays === 0): ?>

                                                    Maturity date reached

                                                <?php else: ?>

                                                    <?php echo $remainingDays; ?> days remaining

                                                <?php endif; ?>
                                            </small>

                                        </div>

                                        <!-- Estimated Returns -->
                                        <div class="investment-estimate">

                                            <span>Estimated Maturity Value</span>

                                            <strong>
                                                <?php echo investment_money($estimatedTotal); ?>
                                            </strong>

                                            <small>
                                                Estimated interest:
                                                <?php echo investment_money($estimatedInterest); ?>
                                            </small>

                                        </div>

                                    <?php else: ?>

                                        <div class="investment-estimate">

                                            <span>Flexible Investment</span>

                                            <strong>No Fixed Maturity</strong>

                                            <small>
                                                Returns depend on the investment
                                                terms and future performance.
                                            </small>

                                        </div>

                                    <?php endif; ?>

                                    <!-- Update Investment -->
                                    <form method="POST"
                                          action="investments.php"
                                          class="investment-update-form">

                                        <input type="hidden"
                                               name="csrf_token"
                                               value="<?php echo investment_escape($csrfToken); ?>">

                                        <input type="hidden"
                                               name="action"
                                               value="update">

                                        <input type="hidden"
                                               name="investment_id"
                                               value="<?php echo (int) $investment["investment_id"]; ?>">

                                        <label for="status-<?php echo (int) $investment["investment_id"]; ?>">
                                            Update Recorded Status
                                        </label>

                                        <select name="status"
                                                id="status-<?php echo (int) $investment["investment_id"]; ?>">

                                            <?php foreach (["Active", "Completed", "Cancelled"] as $option): ?>

                                                <option value="<?php echo $option; ?>"
                                                    <?php echo $recordStatus === $option ? "selected" : ""; ?>>

                                                    <?php echo $option; ?>

                                                </option>

                                            <?php endforeach; ?>

                                        </select>

                                        <button type="submit"
                                                class="investment-update-button">
                                            Update Status
                                        </button>

                                    </form>

                                    <!-- Delete Investment -->
                                    <form method="POST"
                                          action="investments.php"
                                          class="investment-delete-form"
                                          onsubmit="return confirm('Delete this investment? This cannot be undone.');">

                                        <input type="hidden"
                                               name="csrf_token"
                                               value="<?php echo investment_escape($csrfToken); ?>">

                                        <input type="hidden"
                                               name="action"
                                               value="delete">

                                        <input type="hidden"
                                               name="investment_id"
                                               value="<?php echo (int) $investment["investment_id"]; ?>">

                                        <button type="submit"
                                                class="investment-delete-button">
                                            Delete Investment
                                        </button>

                                    </form>

                                </article>

                            <?php endforeach; ?>

                        </div>

                    <?php endif; ?>

                </section>

                <p class="investments-disclaimer">
                    Investment values are illustrative estimates based
                    on a constant annual rate with annual compounding.
                    They are not guaranteed returns. Recorded investment
                    details are for personal tracking and do not represent
                    actual bank transactions.
                </p>

            </div>

        </section>

    </main>

    <!-- Footer -->
    <footer>

        <p>
            &copy; <?php echo date("Y"); ?>
            SheRock Financial. All rights reserved.
        </p>

        <p>Save Smarter. Grow Stronger.</p>

        <p class="disclaimer">
            For educational and financial planning purposes only.
            Not financial or investment advice.
        </p>

    </footer>

</body>
</html>
