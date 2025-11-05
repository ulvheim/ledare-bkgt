<?php
/**
 * Unit Tests for BKGT User Management Plugin
 */

// Load the user management plugin for testing
$plugin_path = WP_PLUGIN_DIR . '/bkgt-user-management/bkgt-user-management.php';
if (file_exists($plugin_path)) {
    require_once $plugin_path;
}

class BKGT_User_Management_Test extends BKGT_TestCase {

    protected function setupTestData() {
        // Create test teams
        $this->test_team_ids = array();

        $teams = array(
            array('title' => 'Test Team A', 'content' => 'Test team A description'),
            array('title' => 'Test Team B', 'content' => 'Test team B description'),
        );

        foreach ($teams as $team) {
            $team_id = wp_insert_post(array(
                'post_type' => 'bkgt_team',
                'post_title' => $team['title'],
                'post_content' => $team['content'],
                'post_status' => 'publish',
            ));
            $this->test_team_ids[] = $team_id;
        }

        // Create test users
        $this->test_user_ids = array();

        $users = array(
            array('user_login' => 'test_trainer', 'user_email' => 'trainer@test.com', 'role' => 'tranare'),
            array('user_login' => 'test_manager', 'user_email' => 'manager@test.com', 'role' => 'lagledare'),
            array('user_login' => 'test_player', 'user_email' => 'player@test.com', 'role' => 'subscriber'),
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
        // Clean up test teams
        if (!empty($this->test_team_ids)) {
            foreach ($this->test_team_ids as $team_id) {
                wp_delete_post($team_id, true);
            }
        }

        // Clean up test users
        if (!empty($this->test_user_ids)) {
            foreach ($this->test_user_ids as $user_id) {
                wp_delete_user($user_id);
            }
        }

        // Clean up user meta
        global $wpdb;
        if ($wpdb) {
            $wpdb->query("DELETE FROM {$wpdb->usermeta} WHERE meta_key = 'bkgt_assigned_teams'");
        }
    }

    /**
     * Test plugin initialization
     */
    public function test_plugin_initialization() {
        $this->assertTrue(class_exists('BKGT_User_Management'));
        $this->assertTrue(function_exists('bkgt_user_management'));
        $this->assertTrue(post_type_exists('bkgt_team'));
    }

    /**
     * Test team post type registration
     */
    public function test_team_post_type_registration() {
        $post_type = get_post_type_object('bkgt_team');

        $this->assertNotNull($post_type);
        $this->assertEquals('bkgt_team', $post_type->name);
        $this->assertTrue($post_type->public);
        $this->assertEquals('lag', $post_type->rewrite['slug']);
        $this->assertContains('title', $post_type->supports);
        $this->assertContains('editor', $post_type->supports);
    }

    /**
     * Test BKGT_Team class methods
     */
    public function test_team_class_get_all_teams() {
        $teams = BKGT_Team::get_all_teams();

        $this->assertIsArray($teams);
        $this->assertGreaterThanOrEqual(2, count($teams)); // At least our test teams

        // Check that our test teams are included
        $team_titles = wp_list_pluck($teams, 'post_title');
        $this->assertContains('Test Team A', $team_titles);
        $this->assertContains('Test Team B', $team_titles);
    }

    public function test_team_class_get_team() {
        $team_id = $this->test_team_ids[0];
        $team = BKGT_Team::get_team($team_id);

        $this->assertNotFalse($team);
        $this->assertEquals('bkgt_team', $team->post_type);
        $this->assertEquals('Test Team A', $team->post_title);
    }

    public function test_team_class_get_team_invalid() {
        $team = BKGT_Team::get_team(999999);
        $this->assertFalse($team);
    }

    public function test_team_class_get_team_stats() {
        $team_id = $this->test_team_ids[0];
        $stats = BKGT_Team::get_team_stats($team_id);

        $this->assertIsArray($stats);
        $this->assertArrayHasKey('members_count', $stats);
        $this->assertArrayHasKey('coaches_count', $stats);
        $this->assertArrayHasKey('managers_count', $stats);

        // Initially should be 0 since no users are assigned
        $this->assertEquals(0, $stats['members_count']);
        $this->assertEquals(0, $stats['coaches_count']);
        $this->assertEquals(0, $stats['managers_count']);
    }

    public function test_team_class_get_teams_for_select() {
        $options = BKGT_Team::get_teams_for_select();

        $this->assertIsArray($options);
        $this->assertGreaterThanOrEqual(2, count($options));

        // Check that our test teams are in the options
        $this->assertArrayHasKey($this->test_team_ids[0], $options);
        $this->assertArrayHasKey($this->test_team_ids[1], $options);
        $this->assertEquals('Test Team A', $options[$this->test_team_ids[0]]);
        $this->assertEquals('Test Team B', $options[$this->test_team_ids[1]]);
    }

    /**
     * Test BKGT_User_Team_Assignment class methods
     */
    public function test_user_team_assignment_get_user_teams() {
        $user_id = $this->test_user_ids[0];
        $teams = BKGT_User_Team_Assignment::get_user_teams($user_id);

        $this->assertIsArray($teams);
        $this->assertEmpty($teams); // Initially no teams assigned
    }

    public function test_user_team_assignment_assign_user_to_team() {
        $user_id = $this->test_user_ids[0];
        $team_id = $this->test_team_ids[0];

        // Assign user to team
        $result = BKGT_User_Team_Assignment::assign_user_to_team($user_id, $team_id);
        $this->assertTrue($result);

        // Check assignment
        $teams = BKGT_User_Team_Assignment::get_user_teams($user_id);
        $this->assertContains($team_id, $teams);

        // Try to assign again (should return false)
        $result = BKGT_User_Team_Assignment::assign_user_to_team($user_id, $team_id);
        $this->assertFalse($result);
    }

    public function test_user_team_assignment_remove_user_from_team() {
        $user_id = $this->test_user_ids[0];
        $team_id = $this->test_team_ids[0];

        // First assign
        BKGT_User_Team_Assignment::assign_user_to_team($user_id, $team_id);

        // Then remove
        $result = BKGT_User_Team_Assignment::remove_user_from_team($user_id, $team_id);
        $this->assertTrue($result);

        // Check removal
        $teams = BKGT_User_Team_Assignment::get_user_teams($user_id);
        $this->assertNotContains($team_id, $teams);

        // Try to remove again (should return false)
        $result = BKGT_User_Team_Assignment::remove_user_from_team($user_id, $team_id);
        $this->assertFalse($result);
    }

    public function test_user_team_assignment_update_user_teams() {
        $user_id = $this->test_user_ids[0];
        $team_ids = $this->test_team_ids;

        // Update user teams
        $result = BKGT_User_Team_Assignment::update_user_teams($user_id, $team_ids);
        $this->assertTrue($result);

        // Check assignments
        $teams = BKGT_User_Team_Assignment::get_user_teams($user_id);
        $this->assertCount(2, $teams);
        $this->assertContains($this->test_team_ids[0], $teams);
        $this->assertContains($this->test_team_ids[1], $teams);
    }

    public function test_user_team_assignment_is_user_in_team() {
        $user_id = $this->test_user_ids[0];
        $team_id = $this->test_team_ids[0];

        // Initially not in team
        $result = BKGT_User_Team_Assignment::is_user_in_team($user_id, $team_id);
        $this->assertFalse($result);

        // Assign to team
        BKGT_User_Team_Assignment::assign_user_to_team($user_id, $team_id);

        // Now should be in team
        $result = BKGT_User_Team_Assignment::is_user_in_team($user_id, $team_id);
        $this->assertTrue($result);
    }

    /**
     * Test team member retrieval
     */
    public function test_team_get_team_members() {
        $user_id = $this->test_user_ids[0]; // trainer
        $team_id = $this->test_team_ids[0];

        // Assign user to team
        BKGT_User_Team_Assignment::assign_user_to_team($user_id, $team_id);

        // Get team members
        $members = BKGT_Team::get_team_members($team_id);
        $this->assertIsArray($members);
        $this->assertCount(1, $members);
        $this->assertEquals($user_id, $members[0]->ID);
    }

    public function test_team_get_team_coaches() {
        $user_id = $this->test_user_ids[0]; // trainer
        $team_id = $this->test_team_ids[0];

        // Assign trainer to team
        BKGT_User_Team_Assignment::assign_user_to_team($user_id, $team_id);

        // Get team coaches
        $coaches = BKGT_Team::get_team_coaches($team_id);
        $this->assertIsArray($coaches);
        $this->assertCount(1, $coaches);
        $this->assertEquals($user_id, $coaches[0]->ID);
    }

    public function test_team_get_team_managers() {
        $user_id = $this->test_user_ids[1]; // manager
        $team_id = $this->test_team_ids[0];

        // Assign manager to team
        BKGT_User_Team_Assignment::assign_user_to_team($user_id, $team_id);

        // Get team managers
        $managers = BKGT_Team::get_team_managers($team_id);
        $this->assertIsArray($managers);
        $this->assertCount(1, $managers);
        $this->assertEquals($user_id, $managers[0]->ID);
    }

    /**
     * Test BKGT_Capabilities class methods
     */
    public function test_capabilities_can_view_performance_data() {
        $admin_user = wp_get_current_user(); // Assuming test runs as admin
        $regular_user = $this->test_user_ids[2]; // player

        // Admin should be able to view performance data
        $this->assertTrue(BKGT_Capabilities::can_view_performance_data($admin_user->ID));

        // Regular user might not (depending on role setup)
        // This test assumes the capability system is properly configured
    }

    public function test_capabilities_get_user_role_label() {
        $trainer_id = $this->test_user_ids[0];
        $manager_id = $this->test_user_ids[1];
        $player_id = $this->test_user_ids[2];

        // Test role labels (these may vary based on actual role setup)
        $trainer_label = BKGT_Capabilities::get_user_role_label($trainer_id);
        $manager_label = BKGT_Capabilities::get_user_role_label($manager_id);
        $player_label = BKGT_Capabilities::get_user_role_label($player_id);

        $this->assertIsString($trainer_label);
        $this->assertIsString($manager_label);
        $this->assertIsString($player_label);
    }

    /**
     * Test team access control
     */
    public function test_team_user_can_access_team() {
        $user_id = $this->test_user_ids[0];
        $team_id = $this->test_team_ids[0];

        // Initially no access
        $can_access = BKGT_Team::user_can_access_team($user_id, $team_id);
        $this->assertFalse($can_access);

        // Assign user to team
        BKGT_User_Team_Assignment::assign_user_to_team($user_id, $team_id);

        // Now should have access
        $can_access = BKGT_Team::user_can_access_team($user_id, $team_id);
        $this->assertTrue($can_access);
    }

    /**
     * Test default teams creation
     */
    public function test_default_teams_creation() {
        // Check if default teams exist (P2013-P2020)
        $default_teams = get_posts(array(
            'post_type' => 'bkgt_team',
            'posts_per_page' => -1,
            'post_status' => 'publish',
        ));

        $team_titles = wp_list_pluck($default_teams, 'post_title');

        // Should contain default teams like P2013, P2014, etc.
        $this->assertContains('P2013', $team_titles);
        $this->assertContains('P2014', $team_titles);
        $this->assertContains('P2020', $team_titles);
    }
}