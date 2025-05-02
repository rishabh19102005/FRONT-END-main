<?php
// Include database connection
$conn = require_once 'config.php';

// Include auth functions
require_once 'auth.php';

// Set page title
$pageTitle = 'Study Resource';

// Get resource ID from URL
$resourceId = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Get resource information
$resource = null;
if ($resourceId > 0) {
    $sql = "SELECT r.id, r.title, r.description, r.file_path, r.type, r.created_at, u.name as user_name 
            FROM resources r 
            JOIN users u ON r.user_id = u.id 
            WHERE r.id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $resourceId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    if ($row = mysqli_fetch_assoc($result)) {
        $resource = $row;
    }
}

// If resource doesn't exist, use default data
if (!$resource) {
    $resource = [
        'id' => 0,
        'title' => 'Calculus Cheat Sheet',
        'description' => 'Quick reference for derivatives and integrals',
        'file_path' => 'uploads/sample.pdf',
        'type' => 'pdf',
        'created_at' => date('Y-m-d H:i:s'),
        'user_name' => 'Prof. Smith'
    ];
}

// Handle comments
$comments = [];
if ($resourceId > 0) {
    // Create comments table if it doesn't exist
    $sql = "CREATE TABLE IF NOT EXISTS resource_comments (
        id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
        resource_id INT(11) NOT NULL,
        user_id INT(11) NOT NULL,
        comment TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (resource_id) REFERENCES resources(id),
        FOREIGN KEY (user_id) REFERENCES users(id)
    )";
    mysqli_query($conn, $sql);
    
    // Get comments for this resource
    $sql = "SELECT c.id, c.comment, c.created_at, u.name as user_name 
            FROM resource_comments c 
            JOIN users u ON c.user_id = u.id 
            WHERE c.resource_id = ? 
            ORDER BY c.created_at DESC";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $resourceId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($result)) {
        $comments[] = $row;
    }
}

// Process comment submission
$error = '';
$success = false;

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] === 'add_comment') {
    // Check if user is logged in
    if (!isLoggedIn()) {
        header("Location: login.php");
        exit();
    }
    
    // Get form data
    $comment = $_POST['comment'] ?? '';
    $userId = $_SESSION['user_id'];
    
    // Basic validation
    if (empty($comment)) {
        $error = "Comment cannot be empty";
    } else {
        // Insert comment
        $sql = "INSERT INTO resource_comments (resource_id, user_id, comment) VALUES (?, ?, ?)";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "iis", $resourceId, $userId, $comment);
        
        if (mysqli_stmt_execute($stmt)) {
            $success = true;
            // Redirect to avoid form resubmission
            header("Location: " . $_SERVER['REQUEST_URI']);
            exit();
        } else {
            $error = "Failed to post comment. Please try again.";
        }
    }
}

// Custom styles
$extraStyles = '<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">';

// Include header
include 'includes/header.php';
?>

