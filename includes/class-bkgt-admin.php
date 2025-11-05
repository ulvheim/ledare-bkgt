<?php
/**
 * Admin interface class for BKGT Data Scraping plugin
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * BKGT Admin Class
 */
class BKGT_Admin {

    /**
     * Database instance
     */
    private $db;

    /**
     * Constructor
     */
    public function __construct($db) {
        $this->db = $db;
        $this->init_hooks();
    }

    /**
     * Initialize hooks
     */
    private function init_hooks() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_bkgt_save_player', array($this, 'save_player'));
        add_action('wp_ajax_bkgt_delete_player', array($this, 'delete_player'));
        add_action('wp_ajax_bkgt_save_event', array($this, 'save_event'));
        add_action('wp_ajax_bkgt_delete_event', array($this, 'delete_event'));
        add_action('wp_ajax_bkgt_get_player_stats', array($this, 'get_player_stats'));
        add_action('wp_ajax_bkgt_save_statistics', array($this, 'save_statistics'));
        add_action('wp_ajax_bkgt_get_players_for_assignment', array($this, 'get_players_for_assignment'));
        add_action('wp_ajax_bkgt_update_player_assignment', array($this, 'update_player_assignment'));
        add_action('wp_ajax_bkgt_save_player_assignments', array($this, 'save_player_assignments'));
        add_action('wp_ajax_bkgt_export_players', array($this, 'export_players'));
        add_action('wp_ajax_bkgt_export_events', array($this, 'export_events'));
        add_action('wp_ajax_bkgt_import_players', array($this, 'import_players'));
        add_action('wp_ajax_bkgt_import_events', array($this, 'import_events'));
        add_action('wp_ajax_bkgt_check_player_duplicate', array($this, 'check_player_duplicate'));
        add_action('wp_ajax_bkgt_check_jersey_duplicate', array($this, 'check_jersey_duplicate'));
        add_action('wp_ajax_bkgt_check_event_duplicate', array($this, 'check_event_duplicate'));
        add_action('wp_ajax_bkgt_save_inline_edit', array($this, 'save_inline_edit'));
        add_action('wp_ajax_bkgt_get_bulk_players', array($this, 'get_bulk_players'));
        add_action('wp_ajax_bkgt_get_bulk_events', array($this, 'get_bulk_events'));
        add_action('wp_ajax_bkgt_perform_bulk_assignment', array($this, 'perform_bulk_assignment'));
        add_action('wp_ajax_bkgt_run_scraper', array($this, 'run_scraper'));
        add_action('wp_ajax_bkgt_get_scraper_status', array($this, 'get_scraper_status'));
        add_action('wp_ajax_bkgt_save_schedule', array($this, 'save_schedule'));
        add_action('bkgt_auto_scrape', array($this, 'run_auto_scrape'));
    }

    /**
     * Test authentication credentials
     */
    public function test_auth() {
        if (!wp_verify_nonce($_POST['nonce'], 'bkgt_admin_nonce') ||
            !current_user_can('manage_options')) {
            wp_die(__('Security check failed', 'bkgt-data-scraping'));
        }

        try {
            // Create scraper instance and test login
            $scraper = new BKGT_Scraper($this->db);
            // Use reflection to access private method for testing
            $reflection = new ReflectionClass($scraper);
            $method = $reflection->getMethod('login_to_svenskalag');
            $method->setAccessible(true);
            $method->invoke($scraper);

            wp_send_json_success(array(
                'message' => __('Authentication successful! Credentials are working correctly.', 'bkgt-data-scraping')
            ));

        } catch (Exception $e) {
            wp_send_json_error(array(
                'message' => __('Authentication failed: ', 'bkgt-data-scraping') . $e->getMessage()
            ));
        }
    }

    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_submenu_page(
            'tools.php',
            __('BKGT Datahantering', 'bkgt-data-scraping'),
            __('BKGT Data', 'bkgt-data-scraping'),
            'manage_options',
            'bkgt-data-management',
            array($this, 'admin_page')
        );

        add_submenu_page(
            'tools.php',
            __('Skapa BKGT Sidor', 'bkgt-data-scraping'),
            __('BKGT Skapa Sidor', 'bkgt-data-scraping'),
            'manage_options',
            'bkgt-create-pages',
            array($this, 'create_pages_page')
        );
    }

    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_scripts($hook) {
        if (strpos($hook, 'bkgt') === false) {
            return;
        }

        wp_enqueue_script('jquery');
        wp_enqueue_script('jquery-ui-dialog');
        wp_enqueue_script('jquery-ui-draggable');
        wp_enqueue_script('jquery-ui-droppable');
        wp_enqueue_style('wp-jquery-ui-dialog');

        wp_enqueue_script(
            'bkgt-admin-js',
            BKGT_DATA_SCRAPING_PLUGIN_URL . 'admin/js/admin.js',
            array('jquery', 'jquery-ui-dialog', 'jquery-ui-draggable', 'jquery-ui-droppable'),
            BKGT_DATA_SCRAPING_VERSION,
            true
        );

        wp_enqueue_style(
            'bkgt-admin-css',
            BKGT_DATA_SCRAPING_PLUGIN_URL . 'admin/css/admin.css',
            array(),
            BKGT_DATA_SCRAPING_VERSION
        );

        wp_localize_script('bkgt-admin-js', 'bkgt_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('bkgt_admin_nonce'),
            'scraper_nonce' => wp_create_nonce('bkgt_scraper_nonce'),
            'strings' => array(
                'confirm_delete' => __('Are you sure you want to delete this item?', 'bkgt-data-scraping'),
                'saving' => __('Saving...', 'bkgt-data-scraping'),
                'saved' => __('Saved successfully!', 'bkgt-data-scraping'),
                'error' => __('An error occurred', 'bkgt-data-scraping'),
                'preparing_scrape' => __('Förbereder skrapning...', 'bkgt-data-scraping'),
                'scrape_completed' => __('Skrapning slutförd!', 'bkgt-data-scraping'),
                'scrape_success' => __('Skrapning slutförd framgångsrikt!', 'bkgt-data-scraping'),
                'schedule_saved' => __('Schemaläggning sparad!', 'bkgt-data-scraping')
            )
        ));
    }

    /**
     * Main admin page
     */
    public function admin_page() {
        include BKGT_DATA_SCRAPING_PLUGIN_DIR . 'templates/admin-dashboard.php';
    }

    /**
     * Create pages page
     */
    public function create_pages_page() {
        if (isset($_POST['create_pages'])) {
            $this->create_bkgt_pages();
            echo '<div class="notice notice-success"><p>' . __('BKGT pages created successfully!', 'bkgt-data-scraping') . '</p></div>';
        }

        ?>
        <div class="wrap">
            <h1><?php _e('Skapa BKGT Sidor', 'bkgt-data-scraping'); ?></h1>
            <p><?php _e('Klicka på knappen nedan för att skapa de nödvändiga BKGT-sidorna för allmän åtkomst.', 'bkgt-data-scraping'); ?></p>

            <form method="post">
                <?php wp_nonce_field('bkgt_create_pages_nonce'); ?>
                <p><?php _e('Följande sidor kommer att skapas:', 'bkgt-data-scraping'); ?></p>
                <ul style="list-style: disc; margin-left: 20px;">
                    <li><?php _e('Dokument - För klubbens dokument och filer', 'bkgt-data-scraping'); ?></li>
                    <li><?php _e('Kommunikation - För intern kommunikation och meddelanden', 'bkgt-data-scraping'); ?></li>
                    <li><?php _e('Utrustning - För hantering av klubbens utrustning', 'bkgt-data-scraping'); ?></li>
                </ul>
                <p><input type="submit" name="create_pages" class="button button-primary" value="<?php _e('Skapa Sidor', 'bkgt-data-scraping'); ?>"></p>
            </form>
        </div>
        <?php
    }

    /**
     * Create BKGT pages
     */
    private function create_bkgt_pages() {
        if (!wp_verify_nonce($_POST['_wpnonce'], 'bkgt_create_pages_nonce') ||
            !current_user_can('manage_options')) {
            return;
        }

        $pages = array(
            array(
                'title' => 'Dokument',
                'slug' => 'dokument',
                'content' => 'Här hittar du klubbens dokument och viktiga filer.'
            ),
            array(
                'title' => 'Kommunikation',
                'slug' => 'kommunikation',
                'content' => 'Intern kommunikation och meddelanden.'
            ),
            array(
                'title' => 'Utrustning',
                'slug' => 'utrustning',
                'content' => 'Hantering av klubbens utrustning och inventarier.'
            )
        );

        foreach ($pages as $page_data) {
            // Check if page already exists
            $existing_page = get_page_by_path($page_data['slug']);

            if (!$existing_page) {
                // Create new page
                wp_insert_post(array(
                    'post_title' => $page_data['title'],
                    'post_name' => $page_data['slug'],
                    'post_content' => $page_data['content'],
                    'post_status' => 'publish',
                    'post_type' => 'page'
                ));
            }
        }
    }

    /**
     * Players management page
     */
    public function players_page() {
        $players = $this->db->get_players('all');
        include BKGT_DATA_SCRAPING_PLUGIN_DIR . 'templates/admin-players.php';
    }

    /**
     * Events management page
     */
    public function events_page() {
        $events = $this->db->get_events('all');
        include BKGT_DATA_SCRAPING_PLUGIN_DIR . 'templates/admin-events.php';
    }

    /**
     * Statistics management page
     */
    public function statistics_page() {
        $players = $this->db->get_players('active');
        $events = $this->db->get_events('completed');
        include BKGT_DATA_SCRAPING_PLUGIN_DIR . 'templates/admin-statistics.php';
    }

    /**
     * Settings page
     */
    public function settings_page() {
        if (isset($_POST['submit'])) {
            $this->save_settings();
        }
        include BKGT_DATA_SCRAPING_PLUGIN_DIR . 'templates/admin-settings.php';
    }

    /**
     * Save settings
     */
    private function save_settings() {
        if (!wp_verify_nonce($_POST['_wpnonce'], 'bkgt_settings_nonce')) {
            return;
        }

        update_option('bkgt_scraping_enabled', isset($_POST['bkgt_scraping_enabled']) ? 'yes' : 'no');
        update_option('bkgt_scraping_interval', sanitize_text_field($_POST['bkgt_scraping_interval']));
        update_option('bkgt_scraping_source_url', esc_url_raw($_POST['bkgt_scraping_source_url']));

        // Save authentication credentials securely
        if (!empty($_POST['bkgt_scraping_username'])) {
            $encrypted_username = $this->encrypt_credential(sanitize_text_field($_POST['bkgt_scraping_username']));
            update_option('bkgt_scraping_username', $encrypted_username);
        }

        if (!empty($_POST['bkgt_scraping_password'])) {
            $encrypted_password = $this->encrypt_credential($_POST['bkgt_scraping_password']);
            update_option('bkgt_scraping_password', $encrypted_password);
        }

        echo '<div class="notice notice-success"><p>' . __('Settings saved successfully!', 'bkgt-data-scraping') . '</p></div>';
    }

    /**
     * Encrypt credential using WordPress salts
     */
    private function encrypt_credential($credential) {
        if (empty($credential)) {
            return '';
        }

        $key = wp_salt('auth') . wp_salt('secure_auth');
        $encrypted = openssl_encrypt($credential, 'AES-256-CBC', $key, 0, substr($key, 0, 16));
        return base64_encode($encrypted);
    }

    /**
     * Decrypt credential using WordPress salts
     */
    public function decrypt_credential($encrypted_credential) {
        if (empty($encrypted_credential)) {
            return '';
        }

        $key = wp_salt('auth') . wp_salt('secure_auth');
        $decrypted = openssl_decrypt(base64_decode($encrypted_credential), 'AES-256-CBC', $key, 0, substr($key, 0, 16));
        return $decrypted;
    }

    /**
     * AJAX: Save player
     */
    public function save_player() {
        if (!wp_verify_nonce($_POST['nonce'], 'bkgt_admin_nonce') ||
            !current_user_can('manage_options')) {
            wp_die(__('Security check failed', 'bkgt-data-scraping'));
        }

        $player_data = array(
            'player_id' => sanitize_text_field($_POST['player_id']),
            'first_name' => sanitize_text_field($_POST['first_name']),
            'last_name' => sanitize_text_field($_POST['last_name']),
            'position' => sanitize_text_field($_POST['position']),
            'birth_date' => !empty($_POST['birth_date']) ? sanitize_text_field($_POST['birth_date']) : null,
            'jersey_number' => !empty($_POST['jersey_number']) ? (int)$_POST['jersey_number'] : null,
            'status' => sanitize_text_field($_POST['status'])
        );

        $result = $this->db->insert_player($player_data);

        if ($result) {
            wp_send_json_success(array(
                'message' => __('Player saved successfully', 'bkgt-data-scraping'),
                'player_id' => $result
            ));
        } else {
            wp_send_json_error(array(
                'message' => __('Failed to save player', 'bkgt-data-scraping')
            ));
        }
    }

    /**
     * AJAX: Delete player
     */
    public function delete_player() {
        if (!wp_verify_nonce($_POST['nonce'], 'bkgt_admin_nonce') ||
            !current_user_can('manage_options')) {
            wp_die(__('Security check failed', 'bkgt-data-scraping'));
        }

        global $wpdb;
        $player_id = (int)$_POST['player_id'];

        $result = $wpdb->delete(
            $this->db->get_table('players'),
            array('id' => $player_id),
            array('%d')
        );

        if ($result) {
            wp_send_json_success(array(
                'message' => __('Player deleted successfully', 'bkgt-data-scraping')
            ));
        } else {
            wp_send_json_error(array(
                'message' => __('Failed to delete player', 'bkgt-data-scraping')
            ));
        }
    }

    /**
     * AJAX: Save event
     */
    public function save_event() {
        if (!wp_verify_nonce($_POST['nonce'], 'bkgt_admin_nonce') ||
            !current_user_can('manage_options')) {
            wp_die(__('Security check failed', 'bkgt-data-scraping'));
        }

        $event_data = array(
            'event_id' => sanitize_text_field($_POST['event_id']),
            'title' => sanitize_text_field($_POST['title']),
            'event_type' => sanitize_text_field($_POST['event_type']),
            'event_date' => sanitize_text_field($_POST['event_date']),
            'location' => sanitize_text_field($_POST['location']),
            'opponent' => sanitize_text_field($_POST['opponent']),
            'home_away' => sanitize_text_field($_POST['home_away']),
            'result' => sanitize_text_field($_POST['result']),
            'status' => sanitize_text_field($_POST['status'])
        );

        $result = $this->db->insert_event($event_data);

        if ($result) {
            wp_send_json_success(array(
                'message' => __('Event saved successfully', 'bkgt-data-scraping'),
                'event_id' => $result
            ));
        } else {
            wp_send_json_error(array(
                'message' => __('Failed to save event', 'bkgt-data-scraping')
            ));
        }
    }

    /**
     * AJAX: Delete event
     */
    public function delete_event() {
        if (!wp_verify_nonce($_POST['nonce'], 'bkgt_admin_nonce') ||
            !current_user_can('manage_options')) {
            wp_die(__('Security check failed', 'bkgt-data-scraping'));
        }

        global $wpdb;
        $event_id = (int)$_POST['event_id'];

        $result = $wpdb->delete(
            $this->db->get_table('events'),
            array('id' => $event_id),
            array('%d')
        );

        if ($result) {
            wp_send_json_success(array(
                'message' => __('Event deleted successfully', 'bkgt-data-scraping')
            ));
        } else {
            wp_send_json_error(array(
                'message' => __('Failed to delete event', 'bkgt-data-scraping')
            ));
        }
    }

    /**
     * AJAX: Get player statistics
     */
    public function get_player_stats() {
        if (!wp_verify_nonce($_POST['nonce'], 'bkgt_admin_nonce') ||
            !current_user_can('manage_options')) {
            wp_die(__('Security check failed', 'bkgt-data-scraping'));
        }

        $player_id = (int)$_POST['player_id'];
        $stats = $this->db->get_player_statistics($player_id);

        wp_send_json_success(array(
            'statistics' => $stats
        ));
    }

    /**
     * AJAX: Save statistics
     */
    public function save_statistics() {
        if (!wp_verify_nonce($_POST['nonce'], 'bkgt_admin_nonce') ||
            !current_user_can('manage_options')) {
            wp_die(__('Security check failed', 'bkgt-data-scraping'));
        }

        $stats_data = array(
            'player_id' => (int)$_POST['player_id'],
            'event_id' => (int)$_POST['event_id'],
            'goals' => (int)$_POST['goals'],
            'assists' => (int)$_POST['assists'],
            'minutes_played' => (int)$_POST['minutes_played'],
            'yellow_cards' => (int)$_POST['yellow_cards'],
            'red_cards' => (int)$_POST['red_cards']
        );

        $result = $this->db->insert_statistics($stats_data);

        if ($result) {
            wp_send_json_success(array(
                'message' => __('Statistics saved successfully', 'bkgt-data-scraping')
            ));
        } else {
            wp_send_json_error(array(
                'message' => __('Failed to save statistics', 'bkgt-data-scraping')
            ));
        }
    }

    /**
     * AJAX: Get players for assignment modal
     */
    public function get_players_for_assignment() {
        if (!wp_verify_nonce($_POST['nonce'], 'bkgt_admin_nonce') ||
            !current_user_can('manage_options')) {
            wp_die(__('Security check failed', 'bkgt-data-scraping'));
        }

        $event_id = (int)$_POST['event_id'];

        // Get all active players
        $all_players = $this->db->get_players(array('status' => 'active'));

        // Get players already assigned to this event
        $assigned_players = $this->db->get_event_players($event_id);

        $assigned_ids = array_column($assigned_players, 'id');

        // Separate available and assigned players
        $available = array_filter($all_players, function($player) use ($assigned_ids) {
            return !in_array($player->id, $assigned_ids);
        });

        $assigned = array_filter($all_players, function($player) use ($assigned_ids) {
            return in_array($player->id, $assigned_ids);
        });

        wp_send_json_success(array(
            'available' => array_values($available),
            'assigned' => array_values($assigned)
        ));
    }

    /**
     * AJAX: Update player assignment (drag and drop)
     */
    public function update_player_assignment() {
        if (!wp_verify_nonce($_POST['nonce'], 'bkgt_admin_nonce') ||
            !current_user_can('manage_options')) {
            wp_die(__('Security check failed', 'bkgt-data-scraping'));
        }

        $player_id = (int)$_POST['player_id'];
        $event_id = (int)$_POST['event_id'];
        $assigned = (bool)$_POST['assigned'];

        if ($assigned) {
            $result = $this->db->assign_player_to_event($player_id, $event_id);
        } else {
            $result = $this->db->remove_player_from_event($player_id, $event_id);
        }

        if ($result) {
            wp_send_json_success();
        } else {
            wp_send_json_error(array(
                'message' => __('Failed to update player assignment', 'bkgt-data-scraping')
            ));
        }
    }

    /**
     * AJAX: Save all player assignments for an event
     */
    public function save_player_assignments() {
        if (!wp_verify_nonce($_POST['nonce'], 'bkgt_admin_nonce') ||
            !current_user_can('manage_options')) {
            wp_die(__('Security check failed', 'bkgt-data-scraping'));
        }

        $event_id = (int)$_POST['event_id'];
        $player_ids = isset($_POST['player_ids']) ? array_map('intval', $_POST['player_ids']) : array();

        // Remove all existing assignments for this event
        $this->db->remove_all_players_from_event($event_id);

        // Add new assignments
        $success_count = 0;
        foreach ($player_ids as $player_id) {
            if ($this->db->assign_player_to_event($player_id, $event_id)) {
                $success_count++;
            }
        }

        wp_send_json_success(array(
            'message' => sprintf(__('Assigned %d players to event', 'bkgt-data-scraping'), $success_count)
        ));
    }

    /**
     * AJAX: Export players to CSV
     */
    public function export_players() {
        if (!wp_verify_nonce($_POST['nonce'], 'bkgt_admin_nonce') ||
            !current_user_can('manage_options')) {
            wp_die(__('Security check failed', 'bkgt-data-scraping'));
        }

        $players = $this->db->get_players('all');

        if (empty($players)) {
            wp_send_json_error(array('message' => __('No players found to export', 'bkgt-data-scraping')));
            return;
        }

        // Create CSV header
        $csv = "förnamn,efternamn,position,tröjnummer,födelsedatum,status\n";

        // Add player data
        foreach ($players as $player) {
            $csv .= sprintf(
                "%s,%s,%s,%s,%s,%s\n",
                $this->escape_csv($player['first_name']),
                $this->escape_csv($player['last_name']),
                $this->escape_csv($player['position']),
                $player['jersey_number'],
                $player['birth_date'] ?: '',
                $player['status']
            );
        }

        wp_send_json_success(array('csv' => $csv));
    }

    /**
     * AJAX: Export events to CSV
     */
    public function export_events() {
        if (!wp_verify_nonce($_POST['nonce'], 'bkgt_admin_nonce') ||
            !current_user_can('manage_options')) {
            wp_die(__('Security check failed', 'bkgt-data-scraping'));
        }

        $events = $this->db->get_events('all');

        if (empty($events)) {
            wp_send_json_error(array('message' => __('No events found to export', 'bkgt-data-scraping')));
            return;
        }

        // Create CSV header
        $csv = "titel,typ,datum_tid,plats,beskrivning\n";

        // Add event data
        foreach ($events as $event) {
            $csv .= sprintf(
                "%s,%s,%s,%s,%s\n",
                $this->escape_csv($event['title']),
                $event['event_type'],
                $event['event_date'],
                $this->escape_csv($event['location'] ?: ''),
                $this->escape_csv($event['description'] ?: '')
            );
        }

        wp_send_json_success(array('csv' => $csv));
    }

    /**
     * AJAX: Import players from CSV
     */
    public function import_players() {
        if (!wp_verify_nonce($_POST['nonce'], 'bkgt_admin_nonce') ||
            !current_user_can('manage_options')) {
            wp_die(__('Security check failed', 'bkgt-data-scraping'));
        }

        if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
            wp_send_json_error(array('message' => __('No file uploaded or upload error', 'bkgt-data-scraping')));
            return;
        }

        $file = $_FILES['csv_file']['tmp_name'];
        $skip_duplicates = isset($_POST['skip_duplicates']) && $_POST['skip_duplicates'] === 'on';

        $handle = fopen($file, 'r');
        if (!$handle) {
            wp_send_json_error(array('message' => __('Could not open file', 'bkgt-data-scraping')));
            return;
        }

        $imported = 0;
        $skipped = 0;
        $errors = 0;
        $header = fgetcsv($handle, 1000, ','); // Skip header

        while (($data = fgetcsv($handle, 1000, ',')) !== false) {
            if (count($data) < 6) {
                $errors++;
                continue;
            }

            $player_data = array(
                'first_name' => sanitize_text_field($data[0]),
                'last_name' => sanitize_text_field($data[1]),
                'position' => sanitize_text_field($data[2]),
                'jersey_number' => (int)$data[3],
                'birth_date' => !empty($data[4]) ? sanitize_text_field($data[4]) : null,
                'status' => sanitize_text_field($data[5])
            );

            // Validate required fields
            if (empty($player_data['first_name']) || empty($player_data['last_name']) ||
                empty($player_data['position']) || empty($player_data['jersey_number'])) {
                $errors++;
                continue;
            }

            // Check for duplicates if requested
            if ($skip_duplicates) {
                $existing = $this->db->get_players('all');
                $duplicate = false;
                foreach ($existing as $existing_player) {
                    if (strtolower($existing_player['first_name']) === strtolower($player_data['first_name']) &&
                        strtolower($existing_player['last_name']) === strtolower($player_data['last_name'])) {
                        $duplicate = true;
                        break;
                    }
                }
                if ($duplicate) {
                    $skipped++;
                    continue;
                }
            }

            $result = $this->db->insert_player($player_data);
            if ($result) {
                $imported++;
            } else {
                $errors++;
            }
        }

        fclose($handle);

        wp_send_json_success(array(
            'imported' => $imported,
            'skipped' => $skipped,
            'errors' => $errors,
            'message' => sprintf(__('Import completed: %d imported, %d skipped, %d errors', 'bkgt-data-scraping'), $imported, $skipped, $errors)
        ));
    }

    /**
     * AJAX: Import events from CSV
     */
    public function import_events() {
        if (!wp_verify_nonce($_POST['nonce'], 'bkgt_admin_nonce') ||
            !current_user_can('manage_options')) {
            wp_die(__('Security check failed', 'bkgt-data-scraping'));
        }

        if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
            wp_send_json_error(array('message' => __('No file uploaded or upload error', 'bkgt-data-scraping')));
            return;
        }

        $file = $_FILES['csv_file']['tmp_name'];
        $skip_duplicates = isset($_POST['skip_duplicates']) && $_POST['skip_duplicates'] === 'on';

        $handle = fopen($file, 'r');
        if (!$handle) {
            wp_send_json_error(array('message' => __('Could not open file', 'bkgt-data-scraping')));
            return;
        }

        $imported = 0;
        $skipped = 0;
        $errors = 0;
        $header = fgetcsv($handle, 1000, ','); // Skip header

        while (($data = fgetcsv($handle, 1000, ',')) !== false) {
            if (count($data) < 5) {
                $errors++;
                continue;
            }

            $event_data = array(
                'title' => sanitize_text_field($data[0]),
                'event_type' => sanitize_text_field($data[1]),
                'event_date' => sanitize_text_field($data[2]),
                'location' => !empty($data[3]) ? sanitize_text_field($data[3]) : null,
                'description' => !empty($data[4]) ? sanitize_text_field($data[4]) : null
            );

            // Validate required fields
            if (empty($event_data['title']) || empty($event_data['event_type']) || empty($event_data['event_date'])) {
                $errors++;
                continue;
            }

            // Validate event type
            if (!in_array($event_data['event_type'], array('match', 'training', 'meeting'))) {
                $errors++;
                continue;
            }

            // Check for duplicates if requested
            if ($skip_duplicates) {
                $existing = $this->db->get_events('all');
                $duplicate = false;
                foreach ($existing as $existing_event) {
                    if (strtolower($existing_event['title']) === strtolower($event_data['title']) &&
                        $existing_event['event_date'] === $event_data['event_date']) {
                        $duplicate = true;
                        break;
                    }
                }
                if ($duplicate) {
                    $skipped++;
                    continue;
                }
            }

            $result = $this->db->insert_event($event_data);
            if ($result) {
                $imported++;
            } else {
                $errors++;
            }
        }

        fclose($handle);

        wp_send_json_success(array(
            'imported' => $imported,
            'skipped' => $skipped,
            'errors' => $errors,
            'message' => sprintf(__('Import completed: %d imported, %d skipped, %d errors', 'bkgt-data-scraping'), $imported, $skipped, $errors)
        ));
    }

    /**
     * Helper: Escape CSV values
     */
    private function escape_csv($value) {
        // If value contains comma, quote, or newline, wrap in quotes and escape quotes
        if (strpos($value, ',') !== false || strpos($value, '"') !== false || strpos($value, "\n") !== false) {
            return '"' . str_replace('"', '""', $value) . '"';
        }
        return $value;
    }

    /**
     * AJAX: Check for duplicate players
     */
    public function check_player_duplicate() {
        if (!wp_verify_nonce($_POST['nonce'], 'bkgt_admin_nonce') ||
            !current_user_can('manage_options')) {
            wp_die(__('Security check failed', 'bkgt-data-scraping'));
        }

        $first_name = sanitize_text_field($_POST['first_name']);
        $last_name = sanitize_text_field($_POST['last_name']);
        $exclude_id = !empty($_POST['exclude_id']) ? (int)$_POST['exclude_id'] : 0;

        global $wpdb;
        $query = $wpdb->prepare(
            "SELECT * FROM {$this->db->tables['players']}
             WHERE LOWER(first_name) = LOWER(%s) AND LOWER(last_name) = LOWER(%s)",
            $first_name, $last_name
        );

        if ($exclude_id > 0) {
            $query .= $wpdb->prepare(" AND id != %d", $exclude_id);
        }

        $existing_player = $wpdb->get_row($query, ARRAY_A);

        wp_send_json_success(array(
            'is_duplicate' => !empty($existing_player),
            'existing_player' => $existing_player
        ));
    }

    /**
     * AJAX: Check for duplicate jersey numbers
     */
    public function check_jersey_duplicate() {
        if (!wp_verify_nonce($_POST['nonce'], 'bkgt_admin_nonce') ||
            !current_user_can('manage_options')) {
            wp_die(__('Security check failed', 'bkgt-data-scraping'));
        }

        $jersey_number = (int)$_POST['jersey_number'];
        $exclude_id = !empty($_POST['exclude_id']) ? (int)$_POST['exclude_id'] : 0;

        global $wpdb;
        $query = $wpdb->prepare(
            "SELECT * FROM {$this->db->tables['players']}
             WHERE jersey_number = %d AND status = 'active'",
            $jersey_number
        );

        if ($exclude_id > 0) {
            $query .= $wpdb->prepare(" AND id != %d", $exclude_id);
        }

        $existing_player = $wpdb->get_row($query, ARRAY_A);

        wp_send_json_success(array(
            'is_duplicate' => !empty($existing_player),
            'existing_player' => $existing_player
        ));
    }

    /**
     * AJAX: Check for duplicate events
     */
    public function check_event_duplicate() {
        if (!wp_verify_nonce($_POST['nonce'], 'bkgt_admin_nonce') ||
            !current_user_can('manage_options')) {
            wp_die(__('Security check failed', 'bkgt-data-scraping'));
        }

        $title = sanitize_text_field($_POST['title']);
        $event_date = sanitize_text_field($_POST['event_date']);
        $exclude_id = !empty($_POST['exclude_id']) ? (int)$_POST['exclude_id'] : 0;

        global $wpdb;
        $query = $wpdb->prepare(
            "SELECT * FROM {$this->db->tables['events']}
             WHERE LOWER(title) = LOWER(%s) AND DATE(event_date) = DATE(%s)",
            $title, $event_date
        );

        if ($exclude_id > 0) {
            $query .= $wpdb->prepare(" AND id != %d", $exclude_id);
        }

        $existing_event = $wpdb->get_row($query, ARRAY_A);

        wp_send_json_success(array(
            'is_duplicate' => !empty($existing_event),
            'existing_event' => $existing_event
        ));
    }

    /**
     * AJAX: Save inline edit
     */
    public function save_inline_edit() {
        if (!wp_verify_nonce($_POST['nonce'], 'bkgt_admin_nonce') ||
            !current_user_can('manage_options')) {
            wp_die(__('Security check failed', 'bkgt-data-scraping'));
        }

        $item_type = sanitize_text_field($_POST['item_type']);
        $item_id = (int)$_POST['item_id'];
        $field_type = sanitize_text_field($_POST['field_type']);
        $new_value = sanitize_text_field($_POST['new_value']);

        // Validate input
        if (!$this->validate_inline_edit($field_type, $new_value)) {
            wp_send_json_error(array('message' => __('Invalid value provided', 'bkgt-data-scraping')));
            return;
        }

        // Update the database
        $result = false;
        if ($item_type === 'player') {
            $result = $this->update_player_field($item_id, $field_type, $new_value);
        } elseif ($item_type === 'event') {
            $result = $this->update_event_field($item_id, $field_type, $new_value);
        }

        if ($result) {
            wp_send_json_success();
        } else {
            wp_send_json_error(array('message' => __('Failed to update field', 'bkgt-data-scraping')));
        }
    }

    /**
     * Validate inline edit value
     */
    private function validate_inline_edit($field_type, $value) {
        switch ($field_type) {
            case 'player-name':
            case 'event-title':
                return !empty(trim($value));
            case 'player-jersey':
                $num = (int)$value;
                return $num >= 0 && $num <= 99;
            case 'player-position':
                $valid_positions = array('QB', 'RB', 'WR', 'TE', 'OL', 'DL', 'LB', 'CB', 'S', 'K', 'P');
                return in_array($value, $valid_positions);
            case 'event-location':
                return true; // Location can be empty
            default:
                return false;
        }
    }

    /**
     * Update player field
     */
    private function update_player_field($player_id, $field_type, $value) {
        global $wpdb;

        switch ($field_type) {
            case 'player-name':
                // Split name into first and last
                $name_parts = explode(' ', $value, 2);
                $first_name = $name_parts[0];
                $last_name = isset($name_parts[1]) ? $name_parts[1] : '';
                return $wpdb->update(
                    $this->db->tables['players'],
                    array('first_name' => $first_name, 'last_name' => $last_name),
                    array('id' => $player_id),
                    array('%s', '%s'),
                    array('%d')
                );
            case 'player-position':
                return $wpdb->update(
                    $this->db->tables['players'],
                    array('position' => $value),
                    array('id' => $player_id),
                    array('%s'),
                    array('%d')
                );
            case 'player-jersey':
                return $wpdb->update(
                    $this->db->tables['players'],
                    array('jersey_number' => (int)$value),
                    array('id' => $player_id),
                    array('%d'),
                    array('%d')
                );
            default:
                return false;
        }
    }

    /**
     * Update event field
     */
    private function update_event_field($event_id, $field_type, $value) {
        global $wpdb;

        switch ($field_type) {
            case 'event-title':
                return $wpdb->update(
                    $this->db->tables['events'],
                    array('title' => $value),
                    array('id' => $event_id),
                    array('%s'),
                    array('%d')
                );
            case 'event-location':
                return $wpdb->update(
                    $this->db->tables['events'],
                    array('location' => $value),
                    array('id' => $event_id),
                    array('%s'),
                    array('%d')
                );
            default:
                return false;
        }
    }

    /**
     * Get players for bulk assignment wizard
     */
    public function get_bulk_players() {
        check_ajax_referer('bkgt_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'bkgt-data-scraping'));
        }

        $players = $this->db->get_all_players();

        wp_send_json_success($players);
    }

    /**
     * Get events for bulk assignment wizard
     */
    public function get_bulk_events() {
        check_ajax_referer('bkgt_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'bkgt-data-scraping'));
        }

        $events = $this->db->get_all_events();

        wp_send_json_success($events);
    }

    /**
     * Perform bulk assignment
     */
    public function perform_bulk_assignment() {
        check_ajax_referer('bkgt_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'bkgt-data-scraping'));
        }

        $players = isset($_POST['players']) ? array_map('intval', $_POST['players']) : array();
        $events = isset($_POST['events']) ? array_map('intval', $_POST['events']) : array();
        $overwrite = isset($_POST['overwrite']) ? (bool) $_POST['overwrite'] : false;

        if (empty($players) || empty($events)) {
            wp_send_json_error(array('message' => __('Inga spelare eller evenemang valda', 'bkgt-data-scraping')));
            return;
        }

        $assigned = 0;

        foreach ($players as $player_id) {
            foreach ($events as $event_id) {
                if ($overwrite) {
                    // Remove existing assignment if overwrite is enabled
                    $this->db->remove_player_assignment($player_id, $event_id);
                }

                // Check if assignment already exists
                if (!$this->db->player_assignment_exists($player_id, $event_id)) {
                    $result = $this->db->assign_player_to_event($player_id, $event_id);
                    if ($result) {
                        $assigned++;
                    }
                }
            }
        }

        wp_send_json_success(array(
            'assigned' => $assigned,
            'message' => sprintf(__('Tilldelade %d spelare till evenemang', 'bkgt-data-scraping'), $assigned)
        ));
    }

    /**
     * Run scraper via AJAX
     */
    public function run_scraper() {
        check_ajax_referer('bkgt_scraper_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Otillräckliga behörigheter', 'bkgt-data-scraping'));
        }

        $scrape_type = sanitize_text_field($_POST['scrape_type']);
        $scraper = bkgt_data_scraping()->scraper;

        try {
            $result = array();

            switch ($scrape_type) {
                case 'all':
                    $result['teams'] = $scraper->scrape_teams();
                    $result['players'] = $scraper->scrape_players();
                    $result['events'] = $scraper->scrape_events();
                    break;
                case 'teams':
                    $result['teams'] = $scraper->scrape_teams();
                    break;
                case 'players':
                    $result['players'] = $scraper->scrape_players();
                    break;
                case 'events':
                    $result['events'] = $scraper->scrape_events();
                    break;
                default:
                    throw new Exception(__('Ogiltig skrapningstyp', 'bkgt-data-scraping'));
            }

            wp_send_json_success(array(
                'message' => __('Skrapning slutförd', 'bkgt-data-scraping'),
                'result' => $result
            ));

        } catch (Exception $e) {
            wp_send_json_error($e->getMessage());
        }
    }

    /**
     * Get scraper status via AJAX
     */
    public function get_scraper_status() {
        check_ajax_referer('bkgt_scraper_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Otillräckliga behörigheter', 'bkgt-data-scraping'));
        }

        $stats = $this->db->get_scraping_stats();
        $recent_logs = $this->db->get_scraping_logs(5);

        wp_send_json_success(array(
            'stats' => $stats,
            'recent_logs' => $recent_logs
        ));
    }

    /**
     * Save scraper schedule settings
     */
    public function save_schedule() {
        check_ajax_referer('bkgt_schedule_nonce', 'bkgt_schedule_nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Otillräckliga behörigheter', 'bkgt-data-scraping'));
        }

        $enabled = isset($_POST['bkgt_auto_scraping_enabled']) ? 'yes' : 'no';
        $schedule = sanitize_text_field($_POST['bkgt_scraping_schedule']);
        $time = sanitize_text_field($_POST['bkgt_scraping_time']);

        update_option('bkgt_auto_scraping_enabled', $enabled);
        update_option('bkgt_scraping_schedule', $schedule);
        update_option('bkgt_scraping_time', $time);

        // Clear existing schedule
        wp_clear_scheduled_hook('bkgt_auto_scrape');

        // Schedule new event if enabled
        if ($enabled === 'yes') {
            $timestamp = strtotime($time);
            if ($timestamp) {
                $next_run = $this->calculate_next_run($schedule, $timestamp);
                wp_schedule_event($next_run, $schedule, 'bkgt_auto_scrape');
            }
        }

        wp_send_json_success(__('Schemaläggning sparad!', 'bkgt-data-scraping'));
    }

    /**
     * Calculate next run time based on schedule
     */
    private function calculate_next_run($schedule, $base_time) {
        $now = time();
        $base_hour = date('H', $base_time);
        $base_minute = date('i', $base_time);

        switch ($schedule) {
            case 'daily':
                $next_run = strtotime("today {$base_hour}:{$base_minute}");
                if ($next_run <= $now) {
                    $next_run = strtotime("tomorrow {$base_hour}:{$base_minute}");
                }
                break;
            case 'weekly':
                $next_run = strtotime("next monday {$base_hour}:{$base_minute}");
                break;
            case 'monthly':
                $next_run = strtotime("first day of next month {$base_hour}:{$base_minute}");
                break;
            default:
                $next_run = $now + 86400; // Default to tomorrow
        }

        return $next_run;
    }

    /**
     * Run automatic scraping via cron
     */
    public function run_auto_scrape() {
        $scraper = bkgt_data_scraping()->scraper;

        try {
            $scraper->scrape_teams();
            $scraper->scrape_players();
            $scraper->scrape_events();

            // Log successful auto-scrape
            error_log('BKGT Auto-scraping completed successfully');

        } catch (Exception $e) {
            // Log failed auto-scrape
            error_log('BKGT Auto-scraping failed: ' . $e->getMessage());
        }
    }
}