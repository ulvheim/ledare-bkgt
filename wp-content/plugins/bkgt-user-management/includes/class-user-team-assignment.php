<?php
/**
 * User Team Assignment Class
 *
 * @package BKGT_User_Management
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class BKGT_User_Team_Assignment {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('show_user_profile', array($this, 'display_team_assignment_fields'));
        add_action('edit_user_profile', array($this, 'display_team_assignment_fields'));
        add_action('personal_options_update', array($this, 'save_team_assignment_fields'));
        add_action('edit_user_profile_update', array($this, 'save_team_assignment_fields'));
    }
    
    /**
     * Get user's assigned teams
     */
    public static function get_user_teams($user_id) {
        $teams = get_user_meta($user_id, 'bkgt_assigned_teams', true);
        return is_array($teams) ? $teams : array();
    }
    
    /**
     * Assign user to team
     */
    public static function assign_user_to_team($user_id, $team_id) {
        $teams = self::get_user_teams($user_id);
        
        if (!in_array($team_id, $teams)) {
            $teams[] = $team_id;
            update_user_meta($user_id, 'bkgt_assigned_teams', $teams);
            
            // Log the assignment
            do_action('bkgt_user_assigned_to_team', $user_id, $team_id);
            
            return true;
        }
        
        return false;
    }
    
    /**
     * Remove user from team
     */
    public static function remove_user_from_team($user_id, $team_id) {
        $teams = self::get_user_teams($user_id);
        $key = array_search($team_id, $teams);
        
        if ($key !== false) {
            unset($teams[$key]);
            update_user_meta($user_id, 'bkgt_assigned_teams', array_values($teams));
            
            // Log the removal
            do_action('bkgt_user_removed_from_team', $user_id, $team_id);
            
            return true;
        }
        
        return false;
    }
    
    /**
     * Update user's team assignments
     */
    public static function update_user_teams($user_id, $team_ids) {
        if (!is_array($team_ids)) {
            $team_ids = array();
        }
        
        // Validate team IDs
        $valid_team_ids = array();
        foreach ($team_ids as $team_id) {
            if (BKGT_Team::get_team($team_id)) {
                $valid_team_ids[] = (int) $team_id;
            }
        }
        
        update_user_meta($user_id, 'bkgt_assigned_teams', $valid_team_ids);
        
        do_action('bkgt_user_teams_updated', $user_id, $valid_team_ids);
        
        return true;
    }
    
    /**
     * Check if user is assigned to team
     */
    public static function is_user_in_team($user_id, $team_id) {
        $teams = self::get_user_teams($user_id);
        return in_array($team_id, $teams);
    }
    
    /**
     * Get all users in a team
     */
    public static function get_team_users($team_id, $role = '') {
        return BKGT_Team::get_team_members($team_id, $role);
    }
    
    /**
     * Display team assignment fields on user profile
     */
    public function display_team_assignment_fields($user) {
        // Only admins can assign teams
        if (!current_user_can('manage_options')) {
            return;
        }
        
        $assigned_teams = self::get_user_teams($user->ID);
        $all_teams = BKGT_Team::get_all_teams();
        
        ?>
        <h2><?php esc_html_e('Laguppdelning', 'bkgt-user-management'); ?></h2>
        <table class="form-table">
            <tr>
                <th>
                    <label><?php esc_html_e('Tilldelade lag', 'bkgt-user-management'); ?></label>
                </th>
                <td>
                    <?php if (!empty($all_teams)): ?>
                        <?php foreach ($all_teams as $team): ?>
                            <label style="display: block; margin-bottom: 5px;">
                                <input 
                                    type="checkbox" 
                                    name="bkgt_assigned_teams[]" 
                                    value="<?php echo esc_attr($team->ID); ?>"
                                    <?php checked(in_array($team->ID, $assigned_teams)); ?>
                                >
                                <?php echo esc_html($team->post_title); ?>
                            </label>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="description">
                            <?php esc_html_e('Inga lag är skapade än.', 'bkgt-user-management'); ?>
                        </p>
                    <?php endif; ?>
                    <p class="description">
                        <?php esc_html_e('Välj vilka lag denna användare ska ha tillgång till.', 'bkgt-user-management'); ?>
                    </p>
                </td>
            </tr>
        </table>
        <?php
    }
    
    /**
     * Save team assignment fields
     */
    public function save_team_assignment_fields($user_id) {
        // Only admins can assign teams
        if (!current_user_can('manage_options')) {
            return;
        }
        
        // Verify nonce
        if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'update-user_' . $user_id)) {
            return;
        }
        
        $team_ids = isset($_POST['bkgt_assigned_teams']) ? $_POST['bkgt_assigned_teams'] : array();
        self::update_user_teams($user_id, $team_ids);
    }
}

// Initialize
new BKGT_User_Team_Assignment();
