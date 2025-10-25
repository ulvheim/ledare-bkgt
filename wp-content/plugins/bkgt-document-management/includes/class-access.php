<?php
/**
 * Document Access Control Class
 *
 * @package BKGT_Document_Management
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class BKGT_Document_Access {

    /**
     * Access types
     */
    const ACCESS_VIEW = 'view';
    const ACCESS_EDIT = 'edit';
    const ACCESS_MANAGE = 'manage';

    /**
     * Access ID
     */
    private $access_id;

    /**
     * Access data
     */
    private $data;

    /**
     * Constructor
     */
    public function __construct($access_id = null) {
        $this->access_id = $access_id;
        if ($access_id) {
            $this->load_data();
        }
    }

    /**
     * Load access data
     */
    private function load_data() {
        global $wpdb;
        $table = $wpdb->prefix . 'bkgt_document_access';

        $this->data = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE id = %d",
            $this->access_id
        ));
    }

    /**
     * Create new access rule
     */
    public static function create($document_id, $access_data) {
        global $wpdb;
        $table = $wpdb->prefix . 'bkgt_document_access';

        $defaults = array(
            'user_id' => null,
            'role' => null,
            'team_id' => null,
            'access_type' => self::ACCESS_VIEW,
            'granted_by' => get_current_user_id(),
            'granted_date' => current_time('mysql'),
        );

        $access_data = wp_parse_args($access_data, $defaults);

        // Validate access type
        if (!in_array($access_data['access_type'], array(self::ACCESS_VIEW, self::ACCESS_EDIT, self::ACCESS_MANAGE))) {
            return new WP_Error('invalid_access_type', __('Ogiltig åtkomsttyp.', 'bkgt-document-management'));
        }

        // Ensure at least one target is specified
        if (!$access_data['user_id'] && !$access_data['role'] && !$access_data['team_id']) {
            return new WP_Error('no_access_target', __('Ingen åtkomstmål specificerad.', 'bkgt-document-management'));
        }

        $result = $wpdb->insert(
            $table,
            array(
                'document_id' => $document_id,
                'user_id' => $access_data['user_id'],
                'role' => $access_data['role'],
                'team_id' => $access_data['team_id'],
                'access_type' => $access_data['access_type'],
                'granted_by' => $access_data['granted_by'],
                'granted_date' => $access_data['granted_date'],
            ),
            array('%d', '%d', '%s', '%d', '%s', '%d', '%s')
        );

        if ($result) {
            $access_id = $wpdb->insert_id;
            return new self($access_id);
        }

        return new WP_Error('access_creation_failed', __('Misslyckades att skapa åtkomstregel.', 'bkgt-document-management'));
    }

    /**
     * Delete access rule
     */
    public static function delete($access_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'bkgt_document_access';

        $result = $wpdb->delete($table, array('id' => $access_id), array('%d'));

        if ($result) {
            return true;
        }

        return new WP_Error('access_deletion_failed', __('Misslyckades att radera åtkomstregel.', 'bkgt-document-management'));
    }

    /**
     * Get access ID
     */
    public function get_id() {
        return $this->access_id;
    }

    /**
     * Get document ID
     */
    public function get_document_id() {
        if (!$this->data) {
            return null;
        }
        return $this->data->document_id;
    }

    /**
     * Get user ID
     */
    public function get_user_id() {
        if (!$this->data) {
            return null;
        }
        return $this->data->user_id;
    }

    /**
     * Get role
     */
    public function get_role() {
        if (!$this->data) {
            return null;
        }
        return $this->data->role;
    }

    /**
     * Get team ID
     */
    public function get_team_id() {
        if (!$this->data) {
            return null;
        }
        return $this->data->team_id;
    }

    /**
     * Get access type
     */
    public function get_access_type() {
        if (!$this->data) {
            return null;
        }
        return $this->data->access_type;
    }

    /**
     * Get granted by user ID
     */
    public function get_granted_by() {
        if (!$this->data) {
            return null;
        }
        return $this->data->granted_by;
    }

    /**
     * Get granted date
     */
    public function get_granted_date() {
        if (!$this->data) {
            return null;
        }
        return $this->data->granted_date;
    }

    /**
     * Get granted by user name
     */
    public function get_granted_by_name() {
        $user_id = $this->get_granted_by();
        if (!$user_id) {
            return __('Okänd', 'bkgt-document-management');
        }

        $user = get_userdata($user_id);
        return $user ? $user->display_name : __('Okänd', 'bkgt-document-management');
    }

    /**
     * Get target name (user, role, or team)
     */
    public function get_target_name() {
        if ($this->get_user_id()) {
            $user = get_userdata($this->get_user_id());
            return $user ? $user->display_name : __('Okänd användare', 'bkgt-document-management');
        }

        if ($this->get_role()) {
            $role_names = array(
                'administrator' => __('Administratör', 'bkgt-document-management'),
                'editor' => __('Redaktör', 'bkgt-document-management'),
                'author' => __('Författare', 'bkgt-document-management'),
                'contributor' => __('Bidragsgivare', 'bkgt-document-management'),
                'subscriber' => __('Prenumerant', 'bkgt-document-management'),
                'bkgt_styrelsemedlem' => __('Styrelsemedlem', 'bkgt-document-management'),
                'bkgt_tranare' => __('Tränare', 'bkgt-document-management'),
                'bkgt_lagledare' => __('Lagledare', 'bkgt-document-management'),
            );
            return isset($role_names[$this->get_role()]) ? $role_names[$this->get_role()] : $this->get_role();
        }

        if ($this->get_team_id() && function_exists('bkgt_get_team_name')) {
            return bkgt_get_team_name($this->get_team_id());
        }

        return __('Okänd', 'bkgt-document-management');
    }

    /**
     * Get access type name
     */
    public function get_access_type_name() {
        $type_names = array(
            self::ACCESS_VIEW => __('Visa', 'bkgt-document-management'),
            self::ACCESS_EDIT => __('Redigera', 'bkgt-document-management'),
            self::ACCESS_MANAGE => __('Hantera', 'bkgt-document-management'),
        );

        $type = $this->get_access_type();
        return isset($type_names[$type]) ? $type_names[$type] : $type;
    }

    /**
     * Get formatted granted date
     */
    public function get_formatted_granted_date($format = 'Y-m-d H:i') {
        $date = $this->get_granted_date();
        if (!$date) {
            return '';
        }

        return date_i18n($format, strtotime($date));
    }

    /**
     * Get all access rules for a document
     */
    public static function get_document_access($document_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'bkgt_document_access';

        $access_rules = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table WHERE document_id = %d ORDER BY granted_date DESC",
            $document_id
        ));

        $access_objects = array();
        foreach ($access_rules as $access_data) {
            $access = new self();
            $access->data = $access_data;
            $access->access_id = $access_data->id;
            $access_objects[] = $access;
        }

        return $access_objects;
    }

    /**
     * Check if user has access to document
     */
    public static function user_has_access($document_id, $user_id = null, $access_type = self::ACCESS_VIEW) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }

        // Administrators always have access
        if (user_can($user_id, 'administrator')) {
            return true;
        }

        // Document creator always has manage access
        $document = get_post($document_id);
        if ($document && $document->post_author == $user_id) {
            return true;
        }

        $access_rules = self::get_document_access($document_id);
        $user = get_userdata($user_id);

        if (!$user) {
            return false;
        }

        $user_roles = $user->roles;
        $user_teams = function_exists('bkgt_get_user_teams') ? bkgt_get_user_teams($user_id) : array();
        $user_team_ids = wp_list_pluck($user_teams, 'team_id');

        $access_levels = array(
            self::ACCESS_VIEW => 1,
            self::ACCESS_EDIT => 2,
            self::ACCESS_MANAGE => 3,
        );

        $required_level = isset($access_levels[$access_type]) ? $access_levels[$access_type] : 1;
        $user_max_level = 0;

        foreach ($access_rules as $rule) {
            $rule_level = isset($access_levels[$rule->get_access_type()]) ? $access_levels[$rule->get_access_type()] : 0;

            // Check user-specific access
            if ($rule->get_user_id() == $user_id && $rule_level >= $required_level) {
                return true;
            }

            // Check role-based access
            if ($rule->get_role() && in_array($rule->get_role(), $user_roles) && $rule_level >= $required_level) {
                return true;
            }

            // Check team-based access
            if ($rule->get_team_id() && in_array($rule->get_team_id(), $user_team_ids) && $rule_level >= $required_level) {
                return true;
            }

            // Track maximum access level for this user
            if (($rule->get_user_id() == $user_id) ||
                ($rule->get_role() && in_array($rule->get_role(), $user_roles)) ||
                ($rule->get_team_id() && in_array($rule->get_team_id(), $user_team_ids))) {
                $user_max_level = max($user_max_level, $rule_level);
            }
        }

        return $user_max_level >= $required_level;
    }

    /**
     * Get user's access level for a document
     */
    public static function get_user_access_level($document_id, $user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }

        // Administrators have manage access
        if (user_can($user_id, 'administrator')) {
            return self::ACCESS_MANAGE;
        }

        // Document creator has manage access
        $document = get_post($document_id);
        if ($document && $document->post_author == $user_id) {
            return self::ACCESS_MANAGE;
        }

        $access_rules = self::get_document_access($document_id);
        $user = get_userdata($user_id);

        if (!$user) {
            return null;
        }

        $user_roles = $user->roles;
        $user_teams = function_exists('bkgt_get_user_teams') ? bkgt_get_user_teams($user_id) : array();
        $user_team_ids = wp_list_pluck($user_teams, 'team_id');

        $access_levels = array(
            self::ACCESS_VIEW => 1,
            self::ACCESS_EDIT => 2,
            self::ACCESS_MANAGE => 3,
        );

        $user_max_level = 0;

        foreach ($access_rules as $rule) {
            $rule_level = isset($access_levels[$rule->get_access_type()]) ? $access_levels[$rule->get_access_type()] : 0;

            if (($rule->get_user_id() == $user_id) ||
                ($rule->get_role() && in_array($rule->get_role(), $user_roles)) ||
                ($rule->get_team_id() && in_array($rule->get_team_id(), $user_team_ids))) {
                $user_max_level = max($user_max_level, $rule_level);
            }
        }

        // Convert back to access type
        $level_to_type = array_flip($access_levels);
        return isset($level_to_type[$user_max_level]) ? $level_to_type[$user_max_level] : null;
    }

    /**
     * Remove all access rules for a document
     */
    public static function remove_document_access($document_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'bkgt_document_access';

        $wpdb->delete($table, array('document_id' => $document_id), array('%d'));
        return true;
    }

    /**
     * Get access statistics
     */
    public static function get_statistics($document_id = null) {
        global $wpdb;
        $table = $wpdb->prefix . 'bkgt_document_access';

        $stats = array(
            'total_rules' => 0,
            'user_rules' => 0,
            'role_rules' => 0,
            'team_rules' => 0,
            'view_rules' => 0,
            'edit_rules' => 0,
            'manage_rules' => 0,
        );

        $where_clause = '';
        if ($document_id) {
            $where_clause = $wpdb->prepare('WHERE document_id = %d', $document_id);
        }

        // Total rules
        $stats['total_rules'] = $wpdb->get_var("SELECT COUNT(*) FROM $table $where_clause");

        // Rules by target type
        $stats['user_rules'] = $wpdb->get_var("SELECT COUNT(*) FROM $table $where_clause AND user_id IS NOT NULL");
        $stats['role_rules'] = $wpdb->get_var("SELECT COUNT(*) FROM $table $where_clause AND role IS NOT NULL");
        $stats['team_rules'] = $wpdb->get_var("SELECT COUNT(*) FROM $table $where_clause AND team_id IS NOT NULL");

        // Rules by access type
        $stats['view_rules'] = $wpdb->get_var("SELECT COUNT(*) FROM $table $where_clause AND access_type = 'view'");
        $stats['edit_rules'] = $wpdb->get_var("SELECT COUNT(*) FROM $table $where_clause AND access_type = 'edit'");
        $stats['manage_rules'] = $wpdb->get_var("SELECT COUNT(*) FROM $table $where_clause AND access_type = 'manage'");

        return $stats;
    }
}