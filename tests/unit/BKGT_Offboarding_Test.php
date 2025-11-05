<?php
/**
 * Unit Tests for BKGT Offboarding Plugin
 */

// Load the offboarding plugin for testing
$plugin_path = WP_PLUGIN_DIR . '/bkgt-offboarding/bkgt-offboarding.php';
if (file_exists($plugin_path)) {
    require_once $plugin_path;
}

class BKGT_Offboarding_Test extends BKGT_TestCase {

    protected function setupTestData() {
        // Create test users
        $this->test_user_ids = array();

        $users = array(
            array('user_login' => 'test_leaving_user', 'user_email' => 'leaving@test.com', 'role' => 'subscriber'),
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

        // Create test offboarding processes
        $this->test_offboarding_ids = array();

        $processes = array(
            array(
                'title' => 'Offboarding: John Doe',
                'user_id' => $this->test_user_ids[0],
                'status' => 'in_progress',
                'deadline' => date('Y-m-d', strtotime('+30 days')),
            ),
            array(
                'title' => 'Offboarding: Jane Smith',
                'user_id' => $this->test_user_ids[0],
                'status' => 'completed',
                'deadline' => date('Y-m-d', strtotime('+15 days')),
            ),
        );

        foreach ($processes as $process) {
            $process_id = wp_insert_post(array(
                'post_type' => 'bkgt_offboarding',
                'post_title' => $process['title'],
                'post_status' => 'publish',
                'meta_input' => array(
                    'bkgt_offboarding_user_id' => $process['user_id'],
                    'bkgt_offboarding_status' => $process['status'],
                    'bkgt_offboarding_deadline' => $process['deadline'],
                    'bkgt_offboarding_tasks' => array(),
                    'bkgt_offboarding_equipment' => array(),
                ),
            ));
            $this->test_offboarding_ids[] = $process_id;
        }
    }

    protected function cleanupTestData() {
        // Clean up test offboarding processes
        if (!empty($this->test_offboarding_ids)) {
            foreach ($this->test_offboarding_ids as $process_id) {
                wp_delete_post($process_id, true);
            }
        }

        // Clean up test users
        if (!empty($this->test_user_ids)) {
            foreach ($this->test_user_ids as $user_id) {
                wp_delete_user($user_id);
            }
        }

        // Clean up scheduled events
        $timestamp = wp_next_scheduled('bkgt_check_offboarding_deadlines');
        if ($timestamp) {
            wp_unschedule_event($timestamp, 'bkgt_check_offboarding_deadlines');
        }
    }

    /**
     * Test plugin initialization
     */
    public function test_plugin_initialization() {
        $this->assertTrue(class_exists('BKGT_Offboarding_System'));
        $this->assertTrue(shortcode_exists('bkgt_offboarding_dashboard'));
        $this->assertTrue(post_type_exists('bkgt_offboarding'));
    }

    /**
     * Test offboarding post type registration
     */
    public function test_offboarding_post_type_registration() {
        $post_type = get_post_type_object('bkgt_offboarding');

        $this->assertNotNull($post_type);
        $this->assertEquals('bkgt_offboarding', $post_type->name);
        $this->assertFalse($post_type->public); // Should be private
        $this->assertTrue($post_type->show_ui);
        $this->assertFalse($post_type->show_in_menu); // Hidden from main menu
        $this->assertContains('title', $post_type->supports);
        $this->assertEquals('offboarding', $post_type->capability_type);
    }

    /**
     * Test offboarding process creation
     */
    public function test_offboarding_process_creation() {
        $process_id = $this->test_offboarding_ids[0];
        $process = get_post($process_id);

        $this->assertNotNull($process);
        $this->assertEquals('bkgt_offboarding', $process->post_type);
        $this->assertEquals('Offboarding: John Doe', $process->post_title);
    }

    /**
     * Test offboarding metadata
     */
    public function test_offboarding_metadata() {
        $process_id = $this->test_offboarding_ids[0];

        // Test user ID metadata
        $user_id = get_post_meta($process_id, 'bkgt_offboarding_user_id', true);
        $this->assertEquals($this->test_user_ids[0], $user_id);

        // Test status metadata
        $status = get_post_meta($process_id, 'bkgt_offboarding_status', true);
        $this->assertEquals('in_progress', $status);

        // Test deadline metadata
        $deadline = get_post_meta($process_id, 'bkgt_offboarding_deadline', true);
        $this->assertNotEmpty($deadline);

        // Test tasks metadata (should be array)
        $tasks = get_post_meta($process_id, 'bkgt_offboarding_tasks', true);
        $this->assertIsArray($tasks);

        // Test equipment metadata (should be array)
        $equipment = get_post_meta($process_id, 'bkgt_offboarding_equipment', true);
        $this->assertIsArray($equipment);
    }

    /**
     * Test offboarding dashboard shortcode
     */
    public function test_offboarding_dashboard_shortcode() {
        // Test shortcode registration
        $this->assertTrue(shortcode_exists('bkgt_offboarding_dashboard'));

        // Test basic shortcode output
        $output = do_shortcode('[bkgt_offboarding_dashboard]');
        $this->assertNotEmpty($output);
        // Output may vary based on user context and permissions
    }

    /**
     * Test AJAX handlers registration
     */
    public function test_ajax_handlers_registration() {
        // Check if AJAX actions are registered
        $this->assertTrue(has_action('wp_ajax_bkgt_start_offboarding'));
        $this->assertTrue(has_action('wp_ajax_bkgt_update_offboarding_task'));
        $this->assertTrue(has_action('wp_ajax_bkgt_complete_offboarding'));
        $this->assertTrue(has_action('wp_ajax_bkgt_generate_equipment_receipt'));
        $this->assertTrue(has_action('wp_ajax_bkgt_update_equipment_status'));
        $this->assertTrue(has_action('wp_ajax_bkgt_add_notification'));
        $this->assertTrue(has_action('wp_ajax_bkgt_delete_notification'));
        $this->assertTrue(has_action('wp_ajax_bkgt_download_receipt'));
        $this->assertTrue(has_action('wp_ajax_nopriv_bkgt_download_receipt'));
    }

    /**
     * Test scheduled events
     */
    public function test_scheduled_events() {
        // Check if the deadline check event is scheduled
        $timestamp = wp_next_scheduled('bkgt_check_offboarding_deadlines');
        $this->assertNotFalse($timestamp);
        $this->assertIsInt($timestamp);
    }

    /**
     * Test offboarding status updates
     */
    public function test_offboarding_status_updates() {
        $process_id = $this->test_offboarding_ids[0];

        // Update status to completed
        $result = update_post_meta($process_id, 'bkgt_offboarding_status', 'completed');
        $this->assertTrue($result !== false);

        // Verify status update
        $status = get_post_meta($process_id, 'bkgt_offboarding_status', true);
        $this->assertEquals('completed', $status);
    }

    /**
     * Test offboarding task management
     */
    public function test_offboarding_task_management() {
        $process_id = $this->test_offboarding_ids[0];

        // Sample tasks
        $tasks = array(
            array(
                'id' => 'return_equipment',
                'title' => 'Return all equipment',
                'status' => 'pending',
                'due_date' => date('Y-m-d', strtotime('+7 days')),
            ),
            array(
                'id' => 'access_removal',
                'title' => 'Remove system access',
                'status' => 'completed',
                'due_date' => date('Y-m-d', strtotime('+1 day')),
            ),
        );

        // Update tasks
        $result = update_post_meta($process_id, 'bkgt_offboarding_tasks', $tasks);
        $this->assertTrue($result !== false);

        // Retrieve and verify tasks
        $stored_tasks = get_post_meta($process_id, 'bkgt_offboarding_tasks', true);
        $this->assertIsArray($stored_tasks);
        $this->assertCount(2, $stored_tasks);
        $this->assertEquals('return_equipment', $stored_tasks[0]['id']);
        $this->assertEquals('pending', $stored_tasks[0]['status']);
        $this->assertEquals('access_removal', $stored_tasks[1]['id']);
        $this->assertEquals('completed', $stored_tasks[1]['status']);
    }

    /**
     * Test equipment tracking
     */
    public function test_equipment_tracking() {
        $process_id = $this->test_offboarding_ids[0];

        // Sample equipment
        $equipment = array(
            array(
                'id' => 'helmet_001',
                'name' => 'Football Helmet',
                'serial_number' => 'FH-2023-001',
                'status' => 'returned',
                'return_date' => date('Y-m-d'),
                'condition' => 'good',
            ),
            array(
                'id' => 'pads_002',
                'name' => 'Shoulder Pads',
                'serial_number' => 'SP-2023-002',
                'status' => 'pending',
                'return_date' => null,
                'condition' => null,
            ),
        );

        // Update equipment
        $result = update_post_meta($process_id, 'bkgt_offboarding_equipment', $equipment);
        $this->assertTrue($result !== false);

        // Retrieve and verify equipment
        $stored_equipment = get_post_meta($process_id, 'bkgt_offboarding_equipment', true);
        $this->assertIsArray($stored_equipment);
        $this->assertCount(2, $stored_equipment);
        $this->assertEquals('helmet_001', $stored_equipment[0]['id']);
        $this->assertEquals('returned', $stored_equipment[0]['status']);
        $this->assertEquals('pads_002', $stored_equipment[1]['id']);
        $this->assertEquals('pending', $stored_equipment[1]['status']);
    }

    /**
     * Test offboarding deadline handling
     */
    public function test_offboarding_deadline_handling() {
        $process_id = $this->test_offboarding_ids[0];

        // Set a past deadline
        $past_deadline = date('Y-m-d', strtotime('-1 day'));
        update_post_meta($process_id, 'bkgt_offboarding_deadline', $past_deadline);
        update_post_meta($process_id, 'bkgt_offboarding_status', 'in_progress');

        // Set a future deadline
        $future_deadline = date('Y-m-d', strtotime('+30 days'));
        $future_process_id = $this->test_offboarding_ids[1];
        update_post_meta($future_process_id, 'bkgt_offboarding_deadline', $future_deadline);

        // Test deadline retrieval
        $past_deadline_stored = get_post_meta($process_id, 'bkgt_offboarding_deadline', true);
        $future_deadline_stored = get_post_meta($future_process_id, 'bkgt_offboarding_deadline', true);

        $this->assertEquals($past_deadline, $past_deadline_stored);
        $this->assertEquals($future_deadline, $future_deadline_stored);
    }

    /**
     * Test offboarding process queries
     */
    public function test_offboarding_process_queries() {
        // Query all offboarding processes
        $args = array(
            'post_type' => 'bkgt_offboarding',
            'posts_per_page' => -1,
            'post_status' => 'publish',
        );

        $query = new WP_Query($args);
        $processes = $query->get_posts();

        $this->assertNotEmpty($processes);
        $this->assertGreaterThanOrEqual(2, count($processes));

        // Query by status
        $args_completed = array(
            'post_type' => 'bkgt_offboarding',
            'posts_per_page' => -1,
            'meta_query' => array(
                array(
                    'key' => 'bkgt_offboarding_status',
                    'value' => 'completed',
                    'compare' => '=',
                ),
            ),
        );

        $query_completed = new WP_Query($args_completed);
        $completed_processes = $query_completed->get_posts();

        $this->assertCount(1, $completed_processes);
        $this->assertEquals('Offboarding: Jane Smith', $completed_processes[0]->post_title);
    }

    /**
     * Test user assignment to offboarding processes
     */
    public function test_user_assignment_to_offboarding() {
        $process_id = $this->test_offboarding_ids[0];
        $user_id = $this->test_user_ids[0];

        // Verify user assignment
        $assigned_user_id = get_post_meta($process_id, 'bkgt_offboarding_user_id', true);
        $this->assertEquals($user_id, $assigned_user_id);

        // Test user lookup
        $user = get_userdata($user_id);
        $this->assertNotNull($user);
        $this->assertEquals('test_leaving_user', $user->user_login);
    }

    /**
     * Test offboarding notifications system
     */
    public function test_offboarding_notifications() {
        $process_id = $this->test_offboarding_ids[0];

        // Sample notifications
        $notifications = array(
            array(
                'id' => 'deadline_warning',
                'type' => 'warning',
                'message' => 'Offboarding deadline approaching',
                'date' => date('Y-m-d H:i:s'),
                'read' => false,
            ),
            array(
                'id' => 'equipment_returned',
                'type' => 'success',
                'message' => 'All equipment has been returned',
                'date' => date('Y-m-d H:i:s'),
                'read' => true,
            ),
        );

        // Store notifications
        $result = update_post_meta($process_id, 'bkgt_offboarding_notifications', $notifications);
        $this->assertTrue($result !== false);

        // Retrieve notifications
        $stored_notifications = get_post_meta($process_id, 'bkgt_offboarding_notifications', true);
        $this->assertIsArray($stored_notifications);
        $this->assertCount(2, $stored_notifications);
        $this->assertEquals('deadline_warning', $stored_notifications[0]['id']);
        $this->assertEquals('equipment_returned', $stored_notifications[1]['id']);
    }

    /**
     * Test admin menu and capabilities
     */
    public function test_admin_menu_and_capabilities() {
        // Test that admin functions exist
        $this->assertTrue(function_exists('add_menu_page'));

        // Test capability type
        $post_type = get_post_type_object('bkgt_offboarding');
        $this->assertEquals('offboarding', $post_type->capability_type);

        // Test capabilities array
        $this->assertArrayHasKey('capabilities', $post_type);
        $this->assertIsArray($post_type->capabilities);
    }

    /**
     * Test script and style enqueuing
     */
    public function test_script_and_style_enqueuing() {
        // Test that enqueue functions exist
        $this->assertTrue(has_action('wp_enqueue_scripts', 'BKGT_Offboarding_System::enqueue_frontend_scripts'));
        $this->assertTrue(has_action('admin_enqueue_scripts', 'BKGT_Offboarding_System::enqueue_admin_scripts'));
    }

    /**
     * Test textdomain loading
     */
    public function test_textdomain_loading() {
        // Test that textdomain action is registered
        $this->assertTrue(has_action('plugins_loaded', 'BKGT_Offboarding_System::load_textdomain'));
    }
}