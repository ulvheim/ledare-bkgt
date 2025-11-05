<?php
/**
 * PHPUnit Bootstrap for BKGT Plugin Tests
 */

// Define WordPress constants for testing (only if not already defined)
if (!defined('WP_DEBUG')) {
    define('WP_DEBUG', true);
}
if (!defined('WP_DEBUG_LOG')) {
    define('WP_DEBUG_LOG', true);
}
if (!defined('WP_DEBUG_DISPLAY')) {
    define('WP_DEBUG_DISPLAY', false);
}

// Define WordPress paths (only if not already defined)
if (!defined('ABSPATH')) {
    define('ABSPATH', dirname(__DIR__) . '/');
}
if (!defined('WPINC')) {
    define('WPINC', 'wp-includes');
}
if (!defined('WP_CONTENT_DIR')) {
    define('WP_CONTENT_DIR', ABSPATH . 'wp-content');
}
if (!defined('WP_PLUGIN_DIR')) {
    define('WP_PLUGIN_DIR', WP_CONTENT_DIR . '/plugins');
}

// Mock basic WordPress functions for testing
if (!function_exists('wp_die')) {
    function wp_die($message = '') {
        throw new Exception($message ?: 'WordPress died');
    }
}

if (!function_exists('is_admin')) {
    function is_admin() {
        return false;
    }
}

if (!function_exists('wp_enqueue_script')) {
    function wp_enqueue_script($handle, $src = '', $deps = array(), $ver = false, $in_footer = false) {
        // Mock function - do nothing
    }
}

if (!function_exists('wp_enqueue_style')) {
    function wp_enqueue_style($handle, $src = '', $deps = array(), $ver = false, $media = 'all') {
        // Mock function - do nothing
    }
}

if (!function_exists('add_action')) {
    function add_action($hook, $function, $priority = 10, $accepted_args = 1) {
        // Mock function - do nothing
    }
}

if (!function_exists('add_filter')) {
    function add_filter($hook, $function, $priority = 10, $accepted_args = 1) {
        // Mock function - do nothing
    }
}

if (!function_exists('register_activation_hook')) {
    function register_activation_hook($file, $function) {
        // Mock function - do nothing
    }
}

if (!function_exists('register_deactivation_hook')) {
    function register_deactivation_hook($file, $function) {
        // Mock function - do nothing
    }
}

if (!function_exists('wp_create_nonce')) {
    function wp_create_nonce($action = -1) {
        return 'test_nonce_' . $action;
    }
}

if (!function_exists('wp_verify_nonce')) {
    function wp_verify_nonce($nonce, $action = -1) {
        return $nonce === 'test_nonce_' . $action;
    }
}

if (!function_exists('current_user_can')) {
    function current_user_can($capability) {
        return true; // Allow all capabilities in tests
    }
}

if (!function_exists('wp_get_current_user')) {
    function wp_get_current_user() {
        return (object) ['ID' => 1, 'user_login' => 'testuser', 'user_email' => 'test@example.com'];
    }
}

if (!function_exists('get_current_user_id')) {
    function get_current_user_id() {
        return 1;
    }
}

if (!function_exists('sanitize_text_field')) {
    function sanitize_text_field($str) {
        return trim(strip_tags($str));
    }
}

if (!function_exists('wp_kses_post')) {
    function wp_kses_post($content) {
        return $content; // Simplified for testing
    }
}

if (!function_exists('esc_attr')) {
    function esc_attr($text) {
        return htmlspecialchars($text, ENT_QUOTES);
    }
}

if (!function_exists('esc_html')) {
    function esc_html($text) {
        return htmlspecialchars($text, ENT_QUOTES);
    }
}

if (!function_exists('esc_url')) {
    function esc_url($url) {
        return $url; // Simplified for testing
    }
}

if (!function_exists('admin_url')) {
    function admin_url($path = '') {
        return 'https://ledare.bkgt.se/wp-admin/' . $path;
    }
}

