<?php
require_once('wp-load.php');

echo "Testing markdown template...\n";

try {
    $plugin = new BKGT_Document_Management();
    $frontend = $plugin->get_frontend_class();
    $templates = $frontend->get_default_templates();

    echo "Available templates:\n";
    foreach ($templates as $template) {
        echo "- " . $template['id'] . ": " . $template['name'] . "\n";
    }

    // Test markdown template content
    $markdown_content = $frontend->get_template_content('markdown-document');
    echo "\nMarkdown template content:\n";
    echo $markdown_content . "\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>