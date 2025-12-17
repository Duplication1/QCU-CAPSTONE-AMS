# Password Recovery & Registration System

## Overview
This system adds two major features to the AMS:
1. **User Registration with Admin Approval** - New users can register but need admin approval
2. **Password Recovery with Security Questions** - Users can reset forgotten passwords using security questions

## Database Setup

Run the SQL file to create the necessary tables:
```sql
-- File: database/password_recovery_and_registration.sql
```

This creates:
- Adds security question columns to `users` table
- Creates `registration_requests` table for pending registrations
- Creates `password_reset_tokens` table (for future email-based reset if needed)

## Features

### 1. User Registration (`view/register.php`)
- Users fill out registration form with:
  - ID Number
  - Full Name
  - Email
  - Role (Student, Faculty, Technician, Laboratory Staff)
  - Password
  - Two security questions with answers
- Registration creates a pending request
- Admin must approve before account is created
- Users are notified to wait for approval

### 2. Forgot Password (`view/forgot_password.php`)
Three-step process:
1. **Step 1**: Enter ID Number
2. **Step 2**: Answer security questions
3. **Step 3**: Set new password

### 3. Admin Registration Management (`view/Administrator/registration_requests.php`)
- View all registration requests (Pending, Approved, Rejected)
- See request details including security questions
- Approve requests (creates user account automatically)
- Reject requests with reason
- Statistics dashboard showing counts

## User Flow

### Registration Flow
1. User clicks "Create Account" on login page
2. Fills out registration form
3. Submits request
4. Redirected to login with success message
5. Admin reviews request in Admin Panel > Registration Requests
6. Admin approves/rejects
7. If approved, user account is created with security questions
8. User can now login

### Password Recovery Flow
1. User clicks "Forgot Password?" on login page
2. Enters their ID Number
3. System shows their security questions
4. User answers both questions correctly
5. User sets new password
6. Redirected to login page
7. Can login with new password

## Security Features
- Passwords are hashed using PHP's `password_hash()`
- Security answers are hashed (case-insensitive comparison)
- Session management prevents unauthorized access to reset steps
- Admin approval required for all new accounts
- Registration requests are tracked with timestamps and reviewer info

## Files Created

### Views
- `view/register.php` - Registration form
- `view/forgot_password.php` - Password recovery flow
- `view/Administrator/registration_requests.php` - Admin panel for managing requests

### Controllers
- `controller/register_controller.php` - Handles registration submissions
- `controller/forgot_password_controller.php` - Handles 3-step password recovery
- `controller/get_registration_details.php` - API endpoint for request details
- `controller/process_registration.php` - Approve/reject registration requests

### Database
- `database/password_recovery_and_registration.sql` - Database schema

## Login Page Updates
The login page now includes:
- "Forgot Password?" link → `forgot_password.php`
- "Create Account" link → `register.php`

## Admin Sidebar Update
Added "Registration Requests" menu item in Administrator sidebar with:
- Yellow color badge (indicates pending items)
- User-check icon
- Links to registration management page

## Testing

### Test Registration
1. Go to login page
2. Click "Create Account"
3. Fill form with test data
4. Submit
5. Login as Administrator
6. Go to "Registration Requests"
7. Approve the request
8. Logout and login with new account

### Test Password Recovery
1. First, ensure a user has security questions (register a new user through admin approval)
2. Go to login page
3. Click "Forgot Password?"
4. Enter ID Number
5. Answer security questions
6. Set new password
7. Login with new password

## Security Questions
Predefined questions to choose from:
- What was the name of your first pet?
- What is your mother's maiden name?
- What was the name of your elementary school?
- What city were you born in?
- What is your favorite food?
- What is your favorite book?
- What was your childhood nickname?
- What is the name of your best friend?
- What is your favorite movie?
- What street did you grow up on?

## Future Enhancements
- Email notifications when registration is approved/rejected
- Email-based password reset (using `password_reset_tokens` table)
- Two-factor authentication
- Password strength requirements
- Account lockout after failed attempts
