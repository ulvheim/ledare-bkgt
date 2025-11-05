<?php
require_once('wp-load.php');

$players = get_posts(array('post_type' => 'bkgt_player', 'numberposts' => -1));
echo 'Players found: ' . count($players) . PHP_EOL;
foreach($players as $p) {
    echo '- ' . $p->post_title . PHP_EOL;
}
?>