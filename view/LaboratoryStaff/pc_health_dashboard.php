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
            <select id="buildingFilter" class="border border-gray-300 rounded px-3 py-1.5 text-xs focus:ring-1 focus:ring-blue-500">
                <option value="">All Buildings</option>
            </select>
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
        
        <!-- Pagination Controls -->
        <div id="paginationControls" class="hidden"></div>
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
let buildingsData = [];
let allRoomsData = [];
let pcAssetsData = {}; // Store assets for each PC

// Pagination variables
let currentPage = 1;
let itemsPerPage = 20;
let filteredPCs = [];

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
        const [pcResponse, roomResponse, buildingResponse, assetsResponse] = await Promise.all([
            fetch('../../controller/get_pc_health_data.php?action=getAll'),
            fetch('../../controller/get_pc_health_data.php?action=getRooms'),
            fetch('../../controller/get_pc_health_data.php?action=getBuildings'),
            fetch('../../controller/get_pc_health_data.php?action=getPCAssets')
        ]);
        
        const pcResult = await pcResponse.json();
        const roomResult = await roomResponse.json();
        const buildingResult = await buildingResponse.json();
        const assetsResult = await assetsResponse.json();
        
        if (pcResult.success) {
            pcUnitsData = pcResult.data;
        }
        
        if (roomResult.success) {
            allRoomsData = roomResult.data;
            roomsData = roomResult.data;
            populateRoomFilter();
        }
        
        if (buildingResult.success) {
            buildingsData = buildingResult.data;
            populateBuildingFilter();
        }
        
        if (assetsResult.success) {
            // Organize assets by PC ID
            pcAssetsData = {};
            assetsResult.data.forEach(asset => {
                if (!pcAssetsData[asset.pc_unit_id]) {
                    pcAssetsData[asset.pc_unit_id] = [];
                }
                pcAssetsData[asset.pc_unit_id].push(asset);
            });
        }
        
        updateDashboard();
    } catch (error) {
        console.error('Error loading PC units:', error);
        showError('Failed to load PC data');
    }
}

// Populate building filter
function populateBuildingFilter() {
    const filter = document.getElementById('buildingFilter');
    buildingsData.forEach(building => {
        const option = document.createElement('option');
        option.value = building.id;
        option.textContent = building.name;
        filter.appendChild(option);
    });
    
    // Add event listener for building filter
    document.getElementById('buildingFilter').addEventListener('change', onBuildingChange);
}

// Handle building filter change
function onBuildingChange() {
    const selectedBuilding = document.getElementById('buildingFilter').value;
    const roomFilter = document.getElementById('roomFilter');
    
    // Clear and reset room filter
    roomFilter.innerHTML = '<option value="">All Rooms</option>';
    
    // Filter rooms by selected building
    const filteredRooms = selectedBuilding 
        ? allRoomsData.filter(room => room.building_id == selectedBuilding)
        : allRoomsData;
    
    filteredRooms.forEach(room => {
        const option = document.createElement('option');
        option.value = room.id;
        option.textContent = room.name;
        roomFilter.appendChild(option);
    });
    
    updateDashboard();
}

