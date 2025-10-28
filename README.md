# Asset Management System (AMS)

A comprehensive asset management system with role-based access control.

## User Roles

The system supports 5 different user roles with automatic role detection based on ID number format:

1. **Student** (ID prefix: S) - e.g., S2024-001
2. **Faculty** (ID prefix: F) - e.g., F2024-001
3. **Technician** (ID prefix: T) - e.g., T2024-001
4. **Laboratory Staff** (ID prefix: L) - e.g., L2024-001
5. **Administrator** (ID prefix: A) - e.g., A2024-001

## Setup Instructions

### 1. Install Dependencies

```bash
npm install
```

### 2. Build Tailwind CSS

```bash
npm run build:css
```

For development with auto-rebuild:
```bash
npm run dev
```

### 3. Environment Configuration

1. Copy `.env.example` to `.env`:
   ```
   copy .env.example .env
   ```
2. Edit `.env` and update your database credentials:
   ```
   DB_HOST=localhost
   DB_NAME=ams_database
   DB_USERNAME=root
   DB_PASSWORD=your_password
   ```

### 4. Database Setup

1. Open phpMyAdmin or MySQL command line
2. Import the database file: `database/ams_database.sql`
3. This will create the database and insert sample users

### 3. Test Login Credentials

All test users have the password: `password123`

- **Administrator**: A2024-001
- **Technician**: T2024-001
- **Laboratory Staff**: L2024-001
- **Faculty**: F2024-001
- **Student**: S2024-001 or S2024-002

## Features

### Login System
- Modern UI with Tailwind CSS
- Role-based authentication
- Automatic role detection from ID number prefix
- Secure password hashing (bcrypt)
- Session management
- Error handling and user feedback
- Environment-based configuration (.env)
- XSS protection with htmlspecialchars()

### ID Number Format
The system automatically determines user roles based on the first letter of the ID:
- **S** → Student
- **F** → Faculty
- **T** → Technician
- **L** → Laboratory Staff
- **A** → Administrator

### Security Features
- Environment variables using .env file
- Password hashing using PHP's password_hash()
- SQL injection prevention using PDO prepared statements
- XSS protection with output escaping
- Session-based authentication
- Role-based access control
- Input validation
- Secure configuration management
- .gitignore for sensitive files

## Project Structure

```
CAPSTONE-AMS/
├── assets/
│   └── css/
│       └── login.css
├── config/
│   └── auth.php (Authentication helper functions)
├── controller/
│   ├── login_controller.php
│   └── logout_controller.php
├── model/
│   ├── Database.php
│   └── User.php
├── view/
│   ├── login.php
│   ├── admin/ (Dashboard for administrators)
│   ├── technician/ (Dashboard for technicians)
│   ├── lab_staff/ (Dashboard for lab staff)
│   └── user/ (Dashboard for students/faculty)
└── database/
    └── ams_database.sql
```

## Next Steps

1. Create dashboard pages for each role:
   - `view/admin/dashboard.php`
   - `view/technician/dashboard.php`
   - `view/lab_staff/dashboard.php`
   - `view/user/dashboard.php`

2. Implement asset management features:
   - Asset listing and search
   - Asset requests
   - Asset maintenance tracking
   - Reporting and analytics

3. Add user management features (for administrators):
   - Add/edit/delete users
   - View user activity logs
   - Manage permissions

## Usage

1. Navigate to: `http://localhost/CAPSTONE-AMS/view/login.php`
2. Enter your Student No. or Employee No.
3. Enter your password
4. Click Login
5. You'll be redirected to your role-specific dashboard

## Support

For issues or questions, please contact your system administrator.
