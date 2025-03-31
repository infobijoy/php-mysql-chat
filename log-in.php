<?php
session_start();
if (isset($_SESSION['user_id'])) {
    header('Location: ./deshboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Log In</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://cdn.jsdelivr.net/npm/daisyui@3.7.3/dist/full.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
</head>
<body class="">

<div class="flex justify-center items-center h-screen bg-gradient-to-r from-blue-100 to-indigo-200">
    <div class="bg-white p-8 rounded-xl shadow-2xl w-96 transform transition-transform duration-500 hover:scale-105">
        <h1 class="text-3xl font-bold mb-8 text-center text-blue-600 animate-pulse">Log In</h1>
        <form id="loginForm">
            <div class="mb-6">
                <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    class="mt-1 block w-full px-4 py-3 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 transition duration-300 ease-in-out transform hover:scale-102"
                    placeholder="Enter your email"
                    required
                />
            </div>

            <div class="mb-8">
                <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    class="mt-1 block w-full px-4 py-3 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 transition duration-300 ease-in-out transform hover:scale-102"
                    placeholder="Enter your password"
                    required
                />
            </div>

            <button
                type="submit"
                id="loginButton"
                class="w-full bg-blue-600 text-white py-3 px-6 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition duration-300 ease-in-out transform hover:scale-105"
            >
                Log In
            </button>
        </form>
    </div>
</div>

  <script>
    $(document).ready(function () {
      // Handle form submission
      $('#loginForm').on('submit', async function (e) {
        e.preventDefault();

        const formData = {
          email: $('#email').val(),
          password: $('#password').val(),
        };

        try {
          const response = await $.ajax({
            url: './ajex/login.php',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify(formData),
            dataType: 'json',
          });

          if (response.success) {
            Toastify({
              text: "Login successful! Redirecting...",
              duration: 3000,
              close: true,
              gravity: "top",
              position: "right",
              backgroundColor: "green",
            }).showToast();

            setTimeout(() => {
              window.location.href = './deshboard.php';
            }, 3000);
          } else {
            Toastify({
              text: response.message || "Invalid email or password.",
              duration: 3000,
              close: true,
              gravity: "top",
              position: "right",
              backgroundColor: "red",
            }).showToast();
          }
        } catch (error) {
          console.error('Error during login:', error);
          Toastify({
            text: "An error occurred. Please try again.",
            duration: 3000,
            close: true,
            gravity: "top",
            position: "right",
            backgroundColor: "red",
          }).showToast();
        }
      });
    });
  </script>
</body>
</html>