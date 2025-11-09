<?php
/**
 * Smart Template Application Class
 *
 * Handles intelligent template suggestions and context-aware variable population
 *
 * @package BKGT_Document_Management
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class BKGT_Smart_Template_Application {

    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_ajax_bkgt_get_template_suggestions', array($this, 'ajax_get_template_suggestions'));
        add_action('wp_ajax_bkgt_apply_template', array($this, 'ajax_apply_template'));
        add_action('wp_ajax_bkgt_bulk_apply_template', array($this, 'ajax_bulk_apply_template'));
        add_action('wp_ajax_bkgt_get_context_variables', array($this, 'ajax_get_context_variables'));
    }

    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_submenu_page(
            'bkgt-documents',
            __('Smarta mallar', 'bkgt-document-management'),
            __('Smarta mallar', 'bkgt-document-management'),
            'manage_options',
            'bkgt-smart-templates',
            array($this, 'admin_page')
        );
    }

    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'bkgt-smart-templates') === false) {
            return;
        }

        wp_enqueue_script('jquery');
        wp_enqueue_script('jquery-ui-autocomplete');

        wp_enqueue_script(
            'bkgt-smart-templates-js',
            plugins_url('admin/js/smart-templates.js', dirname(__FILE__)),
            array('jquery'),
            '1.0.0',
            true
        );

        wp_enqueue_style(
            'bkgt-smart-templates-css',
            plugins_url('admin/css/smart-templates.css', dirname(__FILE__)),
            array(),
            '1.0.0'
        );

        wp_localize_script('bkgt-smart-templates-js', 'bkgt_smart_templates', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('bkgt-smart-templates-nonce'),
            'strings' => array(
                'select_template' => __('Välj mall', 'bkgt-document-management'),
                'apply_template' => __('Applicera mall', 'bkgt-document-management'),
                'preview_document' => __('Förhandsgranska dokument', 'bkgt-document-management'),
                'no_suggestions' => __('Inga mallförslag hittades', 'bkgt-document-management'),
                'loading' => __('Laddar...', 'bkgt-document-management'),
                'error' => __('Ett fel uppstod', 'bkgt-document-management'),
            )
        ));
    }

    /**
     * Main admin page
     */
    public function admin_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Smarta mallar', 'bkgt-document-management'); ?></h1>

            <div class="bkgt-smart-templates">
                <!-- Context Selection -->
                <div class="bkgt-context-selector">
                    <h2><?php _e('Välj sammanhang', 'bkgt-document-management'); ?></h2>
                    <div class="bkgt-context-tabs">
                        <button class="bkgt-context-tab active" data-context="player">
                            <i class="dashicons dashicons-admin-users"></i>
                            <?php _e('Spelare', 'bkgt-document-management'); ?>
                        </button>
                        <button class="bkgt-context-tab" data-context="team">
                            <i class="dashicons dashicons-groups"></i>
                            <?php _e('Lag', 'bkgt-document-management'); ?>
                        </button>
                        <button class="bkgt-context-tab" data-context="equipment">
                            <i class="dashicons dashicons-products"></i>
                            <?php _e('Utrustning', 'bkgt-document-management'); ?>
                        </button>
                        <button class="bkgt-context-tab" data-context="meeting">
                            <i class="dashicons dashicons-calendar"></i>
                            <?php _e('Möte', 'bkgt-document-management'); ?>
                        </button>
                    </div>

                    <div class="bkgt-context-content">
                        <!-- Player Context -->
                        <div class="bkgt-context-panel active" data-context="player">
                            <div class="bkgt-search-section">
                                <label for="bkgt-player-search"><?php _e('Sök spelare:', 'bkgt-document-management'); ?></label>
                                <input type="text" id="bkgt-player-search" placeholder="<?php _e('Ange spelarnamn...', 'bkgt-document-management'); ?>" class="regular-text">
                                <div id="bkgt-player-results" class="bkgt-search-results"></div>
                            </div>
                        </div>

                        <!-- Team Context -->
                        <div class="bkgt-context-panel" data-context="team">
                            <div class="bkgt-search-section">
                                <label for="bkgt-team-select"><?php _e('Välj lag:', 'bkgt-document-management'); ?></label>
                                <select id="bkgt-team-select" class="regular-text">
                                    <option value=""><?php _e('Välj lag...', 'bkgt-document-management'); ?></option>
                                    <option value="damlag"><?php _e('Damlag', 'bkgt-document-management'); ?></option>
                                    <option value="herrlag"><?php _e('Herrlag', 'bkgt-document-management'); ?></option>
                                    <option value="u17"><?php _e('U17', 'bkgt-document-management'); ?></option>
                                    <option value="u15"><?php _e('U15', 'bkgt-document-management'); ?></option>
                                </select>
                            </div>
                        </div>

                        <!-- Equipment Context -->
                        <div class="bkgt-context-panel" data-context="equipment">
                            <div class="bkgt-search-section">
                                <label for="bkgt-equipment-search"><?php _e('Sök utrustning:', 'bkgt-document-management'); ?></label>
                                <input type="text" id="bkgt-equipment-search" placeholder="<?php _e('Ange utrustnings-ID eller typ...', 'bkgt-document-management'); ?>" class="regular-text">
                                <div id="bkgt-equipment-results" class="bkgt-search-results"></div>
                            </div>
                        </div>

                        <!-- Meeting Context -->
                        <div class="bkgt-context-panel" data-context="meeting">
                            <div class="bkgt-form-section">
                                <div class="bkgt-form-row">
                                    <label for="bkgt-meeting-type"><?php _e('Mötestyp:', 'bkgt-document-management'); ?></label>
                                    <select id="bkgt-meeting-type" class="regular-text">
                                        <option value="styrelse"><?php _e('Styrelsemöte', 'bkgt-document-management'); ?></option>
                                        <option value="lagledare"><?php _e('Lagledarmöte', 'bkgt-document-management'); ?></option>
                                        <option value="tranare"><?php _e('Tränarmöte', 'bkgt-document-management'); ?></option>
                                        <option value="foraldrar"><?php _e('Föräldramöte', 'bkgt-document-management'); ?></option>
                                    </select>
                                </div>
                                <div class="bkgt-form-row">
                                    <label for="bkgt-meeting-date"><?php _e('Datum:', 'bkgt-document-management'); ?></label>
                                    <input type="date" id="bkgt-meeting-date" class="regular-text">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Template Suggestions -->
                <div class="bkgt-template-suggestions">
                    <h2><?php _e('Mallförslag', 'bkgt-document-management'); ?></h2>
                    <div id="bkgt-suggestions-container" class="bkgt-suggestions-container">
                        <div class="bkgt-no-suggestions">
                            <i class="dashicons dashicons-lightbulb"></i>
                            <p><?php _e('Välj ett sammanhang ovan för att få mallförslag', 'bkgt-document-management'); ?></p>
                        </div>
                    </div>
                </div>

                <!-- Bulk Application -->
                <div class="bkgt-bulk-application" style="display: none;">
                    <h2><?php _e('Massapplicering', 'bkgt-document-management'); ?></h2>
                    <div class="bkgt-bulk-controls">
                        <button id="bkgt-select-all" class="button"><?php _e('Välj alla', 'bkgt-document-management'); ?></button>
                        <button id="bkgt-apply-selected" class="button button-primary"><?php _e('Applicera valda', 'bkgt-document-management'); ?></button>
                        <span id="bkgt-selection-count"><?php _e('0 valda', 'bkgt-document-management'); ?></span>
                    </div>
                    <div id="bkgt-bulk-results" class="bkgt-bulk-results"></div>
                </div>
            </div>
        </div>

        <!-- Template Preview Modal -->
        <div id="bkgt-template-preview-modal" class="bkgt-modal">
            <div class="bkgt-modal-content">
                <div class="bkgt-modal-header">
                    <h2 id="bkgt-preview-title"><?php _e('Förhandsgranskning', 'bkgt-document-management'); ?></h2>
                    <button class="bkgt-modal-close">&times;</button>
                </div>
                <div class="bkgt-modal-body">
                    <div id="bkgt-preview-content"></div>
                    <div class="bkgt-preview-actions">
                        <button id="bkgt-confirm-apply" class="button button-primary"><?php _e('Applicera mall', 'bkgt-document-management'); ?></button>
                        <button id="bkgt-edit-variables" class="button"><?php _e('Redigera variabler', 'bkgt-document-management'); ?></button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Variable Editor Modal -->
        <div id="bkgt-variable-editor-modal" class="bkgt-modal">
            <div class="bkgt-modal-content large">
                <div class="bkgt-modal-header">
                    <h2><?php _e('Redigera variabler', 'bkgt-document-management'); ?></h2>
                    <button class="bkgt-modal-close">&times;</button>
                </div>
                <div class="bkgt-modal-body">
                    <div id="bkgt-variable-editor"></div>
                    <div class="bkgt-editor-actions">
                        <button id="bkgt-save-variables" class="button button-primary"><?php _e('Spara och applicera', 'bkgt-document-management'); ?></button>
                        <button id="bkgt-cancel-edit" class="button"><?php _e('Avbryt', 'bkgt-document-management'); ?></button>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * AJAX: Get template suggestions based on context
     */
    public function ajax_get_template_suggestions() {
        check_ajax_referer('bkgt-smart-templates-nonce', 'nonce');

        $context_type = sanitize_text_field($_POST['context_type']);
        $context_data = $_POST['context_data'];

        $suggestions = $this->get_template_suggestions($context_type, $context_data);

        wp_send_json_success(array(
            'suggestions' => $suggestions,
            'context_type' => $context_type
        ));
    }

    /**
     * Get template suggestions based on context
     */
    private function get_template_suggestions($context_type, $context_data) {
        $suggestions = array();

        // Get all templates
        $templates = get_posts(array(
            'post_type' => 'bkgt_template',
            'posts_per_page' => -1,
            'post_status' => 'publish'
        ));

        foreach ($templates as $template) {
            $score = $this->calculate_template_score($template, $context_type, $context_data);

            if ($score > 0) {
                $suggestions[] = array(
                    'id' => $template->ID,
                    'title' => $template->post_title,
                    'description' => $template->post_excerpt,
                    'score' => $score,
                    'preview' => $this->generate_template_preview($template, $context_data),
                    'variables' => $this->extract_template_variables($template)
                );
            }
        }

        // Sort by score (highest first)
        usort($suggestions, function($a, $b) {
            return $b['score'] - $a['score'];
        });

        return array_slice($suggestions, 0, 5); // Return top 5 suggestions
    }

    /**
     * Calculate how well a template matches the given context
     */
    private function calculate_template_score($template, $context_type, $context_data) {
        $score = 0;
        $template_content = json_decode($template->post_content, true);

        if (!$template_content || !isset($template_content['components'])) {
            return 0;
        }

        $template_text = $this->extract_text_from_components($template_content['components']);

        // Context-specific scoring
        switch ($context_type) {
            case 'player':
                if (stripos($template_text, 'spelare') !== false ||
                    stripos($template_text, 'player') !== false ||
                    stripos($template->post_title, 'spelare') !== false) {
                    $score += 50;
                }
                if (stripos($template_text, 'utrustning') !== false ||
                    stripos($template_text, 'equipment') !== false) {
                    $score += 30;
                }
                break;

            case 'team':
                if (stripos($template_text, 'lag') !== false ||
                    stripos($template_text, 'team') !== false ||
                    stripos($template->post_title, 'lag') !== false) {
                    $score += 50;
                }
                if (stripos($template_text, 'möte') !== false ||
                    stripos($template_text, 'meeting') !== false) {
                    $score += 30;
                }
                break;

            case 'equipment':
                if (stripos($template_text, 'utrustning') !== false ||
                    stripos($template_text, 'equipment') !== false ||
                    stripos($template->post_title, 'utrustning') !== false) {
                    $score += 50;
                }
                if (stripos($template_text, 'kvitto') !== false ||
                    stripos($template_text, 'receipt') !== false) {
                    $score += 40;
                }
                break;

            case 'meeting':
                $meeting_type = isset($context_data['type']) ? $context_data['type'] : '';
                if (stripos($template_text, 'möte') !== false ||
                    stripos($template_text, 'meeting') !== false ||
                    stripos($template->post_title, 'möte') !== false) {
                    $score += 50;
                }
                if (stripos($template_text, $meeting_type) !== false) {
                    $score += 30;
                }
                break;
        }

        // Bonus for templates with variables (more flexible)
        $variables = $this->extract_template_variables($template);
        $score += count($variables) * 5;

        return $score;
    }

    /**
     * Extract text content from template components
     */
    private function extract_text_from_components($components) {
        $text = '';

        foreach ($components as $component) {
            switch ($component['type']) {
                case 'heading':
                    $text .= $component['text'] . ' ';
                    break;
                case 'text':
                    $text .= $component['content'] . ' ';
                    break;
                case 'variable':
                    $text .= $component['name'] . ' ';
                    break;
                case 'list':
                    $text .= implode(' ', $component['items']) . ' ';
                    break;
            }
        }

        return strtolower($text);
    }

    /**
     * Extract variables from template
     */
    private function extract_template_variables($template) {
        $variables = array();
        $template_content = json_decode($template->post_content, true);

        if (!$template_content || !isset($template_content['components'])) {
            return $variables;
        }

        foreach ($template_content['components'] as $component) {
            if ($component['type'] === 'variable') {
                $variables[] = array(
                    'name' => $component['name'],
                    'description' => isset($component['description']) ? $component['description'] : '',
                    'component_id' => $component['id']
                );
            }
        }

        return $variables;
    }

    /**
     * Generate template preview with populated variables
     */
    private function generate_template_preview($template, $context_data) {
        $template_content = json_decode($template->post_content, true);

        if (!$template_content || !isset($template_content['components'])) {
            return '';
        }

        $populated_variables = $this->populate_variables($template, $context_data);
        $preview = '';

        foreach ($template_content['components'] as $component) {
            $preview .= $this->render_component_preview($component, $populated_variables);
        }

        return $preview;
    }

    /**
     * Populate template variables based on context
     */
    private function populate_variables($template, $context_data) {
        $variables = array();
        $template_vars = $this->extract_template_variables($template);

        foreach ($template_vars as $var) {
            $var_name = $var['name'];
            $variables[$var_name] = $this->get_variable_value($var_name, $context_data);
        }

        return $variables;
    }

    /**
     * Get variable value based on context
     */
    private function get_variable_value($var_name, $context_data) {
        // Common variables
        $common_vars = array(
            '{{DATUM}}' => date('Y-m-d'),
            '{{TID}}' => date('H:i'),
            '{{AR}}' => date('Y'),
            '{{MANAD}}' => date('m'),
            '{{DAG}}' => date('d'),
            '{{ANVANDARNAMN}}' => wp_get_current_user()->display_name,
        );

        if (isset($common_vars[$var_name])) {
            return $common_vars[$var_name];
        }

        // Context-specific variables
        if (isset($context_data['type'])) {
            switch ($context_data['type']) {
                case 'player':
                    return $this->get_player_variable_value($var_name, $context_data);
                case 'team':
                    return $this->get_team_variable_value($var_name, $context_data);
                case 'equipment':
                    return $this->get_equipment_variable_value($var_name, $context_data);
                case 'meeting':
                    return $this->get_meeting_variable_value($var_name, $context_data);
            }
        }

        return '[' . $var_name . ']';
    }

    /**
     * Get player-specific variable values
     */
    private function get_player_variable_value($var_name, $context_data) {
        $player_vars = array(
            '{{SPELARE_NAMN}}' => isset($context_data['name']) ? $context_data['name'] : 'Spelarens namn',
            '{{SPELARE_EPOST}}' => isset($context_data['email']) ? $context_data['email'] : 'spelare@exempel.se',
            '{{SPELARE_TELEFON}}' => isset($context_data['phone']) ? $context_data['phone'] : '070-123 45 67',
            '{{SPELARE_LAG}}' => isset($context_data['team']) ? $context_data['team'] : 'Lag',
            '{{SPELARE_POSITION}}' => isset($context_data['position']) ? $context_data['position'] : 'Position',
        );

        return isset($player_vars[$var_name]) ? $player_vars[$var_name] : '[' . $var_name . ']';
    }

    /**
     * Get team-specific variable values
     */
    private function get_team_variable_value($var_name, $context_data) {
        $team_vars = array(
            '{{LAG_NAMN}}' => isset($context_data['name']) ? $context_data['name'] : 'Lagnamn',
            '{{LAG_TRANARE}}' => isset($context_data['coach']) ? $context_data['coach'] : 'Tränare',
            '{{LAG_LEDare}}' => isset($context_data['manager']) ? $context_data['manager'] : 'Lagledare',
            '{{LAG_ANTAL_SPELARE}}' => isset($context_data['player_count']) ? $context_data['player_count'] : '0',
        );

        return isset($team_vars[$var_name]) ? $team_vars[$var_name] : '[' . $var_name . ']';
    }

    /**
     * Get equipment-specific variable values
     */
    private function get_equipment_variable_value($var_name, $context_data) {
        $equipment_vars = array(
            '{{UTRUSTNING_ID}}' => isset($context_data['id']) ? $context_data['id'] : 'ID',
            '{{UTRUSTNING_TYP}}' => isset($context_data['type']) ? $context_data['type'] : 'Typ',
            '{{UTRUSTNING_TILLVERKARE}}' => isset($context_data['manufacturer']) ? $context_data['manufacturer'] : 'Tillverkare',
            '{{UTRUSTNING_MODELL}}' => isset($context_data['model']) ? $context_data['model'] : 'Modell',
            '{{UTRUSTNING_SKICK}}' => isset($context_data['condition']) ? $context_data['condition'] : 'Skick',
            '{{UTRUSTNING_TILLDELAD_TILL}}' => isset($context_data['assigned_to']) ? $context_data['assigned_to'] : 'Tilldelad till',
        );

        return isset($equipment_vars[$var_name]) ? $equipment_vars[$var_name] : '[' . $var_name . ']';
    }

    /**
     * Get meeting-specific variable values
     */
    private function get_meeting_variable_value($var_name, $context_data) {
        $meeting_vars = array(
            '{{MOTE_TYP}}' => isset($context_data['type']) ? $context_data['type'] : 'Mötestyp',
            '{{MOTE_DATUM}}' => isset($context_data['date']) ? $context_data['date'] : date('Y-m-d'),
            '{{MOTE_TID}}' => isset($context_data['time']) ? $context_data['time'] : 'Tid',
            '{{MOTE_PLATS}}' => isset($context_data['location']) ? $context_data['location'] : 'Plats',
            '{{MOTE_AGENDA}}' => isset($context_data['agenda']) ? $context_data['agenda'] : 'Agenda',
        );

        return isset($meeting_vars[$var_name]) ? $meeting_vars[$var_name] : '[' . $var_name . ']';
    }

    /**
     * Render component preview
     */
    private function render_component_preview($component, $variables) {
        switch ($component['type']) {
            case 'heading':
                $level = isset($component['level']) ? $component['level'] : 'h3';
                $text = isset($component['text']) ? $component['text'] : '';
                return "<{$level}>" . $this->replace_variables($text, $variables) . "</{$level}>";

            case 'text':
                $content = isset($component['content']) ? $component['content'] : '';
                return "<p>" . nl2br($this->replace_variables($content, $variables)) . "</p>";

            case 'variable':
                $name = isset($component['name']) ? $component['name'] : '';
                $value = isset($variables[$name]) ? $variables[$name] : '[' . $name . ']';
                return "<span class='bkgt-variable-preview'>{$value}</span>";

            case 'list':
                $list_type = isset($component['listType']) ? $component['listType'] : 'ul';
                $items = isset($component['items']) ? $component['items'] : array();
                $html = "<{$list_type}>";
                foreach ($items as $item) {
                    $html .= "<li>" . $this->replace_variables($item, $variables) . "</li>";
                }
                $html .= "</{$list_type}>";
                return $html;

            case 'table':
                return "<div class='bkgt-table-preview'>[Tabell]</div>";

            case 'image':
                return "<div class='bkgt-image-preview'>[Bild]</div>";

            case 'divider':
                return "<hr>";

            default:
                return "";
        }
    }

    /**
     * Replace variables in text
     */
    private function replace_variables($text, $variables) {
        foreach ($variables as $var_name => $var_value) {
            $text = str_replace($var_name, $var_value, $text);
        }
        return $text;
    }

    /**
     * AJAX: Apply template with context
     */
    public function ajax_apply_template() {
        check_ajax_referer('bkgt-smart-templates-nonce', 'nonce');

        $template_id = intval($_POST['template_id']);
        $context_type = sanitize_text_field($_POST['context_type']);
        $context_data = $_POST['context_data'];
        $custom_variables = isset($_POST['custom_variables']) ? $_POST['custom_variables'] : array();

        $result = $this->apply_template($template_id, $context_type, $context_data, $custom_variables);

        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        } else {
            wp_send_json_success(array(
                'document_id' => $result,
                'message' => __('Mallen har applicerats framgångsrikt!', 'bkgt-document-management')
            ));
        }
    }

    /**
     * Apply template and create document
     */
    private function apply_template($template_id, $context_type, $context_data, $custom_variables = array()) {
        $template = get_post($template_id);
        if (!$template || $template->post_type !== 'bkgt_template') {
            return new WP_Error('invalid_template', __('Ogiltig mall.', 'bkgt-document-management'));
        }

        // Populate variables
        $variables = $this->populate_variables($template, $context_data);

        // Override with custom variables
        foreach ($custom_variables as $var_name => $var_value) {
            $variables[$var_name] = sanitize_text_field($var_value);
        }

        // Generate document content
        $document_content = $this->generate_document_content($template, $variables);

        // Create document
        $document_args = array(
            'post_title' => $this->generate_document_title($template, $context_data, $variables),
            'post_content' => $document_content,
            'post_type' => 'bkgt_document',
            'post_status' => 'publish',
            'post_author' => get_current_user_id(),
            'meta_input' => array(
                '_bkgt_template_id' => $template_id,
                '_bkgt_context_type' => $context_type,
                '_bkgt_context_data' => $context_data,
                '_bkgt_applied_variables' => $variables,
                '_bkgt_applied_date' => current_time('mysql'),
            )
        );

        $document_id = wp_insert_post($document_args);

        if (is_wp_error($document_id)) {
            return $document_id;
        }

        // Link document to context entity
        $this->link_document_to_context($document_id, $context_type, $context_data);

        return $document_id;
    }

    /**
     * Generate document content from template
     */
    private function generate_document_content($template, $variables) {
        $template_content = json_decode($template->post_content, true);

        if (!$template_content || !isset($template_content['components'])) {
            return '';
        }

        $content = '';

        foreach ($template_content['components'] as $component) {
            $component_html = $this->render_component_html($component, $variables);
            $content .= $component_html . "\n\n";
        }

        return $content;
    }

    /**
     * Render component as HTML for document
     */
    private function render_component_html($component, $variables) {
        switch ($component['type']) {
            case 'heading':
                $level = isset($component['level']) ? $component['level'] : 'h3';
                $text = isset($component['text']) ? $this->replace_variables($component['text'], $variables) : '';
                return "<{$level}>{$text}</{$level}>";

            case 'text':
                $content = isset($component['content']) ? $this->replace_variables($component['content'], $variables) : '';
                return "<p>" . nl2br($content) . "</p>";

            case 'variable':
                $name = isset($component['name']) ? $component['name'] : '';
                $value = isset($variables[$name]) ? $variables[$name] : '[' . $name . ']';
                return "<strong>{$value}</strong>";

            case 'list':
                $list_type = isset($component['listType']) ? $component['listType'] : 'ul';
                $items = isset($component['items']) ? $component['items'] : array();
                $html = "<{$list_type}>";
                foreach ($items as $item) {
                    $replaced_item = $this->replace_variables($item, $variables);
                    $html .= "<li>{$replaced_item}</li>";
                }
                $html .= "</{$list_type}>";
                return $html;

            case 'table':
                // Basic table structure - could be enhanced
                return "<table><tr><td>[Tabellinnehåll]</td></tr></table>";

            case 'image':
                $url = isset($component['url']) ? $component['url'] : '';
                $alt = isset($component['alt']) ? $component['alt'] : '';
                if ($url) {
                    return "<img src='{$url}' alt='{$alt}' style='max-width:100%;'>";
                }
                return "[Bild]";

            case 'divider':
                return "<hr>";

            default:
                return "";
        }
    }

    /**
     * Generate document title
     */
    private function generate_document_title($template, $context_data, $variables) {
        $base_title = $template->post_title;

        // Add context-specific suffix
        switch ($context_data['type']) {
            case 'player':
                $suffix = isset($context_data['name']) ? ' - ' . $context_data['name'] : '';
                break;
            case 'team':
                $suffix = isset($context_data['name']) ? ' - ' . $context_data['name'] : '';
                break;
            case 'equipment':
                $suffix = isset($context_data['id']) ? ' - ' . $context_data['id'] : '';
                break;
            case 'meeting':
                $suffix = isset($context_data['date']) ? ' - ' . $context_data['date'] : '';
                break;
            default:
                $suffix = '';
        }

        return $base_title . $suffix;
    }

    /**
     * Link document to context entity
     */
    private function link_document_to_context($document_id, $context_type, $context_data) {
        // Add meta linking document to context
        switch ($context_type) {
            case 'player':
                if (isset($context_data['id'])) {
                    add_post_meta($document_id, '_bkgt_player_id', $context_data['id']);
                }
                break;
            case 'team':
                if (isset($context_data['id'])) {
                    add_post_meta($document_id, '_bkgt_team_id', $context_data['id']);
                }
                break;
            case 'equipment':
                if (isset($context_data['id'])) {
                    add_post_meta($document_id, '_bkgt_equipment_id', $context_data['id']);
                }
                break;
        }
    }

    /**
     * AJAX: Bulk apply template
     */
    public function ajax_bulk_apply_template() {
        check_ajax_referer('bkgt-smart-templates-nonce', 'nonce');

        $template_id = intval($_POST['template_id']);
        $context_type = sanitize_text_field($_POST['context_type']);
        $items = $_POST['items'];

        $results = array();
        $success_count = 0;

        foreach ($items as $item_data) {
            $result = $this->apply_template($template_id, $context_type, $item_data);

            if (is_wp_error($result)) {
                $results[] = array(
                    'item' => $item_data,
                    'success' => false,
                    'error' => $result->get_error_message()
                );
            } else {
                $results[] = array(
                    'item' => $item_data,
                    'success' => true,
                    'document_id' => $result
                );
                $success_count++;
            }
        }

        wp_send_json_success(array(
            'results' => $results,
            'success_count' => $success_count,
            'total_count' => count($items),
            'message' => sprintf(__('Applicerade mall på %d av %d objekt.', 'bkgt-document-management'), $success_count, count($items))
        ));
    }

    /**
     * AJAX: Get context variables
     */
    public function ajax_get_context_variables() {
        check_ajax_referer('bkgt-smart-templates-nonce', 'nonce');

        $context_type = sanitize_text_field($_POST['context_type']);
        $context_data = $_POST['context_data'];

        $variables = $this->get_context_variables($context_type, $context_data);

        wp_send_json_success(array(
            'variables' => $variables
        ));
    }

    /**
     * Get available variables for context
     */
    private function get_context_variables($context_type, $context_data) {
        $variables = array();

        // Common variables
        $variables[] = array('name' => '{{DATUM}}', 'description' => __('Aktuellt datum', 'bkgt-document-management'));
        $variables[] = array('name' => '{{TID}}', 'description' => __('Aktuell tid', 'bkgt-document-management'));
        $variables[] = array('name' => '{{AR}}', 'description' => __('Aktuellt år', 'bkgt-document-management'));
        $variables[] = array('name' => '{{ANVANDARNAMN}}', 'description' => __('Inloggad användares namn', 'bkgt-document-management'));

        // Context-specific variables
        switch ($context_type) {
            case 'player':
                $variables = array_merge($variables, array(
                    array('name' => '{{SPELARE_NAMN}}', 'description' => __('Spelarens namn', 'bkgt-document-management')),
                    array('name' => '{{SPELARE_EPOST}}', 'description' => __('Spelarens e-post', 'bkgt-document-management')),
                    array('name' => '{{SPELARE_TELEFON}}', 'description' => __('Spelarens telefonnummer', 'bkgt-document-management')),
                    array('name' => '{{SPELARE_LAG}}', 'description' => __('Spelarens lag', 'bkgt-document-management')),
                    array('name' => '{{SPELARE_POSITION}}', 'description' => __('Spelarens position', 'bkgt-document-management')),
                ));
                break;

            case 'team':
                $variables = array_merge($variables, array(
                    array('name' => '{{LAG_NAMN}}', 'description' => __('Lagets namn', 'bkgt-document-management')),
                    array('name' => '{{LAG_TRANARE}}', 'description' => __('Lagets tränare', 'bkgt-document-management')),
                    array('name' => '{{LAG_LEDare}}', 'description' => __('Lagets ledare', 'bkgt-document-management')),
                    array('name' => '{{LAG_ANTAL_SPELARE}}', 'description' => __('Antal spelare i laget', 'bkgt-document-management')),
                ));
                break;

            case 'equipment':
                $variables = array_merge($variables, array(
                    array('name' => '{{UTRUSTNING_ID}}', 'description' => __('Utrustnings-ID', 'bkgt-document-management')),
                    array('name' => '{{UTRUSTNING_TYP}}', 'description' => __('Utrustningstyp', 'bkgt-document-management')),
                    array('name' => '{{UTRUSTNING_TILLVERKARE}}', 'description' => __('Tillverkare', 'bkgt-document-management')),
                    array('name' => '{{UTRUSTNING_MODELL}}', 'description' => __('Modell', 'bkgt-document-management')),
                    array('name' => '{{UTRUSTNING_SKICK}}', 'description' => __('Skick/tillstånd', 'bkgt-document-management')),
                    array('name' => '{{UTRUSTNING_TILLDELAD_TILL}}', 'description' => __('Tilldelad till', 'bkgt-document-management')),
                ));
                break;

            case 'meeting':
                $variables = array_merge($variables, array(
                    array('name' => '{{MOTE_TYP}}', 'description' => __('Mötestyp', 'bkgt-document-management')),
                    array('name' => '{{MOTE_DATUM}}', 'description' => __('Mötesdatum', 'bkgt-document-management')),
                    array('name' => '{{MOTE_TID}}', 'description' => __('Mötestid', 'bkgt-document-management')),
                    array('name' => '{{MOTE_PLATS}}', 'description' => __('Mötesplats', 'bkgt-document-management')),
                    array('name' => '{{MOTE_AGENDA}}', 'description' => __('Mötesagenda', 'bkgt-document-management')),
                ));
                break;
        }

        return $variables;
    }
}