/**
 * Apollo Events - Enhanced Filters
 * Adiciona suporte para filtros por local além das categorias
 */
(function() {
    'use strict';

    function initEventFilters() {
        const filterButtons = document.querySelectorAll('.event-category');
        const eventCards = document.querySelectorAll('.event_listing');

        if (!filterButtons.length || !eventCards.length) {
            return;
        }

        filterButtons.forEach(function(button) {
            button.addEventListener('click', function() {
                const slug = this.getAttribute('data-slug');
                const filterType = this.getAttribute('data-filter-type');

                // Remove active class from all buttons
                filterButtons.forEach(function(btn) {
                    btn.classList.remove('active');
                });

                // Add active class to clicked button
                this.classList.add('active');

                // Filter events
                if (slug === 'all') {
                    // Show all events
                    eventCards.forEach(function(card) {
                        card.style.display = '';
                    });
                } else if (filterType === 'local') {
                    // Filter by local slug (support variations like dedge, d-edge)
                    const normalizedSlug = slug.toLowerCase().replace(/[^a-z0-9]/g, '');
                    let visibleCount = 0;
                    
                    eventCards.forEach(function(card) {
                        const cardLocalSlug = card.getAttribute('data-local-slug');
                        if (!cardLocalSlug) {
                            card.style.display = 'none';
                            return;
                        }
                        
                        const normalizedCardSlug = cardLocalSlug.toLowerCase().replace(/[^a-z0-9]/g, '');
                        const cardSlugLower = cardLocalSlug.toLowerCase();
                        const slugLower = slug.toLowerCase();
                        
                        // Multiple matching strategies
                        const matches = 
                            normalizedCardSlug === normalizedSlug ||  // Normalized match (dedge === dedge)
                            cardSlugLower === slugLower ||            // Exact lowercase match
                            cardLocalSlug === slug ||                 // Exact match
                            cardSlugLower.includes(slugLower) ||      // Contains match
                            slugLower.includes(cardSlugLower);        // Reverse contains match
                        
                        if (matches) {
                            card.style.display = '';
                            visibleCount++;
                        } else {
                            card.style.display = 'none';
                        }
                    });
                    
                    console.log('Apollo Local Filter: slug="' + slug + '", normalized="' + normalizedSlug + '", visible=' + visibleCount);
                } else {
                    // Filter by category slug (default behavior)
                    eventCards.forEach(function(card) {
                        const cardCategory = card.getAttribute('data-category');
                        if (cardCategory === slug) {
                            card.style.display = '';
                        } else {
                            card.style.display = 'none';
                        }
                    });
                }

                // Update count or display message if needed
                const visibleCards = document.querySelectorAll('.event_listing:not([style*="display: none"])');
                console.log('Apollo Filters: ' + visibleCards.length + ' eventos visíveis');
            });
        });
    }

    // Initialize on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initEventFilters);
    } else {
        initEventFilters();
    }

    // Reinitialize on AJAX complete (if needed)
    window.addEventListener('apollo:filters:reinit', initEventFilters);

})();

