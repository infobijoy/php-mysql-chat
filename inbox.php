<?php
session_start();

// Redirect to login if the user is not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ./log-in.php');
    exit;
}

// Get the selected user's ID from the query string
$selectedUserId = $_GET['user_id'] ?? null;

// Validate the selected user ID
if (!$selectedUserId) {
    die("Invalid user ID.");
}

include './include/config-db.php';

// Fetch selected user data from the database
$query = "SELECT id, username, email, password_hash, display_name, profile_picture, status, created_at, updated_at FROM users WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $selectedUserId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
    $senderPhoto = $user['profile_picture'] ?: "demo-2.png";
    $userName = $user['display_name'] ?: $user['username'];
    $lastSeen = $user['status'] ?: "Last Seen";
} else {
    die("User not found.");
}

// Fetch logged-in user's data from the database
$loggedInUserId = $_SESSION['user_id'];
$query = "SELECT profile_picture FROM users WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $loggedInUserId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $loggedInUser = $result->fetch_assoc();
    $reciverPhoto = $loggedInUser['profile_picture'] ?: "demo-1.jpg";
} else {
    die("Logged-in user not found.");
}

$stmt->close();
$conn->close();

include './include/header.php';
?>

<div class="container chat-container px-2 mx-auto">
    <!-- Messages Container -->
    <div id="messagesContainer" class="bg-white z-50 rounded-lg shadow-md" style="height: 100vh; overflow-y: auto;">
        <div class="chat-box-top-head flex bg-indigo-300">
            <a href="./deshboard.php" class="text-black items-center flex py-2 px-2"><i class="fa-solid fa-arrow-left"></i></a>
            <div class="avatar ms-1">
                <div class="w-12 rounded-full">
                    <img src="./profile-photo/<?php echo $senderPhoto; ?>" />
                </div>
            </div>
            <div class="ms-3">
                <span class="block text-black font-bold"><?php echo $userName; ?></span>
                <span class="text-gray-800 text-sm" id="last-seen"></span>
            </div>
        </div>
        <ul id="messagesList" class="space-y-4 px-2.5">
            <!-- Messages will be dynamically loaded here -->
        </ul>
        
        <!-- Scroll to Bottom Button -->
        <button id="goToBottomButton" class="fixed bottom-20 left-1/2 transform -translate-x-1/2 z-50 bg-blue-500 text-white p-2 rounded-full shadow-lg hover:bg-blue-600">
    <i class="fa-solid fa-arrow-down"></i>
</button>
        
        <!-- Send Message Form -->
        <form id="sendMessageForm" class="flex items-center fixed bottom-0 w-full bg-white p-2">
            <textarea
                id="message"
                rows="1"
                name="message"
                class="typeing textarea textarea-primary flex-grow mr-2"
                placeholder="Type your message here"
                required
            ></textarea>
            <button
                type="submit"
                class="bg-blue-500 text-white py-3 px-4 rounded-md hover:bg-blue-600"
            >
                <i class="fa-solid fa-paper-plane"></i>
            </button>
        </form>
    </div>
</div>

<script>
const selectedUserId = <?= $selectedUserId ?>;
let previousMessages = []; // Store previous messages for comparison
let initialLoad = true; // Track if it's the initial load
let lastMessageId = 0; // Track the last message ID to detect new messages
let isScrolledUp = false; // Track if user is scrolled up
const goToBottomButton = document.getElementById('goToBottomButton');
const messagesContainer = document.getElementById('messagesContainer');
const messagesList = document.getElementById('messagesList');

// Helper function to format timestamps
const formatTimestamp = (timestamp) => {
    if (!timestamp) return '';
    const messageDate = new Date(timestamp);
    const now = new Date();

    const isToday = messageDate.toDateString() === now.toDateString();
    const isYesterday = messageDate.toDateString() === new Date(now.setDate(now.getDate() - 1)).toDateString();
    const isSameYear = messageDate.getFullYear() === new Date().getFullYear();

    // Calculate if the date is within the last week (but not today or yesterday)
    const oneWeekAgo = new Date();
    oneWeekAgo.setDate(now.getDate() - 7);
    const isWithinLastWeek = messageDate > oneWeekAgo && !isToday && !isYesterday;

    let datePart = '';
    if (isToday) {
        datePart = 'Today';
    } else if (isYesterday) {
        datePart = 'Yesterday';
    } else if (isWithinLastWeek) {
        // Show the day of the week for messages within the last week
        datePart = messageDate.toLocaleDateString(undefined, {
            weekday: 'short', // Returns day name like "Sun", "Mon", etc.
        });
    } else if (!isSameYear) {
        datePart = messageDate.toLocaleDateString(undefined, {
            day: '2-digit',
            month: 'short',
            year: '2-digit',
        });
    } else {
        datePart = messageDate.toLocaleDateString(undefined, {
            day: '2-digit',
            month: 'short',
        });
    }

    const timePart = messageDate.toLocaleTimeString(undefined, {
        hour: '2-digit',
        minute: '2-digit',
        hour12: true,
    });

    return datePart ? `${datePart} ${timePart}` : timePart;
};

