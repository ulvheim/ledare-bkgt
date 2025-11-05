<?php
require_once('wp-load.php');

echo "=== Creating Sample Production Data ===\n\n";

global $wpdb;

// 1. Create sample inventory items
echo "Creating sample inventory items...\n";
$inventory_items = array(
    array('HELM001', 'Schutt F7 VTD', 1, 1, 'Lager A1', 'normal', '2023-08-15', 'Professional football helmet'),
    array('HELM002', 'Riddell SpeedFlex', 2, 1, 'Lager A1', 'normal', '2023-08-16', 'High-performance helmet'),
    array('SHIRT001', 'Nike Vapor Tröja', 3, 2, 'Lager B2', 'normal', '2023-08-17', 'Team practice jersey'),
    array('SHIRT002', 'Under Armour Tröja', 4, 2, 'Lager B2', 'needs_repair', '2023-08-18', 'Game jersey - needs repair'),
    array('PANTS001', 'Nike Vapor Byxor', 3, 3, 'Lager B3', 'normal', '2023-08-19', 'Compression pants'),
    array('SHOES001', 'Nike Vapor Skor', 3, 4, 'Lager C1', 'normal', '2023-08-20', 'Cleats for grass fields'),
    array('BALL001', 'Wilson NFL Boll', 5, 5, 'Lager D1', 'normal', '2023-08-21', 'Official game ball'),
    array('PAD001', 'Schutt SkyFlex', 1, 6, 'Lager A2', 'normal', '2023-08-22', 'Shoulder pads'),
    array('GLOVES001', 'Cutters Handskar', 6, 7, 'Lager C2', 'normal', '2023-08-23', 'Receiver gloves'),
    array('CONES001', 'Gill Cones', 7, 8, 'Lager E1', 'normal', '2023-08-24', 'Training cones - set of 20')
);

$inventory_table = $wpdb->prefix . 'bkgt_inventory_items';
$inventory_count = 0;
foreach ($inventory_items as $item) {
    $result = $wpdb->insert($inventory_table, array(
        'unique_identifier' => $item[0],
        'title' => $item[1],
        'manufacturer_id' => $item[2],
        'item_type_id' => $item[3],
        'storage_location' => $item[4],
        'condition_status' => $item[5],
        'created_at' => $item[6],
        'notes' => $item[7]
    ));
    if ($result) $inventory_count++;
}
echo "✅ Created $inventory_count inventory items\n\n";

// 2. Create sample players
echo "Creating sample players...\n";
$players = array(
    array('Erik Johansson', 'erik.johansson@bkgt.se', '1998-05-15', 'Forward', 'Senior', 1),
    array('Lars Andersson', 'lars.andersson@bkgt.se', '1999-03-22', 'Midfielder', 'Senior', 1),
    array('Mikael Persson', 'mikael.persson@bkgt.se', '2000-07-10', 'Defender', 'Senior', 1),
    array('Anders Nilsson', 'anders.nilsson@bkgt.se', '1997-11-28', 'Goalkeeper', 'Senior', 1),
    array('Johan Karlsson', 'johan.karlsson@bkgt.se', '2001-01-05', 'Forward', 'Junior', 2),
    array('Peter Olsson', 'peter.olsson@bkgt.se', '2002-09-18', 'Midfielder', 'Junior', 2),
    array('Daniel Svensson', 'daniel.svensson@bkgt.se', '1996-12-03', 'Defender', 'Senior', 3),
    array('Stefan Gustafsson', 'stefan.gustafsson@bkgt.se', '1995-04-20', 'Midfielder', 'Senior', 3)
);

$players_count = 0;
foreach ($players as $player) {
    $player_id = wp_insert_post(array(
        'post_title' => $player[0],
        'post_type' => 'bkgt_player',
        'post_status' => 'publish'
    ));

    if ($player_id) {
        update_post_meta($player_id, '_bkgt_player_email', $player[1]);
        update_post_meta($player_id, '_bkgt_player_birth_date', $player[2]);
        update_post_meta($player_id, '_bkgt_player_position', $player[3]);
        update_post_meta($player_id, '_bkgt_player_category', $player[4]);
        update_post_meta($player_id, '_bkgt_player_team_id', $player[5]);
        $players_count++;
    }
}
echo "✅ Created $players_count players\n\n";

// 3. Create sample offboarding processes
echo "Creating sample offboarding processes...\n";
$offboarding_processes = array(
    array('Avslutande Process - Erik Johansson', 'Player departure process for Erik Johansson', 'completed'),
    array('Avslutande Process - Lars Andersson', 'Player departure process for Lars Andersson', 'in_progress'),
    array('Avslutande Process - Mikael Persson', 'Player departure process for Mikael Persson', 'pending')
);

$offboarding_count = 0;
foreach ($offboarding_processes as $process) {
    $process_id = wp_insert_post(array(
        'post_title' => $process[0],
        'post_content' => $process[1],
        'post_type' => 'bkgt_offboarding',
        'post_status' => 'publish'
    ));

    if ($process_id) {
        update_post_meta($process_id, '_bkgt_offboarding_status', $process[2]);
        update_post_meta($process_id, '_bkgt_offboarding_created_date', current_time('mysql'));
        $offboarding_count++;
    }
}
echo "✅ Created $offboarding_count offboarding processes\n\n";

echo "=== Summary ===\n";
echo "Inventory items created: $inventory_count\n";
echo "Players created: $players_count\n";
echo "Offboarding processes created: $offboarding_count\n";
echo "\n✅ Sample production data created successfully!\n";
?>