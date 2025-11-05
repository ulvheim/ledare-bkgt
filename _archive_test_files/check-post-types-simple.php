<?php
require_once('wp-load.php');

echo "Checking post types immediately after wp-load.php...\n";

if (post_type_exists('bkgt_document')) {
    echo "✓ Post type 'bkgt_document' is registered\n";
} else {
    echo "✗ Post type 'bkgt_document' is NOT registered\n";
}

if (taxonomy_exists('bkgt_doc_category')) {
    echo "✓ Taxonomy 'bkgt_doc_category' is registered\n";
} else {
    echo "✗ Taxonomy 'bkgt_doc_category' is NOT registered\n";
}
?>