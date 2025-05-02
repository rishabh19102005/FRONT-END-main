<?php
// Include database connection
$conn = require_once 'config.php';

// Include auth functions
require_once 'auth.php';

// Set page title
$pageTitle = 'Resources - Study Groups';

// Get resources from database
$resources = [];
$sql = "SELECT r.id, r.title, r.description, r.file_path, r.type, r.created_at, u.name as user_name 
        FROM resources r 
        JOIN users u ON r.user_id = u.id 
        ORDER BY r.created_at DESC 
        LIMIT 10";
$result = mysqli_query($conn, $sql);
if ($result && mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        $resources[] = $row;
    }
}

// Process form submission for uploading a new resource
$error = '';
$success = false;

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] === 'upload_resource') {
    // Check if user is logged in
    if (!isLoggedIn()) {
        header("Location: login.php");
        exit();
    }
    
    // Get form data
    $title = $_POST['title'] ?? '';
    $description = $_POST['description'] ?? '';
    $type = $_POST['type'] ?? '';
    $userId = $_SESSION['user_id'];
    
    // Basic validation
    if (empty($title) || empty($type)) {
        $error = "Title and resource type are required";
    } else {
        // File upload handling
        $uploadDir = 'uploads/';
        $filePath = '';
        
        // Check if directory exists, if not create it
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        // Check if a file was uploaded
        if (isset($_FILES['resource_file']) && $_FILES['resource_file']['error'] == 0) {
            $fileName = basename($_FILES['resource_file']['name']);
            $targetFilePath = $uploadDir . time() . '_' . $fileName; // Add timestamp to make filename unique
            
            // Get file extension
            $fileType = strtolower(pathinfo($targetFilePath, PATHINFO_EXTENSION));
            
            // Set allowed file types based on the selected resource type
            $allowedTypes = array();
            switch ($type) {
                case 'pdf':
                    $allowedTypes = array('pdf');
                    break;
                case 'word':
                    $allowedTypes = array('doc', 'docx');
                    break;
                case 'excel':
                    $allowedTypes = array('xls', 'xlsx', 'csv');
                    break;
                case 'powerpoint':
                    $allowedTypes = array('ppt', 'pptx');
                    break;
                case 'text':
                    $allowedTypes = array('txt', 'rtf');
                    break;
                case 'image':
                    $allowedTypes = array('jpg', 'jpeg', 'png', 'gif');
                    break;
                default:
                    $allowedTypes = array('pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'jpg', 'jpeg', 'png', 'gif');
            }
            
            // Check if file type is allowed
            if (in_array($fileType, $allowedTypes)) {
                // Upload file
                if (move_uploaded_file($_FILES['resource_file']['tmp_name'], $targetFilePath)) {
                    $filePath = $targetFilePath;
                } else {
                    $error = "Failed to upload file. Please try again.";
                }
            } else {
                $error = "Invalid file format. Please upload a valid file type.";
            }
        } else {
            $error = "Please select a file to upload.";
        }
        
        if (empty($error)) {
            // Create the resource
            $sql = "INSERT INTO resources (title, description, file_path, type, user_id) VALUES (?, ?, ?, ?, ?)";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "ssssi", $title, $description, $filePath, $type, $userId);
            
            if (mysqli_stmt_execute($stmt)) {
                $success = true;
                // Redirect to avoid form resubmission
                header("Location: resources.php?uploaded=1");
                exit();
            } else {
                $error = "Failed to upload resource. Please try again.";
            }
        }
    }
}

// Check for success message from redirection
$showSuccess = isset($_GET['uploaded']) && $_GET['uploaded'] == 1;

// Include header
include 'includes/header.php';
?>

