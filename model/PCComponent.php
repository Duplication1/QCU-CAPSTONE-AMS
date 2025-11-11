<?php
/**
 * PC Component Model
 * 
 * Handles all database operations related to PC components
 */

require_once __DIR__ . '/Database.php';

class PCComponent {
    private $conn;
    private $table = 'pc_components';
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }
    
    /**
     * Get all components for a specific PC
     */
    public function getByPCUnit($pc_unit_id) {
        $query = "SELECT * FROM " . $this->table . " 
                  WHERE pc_unit_id = ? 
                  ORDER BY component_type ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$pc_unit_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get component by ID
     */
    public function getById($id) {
        $query = "SELECT * FROM " . $this->table . " WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Create new component
     */
    public function create($data) {
        $query = "INSERT INTO " . $this->table . " 
                  (pc_unit_id, component_type, component_name, brand, model, 
                   serial_number, specifications, purchase_date, purchase_cost, 
                   warranty_expiry, status, `condition`, notes)
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([
            $data['pc_unit_id'],
            $data['component_type'],
            $data['component_name'],
            $data['brand'] ?? null,
            $data['model'] ?? null,
            $data['serial_number'] ?? null,
            $data['specifications'] ?? null,
            $data['purchase_date'] ?? null,
            $data['purchase_cost'] ?? null,
            $data['warranty_expiry'] ?? null,
            $data['status'] ?? 'Working',
            $data['condition'] ?? 'Good',
            $data['notes'] ?? null
        ]);
    }
    
    /**
     * Update component
     */
    public function update($id, $data) {
        $query = "UPDATE " . $this->table . " 
                  SET component_type = ?, component_name = ?, brand = ?, model = ?,
                      serial_number = ?, specifications = ?, purchase_date = ?,
                      purchase_cost = ?, warranty_expiry = ?, status = ?,
                      `condition` = ?, notes = ?
                  WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([
            $data['component_type'],
            $data['component_name'],
            $data['brand'] ?? null,
            $data['model'] ?? null,
            $data['serial_number'] ?? null,
            $data['specifications'] ?? null,
            $data['purchase_date'] ?? null,
            $data['purchase_cost'] ?? null,
            $data['warranty_expiry'] ?? null,
            $data['status'] ?? 'Working',
            $data['condition'] ?? 'Good',
            $data['notes'] ?? null,
            $id
        ]);
    }
    
    /**
     * Delete component
     */
    public function delete($id) {
        $query = "DELETE FROM " . $this->table . " WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([$id]);
    }
    
    /**
     * Get components by type
     */
    public function getByType($component_type) {
        $query = "SELECT c.*, p.terminal_number, r.name as room_name
                  FROM " . $this->table . " c
                  JOIN pc_units p ON c.pc_unit_id = p.id
                  JOIN rooms r ON p.room_id = r.id
                  WHERE c.component_type = ?
                  ORDER BY r.name, p.terminal_number";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$component_type]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get faulty components across all PCs
     */
    public function getFaultyComponents() {
        $query = "SELECT c.*, p.terminal_number, p.pc_name, r.name as room_name
                  FROM " . $this->table . " c
                  JOIN pc_units p ON c.pc_unit_id = p.id
                  JOIN rooms r ON p.room_id = r.id
                  WHERE c.status = 'Faulty'
                  ORDER BY c.created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
