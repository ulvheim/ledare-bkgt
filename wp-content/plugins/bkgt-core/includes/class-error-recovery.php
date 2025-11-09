<?php
/**
 * BKGT Error Recovery Handler - Graceful Error Handling
 * 
 * Provides graceful error handling, user-friendly feedback, and recovery mechanisms.
 * Implements graceful degradation patterns and error state management.
 * 
 * @package BKGT
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * BKGT Error Recovery Handler Class
 * 
 * Handles exceptions gracefully and provides recovery mechanisms
 */
class BKGT_Error_Recovery {
    
    /**
     * Error registry (in-memory cache)
     * 
     * @var array
     */
    private static $error_registry = array();
    
    /**
     * Circuit breaker states (tracks failing operations)
     * 
     * @var array
     */
    private static $circuit_breakers = array();
    
    /**
     * Retry attempts for operations
     * 
     * @var array
     */
    private static $retry_counts = array();
    
    /**
     * Initialize error recovery system
     */
    public static function init() {
        // Set custom exception handler
        set_exception_handler( array( __CLASS__, 'handle_exception' ) );
        
        // Set error handler for non-exception errors
        set_error_handler( array( __CLASS__, 'handle_error' ), E_ALL );
        
        // Log shutdown errors
        register_shutdown_function( array( __CLASS__, 'handle_shutdown' ) );
        
        // Add admin hooks
        if ( is_admin() ) {
            add_action( 'admin_notices', array( __CLASS__, 'display_admin_errors' ) );
            add_action( 'wp_before_admin_bar_render', array( __CLASS__, 'add_admin_bar_errors' ) );
        } else {
            add_action( 'wp_footer', array( __CLASS__, 'display_frontend_errors' ) );
        }
    }
    
    /**
     * Handle exceptions
     * 
     * @param Exception $exception The exception that was thrown
     */
    public static function handle_exception( $exception ) {
        $class = get_class( $exception );
        
        // Log the exception
        if ( $exception instanceof BKGT_Exception ) {
            $level = $exception->get_log_level();
            BKGT_Logger::log( $level, $exception->getMessage(), $exception->get_context() );
        } else {
            BKGT_Logger::error( $exception->getMessage(), array(
                'exception_class' => $class,
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
            ) );
        }
        
        // Store error for display
        self::store_error( $exception );
        
        // Call appropriate handler based on exception type
        if ( $exception instanceof BKGT_Database_Exception ) {
            self::handle_database_error( $exception );
        } elseif ( $exception instanceof BKGT_Validation_Exception ) {
            self::handle_validation_error( $exception );
        } elseif ( $exception instanceof BKGT_Permission_Exception ) {
            self::handle_permission_error( $exception );
        } elseif ( $exception instanceof BKGT_Resource_Not_Found_Exception ) {
            self::handle_not_found_error( $exception );
        } elseif ( $exception instanceof BKGT_API_Exception ) {
            self::handle_api_error( $exception );
        } elseif ( $exception instanceof BKGT_Rate_Limit_Exception ) {
            self::handle_rate_limit_error( $exception );
        } else {
            self::handle_generic_error( $exception );
        }
    }
    
    /**
     * Handle non-exception errors
     * 
     * @param int    $errno Error level
     * @param string $errstr Error message
     * @param string $errfile Error file
     * @param int    $errline Error line
     * 
     * @return bool Whether error was handled
     */
    public static function handle_error( $errno, $errstr, $errfile, $errline ) {
        // Don't log suppressed errors (@)
        if ( error_reporting() === 0 ) {
            return false;
        }
        
        // Convert to exception for consistent handling
        $exception = new ErrorException( $errstr, 0, $errno, $errfile, $errline );
        self::handle_exception( $exception );
        
        return true;
    }
    
    /**
     * Handle fatal errors at shutdown
     */
    public static function handle_shutdown() {
        $error = error_get_last();
        
        if ( $error && in_array( $error['type'], array( E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR ), true ) ) {
            BKGT_Logger::critical(
                'Fatal PHP Error: ' . $error['message'],
                array(
                    'file' => $error['file'],
                    'line' => $error['line'],
                    'type' => $error['type'],
                )
            );
        }
    }
    
