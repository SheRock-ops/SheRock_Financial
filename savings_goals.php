<?php

session_start();

require_once "includes/db.php";

/* ========================================
   Login Protection
======================================== */

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

$user_id = (int) $_SESSION["user_id"];
$first_name = $_SESSION["first_name"] ?? "Member";

$error = "";
$success = "";
$editing_goal = null;

/* ========================================
   Helper Functions
======================================== */

function escape($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8");
}

function money($amount) {
    return "$" . number_format((float) $amount, 2);
}

/* ========================================
   CSRF Protection
======================================== */

if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

$csrf_token = $_SESSION["csrf_token"];

/* ========================================
   Create, Update, Delete, and Contribute
======================================== */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $token = $_POST["csrf_token"] ?? "";

    if (!is_string($token) ||
        !hash_equals($csrf_token, $token)) {

        $error = "Invalid request. Please refresh the page.";

    } else {

        $action = $_POST["action"] ?? "";

        $goal_id = filter_var(
            $_POST["goal_id"] ?? null,
            FILTER_VALIDATE_INT
        );

        if ($action === "create" || $action === "update") {

            $goal_name = trim($_POST["goal_name"] ?? "");
            $target_input = trim($_POST["target_amount"] ?? "");
            $saved_input = trim($_POST["saved_amount"] ?? "0");
            $target_date = trim($_POST["target_date"] ?? "");

            $target_amount = filter_var(
                $target_input,
                FILTER_VALIDATE_FLOAT
            );

            $saved_amount = filter_var(
                $saved_input,
                FILTER_VALIDATE_FLOAT
            );

            $valid_date = true;

            if ($target_date !== "") {

                $date = DateTimeImmutable::createFromFormat(
                    "!Y-m-d",
                    $target_date
                );

                $valid_date = $date &&
                    $date->format("Y-m-d") === $target_date;

            }

            if ($goal_name === "" ||
                strlen($goal_name) > 100) {

                $error = "Enter a goal name of up to 100 characters.";

            } elseif ($target_amount === false ||
                      !is_finite($target_amount) ||
                      $target_amount <= 0 ||
                      $target_amount > 9999999999.99) {

                $error = "Enter a valid target amount greater than zero.";

            } elseif ($saved_amount === false ||
                      !is_finite($saved_amount) ||
                      $saved_amount < 0 ||
                      $saved_amount > 9999999999.99) {

                $error = "Enter a valid saved amount.";

            } elseif (!$valid_date) {

                $error = "Please enter a valid target date.";

            } elseif ($action === "update" &&
                      (!$goal_id || $goal_id <= 0)) {

                $error = "Invalid savings goal.";

            } else {

                if ($action === "create") {

                    $stmt = $conn->prepare(
                        "INSERT INTO savings_goals
                         (user_id, goal_name, target_amount,
                          saved_amount, target_date)
                         VALUES (?, ?, ?, ?, NULLIF(?, ''))"
                    );

                    $stmt->bind_param(
                        "isdds",
                        $user_id,
                        $goal_name,
                        $target_amount,
                        $saved_amount,
                        $target_date
                    );

                    $stmt->execute();
                    $stmt->close();

                    header("Location: savings_goals.php?status=created");
                    exit;

                } else {

                    $stmt = $conn->prepare(
                        "UPDATE savings_goals
                         SET goal_name = ?,
                             target_amount = ?,
                             saved_amount = ?,
                             target_date = NULLIF(?, '')
                         WHERE goal_id = ?
                         AND user_id = ?"
                    );

                    $stmt->bind_param(
                        "sddsii",
                        $goal_name,
                        $target_amount,
                        $saved_amount,
                        $target_date,
                        $goal_id,
                        $user_id
                    );

                    $stmt->execute();
                    $stmt->close();

                    header("Location: savings_goals.php?status=updated");
                    exit;

                }

            }

        } elseif ($action === "contribute") {

            $amount = filter_var(
                $_POST["contribution"] ?? null,
                FILTER_VALIDATE_FLOAT
            );

            if (!$goal_id || $goal_id <= 0 ||
                $amount === false ||
                !is_finite($amount) ||
                $amount <= 0 ||
                $amount > 9999999999.99) {

                $error = "Enter a valid contribution amount.";

            } else {

                $stmt = $conn->prepare(
                    "UPDATE savings_goals
                     SET saved_amount = saved_amount + ?
                     WHERE goal_id = ?
                     AND user_id = ?
                     AND saved_amount + ? <= 9999999999.99"
                );

                $stmt->bind_param(
                    "diid",
                    $amount,
                    $goal_id,
                    $user_id,
                    $amount
                );

                $stmt->execute();

                $updated = $stmt->affected_rows;
                $stmt->close();

                if ($updated > 0) {

                    header("Location: savings_goals.php?status=contributed");
                    exit;

                } else {

                    $error = "Contribution could not be added.";

                }

            }

        } elseif ($action === "delete") {

            if (!$goal_id || $goal_id <= 0) {

                $error = "Invalid savings goal.";

            } else {

                $stmt = $conn->prepare(
                    "DELETE FROM savings_goals
                     WHERE goal_id = ?
                     AND user_id = ?"
                );

                $stmt->bind_param(
                    "ii",
                    $goal_id,
                    $user_id
                );

                $stmt->execute();

                $deleted = $stmt->affected_rows;
                $stmt->close();

                if ($deleted > 0) {

                    header("Location: savings_goals.php?status=deleted");
                    exit;

                } else {

                    $error = "Savings goal could not be found.";

                }

            }

        } else {

            $error = "Invalid action. Please try again.";

        }

    }

}

