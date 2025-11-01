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
        
        <?php if (isset($_SESSION['error_message'])): ?>
<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
    <strong>Error:</strong> <?php echo htmlspecialchars($_SESSION['error_message']); ?>
</div>
<?php unset($_SESSION['error_message']); ?>
<?php endif; ?>

<?php if (isset($_SESSION['success_message'])): ?>
<div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
    <strong>Success:</strong> <?php echo htmlspecialchars($_SESSION['success_message']); ?>
</div>
<?php unset($_SESSION['success_message']); ?>
<?php endif; ?>
        <!-- Welcome Section -->
        <div class=" p-6 mb-8">
            <h2 class="text-2xl text-center font-bold text-gray-800 mb-2">Hi <?php echo htmlspecialchars($_SESSION['full_name']); ?>, what do you need help with?</h2>
        </div>

        <!-- Help Options -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-2 gap-6 mb-8">
            <!-- Hardware Issue -->
            <div onclick="handleIssueClick('hardware')" class="bg-white rounded-lg shadow-md p-6 cursor-pointer hover:shadow-xl transition-all transform hover:-translate-y-1">
                <div class="flex gap-6">
                    <div class="bg-blue-100 p-4 rounded-xl group-hover:bg-blue-200 transition-colors flex items-center justify-center w-16 h-16 flex-shrink-0">
                        <i class="fa-solid fa-desktop text-blue-600 text-3xl"></i>
                    </div>
                    <div class="flex-1">
                        <h3 class="text-xl font-semibold text-gray-800 mb-2">Hardware Issue</h3>
                        <p class="text-gray-600">Computer, printer, or equipment problems that need technical assistance</p>
                    </div>
                </div>
            </div>

            <!-- Software Issue -->
            <div onclick="handleIssueClick('software')" class="bg-white rounded-lg shadow-md p-6 cursor-pointer hover:shadow-xl transition-all transform hover:-translate-y-1">
                <div class="flex gap-6">
                    <div class="bg-green-100 p-4 rounded-xl group-hover:bg-green-200 transition-colors flex items-center justify-center w-16 h-16 flex-shrink-0">
                        <i class="fa-solid fa-mobile-screen-button text-green-600 text-3xl"></i>
                    </div>
                    <div class="flex-1">
                        <h3 class="text-xl font-semibold text-gray-800 mb-2">Software Issue</h3>
                        <p class="text-gray-600">Application crashes, system errors, or software installation problems</p>
                    </div>
                </div>
            </div>

            <!-- Network Issue -->
            <div onclick="handleIssueClick('network')" class="bg-white rounded-lg shadow-md p-6 cursor-pointer hover:shadow-xl transition-all transform hover:-translate-y-1">
                <div class="flex gap-6">
                    <div class="bg-purple-100 p-4 rounded-xl group-hover:bg-purple-200 transition-colors flex items-center justify-center w-16 h-16 flex-shrink-0">
                        <i class="fa-solid fa-wifi text-purple-600 text-3xl"></i>
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
                    <div class="bg-yellow-100 p-4 rounded-xl group-hover:bg-yellow-200 transition-colors flex items-center justify-center w-16 h-16 flex-shrink-0">
                        <i class="fa-solid fa-box text-yellow-600 text-3xl"></i>
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
                    <div class="bg-indigo-100 p-4 rounded-xl group-hover:bg-indigo-200 transition-colors flex items-center justify-center w-16 h-16 flex-shrink-0">
                        <i class="fa-solid fa-building text-indigo-600 text-3xl"></i>
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
                    <div class="bg-gray-100 p-4 rounded-xl group-hover:bg-gray-200 transition-colors flex items-center justify-center w-16 h-16 flex-shrink-0">
                        <i class="fa-solid fa-circle-question text-gray-600 text-3xl"></i>
                    </div>
                    <div class="flex-1">
                        <h3 class="text-xl font-semibold text-gray-800 mb-2">Other</h3>
                        <p class="text-gray-600">General questions, account issues, or assistance with university services</p>
                    </div>
                </div>
            </div>
        </div>

     
       
        </main>

