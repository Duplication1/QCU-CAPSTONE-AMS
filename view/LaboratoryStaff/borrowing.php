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
require_once '../../model/Database.php';
require_once '../../model/AssetBorrowing.php';

// Get Laboratory Staff's e-signature
$lab_staff_signature = null;
try {
    $db = new Database();
    $conn = $db->getConnection();
    $stmt = $conn->prepare("SELECT e_signature FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $lab_staff_signature = $stmt->fetchColumn();
} catch (PDOException $e) {
    // Handle error silently
}

// Get pagination and filter parameters
$filter_status = $_GET['status'] ?? 'all';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = isset($_GET['per_page']) ? intval($_GET['per_page']) : 10;
if ($per_page <= 0) $per_page = 999999; // Show all
$offset = ($page - 1) * $per_page;

$borrowing = new AssetBorrowing();
$requests = [];
$total_requests = 0;
$stats = ['total_borrowings' => 0, 'pending' => 0, 'approved' => 0, 'returned' => 0];
$db_error = null;

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    // Build query with search and filter
    $where_conditions = [];
    $params = [];
    
    if ($filter_status !== 'all') {
        $where_conditions[] = 'ab.status = ?';
        $params[] = $filter_status;
    }
    
    if (!empty($search)) {
        $where_conditions[] = '(u.full_name LIKE ? OR u.email LIKE ? OR a.asset_tag LIKE ? OR a.asset_name LIKE ? OR ab.purpose LIKE ?)';
        $search_param = '%' . $search . '%';
        $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param, $search_param]);
    }
    
    $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
    
    // Count total requests
    $count_query = "SELECT COUNT(*) as total 
                    FROM asset_borrowing ab
                    LEFT JOIN users u ON ab.borrower_id = u.id
                    LEFT JOIN assets a ON ab.asset_id = a.id
                    $where_clause";
    
    $count_stmt = $conn->prepare($count_query);
    if (!empty($params)) {
        $count_stmt->execute($params);
    } else {
        $count_stmt->execute();
    }
    $total_requests = $count_stmt->fetchColumn();
    
    // Fetch paginated requests (LIMIT and OFFSET are safe - already validated as integers)
    $query = "SELECT ab.*, 
              u.full_name as borrower_full_name, 
              u.email as borrower_email,
              a.asset_tag, 
              a.asset_name, 
              a.asset_type,
              a.brand,
              a.model,
              approver.full_name as approved_by_name
              FROM asset_borrowing ab
              LEFT JOIN users u ON ab.borrower_id = u.id
              LEFT JOIN assets a ON ab.asset_id = a.id
              LEFT JOIN users approver ON ab.approved_by = approver.id
              $where_clause
              ORDER BY ab.id DESC
              LIMIT $per_page OFFSET $offset";
    
    $stmt = $conn->prepare($query);
    if (!empty($params)) {
        $stmt->execute($params);
    } else {
        $stmt->execute();
    }
    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get statistics
    $stats = $borrowing->getStatistics();
} catch (Exception $e) {
    $db_error = $e->getMessage();
    $_SESSION['error_message'] = 'Database error: ' . $e->getMessage();
}

$total_pages = ceil($total_requests / $per_page);

