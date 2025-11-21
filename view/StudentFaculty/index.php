<?php
// Prevent page caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

session_start();

// Check if user is logged in and has student or faculty role
if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true || !in_array($_SESSION['role'], ['Student', 'Faculty'])) {
    header("Location: ../login.php");
    exit();
}

require_once '../../config/config.php';

// Get user ID and role
$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'];
$user_name = $_SESSION['full_name'] ?? 'User';

// Database connection
$dbConfig = Config::database();
try {
    $conn = new mysqli($dbConfig['host'], $dbConfig['username'], $dbConfig['password'], $dbConfig['name']);
    $conn->set_charset('utf8mb4');
} catch (mysqli_sql_exception $e) {
    die("Database connection failed");
}

// Get activity summary - issues submitted
$ticketsQuery = "SELECT COUNT(*) as total, 
                        SUM(CASE WHEN status = 'Open' THEN 1 ELSE 0 END) as pending,
                        SUM(CASE WHEN status = 'In Progress' THEN 1 ELSE 0 END) as in_progress,
                        SUM(CASE WHEN status = 'Resolved' THEN 1 ELSE 0 END) as resolved
                 FROM issues WHERE user_id = ?";
$stmt = $conn->prepare($ticketsQuery);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$ticketsStats = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Get borrowing requests summary
$borrowingQuery = "SELECT COUNT(*) as total,
                          SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending,
                          SUM(CASE WHEN status = 'Approved' THEN 1 ELSE 0 END) as approved,
                          SUM(CASE WHEN status = 'Borrowed' THEN 1 ELSE 0 END) as active
                   FROM asset_borrowing WHERE borrower_id = ?";
$stmt = $conn->prepare($borrowingQuery);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$borrowingStats = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Get recent activity (last 10 actions)
$recentActivity = [];
$activityQuery = "
    (SELECT 'ticket' as type, id, CAST(title AS CHAR) COLLATE utf8mb4_unicode_ci as title, 
            CAST(status AS CHAR) COLLATE utf8mb4_unicode_ci as status, created_at as date 
     FROM issues WHERE user_id = ? ORDER BY created_at DESC LIMIT 5)
    UNION ALL
    (SELECT 'borrowing' as type, id, CAST(purpose AS CHAR) COLLATE utf8mb4_unicode_ci as title, 
            CAST(status AS CHAR) COLLATE utf8mb4_unicode_ci as status, created_at as date 
     FROM asset_borrowing WHERE borrower_id = ? ORDER BY created_at DESC LIMIT 5)
    ORDER BY date DESC LIMIT 10
";
$stmt = $conn->prepare($activityQuery);
$stmt->bind_param('ii', $user_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $recentActivity[] = $row;
}
$stmt->close();

// Get user's last login
$lastLoginQuery = "SELECT last_login FROM users WHERE id = ?";
$stmt = $conn->prepare($lastLoginQuery);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$lastLogin = $stmt->get_result()->fetch_assoc()['last_login'] ?? null;
$stmt->close();

$conn->close();

include '../components/layout_header.php';
?>

<style>
    body, html { overflow: hidden !important; height: 100vh; }
</style>

