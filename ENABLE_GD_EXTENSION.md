# Enable GD Extension in XAMPP (Optional - for Better Performance)

## Why Enable GD?
- **Auto-compress** signatures (reduces size by 80-90%)
- **Auto-resize** images to optimal dimensions
- **Faster loading** and better performance

## Without GD Extension:
✅ System still works!
⚠️ Must upload images **smaller than 200KB**
⚠️ No automatic optimization

## To Enable GD Extension:

### Step 1: Edit php.ini
1. Open **XAMPP Control Panel**
2. Click **Config** next to **Apache**
3. Select **PHP (php.ini)**

### Step 2: Find and Uncomment GD
Search for this line (around line 930):
```ini
;extension=gd
```

Remove the semicolon (`;`) to make it:
```ini
extension=gd
```

### Step 3: Save and Restart
1. **Save** the php.ini file
2. **Stop Apache** in XAMPP Control Panel
3. **Start Apache** again

### Step 4: Verify
Visit your profile page and try uploading a signature. You should see better compression!

---

## Already Works Without GD!
The system now has a fallback mode that works without GD. You can use it as-is if you:
- Use small signature images (under 200KB)
- Pre-optimize images before uploading

**Recommended:** Enable GD for the best experience! 🚀
