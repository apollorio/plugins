/**
 * Motion.dev Dashboard Tabs
 * 
 * Smooth tab transitions with Motion.dev and layoutId
 * 
 * @package Apollo_Events_Manager
 * @version 0.1.0
 */

(function() {
    'use strict';

    /**
     * Initialize tab system
     */
    function initTabs() {
        const tabsContainer = document.querySelector('[data-motion-tabs]');
        if (!tabsContainer) {
            return;
        }

        const tabs = tabsContainer.querySelectorAll('[data-tab-trigger]');
        const panels = tabsContainer.querySelectorAll('[data-tab-panel]');
        
        if (tabs.length === 0 || panels.length === 0) {
            return;
        }

        // Create underline indicator
        const indicator = document.createElement('div');
        indicator.className = 'tab-indicator';
        indicator.style.cssText = `
            position: absolute;
            bottom: 0;
            height: 3px;
            background: #007cba;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            border-radius: 3px 3px 0 0;
        `;
        
        const tabsList = tabs[0].parentElement;
        if (tabsList && tabsList.style.position !== 'relative') {
            tabsList.style.position = 'relative';
        }
        tabsList?.appendChild(indicator);

        /**
         * Update indicator position
         * TODO 99: Enhanced with layoutId for smooth transitions
         */
        function updateIndicator(tab) {
            const rect = tab.getBoundingClientRect();
            const parentRect = tab.parentElement.getBoundingClientRect();
            
            // TODO 99: Add layoutId for smooth underline animation
            if (!indicator.hasAttribute('data-layout-id')) {
                indicator.setAttribute('data-layout-id', 'tab-indicator');
            }
            
            indicator.style.width = rect.width + 'px';
            indicator.style.left = (rect.left - parentRect.left) + 'px';
        }

        /**
         * Switch to tab
         */
        function switchTab(targetTab) {
            const targetId = targetTab.getAttribute('data-tab-trigger');
            
            // Update tabs
            tabs.forEach(tab => {
                const isActive = tab.getAttribute('data-tab-trigger') === targetId;
                tab.classList.toggle('active', isActive);
                tab.setAttribute('aria-selected', isActive ? 'true' : 'false');
            });

            // Update panels with animation
            panels.forEach(panel => {
                const panelId = panel.getAttribute('data-tab-panel');
                const isActive = panelId === targetId;
                
                if (isActive) {
                    // Fade in
                    panel.style.display = 'block';
                    panel.style.opacity = '0';
                    panel.style.transform = 'translateY(10px)';
                    
                    setTimeout(() => {
                        panel.style.transition = 'opacity 0.3s ease-out, transform 0.3s ease-out';
                        panel.style.opacity = '1';
                        panel.style.transform = 'translateY(0)';
                    }, 50);
                } else {
                    // Fade out
                    panel.style.opacity = '0';
                    setTimeout(() => {
                        panel.style.display = 'none';
                    }, 300);
                }
            });

            // Update indicator
            updateIndicator(targetTab);
        }

        // Add click handlers
        tabs.forEach(tab => {
            tab.addEventListener('click', function(e) {
                e.preventDefault();
                switchTab(this);
            });
        });

        // Initialize first tab
        const activeTab = Array.from(tabs).find(tab => tab.classList.contains('active')) || tabs[0];
        if (activeTab) {
            switchTab(activeTab);
        }

        // Update indicator on window resize
        window.addEventListener('resize', function() {
            const activeTab = Array.from(tabs).find(tab => tab.classList.contains('active'));
            if (activeTab) {
                updateIndicator(activeTab);
            }
        });
    }

    /**
     * Initialize when DOM is ready
     */
    function init() {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initTabs);
        } else {
            initTabs();
        }
    }

    init();
})();

