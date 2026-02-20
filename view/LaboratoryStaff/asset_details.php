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

// Establish mysqli database connection
$dbConfig = Config::database();
try {
    $conn = new mysqli($dbConfig['host'], $dbConfig['username'], $dbConfig['password'], $dbConfig['name']);
    $conn->set_charset('utf8mb4');
} catch (mysqli_sql_exception $e) {
    die("Database connection error");
}

// Get filter parameter
$filter = isset($_GET['filter']) ? $_GET['filter'] : null;

if (!$filter) {
    header("Location: index.php");
    exit();
}

// Build page title and query based on filter
$pageTitle = '';
$whereClause = '';

switch ($filter) {
    case 'Available':
        $pageTitle = "Available Assets";
        $whereClause = "a.status = 'Available'";
        break;
    case 'In Use':
        $pageTitle = "Assets In Use";
        $whereClause = "a.status = 'In Use'";
        break;
    case 'Fair':
        $pageTitle = "Assets Needing Attention (Fair Condition)";
        $whereClause = "a.condition = 'Fair' AND a.status IN ('Available', 'In Use')";
        break;
    case 'Critical':
        $pageTitle = "Critical Assets (Poor/Non-Functional)";
        $whereClause = "a.condition IN ('Non-Functional', 'Poor')";
        break;
    case 'Healthy':
        $pageTitle = "Healthy Assets (Good/Excellent Condition)";
        $whereClause = "a.condition IN ('Good', 'Excellent')";
        break;
    case 'All':
        $pageTitle = "All Assets";
        $whereClause = "1=1";
        break;
    default:
        header("Location: index.php");
        exit();
}

// Fetch assets with related PC information
$query = "
    SELECT 
        a.id,
        a.asset_tag,
        a.asset_name,
        a.category,
        a.brand,
        a.model,
        a.serial_number,
        a.status,
        a.`condition`,
        a.created_at,
        pc.terminal_number,
        r.name as room_name,
        b.name as building_name,
        CASE 
            WHEN a.status = 'In Use' AND pc.id IS NOT NULL THEN CONCAT(r.name, ' - ', b.name)
            ELSE 'Storage/Not Assigned'
        END as location
    FROM assets a
    LEFT JOIN pc_units pc ON a.pc_unit_id = pc.id
    LEFT JOIN rooms r ON pc.room_id = r.id
    LEFT JOIN buildings b ON r.building_id = b.id
    WHERE $whereClause
    ORDER BY 
        CASE a.condition
            WHEN 'Non-Functional' THEN 1
            WHEN 'Poor' THEN 2
            WHEN 'Fair' THEN 3
            WHEN 'Good' THEN 4
            WHEN 'Excellent' THEN 5
        END ASC,
        a.asset_name ASC
";

$result = $conn->query($query);

$assets = [];
$totalAssets = 0;

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $assets[] = $row;
        $totalAssets++;
    }
}

// Calculate stats
$categoryCounts = [];
foreach ($assets as $asset) {
    $cat = $asset['category'] ?? 'Unknown';
    $categoryCounts[$cat] = ($categoryCounts[$cat] ?? 0) + 1;
}

include '../components/layout_header.php';
?>

