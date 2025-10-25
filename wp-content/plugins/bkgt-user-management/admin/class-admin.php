<?php
/**
 * Admin Interface
 *
 * @package BKGT_User_Management
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class BKGT_User_Management_Admin {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_menu_page(
            __('Användarhantering', 'bkgt-user-management'),
            __('Användarhantering', 'bkgt-user-management'),
            'manage_options',
            'bkgt-user-management',
            array($this, 'render_dashboard_page'),
            'dashicons-admin-users',
            30
        );
        
        add_submenu_page(
            'bkgt-user-management',
            __('Översikt', 'bkgt-user-management'),
            __('Översikt', 'bkgt-user-management'),
            'manage_options',
            'bkgt-user-management',
            array($this, 'render_dashboard_page')
        );
        
        add_submenu_page(
            'bkgt-user-management',
            __('Laguppdelningar', 'bkgt-user-management'),
            __('Laguppdelningar', 'bkgt-user-management'),
            'manage_options',
            'bkgt-team-assignments',
            array($this, 'render_team_assignments_page')
        );
    }
    
    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook) {
        if (strpos($hook, 'bkgt-user-management') === false && 
            strpos($hook, 'bkgt-team-assignments') === false) {
            return;
        }
        
        wp_enqueue_style(
            'bkgt-um-admin',
            BKGT_UM_PLUGIN_URL . 'assets/admin.css',
            array(),
            BKGT_UM_VERSION
        );
        
        wp_enqueue_script(
            'bkgt-um-admin',
            BKGT_UM_PLUGIN_URL . 'assets/admin.js',
            array('jquery'),
            BKGT_UM_VERSION,
            true
        );
    }
    
    /**
     * Render dashboard page
     */
    public function render_dashboard_page() {
        $all_teams = BKGT_Team::get_all_teams();
        $total_users = count_users();
        
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Användarhantering', 'bkgt-user-management'); ?></h1>
            
            <div class="bkgt-dashboard-stats">
                <div class="bkgt-stat-card">
                    <h3><?php esc_html_e('Totalt antal lag', 'bkgt-user-management'); ?></h3>
                    <div class="bkgt-stat-number"><?php echo count($all_teams); ?></div>
                </div>
                
                <div class="bkgt-stat-card">
                    <h3><?php esc_html_e('Totalt antal användare', 'bkgt-user-management'); ?></h3>
                    <div class="bkgt-stat-number"><?php echo $total_users['total_users']; ?></div>
                </div>
            </div>
            
            <h2><?php esc_html_e('Lag', 'bkgt-user-management'); ?></h2>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Lagnamn', 'bkgt-user-management'); ?></th>
                        <th><?php esc_html_e('Tränare', 'bkgt-user-management'); ?></th>
                        <th><?php esc_html_e('Lagledare', 'bkgt-user-management'); ?></th>
                        <th><?php esc_html_e('Totalt medlemmar', 'bkgt-user-management'); ?></th>
                        <th><?php esc_html_e('Åtgärder', 'bkgt-user-management'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($all_teams as $team): 
                        $stats = BKGT_Team::get_team_stats($team->ID);
                    ?>
                    <tr>
                        <td><strong><?php echo esc_html($team->post_title); ?></strong></td>
                        <td><?php echo $stats['coaches_count']; ?></td>
                        <td><?php echo $stats['managers_count']; ?></td>
                        <td><?php echo $stats['members_count']; ?></td>
                        <td>
                            <a href="<?php echo get_edit_post_link($team->ID); ?>" class="button button-small">
                                <?php esc_html_e('Redigera', 'bkgt-user-management'); ?>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }
    
    /**
     * Render team assignments page
     */
    public function render_team_assignments_page() {
        $all_teams = BKGT_Team::get_all_teams();
        $selected_team = isset($_GET['team_id']) ? intval($_GET['team_id']) : 0;
        
        if ($selected_team && !BKGT_Team::get_team($selected_team)) {
            $selected_team = 0;
        }
        
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Laguppdelningar', 'bkgt-user-management'); ?></h1>
            
            <form method="get" action="">
                <input type="hidden" name="page" value="bkgt-team-assignments">
                <label for="team_id"><?php esc_html_e('Välj lag:', 'bkgt-user-management'); ?></label>
                <select name="team_id" id="team_id" onchange="this.form.submit()">
                    <option value="0"><?php esc_html_e('-- Välj ett lag --', 'bkgt-user-management'); ?></option>
                    <?php foreach ($all_teams as $team): ?>
                        <option value="<?php echo esc_attr($team->ID); ?>" <?php selected($selected_team, $team->ID); ?>>
                            <?php echo esc_html($team->post_title); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>
            
            <?php if ($selected_team): 
                $team = BKGT_Team::get_team($selected_team);
                $members = BKGT_Team::get_team_members($selected_team);
            ?>
                <h2><?php echo esc_html($team->post_title); ?> - <?php esc_html_e('Medlemmar', 'bkgt-user-management'); ?></h2>
                
                <?php if (!empty($members)): ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Namn', 'bkgt-user-management'); ?></th>
                            <th><?php esc_html_e('E-post', 'bkgt-user-management'); ?></th>
                            <th><?php esc_html_e('Roll', 'bkgt-user-management'); ?></th>
                            <th><?php esc_html_e('Åtgärder', 'bkgt-user-management'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($members as $member): ?>
                        <tr>
                            <td><?php echo esc_html($member->display_name); ?></td>
                            <td><?php echo esc_html($member->user_email); ?></td>
                            <td><?php echo esc_html(BKGT_Capabilities::get_user_role_label($member->ID)); ?></td>
                            <td>
                                <a href="<?php echo get_edit_user_link($member->ID); ?>" class="button button-small">
                                    <?php esc_html_e('Redigera', 'bkgt-user-management'); ?>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <p><?php esc_html_e('Inga medlemmar tilldelade till detta lag än.', 'bkgt-user-management'); ?></p>
                <?php endif; ?>
            <?php endif; ?>
        </div>
        <?php
    }
}
