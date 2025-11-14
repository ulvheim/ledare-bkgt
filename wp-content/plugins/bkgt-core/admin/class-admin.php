<?php
/**
 * BKGT Core Admin Class
 *
 * Handles admin interface setup and initialization for BKGT Core plugin
 *
 * @package BKGT_Core
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Main admin class for BKGT Core
 */
class BKGT_Core_Admin {
    
    /**
     * Initialize admin functionality
     */
    public function __construct() {
        $this->init_hooks();
    }
    
    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        // Register admin menu
        add_action( 'admin_menu', array( $this, 'add_admin_menu' ), 10 );
        
        // Enqueue admin scripts and styles
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
        
        // AJAX handlers for permission management
        add_action( 'wp_ajax_bkgt_update_role_capability', array( $this, 'ajax_update_role_capability' ) );
        add_action( 'wp_ajax_bkgt_update_user_role', array( $this, 'ajax_update_user_role' ) );
        
        // Initialize error dashboard
        add_action( 'plugins_loaded', array( $this, 'init_error_dashboard' ) );
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        // Add main BKGT menu
        add_menu_page(
            __( 'BKGT System', 'bkgt-core' ),
            __( 'BKGT', 'bkgt-core' ),
            'manage_options',
            'bkgt-dashboard',
            array( $this, 'render_dashboard' ),
            'dashicons-list-view',
            30
        );
        
        // Add dashboard submenu as default page
        add_submenu_page(
            'bkgt-dashboard',
            __( 'Dashboard', 'bkgt-core' ),
            __( 'Dashboard', 'bkgt-core' ),
            'manage_options',
            'bkgt-dashboard',
            array( $this, 'render_dashboard' )
        );
        
