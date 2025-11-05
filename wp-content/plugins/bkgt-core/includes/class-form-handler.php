<?php
/**
 * BKGT_Form_Handler - Unified Form Processing System
 * 
 * Provides centralized form processing with standardized validation, sanitization, 
 * error handling, and display. Ensures consistent UX and data quality across all forms.
 * 
 * @package    BKGT_Core
 * @subpackage Forms
 * @version    1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class BKGT_Form_Handler {
    
    /**
     * Process form submission
     * 
     * Handles complete form lifecycle: nonce verification, permission checks,
     * sanitization, validation, and error handling.
     * 
     * @param  array  $form_config Form configuration
     * @return array              Array with 'success', 'data', and 'errors'
     */
    public static function process($form_config) {
        $result = array(
            'success' => false,
            'data' => array(),
            'errors' => array(),
            'message' => '',
        );
        
        // Extract config
        $nonce_action = $form_config['nonce_action'] ?? '';
        $nonce_field = $form_config['nonce_field'] ?? '_wpnonce';
        $capability = $form_config['capability'] ?? 'edit_posts';
        $entity_type = $form_config['entity_type'] ?? '';
        $fields = $form_config['fields'] ?? array();
        $entity_id = $form_config['entity_id'] ?? null;
        $on_success = $form_config['on_success'] ?? null;
        $on_error = $form_config['on_error'] ?? null;
        
        // Verify nonce
        if (!self::verify_nonce($nonce_action, $nonce_field)) {
            $result['errors']['nonce'] = __('Säkerhetskontroll misslyckades. Försök igen.', 'bkgt-core');
            
            bkgt_log('warning', 'Form nonce verification failed', array(
                'nonce_action' => $nonce_action,
                'user_id' => get_current_user_id(),
            ));
            
            return $result;
        }
        
        // Verify capability
        if (!current_user_can($capability)) {
            $result['errors']['permission'] = __('Du har inte behörighet att utföra denna åtgärd.', 'bkgt-core');
            
            bkgt_log('warning', 'Form permission denied', array(
                'nonce_action' => $nonce_action,
                'capability' => $capability,
                'user_id' => get_current_user_id(),
            ));
            
            return $result;
        }
        
        // Extract and sanitize form data
        $raw_data = self::extract_form_data($fields);
        $process_result = BKGT_Sanitizer::process($raw_data, $entity_type, $entity_id);
        
        $result['data'] = $process_result['data'];
        $result['errors'] = $process_result['errors'];
        
        // Check for validation errors
        if (BKGT_Validator::has_errors($result['errors'])) {
            bkgt_log('info', 'Form validation failed', array(
                'entity_type' => $entity_type,
                'error_count' => count(array_filter($result['errors'])),
                'user_id' => get_current_user_id(),
            ));
            
            if (is_callable($on_error)) {
                call_user_func($on_error, $result);
            }
            
            return $result;
        }
        
        // Call success handler
        if (is_callable($on_success)) {
            $success_result = call_user_func($on_success, $result['data'], $entity_id);
            
            if (is_wp_error($success_result)) {
                $result['errors']['general'] = $success_result->get_error_message();
                return $result;
            }
            
            if (is_array($success_result)) {
                $result = array_merge($result, $success_result);
            }
        }
        
        $result['success'] = true;
        
        bkgt_log('info', 'Form processed successfully', array(
            'entity_type' => $entity_type,
            'user_id' => get_current_user_id(),
        ));
        
        return $result;
    }
    
    /**
     * Verify form nonce
     * 
     * @param  string $action     Nonce action
     * @param  string $field_name Field name
     * @return bool               True if valid
     */
    private static function verify_nonce($action, $field_name) {
        if (empty($action)) {
            return true; // Nonce check disabled
        }
        
        if (!isset($_POST[$field_name])) {
            return false;
        }
        
        return wp_verify_nonce($_POST[$field_name], $action) !== false;
    }
    
    /**
     * Extract form data from POST
     * 
     * @param  array $fields Field names to extract
     * @return array         Extracted data
     */
    private static function extract_form_data($fields) {
        $data = array();
        
        foreach ($fields as $field) {
            if (isset($_POST[$field])) {
                $data[$field] = $_POST[$field];
            }
        }
        
        return $data;
    }
    
    /**
     * Render form errors
     * 
     * Displays validation errors in a consistent, accessible format
     * 
     * @param  array $errors Errors array
     * @param  array $attrs  Optional HTML attributes
     */
    public static function render_errors($errors, $attrs = array()) {
        if (empty($errors) || !BKGT_Validator::has_errors($errors)) {
            return;
        }
        
        $class = isset($attrs['class']) ? $attrs['class'] : 'bkgt-form-errors';
        $role = isset($attrs['role']) ? $attrs['role'] : 'alert';
        
        ?>
        <div class="<?php echo esc_attr($class); ?>" role="<?php echo esc_attr($role); ?>">
            <h3 class="bkgt-form-errors-title">
                <?php esc_html_e('Formuläret innehöll fel:', 'bkgt-core'); ?>
            </h3>
            <ul class="bkgt-form-errors-list">
                <?php foreach ($errors as $field => $error_msgs): ?>
                    <?php if (is_array($error_msgs)): ?>
                        <?php foreach ($error_msgs as $error): ?>
                            <li class="bkgt-form-error" data-field="<?php echo esc_attr($field); ?>">
                                <?php echo esc_html($error); ?>
                            </li>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <li class="bkgt-form-error" data-field="<?php echo esc_attr($field); ?>">
                            <?php echo esc_html($error_msgs); ?>
                        </li>
                    <?php endif; ?>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php
    }
    
    /**
     * Render field error (inline)
     * 
     * @param  string $field_name Field name
     * @param  array  $errors     Errors array
     * @param  array  $attrs      HTML attributes
     */
    public static function render_field_error($field_name, $errors, $attrs = array()) {
        if (!isset($errors[$field_name])) {
            return;
        }
        
        $class = isset($attrs['class']) ? $attrs['class'] : 'bkgt-field-error';
        $field_error = $errors[$field_name];
        
        if (is_array($field_error)) {
            $field_error = implode(' ', $field_error);
        }
        
        ?>
        <span class="<?php echo esc_attr($class); ?>" role="alert">
            <?php echo esc_html($field_error); ?>
        </span>
        <?php
    }
    
    /**
     * Render success message
     * 
     * @param  string $message Message text
     * @param  array  $attrs   HTML attributes
     */
    public static function render_success($message, $attrs = array()) {
        $class = isset($attrs['class']) ? $attrs['class'] : 'bkgt-form-success';
        $role = isset($attrs['role']) ? $attrs['role'] : 'status';
        
        ?>
        <div class="<?php echo esc_attr($class); ?>" role="<?php echo esc_attr($role); ?>">
            <span class="dashicons dashicons-yes"></span>
            <?php echo esc_html($message); ?>
        </div>
        <?php
    }
    
    /**
     * Get field error class
     * 
     * Returns class for fields with errors for styling
     * 
     * @param  string $field_name Field name
     * @param  array  $errors     Errors array
     * @return string             CSS class
     */
    public static function get_field_error_class($field_name, $errors) {
        return isset($errors[$field_name]) ? 'bkgt-field-with-error' : '';
    }
    
    /**
     * Render form field with error display
     * 
     * @param  array $config Field configuration
     * @param  array $errors Errors array
     */
    public static function render_field($config, $errors = array()) {
        $field_name = $config['name'] ?? '';
        $label = $config['label'] ?? '';
        $type = $config['type'] ?? 'text';
        $value = $config['value'] ?? '';
        $required = $config['required'] ?? false;
        $help_text = $config['help_text'] ?? '';
        
        $error_class = self::get_field_error_class($field_name, $errors);
        
        ?>
        <div class="bkgt-form-field <?php echo esc_attr($error_class); ?>">
            <?php if ($label): ?>
                <label for="<?php echo esc_attr($field_name); ?>">
                    <?php echo esc_html($label); ?>
                    <?php if ($required): ?>
                        <span class="bkgt-required-indicator" title="<?php esc_attr_e('Obligatoriskt', 'bkgt-core'); ?>">*</span>
                    <?php endif; ?>
                </label>
            <?php endif; ?>
            
            <?php if ($type === 'textarea'): ?>
                <textarea 
                    id="<?php echo esc_attr($field_name); ?>"
                    name="<?php echo esc_attr($field_name); ?>"
                    rows="<?php echo isset($config['rows']) ? intval($config['rows']) : 4; ?>"
                    class="bkgt-form-input"
                    <?php if ($required): ?>required<?php endif; ?>
                ><?php echo esc_textarea($value); ?></textarea>
            <?php elseif ($type === 'select'): ?>
                <select 
                    id="<?php echo esc_attr($field_name); ?>"
                    name="<?php echo esc_attr($field_name); ?>"
                    class="bkgt-form-input"
                    <?php if ($required): ?>required<?php endif; ?>
                >
                    <option value=""><?php esc_html_e('-- Välj --', 'bkgt-core'); ?></option>
                    <?php foreach ($config['options'] ?? array() as $option_value => $option_label): ?>
                        <option value="<?php echo esc_attr($option_value); ?>" <?php selected($value, $option_value); ?>>
                            <?php echo esc_html($option_label); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            <?php else: ?>
                <input 
                    type="<?php echo esc_attr($type); ?>"
                    id="<?php echo esc_attr($field_name); ?>"
                    name="<?php echo esc_attr($field_name); ?>"
                    value="<?php echo esc_attr($value); ?>"
                    class="bkgt-form-input"
                    <?php if ($required): ?>required<?php endif; ?>
                    <?php if (isset($config['placeholder'])): ?>placeholder="<?php echo esc_attr($config['placeholder']); ?>"<?php endif; ?>
                />
            <?php endif; ?>
            
            <?php if ($help_text): ?>
                <p class="bkgt-help-text"><?php echo wp_kses_post($help_text); ?></p>
            <?php endif; ?>
            
            <?php self::render_field_error($field_name, $errors); ?>
        </div>
        <?php
    }
    
    /**
     * Create form nonce field
     * 
     * @param  string $action Action name
     * @param  string $field  Field name (default: _wpnonce)
     */
    public static function nonce_field($action, $field = '_wpnonce') {
        wp_nonce_field($action, $field);
    }
}
