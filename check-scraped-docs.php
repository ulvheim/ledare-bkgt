<?php
require_once '../../../wp-load.php';

global $wpdb;
$table_name = $wpdb->prefix . 'bkgt_swe3_documents';
$count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");

echo "Documents in database: $count\n";

if ($count > 0) {
    $documents = $wpdb->get_results("SELECT title, document_type, status FROM $table_name LIMIT 5");
    echo "Recent documents:\n";
    foreach ($documents as $doc) {
        echo "  - {$doc->title} ({$doc->document_type}) - {$doc->status}\n";
    }
}
?>