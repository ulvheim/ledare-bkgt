<?php
/**
 * BKGT Error Dashboard Admin Page
 * 
 * Displays error logs, system health, and recovery actions.
 * Provides admin with visibility into system issues and remediation options.
 * 
 * @package BKGT
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * BKGT Error Dashboard Admin Class
 */
class BKGT_Admin_Error_Dashboard {
    
    /**
     * Initialize the dashboard
     */
    public static function init() {
        add_action( 'admin_menu', array( __CLASS__, 'add_menu' ), 99 );
        add_action( 'admin_init', array( __CLASS__, 'handle_actions' ) );
    }
    
    /**
     * Add menu page
     */
    public static function add_menu() {
        add_submenu_page(
            'bkgt-dashboard',
            __( 'Felloggar', 'bkgt-core' ),
            __( 'Felloggar', 'bkgt-core' ),
            'manage_options',
            'bkgt-error-log',
            array( __CLASS__, 'render_dashboard' )
        );
    }
    
    /**
     * Handle admin actions (clear logs, reset circuit breakers, etc.)
     */
    public static function handle_actions() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }
        
        // Clear logs
        if ( isset( $_GET['action'] ) && $_GET['action'] === 'bkgt_clear_logs' && check_admin_referer( 'bkgt_clear_logs' ) ) {
            wp_delete_file( WP_CONTENT_DIR . '/bkgt-logs.log' );
            wp_safe_remote_post( add_query_arg( array(
                'page' => 'bkgt-error-log',
                'message' => 'logs_cleared',
            ), admin_url( 'admin.php' ) ) );
        }
        
        // Reset circuit breaker
        if ( isset( $_GET['action'] ) && $_GET['action'] === 'bkgt_reset_breaker' && check_admin_referer( 'bkgt_reset_breaker' ) ) {
            $operation = sanitize_text_field( wp_unslash( $_GET['operation'] ?? '' ) );
            if ( ! empty( $operation ) ) {
                BKGT_Error_Recovery::reset_circuit_breaker( $operation );
                wp_safe_remote_post( add_query_arg( array(
                    'page' => 'bkgt-error-log',
                    'message' => 'breaker_reset',
                ), admin_url( 'admin.php' ) ) );
            }
        }
    }
    
    /**
     * Render dashboard
     */
    public static function render_dashboard() {
        // Get error statistics
        $stats = BKGT_Error_Recovery::get_error_statistics();
        $logs = BKGT_Logger::get_recent_logs( 50 );
        
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'BKGT Felloggar & Systemhälsa', 'bkgt-core' ); ?></h1>
            
            <?php self::render_messages(); ?>
            
            <!-- System Health Dashboard -->
            <div class="bkgt-health-dashboard" style="margin: 20px 0; display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                
                <!-- Total Errors -->
                <div style="background: #fff; border-left: 4px solid #dc3545; padding: 15px; border-radius: 4px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                    <div style="font-size: 28px; font-weight: bold; color: #dc3545;"><?php echo esc_html( $stats['total_errors'] ); ?></div>
                    <div style="color: #666; margin-top: 5px;"><?php esc_html_e( 'Totalt antal fel (senaste 100)', 'bkgt-core' ); ?></div>
                </div>
                
                <!-- Critical Errors -->
                <div style="background: #fff; border-left: 4px solid #721c24; padding: 15px; border-radius: 4px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                    <div style="font-size: 28px; font-weight: bold; color: #721c24;"><?php echo esc_html( $stats['critical'] ); ?></div>
                    <div style="color: #666; margin-top: 5px;"><?php esc_html_e( 'Kritiska fel', 'bkgt-core' ); ?></div>
                </div>
                
                <!-- Errors -->
                <div style="background: #fff; border-left: 4px solid #ff6b6b; padding: 15px; border-radius: 4px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                    <div style="font-size: 28px; font-weight: bold; color: #ff6b6b;"><?php echo esc_html( $stats['errors'] ); ?></div>
                    <div style="color: #666; margin-top: 5px;"><?php esc_html_e( 'Fel', 'bkgt-core' ); ?></div>
                </div>
                
                <!-- Warnings -->
                <div style="background: #fff; border-left: 4px solid #ffc107; padding: 15px; border-radius: 4px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                    <div style="font-size: 28px; font-weight: bold; color: #ffc107;"><?php echo esc_html( $stats['warnings'] ); ?></div>
                    <div style="color: #666; margin-top: 5px;"><?php esc_html_e( 'Varningar', 'bkgt-core' ); ?></div>
                </div>
                
            </div>
            
            <!-- Actions -->
            <div style="margin: 20px 0; background: #fff; padding: 15px; border-radius: 4px; border: 1px solid #e0e0e0;">
                <h3><?php esc_html_e( 'Åtgärder', 'bkgt-core' ); ?></h3>
                <p>
                    <a href="<?php echo esc_url( wp_nonce_url( add_query_arg( array(
                        'page' => 'bkgt-error-log',
                        'action' => 'bkgt_clear_logs',
                    ), admin_url( 'admin.php' ) ), 'bkgt_clear_logs' ) ); ?>" class="button button-secondary" onclick="return confirm('<?php esc_attr_e( 'Är du säker på att du vill ta bort alla loggar?', 'bkgt-core' ); ?>');">
                        <?php esc_html_e( 'Rensa loggar', 'bkgt-core' ); ?>
                    </a>
                </p>
            </div>
            
            <!-- Error Log Table -->
            <div style="margin: 20px 0; background: #fff; border-radius: 4px; border: 1px solid #e0e0e0; overflow: hidden;">
                <div style="padding: 15px; border-bottom: 1px solid #e0e0e0;">
                    <h3 style="margin: 0;"><?php esc_html_e( 'Senaste fel', 'bkgt-core' ); ?></h3>
                </div>
                
                <?php if ( ! empty( $logs ) ) : ?>
                    <table class="wp-list-table widefat striped" style="margin: 0;">
                        <thead>
                            <tr>
                                <th style="width: 120px;"><?php esc_html_e( 'Tid', 'bkgt-core' ); ?></th>
                                <th style="width: 80px;"><?php esc_html_e( 'Nivå', 'bkgt-core' ); ?></th>
                                <th><?php esc_html_e( 'Meddelande', 'bkgt-core' ); ?></th>
                                <th style="width: 100px;"><?php esc_html_e( 'Användare', 'bkgt-core' ); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ( $logs as $log ) : ?>
                                <?php
                                // Parse log entry
                                $parsed = self::parse_log_entry( $log );
                                $level_color = self::get_level_color( $parsed['level'] );
                                ?>
                                <tr>
                                    <td style="color: #666; font-size: 12px;"><?php echo esc_html( $parsed['time'] ); ?></td>
                                    <td>
                                        <span style="display: inline-block; background: <?php echo esc_attr( $level_color ); ?>; color: white; padding: 3px 8px; border-radius: 3px; font-size: 12px; font-weight: bold;">
                                            <?php echo esc_html( strtoupper( $parsed['level'] ) ); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <strong><?php echo esc_html( $parsed['message'] ); ?></strong>
                                        <?php if ( ! empty( $parsed['context'] ) ) : ?>
                                            <br><small style="color: #666;"><?php echo wp_json_encode( $parsed['context'] ); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td style="font-size: 12px; color: #666;"><?php echo esc_html( $parsed['user'] ); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else : ?>
                    <div style="padding: 30px; text-align: center; color: #666;">
                        <p><?php esc_html_e( 'Inga fel i de senaste loggarna', 'bkgt-core' ); ?></p>
                        <p style="font-size: 48px; opacity: 0.3;">✓</p>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- System Info -->
            <div style="margin: 20px 0; background: #fff; border-radius: 4px; border: 1px solid #e0e0e0; padding: 15px;">
                <h3><?php esc_html_e( 'Systeminformation', 'bkgt-core' ); ?></h3>
                <table style="width: 100%;">
                    <tr>
                        <td style="padding: 8px 0; width: 200px;"><strong><?php esc_html_e( 'PHP-version', 'bkgt-core' ); ?></strong></td>
                        <td><?php echo esc_html( phpversion() ); ?></td>
                    </tr>
                    <tr>
                        <td style="padding: 8px 0;"><strong><?php esc_html_e( 'WordPress-version', 'bkgt-core' ); ?></strong></td>
                        <td><?php echo esc_html( get_bloginfo( 'version' ) ); ?></td>
                    </tr>
                    <tr>
                        <td style="padding: 8px 0;"><strong><?php esc_html_e( 'BKGT Core Version', 'bkgt-core' ); ?></strong></td>
                        <td><?php echo esc_html( BKGT_CORE_VERSION ); ?></td>
                    </tr>
                    <tr>
                        <td style="padding: 8px 0;"><strong><?php esc_html_e( 'Felsökningsläge', 'bkgt-core' ); ?></strong></td>
                        <td><?php echo defined( 'WP_DEBUG' ) && WP_DEBUG ? '<span style="color: #28a745;">✓ Aktiverad</span>' : '<span style="color: #dc3545;">✗ Inaktiverad</span>'; ?></td>
                    </tr>
                    <tr>
                        <td style="padding: 8px 0;"><strong><?php esc_html_e( 'Felloggar-fil', 'bkgt-core' ); ?></strong></td>
                        <td style="font-family: monospace; font-size: 12px; word-break: break-all;">
                            <?php echo esc_html( WP_CONTENT_DIR . '/bkgt-logs.log' ); ?>
                            <?php if ( file_exists( WP_CONTENT_DIR . '/bkgt-logs.log' ) ) : ?>
                                <br><small><?php printf( esc_html__( 'Storlek: %s', 'bkgt-core' ), esc_html( size_format( filesize( WP_CONTENT_DIR . '/bkgt-logs.log' ), 2 ) ) ); ?></small>
                            <?php endif; ?>
                        </td>
                    </tr>
                </table>
            </div>
            
        </div>
        
        <style>
            .bkgt-health-dashboard {
                margin: 20px 0;
            }
            
            .bkgt-notice {
                padding: 12px 15px;
                margin: 15px 0;
                border-radius: 4px;
                border-left: 4px solid;
            }
            
            .bkgt-notice-success {
                background: #d4edda;
                border-left-color: #28a745;
                color: #155724;
            }
            
            .bkgt-notice-error {
                background: #f8d7da;
                border-left-color: #dc3545;
                color: #721c24;
            }
            
            .bkgt-notice-warning {
                background: #fff3cd;
                border-left-color: #ffc107;
                color: #856404;
            }
        </style>
        <?php
    }
    
    /**
     * Render admin messages
     */
    private static function render_messages() {
        if ( isset( $_GET['message'] ) ) {
            $message = sanitize_text_field( wp_unslash( $_GET['message'] ) );
            
            if ( $message === 'logs_cleared' ) {
                echo '<div class="bkgt-notice bkgt-notice-success">';
                echo esc_html__( 'Loggar rensade framgångsrikt', 'bkgt-core' );
                echo '</div>';
            } elseif ( $message === 'breaker_reset' ) {
                echo '<div class="bkgt-notice bkgt-notice-success">';
                echo esc_html__( 'Kretsbrytare återställd', 'bkgt-core' );
                echo '</div>';
            }
        }
    }
    
    /**
     * Parse log entry
     * 
     * @param string $log
     * 
     * @return array
     */
    private static function parse_log_entry( $log ) {
        $parsed = array(
            'time' => '',
            'level' => '',
            'message' => '',
            'user' => '',
            'url' => '',
            'context' => '',
        );
        
        // Extract timestamp
        if ( preg_match( '/\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\]/', $log, $matches ) ) {
            $parsed['time'] = $matches[1];
        }
        
        // Extract level
        if ( preg_match( '/\[(\w+)\]/', $log, $matches ) ) {
            $parsed['level'] = strtolower( $matches[1] );
        }
        
        // Extract message (first main text)
        if ( preg_match( '/\] ([^|]+) \|/', $log, $matches ) ) {
            $parsed['message'] = trim( $matches[1] );
        }
        
        // Extract user
        if ( preg_match( '/User: ([^|]+)/', $log, $matches ) ) {
            $parsed['user'] = trim( $matches[1] );
        }
        
        // Extract context
        if ( preg_match( '/Context: ({.*?})(?: \||$)/', $log, $matches ) ) {
            $parsed['context'] = json_decode( $matches[1], true );
        }
        
        return $parsed;
    }
    
    /**
     * Get color for log level
     * 
     * @param string $level
     * 
     * @return string
     */
    private static function get_level_color( $level ) {
        $colors = array(
            'critical' => '#721c24',
            'error' => '#dc3545',
            'warning' => '#ffc107',
            'info' => '#17a2b8',
            'debug' => '#6c757d',
        );
        
        return $colors[ $level ] ?? '#6c757d';
    }
}

// Initialize dashboard
add_action( 'wp_loaded', array( 'BKGT_Admin_Error_Dashboard', 'init' ) );
