<?php
/**
 * BKGT Validator - Unified Data Validation and Sanitization
 * 
 * Provides consistent input validation, sanitization, and error handling across all BKGT plugins.
 * Prevents XSS, SQL injection, and ensures data integrity.
 * 
 * @package BKGT
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * BKGT_Validator Class
 * 
 * Centralizes all data validation and sanitization functionality
 */
class BKGT_Validator {
    
    /**
     * Validation error messages
     */
    private static $error_messages = array(
        'required'       => 'Detta fält är obligatoriskt',
        'email'          => 'Ogiltig e-postadress',
        'url'            => 'Ogiltig URL',
        'numeric'        => 'Måste vara ett nummer',
        'integer'        => 'Måste vara ett heltal',
        'min_length'     => 'Måste innehålla minst %d tecken',
        'max_length'     => 'Får innehålla högst %d tecken',
        'min_value'      => 'Måste vara minst %d',
        'max_value'      => 'Får vara högst %d',
        'date'           => 'Ogiltigt datumformat (använd YYYY-MM-DD)',
        'phone'          => 'Ogiltigt telefonnummer',
        'invalid_choice' => 'Ogiltigt val',
        'match'          => 'Värdena matchar inte',
    );
    
    /**
     * Validate required field
     * 
     * @param mixed $value Value to validate
     * 
     * @return bool|string True if valid, error message if invalid
     */
    public static function required( $value ) {
        if ( empty( $value ) && $value !== 0 && $value !== '0' ) {
            return self::$error_messages['required'];
        }
        return true;
    }
    
    /**
     * Validate email address
     * 
     * @param string $value Email to validate
     * 
     * @return bool|string True if valid, error message if invalid
     */
    public static function email( $value ) {
        if ( empty( $value ) ) {
            return true; // Allow empty emails (use with 'required' separately)
        }
        
        if ( ! is_email( $value ) ) {
            return self::$error_messages['email'];
        }
        
        return true;
    }
    
    /**
     * Validate URL
     * 
     * @param string $value URL to validate
     * 
     * @return bool|string True if valid, error message if invalid
     */
    public static function url( $value ) {
        if ( empty( $value ) ) {
            return true;
        }
        
        if ( ! filter_var( $value, FILTER_VALIDATE_URL ) ) {
            return self::$error_messages['url'];
        }
        
        return true;
    }
    
    /**
     * Validate numeric value
     * 
     * @param mixed $value Value to validate
     * 
     * @return bool|string True if valid, error message if invalid
     */
    public static function numeric( $value ) {
        if ( empty( $value ) && $value !== 0 && $value !== '0' ) {
            return true;
        }
        
        if ( ! is_numeric( $value ) ) {
            return self::$error_messages['numeric'];
        }
        
        return true;
    }
    
    /**
     * Validate integer value
     * 
     * @param mixed $value Value to validate
     * 
     * @return bool|string True if valid, error message if invalid
     */
    public static function integer( $value ) {
        if ( empty( $value ) && $value !== 0 && $value !== '0' ) {
            return true;
        }
        
        if ( ! filter_var( $value, FILTER_VALIDATE_INT ) ) {
            return self::$error_messages['integer'];
        }
        
        return true;
    }
    
    /**
     * Validate minimum length
     * 
     * @param string $value Value to validate
     * @param int    $min   Minimum length
     * 
     * @return bool|string True if valid, error message if invalid
     */
    public static function min_length( $value, $min ) {
        if ( empty( $value ) ) {
            return true;
        }
        
        if ( strlen( $value ) < $min ) {
            return sprintf( self::$error_messages['min_length'], $min );
        }
        
        return true;
    }
    
    /**
     * Validate maximum length
     * 
     * @param string $value Value to validate
     * @param int    $max   Maximum length
     * 
     * @return bool|string True if valid, error message if invalid
     */
    public static function max_length( $value, $max ) {
        if ( empty( $value ) ) {
            return true;
        }
        
        if ( strlen( $value ) > $max ) {
            return sprintf( self::$error_messages['max_length'], $max );
        }
        
        return true;
    }
    
