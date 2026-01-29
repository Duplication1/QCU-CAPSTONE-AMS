<?php
// Prevent page caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

session_start();

// Check if user is logged in and has student or faculty role
if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true || !in_array($_SESSION['role'], ['Student', 'Faculty'])) {
    header("Location: ../login.php");
    exit();
}

require_once '../../config/config.php';
include '../components/layout_header.php';

// Database connection
$dbConfig = Config::database();
try {
    $conn = new mysqli($dbConfig['host'], $dbConfig['username'], $dbConfig['password'], $dbConfig['name']);
    $conn->set_charset('utf8mb4');
} catch (mysqli_sql_exception $e) {
    die("Database connection failed");
}

// Fetch buildings
$buildingsQuery = "SELECT id, name FROM buildings ORDER BY name";
$buildingsStmt = $conn->prepare($buildingsQuery);
$buildingsStmt->execute();
$buildings = $buildingsStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$buildingsStmt->close();

// Fetch rooms
$roomsQuery = "SELECT id, name, building_id FROM rooms ORDER BY name";
$roomsStmt = $conn->prepare($roomsQuery);
$roomsStmt->execute();
$rooms = $roomsStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$roomsStmt->close();

// Fetch pc_units
$pcQuery = "SELECT id, terminal_number, room_id FROM pc_units WHERE status = 'Active' ORDER BY terminal_number";
$pcStmt = $conn->prepare($pcQuery);
$pcStmt->execute();
$pcs = $pcStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$pcStmt->close();
?>

<style>
    body, html { overflow: hidden !important; height: 100vh; }
    
    /* Red asterisk for required fields */
    label:has(+ input[required])::after,
    label:has(+ select[required])::after,
    label:has(+ textarea[required])::after,
    label.required::after {
        content: ' *';
        color: #ef4444;
        font-weight: bold;
    }
    
    /* DataTable container styling */
    #assetsTableContainer {
        width: 100%;
        overflow-x: auto;
    }

    /* Ensure table takes full width within container */
    #assetsTable {
        width: 100% !important;
        min-width: 600px;
    }

    /* Responsive table styling */
    #assetsTable_wrapper .dataTables_scrollHead,
    #assetsTable_wrapper .dataTables_scrollBody {
        width: 100%;
        overflow-x: auto;
    }

    #assetsTable_wrapper .dataTables_scrollHead table,
    #assetsTable_wrapper .dataTables_scrollBody table {
        width: 100%;
        margin: 0;
    }
    
    /* Style DataTables elements with Tailwind */
    #assetsTable_wrapper .dataTables_length select,
    #assetsTable_wrapper .dataTables_filter input {
        border: 1px solid #d1d5db;
        border-radius: 0.375rem;
        padding: 0.5rem 0.75rem;
        font-size: 0.875rem;
    }
    #assetsTable_wrapper .dataTables_length select:focus,
    #assetsTable_wrapper .dataTables_filter input:focus {
        border-color: #3b82f6;
        box-shadow: 0 0 0 1px #3b82f6;
    }

    #assetsTable_wrapper .dataTables_info {
        font-size: 0.875rem;
        color: #6b7280;
        margin-top: 1rem;
    }

    #assetsTable_wrapper .dataTables_paginate .paginate_button {
        padding: 0.5rem 0.75rem;
        margin: 0 0.25rem;
        border: 1px solid #d1d5db;
        border-radius: 0.375rem;
        font-size: 0.875rem;
        background-color: white;
        color: #374151;
    }
    #assetsTable_wrapper .dataTables_paginate .paginate_button:hover {
        background-color: #eff6ff;
        border-color: #3b82f6;
    }

    #assetsTable_wrapper .dataTables_paginate .paginate_button.current {
        background-color: #2563eb;
        color: white;
        border-color: #2563eb;
    }

    #assetsTable thead th {
        background-color: #f9fafb;
        color: #374151;
        font-weight: 600;
        padding: 0.75rem 1rem;
        text-align: left;
        border-bottom: 1px solid #e5e7eb;
        position: sticky;
        top: 0;
        z-index: 10;
    }

    #assetsTable tbody td {
        padding: 0.75rem 1rem;
        border-bottom: 1px solid #f3f4f6;
        color: #1f2937;
    }

    #assetsTable tbody tr:hover {
        background-color: #eff6ff;
    }
</style>

<!-- Main Content -->
<main class="p-2 bg-gray-50 h-screen overflow-hidden flex flex-col">
    <!-- Session Messages -->
    <?php include '../components/session_messages.php'; ?>
    
    <!-- Header Section -->
    <div class="bg-white rounded shadow-sm border border-gray-200 p-6 mb-4 flex-shrink-0">
        <h2 class="text-xl font-bold text-gray-800">
            Hi <?php echo htmlspecialchars($_SESSION['name'] ?? 'Student'); ?>, what do you need help with?
        </h2>
        <p class="text-sm text-gray-500 mt-1">Choose from the available options below.</p>
    </div>

    <!-- Options Grid -->
    <div class="flex-1 overflow-y-auto">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">

            <!-- Hardware Issue -->
            <div onclick="handleIssueClick('hardware')" role="button" tabindex="0"
                class="bg-white rounded shadow-sm p-6 min-h-[150px] cursor-pointer transition border border-gray-200 hover:border-[#1E3A8A] hover:shadow-md"><br>
                <div class="flex items-start gap-4">
                    <div class="w-10 h-10 bg-[#1E3A8A] bg-opacity-10 rounded flex items-center justify-center flex-shrink-0">
                        <i class="fa-solid fa-computer text-[#1E3A8A] text-base"></i>
                    </div>
                    <div>
                        <h3 class="text-sm font-semibold text-gray-800">Hardware Issue</h3>
                        <p class="text-xs text-gray-500 mt-1">Computer, printer, or equipment problems.</p>
                    </div>
                </div>
            </div>

            <!-- Software Issue -->
            <div onclick="handleIssueClick('software')" 
                class="bg-white rounded shadow-sm p-6 min-h-[140px] cursor-pointer transition border border-gray-200 hover:border-[#1E3A8A] hover:shadow-md"><br>
                <div class="flex items-start gap-4">
                    <div class="w-10 h-10 bg-[#1E3A8A] bg-opacity-10 rounded flex items-center justify-center flex-shrink-0">
                        <i class="fa-solid fa-microchip text-[#1E3A8A] text-base"></i>
                    </div>
                    <div>
                        <h3 class="text-sm font-semibold text-gray-800">Software Issue</h3>
                        <p class="text-xs text-gray-500 mt-1">Application crashes, system errors.</p>
                    </div>
                </div>
            </div>

            <!-- Network Issue -->
            <div onclick="handleIssueClick('network')" 
                class="bg-white rounded shadow-sm p-6 min-h-[140px] cursor-pointer transition border border-gray-200 hover:border-[#1E3A8A] hover:shadow-md"><br>
                <div class="flex items-start gap-4">
                    <div class="w-10 h-10 bg-[#1E3A8A] bg-opacity-10 rounded flex items-center justify-center flex-shrink-0">
                        <i class="fa-solid fa-globe text-[#1E3A8A] text-base"></i>
                    </div>
                    <div>
                        <h3 class="text-sm font-semibold text-gray-800">Network Issue</h3>
                        <p class="text-xs text-gray-500 mt-1">Internet connectivity, Wi-Fi issues.</p>
                    </div>
                </div>
            </div>

            <!-- Borrow Equipment -->
            <div onclick="openBorrowingModal()" 
                class="bg-white rounded shadow-sm p-6 min-h-[140px] cursor-pointer transition border border-gray-200 hover:border-[#1E3A8A] hover:shadow-md"><br>
                <div class="flex items-start gap-4">
                    <div class="w-10 h-10 bg-[#1E3A8A] bg-opacity-10 rounded flex items-center justify-center flex-shrink-0">
                        <i class="fa-solid fa-box text-[#1E3A8A] text-base"></i>
                    </div>
                    <div>
                        <h3 class="text-sm font-semibold text-gray-800">Borrow Equipment</h3>
                        <p class="text-xs text-gray-500 mt-1">Request to borrow laboratory equipment.</p>
                    </div>
                </div>
            </div>

            <!-- Laboratory Concern -->
            <div onclick="handleIssueClick('laboratory')" 
                class="bg-white rounded shadow-sm p-6 min-h-[140px] cursor-pointer transition border border-gray-200 hover:border-[#1E3A8A] hover:shadow-md"><br>
                <div class="flex items-start gap-4">
                    <div class="w-10 h-10 bg-[#1E3A8A] bg-opacity-10 rounded flex items-center justify-center flex-shrink-0">
                        <i class="fa-solid fa-building text-[#1E3A8A] text-base"></i>
                    </div>
                    <div>
                        <h3 class="text-sm font-semibold text-gray-800">Laboratory Concern</h3>
                        <p class="text-xs text-gray-500 mt-1">Lab facility access, safety issues.</p>
                    </div>
                </div>
            </div>

            <!-- Other -->
            <div onclick="handleIssueClick('other')" 
                class="bg-white rounded shadow-sm p-6 min-h-[140px] cursor-pointer transition border border-gray-200 hover:border-[#1E3A8A] hover:shadow-md"><br>
                <div class="flex items-start gap-4">
                    <div class="w-10 h-10 bg-[#1E3A8A] bg-opacity-10 rounded flex items-center justify-center flex-shrink-0">
                        <i class="fa-solid fa-flag text-[#1E3A8A] text-base"></i>
                    </div>
                    <div>
                        <h3 class="text-sm font-semibold text-gray-800">Other</h3>
                        <p class="text-xs text-gray-500 mt-1">General questions, account issues.</p>
                    </div>
                </div>
            </div>

        </div>
    </div>
