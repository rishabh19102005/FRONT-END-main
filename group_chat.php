<?php
// Include database connection
$conn = require_once 'config.php';

// Include auth functions
require_once 'auth.php';

// Require user to be logged in
requireLogin();

// Set page title
$pageTitle = 'Group Chat';

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

// If group doesn't exist, use default data
if (!$group) {
    $group = [
        'id' => 0,
        'name' => 'Mathematics Study Group',
        'description' => 'Focus on algebra, calculus, and statistics'
    ];
}

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

// Record that the user has joined this group
if ($groupId > 0) {
    $userId = $_SESSION['user_id'];
    $sql = "INSERT IGNORE INTO group_members (group_id, user_id) VALUES (?, ?)";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ii", $groupId, $userId);
    mysqli_stmt_execute($stmt);
}

// Get group members
$members = [];
if ($groupId > 0) {
    $sql = "SELECT u.id, u.name, gm.joined_at 
            FROM group_members gm
            JOIN users u ON gm.user_id = u.id
            WHERE gm.group_id = ?
            ORDER BY gm.joined_at DESC";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $groupId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($result)) {
        $members[] = $row;
    }
}

// Handle message submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] === 'send_message') {
    $userId = $_SESSION['user_id'];
    $message = $_POST['message'] ?? '';
    
    if (!empty($message)) {
        // Create chat_messages table if it doesn't exist
        $sql = "CREATE TABLE IF NOT EXISTS chat_messages (
            id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            group_id INT(11) NOT NULL,
            user_id INT(11) NOT NULL,
            message TEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (group_id) REFERENCES groups(id),
            FOREIGN KEY (user_id) REFERENCES users(id)
        )";
        mysqli_query($conn, $sql);
        
        // Insert message
        $sql = "INSERT INTO chat_messages (group_id, user_id, message) VALUES (?, ?, ?)";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "iis", $groupId, $userId, $message);
        mysqli_stmt_execute($stmt);
        
        // Redirect to avoid form resubmission
        header("Location: " . $_SERVER['REQUEST_URI']);
        exit();
    }
}

// Get chat messages
$messages = [];
if ($groupId > 0) {
    $sql = "SELECT m.id, m.message, m.created_at, u.name as user_name 
            FROM chat_messages m 
            JOIN users u ON m.user_id = u.id 
            WHERE m.group_id = ? 
            ORDER BY m.created_at DESC 
            LIMIT 50";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $groupId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($result)) {
        $messages[] = $row;
    }
    // Reverse to show oldest first
    $messages = array_reverse($messages);
}

// Custom styles
$extraStyles = '
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
';

// Include header
include 'includes/header.php';
?>

