<?php
/**
 * Header template
 * 
 * @package BKGT_Ledare
 * @since 1.0.0
 */
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<div class="site-wrapper">
    <!-- Sidebar Navigation -->
    <aside class="site-sidebar">
        <div class="site-branding">
            <?php if (has_custom_logo()): ?>
                <?php the_custom_logo(); ?>
            <?php else: ?>
                <h1 class="site-title">
                    <a href="<?php echo esc_url(home_url('/')); ?>">
                        <?php bloginfo('name'); ?>
                    </a>
                </h1>
                <p class="site-description"><?php bloginfo('description'); ?></p>
            <?php endif; ?>
        </div>
        
        <nav class="main-navigation" role="navigation">
            <div class="nav-section">
                <h4 class="nav-section-title"><?php esc_html_e('Huvudmeny', 'bkgt-ledare'); ?></h4>
                <ul class="nav-menu">
                    <li>
                        <a href="<?php echo esc_url(home_url('/')); ?>" class="<?php echo is_front_page() ? 'current' : ''; ?>">
                            <?php esc_html_e('Dashboard', 'bkgt-ledare'); ?>
                        </a>
                    </li>
                    <li>
                        <a href="<?php echo esc_url(home_url('/lag')); ?>">
                            <?php esc_html_e('Lag', 'bkgt-ledare'); ?>
                        </a>
                    </li>
                    <li>
                        <a href="<?php echo esc_url(home_url('/spelare')); ?>">
                            <?php esc_html_e('Spelare', 'bkgt-ledare'); ?>
                        </a>
                    </li>
                </ul>
            </div>
            
            <div class="nav-section">
                <h4 class="nav-section-title"><?php esc_html_e('Hantering', 'bkgt-ledare'); ?></h4>
                <ul class="nav-menu">
                    <li>
                        <a href="<?php echo esc_url(home_url('/?page_id=15')); ?>">
                            <?php esc_html_e('Utrustning', 'bkgt-ledare'); ?>
                        </a>
                    </li>
                    <li>
                        <a href="<?php echo esc_url(home_url('/?page_id=16')); ?>">
                            <?php esc_html_e('Dokument', 'bkgt-ledare'); ?>
                        </a>
                    </li>
                    <li>
                        <a href="<?php echo esc_url(home_url('/?page_id=17')); ?>">
                            <?php esc_html_e('Kommunikation', 'bkgt-ledare'); ?>
                        </a>
                    </li>
                </ul>
            </div>
            
            <?php if (bkgt_can_view_performance_data()): ?>
            <div class="nav-section">
                <h4 class="nav-section-title"><?php esc_html_e('Konfidentiellt', 'bkgt-ledare'); ?></h4>
                <ul class="nav-menu">
                    <li>
                        <a href="<?php echo esc_url(home_url('/utvardering')); ?>">
                            <?php esc_html_e('Utvärdering', 'bkgt-ledare'); ?>
                        </a>
                    </li>
                </ul>
            </div>
            <?php endif; ?>
            
            <?php if (current_user_can('manage_options')): ?>
            <div class="nav-section">
                <h4 class="nav-section-title"><?php esc_html_e('Administration', 'bkgt-ledare'); ?></h4>
                <ul class="nav-menu">
                    <li>
                        <a href="<?php echo esc_url(admin_url()); ?>">
                            <?php esc_html_e('WP Admin', 'bkgt-ledare'); ?>
                        </a>
                    </li>
                </ul>
            </div>
            <?php endif; ?>
        </nav>
    </aside>
    
    <!-- Main Content Area -->
    <div class="site-main">
        <!-- Header Bar -->
        <header class="site-header">
            <div class="header-content">
                <h2 class="page-title">
                    <?php 
                    if (is_front_page()) {
                        esc_html_e('Dashboard', 'bkgt-ledare');
                    } else {
                        the_title();
                    }
                    ?>
                </h2>
                
                <div class="user-info">
                    <?php if (is_user_logged_in()): ?>
                        <span class="user-role"><?php echo esc_html(bkgt_get_user_role_label()); ?></span>
                        <span class="user-name"><?php echo esc_html(wp_get_current_user()->display_name); ?></span>
                        <a href="<?php echo esc_url(wp_logout_url(home_url())); ?>" class="btn btn-outline">
                            <?php esc_html_e('Logga ut', 'bkgt-ledare'); ?>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </header>
        
        <!-- Content Area -->
        <main class="site-content">
