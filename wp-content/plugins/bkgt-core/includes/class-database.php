<?php
/**
 * BKGT Database Service - Unified Database Operations
 * 
 * Provides consistent database query patterns, error handling, and performance optimization.
 * All database operations should go through this service for consistency and safety.
 * 
 * @package BKGT
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * BKGT_Database Class
 * 
 * Centralizes all database operations with unified patterns
 */
class BKGT_Database {
    
    /**
     * Query cache
     */
    private static $cache = array();
    private static $cache_enabled = true;
    
    /**
     * Initialize database service
     */
    public static function init() {
        // Enable query caching by default (can be disabled for specific queries)
        self::$cache_enabled = ! ( defined( 'WP_DEBUG' ) && WP_DEBUG );
    }
    
    /**
     * Get posts by query arguments
     * 
     * @param array $args WordPress WP_Query arguments
     * @param bool  $use_cache Whether to use cache
     * 
     * @return WP_Query|WP_Error Query object or error
     */
    public static function get_posts( $args = array(), $use_cache = true ) {
        try {
            // Generate cache key
            $cache_key = md5( wp_json_encode( $args ) );
            
            // Check cache
            if ( $use_cache && self::$cache_enabled && isset( self::$cache[ $cache_key ] ) ) {
                BKGT_Logger::debug( "Query cache hit", array( 'cache_key' => $cache_key ) );
                return self::$cache[ $cache_key ];
            }
            
            // Execute query
            BKGT_Logger::debug( "Executing WP_Query", array( 
                'post_type' => isset( $args['post_type'] ) ? $args['post_type'] : 'post',
                'posts_per_page' => isset( $args['posts_per_page'] ) ? $args['posts_per_page'] : 10,
            ) );
            
            $query = new WP_Query( $args );
            
            // Cache result
            if ( $use_cache && self::$cache_enabled ) {
                self::$cache[ $cache_key ] = $query;
            }
            
            return $query;
            
        } catch ( Exception $e ) {
            BKGT_Logger::error( "WP_Query failed: " . $e->getMessage(), array(
                'args' => $args,
                'error' => $e->getCode(),
            ) );
            return new WP_Error( 'query_failed', __( 'Databasfråga misslyckades', 'bkgt' ) );
        }
    }
    
    /**
     * Get a single post
     * 
     * @param int $post_id Post ID
     * 
     * @return WP_Post|WP_Error Post object or error
     */
    public static function get_post( $post_id ) {
        try {
            if ( empty( $post_id ) ) {
                return new WP_Error( 'invalid_post_id', __( 'Ogiltigt post-ID', 'bkgt' ) );
            }
            
            $post = get_post( $post_id );
            
            if ( ! $post ) {
                BKGT_Logger::warning( "Post not found", array( 'post_id' => $post_id ) );
                return new WP_Error( 'post_not_found', __( 'Inlägget hittades inte', 'bkgt' ) );
            }
            
            return $post;
            
        } catch ( Exception $e ) {
            BKGT_Logger::error( "Failed to get post: " . $e->getMessage(), array(
                'post_id' => $post_id,
            ) );
            return new WP_Error( 'get_post_failed', __( 'Kunde inte hämta inlägget', 'bkgt' ) );
        }
    }
    
    /**
     * Create a new post
     * 
     * @param array $post_data Post data (title, content, post_type, etc.)
     * 
     * @return int|WP_Error Post ID or error
     */
    public static function create_post( $post_data = array() ) {
        try {
            // Validate required fields
            $validation = self::validate_post_data( $post_data );
            if ( is_wp_error( $validation ) ) {
                return $validation;
            }
            
            // Sanitize data
            $post_data = self::sanitize_post_data( $post_data );
            
            // Insert post
            $post_id = wp_insert_post( $post_data, true );
            
            if ( is_wp_error( $post_id ) ) {
                BKGT_Logger::error( "Failed to create post: " . $post_id->get_error_message(), array(
                    'post_data' => $post_data,
                ) );
                return $post_id;
            }
            
            // Log creation
            BKGT_Logger::info( "Post created", array(
                'post_id' => $post_id,
                'post_type' => $post_data['post_type'],
                'user_id' => get_current_user_id(),
            ) );
            
            // Clear cache
            self::clear_cache();
            
            return $post_id;
            
        } catch ( Exception $e ) {
            BKGT_Logger::error( "Exception creating post: " . $e->getMessage() );
            return new WP_Error( 'create_post_failed', __( 'Kunde inte skapa inlägget', 'bkgt' ) );
        }
    }
    