include '../components/layout_header.php';
?>

        <!-- Main Content -->
        <main class="p-2">
            
            <!-- Session Messages -->
            <?php include '../components/session_messages.php'; ?>

            <!-- Statistics Cards -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-2 mb-2">
                <div class="bg-white rounded-lg shadow p-3 border border-gray-200">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-600 text-[10px]">Pending</p>
                            <p class="text-xl font-bold text-[#1E3A8A]"><?php echo $stats['pending'] ?? 0; ?></p>
                        </div>
                        <div class="bg-blue-100 p-2 rounded">
                            <i class="fa-solid fa-clock text-[#1E3A8A] text-lg"></i>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow p-3 border border-gray-200">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-600 text-[10px]">Borrowed</p>
                            <p class="text-xl font-bold text-[#1E3A8A]"><?php echo $stats['approved'] ?? 0; ?></p>
                        </div>
                        <div class="bg-blue-100 p-2 rounded">
                            <i class="fa-solid fa-hand-holding text-[#1E3A8A] text-lg"></i>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow p-3 border border-gray-200">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-600 text-[10px]">Returned</p>
                            <p class="text-xl font-bold text-[#1E3A8A]"><?php echo $stats['returned'] ?? 0; ?></p>
                        </div>
                        <div class="bg-blue-100 p-2 rounded">
                            <i class="fa-solid fa-check-circle text-[#1E3A8A] text-lg"></i>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow p-3 border border-gray-200">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-600 text-[10px]">Total</p>
                            <p class="text-xl font-bold text-[#1E3A8A]"><?php echo $stats['total_borrowings'] ?? 0; ?></p>
                        </div>
                        <div class="bg-blue-100 p-2 rounded">
                            <i class="fa-solid fa-list text-[#1E3A8A] text-lg"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Borrowing Requests Table -->
            <div class="bg-white rounded-lg shadow p-3 border border-gray-200">
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-3 gap-2">
                    <h2 class="text-sm font-semibold text-[#1E3A8A]">
                        <i class="fa-solid fa-clipboard-list mr-1"></i>
                        Borrowing Requests (<?php echo $total_requests; ?>)
                    </h2>
                    
                    <!-- Search and Filter -->
                    <div class="flex flex-wrap gap-2 items-center w-full sm:w-auto">
                        <!-- Search Box -->
                        <form method="GET" action="" class="flex items-center gap-2">
                            <input type="hidden" name="status" value="<?php echo htmlspecialchars($filter_status); ?>">
                            <input type="hidden" name="per_page" value="<?php echo $per_page; ?>">
                            <div class="relative">
                                <input type="text" 
                                       name="search" 
                                       id="searchInput"
                                       value="<?php echo htmlspecialchars($search); ?>"
                                       placeholder="Search requests..." 
                                       class="pl-8 pr-3 py-1.5 text-xs border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-[#1E3A8A] focus:border-transparent w-48">
                                <i class="fa-solid fa-search absolute left-2.5 top-2.5 text-gray-400 text-xs"></i>
                            </div>
                            <?php if (!empty($search)): ?>
                            <a href="?status=<?php echo htmlspecialchars($filter_status); ?>&per_page=<?php echo $per_page; ?>" 
                               class="text-xs text-red-600 hover:text-red-700">
                                <i class="fa-solid fa-times-circle"></i> Clear
                            </a>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>

                <!-- Filter Buttons -->
                <div class="flex flex-wrap gap-1 mb-3">
                    <a href="?status=all&search=<?php echo urlencode($search); ?>&per_page=<?php echo $per_page; ?>" 
                       class="px-3 py-1.5 rounded text-xs font-medium transition-colors <?php echo $filter_status === 'all' ? 'bg-[#1E3A8A] text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'; ?>">
                        All
                    </a>
                    <a href="?status=Pending&search=<?php echo urlencode($search); ?>&per_page=<?php echo $per_page; ?>" 
                       class="px-3 py-1.5 rounded text-xs font-medium transition-colors <?php echo $filter_status === 'Pending' ? 'bg-[#1E3A8A] text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'; ?>">
                        Pending
                    </a>
                    <a href="?status=Approved&search=<?php echo urlencode($search); ?>&per_page=<?php echo $per_page; ?>" 
                       class="px-3 py-1.5 rounded text-xs font-medium transition-colors <?php echo $filter_status === 'Approved' ? 'bg-[#1E3A8A] text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'; ?>">
                        Approved
                    </a>
                    <a href="?status=Returned&search=<?php echo urlencode($search); ?>&per_page=<?php echo $per_page; ?>" 
                       class="px-3 py-1.5 rounded text-xs font-medium transition-colors <?php echo $filter_status === 'Returned' ? 'bg-[#1E3A8A] text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'; ?>">
                        Returned
                    </a>
                </div>

                <?php if ($db_error): ?>
                    <div class="text-center py-8">
                        <i class="fa-solid fa-database text-5xl text-red-300 mb-3"></i>
                        <p class="text-red-600 text-base font-semibold">Database Error</p>
                        <p class="text-gray-600 text-xs mt-1">The borrowing tables may not exist yet.</p>
                        <div class="mt-3 p-3 bg-red-50 border border-red-200 rounded max-w-xl mx-auto text-left">
                            <p class="text-xs font-semibold text-red-800 mb-1">To fix this:</p>
                            <ol class="text-xs text-red-700 space-y-1 ml-4">
                                <li>1. Open phpMyAdmin</li>
                                <li>2. Select your database (ams_database)</li>
                                <li>3. Go to the SQL tab</li>
                                <li>4. Copy and run the content from: <code class="bg-red-100 px-2 py-1 rounded">database/create_assets_table.sql</code></li>
                            </ol>
                            <p class="text-[10px] text-red-600 mt-2 font-mono"><?php echo htmlspecialchars($db_error); ?></p>
                        </div>
                    </div>
                <?php elseif (empty($requests)): ?>
                    <div class="text-center py-8">
                        <i class="fa-solid fa-inbox text-5xl text-gray-300 mb-3"></i>
                        <p class="text-gray-600 text-sm">No borrowing requests found.</p>
                        <?php if (!empty($search)): ?>
                        <p class="text-gray-500 text-xs mt-1">Try adjusting your search terms.</p>
                        <?php else: ?>
                        <p class="text-gray-500 text-xs mt-1">Borrowing requests will appear here when students or faculty submit them.</p>
                        <?php endif; ?>
                        <?php if ($filter_status !== 'all'): ?>
                        <p class="text-[10px] text-gray-400 mt-3">Current filter: <span class="font-semibold"><?php echo htmlspecialchars($filter_status); ?></span></p>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <!-- Entries Per Page and Pagination Info -->
                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-3 gap-2">
                        <div class="flex items-center gap-2">
                            <label class="text-xs text-gray-600">Show:</label>
                            <select onchange="window.location.href='?status=<?php echo urlencode($filter_status); ?>&search=<?php echo urlencode($search); ?>&per_page=' + this.value" 
                                    class="text-xs border border-gray-300 rounded px-2 py-1 focus:outline-none focus:ring-2 focus:ring-[#1E3A8A]">
                                <option value="10" <?php echo $per_page == 10 ? 'selected' : ''; ?>>10</option>
                                <option value="25" <?php echo $per_page == 25 ? 'selected' : ''; ?>>25</option>
                                <option value="50" <?php echo $per_page == 50 ? 'selected' : ''; ?>>50</option>
                                <option value="100" <?php echo $per_page == 100 ? 'selected' : ''; ?>>100</option>
                            </select>
                            <span class="text-xs text-gray-600">entries per page</span>
                        </div>
                        <div class="text-xs text-gray-600">
                            Showing <?php echo min(($page - 1) * $per_page + 1, $total_requests); ?> 
                            to <?php echo min($page * $per_page, $total_requests); ?> 
                            of <?php echo $total_requests; ?> requests
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-xs border-collapse">
                            <thead class="bg-[#1E3A8A]">
                                <tr>
                                    <th class="text-left px-3 py-2 font-semibold text-white">Request Date</th>
                                    <th class="text-left px-3 py-2 font-semibold text-white">Borrower</th>
                                    <th class="text-left px-3 py-2 font-semibold text-white">Asset Tag</th>
                                    <th class="text-left px-3 py-2 font-semibold text-white">Asset Name</th>
                                    <th class="text-left px-3 py-2 font-semibold text-white">Borrow Date</th>
                                    <th class="text-left px-3 py-2 font-semibold text-white">Return Date</th>
                                    <th class="text-left px-3 py-2 font-semibold text-white">Status</th>
                                    <th class="text-center px-3 py-2 font-semibold text-white">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <?php foreach ($requests as $request): ?>
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="px-3 py-2"><?php echo date('M d, Y', strtotime($request['created_at'])); ?></td>
                                    <td class="px-3 py-2">
                                        <div class="font-medium text-gray-900"><?php echo htmlspecialchars($request['borrower_full_name']); ?></div>
                                        <div class="text-[10px] text-gray-500"><?php echo htmlspecialchars($request['borrower_email']); ?></div>
                                    </td>
                                    <td class="px-3 py-2">
                                        <span class="font-mono text-[10px] bg-gray-100 px-2 py-1 rounded">
                                            <?php echo htmlspecialchars($request['asset_tag']); ?>
                                        </span>
                                    </td>
                                    <td class="px-3 py-2">
                                        <div class="font-medium"><?php echo htmlspecialchars($request['asset_name']); ?></div>
                                        <div class="text-[10px] text-gray-500"><?php echo htmlspecialchars($request['asset_type']); ?></div>
                                    </td>
                                    <td class="px-3 py-2"><?php echo date('M d, Y', strtotime($request['borrowed_date'])); ?></td>
                                    <td class="px-3 py-2">
                                        <?php 
                                        if ($request['actual_return_date']) {
                                            echo '<span class="text-green-600 font-medium">' . date('M d, Y', strtotime($request['actual_return_date'])) . '</span>';
                                        } else {
                                            echo date('M d, Y', strtotime($request['expected_return_date']));
                                        }
                                        ?>
                                    </td>
                                    <td class="px-3 py-2">
                                        <?php
                                        $statusColors = [
                                            'Pending' => 'bg-yellow-100 text-yellow-800',
                                            'Approved' => 'bg-green-100 text-green-800',
                                            'Borrowed' => 'bg-blue-100 text-blue-800',
                                            'Returned' => 'bg-gray-100 text-gray-800',
                                            'Overdue' => 'bg-red-100 text-red-800',
                                            'Cancelled' => 'bg-red-100 text-red-800'
                                        ];
                                        $statusClass = $statusColors[$request['status']] ?? 'bg-gray-100 text-gray-800';
                                        ?>
                                        <span class="px-2 py-1 rounded-full text-[10px] font-semibold <?php echo $statusClass; ?>">
                                            <?php echo htmlspecialchars($request['status']); ?>
                                        </span>
                                    </td>
                                    <td class="px-3 py-2 text-center">
                                        <div class="flex justify-center gap-1">
                                            <button onclick="viewRequestDetails(<?php echo $request['id']; ?>)" 
                                                    class="bg-[#1E3A8A] hover:bg-[#152d6b] text-white px-2 py-1 rounded text-[10px] transition-colors"
                                                    title="View Details">
                                                <i class="fa-solid fa-eye"></i>
                                            </button>
                                            <?php if ($request['status'] === 'Pending'): ?>
                                            <button onclick="approveRequest(<?php echo $request['id']; ?>)" 
                                                    class="bg-green-600 hover:bg-green-700 text-white px-2 py-1 rounded text-[10px] transition-colors"
                                                    title="Approve">
                                                <i class="fa-solid fa-check"></i>
                                            </button>
                                            <button onclick="cancelRequest(<?php echo $request['id']; ?>)" 
                                                    class="bg-red-600 hover:bg-red-700 text-white px-2 py-1 rounded text-[10px] transition-colors"
                                                    title="Cancel">
                                                <i class="fa-solid fa-times"></i>
                                            </button>
                                            <?php endif; ?>
                                            <?php if ($request['status'] === 'Approved'): ?>
                                            <button onclick="returnAsset(<?php echo $request['id']; ?>)" 
                                                    class="bg-[#1E3A8A] hover:bg-[#152d6b] text-white px-2 py-1 rounded text-[10px] transition-colors"
                                                    title="Mark as Returned">
                                                <i class="fa-solid fa-rotate-left"></i>
                                            </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                    <div class="flex flex-col sm:flex-row justify-between items-center mt-4 gap-3">
                        <div class="text-xs text-gray-600">
                            Page <?php echo $page; ?> of <?php echo $total_pages; ?>
                        </div>
                        <div class="flex gap-1">
                            <?php if ($page > 1): ?>
                                <a href="?status=<?php echo urlencode($filter_status); ?>&search=<?php echo urlencode($search); ?>&per_page=<?php echo $per_page; ?>&page=1" 
                                   class="px-3 py-1 text-xs border border-gray-300 rounded hover:bg-gray-100 transition-colors">
                                    <i class="fa-solid fa-angles-left"></i>
                                </a>
                                <a href="?status=<?php echo urlencode($filter_status); ?>&search=<?php echo urlencode($search); ?>&per_page=<?php echo $per_page; ?>&page=<?php echo $page - 1; ?>" 
                                   class="px-3 py-1 text-xs border border-gray-300 rounded hover:bg-gray-100 transition-colors">
                                    <i class="fa-solid fa-angle-left"></i>
                                </a>
                            <?php endif; ?>

                            <?php
                            $start_page = max(1, $page - 2);
                            $end_page = min($total_pages, $page + 2);
                            
                            for ($i = $start_page; $i <= $end_page; $i++):
                            ?>
                                <a href="?status=<?php echo urlencode($filter_status); ?>&search=<?php echo urlencode($search); ?>&per_page=<?php echo $per_page; ?>&page=<?php echo $i; ?>" 
                                   class="px-3 py-1 text-xs border border-gray-300 rounded hover:bg-gray-100 transition-colors <?php echo $i == $page ? 'bg-[#1E3A8A] text-white hover:bg-[#152d6b]' : ''; ?>">
                                    <?php echo $i; ?>
                                </a>
                            <?php endfor; ?>

                            <?php if ($page < $total_pages): ?>
                                <a href="?status=<?php echo urlencode($filter_status); ?>&search=<?php echo urlencode($search); ?>&per_page=<?php echo $per_page; ?>&page=<?php echo $page + 1; ?>" 
                                   class="px-3 py-1 text-xs border border-gray-300 rounded hover:bg-gray-100 transition-colors">
                                    <i class="fa-solid fa-angle-right"></i>
                                </a>
                                <a href="?status=<?php echo urlencode($filter_status); ?>&search=<?php echo urlencode($search); ?>&per_page=<?php echo $per_page; ?>&page=<?php echo $total_pages; ?>" 
                                   class="px-3 py-1 text-xs border border-gray-300 rounded hover:bg-gray-100 transition-colors">
                                    <i class="fa-solid fa-angles-right"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </main>

