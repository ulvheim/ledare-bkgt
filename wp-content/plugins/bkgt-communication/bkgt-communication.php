<?php
/**
 * Plugin Name: BKGT Communication System
 * Plugin URI: https://ledare.bkgt.se
 * Description: Kommunikationssystem för BKGTS Ledarsystem. Hanterar meddelanden, notifikationer och kommunikation med medlemmar.
 * Version: 1.0.0
 * Author: BKGT Amerikansk Fotboll
 * Author URI: https://bkgt.se
 * Text Domain: bkgt-communication
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 8.0
 * License: Proprietary
 * Requires Plugins: bkgt-core
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('BKGT_COMM_VERSION', '1.0.0');
define('BKGT_COMM_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('BKGT_COMM_PLUGIN_URL', plugin_dir_url(__FILE__));
define('BKGT_COMM_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Include required files
require_once BKGT_COMM_PLUGIN_DIR . 'includes/class-database.php';
require_once BKGT_COMM_PLUGIN_DIR . 'includes/class-message.php';
require_once BKGT_COMM_PLUGIN_DIR . 'includes/class-notification.php';
require_once BKGT_COMM_PLUGIN_DIR . 'admin/class-admin.php';

/**
 * Main Plugin Class
 */
class BKGT_Communication {
    
    /**
     * Single instance of the class
     */
    private static $instance = null;
    
    /**
     * Database handler
     */
    public $db;
    
    /**
     * Get singleton instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->db = new BKGT_Communication_Database();
        $this->init_hooks();
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        
        add_action('init', array($this, 'load_textdomain'));
        
        // Frontend assets
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
        
        // AJAX handlers
        add_action('wp_ajax_bkgt_send_message', array($this, 'ajax_send_message'));
        add_action('wp_ajax_bkgt_get_notifications', array($this, 'ajax_get_notifications'));
        
        // Shortcodes
        add_shortcode('bkgt_communication', array($this, 'shortcode_communication'));
    }
    
    /**
     * Plugin activation
     */
    public function activate() {
        if (!function_exists('bkgt_log')) {
            deactivate_plugins(plugin_basename(__FILE__));
            wp_die(__('BKGT Core plugin must be activated first.', 'bkgt-communication'));
        }
        bkgt_log('info', 'Communication plugin activated');
        $this->db->create_tables();
        flush_rewrite_rules();
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate() {
        if (function_exists('bkgt_log')) {
            bkgt_log('info', 'Communication plugin deactivated');
        }
        flush_rewrite_rules();
    }
    
    /**
     * Load plugin textdomain
     */
    public function load_textdomain() {
        load_plugin_textdomain(
            'bkgt-communication',
            false,
            dirname(BKGT_COMM_PLUGIN_BASENAME) . '/languages'
        );
    }
    
    /**
     * Enqueue frontend assets
     */
    public function enqueue_frontend_assets() {
        if (!is_admin()) {
            wp_enqueue_style(
                'bkgt-communication-frontend',
                BKGT_COMM_PLUGIN_URL . 'assets/frontend.css',
                array(),
                BKGT_COMM_VERSION
            );
            
            wp_enqueue_script(
                'bkgt-communication-frontend',
                BKGT_COMM_PLUGIN_URL . 'assets/frontend.js',
                array('jquery'),
                BKGT_COMM_VERSION,
                true
            );
            
            wp_localize_script('bkgt-communication-frontend', 'bkgt_comm_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('bkgt_comm_nonce')
            ));
        }
    }
    
