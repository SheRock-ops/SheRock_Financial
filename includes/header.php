

<?php
    // Identify the current page
    $currentPage = basename($_SERVER['SCRIPT_NAME']);

    // Check whether the user is logged in
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $isLoggedIn = isset($_SESSION['user_id']);
?>

<!-- SheRock Financial Header -->
<header class="site-header">

    <!-- Logo -->
    <a href="index.php" class="site-logo" aria-label="SheRock Financial Home">
        <img src="images/logo1.png" alt="SheRock Financial Logo">
    </a>

    <!-- Mobile Menu Button -->
    <button
        type="button"
        class="menu-toggle"
        aria-label="Open navigation menu"
        aria-controls="main-navigation"
        aria-expanded="false"
    >
        <span></span>
        <span></span>
        <span></span>
    </button>

    <!-- Navigation -->
    <nav class="site-nav" id="main-navigation" aria-label="Main Navigation">

        <a href="index.php" <?php if ($currentPage === 'index.php') echo 'aria-current="page"'; ?>>Home</a>
        <a href="about.html">About</a>
        <a href="products.php" <?php if ($currentPage === 'products.php') echo 'aria-current="page"'; ?>>Products</a>
        <a href="calculator.php" <?php if ($currentPage === 'calculator.php') echo 'aria-current="page"'; ?>>Calculator</a>

        <?php if ($isLoggedIn): ?>

            <a href="dashboard.php" <?php if ($currentPage === 'dashboard.php') echo 'aria-current="page"'; ?>>Dashboard</a>
            <a href="savings_goals.php" <?php if ($currentPage === 'savings_goals.php') echo 'aria-current="page"'; ?>>Savings Goals</a>
            <a href="investments.php" <?php if ($currentPage === 'investments.php') echo 'aria-current="page"'; ?>>My Investments</a>
            <a href="education.php" <?php if ($currentPage === 'education.php') echo 'aria-current="page"'; ?>>Education</a>
            <a href="logout.php" class="nav-action">Logout</a>

        <?php else: ?>

            <a href="login.php" class="nav-action">Login</a>

        <?php endif; ?>

    </nav>

</header>

<!-- Mobile Navigation Script -->
<script>
    document.addEventListener('DOMContentLoaded', function () {

        const menuToggle = document.querySelector('.menu-toggle');
        const siteNav = document.querySelector('#main-navigation');

        if (!menuToggle || !siteNav) {
            return;
        }

        menuToggle.addEventListener('click', function () {

            const isOpen = siteNav.classList.toggle('nav-open');

            menuToggle.setAttribute('aria-expanded', String(isOpen));
            menuToggle.setAttribute(
                'aria-label',
                isOpen ? 'Close navigation menu' : 'Open navigation menu'
            );

        });

        siteNav.querySelectorAll('a').forEach(function (link) {

            link.addEventListener('click', function () {

                siteNav.classList.remove('nav-open');
                menuToggle.setAttribute('aria-expanded', 'false');
                menuToggle.setAttribute('aria-label', 'Open navigation menu');

            });

        });

    });
</script>