<!-- Main Content -->
<div class="container mx-auto py-8 px-4">
    <h1 class="text-3xl font-bold text-center text-gray-800 mb-6">Study Resources</h1>
    
    <?php if ($showSuccess): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
            Your resource has been uploaded successfully!
        </div>
    <?php endif; ?>
    
    <?php if (!empty($error)): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>
    
    <!-- Resources Grid -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-8">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-xl font-semibold text-gray-800">Available Resources</h2>
            <div class="flex space-x-2">
                <button class="px-3 py-1 bg-blue-100 text-blue-600 rounded hover:bg-blue-200">All</button>
                <button class="px-3 py-1 bg-gray-100 text-gray-600 rounded hover:bg-gray-200">Notes</button>
                <button class="px-3 py-1 bg-gray-100 text-gray-600 rounded hover:bg-gray-200">Books</button>
                <button class="px-3 py-1 bg-gray-100 text-gray-600 rounded hover:bg-gray-200">Practice Tests</button>
            </div>
        </div>
        
        <?php if (empty($resources)): ?>
            <!-- Display default resources when no resources in database -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div class="border rounded-lg overflow-hidden hover:shadow-md transition">
                    <div class="bg-blue-100 p-4">
                        <i class="fas fa-file-pdf text-red-500 text-3xl"></i>
                    </div>
                    <div class="p-4">
                        <h3 class="text-lg font-bold">Calculus Cheat Sheet</h3>
                        <p class="text-gray-600 text-sm mt-1">Quick reference for derivatives and integrals</p>
                        <div class="mt-3 flex justify-between text-sm">
                            <span class="text-gray-500">Uploaded by: Prof. Smith</span>
                            <a href="study_resource.php" class="text-blue-600 hover:underline">View</a>
                        </div>
                    </div>
                </div>
                
                <div class="border rounded-lg overflow-hidden hover:shadow-md transition">
                    <div class="bg-green-100 p-4">
                        <i class="fas fa-file-excel text-green-500 text-3xl"></i>
                    </div>
                    <div class="p-4">
                        <h3 class="text-lg font-bold">Physics Formula Collection</h3>
                        <p class="text-gray-600 text-sm mt-1">Comprehensive list of formulas for mechanics and E&M</p>
                        <div class="mt-3 flex justify-between text-sm">
                            <span class="text-gray-500">Uploaded by: David Lee</span>
                            <a href="study_resource.php" class="text-blue-600 hover:underline">View</a>
                        </div>
                    </div>
                </div>
                
                <div class="border rounded-lg overflow-hidden hover:shadow-md transition">
                    <div class="bg-yellow-100 p-4">
                        <i class="fas fa-file-alt text-yellow-500 text-3xl"></i>
                    </div>
                    <div class="p-4">
                        <h3 class="text-lg font-bold">Programming Best Practices</h3>
                        <p class="text-gray-600 text-sm mt-1">A guide to writing clean, efficient code</p>
                        <div class="mt-3 flex justify-between text-sm">
                            <span class="text-gray-500">Uploaded by: Tech Team</span>
                            <a href="study_resource.php" class="text-blue-600 hover:underline">View</a>
                        </div>
                    </div>
                </div>
                
                <div class="border rounded-lg overflow-hidden hover:shadow-md transition">
                    <div class="bg-purple-100 p-4">
                        <i class="fas fa-book text-purple-500 text-3xl"></i>
                    </div>
                    <div class="p-4">
                        <h3 class="text-lg font-bold">Literature Analysis Guide</h3>
                        <p class="text-gray-600 text-sm mt-1">How to analyze themes and characters in classic works</p>
                        <div class="mt-3 flex justify-between text-sm">
                            <span class="text-gray-500">Uploaded by: English Dept</span>
                            <a href="study_resource.php" class="text-blue-600 hover:underline">View</a>
                        </div>
                    </div>
                </div>
                
                <div class="border rounded-lg overflow-hidden hover:shadow-md transition">
                    <div class="bg-red-100 p-4">
                        <i class="fas fa-file-powerpoint text-red-500 text-3xl"></i>
                    </div>
                    <div class="p-4">
                        <h3 class="text-lg font-bold">Chemistry Lecture Slides</h3>
                        <p class="text-gray-600 text-sm mt-1">Organic chemistry reaction mechanisms</p>
                        <div class="mt-3 flex justify-between text-sm">
                            <span class="text-gray-500">Uploaded by: Dr. Patel</span>
                            <a href="study_resource.php" class="text-blue-600 hover:underline">View</a>
                        </div>
                    </div>
                </div>
                
                <div class="border rounded-lg overflow-hidden hover:shadow-md transition">
                    <div class="bg-blue-100 p-4">
                        <i class="fas fa-file-word text-blue-500 text-3xl"></i>
                    </div>
                    <div class="p-4">
                        <h3 class="text-lg font-bold">Essay Writing Template</h3>
                        <p class="text-gray-600 text-sm mt-1">Structure and format for academic essays</p>
                        <div class="mt-3 flex justify-between text-sm">
                            <span class="text-gray-500">Uploaded by: Writing Center</span>
                            <a href="study_resource.php" class="text-blue-600 hover:underline">View</a>
                        </div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <!-- Display resources from database -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($resources as $resource): ?>
                    <?php 
                    // Determine icon and color based on type
                    $bgColor = 'bg-blue-100';
                    $iconClass = 'fas fa-file text-blue-500';
                    
                    switch ($resource['type']) {
                        case 'pdf':
                            $bgColor = 'bg-red-100';
                            $iconClass = 'fas fa-file-pdf text-red-500';
                            break;
                        case 'excel':
                            $bgColor = 'bg-green-100';
                            $iconClass = 'fas fa-file-excel text-green-500';
                            break;
                        case 'word':
                            $bgColor = 'bg-blue-100';
                            $iconClass = 'fas fa-file-word text-blue-500';
                            break;
                        case 'powerpoint':
                            $bgColor = 'bg-red-100';
                            $iconClass = 'fas fa-file-powerpoint text-red-500';
                            break;
                        case 'text':
                            $bgColor = 'bg-yellow-100';
                            $iconClass = 'fas fa-file-alt text-yellow-500';
                            break;
                        case 'book':
                            $bgColor = 'bg-purple-100';
                            $iconClass = 'fas fa-book text-purple-500';
                            break;
                    }
                    ?>
                    
                    <div class="border rounded-lg overflow-hidden hover:shadow-md transition">
                        <div class="<?php echo $bgColor; ?> p-4">
                            <i class="<?php echo $iconClass; ?> text-3xl"></i>
                        </div>
                        <div class="p-4">
                            <h3 class="text-lg font-bold"><?php echo htmlspecialchars($resource['title']); ?></h3>
                            <p class="text-gray-600 text-sm mt-1"><?php echo htmlspecialchars($resource['description']); ?></p>
                            <div class="mt-3 flex justify-between text-sm">
                                <span class="text-gray-500">Uploaded by: <?php echo htmlspecialchars($resource['user_name']); ?></span>
                                <a href="resource.php?id=<?php echo $resource['id']; ?>" class="text-blue-600 hover:underline">View</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Upload Resource Form -->
    <div class="bg-white rounded-lg shadow-md p-6">
        <h2 class="text-xl font-semibold text-gray-800 mb-4">Upload a Resource</h2>
        
        <?php if (!isLoggedIn()): ?>
            <div class="bg-yellow-100 p-4 rounded-lg">
                <p class="text-yellow-800">You need to <a href="login.php" class="font-bold underline">log in</a> to upload resources.</p>
            </div>
        <?php else: ?>
            <form method="POST" action="" enctype="multipart/form-data" class="space-y-4">
                <input type="hidden" name="action" value="upload_resource">
                
                <div>
                    <label for="title" class="block text-gray-700 font-medium mb-1">Title <span class="text-red-500">*</span></label>
                    <input type="text" id="title" name="title" class="w-full border rounded px-3 py-2" required>
                </div>
                
                <div>
                    <label for="description" class="block text-gray-700 font-medium mb-1">Description</label>
                    <textarea id="description" name="description" rows="3" class="w-full border rounded px-3 py-2"></textarea>
                </div>
                
                <div>
                    <label for="type" class="block text-gray-700 font-medium mb-1">Resource Type <span class="text-red-500">*</span></label>
                    <select id="type" name="type" class="w-full border rounded px-3 py-2" required>
                        <option value="">Select a type</option>
                        <option value="pdf">PDF Document</option>
                        <option value="word">Word Document</option>
                        <option value="excel">Excel Spreadsheet</option>
                        <option value="powerpoint">PowerPoint Presentation</option>
                        <option value="text">Text Document</option>
                        <option value="image">Image</option>
                        <option value="book">Book/Article</option>
                    </select>
                </div>
                
                <div>
                    <label for="resource_file" class="block text-gray-700 font-medium mb-1">Upload File <span class="text-red-500">*</span></label>
                    <input type="file" id="resource_file" name="resource_file" class="w-full border rounded px-3 py-2" required>
                    <p class="text-gray-500 text-sm mt-1">Supported file types: PDF, DOC, DOCX, XLS, XLSX, PPT, PPTX, TXT, JPG, PNG</p>
                </div>
                
                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                    Upload Resource
                </button>
            </form>
        <?php endif; ?>
    </div>
</div>

<?php
// Include footer
include 'includes/footer.php';

// Close database connection
mysqli_close($conn);
?> 