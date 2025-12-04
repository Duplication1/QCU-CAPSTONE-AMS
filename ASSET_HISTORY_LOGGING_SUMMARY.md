# Asset History Logging Implementation Summary

## Overview
All asset changes including room/building changes, status updates, condition changes, and other modifications are now automatically logged to the `asset_history` table through the Asset model and AssetBorrowing model.

## Asset History Model
**Location:** `model/AssetHistory.php`

The AssetHistory class provides comprehensive tracking of all changes made to assets throughout their lifecycle.

### Database Table Structure
```sql
CREATE TABLE `asset_history` (
    `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `asset_id` int(10) UNSIGNED NOT NULL,
    `action_type` varchar(50) NOT NULL,
    `field_changed` varchar(100) DEFAULT NULL,
    `old_value` text DEFAULT NULL,
    `new_value` text DEFAULT NULL,
    `description` text DEFAULT NULL,
    `performed_by` int(10) UNSIGNED DEFAULT NULL,
    `performed_by_name` varchar(255) DEFAULT NULL,
    `ip_address` varchar(45) DEFAULT NULL,
    `user_agent` text DEFAULT NULL,
    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
    PRIMARY KEY (`id`),
    KEY `asset_id` (`asset_id`),
    KEY `action_type` (`action_type`),
    KEY `performed_by` (`performed_by`),
    KEY `created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

## Automatic Logging in Asset Model

### 1. Asset Creation (`create()` method)
**Location:** `model/Asset.php`

When a new asset is created, the following is logged:
- Action: "Created"
- Description: Includes asset tag, name, and room/building info
- Performed by: The user who created the asset

```php
// Example log entry
Action: Created
Description: Asset created: MON-001 - Samsung Monitor in Room 101 (Building A)
Performed by: John Doe
```

### 2. Asset Updates (`update()` method)
**Location:** `model/Asset.php`

The update method automatically detects and logs changes to:

#### **Room/Building Changes** (Priority tracking)
- **Action:** "Room Changed"
- **Tracks:** room_id changes
- **Includes:** Old room name → New room name (with building info)
- **Example:** "Room changed from Room 101 (Building A) to Room 205 (Building B)"

#### **Status Changes**
- **Action:** "Status Changed"
- **Tracks:** status field
- **Example:** "Status changed from Available to In Use"

#### **Condition Changes**
- **Action:** "Condition Changed"
- **Tracks:** condition field
- **Example:** "Condition changed from Good to Fair"

#### **Location Changes**
- **Action:** "Location Changed"
- **Tracks:** location field (specific location within room)
- **Example:** "Location changed from Desk 1 to Desk 5"

#### **Other Field Changes**
Tracks changes to:
- Asset Name
- Asset Type
- Brand
- Model
- Serial Number
- Terminal Number

```php
// Example log entries from a single update
Action: Room Changed
Field: room_id
Old Value: Room 101 (Building A)
New Value: Room 205 (Building B)
Description: Room changed from Room 101 (Building A) to Room 205 (Building B)

Action: Status Changed
Field: status
Old Value: Available
New Value: In Use
Description: Status changed from Available to In Use

Action: Condition Changed
Field: condition
Old Value: Good
New Value: Fair
Description: Condition changed from Good to Fair
```

### 3. Asset Assignment (`assignToUser()` method)
**Location:** `model/Asset.php`

Logs when an asset is assigned to a user:
- **Action:** "Assigned"
- **Field:** assigned_to
- **New Value:** User's full name
- **Description:** "Asset assigned to [User Name]"

### 4. Asset Unassignment (`unassignAsset()` method)
**Location:** `model/Asset.php`

Logs when an asset is unassigned:
- **Action:** "Unassigned"
- **Field:** assigned_to
- **Old Value:** Previous assignee's name
- **Description:** "Asset unassigned from [User Name]"

## Automatic Logging in AssetBorrowing Model

### 1. Borrowing Approval (`approve()` method)
**Location:** `model/AssetBorrowing.php`

When a borrowing request is approved:
- **Action:** "Borrowed"
- **Field:** status
- **Old Value:** Available
- **New Value:** In Use
- **Description:** "Asset borrowed by [Borrower Name]"
- **Performed by:** The staff member who approved

### 2. Asset Return (`returnAsset()` method)
**Location:** `model/AssetBorrowing.php`

When an asset is returned:
- **Action:** "Returned"
- **Field:** status
- **Old Value:** In Use
- **New Value:** Available
- **Description:** "Asset returned by [Borrower Name] (Condition: [condition])"
- **Performed by:** The staff member who processed the return

## AssetHistoryHelper Class
**Location:** `controller/AssetHistoryHelper.php`

Provides convenient methods for logging specific actions:

### Available Methods

```php
// Asset creation
logAssetCreated($asset_id, $asset_tag, $asset_name, $created_by)

