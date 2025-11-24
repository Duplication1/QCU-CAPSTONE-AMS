-- =============================================
-- QCU ASSET MANAGEMENT SYSTEM - COMPREHENSIVE DATABASE
-- Version 2.0 - Enterprise Level (FIXED & PRODUCTION READY)
-- XAMPP/MariaDB Compatible
-- 100% Re-runnable without errors
-- =============================================

-- Create database with proper character set
CREATE DATABASE IF NOT EXISTS qcu_asset_management_system 
CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE qcu_asset_management_system;

-- =============================================
-- SECTION 1: CORE CONFIGURATION TABLES
-- =============================================

CREATE TABLE IF NOT EXISTS departments (
    department_id INT AUTO_INCREMENT PRIMARY KEY,
    department_code VARCHAR(20) UNIQUE NOT NULL,
    department_name VARCHAR(100) NOT NULL,
    department_type ENUM('academic', 'administrative', 'laboratory', 'office', 'research', 'support') DEFAULT 'academic',
    location VARCHAR(100),
    contact_person VARCHAR(100),
    contact_number VARCHAR(20),
    email VARCHAR(100),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_dept_type (department_type),
    INDEX idx_dept_active (is_active)
) ENGINE=InnoDB COMMENT='Organizational departments structure';

CREATE TABLE IF NOT EXISTS categories (
    category_id INT AUTO_INCREMENT PRIMARY KEY,
    category_code VARCHAR(20) UNIQUE NOT NULL,
    category_name VARCHAR(100) NOT NULL,
    description TEXT,
    parent_category_id INT NULL,
    depreciation_rate DECIMAL(5,2) DEFAULT 0.00 COMMENT 'Annual depreciation %',
    useful_life_years INT DEFAULT 5,
    maintenance_interval_days INT DEFAULT 180 COMMENT 'Recommended maintenance frequency',
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (parent_category_id) REFERENCES categories(category_id) ON DELETE SET NULL,
    INDEX idx_cat_parent (parent_category_id),
    INDEX idx_cat_active (is_active)
) ENGINE=InnoDB COMMENT='Asset categories with lifecycle rules';

CREATE TABLE IF NOT EXISTS suppliers (
    supplier_id INT AUTO_INCREMENT PRIMARY KEY,
    supplier_code VARCHAR(20) UNIQUE NOT NULL,
    supplier_name VARCHAR(100) NOT NULL,
    contact_person VARCHAR(100),
    email VARCHAR(100),
    phone VARCHAR(20),
    address TEXT,
    tin_number VARCHAR(50) COMMENT 'Tax Identification Number',
    business_type ENUM('local', 'national', 'international', 'government') DEFAULT 'local',
    performance_rating ENUM('excellent', 'good', 'fair', 'poor') DEFAULT 'good',
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_supplier_rating (performance_rating),
    INDEX idx_supplier_active (is_active)
) ENGINE=InnoDB COMMENT='Supplier/vendor management';

CREATE TABLE IF NOT EXISTS fund_sources (
    fund_id INT AUTO_INCREMENT PRIMARY KEY,
    fund_code VARCHAR(20) UNIQUE NOT NULL,
    fund_name VARCHAR(100) NOT NULL,
    fund_type ENUM('regular', 'special', 'donation', 'grant', 'research', 'development') DEFAULT 'regular',
    fiscal_year YEAR,
    total_budget DECIMAL(15,2),
    balance DECIMAL(15,2),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_fund_year (fiscal_year),
    INDEX idx_fund_type (fund_type)
) ENGINE=InnoDB COMMENT='Budget and funding sources';

-- =============================================
-- SECTION 2: PROCUREMENT WORKFLOW TABLES
-- =============================================

CREATE TABLE IF NOT EXISTS purchase_requests (
    pr_id INT AUTO_INCREMENT PRIMARY KEY,
    pr_number VARCHAR(50) UNIQUE NOT NULL,
    department_id INT NOT NULL,
    requested_by VARCHAR(100) NOT NULL,
    request_date DATE NOT NULL,
    purpose TEXT,
    total_estimated_cost DECIMAL(15,2),
    status ENUM('draft', 'submitted', 'under_review', 'approved', 'rejected', 'po_created', 'cancelled') DEFAULT 'draft',
    approved_by VARCHAR(100),
    approval_date DATE,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (department_id) REFERENCES departments(department_id),
    INDEX idx_pr_status (status),
    INDEX idx_pr_date (request_date),
    INDEX idx_pr_dept (department_id)
) ENGINE=InnoDB COMMENT='Purchase Request (PR) management';