// Function to scroll the messages container to the bottom
const scrollToBottom = () => {
    messagesList.scrollTo({
        top: messagesList.scrollHeight,
        behavior: 'smooth',
    });
    isScrolledUp = false;
    goToBottomButton.classList.add('hidden');
    markMessagesAsSeen(); // Mark messages as seen when scrolling to bottom
};

// Function to show new message toaster
const showNewMessageToast = (message) => {
    const isSender = message.sender_id == <?= $_SESSION['user_id'] ?>;
    if (isSender) return; // Don't show toaster for own messages

    Toastify({
        text: `
            <div class="flex items-center">
                <img src="./profile-photo/<?php echo $senderPhoto;?>" 
                     class="w-8 h-8 rounded-full mr-2" 
                     alt="${message.sender_username}">
                <div>
                    <div class="font-bold">${message.sender_username}</div>
                    <div class="text-sm">${message.message}</div>
                </div>
            </div>
        `,
        duration: 5000,
        gravity: "top",
        position: "right",
        escapeMarkup: false,
        onClick: () => {
            scrollToBottom();
        },
        style: {
            background: "white",
            color: "black",
            border: "1px solid #e5e7eb",
            "box-shadow": "0 4px 6px -1px rgba(0, 0, 0, 0.1)",
            "border-radius": "0.375rem",
            padding: "0.75rem",
        }
    }).showToast();
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

// Function to check if element is in viewport
function isElementInViewport(el) {
    const rect = el.getBoundingClientRect();
    return (
        rect.top >= 0 &&
        rect.left >= 0 &&
        rect.bottom <= (window.innerHeight || document.documentElement.clientHeight) &&
        rect.right <= (window.innerWidth || document.documentElement.clientWidth)
    );
}

// Function to check message visibility and mark as seen
const checkMessageVisibility = () => {
    const messages = document.querySelectorAll('.message-item');
    let shouldMarkAsSeen = false;
    
    messages.forEach(message => {
        if (message.dataset.senderId == selectedUserId && !message.dataset.seen && isElementInViewport(message)) {
            shouldMarkAsSeen = true;
        }
    });
    
    if (shouldMarkAsSeen) {
        markMessagesAsSeen();
    }
};

// Function to fetch and display messages
const fetchMessages = async () => {
    try {
        const response = await fetch(`./ajex/get-chat.php?user_id=${selectedUserId}`);
        const data = await response.json();

        if (data.success) {
            // Check for new messages
            const newMessages = data.messages.filter(msg => msg.id > lastMessageId);
            const hasNewMessages = newMessages.length > 0;
            
            if (hasNewMessages && isScrolledUp) {
                // Show toaster for each new message
                newMessages.forEach(msg => {
                    showNewMessageToast(msg);
                });
            }

            // Update lastMessageId
            if (data.messages.length > 0) {
                lastMessageId = Math.max(...data.messages.map(msg => msg.id));
            }

            if (JSON.stringify(data.messages) !== JSON.stringify(previousMessages)) {
                previousMessages = data.messages;
                messagesList.innerHTML = ''; // Clear messages list

                let lastMessageTime = null;

                data.messages.forEach((message, index) => {
                    const messageTime = new Date(message.created_at);
                    const timeDifference = lastMessageTime
                        ? Math.abs(messageTime - lastMessageTime) / 60000
                        : null;

                    const showTime =
                        !lastMessageTime || // Always show for the first message
                        timeDifference > 5; // Show if time difference > 5 minute

                    const formattedTime = formatTimestamp(message.created_at);
                    const seenTime = formatTimestamp(message.seen_at);

                    if (showTime) {
                        const timeElement = document.createElement('li');
                        timeElement.className = 'text-center text-gray-500 text-xs mt-2';
                        timeElement.textContent = formattedTime;
                        messagesList.appendChild(timeElement);

                        lastMessageTime = messageTime; // Update the lastMessageTime
                    }

                    // Render the message bubble
                    const messageElement = document.createElement('li');
                    const isSender = message.sender_id == <?= $_SESSION['user_id'] ?>;
                    messageElement.className = `chat message-item ${isSender ? 'chat-end' : 'chat-start'}`;
                    messageElement.dataset.messageId = message.id;
                    messageElement.dataset.senderId = message.sender_id;
                    messageElement.dataset.seen = message.is_seen ? 'true' : 'false';

                    messageElement.innerHTML = `
                        <div class="chat-image avatar">
                            <div class="w-10 rounded-full">
                                <img
                                    alt="User Avatar"
                                    src="./profile-photo/${isSender ? '<?php echo $reciverPhoto;?>' : '<?php echo $senderPhoto;?>'}" />
                            </div>
                        </div>
                        <div class="chat-header overflow-hidden transition-all duration-300 ease-in-out" style="max-height: 0; opacity: 0;">
                            <time class="text-xs opacity-50">${formattedTime}</time>
                        </div>
                        <div class="chat-bubble ${isSender ? 'chat-bubble-secondary' : 'chat-bubble-accent'}">${message.message}</div>
                        <div class="chat-footer overflow-hidden transition-all duration-300 ease-in-out" style="max-height: 0; opacity: 0;">
                            ${isSender ? 
                                (message.is_seen ? 
                                    `<span class="text-blue-500">Seen</span>
                                     <time class="text-xs opacity-50">${seenTime}</time>` : 
                                    '<span class="text-gray-500">Delivered</span>') : 
                                ''}
                        </div>
                    `;

                    // Toggle chat header & footer on bubble click
                    const chatBubble = messageElement.querySelector('.chat-bubble');
                    const chatHeader = messageElement.querySelector('.chat-header');
                    const chatFooter = messageElement.querySelector('.chat-footer');

                    chatBubble.addEventListener('click', () => {
                        [chatHeader, chatFooter].forEach(element => {
                            if (element.style.maxHeight === "0px") {
                                element.style.maxHeight = element.scrollHeight + "px";
                                element.style.opacity = "1";
                            } else {
                                element.style.maxHeight = "0px";
                                element.style.opacity = "0";
                            }
                        });
                    });

                    messagesList.appendChild(messageElement);
                });

                // Only scroll to bottom if not scrolled up
                if (!isScrolledUp) {
                    scrollToBottom();
                } else {
                    checkMessageVisibility();
                }
            }
        }
    } catch (error) {
        console.error('Error fetching messages:', error);
    }
};

// Function to show or hide the "Go to Bottom" button
const toggleGoToBottomButton = () => {
    const scrollDistanceFromBottom =
        messagesList.scrollHeight - messagesList.scrollTop - messagesList.clientHeight;

    isScrolledUp = scrollDistanceFromBottom > window.innerHeight / 2;
    
    if (isScrolledUp) {
        goToBottomButton.classList.remove('hidden');
    } else {
        goToBottomButton.classList.add('hidden');
    }
};

// Check scroll position every second
setInterval(toggleGoToBottomButton, 1000);

// Handle sending a message
document.getElementById('sendMessageForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const messageInput = document.getElementById('message');
    const messageText = messageInput.value.trim();
    
    if (!messageText) return;

    const formData = {
        receiver_id: selectedUserId,
        message: messageText,
    };

    try {
        const response = await fetch('./ajex/send-message.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(formData),
        });

        const result = await response.json();
        if (result.success) {
            messageInput.value = ''; // Clear the input field
            fetchMessages(); // Refresh messages immediately
        } else {
            alert('Failed to send message. Please try again.');
        }
    } catch (error) {
        console.error('Error sending message:', error);
        alert('An error occurred. Please try again.');
    }
});

