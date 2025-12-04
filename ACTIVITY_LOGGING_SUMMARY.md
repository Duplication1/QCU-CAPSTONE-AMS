# Activity Logging Implementation Summary

## Overview
All critical actions in the QCU-CAPSTONE-AMS system are now properly logged to the `activity_logs` table using the `ActivityLog` model.

## Activity Log Model
**Location:** `model/ActivityLog.php`

The ActivityLog class provides:
- Static method `ActivityLog::record()` for easy logging
- Automatic IP address and user agent capture
- Indexed database table for fast queries
- Support for various action types and entity types

### Method Signature
```php
ActivityLog::record(
    $user_id,        // User performing the action
    $action,         // Action type: create, update, delete, archive, restore, etc.
    $entity_type,    // Entity affected: asset, ticket, borrowing, signature, etc.
    $entity_id,      // ID of affected entity (optional)
    $description     // Human-readable description
)
```

## Implemented Activity Logging

### 1. Asset Management
| Action | File | Description |
|--------|------|-------------|
| Create Asset | `controller/save_asset.php` | Logs when new assets are created |
| Update Asset | `controller/save_asset.php` | Logs asset modifications |
| Archive Asset | `controller/delete_asset.php` | Logs asset archival |
| Restore Asset | `controller/restore_asset.php` | Logs asset restoration from archive |
| Restore Multiple Assets | `controller/restore_assets.php` | Logs bulk asset restoration |
| Import Assets | `controller/import_assets.php` | Logs CSV imports with count |
| Export Assets | `controller/export_assets.php` | Logs CSV exports with count |
| Dispose Asset | `controller/dispose_asset.php` | Logs asset disposal |

### 2. Ticket/Issue Management
| Action | File | Description |
|--------|------|-------------|
| Submit Issue | `controller/submit_issue.php` | Logs general issue ticket creation |
| Submit Hardware Issue | `controller/submit_hardware.php` | Logs hardware-specific tickets |
| Submit Software Issue | `controller/submit_software.php` | Logs software-specific tickets |
| Submit Network Issue | `controller/submit_network.php` | Logs network-specific tickets |
| Update Ticket Status | `controller/technician_update_status.php` | Logs status changes by technicians |
| Assign Ticket | `controller/assign_ticket.php` | Logs ticket assignments |

### 3. Asset Borrowing
| Action | File | Description |
|--------|------|-------------|
| Submit Borrowing Request | `controller/submit_borrowing.php` | Logs borrowing request creation |
| Approve Borrowing | `model/AssetBorrowing.php` (approve method) | Logs approval by lab staff |
| Return Asset | `model/AssetBorrowing.php` (returnAsset method) | Logs asset returns with condition |
| Cancel Borrowing | `model/AssetBorrowing.php` (cancel method) | Logs cancellations |
| Delete Request | `controller/cancel_request.php` | Logs request deletions by users |

### 4. PC Unit Management
| Action | File | Description |
|--------|------|-------------|
| Restore PC Unit | `controller/restore_pc_unit.php` | Logs single PC unit restoration |
| Restore Multiple PC Units | `controller/restore_pc_units.php` | Logs bulk PC unit restoration |

### 5. User Management
| Action | File | Description |
|--------|------|-------------|
| Login | `controller/login_controller.php` | Logs successful logins |
| Logout | `controller/logout_controller.php` | Logs user logouts |
| Change Password | `controller/change_password.php` | Logs password changes |
| Upload Signature | `controller/upload_signature.php` | Logs e-signature uploads (Lab Staff only) |
| Delete Signature | `controller/delete_signature.php` | Logs e-signature deletions |

### 6. Reporting
| Action | File | Description |
|--------|------|-------------|
| Generate Report | `controller/generate_report.php` | Logs report generation with type and date range |
| Preview Report | `controller/preview_report.php` | Logs report previews |
| Export Logs | `controller/export_logs.php` | Logs activity log exports |
| Export Disposal List | `controller/export_disposal_list.php` | Logs disposal list exports |

## Action Types Used

| Action Type | Description | Examples |
|-------------|-------------|----------|
| `login` | User authentication | Login success |
| `logout` | User logout | Session termination |
| `create` | Creating new records | New asset, ticket, borrowing request |
| `update` | Modifying existing records | Asset update, ticket status change, password change |
| `delete` | Removing records | Signature deletion, request cancellation |
| `archive` | Moving to archive | Asset archival |
| `restore` | Restoring from archive | Asset/PC unit restoration |
| `dispose` | Permanent disposal | Asset disposal |
| `import` | Bulk data import | CSV asset import |
| `export` | Data export | CSV export, report generation |
| `assign` | Assigning resources | Ticket assignment |
| `approve` | Approval actions | Borrowing approval |
| `upload` | File uploads | Signature upload |

## Entity Types Used

| Entity Type | Description | Examples |
|-------------|-------------|----------|
| `user` | User account | Password change, login |
| `asset` | Physical assets | Create, update, archive, restore |
| `pc_unit` | PC units | Restore PC unit |
| `ticket` | Issue tickets | Hardware, software, network issues |
| `borrowing` | Asset borrowing | Request, approve, return, cancel |
| `signature` | E-signatures | Upload, delete |
| `report` | Reports | Generate, preview |
| `scanner` | QR scanner logs | Scanner usage |

## Viewing Activity Logs

### Laboratory Staff
- **URL:** `/view/LaboratoryStaff/activity_logs.php`
- **Filters:** Action type, entity type, date range, search
- **Export:** CSV export available
- Shows only Laboratory Staff activities

