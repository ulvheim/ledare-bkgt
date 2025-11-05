<?php
/**
 * BKGT Exception Classes - Comprehensive Error Handling
 * 
 * Provides domain-specific exception classes for different system components.
 * Enables granular error handling and recovery strategies.
 * 
 * @package BKGT
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Base BKGT Exception
 * 
 * All BKGT exceptions extend this base class for consistent handling
 */
class BKGT_Exception extends Exception {
    
    /**
     * Recovery suggestions for this exception
     * 
     * @var array
     */
    protected $recovery_suggestions = array();
    
    /**
     * Error context (user, request, etc.)
     * 
     * @var array
     */
    protected $context = array();
    
    /**
     * Constructor
     * 
     * @param string $message Exception message
     * @param int    $code Exception code
     * @param array  $recovery_suggestions Array of recovery suggestions
     * @param array  $context Context data
     */
    public function __construct( $message = '', $code = 0, $recovery_suggestions = array(), $context = array() ) {
        parent::__construct( $message, $code );
        $this->recovery_suggestions = $recovery_suggestions;
        $this->context = $context;
        
        // Log the exception
        $this->log_exception();
    }
    
    /**
     * Get recovery suggestions
     * 
     * @return array
     */
    public function get_recovery_suggestions() {
        return $this->recovery_suggestions;
    }
    
    /**
     * Get context data
     * 
     * @return array
     */
    public function get_context() {
        return $this->context;
    }
    
    /**
     * Log the exception
     */
    protected function log_exception() {
        if ( class_exists( 'BKGT_Logger' ) ) {
            $level = $this->get_log_level();
            BKGT_Logger::log( $level, $this->getMessage(), $this->context );
        }
    }
    
    /**
     * Get appropriate log level for this exception
     * 
     * @return string
     */
    protected function get_log_level() {
        return BKGT_Logger::ERROR;
    }
}

/**
 * Database Exception
 * 
 * Thrown when database operations fail
 */
class BKGT_Database_Exception extends BKGT_Exception {
    
    const QUERY_FAILED = 1;
    const CONNECTION_FAILED = 2;
    const TABLE_NOT_FOUND = 3;
    const CONSTRAINT_VIOLATION = 4;
    
    /**
     * Constructor
     * 
     * @param string $message Exception message
     * @param int    $code Exception code
     * @param array  $context Context data
     */
    public function __construct( $message = '', $code = self::QUERY_FAILED, $context = array() ) {
        $recovery_suggestions = array(
            __( 'Försök ladda om sidan', 'bkgt-core' ),
            __( 'Kontakta administratören om problemet kvarstår', 'bkgt-core' ),
            __( 'Kontrollera databasanslutningen i wp-config.php', 'bkgt-core' ),
        );
        
        parent::__construct( $message, $code, $recovery_suggestions, $context );
    }
    
    protected function get_log_level() {
        return BKGT_Logger::ERROR;
    }
}

/**
 * Validation Exception
 * 
 * Thrown when input validation fails
 */
class BKGT_Validation_Exception extends BKGT_Exception {
    
    const INVALID_EMAIL = 1;
    const INVALID_FORMAT = 2;
    const REQUIRED_FIELD = 3;
    const CONSTRAINT_VIOLATION = 4;
    const INVALID_CHOICE = 5;
    
    /**
     * Validation errors
     * 
     * @var array
     */
    private $validation_errors = array();
    
    /**
     * Constructor
     * 
     * @param string $message Exception message
     * @param int    $code Exception code
     * @param array  $validation_errors Field-level validation errors
     * @param array  $context Context data
     */
    public function __construct( $message = '', $code = self::INVALID_FORMAT, $validation_errors = array(), $context = array() ) {
        $this->validation_errors = $validation_errors;
        
        $recovery_suggestions = array(
            __( 'Kontrollera de markerade fälten och försök igen', 'bkgt-core' ),
            __( 'Alla obligatoriska fält måste fyllas i', 'bkgt-core' ),
        );
        
        parent::__construct( $message, $code, $recovery_suggestions, $context );
    }
    
    /**
     * Get validation errors
     * 
     * @return array
     */
    public function get_validation_errors() {
        return $this->validation_errors;
    }
    
    protected function get_log_level() {
        return BKGT_Logger::WARNING;
    }
}

/**
 * Permission Exception
 * 
 * Thrown when user lacks required permissions
 */
class BKGT_Permission_Exception extends BKGT_Exception {
    