<!-- Single Dynamic Issue Modal -->
<div id="issueModal" class="hidden fixed inset-0 z-50 flex items-center justify-center">
    <div class="absolute inset-0 bg-black opacity-50" onclick="closeIssueModal()"></div>
    <div class="bg-white rounded-lg shadow-lg w-full max-w-xl z-10 p-6 mx-4">
        <div class="flex justify-between items-center mb-4">
            <h3 id="modalTitle" class="text-lg font-semibold">Submit Issue</h3>
            <button type="button" onclick="closeIssueModal()" class="text-gray-600 hover:text-gray-800 text-2xl" aria-label="Close">&times;</button>
        </div>

        <form id="issueForm" class="space-y-4" method="post">
            
            <div>
                <label class="block text-sm font-medium text-gray-700">Room: *</label>
                <select id="room" name="room" class="mt-1 block w-full border rounded px-3 py-2" required>
                    <option value="" disabled selected>Select room</option>
                    <option value="IK501">IK501</option>
                    <option value="IK502">IK502</option>
                    <option value="IK503">IK503</option>
                    <option value="IK504">IK504</option>
                    <option value="IK505">IK505</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Terminal No: *</label>
                <select id="terminal" name="terminal" class="mt-1 block w-full border rounded px-3 py-2" required>
                    <option value="" disabled selected>Select terminal</option>
                    <?php for ($i = 1; $i <= 30; $i++): ?>
                        <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                    <?php endfor; ?>
                </select>
            </div>

            <!-- Hardware Component Field (only for Hardware issues) -->
            <div id="hardwareComponentField" class="hidden">
                <label class="block text-sm font-medium text-gray-700">Hardware Component: *</label>
                <select name="hardware_component" id="hardwareComponent" class="mt-1 block w-full border rounded px-3 py-2">
                    <option value="">Select component</option>
                    <option value="Keyboard">Keyboard</option>
                    <option value="Mouse">Mouse</option>
                    <option value="Monitor">Monitor</option>
                    <option value="CPU/System Unit">CPU/System Unit</option>
                    <option value="Others">Others</option>
                </select>
            </div>

            <!-- Hardware Component Others Field (only when "Others" is selected) -->
            <div id="hardwareOthersField" class="hidden">
                <label class="block text-sm font-medium text-gray-700">Specify Hardware Component: *</label>
                <input name="hardware_component_other" id="hardwareComponentOther" type="text" class="mt-1 block w-full border rounded px-3 py-2" placeholder="e.g., Headset, Webcam, Printer">
            </div>

            <!-- Software Name Field (only for Software issues) -->
            <div id="softwareNameField" class="hidden">
                <label class="block text-sm font-medium text-gray-700">Software Name: *</label>
                <input name="software_name" type="text" class="mt-1 block w-full border rounded px-3 py-2" placeholder="e.g., Microsoft Office, Adobe Photoshop">
            </div>

            <!-- Network Issue Type Field (only for Network issues) -->
            <div id="networkTypeField" class="hidden">
                <label class="block text-sm font-medium text-gray-700">Network Issue Type:</label>
                <select name="network_issue_type" id="networkIssueType" class="mt-1 block w-full border rounded px-3 py-2">
                    <option value="">Select issue type</option>
                    <option value="No Connection">No Connection</option>
                    <option value="Slow Internet">Slow Internet</option>
                    <option value="WiFi Problem">WiFi Problem</option>
                    <option value="Others">Others</option>
                </select>
            </div>

            <!-- Network Issue Others Field (only when "Others" is selected) -->
            <div id="networkOthersField" class="hidden">
                <label class="block text-sm font-medium text-gray-700">Specify Network Issue: *</label>
                <input name="network_issue_type_other" id="networkIssueTypeOther" type="text" class="mt-1 block w-full border rounded px-3 py-2" placeholder="e.g., Port blocked, DNS issue, VPN problem">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Issue Title: *</label>
                <input name="title" type="text" class="mt-1 block w-full border rounded px-3 py-2" placeholder="Brief description of the issue" required>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Description:</label>
                <textarea name="description" rows="4" class="mt-1 block w-full border rounded px-3 py-2" placeholder="Provide more details about the issue..."></textarea>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Priority: *</label>
                <select name="priority" class="mt-1 block w-full border rounded px-3 py-2" required>
                    <option value="Low">Low</option>
                    <option value="Medium" selected>Medium</option>
                    <option value="High">High</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Selection Preview:</label>
                <input id="selectionPreview" type="text" class="mt-1 block w-full border rounded px-3 py-2 bg-gray-100" disabled value="">
            </div>

            <div class="flex justify-end gap-2">
                <button type="button" onclick="closeIssueModal()" class="bg-gray-200 hover:bg-gray-300 px-4 py-2 rounded">Cancel</button>
                <button type="submit" id="submitBtn" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded">Submit</button>
            </div>
        </form>
    </div>
</div>

