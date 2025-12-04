# Asset History Tracking - Quick Reference

## What Gets Logged Automatically

### ✅ Asset Creation
- Logged when: New asset is created
- Action: "Created"
- Includes: Asset tag, name, room, building

### ✅ Room/Building Changes
- Logged when: Asset moved to different room
- Action: "Room Changed"
- Tracks: Old room → New room (with building names)
- **Example:** "Room 101 (Building A) → Room 205 (Building B)"

### ✅ Status Changes
- Logged when: Asset status updated
- Action: "Status Changed"
- Tracks: Available, In Use, Under Maintenance, etc.

### ✅ Condition Changes
- Logged when: Asset condition updated
- Action: "Condition Changed"
- Tracks: Good, Fair, Poor, etc.

### ✅ Location Changes
- Logged when: Specific location within room changed
- Action: "Location Changed"
- Tracks: Desk 1 → Desk 5, etc.

### ✅ Field Updates
- Logged when: Asset name, type, brand, model, serial number, or terminal number changes
- Action: "Updated"
- Tracks: Old value → New value for each field

### ✅ Asset Assignment
- Logged when: Asset assigned to a user
- Action: "Assigned"
- Includes: User's full name

### ✅ Asset Unassignment
- Logged when: Asset unassigned from user
- Action: "Unassigned"
- Includes: Previous assignee name

### ✅ Borrowing
- Logged when: Borrowing request approved
- Action: "Borrowed"
- Includes: Borrower's name

### ✅ Asset Return
- Logged when: Borrowed asset returned
- Action: "Returned"
- Includes: Borrower's name and returned condition

## How It Works

### Automatic Tracking
The Asset model (`model/Asset.php`) automatically logs changes when:

```php
// Creating an asset
$asset = new Asset();
$asset->asset_tag = 'LAP-001';
$asset->asset_name = 'Dell Laptop';
$asset->room_id = 5;
$asset->created_by = $_SESSION['user_id'];
$asset->create();  // ← Automatically logs creation

// Updating an asset
$asset = new Asset();
$asset->id = 123;
$asset->room_id = 10;  // Room change
$asset->status = 'In Use';  // Status change
$asset->updated_by = $_SESSION['user_id'];
$asset->update();  // ← Automatically logs all changes
```

### What Gets Detected
When you call `$asset->update()`, the system:
1. Fetches old values from database
2. Compares with new values
3. Logs each difference separately
4. Includes room/building names (not just IDs)

## View Asset History

### For Laboratory Staff
- **Asset Details Modal** in `allassets.php`
- **PC Unit Details** in `addpc.php`
- **Room Assets** in `roomassets.php`

### For Public (QR Scanner)
- **Scan Asset** at `/view/public/scan_asset.php`
- Shows complete timeline
- Displays statistics

## Manual Logging (Optional)

If you need to log special events:

```php
require_once '../../controller/AssetHistoryHelper.php';
$helper = AssetHistoryHelper::getInstance();

// Log maintenance
$helper->logMaintenance(
    $asset_id, 
    'Replaced RAM and cleaned cooling fan', 
    $_SESSION['user_id']
);

// Log disposal
$helper->logDisposal(
    $asset_id, 
    'Asset no longer functional, beyond repair', 
    $_SESSION['user_id']
);

// Log room change (if not using Asset->update())
$helper->logRoomChange(
    $asset_id,
    'Room 101 (Building A)',
    'Room 205 (Building B)',
    $_SESSION['user_id']
);
```

## Database Access

### Get Asset History
```php
$assetHistory = new AssetHistory();

// Get last 50 changes for an asset
$history = $assetHistory->getAssetHistory($asset_id, 50);

// Get recent changes across all assets
$recentHistory = $assetHistory->getRecentHistory(100);

// Get specific action type
$roomChanges = $assetHistory->getHistoryByAction('Room Changed', 50);

// Get statistics
$stats = $assetHistory->getAssetStats($asset_id);
```

## Key Features

### 1. **Comprehensive Room Tracking**
- Shows full room names with building info
- Example: "Room 101 (Computer Lab Building)"
- Not just ID numbers

### 2. **Multiple Changes in One Update**
If one update changes room AND status AND condition:
- 3 separate history entries are created
- Each with specific details
- All with same timestamp

### 3. **User Accountability**
Every entry includes:
- Who made the change (user ID and name)
- When it happened (timestamp)
- What changed (old → new values)
- IP address and user agent

### 4. **Non-Breaking**
- Logging failures don't break operations
- Errors are logged to PHP error log
- Main functionality continues

## Common Scenarios

### Scenario 1: Moving Asset Between Rooms
```php
$asset->room_id = 15;  // Change room
$asset->updated_by = $_SESSION['user_id'];
$asset->update();
```
**Result:** Creates history entry with:
- Action: "Room Changed"
- Old: "Room 101 (Building A)"
- New: "Room 205 (Building B)"

### Scenario 2: Status + Condition Update
```php
$asset->status = 'Under Maintenance';
$asset->condition = 'Fair';
$asset->updated_by = $_SESSION['user_id'];
$asset->update();
```
**Result:** Creates 2 history entries:
1. Status Changed: Available → Under Maintenance
2. Condition Changed: Good → Fair

### Scenario 3: Approving Borrowing
```php
$borrowing = new AssetBorrowing();
$borrowing->approve($borrowing_id, $_SESSION['user_id']);
```
**Result:** Creates history entry:
- Action: "Borrowed"
- Description: "Asset borrowed by [Student Name]"

## SQL Queries

### Track Asset Movement
```sql
SELECT 
    created_at,
    old_value as from_room,
    new_value as to_room,
    performed_by_name
FROM asset_history
WHERE asset_id = 123 
  AND action_type = 'Room Changed'
ORDER BY created_at DESC;
```

### Most Changed Assets
```sql
SELECT 
    a.asset_tag,
    a.asset_name,
    COUNT(*) as changes
FROM asset_history ah
JOIN assets a ON ah.asset_id = a.id
GROUP BY a.id
ORDER BY changes DESC
LIMIT 10;
```

## Troubleshooting

### Issue: History not being logged
**Check:**
1. Is the update actually succeeding?
2. Are values actually changing?
3. Check PHP error log for exceptions

### Issue: Room name shows as "None"
**Reason:** Room might be deleted or ID is null
**Solution:** Normal behavior, shows "None" for null rooms

### Issue: Multiple entries for same change
**Reason:** Update called multiple times
**Solution:** Only call `$asset->update()` once per change

## Best Practices

1. **Always set updated_by**
   ```php
   $asset->updated_by = $_SESSION['user_id'];
   ```

2. **Let automatic logging work**
   - Don't manually log what's already automatic
   - Use `$asset->update()` for all changes

3. **Use manual logging for special events**
   - Maintenance work
   - Disposal reasons
   - Special notes

4. **Review history regularly**
   - Check for unusual patterns
   - Verify asset movements
   - Track condition changes

---

**Quick Reference Version:** 1.0  
**Last Updated:** December 4, 2025
