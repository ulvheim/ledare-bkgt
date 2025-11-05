<?php
/**
 * Admin Button Integration Examples
 * 
 * Demonstrates how to use the BKGT Button System in WordPress admin pages.
 * Shows practical patterns for modernizing admin UI.
 *
 * @package BKGT_Core
 * @subpackage Examples
 * @version 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * ============================================================================
 * EXAMPLE 1: Settings Page Form Buttons
 * ============================================================================
 * 
 * Common use case: Replace default WordPress form submit buttons with
 * modern button system buttons on settings pages.
 */

?>
<!-- EXAMPLE 1: Settings Page Form Buttons -->
<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <form method="post" action="options.php" class="settings-form">
        <?php settings_fields('example_settings_group'); ?>
        
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="example_setting"><?php esc_html_e('Example Setting', 'bkgt'); ?></label>
                </th>
                <td>
                    <input type="text" 
                           id="example_setting" 
                           name="example_setting" 
                           value="<?php echo esc_attr(get_option('example_setting')); ?>"
                           class="regular-text"
                           placeholder="Enter setting value">
                </td>
            </tr>
        </table>

        <!-- Button Group Using BKGT Button System -->
        <div class="button-group">
            <?php
            // Save button (primary action)
            if (function_exists('bkgt_button')) {
                echo bkgt_button()
                    ->text(__('Save Changes', 'bkgt'))
                    ->variant('primary')
                    ->size('medium')
                    ->type('submit')
                    ->classes('button-submit')
                    ->build();
            } else {
                // Fallback for when plugin not active
                echo '<button type="submit" class="button button-primary">' . 
                     esc_html__('Save Changes', 'bkgt') . 
                     '</button>';
            }
            ?>
        </div>
    </form>
</div>

<?php

/**
 * ============================================================================
 * EXAMPLE 2: Plugin Management Buttons
 * ============================================================================
 * 
 * Common use case: Style plugin action buttons (Activate, Deactivate, Delete)
 * with the new button system instead of default WordPress styling.
 */

?>
<!-- EXAMPLE 2: Plugin Management Buttons -->
<div class="plugins-page">
    <?php
    // Get list of plugins
    $plugins = get_plugins();
    
    if (!empty($plugins)) {
        echo '<div class="plugin-list">';
        
        foreach ($plugins as $plugin_file => $plugin_data) {
            $is_active = is_plugin_active($plugin_file);
            
            echo '<div class="plugin-item">';
            echo '<h3>' . esc_html($plugin_data['Name']) . '</h3>';
            echo '<p>' . esc_html($plugin_data['Description']) . '</p>';
            
            // Button group for plugin actions
            echo '<div class="plugin-actions">';
            
            if ($is_active) {
                // Deactivate button (secondary - because it's less common)
                if (function_exists('bkgt_button')) {
                    echo bkgt_button()
                        ->text(__('Deactivate', 'bkgt'))
                        ->variant('secondary')
                        ->size('medium')
                        ->href(wp_nonce_url(
                            admin_url('plugins.php?action=deactivate&plugin=' . urlencode($plugin_file)),
                            'deactivate-plugin_' . $plugin_file
                        ))
                        ->classes('plugin-deactivate-btn')
                        ->build();
                }
            } else {
                // Activate button (primary - main action)
                if (function_exists('bkgt_button')) {
                    echo bkgt_button()
                        ->text(__('Activate', 'bkgt'))
                        ->variant('primary')
                        ->size('medium')
                        ->href(wp_nonce_url(
                            admin_url('plugins.php?action=activate&plugin=' . urlencode($plugin_file)),
                            'activate-plugin_' . $plugin_file
                        ))
                        ->classes('plugin-activate-btn')
                        ->build();
                }
            }
            
            // Settings button (info variant)
            if (function_exists('bkgt_button')) {
                echo bkgt_button()
                    ->text(__('Settings', 'bkgt'))
                    ->variant('info')
                    ->size('medium')
                    ->href(admin_url('admin.php?page=plugin-settings'))
                    ->classes('plugin-settings-btn')
                    ->build();
            }
            
            // Delete button (danger variant)
            if (function_exists('bkgt_button')) {
                echo bkgt_button()
                    ->text(__('Delete', 'bkgt'))
                    ->variant('danger')
                    ->size('medium')
                    ->href(wp_nonce_url(
                        admin_url('plugins.php?action=delete&plugin=' . urlencode($plugin_file)),
                        'delete-plugin_' . $plugin_file
                    ))
                    ->classes('plugin-delete-btn')
                    ->onclick('return confirm("' . esc_attr__('Delete this plugin?', 'bkgt') . '")')
                    ->build();
            }
            
            echo '</div>'; // .plugin-actions
            echo '</div>'; // .plugin-item
        }
        
        echo '</div>'; // .plugin-list
    }
    ?>