    /**
     * AJAX send message
     */
    public function ajax_send_message() {
        // Verify nonce using BKGT Core
        if (!bkgt_validate('verify_nonce', $_POST['nonce'] ?? '', 'bkgt_comm_nonce')) {
            bkgt_log('warning', 'Send message nonce verification failed', array(
                'user_id' => get_current_user_id(),
            ));
            wp_send_json_error(array('message' => __('Säkerhetskontroll misslyckades.', 'bkgt-communication')));
        }
        
        // Check permissions using BKGT Core
        if (!bkgt_can('send_messages')) {
            bkgt_log('warning', 'Send message denied - insufficient permissions', array(
                'user_id' => get_current_user_id(),
            ));
            wp_send_json_error(array('message' => __('Du har inte behörighet att skicka meddelanden.', 'bkgt-communication')));
        }
        
        // Get and validate input using BKGT Core
        $subject = bkgt_validate('sanitize_text', $_POST['subject'] ?? '');
        $message = bkgt_validate('sanitize_html', $_POST['message'] ?? '');
        $recipients = isset($_POST['recipients']) ? (array) $_POST['recipients'] : array();
        
        if (empty($subject) || empty($message)) {
            bkgt_log('warning', 'Send message - subject or message empty');
            wp_send_json_error(array('message' => __('Ämne och meddelande är obligatoriska.', 'bkgt-communication')));
        }
        
        if (empty($recipients)) {
            bkgt_log('warning', 'Send message - no recipients specified');
            wp_send_json_error(array('message' => __('Minst en mottagare krävs.', 'bkgt-communication')));
        }
        
        // Send message logic
        $result = $this->send_message($subject, $message, $recipients);
        
        if ($result) {
            bkgt_log('info', 'Message sent successfully', array(
                'recipients_count' => count($recipients),
                'user_id' => get_current_user_id(),
            ));
            wp_send_json_success(array('message' => __('Meddelande skickat framgångsrikt!', 'bkgt-communication')));
        } else {
            bkgt_log('error', 'Failed to send message', array(
                'recipients_count' => count($recipients),
            ));
            wp_send_json_error(array('message' => __('Kunde inte skicka meddelande.', 'bkgt-communication')));
        }
    }
    
    /**
     * AJAX get notifications
     */
    public function ajax_get_notifications() {
        // Verify nonce using BKGT Core
        if (!bkgt_validate('verify_nonce', $_POST['nonce'] ?? '', 'bkgt_comm_nonce')) {
            bkgt_log('warning', 'Get notifications nonce verification failed', array(
                'user_id' => get_current_user_id(),
            ));
            wp_send_json_error(array('message' => __('Säkerhetskontroll misslyckades.', 'bkgt-communication')));
        }

        // Check if user is logged in
        if (!is_user_logged_in()) {
            bkgt_log('warning', 'Get notifications - user not logged in');
            wp_send_json_error(array('message' => __('Du måste vara inloggad.', 'bkgt-communication')));
        }
        
        // Check permissions using BKGT Core
        if (!bkgt_can('view_notifications')) {
            bkgt_log('warning', 'Get notifications denied - insufficient permissions', array(
                'user_id' => get_current_user_id(),
            ));
            wp_send_json_error(array('message' => __('Du har inte behörighet att visa notifikationer.', 'bkgt-communication')));
        }
        
        $user_id = get_current_user_id();
        $notifications = $this->get_user_notifications($user_id);
        
        bkgt_log('info', 'Notifications retrieved', array(
            'notifications_count' => count($notifications),
            'user_id' => $user_id,
        ));
        
        wp_send_json_success($notifications);
    }
    
    /**
     * Send message
     */
    private function send_message($subject, $message, $recipients) {
        // Use the Message class to send
        $message_id = BKGT_Communication_Message::send($subject, $message, $recipients);
        
        if (!$message_id) {
            bkgt_log('error', 'Failed to send message');
            return false;
        }
        
        bkgt_log('info', 'Message sent successfully', array(
            'message_id' => $message_id,
            'recipients' => $recipients,
        ));
        
        return $message_id;
    }
    
    /**
     * Get user notifications
     */
    private function get_user_notifications($user_id) {
        // Use the Notification class to get notifications
        $notifications = BKGT_Communication_Notification::get_notifications($user_id);
        
        bkgt_log('info', 'Retrieved notifications for user', array(
            'user_id' => $user_id,
            'count' => count($notifications),
        ));
        
        return $notifications;
    }
    
