<?php
/**
 * BKGT Document Management - Template System
 * Handles template-based document creation with variable support
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Template System Class
 */
class BKGT_DM_Template_System {

    /**
     * Available variables for templates
     */
    private $available_variables = array(
        // Player variables
        '{{SPELARE_NAMN}}' => 'player_name',
        '{{SPELARE_EFTERNAMN}}' => 'player_lastname',
        '{{SPELARE_FODELSEDATUM}}' => 'player_birthdate',
        '{{SPELARE_LAG}}' => 'player_team',
        '{{SPELARE_POSITION}}' => 'player_position',

        // Coach/Staff variables
        '{{TRÄNARE_NAMN}}' => 'coach_name',
        '{{TRÄNARE_EFTERNAMN}}' => 'coach_lastname',
        '{{TRÄNARE_LAG}}' => 'coach_team',

        // Document variables
        '{{UTFÄRDANDE_DATUM}}' => 'issue_date',
        '{{UTFÄRDANDE_ÅR}}' => 'issue_year',
        '{{DOKUMENT_TITEL}}' => 'document_title',
        '{{DOKUMENT_NUMMER}}' => 'document_number',

        // Club variables
        '{{KLUBB_NAMN}}' => 'club_name',
        '{{KLUBB_ADRESS}}' => 'club_address',
        '{{KLUBB_TELEFON}}' => 'club_phone',
        '{{KLUBB_EPOST}}' => 'club_email',

        // Equipment variables
        '{{UTRUSTNING_LISTA}}' => 'equipment_list',
        '{{UTRUSTNING_ÅTERLÄMNINGSDATUM}}' => 'equipment_return_date',

        // Offboarding variables
        '{{PERSON_NAMN}}' => 'person_name',
        '{{PERSON_EFTERNAMN}}' => 'person_lastname',
        '{{SLUTDATUM}}' => 'end_date',
        '{{AVSLUTANDE_ANSTÄLLNING}}' => 'termination_reason'
    );

    /**
     * Template categories
     */
    private $template_categories = array(
        'player' => 'Spelardokument',
        'coach' => 'Tränardokument',
        'equipment' => 'Utrustningsdokument',
        'offboarding' => 'Överlämningsdokument',
        'club' => 'Klubbdokument',
        'meeting' => 'Mötesdokument',
        'financial' => 'Ekonomidokument'
    );

    /**
     * Constructor
     */
    public function __construct() {
        $this->init();
    }

    /**
     * Initialize the template system
     */
    public function init() {
        // Create templates table on activation
        register_activation_hook('bkgt-document-management/bkgt-document-management.php', array($this, 'create_templates_table'));

        // Add AJAX handlers for template operations
        add_action('wp_ajax_bkgt_save_template', array($this, 'ajax_save_template'));
        add_action('wp_ajax_bkgt_load_template', array($this, 'ajax_load_template'));
        add_action('wp_ajax_bkgt_delete_template', array($this, 'ajax_delete_template'));
        add_action('wp_ajax_bkgt_create_from_template', array($this, 'ajax_create_from_template'));
        add_action('wp_ajax_bkgt_preview_template', array($this, 'ajax_preview_template'));
    }