    /**
     * Update a post
     * 
     * @param int   $post_id   Post ID
     * @param array $post_data Updated post data
     * 
     * @return int|WP_Error Post ID or error
     */
    public static function update_post( $post_id, $post_data = array() ) {
        try {
            // Verify post exists
            $post = self::get_post( $post_id );
            if ( is_wp_error( $post ) ) {
                return $post;
            }
            
            // Add post ID to update data
            $post_data['ID'] = $post_id;
            
            // Sanitize data
            $post_data = self::sanitize_post_data( $post_data );
            
            // Update post
            $updated = wp_update_post( $post_data, true );
            
            if ( is_wp_error( $updated ) ) {
                BKGT_Logger::error( "Failed to update post: " . $updated->get_error_message(), array(
                    'post_id' => $post_id,
                ) );
                return $updated;
            }
            
            // Log update
            BKGT_Logger::info( "Post updated", array(
                'post_id' => $post_id,
                'user_id' => get_current_user_id(),
            ) );
            
            // Clear cache
            self::clear_cache();
            
            return $post_id;
            
        } catch ( Exception $e ) {
            BKGT_Logger::error( "Exception updating post: " . $e->getMessage() );
            return new WP_Error( 'update_post_failed', __( 'Kunde inte uppdatera inlägget', 'bkgt' ) );
        }
    }
    
    /**
     * Delete a post
     * 
     * @param int  $post_id Post ID
     * @param bool $force   Force delete (bypass trash)
     * 
     * @return bool|WP_Error True on success, error on failure
     */
    public static function delete_post( $post_id, $force = false ) {
        try {
            // Verify post exists
            $post = self::get_post( $post_id );
            if ( is_wp_error( $post ) ) {
                return $post;
            }
            
            // Delete post
            $result = wp_delete_post( $post_id, $force );
            
            if ( ! $result ) {
                BKGT_Logger::warning( "Failed to delete post", array(
                    'post_id' => $post_id,
                    'force' => $force,
                ) );
                return new WP_Error( 'delete_post_failed', __( 'Kunde inte ta bort inlägget', 'bkgt' ) );
            }
            
            // Log deletion
            BKGT_Logger::info( "Post deleted", array(
                'post_id' => $post_id,
                'user_id' => get_current_user_id(),
            ) );
            
            // Clear cache
            self::clear_cache();
            
            return true;
            
        } catch ( Exception $e ) {
            BKGT_Logger::error( "Exception deleting post: " . $e->getMessage() );
            return new WP_Error( 'delete_post_failed', __( 'Kunde inte ta bort inlägget', 'bkgt' ) );
        }
    }
    
    /**
     * Get post metadata
     * 
     * @param int    $post_id Post ID
     * @param string $meta_key Meta key
     * @param bool   $single   Return single value or array
     * 
     * @return mixed Meta value
     */
    public static function get_post_meta( $post_id, $meta_key = '', $single = false ) {
        try {
            if ( empty( $post_id ) || empty( $meta_key ) ) {
                return $single ? null : array();
            }
            
            return get_post_meta( $post_id, $meta_key, $single );
            
        } catch ( Exception $e ) {
            BKGT_Logger::error( "Failed to get post meta: " . $e->getMessage(), array(
                'post_id' => $post_id,
                'meta_key' => $meta_key,
            ) );
            return $single ? null : array();
        }
    }
    
    /**
     * Update post metadata
     * 
     * @param int    $post_id  Post ID
     * @param string $meta_key Meta key
     * @param mixed  $meta_value Meta value
     * 
     * @return int|false Meta ID or false on failure
     */
    public static function update_post_meta( $post_id, $meta_key, $meta_value ) {
        try {
            if ( empty( $post_id ) || empty( $meta_key ) ) {
                return false;
            }
            
            // Sanitize meta value
            if ( is_array( $meta_value ) ) {
                $meta_value = array_map( 'sanitize_text_field', $meta_value );
            } else {
                $meta_value = sanitize_text_field( $meta_value );
            }
            
            $result = update_post_meta( $post_id, $meta_key, $meta_value );
            
            BKGT_Logger::debug( "Post meta updated", array(
                'post_id' => $post_id,
                'meta_key' => $meta_key,
            ) );
            
            return $result;
            
        } catch ( Exception $e ) {
            BKGT_Logger::error( "Failed to update post meta: " . $e->getMessage() );
            return false;
        }
    }
    
