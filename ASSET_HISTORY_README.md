# Asset History & QR Code Scanner System

## Overview
This system provides comprehensive asset tracking with detailed history logging and a public QR code scanner page that displays asset details and history.

## Components

### 1. Database
- **Table**: `asset_history`
- **Location**: `database/create_asset_history_table.sql`
- **Purpose**: Tracks all changes made to assets including status changes, location moves, condition updates, assignments, borrowing, maintenance, etc.

#### Installation
Run the SQL script to create the table:
```sql
-- Via phpMyAdmin or MySQL command line
SOURCE database/create_asset_history_table.sql;
```

Or directly execute it in your database management tool.

### 2. Model Layer
- **File**: `model/AssetHistory.php`
- **Purpose**: Handles database operations for asset history
- **Key Methods**:
  - `logHistory()` - Log a history entry
  - `getAssetHistory($asset_id, $limit)` - Get history for specific asset
  - `getRecentHistory($limit)` - Get recent history across all assets
  - `getHistoryByAction($action_type, $limit)` - Filter by action type
  - `getAssetStats($asset_id)` - Get statistics for an asset

### 3. Controller/Helper Layer
- **File**: `controller/AssetHistoryHelper.php`
- **Purpose**: Provides convenient methods for logging common asset operations
- **Key Methods**:
  - `logAssetCreated()` - Log when asset is created
  - `logStatusChange()` - Log status updates
  - `logConditionChange()` - Log condition changes
  - `logLocationChange()` - Log location moves
  - `logAssignment()` / `logUnassignment()` - Log assignments
  - `logBorrowing()` / `logReturn()` - Log borrowing operations
  - `logMaintenance()` - Log maintenance activities
  - `logDisposal()` / `logRestoration()` - Log disposal and restoration
  - `logQRGeneration()` - Log QR code generation

### 4. Public Scanner Page
- **File**: `view/public/scan_asset.php`
- **URL**: `http://yourdomain.com/view/public/scan_asset.php?id={asset_id}`
- **Purpose**: Public-facing page that displays:
  - Asset details (tag, name, status, condition, etc.)
  - QR code visualization
  - Detailed specifications
  - Statistics (total changes, users involved, days tracked)
  - Borrowing history (if borrowable)
  - Complete activity timeline
- **Features**:
  - No authentication required (public access)
  - Beautiful gradient UI with glass-morphism design
  - Responsive design (mobile-friendly)
  - Timeline visualization of all asset changes

## Usage

### Logging Asset History

#### Option 1: Direct Method (Model)
```php
<?php
require_once 'model/AssetHistory.php';

$history = new AssetHistory();
$history->logHistory(
    $asset_id,           // Asset ID
    'Status Changed',    // Action type
    'status',            // Field changed
    'Available',         // Old value
    'In Use',            // New value
    'Asset assigned',    // Description
    $user_id             // Performed by (user ID)
);
?>
```

#### Option 2: Helper Method (Recommended)
```php
<?php
require_once 'controller/AssetHistoryHelper.php';

$helper = AssetHistoryHelper::getInstance();

// Log asset creation
$helper->logAssetCreated($asset_id, $asset_tag, $asset_name, $user_id);

// Log status change
$helper->logStatusChange($asset_id, 'Available', 'In Use', $user_id);

// Log condition change
$helper->logConditionChange($asset_id, 'Good', 'Fair', $user_id);

// Log borrowing
$helper->logBorrowing($asset_id, 'John Doe', $user_id);

// Log return
$helper->logReturn($asset_id, 'John Doe', $user_id);
?>
```

### Retrieving Asset History

```php
<?php
require_once 'model/AssetHistory.php';

$history = new AssetHistory();

// Get history for specific asset
$asset_history = $history->getAssetHistory($asset_id, 50);

// Get recent history across all assets
$recent = $history->getRecentHistory(100);

// Get statistics for an asset
$stats = $history->getAssetStats($asset_id);
// Returns: total_changes, unique_actions, first_recorded, last_activity, unique_users
?>
```

### Updating QR Codes

#### Step 1: Configure Base URL
Edit `update_qr_codes.php` and set your base URL:
```php
$base_url = 'http://yourdomain.com/QCU-CAPSTONE-AMS';
```

