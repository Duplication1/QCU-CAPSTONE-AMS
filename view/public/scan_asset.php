<?php
// Public QR scanner page - now with authentication and role-based access
session_start();
require_once '../../config/config.php';
require_once '../../model/Database.php';
require_once '../../model/AssetHistory.php';
require_once '../../model/ActivityLog.php';

// Check if user is logged in
$is_logged_in = isset($_SESSION['is_logged_in']) && $_SESSION['is_logged_in'] === true;
$user_role = $is_logged_in ? $_SESSION['role'] : null;
$user_id = $is_logged_in ? $_SESSION['user_id'] : null;

// Get asset_id from URL
$asset_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Handle login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    header('Content-Type: application/json');
    
    $id_number = trim($_POST['id_number'] ?? '');
    $password = trim($_POST['password'] ?? '');
    
    if (empty($id_number) || empty($password)) {
        echo json_encode(['success' => false, 'message' => 'ID number and password are required']);
        exit();
    }
    
    $conn = new mysqli('localhost', 'root', '', 'ams_database');
    if ($conn->connect_error) {
        echo json_encode(['success' => false, 'message' => 'Database connection failed']);
        exit();
    }
    
    $stmt = $conn->prepare("SELECT id, id_number, password, full_name, role, status FROM users WHERE id_number = ?");
    $stmt->bind_param('s', $id_number);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($user = $result->fetch_assoc()) {
        if ($user['status'] !== 'Active') {
            echo json_encode(['success' => false, 'message' => 'Account is not active']);
            exit();
        }
        
        if (password_verify($password, $user['password'])) {
            $_SESSION['is_logged_in'] = true;
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['id_number'] = $user['id_number'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role'] = $user['role'];
            
            // Log login activity
            try {
                ActivityLog::record($user['id'], 'login', 'scanner', $asset_id, 'Logged in via QR scanner for asset viewing');
            } catch (Exception $e) {
                error_log("ActivityLog failed: " . $e->getMessage());
            }
            
            echo json_encode(['success' => true, 'message' => 'Login successful']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid credentials']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid credentials']);
    }
    
    $stmt->close();
    $conn->close();
    exit();
}

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: scan_asset.php?id=" . $asset_id);
    exit();
}

// Handle condition update (Technician only)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_condition']) && $is_logged_in && $user_role === 'Technician') {
    header('Content-Type: application/json');
    
    $new_condition = $_POST['condition'] ?? '';
    $asset_id = intval($_POST['asset_id'] ?? 0);
    
    $conn = new mysqli('localhost', 'root', '', 'ams_database');
    
    // Get old condition
    $old_stmt = $conn->prepare("SELECT `condition` FROM assets WHERE id = ?");
    $old_stmt->bind_param('i', $asset_id);
    $old_stmt->execute();
    $old_result = $old_stmt->get_result();
    $old_data = $old_result->fetch_assoc();
    $old_condition = $old_data['condition'];
    $old_stmt->close();
    
    // Update condition
    $stmt = $conn->prepare("UPDATE assets SET `condition` = ? WHERE id = ?");
    $stmt->bind_param('si', $new_condition, $asset_id);
    
    if ($stmt->execute()) {
        // Log history
        require_once '../../controller/AssetHistoryHelper.php';
        $helper = AssetHistoryHelper::getInstance();
        $helper->logConditionChange($asset_id, $old_condition, $new_condition, $user_id);
        
        // Log to activity logs
        try {
            ActivityLog::record($user_id, 'update', 'asset', $asset_id, "Condition changed from {$old_condition} to {$new_condition} via QR scanner");
        } catch (Exception $e) {
            error_log("ActivityLog failed: " . $e->getMessage());
        }
        
        echo json_encode(['success' => true, 'message' => 'Condition updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update condition']);
    }
    
    $stmt->close();
    $conn->close();
    exit();
}

