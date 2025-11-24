<?php
require_once 'Database.php';

class Room {
    private $conn;
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }
    
    /**
     * Get all rooms
     */
    public function getAll() {
        $query = "SELECT r.*, b.name as building_name FROM rooms r LEFT JOIN buildings b ON r.building_id = b.id ORDER BY r.name ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get room by ID
     */
    public function getById($id) {
        $query = "SELECT * FROM rooms WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get assets in a specific room
     */
    public function getAssetsByRoom($room_id) {
        $query = "SELECT * FROM assets WHERE room_id = ? ORDER BY asset_tag ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$room_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get asset count for each room
     */
    public function getRoomAssetCounts() {
        $query = "SELECT r.id, r.name, 
                  COUNT(a.id) as total_assets,
                  SUM(CASE WHEN a.status = 'Available' THEN 1 ELSE 0 END) as available,
                  SUM(CASE WHEN a.status = 'In Use' THEN 1 ELSE 0 END) as in_use,
                  SUM(CASE WHEN a.status = 'Under Maintenance' THEN 1 ELSE 0 END) as maintenance
                  FROM rooms r
                  LEFT JOIN assets a ON r.id = a.room_id
                  GROUP BY r.id, r.name
                  ORDER BY r.name ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
