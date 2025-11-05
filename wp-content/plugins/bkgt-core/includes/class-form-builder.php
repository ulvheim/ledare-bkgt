<?php
/**
 * BKGT Form Builder - Helper class for creating forms
 * 
 * Provides a fluent API for building forms programmatically.
 * 
 * @package BKGT
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * BKGT_Form_Builder Class
 */
class BKGT_Form_Builder {
    
    /**
     * Form configuration
     */
    private $form_config = array();
    
    /**
     * Form fields
     */
    private $fields = array();
    
    /**
     * Constructor
     */
    public function __construct( $form_id = null, $options = array() ) {
        $this->form_config = array(
            'id' => $form_id ?: 'bkgt-form-' . rand( 1000, 9999 ),
            'layout' => isset( $options['layout'] ) ? $options['layout'] : 'vertical',
            'submitText' => isset( $options['submitText'] ) ? $options['submitText'] : 'Skicka',
            'cancelText' => isset( $options['cancelText'] ) ? $options['cancelText'] : 'Avbryt',
            'showCancel' => isset( $options['showCancel'] ) ? $options['showCancel'] : true,
            ...array_filter( $options, function( $key ) {
                return ! in_array( $key, array( 'layout', 'submitText', 'cancelText', 'showCancel' ), true );
            }, ARRAY_FILTER_USE_KEY ),
        );
    }
    
    /**
     * Add text field
     * 
     * @param string $name Field name
     * @param string $label Field label
     * @param array  $options Additional options
     * 
     * @return $this Fluent interface
     */
    public function add_text( $name, $label = '', $options = array() ) {
        return $this->add_field( 'text', $name, $label, $options );
    }
    
    /**
     * Add email field
     * 
     * @param string $name Field name
     * @param string $label Field label
     * @param array  $options Additional options
     * 
     * @return $this Fluent interface
     */
    public function add_email( $name, $label = '', $options = array() ) {
        return $this->add_field( 'email', $name, $label, $options );
    }
    
    /**
     * Add password field
     * 
     * @param string $name Field name
     * @param string $label Field label
     * @param array  $options Additional options
     * 
     * @return $this Fluent interface
     */
    public function add_password( $name, $label = '', $options = array() ) {
        return $this->add_field( 'password', $name, $label, $options );
    }
    
    /**
     * Add textarea field
     * 
     * @param string $name Field name
     * @param string $label Field label
     * @param array  $options Additional options
     * 
     * @return $this Fluent interface
     */
    public function add_textarea( $name, $label = '', $options = array() ) {
        $defaults = array(
            'rows' => 4,
        );
        $options = wp_parse_args( $options, $defaults );
        return $this->add_field( 'textarea', $name, $label, $options );
    }
    
    /**
     * Add select field
     * 
     * @param string $name Field name
     * @param string $label Field label
     * @param array  $options Additional options (including 'options' key for choices)
     * 
     * @return $this Fluent interface
     */
    public function add_select( $name, $label = '', $options = array() ) {
        return $this->add_field( 'select', $name, $label, $options );
    }
    
    /**
     * Add checkbox field
     * 
     * @param string $name Field name
     * @param string $label Field label
     * @param array  $options Additional options
     * 
     * @return $this Fluent interface
     */
    public function add_checkbox( $name, $label = '', $options = array() ) {
        return $this->add_field( 'checkbox', $name, $label, $options );
    }
    
    /**
     * Add radio field
     * 
     * @param string $name Field name
     * @param string $label Field label
     * @param array  $options Additional options (including 'options' key for choices)
     * 
     * @return $this Fluent interface
     */
    public function add_radio( $name, $label = '', $options = array() ) {
        return $this->add_field( 'radio', $name, $label, $options );
    }
    
    /**
     * Add date field
     * 
     * @param string $name Field name
     * @param string $label Field label
     * @param array  $options Additional options
     * 
     * @return $this Fluent interface
     */
    public function add_date( $name, $label = '', $options = array() ) {
        return $this->add_field( 'date', $name, $label, $options );
    }
    
    /**
     * Add number field
     * 
     * @param string $name Field name
     * @param string $label Field label
     * @param array  $options Additional options
     * 
     * @return $this Fluent interface
     */
    public function add_number( $name, $label = '', $options = array() ) {
        return $this->add_field( 'number', $name, $label, $options );
    }
    
