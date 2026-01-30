<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true || $_SESSION['role'] !== 'Technician') {
    die("Not logged in as technician");
}

require_once '../../config/config.php';

$dbConfig = Config::database();
$conn = new mysqli($dbConfig['host'], $dbConfig['username'], $dbConfig['password'], $dbConfig['name']);
$conn->set_charset('utf8mb4');

$technician_id = $_SESSION['user_id'];

echo "<h1>Dashboard Debug Information</h1>";
echo "<style>body { font-family: monospace; padding: 20px; } table { border-collapse: collapse; margin: 20px 0; } td, th { border: 1px solid #ddd; padding: 8px; } th { background: #f0f0f0; }</style>";

echo "<h2>Session Information</h2>";
echo "<table>";
echo "<tr><th>Key</th><th>Value</th></tr>";
echo "<tr><td>User ID</td><td>" . $_SESSION['user_id'] . "</td></tr>";
echo "<tr><td>Role</td><td>" . $_SESSION['role'] . "</td></tr>";
echo "<tr><td>Full Name</td><td>" . ($_SESSION['full_name'] ?? 'Not set') . "</td></tr>";
echo "</table>";

echo "<h2>Database Connection</h2>";
echo "<table>";
echo "<tr><th>Status</th><th>Value</th></tr>";
echo "<tr><td>Connected</td><td>" . ($conn->ping() ? "✅ Yes" : "❌ No") . "</td></tr>";
echo "<tr><td>Database</td><td>" . $dbConfig['name'] . "</td></tr>";
echo "</table>";

echo "<h2>Issues Table Data</h2>";

// Check total issues
$totalIssues = $conn->query("SELECT COUNT(*) as count FROM issues");
$totalCount = $totalIssues ? $totalIssues->fetch_assoc()['count'] : 0;

// Check issues assigned to this technician
$assignedIssues = $conn->query("SELECT COUNT(*) as count FROM issues WHERE assigned_to = " . intval($technician_id));
$assignedCount = $assignedIssues ? $assignedIssues->fetch_assoc()['count'] : 0;

// Check all technician IDs in issues
$technicianIds = $conn->query("SELECT DISTINCT assigned_to FROM issues WHERE assigned_to IS NOT NULL");

echo "<table>";
echo "<tr><th>Metric</th><th>Count</th></tr>";
echo "<tr><td>Total issues in database</td><td>" . $totalCount . "</td></tr>";
echo "<tr><td>Issues assigned to you (ID: $technician_id)</td><td><strong>" . $assignedCount . "</strong></td></tr>";
echo "</table>";

if ($technicianIds && $technicianIds->num_rows > 0) {
    echo "<h3>Technician IDs found in issues table:</h3>";
    echo "<table>";
    echo "<tr><th>Technician ID</th><th>Issues Count</th></tr>";
    while ($row = $technicianIds->fetch_assoc()) {
        $techId = $row['assigned_to'];
        $countResult = $conn->query("SELECT COUNT(*) as count FROM issues WHERE assigned_to = " . intval($techId));
        $count = $countResult ? $countResult->fetch_assoc()['count'] : 0;
        $highlight = ($techId == $technician_id) ? " style='background: yellow;'" : "";
        echo "<tr$highlight><td>" . $techId . "</td><td>" . $count . "</td></tr>";
    }
    echo "</table>";
}

// Sample issues
echo "<h3>Sample Issues in Database (First 5):</h3>";
$sampleIssues = $conn->query("SELECT id, issue_type, status, priority, assigned_to, created_at FROM issues ORDER BY created_at DESC LIMIT 5");
if ($sampleIssues && $sampleIssues->num_rows > 0) {
    echo "<table>";
    echo "<tr><th>ID</th><th>Type</th><th>Status</th><th>Priority</th><th>Assigned To</th><th>Created</th></tr>";
    while ($row = $sampleIssues->fetch_assoc()) {
        $highlight = ($row['assigned_to'] == $technician_id) ? " style='background: yellow;'" : "";
        echo "<tr$highlight>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . $row['issue_type'] . "</td>";
        echo "<td>" . $row['status'] . "</td>";
        echo "<td>" . $row['priority'] . "</td>";
        echo "<td>" . $row['assigned_to'] . "</td>";
        echo "<td>" . $row['created_at'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p><strong>⚠️ No issues found in the database!</strong></p>";
}

echo "<h2>Maintenance Schedules</h2>";
$totalMaintenance = $conn->query("SELECT COUNT(*) as count FROM maintenance_schedules");
$maintenanceCount = $totalMaintenance ? $totalMaintenance->fetch_assoc()['count'] : 0;

echo "<table>";
echo "<tr><th>Metric</th><th>Count</th></tr>";
echo "<tr><td>Total maintenance schedules</td><td>" . $maintenanceCount . "</td></tr>";
echo "</table>";

echo "<h2>Assets Table</h2>";
$totalAssets = $conn->query("SELECT COUNT(*) as count FROM assets");
$assetsCount = $totalAssets ? $totalAssets->fetch_assoc()['count'] : 0;

$underMaintenance = $conn->query("SELECT COUNT(*) as count FROM assets WHERE status = 'Under Maintenance'");
$maintenanceAssetsCount = $underMaintenance ? $underMaintenance->fetch_assoc()['count'] : 0;

echo "<table>";
echo "<tr><th>Metric</th><th>Count</th></tr>";
echo "<tr><td>Total assets in database</td><td>" . $assetsCount . "</td></tr>";
echo "<tr><td>Assets under maintenance</td><td>" . $maintenanceAssetsCount . "</td></tr>";
echo "</table>";

echo "<h2>Users Table - Technicians</h2>";
$technicians = $conn->query("SELECT id, full_name, email, role FROM users WHERE role = 'Technician'");
if ($technicians && $technicians->num_rows > 0) {
    echo "<table>";
    echo "<tr><th>ID</th><th>Name</th><th>Email</th></tr>";
    while ($row = $technicians->fetch_assoc()) {
        $highlight = ($row['id'] == $technician_id) ? " style='background: yellow;'" : "";
        echo "<tr$highlight>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . $row['full_name'] . "</td>";
        echo "<td>" . $row['email'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p><strong>⚠️ No technicians found in users table!</strong></p>";
}

echo "<hr>";
echo "<h2>Solution</h2>";

if ($assignedCount == 0 && $totalCount == 0) {
    echo "<p><strong>🔍 ISSUE FOUND:</strong> The issues table is empty. You need to:</p>";
    echo "<ol>";
    echo "<li>Have users submit issues through the system</li>";
    echo "<li>Or import sample data for testing</li>";
    echo "</ol>";
} elseif ($assignedCount == 0 && $totalCount > 0) {
    echo "<p><strong>🔍 ISSUE FOUND:</strong> There are $totalCount issues in the database, but none are assigned to you (Technician ID: $technician_id).</p>";
    echo "<p><strong>Solution:</strong> An Administrator needs to assign issues to you. Check the 'Tickets' section in the Administrator dashboard.</p>";
} else {
    echo "<p><strong>✅ Data looks good!</strong> You have $assignedCount issues assigned to you.</p>";
    echo "<p>If the dashboard still shows zeros, there might be a display issue. Try clearing your browser cache.</p>";
}

echo "<br><a href='index.php' style='padding: 10px 20px; background: #1E3A8A; color: white; text-decoration: none; border-radius: 5px;'>← Back to Dashboard</a>";

$conn->close();
?>
