-- SWE3 Plugin Manual Activation SQL
-- Run these queries in your WordPress database to manually activate the SWE3 scraper plugin

-- 1. Add plugin to active plugins list
-- Note: Replace 'a:X' with the correct serialized array count
-- First, get current active plugins
SELECT option_value FROM wp_options WHERE option_name = 'active_plugins';

-- Then update with the new plugin added (adjust the serialized array accordingly)
-- Example for adding to existing plugins:
-- UPDATE wp_options SET option_value = 'a:2:{i:0;s:30:"plugin1/plugin1.php";i:1;s:41:"bkgt-swe3-scraper/bkgt-swe3-scraper.php";}' WHERE option_name = 'active_plugins';

-- 2. Create the SWE3 documents table
CREATE TABLE IF NOT EXISTS `wp_bkgt_swe3_documents` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `swe3_id` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `document_type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `swe3_url` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `local_path` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `file_hash` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `version` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `publication_date` date DEFAULT NULL,
  `scraped_date` datetime DEFAULT CURRENT_TIMESTAMP,
  `dms_document_id` int(11) DEFAULT NULL,
  `status` enum('active','archived','error') COLLATE utf8mb4_unicode_ci DEFAULT 'active',
  `last_checked` datetime DEFAULT NULL,
  `error_message` text COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`),
  UNIQUE KEY `swe3_id` (`swe3_id`),
  KEY `idx_status` (`status`),
  KEY `idx_last_checked` (`last_checked`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Set default plugin options
INSERT IGNORE INTO `wp_options` (`option_name`, `option_value`, `autoload`) VALUES
('bkgt_swe3_scrape_enabled', 'yes', 'yes'),
('bkgt_swe3_scrape_hour', '2', 'yes'),
('bkgt_swe3_scrape_minute', '0', 'yes'),
('bkgt_swe3_log_level', 'info', 'yes'),
('bkgt_swe3_last_scrape', 'never', 'yes'),
('bkgt_swe3_db_version', '1.0.0', 'yes');

-- 4. Verify the table was created
SHOW TABLES LIKE 'wp_bkgt_swe3_documents';

-- 5. Check that options were set
SELECT option_name, option_value FROM wp_options WHERE option_name LIKE 'bkgt_swe3_%';