<?php
// Prevent page caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

session_start();

// Check if user is logged in and has Administrator role
if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true || $_SESSION['role'] !== 'Administrator') {
    header("Location: ../login.php");
    exit();
}

require_once '../../config/config.php';

// Create database connection
if (!isset($conn)) {
    $conn = new mysqli('localhost', 'root', '', 'ams_database');
    if ($conn->connect_error) {
        die('Connection failed: ' . $conn->connect_error);
    }
}

// Get room_id from URL
$room_id = isset($_GET['room_id']) ? intval($_GET['room_id']) : 0;

if ($room_id <= 0) {
    header("Location: building_analytics.php");
    exit();
}

// Get room and building details
$room_query = $conn->prepare("SELECT r.*, b.name as building_name, b.id as building_id 
                               FROM rooms r 
                               JOIN buildings b ON r.building_id = b.id 
                               WHERE r.id = ?");
$room_query->bind_param('i', $room_id);
$room_query->execute();
$room_result = $room_query->get_result();
$room = $room_result->fetch_assoc();
$room_query->close();

if (!$room) {
    header("Location: building_analytics.php");
    exit();
}

// Search and pagination
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$show = isset($_GET['show']) ? $_GET['show'] : '25';
$limit = ($show === 'all') ? PHP_INT_MAX : max(1, intval($show));
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $limit;

// Count total assets
$count_query = "SELECT COUNT(DISTINCT a.id) as total FROM assets a WHERE a.room_id = ?";
$params = [$room_id];
$types = 'i';

if (!empty($search)) {
    $count_query .= " AND (a.asset_tag LIKE ? OR a.asset_name LIKE ? OR a.category LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= 'sss';
}

$count_stmt = $conn->prepare($count_query);
$count_stmt->bind_param($types, ...$params);
$count_stmt->execute();
$total_result = $count_stmt->get_result();
$total_assets_db = $total_result->fetch_assoc()['total'];
$count_stmt->close();

$total_pages = ceil($total_assets_db / $limit);

// Fetch assets with detailed analytics
$assets = [];
$query = "SELECT 
    a.id,
    a.asset_tag,
    a.asset_name,
    a.category,
    a.status,
    a.condition,
    a.created_at as date_acquired,
    a.is_borrowable,
    COUNT(DISTINCT ab.id) as total_borrowings,
    COUNT(DISTINCT CASE WHEN ab.status = 'pending' THEN ab.id END) as pending_borrowings,
    COUNT(DISTINCT CASE WHEN ab.status = 'approved' THEN ab.id END) as approved_borrowings,
    COUNT(DISTINCT CASE WHEN ab.status = 'returned' THEN ab.id END) as returned_borrowings,
    COUNT(DISTINCT ah.id) as history_count,
    MAX(ah.created_at) as last_change,
    (SELECT COUNT(*) FROM issues WHERE component_asset_id = a.id) as asset_issues
FROM assets a
LEFT JOIN asset_borrowing ab ON a.id = ab.asset_id
LEFT JOIN asset_history ah ON a.id = ah.asset_id
WHERE a.room_id = ?";

$params = [$room_id];
$types = 'i';

if (!empty($search)) {
    $query .= " AND (a.asset_tag LIKE ? OR a.asset_name LIKE ? OR a.category LIKE ?)";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= 'sss';
}

$query .= " GROUP BY a.id ORDER BY a.asset_tag ASC LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;
$types .= 'ii';

$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $assets[] = $row;
    }
}
$stmt->close();

include '../components/layout_header.php';
?>

