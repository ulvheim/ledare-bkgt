<?php
/**
 * Assignment Management Class
 *
 * @package BKGT_Inventory
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class BKGT_Assignment {
    
    /**
     * Assignment types
     */
    const TYPE_CLUB = 'club';
    const TYPE_TEAM = 'team';
    const TYPE_INDIVIDUAL = 'individual';
    
    /**
     * Assign item to club
     */
    public static function assign_to_club($item_id) {
        return self::update_assignment($item_id, self::TYPE_CLUB);
    }
    
    /**
     * Assign item to team
     */
    public static function assign_to_team($item_id, $team_id) {
        // Check if User Management plugin is available
        if (!class_exists('BKGT_Team')) {
            return new WP_Error('plugin_not_available', __('Användarhantering plugin krävs.', 'bkgt-inventory'));
        }
        
        // Verify team exists
        $team = BKGT_Team::get_team($team_id);
        if (!$team) {
            return new WP_Error('team_not_found', __('Lag hittades inte.', 'bkgt-inventory'));
        }
        
        return self::update_assignment($item_id, self::TYPE_TEAM, $team_id);
    }
    
    /**
     * Assign item to individual
     */
    public static function assign_to_individual($item_id, $user_id) {
        // Verify user exists
        $user = get_userdata($user_id);
        if (!$user) {
            return new WP_Error('user_not_found', __('Användare hittades inte.', 'bkgt-inventory'));
        }
        
        return self::update_assignment($item_id, self::TYPE_INDIVIDUAL, $user_id);
    }
    
    /**
     * Update item assignment
     */
    private static function update_assignment($item_id, $assignment_type, $assignment_id = null) {
        global $wpdb;
        
        // Get database instance
        $db = BKGT_Database::get_instance();
        $assignments_table = $db->get_assignments_table();
        
        // Verify item exists in inventory_items table
        $inventory_items_table = $db->get_inventory_items_table();
        $item_exists = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$inventory_items_table} WHERE id = %d",
            $item_id
        ));
        
        if (!$item_exists) {
            return new WP_Error('item_not_found', __('Utrustningsartikel hittades inte i inventariet.', 'bkgt-inventory'));
        }
        
        // Map assignment types to database enum values
        $assignee_type_map = array(
            self::TYPE_CLUB => 'location', // Club assignments are stored as location type
            self::TYPE_TEAM => 'team',
            self::TYPE_INDIVIDUAL => 'user'
        );
        
        $assignee_type = isset($assignee_type_map[$assignment_type]) ? $assignee_type_map[$assignment_type] : 'location';
        
        // For club assignments, use a default location ID (we'll need to create this)
        if ($assignment_type === self::TYPE_CLUB) {
            $assignment_id = self::get_default_club_location_id();
        }
        
        // Start transaction
        $wpdb->query('START TRANSACTION');
        
        try {
            // First, unassign any current active assignment for this item
            $current_user_id = get_current_user_id();
            $wpdb->update(
                $assignments_table,
                array(
                    'unassigned_date' => current_time('mysql'),
                    'unassigned_by' => $current_user_id
                ),
                array(
                    'item_id' => $item_id,
                    'unassigned_date' => null // Only active assignments
                ),
                array('%s', '%d'),
                array('%d')
            );
            
            // Create new assignment
            $result = $wpdb->insert(
                $assignments_table,
                array(
                    'item_id' => $item_id,
                    'assignee_type' => $assignee_type,
                    'assignee_id' => $assignment_id,
                    'assigned_date' => current_time('mysql'),
                    'assigned_by' => $current_user_id,
                    'unassigned_date' => null,
                    'unassigned_by' => null,
                    'notes' => ''
                ),
                array('%d', '%s', '%d', '%s', '%d', '%s', '%s', '%s')
            );
            
            if ($result === false) {
                throw new Exception(__('Kunde inte skapa tilldelning.', 'bkgt-inventory'));
            }
            
            // Log the assignment change
            BKGT_History::log($item_id, 'assignment_changed', $current_user_id, array(
                'new_assignment_type' => $assignment_type,
                'new_assignment_id' => $assignment_id,
            ));
            
            $wpdb->query('COMMIT');
            return true;
            
        } catch (Exception $e) {
            $wpdb->query('ROLLBACK');
            return new WP_Error('assignment_failed', $e->getMessage());
        }
    }
    
    /**
     * Get default club location ID
     */
    private static function get_default_club_location_id() {
        // Find the default club storage location
        $locations = BKGT_Location::get_all_locations();
        
        // Look for "Klubbförråd" or the first storage location
        foreach ($locations as $location) {
            if ($location['slug'] === 'klubbförråd' || $location['location_type'] === BKGT_Location::TYPE_STORAGE) {
                return $location['id'];
            }
        }
        
        // Fallback to first location if no storage location found
        return !empty($locations) ? $locations[0]['id'] : 1;
    }
    
    /**
     * Get item assignment
     */
    public static function get_assignment($item_id) {
        global $wpdb;
        
        // Get database instance
        $db = BKGT_Database::get_instance();
        $assignments_table = $db->get_assignments_table();
        
        // Get the most recent active assignment
        $assignment_row = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$assignments_table} 
             WHERE item_id = %d AND unassigned_date IS NULL 
             ORDER BY assigned_date DESC LIMIT 1",
            $item_id
        ));
        
        if (!$assignment_row) {
            return array(
                'type' => '',
                'id' => null,
                'name' => __('Otilldelad', 'bkgt-inventory'),
                'assigned_date' => null,
                'assigned_by' => null
            );
        }
        
        // Map database assignee_type back to our constants
        $type_map = array(
            'location' => self::TYPE_CLUB,
            'team' => self::TYPE_TEAM,
            'user' => self::TYPE_INDIVIDUAL
        );
        
        $assignment_type = isset($type_map[$assignment_row->assignee_type]) ? $type_map[$assignment_row->assignee_type] : '';
        $assignment_id = $assignment_row->assignee_id;
        $name = '';
        
        // Get the display name based on assignment type
        if ($assignment_type === self::TYPE_TEAM && class_exists('BKGT_Team')) {
            $team = BKGT_Team::get_team($assignment_id);
            $name = $team ? $team->post_title : __('Okänd lag', 'bkgt-inventory');
        } elseif ($assignment_type === self::TYPE_INDIVIDUAL) {
            $user = get_userdata($assignment_id);
            $name = $user ? $user->display_name : __('Okänd användare', 'bkgt-inventory');
        } elseif ($assignment_type === self::TYPE_CLUB) {
            $name = __('Klubben', 'bkgt-inventory');
        }
        
        return array(
            'type' => $assignment_type,
            'id' => $assignment_id,
            'name' => $name,
            'assigned_date' => $assignment_row->assigned_date,
            'assigned_by' => $assignment_row->assigned_by
        );
    }
    
    /**
     * Check if user can access item
     */
    public static function user_can_access_item($user_id, $item_id) {
        // Admins can access everything
        if (user_can($user_id, 'manage_options')) {
            return true;
        }
        
        $assignment = self::get_assignment($item_id);
        
        // Club items are accessible to all authenticated users
        if ($assignment['type'] === self::TYPE_CLUB) {
            return is_user_logged_in();
        }
        
        // Team items require team access
        if ($assignment['type'] === self::TYPE_TEAM) {
            if (class_exists('BKGT_Team')) {
                return BKGT_Team::user_can_access_team($user_id, $assignment['id']);
            }
            return false;
        }
        
        // Individual items require personal access or team admin access
        if ($assignment['type'] === self::TYPE_INDIVIDUAL) {
            // Owner can access their own items
            if ($assignment['id'] == $user_id) {
                return true;
            }
            
            // Check if user is a coach/manager of the owner's team
            if (class_exists('BKGT_User_Team_Assignment') && class_exists('BKGT_Team')) {
                $owner_teams = BKGT_User_Team_Assignment::get_user_teams($assignment['id']);
                foreach ($owner_teams as $team_id) {
                    if (BKGT_Team::user_can_access_team($user_id, $team_id)) {
                        return true;
                    }
                }
            }
        }
        
        return false;
    }
    
    /**
     * Get items assigned to user
     */
    public static function get_user_items($user_id, $args = array()) {
        global $wpdb;
        
        // Get database instance
        $db = BKGT_Database::get_instance();
        $assignments_table = $db->get_assignments_table();
        $inventory_items_table = $db->get_inventory_items_table();
        
        // Build WHERE conditions for different assignment types
        $where_conditions = array();
        $where_values = array();
        
        // Club items (location type with default club location)
        $club_location_id = self::get_default_club_location_id();
        $where_conditions[] = "(a.assignee_type = 'location' AND a.assignee_id = %d)";
        $where_values[] = $club_location_id;
        
        // Team items - if User Management plugin is available
        if (class_exists('BKGT_User_Team_Assignment')) {
            $user_teams = BKGT_User_Team_Assignment::get_user_teams($user_id);
            if (!empty($user_teams)) {
                $placeholders = str_repeat('%d,', count($user_teams) - 1) . '%d';
                $where_conditions[] = "(a.assignee_type = 'team' AND a.assignee_id IN ($placeholders))";
                $where_values = array_merge($where_values, $user_teams);
            }
        }
        
        // Individual items assigned directly to user
        $where_conditions[] = "(a.assignee_type = 'user' AND a.assignee_id = %d)";
        $where_values[] = $user_id;
        
        // Combine conditions
        $where_clause = implode(' OR ', $where_conditions);
        
        // Build the query
        $query = $wpdb->prepare(
            "SELECT DISTINCT i.* FROM {$inventory_items_table} i
             INNER JOIN {$assignments_table} a ON i.id = a.item_id
             WHERE a.unassigned_date IS NULL AND ($where_clause)
             ORDER BY i.created_date DESC",
            $where_values
        );
        
        $results = $wpdb->get_results($query);
        
        // Convert to expected format (array of objects with id, unique_id, etc.)
        $items = array();
        foreach ($results as $row) {
            $items[] = (object) array(
                'id' => $row->id,
                'unique_id' => $row->unique_id,
                'manufacturer_id' => $row->manufacturer_id,
                'item_type_id' => $row->item_type_id,
                'condition' => $row->condition,
                'storage_location' => $row->storage_location,
                'metadata' => $row->metadata,
                'sticker' => $row->sticker,
                'created_date' => $row->created_date,
                'updated_date' => $row->updated_date
            );
        }
        
        return $items;
    }
    
    /**
     * Transfer item from one assignment to another
     */
    public static function transfer_item($item_id, $new_assignment_type, $new_assignment_id = null) {
        $current_assignment = self::get_assignment($item_id);
        
        // Don't transfer if assignment hasn't changed
        if ($current_assignment['type'] === $new_assignment_type && $current_assignment['id'] == $new_assignment_id) {
            return true;
        }
        
        return self::update_assignment($item_id, $new_assignment_type, $new_assignment_id);
    }
    
    /**
     * Bulk assign items
     */
    public static function bulk_assign($item_ids, $assignment_type, $assignment_id = null) {
        $results = array();
        
        foreach ($item_ids as $item_id) {
            $result = self::update_assignment($item_id, $assignment_type, $assignment_id);
            $results[$item_id] = $result;
        }
        
        return $results;
    }
    
    /**
     * Get assignment history for an item
     */
    public static function get_assignment_history($item_id) {
        global $wpdb;
        
        // Get database instance
        $db = BKGT_Database::get_instance();
        $assignments_table = $db->get_assignments_table();
        
        // Get all assignments for this item, ordered by assignment date
        $history = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$assignments_table} 
             WHERE item_id = %d 
             ORDER BY assigned_date DESC",
            $item_id
        ));
        
        // Enrich with assignee names
        foreach ($history as &$assignment) {
            $assignment->assignee_name = self::get_assignee_name($assignment->assignee_type, $assignment->assignee_id);
            $assignment->assigned_by_name = self::get_user_display_name($assignment->assigned_by);
            if ($assignment->unassigned_by) {
                $assignment->unassigned_by_name = self::get_user_display_name($assignment->unassigned_by);
            }
        }
        
        return $history;
    }
    
    /**
     * Get assignee name based on type and ID
     */
    private static function get_assignee_name($assignee_type, $assignee_id) {
        switch ($assignee_type) {
            case 'location':
                return __('Klubben', 'bkgt-inventory');
            case 'team':
                if (class_exists('BKGT_Team')) {
                    $team = BKGT_Team::get_team($assignee_id);
                    return $team ? $team->post_title : __('Okänd lag', 'bkgt-inventory');
                }
                return __('Okänd lag', 'bkgt-inventory');
            case 'user':
                $user = get_userdata($assignee_id);
                return $user ? $user->display_name : __('Okänd användare', 'bkgt-inventory');
            default:
                return __('Okänd', 'bkgt-inventory');
        }
    }
    
    /**
     * Get user display name
     */
    private static function get_user_display_name($user_id) {
        $user = get_userdata($user_id);
        return $user ? $user->display_name : __('Okänd användare', 'bkgt-inventory');
    }
    
    /**
     * Get workflow suggestions for item assignment
     */
    public static function get_workflow_suggestions($item_id) {
        global $wpdb;
        
        $db = BKGT_Database::get_instance();
        $inventory_table = $db->get_inventory_items_table();
        $item_types_table = $db->get_item_types_table();
        
        // Get item details
        $item = $wpdb->get_row($wpdb->prepare(
            "SELECT i.*, t.name as item_type_name 
             FROM {$inventory_table} i 
             LEFT JOIN {$item_types_table} t ON i.item_type_id = t.id 
             WHERE i.id = %d",
            $item_id
        ));
        
        if (!$item) {
            return array();
        }
        
        $suggestions = array();
        $item_type_name = strtolower($item->item_type_name);
        
        // Equipment type suggestions based on Swedish football gear
        if (strpos($item_type_name, 'hjälm') !== false) {
            $suggestions[] = array(
                'type' => 'team',
                'message' => __('Hjälmar rekommenderas för alla spelare i kontaktsporter som amerikansk fotboll.', 'bkgt-inventory'),
                'priority' => 'high'
            );
        } elseif (strpos($item_type_name, 'axelskydd') !== false || strpos($item_type_name, 'shoulder') !== false) {
            $suggestions[] = array(
                'type' => 'team',
                'message' => __('Axelskydd är viktiga för quarterbacks, receivers och andra spelare som behöver extra skydd.', 'bkgt-inventory'),
                'priority' => 'high'
            );
        } elseif (strpos($item_type_name, 'benskydd') !== false || strpos($item_type_name, 'knee') !== false) {
            $suggestions[] = array(
                'type' => 'team',
                'message' => __('Benskydd rekommenderas för alla spelare i kontaktsporter.', 'bkgt-inventory'),
                'priority' => 'medium'
            );
        } elseif (strpos($item_type_name, 'armbågsskydd') !== false || strpos($item_type_name, 'elbow') !== false) {
            $suggestions[] = array(
                'type' => 'team',
                'message' => __('Armbågsskydd är användbara för offensive och defensive linemen.', 'bkgt-inventory'),
                'priority' => 'medium'
            );
        }
        
        // Condition-based suggestions
        if ($item->condition === 'Behöver reparation') {
            $suggestions[] = array(
                'type' => 'alert',
                'message' => __('Denna utrustning behöver reparation innan den kan användas.', 'bkgt-inventory'),
                'priority' => 'high'
            );
        } elseif ($item->condition === 'Förlustanmäld') {
            $suggestions[] = array(
                'type' => 'alert',
                'message' => __('Denna utrustning är anmäld som förlorad. Överväg att ersätta den.', 'bkgt-inventory'),
                'priority' => 'high'
            );
        }
        
        // Storage suggestions for unassigned items
        $current_assignment = self::get_assignment($item_id);
        if ($current_assignment['type'] === '') {
            $suggestions[] = array(
                'type' => 'location',
                'message' => __('Överväg att placera utrustningen i förrådet tills den tilldelas.', 'bkgt-inventory'),
                'priority' => 'low'
            );
        }
        
        return $suggestions;
    }
    
    /**
     * Check for assignment conflicts
     */
    public static function check_assignment_conflicts($assignee_type, $assignee_id, $exclude_item_ids = array()) {
        global $wpdb;
        
        $db = BKGT_Database::get_instance();
        $assignments_table = $db->get_assignments_table();
        
        $conflicts = array();
        
        // Check for existing assignments to the same assignee
        $existing_assignments = $wpdb->get_results($wpdb->prepare(
            "SELECT a.item_id, i.unique_id, m.name as manufacturer, t.name as item_type
             FROM {$assignments_table} a
             LEFT JOIN {$db->get_inventory_items_table()} i ON a.item_id = i.id
             LEFT JOIN {$db->get_manufacturers_table()} m ON i.manufacturer_id = m.id
             LEFT JOIN {$db->get_item_types_table()} t ON i.item_type_id = t.id
             WHERE a.assignee_type = %s AND a.assignee_id = %d AND a.unassigned_date IS NULL",
            $assignee_type, $assignee_id
        ));
        
        if (!empty($existing_assignments)) {
            $conflicts['existing_assignments'] = array(
                'level' => 'info',
                'message' => sprintf(
                    __('Denna mottagare har redan %d utrustning(ar) tilldelad.', 'bkgt-inventory'),
                    count($existing_assignments)
                ),
                'items' => $existing_assignments
            );
        }
        
        // Check for equipment type conflicts (e.g., multiple helmets to one person)
        if ($assignee_type === self::TYPE_INDIVIDUAL) {
            $item_type_conflicts = $wpdb->get_results($wpdb->prepare(
                "SELECT COUNT(*) as count, t.name as item_type, t.id as item_type_id
                 FROM {$assignments_table} a
                 LEFT JOIN {$db->get_inventory_items_table()} i ON a.item_id = i.id
                 LEFT JOIN {$db->get_item_types_table()} t ON i.item_type_id = t.id
                 WHERE a.assignee_type = %s AND a.assignee_id = %d AND a.unassigned_date IS NULL
                 GROUP BY t.id, t.name
                 HAVING COUNT(*) > 1",
                $assignee_type, $assignee_id
            ));
            
            if (!empty($item_type_conflicts)) {
                $conflicts['duplicate_types'] = array(
                    'level' => 'warning',
                    'message' => __('Varning: Flera exemplar av samma utrustningstyp tilldelad till samma person.', 'bkgt-inventory'),
                    'types' => $item_type_conflicts
                );
            }
        }
        
        // Check for condition issues
        $damaged_items = $wpdb->get_results($wpdb->prepare(
            "SELECT i.id, i.unique_id, i.condition, m.name as manufacturer, t.name as item_type
             FROM {$assignments_table} a
             LEFT JOIN {$db->get_inventory_items_table()} i ON a.item_id = i.id
             LEFT JOIN {$db->get_manufacturers_table()} m ON i.manufacturer_id = m.id
             LEFT JOIN {$db->get_item_types_table()} t ON i.item_type_id = t.id
             WHERE a.assignee_type = %s AND a.assignee_id = %d AND a.unassigned_date IS NULL
             AND i.condition IN ('Behöver reparation', 'Förlustanmäld')",
            $assignee_type, $assignee_id
        ));
        
        if (!empty($damaged_items)) {
            $conflicts['damaged_equipment'] = array(
                'level' => 'error',
                'message' => __('Varning: Skadad eller förlorad utrustning är tilldelad.', 'bkgt-inventory'),
                'items' => $damaged_items
            );
        }
        
        return $conflicts;
    }
    
    /**
     * Validate assignment before creation
     */
    public static function validate_assignment($item_ids, $assignee_type, $assignee_id) {
        $errors = array();
        $warnings = array();
        
        // Check each item
        foreach ($item_ids as $item_id) {
            // Verify item exists and is available
            $current_assignment = self::get_assignment($item_id);
            if ($current_assignment['type'] !== '') {
                $errors[] = sprintf(
                    __('Artikel %s är redan tilldelad till %s.', 'bkgt-inventory'),
                    $item_id, $current_assignment['name']
                );
            }
        }
        
        // Check for conflicts
        $conflicts = self::check_assignment_conflicts($assignee_type, $assignee_id, $item_ids);
        
        if (isset($conflicts['damaged_equipment'])) {
            $warnings[] = $conflicts['damaged_equipment']['message'];
        }
        
        if (isset($conflicts['duplicate_types'])) {
            $warnings[] = $conflicts['duplicate_types']['message'];
        }
        
        return array(
            'valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings,
            'conflicts' => $conflicts
        );
    }
    
    /**
     * Get system alerts
     */
    public static function get_system_alerts() {
        global $wpdb;
        
        $db = BKGT_Database::get_instance();
        $alerts = array();
        
        // Overdue assignments (assignments older than 6 months)
        $six_months_ago = date('Y-m-d H:i:s', strtotime('-6 months'));
        $overdue_assignments = $wpdb->get_results($wpdb->prepare(
            "SELECT a.item_id, i.unique_id, a.assigned_date, a.assigned_by,
                    CASE 
                        WHEN a.assignee_type = 'location' THEN 'Plats'
                        WHEN a.assignee_type = 'team' THEN COALESCE(tm.post_title, 'Lag')
                        WHEN a.assignee_type = 'user' THEN COALESCE(um.display_name, 'Användare')
                        ELSE 'Okänd'
                    END as assignee_name
             FROM {$db->get_assignments_table()} a
             LEFT JOIN {$db->get_inventory_items_table()} i ON a.item_id = i.id
             LEFT JOIN {$wpdb->posts} tm ON a.assignee_type = 'team' AND a.assignee_id = tm.ID
             LEFT JOIN {$wpdb->users} um ON a.assignee_type = 'user' AND a.assignee_id = um.ID
             WHERE a.unassigned_date IS NULL AND a.assigned_date < %s
             ORDER BY a.assigned_date ASC
             LIMIT 10",
            $six_months_ago
        ));
        
        if (!empty($overdue_assignments)) {
            $alerts['overdue'] = array(
                'level' => 'warning',
                'title' => __('Försenade tilldelningar', 'bkgt-inventory'),
                'message' => sprintf(
                    __('%d utrustningar har varit tilldelade längre än 6 månader.', 'bkgt-inventory'),
                    count($overdue_assignments)
                ),
                'items' => $overdue_assignments,
                'action' => __('Granska och överväg återlämning', 'bkgt-inventory')
            );
        }
        
        // Equipment needing repair
        $repair_needed = $wpdb->get_results(
            "SELECT i.id, i.unique_id, m.name as manufacturer, t.name as item_type
             FROM {$db->get_inventory_items_table()} i
             LEFT JOIN {$db->get_manufacturers_table()} m ON i.manufacturer_id = m.id
             LEFT JOIN {$db->get_item_types_table()} t ON i.item_type_id = t.id
             WHERE i.condition = 'Behöver reparation'"
        );
        
        if (!empty($repair_needed)) {
            $alerts['repair'] = array(
                'level' => 'error',
                'title' => __('Utrustning behöver reparation', 'bkgt-inventory'),
                'message' => sprintf(
                    __('%d utrustningar är markerade som needing repair.', 'bkgt-inventory'),
                    count($repair_needed)
                ),
                'items' => $repair_needed,
                'action' => __('Kontakta verkstad eller service', 'bkgt-inventory')
            );
        }
        
        // Reported lost equipment
        $lost_equipment = $wpdb->get_results(
            "SELECT i.id, i.unique_id, m.name as manufacturer, t.name as item_type
             FROM {$db->get_inventory_items_table()} i
             LEFT JOIN {$db->get_manufacturers_table()} m ON i.manufacturer_id = m.id
             LEFT JOIN {$db->get_item_types_table()} t ON i.item_type_id = t.id
             WHERE i.condition = 'Förlustanmäld'"
        );
        
        if (!empty($lost_equipment)) {
            $alerts['lost'] = array(
                'level' => 'error',
                'title' => __('Förlorad utrustning', 'bkgt-inventory'),
                'message' => sprintf(
                    __('%d utrustningar är anmälda som förlorade.', 'bkgt-inventory'),
                    count($lost_equipment)
                ),
                'items' => $lost_equipment,
                'action' => __('Utreda och överväg ersättning', 'bkgt-inventory')
            );
        }
        
        // Low stock alerts (less than 2 items of each type available)
        $low_stock = $wpdb->get_results(
            "SELECT t.name as item_type, COUNT(i.id) as available_count
             FROM {$db->get_item_types_table()} t
             LEFT JOIN {$db->get_inventory_items_table()} i ON t.id = i.item_type_id
             LEFT JOIN {$db->get_assignments_table()} a ON i.id = a.item_id AND a.unassigned_date IS NULL
             WHERE a.id IS NULL OR a.unassigned_date IS NOT NULL
             GROUP BY t.id, t.name
             HAVING COUNT(i.id) < 2
             ORDER BY COUNT(i.id) ASC"
        );
        
        if (!empty($low_stock)) {
            $alerts['low_stock'] = array(
                'level' => 'info',
                'title' => __('Lågt lagersaldo', 'bkgt-inventory'),
                'message' => sprintf(
                    __('%d utrustningstyper har färre än 2 tillgängliga exemplar.', 'bkgt-inventory'),
                    count($low_stock)
                ),
                'items' => $low_stock,
                'action' => __('Överväg inköp av fler exemplar', 'bkgt-inventory')
            );
        }
        
        return $alerts;
    }
}
