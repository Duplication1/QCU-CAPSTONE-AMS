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

<!-- PC Health Dashboard -->
<main class="p-4 sm:p-6 space-y-6">
    <!-- Header -->
    <div class="bg-gradient-to-r from-blue-600 to-purple-700 rounded-xl shadow-lg p-6 text-white">
        <div class="flex items-center justify-between flex-wrap gap-4">
            <div>
                <h2 class="text-2xl sm:text-3xl font-bold mb-2">🖥️ PC Health Monitor</h2>
                <p class="text-blue-100">Real-time monitoring of all laboratory computers</p>
            </div>
            <div class="flex items-center gap-3">
                <div class="bg-white/20 backdrop-blur-sm rounded-lg px-4 py-2">
                    <div class="text-xs text-blue-100">Last Update</div>
                    <div class="text-sm font-semibold" id="lastUpdateTime">--:--:--</div>
                </div>
                <select id="roomFilter" class="bg-white/20 backdrop-blur-sm border-0 rounded-lg px-4 py-2 text-white font-semibold focus:ring-2 focus:ring-white/50">
                    <option value="">All Rooms</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-xl shadow-lg p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 text-sm font-semibold">Total PCs</p>
                    <h3 class="text-3xl font-bold text-gray-800 mt-1" id="totalPCs">0</h3>
                </div>
                <div class="bg-blue-100 p-3 rounded-lg">
                    <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-lg p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 text-sm font-semibold">Online</p>
                    <h3 class="text-3xl font-bold text-green-600 mt-1" id="onlinePCs">0</h3>
                </div>
                <div class="bg-green-100 p-3 rounded-lg">
                    <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-lg p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 text-sm font-semibold">Warning</p>
                    <h3 class="text-3xl font-bold text-yellow-600 mt-1" id="warningPCs">0</h3>
                </div>
                <div class="bg-yellow-100 p-3 rounded-lg">
                    <svg class="w-8 h-8 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-lg p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 text-sm font-semibold">Critical</p>
                    <h3 class="text-3xl font-bold text-red-600 mt-1" id="criticalPCs">0</h3>
                </div>
                <div class="bg-red-100 p-3 rounded-lg">
                    <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- PC Grid -->
    <div class="bg-white rounded-xl shadow-lg p-6">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-xl font-bold text-gray-800">Laboratory Computers</h3>
            <div class="flex items-center gap-2">
                <span class="w-3 h-3 bg-green-500 rounded-full"></span>
                <span class="text-sm text-gray-600">Healthy</span>
                <span class="w-3 h-3 bg-yellow-500 rounded-full ml-3"></span>
                <span class="text-sm text-gray-600">Warning</span>
                <span class="w-3 h-3 bg-red-500 rounded-full ml-3"></span>
                <span class="text-sm text-gray-600">Critical</span>
                <span class="w-3 h-3 bg-gray-400 rounded-full ml-3"></span>
                <span class="text-sm text-gray-600">Offline</span>
            </div>
        </div>

        <div id="pcGrid" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
            <!-- PC cards will be inserted here -->
            <div class="text-center py-12 col-span-full">
                <div class="inline-block animate-spin rounded-full h-12 w-12 border-4 border-gray-300 border-t-blue-600"></div>
                <p class="mt-4 text-gray-600">Loading PC health data...</p>
            </div>
        </div>
    </div>
</main>

<!-- PC Detail Modal -->
<div id="pcDetailModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50 p-4">
    <div class="bg-white rounded-xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
        <div class="p-6 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <h3 class="text-2xl font-bold text-gray-800" id="modalTitle">PC Details</h3>
                <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        </div>
        <div id="modalContent" class="p-6">
            <!-- Content will be inserted here -->
        </div>
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
    
    filter.addEventListener('change', updateDashboard);
}