CREATE TABLE IF NOT EXISTS pr_items (
    pr_item_id INT AUTO_INCREMENT PRIMARY KEY,
    pr_id INT NOT NULL,
    item_description VARCHAR(200) NOT NULL,
    category_id INT NOT NULL,
    quantity INT NOT NULL,
    estimated_unit_cost DECIMAL(15,2) NOT NULL,
    specifications TEXT,
    justification TEXT,
    urgency ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (pr_id) REFERENCES purchase_requests(pr_id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(category_id),
    INDEX idx_pr_item_urgency (urgency)
) ENGINE=InnoDB COMMENT='Purchase Request line items';

CREATE TABLE IF NOT EXISTS purchase_orders (
    po_id INT AUTO_INCREMENT PRIMARY KEY,
    po_number VARCHAR(50) UNIQUE NOT NULL,
    pr_id INT,
    supplier_id INT NOT NULL,
    po_date DATE NOT NULL,
    delivery_date DATE,
    delivery_terms TEXT,
    payment_terms TEXT,
    total_amount DECIMAL(15,2),
    status ENUM('draft', 'ordered', 'delivered', 'partial', 'cancelled', 'paid') DEFAULT 'draft',
    prepared_by VARCHAR(100),
    approved_by VARCHAR(100),
    fund_id INT,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (supplier_id) REFERENCES suppliers(supplier_id),
    FOREIGN KEY (pr_id) REFERENCES purchase_requests(pr_id),
    FOREIGN KEY (fund_id) REFERENCES fund_sources(fund_id),
    INDEX idx_po_status (status),
    INDEX idx_po_date (po_date),
    INDEX idx_po_supplier (supplier_id)
) ENGINE=InnoDB COMMENT='Purchase Order (PO) management';

CREATE TABLE IF NOT EXISTS po_items (
    po_item_id INT AUTO_INCREMENT PRIMARY KEY,
    po_id INT NOT NULL,
    item_description VARCHAR(200) NOT NULL,
    category_id INT NOT NULL,
    quantity INT NOT NULL,
    unit_cost DECIMAL(15,2) NOT NULL,
    total_cost DECIMAL(15,2) NOT NULL,
    specifications TEXT,
    received_quantity INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (po_id) REFERENCES purchase_orders(po_id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(category_id)
) ENGINE=InnoDB COMMENT='Purchase Order line items';

-- =============================================
-- SECTION 3: ASSET REGISTRATION & LIFECYCLE
-- =============================================

CREATE TABLE IF NOT EXISTS assets (
    asset_id INT AUTO_INCREMENT PRIMARY KEY,
    asset_code VARCHAR(50) UNIQUE NOT NULL,
    property_number VARCHAR(100) UNIQUE COMMENT 'Government property number',
    serial_number VARCHAR(100),
    asset_name VARCHAR(200) NOT NULL,
    category_id INT NOT NULL,
    brand VARCHAR(100),
    model VARCHAR(100),
    specifications TEXT,
    
    -- Acquisition Details
    acquisition_type ENUM('purchase', 'donation', 'transfer', 'confiscation', 'fabricated', 'lease') DEFAULT 'purchase',
    acquisition_date DATE,
    purchase_date DATE,
    purchase_cost DECIMAL(15,2),
    supplier_id INT,
    po_id INT,
    fund_id INT,
    
    -- Financial Details
    salvage_value DECIMAL(15,2) COMMENT 'Estimated end-of-life value',
    current_value DECIMAL(15,2) COMMENT 'Book value after depreciation',
    replacement_cost DECIMAL(15,2) COMMENT 'Cost to replace new',
    depreciation_method ENUM('straight_line', 'declining_balance') DEFAULT 'straight_line',
    accumulated_depreciation DECIMAL(15,2) DEFAULT 0.00,
    
    -- Warranty & Lifecycle
    warranty_expiry DATE,
    expected_life_years INT,
    condition_rating ENUM('excellent', 'good', 'fair', 'poor', 'critical') DEFAULT 'good',
    last_condition_assessment DATE,
    
    -- Identification (Multi-Technology)
    barcode VARCHAR(100),
    rfid_tag VARCHAR(100),
    qr_code_path VARCHAR(500),
    qr_code_data TEXT,
    
    -- Government Compliance
    government_tag VARCHAR(100),
    inventory_card_number VARCHAR(100),
    
    -- Borrowing Configuration
    is_borrowable BOOLEAN DEFAULT FALSE,
    
    -- System Fields
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (category_id) REFERENCES categories(category_id),
    FOREIGN KEY (supplier_id) REFERENCES suppliers(supplier_id),
    FOREIGN KEY (po_id) REFERENCES purchase_orders(po_id),
    FOREIGN KEY (fund_id) REFERENCES fund_sources(fund_id),
    
    INDEX idx_asset_category (category_id),
    INDEX idx_asset_supplier (supplier_id),
    INDEX idx_asset_status (is_active),
    INDEX idx_asset_condition (condition_rating),
    INDEX idx_asset_dates (purchase_date, acquisition_date),
    INDEX idx_asset_borrowable (is_borrowable),
    FULLTEXT idx_asset_search (asset_name, brand, model, specifications)
) ENGINE=InnoDB COMMENT='Central asset registry with complete lifecycle tracking';

-- =============================================
-- SECTION 4: ASSET STATUS & DEPLOYMENT
-- =============================================

CREATE TABLE IF NOT EXISTS asset_status (
    status_id INT AUTO_INCREMENT PRIMARY KEY,
    asset_id INT NOT NULL,
    status ENUM('in_storage', 'deployed', 'under_maintenance', 'for_repair', 'for_calibration', 'for_disposal', 'disposed', 'lost', 'stolen') DEFAULT 'in_storage',
    department_id INT,
    location VARCHAR(100),
    room_number VARCHAR(50),
    assigned_to VARCHAR(100),
    assigned_to_position VARCHAR(100),
    custodian VARCHAR(100) COMMENT 'Responsible person for asset',
    notes TEXT,
    status_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    reported_by VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (asset_id) REFERENCES assets(asset_id) ON DELETE CASCADE,
    FOREIGN KEY (department_id) REFERENCES departments(department_id),
    
    INDEX idx_status_asset (asset_id, status_date),
    INDEX idx_status_current (status, department_id),
    INDEX idx_status_location (location)
) ENGINE=InnoDB COMMENT='Asset status history and current location tracking';

CREATE TABLE IF NOT EXISTS asset_transfers (
    transfer_id INT AUTO_INCREMENT PRIMARY KEY,
    asset_id INT NOT NULL,
    from_department_id INT NOT NULL,
    to_department_id INT NOT NULL,
    from_location VARCHAR(100),
    to_location VARCHAR(100),
    transfer_date DATE NOT NULL,
    transfer_reason TEXT,
    transferred_by VARCHAR(100),
    received_by VARCHAR(100),
    received_date DATE,
    status ENUM('pending', 'completed', 'cancelled', 'rejected') DEFAULT 'pending',
    transfer_approval_by VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (asset_id) REFERENCES assets(asset_id) ON DELETE CASCADE,
    FOREIGN KEY (from_department_id) REFERENCES departments(department_id),
    FOREIGN KEY (to_department_id) REFERENCES departments(department_id),
    
    INDEX idx_transfer_asset (asset_id),
    INDEX idx_transfer_status (status),
    INDEX idx_transfer_date (transfer_date)
) ENGINE=InnoDB COMMENT='Inter-department asset transfer tracking';

-- =============================================
-- SECTION 5: BORROWING SYSTEM
-- =============================================

CREATE TABLE IF NOT EXISTS borrowing_records (
    borrow_id INT AUTO_INCREMENT PRIMARY KEY,
    asset_id INT NOT NULL,
    borrower_type ENUM('student', 'faculty', 'staff', 'external') NOT NULL,
    borrower_id VARCHAR(50) NOT NULL,
    borrower_name VARCHAR(100) NOT NULL,
    borrower_department VARCHAR(100),
    borrow_date DATETIME NOT NULL,
    expected_return_date DATETIME NOT NULL,
    actual_return_date DATETIME NULL,
    purpose TEXT,
    status ENUM('requested', 'approved', 'borrowed', 'returned', 'overdue', 'cancelled', 'lost') DEFAULT 'requested',
    condition_before TEXT,
    condition_after TEXT,
    approved_by VARCHAR(100),
    approved_date DATETIME,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (asset_id) REFERENCES assets(asset_id) ON DELETE CASCADE,
    
    INDEX idx_borrowing_dates (borrow_date, expected_return_date),
    INDEX idx_borrowing_status (status, borrower_type),
    INDEX idx_borrowing_borrower (borrower_id),
    INDEX idx_borrowing_overdue (expected_return_date, status)
) ENGINE=InnoDB COMMENT='Asset borrowing transactions with accountability';

-- =============================================
-- SECTION 6: MAINTENANCE & REPAIR MANAGEMENT
-- =============================================

CREATE TABLE IF NOT EXISTS maintenance_records (
    maintenance_id INT AUTO_INCREMENT PRIMARY KEY,
    asset_id INT NOT NULL,
    maintenance_type ENUM('preventive', 'corrective', 'routine', 'emergency', 'calibration', 'scheduled') NOT NULL,
    maintenance_date DATE NOT NULL,
    next_maintenance_date DATE,
    performed_by VARCHAR(100),
    performed_by_company VARCHAR(100),
    cost DECIMAL(10,2) DEFAULT 0.00,
    description TEXT,
    issues_found TEXT,
    actions_taken TEXT,
    recommendations TEXT,
    status ENUM('scheduled', 'in_progress', 'completed', 'cancelled', 'deferred') DEFAULT 'completed',
    downtime_days INT DEFAULT 0 COMMENT 'Days asset was unavailable',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (asset_id) REFERENCES assets(asset_id) ON DELETE CASCADE,
    
    INDEX idx_maintenance_dates (maintenance_date, next_maintenance_date),
    INDEX idx_maintenance_asset_status (asset_id, status),
    INDEX idx_maintenance_type (maintenance_type)
) ENGINE=InnoDB COMMENT='Preventive and corrective maintenance tracking';

CREATE TABLE IF NOT EXISTS repair_history (
    repair_id INT AUTO_INCREMENT PRIMARY KEY,
    asset_id INT NOT NULL,
    repair_date DATE NOT NULL,
    completion_date DATE,
    problem_description TEXT NOT NULL,
    solution_applied TEXT,
    parts_replaced TEXT,
    labor_cost DECIMAL(10,2) DEFAULT 0.00,
    parts_cost DECIMAL(10,2) DEFAULT 0.00,
    total_cost DECIMAL(10,2),
    repaired_by VARCHAR(100),
    repair_company VARCHAR(100),
    warranty_until DATE,
    status ENUM('pending', 'in_progress', 'completed', 'cancelled') DEFAULT 'completed',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (asset_id) REFERENCES assets(asset_id) ON DELETE CASCADE,
    
    INDEX idx_repair_asset (asset_id),
    INDEX idx_repair_date (repair_date),
    INDEX idx_repair_status (status)
) ENGINE=InnoDB COMMENT='Asset repair history and costs';

-- =============================================
-- SECTION 7: TICKETING SYSTEM
-- =============================================

CREATE TABLE IF NOT EXISTS asset_tickets (
    ticket_id INT AUTO_INCREMENT PRIMARY KEY,
    ticket_number VARCHAR(50) UNIQUE NOT NULL,
    asset_id INT NOT NULL,
    reported_by VARCHAR(100) NOT NULL,
    reported_by_type ENUM('student', 'faculty', 'staff', 'system') DEFAULT 'student',
    reported_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    issue_description TEXT NOT NULL,
    priority ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium',
    status ENUM('open', 'in_progress', 'resolved', 'closed', 'cancelled') DEFAULT 'open',
    assigned_to VARCHAR(100),
    resolution_notes TEXT,
    resolved_date TIMESTAMP NULL,
    satisfaction_rating INT CHECK (satisfaction_rating >= 1 AND satisfaction_rating <= 5),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (asset_id) REFERENCES assets(asset_id) ON DELETE CASCADE,
    
    INDEX idx_ticket_status (status),
    INDEX idx_ticket_priority (priority),
    INDEX idx_ticket_assigned (assigned_to),
    INDEX idx_ticket_dates (reported_date, resolved_date)
) ENGINE=InnoDB COMMENT='Issue tracking and ticketing system';

-- =============================================
-- SECTION 8: DISPOSAL MANAGEMENT
-- =============================================

CREATE TABLE IF NOT EXISTS disposal_requests (
    request_id INT AUTO_INCREMENT PRIMARY KEY,
    asset_id INT NOT NULL,
    request_date DATE NOT NULL,
    requested_by VARCHAR(100) NOT NULL,
    requested_by_department INT,
    reason TEXT NOT NULL,
    disposal_reason ENUM('obsolete', 'damaged_beyond_repair', 'end_of_life', 'surplus', 'upgrade', 'other', 'theft', 'lost') DEFAULT 'obsolete',
    current_condition TEXT,
    estimated_value DECIMAL(10,2),
    last_physical_count_date DATE,
    status ENUM('draft', 'submitted', 'under_review', 'committee_approved', 'university_approved', 'city_hall_approved', 'rejected', 'cancelled') DEFAULT 'draft',
    submitted_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (asset_id) REFERENCES assets(asset_id) ON DELETE CASCADE,
    FOREIGN KEY (requested_by_department) REFERENCES departments(department_id),
    
    INDEX idx_disposal_status_date (status, request_date),
    INDEX idx_disposal_asset (asset_id)
) ENGINE=InnoDB COMMENT='Asset disposal request workflow';

CREATE TABLE IF NOT EXISTS disposal_committee (
    committee_id INT AUTO_INCREMENT PRIMARY KEY,
    request_id INT NOT NULL,
    member_name VARCHAR(100) NOT NULL,
    position VARCHAR(100),
    department VARCHAR(100),
    signature_path VARCHAR(500),
    approval_date DATE,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (request_id) REFERENCES disposal_requests(request_id) ON DELETE CASCADE
) ENGINE=InnoDB COMMENT='Disposal committee member approvals';

CREATE TABLE IF NOT EXISTS disposal_methods (
    method_id INT AUTO_INCREMENT PRIMARY KEY,
    request_id INT NOT NULL,
    method ENUM('public_auction', 'sealed_bidding', 'donation', 'scrap', 'transfer', 'destruction', 'trade_in', 'sale') DEFAULT 'public_auction',
    disposal_date DATE,
    final_value DECIMAL(15,2),
    recipient_name VARCHAR(100),
    recipient_address TEXT,
    certificate_number VARCHAR(100),
    certificate_path VARCHAR(500),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (request_id) REFERENCES disposal_requests(request_id) ON DELETE CASCADE
) ENGINE=InnoDB COMMENT='Disposal method execution tracking';

CREATE TABLE IF NOT EXISTS disposal_tracking (
    tracking_id INT AUTO_INCREMENT PRIMARY KEY,
    request_id INT NOT NULL,
    tracking_date DATE NOT NULL,
    from_location VARCHAR(100),
    to_location VARCHAR(100),
    handled_by VARCHAR(100),
    received_by_city_hall VARCHAR(100),
    receiving_office VARCHAR(100),
    city_hall_receipt_number VARCHAR(100),
    notes TEXT,
    status ENUM('preparing', 'in_transit', 'received_city_hall', 'processing', 'completed', 'cancelled') DEFAULT 'preparing',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (request_id) REFERENCES disposal_requests(request_id) ON DELETE CASCADE,
    
    INDEX idx_disposal_tracking_status (status, tracking_date)
) ENGINE=InnoDB COMMENT='City hall submission tracking';

-- =============================================
-- SECTION 9: FINANCIAL & COMPLIANCE
-- =============================================

CREATE TABLE IF NOT EXISTS depreciation_records (
    depreciation_id INT AUTO_INCREMENT PRIMARY KEY,
    asset_id INT NOT NULL,
    fiscal_year YEAR NOT NULL,
    depreciation_amount DECIMAL(15,2) NOT NULL,
    accumulated_depreciation DECIMAL(15,2) NOT NULL,
    book_value DECIMAL(15,2) NOT NULL,
    calculation_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (asset_id) REFERENCES assets(asset_id) ON DELETE CASCADE,
    
    INDEX idx_depreciation_year (fiscal_year),
    INDEX idx_depreciation_asset (asset_id)
) ENGINE=InnoDB COMMENT='Annual depreciation calculation records';

CREATE TABLE IF NOT EXISTS asset_insurance (
    insurance_id INT AUTO_INCREMENT PRIMARY KEY,
    asset_id INT NOT NULL,
    insurance_company VARCHAR(100),
    policy_number VARCHAR(100),
    coverage_amount DECIMAL(15,2),
    premium_amount DECIMAL(10,2),
    start_date DATE,
    end_date DATE,
    contact_person VARCHAR(100),
    contact_number VARCHAR(20),
    notes TEXT,
    status ENUM('active', 'expired', 'cancelled') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (asset_id) REFERENCES assets(asset_id) ON DELETE CASCADE,
    
    INDEX idx_insurance_status (status),
    INDEX idx_insurance_dates (start_date, end_date)
) ENGINE=InnoDB COMMENT='Asset insurance policy tracking';

CREATE TABLE IF NOT EXISTS physical_inventory (
    inventory_id INT AUTO_INCREMENT PRIMARY KEY,
    inventory_date DATE NOT NULL,
    conducted_by VARCHAR(100),
    department_id INT,
    location VARCHAR(100),
    status ENUM('scheduled', 'in_progress', 'completed', 'discrepancies', 'cancelled') DEFAULT 'scheduled',
    discrepancies_found TEXT,
    verified_by VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (department_id) REFERENCES departments(department_id),
    
    INDEX idx_inventory_date (inventory_date),
    INDEX idx_inventory_status (status)
) ENGINE=InnoDB COMMENT='Physical inventory count schedule';

CREATE TABLE IF NOT EXISTS inventory_items (
    item_id INT AUTO_INCREMENT PRIMARY KEY,
    inventory_id INT NOT NULL,
    asset_id INT NOT NULL,
    physical_condition ENUM('excellent', 'good', 'fair', 'poor', 'missing', 'found') DEFAULT 'good',
    verified_location VARCHAR(100),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (inventory_id) REFERENCES physical_inventory(inventory_id) ON DELETE CASCADE,
    FOREIGN KEY (asset_id) REFERENCES assets(asset_id) ON DELETE CASCADE
) ENGINE=InnoDB COMMENT='Physical inventory count line items';

-- =============================================
-- SECTION 10: DOCUMENT MANAGEMENT
-- =============================================

CREATE TABLE IF NOT EXISTS asset_documents (
    document_id INT AUTO_INCREMENT PRIMARY KEY,
    asset_id INT NOT NULL,
    document_type ENUM('photo', 'receipt', 'warranty', 'manual', 'certificate', 'inspection', 'other', 'contract', 'approval') NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_size INT,
    upload_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    uploaded_by VARCHAR(100),
    description TEXT,
    
    FOREIGN KEY (asset_id) REFERENCES assets(asset_id) ON DELETE CASCADE,
    
    INDEX idx_doc_asset (asset_id),
    INDEX idx_doc_type (document_type)
) ENGINE=InnoDB COMMENT='Asset-related document repository';

-- =============================================
-- SECTION 11: USER MANAGEMENT & SECURITY
-- =============================================

CREATE TABLE IF NOT EXISTS users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    email VARCHAR(100),
    full_name VARCHAR(100) NOT NULL,
    department_id INT,
    position VARCHAR(100),
    role ENUM('super_admin', 'admin', 'department_head', 'technician', 'staff', 'viewer', 'auditor') DEFAULT 'staff',
    is_active BOOLEAN DEFAULT TRUE,
    last_login TIMESTAMP NULL,
    e_signature VARCHAR(255) COMMENT 'E-signature file path',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (department_id) REFERENCES departments(department_id),
    
    INDEX idx_user_role (role),
    INDEX idx_user_active (is_active),
    INDEX idx_user_dept (department_id)
) ENGINE=InnoDB COMMENT='System users with role-based access';

CREATE TABLE IF NOT EXISTS system_logs (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    action VARCHAR(100) NOT NULL,
    table_name VARCHAR(50),
    record_id INT,
    old_values JSON COMMENT 'Previous values in JSON format',
    new_values JSON COMMENT 'Updated values in JSON format',
    description TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    log_timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(user_id),
    
    INDEX idx_log_user (user_id),
    INDEX idx_log_action (action),
    INDEX idx_log_table (table_name, record_id),
    INDEX idx_log_timestamp (log_timestamp)
) ENGINE=InnoDB COMMENT='Complete system audit trail';

-- =============================================
-- SECTION 12: ANALYTICS & PREDICTIVE MAINTENANCE
-- =============================================

CREATE TABLE IF NOT EXISTS analytics_cache (
    cache_id INT AUTO_INCREMENT PRIMARY KEY,
    metric_name VARCHAR(100) NOT NULL,
    metric_value DECIMAL(15,2),
    metric_date DATE,
    category VARCHAR(100),
    department_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (department_id) REFERENCES departments(department_id),
    
    INDEX idx_analytics_metrics (metric_date, metric_name),
    INDEX idx_analytics_category (category)
) ENGINE=InnoDB COMMENT='Pre-computed analytics for dashboard performance';

CREATE TABLE IF NOT EXISTS predictive_maintenance (
    prediction_id INT AUTO_INCREMENT PRIMARY KEY,
    asset_id INT NOT NULL,
    prediction_date DATE NOT NULL,
    failure_probability DECIMAL(5,4) COMMENT 'Probability score 0.0000 to 1.0000',
    predicted_failure_date DATE,
    recommended_action TEXT,
    confidence_level ENUM('low', 'medium', 'high') DEFAULT 'medium',
    is_processed BOOLEAN DEFAULT FALSE,
    processed_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (asset_id) REFERENCES assets(asset_id) ON DELETE CASCADE,
    
    INDEX idx_predict_asset (asset_id),
    INDEX idx_predict_date (prediction_date),
    INDEX idx_predict_processed (is_processed)
) ENGINE=InnoDB COMMENT='AI/ML-ready predictive maintenance scoring';

-- =============================================
-- SECTION 13: SYSTEM CONFIGURATION
-- =============================================

CREATE TABLE IF NOT EXISTS system_config (
    config_id INT AUTO_INCREMENT PRIMARY KEY,
    config_key VARCHAR(100) UNIQUE NOT NULL,
    config_value TEXT,
    config_type ENUM('string', 'integer', 'boolean', 'json') DEFAULT 'string',
    description TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_config_key (config_key),
    INDEX idx_config_active (is_active)
) ENGINE=InnoDB COMMENT='Dynamic system configuration settings';

CREATE TABLE IF NOT EXISTS notification_settings (
    setting_id INT AUTO_INCREMENT PRIMARY KEY,
    notification_type VARCHAR(100) NOT NULL,
    recipient_role ENUM('admin', 'technician', 'staff', 'all') DEFAULT 'admin',
    is_enabled BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_notif_type (notification_type),
    INDEX idx_notif_enabled (is_enabled)
) ENGINE=InnoDB COMMENT='System notification configuration';

-- =============================================
-- SECTION 14: LEGACY COMPATIBILITY (PC Health Monitoring)
-- =============================================

CREATE TABLE IF NOT EXISTS rooms (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_room_name (name)
) ENGINE=InnoDB COMMENT='Computer laboratory rooms';

CREATE TABLE IF NOT EXISTS pc_units (
    id INT AUTO_INCREMENT PRIMARY KEY,
    room_id INT NOT NULL,
    terminal_number VARCHAR(50) NOT NULL,
    pc_name VARCHAR(100),
    asset_tag VARCHAR(50),
    status ENUM('Active','Inactive','Under Maintenance','Disposed') NOT NULL DEFAULT 'Active',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    UNIQUE KEY unique_room_terminal (room_id, terminal_number),
    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE,
    
    INDEX idx_pc_room (room_id),
    INDEX idx_pc_terminal (terminal_number),
    INDEX idx_pc_asset (asset_tag)
) ENGINE=InnoDB COMMENT='Individual PC units for health monitoring';

CREATE TABLE IF NOT EXISTS pc_components (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pc_unit_id INT NOT NULL,
    component_type ENUM('CPU','RAM','Motherboard','Storage','GPU','PSU','Case','Monitor','Keyboard','Mouse','Other') NOT NULL,
    component_name VARCHAR(255) NOT NULL,
    brand VARCHAR(100),
    model VARCHAR(100),
    serial_number VARCHAR(100),
    specifications TEXT,
    purchase_date DATE,
    purchase_cost DECIMAL(10,2),
    warranty_expiry DATE,
    status ENUM('Working','Faulty','Replaced','Disposed') NOT NULL DEFAULT 'Working',
    `condition` ENUM('Excellent','Good','Fair','Poor') NOT NULL DEFAULT 'Good',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (pc_unit_id) REFERENCES pc_units(id) ON DELETE CASCADE,
    
    INDEX idx_component_unit (pc_unit_id),
    INDEX idx_component_type (component_type),
    INDEX idx_component_status (status)
) ENGINE=InnoDB COMMENT='PC component tracking for health monitoring';

-- =============================================
-- SECTION 15: APPLICATION-SPECIFIC TABLES
-- =============================================

CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    type ENUM('info','success','warning','error') DEFAULT 'info',
    related_type ENUM('issue','borrowing','asset','system') DEFAULT 'system',
    related_id INT,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_notif_user (user_id),
    INDEX idx_notif_read (is_read),
    INDEX idx_notif_created (created_at)
) ENGINE=InnoDB COMMENT='User notification queue';

