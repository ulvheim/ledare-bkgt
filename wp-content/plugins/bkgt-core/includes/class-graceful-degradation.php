<?php
/**
 * BKGT Graceful Degradation Utilities
 * 
 * Provides patterns for graceful degradation when systems fail.
 * Implements fallback mechanisms, cache strategies, and partial data handling.
 * 
 * @package BKGT
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * BKGT Graceful Degradation Class
 * 
 * Provides utilities for handling failures gracefully
 */
class BKGT_Graceful_Degradation {
    
    /**
     * Get data with fallback cache
     * 
     * Tries to retrieve fresh data, but falls back to cache if operation fails
     * 
     * @param callable $data_callback Function to get fresh data
     * @param string   $cache_key Cache key for storing fallback data
     * @param int      $cache_duration Cache duration in seconds
     * @param mixed    $default_fallback Default value if no cache exists
     * 
     * @return mixed Data or fallback
     */
    public static function get_with_cache_fallback( $data_callback, $cache_key, $cache_duration = HOUR_IN_SECONDS, $default_fallback = null ) {
        try {
            // Try to get fresh data
            $data = call_user_func( $data_callback );
            
            // Cache successful result
            set_transient( $cache_key, $data, $cache_duration );
            
            return $data;
            
        } catch ( Exception $e ) {
            // Log the failure
            BKGT_Logger::warning( "Failed to get fresh data, falling back to cache: " . $e->getMessage(), array(
                'cache_key' => $cache_key,
            ) );
            
            // Try cached version
            $cached = get_transient( $cache_key );
            
            if ( $cached !== false ) {
                return $cached;
            }
            
            // Return default fallback
            return $default_fallback;
        }
    }
    
    /**
     * Get data with partial result fallback
     * 
     * Attempts to get complete data, but returns partial results if operation fails
     * 
     * @param callable $complete_callback Function to get complete data
     * @param callable $partial_callback Function to get partial data
     * @param int      $max_items Maximum items to return (for partial data)
     * 
     * @return array Complete or partial data
     */
    public static function get_with_partial_fallback( $complete_callback, $partial_callback, $max_items = 10 ) {
        try {
            // Try to get complete data
            $data = call_user_func( $complete_callback );
            
            return array(
                'status' => 'complete',
                'data' => $data,
                'count' => count( $data ),
            );
            
        } catch ( Exception $e ) {
            // Log the failure
            BKGT_Logger::warning( "Failed to get complete data, using partial fallback: " . $e->getMessage() );
            
            try {
                // Try partial data
                $partial_data = call_user_func( $partial_callback, $max_items );
                
                return array(
                    'status' => 'partial',
                    'data' => $partial_data,
                    'count' => count( $partial_data ),
                    'message' => __( 'Zeige begrenzte Daten. Vollständige Daten verfügbar nicht.', 'bkgt-core' ),
                );
                
            } catch ( Exception $partial_e ) {
                // Both operations failed
                BKGT_Logger::error( "Both complete and partial data retrieval failed: " . $partial_e->getMessage() );
                
                return array(
                    'status' => 'empty',
                    'data' => array(),
                    'count' => 0,
                    'message' => __( 'Daten nicht verfügbar. Bitte versuchen Sie später erneut.', 'bkgt-core' ),
                );
            }
        }
    }
    
    /**
     * Execute with fallback handler
     * 
     * Tries to execute a function, with custom fallback on failure
     * 
     * @param callable $primary_callback Primary function to execute
     * @param callable $fallback_callback Fallback function to execute on failure
     * @param array    $context Error context
     * 
     * @return mixed Result of primary or fallback function
     */
    public static function execute_with_fallback( $primary_callback, $fallback_callback, $context = array() ) {
        try {
            return call_user_func( $primary_callback );
            
        } catch ( Exception $e ) {
            BKGT_Logger::warning( "Primary operation failed, executing fallback: " . $e->getMessage(), $context );
            
            try {
                return call_user_func( $fallback_callback );
            } catch ( Exception $fallback_e ) {
                BKGT_Logger::error( "Fallback operation also failed: " . $fallback_e->getMessage() );
                throw $fallback_e;
            }
        }
    }
    
