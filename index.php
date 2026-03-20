<?php
// Fetch real statistics from database
require_once 'config/config.php';

// Initialize default values
$totalAssets = 0;
$totalRooms = 0;
$activeUsers = 0;

try {
    $dbConfig = Config::database();
    $conn = new mysqli($dbConfig['host'], $dbConfig['username'], $dbConfig['password'], $dbConfig['name']);
    $conn->set_charset('utf8mb4');
    
    // Get total assets count
    $assetQuery = $conn->query("SELECT COUNT(*) as total FROM assets WHERE status != 'Disposed'");
    if ($assetQuery) {
        $assetResult = $assetQuery->fetch_assoc();
        $totalAssets = (int)$assetResult['total'];
    }
    
    // Get total rooms count
    $roomQuery = $conn->query("SELECT COUNT(DISTINCT room_id) as total FROM assets WHERE room_id IS NOT NULL AND room_id != ''");
    if ($roomQuery) {
        $roomResult = $roomQuery->fetch_assoc();
        $totalRooms = (int)$roomResult['total'];
    }
    
    // Get active users count (users with status 'Active')
    $userQuery = $conn->query("SELECT COUNT(*) as total FROM users WHERE status = 'Active'");
    if ($userQuery) {
        $userResult = $userQuery->fetch_assoc();
        $activeUsers = (int)$userResult['total'];
    }
    
    $conn->close();
} catch (Exception $e) {
    // If database connection fails, use default values
    error_log("Index.php database error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QCU Asset Management System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * {
            font-family: 'Poppins', sans-serif;
            transition: background-color 0.3s ease, color 0.3s ease, border-color 0.3s ease;
        }
        
        /* Dark mode variables */
        .dark {
            color-scheme: dark;
        }
        
        .dark body {
            background-color: #0f172a;
            color: #e2e8f0;
        }
        
        .dark nav {
            background-color: #1e293b;
            border-bottom: 1px solid #334155;
        }
        
        .dark .hero-gradient {
            background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
        }
        
        .dark .feature-card,
        .dark .bg-white {
            background-color: #1e293b;
            border-color: #334155;
        }
        
        .dark .text-gray-900 { color: #f1f5f9; }
        .dark .text-gray-800 { color: #e2e8f0; }
        .dark .text-gray-700 { color: #cbd5e1; }
        .dark .text-gray-600 { color: #94a3b8; }
        .dark .text-gray-500 { color: #64748b; }
        
        .dark .text-blue-900 { color: #fde047; } /* Yellow-300 for QCU AMS in dark mode */
        
        .dark .bg-gray-50 { background-color: #0f172a; }
        .dark .bg-gray-100 { background-color: #1e293b; }
        .dark .bg-gray-200 { background-color: #334155; }
        
        .dark .from-blue-50 { --tw-gradient-from: #1e293b; }
        .dark .to-indigo-50 { --tw-gradient-to: #0f172a; }
        
        .dark .team-section {
            background: linear-gradient(to bottom right, #1e293b, #0f172a);
        }
        
        .dark .border-gray-100 { border-color: #334155; }
        .dark .border-gray-200 { border-color: #475569; }
        .dark .border-gray-300 { border-color: #475569; }
        
        .dark .shadow-lg,
        .dark .shadow-md,
        .dark .shadow-xl,
        .dark .shadow-2xl {
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.5), 0 10px 10px -5px rgba(0, 0, 0, 0.3);
        }
        
        .dark .feature-card:hover {
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.5);
        }
        
        /* Dark mode toggle button */
        .theme-toggle {
            position: relative;
            width: 60px;
            height: 30px;
            background: #cbd5e1;
            border-radius: 50px;
            cursor: pointer;
            transition: background 0.3s ease;
        }
        
        .dark .theme-toggle {
            background: #475569;
        }
        
        .theme-toggle-slider {
            position: absolute;
            top: 3px;
            left: 3px;
            width: 24px;
            height: 24px;
            background: white;
            border-radius: 50%;
            transition: transform 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .dark .theme-toggle-slider {
            transform: translateX(30px);
            background: #1e293b;
        }
        
        /* Hero gradient animation */
        @keyframes gradient {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        
        .hero-gradient {
            background: #1E3A8A;
            background-size: 400% 400%;
        }
        
        /* Floating animation */
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
        }
        
        .float-animation {
            animation: float 6s ease-in-out infinite;
        }
        
        /* Fade in animation */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .fade-in-up {
            animation: fadeInUp 1s ease-out;
        }
        
        /* Card hover effect */
        .feature-card {
            transition: all 0.3s ease;
        }
        
        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        }
        
        /* Smooth scroll */
        html {
            scroll-behavior: smooth;
        }
        
        /* Glass effect */
        .glass-effect {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        /* Navbar scroll transition */
        .navbar-scrolled {
            background-color: transparent !important;
            backdrop-filter: none;
            box-shadow: none;
        }
        
        .dark .navbar-scrolled {
            background-color: transparent !important;
            box-shadow: none;
        }
        
        /* Center navbar when scrolled */
        .navbar-scrolled #navbar-container {
            display: flex;
            justify-content: center;
        }
        
        .navbar-scrolled #navbar-content {
            justify-content: center;
            width: auto;
        }
        
        /* Hide left logo when scrolled */
        .navbar-scrolled #navbar-logo-left {
            opacity: 0;
            pointer-events: none;
            position: absolute;
        }
        
        /* Show logo inside menu when scrolled */
        .navbar-scrolled #navbar-logo-inside {
            opacity: 1 !important;
        }
        
        .navbar-scrolled #navbar-menu {
            background-color: #291F8B;
            padding: 8px 24px;
            border-radius: 15px; 
            backdrop-filter: blur(10px);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.3),
                        0 2px 4px -1px rgba(0, 0, 0, 0.2);
        }

        
        .navbar-scrolled .nav-link {
            color: #ffffff !important;
        }
        
        .navbar-scrolled .nav-link:hover {
            color: #fde047 !important;
        }
        
        .navbar-scrolled .nav-login-btn {
            background-color: rgba(255, 255, 255, 0.2) !important;
        }
        
        .navbar-scrolled .nav-login-btn:hover {
            background-color: rgba(255, 255, 255, 0.3) !important;
        }
    </style>
</head>
<body class="bg-gray-50">
    
    <!-- Navigation -->
    <nav id="navbar" class="fixed w-full z-50 bg-transparent transition-all duration-700 ease-out">
         <div id="navbar-container" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 transition-all duration-700 ease-out">
            <div id="navbar-content" class="flex justify-between items-center h-20 transition-all duration-700 ease-out">
                <!-- Logo (separate when not scrolled) -->
                <div id="navbar-logo-left" class="flex items-center gap-3 transition-all duration-700 ease-out">
                    <img src="assets/images/QCU-LOGO.png" alt="QCU Logo" class="h-14 w-14 transition-all duration-700 ease-out">
                    <div>
                        <h1 class="text-xl font-bold text-white logo-text transition-all duration-700 ease-out">QCU AMS</h1>
                        <p class="text-xs text-white logo-text transition-all duration-700 ease-out">Asset Management System</p>
                    </div>
                </div>
                
                <!-- Desktop Menu -->
                <div id="navbar-menu" class="hidden md:flex items-center gap-8 transition-all duration-700 ease-out">
                    <!-- Logo (inside menu when scrolled) -->
                    <div id="navbar-logo-inside" class="flex items-center gap-3 transition-all duration-700 ease-out opacity-0">
                        <img src="assets/images/QCU-LOGO.png" alt="QCU Logo" class="h-10 w-10 transition-all duration-700 ease-out">
                    </div>
                    
                    <a href="#home" class="nav-link text-white hover:text-yellow-300 transition-all duration-300 font-medium">Home</a>
                    <a href="#features" class="nav-link text-white hover:text-yellow-300 transition-all duration-300 font-medium">Features</a>
                    <a href="#about" class="nav-link text-white hover:text-yellow-300 transition-all duration-300 font-medium">About</a>
                    <a href="#contact" class="nav-link text-white hover:text-yellow-300 transition-all duration-300 font-medium">Contact</a>
                    
                    <!-- Dark Mode Toggle -->
                    <button onclick="toggleDarkMode()" class="theme-toggle bg-gray-300 hover:bg-gray-200 transition-all duration-300" aria-label="Toggle dark mode">
                        <div class="theme-toggle-slider">
                            <i class="fa-solid fa-sun text-yellow-500 text-xs dark-mode-icon-light"></i>
                            <i class="fa-solid fa-moon text-blue-400 text-xs dark-mode-icon-dark hidden"></i>
                        </div>
                    </button>
                    
                    <a href="view/login.php" 
                    class="nav-login-btn flex items-center gap-2 text-white px-6 py-2.5 rounded-lg font-medium transition-all duration-300"
                    style="background-color: rgba(41, 31, 139, 0.5);">
                    Login
                    <i class="fa-solid fa-sign-in-alt"></i>
                    </a>
                </div>
                
                <!-- Mobile Menu Button -->
                <button id="mobile-menu-btn" class="md:hidden text-white hover:text-yellow-300 transition-all duration-300">
                    <i class="fa-solid fa-bars text-2xl"></i>
                </button>
            </div>
        </div>
        
        <!-- Mobile Menu -->
        <div id="mobile-menu" class="hidden md:hidden bg-[#291F8B]/90 border-t">
            <div class="px-4 py-4 space-y-3">
                <a href="#home" class="block text-gray-700 hover:text-blue-600 transition-colors font-medium py-2">Home</a>
                <a href="#features" class="block text-gray-700 hover:text-blue-600 transition-colors font-medium py-2">Features</a>
                <a href="#about" class="block text-gray-700 hover:text-blue-600 transition-colors font-medium py-2">About</a>
                <a href="#contact" class="block text-gray-700 hover:text-blue-600 transition-colors font-medium py-2">Contact</a>
                
                <!-- Dark Mode Toggle Mobile -->
                <div class="flex items-center justify-between py-2">
                    <span class="text-gray-700 font-medium">Dark Mode</span>
                    <button onclick="toggleDarkMode()" class="theme-toggle" aria-label="Toggle dark mode">
                        <div class="theme-toggle-slider">
                            <i class="fa-solid fa-sun text-yellow-500 text-xs dark-mode-icon-light"></i>
                            <i class="fa-solid fa-moon text-blue-400 text-xs dark-mode-icon-dark hidden"></i>
                        </div>
                    </button>
                </div>
                
                <a href="view/login.php" class="block bg-blue-600 hover:bg-blue-700 text-white px-6 py-2.5 rounded-lg transition-colors font-medium text-center">
                    <i class="fa-solid fa-sign-in-alt mr-2"></i>Login
                </a>
            </div>
        </div>
    </nav>

<!-- Hero Section -->
<section id="home" 
    class="relative bg-cover bg-center min-h-screen flex items-center justify-center pt-20" 
    style="background-image: url('assets/images/lab.png');">
    
    <!-- Overlay for readability -->
    <div class="absolute inset-0" 
     style="background: linear-gradient(to bottom, rgba(41,31,139,0.5) 0%, rgba(255,255,255,0.4) 100%);">
    </div>

    <!-- Hero Content -->
    <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20">
        <div class="grid md:grid-cols-2 gap-12 items-center">
            
            <!-- Left Content -->
            <div class="text-white fade-in-up">
                <h1 class="text-5xl md:text-6xl font-bold mb-6 leading-tight">
                    Manage Your Assets <span class="text-yellow-300">Efficiently</span>
                </h1>
                <p class="text-xl mb-8 text-blue-100">
                    Streamline asset tracking, maintenance, and reporting with Quezon City University's comprehensive Asset Management System.
                </p>
                <div class="flex flex-wrap gap-4">
                    <a href="view/login.php" 
                    class="bg-[#291F8B] text-white px-8 py-4 rounded-lg font-semibold transition-all transform hover:scale-105 shadow-lg hover:bg-[#1E40AF]">
                        <i class="fa-solid fa-rocket mr-2"></i>Get Started
                    </a>    

                    <a href="#features" 
                    class="text-white px-8 py-4 rounded-lg font-semibold transition-alltransform hover:scale-105 shadow-lg hover:bg-gray-700"
                    style="background-color: rgba(255, 0, 0, 0.7);">
                    Learn More
                    </a>

                </div>

                <!-- Stats -->
                <div class="grid grid-cols-3 gap-6 mt-12">
                    <div class="text-center">
                        <div class="text-3xl font-bold text-yellow-300"><?php echo number_format($totalAssets); ?></div>
                        <div class="text-sm text-blue-100">Assets Tracked</div>
                    </div>
                    <div class="text-center">
                        <div class="text-3xl font-bold text-yellow-300"><?php echo number_format($totalRooms); ?></div>
                        <div class="text-sm text-blue-100">Rooms Managed</div>
                    </div>
                    <div class="text-center">
                        <div class="text-3xl font-bold text-yellow-300">24/7</div>
                        <div class="text-sm text-blue-100">Monitoring</div>
                    </div>
                </div>
            </div>
            
            <!-- Right Content - Illustration -->
            <div class="hidden md:block float-animation">
                <div class="relative">
                    <div class="absolute inset-0 bg-blue-400 rounded-full blur-3xl opacity-30"></div>
                    <img src="assets/images/QCU-LOGO.png" alt="Asset Management" class="relative w-full max-w-sm mx-auto drop-shadow-2xl">
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Features Section -->
<section id="features" class="py-20 bg-white">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    
<!-- Section Header -->
<div class="text-center mb-16 mt-6">
  <h2 class="text-4xl font-bold text-gray-900 mb-4">Features</h2>
  <p class="text-xl text-gray-600">Everything you need to manage your assets effectively</p>
</div>
    
    <!-- Feature Cards Grid -->
    <div class="grid md:grid-cols-3 gap-10">
      
      <!-- Feature 1 -->
      <div class="bg-white p-10 rounded-xl shadow-lg hover:shadow-xl transition-all h-64">
        <div class="w-16 h-16 flex items-center justify-center mb-6">
        <img src="assets/images/folder.svg" alt="Folder Icon" class="h-12 w-12">
        </div>
        <h3 class="text-xl font-bold text-gray-900 mb-4">Asset Tracking</h3>
        <p class="text-gray-600 text-sm">
          Track all your assets in real-time with comprehensive details including location, condition, and maintenance history.
        </p>
      </div>
      
      <!-- Feature 2 -->
      <div class="bg-white p-10 rounded-xl shadow-lg hover:shadow-xl transition-all h-64">
        <div class="w-16 h-16 flex items-center justify-center mb-6">
        <img src="assets/images/pchealth.svg" alt="pchealth Icon" class="h-12 w-12">
        </div>
        <h3 class="text-xl font-bold text-gray-900 mb-4">PC Health Monitor</h3>
        <p class="text-gray-600 text-sm">
          Monitor computer systems health, performance metrics, and receive alerts for potential issues before they escalate.
        </p>
      </div>
      
      <!-- Feature 3 -->
      <div class="bg-white p-10 rounded-xl shadow-lg hover:shadow-xl transition-all h-64">
        <div class="w-16 h-16 flex items-center justify-center mb-6">
        <img src="assets/images/ticket.svg" alt="Ticket Icon" class="h-12 w-12">
        </div>

        <h3 class="text-xl font-bold text-gray-900 mb-4">Ticket Management</h3>
        <p class="text-gray-600 text-sm">
          Streamline issue reporting and resolution with an integrated ticket management system for maintenance requests.
        </p>
      </div>
      
      <!-- Feature 4 -->
      <div class="bg-white p-10 rounded-xl shadow-lg hover:shadow-xl transition-all h-64">
        <div class="w-16 h-16 flex items-center justify-center mb-6">
        <img src="assets/images/borrow.svg" alt="Borrow Icon" class="h-12 w-12">
        </div>
        <h3 class="text-xl font-bold text-gray-900 mb-4">Borrowing System</h3>
        <p class="text-gray-600 text-sm">
          Manage asset borrowing with automated approval workflows, tracking, and return notifications.
        </p>
      </div>
      
      <!-- Feature 5 -->
      <div class="bg-white p-10 rounded-xl shadow-lg hover:shadow-xl transition-all h-64">
        <div class="w-16 h-16 flex items-center justify-center mb-6">
        <img src="assets/images/analytics.svg" alt="Analytics Icon" class="h-12 w-12">
        </div>

        <h3 class="text-xl font-bold text-gray-900 mb-4">Analytics & Reports</h3>
        <p class="text-gray-600 text-sm">
          Generate detailed reports and analytics to make data-driven decisions about your assets and resources.
        </p>
      </div>
      
      <!-- Feature 6 -->
     <div class="bg-white p-10 rounded-xl shadow-lg hover:shadow-xl transition-all h-64">
        <div class="w-16 h-16 flex items-center justify-center mb-6">
        <img src="assets/images/qr.svg" alt="QR Icon" class="h-12 w-12">
        </div>

        <h3 class="text-xl font-bold text-gray-900 mb-4">QR Code Integration</h3>
        <p class="text-gray-600 text-sm">
          Quick asset identification and tracking using QR codes for faster inventory management and audits.
        </p>
      </div>
      
    </div>
  </div>
</section>

<!-- About Section -->
<section id="about" 
    class="relative bg-cover bg-center min-h-screen flex items-center justify-center" 
    style="background-image: url('assets/images/about.jpg');">
    
        <!-- Overlay for readability -->
    <div class="absolute inset-0" 
     style="background: linear-gradient(to bottom, rgba(41,31,139,0.5) 0%, rgba(255,255,255,0.4) 100%);">
    </div>

  <!-- Content -->
  <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="grid md:grid-cols-2 gap-12 items-center">
      
      <!-- Left Column: Text -->
      <div class="text-white">
        <h2 class="text-4xl font-bold mb-6">About QCU Asset Management System</h2>
        <p class="text-lg mb-6 leading-relaxed">
          The Quezon City University Asset Management System is a comprehensive solution designed to streamline 
          the tracking, maintenance, and management of all university assets.
        </p>
        <p class="text-lg mb-6 leading-relaxed">
          Our system provides real-time monitoring, automated workflows, and detailed reporting capabilities 
          to ensure efficient resource utilization and accountability.
        </p>
        
        <div class="space-y-4">
          <div class="flex items-start gap-4">
    <div class="bg-blue-600/70 w-12 h-12 rounded-lg flex items-center justify-center flex-shrink-0">
        <i class="fa-solid fa-check text-white text-xl"></i>
    </div>

            <div>
              <h4 class="font-semibold mb-1">Real-time Tracking</h4>
              <p class="text-gray-200">Monitor all assets across multiple locations in real-time</p>
            </div>
          </div>
          
          <div class="flex items-start gap-4">
                <div class="bg-blue-600/70 w-12 h-12 rounded-lg flex items-center justify-center flex-shrink-0">
                <i class="fa-solid fa-check text-white text-xl"></i>
                </div>

            <div>
              <h4 class="font-semibold mb-1">Automated Workflows</h4>
              <p class="text-gray-200">Streamline approval processes and notifications</p>
            </div>
            </div>
          
          <div class="flex items-start gap-4">
            <div class="bg-blue-600/70 w-12 h-12 rounded-lg flex items-center justify-center flex-shrink-0">
            <i class="fa-solid fa-check text-white text-xl"></i>
            </div>

            <div>
              <h4 class="font-semibold mb-1">Comprehensive Reporting</h4>
              <p class="text-gray-200">Generate detailed reports for better decision making</p>
            </div>
          </div>
        </div>
      </div>
      
      <!-- Right Column: Stats -->
      <div class="grid grid-cols-2 gap-4">
        <div class="bg-gray-300/70 text-black p-8 rounded-xl">
            <div class="w-16 h-16 flex items-center justify-center mb-6">
            <img src="assets/images/people.svg" alt="Folder Icon" class="h-14 w-14">
        </div>
        <h3 class="text-3xl font-bold mb-2"><?php echo number_format($activeUsers); ?></h3>
          <p class="text-gray-black">Active Users</p>
        </div>

        <div class="bg-gray-300/70 text-black p-8 rounded-xl">
            <div class="w-16 h-16 flex items-center justify-center mb-6">
            <img src="assets/images/bldg.svg" alt="Folder Icon" class="h-14 w-14">
        </div>
        <h3 class="text-3xl font-bold mb-2">4</h3>
        <p class="text-black">Buildings</p>
      </div>

        <div class="bg-gray-300/70 text-black p-8 rounded-xl">
            <div class="w-16 h-16 flex items-center justify-center mb-6">
            <img src="assets/images/monitor.svg" alt="Folder Icon" class="h-14 w-14">
        </div>
          <h3 class="text-3xl font-bold mb-2">972</h3>
          <p class="text-black">Computers</p>
        </div>
        <div class="bg-gray-300/70 text-black p-8 rounded-xl">
            <div class="w-16 h-16 flex items-center justify-center mb-6">
            <img src="assets/images/hand.svg" alt="Folder Icon" class="h-14 w-14">
        </div>
          <h3 class="text-3xl font-bold mb-2">95%</h3>
          <p class="text-black">Satisfaction</p>
        </div>
      </div>
    </div>
  </div>
</section>


<!-- User Roles Section -->
<section class="py-20 bg-white">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="text-center mb-16">
      <h2 class="text-4xl font-bold text-gray-900 mb-4">Access for Everyone</h2>
      <p class="text-xl text-gray-600">Different portals for different user types</p>
    </div>
    
    <div class="grid md:grid-cols-4 gap-6">

      <!-- Administrator -->
      <div class="text-white rounded-xl shadow-lg flex justify-between items-center p-8 aspect-square relative transition-transform duration-300 hover:scale-105 hover:shadow-2xl"
           style="background: linear-gradient(to bottom right, #5E93F2, #AFCBF9);">
        <div class="flex-1">
          <h3 class="text-2xl font-bold mb-2 text-black">Administrator</h3>
          <p class="text-black mb-4">Full system control and management</p>
          <a href="view/login.php" 
             class="inline-block px-6 py-2 rounded-lg font-semibold transition-colors"
             style="background-color: rgba(255,255,255,0.8); color: black;">
            Login <i class="fa-solid fa-sign-in-alt"></i>
          </a>
        </div>
        <div class="absolute top-16 right-0">
          <img src="assets/images/admin.svg" alt="Administrator Icon" class="w-25 h-25 object-contain opacity-90">
        </div>
      </div>

      <!-- Laboratory Staff -->
      <div class="text-white rounded-xl shadow-lg relative p-8 aspect-square transition-transform duration-300 hover:scale-105 hover:shadow-2xl"
           style="background: linear-gradient(to bottom right, rgba(94,147,242,0.8), #AFCBF9);">
        <div class="flex-1"><br>
          <h3 class="text-2xl font-bold mb-2 text-black">Laboratory Staff</h3>
          <p class="text-black mb-4">Asset and borrowing management</p>
          <a href="view/login.php" 
             class="inline-block px-6 py-2 rounded-lg font-semibold transition-colors"
             style="background-color: rgba(255,255,255,0.8); color: black;">
            Login <i class="fa-solid fa-sign-in-alt"></i>
          </a>
        </div>
        <div class="absolute top-16 right-0">
          <img src="assets/images/lab.svg" alt="Laboratory Staff Icon" class="w-25 h-25 object-contain opacity-90">
        </div>
      </div>

      <!-- Technician -->
      <div class="text-white rounded-xl shadow-lg relative p-8 aspect-square transition-transform duration-300 hover:scale-105 hover:shadow-2xl"
           style="background: linear-gradient(to bottom right, rgba(94,147,242,0.6), #AFCBF9);">
        <div class="flex-1"><br>
          <h3 class="text-2xl font-bold mb-2 text-black">Technician</h3>
          <p class="text-black mb-4">Ticket and maintenance handling</p>
          <a href="view/login.php" 
             class="inline-block px-6 py-2 rounded-lg font-semibold transition-colors"
             style="background-color: rgba(255,255,255,0.8); color: black;">
            Login <i class="fa-solid fa-sign-in-alt"></i>
          </a>
        </div>
        <div class="absolute top-16 right-0">
          <img src="assets/images/tech.svg" alt="Technician Icon" class="w-25 h-25 object-contain opacity-90">
        </div>
      </div>

      <!-- Student/Faculty -->
      <div class="text-white rounded-xl shadow-lg relative p-8 aspect-square transition-transform duration-300 hover:scale-105 hover:shadow-2xl"
           style="background: linear-gradient(to bottom right, rgba(94,147,242,0.4), #AFCBF9);">
        <div class="flex-1"><br>
          <h3 class="text-2xl font-bold mb-2 text-black">Student/Faculty</h3>
          <p class="text-black mb-4">Asset borrowing and requests</p>
          <a href="view/login.php" 
             class="inline-block px-6 py-2 rounded-lg font-semibold transition-colors"
             style="background-color: rgba(255,255,255,0.8); color: black;">
            Login <i class="fa-solid fa-sign-in-alt"></i>
          </a>
        </div>
        <div class="absolute top-16 right-0">
          <img src="assets/images/stud.svg" alt="Student/Faculty Icon" class="w-25 h-25 object-contain opacity-90">
        </div>
      </div>

    </div>
  </div>
</section>


    <!-- Our Team Section -->
    <section id="team" class="team-section py-20 bg-gradient-to-br from-blue-50 to-indigo-50 overflow-hidden">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-4xl font-bold text-gray-900 mb-4">Our Team</h2>
                <p class="text-xl text-gray-600">Meet the talented developers behind QCU Asset Management System</p>
            </div>
            
            <!-- Team Slider Container -->
            <div class="relative max-w-4xl mx-auto">
                <div class="overflow-visible py-8">
                    <div id="teamSlider" class="flex items-center justify-center transition-all duration-500 ease-in-out relative" style="height: 350px;">
                        <!-- Team Member 1 -->
                        <div class="team-slide absolute transition-all duration-500" data-index="0" style="width: 240px;">
                            <div class="bg-white rounded-2xl shadow-xl p-6 text-center border-2 border-blue-400">
                                <div class="w-32 h-32 mx-auto mb-4 rounded-2xl overflow-hidden">
                                    <img src="assets/images/sese.jpg" alt="Sese" class="w-full h-full object-cover">
                                </div>
                                <h3 class="text-lg font-bold text-gray-900 mb-1">Sese</h3>
                                <p class="text-blue-600 font-semibold text-sm mb-1">Project Manager</p>
                                <p class="text-gray-600 text-xs"></p>
                            </div>
                        </div>
                        
                        <!-- Team Member 2 -->
                        <div class="team-slide absolute transition-all duration-500" data-index="1" style="width: 240px;">
                            <div class="bg-white rounded-2xl shadow-xl p-6 text-center border-2 border-blue-400">
                                <div class="w-32 h-32 mx-auto mb-4 rounded-2xl overflow-hidden">
                                    <img src="assets/images/gamot.jpg" alt="Gamot" class="w-full h-full object-cover">
                                </div>
                                <h3 class="text-lg font-bold text-gray-900 mb-1">Gamot</h3>
                                <p class="text-blue-600 font-semibold text-sm mb-1">Main Developer</p>
                                <p class="text-gray-600 text-xs">Full Stack Developer</p>
                            </div>
                        </div>
                        
                        <!-- Team Member 3 -->
                        <div class="team-slide absolute transition-all duration-500" data-index="2" style="width: 240px;">
                            <div class="bg-white rounded-2xl shadow-xl p-6 text-center border-2 border-blue-400">
                                <div class="w-32 h-32 mx-auto mb-4 rounded-2xl overflow-hidden">
                                    <img src="assets/images/albason.jpg" alt="Albason" class="w-full h-full object-cover">
                                </div>
                                <h3 class="text-lg font-bold text-gray-900 mb-1">Albason</h3>
                                <p class="text-blue-600 font-semibold text-sm mb-1">Main Developer</p>
                                <p class="text-gray-600 text-xs">Full Stack Developer</p>
                            </div>
                        </div>
                        
                        <!-- Team Member 4 -->
                        <div class="team-slide absolute transition-all duration-500" data-index="3" style="width: 240px;">
                            <div class="bg-white rounded-2xl shadow-xl p-6 text-center border-2 border-blue-400">
                                <div class="w-32 h-32 mx-auto mb-4 rounded-2xl overflow-hidden">
                                    <img src="assets/images/paeste.png" alt="Paeste" class="w-full h-full object-cover">
                                </div>
                                <h3 class="text-lg font-bold text-gray-900 mb-1">Paeste</h3>
                                <p class="text-blue-600 font-semibold text-sm mb-1">Member</p>
                                <p class="text-gray-600 text-xs">Assistant Project Manager</p>
                            </div>
                        </div>
                        
                        <!-- Team Member 5 -->
                        <div class="team-slide absolute transition-all duration-500" data-index="4" style="width: 240px;">
                            <div class="bg-white rounded-2xl shadow-xl p-6 text-center border-2 border-blue-400">
                                <div class="w-32 h-32 mx-auto mb-4 rounded-2xl overflow-hidden">
                                    <img src="assets/images/bangayan.jpg" alt="Bangayan" class="w-full h-full object-cover">
                                </div>
                                <h3 class="text-lg font-bold text-gray-900 mb-1">Bangayan</h3>
                                <p class="text-blue-600 font-semibold text-sm mb-1">Member</p>
                                <p class="text-gray-600 text-xs">Researcher</p>
                            </div>
                        </div>
                        
                        <!-- Team Member 6 -->
                        <div class="team-slide absolute transition-all duration-500" data-index="5" style="width: 240px;">
                            <div class="bg-white rounded-2xl shadow-xl p-6 text-center border-2 border-blue-400">
                                <div class="w-32 h-32 mx-auto mb-4 rounded-2xl overflow-hidden">
                                    <img src="assets/images/cacam.jpeg" alt="Cacam" class="w-full h-full object-cover">
                                </div>
                                <h3 class="text-lg font-bold text-gray-900 mb-1">Cacam</h3>
                                <p class="text-blue-600 font-semibold text-sm mb-1">Member</p>
                                <p class="text-gray-600 text-xs">Documentation</p>
                            </div>
                        </div>
                        
                        <!-- Team Member 7 -->
                        <div class="team-slide absolute transition-all duration-500" data-index="6" style="width: 240px;">
                            <div class="bg-white rounded-2xl shadow-xl p-6 text-center border-2 border-blue-400">
                                <div class="w-32 h-32 mx-auto mb-4 rounded-2xl overflow-hidden">
                                    <img src="assets/images/cawile.jpg" alt="Cawile" class="w-full h-full object-cover">
                                </div>
                                <h3 class="text-lg font-bold text-gray-900 mb-1">Cawile</h3>
                                <p class="text-blue-600 font-semibold text-sm mb-1">Member</p>
                                <p class="text-gray-600 text-xs">Documentation</p>
                            </div>
                        </div>
                        
                        <!-- Team Member 8 -->
                        <div class="team-slide absolute transition-all duration-500" data-index="7" style="width: 240px;">
                            <div class="bg-white rounded-2xl shadow-xl p-6 text-center border-2 border-blue-400">
                                <div class="w-32 h-32 mx-auto mb-4 rounded-2xl overflow-hidden">
                                    <img src="assets/images/estabillo.jpg" alt="Estabillo" class="w-full h-full object-cover">
                                </div>
                                <h3 class="text-lg font-bold text-gray-900 mb-1">Estabillo</h3>
                                <p class="text-blue-600 font-semibold text-sm mb-1">Member</p>
                                <p class="text-gray-600 text-xs">UI/UX Designer</p>
                            </div>
                        </div>
                        
                        <!-- Team Member 9 -->
                        <div class="team-slide absolute transition-all duration-500" data-index="8" style="width: 240px;">
                            <div class="bg-white rounded-2xl shadow-xl p-6 text-center border-2 border-blue-400">
                                <div class="w-32 h-32 mx-auto mb-4 rounded-2xl overflow-hidden">
                                    <img src="assets/images/peralta.jpeg" alt="Peralta" class="w-full h-full object-cover">
                                </div>
                                <h3 class="text-lg font-bold text-gray-900 mb-1">Peralta</h3>
                                <p class="text-blue-600 font-semibold text-sm mb-1">Member</p>
                                <p class="text-gray-600 text-xs">Researcher  </p>
                            </div>
                        </div>
                        
                        <!-- Team Member 10 -->
                        <div class="team-slide absolute transition-all duration-500" data-index="9" style="width: 240px;">
                            <div class="bg-white rounded-2xl shadow-xl p-6 text-center border-2 border-blue-400">
                                <div class="w-32 h-32 mx-auto mb-4 rounded-2xl overflow-hidden">
                                    <img src="assets/images/ronquillo.jpg" alt="Ronquillo" class="w-full h-full object-cover">
                                </div>
                                <h3 class="text-lg font-bold text-gray-900 mb-1">Ronquillo</h3>
                                <p class="text-blue-600 font-semibold text-sm mb-1">Member</p>
                                <p class="text-gray-600 text-xs">UI/UX Designer  </p>
                            </div>
                        </div>
                        
                        <!-- Team Member 11 -->
                        <div class="team-slide absolute transition-all duration-500" data-index="10" style="width: 240px;">
                            <div class="bg-white rounded-2xl shadow-xl p-6 text-center border-2 border-blue-400">
                                <div class="w-32 h-32 mx-auto mb-4 rounded-2xl overflow-hidden">
                                    <img src="assets/images/santos.jpeg" alt="Santos" class="w-full h-full object-cover">
                                </div>
                                <h3 class="text-lg font-bold text-gray-900 mb-1">Santos</h3>
                                <p class="text-blue-600 font-semibold text-sm mb-1">Member</p>
                                <p class="text-gray-600 text-xs">Documentation  </p>
                            </div>
                        </div>
                        
                        <!-- Team Member 12 -->
                        <div class="team-slide absolute transition-all duration-500" data-index="11" style="width: 240px;">
                            <div class="bg-white rounded-2xl shadow-xl p-6 text-center border-2 border-blue-400">
                                <div class="w-32 h-32 mx-auto mb-4 rounded-2xl overflow-hidden">
                                    <img src="assets/images/naron.jpg" alt="Naron" class="w-full h-full object-cover">
                                </div>
                                <h3 class="text-lg font-bold text-gray-900 mb-1">Naron</h3>
                                <p class="text-blue-600 font-semibold text-sm mb-1">Member</p>
                                <p class="text-gray-600 text-xs">Researcher</p>
                            </div>
                        </div>
                        
                        <!-- Team Member 13 -->
                        <div class="team-slide absolute transition-all duration-500" data-index="12" style="width: 240px;">
                            <div class="bg-white rounded-2xl shadow-xl p-6 text-center border-2 border-blue-400">
                                <div class="w-32 h-32 mx-auto mb-4 rounded-2xl overflow-hidden">
                                    <img src="assets/images/vinas.jpg" alt="Vinas" class="w-full h-full object-cover">
                                </div>
                                <h3 class="text-lg font-bold text-gray-900 mb-1">Viñas</h3>
                                <p class="text-blue-600 font-semibold text-sm mb-1">Member</p>
                                <p class="text-gray-600 text-xs">Front End Designer</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Navigation Dots -->
                <div class="flex justify-center gap-2 mt-8" id="sliderDots"></div>
            </div>
        </div>
    </section>

    <!-- Contact Section -->
    <section id="contact" class="py-20 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-4xl font-bold text-gray-900 mb-4">Get In Touch</h2>
                <p class="text-xl text-gray-600">Have questions? We'd love to hear from you</p>
            </div>
            
            <div class="grid md:grid-cols-3 gap-8">
      <div class="bg-white p-8 rounded-xl shadow-lg text-center">
        <div class="w-16 h-16 mx-auto mb-6 flex items-center justify-center">
          <img src="assets/images/address.svg" alt="Address Icon" class="w-12 h-12 object-contain">
        </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">Address</h3>
                    <p class="text-gray-600">673 Quirino Highway, San Bartolome<br>Novaliches, Quezon City</p>
                </div>
                
      <div class="bg-white p-8 rounded-xl shadow-lg text-center">
        <div class="w-16 h-16 mx-auto mb-6 flex items-center justify-center">
          <img src="assets/images/phone.svg" alt="Phone Icon" class="w-12 h-12 object-contain">
        </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">Phone</h3>
                    <p class="text-gray-600">(02) 8806-3333<br>Local 8100</p>
                </div>
                
      <div class="bg-white p-8 rounded-xl shadow-lg text-center">
        <div class="w-16 h-16 mx-auto mb-6 flex items-center justify-center">
          <img src="assets/images/email.svg" alt="Email Icon" class="w-12 h-12 object-contain">
        </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">Email</h3>
                    <p class="text-gray-600">info@qcu.edu.ph<br>support@qcu.edu.ph</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-[#291F8B] text-white py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid md:grid-cols-4 gap-8">
                <div><br>
                    <div class="flex items-center gap-3 mb-4">
    <img src="assets/images/QCU-LOGO.png" alt="QCU Logo" class="h-16 w-16 mb-2">                        <div>
                            <h3 class="font-bold text-lg text-white">QCU AMS</h3>
                            <p class="text-sm text-white">Asset Management</p>
                        </div>
                    </div>
                </div>
                
                <div>
                    <h4 class="font-bold mb-4">Quick Links</h4>
                    <ul class="space-y-2 text-white text-sm">
                        <li><a href="#home" class="hover:text-white transition-colors">Home</a></li>
                        <li><a href="#features" class="hover:text-white transition-colors">Features</a></li>
                        <li><a href="#about" class="hover:text-white transition-colors">About</a></li>
                        <li><a href="#contact" class="hover:text-white transition-colors">Contact</a></li>
                    </ul>
                </div>
                
                <div>
                    <h4 class="font-bold mb-4">Resources</h4>
                    <ul class="space-y-2 text-white text-sm">
                        <li><a href="#" onclick="openModal('documentation'); return false;" class="hover:text-yellow-300 transition-colors cursor-pointer">Documentation</a></li>
                        <li><a href="#" onclick="openModal('help'); return false;" class="hover:text-yellow-300 transition-colors cursor-pointer">Help Center</a></li>
                        <li><a href="#" onclick="openModal('privacy'); return false;" class="hover:text-yellow-300 transition-colors cursor-pointer">Privacy Policy</a></li>
                        <li><a href="#" onclick="openModal('terms'); return false;" class="hover:text-yellow-300 transition-colors cursor-pointer">Terms of Service</a></li>
                    </ul>
                </div>
                
                <div>
                    <h4 class="font-bold mb-4">Follow Us</h4>
                    <div class="flex gap-3">
                        <a href="https://www.facebook.com/qcu1994" target="_blank" class="bg-blue-600 w-10 h-10 rounded-lg flex items-center justify-center hover:bg-blue-600 transition-colors">
                            <i class="fa-brands fa-facebook"></i>
                        </a>
                       
                        <a href="https://www.instagram.com/quezoncityu/" target="_blank" class="bg-pink-600 w-10 h-10 rounded-lg flex items-center justify-center hover:bg-pink-600 transition-colors">
                            <i class="fa-brands fa-instagram"></i>
                        </a>
                        <a href="https://www.linkedin.com/school/qcu/" target="_blank" class="bg-blue-800 w-10 h-10 rounded-lg flex items-center justify-center hover:bg-blue-700 transition-colors">
                            <i class="fa-brands fa-linkedin"></i>
                        </a>
                    </div>
                </div>
            </div>
            
            <div class="border-t border-gray-800 mt-8 pt-8 text-center text-gray-400 text-sm">
                <p>&copy; 2025 Quezon City University. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <!-- Resource Modals -->
    <div id="resourceModal" class="hidden fixed inset-0 z-[9999] flex items-center justify-center bg-black bg-opacity-50 backdrop-blur-sm">
        <div class="bg-white rounded-xl shadow-2xl max-w-4xl w-11/12 max-h-[90vh] overflow-hidden">
            <div class="flex items-center justify-between p-6 border-b border-gray-200">
                <h2 id="modalTitle" class="text-2xl font-bold text-gray-900"></h2>
                <button onclick="closeModal()" class="text-gray-500 hover:text-gray-700 transition-colors">
                    <i class="fas fa-times text-2xl"></i>
                </button>
            </div>
            <div id="modalContent" class="p-6 overflow-y-auto max-h-[70vh]">
                <!-- Content will be inserted here -->
            </div>
        </div>
    </div>

    <!-- Mobile Menu Script -->
    <script>
        // Modal Functions
        function openModal(type) {
            const modal = document.getElementById('resourceModal');
            const title = document.getElementById('modalTitle');
            const content = document.getElementById('modalContent');
            
            let modalTitle = '';
            let modalContent = '';
            
            switch(type) {
                case 'documentation':
                    modalTitle = 'Documentation';
                    modalContent = `
                        <div class="prose max-w-none">
                            <h3 class="text-xl font-semibold mb-4">QCU Asset Management System Documentation</h3>
                            
                            <div class="space-y-6">
                                <div>
                                    <h4 class="text-lg font-semibold text-blue-600 mb-2">Getting Started</h4>
                                    <p class="text-gray-700 mb-2">Welcome to the QCU Asset Management System. This comprehensive platform helps you track, manage, and monitor all university assets efficiently.</p>
                                    <ul class="list-disc list-inside text-gray-700 space-y-1">
                                        <li>Login with your university credentials</li>
                                        <li>Navigate through the dashboard to access different modules</li>
                                        <li>Use the search function to quickly find assets</li>
                                    </ul>
                                </div>
                                
                                <div>
                                    <h4 class="text-lg font-semibold text-blue-600 mb-2">Key Features</h4>
                                    <ul class="list-disc list-inside text-gray-700 space-y-1">
                                        <li><strong>Asset Tracking:</strong> Monitor all assets with real-time location and status updates</li>
                                        <li><strong>PC Health Monitor:</strong> Track computer system performance and health metrics</li>
                                        <li><strong>Ticket Management:</strong> Submit and track maintenance requests</li>
                                        <li><strong>Borrowing System:</strong> Request and manage asset borrowing with approval workflows</li>
                                        <li><strong>Analytics & Reports:</strong> Generate detailed reports for decision making</li>
                                        <li><strong>QR Code Integration:</strong> Quick asset identification using QR codes</li>
                                    </ul>
                                </div>
                                
                                <div>
                                    <h4 class="text-lg font-semibold text-blue-600 mb-2">User Roles</h4>
                                    <ul class="list-disc list-inside text-gray-700 space-y-1">
                                        <li><strong>Administrator:</strong> Full system access and user management</li>
                                        <li><strong>Laboratory Staff:</strong> Asset and borrowing management</li>
                                        <li><strong>Technician:</strong> Ticket and maintenance handling</li>
                                        <li><strong>Student/Faculty:</strong> Asset borrowing and requests</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    `;
                    break;
                    
                case 'help':
                    modalTitle = 'Help Center';
                    modalContent = `
                        <div class="prose max-w-none">
                            <h3 class="text-xl font-semibold mb-4">Frequently Asked Questions</h3>
                            
                            <div class="space-y-4">
                                <div class="border-b border-gray-200 pb-4">
                                    <h4 class="text-lg font-semibold text-gray-900 mb-2">How do I reset my password?</h4>
                                    <p class="text-gray-700">Click on "Forgot Password" on the login page and follow the instructions sent to your registered email address.</p>
                                </div>
                                
                                <div class="border-b border-gray-200 pb-4">
                                    <h4 class="text-lg font-semibold text-gray-900 mb-2">How do I borrow an asset?</h4>
                                    <p class="text-gray-700">Navigate to the Borrowing section, search for available assets, select the item you need, fill out the borrowing form, and submit for approval.</p>
                                </div>
                                
                                <div class="border-b border-gray-200 pb-4">
                                    <h4 class="text-lg font-semibold text-gray-900 mb-2">How do I report a maintenance issue?</h4>
                                    <p class="text-gray-700">Go to the Ticket Management section, click "Submit Ticket", describe the issue, attach photos if needed, and submit. You'll receive updates on the ticket status.</p>
                                </div>
                                
                                <div class="border-b border-gray-200 pb-4">
                                    <h4 class="text-lg font-semibold text-gray-900 mb-2">Who do I contact for technical support?</h4>
                                    <p class="text-gray-700">For technical support, please contact:</p>
                                    <ul class="list-disc list-inside text-gray-700 mt-2">
                                        <li>Email: support@qcu.edu.ph</li>
                                        <li>Phone: (02) 8806-3333 Local 8100</li>
                                        <li>Office Hours: Monday-Friday, 8:00 AM - 5:00 PM</li>
                                    </ul>
                                </div>
                                
                                <div>
                                    <h4 class="text-lg font-semibold text-gray-900 mb-2">How do I scan QR codes?</h4>
                                    <p class="text-gray-700">Use your mobile device's camera or a QR code scanner app to scan the QR code on any asset. This will display detailed information about the asset.</p>
                                </div>
                            </div>
                        </div>
                    `;
                    break;
                    
                case 'privacy':
                    modalTitle = 'Privacy Policy';
                    modalContent = `
                        <div class="prose max-w-none">
                            <p class="text-sm text-gray-500 mb-4">Last Updated: March 2026</p>
                            
                            <div class="space-y-4 text-gray-700">
                                <div>
                                    <h4 class="text-lg font-semibold text-gray-900 mb-2">1. Information We Collect</h4>
                                    <p>We collect information that you provide directly to us, including:</p>
                                    <ul class="list-disc list-inside mt-2 space-y-1">
                                        <li>Name, email address, and university ID number</li>
                                        <li>Login credentials and authentication information</li>
                                        <li>Asset borrowing and usage records</li>
                                        <li>Maintenance requests and ticket submissions</li>
                                    </ul>
                                </div>
                                
                                <div>
                                    <h4 class="text-lg font-semibold text-gray-900 mb-2">2. How We Use Your Information</h4>
                                    <p>We use the information we collect to:</p>
                                    <ul class="list-disc list-inside mt-2 space-y-1">
                                        <li>Provide, maintain, and improve our services</li>
                                        <li>Process asset borrowing requests and track returns</li>
                                        <li>Respond to maintenance requests and support inquiries</li>
                                        <li>Generate reports and analytics for university administration</li>
                                        <li>Communicate with you about system updates and notifications</li>
                                    </ul>
                                </div>
                                
                                <div>
                                    <h4 class="text-lg font-semibold text-gray-900 mb-2">3. Information Security</h4>
                                    <p>We implement appropriate technical and organizational measures to protect your personal information against unauthorized access, alteration, disclosure, or destruction.</p>
                                </div>
                                
                                <div>
                                    <h4 class="text-lg font-semibold text-gray-900 mb-2">4. Data Retention</h4>
                                    <p>We retain your information for as long as necessary to fulfill the purposes outlined in this privacy policy, unless a longer retention period is required by law.</p>
                                </div>
                                
                                <div>
                                    <h4 class="text-lg font-semibold text-gray-900 mb-2">5. Your Rights</h4>
                                    <p>You have the right to:</p>
                                    <ul class="list-disc list-inside mt-2 space-y-1">
                                        <li>Access and review your personal information</li>
                                        <li>Request corrections to inaccurate data</li>
                                        <li>Request deletion of your data (subject to legal requirements)</li>
                                    </ul>
                                </div>
                                
                                <div>
                                    <h4 class="text-lg font-semibold text-gray-900 mb-2">6. Contact Us</h4>
                                    <p>If you have questions about this Privacy Policy, please contact us at:</p>
                                    <p class="mt-2">Email: privacy@qcu.edu.ph<br>Phone: (02) 8806-3333</p>
                                </div>
                            </div>
                        </div>
                    `;
                    break;
                    
                case 'terms':
                    modalTitle = 'Terms of Service';
                    modalContent = `
                        <div class="prose max-w-none">
                            <p class="text-sm text-gray-500 mb-4">Last Updated: March 2026</p>
                            
                            <div class="space-y-4 text-gray-700">
                                <div>
                                    <h4 class="text-lg font-semibold text-gray-900 mb-2">1. Acceptance of Terms</h4>
                                    <p>By accessing and using the QCU Asset Management System, you accept and agree to be bound by these Terms of Service. If you do not agree to these terms, please do not use the system.</p>
                                </div>
                                
                                <div>
                                    <h4 class="text-lg font-semibold text-gray-900 mb-2">2. User Responsibilities</h4>
                                    <p>As a user of this system, you agree to:</p>
                                    <ul class="list-disc list-inside mt-2 space-y-1">
                                        <li>Provide accurate and complete information</li>
                                        <li>Maintain the confidentiality of your account credentials</li>
                                        <li>Use the system only for authorized university purposes</li>
                                        <li>Report any security breaches or unauthorized access</li>
                                        <li>Return borrowed assets on time and in good condition</li>
                                    </ul>
                                </div>
                                
                                <div>
                                    <h4 class="text-lg font-semibold text-gray-900 mb-2">3. Asset Borrowing Terms</h4>
                                    <ul class="list-disc list-inside space-y-1">
                                        <li>All borrowed assets must be returned by the specified due date</li>
                                        <li>Users are responsible for any damage or loss of borrowed assets</li>
                                        <li>Late returns may result in suspension of borrowing privileges</li>
                                        <li>Assets must be used only for educational or official university purposes</li>
                                    </ul>
                                </div>
                                
                                <div>
                                    <h4 class="text-lg font-semibold text-gray-900 mb-2">4. Prohibited Activities</h4>
                                    <p>You may not:</p>
                                    <ul class="list-disc list-inside mt-2 space-y-1">
                                        <li>Share your account credentials with others</li>
                                        <li>Attempt to gain unauthorized access to the system</li>
                                        <li>Use the system for any illegal or unauthorized purpose</li>
                                        <li>Interfere with or disrupt the system's operation</li>
                                        <li>Misuse or damage university assets</li>
                                    </ul>
                                </div>
                                
                                <div>
                                    <h4 class="text-lg font-semibold text-gray-900 mb-2">5. Limitation of Liability</h4>
                                    <p>Quezon City University shall not be liable for any indirect, incidental, special, or consequential damages arising from your use of the system.</p>
                                </div>
                                
                                <div>
                                    <h4 class="text-lg font-semibold text-gray-900 mb-2">6. Modifications to Terms</h4>
                                    <p>We reserve the right to modify these terms at any time. Continued use of the system after changes constitutes acceptance of the modified terms.</p>
                                </div>
                                
                                <div>
                                    <h4 class="text-lg font-semibold text-gray-900 mb-2">7. Contact Information</h4>
                                    <p>For questions about these Terms of Service, contact:</p>
                                    <p class="mt-2">Email: info@qcu.edu.ph<br>Phone: (02) 8806-3333</p>
                                </div>
                            </div>
                        </div>
                    `;
                    break;
            }
            
            title.textContent = modalTitle;
            content.innerHTML = modalContent;
            modal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }
        
        function closeModal() {
            const modal = document.getElementById('resourceModal');
            modal.classList.add('hidden');
            document.body.style.overflow = 'auto';
        }
        
        // Close modal when clicking outside
        document.getElementById('resourceModal')?.addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });
        
        // Close modal with Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeModal();
            }
        });
    </script>

    <!-- Mobile Menu Script -->
    <script>
// Navbar scroll effect with smooth transitions
let lastScrollY = 0;
let ticking = false;

function updateNavbar() {
    const navbar = document.getElementById('navbar');
    const navbarContainer = document.getElementById('navbar-container');
    const navbarContent = document.getElementById('navbar-content');
    const navbarMenu = document.getElementById('navbar-menu');
    const logoLeft = document.getElementById('navbar-logo-left');
    const logoInside = document.getElementById('navbar-logo-inside');
    
    const scrollPosition = window.scrollY;
    const maxScroll = 100; // Distance over which transition occurs
    const progress = Math.min(scrollPosition / maxScroll, 1);
    
    if (progress > 0.1) {
        navbar.classList.add('navbar-scrolled');
        
        // Smooth opacity transitions
        logoLeft.style.opacity = Math.max(0, 1 - (progress * 2));
        logoInside.style.opacity = Math.min(1, progress * 2);
        
        // Smooth background transition
        const bgOpacity = Math.min(0.95, progress);
        navbarMenu.style.backgroundColor = `rgba(41, 31, 139, ${bgOpacity})`;
        
        // 🔹 Add border radius when scrolled
        navbar.style.borderRadius = "0 0 0.25 0.25rem"; // bottom corners rounded
    } else {
        navbar.classList.remove('navbar-scrolled');
        logoLeft.style.opacity = '1';
        logoInside.style.opacity = '0';
        navbarMenu.style.backgroundColor = '';
        
        // 🔹 Reset radius when back at top
        navbar.style.borderRadius = "0"; 
    }
    
    ticking = false;
}

function requestTick() {
    if (!ticking) {
        requestAnimationFrame(updateNavbar);
        ticking = true;
    }
}

window.addEventListener('scroll', requestTick);

// Optional: toggle radius on click
document.getElementById('navbar').addEventListener('click', () => {
    const navbar = document.getElementById('navbar');
    if (navbar.style.borderRadius === "0") {
        navbar.style.borderRadius = "0 0 1rem 1rem";
    } else {
        navbar.style.borderRadius = "0";
    }
});

        window.addEventListener('scroll', requestTick);
        
        // Dark Mode Toggle
        function toggleDarkMode() {
            const html = document.documentElement;
            const isDark = html.classList.toggle('dark');
            localStorage.setItem('darkMode', isDark ? 'enabled' : 'disabled');
            updateDarkModeIcons(isDark);
        }
        
        function updateDarkModeIcons(isDark) {
            const lightIcons = document.querySelectorAll('.dark-mode-icon-light');
            const darkIcons = document.querySelectorAll('.dark-mode-icon-dark');
            
            if (isDark) {
                lightIcons.forEach(icon => icon.classList.add('hidden'));
                darkIcons.forEach(icon => icon.classList.remove('hidden'));
            } else {
                lightIcons.forEach(icon => icon.classList.remove('hidden'));
                darkIcons.forEach(icon => icon.classList.add('hidden'));
            }
        }
        
        // Check for saved dark mode preference
        (function() {
            const darkMode = localStorage.getItem('darkMode');
            if (darkMode === 'enabled') {
                document.documentElement.classList.add('dark');
                updateDarkModeIcons(true);
            }
        })();
        
        const mobileMenuBtn = document.getElementById('mobile-menu-btn');
        const mobileMenu = document.getElementById('mobile-menu');
        
        mobileMenuBtn.addEventListener('click', () => {
            mobileMenu.classList.toggle('hidden');
        });
        
        // Close mobile menu when clicking on a link
        document.querySelectorAll('#mobile-menu a').forEach(link => {
            link.addEventListener('click', () => {
                mobileMenu.classList.add('hidden');
            });
        });
        
        // Smooth scroll for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });
        
        // Team Slider
        const teamSlider = document.getElementById('teamSlider');
        const sliderDots = document.getElementById('sliderDots');
        const slides = document.querySelectorAll('.team-slide');
        const teamMembers = 13;
        let currentSlide = 0;
        
        // Create navigation dots
        for (let i = 0; i < teamMembers; i++) {
            const dot = document.createElement('button');
            dot.className = 'w-3 h-3 rounded-full transition-all ' + (i === 0 ? 'bg-blue-600 w-8' : 'bg-gray-300');
            dot.addEventListener('click', () => goToSlide(i));
            sliderDots.appendChild(dot);
        }
        
        function updateSlides() {
            const containerWidth = teamSlider.offsetWidth;
            const centerX = containerWidth / 2;
            
            slides.forEach((slide, index) => {
                const card = slide.querySelector('div');
                
                // Calculate relative position
                let diff = index - currentSlide;
                
                // Normalize the difference to handle wraparound
                if (diff > teamMembers / 2) diff -= teamMembers;
                if (diff < -teamMembers / 2) diff += teamMembers;
                
                // Show only nearby slides (center and 1-2 on each side)
                if (Math.abs(diff) > 2) {
                    slide.style.opacity = '0';
                    slide.style.pointerEvents = 'none';
                    return;
                }
                
                slide.style.pointerEvents = 'auto';
                
                // Center (active) slide
                if (diff === 0) {
                    slide.style.left = (centerX - 120) + 'px';
                    slide.style.transform = 'scale(1.15)';
                    slide.style.opacity = '1';
                    slide.style.zIndex = '30';
                    card.style.background = 'white';
                    card.style.borderColor = '#3B82F6';
                    card.style.borderWidth = '3px';
                }
                // Left immediate
                else if (diff === -1) {
                    slide.style.left = (centerX - 300) + 'px';
                    slide.style.transform = 'scale(0.95)';
                    slide.style.opacity = '0.7';
                    slide.style.zIndex = '25';
                    card.style.background = 'white';
                    card.style.borderColor = '#D1D5DB';
                    card.style.borderWidth = '2px';
                }
                // Right immediate
                else if (diff === 1) {
                    slide.style.left = (centerX + 60) + 'px';
                    slide.style.transform = 'scale(0.95)';
                    slide.style.opacity = '0.7';
                    slide.style.zIndex = '25';
                    card.style.background = 'white';
                    card.style.borderColor = '#D1D5DB';
                    card.style.borderWidth = '2px';
                }
                // Far left
                else if (diff === -2) {
                    slide.style.left = (centerX - 480) + 'px';
                    slide.style.transform = 'scale(0.85)';
                    slide.style.opacity = '0.5';
                    slide.style.zIndex = '20';
                    card.style.background = 'white';
                    card.style.borderColor = '#E5E7EB';
                    card.style.borderWidth = '2px';
                }
                // Far right
                else if (diff === 2) {
                    slide.style.left = (centerX + 240) + 'px';
                    slide.style.transform = 'scale(0.85)';
                    slide.style.opacity = '0.5';
                    slide.style.zIndex = '20';
                    card.style.background = 'white';
                    card.style.borderColor = '#E5E7EB';
                    card.style.borderWidth = '2px';
                }
            });
        }
        
        function updateDots() {
            const dots = sliderDots.children;
            for (let i = 0; i < dots.length; i++) {
                if (i === currentSlide) {
                    dots[i].className = 'w-8 h-3 rounded-full transition-all bg-blue-600';
                } else {
                    dots[i].className = 'w-3 h-3 rounded-full transition-all bg-gray-300';
                }
            }
        }
        
        function goToSlide(index) {
            currentSlide = index;
            updateSlides();
            updateDots();
        }
        
        function nextSlide() {
            currentSlide = (currentSlide + 1) % teamMembers;
            goToSlide(currentSlide);
        }
        
        // Initialize
        updateSlides();
        
        // Auto-slide every 3 seconds
        setInterval(nextSlide, 3000);
        
        // Handle window resize
        window.addEventListener('resize', updateSlides);
    </script>
</body>
</html>