-- =============================================
-- SECTION 16: VIEWS FOR SIMPLIFIED QUERIES
-- =============================================

CREATE OR REPLACE VIEW current_asset_status AS
SELECT 
    a.asset_id,
    a.asset_code,
    a.property_number,
    a.asset_name,
    c.category_name,
    d.department_name,
    s.status,
    s.location,
    s.room_number,
    s.assigned_to,
    s.custodian,
    a.purchase_date,
    a.purchase_cost,
    a.current_value,
    a.condition_rating,
    a.is_borrowable
FROM assets a
LEFT JOIN categories c ON a.category_id = c.category_id
LEFT JOIN (
    SELECT s1.*
    FROM asset_status s1
    INNER JOIN (
        SELECT asset_id, MAX(status_date) as max_date
        FROM asset_status
        GROUP BY asset_id
    ) s2 ON s1.asset_id = s2.asset_id AND s1.status_date = s2.max_date
) s ON a.asset_id = s.asset_id
LEFT JOIN departments d ON s.department_id = d.department_id
WHERE a.is_active = TRUE;

CREATE OR REPLACE VIEW maintenance_due AS
SELECT 
    a.asset_code,
    a.asset_name,
    c.category_name,
    m.next_maintenance_date,
    DATEDIFF(m.next_maintenance_date, CURDATE()) as days_until_due,
    m.maintenance_type as last_maintenance_type