</main>

<!-- Single Dynamic Issue Modal -->
<div id="issueModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-black bg-opacity-50 transition-opacity" onclick="closeIssueModal()"></div>
    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-2xl max-h-[95vh] overflow-hidden transform transition-all">
        <!-- Close Button -->
        <button type="button" onclick="closeIssueModal()" class="absolute top-4 right-4 z-10 text-gray-400 hover:text-gray-600 transition-colors" aria-label="Close">
            <i class="fas fa-times text-2xl"></i>
        </button>
        
        <!-- Modal Body -->
        <div class="overflow-hidden p-6">

        <form id="issueForm" class="space-y-4" method="post">
            <input type="hidden" name="category" id="issueCategory" value="">
            <input type="hidden" name="component_asset_id" id="componentAssetId" value="">
            
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2 required">Building</label>
                <select id="building" name="building_id" class="block w-full border-2 border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:border-[#1E3A8A] focus:ring-2 focus:ring-[#1E3A8A] focus:ring-opacity-50 transition-all" required>
                    <option value="" disabled selected>Select building</option>
                    <?php foreach ($buildings as $building): ?>
                        <option value="<?php echo $building['id']; ?>"><?php echo htmlspecialchars($building['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2 required">Room</label>
                <select id="room" name="room_id" class="block w-full border-2 border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:border-[#1E3A8A] focus:ring-2 focus:ring-[#1E3A8A] focus:ring-opacity-50 transition-all" required>
                    <option value="" disabled selected>Select room</option>
                    <?php foreach ($rooms as $room): ?>
                        <option value="<?php echo $room['id']; ?>" data-building="<?php echo $room['building_id']; ?>" data-name="<?php echo htmlspecialchars($room['name']); ?>"><?php echo htmlspecialchars($room['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2 required">Terminal No</label>
                <select id="terminal" name="pc_id" class="block w-full border-2 border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:border-[#1E3A8A] focus:ring-2 focus:ring-[#1E3A8A] focus:ring-opacity-50 transition-all" required>
                    <option value="" disabled selected>Select terminal</option>
                    <?php foreach ($pcs as $pc): ?>
                        <option value="<?php echo $pc['id']; ?>" data-room="<?php echo $pc['room_id']; ?>" data-name="<?php echo htmlspecialchars($pc['terminal_number']); ?>"><?php echo htmlspecialchars($pc['terminal_number']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Hardware Component Field (only for Hardware issues) -->
            <div id="hardwareComponentField" class="hidden">
                <label class="block text-sm font-semibold text-gray-700 mb-2 required">Hardware Component</label>
                <select name="hardware_component" id="hardwareComponent" class="block w-full border-2 border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:border-[#1E3A8A] focus:ring-2 focus:ring-[#1E3A8A] focus:ring-opacity-50 transition-all">
                    <option value="">Select terminal first</option>
                </select>
                <div id="componentLoading" class="hidden mt-2 text-sm text-gray-500">
                    <i class="fa-solid fa-spinner fa-spin"></i> Loading components...
                </div>
            </div>

            <!-- Hardware Component Others Field (only when "Others" is selected) -->
            <div id="hardwareOthersField" class="hidden">
                <label class="block text-sm font-semibold text-gray-700 mb-2 required">Specify Hardware Component</label>
                <input name="hardware_component_other" id="hardwareComponentOther" type="text" class="block w-full border-2 border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:border-[#1E3A8A] focus:ring-2 focus:ring-[#1E3A8A] focus:ring-opacity-50 transition-all" placeholder="e.g., Headset, Webcam, Printer">
            </div>

            <!-- Software Name Field (only for Software issues) -->
            <div id="softwareNameField" class="hidden">
                <label class="block text-sm font-semibold text-gray-700 mb-2 required">Software Name</label>
                <input name="software_name" type="text" class="block w-full border-2 border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:border-[#1E3A8A] focus:ring-2 focus:ring-[#1E3A8A] focus:ring-opacity-50 transition-all" placeholder="e.g., Microsoft Office, Adobe Photoshop">
            </div>

            <!-- Network Issue Type Field (only for Network issues) -->
            <div id="networkTypeField" class="hidden">
                <label class="block text-sm font-semibold text-gray-700 mb-2">Network Issue Type</label>
                <select name="network_issue_type" id="networkIssueType" class="block w-full border-2 border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:border-[#1E3A8A] focus:ring-2 focus:ring-[#1E3A8A] focus:ring-opacity-50 transition-all">
                    <option value="">Select issue type</option>
                    <option value="No Connection">No Connection</option>
                    <option value="Slow Internet">Slow Internet</option>
                    <option value="WiFi Problem">WiFi Problem</option>
                    <option value="Others">Others</option>
                </select>
            </div>

            <!-- Network Issue Others Field (only when "Others" is selected) -->
            <div id="networkOthersField" class="hidden">
                <label class="block text-sm font-semibold text-gray-700 mb-2 required">Specify Network Issue</label>
                <input name="network_issue_type_other" id="networkIssueTypeOther" type="text" class="block w-full border-2 border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:border-[#1E3A8A] focus:ring-2 focus:ring-[#1E3A8A] focus:ring-opacity-50 transition-all" placeholder="e.g., Port blocked, DNS issue, VPN problem">
            </div>

            <!-- Laboratory Concern Fields (only for Laboratory issues) -->
            <div id="laboratoryFieldsContainer" class="hidden space-y-4">
                 <div>
                     <label class="block text-sm font-medium text-gray-700">Concern Type</label>
                     <!-- removed static required; JS will set required when Laboratory is selected -->
                     <select name="laboratory_concern_type" id="laboratoryConcernType" class="mt-1 block w-full border rounded px-3 py-2">
                          <option value="">Select concern type</option>
                          <option value="Access Issue">Access Issue (Locked/Cannot Enter)</option>
                          <option value="Cleanliness">Cleanliness & Maintenance</option>
                          <option value="Safety Hazard">Safety Hazard</option>
                          <option value="Equipment Availability">Equipment Availability</option>
                          <option value="Air Conditioning">Air Conditioning/Ventilation</option>
                          <option value="Lighting">Lighting Issue</option>
                          <option value="Furniture">Furniture/Seating Issue</option>
                          <option value="Others">Others</option>
                      </select>
                  </div>

                 <div id="laboratoryConcernOthersField" class="hidden">
                     <label class="block text-sm font-medium text-gray-700">Specify Concern</label>
                     <input name="laboratory_concern_other" id="laboratoryConcernOther" type="text" class="mt-1 block w-full border rounded px-3 py-2" placeholder="Please describe your concern">
                 </div>
             </div>

            <!-- Other Concern Fields (only for Other issues) -->
            <div id="otherFieldsContainer" class="hidden space-y-4">
                 <div>
                     <label class="block text-sm font-medium text-gray-700">Concern Category</label>
                     <!-- removed static required; JS will set required when Other is selected -->
                     <select name="other_concern_category" id="otherConcernCategory" class="mt-1 block w-full border rounded px-3 py-2">
                         <option value="">Select category</option>
                         <option value="Account Issue">Account/Login Issue</option>
                         <option value="Access Request">Access/Permission Request</option>
                         <option value="Training Request">Training/Assistance Request</option>
                         <option value="Feedback">Feedback/Suggestion</option>
                         <option value="Lost & Found">Lost & Found</option>
                         <option value="General Inquiry">General Inquiry</option>
                         <option value="Others">Others</option>
                     </select>
                 </div>

                <div id="otherConcernOthersField" class="hidden">
                    <label class="block text-sm font-medium text-gray-700">Specify Category</label>
                    <input name="other_concern_other" id="otherConcernOther" type="text" class="mt-1 block w-full border rounded px-3 py-2" placeholder="Please describe the category">
                </div>
            </div>

            <div>
                <!-- Issue Title (restored). Hidden by default and made required via JS for non-laboratory categories -->
                <div id="issueTitleField" class="hidden">
                    <label class="block text-sm font-medium text-gray-700">Issue Title</label>
                    <input name="title" id="issueTitle" type="text" class="mt-1 block w-full border rounded px-3 py-2" placeholder="Brief description of the issue">
                </div>
             </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Description</label>
                <textarea name="description" rows="4" class="block w-full border-2 border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:border-[#1E3A8A] focus:ring-2 focus:ring-[#1E3A8A] focus:ring-opacity-50 transition-all" placeholder="Provide more details about the issue..."></textarea>
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2 required">Priority</label>
                <select name="priority" class="block w-full border-2 border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:border-[#1E3A8A] focus:ring-2 focus:ring-[#1E3A8A] focus:ring-opacity-50 transition-all" required>
                    <option value="Low">Low</option>
                    <option value="Medium" selected>Medium</option>
                    <option value="High">High</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Selection Preview</label>
                <input id="selectionPreview" type="text" class="block w-full border-2 border-gray-300 rounded-lg px-4 py-2.5 text-sm bg-gray-50 text-gray-600" disabled value="">
            </div>

        </form>
        </div>
        
        <!-- Modal Footer -->
        <div class="bg-gray-50 px-6 py-4 border-t border-gray-200 flex justify-end gap-3">
            <button type="button" onclick="closeIssueModal()" class="px-6 py-2.5 bg-gray-200 hover:bg-gray-300 text-gray-800 font-medium rounded-lg transition-colors">
                <i class="fas fa-times mr-2"></i>Cancel
            </button>
            <button type="submit" form="issueForm" id="submitBtn" class="px-6 py-2.5 bg-[#1E3A8A] hover:bg-[#1a2f6f] text-white font-medium rounded-lg transition-colors shadow-lg">
                <i class="fas fa-paper-plane mr-2"></i>Submit
            </button>
        </div>
    </div>
</div>

<!-- Loading Modal -->
<div id="loadingModal" class="hidden fixed inset-0 z-[60] flex items-center justify-center">
    <div class="absolute inset-0 bg-black bg-opacity-60"></div>
    <div class="bg-white rounded-2xl shadow-2xl p-10 z-10 flex flex-col items-center max-w-sm mx-4">
        <div class="relative">
            <div class="animate-spin rounded-full h-20 w-20 border-b-4 border-t-4 border-[#1E3A8A]"></div>
            <div class="absolute inset-0 flex items-center justify-center">
                <i class="fas fa-paper-plane text-[#1E3A8A] text-2xl animate-pulse"></i>
            </div>
        </div>
        <p class="text-xl font-bold text-gray-800 mt-6">Submitting Issue...</p>
        <p class="text-sm text-gray-500 mt-2">Please wait while we process your request</p>
    </div>
</div>

<script>
// Update the handleIssueClick function
function handleIssueClick(issueType) {
    const modal = document.getElementById('issueModal');
    const issueForm = document.getElementById('issueForm');
    const submitBtn = document.getElementById('submitBtn');
    const hardwareComponentField = document.getElementById('hardwareComponentField');
    const hardwareOthersField = document.getElementById('hardwareOthersField');
    const softwareNameField = document.getElementById('softwareNameField');
    const networkTypeField = document.getElementById('networkTypeField');
    const networkOthersField = document.getElementById('networkOthersField');
    const laboratoryFieldsContainer = document.getElementById('laboratoryFieldsContainer');
    const otherFieldsContainer = document.getElementById('otherFieldsContainer');
    const hardwareComponentInput = document.getElementById('hardwareComponent');
    const hardwareComponentOther = document.getElementById('hardwareComponentOther');
    const softwareNameInput = document.querySelector('input[name="software_name"]');
    const networkIssueTypeOther = document.getElementById('networkIssueTypeOther');
    const otherConcernCategory = document.getElementById('otherConcernCategory');
    const laboratoryConcernType = document.getElementById('laboratoryConcernType');
    const issueTitleField = document.getElementById('issueTitleField');
    const issueTitleInput = document.getElementById('issueTitle');
    
    // Reset form
    issueForm.reset();
    hardwareComponentField.classList.add('hidden');
    hardwareOthersField.classList.add('hidden');
    softwareNameField.classList.add('hidden');
    networkTypeField.classList.add('hidden');
    networkOthersField.classList.add('hidden');
    document.getElementById('laboratoryFieldsContainer')?.classList.add('hidden');
    document.getElementById('otherFieldsContainer')?.classList.add('hidden');
    hardwareComponentInput.removeAttribute('required');
    hardwareComponentOther.removeAttribute('required');
    softwareNameInput.removeAttribute('required');
    networkIssueTypeOther.removeAttribute('required');
    // Ensure these conditional fields are not required when hidden
    otherConcernCategory?.removeAttribute('required');
    laboratoryConcernType?.removeAttribute('required');
    // title handled via JS; remove required and hide initially
    issueTitleInput?.removeAttribute('required');
    issueTitleField?.classList.add('hidden');
    
    switch(issueType) {
        case 'hardware':
            issueForm.action = '../../controller/submit_issue.php';
            submitBtn.className = 'px-6 py-2.5 bg-[#1E3A8A] hover:bg-[#1a2f6f] text-white font-medium rounded-lg transition-colors shadow-lg';
            submitBtn.textContent = 'Submit';
            hardwareComponentField.classList.remove('hidden');
            hardwareComponentInput.setAttribute('required', 'required');
            // show and require title
            issueTitleField?.classList.remove('hidden');
            issueTitleInput?.setAttribute('required','required');
            document.getElementById('issueCategory').value = 'hardware';
            // Load components if terminal is already selected
            const terminalSelect = document.getElementById('terminal');
            if (terminalSelect && terminalSelect.value) {
                loadHardwareComponents(terminalSelect.value);
            }
            break;
            
        case 'software':
            issueForm.action = '../../controller/submit_issue.php';
            submitBtn.className = 'px-6 py-2.5 bg-[#1E3A8A] hover:bg-[#1a2f6f] text-white font-medium rounded-lg transition-colors shadow-lg';
            submitBtn.textContent = 'Submit';
            softwareNameField.classList.remove('hidden');
            softwareNameInput.setAttribute('required', 'required');
            // show and require title
            issueTitleField?.classList.remove('hidden');
            issueTitleInput?.setAttribute('required','required');
            document.getElementById('issueCategory').value = 'software';
            break;
            
        case 'network':
            issueForm.action = '../../controller/submit_issue.php';
            submitBtn.className = 'px-6 py-2.5 bg-[#1E3A8A] hover:bg-[#1a2f6f] text-white font-medium rounded-lg transition-colors shadow-lg';
            submitBtn.textContent = 'Submit';
            networkTypeField.classList.remove('hidden');
            // show and require title
            issueTitleField?.classList.remove('hidden');
            issueTitleInput?.setAttribute('required','required');
            document.getElementById('issueCategory').value = 'network';
            break;
            
        case 'borrow':
            openBorrowingModal();
            return;
        case 'laboratory':
            issueForm.action = '../../controller/submit_issue.php';
            submitBtn.className = 'px-6 py-2.5 bg-[#1E3A8A] hover:bg-[#1a2f6f] text-white font-medium rounded-lg transition-colors shadow-lg';
            submitBtn.textContent = 'Submit';
            document.getElementById('issueCategory').value = 'laboratory';
            document.getElementById('laboratoryFieldsContainer').classList.remove('hidden');
            // make lab concern type required when laboratory selected
            laboratoryConcernType?.setAttribute('required','required');
            // hide and do NOT require title for laboratory
            issueTitleField?.classList.add('hidden');
            issueTitleInput?.removeAttribute('required');
            break;
            
        case 'other':
            issueForm.action = '../../controller/submit_issue.php';
            submitBtn.className = 'px-6 py-2.5 bg-[#1E3A8A] hover:bg-[#1a2f6f] text-white font-medium rounded-lg transition-colors shadow-lg';
            submitBtn.textContent = 'Submit';
            document.getElementById('issueCategory').value = 'other';
            document.getElementById('otherFieldsContainer').classList.remove('hidden');
            // make other concern category required when Other selected
            otherConcernCategory?.setAttribute('required','required');
            // Do not show or require the Issue Title for Other concerns
            break;
            
        default:
            showNotification('Please select a valid option.', 'warning');
            return;
    }
    
    // Show modal
    modal.classList.remove('hidden');
    modal.classList.add('flex', 'items-center', 'justify-center');
}
    
function closeIssueModal() {
    const modal = document.getElementById('issueModal');
    const issueForm = document.getElementById('issueForm');
    const hardwareComponentField = document.getElementById('hardwareComponentField');
    const hardwareOthersField = document.getElementById('hardwareOthersField');
    const softwareNameField = document.getElementById('softwareNameField');
    const networkTypeField = document.getElementById('networkTypeField');
    const networkOthersField = document.getElementById('networkOthersField');
    const laboratoryFieldsContainer = document.getElementById('laboratoryFieldsContainer');
    const otherFieldsContainer = document.getElementById('otherFieldsContainer');
    const hardwareComponentInput = document.getElementById('hardwareComponent');
    const hardwareComponentOther = document.getElementById('hardwareComponentOther');
    const softwareNameInput = document.querySelector('input[name="software_name"]');
    const networkIssueTypeOther = document.getElementById('networkIssueTypeOther');
    const otherConcernCategory = document.getElementById('otherConcernCategory');
    const laboratoryConcernType = document.getElementById('laboratoryConcernType');
    const issueTitleField = document.getElementById('issueTitleField');
    const issueTitleInput = document.getElementById('issueTitle');
    
    modal.classList.add('hidden');
    modal.classList.remove('flex', 'items-center', 'justify-center');
    issueForm.reset();
    
    // Hide conditional fields
    hardwareComponentField.classList.add('hidden');
    hardwareOthersField.classList.add('hidden');
    softwareNameField.classList.add('hidden');
    networkTypeField.classList.add('hidden');
    networkOthersField.classList.add('hidden');
    laboratoryFieldsContainer?.classList.add('hidden');
    otherFieldsContainer?.classList.add('hidden');
    hardwareComponentInput.removeAttribute('required');
    hardwareComponentOther.removeAttribute('required');
    softwareNameInput.removeAttribute('required');
    networkIssueTypeOther.removeAttribute('required');
    // ensure removed on close
    otherConcernCategory?.removeAttribute('required');
    laboratoryConcernType?.removeAttribute('required');
    issueTitleInput?.removeAttribute('required');
    issueTitleField?.classList.add('hidden');
}

// Show/hide "Others" text field when hardware component or network issue type changes
document.addEventListener('DOMContentLoaded', function() {
    const roomSelect = document.getElementById('room');
    const terminalSelect = document.getElementById('terminal');
    const preview = document.getElementById('selectionPreview');
    const hardwareComponent = document.getElementById('hardwareComponent');
    const hardwareOthersField = document.getElementById('hardwareOthersField');
    const hardwareComponentOther = document.getElementById('hardwareComponentOther');
    const networkIssueType = document.getElementById('networkIssueType');
    const networkOthersField = document.getElementById('networkOthersField');
    const networkIssueTypeOther = document.getElementById('networkIssueTypeOther');
    const laboratoryConcernType = document.getElementById('laboratoryConcernType');
    const laboratoryConcernOthersField = document.getElementById('laboratoryConcernOthersField');
    const laboratoryConcernOther = document.getElementById('laboratoryConcernOther');
    const otherConcernCategory = document.getElementById('otherConcernCategory');
    const otherConcernOthersField = document.getElementById('otherConcernOthersField');
    const otherConcernOther = document.getElementById('otherConcernOther');
    
    function updatePreview() {
        const roomSelect = document.getElementById('room');
        const terminalSelect = document.getElementById('terminal');
        const roomOption = roomSelect.options[roomSelect.selectedIndex];
        const terminalOption = terminalSelect.options[terminalSelect.selectedIndex];
        const roomName = roomOption ? roomOption.getAttribute('data-name') : '';
        const terminalName = terminalOption ? terminalOption.getAttribute('data-name') : '';
        if (roomName && terminalName) {
            preview.value = `Room: ${roomName}, Terminal: ${terminalName}`;
        } else {
            preview.value = '';
        }
    }
    
    if (roomSelect && terminalSelect && preview) {
        roomSelect.addEventListener('change', updatePreview);
        terminalSelect.addEventListener('change', updatePreview);
    }
    
    // Filter rooms by building
    document.getElementById('building').addEventListener('change', function() {
        const selectedBuilding = this.value;
        const roomOptions = document.querySelectorAll('#room option');
        roomOptions.forEach(option => {
            if (option.value === '') return; // Skip placeholder
            if (option.getAttribute('data-building') === selectedBuilding) {
                option.style.display = '';
            } else {
                option.style.display = 'none';
            }
        });
        // Reset room and terminal
        document.getElementById('room').value = '';
        document.getElementById('terminal').value = '';
        updatePreview();
    });
    
    // Filter terminals by room
    document.getElementById('room').addEventListener('change', function() {
        const selectedRoom = this.value;
        const terminalOptions = document.querySelectorAll('#terminal option');
        terminalOptions.forEach(option => {
            if (option.value === '') return; // Skip placeholder
            if (option.getAttribute('data-room') === selectedRoom) {
                option.style.display = '';
            } else {
                option.style.display = 'none';
            }
        });
        // Reset terminal and components
        document.getElementById('terminal').value = '';
        resetHardwareComponents();
        updatePreview();
    });
    
    // Load hardware components when terminal is selected
    document.getElementById('terminal').addEventListener('change', function() {
        const pcId = this.value;
        if (pcId && document.getElementById('issueCategory').value === 'hardware') {
            loadHardwareComponents(pcId);
        }
        updatePreview();
    });
    
    // Toggle "Others" text field for Hardware Component
    if (hardwareComponent) {
        hardwareComponent.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const componentId = selectedOption?.getAttribute('data-component-id') || '0';
            document.getElementById('componentAssetId').value = componentId;
            
            if (this.value === 'Others') {
                hardwareOthersField.classList.remove('hidden');
                hardwareComponentOther.setAttribute('required', 'required');
            } else {
                hardwareOthersField.classList.add('hidden');
                hardwareComponentOther.removeAttribute('required');
                hardwareComponentOther.value = '';
            }
        });
    }
    
    // Toggle "Others" text field for Network Issue Type
    if (networkIssueType) {
        networkIssueType.addEventListener('change', function() {
            if (this.value === 'Others') {
                networkOthersField.classList.remove('hidden');
                networkIssueTypeOther.setAttribute('required', 'required');
            } else {
                networkOthersField.classList.add('hidden');
                networkIssueTypeOther.removeAttribute('required');
                networkIssueTypeOther.value = '';
            }
        });
    }
    
    // Toggle "Others" text field for Laboratory Concern
    if (laboratoryConcernType) {
        laboratoryConcernType.addEventListener('change', function() {
            if (this.value === 'Others') {
                laboratoryConcernOthersField.classList.remove('hidden');
                laboratoryConcernOther.setAttribute('required', 'required');
            } else {
                laboratoryConcernOthersField.classList.add('hidden');
                laboratoryConcernOther.removeAttribute('required');
                laboratoryConcernOther.value = '';
            }
        });
    }
    
    // Toggle "Others" text field for Other Concern
    if (otherConcernCategory) {
        otherConcernCategory.addEventListener('change', function() {
            if (this.value === 'Others') {
                otherConcernOthersField.classList.remove('hidden');
                otherConcernOther.setAttribute('required', 'required');
            } else {
                otherConcernOthersField.classList.add('hidden');
                otherConcernOther.removeAttribute('required');
                otherConcernOther.value = '';
            }
        });
    }
});

// Close modal when clicking outside
document.addEventListener('click', function(e) {
    const modal = document.getElementById('issueModal');
    if (e.target === modal) {
        closeIssueModal();
    }
});

// Load hardware components for selected PC
async function loadHardwareComponents(pcId) {
    const componentSelect = document.getElementById('hardwareComponent');
    const loadingDiv = document.getElementById('componentLoading');
    const hardwareOthersField = document.getElementById('hardwareOthersField');
    const hardwareComponentOther = document.getElementById('hardwareComponentOther');
    
    // Show loading
    loadingDiv.classList.remove('hidden');
    componentSelect.disabled = true;
    
    // Reset
    componentSelect.innerHTML = '<option value="">Loading...</option>';
    hardwareOthersField.classList.add('hidden');
    hardwareComponentOther.removeAttribute('required');
    hardwareComponentOther.value = '';
    
    try {
        const response = await fetch(`../../controller/get_pc_components.php?pc_id=${pcId}`);
        const data = await response.json();
        
        if (data.success && data.components && data.components.length > 0) {
            componentSelect.innerHTML = '<option value="">Select component</option>';
            
            data.components.forEach(component => {
                const option = document.createElement('option');
                option.value = component.asset_tag;
                option.textContent = `${component.name}${component.brand ? ' - ' + component.brand : ''}${component.model ? ' ' + component.model : ''}`;
                option.setAttribute('data-component-id', component.id);
                componentSelect.appendChild(option);
            });
            
            // Add "Others" option
            const othersOption = document.createElement('option');
            othersOption.value = 'Others';
            othersOption.textContent = 'Others (Not listed)';
            othersOption.setAttribute('data-component-id', '0');
            componentSelect.appendChild(othersOption);
        } else {
            componentSelect.innerHTML = '<option value="">No components found</option><option value="Others" data-component-id="0">Others</option>';
        }
    } catch (error) {
        console.error('Error loading components:', error);
        componentSelect.innerHTML = '<option value="">Error loading components</option><option value="Others">Others</option>';
    } finally {
        loadingDiv.classList.add('hidden');
        componentSelect.disabled = false;
    }
}

// Reset hardware components dropdown
function resetHardwareComponents() {
    const componentSelect = document.getElementById('hardwareComponent');
    const hardwareOthersField = document.getElementById('hardwareOthersField');
    const hardwareComponentOther = document.getElementById('hardwareComponentOther');
    
    componentSelect.innerHTML = '<option value="">Select terminal first</option>';
    componentSelect.disabled = false;
    hardwareOthersField.classList.add('hidden');
    hardwareComponentOther.removeAttribute('required');
    hardwareComponentOther.value = '';
}
</script>

<!-- Borrowing Equipment Modal -->
<div id="borrowingModal" class="fixed inset-0 bg-gray-600 bg-opacity-75 hidden z-50 overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen px-4 py-6">
        <div class="relative bg-white rounded-lg shadow-xl w-full max-w-5xl max-h-[90vh] flex flex-col">
            <!-- Close Button -->
            <button onclick="closeBorrowingModal()" class="absolute top-4 right-4 z-10 text-gray-400 hover:text-gray-600 transition-colors">
                <i class="fas fa-times text-2xl"></i>
            </button>

            <!-- Progress Steps -->
            <div class="px-6 py-4 border-b flex-shrink-0 bg-blue-50">
                <div class="flex justify-between items-center">
                    <div class="flex-1 flex items-center">
                        <div id="step1Indicator" class="w-8 h-8 rounded-full bg-[#1E3A8A] text-white flex items-center justify-center font-bold">1</div>
                        <div class="flex-1 h-1 bg-gray-300 mx-2">
                            <div id="progress1" class="h-full bg-[#1E3A8A]" style="width: 0%"></div>
                        </div>
                    </div>
                    <div class="flex-1 flex items-center">
                        <div id="step2Indicator" class="w-8 h-8 rounded-full bg-gray-300 text-gray-600 flex items-center justify-center font-bold">2</div>
                        <div class="flex-1 h-1 bg-gray-300 mx-2">
                            <div id="progress2" class="h-full bg-[#1E3A8A]" style="width: 0%"></div>
                        </div>
                    </div>
                    <div class="flex-1 flex items-center">
                        <div id="step3Indicator" class="w-8 h-8 rounded-full bg-gray-300 text-gray-600 flex items-center justify-center font-bold">3</div>
                    </div>
                </div>
                <div class="flex justify-between mt-2 text-xs text-gray-600">
                    <span class="flex-1 text-center">Select Asset</span>
                    <span class="flex-1 text-center">Terms & Conditions</span>
                    <span class="flex-1 text-center">Preview & Submit</span>
                </div>
            </div>

            <!-- Modal Body -->
            <div class="flex-1 overflow-hidden px-6 py-4 bg-blue-50/30">
                <!-- Step 1: Asset Selection -->
                <div id="step1" class="step-content">
                    <h4 class="text-lg font-semibold mb-4">Select Equipment to Borrow</h4>
                    <div id="assetsLoading" class="text-center py-8">
                        <i class="fa-solid fa-spinner fa-spin text-4xl text-[#1E3A8A]"></i>
                        <p class="mt-2 text-gray-600">Loading available assets...</p>
                    </div>
                    <div id="assetsTableContainer" class="hidden">
                        <div class="mb-3 p-2 bg-blue-50 rounded text-sm text-blue-800 border border-blue-200">
                            <i class="fa-solid fa-info-circle mr-2"></i>
                            Click on any row to select an asset
                        </div>
                        <div class="overflow-x-auto border rounded-lg">
                            <table id="assetsTable" class="display text-sm" style="width: 100%;">
                                <thead>
                                    <tr>
                                        <th>Asset Tag</th>
                                        <th>Asset Name</th>
                                        <th>Type</th>
                                        <th>Brand</th>
                                        <th>Model</th>
                                        <th>Location</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                    <div id="assetsError" class="hidden text-center py-8 text-red-600"></div>
                    
                    <!-- Next Button for Step 1 -->
                    <div class="mt-6 flex justify-end pb-4">
                        <button type="button" id="step1NextBtn" onclick="proceedToDetails()" class="bg-[#1E3A8A] hover:bg-[#152e6e] text-white px-6 py-2 rounded-lg font-medium shadow-lg hidden">
                            Next: Fill Details<i class="fa-solid fa-arrow-right ml-2"></i>
                        </button>
                    </div>
                </div>

                <!-- Step 1b: Borrowing Details Form (sub-step) -->
                <div id="step1b" class="step-content hidden">
                    <h4 class="text-lg font-semibold mb-4">Borrowing Details</h4>
                    
                    <div class="p-4 bg-blue-50 rounded-lg mb-4 border border-blue-200">
                        <h5 class="font-semibold mb-2 text-blue-900">Selected Asset:</h5>
                        <div id="selectedAssetInfo" class="text-sm text-blue-800"></div>
                    </div>
                    
                    <div class="space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Borrow Date</label>
                                <input type="date" id="borrowDate" class="w-full border rounded px-3 py-2" required>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Expected Return Date</label>
                                <input type="date" id="returnDate" class="w-full border rounded px-3 py-2" required>
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Purpose</label>
                            <textarea id="borrowPurpose" rows="4" class="w-full border rounded px-3 py-2" placeholder="Describe the purpose of borrowing this equipment..." required></textarea>
                        </div>
                        
                        <div class="p-3 bg-gray-50 rounded border border-gray-300">
                            <p class="text-sm text-gray-700">
                                <i class="fa-solid fa-info-circle text-blue-600 mr-2"></i>
                                Please ensure all information is accurate. You will need to agree to terms and conditions in the next step.
                            </p>
                        </div>
                    </div>
                    
                    <!-- Navigation Buttons for Step 1b -->
                    <div class="mt-6 flex justify-between pb-4">
                        <button type="button" onclick="backToAssetSelection()" class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-6 py-2 rounded-lg font-medium shadow-lg">
                            <i class="fa-solid fa-arrow-left mr-2"></i>Back to Selection
                        </button>
                        <button type="button" onclick="proceedToTerms()" class="bg-[#1E3A8A] hover:bg-[#152e6e] text-white px-6 py-2 rounded-lg font-medium shadow-lg">
                            Next: Terms & Conditions<i class="fa-solid fa-arrow-right ml-2"></i>
                        </button>
                    </div>
                </div>

                <!-- Step 2: Terms & Conditions -->
                <div id="step2" class="step-content hidden">
                    <h4 class="text-lg font-semibold mb-4">Terms and Conditions</h4>
                    <div class="border rounded-lg p-4 bg-gray-50 max-h-96 overflow-y-auto" id="termsContainer">
                        <div class="prose prose-sm max-w-none">
                            <h5 class="font-bold text-gray-800 mb-3">Equipment Borrowing Terms and Conditions</h5>
                            
                            <p class="mb-3">Please read and understand the following terms and conditions before borrowing any equipment:</p>
                            
                            <h6 class="font-semibold text-gray-800 mt-4 mb-2">1. Eligibility and Authorization</h6>
                            <ul class="list-disc pl-5 space-y-1 text-gray-700 text-sm">
                                <li>Only enrolled students and authorized faculty members may borrow equipment.</li>
                                <li>Valid student/faculty ID must be presented upon pickup.</li>
                                <li>All borrowing requests are subject to approval by laboratory staff.</li>
                            </ul>
                            
                            <h6 class="font-semibold text-gray-800 mt-4 mb-2">2. Borrower's Responsibilities</h6>
                            <ul class="list-disc pl-5 space-y-1 text-gray-700 text-sm">
                                <li>The borrower is solely responsible for the equipment from the time of pickup until return.</li>
                                <li>Equipment must be used only for the stated academic purpose.</li>
                                <li>Equipment must not be lent, transferred, or subleased to any other person.</li>
                                <li>The borrower must handle the equipment with care and follow all safety guidelines.</li>
                                <li>Any malfunction or damage must be reported immediately to laboratory staff.</li>
                            </ul>
                            
                            <h6 class="font-semibold text-gray-800 mt-4 mb-2">3. Inspection and Condition</h6>
                            <ul class="list-disc pl-5 space-y-1 text-gray-700 text-sm">
                                <li>Equipment will be inspected before lending and upon return.</li>
                                <li>The borrower must verify the equipment's condition and functionality at pickup.</li>
                                <li>Any existing damage must be noted and acknowledged before leaving the laboratory.</li>
                            </ul>
                            
                            <h6 class="font-semibold text-gray-800 mt-4 mb-2">4. Return Policy</h6>
                            <ul class="list-disc pl-5 space-y-1 text-gray-700 text-sm">
                                <li>Equipment must be returned on or before the specified return date.</li>
                                <li>Late returns may result in suspension of borrowing privileges.</li>
                                <li>Equipment must be returned in the same condition as when borrowed.</li>
                                <li>All accessories, cables, and components must be returned together.</li>
                            </ul>
                            
                            <h6 class="font-semibold text-gray-800 mt-4 mb-2">5. Damage, Loss, and Liability</h6>
                            <ul class="list-disc pl-5 space-y-1 text-gray-700 text-sm">
                                <li>The borrower is liable for any damage, loss, or theft of the equipment.</li>
                                <li>Repair or replacement costs will be charged to the borrower's account.</li>
                                <li>Deliberate damage or misuse may result in disciplinary action.</li>
                                <li>In case of theft, a police report must be filed and submitted to the university.</li>
                            </ul>
                            
                            <h6 class="font-semibold text-gray-800 mt-4 mb-2">6. Prohibited Use</h6>
                            <ul class="list-disc pl-5 space-y-1 text-gray-700 text-sm">
                                <li>Equipment must not be used for commercial purposes.</li>
                                <li>Equipment must not be taken outside university premises without authorization.</li>
                                <li>Equipment must not be modified, disassembled, or repaired by the borrower.</li>
                            </ul>
                            
                            <h6 class="font-semibold text-gray-800 mt-4 mb-2">7. Penalties and Sanctions</h6>
                            <ul class="list-disc pl-5 space-y-1 text-gray-700 text-sm">
                                <li>Failure to return equipment on time may result in borrowing privilege suspension.</li>
                                <li>Repeated violations may lead to permanent revocation of borrowing privileges.</li>
                                <li>Academic holds may be placed on accounts with unreturned or damaged equipment.</li>
                            </ul>
                            
                            <h6 class="font-semibold text-gray-800 mt-4 mb-2">8. Acknowledgment</h6>
                            <p class="text-gray-700 text-sm">By signing this agreement, I acknowledge that I have read, understood, and agree to comply with all the terms and conditions stated above. I understand that violation of these terms may result in penalties, financial liability, and/or disciplinary action.</p>
                            
                            <div class="mt-6 p-3 bg-blue-50 border border-blue-300 rounded">
                                <p class="text-sm text-blue-800 font-medium">
                                    <i class="fa-solid fa-exclamation-triangle mr-2"></i>
                                    Important: Please scroll through and read all terms before proceeding.
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-4">
                        <label class="flex items-start space-x-3">
                            <input type="checkbox" id="agreeTerms" class="mt-1" onchange="toggleTermsButton()">
                            <span class="text-sm text-gray-700">
                                I have read and agree to all the terms and conditions stated above. I understand my responsibilities and liabilities as a borrower.
                            </span>
                        </label>
                    </div>
                    
                    <div id="scrollWarning" class="hidden mt-4 p-3 bg-red-50 border border-red-300 rounded">
                        <p class="text-sm text-red-700">
                            <i class="fa-solid fa-exclamation-circle mr-2"></i>
                            Please scroll through the entire terms and conditions document before proceeding.
                        </p>
                    </div>
                    
                    <!-- Navigation Buttons for Step 2 -->
                    <div class="mt-6 flex justify-between pb-4">
                        <button type="button" onclick="backToDetails()" class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-6 py-2 rounded-lg font-medium shadow-lg">
                            <i class="fa-solid fa-arrow-left mr-2"></i>Back to Details
                        </button>
                        <button type="button" id="termsNextBtn" onclick="proceedToPreview()" class="bg-[#1E3A8A] hover:bg-[#152e6e] text-white px-6 py-2 rounded-lg font-medium shadow-lg opacity-50 cursor-not-allowed" disabled>
                            Next: Preview & Submit<i class="fa-solid fa-arrow-right ml-2"></i>
                        </button>
                    </div>
                </div>

                <!-- Step 3: Preview & Submit -->
                <div id="step3" class="step-content hidden overflow-y-auto max-h-[calc(90vh-280px)]">
                    <h4 class="text-lg font-semibold mb-4">Equipment Borrowing Agreement Preview</h4>
                    
                    <!-- Printable Document Preview -->
                    <div id="printableDocument" class="border-2 border-gray-300 rounded-lg p-8 bg-white shadow-inner">
                        <!-- Document Header -->
                        <div class="text-center mb-6 pb-4 border-b-2 border-gray-800">
                            <h3 class="text-2xl font-bold text-gray-800">QUEZON CITY UNIVERSITY</h3>
                            <p class="text-sm text-gray-600">Asset Management System</p>
                            <h4 class="text-xl font-semibold mt-3 text-gray-800">EQUIPMENT BORROWING AGREEMENT</h4>
                        </div>
                        
                        <!-- Document Content -->
                        <div class="space-y-4 text-sm">
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <p class="text-gray-600">Date:</p>
                                    <p class="font-semibold" id="previewDate"></p>
                                </div>
                                <div>
                                    <p class="text-gray-600">Reference No:</p>
                                    <p class="font-semibold" id="previewRefNo"></p>
                                </div>
                            </div>
                            
                            <div class="mt-6">
                                <h5 class="font-bold text-gray-800 mb-3 text-base">BORROWER INFORMATION</h5>
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <p class="text-gray-600">Name:</p>
                                        <p class="font-semibold"><?php echo htmlspecialchars($_SESSION['full_name']); ?></p>
                                    </div>
                                    <div>
                                        <p class="text-gray-600">ID Number:</p>
                                        <p class="font-semibold"><?php echo htmlspecialchars($_SESSION['id_number'] ?? 'N/A'); ?></p>
                                    </div>
                                    <div>
                                        <p class="text-gray-600">Role:</p>
                                        <p class="font-semibold"><?php echo htmlspecialchars($_SESSION['role']); ?></p>
                                    </div>
                                    <div>
                                        <p class="text-gray-600">Email:</p>
                                        <p class="font-semibold"><?php echo htmlspecialchars($_SESSION['email'] ?? 'N/A'); ?></p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mt-6">
                                <h5 class="font-bold text-gray-800 mb-3 text-base">EQUIPMENT DETAILS</h5>
                                <div class="space-y-2">
                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <p class="text-gray-600">Asset Tag:</p>
                                            <p class="font-semibold" id="previewAssetTag"></p>
                                        </div>
                                        <div>
                                            <p class="text-gray-600">Asset Name:</p>
                                            <p class="font-semibold" id="previewAssetName"></p>
                                        </div>
                                        <div>
                                            <p class="text-gray-600">Type:</p>
                                            <p class="font-semibold" id="previewAssetType"></p>
                                        </div>
                                        <div>
                                            <p class="text-gray-600">Brand/Model:</p>
                                            <p class="font-semibold" id="previewBrandModel"></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mt-6">
                                <h5 class="font-bold text-gray-800 mb-3 text-base">BORROWING DETAILS</h5>
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <p class="text-gray-600">Borrow Date:</p>
                                        <p class="font-semibold" id="previewBorrowDate"></p>
                                    </div>
                                    <div>
                                        <p class="text-gray-600">Expected Return Date:</p>
                                        <p class="font-semibold" id="previewReturnDate"></p>
                                    </div>
                                </div>
                                <div class="mt-3">
                                    <p class="text-gray-600">Purpose:</p>
                                    <p class="font-semibold" id="previewPurpose"></p>
                                </div>
                            </div>
                            
                            <div class="mt-6 p-4 bg-gray-50 rounded border border-gray-300">
                                <h5 class="font-bold text-gray-800 mb-2 text-base">BORROWER'S DECLARATION</h5>
                                <p class="text-xs text-gray-700 leading-relaxed">
                                    I hereby acknowledge that I have received the above-mentioned equipment in good working condition. 
                                    I agree to take full responsibility for the equipment and to return it on or before the expected return date 
                                    in the same condition as received. I have read, understood, and agree to comply with all terms and conditions 
                                    of the Equipment Borrowing Agreement.
                                </p>
                            </div>
                            
                            <!-- Signature Section -->
                            <div class="mt-8 grid grid-cols-2 gap-8">
                                <div>
                                    <p class="text-gray-600 mb-2">Borrower's E-Signature:</p>
                                    <div class="border-2 border-dashed border-gray-400 rounded p-4 bg-gray-50 min-h-24 flex items-center justify-center">
                                        <?php
                                        // Get user's e-signature
                                        require_once '../../model/Database.php';
                                        $user_id = $_SESSION['user_id'];
                                        $signature_path = null;
                                        try {
                                            $db = new Database();
                                            $conn = $db->getConnection();
                                            $stmt = $conn->prepare("SELECT e_signature FROM users WHERE id = ?");
                                            $stmt->execute([$user_id]);
                                            $signature_file = $stmt->fetchColumn();
                                            if ($signature_file && file_exists('../../uploads/signatures/' . $signature_file)) {
                                                $signature_path = '../../uploads/signatures/' . $signature_file;
                                            }
                                        } catch (PDOException $e) {
                                            // Handle error silently
                                        }
                                        
                                        if ($signature_path): ?>
                                            <img src="<?php echo htmlspecialchars($signature_path); ?>" alt="E-Signature" class="max-h-20 max-w-full object-contain" id="borrowerSignature">
                                        <?php else: ?>
                                            <p class="text-red-600 text-sm text-center">
                                                <i class="fa-solid fa-exclamation-triangle mr-2"></i>
                                                No e-signature found. Please upload your signature in the <a href="e-signature.php" class="text-blue-600 underline">E-Signature page</a>.
                                            </p>
                                        <?php endif; ?>
                                    </div>
                                    <p class="mt-2 text-center font-semibold border-t border-gray-400 pt-1"><?php echo htmlspecialchars($_SESSION['full_name']); ?></p>
                                    <p class="text-center text-xs text-gray-600">Borrower's Name</p>
                                </div>
                                
                                <div>
                                    <p class="text-gray-600 mb-2">Released By:</p>
                                    <div class="border-2 border-dashed border-gray-400 rounded p-4 bg-gray-50 min-h-24 flex items-center justify-center">
                                        <p class="text-gray-400 text-sm text-center">
                                            <i class="fa-solid fa-user-clock text-2xl mb-2 block"></i>
                                            To be signed by Laboratory Staff
                                        </p>
                                    </div>
                                    <p class="mt-2 text-center font-semibold border-t border-gray-400 pt-1">_______________________</p>
                                    <p class="text-center text-xs text-gray-600">Laboratory Staff Signature</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-6 p-4 bg-blue-50 rounded border border-blue-300 mb-4">
                        <p class="text-sm text-blue-800">
                            <i class="fa-solid fa-info-circle mr-2"></i>
                            This is a preview of your borrowing agreement. You can print this document after submission for your records.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="px-6 py-4 bg-blue-50 rounded-b-lg flex justify-between items-center flex-shrink-0 border-t border-blue-200">
                <div class="text-sm text-gray-600 font-medium" id="stepIndicatorText">Step 1 of 4</div>
                <div class="flex gap-3">
                    <button type="button" id="submitBorrowBtn" onclick="submitBorrowing()" class="bg-[#1E3A8A] hover:bg-[#152e6e] text-white px-6 py-2.5 rounded-lg font-medium shadow-lg hidden transition-all">
                        <i class="fa-solid fa-check mr-2"></i>Submit Request
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>



<script>
let currentStep = 1;
let selectedAsset = null;
let borrowingData = {};
let hasScrolledTerms = false;
let availableAssets = [];
let assetsDataTable = null;

// Open Borrowing Modal
async function openBorrowingModal() {
    document.getElementById('borrowingModal').classList.remove('hidden');
    currentStep = 1;
    updateStepDisplay();
    loadBorrowableAssets();
}

// Close Borrowing Modal
function closeBorrowingModal() {
    document.getElementById('borrowingModal').classList.add('hidden');
    if (assetsDataTable) {
        assetsDataTable.destroy();
        assetsDataTable = null;
    }
    resetBorrowingModal();
}

// Reset Modal
function resetBorrowingModal() {
    currentStep = 1;
    selectedAsset = null;
    borrowingData = {};
    hasScrolledTerms = false;
    
    // Reset form fields
    document.getElementById('agreeTerms').checked = false;
    document.getElementById('borrowDate').value = '';
    document.getElementById('returnDate').value = '';
    document.getElementById('borrowPurpose').value = '';
    document.getElementById('step1NextBtn').classList.add('hidden');
    
    // Hide all steps
    document.getElementById('step1').classList.add('hidden');
    document.getElementById('step1b').classList.add('hidden');
    document.getElementById('step2').classList.add('hidden');
    document.getElementById('step3').classList.add('hidden');
    
    // Show step 1
    document.getElementById('step1').classList.remove('hidden');
    
    // Reset table selection
    $('#assetsTable tbody tr').removeClass('bg-blue-100 border-l-4 border-blue-600');
    
    // Reset loading/error states
    document.getElementById('assetsLoading').classList.remove('hidden');
    document.getElementById('assetsTableContainer').classList.add('hidden');
    document.getElementById('assetsError').classList.add('hidden');
    
    // Reset terms scroll
    document.getElementById('scrollWarning').classList.add('hidden');
    const termsNextBtn = document.getElementById('termsNextBtn');
    if (termsNextBtn) {
        termsNextBtn.disabled = true;
        termsNextBtn.classList.add('opacity-50', 'cursor-not-allowed');
    }
    
    // Update step display
    updateStepDisplay();
}

// Load Borrowable Assets
async function loadBorrowableAssets() {
    const loadingDiv = document.getElementById('assetsLoading');
    const tableContainer = document.getElementById('assetsTableContainer');
    const errorDiv = document.getElementById('assetsError');
    
    try {
        const response = await fetch('../../controller/get_borrowable_assets.php', {
            credentials: 'include'
        });
        
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        
        const data = await response.json();
        
        if (data.success && data.assets && data.assets.length > 0) {
            availableAssets = data.assets;
            displayAssetsTable(data.assets);
            loadingDiv.classList.add('hidden');
            tableContainer.classList.remove('hidden');
        } else {
            loadingDiv.classList.add('hidden');
            errorDiv.classList.remove('hidden');
            errorDiv.innerHTML = '<i class="fa-solid fa-box-open text-4xl text-gray-400 mb-2"></i><p>No borrowable assets available at the moment.</p>';
        }
    } catch (error) {
        console.error('Error loading assets:', error);
        loadingDiv.classList.add('hidden');
        errorDiv.classList.remove('hidden');
        errorDiv.innerHTML = '<i class="fa-solid fa-exclamation-triangle text-4xl text-red-500 mb-2"></i><p>Error loading assets. Please try again.</p>';
    }
}

// Display Assets in DataTable
function displayAssetsTable(assets) {
    // Destroy existing DataTable if it exists
    if (assetsDataTable) {
        assetsDataTable.destroy();
    }
    
    // Prepare data for DataTable with asset IDs
    const tableData = assets.map(asset => ({
        id: asset.id,
        data: [
            asset.asset_tag,
            asset.asset_name,
            asset.asset_type,
            asset.brand || '-',
            asset.model || '-',
            asset.room_name || '-'
        ]
    }));
    
    // Initialize DataTable
    assetsDataTable = $('#assetsTable').DataTable({
        data: tableData.map(item => item.data),
        pageLength: 3,
        lengthMenu: [[3, 10, 25, 50], [3, 10, 25, 50]],
        language: {
            search: "Search assets:",
            lengthMenu: "Show _MENU_ assets",
            info: "Showing _START_ to _END_ of _TOTAL_ assets",
            paginate: {
                first: "First",
                last: "Last",
                next: "Next",
                previous: "Previous"
            },
            emptyTable: "No borrowable assets available",
            zeroRecords: "No matching assets found"
        },
        order: [[0, 'asc']], // Sort by asset tag by default
        responsive: true,
        autoWidth: false,
        createdRow: function(row, data, dataIndex) {
            // Store asset ID in row
            $(row).attr('data-asset-id', tableData[dataIndex].id);
            // Make row clickable
            $(row).addClass('cursor-pointer hover:bg-blue-50 transition-colors');
        }
    });
    
    // Add click event to rows
    $('#assetsTable tbody').on('click', 'tr', function() {
        const assetId = $(this).attr('data-asset-id');
        if (assetId) {
            selectAssetFromTable(parseInt(assetId));
        }
    });
}

// Select Asset from Table
function selectAssetFromTable(assetId) {
    selectedAsset = availableAssets.find(a => a.id == assetId);
    
    // Show next button
    document.getElementById('step1NextBtn').classList.remove('hidden');
    
    // Highlight selected row
    $('#assetsTable tbody tr').removeClass('bg-blue-100 border-l-4 border-blue-600');
    $('#assetsTable tbody tr[data-asset-id="' + assetId + '"]').addClass('bg-blue-100 border-l-4 border-blue-600');
}

// Proceed to Details Form
function proceedToDetails() {
    if (!selectedAsset) {
        showNotification('Please select an asset first.', 'warning');
        return;
    }
    
    // Hide step 1, show step 1b
    document.getElementById('step1').classList.add('hidden');
    document.getElementById('step1b').classList.remove('hidden');
    
    // Update step indicator
    currentStep = 1.5;
    updateStepDisplay();
    
    // Display selected asset info
    document.getElementById('selectedAssetInfo').innerHTML = `
        <p><strong>Asset Tag:</strong> ${selectedAsset.asset_tag}</p>
        <p><strong>Name:</strong> ${selectedAsset.asset_name}</p>
        <p><strong>Type:</strong> ${selectedAsset.asset_type}</p>
        ${selectedAsset.brand ? `<p><strong>Brand/Model:</strong> ${selectedAsset.brand} ${selectedAsset.model || ''}</p>` : ''}
    `;
    
    // Set minimum dates
    const today = new Date().toISOString().split('T')[0];
    document.getElementById('borrowDate').min = today;
    document.getElementById('returnDate').min = today;
}

// Back to Asset Selection
function backToAssetSelection() {
    document.getElementById('step1b').classList.add('hidden');
    document.getElementById('step1').classList.remove('hidden');
    currentStep = 1;
    updateStepDisplay();
}

// Proceed to Terms
function proceedToTerms() {
    const borrowDate = document.getElementById('borrowDate').value;
    const returnDate = document.getElementById('returnDate').value;
    const purpose = document.getElementById('borrowPurpose').value;
    
    if (!borrowDate || !returnDate || !purpose) {
        showNotification('Please fill in all borrowing details.', 'warning');
        return;
    }
    
    if (new Date(returnDate) <= new Date(borrowDate)) {
        showNotification('Return date must be after borrow date.', 'error');
        return;
    }
    
    borrowingData = {
        asset_id: selectedAsset.id,
        asset_tag: selectedAsset.asset_tag,
        asset_name: selectedAsset.asset_name,
        asset_type: selectedAsset.asset_type,
        brand: selectedAsset.brand,
        model: selectedAsset.model,
        borrow_date: borrowDate,
        return_date: returnDate,
        purpose: purpose
    };
    
    // Hide step 1b, show step 2
    document.getElementById('step1b').classList.add('hidden');
    document.getElementById('step2').classList.remove('hidden');
    currentStep = 2;
    updateStepDisplay();
}

// Back to Details
function backToDetails() {
    document.getElementById('step2').classList.add('hidden');
    document.getElementById('step1b').classList.remove('hidden');
    currentStep = 1.5;
    updateStepDisplay();
}

// Proceed to Preview
function proceedToPreview() {
    if (!hasScrolledTerms) {
        document.getElementById('scrollWarning').classList.remove('hidden');
        return;
    }
    
    if (!document.getElementById('agreeTerms').checked) {
        showNotification('Please agree to the terms and conditions.', 'warning');
        return;
    }
    
    // Populate preview
    populatePreview();
    
    // Hide step 2, show step 3
    document.getElementById('step2').classList.add('hidden');
    document.getElementById('step3').classList.remove('hidden');
    currentStep = 3;
    updateStepDisplay();
}

// Track Terms Scroll
document.getElementById('termsContainer')?.addEventListener('scroll', function() {
    const container = this;
    const scrollPercentage = (container.scrollTop / (container.scrollHeight - container.clientHeight)) * 100;
    if (scrollPercentage > 90) {
        hasScrolledTerms = true;
        document.getElementById('scrollWarning').classList.add('hidden');
        toggleTermsButton();
    }
});

// Toggle Terms Button
function toggleTermsButton() {
    const termsCheckbox = document.getElementById('agreeTerms');
    const termsNextBtn = document.getElementById('termsNextBtn');
    
    if (termsCheckbox.checked && hasScrolledTerms) {
        termsNextBtn.disabled = false;
        termsNextBtn.classList.remove('opacity-50', 'cursor-not-allowed');
    } else {
        termsNextBtn.disabled = true;
        termsNextBtn.classList.add('opacity-50', 'cursor-not-allowed');
    }
}

// Update Step Display
function updateStepDisplay() {
    // Update progress indicators
    const stepMap = {
        1: 1,      // Asset selection
        1.5: 1,    // Details form
        2: 2,      // Terms
        3: 3       // Preview
    };
    
    const displayStep = stepMap[currentStep] || currentStep;
    
    for (let i = 1; i <= 3; i++) {
        const indicator = document.getElementById(`step${i}Indicator`);
        if (i < displayStep) {
            indicator.className = 'w-8 h-8 rounded-full bg-green-600 text-white flex items-center justify-center font-bold';
            indicator.innerHTML = '<i class="fa-solid fa-check"></i>';
            if (i < 3) document.getElementById(`progress${i}`).style.width = '100%';
        } else if (i === displayStep) {
            indicator.className = 'w-8 h-8 rounded-full bg-[#1E3A8A] text-white flex items-center justify-center font-bold';
            indicator.textContent = i;
            if (i > 1) document.getElementById(`progress${i-1}`).style.width = '100%';
        } else {
            indicator.className = 'w-8 h-8 rounded-full bg-gray-300 text-gray-600 flex items-center justify-center font-bold';
            indicator.textContent = i;
            if (i < 3) document.getElementById(`progress${i}`).style.width = '0%';
        }
    }
    
    // Update footer step indicator
    const stepTexts = {
        1: 'Step 1 of 4: Select Asset',
        1.5: 'Step 2 of 4: Fill Details',
        2: 'Step 3 of 4: Terms & Conditions',
        3: 'Step 4 of 4: Preview & Submit'
    };
    document.getElementById('stepIndicatorText').textContent = stepTexts[currentStep] || 'Step 1 of 4';
    
    // Update buttons
    document.getElementById('submitBorrowBtn').classList.toggle('hidden', currentStep !== 3);
}

// Populate Preview
function populatePreview() {
    const now = new Date();
    document.getElementById('previewDate').textContent = now.toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
    document.getElementById('previewRefNo').textContent = 'BR-' + now.getTime();
    document.getElementById('previewAssetTag').textContent = borrowingData.asset_tag;
    document.getElementById('previewAssetName').textContent = borrowingData.asset_name;
    document.getElementById('previewAssetType').textContent = borrowingData.asset_type;
    document.getElementById('previewBrandModel').textContent = borrowingData.brand ? `${borrowingData.brand} ${borrowingData.model || ''}` : 'N/A';
    document.getElementById('previewBorrowDate').textContent = new Date(borrowingData.borrow_date).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
    document.getElementById('previewReturnDate').textContent = new Date(borrowingData.return_date).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
    document.getElementById('previewPurpose').textContent = borrowingData.purpose;
}

// Submit Borrowing
function submitBorrowing() {
    // Check if user has e-signature
    const signatureImg = document.getElementById('borrowerSignature');
    if (!signatureImg) {
        showNotification('You need to upload your e-signature before submitting a borrowing request. Please go to the E-Signature page.', 'error', 6000);
        return;
    }
    
    showConfirmModal({
        title: 'Submit Borrowing Request',
        message: 'Are you sure you want to submit this borrowing request?',
        confirmText: 'Submit',
        cancelText: 'Cancel',
        confirmColor: 'bg-blue-600 hover:bg-blue-700',
        type: 'info'
    }).then((confirmed) => {
        if (!confirmed) return;
        
        submitBorrowingRequest();
    });
}

function submitBorrowingRequest() {
    // Create form and submit
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '../../controller/submit_borrowing.php';
    
    const fields = {
        asset_id: borrowingData.asset_id,
        borrowed_date: borrowingData.borrow_date,
        expected_return_date: borrowingData.return_date,
        purpose: borrowingData.purpose,
        agreed_to_terms: '1'
    };
    
    for (const [key, value] of Object.entries(fields)) {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = key;
        input.value = value;
        form.appendChild(input);
    }
    
    document.body.appendChild(form);
    form.submit();
}

// Print Document
function printDocument() {
    const printContent = document.getElementById('printableDocument').innerHTML;
    const originalContent = document.body.innerHTML;
    
    document.body.innerHTML = printContent;
    window.print();
    document.body.innerHTML = originalContent;
    location.reload(); // Reload to restore event listeners
}

// Set min date for borrow date on load
document.addEventListener('DOMContentLoaded', function() {
    const borrowDateInput = document.getElementById('borrowDate');
    const returnDateInput = document.getElementById('returnDate');
    
    if (borrowDateInput) {
        borrowDateInput.addEventListener('change', function() {
            returnDateInput.min = this.value;
        });
    }
});

document.addEventListener('DOMContentLoaded', function(){
  const form = document.getElementById('issueForm');
  if (!form) return;

  // Safe notification wrapper that works even before notifications.js loads
  function safeShowNotification(message, type = 'success', duration = 4000) {
    // Try to use the global showNotification if available
    if (typeof window.showNotification === 'function') {
      window.showNotification(message, type, duration);
      return;
    }
    
    // Fallback: create inline notification
    const existing = document.getElementById('inline-notification-toast');
    if (existing) existing.remove();

    const notification = document.createElement('div');
    notification.id = 'inline-notification-toast';
    notification.className = 'fixed top-6 right-6 z-[9999] transform transition-all duration-300 ease-in-out';
    
    let iconClass, bgColor, borderColor, textColor;
    switch(type) {
      case 'success':
        iconClass = 'fa-check-circle';
        bgColor = 'bg-green-50';
        borderColor = 'border-green-400';
        textColor = 'text-green-800';
        break;
      case 'error':
        iconClass = 'fa-exclamation-circle';
        bgColor = 'bg-red-50';
        borderColor = 'border-red-400';
        textColor = 'text-red-800';
        break;
      case 'warning':
        iconClass = 'fa-exclamation-triangle';
        bgColor = 'bg-yellow-50';
        borderColor = 'border-yellow-400';
        textColor = 'text-yellow-800';
        break;
      default:
        iconClass = 'fa-info-circle';
        bgColor = 'bg-blue-50';
        borderColor = 'border-blue-400';
        textColor = 'text-blue-800';
    }

    notification.innerHTML = `
      <div class="${bgColor} ${textColor} border-l-4 ${borderColor} px-6 py-4 rounded-lg shadow-lg max-w-md flex items-start gap-3" style="animation: slideIn 0.3s ease-out;">
        <i class="fas ${iconClass} text-xl mt-0.5"></i>
        <div class="flex-1">
          <p class="font-medium text-sm leading-relaxed">${message}</p>
        </div>
        <button onclick="this.closest('#inline-notification-toast').remove()" class="text-current opacity-50 hover:opacity-100 transition-opacity">
          <i class="fas fa-times"></i>
        </button>
      </div>
    `;

    document.body.appendChild(notification);

    setTimeout(() => {
      notification.style.opacity = '0';
      notification.style.transform = 'translateX(100%)';
      setTimeout(() => notification.remove(), 300);
    }, duration);
  }

  form.addEventListener('submit', function(e){
    e.preventDefault();
    const submitBtn = form.querySelector('#submitBtn');
    const loadingModal = document.getElementById('loadingModal');
    
    if (submitBtn) submitBtn.disabled = true;
    
    // Show loading modal for at least 2 seconds
    if (loadingModal) loadingModal.classList.remove('hidden');
    
    const startTime = Date.now();

    const fd = new FormData(form);
    fetch(form.action, {
      method: 'POST',
      body: fd,
      credentials: 'same-origin',
      headers: { 'Accept': 'application/json' }
    })
    .then(async res => {
      const text = await res.text();
      try {
        return JSON.parse(text);
      } catch (err) {
        console.error('Server response:', text);
        throw new Error('Invalid server response: ' + text.substring(0, 100));
      }
    })
    .then(json => {
      // Ensure loading modal shows for at least 2 seconds
      const elapsedTime = Date.now() - startTime;
      const remainingTime = Math.max(0, 2000 - elapsedTime);
      
      setTimeout(() => {
        // Hide loading modal
        if (loadingModal) loadingModal.classList.add('hidden');
        
        if (json.success) {
          safeShowNotification(json.message || 'Issue successfully submitted!', 'success');
          form.reset();
          // close modal if open
          const modal = document.getElementById('issueModal');
          if (modal) modal.classList.add('hidden');
        } else {
          safeShowNotification(json.message || 'Failed to submit. Please try again.', 'error');
        }
      }, remainingTime);
    })
    .catch(err => {
      // Ensure loading modal shows for at least 2 seconds
      const elapsedTime = Date.now() - startTime;
      const remainingTime = Math.max(0, 2000 - elapsedTime);
      
      setTimeout(() => {
        // Hide loading modal
        if (loadingModal) loadingModal.classList.add('hidden');
        
        console.error('Submit error:', err);
        safeShowNotification('Failed to submit. Please try again.', 'error');
      }, remainingTime);
    })
    .finally(() => {
      // Re-enable button after loading is done
      setTimeout(() => {
        if (submitBtn) submitBtn.disabled = false;
      }, Math.max(0, 2000 - (Date.now() - startTime)));
    });
  });
});

// Check URL parameters to auto-open modals
document.addEventListener('DOMContentLoaded', function() {
  const urlParams = new URLSearchParams(window.location.search);
  const action = urlParams.get('action');
  
  if (action === 'issue') {
    // Show issue selection modal or open hardware issue by default
    handleIssueClick('hardware');
  } else if (action === 'borrow') {
    // Open borrowing modal
    openBorrowingModal();
  }
});

// Show confirmation modal
function showConfirmModal(options) {
    const {
        title = 'Confirm Action',
        message = 'Are you sure?',
        confirmText = 'Confirm',
        cancelText = 'Cancel',
        confirmColor = 'bg-blue-600 hover:bg-blue-700',
        type = 'info'
    } = options;

    // Create modal HTML
    const modalHTML = `
        <div id="confirmModal" class="fixed inset-0 z-[70] flex items-center justify-center">
            <div class="absolute inset-0 bg-black opacity-50" onclick="closeConfirmModal()"></div>
            <div class="bg-white rounded-lg shadow-xl p-6 z-10 max-w-md w-full mx-4">
                <div class="flex items-center mb-4">
                    <div class="flex-shrink-0">
                        ${type === 'warning' ? '<i class="fa-solid fa-exclamation-triangle text-yellow-500 text-xl"></i>' :
                          type === 'error' ? '<i class="fa-solid fa-times-circle text-red-500 text-xl"></i>' :
                          '<i class="fa-solid fa-info-circle text-blue-500 text-xl"></i>'}
                    </div>
                    <div class="ml-3">
                        <h3 class="text-lg font-semibold text-gray-900">${title}</h3>
                    </div>
                </div>
                <div class="mb-6">
                    <p class="text-gray-700">${message}</p>
                </div>
                <div class="flex justify-end gap-3">
                    <button onclick="closeConfirmModal()" class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-4 py-2 rounded">
                        ${cancelText}
                    </button>
                    <button onclick="confirmAction()" class="${confirmColor} text-white px-4 py-2 rounded">
                        ${confirmText}
                    </button>
                </div>
            </div>
        </div>
    `;

    // Add modal to body
    document.body.insertAdjacentHTML('beforeend', modalHTML);

    // Store the resolve function for the promise
    let resolvePromise;
    const promise = new Promise(resolve => {
        resolvePromise = resolve;
    });

    // Define global functions for the modal buttons
    window.closeConfirmModal = function() {
        const modal = document.getElementById('confirmModal');
        if (modal) {
            modal.remove();
        }
        resolvePromise(false);
    };

    window.confirmAction = function() {
        const modal = document.getElementById('confirmModal');
        if (modal) {
            modal.remove();
        }
        resolvePromise(true);
    };

    return promise;
}
</script>

<?php $conn->close(); ?>

<?php include '../components/layout_footer.php'; ?>