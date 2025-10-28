<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AMS - Login</title>
    <link rel="stylesheet" href="../assets/css/output.css">
</head>
<body class="min-h-screen bg-gradient-to-br from-primary to-secondary flex items-center justify-center p-5">
    <div class="w-full max-w-md">
        <div class="bg-white rounded-xl shadow-2xl p-8 md:p-10">
            <!-- Header -->
            <div class="text-center mb-8">
                <div class="mb-4">
                    <svg class="w-16 h-16 mx-auto text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                    </svg>
                </div>
                <h1 class="text-3xl font-bold text-gray-800 mb-2">Asset Management System</h1>
                <p class="text-gray-600 text-sm">Please login to continue</p>
            </div>
            
            <?php
            session_start();
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
                <div>
                    <label for="id_number" class="block text-sm font-semibold text-gray-700 mb-2">
                        Student No. / Employee No.
                    </label>
                    <input type="text" 
                           id="id_number" 
                           name="id_number" 
                           placeholder="Enter your ID number" 
                           required 
                           autocomplete="off"
                           class="w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:outline-none focus:border-primary transition duration-200">
                   
                </div>
                
                <div>
                    <label for="password" class="block text-sm font-semibold text-gray-700 mb-2">
                        Password
                    </label>
                    <input type="password" 
                           id="password" 
                           name="password" 
                           placeholder="Enter your password" 
                           required
                           class="w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:outline-none focus:border-primary transition duration-200">
                </div>
                
                <div>
                    <button type="submit" 
                            class="w-full bg-gradient-to-r from-primary to-secondary text-white font-semibold py-3 px-4 rounded-lg hover:shadow-lg hover:scale-[1.02] active:scale-100 transition duration-200">
                        Login
                    </button>
                </div>
                
                <div class="text-center">
                    <a href="forgot_password.php" class="text-sm text-primary hover:text-secondary hover:underline transition">
                        Forgot Password?
                    </a>
                </div>
            </form>
     
        <!-- Footer -->
        <div class="text-center mt-6">
            <p class="text-white text-sm opacity-90">© 2025 Asset Management System. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
