/**
 * BKGT Frontend Logger
 * 
 * Simple client-side logging utility that sends logs to browser console
 * and optionally to the server via AJAX.
 * 
 * This is a companion to the PHP BKGT_Logger class,
 * providing logging capabilities in JavaScript/frontend.
 * 
 * @package BKGT_Core
 * @since 1.0.0
 */

(function() {
    'use strict';
    
    /**
     * Global BKGT Logger
     */
    window.bkgt_log = function(message, level = 'info', context = {}) {
        // Levels: 'debug', 'info', 'warning', 'error'
        const validLevels = ['debug', 'info', 'warning', 'error'];
        
        // Normalize level
        if (!validLevels.includes(level)) {
            level = 'info';
        }
        
        // Build log message
        const timestamp = new Date().toISOString();
        const fullMessage = `[${timestamp}] [${level.toUpperCase()}] ${message}`;
        
        // Log to browser console
        switch (level) {
            case 'debug':
                console.debug(fullMessage, context);
                break;
            case 'info':
                console.log(fullMessage, context);
                break;
            case 'warning':
                console.warn(fullMessage, context);
                break;
            case 'error':
                console.error(fullMessage, context);
                break;
        }
        
        // Optionally send to server (only for error and warning)
        if (window.bkgtFormConfig && window.bkgtFormConfig.ajaxurl && (level === 'error' || level === 'warning')) {
            bkgt_log_to_server(message, level, context);
        }
    };
    
    /**
     * Send log to server via AJAX
     * 
     * @private
     */
    function bkgt_log_to_server(message, level, context) {
        if (!navigator.sendBeacon) {
            // Fallback to fetch if sendBeacon not available
            fetch(window.bkgtFormConfig.ajaxurl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: new URLSearchParams({
                    action: 'bkgt_log_frontend',
                    nonce: window.bkgtFormConfig.nonce || '',
                    message: message,
                    level: level,
                    context: JSON.stringify(context)
                }).toString(),
                keepalive: true
            }).catch(() => {
                // Silently fail - don't spam console
            });
        } else {
            // Use sendBeacon for reliable delivery
            navigator.sendBeacon(
                window.bkgtFormConfig.ajaxurl,
                new URLSearchParams({
                    action: 'bkgt_log_frontend',
                    nonce: window.bkgtFormConfig.nonce || '',
                    message: message,
                    level: level,
                    context: JSON.stringify(context)
                }).toString()
            );
        }
    }
    
    /**
     * Debug helper function
     */
    window.bkgt_debug = function(message, data) {
        window.bkgt_log(message, 'debug', data);
    };
    
    /**
     * Info helper function
     */
    window.bkgt_info = function(message, data) {
        window.bkgt_log(message, 'info', data);
    };
    
    /**
     * Warning helper function
     */
    window.bkgt_warn = function(message, data) {
        window.bkgt_log(message, 'warning', data);
    };
    
    /**
     * Error helper function
     */
    window.bkgt_error = function(message, data) {
        window.bkgt_log(message, 'error', data);
    };
    
    // Log that frontend logger is initialized
    if (window.bkgtFormConfig) {
        console.debug('[BKGT] Frontend logger initialized');
    }
})();
