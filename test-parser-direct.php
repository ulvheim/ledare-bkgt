<?php
echo "Starting parser test...\n";

// Define ABSPATH to prevent the parser from exiting
if (!defined('ABSPATH')) {
    define('ABSPATH', '/some/path/');
}

if (file_exists('includes/class-bkgt-swe3-parser.php')) {
    echo "Parser file exists\n";
} else {
    echo "Parser file does not exist\n";
    exit;
}

require_once 'includes/class-bkgt-swe3-parser.php';

echo "Required parser file\n";

if (class_exists('BKGT_SWE3_Parser')) {
    echo "Class BKGT_SWE3_Parser exists\n";
} else {
    echo "Class BKGT_SWE3_Parser does not exist\n";
    exit;
}

try {
    $parser = new BKGT_SWE3_Parser();
    echo "Parser instantiated successfully\n";
} catch (Exception $e) {
    echo "Error instantiating parser: " . $e->getMessage() . "\n";
}
?>