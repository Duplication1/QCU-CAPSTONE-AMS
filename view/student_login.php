<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AMS - Student Login</title>
    <link rel="stylesheet" href="../assets/css/output.css">
</head>
<body class="min-h-screen flex items-center justify-center p-5" style="background: radial-gradient(circle at top left, #dbeafe, #fef9c3 50%, #fecaca);">
    <div class="w-full max-w-md">
        <div class="bg-white rounded-xl shadow-2xl p-8 md:p-10">
            
            <!-- Header -->
            <div class="text-center mb-8">
                <div class="flex justify-center mb-4">
                    <img src="../assets/images/qcu-logo.png" alt="QCU Logo" class="w-20 h-20">
                </div>
                <h1 class="text-3xl font-bold text-gray-800 mb-1">Student Login</h1>
                <p class="text-gray-600 text-sm">Access your student portal</p>
            </div>
            
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
            
            // Redirect if already logged in as student
            if (isset($_SESSION['is_logged_in']) && $_SESSION['is_logged_in'] === true && !$showSuccessModal) {
                if ($_SESSION['role'] === 'Student') {
                    header("Location: ../view/StudentFaculty/index.php");
                    exit();
                }
            }
            
            // Display error messages
            if (isset($_SESSION['error'])) {
                echo '<div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6 text-sm">' . 
                     htmlspecialchars($_SESSION['error']) . '</div>';
                unset($_SESSION['error']);
            }
            if (isset($_SESSION['success'])) {
                echo '<div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-6 text-sm">' . 
                     htmlspecialchars($_SESSION['success']) . '</div>';
                unset($_SESSION['success']);
            }
            ?>
            
            <!-- Login Form -->
            <form action="../controller/login_controller.php" method="POST" class="space-y-6">
                <input type="hidden" name="login_type" value="student">
                
                <!-- Student Number -->
                <div>
                    <label for="id_number" class="block text-sm font-semibold text-gray-700 mb-2">
                        Student Number <span class="text-red-500">*</span>
                    </label>
                    <input type="text" 
                           id="id_number" 
                           name="id_number" 
                           placeholder="Enter your student number" 
                           required 
                           autocomplete="off"
                           class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 transition duration-200">
                </div>

                <!-- Password -->
                <div>
                    <label for="password" class="block text-sm font-semibold text-gray-700 mb-2">
                        Password <span class="text-red-500">*</span>
                    </label>
                    <input type="password" 
                           id="password" 
                           name="password" 
                           placeholder="Enter your password" 
                           required
                           class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 transition duration-200">
                </div>

                <!-- Submit Button -->
                <div>
                    <button type="submit" 
                            class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-3 px-4 rounded-lg transition duration-200 shadow-md hover:shadow-lg">
                        Login
                    </button>
                </div>
            </form>

            <!-- Footer -->
            <div class="text-center mt-8 pt-6 border-t border-gray-200">
                <p class="text-gray-600 text-xs">© 2025 Asset Management System</p>
            </div>

        </div>
    </div>

    <!-- Success Modal -->
    <div id="successModal" class="hidden fixed inset-0 z-50 flex items-center justify-center">
        <div class="absolute inset-0 bg-black opacity-50"></div>
        <div class="relative bg-white rounded-xl shadow-2xl p-8 max-w-md w-full mx-4 transform transition-all">
            <!-- Success Icon -->
            <div class="flex justify-center mb-4">
                <div class="bg-green-100 rounded-full p-3">
                    <svg class="w-16 h-16 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
            
            <!-- Success Message -->
            <div class="text-center">
                <h3 class="text-2xl font-bold text-gray-800 mb-2">Login Successful!</h3>
                <p class="text-gray-600 mb-6">
                    Successfully logged in as <span class="font-semibold text-blue-600" id="userRoleText"></span>
                </p>
                <div class="flex items-center justify-center text-sm text-gray-500">
                    <svg class="animate-spin h-5 w-5 mr-2 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Redirecting to dashboard...
                </div>
            </div>
        </div>
    </div>

    <?php if ($showSuccessModal): ?>
    <script>
        // Show the success modal
        document.getElementById('successModal').classList.remove('hidden');
        document.getElementById('userRoleText').textContent = <?php echo json_encode($userRole); ?>;
        
        // Redirect after 2 seconds
        setTimeout(function() {
            window.location.href = <?php echo json_encode($redirectUrl); ?>;
        }, 2000);
    </script>
    <?php endif; ?>
</body>
</html>