    /**
     * Create templates database table
     */
    public function create_templates_table() {
        global $wpdb;

        $table_name = $wpdb->prefix . 'bkgt_document_templates';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id INT(11) NOT NULL AUTO_INCREMENT,
            template_name VARCHAR(255) NOT NULL,
            template_slug VARCHAR(100) NOT NULL UNIQUE,
            category VARCHAR(50) NOT NULL,
            description TEXT,
            template_content LONGTEXT NOT NULL,
            variables_used TEXT,
            created_by INT(11) NOT NULL,
            created_date DATETIME DEFAULT CURRENT_TIMESTAMP,
            modified_date DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            is_active TINYINT(1) DEFAULT 1,
            PRIMARY KEY (id),
            KEY template_slug (template_slug),
            KEY category (category)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Get available variables
     */
    public function get_available_variables() {
        return $this->available_variables;
    }

    /**
     * Get template categories
     */
    public function get_template_categories() {
        return $this->template_categories;
    }

    /**
     * Save a template
     */
    public function save_template($data) {
        global $wpdb;

        $table_name = $wpdb->prefix . 'bkgt_document_templates';

        // Validate required fields
        if (empty($data['template_name']) || empty($data['template_content'])) {
            return new WP_Error('missing_fields', 'Template name and content are required.');
        }

        // Generate slug if not provided
        if (empty($data['template_slug'])) {
            $data['template_slug'] = sanitize_title($data['template_name']);
        }

        // Extract variables used in template
        $variables_used = $this->extract_variables_from_content($data['template_content']);

        $template_data = array(
            'template_name' => sanitize_text_field($data['template_name']),
            'template_slug' => sanitize_title($data['template_slug']),
            'category' => sanitize_text_field($data['category'] ?? 'general'),
            'description' => sanitize_textarea_field($data['description'] ?? ''),
            'template_content' => wp_kses_post($data['template_content']),
            'variables_used' => json_encode($variables_used),
            'created_by' => get_current_user_id(),
            'is_active' => isset($data['is_active']) ? 1 : 1
        );

        if (isset($data['id']) && !empty($data['id'])) {
            // Update existing template
            $template_data['modified_date'] = current_time('mysql');
            $result = $wpdb->update($table_name, $template_data, array('id' => intval($data['id'])));
        } else {
            // Insert new template
            $result = $wpdb->insert($table_name, $template_data);
        }

        if ($result === false) {
            return new WP_Error('db_error', 'Failed to save template to database.');
        }

        return isset($data['id']) ? intval($data['id']) : $wpdb->insert_id;
    }

    /**
     * Load a template by ID or slug
     */
    public function load_template($identifier) {
        global $wpdb;

        $table_name = $wpdb->prefix . 'bkgt_document_templates';

        if (is_numeric($identifier)) {
            $where = array('id' => intval($identifier));
        } else {
            $where = array('template_slug' => sanitize_title($identifier));
        }

        $template = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $table_name WHERE %s = %s AND is_active = 1",
                key($where),
                current($where)
            ),
            ARRAY_A
        );

        if (!$template) {
            return false;
        }

        // Decode variables
        $template['variables_used'] = json_decode($template['variables_used'], true);