</div>

<?php

/**
 * ============================================================================
 * EXAMPLE 3: Theme Management Buttons
 * ============================================================================
 * 
 * Common use case: Modernize theme selection buttons on the Themes page.
 */

?>
<!-- EXAMPLE 3: Theme Management Buttons -->
<div class="themes-page">
    <?php
    // Get all themes
    $themes = wp_get_themes();
    $current_theme = wp_get_theme();
    
    if (!empty($themes)) {
        echo '<div class="themes-grid">';
        
        foreach ($themes as $theme_slug => $theme) {
            $is_current = ($theme->get_stylesheet() === $current_theme->get_stylesheet());
            
            echo '<div class="theme-card">';
            echo '<h3>' . esc_html($theme->get('Name')) . '</h3>';
            
            // Button group for theme actions
            echo '<div class="theme-actions">';
            
            if (!$is_current) {
                // Activate button (primary action)
                if (function_exists('bkgt_button')) {
                    echo bkgt_button()
                        ->text(__('Activate', 'bkgt'))
                        ->variant('primary')
                        ->size('medium')
                        ->href(wp_nonce_url(
                            admin_url('themes.php?action=activate&stylesheet=' . urlencode($theme->get_stylesheet())),
                            'switch-theme_' . $theme->get_stylesheet()
                        ))
                        ->classes('theme-activate-btn')
                        ->build();
                }
            } else {
                // Current theme indicator button (success variant - non-clickable)
                if (function_exists('bkgt_button')) {
                    echo bkgt_button()
                        ->text(__('Active Theme', 'bkgt'))
                        ->variant('success')
                        ->size('medium')
                        ->disabled(true)
                        ->classes('theme-current-btn')
                        ->build();
                }
            }
            
            // Preview button (secondary)
            if (function_exists('bkgt_button')) {
                echo bkgt_button()
                    ->text(__('Preview', 'bkgt'))
                    ->variant('secondary')
                    ->size('medium')
                    ->href(wp_nonce_url(
                        admin_url('themes.php?action=preview&stylesheet=' . urlencode($theme->get_stylesheet())),
                        'preview-theme_' . $theme->get_stylesheet()
                    ))
                    ->classes('theme-preview-btn')
                    ->build();
            }
            
            // Customize button (info variant)
            if ($is_current && function_exists('bkgt_button')) {
                echo bkgt_button()
                    ->text(__('Customize', 'bkgt'))
                    ->variant('info')
                    ->size('medium')
                    ->href(admin_url('customize.php'))
                    ->classes('theme-customize-btn')
                    ->build();
            }
            
            // Delete button (danger - only for non-active themes)
            if (!$is_current && current_user_can('delete_themes') && function_exists('bkgt_button')) {
                echo bkgt_button()
                    ->text(__('Delete', 'bkgt'))
                    ->variant('danger')
                    ->size('medium')
                    ->href(wp_nonce_url(
                        admin_url('themes.php?action=delete&stylesheet=' . urlencode($theme->get_stylesheet())),
                        'delete-theme_' . $theme->get_stylesheet()
                    ))
                    ->classes('theme-delete-btn')
                    ->onclick('return confirm("' . esc_attr__('Delete this theme?', 'bkgt') . '")')
                    ->build();
            }
            
            echo '</div>'; // .theme-actions
            echo '</div>'; // .theme-card
        }
        
        echo '</div>'; // .themes-grid
    }
    ?>
</div>

<?php

/**
 * ============================================================================
 * EXAMPLE 4: User Management Buttons
 * ============================================================================
 * 
 * Common use case: Update user action buttons in the Users admin page.
 */

