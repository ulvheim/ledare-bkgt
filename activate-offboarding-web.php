<?php
/**
 * BKGT Offboarding Plugin - Web Activation Script
 * This script allows web-based activation of the BKGT Offboarding plugin
 */

// Include WordPress core
require_once('../../../wp-load.php');

// Check if user is logged in and has admin capabilities
if (!is_user_logged_in() || !current_user_can('manage_options')) {
    wp_die('You do not have permission to access this page.');
}

// Handle activation
$message = '';
$status = '';

if (isset($_POST['activate_offboarding'])) {
    // Verify nonce
    if (!wp_verify_nonce($_POST['bkgt_offboarding_nonce'], 'activate_offboarding')) {
        $message = 'Security check failed.';
        $status = 'error';
    } else {
        // Activate the plugin
        $plugin = 'bkgt-offboarding/bkgt-offboarding.php';

        if (!is_plugin_active($plugin)) {
            $result = activate_plugin($plugin);

            if (is_wp_error($result)) {
                $message = 'Failed to activate plugin: ' . $result->get_error_message();
                $status = 'error';
            } else {
                $message = 'BKGT Offboarding plugin activated successfully!';
                $status = 'success';

                // Create database tables
                if (class_exists('BKGT_Offboarding_Database')) {
                    $db = new BKGT_Offboarding_Database();
                    $db->create_tables();
                    $message .= ' Database tables created.';
                }
            }
        } else {
            $message = 'Plugin is already active.';
            $status = 'info';
        }
    }
}

// Get plugin status
$plugin_active = is_plugin_active('bkgt-offboarding/bkgt-offboarding.php');
$plugin_exists = file_exists(WP_PLUGIN_DIR . '/bkgt-offboarding/bkgt-offboarding.php');

?>
<!DOCTYPE html>
<html lang="sv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BKGT Offboarding - Aktivering</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #2c3e50;
            margin-bottom: 30px;
            text-align: center;
        }
        .status {
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        .status.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .status.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .status.info {
            background-color: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
        .plugin-info {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        .plugin-info h3 {
            margin-top: 0;
            color: #495057;
        }
        .activate-btn {
            background-color: #007cba;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            display: block;
            width: 100%;
            max-width: 200px;
            margin: 0 auto;
        }
        .activate-btn:hover {
            background-color: #005a87;
        }
        .activate-btn:disabled {
            background-color: #cccccc;
            cursor: not-allowed;
        }
        .back-link {
            display: inline-block;
            margin-top: 20px;
            color: #007cba;
            text-decoration: none;
        }
        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>BKGT Offboarding - Plugin Aktivering</h1>

        <?php if ($message): ?>
            <div class="status <?php echo $status; ?>">
                <?php echo esc_html($message); ?>
            </div>
        <?php endif; ?>

        <div class="plugin-info">
            <h3>Plugin Information</h3>
            <p><strong>Plugin:</strong> BKGT Offboarding System</p>
            <p><strong>Beskrivning:</strong> Hanterar personalavgångar med utrustningsåterlämning och åtkomstkontroll.</p>
            <p><strong>Status:</strong>
                <?php if ($plugin_exists): ?>
                    <?php echo $plugin_active ? '<span style="color: green;">Aktiv</span>' : '<span style="color: orange;">Inaktiv</span>'; ?>
                <?php else: ?>
                    <span style="color: red;">Pluginfilen hittades inte</span>
                <?php endif; ?>
            </p>
        </div>

        <?php if ($plugin_exists && !$plugin_active): ?>
            <form method="post">
                <?php wp_nonce_field('activate_offboarding', 'bkgt_offboarding_nonce'); ?>
                <button type="submit" name="activate_offboarding" class="activate-btn">
                    Aktivera Offboarding Plugin
                </button>
            </form>
        <?php elseif ($plugin_active): ?>
            <p style="text-align: center; color: green; font-weight: bold;">
                ✓ Plugin är redan aktivt och redo att användas!
            </p>
        <?php else: ?>
            <p style="text-align: center; color: red;">
                Pluginfilen kunde inte hittas. Kontrollera att plugin-mappen är korrekt uppladdad.
            </p>
        <?php endif; ?>

        <p style="text-align: center;">
            <a href="<?php echo admin_url('plugins.php'); ?>" class="back-link">← Tillbaka till Plugins</a>
        </p>
    </div>
</body>
</html>