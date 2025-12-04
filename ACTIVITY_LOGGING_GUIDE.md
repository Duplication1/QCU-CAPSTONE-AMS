# Activity Logging Quick Reference Guide

## Quick Start

### 1. Include the ActivityLog Model
```php
require_once '../model/ActivityLog.php';
require_once '../model/Database.php';
```

### 2. Log an Activity
```php
ActivityLog::record(
    $_SESSION['user_id'],    // Who did it
    'create',                // What action (create, update, delete, etc.)
    'asset',                 // What type (asset, ticket, user, etc.)
    $asset_id,              // Which specific item (ID)
    'Created new laptop'    // Description
);
```

## Action Types Reference

| Action | When to Use | Example |
|--------|-------------|---------|
| `create` | Creating new records | New asset, ticket, request |
| `update` | Modifying existing records | Update asset, change status |
| `delete` | Removing records | Delete signature, cancel request |
| `archive` | Moving to archive | Archive asset |
| `restore` | Restoring from archive | Restore asset |
| `dispose` | Permanent disposal | Dispose asset |
| `import` | Bulk data import | Import CSV |
| `export` | Data export | Export to CSV, generate report |
| `approve` | Approval actions | Approve borrowing |
| `assign` | Assigning resources | Assign ticket to technician |
| `upload` | File uploads | Upload signature |
| `login` | User authentication | User login |
| `logout` | User logout | User logout |

## Entity Types Reference

| Entity | When to Use | Example |
|--------|-------------|---------|
| `asset` | Asset operations | Create, update, archive asset |
| `pc_unit` | PC unit operations | Restore PC unit |
| `ticket` | Issue/ticket operations | Submit, update ticket |
| `borrowing` | Borrowing operations | Request, approve, return |
| `user` | User account operations | Change password |
| `signature` | E-signature operations | Upload, delete signature |
| `report` | Report operations | Generate report |
| `scanner` | Scanner operations | QR code scans |

## Common Patterns

### Pattern 1: After Successful Database Operation
```php
if ($stmt->execute()) {
    $insertId = $stmt->insert_id;
    
    // Log the successful operation
    try {
        require_once '../model/ActivityLog.php';
        ActivityLog::record(
            $_SESSION['user_id'],
            'create',
            'ticket',
            $insertId,
            "Submitted hardware ticket: {$title}"
        );
    } catch (Exception $e) {
        error_log('Activity log failed: ' . $e->getMessage());
    }
}
```

### Pattern 2: In Model Methods
```php
public function approve($id, $approved_by) {
    // Perform the operation
    if ($stmt->execute([$approved_by, $id])) {
        // Log the activity
        if (session_status() === PHP_SESSION_ACTIVE && isset($_SESSION['user_id'])) {
            try {
                require_once __DIR__ . '/ActivityLog.php';
                ActivityLog::record(
                    $approved_by,
                    'approve',
                    'borrowing',
                    $id,
                    'Approved borrowing request'
                );
            } catch (Exception $e) {
                error_log('Activity log failed: ' . $e->getMessage());
            }
        }
        return true;
    }
    return false;
}
```

### Pattern 3: Bulk Operations with Count
```php
// After importing multiple assets
if ($successCount > 0) {
    require_once '../model/ActivityLog.php';
    ActivityLog::record(
        $_SESSION['user_id'],
        'import',
        'asset',
        null,
        "Imported {$successCount} asset(s) from CSV file"
    );
}
```

### Pattern 4: Export Operations
```php
// Before exporting data
try {
    $count = count($assets);
    require_once '../model/ActivityLog.php';
    ActivityLog::record(
        $_SESSION['user_id'],
        'export',
        'asset',
        null,
        "Exported assets to CSV ({$count} records)"
    );
} catch (Exception $e) {
    error_log('Activity log failed: ' . $e->getMessage());
}
```

## Best Practices

### ✅ DO

1. **Always wrap in try-catch**
   ```php
   try {
       ActivityLog::record(...);
   } catch (Exception $e) {
       error_log('Log error: ' . $e->getMessage());
   }
   ```

2. **Use descriptive messages**
   ```php
   // Good
   "Updated asset: Monitor Samsung S24F350 (#MON-001)"
   
   // Better than
   "Updated asset"
   ```

3. **Include relevant details**
   ```php
   // Good
   "Approved borrowing request for Laptop HP-001"
   
   // Include status changes
   "Changed ticket #123 status to Resolved"
   ```

4. **Log after success**
   ```php
   if ($operation_successful) {
       ActivityLog::record(...);  // Only log if operation succeeded
   }
   ```

### ❌ DON'T

1. **Don't fail the main operation if logging fails**
   ```php
   // Bad
   ActivityLog::record(...);  // Will throw exception if fails
   
   // Good
   try {
       ActivityLog::record(...);
   } catch (Exception $e) {
       // Log error but continue
   }
   ```

2. **Don't log sensitive data**
   ```php
   // Bad
   "User changed password to: new_password123"
   
   // Good
   "User changed password"
   ```

