<?php
/**
 * Template Name: Events
 * Description: Displays upcoming matches and events for BKGT
 */

get_header();

// Check user permissions
if (!is_user_logged_in()) {
    echo '<div class="bkgt-restricted-content">';
    echo '<p>' . __('Du måste vara inloggad för att se denna sida.', 'bkgt-team-player') . '</p>';
    echo '<p><a href="' . wp_login_url(get_permalink()) . '" class="button">' . __('Logga in', 'bkgt-team-player') . '</a></p>';
    echo '</div>';
    get_footer();
    exit;
}

$current_user = wp_get_current_user();
$user_roles = $current_user->roles;
$allowed_roles = array('administrator', 'styrelsemedlem', 'tränare', 'lagledare');

// Check if user has appropriate role
if (!array_intersect($user_roles, $allowed_roles)) {
    echo '<div class="bkgt-restricted-content">';
    echo '<p>' . __('Du har inte behörighet att se denna sida.', 'bkgt-team-player') . '</p>';
    echo '</div>';
    get_footer();
    exit;
}

?>

<div class="bkgt-events-page">
    <div class="bkgt-container">

        <header class="bkgt-page-header">
            <h1><?php _e('Matcher & Event', 'bkgt-team-player'); ?></h1>
            <p><?php _e('Kommande matcher och event för BKGT', 'bkgt-team-player'); ?></p>
        </header>

        <div class="bkgt-events-content">
            <?php echo do_shortcode('[bkgt_team_page]'); ?>
        </div>

    </div>
</div>

<style>
.bkgt-events-page {
    padding: 20px 0;
    background: #f8f9fa;
    min-height: 60vh;
}

.bkgt-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 20px;
}

.bkgt-page-header {
    text-align: center;
    margin-bottom: 40px;
}

.bkgt-page-header h1 {
    color: #2c3e50;
    font-size: 2.5em;
    margin-bottom: 10px;
}

.bkgt-page-header p {
    color: #7f8c8d;
    font-size: 1.2em;
}

.bkgt-events-content {
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    padding: 30px;
}

.bkgt-restricted-content {
    text-align: center;
    padding: 50px 20px;
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    max-width: 500px;
    margin: 50px auto;
}

.bkgt-restricted-content p {
    font-size: 1.1em;
    margin-bottom: 20px;
}

.bkgt-restricted-content .button {
    display: inline-block;
    padding: 12px 24px;
    background: #007cba;
    color: #fff;
    text-decoration: none;
    border-radius: 4px;
    font-weight: 500;
}

.bkgt-restricted-content .button:hover {
    background: #005a87;
    color: #fff;
}
</style>

<?php get_footer(); ?>