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

// Get selected month from URL parameter
$selectedMonth = isset($_GET['month']) ? intval($_GET['month']) : 0;
$selectedYear = isset($_GET['year']) ? intval($_GET['year']) : date('Y');

// Calculate date range based on selected month (0-5 represents last 6 months)
$startDate = date('Y-m-01', strtotime("-" . (5 - $selectedMonth) . " months"));
$endDate = date('Y-m-t', strtotime("-" . (5 - $selectedMonth) . " months"));
$monthName = date('F Y', strtotime($startDate));

// Fetch issues for the selected month with PC/Room details
$issuesQuery = "
    SELECT 
        i.id,
        i.title,
        i.description,
        i.category,
        i.priority,
        i.status,
        i.created_at,
        i.updated_at,
        COALESCE(u.full_name, 'Unknown') as reporter,
        COALESCE(i.assigned_technician, 'Unassigned') as technician,
        r.name as room_name,
        b.name as building_name,
        pc.terminal_number as pc_name,
        i.hardware_component,
        i.software_name,
        i.network_issue_type,
        i.laboratory_concern_type
    FROM issues i
    LEFT JOIN users u ON i.user_id = u.id
    LEFT JOIN rooms r ON i.room_id = r.id
    LEFT JOIN buildings b ON r.building_id = b.id
    LEFT JOIN pc_units pc ON i.pc_id = pc.id
    WHERE DATE(i.created_at) BETWEEN ? AND ?
    AND (i.category IS NULL OR i.category != 'borrow')
    ORDER BY i.created_at DESC
";

$stmt = $conn->prepare($issuesQuery);
$stmt->bind_param('ss', $startDate, $endDate);
$stmt->execute();
$issuesResult = $stmt->get_result();
$issues = [];
$issuesByPC = [];
$issuesByCategory = [];

while ($row = $issuesResult->fetch_assoc()) {
    $issues[] = $row;
    
    // Group by PC
    if ($row['pc_name']) {
        if (!isset($issuesByPC[$row['pc_name']])) {
            $issuesByPC[$row['pc_name']] = [];
        }
        $issuesByPC[$row['pc_name']][] = $row;
    }
    
    // Count by category
    if (!isset($issuesByCategory[$row['category']])) {
        $issuesByCategory[$row['category']] = 0;
    }
    $issuesByCategory[$row['category']]++;
}

$totalIssues = count($issues);
$stmt->close();

include '../components/layout_header.php';
?>

