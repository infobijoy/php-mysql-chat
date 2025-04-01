<?php
session_start();
include './include/config-db.php';
if (isset($_SESSION['user_id'])) {
    header('Location: ./deshboard.php');
    exit;
}
if (!isset($_SESSION['user_id'])) {
  include './include/auto-login.php';
  // Check again after auto-login attempt
  if (isset($_SESSION['user_id'])) {
    // Auto-login successful, redirect to dashboard
    header('Location: ./deshboard.php');
    exit;
}
}
require_once './include/auth-middleware.php';
include './include/header.php';
?>
    <div class="min-h-screen flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-xl overflow-hidden w-full max-w-md">
            <div class="bg-gradient-to-r from-blue-600 to-indigo-700 p-6 text-white">
                <h1 class="text-3xl font-bold">Welcome Back</h1>
                <p class="opacity-90 mt-1">Sign in to your account</p>
            </div>
            
            <div class="p-8">
                <form id="loginForm">
                    <div class="mb-6">
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
                        <div class="relative">
                            <input
                                type="email"
                                id="email"
                                name="email"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200"
                                placeholder="your@email.com"
                                required
                            />
                            <i class="fas fa-envelope absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                        </div>
                    </div>

                    <div class="mb-6">
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                        <div class="password-container relative">
                            <input
                                type="password"
                                id="password"
                                name="password"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200 pr-10"
                                placeholder="••••••••"
                                required
                            />
                            <i class="password-toggle fas fa-eye-slash" id="togglePassword"></i>
                        </div>
                        <div class="mt-2 text-right">
                            <a href="#" class="text-sm text-blue-600 hover:text-blue-800 hover:underline">Forgot password?</a>
                        </div>
                    </div>
                    <div class="mb-4 flex items-center">
    <input type="checkbox" id="rememberMe" name="rememberMe" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
    <label for="rememberMe" class="ml-2 block text-sm text-gray-700">Remember me</label>
</div>
                    <button
                        type="submit"
                        id="loginButton"
                        class="w-full bg-gradient-to-r from-blue-600 to-indigo-700 text-white py-3 px-6 rounded-lg hover:from-blue-700 hover:to-indigo-800 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition duration-200 font-medium flex items-center justify-center"
                    >
                        <i class="fas fa-sign-in-alt mr-2"></i> Log In
                    </button>

                    <div class="mt-6 text-center text-sm text-gray-600">
                        Don't have an account? 
                        <a href="./sing-up.php" class="text-blue-600 hover:text-blue-800 hover:underline font-medium">Sign up</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
document.getElementById('loginForm').addEventListener('submit', async function(e) {
    e.preventDefault();

    const email = document.getElementById('email').value;
    const password = document.getElementById('password').value;
    const rememberMe = document.getElementById('rememberMe').checked;

    const loginBtn = document.getElementById('loginButton');
    loginBtn.disabled = true;
    loginBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Logging in...';

    try {
        const response = await fetch('./ajex/login.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                email,
                password,
                rememberMe
            })
        });

        const data = await response.json();

        if (data.success) {
            // Set remember token cookie via JavaScript if available
            if (data.rememberToken) {
                const { selector, validator, expires } = data.rememberToken;
                document.cookie = `remember_token=${selector}:${validator}; expires=${new Date(expires * 1000).toUTCString()}; path=/; samesite=lax`;
            }

            // Show success message and redirect
            Toastify({
                text: data.message,
                duration: 2000,
                backgroundColor: "#28a745",
                gravity: "top",
                position: "center"
            }).showToast();

            setTimeout(() => {
                window.location.href = data.redirect;
            }, 2000);

        } else {
            Toastify({
                text: data.message,
                duration: 3000,
                backgroundColor: "#dc3545",
                gravity: "top",
                position: "center"
            }).showToast();
            loginBtn.disabled = false;
            loginBtn.textContent = 'Log In';
        }

    } catch (error) {
        console.error('Login error:', error);
        Toastify({
            text: "Network error. Please try again.",
            duration: 3000,
            backgroundColor: "#dc3545",
            gravity: "top",
            position: "center"
        }).showToast();
        loginBtn.disabled = false;
        loginBtn.textContent = 'Log In';
    }
});
</script>

<?php include './include/footer.php'; ?>