<?php
// Include database connection
$conn = require_once 'config.php';

// Create subscribers table if not exists
$sql = "CREATE TABLE IF NOT EXISTS subscribers (
    id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
mysqli_query($conn, $sql);

// Initialize result variables
$success = false;
$error = '';

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'] ?? '';
    
    // Basic validation
    if (empty($email)) {
        $error = "Email is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format";
    } else {
        // Check if email already exists
        $sql = "SELECT id FROM subscribers WHERE email = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);
        
        if (mysqli_stmt_num_rows($stmt) > 0) {
            $error = "You are already subscribed to our newsletter";
        } else {
            // Insert subscription
            $sql = "INSERT INTO subscribers (email) VALUES (?)";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "s", $email);
            
            if (mysqli_stmt_execute($stmt)) {
                $success = true;
            } else {
                $error = "Failed to subscribe. Please try again.";
            }
        }
    }
}

// Set page title
$pageTitle = 'Subscribe - Study Groups';

// Include header
include 'includes/header.php';
?>

<div class="container mx-auto py-10 px-4">
    <div class="max-w-md mx-auto bg-white rounded-lg shadow-md overflow-hidden">
        <div class="bg-blue-600 px-6 py-4 text-white">
            <h2 class="text-2xl font-bold">Newsletter Subscription</h2>
        </div>
        
        <div class="p-6">
            <?php if ($success): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                    Thank you for subscribing to our newsletter! You will receive updates on the latest study resources and events.
                </div>
                <a href="index.php" class="bg-blue-600 text-white py-2 px-4 rounded hover:bg-blue-700">Return to Homepage</a>
            <?php else: ?>
                <?php if (!empty($error)): ?>
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>
                
                <p class="text-gray-600 mb-4">Subscribe to our newsletter for the latest updates on study resources, events, and tips.</p>
                
                <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                    <div class="mb-4">
                        <label for="email" class="block text-gray-700 mb-2">Email Address</label>
                        <input type="email" id="email" name="email" placeholder="your@email.com" class="w-full px-4 py-2 border rounded-lg" required>
                    </div>
                    
                    <button type="submit" class="w-full bg-blue-600 text-white py-2 rounded-lg hover:bg-blue-700 transition duration-300">
                        Subscribe
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
// Include footer
include 'includes/footer.php';

// Close database connection
mysqli_close($conn);
?> 