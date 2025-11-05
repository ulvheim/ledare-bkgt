/**
 * BKGT Shortcode Handlers
 * 
 * Handles all events and interactions for buttons in shortcodes:
 * - Player shortcode buttons (View Details, Edit)
 * - Event shortcode buttons (Details, Edit)
 * - Team overview buttons (View Players, View Events, Edit Team)
 * 
 * @version 1.0.0
 * @requires BKGTModal class for modal operations
 * @requires BKGTForm class for form operations
 */

(function() {
    'use strict';

    /**
     * Initialize shortcode handlers when DOM is ready
     */
    document.addEventListener('DOMContentLoaded', function() {
        initializePlayerHandlers();
        initializeEventHandlers();
        initializeTeamHandlers();
        
        if (window.console && window.console.log) {
            console.log('[BKGT Shortcodes] All handlers initialized successfully');
        }
    });

    /**
     * Initialize player card button handlers
     * 
     * Handles:
     * - .player-view-btn (View Details button)
     * - .player-edit-btn (Edit button - admin only)
     */
    function initializePlayerHandlers() {
        // View player details
        document.addEventListener('click', function(e) {
            const viewBtn = e.target.closest('.player-view-btn');
            if (!viewBtn) return;

            e.preventDefault();
            
            const playerId = viewBtn.getAttribute('data-player-id');
            if (!playerId) {
                console.error('[BKGT] Player ID not found in data attribute');
                return;
            }

            handlePlayerView(playerId);
        });

        // Edit player
        document.addEventListener('click', function(e) {
            const editBtn = e.target.closest('.player-edit-btn');
            if (!editBtn) return;

            e.preventDefault();
            
            const playerId = editBtn.getAttribute('data-player-id');
            if (!playerId) {
                console.error('[BKGT] Player ID not found in data attribute');
                return;
            }

            handlePlayerEdit(playerId);
        });
    }

    /**
     * Initialize event button handlers
     * 
     * Handles:
     * - .event-view-btn (Details button)
     * - .event-edit-btn (Edit button - admin only)
     */
    function initializeEventHandlers() {
        // View event details
        document.addEventListener('click', function(e) {
            const viewBtn = e.target.closest('.event-view-btn');
            if (!viewBtn) return;

            e.preventDefault();
            
            const eventId = viewBtn.getAttribute('data-event-id');
            if (!eventId) {
                console.error('[BKGT] Event ID not found in data attribute');
                return;
            }

            handleEventView(eventId);
        });

        // Edit event
        document.addEventListener('click', function(e) {
            const editBtn = e.target.closest('.event-edit-btn');
            if (!editBtn) return;

            e.preventDefault();
            
            const eventId = editBtn.getAttribute('data-event-id');
            if (!eventId) {
                console.error('[BKGT] Event ID not found in data attribute');
                return;
            }

            handleEventEdit(eventId);
        });
    }

    /**
     * Initialize team button handlers
     * 
     * Handles:
     * - .team-players-btn (View All Players)
     * - .team-events-btn (View Events)
     * - .team-edit-btn (Edit Team - admin only)
     */
    function initializeTeamHandlers() {
        // View all players
        document.addEventListener('click', function(e) {
            const btn = e.target.closest('.team-players-btn');
            if (!btn) return;

            e.preventDefault();
            handleTeamViewPlayers();
        });

        // View team events
        document.addEventListener('click', function(e) {
            const btn = e.target.closest('.team-events-btn');
            if (!btn) return;

            e.preventDefault();
            handleTeamViewEvents();
        });

        // Edit team
        document.addEventListener('click', function(e) {
            const btn = e.target.closest('.team-edit-btn');
            if (!btn) return;

            e.preventDefault();
            handleTeamEdit();
        });
    }

    /**
     * Handle player view details action
     * 
     * @param {string} playerId - The player ID to view
     */
    function handlePlayerView(playerId) {
        // Set loading state on button
        const btn = document.querySelector('[data-player-id="' + playerId + '"].player-view-btn');
        if (btn) {
            btn.setAttribute('data-loading', 'true');
            btn.setAttribute('disabled', 'disabled');
        }

        // Simulate AJAX call to get player details
        // In production, this would fetch from the server
        console.log('[BKGT] View player details - ID:', playerId);

        // Mock data for demonstration
        const playerData = {
            id: playerId,
            name: 'Player Name',
            position: 'Forward',
            age: 25,
            email: 'player@example.com',
            phone: '+46 123 456 789',
            joinDate: '2020-01-15',
            stats: {
                goals: 12,
                assists: 5,
                matches: 18
            }
        };

        // Display in modal (when BKGTModal is available)
        if (typeof BKGTModal !== 'undefined') {
            displayPlayerModal(playerData);
        } else {
            console.warn('[BKGT] BKGTModal not available - cannot display player details');
        }

        // Reset button state
        if (btn) {
            btn.removeAttribute('data-loading');
            btn.removeAttribute('disabled');
        }
    }

    /**
     * Handle player edit action
     * 
     * @param {string} playerId - The player ID to edit
     */
    function handlePlayerEdit(playerId) {
        const btn = document.querySelector('[data-player-id="' + playerId + '"].player-edit-btn');
        if (btn) {
            btn.setAttribute('data-loading', 'true');
            btn.setAttribute('disabled', 'disabled');
        }

        console.log('[BKGT] Edit player - ID:', playerId);

        // In production, this would load the edit form from the server
        // For now, just log the action
        if (typeof BKGTForm !== 'undefined') {
            // Form would be displayed in a modal
            console.log('[BKGT] Ready to display edit form for player', playerId);
        }

        if (btn) {
            btn.removeAttribute('data-loading');
            btn.removeAttribute('disabled');
        }
    }

    /**
     * Handle event view details action
     * 
     * @param {string} eventId - The event ID to view
     */
    function handleEventView(eventId) {
        const btn = document.querySelector('[data-event-id="' + eventId + '"].event-view-btn');
        if (btn) {
            btn.setAttribute('data-loading', 'true');
            btn.setAttribute('disabled', 'disabled');
        }

        console.log('[BKGT] View event details - ID:', eventId);

        // Mock event data
        const eventData = {
            id: eventId,
            title: 'Event Title',
            date: '2025-11-02',
            time: '19:00',
            location: 'Stadium Name',
            opponent: 'Opponent Team',
            type: 'match',
            description: 'Event description goes here'
        };

        if (typeof BKGTModal !== 'undefined') {
            displayEventModal(eventData);
        } else {
            console.warn('[BKGT] BKGTModal not available - cannot display event details');
        }

        if (btn) {
            btn.removeAttribute('data-loading');
            btn.removeAttribute('disabled');
        }
    }

    /**
     * Handle event edit action
     * 
     * @param {string} eventId - The event ID to edit
     */
    function handleEventEdit(eventId) {
        const btn = document.querySelector('[data-event-id="' + eventId + '"].event-edit-btn');
        if (btn) {
            btn.setAttribute('data-loading', 'true');
            btn.setAttribute('disabled', 'disabled');
        }

        console.log('[BKGT] Edit event - ID:', eventId);

        if (typeof BKGTForm !== 'undefined') {
            console.log('[BKGT] Ready to display edit form for event', eventId);
        }

        if (btn) {
            btn.removeAttribute('data-loading');
            btn.removeAttribute('disabled');
        }
    }

    /**
     * Handle team - view all players action
     */
    function handleTeamViewPlayers() {
        console.log('[BKGT] Team: View all players');
        // In production, could navigate to a page or show a modal with all players
    }

    /**
     * Handle team - view events action
     */
    function handleTeamViewEvents() {
        console.log('[BKGT] Team: View all events');
        // In production, could navigate to a page or show a modal with all events
    }

    /**
     * Handle team - edit action
     */
    function handleTeamEdit() {
        console.log('[BKGT] Team: Edit team information');
        // In production, would open edit form in a modal
    }

    /**
     * Display player details in modal
     * 
     * @param {object} playerData - Player data to display
     */
    function displayPlayerModal(playerData) {
        // Validate player data
        if (!playerData || !playerData.id) {
            console.error('[BKGT] Invalid player data provided to displayPlayerModal');
            return;
        }

        const modalContent = `
            <div class="bkgt-player-details">
                <h4>${escapeHtml(playerData.name)}</h4>
                <div class="player-info">
                    <p><strong>Position:</strong> ${escapeHtml(playerData.position)}</p>
                    <p><strong>Age:</strong> ${parseInt(playerData.age) || 'N/A'}</p>
                    <p><strong>Email:</strong> <a href="mailto:${escapeHtml(playerData.email)}">${escapeHtml(playerData.email)}</a></p>
                    <p><strong>Phone:</strong> ${escapeHtml(playerData.phone)}</p>
                    <p><strong>Join Date:</strong> ${escapeHtml(playerData.joinDate)}</p>
                </div>
                <div class="player-stats">
                    <h5>Statistics</h5>
                    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem;">
                        <div>
                            <p style="font-size: 2em; margin: 0; color: var(--bkgt-primary, #007bff);">${parseInt(playerData.stats.goals) || 0}</p>
                            <p style="margin: 0.25rem 0 0 0; font-size: 0.875em;"><strong>Goals</strong></p>
                        </div>
                        <div>
                            <p style="font-size: 2em; margin: 0; color: var(--bkgt-secondary, #6c757d);">${parseInt(playerData.stats.assists) || 0}</p>
                            <p style="margin: 0.25rem 0 0 0; font-size: 0.875em;"><strong>Assists</strong></p>
                        </div>
                        <div>
                            <p style="font-size: 2em; margin: 0; color: var(--bkgt-success, #28a745);">${parseInt(playerData.stats.matches) || 0}</p>
                            <p style="margin: 0.25rem 0 0 0; font-size: 0.875em;"><strong>Matches</strong></p>
                        </div>
                    </div>
                </div>
            </div>
        `;

        // Create and display modal if BKGTModal is available
        if (typeof BKGTModal !== 'undefined') {
            const modal = new BKGTModal({
                id: 'player-modal-' + playerData.id,
                title: 'Player Details: ' + escapeHtml(playerData.name),
                content: modalContent,
                buttons: [
                    { text: 'Edit', action: 'edit', variant: 'primary' },
                    { text: 'Close', action: 'close', variant: 'secondary' }
                ]
            });
            modal.open();

            // Handle edit button in modal
            modal.onButtonClick = function(action) {
                if (action === 'edit') {
                    handlePlayerEdit(playerData.id);
                }
            };
        } else {
            console.warn('[BKGT] BKGTModal not available - cannot display player details modal');
        }
    }

    /**
     * Display event details in modal
     * 
     * @param {object} eventData - Event data to display
     */
    function displayEventModal(eventData) {
        // Validate event data
        if (!eventData || !eventData.id) {
            console.error('[BKGT] Invalid event data provided to displayEventModal');
            return;
        }

        const modalContent = `
            <div class="bkgt-event-details">
                <h4>${escapeHtml(eventData.title)}</h4>
                <div class="event-info" style="background: var(--bkgt-light-bg, #f8f9fa); padding: 1rem; border-radius: 4px; margin: 1rem 0;">
                    <p>
                        <strong>📅 Date:</strong> ${escapeHtml(eventData.date)}<br>
                        <strong>🕐 Time:</strong> ${escapeHtml(eventData.time)}<br>
                        <strong>📍 Location:</strong> ${escapeHtml(eventData.location)}<br>
                        <strong>🏟️ Opponent:</strong> ${escapeHtml(eventData.opponent)}<br>
                        <strong>🏷️ Type:</strong> ${escapeHtml(eventData.type)}
                    </p>
                </div>
                <div class="event-description">
                    <p>${escapeHtml(eventData.description)}</p>
                </div>
            </div>
        `;

        if (typeof BKGTModal !== 'undefined') {
            const modal = new BKGTModal({
                id: 'event-modal-' + eventData.id,
                title: 'Event Details: ' + escapeHtml(eventData.title),
                content: modalContent,
                buttons: [
                    { text: 'Edit', action: 'edit', variant: 'primary' },
                    { text: 'Close', action: 'close', variant: 'secondary' }
                ]
            });
            modal.open();

            // Handle edit button in modal
            modal.onButtonClick = function(action) {
                if (action === 'edit') {
                    handleEventEdit(eventData.id);
                }
            };
        } else {
            console.warn('[BKGT] BKGTModal not available - cannot display event details modal');
        }
    }

    /**
     * Utility: Escape HTML to prevent XSS
     * 
     * @param {string} text - Text to escape
     * @returns {string} Escaped text
     */
    function escapeHtml(text) {
        if (!text) return '';
        
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        
        return String(text).replace(/[&<>"']/g, function(m) {
            return map[m];
        });
    }

    /**
     * Utility: Show loading state on button
     * 
     * @param {element} btn - Button element
     * @param {boolean} loading - Loading state
     */
    function setButtonLoading(btn, loading) {
        if (!btn) return;

        if (loading) {
            btn.setAttribute('data-loading', 'true');
            btn.setAttribute('disabled', 'disabled');
        } else {
            btn.removeAttribute('data-loading');
            btn.removeAttribute('disabled');
        }
    }

    /**
     * Utility: Log action for debugging
     * 
     * @param {string} action - Action name
     * @param {string} details - Additional details
     */
    function logAction(action, details) {
        if (window.console && window.console.log) {
            console.log('[BKGT Shortcodes] ' + action + ' - ' + details);
        }
    }

    /**
     * Utility: Make AJAX call to server
     * Used for loading player/event data, saving forms, etc.
     * 
     * @param {object} options - AJAX options
     * @returns {Promise} Promise that resolves with response
     */
    function makeAjaxCall(options) {
        return new Promise(function(resolve, reject) {
            const defaultOptions = {
                action: '',
                data: {},
                method: 'POST',
                nonce: null
            };

            const config = Object.assign({}, defaultOptions, options);

            // Build form data
            const formData = new FormData();
            formData.append('action', config.action);

            if (config.nonce) {
                formData.append('nonce', config.nonce);
            }

            // Add custom data
            Object.keys(config.data).forEach(function(key) {
                formData.append(key, config.data[key]);
            });

            // Make fetch call
            fetch(window.ajaxurl || '/wp-admin/admin-ajax.php', {
                method: config.method,
                body: formData,
                credentials: 'same-origin',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(function(response) {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(function(data) {
                resolve(data);
            })
            .catch(function(error) {
                console.error('[BKGT] AJAX Error:', error);
                reject(error);
            });
        });
    }

})();
