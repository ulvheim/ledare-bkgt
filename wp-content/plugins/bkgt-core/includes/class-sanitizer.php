<?php
/**
 * BKGT_Sanitizer - Unified Data Sanitization System
 * 
 * Provides standardized sanitization methods for all forms across BKGT plugins.
 * Ensures data consistency and security across the entire system.
 * 
 * @package    BKGT_Core
 * @subpackage Sanitization
 * @version    1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class BKGT_Sanitizer {
    
    /**
     * Sanitization rules by entity type
     * 
     * @var array
     */
    private static $rules = array();
    
    /**
     * Initialize sanitizer with default rules
     */
    public static function init() {
        self::register_default_rules();
    }
    
    /**
     * Register default sanitization rules
     */
    private static function register_default_rules() {
        // Equipment item sanitization
        self::$rules['equipment_item'] = array(
            'name' => 'sanitize_text_field',
            'manufacturer_id' => 'absint',
            'item_type_id' => 'absint',
            'serial_number' => 'sanitize_text_field',
            'description' => 'wp_kses_post',
            'purchase_date' => 'sanitize_text_field',
            'purchase_price' => 'floatval',
            'warranty_expiry' => 'sanitize_text_field',
            'condition' => 'sanitize_text_field',
        );
        
        // Manufacturer sanitization
        self::$rules['manufacturer'] = array(
            'name' => 'sanitize_text_field',
            'code' => array('callback' => function($value) {
                return strtoupper(sanitize_text_field($value));
            }),
            'contact_info' => 'sanitize_textarea_field',
        );
        
        // Item type sanitization
        self::$rules['item_type'] = array(
            'name' => 'sanitize_text_field',
            'description' => 'wp_kses_post',
        );
        
        // Event sanitization
        self::$rules['event'] = array(
            'title' => 'sanitize_text_field',
            'description' => 'wp_kses_post',
            'event_date' => 'sanitize_text_field',
            'team_id' => 'absint',
            'location' => 'sanitize_text_field',
        );
        
        // User sanitization
        self::$rules['user'] = array(
            'email' => 'sanitize_email',
            'display_name' => 'sanitize_text_field',
            'first_name' => 'sanitize_text_field',
            'last_name' => 'sanitize_text_field',
            'role' => 'sanitize_text_field',
            'bio' => 'wp_kses_post',
        );
        
        // Document sanitization
        self::$rules['document'] = array(
            'title' => 'sanitize_text_field',
            'description' => 'wp_kses_post',
            'file_url' => 'esc_url_raw',
            'document_type' => 'sanitize_text_field',
        );
        
        // Settings sanitization
        self::$rules['settings'] = array(
            'club_name' => 'sanitize_text_field',
            'club_email' => 'sanitize_email',
            'contact_person' => 'sanitize_text_field',
            'phone' => 'sanitize_text_field',
            'address' => 'sanitize_textarea_field',
        );
    }
    
    /**
     * Sanitize data against rules
     * 
     * @param  array  $data       Data to sanitize
     * @param  string $entity_type Type of entity
     * @return array              Sanitized data
     */
    public static function sanitize($data, $entity_type) {
        if (!isset(self::$rules[$entity_type])) {
            bkgt_log('warning', 'Unknown entity type for sanitization', array(
                'entity_type' => $entity_type,
            ));
            return $data;
        }
        
        $rules = self::$rules[$entity_type];
        $sanitized = array();
        
        foreach ($data as $field_name => $value) {
            if (!isset($rules[$field_name])) {
                // Field not in rules, sanitize as text
                $sanitized[$field_name] = self::sanitize_field($field_name, $value, 'sanitize_text_field');
                continue;
            }
            
            $sanitizer = $rules[$field_name];
            $sanitized[$field_name] = self::sanitize_field($field_name, $value, $sanitizer);
        }
        
        return $sanitized;
    }
    
    /**
     * Sanitize single field
     * 
     * @param  string $field_name Field name
     * @param  mixed  $value      Field value
     * @param  mixed  $sanitizer  Sanitizer function or array
     * @return mixed              Sanitized value
     */
    private static function sanitize_field($field_name, $value, $sanitizer) {
        // Handle null/empty values
        if (is_null($value)) {
            return null;
        }
        
        if (empty($value) && !is_numeric($value)) {
            return '';
        }
        
        // Handle custom callback
        if (is_array($sanitizer) && isset($sanitizer['callback'])) {
            return call_user_func($sanitizer['callback'], $value);
        }
        
        // Handle WordPress sanitize functions
        if (is_string($sanitizer) && function_exists($sanitizer)) {
            return call_user_func($sanitizer, $value);
        }
        
        // Handle arrays - sanitize each element
        if (is_array($value) && is_string($sanitizer)) {
            return array_map(function($item) use ($sanitizer) {
                return function_exists($sanitizer) ? call_user_func($sanitizer, $item) : $item;
            }, $value);
        }
        
        return $value;
    }
    
    /**
     * Sanitize and validate data in one step
     * 
     * Combines sanitization and validation for efficient form processing
     * 
     * @param  array  $data       Data to process
     * @param  string $entity_type Entity type
     * @return array              Array with 'data' (sanitized) and 'errors' keys
     */
    public static function process($data, $entity_type, $entity_id = null) {
        // First sanitize
        $sanitized = self::sanitize($data, $entity_type);
        
        // Then validate
        $errors = BKGT_Validator::validate($sanitized, $entity_type, $entity_id);
        
        return array(
            'data' => $sanitized,
            'errors' => $errors,
        );
    }
    
    /**
     * Sanitize array of values
     * 
     * @param  array  $values    Values to sanitize
     * @param  string $sanitizer Sanitizer function
     * @return array             Sanitized values
     */
    public static function sanitize_array($values, $sanitizer = 'sanitize_text_field') {
        if (!is_array($values)) {
            return $values;
        }
        
        return array_map(function($value) use ($sanitizer) {
            return function_exists($sanitizer) ? call_user_func($sanitizer, $value) : $value;
        }, $values);
    }
    
    /**
     * Sanitize form POST data
     * 
     * Convenience method to sanitize $_POST data for a specific entity
     * 
     * @param  string $entity_type Entity type
     * @param  array  $fields      Optional array of field names to extract
     * @return array               Sanitized data
     */
    public static function sanitize_post($entity_type, $fields = null) {
        $data = array();
        
        if ($fields) {
            foreach ($fields as $field) {
                if (isset($_POST[$field])) {
                    $data[$field] = $_POST[$field];
                }
            }
        } else {
            $data = $_POST;
        }
        
        return self::sanitize($data, $entity_type);
    }
    
    /**
     * Register custom sanitization rule
     * 
     * @param string $entity_type Entity type
     * @param string $field_name  Field name
     * @param mixed  $sanitizer   Sanitizer function or callback
     */
    public static function register_rule($entity_type, $field_name, $sanitizer) {
        if (!isset(self::$rules[$entity_type])) {
            self::$rules[$entity_type] = array();
        }
        self::$rules[$entity_type][$field_name] = $sanitizer;
    }
    
    /**
     * Sanitize HTML content (allow limited tags)
     * 
     * @param  string $content Content to sanitize
     * @return string          Sanitized content
     */
    public static function sanitize_html($content) {
        $allowed_html = array(
            'p' => array(),
            'br' => array(),
            'strong' => array(),
            'em' => array(),
            'u' => array(),
            'a' => array(
                'href' => array(),
                'title' => array(),
            ),
            'ul' => array(),
            'ol' => array(),
            'li' => array(),
            'blockquote' => array(),
        );
        
        return wp_kses($content, $allowed_html);
    }
    
    /**
     * Sanitize phone number
     * 
     * @param  string $phone Phone number
     * @return string        Sanitized phone
     */
    public static function sanitize_phone($phone) {
        return preg_replace('/[^0-9\s\-\+\(\)]/', '', $phone);
    }
    
    /**
     * Sanitize Swedish personal number (personnummer)
     * 
     * @param  string $personnummer Swedish personal number
     * @return string               Sanitized
     */
    public static function sanitize_personnummer($personnummer) {
        // Remove hyphens and spaces
        $cleaned = preg_replace('/[\s\-]/', '', $personnummer);
        
        // Keep only digits
        $cleaned = preg_replace('/[^0-9]/', '', $cleaned);
        
        return $cleaned;
    }
    
    /**
     * Get all sanitization rules for entity
     * 
     * @param  string $entity_type Entity type
     * @return array               Sanitization rules
     */
    public static function get_rules($entity_type) {
        return isset(self::$rules[$entity_type]) ? self::$rules[$entity_type] : array();
    }
}

// Initialize sanitizer on plugin load
if (!has_action('init', array('BKGT_Sanitizer', 'init'))) {
    add_action('init', array('BKGT_Sanitizer', 'init'), 5);
}
