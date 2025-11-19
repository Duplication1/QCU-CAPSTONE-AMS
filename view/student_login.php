<?php
session_start();

// Check for login success
$showSuccessModal = false;
$redirectUrl = '';
if (isset($_SESSION['login_success']) && $_SESSION['login_success'] === true) {
    $showSuccessModal = true;
    $redirectUrl = $_SESSION['redirect_url'] ?? 'StudentFaculty/index.php';
    unset($_SESSION['login_success']);
    unset($_SESSION['redirect_url']);
}

// Get error message if exists
$error_message = $_SESSION['error_message'] ?? '';
unset($_SESSION['error_message']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>AMS - Student Login</title>
  <link rel="stylesheet" href="../assets/css/output.css">
  <script src="https://cdn.tailwindcss.com"></script>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <!-- Include Poppins font in your <head> if not already included -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@700&display=swap" rel="stylesheet">
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.tailwind.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;700&display=swap" rel="stylesheet">

</head>
<body class="min-h-screen flex items-center justify-center p-6 bg-gray-50 dark:bg-[#0b1220] font-[Poppins]">

  <div class="relative w-full max-w-sm">
    <!-- Logo (fixed, no animation) -->
    <div class="absolute -top-12 left-1/2 transform -translate-x-1/2">
      <img src="../assets/images/qcu-logo.png" alt="QCU Logo"
           class="w-20 h-20 rounded-full shadow-lg border-4 border-white dark:border-[#071127]">
    </div>

    <!-- Login Card -->
    <div class="bg-white dark:bg-[#071127] rounded-xl shadow-2xl p-6 pt-14">
      <!-- Header -->
      <div class="text-center mb-6">
        <h1 class="text-xl sm:text-2xl font-bold text-[#1E3A8A] dark:text-yellow-400">Student Login</h1>
        <p class="text-sm font-normal text-gray-600 dark:text-gray-300">Access your student portal</p>
      </div>

      <!-- Login Form -->
      <form action="../controller/login_controller.php" method="POST" class="space-y-5">
        <input type="hidden" name="login_type" value="student">

        <?php if (!empty($error_message)): ?>
        <!-- Error Message -->
        <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-700 dark:text-red-400 px-4 py-3 rounded-lg text-sm">
          <i class="fa-solid fa-circle-exclamation mr-2"></i>
          <?php echo htmlspecialchars($error_message); ?>
        </div>
        <?php endif; ?>

        <!-- Student Number -->
        <div>
          <label for="id_number" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
            Student Number <span class="text-red-500">*</span>
          </label>
          <input type="text" id="id_number" name="id_number" required autocomplete="off"
                 placeholder="Enter your student number"
                 class="w-full px-4 py-2.5 text-base font-normal rounded-lg border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-[#0b1220] text-gray-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-[#1E3A8A] transition">
        </div>

        <!-- Password -->
        <div class="relative">
          <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
            Password <span class="text-red-500">*</span>
          </label>
          <input type="password" id="password" name="password" required
                 placeholder="Enter your password"
                 class="w-full px-4 py-2.5 pr-10 text-base font-normal rounded-lg border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-[#0b1220] text-gray-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-[#1E3A8A] transition">
          <button type="button" onclick="togglePassword()" class="absolute right-3 top-[42px] text-gray-500 dark:text-gray-400">
            <i id="toggleIcon" class="fa-solid fa-eye"></i>
          </button>
        </div>

        <!-- Submit -->
        <button type="submit"
                class="w-full py-2.5 bg-[#1E3A8A] hover:bg-[#172c6e] text-white font-semibold rounded-lg shadow-md hover:shadow-lg transition">
          Login
        </button>
      </form>

      <!-- Footer -->
      <div class="text-center mt-6 pt-4 border-t border-gray-200 dark:border-gray-600">
        <p class="text-xs text-gray-600 dark:text-gray-400">© 2025 Asset Management System</p>
      </div>
    </div>
  </div>

  <!-- Success Modal -->
  <div id="successModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 <?php echo $showSuccessModal ? '' : 'hidden'; ?>">
    <div class="bg-white dark:bg-[#071127] rounded-xl shadow-2xl p-8 max-w-sm w-full mx-4 text-center">
      <div class="mb-4">
        <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-green-100 dark:bg-green-900/30">
          <i class="fa-solid fa-check text-3xl text-green-600 dark:text-green-400"></i>
        </div>
      </div>
      <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">Login Successful!</h3>
      <p class="text-gray-600 dark:text-gray-300 mb-4">Welcome back, you'll be redirected shortly...</p>
      <div class="flex justify-center">
        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-[#1E3A8A]"></div>
      </div>
    </div>
  </div>

  <script>
    function togglePassword() {
      const input = document.getElementById('password');
      const icon = document.getElementById('toggleIcon');
      const isHidden = input.type === 'password';

      input.type = isHidden ? 'text' : 'password';
      icon.classList.toggle('fa-eye');
      icon.classList.toggle('fa-eye-slash');
    }

    // Auto redirect after successful login
    <?php if ($showSuccessModal): ?>
    setTimeout(function() {
      window.location.href = '<?php echo $redirectUrl; ?>';
    }, 2000);
    <?php endif; ?>
  </script>
</body>
</html>
