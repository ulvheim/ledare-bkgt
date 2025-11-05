<?php
/**
 * Main template file - Dashboard
 *
 * @package BKGT_Ledare
 * @since 1.0.0
 */

get_header();

// Check if BKGT core is available
if (!class_exists('BKGT_Database')) {
    // BKGT Core not available - show basic dashboard
    ?>
    <div class="content-container">
        <div class="content-header">
            <h1><?php esc_html_e('Dashboard', 'bkgt-ledare'); ?></h1>
            <p class="text-muted"><?php esc_html_e('Välkommen till BKGTS Ledarsystem', 'bkgt-ledare'); ?></p>
        </div>

        <div class="grid grid-1">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><?php esc_html_e('System Status', 'bkgt-ledare'); ?></h3>
                </div>
                <div class="card-body">
                    <div class="alert alert-warning">
                        <p><?php esc_html_e('BKGT Core plugin är inte tillgängligt. Vänligen kontakta administratören.', 'bkgt-ledare'); ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php
    get_footer();
    return;
}

// Initialize database connection
global $wpdb;
$db = new BKGT_Database();

// Get dashboard data with error handling
$teams_count = 0;
$players_count = 0;
$events_count = 0;

try {
    $teams_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}bkgt_teams");
    $players_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}bkgt_players");
    $events_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}bkgt_events");
} catch (Exception $e) {
    // Database tables might not exist yet
    $teams_count = $players_count = $events_count = 0;
}

// Get recent scraping activity with error handling
$recent_logs = array();
try {
    // Note: get_scraping_logs method doesn't exist in current BKGT_Database class
    // This would need to be implemented in a future version
    $recent_logs = array();
} catch (Exception $e) {
    $recent_logs = array();
}

// Get upcoming events with error handling
$upcoming_events = array();
try {
    // Note: get_events method doesn't exist in current BKGT_Database class
    // This would need to be implemented in a future version
    $upcoming_events = array();
} catch (Exception $e) {
    $upcoming_events = array();
}

// Get user's teams
$user_teams = bkgt_get_user_teams();
$user_team_data = array();

// Check if user is admin - admins see all teams
if (current_user_can('manage_options')) {
    // Get all teams from database table
    $user_team_data = $wpdb->get_results("SELECT id, name, category, coach FROM {$wpdb->prefix}bkgt_teams ORDER BY name ASC");
} elseif (!empty($user_teams)) {
    // Non-admin users see only their assigned teams
    foreach ($user_teams as $team_id) {
        // Get team data from database table
        $team = $wpdb->get_row($wpdb->prepare(
            "SELECT id, name, category, coach FROM {$wpdb->prefix}bkgt_teams WHERE id = %d",
            $team_id
        ));
        if ($team) {
            $user_team_data[] = $team;
        }
    }
}
// If not admin and no assigned teams, $user_team_data remains empty

?>

