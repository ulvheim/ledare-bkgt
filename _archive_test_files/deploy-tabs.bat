@echo off
echo Deploying enhanced Document Management Plugin with tabs...

REM Copy the deployment script to server
scp -i "private_key.pem" "C:\Users\Olheim\Desktop\GH\ledare-bkgt\deploy-tabs-fix.php" root@46.101.127.59:/var/www/html/deploy-tabs-fix.php

if %errorlevel% neq 0 (
    echo SCP failed. Please manually upload deploy-tabs-fix.php to your server root.
    echo Then visit: https://your-domain.com/deploy-tabs-fix.php
    pause
    exit /b 1
)

echo.
echo Deployment script uploaded successfully!
echo.
echo Now visit: https://your-domain.com/deploy-tabs-fix.php
echo.
echo This will update the document management plugin with tab functionality.
echo.
pause