<?php
require_once __DIR__ . '/../config/config.php';

class AssetHistory {
    private $conn;
    
    public function __construct() {
        $this->conn = new mysqli('localhost', 'root', '', 'ams_database');
        
        if ($this->conn->connect_error) {
            throw new Exception("Connection failed: " . $this->conn->connect_error);
        }
    }
    
    /**
     * Log an asset history entry
     */
    public function logHistory($asset_id, $action_type, $field_changed = null, $old_value = null, $new_value = null, $description = null, $performed_by = null) {
        try {
            // Get user details if performed_by is provided
            $performed_by_name = null;
            $ip_address = $_SERVER['REMOTE_ADDR'] ?? null;
            $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
            
            if ($performed_by) {
                $user_query = $this->conn->prepare("SELECT CONCAT(first_name, ' ', last_name) as full_name FROM users WHERE id = ?");
                $user_query->bind_param('i', $performed_by);
                $user_query->execute();
                $result = $user_query->get_result();
                if ($user = $result->fetch_assoc()) {
                    $performed_by_name = $user['full_name'];
                }
                $user_query->close();
            }
            
            $stmt = $this->conn->prepare("
                INSERT INTO asset_history 
                (asset_id, action_type, field_changed, old_value, new_value, description, performed_by, performed_by_name, ip_address, user_agent) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->bind_param('isssssssss', 
                $asset_id, 
                $action_type, 
                $field_changed, 
                $old_value, 
                $new_value, 
                $description, 
                $performed_by, 
                $performed_by_name,
                $ip_address,
                $user_agent
            );
            
            $result = $stmt->execute();
            $stmt->close();
            
            return $result;
        } catch (Exception $e) {
            error_log("AssetHistory Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get history for a specific asset
     */
    public function getAssetHistory($asset_id, $limit = 50) {
        try {
            $stmt = $this->conn->prepare("
                SELECT 
                    ah.*,
                    a.asset_tag,
                    a.asset_name
                FROM asset_history ah
                LEFT JOIN assets a ON ah.asset_id = a.id
                WHERE ah.asset_id = ?
                ORDER BY ah.created_at DESC
                LIMIT ?
            ");
            
            $stmt->bind_param('ii', $asset_id, $limit);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $history = [];
            while ($row = $result->fetch_assoc()) {
                $history[] = $row;
            }
            
            $stmt->close();
            return $history;
        } catch (Exception $e) {
            error_log("AssetHistory Error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get recent history across all assets
     */
    public function getRecentHistory($limit = 100) {
        try {
            $stmt = $this->conn->prepare("
                SELECT 
                    ah.*,
                    a.asset_tag,
                    a.asset_name,
                    a.asset_type,
                    r.name as room_name
                FROM asset_history ah
                LEFT JOIN assets a ON ah.asset_id = a.id
                LEFT JOIN rooms r ON a.room_id = r.id
                ORDER BY ah.created_at DESC
                LIMIT ?
            ");
            
            $stmt->bind_param('i', $limit);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $history = [];
            while ($row = $result->fetch_assoc()) {
                $history[] = $row;
            }
            
            $stmt->close();
            return $history;
        } catch (Exception $e) {
            error_log("AssetHistory Error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get history by action type
     */
    public function getHistoryByAction($action_type, $limit = 50) {
        try {
            $stmt = $this->conn->prepare("
                SELECT 
                    ah.*,
                    a.asset_tag,
                    a.asset_name
                FROM asset_history ah
                LEFT JOIN assets a ON ah.asset_id = a.id
                WHERE ah.action_type = ?
                ORDER BY ah.created_at DESC
                LIMIT ?
            ");
            
            $stmt->bind_param('si', $action_type, $limit);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $history = [];
            while ($row = $result->fetch_assoc()) {
                $history[] = $row;
            }
            
            $stmt->close();
            return $history;
        } catch (Exception $e) {
            error_log("AssetHistory Error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get statistics for an asset
     */
    public function getAssetStats($asset_id) {
        try {
            $stmt = $this->conn->prepare("
                SELECT 
                    COUNT(*) as total_changes,
                    COUNT(DISTINCT action_type) as unique_actions,
                    MIN(created_at) as first_recorded,
                    MAX(created_at) as last_activity,
                    COUNT(DISTINCT performed_by) as unique_users
                FROM asset_history
                WHERE asset_id = ?
            ");
            
            $stmt->bind_param('i', $asset_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $stats = $result->fetch_assoc();
            $stmt->close();
            
            return $stats;
        } catch (Exception $e) {
            error_log("AssetHistory Error: " . $e->getMessage());
            return null;
        }
    }
}
