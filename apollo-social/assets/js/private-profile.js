/**
 * Private Profile JavaScript
 *
 * @package Apollo_Social
 */

/* global document */

document.addEventListener('DOMContentLoaded', function () {
    'use strict';

    // Tab functionality
    var tabs = document.querySelectorAll('[role="tab"]');
    var panels = document.querySelectorAll('[role="tabpanel"]');

    tabs.forEach(function (tab) {
        tab.addEventListener('click', function () {
            // Deactivate all tabs
            tabs.forEach(function (t) {
                t.classList.remove('active');
                t.setAttribute('aria-selected', 'false');
            });

            // Hide all panels
            panels.forEach(function (p) {
                p.classList.remove('active');
            });

            // Activate clicked tab
            this.classList.add('active');
            this.setAttribute('aria-selected', 'true');

            // Show corresponding panel
            var target = this.getAttribute('data-tab-target');
            var panel = document.querySelector('[data-tab-panel="' + target + '"]');
            if (panel) {
                panel.classList.add('active');
            }
        });
    });
});