<div class="content-container">
    <div class="content-header">
        <h1><?php esc_html_e('Dashboard', 'bkgt-ledare'); ?></h1>
        <p class="text-muted"><?php esc_html_e('Välkommen till BKGTS Ledarsystem', 'bkgt-ledare'); ?></p>
    </div>

    <div class="grid grid-3">
        <!-- Quick Stats Card -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><?php esc_html_e('Snabbstatistik', 'bkgt-ledare'); ?></h3>
            </div>
            <div class="card-body">
                <div class="stats-grid">
                    <div class="stat-item">
                        <span class="stat-number"><?php echo intval($teams_count); ?></span>
                        <span class="stat-label"><?php esc_html_e('Lag', 'bkgt-ledare'); ?></span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number"><?php echo intval($players_count); ?></span>
                        <span class="stat-label"><?php esc_html_e('Spelare', 'bkgt-ledare'); ?></span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number"><?php echo intval($events_count); ?></span>
                        <span class="stat-label"><?php esc_html_e('Matcher', 'bkgt-ledare'); ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Activity Card -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><?php esc_html_e('Senaste aktivitet', 'bkgt-ledare'); ?></h3>
            </div>
            <div class="card-body">
                <?php if (!empty($recent_logs)): ?>
                    <div class="activity-timeline">
                        <?php foreach ($recent_logs as $log): ?>
                            <div class="activity-item">
                                <div class="activity-icon">
                                    <?php if ($log['status'] === 'completed'): ?>
                                        <span class="dashicons dashicons-yes" style="color: var(--bkgt-accent);"></span>
                                    <?php elseif ($log['status'] === 'failed'): ?>
                                        <span class="dashicons dashicons-no" style="color: var(--bkgt-secondary);"></span>
                                    <?php else: ?>
                                        <span class="dashicons dashicons-clock" style="color: var(--bkgt-primary);"></span>
                                    <?php endif; ?>
                                </div>
                                <div class="activity-content">
                                    <div class="activity-title">
                                        <?php
                                        $action_text = '';
                                        switch ($log['scrape_type']) {
                                            case 'teams': $action_text = __('Lag hämtade', 'bkgt-ledare'); break;
                                            case 'players': $action_text = __('Spelare hämtade', 'bkgt-ledare'); break;
                                            case 'events': $action_text = __('Matcher hämtade', 'bkgt-ledare'); break;
                                            case 'all': $action_text = __('All data hämtad', 'bkgt-ledare'); break;
                                            default: $action_text = __('Data hämtad', 'bkgt-ledare');
                                        }
                                        echo esc_html($action_text);
                                        ?>
                                    </div>
                                    <div class="activity-meta">
                                        <?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($log['started_at']))); ?>
                                        <?php if ($log['records_added'] > 0): ?>
                                            <span class="activity-count">(+<?php echo intval($log['records_added']); ?>)</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="text-muted"><?php esc_html_e('Ingen aktivitet att visa ännu', 'bkgt-ledare'); ?></p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Notifications Card -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><?php esc_html_e('Kommande träningar', 'bkgt-ledare'); ?></h3>
            </div>
            <div class="card-body">
                <?php if (!empty($upcoming_events)): ?>
                    <div class="upcoming-events">
                        <?php foreach ($upcoming_events as $event): ?>
                            <div class="event-item">
                                <div class="event-date">
                                    <?php echo esc_html(date_i18n('M j', strtotime($event['event_date']))); ?>
                                </div>
                                <div class="event-info">
                                    <div class="event-title"><?php echo esc_html($event['title']); ?></div>
                                    <div class="event-meta">
                                        <?php if (!empty($event['location'])): ?>
                                            <span class="event-location"><?php echo esc_html($event['location']); ?></span>
                                        <?php endif; ?>
                                        <?php if (!empty($event['opponent'])): ?>
                                            <span class="event-opponent">vs <?php echo esc_html($event['opponent']); ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="text-muted"><?php esc_html_e('Inga kommande träningar', 'bkgt-ledare'); ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php if (bkgt_can_view_performance_data()): ?>
    <!-- Performance Data Section (Only for authorized users) -->
    <div class="card mt-3">
        <div class="card-header">
            <h3 class="card-title"><?php esc_html_e('Utvärdering', 'bkgt-ledare'); ?></h3>
        </div>
        <div class="card-body">
            <p class="text-muted"><?php esc_html_e('Prestandadata och utvärderingsverktyg kommer snart...', 'bkgt-ledare'); ?></p>
        </div>
    </div>
    <?php endif; ?>

    <!-- My Teams Section -->
    <div class="card mt-3">
        <div class="card-header">
            <h3 class="card-title">
                <?php echo current_user_can('manage_options') ? esc_html__('Alla Lag', 'bkgt-ledare') : esc_html__('Mina Lag', 'bkgt-ledare'); ?>
            </h3>
        </div>
        <div class="card-body">
            <?php if (!empty($user_team_data)): ?>
                <div class="teams-grid">
                    <?php foreach ($user_team_data as $team): ?>
                        <div class="team-card">
                            <h4><?php echo esc_html($team->name); ?></h4>
                            <div class="team-meta">
                                <span class="team-category"><?php echo esc_html(ucfirst($team->category)); ?></span>
                                <?php if (!empty($team->coach)): ?>
                                    <span class="team-coach"><?php esc_html_e('Tränare:', 'bkgt-ledare'); ?> <?php echo esc_html($team->coach); ?></span>
                                <?php endif; ?>
                            </div>
                            <a href="<?php echo esc_url(get_permalink(51) . '?team=' . $team->id); ?>" class="btn btn-outline btn-sm">
                                <?php esc_html_e('Visa lag', 'bkgt-ledare'); ?>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="text-muted">
                    <?php echo current_user_can('manage_options')
                        ? esc_html__('Inga lag tillgängliga.', 'bkgt-ledare')
                        : esc_html__('Du är inte tilldelad några lag ännu. Kontakta en administratör för att få åtkomst till dina lag.', 'bkgt-ledare'); ?>
                </p>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php get_footer(); ?>