<style>
html, body {
    height: 100vh;
    overflow-y: auto;
}
#app-container {
    min-height: 100vh;
    display: flex;
    flex-direction: column;
}
main {
    flex: 1;
    display: flex;
    flex-direction: column;
    padding: 0.5rem;
    background-color: #f9fafb;
}
.badge {
    display: inline-block;
    padding: 0.25rem 0.5rem;
    font-size: 0.75rem;
    font-weight: 600;
    border-radius: 0.375rem;
}
.badge-active { background-color: #d1fae5; color: #065f46; }
.badge-disposed { background-color: #fee2e2; color: #991b1b; }
.badge-good { background-color: #d1fae5; color: #065f46; }
.badge-fair { background-color: #fef3c7; color: #92400e; }
.badge-poor { background-color: #fee2e2; color: #991b1b; }
</style>

<main>
    <div class="flex-1 flex flex-col overflow-hidden">
        
        <!-- Header -->
        <div class="flex items-center justify-between px-4 py-3 bg-white rounded shadow-sm border border-gray-200 mb-3">
            <div class="flex items-center gap-3">
                <a href="room_analytics.php?building_id=<?= $room['building_id'] ?>" class="text-gray-600 hover:text-gray-800">
                    <i class="fa-solid fa-arrow-left text-lg"></i>
                </a>
                <div>
                    <h1 class="text-xl font-bold text-gray-800"><?= htmlspecialchars($room['building_name']) ?> - <?= htmlspecialchars($room['name']) ?></h1>
                    <p class="text-sm text-gray-500">Detailed asset analytics and usage statistics</p>
                </div>
            </div>
        </div>

        <!-- Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-3 mb-3">
            <?php
            $total_assets = count($assets);
            $active_assets = count(array_filter($assets, fn($a) => in_array($a['status'], ['Available', 'In Use'])));
            $disposed_assets = count(array_filter($assets, fn($a) => $a['status'] === 'Disposed'));
            $borrowable_assets = count(array_filter($assets, fn($a) => $a['is_borrowable'] == 1));
            $total_borrowings = array_sum(array_column($assets, 'total_borrowings'));
            $total_issues = array_sum(array_column($assets, 'asset_issues'));
            ?>
            
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600">Total Assets</p>
                        <p class="text-2xl font-bold text-gray-800"><?= $total_assets ?></p>
                    </div>
                    <div class="bg-blue-100 p-3 rounded-lg">
                        <i class="fa-solid fa-box text-blue-600 text-xl"></i>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600">Active Assets</p>
                        <p class="text-2xl font-bold text-green-600"><?= $active_assets ?></p>
                    </div>
                    <div class="bg-green-100 p-3 rounded-lg">
                        <i class="fa-solid fa-check-circle text-green-600 text-xl"></i>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600">Total Borrowings</p>
                        <p class="text-2xl font-bold text-purple-600"><?= $total_borrowings ?></p>
                    </div>
                    <div class="bg-purple-100 p-3 rounded-lg">
                        <i class="fa-solid fa-exchange-alt text-purple-600 text-xl"></i>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600">Total Issues</p>
                        <p class="text-2xl font-bold text-orange-600"><?= $total_issues ?></p>
                    </div>
                    <div class="bg-orange-100 p-3 rounded-lg">
                        <i class="fa-solid fa-exclamation-triangle text-orange-600 text-xl"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Search Bar -->
        <div class="bg-white rounded shadow-sm border border-gray-200 mb-3 px-4 py-3">
            <div class="flex items-center justify-between mb-3">
                <div class="flex items-center gap-2">
                    <label class="text-sm text-gray-700">Show</label>
                    <select id="showEntries" onchange="changeShowEntries(this.value)" class="border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="10" <?= $show == '10' ? 'selected' : '' ?>>10</option>
                        <option value="25" <?= $show == '25' ? 'selected' : '' ?>>25</option>
                        <option value="100" <?= $show == '100' ? 'selected' : '' ?>>100</option>
                        <option value="all" <?= $show == 'all' ? 'selected' : '' ?>>All</option>
                    </select>
                    <label class="text-sm text-gray-700">entries</label>
                </div>
            </div>
            <form method="GET" action="" class="flex gap-3">
                <input type="hidden" name="room_id" value="<?= $room_id ?>">
                <input type="hidden" name="show" value="<?= htmlspecialchars($show) ?>">
                <div class="flex-1 relative">
                    <input type="text" 
                           name="search" 
                           value="<?= htmlspecialchars($search) ?>"
                           placeholder="Search by asset tag, name, or category..."
                           class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <i class="fa-solid fa-search absolute left-3 top-3 text-gray-400"></i>
                </div>
                <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                    <i class="fa-solid fa-search mr-2"></i>Search
                </button>
                <?php if (!empty($search)): ?>
                    <a href="?room_id=<?= $room_id ?>" class="px-6 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition-colors">
                        <i class="fa-solid fa-times mr-2"></i>Clear
                    </a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Content Area -->
        <div class="bg-white rounded shadow-sm border border-gray-200 mb-3">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Asset</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Condition</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Borrowings</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Issues</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">History</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Last Updated</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (empty($assets)): ?>
                            <tr>
                                <td colspan="8" class="px-6 py-12 text-center">
                                    <i class="fa-solid fa-box-open text-6xl text-gray-300 mb-4"></i>
                                    <p class="text-gray-500 text-lg">No assets found in this room</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($assets as $asset): ?>
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($asset['asset_tag']) ?></div>
                                        <div class="text-xs text-gray-500"><?= htmlspecialchars($asset['asset_name']) ?></div>
                                        <?php if ($asset['is_borrowable'] == 1): ?>
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800 mt-1">
                                                <i class="fa-solid fa-check mr-1"></i>Borrowable
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <span class="text-sm text-gray-700"><?= htmlspecialchars($asset['category']) ?></span>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <span class="badge badge-<?= strtolower($asset['status']) ?>">
                                            <?= ucfirst($asset['status']) ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <span class="badge badge-<?= strtolower($asset['condition']) ?>">
                                            <?= ucfirst($asset['condition']) ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <div class="text-sm">
                                            <div class="font-bold text-gray-800"><?= $asset['total_borrowings'] ?></div>
                                            <?php if ($asset['total_borrowings'] > 0): ?>
                                                <div class="text-xs text-gray-500 mt-1">
                                                    <span class="text-orange-600"><?= $asset['pending_borrowings'] ?></span> pending,
                                                    <span class="text-blue-600"><?= $asset['approved_borrowings'] ?></span> approved,
                                                    <span class="text-green-600"><?= $asset['returned_borrowings'] ?></span> returned
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <?php if ($asset['asset_issues'] > 0): ?>
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-sm font-semibold bg-red-100 text-red-800">
                                                <i class="fa-solid fa-exclamation-circle mr-1"></i><?= $asset['asset_issues'] ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-sm text-gray-400">None</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <span class="text-sm font-medium text-gray-700"><?= $asset['history_count'] ?></span>
                                        <div class="text-xs text-gray-500">changes</div>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <?php if ($asset['last_change']): ?>
                                            <div class="text-sm text-gray-700">
                                                <?= date('M d, Y', strtotime($asset['last_change'])) ?>
                                            </div>
                                            <div class="text-xs text-gray-500">
                                                <?= date('h:i A', strtotime($asset['last_change'])) ?>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-sm text-gray-400">No changes</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1 && $show !== 'all'): ?>
        <div class="bg-white rounded shadow-sm border border-gray-200 mt-3 px-4 py-3">
            <div class="flex items-center justify-between">
                <div class="text-sm text-gray-700">
                    Showing page <?= $page ?> of <?= $total_pages ?> (<?= $total_assets_db ?> total assets)
                </div>
                <div class="flex gap-2">
                    <?php if ($page > 1): ?>
                        <a href="?room_id=<?= $room_id ?>&page=<?= $page - 1 ?>&show=<?= urlencode($show) ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?>" 
                           class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                            <i class="fa-solid fa-chevron-left mr-1"></i>Previous
                        </a>
                    <?php endif; ?>
                    
                    <?php if ($page < $total_pages): ?>
                        <a href="?room_id=<?= $room_id ?>&page=<?= $page + 1 ?>&show=<?= urlencode($show) ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?>" 
                           class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                            Next<i class="fa-solid fa-chevron-right ml-1"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
        <?php if ($show === 'all'): ?>
        <div class="bg-white rounded shadow-sm border border-gray-200 mt-3 px-4 py-3">
            <div class="text-sm text-gray-700 text-center">
                Showing all <?= $total_assets_db ?> assets
            </div>
        </div>
        <?php endif; ?>
    </div>
</main>

<script>
// Change show entries
function changeShowEntries(value) {
    const urlParams = new URLSearchParams(window.location.search);
    urlParams.set('show', value);
    urlParams.set('page', '1'); // Reset to first page
    window.location.search = urlParams.toString();
}

// Close modals on Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        // Future modal handling
    }
});
</script>

<?php include '../components/layout_footer.php'; ?>
