<?php
/**
 * Admin interface for BKGT Communication
 */

class BKGT_Communication_Admin {
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
    }
    
    public function add_admin_menu() {
        add_menu_page(
            __('BKGT Kommunikation', 'bkgt-communication'),
            __('Kommunikation', 'bkgt-communication'),
            'manage_options',
            'bkgt-communication',
            array($this, 'admin_page'),
            'dashicons-email',
            30
        );
    }
    
    public function admin_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('BKGT Kommunikationssystem', 'bkgt-communication'); ?></h1>
            <p><?php _e('Hantera meddelanden och notifikationer.', 'bkgt-communication'); ?></p>
        </div>
        <?php
    }
}