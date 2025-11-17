<?php
/**
 * SWE3 DMS integration class
 */

if (!defined('ABSPATH')) {
    exit;
}

class BKGT_SWE3_DMS_Integration {

    /**
     * DMS category name for SWE3 documents
     */
    const DMS_CATEGORY_NAME = 'SWE3 Official Documents';

    /**
     * DMS document prefix
     */
    const DMS_PREFIX = 'SWE3-';

    /**
     * Constructor - ensure DMS classes are loaded
     */
    public function __construct() {
        $this->ensure_dms_classes_loaded();
    }

    /**
     * Ensure DMS plugin classes are loaded
     */
    private function ensure_dms_classes_loaded() {
        // Check if DMS plugin is active
        if (!is_plugin_active('bkgt-document-management/bkgt-document-management.php')) {
            $this->log('error', 'DMS plugin is not active');
            return false;
        }

        // Load DMS classes if not already loaded
        if (!class_exists('BKGT_Document')) {
            $dms_plugin_dir = WP_PLUGIN_DIR . '/bkgt-document-management/';

            // Load core DMS classes
            $classes_to_load = array(
                'includes/class-document.php',
                'includes/class-database.php',
                'includes/class-category.php',
                'includes/class-access.php'
            );

            foreach ($classes_to_load as $class_file) {
                $file_path = $dms_plugin_dir . $class_file;
                if (file_exists($file_path)) {
                    require_once $file_path;
                }
            }
        }

        return class_exists('BKGT_Document');
    }

    /**
     * Get DMS post type
     */
    private function get_dms_post_type() {
        return 'bkgt_document';
    }

    /**
     * Create or update DMS document
     */
    public function create_or_update_document($document, $file_path, $metadata, $file_hash) {
        // Ensure DMS classes are loaded
        if (!$this->ensure_dms_classes_loaded()) {
            $this->log('error', 'Failed to load DMS classes');
            return false;
        }

        // Check if document already exists in DMS
        $swe3_url = isset($document['url']) ? $document['url'] : $document['swe3_url'];
        $existing_dms_doc = $this->find_existing_dms_document($swe3_url);

        if ($existing_dms_doc) {
            // Update existing document
            return $this->update_dms_document($existing_dms_doc, $document, $file_path, $metadata);
        } else {
            // Create new document
            return $this->create_dms_document($document, $file_path, $metadata);
        }
    }

    /**
     * Create new DMS document
     */
    public function create_dms_document($document, $file_path, $metadata) {
        $this->log('info', sprintf('Creating new DMS document: %s', $document['title']));

        // Check if DMS plugin is available
        if (!class_exists('BKGT_Document')) {
            $this->log('error', 'DMS plugin not available - BKGT_Document class not found');
            return false;
        }

        // Generate DMS title with prefix
        $dms_title = $this->generate_dms_title($document, $metadata);

        // Prepare document data for DMS API
        $document_data = array(
            'post_title' => $dms_title,
            'post_content' => $this->generate_document_content($document, $metadata),
            'post_status' => 'publish',
        );

        // Use DMS plugin's proper API to create document
        $dms_document = BKGT_Document::create($document_data);

        if (is_wp_error($dms_document)) {
            $this->log('error', 'Failed to create DMS document: ' . $dms_document->get_error_message());
            return false;
        }

        $post_id = $dms_document->get_id();

        // Set document category
        $this->set_document_category($post_id);

        // Attach the file
        $attachment_id = $this->attach_file_to_document($post_id, $file_path);

        if (!$attachment_id) {
            $this->log('error', 'Failed to attach file to DMS document');
            // Don't delete the post, just log the error
        }

        // Set metadata
        $this->set_document_metadata($post_id, $document, $metadata, $attachment_id);

        // Set permissions (public access)
        $this->set_document_permissions($post_id);

        $this->log('info', sprintf('Created DMS document with ID: %d', $post_id));

        return $post_id;
    }

    /**
     * Update existing DMS document
     */
    public function update_dms_document($existing_doc, $document, $file_path, $metadata) {
        $this->log('info', sprintf('Updating existing DMS document: %s', $document['title']));

        // Check if DMS plugin is available
        if (!class_exists('BKGT_Document')) {
            $this->log('error', 'DMS plugin not available - BKGT_Document class not found');
            return false;
        }

        $post_id = $existing_doc->ID;

        // Load existing document using DMS API
        $dms_document = new BKGT_Document($post_id);

        if (!$dms_document->get_id()) {
            $this->log('error', 'Failed to load existing DMS document');
            return false;
        }

        // Update document content using DMS API
        $update_data = array(
            'post_title' => $this->generate_dms_title($document, $metadata),
            'post_content' => $this->generate_document_content($document, $metadata),
        );

        $result = $dms_document->update($update_data);

        if (is_wp_error($result)) {
            $this->log('error', 'Failed to update DMS document: ' . $result->get_error_message());
            return false;
        }

        // Update attachment if file has changed
        $this->update_document_attachment($post_id, $file_path);

        // Update metadata
        $this->set_document_metadata($post_id, $document, $metadata);

        $this->log('info', sprintf('Updated DMS document with ID: %d', $post_id));

        return $post_id;
    }

