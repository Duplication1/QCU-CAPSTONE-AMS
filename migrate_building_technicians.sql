-- Migration: Change from room-based technician assignment to building-based
-- Created: 2026-02-17

-- Step 1: Create building_technicians table for managing technicians per building
CREATE TABLE IF NOT EXISTS building_technicians (
    id INT AUTO_INCREMENT PRIMARY KEY,
    building_id INT NOT NULL,
    technician_id INT NOT NULL,
    assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    assigned_by INT NOT NULL,
    UNIQUE KEY unique_building_technician (building_id, technician_id),
    INDEX idx_building (building_id),
    INDEX idx_technician (technician_id),
    FOREIGN KEY (building_id) REFERENCES buildings(id) ON DELETE CASCADE,
    FOREIGN KEY (technician_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Step 2: Remove technician-related columns from maintenance_schedules table
ALTER TABLE maintenance_schedules 
    DROP COLUMN IF EXISTS assigned_technician_id,
    DROP COLUMN IF EXISTS assigned_technician_name,
    DROP COLUMN IF EXISTS assigned_at;

-- Step 3: Add helpful comments
ALTER TABLE building_technicians COMMENT = 'Manages technician assignments at the building level';
ALTER TABLE maintenance_schedules COMMENT = 'Tracks maintenance schedules for rooms with dates';
