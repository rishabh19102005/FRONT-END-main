<?php
// Include database connection
$conn = require_once 'config.php';

// Include auth functions
require_once 'auth.php';

// Set page title
$pageTitle = 'Web Development Bootcamp';

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
        $checkSql = "SELECT id FROM event_registrations WHERE email = ? AND event_id = 1";
        $checkStmt = mysqli_prepare($conn, $checkSql);
        mysqli_stmt_bind_param($checkStmt, "s", $email);
        mysqli_stmt_execute($checkStmt);
        mysqli_stmt_store_result($checkStmt);
        
        if (mysqli_stmt_num_rows($checkStmt) > 0) {
            $error = "You have already registered for this event";
        } else {
            // Insert registration
            $sql = "INSERT INTO event_registrations (name, email, experience_level, event_id) VALUES (?, ?, ?, 1)";
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
    <section class="bg-blue-600 text-white text-center py-16">
        <h1 class="text-4xl font-bold">Web Development Bootcamp 2025</h1>
        <p class="mt-4 text-lg">Join us for an immersive coding experience and take your web skills to the next level!</p>
        <a href="#register" class="mt-6 inline-block bg-white text-blue-600 px-6 py-3 rounded-lg font-semibold">Register Now</a>
    </section>
    
    <!-- Event Details -->
    <section class="container mx-auto p-8">
        <h2 class="text-3xl font-semibold text-center">Event Details</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-6">
            <div class="bg-white p-6 shadow rounded-lg text-center">
                <h3 class="text-xl font-semibold">📅 Date</h3>
                <p>April 20 - 22, 2025</p>
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
            <li>✅ Day 1: Introduction to Web Development</li>
            <li>✅ Day 2: HTML & CSS Essentials</li>
            <li>✅ Day 3: Responsive Web Design & UI Frameworks</li>
            <li>✅ Day 4: JavaScript Fundamentals</li>
            <li>✅ Day 5: Database Management with MongoDB</li>
            <li>✅ Day 6: Full-Stack Application Development</li>
            <li>✅ Day 7: Project Building & Deployment</li>
        </ul>
    </section>
    
    <!-- Speakers Section -->
    <section class="bg-white-200 py-10">
        <h2 class="text-3xl font-semibold text-center">Meet the Speakers</h2>
        <div class="flex flex-wrap justify-center gap-6 mt-6">
            <div class="bg-white p-4 rounded-lg shadow text-center w-60">
                <img src="khan-sir-1.webp" class="mx-auto rounded-full w-32 h-32 object-cover">
                <h3 class="mt-2 font-bold">Khan Sir</h3>
                <p class="text-sm">Public Speaker</p>
            </div>
            <div class="bg-white p-4 rounded-lg shadow text-center w-60">
                <img src="https://outstandingspeakersbureau.in/wp-content/uploads/2023/10/AnandKumar-1.png" class="mx-auto rounded-full w-32 h-32 object-cover">
                <h3 class="mt-2 font-bold">Anand Kumar</h3>
                <p class="text-sm">Full Stack Developer</p>
            </div>
        </div>
    </section>
    
    <!-- Registration Form -->
    <section id="register" class="container mx-auto p-8 bg-gray-200">
        <h2 class="text-3xl font-semibold text-center">Register Now</h2>
        
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
            
            <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded mt-4 w-full">
                Register
            </button>
        </form>
    </section>
    
    <!-- Footer -->
    <footer class="bg-blue-600 text-white text-center py-4 mt-10">
        <p>&copy; 2025 Web Dev Bootcamp | All Rights Reserved</p>
    </footer>
</body>

<?php
// Include footer
include 'includes/footer.php';

// Close database connection
mysqli_close($conn);
?> 