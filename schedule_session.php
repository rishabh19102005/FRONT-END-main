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

// Create study_sessions table if it doesn't exist
$sql = "CREATE TABLE IF NOT EXISTS study_sessions (
    id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    group_id INT(11) NOT NULL,
    creator_id INT(11) NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    session_date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    location VARCHAR(255),
    is_online BOOLEAN DEFAULT 1,
    meeting_link VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (group_id) REFERENCES groups(id),
    FOREIGN KEY (creator_id) REFERENCES users(id)
)";
mysqli_query($conn, $sql);

// Create session_participants table if it doesn't exist
$sql = "CREATE TABLE IF NOT EXISTS session_participants (
    id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    session_id INT(11) NOT NULL,
    user_id INT(11) NOT NULL,
    status ENUM('attending', 'maybe', 'declined') DEFAULT 'attending',
    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY session_user (session_id, user_id),
    FOREIGN KEY (session_id) REFERENCES study_sessions(id),
    FOREIGN KEY (user_id) REFERENCES users(id)
)";
mysqli_query($conn, $sql);

// Process form submission for scheduling a session
$error = '';
$success = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['action']) && $_POST['action'] === 'schedule_session') {
        // Get form data
        $userId = $_SESSION['user_id'];
        $title = $_POST['title'] ?? '';
        $description = $_POST['description'] ?? '';
        $sessionDate = $_POST['session_date'] ?? '';
        $startTime = $_POST['start_time'] ?? '';
        $endTime = $_POST['end_time'] ?? '';
        $isOnline = isset($_POST['is_online']) ? 1 : 0;
        $location = $_POST['location'] ?? '';
        $meetingLink = $_POST['meeting_link'] ?? '';
        
        // Basic validation
        if (empty($title) || empty($sessionDate) || empty($startTime) || empty($endTime)) {
            $error = "Required fields are missing";
        } elseif ($endTime <= $startTime) {
            $error = "End time must be after start time";
        } else {
            // Insert session into database
            $sql = "INSERT INTO study_sessions (group_id, creator_id, title, description, session_date, start_time, end_time, location, is_online, meeting_link) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "iissssssis", $groupId, $userId, $title, $description, $sessionDate, $startTime, $endTime, $location, $isOnline, $meetingLink);
            
            if (mysqli_stmt_execute($stmt)) {
                // Get the newly created session ID
                $sessionId = mysqli_insert_id($conn);
                
                // Add creator as a participant
                $sql = "INSERT INTO session_participants (session_id, user_id, status) VALUES (?, ?, 'attending')";
                $stmt = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt, "ii", $sessionId, $userId);
                mysqli_stmt_execute($stmt);
                
                $success = "Study session scheduled successfully!";
            } else {
                $error = "Failed to schedule session. Please try again.";
            }
        }
    } elseif (isset($_POST['action']) && $_POST['action'] === 'update_status') {
        // Update participation status
        $sessionId = $_POST['session_id'] ?? 0;
        $status = $_POST['status'] ?? '';
        $userId = $_SESSION['user_id'];
        
        if ($sessionId > 0 && in_array($status, ['attending', 'maybe', 'declined'])) {
            // Check if already a participant
            $sql = "SELECT id FROM session_participants WHERE session_id = ? AND user_id = ?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "ii", $sessionId, $userId);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_store_result($stmt);
            
            if (mysqli_stmt_num_rows($stmt) > 0) {
                // Update existing status
                $sql = "UPDATE session_participants SET status = ? WHERE session_id = ? AND user_id = ?";
                $stmt = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt, "sii", $status, $sessionId, $userId);
            } else {
                // Insert new participant
                $sql = "INSERT INTO session_participants (session_id, user_id, status) VALUES (?, ?, ?)";
                $stmt = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt, "iis", $sessionId, $userId, $status);
            }
            
            if (mysqli_stmt_execute($stmt)) {
                $success = "Your participation status has been updated.";
            } else {
                $error = "Failed to update status. Please try again.";
            }
        }
    }
}

// Get upcoming study sessions for this group
$upcomingSessions = [];
$sql = "SELECT ss.*, 
        u.name as creator_name,
        (SELECT COUNT(*) FROM session_participants WHERE session_id = ss.id AND status = 'attending') as attending_count
        FROM study_sessions ss
        JOIN users u ON ss.creator_id = u.id
        WHERE ss.group_id = ? AND ss.session_date >= CURDATE()
        ORDER BY ss.session_date ASC, ss.start_time ASC";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $groupId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
