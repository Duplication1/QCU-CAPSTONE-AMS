<?php
/**
 * Asset Model
 * 
 * Handles all database operations related to assets
 */

require_once __DIR__ . '/Database.php';

class Asset {
    private $conn;
    private $table = 'assets';
    
    // Asset properties
    public $id;
    public $asset_tag;
    public $asset_name;
    public $asset_type;
    public $category;
    public $brand;
    public $model;
    public $serial_number;
    public $specifications;
    public $room_id;
    public $location;
    public $terminal_number;
    public $purchase_date;
    public $purchase_cost;
    public $supplier;
    public $warranty_expiry;
    public $status;
    public $condition;
    public $is_borrowable;
    public $assigned_to;
    public $assigned_date;
    public $assigned_by;
    public $notes;
    public $qr_code;
    public $image;
    public $created_by;
    public $updated_by;
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }
    
    /**
     * Get all assets with optional filters
     */
    public function getAll($filters = []) {
        $query = "SELECT a.*, 
                         r.name as room_name,
                         u.full_name as assigned_to_name,
                         c.full_name as created_by_name
                  FROM " . $this->table . " a
                  LEFT JOIN rooms r ON a.room_id = r.id
                  LEFT JOIN users u ON a.assigned_to = u.id
                  LEFT JOIN users c ON a.created_by = c.id
                  WHERE 1=1";
        
        $params = [];
        
        // Apply filters
        if (!empty($filters['asset_type'])) {
            $query .= " AND a.asset_type = ?";
            $params[] = $filters['asset_type'];
        }
        
        if (!empty($filters['status'])) {
            $query .= " AND a.status = ?";
            $params[] = $filters['status'];
        }
        
        if (!empty($filters['is_borrowable'])) {
            $query .= " AND a.is_borrowable = ?";
            $params[] = $filters['is_borrowable'];
        }
        
        if (!empty($filters['room_id'])) {
            $query .= " AND a.room_id = ?";
            $params[] = $filters['room_id'];
        }
        
        if (!empty($filters['search'])) {
            $query .= " AND (a.asset_tag LIKE ? OR a.asset_name LIKE ? OR a.serial_number LIKE ?)";
            $searchTerm = '%' . $filters['search'] . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }
        
        $query .= " ORDER BY a.created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get asset by ID
     */
    public function getById($id) {
        $query = "SELECT a.*, 
                         r.name as room_name,
                         u.full_name as assigned_to_name,
                         c.full_name as created_by_name,
                         up.full_name as updated_by_name
                  FROM " . $this->table . " a
                  LEFT JOIN rooms r ON a.room_id = r.id
                  LEFT JOIN users u ON a.assigned_to = u.id
                  LEFT JOIN users c ON a.created_by = c.id
                  LEFT JOIN users up ON a.updated_by = up.id
                  WHERE a.id = ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$id]);
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get asset by asset tag
     */
    public function getByAssetTag($asset_tag) {
        $query = "SELECT * FROM " . $this->table . " WHERE asset_tag = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$asset_tag]);
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get all borrowable assets
     */
    public function getBorrowableAssets($status = 'Available') {
        $query = "SELECT a.*, r.name as room_name
                  FROM " . $this->table . " a
                  LEFT JOIN rooms r ON a.room_id = r.id
                  WHERE a.is_borrowable = 1 AND a.status = ?
                  ORDER BY a.asset_name";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$status]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Create new asset
     */
    public function create() {
        $query = "INSERT INTO " . $this->table . " 
                  SET asset_tag = ?,
                      asset_name = ?,
                      asset_type = ?,
                      category = ?,
                      brand = ?,
                      model = ?,
                      serial_number = ?,
                      specifications = ?,
                      room_id = ?,
                      location = ?,
                      terminal_number = ?,
                      purchase_date = ?,
                      purchase_cost = ?,
                      supplier = ?,
                      warranty_expiry = ?,
                      status = ?,
                      condition = ?,
                      is_borrowable = ?,
                      notes = ?,
                      created_by = ?";
        
        $stmt = $this->conn->prepare($query);
        
        return $stmt->execute([
            $this->asset_tag,
            $this->asset_name,
            $this->asset_type,
            $this->category,
            $this->brand,
            $this->model,
            $this->serial_number,
            $this->specifications,
            $this->room_id,
            $this->location,
            $this->terminal_number,
            $this->purchase_date,
            $this->purchase_cost,
            $this->supplier,
            $this->warranty_expiry,
            $this->status,
            $this->condition,
            $this->is_borrowable,
            $this->notes,
            $this->created_by
        ]);
    }
    
    /**
     * Update asset
     */
    public function update() {
        $query = "UPDATE " . $this->table . "
                  SET asset_name = ?,
                      asset_type = ?,
                      category = ?,
                      brand = ?,
                      model = ?,
                      serial_number = ?,
                      specifications = ?,
                      room_id = ?,
                      location = ?,
                      terminal_number = ?,
                      purchase_date = ?,
                      purchase_cost = ?,
                      supplier = ?,
                      warranty_expiry = ?,
                      status = ?,
                      condition = ?,
                      is_borrowable = ?,
                      notes = ?,
                      updated_by = ?
                  WHERE id = ?";
        
        $stmt = $this->conn->prepare($query);
        
        return $stmt->execute([
            $this->asset_name,
            $this->asset_type,
            $this->category,
            $this->brand,
            $this->model,
            $this->serial_number,
            $this->specifications,
            $this->room_id,
            $this->location,
            $this->terminal_number,
            $this->purchase_date,
            $this->purchase_cost,
            $this->supplier,
            $this->warranty_expiry,
            $this->status,
            $this->condition,
            $this->is_borrowable,
            $this->notes,
            $this->updated_by,
            $this->id
        ]);
    }
    
    /**
     * Delete asset
     */
    public function delete($id) {
        $query = "DELETE FROM " . $this->table . " WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        
        return $stmt->execute([$id]);
    }
    
    /**
     * Assign asset to user
     */
    public function assignToUser($asset_id, $user_id, $assigned_by) {
        $query = "UPDATE " . $this->table . "
                  SET assigned_to = ?,
                      assigned_date = NOW(),
                      assigned_by = ?,
                      status = 'In Use',
                      updated_by = ?
                  WHERE id = ?";
        
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([$user_id, $assigned_by, $assigned_by, $asset_id]);
    }
    
    /**
     * Unassign asset
     */
    public function unassignAsset($asset_id, $updated_by) {
        $query = "UPDATE " . $this->table . "
                  SET assigned_to = NULL,
                      assigned_date = NULL,
                      status = 'Available',
                      updated_by = ?
                  WHERE id = ?";
        
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([$updated_by, $asset_id]);
    }
    
    /**
     * Get asset statistics
     */
    public function getStatistics() {
        $query = "SELECT 
                    COUNT(*) as total_assets,
                    SUM(CASE WHEN status = 'Available' THEN 1 ELSE 0 END) as available,
                    SUM(CASE WHEN status = 'In Use' THEN 1 ELSE 0 END) as in_use,
                    SUM(CASE WHEN status = 'Under Maintenance' THEN 1 ELSE 0 END) as under_maintenance,
                    SUM(CASE WHEN is_borrowable = 1 THEN 1 ELSE 0 END) as borrowable,
                    SUM(purchase_cost) as total_value
                  FROM " . $this->table;
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get assets by type count
     */
    public function getAssetsByType() {
        $query = "SELECT asset_type, COUNT(*) as count
                  FROM " . $this->table . "
                  GROUP BY asset_type
                  ORDER BY count DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
