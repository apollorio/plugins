/**
 * Infinite Scroll for Event Listings
 * 
 * Implements Intersection Observer for lazy loading
 * 
 * @package Apollo_Events_Manager
 * @version 0.1.0
 */

(function() {
    'use strict';

    let isLoading = false;
    let currentPage = 1;
    let hasMorePages = true;

    /**
     * Create and append loader element
     */
    function createLoader() {
        const loader = document.createElement('div');
        loader.className = 'infinite-scroll-loader';
        loader.id = 'apollo-infinite-loader';
        loader.innerHTML = '<div class="spinner"></div>';
        return loader;
    }

    /**
     * Load more events via AJAX
     */
    function loadMoreEvents() {
        if (isLoading || !hasMorePages) {
            return;
        }

        isLoading = true;
        const loader = document.getElementById('apollo-infinite-loader');
        if (loader) {
            loader.classList.add('visible');
        }

        const container = document.querySelector('.event_listings');
        if (!container) {
            return;
        }

        // Get current filters
        const categorySlug = document.querySelector('.event-category.active')?.dataset.slug || 'all';
        const searchKeywords = document.getElementById('eventSearchInput')?.value || '';
        const monthStr = document.getElementById('dateDisplay')?.textContent.trim() || '';

        // AJAX call
        if (typeof jQuery !== 'undefined' && typeof apollo_events_ajax !== 'undefined') {
            currentPage++;

            jQuery.ajax({
                url: apollo_events_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'filter_events',
                    category: categorySlug,
                    search: searchKeywords,
                    month: monthStr,
                    page: currentPage,
                    _ajax_nonce: apollo_events_ajax.nonce
                },
                success: function(response) {
                    if (response.success && response.data && response.data.html) {
                        // Append new cards with stagger animation (TODO 92)
                        const tempDiv = document.createElement('div');
                        tempDiv.innerHTML = response.data.html;
                        
                        const newCards = tempDiv.querySelectorAll('.event_listing, .event-list-item');
                        newCards.forEach((card, index) => {
                            // Initial hidden state
                            card.style.opacity = '0';
                            card.style.transform = 'translateY(20px)';
                            container.appendChild(card);
                            
                            // Stagger animation
                            setTimeout(() => {
                                card.style.transition = 'opacity 0.4s ease, transform 0.4s ease';
                                card.style.opacity = '1';
                                card.style.transform = 'translateY(0)';
                            }, index * 50); // 50ms stagger
                        });

                        // Check if there are more pages
                        if (newCards.length === 0 || (response.data.has_more === false)) {
                            hasMorePages = false;
                            if (loader) {
                                loader.remove();
                            }
                        }

                        // Trigger event for motion-event-card.js
                        const event = new CustomEvent('apollo:events-loaded');
                        document.dispatchEvent(event);
                    } else {
                        hasMorePages = false;
                        if (loader) {
                            loader.remove();
                        }
                    }
                },
                error: function() {
                    hasMorePages = false;
                    if (loader) {
                        loader.remove();
                    }
                },
                complete: function() {
                    isLoading = false;
                    if (loader) {
                        loader.classList.remove('visible');
                    }
                }
            });
        }
    }

    /**
     * Initialize Intersection Observer
     */
    function initInfiniteScroll() {
        const container = document.querySelector('.event_listings');
        if (!container) {
            return;
        }

        // Add loader at the end
        const loader = createLoader();
        container.parentNode.appendChild(loader);

        // Create observer
        const observer = new IntersectionObserver(function(entries) {
            entries.forEach(function(entry) {
                if (entry.isIntersecting) {
                    loadMoreEvents();
                }
            });
        }, {
            rootMargin: '200px' // Start loading 200px before reaching the loader
        });

        observer.observe(loader);

        // Reset on filter change
        document.addEventListener('apollo:filter-changed', function() {
            currentPage = 1;
            hasMorePages = true;
            isLoading = false;
        });
    }

    /**
     * Initialize when DOM is ready
     */
    function init() {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initInfiniteScroll);
        } else {
            initInfiniteScroll();
        }
    }

    // Only initialize if on event listing page and in list view
    if (document.querySelector('.event_listings.list-view')) {
        init();
    }

    // Re-initialize when switching to list view
    document.addEventListener('apollo:layout-changed', function(e) {
        if (e.detail && e.detail.layout === 'list') {
            setTimeout(init, 100);
        }
    });
})();

