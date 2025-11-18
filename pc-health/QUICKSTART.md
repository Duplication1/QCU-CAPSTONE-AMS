# Quick Start Guide - PC Health Monitoring

## For Laboratory Staff (Dashboard Access)

### Access the Dashboard
1. Login to AMS system as Laboratory Staff
2. Click on "PC Health Monitor" card on dashboard
3. View real-time status of all laboratory computers

### Dashboard Features
- **Filter by Room**: Use dropdown to view specific labs
- **Color-coded Status**:
  - 🟢 Green = Healthy (everything normal)
  - 🟡 Yellow = Warning (usage 70-90%)
  - 🔴 Red = Critical (usage >90%)
  - ⚫ Gray = Offline (PC not reporting)
- **Click any PC card** to see detailed information

---

## For IT Setup (Installing Agents on PCs)

### Step 1: Firebase Setup (One-time)
1. Go to https://console.firebase.google.com/
2. Create project → Enable Realtime Database
3. Copy Firebase configuration
4. Update these files with your Firebase config:
   - `config/firebase_config.php`
   - `pc-health/client/pc_health_agent.html` (line 165)
   - `pc-health/client/start_agent.bat` (line 16)

### Step 2: Choose Installation Method

#### Method A: HTML Agent (Browser-based) - Easiest
1. On each PC, open browser
2. Navigate to: `http://localhost/CAPSTONE-AMS/pc-health/client/pc_health_agent.html`
3. Fill in:
   - PC Unit ID (from database)
   - Terminal Name (e.g., TH-01)
   - Room Name (e.g., Computer Lab 1)
4. Click "Start Monitoring"
5. Keep browser window open

**To auto-start on boot:**
- Create shortcut to the HTML file
- Place in: `C:\ProgramData\Microsoft\Windows\Start Menu\Programs\StartUp`

#### Method B: PowerShell Agent - Most Reliable
1. Copy these files to each PC:
   - `pc_health_agent.ps1`
   - `start_agent.bat`

2. Edit `start_agent.bat`:
   - Set PC_UNIT_ID (e.g., 1, 2, 3...)
   - Set TERMINAL_NAME (e.g., TH-01, TH-02...)
   - Set ROOM_NAME (e.g., Computer Lab 1)
   - Set FIREBASE_URL (your Firebase URL)

3. Test by running `start_agent.bat`
   - Should show "Status: healthy" every 5 seconds

4. To auto-start on boot:
   - Create shortcut to `start_agent.bat`
   - Place in: `C:\ProgramData\Microsoft\Windows\Start Menu\Programs\StartUp`

### Step 3: Verify
1. Check dashboard - PC should appear online
2. Metrics should update every 5 seconds
3. Status should show healthy/warning/critical based on usage

---

## Quick Troubleshooting

### PC shows as "Offline" on dashboard
- ✅ Check if agent is running on PC
- ✅ Verify Firebase URL is correct
- ✅ Check PC Unit ID matches database
- ✅ Ensure XAMPP Apache is running

### Agent won't start
- ✅ Check Firebase config is set up
- ✅ Verify PC has internet connection
- ✅ Check Windows Firewall isn't blocking
- ✅ Run as Administrator if needed

### Dashboard not updating
- ✅ Check browser console (F12) for errors
- ✅ Verify Firebase config in `config/firebase_config.php`
- ✅ Check if data appears in Firebase Console
- ✅ Refresh the page

---

## Monitoring Multiple Labs

### Example Setup:
```
Computer Lab 1:
  - TH-01 (PC Unit ID: 1)
  - TH-02 (PC Unit ID: 2)
  - TH-03 (PC Unit ID: 3)

Computer Lab 2:
  - TH-01 (PC Unit ID: 4)
  - TH-02 (PC Unit ID: 5)
  - TH-03 (PC Unit ID: 6)
```

Each PC gets:
- Unique PC Unit ID (from database)
- Terminal Name (can repeat per room)
- Room Name (for filtering)

---

## System Requirements

### For PC Agents:
- Windows 7 or higher
- PowerShell 5.1+ (built into Windows 10/11)
- Internet connection
- XAMPP running (for local API)

### For Dashboard:
- Any modern web browser
- Laboratory Staff account
- Internet connection (for Firebase)

---

## Security Notes

⚠️ **For Testing**: Firebase is in test mode (anyone can read/write)

🔒 **For Production**:
1. Enable Firebase Authentication
2. Set proper security rules
3. Use HTTPS for all connections
4. Consider VPN for sensitive data

---

## Support

Need help?
1. Check `PC_HEALTH_REALTIME_SETUP.md` for detailed documentation
2. Review Firebase Console for data flow
3. Check browser/PowerShell console for errors
4. Verify network connectivity

---

## Quick Reference

**Firebase Console**: https://console.firebase.google.com/

**Agent Files Location**: `pc-health/client/`

**Dashboard URL**: `view/LaboratoryStaff/pc_health_dashboard.php`

**Update Interval**: 5 seconds (configurable)

**Health Thresholds**:
- Healthy: < 70% usage
- Warning: 70-90% usage
- Critical: > 90% usage
