# PC Health Agent - PowerShell Script
# This script collects PC health data and sends it to Firebase
# Run this script on startup or as a scheduled task

param(
    [Parameter(Mandatory=$true)]
    [string]$PCUnitId,
    
    [Parameter(Mandatory=$true)]
    [string]$TerminalName,
    
    [Parameter(Mandatory=$true)]
    [string]$RoomName,
    
    [Parameter(Mandatory=$false)]
    [string]$FirebaseURL = "https://your-project-id-default-rtdb.firebaseio.com",
    
    [Parameter(Mandatory=$false)]
    [int]$IntervalSeconds = 5
)

Write-Host "======================================" -ForegroundColor Cyan
Write-Host "  PC Health Monitoring Agent" -ForegroundColor Cyan
Write-Host "======================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "PC Unit ID    : $PCUnitId" -ForegroundColor Green
Write-Host "Terminal Name : $TerminalName" -ForegroundColor Green
Write-Host "Room Name     : $RoomName" -ForegroundColor Green
Write-Host "Update Interval: $IntervalSeconds seconds" -ForegroundColor Green
Write-Host ""
Write-Host "Press Ctrl+C to stop monitoring..." -ForegroundColor Yellow
Write-Host ""

# Function to get CPU usage
function Get-CPUUsage {
    $cpu = Get-Counter '\Processor(_Total)\% Processor Time' -SampleInterval 1 -MaxSamples 1
    return [Math]::Round($cpu.CounterSamples[0].CookedValue, 0)
}

# Function to get CPU details
function Get-CPUDetails {
    $cpu = Get-WmiObject Win32_Processor | Select-Object -First 1
    return @{
        name = $cpu.Name.Trim()
        cores = $cpu.NumberOfCores
        threads = $cpu.NumberOfLogicalProcessors
        speed = [Math]::Round($cpu.MaxClockSpeed / 1000, 2)
    }
}

# Function to get memory usage
function Get-MemoryUsage {
    $os = Get-WmiObject Win32_OperatingSystem
    $totalMB = [Math]::Round($os.TotalVisibleMemorySize / 1024)
    $freeMB = [Math]::Round($os.FreePhysicalMemory / 1024)
    $usedMB = $totalMB - $freeMB
    $percent = [Math]::Round(($usedMB / $totalMB) * 100, 0)
    
    return @{
        usage = $percent
        total = [Math]::Round($totalMB / 1024, 1)
        used = [Math]::Round($usedMB / 1024, 1)
        free = [Math]::Round($freeMB / 1024, 1)
    }
}

# Function to get disk usage
function Get-DiskUsage {
    $disks = Get-WmiObject Win32_LogicalDisk -Filter "DriveType=3" | ForEach-Object {
        $totalGB = [Math]::Round($_.Size / 1GB, 1)
        $freeGB = [Math]::Round($_.FreeSpace / 1GB, 1)
        $usedGB = $totalGB - $freeGB
        $percent = if ($totalGB -gt 0) { [Math]::Round(($usedGB / $totalGB) * 100, 0) } else { 0 }
        
        return @{
            drive = $_.DeviceID
            total = $totalGB
            used = $usedGB
            free = $freeGB
            usage = $percent
        }
    }
    return $disks
}

# Function to get network info
function Get-NetworkInfo {
    $adapters = Get-WmiObject Win32_NetworkAdapterConfiguration | Where-Object { $_.IPEnabled -eq $true } | ForEach-Object {
        return @{
            name = $_.Description
            mac = $_.MACAddress
            ip = $_.IPAddress -join ", "
        }
    }
    return $adapters
}

# Function to calculate health status
function Get-HealthStatus {
    param($cpu, $memory, $disk)
    
    $maxUsage = [Math]::Max([Math]::Max($cpu, $memory), $disk)
    
    if ($maxUsage -gt 90) {
        return "critical"
    } elseif ($maxUsage -gt 70) {
        return "warning"
    } else {
        return "healthy"
    }
}

# Function to send data to Firebase
function Send-ToFirebase {
    param($data)
    
    $json = $data | ConvertTo-Json -Depth 10 -Compress
    $url = "$FirebaseURL/pc_health/$PCUnitId.json"
    
    try {
        $response = Invoke-RestMethod -Uri $url -Method Put -Body $json -ContentType "application/json"
        return $true
    } catch {
        Write-Host "Error sending to Firebase: $_" -ForegroundColor Red
        return $false
    }
}

# Main monitoring loop
$iteration = 0
while ($true) {
    try {
        $iteration++
        
        # Collect metrics
        $cpuUsage = Get-CPUUsage
        $cpuDetails = Get-CPUDetails
        $memory = Get-MemoryUsage
        $disks = Get-DiskUsage
        $network = Get-NetworkInfo
        
        # Calculate health status
        $healthStatus = Get-HealthStatus -cpu $cpuUsage -memory $memory.usage -disk ($disks | Measure-Object -Property usage -Maximum).Maximum
        
        # Prepare data
        $data = @{
            pcUnitId = $PCUnitId
            terminalName = $TerminalName
            roomName = $RoomName
            status = "online"
            lastUpdate = [DateTimeOffset]::UtcNow.ToUnixTimeMilliseconds()
            healthStatus = $healthStatus
            cpu = @{
                usage = $cpuUsage
                name = $cpuDetails.name
                cores = $cpuDetails.cores
                threads = $cpuDetails.threads
            }
            memory = $memory
            disks = $disks
            network = $network
        }
        
        # Send to Firebase
        $success = Send-ToFirebase -data $data
        
        # Display status
        $timestamp = Get-Date -Format "HH:mm:ss"
        $statusColor = switch ($healthStatus) {
            "critical" { "Red" }
            "warning" { "Yellow" }
            default { "Green" }
        }
        
        if ($success) {
            Write-Host "[$timestamp] " -NoNewline -ForegroundColor Gray
            Write-Host "CPU: $cpuUsage% " -NoNewline -ForegroundColor Cyan
            Write-Host "RAM: $($memory.usage)% " -NoNewline -ForegroundColor Cyan
            Write-Host "DISK: $(($disks | Measure-Object -Property usage -Maximum).Maximum)% " -NoNewline -ForegroundColor Cyan
            Write-Host "Status: $healthStatus" -ForegroundColor $statusColor
        } else {
            Write-Host "[$timestamp] Failed to send data" -ForegroundColor Red
        }
        
        # Wait for next interval
        Start-Sleep -Seconds $IntervalSeconds
        
    } catch {
        Write-Host "Error in monitoring loop: $_" -ForegroundColor Red
        Start-Sleep -Seconds $IntervalSeconds
    }
}