    /**
     * Add phone field
     * 
     * @param string $name Field name
     * @param string $label Field label
     * @param array  $options Additional options
     * 
     * @return $this Fluent interface
     */
    public function add_phone( $name, $label = '', $options = array() ) {
        return $this->add_field( 'tel', $name, $label, $options );
    }
    
    /**
     * Add URL field
     * 
     * @param string $name Field name
     * @param string $label Field label
     * @param array  $options Additional options
     * 
     * @return $this Fluent interface
     */
    public function add_url( $name, $label = '', $options = array() ) {
        return $this->add_field( 'url', $name, $label, $options );
    }
    
    /**
     * Add hidden field
     * 
     * @param string $name Field name
     * @param mixed  $value Field value
     * 
     * @return $this Fluent interface
     */
    public function add_hidden( $name, $value = '' ) {
        return $this->add_field( 'hidden', $name, '', array( 'value' => $value ) );
    }
    
    /**
     * Add generic field
     * 
     * @param string $type Field type
     * @param string $name Field name
     * @param string $label Field label
     * @param array  $options Additional options
     * 
     * @return $this Fluent interface
     */
    public function add_field( $type, $name, $label = '', $options = array() ) {
        $field = array(
            'type'     => $type,
            'name'     => $name,
            'label'    => $label,
            'required' => isset( $options['required'] ) ? $options['required'] : false,
        );
        
        // Merge additional options
        $field = array_merge( $field, $options );
        
        $this->fields[] = $field;
        
        return $this;
    }
    
    /**
     * Set form layout
     * 
     * @param string $layout Layout type (vertical, horizontal, grid)
     * 
     * @return $this Fluent interface
     */
    public function set_layout( $layout ) {
        $this->form_config['layout'] = $layout;
        return $this;
    }
    
    /**
     * Set submit button text
     * 
     * @param string $text Button text
     * 
     * @return $this Fluent interface
     */
    public function set_submit_text( $text ) {
        $this->form_config['submitText'] = $text;
        return $this;
    }
    
    /**
     * Set cancel button text
     * 
     * @param string $text Button text
     * 
     * @return $this Fluent interface
     */
    public function set_cancel_text( $text ) {
        $this->form_config['cancelText'] = $text;
        return $this;
    }
    
    /**
     * Show/hide cancel button
     * 
     * @param bool $show Whether to show cancel button
     * 
     * @return $this Fluent interface
     */
    public function set_show_cancel( $show ) {
        $this->form_config['showCancel'] = (bool) $show;
        return $this;
    }
    
    /**
     * Set form configuration
     * 
     * @param array $config Configuration array
     * 
     * @return $this Fluent interface
     */
    public function set_config( $config ) {
        $this->form_config = array_merge( $this->form_config, $config );
        return $this;
    }
    
    /**
     * Get form configuration as JavaScript object
     * 
     * @return array Form configuration
     */
    public function get_config() {
        return array_merge(
            $this->form_config,
            array( 'fields' => $this->fields )
        );
    }
    
    /**
     * Build form HTML
     * 
     * @return string Form HTML
     */
    public function render() {
        $config = $this->get_config();
        
        // Return JavaScript to create form
        return '<script>
            (function() {
                var formConfig = ' . wp_json_encode( $config ) . ';
                var form = new BKGTForm(formConfig);
                // Form will be rendered when attached to a container
                window.' . esc_js( $config['id'] ) . ' = form;
            })();
        </script>';
    }
    
    /**
     * Get form as PHP array (for template rendering)
     * 
     * @return array Form configuration array
     */
    public function get_array() {
        return $this->get_config();
    }
    