// Event listener for scrolling
messagesList.addEventListener('scroll', () => {
    toggleGoToBottomButton();
    checkMessageVisibility();
});

// Click handler for the goToBottomButton
goToBottomButton.addEventListener('click', scrollToBottom);

// Fetch messages on page load
fetchMessages().then(() => {
    scrollToBottom();
    initialLoad = false; // Mark initial load as complete
});

// Poll for new messages every 2 seconds
setInterval(fetchMessages, 2000);

// Check for visible messages every second
setInterval(checkMessageVisibility, 1000);

$(document).ready(function () {
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

    function fetchLastSeen() {
    let userId = new URLSearchParams(window.location.search).get('user_id');
    if (userId) {
        $.ajax({
            url: './ajex/another-status.php',
            type: 'POST',
            data: { 
                action: 'fetch', 
                user_id: userId 
            },
            dataType: 'json', // Ensure the response is treated as JSON
            success: function (response) {
                if (response.last_seen) {
                    $('#last-seen').text(response.last_seen);
                } else {
                    $('#last-seen').text('No Data');
                }
            },
            error: function () {
                $('#last-seen').text('Error fetching data');
            }
        });
    }
}
    updateStatus();
    fetchLastSeen()
    setInterval(function () {
        updateStatus();
        fetchLastSeen();
    }, 3000);

});
</script>

<?php 
include "./include/footer.php";
?>