        return $template;
    }

    /**
     * Get all templates
     */
    public function get_templates($category = null, $active_only = true) {
        global $wpdb;

        $table_name = $wpdb->prefix . 'bkgt_document_templates';

        $where = array();
        if ($category) {
            $where[] = $wpdb->prepare('category = %s', $category);
        }
        if ($active_only) {
            $where[] = 'is_active = 1';
        }

        $where_clause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        $templates = $wpdb->get_results(
            "SELECT * FROM $table_name $where_clause ORDER BY template_name ASC",
            ARRAY_A
        );

        // Decode variables for each template
        foreach ($templates as &$template) {
            $template['variables_used'] = json_decode($template['variables_used'], true);
        }

        return $templates;
    }

    /**
     * Delete a template
     */
    public function delete_template($template_id) {
        global $wpdb;

        $table_name = $wpdb->prefix . 'bkgt_document_templates';

        $result = $wpdb->update(
            $table_name,
            array('is_active' => 0),
            array('id' => intval($template_id)),
            array('%d'),
            array('%d')
        );

        return $result !== false;
    }

    /**
     * Create document from template with variable replacement
     */
    public function create_from_template($template_id, $variables = array()) {
        $template = $this->load_template($template_id);

        if (!$template) {
            return new WP_Error('template_not_found', 'Template not found.');
        }

        $content = $template['template_content'];

        // Replace variables
        foreach ($variables as $key => $value) {
            $placeholder = '{{' . strtoupper($key) . '}}';
            $content = str_replace($placeholder, $value, $content);
        }

        // Replace any remaining variables with empty strings or placeholders
        $content = preg_replace('/\{\{[A-Z_]+\}\}/', '', $content);

        return array(
            'title' => $template['template_name'],
            'content' => $content,
            'category' => $template['category'],
            'template_id' => $template['id']
        );
    }

    /**
     * Extract variables from template content
     */
    private function extract_variables_from_content($content) {
        $variables = array();

        foreach ($this->available_variables as $placeholder => $variable) {
            if (strpos($content, $placeholder) !== false) {
                $variables[] = $variable;
            }
        }

        return array_unique($variables);
    }

    /**
     * Get variable value based on context
     */
    public function get_variable_value($variable, $context = array()) {
        $value = '';

        switch ($variable) {
            case 'issue_date':
                $value = date_i18n('Y-m-d');
                break;
            case 'issue_year':
                $value = date_i18n('Y');
                break;
            case 'club_name':
                $value = get_bloginfo('name');
                break;
            case 'club_address':
                $value = get_option('bkgt_club_address', '');
                break;
            case 'club_phone':
                $value = get_option('bkgt_club_phone', '');
                break;
            case 'club_email':
                $value = get_option('bkgt_club_email', '');
                break;
            default:
                // Check context for dynamic values
                if (isset($context[$variable])) {
                    $value = $context[$variable];
                }
                break;
        }

        return $value;
    }

    /**
     * AJAX: Save template
     */
    public function ajax_save_template() {
        // Verify nonce and capabilities
        if (!wp_verify_nonce($_POST['nonce'], 'bkgt_template_nonce') ||
            !current_user_can('bkgt_manage_documents')) {
            wp_die('Security check failed');
        }

        $data = array(
            'template_name' => sanitize_text_field($_POST['template_name']),
            'template_slug' => sanitize_title($_POST['template_slug'] ?? ''),
            'category' => sanitize_text_field($_POST['category']),
            'description' => sanitize_textarea_field($_POST['description']),
            'template_content' => wp_kses_post($_POST['template_content']),
            'is_active' => isset($_POST['is_active']) ? 1 : 0
        );

        if (isset($_POST['template_id'])) {
            $data['id'] = intval($_POST['template_id']);
        }

        $result = $this->save_template($data);

        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        } else {
            wp_send_json_success(array('template_id' => $result));
        }
    }

    /**
     * AJAX: Load template
     */
    public function ajax_load_template() {
        // Verify nonce and capabilities
        if (!wp_verify_nonce($_POST['nonce'], 'bkgt_template_nonce') ||
            !current_user_can('bkgt_view_documents')) {
            wp_die('Security check failed');
        }

        $template_id = intval($_POST['template_id']);
        $template = $this->load_template($template_id);

        if (!$template) {
            wp_send_json_error('Template not found');
        } else {
            wp_send_json_success($template);
        }
    }

    /**
     * AJAX: Delete template
     */
    public function ajax_delete_template() {
        // Verify nonce and capabilities
        if (!wp_verify_nonce($_POST['nonce'], 'bkgt_template_nonce') ||
            !current_user_can('bkgt_manage_documents')) {
            wp_die('Security check failed');
        }

        $template_id = intval($_POST['template_id']);
        $result = $this->delete_template($template_id);

        if ($result) {
            wp_send_json_success();
        } else {
            wp_send_json_error('Failed to delete template');
        }
    }

    /**
     * AJAX: Create from template
     */
    public function ajax_create_from_template() {
        // Verify nonce and capabilities
        if (!wp_verify_nonce($_POST['nonce'], 'bkgt_template_nonce') ||
            !current_user_can('bkgt_create_documents')) {
            wp_die('Security check failed');
        }

        $template_id = intval($_POST['template_id']);
        $variables = isset($_POST['variables']) ? $_POST['variables'] : array();

        $result = $this->create_from_template($template_id, $variables);

        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        } else {
            wp_send_json_success($result);
        }
    }

    /**
     * AJAX: Preview template
     */
    public function ajax_preview_template() {
        // Verify nonce and capabilities
        if (!wp_verify_nonce($_POST['nonce'], 'bkgt_template_nonce') ||
            !current_user_can('bkgt_view_documents')) {
            wp_die('Security check failed');
        }

        $template_id = intval($_POST['template_id']);
        $variables = isset($_POST['variables']) ? $_POST['variables'] : array();

        $result = $this->create_from_template($template_id, $variables);

        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        } else {
            wp_send_json_success(array('preview' => $result['content']));
        }
    }
}

// Initialize template system
new BKGT_DM_Template_System();