    /**
     * Handle operation with retry and fallback
     * 
     * @param callable $operation Operation to retry
     * @param callable $fallback Fallback if retries fail
     * @param int      $max_retries Maximum retry attempts
     * @param int      $base_delay Base delay in milliseconds
     * 
     * @return mixed
     */
    public static function retry_with_fallback( $operation, $fallback, $max_retries = 3, $base_delay = 100 ) {
        $last_exception = null;
        
        for ( $attempt = 0; $attempt < $max_retries; $attempt++ ) {
            try {
                return call_user_func( $operation );
                
            } catch ( Exception $e ) {
                $last_exception = $e;
                
                if ( $attempt < $max_retries - 1 ) {
                    $delay = $base_delay * pow( 2, $attempt );
                    usleep( $delay * 1000 );
                }
            }
        }
        
        // All retries failed, use fallback
        BKGT_Logger::warning( "Operation failed after {$max_retries} retries, using fallback" );
        
        return call_user_func( $fallback );
    }
    
    /**
     * Batch operation with partial success handling
     * 
     * Processes items in batches, returning successful items even if some fail
     * 
     * @param array    $items Items to process
     * @param callable $processor Function to process each item
     * @param int      $batch_size Items per batch
     * 
     * @return array Results with success/failure tracking
     */
    public static function batch_with_partial_success( $items, $processor, $batch_size = 50 ) {
        $results = array(
            'successful' => array(),
            'failed' => array(),
            'total' => count( $items ),
        );
        
        $batches = array_chunk( $items, $batch_size );
        
        foreach ( $batches as $batch ) {
            foreach ( $batch as $item ) {
                try {
                    $result = call_user_func( $processor, $item );
                    $results['successful'][] = $result;
                } catch ( Exception $e ) {
                    $results['failed'][] = array(
                        'item' => $item,
                        'error' => $e->getMessage(),
                    );
                    
                    BKGT_Logger::warning( "Batch item processing failed: " . $e->getMessage() );
                }
            }
        }
        
        return $results;
    }
    
    /**
     * Get data with timeout fallback
     * 
     * Executes operation with timeout, falls back if it takes too long
     * 
     * @param callable $operation Operation to execute
     * @param callable $fallback Fallback if timeout
     * @param int      $timeout_seconds Timeout in seconds
     * 
     * @return mixed
     */
    public static function execute_with_timeout( $operation, $fallback, $timeout_seconds = 5 ) {
        // Note: PHP's set_time_limit doesn't work well for individual operations
        // This is a simplified version using a flag
        
        $start_time = microtime( true );
        $timeout_ms = $timeout_seconds * 1000;
        
        try {
            $result = call_user_func( $operation );
            
            $elapsed_time = ( microtime( true ) - $start_time ) * 1000;
            
            if ( $elapsed_time > $timeout_ms ) {
                BKGT_Logger::warning( "Operation completed but took too long: {$elapsed_time}ms vs {$timeout_ms}ms" );
                return call_user_func( $fallback );
            }
            
            return $result;
            
        } catch ( Exception $e ) {
            BKGT_Logger::warning( "Operation timed out or failed, using fallback: " . $e->getMessage() );
            return call_user_func( $fallback );
        }
    }
    
    /**
     * Get data with empty result fallback
     * 
     * Ensures a result is always returned, even if empty
     * 
     * @param callable $operation Operation to execute
     * @param mixed    $empty_result Default empty result
     * 
     * @return mixed
     */
    public static function ensure_result( $operation, $empty_result = array() ) {
        try {
            $result = call_user_func( $operation );
            
            // Handle empty results
            if ( empty( $result ) ) {
                BKGT_Logger::debug( "Operation returned empty result" );
                return $empty_result;
            }
            
            return $result;
            
        } catch ( Exception $e ) {
            BKGT_Logger::warning( "Operation failed: " . $e->getMessage() );
            return $empty_result;
        }
    }
    
