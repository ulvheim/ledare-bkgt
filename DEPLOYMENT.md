# BKGT Ledare Deployment Guide

## Overview
This guide covers deploying the BKGT Ledare WordPress management system to production.

## Prerequisites
- SSH access to production server (ledare.bkgt.se)
- SSH key configured for passwordless authentication
- WordPress installation on production server
- Database access on production server

## Environment Setup
Ensure your `.env` file contains the following variables:
```
SSH_HOST=ledare.bkgt.se
SSH_USER=your_ssh_username
SSH_KEY_PATH=/path/to/your/private/key
REMOTE_FOLDER=/path/to/wordpress/installation
DB_HOST=localhost
DB_NAME=your_database_name
DB_USER=your_database_user
DB_PASS=your_database_password
```

## Deployment Script
The `deploy.sh` script provides comprehensive deployment automation:

### Features
- SSH connection testing
- Dry-run mode for safe testing
- Rsync-based file synchronization
- WordPress-specific optimizations
- Database backup before deployment
- Proper file permissions
- Cache clearing
- Rollback capability

### Usage

#### Test Deployment (Recommended First)
```bash
./deploy.sh --dry-run
```

#### Full Deployment
```bash
./deploy.sh
```

#### Deploy Specific Components
```bash
./deploy.sh --theme-only    # Deploy only theme
./deploy.sh --plugins-only  # Deploy only plugins
```

### Script Options
- `--dry-run`: Test deployment without making changes
- `--theme-only`: Deploy only the theme
- `--plugins-only`: Deploy only plugins
- `--no-backup`: Skip database backup
- `--help`: Show help information

## Manual Deployment Steps
If you prefer manual deployment:

1. **Backup Database**
   ```bash
   mysqldump -h $DB_HOST -u $DB_USER -p$DB_PASS $DB_NAME > backup_$(date +%Y%m%d_%H%M%S).sql
   ```

2. **Sync Files**
   ```bash
   rsync -avz --delete --exclude='.git' --exclude='node_modules' --exclude='.env' \
     -e "ssh -i $SSH_KEY_PATH" ./wp-content/themes/bkgt-ledare/ \
     $SSH_USER@$SSH_HOST:$REMOTE_FOLDER/wp-content/themes/bkgt-ledare/

   rsync -avz --delete --exclude='.git' --exclude='node_modules' --exclude='.env' \
     -e "ssh -i $SSH_KEY_PATH" ./wp-content/plugins/ \
     $SSH_USER@$SSH_HOST:$REMOTE_FOLDER/wp-content/plugins/
   ```

3. **Set Permissions**
   ```bash
   ssh -i $SSH_KEY_PATH $SSH_USER@$SSH_HOST "find $REMOTE_FOLDER/wp-content -type f -exec chmod 644 {} \;"
   ssh -i $SSH_KEY_PATH $SSH_USER@$SSH_HOST "find $REMOTE_FOLDER/wp-content -type d -exec chmod 755 {} \;"
   ```

4. **Clear WordPress Cache**
   ```bash
   ssh -i $SSH_KEY_PATH $SSH_USER@$SSH_HOST "wp cache flush --path=$REMOTE_FOLDER"
   ```

## Post-Deployment Verification
1. Visit the website and verify functionality
2. Check WordPress admin panel
3. Test user management, inventory, and document features
4. Verify file permissions are correct
5. Check error logs for any issues

## Troubleshooting
- **SSH Connection Issues**: Verify SSH key and server access
- **Permission Errors**: Check file ownership on server
- **Database Connection**: Verify database credentials
- **Plugin Activation**: Manually activate plugins in WordPress admin if needed

## Security Notes
- Never commit `.env` file to version control
- Use strong SSH keys
- Regularly rotate database passwords
- Keep WordPress and plugins updated
- Monitor server logs for suspicious activity