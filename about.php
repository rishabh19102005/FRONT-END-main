<?php
// Include database connection
$conn = require_once 'config.php';

// Include auth functions
require_once 'auth.php';

// Set page title
$pageTitle = 'About - Study Groups';

// Get team members from database (if we had a team table)
// For now, we'll use the static data

// Include header
include 'includes/header.php';
?>

<!-- Main Content -->
<div class="container mx-auto mt-8 p-6 bg-white shadow-lg rounded-lg">
    <h1 class="text-3xl font-bold text-center text-blue-600">About Us</h1>
    <p class="text-center mt-4 text-gray-600">Learn more about our mission and how we connect students worldwide.</p>

    <div class="mt-6">
        <h2 class="text-xl font-semibold text-gray-800">Our Mission</h2>
        <p class="text-gray-700 mt-2">We aim to create a collaborative learning environment where students can discuss, share resources, and grow together.</p>
    </div>

    <div class="mt-6">
        <h2 class="text-xl font-semibold text-gray-800">How It Works</h2>
        <ul class="list-disc ml-6 text-gray-700">
            <li>Join a study group based on your interests.</li>
            <li>Participate in discussions and ask questions.</li>
            <li>Access shared resources to enhance your learning.</li>
        </ul>
    </div>

    <div class="mt-6">
        <h2 class="text-xl font-semibold text-gray-800">What Our Users Say</h2>
        <p class="italic text-gray-600 mt-2">"This platform helped me connect with peers and improve my understanding of complex topics!" - Student</p>
    </div>

    <div class="mt-6 text-center">
        <h2 class="text-xl font-semibold text-gray-800">Join Us Today!</h2>
        <p class="text-gray-700 mt-2">Start your learning journey with us.</p>
        <a href="register.php" class="mt-4 inline-block bg-blue-600 text-white py-2 px-4 rounded hover:bg-blue-700">Sign Up</a>
    </div>

    <!-- Meet Our Team Section -->
    <div class="mt-8">
        <h2 class="text-xl font-semibold text-gray-800">Meet Our Team</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-4">
            <div class="text-center p-4 bg-gray-100 rounded-lg shadow">
                <img src="InShot_20231029_185717222-min.jpg" alt="Team Member" class="mx-auto rounded-full">
                <h3 class="font-semibold mt-2">Satyam Singh</h3>
                <p class="text-gray-600">Co-Founder & CEO</p>
            </div>
            <div class="text-center p-4 bg-gray-100 rounded-lg shadow">
                <img src="WhatsApp_Image_2023-10-25_at_19.30.57_3837d124-removebg-preview-removebg-preview.jpg" alt="Team Member" class="mx-auto rounded-full">
                <h3 class="font-semibold mt-2">Rishabh Chaurasia</h3>
                <p class="text-gray-600">Head of Development</p>
            </div>
            <div class="text-center p-4 bg-gray-100 rounded-lg shadow">
                <img src="https://via.placeholder.com/100" alt="Team Member" class="mx-auto rounded-full">
                <h3 class="font-semibold mt-2">Harsh pachauri</h3>
                <p class="text-gray-600">Community Manager</p>
            </div>
        </div>
    </div>

    <!-- Contact Us Section -->
    <div class="mt-8">
        <h2 class="text-xl font-semibold text-gray-800">Contact Us</h2>
        <p class="text-gray-700 mt-2">Have questions? Reach out to us!</p>
        <form class="mt-4" method="POST" action="send_contact.php">
            <input type="text" name="name" placeholder="Your Name" class="w-full p-2 border rounded mb-2" required>
            <input type="email" name="email" placeholder="Your Email" class="w-full p-2 border rounded mb-2" required>
            <textarea name="message" placeholder="Your Message" class="w-full p-2 border rounded mb-2" required></textarea>
            <button type="submit" class="bg-blue-600 text-white py-2 px-4 rounded hover:bg-blue-700">Send</button>
        </form>
    </div>
</div>

<?php
// Include footer
include 'includes/footer.php';

// Close database connection
mysqli_close($conn);
?> 