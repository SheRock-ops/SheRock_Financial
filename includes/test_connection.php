
<?php

require_once "db.php";

$result = $conn->query("SHOW TABLES");

if ($result) {
    echo "<h1>SheRock Financial</h1>";
    echo "<p>Database connection successful!</p>";
    echo "<h3>Tables found:</h3>";

    while ($row = $result->fetch_array()) {
        echo htmlspecialchars($row[0]) . "<br>";
    }
} else {
    echo "Could not retrieve database tables.";
}

$conn->close();

?>
