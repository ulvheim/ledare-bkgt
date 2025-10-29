<?php
require_once('wp-load.php');

echo "=== Page Settings Check ===\n\n";

$page_ids = [20, 21, 22]; // Spelare, Matcher, Lagöversikt

foreach ($page_ids as $page_id) {
    $page = get_post($page_id);
    if ($page) {
        echo "Page ID $page_id ({$page->post_title}):\n";
        echo "  Status: {$page->post_status}\n";
        echo "  Type: {$page->post_type}\n";
        echo "  Password protected: " . (!empty($page->post_password) ? 'YES' : 'NO') . "\n";

        // Check if page requires login
        $is_restricted = false;
        if (function_exists('members_get_post_roles')) {
            $roles = members_get_post_roles($page_id);
            if (!empty($roles)) {
                $is_restricted = true;
                echo "  Restricted roles: " . implode(', ', $roles) . "\n";
            }
        }

        if (!$is_restricted) {
            echo "  Access: Public\n";
        }

        echo "\n";
    } else {
        echo "Page ID $page_id not found\n\n";
    }
}

// Check general site visibility
echo "=== Site Settings ===\n";
echo "Site public: " . (get_option('blog_public') ? 'YES' : 'NO') . "\n";
echo "Users can register: " . (get_option('users_can_register') ? 'YES' : 'NO') . "\n";
?>