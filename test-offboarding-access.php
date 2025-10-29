<?php
/**
 * BKGT Offboarding - Access Control Test
 * Tests role-based access control for the offboarding system
 */

// Include WordPress core
require_once('../../../wp-load.php');

// Check if user is logged in and has admin capabilities
if (!is_user_logged_in() || !current_user_can('manage_options')) {
    wp_die('You do not have permission to access this page.');
}

$message = '';
$access_tests = array();

// Test current user capabilities
$current_user = wp_get_current_user();
$user_roles = $current_user->roles;
$user_caps = array(
    'manage_options' => current_user_can('manage_options'),
    'edit_users' => current_user_can('edit_users'),
    'delete_users' => current_user_can('delete_users'),
    'bkgt_manage_offboarding' => current_user_can('bkgt_manage_offboarding'),
    'bkgt_view_offboarding' => current_user_can('bkgt_view_offboarding')
);

// Test role-based access
$test_roles = array('administrator', 'editor', 'author', 'contributor', 'subscriber');
$role_access = array();

foreach ($test_roles as $role_name) {
    $role = get_role($role_name);
    if ($role) {
        $role_access[$role_name] = array(
            'manage_options' => $role->has_cap('manage_options'),
            'edit_users' => $role->has_cap('edit_users'),
            'bkgt_manage_offboarding' => $role->has_cap('bkgt_manage_offboarding'),
            'bkgt_view_offboarding' => $role->has_cap('bkgt_view_offboarding')
        );
    }
}

// Test custom roles if they exist
$custom_roles = array('bkgt_styrelsemedlem', 'bkgt_tranare', 'bkgt_lagledare');
$custom_role_access = array();

foreach ($custom_roles as $role_name) {
    $role = get_role($role_name);
    if ($role) {
        $custom_role_access[$role_name] = array(
            'manage_options' => $role->has_cap('manage_options'),
            'edit_users' => $role->has_cap('edit_users'),
            'bkgt_manage_offboarding' => $role->has_cap('bkgt_manage_offboarding'),
            'bkgt_view_offboarding' => $role->has_cap('bkgt_view_offboarding')
        );
    } else {
        $custom_role_access[$role_name] = 'Role does not exist';
    }
}

