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

// Get current user data
$current_user_stmt = $conn->prepare("SELECT username, display_name, profile_picture FROM users WHERE id = ?");
$current_user_stmt->bind_param('i', $_SESSION['user_id']);
$current_user_stmt->execute();
$current_user_result = $current_user_stmt->get_result();
$current_user = $current_user_result->fetch_assoc();
?>

<?php include './include/header.php'; ?>

<!-- Logout Modal (hidden by default) -->
<div id="logoutModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
  <div class="bg-white rounded-lg p-6 max-w-sm w-full mx-4">
    <div class="text-center">
      <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100">
        <svg class="h-6 w-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
        </svg>
      </div>
      <h3 class="text-lg font-medium text-gray-900 mt-3">Logout Confirmation</h3>
      <div class="mt-2">
        <p class="text-sm text-gray-500">Are you sure you want to logout from your account?</p>
      </div>
      <div class="mt-4 flex justify-center space-x-3">
        <button type="button" onclick="hideLogoutModal()" class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
          Cancel
        </button>
        <a href="./logout.php" class="px-4 py-2 bg-red-600 text-white rounded-md text-sm font-medium hover:bg-red-700">
          Logout
        </a>
      </div>
    </div>
  </div>
</div>
 <div class="container chat-container px-2 mx-auto">
<div class="flex flex-col h-screen user-content bg-white rounded-lg shadow-mdbg-white shadow-md" style="height: 100vh; overflow-y: auto;">
  <!-- Header -->
  <div class="bg-blue-500 text-white p-4 flex justify-between items-center">
    <h1 class="text-xl font-bold">Messages</h1>
    <div class="flex items-center space-x-4">
      <button onclick="showLogoutModal()" class="text-white hover:text-gray-200">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
        </svg>
      </button>
      <div class="avatar">
        <div class="w-8 rounded-full">
          <img src="./profile-photo/<?= $current_user['profile_picture'] ?? 'default.jpg' ?>" alt="Profile" />
        </div>
      </div>
    </div>
  </div>

  <!-- User List Container -->
  <div id="userList" class="flex-1 overflow-y-auto">
    <!-- Users will be loaded via AJAX -->
  </div>
</div>
 </div>

<script>
// Store previous user data for comparison
let previousUserData = null;
const currentTime = new Date().getTime();

// Logout modal functions
function showLogoutModal() {
    document.getElementById('logoutModal').classList.remove('hidden');
}

function hideLogoutModal() {
    document.getElementById('logoutModal').classList.add('hidden');
}

// Close modal when clicking outside
document.getElementById('logoutModal').addEventListener('click', function(e) {
    if (e.target === this) {
        hideLogoutModal();
    }
});

// Function to format activity time
function formatActivityTime(timestamp) {
    if (!timestamp || timestamp === '0000-00-00 00:00:00') {
        return 'Never active';
    }
    
    const now = new Date();
    const activityTime = new Date(timestamp);
    const diffMs = now - activityTime;
    const diffMinutes = Math.floor(diffMs / (1000 * 60));
    
    if (diffMs < 60000) { // 60,000ms = 1 minute
        return 'Active now';
    } else if (diffMinutes < 60) {
        return `${diffMinutes} min ago`;
    } else {
        const options = { hour: 'numeric', minute: 'numeric', hour12: true };
        const timeString = activityTime.toLocaleTimeString('en-US', options);
        
        if (activityTime.toDateString() === now.toDateString()) {
            return `Today at ${timeString}`;
        } else {
            const yesterday = new Date(now);
            yesterday.setDate(yesterday.getDate() - 1);
            
            if (activityTime.toDateString() === yesterday.toDateString()) {
                return `Yesterday at ${timeString}`;
            } else {
                const diffDays = Math.floor(diffMs / (1000 * 60 * 60 * 24));
                
                if (diffDays < 7) {
                    const dayNames = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
                    return `${dayNames[activityTime.getDay()]} at ${timeString}`;
                } else {
                    const dateOptions = { month: 'short', day: 'numeric', year: 'numeric' };
                    return activityTime.toLocaleDateString('en-US', dateOptions);
                }
            }
        }
    }
}

// Function to fetch and update user list
function updateUserList() {
    const fetchStartTime = Date.now();
    
    fetch('./ajex/get-users.php?t=' + new Date().getTime()) // Add cache-busting parameter
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            if (!data.success || !Array.isArray(data.users)) {
                throw new Error('Invalid data format');
            }
            
            // Always update the DOM, but optimize rendering
            const userListContainer = document.getElementById('userList');
            let html = '';
            const now = new Date();
            
            data.users.forEach(user => {
                const lastActiveTime = user.last_active && user.last_active !== '0000-00-00 00:00:00' 
                    ? new Date(user.last_active).getTime() 
                    : 0;
                const isActive = lastActiveTime && (now - lastActiveTime) < 60000;
                
                html += `
                <a href="./inbox.php?user_id=${user.id}" class="block border-b border-gray-200 hover:bg-gray-100 transition-colors">
                    <div class="flex items-center p-3">
                        <div class="avatar relative mr-3">
                            <div class="w-12 rounded-full">
                                <img src="./profile-photo/${user.profile_picture || 'default.jpg'}" alt="${user.username}" />
                            </div>
                            ${lastActiveTime ? `
                            <span class="absolute bottom-0 right-0 w-3 h-3 rounded-full border-2 border-white ${isActive ? 'bg-green-500' : 'bg-gray-400'}"></span>
                            ` : ''}
                        </div>
                        
                        <div class="flex-1 min-w-0">
                            <div class="flex justify-between">
                                <h3 class="font-medium truncate">${user.display_name || user.username}</h3>
                                <span class="text-xs text-gray-500 ml-2 whitespace-nowrap">
                                    ${formatActivityTime(user.status)}
                                </span>
                            </div>
                            
                            ${user.last_message ? `
                                <p class="text-sm text-gray-500 truncate">${user.last_message}</p>
                            ` : `
                                <p class="text-sm text-gray-400 italic">No messages yet</p>
                            `}
                        </div>
                    </div>
                </a>
                `;
            });
            
            // Only update the DOM if the content has changed
            if (userListContainer.innerHTML !== html) {
                userListContainer.innerHTML = html;
            }
            
            previousUserData = data.users;
            
            // Adjust polling interval based on fetch duration
            const fetchDuration = Date.now() - fetchStartTime;
            const nextPoll = Math.max(1000, 5000 - fetchDuration); // Ensure minimum 1s interval
            setTimeout(updateUserList, nextPoll);
        })
        .catch(error => {
            console.error('Error fetching users:', error);
            // Retry with exponential backoff
            const retryDelay = Math.min(30000, 1000 * Math.pow(2, (error.retryCount || 0)));
            error.retryCount = (error.retryCount || 0) + 1;
            setTimeout(updateUserList, retryDelay);
        });
}

// Initial load
document.addEventListener('DOMContentLoaded', updateUserList);
</script>

<?php include './include/footer.php'; ?>