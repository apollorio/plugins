/**
 * Apollo Suppliers JavaScript
 * File: assets/js/apollo-suppliers.js
 */

(function($) {
    'use strict';

    const ApolloSuppliers = {
        init() {
            this.bindFilters();
            this.bindSearch();
            this.bindCardClicks();
            this.updateCount();
        },

        bindFilters() {
            $('.ap-filter-chip').on('click', function() {
                $('.ap-filter-chip').removeClass('ap-active');
                $(this).addClass('ap-active');
                
                const category = $(this).data('cat');
                ApolloSuppliers.filterSuppliers(category);
            });
        },

        bindSearch() {
            let searchTimeout;
            $('#ap-search-input').on('input', function() {
                clearTimeout(searchTimeout);
                const term = $(this).val();
                
                searchTimeout = setTimeout(() => {
                    ApolloSuppliers.searchSuppliers(term);
                }, 300);
            });
        },

        filterSuppliers(category) {
            const $cards = $('.ap-supplier-card');
            
            if (category === 'all') {
                $cards.show();
            } else {
                $cards.each(function() {
                    const cardCat = $(this).find('.ap-supplier-category').text().toLowerCase();
                    const categoryMap = {
                        'sound': 'audio',
                        'light': 'iluminação',
                        'visuals': 'visuals',
                        'security': 'segurança',
                        'bar': 'bar'
                    };
                    
                    if (cardCat.includes(categoryMap[category])) {
                        $(this).show();
                    } else {
                        $(this).hide();
                    }
                });
            }
            
            this.animateCards();
            this.updateCount();
        },

        searchSuppliers(term) {
            const $cards = $('.ap-supplier-card');
            const searchTerm = term.toLowerCase();
            
            if (!searchTerm) {
                $cards.show();
                this.updateCount();
                return;
            }
            
            $cards.each(function() {
                const $card = $(this);
                const name = $card.find('.ap-supplier-name').text().toLowerCase();
                const tags = $card.find('.ap-tag').map(function() {
                    return $(this).text().toLowerCase();
                }).get().join(' ');
                
                if (name.includes(searchTerm) || tags.includes(searchTerm)) {
                    $card.show();
                } else {
                    $card.hide();
                }
            });
            
            this.animateCards();
            this.updateCount();
        },

        animateCards() {
            if (window.Motion) {
                const $visible = $('.ap-supplier-card:visible');
                $visible.each(function(idx) {
                    window.Motion.animate(this, 
                        { opacity: [0, 1], y: [10, 0] }, 
                        { duration: 0.3, delay: idx * 0.05 }
                    );
                });
            }
        },

        updateCount() {
            const count = $('.ap-supplier-card:visible').length;
            $('#ap-count-label').text(`${count} resultado${count !== 1 ? 's' : ''}`);
        },

        bindCardClicks() {
            $(document).on('click', '.ap-supplier-card', function() {
                const supplierId = $(this).data('id');
                window.location.href = apolloSuppliersData.supplierUrl + supplierId;
            });
        }
    };

    $(document).ready(() => ApolloSuppliers.init());

})(jQuery);
