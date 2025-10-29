<?php
// Database setup script for BKGT Team & Player Management
require_once('../../../wp-load.php');
require_once('../includes/class-database.php');

try {
    BKGT_Team_Player_Database::create_tables();
    echo "Database tables created successfully!\n";
} catch (Exception $e) {
    echo "Error creating database tables: " . $e->getMessage() . "\n";
}
?>