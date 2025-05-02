<?php
// Display header
echo "<h2>Removing Live Lecture Feature</h2>";

// Include database connection
$conn = require_once 'config.php';
echo "Connected to database.<br>";

// 1. Drop the lecture tables
$tablesToDrop = ['lecture_participants', 'live_lectures'];

foreach ($tablesToDrop as $table) {
    if (mysqli_query($conn, "DROP TABLE IF EXISTS $table")) {
        echo "Dropped table: $table<br>";
    } else {
        echo "Error dropping table $table: " . mysqli_error($conn) . "<br>";
    }
}

// 2. Remove any teachers (revert to students)
$sql = "UPDATE users SET role = 'student' WHERE role = 'teacher'";
if (mysqli_query($conn, $sql)) {
    $count = mysqli_affected_rows($conn);
    echo "Updated $count teacher(s) to students.<br>";
} else {
    echo "Error updating teachers: " . mysqli_error($conn) . "<br>";
}

// Close database connection
mysqli_close($conn);

echo "<p>The live lecture feature has been removed from the database.</p>";
echo "<p>You should also delete or disable these files to completely remove the feature:</p>";
echo "<ul>";
echo "<li>teacher_dashboard.php</li>";
echo "<li>lecture_room.php</li>";
echo "<li>lectures.php</li>";
echo "</ul>";

echo "<p>Note: You may also need to update any menu items or links to these pages in your header/navigation.</p>";
echo "<br><a href='index.php' class='btn btn-primary'>Return to homepage</a>";
?> 