// Smart Templates JavaScript

jQuery(document).ready(function($) {
    'use strict';

    let currentContext = 'player';
    let currentContextData = {};
    let selectedItems = [];

    // Context tab switching
    $('.bkgt-context-tab').on('click', function() {
        const context = $(this).data('context');

        $('.bkgt-context-tab').removeClass('active');
        $(this).addClass('active');

        $('.bkgt-context-panel').removeClass('active');
        $(`.bkgt-context-panel[data-context="${context}"]`).addClass('active');

        currentContext = context;
        currentContextData = {};
        clearSuggestions();
        clearBulkApplication();
    });

    // Player search with autocomplete
    $('#bkgt-player-search').on('input', function() {
        const query = $(this).val().trim();
        if (query.length < 2) {
            $('#bkgt-player-results').empty();
            return;
        }

        // Simulate player search - in real implementation, this would be an AJAX call
        const mockPlayers = [
            {id: 1, name: 'Anna Andersson', email: 'anna@example.com', team: 'Damlag'},
            {id: 2, name: 'Erik Eriksson', email: 'erik@example.com', team: 'Herrlag'},
            {id: 3, name: 'Maria Nilsson', email: 'maria@example.com', team: 'U17'}
        ];

        const filteredPlayers = mockPlayers.filter(player =>
            player.name.toLowerCase().includes(query.toLowerCase())
        );

        displaySearchResults(filteredPlayers, 'player');
    });

    // Equipment search
    $('#bkgt-equipment-search').on('input', function() {
        const query = $(this).val().trim();
        if (query.length < 2) {
            $('#bkgt-equipment-results').empty();
            return;
        }

        // Simulate equipment search
        const mockEquipment = [
            {id: '0001-0001-00001', type: 'Hjälm', manufacturer: 'Schutt', condition: 'Normal'},
            {id: '0001-0002-00002', type: 'Axelskydd', manufacturer: 'Riddell', condition: 'Behöver reparation'},
            {id: '0002-0001-00003', type: 'Skor', manufacturer: 'Nike', condition: 'Normal'}
        ];

        const filteredEquipment = mockEquipment.filter(item =>
            item.id.includes(query) || item.type.toLowerCase().includes(query.toLowerCase())
        );

        displaySearchResults(filteredEquipment, 'equipment');
    });

    // Team selection
    $('#bkgt-team-select').on('change', function() {
        const teamId = $(this).val();
        if (teamId) {
            currentContextData = {
                type: 'team',
                id: teamId,
                name: $(this).find('option:selected').text()
            };
            getTemplateSuggestions();
        }
    });

    // Meeting form
    $('#bkgt-meeting-type, #bkgt-meeting-date').on('change input', function() {
        const meetingType = $('#bkgt-meeting-type').val();
        const meetingDate = $('#bkgt-meeting-date').val();

        if (meetingType && meetingDate) {
            currentContextData = {
                type: 'meeting',
                meeting_type: meetingType,
                date: meetingDate
            };
            getTemplateSuggestions();
        }
    });

    // Search result selection
    $(document).on('click', '.bkgt-search-result-item', function() {
        const type = $(this).data('type');
        const data = $(this).data('item');

        currentContextData = {
            type: type,
            ...data
        };

        // Highlight selected item
        $('.bkgt-search-result-item').removeClass('selected');
        $(this).addClass('selected');

        getTemplateSuggestions();
    });

    // Template suggestion actions
    $(document).on('click', '.bkgt-apply-template', function(e) {
        e.stopPropagation();
        const templateId = $(this).data('template-id');
        applyTemplate(templateId);
    });

    $(document).on('click', '.bkgt-preview-template', function(e) {
        e.stopPropagation();
        const templateId = $(this).data('template-id');
        previewTemplate(templateId);
    });

    // Bulk application
    $('#bkgt-select-all').on('click', function() {
        const allItems = $('.bkgt-bulk-item');
        if (selectedItems.length === allItems.length) {
            selectedItems = [];
            allItems.removeClass('selected');
        } else {
            selectedItems = allItems.map(function() {
                return $(this).data('item');
            }).get();
            allItems.addClass('selected');
        }
        updateSelectionCount();
    });

    $(document).on('click', '.bkgt-bulk-item', function() {
        const item = $(this).data('item');
        const index = selectedItems.findIndex(selected => selected.id === item.id);

        if (index > -1) {
            selectedItems.splice(index, 1);
            $(this).removeClass('selected');
        } else {
            selectedItems.push(item);
            $(this).addClass('selected');
        }

        updateSelectionCount();
    });

    $('#bkgt-apply-selected').on('click', function() {
        if (selectedItems.length === 0) {
            alert(bkgt_smart_templates.strings.no_selection || 'Välj minst ett objekt.');
            return;
        }

        const templateId = $(this).data('template-id');
        bulkApplyTemplate(templateId, selectedItems);
    });

    // Modal interactions
    $(document).on('click', '.bkgt-modal-close', function() {
        $(this).closest('.bkgt-modal').hide();
    });

    $(window).on('click', function(e) {
        if ($(e.target).hasClass('bkgt-modal')) {
            $('.bkgt-modal').hide();
        }
    });

    $('#bkgt-confirm-apply').on('click', function() {
        const templateId = $(this).data('template-id');
        applyTemplate(templateId, true);
    });

    $('#bkgt-edit-variables').on('click', function() {
        const templateId = $(this).data('template-id');
        editVariables(templateId);
    });

    $('#bkgt-save-variables').on('click', function() {
        saveVariables();
    });

    $('#bkgt-cancel-edit').on('click', function() {
        $('#bkgt-variable-editor-modal').hide();
    });

    function displaySearchResults(results, type) {
        const container = $(`#bkgt-${type}-results`);
        container.empty();

        if (results.length === 0) {
            container.append('<div class="bkgt-no-results">Inga resultat hittades.</div>');
            return;
        }

        results.forEach(function(item) {
            const itemHtml = createSearchResultHtml(item, type);
            container.append(itemHtml);
        });
    }

    function createSearchResultHtml(item, type) {
        let name, meta;

        switch (type) {
            case 'player':
                name = item.name;
                meta = `${item.team} | ${item.email}`;
                break;
            case 'equipment':
                name = `${item.type} (${item.id})`;
                meta = `${item.manufacturer} | ${item.condition}`;
                break;
            default:
                name = item.name || item.id;
                meta = '';
        }

        return `
            <div class="bkgt-search-result-item" data-type="${type}" data-item='${JSON.stringify(item)}'>
                <div class="bkgt-search-result-name">${name}</div>
                <div class="bkgt-search-result-meta">${meta}</div>
            </div>
        `;
    }

    function getTemplateSuggestions() {
        if (!currentContextData.type) {
            return;
        }

        $('.bkgt-suggestions-container').html('<div class="bkgt-loading"><div class="bkgt-spinner"></div></div>');

        $.ajax({
            url: bkgt_smart_templates.ajax_url,
            type: 'POST',
            data: {
                action: 'bkgt_get_template_suggestions',
                context_type: currentContext,
                context_data: currentContextData,
                nonce: bkgt_smart_templates.nonce
            },
            success: function(response) {
                if (response.success) {
                    displaySuggestions(response.data.suggestions);
                } else {
                    showError(response.data.message || bkgt_smart_templates.strings.error);
                }
            },
            error: function() {
                showError(bkgt_smart_templates.strings.error);
            }
        });
    }

    function displaySuggestions(suggestions) {
        const container = $('.bkgt-suggestions-container');

        if (suggestions.length === 0) {
            container.html(`
                <div class="bkgt-no-suggestions">
                    <i class="dashicons dashicons-lightbulb"></i>
                    <p>${bkgt_smart_templates.strings.no_suggestions}</p>
                </div>
            `);
            return;
        }

        let html = '';
        suggestions.forEach(function(suggestion) {
            html += createSuggestionHtml(suggestion);
        });

        container.html(html);

        // Show bulk application if we have multiple suggestions
        if (suggestions.length > 1) {
            showBulkApplication(suggestions);
        }
    }

    function createSuggestionHtml(suggestion) {
        return `
            <div class="bkgt-suggestion-item">
                <div class="bkgt-suggestion-score">${suggestion.score}%</div>
                <div class="bkgt-suggestion-content">
                    <div class="bkgt-suggestion-title">${suggestion.title}</div>
                    <div class="bkgt-suggestion-description">${suggestion.description || ''}</div>
                    <div class="bkgt-suggestion-preview">${suggestion.preview}</div>
                </div>
                <div class="bkgt-suggestion-actions">
                    <button class="button bkgt-preview-template" data-template-id="${suggestion.id}">
                        <i class="dashicons dashicons-visibility"></i> ${bkgt_smart_templates.strings.preview_document}
                    </button>
                    <button class="button button-primary bkgt-apply-template" data-template-id="${suggestion.id}">
                        <i class="dashicons dashicons-plus-alt"></i> ${bkgt_smart_templates.strings.apply_template}
                    </button>
                </div>
            </div>
        `;
    }

    function showBulkApplication(suggestions) {
        $('.bkgt-bulk-application').show();

        // Create bulk items list based on context
        let bulkItems = [];

        switch (currentContext) {
            case 'team':
                // For team context, show bulk application for team members
                bulkItems = [
                    {id: 1, name: 'Anna Andersson', type: 'player'},
                    {id: 2, name: 'Erik Eriksson', type: 'player'},
                    {id: 3, name: 'Maria Nilsson', type: 'player'}
                ];
                break;
            case 'equipment':
                // For equipment context, show bulk application for similar items
                bulkItems = [
                    {id: '0001-0001-00001', name: 'Hjälm 0001-0001-00001', type: 'equipment'},
                    {id: '0001-0001-00002', name: 'Hjälm 0001-0001-00002', type: 'equipment'},
                    {id: '0001-0001-00003', name: 'Hjälm 0001-0001-00003', type: 'equipment'}
                ];
                break;
        }

        if (bulkItems.length > 0) {
            displayBulkItems(bulkItems, suggestions[0]);
        }
    }

    function displayBulkItems(items, template) {
        const container = $('.bkgt-bulk-results');
        container.empty();

        items.forEach(function(item) {
            const itemHtml = `
                <div class="bkgt-bulk-item" data-item='${JSON.stringify(item)}'>
                    <input type="checkbox" class="bkgt-bulk-checkbox">
                    <span class="bkgt-bulk-item-name">${item.name}</span>
                </div>
            `;
            container.append(itemHtml);
        });

        $('#bkgt-apply-selected').data('template-id', template.id);
        updateSelectionCount();
    }

    function updateSelectionCount() {
        const count = selectedItems.length;
        $('#bkgt-selection-count').text(`${count} valda`);
    }

    function applyTemplate(templateId, confirmed = false) {
        if (!confirmed) {
            // Show preview first
            previewTemplate(templateId);
            return;
        }

        // Apply the template
        $.ajax({
            url: bkgt_smart_templates.ajax_url,
            type: 'POST',
            data: {
                action: 'bkgt_apply_template',
                template_id: templateId,
                context_type: currentContext,
                context_data: currentContextData,
                nonce: bkgt_smart_templates.nonce
            },
            success: function(response) {
                if (response.success) {
                    alert(response.data.message);
                    $('#bkgt-template-preview-modal').hide();
                    // Optionally refresh the page or update UI
                } else {
                    showError(response.data.message || bkgt_smart_templates.strings.error);
                }
            },
            error: function() {
                showError(bkgt_smart_templates.strings.error);
            }
        });
    }

    function previewTemplate(templateId) {
        // For now, show a simple preview modal
        // In a full implementation, this would fetch the rendered template
        $('#bkgt-preview-title').text('Förhandsgranskning');
        $('#bkgt-preview-content').html('<p>Template preview would be shown here...</p>');
        $('#bkgt-confirm-apply').data('template-id', templateId);
        $('#bkgt-edit-variables').data('template-id', templateId);
        $('#bkgt-template-preview-modal').show();
    }

    function editVariables(templateId) {
        $('#bkgt-template-preview-modal').hide();

        // Get available variables for the context
        $.ajax({
            url: bkgt_smart_templates.ajax_url,
            type: 'POST',
            data: {
                action: 'bkgt_get_context_variables',
                context_type: currentContext,
                context_data: currentContextData,
                nonce: bkgt_smart_templates.nonce
            },
            success: function(response) {
                if (response.success) {
                    displayVariableEditor(response.data.variables, templateId);
                }
            }
        });
    }

    function displayVariableEditor(variables, templateId) {
        const container = $('#bkgt-variable-editor');
        container.empty();

        variables.forEach(function(variable) {
            const varHtml = `
                <div class="bkgt-variable-group">
                    <div class="bkgt-variable-name">${variable.name}</div>
                    <div class="bkgt-variable-description">${variable.description}</div>
                    <input type="text" class="bkgt-variable-input" data-variable="${variable.name}" placeholder="Ange värde...">
                </div>
            `;
            container.append(varHtml);
        });

        $('#bkgt-save-variables').data('template-id', templateId);
        $('#bkgt-variable-editor-modal').show();
    }

    function saveVariables() {
        const templateId = $('#bkgt-save-variables').data('template-id');
        const customVariables = {};

        $('.bkgt-variable-input').each(function() {
            const varName = $(this).data('variable');
            const varValue = $(this).val().trim();
            if (varValue) {
                customVariables[varName] = varValue;
            }
        });

        // Apply template with custom variables
        $.ajax({
            url: bkgt_smart_templates.ajax_url,
            type: 'POST',
            data: {
                action: 'bkgt_apply_template',
                template_id: templateId,
                context_type: currentContext,
                context_data: currentContextData,
                custom_variables: customVariables,
                nonce: bkgt_smart_templates.nonce
            },
            success: function(response) {
                if (response.success) {
                    alert(response.data.message);
                    $('#bkgt-variable-editor-modal').hide();
                    // Optionally refresh the page or update UI
                } else {
                    showError(response.data.message || bkgt_smart_templates.strings.error);
                }
            },
            error: function() {
                showError(bkgt_smart_templates.strings.error);
            }
        });
    }

    function bulkApplyTemplate(templateId, items) {
        $.ajax({
            url: bkgt_smart_templates.ajax_url,
            type: 'POST',
            data: {
                action: 'bkgt_bulk_apply_template',
                template_id: templateId,
                context_type: currentContext,
                items: items,
                nonce: bkgt_smart_templates.nonce
            },
            success: function(response) {
                if (response.success) {
                    displayBulkResults(response.data.results);
                } else {
                    showError(response.data.message || bkgt_smart_templates.strings.error);
                }
            },
            error: function() {
                showError(bkgt_smart_templates.strings.error);
            }
        });
    }

    function displayBulkResults(results) {
        const container = $('.bkgt-bulk-results');
        container.empty();

        results.forEach(function(result) {
            const statusClass = result.success ? 'success' : 'error';
            const statusIcon = result.success ? '✓' : '✗';
            const message = result.success ?
                `Dokument skapat (ID: ${result.document_id})` :
                result.error;

            const resultHtml = `
                <div class="bkgt-bulk-result-item">
                    <div class="bkgt-bulk-result-status ${statusClass}">${statusIcon}</div>
                    <div class="bkgt-bulk-result-content">
                        <div class="bkgt-bulk-result-title">${result.item.name}</div>
                        <div class="bkgt-bulk-result-message">${message}</div>
                    </div>
                </div>
            `;
            container.append(resultHtml);
        });
    }

    function clearSuggestions() {
        $('.bkgt-suggestions-container').html(`
            <div class="bkgt-no-suggestions">
                <i class="dashicons dashicons-lightbulb"></i>
                <p>${bkgt_smart_templates.strings.no_suggestions}</p>
            </div>
        `);
    }

    function clearBulkApplication() {
        $('.bkgt-bulk-application').hide();
        $('.bkgt-bulk-results').empty();
        selectedItems = [];
        updateSelectionCount();
    }

    function showError(message) {
        alert(message);
    }
});