FROM assets a
JOIN categories c ON a.category_id = c.category_id
JOIN maintenance_records m ON a.asset_id = m.asset_id
WHERE m.next_maintenance_date IS NOT NULL 
AND m.next_maintenance_date >= CURDATE()
AND m.status = 'completed'
ORDER BY m.next_maintenance_date ASC;

CREATE OR REPLACE VIEW borrowing_overdue AS
SELECT 
    b.borrow_id,
    a.asset_code,
    a.asset_name,
    b.borrower_name,
    b.borrower_type,
    b.borrower_id,
    b.borrow_date,
    b.expected_return_date,
    DATEDIFF(CURDATE(), b.expected_return_date) as days_overdue
FROM borrowing_records b
JOIN assets a ON b.asset_id = a.asset_id
WHERE b.status = 'borrowed' 
AND b.expected_return_date < CURDATE();

CREATE OR REPLACE VIEW asset_financial_summary AS
SELECT 
    c.category_name,
    COUNT(a.asset_id) as asset_count,
    SUM(a.purchase_cost) as total_purchase_cost,
    SUM(a.current_value) as total_current_value,
    SUM(a.accumulated_depreciation) as total_depreciation,
    AVG(DATEDIFF(CURDATE(), a.purchase_date)/365.25) as avg_age_years
