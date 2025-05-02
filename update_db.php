<?php
// Include database connection
$conn = require_once 'config.php';

echo "<h2>Database Update Script</h2>";

// Add role column to users table if it doesn't exist
$checkColumn = mysqli_query($conn, "SHOW COLUMNS FROM users LIKE 'role'");
if (mysqli_num_rows($checkColumn) == 0) {
    $alterTable = "ALTER TABLE users ADD COLUMN role ENUM('student', 'teacher', 'admin') DEFAULT 'student'";
    if (mysqli_query($conn, $alterTable)) {
        echo "<p>Successfully added 'role' column to users table.</p>";
    } else {
        echo "<p>Error adding 'role' column: " . mysqli_error($conn) . "</p>";
    }
} else {
    echo "<p>'role' column already exists in users table.</p>";
}

echo "<p>Database update complete. Now you can run the <a href='install.php'>installation script</a> to create the remaining tables.</p>";

// Close connection
mysqli_close($conn);
?> 