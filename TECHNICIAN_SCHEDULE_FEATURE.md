# Technician Schedule-Based Login Feature

## Overview
This feature allows administrators to restrict Technician accounts to login only on specific days of the week. When creating a Technician account, admins can select which days (Monday through Sunday) the technician is allowed to log in.

## Implementation Details

### 1. Database Changes
**File:** `migrate_technician_schedule.sql`

- Added `allowed_login_days` column to the `users` table
- Stores comma-separated list of allowed days (e.g., "Monday,Wednesday,Friday")
- Existing technicians are set to allow all days by default

**To apply the migration:**
```sql
-- Run this SQL in your database
SOURCE migrate_technician_schedule.sql;
```

### 2. Admin User Creation Form
**File:** `view/Administrator/users.php`

**Changes:**
- Added schedule selection section that appears only when "Technician" role is selected
- Displays 7 checkboxes for each day of the week
- Validates that at least one day is selected before submission
- Schedule data is sent as `allowed_days[]` array parameter

**JavaScript Functions Added:**
- `toggleScheduleSection()` - Shows/hides schedule section based on role
- `validateSchedule()` - Ensures at least one day is selected for Technicians

### 3. User Creation Backend
**File:** `view/Administrator/users.php` (PHP section)

**Changes:**
- Processes `allowed_days[]` POST parameter
- Converts array to comma-separated string
- Stores in `allowed_login_days` column when creating Technician accounts
- Non-technician roles have NULL value (no restrictions)

### 4. Login Validation
**File:** `controller/login_controller.php`

**Changes:**
- After successful authentication, checks if user is a Technician
- Gets current day name using `date('l')` (returns "Monday", "Tuesday", etc.)
- Compares current day against allowed days list
- Blocks login with error message if current day is not allowed
- Error message shows which days the technician is allowed to work

**Example Error Message:**
```
"You are not scheduled to work today. Your allowed days are: Monday, Wednesday, Friday"
```

### 5. User Model
**File:** `model/User.php`

- No changes needed
- The `authenticate()` method already returns all user fields including `allowed_login_days`

## Usage Instructions

### For Administrators:

1. **Creating a Technician Account:**
   - Go to Administrator Panel → User Management
   - Click "Add User" button
   - Fill in user details
   - Select "Technician" as the role
   - The "Allowed Login Days" section will appear
   - Check the days when this technician should be able to log in
   - At least one day must be selected
   - Click "Create User"

2. **Schedule Examples:**
   - **Weekdays only:** Check Monday, Tuesday, Wednesday, Thursday, Friday
   - **Weekends only:** Check Saturday, Sunday
   - **Specific days:** Check Monday, Wednesday, Friday
   - **All days:** Check all 7 days

### For Technicians:

1. **Logging In:**
   - Technicians can only log in on their assigned days
   - If they try to log in on a non-assigned day, they will see an error message
   - The error message will show which days they are allowed to work

2. **Schedule Restrictions:**
   - Schedule is checked AFTER password verification
   - Failed login attempts are NOT counted if blocked by schedule
   - Technicians cannot see or modify their own schedule

## Technical Notes

### Day Name Format
- Uses PHP's `date('l')` function which returns full day names
- Format: "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday", "Sunday"
- Case-sensitive matching

### Database Storage
- Stored as VARCHAR(255) to accommodate all day names
- Format: Comma-separated without spaces (e.g., "Monday,Wednesday,Friday")
- NULL value means no restrictions (allows all days)

### Security Considerations
- Schedule check happens after authentication
- Only applies to Technician role
- Other roles (Admin, Lab Staff, Student, Faculty) are not affected
- Schedule cannot be bypassed through session manipulation

### Future Enhancements (Optional)
- Add time-based restrictions (e.g., 8 AM - 5 PM)
- Allow editing schedules for existing technicians
- Add schedule history/audit log
- Display technician schedule in their profile
- Add holiday/exception handling

## Testing Checklist

- [ ] Database migration runs successfully
- [ ] Schedule section appears when Technician role is selected
- [ ] Schedule section hides for other roles
- [ ] Validation prevents submission without selecting days
- [ ] Technician account is created with correct schedule
- [ ] Technician can log in on allowed days
- [ ] Technician is blocked on non-allowed days
- [ ] Error message displays correct allowed days
- [ ] Other roles are not affected by schedule restrictions
- [ ] Existing technicians can still log in (all days allowed by default)

## Troubleshooting

**Issue:** Schedule section doesn't appear
- **Solution:** Clear browser cache and refresh page

**Issue:** Technician can't log in on allowed day
- **Solution:** Check server timezone matches expected timezone

**Issue:** Database error when creating user
- **Solution:** Ensure migration SQL has been run

**Issue:** All technicians blocked from logging in
- **Solution:** Check that `allowed_login_days` column exists and has data