FROM assets a
JOIN categories c ON a.category_id = c.category_id
WHERE a.is_active = TRUE
GROUP BY c.category_id, c.category_name;

-- =============================================
-- SECTION 17: STORED PROCEDURES
-- =============================================

DELIMITER //

-- Calculate depreciation for a specific asset
DROP PROCEDURE IF EXISTS CalculateAssetDepreciation//
CREATE PROCEDURE CalculateAssetDepreciation(IN p_asset_id INT)
BEGIN
    DECLARE v_purchase_cost DECIMAL(15,2);
    DECLARE v_depreciation_rate DECIMAL(5,2);
    DECLARE v_accumulated_depreciation DECIMAL(15,2);
    DECLARE v_current_value DECIMAL(15,2);
    DECLARE v_depreciation_amount DECIMAL(15,2);
    DECLARE v_salvage_value DECIMAL(15,2);
    
    SELECT purchase_cost, depreciation_rate, accumulated_depreciation, salvage_value
    INTO v_purchase_cost, v_depreciation_rate, v_accumulated_depreciation, v_salvage_value
    FROM assets a
    JOIN categories c ON a.category_id = c.category_id
    WHERE a.asset_id = p_asset_id;
    
    SET v_depreciation_amount = ((v_purchase_cost - COALESCE(v_salvage_value, 0)) * v_depreciation_rate / 100);
    SET v_current_value = v_purchase_cost - v_accumulated_depreciation - v_depreciation_amount;
    
    IF v_current_value < COALESCE(v_salvage_value, 0) THEN
        SET v_current_value = COALESCE(v_salvage_value, 0);
        SET v_depreciation_amount = v_purchase_cost - v_accumulated_depreciation - v_salvage_value;
    END IF;
    
    UPDATE assets 
    SET accumulated_depreciation = accumulated_depreciation + v_depreciation_amount,
        current_value = v_current_value,
        updated_at = CURRENT_TIMESTAMP
    WHERE asset_id = p_asset_id;
    
    INSERT INTO depreciation_records (asset_id, fiscal_year, depreciation_amount, accumulated_depreciation, book_value, calculation_date)
    VALUES (p_asset_id, YEAR(CURDATE()), v_depreciation_amount, v_accumulated_depreciation + v_depreciation_amount, v_current_value, CURDATE());
