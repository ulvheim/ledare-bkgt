<?php
/**
 * Template Name: BKGT Players
 * Description: Page template for displaying BKGT players
 *
 * @package BKGT_Ledare
 * @since 1.0.0
 */

get_header(); ?>

<div class="content-container">
    <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
        <header class="content-header">
            <h1><?php the_title(); ?></h1>
        </header>

        <div class="card">
            <div class="card-body">
                <?php the_content(); ?>

                <div class="bkgt-section">
                    <h2>Våra Spelare</h2>
                    <?php echo do_shortcode('[bkgt_players show_filters="true" layout="grid"]'); ?>
                </div>
            </div>
        </div>
    </article>
</div>

<?php get_footer(); ?>