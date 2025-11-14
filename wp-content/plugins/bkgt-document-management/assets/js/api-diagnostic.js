/**
 * API Diagnostics JavaScript
 * Handles testing of all API endpoints
 */

jQuery(document).ready(function($) {
    console.log('API Diagnostics JavaScript loaded');

    // Test single endpoint
    $(document).on('click', '.bkgt-test-endpoint', function() {
        console.log('Single endpoint test clicked');
        const action = $(this).data('action');
        const type = $(this).data('type');
        testEndpoint(action, type);
    });

    // Test all endpoints
    $(document).on('click', '#bkgt-test-all-endpoints', function() {
        console.log('Test all endpoints button clicked');
        const $button = $(this);
        const originalText = $button.text();
        $button.text(bkgt_diagnostic_strings.testing).prop('disabled', true);

        const endpoints = $('.bkgt-test-endpoint');
        console.log('Found', endpoints.length, 'endpoints to test');
        let completed = 0;

        endpoints.each(function(index) {
            const $endpoint = $(this);
            const action = $endpoint.data('action');
            const type = $endpoint.data('type');

            // Stagger requests by 200ms each to avoid overwhelming the server
            setTimeout(() => {
                console.log('Testing endpoint:', action, type);
                testEndpoint(action, type, function() {
                    completed++;
                    console.log('Completed', completed, 'of', endpoints.length);
                    if (completed === endpoints.length) {
                        $button.text(originalText).prop('disabled', false);
                    }
                });
            }, index * 200);
        });
    });

    // Clear results
    $(document).on('click', '#bkgt-clear-results', function() {
        $('.bkgt-test-result').html('<em>' + bkgt_diagnostic_strings.not_tested + '</em>').removeClass('success error warning');
    });

    function testEndpoint(action, type, callback) {
        const resultId = 'result-' + action.replace(/\//g, '-');
        const resultDiv = $('#' + resultId);
        resultDiv.html('<em>' + bkgt_diagnostic_strings.testing + '</em>').removeClass('success error warning');

        // Get endpoint config from localized data
        const config = bkgt_diagnostic_endpoints[action];

        if (!config) {
            resultDiv.html('<strong>' + bkgt_diagnostic_strings.error + ':</strong> ' + bkgt_diagnostic_strings.config_missing).addClass('error');
            if (callback) callback();
            return;
        }

        if (type === 'ajax') {
            // AJAX endpoint test
            const ajaxData = {
                action: action,
                nonce: bkgt_diagnostic_nonce,
                ...config.parameters
            };

            $.ajax({
                url: ajaxurl,
                type: config.method,
                data: ajaxData,
                success: function(response) {
                    if (response.success) {
                        resultDiv.html('<strong>' + bkgt_diagnostic_strings.success + ':</strong> ' + JSON.stringify(response.data, null, 2)).addClass('success');
                    } else {
                        resultDiv.html('<strong>' + bkgt_diagnostic_strings.api_error + ':</strong> ' + (response.data || bkgt_diagnostic_strings.unknown_error)).addClass('error');
                    }
                    if (callback) callback();
                },
                error: function(xhr, status, error) {
                    let errorMsg = bkgt_diagnostic_strings.http_error + ': ' + xhr.status + ' ' + xhr.statusText;
                    if (xhr.responseJSON && xhr.responseJSON.data) {
                        errorMsg += ' - ' + xhr.responseJSON.data;
                    }
                    resultDiv.html('<strong>' + bkgt_diagnostic_strings.error + ':</strong> ' + errorMsg).addClass('error');
                    if (callback) callback();
                }
            });
        } else if (type === 'rest') {
            // REST API endpoint test
            let url = bkgt_diagnostic_site_url + '/' + action;

            // Add query parameters if any
            if (Object.keys(config.parameters).length > 0) {
                const params = new URLSearchParams(config.parameters);
                url += '?' + params.toString();
            }

            $.ajax({
                url: url,
                type: config.method,
                success: function(response, status, xhr) {
                    const statusCode = xhr.status;
                    const expectedStatus = config.expected_status || 200;

                    if (statusCode === expectedStatus) {
                        resultDiv.html('<strong>' + bkgt_diagnostic_strings.expected_response + ':</strong> HTTP ' + statusCode + ' (' + bkgt_diagnostic_strings.as_expected + ')').addClass('success');
                    } else {
                        resultDiv.html('<strong>' + bkgt_diagnostic_strings.unexpected_response + ':</strong> HTTP ' + statusCode + ' (' + bkgt_diagnostic_strings.expected + ' ' + expectedStatus + ')').addClass('warning');
                    }
                    if (callback) callback();
                },
                error: function(xhr, status, error) {
                    const statusCode = xhr.status;
                    const expectedStatus = config.expected_status || 200;

                    if (statusCode === expectedStatus) {
                        resultDiv.html('<strong>' + bkgt_diagnostic_strings.expected_error + ':</strong> HTTP ' + statusCode + ' (' + bkgt_diagnostic_strings.as_expected + ')').addClass('success');
                    } else {
                        let errorMsg = bkgt_diagnostic_strings.http_error + ': ' + statusCode + ' ' + xhr.statusText;
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMsg += ' - ' + xhr.responseJSON.message;
                        }
                        resultDiv.html('<strong>' + bkgt_diagnostic_strings.error + ':</strong> ' + errorMsg + ' (' + bkgt_diagnostic_strings.expected + ' ' + expectedStatus + ')').addClass('error');
                    }
                    if (callback) callback();
                }
            });
        }
    }
});
