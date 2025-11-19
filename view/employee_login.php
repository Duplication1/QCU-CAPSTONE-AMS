<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AMS - Employee Login</title>
    <link rel="stylesheet" href="../assets/css/output.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer" />
</head>
<body class="min-h-screen flex items-center justify-center p-5" style="background: radial-gradient(circle at top left, #dbeafe, #fef9c3 50%, #fecaca);">
    <div class="w-full max-w-md">
        <div class="bg-white/80 backdrop-blur-xl rounded-2xl shadow-2xl border border-white/20 p-10 md:p-12">
            
            <!-- Header -->
            <div class="text-center mb-10">
                <div class="flex justify-center mb-5">
                    <div class="relative">
                        <div class="absolute inset-0 bg-blue-500/20 rounded-full blur-xl"></div>
                        <img src="../assets/images/qcu-logo.png" alt="QCU Logo" class="w-24 h-24 relative">
                    </div>
                </div>
                <h1 class="text-4xl font-bold bg-gradient-to-r from-blue-600 to-blue-800 bg-clip-text text-transparent mb-2">Employee Portal</h1>
                <p class="text-gray-600 text-sm font-medium">Welcome back! Please login to continue</p>
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
            
            // Redirect if already logged in as employee
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
            
            // Display error messages
            if (isset($_SESSION['error_message'])) {
                echo '<div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6 text-sm flex items-center gap-2">' . 
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
            <form action="../controller/login_controller.php" method="POST" class="space-y-7">
                <input type="hidden" name="login_type" value="employee">
                
                <!-- Employee Number -->
                <div class="relative">
                    <input type="text" 
                           id="id_number" 
                           name="id_number" 
                           placeholder="" 
                           required 
                           autocomplete="off"
                           class="peer w-full px-5 py-4 bg-white/50 border-2 border-gray-200 rounded-xl focus:outline-none focus:border-blue-500 focus:bg-white transition-all duration-300 placeholder-transparent">
                    <label for="id_number" class="absolute left-5 -top-3 bg-white px-2 text-sm font-semibold text-gray-600 transition-all duration-300 peer-placeholder-shown:text-base peer-placeholder-shown:text-gray-400 peer-placeholder-shown:top-4 peer-placeholder-shown:bg-transparent peer-focus:-top-3 peer-focus:text-sm peer-focus:text-blue-600 peer-focus:bg-white">
                        Employee Number
                    </label>
                </div>

                <!-- Password -->
                <div class="relative">
                    <input type="password" 
                           id="password" 
                           name="password" 
                           placeholder="" 
                           required
                           class="peer w-full px-5 py-4 bg-white/50 border-2 border-gray-200 rounded-xl focus:outline-none focus:border-blue-500 focus:bg-white transition-all duration-300 placeholder-transparent">
                    <label for="password" class="absolute left-5 -top-3 bg-white px-2 text-sm font-semibold text-gray-600 transition-all duration-300 peer-placeholder-shown:text-base peer-placeholder-shown:text-gray-400 peer-placeholder-shown:top-4 peer-placeholder-shown:bg-transparent peer-focus:-top-3 peer-focus:text-sm peer-focus:text-blue-600 peer-focus:bg-white">
                        Password
                    </label>
                </div>

                <!-- Submit Button -->
                <div class="pt-2">
                    <button type="submit" 
                            class="w-full bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white font-semibold py-4 px-6 rounded-xl transition-all duration-300 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5">
                        <span class="flex items-center justify-center gap-2">
                            Login
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path>
                            </svg>
                        </span>
                    </button>
                </div>
            </form>

            <!-- Footer -->
            <div class="text-center mt-10 pt-8 border-t border-gray-200/50">
                <p class="text-gray-500 text-xs font-medium">© 2025 Asset Management System</p>
            </div>

        </div>
    </div>

    <!-- Success Modal -->
    <div id="successModal" class="hidden fixed inset-0 z-50 flex items-center justify-center">
        <div class="absolute inset-0 bg-black opacity-50"></div>
        <div class="relative bg-white rounded-xl shadow-2xl p-8 max-w-md w-full mx-4 transform transition-all">
            <!-- Success Icon -->
            <div class="flex justify-center mb-4">
                <div class="bg-blue-100 rounded-full p-3">
                    <svg class="w-16 h-16 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
