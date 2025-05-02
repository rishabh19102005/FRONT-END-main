<?php
// Display header
echo "<h2>Database Fix Script</h2>";

// Include database connection
$conn = require_once 'config.php';
echo "Connected to database.<br>";

// First, check if the users table has the role column
$hasRoleColumn = false;
$result = mysqli_query($conn, "SHOW COLUMNS FROM users LIKE 'role'");
if ($result) {
    $hasRoleColumn = (mysqli_num_rows($result) > 0);
}

if (!$hasRoleColumn) {
    echo "The 'role' column is missing from the users table. Fixing it now...<br>";
    
    // Create a temporary table with the new structure
    $sql = "CREATE TABLE users_temp (
        id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        email VARCHAR(255) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        course VARCHAR(50),
        role ENUM('student', 'teacher', 'admin') DEFAULT 'student',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    
    if (mysqli_query($conn, $sql)) {
        echo "Created temporary users table.<br>";
        
        // Copy data from old table to new table
        $sql = "INSERT INTO users_temp (id, name, email, password, course, created_at)
                SELECT id, name, email, password, course, created_at FROM users";
        
        if (mysqli_query($conn, $sql)) {
            echo "Copied user data to temporary table.<br>";
            
            // Drop old table
            if (mysqli_query($conn, "DROP TABLE users")) {
                echo "Dropped old users table.<br>";
                
                // Rename temp table to users
                if (mysqli_query($conn, "RENAME TABLE users_temp TO users")) {
                    echo "Renamed temporary table to users.<br>";
                    echo "Successfully fixed users table with role column!<br>";
                } else {
                    echo "Error renaming table: " . mysqli_error($conn) . "<br>";
                }
            } else {
                echo "Error dropping old table: " . mysqli_error($conn) . "<br>";
            }
        } else {
            echo "Error copying data: " . mysqli_error($conn) . "<br>";
        }
    } else {
        echo "Error creating temporary table: " . mysqli_error($conn) . "<br>";
    }
} else {
    echo "The 'role' column already exists in the users table. No fix needed.<br>";
}

// Now create the lecture tables if they don't exist
$lectureTables = [
    "live_lectures" => "CREATE TABLE IF NOT EXISTS live_lectures (
        id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        description TEXT,
        teacher_id INT(11) NOT NULL,
        group_id INT(11),
        scheduled_time DATETIME NOT NULL,
        duration INT(11) DEFAULT 60,
        is_active BOOLEAN DEFAULT 0,
        meeting_link VARCHAR(255),
        lecture_notes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (teacher_id) REFERENCES users(id),
        FOREIGN KEY (group_id) REFERENCES groups(id)
    )",
    
    "lecture_participants" => "CREATE TABLE IF NOT EXISTS lecture_participants (
        id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
        lecture_id INT(11) NOT NULL,
        user_id INT(11) NOT NULL,
        attendance_status ENUM('registered', 'attended', 'absent') DEFAULT 'registered',
        feedback TEXT,
        attendance_time TIMESTAMP NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY lecture_user (lecture_id, user_id),
        FOREIGN KEY (lecture_id) REFERENCES live_lectures(id),
        FOREIGN KEY (user_id) REFERENCES users(id)
    )"
];

foreach ($lectureTables as $table => $sql) {
    if (mysqli_query($conn, $sql)) {
        echo "Created/verified table: $table<br>";
    } else {
        echo "Error with table $table: " . mysqli_error($conn) . "<br>";
    }
}

// Create sample users if they don't exist
echo "<br>Checking/creating sample users...<br>";

// Sample user (admin)
$password_hash = password_hash('admin123', PASSWORD_DEFAULT);
$sql = "INSERT INTO users (name, email, password, course, role) 
        SELECT 'Admin User', 'admin@example.com', '$password_hash', 'premium', 'admin'
        WHERE NOT EXISTS (SELECT 1 FROM users WHERE email = 'admin@example.com')";
if (mysqli_query($conn, $sql)) {
    echo "Admin user exists or was created.<br>";
} else {
    echo "Error with admin user: " . mysqli_error($conn) . "<br>";
}

// Sample user (teacher)
$teacher_password = password_hash('teacher123', PASSWORD_DEFAULT);
$sql = "INSERT INTO users (name, email, password, course, role) 
        SELECT 'John Teacher', 'teacher@example.com', '$teacher_password', 'Mathematics', 'teacher'
        WHERE NOT EXISTS (SELECT 1 FROM users WHERE email = 'teacher@example.com')";
if (mysqli_query($conn, $sql)) {
    echo "Teacher user exists or was created.<br>";
} else {
    echo "Error with teacher user: " . mysqli_error($conn) . "<br>";
}

// Close database connection
mysqli_close($conn);

echo "<br>Database fix complete! <a href='index.php'>Go to homepage</a>";
?> 