        // Add permissions submenu
        add_submenu_page(
            'bkgt-dashboard',
            __( 'Permissions', 'bkgt-core' ),
            __( 'Permissions', 'bkgt-core' ),
            'manage_options',
            'bkgt-permissions',
            array( $this, 'render_permissions_page' )
        );
    }
    
    /**
     * Render main dashboard page
     */
    public function render_dashboard() {
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'BKGT System Dashboard', 'bkgt-core' ); ?></h1>
            
            <div class="bkgt-admin-container">
                <div class="bkgt-admin-card">
                    <h2><?php esc_html_e( 'System Status', 'bkgt-core' ); ?></h2>
                    <p><?php esc_html_e( 'BKGT Core plugin is active and operational.', 'bkgt-core' ); ?></p>
                </div>
                
                <div class="bkgt-admin-card">
                    <h2><?php esc_html_e( 'System Information', 'bkgt-core' ); ?></h2>
                    <ul>
                        <li><?php echo sprintf( esc_html__( 'BKGT Core Version: %s', 'bkgt-core' ), esc_html( BKGT_CORE_VERSION ) ); ?></li>
                        <li><?php echo sprintf( esc_html__( 'WordPress Version: %s', 'bkgt-core' ), esc_html( get_bloginfo( 'version' ) ) ); ?></li>
                        <li><?php echo sprintf( esc_html__( 'PHP Version: %s', 'bkgt-core' ), esc_html( phpversion() ) ); ?></li>
                    </ul>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render permissions management page
     */
    public function render_permissions_page() {
        // Check if user has permission to manage permissions
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( __( 'You do not have sufficient permissions to access this page.', 'bkgt-core' ) );
        }
        
        // Get all roles and capabilities
        global $wp_roles;
        $roles = $wp_roles->roles;
        
        // Ensure BKGT roles are available (fallback if not loaded)
        $bkgt_roles = array(
            'bkgt_admin' => array(
                'name' => __( 'Styrelsemedlem', 'bkgt-core' ),
                'capabilities' => array()
            ),
            'bkgt_coach' => array(
                'name' => __( 'Tränare', 'bkgt-core' ),
                'capabilities' => array()
            ),
            'bkgt_team_manager' => array(
                'name' => __( 'Lagledare', 'bkgt-core' ),
                'capabilities' => array()
            )
        );
        
        // Merge BKGT roles with existing roles, preferring existing ones
        foreach ( $bkgt_roles as $role_key => $role_data ) {
            if ( ! isset( $roles[ $role_key ] ) ) {
                $roles[ $role_key ] = $role_data;
            }
        }
        
        // Filter to BKGT roles and important core roles for the permission matrix
        $matrix_roles = array();
        $important_core_roles = array('administrator', 'editor');
        
        // Add important core roles first
        foreach ( $important_core_roles as $core_role ) {
            if ( isset( $roles[ $core_role ] ) ) {
                $matrix_roles[ $core_role ] = $roles[ $core_role ];
                $matrix_roles[ $core_role ]['name'] .= ' (WP Core)';
            }
        }
        
        // Add BKGT roles
        foreach ( $roles as $role_key => $role ) {
            if ( strpos( $role_key, 'bkgt_' ) === 0 ) {
                $matrix_roles[ $role_key ] = $role;
            }
        }
        
        $capabilities = BKGT_Permission::get_all_capabilities();
        
        // Get role user counts for matrix roles
        $role_counts = array();
        foreach ( array_keys( $matrix_roles ) as $role_key ) {
            $role_counts[ $role_key ] = count( get_users( array( 'role' => $role_key ) ) );
        }
        
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'BKGT Permission Management', 'bkgt-core' ); ?></h1>
            
            <div class="bkgt-admin-container">
                <div class="bkgt-admin-card">
                    <h2><?php esc_html_e( 'Permission Matrix', 'bkgt-core' ); ?></h2>
                    <p><?php esc_html_e( 'Manage role capabilities and permissions across the BKGT system.', 'bkgt-core' ); ?></p>
                    
                    <div class="bkgt-permission-matrix">
                        <table class="wp-list-table widefat fixed striped">
                            <thead>
                                <tr>
                                    <th><?php esc_html_e( 'Capability', 'bkgt-core' ); ?></th>
                                    <?php foreach ( $matrix_roles as $role_key => $role ) : ?>
                                        <th>
                                            <span class="bkgt-role-badge <?php echo strpos( $role_key, 'bkgt_' ) === 0 ? 'bkgt-role-' . $role_key : 'bkgt-role-core'; ?>">
                                                <?php echo esc_html( $role['name'] ); ?>
                                            </span>
                                            <br><small>(<?php echo intval( $role_counts[ $role_key ] ?? 0 ); ?> users)</small>
                                        </th>
                                    <?php endforeach; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ( $capabilities as $capability => $description ) : ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo esc_html( $capability ); ?></strong>
                                            <br><small><?php echo esc_html( $description ); ?></small>
                                        </td>
                                        <?php foreach ( $matrix_roles as $role_key => $role ) : ?>
                                            <td class="bkgt-permission-cell">
                                                <label class="bkgt-permission-toggle">
                                                    <input type="checkbox" 
                                                           class="bkgt-capability-toggle" 
                                                           data-role="<?php echo esc_attr( $role_key ); ?>" 
                                                           data-capability="<?php echo esc_attr( $capability ); ?>"
                                                           <?php checked( isset( $role['capabilities'][ $capability ] ) && $role['capabilities'][ $capability ] ); ?>>
                                                    <span class="bkgt-toggle-slider"></span>
                                                </label>
                                            </td>
                                        <?php endforeach; ?>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <div class="bkgt-admin-card">
                    <h2><?php esc_html_e( 'User Role Assignments', 'bkgt-core' ); ?></h2>
                    <p><?php esc_html_e( 'Assign users to BKGT roles.', 'bkgt-core' ); ?></p>
                    
                    <div class="bkgt-user-roles">
                        <form method="post" action="">
                            <?php wp_nonce_field( 'bkgt_update_user_roles', 'bkgt_user_roles_nonce' ); ?>
                            
                            <table class="wp-list-table widefat fixed striped">
                                <thead>
                                    <tr>
                                        <th><?php esc_html_e( 'User', 'bkgt-core' ); ?></th>
                                        <th><?php esc_html_e( 'Current Role', 'bkgt-core' ); ?></th>
                                        <th><?php esc_html_e( 'Assign BKGT Role', 'bkgt-core' ); ?></th>
                                        <th><?php esc_html_e( 'Actions', 'bkgt-core' ); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $users = get_users( array( 'number' => 20 ) );
                                    foreach ( $users as $user ) :
                                        $user_roles = $user->roles;
                                        $display_role = '';
                                        $display_role_class = 'bkgt-role-none';
                                        
                                        // Check for BKGT roles first
                                        foreach ( $user_roles as $role ) {
                                            if ( strpos( $role, 'bkgt_' ) === 0 ) {
                                                $display_role = $roles[ $role ]['name'] ?? $role;
                                                $display_role_class = 'bkgt-role-' . $role;
                                                break;
                                            }
                                        }
                                        
                                        // If no BKGT role, check for important core roles
                                        if ( empty( $display_role ) ) {
                                            $important_core_roles = array('administrator', 'editor');
                                            foreach ( $important_core_roles as $core_role ) {
                                                if ( in_array( $core_role, $user_roles ) ) {
                                                    $display_role = ($roles[ $core_role ]['name'] ?? $core_role) . ' (WP Core)';
                                                    $display_role_class = 'bkgt-role-core';
                                                    break;
                                                }
                                            }
                                        }
                                        
                                        // If still no role found, show "No BKGT Role"
                                        if ( empty( $display_role ) ) {
                                            $display_role = __( 'No BKGT Role', 'bkgt-core' );
                                        }
                                    ?>
                                        <tr>
                                            <td>
                                                <?php echo esc_html( $user->display_name ); ?>
                                                <br><small><?php echo esc_html( $user->user_email ); ?></small>
                                            </td>
                                            <td>
                                                <?php if ( $display_role !== __( 'No BKGT Role', 'bkgt-core' ) ) : ?>
                                                    <span class="bkgt-role-badge <?php echo esc_attr( $display_role_class ); ?>">
                                                        <?php echo esc_html( $display_role ); ?>
                                                    </span>
                                                <?php else : ?>
                                                    <span class="bkgt-role-badge bkgt-role-none">
                                                        <?php echo esc_html( $display_role ); ?>
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <select name="bkgt_user_roles[<?php echo esc_attr( $user->ID ); ?>]" class="bkgt-role-select">
                                                    <option value=""><?php esc_html_e( 'No BKGT Role', 'bkgt-core' ); ?></option>
                                                    <?php 
                                                    // Show important WordPress core roles
                                                    $core_roles_to_show = array('administrator', 'editor');
                                                    foreach ( $core_roles_to_show as $core_role ) : 
                                                        if ( isset( $roles[ $core_role ] ) ) :
                                                            $is_selected = in_array( $core_role, $user_roles );
                                                    ?>
                                                        <option value="<?php echo esc_attr( $core_role ); ?>" 
                                                                <?php selected( $is_selected, true ); ?>>
                                                            <?php echo esc_html( $roles[ $core_role ]['name'] ); ?> (WP Core)
                                                        </option>
                                                    <?php 
                                                        endif;
                                                    endforeach; 
                                                    
                                                    // Show BKGT roles
                                                    foreach ( $roles as $role_key => $role ) : 
                                                        if ( strpos( $role_key, 'bkgt_' ) === 0 ) :
                                                            $is_selected = in_array( $role_key, $user_roles );
                                                    ?>
                                                            <option value="<?php echo esc_attr( $role_key ); ?>" 
                                                                    <?php selected( $is_selected, true ); ?>>
                                                                <?php echo esc_html( $role['name'] ); ?>
                                                            </option>
                                                    <?php 
                                                        endif;
                                                    endforeach; 
                                                    ?>
                                                </select>
                                            </td>
                                            <td>
                                                <button type="submit" name="bkgt_update_user_role" value="<?php echo esc_attr( $user->ID ); ?>" 
                                                        class="button button-primary bkgt-update-user-role">
                                                    <?php esc_html_e( 'Update', 'bkgt-core' ); ?>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </form>
                    </div>
                </div>
                
                <div class="bkgt-admin-card">
                    <h2><?php esc_html_e( 'Permission Audit Log', 'bkgt-core' ); ?></h2>
                    <p><?php esc_html_e( 'Recent permission changes and access attempts.', 'bkgt-core' ); ?></p>
                    
                    <div class="bkgt-audit-log">
                        <?php
                        $audit_logs = BKGT_Permission::get_audit_log( 10 );
                        if ( empty( $audit_logs ) ) {
                            echo '<p>' . esc_html__( 'No audit logs available.', 'bkgt-core' ) . '</p>';
                        } else {
                            echo '<table class="wp-list-table widefat fixed striped">';
                            echo '<thead><tr>';
                            echo '<th>' . esc_html__( 'Time', 'bkgt-core' ) . '</th>';
                            echo '<th>' . esc_html__( 'User', 'bkgt-core' ) . '</th>';
                            echo '<th>' . esc_html__( 'Action', 'bkgt-core' ) . '</th>';
                            echo '<th>' . esc_html__( 'Details', 'bkgt-core' ) . '</th>';
                            echo '</tr></thead><tbody>';
                            
                            foreach ( $audit_logs as $log ) {
                                echo '<tr>';
                                echo '<td>' . esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $log['timestamp'] ) ) ) . '</td>';
                                echo '<td>' . esc_html( $log['user'] ) . '</td>';
                                echo '<td>' . esc_html( $log['action'] ) . '</td>';
                                echo '<td>' . esc_html( $log['details'] ) . '</td>';
                                echo '</tr>';
                            }
                            
                            echo '</tbody></table>';
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
        
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            // Handle capability toggle
            $('.bkgt-capability-toggle').on('change', function() {
                var $checkbox = $(this);
                var role = $checkbox.data('role');
                var capability = $checkbox.data('capability');
                var enabled = $checkbox.is(':checked');
                
                // Show loading state
                $checkbox.prop('disabled', true);
                
                // AJAX request to update capability
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'bkgt_update_role_capability',
                        role: role,
                        capability: capability,
                        enabled: enabled,
                        nonce: '<?php echo wp_create_nonce( "bkgt_update_role_capability" ); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            // Show success message
                            showNotice('Permission updated successfully', 'success');
                        } else {
                            // Revert checkbox
                            $checkbox.prop('checked', !enabled);
                            showNotice('Failed to update permission: ' + response.data, 'error');
                        }
                    },
                    error: function() {
                        // Revert checkbox
                        $checkbox.prop('checked', !enabled);
                        showNotice('AJAX error occurred', 'error');
                    },
                    complete: function() {
                        $checkbox.prop('disabled', false);
                    }
                });
            });
            
            // Handle user role update
            $('.bkgt-update-user-role').on('click', function(e) {
                e.preventDefault();
                
                var $button = $(this);
                var userId = $button.val();
                var newRole = $('select[name="bkgt_user_roles[' + userId + ']"]').val();
                
                // Show loading state
                $button.prop('disabled', true).text('<?php esc_html_e( "Updating...", "bkgt-core" ); ?>');
                
                // AJAX request to update user role
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'bkgt_update_user_role',
                        user_id: userId,
                        role: newRole,
                        nonce: '<?php echo wp_create_nonce( "bkgt_update_user_role" ); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            showNotice('User role updated successfully', 'success');
                            location.reload(); // Reload to show updated role
                        } else {
                            showNotice('Failed to update user role: ' + response.data, 'error');
                        }
                    },
                    error: function() {
                        showNotice('AJAX error occurred', 'error');
                    },
                    complete: function() {
                        $button.prop('disabled', false).text('<?php esc_html_e( "Update", "bkgt-core" ); ?>');
                    }
                });
            });
            
            function showNotice(message, type) {
                // Remove existing notices
                $('.bkgt-notice').remove();
                
                // Create notice
                var $notice = $('<div class="bkgt-notice notice notice-' + type + ' is-dismissible"><p>' + message + '</p></div>');
                
                // Add to page
                $('.wrap h1').after($notice);
                
                // Auto-dismiss after 5 seconds
                setTimeout(function() {
                    $notice.fadeOut(function() { $(this).remove(); });
                }, 5000);
            }
        });
        </script>
        <?php
    }
    
    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets( $hook ) {
        if ( strpos( $hook, 'bkgt' ) === false ) {
            return;
        }
        
        // Enqueue admin styles
        wp_enqueue_style(
            'bkgt-core-admin',
            BKGT_CORE_URL . 'admin/css/admin.css',
            array(),
            BKGT_CORE_VERSION
        );
        
        // Enqueue admin scripts
        wp_enqueue_script(
            'bkgt-core-admin',
            BKGT_CORE_URL . 'admin/js/admin.js',
            array( 'jquery' ),
            BKGT_CORE_VERSION,
            true
        );
    }
    
    /**
     * AJAX handler for updating role capabilities
     */
    public function ajax_update_role_capability() {
        // Verify nonce and permissions
        if ( ! wp_verify_nonce( $_POST['nonce'], 'bkgt_update_role_capability' ) || ! current_user_can( 'manage_options' ) ) {
            wp_die( __( 'Security check failed', 'bkgt-core' ) );
        }
        
        $role = sanitize_text_field( $_POST['role'] );
        $capability = sanitize_text_field( $_POST['capability'] );
        $enabled = isset( $_POST['enabled'] ) ? (bool) $_POST['enabled'] : false;
        
        // Get the role object
        $role_obj = get_role( $role );
        if ( ! $role_obj ) {
            wp_send_json_error( __( 'Role not found', 'bkgt-core' ) );
        }
        
        // Update capability
        if ( $enabled ) {
            $role_obj->add_cap( $capability );
        } else {
            $role_obj->remove_cap( $capability );
        }
        
        // Log the change
        bkgt_log( 'info', 'Role capability updated', array(
            'role' => $role,
            'capability' => $capability,
            'enabled' => $enabled,
            'user' => wp_get_current_user()->user_login
        ) );
        
        wp_send_json_success( __( 'Capability updated', 'bkgt-core' ) );
    }
    
    /**
     * AJAX handler for updating user roles
     */
    public function ajax_update_user_role() {
        // Verify nonce and permissions
        if ( ! wp_verify_nonce( $_POST['nonce'], 'bkgt_update_user_role' ) || ! current_user_can( 'manage_options' ) ) {
            wp_die( __( 'Security check failed', 'bkgt-core' ) );
        }
        
        $user_id = intval( $_POST['user_id'] );
        $new_role = sanitize_text_field( $_POST['role'] );
        
        // Get user
        $user = get_user_by( 'id', $user_id );
        if ( ! $user ) {
            wp_send_json_error( __( 'User not found', 'bkgt-core' ) );
        }
        
        // Remove all BKGT roles first
        $bkgt_roles = array( 'bkgt_admin', 'bkgt_coach', 'bkgt_team_manager' );
        foreach ( $bkgt_roles as $role ) {
            $user->remove_role( $role );
        }
        
        // Add new role if specified
        if ( ! empty( $new_role ) ) {
            $user->add_role( $new_role );
        }
        
        // Log the change
        bkgt_log( 'info', 'User role updated', array(
            'user_id' => $user_id,
            'user' => $user->user_login,
            'new_role' => $new_role,
            'updated_by' => wp_get_current_user()->user_login
        ) );
        
        wp_send_json_success( __( 'User role updated', 'bkgt-core' ) );
    }
    
    /**
     * Initialize error dashboard
     */
    public function init_error_dashboard() {
        if ( class_exists( 'BKGT_Admin_Error_Dashboard' ) ) {
            BKGT_Admin_Error_Dashboard::init();
        }
    }
}

// Initialize admin only in admin context
if ( is_admin() ) {
    new BKGT_Core_Admin();
}
