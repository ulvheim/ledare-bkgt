<?php
/**
 * Template Name: BKGT Admin Dashboard
 * Description: Front-end admin dashboard for BKGT data management
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

get_header();

// Check if user has admin capabilities
if (!current_user_can('manage_options')) {
    echo '<div class="bkgt-access-denied"><p>Du har inte behörighet att komma åt denna sida.</p></div>';
    get_footer();
    exit;
}

// Enqueue admin scripts and styles for front-end
wp_enqueue_script('bkgt-admin-js', BKGT_DATA_SCRAPING_PLUGIN_URL . 'admin/js/admin.js', array('jquery'), '1.0.0', true);
wp_enqueue_style('bkgt-admin-css', BKGT_DATA_SCRAPING_PLUGIN_URL . 'admin/css/admin.css', array(), '1.0.0');
wp_enqueue_style('bkgt-frontend-css', BKGT_DATA_SCRAPING_PLUGIN_URL . 'assets/css/frontend.css', array(), '1.0.0');

// Localize script for AJAX
wp_localize_script('bkgt-admin-js', 'bkgt_ajax', array(
    'ajax_url' => admin_url('admin-ajax.php'),
    'nonce' => wp_create_nonce('bkgt_admin_nonce')
));

global $wpdb;
$players_table = $wpdb->prefix . 'bkgt_players';
$events_table = $wpdb->prefix . 'bkgt_events';
$teams_table = $wpdb->prefix . 'bkgt_teams';
$logs_table = $wpdb->prefix . 'bkgt_scraping_logs';

// Get data for overview
$player_count = $wpdb->get_var("SELECT COUNT(*) FROM $players_table WHERE status = 'active'");
$event_count = $wpdb->get_var("SELECT COUNT(*) FROM $events_table WHERE status = 'scheduled'");
$team_count = $wpdb->get_var("SELECT COUNT(*) FROM $teams_table");
$recent_logs = $wpdb->get_results("SELECT * FROM $logs_table ORDER BY start_time DESC LIMIT 5");
?>

<div class="bkgt-frontend-admin wrap">
    <div class="container">
        <h1><?php _e('BKGT Datahantering', 'bkgt-data-scraping'); ?> <span class="dashicons dashicons-groups" aria-hidden="true"></span></h1>

        <!-- Tab Navigation -->
        <nav class="bkgt-tabs-nav" role="tablist" aria-label="<?php _e('Huvudnavigering', 'bkgt-data-scraping'); ?>">
            <button class="bkgt-tab-button active" data-tab="overview" role="tab" aria-selected="true" aria-controls="bkgt-tab-overview" id="bkgt-tab-overview-btn">
                <span class="dashicons dashicons-dashboard" aria-hidden="true"></span>
                <?php _e('Översikt', 'bkgt-data-scraping'); ?>
            </button>
            <button class="bkgt-tab-button" data-tab="players" role="tab" aria-selected="false" aria-controls="bkgt-tab-players" id="bkgt-tab-players-btn">
                <span class="dashicons dashicons-groups" aria-hidden="true"></span>
                <?php _e('Spelare', 'bkgt-data-scraping'); ?>
            </button>
            <button class="bkgt-tab-button" data-tab="scraper" role="tab" aria-selected="false" aria-controls="bkgt-tab-scraper" id="bkgt-tab-scraper-btn">
                <span class="dashicons dashicons-update" aria-hidden="true"></span>
                <?php _e('Skrapning', 'bkgt-data-scraping'); ?>
            </button>
            <button class="bkgt-tab-button" data-tab="settings" role="tab" aria-selected="false" aria-controls="bkgt-tab-settings" id="bkgt-tab-settings-btn">
                <span class="dashicons dashicons-admin-settings" aria-hidden="true"></span>
                <?php _e('Inställningar', 'bkgt-data-scraping'); ?>
            </button>
        </nav>

        <!-- Tab Content -->
        <main class="bkgt-tabs-content" id="bkgt-main-content" role="main">
            <!-- Overview Tab -->
            <div id="bkgt-tab-overview" class="bkgt-tab-panel active" role="tabpanel" aria-labelledby="bkgt-tab-overview-btn" tabindex="0">
                <div class="bkgt-dashboard-grid">
                    <!-- Data Overview Cards -->
                    <div class="bkgt-dashboard-card bkgt-overview-card">
                        <h3><?php _e('Dataöversikt', 'bkgt-data-scraping'); ?> <span class="dashicons dashicons-chart-bar"></span></h3>
                        <div class="bkgt-stats-grid">
                            <div class="bkgt-stat-item">
                                <span class="bkgt-stat-number"><?php echo $player_count; ?></span>
                                <span class="bkgt-stat-label">Aktiva Spelare</span>
                            </div>
                            <div class="bkgt-stat-item">
                                <span class="bkgt-stat-number"><?php echo $event_count; ?></span>
                                <span class="bkgt-stat-label">Kommande Event</span>
                            </div>
                            <div class="bkgt-stat-item">
                                <span class="bkgt-stat-number"><?php echo $team_count; ?></span>
                                <span class="bkgt-stat-label">Lag</span>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Activity -->
                    <div class="bkgt-dashboard-card bkgt-activity-card">
                        <h3><?php _e('Senaste Aktivitet', 'bkgt-data-scraping'); ?> <span class="dashicons dashicons-clock"></span></h3>
                        <div class="bkgt-activity-list">
                            <?php
                            if (!empty($recent_logs)) {
                                foreach ($recent_logs as $log) {
                                    $status_class = $log->status === 'completed' ? 'success' : ($log->status === 'failed' ? 'error' : 'warning');
                                    echo '<div class="bkgt-activity-item ' . $status_class . '">';
                                    echo '<span class="bkgt-activity-time">' . date('H:i', strtotime($log->start_time)) . '</span>';
                                    echo '<span class="bkgt-activity-desc">' . ucfirst($log->operation_type) . ' - ' . $log->status . '</span>';
                                    echo '</div>';
                                }
                            } else {
                                echo '<p>Ingen aktivitet ännu.</p>';
                            }
                            ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Players Tab -->
            <div id="bkgt-tab-players" class="bkgt-tab-panel" role="tabpanel" aria-labelledby="bkgt-tab-players-btn" tabindex="0">
                <div class="bkgt-section-header">
                    <h2><?php _e('Spelarhantering', 'bkgt-data-scraping'); ?></h2>
                    <button class="button button-primary" id="bkgt-add-player-btn">
                        <span class="dashicons dashicons-plus" aria-hidden="true"></span>
                        <?php _e('Lägg till Spelare', 'bkgt-data-scraping'); ?>
                    </button>
                </div>

                <div class="bkgt-players-table-container">
                    <table class="wp-list-table widefat fixed striped bkgt-players-table">
                        <thead>
                            <tr>
                                <th><?php _e('Namn', 'bkgt-data-scraping'); ?></th>
                                <th><?php _e('Position', 'bkgt-data-scraping'); ?></th>
                                <th><?php _e('Tröjnummer', 'bkgt-data-scraping'); ?></th>
                                <th><?php _e('Lag', 'bkgt-data-scraping'); ?></th>
                                <th><?php _e('Status', 'bkgt-data-scraping'); ?></th>
                                <th><?php _e('Åtgärder', 'bkgt-data-scraping'); ?></th>
                            </tr>
                        </thead>
                        <tbody id="bkgt-players-tbody">
                            <?php
                            $players = $wpdb->get_results("
                                SELECT p.*, t.name as team_name
                                FROM $players_table p
                                LEFT JOIN $teams_table t ON p.team_id = t.id
                                ORDER BY p.last_name, p.first_name
                            ");

                            foreach ($players as $player) {
                                $status_class = $player->status === 'active' ? 'bkgt-status-active' : 'bkgt-status-inactive';
                                echo '<tr data-id="' . $player->id . '">';
                                echo '<td>' . esc_html($player->first_name . ' ' . $player->last_name) . '</td>';
                                echo '<td>' . esc_html($player->position) . '</td>';
                                echo '<td>' . esc_html($player->jersey_number ?: '-') . '</td>';
                                echo '<td>' . esc_html($player->team_name ?: 'Ej tilldelad') . '</td>';
                                echo '<td><span class="bkgt-status ' . $status_class . '">' . ucfirst($player->status) . '</span></td>';
                                echo '<td>';
                                echo '<button class="button button-small bkgt-edit-player" data-id="' . $player->id . '">' . __('Redigera', 'bkgt-data-scraping') . '</button> ';
                                echo '<button class="button button-small bkgt-delete-player" data-id="' . $player->id . '">' . __('Ta bort', 'bkgt-data-scraping') . '</button>';
                                echo '</td>';
                                echo '</tr>';
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Scraper Tab -->
            <div id="bkgt-tab-scraper" class="bkgt-tab-panel" role="tabpanel" aria-labelledby="bkgt-tab-scraper-btn" tabindex="0">
                <div class="bkgt-section-header">
                    <h2><?php _e('Dataskrapning', 'bkgt-data-scraping'); ?></h2>
                    <button class="button button-primary" id="bkgt-run-scraper-btn">
                        <span class="dashicons dashicons-update" aria-hidden="true"></span>
                        <?php _e('Kör Skrapning', 'bkgt-data-scraping'); ?>
                    </button>
                </div>

                <div class="bkgt-scraper-status">
                    <div class="bkgt-status-indicator" id="bkgt-scraper-status">
                        <span class="dashicons dashicons-clock" aria-hidden="true"></span>
                        <?php _e('Redo att köra', 'bkgt-data-scraping'); ?>
                    </div>
                    <div class="bkgt-progress-bar" id="bkgt-scraper-progress" style="display: none;">
                        <div class="bkgt-progress-fill" id="bkgt-progress-fill"></div>
                        <span class="bkgt-progress-text" id="bkgt-progress-text">0%</span>
                    </div>
                </div>

                <div class="bkgt-scraper-logs">
                    <h3><?php _e('Skrapningsloggar', 'bkgt-data-scraping'); ?></h3>
                    <div class="bkgt-logs-container" id="bkgt-scraper-logs">
                        <?php
                        $logs = $wpdb->get_results("SELECT * FROM $logs_table ORDER BY start_time DESC LIMIT 10");
                        if (!empty($logs)) {
                            foreach ($logs as $log) {
                                $status_class = $log->status === 'completed' ? 'success' : ($log->status === 'failed' ? 'error' : 'warning');
                                echo '<div class="bkgt-log-entry ' . $status_class . '">';
                                echo '<span class="bkgt-log-time">' . date('Y-m-d H:i:s', strtotime($log->start_time)) . '</span>';
                                echo '<span class="bkgt-log-operation">' . ucfirst($log->operation_type) . '</span>';
                                echo '<span class="bkgt-log-status">' . $log->status . '</span>';
                                if ($log->error_message) {
                                    echo '<span class="bkgt-log-error">' . esc_html($log->error_message) . '</span>';
                                }
                                echo '</div>';
                            }
                        } else {
                            echo '<p>Inga loggar ännu.</p>';
                        }
                        ?>
                    </div>
                </div>
            </div>

            <!-- Settings Tab -->
            <div id="bkgt-tab-settings" class="bkgt-tab-panel" role="tabpanel" aria-labelledby="bkgt-tab-settings-btn" tabindex="0">
                <h2><?php _e('Inställningar', 'bkgt-data-scraping'); ?></h2>
                <form id="bkgt-settings-form">
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php _e('Automatisk Skrapning', 'bkgt-data-scraping'); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" id="bkgt-auto-scrape-enabled" value="1">
                                    <?php _e('Aktivera automatisk skrapning', 'bkgt-data-scraping'); ?>
                                </label>
                                <p class="description"><?php _e('Skrapa data automatiskt enligt schema.', 'bkgt-data-scraping'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Skrapningsintervall', 'bkgt-data-scraping'); ?></th>
                            <td>
                                <select id="bkgt-scrape-interval">
                                    <option value="daily"><?php _e('Dagligen', 'bkgt-data-scraping'); ?></option>
                                    <option value="twicedaily"><?php _e('Två gånger om dagen', 'bkgt-data-scraping'); ?></option>
                                    <option value="weekly"><?php _e('Veckovis', 'bkgt-data-scraping'); ?></option>
                                </select>
                            </td>
                        </tr>
                    </table>
                    <p class="submit">
                        <button type="submit" class="button button-primary"><?php _e('Spara Inställningar', 'bkgt-data-scraping'); ?></button>
                    </p>
                </form>
            </div>
        </main>
    </div>
</div>

<!-- Modal dialogs for forms -->
<div id="bkgt-player-modal" class="bkgt-modal" style="display: none;">
    <div class="bkgt-modal-content">
        <div class="bkgt-modal-header">
            <h3 id="bkgt-modal-title"><?php _e('Lägg till Spelare', 'bkgt-data-scraping'); ?></h3>
            <button class="bkgt-modal-close">&times;</button>
        </div>
        <div class="bkgt-modal-body">
            <form id="bkgt-player-form">
                <input type="hidden" id="bkgt-player-id" value="">
                <p>
                    <label for="bkgt-player-first-name"><?php _e('Förnamn', 'bkgt-data-scraping'); ?></label>
                    <input type="text" id="bkgt-player-first-name" required>
                </p>
                <p>
                    <label for="bkgt-player-last-name"><?php _e('Efternamn', 'bkgt-data-scraping'); ?></label>
                    <input type="text" id="bkgt-player-last-name" required>
                </p>
                <p>
                    <label for="bkgt-player-position"><?php _e('Position', 'bkgt-data-scraping'); ?></label>
                    <input type="text" id="bkgt-player-position">
                </p>
                <p>
                    <label for="bkgt-player-jersey"><?php _e('Tröjnummer', 'bkgt-data-scraping'); ?></label>
                    <input type="number" id="bkgt-player-jersey">
                </p>
                <p>
                    <label for="bkgt-player-birth-date"><?php _e('Födelsedatum', 'bkgt-data-scraping'); ?></label>
                    <input type="date" id="bkgt-player-birth-date">
                </p>
                <p>
                    <label for="bkgt-player-team"><?php _e('Lag', 'bkgt-data-scraping'); ?></label>
                    <select id="bkgt-player-team">
                        <option value=""><?php _e('Välj lag', 'bkgt-data-scraping'); ?></option>
                        <?php
                        $teams = $wpdb->get_results("SELECT * FROM $teams_table ORDER BY name");
                        foreach ($teams as $team) {
                            echo '<option value="' . $team->id . '">' . esc_html($team->name) . '</option>';
                        }
                        ?>
                    </select>
                </p>
                <p>
                    <label for="bkgt-player-status"><?php _e('Status', 'bkgt-data-scraping'); ?></label>
                    <select id="bkgt-player-status">
                        <option value="active"><?php _e('Aktiv', 'bkgt-data-scraping'); ?></option>
                        <option value="inactive"><?php _e('Inaktiv', 'bkgt-data-scraping'); ?></option>
                    </select>
                </p>
            </form>
        </div>
        <div class="bkgt-modal-footer">
            <button class="button" id="bkgt-modal-cancel"><?php _e('Avbryt', 'bkgt-data-scraping'); ?></button>
            <button class="button button-primary" id="bkgt-modal-save"><?php _e('Spara', 'bkgt-data-scraping'); ?></button>
        </div>
    </div>
</div>

<?php get_footer(); ?>