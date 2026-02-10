<?php
/**
 * Diagnostic Tool: Check Signature Status
 * Access: http://localhost/QCU-CAPSTONE-AMS/check_signatures.php
 */

require_once 'config/config.php';
require_once 'model/Database.php';

echo "<!DOCTYPE html><html><head><title>Signature Check</title>";
echo "<style>body{font-family:Arial;padding:20px;max-width:1200px;margin:0 auto;}";
echo "table{width:100%;border-collapse:collapse;margin:20px 0;}";
echo "th,td{border:1px solid #ddd;padding:12px;text-align:left;}";
echo "th{background:#1E3A8A;color:white;}";
echo ".old{background:#fee2e2;} .new{background:#d1fae5;} .none{background:#fef3c7;}";
echo ".btn{padding:8px 16px;background:#1E3A8A;color:white;border:none;border-radius:4px;cursor:pointer;margin:4px;}";
echo ".btn:hover{background:#1e40af;}</style></head><body>";

echo "<h1>📋 E-Signature Status Check</h1>";

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    $stmt = $conn->prepare("SELECT id, full_name, role, email, e_signature FROM users ORDER BY role, full_name");
    $stmt->execute();
    $users = $stmt->fetchAll();
    
    $stats = ['none' => 0, 'old' => 0, 'new' => 0];
    
    echo "<table>";
    echo "<tr><th>ID</th><th>Name</th><th>Role</th><th>Email</th><th>Signature Status</th><th>Type</th><th>Size</th></tr>";
    
    foreach ($users as $user) {
        $sig = $user['e_signature'];
        $status = '';
        $type = '';
        $size = '';
        $class = '';
        
        if (!$sig) {
            $status = '❌ No Signature';
            $type = 'None';
            $size = '-';
            $class = 'none';
            $stats['none']++;
        } elseif (strpos($sig, 'data:image/') === 0) {
            $status = '✅ Base64 (New)';
            $type = 'Base64 Data URI';
            $size = number_format(strlen($sig)) . ' bytes';
            $class = 'new';
            $stats['new']++;
        } else {
            $status = '⚠️ Filename (Old)';
            $type = 'File: ' . htmlspecialchars($sig);
            $size = 'N/A';
            $class = 'old';
            $stats['old']++;
        }
        
        echo "<tr class='{$class}'>";
        echo "<td>{$user['id']}</td>";
        echo "<td>" . htmlspecialchars($user['full_name']) . "</td>";
        echo "<td>" . htmlspecialchars($user['role']) . "</td>";
        echo "<td>" . htmlspecialchars($user['email']) . "</td>";
        echo "<td><strong>{$status}</strong></td>";
        echo "<td>{$type}</td>";
        echo "<td>{$size}</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    
    echo "<h2>📊 Summary</h2>";
    echo "<ul>";
    echo "<li><strong>✅ Base64 (New Format):</strong> {$stats['new']} users</li>";
    echo "<li><strong>⚠️ Filename (Old Format):</strong> {$stats['old']} users - <em>Need to re-upload</em></li>";
    echo "<li><strong>❌ No Signature:</strong> {$stats['none']} users</li>";
    echo "</ul>";
    
    if ($stats['old'] > 0) {
        echo "<h2>🔧 Action Required</h2>";
        echo "<p><strong style='color:#dc2626;'>You have {$stats['old']} user(s) with old file-based signatures.</strong></p>";
        echo "<p>These users need to:</p>";
        echo "<ol>";
        echo "<li>Log in to their account</li>";
        echo "<li>Go to <strong>Profile</strong> page</li>";
        echo "<li><strong>Re-upload</strong> their signature (will auto-convert to Base64)</li>";
        echo "</ol>";
        echo "<p><strong>Alternative:</strong> <a href='migrate_signatures_to_base64.php'><button class='btn'>Run Auto Migration</button></a></p>";
    } else {
        echo "<p style='color:#059669;'><strong>✅ All signatures are in the new format!</strong></p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color:#dc2626;'><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "<hr><p><a href='view/login.php'><button class='btn'>← Back to Login</button></a></p>";
echo "</body></html>";
?>
