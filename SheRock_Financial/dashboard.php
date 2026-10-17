
<?php

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

$first_name = htmlspecialchars(
    $_SESSION["first_name"] ?? "Member",
    ENT_QUOTES,
    "UTF-8"
);

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

    <link rel="stylesheet" href="css/style.css">
</head>
<body>

    <!-- Header -->
    <header>
        <div class="logo">
            <h1>SheRock <span>Financial</span></h1>
        </div>

        <nav aria-label="Main navigation">
            <a href="index.php">Home</a>
            <a href="dashboard.php" aria-current="page">Dashboard</a>
            <a href="logout.php">Logout</a>
        </nav>
    </header>

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

        <!-- Dashboard Features -->
        <section class="services">

            <p class="section-label">YOUR FINANCIAL TOOLS</p>
            <h2>Build Your Financial Future</h2>

            <div class="service-container">

                <article class="service-card">
                    <div class="service-icon">01</div>
                    <h3>Savings Goals</h3>
                    <p>
                        Create and manage your personal savings goals.
                    </p>
                </article>

                <article class="service-card">
                    <div class="service-icon">02</div>
                    <h3>My Investments</h3>
                    <p>
                        Track your investments and monitor maturity dates.
                    </p>
                </article>

                <article class="service-card">
                    <div class="service-icon">03</div>
                    <h3>Investment Calculator</h3>
                    <p>
                        Estimate future growth and plan with confidence.
                    </p>
                </article>

            </div>

        </section>

    </main>

    <!-- Footer -->
    <footer>
        <p>&copy; 2026 SheRock Financial. All rights reserved.</p>
        <p>Save Smarter. Grow Stronger.</p>
    </footer>

</body>
</html>