/* ========================================
   Success Messages
======================================== */

$messages = [
    "created" => "Your savings goal has been created!",
    "updated" => "Your savings goal has been updated!",
    "contributed" => "Your savings contribution has been added!",
    "deleted" => "Your savings goal has been deleted."
];

$status = $_GET["status"] ?? "";

if (is_string($status) && isset($messages[$status])) {
    $success = $messages[$status];
}

/* ========================================
   Load Goal for Editing
======================================== */

$edit_id = filter_var(
    $_GET["edit"] ?? null,
    FILTER_VALIDATE_INT
);

if ($edit_id && $edit_id > 0) {

    $stmt = $conn->prepare(
        "SELECT goal_id, goal_name, target_amount,
                saved_amount, target_date
         FROM savings_goals
         WHERE goal_id = ?
         AND user_id = ?
         LIMIT 1"
    );

    $stmt->bind_param("ii", $edit_id, $user_id);
    $stmt->execute();

    $result = $stmt->get_result();
    $editing_goal = $result->fetch_assoc();

    $stmt->close();

}

/* ========================================
   Load Savings Goals
======================================== */

$stmt = $conn->prepare(
    "SELECT goal_id, goal_name, target_amount,
            saved_amount, target_date, created_at
     FROM savings_goals
     WHERE user_id = ?
     ORDER BY created_at DESC, goal_id DESC"
);

$stmt->bind_param("i", $user_id);
$stmt->execute();

$result = $stmt->get_result();
$goals = $result->fetch_all(MYSQLI_ASSOC);

$stmt->close();

/* ========================================
   Savings Summary
======================================== */

$total_goals = count($goals);
$total_target = 0;
$total_saved = 0;
$completed_goals = 0;

foreach ($goals as $goal) {

    $target = (float) $goal["target_amount"];
    $saved = (float) $goal["saved_amount"];

    $total_target += $target;
    $total_saved += $saved;

    if ($target > 0 && $saved >= $target) {
        $completed_goals++;
    }

}

$overall_progress = $total_target > 0
    ? min(100, ($total_saved / $total_target) * 100)
    : 0;

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Manage your savings goals with SheRock Financial.">
    <title>Savings Goals | SheRock Financial</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css?v=2">