#### Step 2: Run the Update Script
```bash
# Via command line
php update_qr_codes.php

# Or access via browser
http://yourdomain.com/QCU-CAPSTONE-AMS/update_qr_codes.php
```

This will update all existing QR codes to link to the scanner page.

### Generating QR Codes for New Assets

Update your asset creation code to generate QR codes with the scan URL:

```php
<?php
// When creating a new asset
$asset_id = $conn->insert_id;  // Get the newly created asset ID

// Generate scan URL
$scan_url = 'http://yourdomain.com/view/public/scan_asset.php?id=' . $asset_id;

// Generate QR code
$qr_code_url = 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=' . urlencode($scan_url);

// Update asset with QR code
$stmt = $conn->prepare("UPDATE assets SET qr_code = ? WHERE id = ?");
$stmt->bind_param('si', $qr_code_url, $asset_id);
$stmt->execute();

// Log QR generation
$helper = AssetHistoryHelper::getInstance();
$helper->logQRGeneration($asset_id, $user_id);
?>
```

## Integration Points

### When Creating Assets
```php
require_once 'controller/AssetHistoryHelper.php';
$helper = AssetHistoryHelper::getInstance();
$helper->logAssetCreated($asset_id, $asset_tag, $asset_name, $created_by);
```

### When Updating Assets
```php
// Before updating
$old_status = $asset['status'];

// Perform update
$conn->query("UPDATE assets SET status = 'In Use' WHERE id = $asset_id");

// Log the change
$helper->logStatusChange($asset_id, $old_status, 'In Use', $user_id);
```

### When Disposing Assets
```php
$helper->logDisposal($asset_id, 'Asset reached end of life', $user_id);
```

### When Borrowing/Returning
```php
// On borrow approval
$helper->logBorrowing($asset_id, $borrower_name, $approved_by);

// On return
$helper->logReturn($asset_id, $borrower_name, $returned_by);
```

## Action Types

The system supports the following action types:
- `Created` - Asset creation
- `Updated` - General updates
- `Status Changed` - Status modifications
- `Location Changed` - Location moves
- `Assigned` - Asset assignments
- `Unassigned` - Asset unassignments
- `Borrowed` - Borrowing operations
- `Returned` - Return operations
- `Maintenance` - Maintenance activities
- `Condition Changed` - Condition updates
- `Disposed` - Disposal operations
- `Restored` - Restoration from disposal
- `Archived` - Archiving operations
- `QR Generated` - QR code generation

## Benefits

1. **Complete Audit Trail**: Every change is logged with who, what, when, and where
2. **Public Access**: Anyone can scan QR code and view asset details
3. **Statistics**: Track usage patterns and asset lifecycle
4. **Borrowing History**: See complete borrowing timeline
5. **Timeline Visualization**: Beautiful chronological display of all changes
6. **Mobile Friendly**: Scanner page works on all devices
7. **No Auth Required**: Public scanner doesn't need login

## Security Note

The scanner page (`scan_asset.php`) is intentionally public to allow anyone with a QR code to view asset information. If you need to restrict access:

1. Add authentication checks at the top of `scan_asset.php`
2. Limit displayed information based on user role
3. Add IP restrictions or access controls

## Troubleshooting

### QR Codes Not Working
1. Verify the base URL in `update_qr_codes.php`
2. Check that `scan_asset.php` is accessible
3. Ensure `asset_history` table exists

### History Not Logging
1. Check database connection
2. Verify `asset_history` table exists
3. Check PHP error logs for exceptions
4. Ensure user ID is valid

### Scanner Page Not Loading
1. Verify file path: `view/public/scan_asset.php`
2. Check file permissions
3. Verify database connection settings
4. Check for PHP errors in browser console

## Future Enhancements

- Add PDF export of asset history
- Email notifications on critical changes
- Advanced filtering and search
- Mobile app for QR scanning
- Real-time updates via WebSocket
- Integration with maintenance scheduling
- Automated end-of-life alerts

## Support

For issues or questions:
1. Check PHP error logs
2. Verify database tables exist
3. Review this documentation
4. Check file permissions

---

**Version**: 1.0  
**Last Updated**: December 3, 2025  
**Compatible With**: QCU AMS v1.0+
