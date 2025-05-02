<?php
// Include database connection
$conn = require_once 'config.php';

// Include auth functions
require_once 'auth.php';

// Set page title
$pageTitle = 'Discussions - Study Groups';

// Get discussions from database
$discussions = [];
$sql = "SELECT d.id, d.title, d.content, d.created_at, u.name as user_name, g.name as group_name 
        FROM discussions d 
        JOIN users u ON d.user_id = u.id 
        JOIN groups g ON d.group_id = g.id 
        ORDER BY d.created_at DESC 
        LIMIT 10";
$result = mysqli_query($conn, $sql);
if ($result && mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        $discussions[] = $row;
    }
}

// Get groups for the dropdown
$groups = [];
$sql = "SELECT id, name FROM groups ORDER BY name";
$result = mysqli_query($conn, $sql);
if ($result && mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        $groups[] = $row;
    }
}

// Process form submission for creating a new discussion
$error = '';
$success = false;

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] === 'create_discussion') {
    // Check if user is logged in
    if (!isLoggedIn()) {
        header("Location: login.php");
        exit();
    }
    
    // Get form data
    $title = $_POST['title'] ?? '';
    $content = $_POST['content'] ?? '';
    $groupId = $_POST['group_id'] ?? '';
    $userId = $_SESSION['user_id'];
    
    // Basic validation
    if (empty($title) || empty($content) || empty($groupId)) {
        $error = "All fields are required";
    } else {
        // Create the discussion
        $sql = "INSERT INTO discussions (title, content, user_id, group_id) VALUES (?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "ssii", $title, $content, $userId, $groupId);
        
        if (mysqli_stmt_execute($stmt)) {
            $success = true;
            // Redirect to avoid form resubmission
            header("Location: discussions.php?created=1");
            exit();
        } else {
            $error = "Failed to create discussion. Please try again.";
        }
    }
}

// Check for success message from redirection
$showSuccess = isset($_GET['created']) && $_GET['created'] == 1;

// Include header
include 'includes/header.php';
?>

