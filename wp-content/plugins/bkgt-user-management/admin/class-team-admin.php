<?php
/**
 * Team Admin Customization
 *
 * @package BKGT_User_Management
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class BKGT_Team_Admin {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        add_action('save_post_bkgt_team', array($this, 'save_team_meta'), 10, 2);
        add_filter('manage_bkgt_team_posts_columns', array($this, 'customize_team_columns'));
        add_action('manage_bkgt_team_posts_custom_column', array($this, 'render_team_columns'), 10, 2);
    }
    
    /**
     * Add meta boxes to team edit screen
     */
    public function add_meta_boxes() {
        add_meta_box(
            'bkgt_team_info',
            __('Laginformation', 'bkgt-user-management'),
            array($this, 'render_team_info_meta_box'),
            'bkgt_team',
            'side',
            'default'
        );
        
        add_meta_box(
            'bkgt_team_members',
            __('Lagmedlemmar', 'bkgt-user-management'),
            array($this, 'render_team_members_meta_box'),
            'bkgt_team',
            'normal',
            'default'
        );
    }
    
    /**
     * Render team info meta box
     */
    public function render_team_info_meta_box($post) {
        $stats = BKGT_Team::get_team_stats($post->ID);
        
        ?>
        <div class="bkgt-team-stats">
            <p>
                <strong><?php esc_html_e('Tränare:', 'bkgt-user-management'); ?></strong><br>
                <?php echo $stats['coaches_count']; ?>
            </p>
            <p>
                <strong><?php esc_html_e('Lagledare:', 'bkgt-user-management'); ?></strong><br>
                <?php echo $stats['managers_count']; ?>
            </p>
            <p>
                <strong><?php esc_html_e('Totalt medlemmar:', 'bkgt-user-management'); ?></strong><br>
                <?php echo $stats['members_count']; ?>
            </p>
        </div>
        <?php
    }
    
    /**
     * Render team members meta box
     */
    public function render_team_members_meta_box($post) {
        $members = BKGT_Team::get_team_members($post->ID);
        
        if (empty($members)) {
            echo '<p>' . esc_html__('Inga medlemmar tilldelade till detta lag än.', 'bkgt-user-management') . '</p>';
            return;
        }
        
        ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php esc_html_e('Namn', 'bkgt-user-management'); ?></th>
                    <th><?php esc_html_e('E-post', 'bkgt-user-management'); ?></th>
                    <th><?php esc_html_e('Roll', 'bkgt-user-management'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($members as $member): ?>
                <tr>
                    <td>
                        <a href="<?php echo get_edit_user_link($member->ID); ?>">
                            <?php echo esc_html($member->display_name); ?>
                        </a>
                    </td>
                    <td><?php echo esc_html($member->user_email); ?></td>
                    <td><?php echo esc_html(BKGT_Capabilities::get_user_role_label($member->ID)); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php
    }
    
    /**
     * Save team meta
     */
    public function save_team_meta($post_id, $post) {
        // Verify nonce
        if (!isset($_POST['_wpnonce'])) {
            return;
        }
        
        // Autosave check
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        // Permission check
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        // Custom meta saving logic can be added here
    }
    
    /**
     * Customize team list columns
     */
    public function customize_team_columns($columns) {
        $new_columns = array(
            'cb'      => $columns['cb'],
            'title'   => $columns['title'],
            'coaches' => __('Tränare', 'bkgt-user-management'),
            'managers' => __('Lagledare', 'bkgt-user-management'),
            'members' => __('Totalt medlemmar', 'bkgt-user-management'),
            'date'    => $columns['date'],
        );
        
        return $new_columns;
    }
    
    /**
     * Render custom team columns
     */
    public function render_team_columns($column, $post_id) {
        switch ($column) {
            case 'coaches':
                $stats = BKGT_Team::get_team_stats($post_id);
                echo $stats['coaches_count'];
                break;
                
            case 'managers':
                $stats = BKGT_Team::get_team_stats($post_id);
                echo $stats['managers_count'];
                break;
                
            case 'members':
                $stats = BKGT_Team::get_team_stats($post_id);
                echo $stats['members_count'];
                break;
        }
    }
}
