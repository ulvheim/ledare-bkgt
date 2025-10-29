<?php
require_once('wp-load.php');

global $wpdb;

// Check existing BKGT tables
$tables = $wpdb->get_results("SHOW TABLES LIKE 'wp_bkgt_%'");
echo "Existing BKGT tables:\n";
foreach($tables as $table) {
    $table_name = 'Tables_in_' . $wpdb->dbname;
    echo $table->{$table_name} . "\n";
}

// Check if documents table exists
$documents_table = $wpdb->prefix . 'bkgt_documents';
if($wpdb->get_var("SHOW TABLES LIKE '$documents_table'") != $documents_table) {
    echo "\nDocuments table does not exist. Creating...\n";

    // Create documents table
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $documents_table (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        title varchar(255) NOT NULL,
        filename varchar(255) NOT NULL,
        filepath varchar(500) NOT NULL,
        file_url varchar(500) NOT NULL,
        file_size bigint(20) NOT NULL,
        mime_type varchar(100) NOT NULL,
        category varchar(100) DEFAULT '',
        description text,
        uploaded_by bigint(20) unsigned NOT NULL,
        upload_date datetime DEFAULT CURRENT_TIMESTAMP,
        modified_date datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        access_level varchar(50) DEFAULT 'private',
        version int(11) DEFAULT 1,
        parent_id mediumint(9) DEFAULT 0,
        metadata longtext,
        PRIMARY KEY (id),
        KEY uploaded_by (uploaded_by),
        KEY category (category),
        KEY access_level (access_level)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);

    echo "Documents table created successfully!\n";
} else {
    echo "\nDocuments table already exists.\n";
}
?>