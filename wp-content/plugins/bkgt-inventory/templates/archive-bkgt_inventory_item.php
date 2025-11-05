<?php
/**
 * Frontend Template: Inventory Items List
 *
 * @package BKGT_Inventory
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

get_header();
?>

<div class="bkgt-inventory-container">
    <div class="bkgt-inventory-header">
        <h1><?php esc_html_e('Utrustning', 'bkgt-inventory'); ?></h1>
        
        <?php if (current_user_can('manage_inventory')): ?>
        <a href="<?php echo admin_url('post-new.php?post_type=bkgt_inventory_item'); ?>" class="bkgt-button bkgt-button-primary">
            <?php esc_html_e('Lägg till artikel', 'bkgt-inventory'); ?>
        </a>
        <?php endif; ?>
    </div>
    
    <?php
    // Get current user role and team
    $current_user = wp_get_current_user();
    $user_roles = $current_user->roles;
    $user_team = get_user_meta($current_user->ID, '_bkgt_team_id', true);
    
    // Build query based on user permissions
    $args = array(
        'post_type' => 'bkgt_inventory_item',
        'posts_per_page' => -1,
        'post_status' => 'publish',
        'meta_query' => array(),
        'tax_query' => array(),
    );
    
    // Filter based on user role
    if (!current_user_can('manage_inventory')) {
        $meta_query = array('relation' => 'OR');
        
        // Show club items to all users
        $meta_query[] = array(
            'key' => '_bkgt_assignment_type',
            'value' => 'club',
            'compare' => '='
        );
        
        // Show team items to team members
        if ($user_team) {
            $meta_query[] = array(
                'key' => '_bkgt_assignment_type',
                'value' => 'team',
                'compare' => '='
            );
            $meta_query[] = array(
                'key' => '_bkgt_assigned_to',
                'value' => $user_team,
                'compare' => '='
            );
        }
        
        // Show individual items to the owner
        $meta_query[] = array(
            'key' => '_bkgt_assignment_type',
            'value' => 'individual',
            'compare' => '='
        );
        $meta_query[] = array(
            'key' => '_bkgt_assigned_to',
            'value' => $current_user->ID,
            'compare' => '='
        );
        
        $args['meta_query'] = $meta_query;
    }
    
    // Handle filters
    if (isset($_GET['condition'])) {
        $args['tax_query'][] = array(
            'taxonomy' => 'bkgt_condition',
            'field' => 'slug',
            'terms' => sanitize_text_field($_GET['condition']),
        );
    }
    
    if (isset($_GET['manufacturer'])) {
        $args['meta_query'][] = array(
            'key' => '_bkgt_manufacturer_id',
            'value' => intval($_GET['manufacturer']),
            'compare' => '='
        );
    }
    
    $inventory_query = new WP_Query($args);
    ?>
    
    <div class="bkgt-inventory-filters">
        <form method="get" action="">
            <select name="condition">
                <option value=""><?php esc_html_e('Alla skick', 'bkgt-inventory'); ?></option>
                <?php
                $conditions = get_terms(array(
                    'taxonomy' => 'bkgt_condition',
                    'hide_empty' => false,
                ));
                foreach ($conditions as $condition) {
                    printf(
                        '<option value="%s" %s>%s</option>',
                        esc_attr($condition->slug),
                        selected(isset($_GET['condition']) ? $_GET['condition'] : '', $condition->slug, false),
                        esc_html($condition->name)
                    );
                }
                ?>
            </select>
            
            <select name="manufacturer">
                <option value=""><?php esc_html_e('Alla tillverkare', 'bkgt-inventory'); ?></option>
                <?php
                $manufacturers = BKGT_Manufacturer::get_all();
                foreach ($manufacturers as $manufacturer) {
                    printf(
                        '<option value="%s" %s>%s</option>',
                        esc_attr($manufacturer->id),
                        selected(isset($_GET['manufacturer']) ? $_GET['manufacturer'] : '', $manufacturer->id, false),
                        esc_html($manufacturer->name)
                    );
                }
                ?>
            </select>
            
            <button type="submit" class="bkgt-button"><?php esc_html_e('Filtrera', 'bkgt-inventory'); ?></button>
        </form>
    </div>
    
    <?php if ($inventory_query->have_posts()): ?>
        <div class="bkgt-inventory-grid">
            <?php while ($inventory_query->have_posts()): $inventory_query->the_post(); ?>
                <?php
                $post_id = get_the_ID();
                $unique_id = get_post_meta($post_id, '_bkgt_unique_id', true);
                $manufacturer_id = get_post_meta($post_id, '_bkgt_manufacturer_id', true);
                $item_type_id = get_post_meta($post_id, '_bkgt_item_type_id', true);
                $assignment_type = get_post_meta($post_id, '_bkgt_assignment_type', true);
                $assigned_to = get_post_meta($post_id, '_bkgt_assigned_to', true);
                
                $manufacturer = $manufacturer_id ? BKGT_Manufacturer::get($manufacturer_id) : null;
                $item_type = $item_type_id ? BKGT_Item_Type::get($item_type_id) : null;
                $conditions = wp_get_post_terms($post_id, 'bkgt_condition');
                
                // Build assignment label for modal
                $assignment_label = '';
                if (empty($assignment_type)) {
                    $assignment_label = __('Ej tilldelad', 'bkgt-inventory');
                } else {
                    $type_labels = array(
                        'club' => __('Klubben', 'bkgt-inventory'),
                        'team' => __('Lag', 'bkgt-inventory'),
                        'individual' => __('Individ', 'bkgt-inventory'),
                    );
                    
                    $assignment_label = isset($type_labels[$assignment_type]) ? $type_labels[$assignment_type] : $assignment_type;
                    
                    if ($assignment_type === 'team' && $assigned_to) {
                        if (function_exists('bkgt_get_team')) {
                            $team = bkgt_get_team($assigned_to);
                            if ($team) {
                                $assignment_label .= ': ' . esc_html($team->name);
                            }
                        }
                    } elseif ($assignment_type === 'individual' && $assigned_to) {
                        $user = get_userdata($assigned_to);
                        if ($user) {
                            $assignment_label .= ': ' . esc_html($user->display_name);
                        }
                    }
                }
                ?>
                
                <div class="bkgt-inventory-item">
                    <div class="bkgt-item-header">
                        <h3><?php echo esc_html(get_the_title()); ?></h3>
                        <div class="bkgt-item-id"><?php echo esc_html($unique_id); ?></div>
                    </div>
                    
                    <div class="bkgt-item-details">
                        <?php if ($manufacturer): ?>
                            <div class="bkgt-item-manufacturer">
                                <strong><?php esc_html_e('Tillverkare:', 'bkgt-inventory'); ?></strong>
                                <?php echo esc_html($manufacturer->name); ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($item_type): ?>
                            <div class="bkgt-item-type">
                                <strong><?php esc_html_e('Typ:', 'bkgt-inventory'); ?></strong>
                                <?php echo esc_html($item_type->name); ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($conditions)): ?>
                            <div class="bkgt-item-condition">
                                <strong><?php esc_html_e('Skick:', 'bkgt-inventory'); ?></strong>
                                <?php echo esc_html($conditions[0]->name); ?>
                            </div>
                        <?php endif; ?>
                        
                        <div class="bkgt-item-assignment">
                            <strong><?php esc_html_e('Tilldelning:', 'bkgt-inventory'); ?></strong>
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
                        </div>
                    </div>
                    
                    <div class="bkgt-item-actions">
                        <button type="button" class="bkgt-button bkgt-button-secondary bkgt-show-details" 
                                data-item-id="<?php echo esc_attr($post->ID); ?>"
                                data-item-title="<?php echo esc_attr(get_the_title()); ?>"
                                data-unique-id="<?php echo esc_attr($unique_id); ?>"
                                data-manufacturer="<?php echo esc_attr($manufacturer ? $manufacturer->name : ''); ?>"
                                data-item-type="<?php echo esc_attr($item_type ? $item_type->name : ''); ?>"
                                data-serial-number="<?php echo esc_attr($serial_number); ?>"
                                data-assignment="<?php echo esc_attr($assignment_label); ?>">
                            <?php esc_html_e('Visa detaljer', 'bkgt-inventory'); ?>
                        </button>
                        
                        <?php if (current_user_can('manage_inventory')): ?>
                        <a href="<?php echo get_edit_post_link(); ?>" class="bkgt-button">
                            <?php esc_html_e('Redigera', 'bkgt-inventory'); ?>
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <div class="bkgt-no-items">
            <p><?php esc_html_e('Inga utrustningsartiklar hittades.', 'bkgt-inventory'); ?></p>
        </div>
    <?php endif; ?>
    
    <?php wp_reset_postdata(); ?>
</div>

<!-- Item Details Modal -->
<div id="bkgt-item-modal" class="bkgt-modal" style="display: none;">
    <div class="bkgt-modal-overlay"></div>
    <div class="bkgt-modal-content">
        <div class="bkgt-modal-header">
            <h2 id="modal-item-title"></h2>
            <button type="button" class="bkgt-modal-close">&times;</button>
        </div>
        <div class="bkgt-modal-body">
            <div class="bkgt-modal-details">
                <div class="bkgt-detail-row">
                    <label><?php esc_html_e('Unikt ID', 'bkgt-inventory'); ?>:</label>
                    <span id="modal-unique-id"></span>
                </div>
                <div class="bkgt-detail-row">
                    <label><?php esc_html_e('Tillverkare', 'bkgt-inventory'); ?>:</label>
                    <span id="modal-manufacturer"></span>
                </div>
                <div class="bkgt-detail-row">
                    <label><?php esc_html_e('Artikeltyp', 'bkgt-inventory'); ?>:</label>
                    <span id="modal-item-type"></span>
                </div>
                <div class="bkgt-detail-row">
                    <label><?php esc_html_e('Serienummer', 'bkgt-inventory'); ?>:</label>
                    <span id="modal-serial-number"></span>
                </div>
                <div class="bkgt-detail-row">
                    <label><?php esc_html_e('Tilldelning', 'bkgt-inventory'); ?>:</label>
                    <span id="modal-assignment"></span>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
console.log('BKGT Inventory template script starting...');

try {
    console.log('Modal script loaded');
    
    // Handle show details button clicks
    var detailButtons = document.querySelectorAll('.bkgt-show-details');
    console.log('Found', detailButtons.length, 'detail buttons');
    
    if (detailButtons.length === 0) {
        console.error('No detail buttons found! Check if .bkgt-show-details class exists');
    }
    
    detailButtons.forEach(function(button, index) {
        console.log('Attaching listener to button', index);
        button.addEventListener('click', function() {
            console.log('Button clicked!');
            
            var itemTitle = this.getAttribute('data-item-title');
            console.log('Item title:', itemTitle);
            
            // Show modal
            var modal = document.getElementById('bkgt-item-modal');
            if (modal) {
                modal.style.display = 'block';
                console.log('Modal should be visible now');
            } else {
                console.error('Modal element not found!');
            }
        });
    });
    
    console.log('Script initialization complete');
} catch (error) {
    console.error('Script error:', error);
}
</script>

<?php get_footer(); ?>