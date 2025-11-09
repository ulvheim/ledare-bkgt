# BKGT Teams Diagnostic Script
# Diagnoses team data issues on the remote server

param(
    [string]$SshHost = "md0600@ssh.loopia.se",
    [string]$SshKey = "C:\Users\Olheim\.ssh\id_ecdsa_webhost",
    [string]$Action = "diagnose"  # diagnose, cleanup, or clear_repopulate
)

Write-Host "BKGT Teams Data Management" -ForegroundColor Cyan
Write-Host "==========================" -ForegroundColor Cyan
Write-Host ""

# Upload the management script
Write-Host "Uploading manual team insert script..." -ForegroundColor Yellow
scp -i $SshKey -o StrictHostKeyChecking=no "manual-insert-teams.php" ${SshHost}:~/ledare.bkgt.se/public_html/manual-insert-teams.php

# Execute the script
Write-Host "Executing manual team insert..." -ForegroundColor Yellow
ssh -i $SshKey -o StrictHostKeyChecking=no $SshHost "cd ~/ledare.bkgt.se/public_html && php manual-insert-teams.php"

# Clean up
Write-Host "Cleaning up..." -ForegroundColor Yellow
ssh -i $SshKey -o StrictHostKeyChecking=no $SshHost "rm ~/ledare.bkgt.se/public_html/manual-insert-teams.php"

Write-Host ""
Write-Host "Script execution completed." -ForegroundColor Green