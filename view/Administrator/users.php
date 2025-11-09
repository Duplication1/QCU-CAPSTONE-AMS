<?php
// Prevent page caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

session_start();

// Check if user is logged in and has administrator role
if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true || $_SESSION['role'] !== 'Administrator') {
    header("Location: ../login.php");
    exit();
}

require_once '../../config/config.php';
include '../components/layout_header.php';
?>

<?php
// AJAX endpoints (toggle_status, edit_user, delete_user)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax'])) {
    $action = $_POST['action'] ?? '';
    // require admin
    if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true || ($_SESSION['role'] ?? '') !== 'Administrator') {
        echo json_encode(['success'=>false,'message'=>'Not authorized']); exit;
    }

    if ($action === 'toggle_status') {
        $uid = intval($_POST['user_id'] ?? 0);
        if ($uid <= 0) { echo json_encode(['success'=>false,'message'=>'Invalid user']); exit; }
        $s = $conn->prepare("SELECT status FROM users WHERE id = ? LIMIT 1");
        $s->bind_param('i', $uid);
        $s->execute();
        $r = $s->get_result()->fetch_assoc();
        $s->close();
        if (!$r) { echo json_encode(['success'=>false,'message'=>'User not found']); exit; }
        $cur = $r['status'] ?? 'Active';
        $next = ($cur === 'Active') ? 'Inactive' : 'Active';
        $u = $conn->prepare("UPDATE users SET status = ?, updated_at = NOW() WHERE id = ?");
        $u->bind_param('si', $next, $uid);
        $ok = $u->execute();
        $u->close();
        if ($ok) echo json_encode(['success'=>true,'status'=>$next]); else echo json_encode(['success'=>false,'message'=>'Update failed']);
        exit;
    }

    if ($action === 'edit_user') {
        $uid = intval($_POST['user_id'] ?? 0);
        $full = trim($_POST['full_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $role = trim($_POST['role'] ?? '');
        if ($uid <= 0) { echo json_encode(['success'=>false,'message'=>'Invalid user']); exit; }
        if ($full === '' || $email === '' || $role === '') { echo json_encode(['success'=>false,'message'=>'Missing fields']); exit; }
        $u = $conn->prepare("UPDATE users SET full_name = ?, email = ?, role = ?, updated_at = NOW() WHERE id = ?");
        $u->bind_param('sssi', $full, $email, $role, $uid);
        $ok = $u->execute();
        $u->close();
        if ($ok) {
            // return success and updated user data (no session flash — client will update DOM)
            echo json_encode(['success'=>true,'user'=>['id'=>$uid,'full_name'=>$full,'email'=>$email,'role'=>$role]]);
        } else {
            echo json_encode(['success'=>false,'message'=>'Update failed']);
        }
        exit;
    }

    if ($action === 'delete_user') {
        $uid = intval($_POST['user_id'] ?? 0);
        if ($uid <= 0) { echo json_encode(['success'=>false,'message'=>'Invalid user']); exit; }
        // prevent deleting self
        if (isset($_SESSION['user_id']) && intval($_SESSION['user_id']) === $uid) { echo json_encode(['success'=>false,'message'=>'Cannot delete yourself']); exit; }
        $d = $conn->prepare("DELETE FROM users WHERE id = ?");
        $d->bind_param('i', $uid);
        $ok = $d->execute();
        $d->close();
        if ($ok) echo json_encode(['success'=>true]); else echo json_encode(['success'=>false,'message'=>'Delete failed']);
        exit;
    }
}

// Fetch all users for display
$users = [];
$q = $conn->query("SELECT id, id_number, full_name, email, role, status, created_at, last_login FROM users ORDER BY created_at DESC");
if ($q && $q->num_rows > 0) {
    while ($row = $q->fetch_assoc()) $users[] = $row;
}
// no server-side flash alerts here; client updates DOM in-place
?>

        <!-- Main Content -->
        <main class="p-6">
            <div class="bg-white rounded-xl shadow-lg p-6">
                <h2 class="text-2xl font-bold text-gray-800 mb-4">User Management</h2>

                <div class="flex items-center justify-between mb-4 gap-3">
                    <div class="flex items-center gap-2">
                        <input id="userSearch" oninput="filterUsers()" type="search" placeholder="Search users by name, email or role" class="border rounded px-3 py-2 text-sm w-72" />
                        <button onclick="filterUsers()" class="bg-blue-600 text-white px-3 py-2 rounded text-sm">Search</button>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table id="usersTable" class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Email</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Role</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Joined</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Last Active</th>
                                <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-100">
                            <?php foreach ($users as $u): ?>
                            <tr data-user='<?php echo json_encode($u, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT); ?>'>
                                <td class="px-4 py-3"><?php echo htmlspecialchars($u['full_name']); ?></td>
                                <td class="px-4 py-3"><?php echo htmlspecialchars($u['email']); ?></td>
                                <td class="px-4 py-3"><?php echo htmlspecialchars($u['role']); ?></td>
                                <td class="px-4 py-3"><span class="status-badge px-2 py-1 rounded-full text-xs <?php echo $u['status']==='Active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'; ?>"><?php echo htmlspecialchars($u['status']); ?></span></td>
                                <td class="px-4 py-3"><?php echo !empty($u['created_at']) ? date('M d, Y', strtotime($u['created_at'])) : '-'; ?></td>
                                <td class="px-4 py-3"><?php echo !empty($u['last_login']) ? date('M d, Y H:i', strtotime($u['last_login'])) : '-'; ?></td>
                                <td class="px-4 py-3 text-center">
                                    <div class="flex items-center justify-center gap-3">
                                        <button onclick="editUser(this, <?php echo (int)$u['id']; ?>)" class="text-yellow-600 hover:text-yellow-900" title="Edit"><i class="fa fa-pencil"></i></button>
                                        <button onclick="deleteUser(this, <?php echo (int)$u['id']; ?>)" class="text-red-600 hover:text-red-900" title="Delete"><i class="fa fa-trash"></i></button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>



<!-- View user modal (overlay + modal) -->
<div id="userModal" class="hidden fixed inset-0 z-40 flex items-center justify-center">
    <!-- translucent overlay so page is visible beneath -->
    <div class="absolute inset-0 bg-black bg-opacity-40 backdrop-blur-sm" style="background-color: rgba(0,0,0,0.4);" onclick="closeUserModal()"></div>
    <div class="relative bg-white rounded-lg shadow-2xl max-w-3xl w-11/12 md:w-2/3 lg:w-1/2 p-6">
        <h3 id="modalName" class="text-lg font-semibold mb-2"></h3>
        <p id="modalEmail" class="text-sm text-gray-700"></p>
        <p id="modalRole" class="text-sm text-gray-700"></p>
        <p id="modalStatus" class="text-sm text-gray-700"></p>
        <div class="mt-4 flex justify-end gap-2">
            <button onclick="closeUserModal()" class="px-3 py-1 rounded bg-gray-100">Close</button>
            <button id="modalEditBtn" onclick="editUserFromModal()" class="px-3 py-1 rounded bg-yellow-500 text-white">Edit</button>
        </div>
    </div>
</div>

<!-- Edit user modal (overlay + larger modal) -->
<div id="editUserModal" class="hidden fixed inset-0 z-50 flex items-center justify-center">
    <!-- translucent overlay to dim background but keep content visible -->
    <div class="absolute inset-0 bg-black bg-opacity-40 backdrop-blur-sm" style="background-color: rgba(0,0,0,0.4);" onclick="closeEditModal()"></div>
    <div class="relative bg-white rounded-lg shadow-2xl max-w-4xl w-11/12 md:w-3/4 lg:w-2/3 p-6">
        <h3 class="text-xl font-semibold mb-4">Edit User</h3>
        <form id="editUserForm" onsubmit="return submitEditForm(event)">
            <input type="hidden" name="user_id" id="edit_user_id" />
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm text-gray-700">Full name</label>
                    <input id="edit_full_name" name="full_name" class="w-full border rounded px-3 py-2" required />
                </div>
                <div>
                    <label class="block text-sm text-gray-700">Email</label>
                    <input id="edit_email" name="email" type="email" class="w-full border rounded px-3 py-2" required />
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm text-gray-700">Role</label>
                    <select id="edit_role" name="role" class="w-full border rounded px-3 py-2">
                        <option>Administrator</option>
                        <option>Technician</option>
                        <option>LaboratoryStaff</option>
                        <option>Student</option>
                    </select>
                </div>
            </div>
            <div class="mt-6 flex justify-end gap-3">
                <button type="button" onclick="closeEditModal()" class="px-4 py-2 rounded bg-gray-100">Cancel</button>
                <button type="submit" id="editSaveBtn" class="px-4 py-2 rounded bg-blue-600 text-white">Save changes</button>
            </div>
        </form>
    </div>
</div>

<!-- Simple Delete confirmation modal (Yes / No) -->
<div id="deleteConfirmModal" class="hidden fixed inset-0 z-50 flex items-center justify-center">
    <!-- match edit modal overlay: translucent dim with slight blur -->
    <div class="absolute inset-0 bg-black bg-opacity-40 backdrop-blur-sm" style="background-color: rgba(0,0,0,0.4);" onclick="closeDeleteModal()"></div>
        <div class="relative bg-white rounded-lg shadow-lg max-w-md w-11/12 p-6">
            <div class="flex flex-col items-center text-center">
                <!-- danger image (use project's assets image) -->
                <div class="mb-4">
                    <img src="../../assets/images/8376179.png" alt="danger" class="w-16 h-16 object-contain mx-auto" />
                </div>
                <h3 class="text-xl font-semibold mb-2">Are you sure?</h3>
                <p class="text-sm text-gray-600 mb-6">Do you really want to delete this? This process cannot be undone.</p>
                <div class="flex gap-3 flex-col sm:flex-row w-full justify-center mt-2">
                    <button id="cancelDeleteBtn" type="button" onclick="closeDeleteModal()" class="px-4 py-2 rounded bg-gray-200 text-gray-800 w-full sm:w-auto inline-block" style="min-width:120px;">No</button>
                    <button id="confirmDeleteBtn" type="button" onclick="confirmDelete()" class="px-4 py-2 rounded w-full sm:w-auto inline-block" style="min-width:120px;background-color:#ef4444;color:#ffffff;border:none;box-shadow:0 1px 2px rgba(0,0,0,0.1);">Yes</button>
                </div>
            </div>
        </div>
</div>

<?php include '../components/layout_footer.php'; ?>
<!-- Toast notification -->
<div id="globalToast" class="fixed bottom-6 right-6 z-60 hidden">
    <div id="globalToastInner" class="px-4 py-2 rounded shadow text-sm"></div>
</div>
<script>
function showToast(message, type = 'success', duration = 3000) {
    const container = document.getElementById('globalToast');
    const inner = document.getElementById('globalToastInner');
    if (!container || !inner) return;
    inner.textContent = message;
    inner.className = 'px-4 py-2 rounded shadow text-sm ' + (type === 'success' ? 'bg-green-500 text-white' : 'bg-red-500 text-white');
    container.classList.remove('hidden');
    setTimeout(() => {
        container.classList.add('hidden');
    }, duration);
}

// Show a top-of-page alert (used by Student/Faculty flows). Inserts a temporary alert above <main>
function showTopAlert(type, msg, duration = 6000) {
    // remove existing
    const existing = document.getElementById('topAjaxAlert');
    if (existing) existing.remove();

    const div = document.createElement('div');
    div.id = 'topAjaxAlert';
    if (type === 'success') {
        div.className = 'bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4';
        div.innerHTML = '<strong>Success:</strong> ' + msg;
    } else {
        div.className = 'bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4';
        div.innerHTML = '<strong>Error:</strong> ' + msg;
    }
    // insert at top of main content (before <main>)
    const main = document.querySelector('main');
    if (main && main.parentNode) main.parentNode.insertBefore(div, main);
    // auto-remove after duration
    setTimeout(() => { try { div.remove(); } catch(e){} }, duration);
}

function filterUsers() {
    const q = (document.getElementById('userSearch').value || '').toLowerCase().trim();
    const rows = document.querySelectorAll('#usersTable tbody tr');
    rows.forEach(r => {
        const user = r.dataset.user ? JSON.parse(r.dataset.user) : null;
        if (!user) { r.style.display = ''; return; }
        const hay = (user.full_name + ' ' + user.email + ' ' + (user.role||'')).toLowerCase();
        r.style.display = hay.indexOf(q) !== -1 ? '' : 'none';
    });
}

function getRowById(id) {
    const rows = document.querySelectorAll('#usersTable tbody tr');
    for (const r of rows) {
        try {
            const u = r.dataset.user ? JSON.parse(r.dataset.user) : null;
            if (u && parseInt(u.id) === parseInt(id)) return r;
        } catch(e) { continue; }
    }
    return null;
}

function showUserModal(user) {
    document.getElementById('modalName').textContent = user.full_name || '-';
    document.getElementById('modalEmail').textContent = 'Email: ' + (user.email || '-');
    document.getElementById('modalRole').textContent = 'Role: ' + (user.role || '-');
    document.getElementById('modalStatus').textContent = 'Status: ' + (user.status || '-');
    const modal = document.getElementById('userModal');
    modal.dataset.userId = user.id;
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function closeUserModal() {
    const modal = document.getElementById('userModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
    delete modal.dataset.userId;
}



function editUser(btn, userId) {
    // open edit modal and populate fields
    const tr = getRowById(userId);
    const user = tr && tr.dataset.user ? JSON.parse(tr.dataset.user) : null;
    if (!user) return alert('User not found');
    document.getElementById('edit_user_id').value = userId;
    document.getElementById('edit_full_name').value = user.full_name || '';
    document.getElementById('edit_email').value = user.email || '';
    document.getElementById('edit_role').value = user.role || '';
    const modal = document.getElementById('editUserModal');
    if (!modal) return alert('Edit modal not found');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function closeEditModal() {
    const modal = document.getElementById('editUserModal');
    if (!modal) return;
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}

async function submitEditForm(e) {
    e && e.preventDefault();
    console.log('submitEditForm called');
    const btn = document.getElementById('editSaveBtn');
    if (!btn) { console.error('editSaveBtn not found'); showToast('Request failed', 'error'); return false; }
    btn.disabled = true;
    // show immediate feedback
    const userIdEl = document.getElementById('edit_user_id');
    const fullEl = document.getElementById('edit_full_name');
    const emailEl = document.getElementById('edit_email');
    const roleEl = document.getElementById('edit_role');
    if (!userIdEl || !fullEl || !emailEl || !roleEl) {
        console.error('Edit form elements missing');
        showToast('Request failed', 'error');
        btn.disabled = false;
        return false;
    }
    const userId = userIdEl.value;
    const full = fullEl.value.trim();
    const email = emailEl.value.trim();
    const role = roleEl.value;
    // basic validation
    if (!full || !email) {
        showToast('Please fill required fields', 'error');
        btn.disabled = false;
        return false;
    }
    try {
        const form = new URLSearchParams();
        form.append('ajax','1'); form.append('action','edit_user'); form.append('user_id', String(userId));
        form.append('full_name', full); form.append('email', email); form.append('role', role);
        console.log('Submitting edit_user', { user_id: userId, full: full, email: email, role: role });
        const res = await fetch(location.href, { method: 'POST', body: form });
        // Always read text first so we can show raw server output if JSON parse fails
        const raw = await res.text();
        let j;
        try {
            j = JSON.parse(raw);
        } catch (parseErr) {
            console.warn('edit_user: server returned non-JSON response:', raw);
            // If HTTP status indicates success, treat as success but include raw text as message
            j = { success: res.ok, message: raw || (res.ok ? 'OK (no JSON)' : 'Server error') };
        }
        console.log('edit_user response', j);
    if (j && j.success) {
            try {
                const tr = getRowById(userId);
                // if server returned updated user data use it, otherwise build from form
                const userObj = j.user || { id: userId, full_name: full, email: email, role: role };
                if (tr) updateRowAfterEdit(tr, userObj);
                // if view modal open for same user, refresh
                const userModal = document.getElementById('userModal');
                if (userModal && userModal.dataset.userId && parseInt(userModal.dataset.userId) === parseInt(userId)) {
                    showUserModal(userObj);
                }
                // ensure modal is closed and user sees confirmation (top alert style)
                closeEditModal();
                showTopAlert('success', 'Successfully Changed!');
            } catch (innerErr) {
                console.error('Error applying edit changes on client:', innerErr);
                // still close modal and show top alert even if DOM patch failed
                closeEditModal();
                showTopAlert('success', 'Successfully Changed!');
            }
        } else {
            // Show server-provided message if available, otherwise a generic message
            const msg = (j && j.message) ? j.message : ('Update failed' + (raw ? (': ' + raw) : ''));
            showTopAlert('error', msg);
        }
    } catch (err) {
        console.error('submitEditForm error', err);
        showTopAlert('error', 'Request failed');
    }
    btn.disabled = false;
    return false;
}

function editUserFromModal() {
    const modal = document.getElementById('userModal');
    if (!modal || !modal.dataset.userId) return;
    editUser(null, modal.dataset.userId);
}

function updateRowAfterEdit(tr, user) {
    if (!tr || !user) return;
    tr.querySelector('td:nth-child(1)').textContent = user.full_name;
    tr.querySelector('td:nth-child(2)').textContent = user.email;
    tr.querySelector('td:nth-child(3)').textContent = user.role;
    // update dataset
    tr.dataset.user = JSON.stringify(Object.assign(JSON.parse(tr.dataset.user || '{}'), user));
}

async function deleteUser(btn, userId) {
    // open the simple Yes/No confirmation modal
    const modal = document.getElementById('deleteConfirmModal');
    if (!modal) {
        // fallback to browser confirm
        if (!confirm('Are you sure you want to delete this?')) return;
        btn.disabled = true;
        try {
            const form = new URLSearchParams();
            form.append('ajax','1'); form.append('action','delete_user'); form.append('user_id', String(userId));
            const res = await fetch(location.href, { method: 'POST', body: form });
            const j = await res.json();
            if (j.success) {
                const tr = getRowById(userId);
                if (tr) tr.remove();
            } else {
                alert(j.message || 'Delete failed');
            }
        } catch (err) { console.error(err); alert('Request failed'); }
        btn.disabled = false;
        return;
    }

    // store data on modal and show it
    modal.dataset.userId = String(userId);
    // show modal
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function closeDeleteModal() {
    const modal = document.getElementById('deleteConfirmModal');
    if (!modal) return;
    modal.classList.add('hidden');
    modal.classList.remove('flex');
    delete modal.dataset.userId;
}

async function confirmDelete() {
    const modal = document.getElementById('deleteConfirmModal');
    if (!modal || !modal.dataset.userId) return;
    const userId = modal.dataset.userId;
    const btn = document.getElementById('confirmDeleteBtn');
    btn.disabled = true;
    try {
        const form = new URLSearchParams();
        form.append('ajax','1'); form.append('action','delete_user'); form.append('user_id', String(userId));
        const res = await fetch(location.href, { method: 'POST', body: form });
        let j;
        try {
            j = await res.json();
        } catch (parseErr) {
            const txt = await res.text();
            console.warn('Non-JSON response from delete_user:', txt);
            j = { success: res.ok, message: res.ok ? 'OK (non-JSON response)' : (txt || 'Server error') };
        }
        if (j.success) {
            const tr = getRowById(userId);
            if (tr) tr.remove();
            closeDeleteModal();
        } else {
            showToast(j.message || 'Delete failed', 'error');
        }
    } catch (err) { console.error(err); showToast('Request failed', 'error'); }
    btn.disabled = false;
}

async function toggleStatus(btn, userId) {
    btn.disabled = true;
    try {
        const form = new URLSearchParams();
        form.append('ajax', '1');
        form.append('action', 'toggle_status');
        form.append('user_id', String(userId));
        const res = await fetch(location.href, { method: 'POST', body: form });
        let j;
        try {
            j = await res.json();
        } catch (parseErr) {
            const txt = await res.text();
            console.warn('Non-JSON response from toggle_status:', txt);
            j = { success: res.ok, status: null, message: res.ok ? 'OK (non-JSON response)' : (txt || 'Server error') };
        }
        if (j.success) {
            const tr = getRowById(userId);
            const badge = tr && tr.querySelector('.status-badge');
            if (badge) {
                const newStatus = j.status || (badge.textContent === 'Active' ? 'Inactive' : 'Active');
                badge.textContent = newStatus;
                badge.className = 'status-badge px-2 py-1 rounded-full text-xs ' + (newStatus==='Active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800');
            }
        } else {
            showToast(j.message || 'Update failed', 'error');
        }
    } catch (err) { console.error(err); alert('Request failed'); }
    btn.disabled = false;
}
</script>
