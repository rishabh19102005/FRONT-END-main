<?php
// Display header
echo "Study Groups Installation<br>";
echo "------------------------<br><br>";

// Step 1: Create config.php if it doesn't exist
if (!file_exists('config.php')) {
    // Create config file
    $config_content = '<?php
// Database configuration
$servername = "localhost";
$username = "root"; // Default XAMPP username
$password = ""; // Default XAMPP password
$dbname = "study_platform_db";

// Create connection
$conn = mysqli_connect($servername, $username, $password);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Create database if it doesn\'t exist
$sql = "CREATE DATABASE IF NOT EXISTS $dbname";
if (mysqli_query($conn, $sql)) {
    // Connect to the database
    mysqli_select_db($conn, $dbname);
} else {
    die("Error creating database: " . mysqli_error($conn));
}

// Keep the connection open
return $conn;
?>';

    file_put_contents('config.php', $config_content);
    echo "Created config.php file.<br>";
} else {
    echo "Config file already exists.<br>";
}

// Step 2: Include database connection
$conn = require_once 'config.php';
echo "Connected to database.<br>";

// Step 3: Create necessary tables
$tables = [
    "users" => "CREATE TABLE IF NOT EXISTS users (
        id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        email VARCHAR(255) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        course VARCHAR(50),
        role ENUM('student', 'teacher', 'admin') DEFAULT 'student',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",
    
    "groups" => "CREATE TABLE IF NOT EXISTS groups (
        id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        description TEXT,
        created_by INT(11),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (created_by) REFERENCES users(id)
    )",
    
    "group_members" => "CREATE TABLE IF NOT EXISTS group_members (
        id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
        group_id INT(11) NOT NULL,
        user_id INT(11) NOT NULL,
        joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY group_user (group_id, user_id),
        FOREIGN KEY (group_id) REFERENCES groups(id),
        FOREIGN KEY (user_id) REFERENCES users(id)
    )",
    
    "group_materials" => "CREATE TABLE IF NOT EXISTS group_materials (
        id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
        group_id INT(11) NOT NULL,
        user_id INT(11) NOT NULL,
        title VARCHAR(255) NOT NULL,
        description TEXT,
        file_path VARCHAR(255),
        file_type VARCHAR(50),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (group_id) REFERENCES groups(id),
        FOREIGN KEY (user_id) REFERENCES users(id)
    )",
    
    "study_sessions" => "CREATE TABLE IF NOT EXISTS study_sessions (
        id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
        group_id INT(11) NOT NULL,
        creator_id INT(11) NOT NULL,
        title VARCHAR(255) NOT NULL,
        description TEXT,
        session_date DATE NOT NULL,
        start_time TIME NOT NULL,
        end_time TIME NOT NULL,
        location VARCHAR(255),
        is_online BOOLEAN DEFAULT 1,
        meeting_link VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (group_id) REFERENCES groups(id),
        FOREIGN KEY (creator_id) REFERENCES users(id)
    )",
    
    "session_participants" => "CREATE TABLE IF NOT EXISTS session_participants (
        id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
        session_id INT(11) NOT NULL,
        user_id INT(11) NOT NULL,
        status ENUM('attending', 'maybe', 'declined') DEFAULT 'attending',
        joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY session_user (session_id, user_id),
        FOREIGN KEY (session_id) REFERENCES study_sessions(id),
        FOREIGN KEY (user_id) REFERENCES users(id)
    )",
    
    "discussions" => "CREATE TABLE IF NOT EXISTS discussions (
        id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        content TEXT,
        user_id INT(11),
        group_id INT(11),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id),
        FOREIGN KEY (group_id) REFERENCES groups(id)
    )",
    
    "resources" => "CREATE TABLE IF NOT EXISTS resources (
        id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        description TEXT,
        file_path VARCHAR(255),
        type VARCHAR(50),
        user_id INT(11),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id)
    )",
    
    "events" => "CREATE TABLE IF NOT EXISTS events (
        id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        description TEXT,
        event_date DATETIME,
        location VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",
    
    "subscribers" => "CREATE TABLE IF NOT EXISTS subscribers (
        id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(255) NOT NULL UNIQUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",
    
    "contact_messages" => "CREATE TABLE IF NOT EXISTS contact_messages (
        id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        email VARCHAR(255) NOT NULL,
        message TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",
    
    "chat_messages" => "CREATE TABLE IF NOT EXISTS chat_messages (
        id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
        group_id INT(11) NOT NULL,
        user_id INT(11) NOT NULL,
        message TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (group_id) REFERENCES groups(id),
        FOREIGN KEY (user_id) REFERENCES users(id)
    )",
    
    "resource_comments" => "CREATE TABLE IF NOT EXISTS resource_comments (
        id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
        resource_id INT(11) NOT NULL,
        user_id INT(11) NOT NULL,
        comment TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (resource_id) REFERENCES resources(id),
        FOREIGN KEY (user_id) REFERENCES users(id)
    )",
    
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

