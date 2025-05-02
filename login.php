<?php
// Include database connection
$conn = require_once 'config.php';

// Include auth functions
require_once 'auth.php';

// Set page title
$pageTitle = 'Login - Study Groups';

// Check if user is already logged in
if (isLoggedIn()) {
    header("Location: index.php");
    exit();
}

// Initialize error message
$error = '';

// Process login form
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    // Attempt to login
    if (loginUser($conn, $email, $password)) {
        // Redirect to home page
        header("Location: index.php");
        exit();
    } else {
        $error = "Invalid email or password. Please try again.";
    }
}

// Custom styles
$extraStyles = '
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
';

// Include auth header
include 'includes/auth_header.php';
?>

<!-- Login Card -->
<div class="bg-white p-8 rounded-2xl shadow-2xl w-96 max-w-[90%]">
    <!-- <h1 class="text-4xl font-bold text-center text-blue-600 mb-2">StudyGroups</h1> -->
    <h2 class="text-3xl font-bold text-center text-gray-800 mb-4">Welcome Back</h2>
    <p class="text-center text-gray-600 mb-6">Login to join the discussion</p>
    
    <?php if (!empty($error)): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <form id="login-form" class="space-y-4" method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
        <div class="relative">
            <i class="fas fa-envelope absolute left-3 top-3 text-gray-500"></i>
            <input type="email" id="email" name="email" placeholder="Email" class="pl-10 w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-400 focus:outline-none" required>
        </div>
        <div class="relative">
            <i class="fas fa-lock absolute left-3 top-3 text-gray-500"></i>
            <input type="password" id="password" name="password" placeholder="Password" class="pl-10 w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-400 focus:outline-none" required>
            <i id="toggle-password" class="fas fa-eye absolute right-3 top-3 cursor-pointer text-gray-500"></i>
        </div>
        <div class="flex justify-between text-sm text-gray-600">
            <label class="flex items-center space-x-2">
                <input type="checkbox" name="remember" class="accent-blue-500">
                <span>Remember Me</span>
            </label>
            <a href="forgot_password.php" class="text-blue-500 hover:underline">Forgot Password?</a>
        </div>
        <button type="submit" class="w-full bg-blue-600 text-white py-2 rounded-lg hover:bg-blue-700 transition duration-300">Login</button>
    </form>

    <div class="text-center mt-6 text-gray-600">
        <p>Don't have an account? <a href="register.php" class="text-blue-500 hover:underline">Sign up</a></p>
    </div>

    <div class="mt-4 flex justify-center space-x-4">
        <button class="bg-gray-100 p-3 rounded-full hover:bg-gray-200 transition">
            <i class="fa-brands fa-google text-red-500 text-2xl"></i>
        </button>
        <button class="bg-gray-100 p-3 rounded-full hover:bg-gray-200 transition">
            <i class="fa-brands fa-facebook text-blue-600 text-2xl"></i>
        </button>
        <button class="bg-gray-100 p-3 rounded-full hover:bg-gray-200 transition">
            <i class="fa-brands fa-twitter text-blue-400 text-2xl"></i>
        </button>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const togglePassword = document.getElementById('toggle-password');
    const passwordInput = document.getElementById('password');
    
    if (togglePassword && passwordInput) {
        togglePassword.addEventListener('click', function() {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            this.classList.toggle('fa-eye');
            this.classList.toggle('fa-eye-slash');
        });
    }
});
</script>

<?php
// Include auth footer
include 'includes/auth_footer.php';

// Close database connection
mysqli_close($conn);
?> 