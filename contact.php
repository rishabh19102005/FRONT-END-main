<?php
// Include database connection
$conn = require_once 'config.php';

// Include auth functions
require_once 'auth.php';

// Set page title
$pageTitle = 'Contact - Study Groups';

// Process form submission
$error = '';
$success = false;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $message = $_POST['message'] ?? '';
    
    // Basic validation
    if (empty($name) || empty($email) || empty($message)) {
        $error = "All fields are required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format";
    } else {
        // Insert message into database
        $sql = "INSERT INTO contact_messages (name, email, message) VALUES (?, ?, ?)";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "sss", $name, $email, $message);
        
        if (mysqli_stmt_execute($stmt)) {
            $success = true;
        } else {
            $error = "Failed to send message. Please try again.";
        }
    }
}

// Include header
include 'includes/header.php';
?>

<!-- Main Content -->
<div class="container mx-auto mt-8 p-6 bg-white shadow-lg rounded-lg">
    <h1 class="text-3xl font-bold text-center text-blue-600 mb-6">Contact Us</h1>
    
    <?php if ($success): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
            Thank you for your message! We will get back to you as soon as possible.
        </div>
    <?php else: ?>
        <?php if (!empty($error)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <div>
                <h2 class="text-xl font-semibold text-gray-800 mb-4">Get in Touch</h2>
                <p class="text-gray-700 mb-4">We'd love to hear from you! Fill out the form and we'll get back to you as soon as possible.</p>
                
                <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                    <div class="mb-4">
                        <label for="name" class="block text-gray-700 mb-1">Name</label>
                        <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($name ?? ''); ?>" class="w-full px-4 py-2 border rounded-lg" required>
                    </div>
                    
                    <div class="mb-4">
                        <label for="email" class="block text-gray-700 mb-1">Email</label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email ?? ''); ?>" class="w-full px-4 py-2 border rounded-lg" required>
                    </div>
                    
                    <div class="mb-4">
                        <label for="message" class="block text-gray-700 mb-1">Message</label>
                        <textarea id="message" name="message" rows="4" class="w-full px-4 py-2 border rounded-lg" required><?php echo htmlspecialchars($message ?? ''); ?></textarea>
                    </div>
                    
                    <button type="submit" class="bg-blue-600 text-white py-2 px-6 rounded-lg hover:bg-blue-700">Send Message</button>
                </form>
            </div>
            
            <div>
                <h2 class="text-xl font-semibold text-gray-800 mb-4">Contact Information</h2>
                
                <div class="space-y-4">
                    <div>
                        <h3 class="font-semibold text-gray-700">Address</h3>
                        <p class="text-gray-600">Lovely Professional University, Phagwara(144411), Punjab, India</p>
                    </div>
                    
                    <div>
                        <h3 class="font-semibold text-gray-700">Email</h3>
                        <p class="text-gray-600">support@studygroups.com</p>
                    </div>
                    
                    <div>
                        <h3 class="font-semibold text-gray-700">Phone</h3>
                        <p class="text-gray-600">+91 9876543210</p>
                    </div>
                    
                    <div>
                        <h3 class="font-semibold text-gray-700">Hours</h3>
                        <p class="text-gray-600">Monday - Friday: 9:00 AM - 5:00 PM</p>
                        <p class="text-gray-600">Saturday: 10:00 AM - 2:00 PM</p>
                        <p class="text-gray-600">Sunday: Closed</p>
                    </div>
                </div>
                
                <div class="mt-6">
                    <h3 class="font-semibold text-gray-700 mb-2">Follow Us</h3>
                    <div class="flex space-x-4">
                        <a href="#" class="text-blue-600 hover:text-blue-800">Facebook</a>
                        <a href="#" class="text-blue-400 hover:text-blue-600">Twitter</a>
                        <a href="#" class="text-pink-600 hover:text-pink-800">Instagram</a>
                        <a href="#" class="text-blue-800 hover:text-blue-900">LinkedIn</a>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php
// Include footer
include 'includes/footer.php';

// Close database connection
mysqli_close($conn);
?> 