// Status change
logStatusChange($asset_id, $old_status, $new_status, $performed_by)

// Condition change
logConditionChange($asset_id, $old_condition, $new_condition, $performed_by)

// Location change
logLocationChange($asset_id, $old_location, $new_location, $performed_by)

// Room change (NEW)
logRoomChange($asset_id, $old_room, $new_room, $performed_by)

// Building change (NEW)
logBuildingChange($asset_id, $old_building, $new_building, $performed_by)

// Assignment
logAssignment($asset_id, $assigned_to_name, $performed_by)

// Unassignment
logUnassignment($asset_id, $previous_assignee, $performed_by)

// Borrowing
logBorrowing($asset_id, $borrower_name, $performed_by)

// Return
logReturn($asset_id, $borrower_name, $performed_by)

// Maintenance
logMaintenance($asset_id, $maintenance_details, $performed_by)

// Disposal
logDisposal($asset_id, $reason, $performed_by)

// Restoration
logRestoration($asset_id, $performed_by)

// Archiving
logArchiving($asset_id, $performed_by)

// QR Generation
logQRGeneration($asset_id, $performed_by)

// General update
logUpdate($asset_id, $field_changed, $old_value, $new_value, $performed_by)
```

## Action Types in Asset History

| Action Type | When Logged | Description |
|-------------|-------------|-------------|
| Created | Asset creation | New asset added to system |
| Room Changed | Room/building change | Asset moved to different room/building |
| Building Changed | Building change | Asset moved to different building |
| Location Changed | Specific location change | Asset moved within same room |
| Status Changed | Status update | Available, In Use, Maintenance, etc. |
| Condition Changed | Condition update | Good, Fair, Poor, etc. |
| Assigned | User assignment | Asset assigned to specific user |
| Unassigned | Assignment removed | Asset unassigned from user |
| Borrowed | Borrowing approved | Asset borrowed by student/faculty |
| Returned | Asset returned | Asset returned from borrowing |
| Updated | General field changes | Name, type, brand, model, etc. |
| Maintenance | Maintenance performed | Service or repair work |
| Disposed | Asset disposed | Permanent removal |
| Restored | Restoration | Brought back from disposal/archive |
| Archived | Archiving | Moved to archive |
| QR Generated | QR code created | QR code generated for asset |

## Viewing Asset History

### Public Scanner View
**URL:** `/view/public/scan_asset.php`
- Shows complete history when scanning asset QR code
- Displays timeline of all changes
- Shows statistics (total changes, unique users, date ranges)

### Laboratory Staff Views
Asset history can be viewed in:
- Asset details modal in `allassets.php`
- PC unit details in `addpc.php`
- Room assets view in `roomassets.php`

## Example Usage

### Automatic Logging (Built-in)
```php
// When updating an asset, history is automatically logged
$asset = new Asset();
$asset->id = 123;
$asset->room_id = 5;  // Changed from room 3 to room 5
$asset->status = 'In Use';  // Changed from 'Available'
$asset->updated_by = $_SESSION['user_id'];
$asset->update();  // Automatically logs room change and status change
```

### Manual Logging (Using Helper)
```php
require_once '../../controller/AssetHistoryHelper.php';
$helper = AssetHistoryHelper::getInstance();

// Log room change
$helper->logRoomChange(
    $asset_id, 
    'Room 101 (Building A)', 
    'Room 205 (Building B)', 
    $_SESSION['user_id']
);