<style>
    .status-badge {
        padding: 0.25rem 0.75rem;
        border-radius: 9999px;
        font-size: 0.75rem;
        font-weight: 500;
    }
    .status-open { background-color: #DBEAFE; color: #1E40AF; }
    .status-in-progress { background-color: #FEF3C7; color: #92400E; }
    .status-resolved { background-color: #D1FAE5; color: #065F46; }
    .status-closed { background-color: #E5E7EB; color: #374151; }
    
    .priority-badge {
        padding: 0.25rem 0.75rem;
        border-radius: 9999px;
        font-size: 0.75rem;
        font-weight: 500;
    }
    .priority-low { background-color: #E0E7FF; color: #3730A3; }
    .priority-medium { background-color: #FEF3C7; color: #92400E; }
    .priority-high { background-color: #FEE2E2; color: #991B1B; }
</style>

<!-- Main Content -->
<main class="p-6 bg-gray-50 min-h-screen">
    
    <!-- Header -->
    <div class="mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Historical Issues</h1>
                <p class="text-sm text-gray-600 mt-1">Showing issues reported in <strong><?php echo htmlspecialchars($monthName); ?></strong></p>
            </div>
            <a href="index.php" class="px-4 py-2 bg-blue-900 text-white rounded-lg hover:bg-blue-800 transition-colors">
                <i class="fas fa-arrow-left mr-2"></i>Back to Dashboard
            </a>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Total Issues</p>
                    <p class="text-2xl font-bold text-blue-900"><?php echo $totalIssues; ?></p>
                </div>
                <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-ticket text-blue-900 text-xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Affected PCs</p>
                    <p class="text-2xl font-bold text-blue-900"><?php echo count($issuesByPC); ?></p>
                </div>
                <div class="w-12 h-12 bg-purple-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-desktop text-purple-900 text-xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Categories</p>
                    <p class="text-2xl font-bold text-blue-900"><?php echo count($issuesByCategory); ?></p>
                </div>
                <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-layer-group text-green-900 text-xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Date Range</p>
                    <p class="text-sm font-bold text-blue-900"><?php echo date('M d', strtotime($startDate)) . ' - ' . date('M d', strtotime($endDate)); ?></p>
                </div>
                <div class="w-12 h-12 bg-orange-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-calendar text-orange-900 text-xl"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Category Breakdown -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Issues by Category</h2>
        <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
            <?php foreach ($issuesByCategory as $category => $count): ?>
                <div class="text-center p-4 bg-gray-50 rounded-lg">
                    <p class="text-sm text-gray-600 capitalize"><?php echo htmlspecialchars($category); ?></p>
                    <p class="text-2xl font-bold text-blue-900"><?php echo $count; ?></p>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Issues by PC Unit -->
    <?php if (!empty($issuesByPC)): ?>
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">
            <i class="fas fa-desktop text-blue-900 mr-2"></i>Computers with Past Issues
        </h2>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">PC Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Location</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Issue Count</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Categories</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($issuesByPC as $pcName => $pcIssues): ?>
                        <?php 
                        $firstIssue = $pcIssues[0];
                        $categories = array_unique(array_column($pcIssues, 'category'));
                        $statuses = array_count_values(array_column($pcIssues, 'status'));
                        ?>
                        <tr class="hover:bg-gray-50 cursor-pointer" onclick="toggleDetails('pc-<?php echo md5($pcName); ?>')">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <i class="fas fa-desktop text-blue-900 mr-2"></i>
                                    <div>
                                        <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($pcName); ?></div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900"><?php echo htmlspecialchars($firstIssue['building_name'] ?? 'N/A'); ?></div>
                                <div class="text-sm text-gray-500"><?php echo htmlspecialchars($firstIssue['room_name'] ?? 'N/A'); ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-sm font-semibold text-blue-900"><?php echo count($pcIssues); ?> issue(s)</span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex flex-wrap gap-1">
                                    <?php foreach ($categories as $cat): ?>
                                        <span class="px-2 py-1 text-xs bg-blue-100 text-blue-800 rounded capitalize"><?php echo htmlspecialchars($cat); ?></span>
                                    <?php endforeach; ?>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex flex-wrap gap-1">
                                    <?php foreach ($statuses as $status => $count): ?>
                                        <span class="status-badge status-<?php echo strtolower(str_replace(' ', '-', $status)); ?>">
                                            <?php echo htmlspecialchars($status); ?> (<?php echo $count; ?>)
                                        </span>
                                    <?php endforeach; ?>
                                </div>
                            </td>
                        </tr>
                        <tr id="pc-<?php echo md5($pcName); ?>" class="hidden bg-gray-50">
                            <td colspan="5" class="px-6 py-4">
                                <div class="space-y-3">
                                    <h4 class="font-semibold text-gray-900 mb-2">Issue Details:</h4>
                                    <?php foreach ($pcIssues as $issue): ?>
                                        <div class="bg-white border border-gray-200 rounded-lg p-4">
                                            <div class="flex items-start justify-between mb-2">
                                                <div class="flex-1">
                                                    <h5 class="font-medium text-gray-900"><?php echo htmlspecialchars($issue['title']); ?></h5>
                                                    <p class="text-sm text-gray-600 mt-1"><?php echo htmlspecialchars($issue['description'] ?: 'No description'); ?></p>
                                                </div>
                                                <span class="status-badge status-<?php echo strtolower(str_replace(' ', '-', $issue['status'])); ?>">
                                                    <?php echo htmlspecialchars($issue['status']); ?>
                                                </span>
                                            </div>
                                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-3 text-sm">
                                                <div>
                                                    <span class="text-gray-500">Category:</span>
                                                    <span class="font-medium capitalize ml-1"><?php echo htmlspecialchars($issue['category']); ?></span>
                                                </div>
                                                <div>
                                                    <span class="text-gray-500">Priority:</span>
                                                    <span class="priority-badge priority-<?php echo strtolower($issue['priority']); ?> ml-1">
                                                        <?php echo htmlspecialchars($issue['priority']); ?>
                                                    </span>
                                                </div>
                                                <div>
                                                    <span class="text-gray-500">Reporter:</span>
                                                    <span class="font-medium ml-1"><?php echo htmlspecialchars($issue['reporter']); ?></span>
                                                </div>
                                                <div>
                                                    <span class="text-gray-500">Technician:</span>
                                                    <span class="font-medium ml-1"><?php echo htmlspecialchars($issue['technician']); ?></span>
                                                </div>
                                            </div>
                                            <div class="mt-2 text-xs text-gray-500">
                                                <i class="fas fa-clock mr-1"></i>
                                                Reported: <?php echo date('M d, Y h:i A', strtotime($issue['created_at'])); ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <!-- All Issues List -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">
            <i class="fas fa-list text-blue-900 mr-2"></i>All Issues (<?php echo $totalIssues; ?>)
        </h2>
        
        <?php if (empty($issues)): ?>
            <div class="text-center py-12">
                <i class="fas fa-inbox text-gray-300 text-5xl mb-4"></i>
                <p class="text-gray-500">No issues found for this period</p>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Title</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">PC/Location</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Priority</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reporter</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($issues as $issue): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    #<?php echo htmlspecialchars($issue['id']); ?>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900">
                                    <div class="font-medium"><?php echo htmlspecialchars($issue['title']); ?></div>
                                    <?php if ($issue['description']): ?>
                                        <div class="text-gray-500 text-xs mt-1 truncate max-w-xs">
                                            <?php echo htmlspecialchars(substr($issue['description'], 0, 100)); ?>
                                            <?php if (strlen($issue['description']) > 100) echo '...'; ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 capitalize">
                                    <?php echo htmlspecialchars($issue['category']); ?>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900">
                                    <?php if ($issue['pc_name']): ?>
                                        <div class="font-medium"><?php echo htmlspecialchars($issue['pc_name']); ?></div>
                                    <?php endif; ?>
                                    <?php if ($issue['room_name']): ?>
                                        <div class="text-gray-500 text-xs"><?php echo htmlspecialchars($issue['room_name']); ?></div>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="priority-badge priority-<?php echo strtolower($issue['priority']); ?>">
                                        <?php echo htmlspecialchars($issue['priority']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="status-badge status-<?php echo strtolower(str_replace(' ', '-', $issue['status'])); ?>">
                                        <?php echo htmlspecialchars($issue['status']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php echo htmlspecialchars($issue['reporter']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo date('M d, Y', strtotime($issue['created_at'])); ?>
                                    <div class="text-xs"><?php echo date('h:i A', strtotime($issue['created_at'])); ?></div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

</main>

<script>
function toggleDetails(id) {
    const element = document.getElementById(id);
    element.classList.toggle('hidden');
}
</script>

<?php include '../components/layout_footer.php'; ?>
