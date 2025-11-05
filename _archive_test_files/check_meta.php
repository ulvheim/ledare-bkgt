<?php
require_once('wp-load.php');

$meta = get_post_meta(100);
echo "Metadata for post 100:\n";
foreach ($meta as $key => $value) {
    echo "$key: " . $value[0] . "\n";
}
?>