END//

-- Calculate depreciation for all active assets
DROP PROCEDURE IF EXISTS CalculateAllDepreciation//
CREATE PROCEDURE CalculateAllDepreciation()
BEGIN
    DECLARE done INT DEFAULT FALSE;
    DECLARE asset_id_val INT;
    DECLARE cur CURSOR FOR SELECT asset_id FROM assets WHERE is_active = TRUE AND purchase_cost > 0;
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;
    
    OPEN cur;
    
    read_loop: LOOP
        FETCH cur INTO asset_id_val;
        IF done THEN
            LEAVE read_loop;
        END IF;
        
        CALL CalculateAssetDepreciation(asset_id_val);
    END LOOP;
    
    CLOSE cur;
END//

-- Generate custom asset report
DROP PROCEDURE IF EXISTS GenerateAssetReport//
CREATE PROCEDURE GenerateAssetReport(IN report_type VARCHAR(50), IN param_department_id INT)
BEGIN
    IF report_type = 'department_assets' THEN
        SELECT * FROM current_asset_status WHERE department_name IN (SELECT department_name FROM departments WHERE department_id = param_department_id);
    ELSEIF report_type = 'maintenance_due' THEN
        SELECT * FROM maintenance_due;
    ELSEIF report_type = 'borrowing_overdue' THEN
        SELECT * FROM borrowing_overdue;
    ELSEIF report_type = 'financial_summary' THEN
        SELECT * FROM asset_financial_summary;
    END IF;
