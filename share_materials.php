<?php
// Include database connection
$conn = require_once 'config.php';

// Include auth functions
require_once 'auth.php';

// Require user to be logged in
requireLogin();

// Get group ID from URL
$groupId = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Get group information
$group = null;
if ($groupId > 0) {
    $sql = "SELECT id, name, description FROM groups WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $groupId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    if ($row = mysqli_fetch_assoc($result)) {
        $group = $row;
    }
}

// If group doesn't exist, redirect to groups page
if (!$group) {
    header("Location: groups.php");
    exit();
}

// Create group_materials table if it doesn't exist
$sql = "CREATE TABLE IF NOT EXISTS group_materials (
    id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    group_id INT(11) NOT NULL,
    user_id INT(11) NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    file_path VARCHAR(255),
    file_type VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (group_id) REFERENCES groups(id),
    FOREIGN KEY (user_id) REFERENCES users(id)
)";
mysqli_query($conn, $sql);

// Create uploads directory if it doesn't exist
$uploadsDir = 'uploads/group_materials';
if (!file_exists($uploadsDir)) {
    mkdir($uploadsDir, 0755, true);
}

// Process form submission for sharing materials
$error = '';
$success = '';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] === 'share_material') {
    // Get form data
    $userId = $_SESSION['user_id'];
    $title = $_POST['title'] ?? '';
    $description = $_POST['description'] ?? '';
    $fileType = $_POST['file_type'] ?? '';
    
    // Basic validation
    if (empty($title)) {
        $error = "Title is required";
    } elseif (!isset($_FILES['material_file']) || $_FILES['material_file']['error'] === UPLOAD_ERR_NO_FILE) {
        $error = "Please select a file to upload";
    } else {
        // Handle file upload
        $file = $_FILES['material_file'];
        
        // Check for upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $error = "File upload failed. Error code: " . $file['error'];
        } else {
            // Generate unique filename
            $fileExtension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $newFilename = uniqid('material_') . '.' . $fileExtension;
            $filePath = $uploadsDir . '/' . $newFilename;
            
            // Move uploaded file
            if (move_uploaded_file($file['tmp_name'], $filePath)) {
                // Insert record into database
                $sql = "INSERT INTO group_materials (group_id, user_id, title, description, file_path, file_type) 
                        VALUES (?, ?, ?, ?, ?, ?)";
                $stmt = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt, "iissss", $groupId, $userId, $title, $description, $filePath, $fileType);
                
                if (mysqli_stmt_execute($stmt)) {
                    $success = "Material shared successfully!";
                } else {
                    $error = "Failed to save material information. Please try again.";
                }
            } else {
                $error = "Failed to upload file. Please try again.";
            }
        }
    }
}

// Get group materials
$materials = [];
$sql = "SELECT gm.id, gm.title, gm.description, gm.file_path, gm.file_type, gm.created_at, 
        u.name as user_name 
        FROM group_materials gm 
        JOIN users u ON gm.user_id = u.id 
        WHERE gm.group_id = ? 
        ORDER BY gm.created_at DESC";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $groupId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
while ($row = mysqli_fetch_assoc($result)) {
    $materials[] = $row;
}

// Set page title
$pageTitle = 'Share Materials - ' . $group['name'];

// Custom styles
$extraStyles = '
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
';

// Include header
include 'includes/header.php';
?>

