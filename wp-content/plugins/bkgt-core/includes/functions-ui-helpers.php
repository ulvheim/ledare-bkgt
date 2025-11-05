<?php
/**
 * BKGT UI Helper Functions
 * 
 * Common UI rendering functions used across BKGT plugins
 * 
 * @package BKGT_Core
 * @subpackage UI
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Render a professional empty state message
 * 
 * Used when no data is available to display. Shows a friendly message
 * with optional action buttons.
 * 
 * @param array $args {
 *     Optional. Array of configuration options.
 *     
 *     @type string $icon       Emoji or icon HTML. Default: '📭'
 *     @type string $title      Main heading text. Default: 'No data available'
 *     @type string $message    Description text. Default: empty string
 *     @type array  $actions    Array of action buttons. Default: empty array
 *                              Each action array contains:
 *                              - label (string): Button text
 *                              - url (string): Link URL
 *                              - primary (bool): True for primary button style
 *     @type string $class      Additional CSS class. Default: empty string
 * }
 * 
 * @return string HTML output for empty state
 * 
 * @since 1.0.0
 */
function bkgt_render_empty_state($args = array()) {
    $defaults = array(
        'icon' => '📭',
        'title' => __('No data available', 'bkgt-core'),
        'message' => '',
        'actions' => array(),
        'class' => ''
    );
    
    $args = wp_parse_args($args, $defaults);
    
    // Sanitize arguments
    $title = sanitize_text_field($args['title']);
    $message = wp_kses_post($args['message']);
    $icon = !empty($args['icon']) ? $args['icon'] : '📭';
    $class = sanitize_html_class($args['class']);
    
    // Start HTML output
    $output = '<div class="bkgt-empty-state ' . esc_attr($class) . '">';
    
    // Icon
    $output .= '<div class="bkgt-empty-state__icon">' . $icon . '</div>';
    
    // Title
    $output .= '<h3 class="bkgt-empty-state__title">' . esc_html($title) . '</h3>';
    
    // Message
    if (!empty($message)) {
        $output .= '<p class="bkgt-empty-state__message">' . $message . '</p>';
    }
    
    // Action buttons
    if (!empty($args['actions']) && is_array($args['actions'])) {
        $output .= '<div class="bkgt-empty-state__actions">';
        
        foreach ($args['actions'] as $action) {
            if (empty($action['label']) || empty($action['url'])) {
                continue;
            }
            
            $button_class = isset($action['primary']) && $action['primary'] 
                ? 'button button-primary' 
                : 'button';
            
            $output .= sprintf(
                '<a href="%s" class="%s">%s</a>',
                esc_url($action['url']),
                esc_attr($button_class),
                esc_html($action['label'])
            );
        }
        
        $output .= '</div>';
    }
    
    $output .= '</div>';
    
    return $output;
}

/**
 * Get CSS for empty state component
 * 
 * Returns inline CSS for the empty state styling.
 * Can be enqueued via wp_add_inline_style() or printed with wp_head hook.
 * 
 * @return string CSS rules
 * 
 * @since 1.0.0
 */