<div class="container mx-auto py-8 px-4">
    <!-- Resource Details -->
    <div class="bg-white rounded-lg shadow-lg overflow-hidden mb-8">
        <!-- Header -->
        <div class="bg-blue-600 p-6 text-white">
            <div class="flex items-start justify-between">
                <div>
                    <h1 class="text-2xl font-bold"><?php echo htmlspecialchars($resource['title']); ?></h1>
                    <p class="text-blue-100">Uploaded by <?php echo htmlspecialchars($resource['user_name']); ?> • <?php echo date('F j, Y', strtotime($resource['created_at'])); ?></p>
                </div>
                <a href="resources.php" class="bg-white text-blue-600 px-4 py-2 rounded-lg hover:bg-blue-50">Back to Resources</a>
            </div>
        </div>
        
        <!-- Resource Content -->
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <!-- Preview -->
                <div class="md:col-span-2">
                    <div class="border rounded-lg p-2 bg-gray-100 mb-4">
                        <?php if ($resource['type'] === 'pdf'): ?>
                            <div class="h-[40vh] bg-gray-200 flex items-center justify-center border rounded">
                                <i class="fas fa-file-pdf text-red-500 text-6xl"></i>
                                <span class="ml-4 text-gray-600">PDF Preview</span>
                            </div>
                        <?php else: ?>
                            <div class="h-[40vh] bg-gray-200 flex items-center justify-center border rounded">
                                <i class="fas fa-file-alt text-gray-500 text-6xl"></i>
                                <span class="ml-4 text-gray-600">File Preview</span>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="mb-6">
                        <h2 class="text-xl font-semibold text-gray-800 mb-2">Description</h2>
                        <p class="text-gray-700"><?php echo htmlspecialchars($resource['description']); ?></p>
                    </div>
                    
                    <!-- Comments Section -->
                    <div>
                        <h2 class="text-xl font-semibold text-gray-800 mb-4">Comments</h2>
                        
                        <?php if (!isLoggedIn()): ?>
                            <div class="bg-yellow-100 p-4 rounded-lg mb-4">
                                <p class="text-yellow-800">You need to <a href="login.php" class="font-bold underline">log in</a> to leave a comment.</p>
                            </div>
                        <?php else: ?>
                            <?php if (!empty($error)): ?>
                                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                                    <?php echo htmlspecialchars($error); ?>
                                </div>
                            <?php endif; ?>
                            
                            <form method="POST" action="<?php echo htmlspecialchars($_SERVER['REQUEST_URI']); ?>" class="mb-6">
                                <input type="hidden" name="action" value="add_comment">
                                <div class="mb-2">
                                    <textarea name="comment" placeholder="Add your comment..." class="w-full px-4 py-2 border rounded-lg" rows="3" required></textarea>
                                </div>
                                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">Post Comment</button>
                            </form>
                        <?php endif; ?>
                        
                        <div class="space-y-4">
                            <?php if (empty($comments)): ?>
                                <!-- Sample comments -->
                                <div class="border-b pb-4">
                                    <div class="flex items-start">
                                        <div class="rounded-full bg-blue-100 h-8 w-8 flex items-center justify-center text-blue-600 font-bold text-sm mr-3">J</div>
                                        <div>
                                            <div class="flex items-center">
                                                <span class="font-medium">Jane Doe</span>
                                                <span class="text-gray-500 text-sm ml-2">2 days ago</span>
                                            </div>
                                            <p class="text-gray-700 mt-1">This resource is very helpful for understanding integration techniques. Thank you for sharing!</p>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="border-b pb-4">
                                    <div class="flex items-start">
                                        <div class="rounded-full bg-green-100 h-8 w-8 flex items-center justify-center text-green-600 font-bold text-sm mr-3">R</div>
                                        <div>
                                            <div class="flex items-center">
                                                <span class="font-medium">Robert Chen</span>
                                                <span class="text-gray-500 text-sm ml-2">1 week ago</span>
                                            </div>
                                            <p class="text-gray-700 mt-1">Could you add more examples for applications of partial derivatives? That would be really useful for my project.</p>
                                        </div>
                                    </div>
                                </div>
                            <?php else: ?>
                                <!-- Display comments from database -->
                                <?php foreach ($comments as $comment): ?>
                                    <div class="border-b pb-4">
                                        <div class="flex items-start">
                                            <div class="rounded-full bg-blue-100 h-8 w-8 flex items-center justify-center text-blue-600 font-bold text-sm mr-3">
                                                <?php echo substr($comment['user_name'], 0, 1); ?>
                                            </div>
                                            <div>
                                                <div class="flex items-center">
                                                    <span class="font-medium"><?php echo htmlspecialchars($comment['user_name']); ?></span>
                                                    <span class="text-gray-500 text-sm ml-2"><?php echo date('M j, Y', strtotime($comment['created_at'])); ?></span>
                                                </div>
                                                <p class="text-gray-700 mt-1"><?php echo htmlspecialchars($comment['comment']); ?></p>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Sidebar -->
                <div>
                    <div class="bg-gray-50 rounded-lg p-6 border mb-6">
                        <h2 class="text-lg font-semibold text-gray-800 mb-4">Resource Details</h2>
                        
                        <div class="space-y-3">
                            <div>
                                <h3 class="font-semibold text-gray-700">Type</h3>
                                <p class="text-gray-600"><?php echo ucfirst(htmlspecialchars($resource['type'])); ?> Document</p>
                            </div>
                            
                            <div>
                                <h3 class="font-semibold text-gray-700">Size</h3>
                                <p class="text-gray-600">2.4 MB</p>
                            </div>
                            
                            <div>
                                <h3 class="font-semibold text-gray-700">Uploaded On</h3>
                                <p class="text-gray-600"><?php echo date('F j, Y', strtotime($resource['created_at'])); ?></p>
                            </div>
                            
                            <div>
                                <h3 class="font-semibold text-gray-700">Author</h3>
                                <p class="text-gray-600"><?php echo htmlspecialchars($resource['user_name']); ?></p>
                            </div>
                            
                            <div class="pt-4">
                                <a href="<?php echo htmlspecialchars($resource['file_path']); ?>" class="block bg-blue-600 text-white text-center py-2 px-4 rounded-lg hover:bg-blue-700">
                                    <i class="fas fa-download mr-2"></i> Download
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-gray-50 rounded-lg p-6 border">
                        <h2 class="text-lg font-semibold text-gray-800 mb-4">Related Resources</h2>
                        
                        <div class="space-y-4">
                            <div class="flex items-start">
                                <div class="rounded-md bg-blue-100 p-2 mr-3">
                                    <i class="fas fa-file-pdf text-red-500"></i>
                                </div>
                                <div>
                                    <a href="#" class="font-medium text-blue-600 hover:underline">Advanced Calculus Problems</a>
                                    <p class="text-sm text-gray-500">Practice problems</p>
                                </div>
                            </div>
                            
                            <div class="flex items-start">
                                <div class="rounded-md bg-blue-100 p-2 mr-3">
                                    <i class="fas fa-book text-purple-500"></i>
                                </div>
                                <div>
                                    <a href="#" class="font-medium text-blue-600 hover:underline">Calculus Textbook Chapters</a>
                                    <p class="text-sm text-gray-500">Reference material</p>
                                </div>
                            </div>
                            
                            <div class="flex items-start">
                                <div class="rounded-md bg-blue-100 p-2 mr-3">
                                    <i class="fas fa-file-powerpoint text-red-500"></i>
                                </div>
                                <div>
                                    <a href="#" class="font-medium text-blue-600 hover:underline">Lecture Slides - Calculus</a>
                                    <p class="text-sm text-gray-500">Course materials</p>
                                </div>
                            </div>
                        </div>
                    </div>
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