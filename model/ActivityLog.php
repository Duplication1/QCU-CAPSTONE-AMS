<?php
/**
 * ActivityLog Model
 * Handles activity logging for the system
 */

class ActivityLog {
    private $conn;
    private $table_name = "activity_logs";

    public function __construct() {
        $db = new Database();
        $this->conn = $db->getConnection();
        $this->ensureTableExists();
    }

    /**
     * Ensure activity_logs table exists
     */
    private function ensureTableExists() {
        $query = "CREATE TABLE IF NOT EXISTS `activity_logs` (
            `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
            `user_id` int(10) UNSIGNED NOT NULL,
            `action` varchar(50) NOT NULL,
            `entity_type` varchar(50) DEFAULT NULL,
            `entity_id` int(10) UNSIGNED DEFAULT NULL,
            `description` text DEFAULT NULL,
            `ip_address` varchar(45) DEFAULT NULL,
            `user_agent` text DEFAULT NULL,
            `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
            PRIMARY KEY (`id`),
            KEY `user_id` (`user_id`),
            KEY `action` (`action`),
            KEY `created_at` (`created_at`),
            KEY `entity_type` (`entity_type`, `entity_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        try {
            $this->conn->exec($query);
        } catch (PDOException $e) {
            error_log("Activity log table creation failed: " . $e->getMessage());
        }
    }

    /**
     * Log an activity
     * 
     * @param int $user_id User ID performing the action
     * @param string $action Action type (create, update, delete, export, login, logout, etc.)
     * @param string $entity_type Type of entity affected (user, asset, ticket, etc.)
     * @param int|null $entity_id ID of the affected entity
     * @param string|null $description Additional description
     * @return bool Success status
     */
    public function log($user_id, $action, $entity_type = null, $entity_id = null, $description = null) {
        $query = "INSERT INTO " . $this->table_name . " 
                  (user_id, action, entity_type, entity_id, description, ip_address, user_agent) 
                  VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        // Get IP address
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? null;
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip_address = $_SERVER['HTTP_X_FORWARDED_FOR'];
        }
        
        // Get user agent
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
        
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->execute([
                $user_id,
                $action,
                $entity_type,
                $entity_id,
                $description,
                $ip_address,
                $user_agent
            ]);
            return true;
        } catch (PDOException $e) {
            error_log("Activity log failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Static helper for quick logging
     */
    public static function record($user_id, $action, $entity_type = null, $entity_id = null, $description = null) {
        $log = new self();
        return $log->log($user_id, $action, $entity_type, $entity_id, $description);
    }
}
