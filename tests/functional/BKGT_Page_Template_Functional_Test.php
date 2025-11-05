<?php
/**
 * Functional Tests for BKGT Page Templates
 */

class BKGT_Page_Template_Functional_Test extends BKGT_TestCase {

    protected function setupTestData() {
        global $wpdb;
        if ($wpdb) {
            // Create comprehensive test data for all templates
            $players = BKGT_TestHelper::generateRandomData('player', 15);
            foreach ($players as $player) {
                $wpdb->insert('bkgt_players', [
                    'name' => $player['name'],
                    'position' => $player['position'],
                    'team' => 'Template Test Team',
                    'age' => $player['age'],
                    'stats' => $player['stats']
                ]);
            }

            $events = BKGT_TestHelper::generateRandomData('event', 8);
            foreach ($events as $event) {
                $wpdb->insert('bkgt_events', [
                    'title' => $event['title'],
                    'date' => $event['date'],
                    'location' => $event['location'],
                    'home_team' => 'Template Test Team',
                    'away_team' => $event['away_team'],
                    'status' => $event['status']
                ]);
            }

            // Create inventory items
            $items = BKGT_TestHelper::generateRandomData('inventory', 12);
            foreach ($items as $item) {
                $wpdb->insert('bkgt_inventory', [
                    'title' => $item['title'],
                    'type' => $item['type'],
                    'condition' => $item['condition'],
                    'location' => $item['location'],
                    'value' => $item['value']
                ]);
            }
        }
    }

    protected function cleanupTestData() {
        global $wpdb;
        if ($wpdb) {
            $wpdb->query("DELETE FROM bkgt_inventory_assignments");
            $wpdb->query("DELETE FROM bkgt_inventory WHERE title LIKE 'Item%'");
            $wpdb->query("DELETE FROM bkgt_players WHERE team = 'Template Test Team'");
            $wpdb->query("DELETE FROM bkgt_events WHERE home_team = 'Template Test Team'");
        }
    }

    /**
     * Test page-team-overview.php template
     */
    public function test_page_team_overview_template() {
        // Create a test page using the template
        $page_id = $this->createMockPost([
            'post_title' => 'Team Overview Test',
            'post_content' => '[bkgt_team_overview team="Template Test Team"]',
            'post_type' => 'page'
        ]);

        $this->assertGreaterThan(0, $page_id);

        // Simulate page load
        $this->go_to(get_permalink($page_id));

        // Check that shortcodes are processed
        $content = get_the_content();
        $processed_content = apply_filters('the_content', $content);

        $this->assertStringContains($processed_content, 'Template Test Team');
        $this->assertStringContains($processed_content, 'Team');
    }

    /**
     * Test page-players.php template
     */
    public function test_page_players_template() {
        $page_id = $this->createMockPost([
            'post_title' => 'Players Test',
            'post_content' => '[bkgt_players team="Template Test Team"]',
            'post_type' => 'page'
        ]);

        $this->assertGreaterThan(0, $page_id);

        $this->go_to(get_permalink($page_id));

        $content = get_the_content();
        $processed_content = apply_filters('the_content', $content);

        $this->assertStringContains($processed_content, 'Template Test Team');
        $this->assertStringContains($processed_content, 'Player');
    }

    /**
     * Test page-events.php template
     */
    public function test_page_events_template() {
        $page_id = $this->createMockPost([
            'post_title' => 'Events Test',
            'post_content' => '[bkgt_events]',
            'post_type' => 'page'
        ]);

        $this->assertGreaterThan(0, $page_id);

        $this->go_to(get_permalink($page_id));

        $content = get_the_content();
        $processed_content = apply_filters('the_content', $content);

        $this->assertStringContains($processed_content, 'Template Test Team');
        $this->assertStringContains($processed_content, 'Match');
    }

    /**
     * Test template rendering performance
     */
    public function test_template_rendering_performance() {
        $page_id = $this->createMockPost([
            'post_title' => 'Performance Test',
            'post_content' => '
                [bkgt_team_overview team="Template Test Team"]
                [bkgt_players team="Template Test Team"]
                [bkgt_events]
                [bkgt_inventory]
            ',
            'post_type' => 'page'
        ]);

        $start_time = microtime(true);

        $this->go_to(get_permalink($page_id));
        $content = get_the_content();
        $processed_content = apply_filters('the_content', $content);

        $end_time = microtime(true);
        $execution_time = $end_time - $start_time;

        // Should render within reasonable time
        $this->assertLessThan(10.0, $execution_time);
        $this->assertStringContains($processed_content, 'Template Test Team');
    }