END//

DELIMITER ;

-- =============================================
-- SECTION 18: SAMPLE INITIALIZATION DATA
-- =============================================

-- System Configuration
INSERT INTO system_config (config_key, config_value, config_type, description) VALUES
('system.name', 'QCU Asset Management System', 'string', 'System display name'),
('qr.code.base_url', 'http://localhost/CAPSTONE-AMS/assets', 'string', 'Base URL for QR codes'),
('backup.schedule', '0 2 * * 0', 'string', 'Automatic backup schedule (cron format)'),
('maintenance.alert_days', '30', 'integer', 'Days before maintenance alert'),
('disposal.approval_required', 'true', 'boolean', 'Require approval for disposal'),
('depreciation.auto_calculate', 'true', 'boolean', 'Auto-calculate depreciation annually'),
('borrowing.max_days', '7', 'integer', 'Maximum borrowing period in days')
ON DUPLICATE KEY UPDATE config_value = VALUES(config_value);

-- Notification Settings
INSERT INTO notification_settings (notification_type, recipient_role, is_enabled) VALUES
('maintenance_due', 'technician', TRUE),
('borrowing_overdue', 'admin', TRUE),
('disposal_approved', 'admin', TRUE),
('low_inventory', 'admin', TRUE),
('ticket_assigned', 'technician', TRUE),
('asset_transferred', 'admin', TRUE)
ON DUPLICATE KEY UPDATE is_enabled = VALUES(is_enabled);