<div class="container mx-auto py-4 px-4">
    <div class="bg-white rounded-lg shadow-lg p-4 mb-6">
        <div class="flex justify-between items-center">
            <div class="flex items-center">
                <div class="rounded-full bg-blue-600 h-10 w-10 flex items-center justify-center text-white font-bold text-lg mr-3">
                    <?php echo substr($group['name'], 0, 1); ?>
                </div>
                <div>
                    <h1 class="text-xl font-bold"><?php echo htmlspecialchars($group['name']); ?> - Study Materials</h1>
                    <p class="text-sm text-gray-600"><?php echo htmlspecialchars($group['description']); ?></p>
                </div>
            </div>
            <a href="group_chat.php?id=<?php echo $groupId; ?>" class="bg-gray-100 text-gray-600 px-4 py-2 rounded-lg hover:bg-gray-200">
                <i class="fas fa-arrow-left mr-2"></i> Back to Chat
            </a>
        </div>
    </div>
    
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Upload Material Form -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">Share Study Material</h2>
            
            <?php if (!empty($error)): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($success)): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . '?id=' . $groupId); ?>" enctype="multipart/form-data">
                <input type="hidden" name="action" value="share_material">
                
                <div class="mb-4">
                    <label for="title" class="block text-gray-700 mb-1">Title</label>
                    <input type="text" id="title" name="title" class="w-full px-4 py-2 border rounded-lg" required>
                </div>
                
                <div class="mb-4">
                    <label for="description" class="block text-gray-700 mb-1">Description</label>
                    <textarea id="description" name="description" rows="3" class="w-full px-4 py-2 border rounded-lg"></textarea>
                </div>
                
                <div class="mb-4">
                    <label for="file_type" class="block text-gray-700 mb-1">Material Type</label>
                    <select id="file_type" name="file_type" class="w-full px-4 py-2 border rounded-lg">
                        <option value="Notes">Notes</option>
                        <option value="Assignment">Assignment</option>
                        <option value="Practice Questions">Practice Questions</option>
                        <option value="Reference">Reference Material</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                
                <div class="mb-4">
                    <label for="material_file" class="block text-gray-700 mb-1">Upload File</label>
                    <input type="file" id="material_file" name="material_file" class="w-full px-4 py-2 border rounded-lg" required>
                    <p class="text-sm text-gray-500 mt-1">Max file size: 10MB. Supported formats: PDF, DOC, DOCX, PPT, PPTX, XLS, XLSX, TXT, JPG, PNG</p>
                </div>
                
                <button type="submit" class="w-full bg-blue-600 text-white py-2 rounded-lg hover:bg-blue-700">Share Material</button>
            </form>
        </div>
        
        <!-- Materials List -->
        <div class="md:col-span-2">
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-semibold text-gray-800 mb-4">Shared Materials</h2>
                
                <?php if (empty($materials)): ?>
                    <!-- Display message if no materials -->
                    <div class="text-center py-8 text-gray-500">
                        <i class="fas fa-file-alt text-6xl mb-4"></i>
                        <p>No study materials have been shared in this group yet.</p>
                        <p class="mt-2">Be the first to share something with your group members!</p>
                    </div>
                <?php else: ?>
                    <!-- Display materials from database -->
                    <div class="space-y-4">
                        <?php foreach ($materials as $material): ?>
                            <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition">
                                <div class="flex items-start">
                                    <?php 
                                    // Display appropriate icon based on file type
                                    $icon = 'fa-file';
                                    $iconColor = 'text-gray-500';
                                    
                                    $fileExtension = pathinfo($material['file_path'], PATHINFO_EXTENSION);
                                    if (in_array(strtolower($fileExtension), ['pdf'])) {
                                        $icon = 'fa-file-pdf';
                                        $iconColor = 'text-red-500';
                                    } elseif (in_array(strtolower($fileExtension), ['doc', 'docx'])) {
                                        $icon = 'fa-file-word';
                                        $iconColor = 'text-blue-500';
                                    } elseif (in_array(strtolower($fileExtension), ['ppt', 'pptx'])) {
                                        $icon = 'fa-file-powerpoint';
                                        $iconColor = 'text-orange-500';
                                    } elseif (in_array(strtolower($fileExtension), ['xls', 'xlsx'])) {
                                        $icon = 'fa-file-excel';
                                        $iconColor = 'text-green-500';
                                    } elseif (in_array(strtolower($fileExtension), ['jpg', 'jpeg', 'png', 'gif'])) {
                                        $icon = 'fa-file-image';
                                        $iconColor = 'text-purple-500';
                                    }
                                    ?>
                                    <div class="<?php echo $iconColor; ?> text-4xl mr-4">
                                        <i class="fas <?php echo $icon; ?>"></i>
                                    </div>
                                    <div class="flex-1">
                                        <h3 class="text-lg font-bold"><?php echo htmlspecialchars($material['title']); ?></h3>
                                        <p class="text-gray-600 text-sm mb-2"><?php echo htmlspecialchars($material['description']); ?></p>
                                        <div class="flex justify-between items-center">
                                            <span class="text-sm text-gray-500">
                                                Shared by: <?php echo htmlspecialchars($material['user_name']); ?> • 
                                                <?php echo date('M j, Y g:i A', strtotime($material['created_at'])); ?>
                                            </span>
                                            <span class="text-xs px-2 py-1 bg-gray-100 rounded-full">
                                                <?php echo htmlspecialchars($material['file_type']); ?>
                                            </span>
                                        </div>
                                    </div>
                                    <a href="<?php echo htmlspecialchars($material['file_path']); ?>" download class="bg-blue-100 text-blue-600 px-3 py-1 rounded hover:bg-blue-200 ml-2 flex items-center">
                                        <i class="fas fa-download mr-1"></i>
                                        <span>Download</span>
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
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