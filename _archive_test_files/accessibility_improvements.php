/**
 * Accessibility improvements
 */

/**
 * Add skip links for keyboard navigation
 */
function bkgt_ledare_add_skip_links() {
    echo '<a href="#main" class="skip-link screen-reader-text">Hoppa till huvudinnehåll</a>';
    echo '<a href="#navigation" class="skip-link screen-reader-text">Hoppa till navigation</a>';
}
add_action('wp_body_open', 'bkgt_ledare_add_skip_links');

/**
 * Improve navigation accessibility
 */
function bkgt_ledare_navigation_attributes($attributes, $item, $args) {
    // Add ARIA labels to menu items
    if ($args->theme_location === 'primary') {
        $attributes['aria-label'] = !empty($item->title) ? $item->title : 'Menyval';
    }
    return $attributes;
}
add_filter('nav_menu_link_attributes', 'bkgt_ledare_navigation_attributes', 10, 3);

/**
 * Add ARIA labels to form elements
 */
function bkgt_ledare_form_accessibility($field) {
    // Add ARIA labels to login form
    if (strpos($field, 'log') !== false) {
        $field = str_replace('name="log"', 'name="log" aria-label="Användarnamn" autocomplete="username"', $field);
    }
    if (strpos($field, 'pwd') !== false) {
        $field = str_replace('name="pwd"', 'name="pwd" aria-label="Lösenord" autocomplete="current-password"', $field);
    }
    return $field;
}
add_filter('login_form_middle', 'bkgt_ledare_form_accessibility');

/**
 * Add screen reader text for better accessibility
 */
function bkgt_ledare_screen_reader_text($text) {
    return '<span class="screen-reader-text">' . $text . '</span>';
}

/**
 * Improve post content accessibility
 */
function bkgt_ledare_improve_content_accessibility($content) {
    if (!is_admin() && is_singular()) {
        // Add proper heading structure
        $content = preg_replace_callback(
            '/<h([1-6])([^>]*?)>(.*?)<\/h[1-6]>/i',
            function($matches) {
                $level = $matches[1];
                $attributes = $matches[2];
                $text = $matches[3];

                // Ensure proper heading hierarchy
                static $last_level = 0;
                if ($level > $last_level + 1 && $last_level > 0) {
                    $level = $last_level + 1;
                }
                $last_level = $level;

                return "<h{$level}{$attributes} id=\"heading-" . sanitize_title($text) . "\">{$text}</h{$level}>";
            },
            $content
        );
    }
    return $content;
}
add_filter('the_content', 'bkgt_ledare_improve_content_accessibility');

/**
 * Add focus management for modal dialogs
 */
function bkgt_ledare_focus_management() {
    if (!is_admin()) {
        wp_enqueue_script('bkgt-accessibility', get_template_directory_uri() . '/js/accessibility.js', array(), '1.0.0', true);
    }
}
add_action('wp_enqueue_scripts', 'bkgt_ledare_focus_management');

/**
 * Add language attributes for better screen reader support
 */
function bkgt_ledare_language_attributes($output) {
    return $output . ' lang="sv-SE"';
}
add_filter('language_attributes', 'bkgt_ledare_language_attributes');