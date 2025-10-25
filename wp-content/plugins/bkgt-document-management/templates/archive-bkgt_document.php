<?php
/**
 * Document Archive Template
 *
 * @package BKGT_Document_Management
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

get_header();
?>

<div class="bkgt-documents-archive">
    <div class="bkgt-container">

        <header class="bkgt-archive-header">
            <h1><?php _e('Dokument', 'bkgt-document-management'); ?></h1>

            <?php if (current_user_can('edit_documents')): ?>
                <a href="<?php echo admin_url('post-new.php?post_type=bkgt_document'); ?>" class="bkgt-button-primary">
                    <?php _e('Ladda upp dokument', 'bkgt-document-management'); ?>
                </a>
            <?php endif; ?>
        </header>

        <div class="bkgt-documents-content">

            <!-- Categories Filter -->
            <aside class="bkgt-documents-sidebar">
                <div class="bkgt-categories-filter">
                    <h3><?php _e('Kategorier', 'bkgt-document-management'); ?></h3>
                    <?php
                    $categories = BKGT_Document_Category::get_hierarchy();
                    if (!empty($categories)):
                    ?>
                        <ul class="bkgt-category-list">
                            <li class="bkgt-category-item">
                                <a href="<?php echo get_post_type_archive_link('bkgt_document'); ?>" class="<?php echo !is_tax() ? 'active' : ''; ?>">
                                    <?php _e('Alla dokument', 'bkgt-document-management'); ?>
                                </a>
                            </li>
                            <?php foreach ($categories as $category): ?>
                                <li class="bkgt-category-item">
                                    <a href="<?php echo get_term_link($category['term_id'], 'bkgt_doc_category'); ?>" class="<?php echo is_tax('bkgt_doc_category', $category['term_id']) ? 'active' : ''; ?>">
                                        <?php echo esc_html($category['name']); ?>
                                    </a>
                                    <?php if (!empty($category['children'])): ?>
                                        <ul class="bkgt-subcategory-list">
                                            <?php foreach ($category['children'] as $child): ?>
                                                <li class="bkgt-subcategory-item">
                                                    <a href="<?php echo get_term_link($child['term_id'], 'bkgt_doc_category'); ?>" class="<?php echo is_tax('bkgt_doc_category', $child['term_id']) ? 'active' : ''; ?>">
                                                        <?php echo esc_html($child['name']); ?>
                                                    </a>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php endif; ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>

                <!-- Search -->
                <div class="bkgt-search-filter">
                    <h3><?php _e('Sök', 'bkgt-document-management'); ?></h3>
                    <form method="get" action="<?php echo get_post_type_archive_link('bkgt_document'); ?>">
                        <input type="text" name="s" value="<?php echo get_search_query(); ?>" placeholder="<?php esc_attr_e('Sök dokument...', 'bkgt-document-management'); ?>">
                        <button type="submit" class="bkgt-button"><?php _e('Sök', 'bkgt-document-management'); ?></button>
                    </form>
                </div>
            </aside>

            <!-- Documents List -->
            <main class="bkgt-documents-main">

                <?php if (have_posts()): ?>

                    <div class="bkgt-documents-header">
                        <div class="bkgt-view-toggle">
                            <button class="bkgt-view-grid active" data-view="grid">
                                <span class="dashicons dashicons-grid-view"></span>
                            </button>
                            <button class="bkgt-view-list" data-view="list">
                                <span class="dashicons dashicons-list-view"></span>
                            </button>
                        </div>

                        <div class="bkgt-sort-options">
                            <select id="bkgt-sort-documents">
                                <option value="date_desc" <?php selected(isset($_GET['orderby']) && $_GET['orderby'] === 'date_desc'); ?>>
                                    <?php _e('Senaste först', 'bkgt-document-management'); ?>
                                </option>
                                <option value="date_asc" <?php selected(isset($_GET['orderby']) && $_GET['orderby'] === 'date_asc'); ?>>
                                    <?php _e('Äldsta först', 'bkgt-document-management'); ?>
                                </option>
                                <option value="title_asc" <?php selected(isset($_GET['orderby']) && $_GET['orderby'] === 'title_asc'); ?>>
                                    <?php _e('Titel A-Ö', 'bkgt-document-management'); ?>
                                </option>
                                <option value="title_desc" <?php selected(isset($_GET['orderby']) && $_GET['orderby'] === 'title_desc'); ?>>
                                    <?php _e('Titel Ö-A', 'bkgt-document-management'); ?>
                                </option>
                            </select>
                        </div>
                    </div>

                    <div class="bkgt-documents-grid" id="bkgt-documents-container">

                        <?php while (have_posts()): the_post(); ?>
                            <?php
                            $document = new BKGT_Document(get_the_ID());
                            $can_view = BKGT_Document_Access::user_has_access(get_the_ID());
                            $can_edit = BKGT_Document_Access::user_has_access(get_the_ID(), null, BKGT_Document_Access::ACCESS_EDIT);
                            ?>

                            <article class="bkgt-document-card <?php echo $can_view ? 'accessible' : 'restricted'; ?>" data-document-id="<?php the_ID(); ?>">

                                <div class="bkgt-document-icon">
                                    <?php echo $this->get_file_icon($document->get_mime_type()); ?>
                                </div>

                                <div class="bkgt-document-info">
                                    <h3 class="bkgt-document-title">
                                        <?php if ($can_view): ?>
                                            <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                                        <?php else: ?>
                                            <?php the_title(); ?>
                                        <?php endif; ?>
                                    </h3>

                                    <div class="bkgt-document-meta">
                                        <span class="bkgt-document-size"><?php echo $document->get_formatted_file_size(); ?></span>
                                        <span class="bkgt-document-date"><?php echo get_the_date(); ?></span>
                                    </div>

                                    <div class="bkgt-document-categories">
                                        <?php
                                        $categories = get_the_terms(get_the_ID(), 'bkgt_doc_category');
                                        if ($categories):
                                            foreach ($categories as $category):
                                                echo '<span class="bkgt-category-tag">' . esc_html($category->name) . '</span>';
                                            endforeach;
                                        endif;
                                        ?>
                                    </div>

                                    <?php if ($can_view): ?>
                                        <div class="bkgt-document-actions">
                                            <a href="<?php echo $this->get_download_url(get_the_ID()); ?>" class="bkgt-button-secondary bkgt-download-link">
                                                <?php _e('Ladda ner', 'bkgt-document-management'); ?>
                                            </a>
                                            <?php if ($can_edit): ?>
                                                <a href="<?php echo get_edit_post_link(); ?>" class="bkgt-button-secondary">
                                                    <?php _e('Redigera', 'bkgt-document-management'); ?>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    <?php else: ?>
                                        <div class="bkgt-document-restricted">
                                            <span class="bkgt-restricted-notice"><?php _e('Begränsad åtkomst', 'bkgt-document-management'); ?></span>
                                        </div>
                                    <?php endif; ?>
                                </div>

                            </article>

                        <?php endwhile; ?>

                    </div>

                    <!-- Pagination -->
                    <div class="bkgt-pagination">
                        <?php
                        echo paginate_links(array(
                            'prev_text' => __('&laquo; Föregående', 'bkgt-document-management'),
                            'next_text' => __('Nästa &raquo;', 'bkgt-document-management'),
                        ));
                        ?>
                    </div>

                <?php else: ?>

                    <div class="bkgt-no-documents">
                        <div class="bkgt-no-documents-icon">
                            <span class="dashicons dashicons-media-document"></span>
                        </div>
                        <h3><?php _e('Inga dokument hittades', 'bkgt-document-management'); ?></h3>
                        <p><?php _e('Det finns inga dokument som matchar dina kriterier.', 'bkgt-document-management'); ?></p>

                        <?php if (current_user_can('edit_documents')): ?>
                            <a href="<?php echo admin_url('post-new.php?post_type=bkgt_document'); ?>" class="bkgt-button-primary">
                                <?php _e('Ladda upp första dokumentet', 'bkgt-document-management'); ?>
                            </a>
                        <?php endif; ?>
                    </div>

                <?php endif; ?>

            </main>

        </div>

    </div>
</div>

<?php get_footer(); ?>

<?php
/**
 * Get file icon based on MIME type
 */
