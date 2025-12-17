<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>AMS - Create Account</title>
  <link rel="stylesheet" href="../assets/css/output.css" />
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" crossorigin="anonymous" />
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;700&display=swap" rel="stylesheet" />
</head>
<body class="min-h-screen flex items-center justify-center p-6 bg-gray-100 font-[Poppins]" style="background-image: url('../assets/images/image 7.png'); background-size: cover; background-position: center; background-repeat: no-repeat;">
  <div class="relative w-full max-w-2xl">
    <!-- Logo -->
    <div class="absolute -top-12 left-1/2 transform -translate-x-1/2">
      <img src="../assets/images/qcu-logo.png" alt="QCU Logo"
           class="w-20 h-20 rounded-full shadow-lg border-4 border-white dark:border-[#071127]">
    </div>

    <!-- Registration Card -->
    <div class="bg-white rounded-xl shadow-2xl p-6 pt-14 max-h-[90vh] overflow-y-auto">
      <!-- Header -->
      <div class="text-center mb-6">
        <h1 class="text-2xl sm:text-3xl font-bold text-[#1E3A8A]">Create Account</h1>
        <p class="text-sm font-normal text-black mt-1">Register for Asset Management System</p>
        <p class="text-xs text-gray-600 mt-2">
          <i class="fa-solid fa-info-circle mr-1"></i>
          Your account will be pending approval by an administrator
        </p>
      </div>

      <?php
      // Display error message if exists
      if (isset($_SESSION['error_message'])) {
          echo '<div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6 text-sm flex items-center gap-2">' .
               '<i class="fa-solid fa-circle-exclamation"></i>' .
               htmlspecialchars($_SESSION['error_message']) . '</div>';
          unset($_SESSION['error_message']);
      }
      
      // Display success message if exists
      if (isset($_SESSION['success_message'])) {
          echo '<div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-6 text-sm flex items-center gap-2">' .
               '<i class="fa-solid fa-circle-check"></i>' .
               htmlspecialchars($_SESSION['success_message']) . '</div>';
          unset($_SESSION['success_message']);
      }
      ?>

      <!-- Registration Form -->
      <form action="../controller/register_controller.php" method="POST" class="space-y-4">
        
        <!-- Basic Information -->
        <div class="border-b pb-4 mb-4">
          <h3 class="text-lg font-semibold text-gray-800 mb-3">Basic Information</h3>
          
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <!-- ID Number -->
            <div>
              <label for="id_number" class="block text-sm font-medium text-black mb-2">
                ID Number <span class="text-red-500">*</span>
              </label>
              <input type="text" id="id_number" name="id_number" required autocomplete="off"
                     placeholder="e.g., 22-0305, F2024-001"
                     class="w-full px-4 py-2.5 text-base font-normal rounded-lg border border-gray-300 bg-gray-100 text-black focus:outline-none focus:ring-2 focus:ring-[#1E3A8A] transition">
            </div>

            <!-- Full Name -->
            <div>
              <label for="full_name" class="block text-sm font-medium text-black mb-2">
                Full Name <span class="text-red-500">*</span>
              </label>
              <input type="text" id="full_name" name="full_name" required autocomplete="off"
                     placeholder="Enter your full name"
                     class="w-full px-4 py-2.5 text-base font-normal rounded-lg border border-gray-300 bg-gray-100 text-black focus:outline-none focus:ring-2 focus:ring-[#1E3A8A] transition">
            </div>

            <!-- Email -->
            <div>
              <label for="email" class="block text-sm font-medium text-black mb-2">
                Email Address <span class="text-red-500">*</span>
              </label>
              <input type="email" id="email" name="email" required autocomplete="off"
                     placeholder="your.email@example.com"
                     class="w-full px-4 py-2.5 text-base font-normal rounded-lg border border-gray-300 bg-gray-100 text-black focus:outline-none focus:ring-2 focus:ring-[#1E3A8A] transition">
            </div>

            <!-- Role -->
            <div>
              <label for="role" class="block text-sm font-medium text-black mb-2">
                Role <span class="text-red-500">*</span>
              </label>
              <select id="role" name="role" required
                      class="w-full px-4 py-2.5 text-base font-normal rounded-lg border border-gray-300 bg-gray-100 text-black focus:outline-none focus:ring-2 focus:ring-[#1E3A8A] transition">
                <option value="">Select Role</option>
                <option value="Student">Student</option>
                <option value="Faculty">Faculty</option>
                <option value="Technician">Technician</option>
                <option value="Laboratory Staff">Laboratory Staff</option>
              </select>
            </div>
          </div>
        </div>

        <!-- Password -->
        <div class="border-b pb-4 mb-4">
          <h3 class="text-lg font-semibold text-gray-800 mb-3">Password</h3>
          
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <!-- Password -->
            <div class="relative">
              <label for="password" class="block text-sm font-medium text-black mb-2">
                Password <span class="text-red-500">*</span>
              </label>
              <input type="password" id="password" name="password" required
                     placeholder="Enter password"
                     class="w-full px-4 py-2.5 pr-10 text-base font-normal rounded-lg border border-gray-300 bg-gray-100 text-black focus:outline-none focus:ring-2 focus:ring-[#1E3A8A] transition">
              <button type="button" onclick="togglePassword('password')" class="absolute right-3 top-[42px] text-gray-500">
                <i id="toggleIcon-password" class="fa-solid fa-eye"></i>
              </button>
            </div>

            <!-- Confirm Password -->
            <div class="relative">
              <label for="confirm_password" class="block text-sm font-medium text-black mb-2">
                Confirm Password <span class="text-red-500">*</span>
              </label>
              <input type="password" id="confirm_password" name="confirm_password" required
                     placeholder="Confirm password"
                     class="w-full px-4 py-2.5 pr-10 text-base font-normal rounded-lg border border-gray-300 bg-gray-100 text-black focus:outline-none focus:ring-2 focus:ring-[#1E3A8A] transition">
              <button type="button" onclick="togglePassword('confirm_password')" class="absolute right-3 top-[42px] text-gray-500">
                <i id="toggleIcon-confirm_password" class="fa-solid fa-eye"></i>
              </button>
            </div>
          </div>
        </div>

        <!-- Security Questions -->
        <div class="border-b pb-4 mb-4">
          <h3 class="text-lg font-semibold text-gray-800 mb-1">Security Questions</h3>
          <p class="text-xs text-gray-600 mb-3">These will be used for password recovery</p>
          
          <!-- Security Question 1 -->
          <div class="mb-4">
            <label for="security_question_1" class="block text-sm font-medium text-black mb-2">
              Security Question 1 <span class="text-red-500">*</span>
            </label>
            <select id="security_question_1" name="security_question_1" required
                    class="w-full px-4 py-2.5 text-base font-normal rounded-lg border border-gray-300 bg-gray-100 text-black focus:outline-none focus:ring-2 focus:ring-[#1E3A8A] transition mb-2">
              <option value="">Select a question</option>
              <option value="What was the name of your first pet?">What was the name of your first pet?</option>
              <option value="What is your mother's maiden name?">What is your mother's maiden name?</option>
              <option value="What was the name of your elementary school?">What was the name of your elementary school?</option>
              <option value="What city were you born in?">What city were you born in?</option>
              <option value="What is your favorite food?">What is your favorite food?</option>
            </select>
            <input type="text" id="security_answer_1" name="security_answer_1" required
                   placeholder="Your answer"
                   class="w-full px-4 py-2.5 text-base font-normal rounded-lg border border-gray-300 bg-gray-100 text-black focus:outline-none focus:ring-2 focus:ring-[#1E3A8A] transition">
          </div>

          <!-- Security Question 2 -->
          <div>
            <label for="security_question_2" class="block text-sm font-medium text-black mb-2">
              Security Question 2 <span class="text-red-500">*</span>
            </label>
            <select id="security_question_2" name="security_question_2" required
                    class="w-full px-4 py-2.5 text-base font-normal rounded-lg border border-gray-300 bg-gray-100 text-black focus:outline-none focus:ring-2 focus:ring-[#1E3A8A] transition mb-2">
              <option value="">Select a question</option>
              <option value="What is your favorite book?">What is your favorite book?</option>
              <option value="What was your childhood nickname?">What was your childhood nickname?</option>
              <option value="What is the name of your best friend?">What is the name of your best friend?</option>
              <option value="What is your favorite movie?">What is your favorite movie?</option>
              <option value="What street did you grow up on?">What street did you grow up on?</option>
            </select>
            <input type="text" id="security_answer_2" name="security_answer_2" required
                   placeholder="Your answer"
                   class="w-full px-4 py-2.5 text-base font-normal rounded-lg border border-gray-300 bg-gray-100 text-black focus:outline-none focus:ring-2 focus:ring-[#1E3A8A] transition">
          </div>
        </div>

        <!-- Submit Buttons -->
        <div class="flex flex-col sm:flex-row gap-3">
          <button type="submit"
                  class="flex-1 py-2.5 bg-[#1E3A8A] hover:bg-[#172c6e] text-white font-semibold rounded-lg shadow-md hover:shadow-lg transition">
            <i class="fa-solid fa-user-plus mr-2"></i>Create Account
          </button>
          <a href="login.php"
             class="flex-1 py-2.5 bg-gray-200 hover:bg-gray-300 text-gray-800 font-semibold rounded-lg shadow-md hover:shadow-lg transition text-center">
            <i class="fa-solid fa-arrow-left mr-2"></i>Back to Login
          </a>
        </div>
      </form>

      <!-- Footer -->
      <div class="text-center mt-6 pt-4 border-t border-gray-200">
        <p class="text-xs text-gray-600">© 2025 Asset Management System</p>
      </div>
    </div>
  </div>

  <script>
    function togglePassword(fieldId) {
      const input = document.getElementById(fieldId);
      const icon = document.getElementById('toggleIcon-' + fieldId);
      const isHidden = input.type === 'password';

      input.type = isHidden ? 'text' : 'password';
      icon.classList.toggle('fa-eye');
      icon.classList.toggle('fa-eye-slash');
    }

    // Form validation
    document.querySelector('form').addEventListener('submit', function(e) {
      const password = document.getElementById('password').value;
      const confirmPassword = document.getElementById('confirm_password').value;

      if (password !== confirmPassword) {
        e.preventDefault();
        alert('Passwords do not match!');
        return false;
      }

      if (password.length < 6) {
        e.preventDefault();
        alert('Password must be at least 6 characters long!');
        return false;
      }
    });
  </script>
</body>
</html>