// Handle laboratory staff updates (PC assignment, room, status)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_asset']) && $is_logged_in && $user_role === 'Laboratory Staff') {
    header('Content-Type: application/json');
    
    $asset_id = intval($_POST['asset_id'] ?? 0);
    $pc_unit_id = !empty($_POST['pc_unit_id']) ? intval($_POST['pc_unit_id']) : null;
    $room_id = !empty($_POST['room_id']) ? intval($_POST['room_id']) : null;
    $status = $_POST['status'] ?? '';
    
    $conn = new mysqli('localhost', 'root', '', 'ams_database');
    
    // Get old values
    $old_stmt = $conn->prepare("SELECT pc_unit_id, room_id, status FROM assets WHERE id = ?");
    $old_stmt->bind_param('i', $asset_id);
    $old_stmt->execute();
    $old_result = $old_stmt->get_result();
    $old_data = $old_result->fetch_assoc();
    $old_stmt->close();
    
    // Update asset
    $stmt = $conn->prepare("UPDATE assets SET pc_unit_id = ?, room_id = ?, status = ? WHERE id = ?");
    $stmt->bind_param('iisi', $pc_unit_id, $room_id, $status, $asset_id);
    
    if ($stmt->execute()) {
        // Log history
        require_once '../../controller/AssetHistoryHelper.php';
        $helper = AssetHistoryHelper::getInstance();
        
        $changes = [];
        
        if ($old_data['pc_unit_id'] != $pc_unit_id) {
            $old_pc = $old_data['pc_unit_id'] ? "PC-" . $old_data['pc_unit_id'] : 'None';
            $new_pc = $pc_unit_id ? "PC-" . $pc_unit_id : 'Standby';
            $helper->logUpdate($asset_id, 'pc_unit_id', $old_pc, $new_pc, $user_id);
            $changes[] = "PC Unit: {$old_pc} → {$new_pc}";
        }
        
        if ($old_data['room_id'] != $room_id) {
            $helper->logUpdate($asset_id, 'room_id', $old_data['room_id'], $room_id, $user_id);
            $old_room = $old_data['room_id'] ?? 'None';
            $new_room = $room_id ?? 'None';
            $changes[] = "Room: {$old_room} → {$new_room}";
        }
        
        if ($old_data['status'] != $status) {
            $helper->logStatusChange($asset_id, $old_data['status'], $status, $user_id);
            $changes[] = "Status: {$old_data['status']} → {$status}";
        }
        
        // Log to activity logs
        if (!empty($changes)) {
            try {
                $change_description = implode(', ', $changes);
                ActivityLog::record($user_id, 'update', 'asset', $asset_id, "Asset updated via QR scanner: {$change_description}");
            } catch (Exception $e) {
                error_log("ActivityLog failed: " . $e->getMessage());
            }
        }
        
        echo json_encode(['success' => true, 'message' => 'Asset updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update asset']);
    }
    
    $stmt->close();
    $conn->close();
    exit();
}

