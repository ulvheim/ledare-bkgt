<?php
/**
 * Frontend Template: Single Inventory Item
 *
 * @package BKGT_Inventory
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

get_header();

while (have_posts()): the_post();
    $post_id = get_the_ID();
    $unique_id = get_post_meta($post_id, '_bkgt_unique_id', true);
    $manufacturer_id = get_post_meta($post_id, '_bkgt_manufacturer_id', true);
    $item_type_id = get_post_meta($post_id, '_bkgt_item_type_id', true);
    $serial_number = get_post_meta($post_id, '_bkgt_serial_number', true);
    $purchase_date = get_post_meta($post_id, '_bkgt_purchase_date', true);
    $purchase_price = get_post_meta($post_id, '_bkgt_purchase_price', true);
    $warranty_expiry = get_post_meta($post_id, '_bkgt_warranty_expiry', true);
    $notes = get_post_meta($post_id, '_bkgt_notes', true);
    $assignment_type = get_post_meta($post_id, '_bkgt_assignment_type', true);
    $assigned_to = get_post_meta($post_id, '_bkgt_assigned_to', true);
    
    $manufacturer = $manufacturer_id ? BKGT_Manufacturer::get($manufacturer_id) : null;
    $item_type = $item_type_id ? BKGT_Item_Type::get($item_type_id) : null;
    $conditions = wp_get_post_terms($post_id, 'bkgt_condition');
    
    // Check if user can view this item
    $can_view = false;
    $current_user = wp_get_current_user();
    
    if (current_user_can('manage_inventory')) {
        $can_view = true;
    } elseif (empty($assignment_type)) {
        $can_view = false; // Unassigned items not visible to regular users
    } elseif ($assignment_type === 'club') {
        $can_view = true; // Club items visible to all
    } elseif ($assignment_type === 'team') {
        $user_team = get_user_meta($current_user->ID, '_bkgt_team_id', true);
        $can_view = ($assigned_to == $user_team);
    } elseif ($assignment_type === 'individual') {
        $can_view = ($assigned_to == $current_user->ID);
    }
    
    if (!$can_view) {
        echo '<div class="bkgt-access-denied">';
        echo '<p>' . esc_html__('Du har inte behörighet att visa denna artikel.', 'bkgt-inventory') . '</p>';
        echo '</div>';
        get_footer();
        return;
    }
    ?>

    <div class="bkgt-inventory-container">
        <div class="bkgt-inventory-header">
            <h1><?php the_title(); ?></h1>
            <div class="bkgt-item-unique-id"><?php echo esc_html($unique_id); ?></div>
            
            <?php if (current_user_can('manage_inventory')): ?>
            <a href="<?php echo get_edit_post_link(); ?>" class="bkgt-button">
                <?php esc_html_e('Redigera artikel', 'bkgt-inventory'); ?>
            </a>
            <?php endif; ?>
        </div>
        
        <div class="bkgt-item-content">
            <div class="bkgt-item-main">
                <div class="bkgt-item-details-grid">
                    <?php if ($manufacturer): ?>
                    <div class="bkgt-detail-item">
                        <label><?php esc_html_e('Tillverkare', 'bkgt-inventory'); ?></label>
                        <span><?php echo esc_html($manufacturer->name); ?></span>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($item_type): ?>
                    <div class="bkgt-detail-item">
                        <label><?php esc_html_e('Artikeltyp', 'bkgt-inventory'); ?></label>
                        <span><?php echo esc_html($item_type->name); ?></span>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($serial_number): ?>
                    <div class="bkgt-detail-item">
                        <label><?php esc_html_e('Serienummer', 'bkgt-inventory'); ?></label>
                        <span><?php echo esc_html($serial_number); ?></span>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($conditions)): ?>
                    <div class="bkgt-detail-item">
                        <label><?php esc_html_e('Skick', 'bkgt-inventory'); ?></label>
                        <span><?php echo esc_html($conditions[0]->name); ?></span>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($purchase_date): ?>
                    <div class="bkgt-detail-item">
                        <label><?php esc_html_e('Inköpsdatum', 'bkgt-inventory'); ?></label>
                        <span><?php echo wp_date('Y-m-d', strtotime($purchase_date)); ?></span>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($purchase_price): ?>
                    <div class="bkgt-detail-item">
                        <label><?php esc_html_e('Inköpspris', 'bkgt-inventory'); ?></label>
                        <span><?php echo number_format($purchase_price, 2, ',', ' ') . ' SEK'; ?></span>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($warranty_expiry): ?>
                    <div class="bkgt-detail-item">
                        <label><?php esc_html_e('Garanti utgångsdatum', 'bkgt-inventory'); ?></label>
                        <span><?php echo wp_date('Y-m-d', strtotime($warranty_expiry)); ?></span>
                    </div>
                    <?php endif; ?>
                    
                    <div class="bkgt-detail-item">
                        <label><?php esc_html_e('Tilldelning', 'bkgt-inventory'); ?></label>
                        <span>
                            <?php
                            if (empty($assignment_type)) {
                                echo esc_html__('Ej tilldelad', 'bkgt-inventory');
                            } else {
                                $type_labels = array(
                                    'club' => __('Klubben', 'bkgt-inventory'),
                                    'team' => __('Lag', 'bkgt-inventory'),
                                    'individual' => __('Individ', 'bkgt-inventory'),
                                );
                                
                                $label = isset($type_labels[$assignment_type]) ? $type_labels[$assignment_type] : $assignment_type;
                                
                                if ($assignment_type === 'team' && $assigned_to) {
                                    if (function_exists('bkgt_get_team')) {
                                        $team = bkgt_get_team($assigned_to);
                                        if ($team) {
                                            $label .= ': ' . esc_html($team->name);
                                        }
                                    }
                                } elseif ($assignment_type === 'individual' && $assigned_to) {
                                    $user = get_userdata($assigned_to);
                                    if ($user) {
                                        $label .= ': ' . esc_html($user->display_name);
                                    }
                                }
                                
                                echo esc_html($label);
                            }
                            ?>
                        </span>
                    </div>
                </div>
                
                <?php if ($notes): ?>
                <div class="bkgt-item-notes">
                    <h3><?php esc_html_e('Anteckningar', 'bkgt-inventory'); ?></h3>
                    <p><?php echo wp_kses_post($notes); ?></p>
                </div>
                <?php endif; ?>
                
                <div class="bkgt-item-description">
                    <?php the_content(); ?>
                </div>
            </div>
            
            <div class="bkgt-item-sidebar">
                <?php if (has_post_thumbnail()): ?>
                <div class="bkgt-item-image">
                    <?php the_post_thumbnail('large'); ?>
                </div>
                <?php endif; ?>
                
                <div class="bkgt-item-history">
                    <h3><?php esc_html_e('Senaste aktivitet', 'bkgt-inventory'); ?></h3>
                    <?php
                    $history = BKGT_History::get_item_history($post_id, 5);
                    if (!empty($history)):
                    ?>
                        <ul class="bkgt-history-list">
                            <?php foreach ($history as $entry): ?>
                            <li>
                                <small><?php echo wp_date('Y-m-d H:i', strtotime($entry->timestamp)); ?></small>
                                <div><?php echo esc_html(BKGT_History::get_action_description($entry->action, $entry->data)); ?></div>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p><?php esc_html_e('Ingen historik tillgänglig.', 'bkgt-inventory'); ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="bkgt-item-navigation">
            <a href="<?php echo get_post_type_archive_link('bkgt_inventory_item'); ?>" class="bkgt-button bkgt-button-secondary">
                <?php esc_html_e('← Tillbaka till alla artiklar', 'bkgt-inventory'); ?>
            </a>
        </div>
    </div>

<?php endwhile;

get_footer();
?>