<?php
// Include database connection
$conn = require_once 'config.php';

// Include auth functions
require_once 'auth.php';

// Get resource ID from URL
$resourceId = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Check if resource ID is valid
if ($resourceId <= 0) {
    header("Location: resources.php");
    exit();
}

// Get resource data
$resource = null;
$sql = "SELECT r.*, u.name as user_name 
        FROM resources r 
        JOIN users u ON r.user_id = u.id 
        WHERE r.id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $resourceId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if ($row = mysqli_fetch_assoc($result)) {
    $resource = $row;
} else {
    // Resource not found, redirect to resources list
    header("Location: resources.php");
    exit();
}

// Set page title
$pageTitle = 'View Resource: ' . $resource['title'];

// Get file extension for determining resource type
$fileExtension = pathinfo($resource['file_path'], PATHINFO_EXTENSION);

// Include header
include 'includes/header.php';
?>

<div class="container mx-auto py-8 px-4">
    <div class="bg-white rounded-lg shadow-md p-6">
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-bold text-gray-800"><?php echo htmlspecialchars($resource['title']); ?></h1>
            <a href="resources.php" class="bg-gray-200 text-gray-700 px-4 py-2 rounded hover:bg-gray-300">
                <i class="fas fa-arrow-left mr-2"></i> Back to Resources
            </a>
        </div>
        
        <div class="mb-6">
            <div class="mb-4 pb-4 border-b">
                <h2 class="text-lg font-semibold text-gray-700 mb-2">Resource Details</h2>
                <p class="text-gray-600"><?php echo nl2br(htmlspecialchars($resource['description'] ?? 'No description provided.')); ?></p>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                <div>
                    <p class="text-gray-600">
                        <span class="font-medium">Type:</span> 
                        <?php echo ucfirst(htmlspecialchars($resource['type'])); ?>
                    </p>
                </div>
                <div>
                    <p class="text-gray-600">
                        <span class="font-medium">Uploaded by:</span> 
                        <?php echo htmlspecialchars($resource['user_name']); ?>
                    </p>
                </div>
                <div>
                    <p class="text-gray-600">
                        <span class="font-medium">Uploaded on:</span> 
                        <?php echo date('F j, Y g:i A', strtotime($resource['created_at'])); ?>
                    </p>
                </div>
                <div>
                    <p class="text-gray-600">
                        <span class="font-medium">File:</span> 
                        <a href="<?php echo htmlspecialchars($resource['file_path']); ?>" 
                           download class="text-blue-600 hover:underline">
                            Download File
                        </a>
                    </p>
                </div>
            </div>
        </div>
        
        <!-- Resource Preview -->
        <div class="border rounded-lg p-4 bg-gray-50">
            <h2 class="text-lg font-semibold text-gray-700 mb-4">Resource Preview</h2>
            
            <?php if (in_array(strtolower($fileExtension), ['jpg', 'jpeg', 'png', 'gif'])): ?>
                <!-- Image Preview -->
                <div class="flex justify-center">
                    <img src="<?php echo htmlspecialchars($resource['file_path']); ?>" 
                         alt="<?php echo htmlspecialchars($resource['title']); ?>" 
                         class="max-w-full max-h-96 rounded">
                </div>
            <?php elseif (strtolower($fileExtension) === 'pdf'): ?>
                <!-- PDF Preview -->
                <div class="h-96">
                    <object data="<?php echo htmlspecialchars($resource['file_path']); ?>" 
                            type="application/pdf" 
                            width="100%" 
                            height="100%">
                        <p>
                            It appears your browser doesn't support embedded PDFs. 
                            <a href="<?php echo htmlspecialchars($resource['file_path']); ?>" 
                               download class="text-blue-600 hover:underline">
                                Click here to download the file
                            </a>.
                        </p>
                    </object>
                </div>
            <?php else: ?>
                <!-- No Preview Available - Download Prompt -->
                <div class="text-center py-10">
                    <i class="fas fa-file-download text-gray-400 text-5xl mb-4"></i>
                    <p class="text-gray-600 mb-6">
                        This file type cannot be previewed in the browser. 
                        Please download to view its contents.
                    </p>
                    <a href="<?php echo htmlspecialchars($resource['file_path']); ?>" 
                       download class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700">
                        Download File
                    </a>
                </div>
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