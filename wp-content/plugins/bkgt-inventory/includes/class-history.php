<?php
/**
 * History Tracking Class
 *
 * @package BKGT_Inventory
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class BKGT_History {
    
    /**
     * Log an action
     */
    public static function log($item_id, $action, $user_id, $data = array()) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'bkgt_inventory_history';
        
        // Ensure table exists
        self::create_history_table();
        
        $log_data = array(
            'item_id' => $item_id,
            'action' => $action,
            'user_id' => $user_id,
            'data' => wp_json_encode($data),
            'timestamp' => current_time('mysql'),
        );
        
        $wpdb->insert($table_name, $log_data);
    }
    
    /**
     * Get history for an item
     */
    public static function get_item_history($item_id, $limit = 50) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'bkgt_inventory_history';
        
        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$table_name} 
                 WHERE item_id = %d 
                 ORDER BY timestamp DESC 
                 LIMIT %d",
                $item_id,
                $limit
            )
        );
        
        // Format results
        foreach ($results as &$result) {
            $result->data = json_decode($result->data, true);
            $result->user = get_userdata($result->user_id);
            $result->user_name = $result->user ? $result->user->display_name : __('Okänd användare', 'bkgt-inventory');
        }
        
        return $results;
    }
    
    /**
     * Get recent history across all items
     */
    public static function get_recent_history($limit = 100) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'bkgt_inventory_history';
        
        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT h.*, p.post_title as item_title 
                 FROM {$table_name} h 
                 LEFT JOIN {$wpdb->posts} p ON h.item_id = p.ID 
                 ORDER BY h.timestamp DESC 
                 LIMIT %d",
                $limit
            )
        );
        
        // Format results
        foreach ($results as &$result) {
            $result->data = json_decode($result->data, true);
            $result->user = get_userdata($result->user_id);
            $result->user_name = $result->user ? $result->user->display_name : __('Okänd användare', 'bkgt-inventory');
        }
        
        return $results;
    }
    
    /**
     * Get history by user
     */
    public static function get_user_history($user_id, $limit = 50) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'bkgt_inventory_history';
        
        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT h.*, p.post_title as item_title 
                 FROM {$table_name} h 
                 LEFT JOIN {$wpdb->posts} p ON h.item_id = p.ID 
                 WHERE h.user_id = %d 
                 ORDER BY h.timestamp DESC 
                 LIMIT %d",
                $user_id,
                $limit
            )
        );
        
        // Format results
        foreach ($results as &$result) {
            $result->data = json_decode($result->data, true);
        }
        
        return $results;
    }
    
    /**
     * Get history by action type
     */
    public static function get_history_by_action($action, $limit = 50) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'bkgt_inventory_history';
        
        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT h.*, p.post_title as item_title 
                 FROM {$table_name} h 
                 LEFT JOIN {$wpdb->posts} p ON h.item_id = p.ID 
                 WHERE h.action = %s 
                 ORDER BY h.timestamp DESC 
                 LIMIT %d",
                $action,
                $limit
            )
        );
        
        // Format results
        foreach ($results as &$result) {
            $result->data = json_decode($result->data, true);
            $result->user = get_userdata($result->user_id);
            $result->user_name = $result->user ? $result->user->display_name : __('Okänd användare', 'bkgt-inventory');
        }
        
        return $results;
    }
    
    /**
     * Get history statistics
     */
    public static function get_statistics() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'bkgt_inventory_history';
        
        $stats = array(
            'total_actions' => $wpdb->get_var("SELECT COUNT(*) FROM {$table_name}"),
            'actions_today' => $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT COUNT(*) FROM {$table_name} WHERE DATE(timestamp) = %s",
                    current_time('Y-m-d')
                )
            ),
            'actions_this_week' => $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT COUNT(*) FROM {$table_name} WHERE timestamp >= %s",
                    date('Y-m-d H:i:s', strtotime('-7 days'))
                )
            ),
            'most_active_users' => $wpdb->get_results(
                "SELECT user_id, COUNT(*) as action_count, u.display_name 
                 FROM {$table_name} h 
                 LEFT JOIN {$wpdb->users} u ON h.user_id = u.ID 
                 GROUP BY user_id 
                 ORDER BY action_count DESC 
                 LIMIT 5"
            ),
            'action_types' => $wpdb->get_results(
                "SELECT action, COUNT(*) as count 
                 FROM {$table_name} 
                 GROUP BY action 
                 ORDER BY count DESC"
            ),
        );
        
        return $stats;
    }
    
    /**
     * Create history table
     */
    private static function create_history_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'bkgt_inventory_history';
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE {$table_name} (
            id int(11) NOT NULL AUTO_INCREMENT,
            item_id bigint(20) NOT NULL,
            action varchar(50) NOT NULL,
            user_id bigint(20) NOT NULL,
            data longtext,
            timestamp datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY item_id (item_id),
            KEY action (action),
            KEY user_id (user_id),
            KEY timestamp (timestamp)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Delete history for an item
     */
    public static function delete_item_history($item_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'bkgt_inventory_history';
        
        $wpdb->delete($table_name, array('item_id' => $item_id));
    }
    
    /**
     * Clean old history entries
     */
    public static function clean_old_history($days_to_keep = 365) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'bkgt_inventory_history';
        $cutoff_date = date('Y-m-d H:i:s', strtotime("-{$days_to_keep} days"));
        
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$table_name} WHERE timestamp < %s",
                $cutoff_date
            )
        );
    }
    
    /**
     * Get human-readable action description
     */
    public static function get_action_description($action, $data = array()) {
        $descriptions = array(
            'created' => __('skapade artikeln', 'bkgt-inventory'),
            'updated' => __('uppdaterade artikeln', 'bkgt-inventory'),
            'deleted' => __('raderade artikeln', 'bkgt-inventory'),
            'assignment_changed' => __('ändrade tilldelning', 'bkgt-inventory'),
            'condition_changed' => __('ändrade skick', 'bkgt-inventory'),
            'location_changed' => __('ändrade lagringsplats', 'bkgt-inventory'),
        );
        
        $description = isset($descriptions[$action]) ? $descriptions[$action] : $action;
        
        // Add details for specific actions
        if ($action === 'assignment_changed' && !empty($data)) {
            $old_type = isset($data['old_assignment_type']) ? $data['old_assignment_type'] : '';
            $new_type = isset($data['new_assignment_type']) ? $data['new_assignment_type'] : '';
            
            $type_names = array(
                'club' => __('klubben', 'bkgt-inventory'),
                'team' => __('lag', 'bkgt-inventory'),
                'individual' => __('individ', 'bkgt-inventory'),
            );
            
            $old_name = isset($type_names[$old_type]) ? $type_names[$old_type] : $old_type;
            $new_name = isset($type_names[$new_type]) ? $type_names[$new_type] : $new_type;
            
            $description .= sprintf(__(' från %s till %s', 'bkgt-inventory'), $old_name, $new_name);
        }
        
        return $description;
    }
}