<!-- Main Content -->
<main class="p-2 bg-gray-50 h-screen overflow-hidden flex flex-col">
    <!-- Session Messages -->
    <?php include '../components/session_messages.php'; ?>

    <!-- Welcome Section -->
    <div class="bg-white rounded shadow-sm border border-gray-200 p-3 mb-2 flex-shrink-0">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-lg font-bold text-gray-800">
                    Welcome back, <?php echo htmlspecialchars($user_name); ?>!
                </h1>
                <p class="text-[10px] text-gray-500 mt-1">
                    <?php if ($lastLogin): ?>
                        Last login: <?php echo date('F j, Y, g:i A', strtotime($lastLogin)); ?>
                    <?php else: ?>
                        Welcome to your dashboard
                    <?php endif; ?>
                </p>
            </div>
            <button onclick="openLoginHistoryModal()" class="px-3 py-1.5 bg-[#1E3A8A] text-white rounded text-xs hover:bg-[#2a4fa3] transition-colors flex items-center gap-2">
                <i class="fas fa-clock-rotate-left"></i>
                <span>Login History</span>
            </button>
        </div>
    </div>

    <!-- Top Metrics Row -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-2 mb-2 flex-shrink-0">
        <!-- Total Tickets -->
        <div class="bg-white rounded shadow-sm border border-gray-200 p-3 hover:shadow-md transition-shadow">
            <div class="flex items-start justify-between mb-2">
                <div>
                    <p class="text-[10px] font-medium text-gray-500 uppercase mb-1">Total Issues</p>
                    <p class="text-xl font-bold text-gray-900"><?php echo $ticketsStats['total'] ?? 0; ?></p>
                </div>
                <div class="w-8 h-8 bg-[#1E3A8A] bg-opacity-10 rounded flex items-center justify-center">
                    <i class="fas fa-ticket text-[#1E3A8A] text-sm"></i>
                </div>
            </div>
            <p class="text-[10px] text-yellow-600 font-medium">
                <?php echo $ticketsStats['pending'] ?? 0; ?> pending
            </p>
        </div>

        <!-- Borrowing Requests -->
        <div class="bg-white rounded shadow-sm border border-gray-200 p-3 hover:shadow-md transition-shadow">
            <div class="flex items-start justify-between mb-2">
                <div>
                    <p class="text-[10px] font-medium text-gray-500 uppercase mb-1">Borrow Requests</p>
                    <p class="text-xl font-bold text-gray-900"><?php echo $borrowingStats['total'] ?? 0; ?></p>
                </div>
                <div class="w-8 h-8 bg-[#1E3A8A] bg-opacity-10 rounded flex items-center justify-center">
                    <i class="fas fa-box text-[#1E3A8A] text-sm"></i>
                </div>
            </div>
            <p class="text-[10px] text-purple-600 font-medium">
                <?php echo $borrowingStats['active'] ?? 0; ?> active
            </p>
        </div>

        <!-- Resolved Tickets -->
        <div class="bg-white rounded shadow-sm border border-gray-200 p-3 hover:shadow-md transition-shadow">
            <div class="flex items-start justify-between mb-2">
                <div>
                    <p class="text-[10px] font-medium text-gray-500 uppercase mb-1">Resolved Issues</p>
                    <p class="text-xl font-bold text-gray-900"><?php echo $ticketsStats['resolved'] ?? 0; ?></p>
                </div>
                <div class="w-8 h-8 bg-[#1E3A8A] bg-opacity-10 rounded flex items-center justify-center">
                    <i class="fas fa-check-circle text-[#1E3A8A] text-sm"></i>
                </div>
            </div>
            <p class="text-[10px] text-blue-600 font-medium">
                <?php echo $ticketsStats['in_progress'] ?? 0; ?> in progress
            </p>
        </div>

        <!-- Approved Borrowing -->
        <div class="bg-white rounded shadow-sm border border-gray-200 p-3 hover:shadow-md transition-shadow">
            <div class="flex items-start justify-between mb-2">
                <div>
                    <p class="text-[10px] font-medium text-gray-500 uppercase mb-1">Approved Requests</p>
                    <p class="text-xl font-bold text-gray-900"><?php echo $borrowingStats['approved'] ?? 0; ?></p>
                </div>
                <div class="w-8 h-8 bg-[#1E3A8A] bg-opacity-10 rounded flex items-center justify-center">
                    <i class="fas fa-check text-[#1E3A8A] text-sm"></i>
                </div>
            </div>
            <p class="text-[10px] text-green-600 font-medium">
                Ready for pickup
            </p>
        </div>
    </div>

    <!-- Content Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-2 flex-1 min-h-0">
        <!-- Recent Activity -->
        <div class="bg-white rounded shadow-sm border border-gray-200 p-3 flex flex-col min-h-0">
            <div class="flex items-center justify-between mb-3 flex-shrink-0">
                <div>
                    <h3 class="text-sm font-semibold text-gray-900">Recent Activity</h3>
                    <p class="text-[10px] text-gray-500 mt-1">Your recent actions</p>
                </div>
                <a href="requests.php" class="text-[10px] text-blue-600 hover:text-blue-700 font-medium">
                    View All <i class="fas fa-arrow-right ml-1"></i>
                </a>
            </div>

            <div class="overflow-y-auto flex-1 min-h-0">
                <?php if (empty($recentActivity)): ?>
                    <div class="text-center py-8">
                        <i class="fas fa-inbox text-4xl text-gray-300 mb-2"></i>
                        <p class="text-xs text-gray-500">No recent activity</p>
                    </div>
                <?php else: ?>
                    <div class="space-y-2">
                        <?php foreach ($recentActivity as $activity): ?>
                            <?php
                            $typeIcon = $activity['type'] === 'ticket' ? 'fa-ticket' : 'fa-box';
                            $typeColor = $activity['type'] === 'ticket' ? 'blue' : 'green';
                            $statusColors = [
                                'Open' => 'bg-yellow-100 text-yellow-800',
                                'Pending' => 'bg-yellow-100 text-yellow-800',
                                'In Progress' => 'bg-blue-100 text-blue-800',
                                'Resolved' => 'bg-green-100 text-green-800',
                                'Closed' => 'bg-gray-100 text-gray-800',
                                'Approved' => 'bg-green-100 text-green-800',
                                'Borrowed' => 'bg-purple-100 text-purple-800',
                                'Returned' => 'bg-gray-100 text-gray-800',
                                'Cancelled' => 'bg-red-100 text-red-800'
                            ];
                            $statusClass = $statusColors[$activity['status']] ?? 'bg-gray-100 text-gray-800';
                            ?>
                            <div class="flex items-start gap-3 p-2 bg-gray-50 rounded hover:bg-gray-100 transition-colors">
                                <div class="flex-shrink-0 w-7 h-7 bg-[#1E3A8A] bg-opacity-10 rounded flex items-center justify-center">
                                    <i class="fas <?php echo $typeIcon; ?> text-[#1E3A8A] text-xs"></i>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-xs font-medium text-gray-900 truncate">
                                        <?php echo htmlspecialchars($activity['title']); ?>
                                    </p>
                                    <p class="text-[10px] text-gray-500 mt-0.5">
                                        <?php echo ucfirst($activity['type']); ?> • 
                                        <?php echo date('M j, g:i A', strtotime($activity['date'])); ?>
                                    </p>
                                </div>
                                <span class="<?php echo $statusClass; ?> px-2 py-0.5 text-[10px] font-medium rounded-full flex-shrink-0">
                                    <?php echo $activity['status']; ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="bg-white rounded shadow-sm border border-gray-200 p-3 flex flex-col min-h-0">
            <div class="mb-3 flex-shrink-0">
                <h3 class="text-sm font-semibold text-gray-900">Quick Actions</h3>
                <p class="text-[10px] text-gray-500 mt-1">Common tasks</p>
            </div>

            <div class="grid grid-cols-2 gap-2 flex-1">
                <a href="tickets.php?action=issue" class="group bg-gradient-to-br from-blue-50 to-[#E8EDF5] rounded p-4 hover:shadow-md transition-all flex flex-col items-center justify-center text-center">
                    <div class="w-10 h-10 bg-[#1E3A8A] rounded-full flex items-center justify-center mb-2 group-hover:scale-110 transition-transform">
                        <i class="fas fa-ticket text-white"></i>
                    </div>
                    <h4 class="text-xs font-semibold text-gray-800">Submit Issue</h4>
                    <p class="text-[10px] text-gray-600 mt-1">Report problem</p>
                </a>

                <a href="tickets.php?action=borrow" class="group bg-gradient-to-br from-blue-50 to-[#E8EDF5] rounded p-4 hover:shadow-md transition-all flex flex-col items-center justify-center text-center">
                    <div class="w-10 h-10 bg-[#1E3A8A] rounded-full flex items-center justify-center mb-2 group-hover:scale-110 transition-transform">
                        <i class="fas fa-box text-white"></i>
                    </div>
                    <h4 class="text-xs font-semibold text-gray-800">Borrow Asset</h4>
                    <p class="text-[10px] text-gray-600 mt-1">Request equipment</p>
                </a>

                <a href="requests.php" class="group bg-gradient-to-br from-blue-50 to-[#E8EDF5] rounded p-4 hover:shadow-md transition-all flex flex-col items-center justify-center text-center">
                    <div class="w-10 h-10 bg-[#1E3A8A] rounded-full flex items-center justify-center mb-2 group-hover:scale-110 transition-transform">
                        <i class="fas fa-clipboard-check text-white"></i>
                    </div>
                    <h4 class="text-xs font-semibold text-gray-800">My Requests</h4>
                    <p class="text-[10px] text-gray-600 mt-1">View status</p>
                </a>

                <a href="profile.php" class="group bg-gradient-to-br from-blue-50 to-[#E8EDF5] rounded p-4 hover:shadow-md transition-all flex flex-col items-center justify-center text-center">
                    <div class="w-10 h-10 bg-[#1E3A8A] rounded-full flex items-center justify-center mb-2 group-hover:scale-110 transition-transform">
                        <i class="fas fa-signature text-white"></i>
                    </div>
                    <h4 class="text-xs font-semibold text-gray-800">E-Signature</h4>
                    <p class="text-[10px] text-gray-600 mt-1">Manage signature</p>
                </a>
            </div>
        </div>
    </div>