    /**
     * Shortcode for communication display
     */
    public function shortcode_communication($atts) {
        // Check user permissions
        if (!is_user_logged_in()) {
            return '<p>' . __('Du måste vara inloggad för att se denna sida.', 'bkgt-communication') . '</p>';
        }
        
        ob_start();
        ?>
        <div class="bkgt-communication-container">
            <h2><?php _e('Kommunikation', 'bkgt-communication'); ?></h2>
            <p><?php _e('Här kan du skicka meddelanden och hantera kommunikation.', 'bkgt-communication'); ?></p>
            
            <div class="communication-tabs">
                <button class="tab-button active" data-tab="messages"><?php _e('Meddelanden', 'bkgt-communication'); ?></button>
                <button class="tab-button" data-tab="notifications"><?php _e('Notifikationer', 'bkgt-communication'); ?></button>
            </div>
            
            <div id="messages-tab" class="tab-content active">
                <div class="messages-section">
                    <h3><?php _e('Skicka nytt meddelande', 'bkgt-communication'); ?></h3>
                    <form id="send-message-form">
                        <div class="form-group">
                            <label for="message-subject"><?php _e('Ämne', 'bkgt-communication'); ?></label>
                            <input type="text" id="message-subject" name="subject" required placeholder="Ange ämne...">
                        </div>
                        <div class="form-group">
                            <label for="message-content"><?php _e('Meddelande', 'bkgt-communication'); ?></label>
                            <textarea id="message-content" name="message" rows="5" required placeholder="Skriv ditt meddelande här..."></textarea>
                        </div>
                        <div class="form-group">
                            <label><?php _e('Mottagare', 'bkgt-communication'); ?></label>
                            <div class="recipient-options">
                                <label><input type="checkbox" name="recipients[]" value="all"> <?php _e('Alla medlemmar', 'bkgt-communication'); ?></label>
                                <label><input type="checkbox" name="recipients[]" value="coaches"> <?php _e('Tränare', 'bkgt-communication'); ?></label>
                                <label><input type="checkbox" name="recipients[]" value="managers"> <?php _e('Lagledare', 'bkgt-communication'); ?></label>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary"><?php _e('Skicka Meddelande', 'bkgt-communication'); ?></button>
                    </form>
                </div>
                
                <div class="messages-section">
                    <h3><?php _e('Senaste meddelanden', 'bkgt-communication'); ?></h3>
                    <div class="messages-list">
                        <div class="message-item">
                            <div class="message-header">
                                <strong>Träningsuppdatering</strong>
                                <span class="message-date">2025-10-20</span>
                            </div>
                            <div class="message-sender">Från: Huvudtränare</div>
                            <div class="message-preview">Morgondagens träning börjar kl. 18:00 på hemmaplan. Glöm inte att ta med vattenflaska och rätt utrustning...</div>
                        </div>
                        
                        <div class="message-item">
                            <div class="message-header">
                                <strong>Matchresultat</strong>
                                <span class="message-date">2025-10-18</span>
                            </div>
                            <div class="message-sender">Från: Lagledare</div>
                            <div class="message-preview">Bra jobbat igår! Vi vann med 28-14 mot Stockholm Gladiators. Nästa match är på lördag...</div>
                        </div>
                        
                        <div class="message-item">
                            <div class="message-header">
                                <strong>Utrustningsinventering</strong>
                                <span class="message-date">2025-10-15</span>
                            </div>
                            <div class="message-sender">Från: Utrustningsansvarig</div>
                            <div class="message-preview">Påminnelse: Glöm inte att lämna tillbaka utrustning efter träning. Kontakta mig om något är trasigt...</div>
                        </div>
                        
                        <div class="message-item">
                            <div class="message-header">
                                <strong>Välkommen nya spelare!</strong>
                                <span class="message-date">2025-10-10</span>
                            </div>
                            <div class="message-sender">Från: Styrelsen</div>
                            <div class="message-preview">Välkomna till BKGTS! Första träningen är på måndag. Läs igenom säkerhetsinstruktionerna innan...</div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div id="notifications-tab" class="tab-content">
                <h3><?php _e('Notifikationer', 'bkgt-communication'); ?></h3>
                <div id="notifications-list">
                    <div class="notification-item unread">
                        <div class="notification-icon">
                            <span class="dashicons dashicons-calendar"></span>
                        </div>
                        <div class="notification-content">
                            <strong>Träning inställd</strong>
                            <p>Morgondagens träning kl. 18:00 är inställd p.g.a. väder</p>
                            <small>2025-10-25 14:30</small>
                        </div>
                    </div>
                    
                    <div class="notification-item">
                        <div class="notification-icon">
                            <span class="dashicons dashicons-groups"></span>
                        </div>
                        <div class="notification-content">
                            <strong>Nytt meddelande från tränare</strong>
                            <p>Du har fått ett nytt meddelande från huvudtränaren</p>
                            <small>2025-10-24 16:45</small>
                        </div>
                    </div>
                    
                    <div class="notification-item">
                        <div class="notification-icon">
                            <span class="dashicons dashicons-admin-tools"></span>
                        </div>
                        <div class="notification-content">
                            <strong>Utrustning behöver service</strong>
                            <p>2 hjälmar behöver repareras. Kontakta utrustningsansvarig</p>
                            <small>2025-10-23 09:15</small>
                        </div>
                    </div>
                    
                    <div class="notification-item">
                        <div class="notification-icon">
                            <span class="dashicons dashicons-tickets"></span>
                        </div>
                        <div class="notification-content">
                            <strong>Ny match inbokad</strong>
                            <p>Match mot Uppsala 86ers den 15 november</p>
                            <small>2025-10-22 11:20</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <style>
        .communication-tabs {
            display: flex;
            gap: 5px;
            margin-bottom: 20px;
            border-bottom: 1px solid #ddd;
        }
        .tab-button {
            padding: 10px 20px;
            border: none;
            background: #f5f5f5;
            cursor: pointer;
            border-radius: 5px 5px 0 0;
        }
        .tab-button.active {
            background: #007cba;
            color: white;
        }
        .tab-content {
            display: none;
        }
        .tab-content.active {
            display: block;
        }
        .messages-section {
            margin-bottom: 30px;
        }
        .messages-list {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        .message-item {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            background: #fff;
        }
        .message-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
        }
        .message-date {
            color: #999;
            font-size: 12px;
        }
        .message-sender {
            color: #666;
            font-size: 14px;
            margin-bottom: 8px;
        }
        .message-preview {
            color: #333;
            line-height: 1.4;
        }
        .notifications-list {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        .notification-item {
            display: flex;
            align-items: flex-start;
            gap: 15px;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            background: #fff;
        }
        .notification-item.unread {
            border-left: 4px solid #007cba;
            background: #f0f8ff;
        }
        .notification-icon {
            color: #007cba;
            font-size: 20px;
            margin-top: 2px;
        }
        .notification-content strong {
            display: block;
            color: #007cba;
            margin-bottom: 5px;
        }
        .notification-content p {
            margin: 0 0 5px 0;
            color: #666;
        }
        .notification-content small {
            color: #999;
            font-size: 12px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        .form-group input, .form-group textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .recipient-options {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }
        .btn-primary {
            background: #007cba;
            color: white;
        }
        .btn-outline {
            background: transparent;
            color: #007cba;
            border: 1px solid #007cba;
        }
        </style>
        <?php
        return ob_get_clean();
    }
}

/**
 * Initialize the plugin
 */
function bkgt_communication() {
    return BKGT_Communication::get_instance();
}

// Start the plugin
bkgt_communication();

// Initialize admin classes
add_action('admin_init', function() {
    new BKGT_Communication_Admin();
});