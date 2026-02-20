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
";

$result = $conn->query($query);

// Group issues by PC
$pcGroups = [];
$totalIssues = 0;

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
        $totalIssues++;
    }
}

// Calculate summary stats
$totalPCs = count($pcGroups);
$avgIssuesPerPC = $totalPCs > 0 ? round($totalIssues / $totalPCs, 1) : 0;

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
                <p class="text-3xl font-bold text-blue-900"><?php echo $totalIssues; ?></p>
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
    <?php endif; ?>
</main>

<?php include '../components/layout_footer.php'; ?>
