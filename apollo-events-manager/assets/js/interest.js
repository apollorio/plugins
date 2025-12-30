/**
 * Apollo Interest JavaScript
 * Toggle interest, animations, and AJAX handling
 *
 * @package Apollo_Events_Manager
 * @since 2.0.0
 */

(function($) {
    'use strict';

    /**
     * Apollo Interest Handler
     */
    class ApolloInterest {
        constructor() {
            this.settings = window.apolloInterest || {};
            this.init();
        }

        init() {
            this.bindEvents();
            this.initFloatingButton();
        }

        bindEvents() {
            // Interest button click
            $(document).on('click', '.apollo-interest-btn', (e) => {
                e.preventDefault();
                this.toggleInterest($(e.currentTarget));
            });

            // Keyboard accessibility
            $(document).on('keydown', '.apollo-interest-btn', (e) => {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    this.toggleInterest($(e.currentTarget));
                }
            });
        }

        toggleInterest($button) {
            // Check if user is logged in
            if (!this.settings.isLoggedIn) {
                this.showLoginPrompt();
                return;
            }

            // Prevent double clicks
            if ($button.hasClass('is-loading')) {
                return;
            }

            const eventId = $button.data('event-id');

            if (!eventId) {
                console.error('Apollo Interest: Event ID not found');
                return;
            }

            // Set loading state
            $button.addClass('is-loading');

            // Make AJAX request
            $.ajax({
                url: this.settings.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'apollo_toggle_interest',
                    nonce: this.settings.nonce,
                    event_id: eventId
                },
                success: (response) => {
                    if (response.success) {
                        this.updateButton($button, response.data);
                        this.updateAllButtons(eventId, response.data);
                        this.showFeedback($button, response.data.action);
                    } else {
                        this.showError(response.data?.message || this.settings.i18n?.error);
                    }
                },
                error: (xhr) => {
                    if (xhr.status === 401) {
                        this.showLoginPrompt();
                    } else {
                        this.showError(this.settings.i18n?.error);
                    }
                },
                complete: () => {
                    $button.removeClass('is-loading');
                }
            });
        }

        updateButton($button, data) {
            const isInterested = data.interested;

            // Update button state
            if (isInterested) {
                $button.addClass('is-interested');
                $button.attr('aria-pressed', 'true');
            } else {
                $button.removeClass('is-interested');
                $button.attr('aria-pressed', 'false');
            }

            // Update icon
            const $icon = $button.find('.apollo-interest-btn__icon i');
            if ($icon.length) {
                $icon.removeClass('far fas').addClass(isInterested ? 'fas' : 'far');
            }

            // Update text
            const $text = $button.find('.apollo-interest-btn__text');
            if ($text.length) {
                $text.text(isInterested ? 'Interessado' : this.settings.i18n?.interested || 'Tenho Interesse');
            }

            // Update count
            const $count = $button.find('.apollo-interest-btn__count');
            if ($count.length) {
                if (data.count > 0) {
                    $count.text(data.countFormatted).show();
                } else {
                    $count.hide();
                }
            }
        }

        updateAllButtons(eventId, data) {
            // Update all buttons for this event
            $(`.apollo-interest-btn[data-event-id="${eventId}"]`).each((i, btn) => {
                this.updateButton($(btn), data);
            });

            // Update count displays
            $(`.apollo-interest-count[data-event-id="${eventId}"]`).each((i, el) => {
                const $el = $(el);
                $el.find('.apollo-interest-count__number').text(data.countFormatted);
            });
        }

        showFeedback($button, action) {
            // Create ripple effect
            const $ripple = $('<span class="apollo-interest-ripple"></span>');
            $button.append($ripple);

            setTimeout(() => {
                $ripple.addClass('is-active');
            }, 10);

            setTimeout(() => {
                $ripple.remove();
            }, 600);

            // Trigger heart animation for added interest
            if (action === 'added') {
                this.showHeartAnimation($button);
            }
        }

        showHeartAnimation($button) {
            const $heart = $('<span class="apollo-heart-float"><i class="fas fa-heart"></i></span>');
            $button.append($heart);

            setTimeout(() => {
                $heart.addClass('is-active');
            }, 10);

            setTimeout(() => {
                $heart.remove();
            }, 1000);
        }

        showLoginPrompt() {
            const message = this.settings.i18n?.loginRequired || 'Faça login para marcar interesse';

            // Check if there's a custom login modal
            if (typeof window.apolloShowLoginModal === 'function') {
                window.apolloShowLoginModal();
                return;
            }

            // Simple confirmation dialog
            if (confirm(message + '\n\nDeseja ir para a página de login?')) {
                window.location.href = this.settings.loginUrl || '/wp-login.php';
            }
        }

        showError(message) {
            // Check if there's a toast notification system
            if (typeof window.apolloToast === 'function') {
                window.apolloToast.error(message);
                return;
            }

            // Simple alert fallback
            alert(message);
        }

        initFloatingButton() {
            const $floating = $('.apollo-interest-floating');
            if (!$floating.length) return;

            // Show/hide based on scroll
            let lastScroll = 0;
            $(window).on('scroll', () => {
                const currentScroll = $(window).scrollTop();

                if (currentScroll > 300) {
                    $floating.addClass('is-visible');
                } else {
                    $floating.removeClass('is-visible');
                }

                // Hide when scrolling down
                if (currentScroll > lastScroll && currentScroll > 500) {
                    $floating.addClass('is-hidden');
                } else {
                    $floating.removeClass('is-hidden');
                }

                lastScroll = currentScroll;
            });
        }
    }

    /**
     * Add dynamic CSS for animations
     */
    function addAnimationStyles() {
        const style = document.createElement('style');
        style.textContent = `
            .apollo-interest-ripple {
                position: absolute;
                top: 50%;
                left: 50%;
                width: 0;
                height: 0;
                border-radius: 50%;
                background: rgba(239, 68, 68, 0.3);
                transform: translate(-50%, -50%);
                pointer-events: none;
            }
            .apollo-interest-ripple.is-active {
                width: 200%;
                height: 200%;
                opacity: 0;
                transition: all 0.6s ease-out;
            }
            .apollo-heart-float {
                position: absolute;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%);
                color: #ef4444;
                font-size: 1.5rem;
                opacity: 0;
                pointer-events: none;
            }
            .apollo-heart-float.is-active {
                animation: heartFloat 1s ease-out forwards;
            }
            @keyframes heartFloat {
                0% {
                    opacity: 1;
                    transform: translate(-50%, -50%) scale(1);
                }
                100% {
                    opacity: 0;
                    transform: translate(-50%, -200%) scale(1.5);
                }
            }
            .apollo-interest-floating {
                opacity: 0;
                visibility: hidden;
                transform: translateY(20px);
                transition: all 0.3s ease;
            }
            .apollo-interest-floating.is-visible {
                opacity: 1;
                visibility: visible;
                transform: translateY(0);
            }
            .apollo-interest-floating.is-hidden {
                opacity: 0;
                visibility: hidden;
                transform: translateY(20px);
            }
        `;
        document.head.appendChild(style);
    }

    /**
     * Initialize on document ready
     */
    $(document).ready(function() {
        addAnimationStyles();
        window.apolloInterestHandler = new ApolloInterest();
    });

    // Make class available globally
    window.ApolloInterest = ApolloInterest;

})(jQuery);