</main>

<!-- Login History Modal -->
<div id="loginHistoryModal" class="hidden fixed inset-0 z-50 overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen px-4">
        <!-- Backdrop -->
        <div class="fixed inset-0 bg-black bg-opacity-50 transition-opacity" onclick="closeLoginHistoryModal()"></div>
        
        <!-- Modal Content -->
        <div class="relative bg-white rounded-2xl shadow-2xl max-w-3xl w-full max-h-[80vh] flex flex-col">
            <!-- Header -->
            <div class="flex items-center justify-between p-6 border-b border-gray-200">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-clock-rotate-left text-blue-600 text-xl"></i>
                    </div>
                    <div>
                        <h3 class="text-2xl font-bold text-gray-800">Login History</h3>
                        <p class="text-sm text-gray-500">Your recent login activities</p>
                    </div>
                </div>
                <button onclick="closeLoginHistoryModal()" class="text-gray-400 hover:text-gray-600 transition-colors">
                    <i class="fas fa-times text-2xl"></i>
                </button>
            </div>

            <!-- Content -->
            <div class="flex-1 overflow-y-auto p-6">
                <div id="loginHistoryContent" class="space-y-3">
                    <div class="flex items-center justify-center py-8">
                        <i class="fas fa-spinner fa-spin text-3xl text-gray-400"></i>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="p-4 border-t border-gray-200 bg-gray-50 rounded-b-2xl">
                <button onclick="closeLoginHistoryModal()" class="w-full px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors">
                    Close
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function openLoginHistoryModal() {
    document.getElementById('loginHistoryModal').classList.remove('hidden');
    loadLoginHistory();
}

