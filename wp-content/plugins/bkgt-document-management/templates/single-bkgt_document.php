<?php
/**
 * Single Document Template
 *
 * @package BKGT_Document_Management
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

get_header();

while (have_posts()): the_post();
    $document = new BKGT_Document(get_the_ID());
    $can_view = BKGT_Document_Access::user_has_access(get_the_ID());
    $can_edit = BKGT_Document_Access::user_has_access(get_the_ID(), null, BKGT_Document_Access::ACCESS_EDIT);
    $can_manage = BKGT_Document_Access::user_has_access(get_the_ID(), null, BKGT_Document_Access::ACCESS_MANAGE);
    ?>

    <div class="bkgt-single-document">
        <div class="bkgt-container">

            <!-- Document Header -->
            <header class="bkgt-document-header">

                <div class="bkgt-document-title-section">
                    <div class="bkgt-document-icon">
                        <?php echo get_file_icon($document->get_mime_type()); ?>
                    </div>

                    <div class="bkgt-document-title-info">
                        <h1><?php the_title(); ?></h1>

                        <div class="bkgt-document-meta">
                            <span class="bkgt-meta-item">
                                <strong><?php _e('Storlek:', 'bkgt-document-management'); ?></strong>
                                <?php echo $document->get_formatted_file_size(); ?>
                            </span>
                            <span class="bkgt-meta-item">
                                <strong><?php _e('Typ:', 'bkgt-document-management'); ?></strong>
                                <?php echo esc_html($document->get_mime_type()); ?>
                            </span>
                            <span class="bkgt-meta-item">
                                <strong><?php _e('Uppladdad:', 'bkgt-document-management'); ?></strong>
                                <?php echo $document->get_upload_date(); ?>
                            </span>
                            <span class="bkgt-meta-item">
                                <strong><?php _e('Nedladdningar:', 'bkgt-document-management'); ?></strong>
                                <?php echo $document->get_download_count(); ?>
                            </span>
                        </div>

                        <div class="bkgt-document-categories">
                            <?php
                            $categories = get_the_terms(get_the_ID(), 'bkgt_doc_category');
                            if ($categories):
                                echo '<strong>' . __('Kategorier:', 'bkgt-document-management') . '</strong> ';
                                $category_links = array();
                                foreach ($categories as $category):
                                    $category_links[] = '<a href="' . get_term_link($category) . '">' . esc_html($category->name) . '</a>';
                                endforeach;
                                echo implode(', ', $category_links);
                            endif;
                            ?>
                        </div>
                    </div>
                </div>

                <div class="bkgt-document-actions">
                    <?php if ($can_view): ?>
                        <a href="<?php echo get_download_url(get_the_ID()); ?>" class="bkgt-button-primary bkgt-download-link">
                            <span class="dashicons dashicons-download"></span>
                            <?php _e('Ladda ner', 'bkgt-document-management'); ?>
                        </a>
                    <?php endif; ?>

                    <?php if ($can_edit): ?>
                        <a href="<?php echo get_edit_post_link(); ?>" class="bkgt-button-secondary">
                            <span class="dashicons dashicons-edit"></span>
                            <?php _e('Redigera', 'bkgt-document-management'); ?>
                        </a>
                    <?php endif; ?>

                    <?php if ($can_manage): ?>
                        <button class="bkgt-button-secondary bkgt-share-document" data-document-id="<?php the_ID(); ?>">
                            <span class="dashicons dashicons-share"></span>
                            <?php _e('Dela', 'bkgt-document-management'); ?>
                        </button>
                    <?php endif; ?>

                    <a href="<?php echo get_post_type_archive_link('bkgt_document'); ?>" class="bkgt-button-secondary">
                        <span class="dashicons dashicons-arrow-left-alt"></span>
                        <?php _e('Tillbaka till dokument', 'bkgt-document-management'); ?>
                    </a>
                </div>

            </header>

            <!-- Document Content -->
            <div class="bkgt-document-content">

                <?php if ($can_view): ?>

                    <!-- Document Description -->
                    <?php if (get_the_content()): ?>
                        <div class="bkgt-document-description">
                            <h2><?php _e('Beskrivning', 'bkgt-document-management'); ?></h2>
                            <div class="bkgt-description-content">
                                <?php the_content(); ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Document Versions -->
                    <?php
                    $versions = $document->get_versions();
                    if (count($versions) > 1):
                    ?>
                        <div class="bkgt-document-versions">
                            <h2><?php _e('Versioner', 'bkgt-document-management'); ?></h2>

                            <div class="bkgt-versions-list">
                                <table class="bkgt-table">
                                    <thead>
                                        <tr>
                                            <th><?php _e('Version', 'bkgt-document-management'); ?></th>
                                            <th><?php _e('Storlek', 'bkgt-document-management'); ?></th>
                                            <th><?php _e('Uppladdad av', 'bkgt-document-management'); ?></th>
                                            <th><?php _e('Datum', 'bkgt-document-management'); ?></th>
                                            <th><?php _e('Ändringar', 'bkgt-document-management'); ?></th>
                                            <th><?php _e('Åtgärder', 'bkgt-document-management'); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($versions as $index => $version): ?>
                                            <tr class="<?php echo $index === 0 ? 'current-version' : ''; ?>">
                                                <td>
                                                    <?php echo count($versions) - $index; ?>
                                                    <?php if ($index === 0): ?>
                                                        <span class="bkgt-current-badge"><?php _e('(Aktuell)', 'bkgt-document-management'); ?></span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo $version->get_formatted_file_size(); ?></td>
                                                <td><?php echo esc_html($version->get_uploaded_by_name()); ?></td>
                                                <td><?php echo $version->get_formatted_upload_date(); ?></td>
                                                <td><?php echo esc_html($version->get_change_description() ?: __('Ingen beskrivning', 'bkgt-document-management')); ?></td>
                                                <td>
                                                    <a href="<?php echo get_download_version_url($version->get_id()); ?>" class="bkgt-button-small">
                                                        <?php _e('Ladda ner', 'bkgt-document-management'); ?>
                                                    </a>
                                                    <?php if ($can_edit && $index > 0): ?>
                                                        <button class="bkgt-button-small bkgt-restore-version" data-version-id="<?php echo $version->get_id(); ?>">
                                                            <?php _e('Återställ', 'bkgt-document-management'); ?>
                                                        </button>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Document Statistics -->
                    <div class="bkgt-document-stats">
                        <h2><?php _e('Statistik', 'bkgt-document-management'); ?></h2>

                        <div class="bkgt-stats-grid">
                            <div class="bkgt-stat-item">
                                <span class="bkgt-stat-label"><?php _e('Totalt antal nedladdningar:', 'bkgt-document-management'); ?></span>
                                <span class="bkgt-stat-value"><?php echo $document->get_download_count(); ?></span>
                            </div>

                            <div class="bkgt-stat-item">
                                <span class="bkgt-stat-label"><?php _e('Nedladdningar senaste veckan:', 'bkgt-document-management'); ?></span>
                                <span class="bkgt-stat-value"><?php echo $document->get_download_count(7); ?></span>
                            </div>

                            <div class="bkgt-stat-item">
                                <span class="bkgt-stat-label"><?php _e('Antal versioner:', 'bkgt-document-management'); ?></span>
                                <span class="bkgt-stat-value"><?php echo count($versions); ?></span>
                            </div>
                        </div>
                    </div>

                <?php else: ?>

                    <!-- Access Restricted -->
                    <div class="bkgt-access-restricted">
                        <div class="bkgt-restricted-icon">
                            <span class="dashicons dashicons-lock"></span>
                        </div>

                        <h2><?php _e('Begränsad åtkomst', 'bkgt-document-management'); ?></h2>

                        <p><?php _e('Du har inte behörighet att visa detta dokument. Kontakta en administratör om du behöver åtkomst.', 'bkgt-document-management'); ?></p>

                        <div class="bkgt-access-info">
                            <h3><?php _e('Åtkomstinformation', 'bkgt-document-management'); ?></h3>
                            <p><?php _e('Detta dokument är begränsat till specifika användare, roller eller lag. Dina nuvarande behörigheter räcker inte för att visa innehållet.', 'bkgt-document-management'); ?></p>
                        </div>
                    </div>

                <?php endif; ?>

            </div>

        </div>
    </div>

    <?php
endwhile;

get_footer();

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

/**
 * Get download URL for specific version
 */
function get_download_version_url($version_id) {
    return wp_nonce_url(
        add_query_arg(array(
            'action' => 'bkgt_download_version',
            'version_id' => $version_id,
        ), admin_url('admin-ajax.php')),
        'download_version_' . $version_id
    );
}
?>