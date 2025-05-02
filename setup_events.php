<?php
// Display header
echo "<h2>Setting Up Events Feature</h2>";

// Include database connection
$conn = require_once 'config.php';
echo "Connected to database.<br>";

// Create event registrations table
$sql = "CREATE TABLE IF NOT EXISTS event_registrations (
    id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    experience_level VARCHAR(50) NOT NULL,
    event_id INT(11) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY event_email (event_id, email)
)";

if (mysqli_query($conn, $sql)) {
    echo "Created event_registrations table.<br>";
} else {
    echo "Error creating event_registrations table: " . mysqli_error($conn) . "<br>";
}

// Check if events table already has our events
$result = mysqli_query($conn, "SELECT COUNT(*) AS count FROM events WHERE title LIKE 'Web Development Bootcamp' OR title LIKE 'AI & ML Workshop'");
$row = mysqli_fetch_assoc($result);

if ($row['count'] < 2) {
    // Clear existing events if needed
    mysqli_query($conn, "DELETE FROM events WHERE title LIKE 'Web Development Bootcamp' OR title LIKE 'AI & ML Workshop'");
    
    // Insert the two events
    $events = [
        ["Web Development Bootcamp", "Learn modern web development techniques", "2025-04-20 17:00:00", "Online"],
        ["AI & ML Workshop", "Introduction to machine learning algorithms", "2025-04-22 18:00:00", "Online"]
    ];
    
    foreach ($events as $event) {
        $sql = "INSERT INTO events (title, description, event_date, location) VALUES (?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "ssss", $event[0], $event[1], $event[2], $event[3]);
        
        if (mysqli_stmt_execute($stmt)) {
            echo "Added event: " . htmlspecialchars($event[0]) . "<br>";
        } else {
            echo "Error adding event: " . mysqli_error($conn) . "<br>";
        }
    }
} else {
    echo "Events already exist in the database.<br>";
}

// Close database connection
mysqli_close($conn);

echo "<p>Event system setup is complete!</p>";
echo "<p>You can now access the event pages:</p>";
echo "<ul>";
echo "<li><a href='web_bootcamp.php'>Web Development Bootcamp</a></li>";
echo "<li><a href='ai_ml_workshop.php'>AI & ML Workshop</a></li>";
echo "</ul>";
?> 