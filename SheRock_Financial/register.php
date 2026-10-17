
<?php

/* ========================================
   SheRock Financial
   User Registration
======================================== */

session_start();

require_once "includes/db.php";

/* ========================================
   Redirect Logged-In Users
======================================== */

if (isset($_SESSION["user_id"])) {
    header("Location: dashboard.php");
    exit;
}

/* ========================================
   Form Variables
======================================== */

$error = "";

$first_name = "";
$last_name = "";
$email = "";

/* ========================================
   Registration Form Processing
======================================== */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $first_name = trim($_POST["first_name"] ?? "");
    $last_name = trim($_POST["last_name"] ?? "");
    $email = strtolower(trim($_POST["email"] ?? ""));

    $password = $_POST["password"] ?? "";
    $confirm_password = $_POST["confirm_password"] ?? "";

    /* ========================================
       Validate Form Fields
    ======================================== */

    if (
        $first_name === "" ||
        $last_name === "" ||
        $email === "" ||
        $password === "" ||
        $confirm_password === ""
    ) {

        $error = "Please complete all fields.";

    } elseif (
        mb_strlen($first_name) > 100 ||
        mb_strlen($last_name) > 100 ||
        strlen($email) > 255
    ) {

        $error = "One or more fields are too long.";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $error = "Please enter a valid email address.";

    } elseif (strlen($password) < 12) {

        $error = "Your password must contain at least 12 characters.";

    } elseif (strlen($password) > 4096) {

        $error = "Your password is too long.";

    } elseif ($password !== $confirm_password) {

        $error = "Passwords do not match.";

    } else {

        /* ========================================
           Check Existing Email
        ======================================== */

        $stmt = $conn->prepare(
            "SELECT user_id
             FROM users
             WHERE email = ?
             LIMIT 1"
        );

        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {

            $error = "An account with this email already exists.";

        }

        $stmt->close();

        /* ========================================
           Create New User
        ======================================== */

        if ($error === "") {

            $password_hash = password_hash(
                $password,
                PASSWORD_DEFAULT
            );

            $stmt = $conn->prepare(
                "INSERT INTO users
                 (first_name, last_name, email, password_hash)
                 VALUES (?, ?, ?, ?)"
            );

            $stmt->bind_param(
                "ssss",
                $first_name,
                $last_name,
                $email,
                $password_hash
            );

            try {

                $stmt->execute();

                header("Location: login.php?registered=1");
                exit;

            } catch (mysqli_sql_exception $e) {

                if ($e->getCode() == 1062) {

                    $error = "An account with this email already exists.";

                } else {

                    error_log($e->getMessage());

                    $error = "Registration could not be completed. Please try again.";

                }

            } finally {

                $stmt->close();

            }

        }

    }

}

/* ========================================
   Escape HTML Output
======================================== */

function escape($value) {
    return htmlspecialchars($value, ENT_QUOTES, "UTF-8");
}

/* ========================================
   CSRF Token
======================================== */

if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Create your SheRock Financial account and start your journey toward financial confidence.">

    <title>Register | SheRock Financial</title>

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
            <a href="login.php">Login</a>
        </nav>
    </header>

    <!-- Main Content -->
    <main class="auth-main">

        <!-- Registration Section -->
        <section class="auth-card">

            <p class="section-label">
                START YOUR FINANCIAL JOURNEY
            </p>

            <h2>Create Your Account</h2>

            <p class="auth-description">
                Join SheRock Financial and take the first step
                toward a stronger financial future.
            </p>

            <!-- Registration Error -->
            <?php if ($error !== ""): ?>

                <p class="auth-error" role="alert">
                    <?php echo escape($error); ?>
                </p>

            <?php endif; ?>

            <!-- Registration Form -->
            <form action="register.php" method="POST" class="auth-form">

                <input type="hidden"
                       name="csrf_token"
                       value="<?php echo escape($_SESSION["csrf_token"]); ?>">

                <label for="first_name">First Name</label>

                <input type="text"
                       id="first_name"
                       name="first_name"
                       maxlength="100"
                       autocomplete="given-name"
                       value="<?php echo escape($first_name); ?>"
                       required>

                <label for="last_name">Last Name</label>

                <input type="text"
                       id="last_name"
                       name="last_name"
                       maxlength="100"
                       autocomplete="family-name"
                       value="<?php echo escape($last_name); ?>"
                       required>

                <label for="email">Email Address</label>

                <input type="email"
                       id="email"
                       name="email"
                       maxlength="255"
                       autocomplete="email"
                       value="<?php echo escape($email); ?>"
                       required>

                <label for="password">Password</label>

                <input type="password"
                       id="password"
                       name="password"
                       minlength="12"
                       autocomplete="new-password"
                       required>

                <label for="confirm_password">Confirm Password</label>

                <input type="password"
                       id="confirm_password"
                       name="confirm_password"
                       minlength="12"
                       autocomplete="new-password"
                       required>

                <button type="submit" class="btn auth-submit">
                    Create Account
                </button>

            </form>

            <!-- Login Link -->
            <p class="auth-link">
                Already have an account?
                <a href="login.php">Sign In</a>
            </p>

        </section>

    </main>

    <!-- Footer -->
    <footer>
        <p>&copy; 2026 SheRock Financial. All rights reserved.</p>
        <p>Save Smarter. Grow Stronger.</p>

        <p class="disclaimer">
            For educational and financial planning purposes only.
            Not financial or investment advice.
        </p>
    </footer>

</body>
</html>

