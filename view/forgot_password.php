<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>AMS - Forgot Password</title>
  <link rel="stylesheet" href="../assets/css/output.css" />
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" crossorigin="anonymous" />
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;700&display=swap" rel="stylesheet" />
</head>
<body class="min-h-screen flex items-center justify-center p-6 bg-gray-100 font-[Poppins]" style="background-image: url('../assets/images/image 7.png'); background-size: cover; background-position: center; background-repeat: no-repeat;">
  <div class="relative w-full max-w-md">
    <!-- Logo -->
    <div class="absolute -top-12 left-1/2 transform -translate-x-1/2">
      <img src="../assets/images/qcu-logo.png" alt="QCU Logo"
           class="w-20 h-20 rounded-full shadow-lg border-4 border-white dark:border-[#071127]">
    </div>

    <!-- Forgot Password Card -->
    <div class="bg-white rounded-xl shadow-2xl p-6 pt-14">
      <!-- Header -->
      <div class="text-center mb-6">
        <h1 class="text-2xl sm:text-3xl font-bold text-[#1E3A8A]">Forgot Password</h1>
        <p class="text-sm font-normal text-black mt-1">Recover your account using security questions</p>
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

      <!-- Step 1: Enter ID Number -->
      <div id="step1" class="<?php echo !isset($_SESSION['reset_user_id']) ? '' : 'hidden'; ?>">
        <form action="../controller/forgot_password_controller.php" method="POST" class="space-y-5">
          <input type="hidden" name="step" value="1">
          
          <div>
            <label for="id_number" class="block text-sm font-medium text-black mb-2">
              Enter Your ID Number <span class="text-red-500">*</span>
            </label>
            <input type="text" id="id_number" name="id_number" required autocomplete="off"
                   placeholder="Enter your ID number"
                   class="w-full px-4 py-2.5 text-base font-normal rounded-lg border border-gray-300 bg-gray-100 text-black focus:outline-none focus:ring-2 focus:ring-[#1E3A8A] transition">
          </div>

          <button type="submit"
                  class="w-full py-2.5 bg-[#1E3A8A] hover:bg-[#172c6e] text-white font-semibold rounded-lg shadow-md hover:shadow-lg transition">
            Continue
          </button>

          <div class="text-center">
            <a href="login.php" class="text-sm text-[#1E3A8A] hover:underline">
              <i class="fa-solid fa-arrow-left mr-1"></i>Back to Login
            </a>
          </div>
        </form>
      </div>

      <!-- Step 2: Answer Security Questions -->
      <div id="step2" class="<?php echo isset($_SESSION['reset_user_id']) && !isset($_SESSION['reset_verified']) ? '' : 'hidden'; ?>">
        <form action="../controller/forgot_password_controller.php" method="POST" class="space-y-5">
          <input type="hidden" name="step" value="2">
          
          <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4">
            <p class="text-sm text-blue-800">
              <i class="fa-solid fa-info-circle mr-2"></i>
              Please answer your security questions to verify your identity.
            </p>
          </div>

          <?php if (isset($_SESSION['security_question_1'])): ?>
          <div>
            <label for="security_answer_1" class="block text-sm font-medium text-black mb-2">
              <?php echo htmlspecialchars($_SESSION['security_question_1']); ?> <span class="text-red-500">*</span>
            </label>
            <input type="text" id="security_answer_1" name="security_answer_1" required
                   placeholder="Your answer"
                   class="w-full px-4 py-2.5 text-base font-normal rounded-lg border border-gray-300 bg-gray-100 text-black focus:outline-none focus:ring-2 focus:ring-[#1E3A8A] transition">
          </div>
          <?php endif; ?>

          <?php if (isset($_SESSION['security_question_2'])): ?>
          <div>
            <label for="security_answer_2" class="block text-sm font-medium text-black mb-2">
              <?php echo htmlspecialchars($_SESSION['security_question_2']); ?> <span class="text-red-500">*</span>
            </label>
            <input type="text" id="security_answer_2" name="security_answer_2" required
                   placeholder="Your answer"
                   class="w-full px-4 py-2.5 text-base font-normal rounded-lg border border-gray-300 bg-gray-100 text-black focus:outline-none focus:ring-2 focus:ring-[#1E3A8A] transition">
          </div>
          <?php endif; ?>

          <button type="submit"
                  class="w-full py-2.5 bg-[#1E3A8A] hover:bg-[#172c6e] text-white font-semibold rounded-lg shadow-md hover:shadow-lg transition">
            Verify Answers
          </button>

          <div class="text-center">
            <a href="forgot_password.php?cancel=1" class="text-sm text-gray-600 hover:underline">
              <i class="fa-solid fa-times mr-1"></i>Cancel
            </a>
          </div>
        </form>
      </div>

      <!-- Step 3: Reset Password -->
      <div id="step3" class="<?php echo isset($_SESSION['reset_verified']) && $_SESSION['reset_verified'] === true ? '' : 'hidden'; ?>">
        <form action="../controller/forgot_password_controller.php" method="POST" class="space-y-5">
          <input type="hidden" name="step" value="3">
          
          <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-4">
            <p class="text-sm text-green-800">
              <i class="fa-solid fa-check-circle mr-2"></i>
              Identity verified! Please enter your new password.
            </p>
          </div>

          <div class="relative">
            <label for="new_password" class="block text-sm font-medium text-black mb-2">
              New Password <span class="text-red-500">*</span>
            </label>
            <input type="password" id="new_password" name="new_password" required
                   placeholder="Enter new password"
                   class="w-full px-4 py-2.5 pr-10 text-base font-normal rounded-lg border border-gray-300 bg-gray-100 text-black focus:outline-none focus:ring-2 focus:ring-[#1E3A8A] transition">
            <button type="button" onclick="togglePassword('new_password')" class="absolute right-3 top-[42px] text-gray-500">
              <i id="toggleIcon-new_password" class="fa-solid fa-eye"></i>
            </button>
          </div>

          <div class="relative">
            <label for="confirm_password" class="block text-sm font-medium text-black mb-2">
              Confirm New Password <span class="text-red-500">*</span>
            </label>
            <input type="password" id="confirm_password" name="confirm_password" required
                   placeholder="Confirm new password"
                   class="w-full px-4 py-2.5 pr-10 text-base font-normal rounded-lg border border-gray-300 bg-gray-100 text-black focus:outline-none focus:ring-2 focus:ring-[#1E3A8A] transition">
            <button type="button" onclick="togglePassword('confirm_password')" class="absolute right-3 top-[42px] text-gray-500">
              <i id="toggleIcon-confirm_password" class="fa-solid fa-eye"></i>
            </button>
          </div>

          <button type="submit"
                  class="w-full py-2.5 bg-[#1E3A8A] hover:bg-[#172c6e] text-white font-semibold rounded-lg shadow-md hover:shadow-lg transition">
            Reset Password
          </button>
        </form>
      </div>

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

    // Password validation for step 3
    const step3Form = document.querySelector('#step3 form');
    if (step3Form) {
      step3Form.addEventListener('submit', function(e) {
        const password = document.getElementById('new_password').value;
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
    }
  </script>

  <?php
  // Handle cancel action
  if (isset($_GET['cancel'])) {
      unset($_SESSION['reset_user_id']);
      unset($_SESSION['security_question_1']);
      unset($_SESSION['security_question_2']);
      unset($_SESSION['reset_verified']);
      header("Location: login.php");
      exit();
  }
  ?>
</body>
</html>
