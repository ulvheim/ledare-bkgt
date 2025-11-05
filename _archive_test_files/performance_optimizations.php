/**
 * Add viewport and responsive meta tags
 */
function bkgt_ledare_add_meta_tags() {
    echo '<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">';
    echo '<meta name="theme-color" content="#1e40af">';
    echo '<meta name="mobile-web-app-capable" content="yes">';
    echo '<meta name="apple-mobile-web-app-capable" content="yes">';
    echo '<meta name="apple-mobile-web-app-status-bar-style" content="default">';
}
add_action('wp_head', 'bkgt_ledare_add_meta_tags');

/**
 * Performance optimizations
 */

/**
 * Add performance headers
 */
function bkgt_ledare_performance_headers() {
    if (!is_admin()) {
        // Cache static assets for 1 year
        if (isset($_SERVER['REQUEST_URI']) &&
            preg_match('/\.(css|js|png|jpg|jpeg|gif|ico|svg|woff|woff2|ttf|eot)$/', $_SERVER['REQUEST_URI'])) {
            header('Cache-Control: public, max-age=31536000');
            header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 31536000) . ' GMT');
        }

        // Enable compression
        if (!headers_sent() && extension_loaded('zlib')) {
            if (substr_count($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip')) {
                ob_start('ob_gzhandler');
            }
        }
    }
}
add_action('init', 'bkgt_ledare_performance_headers');

/**
 * Minify and combine CSS/JS in production
 */
function bkgt_ledare_optimize_assets() {
    if (!defined('WP_DEBUG') || !WP_DEBUG) {
        // Minify CSS output
        add_filter('style_loader_tag', 'bkgt_ledare_minify_css', 10, 4);

        // Minify JS output
        add_filter('script_loader_tag', 'bkgt_ledare_minify_js', 10, 3);
    }
}
add_action('wp_enqueue_scripts', 'bkgt_ledare_optimize_assets', 999);

/**
 * Minify CSS tags
 */
function bkgt_ledare_minify_css($html, $handle, $href, $media) {
    if (strpos($href, 'ver=') !== false) {
        $href = remove_query_arg('ver', $href);
    }
    return str_replace(' />', ' media="' . $media . '" />', $html);
}

/**
 * Minify JS tags
 */
function bkgt_ledare_minify_js($tag, $handle, $src) {
    if (strpos($src, 'ver=') !== false) {
        $src = remove_query_arg('ver', $src);
    }
    return str_replace(' src=', ' defer src=', $tag);
}

/**
 * Remove unnecessary scripts and styles
 */
function bkgt_ledare_remove_unnecessary_assets() {
    // Remove WordPress emoji scripts
    remove_action('wp_head', 'print_emoji_detection_script', 7);
    remove_action('wp_print_styles', 'print_emoji_styles');
    remove_action('admin_print_scripts', 'print_emoji_detection_script');
    remove_action('admin_print_styles', 'print_emoji_styles');

    // Remove unnecessary scripts
    wp_dequeue_script('wp-embed');

    // Remove query strings from static resources
    add_filter('style_loader_src', 'bkgt_ledare_remove_query_strings', 10, 2);
    add_filter('script_loader_src', 'bkgt_ledare_remove_query_strings', 10, 2);
}
add_action('init', 'bkgt_ledare_remove_unnecessary_assets');

/**
 * Remove query strings from static resources
 */
function bkgt_ledare_remove_query_strings($src) {
    if (!is_admin() && strpos($src, 'ver=') !== false) {
        $src = remove_query_arg('ver', $src);
    }
    return $src;
}

/**
 * Optimize database queries
 */
function bkgt_ledare_optimize_queries($query) {
    if (!is_admin() && $query->is_main_query()) {
        // Limit posts per page for better performance
        if ($query->is_home() || $query->is_archive()) {
            $query->set('posts_per_page', 12);
        }

        // Disable unnecessary post data
        $query->set('no_found_rows', true);
        $query->set('update_post_meta_cache', false);
        $query->set('update_post_term_cache', false);
    }
    return $query;
}
add_action('pre_get_posts', 'bkgt_ledare_optimize_queries');

/**
 * Add preload hints for critical resources
 */
function bkgt_ledare_add_preload_hints() {
    if (!is_admin()) {
        // Preload main stylesheet
        echo '<link rel="preload" href="' . get_stylesheet_uri() . '" as="style" onload="this.onload=null;this.rel=\'stylesheet\'">';

        // Preload main JavaScript
        echo '<link rel="preload" href="' . get_template_directory_uri() . '/js/main.js" as="script">';

        // Preconnect to external domains if needed
        // echo '<link rel="preconnect" href="https://fonts.googleapis.com">';
        // echo '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>';
    }
}
add_action('wp_head', 'bkgt_ledare_add_preload_hints', 1);

/**
 * Lazy load images
 */
function bkgt_ledare_lazy_load_images($content) {
    if (!is_admin() && !is_feed()) {
        $content = preg_replace_callback(
            '/<img([^>]+?)src=(["\'])([^"\']+)(["\'])([^>]*?)>/i',
            function($matches) {
                $img_tag = $matches[0];
                $attributes = $matches[1] . $matches[5];

                // Skip if already has loading attribute
                if (strpos($attributes, 'loading=') !== false) {
                    return $img_tag;
                }

                // Add lazy loading
                return str_replace('<img', '<img loading="lazy"', $img_tag);
            },
            $content
        );
    }
    return $content;
}
add_filter('the_content', 'bkgt_ledare_lazy_load_images');
add_filter('post_thumbnail_html', 'bkgt_ledare_lazy_load_images');
add_filter('get_avatar', 'bkgt_ledare_lazy_load_images');