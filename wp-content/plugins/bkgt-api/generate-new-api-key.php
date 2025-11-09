<?php
/**
 * Generate New BKGT API Key
 * Creates a new API key after flushing old ones
 */

// Include WordPress
require_once('../../../wp-load.php');

if (!is_user_logged_in() || !current_user_can('manage_options')) {
    die('Access denied. You must be logged in as an administrator.');
}

echo "<h1>Generating New BKGT API Key</h1>";

// Include the auth class
require_once('includes/class-bkgt-auth.php');

$auth = new BKGT_API_Auth();

// Generate a new API key
$name = 'Production API Key - ' . date('Y-m-d H:i:s');
$permissions = array('read', 'write', 'admin');

$new_key = $auth->generate_api_key($name, get_current_user_id(), $permissions);

if ($new_key) {
    echo "<p style='color:green'>✅ New API key generated successfully!</p>";
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Field</th><th>Value</th></tr>";
    echo "<tr><td>Name</td><td>" . htmlspecialchars($name) . "</td></tr>";
    echo "<tr><td>API Key</td><td><code style='font-size:16px;'>" . htmlspecialchars($new_key) . "</code></td></tr>";
    echo "<tr><td>Permissions</td><td>" . htmlspecialchars(implode(', ', $permissions)) . "</td></tr>";
    echo "<tr><td>Created By</td><td>" . get_current_user()->display_name . "</td></tr>";
    echo "</table>";

    echo "<div style='background:#e8f5e8; border:1px solid #28a745; padding:15px; margin:20px 0; border-radius:4px;'>";
    echo "<h3>🔑 Your New API Key</h3>";
    echo "<p><strong>Copy this key and store it securely:</strong></p>";
    echo "<input type='text' value='" . htmlspecialchars($new_key) . "' style='width:100%; padding:8px; font-family:monospace; font-size:14px;' readonly onclick='this.select();'>";
    echo "<p><em>This key will only be shown once. Make sure to save it now.</em></p>";
    echo "</div>";

    echo "<hr>";
    echo "<h2>Next Steps</h2>";
    echo "<ol>";
    echo "<li><strong>Save your API key securely</strong> - Store it in your application configuration</li>";
    echo "<li><a href='https://ledare.bkgt.se/wp-content/plugins/bkgt-api/test-production-api.php' target='_blank'>Test the new API key</a> with the production test suite</li>";
    echo "<li>Update your mobile app or integration to use the new key</li>";
    echo "</ol>";

} else {
    echo "<p style='color:red'>❌ Failed to generate new API key</p>";
}
?>