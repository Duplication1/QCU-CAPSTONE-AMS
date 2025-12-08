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
        
        // Normalize role for database storage
        if ($role === 'LaboratoryStaff') {
            $role = 'Laboratory Staff';
        }
        
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
        // Fix AUTO_INCREMENT issue if it exists (one-time fix) - only if needed
        $checkAutoInc = $conn->query("SHOW CREATE TABLE users");
        if ($checkAutoInc) {
            $createTableRow = $checkAutoInc->fetch_assoc();
            if ($createTableRow && strpos($createTableRow['Create Table'], 'AUTO_INCREMENT') === false) {
                // Get the current maximum ID to set AUTO_INCREMENT properly
                $maxIdResult = $conn->query("SELECT MAX(id) as max_id FROM users");
                $maxId = 1;
                if ($maxIdResult && $row = $maxIdResult->fetch_assoc()) {
                    $maxId = max(1, intval($row['max_id']) + 1);
                }
                $conn->query("ALTER TABLE users MODIFY id INT(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT = $maxId");
            }
        }
        
        $id_number = trim($_POST['id_number'] ?? '');
        $full = trim($_POST['full_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $role = trim($_POST['role'] ?? '');
        $password = trim($_POST['password'] ?? '');

        // require password now
        if ($full === '' || $email === '' || $role === '' || $password === '') {
            echo json_encode(['success'=>false,'message'=>'Missing required fields (password is required)']); exit;
        }

        // Validate role is one of the allowed values (support both formats)
        $validRoles = ['Student', 'Faculty', 'Technician', 'Laboratory Staff', 'LaboratoryStaff', 'Administrator'];
        if (!in_array($role, $validRoles)) {
            echo json_encode(['success'=>false,'message'=>'Invalid role selected. Please choose a valid role.']); exit;
        }

        // Normalize role for database storage (convert LaboratoryStaff to Laboratory Staff)
        if ($role === 'LaboratoryStaff') {
            $role = 'Laboratory Staff';
        }

        // Enhanced duplicate check - handle empty id_number case
        if (!empty($id_number)) {
            $chk = $conn->prepare("SELECT id FROM users WHERE email = ? OR id_number = ? LIMIT 1");
            $chk->bind_param('ss', $email, $id_number);
        } else {
            $chk = $conn->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
            $chk->bind_param('s', $email);
        }
        $chk->execute();
        $rchk = $chk->get_result()->fetch_assoc();
        $chk->close();
        if ($rchk) {
            echo json_encode(['success'=>false,'message'=>'A user with this email or ID number already exists']); exit;
        }

        // password required - hash it
        $hash = password_hash($password, PASSWORD_DEFAULT);

        // Handle NULL id_number if empty to avoid constraint issues
        $id_number_param = !empty($id_number) ? $id_number : NULL;
        
        // Fix AUTO_INCREMENT issue: explicitly specify NULL for id to force auto-increment
        $stmt = $conn->prepare("INSERT INTO users (id, id_number, full_name, email, role, password, status, created_at) VALUES (NULL, ?, ?, ?, ?, ?, 'Active', NOW())");
        $stmt->bind_param('sssss', $id_number_param, $full, $email, $role, $hash);
        $ok = $stmt->execute();
        if (!$ok) {
            $err = $stmt->error;
            $stmt->close();
            // More detailed error message for debugging
            if (strpos($err, 'Duplicate entry') !== false) {
                echo json_encode(['success'=>false,'message'=>'This ID number or email is already in use. Please use a different one.']); 
            } else {
                echo json_encode(['success'=>false,'message'=>'Database error: '.$err]); 
            }
            exit;
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
            'id_number' => $id_number_param,
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
<style>
html, body {
    height: 100vh;
    overflow: hidden;
}
#app-container {
    height: 100vh;
    display: flex;
    flex-direction: column;
    overflow: hidden;
}
main {
    flex: 1;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    padding: 0.5rem;
    background-color: #f9fafb;
}
</style>

        <!-- Main Content -->
        <main class="flex-1 flex flex-col overflow-hidden">
            <div class="flex-1 flex flex-col overflow-hidden">  

                <div class="flex items-center justify-between px-3 py-2 bg-white rounded shadow-sm border border-gray-200 mb-2 gap-2">
                    <div>
                        <h3 class="text-sm font-semibold text-gray-800">All users <span id="usersCount" class="text-xs text-gray-500">(<?php echo count($users); ?>)</span></h3>
                    </div>
                    <div class="flex items-center gap-2">
<div class="relative flex items-center gap-2">
  <input id="userSearch" oninput="filterUsers()" type="search" placeholder="Search users..."
    class="w-48 pl-8 pr-3 py-1.5 text-xs border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-[#1E3A8A] focus:border-[#1E3A8A] transition" />
  <i class="fas fa-search absolute left-2.5 text-gray-400 text-xs pointer-events-none" style="top: 50%; transform: translateY(-50%);"></i>
  
  <!-- Filter Button -->
  <div class="relative">
    <button id="filterBtn" onclick="toggleFilterMenu()" 
      class="px-2 py-1.5 bg-gray-100 border border-gray-300 rounded hover:bg-gray-200 focus:outline-none focus:ring-1 focus:ring-[#1E3A8A] transition"
      title="Filter users">
      <i class="fas fa-filter text-gray-600 text-xs"></i>
    </button>
    
    <!-- Filter Dropdown Menu -->
    <div id="filterMenu" class="hidden absolute right-0 mt-1 w-48 bg-white border border-gray-200 rounded shadow-lg z-50">
      <div class="p-2">
        <h4 class="text-xs font-semibold text-gray-700 mb-2">Filter Users</h4>
        
        <!-- Role Filter -->
        <div class="mb-2">
          <label class="block text-[10px] font-medium text-gray-600 mb-1">Role</label>
          <select id="roleFilter" onchange="applyFilters()" class="w-full px-2 py-1 text-xs border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-[#1E3A8A]">
            <option value="">All Roles</option>
            <option value="Administrator">Administrator</option>
            <option value="Technician">Technician</option>
            <option value="LaboratoryStaff">Laboratory Staff</option>
            <option value="Student">Student</option>
          </select>
        </div>
        
        <!-- Status Filter -->
        <div class="mb-2">
          <label class="block text-[10px] font-medium text-gray-600 mb-1">Status</label>
          <select id="statusFilter" onchange="applyFilters()" class="w-full px-2 py-1 text-xs border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-[#1E3A8A]">
            <option value="">All Status</option>
            <option value="Active">Active</option>
            <option value="Inactive">Inactive</option>
            <option value="Deactivated">Deactivated</option>
          </select>
        </div>
        
        <!-- Clear Filters Button -->
        <button onclick="clearFilters()" class="w-full px-2 py-1 text-xs bg-gray-100 hover:bg-gray-200 rounded transition">
          Clear Filters
        </button>
      </div>
    </div>
  </div>
</div>
                        <button id="bulkImportBtn" onclick="openBulkImportModal()"
  class="flex items-center gap-1.5 px-3 py-1.5 bg-green-600 text-white text-xs font-semibold rounded shadow-sm hover:bg-green-700 focus:outline-none focus:ring-1 focus:ring-green-600">
  <i class="fas fa-file-upload text-xs"></i>
  Import Users
</button>
                        <button id="addUserBtn" onclick="openAddUserModal()"
  class="flex items-center gap-1.5 px-3 py-1.5 bg-[#1E3A8A] text-white text-xs font-semibold rounded shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-1 focus:ring-[#1E3A8A]">
  <i class="fas fa-user-plus text-xs"></i>
  Add User
</button>
                    </div>
                </div>

                <div class="flex-1 overflow-auto bg-white rounded shadow-sm border border-gray-200">
 <table id="usersTable" class="min-w-full divide-y divide-gray-200">
  <thead class="bg-[#1E3A8A] text-white sticky top-0 z-10">
    <tr>
      <th class="px-3 py-2 text-left text-[10px] font-medium uppercase">Name</th>
      <th class="px-3 py-2 text-left text-[10px] font-medium uppercase">Email</th>
      <th class="px-3 py-2 text-left text-[10px] font-medium uppercase">Role</th>
      <th class="px-3 py-2 text-left text-[10px] font-medium uppercase">Status</th>
      <th class="px-3 py-2 text-left text-[10px] font-medium uppercase">Joined</th>
      <th class="px-3 py-2 text-left text-[10px] font-medium uppercase">Last Active</th>
    </tr>
  </thead>
  <tbody class="bg-white divide-y divide-gray-100">
    <?php foreach ($users as $u): ?>
    <?php $avatarWeb = ams_find_avatar_web($u['id']); $udata = $u; $udata['avatar'] = $avatarWeb; $initial = strtoupper(substr(trim($u['full_name']), 0, 1)); ?>
    <tr class="hover:bg-blue-50 transition-colors" data-user='<?php echo json_encode($udata, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT); ?>'>
      <td class="px-3 py-2">
        <div class="flex items-center gap-2">
          <div class="w-7 h-7 rounded-full bg-gray-200 overflow-hidden flex items-center justify-center text-[10px] font-medium text-gray-700">
            <?php if ($avatarWeb): ?>
              <img src="<?php echo htmlspecialchars($avatarWeb); ?>" alt="avatar" class="w-full h-full object-cover" />
            <?php else: ?>
              <span><?php echo htmlspecialchars($initial ?: '?'); ?></span>
            <?php endif; ?>
          </div>
          <span class="font-medium text-xs text-gray-800"><?php echo htmlspecialchars($u['full_name']); ?></span>
        </div>
      </td>
      <td class="px-3 py-2 text-xs text-gray-500"><?php echo htmlspecialchars($u['email']); ?></td>
      <td class="px-3 py-2 text-xs"><?php echo htmlspecialchars($u['role'] === 'LaboratoryStaff' ? 'Laboratory Staff' : $u['role']); ?></td>
      <td class="px-3 py-2">
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
        <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[10px] font-medium <?php echo $statusClass; ?>">
          <?php echo htmlspecialchars($status); ?>
        </span>
      </td>
      <td class="px-3 py-2 text-xs"><?php echo !empty($u['created_at']) ? date('M d, Y', strtotime($u['created_at'])) : '-'; ?></td>
      <td class="px-3 py-2 flex items-center justify-between">
        <span class="text-xs"><?php echo !empty($u['last_login']) ? date('M d, Y H:i', strtotime($u['last_login'])) : '-'; ?></span>
        <div class="relative inline-block text-left ml-2">
          <button type="button" onclick="toggleRowMenu(this, <?php echo (int)$u['id']; ?>)" class="p-1 rounded hover:bg-gray-100 text-xs" aria-expanded="false" aria-haspopup="true" title="Actions">
            <i class="fa fa-ellipsis-v text-xs"></i>
          </button>
          <div class="hidden origin-top-right absolute right-0 mt-1 w-36 bg-white border rounded shadow-lg z-60" role="menu">
            <div class="py-1">
              <button type="button" class="w-full text-left px-2 py-1.5 text-xs text-gray-700 hover:bg-gray-100" onclick="editUser(this, <?php echo (int)$u['id']; ?>)">
                <i class="fa fa-pencil mr-1 text-xs"></i> Edit details
              </button>
              <?php if ($u['status'] === 'Deactivated' || $u['status'] === ''): ?>
              <button type="button" class="w-full text-left px-2 py-1.5 text-xs text-green-600 hover:bg-green-50" onclick="unsuspendUser(<?php echo (int)$u['id']; ?>)">
                <i class="fa fa-check-circle mr-1 text-xs"></i> Activate user
              </button>
              <?php else: ?>
              <button type="button" class="w-full text-left px-2 py-1.5 text-xs text-red-600 hover:bg-red-50" onclick="suspendUser(<?php echo (int)$u['id']; ?>)">
                <i class="fa fa-ban mr-1 text-xs"></i> Deactivate user
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
                <div class="px-3 py-2 flex items-center justify-center bg-white rounded shadow-sm border border-gray-200 mt-2">
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

<!-- Unsuspend/Activate Confirmation Modal -->
<div id="unsuspendConfirmModal" class="hidden fixed inset-0 z-50 flex items-center justify-center">
    <div class="absolute inset-0 bg-black bg-opacity-40 backdrop-blur-sm" style="background-color: rgba(0,0,0,0.4);" onclick="closeUnsuspendModal()"></div>
        <div class="relative bg-white rounded-lg shadow-lg max-w-md w-11/12 p-6">
            <div class="flex flex-col items-center text-center">
                <div class="mb-4">
                    <img src="../../assets/images/8376179.png" alt="warning" class="w-16 h-16 object-contain mx-auto" />
                </div>
                <h3 class="text-xl font-semibold mb-2">Are you sure?</h3>
                <p class="text-sm text-gray-600 mb-6">Do you really want to activate this user? Their status will be set to Active.</p>
                <div class="flex gap-3 flex-col sm:flex-row w-full justify-center mt-2">
                    <button id="cancelUnsuspendBtn" type="button" onclick="closeUnsuspendModal()" class="px-4 py-2 rounded bg-gray-200 text-gray-800 w-full sm:w-auto inline-block" style="min-width:120px;">No</button>
                    <button id="confirmUnsuspendBtn" type="button" onclick="confirmUnsuspend()" class="px-4 py-2 rounded w-full sm:w-auto inline-block" style="min-width:120px;background-color:#10b981;color:#ffffff;border:none;box-shadow:0 1px 2px rgba(0,0,0,0.1);">Yes</button>
                </div>
            </div>
        </div>
</div>

<!-- Bulk Import Modal -->
<div id="bulkImportModal" class="hidden fixed inset-0 z-50 flex items-center justify-center">
    <div class="absolute inset-0 bg-black bg-opacity-40 backdrop-blur-sm" onclick="closeBulkImportModal()"></div>
    <div class="relative bg-white rounded-lg shadow-2xl max-w-4xl w-11/12 md:w-3/4 p-6 z-50 max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h3 class="text-xl font-semibold text-gray-800">Import Users from CSV</h3>
                <p class="text-sm text-gray-600 mt-1">Upload a CSV file to add multiple users at once</p>
            </div>
            <button onclick="closeBulkImportModal()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>

        <!-- CSV Format Instructions -->
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4">
            <h4 class="text-sm font-semibold text-blue-900 mb-2">
                <i class="fas fa-info-circle mr-2"></i>CSV Format Requirements
            </h4>
            <ul class="text-xs text-blue-800 space-y-1 ml-6 list-disc">
                <li>First row must contain headers: <code class="bg-blue-100 px-1 rounded">id_number, full_name, email, role, password</code></li>
                <li>Valid roles: Administrator, Technician, LaboratoryStaff, Student</li>
                <li>Email addresses must be unique</li>
                <li>Password is required for each user</li>
            </ul>
            <button onclick="downloadTemplate()" class="mt-3 text-xs text-blue-600 hover:text-blue-800 font-medium">
                <i class="fas fa-download mr-1"></i>Download CSV Template
            </button>
        </div>

        <!-- File Upload Area -->
        <form id="bulkImportForm" onsubmit="return submitBulkImport(event)">
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Select CSV File</label>
                <div class="flex items-center gap-3">
                    <input type="file" id="csvFile" name="csv_file" accept=".csv" required
                        class="block w-full text-sm text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 focus:outline-none focus:ring-2 focus:ring-[#1E3A8A]"
                        onchange="previewCSV(event)" />
                </div>
            </div>

            <!-- Preview Area -->
            <div id="csvPreview" class="hidden mb-4">
                <h4 class="text-sm font-semibold text-gray-800 mb-2">Preview (First 5 rows)</h4>
                <div class="overflow-x-auto border rounded-lg">
                    <table class="min-w-full divide-y divide-gray-200 text-xs">
                        <thead class="bg-gray-50">
                            <tr id="csvPreviewHeader"></tr>
                        </thead>
                        <tbody id="csvPreviewBody" class="bg-white divide-y divide-gray-200"></tbody>
                    </table>
                </div>
                <p id="csvRowCount" class="text-xs text-gray-600 mt-2"></p>
            </div>

            <!-- Error Display -->
            <div id="importErrors" class="hidden mb-4 bg-red-50 border border-red-200 rounded-lg p-3">
                <h4 class="text-sm font-semibold text-red-900 mb-2">
                    <i class="fas fa-exclamation-triangle mr-2"></i>Import Errors
                </h4>
                <ul id="importErrorList" class="text-xs text-red-800 space-y-1 ml-6 list-disc"></ul>
            </div>

            <!-- Success Display -->
            <div id="importSuccess" class="hidden mb-4 bg-green-50 border border-green-200 rounded-lg p-3">
                <h4 class="text-sm font-semibold text-green-900">
                    <i class="fas fa-check-circle mr-2"></i><span id="importSuccessMessage"></span>
                </h4>
            </div>

            <!-- Action Buttons -->
            <div class="flex items-center justify-end gap-3">
                <button type="button" onclick="closeBulkImportModal()" class="px-4 py-2 text-sm rounded bg-gray-100 hover:bg-gray-200">
                    Cancel
                </button>
                <button type="submit" id="bulkImportBtn" class="px-4 py-2 text-sm rounded bg-[#1E3A8A] text-white hover:bg-blue-700">
                    <i class="fas fa-upload mr-2"></i>Import Users
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Add User Modal -->
<div id="addUserModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/60 backdrop-blur-sm z-40" onclick="closeAddUserModal()"></div>
    <div class="relative bg-white rounded-2xl shadow-2xl max-w-3xl w-full z-50">
        <!-- Header -->
        <div class="bg-white px-6 py-5 rounded-t-2xl border-b border-gray-200">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-xl font-bold text-gray-900">Create New User</h3>
                    <p class="text-sm text-gray-600 mt-1">Add a new account to the system</p>
                </div>
                <button type="button" onclick="closeAddUserModal()" class="text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg p-2 transition-all">
                    <i class="fa-solid fa-times text-xl"></i>
                </button>
            </div>
        </div>

        <!-- Form Content -->
        <form id="addUserForm" onsubmit="return submitAddUser(event)" class="p-6">
            <!-- Personal Information Section -->
            <div class="mb-5">
                <h4 class="text-sm font-semibold text-gray-800 mb-3 flex items-center gap-2">
                    <i class="fa-solid fa-user text-[#1E3A8A] text-sm"></i>
                    Personal Information
                </h4>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="md:col-span-2">
                        <label class="block text-xs font-medium text-gray-700 mb-1.5">
                            Full Name <span class="text-red-500">*</span>
                        </label>
                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <input id="add_first_name" name="first_name" placeholder="First Name" 
                                    class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#1E3A8A] focus:border-transparent transition-all" required />
                            </div>
                            <div>
                                <input id="add_last_name" name="last_name" placeholder="Last Name" 
                                    class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#1E3A8A] focus:border-transparent transition-all" required />
                            </div>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1.5">
                            ID Number
                        </label>
                        <div>
                            <input id="add_id_number" name="id_number" type="text" inputmode="numeric" pattern="[0-9\-]*" placeholder="e.g. 22-0311"
                                class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#1E3A8A] focus:border-transparent transition-all" 
                                oninput="validateIdNumberInput(this, 'add_id_error')" />
                        </div>
                        <span id="add_id_error" class="text-xs text-red-600 mt-1 hidden flex items-center gap-1">
                            <i class="fa-solid fa-exclamation-circle"></i>Use numbers only
                        </span>
                    </div>
                    
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1.5">
                            Email Address <span class="text-red-500">*</span>
                        </label>
                        <div>
                            <input id="add_email" name="email" type="email" placeholder="user@example.com"
                                class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#1E3A8A] focus:border-transparent transition-all" required />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Account Information Section -->
            <div class="mb-5">
                <h4 class="text-sm font-semibold text-gray-800 mb-3 flex items-center gap-2">
                    <i class="fa-solid fa-shield-halved text-[#1E3A8A] text-sm"></i>
                    Account Information
                </h4>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1.5">
                            Role <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <select id="add_role" name="role" 
                                class="w-full px-3 pr-8 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#1E3A8A] focus:border-transparent transition-all appearance-none bg-white" required>
                                <option value="">Select a role...</option>
                                <option value="Administrator">Administrator</option>
                                <option value="Technician">Technician</option>
                                <option value="LaboratoryStaff">Laboratory Staff</option>
                                <option value="Student">Student</option>
                            </select>
                            <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                <i class="fa-solid fa-chevron-down text-gray-400 text-xs"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Security Section -->
            <div class="mb-5">
                <h4 class="text-sm font-semibold text-gray-800 mb-3 flex items-center gap-2">
                    <i class="fa-solid fa-lock text-[#1E3A8A] text-sm"></i>
                    Security
                </h4>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1.5">
                            Password <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <input id="add_password" name="password" type="password" placeholder="Enter password"
                                class="w-full px-3 pr-10 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#1E3A8A] focus:border-transparent transition-all" 
                                required oninput="validateAddPassword()" />
                            <button type="button" onclick="togglePasswordVisibility('add_password', 'toggle_add_password')" 
                                class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600">
                                <i id="toggle_add_password" class="fa-solid fa-eye"></i>
                            </button>
                        </div>
                        <div id="add_password_requirements" class="mt-2 space-y-1 bg-gray-50 p-2 rounded">
                            <div id="add_req_length" class="text-xs text-gray-500 flex items-center gap-1.5">
                                <i class="fa-solid fa-circle text-[5px]"></i>
                                <span>At least 8 characters</span>
                            </div>
                            <div id="add_req_capital" class="text-xs text-gray-500 flex items-center gap-1.5">
                                <i class="fa-solid fa-circle text-[5px]"></i>
                                <span>Contains capital letter (A-Z)</span>
                            </div>
                            <div id="add_req_special" class="text-xs text-gray-500 flex items-center gap-1.5">
                                <i class="fa-solid fa-circle text-[5px]"></i>
                                <span>Contains special character (!@#$%^&*)</span>
                            </div>
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1.5">
                            Confirm Password <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <input id="add_confirm_password" name="confirm_password" type="password" placeholder="Re-enter password"
                                class="w-full px-3 pr-10 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#1E3A8A] focus:border-transparent transition-all" 
                                required oninput="validateAddPassword()" />
                            <button type="button" onclick="togglePasswordVisibility('add_confirm_password', 'toggle_add_confirm_password')" 
                                class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600">
                                <i id="toggle_add_confirm_password" class="fa-solid fa-eye"></i>
                            </button>
                        </div>
                        <div class="mt-2">
                            <div id="add_password_match_error" class="text-xs text-red-600 bg-red-50 px-2 py-1.5 rounded hidden flex items-center gap-1.5">
                                <i class="fa-solid fa-exclamation-circle"></i>
                                <span>Passwords do not match</span>
                            </div>
                            <div id="add_password_match_success" class="text-xs text-green-600 bg-green-50 px-2 py-1.5 rounded hidden flex items-center gap-1.5">
                                <i class="fa-solid fa-check-circle"></i>
                                <span>Passwords match</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex items-center justify-end gap-3 pt-5 border-t border-gray-200 mt-6">
                <button type="button" onclick="closeAddUserModal()" 
                    class="px-6 py-2.5 text-sm rounded-lg border-2 border-gray-300 text-gray-700 font-semibold hover:bg-gray-50 hover:border-gray-400 transition-all flex items-center gap-2">
                    <i class="fa-solid fa-times"></i>
                    Cancel
                </button>
                <button type="submit" id="addUserSaveBtn" 
                    class="px-6 py-2.5 text-sm rounded-lg bg-[#1E3A8A] text-white font-semibold hover:bg-[#2563EB] hover:shadow-xl transform hover:-translate-y-0.5 transition-all flex items-center gap-2 shadow-lg">
                    <i class="fa-solid fa-user-plus"></i>
                    Create User
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Toast notification -->
<div id="globalToast" class="fixed bottom-6 right-6 z-60 hidden">
    <div id="globalToastInner" class="px-4 py-2 rounded shadow text-sm"></div>
</div>

<script>
// Define all onclick-called functions globally as placeholders - they'll be properly defined after DOM loads
// This prevents "function is not defined" errors for inline onclick handlers
window.toggleRowMenu = function(btn, userId) {
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
};

// Helper function
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

function showToast(message, type = 'success', duration = 3000) {
    const container = document.getElementById('globalToast');
    const inner = document.getElementById('globalToastInner');
    if (!container || !inner) return;
    inner.textContent = message;
    inner.className = 'px-4 py-2 rounded shadow text-sm ' + (type === 'success' ? 'bg-green-500 text-white' : 'bg-red-500 text-white');
    container.classList.remove('hidden');
    setTimeout(() => { container.classList.add('hidden'); }, duration);
}

// Define full implementations immediately
window.editUser = function(btn, userId) {
    const tr = getRowById(userId);
    const user = tr && tr.dataset.user ? JSON.parse(tr.dataset.user) : null;
    if (!user) { showToast('User not found', 'error'); return; }

    const modal = document.getElementById('editUserModal');
    if (!modal) { showToast('Edit modal not found', 'error'); return; }

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
};

window.closeUserModal = function() {
    const modal = document.getElementById('userModal');
    if (!modal) return;
    modal.classList.add('hidden');
    modal.classList.remove('flex');
    delete modal.dataset.userId;
};

window.closeEditModal = function() {
    const modal = document.getElementById('editUserModal');
    if (!modal) return;
    modal.classList.add('hidden');
    modal.classList.remove('flex');
};

window.editUserFromModal = function() {
    const modal = document.getElementById('userModal');
    if (!modal || !modal.dataset.userId) return;
    window.editUser(null, modal.dataset.userId);
};

window.triggerEditProfileUpload = function() {
    const input = document.getElementById('edit_profile_file');
    if (input) input.click();
};

window.suspendUser = function(userId) {
    const modal = document.getElementById('suspendConfirmModal');
    if (!modal) {
        alert('Deactivate user feature loading...');
        return;
    }
    modal.dataset.userId = String(userId);
    modal.classList.remove('hidden');
    modal.classList.add('flex');
};

window.unsuspendUser = function(userId) {
    const modal = document.getElementById('unsuspendConfirmModal');
    if (!modal) {
        alert('Activate user feature loading...');
        return;
    }
    modal.dataset.userId = String(userId);
    modal.classList.remove('hidden');
    modal.classList.add('flex');
};

window.closeUnsuspendModal = function() {
    const modal = document.getElementById('unsuspendConfirmModal');
    if (!modal) return;
    modal.classList.add('hidden');
    modal.classList.remove('flex');
    delete modal.dataset.userId;
};

window.confirmUnsuspend = async function() {
    const modal = document.getElementById('unsuspendConfirmModal');
    if (!modal) return;
    const userId = modal.dataset.userId;
    if (!userId) { window.closeUnsuspendModal(); return; }
    const btn = document.getElementById('confirmUnsuspendBtn');
    if (btn) btn.disabled = true;
    try {
        const form = new URLSearchParams();
        form.append('ajax','1'); form.append('action','unsuspend_user'); form.append('user_id', String(userId));
        const res = await fetch(location.href, { method: 'POST', body: form });
        const j = await res.json();
        if (j.success) {
            location.reload();
        } else {
            alert(j.message || 'Activate failed');
        }
    } catch (err) { 
        console.error(err); 
        alert('Request failed');
    }
    if (btn) btn.disabled = false;
    window.closeUnsuspendModal();
};

window.deleteUser = function(btn, userId) {
    const modal = document.getElementById('deleteConfirmModal');
    if (!modal) {
        if (confirm('Are you sure you want to delete this user?')) {
            // Trigger deletion via AJAX
            const form = new URLSearchParams();
            form.append('ajax','1'); form.append('action','delete_user'); form.append('user_id', String(userId));
            fetch(location.href, { method: 'POST', body: form })
                .then(res => res.json())
                .then(j => { if (j.success) location.reload(); else alert(j.message || 'Delete failed'); })
                .catch(err => { console.error(err); alert('Request failed'); });
        }
        return;
    }
    modal.dataset.userId = String(userId);
    modal.classList.remove('hidden');
    modal.classList.add('flex');
};

window.closeDeleteModal = function() {
    const modal = document.getElementById('deleteConfirmModal');
    if (!modal) return;
    modal.classList.add('hidden');
    modal.classList.remove('flex');
    delete modal.dataset.userId;
};

window.confirmDelete = async function() {
    const modal = document.getElementById('deleteConfirmModal');
    if (!modal || !modal.dataset.userId) return;
    const userId = modal.dataset.userId;
    const btn = document.getElementById('confirmDeleteBtn');
    if (btn) btn.disabled = true;
    try {
        const form = new URLSearchParams();
        form.append('ajax','1'); form.append('action','delete_user'); form.append('user_id', String(userId));
        const res = await fetch(location.href, { method: 'POST', body: form });
        const j = await res.json();
        if (j.success) {
            location.reload();
        } else {
            alert(j.message || 'Delete failed');
        }
    } catch (err) {
        console.error(err);
        alert('Request failed');
    }
    if (btn) btn.disabled = false;
};

window.closeSuspendModal = function() {
    const modal = document.getElementById('suspendConfirmModal');
    if (!modal) return;
    modal.classList.add('hidden');
    modal.classList.remove('flex');
    delete modal.dataset.userId;
};

window.confirmSuspend = async function() {
    const modal = document.getElementById('suspendConfirmModal');
    if (!modal) return;
    const userId = modal.dataset.userId;
    if (!userId) { window.closeSuspendModal(); return; }
    const btn = document.getElementById('confirmSuspendBtn');
    if (btn) btn.disabled = true;
    try {
        const form = new URLSearchParams();
        form.append('ajax','1'); form.append('action','suspend_user'); form.append('user_id', String(userId));
        const res = await fetch(location.href, { method: 'POST', body: form });
        const j = await res.json();
        if (j.success) {
            location.reload();
        } else {
            alert(j.message || 'Deactivate failed');
        }
    } catch (err) { 
        console.error(err); 
        alert('Request failed');
    }
    if (btn) btn.disabled = false;
    window.closeSuspendModal();
};

window.toggleFilterMenu = function() {
    const menu = document.getElementById('filterMenu');
    if (!menu) return;
    if (menu.classList.contains('hidden')) {
        menu.classList.remove('hidden');
    } else {
        menu.classList.add('hidden');
    }
};

window.clearFilters = function() {
    const roleFilter = document.getElementById('roleFilter');
    const statusFilter = document.getElementById('statusFilter');
    const userSearch = document.getElementById('userSearch');
    if (roleFilter) roleFilter.value = '';
    if (statusFilter) statusFilter.value = '';
    if (userSearch) userSearch.value = '';
    if (typeof filterUsers === 'function') filterUsers();
};

window.openBulkImportModal = function() {
    const modal = document.getElementById('bulkImportModal');
    if (!modal) return;
    modal.classList.remove('hidden');
};

window.openAddUserModal = function() {
    const modal = document.getElementById('addUserModal');
    if (!modal) return;
    modal.classList.remove('hidden');
    modal.classList.add('flex');
};

window.submitEditForm = async function(e) {
    e && e.preventDefault();
    console.log('submitEditForm called');
    const btn = document.getElementById('editSaveBtn');
    if (!btn) { console.error('editSaveBtn not found'); if (typeof showToast === 'function') showToast('Request failed', 'error'); return false; }
    btn.disabled = true;
    const userIdEl = document.getElementById('edit_user_id');
    const firstEl = document.getElementById('edit_first_name');
    const lastEl = document.getElementById('edit_last_name');
    const emailEl = document.getElementById('edit_email');
    const roleEl = document.getElementById('edit_role');
    const idNumberEl = document.getElementById('edit_id_number');
    if (!userIdEl || !firstEl || !lastEl || !emailEl || !roleEl) {
        console.error('Edit form elements missing');
        if (typeof showToast === 'function') showToast('Request failed', 'error');
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
    if (!full || !email) {
        if (typeof showToast === 'function') showToast('Please fill required fields', 'error');
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
        const raw = await res.text();
        let j;
        try {
            j = JSON.parse(raw);
        } catch (parseErr) {
            console.warn('edit_user: server returned non-JSON response:', raw);
            j = { success: res.ok, message: raw || (res.ok ? 'OK (no JSON)' : 'Server error') };
        }
        console.log('edit_user response', j);
        if (j && j.success) {
            window.closeEditModal();
            if (typeof showToast === 'function') {
                showToast((j.user?.full_name || full || 'User') + ' details updated successfully!', 'success');
            }
            setTimeout(() => location.reload(), 800);
        } else {
            const msg = (j && j.message) ? j.message : ('Update failed' + (raw ? (': ' + raw) : ''));
            if (typeof showToast === 'function') {
                showToast(msg, 'error');
            } else {
                alert('Error: ' + msg);
            }
        }
    } catch (err) {
        console.error('submitEditForm error', err);
        alert('Request failed: ' + err.message);
    }
    btn.disabled = false;
    return false;
};

window.filterUsers = function() {
    // If updatePagination exists (loaded later), use it for proper pagination support
    if (typeof updatePagination === 'function') {
        updatePagination();
        return;
    }
    
    // Fallback: simple show/hide without pagination
    const searchQuery = (document.getElementById('userSearch')?.value || '').toLowerCase().trim();
    const roleFilter = (document.getElementById('roleFilter')?.value || '').toLowerCase();
    const statusFilter = (document.getElementById('statusFilter')?.value || '').toLowerCase();
    
    const tbody = document.querySelector('#usersTable tbody');
    if (!tbody) return;
    
    const rows = tbody.querySelectorAll('tr');
    
    rows.forEach(row => {
        try {
            const user = row.dataset.user ? JSON.parse(row.dataset.user) : null;
            if (!user) {
                row.style.display = '';
                return;
            }
            
            // Search filter
            const searchText = (user.full_name + ' ' + user.email + ' ' + (user.role || '')).toLowerCase();
            const matchesSearch = !searchQuery || searchText.indexOf(searchQuery) !== -1;
            
            // Role filter
            const userRole = (user.role || '').toLowerCase();
            const matchesRole = !roleFilter || userRole === roleFilter;
            
            // Status filter
            const userStatus = (user.status || 'active').toLowerCase();
            const matchesStatus = !statusFilter || userStatus === statusFilter;
            
            row.style.display = (matchesSearch && matchesRole && matchesStatus) ? '' : 'none';
        } catch (e) {
            row.style.display = '';
        }
    });
};

window.applyFilters = function() {
    // Reset to first page when filtering
    if (typeof currentPage !== 'undefined') {
        currentPage = 1;
    }
    
    // Call filterUsers to apply the filters
    window.filterUsers();
    window.filterUsers();
    
    const roleFilter = document.getElementById('roleFilter');
    const statusFilter = document.getElementById('statusFilter');
    const filterBtn = document.getElementById('filterBtn');
    
    if (filterBtn && roleFilter && statusFilter) {
        if (roleFilter.value || statusFilter.value) {
            filterBtn.classList.add('bg-blue-100', 'border-blue-300');
            filterBtn.classList.remove('bg-gray-100', 'border-gray-300');
        } else {
            filterBtn.classList.remove('bg-blue-100', 'border-blue-300');
            filterBtn.classList.add('bg-gray-100', 'border-gray-300');
        }
    }
};

window.submitAddUser = async function(e) {
    e && e.preventDefault();
    const btn = document.getElementById('addUserSaveBtn');
    if (btn) btn.disabled = true;
    try {
        const form = document.getElementById('addUserForm');
        const first = (form.querySelector('input[name="first_name"]')||{}).value || '';
        const last = (form.querySelector('input[name="last_name"]')||{}).value || '';
        const pwd = (form.querySelector('input[name="password"]')||{}).value || '';
        if (!pwd.trim()) {
            if (typeof showToast === 'function') {
                showToast('Password is required', 'error');
            } else {
                alert('Password is required');
            }
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
        try { j = JSON.parse(raw); } catch (err) { 
            console.warn('create_user: server returned non-JSON:', raw); 
            j = { success: res.ok, message: raw || (res.ok ? 'OK (no JSON)' : 'Server error') }; 
        }

        console.log('create_user parsed JSON:', j);
        if (j && j.success) {
            if (typeof closeAddUserModal === 'function') closeAddUserModal();
            if (typeof showToast === 'function') {
                showToast(j.message || 'User created successfully!', 'success');
            } else {
                alert(j.message || 'User created successfully!');
            }
            setTimeout(() => location.reload(), 800);
        } else {
            if (typeof showToast === 'function') {
                showToast(j.message || 'Create failed', 'error');
            } else {
                alert('Error: ' + (j.message || 'Create failed'));
            }
        }
    } catch (err) {
        console.error('create user error', err);
        if (typeof showToast === 'function') {
            showToast('Request failed: ' + err.message, 'error');
        } else {
            alert('Request failed: ' + err.message);
        }
    }
    if (btn) btn.disabled = false;
    return false;
};

window.closeAddUserModal = function() {
    const modal = document.getElementById('addUserModal');
    if (!modal) return;
    modal.classList.add('hidden');
    modal.classList.remove('flex');
    // Reset form
    const form = document.getElementById('addUserForm');
    if (form) form.reset();
};

window.closeBulkImportModal = function() {
    const modal = document.getElementById('bulkImportModal');
    if (!modal) return;
    modal.classList.add('hidden');
    // Reset form
    const form = document.getElementById('bulkImportForm');
    if (form) form.reset();
    document.getElementById('csvPreview')?.classList.add('hidden');
    document.getElementById('importErrors')?.classList.add('hidden');
    document.getElementById('importSuccess')?.classList.add('hidden');
};

window.downloadTemplate = function() {
    const csvContent = "id_number,full_name,email,role,password\n" +
                      "2021-00001,John Doe,john.doe@example.com,Student,password123\n" +
                      "2021-00002,Jane Smith,jane.smith@example.com,Student,password123\n" +
                      "EMP-001,Bob Johnson,bob.johnson@example.com,Technician,password123";
    
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    const url = URL.createObjectURL(blob);
    link.setAttribute('href', url);
    link.setAttribute('download', 'users_import_template.csv');
    link.style.visibility = 'hidden';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
};

window.previewCSV = function(event) {
    const file = event.target.files[0];
    if (!file) return;
    
    const reader = new FileReader();
    reader.onload = function(e) {
        const text = e.target.result;
        const rows = text.split('\n').map(row => row.trim()).filter(row => row);
        
        if (rows.length < 2) {
            if (typeof showToast === 'function') {
                showToast('CSV file must contain at least a header row and one data row', 'error');
            }
            return;
        }
        
        const headers = rows[0].split(',').map(h => h.trim());
        const preview = document.getElementById('csvPreview');
        const headerRow = document.getElementById('csvPreviewHeader');
        const bodyTable = document.getElementById('csvPreviewBody');
        const rowCount = document.getElementById('csvRowCount');
        
        if (!headerRow || !bodyTable || !rowCount) return;
        
        // Show headers
        headerRow.innerHTML = headers.map(h => `<th class="px-3 py-2 text-left text-xs font-medium text-gray-700 bg-gray-50">${h}</th>`).join('');
        
        // Show first 5 data rows
        bodyTable.innerHTML = '';
        const dataRows = rows.slice(1, 6);
        dataRows.forEach((row, idx) => {
            const cells = row.split(',').map(c => c.trim());
            const tr = document.createElement('tr');
            tr.innerHTML = cells.map(cell => `<td class="px-3 py-2 text-xs text-gray-600">${cell}</td>`).join('');
            bodyTable.appendChild(tr);
        });
        
        rowCount.textContent = `Total rows: ${rows.length - 1} users`;
        if (preview) preview.classList.remove('hidden');
    };
    
    reader.readAsText(file);
};

window.submitBulkImport = async function(event) {
    event.preventDefault();
    
    const fileInput = document.getElementById('csvFile');
    const file = fileInput?.files[0];
    
    if (!file) {
        if (typeof showToast === 'function') {
            showToast('Please select a CSV file', 'error');
        }
        return false;
    }
    
    const importBtn = document.getElementById('bulkImportBtn');
    const originalText = importBtn?.innerHTML || 'Import Users';
    if (importBtn) {
        importBtn.disabled = true;
        importBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Importing...';
    }
    
    // Hide previous results
    document.getElementById('importErrors')?.classList.add('hidden');
    document.getElementById('importSuccess')?.classList.add('hidden');
    
    try {
        const text = await file.text();
        const rows = text.split('\n').map(row => row.trim()).filter(row => row);
        
        if (rows.length < 2) {
            throw new Error('CSV file must contain at least a header row and one data row');
        }
        
        const headers = rows[0].split(',').map(h => h.trim().toLowerCase());
        const requiredHeaders = ['id_number', 'full_name', 'email', 'role', 'password'];
        
        // Validate headers
        const missingHeaders = requiredHeaders.filter(h => !headers.includes(h));
        if (missingHeaders.length > 0) {
            throw new Error(`Missing required columns: ${missingHeaders.join(', ')}`);
        }
        
        const users = [];
        const errors = [];
        
        // Parse data rows
        for (let i = 1; i < rows.length; i++) {
            const cells = rows[i].split(',').map(c => c.trim());
            const user = {};
            
            headers.forEach((header, idx) => {
                user[header] = cells[idx] || '';
            });
            
            // Validate row
            if (!user.full_name || !user.email || !user.role || !user.password) {
                errors.push(`Row ${i + 1}: Missing required fields`);
                continue;
            }
            
            if (!['Administrator', 'Technician', 'LaboratoryStaff', 'Student'].includes(user.role)) {
                errors.push(`Row ${i + 1}: Invalid role "${user.role}"`);
                continue;
            }
            
            users.push(user);
        }
        
        if (errors.length > 0 && users.length === 0) {
            const errorList = document.getElementById('importErrorList');
            if (errorList) {
                errorList.innerHTML = errors.map(e => `<li>${e}</li>`).join('');
            }
            document.getElementById('importErrors')?.classList.remove('hidden');
            if (importBtn) {
                importBtn.disabled = false;
                importBtn.innerHTML = originalText;
            }
            return false;
        }
        
        // Import users
        let successCount = 0;
        let failCount = 0;
        
        for (const user of users) {
            try {
                const formData = new FormData();
                formData.append('ajax', '1');
                formData.append('action', 'create_user');
                formData.append('id_number', user.id_number);
                formData.append('full_name', user.full_name);
                formData.append('email', user.email);
                formData.append('role', user.role);
                formData.append('password', user.password);
                
                const response = await fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    successCount++;
                } else {
                    errors.push(`${user.email}: ${result.message || 'Failed to create'}`);
                    failCount++;
                }
            } catch (err) {
                errors.push(`${user.email}: ${err.message}`);
                failCount++;
            }
        }
        
        // Show results
        if (successCount > 0) {
            const successMsg = document.getElementById('importSuccessMessage');
            if (successMsg) {
                successMsg.textContent = 
                    `Successfully imported ${successCount} user(s)${failCount > 0 ? `. ${failCount} failed.` : '.'}`;
            }
            document.getElementById('importSuccess')?.classList.remove('hidden');
        }
        
        if (errors.length > 0) {
            const errorList = document.getElementById('importErrorList');
            if (errorList) {
                errorList.innerHTML = errors.slice(0, 10).map(e => `<li>${e}</li>`).join('');
                if (errors.length > 10) {
                    errorList.innerHTML += `<li>...and ${errors.length - 10} more errors</li>`;
                }
            }
            document.getElementById('importErrors')?.classList.remove('hidden');
        }
        
        if (successCount > 0) {
            if (typeof showToast === 'function') {
                showToast(`Successfully imported ${successCount} users!`, 'success');
            }
            if (errors.length === 0) {
                setTimeout(() => {
                    window.closeBulkImportModal();
                    location.reload();
                }, 1500);
            } else {
                setTimeout(() => {
                    location.reload();
                }, 3000);
            }
        }
        
    } catch (err) {
        if (typeof showToast === 'function') {
            showToast(err.message, 'error');
        } else {
            alert('Error: ' + err.message);
        }
    } finally {
        if (importBtn) {
            importBtn.disabled = false;
            importBtn.innerHTML = originalText;
        }
    }
    
    return false;
};

// Other placeholders - these don't need early implementation
</script>

<?php include '../components/layout_footer.php'; ?>
<script>
// Pagination variables
let currentPage = 1;
let pageSize = 10;
let allRows = [];

// Define helper functions immediately (not in DOMContentLoaded)
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

// Override placeholder with real implementation for editUser
window.editUser = function(btn, userId) {
    const tr = getRowById(userId);
    const user = tr && tr.dataset.user ? JSON.parse(tr.dataset.user) : null;
    if (!user) { showToast('User not found', 'error'); return; }

    const modal = document.getElementById('editUserModal');
    if (!modal) { showToast('Edit modal not found', 'error'); return; }

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
};

window.closeUserModal = function() {
    const modal = document.getElementById('userModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
    delete modal.dataset.userId;
};

// Initialize pagination on page load
document.addEventListener('DOMContentLoaded', function() {
    allRows = Array.from(document.querySelectorAll('#usersTable tbody tr'));
    console.log('Total users loaded:', allRows.length);
    currentPage = 1;
    updatePagination();
    
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
        const matchesRole = !roleFilter || userRole === roleFilter;
        
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
    
    console.log('Pagination:', { totalRows, pageSize, totalPages, currentPage });
    
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
    if (!container) {
        console.error('pageNumbers container not found!');
        return;
    }
    container.innerHTML = '';
    
    console.log('Rendering page numbers, totalPages:', totalPages);
    
    if (totalPages <= 1) {
        // Still show current page indicator even if only 1 page
        const info = document.createElement('span');
        info.className = 'text-sm text-gray-500';
        info.textContent = `Page 1 of 1`;
        container.appendChild(info);
        return;
    }
    
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

// Show a top-of-page alert (now uses fixed-position chip notification)
function showTopAlert(type, msg, duration = 5000) {
    // Sanitize / normalize message (avoid dumping raw JSON)
    if (msg && typeof msg === 'string' && msg.trim().startsWith('{') && msg.indexOf('"success"') !== -1) {
        // Fallback generic text if a JSON blob was passed accidentally
        msg = type === 'success' ? 'Operation completed successfully.' : 'An error occurred.';
    }
    // Use the fixed-position chip notification system
    showChip(msg, type, 'admin-alert-chip', duration);
}

function filterUsers() {
    currentPage = 1; // Reset to first page when searching
    updatePagination();
}

window.closeEditModal = function() {
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
};

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

window.editUserFromModal = function() {
    const modal = document.getElementById('userModal');
    if (!modal || !modal.dataset.userId) return;
    editUser(null, modal.dataset.userId);
};

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

window.triggerEditProfileUpload = function() {
    const input = document.getElementById('edit_profile_file');
    if (input) input.click();
};



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
        <td class="px-4 py-3">${escapeHtml(user.role === 'LaboratoryStaff' ? 'Laboratory Staff' : (user.role||''))}</td>
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

window.suspendUser = async function(userId) {
    const modal = document.getElementById('suspendConfirmModal');
    if (!modal) {
        // fallback to async confirm modal
        const confirmed = await showConfirmModal({
            title: 'Deactivate User',
            message: 'Are you sure you want to deactivate this user? Their status will be set to Deactivated.',
            confirmText: 'Deactivate',
            cancelText: 'Cancel',
            confirmColor: 'bg-orange-600 hover:bg-orange-700',
            type: 'warning'
        });
        
        if (!confirmed) return;
        
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
        } catch (err) { console.error(err); showNotification('Request failed', 'error'); }
        return;
    }

    // store data on modal and show it
    modal.dataset.userId = String(userId);
    modal.classList.remove('hidden');
    modal.classList.add('flex');
};

window.unsuspendUser = async function(userId) {
    const confirmed = await showConfirmModal({
        title: 'Activate User',
        message: 'Are you sure you want to activate this user? Their status will be set to Active.',
        confirmText: 'Activate',
        cancelText: 'Cancel',
        confirmColor: 'bg-green-600 hover:bg-green-700',
        type: 'success'
    });
    
    if (!confirmed) return;
    
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
    } catch (err) { console.error(err); showNotification('Request failed', 'error'); }
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

window.deleteUser = async function(btn, userId) {
    // open the simple Yes/No confirmation modal
    const modal = document.getElementById('deleteConfirmModal');
    if (!modal) {
        // fallback to async confirm modal
        const confirmed = await showConfirmModal({
            title: 'Delete User',
            message: 'Are you sure you want to delete this user? This action cannot be undone.',
            confirmText: 'Delete',
            cancelText: 'Cancel',
            confirmColor: 'bg-red-600 hover:bg-red-700',
            type: 'danger'
        });
        
        if (!confirmed) return;
        
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
        } catch (err) { console.error(err); showNotification('Request failed', 'error'); }
        btn.disabled = false;
        return;
    }

    // store data on modal and show it
    modal.dataset.userId = String(userId);
    // show modal
    modal.classList.remove('hidden');
    modal.classList.add('flex');
};

window.closeDeleteModal = function() {
    const modal = document.getElementById('deleteConfirmModal');
    if (!modal) return;
    modal.classList.add('hidden');
    modal.classList.remove('flex');
    delete modal.dataset.userId;
};

window.closeSuspendModal = function() {
    const modal = document.getElementById('suspendConfirmModal');
    if (!modal) return;
    modal.classList.add('hidden');
    modal.classList.remove('flex');
    delete modal.dataset.userId;
};

window.confirmSuspend = async function() {
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
    } catch (err) { console.error(err); showNotification('Request failed', 'error'); }
    if (btn) btn.disabled = false;
    closeSuspendModal();
};

window.confirmDelete = async function() {
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
};

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
    } catch (err) { console.error(err); showNotification('Request failed', 'error'); }
    btn.disabled = false;
}

// Add User modal handlers
window.openAddUserModal = function() {
    const m = document.getElementById('addUserModal');
    if (!m) return;
    // reset preview state and show
    _resetAddModalState();
    m.classList.remove('hidden');
    m.classList.add('flex');
};

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
        const confirmPwd = document.getElementById('add_confirm_password').value || '';
        
        // Validate password is not empty
        if (!pwd.trim()) {
            showTopAlert('error', 'Password is required');
            if (btn) btn.disabled = false;
            return false;
        }
        
        // Validate password length
        if (pwd.length < 8) {
            showTopAlert('error', 'Password must be at least 8 characters long');
            if (btn) btn.disabled = false;
            return false;
        }
        
        // Validate capital letter
        const capitalRegex = /[A-Z]/;
        if (!capitalRegex.test(pwd)) {
            showTopAlert('error', 'Password must contain at least one capital letter (A-Z)');
            if (btn) btn.disabled = false;
            return false;
        }
        
        // Validate special character
        const specialCharRegex = /[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/;
        if (!specialCharRegex.test(pwd)) {
            showTopAlert('error', 'Password must contain at least one special character (!@#$%^&*)');
            if (btn) btn.disabled = false;
            return false;
        }
        
        // Validate passwords match
        if (pwd !== confirmPwd) {
            showTopAlert('error', 'Passwords do not match');
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

// Validate password requirements for add user modal
function validateAddPassword() {
    const password = document.getElementById('add_password').value;
    const confirmPassword = document.getElementById('add_confirm_password').value;
    
    // Check length requirement
    const reqLength = document.getElementById('add_req_length');
    if (password.length >= 8) {
        reqLength.className = 'text-xs text-green-600 flex items-center gap-1.5';
        reqLength.innerHTML = '<i class="fa-solid fa-check-circle"></i><span>At least 8 characters</span>';
    } else {
        reqLength.className = 'text-xs text-gray-500 flex items-center gap-1.5';
        reqLength.innerHTML = '<i class="fa-solid fa-circle text-[5px]"></i><span>At least 8 characters</span>';
    }
    
    // Check capital letter requirement
    const capitalRegex = /[A-Z]/;
    const reqCapital = document.getElementById('add_req_capital');
    if (capitalRegex.test(password)) {
        reqCapital.className = 'text-xs text-green-600 flex items-center gap-1.5';
        reqCapital.innerHTML = '<i class="fa-solid fa-check-circle"></i><span>Contains capital letter (A-Z)</span>';
    } else {
        reqCapital.className = 'text-xs text-gray-500 flex items-center gap-1.5';
        reqCapital.innerHTML = '<i class="fa-solid fa-circle text-[5px]"></i><span>Contains capital letter (A-Z)</span>';
    }
    
    // Check special character requirement
    const specialCharRegex = /[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/;
    const reqSpecial = document.getElementById('add_req_special');
    if (specialCharRegex.test(password)) {
        reqSpecial.className = 'text-xs text-green-600 flex items-center gap-1.5';
        reqSpecial.innerHTML = '<i class="fa-solid fa-check-circle"></i><span>Contains special character (!@#$%^&*)</span>';
    } else {
        reqSpecial.className = 'text-xs text-gray-500 flex items-center gap-1.5';
        reqSpecial.innerHTML = '<i class="fa-solid fa-circle text-[5px]"></i><span>Contains special character (!@#$%^&*)</span>';
    }
    
    // Check if passwords match (only if confirm password field has value)
    const matchError = document.getElementById('add_password_match_error');
    const matchSuccess = document.getElementById('add_password_match_success');
    
    if (confirmPassword.length > 0) {
        if (password === confirmPassword) {
            matchError.classList.add('hidden');
            matchSuccess.classList.remove('hidden');
        } else {
            matchError.classList.remove('hidden');
            matchSuccess.classList.add('hidden');
        }
    } else {
        matchError.classList.add('hidden');
        matchSuccess.classList.add('hidden');
    }
}

// Toggle password visibility
function togglePasswordVisibility(inputId, iconId) {
    const input = document.getElementById(inputId);
    const icon = document.getElementById(iconId);
    
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}

// Filter menu toggle
window.toggleFilterMenu = function() {
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
};

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
window.clearFilters = function() {
    document.getElementById('roleFilter').value = '';
    document.getElementById('statusFilter').value = '';
    document.getElementById('userSearch').value = '';
    
    const filterBtn = document.getElementById('filterBtn');
    filterBtn.classList.remove('bg-blue-100', 'border-blue-300');
    filterBtn.classList.add('bg-gray-100', 'border-gray-300');
    
    applyFilters();
};

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
        const matchesRole = !roleFilter || userRole === roleFilter;
        
        // Status filter
        const userStatus = (user.status || 'active').toLowerCase();
        const matchesStatus = !statusFilter || userStatus === statusFilter;
        
        return matchesSearch && matchesRole && matchesStatus;
    });
}

// Bulk Import Functions
window.openBulkImportModal = function() {
    document.getElementById('bulkImportModal').classList.remove('hidden');
    document.getElementById('csvFile').value = '';
    document.getElementById('csvPreview').classList.add('hidden');
    document.getElementById('importErrors').classList.add('hidden');
    document.getElementById('importSuccess').classList.add('hidden');
};

function closeBulkImportModal() {
    document.getElementById('bulkImportModal').classList.add('hidden');
}

function downloadTemplate() {
    const csvContent = "id_number,full_name,email,role,password\n" +
                      "2021-00001,John Doe,john.doe@example.com,Student,password123\n" +
                      "2021-00002,Jane Smith,jane.smith@example.com,Student,password123\n" +
                      "EMP-001,Bob Johnson,bob.johnson@example.com,Technician,password123";
    
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    const url = URL.createObjectURL(blob);
    link.setAttribute('href', url);
    link.setAttribute('download', 'users_import_template.csv');
    link.style.visibility = 'hidden';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

function previewCSV(event) {
    const file = event.target.files[0];
    if (!file) return;
    
    const reader = new FileReader();
    reader.onload = function(e) {
        const text = e.target.result;
        const rows = text.split('\n').map(row => row.trim()).filter(row => row);
        
        if (rows.length < 2) {
            showToast('CSV file must contain at least a header row and one data row', 'error');
            return;
        }
        
        const headers = rows[0].split(',').map(h => h.trim());
        const preview = document.getElementById('csvPreview');
        const headerRow = document.getElementById('csvPreviewHeader');
        const bodyTable = document.getElementById('csvPreviewBody');
        const rowCount = document.getElementById('csvRowCount');
        
        // Show headers
        headerRow.innerHTML = headers.map(h => `<th class="px-3 py-2 text-left text-xs font-medium text-gray-700 bg-gray-50">${h}</th>`).join('');
        
        // Show first 5 data rows
        bodyTable.innerHTML = '';
        const dataRows = rows.slice(1, 6);
        dataRows.forEach((row, idx) => {
            const cells = row.split(',').map(c => c.trim());
            const tr = document.createElement('tr');
            tr.innerHTML = cells.map(cell => `<td class="px-3 py-2 text-xs text-gray-600">${cell}</td>`).join('');
            bodyTable.appendChild(tr);
        });
        
        rowCount.textContent = `Total rows: ${rows.length - 1} users`;
        preview.classList.remove('hidden');
    };
    
    reader.readAsText(file);
}

async function submitBulkImport(event) {
    event.preventDefault();
    
    const fileInput = document.getElementById('csvFile');
    const file = fileInput.files[0];
    
    if (!file) {
        showToast('Please select a CSV file', 'error');
        return false;
    }
    
    const importBtn = document.getElementById('bulkImportBtn');
    const originalText = importBtn.innerHTML;
    importBtn.disabled = true;
    importBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Importing...';
    
    // Hide previous results
    document.getElementById('importErrors').classList.add('hidden');
    document.getElementById('importSuccess').classList.add('hidden');
    
    try {
        const text = await file.text();
        const rows = text.split('\n').map(row => row.trim()).filter(row => row);
        
        if (rows.length < 2) {
            throw new Error('CSV file must contain at least a header row and one data row');
        }
        
        const headers = rows[0].split(',').map(h => h.trim().toLowerCase());
        const requiredHeaders = ['id_number', 'full_name', 'email', 'role', 'password'];
        
        // Validate headers
        const missingHeaders = requiredHeaders.filter(h => !headers.includes(h));
        if (missingHeaders.length > 0) {
            throw new Error(`Missing required columns: ${missingHeaders.join(', ')}`);
        }
        
        const users = [];
        const errors = [];
        
        // Parse data rows
        for (let i = 1; i < rows.length; i++) {
            const cells = rows[i].split(',').map(c => c.trim());
            const user = {};
            
            headers.forEach((header, idx) => {
                user[header] = cells[idx] || '';
            });
            
            // Validate row
            if (!user.full_name || !user.email || !user.role || !user.password) {
                errors.push(`Row ${i + 1}: Missing required fields`);
                continue;
            }
            
            if (!['Administrator', 'Technician', 'LaboratoryStaff', 'Student'].includes(user.role)) {
                errors.push(`Row ${i + 1}: Invalid role "${user.role}"`);
                continue;
            }
            
            users.push(user);
        }
        
        if (errors.length > 0 && users.length === 0) {
            const errorList = document.getElementById('importErrorList');
            errorList.innerHTML = errors.map(e => `<li>${e}</li>`).join('');
            document.getElementById('importErrors').classList.remove('hidden');
            importBtn.disabled = false;
            importBtn.innerHTML = originalText;
            return false;
        }
        
        // Import users
        let successCount = 0;
        let failCount = 0;
        
        for (const user of users) {
            try {
                const formData = new FormData();
                formData.append('ajax', '1');
                formData.append('action', 'create_user');
                formData.append('id_number', user.id_number);
                formData.append('full_name', user.full_name);
                formData.append('email', user.email);
                formData.append('role', user.role);
                formData.append('password', user.password);
                
                const response = await fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    successCount++;
                    // Add user to table
                    if (result.user) {
                        addUserToTable(result.user);
                    }
                } else {
                    errors.push(`${user.email}: ${result.message || 'Failed to create'}`);
                    failCount++;
                }
            } catch (err) {
                errors.push(`${user.email}: ${err.message}`);
                failCount++;
            }
        }
        
        // Show results
        if (successCount > 0) {
            document.getElementById('importSuccessMessage').textContent = 
                `Successfully imported ${successCount} user(s)${failCount > 0 ? `. ${failCount} failed.` : '.'}`;
            document.getElementById('importSuccess').classList.remove('hidden');
            updateUsersCount();
            updatePagination();
        }
        
        if (errors.length > 0) {
            const errorList = document.getElementById('importErrorList');
            errorList.innerHTML = errors.slice(0, 10).map(e => `<li>${e}</li>`).join('');
            if (errors.length > 10) {
                errorList.innerHTML += `<li>...and ${errors.length - 10} more errors</li>`;
            }
            document.getElementById('importErrors').classList.remove('hidden');
        }
        
        if (successCount > 0 && errors.length === 0) {
            showToast(`Successfully imported ${successCount} users!`, 'success');
            setTimeout(() => {
                closeBulkImportModal();
            }, 2000);
        }
        
    } catch (err) {
        showToast(err.message, 'error');
    } finally {
        importBtn.disabled = false;
        importBtn.innerHTML = originalText;
    }
    
    return false;
}

function addUserToTable(user) {
    const tbody = document.querySelector('#usersTable tbody');
    const initial = user.full_name ? user.full_name.charAt(0).toUpperCase() : '?';
    const avatarHtml = user.avatar ? 
        `<img src="${user.avatar}" alt="avatar" class="w-full h-full object-cover" />` :
        `<span>${initial}</span>`;
    
    const statusClass = user.status === 'Active' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600';
    const createdDate = new Date(user.created_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
    
    const row = document.createElement('tr');
    row.className = 'hover:bg-blue-50 transition-colors';
    row.dataset.user = JSON.stringify(user);
    row.innerHTML = `
        <td class="px-3 py-2">
            <div class="flex items-center gap-2">
                <div class="w-7 h-7 rounded-full bg-gray-200 overflow-hidden flex items-center justify-center text-[10px] font-medium text-gray-700">
                    ${avatarHtml}
                </div>
                <span class="font-medium text-xs text-gray-800">${user.full_name}</span>
            </div>
        </td>
        <td class="px-3 py-2 text-xs text-gray-500">${user.email}</td>
        <td class="px-3 py-2 text-xs">${user.role === 'LaboratoryStaff' ? 'Laboratory Staff' : user.role}</td>
        <td class="px-3 py-2">
            <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[10px] font-medium ${statusClass}">
                ${user.status}
            </span>
        </td>
        <td class="px-3 py-2 text-xs">${createdDate}</td>
        <td class="px-3 py-2 flex items-center justify-between">
            <span class="text-xs">-</span>
            <div class="relative inline-block text-left ml-2">
                <button type="button" onclick="toggleRowMenu(this, ${user.id})" class="p-1 rounded hover:bg-gray-100 text-xs">
                    <i class="fa fa-ellipsis-v text-xs"></i>
                </button>
                <div class="hidden origin-top-right absolute right-0 mt-1 w-36 bg-white border rounded shadow-lg z-60" role="menu">
                    <div class="py-1">
                        <button type="button" class="w-full text-left px-2 py-1.5 text-xs text-gray-700 hover:bg-gray-100" onclick="editUser(this, ${user.id})">
                            <i class="fa fa-pencil mr-1 text-xs"></i> Edit details
                        </button>
                        <button type="button" class="w-full text-left px-2 py-1.5 text-xs text-red-600 hover:bg-red-50" onclick="suspendUser(${user.id})">
                            <i class="fa fa-ban mr-1 text-xs"></i> Deactivate user
                        </button>
                    </div>
                </div>
            </div>
        </td>
    `;
    
    tbody.insertBefore(row, tbody.firstChild);
    allRows.unshift(row);
}

function updateUsersCount() {
    const count = document.getElementById('usersCount');
    if (count) {
        count.textContent = `(${allRows.length})`;
    }
}

// ...existing code...
</script>
