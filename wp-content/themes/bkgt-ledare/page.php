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
                
                <!-- Breadcrumb Navigation -->
                <nav class="breadcrumb-nav" aria-label="Breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item">
                            <a href="<?php echo home_url(); ?>">Dashboard</a>
                        </li>
                        <?php
                        $page_slug = get_post_field('post_name', get_post());
                        $page_title = get_the_title();
                        
                        // Add intermediate breadcrumb based on page type
                        if ($page_slug === 'spelare' || strpos(strtolower($page_title), 'spelare') !== false) {
                            echo '<li class="breadcrumb-item"><a href="' . home_url() . '">Spelare</a></li>';
                        } elseif ($page_slug === 'lag' || strpos(strtolower($page_title), 'lag') !== false || strpos(strtolower($page_title), 'översikt') !== false) {
                            echo '<li class="breadcrumb-item"><a href="' . home_url('/lag') . '">Lag</a></li>';
                        } elseif ($page_slug === 'dokument' || strpos(strtolower($page_title), 'dokument') !== false) {
                            echo '<li class="breadcrumb-item"><a href="' . home_url('/?page_id=16') . '">Dokument</a></li>';
                        } elseif ($page_slug === 'utrustning' || strpos(strtolower($page_title), 'utrustning') !== false) {
                            echo '<li class="breadcrumb-item"><a href="' . home_url('/?page_id=15') . '">Utrustning</a></li>';
                        } elseif ($page_slug === 'kommunikation' || strpos(strtolower($page_title), 'kommunikation') !== false) {
                            echo '<li class="breadcrumb-item"><a href="' . home_url('/?page_id=17') . '">Kommunikation</a></li>';
                        } elseif ($page_slug === 'utvardering' || strpos(strtolower($page_title), 'utvärdering') !== false) {
                            echo '<li class="breadcrumb-item"><a href="' . home_url('/utvardering') . '">Konfidentiellt</a></li>';
                        }
                        ?>
                        <li class="breadcrumb-item active" aria-current="page"><?php the_title(); ?></li>
                    </ol>
                </nav>
            </header>

            <div class="card">
                <div class="card-body">
                    <?php 
                    // Only show the_content() if it's not an utrustning page
                    $page_slug = get_post_field('post_name', get_post());
                    if (!($page_slug === 'utrustning' || strpos(strtolower(get_the_title()), 'utrustning') !== false)) {
                        the_content();
                    }
                    ?>

                    <?php
                    // Add BKGT shortcodes based on page slug

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
                    } elseif ($page_slug === 'dokument' || strpos(strtolower(get_the_title()), 'dokument') !== false) {
                        // Document content is already in the page content, don't add duplicate
                        // echo '<div class="bkgt-section">';
                        // echo '<h2>Klubbens Dokument</h2>';
                        // echo do_shortcode('[bkgt_documents]');
                        // echo '</div>';
                    } elseif ($page_slug === 'kommunikation' || strpos(strtolower(get_the_title()), 'kommunikation') !== false) {
                        echo '<div class="bkgt-section">';
                        echo '<h2>Intern Kommunikation</h2>';
                        echo do_shortcode('[bkgt_communication]');
                        echo '</div>';
                    } elseif ($page_slug === 'utrustning' || strpos(strtolower(get_the_title()), 'utrustning') !== false) {
                        echo '<div class="bkgt-section">';
                        echo '<h2>Utrustning & Inventarier</h2>';
                        echo do_shortcode('[bkgt_inventory]');
                        echo '</div>';
                    }
                    ?>
                </div>
            </div>
        </article>
    <?php endwhile; ?>
</div>

<?php get_footer(); ?>