# PC Health Monitoring System

A comprehensive real-time PC health monitoring system for laboratory computers using Firebase Realtime Database.

## 🎯 Overview

This system allows Laboratory Staff to monitor all laboratory computers from a centralized dashboard. It provides real-time updates on CPU, RAM, and disk usage with color-coded health indicators.

## 📁 Project Structure

```
pc-health/
├── api.php                      # Local API for collecting PC metrics
├── index.php                    # Original standalone monitor
├── client/
│   ├── pc_health_agent.html    # Browser-based monitoring agent
│   ├── pc_health_agent.ps1     # PowerShell monitoring agent
│   └── start_agent.bat         # Batch file to launch PowerShell agent
├── setup_wizard.html           # Interactive setup wizard
├── system_overview.html        # Visual system documentation
├── QUICKSTART.md              # Quick reference guide
└── README.md                  # This file
```

## 🚀 Quick Start

### For Laboratory Staff (Dashboard Access)
1. Login as Laboratory Staff
2. Click "PC Health Monitor" on dashboard
3. View real-time status of all PCs

### For IT Setup (First Time)
1. Open `setup_wizard.html` in browser
2. Follow the 4-step setup process
3. Deploy agents on laboratory computers

## 📖 Documentation

- **Setup Wizard**: Interactive guide → `setup_wizard.html`
- **Quick Start**: Fast deployment → `QUICKSTART.md`
- **Full Guide**: Complete documentation → `../PC_HEALTH_REALTIME_SETUP.md`
- **System Overview**: Visual documentation → `system_overview.html`
- **Summary**: Implementation details → `../PC_HEALTH_SYSTEM_SUMMARY.md`

## ✨ Features

### Real-time Monitoring
- ✅ Updates every 5 seconds
- ✅ WebSocket connectivity via Firebase
- ✅ No page refresh needed
- ✅ Instant status changes

### Visual Dashboard
- ✅ Color-coded health indicators
- ✅ Progress bars for metrics
- ✅ Overview statistics
- ✅ Detailed PC information modals
- ✅ Room filtering

### PC Agents
- ✅ Browser-based (HTML)
- ✅ Background service (PowerShell)
- ✅ Auto-start capability
- ✅ Persistent configuration

### Health Status
- 🟢 **Healthy**: All metrics < 70%
- 🟡 **Warning**: Any metric 70-90%
- 🔴 **Critical**: Any metric > 90%
- ⚫ **Offline**: No data for 30+ seconds

## 🛠️ Installation Methods

### Method A: Browser-based Agent (Easiest)
1. Open `client/pc_health_agent.html`
2. Fill in PC Unit ID, Terminal Name, Room
3. Click "Start Monitoring"

### Method B: PowerShell Agent (Most Reliable)
1. Edit `client/start_agent.bat` with PC details
2. Run the batch file
3. Add to Windows Startup for auto-start

## 📊 Monitored Metrics

- **CPU Usage**: Processor utilization percentage
- **RAM Usage**: Memory consumption and availability
- **Disk Usage**: Storage space per drive
- **System Info**: CPU details, network adapters
- **Health Status**: Calculated based on thresholds

## 🔧 Requirements

### For PC Agents:
- Windows 7 or higher
- PowerShell 5.1+ (built into Windows 10/11)
- Internet connection
- XAMPP Apache running

### For Dashboard:
- Modern web browser
- Laboratory Staff account
- Firebase connection

## ⚙️ Configuration

1. **Firebase Setup**:
   - Create Firebase project
   - Enable Realtime Database
   - Copy configuration

2. **Update Config Files**:
   - `../config/firebase_config.php`
   - `client/pc_health_agent.html` (line 165)
   - `client/start_agent.bat` (line 16)

3. **Deploy Agents**:
   - Install on each PC
   - Configure PC Unit ID
   - Start monitoring

## 🔒 Security

**Development Mode** (Current):
- Firebase in test mode
- Open read/write access
- Suitable for internal testing

**Production Recommendations**:
- Enable Firebase Authentication
- Implement security rules
- Use HTTPS connections
- Consider VPN for sensitive data

## 📈 Performance

- Per PC: ~1KB every 5 seconds
- 50 PCs: ~600KB/minute
- Firebase Free Tier: Supports up to 50 PCs

## 🐛 Troubleshooting

### PC shows offline
- Check if agent is running
- Verify Firebase URL is correct
- Check PC Unit ID matches database

### Dashboard not updating
- Check Firebase config
- View browser console for errors
- Verify Firebase rules

### Agent won't start
- Check Firebase configuration
- Verify internet connection
- Run as Administrator if needed

## 📞 Support

1. Check `QUICKSTART.md` for common issues
2. Review `../PC_HEALTH_REALTIME_SETUP.md` for details
3. View browser/PowerShell console for errors
4. Check Firebase Console for data flow

## 🔮 Future Enhancements

- [ ] Historical data tracking & charts
- [ ] Email/SMS alerts
- [ ] Temperature monitoring
- [ ] Application usage tracking
- [ ] Mobile app
- [ ] Predictive maintenance

## 📜 Original Standalone System

The original standalone monitoring system is still available:
- `index.php`: Single-PC monitor interface
- `api.php`: Local metrics API
  "disk": 60.5
}
```

## Notes

- This version works entirely in PHP without requiring Python.
- Uses `wmic` commands which are Windows-specific.
- For cross-platform compatibility, the Python version is recommended.