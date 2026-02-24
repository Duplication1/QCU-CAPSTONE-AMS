# Ticket Image Upload Feature

## Overview
This feature allows users to attach images to ticket submissions. It's useful for providing visual context for hardware issues, software bugs, or other problems.

## Features
- **Optional image attachment** when submitting tickets
- **Real-time image preview** before submission
- **File validation** (type and size)
- **Secure upload handling** with proper permissions and restrictions
- **Supported formats**: JPG, JPEG, PNG, GIF, WEBP
- **Maximum file size**: 5MB

## Installation

### 1. Run the Migration
Visit the migration page in your browser to set up the database and directory:
```
http://localhost/QCU-CAPSTONE-AMS/run_ticket_image_migration.php
```

This will:
- Add the `image_path` column to the `issues` table
- Create the `uploads/ticket_images/` directory
- Set up security restrictions (.htaccess)

### 2. Verify Directory Permissions
Ensure the uploads directory is writable:
```bash
# On Windows (if needed)
icacls "uploads\ticket_images" /grant Everyone:F

# On Linux/Mac
chmod -R 755 uploads/ticket_images
```

### 3. Clean Up (Optional)
After successful migration, you can delete:
- `run_ticket_image_migration.php`
- `migrate_ticket_images.sql`

## Usage

### For Users
1. Navigate to the ticket submission page
2. Fill out the ticket form as usual
3. Click "Choose File" in the "Attach Image" section (optional)
4. Select an image file (JPG, PNG, GIF, or WEBP)
5. Preview appears automatically
6. Click "Remove image" if you want to change or remove it
7. Submit the ticket

### For Developers

#### Database Schema
New column added to `issues` table:
```sql
`image_path` VARCHAR(500) DEFAULT NULL COMMENT 'Path to uploaded issue image'
```

#### File Structure
```
uploads/
  ticket_images/
    .gitkeep
    .gitignore
    .htaccess
    [uploaded images will be stored here]
```

#### Accessing Uploaded Images
Images are stored with the path format:
```
uploads/ticket_images/YYYY-MM-DD_HHmmss_uniqueid.ext
```

To display an image in your views:
```php
<?php if (!empty($issue['image_path'])): ?>
    <img src="../../<?php echo htmlspecialchars($issue['image_path']); ?>" 
         alt="Issue image" 
         class="max-w-full h-auto">
<?php endif; ?>
```

## Security Features
- File type validation (only images allowed)
- File size limit (5MB maximum)
- PHP execution disabled in upload directory (.htaccess)
- Unique filenames prevent overwrites
- Path validation in controller

## Files Modified
1. **Database**:
   - `migrate_ticket_images.sql` - Migration script
   - `run_ticket_image_migration.php` - Migration runner

2. **Backend**:
   - `controller/submit_issue.php` - Handle file uploads

3. **Frontend**:
   - `view/StudentFaculty/tickets.php` - Add upload field and preview
   - `view/LaboratoryStaff/submit_tickets.php` - Add upload field and preview

4. **Uploads**:
   - `uploads/ticket_images/` - Storage directory (created automatically)

## Troubleshooting

### Images Not Uploading
1. Check directory permissions (must be writable)
2. Verify PHP upload settings in `php.ini`:
   ```ini
   upload_max_filesize = 5M
   post_max_size = 8M
   ```
3. Check Apache/server error logs

### Preview Not Showing
1. Ensure JavaScript is enabled
2. Check browser console for errors
3. Verify the file is a valid image format

### Database Error
1. Ensure migration was run successfully
2. Check database user has ALTER privileges
3. Verify connection settings in `config/config.php`

## Future Enhancements
- Multiple image uploads per ticket
- Image compression/optimization
- Thumbnail generation
- Image gallery view in ticket details
- Image annotation tools

## Support
For issues or questions, contact the development team.
