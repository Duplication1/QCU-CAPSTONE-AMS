<?php
// Prevent page caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

session_start();

// Check if user is logged in and has technician role
if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true || $_SESSION['role'] !== 'Technician') {
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

$technician_name = $conn->real_escape_string($_SESSION['full_name'] ?? '');

// Get filter parameters
$filterStatus   = isset($_GET['status'])   ? $_GET['status']   : null;
$filterCategory = isset($_GET['category']) ? $_GET['category'] : null;
$filterPriority = isset($_GET['priority']) ? $_GET['priority'] : null;
$filterAll      = isset($_GET['filter'])   && $_GET['filter'] === 'All';

// Build page title and WHERE clause
$pageTitle   = '';
$filterLabel = '';
$whereClause = "i.assigned_technician = '$technician_name'";

if ($filterAll) {
    $pageTitle   = "All Assigned Issues";
    $filterLabel = 'filter';
    // no extra condition — show everything assigned
} elseif ($filterStatus) {
    $safe = $conn->real_escape_string($filterStatus);
    $pageTitle   = "Issues with Status: " . htmlspecialchars($filterStatus);
    $filterLabel = 'status';
    if ($filterStatus === 'Resolved') {
        // Count both Resolved and Closed
        $whereClause .= " AND i.status IN ('Resolved', 'Closed')";
    } else {
        $whereClause .= " AND i.status = '$safe'";
    }
} elseif ($filterCategory) {
    $safe = $conn->real_escape_string($filterCategory);
    $pageTitle   = "Issues with Category: " . htmlspecialchars($filterCategory);
    $filterLabel = 'category';
    $whereClause .= " AND i.category = '$safe' AND i.status NOT IN ('Resolved', 'Closed')";
} elseif ($filterPriority) {
    $safe = $conn->real_escape_string($filterPriority);
    $pageTitle   = "Issues with Priority: " . htmlspecialchars($filterPriority);
    $filterLabel = 'priority';
    $whereClause .= " AND i.priority = '$safe' AND i.status NOT IN ('Resolved', 'Closed')";
} else {
    header("Location: index.php");
    exit();
}

// Fetch issues grouped by PC
$query = "
    SELECT 
        i.id          AS issue_id,
        i.description,
        i.category,
        i.status,
        i.priority,
        i.created_at,
        i.assigned_at,
        u.terminal_number,
        u.id          AS pc_id,
        r.name        AS room_name,
        b.name        AS building_name
    FROM issues i
    LEFT JOIN pc_units u ON i.pc_id = u.id
    LEFT JOIN rooms r    ON u.room_id = r.id
    LEFT JOIN buildings b ON r.building_id = b.id
    WHERE $whereClause
    ORDER BY u.terminal_number ASC, i.created_at DESC
";

$result = $conn->query($query);

// Group issues by PC
$pcGroups   = [];
$totalIssues = 0;

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $pcKey = $row['terminal_number'] ?? 'Unknown PC';
        if (!isset($pcGroups[$pcKey])) {
            $pcGroups[$pcKey] = [
                'pc_id'           => $row['pc_id'],
                'terminal_number' => $row['terminal_number'],
                'room_name'       => $row['room_name'],
                'building_name'   => $row['building_name'],
                'issues'          => []
            ];
        }
        $pcGroups[$pcKey]['issues'][] = $row;
        $totalIssues++;
    }
}

$totalPCs        = count($pcGroups);
$avgIssuesPerPC  = $totalPCs > 0 ? round($totalIssues / $totalPCs, 1) : 0;

include '../components/layout_header.php';
?>

