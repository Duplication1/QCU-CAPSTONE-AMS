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
                <div class="flex justify-center mb-4">
                    <img src="../assets/images/qcu-logo.png" alt="QCU Logo" class="w-20 h-20">
                </div>
                <h1 class="text-3xl font-bold text-gray-800 mb-2">Login</h1>
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
            <form action="../controller/login_controller.php" method="POST" class="space-y-6 text-left">

                <!-- Student Number -->
                <div>
                    <label for="id_number" class="block text-sm font-semibold text-gray-700 mb-2">
                        Student No. / Employee No. <span class="text-red-500">*</span>
                    </label>
                    <input type="text" 
                           id="id_number" 
                           name="id_number" 
                           placeholder="Enter your ID number" 
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
                            class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-3 px-4 rounded-lg transition duration-200">
                        Submit
                    </button>
                </div>
        </form>

        <!-- Divider -->
            <div class="mt-8 border-t border-gray-200"></div>

     
        <!-- Footer -->
        <div class="text-center mt-6">
            <p class="text-gray-700 text-sm opacity-90">© 2025 Asset Management System. All rights reserved.</p>
        </div>

    </div>
</body>
</html>
