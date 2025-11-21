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
include '../components/layout_header.php';
?>

<main class="p-3 space-y-3">
    <!-- Filters -->
    <div class="bg-white rounded-lg shadow p-3">
        <div class="flex flex-wrap items-center gap-2">
            <div class="flex-1 min-w-[200px]">
                <input type="text" id="searchInput" placeholder="Search PC or Room..." 
                       class="w-full border border-gray-300 rounded px-3 py-1.5 text-xs focus:ring-1 focus:ring-purple-500 focus:border-purple-500">
            </div>
            <select id="roomFilter" class="border border-gray-300 rounded px-3 py-1.5 text-xs focus:ring-1 focus:ring-purple-500">
                <option value="">All Rooms</option>
            </select>
            <select id="statusFilter" class="border border-gray-300 rounded px-3 py-1.5 text-xs focus:ring-1 focus:ring-purple-500">
                <option value="">All Status</option>
                <option value="healthy">Healthy</option>
                <option value="warning">Warning</option>
                <option value="critical">Critical</option>
                <option value="offline">Offline</option>
            </select>
            <select id="tempFilter" class="border border-gray-300 rounded px-3 py-1.5 text-xs focus:ring-1 focus:ring-purple-500">
                <option value="">All Temps</option>
                <option value="normal">Normal (&lt;70°C)</option>
                <option value="hot">Hot (70-80°C)</option>
                <option value="critical">Critical (&gt;80°C)</option>
            </select>
            <select id="alertFilter" class="border border-gray-300 rounded px-3 py-1.5 text-xs focus:ring-1 focus:ring-purple-500">
                <option value="">All</option>
                <option value="alerts">With Alerts</option>
                <option value="no-alerts">No Alerts</option>
            </select>
            <button onclick="clearFilters()" class="px-3 py-1.5 bg-gray-100 hover:bg-gray-200 rounded text-xs font-medium text-gray-700">
                Clear
            </button>
            <div class="text-[10px] text-gray-500 ml-auto" id="lastUpdateTime">--:--</div>
        </div>
    </div>

    <!-- Alert Banner -->
    <div id="alertBanner" class="hidden bg-red-50 border-l-4 border-red-500 p-3 rounded text-xs">
        <div class="flex gap-2">
            <svg class="h-4 w-4 text-red-500 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
            </svg>
            <div>
                <p class="font-semibold text-red-800 mb-1">Hardware Alerts</p>
                <div id="alertList" class="text-red-700"></div>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-2 md:grid-cols-5 gap-2">
        <div class="bg-white rounded-lg shadow p-3">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 text-[10px]">Total PCs</p>
                    <p class="text-xl font-bold text-gray-800" id="totalPCs">0</p>
                </div>
                <div class="bg-blue-100 p-2 rounded">
                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-lg shadow p-3">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 text-[10px]">Online</p>
                    <p class="text-xl font-bold text-green-600" id="onlinePCs">0</p>
                </div>
                <div class="bg-green-100 p-2 rounded">
                    <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-lg shadow p-3">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 text-[10px]">Overheating</p>
                    <p class="text-xl font-bold text-orange-600" id="overheatingPCs">0</p>
                </div>
                <div class="bg-orange-100 p-2 rounded">
                    <svg class="w-5 h-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 18.657A8 8 0 016.343 7.343S7 9 9 10c0-2 .5-5 2.986-7C14 5 16.09 5.777 17.656 7.343A7.975 7.975 0 0120 13a7.975 7.975 0 01-2.343 5.657z"/>
                    </svg>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-lg shadow p-3">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 text-[10px]">HW Alerts</p>
                    <p class="text-xl font-bold text-red-600" id="alertPCs">0</p>
                </div>
                <div class="bg-red-100 p-2 rounded">
                    <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-lg shadow p-3">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 text-[10px]">Critical</p>
                    <p class="text-xl font-bold text-red-600" id="criticalPCs">0</p>
                </div>
                <div class="bg-red-100 p-2 rounded">
                    <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- PC Grid -->
    <div class="bg-white rounded-lg shadow p-3">
        <div class="flex items-center justify-between mb-3">
            <h3 class="text-sm font-semibold text-gray-800">Laboratory Computers</h3>
            <div class="flex items-center gap-2 text-[10px] text-gray-600">
                <div class="flex items-center gap-1">
                    <span class="w-2 h-2 bg-green-500 rounded-full"></span>
                    <span>Healthy</span>
                </div>
                <div class="flex items-center gap-1">
                    <span class="w-2 h-2 bg-orange-500 rounded-full"></span>
                    <span>Hot</span>
                </div>
                <div class="flex items-center gap-1">
                    <span class="w-2 h-2 bg-red-500 rounded-full"></span>
                    <span>Critical</span>
                </div>
                <div class="flex items-center gap-1">
                    <span class="w-2 h-2 bg-gray-400 rounded-full"></span>
                    <span>Offline</span>
                </div>
            </div>
        </div>

        <div id="pcGrid" class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-2">
            <div class="text-center py-6 col-span-full">
                <div class="inline-block animate-spin rounded-full h-6 w-6 border-2 border-gray-300 border-t-purple-600"></div>
                <p class="mt-2 text-xs text-gray-600">Loading...</p>
            </div>
        </div>
    </div>
