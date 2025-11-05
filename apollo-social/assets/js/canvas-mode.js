/**
 * Apollo Social Canvas Mode JavaScript
 */
(function($) {
    'use strict';

    const ApolloCanvas = {
        
        init: function() {
            this.bindEvents();
            this.setupAjax();
            console.log('Apollo Canvas Mode initialized');
        },

        bindEvents: function() {
            // Handle navigation clicks
            $(document).on('click', '.apollo-menu a', this.handleNavigation);
            
            // Handle smooth scrolling
            $(document).on('click', 'a[href^="#"]', this.handleSmoothScroll);
        },

        handleNavigation: function(e) {
            // For now, let default navigation work
            // Later: implement AJAX loading
        },

        handleSmoothScroll: function(e) {
            const target = $(this.getAttribute('href'));
            if (target.length) {
                e.preventDefault();
                $('html, body').animate({
                    scrollTop: target.offset().top - 80
                }, 500);
            }
        },

        setupAjax: function() {
            // Setup AJAX defaults if apolloCanvas object exists
            if (typeof apolloCanvas !== 'undefined') {
                $.ajaxSetup({
                    beforeSend: function(xhr) {
                        xhr.setRequestHeader('X-WP-Nonce', apolloCanvas.nonce);
                    }
                });
            }
        },

        // Utility function for loading content via AJAX
        loadContent: function(url, container) {
            $(container).addClass('loading');
            
            $.get(url)
                .done(function(data) {
                    $(container).html(data);
                })
                .fail(function() {
                    $(container).html('<p>Erro ao carregar conteúdo.</p>');
                })
                .always(function() {
                    $(container).removeClass('loading');
                });
        }
    };

    // Initialize when document is ready
    $(document).ready(function() {
        ApolloCanvas.init();
    });

    // Make ApolloCanvas available globally
    window.ApolloCanvas = ApolloCanvas;

})(jQuery);