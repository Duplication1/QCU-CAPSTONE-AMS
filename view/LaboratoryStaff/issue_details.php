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

// Get filter parameters
$filterStatus = isset($_GET['status']) ? $_GET['status'] : null;
$filterCategory = isset($_GET['category']) ? $_GET['category'] : null;

// Build page title and query
$pageTitle = '';
$whereClause = '';
$filterValue = '';
$itemsPerPage = 12;
$currentPage = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;

if ($filterStatus) {
    $filterValue = $filterStatus;
    $pageTitle = "Issues with Status: " . htmlspecialchars($filterStatus);
    if ($filterStatus === 'Open') {
        $whereClause = "i.status = 'Open' AND (i.assigned_technician IS NULL OR i.assigned_technician = '') AND (i.category IS NULL OR i.category != 'borrow')";
    } else {
        $whereClause = "i.status = '" . $conn->real_escape_string($filterStatus) . "' AND (i.category IS NULL OR i.category != 'borrow')";
    }
} elseif ($filterCategory) {
    $filterValue = $filterCategory;
    $pageTitle = "Issues with Category: " . htmlspecialchars($filterCategory);
    $whereClause = "i.category = '" . $conn->real_escape_string($filterCategory) . "' AND i.status != 'Resolved' AND i.category != 'borrow'";
} else {
    header("Location: index.php");
    exit();
}

// Count total filtered issues for pagination
$countQuery = "
    SELECT COUNT(*) as total
    FROM issues i
    WHERE $whereClause
";
$countResult = $conn->query($countQuery);
$totalFilteredIssues = ($countResult && $countResult->num_rows > 0) ? (int)$countResult->fetch_assoc()['total'] : 0;

$totalPages = max(1, (int)ceil($totalFilteredIssues / $itemsPerPage));
if ($currentPage > $totalPages) {
    $currentPage = $totalPages;
}
$offset = ($currentPage - 1) * $itemsPerPage;

// Fetch issues grouped by PC
$query = "
    SELECT 
        i.id as issue_id,
        i.description,
        i.category,
        i.status,
        i.priority,
        i.created_at,
        i.assigned_at,
        u.terminal_number,
        u.id as pc_id,
        r.name as room_name,
        b.name as building_name,
        tech.full_name as tech_name
    FROM issues i
    LEFT JOIN pc_units u ON i.pc_id = u.id
    LEFT JOIN rooms r ON u.room_id = r.id
    LEFT JOIN buildings b ON r.building_id = b.id
    LEFT JOIN users tech ON i.assigned_technician = tech.id
    WHERE $whereClause
    ORDER BY u.terminal_number ASC, i.created_at DESC
    LIMIT " . (int)$itemsPerPage . " OFFSET " . (int)$offset;

$result = $conn->query($query);

// Group issues by PC
$pcGroups = [];
$currentPageIssueCount = 0;

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $pcKey = $row['terminal_number'] ?? 'Unknown PC';
        
        if (!isset($pcGroups[$pcKey])) {
            $pcGroups[$pcKey] = [
                'pc_id' => $row['pc_id'],
                'terminal_number' => $row['terminal_number'],
                'room_name' => $row['room_name'],
                'building_name' => $row['building_name'],
                'issues' => []
            ];
        }
        
        $pcGroups[$pcKey]['issues'][] = $row;
        $currentPageIssueCount++;
    }
}

// Calculate summary stats
$totalPCsQuery = "
    SELECT COUNT(DISTINCT i.pc_id) as total_pcs
    FROM issues i
    WHERE $whereClause
";
$totalPCsResult = $conn->query($totalPCsQuery);
$totalPCs = ($totalPCsResult && $totalPCsResult->num_rows > 0) ? (int)$totalPCsResult->fetch_assoc()['total_pcs'] : 0;
$avgIssuesPerPC = $totalPCs > 0 ? round($totalFilteredIssues / $totalPCs, 1) : 0;
$startItem = $totalFilteredIssues > 0 ? $offset + 1 : 0;
$endItem = min($offset + $itemsPerPage, $totalFilteredIssues);

function buildPageUrl($page) {
    $params = $_GET;
    $params['page'] = $page;
    return '?' . http_build_query($params);
}

include '../components/layout_header.php';
?>