if (!function_exists('wp_nonce_field')) {
    function wp_nonce_field($action = -1, $name = '_wpnonce', $referer = true) {
        return '<input type="hidden" name="' . $name . '" value="' . wp_create_nonce($action) . '" />';
    }
}

if (!function_exists('wp_nonce_url')) {
    function wp_nonce_url($url, $action = -1) {
        return $url . '?_wpnonce=' . wp_create_nonce($action);
    }
}

if (!function_exists('check_admin_referer')) {
    function check_admin_referer($action = -1, $query_arg = '_wpnonce') {
        return true; // Always pass in tests
    }
}

if (!function_exists('wp_redirect')) {
    function wp_redirect($location, $status = 302) {
        // Mock function - do nothing
    }
}

if (!function_exists('plugin_dir_path')) {
    function plugin_dir_path($file) {
        return WP_PLUGIN_DIR . '/' . dirname(plugin_basename($file)) . '/';
    }
}

if (!function_exists('plugin_basename')) {
    function plugin_basename($file) {
        $file = str_replace('\\', '/', $file);
        $file = preg_replace('|/+|', '/', $file);
        $plugin_dir = str_replace('\\', '/', WP_PLUGIN_DIR);
        $plugin_dir = preg_replace('|/+|', '/', $plugin_dir);
        $mu_plugin_dir = defined('WPMU_PLUGIN_DIR') ? str_replace('\\', '/', WPMU_PLUGIN_DIR) : '';
        $mu_plugin_dir = preg_replace('|/+|', '/', $mu_plugin_dir);
        $file = preg_replace('#^' . preg_quote($plugin_dir, '#') . '/|^' . preg_quote($mu_plugin_dir, '#') . '/#', '', $file);
        return trim($file, '/');
    }
}

if (!function_exists('plugins_url')) {
    function plugins_url($path = '', $plugin = '') {
        $url = 'https://ledare.bkgt.se/wp-content/plugins';
        if (!empty($plugin) && is_string($plugin)) {
            $folder = dirname(plugin_basename($plugin));
            if ('.' !== $folder) {
                $url .= '/' . ltrim($folder, '/');
            }
        }
        if ($path && is_string($path)) {
            $url .= '/' . ltrim($path, '/');
        }
        return $url;
    }
}

if (!function_exists('plugin_dir_url')) {
    function plugin_dir_url($file) {
        return plugins_url('', $file);
    }
}

// Mock post type and taxonomy functions
if (!function_exists('register_post_type')) {
    function register_post_type($post_type, $args = array()) {
        // Mock function - do nothing
        return true;
    }
}

if (!function_exists('register_taxonomy')) {
    function register_taxonomy($taxonomy, $object_type, $args = array()) {
        // Mock function - do nothing
        return true;
    }
}

if (!function_exists('add_post_type_support')) {
    function add_post_type_support($post_type, $feature) {
        // Mock function - do nothing
    }
}

if (!function_exists('add_theme_support')) {
    function add_theme_support($feature) {
        // Mock function - do nothing
    }
}

if (!function_exists('add_cap')) {
    function add_cap($role, $cap) {
        // Mock function - do nothing
    }
}

if (!function_exists('remove_cap')) {
    function remove_cap($role, $cap) {
        // Mock function - do nothing
    }
}

// Mock AJAX functions
if (!function_exists('wp_send_json_success')) {
    function wp_send_json_success($data = null) {
        echo json_encode(array_merge(array('success' => true), $data ? array('data' => $data) : array()));
        exit;
    }
}

if (!function_exists('wp_send_json_error')) {
    function wp_send_json_error($data = null) {
        echo json_encode(array_merge(array('success' => false), $data ? array('data' => $data) : array()));
        exit;
    }
}

// Mock shortcode functions
if (!function_exists('add_shortcode')) {
    function add_shortcode($tag, $func) {
        // Mock function - do nothing
    }
}

if (!function_exists('do_shortcode')) {
    function do_shortcode($content) {
        return $content; // Simplified for testing
    }
}

