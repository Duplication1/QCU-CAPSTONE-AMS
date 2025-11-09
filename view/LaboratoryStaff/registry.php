<?php
// Prevent page caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

session_start();

// Check if user is logged in and has laboratory staff role
if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true || $_SESSION['role'] !== 'Laboratory Staff') {
    header("Location: ../login.php");
    exit();
}

require_once '../../config/config.php';
include '../components/layout_header.php';
?>

        <!-- Main Content -->
        <main class="p-6">
            <div class="bg-white rounded-xl shadow-lg p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-2xl font-bold text-gray-800">Asset Registry</h2>
                </div>

                <div class="flex justify-center mt-8 mb-6">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 w-full max-w-1xl">
                        <div onclick="openRegisterModal()" class="bg-white rounded-xl shadow-lg p-8 hover:shadow-xl transition duration-200 cursor-pointer border border-gray-100 hover:border-green-300 group transform hover:scale-105">
                            <div class="flex gap-6">
                                <div class="bg-green-100 p-4 rounded-xl group-hover:bg-green-200 transition-colors flex items-center justify-center w-16 h-16 flex-shrink-0">
                                    <i class="fa-solid fa-box text-green-600 text-3xl"></i>
                                </div>
                                <div class="flex-1">
                                    <h3 class="text-xl font-semibold text-gray-800 mb-2">Register Asset</h3>
                                    <p class="text-gray-600">Add a new asset to the registry. Provide details like tag, name, type, and location.</p>
                                </div>
                            </div>
                        </div>

                        <div onclick="openRequestModal()" class="bg-white rounded-xl shadow-lg p-8 hover:shadow-xl transition duration-200 cursor-pointer border border-gray-100 hover:border-yellow-300 group transform hover:scale-105">
                            <div class="flex gap-6">
                                <div class="bg-yellow-100 p-4 rounded-xl group-hover:bg-yellow-200 transition-colors flex items-center justify-center w-16 h-16 flex-shrink-0">
                                    <i class="fa-solid fa-box-open text-yellow-600 text-3xl"></i>
                                </div>
                                <div class="flex-1">
                                    <h3 class="text-xl font-semibold text-gray-800 mb-2">Request Asset</h3>
                                    <p class="text-gray-600">Request an asset for borrowing. Specify dates and purpose for the request.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </main>

        <!-- Tables: Registered Assets & Requests -->
        <main class="p-6">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 max-w-7xl mx-auto">
                <div class="bg-white rounded-lg shadow p-4">
                    <h3 class="text-lg font-semibold mb-3">Registered Assets</h3>
                    <div class="overflow-x-auto">
                        <table id="registeredAssetsTable" class="min-w-full text-sm">
                            <thead>
                                <tr class="text-left text-xs text-gray-500 uppercase"><th class="px-3 py-2">Tag</th><th class="px-3 py-2">Name</th><th class="px-3 py-2">Type</th><th class="px-3 py-2">Location</th></tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow p-4">
                    <h3 class="text-lg font-semibold mb-3">Asset Requests</h3>
                    <div class="overflow-x-auto">
                        <table id="assetRequestsTable" class="min-w-full text-sm">
                            <thead>
                                <tr class="text-left text-xs text-gray-500 uppercase"><th class="px-3 py-2">Ref</th><th class="px-3 py-2">Borrow</th><th class="px-3 py-2">Return</th><th class="px-3 py-2">Purpose</th></tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>

        <!-- Register Asset Modal -->
<div id="registerModal" class="hidden fixed inset-0 z-50 flex items-center justify-center">
    <div class="absolute inset-0 bg-black opacity-50" onclick="closeRegisterModal()"></div>
    <div class="bg-white rounded-lg shadow-lg w-full max-w-md z-10 p-6 mx-4">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold">Register Asset</h3>
            <button onclick="closeRegisterModal()" class="text-gray-600 hover:text-gray-800 text-2xl">&times;</button>
        </div>
        <form id="registerForm" class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700">Asset Tag *</label>
                <input name="asset_tag" class="mt-1 block w-full border rounded px-3 py-2" required />
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Asset Name *</label>
                <input name="asset_name" class="mt-1 block w-full border rounded px-3 py-2" required />
            </div>
            <div class="grid grid-cols-2 gap-2">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Type</label>
                    <input name="asset_type" class="mt-1 block w-full border rounded px-3 py-2" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Brand/Model</label>
                    <input name="brand_model" class="mt-1 block w-full border rounded px-3 py-2" />
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Room / Location</label>
                <input name="room" class="mt-1 block w-full border rounded px-3 py-2" />
            </div>
            <div class="flex justify-end gap-2">
                <button type="button" onclick="closeRegisterModal()" class="bg-gray-200 hover:bg-gray-300 px-4 py-2 rounded">Cancel</button>
                <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded">Register</button>
            </div>
        </form>
    </div>
</div>

<!-- Request Asset Modal -->
<div id="requestModal" class="hidden fixed inset-0 z-50 flex items-center justify-center">
    <div class="absolute inset-0 bg-black opacity-50" onclick="closeRequestModal()"></div>
    <div class="bg-white rounded-lg shadow-lg w-full max-w-md z-10 p-6 mx-4">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold">Request Asset</h3>
            <button onclick="closeRequestModal()" class="text-gray-600 hover:text-gray-800 text-2xl">&times;</button>
        </div>
        <form id="requestForm" class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700">Asset Tag or Name *</label>
                <input name="asset_ref" class="mt-1 block w-full border rounded px-3 py-2" required />
            </div>
            <div class="grid grid-cols-2 gap-2">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Borrow Date *</label>
                    <input type="date" name="borrow_date" class="mt-1 block w-full border rounded px-3 py-2" required />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Return Date *</label>
                    <input type="date" name="return_date" class="mt-1 block w-full border rounded px-3 py-2" required />
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Purpose</label>
                <textarea name="purpose" rows="3" class="mt-1 block w-full border rounded px-3 py-2"></textarea>
            </div>
            <div class="flex justify-end gap-2">
                <button type="button" onclick="closeRequestModal()" class="bg-gray-200 hover:bg-gray-300 px-4 py-2 rounded">Cancel</button>
                <button type="submit" class="bg-yellow-600 hover:bg-yellow-700 text-white px-4 py-2 rounded">Request</button>
            </div>
        </form>
    </div>
