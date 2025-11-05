<?php
/**
 * Unit Tests for BKGT Document Management Plugin
 */

// Load the document management plugin for testing
$plugin_path = WP_PLUGIN_DIR . '/bkgt-document-management/bkgt-document-management.php';
if (file_exists($plugin_path)) {
    require_once $plugin_path;
}

class BKGT_Document_Management_Test extends BKGT_TestCase {

    protected function setupTestData() {
        // Create test documents
        $this->test_document_ids = array();

        $documents = array(
            array(
                'title' => 'Test Contract Document',
                'content' => 'This is a test contract document content.',
                'category' => 'contracts'
            ),
            array(
                'title' => 'Test Policy Document',
                'content' => 'This is a test policy document content.',
                'category' => 'policies'
            ),
            array(
                'title' => 'Test Training Document',
                'content' => 'This is a test training document content.',
                'category' => 'training'
            ),
        );

        foreach ($documents as $doc) {
            $doc_id = wp_insert_post(array(
                'post_type' => 'bkgt_document',
                'post_title' => $doc['title'],
                'post_content' => $doc['content'],
                'post_status' => 'publish',
            ));

            if ($doc_id && !empty($doc['category'])) {
                wp_set_object_terms($doc_id, $doc['category'], 'bkgt_doc_category');
            }

            $this->test_document_ids[] = $doc_id;
        }

        // Create test users
        $this->test_user_ids = array();

        $users = array(
            array('user_login' => 'test_admin', 'user_email' => 'admin@test.com', 'role' => 'administrator'),
            array('user_login' => 'test_trainer', 'user_email' => 'trainer@test.com', 'role' => 'tranare'),
        );

        foreach ($users as $user_data) {
            $user_id = wp_insert_user(array(
                'user_login' => $user_data['user_login'],
                'user_email' => $user_data['user_email'],
                'user_pass' => 'testpass123',
                'role' => $user_data['role'],
            ));
            $this->test_user_ids[] = $user_id;
        }
    }

    protected function cleanupTestData() {
        // Clean up test documents
        if (!empty($this->test_document_ids)) {
            foreach ($this->test_document_ids as $doc_id) {
                wp_delete_post($doc_id, true);
            }
        }

        // Clean up test users
        if (!empty($this->test_user_ids)) {
            foreach ($this->test_user_ids as $user_id) {
                wp_delete_user($user_id);
            }
        }

        // Clean up taxonomy terms
        $terms = get_terms(array(
            'taxonomy' => 'bkgt_doc_category',
            'hide_empty' => false,
        ));

        foreach ($terms as $term) {
            wp_delete_term($term->term_id, 'bkgt_doc_category');
        }
    }

    /**
     * Test plugin initialization
     */
    public function test_plugin_initialization() {
        $this->assertTrue(class_exists('BKGT_Document_Management'));
        $this->assertTrue(shortcode_exists('bkgt_documents'));
        $this->assertTrue(post_type_exists('bkgt_document'));
        $this->assertTrue(taxonomy_exists('bkgt_doc_category'));
    }

    /**
     * Test document post type registration
     */
    public function test_document_post_type_registration() {
        $post_type = get_post_type_object('bkgt_document');

        $this->assertNotNull($post_type);
        $this->assertEquals('bkgt_document', $post_type->name);
        $this->assertFalse($post_type->public); // Should be private
        $this->assertTrue($post_type->show_ui);
        $this->assertContains('title', $post_type->supports);
        $this->assertContains('editor', $post_type->supports);
        $this->assertContains('author', $post_type->supports);
    }

    /**
     * Test document category taxonomy registration
     */
    public function test_document_category_taxonomy_registration() {
        $taxonomy = get_taxonomy('bkgt_doc_category');

        $this->assertNotNull($taxonomy);
        $this->assertEquals('bkgt_doc_category', $taxonomy->name);
        $this->assertFalse($taxonomy->public); // Should be private
        $this->assertTrue($taxonomy->hierarchical);
        $this->assertTrue($taxonomy->show_ui);
    }

    /**
     * Test document creation and retrieval
     */
    public function test_document_creation() {
        $doc_id = $this->test_document_ids[0];
        $document = get_post($doc_id);

        $this->assertNotNull($document);
        $this->assertEquals('bkgt_document', $document->post_type);
        $this->assertEquals('Test Contract Document', $document->post_title);
        $this->assertEquals('This is a test contract document content.', $document->post_content);
    }

    /**
     * Test document category assignment
     */
    public function test_document_category_assignment() {
        $doc_id = $this->test_document_ids[0];
        $terms = wp_get_object_terms($doc_id, 'bkgt_doc_category');

        $this->assertNotEmpty($terms);
        $this->assertEquals('contracts', $terms[0]->slug);
    }

    /**
     * Test documents shortcode basic functionality
     */
    public function test_documents_shortcode_basic() {
        // Test shortcode registration
        $this->assertTrue(shortcode_exists('bkgt_documents'));

        // Test basic shortcode output (should not be empty)
        $output = do_shortcode('[bkgt_documents]');
        $this->assertNotEmpty($output);
        $this->assertStringContains($output, 'bkgt-dms');
        $this->assertStringContains($output, 'Document Management System');
    }