// Mock user functions
if (!function_exists('get_userdata')) {
    function get_userdata($user_id) {
        return (object) [
            'ID' => $user_id,
            'user_login' => 'testuser' . $user_id,
            'user_email' => 'test' . $user_id . '@example.com',
            'display_name' => 'Test User ' . $user_id
        ];
    }
}

if (!function_exists('wp_insert_user')) {
    function wp_insert_user($userdata) {
        return rand(1, 1000); // Return a random user ID
    }
}

if (!function_exists('wp_update_user')) {
    function wp_update_user($userdata) {
        return $userdata['ID'] ?? rand(1, 1000);
    }
}

if (!function_exists('wp_delete_user')) {
    function wp_delete_user($user_id) {
        return true;
    }
}

if (!function_exists('username_exists')) {
    function username_exists($username) {
        return false; // Assume username doesn't exist
    }
}

if (!function_exists('email_exists')) {
    function email_exists($email) {
        return false; // Assume email doesn't exist
    }
}

if (!function_exists('wp_set_current_user')) {
    function wp_set_current_user($id = 0, $name = '') {
        // Mock function - do nothing
    }
}

if (!function_exists('post_type_exists')) {
    function post_type_exists($post_type) {
        return true; // Assume post type exists
    }
}

if (!function_exists('get_post_type_object')) {
    function get_post_type_object($post_type) {
        return (object) [
            'name' => $post_type,
            'label' => ucfirst($post_type),
            'public' => true,
            'hierarchical' => false,
            'supports' => ['title', 'editor', 'custom-fields']
        ];
    }
}

if (!function_exists('wp_parse_args')) {
    function wp_parse_args($args, $defaults = '') {
        if (is_object($args)) {
            $r = get_object_vars($args);
        } elseif (is_array($args)) {
            $r =& $args;
        } else {
            wp_parse_str($args, $r);
        }

        if (is_array($defaults)) {
            return array_merge($defaults, $r);
        }
        return $r;
    }
}

if (!function_exists('wp_parse_str')) {
    function wp_parse_str($string, &$array) {
        parse_str($string, $array);
    }
}

if (!function_exists('do_action')) {
    function do_action($tag, ...$args) {
        // Mock function - do nothing
    }
}

if (!function_exists('apply_filters')) {
    function apply_filters($tag, $value, ...$args) {
        return $value; // Return the value unchanged
    }
}

if (!function_exists('user_can')) {
    function user_can($user, $capability) {
        return true; // Allow all capabilities in tests
    }
}

if (!function_exists('get_bloginfo')) {
    function get_bloginfo($show = '', $filter = 'raw') {
        $bloginfo = [
            'name' => 'BKGT Ledare Test Site',
            'description' => 'Test site for BKGT Ledare',
            'url' => 'https://ledare.bkgt.se',
            'version' => '6.4.1',
            'charset' => 'UTF-8'
        ];

        if (empty($show)) {
            return $bloginfo;
        }

        return $bloginfo[$show] ?? '';
    }
}

if (!function_exists('shortcode_exists')) {
    function shortcode_exists($tag) {
        return true; // Assume shortcode exists
    }
}

if (!function_exists('get_taxonomy')) {
    function get_taxonomy($taxonomy) {
        return (object) [
            'name' => $taxonomy,
            'label' => ucfirst(str_replace('_', ' ', $taxonomy)),
            'public' => true,
            'hierarchical' => false
        ];
    }
}

if (!function_exists('has_action')) {
    function has_action($tag, $function_to_check = false) {
        return true; // Assume action exists
    }
}

if (!function_exists('has_filter')) {
    function has_filter($tag, $function_to_check = false) {
        return true; // Assume filter exists
    }
}