    /**
     * Test template with user roles
     */
    public function test_template_user_role_access() {
        $page_id = $this->createMockPost([
            'post_title' => 'Admin Dashboard Test',
            'post_content' => '[bkgt_admin_dashboard]',
            'post_type' => 'page'
        ]);

        // Test as regular user
        $regular_user = $this->createMockUser('subscriber');
        if ($regular_user) {
            wp_set_current_user($regular_user->ID);
            $this->go_to(get_permalink($page_id));
            $content = get_the_content();
            $processed_content = apply_filters('the_content', $content);
            // Should handle gracefully without admin content
            $this->assertIsString($processed_content);
        }

        // Test as admin
        $admin_user = $this->createMockUser('administrator');
        if ($admin_user) {
            wp_set_current_user($admin_user->ID);
            $this->go_to(get_permalink($page_id));
            $content = get_the_content();
            $processed_content = apply_filters('the_content', $content);
            $this->assertIsString($processed_content);
        }

        wp_set_current_user(0); // Reset
    }

    /**
     * Test template content structure
     */
    public function test_template_content_structure() {
        $page_id = $this->createMockPost([
            'post_title' => 'Structure Test',
            'post_content' => '[bkgt_players team="Template Test Team" limit="5"]',
            'post_type' => 'page'
        ]);

        $this->go_to(get_permalink($page_id));
        $content = get_the_content();
        $processed_content = apply_filters('the_content', $content);

        // Should have proper HTML structure
        $this->assertStringContains($processed_content, '<div');
        $this->assertStringContains($processed_content, '</div>');

        // Should not have PHP errors
        $this->assertStringNotContains($processed_content, 'Fatal error');
        $this->assertStringNotContains($processed_content, 'Parse error');
        $this->assertStringNotContains($processed_content, 'Warning');
    }

    /**
     * Test template with empty data
     */
    public function test_template_empty_data_handling() {
        // Clean up existing data first
        global $wpdb;
        if ($wpdb) {
            $wpdb->query("DELETE FROM bkgt_players WHERE team = 'Template Test Team'");
        }

        $page_id = $this->createMockPost([
            'post_title' => 'Empty Data Test',
            'post_content' => '[bkgt_players team="Nonexistent Team"]',
            'post_type' => 'page'
        ]);

        $this->go_to(get_permalink($page_id));
        $content = get_the_content();
        $processed_content = apply_filters('the_content', $content);

        // Should handle empty data gracefully
        $this->assertIsString($processed_content);
        $this->assertStringNotContains($processed_content, 'Fatal error');
    }

    /**
     * Test template caching
     */
    public function test_template_caching_behavior() {
        $page_id = $this->createMockPost([
            'post_title' => 'Cache Test',
            'post_content' => '[bkgt_players team="Template Test Team"]',
            'post_type' => 'page'
        ]);

        // First render
        $this->go_to(get_permalink($page_id));
        $content1 = get_the_content();
        $processed_content1 = apply_filters('the_content', $content1);

        // Second render
        $this->go_to(get_permalink($page_id));
        $content2 = get_the_content();
        $processed_content2 = apply_filters('the_content', $content2);

        // Should be consistent
        $this->assertEquals(strlen($processed_content1), strlen($processed_content2));
    }

    /**
     * Test multiple templates on same page
     */
    public function test_multiple_templates_same_page() {
        $page_id = $this->createMockPost([
            'post_title' => 'Multi Template Test',
            'post_content' => '
                <h2>Team Overview</h2>
                [bkgt_team_overview team="Template Test Team"]

                <h2>Players</h2>
                [bkgt_players team="Template Test Team" limit="3"]

                <h2>Events</h2>
                [bkgt_events limit="3"]

                <h2>Inventory</h2>
                [bkgt_inventory type="football"]
            ',
            'post_type' => 'page'
        ]);

        $this->go_to(get_permalink($page_id));
        $content = get_the_content();
        $processed_content = apply_filters('the_content', $content);

        // Should contain content from all sections
        $this->assertStringContains($processed_content, 'Team Overview');
        $this->assertStringContains($processed_content, 'Players');
        $this->assertStringContains($processed_content, 'Events');
        $this->assertStringContains($processed_content, 'Inventory');
        $this->assertStringContains($processed_content, 'Template Test Team');
    }

    /**
     * Test template responsive design
     */
    public function test_template_responsive_design() {
        $page_id = $this->createMockPost([
            'post_title' => 'Responsive Test',
            'post_content' => '[bkgt_players team="Template Test Team"]',
            'post_type' => 'page'
        ]);

        $this->go_to(get_permalink($page_id));
        $content = get_the_content();
        $processed_content = apply_filters('the_content', $content);

        // Should contain responsive classes or structure
        $this->assertStringContains($processed_content, 'class=');
        // Should have proper grid/table structure for mobile
        $this->assertStringContains($processed_content, '<table') ||
               $this->assertStringContains($processed_content, 'grid') ||
               $this->assertStringContains($processed_content, 'flex');
    }

    /**
     * Test template JavaScript functionality
     */
    public function test_template_javascript_functionality() {
        $page_id = $this->createMockPost([
            'post_title' => 'JS Test',
            'post_content' => '[bkgt_inventory]',
            'post_type' => 'page'
        ]);

        $this->go_to(get_permalink($page_id));

        // Check if scripts are enqueued (this would need WP testing framework)
        // For now, just verify the page loads
        $this->assertTrue(is_page());
        $this->assertEquals($page_id, get_the_ID());
    }
}