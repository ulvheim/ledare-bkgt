<?php
/**
 * BKGT Ledare Theme Functions
 * 
 * @package BKGT_Ledare
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Theme Setup
 */
function bkgt_ledare_setup() {
    // Add support for document title tag
    add_theme_support('title-tag');
    
    // Add support for custom logo
    add_theme_support('custom-logo', array(
        'height'      => 60,
        'width'       => 200,
        'flex-height' => true,
        'flex-width'  => true,
    ));
    
    // Register navigation menus
    register_nav_menus(array(
        'primary' => __('Huvudmeny', 'bkgt-ledare'),
        'teams'   => __('Lagmeny', 'bkgt-ledare'),
    ));
    
    // Load text domain for translations
    load_theme_textdomain('bkgt-ledare', get_template_directory() . '/languages');
}
add_action('after_setup_theme', 'bkgt_ledare_setup');

/**
 * Enqueue scripts and styles
 */
function bkgt_ledare_scripts() {
    // Main stylesheet
    wp_enqueue_style('bkgt-ledare-style', get_stylesheet_uri(), array(), '1.0.0');
    
    // Main JavaScript (we'll create this later)
    wp_enqueue_script('bkgt-ledare-script', get_template_directory_uri() . '/js/main.js', array(), '1.0.0', true);
    
    // Localize script for AJAX
    wp_localize_script('bkgt-ledare-script', 'bkgtLedare', array(
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce'   => wp_create_nonce('bkgt-ledare-nonce'),
    ));
}
add_action('wp_enqueue_scripts', 'bkgt_ledare_scripts');

/**
 * Register widget areas
 */
function bkgt_ledare_widgets_init() {
    register_sidebar(array(
        'name'          => __('Dashboard Sidebar', 'bkgt-ledare'),
        'id'            => 'dashboard-sidebar',
        'description'   => __('Visas i dashboard-sidofältet', 'bkgt-ledare'),
        'before_widget' => '<div id="%1$s" class="widget %2$s">',
        'after_widget'  => '</div>',
        'before_title'  => '<h3 class="widget-title">',
        'after_title'   => '</h3>',
    ));
}
add_action('widgets_init', 'bkgt_ledare_widgets_init');

/**
 * Add Swedish role names
 */
function bkgt_ledare_add_custom_roles() {
    // Remove default roles we don't need
    remove_role('subscriber');
    remove_role('contributor');
    remove_role('author');
    remove_role('editor');
    
    // Add custom roles from specification
    
    // Styrelsemedlem (Board Member / Admin)
    add_role('styrelsemedlem', __('Styrelsemedlem', 'bkgt-ledare'), array(
        'read'                   => true,
        'edit_posts'             => true,
        'delete_posts'           => true,
        'publish_posts'          => true,
        'upload_files'           => true,
        'edit_others_posts'      => true,
        'delete_others_posts'    => true,
        'manage_categories'      => true,
        'manage_options'         => true,
        // Custom capabilities
        'view_performance_data'  => true,
        'manage_inventory'       => true,
        'manage_documents'       => true,
        'manage_all_teams'       => true,
    ));
    
    // Tränare (Coach)
    add_role('tranare', __('Tränare', 'bkgt-ledare'), array(
        'read'                   => true,
        'edit_posts'             => true,
        'delete_posts'           => true,
        'publish_posts'          => true,
        'upload_files'           => true,
        // Custom capabilities
        'view_performance_data'  => true,
        'manage_inventory'       => true,
        'manage_documents'       => true,
        'view_team_data'         => true,
    ));
    
    // Lagledare (Team Manager)
    add_role('lagledare', __('Lagledare', 'bkgt-ledare'), array(
        'read'                   => true,
        'edit_posts'             => true,
        'upload_files'           => true,
        // Custom capabilities
        'manage_inventory'       => true,
        'manage_documents'       => true,
        'view_team_data'         => true,
    ));
}
// Run once on theme activation
add_action('after_switch_theme', 'bkgt_ledare_add_custom_roles');

/**
 * Get current user's role in Swedish
 */
function bkgt_get_user_role_label($user_id = null) {
    if (!$user_id) {
        $user_id = get_current_user_id();
    }
    
    $user = get_userdata($user_id);
    if (!$user) {
        return '';
    }
    
    $roles = $user->roles;
    $role = reset($roles);
    
    $role_labels = array(
        'administrator'   => __('Administratör', 'bkgt-ledare'),
        'styrelsemedlem'  => __('Styrelsemedlem', 'bkgt-ledare'),
        'tranare'         => __('Tränare', 'bkgt-ledare'),
        'lagledare'       => __('Lagledare', 'bkgt-ledare'),
    );
    
    return isset($role_labels[$role]) ? $role_labels[$role] : ucfirst($role);
}

