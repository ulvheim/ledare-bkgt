<?php
/**
 * Database setup for BKGT Offboarding System
 */

if (!defined('ABSPATH')) {
    exit;
}

class BKGT_Offboarding_Database {

    public static function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        // Offboarding tasks table (for detailed task tracking)
        $tasks_table = $wpdb->prefix . 'bkgt_offboarding_tasks';
        $sql = "CREATE TABLE IF NOT EXISTS $tasks_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            offboarding_id mediumint(9) NOT NULL,
            task_title varchar(255) NOT NULL,
            task_description text,
            is_required tinyint(1) DEFAULT 1,
            is_completed tinyint(1) DEFAULT 0,
            completed_date datetime DEFAULT NULL,
            completed_by bigint(20) unsigned DEFAULT NULL,
            created_date datetime DEFAULT CURRENT_TIMESTAMP,
            updated_date datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY offboarding_id (offboarding_id),
            KEY is_completed (is_completed)
        ) $charset_collate;";
        $wpdb->query($sql);

        // Offboarding equipment tracking table
        $equipment_table = $wpdb->prefix . 'bkgt_offboarding_equipment';
        $sql = "CREATE TABLE IF NOT EXISTS $equipment_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            offboarding_id mediumint(9) NOT NULL,
            assignment_id mediumint(9) NOT NULL,
            is_returned tinyint(1) DEFAULT 0,
            returned_date datetime DEFAULT NULL,
            returned_condition varchar(100) DEFAULT NULL,
            notes text,
            created_date datetime DEFAULT CURRENT_TIMESTAMP,
            updated_date datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY offboarding_id (offboarding_id),
            KEY assignment_id (assignment_id),
            KEY is_returned (is_returned)
        ) $charset_collate;";
        $wpdb->query($sql);

        // Offboarding notifications table
        $notifications_table = $wpdb->prefix . 'bkgt_offboarding_notifications';
        $sql = "CREATE TABLE IF NOT EXISTS $notifications_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            offboarding_id mediumint(9) NOT NULL,
            notification_type varchar(50) NOT NULL,
            recipient_id bigint(20) unsigned NOT NULL,
            sent_date datetime DEFAULT NULL,
            is_read tinyint(1) DEFAULT 0,
            created_date datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY offboarding_id (offboarding_id),
            KEY recipient_id (recipient_id),
            KEY notification_type (notification_type)
        ) $charset_collate;";
        $wpdb->query($sql);
    }

    public static function upgrade_tables() {
        global $wpdb;

        // Check if we need to add any missing columns or tables
        // This would be used for future updates
    }
}

// Hook into plugin activation
register_activation_hook(plugin_dir_path(__DIR__) . 'bkgt-offboarding.php', array('BKGT_Offboarding_Database', 'create_tables'));
?>