</main>

<!-- PC Detail Modal -->
<div id="pcDetailModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50 p-3">
    <div class="bg-white rounded-lg shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
        <div class="p-3 border-b border-gray-200 flex items-center justify-between">
            <h3 class="text-base font-bold text-gray-800" id="modalTitle">PC Details</h3>
            <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        <div id="modalContent" class="p-3"></div>
    </div>
</div>

<!-- Firebase SDK -->
<script src="https://www.gstatic.com/firebasejs/9.22.0/firebase-app-compat.js"></script>
<script src="https://www.gstatic.com/firebasejs/9.22.0/firebase-database-compat.js"></script>

<script>
let firebaseApp, database;
let pcUnitsData = [];
let healthDataCache = {};
let roomsData = [];
let allAlerts = [];

// Initialize Firebase
async function initFirebase() {
    try {
        const response = await fetch('../../controller/get_pc_health_data.php?action=getFirebaseConfig');
        const result = await response.json();
        
        if (result.success) {
            const config = result.config;
            firebaseApp = firebase.initializeApp(config);
            database = firebase.database();
            
            database.ref('pc_health').on('value', (snapshot) => {
                healthDataCache = snapshot.val() || {};
                updateDashboard();
            });
            
            console.log('Firebase connected successfully');
        } else {
            throw new Error('Failed to get Firebase config');
        }
    } catch (error) {
        console.error('Firebase initialization error:', error);
    }
}

// Load PC units and rooms
async function loadPCUnits() {
    try {
        const [pcResponse, roomResponse] = await Promise.all([
            fetch('../../controller/get_pc_health_data.php?action=getAll'),
            fetch('../../controller/get_pc_health_data.php?action=getRooms')
        ]);
        
        const pcResult = await pcResponse.json();
        const roomResult = await roomResponse.json();
        
        if (pcResult.success) {
            pcUnitsData = pcResult.data;
        }
        
        if (roomResult.success) {
            roomsData = roomResult.data;
            populateRoomFilter();
        }
        
        updateDashboard();
    } catch (error) {
        console.error('Error loading PC units:', error);
    }
}

// Populate room filter
function populateRoomFilter() {
    const filter = document.getElementById('roomFilter');
    roomsData.forEach(room => {
        const option = document.createElement('option');
        option.value = room.id;
        option.textContent = room.name;
        filter.appendChild(option);
    });
    
    // Add event listeners for all filters
    document.getElementById('roomFilter').addEventListener('change', updateDashboard);
    document.getElementById('statusFilter').addEventListener('change', updateDashboard);
    document.getElementById('tempFilter').addEventListener('change', updateDashboard);
    document.getElementById('alertFilter').addEventListener('change', updateDashboard);
    document.getElementById('searchInput').addEventListener('input', updateDashboard);
}