if ($asset_id <= 0) {
    $error = "Invalid asset ID";
} elseif (!$is_logged_in) {
    // Show login form - asset details hidden
    $show_login = true;
} else {
    // Get database connection
    $conn = new mysqli('localhost', 'root', '', 'ams_database');
    
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    // Get asset details with related information
    $stmt = $conn->prepare("
        SELECT 
            a.*,
            r.name as room_name,
            r.building_id,
            b.name as building_name,
            ac.name as category_name,
            pu.terminal_number,
            u1.full_name as created_by_name
        FROM assets a
        LEFT JOIN rooms r ON a.room_id = r.id
        LEFT JOIN buildings b ON r.building_id = b.id
        LEFT JOIN asset_categories ac ON a.category = ac.id
        LEFT JOIN pc_units pu ON a.pc_unit_id = pu.id
        LEFT JOIN users u1 ON a.created_by = u1.id
        WHERE a.id = ?
    ");
    
    $stmt->bind_param('i', $asset_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $asset = $result->fetch_assoc();
    $stmt->close();
    
    if (!$asset) {
        $error = "Asset not found";
    } else {
        // Get asset history
        $assetHistory = new AssetHistory();
        $history = $assetHistory->getAssetHistory($asset_id, 50);
        $stats = $assetHistory->getAssetStats($asset_id);
        
        // Get borrowing history if applicable
        $borrowing_history = [];
        if ($asset['is_borrowable']) {
            $borrow_stmt = $conn->prepare("
                SELECT 
                    ab.*,
                    u1.full_name as borrower_name,
                    u2.full_name as approved_by_name
                FROM asset_borrowing ab
                LEFT JOIN users u1 ON ab.borrower_id = u1.id
                LEFT JOIN users u2 ON ab.approved_by = u2.id
                WHERE ab.asset_id = ?
                ORDER BY ab.created_at DESC
                LIMIT 10
            ");
            $borrow_stmt->bind_param('i', $asset_id);
            $borrow_stmt->execute();
            $borrow_result = $borrow_stmt->get_result();
            while ($row = $borrow_result->fetch_assoc()) {
                $borrowing_history[] = $row;
            }
            $borrow_stmt->close();
        }
        
        // Get dropdown data for Laboratory Staff
        $buildings = [];
        $rooms = [];
        $pc_units = [];
        
        if ($user_role === 'Laboratory Staff') {
            // Get buildings
            $building_stmt = $conn->query("SELECT id, name FROM buildings ORDER BY name");
            while ($row = $building_stmt->fetch_assoc()) {
                $buildings[] = $row;
            }
            
            // Get all rooms with building info
            $room_stmt = $conn->query("SELECT r.id, r.name, r.building_id, b.name as building_name 
                                       FROM rooms r 
                                       LEFT JOIN buildings b ON r.building_id = b.id 
                                       ORDER BY b.name, r.name");
            while ($row = $room_stmt->fetch_assoc()) {
                $rooms[] = $row;
            }
            
            // Get PC units with their room and building info
            $pc_stmt = $conn->query("SELECT pu.id, pu.terminal_number, pu.room_id, 
                                            r.name as room_name, r.building_id,
                                            b.name as building_name
                                     FROM pc_units pu
                                     LEFT JOIN rooms r ON pu.room_id = r.id
                                     LEFT JOIN buildings b ON r.building_id = b.id
                                     ORDER BY b.name, r.name, pu.terminal_number");
            while ($row = $pc_stmt->fetch_assoc()) {
                $pc_units[] = $row;
            }
        }
    }
    
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($asset) ? htmlspecialchars($asset['asset_tag']) : 'Asset Scanner'; ?> - QCU AMS</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        .glass-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.37);
        }
        .status-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.875rem;
            font-weight: 600;
        }
        .timeline-item {
            position: relative;
            padding-left: 2rem;
            padding-bottom: 1.5rem;
        }
        .timeline-item:before {
            content: '';
            position: absolute;
            left: 0;
            top: 0.5rem;
            width: 0.75rem;
            height: 0.75rem;
            background: #667eea;
            border-radius: 50%;
            border: 2px solid white;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.2);
        }
        .timeline-item:after {
            content: '';
            position: absolute;
            left: 0.325rem;
            top: 1.25rem;
            width: 2px;
            height: calc(100% - 1rem);
            background: linear-gradient(to bottom, #667eea, transparent);
        }
        .timeline-item:last-child:after {
            display: none;
        }
        .login-form {
            max-width: 400px;
            margin: 0 auto;
        }
        .action-buttons {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }
    </style>
</head>
<body class="p-4 md:p-8">
    <div class="max-w-6xl mx-auto">
        <!-- Header -->
        <div class="text-center mb-8">
            <h1 class="text-4xl font-bold text-white mb-2">
                <i class="fas fa-qrcode mr-3"></i>Asset Information
            </h1>
            <p class="text-white text-opacity-90">Quezon City University - Asset Management System</p>
            <?php if ($is_logged_in): ?>
            <div class="mt-4 flex items-center justify-center gap-4 text-white">
                <span class="text-sm">
                    <i class="fas fa-user mr-2"></i><?php echo htmlspecialchars($_SESSION['full_name']); ?>
                    <span class="ml-2 px-2 py-1 bg-white bg-opacity-20 rounded-full text-xs">
                        <?php echo htmlspecialchars($user_role); ?>
                    </span>
                </span>
                <a href="?logout=1&id=<?php echo $asset_id; ?>" class="text-sm hover:underline">
                    <i class="fas fa-sign-out-alt mr-1"></i>Logout
                </a>
            </div>
            <?php endif; ?>
        </div>

        <?php if (isset($show_login) && $show_login): ?>
            <!-- Login Form -->
            <div class="glass-card p-8 login-form">
                <div class="text-center mb-6">
                    <i class="fas fa-lock text-5xl text-blue-600 mb-4"></i>
                    <h2 class="text-2xl font-bold text-gray-800 mb-2">Login Required</h2>
                    <p class="text-gray-600">Please login to view asset information</p>
                </div>
                
                <form id="loginForm" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">ID Number</label>
                        <input type="text" name="id_number" required
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                        <input type="password" name="password" required
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                    
                    <div id="loginError" class="hidden text-red-600 text-sm"></div>
                    
                    <button type="submit" 
                            class="w-full bg-blue-600 text-white py-2 px-4 rounded-lg hover:bg-blue-700 transition-colors font-medium">
                        <i class="fas fa-sign-in-alt mr-2"></i>Login
                    </button>
                </form>
            </div>
        <?php elseif (isset($error)): ?>
            <!-- Error State -->
            <div class="glass-card p-8 text-center">
                <div class="text-red-500 mb-4">
                    <i class="fas fa-exclamation-circle text-6xl"></i>
                </div>
                <h2 class="text-2xl font-bold text-gray-800 mb-2"><?php echo htmlspecialchars($error); ?></h2>
                <p class="text-gray-600">Please scan a valid QR code or check the asset ID.</p>
            </div>
        <?php else: ?>
            <!-- Asset Details Card -->
            <div class="glass-card p-6 md:p-8 mb-6">
                <div class="grid md:grid-cols-3 gap-6">
                    <!-- QR Code Display -->
                    <div class="text-center">
                        <img src="<?php echo htmlspecialchars($asset['qr_code']); ?>" 
                             alt="QR Code" 
                             class="w-48 h-48 mx-auto mb-4 rounded-lg shadow-md">
                        <h3 class="text-xl font-bold text-gray-800"><?php echo htmlspecialchars($asset['asset_tag']); ?></h3>
                    </div>

                    <!-- Basic Information -->
                    <div class="md:col-span-2">
                        <h2 class="text-2xl font-bold text-gray-800 mb-4">
                            <?php echo htmlspecialchars($asset['asset_name']); ?>
                        </h2>
                        
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <span class="text-gray-600 text-sm">Status:</span>
                                <div class="status-badge bg-<?php 
                                    echo $asset['status'] === 'Available' ? 'green' : 
                                         ($asset['status'] === 'In Use' ? 'blue' : 
                                         ($asset['status'] === 'Disposed' ? 'red' : 'yellow')); 
                                ?>-100 text-<?php 
                                    echo $asset['status'] === 'Available' ? 'green' : 
                                         ($asset['status'] === 'In Use' ? 'blue' : 
                                         ($asset['status'] === 'Disposed' ? 'red' : 'yellow')); 
                                ?>-800">
                                    <i class="fas fa-circle text-xs mr-1"></i>
                                    <?php echo htmlspecialchars($asset['status']); ?>
                                </div>
                            </div>
                            <div>
                                <span class="text-gray-600 text-sm">Condition:</span>
                                <div class="status-badge bg-gray-100 text-gray-800">
                                    <?php echo htmlspecialchars($asset['condition']); ?>
                                </div>
                            </div>
                            <div>
                                <span class="text-gray-600 text-sm">Type:</span>
                                <p class="font-semibold text-gray-800"><?php echo htmlspecialchars($asset['asset_type']); ?></p>
                            </div>
                            <div>
                                <span class="text-gray-600 text-sm">Category:</span>
                                <p class="font-semibold text-gray-800"><?php echo htmlspecialchars($asset['category_name'] ?? 'N/A'); ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Detailed Specifications -->
                <div class="mt-6 border-t pt-6">
                    <h3 class="text-lg font-bold text-gray-800 mb-4">
                        <i class="fas fa-info-circle mr-2"></i>Detailed Information
                    </h3>
                    <div class="grid md:grid-cols-3 gap-4 text-sm">
                        <?php if ($asset['brand']): ?>
                        <div>
                            <span class="text-gray-600">Brand:</span>
                            <p class="font-semibold text-gray-800"><?php echo htmlspecialchars($asset['brand']); ?></p>
                        </div>
                        <?php endif; ?>
                        <?php if ($asset['model']): ?>
                        <div>
                            <span class="text-gray-600">Model:</span>
                            <p class="font-semibold text-gray-800"><?php echo htmlspecialchars($asset['model']); ?></p>
                        </div>
                        <?php endif; ?>
                        <?php if ($asset['serial_number']): ?>
                        <div>
                            <span class="text-gray-600">Serial Number:</span>
                            <p class="font-semibold text-gray-800"><?php echo htmlspecialchars($asset['serial_number']); ?></p>
                        </div>
                        <?php endif; ?>
                        <?php if ($asset['room_name']): ?>
                        <div>
                            <span class="text-gray-600">Location:</span>
                            <p class="font-semibold text-gray-800">
                                <i class="fas fa-map-marker-alt mr-1"></i>
                                <?php echo htmlspecialchars($asset['building_name'] . ' - ' . $asset['room_name']); ?>
                            </p>
                        </div>
                        <?php endif; ?>
                        <?php if ($asset['is_borrowable']): ?>
                        <div>
                            <span class="text-gray-600">Borrowable:</span>
                            <p class="font-semibold text-green-600">
                                <i class="fas fa-check-circle mr-1"></i>Yes
                            </p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Statistics -->
                <?php if ($stats && in_array($user_role, ['Administrator', 'Laboratory Staff', 'Technician'])): ?>
                <div class="mt-6 border-t pt-6">
                    <h3 class="text-lg font-bold text-gray-800 mb-4">
                        <i class="fas fa-chart-line mr-2"></i>Asset Statistics
                    </h3>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <div class="bg-blue-50 p-4 rounded-lg text-center">
                            <div class="text-3xl font-bold text-blue-600"><?php echo $stats['total_changes']; ?></div>
                            <div class="text-sm text-gray-600 mt-1">Total Changes</div>
                        </div>
                        <div class="bg-purple-50 p-4 rounded-lg text-center">
                            <div class="text-3xl font-bold text-purple-600"><?php echo $stats['unique_actions']; ?></div>
                            <div class="text-sm text-gray-600 mt-1">Action Types</div>
                        </div>
                        <div class="bg-green-50 p-4 rounded-lg text-center">
                            <div class="text-3xl font-bold text-green-600"><?php echo $stats['unique_users']; ?></div>
                            <div class="text-sm text-gray-600 mt-1">Users Involved</div>
                        </div>
                        <div class="bg-orange-50 p-4 rounded-lg text-center">
                            <div class="text-3xl font-bold text-orange-600">
                                <?php 
                                if ($stats['first_recorded']) {
                                    $days = floor((strtotime('now') - strtotime($stats['first_recorded'])) / 86400);
                                    echo $days;
                                } else {
                                    echo '0';
                                }
                                ?>
                            </div>
                            <div class="text-sm text-gray-600 mt-1">Days Tracked</div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Action Buttons Based on Role -->
                <?php if ($user_role === 'Technician'): ?>
                <div class="mt-6 border-t pt-6">
                    <h3 class="text-lg font-bold text-gray-800 mb-4">
                        <i class="fas fa-tools mr-2"></i>Technician Actions
                    </h3>
                    <form id="updateConditionForm" class="flex gap-4 items-end">
                        <input type="hidden" name="asset_id" value="<?php echo $asset_id; ?>">
                        <div class="flex-1">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Update Condition</label>
                            <select name="condition" required
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                <option value="Excellent" <?php echo $asset['condition'] === 'Excellent' ? 'selected' : ''; ?>>Excellent</option>
                                <option value="Good" <?php echo $asset['condition'] === 'Good' ? 'selected' : ''; ?>>Good</option>
                                <option value="Fair" <?php echo $asset['condition'] === 'Fair' ? 'selected' : ''; ?>>Fair</option>
                                <option value="Poor" <?php echo $asset['condition'] === 'Poor' ? 'selected' : ''; ?>>Poor</option>
                                <option value="Non-Functional" <?php echo $asset['condition'] === 'Non-Functional' ? 'selected' : ''; ?>>Non-Functional</option>
                            </select>
                        </div>
                        <button type="submit" 
                                class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                            <i class="fas fa-save mr-2"></i>Update
                        </button>
                    </form>
                </div>
                <?php elseif ($user_role === 'Laboratory Staff'): ?>
                <div class="mt-6 border-t pt-6">
                    <h3 class="text-lg font-bold text-gray-800 mb-4">
                        <i class="fas fa-cog mr-2"></i>Laboratory Staff Actions
                    </h3>
                    <form id="updateAssetForm" class="space-y-4">
                        <input type="hidden" name="asset_id" value="<?php echo $asset_id; ?>">
                        
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">1. Select Location</label>
                                <select id="buildingSelect"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                    <option value="standby" <?php echo empty($asset['building_id']) ? 'selected' : ''; ?>>Standby (No Building Assignment)</option>
                                    <?php foreach ($buildings as $building): ?>
                                        <option value="<?php echo $building['id']; ?>"
                                                <?php echo $asset['building_id'] == $building['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($building['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">2. Select Room in Building</label>
                                <select name="room_id" id="roomSelect"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                    <option value="">Select Location First</option>
                                    <?php foreach ($rooms as $room): ?>
                                        <option value="<?php echo $room['id']; ?>" 
                                                data-building="<?php echo $room['building_id']; ?>"
                                                <?php echo $asset['room_id'] == $room['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($room['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">3. Select PC Unit in Room (Optional - Leave empty for Standby)</label>
                                <select name="pc_unit_id" id="pcSelect"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                    <option value="">Standby (No PC Unit)</option>
                                    <?php foreach ($pc_units as $pc): ?>
                                        <option value="<?php echo $pc['id']; ?>" 
                                                data-room="<?php echo $pc['room_id']; ?>"
                                                data-building="<?php echo $pc['building_id']; ?>"
                                                <?php echo $asset['pc_unit_id'] == $pc['id'] ? 'selected' : ''; ?>>
                                            Terminal <?php echo htmlspecialchars($pc['terminal_number']); ?>
                                            <?php if ($pc['room_name']): ?>
                                                - <?php echo htmlspecialchars($pc['room_name']); ?>
                                            <?php endif; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">4. Set Status</label>
                                <select name="status" required
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                    <option value="Available" <?php echo $asset['status'] === 'Available' ? 'selected' : ''; ?>>Available</option>
                                    <option value="In Use" <?php echo $asset['status'] === 'In Use' ? 'selected' : ''; ?>>In Use</option>
                                    <option value="Under Maintenance" <?php echo $asset['status'] === 'Under Maintenance' ? 'selected' : ''; ?>>Under Maintenance</option>
                                    <option value="Retired" <?php echo $asset['status'] === 'Retired' ? 'selected' : ''; ?>>Retired</option>
                                    <option value="Disposed" <?php echo $asset['status'] === 'Disposed' ? 'selected' : ''; ?>>Disposed</option>
                                    <option value="Archive" <?php echo $asset['status'] === 'Archive' ? 'selected' : ''; ?>>Archive</option>
                                </select>
                            </div>
                        </div>
                        
                        <button type="submit" 
                                class="w-full px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                            <i class="fas fa-save mr-2"></i>Update Asset
                        </button>
                    </form>
                </div>
                <?php endif; ?>
            </div>

            <!-- Borrowing History (if applicable) -->
            <?php if ($asset['is_borrowable'] && !empty($borrowing_history) && in_array($user_role, ['Administrator', 'Laboratory Staff', 'Technician'])): ?>
            <div class="glass-card p-6 md:p-8 mb-6">
                <h3 class="text-lg font-bold text-gray-800 mb-4">
                    <i class="fas fa-history mr-2"></i>Borrowing History
                </h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-2 text-left">Borrower</th>
                                <th class="px-4 py-2 text-left">Borrowed Date</th>
                                <th class="px-4 py-2 text-left">Return Date</th>
                                <th class="px-4 py-2 text-left">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($borrowing_history as $borrow): ?>
                            <tr class="border-b">
                                <td class="px-4 py-2"><?php echo htmlspecialchars($borrow['borrower_name']); ?></td>
                                <td class="px-4 py-2"><?php echo date('M d, Y H:i', strtotime($borrow['borrow_date'])); ?></td>
                                <td class="px-4 py-2">
                                    <?php echo $borrow['return_date'] ? date('M d, Y H:i', strtotime($borrow['return_date'])) : '-'; ?>
                                </td>
                                <td class="px-4 py-2">
                                    <span class="status-badge bg-<?php 
                                        echo $borrow['status'] === 'Approved' ? 'green' : 
                                             ($borrow['status'] === 'Pending' ? 'yellow' : 
                                             ($borrow['status'] === 'Returned' ? 'blue' : 'red')); 
                                    ?>-100 text-<?php 
                                        echo $borrow['status'] === 'Approved' ? 'green' : 
                                             ($borrow['status'] === 'Pending' ? 'yellow' : 
                                             ($borrow['status'] === 'Returned' ? 'blue' : 'red')); 
                                    ?>-800">
                                        <?php echo htmlspecialchars($borrow['status']); ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>

            <!-- Asset History Timeline -->
            <?php if (!empty($history) && in_array($user_role, ['Administrator', 'Laboratory Staff', 'Technician'])): ?>
            <div class="glass-card p-6 md:p-8">
                <h3 class="text-lg font-bold text-gray-800 mb-6">
                    <i class="fas fa-clock-rotate-left mr-2"></i>Asset Activity Timeline
                </h3>
                <div class="space-y-2">
                    <?php foreach ($history as $record): ?>
                    <div class="timeline-item">
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <div class="flex justify-between items-start mb-2">
                                <div>
                                    <span class="inline-block px-3 py-1 bg-blue-100 text-blue-800 text-xs font-semibold rounded-full">
                                        <?php echo htmlspecialchars($record['action_type']); ?>
                                    </span>
                                    <?php if ($record['field_changed']): ?>
                                        <span class="text-gray-600 text-xs ml-2">
                                            <i class="fas fa-edit mr-1"></i><?php echo htmlspecialchars($record['field_changed']); ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <span class="text-xs text-gray-500">
                                    <?php echo date('M d, Y H:i', strtotime($record['created_at'])); ?>
                                </span>
                            </div>
                            <?php if ($record['description']): ?>
                            <p class="text-sm text-gray-700 mb-2"><?php echo htmlspecialchars($record['description']); ?></p>
                            <?php endif; ?>
                            <?php if ($record['old_value'] || $record['new_value']): ?>
                            <div class="text-xs text-gray-600 flex gap-4">
                                <?php if ($record['old_value']): ?>
                                <div>
                                    <span class="text-red-600">Old:</span> 
                                    <span class="font-mono"><?php echo htmlspecialchars($record['old_value']); ?></span>
                                </div>
                                <?php endif; ?>
                                <?php if ($record['new_value']): ?>
                                <div>
                                    <span class="text-green-600">New:</span> 
                                    <span class="font-mono"><?php echo htmlspecialchars($record['new_value']); ?></span>
                                </div>
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>
                            <?php if ($record['performed_by_name']): ?>
                            <div class="text-xs text-gray-500 mt-2">
                                <i class="fas fa-user mr-1"></i>
                                By: <?php echo htmlspecialchars($record['performed_by_name']); ?>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        <?php endif; ?>

        <!-- Footer -->
        <div class="text-center text-white mt-8">
            <p class="text-sm opacity-90">
                Powered by QCU Asset Management System &copy; <?php echo date('Y'); ?>
            </p>
        </div>
    </div>
    
    <script>
    // Building > Room > PC cascading filters for Laboratory Staff
    const buildingSelect = document.getElementById('buildingSelect');
    const roomSelect = document.getElementById('roomSelect');
    const pcSelect = document.getElementById('pcSelect');
    
    if (buildingSelect && roomSelect && pcSelect) {
        const allRoomOptions = Array.from(roomSelect.options);
        const allPcOptions = Array.from(pcSelect.options);
        
        // Building change - filter rooms
        buildingSelect.addEventListener('change', function() {
            const selectedBuilding = this.value;
            
            // Handle Standby mode
            if (selectedBuilding === 'standby') {
                roomSelect.innerHTML = '<option value="">Standby - No Room</option>';
                roomSelect.disabled = true;
                roomSelect.value = '';
                
                pcSelect.innerHTML = '<option value="">Standby (No PC Unit)</option>';
                pcSelect.disabled = false;
                pcSelect.value = '';
                return;
            }
            
            // Clear and reset room select
            roomSelect.innerHTML = selectedBuilding ? 
                '<option value="">Select Room in this Building</option>' : 
                '<option value="">Select Location First</option>';
            roomSelect.disabled = !selectedBuilding;
            
            // Filter rooms by building
            if (selectedBuilding) {
                allRoomOptions.forEach(option => {
                    if (option.value && option.dataset.building === selectedBuilding) {
                        roomSelect.appendChild(option.cloneNode(true));
                    }
                });
            }
            
            // Clear PC selection   
            pcSelect.value = '';
            pcSelect.disabled = true;
            pcSelect.innerHTML = '<option value="">Select Room First</option>';
        });
        
        // Room change - filter PCs
        roomSelect.addEventListener('change', function() {
            const selectedRoom = this.value;
            const selectedBuilding = buildingSelect.value;
            
            // Clear and reset PC select
            pcSelect.innerHTML = '<option value="">Standby (No PC Unit)</option>';
            pcSelect.disabled = false;
            
            // Filter PCs by room
            if (selectedRoom) {
                allPcOptions.forEach(option => {
                    if (option.value && option.dataset.room === selectedRoom) {
                        pcSelect.appendChild(option.cloneNode(true));
                    }
                });
            }
        });
        
        // Initialize on page load
        if (buildingSelect.value) {
            buildingSelect.dispatchEvent(new Event('change'));
            
            // Wait for rooms to populate, then trigger room change
            setTimeout(() => {
                if (roomSelect.value && buildingSelect.value !== 'standby') {
                    roomSelect.dispatchEvent(new Event('change'));
                }
            }, 50);
        } else {
            roomSelect.disabled = true;
            pcSelect.disabled = true;
        }
    }
    
    // Login Form Handler
    document.getElementById('loginForm')?.addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(e.target);
        formData.append('login', '1');
        
        const errorDiv = document.getElementById('loginError');
        const submitButton = e.target.querySelector('button[type="submit"]');
        const originalButtonText = submitButton.innerHTML;
        
        errorDiv.classList.add('hidden');
        submitButton.disabled = true;
        submitButton.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Logging in...';
        
        try {
            const response = await fetch('', {
                method: 'POST',
                body: formData
            });
            
            const text = await response.text();
            let result;
            
            try {
                result = JSON.parse(text);
            } catch (parseError) {
                console.error('Response was not JSON:', text);
                throw new Error('Invalid response from server');
            }
            
            if (result.success) {
                window.location.reload();
            } else {
                errorDiv.textContent = result.message;
                errorDiv.classList.remove('hidden');
                submitButton.disabled = false;
                submitButton.innerHTML = originalButtonText;
            }
        } catch (error) {
            console.error('Login error:', error);
            errorDiv.textContent = error.message || 'An error occurred. Please try again.';
            errorDiv.classList.remove('hidden');
            submitButton.disabled = false;
            submitButton.innerHTML = originalButtonText;
        }
    });
    
    // Update Condition Form (Technician)
    document.getElementById('updateConditionForm')?.addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(e.target);
        formData.append('update_condition', '1');
        
        const button = e.target.querySelector('button[type="submit"]');
        const originalText = button.innerHTML;
        button.disabled = true;
        button.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Updating...';
        
        try {
            const response = await fetch('', {
                method: 'POST',
                body: formData
            });
            
            const text = await response.text();
            let result;
            
            try {
                result = JSON.parse(text);
            } catch (parseError) {
                console.error('Response was not JSON:', text);
                throw new Error('Invalid response from server');
            }
            
            if (result.success) {
                showAlert('success', result.message);
                setTimeout(() => window.location.reload(), 1500);
            } else {
                showAlert('error', result.message);
                button.disabled = false;
                button.innerHTML = originalText;
            }
        } catch (error) {
            console.error('Update error:', error);
            showAlert('error', error.message || 'An error occurred. Please try again.');
            button.disabled = false;
            button.innerHTML = originalText;
        }
    });
    
    // Update Asset Form (Laboratory Staff)
    document.getElementById('updateAssetForm')?.addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(e.target);
        formData.append('update_asset', '1');
        
        const button = e.target.querySelector('button[type="submit"]');
        const originalText = button.innerHTML;
        button.disabled = true;
        button.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Updating...';
        
        try {
            const response = await fetch('', {
                method: 'POST',
                body: formData
            });
            
            const text = await response.text();
            let result;
            
            try {
                result = JSON.parse(text);
            } catch (parseError) {
                console.error('Response was not JSON:', text);
                throw new Error('Invalid response from server');
            }
            
            if (result.success) {
                showAlert('success', result.message);
                setTimeout(() => window.location.reload(), 1500);
            } else {
                showAlert('error', result.message);
                button.disabled = false;
                button.innerHTML = originalText;
            }
        } catch (error) {
            console.error('Update error:', error);
            showAlert('error', error.message || 'An error occurred. Please try again.');
            button.disabled = false;
            button.innerHTML = originalText;
        }
    });
    
    // Alert function
    function showAlert(type, message) {
        const alertDiv = document.createElement('div');
        alertDiv.className = `fixed top-4 right-4 z-50 px-6 py-3 rounded-lg shadow-lg ${
            type === 'success' ? 'bg-green-500' : 'bg-red-500'
        } text-white font-medium`;
        alertDiv.textContent = message;
        
        document.body.appendChild(alertDiv);
        
        setTimeout(() => alertDiv.remove(), 5000);
    }
    </script>
</body>
</html>
