@echo off
REM PC Health Agent Launcher
REM This batch file starts the PC Health Agent with predefined settings

REM ====================================
REM CONFIGURATION - EDIT THESE VALUES
REM ====================================

REM Get the PC Unit ID from your database (e.g., 1, 2, 3, etc.)
SET PC_UNIT_ID=1

REM Terminal name (e.g., TH-01, PC-01, etc.)
SET TERMINAL_NAME=TH-01

REM Room name (e.g., Computer Lab 1, Computer Lab 2, etc.)
SET ROOM_NAME=Computer Lab 1

REM Firebase Database URL (replace with your actual Firebase URL)
SET FIREBASE_URL=https://your-project-id-default-rtdb.firebaseio.com

REM Update interval in seconds (default: 5)
SET INTERVAL=5

REM ====================================
REM DO NOT EDIT BELOW THIS LINE
REM ====================================

echo.
echo ======================================
echo   PC Health Monitoring Agent
echo ======================================
echo.
echo Starting monitoring for:
echo   PC Unit ID    : %PC_UNIT_ID%
echo   Terminal Name : %TERMINAL_NAME%
echo   Room Name     : %ROOM_NAME%
echo.

REM Check if PowerShell script exists
if not exist "%~dp0pc_health_agent.ps1" (
    echo ERROR: pc_health_agent.ps1 not found!
    echo Please ensure pc_health_agent.ps1 is in the same folder as this batch file.
    pause
    exit /b 1
)

REM Start PowerShell script
powershell.exe -ExecutionPolicy Bypass -File "%~dp0pc_health_agent.ps1" -PCUnitId "%PC_UNIT_ID%" -TerminalName "%TERMINAL_NAME%" -RoomName "%ROOM_NAME%" -FirebaseURL "%FIREBASE_URL%" -IntervalSeconds %INTERVAL%

pause