<!-- Main Content -->
<div class="container mx-auto py-8 px-4">
    <h1 class="text-3xl font-bold text-center text-gray-800 mb-6">Discussions</h1>
    
    <?php if ($showSuccess): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
            Your discussion has been created successfully!
        </div>
    <?php endif; ?>
    
    <?php if (!empty($error)): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>
    
    <div class="flex flex-col md:flex-row gap-8">
        <!-- Discussions List -->
        <div class="w-full md:w-2/3">
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-semibold text-gray-800 mb-4">Recent Discussions</h2>
                
                <?php if (empty($discussions)): ?>
                    <!-- Display default discussions when no discussions in database -->
                    <div class="space-y-6">
                        <div class="border-b pb-4">
                            <div class="flex justify-between items-start">
                                <h3 class="text-lg font-bold text-blue-600">Tips for Advanced Calculus Exam</h3>
                                <span class="text-sm text-gray-500">Mathematics Group</span>
                            </div>
                            <p class="text-gray-700 mt-1">I'm struggling with some complex integration techniques. Anyone has some tips or good online resources to share?</p>
                            <div class="mt-2 flex justify-between">
                                <span class="text-sm text-gray-500">Started by: Ahmed Khan • 2 days ago</span>
                                <a href="#" class="text-blue-600 hover:underline">View Discussion (12 replies)</a>
                            </div>
                        </div>
                        
                        <div class="border-b pb-4">
                            <div class="flex justify-between items-start">
                                <h3 class="text-lg font-bold text-blue-600">Best Frameworks for Web Development</h3>
                                <span class="text-sm text-gray-500">Computer Science Group</span>
                            </div>
                            <p class="text-gray-700 mt-1">What are your thoughts on React vs Angular vs Vue? Which one would you recommend for a beginner?</p>
                            <div class="mt-2 flex justify-between">
                                <span class="text-sm text-gray-500">Started by: Maria Lee • 4 days ago</span>
                                <a href="#" class="text-blue-600 hover:underline">View Discussion (8 replies)</a>
                            </div>
                        </div>
                        
                        <div class="border-b pb-4">
                            <div class="flex justify-between items-start">
                                <h3 class="text-lg font-bold text-blue-600">Physics Experimental Lab Practice</h3>
                                <span class="text-sm text-gray-500">Physics Group</span>
                            </div>
                            <p class="text-gray-700 mt-1">Does anyone have experience with the pendulum experiment? I'm getting inconsistent results and need help.</p>
                            <div class="mt-2 flex justify-between">
                                <span class="text-sm text-gray-500">Started by: John Smith • 1 week ago</span>
                                <a href="#" class="text-blue-600 hover:underline">View Discussion (5 replies)</a>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- Display discussions from database -->
                    <div class="space-y-6">
                        <?php foreach ($discussions as $discussion): ?>
                            <div class="border-b pb-4">
                                <div class="flex justify-between items-start">
                                    <h3 class="text-lg font-bold text-blue-600"><?php echo htmlspecialchars($discussion['title']); ?></h3>
                                    <span class="text-sm text-gray-500"><?php echo htmlspecialchars($discussion['group_name']); ?></span>
                                </div>
                                <p class="text-gray-700 mt-1"><?php echo htmlspecialchars(substr($discussion['content'], 0, 150)); ?>...</p>
                                <div class="mt-2 flex justify-between">
                                    <span class="text-sm text-gray-500">
                                        Started by: <?php echo htmlspecialchars($discussion['user_name']); ?> • 
                                        <?php echo date('M j, Y', strtotime($discussion['created_at'])); ?>
                                    </span>
                                    <a href="discussion.php?id=<?php echo $discussion['id']; ?>" class="text-blue-600 hover:underline">View Discussion</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Start Discussion Form -->
        <div class="w-full md:w-1/3">
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-semibold text-gray-800 mb-4">Start a New Discussion</h2>
                
                <?php if (!isLoggedIn()): ?>
                    <div class="bg-yellow-100 p-4 rounded-lg">
                        <p class="text-yellow-800">You need to <a href="login.php" class="font-bold underline">log in</a> to start a discussion.</p>
                    </div>
                <?php else: ?>
                    <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                        <input type="hidden" name="action" value="create_discussion">
                        
                        <div class="mb-4">
                            <label for="title" class="block text-gray-700 mb-1">Title</label>
                            <input type="text" id="title" name="title" class="w-full px-4 py-2 border rounded-lg" required>
                        </div>
                        
                        <div class="mb-4">
                            <label for="group_id" class="block text-gray-700 mb-1">Group</label>
                            <select id="group_id" name="group_id" class="w-full px-4 py-2 border rounded-lg" required>
                                <option value="">Select a group</option>
                                <?php if (empty($groups)): ?>
                                    <option value="1">Mathematics Group</option>
                                    <option value="2">Computer Science Group</option>
                                    <option value="3">Physics Group</option>
                                    <option value="4">Literature Circle</option>
                                <?php else: ?>
                                    <?php foreach ($groups as $group): ?>
                                        <option value="<?php echo $group['id']; ?>"><?php echo htmlspecialchars($group['name']); ?></option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                        
                        <div class="mb-4">
                            <label for="content" class="block text-gray-700 mb-1">Content</label>
                            <textarea id="content" name="content" rows="6" class="w-full px-4 py-2 border rounded-lg" required></textarea>
                        </div>
                        
                        <button type="submit" class="w-full bg-blue-600 text-white py-2 rounded-lg hover:bg-blue-700">Post Discussion</button>
                    </form>
                <?php endif; ?>
                
                <div class="mt-6">
                    <h3 class="font-semibold text-gray-700 mb-2">Posting Guidelines</h3>
                    <ul class="text-sm text-gray-600 space-y-1 list-disc list-inside">
                        <li>Be clear and specific with your questions</li>
                        <li>Provide context to help others understand your issue</li>
                        <li>Be respectful in your discussions</li>
                        <li>Check if a similar topic has been discussed before</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
include 'includes/footer.php';

// Close database connection
mysqli_close($conn);
?> 