3. **Don't log read-only operations**
   ```php
   // Don't log these:
   // - Viewing a list
   // - Reading details
   // - Searching
   // - Filtering
   ```

4. **Don't forget user context**
   ```php
   // Bad
   ActivityLog::record(null, 'create', 'asset', ...);
   
   // Good
   ActivityLog::record($_SESSION['user_id'], 'create', 'asset', ...);
   ```

## Checking Activity Logs

### In Database
```sql
-- Recent activities by a user
SELECT * FROM activity_logs 
WHERE user_id = 123 
ORDER BY created_at DESC 
LIMIT 50;

-- All asset-related activities
SELECT * FROM activity_logs 
WHERE entity_type = 'asset' 
ORDER BY created_at DESC;

-- Activities in date range
SELECT * FROM activity_logs 
WHERE DATE(created_at) BETWEEN '2025-12-01' AND '2025-12-31';

-- Count by action type
SELECT action, COUNT(*) as count 
FROM activity_logs 
GROUP BY action 
ORDER BY count DESC;
```

### In Application
- Laboratory Staff: `/view/LaboratoryStaff/activity_logs.php`
- Technician: `/view/Technician/activity_logs.php`
- Administrator: `/view/Administrator/logs.php`

## Testing Your Implementation

### 1. Visual Check
After implementing logging:
```php
// Add your logging code
ActivityLog::record($_SESSION['user_id'], 'create', 'asset', $id, 'Test asset');

// Then check the activity_logs table
SELECT * FROM activity_logs ORDER BY created_at DESC LIMIT 1;
```

### 2. Test Checklist
- [ ] Logging happens after successful operation
- [ ] User ID is correctly captured
- [ ] Action type matches the operation
- [ ] Entity type is correct
- [ ] Entity ID is captured (when available)
- [ ] Description is clear and informative
- [ ] Errors are logged but don't break the operation

## Troubleshooting

### Issue: Logs not appearing
**Check:**
1. Is the operation actually succeeding?
2. Is `$_SESSION['user_id']` set?
3. Are there any PHP errors in error_log?
4. Is the database connection working?

### Issue: Exception thrown
**Solution:**
```php
// Always wrap in try-catch
try {
    require_once '../model/ActivityLog.php';
    ActivityLog::record(...);
} catch (Exception $e) {
    error_log('Activity log error: ' . $e->getMessage());
}
```

### Issue: Wrong user_id logged
**Check:**
```php
// Verify session is started
if (!isset($_SESSION['user_id'])) {
    error_log('No user_id in session');
}

// Use correct user_id
ActivityLog::record($_SESSION['user_id'], ...);
```

## Examples by Feature

### Asset Management
```php
// Create asset
ActivityLog::record($_SESSION['user_id'], 'create', 'asset', $id, "Created asset: {$name}");

// Update asset
ActivityLog::record($_SESSION['user_id'], 'update', 'asset', $id, "Updated asset: {$name}");

// Archive asset
ActivityLog::record($_SESSION['user_id'], 'archive', 'asset', $id, "Archived asset: {$tag}");

// Restore asset
ActivityLog::record($_SESSION['user_id'], 'restore', 'asset', $id, "Restored asset: {$tag}");

// Export assets
ActivityLog::record($_SESSION['user_id'], 'export', 'asset', null, "Exported {$count} assets to CSV");
```

### Ticket Management
```php
// Submit ticket
ActivityLog::record($userId, 'create', 'ticket', $ticketId, "Submitted {$type} ticket: {$title}");

// Update status
ActivityLog::record($_SESSION['user_id'], 'update', 'ticket', $id, "Changed ticket status to {$status}");

// Assign ticket
ActivityLog::record($_SESSION['user_id'], 'assign', 'ticket', $id, "Assigned ticket to {$technician}");
```

### Borrowing
```php
// Submit request
ActivityLog::record($_SESSION['user_id'], 'create', 'borrowing', null, "Submitted borrowing request for asset {$id}");

// Approve request
ActivityLog::record($approvedBy, 'approve', 'borrowing', $id, "Approved borrowing request");

// Return asset
ActivityLog::record($_SESSION['user_id'], 'update', 'borrowing', $id, "Marked asset as returned (Condition: {$condition})");

// Cancel request
ActivityLog::record($_SESSION['user_id'], 'update', 'borrowing', $id, "Cancelled borrowing request");
```

### User Actions
```php
// Change password
ActivityLog::record($_SESSION['user_id'], 'update', 'user', $_SESSION['user_id'], "Changed password");

// Upload signature
ActivityLog::record($userId, 'upload', 'signature', $userId, "Uploaded e-signature");

// Delete signature
ActivityLog::record($userId, 'delete', 'signature', $userId, "Deleted e-signature");
```

## Need Help?

1. Check `ACTIVITY_LOGGING_SUMMARY.md` for detailed documentation
2. Review existing implementations in the codebase
3. Look at the ActivityLog model: `model/ActivityLog.php`
4. Test in development environment first

---

**Quick Reference Version:** 1.0  
**Last Updated:** December 4, 2025