// Create tables
foreach ($tables as $table => $sql) {
    if (mysqli_query($conn, $sql)) {
        echo "Created table: $table<br>";
    } else {
        echo "Error creating table $table: " . mysqli_error($conn) . "<br>";
    }
}

// Step 4: Create sample data
echo "<br>Creating sample data...<br>";

// Sample user (admin)
$password_hash = password_hash('admin123', PASSWORD_DEFAULT);
$sql = "INSERT INTO users (name, email, password, course, role) 
        SELECT 'Admin User', 'admin@example.com', '$password_hash', 'premium', 'admin'
        WHERE NOT EXISTS (SELECT 1 FROM users WHERE email = 'admin@example.com')";
if (mysqli_query($conn, $sql)) {
    echo "Created admin user.<br>";
}

// Sample user (teacher)
$teacher_password = password_hash('teacher123', PASSWORD_DEFAULT);
$sql = "INSERT INTO users (name, email, password, course, role) 
        SELECT 'John Teacher', 'teacher@example.com', '$teacher_password', 'Mathematics', 'teacher'
        WHERE NOT EXISTS (SELECT 1 FROM users WHERE email = 'teacher@example.com')";
if (mysqli_query($conn, $sql)) {
    echo "Created teacher user.<br>";
}

// Get admin user id
$admin_id = 0;
$result = mysqli_query($conn, "SELECT id FROM users WHERE email = 'admin@example.com'");
if ($row = mysqli_fetch_assoc($result)) {
    $admin_id = $row['id'];
}

// Get teacher user id
$teacher_id = 0;
$result = mysqli_query($conn, "SELECT id FROM users WHERE email = 'teacher@example.com'");
if ($row = mysqli_fetch_assoc($result)) {
    $teacher_id = $row['id'];
}

// Sample groups
$groups = [
    ["Mathematics Study Group", "Focus on algebra, calculus, and statistics", $admin_id],
    ["Computer Science Group", "Programming, data structures, and algorithms", $admin_id],
    ["Physics Discussion", "Mechanics, thermodynamics, and quantum physics", $admin_id],
    ["Literature Circle", "Book discussions, poetry, and creative writing", $admin_id]
];

foreach ($groups as $group) {
    $sql = "INSERT INTO groups (name, description, created_by) 
            SELECT ?, ?, ?
            WHERE NOT EXISTS (SELECT 1 FROM groups WHERE name = ?)";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ssis", $group[0], $group[1], $group[2], $group[0]);
    mysqli_stmt_execute($stmt);
}
echo "Created sample groups.<br>";

// Add admin user to all groups
for ($i = 1; $i <= 4; $i++) {
    $sql = "INSERT IGNORE INTO group_members (group_id, user_id) VALUES (?, ?)";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ii", $i, $admin_id);
    mysqli_stmt_execute($stmt);
}
echo "Added admin user to all groups.<br>";

// Sample events
$events = [
    ["Web Development Bootcamp", "Learn modern web development techniques", "2025-04-20 17:00:00", "Online"],
    ["AI & ML Workshop", "Introduction to machine learning algorithms", "2025-04-22 18:00:00", "Online"]
];

foreach ($events as $event) {
    $sql = "INSERT INTO events (title, description, event_date, location) 
            SELECT ?, ?, ?, ?
            WHERE NOT EXISTS (SELECT 1 FROM events WHERE title = ?)";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "sssss", $event[0], $event[1], $event[2], $event[3], $event[0]);
    mysqli_stmt_execute($stmt);
}
echo "Created sample events.<br>";

// Create uploads directory
$uploadsDir = __DIR__ . '/uploads';
if (!file_exists($uploadsDir)) {
    mkdir($uploadsDir, 0755, true);
    echo "Created uploads directory.<br>";
} else {
    echo "Uploads directory already exists.<br>";
}

// Make sure the directory is writable
if (is_writable($uploadsDir)) {
    echo "Uploads directory is writable.<br>";
} else {
    chmod($uploadsDir, 0755);
    echo "Set permissions on uploads directory.<br>";
}

// Close database connection
mysqli_close($conn);

echo "<br>Installation complete! <a href='index.php'>Go to homepage</a>";
?> 