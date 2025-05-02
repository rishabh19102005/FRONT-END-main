<?php
// Include database connection
$conn = require_once 'config.php';

// Include auth functions
require_once 'auth.php';

// Set page title
$pageTitle = 'Register - Study Groups';

// Check if user is already logged in
if (isLoggedIn()) {
    header("Location: index.php");
    exit();
}

// Initialize error and success messages
$error = '';
$success = '';

// Process registration form
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $course = $_POST['course'] ?? 'free';
    
    // Basic validation
    if (empty($name) || empty($email) || empty($password)) {
        $error = "All fields are required";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters long";
    } else {
        // Check if email already exists
        $sql = "SELECT id FROM users WHERE email = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);
        
        if (mysqli_stmt_num_rows($stmt) > 0) {
            $error = "Email already in use. Please use a different email or login.";
        } else {
            // Register the user
            $userId = registerUser($conn, $name, $email, $password, $course);
            
            if ($userId) {
                // Automatically log in the user
                $_SESSION['user_id'] = $userId;
                $_SESSION['user_name'] = $name;
                $_SESSION['user_email'] = $email;
                
                // Check if course is paid
                if ($course !== 'free') {
                    // Redirect to payment page
                    header("Location: payment.php?course=" . urlencode($course));
                    exit();
                } else {
                    // Redirect to homepage
                    header("Location: index.php");
                    exit();
                }
            } else {
                $error = "Registration failed. Please try again.";
            }
        }
    }
}

// Custom styles
$extraStyles = '
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
';

// Include auth header
include 'includes/auth_header.php';
?>

<div class="bg-white p-8 rounded-xl shadow-2xl w-full max-w-md">
    <h2 class="text-3xl font-bold text-center text-gray-800">Join Study Groups</h2>
    <p class="text-gray-500 text-center mb-6">Create an account to collaborate and learn!</p>
    
    <?php if (!empty($error)): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($success)): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            <?php echo htmlspecialchars($success); ?>
        </div>
    <?php endif; ?>
    
    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST">
        <div class="mb-4 relative">
            <label for="name" class="block text-gray-600 font-medium">Name</label>
            <input type="text" id="name" name="name" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-400 focus:outline-none" required>
            <i class="fas fa-user absolute right-3 top-10 text-gray-400"></i>
        </div>
        <div class="mb-4 relative">
            <label for="email" class="block text-gray-600 font-medium">Email</label>
            <input type="email" id="email" name="email" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-400 focus:outline-none" required>
            <i class="fas fa-envelope absolute right-3 top-10 text-gray-400"></i>
        </div>
        <div class="mb-4 relative">
            <label for="password" class="block text-gray-600 font-medium">Password</label>
            <input type="password" id="password" name="password" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-400 focus:outline-none" required>
            <i class="fas fa-lock absolute right-3 top-10 text-gray-400"></i>
        </div>
        <div class="mb-4">
            <label for="course" class="block text-gray-600 font-medium">Select Course</label>
            <select id="course" name="course" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-400 focus:outline-none">
                <option value="free">Free Course</option>
                <option value="basic">Basic Plan - $10/month</option>
                <option value="premium">Premium Plan - $25/month</option>
            </select>
        </div>
        <div class="mb-4">
            <label class="inline-flex items-center">
                <input type="checkbox" class="form-checkbox text-blue-500" required>
                <span class="ml-2 text-gray-600">I agree to the <a href="#" class="text-blue-500 hover:underline">Terms & Conditions</a></span>
            </label>
        </div>
        <button type="submit" class="w-full bg-blue-500 text-white py-2 rounded-lg hover:bg-blue-600 transition duration-300">Register</button>
    </form>
    <p class="text-center text-gray-600 mt-4">Already have an account? <a href="login.php" class="text-blue-500 hover:underline">Login</a></p>
</div>

<?php
// Include auth footer
include 'includes/auth_footer.php';

// Close database connection
mysqli_close($conn);
?> 