function closeLoginHistoryModal() {
    document.getElementById('loginHistoryModal').classList.add('hidden');
}

async function loadLoginHistory() {
    const content = document.getElementById('loginHistoryContent');
    content.innerHTML = '<div class="flex items-center justify-center py-8"><i class="fas fa-spinner fa-spin text-3xl text-gray-400"></i></div>';
    
    try {
        const response = await fetch('../../controller/get_login_history.php');
        const data = await response.json();
        
        if (data.success && data.history && data.history.length > 0) {
            content.innerHTML = data.history.map((login, index) => {
                const date = new Date(login.login_time);
                const timeAgo = getTimeAgo(date);
                const isCurrentSession = index === 0;
                
                return `
                    <div class="flex items-start gap-4 p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors ${
                        isCurrentSession ? 'border-2 border-blue-500' : ''
                    }">
                        <div class="flex-shrink-0 w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
                            <i class="fas fa-${
                                login.device_type === 'mobile' ? 'mobile' : 'desktop'
                            } text-blue-600"></i>
                        </div>
                        <div class="flex-1">
                            <div class="flex items-center gap-2 mb-1">
                                <p class="font-medium text-gray-900">
                                    ${date.toLocaleDateString('en-US', { 
                                        month: 'long', 
                                        day: 'numeric', 
                                        year: 'numeric' 
                                    })} at ${date.toLocaleTimeString('en-US', { 
                                        hour: '2-digit', 
                                        minute: '2-digit' 
                                    })}
                                </p>
                                ${isCurrentSession ? '<span class="px-2 py-0.5 bg-blue-100 text-blue-700 text-xs font-medium rounded-full">Current Session</span>' : ''}
                            </div>
                            <p class="text-sm text-gray-500">${timeAgo}</p>
                            ${login.ip_address ? `<p class="text-xs text-gray-400 mt-1">IP: ${login.ip_address}</p>` : ''}
                        </div>
                    </div>
                `;
            }).join('');
        } else {
            content.innerHTML = `
                <div class="text-center py-12">
                    <i class="fas fa-clock text-6xl text-gray-300 mb-4"></i>
                    <p class="text-gray-500">No login history available</p>
                </div>
            `;
        }
    } catch (error) {
        console.error('Error loading login history:', error);
        content.innerHTML = `
            <div class="text-center py-12">
                <i class="fas fa-exclamation-triangle text-6xl text-red-300 mb-4"></i>
                <p class="text-red-500">Failed to load login history</p>
            </div>
        `;
    }
}

function getTimeAgo(date) {
    const seconds = Math.floor((new Date() - date) / 1000);
    
    let interval = seconds / 31536000;
    if (interval > 1) return Math.floor(interval) + ' year' + (Math.floor(interval) > 1 ? 's' : '') + ' ago';
    
    interval = seconds / 2592000;
    if (interval > 1) return Math.floor(interval) + ' month' + (Math.floor(interval) > 1 ? 's' : '') + ' ago';
    
    interval = seconds / 86400;
    if (interval > 1) return Math.floor(interval) + ' day' + (Math.floor(interval) > 1 ? 's' : '') + ' ago';
    
    interval = seconds / 3600;
    if (interval > 1) return Math.floor(interval) + ' hour' + (Math.floor(interval) > 1 ? 's' : '') + ' ago';
    
    interval = seconds / 60;
    if (interval > 1) return Math.floor(interval) + ' minute' + (Math.floor(interval) > 1 ? 's' : '') + ' ago';
    
    return 'Just now';
}
</script>

<?php include '../components/layout_footer.php'; ?>