<style>
    .issue-card { transition: all 0.2s; }
    .issue-card:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
    .priority-high   { border-left: 4px solid #EF4444; }
    .priority-medium { border-left: 4px solid #F59E0B; }
    .priority-low    { border-left: 4px solid #10B981; }
</style>

<!-- Main Content -->
<main class="p-6 bg-gray-50 min-h-screen">
    <!-- Header -->
    <div class="mb-6">
        <div class="flex items-center justify-between mb-2">
            <div>
                <h1 class="text-2xl font-bold text-gray-900"><?php echo $pageTitle; ?></h1>
                <p class="text-sm text-gray-600 mt-1">
                    Detailed breakdown of your assigned computers with issues in this
                    <?php echo $filterLabel; ?>
                </p>
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
                    <p class="text-sm text-gray-600 mb-1">Total Issues</p>
                    <p class="text-3xl font-bold text-blue-900"><?php echo $totalIssues; ?></p>
                </div>
                <div class="bg-blue-100 p-3 rounded-full">
                    <i class="fas fa-exclamation-circle text-2xl text-blue-900"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 mb-1">Affected PCs</p>
                    <p class="text-3xl font-bold text-blue-900"><?php echo $totalPCs; ?></p>
                </div>
                <div class="bg-purple-100 p-3 rounded-full">
                    <i class="fas fa-desktop text-2xl text-purple-900"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 mb-1">Avg Issues per PC</p>
                    <p class="text-3xl font-bold text-blue-900"><?php echo $avgIssuesPerPC; ?></p>
                </div>
                <div class="bg-amber-100 p-3 rounded-full">
                    <i class="fas fa-chart-bar text-2xl text-amber-900"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- PC Groups -->
    <?php if (empty($pcGroups)): ?>
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-8 text-center">
            <i class="fas fa-inbox text-6xl text-gray-300 mb-4"></i>
            <h3 class="text-xl font-semibold text-gray-700 mb-2">No Issues Found</h3>
            <p class="text-gray-500">There are no issues matching this <?php echo $filterLabel; ?>.</p>
        </div>
    <?php else: ?>
        <div class="space-y-6">
            <?php foreach ($pcGroups as $pcKey => $pcData): ?>
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
                    <!-- PC Header -->
                    <div class="bg-gradient-to-r from-blue-900 to-blue-800 text-white p-4">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="bg-white bg-opacity-20 p-3 rounded-lg">
                                    <i class="fas fa-desktop text-2xl"></i>
                                </div>
                                <div>
                                    <h2 class="text-xl font-bold">
                                        <?php echo htmlspecialchars($pcData['terminal_number'] ?? 'Unknown PC'); ?>
                                    </h2>
                                    <p class="text-sm opacity-90">
                                        <?php echo htmlspecialchars($pcData['room_name'] ?? 'Unknown Room'); ?> -
                                        <?php echo htmlspecialchars($pcData['building_name'] ?? 'Unknown Building'); ?>
                                    </p>
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="text-3xl font-bold"><?php echo count($pcData['issues']); ?></p>
                                <p class="text-sm opacity-90">
                                    <?php echo count($pcData['issues']) === 1 ? 'Issue' : 'Issues'; ?>
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Issues List -->
                    <div class="p-4 space-y-3">
                        <?php foreach ($pcData['issues'] as $issue): ?>
                            <div class="issue-card bg-gray-50 rounded-lg p-4 priority-<?php echo strtolower($issue['priority']); ?>">
                                <div class="flex items-start justify-between mb-2">
                                    <div class="flex-1">
                                        <div class="flex items-center gap-2 mb-1 flex-wrap">
                                            <span class="text-xs font-semibold text-gray-500">
                                                #<?php echo $issue['issue_id']; ?>
                                            </span>
                                            <span class="px-2 py-0.5 text-xs font-medium rounded <?php
                                                echo $issue['status'] === 'Pending'     ? 'bg-yellow-100 text-yellow-800' :
                                                    ($issue['status'] === 'In Progress' ? 'bg-blue-100 text-blue-800' :
                                                                                          'bg-green-100 text-green-800');
                                            ?>">
                                                <?php echo htmlspecialchars($issue['status']); ?>
                                            </span>
                                            <span class="px-2 py-0.5 text-xs font-medium rounded <?php
                                                echo $issue['priority'] === 'High'   ? 'bg-red-100 text-red-800' :
                                                    ($issue['priority'] === 'Medium' ? 'bg-orange-100 text-orange-800' :
                                                                                       'bg-green-100 text-green-800');
                                            ?>">
                                                <?php echo htmlspecialchars($issue['priority']); ?>
                                            </span>
                                            <span class="px-2 py-0.5 text-xs font-medium rounded bg-purple-100 text-purple-800">
                                                <?php echo htmlspecialchars($issue['category']); ?>
                                            </span>
                                        </div>
                                        <p class="text-sm text-gray-700 font-medium mb-2">
                                            <?php echo htmlspecialchars($issue['description']); ?>
                                        </p>
                                        <div class="flex items-center gap-4 text-xs text-gray-500 flex-wrap">
                                            <span>
                                                <i class="far fa-calendar mr-1"></i>
                                                Reported: <?php echo date('M d, Y g:i A', strtotime($issue['created_at'])); ?>
                                            </span>
                                            <?php if (!empty($issue['assigned_at'])): ?>
                                                <span>
                                                    <i class="fas fa-user-check mr-1"></i>
                                                    Assigned: <?php echo date('M d, Y', strtotime($issue['assigned_at'])); ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</main>

<?php include '../components/layout_footer.php'; ?>
