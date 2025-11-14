<?php
/**
 * Alternative upload endpoint using admin-ajax.php
 * This avoids nginx restrictions on /wp-json POST requests
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handle file upload via admin-ajax
 */
function bkgt_swe3_ajax_upload_document() {
    // Verify request method
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        wp_send_json_error('Invalid request method', 400);
    }
    
    // Check if file is uploaded
    if (empty($_FILES['file'])) {
        wp_send_json_error('No file provided', 400);
    }
    
    $file = $_FILES['file'];
    $title = isset($_POST['title']) ? sanitize_text_field($_POST['title']) : 'Untitled';
    $url = isset($_POST['url']) ? esc_url($_POST['url']) : '';
    $date = isset($_POST['date']) ? sanitize_text_field($_POST['date']) : '';
    $size = isset($_POST['size']) ? intval($_POST['size']) : 0;
    
    if (empty($title) || empty($url)) {
        wp_send_json_error('Title and URL are required', 400);
    }
    
    // Validate file size
    if ($file['size'] > 100 * 1024 * 1024) {
        wp_send_json_error('File is too large (max 100MB)', 400);
    }
    
    // Validate file is PDF by extension and mime type
    $filename = strtolower($file['name']);
    if (!preg_match('/\.pdf$/', $filename)) {
        wp_send_json_error('Only PDF files are allowed', 400);
    }
    
    // Also check mime type if provided (but be lenient as content-type might vary)
    $allowed_mimes = array('application/pdf', 'text/plain', 'application/octet-stream');
    if (!in_array($file['type'], $allowed_mimes)) {
        // Log but don't block - sometimes MIME type detection varies
        error_log("Warning: Unexpected MIME type for {$filename}: {$file['type']}");
    }
    
    try {
        // Get upload directory
        $upload_dir = wp_upload_dir();
        $target_dir = $upload_dir['basedir'] . '/swe3-documents/';
        
        // Create directory
        if (!is_dir($target_dir)) {
            wp_mkdir_p($target_dir);
        }
        
        // Generate unique filename
        $filename = sanitize_file_name($title . '.pdf');
        $counter = 1;
        $original_filename = $filename;
        
        while (file_exists($target_dir . $filename)) {
            $filename = pathinfo($original_filename, PATHINFO_FILENAME) . '-' . $counter . '.pdf';
            $counter++;
        }
        
        $file_path = $target_dir . $filename;
        
        // Move uploaded file
        if (!move_uploaded_file($file['tmp_name'], $file_path)) {
            wp_send_json_error('Failed to move uploaded file', 500);
        }
        
        // Create WordPress attachment
        $attachment_id = wp_insert_attachment(
            array(
                'post_mime_type' => 'application/pdf',
                'post_title' => wp_strip_all_tags($title),
                'post_content' => '',
                'post_status' => 'inherit'
            ),
            $file_path
        );
        
        if (is_wp_error($attachment_id)) {
            @unlink($file_path);
            wp_send_json_error('Failed to create attachment', 400);
        }
        
        // Generate attachment metadata
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        $attach_data = wp_generate_attachment_metadata($attachment_id, $file_path);
        wp_update_attachment_metadata($attachment_id, $attach_data);
        
        // Create DMS document post
        $post_id = wp_insert_post(array(
            'post_type' => 'bkgt_document',
            'post_status' => 'publish',
            'post_title' => 'SWE3 - ' . wp_strip_all_tags($title),
            'post_content' => wp_sprintf(
                '<p>Official SWE3 document</p><p><strong>Original Title:</strong> %s</p><p><strong>Source:</strong> <a href="%s" target="_blank">SWE3 Website</a></p>',
                esc_html($title),
                esc_url($url)
            ),
            'post_excerpt' => 'SWE3 Official Document: ' . $title
        ));
        
        if (is_wp_error($post_id)) {
            wp_delete_attachment($attachment_id, true);
            @unlink($file_path);
            wp_send_json_error('Failed to create document post', 400);
        }
        
        // Set metadata
        update_post_meta($post_id, '_bkgt_file_url', wp_get_attachment_url($attachment_id));
        update_post_meta($post_id, '_bkgt_file_id', $attachment_id);
        update_post_meta($post_id, '_bkgt_is_swe3_document', '1');
        update_post_meta($post_id, '_bkgt_swe3_url', $url);
        update_post_meta($post_id, '_bkgt_swe3_date', $date);
        update_post_meta($post_id, '_bkgt_file_size', $size);
        
        // Set category
        $category = get_term_by('name', 'SWE3 Official Documents', 'bkgt_doc_category');
        if (!$category) {
            $category = wp_create_term('SWE3 Official Documents', 'bkgt_doc_category');
            $category_id = $category['term_id'];
        } else {
            $category_id = $category->term_id;
        }
        wp_set_object_terms($post_id, $category_id, 'bkgt_doc_category');
        
        wp_send_json_success(array(
            'post_id' => $post_id,
            'attachment_id' => $attachment_id,
            'message' => 'Document uploaded successfully'
        ));
        
    } catch (Exception $e) {
        wp_send_json_error('Upload failed: ' . $e->getMessage(), 500);
    }
}

// Register the AJAX action
add_action('wp_ajax_nopriv_swe3_upload_document', 'bkgt_swe3_ajax_upload_document');
add_action('wp_ajax_swe3_upload_document', 'bkgt_swe3_ajax_upload_document');