    /**
     * Find existing DMS document by SWE3 URL
     */
    private function find_existing_dms_document($swe3_url) {
        $args = array(
            'post_type' => $this->get_dms_post_type(),
            'meta_query' => array(
                array(
                    'key' => '_bkgt_swe3_url',
                    'value' => $swe3_url,
                    'compare' => '='
                )
            ),
            'posts_per_page' => 1,
        );

        $query = new WP_Query($args);

        if ($query->have_posts()) {
            return $query->posts[0];
        }

        return null;
    }

    /**
     * Generate DMS title with SWE3 prefix
     */
    private function generate_dms_title($document, $metadata) {
        $prefix = self::DMS_PREFIX;
        $type_label = $this->get_type_label($document['type']);
        $title = $document['title'];
        $version = !empty($metadata['version']) ? ' v' . $metadata['version'] : '';

        return sprintf('%s%s - %s%s', $prefix, $type_label, $title, $version);
    }

    /**
     * Get human-readable type label
     */
    private function get_type_label($type) {
        $labels = array(
            'competition-regulations' => 'Competition Regulations',
            'game-rules' => 'Game Rules',
            'referee-guidelines' => 'Referee Guidelines',
            'development-series' => 'Development Series',
            'safety-medical' => 'Safety & Medical',
            'easy-football' => 'Easy Football',
            'competition-formats' => 'Competition Formats',
            'general' => 'General',
        );

        return isset($labels[$type]) ? $labels[$type] : ucfirst(str_replace('-', ' ', $type));
    }

    /**
     * Generate document content
     */
    private function generate_document_content($document, $metadata) {
        $content = sprintf(
            '<p>This is an official document from the Swedish American Football Federation (SWE3).</p>' .
            '<p><strong>Original Title:</strong> %s</p>' .
            '<p><strong>Document Type:</strong> %s</p>' .
            '<p><strong>Source:</strong> <a href="%s" target="_blank">SWE3 Website</a></p>',
            esc_html($document['title']),
            esc_html($this->get_type_label($document['type'])),
            esc_url($document['url'])
        );

        if (!empty($metadata['version'])) {
            $content .= sprintf('<p><strong>Version:</strong> %s</p>', esc_html($metadata['version']));
        }

        if (!empty($metadata['publication_date'])) {
            $content .= sprintf('<p><strong>Publication Date:</strong> %s</p>', esc_html($metadata['publication_date']));
        }

        if (!empty($metadata['description'])) {
            $content .= sprintf('<p><strong>Description:</strong> %s</p>', esc_html($metadata['description']));
        }

        $content .= '<p><em>This document is automatically updated from the official SWE3 website.</em></p>';

        return $content;
    }

    /**
     * Generate document excerpt
     */
    private function generate_document_excerpt($document, $metadata) {
        $excerpt = sprintf(
            'Official SWE3 %s document: %s',
            $this->get_type_label($document['type']),
            $document['title']
        );

        if (!empty($metadata['version'])) {
            $excerpt .= ' (Version ' . $metadata['version'] . ')';
        }

        return $excerpt;
    }

    /**
     * Set document category
     */
    private function set_document_category($post_id) {
        $category_name = self::DMS_CATEGORY_NAME;

        // Check if category exists
        $category = get_term_by('name', $category_name, 'category');

        if (!$category) {
            // Create category
            $category_id = wp_create_category($category_name);
        } else {
            $category_id = $category->term_id;
        }

        // Assign category to post
        wp_set_post_categories($post_id, array($category_id));
    }

