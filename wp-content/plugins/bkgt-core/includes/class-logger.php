<?php
/**
 * BKGT Logger - Unified Error Handling and Logging System
 * 
 * Provides consistent error logging, user feedback, and debugging across all BKGT plugins.
 * All errors are logged with context and severity levels.
 * 
 * @package BKGT
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * BKGT_Logger Class
 * 
 * Centralizes all error handling and logging functionality
 */
class BKGT_Logger {
    
    /**
     * Log severity levels
     */
    const CRITICAL = 'critical';  // System down, immediate action needed
    const ERROR    = 'error';     // Functionality broken
    const WARNING  = 'warning';   // Degraded functionality
    const INFO     = 'info';      // System operations
    const DEBUG    = 'debug';     // Development details
    
    /**
     * Log file path
     */
    private static $log_file = null;
    
    /**
     * Initialize logger
     */
    public static function init() {
        self::$log_file = WP_CONTENT_DIR . '/bkgt-logs.log';
        
        // Create log file if it doesn't exist
        if ( ! file_exists( self::$log_file ) ) {
            touch( self::$log_file );
        }
        
        // Hook into WordPress errors
        add_action( 'wp_footer', array( __CLASS__, 'log_php_errors' ), 999 );
    }
    
    /**
     * Log a message with context
     * 
     * @param string $level   Log level (critical, error, warning, info, debug)
     * @param string $message Log message
     * @param array  $context Additional context (user, request, data, etc.)
     * 
     * @return bool Whether log was written successfully
     */
    public static function log( $level = self::INFO, $message = '', $context = array() ) {
        if ( empty( $message ) ) {
            return false;
        }
        
        // Prepare log entry
        $entry = self::prepare_log_entry( $level, $message, $context );
        
        // Write to file
        $written = error_log( $entry, 3, self::$log_file );
        
        // Also send to WordPress debug if enabled
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            error_log( "BKGT [{$level}]: {$message}", 0 );
        }
        
