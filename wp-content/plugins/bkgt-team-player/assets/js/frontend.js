/**
 * BKGT Team & Player Management - Frontend JavaScript
 */

(function($) {
    'use strict';

    $(document).ready(function() {

        // Team selector change handler
        $('#performance_team_select').on('change', function() {
            var teamSlug = $(this).val();
            if (teamSlug) {
                var newUrl = updateQueryStringParameter(window.location.href, 'team', teamSlug);
                window.location.href = newUrl;
            }
        });

        // Performance rating form submission
        $('#bkgt-performance-form').on('submit', function(e) {
            e.preventDefault();

            var formData = new FormData(this);
            formData.append('action', 'bkgt_save_performance_rating');
            formData.append('nonce', bkgt_tp_ajax.nonce);

            $.ajax({
                url: bkgt_tp_ajax.ajax_url,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                beforeSend: function() {
                    $('input[type="submit"]', '#bkgt-performance-form').prop('disabled', true).val('Saving...');
                },
                success: function(response) {
                    if (response.success) {
                        alert(response.data.message);
                        // Redirect back to performance page
                        window.location.href = removeQueryStringParameter(window.location.href, 'action');
                    } else {
                        alert(response.data.message || 'Error saving rating');
                    }
                },
                error: function() {
                    alert('Error saving performance rating. Please try again.');
                },
                complete: function() {
                    $('input[type="submit"]', '#bkgt-performance-form').prop('disabled', false).val('Save Rating');
                }
            });
        });

        // Load players when team is selected in rating form
        $('#rating_team').on('change', function() {
            var teamId = $(this).val();
            if (teamId) {
                loadTeamPlayers(teamId);
            } else {
                $('#rating_player').html('<option value="">Select Player</option>');
            }
        });

        // Auto-calculate overall rating
        $('#enthusiasm_rating, #performance_rating, #skill_rating').on('change', function() {
            var enthusiasm = parseInt($('#enthusiasm_rating').val()) || 0;
            var performance = parseInt($('#performance_rating').val()) || 0;
            var skill = parseInt($('#skill_rating').val()) || 0;

            if (enthusiasm && performance && skill) {
                var overall = ((enthusiasm + performance + skill) / 3).toFixed(1);
                // Could display this somewhere if needed
                console.log('Overall rating: ' + overall);
            }
        });

    });

    // Helper function to load team players
    function loadTeamPlayers(teamId) {
        $.ajax({
            url: bkgt_tp_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'bkgt_get_team_players',
                team_id: teamId,
                nonce: bkgt_tp_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    var options = '<option value="">Select Player</option>';
                    $.each(response.data.players, function(index, player) {
                        options += '<option value="' + player.id + '">' + player.display_name + '</option>';
                    });
                    $('#rating_player').html(options);
                } else {
                    $('#rating_player').html('<option value="">Error loading players</option>');
                }
            },
            error: function() {
                $('#rating_player').html('<option value="">Error loading players</option>');
            }
        });
    }

    // Helper function to update query string parameter
    function updateQueryStringParameter(uri, key, value) {
        var re = new RegExp("([?&])" + key + "=.*?(&|$)", "i");
        var separator = uri.indexOf('?') !== -1 ? "&" : "?";
        if (uri.match(re)) {
            return uri.replace(re, '$1' + key + "=" + value + '$2');
        } else {
            return uri + separator + key + "=" + value;
        }
    }

    // Helper function to remove query string parameter
    function removeQueryStringParameter(url, parameter) {
        var urlParts = url.split('?');
        if (urlParts.length >= 2) {
            var prefix = encodeURIComponent(parameter) + '=';
            var parts = urlParts[1].split(/[&;]/g);

            for (var i = parts.length; i-- > 0;) {
                if (parts[i].lastIndexOf(prefix, 0) !== -1) {
                    parts.splice(i, 1);
                }
            }

            url = urlParts[0] + (parts.length > 0 ? '?' + parts.join('&') : '');
        }
        return url;
    }

})(jQuery);