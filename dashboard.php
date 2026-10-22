<?php

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

require_once "includes/db.php";

$first_name = htmlspecialchars(
    $_SESSION["first_name"] ?? "Member",
    ENT_QUOTES,
    "UTF-8"
);

$userId = (int) $_SESSION["user_id"];

/* ========================================
   Dashboard Financial Overview
======================================== */

$totalSavings = 0;
$totalGoals = 0;
$totalTarget = 0;
$totalInvested = 0;
$activeInvestments = 0;
$savingsAvailable = false;
$investmentsAvailable = false;

/* ========================================
   Load Savings Summary
======================================== */

try {

    $stmt = $conn->prepare(
        "SELECT COUNT(*) AS total_goals,
                COALESCE(SUM(saved_amount), 0) AS total_saved,
                COALESCE(SUM(target_amount), 0) AS total_target
         FROM savings_goals
         WHERE user_id = ?"
    );

    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $savingsSummary = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $totalGoals = (int) $savingsSummary["total_goals"];
    $totalSavings = (float) $savingsSummary["total_saved"];
    $totalTarget = (float) $savingsSummary["total_target"];
    $savingsAvailable = true;

} catch (mysqli_sql_exception $exception) {

    error_log("Dashboard savings summary: " . $exception->getMessage());

}

/* ========================================
   Load Investment Summary
======================================== */

try {

    $stmt = $conn->prepare(
        "SELECT COALESCE(SUM(
                    CASE WHEN status <> 'Cancelled'
                         THEN deposit_amount ELSE 0 END
                ), 0) AS total_invested,
                COALESCE(SUM(
                    CASE WHEN status = 'Active'
                         THEN 1 ELSE 0 END
                ), 0) AS active_investments
         FROM investments
         WHERE user_id = ?"
    );

    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $investmentSummary = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $totalInvested = (float) $investmentSummary["total_invested"];
    $activeInvestments = (int) $investmentSummary["active_investments"];
    $investmentsAvailable = true;

} catch (mysqli_sql_exception $exception) {

    error_log("Dashboard investment summary: " . $exception->getMessage());

}

$overallProgress = $totalTarget > 0
    ? min(100, max(0, ($totalSavings / $totalTarget) * 100))
    : 0;

