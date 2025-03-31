<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sign Up</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://cdn.jsdelivr.net/npm/daisyui@3.7.3/dist/full.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
</head>
<body class="bg-gray-100 flex items-center justify-center h-screen">
  <div class="bg-white p-8 rounded-lg shadow-md w-96">
    <h1 class="text-2xl font-bold mb-6 text-center">Sign Up</h1>
    <form id="signupForm">
      <!-- Username Field -->
      <div class="mb-4">
        <label for="username" class="block text-sm font-medium text-gray-700">Username</label>
        <input
          type="text"
          id="username"
          name="username"
          class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
          placeholder="Enter your username"
          required
        />
        <p id="usernameError" class="text-sm text-red-500 mt-1 hidden">Username is already taken.</p>
      </div>

      <!-- Email Field -->
      <div class="mb-4">
        <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
        <input
          type="email"
          id="email"
          name="email"
          class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
          placeholder="Enter your email"
          required
        />
        <p id="emailError" class="text-sm text-red-500 mt-1 hidden">Email is already registered.</p>
      </div>

      <!-- Password Field -->
      <div class="mb-6">
        <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
        <input
          type="password"
          id="password"
          name="password"
          class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
          placeholder="Enter your password"
          required
        />
      </div>

      <!-- Sign Up Button -->
      <button
        type="submit"
        id="signupButton"
        class="w-full bg-blue-500 text-white py-2 px-4 rounded-md hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed"
        disabled
      >
        Sign Up
      </button>
    </form>
  </div>

  <script>
    $(document).ready(function () {
      const $usernameInput = $('#username');
      const $emailInput = $('#email');
      const $passwordInput = $('#password');
      const $signupButton = $('#signupButton');
      const $usernameError = $('#usernameError');
      const $emailError = $('#emailError');

      // Function to check if username/email is available
      const checkAvailability = async (field, value, $errorElement) => {
        if (!value) return false;

        try {
          const response = await $.ajax({
            url: `./ajex/check-availability.php?${field}=${encodeURIComponent(value)}`,
            method: 'GET',
            dataType: 'json',
          });

          if (response.available) {
            $errorElement.addClass('hidden');
            return true;
          } else {
            $errorElement.removeClass('hidden');
            return false;
          }
        } catch (error) {
          console.error('Error checking availability:', error);
          return false;
        }
      };

      // Function to validate the form
      const validateForm = async () => {
        const isUsernameValid = await checkAvailability('username', $usernameInput.val(), $usernameError);
        const isEmailValid = await checkAvailability('email', $emailInput.val(), $emailError);

        // Enable the signup button only if all fields are valid
        if (isUsernameValid && isEmailValid && $passwordInput.val()) {
          $signupButton.prop('disabled', false);
        } else {
          $signupButton.prop('disabled', true);
        }
      };

      // Add event listeners for input fields
      $usernameInput.on('input', validateForm);
      $emailInput.on('input', validateForm);
      $passwordInput.on('input', validateForm);

      // Handle form submission
      $('#signupForm').on('submit', async function (e) {
        e.preventDefault();

        const formData = {
          username: $usernameInput.val(),
          email: $emailInput.val(),
          password: $passwordInput.val(),
        };

        try {
          const response = await $.ajax({
            url: './ajex/signup.php',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify(formData),
            dataType: 'json',
          });

          if (response.success) {
            Toastify({
              text: "Signup successful! Redirecting...",
              duration: 3000,
              close: true,
              gravity: "top",
              position: "right",
              backgroundColor: "green",
            }).showToast();

            setTimeout(() => {
              window.location.href = './log-in.php';
            }, 3000);
          } else {
            Toastify({
              text: response.message || "Signup failed. Please try again.",
              duration: 3000,
              close: true,
              gravity: "top",
              position: "right",
              backgroundColor: "red",
            }).showToast();
          }
        } catch (error) {
          console.error('Error during signup:', error);
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