    /**
     * Build form HTML with inline container
     * 
     * @param string $container_id Container element ID
     * 
     * @return string Complete form HTML
     */
    public function render_in_container( $container_id ) {
        $config = $this->get_config();
        
        ob_start();
        ?>
        <div id="<?php echo esc_attr( $container_id ); ?>"></div>
        <script>
            (function() {
                if (window.BKGTForm) {
                    var formConfig = <?php echo wp_json_encode( $config ); ?>;
                    var form = new BKGTForm(formConfig);
                    form.render('#<?php echo esc_js( $container_id ); ?>');
                    window.<?php echo esc_js( $config['id'] ); ?> = form;
                } else {
                    console.error('BKGTForm not available. Make sure bkgt-form.js is loaded.');
                }
            })();
        </script>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Validate form data against field configuration
     * 
     * @param array $data Data to validate
     * 
     * @return array|true True if valid, array of errors if invalid
     */
    public function validate( $data ) {
        $errors = array();
        
        foreach ( $this->fields as $field ) {
            $value = isset( $data[ $field['name'] ] ) ? $data[ $field['name'] ] : '';
            
            // Required field validation
            if ( isset( $field['required'] ) && $field['required'] && empty( $value ) ) {
                $errors[ $field['name'] ] = $field['errorRequired'] ?? 'Detta fält är obligatoriskt';
                continue;
            }
            
            // Skip further validation if field is empty and not required
            if ( empty( $value ) ) {
                continue;
            }
            
            // Type-specific validation
            switch ( $field['type'] ) {
                case 'email':
                    if ( ! is_email( $value ) ) {
                        $errors[ $field['name'] ] = $field['errorInvalid'] ?? 'Ogiltig e-postadress';
                    }
                    break;
                    
                case 'url':
                    if ( ! filter_var( $value, FILTER_VALIDATE_URL ) ) {
                        $errors[ $field['name'] ] = $field['errorInvalid'] ?? 'Ogiltig URL';
                    }
                    break;
                    
                case 'number':
                    if ( ! is_numeric( $value ) ) {
                        $errors[ $field['name'] ] = $field['errorInvalid'] ?? 'Måste vara ett nummer';
                    } elseif ( isset( $field['min'] ) && $value < $field['min'] ) {
                        $errors[ $field['name'] ] = sprintf( 'Måste vara minst %s', $field['min'] );
                    } elseif ( isset( $field['max'] ) && $value > $field['max'] ) {
                        $errors[ $field['name'] ] = sprintf( 'Får vara högst %s', $field['max'] );
                    }
                    break;
                    
                case 'date':
                    if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $value ) ) {
                        $errors[ $field['name'] ] = $field['errorInvalid'] ?? 'Ogiltigt datumformat (YYYY-MM-DD)';
                    }
                    break;
                    
                case 'tel':
                    $cleaned = preg_replace( '/\D/', '', $value );
                    if ( strlen( $cleaned ) < 6 ) {
                        $errors[ $field['name'] ] = $field['errorInvalid'] ?? 'Ogiltigt telefonnummer';
                    }
                    break;
            }
            
            // Min/max length validation
            if ( isset( $field['minLength'] ) && strlen( $value ) < $field['minLength'] ) {
                $errors[ $field['name'] ] = sprintf( 'Måste innehålla minst %d tecken', $field['minLength'] );
            }
            
            if ( isset( $field['maxLength'] ) && strlen( $value ) > $field['maxLength'] ) {
                $errors[ $field['name'] ] = sprintf( 'Får innehålla högst %d tecken', $field['maxLength'] );
            }
        }
        
        return empty( $errors ) ? true : $errors;
    }
    
    /**
     * Sanitize form data based on field types
     * 
     * @param array $data Data to sanitize
     * 
     * @return array Sanitized data
     */
    public function sanitize( $data ) {
        $sanitized = array();
        
        foreach ( $this->fields as $field ) {
            if ( ! isset( $data[ $field['name'] ] ) ) {
                continue;
            }
            
            $value = $data[ $field['name'] ];
            
            switch ( $field['type'] ) {
                case 'email':
                    $sanitized[ $field['name'] ] = sanitize_email( $value );
                    break;
                    
                case 'url':
                    $sanitized[ $field['name'] ] = esc_url( $value );
                    break;
                    
                case 'number':
                    $sanitized[ $field['name'] ] = is_numeric( $value ) ? (float) $value : 0;
                    break;
                    
                case 'textarea':
                    $sanitized[ $field['name'] ] = wp_kses_post( $value );
                    break;
                    
                case 'checkbox':
                    $sanitized[ $field['name'] ] = (bool) $value;
                    break;
                    
                default:
                    $sanitized[ $field['name'] ] = sanitize_text_field( $value );
                    break;
            }
        }
        
        return $sanitized;
    }
}

/**
 * Helper function to create a form builder
 * 
 * @param string $form_id Form ID
 * @param array  $options Form options
 * 
 * @return BKGT_Form_Builder Form builder instance
 */
function bkgt_form_builder( $form_id = null, $options = array() ) {
    return new BKGT_Form_Builder( $form_id, $options );
}