<!-- View Request Details Modal -->
<div id="viewDetailsModal" class="fixed inset-0 bg-gray-600 bg-opacity-75 hidden z-50 overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen px-4 py-6">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-3xl">
            <div class="bg-[#1E3A8A] text-white px-6 py-4 rounded-t-lg flex justify-between items-center">
                <h3 class="text-xl font-bold">
                    <i class="fa-solid fa-file-lines mr-2"></i>
                    Request Details
                </h3>
                <button onclick="closeViewModal()" class="text-white hover:text-gray-200">
                    <i class="fa-solid fa-xmark text-2xl"></i>
                </button>
            </div>
            <div class="p-6" id="viewDetailsContent">
                <div class="text-center py-8">
                    <i class="fa-solid fa-spinner fa-spin text-4xl text-blue-600"></i>
                    <p class="mt-2 text-gray-600">Loading details...</p>
                </div>
            </div>
            <div class="px-6 py-4 bg-gray-50 rounded-b-lg flex justify-between border-t">
                <button onclick="printBorrowingDocument()" class="bg-[#1E3A8A] hover:bg-[#152d6b] text-white px-6 py-2 rounded-lg font-medium">
                    <i class="fa-solid fa-print mr-2"></i>Print Document
                </button>
                <button onclick="closeViewModal()" class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-6 py-2 rounded-lg font-medium">
                    Close
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Return Asset Modal -->
<div id="returnModal" class="fixed inset-0 bg-gray-600 bg-opacity-75 hidden z-50 overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen px-4 py-6">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-md">
            <div class="bg-[#1E3A8A] text-white px-6 py-4 rounded-t-lg flex justify-between items-center">
                <h3 class="text-xl font-bold">
                    <i class="fa-solid fa-rotate-left mr-2"></i>
                    Return Asset
                </h3>
                <button onclick="closeReturnModal()" class="text-white hover:text-gray-200">
                    <i class="fa-solid fa-xmark text-2xl"></i>
                </button>
            </div>
            <form id="returnForm" method="POST" action="../../controller/return_asset.php">
                <div class="p-6">
                    <input type="hidden" name="borrowing_id" id="return_borrowing_id">
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Returned Condition <span class="text-red-500">*</span>
                        </label>
                        <select name="returned_condition" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                            <option value="">Select condition...</option>
                            <option value="Excellent">Excellent - Like new</option>
                            <option value="Good">Good - Minor wear</option>
                            <option value="Fair">Fair - Visible wear</option>
                            <option value="Poor">Poor - Significant damage</option>
                            <option value="Damaged">Damaged - Needs repair</option>
                        </select>
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Return Notes (Optional)
                        </label>
                        <textarea name="return_notes" rows="4" 
                                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                                  placeholder="Add any notes about the returned asset..."></textarea>
                    </div>
                </div>
                <div class="px-6 py-4 bg-gray-50 rounded-b-lg flex justify-end gap-3 border-t">
                    <button type="button" onclick="closeReturnModal()" 
                            class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-6 py-2 rounded-lg font-medium">
                        Cancel
                    </button>
                    <button type="submit" 
                            class="bg-[#1E3A8A] hover:bg-[#152d6b] text-white px-6 py-2 rounded-lg font-medium">
                        <i class="fa-solid fa-check mr-2"></i>Confirm Return
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Store current request for printing (global scope)
let currentRequest = null;

