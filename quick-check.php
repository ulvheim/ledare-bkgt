<?php
/**
 * Quick Team Count Check
 */

require_once('wp-load.php');

if (!defined('ABSPATH')) {
    die('Direct access not allowed');
}

global $wpdb;

$table_name = $wpdb->prefix . 'bkgt_teams';
$teams = $wpdb->get_results("SELECT id, name FROM $table_name ORDER BY name");

echo count($teams) . " teams in database:\n";
foreach ($teams as $team) {
    echo "- {$team->name}\n";
}
?>