    const INSUFFICIENT_ROLE = 1;
    const INVALID_NONCE = 2;
    const TEAM_ACCESS_DENIED = 3;
    const ACTION_NOT_ALLOWED = 4;
    
    /**
     * Required capability
     * 
     * @var string
     */
    private $required_capability = '';
    
    /**
     * Constructor
     * 
     * @param string $message Exception message
     * @param int    $code Exception code
     * @param string $required_capability Required capability
     * @param array  $context Context data
     */
    public function __construct( $message = '', $code = self::INSUFFICIENT_ROLE, $required_capability = '', $context = array() ) {
        $this->required_capability = $required_capability;
        
        $recovery_suggestions = array(
            __( 'Du har inte behörighet för denna åtgärd', 'bkgt-core' ),
            __( 'Kontakta en administratör för att begära åtkomst', 'bkgt-core' ),
        );
        
        parent::__construct( $message, $code, $recovery_suggestions, $context );
    }
    
    /**
     * Get required capability
     * 
     * @return string
     */
    public function get_required_capability() {
        return $this->required_capability;
    }
    
    protected function get_log_level() {
        return BKGT_Logger::WARNING;
    }
}

/**
 * Resource Not Found Exception
 * 
 * Thrown when a resource (post, user, etc.) is not found
 */
class BKGT_Resource_Not_Found_Exception extends BKGT_Exception {
    
    const POST_NOT_FOUND = 1;
    const USER_NOT_FOUND = 2;
    const RESOURCE_NOT_FOUND = 3;
    const TEAM_NOT_FOUND = 4;
    
    /**
     * Resource type
     * 
     * @var string
     */
    private $resource_type = '';
    
    /**
     * Resource identifier
     * 
     * @var mixed
     */
    private $resource_id = null;
    
    /**
     * Constructor
     * 
     * @param string $message Exception message
     * @param int    $code Exception code
     * @param string $resource_type Type of resource (post, user, etc.)
     * @param mixed  $resource_id Identifier of the resource
     * @param array  $context Context data
     */
    public function __construct( $message = '', $code = self::RESOURCE_NOT_FOUND, $resource_type = '', $resource_id = null, $context = array() ) {
        $this->resource_type = $resource_type;
        $this->resource_id = $resource_id;
        
        $recovery_suggestions = array(
            sprintf( __( '%s hittades inte', 'bkgt-core' ), ucfirst( $resource_type ) ),
            __( 'Den kan ha tagits bort eller flyttats', 'bkgt-core' ),
            __( 'Försök navigera tillbaka och uppdatera', 'bkgt-core' ),
        );
        
        parent::__construct( $message, $code, $recovery_suggestions, $context );
    }
    
    /**
     * Get resource type
     * 
     * @return string
     */
    public function get_resource_type() {
        return $this->resource_type;
    }
    
    /**
     * Get resource ID
     * 
     * @return mixed
     */
    public function get_resource_id() {
        return $this->resource_id;
    }
    
    protected function get_log_level() {
        return BKGT_Logger::WARNING;
    }
}

/**
 * External API Exception
 * 
 * Thrown when external API calls fail
 */
class BKGT_API_Exception extends BKGT_Exception {
    
    const CONNECTION_FAILED = 1;
    const TIMEOUT = 2;
    const INVALID_RESPONSE = 3;
    const RATE_LIMIT = 4;
    const AUTHENTICATION_FAILED = 5;
    
    /**
     * API endpoint
     * 
     * @var string
     */
    private $endpoint = '';
    
    /**
     * HTTP status code
     * 
     * @var int
     */
    private $http_status = 0;
    
    /**
     * Constructor
     * 
     * @param string $message Exception message
     * @param int    $code Exception code
     * @param string $endpoint API endpoint
     * @param int    $http_status HTTP status code
     * @param array  $context Context data
     */
    public function __construct( $message = '', $code = self::CONNECTION_FAILED, $endpoint = '', $http_status = 0, $context = array() ) {
        $this->endpoint = $endpoint;
        $this->http_status = $http_status;
        
        $recovery_suggestions = array(
            __( 'Den externa tjänsten är för närvarande otillgänglig', 'bkgt-core' ),
            __( 'Försök igen senare', 'bkgt-core' ),
            __( 'Kontakta administratören om problemet kvarstår', 'bkgt-core' ),
        );
        
        // Add specific suggestions based on error code
        if ( $code === self::RATE_LIMIT ) {
            $recovery_suggestions[] = __( 'Du har gjort för många förfrågningar, vänta innan du försöker igen', 'bkgt-core' );
        }
        
        parent::__construct( $message, $code, $recovery_suggestions, $context );
    }
    
