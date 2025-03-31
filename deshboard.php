<?php
session_start();

// Redirect to login if the user is not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ./log-in.php');
    exit;
}

// Include the database configuration file
include './include/config-db.php';

// Fetch all users from the database
try {
    $stmt = $conn->prepare("SELECT id, username FROM users WHERE id != ?");
    $stmt->bind_param('i', $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $users = $result->fetch_all(MYSQLI_ASSOC);
} catch (Exception $e) {
    die("Error fetching users: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://cdn.jsdelivr.net/npm/daisyui@3.7.3/dist/full.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
  <div class="container mx-auto p-4">
    <h1 class="text-2xl font-bold mb-6">Welcome to the Dashboard</h1>

    <!-- Logout Button -->
    <div class="mb-6">
      <a href="./logout.php" class="bg-red-500 text-white py-2 px-4 rounded-md hover:bg-red-600">Logout</a>
    </div>

    <!-- List of Users -->
    <div class="bg-white p-6 rounded-lg shadow-md">
      <h2 class="text-xl font-semibold mb-4">All Users</h2>
      <ul class="space-y-2">
        <?php foreach ($users as $user): ?>
          <li class="flex items-center justify-between p-2 hover:bg-gray-50 rounded-md">
            <span class="text-gray-700"><?= htmlspecialchars($user['username']) ?></span>
            <a href="./inbox.php?user_id=<?= $user['id'] ?>" class="bg-blue-500 text-white py-1 px-3 rounded-md hover:bg-blue-600">Go to Inbox</a>
          </li>
        <?php endforeach; ?>
      </ul>
    </div>
  </div>
</body>
</html>