### Technician
- **URL:** `/view/Technician/activity_logs.php`
- **Filters:** Action type, entity type, date range, search
- Shows only Technician activities

### Administrator
- **URL:** `/view/Administrator/logs.php`
- **Features:** Combined view of activity logs and login history
- **Filters:** Action, user, date
- Shows all user activities

## Database Schema

### activity_logs Table
```sql
CREATE TABLE `activity_logs` (
    `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` int(10) UNSIGNED NOT NULL,
    `action` varchar(50) NOT NULL,
    `entity_type` varchar(50) DEFAULT NULL,
    `entity_id` int(10) UNSIGNED DEFAULT NULL,
    `description` text DEFAULT NULL,
    `ip_address` varchar(45) DEFAULT NULL,
    `user_agent` text DEFAULT NULL,
    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
    PRIMARY KEY (`id`),
    KEY `user_id` (`user_id`),
    KEY `action` (`action`),
    KEY `created_at` (`created_at`),
    KEY `entity_type` (`entity_type`, `entity_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

## Implementation Best Practices

### 1. Always Include User Context
```php
ActivityLog::record(
    $_SESSION['user_id'],  // Always use session user_id
    'create',
    'asset',
    $asset_id,
    'Created asset: Monitor #001'
);
```

### 2. Use Descriptive Messages
```php
// Good
'Approved borrowing request for Laptop HP-001'

// Bad
'Approved'
```

### 3. Handle Exceptions Gracefully
```php
try {
    ActivityLog::record(...);
} catch (Exception $logError) {
    error_log('Failed to log action: ' . $logError->getMessage());
    // Don't fail the main operation if logging fails
}
```

### 4. Log After Success
```php
if ($operation_success) {
    // Log only after confirming the operation succeeded
    ActivityLog::record(...);
}
```

## Coverage Status

### ✅ Fully Logged Actions
- Asset management (create, update, archive, restore, import, export, dispose)
- Ticket management (create, update, assign)
- Borrowing workflow (request, approve, return, cancel)
- User authentication (login, logout, password change)
- E-signature management (upload, delete)
- Report generation (all types)
- PC unit management (restore)

### 📋 Actions Not Requiring Logging
- Read-only operations (viewing lists, details)
- API queries (get_asset_details, get_notifications, etc.)
- File downloads without data export
- Navigation actions

## Testing Checklist

To verify activity logging is working:

1. ✅ Create a new asset → Check activity_logs for 'create' + 'asset'
2. ✅ Update an asset → Check for 'update' + 'asset'
3. ✅ Archive an asset → Check for 'archive' + 'asset'
4. ✅ Restore an asset → Check for 'restore' + 'asset'
5. ✅ Submit a ticket → Check for 'create' + 'ticket'
6. ✅ Update ticket status → Check for 'update' + 'ticket'
7. ✅ Submit borrowing request → Check for 'create' + 'borrowing'
8. ✅ Approve borrowing → Check for 'approve' + 'borrowing'
9. ✅ Return asset → Check for 'update' + 'borrowing'
10. ✅ Change password → Check for 'update' + 'user'
11. ✅ Upload signature → Check for 'upload' + 'signature'
12. ✅ Export assets → Check for 'export' + 'asset'
13. ✅ Generate report → Check for 'export' + 'report'

## Recent Updates (December 4, 2025)

### Files Modified
1. `controller/submit_issue.php` - Added logging for general issue tickets
2. `controller/submit_hardware.php` - Added logging for hardware tickets
3. `controller/submit_software.php` - Added logging for software tickets
4. `controller/submit_network.php` - Added logging for network tickets
5. `controller/submit_borrowing.php` - Added logging for borrowing requests
6. `controller/delete_signature.php` - Added logging for signature deletions
7. `controller/export_assets.php` - Added logging for asset exports
8. `controller/cancel_request.php` - Added logging for request cancellations
9. `model/AssetBorrowing.php` - Added logging for approve, return, and cancel actions

### Changes Made
- Added `require_once` statements for ActivityLog model
- Added `ActivityLog::record()` calls with appropriate parameters
- Included proper error handling with try-catch blocks
- Used descriptive messages for all log entries

## Maintenance Notes

### Adding New Activity Logging

When creating new features that modify data:

1. Include the ActivityLog model:
```php
require_once '../model/ActivityLog.php';
require_once '../model/Database.php';
```

2. Add logging after successful operations:
```php
if ($operation_successful) {
    ActivityLog::record(
        $_SESSION['user_id'],
        'action_type',      // create, update, delete, etc.
        'entity_type',      // asset, ticket, user, etc.
        $entity_id,         // ID of affected record
        'Description'       // What happened
    );
}
```

3. Handle exceptions:
```php
try {
    ActivityLog::record(...);
} catch (Exception $e) {
    error_log('Activity log error: ' . $e->getMessage());
}
```

### Regular Maintenance

1. **Monitor Log Size:** The activity_logs table can grow large over time
2. **Archive Old Logs:** Consider archiving logs older than 1-2 years
3. **Index Optimization:** Ensure indexes are optimized for common queries
4. **Review Logs:** Regularly review for unusual patterns or security concerns

## Security Considerations

1. **Access Control:** Activity logs should only be viewable by authorized staff
2. **Data Retention:** Comply with data retention policies
3. **PII Protection:** Be careful not to log sensitive personal information
4. **Audit Trail:** Logs provide an audit trail for compliance and security

## Support

For questions or issues with activity logging:
- Review this document
- Check the ActivityLog model implementation
- Examine existing logging implementations as examples
- Test in development environment before deploying changes

---

**Document Version:** 1.0  
**Last Updated:** December 4, 2025  
**Maintained By:** Development Team
