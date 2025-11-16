<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Function to get CPU usage on Windows
function getCpuUsage() {
    $command = 'powershell -Command "Get-Counter \'\\Processor(_Total)\\% Processor Time\' -SampleInterval 1 -MaxSamples 1 | Select-Object -ExpandProperty CounterSamples | Select-Object -ExpandProperty CookedValue"';
    $output = shell_exec($command);
    $value = trim($output);
    return is_numeric($value) ? round((float) $value) : 0;
}

// Function to get CPU details
function getCpuDetails() {
    $output = shell_exec('wmic cpu get name,numberofcores,numberoflogicalprocessors,maxclockspeed /format:list');
    $details = [
        'name' => 'Unknown',
        'cores' => 0,
        'threads' => 0,
        'speed' => 0
    ];
    
    if (preg_match('/Name=(.+)/', $output, $matches)) {
        $details['name'] = trim($matches[1]);
    }
    if (preg_match('/NumberOfCores=(\d+)/', $output, $matches)) {
        $details['cores'] = (int) $matches[1];
    }
    if (preg_match('/NumberOfLogicalProcessors=(\d+)/', $output, $matches)) {
        $details['threads'] = (int) $matches[1];
    }
    if (preg_match('/MaxClockSpeed=(\d+)/', $output, $matches)) {
        $details['speed'] = round((int) $matches[1] / 1000, 2); // Convert to GHz
    }
    
    return $details;
}

// Function to get memory details
function getMemoryDetails() {
    $output = shell_exec('wmic os get freephysicalmemory,totalvisiblememorysize /value');
    $free = 0;
    $total = 0;
    
    if (preg_match('/FreePhysicalMemory=(\d+)/', $output, $matches)) {
        $free = (float) $matches[1];
    }
    if (preg_match('/TotalVisibleMemorySize=(\d+)/', $output, $matches)) {
        $total = (float) $matches[1];
    }
    
    $used = $total - $free;
    $usagePercent = $total > 0 ? round(($used / $total) * 100) : 0;
    
    return [
        'percent' => $usagePercent,
        'total' => round($total / 1024 / 1024, 1), // Convert to GB
        'used' => round($used / 1024 / 1024, 1), // Convert to GB
        'free' => round($free / 1024 / 1024, 1) // Convert to GB
    ];
}

// Function to get disk details
function getDiskDetails() {
    $drives = [];
    $letters = range('C', 'Z');
    
    foreach ($letters as $letter) {
        $drive = $letter . ':';
        if (is_dir($drive)) {
            $total = disk_total_space($drive);
            $free = disk_free_space($drive);
            
            if ($total && $free) {
                $used = $total - $free;
                $drives[] = [
                    'drive' => $drive,
                    'total' => round($total / 1024 / 1024 / 1024, 1), // Convert to GB
                    'used' => round($used / 1024 / 1024 / 1024, 1), // Convert to GB
                    'free' => round($free / 1024 / 1024 / 1024, 1), // Convert to GB
                    'percent' => round(($used / $total) * 100)
                ];
            }
        }
    }
    
    return $drives;
}

// Function to get network statistics
function getNetworkStats() {
    $output = shell_exec('wmic nicconfig where IPEnabled=true get description,macaddress /format:list');
    $interfaces = [];
    
    preg_match_all('/Description=(.+?)\s+MACAddress=(.+?)(?=Description|\z)/s', $output, $matches, PREG_SET_ORDER);
    
    foreach ($matches as $match) {
        if (!empty(trim($match[1])) && !empty(trim($match[2]))) {
            $interfaces[] = [
                'name' => trim($match[1]),
                'mac' => trim($match[2])
            ];
        }
    }
    
    return $interfaces;
}

// Function to get running processes count
function getProcessCount() {
    $output = shell_exec('tasklist /FO CSV /NH');
    $lines = explode("\n", trim($output));
    return count($lines);
}

// Function to get system uptime
function getUptime() {
    $output = shell_exec('wmic os get lastbootuptime /value');
    if (preg_match('/LastBootUpTime=(\d{14})/', $output, $matches)) {
        $bootTimeStr = $matches[1];
        // Parse YYYYMMDDHHMMSS
        $year = substr($bootTimeStr, 0, 4);
        $month = substr($bootTimeStr, 4, 2);
        $day = substr($bootTimeStr, 6, 2);
        $hour = substr($bootTimeStr, 8, 2);
        $minute = substr($bootTimeStr, 10, 2);
        $second = substr($bootTimeStr, 12, 2);
        
        $bootTime = mktime($hour, $minute, $second, $month, $day, $year);
        $uptime = time() - $bootTime;
        
        $days = floor($uptime / 86400);
        $hours = floor(($uptime % 86400) / 3600);
        $minutes = floor(($uptime % 3600) / 60);
        
        return [
            'days' => $days,
            'hours' => $hours,
            'minutes' => $minutes,
            'formatted' => sprintf('%dd %dh %dm', $days, $hours, $minutes)
        ];
    }
    return ['days' => 0, 'hours' => 0, 'minutes' => 0, 'formatted' => 'Unknown'];
}

// Function to get OS info
function getOSInfo() {
    $output = shell_exec('wmic os get caption,version,osarchitecture /format:list');
    $info = [
        'name' => 'Unknown',
        'version' => 'Unknown',
        'architecture' => 'Unknown'
    ];
    
    if (preg_match('/Caption=(.+)/', $output, $matches)) {
        $info['name'] = trim($matches[1]);
    }
    if (preg_match('/Version=(.+)/', $output, $matches)) {
        $info['version'] = trim($matches[1]);
    }
    if (preg_match('/OSArchitecture=(.+)/', $output, $matches)) {
        $info['architecture'] = trim($matches[1]);
    }
    
    return $info;
}

$data = [
    'cpu' => [
        'usage' => getCpuUsage(),
        'details' => getCpuDetails()
    ],
    'memory' => getMemoryDetails(),
    'disk' => getDiskDetails(),
    'network' => getNetworkStats(),
    'processes' => getProcessCount(),
    'uptime' => getUptime(),
    'os' => getOSInfo()
];

echo json_encode($data);
?>