<div class="container mx-auto py-4 px-4">
    <div class="bg-white rounded-lg shadow-lg overflow-hidden">
        <!-- Group Header -->
        <div class="bg-blue-600 text-white p-4">
            <div class="flex items-center">
                <div class="rounded-full bg-white h-10 w-10 flex items-center justify-center text-blue-600 font-bold text-lg mr-3">
                    <?php echo substr($group['name'], 0, 1); ?>
                </div>
                <div>
                    <h1 class="text-xl font-bold"><?php echo htmlspecialchars($group['name']); ?></h1>
                    <p class="text-sm text-blue-100"><?php echo htmlspecialchars($group['description']); ?></p>
                </div>
            </div>
        </div>
        
        <!-- Chat Window -->
        <div class="flex flex-col h-[70vh]">
            <!-- Chat Messages -->
            <div class="flex-1 overflow-y-auto p-4 space-y-4" id="chat-messages">
                <?php if (empty($messages)): ?>
                    <!-- Welcome message when joining a group -->
                    <div class="text-center my-4">
                        <div class="inline-block bg-blue-100 text-blue-800 px-4 py-2 rounded-full text-sm">
                            Welcome to <?php echo htmlspecialchars($group['name']); ?>! Say hello to everyone.
                        </div>
                    </div>
                    
                    <!-- Sample messages if no messages in database -->
                    <div class="flex items-start mb-4">
                        <div class="rounded-full bg-blue-100 h-8 w-8 flex items-center justify-center text-blue-600 font-bold text-sm mr-2">J</div>
                        <div class="bg-gray-100 rounded-lg p-3 max-w-md">
                            <div class="font-semibold text-sm">Admin User</div>
                            <p>Hello everyone! Who's studying for the exam next week?</p>
                            <span class="text-xs text-gray-500">10:30 AM</span>
                        </div>
                    </div>
                    <div class="flex items-start mb-4 justify-end">
                        <div class="bg-blue-600 text-white rounded-lg p-3 max-w-md">
                            <p>I am! Would anyone like to join a study session on Friday evening?</p>
                            <span class="text-xs text-blue-200">10:32 AM</span>
                        </div>
                        <div class="rounded-full bg-green-500 h-8 w-8 flex items-center justify-center text-white font-bold text-sm ml-2">
                            <?php echo substr($_SESSION['user_name'] ?? 'You', 0, 1); ?>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- Display messages from database -->
                    <?php foreach ($messages as $msg): ?>
                        <?php $isCurrentUser = ($msg['user_name'] == $_SESSION['user_name']); ?>
                        <div class="flex items-start mb-4 <?php echo $isCurrentUser ? 'justify-end' : ''; ?>">
                            <?php if (!$isCurrentUser): ?>
                                <div class="rounded-full bg-blue-100 h-8 w-8 flex items-center justify-center text-blue-600 font-bold text-sm mr-2">
                                    <?php echo substr($msg['user_name'], 0, 1); ?>
                                </div>
                            <?php endif; ?>
                            
                            <div class="<?php echo $isCurrentUser ? 'bg-blue-600 text-white' : 'bg-gray-100'; ?> rounded-lg p-3 max-w-md">
                                <?php if (!$isCurrentUser): ?>
                                    <div class="font-semibold text-sm"><?php echo htmlspecialchars($msg['user_name']); ?></div>
                                <?php endif; ?>
                                <p><?php echo htmlspecialchars($msg['message']); ?></p>
                                <span class="text-xs <?php echo $isCurrentUser ? 'text-blue-200' : 'text-gray-500'; ?>">
                                    <?php echo date('g:i A', strtotime($msg['created_at'])); ?>
                                </span>
                            </div>
                            
                            <?php if ($isCurrentUser): ?>
                                <div class="rounded-full bg-green-500 h-8 w-8 flex items-center justify-center text-white font-bold text-sm ml-2">
                                    <?php echo substr($_SESSION['user_name'], 0, 1); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <!-- Message Input -->
            <div class="border-t p-3">
                <form method="POST" action="<?php echo htmlspecialchars($_SERVER['REQUEST_URI']); ?>" class="flex">
                    <input type="hidden" name="action" value="send_message">
                    <input type="text" name="message" placeholder="Type your message..." class="flex-1 border rounded-l-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                    <button type="submit" class="bg-blue-600 text-white rounded-r-lg px-4 py-2 hover:bg-blue-700">
                        <i class="fas fa-paper-plane"></i>
                    </button>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Group Members and Options -->
    <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Group Members -->
        <div class="bg-white rounded-lg shadow-md p-4">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">Group Members</h2>
            <div class="space-y-3">
                <?php if (empty($members)): ?>
                    <!-- Default members when no members in database -->
                    <div class="flex items-center">
                        <div class="rounded-full bg-blue-100 h-8 w-8 flex items-center justify-center text-blue-600 font-bold text-sm mr-3">J</div>
                        <div>
                            <span class="font-medium">John Doe</span>
                            <span class="text-xs text-green-500 ml-2">Online</span>
                        </div>
                    </div>
                    <div class="flex items-center">
                        <div class="rounded-full bg-purple-500 h-8 w-8 flex items-center justify-center text-white font-bold text-sm mr-3">S</div>
                        <div>
                            <span class="font-medium">Sarah Johnson</span>
                            <span class="text-xs text-gray-500 ml-2">Offline</span>
                        </div>
                    </div>
                    <div class="flex items-center">
                        <div class="rounded-full bg-green-500 h-8 w-8 flex items-center justify-center text-white font-bold text-sm mr-3">
                            <?php echo substr($_SESSION['user_name'], 0, 1); ?>
                        </div>
                        <div>
                            <span class="font-medium"><?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                            <span class="text-xs text-green-500 ml-2">You</span>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- Display members from database -->
                    <?php foreach ($members as $member): ?>
                        <?php $isCurrentUser = ($member['id'] == $_SESSION['user_id']); ?>
                        <div class="flex items-center">
                            <div class="rounded-full <?php echo $isCurrentUser ? 'bg-green-500 text-white' : 'bg-blue-100 text-blue-600'; ?> h-8 w-8 flex items-center justify-center font-bold text-sm mr-3">
                                <?php echo substr($member['name'], 0, 1); ?>
                            </div>
                            <div>
                                <span class="font-medium"><?php echo htmlspecialchars($member['name']); ?></span>
                                <?php if ($isCurrentUser): ?>
                                    <span class="text-xs text-green-500 ml-2">You</span>
                                <?php else: ?>
                                    <span class="text-xs text-gray-500 ml-2">
                                        Joined <?php echo date('M j', strtotime($member['joined_at'])); ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Group Options -->
        <div class="bg-white rounded-lg shadow-md p-4">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">Group Options</h2>
            <div class="space-y-3">
                <div class="flex items-center space-x-3">
                    <a href="video_call.php?id=<?php echo $groupId; ?>" class="bg-blue-100 text-blue-600 px-4 py-2 rounded-lg hover:bg-blue-200 flex items-center">
                        <i class="fas fa-video mr-2"></i>
                        <span>Start Video Call</span>
                    </a>
                    <a href="voice_call.php?id=<?php echo $groupId; ?>" class="bg-green-100 text-green-600 px-4 py-2 rounded-lg hover:bg-green-200 flex items-center">
                        <i class="fas fa-phone-alt mr-2"></i>
                        <span>Voice Call</span>
                    </a>
                </div>
                
                <a href="share_materials.php?id=<?php echo $groupId; ?>" class="block bg-gray-100 text-gray-600 px-4 py-2 rounded-lg hover:bg-gray-200">
                    <i class="fas fa-file-alt mr-2"></i>
                    <span>Share Study Materials</span>
                </a>
                
                <a href="schedule_session.php?id=<?php echo $groupId; ?>" class="block bg-gray-100 text-gray-600 px-4 py-2 rounded-lg hover:bg-gray-200">
                    <i class="fas fa-calendar-alt mr-2"></i>
                    <span>Schedule Study Session</span>
                </a>
                
                <a href="groups.php" class="block bg-gray-100 text-gray-600 px-4 py-2 rounded-lg hover:bg-gray-200">
                    <i class="fas fa-arrow-left mr-2"></i>
                    <span>Back to Groups</span>
                </a>
            </div>
        </div>
    </div>
</div>

<script>
    // Auto-scroll to bottom of messages on page load
    document.addEventListener('DOMContentLoaded', function() {
        const chatMessages = document.getElementById('chat-messages');
        if (chatMessages) {
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }
    });
</script>

<?php
// Include footer
include 'includes/footer.php';

// Close database connection
mysqli_close($conn);
?> 