    /**
     * Attach file to document
     */
    private function attach_file_to_document($post_id, $file_path) {
        // Check if file exists
        if (!file_exists($file_path)) {
            $this->log('error', 'File does not exist: ' . $file_path);
            return false;
        }

        // Prepare file data
        $file_name = basename($file_path);
        $file_type = wp_check_filetype($file_name);

        // Handle upload
        $upload_dir = wp_upload_dir();
        $target_path = $upload_dir['path'] . '/' . $file_name;

        // If file is not already in uploads directory, copy it
        if ($file_path !== $target_path) {
            if (!copy($file_path, $target_path)) {
                $this->log('error', 'Failed to copy file to uploads directory');
                return false;
            }
        }

        // Create attachment
        $attachment_data = array(
            'guid' => $upload_dir['url'] . '/' . $file_name,
            'post_mime_type' => $file_type['type'],
            'post_title' => $file_name,
            'post_content' => '',
            'post_status' => 'inherit'
        );

        $attachment_id = wp_insert_attachment($attachment_data, $target_path, $post_id);

        if (is_wp_error($attachment_id)) {
            $this->log('error', 'Failed to create attachment: ' . $attachment_id->get_error_message());
            return false;
        }

        // Generate metadata for attachment
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        $attachment_metadata = wp_generate_attachment_metadata($attachment_id, $target_path);
        wp_update_attachment_metadata($attachment_id, $attachment_metadata);

        return $attachment_id;
    }

    /**
     * Update document attachment
     */
    private function update_document_attachment($post_id, $file_path) {
        // Get existing attachment
        $existing_attachments = get_attached_media('', $post_id);

        if (!empty($existing_attachments)) {
            // Update the first attachment found
            $attachment_id = key($existing_attachments);
            $this->replace_attachment_file($attachment_id, $file_path);
        } else {
            // Create new attachment
            $this->attach_file_to_document($post_id, $file_path);
        }
    }

    /**
     * Replace attachment file
     */
    private function replace_attachment_file($attachment_id, $new_file_path) {
        $attachment = get_post($attachment_id);

        if (!$attachment) {
            return false;
        }

        $old_file_path = get_attached_file($attachment_id);

        // Copy new file over old file
        if (!copy($new_file_path, $old_file_path)) {
            $this->log('error', 'Failed to replace attachment file');
            return false;
        }

        // Update attachment metadata
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        $attachment_metadata = wp_generate_attachment_metadata($attachment_id, $old_file_path);
        wp_update_attachment_metadata($attachment_id, $attachment_metadata);

        return true;
    }

    /**
     * Set document metadata
     */
    private function set_document_metadata($post_id, $document, $metadata, $attachment_id = null) {
        // Get attachment data for DMS API compatibility
        $file_url = '';
        $file_path = '';
        $file_size = 0;
        $mime_type = '';

        if ($attachment_id) {
            $attachment = get_post($attachment_id);
            if ($attachment) {
                $file_url = wp_get_attachment_url($attachment_id);
                $file_path = get_attached_file($attachment_id);
                $file_size = filesize($file_path);
                $mime_type = $attachment->post_mime_type;
            }
        }

        // DMS API compatible metadata (same as upload_document method)
        update_post_meta($post_id, '_bkgt_file_url', $file_url);
        update_post_meta($post_id, '_bkgt_file_path', str_replace(wp_get_upload_dir()['basedir'] . '/', '', $file_path));
        update_post_meta($post_id, '_bkgt_file_size', $file_size);
        update_post_meta($post_id, '_bkgt_mime_type', $mime_type);

        // Document type and category for DMS API
        update_post_meta($post_id, '_bkgt_document_type', $this->map_swe3_type_to_dms_type($document['type']));

        // SWE3 specific metadata
        $swe3_url = isset($document['url']) ? $document['url'] : $document['swe3_url'];
        update_post_meta($post_id, '_bkgt_swe3_url', $swe3_url);
        update_post_meta($post_id, '_bkgt_swe3_type', $document['type']);
        update_post_meta($post_id, '_bkgt_swe3_title', $document['title']);
        update_post_meta($post_id, '_bkgt_swe3_version', $metadata['version']);
        update_post_meta($post_id, '_bkgt_swe3_publication_date', $metadata['publication_date']);
        update_post_meta($post_id, '_bkgt_swe3_description', $metadata['description']);
        update_post_meta($post_id, '_bkgt_swe3_last_updated', current_time('mysql'));

        if ($attachment_id) {
            update_post_meta($post_id, '_bkgt_swe3_attachment_id', $attachment_id);
        }

        // Mark as SWE3 document
        update_post_meta($post_id, '_bkgt_is_swe3_document', '1');
    }

    /**
     * Set document permissions for public access
     */
    private function set_document_permissions($post_id) {
        // Ensure the document is published and publicly accessible
        // This may need to be adjusted based on your DMS permission system

        // Remove any password protection
        update_post_meta($post_id, '_edit_lock', '');
        update_post_meta($post_id, '_edit_last', '');

        // Set visibility to public
        $post = get_post($post_id);
        if ($post->post_status !== 'publish') {
            wp_update_post(array(
                'ID' => $post_id,
                'post_status' => 'publish'
            ));
        }
    }

