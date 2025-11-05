<?php
/**
 * Plugin Name: BKGT Team & Player Management
 * Plugin URI: https://bkgt.se
 * Description: Team pages, player dossiers, and performance management for BKGTS.
 * Version: 1.0.0
 * Author: BKGT Amerikansk Fotboll
 * License: GPL v2 or later
 * Text Domain: bkgt-team-player
 * Requires Plugins: bkgt-core
 */

if (!defined('ABSPATH')) {
    exit;
}

define('BKGT_TP_VERSION', '1.0.0');
define('BKGT_TP_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('BKGT_TP_PLUGIN_URL', plugin_dir_url(__FILE__));

/**
 * Safe logging function that checks if bkgt_log exists
 * Fallback to error_log if bkgt_log is not available
 */
if (!function_exists('bkgt_log_safe')) {
    function bkgt_log_safe($level = 'info', $message = '', $data = array()) {
        if (function_exists('bkgt_log')) {
            bkgt_log($level, $message, $data);
        } else {
            // Fallback to error_log if bkgt_log is not available
            $log_message = '[BKGT-TP-' . strtoupper($level) . '] ' . $message;
            if (!empty($data)) {
                $log_message .= ' ' . json_encode($data);
            }
            error_log($log_message);
        }
    }
}

// Plugin activation hook
register_activation_hook(__FILE__, 'bkgt_team_player_activate');

function bkgt_team_player_activate() {
    if (!function_exists('bkgt_log')) {
        die(__('BKGT Core plugin must be activated first.', 'bkgt-team-player'));
    }
    bkgt_log('info', 'Team & Player Management plugin activated');
}

// Plugin deactivation hook
register_deactivation_hook(__FILE__, 'bkgt_team_player_deactivate');

function bkgt_team_player_deactivate() {
    if (function_exists('bkgt_log')) {
        bkgt_log('info', 'Team & Player Management plugin deactivated');
    }
}

/**
 * Main Plugin Class
 */
class BKGT_Team_Player_Management {

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('init', array($this, 'init'));
        add_action('plugins_loaded', array($this, 'load_textdomain'));
    }

    public function init() {
        // Register custom post types
        add_action('init', array($this, 'register_post_types'));

        // Add shortcodes
        add_shortcode('bkgt_team_page', array($this, 'team_page_shortcode'));
        add_shortcode('bkgt_player_dossier', array($this, 'player_dossier_shortcode'));
        add_shortcode('bkgt_performance_page', array($this, 'performance_page_shortcode'));
        add_shortcode('bkgt_team_overview', array($this, 'team_overview_shortcode'));
        add_shortcode('bkgt_players', array($this, 'players_shortcode'));
        add_shortcode('bkgt_events', array($this, 'events_shortcode'));

        // AJAX handlers
        add_action('wp_ajax_bkgt_save_player_note', array($this, 'ajax_save_player_note'));
        add_action('wp_ajax_bkgt_save_performance_rating', array($this, 'ajax_save_performance_rating'));
        add_action('wp_ajax_bkgt_get_player_stats', array($this, 'ajax_get_player_stats'));
        add_action('wp_ajax_bkgt_get_team_performance', array($this, 'ajax_get_team_performance'));
        add_action('wp_ajax_bkgt_get_team_players', array($this, 'ajax_get_team_players'));
        add_action('wp_ajax_bkgt_save_event', array($this, 'ajax_save_event'));
        add_action('wp_ajax_bkgt_delete_event', array($this, 'ajax_delete_event'));
        add_action('wp_ajax_bkgt_get_events', array($this, 'ajax_get_events'));
        add_action('wp_ajax_bkgt_toggle_event_status', array($this, 'ajax_toggle_event_status'));

        // Admin menus
        if (is_admin()) {
            add_action('admin_menu', array($this, 'add_admin_menu'));
            add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        }

        // Enqueue scripts and styles
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_scripts'));
    }

    public function load_textdomain() {
        load_plugin_textdomain('bkgt-team-player', false, dirname(plugin_basename(__FILE__)) . '/languages/');
    }

    public function register_post_types() {
        // Player Dossier post type
        register_post_type('bkgt_player', array(
            'labels' => array(
                'name' => __('Players', 'bkgt-team-player'),
                'singular_name' => __('Player', 'bkgt-team-player'),
                'add_new' => __('Add New Player', 'bkgt-team-player'),
                'add_new_item' => __('Add New Player', 'bkgt-team-player'),
                'edit_item' => __('Edit Player', 'bkgt-team-player'),
                'new_item' => __('New Player', 'bkgt-team-player'),
                'view_item' => __('View Player', 'bkgt-team-player'),
                'search_items' => __('Search Players', 'bkgt-team-player'),
                'not_found' => __('No players found', 'bkgt-team-player'),
                'not_found_in_trash' => __('No players found in trash', 'bkgt-team-player'),
            ),
            'public' => true,
            'has_archive' => true,
            'show_in_menu' => true,
            'supports' => array('title', 'editor', 'thumbnail'),
            'show_in_rest' => false,
        ));

        // Events post type
        register_post_type('bkgt_event', array(
            'labels' => array(
                'name' => __('Events', 'bkgt-team-player'),
                'singular_name' => __('Event', 'bkgt-team-player'),
                'add_new' => __('Add Event', 'bkgt-team-player'),
                'add_new_item' => __('Add New Event', 'bkgt-team-player'),
                'edit_item' => __('Edit Event', 'bkgt-team-player'),
                'new_item' => __('New Event', 'bkgt-team-player'),
                'view_item' => __('View Event', 'bkgt-team-player'),
                'search_items' => __('Search Events', 'bkgt-team-player'),
                'not_found' => __('No events found', 'bkgt-team-player'),
                'not_found_in_trash' => __('No events found in trash', 'bkgt-team-player'),
            ),
            'public' => false,
            'show_ui' => false,
            'show_in_menu' => false,
            'supports' => array('title', 'editor'),
            'show_in_rest' => false,
        ));

        // Event types taxonomy
        register_taxonomy('bkgt_event_type', 'bkgt_event', array(
            'labels' => array(
                'name' => __('Event Types', 'bkgt-team-player'),
                'singular_name' => __('Event Type', 'bkgt-team-player'),
            ),
            'hierarchical' => false,
            'public' => false,
            'show_ui' => false,
            'query_var' => false,
        ));
    }

    public function add_admin_menu() {
        add_menu_page(
            __('Team & Player Management', 'bkgt-team-player'),
            __('Teams & Players', 'bkgt-team-player'),
            'edit_posts',
            'bkgt-team-player',
            array($this, 'admin_page'),
            'dashicons-groups',
            25
        );

        add_submenu_page(
            'bkgt-team-player',
            __('All Teams', 'bkgt-team-player'),
            __('All Teams', 'bkgt-team-player'),
            'edit_posts',
            'edit.php?post_type=bkgt_team'
        );

        add_submenu_page(
            'bkgt-team-player',
            __('All Players', 'bkgt-team-player'),
            __('All Players', 'bkgt-team-player'),
            'edit_posts',
            'edit.php?post_type=bkgt_player'
        );

        add_submenu_page(
            'bkgt-team-player',
            __('Performance Ratings', 'bkgt-team-player'),
            __('Performance Ratings', 'bkgt-team-player'),
            'manage_options',
            'bkgt-performance-ratings',
            array($this, 'performance_admin_page')
        );

        add_submenu_page(
            'bkgt-team-player',
            __('Setup Pages', 'bkgt-team-player'),
            __('Setup Pages', 'bkgt-team-player'),
            'manage_options',
            'bkgt-setup-pages',
            array($this, 'setup_pages_admin_page')
        );
    }

    public function admin_page() {
        // Get current tab
        $current_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'overview';

        // Get data for overview with error handling
        global $wpdb;
        $total_teams = 0;
        $total_players = 0;
        $recent_ratings = 0;

        try {
            $total_teams = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}bkgt_teams");
            if ($wpdb->last_error) {
                error_log('BKGT Team Player: Database error getting team count: ' . $wpdb->last_error);
                add_settings_error('bkgt_team_player', 'db_error', __('Database error: Could not retrieve team count.', 'bkgt-team-player'), 'error');
            }

            $total_players = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}bkgt_players");
            if ($wpdb->last_error) {
                error_log('BKGT Team Player: Database error getting player count: ' . $wpdb->last_error);
                add_settings_error('bkgt_team_player', 'db_error', __('Database error: Could not retrieve player count.', 'bkgt-team-player'), 'error');
            }

            $recent_ratings = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}bkgt_performance_ratings WHERE created_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
            if ($wpdb->last_error) {
                error_log('BKGT Team Player: Database error getting recent ratings count: ' . $wpdb->last_error);
                add_settings_error('bkgt_team_player', 'db_error', __('Database error: Could not retrieve recent ratings count.', 'bkgt-team-player'), 'error');
            }
        } catch (Exception $e) {
            error_log('BKGT Team Player: Exception in admin_page: ' . $e->getMessage());
            add_settings_error('bkgt_team_player', 'exception_error', __('An unexpected error occurred while loading the dashboard.', 'bkgt-team-player'), 'error');
        }

        ?>
        <div class="wrap">
            <h1><?php _e('Team & Player Management', 'bkgt-team-player'); ?></h1>

            <?php settings_errors('bkgt_team_player'); ?>

            <!-- Tab Navigation -->
            <nav class="bkgt-tab-nav">
                <a href="<?php echo admin_url('admin.php?page=bkgt-team-player&tab=overview'); ?>"
                   class="nav-tab <?php echo $current_tab === 'overview' ? 'nav-tab-active' : ''; ?>"
                   data-tab="overview">
                    <span class="dashicons dashicons-dashboard"></span>
                    <?php _e('Översikt', 'bkgt-team-player'); ?>
                </a>
                <a href="<?php echo admin_url('admin.php?page=bkgt-team-player&tab=teams'); ?>"
                   class="nav-tab <?php echo $current_tab === 'teams' ? 'nav-tab-active' : ''; ?>"
                   data-tab="teams">
                    <span class="dashicons dashicons-groups"></span>
                    <?php _e('Lag', 'bkgt-team-player'); ?>
                </a>
                <a href="<?php echo admin_url('admin.php?page=bkgt-team-player&tab=players'); ?>"
                   class="nav-tab <?php echo $current_tab === 'players' ? 'nav-tab-active' : ''; ?>"
                   data-tab="players">
                    <span class="dashicons dashicons-admin-users"></span>
                    <?php _e('Spelare', 'bkgt-team-player'); ?>
                </a>
                <a href="<?php echo admin_url('admin.php?page=bkgt-team-player&tab=events'); ?>"
                   class="nav-tab <?php echo $current_tab === 'events' ? 'nav-tab-active' : ''; ?>"
                   data-tab="events">
                    <span class="dashicons dashicons-calendar-alt"></span>
                    <?php _e('Matcher & Träningar', 'bkgt-team-player'); ?>
                </a>
                <a href="<?php echo admin_url('admin.php?page=bkgt-team-player&tab=performance'); ?>"
                   class="nav-tab <?php echo $current_tab === 'performance' ? 'nav-tab-active' : ''; ?>"
                   data-tab="performance">
                    <span class="dashicons dashicons-chart-bar"></span>
                    <?php _e('Prestanda', 'bkgt-team-player'); ?>
                </a>
                <a href="<?php echo admin_url('admin.php?page=bkgt-team-player&tab=settings'); ?>"
                   class="nav-tab <?php echo $current_tab === 'settings' ? 'nav-tab-active' : ''; ?>"
                   data-tab="settings">
                    <span class="dashicons dashicons-admin-settings"></span>
                    <?php _e('Inställningar', 'bkgt-team-player'); ?>
                </a>
            </nav>

            <div class="bkgt-tab-content">
                <?php
                try {
                    switch ($current_tab) {
                        case 'overview':
                            $this->render_overview_tab($total_teams, $total_players, $recent_ratings);
                            break;
                        case 'teams':
                            $this->render_teams_tab();
                            break;
                        case 'players':
                            $this->render_players_tab();
                            break;
                        case 'events':
                            $this->render_events_tab();
                            break;
                        case 'performance':
                            $this->render_performance_tab();
                            break;
                        case 'settings':
                            $this->render_settings_tab();
                            break;
                        default:
                            $this->render_overview_tab($total_teams, $total_players, $recent_ratings);
                    }
                } catch (Exception $e) {
                    error_log('BKGT Team Player: Exception rendering tab ' . $current_tab . ': ' . $e->getMessage());
                    echo '<div class="notice notice-error"><p>' . __('An error occurred while loading this section. Please try again or contact support.', 'bkgt-team-player') . '</p></div>';
                }
                ?>
            </div>
        </div>
        <?php
    }

    /**
     * Render Overview Tab
     */
    private function render_overview_tab($total_teams, $total_players, $recent_ratings) {
        ?>
        <div id="overview-tab" class="bkgt-overview-tab">
            <!-- Metric Cards -->
            <div class="bkgt-metrics-grid">
                <div class="bkgt-metric-card">
                    <div class="bkgt-metric-icon">
                        <span class="dashicons dashicons-groups"></span>
                    </div>
                    <div class="bkgt-metric-content">
                        <h3><?php echo esc_html($total_teams); ?></h3>
                        <p><?php _e('Aktiva Lag', 'bkgt-team-player'); ?></p>
                    </div>
                </div>

                <div class="bkgt-metric-card">
                    <div class="bkgt-metric-icon">
                        <span class="dashicons dashicons-admin-users"></span>
                    </div>
                    <div class="bkgt-metric-content">
                        <h3><?php echo esc_html($total_players); ?></h3>
                        <p><?php _e('Registrerade Spelare', 'bkgt-team-player'); ?></p>
                    </div>
                </div>

                <div class="bkgt-metric-card">
                    <div class="bkgt-metric-icon">
                        <span class="dashicons dashicons-star-filled"></span>
                    </div>
                    <div class="bkgt-metric-content">
                        <h3><?php echo esc_html($recent_ratings); ?></h3>
                        <p><?php _e('Spelarutvärderingar (senaste veckan)', 'bkgt-team-player'); ?></p>
                    </div>
                </div>

                <div class="bkgt-metric-card">
                    <div class="bkgt-metric-icon">
                        <span class="dashicons dashicons-calendar-alt"></span>
                    </div>
                    <div class="bkgt-metric-content">
                        <h3><?php _e('Kommande', 'bkgt-team-player'); ?></h3>
                        <p><?php _e('Matcher & Träningar', 'bkgt-team-player'); ?></p>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="bkgt-quick-actions">
                <h3><?php _e('Snabbåtgärder', 'bkgt-team-player'); ?></h3>
                <div class="bkgt-action-buttons">
                    <a href="<?php echo admin_url('post-new.php?post_type=bkgt_player'); ?>" class="button button-primary">
                        <span class="dashicons dashicons-plus"></span>
                        <?php _e('Lägg till Spelare', 'bkgt-team-player'); ?>
                    </a>
                    <a href="<?php echo admin_url('post-new.php?post_type=bkgt_team'); ?>" class="button button-secondary">
                        <span class="dashicons dashicons-plus"></span>
                        <?php _e('Skapa Nytt Lag', 'bkgt-team-player'); ?>
                    </a>
                    <a href="<?php echo admin_url('admin.php?page=bkgt-team-player&tab=events'); ?>" class="button button-secondary">
                        <span class="dashicons dashicons-calendar"></span>
                        <?php _e('Hantera Matcher', 'bkgt-team-player'); ?>
                    </a>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="bkgt-recent-activity">
                <h3><?php _e('Senaste Aktivitet', 'bkgt-team-player'); ?></h3>
                <div class="bkgt-activity-feed">
                    <div class="bkgt-activity-item">
                        <span class="dashicons dashicons-admin-users"></span>
                        <span><?php _e('Nya spelare importerade från Svenskalag.se', 'bkgt-team-player'); ?></span>
                        <small><?php _e('2 timmar sedan', 'bkgt-team-player'); ?></small>
                    </div>
                    <div class="bkgt-activity-item">
                        <span class="dashicons dashicons-star-filled"></span>
                        <span><?php _e('Prestandabetyg uppdaterade för Damlaget', 'bkgt-team-player'); ?></span>
                        <small><?php _e('1 dag sedan', 'bkgt-team-player'); ?></small>
                    </div>
                    <div class="bkgt-activity-item">
                        <span class="dashicons dashicons-calendar-alt"></span>
                        <span><?php _e('Träning schemalagd för U17', 'bkgt-team-player'); ?></span>
                        <small><?php _e('2 dagar sedan', 'bkgt-team-player'); ?></small>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Render Teams Tab
     */
    private function render_teams_tab() {
        ?>
        <div id="teams-tab" class="bkgt-teams-tab">
            <div class="bkgt-tab-header">
                <h2><?php _e('Laghantering', 'bkgt-team-player'); ?></h2>
                <a href="<?php echo admin_url('post-new.php?post_type=bkgt_team'); ?>" class="button button-primary">
                    <span class="dashicons dashicons-plus"></span>
                    <?php _e('Lägg till Nytt Lag', 'bkgt-team-player'); ?>
                </a>
            </div>

            <div class="bkgt-teams-grid">
                <?php
                $teams = get_posts(array(
                    'post_type' => 'bkgt_team',
                    'posts_per_page' => -1,
                    'post_status' => 'publish'
                ));

                if (empty($teams)) {
                    echo '<p>' . __('Inga lag hittades. Skapa ditt första lag för att komma igång.', 'bkgt-team-player') . '</p>';
                } else {
                    foreach ($teams as $team) {
                        $this->render_team_card($team);
                    }
                }
                ?>
            </div>
        </div>
        <?php
    }

    /**
     * Render team card
     */
    private function render_team_card($team) {
        global $wpdb;
        $player_count = 0;

        try {
            $player_count = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}bkgt_players WHERE team_id = %d",
                $team->ID
            ));

            if ($wpdb->last_error) {
                error_log('BKGT Team Player: Database error getting player count for team ' . $team->ID . ': ' . $wpdb->last_error);
                $player_count = 0; // Default to 0 on error
            }
        } catch (Exception $e) {
            error_log('BKGT Team Player: Exception getting player count for team ' . $team->ID . ': ' . $e->getMessage());
            $player_count = 0;
        }

        ?>
        <div class="bkgt-team-card">
            <div class="bkgt-team-header">
                <h3><?php echo esc_html($team->post_title); ?></h3>
                <span class="bkgt-player-count">
                    <span class="dashicons dashicons-admin-users"></span>
                    <?php printf(_n('%d spelare', '%d spelare', $player_count, 'bkgt-team-player'), $player_count); ?>
                </span>
            </div>

            <div class="bkgt-team-actions">
                <a href="<?php echo get_permalink($team->ID); ?>" class="button button-small" target="_blank">
                    <span class="dashicons dashicons-visibility"></span>
                    <?php _e('Visa', 'bkgt-team-player'); ?>
                </a>
                <a href="<?php echo admin_url('post.php?post=' . $team->ID . '&action=edit'); ?>" class="button button-small">
                    <span class="dashicons dashicons-edit"></span>
                    <?php _e('Redigera', 'bkgt-team-player'); ?>
                </a>
            </div>
        </div>
        <?php
    }

    /**
     * Render Players Tab
     */
    private function render_players_tab() {
        ?>
        <div id="players-tab" class="bkgt-players-tab">
            <div class="bkgt-tab-header">
                <h2><?php _e('Spelarhantering', 'bkgt-team-player'); ?></h2>
                <a href="<?php echo admin_url('post-new.php?post_type=bkgt_player'); ?>" class="button button-primary">
                    <span class="dashicons dashicons-plus"></span>
                    <?php _e('Lägg till Ny Spelare', 'bkgt-team-player'); ?>
                </a>
            </div>

            <div class="bkgt-players-grid">
                <?php
                $players = get_posts(array(
                    'post_type' => 'bkgt_player',
                    'posts_per_page' => 20,
                    'post_status' => 'publish'
                ));

                if (empty($players)) {
                    echo '<p>' . __('Inga spelare hittades. Lägg till dina första spelare för att komma igång.', 'bkgt-team-player') . '</p>';
                } else {
                    foreach ($players as $player) {
                        $this->render_player_card($player);
                    }
                }
                ?>
            </div>
        </div>
        <?php
    }

    /**
     * Render player card
     */
    private function render_player_card($player) {
        $position = get_post_meta($player->ID, '_bkgt_position', true);
        $jersey_number = get_post_meta($player->ID, '_bkgt_jersey_number', true);

        ?>
        <div class="bkgt-player-card">
            <div class="bkgt-player-avatar">
                <span class="dashicons dashicons-admin-users"></span>
            </div>

            <div class="bkgt-player-info">
                <h4><?php echo esc_html($player->post_title); ?></h4>
                <div class="bkgt-player-meta">
                    <?php if ($position): ?>
                        <span class="bkgt-position"><?php echo esc_html($position); ?></span>
                    <?php endif; ?>
                    <?php if ($jersey_number): ?>
                        <span class="bkgt-jersey">#<?php echo esc_html($jersey_number); ?></span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="bkgt-player-actions">
                <a href="<?php echo get_permalink($player->ID); ?>" class="button button-small" target="_blank">
                    <span class="dashicons dashicons-visibility"></span>
                </a>
                <a href="<?php echo admin_url('post.php?post=' . $player->ID . '&action=edit'); ?>" class="button button-small">
                    <span class="dashicons dashicons-edit"></span>
                </a>
            </div>
        </div>
        <?php
    }

    /**
     * Render Events Tab
     */
    private function render_events_tab() {
        ?>
        <div id="events-tab" class="bkgt-events-tab">
            <div class="bkgt-tab-header">
                <h2><?php _e('Matcher & Träningar', 'bkgt-team-player'); ?></h2>
                <button class="button button-primary" id="bkgt-add-event-btn">
                    <span class="dashicons dashicons-plus"></span>
                    <?php _e('Schemalägg Event', 'bkgt-team-player'); ?>
                </button>
            </div>

            <!-- Event creation form (hidden by default) -->
            <div id="bkgt-event-form-container" class="bkgt-event-form-container" style="display: none;">
                <?php $this->render_event_form(); ?>
            </div>

            <!-- Events list table -->
            <table class="bkgt-events-table wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th class="column-type"><?php _e('Type', 'bkgt-team-player'); ?></th>
                        <th class="column-title"><?php _e('Event', 'bkgt-team-player'); ?></th>
                        <th class="column-date"><?php _e('Date & Time', 'bkgt-team-player'); ?></th>
                        <th class="column-location"><?php _e('Location', 'bkgt-team-player'); ?></th>
                        <th class="column-status"><?php _e('Status', 'bkgt-team-player'); ?></th>
                        <th class="column-actions"><?php _e('Actions', 'bkgt-team-player'); ?></th>
                    </tr>
                </thead>
                <tbody id="bkgt-events-tbody">
                    <?php $this->render_events_list(); ?>
                </tbody>
            </table>
        </div>

        <script>
        jQuery(document).ready(function($) {
            // Show/hide event form
            $('#bkgt-add-event-btn').on('click', function(e) {
                e.preventDefault();
                $('#bkgt-event-form-container').slideToggle();
                $('input[name="event_id"]').val('');
                $('#bkgt-event-form')[0].reset();
            });

            $('#bkgt-cancel-event').on('click', function(e) {
                e.preventDefault();
                $('#bkgt-event-form-container').slideUp();
            });

            // Event form submission
            $('#bkgt-event-form').on('submit', function(e) {
                e.preventDefault();

                var formData = {
                    action: 'bkgt_save_event',
                    event_id: $('input[name="event_id"]').val(),
                    event_title: $('input[name="event_title"]').val(),
                    event_type: $('select[name="event_type"]').val(),
                    event_date: $('input[name="event_date"]').val(),
                    event_time: $('input[name="event_time"]').val(),
                    event_location: $('input[name="event_location"]').val(),
                    event_opponent: $('input[name="event_opponent"]').val(),
                    event_notes: $('textarea[name="event_notes"]').val(),
                    nonce: '<?php echo wp_create_nonce('bkgt_save_event'); ?>'
                };

                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: formData,
                    success: function(response) {
                        if (response.success) {
                            alert('<?php _e('Event saved successfully!', 'bkgt-team-player'); ?>');
                            $('#bkgt-event-form-container').slideUp();
                            location.reload();
                        } else {
                            alert('<?php _e('Error:', 'bkgt-team-player'); ?> ' + response.data.message);
                        }
                    }
                });
            });

            // Delete event
            $(document).on('click', '.bkgt-event-delete', function(e) {
                e.preventDefault();
                if (!confirm('<?php _e('Are you sure?', 'bkgt-team-player'); ?>')) return;

                var eventId = $(this).data('event-id');
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'bkgt_delete_event',
                        event_id: eventId,
                        nonce: '<?php echo wp_create_nonce('bkgt_delete_event'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            location.reload();
                        } else {
                            alert('<?php _e('Error deleting event', 'bkgt-team-player'); ?>');
                        }
                    }
                });
            });

            // Toggle event status
            $(document).on('click', '.bkgt-event-toggle-status', function(e) {
                e.preventDefault();
                var eventId = $(this).data('event-id');
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'bkgt_toggle_event_status',
                        event_id: eventId,
                        nonce: '<?php echo wp_create_nonce('bkgt_toggle_event_status'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            location.reload();
                        }
                    }
                });
            });

            // Edit event
            $(document).on('click', '.bkgt-event-edit', function(e) {
                e.preventDefault();
                var eventId = $(this).data('event-id');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'bkgt_get_events',
                        event_id: eventId,
                        nonce: '<?php echo wp_create_nonce('bkgt_get_events'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            var event = response.data;
                            $('input[name="event_id"]').val(event.ID);
                            $('input[name="event_title"]').val(event.post_title);
                            $('select[name="event_type"]').val(event.event_type);
                            $('input[name="event_date"]').val(event.event_date);
                            $('input[name="event_time"]').val(event.event_time);
                            $('input[name="event_location"]').val(event.event_location);
                            $('input[name="event_opponent"]').val(event.event_opponent);
                            $('textarea[name="event_notes"]').val(event.event_notes);
                            
                            $('#bkgt-event-form-container').slideDown();
                            $('html, body').animate({scrollTop: $('#bkgt-event-form-container').offset().top}, 500);
                        }
                    }
                });
            });
        });
        </script>
        <?php
    }

    /**
     * Render event form
     */
    private function render_event_form() {
        ?>
        <div class="bkgt-event-form">
            <h3><?php _e('Lägg till eller redigera event', 'bkgt-team-player'); ?></h3>
            <form id="bkgt-event-form" class="bkgt-form-container" data-validate>
                <?php wp_nonce_field('bkgt_save_event', 'bkgt_event_nonce'); ?>
                <input type="hidden" name="event_id" value="">

                <div class="bkgt-form-row">
                    <label for="event_title"><?php _e('Event Title', 'bkgt-team-player'); ?> <span class="bkgt-required-indicator">*</span></label>
                    <input type="text" 
                           id="event_title" 
                           name="event_title" 
                           data-validate-type="text"
                           data-validate-required="true"
                           data-validate-min-length="3"
                           data-validate-max-length="150"
                           required>
                </div>

                <div class="bkgt-form-row">
                    <label for="event_type"><?php _e('Event Type', 'bkgt-team-player'); ?> <span class="bkgt-required-indicator">*</span></label>
                    <select id="event_type" 
                            name="event_type" 
                            data-validate-type="select"
                            data-validate-required="true"
                            required>
                        <option value="">-- Välj typ --</option>
                        <option value="match"><?php _e('Match', 'bkgt-team-player'); ?></option>
                        <option value="training"><?php _e('Training', 'bkgt-team-player'); ?></option>
                        <option value="meeting"><?php _e('Meeting', 'bkgt-team-player'); ?></option>
                    </select>
                </div>

                <div class="bkgt-form-row">
                    <label for="event_date"><?php _e('Date', 'bkgt-team-player'); ?> <span class="bkgt-required-indicator">*</span></label>
                    <input type="date" 
                           id="event_date" 
                           name="event_date" 
                           data-validate-type="date"
                           data-validate-required="true"
                           required>
                </div>

                <div class="bkgt-form-row">
                    <label for="event_time"><?php _e('Time', 'bkgt-team-player'); ?> <span class="bkgt-required-indicator">*</span></label>
                    <input type="time" 
                           id="event_time" 
                           name="event_time" 
                           data-validate-type="time"
                           data-validate-required="true"
                           required>
                </div>

                <div class="bkgt-form-row">
                    <label for="event_location"><?php _e('Location', 'bkgt-team-player'); ?></label>
                    <input type="text" 
                           id="event_location" 
                           name="event_location" 
                           placeholder="<?php _e('e.g., Söderstadion', 'bkgt-team-player'); ?>"
                           data-validate-type="text"
                           data-validate-max-length="200">
                </div>

                <div class="bkgt-form-row">
                    <label for="event_opponent"><?php _e('Opponent/Team', 'bkgt-team-player'); ?></label>
                    <input type="text" 
                           id="event_opponent" 
                           name="event_opponent" 
                           placeholder="<?php _e('e.g., Stockholm United', 'bkgt-team-player'); ?>"
                           data-validate-type="text"
                           data-validate-max-length="200">
                </div>

                <div class="bkgt-form-row">
                    <label for="event_notes"><?php _e('Notes', 'bkgt-team-player'); ?></label>
                    <textarea id="event_notes" 
                              name="event_notes" 
                              rows="4"
                              data-validate-type="text"
                              data-validate-max-length="1000"></textarea>
                </div>

                <div class="bkgt-form-actions">
                    <button type="submit" class="button button-primary"><?php _e('Save Event', 'bkgt-team-player'); ?></button>
                    <button type="button" class="button" id="bkgt-cancel-event"><?php _e('Cancel', 'bkgt-team-player'); ?></button>
                </div>
            </form>
        </div>
        <?php
    }

    /**
     * Render events list
     */
    private function render_events_list() {
        $args = array(
            'post_type' => 'bkgt_event',
            'posts_per_page' => 50,
            'orderby' => 'meta_value',
            'meta_key' => '_bkgt_event_date',
            'order' => 'ASC',
        );

        $events = get_posts($args);

        if (empty($events)) {
            echo '<tr><td colspan="6" style="text-align: center; padding: 20px;">' . __('No events yet. Click "Schemalägg Event" to create one.', 'bkgt-team-player') . '</td></tr>';
            return;
        }

        foreach ($events as $event) {
            $event_date = get_post_meta($event->ID, '_bkgt_event_date', true);
            $event_time = get_post_meta($event->ID, '_bkgt_event_time', true);
            $event_type = get_post_meta($event->ID, '_bkgt_event_type', true);
            $event_location = get_post_meta($event->ID, '_bkgt_event_location', true);
            $event_status = get_post_meta($event->ID, '_bkgt_event_status', true) ?: 'scheduled';
            
            $date_display = $event_date && $event_time ? sprintf('%s %s', $event_date, $event_time) : __('Unscheduled', 'bkgt-team-player');
            $type_display = $event_type === 'match' ? __('Match', 'bkgt-team-player') : ($event_type === 'training' ? __('Training', 'bkgt-team-player') : __('Meeting', 'bkgt-team-player'));
            $status_display = $event_status === 'scheduled' ? __('Scheduled', 'bkgt-team-player') : ($event_status === 'cancelled' ? __('Cancelled', 'bkgt-team-player') : __('Completed', 'bkgt-team-player'));
            ?>
            <tr class="bkgt-event-row" data-event-id="<?php echo $event->ID; ?>">
                <td><?php echo esc_html($type_display); ?></td>
                <td><strong><?php echo esc_html($event->post_title); ?></strong></td>
                <td><?php echo esc_html($date_display); ?></td>
                <td><?php echo esc_html($event_location ?: '—'); ?></td>
                <td><?php echo esc_html($status_display); ?></td>
                <td>
                    <a href="#" class="bkgt-event-edit" data-event-id="<?php echo $event->ID; ?>"><?php _e('Edit', 'bkgt-team-player'); ?></a> |
                    <a href="#" class="bkgt-event-delete" data-event-id="<?php echo $event->ID; ?>"><?php _e('Delete', 'bkgt-team-player'); ?></a> |
                    <a href="#" class="bkgt-event-toggle-status" data-event-id="<?php echo $event->ID; ?>"><?php _e('Toggle Status', 'bkgt-team-player'); ?></a>
                </td>
            </tr>
            <?php
        }
    }

    /**
     * Render Performance Tab
     */
    private function render_performance_tab() {
        ?>
        <div id="performance-tab" class="bkgt-performance-tab">
            <div class="bkgt-tab-header">
                <h2><?php _e('Prestandahantering', 'bkgt-team-player'); ?></h2>
                <a href="<?php echo admin_url('admin.php?page=bkgt-performance-ratings'); ?>" class="button button-primary">
                    <span class="dashicons dashicons-chart-bar"></span>
                    <?php _e('Hantera Betyg', 'bkgt-team-player'); ?>
                </a>
            </div>

            <div class="bkgt-performance-overview">
                <p><?php _e('Hantera konfidentiella prestandabetyg för spelare. Denna data är endast synlig för tränare och styrelsemedlemmar.', 'bkgt-team-player'); ?></p>

                <div class="bkgt-performance-stats">
                    <?php
                    global $wpdb;
                    $total_ratings = 0;
                    $avg_rating = 0;

                    try {
                        $total_ratings = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}bkgt_performance_ratings");
                        if ($wpdb->last_error) {
                            error_log('BKGT Team Player: Database error getting total ratings: ' . $wpdb->last_error);
                            $total_ratings = 0;
                        }

                        $avg_rating = $wpdb->get_var("SELECT AVG(overall_rating) FROM {$wpdb->prefix}bkgt_performance_ratings");
                        if ($wpdb->last_error) {
                            error_log('BKGT Team Player: Database error getting average rating: ' . $wpdb->last_error);
                            $avg_rating = 0;
                        }
                    } catch (Exception $e) {
                        error_log('BKGT Team Player: Exception getting performance stats: ' . $e->getMessage());
                        $total_ratings = 0;
                        $avg_rating = 0;
                    }
                    ?>
                    <div class="bkgt-stat-card">
                        <h4><?php echo esc_html($total_ratings); ?></h4>
                        <p><?php _e('Totala Betyg', 'bkgt-team-player'); ?></p>
                    </div>
                    <div class="bkgt-stat-card">
                        <h4><?php echo $avg_rating ? number_format($avg_rating, 1) : '0.0'; ?>/10</h4>
                        <p><?php _e('Genomsnittsbetyg', 'bkgt-team-player'); ?></p>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Render Settings Tab
     */
    private function render_settings_tab() {
        ?>
        <div id="settings-tab" class="bkgt-settings-tab">
            <div class="bkgt-tab-header">
                <h2><?php _e('Inställningar', 'bkgt-team-player'); ?></h2>
            </div>

            <div class="bkgt-settings-content">
                <div class="bkgt-settings-section">
                    <h3><?php _e('Datainhämtning', 'bkgt-team-player'); ?></h3>
                    <p><?php _e('Konfigurera automatisk inhämtning av data från svenskalag.se', 'bkgt-team-player'); ?></p>
                    <button class="button button-secondary" disabled>
                        <?php _e('Konfigurera Skrapning', 'bkgt-team-player'); ?>
                    </button>
                </div>

                <div class="bkgt-settings-section">
                    <h3><?php _e('Användarinställningar', 'bkgt-team-player'); ?></h3>
                    <p><?php _e('Anpassa dashboard och aviseringsinställningar', 'bkgt-team-player'); ?></p>
                    <button class="button button-secondary" disabled>
                        <?php _e('Anpassa Dashboard', 'bkgt-team-player'); ?>
                    </button>
                </div>
            </div>
        </div>
        <?php
    }

    public function performance_admin_page() {
        // Check permissions - only admins and coaches
        if (!current_user_can('manage_options') && !current_user_can('edit_posts')) {
            wp_die(__('You do not have permission to access this page.', 'bkgt-team-player'));
        }

        global $wpdb;

        // Get all teams
        $teams = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}bkgt_teams ORDER BY name ASC");

        ?>
        <div class="wrap">
            <h1><?php _e('Performance Ratings Management', 'bkgt-team-player'); ?></h1>

            <div class="bkgt-performance-admin">
                <p><?php _e('Manage confidential performance ratings for players. This data is only visible to coaches and board members.', 'bkgt-team-player'); ?></p>

                <div class="bkgt-team-selector">
                    <label for="performance_team"><?php _e('Select Team:', 'bkgt-team-player'); ?></label>
                    <select id="performance_team">
                        <option value=""><?php _e('Choose a team...', 'bkgt-team-player'); ?></option>
                        <?php foreach ($teams as $team) : ?>
                            <option value="<?php echo esc_attr($team->id); ?>"><?php echo esc_html($team->name); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div id="performance-ratings-container" style="display: none;">
                    <!-- AJAX loaded content -->
                </div>
            </div>
        </div>

        <script>
        jQuery(document).ready(function($) {
            $('#performance_team').on('change', function() {
                var teamId = $(this).val();
                if (teamId) {
                    loadTeamPerformance(teamId);
                } else {
                    $('#performance-ratings-container').hide();
                }
            });

            function loadTeamPerformance(teamId) {
                $('#performance-ratings-container').html('<p><?php _e('Loading...', 'bkgt-team-player'); ?></p>').show();

                $.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    type: 'POST',
                    data: {
                        action: 'bkgt_get_team_performance',
                        team_id: teamId,
                        nonce: '<?php echo wp_create_nonce('bkgt_performance_nonce'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            $('#performance-ratings-container').html(response.data.html);
                        } else {
                            $('#performance-ratings-container').html('<p><?php _e('Error loading performance data.', 'bkgt-team-player'); ?></p>');
                        }
                    },
                    error: function() {
                        $('#performance-ratings-container').html('<p><?php _e('Error loading performance data.', 'bkgt-team-player'); ?></p>');
                    }
                });
            }
        });
        </script>
        <?php
    }

    public function enqueue_frontend_scripts() {
        if (is_page() || is_single()) {
            wp_enqueue_style('bkgt-team-player-style', BKGT_TP_PLUGIN_URL . 'assets/css/frontend.css', array(), BKGT_TP_VERSION);
            wp_enqueue_script('bkgt-team-player-script', BKGT_TP_PLUGIN_URL . 'assets/js/frontend.js', array('jquery'), BKGT_TP_VERSION, true);

            wp_localize_script('bkgt-team-player-script', 'bkgt_tp_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('bkgt_team_player_nonce')
            ));
        }
    }

    public function enqueue_admin_scripts($hook) {
        if ('toplevel_page_bkgt-team-player' === $hook) {
            wp_enqueue_style('bkgt-admin-dashboard', BKGT_TP_PLUGIN_URL . 'assets/css/admin-dashboard.css', array(), BKGT_TP_VERSION);
            wp_enqueue_script('bkgt-admin-dashboard', BKGT_TP_PLUGIN_URL . 'assets/js/admin-dashboard.js', array('jquery'), BKGT_TP_VERSION, true);

            wp_localize_script('bkgt-admin-dashboard', 'bkgt_admin_dashboard', array(
                'loading' => __('Laddar...', 'bkgt-team-player'),
                'error' => __('Ett fel uppstod', 'bkgt-team-player'),
                'success' => __('Lyckades', 'bkgt-team-player'),
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('bkgt_admin_dashboard_nonce')
            ));
        }
    }

    // Shortcode implementations will be added next
    public function team_page_shortcode($atts) {
        global $wpdb;

        $atts = shortcode_atts(array(
            'team' => '',
            'show_roster' => 'true',
            'show_stats' => 'true'
        ), $atts);

        $output = '<div class="bkgt-team-page">';

        try {
            if (!empty($atts['team'])) {
                // Get specific team
                $team = $wpdb->get_row($wpdb->prepare(
                    "SELECT * FROM {$wpdb->prefix}bkgt_teams WHERE slug = %s",
                    $atts['team']
                ));

                if ($wpdb->last_error) {
                    error_log('BKGT Team Player: Database error getting team by slug: ' . $wpdb->last_error);
                    $output .= '<p>' . __('Error loading team information.', 'bkgt-team-player') . '</p>';
                } elseif ($team) {
                    $output .= '<h2>' . esc_html($team->name) . '</h2>';
                    if (!empty($team->description)) {
                        $output .= '<p class="team-description">' . esc_html($team->description) . '</p>';
                    }

                    if ($atts['show_roster'] === 'true') {
                        $output .= $this->get_team_roster($team->id);
                    }

                    if ($atts['show_stats'] === 'true') {
                        $output .= $this->get_team_stats($team->id);
                    }
                } else {
                    $output .= '<p>' . __('Team not found.', 'bkgt-team-player') . '</p>';
                }
            } else {
                // Show all teams
                $teams = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}bkgt_teams ORDER BY name ASC");

                if ($wpdb->last_error) {
                    error_log('BKGT Team Player: Database error getting all teams: ' . $wpdb->last_error);
                    $output .= '<p>' . __('Error loading teams.', 'bkgt-team-player') . '</p>';
                } else {
                    $output .= '<h2>' . __('Our Teams', 'bkgt-team-player') . '</h2>';
                    $output .= '<div class="bkgt-teams-grid">';

                    foreach ($teams as $team) {
                        $output .= '<div class="bkgt-team-card">';
                        $output .= '<h3><a href="' . esc_url(add_query_arg('team', $team->slug)) . '">' . esc_html($team->name) . '</a></h3>';
                        if (!empty($team->description)) {
                            $output .= '<p>' . esc_html($team->description) . '</p>';
                        }
                        $output .= '</div>';
                    }

                    $output .= '</div>';
                }
            }
        } catch (Exception $e) {
            error_log('BKGT Team Player: Exception in team_page_shortcode: ' . $e->getMessage());
            $output .= '<p>' . __('An error occurred while loading team information. Please try again later.', 'bkgt-team-player') . '</p>';
        }

        $output .= '</div>';
        return $output;
    }

    private function get_team_roster($team_id) {
        global $wpdb;

        try {
            $players = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}bkgt_players
                 WHERE team_id = %d
                 ORDER BY jersey_number ASC, last_name ASC",
                $team_id
            ));

            if ($wpdb->last_error) {
                error_log('BKGT Team Player: Database error getting team roster for team ' . $team_id . ': ' . $wpdb->last_error);
                return '<h3>' . __('Team Roster', 'bkgt-team-player') . '</h3><p>' . __('Error loading roster.', 'bkgt-team-player') . '</p>';
            }

            if (empty($players)) {
                return '<h3>' . __('Team Roster', 'bkgt-team-player') . '</h3><p>' . __('No players found.', 'bkgt-team-player') . '</p>';
            }

            $output = '<h3>' . __('Team Roster', 'bkgt-team-player') . '</h3>';
            $output .= '<div class="bkgt-roster">';
            $output .= '<table class="bkgt-roster-table">';
            $output .= '<thead><tr><th>' . __('Jersey', 'bkgt-team-player') . '</th><th>' . __('Name', 'bkgt-team-player') . '</th><th>' . __('Position', 'bkgt-team-player') . '</th></tr></thead>';
            $output .= '<tbody>';

            foreach ($players as $player) {
                $output .= '<tr>';
                $output .= '<td>' . (empty($player->jersey_number) ? '-' : esc_html($player->jersey_number)) . '</td>';
                $output .= '<td><a href="' . esc_url(add_query_arg('player', $player->id)) . '">' . esc_html($player->first_name . ' ' . $player->last_name) . '</a></td>';
                $output .= '<td>' . (empty($player->position) ? '-' : esc_html($player->position)) . '</td>';
                $output .= '</tr>';
            }

            $output .= '</tbody></table></div>';
            return $output;
        } catch (Exception $e) {
            error_log('BKGT Team Player: Exception getting team roster for team ' . $team_id . ': ' . $e->getMessage());
            return '<h3>' . __('Team Roster', 'bkgt-team-player') . '</h3><p>' . __('Error loading roster.', 'bkgt-team-player') . '</p>';
        }
    }

    private function get_team_stats($team_id) {
        global $wpdb;

        try {
            // Get team performance summary
            $stats = $wpdb->get_row($wpdb->prepare(
                "SELECT
                    COUNT(DISTINCT p.id) as total_players,
                    AVG(r.overall_rating) as avg_rating,
                    COUNT(r.id) as total_ratings
                 FROM {$wpdb->prefix}bkgt_players p
                 LEFT JOIN {$wpdb->prefix}bkgt_performance_ratings r ON p.id = r.player_id
                 WHERE p.team_id = %d",
                $team_id
            ));

            if ($wpdb->last_error) {
                error_log('BKGT Team Player: Database error getting team stats for team ' . $team_id . ': ' . $wpdb->last_error);
                return '<h3>' . __('Team Statistics', 'bkgt-team-player') . '</h3><p>' . __('Error loading statistics.', 'bkgt-team-player') . '</p>';
            }

            if (!$stats) {
                return '<h3>' . __('Team Statistics', 'bkgt-team-player') . '</h3><p>' . __('No statistics available.', 'bkgt-team-player') . '</p>';
            }

            $output = '<h3>' . __('Team Statistics', 'bkgt-team-player') . '</h3>';
            $output .= '<div class="bkgt-team-stats">';
            $output .= '<p><strong>' . __('Total Players:', 'bkgt-team-player') . '</strong> ' . esc_html($stats->total_players) . '</p>';

            if ($stats->total_ratings > 0) {
                $output .= '<p><strong>' . __('Average Performance Rating:', 'bkgt-team-player') . '</strong> ' . number_format($stats->avg_rating, 1) . '/5.0</p>';
            }

            $output .= '</div>';
            return $output;
        } catch (Exception $e) {
            error_log('BKGT Team Player: Exception getting team stats for team ' . $team_id . ': ' . $e->getMessage());
            return '<h3>' . __('Team Statistics', 'bkgt-team-player') . '</h3><p>' . __('Error loading statistics.', 'bkgt-team-player') . '</p>';
        }
    }

    public function player_dossier_shortcode($atts) {
        global $wpdb;

        try {
            $atts = shortcode_atts(array(
                'player' => '',
                'show_stats' => 'true',
                'show_notes' => 'false'
            ), $atts);

            $output = '<div class="bkgt-player-dossier">';

            if (!empty($atts['player']) && is_numeric($atts['player'])) {
                $player_id = intval($atts['player']);

                $player = $wpdb->get_row($wpdb->prepare(
                    "SELECT p.*, t.name as team_name
                     FROM {$wpdb->prefix}bkgt_players p
                     LEFT JOIN {$wpdb->prefix}bkgt_teams t ON p.team_id = t.id
                     WHERE p.id = %d",
                    $player_id
                ));

                if ($wpdb->last_error) {
                    error_log('BKGT Team Player: Database error getting player dossier for player ' . $player_id . ': ' . $wpdb->last_error);
                    return '<div class="bkgt-player-dossier"><p>' . __('Error loading player information.', 'bkgt-team-player') . '</p></div>';
                }

                if ($player) {
                    $output .= '<h2>' . esc_html($player->first_name . ' ' . $player->last_name) . '</h2>';

                    // Basic info
                    $output .= '<div class="bkgt-player-info">';
                    $output .= '<p><strong>' . __('Team:', 'bkgt-team-player') . '</strong> ' . (empty($player->team_name) ? __('Not assigned', 'bkgt-team-player') : esc_html($player->team_name)) . '</p>';
                    if (!empty($player->jersey_number)) {
                        $output .= '<p><strong>' . __('Jersey Number:', 'bkgt-team-player') . '</strong> ' . esc_html($player->jersey_number) . '</p>';
                    }
                    if (!empty($player->position)) {
                        $output .= '<p><strong>' . __('Position:', 'bkgt-team-player') . '</strong> ' . esc_html($player->position) . '</p>';
                    }
                    if (!empty($player->birth_date)) {
                        $birth_date = new DateTime($player->birth_date);
                        $age = $birth_date->diff(new DateTime())->y;
                        $output .= '<p><strong>' . __('Age:', 'bkgt-team-player') . '</strong> ' . esc_html($age) . '</p>';
                    }
                    $output .= '</div>';

                    // Performance ratings (only for coaches and board members)
                    if (current_user_can('manage_options') || current_user_can('edit_posts')) {
                        $output .= $this->get_player_ratings($player_id);
                    }

                    // Statistics
                    if ($atts['show_stats'] === 'true') {
                        $output .= $this->get_player_stats($player_id);
                    }

                    // Notes (confidential)
                    if ($atts['show_notes'] === 'true' && (current_user_can('manage_options') || current_user_can('edit_posts'))) {
                        $output .= $this->get_player_notes($player_id);
                    }

                } else {
                    $output .= '<p>' . __('Player not found.', 'bkgt-team-player') . '</p>';
                }
            } else {
                $output .= '<p>' . __('Please specify a valid player ID.', 'bkgt-team-player') . '</p>';
            }

            $output .= '</div>';
            return $output;
        } catch (Exception $e) {
            error_log('BKGT Team Player: Exception in player dossier shortcode for player ' . ($atts['player'] ?? 'unknown') . ': ' . $e->getMessage());
            return '<div class="bkgt-player-dossier"><p>' . __('Error loading player information.', 'bkgt-team-player') . '</p></div>';
        }
    }

    private function get_player_ratings($player_id) {
        global $wpdb;

        try {
            $ratings = $wpdb->get_results($wpdb->prepare(
                "SELECT r.*, u.display_name as rater_name
                 FROM {$wpdb->prefix}bkgt_performance_ratings r
                 LEFT JOIN {$wpdb->users} u ON r.rater_id = u.ID
                 WHERE r.player_id = %d
                 ORDER BY r.rating_date DESC
                 LIMIT 5",
                $player_id
            ));

            if ($wpdb->last_error) {
                error_log('BKGT Team Player: Database error getting player ratings for player ' . $player_id . ': ' . $wpdb->last_error);
                return '<h3>' . __('Performance Ratings', 'bkgt-team-player') . '</h3><p>' . __('Error loading ratings.', 'bkgt-team-player') . '</p>';
            }

            if (empty($ratings)) {
                return '<h3>' . __('Performance Ratings', 'bkgt-team-player') . '</h3><p>' . __('No ratings available.', 'bkgt-team-player') . '</p>';
            }

            $output = '<h3>' . __('Recent Performance Ratings', 'bkgt-team-player') . '</h3>';
            $output .= '<div class="bkgt-player-ratings">';

            foreach ($ratings as $rating) {
                $output .= '<div class="bkgt-rating-card">';
                $output .= '<p><strong>' . __('Date:', 'bkgt-team-player') . '</strong> ' . esc_html(date_i18n(get_option('date_format'), strtotime($rating->rating_date))) . '</p>';
                $output .= '<p><strong>' . __('Rater:', 'bkgt-team-player') . '</strong> ' . esc_html($rating->rater_name) . '</p>';
                $output .= '<p><strong>' . __('Overall Rating:', 'bkgt-team-player') . '</strong> ' . number_format($rating->overall_rating, 1) . '/5.0</p>';
                if (!empty($rating->comments)) {
                    $output .= '<p><strong>' . __('Comments:', 'bkgt-team-player') . '</strong> ' . esc_html($rating->comments) . '</p>';
                }
                $output .= '</div>';
            }

            $output .= '</div>';
            return $output;
        } catch (Exception $e) {
            error_log('BKGT Team Player: Exception getting player ratings for player ' . $player_id . ': ' . $e->getMessage());
            return '<h3>' . __('Performance Ratings', 'bkgt-team-player') . '</h3><p>' . __('Error loading ratings.', 'bkgt-team-player') . '</p>';
        }
    }

    private function get_player_stats($player_id) {
        global $wpdb;

        try {
            $stats = $wpdb->get_row($wpdb->prepare(
                "SELECT
                    COUNT(*) as games_played,
                    SUM(points_scored) as total_points,
                    SUM(touchdowns) as total_touchdowns,
                    SUM(tackles) as total_tackles,
                    AVG(points_scored) as avg_points_per_game
                 FROM {$wpdb->prefix}bkgt_player_statistics
                 WHERE player_id = %d",
                $player_id
            ));

            if ($wpdb->last_error) {
                error_log('BKGT Team Player: Database error getting player stats for player ' . $player_id . ': ' . $wpdb->last_error);
                return '<h3>' . __('Player Statistics', 'bkgt-team-player') . '</h3><p>' . __('Error loading statistics.', 'bkgt-team-player') . '</p>';
            }

            if (!$stats || $stats->games_played == 0) {
                return '<h3>' . __('Player Statistics', 'bkgt-team-player') . '</h3><p>' . __('No statistics available.', 'bkgt-team-player') . '</p>';
            }

            $output = '<h3>' . __('Player Statistics', 'bkgt-team-player') . '</h3>';
            $output .= '<div class="bkgt-player-stats">';
            $output .= '<p><strong>' . __('Games Played:', 'bkgt-team-player') . '</strong> ' . esc_html($stats->games_played) . '</p>';
            $output .= '<p><strong>' . __('Total Points:', 'bkgt-team-player') . '</strong> ' . esc_html($stats->total_points) . '</p>';
            $output .= '<p><strong>' . __('Total Touchdowns:', 'bkgt-team-player') . '</strong> ' . esc_html($stats->total_touchdowns) . '</p>';
            $output .= '<p><strong>' . __('Total Tackles:', 'bkgt-team-player') . '</strong> ' . esc_html($stats->total_tackles) . '</p>';
            $output .= '<p><strong>' . __('Average Points per Game:', 'bkgt-team-player') . '</strong> ' . number_format($stats->avg_points_per_game, 1) . '</p>';
            $output .= '</div>';

            return $output;
        } catch (Exception $e) {
            error_log('BKGT Team Player: Exception getting player stats for player ' . $player_id . ': ' . $e->getMessage());
            return '<h3>' . __('Player Statistics', 'bkgt-team-player') . '</h3><p>' . __('Error loading statistics.', 'bkgt-team-player') . '</p>';
        }
    }

    private function get_player_notes($player_id) {
        global $wpdb;

        try {
            $notes = $wpdb->get_results($wpdb->prepare(
                "SELECT n.*, u.display_name as author_name
                 FROM {$wpdb->prefix}bkgt_player_notes n
                 LEFT JOIN {$wpdb->users} u ON n.author_id = u.ID
                 WHERE n.player_id = %d AND n.is_private = 0
                 ORDER BY n.created_date DESC
                 LIMIT 10",
                $player_id
            ));

            if ($wpdb->last_error) {
                error_log('BKGT Team Player: Database error getting player notes for player ' . $player_id . ': ' . $wpdb->last_error);
                return '<h3>' . __('Player Notes', 'bkgt-team-player') . '</h3><p>' . __('Error loading notes.', 'bkgt-team-player') . '</p>';
            }

            if (empty($notes)) {
                return '<h3>' . __('Player Notes', 'bkgt-team-player') . '</h3><p>' . __('No public notes available.', 'bkgt-team-player') . '</p>';
            }

            $output = '<h3>' . __('Player Notes', 'bkgt-team-player') . '</h3>';
            $output .= '<div class="bkgt-player-notes">';

            foreach ($notes as $note) {
                $output .= '<div class="bkgt-note-card">';
                $output .= '<h4>' . esc_html($note->title) . '</h4>';
                $output .= '<p><strong>' . __('Author:', 'bkgt-team-player') . '</strong> ' . esc_html($note->author_name) . '</p>';
                $output .= '<p><strong>' . __('Date:', 'bkgt-team-player') . '</strong> ' . esc_html(date_i18n(get_option('date_format'), strtotime($note->created_date))) . '</p>';
                $output .= '<div class="bkgt-note-content">' . wp_kses_post($note->content) . '</div>';
                $output .= '</div>';
            }

            $output .= '</div>';
            return $output;
        } catch (Exception $e) {
            error_log('BKGT Team Player: Exception getting player notes for player ' . $player_id . ': ' . $e->getMessage());
            return '<h3>' . __('Player Notes', 'bkgt-team-player') . '</h3><p>' . __('Error loading notes.', 'bkgt-team-player') . '</p>';
        }
    }

    public function performance_page_shortcode($atts) {
        // Check permissions - only coaches and board members
        if (!current_user_can('manage_options') && !current_user_can('edit_posts')) {
            return '<p>' . __('You do not have permission to view this page.', 'bkgt-team-player') . '</p>';
        }

        global $wpdb;

        try {
            $atts = shortcode_atts(array(
                'team' => '',
                'action' => 'view', // 'view', 'add', 'edit'
                'tab' => 'statistics' // 'statistics', 'ratings'
            ), $atts);

            $output = '<div class="bkgt-performance-page">';

            if ($atts['action'] === 'add') {
                $output .= $this->performance_rating_form();
            } else {
                // Get teams for the current user
                $teams = $this->get_user_teams();

                if (empty($teams)) {
                    $output .= '<p>' . __('No teams available for performance management.', 'bkgt-team-player') . '</p>';
                } else {
                    $output .= '<h2>' . __('Utvärdering - Performance Management', 'bkgt-team-player') . '</h2>';

                    if (count($teams) > 1) {
                        $output .= '<div class="bkgt-team-selector">';
                        $output .= '<label for="performance_team_select">' . __('Select Team:', 'bkgt-team-player') . '</label>';
                        $output .= '<select id="performance_team_select">';
                        $output .= '<option value="">' . __('Choose a team...', 'bkgt-team-player') . '</option>';
                        foreach ($teams as $team) {
                            $selected = ($atts['team'] === $team->slug) ? ' selected' : '';
                            $output .= '<option value="' . esc_attr($team->slug) . '"' . $selected . '>' . esc_html($team->name) . '</option>';
                        }
                        $output .= '</select></div>';
                    }

                    $selected_team = null;
                    if (!empty($atts['team'])) {
                        $selected_team = $wpdb->get_row($wpdb->prepare(
                            "SELECT * FROM {$wpdb->prefix}bkgt_teams WHERE slug = %s",
                            $atts['team']
                        ));

                        if ($wpdb->last_error) {
                            error_log('BKGT Team Player: Database error getting team in performance page: ' . $wpdb->last_error);
                            $output .= '<p>' . __('Error loading team information.', 'bkgt-team-player') . '</p>';
                            $output .= '</div>';
                            return $output;
                        }
                    } elseif (count($teams) === 1) {
                        $selected_team = $teams[0];
                    }

                    if ($selected_team) {
                        // Add tab navigation
                        $output .= '<div class="bkgt-performance-tabs">';
                        $output .= '<div class="bkgt-tab-buttons">';
                        $statistics_url = add_query_arg(array('tab' => 'statistics'), remove_query_arg('tab'));
                        $ratings_url = add_query_arg(array('tab' => 'ratings'), remove_query_arg('tab'));

                        $statistics_class = ($atts['tab'] === 'statistics') ? 'active' : '';
                        $ratings_class = ($atts['tab'] === 'ratings') ? 'active' : '';

                        $output .= '<a href="' . esc_url($statistics_url) . '" class="bkgt-tab-button ' . $statistics_class . '">' . __('📊 Statistik', 'bkgt-team-player') . '</a>';
                        $output .= '<a href="' . esc_url($ratings_url) . '" class="bkgt-tab-button ' . $ratings_class . '">' . __('📝 Utvärderingar', 'bkgt-team-player') . '</a>';
                        $output .= '<a href="' . esc_url(add_query_arg('action', 'add')) . '" class="button bkgt-add-rating-btn">' . __('➕ Lägg till utvärdering', 'bkgt-team-player') . '</a>';
                        $output .= '</div>';

                        $output .= '<div class="bkgt-tab-content">';

                        if ($atts['tab'] === 'statistics') {
                            $output .= $this->get_team_performance_statistics($selected_team->id);
                        } else {
                            $output .= $this->get_team_performance_ratings($selected_team->id);
                        }

                        $output .= '</div></div>';
                    } else {
                        $output .= '<p>' . __('Please select a team to view performance data.', 'bkgt-team-player') . '</p>';
                    }
                }
            }

            $output .= '</div>';
            return $output;
        } catch (Exception $e) {
            error_log('BKGT Team Player: Exception in performance page shortcode: ' . $e->getMessage());
            return '<div class="bkgt-performance-page"><p>' . __('Error loading performance page.', 'bkgt-team-player') . '</p></div>';
        }
    }

    private function get_user_teams() {
        global $wpdb;

        try {
            // For board members, return all teams
            if (current_user_can('manage_options')) {
                $teams = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}bkgt_teams ORDER BY name ASC");
            } else {
                // For coaches, return only their teams (this would need to be implemented based on user-team relationships)
                // For now, return all teams - this should be restricted based on actual permissions
                $teams = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}bkgt_teams ORDER BY name ASC");
            }

            if ($wpdb->last_error) {
                error_log('BKGT Team Player: Database error getting user teams: ' . $wpdb->last_error);
                return array();
            }

            return $teams ?: array();
        } catch (Exception $e) {
            error_log('BKGT Team Player: Exception getting user teams: ' . $e->getMessage());
            return array();
        }
    }

    private function performance_rating_form() {
        global $wpdb;

        try {
            $teams = $this->get_user_teams();
            $players = array();

            if (!empty($_GET['team'])) {
                $team = $wpdb->get_row($wpdb->prepare(
                    "SELECT * FROM {$wpdb->prefix}bkgt_teams WHERE slug = %s",
                    sanitize_text_field($_GET['team'])
                ));

                if ($wpdb->last_error) {
                    error_log('BKGT Team Player: Database error getting team in rating form: ' . $wpdb->last_error);
                    return '<h2>' . __('Add Performance Rating', 'bkgt-team-player') . '</h2><p>' . __('Error loading form.', 'bkgt-team-player') . '</p>';
                }

                if ($team) {
                    $players = $wpdb->get_results($wpdb->prepare(
                        "SELECT * FROM {$wpdb->prefix}bkgt_players
                         WHERE team_id = %d
                         ORDER BY CONCAT(first_name, ' ', last_name) ASC",
                        $team->id
                    ));

                    if ($wpdb->last_error) {
                        error_log('BKGT Team Player: Database error getting players in rating form: ' . $wpdb->last_error);
                        $players = array();
                    }
                }
            }

            $output = '<h2>' . __('Add Performance Rating', 'bkgt-team-player') . '</h2>';
            $output .= '<form id="bkgt-performance-form" method="post">';

            $output .= '<div class="bkgt-form-row">';
            $output .= '<label for="rating_team">' . __('Team:', 'bkgt-team-player') . '</label>';
            $output .= '<select id="rating_team" name="team_id" required>';
            $output .= '<option value="">' . __('Select Team', 'bkgt-team-player') . '</option>';
            foreach ($teams as $team) {
                $selected = (!empty($_GET['team']) && $_GET['team'] === $team->slug) ? ' selected' : '';
                $output .= '<option value="' . esc_attr($team->id) . '"' . $selected . '>' . esc_html($team->name) . '</option>';
            }
            $output .= '</select></div>';

            $output .= '<div class="bkgt-form-row">';
            $output .= '<label for="rating_player">' . __('Player:', 'bkgt-team-player') . '</label>';
            $output .= '<select id="rating_player" name="player_id" required>';
            $output .= '<option value="">' . __('Select Player', 'bkgt-team-player') . '</option>';
            foreach ($players as $player) {
                $output .= '<option value="' . esc_attr($player->id) . '">' . esc_html($player->first_name . ' ' . $player->last_name) . '</option>';
            }
            $output .= '</select></div>';

            $output .= '<div class="bkgt-form-row">';
            $output .= '<label for="enthusiasm_rating">' . __('Enthusiasm (1-5):', 'bkgt-team-player') . '</label>';
            $output .= '<select id="enthusiasm_rating" name="enthusiasm_rating" required>';
            for ($i = 1; $i <= 5; $i++) {
                $output .= '<option value="' . $i . '">' . $i . '</option>';
            }
            $output .= '</select></div>';

            $output .= '<div class="bkgt-form-row">';
            $output .= '<label for="performance_rating">' . __('Performance (1-5):', 'bkgt-team-player') . '</label>';
            $output .= '<select id="performance_rating" name="performance_rating" required>';
            for ($i = 1; $i <= 5; $i++) {
                $output .= '<option value="' . $i . '">' . $i . '</option>';
            }
            $output .= '</select></div>';

            $output .= '<div class="bkgt-form-row">';
            $output .= '<label for="skill_rating">' . __('Skill (1-5):', 'bkgt-team-player') . '</label>';
            $output .= '<select id="skill_rating" name="skill_rating" required>';
            for ($i = 1; $i <= 5; $i++) {
                $output .= '<option value="' . $i . '">' . $i . '</option>';
            }
            $output .= '</select></div>';

            $output .= '<div class="bkgt-form-row">';
            $output .= '<label for="rating_comments">' . __('Comments:', 'bkgt-team-player') . '</label>';
            $output .= '<textarea id="rating_comments" name="comments" rows="4"></textarea>';
            $output .= '</div>';

            $output .= '<div class="bkgt-form-row">';
            $output .= '<input type="submit" value="' . __('Save Rating', 'bkgt-team-player') . '" class="button button-primary">';
            $output .= '<a href="' . esc_url(remove_query_arg('action')) . '" class="button">' . __('Cancel', 'bkgt-team-player') . '</a>';
            $output .= '</div>';

            $output .= wp_nonce_field('bkgt_performance_rating', 'bkgt_performance_nonce', false, false);
            $output .= '</form>';

            return $output;
        } catch (Exception $e) {
            error_log('BKGT Team Player: Exception in performance rating form: ' . $e->getMessage());
            return '<h2>' . __('Add Performance Rating', 'bkgt-team-player') . '</h2><p>' . __('Error loading form.', 'bkgt-team-player') . '</p>';
        }
    }

    private function get_team_performance_ratings($team_id) {
        global $wpdb;

        try {
            $ratings = $wpdb->get_results($wpdb->prepare(
                "SELECT r.*, CONCAT(p.first_name, ' ', p.last_name) as player_name, u.display_name as rater_name
                 FROM {$wpdb->prefix}bkgt_performance_ratings r
                 LEFT JOIN {$wpdb->prefix}bkgt_players p ON r.player_id = p.id
                 LEFT JOIN {$wpdb->users} u ON r.rater_id = u.ID
                 WHERE r.team_id = %d
                 ORDER BY r.rating_date DESC
                 LIMIT 50",
                $team_id
            ));

            if ($wpdb->last_error) {
                error_log('BKGT Team Player: Database error getting team performance ratings for team ' . $team_id . ': ' . $wpdb->last_error);
                return '<h3>' . __('Performance Ratings', 'bkgt-team-player') . '</h3><p>' . __('Error loading ratings.', 'bkgt-team-player') . '</p>';
            }

            if (empty($ratings)) {
                return '<h3>' . __('Performance Ratings', 'bkgt-team-player') . '</h3><p>' . __('No ratings found for this team.', 'bkgt-team-player') . '</p>';
            }

            $output = '<h3>' . __('Performance Ratings', 'bkgt-team-player') . '</h3>';
            $output .= '<div class="bkgt-performance-ratings">';
            $output .= '<table class="bkgt-ratings-table">';
            $output .= '<thead><tr>';
            $output .= '<th>' . __('Player', 'bkgt-team-player') . '</th>';
            $output .= '<th>' . __('Date', 'bkgt-team-player') . '</th>';
            $output .= '<th>' . __('Rater', 'bkgt-team-player') . '</th>';
            $output .= '<th>' . __('Overall Rating', 'bkgt-team-player') . '</th>';
            $output .= '<th>' . __('Comments', 'bkgt-team-player') . '</th>';
            $output .= '</tr></thead><tbody>';

            foreach ($ratings as $rating) {
                $output .= '<tr>';
                $output .= '<td>' . esc_html($rating->player_name) . '</td>';
                $output .= '<td>' . esc_html(date_i18n(get_option('date_format'), strtotime($rating->rating_date))) . '</td>';
                $output .= '<td>' . esc_html($rating->rater_name) . '</td>';
                $output .= '<td>' . number_format($rating->overall_rating, 1) . '/5.0</td>';
                $output .= '<td>' . (empty($rating->comments) ? '-' : esc_html(substr($rating->comments, 0, 50)) . '...') . '</td>';
                $output .= '</tr>';
            }

            $output .= '</tbody></table></div>';
            return $output;
        } catch (Exception $e) {
            error_log('BKGT Team Player: Exception getting team performance ratings for team ' . $team_id . ': ' . $e->getMessage());
            return '<h3>' . __('Performance Ratings', 'bkgt-team-player') . '</h3><p>' . __('Error loading ratings.', 'bkgt-team-player') . '</p>';
        }
    }

    private function get_team_performance_statistics($team_id) {
        global $wpdb;
        
        // Get statistics data
        $stats = $this->calculate_team_performance_stats($team_id);
        $player_stats = $this->get_player_performance_stats($team_id);
        $recent_trends = $this->get_performance_trends($team_id);
        
        $output = '<div class="bkgt-performance-statistics">';
        
        // Overview Stats Cards
        $output .= '<div class="bkgt-stats-overview">';
        $output .= '<div class="bkgt-stat-card">';
        $output .= '<h4>' . __('Total Players', 'bkgt-team-player') . '</h4>';
        $output .= '<div class="bkgt-stat-value">' . $stats['total_players'] . '</div>';
        $output .= '</div>';
        
        $output .= '<div class="bkgt-stat-card">';
        $output .= '<h4>' . __('Evaluations This Month', 'bkgt-team-player') . '</h4>';
        $output .= '<div class="bkgt-stat-value">' . $stats['evaluations_this_month'] . '</div>';
        $output .= '</div>';
        
        $output .= '<div class="bkgt-stat-card">';
        $output .= '<h4>' . __('Average Rating', 'bkgt-team-player') . '</h4>';
        $output .= '<div class="bkgt-stat-value">' . number_format($stats['avg_rating'], 1) . '/5.0</div>';
        $output .= '</div>';
        
        $output .= '<div class="bkgt-stat-card">';
        $output .= '<h4>' . __('Top Performer', 'bkgt-team-player') . '</h4>';
        $output .= '<div class="bkgt-stat-value">' . esc_html($stats['top_performer']) . '</div>';
        $output .= '</div>';
        $output .= '</div>';
        
        // Charts Section
        $output .= '<div class="bkgt-charts-section">';
        $output .= '<h3>' . __('Performance Analytics', 'bkgt-team-player') . '</h3>';
        
        // Rating Distribution Chart
        $output .= '<div class="bkgt-chart-container">';
        $output .= '<h4>' . __('Rating Distribution', 'bkgt-team-player') . '</h4>';
        $output .= '<div class="bkgt-chart-placeholder">';
        $output .= '<div class="bkgt-rating-bars">';
        for ($i = 5; $i >= 1; $i--) {
            $count = isset($stats['rating_distribution'][$i]) ? $stats['rating_distribution'][$i] : 0;
            $percentage = $stats['total_ratings'] > 0 ? ($count / $stats['total_ratings']) * 100 : 0;
            $output .= '<div class="bkgt-rating-bar">';
            $output .= '<span class="bkgt-rating-label">' . $i . ' ' . __('stars', 'bkgt-team-player') . '</span>';
            $output .= '<div class="bkgt-rating-bar-fill" style="width: ' . $percentage . '%"></div>';
            $output .= '<span class="bkgt-rating-count">' . $count . '</span>';
            $output .= '</div>';
        }
        $output .= '</div></div></div>';
        
        // Performance Trends Chart
        $output .= '<div class="bkgt-chart-container">';
        $output .= '<h4>' . __('Performance Trends (Last 6 Months)', 'bkgt-team-player') . '</h4>';
        $output .= '<div class="bkgt-chart-placeholder">';
        if (!empty($recent_trends)) {
            $output .= '<div class="bkgt-trend-chart">';
            foreach ($recent_trends as $trend) {
                $output .= '<div class="bkgt-trend-point" data-month="' . esc_attr($trend->month) . '" data-rating="' . esc_attr($trend->avg_rating) . '"></div>';
            }
            $output .= '</div>';
            $output .= '<p class="bkgt-chart-note">' . __('📈 Interactive chart showing average ratings over time', 'bkgt-team-player') . '</p>';
        } else {
            $output .= '<p>' . __('No trend data available yet.', 'bkgt-team-player') . '</p>';
        }
        $output .= '</div></div>';
        $output .= '</div>';
        
        // Player Performance Table
        $output .= '<div class="bkgt-player-stats-section">';
        $output .= '<h3>' . __('Player Performance Summary', 'bkgt-team-player') . '</h3>';
        $output .= '<table class="bkgt-player-stats-table">';
        $output .= '<thead>';
        $output .= '<tr>';
        $output .= '<th>' . __('Player', 'bkgt-team-player') . '</th>';
        $output .= '<th>' . __('Latest Rating', 'bkgt-team-player') . '</th>';
        $output .= '<th>' . __('Evaluations', 'bkgt-team-player') . '</th>';
        $output .= '<th>' . __('Trend', 'bkgt-team-player') . '</th>';
        $output .= '<th>' . __('Last Evaluation', 'bkgt-team-player') . '</th>';
        $output .= '</tr>';
        $output .= '</thead>';
        $output .= '<tbody>';
        
        if (!empty($player_stats)) {
            foreach ($player_stats as $player) {
                $trend_class = '';
                $trend_symbol = '';
                if ($player->trend > 0.1) {
                    $trend_class = 'positive';
                    $trend_symbol = '↗️';
                } elseif ($player->trend < -0.1) {
                    $trend_class = 'negative';
                    $trend_symbol = '↘️';
                } else {
                    $trend_class = 'stable';
                    $trend_symbol = '➡️';
                }
                
                $output .= '<tr>';
                $output .= '<td>' . esc_html($player->player_name) . '</td>';
                $output .= '<td>' . number_format($player->latest_rating, 1) . '/5.0</td>';
                $output .= '<td>' . $player->evaluation_count . '</td>';
                $output .= '<td class="bkgt-trend ' . $trend_class . '">' . $trend_symbol . ' ' . number_format(abs($player->trend), 1) . '</td>';
                $output .= '<td>' . esc_html(date_i18n(get_option('date_format'), strtotime($player->last_evaluation))) . '</td>';
                $output .= '</tr>';
            }
        } else {
            $output .= '<tr><td colspan="5">' . __('No player statistics available.', 'bkgt-team-player') . '</td></tr>';
        }
        
        $output .= '</tbody>';
        $output .= '</table>';
        $output .= '</div>';
        
        $output .= '</div>';
        
        // Add CSS styles
        $output .= '<style>
        .bkgt-performance-statistics { margin-top: 20px; }
        .bkgt-stats-overview { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .bkgt-stat-card { background: #f8f9fa; border: 1px solid #e9ecef; border-radius: 8px; padding: 20px; text-align: center; }
        .bkgt-stat-card h4 { margin: 0 0 10px 0; color: #666; font-size: 14px; text-transform: uppercase; }
        .bkgt-stat-value { font-size: 28px; font-weight: bold; color: #007cba; }
        .bkgt-charts-section { margin-bottom: 30px; }
        .bkgt-chart-container { background: #fff; border: 1px solid #e9ecef; border-radius: 8px; padding: 20px; margin-bottom: 20px; }
        .bkgt-chart-container h4 { margin-top: 0; color: #333; }
        .bkgt-chart-placeholder { background: #f8f9fa; border: 2px dashed #dee2e6; border-radius: 4px; padding: 40px; text-align: center; color: #6c757d; }
        .bkgt-rating-bars { max-width: 400px; margin: 0 auto; }
        .bkgt-rating-bar { display: flex; align-items: center; margin-bottom: 8px; }
        .bkgt-rating-label { width: 80px; font-size: 14px; }
        .bkgt-rating-bar-fill { height: 20px; background: #007cba; border-radius: 10px; margin: 0 10px; transition: width 0.3s ease; }
        .bkgt-rating-count { width: 30px; text-align: right; font-size: 14px; }
        .bkgt-player-stats-table { width: 100%; border-collapse: collapse; background: #fff; border: 1px solid #e9ecef; border-radius: 8px; overflow: hidden; }
        .bkgt-player-stats-table th, .bkgt-player-stats-table td { padding: 12px; text-align: left; border-bottom: 1px solid #e9ecef; }
        .bkgt-player-stats-table th { background: #f8f9fa; font-weight: 600; }
        .bkgt-trend.positive { color: #28a745; }
        .bkgt-trend.negative { color: #dc3545; }
        .bkgt-trend.stable { color: #6c757d; }
        .bkgt-performance-tabs { margin-top: 20px; }
        .bkgt-tab-buttons { display: flex; gap: 10px; margin-bottom: 20px; align-items: center; }
        .bkgt-tab-button { padding: 10px 20px; background: #f8f9fa; border: 1px solid #e9ecef; border-radius: 4px; text-decoration: none; color: #666; }
        .bkgt-tab-button.active { background: #007cba; color: #fff; border-color: #007cba; }
        .bkgt-add-rating-btn { margin-left: auto; }
        </style>';
        
        return $output;
    }

    private function calculate_team_performance_stats($team_id) {
        global $wpdb;

        try {
            $stats = array(
                'total_players' => 0,
                'evaluations_this_month' => 0,
                'avg_rating' => 0,
                'top_performer' => 'N/A',
                'total_ratings' => 0,
                'rating_distribution' => array(1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0)
            );

            // Total players in team
            $stats['total_players'] = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}bkgt_players WHERE team_id = %d",
                $team_id
            ));

            if ($wpdb->last_error) {
                error_log('BKGT Team Player: Database error getting total players for team ' . $team_id . ': ' . $wpdb->last_error);
                return $stats;
            }

            // Evaluations this month
            $stats['evaluations_this_month'] = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}bkgt_performance_ratings
                 WHERE team_id = %d AND MONTH(rating_date) = MONTH(CURDATE()) AND YEAR(rating_date) = YEAR(CURDATE())",
                $team_id
            ));

            if ($wpdb->last_error) {
                error_log('BKGT Team Player: Database error getting evaluations this month for team ' . $team_id . ': ' . $wpdb->last_error);
                return $stats;
            }

            // Average rating and rating distribution
            $ratings = $wpdb->get_results($wpdb->prepare(
                "SELECT overall_rating FROM {$wpdb->prefix}bkgt_performance_ratings
                 WHERE team_id = %d",
                $team_id
            ));

            if ($wpdb->last_error) {
                error_log('BKGT Team Player: Database error getting ratings for team ' . $team_id . ': ' . $wpdb->last_error);
                return $stats;
            }

            if (!empty($ratings)) {
                $total_rating = 0;
                foreach ($ratings as $rating) {
                    $total_rating += $rating->overall_rating;
                    $rounded_rating = round($rating->overall_rating);
                    if (isset($stats['rating_distribution'][$rounded_rating])) {
                        $stats['rating_distribution'][$rounded_rating]++;
                    }
                }
                $stats['avg_rating'] = $total_rating / count($ratings);
                $stats['total_ratings'] = count($ratings);
            }

            // Top performer
            $top_performer = $wpdb->get_row($wpdb->prepare(
                "SELECT CONCAT(p.first_name, ' ', p.last_name) as display_name, AVG(r.overall_rating) as avg_rating
                 FROM {$wpdb->prefix}bkgt_performance_ratings r
                 JOIN {$wpdb->prefix}bkgt_players p ON r.player_id = p.id
                 WHERE r.team_id = %d
                 GROUP BY r.player_id
                 ORDER BY avg_rating DESC
                 LIMIT 1",
                $team_id
            ));

            if ($wpdb->last_error) {
                error_log('BKGT Team Player: Database error getting top performer for team ' . $team_id . ': ' . $wpdb->last_error);
                return $stats;
            }

            if ($top_performer) {
                $stats['top_performer'] = $top_performer->display_name;
            }

            return $stats;
        } catch (Exception $e) {
            error_log('BKGT Team Player: Exception calculating team performance stats for team ' . $team_id . ': ' . $e->getMessage());
            return array(
                'total_players' => 0,
                'evaluations_this_month' => 0,
                'avg_rating' => 0,
                'top_performer' => 'N/A',
                'total_ratings' => 0,
                'rating_distribution' => array(1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0)
            );
        }
    }
    
    private function get_player_performance_stats($team_id) {
        global $wpdb;

        try {
            $stats = $wpdb->get_results($wpdb->prepare(
                "SELECT
                    CONCAT(p.first_name, ' ', p.last_name) as player_name,
                    COUNT(r.id) as evaluation_count,
                    MAX(r.rating_date) as last_evaluation,
                    (
                        SELECT overall_rating FROM {$wpdb->prefix}bkgt_performance_ratings
                        WHERE player_id = p.id ORDER BY rating_date DESC LIMIT 1
                    ) as latest_rating,
                    (
                        SELECT AVG(overall_rating) FROM {$wpdb->prefix}bkgt_performance_ratings
                        WHERE player_id = p.id AND rating_date >= DATE_SUB(CURDATE(), INTERVAL 3 MONTH)
                    ) - (
                        SELECT AVG(overall_rating) FROM {$wpdb->prefix}bkgt_performance_ratings
                        WHERE player_id = p.id AND rating_date BETWEEN DATE_SUB(CURDATE(), INTERVAL 6 MONTH) AND DATE_SUB(CURDATE(), INTERVAL 3 MONTH)
                    ) as trend
                 FROM {$wpdb->prefix}bkgt_players p
                 LEFT JOIN {$wpdb->prefix}bkgt_performance_ratings r ON p.id = r.player_id
                 WHERE p.team_id = %d
                 GROUP BY p.id
                 HAVING evaluation_count > 0
                 ORDER BY latest_rating DESC",
                $team_id
            ));

            if ($wpdb->last_error) {
                error_log('BKGT Team Player: Database error getting player performance stats for team ' . $team_id . ': ' . $wpdb->last_error);
                return array();
            }

            return $stats ?: array();
        } catch (Exception $e) {
            error_log('BKGT Team Player: Exception getting player performance stats for team ' . $team_id . ': ' . $e->getMessage());
            return array();
        }
    }
    
    private function get_performance_trends($team_id) {
        global $wpdb;

        try {
            $trends = $wpdb->get_results($wpdb->prepare(
                "SELECT
                    DATE_FORMAT(rating_date, '%Y-%m') as month,
                    AVG(overall_rating) as avg_rating,
                    COUNT(*) as rating_count
                 FROM {$wpdb->prefix}bkgt_performance_ratings
                 WHERE team_id = %d AND rating_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
                 GROUP BY DATE_FORMAT(rating_date, '%Y-%m')
                 ORDER BY month ASC",
                $team_id
            ));

            if ($wpdb->last_error) {
                error_log('BKGT Team Player: Database error getting performance trends for team ' . $team_id . ': ' . $wpdb->last_error);
                return array();
            }

            return $trends ?: array();
        } catch (Exception $e) {
            error_log('BKGT Team Player: Exception getting performance trends for team ' . $team_id . ': ' . $e->getMessage());
            return array();
        }
    }

    /**
     * Setup Pages Admin Page
     */
    public function setup_pages_admin_page() {
        if (isset($_POST['bkgt_setup_pages']) && wp_verify_nonce($_POST['bkgt_setup_nonce'], 'bkgt_setup_pages')) {
            $this->create_bkgt_pages();
            echo '<div class="notice notice-success"><p>' . __('Pages created/updated successfully!', 'bkgt-team-player') . '</p></div>';
        }

        ?>
        <div class="wrap">
            <h1><?php _e('BKGT Page Setup', 'bkgt-team-player'); ?></h1>

            <p><?php _e('This tool creates the necessary WordPress pages with the correct templates for the BKGT team and player functionality.', 'bkgt-team-player'); ?></p>

            <div class="bkgt-setup-info">
                <h3><?php _e('Pages to be created:', 'bkgt-team-player'); ?></h3>
                <ul>
                    <li><strong><?php _e('Lagöversikt', 'bkgt-team-player'); ?></strong> - <?php _e('Team overview with statistics', 'bkgt-team-player'); ?> (page-team-overview.php)</li>
                    <li><strong><?php _e('Spelare', 'bkgt-team-player'); ?></strong> - <?php _e('Players directory with filters', 'bkgt-team-player'); ?> (page-players.php)</li>
                    <li><strong><?php _e('Matcher & Event', 'bkgt-team-player'); ?></strong> - <?php _e('Events and matches', 'bkgt-team-player'); ?> (page-events.php)</li>
                </ul>
            </div>

            <form method="post">
                <?php wp_nonce_field('bkgt_setup_pages', 'bkgt_setup_nonce'); ?>
                <p>
                    <input type="submit" name="bkgt_setup_pages" class="button button-primary" value="<?php _e('Create/Update Pages', 'bkgt-team-player'); ?>">
                </p>
            </form>
        </div>
        <?php
    }

    /**
     * Create BKGT pages with correct templates
     */
    private function create_bkgt_pages() {
        $pages = array(
            array(
                'title' => 'Lagöversikt',
                'slug' => 'lagoversikt',
                'template' => 'page-team-overview.php',
                'content' => 'Statistik och översikt över BKGT laget.'
            ),
            array(
                'title' => 'Spelare',
                'slug' => 'spelare',
                'template' => 'page-players.php',
                'content' => 'Här hittar du alla våra spelare i BKGT.'
            ),
            array(
                'title' => 'Matcher & Event',
                'slug' => 'matcher',
                'template' => 'page-events.php',
                'content' => 'Kommande matcher och event för BKGT.'
            )
        );

        foreach ($pages as $page_data) {
            // Check if page exists
            $existing_page = get_page_by_path($page_data['slug']);

            $post_data = array(
                'post_title' => $page_data['title'],
                'post_name' => $page_data['slug'],
                'post_content' => $page_data['content'],
                'post_status' => 'publish',
                'post_type' => 'page',
                'meta_input' => array(
                    '_wp_page_template' => $page_data['template']
                )
            );

            if ($existing_page) {
                $post_data['ID'] = $existing_page->ID;
                wp_update_post($post_data);
            } else {
                wp_insert_post($post_data);
            }
        }
    }

    // AJAX handlers will be implemented
    public function ajax_save_player_note() {
        // Verify nonce using BKGT Core
        if (!bkgt_validate('verify_nonce', $_POST['nonce'] ?? '', 'bkgt_team_player_nonce')) {
            bkgt_log_safe('warning', 'Player note nonce verification failed', array(
                'user_id' => get_current_user_id(),
            ));
            wp_send_json_error(array('message' => __('Säkerhetskontroll misslyckades.', 'bkgt-team-player')));
        }

        // Check permissions using BKGT Core
        if (!bkgt_can('edit_player_data')) {
            bkgt_log_safe('warning', 'Player note save denied - insufficient permissions', array(
                'user_id' => get_current_user_id(),
            ));
            wp_send_json_error(array('message' => __('Du har inte behörighet att spara spelarnotes.', 'bkgt-team-player')));
        }

        // Get and validate input using BKGT Core
        $player_id = intval($_POST['player_id'] ?? 0);
        $note_type = bkgt_validate('sanitize_text', $_POST['note_type'] ?? '');
        $title = bkgt_validate('sanitize_text', $_POST['title'] ?? '');
        $content = bkgt_validate('sanitize_html', $_POST['content'] ?? '');
        $is_private = isset($_POST['is_private']) ? 1 : 0;

        if (empty($player_id)) {
            bkgt_log('warning', 'Player note save - player ID missing');
            wp_send_json_error(array('message' => __('Spelare-ID krävs.', 'bkgt-team-player')));
        }

        if (empty($note_type)) {
            bkgt_log('warning', 'Player note save - note type missing');
            wp_send_json_error(array('message' => __('Anteckningstyp krävs.', 'bkgt-team-player')));
        }

        // Insert note using BKGT Database Core
        $note_data = array(
            'player_id' => $player_id,
            'author_id' => get_current_user_id(),
            'note_type' => $note_type,
            'title' => $title,
            'content' => $content,
            'is_private' => $is_private
        );

        global $wpdb;
        $result = $wpdb->insert(
            $wpdb->prefix . 'bkgt_player_notes',
            $note_data,
            array('%d', '%d', '%s', '%s', '%s', '%d')
        );

        if ($result) {
            bkgt_log('info', 'Player note saved successfully', array(
                'player_id' => $player_id,
                'note_type' => $note_type,
                'user_id' => get_current_user_id(),
            ));
            wp_send_json_success(array('message' => __('Anteckning sparad framgångsrikt!', 'bkgt-team-player')));
        } else {
            bkgt_log('error', 'Failed to save player note', array(
                'player_id' => $player_id,
                'error' => $wpdb->last_error,
            ));
            wp_send_json_error(array('message' => __('Misslyckades att spara anteckning.', 'bkgt-team-player')));
        }
    }

    public function ajax_save_performance_rating() {
        // Verify nonce using BKGT Core
        if (!bkgt_validate('verify_nonce', $_POST['nonce'] ?? '', 'bkgt_performance_nonce')) {
            bkgt_log('warning', 'Performance rating nonce verification failed', array(
                'user_id' => get_current_user_id(),
            ));
            wp_send_json_error(array('message' => __('Säkerhetskontroll misslyckades.', 'bkgt-team-player')));
        }

        // Check permissions using BKGT Core
        if (!bkgt_can('rate_player_performance')) {
            bkgt_log('warning', 'Performance rating save denied - insufficient permissions', array(
                'user_id' => get_current_user_id(),
            ));
            wp_send_json_error(array('message' => __('Du har inte behörighet att betygsätta spelares prestanda.', 'bkgt-team-player')));
        }

        // Get and validate input using BKGT Core
        $player_id = intval($_POST['player_id'] ?? 0);
        $team_id = intval($_POST['team_id'] ?? 0);
        $enthusiasm = intval($_POST['enthusiasm_rating'] ?? 0);
        $performance = intval($_POST['performance_rating'] ?? 0);
        $skill = intval($_POST['skill_rating'] ?? 0);
        $comments = bkgt_validate('sanitize_html', $_POST['comments'] ?? '');
        $season = bkgt_validate('sanitize_text', $_POST['season'] ?? '');

        // Validate required fields
        if (empty($player_id) || empty($team_id)) {
            bkgt_log('warning', 'Performance rating - missing player or team ID');
            wp_send_json_error(array('message' => __('Spelare och lag ID krävs.', 'bkgt-team-player')));
        }

        // Validate ratings (1-5 scale)
        if ($enthusiasm < 1 || $enthusiasm > 5 || $performance < 1 || $performance > 5 || $skill < 1 || $skill > 5) {
            bkgt_log('warning', 'Performance rating - invalid rating values', array(
                'enthusiasm' => $enthusiasm,
                'performance' => $performance,
                'skill' => $skill,
            ));
            wp_send_json_error(array('message' => __('Betygsvärden måste vara mellan 1-5.', 'bkgt-team-player')));
        }

        // Insert rating using BKGT Database Core
        global $wpdb;
        $rating_data = array(
            'player_id' => $player_id,
            'team_id' => $team_id,
            'rater_id' => get_current_user_id(),
            'enthusiasm_rating' => $enthusiasm,
            'performance_rating' => $performance,
            'skill_rating' => $skill,
            'comments' => $comments,
            'season' => $season
        );

        $result = $wpdb->insert(
            $wpdb->prefix . 'bkgt_performance_ratings',
            $rating_data,
            array('%d', '%d', '%d', '%d', '%d', '%d', '%s', '%s')
        );

        if ($result) {
            bkgt_log('info', 'Performance rating saved successfully', array(
                'player_id' => $player_id,
                'team_id' => $team_id,
                'average_rating' => ($enthusiasm + $performance + $skill) / 3,
                'user_id' => get_current_user_id(),
            ));
            wp_send_json_success(array('message' => __('Prestanda betyg sparad framgångsrikt!', 'bkgt-team-player')));
        } else {
            bkgt_log('error', 'Failed to save performance rating', array(
                'player_id' => $player_id,
                'error' => $wpdb->last_error,
            ));
            wp_send_json_error(array('message' => __('Misslyckades att spara prestanda betyg.', 'bkgt-team-player')));
        }
    }

    public function ajax_get_player_stats() {
        // Verify nonce using BKGT Core
        if (!bkgt_validate('verify_nonce', $_POST['bkgt_nonce'] ?? '', 'bkgt_team_player_nonce')) {
            bkgt_log('warning', 'Player stats nonce verification failed', array(
                'user_id' => get_current_user_id(),
            ));
            wp_send_json_error(array('message' => __('Säkerhetskontroll misslyckades.', 'bkgt-team-player')));
        }

        // Check permissions using BKGT Core
        if (!bkgt_can('view_player_stats')) {
            bkgt_log('warning', 'Player stats access denied - insufficient permissions', array(
                'user_id' => get_current_user_id(),
            ));
            wp_send_json_error(array('message' => __('Du har inte behörighet att visa spelarstatistik.', 'bkgt-team-player')));
        }

        $player_id = intval($_POST['player_id'] ?? 0);

        if (empty($player_id)) {
            bkgt_log('warning', 'Player stats - player ID missing');
            wp_send_json_error(array('message' => __('Spelare-ID krävs.', 'bkgt-team-player')));
        }

        global $wpdb;
        $stats = $wpdb->get_row($wpdb->prepare(
            "SELECT
                COUNT(*) as games_played,
                SUM(points_scored) as total_points,
                SUM(touchdowns) as total_touchdowns,
                SUM(tackles) as total_tackles,
                AVG(points_scored) as avg_points_per_game
             FROM {$wpdb->prefix}bkgt_player_statistics
             WHERE player_id = %d",
            $player_id
        ));

        if ($stats) {
            bkgt_log('info', 'Player stats retrieved', array(
                'player_id' => $player_id,
                'games_played' => $stats->games_played,
            ));
            wp_send_json_success($stats);
        } else {
            bkgt_log('info', 'Player stats - no statistics found', array(
                'player_id' => $player_id,
            ));
            wp_send_json_error(array('message' => __('Ingen statistik hittad.', 'bkgt-team-player')));
        }
    }

    // AJAX handler for getting team performance ratings (admin)
    public function ajax_get_team_performance() {
        // Verify nonce using BKGT Core
        if (!bkgt_validate('verify_nonce', $_POST['nonce'] ?? '', 'bkgt_performance_nonce')) {
            bkgt_log('warning', 'Team performance nonce verification failed', array(
                'user_id' => get_current_user_id(),
            ));
            wp_send_json_error(array('message' => __('Säkerhetskontroll misslyckades.', 'bkgt-team-player')));
        }

        // Check permissions using BKGT Core
        if (!bkgt_can('view_performance_ratings')) {
            bkgt_log('warning', 'Team performance access denied - insufficient permissions', array(
                'user_id' => get_current_user_id(),
            ));
            wp_send_json_error(array('message' => __('Du har inte behörighet att visa prestanda betyg.', 'bkgt-team-player')));
        }

        $team_id = intval($_POST['team_id'] ?? 0);

        if (empty($team_id)) {
            bkgt_log('warning', 'Team performance - team ID missing');
            wp_send_json_error(array('message' => __('Lag-ID krävs.', 'bkgt-team-player')));
        }

        global $wpdb;

        $ratings = $wpdb->get_results($wpdb->prepare(
            "SELECT r.*, CONCAT(p.first_name, ' ', p.last_name) as player_name, u.display_name as rater_name
             FROM {$wpdb->prefix}bkgt_performance_ratings r
             LEFT JOIN {$wpdb->prefix}bkgt_players p ON r.player_id = p.id
             LEFT JOIN {$wpdb->users} u ON r.rater_id = u.ID
             WHERE r.team_id = %d
             ORDER BY r.rating_date DESC
             LIMIT 50",
            $team_id
        ));

        $html = '<table class="wp-list-table widefat fixed striped">';
        $html .= '<thead><tr>';
        $html .= '<th>' . __('Spelare', 'bkgt-team-player') . '</th>';
        $html .= '<th>' . __('Datum', 'bkgt-team-player') . '</th>';
        $html .= '<th>' . __('Betygsättare', 'bkgt-team-player') . '</th>';
        $html .= '<th>' . __('Övergripande betyg', 'bkgt-team-player') . '</th>';
        $html .= '<th>' . __('Kommentarer', 'bkgt-team-player') . '</th>';
        $html .= '</tr></thead><tbody>';

        if (empty($ratings)) {
            $html .= '<tr><td colspan="5">' . __('Inga betyg hittade för detta lag.', 'bkgt-team-player') . '</td></tr>';
        } else {
            foreach ($ratings as $rating) {
                $html .= '<tr>';
                $html .= '<td>' . esc_html($rating->player_name) . '</td>';
                $html .= '<td>' . esc_html(date_i18n(get_option('date_format'), strtotime($rating->rating_date))) . '</td>';
                $html .= '<td>' . esc_html($rating->rater_name) . '</td>';
                $html .= '<td>' . number_format($rating->overall_rating, 1) . '/5.0</td>';
                $html .= '<td>' . (empty($rating->comments) ? '-' : esc_html(substr($rating->comments, 0, 50)) . (strlen($rating->comments) > 50 ? '...' : '')) . '</td>';
                $html .= '</tr>';
            }
        }

        $html .= '</tbody></table>';

        bkgt_log('info', 'Team performance ratings retrieved', array(
            'team_id' => $team_id,
            'ratings_count' => count($ratings),
            'user_id' => get_current_user_id(),
        ));

        wp_send_json_success(array('html' => $html));
    }

    public function ajax_get_team_players() {
        // Verify nonce using BKGT Core
        if (!bkgt_validate('verify_nonce', $_POST['bkgt_nonce'] ?? '', 'bkgt_team_player_nonce')) {
            bkgt_log('warning', 'Team players nonce verification failed', array(
                'user_id' => get_current_user_id(),
            ));
            wp_send_json_error(array('message' => __('Säkerhetskontroll misslyckades.', 'bkgt-team-player')));
        }

        // Check permissions using BKGT Core
        if (!bkgt_can('view_team_players')) {
            bkgt_log('warning', 'Team players access denied - insufficient permissions', array(
                'user_id' => get_current_user_id(),
            ));
            wp_send_json_error(array('message' => __('Du har inte behörighet att visa lagspelare.', 'bkgt-team-player')));
        }

        $team_id = intval($_POST['team_id'] ?? 0);

        if (empty($team_id)) {
            bkgt_log('warning', 'Team players - team ID missing');
            wp_send_json_error(array('message' => __('Lag-ID krävs.', 'bkgt-team-player')));
        }

        global $wpdb;

        $players = $wpdb->get_results($wpdb->prepare(
            "SELECT id, CONCAT(first_name, ' ', last_name) as display_name
             FROM {$wpdb->prefix}bkgt_players
             WHERE team_id = %d
             ORDER BY CONCAT(first_name, ' ', last_name) ASC",
            $team_id
        ));

        if (!empty($players)) {
            bkgt_log('info', 'Team players retrieved', array(
                'team_id' => $team_id,
                'players_count' => count($players),
                'user_id' => get_current_user_id(),
            ));
            wp_send_json_success(array('players' => $players));
        } else {
            bkgt_log('info', 'Team players - no players found', array(
                'team_id' => $team_id,
            ));
            wp_send_json_success(array('players' => array(), 'message' => __('Inga spelare hittade för detta lag.', 'bkgt-team-player')));
        }
    }

    /**
     * Save Event via AJAX
     */
    public function ajax_save_event() {
        // Verify nonce
        check_ajax_referer('bkgt_save_event');

        // Check permissions
        if (!current_user_can('manage_options') && !current_user_can('manage_team_calendar')) {
            bkgt_log('warning', 'Event save - insufficient permissions', array(
                'user_id' => get_current_user_id(),
            ));
            wp_send_json_error(array('message' => __('Du har inte behörighet att spara event.', 'bkgt-team-player')));
        }

        // Extract form data
        $raw_data = array(
            'event_id' => $_POST['event_id'] ?? '0',
            'event_title' => $_POST['event_title'] ?? '',
            'event_type' => $_POST['event_type'] ?? 'match',
            'event_date' => $_POST['event_date'] ?? '',
            'event_time' => $_POST['event_time'] ?? '',
            'event_location' => $_POST['event_location'] ?? '',
            'event_opponent' => $_POST['event_opponent'] ?? '',
            'event_notes' => $_POST['event_notes'] ?? '',
        );

        // Use BKGT_Sanitizer for context-aware data cleaning
        $sanitize_data = array(
            'title' => $raw_data['event_title'],
            'type' => $raw_data['event_type'],
            'date' => $raw_data['event_date'],
            'time' => $raw_data['event_time'],
            'location' => $raw_data['event_location'],
            'opponent' => $raw_data['event_opponent'],
            'notes' => $raw_data['event_notes'],
        );

        $sanitize_result = BKGT_Sanitizer::process($sanitize_data, 'event');
        $sanitized_data = $sanitize_result['data'];

        // Validate using BKGT_Validator
        $validation_result = BKGT_Validator::validate($sanitized_data, 'event');

        // Check for validation errors
        if (!empty($validation_result)) {
            $error_messages = array();
            foreach ($validation_result as $field => $errors) {
                if (is_array($errors)) {
                    $error_messages[] = implode(', ', $errors);
                } else {
                    $error_messages[] = $errors;
                }
            }
            wp_send_json_error(array('message' => implode(' | ', $error_messages)));
        }

        $event_id = intval($raw_data['event_id']);

        // Create or update post
        if ($event_id > 0) {
            // Update existing
            $post_data = array(
                'ID' => $event_id,
                'post_title' => $sanitized_data['title'],
                'post_content' => $sanitized_data['notes'],
            );
            $post_id = wp_update_post($post_data);
        } else {
            // Create new
            $post_data = array(
                'post_title' => $sanitized_data['title'],
                'post_content' => $sanitized_data['notes'],
                'post_type' => 'bkgt_event',
                'post_status' => 'publish',
            );
            $post_id = wp_insert_post($post_data);
        }

        if (is_wp_error($post_id)) {
            bkgt_log('error', 'Event save - post creation failed', array(
                'error' => $post_id->get_error_message(),
            ));
            wp_send_json_error(array('message' => __('Kunde inte spara event.', 'bkgt-team-player')));
        }

        // Save post meta with sanitized data
        update_post_meta($post_id, '_bkgt_event_type', $sanitized_data['type']);
        update_post_meta($post_id, '_bkgt_event_date', $sanitized_data['date']);
        update_post_meta($post_id, '_bkgt_event_time', $sanitized_data['time']);
        update_post_meta($post_id, '_bkgt_event_location', $sanitized_data['location']);
        update_post_meta($post_id, '_bkgt_event_opponent', $sanitized_data['opponent']);
        update_post_meta($post_id, '_bkgt_event_status', 'scheduled');

        bkgt_log('info', 'Event saved successfully', array(
            'post_id' => $post_id,
            'event_title' => $sanitized_data['title'],
            'user_id' => get_current_user_id(),
        ));

        wp_send_json_success(array('post_id' => $post_id, 'message' => __('Event sparad.', 'bkgt-team-player')));
    }

    /**
     * Delete Event via AJAX
     */
    public function ajax_delete_event() {
        // Verify nonce
        check_ajax_referer('bkgt_delete_event');

        // Check permissions
        if (!current_user_can('manage_options') && !current_user_can('manage_team_calendar')) {
            bkgt_log('warning', 'Event delete - insufficient permissions', array(
                'user_id' => get_current_user_id(),
            ));
            wp_send_json_error(array('message' => __('Du har inte behörighet att ta bort event.', 'bkgt-team-player')));
        }

        $event_id = intval($_POST['event_id'] ?? 0);

        if (empty($event_id)) {
            wp_send_json_error(array('message' => __('Event-ID krävs.', 'bkgt-team-player')));
        }

        $result = wp_delete_post($event_id, true);

        if (!$result) {
            bkgt_log('error', 'Event delete - failed', array(
                'event_id' => $event_id,
                'user_id' => get_current_user_id(),
            ));
            wp_send_json_error(array('message' => __('Kunde inte ta bort event.', 'bkgt-team-player')));
        }

        bkgt_log('info', 'Event deleted successfully', array(
            'event_id' => $event_id,
            'user_id' => get_current_user_id(),
        ));

        wp_send_json_success(array('message' => __('Event borttagen.', 'bkgt-team-player')));
    }

    /**
     * Get Event via AJAX
     */
    public function ajax_get_events() {
        // Verify nonce
        check_ajax_referer('bkgt_get_events');

        // Check permissions
        if (!current_user_can('manage_options') && !current_user_can('manage_team_calendar')) {
            bkgt_log('warning', 'Event get - insufficient permissions', array(
                'user_id' => get_current_user_id(),
            ));
            wp_send_json_error(array('message' => __('Du har inte behörighet att visa event.', 'bkgt-team-player')));
        }

        $event_id = intval($_POST['event_id'] ?? 0);

        if (empty($event_id)) {
            wp_send_json_error(array('message' => __('Event-ID krävs.', 'bkgt-team-player')));
        }

        $event = get_post($event_id);

        if (!$event || $event->post_type !== 'bkgt_event') {
            wp_send_json_error(array('message' => __('Event hittades inte.', 'bkgt-team-player')));
        }

        $event_data = array(
            'ID' => $event->ID,
            'post_title' => $event->post_title,
            'post_content' => $event->post_content,
            'event_type' => get_post_meta($event->ID, '_bkgt_event_type', true),
            'event_date' => get_post_meta($event->ID, '_bkgt_event_date', true),
            'event_time' => get_post_meta($event->ID, '_bkgt_event_time', true),
            'event_location' => get_post_meta($event->ID, '_bkgt_event_location', true),
            'event_opponent' => get_post_meta($event->ID, '_bkgt_event_opponent', true),
            'event_notes' => get_post_meta($event->ID, '_bkgt_event_status', true),
        );

        bkgt_log('info', 'Event retrieved', array(
            'event_id' => $event_id,
            'user_id' => get_current_user_id(),
        ));

        wp_send_json_success($event_data);
    }

    /**
     * Toggle Event Status via AJAX
     */
    public function ajax_toggle_event_status() {
        // Verify nonce
        check_ajax_referer('bkgt_toggle_event_status');

        // Check permissions
        if (!current_user_can('manage_options') && !current_user_can('manage_team_calendar')) {
            bkgt_log('warning', 'Event status toggle - insufficient permissions', array(
                'user_id' => get_current_user_id(),
            ));
            wp_send_json_error(array('message' => __('Du har inte behörighet att ändra event-status.', 'bkgt-team-player')));
        }

        $event_id = intval($_POST['event_id'] ?? 0);

        if (empty($event_id)) {
            wp_send_json_error(array('message' => __('Event-ID krävs.', 'bkgt-team-player')));
        }

        $event = get_post($event_id);

        if (!$event || $event->post_type !== 'bkgt_event') {
            wp_send_json_error(array('message' => __('Event hittades inte.', 'bkgt-team-player')));
        }

        // Get current status and toggle
        $current_status = get_post_meta($event_id, '_bkgt_event_status', true) ?: 'scheduled';
        $new_status = $current_status === 'scheduled' ? 'cancelled' : 'scheduled';

        update_post_meta($event_id, '_bkgt_event_status', $new_status);

        bkgt_log('info', 'Event status toggled', array(
            'event_id' => $event_id,
            'old_status' => $current_status,
            'new_status' => $new_status,
            'user_id' => get_current_user_id(),
        ));

        wp_send_json_success(array(
            'status' => $new_status,
            'message' => __('Event-status uppdaterad.', 'bkgt-team-player')
        ));
    }

    /**
     * Team Overview Shortcode - Used by page templates
     */
    public function team_overview_shortcode($atts) {
        // Guard: Check if BKGT Core is loaded
        if (!function_exists('bkgt_log')) {
            return '<div class="bkgt-team-overview"><p>' . __('Error: BKGT Core plugin is not loaded.', 'bkgt-team-player') . '</p></div>';
        }
        
        $atts = shortcode_atts(array(
            'show_stats' => 'true',
            'show_upcoming' => 'true'
        ), $atts);

        $output = '<div class="bkgt-team-overview">';

        if ($atts['show_stats'] === 'true') {
            $output .= $this->get_team_overview_stats();
        }

        if ($atts['show_upcoming'] === 'true') {
            $output .= $this->get_upcoming_events();
        }

        $output .= '</div>';
        return $output;
    }

    /**
     * Players Shortcode - Used by page templates
     */
    public function players_shortcode($atts) {
        $atts = shortcode_atts(array(
            'show_filters' => 'true',
            'layout' => 'grid', // 'grid' or 'list'
            'team' => '', // Filter by team
            'limit' => -1
        ), $atts);

        $output = '<div class="bkgt-players-display">';

        if ($atts['show_filters'] === 'true') {
            $output .= $this->get_players_filters();
        }

        $output .= '<div class="bkgt-players-' . esc_attr($atts['layout']) . '">';
        $output .= $this->get_players_list($atts);
        $output .= '</div>';

        $output .= '</div>';
        return $output;
    }

    /**
     * Get team overview statistics
     */
    private function get_team_overview_stats() {
        global $wpdb;

        try {
            $total_teams = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}bkgt_teams");
            if ($wpdb->last_error) {
                error_log('BKGT Team Player: Database error getting total teams: ' . $wpdb->last_error);
                $total_teams = 0;
            }

            $total_players = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}bkgt_players");
            if ($wpdb->last_error) {
                error_log('BKGT Team Player: Database error getting total players: ' . $wpdb->last_error);
                $total_players = 0;
            }

            $total_ratings = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}bkgt_performance_ratings");
            if ($wpdb->last_error) {
                error_log('BKGT Team Player: Database error getting total ratings: ' . $wpdb->last_error);
                $total_ratings = 0;
            }

            $output = '<div class="bkgt-overview-stats">';
            $output .= '<h3>' . __('Lagstatistik', 'bkgt-team-player') . '</h3>';
            $output .= '<div class="bkgt-stats-grid">';

            $output .= '<div class="bkgt-stat-item">';
            $output .= '<span class="bkgt-stat-number">' . intval($total_teams) . '</span>';
            $output .= '<span class="bkgt-stat-label">' . __('Aktiva Lag', 'bkgt-team-player') . '</span>';
            $output .= '</div>';

            $output .= '<div class="bkgt-stat-item">';
            $output .= '<span class="bkgt-stat-number">' . intval($total_players) . '</span>';
            $output .= '<span class="bkgt-stat-label">' . __('Registrerade Spelare', 'bkgt-team-player') . '</span>';
            $output .= '</div>';

            $output .= '<div class="bkgt-stat-item">';
            $output .= '<span class="bkgt-stat-number">' . intval($total_ratings) . '</span>';
            $output .= '<span class="bkgt-stat-label">' . __('Spelarutvärderingar', 'bkgt-team-player') . '</span>';
            $output .= '</div>';

            $output .= '</div></div>';
            return $output;
        } catch (Exception $e) {
            error_log('BKGT Team Player: Exception getting team overview stats: ' . $e->getMessage());
            return '<div class="bkgt-overview-stats"><h3>' . __('Lagstatistik', 'bkgt-team-player') . '</h3><p>' . __('Error loading statistics.', 'bkgt-team-player') . '</p></div>';
        }
    }

    /**
     * Get upcoming events
     */
    private function get_upcoming_events() {
        global $wpdb;
        
        try {
            // Try to get real upcoming events from database
            $events = $wpdb->get_results(
                "SELECT * FROM {$wpdb->prefix}bkgt_events 
                 WHERE event_date >= NOW()
                 ORDER BY event_date ASC
                 LIMIT 5"
            );
            
            if ($wpdb->last_error) {
                bkgt_log_safe('error', 'Error retrieving upcoming events', array('error' => $wpdb->last_error));
                $events = array();
            }
            
            $output = '<div class="bkgt-upcoming-events">';
            $output .= '<h3>' . __('Kommande Matcher & Träningar', 'bkgt-team-player') . '</h3>';
            
            if (!empty($events)) {
                // Display real events
                $output .= '<ul class="bkgt-events-list">';
                foreach ($events as $event) {
                    $event_date = new DateTime($event->event_date);
                    $output .= '<li class="bkgt-event-item">';
                    $output .= '<span class="bkgt-event-date">' . esc_html($event_date->format('Y-m-d H:i')) . '</span>';
                    $output .= '<span class="bkgt-event-title">' . esc_html($event->title ?? '') . '</span>';
                    $output .= '</li>';
                }
                $output .= '</ul>';
            } else {
                // No events - show helpful message for admins
                bkgt_log_safe('info', 'No upcoming events found, showing placeholder message');
                
                if (current_user_can('manage_options')) {
                    $output .= '<div class="bkgt-events-empty-admin">';
                    $output .= '<p>' . __('Inga kommande matcher eller träningar är schemalagda.', 'bkgt-team-player') . '</p>';
                    $output .= '<p>';
                    $output .= '<a href="' . esc_url(admin_url('admin.php?page=bkgt-team-player')) . '" class="button button-primary">';
                    $output .= __('Lägg till Event', 'bkgt-team-player');
                    $output .= '</a>';
                    $output .= '</p>';
                    $output .= '</div>';
                } else {
                    $output .= '<div class="bkgt-events-empty">';
                    $output .= '<p>' . __('Inga kommande matcher eller träningar är schemalagda för närvarande.', 'bkgt-team-player') . '</p>';
                    $output .= '</div>';
                }
            }
            
            $output .= '</div>';
            return $output;
        } catch (Exception $e) {
            bkgt_log('error', 'Exception getting upcoming events', array('error' => $e->getMessage()));
            $output = '<div class="bkgt-upcoming-events">';
            $output .= '<h3>' . __('Kommande Matcher & Träningar', 'bkgt-team-player') . '</h3>';
            $output .= '<p>' . __('Kalenderintegration kommer snart. Använd svenskalag.se för nuvarande schemaläggning.', 'bkgt-team-player') . '</p>';
            $output .= '</div>';
            return $output;
        }
    }

    /**
     * Get players filters
     */
    private function get_players_filters() {
        global $wpdb;

        try {
            $teams = $wpdb->get_results("SELECT id, name FROM {$wpdb->prefix}bkgt_teams ORDER BY name ASC");

            if ($wpdb->last_error) {
                error_log('BKGT Team Player: Database error getting teams for filters: ' . $wpdb->last_error);
                $teams = array();
            }

            $output = '<div class="bkgt-players-filters">';
            $output .= '<form method="get" class="bkgt-filter-form">';

            $output .= '<div class="bkgt-filter-group">';
            $output .= '<label for="team_filter">' . __('Filtrera efter lag:', 'bkgt-team-player') . '</label>';
            $output .= '<select name="team" id="team_filter">';
            $output .= '<option value="">' . __('Alla lag', 'bkgt-team-player') . '</option>';
            foreach ($teams as $team) {
                $selected = (isset($_GET['team']) && $_GET['team'] == $team->id) ? ' selected' : '';
                $output .= '<option value="' . esc_attr($team->id) . '"' . $selected . '>' . esc_html($team->name) . '</option>';
            }
            $output .= '</select>';
            $output .= '</div>';

            $output .= '<div class="bkgt-filter-group">';
            $output .= '<label for="search_filter">' . __('Sök spelare:', 'bkgt-team-player') . '</label>';
            $output .= '<input type="text" name="search" id="search_filter" value="' . esc_attr(isset($_GET['search']) ? $_GET['search'] : '') . '" placeholder="' . __('Sök efter namn...', 'bkgt-team-player') . '">';
            $output .= '</div>';

            $output .= '<button type="submit" class="button">' . __('Filtrera', 'bkgt-team-player') . '</button>';
            $output .= '</form>';
            $output .= '</div>';

            return $output;
        } catch (Exception $e) {
            error_log('BKGT Team Player: Exception getting players filters: ' . $e->getMessage());
            return '<div class="bkgt-players-filters"><p>' . __('Error loading filters.', 'bkgt-team-player') . '</p></div>';
        }
    }

    /**
     * Get players list
     */
    private function get_players_list($atts) {
        global $wpdb;

        try {
            $where_clauses = array();
            $join_clauses = array();
            $params = array();

            // Team filter
            if (!empty($atts['team'])) {
                $where_clauses[] = "p.team_id = %d";
                $params[] = intval($atts['team']);
            }

            // Search filter
            if (!empty($_GET['search'])) {
                $where_clauses[] = "CONCAT(p.first_name, ' ', p.last_name) LIKE %s";
                $params[] = '%' . $wpdb->esc_like($_GET['search']) . '%';
            }

            $where_sql = implode(' AND ', $where_clauses);
            $where_clause = !empty($where_sql) ? "WHERE {$where_sql}" : "";

            $limit_sql = '';
            if ($atts['limit'] > 0) {
                $limit_sql = $wpdb->prepare("LIMIT %d", intval($atts['limit']));
            }

            $query = "SELECT p.*, t.name as team_name
                      FROM {$wpdb->prefix}bkgt_players p
                      LEFT JOIN {$wpdb->prefix}bkgt_teams t ON p.team_id = t.id
                      {$where_clause}
                      ORDER BY CONCAT(p.first_name, ' ', p.last_name) ASC
                      {$limit_sql}";

            if (!empty($params)) {
                $players = $wpdb->get_results($wpdb->prepare($query, $params));
            } else {
                $players = $wpdb->get_results($query);
            }

            if ($wpdb->last_error) {
                error_log('BKGT Team Player: Database error getting players list: ' . $wpdb->last_error);
                return '<p>' . __('Error loading players.', 'bkgt-team-player') . '</p>';
            }

            if (empty($players)) {
                return '<p>' . __('Inga spelare hittades.', 'bkgt-team-player') . '</p>';
            }

            $output = '';
            foreach ($players as $player) {
                if ($atts['layout'] === 'grid') {
                    $output .= $this->render_player_card_frontend($player);
                } else {
                    $output .= $this->render_player_list_item($player);
                }
            }

            return $output;
        } catch (Exception $e) {
            error_log('BKGT Team Player: Exception getting players list: ' . $e->getMessage());
            return '<p>' . __('Error loading players.', 'bkgt-team-player') . '</p>';
        }
    }

    /**
     * Render player card for frontend
     */
    private function render_player_card_frontend($player) {
        $output = '<div class="bkgt-player-card-frontend">';
        $output .= '<div class="bkgt-player-avatar">';
        $output .= '<span class="dashicons dashicons-admin-users"></span>';
        $output .= '</div>';

        $output .= '<div class="bkgt-player-info">';
        $output .= '<h4>' . esc_html($player->first_name . ' ' . $player->last_name) . '</h4>';
        if (!empty($player->team_name)) {
            $output .= '<p class="bkgt-player-team">' . esc_html($player->team_name) . '</p>';
        }
        if (!empty($player->position)) {
            $output .= '<p class="bkgt-player-position">' . esc_html($player->position) . '</p>';
        }
        $output .= '</div>';

        $output .= '<div class="bkgt-player-actions">';
        $output .= '<a href="' . esc_url(get_permalink(52) . '?player=' . $player->id) . '" class="button button-small">' . __('Visa Profil', 'bkgt-team-player') . '</a>';
        $output .= '</div>';

        $output .= '</div>';
        return $output;
    }

    /**
     * Render player list item
     */
    private function render_player_list_item($player) {
        $output = '<div class="bkgt-player-list-item">';
        $output .= '<div class="bkgt-player-list-info">';
        $output .= '<strong>' . esc_html($player->first_name . ' ' . $player->last_name) . '</strong>';
        if (!empty($player->team_name)) {
            $output .= ' - ' . esc_html($player->team_name);
        }
        if (!empty($player->position)) {
            $output .= ' (' . esc_html($player->position) . ')';
        }
        $output .= '</div>';
        $output .= '<a href="' . esc_url(get_permalink(52) . '?player=' . $player->id) . '" class="button button-small">' . __('Visa', 'bkgt-team-player') . '</a>';
        $output .= '</div>';
        return $output;
    }

    /**
     * Events Shortcode - Used by page templates
     */
    public function events_shortcode($atts) {
        $atts = shortcode_atts(array(
            'upcoming' => 'true',
            'limit' => 10,
            'layout' => 'list', // 'list' or 'calendar'
            'team' => '' // Filter by team
        ), $atts);

        $output = '<div class="bkgt-events-display">';

        if ($atts['layout'] === 'calendar') {
            $output .= $this->get_events_calendar();
        } else {
            $output .= $this->get_events_list($atts);
        }

        $output .= '</div>';
        return $output;
    }

    /**
     * Get events list
     */
    private function get_events_list($atts) {
        $output = '<div class="bkgt-events-list">';

        // Query events from database
        $args = array(
            'post_type' => 'bkgt_event',
            'posts_per_page' => intval($atts['limit']),
            'orderby' => 'meta_value',
            'meta_key' => '_bkgt_event_date',
            'order' => 'ASC',
            'meta_type' => 'DATE',
            'meta_query' => array(),
        );

        // Filter by upcoming events if requested
        if ($atts['upcoming'] === 'true') {
            $today = date('Y-m-d');
            $args['meta_query'][] = array(
                'key' => '_bkgt_event_date',
                'value' => $today,
                'compare' => '>=',
                'type' => 'DATE'
            );
            $output .= '<h3>' . __('Kommande Matcher & Event', 'bkgt-team-player') . '</h3>';
        } else {
            $output .= '<h3>' . __('Matcher & Event', 'bkgt-team-player') . '</h3>';
        }

        $events = get_posts($args);

        if (empty($events)) {
            $output .= '<div class="bkgt-events-empty">';
            $output .= '<p>' . __('Inga event schemalägda för närvarande.', 'bkgt-team-player') . '</p>';
            $output .= '</div>';
        } else {
            $output .= '<div class="bkgt-events-container">';

            foreach ($events as $event) {
                $event_date = get_post_meta($event->ID, '_bkgt_event_date', true);
                $event_time = get_post_meta($event->ID, '_bkgt_event_time', true);
                $event_type = get_post_meta($event->ID, '_bkgt_event_type', true);
                $event_location = get_post_meta($event->ID, '_bkgt_event_location', true);
                $event_opponent = get_post_meta($event->ID, '_bkgt_event_opponent', true);
                $event_status = get_post_meta($event->ID, '_bkgt_event_status', true) ?: 'scheduled';

                // Format date and time
                $date_obj = DateTime::createFromFormat('Y-m-d', $event_date);
                $date_display = $date_obj ? $date_obj->format('j M Y') : $event_date;
                $time_display = $event_time ?: '—';

                // Determine event type display
                $type_badge = '';
                if ($event_type === 'match') {
                    $type_badge = '<span class="bkgt-event-badge match">' . __('Match', 'bkgt-team-player') . '</span>';
                } elseif ($event_type === 'training') {
                    $type_badge = '<span class="bkgt-event-badge training">' . __('Träning', 'bkgt-team-player') . '</span>';
                } else {
                    $type_badge = '<span class="bkgt-event-badge meeting">' . __('Möte', 'bkgt-team-player') . '</span>';
                }

                // Determine status class
                $status_class = $event_status === 'cancelled' ? 'cancelled' : 'active';

                $output .= '<div class="bkgt-event-card ' . $status_class . '" data-event-date="' . esc_attr($event_date) . '">';

                // Event header with date and time
                $output .= '<div class="bkgt-event-header">';
                $output .= '<div class="bkgt-event-datetime">';
                $output .= '<div class="bkgt-event-date">' . esc_html($date_display) . '</div>';
                $output .= '<div class="bkgt-event-time">' . esc_html($time_display) . '</div>';
                $output .= '</div>';
                $output .= '<div class="bkgt-event-badge-container">' . $type_badge . '</div>';
                $output .= '</div>';

                // Event content
                $output .= '<div class="bkgt-event-content">';
                $output .= '<h4 class="bkgt-event-title">' . esc_html($event->post_title) . '</h4>';

                // Opponent info
                if (!empty($event_opponent)) {
                    $output .= '<div class="bkgt-event-opponent">';
                    $output .= '<span class="label">' . __('Motståndare:', 'bkgt-team-player') . '</span> ';
                    $output .= '<span class="value">' . esc_html($event_opponent) . '</span>';
                    $output .= '</div>';
                }

                // Location info
                if (!empty($event_location)) {
                    $output .= '<div class="bkgt-event-location">';
                    $output .= '<span class="dashicons dashicons-location-alt"></span> ';
                    $output .= '<span>' . esc_html($event_location) . '</span>';
                    $output .= '</div>';
                }

                // Status indicator for cancelled events
                if ($event_status === 'cancelled') {
                    $output .= '<div class="bkgt-event-status">';
                    $output .= '<span class="status-label">' . __('INSTÄLLT', 'bkgt-team-player') . '</span>';
                    $output .= '</div>';
                }

                // Notes
                if (!empty($event->post_content)) {
                    $output .= '<div class="bkgt-event-notes">';
                    $output .= wp_kses_post($event->post_content);
                    $output .= '</div>';
                }

                $output .= '</div>'; // .bkgt-event-content
                $output .= '</div>'; // .bkgt-event-card
            }

            $output .= '</div>'; // .bkgt-events-container
        }

        $output .= '</div>';
        return $output;
    }

    /**
     * Get events calendar (with fallback for no events)
     */
    private function get_events_calendar() {
        global $wpdb;
        
        try {
            // Check if we have any events in the system
            $event_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}bkgt_events");
            
            if ($wpdb->last_error) {
                bkgt_log('error', 'Error counting events', array('error' => $wpdb->last_error));
                $event_count = 0;
            }
            
            $output = '<div class="bkgt-events-calendar">';
            $output .= '<h3>' . __('Matcher & Event Kalender', 'bkgt-team-player') . '</h3>';
            
            if ($event_count > 0) {
                // Show actual calendar if events exist
                $output .= '<div class="bkgt-calendar-view">';
                $output .= '<div class="bkgt-calendar-list">';
                
                // Retrieve and display events
                $events = $wpdb->get_results(
                    "SELECT * FROM {$wpdb->prefix}bkgt_events 
                     ORDER BY event_date ASC
                     LIMIT 30"
                );
                
                foreach ($events as $event) {
                    $event_date = new DateTime($event->event_date);
                    $output .= '<div class="bkgt-calendar-event">';
                    $output .= '<div class="bkgt-calendar-event-date">' . esc_html($event_date->format('M d')) . '</div>';
                    $output .= '<div class="bkgt-calendar-event-title">' . esc_html($event->title ?? '') . '</div>';
                    $output .= '</div>';
                }
                
                $output .= '</div></div>';
            } else {
                // No events - show helpful fallback
                bkgt_log('info', 'No events found for calendar view, showing fallback notice');
                
                $output .= '<div class="bkgt-calendar-fallback">';
                $output .= '<div class="bkgt-calendar-empty-notice">';
                
                if (current_user_can('manage_options')) {
                    $output .= '<p class="bkgt-calendar-message">';
                    $output .= __('Inga matcher eller träningar är schemalagda än.', 'bkgt-team-player');
                    $output .= '</p>';
                    $output .= '<p>';
                    $output .= '<a href="' . esc_url(admin_url('admin.php?page=bkgt-team-player')) . '" class="button button-primary">';
                    $output .= __('Lägg till första evenemang', 'bkgt-team-player');
                    $output .= '</a> ';
                    $output .= '<a href="' . esc_url(admin_url('admin.php?page=bkgt-events')) . '" class="button">';
                    $output .= __('Till Event Manager', 'bkgt-team-player');
                    $output .= '</a>';
                    $output .= '</p>';
                    $output .= '<p class="bkgt-calendar-hint">';
                    $output .= __('Kalendarvy aktiveras automatiskt när du lägger till ditt första event.', 'bkgt-team-player');
                    $output .= '</p>';
                } else {
                    $output .= '<p class="bkgt-calendar-message">';
                    $output .= __('Inga matcher eller träningar är schemalagda för närvarande.', 'bkgt-team-player');
                    $output .= '</p>';
                    $output .= '<p class="bkgt-calendar-hint">';
                    $output .= __('Kontakta administratören för att schemalägga events.', 'bkgt-team-player');
                    $output .= '</p>';
                }
                
                $output .= '</div></div>';
            }
            
            $output .= '</div>';
            return $output;
        } catch (Exception $e) {
            bkgt_log('error', 'Exception in calendar rendering', array('error' => $e->getMessage()));
            
            $output = '<div class="bkgt-events-calendar">';
            $output .= '<h3>' . __('Matcher & Event Kalender', 'bkgt-team-player') . '</h3>';
            $output .= '<div class="bkgt-calendar-error">';
            $output .= '<p>' . __('Kunde inte ladda kalender. Försök igen senare.', 'bkgt-team-player') . '</p>';
            $output .= '</div>';
            $output .= '</div>';
            return $output;
        }
    }
}

/**
 * Initialize the plugin
 */
function bkgt_team_player_management() {
    return BKGT_Team_Player_Management::get_instance();
}
add_action('plugins_loaded', 'bkgt_team_player_management');

// Include database class
require_once BKGT_TP_PLUGIN_DIR . 'includes/class-database.php';
?>