function dashboard_money($amount) {
    return "$" . number_format((float) $amount, 2);
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | SheRock Financial</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css?v=6">
    <style>
        /* ========================================
           Dashboard Financial Overview
        ======================================== */

        .dashboard-overview {
            padding: 70px 8%;
            background: #F5F8FC;
            text-align: center;
        }

        .dashboard-overview h2 {
            color: #142D4E;
            font-family: "Playfair Display", serif;
            margin: 12px 0 15px;
        }

        .dashboard-overview-intro {
            color: #526782;
            max-width: 650px;
            margin: 0 auto 35px;
            line-height: 1.7;
        }

        .dashboard-summary-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 20px;
            max-width: 1200px;
            margin: 0 auto;
        }

        .dashboard-summary-card {
            background: #FFFFFF;
            border-top: 4px solid #D7B879;
            border-radius: 12px;
            padding: 28px 18px;
            box-shadow: 0 10px 30px rgba(11, 29, 51, 0.07);
            min-width: 0;
        }

        .dashboard-summary-card h3 {
            font-family: "DM Sans", sans-serif;
            font-size: 14px;
            font-weight: 600;
            color: #526782;
            margin: 0 0 16px;
        }

        .dashboard-summary-card strong {
            display: block;
            font-size: clamp(1.45rem, 2.4vw, 2rem);
            color: #142D4E;
            overflow-wrap: anywhere;
        }

        .dashboard-summary-card small {
            display: block;
            color: #526782;
            margin-top: 12px;
            line-height: 1.5;
        }

        .dashboard-overall-progress {
            max-width: 1200px;
            margin: 30px auto 0;
            padding: 25px;
            border-radius: 12px;
            background: #FFFFFF;
            text-align: left;
        }

        .dashboard-overall-progress h3 {
            color: #142D4E;
            font-family: "Playfair Display", serif;
            margin: 0 0 14px;
        }

        .dashboard-progress-track {
            height: 13px;
            background: #E5EBF3;
            border-radius: 20px;
            overflow: hidden;
        }

        .dashboard-progress-fill {
            height: 100%;
            background: #D7B879;
            border-radius: 20px;
        }

        .dashboard-overall-progress p,
        .dashboard-summary-note {
            color: #526782;
            line-height: 1.6;
        }

        .dashboard-summary-note {
            margin: 25px auto 0;
            max-width: 900px;
            font-size: 13px;
        }

        .dashboard-data-warning {
            max-width: 900px;
            margin: 0 auto 25px;
            padding: 15px;
            background: #FFF4E5;
            color: #795200;
            border-left: 4px solid #D7B879;
            border-radius: 6px;
        }

        @media (max-width: 900px) {
            .dashboard-summary-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 550px) {
            .dashboard-overview {
                padding: 50px 5%;
            }

            .dashboard-summary-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>

    <!-- Shared Header -->
    <?php include __DIR__ . '/includes/header.php'; ?>

    <!-- Main Content -->
    <main>

        <!-- Dashboard Welcome -->
        <section class="dashboard-welcome">

            <p class="hero-label">YOUR FINANCIAL JOURNEY</p>

            <h2>Welcome, <?php echo $first_name; ?>!</h2>

            <p>
                Your financial future starts with smart decisions.
                Explore your savings, investments, and financial goals.
            </p>

        </section>

        <!-- Dashboard Financial Overview -->
        <section class="dashboard-overview" aria-labelledby="dashboard-overview-title">

            <p class="section-label">YOUR FINANCIAL SNAPSHOT</p>

            <h2 id="dashboard-overview-title">Your Financial Overview</h2>

            <p class="dashboard-overview-intro">
                A quick look at your recorded savings goals and investments.
            </p>

            <?php if (!$savingsAvailable || !$investmentsAvailable): ?>
                <p class="dashboard-data-warning" role="alert">
                    Some financial information is temporarily unavailable.
                    Please try again later.
                </p>
            <?php endif; ?>

            <div class="dashboard-summary-grid">

                <article class="dashboard-summary-card">
                    <h3>Total Savings</h3>
                    <strong>
                        <?php echo $savingsAvailable ? dashboard_money($totalSavings) : "—"; ?>
                    </strong>
                    <small>Amount recorded toward savings goals</small>
                </article>

                <article class="dashboard-summary-card">
                    <h3>Savings Goals</h3>
                    <strong>
                        <?php echo $savingsAvailable ? $totalGoals : "—"; ?>
                    </strong>
                    <small>Your current number of goals</small>
                </article>

                <article class="dashboard-summary-card">
                    <h3>Recorded Investments</h3>
                    <strong>
                        <?php echo $investmentsAvailable ? dashboard_money($totalInvested) : "—"; ?>
                    </strong>
                    <small>Total deposits excluding cancelled records</small>
                </article>

                <article class="dashboard-summary-card">
                    <h3>Active Investments</h3>
                    <strong>
                        <?php echo $investmentsAvailable ? $activeInvestments : "—"; ?>
                    </strong>
                    <small>Includes active records past maturity</small>
                </article>

            </div>

            <?php if ($savingsAvailable): ?>
                <!-- Overall Savings Progress -->
                <div class="dashboard-overall-progress">

                    <h3>Overall Savings Progress</h3>

                    <div class="dashboard-progress-track"
                         role="progressbar"
                         aria-label="Overall savings progress"
                         aria-valuemin="0"
                         aria-valuemax="100"
                         aria-valuenow="<?php echo round($overallProgress); ?>">

                        <div class="dashboard-progress-fill"
                             style="width: <?php echo number_format($overallProgress, 2, ".", ""); ?>%;">
                        </div>

                    </div>

                    <p>
                        <?php echo number_format($overallProgress, 1); ?>%
                        of your combined savings targets
                    </p>

                </div>
            <?php endif; ?>

            <p class="dashboard-summary-note">
                These are self-recorded planning amounts, not verified bank balances
                or transactions. Savings and investments are displayed separately.
            </p>

        </section>

        <!-- Dashboard Features -->
        <section class="services dashboard-services">

            <p class="section-label">YOUR FINANCIAL TOOLS</p>

            <h2>Build Your Financial Future</h2>

            <p class="dashboard-intro">
                Everything you need to save smarter, explore investments,
                and build financial confidence in one place.
            </p>

            <div class="service-container dashboard-tools">

                <!-- Savings Goals -->
                <article class="service-card dashboard-tool-card">

                    <div class="service-icon">01</div>

                    <h3>Savings Goals</h3>

                    <p>
                        Create savings goals, record contributions,
                        and monitor your progress.
                    </p>

                    <a href="savings_goals.php" class="dashboard-tool-link">
                        Manage Savings Goals &rarr;
                    </a>

                </article>

                <!-- Investment Products -->
                <article class="service-card dashboard-tool-card">

                    <div class="service-icon">02</div>

                    <h3>Investment Products</h3>

                    <p>
                        Explore investment products, compare interest rates,
                        and discover different savings options.
                    </p>

                    <a href="products.php" class="dashboard-tool-link">
                        Explore Products &rarr;
                    </a>

                </article>

                <!-- Investment Calculator -->
                <article class="service-card dashboard-tool-card">

                    <div class="service-icon">03</div>

                    <h3>Investment Calculator</h3>

                    <p>
                        Estimate potential investment growth
                        and plan for future financial milestones.
                    </p>

                    <a href="calculator.php" class="dashboard-tool-link">
                        Calculate Returns &rarr;
                    </a>

                </article>

                <!-- My Investments -->
                <article class="service-card dashboard-tool-card">

                    <div class="service-icon">04</div>

                    <h3>My Investments</h3>

                    <p>
                        Record investments, review estimated maturity values,
                        and track investment status.
                    </p>

                    <a href="investments.php" class="dashboard-tool-link">
                        Track Investments &rarr;
                    </a>

                </article>

                <!-- Financial Education -->
                <article class="service-card dashboard-tool-card">

                    <div class="service-icon">05</div>

                    <h3>Financial Education Hub</h3>

                    <p>
                        Learn about budgeting, saving, investing,
                        and financial security through interactive lessons.
                    </p>

                    <a href="education.php" class="dashboard-tool-link">
                        Start Learning &rarr;
                    </a>

                </article>

                <!-- Financial Literacy Quiz -->
                <article class="service-card dashboard-tool-card">

                    <div class="service-icon">06</div>

                    <h3>Financial Literacy Quiz</h3>

                    <p>
                        Test your financial knowledge with six questions,
                        instant feedback, and your final quiz score.
                    </p>

                    <a href="education.php#financial-quiz" class="dashboard-tool-link">
                        Take the Quiz &rarr;
                    </a>

                </article>

            </div>

        </section>

    </main>

    <!-- Footer -->
    <footer>
        <p>&copy; <?php echo date("Y"); ?> SheRock Financial. All rights reserved.</p>
        <p>Save Smarter. Grow Stronger.</p>
        <p>For educational and financial planning purposes only.</p>
    </footer>

</body>
</html>