// Populate room filter
function populateRoomFilter() {
    const filter = document.getElementById('roomFilter');
    allRoomsData.forEach(room => {
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
    document.getElementById('buildingFilter').value = '';
    document.getElementById('roomFilter').innerHTML = '<option value="">All Rooms</option>';
    allRoomsData.forEach(room => {
        const option = document.createElement('option');
        option.value = room.id;
        option.textContent = room.name;
        document.getElementById('roomFilter').appendChild(option);
    });
    document.getElementById('statusFilter').value = '';
    updateDashboard();
}

// Update dashboard
function updateDashboard() {
    const selectedRoom = document.getElementById('roomFilter').value;
    const selectedBuilding = document.getElementById('buildingFilter').value;
    const selectedStatus = document.getElementById('statusFilter').value;
    const searchText = document.getElementById('searchInput').value.toLowerCase();
    
    // Check if any filter is applied
    const hasFilters = selectedRoom || selectedBuilding || selectedStatus || searchText;
    
    // If no filters applied, show empty state
    if (!hasFilters) {
        filteredPCs = [];
        renderPCGrid();
        updateStatistics();
        return;
    }
    
    filteredPCs = pcUnitsData;
    
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
    
    // Reset to page 1 when filters change
    currentPage = 1;
    
    renderPCGrid();
    updateStatistics();
}

// Render PC grid with pagination
function renderPCGrid() {
    const grid = document.getElementById('pcGrid');
    grid.innerHTML = '';
    
    if (filteredPCs.length === 0) {
        grid.innerHTML = `
            <div class="text-center py-8 col-span-full">
                <svg class="w-12 h-12 text-gray-400 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                </svg>
                <p class="text-sm text-gray-600 font-medium">Select filters to view PCs</p>
                <p class="text-xs text-gray-500 mt-1">Use the filters above to display laboratory computers</p>
            </div>
        `;
        document.getElementById('paginationControls').classList.add('hidden');
        return;
    }
    
    // Calculate pagination
    const totalPages = Math.ceil(filteredPCs.length / itemsPerPage);
    const startIndex = (currentPage - 1) * itemsPerPage;
    const endIndex = Math.min(startIndex + itemsPerPage, filteredPCs.length);
    const paginatedPCs = filteredPCs.slice(startIndex, endIndex);
    
    // Render PC cards
    paginatedPCs.forEach(pc => {
        const healthData = healthDataCache[pc.id];
        const status = getHealthStatus(healthData);
        const card = createPCCard(pc, healthData, status);
        grid.appendChild(card);
    });
    
    // Update pagination controls
    updatePaginationControls(totalPages);
}

// Update statistics
function updateStatistics() {
    let totalPCs = filteredPCs.length;
    let onlineCount = 0;
    let warningCount = 0;
    let criticalCount = 0;
    
    filteredPCs.forEach(pc => {
        const healthData = healthDataCache[pc.id];
        const status = getHealthStatus(healthData);
        
        if (status === 'online' || status === 'healthy') onlineCount++;
        if (status === 'warning') warningCount++;
        if (status === 'critical') criticalCount++;
    });
    
    // Update statistics
    document.getElementById('totalPCs').textContent = totalPCs;
    document.getElementById('onlinePCs').textContent = onlineCount;
    document.getElementById('warningPCs').textContent = warningCount;
    document.getElementById('criticalPCs').textContent = criticalCount;
    document.getElementById('lastUpdateTime').textContent = new Date().toLocaleTimeString();
}

// Update pagination controls
function updatePaginationControls(totalPages) {
    const paginationDiv = document.getElementById('paginationControls');
    
    if (totalPages <= 1) {
        paginationDiv.classList.add('hidden');
        return;
    }
    
    paginationDiv.classList.remove('hidden');
    
    const startItem = (currentPage - 1) * itemsPerPage + 1;
    const endItem = Math.min(currentPage * itemsPerPage, filteredPCs.length);
    
    paginationDiv.innerHTML = `
        <div class="flex items-center justify-between px-4 py-3 bg-white border-t border-gray-200">
            <div class="flex-1 flex justify-between sm:hidden">
                <button onclick="changePage(${currentPage - 1})" ${currentPage === 1 ? 'disabled' : ''} class="relative inline-flex items-center px-4 py-2 text-sm font-medium rounded-md ${currentPage === 1 ? 'text-gray-400 bg-gray-100 cursor-not-allowed' : 'text-gray-700 bg-white hover:bg-gray-50'} border border-gray-300">
                    Previous
                </button>
                <button onclick="changePage(${currentPage + 1})" ${currentPage === totalPages ? 'disabled' : ''} class="relative inline-flex items-center px-4 py-2 ml-3 text-sm font-medium rounded-md ${currentPage === totalPages ? 'text-gray-400 bg-gray-100 cursor-not-allowed' : 'text-gray-700 bg-white hover:bg-gray-50'} border border-gray-300">
                    Next
                </button>
            </div>
            <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                <div>
                    <p class="text-sm text-gray-700">
                        Showing <span class="font-medium">${startItem}</span> to <span class="font-medium">${endItem}</span> of <span class="font-medium">${filteredPCs.length}</span> PCs
                    </p>
                </div>
                <div>
                    <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
                        <button onclick="changePage(${currentPage - 1})" ${currentPage === 1 ? 'disabled' : ''} class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 text-sm font-medium ${currentPage === 1 ? 'text-gray-400 bg-gray-100 cursor-not-allowed' : 'text-gray-500 bg-white hover:bg-gray-50'}">
                            <i class="fa-solid fa-chevron-left"></i>
                        </button>
                        ${generatePageNumbers(currentPage, totalPages)}
                        <button onclick="changePage(${currentPage + 1})" ${currentPage === totalPages ? 'disabled' : ''} class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 text-sm font-medium ${currentPage === totalPages ? 'text-gray-400 bg-gray-100 cursor-not-allowed' : 'text-gray-500 bg-white hover:bg-gray-50'}">
                            <i class="fa-solid fa-chevron-right"></i>
                        </button>
                    </nav>
                </div>
            </div>
        </div>
    `;
}

// Generate page number buttons
function generatePageNumbers(current, total) {
    let html = '';
    const startPage = Math.max(1, current - 2);
    const endPage = Math.min(total, current + 2);
    
    if (startPage > 1) {
        html += `<button onclick="changePage(1)" class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">1</button>`;
        if (startPage > 2) {
            html += `<span class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700">...</span>`;
        }
    }
    
    for (let i = startPage; i <= endPage; i++) {
        if (i === current) {
            html += `<button class="relative inline-flex items-center px-4 py-2 border border-[#1E3A8A] bg-[#1E3A8A] text-sm font-medium text-white">${i}</button>`;
        } else {
            html += `<button onclick="changePage(${i})" class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">${i}</button>`;
        }
    }
    
    if (endPage < total) {
        if (endPage < total - 1) {
            html += `<span class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700">...</span>`;
        }
        html += `<button onclick="changePage(${total})" class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">${total}</button>`;
    }
    
    return html;
}

// Change page
function changePage(page) {
    const totalPages = Math.ceil(filteredPCs.length / itemsPerPage);
    if (page < 1 || page > totalPages) return;
    
    currentPage = page;
    renderPCGrid();
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

// Calculate health score percentage based on asset conditions
function calculateHealthScore(healthData, pcId) {
    // Get assets for this PC
    const assets = pcAssetsData[pcId] || [];
    
    if (assets.length === 0) {
        return 0;
    }
    
    // Define condition scores
    const conditionScores = {
        'Excellent': 100,
        'Good': 80,
        'Fair': 60,
        'Poor': 40,
        'Non-Functional': 0
    };
    
    // Calculate average score
    let totalScore = 0;
    let validAssets = 0;
    
    assets.forEach(asset => {
        const condition = asset.condition || 'Fair';
        const score = conditionScores[condition] !== undefined ? conditionScores[condition] : 60;
        totalScore += score;
        validAssets++;
    });
    
    if (validAssets === 0) {
        return 0;
    }
    
    const healthScore = totalScore / validAssets;
    return Math.round(healthScore);
}

// Create PC card
function createPCCard(pc, healthData, status) {
    const healthScore = calculateHealthScore(healthData, pc.id);
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
    
    const healthScoreColor = 
        healthScore >= 70 ? 'text-green-600' :
        healthScore >= 40 ? 'text-yellow-600' :
        healthScore > 0 ? 'text-red-600' : 'text-gray-400';
    
    card.innerHTML = `
        <div class="flex items-center justify-between mb-3">
            <div class="flex items-center gap-2">
                <span class="w-3 h-3 ${statusColor} rounded-full"></span>
                <span class="font-bold text-gray-800">${pc.terminal_number || 'TH-' + pc.id}</span>
            </div>
            <span class="text-xs text-gray-500">${pc.room_name || 'Unknown'}</span>
        </div>
        
        <!-- Health Score Display -->
        <div class="mb-3 text-center bg-gray-50 rounded-lg py-3">
            <p class="text-xs text-gray-600 mb-1">Health Score</p>
            <p class="text-3xl font-bold ${healthScoreColor}">${healthScore}%</p>
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
            <div class="text-center py-2">
                <p class="text-gray-400 text-xs">No data available</p>
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
    const healthScore = calculateHealthScore(healthData, pc.id);
    const assets = pcAssetsData[pc.id] || [];
    
    title.textContent = `${pc.terminal_number || 'TH-' + pc.id} - ${pc.room_name}`;
    
    if (assets.length === 0) {
        content.innerHTML = `
            <div class="text-center py-6">
                <svg class="w-12 h-12 text-gray-400 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                </svg>
                <h4 class="text-base font-semibold text-gray-600 mb-1">No Assets Found</h4>
                <p class="text-xs text-gray-500">No components registered for this PC</p>
                <div class="mt-4 bg-gray-100 rounded-lg py-3">
                    <p class="text-xs text-gray-600 mb-1">Health Score</p>
                    <p class="text-3xl font-bold text-gray-400">0%</p>
                </div>
            </div>
        `;
    } else {
        const healthScoreColor = 
            healthScore >= 70 ? 'text-green-600' :
            healthScore >= 40 ? 'text-yellow-600' :
            'text-red-600';
        
        // Generate asset list with condition colors
        const assetListHTML = assets.map(asset => {
            const conditionColors = {
                'Excellent': 'bg-green-100 text-green-800',
                'Good': 'bg-blue-100 text-blue-800',
                'Fair': 'bg-yellow-100 text-yellow-800',
                'Poor': 'bg-orange-100 text-orange-800',
                'Non-Functional': 'bg-red-100 text-red-800'
            };
            const colorClass = conditionColors[asset.condition] || 'bg-gray-100 text-gray-800';
            
            return `
                <div class="bg-gray-50 rounded p-2 mb-1">
                    <div class="flex justify-between items-center">
                        <div class="flex-1">
                            <p class="text-xs font-semibold text-gray-800">${asset.name || 'Unknown Component'}</p>
                            <p class="text-[10px] text-gray-500">${asset.category || 'N/A'} ${asset.brand ? '- ' + asset.brand : ''}</p>
                        </div>
                        <span class="text-[9px] px-2 py-1 rounded font-medium ${colorClass}">
                            ${asset.condition || 'Fair'}
                        </span>
                    </div>
                </div>
            `;
        }).join('');
        
        content.innerHTML = `
            <div class="space-y-3 text-xs">
                <!-- Health Score -->
                <div class="bg-gradient-to-r from-blue-50 to-purple-50 rounded-lg p-4 text-center">
                    <p class="text-xs text-gray-600 mb-2">Overall Health Score</p>
                    <p class="text-5xl font-bold ${healthScoreColor}">${healthScore}%</p>
                    <p class="text-xs text-gray-500 mt-2">
                        ${healthScore >= 70 ? 'Excellent' : healthScore >= 40 ? 'Fair' : 'Poor'} Condition
                    </p>
                    <p class="text-[10px] text-gray-400 mt-1">Based on ${assets.length} component${assets.length !== 1 ? 's' : ''}</p>
                </div>
                
                <!-- Assets/Components List -->
                <div>
                    <p class="font-semibold text-gray-700 mb-2">PC Components</p>
                    <div class="max-h-64 overflow-y-auto">
                        ${assetListHTML}
                    </div>
                </div>
                
                ${healthData && healthData.status !== 'offline' ? `
                    <div class="border-t pt-3">
                        <p class="font-semibold text-gray-700 mb-2">System Performance</p>
                        <div class="bg-gray-50 rounded p-2 space-y-2">
                            <div>
                                <div class="flex justify-between items-center mb-1">
                                    <span class="text-gray-600">CPU Usage:</span>
                                    <span class="font-semibold">${healthData.cpu?.usage || 0}%</span>
                                </div>
                                <div class="w-full bg-gray-300 rounded-full h-2">
                                    <div class="h-2 rounded-full bg-blue-500" style="width: ${healthData.cpu?.usage || 0}%"></div>
                                </div>
                            </div>
                            <div>
                                <div class="flex justify-between items-center mb-1">
                                    <span class="text-gray-600">RAM Usage:</span>
                                    <span class="font-semibold">${healthData.memory?.usage || 0}%</span>
                                </div>
                                <div class="w-full bg-gray-300 rounded-full h-2">
                                    <div class="h-2 rounded-full bg-green-500" style="width: ${healthData.memory?.usage || 0}%"></div>
                                </div>
                            </div>
                            <div>
                                <div class="flex justify-between items-center mb-1">
                                    <span class="text-gray-600">Disk Usage:</span>
                                    <span class="font-semibold">${healthData.disks?.[0]?.usage || 0}%</span>
                                </div>
                                <div class="w-full bg-gray-300 rounded-full h-2">
                                    <div class="h-2 rounded-full bg-purple-500" style="width: ${healthData.disks?.[0]?.usage || 0}%"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                ` : ''}
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
    
    // Set initial empty state
    document.getElementById('pcGrid').innerHTML = `
        <div class="text-center py-8 col-span-full">
            <svg class="w-12 h-12 text-gray-400 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
            </svg>
            <p class="text-sm text-gray-600 font-medium">Select filters to view PCs</p>
            <p class="text-xs text-gray-500 mt-1">Use the filters above to display laboratory computers</p>
        </div>
    `;
    
    // Refresh data every minute as backup
    setInterval(() => {
        document.getElementById('lastUpdateTime').textContent = new Date().toLocaleTimeString();
    }, 1000);
});
</script>

<?php include '../components/layout_footer.php'; ?>
