<!-- Add/Edit Asset Modal -->
<div id="addEditAssetModal" class="modal">
    <div class="modal-content max-w-4xl">
        <div class="bg-blue-600 text-white p-6 rounded-t-xl">
            <div class="flex justify-between items-center">
                <h3 class="text-2xl font-bold" id="assetModalTitle">Add New Asset</h3>
                <button onclick="closeAddEditAssetModal()" class="text-white hover:text-gray-200 text-2xl">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
        <div class="p-6">
            <form id="assetForm" method="POST" action="../../controller/save_asset.php" enctype="multipart/form-data">
                <input type="hidden" name="asset_id" id="asset_id">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Asset Tag -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Asset Tag <span class="text-red-600">*</span></label>
                        <input type="text" name="asset_tag" id="asset_tag" required
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                               placeholder="e.g., COMP-IK501-001">
                    </div>

                    <!-- Asset Name -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Asset Name <span class="text-red-600">*</span></label>
                        <input type="text" name="asset_name" id="asset_name" required
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                               placeholder="e.g., Desktop Computer">
                    </div>

                    <!-- Asset Type -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Asset Type <span class="text-red-600">*</span></label>
                        <select name="asset_type" id="asset_type" required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <option value="">Select Type</option>
                            <option value="Hardware">Hardware</option>
                            <option value="Software">Software</option>
                            <option value="Furniture">Furniture</option>
                            <option value="Equipment">Equipment</option>
                            <option value="Peripheral">Peripheral</option>
                            <option value="Network Device">Network Device</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>

                    <!-- Category -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Category</label>
                        <input type="text" name="category" id="category"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                               placeholder="e.g., Desktop, Laptop, Monitor">
                    </div>

                    <!-- Brand -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Brand</label>
                        <input type="text" name="brand" id="brand"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                               placeholder="e.g., Dell, HP, Lenovo">
                    </div>

                    <!-- Model -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Model</label>
                        <input type="text" name="model" id="model"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                               placeholder="e.g., OptiPlex 7090">
                    </div>

                    <!-- Serial Number -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Serial Number</label>
                        <input type="text" name="serial_number" id="serial_number"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                               placeholder="e.g., SN123456789">
                    </div>

                    <!-- Room -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Room/Laboratory</label>
                        <select name="room_id" id="room_id"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <option value="">Select Room</option>
                            <?php
                            require_once '../../model/Room.php';
                            $roomModel = new Room();
                            $rooms = $roomModel->getAll();
                            foreach ($rooms as $room):
                            ?>
                                <option value="<?php echo $room['id']; ?>"><?php echo htmlspecialchars($room['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Terminal Number -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Terminal Number</label>
                        <input type="text" name="terminal_number" id="terminal_number"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                               placeholder="e.g., 1, 2, 3">
                    </div>

                    <!-- Location -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Location</label>
                        <input type="text" name="location" id="location"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                               placeholder="Specific location within room">
                    </div>

                    <!-- Status -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Status <span class="text-red-600">*</span></label>
                        <select name="status" id="status" required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <option value="Available">Available</option>
                            <option value="In Use">In Use</option>
                            <option value="Active">Active</option>
                            <option value="Under Maintenance">Under Maintenance</option>
                            <option value="Retired">Retired</option>
                            <option value="Disposed">Disposed</option>
                            <option value="Lost">Lost</option>
                            <option value="Damaged">Damaged</option>
                        </select>
                    </div>

                    <!-- Condition -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Condition <span class="text-red-600">*</span></label>
                        <select name="condition" id="condition" required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <option value="Excellent">Excellent</option>
                            <option value="Good">Good</option>
                            <option value="Fair">Fair</option>
                            <option value="Poor">Poor</option>
                            <option value="Non-Functional">Non-Functional</option>
                        </select>
                    </div>

                    <!-- Purchase Date -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Purchase Date</label>
                        <input type="date" name="purchase_date" id="purchase_date"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>

                    <!-- Purchase Cost -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Purchase Cost (₱)</label>
                        <input type="number" step="0.01" name="purchase_cost" id="purchase_cost"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                               placeholder="0.00">
                    </div>

                    <!-- Supplier -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Supplier</label>
                        <input type="text" name="supplier" id="supplier"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                               placeholder="Supplier name">
                    </div>

                    <!-- Warranty Expiry -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Warranty Expiry</label>
                        <input type="date" name="warranty_expiry" id="warranty_expiry"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>

                    <!-- Is Borrowable -->
                    <div class="flex items-center">
                        <input type="checkbox" name="is_borrowable" id="is_borrowable" value="1"
                               class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                        <label for="is_borrowable" class="ml-2 text-sm font-semibold text-gray-700">Is Borrowable</label>
                    </div>

                    <!-- Specifications -->
                    <div class="md:col-span-2">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Specifications</label>
                        <textarea name="specifications" id="specifications" rows="3"
                                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                  placeholder="Technical specifications or description"></textarea>
                    </div>

                    <!-- Notes -->
                    <div class="md:col-span-2">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Notes</label>
                        <textarea name="notes" id="notes" rows="3"
                                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                  placeholder="Additional notes or comments"></textarea>
                    </div>

                    <!-- Asset Image -->
                    <div class="md:col-span-2">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Asset Image</label>
                        <input type="file" name="asset_image" id="asset_image" accept="image/*"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <p class="text-xs text-gray-500 mt-1">Maximum file size: 2MB. Supported formats: JPG, PNG, GIF</p>
                    </div>
                </div>

                <div class="mt-6 flex justify-end space-x-3">
                    <button type="button" onclick="closeAddEditAssetModal()" 
                            class="px-6 py-2 bg-gray-300 hover:bg-gray-400 text-gray-800 rounded-lg transition">
                        Cancel
                    </button>
                    <button type="submit" 
                            class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition">
                        <i class="fas fa-save mr-2"></i>Save Asset
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function openAddAssetModal() {
        document.getElementById('assetModalTitle').textContent = 'Add New Asset';
        document.getElementById('assetForm').reset();
        document.getElementById('asset_id').value = '';
        document.getElementById('addEditAssetModal').classList.add('show');
    }

    function closeAddEditAssetModal() {
        document.getElementById('addEditAssetModal').classList.remove('show');
    }

    function editAsset(assetId) {
        document.getElementById('assetModalTitle').textContent = 'Edit Asset';
        document.getElementById('addEditAssetModal').classList.add('show');
        
        // Fetch asset data
        fetch(`../../controller/get_asset_details.php?id=${assetId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const asset = data.asset;
                    document.getElementById('asset_id').value = asset.id;
                    document.getElementById('asset_tag').value = asset.asset_tag;
                    document.getElementById('asset_name').value = asset.asset_name;
                    document.getElementById('asset_type').value = asset.asset_type;
                    document.getElementById('category').value = asset.category || '';
                    document.getElementById('brand').value = asset.brand || '';
                    document.getElementById('model').value = asset.model || '';
                    document.getElementById('serial_number').value = asset.serial_number || '';
                    document.getElementById('room_id').value = asset.room_id || '';
                    document.getElementById('terminal_number').value = asset.terminal_number || '';
                    document.getElementById('location').value = asset.location || '';
                    document.getElementById('status').value = asset.status;
                    document.getElementById('condition').value = asset.condition;
                    document.getElementById('purchase_date').value = asset.purchase_date || '';
                    document.getElementById('purchase_cost').value = asset.purchase_cost || '';
                    document.getElementById('supplier').value = asset.supplier || '';
                    document.getElementById('warranty_expiry').value = asset.warranty_expiry || '';
                    document.getElementById('is_borrowable').checked = asset.is_borrowable == 1;
                    document.getElementById('specifications').value = asset.specifications || '';
                    document.getElementById('notes').value = asset.notes || '';
                }
            })
            .catch(error => {
                alert('Error loading asset data');
            });
    }

    // Form submission
    document.getElementById('assetForm')?.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        
        fetch('../../controller/save_asset.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            alert('Error saving asset');
        });
    });
</script>
