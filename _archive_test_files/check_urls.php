<?php
require_once('wp-load.php');

echo "=== Site URLs ===\n";
echo "Site URL: " . get_site_url() . "\n";
echo "Admin URL: " . admin_url() . "\n";
echo "Login URL: " . wp_login_url() . "\n";
echo "\n=== Inventory Admin URLs ===\n";
echo "Main Inventory: " . admin_url('admin.php?page=bkgt-inventory') . "\n";
echo "All Items: " . admin_url('edit.php?post_type=bkgt_inventory_item') . "\n";
echo "Add New Item: " . admin_url('post-new.php?post_type=bkgt_inventory_item') . "\n";
echo "Manufacturers: " . admin_url('admin.php?page=bkgt-manufacturers') . "\n";
echo "Item Types: " . admin_url('admin.php?page=bkgt-item-types') . "\n";