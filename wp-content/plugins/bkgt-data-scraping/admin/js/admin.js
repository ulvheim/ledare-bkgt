/**
 * Admin JavaScript for BKGT Data Scraping plugin
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        // Initialize tab functionality
        initTabs();

        // Initialize modal dialogs
        initModals();

        // Initialize player management
        initPlayerManagement();

        // Initialize event management
        initEventManagement();

        // Initialize statistics management
        initStatisticsManagement();

        // Initialize scraping functionality
        initScraping();

        // Initialize drag and drop functionality
        initDragAndDrop();

        // Initialize player assignment modal
        initPlayerAssignment();

        // Initialize search and filtering functionality
        initSearchAndFilters();

        // Initialize export/import functionality
        initExportImport();

        // Initialize form validation
        initFormValidation();

        // Initialize inline editing functionality
        initInlineEditing();

        // Initialize bulk assignment wizard
        initBulkAssignmentWizard();

        // Initialize accessibility features
        initAccessibility();
    });

    /**
     * Initialize accessibility features
     */
    function initAccessibility() {
        // Focus management for modals
        $(document).on('shown.bs.modal', '.bkgt-modal', function() {
            var $modal = $(this);
            var $focusableElements = $modal.find('button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])');
            var $firstFocusable = $focusableElements.first();
            var $lastFocusable = $focusableElements.last();

            $firstFocusable.focus();

            $modal.on('keydown', function(e) {
                if (e.keyCode === 27) { // Escape
                    $modal.hide();
                }
                if (e.keyCode === 9) { // Tab
                    if (e.shiftKey) {
                        if (document.activeElement === $firstFocusable[0]) {
                            e.preventDefault();
                            $lastFocusable.focus();
                        }
                    } else {
                        if (document.activeElement === $lastFocusable[0]) {
                            e.preventDefault();
                            $firstFocusable.focus();
                        }
                    }
                }
            });
        });

        // Announce dynamic content changes
        $(document).on('ajaxComplete', function() {
            // Announce table updates
            if ($('.bkgt-data-table').length) {
                announceToScreenReader('Tabellen har uppdaterats');
            }
        });
    }

    /**
     * Announce message to screen readers
     */
    function announceToScreenReader(message) {
        var $liveRegion = $('#bkgt-status-messages');
        if ($liveRegion.length) {
            $liveRegion.text(message);
            // Clear after a delay to allow re-announcement
            setTimeout(function() {
                $liveRegion.text('');
            }, 1000);
        }
    }

    /**
     * Initialize tab switching
     */
    function initTabs() {
        $('.bkgt-tab-button').on('click', function() {
            var tabId = $(this).data('tab');
            switchToTab(tabId);
        });

        // Keyboard navigation for tabs
        $('.bkgt-tab-button').on('keydown', function(e) {
            var $tabs = $('.bkgt-tab-button');
            var currentIndex = $tabs.index(this);

            switch(e.keyCode) {
                case 37: // Left arrow
                case 38: // Up arrow
                    e.preventDefault();
                    var prevIndex = currentIndex - 1;
                    if (prevIndex < 0) prevIndex = $tabs.length - 1;
                    $tabs.eq(prevIndex).focus();
                    break;
                case 39: // Right arrow
                case 40: // Down arrow
                    e.preventDefault();
                    var nextIndex = currentIndex + 1;
                    if (nextIndex >= $tabs.length) nextIndex = 0;
                    $tabs.eq(nextIndex).focus();
                    break;
                case 13: // Enter
                case 32: // Space
                    e.preventDefault();
                    var tabId = $(this).data('tab');
                    switchToTab(tabId);
                    break;
            }
        });

        function switchToTab(tabId) {
            // Update ARIA attributes
            $('.bkgt-tab-button').attr('aria-selected', 'false');
            $('.bkgt-tab-panel').attr('aria-hidden', 'true').hide();

            // Add active class to clicked tab
            $('.bkgt-tab-button[data-tab="' + tabId + '"]').addClass('active').attr('aria-selected', 'true');

            // Show corresponding panel
            $('#bkgt-tab-' + tabId).addClass('active').attr('aria-hidden', 'false').show();

            // Announce tab change to screen readers
            announceToScreenReader('Fliken ' + $('.bkgt-tab-button[data-tab="' + tabId + '"]').text().trim() + ' är nu aktiv');
        }
    }

    /**
     * Initialize modal dialogs
     */
    function initModals() {
        // Close modal when clicking outside or on close button
        $(document).on('click', '.bkgt-modal-close, .bkgt-modal-cancel', function() {
            $(this).closest('.bkgt-modal').hide();
        });

        // Close modal when clicking outside modal content
        $(document).on('click', '.bkgt-modal', function(e) {
            if (e.target === this) {
                $(this).hide();
            }
        });
    }

    /**
     * Initialize player management
     */
    function initPlayerManagement() {
        // Add new player
        $('#bkgt-add-player').on('click', function() {
            resetPlayerForm();
            $('#bkgt-player-modal-title').text(bkgt_ajax.strings.add_player || 'Add New Player');
            $('#bkgt-player-modal').show();
        });

        // Edit player
        $(document).on('click', '.bkgt-edit-player', function() {
            var playerData = $(this).data('player');
            populatePlayerForm(playerData);
            $('#bkgt-player-modal-title').text(bkgt_ajax.strings.edit_player || 'Edit Player');
            $('#bkgt-player-modal').show();
        });

        // Delete player
        $(document).on('click', '.bkgt-delete-player', function() {
            if (!confirm(bkgt_ajax.strings.confirm_delete)) {
                return;
            }

            var playerId = $(this).data('player-id');
            deletePlayer(playerId);
        });

        // Save player form
        $('#bkgt-player-form').on('submit', function(e) {
            e.preventDefault();
            savePlayer();
        });
    }

    /**
     * Initialize event management
     */
    function initEventManagement() {
        // Add new event
        $('#bkgt-add-event').on('click', function() {
            resetEventForm();
            $('#bkgt-event-modal-title').text(bkgt_ajax.strings.add_event || 'Add New Event');
            $('#bkgt-event-modal').show();
        });

        // Edit event
        $(document).on('click', '.bkgt-edit-event', function() {
            var eventData = $(this).data('event');
            populateEventForm(eventData);
            $('#bkgt-event-modal-title').text(bkgt_ajax.strings.edit_event || 'Edit Event');
            $('#bkgt-event-modal').show();
        });

        // Delete event
        $(document).on('click', '.bkgt-delete-event', function() {
            if (!confirm(bkgt_ajax.strings.confirm_delete)) {
                return;
            }

            var eventId = $(this).data('event-id');
            deleteEvent(eventId);
        });

        // Save event form
        $('#bkgt-event-form').on('submit', function(e) {
            e.preventDefault();
            saveEvent();
        });
    }

    /**
     * Initialize statistics management
     */
    function initStatisticsManagement() {
        // Add new statistics
        $('#bkgt-add-statistics').on('click', function() {
            resetStatisticsForm();
            $('#bkgt-statistics-modal').show();
        });

        // Load player statistics
        $('#bkgt-load-player-stats').on('click', function() {
            var playerId = $('#bkgt-stats-player-filter').val();
            if (!playerId) {
                alert('Please select a player first.');
                return;
            }
            loadPlayerStatistics(playerId);
        });

        // Save statistics form
        $('#bkgt-statistics-form').on('submit', function(e) {
            e.preventDefault();
            saveStatistics();
        });
    }

    /**
     * Initialize scraping functionality
     */
    function initScraping() {
        // Scrape players (legacy)
        $(document).on('click', '#bkgt-scrape-players, #bkgt-manual-scrape-players', function() {
            scrapeData('players');
        });

        // Scrape events (legacy)
        $(document).on('click', '#bkgt-scrape-events, #bkgt-manual-scrape-events', function() {
            scrapeData('events');
        });

        // New scraper interface
        $(document).on('click', '.bkgt-scrape-btn', function() {
            var scrapeType = $(this).data('type');
            runScraper(scrapeType);
        });

        // Handle schedule form submission
        $(document).on('submit', '.bkgt-schedule-form', function(e) {
            e.preventDefault();
            saveScraperSchedule($(this));
        });
    }

    /**
     * Initialize drag and drop functionality for player assignment
     */
    function initDragAndDrop() {
        // Make players draggable
        $(document).on('mousedown', '.bkgt-player-card', function() {
            $(this).addClass('dragging');
        });

        $(document).on('mouseup', function() {
            $('.bkgt-player-card').removeClass('dragging');
        });

        // Initialize droppable areas
        $('.bkgt-droppable').droppable({
            accept: '.bkgt-player-card',
            tolerance: 'pointer',
            over: function(event, ui) {
                $(this).addClass('bkgt-drag-over');
            },
            out: function(event, ui) {
                $(this).removeClass('bkgt-drag-over');
            },
            drop: function(event, ui) {
                $(this).removeClass('bkgt-drag-over');
                var playerCard = ui.draggable;
                var targetList = $(this);

                // Move the player card to the new list
                playerCard.detach().appendTo(targetList);

                // Update assignment data
                updatePlayerAssignment(playerCard.data('player-id'), targetList.attr('id'));
            }
        });

        // Make player cards draggable
        $('.bkgt-player-list .bkgt-player-card').draggable({
            revert: 'invalid',
            cursor: 'move',
            helper: 'clone',
            start: function(event, ui) {
                $(this).addClass('dragging');
            },
            stop: function(event, ui) {
                $(this).removeClass('dragging');
            }
        });
    }

    /**
     * Initialize player assignment modal
     */
    function initPlayerAssignment() {
        // Open assignment modal for an event
        $(document).on('click', '.bkgt-assign-players', function() {
            var eventId = $(this).data('event-id');
            var eventTitle = $(this).data('event-title');

            $('#bkgt-assignment-modal h3').text('Tilldela spelare till: ' + eventTitle);
            $('#bkgt-assignment-modal').data('event-id', eventId);

            loadPlayersForAssignment(eventId);
            $('#bkgt-assignment-modal').show();
        });

        // Save assignment
        $('#bkgt-save-assignment').on('click', function() {
            savePlayerAssignments();
        });
    }

    /**
     * Initialize search and filtering functionality
     */
    function initSearchAndFilters() {
        // Players search and filters
        $('#bkgt-players-search').on('input', function() {
            filterPlayers();
        });

        $('#bkgt-players-status-filter, #bkgt-players-position-filter').on('change', function() {
            filterPlayers();
        });

        $('#bkgt-clear-players-filters').on('click', function() {
            $('#bkgt-players-search').val('');
            $('#bkgt-players-status-filter').val('');
            $('#bkgt-players-position-filter').val('');
            filterPlayers();
        });

        // Events search and filters
        $('#bkgt-events-search').on('input', function() {
            filterEvents();
        });

        $('#bkgt-events-type-filter, #bkgt-events-date-filter').on('change', function() {
            filterEvents();
        });

        $('#bkgt-clear-events-filters').on('click', function() {
            $('#bkgt-events-search').val('');
            $('#bkgt-events-type-filter').val('');
            $('#bkgt-events-date-filter').val('');
            filterEvents();
        });
    }

    /**
     * Initialize export/import functionality
     */
    function initExportImport() {
        // Export buttons
        $('#bkgt-export-players').on('click', function() {
            exportData('players');
        });

        $('#bkgt-export-events').on('click', function() {
            exportData('events');
        });

        // Import buttons
        $('#bkgt-import-players').on('click', function() {
            $('#bkgt-player-import-modal').show();
        });

        $('#bkgt-import-events').on('click', function() {
            $('#bkgt-event-import-modal').show();
        });

        // Import form submissions
        $('#bkgt-player-import-form').on('submit', function(e) {
            e.preventDefault();
            importPlayers();
        });

        $('#bkgt-event-import-form').on('submit', function(e) {
            e.preventDefault();
            importEvents();
        });
    }

    /**
     * Export data to CSV
     */
    function exportData(type) {
        var filename = 'bkgt-' + type + '-' + new Date().toISOString().split('T')[0] + '.csv';

        $.ajax({
            url: bkgt_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'bkgt_export_' + type,
                nonce: bkgt_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    // Create and download CSV file
                    var csvContent = response.data.csv;
                    var blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
                    var link = document.createElement('a');

                    if (link.download !== undefined) {
                        var url = URL.createObjectURL(blob);
                        link.setAttribute('href', url);
                        link.setAttribute('download', filename);
                        link.style.visibility = 'hidden';
                        document.body.appendChild(link);
                        link.click();
                        document.body.removeChild(link);
                    }
                } else {
                    alert(response.data.message || bkgt_ajax.strings.error);
                }
            },
            error: function() {
                alert(bkgt_ajax.strings.error);
            }
        });
    }

    /**
     * Import players from CSV
     */
    function importPlayers() {
        var formData = new FormData(document.getElementById('bkgt-player-import-form'));
        formData.append('action', 'bkgt_import_players');
        formData.append('nonce', bkgt_ajax.nonce);

        $('#bkgt-player-import-form').hide();
        $('#bkgt-import-progress').show();

        $.ajax({
            url: bkgt_ajax.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            xhr: function() {
                var xhr = new window.XMLHttpRequest();
                xhr.upload.addEventListener('progress', function(e) {
                    if (e.lengthComputable) {
                        var percentComplete = (e.loaded / e.total) * 100;
                        $('.bkgt-progress-fill').css('width', percentComplete + '%');
                    }
                });
                return xhr;
            },
            success: function(response) {
                $('#bkgt-import-progress').hide();
                $('#bkgt-player-import-form').show();

                if (response.success) {
                    alert(bkgt_ajax.strings.saved + ' ' + response.data.imported + ' players imported.');
                    location.reload(); // Refresh to show imported data
                } else {
                    alert(response.data.message || bkgt_ajax.strings.error);
                }
            },
            error: function() {
                $('#bkgt-import-progress').hide();
                $('#bkgt-player-import-form').show();
                alert(bkgt_ajax.strings.error);
            }
        });
    }

    /**
     * Import events from CSV
     */
    function importEvents() {
        var formData = new FormData(document.getElementById('bkgt-event-import-form'));
        formData.append('action', 'bkgt_import_events');
        formData.append('nonce', bkgt_ajax.nonce);

        $('#bkgt-event-import-form').hide();
        $('#bkgt-event-import-progress').show();

        $.ajax({
            url: bkgt_ajax.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            xhr: function() {
                var xhr = new window.XMLHttpRequest();
                xhr.upload.addEventListener('progress', function(e) {
                    if (e.lengthComputable) {
                        var percentComplete = (e.loaded / e.total) * 100;
                        $('.bkgt-progress-fill').css('width', percentComplete + '%');
                    }
                });
                return xhr;
            },
            success: function(response) {
                $('#bkgt-event-import-progress').hide();
                $('#bkgt-event-import-form').show();

                if (response.success) {
                    alert(bkgt_ajax.strings.saved + ' ' + response.data.imported + ' events imported.');
                    location.reload(); // Refresh to show imported data
                } else {
                    alert(response.data.message || bkgt_ajax.strings.error);
                }
            },
            error: function() {
                $('#bkgt-event-import-progress').hide();
                $('#bkgt-event-import-form').show();
                alert(bkgt_ajax.strings.error);
            }
        });
    }

    /**
     * Filter players based on search and filter criteria
     */
    function filterPlayers() {
        var searchTerm = $('#bkgt-players-search').val().toLowerCase();
        var statusFilter = $('#bkgt-players-status-filter').val();
        var positionFilter = $('#bkgt-players-position-filter').val();

        $('.bkgt-player-card').each(function() {
            var $card = $(this);
            var playerName = $card.find('h4').text().toLowerCase();
            var playerData = $card.data('player-id');
            var playerStatus = $card.hasClass('bkgt-status-active') ? 'active' :
                              $card.hasClass('bkgt-status-inactive') ? 'inactive' :
                              $card.hasClass('bkgt-status-injured') ? 'injured' : '';
            var playerPosition = $card.find('.bkgt-player-position').text().trim();

            var matchesSearch = !searchTerm || playerName.includes(searchTerm);
            var matchesStatus = !statusFilter || playerStatus === statusFilter;
            var matchesPosition = !positionFilter || playerPosition.includes(positionFilter);

            if (matchesSearch && matchesStatus && matchesPosition) {
                $card.show();
            } else {
                $card.hide();
            }
        });

        updateFilterResults('players');
    }

    /**
     * Filter events based on search and filter criteria
     */
    function filterEvents() {
        var searchTerm = $('#bkgt-events-search').val().toLowerCase();
        var typeFilter = $('#bkgt-events-type-filter').val();
        var dateFilter = $('#bkgt-events-date-filter').val();

        $('.bkgt-event-card').each(function() {
            var $card = $(this);
            var eventTitle = $card.find('h4').text().toLowerCase();
            var eventData = $card.data('event-id');
            var eventType = $card.hasClass('bkgt-event-match') ? 'match' :
                           $card.hasClass('bkgt-event-training') ? 'training' :
                           $card.hasClass('bkgt-event-meeting') ? 'meeting' : '';
            var eventDate = $card.data('date');

            var matchesSearch = !searchTerm || eventTitle.includes(searchTerm);
            var matchesType = !typeFilter || eventType === typeFilter;
            var matchesDate = checkDateFilter(eventDate, dateFilter);

            if (matchesSearch && matchesType && matchesDate) {
                $card.show();
            } else {
                $card.hide();
            }
        });

        updateFilterResults('events');
    }

    /**
     * Check if event date matches the date filter
     */
    function checkDateFilter(eventDate, dateFilter) {
        if (!dateFilter) return true;

        var now = new Date();
        var eventDateObj = new Date(eventDate);
        var today = new Date(now.getFullYear(), now.getMonth(), now.getDate());

        switch (dateFilter) {
            case 'upcoming':
                return eventDateObj >= today;
            case 'past':
                return eventDateObj < today;
            case 'today':
                return eventDateObj.toDateString() === today.toDateString();
            default:
                return true;
        }
    }

    /**
     * Update filter results count
     */
    function updateFilterResults(type) {
        var $container = type === 'players' ? $('#bkgt-players-container') : $('#bkgt-events-container');
        var visibleCount = $container.find('.bkgt-' + (type === 'players' ? 'player' : 'event') + '-card:visible').length;
        var totalCount = $container.find('.bkgt-' + (type === 'players' ? 'player' : 'event') + '-card').length;

        // Update results count display (could be added to UI later)
        console.log('Showing ' + visibleCount + ' of ' + totalCount + ' ' + type);
    }

    /**
     * Reset player form
     */
    function resetPlayerForm() {
        $('#bkgt-player-form')[0].reset();
        $('#bkgt-player-id').val('');
    }

    /**
     * Populate player form
     */
    function populatePlayerForm(playerData) {
        $('#bkgt-player-id').val(playerData.id);
        $('#bkgt-player-player-id').val(playerData.player_id);
        $('#bkgt-player-first-name').val(playerData.first_name);
        $('#bkgt-player-last-name').val(playerData.last_name);
        $('#bkgt-player-position').val(playerData.position);
        $('#bkgt-player-jersey').val(playerData.jersey_number);
        $('#bkgt-player-birth-date').val(playerData.birth_date);
        $('#bkgt-player-status').val(playerData.status);
    }

    /**
     * Save player
     */
    function savePlayer() {
        var formData = new FormData(document.getElementById('bkgt-player-form'));
        formData.append('action', 'bkgt_save_player');

        $.ajax({
            url: bkgt_ajax.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            beforeSend: function() {
                $('button[type="submit"]', '#bkgt-player-form').prop('disabled', true).text(bkgt_ajax.strings.saving);
            },
            success: function(response) {
                if (response.success) {
                    $('#bkgt-player-modal').hide();
                    location.reload(); // Refresh to show updated data
                } else {
                    alert(response.data.message || bkgt_ajax.strings.error);
                }
            },
            error: function() {
                alert(bkgt_ajax.strings.error);
            },
            complete: function() {
                $('button[type="submit"]', '#bkgt-player-form').prop('disabled', false).text('Save Player');
            }
        });
    }

    /**
     * Delete player
     */
    function deletePlayer(playerId) {
        $.ajax({
            url: bkgt_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'bkgt_delete_player',
                player_id: playerId,
                nonce: bkgt_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    location.reload(); // Refresh to show updated data
                } else {
                    alert(response.data.message || bkgt_ajax.strings.error);
                }
            },
            error: function() {
                alert(bkgt_ajax.strings.error);
            }
        });
    }

    /**
     * Reset event form
     */
    function resetEventForm() {
        $('#bkgt-event-form')[0].reset();
        $('#bkgt-event-id').val('');
    }

    /**
     * Populate event form
     */
    function populateEventForm(eventData) {
        $('#bkgt-event-id').val(eventData.id);
        $('#bkgt-event-event-id').val(eventData.event_id);
        $('#bkgt-event-title').val(eventData.title);
        $('#bkgt-event-type').val(eventData.event_type);
        $('#bkgt-event-date').val(eventData.event_date.replace(' ', 'T'));
        $('#bkgt-event-location').val(eventData.location);
        $('#bkgt-event-opponent').val(eventData.opponent);
        $('#bkgt-event-home-away').val(eventData.home_away);
        $('#bkgt-event-result').val(eventData.result);
        $('#bkgt-event-status').val(eventData.status);
    }

    /**
     * Save event
     */
    function saveEvent() {
        var formData = new FormData(document.getElementById('bkgt-event-form'));
        formData.append('action', 'bkgt_save_event');

        $.ajax({
            url: bkgt_ajax.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            beforeSend: function() {
                $('button[type="submit"]', '#bkgt-event-form').prop('disabled', true).text(bkgt_ajax.strings.saving);
            },
            success: function(response) {
                if (response.success) {
                    $('#bkgt-event-modal').hide();
                    location.reload(); // Refresh to show updated data
                } else {
                    alert(response.data.message || bkgt_ajax.strings.error);
                }
            },
            error: function() {
                alert(bkgt_ajax.strings.error);
            },
            complete: function() {
                $('button[type="submit"]', '#bkgt-event-form').prop('disabled', false).text('Save Event');
            }
        });
    }

    /**
     * Delete event
     */
    function deleteEvent(eventId) {
        $.ajax({
            url: bkgt_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'bkgt_delete_event',
                event_id: eventId,
                nonce: bkgt_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    location.reload(); // Refresh to show updated data
                } else {
                    alert(response.data.message || bkgt_ajax.strings.error);
                }
            },
            error: function() {
                alert(bkgt_ajax.strings.error);
            }
        });
    }

    /**
     * Reset statistics form
     */
    function resetStatisticsForm() {
        $('#bkgt-statistics-form')[0].reset();
    }

    /**
     * Load player statistics
     */
    function loadPlayerStatistics(playerId) {
        $.ajax({
            url: bkgt_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'bkgt_get_player_stats',
                player_id: playerId,
                nonce: bkgt_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    displayPlayerStatistics(response.data.statistics);
                } else {
                    alert(response.data.message || bkgt_ajax.strings.error);
                }
            },
            error: function() {
                alert(bkgt_ajax.strings.error);
            }
        });
    }

    /**
     * Display player statistics
     */
    function displayPlayerStatistics(statistics) {
        var tbody = $('#bkgt-player-stats-body');
        tbody.empty();

        if (statistics.length === 0) {
            tbody.append('<tr><td colspan="8" style="text-align: center;">No statistics found for this player.</td></tr>');
            return;
        }

        // Get player name
        var playerName = $('#bkgt-stats-player-filter option:selected').text();
        $('#bkgt-player-stats-title').text('Statistics for ' + playerName);
        $('#bkgt-player-statistics').show();

        statistics.forEach(function(stat) {
            var row = '<tr>' +
                '<td>' + stat.event_title + '</td>' +
                '<td>' + stat.event_date + '</td>' +
                '<td>' + stat.goals + '</td>' +
                '<td>' + stat.assists + '</td>' +
                '<td>' + stat.minutes_played + '</td>' +
                '<td>' + stat.yellow_cards + '</td>' +
                '<td>' + stat.red_cards + '</td>' +
                '<td><button type="button" class="button button-small bkgt-edit-stats" data-stats="' + JSON.stringify(stat).replace(/"/g, '&quot;') + '">Edit</button></td>' +
                '</tr>';
            tbody.append(row);
        });
    }

    /**
     * Save statistics
     */
    function saveStatistics() {
        var formData = new FormData(document.getElementById('bkgt-statistics-form'));
        formData.append('action', 'bkgt_save_statistics');

        $.ajax({
            url: bkgt_ajax.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            beforeSend: function() {
                $('button[type="submit"]', '#bkgt-statistics-form').prop('disabled', true).text(bkgt_ajax.strings.saving);
            },
            success: function(response) {
                if (response.success) {
                    $('#bkgt-statistics-modal').hide();
                    // Reload current player statistics if visible
                    var playerId = $('#bkgt-stats-player-filter').val();
                    if (playerId && $('#bkgt-player-statistics').is(':visible')) {
                        loadPlayerStatistics(playerId);
                    }
                } else {
                    alert(response.data.message || bkgt_ajax.strings.error);
                }
            },
            error: function() {
                alert(bkgt_ajax.strings.error);
            },
            complete: function() {
                $('button[type="submit"]', '#bkgt-statistics-form').prop('disabled', false).text('Save Statistics');
            }
        });
    }

    /**
     * Scrape data
     */
    function scrapeData(dataType) {
        $.ajax({
            url: bkgt_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'bkgt_manual_scrape',
                data_type: dataType,
                nonce: bkgt_ajax.nonce
            },
            beforeSend: function() {
                $('#bkgt-scraping-modal').show();
            },
            success: function(response) {
                $('#bkgt-scraping-modal').hide();
                if (response.success) {
                    alert(bkgt_ajax.strings.saved + ' ' + response.data.count + ' records scraped.');
                    location.reload(); // Refresh to show updated data
                } else {
                    alert(response.data.message || bkgt_ajax.strings.error);
                }
            },
            error: function() {
                $('#bkgt-scraping-modal').hide();
                alert(bkgt_ajax.strings.error);
            }
        });
    }

    /**
     * Run scraper with new interface
     */
    function runScraper(scrapeType) {
        var $progress = $('#bkgt-scraper-progress');
        var $progressFill = $('#bkgt-progress-fill');
        var $progressText = $('#bkgt-progress-text');

        $progress.show();
        $progressFill.css('width', '0%');
        $progressText.text(bkgt_ajax.strings.preparing_scrape || 'Förbereder skrapning...');

        // Disable buttons during scraping
        $('.bkgt-scrape-btn').prop('disabled', true);

        $.ajax({
            url: bkgt_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'bkgt_run_scraper',
                scrape_type: scrapeType,
                nonce: bkgt_ajax.scraper_nonce
            },
            success: function(response) {
                $progress.hide();
                $('.bkgt-scrape-btn').prop('disabled', false);

                if (response.success) {
                    $progressFill.css('width', '100%');
                    $progressText.text(bkgt_ajax.strings.scrape_completed || 'Skrapning slutförd!');

                    // Show success message
                    alert(bkgt_ajax.strings.scrape_success || 'Skrapning slutförd framgångsrikt!');

                    // Refresh the scraper tab content
                    location.reload();
                } else {
                    alert(response.data || bkgt_ajax.strings.error);
                }
            },
            error: function(xhr, status, error) {
                $progress.hide();
                $('.bkgt-scrape-btn').prop('disabled', false);
                alert(bkgt_ajax.strings.error + ': ' + error);
            }
        });
    }

    /**
     * Save scraper schedule settings
     */
    function saveScraperSchedule($form) {
        var formData = $form.serialize();

        $.ajax({
            url: bkgt_ajax.ajax_url,
            type: 'POST',
            data: formData + '&action=bkgt_save_schedule',
            success: function(response) {
                if (response.success) {
                    alert(bkgt_ajax.strings.schedule_saved || 'Schemaläggning sparad!');
                } else {
                    alert(response.data || bkgt_ajax.strings.error);
                }
            },
            error: function() {
                alert(bkgt_ajax.strings.error);
            }
        });
    }

    /**
     * Load players for assignment modal
     */
    function loadPlayersForAssignment(eventId) {
        $.ajax({
            url: bkgt_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'bkgt_get_players_for_assignment',
                event_id: eventId,
                nonce: bkgt_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    populateAssignmentLists(response.data.available, response.data.assigned);
                    initDragAndDrop(); // Reinitialize drag and drop after loading content
                } else {
                    alert(response.data.message || bkgt_ajax.strings.error);
                }
            },
            error: function() {
                alert(bkgt_ajax.strings.error);
            }
        });
    }

    /**
     * Populate assignment lists with players
     */
    function populateAssignmentLists(available, assigned) {
        var availableHtml = '';
        var assignedHtml = '';

        // Build available players list
        available.forEach(function(player) {
            availableHtml += createPlayerCardHtml(player);
        });

        // Build assigned players list
        assigned.forEach(function(player) {
            assignedHtml += createPlayerCardHtml(player);
        });

        $('#bkgt-available-players').html(availableHtml);
        $('#bkgt-assigned-players').html(assignedHtml);
    }

    /**
     * Create HTML for player card
     */
    function createPlayerCardHtml(player) {
        var initials = (player.first_name.charAt(0) + player.last_name.charAt(0)).toUpperCase();
        return '<div class="bkgt-player-card bkgt-draggable" data-player-id="' + player.id + '">' +
            '<div class="bkgt-player-info">' +
                '<div class="bkgt-player-avatar">' + initials + '</div>' +
                '<div class="bkgt-player-details">' +
                    '<h4>' + player.first_name + ' ' + player.last_name + '</h4>' +
                    '<p>' + player.position + ' #' + player.jersey_number + '</p>' +
                '</div>' +
            '</div>' +
            '<div class="bkgt-player-actions">' +
                '<button type="button" class="button bkgt-remove-player" data-player-id="' + player.id + '">&times;</button>' +
            '</div>' +
        '</div>';
    }

    /**
     * Update player assignment when dragged
     */
    function updatePlayerAssignment(playerId, targetListId) {
        var assigned = (targetListId === 'bkgt-assigned-players');
        var eventId = $('#bkgt-assignment-modal').data('event-id');

        $.ajax({
            url: bkgt_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'bkgt_update_player_assignment',
                player_id: playerId,
                event_id: eventId,
                assigned: assigned,
                nonce: bkgt_ajax.nonce
            },
            success: function(response) {
                if (!response.success) {
                    alert(response.data.message || bkgt_ajax.strings.error);
                    // Reload the lists to revert the change
                    loadPlayersForAssignment(eventId);
                }
            },
            error: function() {
                alert(bkgt_ajax.strings.error);
                loadPlayersForAssignment(eventId);
            }
        });
    }

    /**
     * Save all player assignments
     */
    function savePlayerAssignments() {
        var eventId = $('#bkgt-assignment-modal').data('event-id');
        var assignedPlayerIds = [];

        $('#bkgt-assigned-players .bkgt-player-card').each(function() {
            assignedPlayerIds.push($(this).data('player-id'));
        });

        $.ajax({
            url: bkgt_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'bkgt_save_player_assignments',
                event_id: eventId,
                player_ids: assignedPlayerIds,
                nonce: bkgt_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    $('#bkgt-assignment-modal').hide();
                    alert(bkgt_ajax.strings.saved);
                    location.reload(); // Refresh to show updated assignments
                } else {
                    alert(response.data.message || bkgt_ajax.strings.error);
                }
            },
            error: function() {
                alert(bkgt_ajax.strings.error);
            }
        });
    }

    /**
     * Remove player from assignment
     */
    $(document).on('click', '.bkgt-remove-player', function() {
        var playerCard = $(this).closest('.bkgt-player-card');
        var targetList = $('#bkgt-available-players');
        playerCard.detach().appendTo(targetList);
        updatePlayerAssignment(playerCard.data('player-id'), 'bkgt-available-players');
    });

    /**
     * Initialize form validation
     */
    function initFormValidation() {
        // Player form validation
        $('#bkgt-player-form').on('submit', function(e) {
            if (!validatePlayerForm()) {
                e.preventDefault();
                return false;
            }
        });

        // Event form validation
        $('#bkgt-event-form').on('submit', function(e) {
            if (!validateEventForm()) {
                e.preventDefault();
                return false;
            }
        });

        // Real-time validation
        $('#bkgt-player-first-name, #bkgt-player-last-name').on('blur', function() {
            checkPlayerDuplicate();
        });

        $('#bkgt-player-jersey').on('blur', function() {
            checkJerseyNumberDuplicate();
        });

        $('#bkgt-event-title').on('blur', function() {
            checkEventDuplicate();
        });
    }

    /**
     * Validate player form
     */
    function validatePlayerForm() {
        var firstName = $('#bkgt-player-first-name').val().trim();
        var lastName = $('#bkgt-player-last-name').val().trim();
        var position = $('#bkgt-player-position').val();
        var jerseyNumber = $('#bkgt-player-jersey').val();
        var birthDate = $('#bkgt-player-birth-date').val();

        // Required fields
        if (!firstName || !lastName || !position || !jerseyNumber) {
            alert(bkgt_ajax.strings.error + ': ' + 'Alla obligatoriska fält måste fyllas i.');
            return false;
        }

        // Jersey number validation
        if (jerseyNumber < 0 || jerseyNumber > 99) {
            alert('Tröjnummer måste vara mellan 0 och 99.');
            return false;
        }

        // Birth date validation (if provided)
        if (birthDate) {
            var birthDateObj = new Date(birthDate);
            var today = new Date();
            var minAge = new Date(today.getFullYear() - 100, today.getMonth(), today.getDate());

            if (birthDateObj > today) {
                alert('Födelsedatum kan inte vara i framtiden.');
                return false;
            }

            if (birthDateObj < minAge) {
                alert('Födelsedatum verkar vara för gammalt.');
                return false;
            }
        }

        return true;
    }

    /**
     * Validate event form
     */
    function validateEventForm() {
        var title = $('#bkgt-event-title').val().trim();
        var eventType = $('#bkgt-event-type').val();
        var eventDate = $('#bkgt-event-date').val();

        // Required fields
        if (!title || !eventType || !eventDate) {
            alert(bkgt_ajax.strings.error + ': ' + 'Alla obligatoriska fält måste fyllas i.');
            return false;
        }

        // Date validation
        var eventDateObj = new Date(eventDate);
        var now = new Date();

        if (eventDateObj < now && $('#bkgt-event-id').val() === '') {
            // Only warn for new events, allow editing past events
            if (!confirm('Du skapar en händelse i det förflutna. Är du säker?')) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check for duplicate players
     */
    function checkPlayerDuplicate() {
        var firstName = $('#bkgt-player-first-name').val().trim();
        var lastName = $('#bkgt-player-last-name').val().trim();
        var playerId = $('#bkgt-player-id').val();

        if (!firstName || !lastName) return;

        $.ajax({
            url: bkgt_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'bkgt_check_player_duplicate',
                first_name: firstName,
                last_name: lastName,
                exclude_id: playerId,
                nonce: bkgt_ajax.nonce
            },
            success: function(response) {
                if (response.success && response.data.is_duplicate) {
                    $('#bkgt-player-first-name, #bkgt-player-last-name').addClass('bkgt-field-error');
                    alert('En spelare med samma namn finns redan: ' + response.data.existing_player.first_name + ' ' + response.data.existing_player.last_name);
                } else {
                    $('#bkgt-player-first-name, #bkgt-player-last-name').removeClass('bkgt-field-error');
                }
            }
        });
    }

    /**
     * Check for duplicate jersey numbers
     */
    function checkJerseyNumberDuplicate() {
        var jerseyNumber = $('#bkgt-player-jersey').val();
        var playerId = $('#bkgt-player-id').val();

        if (!jerseyNumber) return;

        $.ajax({
            url: bkgt_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'bkgt_check_jersey_duplicate',
                jersey_number: jerseyNumber,
                exclude_id: playerId,
                nonce: bkgt_ajax.nonce
            },
            success: function(response) {
                if (response.success && response.data.is_duplicate) {
                    $('#bkgt-player-jersey').addClass('bkgt-field-error');
                    alert('Tröjnummer ' + jerseyNumber + ' används redan av: ' + response.data.existing_player.first_name + ' ' + response.data.existing_player.last_name);
                } else {
                    $('#bkgt-player-jersey').removeClass('bkgt-field-error');
                }
            }
        });
    }

    /**
     * Check for duplicate events
     */
    function checkEventDuplicate() {
        var title = $('#bkgt-event-title').val().trim();
        var eventDate = $('#bkgt-event-date').val();
        var eventId = $('#bkgt-event-id').val();

        if (!title || !eventDate) return;

        $.ajax({
            url: bkgt_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'bkgt_check_event_duplicate',
                title: title,
                event_date: eventDate,
                exclude_id: eventId,
                nonce: bkgt_ajax.nonce
            },
            success: function(response) {
                if (response.success && response.data.is_duplicate) {
                    $('#bkgt-event-title').addClass('bkgt-field-error');
                    alert('En liknande händelse finns redan: ' + response.data.existing_event.title + ' (' + response.data.existing_event.event_date + ')');
                } else {
                    $('#bkgt-event-title').removeClass('bkgt-field-error');
                }
            }
        });
    }

    /**
     * Initialize inline editing functionality
     */
    function initInlineEditing() {
        // Double-click to edit player name
        $(document).on('dblclick', '.bkgt-player-card h4', function() {
            makeInlineEditable($(this), 'player-name');
        });

        // Double-click to edit player position
        $(document).on('dblclick', '.bkgt-player-position', function() {
            makeInlineEditable($(this), 'player-position');
        });

        // Double-click to edit player jersey
        $(document).on('dblclick', '.bkgt-player-detail:contains("Tröjnummer") .bkgt-detail-value', function() {
            makeInlineEditable($(this), 'player-jersey');
        });

        // Double-click to edit event title
        $(document).on('dblclick', '.bkgt-event-card h4', function() {
            makeInlineEditable($(this), 'event-title');
        });

        // Double-click to edit event location
        $(document).on('dblclick', '.bkgt-event-detail:contains("Plats") .bkgt-detail-value', function() {
            makeInlineEditable($(this), 'event-location');
        });

        // Save on Enter, cancel on Escape
        $(document).on('keydown', '.bkgt-inline-edit', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                saveInlineEdit($(this));
            } else if (e.key === 'Escape') {
                e.preventDefault();
                cancelInlineEdit($(this));
            }
        });

        // Save on blur (clicking outside)
        $(document).on('blur', '.bkgt-inline-edit', function() {
            saveInlineEdit($(this));
        });
    }

    /**
     * Make an element inline editable
     */
    function makeInlineEditable($element, fieldType) {
        if ($element.hasClass('bkgt-inline-edit')) return; // Already editing

        var currentValue = $element.text().trim();
        var $card = $element.closest('.bkgt-player-card, .bkgt-event-card');
        var itemId = $card.data('player-id') || $card.data('event-id');
        var itemType = $card.hasClass('bkgt-player-card') ? 'player' : 'event';

        // Create input/select based on field type
        var $input;
        switch (fieldType) {
            case 'player-position':
                $input = $('<select class="bkgt-inline-edit">' +
                    '<option value="QB">Quarterback (QB)</option>' +
                    '<option value="RB">Running Back (RB)</option>' +
                    '<option value="WR">Wide Receiver (WR)</option>' +
                    '<option value="TE">Tight End (TE)</option>' +
                    '<option value="OL">Offensive Line (OL)</option>' +
                    '<option value="DL">Defensive Line (DL)</option>' +
                    '<option value="LB">Linebacker (LB)</option>' +
                    '<option value="CB">Cornerback (CB)</option>' +
                    '<option value="S">Safety (S)</option>' +
                    '<option value="K">Kicker (K)</option>' +
                    '<option value="P">Punter (P)</option>' +
                    '</select>');
                $input.val(currentValue);
                break;
            case 'player-jersey':
                $input = $('<input type="number" class="bkgt-inline-edit" min="0" max="99">');
                $input.val(currentValue);
                break;
            default:
                $input = $('<input type="text" class="bkgt-inline-edit">');
                $input.val(currentValue);
                break;
        }

        $input.data('original-value', currentValue);
        $input.data('field-type', fieldType);
        $input.data('item-id', itemId);
        $input.data('item-type', itemType);

        $element.html($input);
        $input.focus().select();
    }

    /**
     * Save inline edit
     */
    function saveInlineEdit($input) {
        var newValue = $input.val().trim();
        var originalValue = $input.data('original-value');
        var fieldType = $input.data('field-type');
        var itemId = $input.data('item-id');
        var itemType = $input.data('item-type');

        // If value hasn't changed, just cancel
        if (newValue === originalValue) {
            cancelInlineEdit($input);
            return;
        }

        // Validate
        if (!validateInlineEdit(fieldType, newValue)) {
            cancelInlineEdit($input);
            return;
        }

        // Save via AJAX
        $.ajax({
            url: bkgt_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'bkgt_save_inline_edit',
                item_type: itemType,
                item_id: itemId,
                field_type: fieldType,
                new_value: newValue,
                nonce: bkgt_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    // Update the display
                    var $element = $input.parent();
                    $element.text(newValue);

                    // Show success indicator
                    showInlineSuccess($element);
                } else {
                    alert(response.data.message || bkgt_ajax.strings.error);
                    cancelInlineEdit($input);
                }
            },
            error: function() {
                alert(bkgt_ajax.strings.error);
                cancelInlineEdit($input);
            }
        });
    }

    /**
     * Cancel inline edit
     */
    function cancelInlineEdit($input) {
        var originalValue = $input.data('original-value');
        $input.parent().text(originalValue);
    }

    /**
     * Validate inline edit value
     */
    function validateInlineEdit(fieldType, value) {
        switch (fieldType) {
            case 'player-name':
            case 'event-title':
                return value.length > 0;
            case 'player-jersey':
                var num = parseInt(value);
                return !isNaN(num) && num >= 0 && num <= 99;
            case 'player-position':
                var validPositions = ['QB', 'RB', 'WR', 'TE', 'OL', 'DL', 'LB', 'CB', 'S', 'K', 'P'];
                return validPositions.includes(value);
            default:
                return true;
        }
    }

    /**
     * Show success indicator for inline edit
     */
    function showInlineSuccess($element) {
        $element.css('background-color', '#d4edda');
        setTimeout(function() {
            $element.css('background-color', '');
        }, 1000);
    }

    /**
     * Initialize bulk assignment wizard
     */
    function initBulkAssignmentWizard() {
        var currentStep = 1;
        var selectedPlayers = [];
        var selectedEvents = [];

        // Launch wizard
        $('#bkgt-bulk-assign').on('click', function() {
            resetWizard();
            loadWizardData();
            $('#bkgt-bulk-assignment-modal').show();
        });

        // Close wizard
        $('.bkgt-wizard-close, #bkgt-bulk-assignment-modal').on('click', function(e) {
            if (e.target === this) {
                $('#bkgt-bulk-assignment-modal').hide();
            }
        });

        // Step navigation
        $('#bkgt-wizard-next').on('click', function() {
            if (validateCurrentStep(currentStep)) {
                goToStep(currentStep + 1);
            }
        });

        $('#bkgt-wizard-prev').on('click', function() {
            goToStep(currentStep - 1);
        });

        // Selection controls
        $('#bkgt-select-all-players').on('click', function() {
            $('.bkgt-bulk-player-item').addClass('selected').find('input[type="checkbox"]').prop('checked', true);
            updateSelectedPlayers();
        });

        $('#bkgt-clear-player-selection').on('click', function() {
            $('.bkgt-bulk-player-item').removeClass('selected').find('input[type="checkbox"]').prop('checked', false);
            updateSelectedPlayers();
        });

        $('#bkgt-select-all-events').on('click', function() {
            $('.bkgt-bulk-event-item').addClass('selected').find('input[type="checkbox"]').prop('checked', true);
            updateSelectedEvents();
        });

        $('#bkgt-clear-event-selection').on('click', function() {
            $('.bkgt-bulk-event-item').removeClass('selected').find('input[type="checkbox"]').prop('checked', false);
            updateSelectedEvents();
        });

        // Item selection
        $(document).on('click', '.bkgt-bulk-player-item', function() {
            $(this).toggleClass('selected');
            $(this).find('input[type="checkbox"]').prop('checked', $(this).hasClass('selected'));
            updateSelectedPlayers();
        });

        $(document).on('click', '.bkgt-bulk-event-item', function() {
            $(this).toggleClass('selected');
            $(this).find('input[type="checkbox"]').prop('checked', $(this).hasClass('selected'));
            updateSelectedEvents();
        });

        // Final assignment
        $('#bkgt-wizard-next').on('click', function() {
            if (currentStep === 3) {
                performBulkAssignment();
            }
        });

        function resetWizard() {
            currentStep = 1;
            selectedPlayers = [];
            selectedEvents = [];
            goToStep(1);
        }

        function goToStep(step) {
            $('.bkgt-wizard-step').removeClass('active');
            $('.bkgt-wizard-step[data-step="' + step + '"]').addClass('active');
            $('.bkgt-step-indicator').removeClass('active');
            $('.bkgt-step-indicator[data-step="' + step + '"]').addClass('active');

            currentStep = step;

            // Update navigation buttons
            $('#bkgt-wizard-prev').toggle(step > 1);
            $('#bkgt-wizard-next').text(step === 3 ? 'Tilldela spelare' : 'Nästa');
        }

        function validateCurrentStep(step) {
            switch (step) {
                case 1:
                    return selectedPlayers.length > 0;
                case 2:
                    return selectedEvents.length > 0;
                case 3:
                    return true;
                default:
                    return false;
            }
        }

        function loadWizardData() {
            // Load players
            $.ajax({
                url: bkgt_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'bkgt_get_bulk_players',
                    nonce: bkgt_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        populatePlayerGrid(response.data);
                    }
                }
            });

            // Load events
            $.ajax({
                url: bkgt_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'bkgt_get_bulk_events',
                    nonce: bkgt_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        populateEventGrid(response.data);
                    }
                }
            });
        }

        function populatePlayerGrid(players) {
            var html = '';
            players.forEach(function(player) {
                html += '<div class="bkgt-bulk-player-item" data-player-id="' + player.id + '">' +
                    '<input type="checkbox">' +
                    '<div class="bkgt-player-info">' +
                        '<strong>' + player.first_name + ' ' + player.last_name + '</strong>' +
                        '<span>' + player.position + ' #' + player.jersey_number + '</span>' +
                    '</div>' +
                '</div>';
            });
            $('#bkgt-bulk-player-list').html(html);
        }

        function populateEventGrid(events) {
            var html = '';
            events.forEach(function(event) {
                var date = new Date(event.event_date).toLocaleDateString('sv-SE');
                html += '<div class="bkgt-bulk-event-item" data-event-id="' + event.id + '">' +
                    '<input type="checkbox">' +
                    '<div class="bkgt-event-info">' +
                        '<strong>' + event.title + '</strong>' +
                        '<span>' + event.event_type + ' - ' + date + '</span>' +
                    '</div>' +
                '</div>';
            });
            $('#bkgt-bulk-event-list').html(html);
        }

        function updateSelectedPlayers() {
            selectedPlayers = [];
            $('.bkgt-bulk-player-item.selected').each(function() {
                var playerId = $(this).data('player-id');
                var playerName = $(this).find('strong').text();
                selectedPlayers.push({id: playerId, name: playerName});
            });
        }

        function updateSelectedEvents() {
            selectedEvents = [];
            $('.bkgt-bulk-event-item.selected').each(function() {
                var eventId = $(this).data('event-id');
                var eventTitle = $(this).find('strong').text();
                selectedEvents.push({id: eventId, title: eventTitle});
            });
        }

        function updateSummary() {
            var playerHtml = '';
            selectedPlayers.forEach(function(player) {
                playerHtml += '<li>' + player.name + '</li>';
            });
            $('#bkgt-selected-players-summary').html(playerHtml);

            var eventHtml = '';
            selectedEvents.forEach(function(event) {
                eventHtml += '<li>' + event.title + '</li>';
            });
            $('#bkgt-selected-events-summary').html(eventHtml);
        }

        // Override next button for step 3
        $(document).on('click', '#bkgt-wizard-next', function() {
            if (currentStep === 3) {
                performBulkAssignment();
            } else if (validateCurrentStep(currentStep)) {
                if (currentStep === 2) {
                    updateSummary();
                }
                goToStep(currentStep + 1);
            } else {
                alert('Vänligen gör ett val innan du fortsätter.');
            }
        });

        function performBulkAssignment() {
            var overwrite = $('#bkgt-overwrite-existing').is(':checked');

            $.ajax({
                url: bkgt_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'bkgt_perform_bulk_assignment',
                    players: selectedPlayers.map(p => p.id),
                    events: selectedEvents.map(e => e.id),
                    overwrite: overwrite,
                    nonce: bkgt_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        alert('Tilldelning slutförd! ' + response.data.assigned + ' tilldelningar skapades.');
                        $('#bkgt-bulk-assignment-modal').hide();
                        location.reload(); // Refresh to show updated assignments
                    } else {
                        alert(response.data.message || bkgt_ajax.strings.error);
                    }
                },
                error: function() {
                    alert(bkgt_ajax.strings.error);
                }
            });
        }
    }
})(jQuery);