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

// Get group members
$members = [];
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

// Set page title
$pageTitle = 'Voice Call - ' . $group['name'];

// Custom styles for voice call page
$extraStyles = '
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
    .voice-avatar {
        width: 100px;
        height: 100px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2.5rem;
        color: white;
        margin: 0 auto 1rem auto;
    }
    .voice-controls {
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
    .participant {
        position: relative;
    }
    .speaking-indicator {
        position: absolute;
        top: -5px;
        right: -5px;
        width: 15px;
        height: 15px;
        border-radius: 50%;
        background-color: #10B981;
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
                    <h1 class="text-xl font-bold"><?php echo htmlspecialchars($group['name']); ?> - Voice Call</h1>
                    <p class="text-sm text-gray-600"><?php echo htmlspecialchars($group['description']); ?></p>
                </div>
            </div>
            <div class="text-center bg-green-100 text-green-800 px-4 py-2 rounded-lg">
                <span id="call-timer">00:00</span>
            </div>
            <a href="group_chat.php?id=<?php echo $groupId; ?>" class="bg-gray-100 text-gray-600 px-4 py-2 rounded-lg hover:bg-gray-200">
                <i class="fas fa-arrow-left mr-2"></i> Back to Chat
            </a>
        </div>
    </div>
    
    <!-- Voice Call Participants -->
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6 mb-20">
        <!-- Your avatar -->
        <div class="text-center bg-white p-6 rounded-lg shadow-md participant" id="local-participant">
            <div class="voice-avatar bg-green-500" id="user-avatar">
                <?php echo substr($_SESSION['user_name'], 0, 1); ?>
            </div>
            <h3 class="font-semibold"><?php echo htmlspecialchars($_SESSION['user_name']); ?> (You)</h3>
            <p class="text-sm text-green-500">Connected</p>
        </div>
        
        <?php if (empty($members)): ?>
            <!-- Sample members if no members in database -->
            <div class="text-center bg-white p-6 rounded-lg shadow-md participant">
                <div class="voice-avatar bg-blue-500">J</div>
                <h3 class="font-semibold">John Doe</h3>
                <p class="text-sm text-green-500">Connected</p>
                <div class="speaking-indicator"></div>
            </div>
            
            <div class="text-center bg-white p-6 rounded-lg shadow-md participant">
                <div class="voice-avatar bg-purple-500">S</div>
                <h3 class="font-semibold">Sarah Johnson</h3>
                <p class="text-sm text-gray-500">Connecting...</p>
            </div>
        <?php else: ?>
            <!-- Display members from database -->
            <?php foreach ($members as $member): ?>
                <?php if ($member['id'] != $_SESSION['user_id']): ?>
                    <div class="text-center bg-white p-6 rounded-lg shadow-md participant">
                        <div class="voice-avatar bg-blue-500">
                            <?php echo substr($member['name'], 0, 1); ?>
                        </div>
                        <h3 class="font-semibold"><?php echo htmlspecialchars($member['name']); ?></h3>
                        <p class="text-sm text-gray-500">Waiting to join...</p>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    
    <!-- Voice Call Controls -->
    <div class="voice-controls flex items-center bg-white p-3 rounded-full shadow-lg">
        <button class="control-btn bg-gray-100 text-gray-700 hover:bg-gray-200" id="toggle-audio" title="Toggle Microphone">
            <i class="fas fa-microphone"></i>
        </button>
        <button class="control-btn bg-gray-100 text-gray-700 hover:bg-gray-200" id="toggle-speaker" title="Toggle Speaker">
            <i class="fas fa-volume-up"></i>
        </button>
        <button class="control-btn bg-red-600 text-white hover:bg-red-700" id="end-call" title="End Call">
            <i class="fas fa-phone-slash"></i>
        </button>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Get HTML elements
    const toggleAudioBtn = document.getElementById('toggle-audio');
    const toggleSpeakerBtn = document.getElementById('toggle-speaker');
    const endCallBtn = document.getElementById('end-call');
    const callTimer = document.getElementById('call-timer');
    const userAvatar = document.getElementById('user-avatar');
    
    // Variables to track states
    let localStream;
    let isAudioOn = true;
    let isSpeakerOn = true;
    let callStartTime = new Date();
    let timerInterval;
    
    // Initialize microphone
    async function initMicrophone() {
        try {
            // Request access to microphone
            localStream = await navigator.mediaDevices.getUserMedia({
                audio: true
            });
            
            console.log('Microphone initialized successfully');
            
            // Create audio context for visualization
            const audioContext = new (window.AudioContext || window.webkitAudioContext)();
            const analyser = audioContext.createAnalyser();
            const microphone = audioContext.createMediaStreamSource(localStream);
            microphone.connect(analyser);
            analyser.fftSize = 256;
            
            const bufferLength = analyser.frequencyBinCount;
            const dataArray = new Uint8Array(bufferLength);
            
            function checkAudioLevel() {
                analyser.getByteFrequencyData(dataArray);
                
                // Calculate average volume level
                let sum = 0;
                for (let i = 0; i < bufferLength; i++) {
                    sum += dataArray[i];
                }
                const average = sum / bufferLength;
                
                // If speaking (volume above threshold), add pulsing effect to avatar
                if (average > 20 && isAudioOn) {
                    userAvatar.classList.add('ring-4', 'ring-green-300', 'ring-opacity-75');
                } else {
                    userAvatar.classList.remove('ring-4', 'ring-green-300', 'ring-opacity-75');
                }
                
                requestAnimationFrame(checkAudioLevel);
            }
            
            checkAudioLevel();
            
        } catch (error) {
            console.error('Error accessing microphone:', error);
            alert('Unable to access your microphone. Please grant permission and reload the page.');
        }
    }
    
    // Update call timer
    function updateCallTimer() {
        const now = new Date();
        const diff = new Date(now - callStartTime);
        const minutes = diff.getUTCMinutes().toString().padStart(2, '0');
        const seconds = diff.getUTCSeconds().toString().padStart(2, '0');
        callTimer.textContent = `${minutes}:${seconds}`;
    }
    
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
                
                // Add/remove muted indicator
                if (!isAudioOn) {
                    const mutedIndicator = document.createElement('div');
                    mutedIndicator.id = 'muted-indicator';
                    mutedIndicator.className = 'absolute -top-2 -right-2 bg-red-500 text-white rounded-full p-1';
                    mutedIndicator.innerHTML = '<i class="fas fa-microphone-slash text-xs"></i>';
                    document.getElementById('local-participant').appendChild(mutedIndicator);
                } else {
                    const mutedIndicator = document.getElementById('muted-indicator');
                    if (mutedIndicator) mutedIndicator.remove();
                }
            }
        }
    });
    
    // Toggle speaker
    toggleSpeakerBtn.addEventListener('click', function() {
        isSpeakerOn = !isSpeakerOn;
        toggleSpeakerBtn.innerHTML = isSpeakerOn ? 
            '<i class="fas fa-volume-up"></i>' : 
            '<i class="fas fa-volume-mute"></i>';
            
        // In a real app, this would control audio output device
    });
    
    // End call
    endCallBtn.addEventListener('click', function() {
        // Stop all tracks
        if (localStream) {
            localStream.getTracks().forEach(track => track.stop());
        }
        
        // Clear timer
        clearInterval(timerInterval);
        
        // Redirect back to group chat
        window.location.href = 'group_chat.php?id=<?php echo $groupId; ?>';
    });
    
    // Simulate someone speaking (John in this demo)
    function simulateSpeaking() {
        const participants = document.querySelectorAll('.participant:not(#local-participant)');
        if (participants.length > 0) {
            const randomIndex = Math.floor(Math.random() * participants.length);
            const randomParticipant = participants[randomIndex];
            
            // Add speaking effect
            const avatar = randomParticipant.querySelector('.voice-avatar');
            if (avatar) {
                avatar.classList.add('ring-4', 'ring-green-300', 'ring-opacity-75');
                
                // Remove after random time between 1-3 seconds
                setTimeout(() => {
                    avatar.classList.remove('ring-4', 'ring-green-300', 'ring-opacity-75');
                }, Math.random() * 2000 + 1000);
            }
        }
    }
    
    // Initialize call
    initMicrophone();
    
    // Start call timer
    timerInterval = setInterval(updateCallTimer, 1000);
    
    // Simulate periodic speaking from other participants
    setInterval(simulateSpeaking, 5000);
});
</script>

<?php
// Include footer
include 'includes/footer.php';

// Close database connection
mysqli_close($conn);
?> 