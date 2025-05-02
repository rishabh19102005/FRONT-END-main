<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Function to check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Function to require login to access a page
function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: login.php");
        exit();
    }
}

// Function to check if the current user is a teacher
function isTeacher() {
    return isLoggedIn() && isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'teacher';
}

// Function to require teacher role to access a page
function requireTeacher() {
    if (!isTeacher()) {
        header("Location: index.php");
        exit();
    }
}

// Function to get current user data
function getCurrentUser($conn) {
    if (!isLoggedIn()) {
        return null;
    }
    
    $userId = $_SESSION['user_id'];
    
    // Check if role column exists
    $checkRoleColumn = mysqli_query($conn, "SHOW COLUMNS FROM users LIKE 'role'");
    $roleExists = mysqli_num_rows($checkRoleColumn) > 0;
    
    if ($roleExists) {
        $sql = "SELECT id, name, email, course, role FROM users WHERE id = ?";
    } else {
        $sql = "SELECT id, name, email, course FROM users WHERE id = ?";
    }
    
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $userId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($row = mysqli_fetch_assoc($result)) {
        // Add default role if it doesn't exist
        if (!$roleExists && !isset($row['role'])) {
            $row['role'] = 'student';
        }
        return $row;
    }
    
    return null;
}

// Function to register a new user
function registerUser($conn, $name, $email, $password, $course, $role = 'student') {
    // Hash the password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    // Check if role column exists
    $checkRoleColumn = mysqli_query($conn, "SHOW COLUMNS FROM users LIKE 'role'");
    $roleExists = mysqli_num_rows($checkRoleColumn) > 0;
    
    // Insert user
    if ($roleExists) {
        $sql = "INSERT INTO users (name, email, password, course, role) VALUES (?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "sssss", $name, $email, $hashedPassword, $course, $role);
    } else {
        $sql = "INSERT INTO users (name, email, password, course) VALUES (?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "ssss", $name, $email, $hashedPassword, $course);
    }
    
    if (mysqli_stmt_execute($stmt)) {
        return mysqli_insert_id($conn);
    }
    
    return false;
}

// Function to login a user
function loginUser($conn, $email, $password) {
    // Check if role column exists
    $checkRoleColumn = mysqli_query($conn, "SHOW COLUMNS FROM users LIKE 'role'");
    $roleExists = mysqli_num_rows($checkRoleColumn) > 0;
    
    if ($roleExists) {
        $sql = "SELECT id, name, email, password, role FROM users WHERE email = ?";
    } else {
        $sql = "SELECT id, name, email, password FROM users WHERE email = ?";
    }
    
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($user = mysqli_fetch_assoc($result)) {
        if (password_verify($password, $user['password'])) {
            // Password is correct, start session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            
            // Set default role if column doesn't exist
            if ($roleExists) {
                $_SESSION['user_role'] = $user['role'];
            } else {
                $_SESSION['user_role'] = 'student';
            }
            
            return true;
        }
    }
    
    return false;
}

// Function to make a user a teacher
function makeTeacher($conn, $userId) {
    // Check if role column exists
    $checkRoleColumn = mysqli_query($conn, "SHOW COLUMNS FROM users LIKE 'role'");
    $roleExists = mysqli_num_rows($checkRoleColumn) > 0;
    
    if ($roleExists) {
        $sql = "UPDATE users SET role = 'teacher' WHERE id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $userId);
        return mysqli_stmt_execute($stmt);
    }
    
    // If role column doesn't exist, just return true as we can't update it
    return true;
}

// Function to logout a user
function logoutUser() {
    // Unset all session variables
    $_SESSION = array();
    
    // Destroy the session
    session_destroy();
}
?> 