?>
<!-- EXAMPLE 4: User Management Buttons -->
<div class="users-page">
    <?php
    // Get users
    $users = get_users(['number' => -1]);
    
    if (!empty($users)) {
        echo '<table class="wp-list-table widefat">';
        echo '<thead><tr><th>User</th><th>Role</th><th>Actions</th></tr></thead>';
        echo '<tbody>';
        
        foreach ($users as $user) {
            echo '<tr>';
            echo '<td>' . esc_html($user->display_name) . '</td>';
            echo '<td>' . esc_html(implode(', ', $user->roles)) . '</td>';
            echo '<td class="user-actions">';
            
            // View button (secondary)
            if (function_exists('bkgt_button')) {
                echo bkgt_button()
                    ->text(__('View', 'bkgt'))
                    ->variant('secondary')
                    ->size('small')
                    ->href(get_author_posts_url($user->ID))
                    ->classes('user-view-btn')
                    ->build();
            }
            
            // Edit button (info)
            if (current_user_can('edit_user', $user->ID) && function_exists('bkgt_button')) {
                echo bkgt_button()
                    ->text(__('Edit', 'bkgt'))
                    ->variant('info')
                    ->size('small')
                    ->href(admin_url('user-edit.php?user_id=' . $user->ID))
                    ->classes('user-edit-btn')
                    ->build();
            }
            
            // Delete button (danger - only for non-self, non-super admin)
            if (current_user_can('delete_user', $user->ID) && 
                $user->ID !== get_current_user_id() && 
                !is_super_admin($user->ID) && 
                function_exists('bkgt_button')) {
                echo bkgt_button()
                    ->text(__('Delete', 'bkgt'))
                    ->variant('danger')
                    ->size('small')
                    ->href(wp_nonce_url(
                        admin_url('user-edit.php?action=delete&user=' . $user->ID),
                        'delete-user_' . $user->ID
                    ))
                    ->classes('user-delete-btn')
                    ->onclick('return confirm("' . esc_attr__('Delete this user?', 'bkgt') . '")')
                    ->build();
            }
            
            echo '</td>';
            echo '</tr>';
        }
        
        echo '</tbody>';
        echo '</table>';
    }
    ?>
</div>

<?php

/**
 * ============================================================================
 * EXAMPLE 5: Bulk Action Buttons
 * ============================================================================
 * 
 * Common use case: Add bulk action buttons with modern styling.
 */

?>
<!-- EXAMPLE 5: Bulk Action Buttons -->
<div class="bulk-actions">
    <label for="bulk-action-selector"><?php esc_html_e('Bulk Actions:', 'bkgt'); ?></label>
    <select id="bulk-action-selector" name="bulk_action">
        <option value=""><?php esc_html_e('Select Action', 'bkgt'); ?></option>
        <option value="delete"><?php esc_html_e('Delete', 'bkgt'); ?></option>
        <option value="export"><?php esc_html_e('Export', 'bkgt'); ?></option>
        <option value="archive"><?php esc_html_e('Archive', 'bkgt'); ?></option>
    </select>

    <!-- Apply button (primary action) -->
    <?php
    if (function_exists('bkgt_button')) {
        echo bkgt_button()
            ->text(__('Apply', 'bkgt'))
            ->variant('primary')
            ->size('medium')
            ->type('submit')
            ->classes('bulk-action-apply')
            ->build();
    }
    ?>
</div>

<?php

/**
 * ============================================================================
 * EXAMPLE 6: Confirmation Modal Button Pattern
 * ============================================================================
 * 
 * Common use case: Use buttons that trigger confirmation modals instead
 * of inline confirmations.
 */

?>
<!-- EXAMPLE 6: Confirmation Modal Buttons (JavaScript) -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Pattern: Danger button that shows confirmation modal
    document.addEventListener('click', function(e) {
        const dangerBtn = e.target.closest('.confirm-action-btn');
        if (!dangerBtn) return;
        
        e.preventDefault();
        
        const action = dangerBtn.getAttribute('data-action');
        const confirmText = dangerBtn.getAttribute('data-confirm');
        const redirectUrl = dangerBtn.getAttribute('href');
        
        // Show confirmation modal if available
        if (typeof BKGTModal !== 'undefined') {
            const modal = new BKGTModal({
                title: 'Confirm Action',
                content: confirmText || 'Are you sure?',
                buttons: [
                    {
                        text: 'Cancel',
                        action: 'cancel',
                        variant: 'secondary'
                    },
                    {
                        text: 'Confirm',
                        action: 'confirm',
                        variant: 'danger'
                    }
                ],
                onConfirm: function() {
                    // Redirect to the action URL
                    window.location.href = redirectUrl;
                }
            });
            modal.open();
        } else {
            // Fallback to simple confirm
            if (confirm(confirmText || 'Are you sure?')) {
                window.location.href = redirectUrl;
            }
        }
    });
});
</script>