function bkgt_get_empty_state_css() {
    return <<<CSS
        .bkgt-empty-state {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 60px 20px;
            text-align: center;
            background: linear-gradient(135deg, var(--color-bg-secondary, #f8f9fa) 0%, var(--color-bg-tertiary, #f1f3f5) 100%);
            border: 2px dashed var(--color-border-light, #e9ecef);
            border-radius: 8px;
            min-height: 300px;
            gap: 15px;
            color: var(--color-text-secondary, #646970);
        }

        .bkgt-empty-state__icon {
            font-size: 64px;
            line-height: 1;
            margin-bottom: 10px;
        }

        .bkgt-empty-state__title {
            margin: 0;
            font-size: 20px;
            font-weight: 600;
            color: var(--color-text-primary, #1d2327);
        }

        .bkgt-empty-state__message {
            margin: 10px 0 0 0;
            font-size: 14px;
            max-width: 400px;
            color: var(--color-text-secondary, #646970);
        }

        .bkgt-empty-state__actions {
            display: flex;
            gap: 10px;
            justify-content: center;
            flex-wrap: wrap;
            margin-top: 15px;
        }

        .bkgt-empty-state__actions .button {
            font-size: 13px;
            padding: 6px 12px;
            text-decoration: none;
        }

        .bkgt-empty-state__actions .button-primary {
            background-color: var(--color-primary, #0056b3);
            color: white;
            border-color: var(--color-primary, #0056b3);
        }

        .bkgt-empty-state__actions .button-primary:hover {
            background-color: var(--color-primary-dark, #003d82);
            border-color: var(--color-primary-dark, #003d82);
        }

        /* Mobile responsive */
        @media (max-width: 600px) {
            .bkgt-empty-state {
                padding: 40px 15px;
                min-height: 250px;
            }

            .bkgt-empty-state__icon {
                font-size: 48px;
            }

            .bkgt-empty-state__title {
                font-size: 18px;
            }

            .bkgt-empty-state__message {
                font-size: 13px;
            }

            .bkgt-empty-state__actions {
                flex-direction: column;
            }

            .bkgt-empty-state__actions .button {
                width: 100%;
            }
        }
CSS;
}

/**
 * Render a loading state skeleton
 * 
 * Shows a shimmer animation while content is loading
 * 
 * @param array $args {
 *     Optional configuration
 *     
 *     @type int    $count      Number of skeleton items. Default: 3
 *     @type string $class      Additional CSS class. Default: empty
 * }
 * 
 * @return string HTML output
 * 
 * @since 1.0.0
 */
function bkgt_render_skeleton($args = array()) {
    $defaults = array(
        'count' => 3,
        'class' => ''
    );
    
    $args = wp_parse_args($args, $defaults);
    $count = absint($args['count']);
    $class = sanitize_html_class($args['class']);
    
    $output = '<div class="bkgt-skeleton-loader ' . esc_attr($class) . '">';
    
    for ($i = 0; $i < $count; $i++) {
        $output .= '<div class="bkgt-skeleton-item">';
        $output .= '<div class="bkgt-skeleton-line bkgt-skeleton-title"></div>';
        $output .= '<div class="bkgt-skeleton-line bkgt-skeleton-text"></div>';
        $output .= '<div class="bkgt-skeleton-line bkgt-skeleton-text"></div>';
        $output .= '</div>';
    }
    
    $output .= '</div>';
    
    return $output;
}

/**
 * Get CSS for skeleton loading state
 * 
 * @return string CSS rules
 * 
 * @since 1.0.0
 */
function bkgt_get_skeleton_css() {
    return <<<CSS
        @keyframes bkgt-skeleton-shimmer {
            0% {
                background-position: -1000px 0;
            }
            100% {
                background-position: 1000px 0;
            }
        }

        .bkgt-skeleton-loader {
            display: grid;
            gap: 20px;
        }

        .bkgt-skeleton-item {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .bkgt-skeleton-line {
            height: 12px;
            background: linear-gradient(
                90deg,
                var(--color-bg-secondary, #f0f0f0) 25%,
                var(--color-bg-tertiary, #e0e0e0) 50%,
                var(--color-bg-secondary, #f0f0f0) 75%
            );
            background-size: 1000px 100%;
            animation: bkgt-skeleton-shimmer 2s infinite;
            border-radius: 4px;
        }

        .bkgt-skeleton-title {
            height: 16px;
            width: 60%;
        }

        .bkgt-skeleton-text {
            width: 100%;
        }

        .bkgt-skeleton-text:last-child {
            width: 80%;
        }
CSS;
}

/**
 * Render an error message box
 * 
 * Professional error display with icon and optional action
 * 
 * @param array $args {
 *     Optional configuration
 *     
 *     @type string $message      Error message (required)
 *     @type string $action_label Button text. Default: empty
 *     @type string $action_url   Button URL. Default: empty
 *     @type string $class        Additional CSS class. Default: empty
 *     @type bool   $dismissible  Show close button. Default: false
 * }
 * 
 * @return string HTML output
 * 
 * @since 1.0.0
 */
function bkgt_render_error($args = array()) {
    $defaults = array(
        'message' => __('An error occurred', 'bkgt-core'),
        'action_label' => '',
        'action_url' => '',
        'class' => '',
        'dismissible' => false
    );
    
    $args = wp_parse_args($args, $defaults);
    
    $message = wp_kses_post($args['message']);
    $class = sanitize_html_class($args['class']);
    $dismissible = (bool) $args['dismissible'];
    
    $output = '<div class="bkgt-error-box ' . esc_attr($class) . '" role="alert">';
    
    if ($dismissible) {
        $output .= '<button class="bkgt-error-close" aria-label="' . esc_attr__('Close', 'bkgt-core') . '">&times;</button>';
    }
    
    $output .= '<div class="bkgt-error-content">';
    $output .= '<strong>⚠️ ' . esc_html__('Error:', 'bkgt-core') . '</strong> ' . $message;
    
    if (!empty($args['action_label']) && !empty($args['action_url'])) {
        $output .= ' <a href="' . esc_url($args['action_url']) . '" class="bkgt-error-action">' . 
                   esc_html($args['action_label']) . '</a>';
    }
    
    $output .= '</div></div>';
    
    return $output;
}

/**
 * Get CSS for error box
 * 
 * @return string CSS rules
 * 
 * @since 1.0.0
 */
function bkgt_get_error_css() {
    return <<<CSS
        .bkgt-error-box {
            background-color: var(--color-danger-light, #f8d7da);
            border: 1px solid var(--color-danger, #dc3545);
            border-radius: 4px;
            padding: 12px 15px;
            margin-bottom: 15px;
            color: var(--color-danger-text, #721c24);
            font-size: 14px;
            position: relative;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .bkgt-error-box.dismissible {
            padding-right: 35px;
        }

        .bkgt-error-close {
            position: absolute;
            top: 50%;
            right: 10px;
            transform: translateY(-50%);
            background: none;
            border: none;
            font-size: 20px;
            cursor: pointer;
            padding: 0;
            color: var(--color-danger-text, #721c24);
        }

        .bkgt-error-close:hover {
            opacity: 0.7;
        }

        .bkgt-error-action {
            color: var(--color-danger-text, #721c24);
            text-decoration: underline;
            margin-left: 5px;
        }

        .bkgt-error-action:hover {
            color: var(--color-danger-dark, #bd2130);
        }
CSS;
}

/**
 * Enqueue empty state CSS
 * 
 * Hook into wp_enqueue_scripts or admin_enqueue_scripts
 * 
 * @return void
 * 
 * @since 1.0.0
 */
function bkgt_enqueue_empty_state_css() {
    wp_add_inline_style('bkgt-core', bkgt_get_empty_state_css());
    wp_add_inline_style('bkgt-core', bkgt_get_skeleton_css());
    wp_add_inline_style('bkgt-core', bkgt_get_error_css());
}

// Hook to enqueue CSS
add_action('wp_enqueue_scripts', 'bkgt_enqueue_empty_state_css', 15);
add_action('admin_enqueue_scripts', 'bkgt_enqueue_empty_state_css', 15);