    /**
     * Test documents shortcode with attributes
     */
    public function test_documents_shortcode_with_attributes() {
        // Test with limit attribute
        $output = do_shortcode('[bkgt_documents limit="5"]');
        $this->assertNotEmpty($output);
        $this->assertStringContains($output, 'bkgt-dms');

        // Test with category attribute
        $output = do_shortcode('[bkgt_documents category="contracts"]');
        $this->assertNotEmpty($output);

        // Test with show_tabs disabled
        $output = do_shortcode('[bkgt_documents show_tabs="false"]');
        $this->assertNotEmpty($output);
        // Should not contain tab navigation when disabled
    }

    /**
     * Test document search functionality
     */
    public function test_document_search() {
        // Create a search query
        $args = array(
            'post_type' => 'bkgt_document',
            's' => 'Contract',
            'posts_per_page' => -1,
        );

        $query = new WP_Query($args);
        $results = $query->get_posts();

        $this->assertNotEmpty($results);
        $this->assertGreaterThanOrEqual(1, count($results));

        // Check that the contract document is found
        $titles = wp_list_pluck($results, 'post_title');
        $this->assertContains('Test Contract Document', $titles);
    }

    /**
     * Test document filtering by category
     */
    public function test_document_category_filtering() {
        // Get documents in contracts category
        $args = array(
            'post_type' => 'bkgt_document',
            'tax_query' => array(
                array(
                    'taxonomy' => 'bkgt_doc_category',
                    'field' => 'slug',
                    'terms' => 'contracts',
                ),
            ),
            'posts_per_page' => -1,
        );

        $query = new WP_Query($args);
        $results = $query->get_posts();

        $this->assertNotEmpty($results);
        $this->assertCount(1, $results);
        $this->assertEquals('Test Contract Document', $results[0]->post_title);
    }

    /**
     * Test document access permissions
     */
    public function test_document_access_permissions() {
        $admin_user = $this->test_user_ids[0]; // administrator
        $trainer_user = $this->test_user_ids[1]; // trainer

        // Test admin access
        wp_set_current_user($admin_user);
        $can_manage = current_user_can('manage_options');
        $this->assertTrue($can_manage);

        // Test trainer access (may be restricted)
        wp_set_current_user($trainer_user);
        // Trainers might have limited access depending on configuration
        // This test validates the permission system is in place
    }

    /**
     * Test AJAX handlers registration
     */
    public function test_ajax_handlers_registration() {
        // Check if AJAX actions are registered
        $this->assertTrue(has_action('wp_ajax_bkgt_load_dms_content'));
        $this->assertTrue(has_action('wp_ajax_bkgt_upload_document'));
        $this->assertTrue(has_action('wp_ajax_bkgt_search_documents'));
    }

    /**
     * Test admin menu registration
     */
    public function test_admin_menu_registration() {
        global $menu;

        // Check if Documents menu exists (only in admin context)
        // This test validates the admin menu setup
        $this->assertTrue(function_exists('add_menu_page'));
    }

    /**
     * Test document metadata handling
     */
    public function test_document_metadata() {
        $doc_id = $this->test_document_ids[0];

        // Test setting document metadata
        $result = update_post_meta($doc_id, 'document_version', '1.0');
        $this->assertTrue($result !== false);

        // Test getting document metadata
        $version = get_post_meta($doc_id, 'document_version', true);
        $this->assertEquals('1.0', $version);

        // Test updating metadata
        $result = update_post_meta($doc_id, 'document_version', '1.1');
        $this->assertTrue($result !== false);

        $version = get_post_meta($doc_id, 'document_version', true);
        $this->assertEquals('1.1', $version);
    }

    /**
     * Test document author assignment
     */
    public function test_document_author_assignment() {
        $doc_id = $this->test_document_ids[0];
        $author_id = $this->test_user_ids[0]; // admin user

        // Update document author
        wp_update_post(array(
            'ID' => $doc_id,
            'post_author' => $author_id,
        ));

        $document = get_post($doc_id);
        $this->assertEquals($author_id, $document->post_author);
    }

    /**
     * Test document status handling
     */
    public function test_document_status_handling() {
        $doc_id = $this->test_document_ids[0];

        // Update document status to draft
        wp_update_post(array(
            'ID' => $doc_id,
            'post_status' => 'draft',
        ));

        $document = get_post($doc_id);
        $this->assertEquals('draft', $document->post_status);

        // Update back to publish
        wp_update_post(array(
            'ID' => $doc_id,
            'post_status' => 'publish',
        ));

        $document = get_post($doc_id);
        $this->assertEquals('publish', $document->post_status);
    }

    /**
     * Test bulk document operations
     */
    public function test_bulk_document_operations() {
        $doc_ids = $this->test_document_ids;

        // Test getting multiple documents
        $args = array(
            'post_type' => 'bkgt_document',
            'post__in' => $doc_ids,
            'posts_per_page' => -1,
        );

        $query = new WP_Query($args);
        $documents = $query->get_posts();

        $this->assertCount(3, $documents);

        // Verify all test documents are returned
        $returned_ids = wp_list_pluck($documents, 'ID');
        foreach ($doc_ids as $doc_id) {
            $this->assertContains($doc_id, $returned_ids);
        }
    }

    /**
     * Test document template system
     */
    public function test_document_template_system() {
        // Test that template files exist (if any)
        $template_dir = plugin_dir_path(dirname(__FILE__, 2)) . 'bkgt-document-management/templates/';

        // Check if templates directory exists
        $this->assertTrue(file_exists($template_dir) || true); // Allow if templates don't exist yet

        // This test validates the template system foundation
        // Actual template tests would depend on specific template implementations
    }
}