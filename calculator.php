<?php

session_start();

require_once "includes/db.php";

/* ========================================
   Helper Functions
======================================== */

function calculator_escape($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8");
}

function calculator_money($amount) {
    return "$" . number_format((float) $amount, 2);
}

function calculator_frequency($frequency) {

    switch ((int) $frequency) {

        case 12:
            return "Monthly";

        case 4:
            return "Quarterly";

        case 2:
            return "Semi-Annually";

        case 1:
            return "Annually";

        default:
            return "Unknown";

    }

}

/* ========================================
   Session and Security
======================================== */

$isLoggedIn = isset($_SESSION["user_id"]);

if (empty($_SESSION["calculator_csrf"])) {
    $_SESSION["calculator_csrf"] = bin2hex(random_bytes(32));
}

$csrfToken = $_SESSION["calculator_csrf"];

$success = "";
$error = "";

$history = [];

/* ========================================
   Save Investment Calculation
======================================== */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if (!$isLoggedIn) {

        $error = "Please log in to save your calculations.";

    } elseif (
        !isset($_POST["csrf_token"]) ||
        !hash_equals($csrfToken, (string) $_POST["csrf_token"])
    ) {

        $error = "Your session could not be verified. Please refresh the page.";

    } else {

        $amount = filter_var(
            $_POST["initial_amount"] ?? null,
            FILTER_VALIDATE_FLOAT
        );

        $rate = filter_var(
            $_POST["interest_rate"] ?? null,
            FILTER_VALIDATE_FLOAT
        );

        $years = filter_var(
            $_POST["investment_years"] ?? null,
            FILTER_VALIDATE_INT
        );

        $frequency = filter_var(
            $_POST["compounding_frequency"] ?? null,
            FILTER_VALIDATE_INT
        );

        /* ========================================
           Validate Investment Details
        ======================================== */

        if (
            $amount === false ||
            $rate === false ||
            $years === false ||
            $frequency === false ||
            $amount < 0.01 ||
            $amount > 9999999999.99 ||
            $rate < 0 ||
            $rate > 100 ||
            $years < 1 ||
            $years > 100 ||
            !in_array($frequency, [1, 2, 4, 12], true)
        ) {

            $error = "Please enter valid investment details.";

        } else {

            /* ========================================
               Calculate Compound Interest in PHP
            ======================================== */

            $termMonths = $years * 12;

            $estimatedTotal = $amount * pow(
                1 + ($rate / 100) / $frequency,
                $frequency * $years
            );

            $estimatedInterest = $estimatedTotal - $amount;

            if (
                !is_finite($estimatedTotal) ||
                $estimatedTotal > 9999999999.99
            ) {

                $error = "The estimated amount exceeds the supported limit.";

            } else {

                $estimatedTotal = round($estimatedTotal, 2);
                $estimatedInterest = round($estimatedInterest, 2);

                /* ========================================
                   Insert Calculation into MySQL
                ======================================== */

                try {

                    $userId = (int) $_SESSION["user_id"];

                    $stmt = $conn->prepare(
                        "INSERT INTO calculator_history
                         (user_id, initial_amount, interest_rate,
                          term_months, compounding_frequency,
                          estimated_interest, estimated_total)
                         VALUES (?, ?, ?, ?, ?, ?, ?)"
                    );

                    $stmt->bind_param(
                    "iddiidd",
                    $userId,
                    $amount,
                    $rate,
                    $termMonths,
                    $frequency,
                    $estimatedInterest,
                    $estimatedTotal
                );

                    $stmt->execute();

                    $stmt->close();

                    header("Location: calculator.php?saved=1");
                    exit;

                } catch (mysqli_sql_exception $exception) {

                    error_log($exception->getMessage());

                    $error = "Unable to save your calculation. Please try again.";

                }

            }

        }

    }

}

/* ========================================
   Saved Confirmation
======================================== */