while ($row = mysqli_fetch_assoc($result)) {
    // Get user's participation status
    $row['user_status'] = null;
    $sql2 = "SELECT status FROM session_participants WHERE session_id = ? AND user_id = ?";
    $stmt2 = mysqli_prepare($conn, $sql2);
    mysqli_stmt_bind_param($stmt2, "ii", $row['id'], $_SESSION['user_id']);
    mysqli_stmt_execute($stmt2);
    $result2 = mysqli_stmt_get_result($stmt2);
    if ($status = mysqli_fetch_assoc($result2)) {
        $row['user_status'] = $status['status'];
    }
    
    $upcomingSessions[] = $row;
}

// Get past study sessions for this group
$pastSessions = [];
$sql = "SELECT ss.*, 
        u.name as creator_name,
        (SELECT COUNT(*) FROM session_participants WHERE session_id = ss.id AND status = 'attending') as attending_count
        FROM study_sessions ss
        JOIN users u ON ss.creator_id = u.id
        WHERE ss.group_id = ? AND (ss.session_date < CURDATE() OR (ss.session_date = CURDATE() AND ss.end_time < CURTIME()))
        ORDER BY ss.session_date DESC, ss.start_time DESC
        LIMIT 5";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $groupId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
while ($row = mysqli_fetch_assoc($result)) {
    $pastSessions[] = $row;
}

