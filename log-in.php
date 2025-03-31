<?php
session_start();
if (isset($_SESSION['user_id'])) {
    header('Location: ./deshboard.php');
    exit;
}
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
    $(document).ready(function () {
        // Password toggle functionality
        const togglePassword = document.querySelector('#togglePassword');
        const password = document.querySelector('#password');
        
        togglePassword.addEventListener('click', function (e) {
            // Toggle the type attribute
            const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
            password.setAttribute('type', type);
            
            // Toggle the eye / eye slash icon
            this.classList.toggle('fa-eye');
            this.classList.toggle('fa-eye-slash');
        });

        // Handle form submission
        $('#loginForm').on('submit', async function (e) {
            e.preventDefault();
            
            // Disable button and show loading state
            const loginBtn = $('#loginButton');
            loginBtn.prop('disabled', true);
            loginBtn.html('<i class="fas fa-spinner fa-spin mr-2"></i> Logging in...');
            
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
                        position: "center",
                        backgroundColor: "#4CAF50",
                        stopOnFocus: true,
                    }).showToast();

                    setTimeout(() => {
                        window.location.href = './deshboard.php';
                    }, 3000);
                } else {
                    loginBtn.prop('disabled', false);
                    loginBtn.html('<i class="fas fa-sign-in-alt mr-2"></i> Log In');
                    
                    Toastify({
                        text: response.message || "Invalid email or password.",
                        duration: 3000,
                        close: true,
                        gravity: "top",
                        position: "center",
                        backgroundColor: "#F44336",
                        stopOnFocus: true,
                    }).showToast();
                }
            } catch (error) {
                console.error('Error during login:', error);
                loginBtn.prop('disabled', false);
                loginBtn.html('<i class="fas fa-sign-in-alt mr-2"></i> Log In');
                
                Toastify({
                    text: "An error occurred. Please try again.",
                    duration: 3000,
                    close: true,
                    gravity: "top",
                    position: "center",
                    backgroundColor: "#F44336",
                    stopOnFocus: true,
                }).showToast();
            }
        });
    });
    </script>

<?php include './include/footer.php'; ?>