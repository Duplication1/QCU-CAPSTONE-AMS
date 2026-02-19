<?php
require_once 'Database.php';

class User {
    private $conn;
    private $table_name = "users";
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }
    
    /**
     * Authenticate user with ID number and password
     * Automatically detects role based on ID format
     */
    public function authenticate($id_number, $password) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE id_number = :id_number LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id_number', $id_number);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $user = $stmt->fetch();
            
            // Verify password
            if (password_verify($password, $user['password'])) {
                // Remove password from returned data
                unset($user['password']);
                return $user;
            }
        }
        
        return false;
    }
    
    /**
     * Determine role based on ID number format
     * S prefix = Student
     * F prefix = Faculty
     * T prefix = Technician
     * L prefix = Laboratory Staff
     * A prefix = Administrator
     */
    public function determineRoleFromId($id_number) {
        $prefix = strtoupper(substr($id_number, 0, 1));
        
        switch ($prefix) {
            case 'S':
                return 'Student';
            case 'F':
                return 'Faculty';
            case 'T':
                return 'Technician';
            case 'L':
                return 'Laboratory Staff';
            case 'A':
                return 'Administrator';
            default:
                return null;
        }
    }
    
    /**
     * Create new user
     */
    public function create($id_number, $password, $full_name, $email, $role = null) {
        // Auto-detect role if not provided
        if ($role === null) {
            $role = $this->determineRoleFromId($id_number);
        }
        
        if ($role === null) {
            return false;
        }
        
        $query = "INSERT INTO " . $this->table_name . " 
                  (id_number, password, full_name, email, role, created_at) 
                  VALUES (:id_number, :password, :full_name, :email, :role, NOW())";
        
        $stmt = $this->conn->prepare($query);
        
        // Hash password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        $stmt->bindParam(':id_number', $id_number);
        $stmt->bindParam(':password', $hashed_password);
        $stmt->bindParam(':full_name', $full_name);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':role', $role);
        
        if ($stmt->execute()) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Get user by ID number
     */
    public function getByIdNumber($id_number) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE id_number = :id_number LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id_number', $id_number);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $user = $stmt->fetch();
            unset($user['password']);
            return $user;
        }
        
        return false;
    }
    
    /**
     * Update user profile
     */
    public function update($id, $data) {
        $query = "UPDATE " . $this->table_name . " 
                  SET full_name = :full_name, 
                      email = :email,
                      updated_at = NOW()
                  WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':full_name', $data['full_name']);
        $stmt->bindParam(':email', $data['email']);
        
        return $stmt->execute();
    }
    
    /**
     * Change password
     */
    public function changePassword($id, $new_password) {
        $query = "UPDATE " . $this->table_name . " 
                  SET password = :password, 
                      updated_at = NOW()
                  WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':password', $hashed_password);
        
        return $stmt->execute();
    }

    /**
     * Update the user's last_login timestamp to now
     */
    public function updateLastLogin($id) {
        $query = "UPDATE " . $this->table_name . " SET last_login = NOW() WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }

    /**
     * Check if account is locked
     */
    public function isAccountLocked($id_number) {
        $query = "SELECT account_locked_until FROM " . $this->table_name . " WHERE id_number = :id_number LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id_number', $id_number);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch();
            if ($row['account_locked_until'] && strtotime($row['account_locked_until']) > time()) {
                return $row['account_locked_until'];
            }
        }
        return false;
    }

    /**
     * Get current failed login attempts
     */
    public function getFailedAttempts($id_number) {
        $query = "SELECT failed_login_attempts FROM " . $this->table_name . " WHERE id_number = :id_number LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id_number', $id_number);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch();
            return (int)$row['failed_login_attempts'];
        }
        return 0;
    }

    /**
     * Increment failed login attempts
     * Returns the new attempt count
     */
    public function incrementFailedAttempts($id_number) {
        // First, get current attempts
        $query = "SELECT failed_login_attempts FROM " . $this->table_name . " WHERE id_number = :id_number LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id_number', $id_number);
        $stmt->execute();
        
        $attempts = 0;
        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch();
            $attempts = (int)$row['failed_login_attempts'] + 1;
        } else {
            // User doesn't exist - don't track attempts for invalid IDs
            return 0;
        }
        
        // If 3 or more attempts, lock the account for 30 minutes
        if ($attempts >= 3) {
            $query = "UPDATE " . $this->table_name . " 
                      SET failed_login_attempts = :attempts, 
                          account_locked_until = DATE_ADD(NOW(), INTERVAL 30 MINUTE)
                      WHERE id_number = :id_number";
        } else {
            $query = "UPDATE " . $this->table_name . " 
                      SET failed_login_attempts = :attempts
                      WHERE id_number = :id_number";
        }
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':attempts', $attempts);
        $stmt->bindParam(':id_number', $id_number);
        $stmt->execute();
        
        return $attempts;
    }

    /**
     * Reset failed login attempts on successful login
     */
    public function resetFailedAttempts($id_number) {
        $query = "UPDATE " . $this->table_name . " 
                  SET failed_login_attempts = 0, 
                      account_locked_until = NULL
                  WHERE id_number = :id_number";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id_number', $id_number);
        return $stmt->execute();
    }
}
?>