    /**
     * Delete post metadata
     * 
     * @param int    $post_id  Post ID
     * @param string $meta_key Meta key
     * 
     * @return bool True on success
     */
    public static function delete_post_meta( $post_id, $meta_key ) {
        try {
            if ( empty( $post_id ) || empty( $meta_key ) ) {
                return false;
            }
            
            $result = delete_post_meta( $post_id, $meta_key );
            
            BKGT_Logger::debug( "Post meta deleted", array(
                'post_id' => $post_id,
                'meta_key' => $meta_key,
            ) );
            
            return $result;
            
        } catch ( Exception $e ) {
            BKGT_Logger::error( "Failed to delete post meta: " . $e->getMessage() );
            return false;
        }
    }
    
    /**
     * Execute raw database query with prepared statement
     * 
     * @param string $query SQL query with %s, %d placeholders
     * @param array  $args  Query arguments to replace placeholders
     * 
     * @return array|null Query results or null on failure
     */
    public static function query( $query = '', $args = array() ) {
        global $wpdb;
        
        try {
            if ( empty( $query ) ) {
                return null;
            }
            
            // Prepare query
            if ( ! empty( $args ) ) {
                $prepared_query = $wpdb->prepare( $query, $args );
            } else {
                $prepared_query = $query;
            }
            
            // Log query (only in debug mode to avoid performance impact)
            BKGT_Logger::debug( "Executing database query", array(
                'query' => substr( $prepared_query, 0, 100 ), // First 100 chars
            ) );
            
            // Execute query
            $results = $wpdb->get_results( $prepared_query );
            
            // Check for errors
            if ( $wpdb->last_error ) {
                BKGT_Logger::error( "Database query error: " . $wpdb->last_error, array(
                    'query' => $query,
                ) );
                return null;
            }
            
            return $results;
            
        } catch ( Exception $e ) {
            BKGT_Logger::error( "Exception executing query: " . $e->getMessage() );
            return null;
        }
    }
    
    /**
     * Get a single row from database
     * 
     * @param string $query SQL query
     * @param array  $args  Query arguments
     * 
     * @return object|null Single row or null
     */
    public static function query_row( $query = '', $args = array() ) {
        global $wpdb;
        
        try {
            if ( empty( $query ) ) {
                return null;
            }
            
            // Prepare query
            $prepared_query = ! empty( $args ) ? $wpdb->prepare( $query, $args ) : $query;
            
            $result = $wpdb->get_row( $prepared_query );
            
            if ( $wpdb->last_error ) {
                BKGT_Logger::error( "Database query error: " . $wpdb->last_error );
                return null;
            }
            
            return $result;
            
        } catch ( Exception $e ) {
            BKGT_Logger::error( "Exception executing query_row: " . $e->getMessage() );
            return null;
        }
    }
    
    /**
     * Get a single column value
     * 
     * @param string $query SQL query
     * @param array  $args  Query arguments
     * 
     * @return mixed|null Column value or null
     */
    public static function query_var( $query = '', $args = array() ) {
        global $wpdb;
        
        try {
            if ( empty( $query ) ) {
                return null;
            }
            
            // Prepare query
            $prepared_query = ! empty( $args ) ? $wpdb->prepare( $query, $args ) : $query;
            
            $result = $wpdb->get_var( $prepared_query );
            
            if ( $wpdb->last_error ) {
                BKGT_Logger::error( "Database query error: " . $wpdb->last_error );
                return null;
            }
            
            return $result;
            
        } catch ( Exception $e ) {
            BKGT_Logger::error( "Exception executing query_var: " . $e->getMessage() );
            return null;
        }
    }
    
    /**
     * Insert row into database
     * 
     * @param string $table Table name (without prefix)
     * @param array  $data  Data to insert
     * 
     * @return int|false Insert ID or false on failure
     */
    public static function insert( $table = '', $data = array() ) {
        global $wpdb;
        
        try {
            if ( empty( $table ) || empty( $data ) ) {
                return false;
            }
            
            // Sanitize data
            $data = self::sanitize_array( $data );
            
            // Insert
            $result = $wpdb->insert( $wpdb->prefix . $table, $data );
            
            if ( ! $result ) {
                BKGT_Logger::error( "Failed to insert into {$table}: " . $wpdb->last_error, array(
                    'table' => $table,
                    'data' => $data,
                ) );
                return false;
            }
            
            BKGT_Logger::debug( "Inserted into {$table}", array(
                'insert_id' => $wpdb->insert_id,
            ) );
            
            // Clear cache
            self::clear_cache();
            
            return $wpdb->insert_id;
            
        } catch ( Exception $e ) {
            BKGT_Logger::error( "Exception inserting into {$table}: " . $e->getMessage() );
            return false;
        }
    }
    