// Auto-submit search with debounce
let searchTimeout;
const searchInput = document.getElementById('searchInput');
if (searchInput) {
    searchInput.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            this.form.submit();
        }, 800); // Wait 800ms after user stops typing
    });
}

// View Request Details
async function viewRequestDetails(requestId) {
    document.getElementById('viewDetailsModal').classList.remove('hidden');
    
    try {
        const response = await fetch(`../../controller/get_borrowing_details.php?id=${requestId}`);
        
        // Check if response is OK
        if (!response.ok) {
            const errorText = await response.text();
            console.error('Response error:', response.status, errorText);
            throw new Error(`HTTP ${response.status}: ${errorText}`);
        }
        
        const data = await response.json();
        console.log('Request data:', data);
        
        if (data.success) {
            displayRequestDetails(data.request);
        } else {
            document.getElementById('viewDetailsContent').innerHTML = `
                <div class="text-center py-8 text-red-600">
                    <i class="fa-solid fa-exclamation-triangle text-4xl mb-2"></i>
                    <p>Error loading request details.</p>
                    <p class="text-sm mt-2">${data.error || 'Unknown error'}</p>
                </div>
            `;
        }
    } catch (error) {
        console.error('Fetch error:', error);
        document.getElementById('viewDetailsContent').innerHTML = `
            <div class="text-center py-8 text-red-600">
                <i class="fa-solid fa-exclamation-triangle text-4xl mb-2"></i>
                <p>Error loading request details.</p>
                <p class="text-sm mt-2">${error.message}</p>
            </div>
        `;
    }
}

