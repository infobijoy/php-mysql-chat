<?php
session_start();
include './include/config-db.php';

if (!isset($_SESSION['user_id'])) {
    include './include/auto-login.php';
    // Check again after auto-login attempt
    if (!isset($_SESSION['user_id'])) {
        // Auto-login failed, redirect to login page
        header('Location: ./log-in.php');
        exit;
    }
  }
  require_once './include/auth-middleware.php';
// Check if user is logged in
$isLoggedIn = isset($_SESSION['user_id']);
$openchat ='';
$selectedUserId = $_GET['user_id'] ?? null;
// If auto-login is enabled and user isn't logged in, try auto-login
if (!$isLoggedIn) {
    include './include/auto-login.php';
    $isLoggedIn = isset($_SESSION['user_id']);
}
$loggedInUserId = $_SESSION['user_id'];
// Fetch user data if logged in
// Fetch selected user data from the database
$query = "SELECT id, username, email, password_hash, display_name, profile_picture, status, created_at, updated_at FROM users WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $selectedUserId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
    $senderPhoto = $user['profile_picture'] ?: "default.jpg";
    $userName = $user['display_name'] ?: $user['username'];
    $lastSeen = $user['status'] ?: "Last Seen";
    $openchat = 1;
} else {
    //die("User not found.");
}

