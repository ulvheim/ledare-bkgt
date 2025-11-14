<?php
/**
 * SWE3 REST API Upload Endpoint
 * Allows Python scraper to upload documents to WordPress DMS
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('rest_api_init', function() {
    register_rest_route('bkgt/v1', '/swe3-upload-document', array(
        'methods' => array('POST'),
        'callback' => 'bkgt_swe3_rest_upload_document',
        'permission_callback' => function() {
            return true; // Allow all requests
        },
        'args' => array()  // Empty args to allow raw multipart processing
    ));
});

function bkgt_swe3_rest_upload_document($request) {
    // Error log for debugging
    error_log('[SWE3 Upload] Request received');
    error_log('[SWE3 Upload] Request method: ' . $request->get_method());
    error_log('[SWE3 Upload] Request params: ' . json_encode($request->get_params()));
    
    // Get the uploaded file from request
    $files = $request->get_file_params();
    error_log('[SWE3 Upload] Files received: ' . json_encode(array_keys($files)));
    
    if (empty($files) || !isset($files['file'])) {
        error_log('[SWE3 Upload] Error: No file in request');
        return new WP_Error(
            'no_file',
            'No file provided in request',
            array('status' => 400)
        );
    }
    
    $uploaded_file = $files['file'];
    error_log('[SWE3 Upload] File info: ' . json_encode($uploaded_file));
    
    // Get parameters from request body (POST data)
    $params = json_decode(file_get_contents('php://input'), true);
    
    // Fallback to regular params if JSON didn't work
    if (!$params) {
        $params = $request->get_json_params();
    }
    if (!$params) {
        $params = $request->get_body_params();
    }
    
    $title = isset($params['title']) ? $params['title'] : $request->get_param('title');
    $url = isset($params['url']) ? $params['url'] : $request->get_param('url');
    $date = isset($params['date']) ? $params['date'] : $request->get_param('date');
    $size = isset($params['size']) ? $params['size'] : $request->get_param('size');
    
    error_log('[SWE3 Upload] Title: ' . $title);
    error_log('[SWE3 Upload] URL: ' . $url);
    
    if (empty($title) || empty($url)) {
        error_log('[SWE3 Upload] Error: Missing title or URL');
        return new WP_Error(
            'missing_params',
            'Title and URL are required',
            array('status' => 400)
        );
    }
    
    // Check file size (max 100MB)
    if ($uploaded_file['size'] > 100 * 1024 * 1024) {
        error_log('[SWE3 Upload] Error: File too large');
        return new WP_Error(
            'file_too_large',
            'File is too large (max 100MB)',
            array('status' => 400)
        );
    }
    
    try {
        // Move uploaded file to WordPress uploads directory
        $upload_dir = wp_upload_dir();
        $target_dir = $upload_dir['basedir'] . '/swe3-documents/';
        
        error_log('[SWE3 Upload] Target dir: ' . $target_dir);
        
        // Create directory if it doesn't exist
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
        
        error_log('[SWE3 Upload] Target file: ' . $file_path);
        error_log('[SWE3 Upload] Temp file: ' . $uploaded_file['tmp_name']);
        
        // Move the file
        if (!move_uploaded_file($uploaded_file['tmp_name'], $file_path)) {
            error_log('[SWE3 Upload] Error: Failed to move uploaded file');
            return new WP_Error(
                'move_failed',
                'Failed to move uploaded file',
                array('status' => 500)
            );
        }
        
        error_log('[SWE3 Upload] File moved successfully');
        
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
            error_log('[SWE3 Upload] Error: Failed to create attachment: ' . $attachment_id->get_error_message());
            @unlink($file_path);
            return new WP_Error(
                'attachment_failed',
                'Failed to create attachment',
                array('status' => 400)
            );
        }
        
        error_log('[SWE3 Upload] Attachment created: ' . $attachment_id);
        
        // Require wp-admin/includes/image.php for wp_generate_attachment_metadata
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
            error_log('[SWE3 Upload] Error: Failed to create post: ' . $post_id->get_error_message());
            wp_delete_attachment($attachment_id, true);
            @unlink($file_path);
            return new WP_Error(
                'post_creation_failed',
                'Failed to create document post',
                array('status' => 400)
            );
        }
        
        error_log('[SWE3 Upload] Document post created: ' . $post_id);
        
        // Attach file to post
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
        
        error_log('[SWE3 Upload] Success: Document uploaded as post ' . $post_id);
        
        return new WP_REST_Response(array(
            'success' => true,
            'post_id' => $post_id,
            'attachment_id' => $attachment_id,
            'message' => 'Document uploaded successfully'
        ), 200);
        
    } catch (Exception $e) {
        error_log('[SWE3 Upload] Exception: ' . $e->getMessage());
        return new WP_Error(
            'upload_error',
            'Upload failed: ' . $e->getMessage(),
            array('status' => 500)
        );
    }
}
