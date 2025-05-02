<?php
// Include database connection
$conn = require_once 'config.php';

// Include auth functions
require_once 'auth.php';

// Require user to be logged in
requireLogin();

// Set page title
$pageTitle = 'Payment - Study Groups';

// Get course from query string
$course = $_GET['course'] ?? 'basic';
$coursePrice = ($course === 'premium') ? 25 : 10;
$courseName = ($course === 'premium') ? 'Premium Plan' : 'Basic Plan';

// Process payment form
$success = false;
$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // In a real application, you would integrate with a payment gateway here
    // For now, we'll just simulate a successful payment
    
    // Update user's course in the database
    $userId = $_SESSION['user_id'];
    $sql = "UPDATE users SET course = ? WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "si", $course, $userId);
    
    if (mysqli_stmt_execute($stmt)) {
        $success = true;
    } else {
        $error = "Failed to process payment. Please try again.";
    }
}

// Include header with custom styles
include 'includes/header.php';
?>

<div class="container mx-auto py-8 px-4">
    <div class="max-w-lg mx-auto bg-white rounded-lg shadow-md overflow-hidden">
        <div class="bg-blue-600 px-6 py-4 text-white">
            <h2 class="text-2xl font-bold">Payment Details</h2>
            <p>Complete your subscription to <?php echo htmlspecialchars($courseName); ?></p>
        </div>
        
        <?php if ($success): ?>
            <div class="p-6">
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                    Payment successful! Your account has been upgraded to <?php echo htmlspecialchars($courseName); ?>.
                </div>
                <a href="index.php" class="bg-blue-600 text-white py-2 px-4 rounded hover:bg-blue-700">Return to Homepage</a>
            </div>
        <?php else: ?>
            <?php if (!empty($error)): ?>
                <div class="px-6 pt-4">
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                </div>
            <?php endif; ?>
            
            <div class="p-6">
                <div class="mb-6">
                    <p class="text-lg font-bold">Subscription Summary</p>
                    <div class="flex justify-between mt-2">
                        <span><?php echo htmlspecialchars($courseName); ?></span>
                        <span>$<?php echo number_format($coursePrice, 2); ?>/month</span>
                    </div>
                    <div class="border-t border-gray-200 my-4"></div>
                    <div class="flex justify-between font-bold">
                        <span>Total</span>
                        <span>$<?php echo number_format($coursePrice, 2); ?></span>
                    </div>
                </div>
                
                <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . '?course=' . urlencode($course)); ?>">
                    <div class="mb-4">
                        <label for="card_number" class="block text-gray-700 mb-2">Card Number</label>
                        <input type="text" id="card_number" name="card_number" placeholder="1234 5678 9012 3456" class="w-full px-4 py-2 border rounded-lg" required>
                    </div>
                    
                    <div class="flex mb-4 gap-4">
                        <div class="w-1/2">
                            <label for="exp_date" class="block text-gray-700 mb-2">Expiry Date</label>
                            <input type="text" id="exp_date" name="exp_date" placeholder="MM/YY" class="w-full px-4 py-2 border rounded-lg" required>
                        </div>
                        <div class="w-1/2">
                            <label for="cvv" class="block text-gray-700 mb-2">CVV</label>
                            <input type="text" id="cvv" name="cvv" placeholder="123" class="w-full px-4 py-2 border rounded-lg" required>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label for="name" class="block text-gray-700 mb-2">Name on Card</label>
                        <input type="text" id="name" name="name" placeholder="John Doe" class="w-full px-4 py-2 border rounded-lg" required>
                    </div>
                    
                    <button type="submit" class="w-full bg-blue-600 text-white py-2 rounded-lg hover:bg-blue-700 transition duration-300">
                        Pay $<?php echo number_format($coursePrice, 2); ?>
                    </button>
                </form>
                
                <div class="mt-4 text-center text-gray-600 text-sm">
                    <p>Your payment information is secured with industry-standard encryption.</p>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
// Include footer
include 'includes/footer.php';

// Close database connection
mysqli_close($conn);
?> 