<style>
    .asset-card { transition: all 0.2s; }
    .asset-card:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
    .condition-excellent { border-left: 4px solid #10B981; }
    .condition-good { border-left: 4px solid #3B82F6; }
    .condition-fair { border-left: 4px solid #F59E0B; }
    .condition-poor { border-left: 4px solid #EF4444; }
    .condition-non-functional { border-left: 4px solid #7F1D1D; }
</style>

<!-- Main Content -->
<main class="p-6 bg-gray-50 min-h-screen">
    <!-- Header -->
    <div class="mb-6">
        <div class="flex items-center justify-between mb-2">
            <div>
                <h1 class="text-2xl font-bold text-gray-900"><?php echo $pageTitle; ?></h1>
                <p class="text-sm text-gray-600 mt-1">Detailed list of assets matching the selected filter</p>
            </div>
            <a href="index.php" class="bg-blue-900 text-white px-4 py-2 rounded-lg hover:bg-blue-800 transition-colors">
                <i class="fas fa-arrow-left mr-2"></i>Back to Dashboard
            </a>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 mb-1">Total Assets</p>
                    <p class="text-3xl font-bold text-blue-900"><?php echo $totalAssets; ?></p>
                </div>
                <div class="bg-blue-100 p-3 rounded-full">
                    <i class="fas fa-boxes text-2xl text-blue-900"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 mb-1">Categories</p>
                    <p class="text-3xl font-bold text-blue-900"><?php echo count($categoryCounts); ?></p>
                </div>
                <div class="bg-purple-100 p-3 rounded-full">
                    <i class="fas fa-tags text-2xl text-purple-900"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 mb-1">Filter Applied</p>
                    <p class="text-lg font-bold text-blue-900"><?php echo htmlspecialchars($filter); ?></p>
                </div>
                <div class="bg-amber-100 p-3 rounded-full">
                    <i class="fas fa-filter text-2xl text-amber-900"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Assets List -->
    <?php if (empty($assets)): ?>
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-8 text-center">
            <i class="fas fa-inbox text-6xl text-gray-300 mb-4"></i>
            <h3 class="text-xl font-semibold text-gray-700 mb-2">No Assets Found</h3>
            <p class="text-gray-500">There are no assets matching this filter.</p>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <?php foreach ($assets as $asset): ?>
                <div class="asset-card bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden condition-<?php echo strtolower(str_replace(' ', '-', $asset['condition'])); ?>">
                    <div class="p-4">
                        <!-- Asset Header -->
                        <div class="flex items-start justify-between mb-3">
                            <div class="flex-1">
                                <h3 class="text-lg font-bold text-gray-900 mb-1">
                                    <?php echo htmlspecialchars($asset['asset_name']); ?>
                                </h3>
                                <p class="text-xs text-gray-500 font-mono">
                                    <?php echo htmlspecialchars($asset['asset_tag']); ?>
                                </p>
                            </div>
                            <div class="text-right">
                                <span class="px-2 py-1 text-xs font-medium rounded <?php
                                    echo $asset['status'] === 'Available' ? 'bg-green-100 text-green-800' :
                                         ($asset['status'] === 'In Use' ? 'bg-blue-100 text-blue-800' :
                                         'bg-gray-100 text-gray-800');
                                ?>">
                                    <?php echo htmlspecialchars($asset['status']); ?>
                                </span>
                            </div>
                        </div>

                        <!-- Asset Details -->
                        <div class="space-y-2 mb-3">
                            <div class="flex items-center text-sm">
                                <i class="fas fa-tag w-5 text-gray-400"></i>
                                <span class="text-gray-700"><?php echo htmlspecialchars($asset['category']); ?></span>
                            </div>
                            
                            <?php if ($asset['brand']): ?>
                                <div class="flex items-center text-sm">
                                    <i class="fas fa-copyright w-5 text-gray-400"></i>
                                    <span class="text-gray-700">
                                        <?php echo htmlspecialchars($asset['brand']); ?>
                                        <?php if ($asset['model']): ?>
                                            - <?php echo htmlspecialchars($asset['model']); ?>
                                        <?php endif; ?>
                                    </span>
                                </div>
                            <?php endif; ?>

                            <?php if ($asset['terminal_number']): ?>
                                <div class="flex items-center text-sm">
                                    <i class="fas fa-desktop w-5 text-gray-400"></i>
                                    <span class="text-gray-700">PC: <?php echo htmlspecialchars($asset['terminal_number']); ?></span>
                                </div>
                            <?php endif; ?>

                            <div class="flex items-center text-sm">
                                <i class="fas fa-map-marker-alt w-5 text-gray-400"></i>
                                <span class="text-gray-700"><?php echo htmlspecialchars($asset['location']); ?></span>
                            </div>

                            <?php if ($asset['serial_number']): ?>
                                <div class="flex items-center text-sm">
                                    <i class="fas fa-barcode w-5 text-gray-400"></i>
                                    <span class="text-gray-700 font-mono text-xs"><?php echo htmlspecialchars($asset['serial_number']); ?></span>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Condition Badge -->
                        <div class="pt-3 border-t border-gray-200">
                            <div class="flex items-center justify-between">
                                <span class="text-xs text-gray-500">Condition:</span>
                                <span class="px-2 py-1 text-xs font-bold rounded <?php
                                    echo $asset['condition'] === 'Excellent' ? 'bg-green-100 text-green-800' :
                                         ($asset['condition'] === 'Good' ? 'bg-blue-100 text-blue-800' :
                                         ($asset['condition'] === 'Fair' ? 'bg-yellow-100 text-yellow-800' :
                                         ($asset['condition'] === 'Poor' ? 'bg-orange-100 text-orange-800' :
                                         'bg-red-100 text-red-800')));
                                ?>">
                                    <?php echo htmlspecialchars($asset['condition']); ?>
                                </span>
                            </div>

                            <?php if ($asset['created_at']): ?>
                                <div class="mt-2 text-xs text-gray-500">
                                    <i class="far fa-calendar mr-1"></i>
                                    Added: <?php echo date('M d, Y', strtotime($asset['created_at'])); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</main>

<?php include '../components/layout_footer.php'; ?>
