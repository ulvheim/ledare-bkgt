<?php
/**
 * Main template file
 * 
 * @package BKGT_Ledare
 * @since 1.0.0
 */

get_header(); ?>

<div class="content-container">
    <div class="content-header">
        <h1><?php esc_html_e('Dashboard', 'bkgt-ledare'); ?></h1>
        <p class="text-muted"><?php esc_html_e('Välkommen till BKGTS Ledarsystem', 'bkgt-ledare'); ?></p>
    </div>
    
    <div class="grid grid-3">
        <!-- Quick Stats Card -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><?php esc_html_e('Snabbstatistik', 'bkgt-ledare'); ?></h3>
            </div>
            <div class="card-body">
                <p class="text-muted"><?php esc_html_e('Statistik kommer snart...', 'bkgt-ledare'); ?></p>
            </div>
        </div>
        
        <!-- Recent Activity Card -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><?php esc_html_e('Senaste aktivitet', 'bkgt-ledare'); ?></h3>
            </div>
            <div class="card-body">
                <p class="text-muted"><?php esc_html_e('Ingen aktivitet att visa', 'bkgt-ledare'); ?></p>
            </div>
        </div>
        
        <!-- Notifications Card -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><?php esc_html_e('Notifikationer', 'bkgt-ledare'); ?></h3>
            </div>
            <div class="card-body">
                <p class="text-muted"><?php esc_html_e('Inga nya notifikationer', 'bkgt-ledare'); ?></p>
            </div>
        </div>
    </div>
    
    <?php if (bkgt_can_view_performance_data()): ?>
    <!-- Performance Data Section (Only for authorized users) -->
    <div class="card mt-3">
        <div class="card-header">
            <h3 class="card-title"><?php esc_html_e('Utvärdering', 'bkgt-ledare'); ?></h3>
        </div>
        <div class="card-body">
            <p class="text-muted"><?php esc_html_e('Utvärderingsdata kommer att visas här', 'bkgt-ledare'); ?></p>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- My Teams Section -->
    <?php 
    $user_teams = bkgt_get_user_teams();
    if (!empty($user_teams)): 
    ?>
    <div class="card mt-3">
        <div class="card-header">
            <h3 class="card-title"><?php esc_html_e('Mina Lag', 'bkgt-ledare'); ?></h3>
        </div>
        <div class="card-body">
            <p class="text-muted"><?php esc_html_e('Dina lag kommer att visas här', 'bkgt-ledare'); ?></p>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php get_footer(); ?>
