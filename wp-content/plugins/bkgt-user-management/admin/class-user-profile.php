<?php
/**
 * User Profile Customization
 *
 * @package BKGT_User_Management
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class BKGT_User_Profile {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_filter('user_contactmethods', array($this, 'add_custom_contact_fields'));
        add_action('show_user_profile', array($this, 'display_team_info'));
        add_action('edit_user_profile', array($this, 'display_team_info'));
    }
    
    /**
     * Add custom contact fields
     */
    public function add_custom_contact_fields($fields) {
        $fields['phone'] = __('Telefon', 'bkgt-user-management');
        $fields['emergency_contact'] = __('Nödkontakt', 'bkgt-user-management');
        $fields['emergency_phone'] = __('Nödkontakt telefon', 'bkgt-user-management');
        
        return $fields;
    }
    
    /**
     * Display team info on profile
     */
    public function display_team_info($user) {
        $assigned_teams = BKGT_User_Team_Assignment::get_user_teams($user->ID);
        
        if (empty($assigned_teams)) {
            return;
        }
        
        ?>
        <h2><?php esc_html_e('Laginformation', 'bkgt-user-management'); ?></h2>
        <table class="form-table">
            <tr>
                <th><?php esc_html_e('Mina lag', 'bkgt-user-management'); ?></th>
                <td>
                    <?php foreach ($assigned_teams as $team_id): 
                        $team = BKGT_Team::get_team($team_id);
                        if ($team):
                    ?>
                        <span style="display: inline-block; padding: 5px 10px; background: #f0f0f0; border-radius: 3px; margin: 2px;">
                            <?php echo esc_html($team->post_title); ?>
                        </span>
                    <?php 
                        endif;
                    endforeach; 
                    ?>
                </td>
            </tr>
        </table>
        <?php
    }
}