    /**
     * Store error for display
     *
     * @param Exception $exception
     */
    private static function store_error( $exception ) {
        $error_id = wp_generate_uuid4();

        // Store only serializable data, not the entire exception object
        self::$error_registry[ $error_id ] = array(
            'message' => $exception->getMessage(),
            'code' => $exception->getCode(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
            'class' => get_class( $exception ),
            'time' => current_time( 'timestamp' ),
            'url' => $_SERVER['REQUEST_URI'] ?? '',
            'user_id' => get_current_user_id(),
        );

        // Store in transient for later retrieval
        set_transient( 'bkgt_error_' . $error_id, self::$error_registry[ $error_id ], HOUR_IN_SECONDS );
    }    /**
     * Handle database errors
     * 
     * @param BKGT_Database_Exception $e
     */
    private static function handle_database_error( $e ) {
        BKGT_Logger::error( 'Database error: ' . $e->getMessage(), $e->get_context() );
        
        // Activate circuit breaker for database operations
        self::trigger_circuit_breaker( 'database_operations' );
        
        // Display user-friendly message
        if ( ! wp_doing_ajax() ) {
            self::add_error_notice( __( 'Es tut mir leid, die Datenbank antwortet nicht. Bitte versuchen Sie es später.', 'bkgt-core' ), 'error' );
        }
    }
    
    /**
     * Handle validation errors
     * 
     * @param BKGT_Validation_Exception $e
     */
    private static function handle_validation_error( $e ) {
        BKGT_Logger::warning( 'Validation error: ' . $e->getMessage() );
        
        if ( ! wp_doing_ajax() ) {
            self::add_error_notice( __( 'Bitte überprüfen Sie Ihre Eingaben', 'bkgt-core' ), 'warning' );
        }
    }
    
    /**
     * Handle permission errors
     * 
     * @param BKGT_Permission_Exception $e
     */
    private static function handle_permission_error( $e ) {
        BKGT_Logger::warning( 'Permission denied: ' . $e->getMessage(), array(
            'user_id' => get_current_user_id(),
            'capability' => $e->get_required_capability(),
        ) );
        
        if ( ! wp_doing_ajax() ) {
            self::add_error_notice( __( 'Sie haben keine Berechtigung für diese Aktion', 'bkgt-core' ), 'error' );
        }
    }
    
    /**
     * Handle not found errors
     * 
     * @param BKGT_Resource_Not_Found_Exception $e
     */
    private static function handle_not_found_error( $e ) {
        BKGT_Logger::warning( 'Resource not found: ' . $e->get_resource_type() . ' - ' . $e->get_resource_id() );
        
        if ( ! wp_doing_ajax() ) {
            self::add_error_notice( sprintf( __( '%s nicht gefunden', 'bkgt-core' ), $e->get_resource_type() ), 'warning' );
        }
    }
    
    /**
     * Handle API errors
     * 
     * @param BKGT_API_Exception $e
     */
    private static function handle_api_error( $e ) {
        BKGT_Logger::error( 'API error: ' . $e->getMessage(), array(
            'endpoint' => $e->get_endpoint(),
            'http_status' => $e->get_http_status(),
        ) );
        
        // Activate circuit breaker for this API
        self::trigger_circuit_breaker( 'api_' . $e->get_endpoint() );
        
        if ( ! wp_doing_ajax() ) {
            self::add_error_notice( __( 'Ein externer Service antwortet nicht. Bitte versuchen Sie es später.', 'bkgt-core' ), 'error' );
        }
    }
    
    /**
     * Handle rate limit errors
     * 
     * @param BKGT_Rate_Limit_Exception $e
     */
    private static function handle_rate_limit_error( $e ) {
        BKGT_Logger::warning( 'Rate limit exceeded: ' . $e->getMessage(), array(
            'user_id' => get_current_user_id(),
            'reset_time' => $e->get_reset_time(),
        ) );
        
        if ( ! wp_doing_ajax() ) {
            self::add_error_notice( __( 'Zu viele Versuche. Bitte warten Sie vor dem nächsten Versuch.', 'bkgt-core' ), 'warning' );
        }
    }
    
    /**
     * Handle generic errors
     * 
     * @param Exception $e
     */
    private static function handle_generic_error( $e ) {
        BKGT_Logger::error( 'Unexpected error: ' . $e->getMessage(), array(
            'file' => $e->getFile(),
            'line' => $e->getLine(),
        ) );
        
        if ( ! wp_doing_ajax() ) {
            self::add_error_notice( __( 'Es ist ein unerwarteter Fehler aufgetreten', 'bkgt-core' ), 'error' );
        }
    }
    
    /**
     * Trigger circuit breaker for an operation
     * 
     * @param string $operation_name Name of the operation
     * @param int    $duration Duration in seconds (default: 5 minutes)
     */
    public static function trigger_circuit_breaker( $operation_name, $duration = 300 ) {
        self::$circuit_breakers[ $operation_name ] = array(
            'triggered' => true,
            'time' => current_time( 'timestamp' ),
            'duration' => $duration,
        );
        
        set_transient( 'bkgt_circuit_' . $operation_name, true, $duration );
    }
    
    /**
     * Check if circuit breaker is active
     * 
     * @param string $operation_name
     * 
     * @return bool
     */
    public static function is_circuit_breaker_active( $operation_name ) {
        if ( isset( self::$circuit_breakers[ $operation_name ] ) && self::$circuit_breakers[ $operation_name ]['triggered'] ) {
            $elapsed = current_time( 'timestamp' ) - self::$circuit_breakers[ $operation_name ]['time'];
            if ( $elapsed < self::$circuit_breakers[ $operation_name ]['duration'] ) {
                return true;
            } else {
                self::$circuit_breakers[ $operation_name ]['triggered'] = false;
            }
        }
        
        return get_transient( 'bkgt_circuit_' . $operation_name ) !== false;
    }
    
    /**
     * Reset circuit breaker
     * 
     * @param string $operation_name
     */
    public static function reset_circuit_breaker( $operation_name ) {
        delete_transient( 'bkgt_circuit_' . $operation_name );
        if ( isset( self::$circuit_breakers[ $operation_name ] ) ) {
            self::$circuit_breakers[ $operation_name ]['triggered'] = false;
        }
    }
    
    /**
     * Retry an operation with exponential backoff
     * 
     * @param callable $callback Function to retry
     * @param int      $max_attempts Maximum retry attempts (default: 3)
     * @param int      $base_delay Base delay in milliseconds (default: 100)
     * 
     * @return mixed Result of callback or null if all attempts failed
     */
    public static function retry_with_backoff( $callback, $max_attempts = 3, $base_delay = 100 ) {
        $attempt = 0;
        
        while ( $attempt < $max_attempts ) {
            try {
                return call_user_func( $callback );
            } catch ( Exception $e ) {
                $attempt++;
                
                if ( $attempt >= $max_attempts ) {
                    throw $e;
                }
                
                // Exponential backoff: 100ms, 200ms, 400ms, etc.
                $delay = $base_delay * pow( 2, $attempt - 1 );
                usleep( $delay * 1000 );
                
                BKGT_Logger::debug( "Retrying operation, attempt {$attempt}/{$max_attempts}, delay: {$delay}ms" );
            }
        }
        
        return null;
    }
    
    /**
     * Add error notice to display
     * 
     * @param string $message Error message
     * @param string $type Notice type (error, warning, success, info)
     */
    public static function add_error_notice( $message, $type = 'error' ) {
        if ( is_admin() ) {
            add_action( 'admin_notices', function() use ( $message, $type ) {
                echo '<div class="notice notice-' . esc_attr( $type ) . ' is-dismissible">';
                echo '<p><strong>' . esc_html__( 'BKGT Fehler:', 'bkgt-core' ) . '</strong> ' . esc_html( $message ) . '</p>';
                echo '</div>';
            } );
        } else {
            // Store for frontend display
            $notices = get_transient( 'bkgt_frontend_notices' ) ?: array();
            $notices[] = array(
                'message' => $message,
                'type' => $type,
                'time' => current_time( 'timestamp' ),
            );
            set_transient( 'bkgt_frontend_notices', $notices, HOUR_IN_SECONDS );
        }
    }
    
    /**
     * Display admin errors
     */
    public static function display_admin_errors() {
        // Get recent errors from registry
        if ( empty( self::$error_registry ) ) {
            return;
        }
        
        $recent_errors = array_slice( self::$error_registry, -3 ); // Show last 3
        
        foreach ( $recent_errors as $error_data ) {
            $exception = $error_data['exception'];
            $message = $exception->getMessage();
            
            if ( $exception instanceof BKGT_Exception ) {
                $suggestions = $exception->get_recovery_suggestions();
                
                echo '<div class="notice notice-error is-dismissible">';
                echo '<p><strong>' . esc_html__( 'BKGT Fehler:', 'bkgt-core' ) . '</strong> ' . esc_html( $message ) . '</p>';
                
                if ( ! empty( $suggestions ) ) {
                    echo '<ul style="margin-top: 10px; margin-left: 20px;">';
                    foreach ( $suggestions as $suggestion ) {
                        echo '<li>' . esc_html( $suggestion ) . '</li>';
                    }
                    echo '</ul>';
                }
                
                echo '</div>';
            }
        }
    }
    
    /**
     * Add error info to admin bar
     */
    public static function add_admin_bar_errors() {
        global $wp_admin_bar;
        
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }
        
        $error_count = count( self::$error_registry );
        
        if ( $error_count > 0 ) {
            $wp_admin_bar->add_menu( array(
                'id' => 'bkgt-errors',
                'title' => sprintf( esc_html__( 'BKGT Fehler (%d)', 'bkgt-core' ), $error_count ),
                'href' => admin_url( 'admin.php?page=bkgt-error-log' ),
                'meta' => array(
                    'class' => 'bkgt-error-indicator',
                ),
            ) );
        }
    }
    
