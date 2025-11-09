# BKGT SWE3 Document Scraper

A WordPress plugin that automatically scrapes and curates official documents from the Swedish American Football Federation (SWE3) website, integrating them into the BKGT Document Management System.

## Features

- **Automated Daily Scraping**: Scheduled scraping of SWE3 documents at configurable times
- **Document Management Integration**: Seamless integration with BKGT DMS
- **Change Detection**: Only updates documents when content has actually changed
- **Comprehensive Logging**: Detailed logging for monitoring and debugging
- **Admin Dashboard**: User-friendly interface for monitoring and manual control
- **Public Access**: All SWE3 documents are made available to authenticated BKGT users
- **Version Control**: Maintains complete history of document changes

## Installation

1. Download the plugin files
2. Upload the `bkgt-swe3-scraper` folder to `/wp-content/plugins/`
3. Activate the plugin through the WordPress admin dashboard
4. Configure settings in **Tools > SWE3 Scraper**

## Configuration

### Basic Settings

- **Enable Scraping**: Turn automatic daily scraping on/off
- **Scrape Time**: Set the daily execution time (default: 02:00)
- **Log Level**: Control logging verbosity (Debug, Info, Warning, Error)

### Advanced Configuration

The plugin can be further configured by defining constants in `wp-config.php`:

```php
// Define DMS post type (adjust based on your DMS)
define('BKGT_DMS_POST_TYPE', 'document');

// Enable URL validation during development
define('BKGT_SWE3_VALIDATE_URLS', true);

// Custom upload directory for SWE3 documents
define('BKGT_SWE3_UPLOAD_DIR', 'swe3-documents');
```

## Usage

### Automatic Operation

Once configured, the plugin will:
1. Run daily at the configured time
2. Scrape the SWE3 rules page for new/updated documents
3. Download any changed documents
4. Create or update DMS entries with proper categorization
5. Log all activities for monitoring

### Manual Operation

Administrators can manually trigger scraping from the admin dashboard:
1. Go to **Tools > SWE3 Scraper**
2. Click **"Run Manual Scrape"**
3. Monitor progress in the status area

### Document Access

SWE3 documents are available in the DMS with:
- **Naming Convention**: "SWE3-[Type]-[Title]-[Version]"
- **Category**: "SWE3 Official Documents"
- **Permissions**: Public access for all authenticated users

## Document Types

The plugin categorizes documents into the following types:

- **Competition Regulations** (`competition-regulations`): Official rules and regulations
- **Game Rules** (`game-rules`): Specific game rules and modifications
- **Referee Guidelines** (`referee-guidelines`): Referee rules and procedures
- **Development Series** (`development-series`): Youth and development program rules
- **Safety & Medical** (`safety-medical`): Safety protocols and medical guidelines
- **Easy Football** (`easy-football`): Easy Football program rules
- **Competition Formats** (`competition-formats`): Series and competition structures
- **General** (`general`): Other official documents

## Database Structure

The plugin creates a custom table `wp_bkgt_swe3_documents` to track:

```sql
CREATE TABLE wp_bkgt_swe3_documents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    swe3_id VARCHAR(100) UNIQUE,           -- SWE3 document identifier
    title VARCHAR(255) NOT NULL,           -- Document title
    document_type VARCHAR(50),             -- Document category
    swe3_url VARCHAR(500),                 -- Original SWE3 URL
    local_path VARCHAR(500),               -- Local file path
    file_hash VARCHAR(64),                 -- MD5 hash for change detection
    version VARCHAR(20),                   -- Document version
    publication_date DATE,                 -- SWE3 publication date
    scraped_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    dms_document_id INT,                   -- Linked DMS document ID
    status ENUM('active', 'archived', 'error') DEFAULT 'active',
    last_checked DATETIME,
    error_message TEXT
);
```

## Monitoring

### Admin Dashboard

The admin interface provides:
- **Status Overview**: Current scraping status and next run time
- **Document Statistics**: Count of documents by type
- **Recent Activity**: Last 10 processed documents
- **Manual Controls**: Trigger immediate scraping

### Logging

Logs are written to the WordPress debug log when `WP_DEBUG` is enabled. Log levels:
- **ERROR**: Critical errors that prevent operation
- **WARNING**: Issues that don't stop execution
- **INFO**: Normal operation information
- **DEBUG**: Detailed debugging information

### Error Handling

The plugin includes comprehensive error handling:
- **Retry Logic**: Failed downloads are retried up to 3 times with exponential backoff
- **Failure Notifications**: Email alerts after 3 consecutive failures
- **Graceful Degradation**: Continues processing other documents if one fails
- **Status Tracking**: All operations are logged with timestamps

## Security

- **Rate Limiting**: Respectful scraping with 2-second delays between requests
- **User Agent**: Identifies as "BKGT System" to SWE3
- **Input Validation**: All inputs are sanitized and validated
- **File Security**: Downloaded files are stored securely in WordPress uploads
- **Access Control**: Admin-only access to scraper controls

## Legal Compliance

- **Fair Use**: Educational use of publicly available documents
- **Attribution**: Clear source attribution to SWE3
- **Respectful Crawling**: Minimal impact on SWE3 servers
- **Terms Compliance**: Adheres to SWE3 website terms of service

## Troubleshooting

### Common Issues

1. **Scraping Fails**
   - Check SWE3 website accessibility
   - Verify cron jobs are running
   - Check PHP error logs

2. **Documents Not Updating**
   - Verify file permissions on upload directory
   - Check available disk space
   - Review error logs for specific issues

3. **DMS Integration Issues**
   - Confirm DMS post type configuration
   - Check user permissions for document creation
   - Verify category creation permissions

### Debug Mode

Enable debug logging by setting log level to "Debug" in admin settings and adding to `wp-config.php`:

```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

## Development

### Architecture

The plugin follows a modular architecture:

- **`BKGT_SWE3_Scraper`**: Core scraping logic and document processing
- **`BKGT_SWE3_Parser`**: HTML parsing and document extraction
- **`BKGT_SWE3_Scheduler`**: WP Cron integration and scheduling
- **`BKGT_SWE3_DMS_Integration`**: Document management system integration
- **`BKGT_SWE3_Admin`**: Administrative interface

### Hooks and Filters

The plugin provides several WordPress hooks for customization:

```php
// Before scraping starts
do_action('bkgt_swe3_before_scrape');

// After scraping completes
do_action('bkgt_swe3_after_scrape', $results);

// Filter document processing
apply_filters('bkgt_swe3_document_data', $document_data);

// Custom document type detection
apply_filters('bkgt_swe3_document_type', $detected_type, $title, $url);
```

### Extending the Plugin

To add custom document processing:

```php
add_filter('bkgt_swe3_document_data', 'my_custom_processing', 10, 1);
function my_custom_processing($document) {
    // Add custom metadata extraction
    $document['custom_field'] = 'value';
    return $document;
}
```

## Changelog

### Version 1.0.0
- Initial release
- Automated SWE3 document scraping
- BKGT DMS integration
- Admin dashboard
- Comprehensive error handling

## Support

For support and bug reports, please contact the BKGT development team.

## License

This plugin is licensed under the GPL v2 or later.

---

**Note**: This plugin is designed specifically for the BKGT WordPress installation and may require customization for use in other environments.