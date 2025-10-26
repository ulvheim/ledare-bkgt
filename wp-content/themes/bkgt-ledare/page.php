<?php
/**
 * Template for displaying pages
 *
 * @package BKGT_Ledare
 * @since 1.0.0
 */

get_header(); ?>

<div class="content-container">
    <?php while (have_posts()): the_post(); ?>
        <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
            <header class="content-header">
                <h1><?php the_title(); ?></h1>
            </header>

            <div class="card">
                <div class="card-body">
                    <?php the_content(); ?>

                    <?php
                    // Add BKGT shortcodes based on page slug
                    $page_slug = get_post_field('post_name', get_post());

                    if ($page_slug === 'spelare' || strpos(strtolower(get_the_title()), 'spelare') !== false) {
                        echo '<div class="bkgt-section">';
                        echo '<h2>Våra Spelare</h2>';
                        echo do_shortcode('[bkgt_players show_filters="true" layout="grid"]');
                        echo '</div>';
                    } elseif ($page_slug === 'matcher' || $page_slug === 'event' || strpos(strtolower(get_the_title()), 'match') !== false || strpos(strtolower(get_the_title()), 'event') !== false) {
                        echo '<div class="bkgt-section">';
                        echo '<h2>Kommande Matcher & Event</h2>';
                        echo do_shortcode('[bkgt_events upcoming="true" limit="10" layout="list"]');
                        echo '</div>';
                    } elseif ($page_slug === 'lagoversikt' || strpos(strtolower(get_the_title()), 'lag') !== false || strpos(strtolower(get_the_title()), 'översikt') !== false) {
                        echo '<div class="bkgt-section">';
                        echo '<h2>Lagöversikt & Statistik</h2>';
                        echo do_shortcode('[bkgt_team_overview show_stats="true" show_upcoming="true"]');
                        echo '</div>';
                    }
                    ?>
                </div>
            </div>
        </article>
    <?php endwhile; ?>
</div>

<?php get_footer(); ?>