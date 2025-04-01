<?php
session_start();
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
?>
  <?php include './include/header.php'; ?>

  <style>
    .hero-bg {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }
    .feature-card {
      transition: all 0.3s ease;
    }
    .feature-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
    }
  </style>
  <!-- Hero Section -->
  <div class="hero-bg text-white">
    <div class="container mx-auto px-6 py-24">
      <div class="flex flex-col md:flex-row items-center">
        <div class="md:w-1/2 mb-10 md:mb-0">
          <h1 class="text-4xl md:text-5xl font-bold mb-6">Connect with <span class="text-yellow-300">Everyone</span></h1>
          <p class="text-xl mb-8">Experience seamless real-time messaging with our secure and intuitive chat platform.</p>
          <div class="flex flex-col sm:flex-row gap-4">
            <a href="log-in.php" class="btn btn-primary btn-lg">
              <i class="fas fa-sign-in-alt mr-2"></i> Log In
            </a>
            <a href="sing-up.php" class="btn btn-accent btn-lg">
              <i class="fas fa-user-plus mr-2"></i> Sign Up
            </a>
          </div>
        </div>
        <div class="md:w-1/2">
          <img src="https://illustrations.popsy.co/amber/digital-nomad.svg" alt="Chat Illustration" class="w-full max-w-md mx-auto">
        </div>
      </div>
    </div>
  </div>

  <!-- Features Section -->
  <div class="py-16 bg-white">
    <div class="container mx-auto px-6">
      <h2 class="text-3xl font-bold text-center mb-16">Why Choose Our <span class="text-purple-600">ChatApp</span></h2>
      
      <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
        <!-- Feature 1 -->
        <div class="feature-card bg-base-100 p-8 rounded-xl shadow-md border border-gray-100">
          <div class="text-purple-600 mb-4">
            <i class="fas fa-bolt text-4xl"></i>
          </div>
          <h3 class="text-xl font-bold mb-3">Lightning Fast</h3>
          <p class="text-gray-600">Real-time messaging with instant delivery and read receipts for seamless communication.</p>
        </div>
        
        <!-- Feature 2 -->
        <div class="feature-card bg-base-100 p-8 rounded-xl shadow-md border border-gray-100">
          <div class="text-blue-500 mb-4">
            <i class="fas fa-lock text-4xl"></i>
          </div>
          <h3 class="text-xl font-bold mb-3">End-to-End Secure</h3>
          <p class="text-gray-600">Your conversations are protected with industry-leading encryption technology.</p>
        </div>
        
        <!-- Feature 3 -->
        <div class="feature-card bg-base-100 p-8 rounded-xl shadow-md border border-gray-100">
          <div class="text-green-500 mb-4">
            <i class="fas fa-heart text-4xl"></i>
          </div>
          <h3 class="text-xl font-bold mb-3">User Friendly</h3>
          <p class="text-gray-600">Intuitive interface designed for all users with customizable themes and settings.</p>
        </div>
      </div>
    </div>
  </div>

  <!-- CTA Section -->
  <div class="py-16 bg-gradient-to-r from-purple-500 to-indigo-600 text-white">
    <div class="container mx-auto px-6 text-center">
      <h2 class="text-3xl font-bold mb-6">Ready to Get Started?</h2>
      <p class="text-xl mb-8 max-w-2xl mx-auto">Join thousands of happy users who are already enjoying seamless communication.</p>
      <div class="flex flex-col sm:flex-row justify-center gap-4">
        <a href="sing-up.php" class="btn btn-primary btn-lg">
          <i class="fas fa-rocket mr-2"></i> Sign Up Free
        </a>
        <a href="log-in.php" class="btn btn-outline btn-lg text-white border-white hover:bg-white hover:text-purple-600">
          <i class="fas fa-sign-in-alt mr-2"></i> Existing User
        </a>
      </div>
    </div>
  </div>

  <?php include './include/footer.php'; ?>