function get_file_icon($mime_type) {
    $icon_class = 'dashicons-media-document'; // Default

    if (strpos($mime_type, 'pdf') !== false) {
        $icon_class = 'dashicons-media-document';
    } elseif (strpos($mime_type, 'word') !== false || strpos($mime_type, 'document') !== false) {
        $icon_class = 'dashicons-media-document';
    } elseif (strpos($mime_type, 'spreadsheet') !== false || strpos($mime_type, 'excel') !== false) {
        $icon_class = 'dashicons-media-spreadsheet';
    } elseif (strpos($mime_type, 'presentation') !== false || strpos($mime_type, 'powerpoint') !== false) {
        $icon_class = 'dashicons-media-interactive';
    } elseif (strpos($mime_type, 'image') !== false) {
        $icon_class = 'dashicons-format-image';
    } elseif (strpos($mime_type, 'text') !== false) {
        $icon_class = 'dashicons-media-text';
    }

    return '<span class="dashicons ' . $icon_class . '"></span>';
}

/**
 * Get download URL for document
 */
function get_download_url($document_id) {
    return wp_nonce_url(
        add_query_arg(array(
            'action' => 'bkgt_download_document',
            'document_id' => $document_id,
        ), admin_url('admin-ajax.php')),
        'download_document_' . $document_id
    );
}
?>