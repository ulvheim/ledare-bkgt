<?php
require_once('wp-load.php');

echo "=== Database Table Investigation & Fix ===\n\n";

// Check what tables exist
global $wpdb;
$prefix = $wpdb->prefix;

$expected_tables = array(
    'bkgt_manufacturers',
    'bkgt_item_types',
    'bkgt_inventory_items',
    'bkgt_assignments',
    'bkgt_locations'
);

echo "Checking for BKGT inventory tables:\n";
foreach ($expected_tables as $table_name) {
    $full_table_name = $prefix . $table_name;
    $exists = $wpdb->get_var("SHOW TABLES LIKE '$full_table_name'");
    if ($exists) {
        $count = $wpdb->get_var("SELECT COUNT(*) FROM $full_table_name");
        echo "✅ $full_table_name exists ($count records)\n";
    } else {
        echo "❌ $full_table_name does NOT exist\n";
    }
}

echo "\n=== Creating Missing Tables Manually ===\n";

$charset_collate = $wpdb->get_charset_collate();

// Create tables one by one, without foreign key constraints first
$tables_to_create = array(
    'manufacturers' => "CREATE TABLE IF NOT EXISTS {$prefix}bkgt_manufacturers (
        id int(11) NOT NULL AUTO_INCREMENT,
        name varchar(255) NOT NULL,
        manufacturer_id varchar(4) NOT NULL,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY manufacturer_id (manufacturer_id)
    ) $charset_collate;",

    'item_types' => "CREATE TABLE IF NOT EXISTS {$prefix}bkgt_item_types (
        id int(11) NOT NULL AUTO_INCREMENT,
        name varchar(255) NOT NULL,
        item_type_id varchar(4) NOT NULL,
        custom_fields longtext,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY item_type_id (item_type_id)
    ) $charset_collate;",

    'inventory_items' => "CREATE TABLE IF NOT EXISTS {$prefix}bkgt_inventory_items (
        id int(11) NOT NULL AUTO_INCREMENT,
        unique_identifier varchar(20) NOT NULL,
        manufacturer_id int(11) NOT NULL DEFAULT 0,
        item_type_id int(11) NOT NULL DEFAULT 0,
        title varchar(255) NOT NULL,
        storage_location varchar(255),
        condition_status enum('normal','needs_repair','repaired','reported_lost','scrapped') DEFAULT 'normal',
        condition_date datetime,
        condition_reason text,
        metadata longtext,
        sticker_code varchar(50),
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY unique_identifier (unique_identifier)
    ) $charset_collate;",

    'locations' => "CREATE TABLE IF NOT EXISTS {$prefix}bkgt_locations (
        id int(11) NOT NULL AUTO_INCREMENT,
        name varchar(255) NOT NULL,
        slug varchar(255) NOT NULL,
        parent_id int(11) DEFAULT NULL,
        location_type enum('storage','repair','locker','warehouse','other') DEFAULT 'storage',
        address text,
        contact_person varchar(255),
        contact_phone varchar(50),
        contact_email varchar(255),
        capacity int(11) DEFAULT NULL,
        access_restrictions text,
        notes text,
        is_active tinyint(1) DEFAULT 1,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY slug (slug)
    ) $charset_collate;",

    'assignments' => "CREATE TABLE IF NOT EXISTS {$prefix}bkgt_assignments (
        id int(11) NOT NULL AUTO_INCREMENT,
        item_id int(11) NOT NULL DEFAULT 0,
        assignee_type enum('location','team','user') NOT NULL,
        assignee_id int(11) NOT NULL DEFAULT 0,
        assigned_date datetime DEFAULT CURRENT_TIMESTAMP,
        assigned_by int(11) NOT NULL DEFAULT 0,
        unassigned_date datetime NULL,
        unassigned_by int(11) NULL,
        notes text,
        PRIMARY KEY (id)
    ) $charset_collate;"
);

foreach ($tables_to_create as $table_type => $sql) {
    $table_name = $prefix . 'bkgt_' . $table_type;
    $exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'");

    if (!$exists) {
        echo "Creating $table_name...\n";
        $result = $wpdb->query($sql);

        if ($result === false) {
            echo "❌ Failed to create $table_name: " . $wpdb->last_error . "\n";
            echo "SQL: $sql\n\n";
        } else {
            echo "✅ Created $table_name successfully\n";
        }
    } else {
        echo "✅ $table_name already exists\n";
    }
}

echo "\n=== Populating Sample Data ===\n";

// Insert sample manufacturers
$manufacturers = array(
    array('Nike', 'NIKE'),
    array('Under Armour', 'UA'),
    array('Schutt', 'SCHT'),
    array('Riddell', 'RIDL')
);

foreach ($manufacturers as $manufacturer) {
    $exists = $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM {$prefix}bkgt_manufacturers WHERE manufacturer_id = %s",
        $manufacturer[1]
    ));

    if (!$exists) {
        $result = $wpdb->insert(
            "{$prefix}bkgt_manufacturers",
            array(
                'name' => $manufacturer[0],
                'manufacturer_id' => $manufacturer[1]
            )
        );

        if ($result) {
            echo "✅ Added manufacturer: {$manufacturer[0]}\n";
        } else {
            echo "❌ Failed to add manufacturer: {$manufacturer[0]}\n";
        }
    }
}

// Insert sample item types
$item_types = array(
    array('Hjälm', 'HELM'),
    array('Axelskydd', 'SHLD'),
    array('Tröja', 'SHRT'),
    array('Byxor', 'PANT'),
    array('Skor', 'SHOE')
);

foreach ($item_types as $item_type) {
    $exists = $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM {$prefix}bkgt_item_types WHERE item_type_id = %s",
        $item_type[1]
    ));

    if (!$exists) {
        $result = $wpdb->insert(
            "{$prefix}bkgt_item_types",
            array(
                'name' => $item_type[0],
                'item_type_id' => $item_type[1]
            )
        );

        if ($result) {
            echo "✅ Added item type: {$item_type[0]}\n";
        } else {
            echo "❌ Failed to add item type: {$item_type[0]}\n";
        }
    }
}

echo "\n=== Final Table Status ===\n";
foreach ($expected_tables as $table_name) {
    $full_table_name = $prefix . $table_name;
    $exists = $wpdb->get_var("SHOW TABLES LIKE '$full_table_name'");
    if ($exists) {
        $count = $wpdb->get_var("SELECT COUNT(*) FROM $full_table_name");
        echo "✅ $full_table_name exists ($count records)\n";
    } else {
        echo "❌ $full_table_name still missing\n";
    }
}

echo "\n=== Testing Shortcode ===\n";
$shortcode_result = do_shortcode('[bkgt_inventory]');
if (strpos($shortcode_result, 'wpdberror') !== false) {
    echo "❌ Still has database errors\n";
} else {
    echo "✅ No database errors in shortcode output\n";
    echo "Shortcode output length: " . strlen($shortcode_result) . " characters\n";
}