        return $written;
    }
    
    /**
     * Prepare log entry with formatting
     * 
     * @param string $level   Log level
     * @param string $message Log message
     * @param array  $context Context data
     * 
     * @return string Formatted log entry
     */
    private static function prepare_log_entry( $level, $message, $context ) {
        $timestamp = current_time( 'Y-m-d H:i:s' );
        
        // Get current user
        $user = wp_get_current_user();
        $user_str = $user->ID ? $user->user_login . " (ID: {$user->ID})" : 'Anonymous';
        
        // Get request URL
        $url = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : 'CLI';
        
        // Build context string
        $context_str = '';
        if ( ! empty( $context ) ) {
            $context_str = ' | Context: ' . wp_json_encode( $context );
        }
        
        // Build stack trace for errors
        $trace_str = '';
        if ( in_array( $level, array( self::CRITICAL, self::ERROR ), true ) ) {
            $trace = debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS, 5 );
            // Skip the logger itself in the trace
            array_shift( $trace );
            
            $trace_lines = array();
            foreach ( $trace as $frame ) {
                if ( isset( $frame['file'], $frame['line'], $frame['function'] ) ) {
                    $file = str_replace( ABSPATH, '', $frame['file'] );
                    $trace_lines[] = "{$file}:{$frame['line']} in {$frame['function']}()";
                }
            }
            
            if ( ! empty( $trace_lines ) ) {
                $trace_str = ' | Stack: ' . implode( ' < ', $trace_lines );
            }
        }
        
        return "[{$timestamp}] [{$level}] {$message} | User: {$user_str} | URL: {$url}{$context_str}{$trace_str}\n";
    }
    
    /**
     * Log critical error (system down)
     * 
     * @param string $message Error message
     * @param array  $context Context data
     * 
     * @return bool Success
     */
    public static function critical( $message, $context = array() ) {
        $result = self::log( self::CRITICAL, $message, $context );
        
        // Send email alert for critical errors
        self::send_alert( 'CRITICAL', $message, $context );
        
        return $result;
    }
    
    /**
     * Log error (functionality broken)
     * 
     * @param string $message Error message
     * @param array  $context Context data
     * 
     * @return bool Success
     */
    public static function error( $message, $context = array() ) {
        return self::log( self::ERROR, $message, $context );
    }
    
    /**
     * Log warning (degraded functionality)
     * 
     * @param string $message Warning message
     * @param array  $context Context data
     * 
     * @return bool Success
     */
    public static function warning( $message, $context = array() ) {
        return self::log( self::WARNING, $message, $context );
    }
    
    /**
     * Log info message
     * 
     * @param string $message Info message
     * @param array  $context Context data
     * 
     * @return bool Success
     */
    public static function info( $message, $context = array() ) {
        return self::log( self::INFO, $message, $context );
    }
    
    /**
     * Log debug message (only in development)
     * 
     * @param string $message Debug message
     * @param array  $context Context data
     * 
     * @return bool Success
     */
    public static function debug( $message, $context = array() ) {
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            return self::log( self::DEBUG, $message, $context );
        }
        return false;
    }
    
    /**
     * Send critical error alert to admin
     * 
     * @param string $level    Error level
     * @param string $message  Error message
     * @param array  $context  Context data
     */
    private static function send_alert( $level, $message, $context ) {
        // Only send alerts for critical errors
        if ( $level !== 'CRITICAL' ) {
            return;
        }
        
        // Get admin email
        $admin_email = get_option( 'admin_email' );
        if ( empty( $admin_email ) ) {
            return;
        }
        
        // Build email
        $subject = "[BKGT Critical Error] {$message}";
        $email_body = "A critical error has occurred on your BKGT site.\n\n";
        $email_body .= "Error: {$message}\n";
        $email_body .= "Time: " . current_time( 'Y-m-d H:i:s' ) . "\n";
        $email_body .= "URL: " . ( isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : 'N/A' ) . "\n";
        $email_body .= "User: " . wp_get_current_user()->user_login . "\n";
        
        if ( ! empty( $context ) ) {
            $email_body .= "\nContext:\n" . wp_json_encode( $context, JSON_PRETTY_PRINT ) . "\n";
        }
        
        $email_body .= "\nCheck the debug log at: " . site_url() . "/wp-content/bkgt-logs.log\n";
        
        wp_mail( $admin_email, $subject, $email_body );
    }
    
    /**
     * Get recent log entries
     * 
     * @param int $limit Number of entries to retrieve
     * 
     * @return array Log entries
     */
    public static function get_recent_logs( $limit = 50 ) {
        if ( ! file_exists( self::$log_file ) ) {
            return array();
        }
        
        $lines = file( self::$log_file, FILE_IGNORE_NEW_LINES );
        
        if ( empty( $lines ) ) {
            return array();
        }
        
        // Return last N lines (most recent first)
        return array_reverse( array_slice( $lines, -$limit ) );
    }
    
    /**
     * Clear old log entries (older than 30 days)
     */
    public static function cleanup_old_logs() {
        if ( ! file_exists( self::$log_file ) ) {
            return;
        }
        
        $lines = file( self::$log_file, FILE_IGNORE_NEW_LINES );
        $cutoff_time = strtotime( '-30 days' );
        $new_lines = array();
        
        foreach ( $lines as $line ) {
            // Extract timestamp from log entry
            if ( preg_match( '/\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\]/', $line, $matches ) ) {
                $entry_time = strtotime( $matches[1] );
                
                if ( $entry_time >= $cutoff_time ) {
                    $new_lines[] = $line;
                }
            }
        }
        
        // Write filtered logs back
        file_put_contents( self::$log_file, implode( "\n", $new_lines ) . "\n", LOCK_EX );
    }
    
    /**
     * Hook to log PHP errors
     */
    public static function log_php_errors() {
        if ( function_exists( 'error_get_last' ) ) {
            $error = error_get_last();
            
            if ( $error && $error['type'] !== E_DEPRECATED ) {
                self::error(
                    $error['message'],
                    array(
                        'file' => $error['file'],
                        'line' => $error['line'],
                        'type' => $error['type'],
                    )
                );
            }
        }
    }
}

// Initialize logger on WordPress load
add_action( 'wp_loaded', array( 'BKGT_Logger', 'init' ) );

// Cleanup old logs daily
if ( ! wp_next_scheduled( 'bkgt_cleanup_logs' ) ) {
    wp_schedule_event( time(), 'daily', 'bkgt_cleanup_logs' );
}
add_action( 'bkgt_cleanup_logs', array( 'BKGT_Logger', 'cleanup_old_logs' ) );
