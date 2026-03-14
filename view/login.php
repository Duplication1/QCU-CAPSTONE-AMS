<?php
session_start();

// Check for login success
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

// If already logged in, redirect to appropriate dashboard
if (isset($_SESSION['is_logged_in']) && $_SESSION['is_logged_in'] === true && !$showSuccessModal) {
    switch ($_SESSION['role']) {
        case 'Administrator':
            header("Location: Administrator/index.php");
            exit();
        case 'Technician':
            header("Location: Technician/index.php");
            exit();
        case 'Laboratory Staff':
            header("Location: LaboratoryStaff/index.php");
            exit();
        case 'Student':
        case 'Faculty':
            header("Location: StudentFaculty/index.php");
            exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>AMS - Login</title>
  <link rel="stylesheet" href="../assets/css/output.css" />
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" crossorigin="anonymous" />
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;700&display=swap" rel="stylesheet" />
  <style>
    .tab-button {
      transition: all 0.3s ease;
    }
    .tab-button.active {
      background-color: #1E3A8A;
      color: white;
    }
    .tab-button:not(.active) {
      background-color: #E5E7EB;
      color: #4B5563;
    }
    .tab-button:not(.active):hover {
      background-color: #D1D5DB;
    }

       /* Background slideshow layers */
    .bg-slideshow {
      position: fixed;
      top: 0; left: 0;
      width: 100%; height: 100%;
      z-index: -1; /* behind everything */
      background-size: cover;
      background-position: center;
      background-repeat: no-repeat;
      opacity: 0;
      transition: opacity 1.5s ease-in-out;
    }
    .bg-slideshow.active {
      opacity: 1;
    }

    /* Optional overlay tint */
    .bg-overlay {
      position: fixed;
      inset: 0;
      background-color: #694cc9ff;
      opacity: 0.09;
      z-index: 0;
    }

    /* Slideshow indicators */
    .slideshow-indicators {
      position: fixed;
      bottom: 30px;
      left: 50%;
      transform: translateX(-50%);
      display: flex;
      gap: 12px;
      z-index: 10;
    }

    .indicator-dot {
      width: 14px;
      height: 14px;
      border-radius: 50%;
      background-color: rgba(255, 255, 255, 0.5);
      border: 2px solid rgba(255, 255, 255, 0.8);
      cursor: pointer;
      transition: all 0.3s ease;
      box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
    }

    .indicator-dot:hover {
      background-color: rgba(255, 255, 255, 0.7);
      transform: scale(1.2);
    }

    .indicator-dot.active {
      background-color: #1E3A8A;
      border-color: white;
      transform: scale(1.3);
    }
  </style>

<script>

window.onload = function() {
  const images = [
    "../assets/images/loginbg1.jpg",
    "../assets/images/loginbg2.jpg",
    "../assets/images/loginbg3.jpg",
    "../assets/images/loginbg4.jpg",
    "../assets/images/loginbg5.jpg",
    "../assets/images/loginbg6.jpg"
  ];

  let currentIndex = 0;
  let autoPlayInterval;
  const bg1 = document.getElementById('bg1');
  const bg2 = document.getElementById('bg2');
  let activeBg = bg1;
  let inactiveBg = bg2;

  // Set initial background
  bg1.style.backgroundImage = `url('${images[0]}')`;
  bg1.classList.add('active');

  function updateIndicators() {
    document.querySelectorAll('.indicator-dot').forEach((dot, index) => {
      if (index === currentIndex) {
        dot.classList.add('active');
      } else {
        dot.classList.remove('active');
      }
    });
  }

  function changeBackground(index = null) {
    if (index !== null) {
      currentIndex = index;
    } else {
      currentIndex = (currentIndex + 1) % images.length;
    }

    // Prepare the inactive background with the new image
    inactiveBg.style.backgroundImage = `url('${images[currentIndex]}')`;
    
    // Fade out active, fade in inactive
    activeBg.classList.remove('active');
    inactiveBg.classList.add('active');
    
    // Swap references
    [activeBg, inactiveBg] = [inactiveBg, activeBg];
    
    // Update indicators
    updateIndicators();
  }

  function startAutoPlay() {
    autoPlayInterval = setInterval(() => changeBackground(), 5000);
  }

  function stopAutoPlay() {
    clearInterval(autoPlayInterval);
  }

  function goToSlide(index) {
    stopAutoPlay();
    changeBackground(index);
    startAutoPlay();
  }

  // Initialize indicators
  updateIndicators();
  
  // Start auto-play
  startAutoPlay();

  // Expose goToSlide function globally for onclick handlers
  window.goToSlide = goToSlide;
};

</script>

</head>
<body class="relative min-h-screen flex items-center justify-center p-6 bg-gray-100 font-[Poppins]"
      style="background-image: url('../assets/images/loginbg1.jpg'); background-size: cover; background-position: center; background-repeat: no-repeat;">
      <div class="absolute inset-0 bg-[#1E3A8A] opacity-40 z-0"></div>

  <!-- Background layers --> <div id="bg1" class="bg-slideshow active"></div> <div id="bg2" class="bg-slideshow"></div> <div class="bg-overlay"></div>
  
  <!-- Slideshow Indicators -->
  <div class="slideshow-indicators">
    <div class="indicator-dot active" onclick="goToSlide(0)"></div>
    <div class="indicator-dot" onclick="goToSlide(1)"></div>
    <div class="indicator-dot" onclick="goToSlide(2)"></div>
    <div class="indicator-dot" onclick="goToSlide(3)"></div>
    <div class="indicator-dot" onclick="goToSlide(4)"></div>
    <div class="indicator-dot" onclick="goToSlide(5)"></div>
  </div>

  <div class="relative w-full max-w-md">
    <!-- Logo -->
    <div class="absolute -top-12 left-1/2 transform -translate-x-1/2">
      <img src="../assets/images/qcu-logo.png" alt="QCU Logo"
           class="w-20 h-20 rounded-full shadow-lg border-4 border-white dark:border-[#071127]">
    </div>

    <!-- Login Card -->
    <div class="bg-white rounded-xl shadow-2xl p-6 pt-14">
      <!-- Header -->
      <div class="text-center mb-6">
        <h1 class="text-2xl sm:text-3xl font-bold text-[#1E3A8A]">Asset Management System</h1>
        <p class="text-sm font-normal text-black mt-1">Welcome! Please login to continue</p>
      </div>

      <?php
      // Display error message if exists
      if (isset($_SESSION['error_message'])) {
          echo '<div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-700 dark:text-red-400 px-4 py-3 rounded-lg mb-6 text-sm flex items-center gap-2">' .
               '<i class="fa-solid fa-circle-exclamation"></i>' .
               htmlspecialchars($_SESSION['error_message']) . '</div>';
          unset($_SESSION['error_message']);
      }
      
      // Display success message if exists
      if (isset($_SESSION['success'])) {
          echo '<div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-6 text-sm flex items-center gap-2">' .
               '<i class="fa-solid fa-circle-check"></i>' .
               htmlspecialchars($_SESSION['success']) . '</div>';
          unset($_SESSION['success']);
      }
      ?>

      <!-- Login Form -->
      <form action="../controller/login_controller.php" method="POST" class="space-y-5">
        <!-- ID Number -->
        <div>
          <label for="id_number" class="block text-sm font-medium text-black mb-2">
            ID Number <span class="text-red-500">*</span>
          </label>
          <input type="text" id="id_number" name="id_number" required autocomplete="off"
                 placeholder="Enter your ID number"
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

        <!-- Additional Links -->
        <div class="flex flex-col sm:flex-row justify-between items-center gap-2 text-sm">
          <a href="forgot_password.php" class="text-[#1E3A8A] hover:underline">
            <i class="fa-solid fa-key mr-1"></i>Forgot Password?
          </a>
          <a href="register.php" class="text-[#1E3A8A] hover:underline">
            <i class="fa-solid fa-user-plus mr-1"></i>Create Account
          </a>
        </div>
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
        <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-green-100 dark:bg-green-900/30" style="background-color: #dcfce7;">
          <i class="fa-solid fa-check text-3xl text-green-600 dark:text-green-400" style="color: #16a34a;"></i>
        </div>
      </div>
      <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2" style="color: #111827;">Login Successful!</h3>
      <p class="text-gray-800 dark:text-gray-300 mb-4" style="color: #1f2937;">
        Successfully logged in as <span class="font-bold text-green-600" id="userRoleText" style="color: #16a34a;"></span>
      </p>
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
