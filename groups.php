<?php
// Include database connection
$conn = require_once 'config.php';

// Include auth functions
require_once 'auth.php';

// Set page title
$pageTitle = 'Study Groups';

// Create group_members table if it doesn't exist
$sql = "CREATE TABLE IF NOT EXISTS group_members (
    id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    group_id INT(11) NOT NULL,
    user_id INT(11) NOT NULL,
    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY group_user (group_id, user_id),
    FOREIGN KEY (group_id) REFERENCES groups(id),
    FOREIGN KEY (user_id) REFERENCES users(id)
)";
mysqli_query($conn, $sql);

// Get study groups from database with member counts
$groups = [];
$sql = "SELECT g.id, g.name, g.description, g.created_at, 
        (SELECT COUNT(*) FROM group_members WHERE group_id = g.id) as member_count 
        FROM groups g 
        ORDER BY g.created_at DESC";
$result = mysqli_query($conn, $sql);
if ($result && mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        $groups[] = $row;
    }
}

// Process form submission for creating a new group
$error = '';
$success = false;

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] === 'create_group') {
    // Check if user is logged in
    if (!isLoggedIn()) {
        header("Location: login.php");
        exit();
    }
    
    // Get form data
    $groupName = $_POST['group_name'] ?? '';
    $groupDescription = $_POST['group_description'] ?? '';
    $userId = $_SESSION['user_id'];
    
    // Basic validation
    if (empty($groupName)) {
        $error = "Group name is required";
    } else {
        // Create the group
        $sql = "INSERT INTO groups (name, description, created_by) VALUES (?, ?, ?)";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "ssi", $groupName, $groupDescription, $userId);
        
        if (mysqli_stmt_execute($stmt)) {
            // Get the newly created group ID
            $groupId = mysqli_insert_id($conn);
            
            // Add the creator as a member
            $sql = "INSERT INTO group_members (group_id, user_id) VALUES (?, ?)";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "ii", $groupId, $userId);
            mysqli_stmt_execute($stmt);
            
            $success = true;
            // Redirect to avoid form resubmission
            header("Location: groups.php?created=1");
            exit();
        } else {
            $error = "Failed to create group. Please try again.";
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
    <h1 class="text-3xl font-bold text-center text-gray-800 mb-6">Study Groups</h1>
    
    <?php if ($showSuccess): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
            Your study group has been created successfully!
        </div>
    <?php endif; ?>
    
    <?php if (!empty($error)): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>
    
    <div class="flex flex-col md:flex-row gap-8">
        <!-- Groups List -->
        <div class="w-full md:w-2/3">
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-semibold text-gray-800 mb-4">Available Groups</h2>
                
                <?php if (empty($groups)): ?>
                    <!-- Display default groups when no groups in database -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition">
                            <h3 class="text-lg font-bold">Mathematics Study Group</h3>
                            <p class="text-gray-600 text-sm">Focus on algebra, calculus, and statistics</p>
                            <div class="flex justify-between items-center mt-4">
                                <span class="text-sm text-gray-500">Members: 120</span>
                                <a href="group_chat.php?id=1" class="bg-blue-600 text-white px-4 py-1 rounded hover:bg-blue-700 text-sm">Join</a>
                            </div>
                        </div>
                        <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition">
                            <h3 class="text-lg font-bold">Computer Science Group</h3>
                            <p class="text-gray-600 text-sm">Programming, data structures, and algorithms</p>
                            <div class="flex justify-between items-center mt-4">
                                <span class="text-sm text-gray-500">Members: 85</span>
                                <a href="group_chat.php?id=2" class="bg-blue-600 text-white px-4 py-1 rounded hover:bg-blue-700 text-sm">Join</a>
                            </div>
                        </div>
                        <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition">
                            <h3 class="text-lg font-bold">Physics Discussion</h3>
                            <p class="text-gray-600 text-sm">Mechanics, thermodynamics, and quantum physics</p>
                            <div class="flex justify-between items-center mt-4">
                                <span class="text-sm text-gray-500">Members: 64</span>
                                <a href="group_chat.php?id=3" class="bg-blue-600 text-white px-4 py-1 rounded hover:bg-blue-700 text-sm">Join</a>
                            </div>
                        </div>
                        <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition">
                            <h3 class="text-lg font-bold">Literature Circle</h3>
                            <p class="text-gray-600 text-sm">Book discussions, poetry, and creative writing</p>
                            <div class="flex justify-between items-center mt-4">
                                <span class="text-sm text-gray-500">Members: 42</span>
                                <a href="group_chat.php?id=4" class="bg-blue-600 text-white px-4 py-1 rounded hover:bg-blue-700 text-sm">Join</a>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- Display groups from database -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <?php foreach ($groups as $group): ?>
                            <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition">
                                <h3 class="text-lg font-bold"><?php echo htmlspecialchars($group['name']); ?></h3>
                                <p class="text-gray-600 text-sm"><?php echo htmlspecialchars($group['description']); ?></p>
                                <div class="flex justify-between items-center mt-4">
                                    <span class="text-sm text-gray-500">
                                        Members: <?php echo intval($group['member_count']); ?>
                                    </span>
                                    <a href="group_chat.php?id=<?php echo $group['id']; ?>" class="bg-blue-600 text-white px-4 py-1 rounded hover:bg-blue-700 text-sm">Join</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Create Group Form -->
        <div class="w-full md:w-1/3">
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-semibold text-gray-800 mb-4">Create a Group</h2>
                
                <?php if (!isLoggedIn()): ?>
                    <div class="bg-yellow-100 p-4 rounded-lg">
                        <p class="text-yellow-800">You need to <a href="login.php" class="font-bold underline">log in</a> to create a study group.</p>
                    </div>
                <?php else: ?>
                    <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                        <input type="hidden" name="action" value="create_group">
                        
                        <div class="mb-4">
                            <label for="group_name" class="block text-gray-700 mb-1">Group Name</label>
                            <input type="text" id="group_name" name="group_name" class="w-full px-4 py-2 border rounded-lg" required>
                        </div>
                        
                        <div class="mb-4">
                            <label for="group_description" class="block text-gray-700 mb-1">Description</label>
                            <textarea id="group_description" name="group_description" rows="3" class="w-full px-4 py-2 border rounded-lg"></textarea>
                        </div>
                        
                        <button type="submit" class="w-full bg-blue-600 text-white py-2 rounded-lg hover:bg-blue-700">Create Group</button>
                    </form>
                <?php endif; ?>
                
                <div class="mt-6">
                    <h3 class="font-semibold text-gray-700 mb-2">Group Guidelines</h3>
                    <ul class="text-sm text-gray-600 space-y-1 list-disc list-inside">
                        <li>Be respectful to all members</li>
                        <li>Stay on topic and relevant to the group's purpose</li>
                        <li>No spamming or self-promotion</li>
                        <li>Share resources that benefit the group</li>
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