-- Sample Departments
INSERT INTO departments (department_code, department_name, department_type, location, contact_person, email) VALUES
('ICT', 'Information and Communications Technology', 'administrative', 'Main Building', 'ICT Director', 'ict@qcu.edu.ph'),
('COE', 'College of Engineering', 'academic', 'Engineering Building', 'Dean of Engineering', 'coe@qcu.edu.ph'),
('CICS', 'College of Computer Studies', 'academic', 'Computer Building', 'Dean of CICS', 'cics@qcu.edu.ph'),
('LAB-IK501', 'Computer Laboratory IK501', 'laboratory', 'Main Building Room 501', 'Lab Supervisor', 'lab501@qcu.edu.ph'),
('LAB-IK502', 'Computer Laboratory IK502', 'laboratory', 'Main Building Room 502', 'Lab Supervisor', 'lab502@qcu.edu.ph'),
('ADMIN', 'University Administration', 'administrative', 'Admin Building', 'Admin Head', 'admin@qcu.edu.ph')
ON DUPLICATE KEY UPDATE department_name = VALUES(department_name);

-- Sample Categories
INSERT INTO categories (category_code, category_name, description, depreciation_rate, useful_life_years, maintenance_interval_days) VALUES
('COMP-DT', 'Desktop Computers', 'Desktop computer units for laboratories and offices', 25.00, 5, 180),
('COMP-LT', 'Laptop Computers', 'Portable laptop computer units', 30.00, 4, 180),
('PRINT', 'Printers', 'All types of printers (inkjet, laser, multifunction)', 20.00, 5, 90),
('NET-SW', 'Network Switches', 'Network switching equipment', 15.00, 7, 180),
('NET-AP', 'Access Points', 'Wireless access points', 20.00, 5, 180),
('NET-RT', 'Routers', 'Network routing equipment', 15.00, 7, 180),
('PER-MON', 'Monitors', 'Computer display monitors', 25.00, 6, 365),
('PER-KB', 'Keyboards', 'Computer keyboards', 40.00, 3, 365),
('PER-MS', 'Computer Mice', 'Computer mice and pointing devices', 40.00, 3, 365),
('PER-UPS', 'UPS Units', 'Uninterruptible Power Supply units', 20.00, 5, 90),
('FURN-DSK', 'Desks', 'Computer desks and workstations', 10.00, 10, 730),
('FURN-CHR', 'Chairs', 'Office and computer chairs', 15.00, 7, 365),
('AC', 'Air Conditioners', 'Cooling systems for rooms', 15.00, 8, 90),
('PROJ', 'Projectors', 'Video projectors for presentations', 25.00, 5, 180),
('SOFT-OS', 'Operating Systems', 'Operating system licenses', 0.00, 3, 0),
('SOFT-APP', 'Application Software', 'Application software licenses', 0.00, 3, 0)
ON DUPLICATE KEY UPDATE category_name = VALUES(category_name);

-- Sample Suppliers
INSERT INTO suppliers (supplier_code, supplier_name, contact_person, email, phone, business_type, performance_rating) VALUES
('SUP-DELL', 'Dell Philippines Inc.', 'Sales Manager', 'sales@dell.ph', '02-1234-5678', 'national', 'excellent'),
('SUP-HP', 'HP Philippines', 'Account Manager', 'sales@hp.ph', '02-2345-6789', 'national', 'excellent'),
('SUP-LENOVO', 'Lenovo Technology', 'Sales Executive', 'sales@lenovo.ph', '02-3456-7890', 'national', 'good'),
('SUP-CISCO', 'Cisco Systems Philippines', 'Channel Manager', 'sales@cisco.ph', '02-4567-8901', 'international', 'excellent'),
('SUP-LOCAL-IT', 'Local IT Solutions', 'Owner', 'info@localit.com', '02-5678-9012', 'local', 'good')
ON DUPLICATE KEY UPDATE supplier_name = VALUES(supplier_name);

-- Sample Fund Sources
INSERT INTO fund_sources (fund_code, fund_name, fund_type, fiscal_year, total_budget, balance) VALUES
('REG-2025', 'Regular Budget FY2025', 'regular', 2025, 5000000.00, 4500000.00),
('SPEC-ICT-2025', 'Special ICT Fund FY2025', 'special', 2025, 2000000.00, 1800000.00),
('DONA-TECH-2025', 'Technology Donation Fund', 'donation', 2025, 500000.00, 500000.00)
ON DUPLICATE KEY UPDATE fund_name = VALUES(fund_name);

-- Sample Rooms
INSERT INTO rooms (name) VALUES
('IK501'), ('IK502'), ('IK503'), ('IK504'), ('IK505')
ON DUPLICATE KEY UPDATE name = VALUES(name);

-- =============================================
-- DATABASE VALIDATION QUERY
-- =============================================

SELECT 
    'QCU AMS DATABASE v2.0 CREATED SUCCESSFULLY!' as status,
    COUNT(*) as total_tables,
    (SELECT COUNT(*) FROM information_schema.VIEWS WHERE TABLE_SCHEMA = 'qcu_asset_management_system') as total_views,
    (SELECT COUNT(*) FROM information_schema.ROUTINES WHERE ROUTINE_SCHEMA = 'qcu_asset_management_system' AND ROUTINE_TYPE = 'PROCEDURE') as total_procedures
FROM information_schema.TABLES 
WHERE TABLE_SCHEMA = 'qcu_asset_management_system' 
AND TABLE_TYPE = 'BASE TABLE';

-- =============================================
-- END OF SCRIPT
-- Production Ready - 100% Re-runnable
-- =============================================