if (isset($_GET["saved"]) && $_GET["saved"] === "1") {
    $success = "Your investment calculation was saved successfully.";
}

/* ========================================
   Load User Calculation History
======================================== */

if ($isLoggedIn) {

    try {

        $userId = (int) $_SESSION["user_id"];

        $stmt = $conn->prepare(
            "SELECT calculation_id, initial_amount,
                    interest_rate, term_months,
                    compounding_frequency, estimated_interest,
                    estimated_total, created_at
             FROM calculator_history
             WHERE user_id = ?
             ORDER BY created_at DESC, calculation_id DESC
             LIMIT 20"
        );

        $stmt->bind_param("i", $userId);

        $stmt->execute();

        $result = $stmt->get_result();

        $history = $result->fetch_all(MYSQLI_ASSOC);

        $stmt->close();

    } catch (mysqli_sql_exception $exception) {

        error_log($exception->getMessage());

        $error = "Unable to load calculation history.";

    }

}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Calculate investment growth and save your financial projections with SheRock Financial.">
    <title>Investment Calculator | SheRock Financial</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css?v=4">
</head>
<body>

    <!-- Shared Header -->
    <?php include __DIR__ . '/includes/header.php'; ?>

    <!-- Main Content -->
    <main>

        <!-- Calculator Hero -->
        <section class="calculator-hero">

            <p class="section-label">
                PLAN YOUR FINANCIAL FUTURE
            </p>

            <h2>Investment Calculator</h2>

            <p>
                Explore how your savings could grow over time
                with compound interest.
            </p>

        </section>

        <!-- Calculator Section -->
        <section class="calculator-section">

            <div class="calculator-container">

                <p class="section-label">
                    SAVE SMARTER. GROW STRONGER.
                </p>

                <h2>Calculate Your Investment Growth</h2>

                <p class="calculator-intro">
                    Enter your investment details below to estimate
                    your future balance and potential interest earnings.
                </p>

                <?php if ($success !== ""): ?>

                    <p class="calculator-success" role="status">
                        <?php echo calculator_escape($success); ?>
                    </p>

                <?php endif; ?>

                <?php if ($error !== ""): ?>

                    <p class="calculator-error" role="alert">
                        <?php echo calculator_escape($error); ?>
                    </p>

                <?php endif; ?>

                <div class="calculator-layout">

                    <!-- Calculator Form -->
                    <div class="calculator-card">

                        <h3>Investment Details</h3>

                        <form id="investmentCalculator">

                            <div class="calculator-field">

                                <label for="initialInvestment">
                                    Initial Investment (CAD)
                                </label>

                                <input type="number"
                                       id="initialInvestment"
                                       min="0.01"
                                       max="9999999999.99"
                                       step="0.01"
                                       value="1000"
                                       required>

                            </div>

                            <div class="calculator-field">

                                <label for="annualRate">
                                    Annual Interest Rate (%)
                                </label>

                                <input type="number"
                                       id="annualRate"
                                       min="0"
                                       max="100"
                                       step="0.01"
                                       value="3.25"
                                       required>

                            </div>

                            <div class="calculator-field">

                                <label for="investmentYears">
                                    Investment Term (Years)
                                </label>

                                <input type="number"
                                       id="investmentYears"
                                       min="1"
                                       max="100"
                                       step="1"
                                       value="5"
                                       required>

                            </div>

                            <div class="calculator-field">

                                <label for="compoundFrequency">
                                    Compounding Frequency
                                </label>

                                <select id="compoundFrequency">

                                    <option value="12">Monthly</option>
                                    <option value="4">Quarterly</option>
                                    <option value="2">Semi-Annually</option>
                                    <option value="1">Annually</option>

                                </select>

                            </div>

                            <button type="submit"
                                    class="btn calculator-submit">
                                Calculate Returns
                            </button>

                        </form>

                    </div>

                    <!-- Calculator Results -->
                    <div class="calculator-results" aria-live="polite">

                        <p class="section-label">
                            YOUR ESTIMATED RETURNS
                        </p>

                        <h3>Investment Summary</h3>

                        <div class="calculator-result-main">

                            <span>Estimated Maturity Value</span>

                            <strong id="maturityValue">$0.00</strong>

                        </div>

                        <div class="calculator-result-row">

                            <span>Initial Investment</span>

                            <strong id="resultPrincipal">$0.00</strong>

                        </div>

                        <div class="calculator-result-row">

                            <span>Total Interest Earned</span>

                            <strong id="interestEarned">$0.00</strong>

                        </div>

                        <div class="calculator-result-row">

                            <span>Investment Term</span>

                            <strong id="resultTerm">—</strong>

                        </div>

                        <div class="calculator-result-row">

                            <span>Compounding Frequency</span>

                            <strong id="resultFrequency">—</strong>

                        </div>

                        <?php if ($isLoggedIn): ?>

                            <!-- Save Calculation Form -->
                            <form method="POST"
                                  action="calculator.php"
                                  id="saveCalculationForm">

                                <input type="hidden"
                                       name="csrf_token"
                                       value="<?php echo calculator_escape($csrfToken); ?>">

                                <input type="hidden"
                                       name="initial_amount"
                                       id="saveAmount">

                                <input type="hidden"
                                       name="interest_rate"
                                       id="saveRate">

                                <input type="hidden"
                                       name="investment_years"
                                       id="saveYears">

                                <input type="hidden"
                                       name="compounding_frequency"
                                       id="saveFrequency">

                                <button type="submit"
                                        class="btn calculator-save"
                                        id="saveCalculationButton"
                                        disabled>
                                    Save Calculation
                                </button>

                            </form>

                        <?php else: ?>

                            <p class="calculator-login-note">
                                <a href="login.php">Log in</a>
                                to save your investment calculations.
                            </p>

                        <?php endif; ?>

                        <p class="calculator-note">
                            These calculations are estimates based on
                            a constant annual interest rate and regular
                            compounding. Actual returns may vary.
                        </p>

                    </div>

                </div>

                <?php if ($isLoggedIn): ?>

                    <!-- Calculation History -->
                    <section class="calculator-history">

                        <p class="section-label">
                            TRACK YOUR FINANCIAL PLANNING
                        </p>

                        <h2>Calculation History</h2>

                        <p>
                            Review your 20 most recently saved
                            investment calculations.
                        </p>

                        <?php if (empty($history)): ?>

                            <div class="calculator-history-empty">

                                <p>
                                    You haven't saved any calculations yet.
                                    Calculate your investment returns and
                                    select Save Calculation to get started.
                                </p>

                            </div>

                        <?php else: ?>

                            <div class="calculator-history-wrapper">

                                <table>

                                    <thead>
                                        <tr>
                                            <th scope="col">Date</th>
                                            <th scope="col">Initial Amount</th>
                                            <th scope="col">Interest Rate</th>
                                            <th scope="col">Term</th>
                                            <th scope="col">Compounding</th>
                                            <th scope="col">Interest Earned</th>
                                            <th scope="col">Maturity Value</th>
                                        </tr>
                                    </thead>

                                    <tbody>

                                        <?php foreach ($history as $item): ?>

                                            <tr>

                                                <td>
                                                    <?php echo calculator_escape($item["created_at"]); ?>
                                                </td>

                                                <td>
                                                    <?php echo calculator_money($item["initial_amount"]); ?>
                                                </td>

                                                <td>
                                                    <?php echo number_format((float) $item["interest_rate"], 2); ?>%
                                                </td>

                                                <td>
                                                    <?php echo (int) $item["term_months"]; ?> months
                                                </td>

                                                <td>
                                                    <?php echo calculator_escape(
                                                        calculator_frequency($item["compounding_frequency"])
                                                    ); ?>
                                                </td>

                                                <td>
                                                    <?php echo calculator_money($item["estimated_interest"]); ?>
                                                </td>

                                                <td>
                                                    <?php echo calculator_money($item["estimated_total"]); ?>
                                                </td>

                                            </tr>

                                        <?php endforeach; ?>

                                    </tbody>

                                </table>

                            </div>

                        <?php endif; ?>

                    </section>

                <?php endif; ?>

            </div>

        </section>

    </main>

    <!-- Footer -->
    <footer>

        <p>&copy; <?php echo date("Y"); ?> SheRock Financial. All rights reserved.</p>

        <p>Save Smarter. Grow Stronger.</p>

        <p class="disclaimer">
            For educational and financial planning purposes only.
            Calculations are illustrative and do not guarantee returns.
            Not financial or investment advice.
        </p>

    </footer>

    <!-- Investment Calculator JavaScript -->
    <script>

        /* ========================================
           Calculator Elements
        ======================================== */

        const calculatorForm = document.getElementById("investmentCalculator");

        const maturityValue = document.getElementById("maturityValue");
        const resultPrincipal = document.getElementById("resultPrincipal");
        const interestEarned = document.getElementById("interestEarned");
        const resultTerm = document.getElementById("resultTerm");
        const resultFrequency = document.getElementById("resultFrequency");

        const saveButton = document.getElementById("saveCalculationButton");

        /* ========================================
           Format Currency
        ======================================== */

        function formatCurrency(amount) {

            return amount.toLocaleString("en-CA", {
                style: "currency",
                currency: "CAD"
            });

        }

        /* ========================================
           Calculate Compound Interest
        ======================================== */

        function calculateInvestment(event) {

            event.preventDefault();

            const principal = Number(
                document.getElementById("initialInvestment").value
            );

            const rate = Number(
                document.getElementById("annualRate").value
            );

            const years = Number(
                document.getElementById("investmentYears").value
            );

            const frequencySelect = document.getElementById("compoundFrequency");

            const frequency = Number(frequencySelect.value);

            if (
                !Number.isFinite(principal) ||
                !Number.isFinite(rate) ||
                !Number.isInteger(years) ||
                principal < 0.01 ||
                principal > 9999999999.99 ||
                rate < 0 ||
                rate > 100 ||
                years < 1 ||
                years > 100
            ) {

                alert("Please enter valid investment details.");
                return;

            }

            const total = principal * Math.pow(
                1 + (rate / 100) / frequency,
                frequency * years
            );

            if (
                !Number.isFinite(total) ||
                total > 9999999999.99
            ) {

                alert("The estimated amount exceeds the supported limit.");
                return;

            }

            const interest = total - principal;

            /* ========================================
               Display Results
            ======================================== */

            maturityValue.textContent = formatCurrency(total);

            resultPrincipal.textContent = formatCurrency(principal);

            interestEarned.textContent = formatCurrency(interest);

            resultTerm.textContent = years +
                (years === 1 ? " year" : " years");

            resultFrequency.textContent =
                frequencySelect.options[frequencySelect.selectedIndex].text.trim();

            /* ========================================
               Prepare Save Form
            ======================================== */

            if (saveButton) {

                document.getElementById("saveAmount").value = principal;
                document.getElementById("saveRate").value = rate;
                document.getElementById("saveYears").value = years;
                document.getElementById("saveFrequency").value = frequency;

                saveButton.disabled = false;

            }

        }

        /* ========================================
           Disable Save After Editing Inputs
        ======================================== */

        if (saveButton) {

            const calculatorInputs = calculatorForm.querySelectorAll(
                "input, select"
            );

            calculatorInputs.forEach(function(input) {

                input.addEventListener("input", function() {
                    saveButton.disabled = true;
                });

                input.addEventListener("change", function() {
                    saveButton.disabled = true;
                });

            });

        }

        /* ========================================
           Calculator Event
        ======================================== */

        calculatorForm.addEventListener("submit", calculateInvestment);

    </script>

</body>
</html>

