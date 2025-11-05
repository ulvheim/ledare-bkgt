<?php
/**
 * Analytics and Recommendations Class
 *
 * @package BKGT_Inventory
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class BKGT_Inventory_Analytics {

    /**
     * Get analytics-driven recommendations for inventory quantities
     */
    public static function get_quantity_recommendations($item_type_id = null) {
        global $wpdb;

        $recommendations = array();

        // Get current team size (from user management plugin if available)
        $team_size = self::get_current_team_size();

        // Get historical usage data
        $usage_data = self::get_historical_usage_data($item_type_id);

        // Calculate seasonal patterns
        $seasonal_patterns = self::calculate_seasonal_patterns($item_type_id);

        // Generate recommendations
        if ($item_type_id) {
            $recommendations = self::generate_item_recommendations($item_type_id, $team_size, $usage_data, $seasonal_patterns);
        } else {
            // Get all item types and generate recommendations
            $item_types = BKGT_Item_Type::get_all();
            foreach ($item_types as $item_type) {
                $type_recommendations = self::generate_item_recommendations($item_type->id, $team_size, $usage_data, $seasonal_patterns);
                $recommendations = array_merge($recommendations, $type_recommendations);
            }
        }

        return $recommendations;
    }

    /**
     * Get current team size
     */
    private static function get_current_team_size() {
        global $wpdb;

        // Try to get from team/player plugin if available
        if (class_exists('BKGT_Team_Player_Management')) {
            $team_size = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}bkgt_players WHERE active = 1");
            if ($team_size) {
                return $team_size;
            }
        }

        // Fallback: estimate based on historical assignments
        $avg_team_size = $wpdb->get_var("
            SELECT AVG(player_count) FROM (
                SELECT COUNT(DISTINCT assignee_id) as player_count, DATE_FORMAT(assignment_date, '%Y-%m') as month
                FROM {$wpdb->prefix}bkgt_inventory_assignments
                WHERE assignee_id IS NOT NULL
                GROUP BY DATE_FORMAT(assignment_date, '%Y-%m')
                ORDER BY month DESC
                LIMIT 6
            ) as monthly_counts
        ");

        return max($avg_team_size ?: 25, 10); // Default to 25, minimum 10
    }

    /**
     * Get historical usage data
     */
    private static function get_historical_usage_data($item_type_id = null) {
        global $wpdb;

        $where_clause = $item_type_id ? $wpdb->prepare("AND pm.meta_value = %d", $item_type_id) : "";

        $usage_data = $wpdb->get_results("
            SELECT
                it.name as item_type,
                COUNT(a.id) as total_assignments,
                COUNT(DISTINCT a.item_id) as unique_items_used,
                AVG(DATEDIFF(COALESCE(a.return_date, CURDATE()), a.assignment_date)) as avg_usage_days,
                COUNT(CASE WHEN a.return_date IS NULL THEN 1 END) as currently_assigned,
                MAX(a.assignment_date) as last_assignment
            FROM {$wpdb->prefix}bkgt_inventory_assignments a
            JOIN {$wpdb->posts} p ON a.item_id = p.ID
            JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_bkgt_item_type_id'
            JOIN {$wpdb->prefix}bkgt_item_types it ON pm.meta_value = it.id
            WHERE 1=1 {$where_clause}
            GROUP BY it.id, it.name
            ORDER BY total_assignments DESC
        ");

        return $usage_data;
    }

    /**
     * Calculate seasonal patterns
     */
    private static function calculate_seasonal_patterns($item_type_id = null) {
        global $wpdb;

        $where_clause = $item_type_id ? $wpdb->prepare("AND pm.meta_value = %d", $item_type_id) : "";

        $seasonal_data = $wpdb->get_results("
            SELECT
                MONTH(a.assignment_date) as month,
                COUNT(a.id) as assignments,
                it.name as item_type
            FROM {$wpdb->prefix}bkgt_inventory_assignments a
            JOIN {$wpdb->posts} p ON a.item_id = p.ID
            JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_bkgt_item_type_id'
            JOIN {$wpdb->prefix}bkgt_item_types it ON pm.meta_value = it.id
            WHERE YEAR(a.assignment_date) >= YEAR(CURDATE()) - 2
            {$where_clause}
            GROUP BY MONTH(a.assignment_date), it.id, it.name
            ORDER BY month
        ");

        $patterns = array();
        foreach ($seasonal_data as $data) {
            if (!isset($patterns[$data->item_type])) {
                $patterns[$data->item_type] = array_fill(1, 12, 0);
            }
            $patterns[$data->item_type][$data->month] = $data->assignments;
        }

        return $patterns;
    }

    /**
     * Generate recommendations for a specific item type
     */
    private static function generate_item_recommendations($item_type_id, $team_size, $usage_data, $seasonal_patterns) {
        global $wpdb;

        $item_type = BKGT_Item_Type::get($item_type_id);
        if (!$item_type) return array();

        $recommendations = array();

        // Get current inventory count
        $current_count = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) FROM {$wpdb->posts} p
            JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_bkgt_item_type_id'
            WHERE p.post_type = 'bkgt_inventory_item'
            AND pm.meta_value = %d
        ", $item_type_id));

        // Find usage data for this item type
        $type_usage = null;
        foreach ($usage_data as $usage) {
            if ($usage->item_type === $item_type->name) {
                $type_usage = $usage;
                break;
            }
        }

        if ($type_usage) {
            // Calculate recommended quantity based on usage patterns
            $recommended_quantity = self::calculate_recommended_quantity($type_usage, $team_size, $current_count);

            // Calculate seasonal adjustment
            $seasonal_multiplier = self::get_seasonal_multiplier($item_type->name, $seasonal_patterns);

            $final_recommendation = round($recommended_quantity * $seasonal_multiplier);

            $recommendations[] = array(
                'item_type_id' => $item_type_id,
                'item_type_name' => $item_type->name,
                'current_quantity' => $current_count,
                'recommended_quantity' => $final_recommendation,
                'confidence_level' => self::calculate_confidence_level($type_usage),
                'reasoning' => self::generate_recommendation_reasoning($type_usage, $team_size, $seasonal_multiplier),
                'seasonal_adjustment' => $seasonal_multiplier,
                'avg_usage_days' => round($type_usage->avg_usage_days ?: 0, 1),
                'utilization_rate' => $current_count > 0 ? round(($type_usage->currently_assigned / $current_count) * 100, 1) : 0
            );
        }

        return $recommendations;
    }

    /**
     * Calculate recommended quantity
     */
    private static function calculate_recommended_quantity($usage_data, $team_size, $current_count) {
        // Base calculation: assignments per month * average usage days / 30 * buffer factor
        $monthly_assignments = $usage_data->total_assignments / max(1, $usage_data->unique_items_used);

        // Estimate items needed per player per season
        $items_per_player = $monthly_assignments * 12 / max($team_size, 1);

        // Add buffer for maintenance, losses, etc.
        $buffer_factor = 1.2; // 20% buffer

        $recommended = $items_per_player * $team_size * $buffer_factor;

        // Don't recommend less than current count unless utilization is very low
        $utilization_rate = $current_count > 0 ? ($usage_data->currently_assigned / $current_count) : 0;

        if ($utilization_rate < 0.3 && $recommended < $current_count) {
            $recommended = $current_count * 0.9; // Reduce by 10% if underutilized
        }

        return max($recommended, 1); // At least 1 item
    }

    /**
     * Get seasonal multiplier
     */
    private static function get_seasonal_multiplier($item_type_name, $seasonal_patterns) {
        $current_month = date('n');

        if (isset($seasonal_patterns[$item_type_name])) {
            $monthly_data = $seasonal_patterns[$item_type_name];
            $current_month_usage = $monthly_data[$current_month] ?: 1;
            $avg_usage = array_sum($monthly_data) / count(array_filter($monthly_data)) ?: 1;

            $multiplier = $current_month_usage / $avg_usage;

            // Limit multiplier to reasonable range
            return max(0.7, min(1.5, $multiplier));
        }

        return 1.0; // No seasonal adjustment
    }

    /**
     * Calculate confidence level
     */
    private static function calculate_confidence_level($usage_data) {
        $assignments = $usage_data->total_assignments;
        $months_of_data = 12; // Assume we have at least a year of data

        if ($assignments > 100) return 'high';
        if ($assignments > 50) return 'medium';
        if ($assignments > 10) return 'low';
        return 'very_low';
    }

    /**
     * Generate recommendation reasoning
     */
    private static function generate_recommendation_reasoning($usage_data, $team_size, $seasonal_multiplier) {
        $reasoning = array();

        $reasoning[] = sprintf(__('Baserat på %d tilldelningar till %d spelare', 'bkgt-inventory'),
            $usage_data->total_assignments, $team_size);

        if ($seasonal_multiplier > 1.1) {
            $reasoning[] = __('Högsäsong - ökad efterfrågan förväntas', 'bkgt-inventory');
        } elseif ($seasonal_multiplier < 0.9) {
            $reasoning[] = __('Lågsäsong - minskad efterfrågan förväntas', 'bkgt-inventory');
        }

        $utilization_rate = $usage_data->currently_assigned / max(1, $usage_data->unique_items_used) * 100;
        if ($utilization_rate > 80) {
            $reasoning[] = __('Hög användningsgrad - fler artiklar rekommenderas', 'bkgt-inventory');
        } elseif ($utilization_rate < 30) {
            $reasoning[] = __('Låg användningsgrad - färre artiklar kan räcka', 'bkgt-inventory');
        }

        return implode('. ', $reasoning) . '.';
    }

    /**
     * Get inventory optimization suggestions
     */
    public static function get_optimization_suggestions() {
        global $wpdb;

        $suggestions = array();

        // Find overstocked items
        $overstocked = $wpdb->get_results("
            SELECT p.ID, p.post_title, COUNT(a.id) as assignments, MAX(a.assignment_date) as last_used
            FROM {$wpdb->posts} p
            LEFT JOIN {$wpdb->prefix}bkgt_inventory_assignments a ON p.ID = a.item_id
            WHERE p.post_type = 'bkgt_inventory_item'
            GROUP BY p.ID, p.post_title
            HAVING assignments = 0 OR last_used < DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
            ORDER BY last_used ASC
            LIMIT 10
        ");

        if (!empty($overstocked)) {
            $suggestions[] = array(
                'type' => 'overstocked',
                'title' => __('Överlagrade artiklar', 'bkgt-inventory'),
                'description' => sprintf(__('Följande artiklar har inte använts på 6+ månader: %s', 'bkgt-inventory'),
                    implode(', ', array_map(function($item) { return $item->post_title; }, array_slice($overstocked, 0, 3)))),
                'items' => $overstocked,
                'action' => __('Överväg att sälja eller donera dessa artiklar', 'bkgt-inventory')
            );
        }

        // Find frequently damaged items
        $damaged_items = $wpdb->get_results("
            SELECT p.post_title, COUNT(*) as damage_count
            FROM {$wpdb->posts} p
            JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
            WHERE p.post_type = 'bkgt_inventory_item'
            AND pm.meta_key = '_bkgt_condition_status'
            AND pm.meta_value IN ('needs_repair', 'reported_lost')
            GROUP BY p.ID, p.post_title
            ORDER BY damage_count DESC
            LIMIT 5
        ");

        if (!empty($damaged_items)) {
            $suggestions[] = array(
                'type' => 'maintenance',
                'title' => __('Artiklar som behöver underhåll', 'bkgt-inventory'),
                'description' => sprintf(__('Följande artiklar har ofta problem: %s', 'bkgt-inventory'),
                    implode(', ', array_map(function($item) { return $item->post_title; }, $damaged_items))),
                'items' => $damaged_items,
                'action' => __('Överväg högre kvalitetsalternativ eller förbättrat underhåll', 'bkgt-inventory')
            );
        }

        return $suggestions;
    }
}