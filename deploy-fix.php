<?php
/**
 * Deploy script to remove the conflicting bkgt-api-diagnostic menu registration from document-management plugin
 * This should be uploaded to the server and accessed via web browser
 */

// Check if this is being run on the server
if ($_SERVER['HTTP_HOST'] === 'ledare.bkgt.se' || $_SERVER['SERVER_NAME'] === 'ledare.bkgt.se') {
    $file_path = __DIR__ . '/wp-content/plugins/bkgt-document-management/admin/class-admin.php';
    
    if (!file_exists($file_path)) {
        die('File not found: ' . $file_path);
    }
    
    // Read the file
    $content = file_get_contents($file_path);
    
    // Find and remove the conflicting add_menu_page registration
    // Look for the pattern starting at the template_builder closing and going to the next closing brace
    
    $pattern = '/add_menu_page\(\s*__\(\'API Diagnostik\',\s*\'bkgt-document-management\'\),\s*__\(\'API Diagnostik\',\s*\'bkgt-document-management\'\),\s*\'manage_options\',\s*\'bkgt-api-diagnostic\',\s*array\(\$this,\s*\'api_diagnostic_page\'\),\s*\'dashicons-testimonial\',\s*31\s*\);\s*/s';
    
    $new_content = preg_replace($pattern, '', $content);
    
    if ($new_content === $content) {
        die('Pattern not found! The conflicting menu registration might already be removed or the pattern has changed.');
    }
    
    // Write the file back
    if (file_put_contents($file_path, $new_content) === false) {
        die('Failed to write file!');
    }
    
    echo 'SUCCESS: Conflicting add_menu_page registration removed from document-management plugin!';
    echo '<br><br>Next steps:';
    echo '<br>1. Clear your browser cache';
    echo '<br>2. Log out and log back in to WordPress';
    echo '<br>3. Go to wp-admin/admin.php?page=bkgt-api-diagnostic';
    echo '<br>4. Verify the page now shows under "BKGT API" submenu and displays content';
    
} else {
    echo 'This script can only be run on the target server (ledare.bkgt.se)';
    echo '<br>Upload this file to the root of ledare.bkgt.se and access it via browser.';
    echo '<br>URL: https://ledare.bkgt.se/deploy-fix.php';
}
?>
