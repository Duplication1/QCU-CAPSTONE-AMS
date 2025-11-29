<?php
require_once __DIR__ . '/../config/config.php';

class Database {
    private $host;
    private $db_name;
    private $username;
    private $password;
    private $conn;
    
    public function __construct() {
        // Load database configuration from .env
        $dbConfig = Config::database();
        $this->host = $dbConfig['host'];
        $this->db_name = $dbConfig['name'];
        $this->username = $dbConfig['username'];
        $this->password = $dbConfig['password'];
    }
    
    public function getConnection() {
        $this->conn = null;
        
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name,
                $this->username,
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            throw new Exception("Database connection failed: " . $e->getMessage());
        }
        
        return $this->conn;
    }
}
?>
