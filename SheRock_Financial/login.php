<?php

session_start();

require_once "includes/db.php";

if (isset($_SESSION["user_id"])) {
    header("Location: dashboard.php");
    exit;
}

$error = "";
$email = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = strtolower(trim($_POST["email"] ?? ""));
    $password = $_POST["password"] ?? "";

    if ($email === "" || $password === "") {

        $error = "Please enter your email and password.";

    } else {

        $stmt = $conn->prepare(
            "SELECT user_id, first_name, password_hash
             FROM users
             WHERE email = ?
             LIMIT 1"
        );

        $stmt->bind_param("s", $email);
        $stmt->execute();

        $stmt->bind_result(
            $user_id,
            $first_name,
            $password_hash
        );

        $found = $stmt->fetch();

        $stmt->close();

        if ($found && password_verify($password, $password_hash)) {

            session_regenerate_id(true);

            $_SESSION["user_id"] = $user_id;
            $_SESSION["first_name"] = $first_name;

            header("Location: dashboard.php");
            exit;

        } else {

            $error = "Invalid email or password.";

        }

    }

}

function escape($value) {
    return htmlspecialchars($value, ENT_QUOTES, "UTF-8");
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Sign in to SheRock Financial.">
    <title>Login | SheRock Financial</title>

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
            <a href="about.php">About</a>
            <a href="products.php">Products</a>
            <a href="calculator.php">Calculator</a>
            <a href="register.php">Register</a>
        </nav>
    </header>

    <!-- Main Content -->
    <main class="auth-main">

        <!-- Login Section -->
        <section class="auth-card">

            <p class="section-label">WELCOME BACK</p>

            <h2>Sign In</h2>

            <p class="auth-description">
                Continue your journey toward financial confidence.
            </p>

            <?php if (isset($_GET["registered"])): ?>
                <p class="auth-success" role="status">
                    Registration successful! Please sign in.
                </p>
            <?php endif; ?>

            <?php if ($error !== ""): ?>
                <p class="auth-error" role="alert">
                    <?php echo escape($error); ?>
                </p>
            <?php endif; ?>

            <form action="login.php" method="POST" class="auth-form">

                <label for="email">Email Address</label>
                <input type="email"
                       id="email"
                       name="email"
                       autocomplete="email"
                       value="<?php echo escape($email); ?>"
                       required>

                <label for="password">Password</label>
                <input type="password"
                       id="password"
                       name="password"
                       autocomplete="current-password"
                       required>

                <button type="submit" class="btn auth-submit">
                    Sign In
                </button>

            </form>

            <p class="auth-link">
                Don't have an account?
                <a href="register.php">Create Account</a>
            </p>

        </section>

    </main>

    <!-- Footer -->
    <footer>
        <p>&copy; 2026 SheRock Financial. All rights reserved.</p>
        <p>Save Smarter. Grow Stronger.</p>
    </footer>

</body>
</html>