// Log maintenance
$helper->logMaintenance(
    $asset_id, 
    'Replaced keyboard and cleaned monitor', 
    $_SESSION['user_id']
);
```

## Database Queries

### Get Asset History
```php
$assetHistory = new AssetHistory();
$history = $assetHistory->getAssetHistory($asset_id, 50);  // Last 50 entries
```

### Get Recent Changes Across All Assets
```php
$assetHistory = new AssetHistory();
$recentHistory = $assetHistory->getRecentHistory(100);  // Last 100 changes
```

### Get History by Action Type
```php
$assetHistory = new AssetHistory();
$roomChanges = $assetHistory->getHistoryByAction('Room Changed', 50);
```

### Get Asset Statistics
```php
$assetHistory = new AssetHistory();
$stats = $assetHistory->getAssetStats($asset_id);
// Returns: total_changes, unique_actions, first_recorded, last_activity, unique_users
```

## SQL Queries for Analysis

### Find all room changes
```sql
SELECT 
    ah.created_at,
    ah.asset_id,
    a.asset_tag,
    a.asset_name,
    ah.old_value as old_room,
    ah.new_value as new_room,
    ah.performed_by_name
FROM asset_history ah
JOIN assets a ON ah.asset_id = a.id
WHERE ah.action_type = 'Room Changed'
ORDER BY ah.created_at DESC;
```

### Find assets with most changes
```sql
SELECT 
    a.asset_tag,
    a.asset_name,
    COUNT(*) as total_changes,
    COUNT(DISTINCT ah.action_type) as unique_actions
FROM asset_history ah
JOIN assets a ON ah.asset_id = a.id
GROUP BY a.id
ORDER BY total_changes DESC
LIMIT 10;
```

### Track asset movement over time
```sql
SELECT 
    ah.created_at,
    ah.old_value as from_location,
    ah.new_value as to_location,
    ah.performed_by_name
FROM asset_history ah
WHERE ah.asset_id = ? 
  AND ah.action_type IN ('Room Changed', 'Location Changed', 'Building Changed')
ORDER BY ah.created_at ASC;
```

## Benefits

### 1. Complete Audit Trail
- Every change is recorded with timestamp
- User information captured automatically
- IP address and user agent logged

### 2. Room/Building Tracking
- Know when assets moved between rooms
- Track building transfers
- Historical location data for asset management

### 3. Accountability
- See who made each change
- When changes occurred
- What was changed

### 4. Analysis
- Identify frequently moved assets
- Track asset condition over time
- Monitor borrowing patterns
- Maintenance history

### 5. Reporting
- Generate movement reports
- Audit compliance
- Asset lifecycle tracking

## Error Handling

All asset history logging includes error handling:

```php
try {
    require_once __DIR__ . '/AssetHistory.php';
    $assetHistory = new AssetHistory();
    $assetHistory->logHistory(...);
} catch (Exception $e) {
    error_log("Failed to log asset history: " . $e->getMessage());
    // Main operation continues even if logging fails
}
```

This ensures that logging failures don't break asset operations.

## Testing Checklist

To verify asset history logging:

1. ✅ Create new asset → Check for "Created" entry
2. ✅ Move asset to different room → Check for "Room Changed" entry with building info
3. ✅ Change asset status → Check for "Status Changed" entry
4. ✅ Change asset condition → Check for "Condition Changed" entry
5. ✅ Update asset location → Check for "Location Changed" entry
6. ✅ Assign asset to user → Check for "Assigned" entry
7. ✅ Unassign asset → Check for "Unassigned" entry
8. ✅ Approve borrowing → Check for "Borrowed" entry
9. ✅ Return asset → Check for "Returned" entry with condition
10. ✅ Update other fields → Check for "Updated" entries

## Maintenance

### Database Maintenance
```sql
-- Check table size
SELECT 
    COUNT(*) as total_records,
    MIN(created_at) as oldest_record,
    MAX(created_at) as newest_record
FROM asset_history;

-- Archive old records (optional)
-- Consider moving records older than 2 years to archive table
```

### Performance Considerations
- Indexes are created on key fields (asset_id, action_type, created_at)
- Consider archiving very old records if table grows too large
- Regular OPTIMIZE TABLE if needed

---

**Document Version:** 1.0  
**Last Updated:** December 4, 2025  
**Maintained By:** Development Team
