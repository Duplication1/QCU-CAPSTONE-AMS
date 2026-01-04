<?php
// Prevent page caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

session_start();

// Check if user is logged in and has Laboratory Staff role
if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true || $_SESSION['role'] !== 'Laboratory Staff') {
    header("Location: ../login.php");
    exit();
}

require_once '../../config/config.php';

// Create database connection
if (!isset($conn)) {
    $conn = new mysqli('localhost', 'root', '', 'ams_database');
    if ($conn->connect_error) {
        // Check if it's an AJAX request
        if (isset($_POST['ajax']) && $_POST['ajax'] === '1') {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Database connection failed']);
            exit();
        }
        die("Connection failed: " . $conn->connect_error);
    }
}

// Get room_id from URL
$room_id = isset($_GET['room_id']) ? intval($_GET['room_id']) : 0;

if ($room_id <= 0) {
    header("Location: buildings.php");
    exit();
}

// Get room and building details
$room_query = $conn->prepare("SELECT r.*, b.name as building_name FROM rooms r LEFT JOIN buildings b ON r.building_id = b.id WHERE r.id = ?");
$room_query->bind_param('i', $room_id);
$room_query->execute();
$room_result = $room_query->get_result();
$room = $room_result->fetch_assoc();
$room_query->close();

if (!$room) {
    header("Location: buildings.php");
    exit();
}

// Helper function to get next available terminal number
function getNextAvailableTerminalNumber($conn, $prefix, $start_number = 1) {
    $current_number = $start_number;
    $max_attempts = 1000; // Prevent infinite loop
    $attempts = 0;
    
    while ($attempts < $max_attempts) {
        $terminal_number = $prefix . str_pad($current_number, 2, '0', STR_PAD_LEFT);
        
        $check_stmt = $conn->prepare("SELECT id FROM pc_units WHERE terminal_number = ?");
        $check_stmt->bind_param('s', $terminal_number);
        $check_stmt->execute();
        $check_stmt->store_result();
        
        if ($check_stmt->num_rows == 0) {
            $check_stmt->close();
            return $current_number;
        }
        
        $check_stmt->close();
        $current_number++;
        $attempts++;
    }
    
    return null; // No available number found
}

