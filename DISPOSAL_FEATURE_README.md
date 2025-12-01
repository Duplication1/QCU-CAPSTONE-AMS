# Asset Disposal Feature Implementation

## Overview
Added a comprehensive asset disposal management feature for Laboratory Staff to track and dispose of assets based on end-of-life dates and condition.

## Changes Made

### 1. Database Schema Update
**File:** `database/add_end_of_life_column.sql`

A new column `end_of_life` has been added to the `assets` table to track when an asset is expected to reach the end of its useful life.

**To apply this migration, run:**
```sql
-- In phpMyAdmin or MySQL command line:
ALTER TABLE `assets` 
ADD COLUMN `end_of_life` DATE DEFAULT NULL COMMENT 'Expected end of life date for the asset' 
AFTER `warranty_expiry`;
```

### 2. New Pages Created

#### Disposal Management Page
**File:** `view/LaboratoryStaff/disposal.php`

Features:
- Dashboard showing assets eligible for disposal
- Statistics cards for:
  - Total eligible assets
  - Assets past end-of-life date
  - Assets in poor condition
  - Non-functional assets
- Interactive DataTable with:
  - Asset details
  - Location information
  - Condition badges
  - Disposal reason indicators
  - Action buttons (view details, mark for disposal)
- Export functionality to CSV
- Modal dialogs for asset details and disposal confirmation

**Disposal Eligibility Criteria:**
An asset is eligible for disposal if:
1. End-of-life date has passed (today > end_of_life), OR
2. Condition is "Poor" or "Non-Functional"

### 3. New Controllers Created

#### Dispose Asset Controller
**File:** `controller/dispose_asset.php`

- Handles POST requests to mark assets as disposed
- Updates asset status to "Disposed"
- Appends disposal notes to asset record
- Logs disposal action in activity_logs
- Uses database transactions for data integrity

#### Export Disposal List Controller
**File:** `controller/export_disposal_list.php`

- Exports disposal-eligible assets as CSV
- Includes comprehensive asset information
- Logs export action

### 4. Sidebar Navigation Update
**File:** `view/components/sidebar.php`

Added "Disposal" submenu item under Laboratory Staff's Asset Registry section:
- All Assets
- Buildings
- Stand By Assets
- **Disposal** ← NEW

### 5. Tailwind CSS CDN Removed
**File:** `view/components/layout_header.php`

Removed the Tailwind CSS CDN script tag. The application now uses the locally compiled Tailwind CSS from `assets/css/output.css`.

**To use local Tailwind:**
```bash
# Development mode (watches for changes)
npm run dev

# Production build (minified)
npm run build:css
```

## How to Use the Disposal Feature

### For Laboratory Staff:

1. **Navigate to Disposal Page:**
   - Go to Asset Registry → Disposal in the sidebar

2. **View Eligible Assets:**
   - See all assets eligible for disposal based on end-of-life or condition
   - View statistics at the top of the page

3. **Mark Asset for Disposal:**
   - Click the trash icon in the Actions column
   - Add optional disposal notes
   - Confirm the disposal action

4. **Export Disposal List:**
   - Click the "Export List" button to download a CSV file
   - Share with stakeholders for disposal approval

### Setting End-of-Life Dates:

When creating or editing assets, set the `end_of_life` date field to specify when the asset should be disposed. Assets will automatically appear in the disposal list once this date passes.

## Database Migration Instructions

### Option 1: Using phpMyAdmin
1. Open phpMyAdmin
2. Select the `ams_database` database
3. Go to the SQL tab
4. Copy and paste the content from `database/add_end_of_life_column.sql`
5. Click "Go" to execute

### Option 2: Using MySQL Command Line
```bash
mysql -u root -p ams_database < database/add_end_of_life_column.sql
```

### Option 3: Manual Execution
Run this SQL command:
```sql
ALTER TABLE `assets` 
ADD COLUMN `end_of_life` DATE DEFAULT NULL COMMENT 'Expected end of life date for the asset' 
AFTER `warranty_expiry`;
```

## Testing the Feature

1. **Add test data:**
   ```sql
   -- Set some assets with past end-of-life dates
   UPDATE assets 
   SET end_of_life = DATE_SUB(CURDATE(), INTERVAL 30 DAY)
   WHERE id IN (376, 377, 378);
   
   -- Set some assets with poor condition
   UPDATE assets 
   SET condition = 'Poor'
   WHERE id IN (379, 380);
   
   -- Set some assets with non-functional condition
   UPDATE assets 
   SET condition = 'Non-Functional'
   WHERE id = 381;
   ```

2. **View disposal page:**
   - Login as Laboratory Staff
   - Navigate to Asset Registry → Disposal
   - You should see the test assets listed

3. **Test disposal action:**
   - Click trash icon on an asset
   - Add disposal notes
   - Confirm disposal
   - Verify asset status changes to "Disposed"

## Technical Details

### Database Schema
- **New Column:** `end_of_life` (DATE, nullable)
- **Location:** After `warranty_expiry` in `assets` table
- **Purpose:** Track expected end-of-life date for assets

### Disposal Logic
```php
WHERE 
    status NOT IN ('Disposed', 'Archive', 'Archived')
    AND (
        (end_of_life IS NOT NULL AND end_of_life < CURRENT_DATE)
        OR condition IN ('Poor', 'Non-Functional')
    )
```

### Activity Logging
All disposal actions are logged in the `activity_logs` table with:
- User who performed the action
- Asset ID and details
- Disposal notes
- IP address and user agent
- Timestamp

## Security Considerations

- Only Laboratory Staff can access the disposal feature
- All actions are logged for audit trail
- Database transactions ensure data integrity
- Input validation on all user inputs
- SQL injection prevention using prepared statements

## Future Enhancements

Potential improvements:
1. Bulk disposal functionality
2. Disposal approval workflow
3. Email notifications for assets nearing end-of-life
4. Disposal schedule planning
5. Asset lifecycle analytics
6. Integration with external disposal/recycling services
7. Photo documentation for disposed assets
