/**
 * BKGT Ledare Accessibility JavaScript
 * Enhances keyboard navigation and screen reader support
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        // Focus management for skip links
        $('.skip-link').on('click', function(e) {
            e.preventDefault();
            var target = $(this).attr('href');
            $(target).attr('tabindex', '-1').focus();
        });

        // Enhanced keyboard navigation for menus
        $('.menu-item a').on('focus', function() {
            $(this).parent().addClass('focus');
        }).on('blur', function() {
            $(this).parent().removeClass('focus');
        });

        // Add ARIA expanded states for dropdown menus (if implemented)
        $('.menu-item-has-children > a').on('click', function(e) {
            var $parent = $(this).parent();
            var expanded = $parent.attr('aria-expanded') === 'true';
            $parent.attr('aria-expanded', !expanded);
        });

        // Focus trap for modal dialogs (if implemented)
        function trapFocus(element) {
            var focusableEls = element.querySelectorAll('a[href]:not([disabled]), button:not([disabled]), textarea:not([disabled]), input[type="text"]:not([disabled]), input[type="radio"]:not([disabled]), input[type="checkbox"]:not([disabled]), select:not([disabled])');
            var firstFocusableEl = focusableEls[0];
            var lastFocusableEl = focusableEls[focusableEls.length - 1];
            var KEYCODE_TAB = 9;

            element.addEventListener('keydown', function(e) {
                if (e.key !== 'Tab' && e.keyCode !== KEYCODE_TAB) return;

                if (e.shiftKey) {
                    if (document.activeElement === firstFocusableEl) {
                        lastFocusableEl.focus();
                        e.preventDefault();
                    }
                } else {
                    if (document.activeElement === lastFocusableEl) {
                        firstFocusableEl.focus();
                        e.preventDefault();
                    }
                }
            });
        }

        // Announce dynamic content changes to screen readers
        window.bkgtAnnounceToScreenReader = function(message) {
            var announcement = document.createElement('div');
            announcement.setAttribute('aria-live', 'polite');
            announcement.setAttribute('aria-atomic', 'true');
            announcement.style.position = 'absolute';
            announcement.style.left = '-10000px';
            announcement.style.width = '1px';
            announcement.style.height = '1px';
            announcement.style.overflow = 'hidden';

            announcement.textContent = message;
            document.body.appendChild(announcement);

            setTimeout(function() {
                document.body.removeChild(announcement);
            }, 1000);
        };

        // High contrast mode detection
        function detectHighContrast() {
            var testElement = document.createElement('div');
            testElement.style.color = 'rgb(31, 41, 55)';
            testElement.style.backgroundColor = 'rgb(255, 255, 255)';
            testElement.style.position = 'absolute';
            testElement.style.left = '-9999px';
            testElement.style.top = '-9999px';
            document.body.appendChild(testElement);

            var computedStyle = window.getComputedStyle(testElement);
            var isHighContrast = computedStyle.color === computedStyle.backgroundColor;

            document.body.removeChild(testElement);
            return isHighContrast;
        }

        // Add high contrast class if detected
        if (detectHighContrast()) {
            document.documentElement.classList.add('high-contrast');
        }

        // Keyboard shortcut for skip links (Ctrl/Cmd + Home)
        $(document).on('keydown', function(e) {
            if ((e.ctrlKey || e.metaKey) && e.keyCode === 36) { // Home key
                e.preventDefault();
                $('.skip-link:first').focus();
            }
        });
    });

})(jQuery);