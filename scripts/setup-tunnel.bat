@echo off
setlocal enabledelayedexpansion

REM Crelate Local Development Tunnel Setup Script for Windows
REM This script helps set up Cloudflare Tunnel for local development

set LOCAL_PORT=5210
set TUNNEL_NAME=crelate-local-dev
set CONFIG_FILE=.env.local

echo === Crelate Local Development Tunnel Setup ===
echo.

if "%1"=="" goto help

if "%1"=="setup" goto setup
if "%1"=="start" goto start
if "%1"=="stop" goto stop
if "%1"=="status" goto status
if "%1"=="help" goto help

echo Unknown command: %1
goto help

:check_cloudflared
echo Checking for cloudflared...
cloudflared --version >nul 2>&1
if errorlevel 1 (
    echo ❌ cloudflared is not installed
    echo.
    echo Please install cloudflared:
    echo   Download from: https://developers.cloudflare.com/cloudflare-one/connections/connect-apps/install-and-setup/installation/
    echo   Or use Chocolatey: choco install cloudflared
    exit /b 1
)
echo ✅ cloudflared is installed
goto :eof

:check_tunnel
echo Checking for existing tunnel...
cloudflared tunnel list | findstr "%TUNNEL_NAME%" >nul
if errorlevel 1 (
    echo ⚠️  Tunnel '%TUNNEL_NAME%' not found
    exit /b 1
) else (
    echo ✅ Tunnel '%TUNNEL_NAME%' exists
    exit /b 0
)

:create_tunnel
echo Creating tunnel '%TUNNEL_NAME%'...
cloudflared tunnel create "%TUNNEL_NAME%"
if errorlevel 1 (
    echo ❌ Failed to create tunnel
    exit /b 1
)

for /f "tokens=1" %%i in ('cloudflared tunnel list ^| findstr "%TUNNEL_NAME%"') do set TUNNEL_ID=%%i
echo ✅ Tunnel created with ID: !TUNNEL_ID!

call :create_config_file !TUNNEL_ID!
goto :eof

:create_config_file
echo Creating tunnel configuration...
if not exist "%USERPROFILE%\.cloudflared" mkdir "%USERPROFILE%\.cloudflared"

(
echo tunnel: %1
echo credentials-file: %USERPROFILE%\.cloudflared\%1.json
echo.
echo ingress:
echo   - hostname: crelate-local-dev.your-domain.com
echo     service: http://localhost:%LOCAL_PORT%
echo   - service: http_status:404
) > "%USERPROFILE%\.cloudflared\config.yml"

echo ✅ Configuration file created
goto :eof

:start_tunnel
echo Starting tunnel...

REM Check if tunnel is already running
tasklist /FI "IMAGENAME eq cloudflared.exe" 2>NUL | find /I /N "cloudflared.exe">NUL
if "%ERRORLEVEL%"=="0" (
    echo ⚠️  Tunnel is already running
    goto :eof
)

REM Start tunnel in background
start /B cloudflared tunnel run "%TUNNEL_NAME%" > tunnel.log 2>&1

REM Wait a moment for tunnel to start
timeout /t 3 /nobreak >nul

REM Check if tunnel started successfully
tasklist /FI "IMAGENAME eq cloudflared.exe" 2>NUL | find /I /N "cloudflared.exe">NUL
if "%ERRORLEVEL%"=="0" (
    echo ✅ Tunnel is running
    echo Logs are being written to tunnel.log
) else (
    echo ❌ Failed to start tunnel
    echo Check tunnel.log for details
    exit /b 1
)
goto :eof

:get_tunnel_url
echo Getting tunnel URL...
echo Waiting for tunnel to be ready...
timeout /t 5 /nobreak >nul

REM Try to get the URL from the logs
if exist "tunnel.log" (
    for /f "tokens=*" %%i in ('findstr "https://.*trycloudflare.com" tunnel.log') do (
        set TUNNEL_URL=%%i
        goto :found_url
    )
)

echo ⚠️  Could not determine tunnel URL automatically
echo Please check tunnel.log for the URL
goto :eof

:found_url
echo ✅ Tunnel URL: !TUNNEL_URL!

REM Save to .env.local
echo PUBLIC_BASE_URL=!TUNNEL_URL! > "%CONFIG_FILE%"
echo ✅ Saved to %CONFIG_FILE%
goto :eof

:stop_tunnel
echo Stopping tunnel...

REM Find and kill tunnel process
taskkill /F /IM cloudflared.exe >nul 2>&1
if errorlevel 1 (
    echo ⚠️  No tunnel process found
) else (
    echo ✅ Tunnel stopped
)
goto :eof

:show_status
echo === Tunnel Status ===

call :check_tunnel
if errorlevel 1 (
    echo ❌ Tunnel does not exist
    goto :eof
)

REM Check if tunnel is running
tasklist /FI "IMAGENAME eq cloudflared.exe" 2>NUL | find /I /N "cloudflared.exe">NUL
if "%ERRORLEVEL%"=="0" (
    echo ✅ Tunnel is running
    
    REM Show tunnel URL
    if exist "%CONFIG_FILE%" (
        for /f "tokens=2 delims==" %%i in ('findstr "PUBLIC_BASE_URL" "%CONFIG_FILE%"') do (
            echo ✅ Public URL: %%i
        )
    )
) else (
    echo ❌ Tunnel is not running
)

echo Local port: %LOCAL_PORT%
goto :eof

:setup
call :check_cloudflared
call :check_tunnel
if errorlevel 1 (
    call :create_tunnel
)
call :start_tunnel
call :get_tunnel_url
call :show_status
goto :eof

:start
call :check_cloudflared
call :check_tunnel
if errorlevel 1 (
    echo ❌ Tunnel does not exist. Run 'setup-tunnel.bat setup' first.
    exit /b 1
)
call :start_tunnel
call :get_tunnel_url
goto :eof

:stop
call :stop_tunnel
goto :eof

:status
call :show_status
goto :eof

:help
echo Usage: %0 [COMMAND]
echo.
echo Commands:
echo   setup     - Set up tunnel (create if needed, start)
echo   start     - Start existing tunnel
echo   stop      - Stop tunnel
echo   status    - Show tunnel status
echo   help      - Show this help
echo.
echo Environment variables:
echo   LOCAL_PORT - Local port to tunnel (default: 5210)
echo.
echo Examples:
echo   %0 setup
echo   set LOCAL_PORT=3000 && %0 setup
echo   %0 status
goto :eof
