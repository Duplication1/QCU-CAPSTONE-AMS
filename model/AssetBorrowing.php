<?php
/**
 * Asset Borrowing Model
 * 
 * Handles all database operations related to asset borrowing
 */

require_once __DIR__ . '/Database.php';

class AssetBorrowing {
    private $conn;
    private $table = 'asset_borrowing';
    
    // Borrowing properties
    public $id;
    public $asset_id;
    public $borrower_id;
    public $borrower_name;
    public $borrowed_date;
    public $expected_return_date;
    public $actual_return_date;
    public $purpose;
    public $status;
    public $approved_by;
    public $approved_date;
    public $returned_condition;
    public $return_notes;
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }
    
    /**
     * Get all borrowing records with optional filters
     */
    public function getAll($filters = []) {
        $query = "SELECT ab.*, 
                         a.asset_tag,
                         a.asset_name,
                         a.asset_type,
                         u.full_name as borrower_full_name,
                         ap.full_name as approved_by_name
                  FROM " . $this->table . " ab
                  INNER JOIN assets a ON ab.asset_id = a.id
                  INNER JOIN users u ON ab.borrower_id = u.id
                  LEFT JOIN users ap ON ab.approved_by = ap.id
                  WHERE 1=1";
        
        $params = [];
        
        // Apply filters
        if (!empty($filters['status'])) {
            $query .= " AND ab.status = ?";
            $params[] = $filters['status'];
        }
        
        if (!empty($filters['borrower_id'])) {
            $query .= " AND ab.borrower_id = ?";
            $params[] = $filters['borrower_id'];
        }
        
        if (!empty($filters['asset_id'])) {
            $query .= " AND ab.asset_id = ?";
            $params[] = $filters['asset_id'];
        }
        
        $query .= " ORDER BY ab.created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get borrowing record by ID
     */
    public function getById($id) {
        $query = "SELECT ab.*, 
                         a.asset_tag,
                         a.asset_name,
                         a.asset_type,
                         a.brand,
                         a.model,
                         u.full_name as borrower_full_name,
                         u.email as borrower_email,
                         ap.full_name as approved_by_name
                  FROM " . $this->table . " ab
                  INNER JOIN assets a ON ab.asset_id = a.id
                  INNER JOIN users u ON ab.borrower_id = u.id
                  LEFT JOIN users ap ON ab.approved_by = ap.id
                  WHERE ab.id = ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$id]);
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Create new borrowing request
     */
    public function create() {
        $query = "INSERT INTO " . $this->table . "
                  SET asset_id = ?,
                      borrower_id = ?,
                      borrower_name = ?,
                      borrowed_date = ?,
                      expected_return_date = ?,
                      purpose = ?,
                      status = ?";
        
        $stmt = $this->conn->prepare($query);
        
        return $stmt->execute([
            $this->asset_id,
            $this->borrower_id,
            $this->borrower_name,
            $this->borrowed_date,
            $this->expected_return_date,
            $this->purpose,
            $this->status
        ]);
    }
    
    /**
     * Approve borrowing request
     */
    public function approve($id, $approved_by) {
        $query = "UPDATE " . $this->table . "
                  SET status = 'Approved',
                      approved_by = ?,
                      approved_date = NOW()
                  WHERE id = ?";
        
        $stmt = $this->conn->prepare($query);
        
        if ($stmt->execute([$approved_by, $id])) {
            // Update asset status to 'Borrowed'
            $query2 = "UPDATE assets a
                       INNER JOIN " . $this->table . " ab ON a.id = ab.asset_id
                       SET a.status = 'In Use'
                       WHERE ab.id = ?";
            $stmt2 = $this->conn->prepare($query2);
            $stmt2->execute([$id]);
            
            return true;
        }
        
        return false;
    }
    
    /**
     * Return borrowed asset
     */
    public function returnAsset($id, $returned_condition, $return_notes = null) {
        $query = "UPDATE " . $this->table . "
                  SET status = 'Returned',
                      actual_return_date = NOW(),
                      returned_condition = ?,
                      return_notes = ?
                  WHERE id = ?";
        
        $stmt = $this->conn->prepare($query);
        
        if ($stmt->execute([$returned_condition, $return_notes, $id])) {
            // Update asset status back to 'Available'
            $query2 = "UPDATE assets a
                       INNER JOIN " . $this->table . " ab ON a.id = ab.asset_id
                       SET a.status = 'Available'
                       WHERE ab.id = ?";
            $stmt2 = $this->conn->prepare($query2);
            $stmt2->execute([$id]);
            
            return true;
        }
        
        return false;
    }
    
    /**
     * Cancel borrowing request
     */
    public function cancel($id) {
        $query = "UPDATE " . $this->table . "
                  SET status = 'Cancelled'
                  WHERE id = ?";
        
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([$id]);
    }
    
    /**
     * Get user's borrowing history
     */
    public function getUserHistory($user_id) {
        $query = "SELECT ab.*, 
                         a.asset_tag,
                         a.asset_name,
                         a.asset_type
                  FROM " . $this->table . " ab
                  INNER JOIN assets a ON ab.asset_id = a.id
                  WHERE ab.borrower_id = ?
                  ORDER BY ab.created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$user_id]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get asset's borrowing history
     */
    public function getAssetHistory($asset_id) {
        $query = "SELECT ab.*, 
                         u.full_name as borrower_full_name,
                         ap.full_name as approved_by_name
                  FROM " . $this->table . " ab
                  INNER JOIN users u ON ab.borrower_id = u.id
                  LEFT JOIN users ap ON ab.approved_by = ap.id
                  WHERE ab.asset_id = ?
                  ORDER BY ab.created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$asset_id]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get overdue borrowings
     */
    public function getOverdue() {
        $query = "SELECT ab.*, 
                         a.asset_tag,
                         a.asset_name,
                         u.full_name as borrower_full_name,
                         u.email as borrower_email
                  FROM " . $this->table . " ab
                  INNER JOIN assets a ON ab.asset_id = a.id
                  INNER JOIN users u ON ab.borrower_id = u.id
                  WHERE ab.status IN ('Borrowed', 'Approved')
                  AND ab.expected_return_date < NOW()
                  ORDER BY ab.expected_return_date";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get borrowing statistics
     */
    public function getStatistics() {
        $query = "SELECT 
                    COUNT(*) as total_borrowings,
                    SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending,
                    SUM(CASE WHEN status = 'Approved' THEN 1 ELSE 0 END) as approved,
                    SUM(CASE WHEN status = 'Borrowed' THEN 1 ELSE 0 END) as borrowed,
                    SUM(CASE WHEN status = 'Returned' THEN 1 ELSE 0 END) as returned,
                    SUM(CASE WHEN status = 'Overdue' THEN 1 ELSE 0 END) as overdue
                  FROM " . $this->table;
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>