<!-- Usage: -->
<?php
if (function_exists('bkgt_button')) {
    echo bkgt_button()
        ->text(__('Delete User', 'bkgt'))
        ->variant('danger')
        ->size('medium')
        ->href('#')
        ->classes('confirm-action-btn')
        ->attribute('data-action', 'delete_user')
        ->attribute('data-confirm', __('Are you sure you want to delete this user? This cannot be undone.', 'bkgt'))
        ->onclick('return false;')
        ->build();
}
?>

<?php

/**
 * ============================================================================
 * EXAMPLE 7: Button Group Patterns
 * ============================================================================
 * 
 * Common use case: Group related buttons together for better UX.
 */

?>
<!-- EXAMPLE 7: Button Groups -->
<div class="button-group-example">
    
    <!-- Horizontal button group -->
    <div class="button-group button-group-horizontal">
        <label><?php esc_html_e('Status Actions:', 'bkgt'); ?></label>
        
        <?php
        if (function_exists('bkgt_button')) {
            // Publish button (primary)
            echo bkgt_button()
                ->text(__('Publish', 'bkgt'))
                ->variant('primary')
                ->size('medium')
                ->type('submit')
                ->classes('status-action-btn')
                ->attribute('data-status', 'publish')
                ->build();
            
            // Schedule button (secondary)
            echo bkgt_button()
                ->text(__('Schedule', 'bkgt'))
                ->variant('secondary')
                ->size('medium')
                ->type('submit')
                ->classes('status-action-btn')
                ->attribute('data-status', 'scheduled')
                ->build();
            
            // Draft button (secondary)
            echo bkgt_button()
                ->text(__('Draft', 'bkgt'))
                ->variant('secondary')
                ->size('medium')
                ->type('submit')
                ->classes('status-action-btn')
                ->attribute('data-status', 'draft')
                ->build();
            
            // Trash button (danger)
            echo bkgt_button()
                ->text(__('Trash', 'bkgt'))
                ->variant('danger')
                ->size('medium')
                ->type('submit')
                ->classes('status-action-btn')
                ->attribute('data-status', 'trash')
                ->build();
        }
        ?>
    </div>
</div>

<?php

/**
 * ============================================================================
 * EXAMPLE 8: Button Size Variations
 * ============================================================================
 * 
 * Show all available button sizes for admin context.
 */

?>
<!-- EXAMPLE 8: Button Size Variations -->
<div class="button-sizes">
    <h3><?php esc_html_e('Button Sizes', 'bkgt'); ?></h3>
    
    <?php
    $sizes = ['small', 'medium', 'large'];
    
    foreach ($sizes as $size) {
        if (function_exists('bkgt_button')) {
            echo bkgt_button()
                ->text(ucfirst($size) . ' Button')
                ->variant('primary')
                ->size($size)
                ->classes('size-demo-btn')
                ->build();
            echo ' ';
        }
    }
    ?>
</div>

<?php

/**
 * ============================================================================
 * EXAMPLE 9: Button State Variations
 * ============================================================================
 * 
 * Show disabled, loading, and other state variations.
 */

?>
<!-- EXAMPLE 9: Button State Variations -->
<div class="button-states">
    <h3><?php esc_html_e('Button States', 'bkgt'); ?></h3>
    
    <?php
    if (function_exists('bkgt_button')) {
        // Normal button
        echo bkgt_button()
            ->text(__('Normal', 'bkgt'))
            ->variant('primary')
            ->size('medium')
            ->build();
        echo ' ';
        
        // Disabled button
        echo bkgt_button()
            ->text(__('Disabled', 'bkgt'))
            ->variant('primary')
            ->size('medium')
            ->disabled(true)
            ->build();
        echo ' ';
        
        // Loading button (with data attribute for JS)
        echo bkgt_button()
            ->text(__('Loading', 'bkgt'))
            ->variant('primary')
            ->size('medium')
            ->classes('loading-btn')
            ->attribute('data-loading-text', __('Processing...', 'bkgt'))
            ->build();
    }
    ?>
