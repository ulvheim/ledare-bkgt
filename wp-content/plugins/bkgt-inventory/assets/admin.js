/**
 * BKGT Inventory Admin JavaScript
 *
 * @package BKGT_Inventory
 * @since 1.0.0
 */

(function($) {
    'use strict';
    
    $(document).ready(function() {
        
        // Handle delete buttons
        $('.button-link-delete').on('click', function(e) {
            e.preventDefault();
            
            var $button = $(this);
            var action = $button.data('action');
            var id = $button.data('id');
            var name = $button.data('name');
            
            if (!confirm(bkgtInventory.strings.confirmDelete.replace('%s', name))) {
                return;
            }
            
            $button.prop('disabled', true).text('Raderar...');
            
            $.ajax({
                url: bkgtInventory.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'bkgt_inventory_action',
                    sub_action: action,
                    id: id,
                    nonce: bkgtInventory.nonce
                },
                success: function(response) {
                    if (response.success) {
                        $button.closest('tr').fadeOut(300, function() {
                            $(this).remove();
                        });
                    } else {
                        alert(response.data || 'Ett fel uppstod.');
                        $button.prop('disabled', false).text('Radera');
                    }
                },
                error: function() {
                    alert('Ett fel uppstod vid kommunikation med servern.');
                    $button.prop('disabled', false).text('Radera');
                }
            });
        });
        
        // Handle manufacturer/item type code auto-uppercase
        $('#manufacturer_code, #item_type_code').on('input', function() {
            var value = $(this).val().toUpperCase();
            $(this).val(value);
        });
        
        // Generate unique ID preview
        var generateUniqueIdPreview = function() {
            var manufacturerSelect = $('#bkgt_manufacturer_id');
            var itemTypeSelect = $('#bkgt_item_type_id');
            var previewElement = $('#bkgt_unique_id_preview');
            
            if (manufacturerSelect.length && itemTypeSelect.length) {
                var manufacturerId = manufacturerSelect.val();
                var itemTypeId = itemTypeSelect.val();
                
                if (manufacturerId && itemTypeId) {
                    // Make AJAX call to generate identifier
                    $.ajax({
                        url: bkgtInventory.ajaxUrl,
                        type: 'POST',
                        data: {
                            action: 'bkgt_inventory_action',
                            sub_action: 'generate_identifier',
                            manufacturer_id: manufacturerId,
                            item_type_id: itemTypeId,
                            nonce: bkgtInventory.nonce
                        },
                        success: function(response) {
                            if (response.success) {
                                if (!previewElement.length) {
                                    $('#bkgt_manufacturer_id').closest('td').append('<p id="bkgt_unique_id_preview" style="margin-top: 5px; color: #666; font-size: 12px;"></p>');
                                    previewElement = $('#bkgt_unique_id_preview');
                                }
                                previewElement.text('Förhandsgranskning: ' + response.data.identifier);
                            }
                        }
                    });
                } else {
                    if (previewElement.length) {
                        previewElement.text('');
                    }
                }
            }
        };
        
        $('#bkgt_manufacturer_id, #bkgt_item_type_id').on('change', generateUniqueIdPreview);
        
        // Initialize preview on page load
        generateUniqueIdPreview();
        
        // Handle bulk actions for inventory items
        $('#doaction, #doaction2').on('click', function(e) {
            var action = $(this).prev('select').val();
            
            if (action === 'trash' || action === 'delete') {
                var checkedBoxes = $('input[name="post[]"]:checked');
                
                if (checkedBoxes.length > 0 && !confirm(bkgtInventory.strings.confirmBulkDelete)) {
                    e.preventDefault();
                    return false;
                }
            }
        });
        
        // Handle quick assignment links
        $('.row-actions .assign_club, .row-actions .assign_team, .row-actions .unassign').on('click', function(e) {
            e.preventDefault();
            
            var $link = $(this);
            var url = new URL($link.attr('href'));
            var postId = url.searchParams.get('post');
            var assignmentType = $link.hasClass('assign_club') ? 'club' : 
                               ($link.hasClass('assign_team') ? 'team' : 'unassign');
            
            var assignedTo = 0;
            if (assignmentType === 'team') {
                // For now, we'll need to show a dialog or redirect to edit page
                // This is a simplified version - in production you'd want a modal
                window.location.href = $link.attr('href');
                return;
            }
            
            $link.text('Tilldelar...');
            
            $.ajax({
                url: bkgtInventory.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'bkgt_inventory_action',
                    sub_action: 'quick_assign',
                    post_id: postId,
                    assignment_type: assignmentType,
                    assigned_to: assignedTo,
                    nonce: bkgtInventory.nonce
                },
                success: function(response) {
                    if (response.success) {
                        location.reload(); // Refresh to show updated assignment
                    } else {
                        alert(response.data || 'Ett fel uppstod.');
                        $link.text($link.hasClass('assign_club') ? 'Tilldela klubben' : 
                                 ($link.hasClass('assign_team') ? 'Tilldela lag' : 'Ta bort tilldelning'));
                    }
                },
                error: function() {
                    alert('Ett fel uppstod vid kommunikation med servern.');
                    $link.text($link.hasClass('assign_club') ? 'Tilldela klubben' : 
                             ($link.hasClass('assign_team') ? 'Tilldela lag' : 'Ta bort tilldelning'));
                }
            });
        });
        
        // Auto-save assignment changes (optional enhancement)
        var assignmentTimeout;
        $('#bkgt_assignment_type, #bkgt_assigned_team, #bkgt_assigned_user').on('change', function() {
            clearTimeout(assignmentTimeout);
            assignmentTimeout = setTimeout(function() {
                // Could implement auto-save here if desired
                console.log('Assignment changed - could auto-save');
            }, 1000);
        });
        
    });
    
})(jQuery);