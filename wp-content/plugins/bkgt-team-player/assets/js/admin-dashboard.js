/**
 * BKGT Team Player Admin Dashboard JavaScript
 */

jQuery(document).ready(function($) {
    'use strict';

    // Tab navigation functionality
    $('.bkgt-tab-nav .nav-tab').on('click', function(e) {
        e.preventDefault();

        var $this = $(this);
        var targetTab = $this.data('tab');

        // Update active tab
        $('.bkgt-tab-nav .nav-tab').removeClass('nav-tab-active');
        $this.addClass('nav-tab-active');

        // Update URL hash without page reload
        if (history.pushState) {
            history.pushState(null, null, '#' + targetTab);
        }

        // Show corresponding tab content
        $('.bkgt-tab-content > div').hide();
        $('#' + targetTab + '-tab').show();
    });

    // Handle initial tab based on URL hash
    var hash = window.location.hash.substring(1);
    if (hash) {
        $('.bkgt-tab-nav .nav-tab[data-tab="' + hash + '"]').trigger('click');
    } else {
        // Default to overview tab
        $('.bkgt-tab-nav .nav-tab[data-tab="overview"]').trigger('click');
    }

    // Quick action buttons - add loading states
    $('.bkgt-action-buttons .button').on('click', function() {
        var $button = $(this);
        var originalText = $button.html();

        // Add loading state
        $button.prop('disabled', true).html('<span class="dashicons dashicons-update spin"></span> ' + bkgt_admin_dashboard.loading);

        // Re-enable after 2 seconds (for demo purposes)
        setTimeout(function() {
            $button.prop('disabled', false).html(originalText);
        }, 2000);
    });

    // Team card actions
    $('.bkgt-team-card .button').on('click', function(e) {
        var $button = $(this);
        var action = $button.hasClass('button-primary') ? 'edit' : 'view';

        // Add visual feedback
        $button.css('opacity', '0.7');

        setTimeout(function() {
            $button.css('opacity', '1');
        }, 200);
    });

    // Player card actions
    $('.bkgt-player-card .button').on('click', function(e) {
        var $button = $(this);
        var action = $button.hasClass('dashicons-visibility') ? 'view' : 'edit';

        // Add visual feedback
        $button.css('opacity', '0.7');

        setTimeout(function() {
            $button.css('opacity', '1');
        }, 200);
    });

    // Performance stats refresh (if needed)
    $('.bkgt-performance-tab .button-primary').on('click', function() {
        var $button = $(this);

        $button.prop('disabled', true).html('<span class="dashicons dashicons-update spin"></span> Laddar...');

        // Simulate loading
        setTimeout(function() {
            $button.prop('disabled', false).html('<span class="dashicons dashicons-chart-bar"></span> Hantera Betyg');
        }, 1500);
    });

    // Settings section interactions
    $('.bkgt-settings-section .button').on('click', function() {
        var $button = $(this);
        var section = $button.closest('.bkgt-settings-section').find('h3').text();

        // For demo purposes, show alert
        if ($button.prop('disabled')) {
            alert('Denna funktion är under utveckling. ' + section);
        }
    });

    // Add spin animation for loading icons
    if (!$('#bkgt-admin-styles').length) {
        $('head').append('<style id="bkgt-admin-styles">.dashicons.spin { animation: spin 1s linear infinite; } @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }</style>');
    }

    // Handle browser back/forward navigation
    $(window).on('popstate', function() {
        var hash = window.location.hash.substring(1);
        if (hash) {
            $('.bkgt-tab-nav .nav-tab[data-tab="' + hash + '"]').trigger('click');
        }
    });

    // Initialize tooltips for better UX (if available)
    if (typeof tippy !== 'undefined') {
        tippy('.bkgt-action-buttons .button', {
            content: function(reference) {
                return reference.getAttribute('title') || reference.textContent;
            }
        });
    }

    // Add keyboard navigation support
    $(document).on('keydown', function(e) {
        if (e.altKey) {
            var key = e.key.toLowerCase();
            var tabMap = {
                'o': 'overview',
                'l': 'teams',
                's': 'players',
                'm': 'events',
                'p': 'performance',
                'i': 'settings'
            };

            if (tabMap[key]) {
                e.preventDefault();
                $('.bkgt-tab-nav .nav-tab[data-tab="' + tabMap[key] + '"]').trigger('click');
            }
        }
    });

    // Auto-refresh metrics every 5 minutes
    setInterval(function() {
        // This would typically make an AJAX call to refresh metrics
        console.log('Refreshing dashboard metrics...');
    }, 300000);

});

// Localize script variables
var bkgt_admin_dashboard = bkgt_admin_dashboard || {
    loading: 'Laddar...',
    error: 'Ett fel uppstod',
    success: 'Lyckades'
};