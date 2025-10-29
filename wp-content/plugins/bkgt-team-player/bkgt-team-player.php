<?php
/**
 * Plugin Name: BKGT Team & Player Management
 * Plugin URI: https://bkgt.se
 * Description: Team pages, player dossiers, and performance management for BKGTS.
 * Version: 1.0.0
 * Author: BKGT Amerikansk Fotboll
 * License: GPL v2 or later
 * Text Domain: bkgt-team-player
 */

if (!defined('ABSPATH')) {
    exit;
}

define('BKGT_TP_VERSION', '1.0.0');
define('BKGT_TP_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('BKGT_TP_PLUGIN_URL', plugin_dir_url(__FILE__));

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

        // Get data for overview
        global $wpdb;
        $total_teams = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}bkgt_teams");
        $total_players = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}bkgt_players");
        $recent_ratings = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}bkgt_performance_ratings WHERE created_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)");

        ?>
        <div class="wrap">
            <h1><?php _e('Team & Player Management', 'bkgt-team-player'); ?></h1>

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
                        <p><?php _e('Prestandabetyg (senaste veckan)', 'bkgt-team-player'); ?></p>
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
        $player_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}bkgt_players WHERE team_id = %d",
            $team->ID
        ));

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
                <button class="button button-primary" disabled>
                    <span class="dashicons dashicons-plus"></span>
                    <?php _e('Schemalägg Event', 'bkgt-team-player'); ?>
                </button>
            </div>

            <div class="bkgt-events-placeholder">
                <div class="bkgt-placeholder-icon">
                    <span class="dashicons dashicons-calendar-alt"></span>
                </div>
                <h3><?php _e('Eventhantering Kommer Snart', 'bkgt-team-player'); ?></h3>
                <p><?php _e('Denna funktion är under utveckling. Använd för närvarande svenskalag.se för att schemalägga matcher och träningar.', 'bkgt-team-player'); ?></p>
            </div>
        </div>
        <?php
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
                    $total_ratings = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}bkgt_performance_ratings");
                    $avg_rating = $wpdb->get_var("SELECT AVG(overall_rating) FROM {$wpdb->prefix}bkgt_performance_ratings");
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

        if (!empty($atts['team'])) {
            // Get specific team
            $team = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}bkgt_teams WHERE slug = %s",
                $atts['team']
            ));

            if ($team) {
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
            $teams = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}bkgt_teams WHERE status = 'active' ORDER BY name ASC");

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

        $output .= '</div>';
        return $output;
    }

    private function get_team_roster($team_id) {
        global $wpdb;

        $players = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}bkgt_players
             WHERE team_id = %d AND status = 'active'
             ORDER BY jersey_number ASC, last_name ASC",
            $team_id
        ));

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
            $output .= '<td><a href="' . esc_url(add_query_arg('player', $player->id)) . '">' . esc_html($player->display_name) . '</a></td>';
            $output .= '<td>' . (empty($player->position) ? '-' : esc_html($player->position)) . '</td>';
            $output .= '</tr>';
        }

        $output .= '</tbody></table></div>';
        return $output;
    }

    private function get_team_stats($team_id) {
        global $wpdb;

        // Get team performance summary
        $stats = $wpdb->get_row($wpdb->prepare(
            "SELECT
                COUNT(DISTINCT p.id) as total_players,
                AVG(r.overall_rating) as avg_rating,
                COUNT(r.id) as total_ratings
             FROM {$wpdb->prefix}bkgt_players p
             LEFT JOIN {$wpdb->prefix}bkgt_performance_ratings r ON p.id = r.player_id
             WHERE p.team_id = %d AND p.status = 'active'",
            $team_id
        ));

        if (!$stats) {
            return '';
        }

        $output = '<h3>' . __('Team Statistics', 'bkgt-team-player') . '</h3>';
        $output .= '<div class="bkgt-team-stats">';
        $output .= '<p><strong>' . __('Total Players:', 'bkgt-team-player') . '</strong> ' . esc_html($stats->total_players) . '</p>';

        if ($stats->total_ratings > 0) {
            $output .= '<p><strong>' . __('Average Performance Rating:', 'bkgt-team-player') . '</strong> ' . number_format($stats->avg_rating, 1) . '/5.0</p>';
        }

        $output .= '</div>';
        return $output;
    }

    public function player_dossier_shortcode($atts) {
        global $wpdb;

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

            if ($player) {
                $output .= '<h2>' . esc_html($player->display_name) . '</h2>';

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
    }

    private function get_player_ratings($player_id) {
        global $wpdb;

        $ratings = $wpdb->get_results($wpdb->prepare(
            "SELECT r.*, u.display_name as rater_name
             FROM {$wpdb->prefix}bkgt_performance_ratings r
             LEFT JOIN {$wpdb->users} u ON r.rater_id = u.ID
             WHERE r.player_id = %d
             ORDER BY r.rating_date DESC
             LIMIT 5",
            $player_id
        ));

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
    }

    private function get_player_stats($player_id) {
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
    }

    private function get_player_notes($player_id) {
        global $wpdb;

        $notes = $wpdb->get_results($wpdb->prepare(
            "SELECT n.*, u.display_name as author_name
             FROM {$wpdb->prefix}bkgt_player_notes n
             LEFT JOIN {$wpdb->users} u ON n.author_id = u.ID
             WHERE n.player_id = %d AND n.is_private = 0
             ORDER BY n.created_date DESC
             LIMIT 10",
            $player_id
        ));

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
    }

    public function performance_page_shortcode($atts) {
        // Check permissions - only coaches and board members
        if (!current_user_can('manage_options') && !current_user_can('edit_posts')) {
            return '<p>' . __('You do not have permission to view this page.', 'bkgt-team-player') . '</p>';
        }

        global $wpdb;

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
    }

    private function get_user_teams() {
        global $wpdb;

        // For board members, return all teams
        if (current_user_can('manage_options')) {
            return $wpdb->get_results("SELECT * FROM {$wpdb->prefix}bkgt_teams WHERE status = 'active' ORDER BY name ASC");
        }

        // For coaches, return only their teams (this would need to be implemented based on user-team relationships)
        // For now, return all teams - this should be restricted based on actual permissions
        return $wpdb->get_results("SELECT * FROM {$wpdb->prefix}bkgt_teams WHERE status = 'active' ORDER BY name ASC");
    }

    private function performance_rating_form() {
        global $wpdb;

        $teams = $this->get_user_teams();
        $players = array();

        if (!empty($_GET['team'])) {
            $team = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}bkgt_teams WHERE slug = %s",
                sanitize_text_field($_GET['team'])
            ));

            if ($team) {
                $players = $wpdb->get_results($wpdb->prepare(
                    "SELECT * FROM {$wpdb->prefix}bkgt_players
                     WHERE team_id = %d AND status = 'active'
                     ORDER BY display_name ASC",
                    $team->id
                ));
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
            $output .= '<option value="' . esc_attr($player->id) . '">' . esc_html($player->display_name) . '</option>';
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
    }

    private function get_team_performance_ratings($team_id) {
        global $wpdb;

        $ratings = $wpdb->get_results($wpdb->prepare(
            "SELECT r.*, p.display_name as player_name, u.display_name as rater_name
             FROM {$wpdb->prefix}bkgt_performance_ratings r
             LEFT JOIN {$wpdb->prefix}bkgt_players p ON r.player_id = p.id
             LEFT JOIN {$wpdb->users} u ON r.rater_id = u.ID
             WHERE r.team_id = %d
             ORDER BY r.rating_date DESC
             LIMIT 50",
            $team_id
        ));

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
        
        // Evaluations this month
        $stats['evaluations_this_month'] = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}bkgt_performance_ratings 
             WHERE team_id = %d AND MONTH(rating_date) = MONTH(CURDATE()) AND YEAR(rating_date) = YEAR(CURDATE())",
            $team_id
        ));
        
        // Average rating and rating distribution
        $ratings = $wpdb->get_results($wpdb->prepare(
            "SELECT overall_rating FROM {$wpdb->prefix}bkgt_performance_ratings 
             WHERE team_id = %d",
            $team_id
        ));
        
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
            "SELECT p.display_name, AVG(r.overall_rating) as avg_rating
             FROM {$wpdb->prefix}bkgt_performance_ratings r
             JOIN {$wpdb->prefix}bkgt_players p ON r.player_id = p.id
             WHERE r.team_id = %d
             GROUP BY r.player_id
             ORDER BY avg_rating DESC
             LIMIT 1",
            $team_id
        ));
        
        if ($top_performer) {
            $stats['top_performer'] = $top_performer->display_name;
        }
        
        return $stats;
    }
    
    private function get_player_performance_stats($team_id) {
        global $wpdb;
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT 
                p.display_name as player_name,
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
    }
    
    private function get_performance_trends($team_id) {
        global $wpdb;
        
        return $wpdb->get_results($wpdb->prepare(
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
        // Check permissions
        if (!current_user_can('manage_options') && !current_user_can('edit_posts')) {
            wp_die(__('Insufficient permissions', 'bkgt-team-player'));
        }

        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'bkgt_team_player_nonce')) {
            wp_die(__('Security check failed', 'bkgt-team-player'));
        }

        global $wpdb;

        $player_id = intval($_POST['player_id']);
        $note_type = sanitize_text_field($_POST['note_type']);
        $title = sanitize_text_field($_POST['title']);
        $content = wp_kses_post($_POST['content']);
        $is_private = isset($_POST['is_private']) ? 1 : 0;

        $result = $wpdb->insert(
            $wpdb->prefix . 'bkgt_player_notes',
            array(
                'player_id' => $player_id,
                'author_id' => get_current_user_id(),
                'note_type' => $note_type,
                'title' => $title,
                'content' => $content,
                'is_private' => $is_private
            ),
            array('%d', '%d', '%s', '%s', '%s', '%d')
        );

        if ($result) {
            wp_send_json_success(array('message' => __('Note saved successfully', 'bkgt-team-player')));
        } else {
            wp_send_json_error(array('message' => __('Failed to save note', 'bkgt-team-player')));
        }
    }

    public function ajax_save_performance_rating() {
        // Check permissions
        if (!current_user_can('manage_options') && !current_user_can('edit_posts')) {
            wp_die(__('Insufficient permissions', 'bkgt-team-player'));
        }

        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'bkgt_performance_nonce')) {
            wp_die(__('Security check failed', 'bkgt-team-player'));
        }

        global $wpdb;

        $player_id = intval($_POST['player_id']);
        $team_id = intval($_POST['team_id']);
        $enthusiasm = intval($_POST['enthusiasm_rating']);
        $performance = intval($_POST['performance_rating']);
        $skill = intval($_POST['skill_rating']);
        $comments = sanitize_textarea_field($_POST['comments']);
        $season = sanitize_text_field($_POST['season']);

        // Validate ratings
        if ($enthusiasm < 1 || $enthusiasm > 5 || $performance < 1 || $performance > 5 || $skill < 1 || $skill > 5) {
            wp_send_json_error(array('message' => __('Invalid rating values', 'bkgt-team-player')));
            return;
        }

        $result = $wpdb->insert(
            $wpdb->prefix . 'bkgt_performance_ratings',
            array(
                'player_id' => $player_id,
                'team_id' => $team_id,
                'rater_id' => get_current_user_id(),
                'enthusiasm_rating' => $enthusiasm,
                'performance_rating' => $performance,
                'skill_rating' => $skill,
                'comments' => $comments,
                'season' => $season
            ),
            array('%d', '%d', '%d', '%d', '%d', '%d', '%s', '%s')
        );

        if ($result) {
            wp_send_json_success(array('message' => __('Performance rating saved successfully', 'bkgt-team-player')));
        } else {
            wp_send_json_error(array('message' => __('Failed to save performance rating', 'bkgt-team-player')));
        }
    }

    public function ajax_get_player_stats() {
        global $wpdb;

        $player_id = intval($_POST['player_id']);

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
            wp_send_json_success($stats);
        } else {
            wp_send_json_error(array('message' => __('No statistics found', 'bkgt-team-player')));
        }
    }

    // AJAX handler for getting team performance ratings (admin)
    public function ajax_get_team_performance() {
        // Check permissions
        if (!current_user_can('manage_options') && !current_user_can('edit_posts')) {
            wp_die(__('Insufficient permissions', 'bkgt-team-player'));
        }

        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'bkgt_performance_nonce')) {
            wp_die(__('Security check failed', 'bkgt-team-player'));
        }

        global $wpdb;

        $team_id = intval($_POST['team_id']);

        $ratings = $wpdb->get_results($wpdb->prepare(
            "SELECT r.*, p.display_name as player_name, u.display_name as rater_name
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
        $html .= '<th>' . __('Player', 'bkgt-team-player') . '</th>';
        $html .= '<th>' . __('Date', 'bkgt-team-player') . '</th>';
        $html .= '<th>' . __('Rater', 'bkgt-team-player') . '</th>';
        $html .= '<th>' . __('Overall Rating', 'bkgt-team-player') . '</th>';
        $html .= '<th>' . __('Comments', 'bkgt-team-player') . '</th>';
        $html .= '</tr></thead><tbody>';

        if (empty($ratings)) {
            $html .= '<tr><td colspan="5">' . __('No ratings found for this team.', 'bkgt-team-player') . '</td></tr>';
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

        wp_send_json_success(array('html' => $html));
    }

    public function ajax_get_team_players() {
        global $wpdb;

        $team_id = intval($_POST['team_id']);

        $players = $wpdb->get_results($wpdb->prepare(
            "SELECT id, display_name
             FROM {$wpdb->prefix}bkgt_players
             WHERE team_id = %d AND status = 'active'
             ORDER BY display_name ASC",
            $team_id
        ));

        wp_send_json_success(array('players' => $players));
    }

    /**
     * Team Overview Shortcode - Used by page templates
     */
    public function team_overview_shortcode($atts) {
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

        $total_teams = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}bkgt_teams WHERE status = 'active'");
        $total_players = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}bkgt_players WHERE status = 'active'");
        $total_ratings = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}bkgt_performance_ratings");

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
        $output .= '<span class="bkgt-stat-label">' . __('Prestandabetyg', 'bkgt-team-player') . '</span>';
        $output .= '</div>';

        $output .= '</div></div>';
        return $output;
    }

    /**
     * Get upcoming events
     */
    private function get_upcoming_events() {
        // Placeholder for upcoming events - would integrate with calendar system
        $output = '<div class="bkgt-upcoming-events">';
        $output .= '<h3>' . __('Kommande Matcher & Träningar', 'bkgt-team-player') . '</h3>';
        $output .= '<p>' . __('Kalenderintegration kommer snart. Använd svenskalag.se för nuvarande schemaläggning.', 'bkgt-team-player') . '</p>';
        $output .= '</div>';
        return $output;
    }

    /**
     * Get players filters
     */
    private function get_players_filters() {
        global $wpdb;

        $teams = $wpdb->get_results("SELECT id, name FROM {$wpdb->prefix}bkgt_teams WHERE status = 'active' ORDER BY name ASC");

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
    }

    /**
     * Get players list
     */
    private function get_players_list($atts) {
        global $wpdb;

        $where_clauses = array("p.status = 'active'");
        $join_clauses = array();
        $params = array();

        // Team filter
        if (!empty($atts['team'])) {
            $where_clauses[] = "p.team_id = %d";
            $params[] = intval($atts['team']);
        }

        // Search filter
        if (!empty($_GET['search'])) {
            $where_clauses[] = "p.display_name LIKE %s";
            $params[] = '%' . $wpdb->esc_like($_GET['search']) . '%';
        }

        $where_sql = implode(' AND ', $where_clauses);

        $limit_sql = '';
        if ($atts['limit'] > 0) {
            $limit_sql = $wpdb->prepare("LIMIT %d", intval($atts['limit']));
        }

        $query = "SELECT p.*, t.name as team_name
                  FROM {$wpdb->prefix}bkgt_players p
                  LEFT JOIN {$wpdb->prefix}bkgt_teams t ON p.team_id = t.id
                  WHERE {$where_sql}
                  ORDER BY p.display_name ASC
                  {$limit_sql}";

        if (!empty($params)) {
            $players = $wpdb->get_results($wpdb->prepare($query, $params));
        } else {
            $players = $wpdb->get_results($query);
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
        $output .= '<h4>' . esc_html($player->display_name) . '</h4>';
        if (!empty($player->team_name)) {
            $output .= '<p class="bkgt-player-team">' . esc_html($player->team_name) . '</p>';
        }
        if (!empty($player->position)) {
            $output .= '<p class="bkgt-player-position">' . esc_html($player->position) . '</p>';
        }
        $output .= '</div>';

        $output .= '<div class="bkgt-player-actions">';
        $output .= '<a href="' . esc_url(get_permalink(get_page_by_path('spelare')->ID) . '?player=' . $player->id) . '" class="button button-small">' . __('Visa Profil', 'bkgt-team-player') . '</a>';
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
        $output .= '<strong>' . esc_html($player->display_name) . '</strong>';
        if (!empty($player->team_name)) {
            $output .= ' - ' . esc_html($player->team_name);
        }
        if (!empty($player->position)) {
            $output .= ' (' . esc_html($player->position) . ')';
        }
        $output .= '</div>';
        $output .= '<a href="' . esc_url(get_permalink(get_page_by_path('spelare')->ID) . '?player=' . $player->id) . '" class="button button-small">' . __('Visa', 'bkgt-team-player') . '</a>';
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
        // For now, return placeholder content since full calendar integration is pending
        $output = '<div class="bkgt-events-list">';

        if ($atts['upcoming'] === 'true') {
            $output .= '<h3>' . __('Kommande Matcher & Event', 'bkgt-team-player') . '</h3>';
            $output .= '<div class="bkgt-events-placeholder">';
            $output .= '<div class="bkgt-placeholder-icon">📅</div>';
            $output .= '<h4>' . __('Kalenderintegration Under Utveckling', 'bkgt-team-player') . '</h4>';
            $output .= '<p>' . __('Vi arbetar på att integrera en fullständig kalenderlösning. Under tiden kan du:', 'bkgt-team-player') . '</p>';
            $output .= '<ul>';
            $output .= '<li>' . __('Använda <a href="https://svenskalag.se/bkgt" target="_blank">svenskalag.se</a> för schemaläggning', 'bkgt-team-player') . '</li>';
            $output .= '<li>' . __('Kontakta tränare för träningstider', 'bkgt-team-player') . '</li>';
            $output .= '<li>' . __('Följa våra sociala medier för uppdateringar', 'bkgt-team-player') . '</li>';
            $output .= '</ul>';
            $output .= '</div>';
        }

        $output .= '</div>';
        return $output;
    }

    /**
     * Get events calendar (placeholder)
     */
    private function get_events_calendar() {
        $output = '<div class="bkgt-events-calendar">';
        $output .= '<h3>' . __('Matcher & Event Kalender', 'bkgt-team-player') . '</h3>';
        $output .= '<div class="bkgt-calendar-placeholder">';
        $output .= '<p>' . __('Kalendervy kommer snart. Använd listvyn ovan eller svenskalag.se.', 'bkgt-team-player') . '</p>';
        $output .= '</div>';
        $output .= '</div>';
        return $output;
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