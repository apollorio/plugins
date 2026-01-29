/**
 * Apollo Events Manager - Share Module
 *
 * @package Apollo_Events_Manager
 * @since 2.0.0
 */

(function($) {
    'use strict';

    /**
     * Share button handler.
     */
    class ApolloShare {
        constructor(element) {
            this.$container = $(element);
            this.eventId = this.$container.data('event-id');
            this.$buttons = this.$container.find('.apollo-share__btn');

            this.init();
        }

        init() {
            this.$buttons.on('click', (e) => this.handleClick(e));
        }

        handleClick(e) {
            const $btn = $(e.currentTarget);
            const network = $btn.data('network');

            // Handle copy button specially
            if (network === 'copy') {
                e.preventDefault();
                this.handleCopy($btn);
                return;
            }

            // Track share
            this.trackShare(network);

            // For mobile, try native share API
            if (this.shouldUseNativeShare()) {
                e.preventDefault();
                this.nativeShare();
            }
        }

        handleCopy($btn) {
            const url = $btn.data('clipboard-text');

            if (!url) return;

            navigator.clipboard.writeText(url)
                .then(() => {
                    this.showCopyFeedback($btn);
                    this.trackShare('copy');
                })
                .catch(() => {
                    // Fallback for older browsers
                    this.fallbackCopy(url);
                    this.showCopyFeedback($btn);
                    this.trackShare('copy');
                });
        }

        fallbackCopy(text) {
            const textarea = document.createElement('textarea');
            textarea.value = text;
            textarea.style.position = 'fixed';
            textarea.style.opacity = '0';
            document.body.appendChild(textarea);
            textarea.select();
            document.execCommand('copy');
            document.body.removeChild(textarea);
        }

        showCopyFeedback($btn) {
            const $icon = $btn.find('i');
            const originalClass = $icon.attr('class');

            $btn.addClass('is-copied');
            $icon.removeClass().addClass('fas fa-check');

            // Show toast
            this.showToast(apolloShare.i18n.copied);

            setTimeout(() => {
                $btn.removeClass('is-copied');
                $icon.removeClass().addClass(originalClass);
            }, 2000);
        }

        showToast(message) {
            let $toast = $('.apollo-share-copied-toast');

            if (!$toast.length) {
                $toast = $(`
                    <div class="apollo-share-copied-toast">
                        <i class="fas fa-check-circle"></i>
                        <span>${message}</span>
                    </div>
                `);
                $('body').append($toast);
            } else {
                $toast.find('span').text(message);
            }

            // Show toast
            setTimeout(() => $toast.addClass('is-visible'), 10);

            // Hide after 2 seconds
            setTimeout(() => {
                $toast.removeClass('is-visible');
            }, 2500);
        }

        shouldUseNativeShare() {
            return navigator.share && this.isMobile();
        }

        isMobile() {
            return /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
        }

        nativeShare() {
            const title = document.querySelector('meta[property="og:title"]')?.content || document.title;
            const url = window.location.href;

            navigator.share({
                title: title,
                url: url
            }).then(() => {
                this.trackShare('native');
            }).catch((error) => {
                console.log('Share cancelled:', error);
            });
        }

        trackShare(network) {
            if (!apolloShare.ajaxUrl) return;

            $.ajax({
                url: apolloShare.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'apollo_track_share',
                    nonce: apolloShare.nonce,
                    event_id: this.eventId,
                    network: network
                },
                success: (response) => {
                    if (response.success && response.data.count) {
                        this.updateCount(response.data.count);
                    }
                }
            });
        }

        updateCount(count) {
            const $count = this.$container.find('.apollo-share__count-number');
            if ($count.length) {
                $count.text(count);
            }
        }
    }

    /**
     * Share modal.
     */
    class ApolloShareModal {
        constructor() {
            this.$modal = null;
            this.init();
        }

        init() {
            $(document).on('click', '[data-share-modal]', (e) => {
                e.preventDefault();
                const eventId = $(e.currentTarget).data('event-id');
                this.open(eventId);
            });
        }

        open(eventId) {
            if (!this.$modal) {
                this.createModal();
            }

            this.$modal.data('event-id', eventId);
            this.$modal.addClass('is-open');
            $('body').css('overflow', 'hidden');
        }

        close() {
            this.$modal.removeClass('is-open');
            $('body').css('overflow', '');
        }

        createModal() {
            this.$modal = $(`
                <div class="apollo-share-modal">
                    <div class="apollo-share-modal__content">
                        <div class="apollo-share-modal__header">
                            <h3 class="apollo-share-modal__title">${apolloShare.i18n.share}</h3>
                            <button type="button" class="apollo-share-modal__close">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                        <div class="apollo-share-modal__networks">
                            <a href="#" class="apollo-share-modal__network" data-network="whatsapp" style="--btn-color: #25D366">
                                <i class="fab fa-whatsapp"></i>
                                <span>WhatsApp</span>
                            </a>
                            <a href="#" class="apollo-share-modal__network" data-network="facebook" style="--btn-color: #1877F2">
                                <i class="fab fa-facebook-f"></i>
                                <span>Facebook</span>
                            </a>
                            <a href="#" class="apollo-share-modal__network" data-network="twitter" style="--btn-color: #000">
                                <i class="fab fa-x-twitter"></i>
                                <span>X</span>
                            </a>
                            <a href="#" class="apollo-share-modal__network" data-network="telegram" style="--btn-color: #0088CC">
                                <i class="fab fa-telegram-plane"></i>
                                <span>Telegram</span>
                            </a>
                        </div>
                        <div class="apollo-share-modal__copy">
                            <input type="text" class="apollo-share-modal__copy-input" value="${window.location.href}" readonly>
                            <button type="button" class="apollo-share-modal__copy-btn">
                                <i class="fas fa-copy"></i> Copiar
                            </button>
                        </div>
                    </div>
                </div>
            `);

            $('body').append(this.$modal);
            this.bindModalEvents();
        }

        bindModalEvents() {
            this.$modal.find('.apollo-share-modal__close').on('click', () => this.close());

            this.$modal.on('click', (e) => {
                if ($(e.target).hasClass('apollo-share-modal')) {
                    this.close();
                }
            });

            this.$modal.find('.apollo-share-modal__copy-btn').on('click', () => {
                const $input = this.$modal.find('.apollo-share-modal__copy-input');
                navigator.clipboard.writeText($input.val());

                const $btn = this.$modal.find('.apollo-share-modal__copy-btn');
                $btn.html('<i class="fas fa-check"></i> Copiado!');

                setTimeout(() => {
                    $btn.html('<i class="fas fa-copy"></i> Copiar');
                }, 2000);
            });

            this.$modal.find('.apollo-share-modal__network').on('click', (e) => {
                e.preventDefault();
                const network = $(e.currentTarget).data('network');
                this.share(network);
            });

            $(document).on('keydown', (e) => {
                if (e.key === 'Escape' && this.$modal.hasClass('is-open')) {
                    this.close();
                }
            });
        }

        share(network) {
            const url = encodeURIComponent(window.location.href);
            const title = encodeURIComponent(document.title);

            const urls = {
                whatsapp: `https://api.whatsapp.com/send?text=${title}%20${url}`,
                facebook: `https://www.facebook.com/sharer/sharer.php?u=${url}`,
                twitter: `https://twitter.com/intent/tweet?text=${title}&url=${url}`,
                telegram: `https://t.me/share/url?url=${url}&text=${title}`
            };

            if (urls[network]) {
                window.open(urls[network], '_blank', 'width=600,height=400');
            }

            this.close();
        }
    }

    /**
     * Floating share buttons.
     */
    class ApolloFloatingShare {
        constructor() {
            this.$floating = $('.apollo-share--floating');

            if (!this.$floating.length) return;

            this.init();
        }

        init() {
            this.handleScroll();
            $(window).on('scroll', () => this.handleScroll());
        }

        handleScroll() {
            const scrollTop = $(window).scrollTop();
            const docHeight = $(document).height();
            const winHeight = $(window).height();

            // Hide when near bottom
            if (scrollTop + winHeight > docHeight - 200) {
                this.$floating.css('opacity', '0');
            } else if (scrollTop > 300) {
                this.$floating.css('opacity', '1');
            } else {
                this.$floating.css('opacity', '0');
            }
        }
    }

    /**
     * Initialize on DOM ready.
     */
    $(function() {
        // Initialize share containers
        $('.apollo-share').each(function() {
            new ApolloShare(this);
        });

        // Initialize modal
        new ApolloShareModal();

        // Initialize floating share
        new ApolloFloatingShare();
    });

    // Expose classes globally
    window.ApolloShare = ApolloShare;
    window.ApolloShareModal = ApolloShareModal;
    window.ApolloFloatingShare = ApolloFloatingShare;

})(jQuery);
