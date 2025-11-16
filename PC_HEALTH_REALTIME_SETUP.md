# PC Health Monitoring System with Firebase

## Overview
This system provides real-time PC health monitoring for laboratory computers using Firebase Realtime Database. Laboratory staff can monitor CPU, RAM, and disk usage across all lab computers from a centralized dashboard that updates in real-time.

## Architecture

### Components
1. **PC Health Agent** (`pc-health/client/pc_health_agent.html`)
   - Runs on each laboratory computer
   - Collects system metrics (CPU, RAM, Disk)
   - Sends data to Firebase every 5 seconds
   
2. **Firebase Realtime Database**
   - Stores PC health data
   - Provides real-time synchronization
   - Accessible from anywhere
   
3. **Dashboard** (`view/LaboratoryStaff/pc_health_dashboard.php`)
   - Real-time visualization of all PCs
   - Status indicators (Healthy/Warning/Critical)
   - Detailed metrics for each computer
   - Room filtering

## Setup Instructions

### Step 1: Firebase Setup

1. Go to [Firebase Console](https://console.firebase.google.com/)
2. Create a new project or select existing one
3. Go to **Realtime Database** → Create database
4. Start in **test mode** (we'll secure it later)
5. Go to **Project Settings** → **General** → **Your apps**
6. Click **Web** (</>) to add a web app
7. Copy the configuration values

### Step 2: Configure Firebase in Your Application

1. Open `config/firebase_config.php`
2. Replace the placeholder values with your Firebase config:

```php
define('FIREBASE_CONFIG', [
    'apiKey' => "YOUR_ACTUAL_API_KEY",
    'authDomain' => "your-project-id.firebaseapp.com",
    'databaseURL' => "https://your-project-id-default-rtdb.firebaseio.com",
    'projectId' => "your-project-id",
    'storageBucket' => "your-project-id.appspot.com",
    'messagingSenderId' => "YOUR_SENDER_ID",
    'appId' => "YOUR_APP_ID"
]);
```

3. Open `pc-health/client/pc_health_agent.html`
4. Update the `firebaseConfig` object around line 165 with the same values

### Step 3: Setup Firebase Security Rules

In Firebase Console → Realtime Database → Rules, add:

```json
{
  "rules": {
    "pc_health": {
      ".read": true,
      ".write": true,
      "$pc_id": {
        ".validate": "newData.hasChildren(['pcUnitId', 'status', 'lastUpdate'])"
      }
    }
  }
}
```

**Note:** For production, implement proper authentication and restrict read/write access.

### Step 4: Deploy PC Health Agent on Each Computer

For each laboratory computer:

1. **Option A: Browser-based (Recommended for testing)**
   - Open `http://localhost/CAPSTONE-AMS/pc-health/client/pc_health_agent.html` in browser
   - Fill in PC Unit ID, Terminal Name, and Room
   - Click "Start Monitoring"
   - Keep browser window open

2. **Option B: Startup Script (For permanent deployment)**
   
   Create a batch file `start_pc_agent.bat`:
   ```batch
   @echo off
   start "" "C:\Program Files\Google\Chrome\Application\chrome.exe" --app="http://localhost/CAPSTONE-AMS/pc-health/client/pc_health_agent.html" --start-fullscreen
   ```
   
   Add to Windows Startup:
   - Press `Win + R`, type `shell:startup`, press Enter
   - Place the batch file in the Startup folder
   - PC agent will start automatically on boot

3. **Option C: Desktop Shortcut**
   - Create shortcut to `pc_health_agent.html`
   - Place on desktop for easy access
   - Staff can start/stop monitoring as needed

### Step 5: Access the Dashboard

1. Login as Laboratory Staff
2. Navigate to dashboard home
3. Click on "PC Health Monitor" card
4. View real-time health data for all computers

## Features

### PC Health Agent
- ✅ Real-time data collection (every 5 seconds)
- ✅ CPU usage monitoring
- ✅ RAM usage monitoring
- ✅ Disk usage monitoring
- ✅ Network information
- ✅ System information
- ✅ Automatic offline detection
- ✅ Persistent configuration (saves settings)
- ✅ Visual status indicators

### Dashboard
- ✅ Real-time updates (no page refresh needed)
- ✅ Overview statistics (Total, Online, Warning, Critical)
- ✅ Room filtering
- ✅ Color-coded health status
  - 🟢 Green: Healthy (<70% usage)
  - 🟡 Yellow: Warning (70-90% usage)
  - 🔴 Red: Critical (>90% usage)
  - ⚫ Gray: Offline
- ✅ Detailed PC view modal
- ✅ Visual progress bars
- ✅ Last update timestamp

## Data Structure in Firebase

```json
{
  "pc_health": {
    "1": {  // PC Unit ID
      "pcUnitId": "1",
      "terminalName": "TH-01",
      "roomName": "Computer Lab 1",
      "status": "online",
      "lastUpdate": 1700000000000,
      "healthStatus": "healthy",
      "cpu": {
        "usage": 45,
        "name": "Intel Core i5-10400",
        "cores": 6,
        "threads": 12
      },
      "memory": {
        "usage": 62,
        "total": 16,
        "used": 9.9,
        "free": 6.1
      },
      "disks": [
        {
          "drive": "C:",
          "usage": 75,
          "total": 500,
          "used": 375,
          "free": 125
        }
      ],
      "network": [...],
      "systemInfo": {...}
    }
  }
}
```

## Health Status Calculation

- **Healthy**: All metrics < 70%
- **Warning**: Any metric 70-90%
- **Critical**: Any metric > 90%
- **Offline**: No data received for 30+ seconds

## Troubleshooting

### PC Agent Not Connecting
1. Check if XAMPP Apache is running
2. Verify Firebase config is correct in `pc_health_agent.html`
3. Check browser console for errors (F12)
4. Ensure `pc-health/api.php` is accessible

### Dashboard Not Updating
1. Check Firebase config in `config/firebase_config.php`
2. Check browser console for Firebase connection errors
3. Verify Firebase rules allow read access
4. Check if PC agents are running and sending data

### "Offline" Status When PC is On
1. Verify PC agent is running
2. Check Firebase connection in agent
3. Verify PC Unit ID matches database record
4. Check system time is synchronized

## Performance Considerations

- Each PC sends ~1KB of data every 5 seconds
- For 50 PCs: ~600KB/minute bandwidth
- Firebase free tier: 1GB/month download, 10GB storage
- Dashboard updates instantly via WebSocket

## Security Recommendations

### For Production:

1. **Enable Firebase Authentication**
   ```json
   {
     "rules": {
       "pc_health": {
         ".read": "auth != null && auth.role == 'laboratory_staff'",
         ".write": "auth != null",
         "$pc_id": {
           ".write": "auth != null && auth.pcId == $pc_id"
         }
       }
     }
   }
   ```

2. **Use Environment Variables**
   - Don't commit Firebase config to git
   - Use `.env` files for configuration

3. **Implement Rate Limiting**
   - Prevent excessive writes from agents
   - Monitor Firebase usage

4. **Network Security**
   - Use HTTPS for all connections
   - Consider VPN for sensitive labs
   - Restrict Firebase access by IP (if possible)

## Future Enhancements

- [ ] Email/SMS alerts for critical issues
- [ ] Historical data tracking and charts
- [ ] Predictive maintenance alerts
- [ ] Remote PC management commands
- [ ] Temperature monitoring (if hardware supports)
- [ ] Application usage tracking
- [ ] Automated weekly reports
- [ ] Mobile app for monitoring

## Support

For issues or questions:
1. Check Firebase Console for data flow
2. Review browser console logs
3. Verify PHP error logs in XAMPP
4. Test Firebase connection with online tools

## License

Internal use for CAPSTONE-AMS project.