<script>
// Update the handleIssueClick function
function handleIssueClick(issueType) {
    const modal = document.getElementById('issueModal');
    const modalTitle = document.getElementById('modalTitle');
    const issueForm = document.getElementById('issueForm');
    const submitBtn = document.getElementById('submitBtn');
    const hardwareComponentField = document.getElementById('hardwareComponentField');
    const hardwareOthersField = document.getElementById('hardwareOthersField');
    const softwareNameField = document.getElementById('softwareNameField');
    const networkTypeField = document.getElementById('networkTypeField');
    const networkOthersField = document.getElementById('networkOthersField');
    const hardwareComponentInput = document.getElementById('hardwareComponent');
    const hardwareComponentOther = document.getElementById('hardwareComponentOther');
    const softwareNameInput = document.querySelector('input[name="software_name"]');
    const networkIssueTypeOther = document.getElementById('networkIssueTypeOther');
    
    // Reset form
    issueForm.reset();
    hardwareComponentField.classList.add('hidden');
    hardwareOthersField.classList.add('hidden');
    softwareNameField.classList.add('hidden');
    networkTypeField.classList.add('hidden');
    networkOthersField.classList.add('hidden');
    hardwareComponentInput.removeAttribute('required');
    hardwareComponentOther.removeAttribute('required');
    softwareNameInput.removeAttribute('required');
    networkIssueTypeOther.removeAttribute('required');
    
    switch(issueType) {
        case 'hardware':
            modalTitle.textContent = 'Submit Hardware Issue';
            modalTitle.className = 'text-lg font-semibold text-blue-700';
            issueForm.action = '../../controller/submit_hardware.php';
            submitBtn.className = 'bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded';
            submitBtn.textContent = 'Submit';
            hardwareComponentField.classList.remove('hidden');
            hardwareComponentInput.setAttribute('required', 'required');
            break;
            
        case 'software':
            modalTitle.textContent = 'Submit Software Issue';
            modalTitle.className = 'text-lg font-semibold text-green-700';
            issueForm.action = '../../controller/submit_software.php';
            submitBtn.className = 'bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded';
            submitBtn.textContent = 'Submit';
            softwareNameField.classList.remove('hidden');
            softwareNameInput.setAttribute('required', 'required');
            break;
            
        case 'network':
            modalTitle.textContent = 'Submit Network Issue';
            modalTitle.className = 'text-lg font-semibold text-purple-700';
            issueForm.action = '../../controller/submit_network.php';
            submitBtn.className = 'bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded';
            submitBtn.textContent = 'Submit';
            networkTypeField.classList.remove('hidden');
            break;
            
        case 'borrow':
            alert('Borrow Equipment feature coming soon!');
            return;
        case 'laboratory':
            alert('Laboratory Concern feature coming soon!');
            return;
        case 'other':
            alert('Other concerns feature coming soon!');
            return;
        default:
            alert('Please select a valid option.');
            return;
    }
    
    // Show modal
    modal.classList.remove('hidden');
}

function closeIssueModal() {
    const modal = document.getElementById('issueModal');
    const issueForm = document.getElementById('issueForm');
    const hardwareComponentField = document.getElementById('hardwareComponentField');
    const hardwareOthersField = document.getElementById('hardwareOthersField');
    const softwareNameField = document.getElementById('softwareNameField');
    const networkTypeField = document.getElementById('networkTypeField');
    const networkOthersField = document.getElementById('networkOthersField');
    const hardwareComponentInput = document.getElementById('hardwareComponent');
    const hardwareComponentOther = document.getElementById('hardwareComponentOther');
    const softwareNameInput = document.querySelector('input[name="software_name"]');
    const networkIssueTypeOther = document.getElementById('networkIssueTypeOther');
    
    modal.classList.add('hidden');
    issueForm.reset();
    
    // Hide conditional fields
    hardwareComponentField.classList.add('hidden');
    hardwareOthersField.classList.add('hidden');
    softwareNameField.classList.add('hidden');
    networkTypeField.classList.add('hidden');
    networkOthersField.classList.add('hidden');
    hardwareComponentInput.removeAttribute('required');
    hardwareComponentOther.removeAttribute('required');
    softwareNameInput.removeAttribute('required');
    networkIssueTypeOther.removeAttribute('required');
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
    
    function updatePreview() {
        const room = roomSelect.value;
        const terminal = terminalSelect.value;
        if (room && terminal) {
            preview.value = `Room: ${room}, Terminal: ${terminal}`;
        } else {
            preview.value = '';
        }
    }
    
    if (roomSelect && terminalSelect && preview) {
        roomSelect.addEventListener('change', updatePreview);
        terminalSelect.addEventListener('change', updatePreview);
    }
    
    // Toggle "Others" text field for Hardware Component
    if (hardwareComponent) {
        hardwareComponent.addEventListener('change', function() {
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
});

// Close modal when clicking outside
document.addEventListener('click', function(e) {
    const modal = document.getElementById('issueModal');
    if (e.target === modal) {
        closeIssueModal();
    }
});
</script>

<?php include '../components/layout_footer.php'; ?>