// Mock WP_Query class
if (!class_exists('WP_Query')) {
    class WP_Query {
        public $posts = [];
        public $post_count = 0;
        public $found_posts = 0;

        public function __construct($query = null) {
            // Mock posts
            $this->posts = [
                (object) ['ID' => 1, 'post_title' => 'Test Document 1', 'post_type' => 'bkgt_document'],
                (object) ['ID' => 2, 'post_title' => 'Test Document 2', 'post_type' => 'bkgt_document']
            ];
            $this->post_count = count($this->posts);
            $this->found_posts = $this->post_count;
        }

        public function have_posts() {
            return !empty($this->posts);
        }

        public function the_post() {
            // Mock implementation
        }

        public function rewind_posts() {
            // Mock implementation
        }
    }
}

// Mock post functions
if (!function_exists('get_post')) {
    function get_post($post_id) {
        return (object) [
            'ID' => $post_id,
            'post_title' => 'Test Post ' . $post_id,
            'post_content' => 'Test content',
            'post_status' => 'publish',
            'post_type' => 'post'
        ];
    }
}

if (!function_exists('wp_insert_post')) {
    function wp_insert_post($postarr) {
        return rand(1, 1000); // Return a random post ID
    }
}

if (!function_exists('wp_update_post')) {
    function wp_update_post($postarr) {
        return $postarr['ID'] ?? rand(1, 1000);
    }
}

if (!function_exists('wp_delete_post')) {
    function wp_delete_post($post_id) {
        return (object) ['ID' => $post_id];
    }
}

if (!function_exists('get_posts')) {
    function get_posts($args = array()) {
        return array(
            (object) ['ID' => 1, 'post_title' => 'Test Post 1'],
            (object) ['ID' => 2, 'post_title' => 'Test Post 2']
        );
    }
}

// Mock term functions
if (!function_exists('wp_insert_term')) {
    function wp_insert_term($term, $taxonomy) {
        return array('term_id' => rand(1, 1000), 'term_taxonomy_id' => rand(1, 1000));
    }
}

if (!function_exists('wp_set_post_terms')) {
    function wp_set_post_terms($post_id, $terms, $taxonomy) {
        return array(rand(1, 1000));
    }
}

if (!function_exists('wp_set_object_terms')) {
    function wp_set_object_terms($object_id, $terms, $taxonomy, $append = false) {
        return array(rand(1, 1000)); // Return array of term IDs
    }
}

if (!function_exists('wp_get_object_terms')) {
    function wp_get_object_terms($object_ids, $taxonomies, $args = array()) {
        return array(
            (object) ['term_id' => 1, 'name' => 'Test Category', 'slug' => 'test-category']
        );
    }
}

// Mock meta functions
if (!function_exists('get_post_meta')) {
    function get_post_meta($post_id, $key = '', $single = false) {
        return $single ? 'test_value' : array('test_value');
    }
}

if (!function_exists('update_post_meta')) {
    function update_post_meta($post_id, $meta_key, $meta_value) {
        return true;
    }
}

if (!function_exists('delete_post_meta')) {
    function delete_post_meta($post_id, $meta_key) {
        return true;
    }
}

if (!function_exists('get_user_meta')) {
    function get_user_meta($user_id, $key = '', $single = false) {
        return $single ? 'test_value' : array('test_value');
    }
}

if (!function_exists('update_user_meta')) {
    function update_user_meta($user_id, $meta_key, $meta_value) {
        return true;
    }
}

// Mock option functions
if (!function_exists('get_option')) {
    function get_option($option, $default = false) {
        return $default;
    }
}

if (!function_exists('update_option')) {
    function update_option($option, $value) {
        return true;
    }
}

if (!function_exists('delete_option')) {
    function delete_option($option) {
        return true;
    }
}

// Mock transient functions
if (!function_exists('get_transient')) {
    function get_transient($transient) {
        return false;
    }
}

if (!function_exists('set_transient')) {
    function set_transient($transient, $value, $expiration = 0) {
        return true;
    }
}

if (!function_exists('delete_transient')) {
    function delete_transient($transient) {
        return true;
    }
}

// Load test utilities
require_once __DIR__ . '/TestCase.php';
require_once __DIR__ . '/TestHelper.php';

// Note: Plugins are loaded individually in test files to avoid conflicts
// Do not load all plugins here as they may have conflicting definitions