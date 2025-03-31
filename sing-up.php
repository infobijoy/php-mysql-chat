<?php
session_start();
if (isset($_SESSION['user_id'])) {
    header('Location: ./deshboard.php');
    exit;
}
include './include/header.php';
?>
    <style>
        .input-container {
            position: relative;
        }
        .input-icon {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #9ca3af;
        }
        .input-with-icon {
            padding-left: 40px;
        }
        .strength-meter {
            height: 4px;
            background-color: #e5e7eb;
            margin-top: 8px;
            border-radius: 2px;
            overflow: hidden;
        }
        .strength-meter-fill {
            height: 100%;
            width: 0%;
            transition: width 0.3s, background-color 0.3s;
        }
    </style>
    <div class="min-h-screen flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-xl overflow-hidden w-full max-w-md">
            <div class="bg-gradient-to-r from-blue-600 to-indigo-700 p-6 text-white text-center">
                <h1 class="text-3xl font-bold">Create Account</h1>
                <p class="opacity-90 mt-1">Join our community today</p>
            </div>
            
            <div class="p-8">
                <form id="signupForm">
                    <!-- Username Field -->
                    <div class="mb-6">
                        <label for="username" class="block text-sm font-medium text-gray-700 mb-1">Username</label>
                        <div class="input-container">
                            <i class="fas fa-user input-icon"></i>
                            <input
                                type="text"
                                id="username"
                                name="username"
                                class="w-full px-4 py-3 pl-10 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200"
                                placeholder="johndoe"
                                required
                                minlength="3"
                                maxlength="20"
                            />
                        </div>
                        <p id="usernameError" class="text-sm text-red-500 mt-1 hidden">
                            <i class="fas fa-exclamation-circle mr-1"></i> Username is already taken
                        </p>
                        <p id="usernameValid" class="text-sm text-green-500 mt-1 hidden">
                            <i class="fas fa-check-circle mr-1"></i> Username available
                        </p>
                    </div>

                    <!-- Email Field -->
                    <div class="mb-6">
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
                        <div class="input-container">
                            <i class="fas fa-envelope input-icon"></i>
                            <input
                                type="email"
                                id="email"
                                name="email"
                                class="w-full px-4 py-3 pl-10 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200"
                                placeholder="your@email.com"
                                required
                            />
                        </div>
                        <p id="emailError" class="text-sm text-red-500 mt-1 hidden">
                            <i class="fas fa-exclamation-circle mr-1"></i> Email is already registered
                        </p>
                        <p id="emailValid" class="text-sm text-green-500 mt-1 hidden">
                            <i class="fas fa-check-circle mr-1"></i> Email available
                        </p>
                    </div>

                    <!-- Password Field -->
                    <div class="mb-6">
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                        <div class="input-container">
                            <i class="fas fa-lock input-icon"></i>
                            <input
                                type="password"
                                id="password"
                                name="password"
                                class="w-full px-4 py-3 pl-10 pr-10 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200"
                                placeholder="••••••••"
                                required
                                minlength="8"
                            />
                            <i class="password-toggle fas fa-eye-slash" id="togglePassword"></i>
                        </div>
                        <div class="strength-meter">
                            <div class="strength-meter-fill" id="passwordStrength"></div>
                        </div>
                        <p class="text-xs text-gray-500 mt-2">
                            Password must be at least 8 characters
                        </p>
                    </div>

                    <!-- Terms Checkbox -->
                    <div class="mb-6 flex items-start">
                        <div class="flex items-center h-5">
                            <input
                                id="terms"
                                name="terms"
                                type="checkbox"
                                class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300 rounded"
                                required
                            />
                        </div>
                        <div class="ml-3 text-sm">
                            <label for="terms" class="font-medium text-gray-700">
                                I agree to the <a href="#" class="text-blue-600 hover:underline">Terms of Service</a> and <a href="#" class="text-blue-600 hover:underline">Privacy Policy</a>
                            </label>
                        </div>
                    </div>

                    <!-- Sign Up Button -->
                    <button
                        type="submit"
                        id="signupButton"
                        class="w-full bg-gradient-to-r from-blue-600 to-indigo-700 text-white py-3 px-6 rounded-lg hover:from-blue-700 hover:to-indigo-800 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition duration-200 font-medium flex items-center justify-center disabled:opacity-50 disabled:cursor-not-allowed"
                        disabled
                    >
                        <i class="fas fa-user-plus mr-2"></i> Create Account
                    </button>

                    <div class="mt-6 text-center text-sm text-gray-600">
                        Already have an account? 
                        <a href="./log-in.php" class="text-blue-600 hover:text-blue-800 hover:underline font-medium">Log in</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
    $(document).ready(function () {
        // DOM Elements
        const $usernameInput = $('#username');
        const $emailInput = $('#email');
        const $passwordInput = $('#password');
        const $signupButton = $('#signupButton');
        const $usernameError = $('#usernameError');
        const $emailError = $('#emailError');
        const $usernameValid = $('#usernameValid');
        const $emailValid = $('#emailValid');
        const $passwordStrength = $('#passwordStrength');
        const $togglePassword = $('#togglePassword');
        const $termsCheckbox = $('#terms');

        // Debounce function to limit API calls
        const debounce = (func, delay) => {
            let timeoutId;
            return (...args) => {
                clearTimeout(timeoutId);
                timeoutId = setTimeout(() => {
                    func.apply(this, args);
                }, delay);
            };
        };

        // Password strength calculator
        const calculatePasswordStrength = (password) => {
            if (!password) return 0;
            
            let strength = 0;
            
            // Length contributes up to 40%
            strength += Math.min(password.length / 20 * 40, 40);
            
            // Character variety contributes up to 60%
            const hasLower = /[a-z]/.test(password);
            const hasUpper = /[A-Z]/.test(password);
            const hasNumber = /[0-9]/.test(password);
            const hasSpecial = /[^A-Za-z0-9]/.test(password);
            
            const varietyCount = [hasLower, hasUpper, hasNumber, hasSpecial].filter(Boolean).length;
            strength += varietyCount * 15; // 15% per variety type
            
            return Math.min(strength, 100);
        };

        // Update password strength meter
        const updatePasswordStrength = (password) => {
            const strength = calculatePasswordStrength(password);
            $passwordStrength.css('width', strength + '%');
            
            if (strength < 40) {
                $passwordStrength.css('background-color', '#ef4444'); // red
            } else if (strength < 70) {
                $passwordStrength.css('background-color', '#f59e0b'); // amber
            } else {
                $passwordStrength.css('background-color', '#10b981'); // emerald
            }
        };

        // Password toggle functionality
        $togglePassword.on('click', function() {
            const type = $passwordInput.attr('type') === 'password' ? 'text' : 'password';
            $passwordInput.attr('type', type);
            $(this).toggleClass('fa-eye fa-eye-slash');
        });

        // Check field availability
        const checkAvailability = async (field, value, $errorElement, $validElement) => {
            if (!value || value.length < (field === 'username' ? 3 : 5)) {
                $errorElement.addClass('hidden');
                $validElement.addClass('hidden');
                return false;
            }

            try {
                const response = await $.ajax({
                    url: `./ajex/check-availability.php?${field}=${encodeURIComponent(value)}`,
                    method: 'GET',
                    dataType: 'json',
                });

                if (response.available) {
                    $errorElement.addClass('hidden');
                    $validElement.removeClass('hidden');
                    return true;
                } else {
                    $errorElement.removeClass('hidden');
                    $validElement.addClass('hidden');
                    return false;
                }
            } catch (error) {
                console.error('Error checking availability:', error);
                return false;
            }
        };

        // Form validation
        const validateForm = debounce(async () => {
            const isUsernameValid = await checkAvailability('username', $usernameInput.val(), $usernameError, $usernameValid);
            const isEmailValid = await checkAvailability('email', $emailInput.val(), $emailError, $emailValid);
            const isPasswordValid = $passwordInput.val().length >= 8;
            const isTermsAccepted = $termsCheckbox.is(':checked');

            // Update password strength
            updatePasswordStrength($passwordInput.val());

            // Enable button if all valid
            $signupButton.prop('disabled', !(isUsernameValid && isEmailValid && isPasswordValid && isTermsAccepted));
        }, 500);

        // Event listeners
        $usernameInput.on('input', validateForm);
        $emailInput.on('input', validateForm);
        $passwordInput.on('input', validateForm);
        $termsCheckbox.on('change', validateForm);

        // Form submission
        $('#signupForm').on('submit', async function(e) {
            e.preventDefault();
            
            // Disable button and show loading
            $signupButton.prop('disabled', true);
            $signupButton.html('<i class="fas fa-spinner fa-spin mr-2"></i> Creating account...');

            const formData = {
                username: $usernameInput.val(),
                email: $emailInput.val(),
                password: $passwordInput.val(),
                terms: $termsCheckbox.is(':checked')
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
                        text: "Account created successfully! Redirecting...",
                        duration: 3000,
                        close: true,
                        gravity: "top",
                        position: "center",
                        backgroundColor: "#10b981",
                        stopOnFocus: true,
                    }).showToast();

                    setTimeout(() => {
                        window.location.href = './log-in.php';
                    }, 3000);
                } else {
                    $signupButton.prop('disabled', false);
                    $signupButton.html('<i class="fas fa-user-plus mr-2"></i> Create Account');
                    
                    Toastify({
                        text: response.message || "Signup failed. Please try again.",
                        duration: 3000,
                        close: true,
                        gravity: "top",
                        position: "center",
                        backgroundColor: "#ef4444",
                        stopOnFocus: true,
                    }).showToast();
                }
            } catch (error) {
                console.error('Error during signup:', error);
                $signupButton.prop('disabled', false);
                $signupButton.html('<i class="fas fa-user-plus mr-2"></i> Create Account');
                
                Toastify({
                    text: "An error occurred. Please try again.",
                    duration: 3000,
                    close: true,
                    gravity: "top",
                    position: "center",
                    backgroundColor: "#ef4444",
                    stopOnFocus: true,
                }).showToast();
            }
        });
    });
    </script>
<?php include './include/footer.php'; ?>