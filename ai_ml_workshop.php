<?php
// Include database connection
$conn = require_once 'config.php';

// Include auth functions
require_once 'auth.php';

// Set page title
$pageTitle = 'AI & ML Workshop';

// Process registration form
$success = '';
$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $experience = $_POST['experience'] ?? '';
    
    // Basic validation
    if (empty($name) || empty($email) || empty($experience)) {
        $error = "All fields are required";
    } else {
        // Check if already registered
        $checkSql = "SELECT id FROM event_registrations WHERE email = ? AND event_id = 2";
        $checkStmt = mysqli_prepare($conn, $checkSql);
        mysqli_stmt_bind_param($checkStmt, "s", $email);
        mysqli_stmt_execute($checkStmt);
        mysqli_stmt_store_result($checkStmt);
        
        if (mysqli_stmt_num_rows($checkStmt) > 0) {
            $error = "You have already registered for this event";
        } else {
            // Insert registration
            $sql = "INSERT INTO event_registrations (name, email, experience_level, event_id) VALUES (?, ?, ?, 2)";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "sss", $name, $email, $experience);
            
            if (mysqli_stmt_execute($stmt)) {
                $success = "Registration successful! We'll send you details via email.";
            } else {
                $error = "Registration failed. Please try again.";
            }
        }
    }
}

// Include header
include 'includes/header.php';
?>

<body class="bg-gray-100">
    <!-- Hero Section -->
    <section class="bg-purple-600 text-white text-center py-16">
        <h1 class="text-4xl font-bold">AI & ML Workshop 2025</h1>
        <p class="mt-4 text-lg">Explore the future of Artificial Intelligence and Machine Learning with industry experts!</p>
        <a href="#register" class="mt-6 inline-block bg-white text-purple-600 px-6 py-3 rounded-lg font-semibold">Register Now</a>
    </section>
    
    <!-- Event Details -->
    <section class="container mx-auto p-8">
        <h2 class="text-3xl font-semibold text-center">Event Details</h2>
        <p class="text-center text-gray-600 mt-2">A deep dive into AI and ML concepts, hands-on sessions, and networking opportunities.</p>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-6">
            <div class="bg-white p-6 shadow rounded-lg text-center">
                <h3 class="text-xl font-semibold">📅 Date</h3>
                <p>May 15 - 17, 2025</p>
            </div>
            <div class="bg-white p-6 shadow rounded-lg text-center">
                <h3 class="text-xl font-semibold">📍 Location</h3>
                <p>Online (Zoom & Discord)</p>
            </div>
            <div class="bg-white p-6 shadow rounded-lg text-center">
                <h3 class="text-xl font-semibold">🎟️ Fee</h3>
                <p>Free for early registrants</p>
            </div>
        </div>
    </section>
    
    <!-- Agenda Section -->
    <section class="bg-gray-200 py-10">
        <h2 class="text-3xl font-semibold text-center">Workshop Agenda</h2>
        <ul class="max-w-2xl mx-auto mt-6 space-y-4 text-gray-700">
            <li>✅ Introduction to AI & ML</li>
            <li>✅ Deep Learning and Neural Networks</li>
            <li>✅ Hands-on Python ML Projects</li>
            <li>✅ Real-world AI Applications</li>
            <li>✅ Panel Discussion with Experts</li>
        </ul>
    </section>
    
    <!-- Speakers Section -->
    <section class="py-10">
        <h2 class="text-3xl font-semibold text-center">Meet the Speakers</h2>
        <div class="flex flex-wrap justify-center gap-6 mt-6">
            <div class="bg-white p-4 rounded-lg shadow text-center w-60">
                <div class="w-32 h-32 bg-gray-300 rounded-full mx-auto"></div>
                <h3 class="mt-2 font-bold">Mr. Satyam Singh</h3>
                <p class="text-sm">AI Research Scientist</p>
            </div>
            <div class="bg-white p-4 rounded-lg shadow text-center w-60">
                <div class="w-32 h-32 bg-gray-300 rounded-full mx-auto"></div>
                <h3 class="mt-2 font-bold">Rishabh Chaurasia</h3>
                <p class="text-sm">Machine Learning Engineer</p>
            </div>
        </div>
    </section>
    
    <!-- Registration Form -->
    <section id="register" class="container mx-auto p-8">
        <h2 class="text-3xl font-semibold text-center">Register Now</h2>
        <p class="text-center text-gray-600 mt-2">Secure your spot and get ready to explore AI & ML like never before!</p>
        
        <?php if (!empty($success)): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded max-w-lg mx-auto mt-6">
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php elseif (!empty($error)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded max-w-lg mx-auto mt-6">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>#register" class="bg-white p-6 shadow rounded-lg max-w-lg mx-auto mt-6">
            <label class="block font-semibold">Name</label>
            <input type="text" name="name" class="w-full p-2 border rounded mt-1" placeholder="Enter your name" required>
            
            <label class="block font-semibold mt-4">Email</label>
            <input type="email" name="email" class="w-full p-2 border rounded mt-1" placeholder="Enter your email" required>
            
            <label class="block font-semibold mt-4">Experience Level</label>
            <select name="experience" class="w-full p-2 border rounded mt-1" required>
                <option value="Beginner">Beginner</option>
                <option value="Intermediate">Intermediate</option>
                <option value="Advanced">Advanced</option>
            </select>
            
            <button type="submit" class="bg-purple-600 text-white px-4 py-2 rounded mt-4 w-full">
                Register
            </button>
        </form>
    </section>
    
    <!-- Footer -->
    <footer class="bg-purple-600 text-white text-center py-4 mt-10">
        <p>&copy; 2025 AI & ML Workshop | All Rights Reserved</p>
    </footer>
</body>

<?php
// Include footer
include 'includes/footer.php';

// Close database connection
mysqli_close($conn);
?> 