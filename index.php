<?php
// Include database connection
$conn = require_once 'config.php';

// Include auth functions
require_once 'auth.php';

// Set page title
$pageTitle = 'Study Groups - Online Discussion Platform';

// Get upcoming events from database
$events = [];
$sql = "SELECT id, title, event_date FROM events WHERE event_date > NOW() ORDER BY event_date LIMIT 2";
$result = mysqli_query($conn, $sql);
if ($result && mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        $events[] = $row;
    }
}

// Include header
include 'includes/header.php';
?>

    <!-- Hero Section -->
    <header class="bg-blue-500 text-white text-center py-16">
        <h1 class="text-4xl font-bold">Join the Best Online Study Groups</h1>
        <p class="mt-2 text-lg">Collaborate, discuss, and learn with students worldwide.</p>
        <a href="register.php" class="mt-4 inline-block bg-white text-blue-600 font-semibold py-2 px-6 rounded-lg shadow-lg hover:bg-gray-200">Get Started</a>
    </header>

    <!-- Features Section -->
    <section class="container mx-auto my-12 text-center">
        <h2 class="text-3xl font-bold text-gray-800">Why Join Us?</h2>
        <div class="flex flex-wrap justify-center mt-6 gap-6">
            <div class="bg-white p-6 rounded-lg shadow-md w-64 ">
                <h3 class="text-xl font-bold">Live Discussions</h3>
                <p class="mt-2 text-gray-600">Engage in real-time conversations with peers.</p>
            </div>
            <div class="bg-white p-6 rounded-lg shadow-md w-64 ">
                <h3 class="text-xl font-bold">Study Resources</h3>
                <p class="mt-2 text-gray-600">Access free study materials and notes.</p>
            </div>
            <div class="bg-white p-6 rounded-lg shadow-md w-64">
                <h3 class="text-xl font-bold">Expert Mentors</h3>
                <p class="mt-2 text-gray-600">Learn from industry professionals and educators.</p>
            </div>
            <div class="bg-white p-6 rounded-lg shadow-md w-64">
                <h3 class="text-xl font-bold">Mock Tests</h3>
                <p class="mt-2 text-gray-600">Test your knowledge with quizzes and mock exams.</p>
            </div>
        </div>
    </section>

    <!-- Upcoming Events -->
    <section class="bg-gray-200 py-12 text-center">
        <h2 class="text-3xl font-bold text-gray-800">Upcoming Events</h2>
        <div class="mt-6 flex justify-center gap-6">
            <?php if (count($events) > 0): ?>
                <?php foreach ($events as $event): ?>
                    <div class="bg-white p-4 rounded-lg shadow-md w-72">
                        <h3 class="text-lg font-semibold"><?php echo htmlspecialchars($event['title']); ?></h3>
                        <p class="text-gray-600"><?php echo date('F j, Y | g A', strtotime($event['event_date'])); ?></p>
                        <a href="event.php?id=<?php echo $event['id']; ?>" class="text-blue-600 font-semibold">Join Now</a>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="bg-white p-4 rounded-lg shadow-md w-72">
                    <h3 class="text-lg font-semibold">Web Development Bootcamp</h3>
                    <p class="text-gray-600">May 2, 2025 | 10 AM</p>
                    <div class="mt-2 flex justify-between">
                        <a href="web_bootcamp.php" class="bg-blue-600 text-white px-3 py-1 rounded hover:bg-blue-700">Join Now</a>
                        <a href="web_bootcamp.php#register" class="bg-gray-200 text-gray-800 px-3 py-1 rounded hover:bg-gray-300">Register</a>
                    </div>
                </div>
                <div class="bg-white p-4 rounded-lg shadow-md w-72">
                    <h3 class="text-lg font-semibold">AI & ML Workshop</h3>
                    <p class="text-gray-600">May 15, 2025 | 6 PM</p>
                    <div class="mt-2 flex justify-between">
                        <a href="ai_ml_workshop.php" class="bg-purple-600 text-white px-3 py-1 rounded hover:bg-purple-700">Join Now</a>
                        <a href="ai_ml_workshop.php#register" class="bg-gray-200 text-gray-800 px-3 py-1 rounded hover:bg-gray-300">Register</a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Testimonials -->
    <section class="container mx-auto my-12 text-center">
        <h2 class="text-3xl font-bold text-gray-800">What Our Users Say</h2>
        <div class="mt-6 flex justify-center gap-6">
            <div class="bg-white p-6 rounded-lg shadow-md w-64">
                <p class="text-gray-600">"This platform helped me ace my exams!"</p>
                <h3 class="mt-2 font-semibold">- Riya Thakur</h3>
            </div>
            <div class="bg-white p-6 rounded-lg shadow-md w-64">
                <p class="text-gray-600">"Best place to discuss complex problems!"</p>
                <h3 class="mt-2 font-semibold">- Shivam Kumar</h3>
            </div>
        </div>
    </section>

    <!-- FAQ Section -->
    <section class="bg-gray-200 py-12 text-center">
        <h2 class="text-3xl font-bold text-gray-800">Frequently Asked Questions</h2>
        <div class="mt-6 space-y-4">
            <div class="bg-white p-4 rounded-lg shadow-md w-3/4 mx-auto">
                <h3 class="text-lg font-semibold">How do I join a study group?</h3>
                <p class="text-gray-600">Sign up and browse available groups to join discussions.</p>
            </div>
            <div class="bg-white p-4 rounded-lg shadow-md w-3/4 mx-auto">
                <h3 class="text-lg font-semibold">Is it free to use?</h3>
                <p class="text-gray-600">Yes! You can join and participate for free.</p>
            </div>
        </div>
    </section>

    <!-- Newsletter Subscription -->
    <section class="container mx-auto my-12 text-center">
        <h2 class="text-3xl font-bold text-gray-800">Stay Updated</h2>
        <p class="mt-2 text-gray-600">Subscribe to our newsletter for the latest updates.</p>
        <form class="mt-4 flex justify-center" method="POST" action="subscribe.php">
            <input type="email" name="email" placeholder="Enter your email" class="p-2 rounded-l-lg border border-gray-300" required>
            <button type="submit" class="bg-blue-600 text-white p-2 rounded-r-lg">Subscribe</button>
        </form>
    </section>

<?php
// Include footer
include 'includes/footer.php';

// Close database connection
mysqli_close($conn);
?> 