function displayRequestDetails(request) {
    // Store request data globally for printing
    currentRequest = request;
    
    const statusColors = {
        'Pending': 'bg-yellow-100 text-yellow-800',
        'Approved': 'bg-green-100 text-green-800',
        'Borrowed': 'bg-blue-100 text-blue-800',
        'Returned': 'bg-gray-100 text-gray-800',
        'Overdue': 'bg-red-100 text-red-800',
        'Cancelled': 'bg-red-100 text-red-800'
    };
    
    const statusClass = statusColors[request.status] || 'bg-gray-100 text-gray-800';
    
    const content = `
        <div class="space-y-4">
            <div class="flex justify-between items-start">
                <div>
                    <h4 class="text-lg font-semibold text-gray-800">Request Information</h4>
                    <p class="text-sm text-gray-600">Request ID: #${request.id}</p>
                </div>
                <span class="px-4 py-2 rounded-full text-sm font-semibold ${statusClass}">
                    ${request.status}
                </span>
            </div>
            
            <div class="border-t pt-4">
                <h5 class="font-semibold text-gray-800 mb-3">Borrower Information</h5>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-sm text-gray-600">Name:</p>
                        <p class="font-semibold">${request.borrower_full_name}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Email:</p>
                        <p class="font-semibold">${request.borrower_email || 'N/A'}</p>
                    </div>
                </div>
            </div>
            
            <div class="border-t pt-4">
                <h5 class="font-semibold text-gray-800 mb-3">Asset Details</h5>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-sm text-gray-600">Asset Tag:</p>
                        <p class="font-semibold font-mono">${request.asset_tag}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Asset Name:</p>
                        <p class="font-semibold">${request.asset_name}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Type:</p>
                        <p class="font-semibold">${request.asset_type}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Brand/Model:</p>
                        <p class="font-semibold">${request.brand || 'N/A'} ${request.model || ''}</p>
                    </div>
                </div>
            </div>
            
            <div class="border-t pt-4">
                <h5 class="font-semibold text-gray-800 mb-3">Borrowing Timeline</h5>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-sm text-gray-600">Request Date:</p>
                        <p class="font-semibold">${new Date(request.created_at).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' })}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Borrow Date:</p>
                        <p class="font-semibold">${new Date(request.borrowed_date).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' })}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Expected Return:</p>
                        <p class="font-semibold">${new Date(request.expected_return_date).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' })}</p>
                    </div>
                    ${request.actual_return_date ? `
                    <div>
                        <p class="text-sm text-gray-600">Actual Return:</p>
                        <p class="font-semibold text-green-600">${new Date(request.actual_return_date).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' })}</p>
                    </div>
                    ` : ''}
                </div>
            </div>
            
            <div class="border-t pt-4">
                <h5 class="font-semibold text-gray-800 mb-2">Purpose:</h5>
                <p class="text-gray-700 bg-gray-50 p-3 rounded">${request.purpose || 'N/A'}</p>
            </div>
            
            ${request.approved_by_name ? `
            <div class="border-t pt-4">
                <h5 class="font-semibold text-gray-800 mb-2">Approval Information:</h5>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-sm text-gray-600">Approved By:</p>
                        <p class="font-semibold">${request.approved_by_name}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Approval Date:</p>
                        <p class="font-semibold">${new Date(request.approved_date).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' })}</p>
                    </div>
                </div>
            </div>
            ` : ''}
            
            ${request.return_notes ? `
            <div class="border-t pt-4">
                <h5 class="font-semibold text-gray-800 mb-2">Return Information:</h5>
                ${request.returned_condition ? `
                <div class="mb-2">
                    <span class="text-sm text-gray-600">Condition: </span>
                    <span class="font-semibold">${request.returned_condition}</span>
                </div>
                ` : ''}
                <p class="text-gray-700 bg-gray-50 p-3 rounded">${request.return_notes}</p>
            </div>
            ` : ''}
        </div>
    `;
    
    document.getElementById('viewDetailsContent').innerHTML = content;
}

function closeViewModal() {
    document.getElementById('viewDetailsModal').classList.add('hidden');
}

// Approve Request
async function approveRequest(requestId) {
    const confirmed = await showConfirmModal({
        title: 'Approve Borrowing Request',
        message: 'Are you sure you want to approve this borrowing request?',
        confirmText: 'Approve',
        cancelText: 'Cancel',
        confirmColor: 'bg-green-600 hover:bg-green-700',
        type: 'success'
    });
    
    if (!confirmed) return;
    
    try {
        const formData = new FormData();
        formData.append('borrowing_id', requestId);
        
        const response = await fetch('../../controller/approve_borrowing.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            window.location.reload();
        } else {
            showNotification(data.error || 'Failed to approve request', 'error');
        }
    } catch (error) {
        showNotification('Error approving request. Please try again.', 'error');
    }
}

// Cancel Request
async function cancelRequest(requestId) {
    const confirmed = await showConfirmModal({
        title: 'Cancel Borrowing Request',
        message: 'Are you sure you want to cancel this borrowing request?',
        confirmText: 'Cancel Request',
        cancelText: 'Go Back',
        confirmColor: 'bg-red-600 hover:bg-red-700',
        type: 'danger'
    });
    
    if (!confirmed) return;
    
    try {
        const formData = new FormData();
        formData.append('borrowing_id', requestId);
        
        const response = await fetch('../../controller/cancel_borrowing.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            window.location.reload();
        } else {
            showNotification(data.error || 'Failed to cancel request', 'error');
        }
    } catch (error) {
        showNotification('Error cancelling request. Please try again.', 'error');
    }
}

// Return Asset
function returnAsset(requestId) {
    document.getElementById('return_borrowing_id').value = requestId;
    document.getElementById('returnModal').classList.remove('hidden');
}

function closeReturnModal() {
    document.getElementById('returnModal').classList.add('hidden');
    document.getElementById('returnForm').reset();
}

// Print Borrowing Document
async function printBorrowingDocument() {
    if (!currentRequest) {
        showNotification('No request data available', 'error');
        return;
    }
    
    try {
        // Fetch signatures
        const response = await fetch(`../../controller/get_signatures_for_print.php?borrower_id=${currentRequest.borrower_id}&lab_staff_id=<?php echo $_SESSION['user_id']; ?>`);
        const data = await response.json();
        
        if (!data.success) {
            showNotification(data.error || 'Unknown error', 'error');
            return;
        }
        
        // Generate printable document
        const printWindow = window.open('', '_blank');
        const printContent = generatePrintableDocument(currentRequest, data.borrower_signature, data.lab_staff_signature);
        
        printWindow.document.write(printContent);
        printWindow.document.close();
        printWindow.focus();
        
        setTimeout(() => {
            printWindow.print();
        }, 250);
    } catch (error) {
        console.error('Print error:', error);
        showNotification('Error printing document. Please try again.', 'error');
    }
}

function generatePrintableDocument(request, borrowerSig, labStaffSig) {
    const today = new Date().toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
    const refNo = 'BRW-' + request.id.toString().padStart(6, '0');
    
    return `
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Equipment Borrowing Agreement - ${refNo}</title>
    <style>
        @media print {
            body { margin: 0; }
            @page { margin: 0.5in; }
        }
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 8.5in;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            text-align: center;
            border-bottom: 3px solid #000;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: bold;
        }
        .header h2 {
            margin: 5px 0;
            font-size: 18px;
            font-weight: bold;
        }
        .header p {
            margin: 3px 0;
            font-size: 12px;
            color: #666;
        }
        .section {
            margin-bottom: 20px;
        }
        .section-title {
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 10px;
            padding: 5px;
            background-color: #f0f0f0;
            border-left: 4px solid #333;
        }
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 15px;
        }
        .info-item {
            font-size: 12px;
        }
        .info-label {
            color: #666;
            font-size: 11px;
            margin-bottom: 2px;
        }
        .info-value {
            font-weight: bold;
        }
        .declaration-box {
            background-color: #f9f9f9;
            border: 1px solid #ddd;
            padding: 15px;
            margin: 20px 0;
            border-radius: 5px;
        }
        .declaration-box h3 {
            margin-top: 0;
            font-size: 13px;
        }
        .declaration-box p {
            font-size: 11px;
            line-height: 1.5;
            margin: 0;
        }
        .signature-section {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-top: 30px;
        }
        .signature-box {
            text-align: center;
        }
        .signature-box .label {
            font-size: 11px;
            color: #666;
            margin-bottom: 5px;
        }
        .signature-box .sig-area {
            border: 2px dashed #ccc;
            background-color: #fafafa;
            padding: 15px;
            min-height: 80px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 10px;
        }
        .signature-box .sig-area img {
            max-height: 70px;
            max-width: 100%;
        }
        .signature-box .sig-area .no-sig {
            color: #999;
            font-size: 11px;
        }
        .signature-box .name-line {
            border-top: 2px solid #333;
            padding-top: 5px;
            font-weight: bold;
            font-size: 12px;
        }
        .signature-box .role-text {
            font-size: 10px;
            color: #666;
        }
        .footer {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 1px solid #ddd;
            text-align: center;
            font-size: 10px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>QUEZON CITY UNIVERSITY</h1>
        <p>Asset Management System</p>
        <h2>EQUIPMENT BORROWING AGREEMENT</h2>
    </div>
    
    <div class="info-grid" style="margin-bottom: 25px;">
        <div class="info-item">
            <div class="info-label">Date:</div>
            <div class="info-value">${today}</div>
        </div>
        <div class="info-item">
            <div class="info-label">Reference No:</div>
            <div class="info-value">${refNo}</div>
        </div>
    </div>
    
    <div class="section">
        <div class="section-title">BORROWER INFORMATION</div>
        <div class="info-grid">
            <div class="info-item">
                <div class="info-label">Name:</div>
                <div class="info-value">${request.borrower_full_name}</div>
            </div>
            <div class="info-item">
                <div class="info-label">Email:</div>
                <div class="info-value">${request.borrower_email || 'N/A'}</div>
            </div>
        </div>
    </div>
    
    <div class="section">
        <div class="section-title">EQUIPMENT DETAILS</div>
        <div class="info-grid">
            <div class="info-item">
                <div class="info-label">Asset Tag:</div>
                <div class="info-value">${request.asset_tag}</div>
            </div>
            <div class="info-item">
                <div class="info-label">Asset Name:</div>
                <div class="info-value">${request.asset_name}</div>
            </div>
            <div class="info-item">
                <div class="info-label">Type:</div>
                <div class="info-value">${request.asset_type}</div>
            </div>
            <div class="info-item">
                <div class="info-label">Brand/Model:</div>
                <div class="info-value">${request.brand || 'N/A'} ${request.model || ''}</div>
            </div>
        </div>
    </div>
    
    <div class="section">
        <div class="section-title">BORROWING DETAILS</div>
        <div class="info-grid">
            <div class="info-item">
                <div class="info-label">Borrow Date:</div>
                <div class="info-value">${new Date(request.borrowed_date).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' })}</div>
            </div>
            <div class="info-item">
                <div class="info-label">Expected Return Date:</div>
                <div class="info-value">${new Date(request.expected_return_date).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' })}</div>
            </div>
        </div>
        <div class="info-item" style="margin-top: 10px;">
            <div class="info-label">Purpose:</div>
            <div class="info-value">${request.purpose || 'N/A'}</div>
        </div>
    </div>
    
    <div class="declaration-box">
        <h3>BORROWER'S DECLARATION</h3>
        <p>
            I hereby acknowledge that I have received the above-mentioned equipment in good working condition. 
            I agree to take full responsibility for the equipment and to return it on or before the expected return date 
            in the same condition as received. I have read, understood, and agree to comply with all terms and conditions 
            of the Equipment Borrowing Agreement.
        </p>
    </div>
    
    <div class="signature-section">
        <div class="signature-box">
            <div class="label">Borrower's E-Signature:</div>
            <div class="sig-area">
                ${borrowerSig ? `<img src="${borrowerSig}" alt="Borrower Signature">` : '<div class="no-sig">No signature available</div>'}
            </div>
            <div class="name-line">${request.borrower_full_name}</div>
            <div class="role-text">Borrower's Name</div>
        </div>
        
        <div class="signature-box">
            <div class="label">Released By (Laboratory Staff):</div>
            <div class="sig-area">
                ${labStaffSig ? `<img src="${labStaffSig}" alt="Lab Staff Signature">` : '<div class="no-sig">No signature available</div>'}
            </div>
            <div class="name-line"><?php echo htmlspecialchars($_SESSION['full_name']); ?></div>
            <div class="role-text">Laboratory Staff Signature</div>
        </div>
    </div>
    
    <div class="footer">
        <p>This is an official document from Quezon City University Asset Management System</p>
        <p>Printed on: ${new Date().toLocaleString('en-US', { year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit' })}</p>
    </div>
</body>
</html>
    `;
}
</script>
    
<?php include '../components/layout_footer.php'; ?>