/**
 * Check if current user has access to performance data
 */
function bkgt_can_view_performance_data($user_id = null) {
    if (!$user_id) {
        $user_id = get_current_user_id();
    }
    
    return user_can($user_id, 'view_performance_data') || user_can($user_id, 'manage_options');
}

/**
 * Get user's assigned teams
 */
function bkgt_get_user_teams($user_id = null) {
    if (!$user_id) {
        $user_id = get_current_user_id();
    }
    
    // This will be implemented with the teams plugin
    // For now, return empty array
    $teams = get_user_meta($user_id, 'assigned_teams', true);
    return $teams ? $teams : array();
}

/**
 * Remove unnecessary admin bar items
 */
function bkgt_ledare_remove_admin_bar_items() {
    global $wp_admin_bar;
    
    // Remove items we don't need for internal system
    $wp_admin_bar->remove_node('wp-logo');
    $wp_admin_bar->remove_node('comments');
    $wp_admin_bar->remove_node('new-content');
}
add_action('admin_bar_menu', 'bkgt_ledare_remove_admin_bar_items', 999);

/**
 * Disable comments completely
 */
function bkgt_ledare_disable_comments() {
    // Close comments on the front-end
    add_filter('comments_open', '__return_false', 20, 2);
    add_filter('pings_open', '__return_false', 20, 2);
    
    // Hide existing comments
    add_filter('comments_array', '__return_empty_array', 10, 2);
    
    // Remove comments page in menu
    add_action('admin_menu', function() {
        remove_menu_page('edit-comments.php');
    });
    
    // Redirect any user trying to access comments page
    add_action('admin_init', function() {
        global $pagenow;
        if ($pagenow === 'edit-comments.php') {
            wp_redirect(admin_url());
            exit;
        }
    });
}
add_action('init', 'bkgt_ledare_disable_comments');

/**
 * Customize admin footer text
 */
function bkgt_ledare_admin_footer_text() {
    echo 'BKGTS American Football - Ledarsystem';
}
add_filter('admin_footer_text', 'bkgt_ledare_admin_footer_text');

/**
 * Disable post formats - we're not a blog
 */
function bkgt_ledare_disable_post_formats() {
    remove_theme_support('post-formats');
}
add_action('after_setup_theme', 'bkgt_ledare_disable_post_formats');

/**
 * Redirect users after login based on role
 */
function bkgt_ledare_login_redirect($redirect_to, $request, $user) {
    if (isset($user->roles) && is_array($user->roles)) {
        // Redirect to front page (dashboard) instead of admin
        if (!in_array('administrator', $user->roles)) {
            $redirect_to = home_url();
        }
    }
    return $redirect_to;
}
add_filter('login_redirect', 'bkgt_ledare_login_redirect', 10, 3);

/**
 * Hide admin bar for non-admins
 */
function bkgt_ledare_hide_admin_bar() {
    if (!current_user_can('administrator') && !is_admin()) {
        show_admin_bar(false);
    }
}
add_action('after_setup_theme', 'bkgt_ledare_hide_admin_bar');

/**
 * Require login for all frontend pages
 */
function bkgt_ledare_require_login() {
    // Don't require login for login page, admin area, or AJAX requests
    if (is_admin() || is_login() || wp_doing_ajax()) {
        return;
    }

    // Don't require login for REST API (needed for some plugins)
    if (defined('REST_REQUEST') && REST_REQUEST) {
        return;
    }

    // Check if user is logged in
    if (!is_user_logged_in()) {
        // Redirect to login page with return URL
        $login_url = wp_login_url(get_permalink());
        wp_redirect($login_url);
        exit;
    }
}
add_action('template_redirect', 'bkgt_ledare_require_login');

/**
 * Add viewport meta tag and other important meta tags
 */
function bkgt_ledare_add_meta_tags() {
    echo '<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">' . "\n";
    echo '<meta name="format-detection" content="telephone=no">' . "\n";
    echo '<meta name="theme-color" content="#1e40af">' . "\n"; // BKGT blue color
}
add_action('wp_head', 'bkgt_ledare_add_meta_tags', 1);
