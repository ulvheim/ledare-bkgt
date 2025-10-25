<?php
/**
 * Document Category Class
 *
 * @package BKGT_Document_Management
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class BKGT_Document_Category {

    /**
     * Category ID
     */
    private $category_id;

    /**
     * Category data
     */
    private $data;

    /**
     * Constructor
     */
    public function __construct($category_id = null) {
        $this->category_id = $category_id;
        if ($category_id) {
            $this->load_data();
        }
    }

    /**
     * Load category data
     */
    private function load_data() {
        $this->data = get_term($this->category_id, 'bkgt_doc_category');
    }

    /**
     * Create new category
     */
    public static function create($data) {
        $defaults = array(
            'name' => '',
            'description' => '',
            'parent' => 0,
        );

        $category_data = wp_parse_args($data, $defaults);

        $result = wp_insert_term(
            $category_data['name'],
            'bkgt_doc_category',
            array(
                'description' => $category_data['description'],
                'parent' => $category_data['parent'],
            )
        );

        if (!is_wp_error($result)) {
            return new self($result['term_id']);
        }

        return $result; // Return WP_Error
    }

    /**
     * Update category
     */
    public function update($data) {
        if (!$this->category_id) {
            return new WP_Error('invalid_category', __('Ogiltig kategori-ID.', 'bkgt-document-management'));
        }

        $update_data = array('term_id' => $this->category_id);
        $allowed_fields = array('name', 'description', 'parent');

        foreach ($allowed_fields as $field) {
            if (isset($data[$field])) {
                $update_data[$field] = $data[$field];
            }
        }

        $result = wp_update_term($this->category_id, 'bkgt_doc_category', $update_data);

        if (!is_wp_error($result)) {
            $this->load_data();
            return true;
        }

        return $result;
    }

    /**
     * Delete category
     */
    public static function delete($category_id) {
        if (!current_user_can('manage_categories')) {
            return new WP_Error('insufficient_permissions', __('Otillräckliga behörigheter.', 'bkgt-document-management'));
        }

        // Check if category has documents
        $documents = BKGT_Document::get_by_category($category_id);
        if (!empty($documents)) {
            return new WP_Error('category_not_empty', __('Kategorin innehåller dokument och kan inte raderas.', 'bkgt-document-management'));
        }

        $result = wp_delete_term($category_id, 'bkgt_doc_category');

        if ($result) {
            return true;
        }

        return new WP_Error('delete_failed', __('Misslyckades att radera kategori.', 'bkgt-document-management'));
    }

    /**
     * Get category name
     */
    public function get_name() {
        if (!$this->data) {
            return '';
        }
        return $this->data->name;
    }

    /**
     * Get category description
     */
    public function get_description() {
        if (!$this->data) {
            return '';
        }
        return $this->data->description;
    }

    /**
     * Get parent category
     */
    public function get_parent() {
        if (!$this->data) {
            return 0;
        }
        return $this->data->parent;
    }

    /**
     * Get category count
     */
    public function get_count() {
        if (!$this->data) {
            return 0;
        }
        return $this->data->count;
    }

    /**
     * Get category slug
     */
    public function get_slug() {
        if (!$this->data) {
            return '';
        }
        return $this->data->slug;
    }

    /**
     * Get all categories
     */
    public static function get_all($args = array()) {
        $defaults = array(
            'taxonomy' => 'bkgt_doc_category',
            'hide_empty' => false,
            'orderby' => 'name',
            'order' => 'ASC',
        );

        $query_args = wp_parse_args($args, $defaults);
        return get_terms($query_args);
    }

    /**
     * Get categories hierarchy
     */
    public static function get_hierarchy() {
        $categories = self::get_all(array('parent' => 0));
        $hierarchy = array();

        foreach ($categories as $category) {
            $hierarchy[] = array(
                'term_id' => $category->term_id,
                'name' => $category->name,
                'children' => self::get_children($category->term_id),
            );
        }

        return $hierarchy;
    }

    /**
     * Get category children
     */
    public static function get_children($parent_id) {
        $children = get_terms(array(
            'taxonomy' => 'bkgt_doc_category',
            'parent' => $parent_id,
            'hide_empty' => false,
        ));

        $result = array();
        foreach ($children as $child) {
            $result[] = array(
                'term_id' => $child->term_id,
                'name' => $child->name,
                'children' => self::get_children($child->term_id),
            );
        }

        return $result;
    }

    /**
     * Get category path (breadcrumb)
     */
    public function get_path() {
        if (!$this->category_id) {
            return array();
        }

        $path = array();
        $current = $this->data;

        while ($current) {
            array_unshift($path, array(
                'term_id' => $current->term_id,
                'name' => $current->name,
                'slug' => $current->slug,
            ));

            if ($current->parent) {
                $current = get_term($current->parent, 'bkgt_doc_category');
            } else {
                break;
            }
        }

        return $path;
    }

    /**
     * Set category permissions
     */
    public function set_permissions($permissions) {
        if (!$this->category_id) {
            return new WP_Error('invalid_category', __('Ogiltig kategori.', 'bkgt-document-management'));
        }

        update_term_meta($this->category_id, '_bkgt_category_permissions', $permissions);
        return true;
    }

    /**
     * Get category permissions
     */
    public function get_permissions() {
        if (!$this->category_id) {
            return array();
        }

        $permissions = get_term_meta($this->category_id, '_bkgt_category_permissions', true);
        return $permissions ? $permissions : array();
    }

    /**
     * Check if user can access category
     */
    public function user_can_access($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }

        // Check category permissions
        $permissions = $this->get_permissions();
        if (empty($permissions)) {
            return true; // No restrictions
        }

        $user = get_userdata($user_id);
        if (!$user) {
            return false;
        }

        // Check roles
        if (isset($permissions['roles']) && !empty($permissions['roles'])) {
            $has_role = false;
            foreach ($permissions['roles'] as $role) {
                if (in_array($role, $user->roles)) {
                    $has_role = true;
                    break;
                }
            }
            if (!$has_role) {
                return false;
            }
        }

        // Check teams (if user management plugin is active)
        if (isset($permissions['teams']) && !empty($permissions['teams']) && function_exists('bkgt_get_user_teams')) {
            $user_teams = bkgt_get_user_teams($user_id);
            $team_ids = wp_list_pluck($user_teams, 'team_id');

            $has_team = false;
            foreach ($permissions['teams'] as $team_id) {
                if (in_array($team_id, $team_ids)) {
                    $has_team = true;
                    break;
                }
            }
            if (!$has_team) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get category statistics
     */
    public function get_statistics() {
        if (!$this->category_id) {
            return array();
        }

        $stats = array(
            'document_count' => $this->get_count(),
            'total_size' => 0,
            'last_updated' => null,
        );

        // Get documents in this category
        $documents = BKGT_Document::get_by_category($this->category_id);

        if (!empty($documents)) {
            $total_size = 0;
            $last_updated = null;

            foreach ($documents as $doc) {
                $document = new BKGT_Document($doc->ID);
                $total_size += $document->get_file_size();

                $upload_date = $document->get_upload_date();
                if (!$last_updated || strtotime($upload_date) > strtotime($last_updated)) {
                    $last_updated = $upload_date;
                }
            }

            $stats['total_size'] = $total_size;
            $stats['last_updated'] = $last_updated;
        }

        return $stats;
    }

    /**
     * Get formatted total size
     */
    public function get_formatted_total_size() {
        $stats = $this->get_statistics();
        $bytes = $stats['total_size'];

        if (!$bytes) {
            return '0 B';
        }

        $units = array('B', 'KB', 'MB', 'GB', 'TB');
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= pow(1024, $pow);

        return round($bytes, 2) . ' ' . $units[$pow];
    }
}