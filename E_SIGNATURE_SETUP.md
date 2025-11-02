# E-Signature Feature Setup Instructions

## Database Setup

1. **Open phpMyAdmin** (http://localhost/phpmyadmin)

2. **Select your database** (`ams_database`)

3. **Run the migration SQL**:
   - Go to the SQL tab
   - Open the file: `database/add_e_signature_column.sql`
   - Copy and paste the SQL content, or import the file
   - Click "Go" to execute

   Alternatively, you can run this command in the SQL tab:
   ```sql
   ALTER TABLE `users` 
   ADD COLUMN `e_signature` VARCHAR(255) NULL DEFAULT NULL AFTER `last_login`;
   ```

## Verify Installation

1. Go to the users table structure
2. Confirm the `e_signature` column exists (should be after `last_login`)

## Testing

1. Log in as a Student or Faculty user
2. Navigate to "My E-Signature" in the sidebar
3. Upload a signature image (JPG, PNG, or GIF, max 2MB)
4. Verify the signature displays correctly
5. Test the remove functionality

## File Structure Created

```
uploads/
  signatures/
    .htaccess          (Security configuration)
    README.md          (Documentation)
    (signature files will be stored here)

controller/
  upload_signature.php    (Handles signature upload)
  delete_signature.php    (Handles signature deletion)

view/
  StudentFaculty/
    e-signature.php        (E-signature management page)

database/
  add_e_signature_column.sql  (Database migration)
```

## Features

- ✅ Upload signature images (JPG, PNG, GIF)
- ✅ File size limit: 2MB
- ✅ Live preview before upload
- ✅ Display current signature
- ✅ Delete/remove signature
- ✅ Security: Only authenticated Student/Faculty users can manage their signatures
- ✅ Automatic old signature cleanup on new upload

## Notes

- Signatures are stored in `uploads/signatures/` directory
- File naming: `signature_{user_id}_{timestamp}.{extension}`
- Directory is protected with .htaccess (no direct listing)
- Only image files are accessible