// Clear all filters
function clearFilters() {
    document.getElementById('searchInput').value = '';
    document.getElementById('roomFilter').value = '';
    document.getElementById('statusFilter').value = '';
    document.getElementById('tempFilter').value = '';
    document.getElementById('alertFilter').value = '';
    updateDashboard();
}

// Update dashboard
function updateDashboard() {
    const selectedRoom = document.getElementById('roomFilter').value;
    const selectedStatus = document.getElementById('statusFilter').value;
    const selectedTemp = document.getElementById('tempFilter').value;
    const selectedAlert = document.getElementById('alertFilter').value;
    const searchText = document.getElementById('searchInput').value.toLowerCase();
    
    let filteredPCs = pcUnitsData;
    
    // Apply filters
    filteredPCs = filteredPCs.filter(pc => {
        const healthData = healthDataCache[pc.id];
        const status = getHealthStatus(healthData);
        const pcName = (pc.terminal_number || `TH-${pc.id}`).toLowerCase();
        const roomName = (pc.room_name || '').toLowerCase();
        
        // Search filter
        if (searchText && !pcName.includes(searchText) && !roomName.includes(searchText)) {
            return false;
        }
        
        // Room filter
        if (selectedRoom && pc.room_id != selectedRoom) {
            return false;
        }
        
        // Status filter
        if (selectedStatus) {
            if (selectedStatus === 'healthy' && status !== 'healthy' && status !== 'online') return false;
            if (selectedStatus === 'warning' && status !== 'warning') return false;
            if (selectedStatus === 'critical' && status !== 'critical') return false;
            if (selectedStatus === 'offline' && status !== 'offline') return false;
        }
        
        // Temperature filter
        if (selectedTemp && healthData?.cpu?.temperature) {
            const temp = healthData.cpu.temperature;
            if (selectedTemp === 'normal' && temp >= 70) return false;
            if (selectedTemp === 'hot' && (temp < 70 || temp > 80)) return false;
            if (selectedTemp === 'critical' && temp <= 80) return false;
        } else if (selectedTemp) {
            return false; // Filter out PCs without temperature data
        }
        
        // Alert filter
        if (selectedAlert) {
            const hasAlerts = healthData?.hasAlerts === true;
            if (selectedAlert === 'alerts' && !hasAlerts) return false;
            if (selectedAlert === 'no-alerts' && hasAlerts) return false;
        }
        
        return true;
    });
    
    let totalPCs = filteredPCs.length;
    let onlineCount = 0;
    let overheatingCount = 0;
    let alertCount = 0;
    let criticalCount = 0;
    allAlerts = [];
    
    const grid = document.getElementById('pcGrid');
    grid.innerHTML = '';
    
    if (filteredPCs.length === 0) {
        grid.innerHTML = `
            <div class="text-center py-8 col-span-full">
                <svg class="w-12 h-12 text-gray-400 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M12 12h.01M12 12h.01M12 12h.01M12 21a9 9 0 100-18 9 9 0 000 18z"/>
                </svg>
                <p class="text-sm text-gray-600">No PCs match the selected filters</p>
            </div>
        `;
    } else {
        filteredPCs.forEach(pc => {
            const healthData = healthDataCache[pc.id];
            const status = getHealthStatus(healthData);
            
            if (status !== 'offline') onlineCount++;
            if (status === 'critical') criticalCount++;
            
            // Check for temperature alerts
            if (healthData?.cpu?.temperature) {
                if (healthData.cpu.temperature > 70) {
                    overheatingCount++;
                    if (healthData.cpu.temperature > 80) {
                        allAlerts.push({
                            pc: pc.terminal_number || `TH-${pc.id}`,
                            room: pc.room_name,
                            message: `CPU overheating: ${healthData.cpu.temperature}°C`,
                            severity: 'critical'
                        });
                    }
                }
            }
            
            // Check for hardware alerts
            if (healthData?.hasAlerts && healthData?.alerts) {
                alertCount++;
                healthData.alerts.forEach(alert => {
                    allAlerts.push({
                        pc: pc.terminal_number || `TH-${pc.id}`,
                        room: pc.room_name,
                        message: alert.message,
                        severity: alert.type
                    });
                });
            }
            
            const card = createPCCard(pc, healthData, status);
            grid.appendChild(card);
        });
    }
    
    document.getElementById('totalPCs').textContent = totalPCs;
    document.getElementById('onlinePCs').textContent = onlineCount;
    document.getElementById('overheatingPCs').textContent = overheatingCount;
    document.getElementById('alertPCs').textContent = alertCount;
    document.getElementById('criticalPCs').textContent = criticalCount;
    document.getElementById('lastUpdateTime').textContent = new Date().toLocaleTimeString();
    
    updateAlertBanner();
}

