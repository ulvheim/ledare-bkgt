/**
 * Frontend JavaScript for BKGT Data Scraping plugin
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        // Initialize components
        initPlayerFilters();
        initEventFilters();
        initLazyLoading();
    });

    /**
     * Initialize player filtering
     */
    function initPlayerFilters() {
        $('.bkgt-player-filter').on('change', function() {
            var $container = $(this).closest('.bkgt-players-container');
            var filters = getPlayerFilters($container);

            filterPlayers($container, filters);
        });
    }

    /**
     * Get player filters from form
     */
    function getPlayerFilters($container) {
        return {
            position: $container.find('.bkgt-filter-position').val(),
            status: $container.find('.bkgt-filter-status').val(),
            search: $container.find('.bkgt-search-players').val().toLowerCase()
        };
    }

    /**
     * Filter players based on criteria
     */
    function filterPlayers($container, filters) {
        $container.find('.bkgt-player-card').each(function() {
            var $card = $(this);
            var show = true;

            // Position filter
            if (filters.position && $card.data('position') !== filters.position) {
                show = false;
            }

            // Status filter
            if (filters.status && $card.data('status') !== filters.status) {
                show = false;
            }

            // Search filter
            if (filters.search) {
                var name = $card.find('.bkgt-player-name').text().toLowerCase();
                if (name.indexOf(filters.search) === -1) {
                    show = false;
                }
            }

            $card.toggle(show);
        });
    }

    /**
     * Initialize event filtering
     */
    function initEventFilters() {
        $('.bkgt-event-filter').on('change', function() {
            var $container = $(this).closest('.bkgt-events-container');
            var filters = getEventFilters($container);

            filterEvents($container, filters);
        });
    }

    /**
     * Get event filters from form
     */
    function getEventFilters($container) {
        return {
            type: $container.find('.bkgt-filter-type').val(),
            upcoming: $container.find('.bkgt-filter-upcoming').is(':checked')
        };
    }

    /**
     * Filter events based on criteria
     */
    function filterEvents($container, filters) {
        $container.find('.bkgt-event-item').each(function() {
            var $event = $(this);
            var show = true;

            // Type filter
            if (filters.type && $event.data('type') !== filters.type) {
                show = false;
            }

            // Upcoming filter
            if (filters.upcoming) {
                var eventDate = new Date($event.data('date'));
                var today = new Date();
                if (eventDate < today) {
                    show = false;
                }
            }

            $event.toggle(show);
        });
    }

    /**
     * Initialize lazy loading for performance
     */
    function initLazyLoading() {
        // Use Intersection Observer for lazy loading if available
        if ('IntersectionObserver' in window) {
            var observer = new IntersectionObserver(function(entries) {
                entries.forEach(function(entry) {
                    if (entry.isIntersecting) {
                        var $element = $(entry.target);
                        if ($element.hasClass('bkgt-lazy-load')) {
                            loadLazyContent($element);
                            observer.unobserve(entry.target);
                        }
                    }
                });
            });

            $('.bkgt-lazy-load').each(function() {
                observer.observe(this);
            });
        } else {
            // Fallback for older browsers
            loadAllLazyContent();
        }
    }

    /**
     * Load lazy content
     */
    function loadLazyContent($element) {
        var type = $element.data('lazy-type');
        var id = $element.data('lazy-id');

        $element.addClass('bkgt-loading');

        $.ajax({
            url: bkgt_frontend.ajax_url,
            type: 'POST',
            data: {
                action: 'bkgt_load_lazy_content',
                type: type,
                id: id,
                nonce: bkgt_frontend.nonce
            },
            success: function(response) {
                if (response.success) {
                    $element.html(response.data.content);
                } else {
                    $element.html('<p>' + bkgt_frontend.strings.error + '</p>');
                }
            },
            error: function() {
                $element.html('<p>' + bkgt_frontend.strings.error + '</p>');
            },
            complete: function() {
                $element.removeClass('bkgt-loading bkgt-lazy-load');
            }
        });
    }

    /**
     * Load all lazy content (fallback)
     */
    function loadAllLazyContent() {
        $('.bkgt-lazy-load').each(function() {
            loadLazyContent($(this));
        });
    }

    /**
     * Show loading state
     */
    function showLoading($element) {
        $element.html('<div class="bkgt-loading"><div class="bkgt-spinner"></div><p>' + bkgt_frontend.strings.loading + '</p></div>');
    }

    /**
     * Show error state
     */
    function showError($element, message) {
        $element.html('<div class="bkgt-error"><p>' + (message || bkgt_frontend.strings.error) + '</p></div>');
    }

    /**
     * Show no data state
     */
    function showNoData($element, message) {
        $element.html('<p>' + (message || bkgt_frontend.strings.no_data) + '</p>');
    }

})(jQuery);