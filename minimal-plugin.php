<?php
/**
 * Plugin Name: BKGT Document Management
 * Plugin URI: https://bkgt.se
 * Description: Secure document management system with version control and access permissions for BKGTS.
 * Version: 1.0.0
 * Author: BKGT Development Team
 * License: GPL v2 or later
 * Text Domain: bkgt-document-management
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class BKGT_Document_Management {

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // Minimal constructor
    }
}

function bkgt_document_management() {
    return BKGT_Document_Management::get_instance();
}

bkgt_document_management();