<?php
/**
 * Locations Management Page Template
 *
 * @package BKGT_Inventory
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1><?php _e('Platsförvaltning', 'bkgt-inventory'); ?></h1>
    
    <?php settings_errors('bkgt_locations'); ?>
    
    <div class="bkgt-locations-container">
        <div class="bkgt-locations-sidebar">
            <div class="bkgt-location-form-container">
                <h2><?php _e('Lägg till ny plats', 'bkgt-inventory'); ?></h2>
                <form method="post" action="">
                    <?php wp_nonce_field('bkgt_location_action'); ?>
                    <input type="hidden" name="action" value="create_location">
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row"><label for="location_name"><?php _e('Platsnamn *', 'bkgt-inventory'); ?></label></th>
                            <td><input type="text" id="location_name" name="location_name" class="regular-text" required></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="location_slug"><?php _e('Slug', 'bkgt-inventory'); ?></label></th>
                            <td><input type="text" id="location_slug" name="location_slug" class="regular-text" placeholder="<?php _e('Lämna tom för auto-generering', 'bkgt-inventory'); ?>"></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="parent_id"><?php _e('Föräldraplats', 'bkgt-inventory'); ?></label></th>
                            <td>
                                <select id="parent_id" name="parent_id" class="regular-text">
                                    <option value=""><?php _e('Ingen förälder (toppnivå)', 'bkgt-inventory'); ?></option>
                                    <?php $this->render_location_options($locations); ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="location_type"><?php _e('Platstyp', 'bkgt-inventory'); ?></label></th>
                            <td>
                                <select id="location_type" name="location_type" class="regular-text">
                                    <?php foreach (BKGT_Location::get_location_types() as $type => $label): ?>
                                        <option value="<?php echo esc_attr($type); ?>"><?php echo esc_html($label); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="address"><?php _e('Adress', 'bkgt-inventory'); ?></label></th>
                            <td><textarea id="address" name="address" class="regular-text" rows="3"></textarea></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="contact_person"><?php _e('Kontaktperson', 'bkgt-inventory'); ?></label></th>
                            <td><input type="text" id="contact_person" name="contact_person" class="regular-text"></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="contact_phone"><?php _e('Telefon', 'bkgt-inventory'); ?></label></th>
                            <td><input type="tel" id="contact_phone" name="contact_phone" class="regular-text"></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="contact_email"><?php _e('E-post', 'bkgt-inventory'); ?></label></th>
                            <td><input type="email" id="contact_email" name="contact_email" class="regular-text"></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="capacity"><?php _e('Kapacitet', 'bkgt-inventory'); ?></label></th>
                            <td><input type="number" id="capacity" name="capacity" class="regular-text" min="0" placeholder="<?php _e('Antal artiklar', 'bkgt-inventory'); ?>"></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="access_restrictions"><?php _e('Åtkomstbegränsningar', 'bkgt-inventory'); ?></label></th>
                            <td><textarea id="access_restrictions" name="access_restrictions" class="regular-text" rows="2" placeholder="<?php _e('Vem har tillgång? Behörigheter?', 'bkgt-inventory'); ?>"></textarea></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="notes"><?php _e('Anteckningar', 'bkgt-inventory'); ?></label></th>
                            <td><textarea id="notes" name="notes" class="regular-text" rows="3"></textarea></td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Aktiv', 'bkgt-inventory'); ?></th>
                            <td><label><input type="checkbox" name="is_active" value="1" checked> <?php _e('Plats är aktiv', 'bkgt-inventory'); ?></label></td>
                        </tr>
                    </table>
                    
                    <p class="submit">
                        <input type="submit" name="submit" id="submit" class="button button-primary" value="<?php _e('Skapa plats', 'bkgt-inventory'); ?>">
                    </p>
                </form>
            </div>
        </div>
        
        <div class="bkgt-locations-main">
            <h2><?php _e('Alla platser', 'bkgt-inventory'); ?></h2>
            
            <?php if (empty($locations)): ?>
                <p><?php _e('Inga platser har skapats än.', 'bkgt-inventory'); ?></p>
            <?php else: ?>
                <div class="bkgt-locations-list">
                    <?php $this->render_locations_hierarchy($locations); ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.bkgt-locations-container {
    display: flex;
    gap: 20px;
}

.bkgt-locations-sidebar {
    flex: 0 0 400px;
}

.bkgt-locations-main {
    flex: 1;
}

.bkgt-location-form-container {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    padding: 20px;
}

.bkgt-locations-list {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
}

.bkgt-location-item {
    border-bottom: 1px solid #f0f0f1;
    padding: 15px;
}

.bkgt-location-item:last-child {
    border-bottom: none;
}

.bkgt-location-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.bkgt-location-name {
    font-weight: 600;
    font-size: 16px;
}

.bkgt-location-type {
    background: #f0f0f1;
    padding: 2px 8px;
    border-radius: 3px;
    font-size: 12px;
    text-transform: uppercase;
}

.bkgt-location-meta {
    margin-top: 8px;
    font-size: 13px;
    color: #666;
}

.bkgt-location-actions {
    margin-top: 10px;
}

.bkgt-location-actions .button {
    margin-right: 5px;
}

.bkgt-location-children {
    margin-left: 20px;
    border-left: 2px solid #f0f0f1;
}

.bkgt-edit-location-form {
    background: #f9f9f9;
    border: 1px solid #e5e5e5;
    border-radius: 4px;
    padding: 15px;
    margin-top: 10px;
}

.bkgt-edit-location-form .form-table {
    margin-bottom: 0;
}

.bkgt-edit-location-form .submit {
    margin-top: 15px;
    padding-top: 15px;
    border-top: 1px solid #e5e5e5;
}
</style>

<script>
jQuery(document).ready(function($) {
    // Auto-generate slug from name
    $('#location_name').on('input', function() {
        var name = $(this).val();
        var slug = name.toLowerCase()
            .replace(/[^\w\s-]/g, '')
            .replace(/[\s_-]+/g, '-')
            .replace(/^-+|-+$/g, '');
        $('#location_slug').val(slug);
    });
    
    // Toggle edit forms
    $('.bkgt-edit-location').on('click', function(e) {
        e.preventDefault();
        var $item = $(this).closest('.bkgt-location-item');
        $item.find('.bkgt-edit-location-form').slideToggle();
    });
    
    // Cancel edit
    $('.bkgt-cancel-edit').on('click', function(e) {
        e.preventDefault();
        $(this).closest('.bkgt-edit-location-form').slideUp();
    });
});
</script>