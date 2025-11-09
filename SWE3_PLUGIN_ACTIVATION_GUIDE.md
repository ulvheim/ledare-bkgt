# Manual SWE3 Plugin Activation Guide

Since PHP/MySQL are not directly accessible in this environment, follow these steps to manually activate the SWE3 scraper plugin:

## Step 1: Database Setup

Run the SQL script `swe3-plugin-activation.sql` in your WordPress database:

1. Access your database (phpMyAdmin, MySQL Workbench, or command line)
2. Select the `bkgt_se_db_1` database
3. Run the queries from `swe3-plugin-activation.sql`

This will:
- Create the `wp_bkgt_swe3_documents` table
- Set default plugin options
- Add the plugin to active plugins (you may need to adjust the serialized array)

## Step 2: Plugin Activation

### Option A: WordPress Admin
1. Log into WordPress admin
2. Go to **Plugins**
3. Find "BKGT SWE3 Document Scraper"
4. Click **Activate**

### Option B: Manual Database Update
If the plugin doesn't appear, manually add it to active plugins:

```sql
-- Get current active plugins
SELECT option_value FROM wp_options WHERE option_name = 'active_plugins';

-- Update with plugin added (adjust the array count and existing plugins)
UPDATE wp_options SET option_value = 'a:1:{i:0;s:41:"bkgt-swe3-scraper/bkgt-swe3-scraper.php";}'
WHERE option_name = 'active_plugins';
```

## Step 3: Verify Installation

1. Check that the plugin appears as "Active" in WordPress admin
2. Visit **Tools > SWE3 Scraper** to access the admin dashboard
3. The database table should be created and options should be set

## Step 4: Test Functionality

1. Go to **Tools > SWE3 Scraper**
2. Click **"Run Manual Scrape"** to test the scraping functionality
3. Check the activity log for results
4. Verify that documents are created in your DMS

## Troubleshooting

### Plugin Not Appearing
- Ensure the plugin files are in `wp-content/plugins/bkgt-swe3-scraper/`
- Check file permissions
- Clear any caching plugins

### Database Errors
- Verify database credentials in `wp-config.php`
- Check that the user has CREATE TABLE permissions
- Ensure the table prefix is correct (default: `wp_`)

### Scraping Issues
- Check SWE3 website accessibility
- Verify upload directory permissions
- Review error logs in the admin dashboard

## Alternative: Web-Based Activation

If you have web access to your WordPress installation:

1. Upload `activate-swe3-plugin.php` to your WordPress root
2. Visit `http://yourdomain.com/activate-swe3-plugin.php` in a browser
3. The script will activate the plugin automatically
4. Delete the script after activation for security

## Post-Activation

Once activated:
- The plugin will run daily at 02:00
- Monitor the admin dashboard for status
- Check document creation in your DMS
- SWE3 documents will be available with "SWE3-" prefix