    /**
     * Get endpoint
     * 
     * @return string
     */
    public function get_endpoint() {
        return $this->endpoint;
    }
    
    /**
     * Get HTTP status code
     * 
     * @return int
     */
    public function get_http_status() {
        return $this->http_status;
    }
    
    protected function get_log_level() {
        return BKGT_Logger::ERROR;
    }
}

/**
 * File Operation Exception
 * 
 * Thrown when file operations fail
 */
class BKGT_File_Exception extends BKGT_Exception {
    
    const FILE_NOT_FOUND = 1;
    const PERMISSION_DENIED = 2;
    const WRITE_FAILED = 3;
    const READ_FAILED = 4;
    const INVALID_FORMAT = 5;
    
    /**
     * File path
     * 
     * @var string
     */
    private $file_path = '';
    
    /**
     * Constructor
     * 
     * @param string $message Exception message
     * @param int    $code Exception code
     * @param string $file_path Path to the file
     * @param array  $context Context data
     */
    public function __construct( $message = '', $code = self::FILE_NOT_FOUND, $file_path = '', $context = array() ) {
        $this->file_path = $file_path;
        
        $recovery_suggestions = array(
            __( 'Kontrollera att filen finns och är läsbar', 'bkgt-core' ),
            __( 'Kontrollera filbehörigheterna', 'bkgt-core' ),
            __( 'Kontakta serverleverantören för att kontrollera diskutrymme', 'bkgt-core' ),
        );
        
        parent::__construct( $message, $code, $recovery_suggestions, $context );
    }
    
    /**
     * Get file path
     * 
     * @return string
     */
    public function get_file_path() {
        return $this->file_path;
    }
    
    protected function get_log_level() {
        return BKGT_Logger::ERROR;
    }
}

/**
 * Configuration Exception
 * 
 * Thrown when configuration is missing or invalid
 */
class BKGT_Configuration_Exception extends BKGT_Exception {
    
    const MISSING_SETTING = 1;
    const INVALID_SETTING = 2;
    const INCOMPLETE_CONFIG = 3;
    
    /**
     * Setting name
     * 
     * @var string
     */
    private $setting_name = '';
    
    /**
     * Constructor
     * 
     * @param string $message Exception message
     * @param int    $code Exception code
     * @param string $setting_name Name of the missing/invalid setting
     * @param array  $context Context data
     */
    public function __construct( $message = '', $code = self::MISSING_SETTING, $setting_name = '', $context = array() ) {
        $this->setting_name = $setting_name;
        
        $recovery_suggestions = array(
            sprintf( __( 'Konfigurera inställningen "%s" i admin-panelen', 'bkgt-core' ), $setting_name ),
            __( 'Kontrollera BKGT-pluginets inställningar', 'bkgt-core' ),
            __( 'Kontakta administratören om du inte vet vad som ska konfigureras', 'bkgt-core' ),
        );
        
        parent::__construct( $message, $code, $recovery_suggestions, $context );
    }
    
    /**
     * Get setting name
     * 
     * @return string
     */
    public function get_setting_name() {
        return $this->setting_name;
    }
    
    protected function get_log_level() {
        return BKGT_Logger::WARNING;
    }
}

/**
 * Rate Limit Exception
 * 
 * Thrown when rate limits are exceeded
 */
class BKGT_Rate_Limit_Exception extends BKGT_Exception {
    
    const ACTION_LIMITED = 1;
    const IP_LIMITED = 2;
    const USER_LIMITED = 3;
    
    /**
     * Time until reset (in seconds)
     * 
     * @var int
     */
    private $reset_time = 0;
    
    /**
     * Constructor
     * 
     * @param string $message Exception message
     * @param int    $code Exception code
     * @param int    $reset_time Seconds until rate limit resets
     * @param array  $context Context data
     */
    public function __construct( $message = '', $code = self::ACTION_LIMITED, $reset_time = 0, $context = array() ) {
        $this->reset_time = $reset_time;
        
        $recovery_suggestions = array(
            sprintf( __( 'Försök igen om %d sekunder', 'bkgt-core' ), $reset_time ),
            __( 'Du gör för många åtgärder för snabbt', 'bkgt-core' ),
        );
        
        parent::__construct( $message, $code, $recovery_suggestions, $context );
    }
    
    /**
     * Get reset time
     * 
     * @return int
     */
    public function get_reset_time() {
        return $this->reset_time;
    }
    
    protected function get_log_level() {
        return BKGT_Logger::WARNING;
    }
}