    /**
     * Chain operations with fallback
     * 
     * Tries multiple operations in sequence until one succeeds
     * 
     * @param array $operations Array of callbacks to try in order
     * @param mixed $default Default value if all fail
     * 
     * @return mixed
     */
    public static function chain_operations( $operations, $default = null ) {
        foreach ( $operations as $index => $operation ) {
            try {
                $result = call_user_func( $operation );
                
                BKGT_Logger::debug( "Operation chain succeeded at attempt " . ( $index + 1 ) );
                return $result;
                
            } catch ( Exception $e ) {
                BKGT_Logger::warning( "Operation in chain failed (attempt " . ( $index + 1 ) . "): " . $e->getMessage() );
                
                // Continue to next operation
                continue;
            }
        }
        
        // All operations failed
        BKGT_Logger::error( "All operations in chain failed" );
        return $default;
    }
    
    /**
     * Safe database query with fallback
     * 
     * @param string   $query Database query
     * @param string   $cache_key Optional cache key for fallback
     * @param callable $fallback Optional fallback function
     * 
     * @return mixed Query results or fallback
     */
    public static function safe_query( $query, $cache_key = '', $fallback = null ) {
        global $wpdb;
        
        try {
            $result = $wpdb->get_results( $query );
            
            // Cache successful result if cache key provided
            if ( ! empty( $cache_key ) ) {
                set_transient( $cache_key, $result, HOUR_IN_SECONDS );
            }
            
            return $result;
            
        } catch ( Exception $e ) {
            BKGT_Logger::error( "Database query failed: " . $e->getMessage() );
            
            // Try cache
            if ( ! empty( $cache_key ) ) {
                $cached = get_transient( $cache_key );
                if ( $cached !== false ) {
                    BKGT_Logger::info( "Using cached database results" );
                    return $cached;
                }
            }
            
            // Use fallback if provided
            if ( is_callable( $fallback ) ) {
                return call_user_func( $fallback );
            }
            
            return array();
        }
    }
    
    /**
     * Safe API call with fallback
     * 
     * @param string   $url API endpoint URL
     * @param array    $args Request arguments
     * @param callable $fallback Fallback function
     * @param string   $cache_key Optional cache key
     * 
     * @return mixed API response or fallback
     */
    public static function safe_api_call( $url, $args = array(), $fallback = null, $cache_key = '' ) {
        try {
            $response = wp_remote_get( $url, wp_parse_args( $args, array(
                'timeout' => 5,
            ) ) );
            
            if ( is_wp_error( $response ) ) {
                throw new BKGT_API_Exception( $response->get_error_message(), BKGT_API_Exception::CONNECTION_FAILED, $url );
            }
            
            $body = wp_remote_retrieve_body( $response );
            $data = json_decode( $body, true );
            
            // Cache successful result
            if ( ! empty( $cache_key ) ) {
                set_transient( $cache_key, $data, 4 * HOUR_IN_SECONDS );
            }
            
            return $data;
            
        } catch ( Exception $e ) {
            BKGT_Logger::warning( "API call failed: " . $e->getMessage(), array(
                'url' => $url,
            ) );
            
            // Try cache
            if ( ! empty( $cache_key ) ) {
                $cached = get_transient( $cache_key );
                if ( $cached !== false ) {
                    BKGT_Logger::info( "Using cached API results" );
                    return $cached;
                }
            }
            
            // Use fallback
            if ( is_callable( $fallback ) ) {
                return call_user_func( $fallback );
            }
            
            return null;
        }
    }
    
    /**
     * Render fallback UI for empty results
     * 
     * @param string $message User-friendly message
     * @param array  $actions Optional action buttons
     * 
     * @return string HTML
     */
    public static function render_empty_state( $message, $actions = array() ) {
        $html = '<div class="bkgt-empty-state" style="text-align: center; padding: 30px; color: #666;">';
        $html .= '<p style="font-size: 48px; opacity: 0.3;">⚠️</p>';
        $html .= '<p>' . esc_html( $message ) . '</p>';
        
        if ( ! empty( $actions ) ) {
            $html .= '<div style="margin-top: 20px;">';
            foreach ( $actions as $label => $url ) {
                $html .= '<a href="' . esc_url( $url ) . '" class="button" style="margin: 5px;">' . esc_html( $label ) . '</a>';
            }
            $html .= '</div>';
        }
        
        $html .= '</div>';
        
        return $html;
    }
}
