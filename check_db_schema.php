<?php
require_once "config/config.php";
$res = $conn->query("SHOW COLUMNS FROM issues");
while($r = $res->fetch_assoc()) {
    echo $r["Field"] . " | " . $r["Type"] . " | " . $r["Default"] . "\n";
}
