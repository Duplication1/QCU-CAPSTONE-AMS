<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>AMS - Employee Login</title>
  <link rel="stylesheet" href="../assets/css/output.css" />
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" crossorigin="anonymous" />
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;700&display=swap" rel="stylesheet" />
</head>
<body class="min-h-screen flex items-center justify-center p-6 bg-gray-100 font-[Poppins]" style="background-image: url('../assets/images/image 8.png'); background-size: cover; background-position: center; background-repeat: no-repeat;">
  <div class="relative w-full max-w-sm">
    <!-- Logo -->
    <div class="absolute -top-12 left-1/2 transform -translate-x-1/2">
      <img src="../assets/images/qcu-logo.png" alt="QCU Logo"
           class="w-20 h-20 rounded-full shadow-lg border-4 border-white dark:border-[#071127]">
    </div>

    <!-- Login Card -->
    <div class="bg-white rounded-xl shadow-2xl p-6 pt-14">
      <!-- Header -->
      <div class="text-center mb-6">
        <h1 class="text-xl sm:text-2xl font-bold text-[#1E3A8A]">Employee Portal</h1>
        <p class="text-sm font-normal text-black">Welcome back! Please login to continue</p>
      </div>

      <?php
      session_start();
      $showSuccessModal = false;
      $userRole = '';
      $redirectUrl = '';
      if (isset($_SESSION['login_success']) && $_SESSION['login_success'] === true) {
          $showSuccessModal = true;
          $userRole = $_SESSION['role'];
          $redirectUrl = $_SESSION['redirect_url'];
          unset($_SESSION['login_success']);
          unset($_SESSION['redirect_url']);
      }
      if (isset($_SESSION['is_logged_in']) && $_SESSION['is_logged_in'] === true && !$showSuccessModal) {
          $employeeRoles = ['Administrator', 'Technician', 'Laboratory Staff', 'Faculty'];
          if (in_array($_SESSION['role'], $employeeRoles)) {
              switch ($_SESSION['role']) {
                  case 'Administrator':
                      header("Location: ../view/Administrator/index.php");
                      break;
                  case 'Technician':
                      header("Location: ../view/Technician/index.php");
                      break;
                  case 'Laboratory Staff':
                      header("Location: ../view/LaboratoryStaff/index.php");
                      break;
                  case 'Faculty':
                      header("Location: ../view/StudentFaculty/index.php");
                      break;
              }
              exit();
          }
      }
      if (isset($_SESSION['error_message'])) {
          echo '<div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-700 dark:text-red-400 px-4 py-3 rounded-lg mb-6 text-sm flex items-center gap-2">' .
               '<i class="fa-solid fa-circle-exclamation"></i>' .
               htmlspecialchars($_SESSION['error_message']) . '</div>';
          unset($_SESSION['error_message']);
      }
      if (isset($_SESSION['success'])) {
          echo '<div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-6 text-sm flex items-center gap-2">' .
               '<i class="fa-solid fa-circle-check"></i>' .
               htmlspecialchars($_SESSION['success']) . '</div>';
          unset($_SESSION['success']);
      }
      ?>

      <!-- Login Form -->
      <form action="../controller/login_controller.php" method="POST" class="space-y-5">
        <input type="hidden" name="login_type" value="employee">

        <!-- Employee Number -->
        <div>
          <label for="id_number" class="block text-sm font-medium text-black mb-2">
            Employee Number <span class="text-red-500">*</span>
          </label>
          <input type="text" id="id_number" name="id_number" required autocomplete="off"
                 placeholder="Enter your employee number"
                 class="w-full px-4 py-2.5 text-base font-normal rounded-lg border border-gray-300 dark:border-gray-600 bg-gray-100 text-black focus:outline-none focus:ring-2 focus:ring-[#1E3A8A] transition">
        </div>

        <!-- Password -->
        <div class="relative">
          <label for="password" class="block text-sm font-medium text-black mb-2">
            Password <span class="text-red-500">*</span>
          </label>
          <input type="password" id="password" name="password" required
                 placeholder="Enter your password"
                 class="w-full px-4 py-2.5 pr-10 text-base font-normal rounded-lg border border-gray-300 dark:border-gray-600 bg-gray-100 text-black focus:outline-none focus:ring-2 focus:ring-[#1E3A8A] transition">
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
    <div class="bg-white rounded-xl shadow-2xl p-8 max-w-sm w-full mx-4 text-center">
      <div class="mb-4">
        <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-green-100 dark:bg-green-900/30">
          <i class="fa-solid fa-check text-3xl text-green-600 dark:text-green-400"></i>
        </div>
      </div>
      <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">Login Successful!</h3>
      <p class="text-gray-600 dark:text-gray-300 mb-4">Successfully logged in as <span class="font-semibold text-[#1E3A8A] dark:text-yellow-400" id="userRoleText"></span></p>
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

    <?php if ($showSuccessModal): ?>
    document.getElementById('successModal').classList.remove('hidden');
    document.getElementById('userRoleText').textContent = <?php echo json_encode($userRole); ?>;
    setTimeout(function() {
      window.location.href = <?php echo json_encode($redirectUrl); ?>;
    }, 2000);
    <?php endif; ?>
  </script>
</body>
</html>