</div>

<?php

/**
 * ============================================================================
 * EXAMPLE 10: Icon Button Pattern
 * ============================================================================
 * 
 * Common use case: Buttons with icons for common actions.
 */

?>
<!-- EXAMPLE 10: Icon Buttons -->
<div class="icon-buttons">
    <h3><?php esc_html_e('Icon Buttons', 'bkgt'); ?></h3>
    
    <?php
    if (function_exists('bkgt_button')) {
        // Edit with icon
        echo bkgt_button()
            ->text('✏️ Edit')
            ->variant('info')
            ->size('medium')
            ->build();
        echo ' ';
        
        // Delete with icon
        echo bkgt_button()
            ->text('🗑️ Delete')
            ->variant('danger')
            ->size('medium')
            ->build();
        echo ' ';
        
        // Save with icon
        echo bkgt_button()
            ->text('💾 Save')
            ->variant('primary')
            ->size('medium')
            ->build();
        echo ' ';
        
        // Settings with icon
        echo bkgt_button()
            ->text('⚙️ Settings')
            ->variant('secondary')
            ->size('medium')
            ->build();
    }
    ?>
</div>

<?php
/**
 * ============================================================================
 * STYLING GUIDE
 * ============================================================================
 */
?>
<style>
    /* Button group spacing */
    .button-group {
        margin: 1.5rem 0;
    }

    .button-group label {
        display: block;
        margin-bottom: 0.75rem;
        font-weight: 600;
    }

    /* Horizontal button groups */
    .button-group-horizontal {
        display: flex;
        gap: 0.75rem;
        flex-wrap: wrap;
        align-items: center;
    }

    /* Button spacing */
    .button-group .bkgt-button {
        margin-right: 0.5rem;
        margin-bottom: 0.5rem;
    }

    /* Plugin/theme card layouts */
    .plugin-item,
    .theme-card {
        padding: 1.5rem;
        background: white;
        border: 1px solid #e0e0e0;
        border-radius: 4px;
        margin-bottom: 1rem;
    }

    .plugin-actions,
    .theme-actions,
    .user-actions {
        display: flex;
        gap: 0.5rem;
        margin-top: 1rem;
        flex-wrap: wrap;
    }

    /* Bulk actions */
    .bulk-actions {
        margin: 1rem 0;
        padding: 1rem;
        background: #f5f5f5;
        border-radius: 4px;
    }

    .bulk-actions label {
        margin-right: 0.75rem;
        font-weight: 600;
    }

    .bulk-actions select {
        margin-right: 1rem;
    }

    /* State demonstrations */
    .button-sizes,
    .button-states,
    .icon-buttons {
        margin: 2rem 0;
        padding: 1.5rem;
        background: #f9f9f9;
        border-radius: 4px;
    }

    .button-sizes h3,
    .button-states h3,
    .icon-buttons h3 {
        margin-top: 0;
        margin-bottom: 1rem;
    }

    .button-sizes .bkgt-button,
    .button-states .bkgt-button,
    .icon-buttons .bkgt-button {
        margin-right: 1rem;
        margin-bottom: 0.5rem;
    }

    /* Themes grid */
    .themes-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 1.5rem;
    }

    .theme-card {
        display: flex;
        flex-direction: column;
    }

    .theme-actions {
        margin-top: auto;
    }
</style>

<?php
/**
 * ============================================================================
 * USAGE NOTES
 * ============================================================================
 * 
 * 1. Always check if bkgt_button() exists before using it
 * 2. Provide fallback HTML for compatibility
 * 3. Use appropriate variants for action context:
 *    - primary: Main/important action
 *    - secondary: Alternative/less common action
 *    - danger: Destructive action (delete, trash)
 *    - info: Informational/settings action
 *    - success: Successful/positive action
 *    - warning: Warning/attention needed action
 * 4. Use size appropriately:
 *    - small: Inline tables, compact layouts
 *    - medium: Normal buttons in forms
 *    - large: Call-to-action buttons
 * 5. Always provide clear button text for accessibility
 * 6. Use data attributes for JavaScript hooks
 * 7. Maintain consistent spacing between button groups
 * 8. Test in mobile/responsive layouts
 * 9. Ensure proper nonce handling for security
 * 10. Use current_user_can() for capability checks
 * 
 * ============================================================================
 */
