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
<<<<<<< HEAD
=======
            background-size: 400% 400%;
>>>>>>> 14b90a3cb03ab18ce465d310e5382ffa6df5d8cd
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
    </style>
</head>
<body class="bg-gray-50">
    
    <!-- Navigation -->
    <nav class="fixed w-full z-50 bg-white shadow-md">
        <div class="px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-20">
                <!-- Logo -->
                <div class="flex items-center gap-3">
                    <img src="assets/images/QCU-LOGO.png" alt="QCU Logo" class="h-14 w-14">
                    <div>
<<<<<<< HEAD
                        <h1 class="text-xl font-bold text-blue-900">QCU AMS</h1>
                        <p class="text-xs text-gray-600 dark:text-gray-400">Asset Management System</p>
=======
                        <h1 class="text-xl font-bold text-blue-900">Quezon City University</h1>
                        <p class="text-xs text-gray-600">Asset Management System</p>
>>>>>>> 14b90a3cb03ab18ce465d310e5382ffa6df5d8cd
                    </div>
                </div>
                
                <!-- Desktop Menu -->
                <div class="hidden md:flex items-center gap-8">
                    <a href="#home" class="text-gray-700 hover:text-blue-600 transition-colors font-medium">Home</a>
                    <a href="#features" class="text-gray-700 hover:text-blue-600 transition-colors font-medium">Features</a>
                    <a href="#about" class="text-gray-700 hover:text-blue-600 transition-colors font-medium">About</a>
                    <a href="#contact" class="text-gray-700 hover:text-blue-600 transition-colors font-medium">Contact</a>
                    
                    <!-- Dark Mode Toggle -->
                    <button onclick="toggleDarkMode()" class="theme-toggle" aria-label="Toggle dark mode">
                        <div class="theme-toggle-slider">
                            <i class="fa-solid fa-sun text-yellow-500 text-xs dark-mode-icon-light"></i>
                            <i class="fa-solid fa-moon text-blue-400 text-xs dark-mode-icon-dark hidden"></i>
                        </div>
                    </button>
                    
                    <a href="view/employee_login.php" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2.5 rounded-lg transition-colors font-medium">
                        <i class="fa-solid fa-sign-in-alt mr-2"></i>Login
                    </a>
                </div>
                
                <!-- Mobile Menu Button -->
                <button id="mobile-menu-btn" class="md:hidden text-gray-700 hover:text-blue-600">
                    <i class="fa-solid fa-bars text-2xl"></i>
                </button>
            </div>
        </div>
        
        <!-- Mobile Menu -->
        <div id="mobile-menu" class="hidden md:hidden bg-white border-t">
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
                
                <a href="view/employee_login.php" class="block bg-blue-600 hover:bg-blue-700 text-white px-6 py-2.5 rounded-lg transition-colors font-medium text-center">
                    <i class="fa-solid fa-sign-in-alt mr-2"></i>Login
                </a>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section id="home" class="hero-gradient min-h-screen flex items-center justify-center pt-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20">
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
                        <a href="view/employee_login.php" class="bg-white text-blue-600 px-8 py-4 rounded-lg font-semibold hover:bg-blue-50 transition-all transform hover:scale-105 shadow-lg">
                            <i class="fa-solid fa-rocket mr-2"></i>Get Started
                        </a>
                        <a href="#features" class="glass-effect text-white px-8 py-4 rounded-lg font-semibold hover:bg-white/20 transition-all">
                            <i class="fa-solid fa-circle-play mr-2"></i>Learn More
                        </a>
                    </div>
                    
                    <!-- Stats -->
                    <div class="grid grid-cols-3 gap-6 mt-12">
                        <div class="text-center">
                            <div class="text-3xl font-bold text-yellow-300">1000+</div>
                            <div class="text-sm text-blue-100">Assets Tracked</div>
                        </div>
                        <div class="text-center">
                            <div class="text-3xl font-bold text-yellow-300">50+</div>
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
                        <img src="assets/images/QCU-LOGO.png" alt="Asset Management" class="relative w-full max-w-lg mx-auto drop-shadow-2xl">
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="py-20 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-4xl font-bold text-gray-900 mb-4">Powerful Features</h2>
                <p class="text-xl text-gray-600">Everything you need to manage your assets effectively</p>
            </div>
            
            <div class="grid md:grid-cols-3 gap-8">
                <!-- Feature 1 -->
                <div class="feature-card bg-white p-8 rounded-xl shadow-lg border border-gray-100">
                    <div class="bg-blue-100 w-16 h-16 rounded-full flex items-center justify-center mb-6">
                        <i class="fa-solid fa-clipboard-list text-blue-600 text-2xl"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 mb-4">Asset Tracking</h3>
                    <p class="text-gray-600 leading-relaxed">
                        Track all your assets in real-time with comprehensive details including location, condition, and maintenance history.
                    </p>
                </div>
                
                <!-- Feature 2 -->
                <div class="feature-card bg-white p-8 rounded-xl shadow-lg border border-gray-100">
                    <div class="bg-green-100 w-16 h-16 rounded-full flex items-center justify-center mb-6">
                        <i class="fa-solid fa-heart-pulse text-green-600 text-2xl"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 mb-4">PC Health Monitor</h3>
                    <p class="text-gray-600 leading-relaxed">
                        Monitor computer systems health, performance metrics, and receive alerts for potential issues before they escalate.
                    </p>
                </div>
                
                <!-- Feature 3 -->
                <div class="feature-card bg-white p-8 rounded-xl shadow-lg border border-gray-100">
                    <div class="bg-purple-100 w-16 h-16 rounded-full flex items-center justify-center mb-6">
                        <i class="fa-solid fa-ticket text-purple-600 text-2xl"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 mb-4">Ticket Management</h3>
                    <p class="text-gray-600 leading-relaxed">
                        Streamline issue reporting and resolution with an integrated ticket management system for maintenance requests.
                    </p>
                </div>
                
                <!-- Feature 4 -->
                <div class="feature-card bg-white p-8 rounded-xl shadow-lg border border-gray-100">
                    <div class="bg-yellow-100 w-16 h-16 rounded-full flex items-center justify-center mb-6">
                        <i class="fa-solid fa-right-left text-yellow-600 text-2xl"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 mb-4">Borrowing System</h3>
                    <p class="text-gray-600 leading-relaxed">
                        Manage asset borrowing with automated approval workflows, tracking, and return notifications.
                    </p>
                </div>
                
                <!-- Feature 5 -->
                <div class="feature-card bg-white p-8 rounded-xl shadow-lg border border-gray-100">
                    <div class="bg-red-100 w-16 h-16 rounded-full flex items-center justify-center mb-6">
                        <i class="fa-solid fa-chart-column text-red-600 text-2xl"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 mb-4">Analytics & Reports</h3>
                    <p class="text-gray-600 leading-relaxed">
                        Generate detailed reports and analytics to make data-driven decisions about your assets and resources.
                    </p>
                </div>
                
                <!-- Feature 6 -->
                <div class="feature-card bg-white p-8 rounded-xl shadow-lg border border-gray-100">
                    <div class="bg-indigo-100 w-16 h-16 rounded-full flex items-center justify-center mb-6">
                        <i class="fa-solid fa-qrcode text-indigo-600 text-2xl"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 mb-4">QR Code Integration</h3>
                    <p class="text-gray-600 leading-relaxed">
                        Quick asset identification and tracking using QR codes for faster inventory management and audits.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- About Section -->
    <section id="about" class="py-20 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid md:grid-cols-2 gap-12 items-center">
                <div>
                    <h2 class="text-4xl font-bold text-gray-900 mb-6">About QCU Asset Management System</h2>
                    <p class="text-lg text-gray-600 mb-6 leading-relaxed">
                        The Quezon City University Asset Management System is a comprehensive solution designed to streamline 
                        the tracking, maintenance, and management of all university assets.
                    </p>
                    <p class="text-lg text-gray-600 mb-6 leading-relaxed">
                        Our system provides real-time monitoring, automated workflows, and detailed reporting capabilities 
                        to ensure efficient resource utilization and accountability.
                    </p>
                    
                    <div class="space-y-4">
                        <div class="flex items-start gap-4">
                            <div class="bg-blue-100 w-12 h-12 rounded-lg flex items-center justify-center flex-shrink-0">
                                <i class="fa-solid fa-check text-blue-600 text-xl"></i>
                            </div>
                            <div>
                                <h4 class="font-semibold text-gray-900 mb-1">Real-time Tracking</h4>
                                <p class="text-gray-600">Monitor all assets across multiple locations in real-time</p>
                            </div>
                        </div>
                        
                        <div class="flex items-start gap-4">
                            <div class="bg-blue-100 w-12 h-12 rounded-lg flex items-center justify-center flex-shrink-0">
                                <i class="fa-solid fa-check text-blue-600 text-xl"></i>
                            </div>
                            <div>
                                <h4 class="font-semibold text-gray-900 mb-1">Automated Workflows</h4>
                                <p class="text-gray-600">Streamline approval processes and notifications</p>
                            </div>
                        </div>
                        
                        <div class="flex items-start gap-4">
                            <div class="bg-blue-100 w-12 h-12 rounded-lg flex items-center justify-center flex-shrink-0">
                                <i class="fa-solid fa-check text-blue-600 text-xl"></i>
                            </div>
                            <div>
                                <h4 class="font-semibold text-gray-900 mb-1">Comprehensive Reporting</h4>
                                <p class="text-gray-600">Generate detailed reports for better decision making</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <div class="bg-blue-600 text-white p-8 rounded-xl">
                        <i class="fa-solid fa-users text-4xl mb-4"></i>
                        <h3 class="text-3xl font-bold mb-2">500+</h3>
                        <p class="text-blue-100">Active Users</p>
                    </div>
                    <div class="bg-green-600 text-white p-8 rounded-xl">
                        <i class="fa-solid fa-building text-4xl mb-4"></i>
                        <h3 class="text-3xl font-bold mb-2">15+</h3>
                        <p class="text-green-100">Buildings</p>
                    </div>
                    <div class="bg-purple-600 text-white p-8 rounded-xl">
                        <i class="fa-solid fa-desktop text-4xl mb-4"></i>
                        <h3 class="text-3xl font-bold mb-2">800+</h3>
                        <p class="text-purple-100">Computers</p>
                    </div>
                    <div class="bg-yellow-600 text-white p-8 rounded-xl">
                        <i class="fa-solid fa-handshake text-4xl mb-4"></i>
                        <h3 class="text-3xl font-bold mb-2">100%</h3>
                        <p class="text-yellow-100">Satisfaction</p>
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
                <div class="bg-gradient-to-br from-red-500 to-red-600 text-white p-8 rounded-xl shadow-lg hover:shadow-2xl transition-all transform hover:scale-105">
                    <i class="fa-solid fa-user-tie text-5xl mb-4"></i>
                    <h3 class="text-2xl font-bold mb-3">Administrator</h3>
                    <p class="text-red-100 mb-4">Full system control and management</p>
                    <a href="view/employee_login.php" class="inline-block bg-white text-red-600 px-6 py-2 rounded-lg font-semibold hover:bg-red-50 transition-colors">
                        Login <i class="fa-solid fa-arrow-right ml-2"></i>
                    </a>
                </div>
                
                <div class="bg-gradient-to-br from-yellow-500 to-yellow-600 text-white p-8 rounded-xl shadow-lg hover:shadow-2xl transition-all transform hover:scale-105">
                    <i class="fa-solid fa-user-gear text-5xl mb-4"></i>
                    <h3 class="text-2xl font-bold mb-3">Laboratory Staff</h3>
                    <p class="text-yellow-100 mb-4">Asset and borrowing management</p>
                    <a href="view/employee_login.php" class="inline-block bg-white text-yellow-600 px-6 py-2 rounded-lg font-semibold hover:bg-yellow-50 transition-colors">
                        Login <i class="fa-solid fa-arrow-right ml-2"></i>
                    </a>
                </div>
                
                <div class="bg-gradient-to-br from-purple-500 to-purple-600 text-white p-8 rounded-xl shadow-lg hover:shadow-2xl transition-all transform hover:scale-105">
                    <i class="fa-solid fa-screwdriver-wrench text-5xl mb-4"></i>
                    <h3 class="text-2xl font-bold mb-3">Technician</h3>
                    <p class="text-purple-100 mb-4">Ticket and maintenance handling</p>
                    <a href="view/employee_login.php" class="inline-block bg-white text-purple-600 px-6 py-2 rounded-lg font-semibold hover:bg-purple-50 transition-colors">
                        Login <i class="fa-solid fa-arrow-right ml-2"></i>
                    </a>
                </div>
                
                <div class="bg-gradient-to-br from-blue-500 to-blue-600 text-white p-8 rounded-xl shadow-lg hover:shadow-2xl transition-all transform hover:scale-105">
                    <i class="fa-solid fa-graduation-cap text-5xl mb-4"></i>
                    <h3 class="text-2xl font-bold mb-3">Student/Faculty</h3>
                    <p class="text-blue-100 mb-4">Asset borrowing and requests</p>
                    <a href="view/student_login.php" class="inline-block bg-white text-blue-600 px-6 py-2 rounded-lg font-semibold hover:bg-blue-50 transition-colors">
                        Login <i class="fa-solid fa-arrow-right ml-2"></i>
                    </a>
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
                    <div class="bg-blue-100 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-6">
                        <i class="fa-solid fa-location-dot text-blue-600 text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">Address</h3>
                    <p class="text-gray-600">673 Quirino Highway, San Bartolome<br>Novaliches, Quezon City</p>
                </div>
                
                <div class="bg-white p-8 rounded-xl shadow-lg text-center">
                    <div class="bg-green-100 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-6">
                        <i class="fa-solid fa-phone text-green-600 text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">Phone</h3>
                    <p class="text-gray-600">(02) 8806-3333<br>Local 8100</p>
                </div>
                
                <div class="bg-white p-8 rounded-xl shadow-lg text-center">
                    <div class="bg-purple-100 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-6">
                        <i class="fa-solid fa-envelope text-purple-600 text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">Email</h3>
                    <p class="text-gray-600">info@qcu.edu.ph<br>support@qcu.edu.ph</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-gray-900 text-white py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid md:grid-cols-4 gap-8">
                <div>
                    <div class="flex items-center gap-3 mb-4">
                        <img src="assets/images/QCU-LOGO.png" alt="QCU Logo" class="h-12 w-12">
                        <div>
                            <h3 class="font-bold text-lg text-blue-900">QCU AMS</h3>
                            <p class="text-sm text-gray-400">Asset Management</p>
                        </div>
                    </div>
                    <p class="text-gray-400 text-sm">
                        Empowering Quezon City University with efficient asset management solutions.
                    </p>
                </div>
                
                <div>
                    <h4 class="font-bold mb-4">Quick Links</h4>
                    <ul class="space-y-2 text-gray-400 text-sm">
                        <li><a href="#home" class="hover:text-white transition-colors">Home</a></li>
                        <li><a href="#features" class="hover:text-white transition-colors">Features</a></li>
                        <li><a href="#about" class="hover:text-white transition-colors">About</a></li>
                        <li><a href="#contact" class="hover:text-white transition-colors">Contact</a></li>
                    </ul>
                </div>
                
                <div>
                    <h4 class="font-bold mb-4">Resources</h4>
                    <ul class="space-y-2 text-gray-400 text-sm">
                        <li><a href="#" class="hover:text-white transition-colors">Documentation</a></li>
                        <li><a href="#" class="hover:text-white transition-colors">Help Center</a></li>
                        <li><a href="#" class="hover:text-white transition-colors">Privacy Policy</a></li>
                        <li><a href="#" class="hover:text-white transition-colors">Terms of Service</a></li>
                    </ul>
                </div>
                
                <div>
                    <h4 class="font-bold mb-4">Follow Us</h4>
                    <div class="flex gap-3">
                        <a href="#" class="bg-gray-800 w-10 h-10 rounded-lg flex items-center justify-center hover:bg-blue-600 transition-colors">
                            <i class="fa-brands fa-facebook"></i>
                        </a>
                       
                        <a href="#" class="bg-gray-800 w-10 h-10 rounded-lg flex items-center justify-center hover:bg-pink-600 transition-colors">
                            <i class="fa-brands fa-instagram"></i>
                        </a>
                        <a href="#" class="bg-gray-800 w-10 h-10 rounded-lg flex items-center justify-center hover:bg-blue-700 transition-colors">
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

    <!-- Mobile Menu Script -->
    <script>
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