?>
<!DOCTYPE html>
<html lang="sv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BKGT Offboarding - Access Control Test</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            max-width: 1000px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #2c3e50;
            margin-bottom: 30px;
            text-align: center;
        }
        .section {
            margin-bottom: 30px;
            padding: 20px;
            border: 1px solid #dee2e6;
            border-radius: 4px;
        }
        .section h2 {
            margin-top: 0;
            color: #495057;
            border-bottom: 2px solid #007cba;
            padding-bottom: 10px;
        }
        .current-user {
            background-color: #e7f3ff;
            border-left: 4px solid #007cba;
        }
        .role-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        .role-table th, .role-table td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #dee2e6;
        }
        .role-table th {
            background-color: #f8f9fa;
            font-weight: bold;
        }
        .cap-yes {
            color: green;
            font-weight: bold;
        }
        .cap-no {
            color: red;
        }
        .back-link {
            display: inline-block;
            margin-top: 20px;
            color: #007cba;
            text-decoration: none;
        }
        .back-link:hover {
            text-decoration: underline;
        }
        .summary {
            background-color: #d1ecf1;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>BKGT Offboarding - Access Control Test</h1>

        <div class="summary">
            <strong>Current User:</strong> <?php echo esc_html($current_user->display_name); ?> (<?php echo esc_html($current_user->user_login); ?>)<br>
            <strong>Roles:</strong> <?php echo esc_html(implode(', ', $user_roles)); ?>
        </div>

        <div class="section current-user">
            <h2>Current User Capabilities</h2>
            <table class="role-table">
                <thead>
                    <tr>
                        <th>Capability</th>
                        <th>Status</th>
                        <th>Description</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>manage_options</td>
                        <td class="<?php echo $user_caps['manage_options'] ? 'cap-yes' : 'cap-no'; ?>">
                            <?php echo $user_caps['manage_options'] ? 'YES' : 'NO'; ?>
                        </td>
                        <td>Can manage WordPress options (admin access)</td>
                    </tr>
                    <tr>
                        <td>edit_users</td>
                        <td class="<?php echo $user_caps['edit_users'] ? 'cap-yes' : 'cap-no'; ?>">
                            <?php echo $user_caps['edit_users'] ? 'YES' : 'NO'; ?>
                        </td>
                        <td>Can edit other users</td>
                    </tr>
                    <tr>
                        <td>delete_users</td>
                        <td class="<?php echo $user_caps['delete_users'] ? 'cap-yes' : 'cap-no'; ?>">
                            <?php echo $user_caps['delete_users'] ? 'YES' : 'NO'; ?>
                        </td>
                        <td>Can delete users</td>
                    </tr>
                    <tr>
                        <td>bkgt_manage_offboarding</td>
                        <td class="<?php echo $user_caps['bkgt_manage_offboarding'] ? 'cap-yes' : 'cap-no'; ?>">
                            <?php echo $user_caps['bkgt_manage_offboarding'] ? 'YES' : 'NO'; ?>
                        </td>
                        <td>Can manage offboarding processes</td>
                    </tr>
                    <tr>
                        <td>bkgt_view_offboarding</td>
                        <td class="<?php echo $user_caps['bkgt_view_offboarding'] ? 'cap-yes' : 'cap-no'; ?>">
                            <?php echo $user_caps['bkgt_view_offboarding'] ? 'YES' : 'NO'; ?>
                        </td>
                        <td>Can view offboarding status</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="section">
            <h2>Standard WordPress Roles</h2>
            <table class="role-table">
                <thead>
                    <tr>
                        <th>Role</th>
                        <th>manage_options</th>
                        <th>edit_users</th>
                        <th>bkgt_manage_offboarding</th>
                        <th>bkgt_view_offboarding</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($role_access as $role_name => $caps): ?>
                        <tr>
                            <td><?php echo esc_html($role_name); ?></td>
                            <td class="<?php echo $caps['manage_options'] ? 'cap-yes' : 'cap-no'; ?>">
                                <?php echo $caps['manage_options'] ? 'YES' : 'NO'; ?>
                            </td>
                            <td class="<?php echo $caps['edit_users'] ? 'cap-yes' : 'cap-no'; ?>">
                                <?php echo $caps['edit_users'] ? 'YES' : 'NO'; ?>
                            </td>
                            <td class="<?php echo $caps['bkgt_manage_offboarding'] ? 'cap-yes' : 'cap-no'; ?>">
                                <?php echo $caps['bkgt_manage_offboarding'] ? 'YES' : 'NO'; ?>
                            </td>
                            <td class="<?php echo $caps['bkgt_view_offboarding'] ? 'cap-yes' : 'cap-no'; ?>">
                                <?php echo $caps['bkgt_view_offboarding'] ? 'YES' : 'NO'; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="section">
            <h2>BKGT Custom Roles</h2>
            <table class="role-table">
                <thead>
                    <tr>
                        <th>Role</th>
                        <th>manage_options</th>
                        <th>edit_users</th>
                        <th>bkgt_manage_offboarding</th>
                        <th>bkgt_view_offboarding</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($custom_role_access as $role_name => $caps): ?>
                        <tr>
                            <td><?php echo esc_html($role_name); ?></td>
                            <?php if (is_array($caps)): ?>
                                <td class="<?php echo $caps['manage_options'] ? 'cap-yes' : 'cap-no'; ?>">
                                    <?php echo $caps['manage_options'] ? 'YES' : 'NO'; ?>
                                </td>
                                <td class="<?php echo $caps['edit_users'] ? 'cap-yes' : 'cap-no'; ?>">
                                    <?php echo $caps['edit_users'] ? 'YES' : 'NO'; ?>
                                </td>
                                <td class="<?php echo $caps['bkgt_manage_offboarding'] ? 'cap-yes' : 'cap-no'; ?>">
                                    <?php echo $caps['bkgt_manage_offboarding'] ? 'YES' : 'NO'; ?>
                                </td>
                                <td class="<?php echo $caps['bkgt_view_offboarding'] ? 'cap-yes' : 'cap-no'; ?>">
                                    <?php echo $caps['bkgt_view_offboarding'] ? 'YES' : 'NO'; ?>
                                </td>
                            <?php else: ?>
                                <td colspan="4" style="color: orange;"><?php echo esc_html($caps); ?></td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <p style="text-align: center;">
            <a href="<?php echo admin_url('admin.php?page=bkgt-offboarding'); ?>" class="back-link">← Tillbaka till Offboarding Admin</a>
        </p>
    </div>
</body>
</html>