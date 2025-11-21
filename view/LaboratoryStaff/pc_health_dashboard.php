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
include '../components/layout_header.php';
?>

<main class="p-3 space-y-3">
    <!-- Filters -->
    <div class="bg-white rounded-lg shadow p-3">
        <div class="flex flex-wrap items-center gap-2">
            <div class="flex-1 min-w-[200px]">
                <input type="text" id="searchInput" placeholder="Search PC or Room..." 
                       class="w-full border border-gray-300 rounded px-3 py-1.5 text-xs focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
            </div>
            <select id="roomFilter" class="border border-gray-300 rounded px-3 py-1.5 text-xs focus:ring-1 focus:ring-blue-500">
                <option value="">All Rooms</option>
            </select>
            <select id="statusFilter" class="border border-gray-300 rounded px-3 py-1.5 text-xs focus:ring-1 focus:ring-blue-500">
                <option value="">All Status</option>
                <option value="healthy">Healthy</option>
                <option value="warning">Warning</option>
                <option value="critical">Critical</option>
                <option value="offline">Offline</option>
            </select>
            <button onclick="clearFilters()" class="px-3 py-1.5 bg-gray-100 hover:bg-gray-200 rounded text-xs font-medium text-gray-700">
                Clear
            </button>
            <div class="text-[10px] text-gray-500 ml-auto" id="lastUpdateTime">--:--</div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
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
                    <p class="text-gray-600 text-[10px]">Warning</p>
                    <p class="text-xl font-bold text-yellow-600" id="warningPCs">0</p>
                </div>
                <div class="bg-yellow-100 p-2 rounded">
                    <svg class="w-5 h-5 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                    <span class="w-2 h-2 bg-yellow-500 rounded-full"></span>
                    <span>Warning</span>
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
                <div class="inline-block animate-spin rounded-full h-6 w-6 border-2 border-gray-300 border-t-blue-600"></div>
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

// Initialize Firebase
async function initFirebase() {
    try {
        const response = await fetch('../../controller/get_pc_health_data.php?action=getFirebaseConfig');
        const result = await response.json();
        
        if (result.success) {
            const config = result.config;
            firebaseApp = firebase.initializeApp(config);
            database = firebase.database();
            
            // Listen for real-time updates
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
        showError('Failed to connect to real-time monitoring service');
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
        showError('Failed to load PC data');
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
    document.getElementById('searchInput').addEventListener('input', updateDashboard);
}

// Clear all filters
function clearFilters() {
    document.getElementById('searchInput').value = '';
    document.getElementById('roomFilter').value = '';
    document.getElementById('statusFilter').value = '';
    updateDashboard();
}

// Update dashboard
function updateDashboard() {
    const selectedRoom = document.getElementById('roomFilter').value;
    const selectedStatus = document.getElementById('statusFilter').value;
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
        
        return true;
    });
    
    // Update statistics
    let totalPCs = filteredPCs.length;
    let onlineCount = 0;
    let warningCount = 0;
    let criticalCount = 0;
    
    // Update PC grid
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
            
            if (status === 'online' || status === 'healthy') onlineCount++;
            if (status === 'warning') warningCount++;
            if (status === 'critical') criticalCount++;
            
            const card = createPCCard(pc, healthData, status);
            grid.appendChild(card);
        });
    }
    
    // Update statistics
    document.getElementById('totalPCs').textContent = totalPCs;
    document.getElementById('onlinePCs').textContent = onlineCount;
    document.getElementById('warningPCs').textContent = warningCount;
    document.getElementById('criticalPCs').textContent = criticalCount;
    document.getElementById('lastUpdateTime').textContent = new Date().toLocaleTimeString();
}

// Get health status
function getHealthStatus(healthData) {
    if (!healthData || !healthData.status || healthData.status === 'offline') {
        // Check if last update was more than 30 seconds ago
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
    card.className = `bg-white border-2 rounded-lg p-4 cursor-pointer transition-all hover:shadow-lg ${
        status === 'critical' ? 'border-red-500' : 
        status === 'warning' ? 'border-yellow-500' : 
        status === 'online' || status === 'healthy' ? 'border-green-500' : 
        'border-gray-300'
    }`;
    
    card.onclick = () => showPCDetail(pc, healthData);
    
    const statusColor = 
        status === 'critical' ? 'bg-red-500' : 
        status === 'warning' ? 'bg-yellow-500' : 
        status === 'online' || status === 'healthy' ? 'bg-green-500' : 
        'bg-gray-400';
    
    card.innerHTML = `
        <div class="flex items-center justify-between mb-3">
            <div class="flex items-center gap-2">
                <span class="w-3 h-3 ${statusColor} rounded-full"></span>
                <span class="font-bold text-gray-800">${pc.terminal_number || 'TH-' + pc.id}</span>
            </div>
            <span class="text-xs text-gray-500">${pc.room_name || 'Unknown'}</span>
        </div>
        
        ${healthData && status !== 'offline' ? `
            <div class="space-y-2">
                <div class="flex justify-between items-center">
                    <span class="text-xs text-gray-600">CPU</span>
                    <span class="text-sm font-semibold ${healthData.cpu?.usage > 80 ? 'text-red-600' : 'text-gray-800'}">${healthData.cpu?.usage || 0}%</span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-2">
                    <div class="h-2 rounded-full ${healthData.cpu?.usage > 80 ? 'bg-red-500' : 'bg-blue-500'}" style="width: ${healthData.cpu?.usage || 0}%"></div>
                </div>
                
                <div class="flex justify-between items-center">
                    <span class="text-xs text-gray-600">RAM</span>
                    <span class="text-sm font-semibold ${healthData.memory?.usage > 80 ? 'text-red-600' : 'text-gray-800'}">${healthData.memory?.usage || 0}%</span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-2">
                    <div class="h-2 rounded-full ${healthData.memory?.usage > 80 ? 'bg-red-500' : 'bg-green-500'}" style="width: ${healthData.memory?.usage || 0}%"></div>
                </div>
                
                <div class="flex justify-between items-center">
                    <span class="text-xs text-gray-600">Disk</span>
                    <span class="text-sm font-semibold ${healthData.disks?.[0]?.usage > 80 ? 'text-red-600' : 'text-gray-800'}">${healthData.disks?.[0]?.usage || 0}%</span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-2">
                    <div class="h-2 rounded-full ${healthData.disks?.[0]?.usage > 80 ? 'bg-red-500' : 'bg-purple-500'}" style="width: ${healthData.disks?.[0]?.usage || 0}%"></div>
                </div>
            </div>
        ` : `
            <div class="text-center py-4">
                <p class="text-gray-400 text-sm">Offline</p>
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

// Show error message
function showError(message) {
    // You can implement a toast notification here
    console.error(message);
}

// Initialize
document.addEventListener('DOMContentLoaded', () => {
    initFirebase();
    loadPCUnits();
    
    // Refresh data every minute as backup
    setInterval(() => {
        document.getElementById('lastUpdateTime').textContent = new Date().toLocaleTimeString();
    }, 1000);
});
</script>

<?php include '../components/layout_footer.php'; ?>
