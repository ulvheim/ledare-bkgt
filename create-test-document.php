<?php
require_once('wp-load.php');

// Create a test document
$document_data = array(
    'post_title' => 'Test Document 1',
    'post_content' => 'This is a test document to demonstrate the document management system. It contains sample content that would typically be a document or file description.',
    'post_status' => 'publish',
    'post_type' => 'bkgt_document',
    'post_author' => 1,
);

$document_id = wp_insert_post($document_data);

if ($document_id && !is_wp_error($document_id)) {
    echo "Test document created successfully with ID: $document_id\n";

    // Add it to a category
    wp_set_object_terms($document_id, 'General', 'bkgt_doc_category');

    echo "Document added to 'General' category\n";
} else {
    echo "Failed to create document\n";
}
?>