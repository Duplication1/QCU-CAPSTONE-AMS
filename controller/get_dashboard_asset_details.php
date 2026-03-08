<?php
ob_start();
ini_set('display_errors', 0);
error_reporting(0);
session_start();

function sendJson($data, $code = 200) {
    ob_clean();
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit();
}

if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true || $_SESSION['role'] !== 'Administrator') {
    sendJson(['error' => 'Unauthorized'], 403);
}

require_once '../config/config.php';

mysqli_report(MYSQLI_REPORT_OFF);

try {
    $dbConfig = Config::database();
    $conn = new mysqli($dbConfig['host'], $dbConfig['username'], $dbConfig['password'], $dbConfig['name']);
    $conn->set_charset('utf8mb4');
    if ($conn->connect_error) sendJson(['error' => 'DB connection failed'], 500);

    $filter = $_GET['filter'] ?? '';
    $value  = $_GET['value']  ?? '';

    if ($filter === 'asset_status') {
        if ($value === 'all') {
            $sql = "SELECT a.asset_tag, a.asset_name, a.asset_type, a.status, a.`condition`,
                           COALESCE(r.name, '-') AS location,
                           DATE_FORMAT(a.created_at, '%Y-%m-%d') AS acquired_date
                    FROM assets a
                    LEFT JOIN rooms r ON a.room_id = r.id
                    WHERE a.status NOT IN ('Disposed','Archive')
                    ORDER BY a.asset_name ASC";
            $stmt = $conn->prepare($sql);
            if (!$stmt) sendJson(['error' => 'Query error: ' . $conn->error], 500);
            $stmt->execute();
        } else {
            $sql = "SELECT a.asset_tag, a.asset_name, a.asset_type, a.status, a.`condition`,
                           COALESCE(r.name, '-') AS location,
                           DATE_FORMAT(a.created_at, '%Y-%m-%d') AS acquired_date
                    FROM assets a
                    LEFT JOIN rooms r ON a.room_id = r.id
                    WHERE a.status = ?
                    ORDER BY a.asset_name ASC";
            $stmt = $conn->prepare($sql);
            if (!$stmt) sendJson(['error' => 'Query error: ' . $conn->error], 500);
            $stmt->bind_param('s', $value);
            $stmt->execute();
        }
        $res    = $stmt->get_result();
        $assets = [];
        while ($row = $res->fetch_assoc()) $assets[] = $row;
        sendJson(['title' => $value === 'all' ? 'All Active Assets' : $value . ' Assets', 'total' => count($assets), 'assets' => $assets]);
    }

    if ($filter === 'type') {
        $sql = "SELECT a.asset_tag, a.asset_name, a.asset_type, a.status, a.`condition`,
                       COALESCE(r.name, '-') AS location,
                       DATE_FORMAT(a.created_at, '%Y-%m-%d') AS acquired_date
                FROM assets a
                LEFT JOIN rooms r ON a.room_id = r.id
                WHERE a.asset_type = ? AND a.status NOT IN ('Disposed','Archive')
                ORDER BY a.asset_name ASC";
        $stmt = $conn->prepare($sql);
        if (!$stmt) sendJson(['error' => 'Query error: ' . $conn->error], 500);
        $stmt->bind_param('s', $value);
        $stmt->execute();
        $res    = $stmt->get_result();
        $assets = [];
        while ($row = $res->fetch_assoc()) $assets[] = $row;
        sendJson(['title' => $value . ' Assets', 'total' => count($assets), 'assets' => $assets]);
    }

    if ($filter === 'month') {
        $sql = "SELECT a.asset_tag, a.asset_name, a.asset_type, a.status, a.`condition`,
                       COALESCE(r.name, '-') AS location,
                       DATE_FORMAT(a.created_at, '%Y-%m-%d') AS acquired_date
                FROM assets a
                LEFT JOIN rooms r ON a.room_id = r.id
                WHERE DATE_FORMAT(a.created_at, '%Y-%m') = ?
                ORDER BY a.created_at DESC";
        $stmt = $conn->prepare($sql);
        if (!$stmt) sendJson(['error' => 'Query error: ' . $conn->error], 500);
        $stmt->bind_param('s', $value);
        $stmt->execute();
        $res    = $stmt->get_result();
        $assets = [];
        while ($row = $res->fetch_assoc()) $assets[] = $row;
        sendJson(['title' => 'Assets Added in ' . $value, 'total' => count($assets), 'assets' => $assets]);
    }

    if ($filter === 'borrowing_status') {
        $sql = "SELECT a.asset_tag, a.asset_name, a.asset_type,
                       COALESCE(ab.borrower_name, u.full_name, '-') AS borrower,
                       ab.status,
                       DATE_FORMAT(ab.borrowed_date, '%Y-%m-%d') AS borrowed_date,
                       DATE_FORMAT(ab.expected_return_date, '%Y-%m-%d') AS return_date
                FROM asset_borrowing ab
                JOIN assets a ON ab.asset_id = a.id
                LEFT JOIN users u ON ab.borrower_id = u.id
                WHERE ab.status = ?
                ORDER BY ab.borrowed_date DESC";
        $stmt = $conn->prepare($sql);
        if (!$stmt) sendJson(['error' => 'Query error: ' . $conn->error], 500);
        $stmt->bind_param('s', $value);
        $stmt->execute();
        $res    = $stmt->get_result();
        $assets = [];
        while ($row = $res->fetch_assoc()) $assets[] = $row;
        sendJson(['title' => $value . ' Borrowings', 'total' => count($assets), 'assets' => $assets, 'mode' => 'borrowing']);
    }

    if ($filter === 'issue_status') {
        $statusMap = ['Pending' => 'Open', 'In Progress' => 'In Progress', 'Resolved' => 'Resolved', 'Closed' => 'Closed'];
        $dbStatus  = $statusMap[$value] ?? $value;
        $sql = "SELECT i.id, i.title AS issue_title, i.category, i.status,
                       i.assigned_technician,
                       COALESCE(r.name, '-') AS room,
                       DATE_FORMAT(i.created_at, '%Y-%m-%d') AS reported_date
                FROM issues i
                LEFT JOIN rooms r ON i.room_id = r.id
                WHERE i.status = ?
                ORDER BY i.created_at DESC";
        $stmt = $conn->prepare($sql);
        if (!$stmt) sendJson(['error' => 'Query error: ' . $conn->error], 500);
        $stmt->bind_param('s', $dbStatus);
        $stmt->execute();
        $res    = $stmt->get_result();
        $assets = [];
        while ($row = $res->fetch_assoc()) $assets[] = $row;
        sendJson(['title' => $value . ' Issues', 'total' => count($assets), 'assets' => $assets, 'mode' => 'issue']);
    }

    sendJson(['error' => 'Unknown filter'], 400);

} catch (Throwable $e) {
    sendJson(['error' => 'Server error: ' . $e->getMessage()], 500);
}