</head>
<body>

        <!-- Shared Header -->
            <?php include __DIR__ . '/includes/header.php'; ?>
        

    <!-- Main Content -->
    <main>

        <!-- Savings Hero -->
        <section class="savings-hero">

            <div class="savings-container">

                <p class="section-label">
                    YOUR FINANCIAL JOURNEY
                </p>

                <h2>My Savings Goals</h2>

                <p>
                    Welcome, <?php echo escape($first_name); ?>!
                    Turn your financial dreams into achievable goals.
                </p>

            </div>

        </section>

        <!-- Savings Summary -->
        <section class="savings-section">

            <div class="savings-container">

                <p class="section-label">YOUR PROGRESS</p>

                <h2>Savings Overview</h2>

                <div class="savings-summary">

                    <div class="savings-summary-card">
                        <h3>Total Goals</h3>
                        <p><?php echo $total_goals; ?></p>
                    </div>

                    <div class="savings-summary-card">
                        <h3>Total Saved</h3>
                        <p><?php echo money($total_saved); ?></p>
                    </div>

                    <div class="savings-summary-card">
                        <h3>Total Target</h3>
                        <p><?php echo money($total_target); ?></p>
                    </div>

                    <div class="savings-summary-card">
                        <h3>Completed Goals</h3>
                        <p><?php echo $completed_goals; ?></p>
                    </div>

                </div>

                <div class="savings-overall">

                    <h3>Overall Savings Progress</h3>

                    <div class="savings-progress"
                         role="progressbar"
                         aria-label="Overall savings progress"
                         aria-valuemin="0"
                         aria-valuemax="100"
                         aria-valuenow="<?php echo round($overall_progress); ?>">

                        <div class="savings-progress-fill"
                             style="width: <?php echo round($overall_progress, 2); ?>%;">
                        </div>

                    </div>

                    <p>
                        <?php echo number_format($overall_progress, 1); ?>%
                        of your combined savings targets
                    </p>

                </div>

            </div>

        </section>

        <!-- Goal Form -->
        <section class="savings-section savings-form-section">

            <div class="savings-container">

                <p class="section-label">
                    SAVE SMARTER. GROW STRONGER.
                </p>

                <h2>
                    <?php echo $editing_goal ? "Edit Savings Goal" : "Create a Savings Goal"; ?>
                </h2>

                <?php if ($success !== ""): ?>
                    <p class="auth-success" role="status">
                        <?php echo escape($success); ?>
                    </p>
                <?php endif; ?>

                <?php if ($error !== ""): ?>
                    <p class="auth-error" role="alert">
                        <?php echo escape($error); ?>
                    </p>
                <?php endif; ?>

                <div class="savings-form-card">

                    <form action="savings_goals.php"
                          method="POST"
                          class="auth-form">

                        <input type="hidden"
                               name="csrf_token"
                               value="<?php echo escape($csrf_token); ?>">

                        <input type="hidden"
                               name="action"
                               value="<?php echo $editing_goal ? "update" : "create"; ?>">

                        <?php if ($editing_goal): ?>
                            <input type="hidden"
                                   name="goal_id"
                                   value="<?php echo (int) $editing_goal["goal_id"]; ?>">
                        <?php endif; ?>

                        <label for="goal_name">Goal Name</label>

                        <input type="text"
                               id="goal_name"
                               name="goal_name"
                               maxlength="100"
                               placeholder="Example: Emergency Fund"
                               value="<?php echo escape($editing_goal["goal_name"] ?? ""); ?>"
                               required>

                        <label for="target_amount">
                            Target Amount ($)
                        </label>

                        <input type="number"
                               id="target_amount"
                               name="target_amount"
                               min="0.01"
                               max="9999999999.99"
                               step="0.01"
                               placeholder="5000.00"
                               value="<?php echo escape($editing_goal["target_amount"] ?? ""); ?>"
                               required>

                        <label for="saved_amount">
                            Amount Already Saved ($)
                        </label>

                        <input type="number"
                               id="saved_amount"
                               name="saved_amount"
                               min="0"
                               max="9999999999.99"
                               step="0.01"
                               placeholder="0.00"
                               value="<?php echo escape($editing_goal["saved_amount"] ?? "0"); ?>"
                               required>

                        <label for="target_date">
                            Target Date (Optional)
                        </label>

                        <input type="date"
                               id="target_date"
                               name="target_date"
                               value="<?php echo escape($editing_goal["target_date"] ?? ""); ?>">

                        <button type="submit" class="btn auth-submit">
                            <?php echo $editing_goal ? "Update Goal" : "Create Goal"; ?>
                        </button>

                        <?php if ($editing_goal): ?>
                            <a href="savings_goals.php"
                               class="savings-cancel">
                                Cancel Editing
                            </a>
                        <?php endif; ?>

                    </form>

                </div>

            </div>

        </section>

        <!-- Savings Goals List -->
        <section class="savings-section">

            <div class="savings-container">

                <p class="section-label">
                    TRACK YOUR SUCCESS
                </p>

                <h2>Your Savings Goals</h2>

                <?php if (empty($goals)): ?>

                    <div class="savings-empty">

                        <h3>No Savings Goals Yet</h3>

                        <p>
                            Create your first savings goal and
                            begin building your financial future.
                        </p>

                    </div>

                <?php else: ?>

                    <div class="savings-grid">

                        <?php foreach ($goals as $goal): ?>

                            <?php

                            $target = (float) $goal["target_amount"];
                            $saved = (float) $goal["saved_amount"];

                            $progress = $target > 0
                                ? min(100, ($saved / $target) * 100)
                                : 0;

                            $remaining = max(0, $target - $saved);
                            $is_complete = $target > 0 && $saved >= $target;

                            ?>

                            <article class="savings-goal-card">

                                <p class="section-label">
                                    <?php echo $is_complete ? "GOAL COMPLETED" : "SAVINGS IN PROGRESS"; ?>
                                </p>

                                <h3>
                                    <?php echo escape($goal["goal_name"]); ?>
                                </h3>

                                <p>
                                    Saved:
                                    <strong><?php echo money($saved); ?></strong>
                                </p>

                                <p>
                                    Target:
                                    <strong><?php echo money($target); ?></strong>
                                </p>

                                <div class="savings-progress"
                                     role="progressbar"
                                     aria-label="<?php echo escape($goal["goal_name"]); ?> progress"
                                     aria-valuemin="0"
                                     aria-valuemax="100"
                                     aria-valuenow="<?php echo round($progress); ?>">

                                    <div class="savings-progress-fill"
                                         style="width: <?php echo round($progress, 2); ?>%;">
                                    </div>

                                </div>

                                <p class="savings-percentage">
                                    <?php echo number_format($progress, 1); ?>% Complete
                                </p>

                                <p>
                                    Remaining:
                                    <strong><?php echo money($remaining); ?></strong>
                                </p>

                                <?php if (!empty($goal["target_date"])): ?>
                                    <p>
                                        Target Date:
                                        <strong>
                                            <?php echo escape(date("F j, Y", strtotime($goal["target_date"]))); ?>
                                        </strong>
                                    </p>
                                <?php endif; ?>

                                <!-- Add Contribution -->
                                <form action="savings_goals.php"
                                      method="POST"
                                      class="savings-contribution-form">

                                    <input type="hidden"
                                           name="csrf_token"
                                           value="<?php echo escape($csrf_token); ?>">

                                    <input type="hidden"
                                           name="action"
                                           value="contribute">

                                    <input type="hidden"
                                           name="goal_id"
                                           value="<?php echo (int) $goal["goal_id"]; ?>">

                                    <label for="contribution_<?php echo (int) $goal["goal_id"]; ?>">
                                        Add Savings ($)
                                    </label>

                                    <input type="number"
                                           id="contribution_<?php echo (int) $goal["goal_id"]; ?>"
                                           name="contribution"
                                           min="0.01"
                                           max="9999999999.99"
                                           step="0.01"
                                           placeholder="100.00"
                                           required>

                                    <button type="submit" class="btn">
                                        Add Savings
                                    </button>

                                </form>

                                <!-- Goal Actions -->
                                <div class="savings-actions">

                                    <a href="savings_goals.php?edit=<?php echo (int) $goal["goal_id"]; ?>"
                                       class="btn btn-outline">
                                        Edit Goal
                                    </a>

                                    <form action="savings_goals.php"
                                          method="POST"
                                          onsubmit="return confirm('Delete this savings goal?');">

                                        <input type="hidden"
                                               name="csrf_token"
                                               value="<?php echo escape($csrf_token); ?>">

                                        <input type="hidden"
                                               name="action"
                                               value="delete">

                                        <input type="hidden"
                                               name="goal_id"
                                               value="<?php echo (int) $goal["goal_id"]; ?>">

                                        <button type="submit"
                                                class="savings-delete">
                                            Delete
                                        </button>

                                    </form>

                                </div>

                            </article>

                        <?php endforeach; ?>

                    </div>

                <?php endif; ?>

            </div>

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

