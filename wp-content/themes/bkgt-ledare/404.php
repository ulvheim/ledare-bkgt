<?php
/**
 * 404 Error Page Template
 * 
 * @package BKGT_Ledare
 * @since 1.0.0
 */

get_header(); ?>

<div class="content-container">
    <div class="card">
        <div class="card-body text-center">
            <h1 style="font-size: 4rem; margin-bottom: 1rem;">404</h1>
            <h2><?php esc_html_e('Sidan kunde inte hittas', 'bkgt-ledare'); ?></h2>
            <p class="text-muted"><?php esc_html_e('Den sida du letar efter finns tyvärr inte.', 'bkgt-ledare'); ?></p>
            <a href="<?php echo esc_url(home_url('/')); ?>" class="btn btn-primary">
                <?php esc_html_e('Tillbaka till Dashboard', 'bkgt-ledare'); ?>
            </a>
        </div>
    </div>
</div>

<?php get_footer(); ?>
