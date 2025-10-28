-- Asset Management System Database
-- Create database
CREATE DATABASE IF NOT EXISTS ams_database;
USE ams_database;

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_number VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    role ENUM('Student', 'Faculty', 'Technician', 'Laboratory Staff', 'Administrator') NOT NULL,
    status ENUM('Active', 'Inactive', 'Suspended') DEFAULT 'Active',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    last_login DATETIME DEFAULT NULL,
    INDEX idx_id_number (id_number),
    INDEX idx_role (role),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert sample users for testing
-- Password for all users: password123

-- Administrator
INSERT INTO users (id_number, password, full_name, email, role) VALUES
('A2024-001', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin User', 'admin@ams.edu', 'Administrator');

-- Technician
INSERT INTO users (id_number, password, full_name, email, role) VALUES
('T2024-001', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'John Technician', 'technician@ams.edu', 'Technician');

-- Laboratory Staff
INSERT INTO users (id_number, password, full_name, email, role) VALUES
('L2024-001', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Maria Lab Staff', 'labstaff@ams.edu', 'Laboratory Staff');

-- Faculty
INSERT INTO users (id_number, password, full_name, email, role) VALUES
('F2024-001', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Dr. Jane Faculty', 'faculty@ams.edu', 'Faculty');

-- Students
INSERT INTO users (id_number, password, full_name, email, role) VALUES
('S2024-001', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Student One', 'student1@ams.edu', 'Student'),
('S2024-002', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Student Two', 'student2@ams.edu', 'Student');

-- Assets table (for future use)
CREATE TABLE IF NOT EXISTS assets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    asset_code VARCHAR(50) UNIQUE NOT NULL,
    asset_name VARCHAR(200) NOT NULL,
    category VARCHAR(100),
    description TEXT,
    status ENUM('Available', 'In Use', 'Maintenance', 'Damaged', 'Disposed') DEFAULT 'Available',
    location VARCHAR(200),
    purchase_date DATE,
    purchase_cost DECIMAL(10,2),
    assigned_to INT DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_asset_code (asset_code),
    INDEX idx_status (status),
    INDEX idx_category (category)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Asset requests table (for future use)
CREATE TABLE IF NOT EXISTS asset_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    asset_id INT,
    request_type ENUM('Borrow', 'Return', 'Maintenance', 'Report Issue') NOT NULL,
    request_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    requested_from DATE,
    requested_until DATE,
    purpose TEXT,
    status ENUM('Pending', 'Approved', 'Rejected', 'Completed', 'Cancelled') DEFAULT 'Pending',
    approved_by INT DEFAULT NULL,
    approval_date DATETIME DEFAULT NULL,
    notes TEXT,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (asset_id) REFERENCES assets(id) ON DELETE SET NULL,
    FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_user_id (user_id),
    INDEX idx_status (status),
    INDEX idx_request_date (request_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Activity logs table
CREATE TABLE IF NOT EXISTS activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    action VARCHAR(100) NOT NULL,
    description TEXT,
    ip_address VARCHAR(45),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_user_id (user_id),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