    /**
     * Update row in database
     * 
     * @param string $table Table name (without prefix)
     * @param array  $data  Data to update
     * @param array  $where Where clause
     * 
     * @return int|false Rows affected or false on failure
     */
    public static function update( $table = '', $data = array(), $where = array() ) {
        global $wpdb;
        
        try {
            if ( empty( $table ) || empty( $data ) || empty( $where ) ) {
                return false;
            }
            
            // Sanitize data
            $data = self::sanitize_array( $data );
            $where = self::sanitize_array( $where );
            
            // Update
            $result = $wpdb->update( $wpdb->prefix . $table, $data, $where );
            
            if ( $result === false ) {
                BKGT_Logger::error( "Failed to update {$table}: " . $wpdb->last_error );
                return false;
            }
            
            BKGT_Logger::debug( "Updated {$table}", array(
                'rows_affected' => $result,
            ) );
            
            // Clear cache
            self::clear_cache();
            
            return $result;
            
        } catch ( Exception $e ) {
            BKGT_Logger::error( "Exception updating {$table}: " . $e->getMessage() );
            return false;
        }
    }
    
    /**
     * Delete rows from database
     * 
     * @param string $table Table name (without prefix)
     * @param array  $where Where clause
     * 
     * @return int|false Rows affected or false on failure
     */
    public static function delete( $table = '', $where = array() ) {
        global $wpdb;
        
        try {
            if ( empty( $table ) || empty( $where ) ) {
                return false;
            }
            
            // Sanitize where clause
            $where = self::sanitize_array( $where );
            
            // Delete
            $result = $wpdb->delete( $wpdb->prefix . $table, $where );
            
            if ( $result === false ) {
                BKGT_Logger::error( "Failed to delete from {$table}: " . $wpdb->last_error );
                return false;
            }
            
            BKGT_Logger::info( "Deleted from {$table}", array(
                'rows_affected' => $result,
                'user_id' => get_current_user_id(),
            ) );
            
            // Clear cache
            self::clear_cache();
            
            return $result;
            
        } catch ( Exception $e ) {
            BKGT_Logger::error( "Exception deleting from {$table}: " . $e->getMessage() );
            return false;
        }
    }
    
    /**
     * Validate post data
     * 
     * @param array $post_data Post data
     * 
     * @return bool|WP_Error True if valid, error otherwise
     */
    private static function validate_post_data( $post_data ) {
        $required_fields = array( 'post_type' );
        
        foreach ( $required_fields as $field ) {
            if ( empty( $post_data[ $field ] ) ) {
                return new WP_Error( 'invalid_post_data', sprintf(
                    __( 'Fältet "%s" är obligatoriskt', 'bkgt' ),
                    $field
                ) );
            }
        }
        
        return true;
    }
    
    /**
     * Sanitize post data
     * 
     * @param array $post_data Post data
     * 
     * @return array Sanitized data
     */
    private static function sanitize_post_data( $post_data ) {
        if ( isset( $post_data['post_title'] ) ) {
            $post_data['post_title'] = sanitize_text_field( $post_data['post_title'] );
        }
        
        if ( isset( $post_data['post_content'] ) ) {
            $post_data['post_content'] = wp_kses_post( $post_data['post_content'] );
        }
        
        if ( isset( $post_data['post_excerpt'] ) ) {
            $post_data['post_excerpt'] = sanitize_text_field( $post_data['post_excerpt'] );
        }
        
        return $post_data;
    }
    
    /**
     * Sanitize array values
     * 
     * @param array $array Array to sanitize
     * 
     * @return array Sanitized array
     */
    private static function sanitize_array( $array ) {
        $sanitized = array();
        
        foreach ( $array as $key => $value ) {
            $sanitized[ $key ] = is_array( $value ) 
                ? self::sanitize_array( $value )
                : sanitize_text_field( $value );
        }
        
        return $sanitized;
    }
    
    /**
     * Clear query cache
     */
    public static function clear_cache() {
        self::$cache = array();
        wp_cache_flush();
    }
    
    /**
     * Get cache statistics
     * 
     * @return array Cache stats
     */
    public static function get_cache_stats() {
        return array(
            'cache_enabled' => self::$cache_enabled,
            'cached_queries' => count( self::$cache ),
            'cache_size' => strlen( serialize( self::$cache ) ),
        );
    }
}

// Initialize on WordPress load
add_action( 'wp_loaded', array( 'BKGT_Database', 'init' ) );