</div>

<script>
// Modal controls
function openRegisterModal(){ document.getElementById('registerModal').classList.remove('hidden'); }
function closeRegisterModal(){ document.getElementById('registerModal').classList.add('hidden'); }
function openRequestModal(){ document.getElementById('requestModal').classList.remove('hidden'); }
function closeRequestModal(){ document.getElementById('requestModal').classList.add('hidden'); }

// Top alert helper (reused from StudentFaculty)
function showTopAlert(type, msg) {
    const existing = document.getElementById('topAjaxAlert'); if (existing) existing.remove();
    const div = document.createElement('div'); div.id = 'topAjaxAlert';
    if (type === 'success') { div.className = 'bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4'; div.innerHTML = '<strong>Success:</strong> ' + msg; }
    else { div.className = 'bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4'; div.innerHTML = '<strong>Error:</strong> ' + msg; }
    const main = document.querySelector('main'); if (main && main.parentNode) main.parentNode.insertBefore(div, main);
    setTimeout(()=> div.remove(), 6000);
}

// Helper to append a registered asset row to the table
function addRegisteredAssetRow(asset) {
    const tbody = document.querySelector('#registeredAssetsTable tbody');
    if (!tbody) return;
    const tr = document.createElement('tr');
    tr.className = 'border-t';
    const tag = asset.asset_tag || asset.tag || '';
    const name = asset.asset_name || asset.name || '';
    const type = asset.asset_type || asset.type || '';
    const room = asset.room || asset.location || '';
    tr.innerHTML = `<td class="px-3 py-2">${tag}</td><td class="px-3 py-2">${name}</td><td class="px-3 py-2">${type}</td><td class="px-3 py-2">${room}</td>`;
    tbody.prepend(tr);
}

// Helper to append a request row to the requests table
function addRequestRow(req) {
    const tbody = document.querySelector('#assetRequestsTable tbody');
    if (!tbody) return;
    const tr = document.createElement('tr');
    tr.className = 'border-t';
    const ref = req.asset_ref || req.ref || '';
    const borrow = req.borrow_date || req.borrow || '';
    const ret = req.return_date || req.return || '';
    const purpose = req.purpose || '';
    tr.innerHTML = `<td class="px-3 py-2">${ref}</td><td class="px-3 py-2">${borrow}</td><td class="px-3 py-2">${ret}</td><td class="px-3 py-2">${purpose}</td>`;
    tbody.prepend(tr);
}

// Try to load existing data if controller endpoints exist (non-blocking)
document.addEventListener('DOMContentLoaded', function(){
    // load registered assets
    fetch('../../controller/get_registered_assets.php').then(r => r.json()).then(j => {
        if (j && j.success && Array.isArray(j.assets)) {
            j.assets.forEach(a => addRegisteredAssetRow(a));
        }
    }).catch(()=>{});

    // load requests
    fetch('../../controller/get_asset_requests.php').then(r => r.json()).then(j => {
        if (j && j.success && Array.isArray(j.requests)) {
            j.requests.forEach(rq => addRequestRow(rq));
        }
    }).catch(()=>{});
});

// Register form submit (AJAX)
document.getElementById('registerForm').addEventListener('submit', function(e){
    e.preventDefault();
    const btn = this.querySelector('button[type=submit]'); if (btn) btn.disabled = true;
    const fd = new FormData(this);
    fetch('../../controller/register_asset.php', { method:'POST', body: fd, credentials:'same-origin' })
    .then(async r => { const text = await r.text(); try { return JSON.parse(text); } catch(err){ throw new Error('Invalid server response'); } })
    .then(json => {
        if (json.success) {
            showTopAlert('success', json.message || 'Asset registered');
            // append to table: prefer server-returned asset object, otherwise use form values
            const assetObj = json.asset || Object.fromEntries(fd.entries());
            addRegisteredAssetRow(assetObj);
            this.reset(); closeRegisterModal();
        } else showTopAlert('error', json.message || 'Failed to register');
    }).catch(err => { console.error(err); showTopAlert('error','Request failed'); })
    .finally(()=>{ if (btn) btn.disabled = false; });
});

// Request form submit (AJAX)
document.getElementById('requestForm').addEventListener('submit', function(e){
    e.preventDefault();
    const btn = this.querySelector('button[type=submit]'); if (btn) btn.disabled = true;
    const fd = new FormData(this);
    fetch('../../controller/request_asset.php', { method:'POST', body: fd, credentials:'same-origin' })
    .then(async r => { const text = await r.text(); try { return JSON.parse(text); } catch(err){ throw new Error('Invalid server response'); } })
    .then(json => {
        if (json.success) {
            showTopAlert('success', json.message || 'Request submitted');
            const reqObj = json.request || Object.fromEntries(fd.entries());
            addRequestRow(reqObj);
            this.reset(); closeRequestModal();
        } else showTopAlert('error', json.message || 'Failed to submit request');
    }).catch(err => { console.error(err); showTopAlert('error','Request failed'); })
    .finally(()=>{ if (btn) btn.disabled = false; });
});
</script>

<?php include '../components/layout_footer.php'; ?>
