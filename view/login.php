<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AMS - Login</title>
    <link rel="stylesheet" href="../assets/css/output.css">
</head>
<body class="min-h-screen bg-gradient-to-br from-primary to-secondary flex items-center justify-center p-5">
    <div class="w-full max-w-2xl">
        <div class="bg-white rounded-xl shadow-2xl p-8 md:p-10">
            
            <!-- Header -->
            <div class="text-center mb-10">
                <div class="flex justify-center mb-4">
                    <img src="../assets/images/qcu-logo.png" alt="QCU Logo" class="w-24 h-24">
                </div>
                <h1 class="text-4xl font-bold text-gray-800 mb-2">Asset Management System</h1>
                <p class="text-gray-600">Welcome back</p>
            </div>
            
            <?php
            session_start();
            
            // Redirect if already logged in
            if (isset($_SESSION['is_logged_in']) && $_SESSION['is_logged_in'] === true) {
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
                    case 'Student':
                    case 'Faculty':
                        header("Location: ../view/StudentFaculty/index.php");
                        break;
                }
                exit();
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
                <!-- ID Number -->
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
</body>
</html>