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
          <img src="./profile-photo/<?= htmlspecialchars($senderPhoto) ?>" />
        </div>
      </div>
      <div class="ms-3">
        <span class="block text-black font-bold"><?= htmlspecialchars($userName) ?></span>
        <span class="text-gray-800 text-sm"><?= htmlspecialchars($lastSeen) ?></span>
      </div>
    </div>
    <ul id="messagesList" class="space-y-4 px-2.5">
      <!-- Messages will be dynamically loaded here -->
    </ul>

    <!-- Go to Bottom Button -->
    <button
  id="goToBottomButton"
  class="hidden fixed bottom-16 shadow-xl  z-50 bg-blue-500 text-white py-2 px-4 rounded-full hover:bg-blue-600 transform -translate-x-1/2 left-1/2"
  onclick="scrollToBottom()"
>
  <i class="fa-solid fa-arrow-down"></i>
</button>

    <form id="sendMessageForm" class="flex items-center fixed bottom-0">
      <textarea
        id="message"
        rows="1"
        name="message"
        class="typeing textarea textarea-primary"
        placeholder="Type your message here"
        required
      ></textarea>
      <button
        type="submit"
        class="mt-4 sent-btn absolute right-0 bg-blue-500 text-white py-2 px-4 rounded-md hover:bg-blue-600"
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
  const goToBottomButton = document.getElementById('goToBottomButton');
  const messagesContainer = document.getElementById('messagesContainer');
  const messagesList = document.getElementById('messagesList');

  // Helper function to format timestamps
  const formatTimestamp = (timestamp) => {
  const messageDate = new Date(timestamp);
  const now = new Date();

  const isToday = messageDate.toDateString() === now.toDateString();
  const isYesterday =
    messageDate.toDateString() ===
    new Date(now.setDate(now.getDate() - 1)).toDateString();
  const isSameYear = messageDate.getFullYear() === new Date().getFullYear();

  // Calculate if the date is within the last week (but not today or yesterday)
  const oneWeekAgo = new Date();
  oneWeekAgo.setDate(now.getDate() - 7);
  const isWithinLastWeek =
    messageDate > oneWeekAgo && !isToday && !isYesterday;

  let datePart = '';
  if (isToday) {
    // I remove dateparet value cuz i dont need to show today. if need show today message then add the value 'Today' like this datePart = 'Today';
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
};

// Function to fetch and display messages
const fetchMessages = async () => {
  try {
    const response = await fetch(`./ajex/get-chat.php?user_id=${selectedUserId}`);
    const data = await response.json();

    if (data.success) {
      if (JSON.stringify(data.messages) !== JSON.stringify(previousMessages)) {
        previousMessages = data.messages;
        messagesList.innerHTML = ''; // Clear messages list

        let lastMessageTime = null; // Track the time of the last message rendered

        data.messages.forEach((message, index) => {
          const messageTime = new Date(message.created_at);
          const timeDifference = lastMessageTime
            ? Math.abs(messageTime - lastMessageTime) / 60000
            : null;

          const showTime =
            !lastMessageTime || // Always show for the first message
            timeDifference > 1; // Show if time difference > 1 minute

          const formattedTime = formatTimestamp(message.created_at);

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
          messageElement.className = `chat ${isSender ? 'chat-end' : 'chat-start'}`;
          messageElement.innerHTML = `
            <div class="chat-image avatar">
              <div class="w-10 rounded-full">
                <img
                  alt="User Avatar"
                  src="./profile-photo/${isSender ? '<?php echo $reciverPhoto;?>' : '<?php echo $senderPhoto;?>'}" />
              </div>
            </div>
            <div class="chat-header">
              <!--${message.sender_username}-->
              <time class="text-xs opacity-50">${formattedTime}</time>
            </div>
            <div class="chat-bubble ${
              isSender ? 'chat-bubble-secondary' : 'chat-bubble-accent'
            }">${message.message}</div>
            <div class="chat-footer opacity-50">Seen</div>
          `;
          messagesList.appendChild(messageElement);
        });

        // Scroll to the bottom of the chat after updating messages
        scrollToBottom();
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

  if (scrollDistanceFromBottom > window.innerHeight / 2) {
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
  const formData = {
    receiver_id: selectedUserId,
    message: document.getElementById('message').value,
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
      document.getElementById('message').value = ''; // Clear the input field
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
messagesList.addEventListener('scroll', toggleGoToBottomButton);

// Fetch messages on page load
fetchMessages().then(() => {
  scrollToBottom();
  initialLoad = false; // Mark initial load as complete
});

// Poll for new messages every 2 seconds
setInterval(fetchMessages, 2000);

</script>
<?php 
include "./include/footer.php";
?>