// Set page title
$pageTitle = 'Schedule Sessions - ' . $group['name'];

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
                    <h1 class="text-xl font-bold"><?php echo htmlspecialchars($group['name']); ?> - Study Sessions</h1>
                    <p class="text-sm text-gray-600"><?php echo htmlspecialchars($group['description']); ?></p>
                </div>
            </div>
            <a href="group_chat.php?id=<?php echo $groupId; ?>" class="bg-gray-100 text-gray-600 px-4 py-2 rounded-lg hover:bg-gray-200">
                <i class="fas fa-arrow-left mr-2"></i> Back to Chat
            </a>
        </div>
    </div>
    
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Schedule Session Form -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">Schedule a Study Session</h2>
            
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
            
            <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . '?id=' . $groupId); ?>">
                <input type="hidden" name="action" value="schedule_session">
                
                <div class="mb-4">
                    <label for="title" class="block text-gray-700 mb-1">Session Title *</label>
                    <input type="text" id="title" name="title" class="w-full px-4 py-2 border rounded-lg" required
                           placeholder="E.g., Midterm Review, Problem Solving">
                </div>
                
                <div class="mb-4">
                    <label for="description" class="block text-gray-700 mb-1">Description</label>
                    <textarea id="description" name="description" rows="2" class="w-full px-4 py-2 border rounded-lg"
                             placeholder="What will you cover in this session?"></textarea>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label for="session_date" class="block text-gray-700 mb-1">Date *</label>
                        <input type="date" id="session_date" name="session_date" class="w-full px-4 py-2 border rounded-lg" 
                               required min="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <div>
                        <label for="start_time" class="block text-gray-700 mb-1">Start Time *</label>
                        <input type="time" id="start_time" name="start_time" class="w-full px-4 py-2 border rounded-lg" required>
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label for="end_time" class="block text-gray-700 mb-1">End Time *</label>
                        <input type="time" id="end_time" name="end_time" class="w-full px-4 py-2 border rounded-lg" required>
                    </div>
                    <div>
                        <label class="block text-gray-700 mb-1">Session Type</label>
                        <div class="flex items-center mt-2">
                            <input type="checkbox" id="is_online" name="is_online" class="mr-2" checked>
                            <label for="is_online">Online Session</label>
                        </div>
                    </div>
                </div>
                
                <div id="location_container" class="mb-4 hidden">
                    <label for="location" class="block text-gray-700 mb-1">Location</label>
                    <input type="text" id="location" name="location" class="w-full px-4 py-2 border rounded-lg"
                           placeholder="E.g., Library, Room 204, Campus Center">
                </div>
                
                <div id="meeting_link_container" class="mb-4">
                    <label for="meeting_link" class="block text-gray-700 mb-1">Meeting Link</label>
                    <input type="url" id="meeting_link" name="meeting_link" class="w-full px-4 py-2 border rounded-lg"
                           placeholder="Zoom, Google Meet, or other video call link">
                </div>
                
                <button type="submit" class="w-full bg-blue-600 text-white py-2 rounded-lg hover:bg-blue-700">Schedule Session</button>
            </form>
        </div>
        
        <!-- Upcoming Sessions -->
        <div class="md:col-span-2">
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-semibold text-gray-800 mb-4">Upcoming Study Sessions</h2>
                
                <?php if (empty($upcomingSessions)): ?>
                    <!-- Display message if no upcoming sessions -->
                    <div class="text-center py-8 text-gray-500">
                        <i class="fas fa-calendar-alt text-6xl mb-4"></i>
                        <p>No upcoming study sessions scheduled for this group.</p>
                        <p class="mt-2">Be the first to schedule a session!</p>
                    </div>
                <?php else: ?>
                    <!-- Display upcoming sessions -->
                    <div class="space-y-6">
                        <?php foreach ($upcomingSessions as $session): ?>
                            <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition">
                                <div class="flex items-start">
                                    <div class="bg-blue-100 text-blue-800 p-3 rounded-lg mr-4 text-center min-w-[70px]">
                                        <span class="block text-xl font-bold"><?php echo date('d', strtotime($session['session_date'])); ?></span>
                                        <span class="block text-sm"><?php echo date('M', strtotime($session['session_date'])); ?></span>
                                    </div>
                                    <div class="flex-1">
                                        <h3 class="text-lg font-bold"><?php echo htmlspecialchars($session['title']); ?></h3>
                                        <p class="text-gray-600 text-sm"><?php echo htmlspecialchars($session['description']); ?></p>
                                        
                                        <div class="flex flex-wrap gap-3 mt-2">
                                            <span class="flex items-center text-sm text-gray-600">
                                                <i class="far fa-clock mr-1"></i>
                                                <?php echo date('g:i A', strtotime($session['start_time'])); ?> - 
                                                <?php echo date('g:i A', strtotime($session['end_time'])); ?>
                                            </span>
                                            
                                            <span class="flex items-center text-sm text-gray-600">
                                                <i class="fas <?php echo $session['is_online'] ? 'fa-video' : 'fa-map-marker-alt'; ?> mr-1"></i>
                                                <?php if ($session['is_online']): ?>
                                                    Online
                                                    <?php if (!empty($session['meeting_link'])): ?>
                                                        <a href="<?php echo htmlspecialchars($session['meeting_link']); ?>" target="_blank" class="ml-1 text-blue-600 hover:underline">
                                                            (Join)
                                                        </a>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <?php echo htmlspecialchars($session['location']); ?>
                                                <?php endif; ?>
                                            </span>
                                            
                                            <span class="flex items-center text-sm text-gray-600">
                                                <i class="fas fa-user mr-1"></i>
                                                Created by <?php echo htmlspecialchars($session['creator_name']); ?>
                                            </span>
                                            
                                            <span class="flex items-center text-sm text-gray-600">
                                                <i class="fas fa-users mr-1"></i>
                                                <?php echo $session['attending_count']; ?> attending
                                            </span>
                                        </div>
                                    </div>
                                    <div>
                                        <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . '?id=' . $groupId); ?>">
                                            <input type="hidden" name="action" value="update_status">
                                            <input type="hidden" name="session_id" value="<?php echo $session['id']; ?>">
                                            
                                            <select name="status" onchange="this.form.submit()" class="px-3 py-1 border rounded-lg bg-white">
                                                <option value="attending" <?php echo $session['user_status'] === 'attending' ? 'selected' : ''; ?>>Attending</option>
                                                <option value="maybe" <?php echo $session['user_status'] === 'maybe' ? 'selected' : ''; ?>>Maybe</option>
                                                <option value="declined" <?php echo $session['user_status'] === 'declined' ? 'selected' : ''; ?>>Decline</option>
                                            </select>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($pastSessions)): ?>
                    <h3 class="text-lg font-semibold text-gray-700 mt-8 mb-4">Past Sessions</h3>
                    <div class="space-y-4">
                        <?php foreach ($pastSessions as $session): ?>
                            <div class="border border-gray-200 rounded-lg p-3 bg-gray-50">
                                <div class="flex items-center">
                                    <div class="bg-gray-200 text-gray-700 p-2 rounded-lg mr-3 text-center min-w-[60px]">
                                        <span class="block text-sm font-medium"><?php echo date('M d', strtotime($session['session_date'])); ?></span>
                                    </div>
                                    <div class="flex-1">
                                        <h4 class="font-medium"><?php echo htmlspecialchars($session['title']); ?></h4>
                                        <div class="flex flex-wrap gap-2 text-xs text-gray-500">
                                            <span><?php echo date('g:i A', strtotime($session['start_time'])); ?> - 
                                                  <?php echo date('g:i A', strtotime($session['end_time'])); ?></span>
                                            <span>•</span>
                                            <span><?php echo $session['attending_count']; ?> attended</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const isOnlineCheckbox = document.getElementById('is_online');
    const locationContainer = document.getElementById('location_container');
    const meetingLinkContainer = document.getElementById('meeting_link_container');
    
    // Toggle between online and in-person fields
    function toggleSessionType() {
        if (isOnlineCheckbox.checked) {
            locationContainer.classList.add('hidden');
            meetingLinkContainer.classList.remove('hidden');
        } else {
            locationContainer.classList.remove('hidden');
            meetingLinkContainer.classList.add('hidden');
        }
    }
    
    // Initial setup
    toggleSessionType();
    
    // Listen for changes
    isOnlineCheckbox.addEventListener('change', toggleSessionType);
});
</script>

<?php
// Include footer
include 'includes/footer.php';

// Close database connection
mysqli_close($conn);
?> 