    /**
     * Display frontend errors
     */
    public static function display_frontend_errors() {
        $notices = get_transient( 'bkgt_frontend_notices' );
        
        if ( empty( $notices ) ) {
            return;
        }
        
        echo '<div class="bkgt-error-notices">';
        
        foreach ( $notices as $notice ) {
            $class = 'bkgt-notice bkgt-notice-' . esc_attr( $notice['type'] );
            echo '<div class="' . esc_attr( $class ) . '">';
            echo '<p>' . esc_html( $notice['message'] ) . '</p>';
            echo '</div>';
        }
        
        echo '</div>';
        
        // Clear notices
        delete_transient( 'bkgt_frontend_notices' );
    }
    
    /**
     * Get recovery suggestions for an exception
     * 
     * @param Exception $exception
     * 
     * @return array
     */
    public static function get_recovery_suggestions( $exception ) {
        if ( $exception instanceof BKGT_Exception ) {
            return $exception->get_recovery_suggestions();
        }
        
        return array( __( 'Kontaktieren Sie den Administrator', 'bkgt-core' ) );
    }
    
    /**
     * Get error statistics (for admin dashboard)
     * 
     * @return array
     */
    public static function get_error_statistics() {
        $logs = BKGT_Logger::get_recent_logs( 100 );
        
        $stats = array(
            'total_errors' => 0,
            'critical' => 0,
            'errors' => 0,
            'warnings' => 0,
            'by_type' => array(),
        );
        
        foreach ( $logs as $log ) {
            $stats['total_errors']++;
            
            if ( strpos( $log, '[critical]' ) !== false ) {
                $stats['critical']++;
            } elseif ( strpos( $log, '[error]' ) !== false ) {
                $stats['errors']++;
            } elseif ( strpos( $log, '[warning]' ) !== false ) {
                $stats['warnings']++;
            }
        }
        
        return $stats;
    }
}

// Initialize error recovery on WordPress load
add_action( 'wp_loaded', array( 'BKGT_Error_Recovery', 'init' ), 5 );
