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
?>

<!-- Main Content -->
<div class="p-8">   
    <!-- Success/Error Alert Container -->
    <div id="alertContainer"></div>
    
    <h2 class="text-2xl font-semibold text-gray-800 mb-2">
    Hi <?php echo htmlspecialchars($_SESSION['name'] ?? 'Student'); ?>, what do you need help with?
    </h2>

  <p class="text-gray-500 mb-8">Choose from the available options below.</p>

<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8 items-stretch">

  <!-- Hardware Issue -->
  <div onclick="handleIssueClick('hardware')" role="button" tabindex="0"
    class="bg-white rounded-xl p-6 shadow-md cursor-pointer transition 
           border border-transparent hover:border-[#1E3A8A] hover:shadow-lg 
           transform hover:scale-[1.05] transition-all duration-300 ease-in-out">

    <div class="flex items-start gap-4">
      <div class="p-3 bg-blue-100 text-[#1E3A8A] rounded-full transition-colors duration-300">
        <i class="fa-solid fa-computer text-xl group-hover:scale-110 transition-transform duration-200"></i>
      </div>
      <div>
        <h3 class="font-semibold text-gray-800">Hardware Issue</h3>
        <p class="text-sm text-gray-500">
          Computer, printer, or equipment problems that need technical assistance.
        </p>
      </div>
    </div>
  </div>

    <!-- Software Issue -->
    <div onclick="handleIssueClick('software')" 
    class="bg-white rounded-xl p-6 shadow-md cursor-pointer transition 
           border border-transparent hover:border-green-500 hover:shadow-lg 
           transform hover:scale-[1.05] transition-all duration-300 ease-in-out">

      <div class="flex items-start gap-4">
        <div class="p-3 bg-green-100 text-green-500 rounded-full transition-colors duration-300">
          <i class="fa-solid fa-microchip text-xl group-hover:scale-110 transition-transform duration-200"></i>
        </div>
        <div>
          <h3 class="font-semibold text-gray-800">Software Issue</h3>
          <p class="text-sm text-gray-500">
            Application crashes, system errors, or software installation problems.
          </p>
        </div>
      </div>
    </div>

    <!-- Network Issue -->
    <div onclick="handleIssueClick('network')" 
    class="bg-white rounded-xl p-6 shadow-md cursor-pointer transition 
           border border-transparent hover:border-violet-500 hover:shadow-lg 
           transform hover:scale-[1.05] transition-all duration-300 ease-in-out">

      <div class="flex items-start gap-4">
        <div class="p-3 bg-violet-100 text-violet-500 rounded-full transition-colors duration-300">
          <i class="fa-solid fa-globe text-xl group-hover:scale-110 transition-transform duration-200"></i>
        </div>
        <div>
          <h3 class="font-semibold text-gray-800">Network Issue</h3>
          <p class="text-sm text-gray-500">
            Internet connectivity problems, Wi-Fi issues, or network access troubles.
          </p>
        </div>
      </div>
    </div>

    <!-- Borrow Equipment -->
    <div onclick="openBorrowingModal()" 
    class="bg-white rounded-xl p-6 shadow-md cursor-pointer transition 
           border border-transparent hover:border-yellow-500 hover:shadow-lg 
           transform hover:scale-[1.05] transition-all duration-300 ease-in-out">

      <div class="flex items-start gap-4">
        <div class="p-3 bg-yellow-100 text-yellow-500 rounded-full transition-colors duration-300">
          <i class="fa-solid fa-box text-xl group-hover:scale-110 transition-transform duration-200""></i>
        </div>
        <div>
          <h3 class="font-semibold text-gray-800">Borrow Equipment</h3>
          <p class="text-sm text-gray-500">
            Request laboratory equipment, tools, or devices for academic projects.
          </p>
        </div>
      </div>
    </div>

    <!-- Laboratory Concern -->
    <div onclick="handleIssueClick('laboratory')" 
    class="bg-white rounded-xl p-6 shadow-md cursor-pointer transition 
           border border-transparent hover:border-blue-500 hover:shadow-lg 
           transform hover:scale-[1.05] transition-all duration-300 ease-in-out">

      <div class="flex items-start gap-4">
        <div class="p-3 bg-blue-100 text-blue-500 rounded-full transition-colors duration-300">
          <i class="fa-solid fa-building text-xl group-hover:scale-110 transition-transform duration-200"></i>
        </div>
        <div>
          <h3 class="font-semibold text-gray-800">Laboratory Concern</h3>
          <p class="text-sm text-gray-500">
            Lab facility access, safety issues, or equipment availability concerns.
          </p>
        </div>
      </div>
    </div>

    <!-- Other -->
    <div onclick="handleIssueClick('hardware')" 
    class="bg-white rounded-xl p-6 shadow-md cursor-pointer transition 
           border border-transparent hover:border-gray-500 hover:shadow-lg 
           transform hover:scale-[1.05] transition-all duration-300 ease-in-out">

      <div class="flex items-start gap-4">
        <div class="p-3 bg-gray-100 text-gray-500 rounded-full transition-colors duration-300">
          <i class="fa-solid fa-flag text-xl group-hover:scale-110 transition-transform duration-200"></i>
        </div>
        <div>
          <h3 class="font-semibold text-gray-800">Other</h3>
          <p class="text-sm text-gray-500">
            General questions, account issues, or assistance with university services.
          </p>
        </div>
      </div>
    </div>

      </div>
</div>

<!-- Single Dynamic Issue Modal -->
<div id="issueModal" class="hidden fixed inset-0 z-50 flex items-center justify-center">
    <div class="absolute inset-0 bg-black opacity-50" onclick="closeIssueModal()"></div>
    <div class="bg-white rounded-lg shadow-lg w-full max-w-xl z-10 p-6 mx-4">
        <div class="flex justify-between items-center mb-4">
            <h3 id="modalTitle" class="text-lg font-semibold">Submit Issue</h3>
            <button type="button" onclick="closeIssueModal()" class="text-gray-600 hover:text-gray-800 text-2xl" aria-label="Close">&times;</button>
        </div>

        <form id="issueForm" class="space-y-4" method="post">
            <input type="hidden" name="category" id="issueCategory" value="">
            
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

            <!-- Laboratory Concern Fields (only for Laboratory issues) -->
            <div id="laboratoryFieldsContainer" class="hidden space-y-4">
                 <div>
                     <label class="block text-sm font-medium text-gray-700">Concern Type: *</label>
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
                     <label class="block text-sm font-medium text-gray-700">Specify Concern: *</label>
                     <input name="laboratory_concern_other" id="laboratoryConcernOther" type="text" class="mt-1 block w-full border rounded px-3 py-2" placeholder="Please describe your concern">
                 </div>
             </div>

            <!-- Other Concern Fields (only for Other issues) -->
            <div id="otherFieldsContainer" class="hidden space-y-4">
                 <div>
                     <label class="block text-sm font-medium text-gray-700">Concern Category: *</label>
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
                    <label class="block text-sm font-medium text-gray-700">Specify Category: *</label>
                    <input name="other_concern_other" id="otherConcernOther" type="text" class="mt-1 block w-full border rounded px-3 py-2" placeholder="Please describe the category">
                </div>
            </div>

            <div>
                <!-- Issue Title (restored). Hidden by default and made required via JS for non-laboratory categories -->
                <div id="issueTitleField" class="hidden">
                    <label class="block text-sm font-medium text-gray-700">Issue Title: *</label>
                    <input name="title" id="issueTitle" type="text" class="mt-1 block w-full border rounded px-3 py-2" placeholder="Brief description of the issue">
                </div>
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

<!-- Loading Modal -->
<div id="loadingModal" class="hidden fixed inset-0 z-[60] flex items-center justify-center">
    <div class="absolute inset-0 bg-black opacity-50"></div>
    <div class="bg-white rounded-lg shadow-lg p-8 z-10 flex flex-col items-center">
        <div class="animate-spin rounded-full h-16 w-16 border-b-4 border-blue-600 mb-4"></div>
        <p class="text-lg font-semibold text-gray-800">Submitting Issue...</p>
        <p class="text-sm text-gray-500 mt-2">Please wait</p>
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
            modalTitle.textContent = 'Submit Hardware Issue';
            modalTitle.className = 'text-lg font-semibold text-blue-700';
            issueForm.action = '../../controller/submit_issue.php';
            submitBtn.className = 'bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded';
            submitBtn.textContent = 'Submit';
            hardwareComponentField.classList.remove('hidden');
            hardwareComponentInput.setAttribute('required', 'required');
            // show and require title
            issueTitleField?.classList.remove('hidden');
            issueTitleInput?.setAttribute('required','required');
            document.getElementById('issueCategory').value = 'hardware';
            break;
            
        case 'software':
            modalTitle.textContent = 'Submit Software Issue';
            modalTitle.className = 'text-lg font-semibold text-green-700';
            issueForm.action = '../../controller/submit_issue.php';
            submitBtn.className = 'bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded';
            submitBtn.textContent = 'Submit';
            softwareNameField.classList.remove('hidden');
            softwareNameInput.setAttribute('required', 'required');
            // show and require title
            issueTitleField?.classList.remove('hidden');
            issueTitleInput?.setAttribute('required','required');
            document.getElementById('issueCategory').value = 'software';
            break;
            
        case 'network':
            modalTitle.textContent = 'Submit Network Issue';
            modalTitle.className = 'text-lg font-semibold text-purple-700';
            issueForm.action = '../../controller/submit_issue.php';
            submitBtn.className = 'bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded';
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
            modalTitle.textContent = 'Submit Laboratory Concern';
            modalTitle.className = 'text-lg font-semibold text-indigo-700';
            issueForm.action = '../../controller/submit_issue.php';
            submitBtn.className = 'bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded';
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
            modalTitle.textContent = 'Submit Other Concern';
            modalTitle.className = 'text-lg font-semibold text-gray-700';
            issueForm.action = '../../controller/submit_issue.php';
            submitBtn.className = 'bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded';
            submitBtn.textContent = 'Submit';
            document.getElementById('issueCategory').value = 'other';
            document.getElementById('otherFieldsContainer').classList.remove('hidden');
            // make other concern category required when Other selected
            otherConcernCategory?.setAttribute('required','required');
            // Do not show or require the Issue Title for Other concerns
            break;
            
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
</script>

<!-- Borrowing Equipment Modal -->
<div id="borrowingModal" class="fixed inset-0 bg-gray-600 bg-opacity-75 hidden z-50 overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen px-4 py-6">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-5xl max-h-[90vh] flex flex-col">
            <!-- Modal Header -->
            <div class="bg-yellow-600 text-white px-6 py-4 rounded-t-lg flex justify-between items-center flex-shrink-0">
                <h3 class="text-xl font-bold">
                    <i class="fa-solid fa-box mr-2"></i>
                    <span id="borrowingModalTitle">Borrow Equipment</span>
                </h3>
                <button onclick="closeBorrowingModal()" class="text-white hover:text-gray-200">
                    <i class="fa-solid fa-xmark text-2xl mb-2 block"></i>
                </button>
            </div>

            <!-- Progress Steps -->
            <div class="px-6 py-4 border-b flex-shrink-0 bg-yellow-50">
                <div class="flex justify-between items-center">
                    <div class="flex-1 flex items-center">
                        <div id="step1Indicator" class="w-8 h-8 rounded-full bg-yellow-600 text-white flex items-center justify-center font-bold">1</div>
                        <div class="flex-1 h-1 bg-gray-300 mx-2">
                            <div id="progress1" class="h-full bg-yellow-600" style="width: 0%"></div>
                        </div>
                    </div>
                    <div class="flex-1 flex items-center">
                        <div id="step2Indicator" class="w-8 h-8 rounded-full bg-gray-300 text-gray-600 flex items-center justify-center font-bold">2</div>
                        <div class="flex-1 h-1 bg-gray-300 mx-2">
                            <div id="progress2" class="h-full bg-yellow-600" style="width: 0%"></div>
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
            <div class="flex-1 overflow-y-auto px-6 py-4 bg-yellow-50/30" style="max-height: calc(90vh - 200px);">
                <!-- Step 1: Asset Selection -->
                <div id="step1" class="step-content">
                    <h4 class="text-lg font-semibold mb-4">Select Equipment to Borrow</h4>
                    <div id="assetsLoading" class="text-center py-8">
                        <i class="fa-solid fa-spinner fa-spin text-4xl text-yellow-600"></i>
                        <p class="mt-2 text-gray-600">Loading available assets...</p>
                    </div>
                    <div id="assetsTableContainer" class="hidden">
                        <div class="mb-3 p-2 bg-yellow-50 rounded text-sm text-yellow-800 border border-yellow-200">
                            <i class="fa-solid fa-info-circle mr-2"></i>
                            Click on any row to select an asset
                        </div>
                        <div class="overflow-x-auto border rounded-lg">
                            <table id="assetsTable" class="display w-full text-sm">
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
                        <button type="button" id="step1NextBtn" onclick="proceedToDetails()" class="bg-yellow-600 hover:bg-yellow-700 text-white px-6 py-2 rounded-lg font-medium shadow-lg hidden">
                            Next: Fill Details<i class="fa-solid fa-arrow-right ml-2"></i>
                        </button>
                    </div>
                </div>

                <!-- Step 1b: Borrowing Details Form (sub-step) -->
                <div id="step1b" class="step-content hidden">
                    <h4 class="text-lg font-semibold mb-4">Borrowing Details</h4>
                    
                    <div class="p-4 bg-yellow-50 rounded-lg mb-4 border border-yellow-200">
                        <h5 class="font-semibold mb-2 text-yellow-900">Selected Asset:</h5>
                        <div id="selectedAssetInfo" class="text-sm text-yellow-800"></div>
                    </div>
                    
                    <div class="space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Borrow Date: *</label>
                                <input type="date" id="borrowDate" class="w-full border rounded px-3 py-2" required>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Expected Return Date: *</label>
                                <input type="date" id="returnDate" class="w-full border rounded px-3 py-2" required>
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Purpose: *</label>
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
                        <button type="button" onclick="proceedToTerms()" class="bg-yellow-600 hover:bg-yellow-700 text-white px-6 py-2 rounded-lg font-medium shadow-lg">
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
                            
                            <div class="mt-6 p-3 bg-yellow-50 border border-yellow-300 rounded">
                                <p class="text-sm text-yellow-800 font-medium">
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
                        <button type="button" id="termsNextBtn" onclick="proceedToPreview()" class="bg-yellow-600 hover:bg-yellow-700 text-white px-6 py-2 rounded-lg font-medium shadow-lg opacity-50 cursor-not-allowed" disabled>
                            Next: Preview & Submit<i class="fa-solid fa-arrow-right ml-2"></i>
                        </button>
                    </div>
                </div>

                <!-- Step 3: Preview & Submit -->
                <div id="step3" class="step-content hidden">
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
                    
                    <div class="mt-6 p-4 bg-yellow-50 rounded border border-yellow-300 mb-4">
                        <p class="text-sm text-yellow-800">
                            <i class="fa-solid fa-info-circle mr-2"></i>
                            This is a preview of your borrowing agreement. You can print this document after submission for your records.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="px-6 py-4 bg-yellow-50 rounded-b-lg flex justify-between items-center flex-shrink-0 border-t border-yellow-200">
                <div class="text-sm text-gray-600 font-medium" id="stepIndicatorText">Step 1 of 4</div>
                <div class="flex gap-3">
                    <button type="button" id="submitBorrowBtn" onclick="submitBorrowing()" class="bg-yellow-500 hover:bg-yellow-600 text-white px-6 py-2.5 rounded-lg font-medium shadow-lg hidden transition-all">
                        <i class="fa-solid fa-check mr-2"></i>Submit Request
                    </button>
                    <button type="button" id="printBtn" onclick="printDocument()" class="bg-yellow-600 hover:bg-yellow-700 text-white px-5 py-2.5 rounded-lg font-medium shadow-lg hidden transition-all">
                        <i class="fa-solid fa-print mr-2"></i>Print
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
    $('#assetsTable tbody tr').removeClass('bg-yellow-100 border-l-4 border-yellow-600');
    
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
        const response = await fetch('../../controller/get_borrowable_assets.php');
        const data = await response.json();
        
        if (data.success && data.assets.length > 0) {
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
        pageLength: 10,
        lengthMenu: [[5, 10, 25, 50], [5, 10, 25, 50]],
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
        dom: '<"flex flex-col sm:flex-row justify-between items-center mb-4"lf>rtip',
        scrollX: false,
        autoWidth: true,
        createdRow: function(row, data, dataIndex) {
            // Store asset ID in row
            $(row).attr('data-asset-id', tableData[dataIndex].id);
            // Make row clickable
            $(row).addClass('cursor-pointer hover:bg-yellow-50 transition-colors');
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
    $('#assetsTable tbody tr').removeClass('bg-yellow-100 border-l-4 border-yellow-600');
    $('#assetsTable tbody tr[data-asset-id="' + assetId + '"]').addClass('bg-yellow-100 border-l-4 border-yellow-600');
}

// Proceed to Details Form
function proceedToDetails() {
    if (!selectedAsset) {
        alert('Please select an asset first.');
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
        alert('Please fill in all borrowing details.');
        return;
    }
    
    if (new Date(returnDate) <= new Date(borrowDate)) {
        alert('Return date must be after borrow date.');
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
        alert('Please agree to the terms and conditions.');
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
            indicator.className = 'w-8 h-8 rounded-full bg-yellow-600 text-white flex items-center justify-center font-bold';
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
    document.getElementById('printBtn').classList.toggle('hidden', currentStep !== 3);
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
        alert('You need to upload your e-signature before submitting a borrowing request. Please go to the E-Signature page.');
        return;
    }
    
    if (!confirm('Are you sure you want to submit this borrowing request?')) {
        return;
    }
    
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

  function showTopAlert(type, msg) {
    // remove existing
    const existing = document.getElementById('topAjaxAlert');
    if (existing) existing.remove();

    const div = document.createElement('div');
    div.id = 'topAjaxAlert';
    
    if (type === 'success') {
      div.className = 'bg-green-100 border border-green-400 text-green-700 px-6 py-4 rounded-lg mb-6';
      div.innerHTML = '<strong>Success!</strong> ' + msg;
    } else {
      div.className = 'bg-red-100 border border-red-400 text-red-700 px-6 py-4 rounded-lg mb-6';
      div.innerHTML = '<strong>Error!</strong> ' + msg;
    }
    
    // Insert into alert container
    const alertContainer = document.getElementById('alertContainer');
    if (alertContainer) {
      alertContainer.appendChild(div);
    }
    
    // Scroll to top to show the alert
    window.scrollTo({ top: 0, behavior: 'smooth' });
    
    // auto-remove after 5s
    setTimeout(() => div.remove(), 3000);
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
        throw new Error('Invalid server response');
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
          showTopAlert('success', json.message || 'Issue is successfuly submitted!');
          form.reset();
          // close modal if open
          const modal = document.getElementById('issueModal');
          if (modal) modal.classList.add('hidden');
        } else {
          showTopAlert('error', json.message || 'Failed to submit. Please try again.');
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
        showTopAlert('error', 'Failed to submit. Please try again.');
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
</script>

<?php include '../components/layout_footer.php'; ?>