if ($isLoggedIn) {
    $stmt = $conn->prepare("SELECT username, display_name, profile_picture, status, status FROM users WHERE id = ?");
    $stmt->bind_param('i', $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $userSelf = $result->fetch_assoc();
    $selfPhoto = $userSelf['profile_picture'] ?: "default.jpg";
    $selfName = $userSelf['display_name'] ?: $userSelf['username'];
    $selfLastSeen = $userSelf['status'] ?: "Last Seen";
}
include './include/header.php'; 
$greeting='Thanks for useing';
$currentHour = date('H');
// Determine greeting based on time
if ($currentHour >= 5 && $currentHour < 12) {
    $greeting = "Good Morning";
} elseif ($currentHour >= 12 && $currentHour < 17) {
    $greeting = "Good Afternoon";
} elseif ($currentHour >= 17 && $currentHour < 20) {
    $greeting = "Good Evening";
} else {
    $greeting = "Good Night";
}
?>
<style>
    .messenger-gradient {
        background: linear-gradient(135deg, #3a7bd5 0%, #00d2ff 100%);
    }
    .chat-container {
        height: calc(100vh - 80px);
    }
    .conversation-card:hover {
        background-color: rgba(255,255,255,0.1);
        transform: translateX(5px);
    }
    .active-conversation {
        background-color: rgba(255,255,255,0.15);
        border-left: 3px solid white;
    }
    .message-input {
        backdrop-filter: blur(10px);
    }
    .status-dot {
        width: 12px;
        height: 12px;
        border: 2px solid #3a7bd5;
    }
    .online { background-color: #2ecc71; }
    .few-s-m-h-ago { background-color: #f39c12; }
    .offline { background-color: #95a5a6; }
    .typing-indicator span {
        animation: bounce 1.5s infinite ease-in-out;
    }
    .typing-indicator span:nth-child(2) {
        animation-delay: 0.2s;
    }
    .typing-indicator span:nth-child(3) {
        animation-delay: 0.4s;
    }
    .reply-preview {
    display: none;
    background: rgba(255, 255, 255, 0.1);
    border-left: 3px solid #3b82f6;
    padding: 8px 12px;
    margin-bottom: 8px;
    border-radius: 8px;
    position: relative;
}

.reply-preview .close-reply {
    position: absolute;
    right: 8px;
    top: 8px;
    cursor: pointer;
    opacity: 0.7;
}

.replying-to-label {
    font-size: 0.75rem;
    opacity: 0.7;
    margin-bottom: 4px;
}
    @keyframes bounce {
        0%, 60%, 100% { transform: translateY(0); }
        30% { transform: translateY(-5px); }
    }
    .seen-time-tooltip {
    transform: translateX(-50%);
    left: 50%;
}

.seen-time-tooltip:after {
    content: '';
    position: absolute;
    top: 100%;
    left: 50%;
    margin-left: -5px;
    border-width: 5px;
    border-style: solid;
    border-color: black transparent transparent transparent;
}
.mobile-head {
    position: fixed;
    z-index: 53;
    width: 255px;
    background: linear-gradient(45deg, #2992ff, #15cced);
    padding: 12px 12px !important;
    display: flex;
    align-items: center;
}
div#user-list {
    padding-top: 65px;
}
.replay-text {
    border-radius: 6px;
    padding: 4px 5px 4px 2px;
    background: #5cd1ff75;
}
#menu-toggle {
    z-index: 60;
}
.mobile-ms {
        margin-left: 7px;
    }
    @media (max-width:767px) {
    div#user-list {
        padding-top: 73px;
        z-index: 49;
        background: linear-gradient(45deg, #06e2ff, #3f5ff9);
    }
    .mobile-head {
        max-width: 100vw;
    }
    div#chat-box {
        max-width: 100vw;
    }
    .mobile-ms {
        margin-left: 48px;
    }
    }
</style>

<?php if ($isLoggedIn): ?>
<!-- Pure Messenger Interface -->
<div class="flex flex-col h-screen messenger-gradient text-white">
    <!-- Main Content -->
    <div class="flex flex-1 overflow-hidden">
    <div class="mobile-head px-4 fixed left-0 transform -translate-x-full transition-transform duration-300 ease-in-out md:translate-x-0 border-b border-white border-opacity-10">
    <div class="avatar relative mobile-ms">
        <div class="w-12 rounded-full">
            <img class="self-img" src="./profile-photo/<?php echo $selfPhoto;?>" alt="Profile" />
        </div>
    </div>
    <div class="ms-4 overflow-hidden">
        <h2 class="font-semibold truncate"><?php echo $selfName;?></h2>
        <p class="text-xs opacity-70 truncate"><?php echo $greeting;?></p>
    </div>
</div>

<!-- Sidebar User List -->
<div id="user-list" class="w-64 bg-white bg-opacity-10 border-r border-white border-opacity-10 overflow-y-auto fixed inset-y-0 left-0 transform -translate-x-full transition-transform duration-300 ease-in-out md:relative md:translate-x-0">
</div>

        <!-- Chat Area -->
        <div class="flex flex-col flex-1">
            <!-- Chat Header -->
            <?php
if ($openchat) {
    echo '
    <div class="flex items-center justify-between p-4 border-b border-white border-opacity-20">
        <div class="flex items-center space-x-3">
            <button id="menu-toggle" class="p-2 z-50 rounded-full hover:bg-white hover:bg-opacity-10 md:hidden">
                <i class="fas fa-bars"></i>
            </button>
            <div class="avatar relative">
                <div class="w-10 rounded-full">
                    <img src="./profile-photo/'.$senderPhoto.'" alt="User" />
                </div>
                <span id="online-offline-status" class="status-dot offline rounded-full absolute bottom-0 right-0"></span>
            </div>
            <div>
                <h2 class="font-semibold">'.$userName.'</h2>
                <p class="flex">
                    <span id="last-seen" class="text-xs opacity-70">Loading...</span>
                    <span id="typing-status" class="text-xs opacity-70 ms-3"></span>
                </p>
            </div>
        </div>
        <div class="flex space-x-4">
            <!----<button class="p-2 rounded-full hover:bg-white hover:bg-opacity-10">
                <i class="fas fa-phone"></i>
            </button>
            <button class="p-2 rounded-full hover:bg-white hover:bg-opacity-10">
                <i class="fas fa-video"></i>
            </button>---->
            <button class="p-2 rounded-full hover:bg-white hover:bg-opacity-10">
                <i class="fas fa-info-circle"></i>
            </button>
        </div>
    </div>

    <!-- Messages -->
    <div id="chat-box" class="flex-1 p-4 overflow-y-auto space-y-3">
    </div>

    <!-- Message Input -->
    <div class="p-4 border-t border-white border-opacity-20 message-input">
    <div id="reply-preview" class="reply-preview">
        <div class="replying-to-label">Replying to: <span id="replying-to-text"></span></div>
        <div id="reply-message-content" class="truncate"></div>
        <span class="close-reply">&times;</span>
        <input type="hidden" id="replying-to-id">
    </div>
    <div class="flex items-center space-x-2">
        <input type="text" id="type-message" placeholder="Type a message" 
               class="flex-1 bg-white bg-opacity-20 rounded-full px-4 py-2 border-none placeholder-white placeholder-opacity-70">
        <button id="send-button" class="p-2 rounded-full bg-blue-500 text-white">
            <i class="fas fa-paper-plane"></i>
        </button>
    </div>
</div>
    
<button id="goToBottomButton" class="fixed bottom-20 px-4 right-7 z-50 bg-blue-500 text-white p-2 rounded-full shadow-lg hover:bg-blue-600">
    <i class="fa-solid fa-arrow-down"></i>
</button>
';
} else {
    echo '
    <div class="flex flex-col items-center justify-center h-full text-center p-8">
        <button id="menu-toggle" class="fixed top-4 left-4 p-2 z-50 rounded-full hover:bg-white hover:bg-opacity-10 md:hidden">
            <i class="fas fa-bars"></i>
        </button>
        <div class="w-32 h-32 bg-white bg-opacity-10 rounded-full flex items-center justify-center mb-4">
            <i class="fas fa-comments text-4xl text-white text-opacity-50"></i>
        </div>
        <h2 class="text-2xl font-semibold mb-2">Select a user to chat</h2>
        <p class="text-white text-opacity-70 max-w-md">
            Choose a contact from your list to start messaging. Your conversations will appear here.
        </p>
        <button class="mt-6 px-6 py-2 bg-blue-500 text-white rounded-full hover:bg-blue-600 transition">
            <i class="fas fa-user-plus mr-2"></i> Add New Contact
        </button>
    </div>';
}
?>
        </div>
    </div>
</div>

<script>
document.getElementById("menu-toggle").addEventListener("click", function() {
    // Toggle user-list
    let userList = document.getElementById("user-list");
    userList.classList.toggle("-translate-x-full");
    
    // Toggle mobile-head
    let mobileHead = document.querySelector(".mobile-head");
    mobileHead.classList.toggle("-translate-x-full");
});
</script>


<?php else: ?>
    <!-- Guest view remains unchanged -->
    <div class="hero-bg text-white">
        <!-- ... existing guest content ... -->
    </div>
<?php endif; ?>
    <script>
    $(document).ready(function() {
        // More efficient refresh using AJAX to fetch only user list
        function refreshUserList() {
            $.ajax({
                url: './ajex/user-list.php', // Create a separate endpoint for this
                type: 'GET',
                dataType: 'html',
                success: function(data) {
                    $('#user-list').html(data);
                },
                error: function(xhr, status, error) {
                    console.error('Error refreshing user list:', error);
                },
                complete: function() {
                    setTimeout(refreshUserList, 1000);
                }
            });
        }
        
        // Initial call
        refreshUserList();
    });
    </script>
<script>
const goToBottomButton = document.getElementById('goToBottomButton');
const messagesList = document.getElementById('chat-box');
let previousMessages = [];
let initialLoad = true;
let lastMessageId = 0;
let isScrolledUp = false;
let selectedUserId = '<?php echo $selectedUserId;?>';

$(document).ready(function() {
    const scrollToBottom = () => {
        if (!messagesList) return;
        messagesList.scrollTo({
            top: messagesList.scrollHeight,
            behavior: 'smooth',
        });
        isScrolledUp = false;
        if (goToBottomButton) {
            goToBottomButton.classList.add('hidden');
        }
        markMessagesAsSeen();
    };
    // Function to mark messages as seen
const markMessagesAsSeen = async () => {
    try {
        const response = await fetch(`./ajex/mark-as-seen.php?user_id=${selectedUserId}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ user_id: <?= $_SESSION['user_id'] ?> })
        });
        const result = await response.json();
        if (result.success) {
            // Update seen status in UI
            document.querySelectorAll('.message-item').forEach(item => {
                if (item.dataset.senderId == selectedUserId && !item.dataset.seen) {
                    const footer = item.querySelector('.chat-footer');
                    if (footer) {
                        footer.innerHTML = `
                            <span class="text-blue-500">Seen</span>
                            <time class="text-xs opacity-50">${formatTimestamp(result.seen_at)}</time>
                        `;
                        item.dataset.seen = 'true';
                    }
                }
            });
        }
    } catch (error) {
        console.error('Error marking messages as seen:', error);
    }
};
    scrollToBottom();

    function loadChatMessages(userId, update = false) {
        if (!userId) return;
        if (!update) {
            $('#chat-box').html('<p class="text-center opacity-70 py-10">Loading...</p>');
        }
        $.ajax({
            url: './ajex/get-chat.php',
            type: 'GET',
            data: { user_id: userId },
            dataType: 'json',
            success: function(response) {
                if (response.success && response.messages) {
                    displayMessages(response.messages);
                } else {
                    console.error('Error loading messages:', response.message);
                    $('#chat-box').html('<p class="text-center text-red-300 py-4">Error loading messages</p>');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error:', error);
                $('#chat-box').html('<p class="text-center text-red-300 py-4">Connection error</p>');
            }
        });
    }
    function displayMessages(messages) {
    if (JSON.stringify(messages) === JSON.stringify(previousMessages)) {
        return;
    }
    previousMessages = messages;
    const chatBox = $('#chat-box');
    chatBox.empty();

    if (messages.length === 0) {
        chatBox.html('<p class="text-center opacity-70 py-10">No messages yet. Start the conversation!</p>');
        return;
    }

    let currentDate = null;
    let hasNewMessages = false;

    messages.forEach(message => {
        if (message.id > lastMessageId) {
            hasNewMessages = true;
            lastMessageId = message.id;
        }
        const messageDate = new Date(message.created_at).toDateString();
        if (messageDate !== currentDate) {
            currentDate = messageDate;
            const dateHeader = formatDateHeader(message.created_at);
            chatBox.append(`<div class="text-center py-4 text-xs opacity-50">${dateHeader}</div>`);
        }
        const isCurrentUser = message.sender_id == <?= $_SESSION['user_id'] ?? 0 ?>;
        const messageClass = isCurrentUser ? 'justify-end' : 'justify-start';
        const bubbleClass = isCurrentUser ? 'bg-white text-blue-900' : 'bg-white bg-opacity-20';
        const messageTime = formatTime(message.created_at);
        
        // Status icon and tooltip container
        let statusIcon = '';
        let seenTimeTooltip = '';
        
        if (isCurrentUser) {
            if (message.is_seen) {
                const seenTime = message.seen_at ? formatTime(message.seen_at) : '';
                statusIcon = `<i class="fas fa-check-double text-blue-500 ml-1"></i>`;
                seenTimeTooltip = `<div class="seen-time-tooltip hidden absolute bg-black text-white text-xs px-2 py-1 rounded bottom-full mb-1 whitespace-nowrap">
                    Seen at ${seenTime}
                </div>`;
            } else {
                statusIcon = '<i class="fas fa-check text-gray-400 ml-1"></i>';
            }
        }
        
        // Check for replyid pattern
        const replyIdMatch = message.message.match(/replyid\((\d+)\)/i);
        let displayMessage = message.message;
        let replyHtml = '';
        
        if (replyIdMatch) {
            const replyId = replyIdMatch[1];
            // Find the original message being replied to
            const originalMessage = messages.find(m => m.id == replyId);
            
            if (originalMessage) {
                // Clean both the original message and current message
                const cleanOriginalMessage = originalMessage.message.replace(/replyid\(\d+\)/i, '').trim();
                displayMessage = message.message.replace(replyIdMatch[0], '').trim();
                
                // Create reply preview
                const originalSenderIsCurrent = originalMessage.sender_id == <?= $_SESSION['user_id'] ?? 0 ?>;
                const senderName = originalSenderIsCurrent ? 'You' : originalMessage.sender_name || '<?= $userName;?>';
                
                replyHtml = `
                    <div class="flex replay-text items-start mb-1 -mt-1">
                        <div class="w-1 h-8 ${isCurrentUser ? 'bg-blue-300' : 'bg-blue-400'} rounded-full mr-2"></div>
                        <div class="text-xs ${isCurrentUser ? 'text-blue-700' : 'text-gray-200'}">
                            <div class="font-medium">${senderName}</div>
                            <div class="truncate max-w-[180px]">${escapeHtml(cleanOriginalMessage)}</div>
                        </div>
                    </div>
                `;
            }
        }
        
        const messageHtml = `
            <div class="flex ${messageClass} mb-2" data-message-id="${message.id}">
                <div class="${bubbleClass} rounded-2xl p-3 max-w-[70%] relative">
                    ${replyHtml}
                    <p data-message-id="${message.id}">${escapeHtml(displayMessage)}</p>
                    <div class="status-container inline-flex items-center float-right mt-1 relative">
                        <span class="text-xs opacity-70">${messageTime}</span>
                        ${statusIcon}
                        ${seenTimeTooltip}
                    </div>
                </div>
            </div>
        `;
        chatBox.append(messageHtml);
    });

    // Add click event for seen time tooltip
    $('.status-container').on('click', function(e) {
        e.stopPropagation();
        $('.seen-time-tooltip').addClass('hidden');
        $(this).find('.seen-time-tooltip').toggleClass('hidden');
    });

    $(document).on('click', function() {
        $('.seen-time-tooltip').addClass('hidden');
    });
    
    if (initialLoad || !isScrolledUp) {
        scrollToBottom();
        initialLoad = false;
    } else if (hasNewMessages) {
        if (goToBottomButton) {
            goToBottomButton.classList.remove('hidden');
        }
    }
}

    if (goToBottomButton) {
        goToBottomButton.addEventListener('click', scrollToBottom);
    }

    const toggleGoToBottomButton = () => {
        if (!messagesList || !goToBottomButton) return;
        const scrollDistanceFromBottom = messagesList.scrollHeight - messagesList.scrollTop - messagesList.clientHeight;
        isScrolledUp = scrollDistanceFromBottom > 200;
        if (isScrolledUp) {
            goToBottomButton.classList.remove('hidden');
        } else {
            goToBottomButton.classList.add('hidden');
        }
    };

    if (messagesList) {
        messagesList.addEventListener('scroll', toggleGoToBottomButton);
    }

    $(document).on('click', '.conversation-card', function() {
        $('.conversation-card').removeClass('active-conversation');
        $(this).addClass('active-conversation');
        const userId = $(this).data('user-id');
        if (userId !== selectedUserId) {
            selectedUserId = userId;
            initialLoad = true;
            lastMessageId = 0;
            loadChatMessages(userId);
        }
    });
    loadChatMessages(selectedUserId, true);
    setInterval(function() {
        if (selectedUserId) {
            loadChatMessages(selectedUserId, true);
        }
    }, 1000);
    scrollToBottom();
    function formatDateHeader(dateString) {
    const date = new Date(dateString);
    const today = new Date();
    const yesterday = new Date(today);
    yesterday.setDate(yesterday.getDate() - 1);

    if (date.toDateString() === today.toDateString()) {
        return 'Today';
    }
    if (date.toDateString() === yesterday.toDateString()) {
        return 'Yesterday';
    }

    // For dates within the same week
    const daysDiff = Math.floor((today - date) / (1000 * 60 * 60 * 24));
    if (daysDiff < 7 && date.getFullYear() === today.getFullYear()) {
        return date.toLocaleDateString('en-US', { weekday: 'long' });
    }

    // Show full date if older
    return date.toLocaleDateString('en-US', { 
        weekday: 'long', 
        month: 'short', 
        day: 'numeric', 
        year: today.getFullYear() !== date.getFullYear() ? 'numeric' : undefined 
    });
}

function formatTime(dateString) {
    const date = new Date(dateString);
    const now = new Date();
    const diffInSeconds = Math.floor((now - date) / 1000);

    if (diffInSeconds < 60) {
        return `${diffInSeconds}s ago`;
    }

    const diffInMinutes = Math.floor(diffInSeconds / 60);
    if (diffInMinutes < 60) {
        return `${diffInMinutes}m ago`;
    }

    const diffInHours = Math.floor(diffInMinutes / 60);
    if (diffInHours < 24) {
        return `${diffInHours}h ago`;
    }

    // Show time if more than 23 hours
    return date.toLocaleTimeString('en-US', {
        hour: 'numeric',
        minute: '2-digit',
        hour12: true
    }).toLowerCase();
}

    function escapeHtml(text) {
        return text.replace(/</g, "&lt;").replace(/>/g, "&gt;");
    }
});


// Global variables
let replyingToId = null;

// Double click handler for messages
$(document).on('dblclick', '[data-message-id]', function() {
    const messageId = $(this).data('message-id');
    const messageContent = $(this).find('p[data-message-id]').text().trim();
    
    // Set the reply preview
    $('#replying-to-text').text(messageContent.substring(0, 30) + (messageContent.length > 30 ? '...' : ''));
    $('#reply-message-content').text(messageContent);
    $('#replying-to-id').val(messageId);
    $('.reply-preview').show();
    
    replyingToId = messageId;
    $('#type-message').focus();
});

// Close reply preview
$(document).on('click', '.close-reply', function() {
    $('.reply-preview').hide();
    replyingToId = null;
});

// Modified sendMessage function
function sendMessage() {
    const message = $('#type-message').val().trim();
    if (!message) return;
    
    const receiverId = selectedUserId;
    if (!receiverId) {
        alert("No user selected to send message.");
        return;
    }
    
    // Prepare message content
    let finalMessage = message;
    if (replyingToId) {
        finalMessage = `replyid(${replyingToId}) ${message}`;
    }
    
    $.ajax({
        url: './ajex/send-message.php',
        type: 'POST',
        contentType: 'application/json',
        data: JSON.stringify({
            receiver_id: receiverId,
            message: finalMessage
        }),
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                $('#type-message').val('');
                $('.reply-preview').hide();
                replyingToId = null;
                loadChatMessages(receiverId, true);
            } else {
                console.error("Error sending message:", response.message);
            }
        },
        error: function(xhr, status, error) {
            console.error("AJAX error:", error);
        }
    });
}

// Event listeners
$('#send-button').on('click', sendMessage);

$('#type-message').on('keypress', function(event) {
    if (event.which === 13 && !event.shiftKey) {
        event.preventDefault();
        sendMessage();
    }
});



    //Typeing status update on server
    let messageInput = document.getElementById('type-message');
    let isTyping = false;
    let typingTimer;
    let typingFor = '<?php echo $selectedUserId; ?>';

    // Function to send typing status update
    function sendTypingStatus(typing) {
        fetch('./ajex/typing-status.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'typing_for=' + encodeURIComponent(typingFor) + '&is_typing=' + (typing ? '1' : '0')
        })
        .then(response => response.text())
        .then(data => {
            console.log(data);
        });
    }
    // Start typing indicator
    function startTyping() {
        if (!isTyping) {
            isTyping = true;
            sendTypingStatus(true);
        }
        // Reset the timer
        clearTimeout(typingTimer);
        typingTimer = setTimeout(stopTyping, 500); // Stop after 0.5 seconds of inactivity
    }

    // Stop typing indicator
    function stopTyping() {
        if (isTyping) {
            isTyping = false;
            sendTypingStatus(false);
        }
    }
    messageInput.addEventListener('input', startTyping);
    messageInput.addEventListener('keydown', startTyping);
    messageInput.addEventListener('blur', stopTyping);
    messageInput.addEventListener('input', function() {
        if (this.value.length === 0) {
            stopTyping();
        }
    });

    function updateStatus() {
        $.ajax({
            url: './ajex/self-status.php',
            type: 'POST',
            data: { 
                action: 'update', 
                user_id: '<?php echo $loggedInUserId; ?>' 
            },
            success: function (response) {
                console.log('Status updated');
            }
        });
    }
    updateStatus();
    setInterval(function () {
        updateStatus();
    }, 10000);



    $(document).ready(function() {
    // Function to update user status
    function updateUserStatus() {
        fetch('./ajex/another-status.php?user_id=<?php echo $selectedUserId;?>')
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                // Update status dot
                const statusDot = document.getElementById('online-offline-status');
                if (statusDot) {
                    statusDot.classList.remove('online', 'offline');
                    statusDot.classList.add(data.statusClass);
                }
                
                // Update last seen text
                const lastSeenElement = document.getElementById('last-seen');
                if (lastSeenElement && data.lastSeen) {
                    lastSeenElement.textContent = data.lastSeen;
                }
            })
            .catch(error => {
                console.error('Error fetching status:', error);
                // Optional: Show error to user
                // document.getElementById('status-error').textContent = 'Could not update status';
            });
    }

    // Initial update and set interval
    updateUserStatus();
    setInterval(updateUserStatus, 5000);
});
</script>
<script>
        function checkTyping() {
            var typingForUserId = '<?php echo $selectedUserId;?>'; // Replace with the actual user ID you're checking for
            var loggedInUserId = <?php echo isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0; ?>; // PHP session for loged in user
            if(loggedInUserId === 0){
                document.getElementById('typing-status').innerHTML = "Please Log In";
                return;
            }

            var xhr = new XMLHttpRequest();
            xhr.onreadystatechange = function() {
                if (xhr.readyState == 4 && xhr.status == 200) {
                    document.getElementById('typing-status').innerHTML = xhr.responseText;
                }
            };
            xhr.open("GET", "./ajex/see-typing-status.php?typingForUserId=" + typingForUserId + "&loggedInUserId=" + loggedInUserId, true); //Replace your_php_file.php with the actual file name.
            xhr.send();
        }
        setInterval(checkTyping, 1000); // Check every 1 second
    </script>