// Update dashboard
function updateDashboard() {
    const selectedRoom = document.getElementById('roomFilter').value;
    let filteredPCs = pcUnitsData;
    
    if (selectedRoom) {
        filteredPCs = pcUnitsData.filter(pc => pc.room_id == selectedRoom);
    }
    
    // Update statistics
    let totalPCs = filteredPCs.length;
    let onlineCount = 0;
    let warningCount = 0;
    let criticalCount = 0;
    
    // Update PC grid
    const grid = document.getElementById('pcGrid');
    grid.innerHTML = '';
    
    filteredPCs.forEach(pc => {
        const healthData = healthDataCache[pc.id];
        const status = getHealthStatus(healthData);
        
        if (status === 'online') onlineCount++;
        if (status === 'warning') warningCount++;
        if (status === 'critical') criticalCount++;
        
        const card = createPCCard(pc, healthData, status);
        grid.appendChild(card);
    });
    
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
            <div class="text-center py-8">
                <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                </svg>
                <h4 class="text-xl font-semibold text-gray-600 mb-2">PC is Offline</h4>
                <p class="text-gray-500">No health data available</p>
                ${healthData?.lastUpdate ? `<p class="text-sm text-gray-400 mt-2">Last seen: ${new Date(healthData.lastUpdate).toLocaleString()}</p>` : ''}
            </div>
        `;
    } else {
        content.innerHTML = `
            <div class="space-y-6">
                <!-- System Info -->
                <div>
                    <h4 class="font-semibold text-gray-700 mb-3">System Information</h4>
                    <div class="bg-gray-50 rounded-lg p-4 space-y-2">
                        <div class="flex justify-between">
                            <span class="text-gray-600">Status:</span>
                            <span class="font-semibold text-green-600">${healthData.status}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Last Update:</span>
                            <span class="font-semibold">${new Date(healthData.lastUpdate).toLocaleString()}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Health Status:</span>
                            <span class="font-semibold ${
                                healthData.healthStatus === 'critical' ? 'text-red-600' :
                                healthData.healthStatus === 'warning' ? 'text-yellow-600' :
                                'text-green-600'
                            }">${healthData.healthStatus?.toUpperCase()}</span>
                        </div>
                    </div>
                </div>
                
                <!-- CPU -->
                <div>
                    <h4 class="font-semibold text-gray-700 mb-3">CPU</h4>
                    <div class="bg-gray-50 rounded-lg p-4 space-y-2">
                        <div class="flex justify-between">
                            <span class="text-gray-600">Usage:</span>
                            <span class="font-semibold">${healthData.cpu?.usage}%</span>
                        </div>
                        <div class="w-full bg-gray-300 rounded-full h-3">
                            <div class="h-3 rounded-full bg-blue-500" style="width: ${healthData.cpu?.usage}%"></div>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Processor:</span>
                            <span class="font-semibold text-sm">${healthData.cpu?.name}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Cores:</span>
                            <span class="font-semibold">${healthData.cpu?.cores} cores / ${healthData.cpu?.threads} threads</span>
                        </div>
                    </div>
                </div>
                
                <!-- Memory -->
                <div>
                    <h4 class="font-semibold text-gray-700 mb-3">Memory</h4>
                    <div class="bg-gray-50 rounded-lg p-4 space-y-2">
                        <div class="flex justify-between">
                            <span class="text-gray-600">Usage:</span>
                            <span class="font-semibold">${healthData.memory?.usage}%</span>
                        </div>
                        <div class="w-full bg-gray-300 rounded-full h-3">
                            <div class="h-3 rounded-full bg-green-500" style="width: ${healthData.memory?.usage}%"></div>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Total:</span>
                            <span class="font-semibold">${healthData.memory?.total} GB</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Used:</span>
                            <span class="font-semibold">${healthData.memory?.used} GB</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Free:</span>
                            <span class="font-semibold">${healthData.memory?.free} GB</span>
                        </div>
                    </div>
                </div>
                
                <!-- Disks -->
                <div>
                    <h4 class="font-semibold text-gray-700 mb-3">Storage</h4>
                    ${healthData.disks?.map(disk => `
                        <div class="bg-gray-50 rounded-lg p-4 space-y-2 mb-2">
                            <div class="flex justify-between">
                                <span class="text-gray-600">Drive ${disk.drive}:</span>
                                <span class="font-semibold">${disk.usage}%</span>
                            </div>
                            <div class="w-full bg-gray-300 rounded-full h-3">
                                <div class="h-3 rounded-full bg-purple-500" style="width: ${disk.usage}%"></div>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">${disk.used} GB used</span>
                                <span class="text-gray-600">${disk.free} GB free</span>
                            </div>
                        </div>
                    `).join('') || '<p class="text-gray-500">No disk data available</p>'}
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