// Update alert banner
function updateAlertBanner() {
    const banner = document.getElementById('alertBanner');
    const alertList = document.getElementById('alertList');
    
    if (allAlerts.length > 0) {
        alertList.innerHTML = '<ul class="list-disc list-inside space-y-1">' +
            allAlerts.map(alert => `<li><strong>${alert.pc}</strong> (${alert.room}): ${alert.message}</li>`).join('') +
            '</ul>';
        banner.classList.remove('hidden');
    } else {
        banner.classList.add('hidden');
    }
}

// Get health status
function getHealthStatus(healthData) {
    if (!healthData || !healthData.status || healthData.status === 'offline') {
        if (healthData && healthData.lastUpdate) {
            const timeDiff = Date.now() - healthData.lastUpdate;
            if (timeDiff < 30000) {
                return healthData.healthStatus || 'healthy';
            }
        }
        return 'offline';
    }
    
    return healthData.healthStatus || 'healthy';
}

// Create PC card
function createPCCard(pc, healthData, status) {
    const card = document.createElement('div');
    
    const hasTemperatureAlert = healthData?.cpu?.temperature > 70;
    const hasHardwareAlert = healthData?.hasAlerts === true;
    const isCritical = status === 'critical' || healthData?.cpu?.temperature > 80;
    
    card.className = `bg-white border-2 rounded-lg p-2 cursor-pointer transition-all hover:shadow ${
        isCritical ? 'border-red-500' : 
        hasTemperatureAlert || hasHardwareAlert ? 'border-orange-500' : 
        status === 'online' || status === 'healthy' ? 'border-green-500' : 
        'border-gray-300'
    }`;
    
    card.onclick = () => showPCDetail(pc, healthData);
    
    const statusColor = 
        isCritical ? 'bg-red-500' : 
        hasTemperatureAlert || hasHardwareAlert ? 'bg-orange-500' : 
        status === 'online' || status === 'healthy' ? 'bg-green-500' : 
        'bg-gray-400';
    
    card.innerHTML = `
        <div class="flex items-center justify-between mb-2">
            <div class="flex items-center gap-1">
                <span class="w-2 h-2 ${statusColor} rounded-full ${status !== 'offline' ? 'animate-pulse' : ''}"></span>
                <span class="font-bold text-gray-800 text-xs">${pc.terminal_number || 'TH-' + pc.id}</span>
            </div>
            <span class="text-[10px] text-gray-500">${pc.room_name || 'Unknown'}</span>
        </div>
        
        ${healthData && status !== 'offline' ? `
            <div class="space-y-1.5">
                ${healthData.cpu?.temperature ? `
                    <div class="flex justify-between items-center">
                        <span class="text-[10px] text-gray-600">🌡️</span>
                        <span class="text-xs font-bold ${
                            healthData.cpu.temperature > 80 ? 'text-red-600' :
                            healthData.cpu.temperature > 70 ? 'text-orange-600' :
                            'text-green-600'
                        }">${healthData.cpu.temperature}°C</span>
                    </div>
                ` : ''}
                
                ${hasHardwareAlert ? `
                    <div class="bg-red-50 border border-red-200 rounded p-1">
                        <p class="text-[10px] text-red-700 font-semibold">⚠️ Alert</p>
                    </div>
                ` : ''}
                
                <div class="flex justify-between items-center">
                    <span class="text-[10px] text-gray-600">CPU</span>
                    <span class="text-xs font-semibold ${healthData.cpu?.usage > 80 ? 'text-red-600' : 'text-gray-800'}">${healthData.cpu?.usage || 0}%</span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-1.5">
                    <div class="h-1.5 rounded-full ${healthData.cpu?.usage > 80 ? 'bg-red-500' : 'bg-blue-500'}" style="width: ${healthData.cpu?.usage || 0}%"></div>
                </div>
                
                <div class="flex justify-between items-center">
                    <span class="text-[10px] text-gray-600">RAM</span>
                    <span class="text-xs font-semibold ${healthData.memory?.usage > 80 ? 'text-red-600' : 'text-gray-800'}">${healthData.memory?.usage || 0}%</span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-1.5">
                    <div class="h-1.5 rounded-full ${healthData.memory?.usage > 80 ? 'bg-red-500' : 'bg-green-500'}" style="width: ${healthData.memory?.usage || 0}%"></div>
                </div>
                
                <div class="flex justify-between items-center">
                    <span class="text-[10px] text-gray-600">Disk</span>
                    <span class="text-xs font-semibold ${healthData.disks?.[0]?.usage > 80 ? 'text-red-600' : 'text-gray-800'}">${healthData.disks?.[0]?.usage || 0}%</span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-1.5">
                    <div class="h-1.5 rounded-full ${healthData.disks?.[0]?.usage > 80 ? 'bg-red-500' : 'bg-purple-500'}" style="width: ${healthData.disks?.[0]?.usage || 0}%"></div>
                </div>
            </div>
        ` : `
            <div class="text-center py-3">
                <p class="text-gray-400 text-xs">Offline</p>
            </div>
        `}
    `;
    
    return card;
}

