<?php
require_once('wp-load.php');

// Hook into init to check after post types are registered
add_action('init', 'check_post_types_after_init', 20);

function check_post_types_after_init() {
    echo "Checking post types after init hook...\n";

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
}