    /**
     * Validate minimum value
     * 
     * @param mixed $value Value to validate
     * @param mixed $min   Minimum value
     * 
     * @return bool|string True if valid, error message if invalid
     */
    public static function min_value( $value, $min ) {
        if ( empty( $value ) && $value !== 0 && $value !== '0' ) {
            return true;
        }
        
        if ( $value < $min ) {
            return sprintf( self::$error_messages['min_value'], $min );
        }
        
        return true;
    }
    
    /**
     * Validate maximum value
     * 
     * @param mixed $value Value to validate
     * @param mixed $max   Maximum value
     * 
     * @return bool|string True if valid, error message if invalid
     */
    public static function max_value( $value, $max ) {
        if ( empty( $value ) && $value !== 0 && $value !== '0' ) {
            return true;
        }
        
        if ( $value > $max ) {
            return sprintf( self::$error_messages['max_value'], $max );
        }
        
        return true;
    }
    
    /**
     * Validate date format (YYYY-MM-DD)
     * 
     * @param string $value Date to validate
     * 
     * @return bool|string True if valid, error message if invalid
     */
    public static function date( $value ) {
        if ( empty( $value ) ) {
            return true;
        }
        
        if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $value ) ) {
            return self::$error_messages['date'];
        }
        
        // Validate it's an actual date
        $date_obj = DateTime::createFromFormat( 'Y-m-d', $value );
        if ( ! $date_obj || $date_obj->format( 'Y-m-d' ) !== $value ) {
            return self::$error_messages['date'];
        }
        
        return true;
    }
    
    /**
     * Validate phone number
     * 
     * @param string $value Phone number to validate
     * 
     * @return bool|string True if valid, error message if invalid
     */
    public static function phone( $value ) {
        if ( empty( $value ) ) {
            return true;
        }
        
        // Swedish phone format validation (basic)
        if ( ! preg_match( '/^[\d\s\-\+\(\)]+$/', $value ) || strlen( preg_replace( '/\D/', '', $value ) ) < 6 ) {
            return self::$error_messages['phone'];
        }
        
        return true;
    }
    
    /**
     * Validate value is in allowed choices
     * 
     * @param mixed $value   Value to validate
     * @param array $choices Allowed choices
     * 
     * @return bool|string True if valid, error message if invalid
     */
    public static function in_array( $value, $choices ) {
        if ( empty( $value ) ) {
            return true;
        }
        
        if ( ! in_array( $value, $choices, true ) ) {
            return self::$error_messages['invalid_choice'];
        }
        
        return true;
    }
    
    /**
     * Validate two values match (e.g., password confirmation)
     * 
     * @param mixed $value1 First value
     * @param mixed $value2 Second value
     * 
     * @return bool|string True if valid, error message if invalid
     */
    public static function match( $value1, $value2 ) {
        if ( $value1 !== $value2 ) {
            return self::$error_messages['match'];
        }
        
        return true;
    }
    
    /**
     * Sanitize text input
     * 
     * Removes dangerous characters and HTML/JavaScript
     * 
     * @param string $value Text to sanitize
     * 
     * @return string Sanitized text
     */
    public static function sanitize_text( $value ) {
        if ( ! is_string( $value ) ) {
            return $value;
        }
        
        return sanitize_text_field( $value );
    }
    
    /**
     * Sanitize for database (remove dangerous characters)
     * 
     * @param string $value Value to sanitize
     * 
     * @return string Sanitized value
     */
    public static function sanitize_db( $value ) {
        global $wpdb;
        return $wpdb->prepare( '%s', $value );
    }
    
    /**
     * Sanitize HTML (allow safe HTML tags)
     * 
     * @param string $value HTML to sanitize
     * 
     * @return string Sanitized HTML
     */
    public static function sanitize_html( $value ) {
        return wp_kses_post( $value );
    }
    
    /**
     * Sanitize email address
     * 
     * @param string $value Email to sanitize
     * 
     * @return string Sanitized email
     */
    public static function sanitize_email( $value ) {
        return sanitize_email( $value );
    }
    
    /**
     * Sanitize URL
     * 
     * @param string $value URL to sanitize
     * 
     * @return string Sanitized URL
     */
    public static function sanitize_url( $value ) {
        return esc_url( $value );
    }
    
    /**
     * Escape for HTML output
     * 
     * @param string $value Value to escape
     * 
     * @return string Escaped value
     */
    public static function escape_html( $value ) {
        return esc_html( $value );
    }
    
    /**
     * Escape for HTML attribute
     * 
     * @param string $value Value to escape
     * 
     * @return string Escaped value
     */
    public static function escape_attr( $value ) {
        return esc_attr( $value );
    }
    
    /**
     * Validate and sanitize equipment item data
     * 
     * @param array $data Equipment data to validate
     * 
     * @return array|WP_Error Array of sanitized data or WP_Error with validation errors
     */
    public static function validate_equipment_item( $data ) {
        $errors = array();
        $sanitized = array();
        
        // Required fields
        $required_fields = array( 'manufacturer_id', 'item_type_id', 'condition' );
        foreach ( $required_fields as $field ) {
            if ( empty( $data[ $field ] ) ) {
                $errors[ $field ] = self::$error_messages['required'];
                continue;
            }
        }
        
        // Manufacturer ID - must be numeric
        if ( ! empty( $data['manufacturer_id'] ) ) {
            $validation = self::integer( $data['manufacturer_id'] );
            if ( $validation !== true ) {
                $errors['manufacturer_id'] = $validation;
            } else {
                $sanitized['manufacturer_id'] = intval( $data['manufacturer_id'] );
            }
        }
        
        // Item Type ID - must be numeric
        if ( ! empty( $data['item_type_id'] ) ) {
            $validation = self::integer( $data['item_type_id'] );
            if ( $validation !== true ) {
                $errors['item_type_id'] = $validation;
            } else {
                $sanitized['item_type_id'] = intval( $data['item_type_id'] );
            }
        }
        
        // Condition - must be valid status
        if ( ! empty( $data['condition'] ) ) {
            $valid_conditions = array( 'normal', 'repair', 'repaired', 'lost', 'scrapped' );
            $validation = self::in_array( $data['condition'], $valid_conditions );
            if ( $validation !== true ) {
                $errors['condition'] = $validation;
            } else {
                $sanitized['condition'] = $data['condition'];
            }
        }
        
        // Description - optional, sanitize text
        if ( ! empty( $data['description'] ) ) {
            $sanitized['description'] = self::sanitize_text( $data['description'] );
        }
        
        // Return errors or sanitized data
        if ( ! empty( $errors ) ) {
            return new WP_Error( 'validation_failed', 'Validation failed', $errors );
        }
        
        return $sanitized;
    }
    
    /**
     * Validate nonce for security
     * 
     * @param string $nonce    Nonce value to verify
     * @param string $action   Nonce action
     * 
     * @return bool|WP_Error True if valid, WP_Error if invalid
     */
    public static function verify_nonce( $nonce, $action = -1 ) {
        if ( ! wp_verify_nonce( $nonce, $action ) ) {
            BKGT_Logger::warning( "Nonce verification failed for action: {$action}" );
            return new WP_Error( 'nonce_failed', __( 'Säkerhetskontroll misslyckades', 'bkgt' ) );
        }
        return true;
    }
    
    /**
     * Validate user capability
     * 
     * @param string $capability Capability to check
     * 
     * @return bool|WP_Error True if user has capability, WP_Error if not
     */
    public static function check_capability( $capability ) {
        if ( ! current_user_can( $capability ) ) {
            BKGT_Logger::warning( "User capability check failed: {$capability}", array(
                'user_id' => get_current_user_id(),
                'capability' => $capability,
            ) );
            return new WP_Error( 'access_denied', __( 'Du har inte behörighet att utföra denna åtgärd', 'bkgt' ) );
        }
        return true;
    }
    
    /**
     * Check if validation errors exist
     * 
     * @param array $errors Array of validation errors
     * 
     * @return bool True if errors exist, false otherwise
     */
    public static function has_errors( $errors ) {
        return ! empty( $errors ) && is_array( $errors );
    }
    
    /**
     * General validation method called by sanitizer
     * 
     * @param array  $data       Sanitized data to validate
     * @param string $entity_type Type of entity being validated
     * @param int    $entity_id   Optional entity ID for updates
     * 
     * @return array Array of validation errors (empty if valid)
     */
    public static function validate( $data, $entity_type, $entity_id = null ) {
        $errors = array();
        
        switch ( $entity_type ) {
            case 'manufacturer':
                $errors = self::validate_manufacturer( $data, $entity_id );
                break;
                
            case 'item_type':
                $errors = self::validate_item_type( $data, $entity_id );
                break;
                
            case 'inventory_item':
                $errors = self::validate_inventory_item( $data, $entity_id );
                break;
                
            case 'equipment_item':
                $errors = self::validate_equipment_item( $data );
                break;
                
            default:
                // No specific validation for this entity type
                break;
        }
        
        return $errors;
    }
    
    /**
     * Validate manufacturer data
     * 
     * @param array $data     Manufacturer data
     * @param int   $entity_id Optional manufacturer ID for updates
     * 
     * @return array Validation errors
     */
    private static function validate_manufacturer( $data, $entity_id = null ) {
        $errors = array();
        
        // Name is required
        if ( empty( $data['name'] ) ) {
            $errors['name'] = self::$error_messages['required'];
        } elseif ( strlen( $data['name'] ) > 255 ) {
            $errors['name'] = sprintf( self::$error_messages['max_length'], 255 );
        }
        
        // Check for duplicate name if creating new manufacturer
        if ( ! $entity_id && ! empty( $data['name'] ) ) {
            global $wpdb;
            $existing = $wpdb->get_var( $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}bkgt_manufacturers WHERE name = %s",
                $data['name']
            ) );
            if ( $existing > 0 ) {
                $errors['name'] = __( 'En tillverkare med detta namn finns redan', 'bkgt' );
            }
        }
        
        return $errors;
    }
    
    /**
     * Validate item type data
     * 
     * @param array $data     Item type data
     * @param int   $entity_id Optional item type ID for updates
     * 
     * @return array Validation errors
     */
    private static function validate_item_type( $data, $entity_id = null ) {
        $errors = array();
        
        // Name is required
        if ( empty( $data['name'] ) ) {
            $errors['name'] = self::$error_messages['required'];
        } elseif ( strlen( $data['name'] ) > 255 ) {
            $errors['name'] = sprintf( self::$error_messages['max_length'], 255 );
        }
        
        // Check for duplicate name if creating new item type
        if ( ! $entity_id && ! empty( $data['name'] ) ) {
            global $wpdb;
            $existing = $wpdb->get_var( $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}bkgt_item_types WHERE name = %s",
                $data['name']
            ) );
            if ( $existing > 0 ) {
                $errors['name'] = __( 'En artikeltyp med detta namn finns redan', 'bkgt' );
            }
        }
        
        return $errors;
    }
    
    /**
     * Validate inventory item data
     * 
     * @param array $data     Inventory item data
     * @param int   $entity_id Optional inventory item ID for updates
     * 
     * @return array Validation errors
     */
    private static function validate_inventory_item( $data, $entity_id = null ) {
        $errors = array();
        
        // Manufacturer ID is required and must be numeric
        if ( empty( $data['manufacturer_id'] ) ) {
            $errors['manufacturer_id'] = self::$error_messages['required'];
        } elseif ( ! is_numeric( $data['manufacturer_id'] ) ) {
            $errors['manufacturer_id'] = self::$error_messages['numeric'];
        } else {
            // Check if manufacturer exists
            global $wpdb;
            $manufacturer_exists = $wpdb->get_var( $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}bkgt_manufacturers WHERE id = %d",
                $data['manufacturer_id']
            ) );
            if ( ! $manufacturer_exists ) {
                $errors['manufacturer_id'] = __( 'Ogiltig tillverkare vald', 'bkgt' );
            }
        }
        
        // Item type ID is required and must be numeric
        if ( empty( $data['item_type_id'] ) ) {
            $errors['item_type_id'] = self::$error_messages['required'];
        } elseif ( ! is_numeric( $data['item_type_id'] ) ) {
            $errors['item_type_id'] = self::$error_messages['numeric'];
        } else {
            // Check if item type exists
            global $wpdb;
            $item_type_exists = $wpdb->get_var( $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}bkgt_item_types WHERE id = %d",
                $data['item_type_id']
            ) );
            if ( ! $item_type_exists ) {
                $errors['item_type_id'] = __( 'Ogiltig artikeltyp vald', 'bkgt' );
            }
        }
        
        // Title is required
        if ( empty( $data['title'] ) ) {
            $errors['title'] = self::$error_messages['required'];
        } elseif ( strlen( $data['title'] ) > 255 ) {
            $errors['title'] = sprintf( self::$error_messages['max_length'], 255 );
        }
        
        return $errors;
    }
}
