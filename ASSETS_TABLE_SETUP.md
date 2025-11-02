# Assets Table Setup Instructions

## Overview
This creates a comprehensive asset management system with three related tables:
1. **assets** - Main asset inventory table
2. **asset_borrowing** - Borrowing transactions and history
3. **asset_maintenance** - Maintenance logs and schedules

## Database Setup

### Run the SQL Migration

1. **Open phpMyAdmin** (http://localhost/phpmyadmin)
2. **Select your database** (`ams_database`)
3. **Go to the SQL tab**
4. **Import or paste** the content from `database/create_assets_table.sql`
5. **Click "Go"** to execute

## Assets Table Structure

### Main Asset Information
- `id` - Primary key
- `asset_tag` - Unique identifier (e.g., COMP-IK501-001)
- `asset_name` - Name/model of the asset
- `asset_type` - Hardware, Software, Furniture, Equipment, Peripheral, Network Device, Other
- `category` - Subcategory (Desktop, Laptop, Printer, etc.)
- `brand` - Manufacturer brand
- `model` - Model number/name
- `serial_number` - Serial number
- `specifications` - Technical specifications

### Location Information
- `room_id` - Foreign key to rooms table
- `location` - Specific location within room
- `terminal_number` - Terminal/workstation number

### Financial Information
- `purchase_date` - Date of purchase
- `purchase_cost` - Cost in PHP
- `supplier` - Vendor/supplier name
- `warranty_expiry` - Warranty expiration date

### Status Information
- `status` - Active, In Use, Available, Under Maintenance, Retired, Disposed, Lost, Damaged
- `condition` - Excellent, Good, Fair, Poor, Non-Functional
- **`is_borrowable`** - **1 = Can be borrowed, 0 = Cannot be borrowed** ✨

### Assignment Information
- `assigned_to` - User ID of person assigned
- `assigned_date` - When assigned
- `assigned_by` - User ID who made assignment

### Maintenance Information
- `last_maintenance_date` - Last maintenance
- `next_maintenance_date` - Scheduled next maintenance
- `maintenance_notes` - Maintenance notes

### Additional Information
- `notes` - General notes
- `qr_code` - Path to QR code image
- `image` - Path to asset image

### Audit Fields
- `created_by` - User who created record
- `updated_by` - User who last updated
- `created_at` - Creation timestamp
- `updated_at` - Last update timestamp

## Asset Borrowing Table

Tracks all borrowing transactions:
- Asset borrowed
- Borrower information
- Borrow and return dates
- Approval status
- Condition on return
- Purpose of borrowing

**Statuses:** Pending, Approved, Borrowed, Returned, Overdue, Cancelled

## Asset Maintenance Table

Logs all maintenance activities:
- Maintenance type (Preventive, Corrective, Emergency, Inspection, Upgrade)
- Date and performer
- Cost and description
- Next scheduled maintenance

**Statuses:** Scheduled, In Progress, Completed, Cancelled

## Sample Data Included

The migration includes 10 sample assets:
- 3 Desktop Computers (fixed location, not borrowable)
- 2 Laptops (borrowable)
- 1 Projector (borrowable)
- 1 Network Printer (not borrowable)
- 1 Network Switch (not borrowable)
- 1 Software License (not borrowable)
- 1 Office Chair (not borrowable)

## Key Features

✅ **Borrowable Assets**
   - `is_borrowable` field determines if asset can be borrowed
   - Separate borrowing transaction table
   - Tracks approval workflow

✅ **Asset Tracking**
   - Unique asset tags
   - Serial numbers
   - Location tracking (room + terminal)
   - Assignment tracking

✅ **Financial Management**
   - Purchase information
   - Cost tracking
   - Warranty tracking
   - Supplier information

✅ **Maintenance Management**
   - Maintenance history
   - Scheduled maintenance
   - Cost tracking
   - Technician assignment

✅ **Status Management**
   - Multiple status types
   - Condition tracking
   - Assignment tracking
   - Full audit trail

✅ **Database Integrity**
   - Foreign key constraints
   - Indexes for performance
   - Cascading updates/deletes where appropriate
   - Proper data types and constraints

## Usage Examples

### Find all borrowable assets
```sql
SELECT * FROM assets WHERE is_borrowable = 1 AND status = 'Available';
```

### Find assets in a specific room
```sql
SELECT a.*, r.name as room_name 
FROM assets a 
LEFT JOIN rooms r ON a.room_id = r.id 
WHERE r.name = 'IK501';
```

### Find currently borrowed assets
```sql
SELECT a.asset_name, ab.borrower_name, ab.borrowed_date 
FROM asset_borrowing ab
JOIN assets a ON ab.asset_id = a.id
WHERE ab.status = 'Borrowed';
```

### Find assets due for maintenance
```sql
SELECT * FROM assets 
WHERE next_maintenance_date <= CURDATE() 
AND status != 'Retired';
```

## Next Steps

After creating the tables, you can:
1. Build CRUD interfaces for asset management
2. Implement borrowing workflow
3. Add QR code generation for assets
4. Create maintenance scheduling system
5. Build reporting dashboards
6. Add asset depreciation tracking