    /**
     * Get DMS documents by type
     */
    public function get_documents_by_type($type = null, $limit = -1) {
        $args = array(
            'post_type' => $this->get_dms_post_type(),
            'meta_query' => array(
                array(
                    'key' => '_bkgt_is_swe3_document',
                    'value' => '1',
                    'compare' => '='
                )
            ),
            'posts_per_page' => $limit,
            'orderby' => 'date',
            'order' => 'DESC',
        );

        if ($type) {
            $args['meta_query'][] = array(
                'key' => '_bkgt_swe3_type',
                'value' => $type,
                'compare' => '='
            );
        }

        return get_posts($args);
    }

    /**
     * Get document statistics
     */
    public function get_document_statistics() {
        global $wpdb;

        $stats = array(
            'total_documents' => 0,
            'documents_by_type' => array(),
            'last_updated' => null,
        );

        // Total documents
        $total = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->posts} p
             INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
             WHERE p.post_type = %s
             AND pm.meta_key = '_bkgt_is_swe3_document'
             AND pm.meta_value = '1'
             AND p.post_status = 'publish'",
            $this->get_dms_post_type()
        ));

        $stats['total_documents'] = intval($total);

        // Documents by type
        $type_results = $wpdb->get_results($wpdb->prepare(
            "SELECT pm2.meta_value as doc_type, COUNT(*) as count
             FROM {$wpdb->posts} p
             INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
             INNER JOIN {$wpdb->postmeta} pm2 ON p.ID = pm2.post_id
             WHERE p.post_type = %s
             AND pm.meta_key = '_bkgt_is_swe3_document'
             AND pm.meta_value = '1'
             AND pm2.meta_key = '_bkgt_swe3_type'
             AND p.post_status = 'publish'
             GROUP BY pm2.meta_value",
            $this->get_dms_post_type()
        ));

        foreach ($type_results as $result) {
            $stats['documents_by_type'][$result->doc_type] = intval($result->count);
        }

        // Last updated
        $last_updated = $wpdb->get_var($wpdb->prepare(
            "SELECT pm.meta_value
             FROM {$wpdb->posts} p
             INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
             WHERE p.post_type = %s
             AND pm.meta_key = '_bkgt_swe3_last_updated'
             AND p.post_status = 'publish'
             ORDER BY pm.meta_value DESC
             LIMIT 1",
            $this->get_dms_post_type()
        ));

        $stats['last_updated'] = $last_updated;

        return $stats;
    }

    /**
     * Clean up orphaned SWE3 documents
     */
    public function cleanup_orphaned_documents() {
        global $wpdb;

        // Find documents that are no longer in the SWE3 database
        $orphaned_posts = $wpdb->get_results($wpdb->prepare(
            "SELECT p.ID, p.post_title
             FROM {$wpdb->posts} p
             INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
             LEFT JOIN {$wpdb->prefix}bkgt_swe3_documents sw ON pm.meta_value = sw.swe3_url
             WHERE p.post_type = %s
             AND pm.meta_key = '_bkgt_swe3_url'
             AND sw.id IS NULL
             AND p.post_status = 'publish'",
            $this->get_dms_post_type()
        ));

        $cleaned_count = 0;
        foreach ($orphaned_posts as $post) {
            // Move to trash instead of permanent deletion
            wp_trash_post($post->ID);
            $this->log('info', sprintf('Moved orphaned SWE3 document to trash: %s (ID: %d)', $post->post_title, $post->ID));
            $cleaned_count++;
        }

        return $cleaned_count;
    }

    /**
     * Log message
     */
    private function log($level, $message) {
        if (method_exists(bkgt_swe3_scraper()->scraper, 'log')) {
            bkgt_swe3_scraper()->scraper->log($level, '[DMS Integration] ' . $message);
        } else {
            error_log(sprintf('[BKGT SWE3 DMS] [%s] %s', strtoupper($level), $message));
        }
    }

    /**
     * Map SWE3 document types to DMS document types
     */
    private function map_swe3_type_to_dms_type($swe3_type) {
        $type_mapping = array(
            'competition-regulations' => 'rules',
            'game-rules' => 'rules',
            'referee-guidelines' => 'rules',
            'development-series' => 'rules',
            'safety-medical' => 'rules',
            'easy-football' => 'rules',
            'competition-formats' => 'rules',
            'general' => 'other',
        );

        return isset($type_mapping[$swe3_type]) ? $type_mapping[$swe3_type] : 'other';
    }
}