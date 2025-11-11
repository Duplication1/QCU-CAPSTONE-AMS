<?php
/**
 * PC Unit Model
 * 
 * Handles all database operations related to PC units
 */

require_once __DIR__ . '/Database.php';

class PCUnit {
    private $conn;
    private $table = 'pc_units';
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }
    
    /**
     * Get all PC units
     */
    public function getAll() {
        $query = "SELECT p.*, r.name as room_name 
                  FROM " . $this->table . " p
                  LEFT JOIN rooms r ON p.room_id = r.id
                  ORDER BY r.name ASC, p.terminal_number ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get PC units by room
     */
    public function getByRoom($room_id) {
        $query = "SELECT p.*, r.name as room_name 
                  FROM " . $this->table . " p
                  LEFT JOIN rooms r ON p.room_id = r.id
                  WHERE p.room_id = ?
                  ORDER BY p.terminal_number ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$room_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get PC unit by ID with components
     */
    public function getByIdWithComponents($id) {
        // Get PC unit details
        $query = "SELECT p.*, r.name as room_name 
                  FROM " . $this->table . " p
                  LEFT JOIN rooms r ON p.room_id = r.id
                  WHERE p.id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$id]);
        $pcUnit = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($pcUnit) {
            // Get components
            $query = "SELECT * FROM pc_components 
                      WHERE pc_unit_id = ? 
                      ORDER BY 
                        CASE component_type
                          WHEN 'CPU' THEN 1
                          WHEN 'Motherboard' THEN 2
                          WHEN 'RAM' THEN 3
                          WHEN 'Storage' THEN 4
                          WHEN 'GPU' THEN 5
                          WHEN 'PSU' THEN 6
                          WHEN 'Case' THEN 7
                          WHEN 'Monitor' THEN 8
                          WHEN 'Keyboard' THEN 9
                          WHEN 'Mouse' THEN 10
                          ELSE 11
                        END";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$id]);
            $pcUnit['components'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        
        return $pcUnit;
    }
    
    /**
     * Get PC unit by room and terminal number
     */
    public function getByRoomAndTerminal($room_id, $terminal_number) {
        $query = "SELECT p.*, r.name as room_name 
                  FROM " . $this->table . " p
                  LEFT JOIN rooms r ON p.room_id = r.id
                  WHERE p.room_id = ? AND p.terminal_number = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$room_id, $terminal_number]);
        $pcUnit = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($pcUnit) {
            // Get components
            $query = "SELECT * FROM pc_components WHERE pc_unit_id = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$pcUnit['id']]);
            $pcUnit['components'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        
        return $pcUnit;
    }
    
    /**
     * Create new PC unit
     */
    public function create($data) {
        $query = "INSERT INTO " . $this->table . " 
                  (room_id, terminal_number, pc_name, asset_tag, status, notes)
                  VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([
            $data['room_id'],
            $data['terminal_number'],
            $data['pc_name'] ?? null,
            $data['asset_tag'] ?? null,
            $data['status'] ?? 'Active',
            $data['notes'] ?? null
        ]);
    }
    
    /**
     * Update PC unit
     */
    public function update($id, $data) {
        $query = "UPDATE " . $this->table . " 
                  SET pc_name = ?, asset_tag = ?, status = ?, notes = ?
                  WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([
            $data['pc_name'] ?? null,
            $data['asset_tag'] ?? null,
            $data['status'] ?? 'Active',
            $data['notes'] ?? null,
            $id
        ]);
    }
    
    /**
     * Delete PC unit
     */
    public function delete($id) {
        $query = "DELETE FROM " . $this->table . " WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([$id]);
    }
    
    /**
     * Get component statistics for a PC
     */
    public function getComponentStats($pc_unit_id) {
        $query = "SELECT 
                    COUNT(*) as total_components,
                    SUM(CASE WHEN status = 'Working' THEN 1 ELSE 0 END) as working,
                    SUM(CASE WHEN status = 'Faulty' THEN 1 ELSE 0 END) as faulty,
                    SUM(CASE WHEN status = 'Replaced' THEN 1 ELSE 0 END) as replaced
                  FROM pc_components
                  WHERE pc_unit_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$pc_unit_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