// Handle AJAX requests for PC components
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax']) && $_POST['ajax'] === '1') {
    // Suppress any output before JSON response
    if (ob_get_level()) ob_clean();
    header('Content-Type: application/json');
    error_reporting(E_ALL);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    
    // Enable mysqli exceptions
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    
    try {
        $action = $_POST['action'] ?? '';

        if ($action === 'get_pc_categories') {
            $pc_categories_stmt = $conn->prepare("SELECT id, name FROM asset_categories WHERE is_pc_category = 1 ORDER BY name");
            $pc_categories_stmt->execute();
            $pc_categories_result = $pc_categories_stmt->get_result();

            $categories = [];
            while ($category = $pc_categories_result->fetch_assoc()) {
                $categories[] = $category;
            }
            $pc_categories_stmt->close();

            echo json_encode(['success' => true, 'categories' => $categories]);
            exit();
        }

        if ($action === 'get_next_available_number') {
            $prefix = $_POST['prefix'] ?? 'PC-';
            $start = intval($_POST['start'] ?? 1);
            
            $next_number = getNextAvailableTerminalNumber($conn, $prefix, $start);
            
            if ($next_number !== null) {
                echo json_encode(['success' => true, 'next_number' => $next_number]);
            } else {
                echo json_encode(['success' => false, 'message' => 'No available numbers found']);
            }
            exit();
        }

        if ($action === 'get_next_available_asset_tag') {
            $date = $_POST['date'] ?? date('m-d-Y');
            $room_name = $_POST['room_name'] ?? '';
            $start = intval($_POST['start'] ?? 1);
            
            $current_number = $start;
            $max_attempts = 1000;
            $attempts = 0;
            
            while ($attempts < $max_attempts) {
                $asset_tag = $date . '-' . $room_name . '-TH-' . str_pad($current_number, 3, '0', STR_PAD_LEFT);
                
                $check_stmt = $conn->prepare("SELECT id FROM pc_units WHERE asset_tag = ?");
                $check_stmt->bind_param('s', $asset_tag);
                $check_stmt->execute();
                $check_stmt->store_result();
                
                if ($check_stmt->num_rows == 0) {
                    $check_stmt->close();
                    echo json_encode(['success' => true, 'next_number' => $current_number, 'asset_tag' => $asset_tag]);
                    exit();
                }
                
                $check_stmt->close();
                $current_number++;
                $attempts++;
            }
            
            echo json_encode(['success' => false, 'message' => 'No available asset tags found']);
            exit();
        }

    if ($action === 'create_pc_unit') {
        $bulk_mode = $_POST['bulk_mode'] ?? 'single';

        if ($bulk_mode === 'bulk') {
            // Bulk creation logic
            $prefix = trim($_POST['prefix'] ?? '');
            $range_start = intval($_POST['range_start'] ?? 1);
            $range_end = intval($_POST['range_end'] ?? 1);
            $asset_date = trim($_POST['asset_date'] ?? date('Y-m-d'));
            $date = date('m-d-Y', strtotime($asset_date));
            $status = $_POST['status'] ?? 'Active';
            $notes = trim($_POST['notes'] ?? '');
            $selected_components = $_POST['selected_components'] ?? [];

            if (empty($prefix) || $range_start < 1 || $range_end < $range_start) {
                echo json_encode(['success' => false, 'message' => 'Invalid bulk creation parameters']);
                exit();
            }

            if ($range_end - $range_start > 100) {
                echo json_encode(['success' => false, 'message' => 'Maximum 100 units can be created at once']);
                exit();
            }

            $created_count = 0;
            $errors = [];
            $skipped_count = 0;

            // Find the next available starting number
            $current_number = getNextAvailableTerminalNumber($conn, $prefix, $range_start);
            
            if ($current_number === null) {
                echo json_encode(['success' => false, 'message' => 'No available terminal numbers found in the specified range']);
                exit();
            }

            $units_to_create = $range_end - $range_start + 1;
            $created = 0;

            while ($created < $units_to_create && $current_number <= ($range_start + 1000)) {
                $terminal_number = $prefix . str_pad($current_number, 2, '0', STR_PAD_LEFT);
                
                // Double-check if terminal number exists (shouldn't happen with our helper function)
                $check_stmt = $conn->prepare("SELECT id FROM pc_units WHERE terminal_number = ?");
                $check_stmt->bind_param('s', $terminal_number);
                $check_stmt->execute();
                $check_stmt->store_result();

                if ($check_stmt->num_rows > 0) {
                    $check_stmt->close();
                    $current_number++;
                    $skipped_count++;
                    continue;
                }
                $check_stmt->close();
                
                // Use the date selected in the form (already formatted above)
                $asset_tag = $date . '-' . $room['name'] . '-TH-' . str_pad($current_number, 3, '0', STR_PAD_LEFT);

                // Insert PC unit
                $insert_stmt = $conn->prepare("INSERT INTO pc_units (terminal_number, asset_tag, room_id, status, notes, created_by) VALUES (?, ?, ?, ?, ?, ?)");
                $created_by = $_SESSION['user_id'];
                $insert_stmt->bind_param('ssissi', $terminal_number, $asset_tag, $room_id, $status, $notes, $created_by);

                if ($insert_stmt->execute()) {
                    $new_id = $conn->insert_id;
                    $created_count++;
                    $created++;

                    // Automatically create assets for selected PC components
                    if (!empty($selected_components)) {
                        foreach ($selected_components as $category_id) {
                            // Get category name
                            $cat_stmt = $conn->prepare("SELECT name FROM asset_categories WHERE id = ?");
                            $cat_stmt->bind_param('i', $category_id);
                            $cat_stmt->execute();
                            $cat_result = $cat_stmt->get_result();
                            $category = $cat_result->fetch_assoc();
                            $cat_stmt->close();

                            if (!$category) continue;

                            // Get specifications from form
                            $brand = trim($_POST["component_brand_{$category_id}"] ?? '');
                            $model = trim($_POST["component_model_{$category_id}"] ?? '');
                            $serial = trim($_POST["component_serial_{$category_id}"] ?? '');
                            $condition = trim($_POST["component_condition_{$category_id}"] ?? 'Good');
                            $component_date = trim($_POST["component_date_{$category_id}"] ?? $asset_date);
                            $component_date_formatted = date('m-d-Y', strtotime($component_date));

                            // Generate unique asset tag for THIS specific PC unit
                            // Use current_number to make it unique per PC
                            $component_suffix = str_pad($current_number, 3, '0', STR_PAD_LEFT);
                            $asset_tag = $component_date_formatted . '-' . $room['name'] . '-' . strtoupper($category['name']) . '-' . $component_suffix;
                            $asset_name = $category['name'];

                            // Check if asset tag already exists and find next available
                            $asset_tag_counter = 1;
                            $max_tag_attempts = 1000;
                            while ($asset_tag_counter < $max_tag_attempts) {
                                $check_asset = $conn->prepare("SELECT id FROM assets WHERE asset_tag = ?");
                                $check_asset->bind_param('s', $asset_tag);
                                $check_asset->execute();
                                $check_asset->store_result();

                                if ($check_asset->num_rows == 0) {
                                    $check_asset->close();
                                    break; // Found available tag
                                }
                                $check_asset->close();
                                
                                // Try next number
                                $component_suffix = str_pad($current_number + $asset_tag_counter, 3, '0', STR_PAD_LEFT);
                                $asset_tag = $component_date_formatted . '-' . $room['name'] . '-' . strtoupper($category['name']) . '-' . $component_suffix;
                                $asset_tag_counter++;
                            }

                            // Insert asset first to get ID
                            $asset_stmt = $conn->prepare("INSERT INTO assets (asset_tag, asset_name, asset_type, brand, model, serial_number, room_id, pc_unit_id, status, `condition`, created_by, category) VALUES (?, ?, 'Hardware', ?, ?, ?, ?, ?, 'Available', ?, ?, ?)");
                            $asset_stmt->bind_param('sssssiisii', $asset_tag, $asset_name, $brand, $model, $serial, $room_id, $new_id, $condition, $created_by, $category_id);

                            if ($asset_stmt->execute()) {
                                $asset_id = $conn->insert_id;
                                
                                // Generate QR code with scan URL
                                $base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'];
                                $scan_url = $base_url . '/QCU-CAPSTONE-AMS/view/public/scan_asset.php?id=' . $asset_id;
                                $qr_code_url = 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=' . urlencode($scan_url);
                                
                                // Update asset with QR code
                                $update_qr = $conn->prepare("UPDATE assets SET qr_code = ? WHERE id = ?");
                                $update_qr->bind_param('si', $qr_code_url, $asset_id);
                                $update_qr->execute();
                                $update_qr->close();
                                
                                // Log asset creation history
                                require_once '../../controller/AssetHistoryHelper.php';
                                $historyHelper = AssetHistoryHelper::getInstance();
                                $historyHelper->logAssetCreated($asset_id, $asset_tag, $asset_name, $created_by);
                            } else {
                                $errors[] = "Failed to create asset for PC-$terminal_number: " . $asset_stmt->error;
                            }
                            $asset_stmt->close();
                        }
                    }
                } else {
                    $errors[] = "Failed to create PC unit $terminal_number: " . $insert_stmt->error;
                }
                $insert_stmt->close();
                $current_number++;
            }

            if ($created_count > 0) {
                $message = "Successfully created $created_count PC unit(s)";
                if ($skipped_count > 0) {
                    $message .= " (skipped $skipped_count existing units)";
                }
                $selected_components = $_POST['selected_components'] ?? [];
                if (!empty($selected_components)) {
                    $message .= " with selected components";
                }
                if (!empty($errors)) {
                    $message .= ". Errors: " . implode(', ', $errors);
                }
                echo json_encode(['success' => true, 'message' => $message, 'created' => $created_count]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to create any PC units. Errors: ' . implode(', ', $errors)]);
            }
        } else {
            // Single PC creation logic
            $terminal_number = trim($_POST['terminal_number'] ?? '');
            $asset_date = trim($_POST['asset_date'] ?? date('Y-m-d'));
            $date = date('m-d-Y', strtotime($asset_date));
            $status = $_POST['status'] ?? 'Active';
            $notes = trim($_POST['notes'] ?? '');

            if (empty($terminal_number)) {
                echo json_encode(['success' => false, 'message' => 'Terminal number is required']);
                exit();
            }

            // Check if terminal number already exists and auto-increment if needed
            $check_stmt = $conn->prepare("SELECT id FROM pc_units WHERE terminal_number = ?");
            $check_stmt->bind_param('s', $terminal_number);
            $check_stmt->execute();
            $check_stmt->store_result();

            if ($check_stmt->num_rows > 0) {
                $check_stmt->close();
                // Extract prefix and number
                preg_match('/^([A-Za-z-]+)(\d+)$/', $terminal_number, $matches);
                if ($matches) {
                    $prefix = $matches[1];
                    $start_num = intval($matches[2]);
                    $next_num = getNextAvailableTerminalNumber($conn, $prefix, $start_num);
                    if ($next_num !== null) {
                        $terminal_number = $prefix . str_pad($next_num, strlen($matches[2]), '0', STR_PAD_LEFT);
                    } else {
                        echo json_encode(['success' => false, 'message' => 'No available terminal numbers found']);
                        exit();
                    }
                } else {
                    echo json_encode(['success' => false, 'message' => 'Invalid terminal number format']);
                    exit();
                }
            } else {
                $check_stmt->close();
            }
            
            // Generate asset tag AFTER determining final terminal number
            $terminal_num = preg_replace('/[^0-9]/', '', $terminal_number);
            $asset_tag = $date . '-' . $room['name'] . '-TH-' . str_pad($terminal_num, 3, '0', STR_PAD_LEFT);

            // Insert PC unit
            $insert_stmt = $conn->prepare("INSERT INTO pc_units (terminal_number, asset_tag, room_id, status, notes, created_by) VALUES (?, ?, ?, ?, ?, ?)");
            $created_by = $_SESSION['user_id'];
            $insert_stmt->bind_param('ssissi', $terminal_number, $asset_tag, $room_id, $status, $notes, $created_by);

            if ($insert_stmt->execute()) {
                $new_id = $conn->insert_id;

                // Automatically create assets for selected PC components
                $selected_components = $_POST['selected_components'] ?? [];

                if (!empty($selected_components)) {
                    $assets_created = 0;
                    $asset_failed = [];

                    foreach ($selected_components as $category_id) {
                        // Get category name
                        $cat_stmt = $conn->prepare("SELECT name FROM asset_categories WHERE id = ?");
                        $cat_stmt->bind_param('i', $category_id);
                        $cat_stmt->execute();
                        $cat_result = $cat_stmt->get_result();
                        $category = $cat_result->fetch_assoc();
                        $cat_stmt->close();

                        if (!$category) continue;

                        // Get specifications from form
                        $brand = trim($_POST["component_brand_{$category_id}"] ?? '');
                        $model = trim($_POST["component_model_{$category_id}"] ?? '');
                        $serial = trim($_POST["component_serial_{$category_id}"] ?? '');
                        $condition = trim($_POST["component_condition_{$category_id}"] ?? 'Good');
                        $component_date = trim($_POST["component_date_{$category_id}"] ?? $asset_date);
                        $component_date_formatted = date('m-d-Y', strtotime($component_date));

                        // Generate asset tag: DATE-ROOM-COMPONENT-001
                        // Use component-specific date if provided, otherwise use PC unit date
                        $asset_tag = $component_date_formatted . '-' . $room['name'] . '-' . strtoupper($category['name']) . '-001';
                        $asset_name = $category['name'];

                        // Check if asset tag already exists
                        $check_asset = $conn->prepare("SELECT id FROM assets WHERE asset_tag = ?");
                        $check_asset->bind_param('s', $asset_tag);
                        $check_asset->execute();
                        $check_asset->store_result();

                        if ($check_asset->num_rows > 0) {
                            $check_asset->close();
                            continue; // Skip if asset already exists
                        }
                        $check_asset->close();

                        // Insert asset first to get ID
                        $asset_stmt = $conn->prepare("INSERT INTO assets (asset_tag, asset_name, asset_type, brand, model, serial_number, room_id, pc_unit_id, status, `condition`, created_by, category) VALUES (?, ?, 'Hardware', ?, ?, ?, ?, ?, 'Available', ?, ?, ?)");
                        $asset_stmt->bind_param('sssssiisii', $asset_tag, $asset_name, $brand, $model, $serial, $room_id, $new_id, $condition, $created_by, $category_id);

                        if ($asset_stmt->execute()) {
                            $asset_id = $conn->insert_id;
                            
                            // Generate QR code with scan URL
                            $base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'];
                            $scan_url = $base_url . '/QCU-CAPSTONE-AMS/view/public/scan_asset.php?id=' . $asset_id;
                            $qr_code_url = 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=' . urlencode($scan_url);
                            
                            // Update asset with QR code
                            $update_qr = $conn->prepare("UPDATE assets SET qr_code = ? WHERE id = ?");
                            $update_qr->bind_param('si', $qr_code_url, $asset_id);
                            $update_qr->execute();
                            $update_qr->close();
                            
                            // Log asset creation history
                            require_once '../../controller/AssetHistoryHelper.php';
                            $historyHelper = AssetHistoryHelper::getInstance();
                            $historyHelper->logAssetCreated($asset_id, $asset_tag, $asset_name, $created_by);
                            
                            $assets_created++;
                        } else {
                            $asset_failed[] = $asset_tag;
                        }
                        $asset_stmt->close();
                    }

                    $message = 'PC Unit created successfully';
                    if ($assets_created > 0) {
                        $message .= ". Automatically created $assets_created assets from selected components";
                    }
                    if (!empty($asset_failed)) {
                        $message .= ". Failed to create assets: " . implode(', ', $asset_failed);
                    }

                    echo json_encode(['success' => true, 'message' => $message, 'id' => $new_id]);
                } else {
                    echo json_encode(['success' => true, 'message' => 'PC Unit created successfully', 'id' => $new_id]);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to create PC unit']);
            }
            $insert_stmt->close();
        }
    }
        
    } catch (mysqli_sql_exception $e) {
        // Catch mysqli errors
        echo json_encode(['success' => false, 'message' => 'Database Error: ' . $e->getMessage()]);
    } catch (Exception $e) {
        // Catch any other errors
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    } catch (Error $e) {
        // Catch PHP 7+ errors
        echo json_encode(['success' => false, 'message' => 'System Error: ' . $e->getMessage()]);
    }
    exit();
}

include '../components/layout_header.php';
?>

<style>
.content-container {
    padding: 0.5rem;
}
</style>

<main>
    <div class="content-container">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
            <!-- Breadcrumb Navigation -->
            <ol class="inline-flex items-center space-x-1 md:space-x-3 mb-4">
                <li class="inline-flex items-center">
                    <a href="buildings.php" class="inline-flex items-center text-sm font-medium text-gray-700 hover:text-blue-600">
                        <i class="fa-solid fa-building mr-2"></i>
                        Buildings
                    </a>
                </li>
                <li>
                    <div class="flex items-center">
                        <i class="fa-solid fa-chevron-right text-gray-400 mx-2"></i>
                        <a href="rooms.php?building_id=1" class="text-sm font-medium text-gray-700 hover:text-blue-600">
                            IK                        </a>
                    </div>
                </li>
                <li>
                    <div class="flex items-center">
                        <i class="fa-solid fa-chevron-right text-gray-400 mx-2"></i>
                        <a href="roomassets.php?room_id=<?php echo $room_id; ?>" class="text-sm font-medium text-gray-700 hover:text-blue-600">
                            <?php echo htmlspecialchars($room['name']); ?>
                        </a>
                    </div>
                </li>
                <li aria-current="page">
                    <div class="flex items-center">
                        <i class="fa-solid fa-chevron-right text-gray-400 mx-2"></i>
                        <span class="text-sm font-medium text-gray-500">Add PC Unit</span>
                    </div>
                </li>
            </ol>

            <!-- Page Header -->
            <div class="bg-gradient-to-r from-[#1E3A8A] to-[#153570] px-4 py-3 rounded-lg mb-4">
                <h1 class="text-lg font-semibold text-white flex items-center">
                    <i class="fa-solid fa-plus mr-2"></i>
                    Add New PC Unit(s)
                </h1>
                <p class="text-blue-100 mt-1 text-sm">Create new PC units for <?php echo htmlspecialchars($room['name']); ?> in <?php echo htmlspecialchars($room['building_name']); ?></p>
            </div>

            <!-- Main Form -->
            <form id="pcUnitForm" class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <input type="hidden" name="room_id" value="<?php echo $room_id; ?>">

                <!-- Left Column -->
                <div class="space-y-4">
                    <!-- Mode Selection -->
                    <div class="bg-white border border-gray-200 rounded-lg p-4">
                        <label class="block text-sm font-medium text-gray-700 mb-3">Creation Mode</label>
                        <div class="flex gap-6">
                            <label class="flex items-center cursor-pointer">
                                <input type="radio" name="bulk_mode" value="single" checked onchange="toggleBulkMode()"
                                       class="mr-2 text-[#1E3A8A] focus:ring-[#1E3A8A] w-4 h-4">
                                <span class="text-sm font-medium">Single PC</span>
                            </label>
                            <label class="flex items-center cursor-pointer">
                                <input type="radio" name="bulk_mode" value="bulk" onchange="toggleBulkMode()"
                                       class="mr-2 text-[#1E3A8A] focus:ring-[#1E3A8A] w-4 h-4">
                                <span class="text-sm font-medium">Bulk (Identical PCs)</span>
                            </label>
                        </div>
                    </div>

                    <!-- Single Mode Fields -->
                    <div id="singleModeFields" class="bg-white border border-gray-200 rounded-lg p-4">
                        <h3 class="text-sm font-medium text-gray-900 mb-3">PC Unit Details</h3>
                        <div class="space-y-3">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Asset Tag Date <span class="text-red-500">*</span>
                                </label>
                                <input type="date" name="asset_date" id="singleAssetDate" required
                                       onchange="handleDateChange()"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-[#1E3A8A] focus:border-transparent text-sm"
                                       value="<?php echo date('Y-m-d'); ?>">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Terminal Number <span class="text-red-500">*</span>
                                </label>
                                <input type="text" name="terminal_number" id="singleTerminalNumber"
                                       onchange="updateAssetTagPreview()" onblur="updateAssetTagPreview()"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-[#1E3A8A] focus:border-transparent text-sm"
                                       placeholder="e.g., PC-001">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Asset Tag (Auto-generated)
                                </label>
                                <input type="text" id="assetTagPreview" readonly
                                       class="w-full px-3 py-2 bg-gray-100 border border-gray-300 rounded-md text-sm text-gray-600 cursor-not-allowed"
                                       placeholder="<?php echo date('m-d-Y') . '-' . $room['name'] . '-TH-001'; ?>">
                            </div>
                        </div>
                    </div>

                    <!-- Bulk Mode Fields -->
                    <div id="bulkModeFields" class="bg-white border border-gray-200 rounded-lg p-4 hidden">
                        <h3 class="text-sm font-medium text-gray-900 mb-3">Bulk Creation</h3>
                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 mb-4">
                            <div class="flex items-start gap-3">
                                <i class="fa-solid fa-info-circle text-[#1E3A8A] mt-1"></i>
                                <div class="text-sm text-blue-900">
                                    <p class="font-medium mb-1">Bulk Creation</p>
                                    <p>Create multiple PC units with sequential numbering. Example: PC-01 to PC-50</p>
                                </div>
                            </div>
                        </div>

                        <div class="space-y-3">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Asset Tag Date <span class="text-red-500">*</span>
                                </label>
                                <input type="date" name="asset_date" id="bulkAssetDate" required
                                       oninput="updateBulkAssetTagPreview()"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-[#1E3A8A] focus:border-transparent text-sm"
                                       value="<?php echo date('Y-m-d'); ?>">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Prefix <span class="text-red-500">*</span>
                                </label>
                                <input type="text" name="prefix" id="bulkPrefix" value="PC-"
                                       oninput="updateBulkAssetTagPreview()"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-[#1E3A8A] focus:border-transparent text-sm"
                                       placeholder="e.g., PC-">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Quantity (How many PCs?) <span class="text-red-500">*</span>
                                </label>
                                <input type="number" name="quantity" id="bulkQuantity" value="50" min="1" max="100"
                                       oninput="updateBulkAssetTagPreview()"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-[#1E3A8A] focus:border-transparent text-sm">
                                <input type="hidden" name="range_start" id="bulkRangeStart" value="1">
                                <input type="hidden" name="range_end" id="bulkRangeEnd" value="50">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Asset Tag Preview (First & Last)
                                </label>
                                <div class="space-y-2">
                                    <input type="text" id="bulkAssetTagPreviewFirst" readonly
                                           class="w-full px-3 py-2 bg-gray-100 border border-gray-300 rounded-md text-sm text-gray-600 cursor-not-allowed"
                                           placeholder="First: <?php echo date('m-d-Y') . '-' . $room['name'] . '-TH-001'; ?>">
                                    <input type="text" id="bulkAssetTagPreviewLast" readonly
                                           class="w-full px-3 py-2 bg-gray-100 border border-gray-300 rounded-md text-sm text-gray-600 cursor-not-allowed"
                                           placeholder="Last: <?php echo date('m-d-Y') . '-' . $room['name'] . '-TH-050'; ?>">
                                </div>
                            </div>

                            <div class="text-sm text-gray-600 bg-gray-50 p-2 rounded-md">
                                <i class="fa-solid fa-lightbulb mr-2 text-yellow-500"></i>
                                Maximum 100 units can be created at once. The system will automatically find the next available numbers.
                            </div>
                        </div>
                    </div>

                    <!-- Common Fields -->
                    <div class="bg-white border border-gray-200 rounded-lg p-4">
                        <h3 class="text-sm font-medium text-gray-900 mb-3">Additional Settings</h3>
                        <div class="space-y-3">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                                <select name="status"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-[#1E3A8A] focus:border-transparent text-sm">
                                    <option value="Active">Active</option>
                                    <option value="Inactive">Inactive</option>
                                    <option value="Maintenance">Maintenance</option>
                                    <option value="Archive">Archive</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Notes (Optional)</label>
                                <textarea name="notes" rows="3"
                                          class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-[#1E3A8A] focus:border-transparent text-sm"
                                          placeholder="Additional information about this PC unit..."></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column -->
                <div class="space-y-4">
                    <!-- PC Components Selection -->
                    <div class="bg-white border border-gray-200 rounded-lg p-4">
                        <h3 class="text-sm font-medium text-gray-900 mb-3 flex items-center">
                            <i class="fa-solid fa-microchip text-[#1E3A8A] mr-2"></i>
                            PC Components (Optional)
                        </h3>
                        <div class="bg-gray-50 border border-gray-200 rounded-lg p-3 mb-3">
                            <p class="text-sm text-gray-700 mb-2">
                                Select which components to automatically create assets for this PC unit. You can customize specifications for each selected component.
                            </p>

                            <div id="pcComponentsContainer" class="space-y-3">
                                <!-- Components will be loaded here -->
                            </div>

                            <div class="mt-3 text-sm text-gray-600 bg-blue-50 p-2 rounded-md">
                                <i class="fa-solid fa-info-circle mr-2 text-blue-600"></i>
                                Assets will be created with format: DATE-ROOM-{COMPONENT}-001
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons (Full Width) -->
                <div class="col-span-1 lg:col-span-2 flex justify-end gap-3 pt-4 border-t border-gray-200">
                    <a href="roomassets.php?room_id=<?php echo $room_id; ?>"
                       class="px-4 py-2 border border-gray-300 rounded-md hover:bg-gray-50 transition-colors text-sm font-medium">
                        <i class="fa-solid fa-arrow-left mr-2"></i>Back to Room
                    </a>
                    <button type="button" onclick="submitPCUnit()"
                            class="px-4 py-2 bg-[#1E3A8A] text-white rounded-md hover:bg-[#153570] transition-colors text-sm font-medium">
                        <i class="fa-solid fa-plus mr-2"></i><span id="submitButtonText">Add PC Unit</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</main>

<script>
// Get next available terminal number
async function getNextAvailableNumber(prefix, startNumber = 1) {
    try {
        const formData = new FormData();
        formData.append('ajax', '1');
        formData.append('action', 'get_next_available_number');
        formData.append('prefix', prefix);
        formData.append('start', startNumber);

        const response = await fetch(location.href, {
            method: 'POST',
            body: formData
        });

        const result = await response.json();
        return result.success ? result.next_number : null;
    } catch (error) {
        console.error('Error getting next available number:', error);
        return null;
    }
}

// Auto-suggest terminal number for single mode
async function autoSuggestTerminalNumber() {
    const terminalInput = document.getElementById('singleTerminalNumber');
    if (!terminalInput || terminalInput.value.trim()) return; // Don't override if user already typed
    
    const assetDateInput = document.getElementById('singleAssetDate').value;
    if (!assetDateInput) return;
    
    const [year, month, day] = assetDateInput.split('-');
    const dateStr = month + '-' + day + '-' + year;
    const roomName = '<?php echo $room['name']; ?>';
    
    const result = await getNextAvailableAssetTag(dateStr, roomName, 1);
    if (result && result.next_number !== null) {
        terminalInput.value = 'PC-' + String(result.next_number).padStart(3, '0');
        updateAssetTagPreview();
    }
}

// Get next available asset tag
async function getNextAvailableAssetTag(date, roomName, startNumber = 1) {
    try {
        const formData = new FormData();
        formData.append('ajax', '1');
        formData.append('action', 'get_next_available_asset_tag');
        formData.append('date', date);
        formData.append('room_name', roomName);
        formData.append('start', startNumber);

        const response = await fetch(location.href, {
            method: 'POST',
            body: formData
        });

        const result = await response.json();
        return result.success ? result : null;
    } catch (error) {
        console.error('Error getting next available asset tag:', error);
        return null;
    }
}

// Handle date change to update terminal number and asset tag
async function handleDateChange() {
    const assetDateInput = document.getElementById('singleAssetDate').value;
    const terminalInput = document.getElementById('singleTerminalNumber');
    
    if (!assetDateInput) return;
    
    const [year, month, day] = assetDateInput.split('-');
    const dateStr = month + '-' + day + '-' + year;
    const roomName = '<?php echo $room['name']; ?>';
    
    // Get current terminal number or start from 1
    const currentTerminal = terminalInput.value.trim();
    const currentNum = currentTerminal ? parseInt(currentTerminal.replace(/[^0-9]/g, '')) || 1 : 1;
    
    const result = await getNextAvailableAssetTag(dateStr, roomName, currentNum);
    if (result && result.next_number !== null) {
        const prefix = currentTerminal.match(/^[A-Za-z-]+/)?.[0] || 'PC-';
        terminalInput.value = prefix + String(result.next_number).padStart(3, '0');
        updateAssetTagPreview();
    }
}

// Auto-update bulk range when prefix changes
async function autoUpdateBulkRange() {
    updateBulkAssetTagPreview();
}

// Load PC Components for selection
async function loadPCComponents() {
    try {
        const formData = new FormData();
        formData.append('ajax', '1');
        formData.append('action', 'get_pc_categories');

        const response = await fetch(location.href, {
            method: 'POST',
            body: formData
        });

        const result = await response.json();

        if (result.success && result.categories) {
            const container = document.getElementById('pcComponentsContainer');
            container.innerHTML = '';

            result.categories.forEach(category => {
                const componentDiv = document.createElement('div');
                componentDiv.className = 'component-item bg-white border border-gray-200 rounded-lg p-4';
                componentDiv.innerHTML = `
                    <div class="space-y-3">
                        <div class="flex items-center">
                            <input type="checkbox"
                                   id="component_${category.id}"
                                   name="selected_components[]"
                                   value="${category.id}"
                                   onchange="toggleComponentSpecs(${category.id})"
                                   class="mr-2 text-[#1E3A8A] focus:ring-[#1E3A8A] w-4 h-4">
                            <label for="component_${category.id}" class="text-sm font-medium text-gray-800 cursor-pointer">
                                ${category.name}
                            </label>
                        </div>
                        <div id="specs_${category.id}" class="component-specs hidden ml-6 space-y-3">
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Component Asset Tag Date</label>
                                <input type="date" id="component_date_${category.id}" name="component_date_${category.id}"
                                       class="w-full px-2 py-1 border border-gray-300 rounded focus:ring-2 focus:ring-[#1E3A8A] focus:border-transparent text-sm"
                                       value="<?php echo date('Y-m-d'); ?>"
                                       oninput="updateComponentAssetTagPreview(${category.id}, '${category.name}')">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Asset Tag (Preview)</label>
                                <input type="text" id="component_tag_${category.id}" readonly
                                       class="w-full px-2 py-1 bg-gray-100 border border-gray-300 rounded text-xs text-gray-600 cursor-not-allowed"
                                       placeholder="DATE-<?php echo $room['name']; ?>-${category.name.toUpperCase()}-001">
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 mb-1">Brand</label>
                                    <input type="text"
                                           name="component_brand_${category.id}"
                                           class="w-full px-2 py-1 border border-gray-300 rounded focus:ring-2 focus:ring-[#1E3A8A] focus:border-transparent text-sm"
                                           placeholder="e.g., Dell">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 mb-1">Model</label>
                                    <input type="text"
                                           name="component_model_${category.id}"
                                           class="w-full px-2 py-1 border border-gray-300 rounded focus:ring-2 focus:ring-[#1E3A8A] focus:border-transparent text-sm"
                                           placeholder="e.g., DDR4-8GB">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 mb-1">Serial Number</label>
                                    <input type="text"
                                           name="component_serial_${category.id}"
                                           class="w-full px-2 py-1 border border-gray-300 rounded focus:ring-2 focus:ring-[#1E3A8A] focus:border-transparent text-sm"
                                           placeholder="Optional">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 mb-1">Condition</label>
                                    <select name="component_condition_${category.id}"
                                            class="w-full px-2 py-1 border border-gray-300 rounded focus:ring-2 focus:ring-[#1E3A8A] focus:border-transparent text-sm">
                                        <option value="Good">Good</option>
                                        <option value="Excellent">Excellent</option>
                                        <option value="Fair">Fair</option>
                                        <option value="Poor">Poor</option>
                                        <option value="Non-Functional">Non-Functional</option>
                                    </select>
                                </div>
                                <div class="col-span-2">
                                    <label class="block text-xs font-medium text-gray-700 mb-1">End of Life (Optional)</label>
                                    <input type="date"
                                           name="component_eol_${category.id}"
                                           class="w-full px-2 py-1 border border-gray-300 rounded focus:ring-2 focus:ring-[#1E3A8A] focus:border-transparent text-sm">
                                </div>
                            </div>
                        </div>
                    </div>
                `;
                container.appendChild(componentDiv);
            });
        }
    } catch (error) {
        console.error('Error loading PC components:', error);
    }
}

// Update asset tag preview
async function updateAssetTagPreview() {
    const terminalNumber = document.getElementById('singleTerminalNumber').value.trim();
    const assetDateInput = document.getElementById('singleAssetDate').value;
    const assetTagPreview = document.getElementById('assetTagPreview');
    const terminalInput = document.getElementById('singleTerminalNumber');
    
    if (terminalNumber && assetDateInput) {
        // Parse date properly to avoid timezone issues
        const [year, month, day] = assetDateInput.split('-');
        const dateStr = month + '-' + day + '-' + year;
        const roomName = '<?php echo $room['name']; ?>';
        const terminalNum = parseInt(terminalNumber.replace(/[^0-9]/g, '')) || 1;
        
        // Check if asset tag already exists and find next available
        const result = await getNextAvailableAssetTag(dateStr, roomName, terminalNum);
        if (result && result.next_number !== null) {
            if (result.next_number !== terminalNum) {
                // Update terminal number to match available asset tag
                const prefix = terminalNumber.match(/^[A-Za-z-]+/)?.[0] || 'PC-';
                terminalInput.value = prefix + String(result.next_number).padStart(3, '0');
            }
            assetTagPreview.value = result.asset_tag;
        } else {
            // Fallback to manual generation if check fails
            const paddedNum = String(terminalNum).padStart(3, '0');
            assetTagPreview.value = dateStr + '-' + roomName + '-TH-' + paddedNum;
        }
    } else {
        assetTagPreview.value = '';
    }
}

// Update bulk asset tag preview
async function updateBulkAssetTagPreview() {
    const assetDateInput = document.getElementById('bulkAssetDate').value;
    const quantity = parseInt(document.getElementById('bulkQuantity').value) || 1;
    const prefix = document.getElementById('bulkPrefix').value;
    const previewFirst = document.getElementById('bulkAssetTagPreviewFirst');
    const previewLast = document.getElementById('bulkAssetTagPreviewLast');
    
    if (assetDateInput && quantity > 0) {
        const [year, month, day] = assetDateInput.split('-');
        const dateStr = month + '-' + day + '-' + year;
        const roomName = '<?php echo $room['name']; ?>';
        
        // Get next available number for the start
        const result = await getNextAvailableAssetTag(dateStr, roomName, 1);
        const startNum = result && result.next_number ? result.next_number : 1;
        const endNum = startNum + quantity - 1;
        
        // Update hidden fields
        document.getElementById('bulkRangeStart').value = startNum;
        document.getElementById('bulkRangeEnd').value = endNum;
        
        // Update preview
        const startNumPadded = String(startNum).padStart(3, '0');
        const endNumPadded = String(endNum).padStart(3, '0');
        previewFirst.value = dateStr + '-' + roomName + '-TH-' + startNumPadded;
        previewLast.value = dateStr + '-' + roomName + '-TH-' + endNumPadded;
    } else {
        previewFirst.value = '';
        previewLast.value = '';
    }
}

// Update component asset tag preview
function updateComponentAssetTagPreview(categoryId, categoryName) {
    const componentDate = document.getElementById('component_date_' + categoryId).value;
    const componentTag = document.getElementById('component_tag_' + categoryId);
    
    if (componentDate) {
        const [year, month, day] = componentDate.split('-');
        const dateStr = month + '-' + day + '-' + year;
        const roomName = '<?php echo $room['name']; ?>';
        componentTag.value = dateStr + '-' + roomName + '-' + categoryName.toUpperCase() + '-001';
    } else {
        componentTag.value = '';
    }
}

// Toggle component specifications visibility
function toggleComponentSpecs(categoryId) {
    const checkbox = document.getElementById(`component_${categoryId}`);
    const specsDiv = document.getElementById(`specs_${categoryId}`);

    if (checkbox.checked) {
        specsDiv.classList.remove('hidden');
    } else {
        specsDiv.classList.add('hidden');
        // Clear the specification fields when unchecked
        const inputs = specsDiv.querySelectorAll('input, select');
        inputs.forEach(input => input.value = '');
    }
}

// Toggle Bulk Mode
function toggleBulkMode() {
    const bulkMode = document.querySelector('input[name="bulk_mode"]:checked').value;
    const singleFields = document.getElementById('singleModeFields');
    const bulkFields = document.getElementById('bulkModeFields');
    const singleTerminal = document.getElementById('singleTerminalNumber');
    const submitButton = document.getElementById('submitButtonText');

    if (bulkMode === 'bulk') {
        singleFields.classList.add('hidden');
        bulkFields.classList.remove('hidden');
        singleTerminal.removeAttribute('required');
        submitButton.textContent = 'Create Identical PCs';
    } else {
        singleFields.classList.remove('hidden');
        bulkFields.classList.add('hidden');
        singleTerminal.setAttribute('required', 'required');
        submitButton.textContent = 'Add PC Unit';
    }
}

// Submit PC Unit Form
function submitPCUnit() {
    const form = document.getElementById('pcUnitForm');
    const bulkMode = document.querySelector('input[name="bulk_mode"]:checked').value;

    // Validation
    if (bulkMode === 'single') {
        const terminalNumber = document.getElementById('singleTerminalNumber').value.trim();
        
        if (!terminalNumber) {
            showAlert('error', 'Terminal number is required');
            document.getElementById('singleTerminalNumber').focus();
            return;
        }
        
        // Validate terminal number format (should contain letters and numbers)
        if (!/^[A-Za-z]+-\d+$/.test(terminalNumber)) {
            showAlert('error', 'Invalid terminal number format. Expected format: PC-001');
            document.getElementById('singleTerminalNumber').focus();
            return;
        }
        
    } else {
        const prefix = form.querySelector('input[name="prefix"]').value.trim();
        const quantity = parseInt(form.querySelector('input[name="quantity"]').value);
        const rangeStart = parseInt(form.querySelector('input[name="range_start"]').value);
        const rangeEnd = parseInt(form.querySelector('input[name="range_end"]').value);

        if (!prefix) {
            showAlert('error', 'Prefix is required for bulk creation');
            form.querySelector('input[name="prefix"]').focus();
            return;
        }
        
        // Validate prefix format (should contain at least one letter and end with hyphen)
        if (!/^[A-Za-z]+-$/.test(prefix)) {
            showAlert('error', 'Invalid prefix format. Expected format: PC- or LAB-');
            form.querySelector('input[name="prefix"]').focus();
            return;
        }

        if (isNaN(quantity) || quantity < 1) {
            showAlert('error', 'Quantity must be at least 1');
            form.querySelector('input[name="quantity"]').focus();
            return;
        }

        if (quantity > 100) {
            showAlert('error', 'Maximum 100 units can be created at once');
            form.querySelector('input[name="quantity"]').focus();
            return;
        }
        
        if (quantity === 1) {
            showAlert('warning', 'You are creating only 1 unit. Consider using Single PC mode instead.');
            // Allow to continue but warn user
        }

        // Show bulk creation confirmation modal
        const range = `${prefix}${String(rangeStart).padStart(2, '0')} to ${prefix}${String(rangeEnd).padStart(2, '0')}`;
        openBulkCreatePCModal(quantity, range, form);
        return;
    }

    // For single mode, proceed directly
    proceedWithPCCreation();
}

// Bulk Create PC Modal Functions
let currentBulkCreateForm = null;

function openBulkCreatePCModal(count, range, form) {
    currentBulkCreateForm = form;
    const modal = document.getElementById('bulkCreatePCModal');
    document.getElementById('bulkCreateCount').textContent = count;
    document.getElementById('bulkCreateRange').textContent = range;
    modal.classList.remove('hidden');
}

function closeBulkCreatePCModal() {
    const modal = document.getElementById('bulkCreatePCModal');
    modal.classList.add('hidden');
    currentBulkCreateForm = null;
}

function confirmBulkCreatePC() {
    if (!currentBulkCreateForm) return;
    closeBulkCreatePCModal();
    proceedWithPCCreation();
}

// Close modal when clicking outside
document.getElementById('bulkCreatePCModal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        closeBulkCreatePCModal();
    }
});

function proceedWithPCCreation() {
    const form = document.getElementById('pcUnitForm');
    const formData = new FormData(form);
    formData.append('ajax', '1');
    formData.append('action', 'create_pc_unit');

    // Disable submit button
    const submitBtn = document.querySelector('button[onclick="submitPCUnit()"]');
    const originalText = submitBtn ? submitBtn.innerHTML : '';
    if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-2"></i>Creating...';
    }

    fetch(location.href, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const bulkMode = document.querySelector('input[name="bulk_mode"]:checked').value;
            if (bulkMode === 'bulk' && data.created) {
                showAlert('success', `Successfully created ${data.created} PC units!`);
            } else {
                showAlert('success', data.message);
            }
            setTimeout(() => window.location.href = `roomassets.php?room_id=<?php echo $room_id; ?>`, 1500);
        } else {
            showAlert('error', data.message);
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('error', 'An error occurred while creating the PC unit(s)');
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
    });
}

// Alert function
function showAlert(type, message) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `fixed top-4 right-4 z-50 px-6 py-3 rounded-lg shadow-lg ${
        type === 'success' ? 'bg-green-500' : 'bg-red-500'
    } text-white font-medium text-lg`;
    alertDiv.textContent = message;

    document.body.appendChild(alertDiv);

    setTimeout(() => alertDiv.remove(), 5000);
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    loadPCComponents();
    toggleBulkMode(); // Initialize form state
    
    // Auto-suggest terminal number for single mode on page load
    setTimeout(autoSuggestTerminalNumber, 500);
    
    // Auto-update bulk preview on page load
    setTimeout(updateBulkAssetTagPreview, 500);
    
    // Add event listeners for bulk mode auto-update
    const bulkPrefix = document.getElementById('bulkPrefix');
    
    if (bulkPrefix) {
        bulkPrefix.addEventListener('blur', autoUpdateBulkRange);
    }
});
</script>

<!-- Bulk Create PC Units Confirmation Modal -->
<div id="bulkCreatePCModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50 flex items-center justify-center">
    <div class="relative mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3 text-center">
            <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-blue-100 mb-4">
                <i class="fa-solid fa-desktop text-blue-600 text-xl"></i>
            </div>
            <h3 class="text-lg leading-6 font-medium text-gray-900 mb-2">Confirm Bulk PC Creation</h3>
            <div class="mt-2 px-7 py-3">
                <p class="text-sm text-gray-500 mb-4">
                    You are about to create <span id="bulkCreateCount" class="font-semibold text-gray-900"></span> PC units with the following range:
                </p>
                <div class="bg-gray-50 p-3 rounded-lg border">
                    <strong>Terminal Numbers:</strong> <span id="bulkCreateRange" class="text-blue-600 font-mono"></span>
                </div>
            </div>
            <div class="flex items-center px-4 py-3 gap-3">
                <button id="confirmBulkCreatePCBtn" onclick="confirmBulkCreatePC()"
                        class="flex-1 px-4 py-2 bg-blue-600 text-white text-base font-medium rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <i class="fa-solid fa-plus mr-2"></i>Create PCs
                </button>
                <button onclick="closeBulkCreatePCModal()"
                        class="flex-1 px-4 py-2 bg-gray-300 text-gray-900 text-base font-medium rounded-md hover:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-500">
                    Cancel
                </button>
            </div>
        </div>
    </div>
</div>

<?php include '../components/layout_footer.php'; ?>
