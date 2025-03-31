<?php
session_start();
if (isset($_SESSION['user_id'])) {
    header('Location: ./deshboard.php');
    exit;
}
include './include/header.php';
?>

<div class="flex justify-center items-center h-screen">
  <div class="space-x-4">
    <a href="log-in.php" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">Log In</a>
    <a href="sing-up.php" class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">Sign Up</a>
    <a href="deshboard.php" class="bg-purple-500 hover:bg-purple-700 text-white font-bold py-2 px-4 rounded">Dashboard</a>
  </div>
</div>

<?php 
include "./include/footer.php";
?>