<style>
  table { border-collapse: collapse; width: 100%; }
  th, td { padding: 8px; text-align: center; } 
  thead th { background-color: #1E3A8A; color: white; } 
  tbody tr { border-bottom: 1px solid #ddd; }
  tbody tr:hover { background-color: #eff6ff; }
  .priority-high { background-color: #fee2e2; }
  .priority-medium { background-color: #ffedd5; }
  .priority-low { background-color: #d1fae5; }
</style>

<!-- Main Content -->
<main class="p-6 bg-gray-50 min-h-screen">
    <!-- Header -->
    <div class="mb-6">
        <div class="flex items-center justify-between mb-2">
            <div>
                <h1 class="text-2xl font-bold text-gray-900"><?php echo $pageTitle; ?></h1>
                <p class="text-sm text-gray-600 mt-1">
                    Detailed breakdown of computers with issues in this <?php echo $filterStatus ? 'status' : 'category'; ?>
                </p>
                <p class="text-xs text-gray-500 mt-1">Showing <?php echo $startItem; ?>-<?php echo $endItem; ?> of <?php echo $totalFilteredIssues; ?> issues</p>
            </div>
            <a href="index.php" class="bg-blue-900 text-white px-4 py-2 rounded-lg hover:bg-blue-800 transition-colors">
                <i class="fas fa-arrow-left mr-2"></i>Back to Dashboard
            </a>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-600 mb-1">Total Issues</p>
                <p class="text-3xl font-bold text-blue-900"><?php echo $totalFilteredIssues; ?></p>
            </div>
            <div class="bg-blue-100 p-3 rounded-full">
                <i class="fas fa-exclamation-circle text-2xl text-blue-900"></i>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-600 mb-1">Affected PCs</p>
                <p class="text-3xl font-bold text-blue-900"><?php echo $totalPCs; ?></p>
            </div>
            <div class="bg-purple-100 p-3 rounded-full">
                <i class="fas fa-desktop text-2xl text-purple-900"></i>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-600 mb-1">Avg Issues per PC</p>
                <p class="text-3xl font-bold text-blue-900"><?php echo $avgIssuesPerPC; ?></p>
            </div>
            <div class="bg-amber-100 p-3 rounded-full">
                <i class="fas fa-chart-bar text-2xl text-amber-900"></i>
            </div>
        </div>
    </div>

    <!-- Issues Table -->
    <?php if (empty($pcGroups)): ?>
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-8 text-center">
            <i class="fas fa-inbox text-6xl text-gray-300 mb-4"></i>
            <h3 class="text-xl font-semibold text-gray-700 mb-2">No Issues Found</h3>
            <p class="text-gray-500">There are no issues matching this <?php echo $filterStatus ? 'status' : 'category'; ?>.</p>
        </div>
    <?php else: ?>
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-x-auto">
            <table class="min-w-full text-sm text-gray-700">
                <thead>
                    <tr>
                        <th>PC Name</th>
                        <th>Location</th>
                        <th>Issue ID</th>
                        <th>Status</th>
                        <th>Priority</th>
                        <th>Category</th>
                        <th>Description</th>
                        <th>Reported</th>
                        <th>Assigned</th>
                        <th>Technician</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pcGroups as $pcData): ?>
                        <?php foreach ($pcData['issues'] as $issue): ?>
                            <tr class="hover:bg-blue-50">
                                <td><?php echo htmlspecialchars($pcData['terminal_number'] ?? 'Unknown PC'); ?></td>
                                <td>
                                    <?php echo htmlspecialchars($pcData['room_name'] ?? 'Unknown Room'); ?> -
                                    <?php echo htmlspecialchars($pcData['building_name'] ?? 'Unknown Building'); ?>
                                </td>
                                <td>#<?php echo $issue['issue_id']; ?></td>
                                <td><?php echo htmlspecialchars($issue['status']); ?></td>
                                <td><?php echo htmlspecialchars($issue['priority']); ?></td>
                                <td><?php echo htmlspecialchars($issue['category']); ?></td>
                                <td><?php echo htmlspecialchars($issue['description']); ?></td>
                                <td><?php echo date('M d, Y g:i A', strtotime($issue['created_at'])); ?></td>
                                <td>
                                    <?php if (!empty($issue['assigned_at'])): ?>
                                        <?php echo date('M d, Y', strtotime($issue['assigned_at'])); ?>
                                    <?php else: ?>
                                        —
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($issue['tech_name'] ?? '—'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if ($totalPages > 1): ?>
            <div class="mt-6 bg-white border border-gray-200 rounded-lg p-4">
                <div class="flex items-center justify-between">
                    <a href="<?php echo buildPageUrl(max(1, $currentPage - 1)); ?>"
                       class="px-3 py-1.5 rounded border border-gray-300 text-sm font-semibold <?php echo $currentPage <= 1 ? 'pointer-events-none opacity-50' : 'hover:bg-gray-50'; ?>">
                        Previous
                    </a>

                    <span class="text-sm text-gray-600 font-medium">
                        Page <?php echo $currentPage; ?> of <?php echo $totalPages; ?>
                    </span>

                    <a href="<?php echo buildPageUrl(min($totalPages, $currentPage + 1)); ?>"
                       class="px-3 py-1.5 rounded border border-gray-300 text-sm font-semibold <?php echo $currentPage >= $totalPages ? 'pointer-events-none opacity-50' : 'hover:bg-gray-50'; ?>">
                        Next
                    </a>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</main>

<?php include '../components/layout_footer.php'; ?>