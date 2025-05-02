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

// Set page title
$pageTitle = 'Video Call - ' . $group['name'];

// Custom styles for video call page
$extraStyles = '
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
    .video-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 1rem;
    }
    .video-item {
        aspect-ratio: 16/9;
        background-color: #2d3748;
        border-radius: 0.5rem;
        overflow: hidden;
        position: relative;
    }
    .video-controls {
        position: fixed;
        bottom: 1rem;
        left: 50%;
        transform: translateX(-50%);
        z-index: 10;
    }
    .control-btn {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 0.5rem;
    }
</style>
';

// Include header
include 'includes/header.php';
?>

<div class="container mx-auto py-4 px-4">
    <div class="bg-white rounded-lg shadow-lg p-4 mb-4">
        <div class="flex justify-between items-center">
            <div class="flex items-center">
                <div class="rounded-full bg-blue-600 h-10 w-10 flex items-center justify-center text-white font-bold text-lg mr-3">
                    <?php echo substr($group['name'], 0, 1); ?>
                </div>
                <div>
                    <h1 class="text-xl font-bold"><?php echo htmlspecialchars($group['name']); ?> - Video Call</h1>
                    <p class="text-sm text-gray-600"><?php echo htmlspecialchars($group['description']); ?></p>
                </div>
            </div>
            <a href="group_chat.php?id=<?php echo $groupId; ?>" class="bg-gray-100 text-gray-600 px-4 py-2 rounded-lg hover:bg-gray-200">
                <i class="fas fa-arrow-left mr-2"></i> Back to Chat
            </a>
        </div>
    </div>
    
    <!-- Video Grid -->
    <div class="video-grid mb-20">
        <!-- Main Video (your camera) -->
        <div class="video-item relative" id="local-video">
            <div class="absolute bottom-2 left-2 bg-gray-800 bg-opacity-70 text-white px-2 py-1 rounded-lg text-sm">
                You (<?php echo htmlspecialchars($_SESSION['user_name']); ?>)
            </div>
        </div>
        
        <!-- Peer Videos -->
        <div class="video-item relative" id="peer-video-1">
            <div class="absolute bottom-2 left-2 bg-gray-800 bg-opacity-70 text-white px-2 py-1 rounded-lg text-sm">
                ADMIN
            </div>
            <div class="absolute inset-0 flex items-center justify-center text-white">
                <div class="text-center">
                    <div class="rounded-full bg-blue-600 h-20 w-20 flex items-center justify-center text-white font-bold text-3xl mx-auto mb-2">
                        J
                    </div>
                    <p>Camera off</p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Video Call Controls -->
    <div class="video-controls flex items-center bg-white p-3 rounded-full shadow-lg">
        <button class="control-btn bg-gray-100 text-gray-700 hover:bg-gray-200" id="toggle-video" title="Toggle Video">
            <i class="fas fa-video"></i>
        </button>
        <button class="control-btn bg-gray-100 text-gray-700 hover:bg-gray-200" id="toggle-audio" title="Toggle Audio">
            <i class="fas fa-microphone"></i>
        </button>
        <button class="control-btn bg-gray-100 text-gray-700 hover:bg-gray-200" id="share-screen" title="Share Screen">
            <i class="fas fa-desktop"></i>
        </button>
        <button class="control-btn bg-red-600 text-white hover:bg-red-700" id="end-call" title="End Call">
            <i class="fas fa-phone-slash"></i>
        </button>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Get HTML elements
    const localVideo = document.getElementById('local-video');
    const toggleVideoBtn = document.getElementById('toggle-video');
    const toggleAudioBtn = document.getElementById('toggle-audio');
    const shareScreenBtn = document.getElementById('share-screen');
    const endCallBtn = document.getElementById('end-call');
    
    // Variables to track states
    let localStream;
    let isVideoOn = true;
    let isAudioOn = true;
    
    // Initialize webcam
    async function initWebcam() {
        try {
            // Request access to camera and microphone
            localStream = await navigator.mediaDevices.getUserMedia({
                video: true,
                audio: true
            });
            
            // Create video element for local stream
            const videoElement = document.createElement('video');
            videoElement.srcObject = localStream;
            videoElement.autoplay = true;
            videoElement.muted = true; // Mute local video to prevent feedback
            videoElement.style.width = '100%';
            videoElement.style.height = '100%';
            videoElement.style.objectFit = 'cover';
            
            // Add video element to container
            localVideo.appendChild(videoElement);
            
            console.log('Webcam initialized successfully');
        } catch (error) {
            console.error('Error accessing media devices:', error);
            localVideo.innerHTML = `
                <div class="absolute inset-0 flex items-center justify-center text-white">
                    <div class="text-center">
                        <div class="rounded-full bg-green-500 h-20 w-20 flex items-center justify-center text-white font-bold text-3xl mx-auto mb-2">
                            ${getUserInitial()}
                        </div>
                        <p>Camera access denied</p>
                    </div>
                </div>
            `;
        }
    }
    
    // Get user initial
    function getUserInitial() {
        const userName = '<?php echo $_SESSION['user_name']; ?>';
        return userName.charAt(0).toUpperCase();
    }
    
    // Toggle video
    toggleVideoBtn.addEventListener('click', function() {
        if (localStream) {
            const videoTracks = localStream.getVideoTracks();
            if (videoTracks.length > 0) {
                isVideoOn = !isVideoOn;
                videoTracks[0].enabled = isVideoOn;
                toggleVideoBtn.innerHTML = isVideoOn ? 
                    '<i class="fas fa-video"></i>' : 
                    '<i class="fas fa-video-slash"></i>';
                
                if (!isVideoOn) {
                    localVideo.innerHTML += `
                        <div class="absolute inset-0 flex items-center justify-center text-white" id="camera-off-overlay">
                            <div class="text-center">
                                <div class="rounded-full bg-green-500 h-20 w-20 flex items-center justify-center text-white font-bold text-3xl mx-auto mb-2">
                                    ${getUserInitial()}
                                </div>
                                <p>Camera off</p>
                            </div>
                        </div>
                    `;
                } else {
                    const overlay = document.getElementById('camera-off-overlay');
                    if (overlay) overlay.remove();
                }
            }
        }
    });
    
    // Toggle audio
    toggleAudioBtn.addEventListener('click', function() {
        if (localStream) {
            const audioTracks = localStream.getAudioTracks();
            if (audioTracks.length > 0) {
                isAudioOn = !isAudioOn;
                audioTracks[0].enabled = isAudioOn;
                toggleAudioBtn.innerHTML = isAudioOn ? 
                    '<i class="fas fa-microphone"></i>' : 
                    '<i class="fas fa-microphone-slash"></i>';
            }
        }
    });
    
    // Share screen
    shareScreenBtn.addEventListener('click', async function() {
        try {
            const screenStream = await navigator.mediaDevices.getDisplayMedia({
                video: true
            });
            
            // Replace camera with screen
            if (localStream) {
                const videoTracks = localStream.getVideoTracks();
                if (videoTracks.length > 0) {
                    localStream.removeTrack(videoTracks[0]);
                }
                screenStream.getVideoTracks()[0].onended = function() {
                    initWebcam(); // Reinitialize webcam when screen sharing ends
                };
                localStream.addTrack(screenStream.getVideoTracks()[0]);
                
                // Update video element
                const videoElement = localVideo.querySelector('video');
                if (videoElement) {
                    videoElement.srcObject = localStream;
                }
            }
        } catch (error) {
            console.error('Error sharing screen:', error);
        }
    });
    
    // End call
    endCallBtn.addEventListener('click', function() {
        // Stop all tracks
        if (localStream) {
            localStream.getTracks().forEach(track => track.stop());
        }
        
        // Redirect back to group chat
        window.location.href = 'group_chat.php?id=<?php echo $groupId; ?>';
    });
    
    // Initialize webcam on page load
    initWebcam();
});
</script>

<?php
// Include footer
include 'includes/footer.php';

// Close database connection
mysqli_close($conn);
?> 