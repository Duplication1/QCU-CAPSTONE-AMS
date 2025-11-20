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

// Establish mysqli database connection
$dbConfig = Config::database();
try {
    $conn = new mysqli($dbConfig['host'], $dbConfig['username'], $dbConfig['password'], $dbConfig['name']);
    $conn->set_charset('utf8mb4');
} catch (mysqli_sql_exception $e) {
    if (Config::isDebug()) {
        die("Database connection failed: " . $e->getMessage());
    } else {
        die("Database connection error. Please contact administrator.");
    }
}

// Helpers: avatar paths and saving
function ams_get_avatar_dir_fs() {
    // filesystem path to avatars directory
    $dir = realpath(__DIR__ . '/../../assets/images');
    if ($dir === false) $dir = __DIR__ . '/../../assets/images';
    $avatars = $dir . '/avatars';
    if (!is_dir($avatars)) { @mkdir($avatars, 0777, true); }
    return $avatars;
}
function ams_get_avatar_web_base() {
    // path used in img src relative to this view
    return '../../assets/images/avatars';
}
function ams_find_avatar_web($userId) {
    $dir = ams_get_avatar_dir_fs();
    $patterns = ["avatar_{$userId}.jpg","avatar_{$userId}.jpeg","avatar_{$userId}.png","avatar_{$userId}.webp"];
    foreach ($patterns as $p) {
        if (file_exists($dir . '/' . $p)) {
            return ams_get_avatar_web_base() . '/' . $p;
        }
    }
    return '';
}
function ams_save_uploaded_avatar($userId, $fileArr) {
    if (!isset($fileArr) || !is_array($fileArr) || ($fileArr['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) return '';
    $tmp = $fileArr['tmp_name'];
    if (!$tmp || !is_uploaded_file($tmp)) return '';
    $ext = strtolower(pathinfo($fileArr['name'] ?? '', PATHINFO_EXTENSION));
    $allowed = ['jpg','jpeg','png','webp'];
    if (!in_array($ext, $allowed, true)) { $ext = 'jpg'; }
    $dir = ams_get_avatar_dir_fs();
    // remove old files
    foreach (['jpg','jpeg','png','webp'] as $oldExt) { @unlink($dir . "/avatar_{$userId}.{$oldExt}"); }
    $dest = $dir . "/avatar_{$userId}.{$ext}";
    if (@move_uploaded_file($tmp, $dest)) {
        return ams_get_avatar_web_base() . "/avatar_{$userId}.{$ext}";
    }
    return '';
}

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
        $idNumber = trim($_POST['id_number'] ?? '');
        if ($uid <= 0) { echo json_encode(['success'=>false,'message'=>'Invalid user']); exit; }
        if ($full === '' || $email === '' || $role === '') { echo json_encode(['success'=>false,'message'=>'Missing fields']); exit; }
        $u = $conn->prepare("UPDATE users SET id_number = ?, full_name = ?, email = ?, role = ?, updated_at = NOW() WHERE id = ?");
        $u->bind_param('ssssi', $idNumber, $full, $email, $role, $uid);
        $ok = $u->execute();
        $u->close();
        if ($ok) {
            $avatarWeb = '';
            if (!empty($_FILES['profile_photo'])) {
                $avatarWeb = ams_save_uploaded_avatar($uid, $_FILES['profile_photo']);
            }
            // return success and updated user data (no session flash — client will update DOM)
            echo json_encode(['success'=>true,'user'=>['id'=>$uid,'id_number'=>$idNumber,'full_name'=>$full,'email'=>$email,'role'=>$role,'avatar'=>$avatarWeb]]);
        } else {
            echo json_encode(['success'=>false,'message'=>'Update failed']);
        }
        exit;
    }

    if ($action === 'suspend_user') {
        $uid = intval($_POST['user_id'] ?? 0);
        if ($uid <= 0) { echo json_encode(['success'=>false,'message'=>'Invalid user']); exit; }
        // prevent suspending self
        if (isset($_SESSION['user_id']) && intval($_SESSION['user_id']) === $uid) { echo json_encode(['success'=>false,'message'=>'Cannot deactivate yourself']); exit; }
        
        // Try to update to Deactivated
        $s = $conn->prepare("UPDATE users SET status = 'Deactivated' WHERE id = ?");
        $s->bind_param('i', $uid);
        $ok = $s->execute();
        
        if (!$ok) {
            $error = $s->error;
            $s->close();
            echo json_encode(['success'=>false,'message'=>'Deactivate failed: ' . $error]); exit;
        }
        
        // Check if update actually worked by reading back the status
        $s->close();
        $check = $conn->prepare("SELECT status FROM users WHERE id = ?");
        $check->bind_param('i', $uid);
        $check->execute();
        $result = $check->get_result()->fetch_assoc();
        $check->close();
        
        $actualStatus = $result['status'] ?? '';
        
        error_log("Deactivate attempt - User ID: $uid, Status after UPDATE: '$actualStatus'");
        
        // If status is empty or Deactivated, success
        if ($actualStatus === '' || $actualStatus === 'Deactivated') {
            echo json_encode(['success'=>true,'status'=>'Deactivated','actualStatus'=>$actualStatus]);
        } else {
            // Database rejected the value - ENUM doesn't have 'Deactivated'
            // It likely reverted to the default 'Active' or another ENUM value
            echo json_encode([
                'success'=>false,
                'message'=>'⚠️ DATABASE ERROR: The status ENUM is missing "Deactivated". Run this SQL in phpMyAdmin: ALTER TABLE users MODIFY status ENUM(\'Active\',\'Inactive\',\'Suspended\',\'Deactivated\') DEFAULT \'Active\';',
                'actualStatus'=>$actualStatus
            ]);
        }
        exit;
    }

    if ($action === 'unsuspend_user') {
        $uid = intval($_POST['user_id'] ?? 0);
        if ($uid <= 0) { echo json_encode(['success'=>false,'message'=>'Invalid user']); exit; }
        
        // Only activate users that are deactivated (empty status or explicitly deactivated)
        $check = $conn->prepare("SELECT status FROM users WHERE id = ?");
        $check->bind_param('i', $uid);
        $check->execute();
        $result = $check->get_result()->fetch_assoc();
        $check->close();
        
        $currentStatus = $result['status'] ?? '';
        if ($currentStatus !== '' && $currentStatus !== 'Deactivated') {
            echo json_encode(['success'=>false,'message'=>'User is not deactivated']); exit;
        }
        
        $s = $conn->prepare("UPDATE users SET status = 'Active' WHERE id = ?");
        $s->bind_param('i', $uid);
        $ok = $s->execute();
        if (!$ok) {
            $error = $s->error;
            $s->close();
            echo json_encode(['success'=>false,'message'=>'Activate failed: ' . $error]); exit;
        }
        $s->close();
        echo json_encode(['success'=>true,'status'=>'Active']);
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

    // Create user
    if ($action === 'create_user') {
        $id_number = trim($_POST['id_number'] ?? '');
        $full = trim($_POST['full_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $role = trim($_POST['role'] ?? '');
        $password = trim($_POST['password'] ?? '');

        // require password now
        if ($full === '' || $email === '' || $role === '' || $password === '') {
            echo json_encode(['success'=>false,'message'=>'Missing required fields (password is required)']); exit;
        }

        // check duplicates
        $chk = $conn->prepare("SELECT id FROM users WHERE email = ? OR id_number = ? LIMIT 1");
        $chk->bind_param('ss', $email, $id_number);
        $chk->execute();
        $rchk = $chk->get_result()->fetch_assoc();
        $chk->close();
        if ($rchk) {
            echo json_encode(['success'=>false,'message'=>'A user with this email or ID number already exists']); exit;
        }

        // password required - hash it
        $hash = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $conn->prepare("INSERT INTO users (id_number, full_name, email, role, password, status, created_at) VALUES (?, ?, ?, ?, ?, 'Active', NOW())");
        $stmt->bind_param('sssss', $id_number, $full, $email, $role, $hash);
        $ok = $stmt->execute();
        if (!$ok) {
            $err = $stmt->error;
            $stmt->close();
            echo json_encode(['success'=>false,'message'=>'Insert failed: '.$err]); exit;
        }
        $newId = $stmt->insert_id;
        $stmt->close();

        // handle avatar upload, if any
        $avatarWeb = '';
        if (!empty($_FILES['profile_photo'])) {
            $avatarWeb = ams_save_uploaded_avatar($newId, $_FILES['profile_photo']);
        }

        // return created user object (do not return password)
        $userObj = [
            'id' => $newId,
            'id_number' => $id_number,
            'full_name' => $full,
            'email' => $email,
            'role' => $role,
            'status' => 'Active',
            'created_at' => date('Y-m-d H:i:s'),
            'avatar' => $avatarWeb
        ];

        echo json_encode(['success'=>true,'user'=>$userObj,'message'=>'User Created!']);
        exit;
    }
}

// Fetch all users for display
$users = [];
$q = $conn->query("SELECT id, id_number, full_name, email, role, status, created_at, last_login FROM users ORDER BY created_at DESC");
if ($q && $q->num_rows > 0) {
    while ($row = $q->fetch_assoc()) {
        // Debug: Check if status is empty and log it
        if (empty($row['status'])) {
            error_log("User ID {$row['id']} has empty status");
        }
        $users[] = $row;
    }
}
// no server-side flash alerts here; client updates DOM in-place

// Now include layout header for normal page rendering
include '../components/layout_header.php';
?>

        <!-- Main Content -->
        <main class="p-6">
            <div class="bg-white rounded-xl shadow-lg p-6">
                <h2 class="text-2xl font-bold text-gray-800 mb-4">User Management</h2>

                <div class="flex items-center justify-between mb-4 gap-3">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-800">All users <span id="usersCount" class="text-sm text-gray-500"><?php echo count($users); ?></span></h3>
                    </div>
                    <div class="flex items-center gap-3">
<div class="relative flex items-center gap-2">
  <input id="userSearch" oninput="filterUsers()" type="search" placeholder="Search users..."
    class="w-64 pl-10 pr-4 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#1E3A8A] focus:border-[#1E3A8A] transition" />
  <i class="fas fa-search absolute left-3 top-2.5 text-gray-400"></i>
  
  <!-- Filter Button -->
  <div class="relative">
    <button id="filterBtn" onclick="toggleFilterMenu()" 
      class="px-3 py-2 bg-gray-100 border border-gray-300 rounded-lg hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-[#1E3A8A] transition"
      title="Filter users">
      <i class="fas fa-filter text-gray-600"></i>
    </button>
    
    <!-- Filter Dropdown Menu -->
    <div id="filterMenu" class="hidden absolute right-0 mt-2 w-64 bg-white border border-gray-200 rounded-lg shadow-lg z-50">
      <div class="p-4">
        <h4 class="text-sm font-semibold text-gray-700 mb-3">Filter Users</h4>
        
        <!-- Role Filter -->
        <div class="mb-4">
          <label class="block text-xs font-medium text-gray-600 mb-2">Role</label>
          <select id="roleFilter" onchange="applyFilters()" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#1E3A8A]">
            <option value="">All Roles</option>
            <option value="Administrator">Administrator</option>
            <option value="Technician">Technician</option>
            <option value="LaboratoryStaff">Laboratory Staff</option>
            <option value="Student">Student</option>
          </select>
        </div>
        
        <!-- Status Filter -->
        <div class="mb-4">
          <label class="block text-xs font-medium text-gray-600 mb-2">Status</label>
          <select id="statusFilter" onchange="applyFilters()" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#1E3A8A]">
            <option value="">All Status</option>
            <option value="Active">Active</option>
            <option value="Inactive">Inactive</option>
            <option value="Deactivated">Deactivated</option>
          </select>
        </div>
        
        <!-- Clear Filters Button -->
        <button onclick="clearFilters()" class="w-full px-3 py-2 text-sm bg-gray-100 hover:bg-gray-200 rounded-lg transition">
          Clear Filters
        </button>
      </div>
    </div>
  </div>
</div>
                        <button id="addUserBtn" onclick="openAddUserModal()"
  class="flex items-center gap-2 px-4 py-2 bg-[#1E3A8A] text-white text-sm font-semibold rounded-lg shadow hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-[#1E3A8A]">
  <i class="fas fa-user-plus"></i>
  Add User
</button>
                    </div>
                </div>

                <div class="overflow-x-auto">
 <table id="usersTable" class="min-w-full rounded-lg overflow-hidden divide-y divide-gray-200">
  <thead class="bg-blue-100 text-[#1E3A8A] sticky top-0 z-10">
    <tr>
      <th class="px-4 py-2 text-left text-xs font-semibold uppercase">Name</th>
      <th class="px-4 py-2 text-left text-xs font-semibold uppercase">Email</th>
      <th class="px-4 py-2 text-left text-xs font-semibold uppercase">Role</th>
      <th class="px-4 py-2 text-left text-xs font-semibold uppercase">Status</th>
      <th class="px-4 py-2 text-left text-xs font-semibold uppercase">Joined</th>
      <th class="px-4 py-2 text-left text-xs font-semibold uppercase">Last Active</th>
    </tr>
  </thead>
  <tbody class="bg-white divide-y divide-gray-100">
    <?php foreach ($users as $u): ?>
    <?php $avatarWeb = ams_find_avatar_web($u['id']); $udata = $u; $udata['avatar'] = $avatarWeb; $initial = strtoupper(substr(trim($u['full_name']), 0, 1)); ?>
    <tr class="hover:bg-blue-50 transition-colors" data-user='<?php echo json_encode($udata, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT); ?>'>
      <td class="px-4 py-3">
        <div class="flex items-center gap-3">
          <div class="w-8 h-8 rounded-full bg-gray-200 overflow-hidden flex items-center justify-center text-xs font-medium text-gray-700">
            <?php if ($avatarWeb): ?>
              <img src="<?php echo htmlspecialchars($avatarWeb); ?>" alt="avatar" class="w-full h-full object-cover" />
            <?php else: ?>
              <span><?php echo htmlspecialchars($initial ?: '?'); ?></span>
            <?php endif; ?>
          </div>
          <span class="font-medium text-gray-800"><?php echo htmlspecialchars($u['full_name']); ?></span>
        </div>
      </td>
      <td class="px-4 py-3 text-gray-500"><?php echo htmlspecialchars($u['email']); ?></td>
      <td class="px-4 py-3"><?php echo htmlspecialchars($u['role']); ?></td>
      <td class="px-4 py-3">
        <?php 
        // Handle empty status (when ENUM value doesn't exist in database)
        $status = $u['status'] ?: 'Active';
        $statusClass = 'bg-gray-100 text-gray-600';
        if ($status === 'Active') {
          $statusClass = 'bg-green-100 text-green-700';
        } elseif ($status === 'Deactivated' || $status === '') {
          $statusClass = 'bg-red-100 text-red-700';
          if ($status === '') $status = 'Deactivated'; // Fix blank status
        }
        ?>
        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium <?php echo $statusClass; ?>">
          <?php echo htmlspecialchars($status); ?>
        </span>
      </td>
      <td class="px-4 py-3"><?php echo !empty($u['created_at']) ? date('M d, Y', strtotime($u['created_at'])) : '-'; ?></td>
      <td class="px-4 py-3 flex items-center justify-between">
        <span><?php echo !empty($u['last_login']) ? date('M d, Y H:i', strtotime($u['last_login'])) : '-'; ?></span>
        <div class="relative inline-block text-left ml-3">
          <button type="button" onclick="toggleRowMenu(this, <?php echo (int)$u['id']; ?>)" class="p-1 rounded hover:bg-gray-100 text-sm" aria-expanded="false" aria-haspopup="true" title="Actions">
            <i class="fa fa-ellipsis-v text-sm"></i>
          </button>
          <div class="hidden origin-top-right absolute right-0 mt-2 w-40 bg-white border rounded shadow-lg z-60" role="menu">
            <div class="py-1">
              <button type="button" class="w-full text-left px-3 py-2 text-sm text-gray-700 hover:bg-gray-100" onclick="editUser(this, <?php echo (int)$u['id']; ?>)">
                <i class="fa fa-pencil mr-2"></i> Edit details
              </button>
              <?php if ($u['status'] === 'Deactivated' || $u['status'] === ''): ?>
              <button type="button" class="w-full text-left px-3 py-2 text-sm text-green-600 hover:bg-green-50" onclick="unsuspendUser(<?php echo (int)$u['id']; ?>)">
                <i class="fa fa-check-circle mr-2"></i> Activate user
              </button>
              <?php else: ?>
              <button type="button" class="w-full text-left px-3 py-2 text-sm text-red-600 hover:bg-red-50" onclick="suspendUser(<?php echo (int)$u['id']; ?>)">
                <i class="fa fa-ban mr-2"></i> Deactivate user
              </button>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </td>
    </tr>
    <?php endforeach; ?>
  </tbody>
</table>

                </div>
                
                <!-- Pagination Controls -->
                <div class="mt-4 flex items-center justify-center border-t pt-4">
                    <div id="pageNumbers" class="flex items-center gap-1">
                        <!-- Page numbers will be inserted here -->
                    </div>
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

<!-- Edit user modal (overlay + styled modal matching provided design) -->
<div id="editUserModal" class="hidden fixed inset-0 z-50 flex items-center justify-center">
    <div class="absolute inset-0 bg-black bg-opacity-40 backdrop-blur-sm" style="background-color: rgba(0,0,0,0.4);" onclick="closeEditModal()"></div>
    <div class="relative bg-white rounded-lg shadow-2xl max-w-3xl w-11/12 md:w-3/4 p-6">
        <div class="flex items-start gap-4">
            <!-- <div class="flex-shrink-0 flex flex-col items-center gap-2">
                <div id="edit_profile_photo_container" class="w-20 h-20 rounded-full bg-gray-100 overflow-hidden flex items-center justify-center cursor-pointer" role="button" tabindex="0" aria-label="Update profile photo" title="Update profile photo" onclick="triggerEditProfileUpload()" onkeydown="if(event.key==='Enter' || event.key===' '){ event.preventDefault(); triggerEditProfileUpload(); }">
                    <img id="edit_profile_photo_img" src="" alt="avatar" class="w-full h-full object-cover" style="display:none;" />
                    <span id="edit_profile_initial" class="text-gray-500 text-xs uppercase tracking-wide">Add photo</span>
                </div>
                <input id="edit_profile_file" name="profile_photo" type="file" accept="image/*" class="hidden" onchange="previewEditProfileFile(event)" />
            </div> -->
            <div class="flex-1">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 id="edit_modal_title" class="text-xl font-semibold">Edit User</h3>
                        <p id="edit_modal_subtitle" class="text-sm text-gray-600">Update account details</p>
                    </div>
                </div>

                <form id="editUserForm" onsubmit="return submitEditForm(event)" class="mt-4">
                    <input type="hidden" name="user_id" id="edit_user_id" />
                    <div class="space-y-3">
                        <!-- Name row (always full width) -->
                        <div>
                            <label class="block text-xs text-gray-600">Name</label>
                            <div class="flex gap-2 mt-2">
                                <input id="edit_first_name" name="first_name" placeholder="First" class="w-1/2 border rounded px-3 py-2" required />
                                <input id="edit_last_name" name="last_name" placeholder="Last" class="w-1/2 border rounded px-3 py-2" required />
                            </div>
                        </div>
                        <!-- Row: ID Number (left) + Email (right) -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs text-gray-600">ID Number</label>
                                <input id="edit_id_number" name="id_number" type="text" inputmode="numeric" pattern="[0-9\-]*" class="w-full border rounded px-3 py-2 mt-2" oninput="validateIdNumberInput(this, 'edit_id_error')" />
                                <span id="edit_id_error" class="text-xs text-red-600 hidden">*Use numbers only</span>
                            </div>
                            <div>
                                <label class="block text-xs text-gray-600">Email address</label>
                                <input id="edit_email" name="email" type="email" class="w-full border rounded px-3 py-2 mt-2" required />
                            </div>
                        </div>
                        <!-- Role row (full width) -->
                        <div>
                            <label class="block text-xs text-gray-600">Role</label>
                            <select id="edit_role" name="role" class="w-full border rounded px-3 py-2 mt-2">
                                <option value="Administrator">Administrator</option>
                                <option value="Technician">Technician</option>
                                <option value="LaboratoryStaff">LaboratoryStaff</option>
                                <option value="Student">Student</option>
                            </select>
                        </div>
                    </div>

                    <div class="mt-6 flex items-center justify-between">
                        <button type="button" id="editDeleteBtn" onclick="deleteUser(null, document.getElementById('edit_user_id').value)" class="px-3 py-2 rounded bg-red-50 text-red-600 border border-red-100">Delete user</button>
                        <div class="flex items-center gap-3">
                            <button type="button" onclick="closeEditModal()" class="px-4 py-2 rounded bg-gray-100">Cancel</button>
                            <button type="submit" id="editSaveBtn" class="px-4 py-2 rounded bg-black text-white">Save changes</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
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

<!-- Suspend confirmation modal (Yes / No) -->
<div id="suspendConfirmModal" class="hidden fixed inset-0 z-50 flex items-center justify-center">
    <div class="absolute inset-0 bg-black bg-opacity-40 backdrop-blur-sm" style="background-color: rgba(0,0,0,0.4);" onclick="closeSuspendModal()"></div>
        <div class="relative bg-white rounded-lg shadow-lg max-w-md w-11/12 p-6">
            <div class="flex flex-col items-center text-center">
                <div class="mb-4">
                    <img src="../../assets/images/8376179.png" alt="warning" class="w-16 h-16 object-contain mx-auto" />
                </div>
                <h3 class="text-xl font-semibold mb-2">Are you sure?</h3>
                <p class="text-sm text-gray-600 mb-6">Do you really want to deactivate this user? Their status will be set to Deactivated.</p>
                <div class="flex gap-3 flex-col sm:flex-row w-full justify-center mt-2">
                    <button id="cancelSuspendBtn" type="button" onclick="closeSuspendModal()" class="px-4 py-2 rounded bg-gray-200 text-gray-800 w-full sm:w-auto inline-block" style="min-width:120px;">No</button>
                    <button id="confirmSuspendBtn" type="button" onclick="confirmSuspend()" class="px-4 py-2 rounded w-full sm:w-auto inline-block" style="min-width:120px;background-color:#ef4444;color:#ffffff;border:none;box-shadow:0 1px 2px rgba(0,0,0,0.1);">Yes</button>
                </div>
            </div>
        </div>
</div>

<!-- Add User Modal -->
<div id="addUserModal" class="hidden fixed inset-0 z-50 flex items-center justify-center">
    <div class="absolute inset-0 bg-black bg-opacity-40 backdrop-blur-sm z-40" style="background-color: rgba(0,0,0,0.4);" onclick="closeAddUserModal()"></div>
    <div class="relative bg-white rounded-lg shadow-2xl max-w-3xl w-11/12 md:w-3/4 p-6 z-50">
        <div class="flex items-start gap-4">
            <!-- <div class="flex-shrink-0 flex flex-col items-center gap-2">
                <div id="add_profile_photo_container" class="w-20 h-20 rounded-full bg-gray-100 overflow-hidden flex items-center justify-center cursor-pointer" role="button" tabindex="0" aria-label="Upload profile photo" title="Upload profile photo" onclick="triggerAddProfileUpload()" onkeydown="if(event.key==='Enter' || event.key===' '){ event.preventDefault(); triggerAddProfileUpload(); }">
                    <img id="add_profile_photo_img" src="" alt="avatar" class="w-full h-full object-cover" style="display:none;" />
                    <span id="add_profile_initial" class="text-gray-500 text-xs uppercase tracking-wide">Add photo</span>
                </div>
                <input id="add_profile_file" name="profile_photo" type="file" accept="image/*" class="hidden" onchange="previewAddProfileFile(event)" />
            </div> -->
            <div class="flex-1">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-xl font-semibold">Create New User</h3>
                        <p class="text-sm text-gray-600">Add a new account and set an initial password</p>
                    </div>
                </div>

                <form id="addUserForm" onsubmit="return submitAddUser(event)" class="mt-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs text-gray-600">Name</label>
                            <div class="flex gap-2 mt-2">
                                <input id="add_first_name" name="first_name" placeholder="First Name" class="w-1/2 border rounded px-3 py-2" required />
                                <input id="add_last_name" name="last_name" placeholder="Last Name" class="w-1/2 border rounded px-3 py-2" required />
                            </div>
                        </div>
                        <div>
                       
                        </div>

                        <div>
                            <label class="block text-xs text-gray-600">ID Number</label>
                            <input id="add_id_number" name="id_number" type="text" inputmode="numeric" pattern="[0-9\-]*" class="w-full border rounded px-3 py-2 mt-2" oninput="validateIdNumberInput(this, 'add_id_error')" />
                            <span id="add_id_error" class="text-xs text-red-600 hidden">*Use numbers only</span>
                        </div>
                        <div>
                            <label class="block text-xs text-gray-600">Email address</label>
                            <input id="add_email" name="email" type="email" class="w-full border rounded px-3 py-2 mt-2" required />
                        </div>


                        <div>
                            <label class="block text-xs text-gray-600">Role</label>
                            <select id="add_role" name="role" class="w-full border rounded px-3 py-2 mt-2" required>
                                <option value="Administrator">Administrator</option>
                                <option value="Technician">Technician</option>
                                <option value="LaboratoryStaff">LaboratoryStaff</option>
                                <option value="Student">Student</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs text-gray-600">Password</label>
                            <input id="add_password" name="password" type="password" class="w-full border rounded px-3 py-2 mt-2" required />
                        </div>
                    </div>

                    <div class="mt-6 flex items-center justify-end gap-3">
                        <button type="button" onclick="closeAddUserModal()" class="px-4 py-2 rounded bg-gray-100">Cancel</button>
                        <button type="submit" id="addUserSaveBtn" class="px-4 py-2 rounded bg-blue-600 text-white">Create</button>
                    </div>
                </form>
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
// Pagination variables
let currentPage = 1;
let pageSize = 10;
let allRows = [];

// Initialize pagination on page load
document.addEventListener('DOMContentLoaded', function() {
    allRows = Array.from(document.querySelectorAll('#usersTable tbody tr'));
    updatePagination();
});

function changePageSize() {
    pageSize = parseInt(document.getElementById('pageSize').value) || 10;
    currentPage = 1;
    updatePagination();
}

function changePage(direction) {
    const visibleRows = getVisibleRows();
    const totalPages = Math.ceil(visibleRows.length / pageSize);
    
    if (direction === 'prev' && currentPage > 1) {
        currentPage--;
    } else if (direction === 'next' && currentPage < totalPages) {
        currentPage++;
    } else if (typeof direction === 'number') {
        currentPage = direction;
    }
    
    updatePagination();
}

function getVisibleRows() {
    const searchQuery = (document.getElementById('userSearch').value || '').toLowerCase().trim();
    const roleFilter = (document.getElementById('roleFilter')?.value || '').toLowerCase();
    const statusFilter = (document.getElementById('statusFilter')?.value || '').toLowerCase();
    
    return allRows.filter(row => {
        const user = row.dataset.user ? JSON.parse(row.dataset.user) : null;
        if (!user) return true;
        
        // Search filter
        const searchText = (user.full_name + ' ' + user.email + ' ' + (user.role || '')).toLowerCase();
        const matchesSearch = !searchQuery || searchText.indexOf(searchQuery) !== -1;
        
        // Role filter
        const userRole = (user.role || '').toLowerCase();
        const matchesRole = !roleFilter || userRole === roleFilter || 
                           (roleFilter === 'laboratorystaff' && userRole === 'laboratory staff');
        
        // Status filter
        const userStatus = (user.status || 'active').toLowerCase();
        const matchesStatus = !statusFilter || userStatus === statusFilter;
        
        return matchesSearch && matchesRole && matchesStatus;
    });
}

function updatePagination() {
    const visibleRows = getVisibleRows();
    const totalRows = visibleRows.length;
    const totalPages = Math.ceil(totalRows / pageSize);
    
    // Ensure current page is valid
    if (currentPage > totalPages && totalPages > 0) {
        currentPage = totalPages;
    }
    if (currentPage < 1) {
        currentPage = 1;
    }
    
    const startIndex = (currentPage - 1) * pageSize;
    const endIndex = Math.min(startIndex + pageSize, totalRows);
    
    // Hide all rows first
    allRows.forEach(row => row.style.display = 'none');
    
    // Show only rows for current page
    visibleRows.slice(startIndex, endIndex).forEach(row => row.style.display = '');
    
    // Update page numbers
    renderPageNumbers(totalPages);
}

function renderPageNumbers(totalPages) {
    const container = document.getElementById('pageNumbers');
    container.innerHTML = '';
    
    if (totalPages <= 1) return;
    
    const maxVisible = 5;
    let startPage = Math.max(1, currentPage - Math.floor(maxVisible / 2));
    let endPage = Math.min(totalPages, startPage + maxVisible - 1);
    
    if (endPage - startPage < maxVisible - 1) {
        startPage = Math.max(1, endPage - maxVisible + 1);
    }
    
    // First page
    if (startPage > 1) {
        addPageButton(container, 1);
        if (startPage > 2) {
            const ellipsis = document.createElement('span');
            ellipsis.className = 'px-2 text-gray-400';
            ellipsis.textContent = '...';
            container.appendChild(ellipsis);
        }
    }
    
    // Page numbers
    for (let i = startPage; i <= endPage; i++) {
        addPageButton(container, i);
    }
    
    // Last page
    if (endPage < totalPages) {
        if (endPage < totalPages - 1) {
            const ellipsis = document.createElement('span');
            ellipsis.className = 'px-2 text-gray-400';
            ellipsis.textContent = '...';
            container.appendChild(ellipsis);
        }
        addPageButton(container, totalPages);
    }
}

function addPageButton(container, pageNum) {
    const btn = document.createElement('button');
    btn.textContent = pageNum;
    btn.className = 'px-3 py-1 text-sm border rounded-lg ' + 
        (pageNum === currentPage 
            ? 'bg-[#1E3A8A] text-white border-[#1E3A8A]' 
            : 'border-gray-300 hover:bg-gray-50');
    btn.onclick = () => changePage(pageNum);
    container.appendChild(btn);
}

function validateIdNumberInput(input, errorId) {
    const errorEl = document.getElementById(errorId);
    const hasInvalid = /[^0-9\-]/.test(input.value);
    if (hasInvalid) {
        errorEl.classList.remove('hidden');
        input.value = input.value.replace(/[^0-9\-]/g, '');
        setTimeout(() => errorEl.classList.add('hidden'), 2000);
    } else {
        errorEl.classList.add('hidden');
    }
}

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
function showTopAlert(type, msg, duration = 5000) {
    // Sanitize / normalize message (avoid dumping raw JSON)
    if (msg && typeof msg === 'string' && msg.trim().startsWith('{') && msg.indexOf('"success"') !== -1) {
        // Fallback generic text if a JSON blob was passed accidentally
        msg = type === 'success' ? 'Operation completed successfully.' : 'An error occurred.';
    }
    const existing = document.getElementById('topAjaxAlert');
    if (existing) { try { existing.remove(); } catch(e){} }
    const div = document.createElement('div');
    div.id = 'topAjaxAlert';
    const base = 'px-4 py-2 rounded mb-4 text-sm flex items-start gap-3 max-w-xl shadow';
    if (type === 'success') {
        div.className = 'bg-green-100 border border-green-400 text-green-700 ' + base;
        div.innerHTML = '<span class="font-semibold">Success:</span><span class="flex-1">' + msg + '</span>';
    } else {
        div.className = 'bg-red-100 border border-red-400 text-red-700 ' + base;
        div.innerHTML = '<span class="font-semibold">Error:</span><span class="flex-1">' + msg + '</span>';
    }
    const closeBtn = document.createElement('button');
    closeBtn.type = 'button';
    closeBtn.className = 'text-xs font-medium opacity-70 hover:opacity-100 transition';
    closeBtn.textContent = '✕';
    closeBtn.onclick = () => { try { div.remove(); } catch(e){} };
    div.appendChild(closeBtn);
    const main = document.querySelector('main');
    if (main) {
        // Insert as first child inside main (matches student side placement)
        main.insertBefore(div, main.firstChild);
    } else {
        document.body.appendChild(div);
    }
    setTimeout(() => { try { div.remove(); } catch(e){} }, duration);
}

function filterUsers() {
    currentPage = 1; // Reset to first page when searching
    updatePagination();
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
    const tr = getRowById(userId);
    const user = tr && tr.dataset.user ? JSON.parse(tr.dataset.user) : null;
    if (!user) { showToast('User not found', 'error'); return; }

    const modal = document.getElementById('editUserModal');
    if (!modal) { alert('Edit modal not found'); return; }

    document.getElementById('edit_user_id').value = userId;

    const full = (user.full_name || '').trim();
    let first = '', last = '';
    if (full) {
        const parts = full.split(/\s+/);
        first = parts.shift() || '';
        last = parts.join(' ') || '';
    }

    const fnameEl = document.getElementById('edit_first_name');
    const lnameEl = document.getElementById('edit_last_name');
    const emailEl = document.getElementById('edit_email');
    const roleEl = document.getElementById('edit_role');
    const idEl = document.getElementById('edit_id_number');
    const titleEl = document.getElementById('edit_modal_title');
    const subtitleEl = document.getElementById('edit_modal_subtitle');
    const imgEl = document.getElementById('edit_profile_photo_img');
    const initialEl = document.getElementById('edit_profile_initial');
    const photoContainer = document.getElementById('edit_profile_photo_container');
    const fileInput = document.getElementById('edit_profile_file');

    if (fileInput) fileInput.value = '';
    if (imgEl) { imgEl.src = ''; imgEl.style.display = 'none'; }
    if (initialEl) { initialEl.textContent = 'Add photo'; initialEl.style.display = ''; }

    if (fnameEl) fnameEl.value = first;
    if (lnameEl) lnameEl.value = last;
    if (emailEl) emailEl.value = user.email || '';
    if (roleEl) roleEl.value = user.role || '';
    if (idEl) idEl.value = user.id_number || '';
    if (titleEl) titleEl.textContent = full || 'Edit User';
    if (subtitleEl) subtitleEl.textContent = user.email || 'Update account details';

    // avatar preview handling
    if (photoContainer) photoContainer.classList.add('bg-gray-100');

    const avatar = user.avatar || user.profile_photo || '';
    if (avatar) {
        if (imgEl) { imgEl.src = avatar; imgEl.style.display = ''; }
        if (initialEl) { initialEl.textContent = ''; initialEl.style.display = 'none'; }
        if (photoContainer) photoContainer.classList.remove('bg-gray-100');
    } else {
        const fallback = (full || user.email || 'User').trim().charAt(0).toUpperCase() || 'U';
        if (imgEl) imgEl.style.display = 'none';
        if (initialEl) { initialEl.textContent = fallback; initialEl.style.display = ''; }
        if (photoContainer) photoContainer.classList.add('bg-gray-100');
    }

    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function closeEditModal() {
    const modal = document.getElementById('editUserModal');
    if (!modal) return;
    modal.classList.add('hidden');
    modal.classList.remove('flex');
    const imgEl = document.getElementById('edit_profile_photo_img');
    const initialEl = document.getElementById('edit_profile_initial');
    const container = document.getElementById('edit_profile_photo_container');
    const fileInput = document.getElementById('edit_profile_file');
    if (imgEl) { imgEl.src = ''; imgEl.style.display = 'none'; }
    if (initialEl) { initialEl.textContent = 'Add photo'; initialEl.style.display = ''; }
    if (container) container.classList.add('bg-gray-100');
    if (fileInput) fileInput.value = '';
}

async function submitEditForm(e) {
    e && e.preventDefault();
    console.log('submitEditForm called');
    const btn = document.getElementById('editSaveBtn');
    if (!btn) { console.error('editSaveBtn not found'); showToast('Request failed', 'error'); return false; }
    btn.disabled = true;
    // show immediate feedback
    const userIdEl = document.getElementById('edit_user_id');
    const firstEl = document.getElementById('edit_first_name');
    const lastEl = document.getElementById('edit_last_name');
    const emailEl = document.getElementById('edit_email');
    const roleEl = document.getElementById('edit_role');
    const idNumberEl = document.getElementById('edit_id_number');
    if (!userIdEl || !firstEl || !lastEl || !emailEl || !roleEl) {
        console.error('Edit form elements missing');
        showToast('Request failed', 'error');
        btn.disabled = false;
        return false;
    }
    const userId = userIdEl.value;
    const first = firstEl ? firstEl.value.trim() : '';
    const last = lastEl ? lastEl.value.trim() : '';
    const full = (first + ' ' + last).trim();
    const email = emailEl.value.trim();
    const role = roleEl.value;
    const idNumber = idNumberEl ? idNumberEl.value.trim() : '';
    // basic validation
    if (!full || !email) {
        showToast('Please fill required fields', 'error');
        btn.disabled = false;
        return false;
    }
    try {
        const form = new FormData();
        form.append('ajax','1'); form.append('action','edit_user'); form.append('user_id', String(userId));
        form.append('full_name', full); form.append('email', email); form.append('role', role);
        form.append('id_number', idNumber);
        const editFile = document.getElementById('edit_profile_file');
        if (editFile && editFile.files && editFile.files[0]) {
            form.append('profile_photo', editFile.files[0]);
        }
        console.log('Submitting edit_user', { user_id: userId, full: full, email: email, role: role, id_number: idNumber });
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
                const userObj = j.user || { id: userId, full_name: full, email: email, role: role, id_number: idNumber };
                const editImg = document.getElementById('edit_profile_photo_img');
                if ((!userObj.avatar || !userObj.avatar.length) && editImg && editImg.src) { try { userObj.avatar = editImg.src; } catch(e){} }
                if (tr) updateRowAfterEdit(tr, userObj);
                // if view modal open for same user, refresh
                const userModal = document.getElementById('userModal');
                if (userModal && userModal.dataset.userId && parseInt(userModal.dataset.userId) === parseInt(userId)) {
                    showUserModal(userObj);
                }
                // ensure modal is closed and user sees confirmation (action toast)
                closeEditModal();
                showActionToast((userObj.full_name || 'User') + ' details updated', function(){ try{ location.reload(); } catch(e){} }, function(){ showUserModal(userObj); });
            } catch (innerErr) {
                console.error('Error applying edit changes on client:', innerErr);
                // still close modal and show action toast even if DOM patch failed
                closeEditModal();
                showActionToast((userObj.full_name || 'User') + ' details updated', function(){ try{ location.reload(); } catch(e){} }, function(){ showUserModal(userObj); });
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

// Show an action-style toast with Undo and View links (bottom-right)
function showActionToast(message, onUndo, onView, duration = 6000) {
    // remove existing
    const existing = document.getElementById('actionToast');
    if (existing) existing.remove();
    const div = document.createElement('div');
    div.id = 'actionToast';
    div.className = 'fixed bottom-6 right-6 z-70 bg-white border rounded shadow-lg px-4 py-3 flex items-center gap-4';
    const msg = document.createElement('div'); msg.className = 'text-sm text-gray-800'; msg.textContent = message;
    const actions = document.createElement('div'); actions.className = 'flex items-center gap-2';
    const undoBtn = document.createElement('button'); undoBtn.className = 'text-xs px-2 py-1 bg-gray-100 rounded'; undoBtn.textContent = 'Undo';
    undoBtn.onclick = function(e){ try{ if (typeof onUndo === 'function') onUndo(); } finally { div.remove(); } };
    viewBtn.onclick = function(e){ try{ if (typeof onView === 'function') onView(); } finally { div.remove(); } };
    actions.appendChild(undoBtn); actions.appendChild(viewBtn);
    div.appendChild(msg); div.appendChild(actions);
    document.body.appendChild(div);
    setTimeout(()=>{ try{ div.remove(); } catch(e){} }, duration);
}

function editUserFromModal() {
    const modal = document.getElementById('userModal');
    if (!modal || !modal.dataset.userId) return;
    editUser(null, modal.dataset.userId);
}

// Preview selected profile file in the edit modal (client-side only)
function previewEditProfileFile(e) {
    const input = e.target;
    if (!input || !input.files || !input.files[0]) return;
    const file = input.files[0];
    const reader = new FileReader();
    reader.onload = function(ev) {
        const data = ev.target.result;
        const imgEl = document.getElementById('edit_profile_photo_img');
        const initialEl = document.getElementById('edit_profile_initial');
        const container = document.getElementById('edit_profile_photo_container');
        if (imgEl) { imgEl.src = data; imgEl.style.display = ''; }
        if (initialEl) { initialEl.textContent = ''; initialEl.style.display = 'none'; }
        if (container) container.classList.remove('bg-gray-100');
    };
    reader.readAsDataURL(file);
}

function triggerEditProfileUpload() {
    const input = document.getElementById('edit_profile_file');
    if (input) input.click();
}



function viewProfileFromModal() {
    const userId = document.getElementById('edit_user_id') ? document.getElementById('edit_user_id').value : null;
    if (!userId) { showToast('No user selected', 'error'); return; }
    const url = window.location.origin + window.location.pathname + '?view_user=' + encodeURIComponent(userId);
    window.open(url, '_blank');
}

function updateRowAfterEdit(tr, user) {
    if (!tr || !user) return;
    // merge existing dataset user with new values
    let existing = {};
    try { existing = tr.dataset.user ? JSON.parse(tr.dataset.user) : {}; } catch(e) { existing = {}; }
    const merged = Object.assign({}, existing, user);

    function renderUserCell(u) {
        const initial = (u.full_name || '').trim().charAt(0) || '?';
        const avatarHtml = u.avatar ? `<img src="${escapeHtml(u.avatar)}" alt="avatar" class="w-full h-full object-cover" />` : `<span>${escapeHtml(initial)}</span>`;
        return `
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-full bg-gray-200 overflow-hidden flex items-center justify-center text-xs font-medium text-gray-700">${avatarHtml}</div>
                <span class="font-medium text-gray-800">${escapeHtml(u.full_name || '-')}</span>
            </div>
        `;
    }

    // helper to escape HTML in template strings
    function escapeHtml(s){ if(!s) return ''; return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#39;'); }

    // update cells
    const nameCell = tr.querySelector('td:nth-child(1)');
    if (nameCell) nameCell.innerHTML = renderUserCell(merged);
    const lastActiveCell = tr.querySelector('td:nth-child(2)');
    if (lastActiveCell) lastActiveCell.textContent = merged.last_login ? formatDateShort(merged.last_login) : (merged.last_login === null ? '-' : (lastActiveCell.textContent || '-'));
    const createdCell = tr.querySelector('td:nth-child(3)');
    if (createdCell) createdCell.textContent = merged.created_at ? formatDateShortDate(merged.created_at) : (createdCell.textContent || '-');

    // save merged dataset
    tr.dataset.user = JSON.stringify(merged);
}

// Create and append a user row using the same layout as server-rendered rows
function addUserRow(user) {
    if (!user || !user.id) return;
    function escapeHtml(s){ if(!s) return ''; return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#39;'); }
    function formatDateShort(d){ try { return new Date(d).toLocaleString('en-US', { year:'numeric', month:'short', day:'2-digit', hour:'2-digit', minute:'2-digit' }); } catch(e){ return '-'; } }
    function formatDateShortDate(d){ try { return new Date(d).toLocaleDateString('en-US', { year:'numeric', month:'short', day:'2-digit' }); } catch(e){ return '-'; } }
    function renderNameCell(u){
        const initial = (u.full_name||'').trim().charAt(0) || '?';
        const avatarHtml = u.avatar ? `<img src="${escapeHtml(u.avatar)}" alt="avatar" class="w-full h-full object-cover" />` : `<span>${escapeHtml(initial)}</span>`;
        return `
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-full bg-gray-200 overflow-hidden flex items-center justify-center text-xs font-medium text-gray-700">${avatarHtml}</div>
                <span class="font-medium text-gray-800">${escapeHtml(u.full_name||'')}</span>
            </div>
        `;
    }
    const tr = document.createElement('tr');
    tr.dataset.user = JSON.stringify(user);
    tr.innerHTML = `
        <td class="px-4 py-3">${renderNameCell(user)}</td>
        <td class="px-4 py-3 text-gray-500">${escapeHtml(user.email||'')}</td>
        <td class="px-4 py-3">${escapeHtml(user.role||'')}</td>
        <td class="px-4 py-3"><span class="status-badge px-2 py-1 rounded" style="background-color:#d1fae5;color:#065f46;">${escapeHtml(user.status||'')}</span></td>
        <td class="px-4 py-3">${user.created_at ? formatDateShortDate(user.created_at) : '-'}</td>
        <td class="px-4 py-3 flex items-center justify-between">
            <span>${user.last_login ? formatDateShort(user.last_login) : '-'}</span>
            <div class="relative inline-block text-left ml-3">
                <button type="button" onclick="toggleRowMenu(this, ${parseInt(user.id)})" class="p-1 rounded hover:bg-gray-100 text-sm" aria-expanded="false" aria-haspopup="true" title="Actions">
                    <i class="fa fa-ellipsis-v text-sm"></i>
                </button>
                <div class="hidden origin-top-right absolute right-0 mt-2 w-40 bg-white border rounded shadow-lg z-60" role="menu">
                    <div class="py-1">
                        <button type="button" class="w-full text-left px-3 py-2 text-sm text-gray-700 hover:bg-gray-100" onclick="editUser(this, ${parseInt(user.id)})"><i class="fa fa-pencil mr-2"></i> Edit details</button>
                        ${user.status === 'Deactivated' 
                            ? '<button type="button" class="w-full text-left px-3 py-2 text-sm text-green-600 hover:bg-green-50" onclick="unsuspendUser(' + parseInt(user.id) + ')"><i class="fa fa-check-circle mr-2"></i> Activate user</button>'
                            : '<button type="button" class="w-full text-left px-3 py-2 text-sm text-red-600 hover:bg-red-50" onclick="suspendUser(' + parseInt(user.id) + ')"><i class="fa fa-ban mr-2"></i> Deactivate user</button>'}
-                        <button type="button" class="w-full text-left px-3 py-2 text-sm text-red-600 hover:bg-red-50" onclick="deleteUser(null, ${parseInt(user.id)})"><i class="fa fa-trash mr-2"></i> Delete user</button>
                    </div>
                </div>
            </div>
        </td>
    `;
    const tbody = document.querySelector('#usersTable tbody');
    if (tbody) tbody.insertAdjacentElement('afterbegin', tr);
    // update users count
    const countEl = document.getElementById('usersCount');
    if (countEl) countEl.textContent = parseInt(countEl.textContent || '0') + 1;
    // Update pagination
    allRows = Array.from(document.querySelectorAll('#usersTable tbody tr'));
    updatePagination();
}

function formatDateShort(d){ try { return new Date(d).toLocaleString('en-US', { year:'numeric', month:'short', day:'2-digit', hour:'2-digit', minute:'2-digit' }); } catch(e){ return '-'; } }
function formatDateShortDate(d){ try { return new Date(d).toLocaleDateString('en-US', { year:'numeric', month:'short', day:'2-digit' }); } catch(e){ return '-'; } }

async function suspendUser(userId) {
    const modal = document.getElementById('suspendConfirmModal');
    if (!modal) {
        // fallback to browser confirm
        if (!confirm('Are you sure you want to deactivate this user? Their status will be set to Deactivated.')) return;
        try {
            const form = new URLSearchParams();
            form.append('ajax','1'); form.append('action','suspend_user'); form.append('user_id', String(userId));
            const res = await fetch(location.href, { method: 'POST', body: form });
            const j = await res.json();
            if (j.success) {
                updateUserStatus(userId, 'Deactivated');
                showTopAlert('success', 'User Deactivated');
            } else {
                showTopAlert('error', j.message || 'Deactivate failed');
            }
        } catch (err) { console.error(err); alert('Request failed'); }
        return;
    }

    // store data on modal and show it
    modal.dataset.userId = String(userId);
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

async function unsuspendUser(userId) {
    if (!confirm('Are you sure you want to activate this user? Their status will be set to Active.')) return;
    try {
        const form = new URLSearchParams();
        form.append('ajax','1'); form.append('action','unsuspend_user'); form.append('user_id', String(userId));
        const res = await fetch(location.href, { method: 'POST', body: form });
        const j = await res.json();
        if (j.success) {
            updateUserStatus(userId, 'Active');
            showTopAlert('success', 'User Activated');
        } else {
            showTopAlert('error', j.message || 'Activate failed');
        }
    } catch (err) { console.error(err); alert('Request failed'); }
}

function updateUserStatus(userId, newStatus) {
    const tr = getRowById(userId);
    if (tr) {
        // Update status cell
        const statusCell = tr.querySelector('td:nth-child(4)');
        if (statusCell) {
            if (newStatus === 'Active') {
                statusCell.innerHTML = '<span class="px-2 py-1 text-xs rounded" style="background-color:#d1fae5;color:#065f46;">Active</span>';
            } else if (newStatus === 'Deactivated') {
                statusCell.innerHTML = '<span class="px-2 py-1 text-xs rounded" style="background-color:#fee2e2;color:#991b1b;">Deactivated</span>';
            } else if (newStatus === 'Inactive') {
                statusCell.innerHTML = '<span class="px-2 py-1 text-xs rounded" style="background-color:#f3f4f6;color:#4b5563;">Inactive</span>';
            }
        }
        
        // Update user data in dataset
        try {
            const userData = JSON.parse(tr.dataset.user);
            userData.status = newStatus;
            tr.dataset.user = JSON.stringify(userData);
        } catch(e) { console.error('Failed to update user data:', e); }
        
        // Update the three-dot menu button
        const menu = tr.querySelector('div[role="menu"]');
        if (menu) {
            const menuContent = menu.querySelector('.py-1');
            if (menuContent) {
                const buttons = menuContent.querySelectorAll('button');
                // Find and replace the suspend/unsuspend button (second button, now becomes last since delete is removed)
                if (buttons.length >= 2) {
                    const oldBtn = buttons[1];
                    const newBtn = document.createElement('button');
                    newBtn.type = 'button';
                    newBtn.className = 'w-full text-left px-3 py-2 text-sm';
                    
                    if (newStatus === 'Deactivated') {
                        newBtn.className += ' text-green-600 hover:bg-green-50';
                        newBtn.onclick = () => unsuspendUser(userId);
                        newBtn.innerHTML = '<i class="fa fa-check-circle mr-2"></i> Activate user';
                    } else {
                        newBtn.className += ' text-red-600 hover:bg-red-50';
                        newBtn.onclick = () => suspendUser(userId);
                        newBtn.innerHTML = '<i class="fa fa-ban mr-2"></i> Deactivate user';
                    }
                    
                    oldBtn.replaceWith(newBtn);
                }
            }
        }
    }
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
                const countEl = document.getElementById('usersCount');
                if (countEl) countEl.textContent = Math.max(0, parseInt(countEl.textContent || '0') - 1);
                showTopAlert('success', 'User Deleted');
            } else {
                showTopAlert('error', j.message || 'Delete failed');
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

function closeSuspendModal() {
    const modal = document.getElementById('suspendConfirmModal');
    if (!modal) return;
    modal.classList.add('hidden');
    modal.classList.remove('flex');
    delete modal.dataset.userId;
}

async function confirmSuspend() {
    const modal = document.getElementById('suspendConfirmModal');
    if (!modal) return;
    const userId = modal.dataset.userId;
    if (!userId) { closeSuspendModal(); return; }
    const btn = document.getElementById('confirmSuspendBtn');
    if (btn) btn.disabled = true;
    try {
        const form = new URLSearchParams();
        form.append('ajax','1'); form.append('action','suspend_user'); form.append('user_id', String(userId));
        const res = await fetch(location.href, { method: 'POST', body: form });
        const j = await res.json();
        if (j.success) {
            updateUserStatus(userId, 'Deactivated');
            showTopAlert('success', 'User Deactivated');
        } else {
            showTopAlert('error', j.message || 'Deactivate failed');
        }
    } catch (err) { console.error(err); alert('Request failed'); }
    if (btn) btn.disabled = false;
    closeSuspendModal();
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
        // visual feedback while deleting
        const trPreview = getRowById(userId);
        if (trPreview) trPreview.style.opacity = '0.5';
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
            let removed = false;
            const tr = getRowById(userId);
            if (tr) {
                tr.style.transition = 'opacity .25s, height .25s, margin .25s';
                tr.style.opacity = '0';
                setTimeout(()=>{ try { tr.remove(); removed = true; } catch(e){} }, 250);
            }
            // Update count immediately
            const countEl = document.getElementById('usersCount');
            if (countEl) countEl.textContent = Math.max(0, parseInt(countEl.textContent || '0') - 1);
            closeDeleteModal();
            showTopAlert('success', 'User Deleted!');
            // Update pagination
            allRows = Array.from(document.querySelectorAll('#usersTable tbody tr'));
            updatePagination();
            // Fallback: if row not gone after short delay, force reload to reflect removal
            setTimeout(()=>{ if (!removed) { const still = getRowById(userId); if (still) { location.reload(); } } }, 600);
        } else {
            // restore preview style if failure
            const tr = getRowById(userId);
            if (tr) tr.style.opacity = '';
            showTopAlert('error', j.message || 'Delete failed');
        }
    } catch (err) {
        console.error(err);
        const tr = getRowById(userId); if (tr) tr.style.opacity = '';
        showTopAlert('error', 'Request failed');
    }
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

// Add User modal handlers
function openAddUserModal() {
    const m = document.getElementById('addUserModal');
    if (!m) return;
    // reset preview state and show
    _resetAddModalState();
    m.classList.remove('hidden');
    m.classList.add('flex');
}

// Reset add-modal previews and form state
function _resetAddModalState() {
    const img = document.getElementById('add_profile_photo_img');
    const smallImg = document.getElementById('add_profile_small_img');
    const container = document.getElementById('add_profile_photo_container');
    const initial = document.getElementById('add_profile_initial');
    const input = document.getElementById('add_profile_file');
    if (img) { img.src = ''; img.style.display = 'none'; }
    if (smallImg) { smallImg.src = ''; smallImg.style.display = 'none'; }
    if (initial) { initial.textContent = 'Add photo'; initial.style.display = ''; }
    if (container) { container.style.background = ''; }
    if (input) input.value = '';
}

function closeAddUserModal() {
    const m = document.getElementById('addUserModal');
    if (!m) return;
    m.classList.add('hidden');
    m.classList.remove('flex');
    // reset form
    const f = document.getElementById('addUserForm');
    if (f) f.reset();
    _resetAddModalState();
}


async function submitAddUser(e) {
    e && e.preventDefault();
    const btn = document.getElementById('addUserSaveBtn');
    if (btn) btn.disabled = true;
    try {
        const form = document.getElementById('addUserForm');
        const first = (form.querySelector('input[name="first_name"]')||{}).value || '';
        const last = (form.querySelector('input[name="last_name"]')||{}).value || '';
        const pwd = (form.querySelector('input[name="password"]')||{}).value || '';
        if (!pwd.trim()) {
            showTopAlert('error', 'Password is required');
            if (btn) btn.disabled = false;
            return false;
        }

        const full = (first + ' ' + last).trim();
        const fd = new FormData(form);
        fd.append('full_name', full);
        fd.append('ajax','1'); fd.append('action','create_user');
        const addFile = document.getElementById('add_profile_file');
        if (addFile && addFile.files && addFile.files[0]) {
            fd.append('profile_photo', addFile.files[0]);
        }

        const res = await fetch(location.href, { method: 'POST', body: fd });
        const raw = await res.text();
        console.log('create_user raw response:', raw);
        let j;
        try { j = JSON.parse(raw); } catch (err) { console.warn('create_user: server returned non-JSON:', raw); j = { success: res.ok, message: raw || (res.ok ? 'OK (no JSON)' : 'Server error') }; }

        console.log('create_user parsed JSON:', j);
        if (j && j.success) {
            if (j.user) {
                const addImg = document.getElementById('add_profile_photo_img');
                if ((!j.user.avatar || !j.user.avatar.length) && addImg && addImg.src) { try { j.user.avatar = addImg.src; } catch(e){} }
                addUserRow(j.user);
                closeAddUserModal();
                showTopAlert('success', j.message || 'User Created!');
            } else {
                showTopAlert('success', j.message || 'User Created! — reloading list');
                setTimeout(()=> location.reload(), 600);
            }
        } else {
            showTopAlert('error', j.message || 'Create failed');
        }
    } catch (err) {
        console.error('create user error', err);
        showTopAlert('error', 'Request failed. See console.');
    }
    if (btn) btn.disabled = false;
    return false;
}

// Preview selected profile file in the add modal (client-side only)
function previewAddProfileFile(e) {
    const input = e.target;
    if (!input || !input.files || !input.files[0]) return;
    const file = input.files[0];
    const reader = new FileReader();
    reader.onload = function(ev) {
        const data = ev.target.result;
        const imgLarge = document.getElementById('add_profile_photo_img');
        const imgSmall = document.getElementById('add_profile_small_img');
        const initial = document.getElementById('add_profile_initial');
        const container = document.getElementById('add_profile_photo_container');
        if (imgLarge) { imgLarge.src = data; imgLarge.style.display = ''; }
        if (imgSmall) { imgSmall.src = data; imgSmall.style.display = ''; }
        if (initial) { initial.textContent = ''; initial.style.display = 'none'; }
        if (container) container.style.background = '';
    };
    reader.readAsDataURL(file);
}

function triggerAddProfileUpload() {
    const input = document.getElementById('add_profile_file');
    if (input) input.click();
}

// Toggle the per-row action menu (three-dots). This creates a floating clone appended to body
function toggleRowMenu(btn, userId) {
    // If a floating menu for this user already exists, toggle it off
    const existingForUser = document.querySelector('.floating-row-menu[data-user-id="' + userId + '"]');
    if (existingForUser) { try { existingForUser.remove(); } catch(e){} return; }

    // remove any other floating menus first
    document.querySelectorAll('.floating-row-menu').forEach(c => c.remove());

    const menuTemplate = btn && btn.parentElement ? btn.parentElement.querySelector('div[role="menu"]') : null;
    if (!menuTemplate) return;

    // clone the menu so it can float above overflowing parents
    const clone = menuTemplate.cloneNode(true);
    clone.classList.remove('hidden');
    clone.classList.add('floating-row-menu');
    clone.setAttribute('data-user-id', String(userId));
    clone.style.position = 'fixed';
    clone.style.zIndex = '9999';
    clone.style.minWidth = '150px';
    clone.style.visibility = 'hidden';

    document.body.appendChild(clone);

    // measure and position near the button
    const rect = btn.getBoundingClientRect();
    // ensure it's briefly in DOM so offsetWidth/Height are available
    const cw = clone.offsetWidth || 180;
    const ch = clone.offsetHeight || 120;
    let left = rect.right - cw;
    if (left < 8) left = rect.left;
    if (left + cw > window.innerWidth - 8) left = Math.max(8, window.innerWidth - cw - 8);
    let top = rect.bottom + 6;
    if (top + ch > window.innerHeight - 8) top = rect.top - ch - 6;
    if (top < 8) top = 8;

    clone.style.left = left + 'px';
    clone.style.top = top + 'px';
    clone.style.visibility = '';

    // stop propagation on clicks inside clone so outside-click handler won't immediately close it
    clone.addEventListener('click', function(ev){ ev.stopPropagation(); });

    // when a menu action is clicked, also remove the clone (menu action handlers are inline and will run)
    Array.from(clone.querySelectorAll('button')).forEach(b=> b.addEventListener('click', ()=> { try{ clone.remove(); } catch(e){} }));
}

// Close menus when clicking outside
document.addEventListener('click', function(e){
    const el = e.target;
    // if click inside a menu or its button, ignore
    if (el.closest && el.closest('[role="menu"]')) return;
    if (el.closest && el.closest('.relative.inline-block.text-left')) return;
    // hide any in-place menus
    document.querySelectorAll('div[role="menu"]').forEach(d => { if (!d.classList.contains('hidden')) d.classList.add('hidden'); });
    // remove any floating clones
    document.querySelectorAll('.floating-row-menu').forEach(c => c.remove());
});

// Filter menu toggle
function toggleFilterMenu() {
    const menu = document.getElementById('filterMenu');
    if (!menu) return;
    
    if (menu.classList.contains('hidden')) {
        menu.classList.remove('hidden');
        // Close menu when clicking outside
        setTimeout(() => {
            document.addEventListener('click', closeFilterMenuOutside);
        }, 0);
    } else {
        menu.classList.add('hidden');
        document.removeEventListener('click', closeFilterMenuOutside);
    }
}

function closeFilterMenuOutside(e) {
    const menu = document.getElementById('filterMenu');
    const btn = document.getElementById('filterBtn');
    
    if (menu && btn && !menu.contains(e.target) && !btn.contains(e.target)) {
        menu.classList.add('hidden');
        document.removeEventListener('click', closeFilterMenuOutside);
    }
}

// Apply filters
function applyFilters() {
    currentPage = 1; // Reset to first page when filtering
    updatePagination();
    
    // Update filter button to show active state
    const roleFilter = document.getElementById('roleFilter').value;
    const statusFilter = document.getElementById('statusFilter').value;
    const filterBtn = document.getElementById('filterBtn');
    
    if (roleFilter || statusFilter) {
        filterBtn.classList.add('bg-blue-100', 'border-blue-300');
        filterBtn.classList.remove('bg-gray-100', 'border-gray-300');
    } else {
        filterBtn.classList.remove('bg-blue-100', 'border-blue-300');
        filterBtn.classList.add('bg-gray-100', 'border-gray-300');
    }
}

// Clear all filters
function clearFilters() {
    document.getElementById('roleFilter').value = '';
    document.getElementById('statusFilter').value = '';
    document.getElementById('userSearch').value = '';
    
    const filterBtn = document.getElementById('filterBtn');
    filterBtn.classList.remove('bg-blue-100', 'border-blue-300');
    filterBtn.classList.add('bg-gray-100', 'border-gray-300');
    
    applyFilters();
}

// Update getVisibleRows to include filter logic
function getVisibleRows() {
    const searchQuery = (document.getElementById('userSearch').value || '').toLowerCase().trim();
    const roleFilter = (document.getElementById('roleFilter')?.value || '').toLowerCase();
    const statusFilter = (document.getElementById('statusFilter')?.value || '').toLowerCase();
    
    return allRows.filter(row => {
        const user = row.dataset.user ? JSON.parse(row.dataset.user) : null;
        if (!user) return true;
        
        // Search filter
        const searchText = (user.full_name + ' ' + user.email + ' ' + (user.role || '')).toLowerCase();
        const matchesSearch = !searchQuery || searchText.indexOf(searchQuery) !== -1;
        
        // Role filter
        const userRole = (user.role || '').toLowerCase();
        const matchesRole = !roleFilter || userRole === roleFilter || 
                           (roleFilter === 'laboratorystaff' && userRole === 'laboratory staff');
        
        // Status filter
        const userStatus = (user.status || 'active').toLowerCase();
        const matchesStatus = !statusFilter || userStatus === statusFilter;
        
        return matchesSearch && matchesRole && matchesStatus;
    });
}

// ...existing code...
</script>
