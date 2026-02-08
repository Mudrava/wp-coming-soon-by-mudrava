/**
 * Countdown Block – View Script
 *
 * Vanilla JS that hydrates static countdown blocks on the frontend.
 * Reads configuration from data attributes and runs a 1-second interval
 * timer. Dispatches a custom 'mudrava-countdown-expired' event when
 * the countdown reaches zero.
 *
 * @package Mudrava\ComingSoon
 * @since   1.0.0
 */

(function () {
    'use strict';

    /**
     * Pad a number with a leading zero.
     *
     * @param {number} n
     * @return {string}
     */
    function padZero(n) {
        return String(n).padStart(2, '0');
    }

    /**
     * Calculate remaining time.
     *
     * @param {string} targetDate ISO date string.
     * @return {object|null}
     */
    function getTimeRemaining(targetDate) {
        var total = new Date(targetDate).getTime() - Date.now();

        if (total <= 0) return null;

        return {
            days: Math.floor(total / (1000 * 60 * 60 * 24)),
            hours: Math.floor((total / (1000 * 60 * 60)) % 24),
            minutes: Math.floor((total / (1000 * 60)) % 60),
            seconds: Math.floor((total / 1000) % 60),
        };
    }

    /**
     * Initialize a single countdown block element.
     *
     * @param {HTMLElement} el Block root element.
     */
    function initCountdown(el) {
        var targetDate = el.getAttribute('data-target-date');

        if (!targetDate) return;

        var expiredMessage = el.getAttribute('data-expired-message') || 'We have launched!';
        var units = el.querySelectorAll('.wp-block-mudrava-countdown__unit');

        function tick() {
            var remaining = getTimeRemaining(targetDate);

            if (!remaining) {
                /* Countdown expired. */
                el.innerHTML = '<p class="wp-block-mudrava-countdown__expired">' +
                    expiredMessage + '</p>';

                el.dispatchEvent(new CustomEvent('mudrava-countdown-expired', {
                    bubbles: true,
                    detail: { targetDate: targetDate },
                }));

                return false;
            }

            units.forEach(function (unit) {
                var type = unit.getAttribute('data-unit');
                var numberEl = unit.querySelector('.wp-block-mudrava-countdown__number');

                if (numberEl && remaining[type] !== undefined) {
                    numberEl.textContent = padZero(remaining[type]);
                }
            });

            return true;
        }

        /* Initial tick. */
        if (tick()) {
            var timer = setInterval(function () {
                if (!tick()) {
                    clearInterval(timer);
                }
            }, 1000);
        }
    }

    /* Initialize all countdown blocks on the page. */
    function init() {
        var blocks = document.querySelectorAll('.wp-block-mudrava-countdown');

        blocks.forEach(function (block) {
            initCountdown(block);
        });
    }

    /* Run when DOM is ready. */
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
