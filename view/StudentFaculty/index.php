<?php
session_start();

// Check if user is logged in and has student or faculty role
if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true || !in_array($_SESSION['role'], ['Student', 'Faculty'])) {
    header("Location: ../login.php");
    exit();
}

require_once '../../config/config.php';
include '../components/layout_header.php';
?>

        <!-- Main Content -->
        <main class="p-6">
        <!-- Welcome Section -->
        <div class=" p-6 mb-8">
            <h2 class="text-2xl text-center font-bold text-gray-800 mb-2">Hi <?php echo htmlspecialchars($_SESSION['full_name']); ?>, what do you need help with?</h2>
        </div>

        <!-- Help Options -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-2 gap-6 mb-8">
            <!-- Hardware Issue -->
            <div class="bg-white rounded-xl shadow-lg p-8 hover:shadow-xl transition duration-200 cursor-pointer border border-gray-100 hover:border-blue-300 group transform hover:scale-105" onclick="handleIssueClick('hardware')">
                <div class="flex gap-6">
                    <div class="bg-blue-100 p-4 rounded-xl group-hover:bg-blue-200 transition-colors">
                        <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                        </svg>
                    </div>
                    <div class="flex-1">
                        <h3 class="text-xl font-semibold text-gray-800 mb-2">Hardware Issue</h3>
                        <p class="text-gray-600">Computer, printer, or equipment problems that need technical assistance</p>
                    </div>
                </div>
            </div>

            <!-- Software Issue -->
            <div class="bg-white rounded-xl shadow-lg p-8 hover:shadow-xl transition duration-200 cursor-pointer border border-gray-100 hover:border-green-300 group transform hover:scale-105" onclick="handleIssueClick('software')">
                <div class="flex gap-6">
                    <div class="bg-green-100 p-4 rounded-xl group-hover:bg-green-200 transition-colors">
                        <svg class="w-8 h-8 text-green-600 " fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                        </svg>
                    </div>
                    <div class="flex-1">
                        <h3 class="text-xl font-semibold text-gray-800 mb-2">Software Issue</h3>
                        <p class="text-gray-600">Application crashes, system errors, or software installation problems</p>
                    </div>
                </div>
            </div>

            <!-- Network Issue -->
            <div class="bg-white rounded-xl shadow-lg p-8 hover:shadow-xl transition duration-200 cursor-pointer border border-gray-100 hover:border-purple-300 group transform hover:scale-105" onclick="handleIssueClick('network')">
                <div class="flex gap-6">
                    <div class="bg-purple-100 p-4 rounded-xl group-hover:bg-purple-200 transition-colors">
                        <svg class="w-8 h-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"></path>
                        </svg>
                    </div>
                    <div class="flex-1">
                        <h3 class="text-xl font-semibold text-gray-800 mb-2">Network Issue</h3>
                        <p class="text-gray-600">Internet connectivity problems, Wi-Fi issues, or network access troubles</p>
                    </div>
                </div>
            </div>

            <!-- Borrow Equipment -->
            <div class="bg-white rounded-xl shadow-lg p-8 hover:shadow-xl transition duration-200 cursor-pointer border border-gray-100 hover:border-yellow-300 group transform hover:scale-105" onclick="handleIssueClick('borrow')">
                <div class="flex gap-6">
                    <div class="bg-yellow-100 p-4 rounded-xl group-hover:bg-yellow-200 transition-colors">
                        <svg class="w-8 h-8 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                        </svg>
                    </div>
                    <div class="flex-1">
                        <h3 class="text-xl font-semibold text-gray-800 mb-2">Borrow Equipment</h3>
                        <p class="text-gray-600">Request laboratory equipment, tools, or devices for academic projects</p>
                    </div>
                </div>
            </div>

            <!-- Laboratory Concern -->
            <div class="bg-white rounded-xl shadow-lg p-8 hover:shadow-xl transition duration-200 cursor-pointer border border-gray-100 hover:border-indigo-300 group transform hover:scale-105" onclick="handleIssueClick('laboratory')">
                <div class="flex gap-6">
                    <div class="bg-indigo-100 p-4 rounded-xl group-hover:bg-indigo-200 transition-colors">
                        <svg class="w-8 h-8 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                        </svg>
                    </div>
                    <div class="flex-1">
                        <h3 class="text-xl font-semibold text-gray-800 mb-2">Laboratory Concern</h3>
                        <p class="text-gray-600">Lab facility access, safety issues, or equipment availability concerns</p>
                    </div>
                </div>
            </div>

            <!-- Other -->
            <div class="bg-white rounded-xl shadow-lg p-8 hover:shadow-xl transition duration-200 cursor-pointer border border-gray-100 hover:border-gray-300 group transform hover:scale-105" onclick="handleIssueClick('other')">
                <div class="flex gap-6">
                    <div class="bg-gray-100 p-4 rounded-xl group-hover:bg-gray-200 transition-colors">
                        <svg class="w-8 h-8 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div class="flex-1">
                        <h3 class="text-xl font-semibold text-gray-800 mb-2">Other</h3>
                        <p class="text-gray-600">General questions, account issues, or assistance with university services</p>
                    </div>
                </div>
            </div>
        </div>

     
       
        </main>

<script>
function handleIssueClick(issueType) {
    // Add visual feedback
    console.log('Issue type selected:', issueType);
    
    // You can customize these actions based on your needs
    switch(issueType) {
        case 'hardware':
            alert('Hardware Issue selected. You will be redirected to submit a hardware support request.');
            // window.location.href = 'hardware-support.php';
            break;
        case 'software':
            alert('Software Issue selected. You will be redirected to submit a software support request.');
            // window.location.href = 'software-support.php';
            break;
        case 'network':
            alert('Network Issue selected. You will be redirected to submit a network support request.');
            // window.location.href = 'network-support.php';
            break;
        case 'borrow':
            alert('Borrow Equipment selected. You will be redirected to the equipment request form.');
            // window.location.href = 'equipment-request.php';
            break;
        case 'laboratory':
            alert('Laboratory Concern selected. You will be redirected to submit a laboratory concern.');
            // window.location.href = 'laboratory-concern.php';
            break;
        case 'other':
            alert('Other selected. You will be redirected to general support.');
            // window.location.href = 'general-support.php';
            break;
        default:
            alert('Please select a valid option.');
    }
}
</script>

<?php include '../components/layout_footer.php'; ?>