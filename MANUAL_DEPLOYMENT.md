# BKGT API Plugin - Manual Deployment Guide
# Since automated scripts have authentication issues, use this manual method

## Files to Upload

Upload these files to: `/public_html/wp-content/plugins/bkgt-api/`

### Core Files:
- `bkgt-api.php` (20.2 KB)
- `README.md` (52.8 KB)
- `flush-api-keys.php` (5.4 KB) - **NEW**
- `generate-new-api-key.php` (2.4 KB) - **NEW**

### Include Files (upload to `includes/` folder):
- `includes/class-bkgt-api.php` (29.1 KB)
- `includes/class-bkgt-auth.php` (15.7 KB) - **MODIFIED**
- `includes/class-bkgt-endpoints.php` (84.0 KB)
- `includes/class-bkgt-security.php` (22.2 KB)
- `includes/class-bkgt-notifications.php` (20.5 KB)

### Admin Files (upload to `admin/` folder):
- `admin/class-bkgt-api-admin.php` (53.2 KB) - **MODIFIED**
- `admin/css/admin.css` (11.0 KB)
- `admin/js/admin.js` (30.4 KB) - **MODIFIED**

## Deployment Steps

1. **Connect to your server** using FileZilla, WinSCP, or similar SFTP client:
   - Server: `ssh.loopia.se`
   - User: `md0600`
   - Port: `22`
   - Protocol: `SFTP`

2. **Navigate to the plugin directory**:
   ```
   /public_html/wp-content/plugins/bkgt-api/
   ```

3. **Upload all files** listed above, maintaining the folder structure.

4. **Verify upload** by checking file sizes match the local files.

## Post-Deployment Steps

1. **Flush existing API keys**:
   ```
   https://ledare.bkgt.se/wp-content/plugins/bkgt-api/flush-api-keys.php
   ```

2. **Generate new API key**:
   ```
   https://ledare.bkgt.se/wp-content/plugins/bkgt-api/generate-new-api-key.php
   ```

3. **Test the API**:
   ```
   https://ledare.bkgt.se/wp-content/plugins/bkgt-api/test-production-api.php
   ```

## What Was Changed

- **Added delete functionality**: API keys can now be permanently deleted (not just revoked)
- **Added flush script**: Removes all existing API keys for a fresh start
- **Added key generation script**: Creates new API keys programmatically
- **Updated admin interface**: Added delete buttons and functionality

## Troubleshooting

If you get 404 errors after deployment:
1. Check that the plugin is activated in WordPress Admin
2. Flush permalinks: Settings → Permalinks → Save Changes
3. Clear any caching plugins

If API authentication still fails:
1. Run the flush script first
2. Generate a new API key
3. Update your application with the new key