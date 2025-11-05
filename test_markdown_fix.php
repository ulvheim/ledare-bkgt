<?php
require_once('wp-load.php');

echo "Testing markdown template content...\n";

try {
    $plugin = new BKGT_Document_Management();
    $frontend = $plugin->get_frontend_class();

    // Test the markdown template content directly
    $content = $frontend->get_template_content('markdown-document');
    echo "Markdown template content:\n";
    echo $content . "\n\n";

    // Test the full template array
    $templates = $frontend->get_default_templates();
    echo "Available templates:\n";
    foreach ($templates as $template) {
        echo "- " . $template['id'] . ": " . $template['name'] . "\n";
        if ($template['id'] === 'markdown-document') {
            echo "  Content length: " . strlen($template['content']) . " characters\n";
            echo "  Variables: " . count($template['variables']) . "\n";
        }
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>