// Show PC detail modal
function showPCDetail(pc, healthData) {
    const modal = document.getElementById('pcDetailModal');
    const title = document.getElementById('modalTitle');
    const content = document.getElementById('modalContent');
    
    title.textContent = `${pc.terminal_number || 'TH-' + pc.id} - ${pc.room_name}`;
    
    if (!healthData || healthData.status === 'offline') {
        content.innerHTML = `
            <div class="text-center py-6">
                <svg class="w-12 h-12 text-gray-400 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                </svg>
                <h4 class="text-base font-semibold text-gray-600 mb-1">PC Offline</h4>
                <p class="text-xs text-gray-500">No data available</p>
                ${healthData?.lastUpdate ? `<p class="text-[10px] text-gray-400 mt-1">Last: ${new Date(healthData.lastUpdate).toLocaleTimeString()}</p>` : ''}
            </div>
        `;
    } else {
        content.innerHTML = `
            <div class="space-y-3 text-xs">
                ${healthData.hasAlerts && healthData.alerts ? `
                    <div class="bg-red-50 border-l-4 border-red-500 p-2 rounded">
                        <p class="font-bold text-red-800 mb-1 flex items-center gap-1">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                            </svg>
                            Hardware Alerts
                        </p>
                        <ul class="space-y-0.5">
                            ${healthData.alerts.map(alert => `
                                <li class="${alert.type === 'critical' ? 'text-red-700 font-semibold' : 'text-orange-700'}">
                                    • ${alert.message}
                                </li>
                            `).join('')}
                        </ul>
                    </div>
                ` : ''}
                
                <div class="bg-gray-50 rounded p-2 space-y-1">
                    <div class="flex justify-between">
                        <span class="text-gray-600">Status:</span>
                        <span class="font-semibold text-green-600">${healthData.status}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Updated:</span>
                        <span class="font-semibold">${new Date(healthData.lastUpdate).toLocaleTimeString()}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Health:</span>
                        <span class="font-semibold ${
                            healthData.healthStatus === 'critical' ? 'text-red-600' :
                            healthData.healthStatus === 'warning' ? 'text-yellow-600' :
                            'text-green-600'
                        }">${healthData.healthStatus?.toUpperCase()}</span>
                    </div>
                </div>
                
                <div>
                    <p class="font-semibold text-gray-700 mb-1">CPU</p>
                    <div class="bg-gray-50 rounded p-2 space-y-1">
                        ${healthData.cpu?.temperature ? `
                            <div class="flex justify-between items-center p-2 rounded ${
                                healthData.cpu.temperature > 80 ? 'bg-red-100 border border-red-500' :
                                healthData.cpu.temperature > 70 ? 'bg-orange-100 border border-orange-500' :
                                'bg-green-100'
                            }">
                                <span class="font-semibold">🌡️ Temp:</span>
                                <span class="text-lg font-bold ${
                                    healthData.cpu.temperature > 80 ? 'text-red-600' :
                                    healthData.cpu.temperature > 70 ? 'text-orange-600' :
                                    'text-green-600'
                                }">${healthData.cpu.temperature}°C</span>
                            </div>
                            <p class="text-[10px] text-gray-600">
                                ${healthData.cpu.temperature > 80 ? '⚠️ Critical temperature!' :
                                  healthData.cpu.temperature > 70 ? '⚠️ High temperature' :
                                  '✓ Normal temperature'}
                            </p>
                        ` : ''}
                        <div class="flex justify-between">
                            <span class="text-gray-600">Usage:</span>
                            <span class="font-semibold">${healthData.cpu?.usage}%</span>
                        </div>
                        <div class="w-full bg-gray-300 rounded-full h-2">
                            <div class="h-2 rounded-full bg-blue-500" style="width: ${healthData.cpu?.usage}%"></div>
                        </div>
                        <div class="flex justify-between text-[10px]">
                            <span class="text-gray-600">${healthData.cpu?.name}</span>
                        </div>
                    </div>
                </div>
                
                <div>
                    <p class="font-semibold text-gray-700 mb-1">Memory</p>
                    <div class="bg-gray-50 rounded p-2 space-y-1">
                        <div class="flex justify-between">
                            <span class="text-gray-600">Usage:</span>
                            <span class="font-semibold">${healthData.memory?.usage}%</span>
                        </div>
                        <div class="w-full bg-gray-300 rounded-full h-2">
                            <div class="h-2 rounded-full bg-green-500" style="width: ${healthData.memory?.usage}%"></div>
                        </div>
                        <div class="flex justify-between text-[10px]">
                            <span class="text-gray-600">${healthData.memory?.used}/${healthData.memory?.total} GB</span>
                        </div>
                    </div>
                </div>
                
                <div>
                    <p class="font-semibold text-gray-700 mb-1">Storage</p>
                    ${healthData.disks?.map(disk => `
                        <div class="bg-gray-50 rounded p-2 space-y-1 mb-1">
                            <div class="flex justify-between">
                                <span class="text-gray-600">Drive ${disk.drive}:</span>
                                <span class="font-semibold">${disk.usage}%</span>
                            </div>
                            <div class="w-full bg-gray-300 rounded-full h-2">
                                <div class="h-2 rounded-full bg-purple-500" style="width: ${disk.usage}%"></div>
                            </div>
                            <div class="flex justify-between text-[10px]">
                                <span class="text-gray-600">${disk.used}/${disk.total} GB</span>
                            </div>
                        </div>
                    `).join('') || '<p class="text-gray-500">No data</p>'}
                </div>
            </div>
        `;
    }
    
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

// Close modal
function closeModal() {
    const modal = document.getElementById('pcDetailModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}

// Initialize
document.addEventListener('DOMContentLoaded', () => {
    initFirebase();
    loadPCUnits();
    
    setInterval(() => {
        document.getElementById('lastUpdateTime').textContent = new Date().toLocaleTimeString();
    }, 1000);
});
</script>

<?php include '../components/layout_footer.php'; ?>
