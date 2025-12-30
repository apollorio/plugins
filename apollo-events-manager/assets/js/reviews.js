/**
 * Apollo Events Manager - Reviews Module JavaScript
 *
 * @package Apollo_Events_Manager
 * @since 2.0.0
 */

(function($) {
    'use strict';

    /**
     * Apollo Reviews Manager
     */
    class ApolloReviews {
        constructor() {
            this.config = window.apolloReviews || {};
            this.init();
        }

        init() {
            this.bindEvents();
        }

        bindEvents() {
            $(document)
                .on('click', '.apollo-review-form__star', this.handleStarClick.bind(this))
                .on('mouseenter', '.apollo-review-form__star', this.handleStarHover.bind(this))
                .on('mouseleave', '.apollo-review-form__stars', this.handleStarsLeave.bind(this))
                .on('submit', '.apollo-review-form__form', this.handleFormSubmit.bind(this))
                .on('click', '.apollo-review__helpful', this.handleHelpfulClick.bind(this))
                .on('change', '.apollo-reviews__sort-select', this.handleSortChange.bind(this));
        }

        /**
         * Handle star click
         */
        handleStarClick(e) {
            e.preventDefault();
            const $star = $(e.currentTarget);
            const $container = $star.closest('.apollo-review-form__stars');
            const rating = parseInt($star.data('rating'), 10);

            // Update hidden input
            $container.siblings('input[name="rating"]').val(rating);

            // Update star states
            $container.find('.apollo-review-form__star').each(function(index) {
                const $s = $(this);
                const starIndex = index + 1;

                $s.removeClass('is-selected is-hovered');

                if (starIndex <= rating) {
                    $s.addClass('is-selected');
                    $s.find('i').removeClass('far').addClass('fas');
                } else {
                    $s.find('i').removeClass('fas').addClass('far');
                }
            });

            // Add animation
            $star.addClass('is-animating');
            setTimeout(() => $star.removeClass('is-animating'), 300);
        }

        /**
         * Handle star hover
         */
        handleStarHover(e) {
            const $star = $(e.currentTarget);
            const $container = $star.closest('.apollo-review-form__stars');
            const rating = parseInt($star.data('rating'), 10);

            $container.find('.apollo-review-form__star').each(function(index) {
                const $s = $(this);
                const starIndex = index + 1;

                if (starIndex <= rating) {
                    $s.addClass('is-hovered');
                    $s.find('i').removeClass('far').addClass('fas');
                } else if (!$s.hasClass('is-selected')) {
                    $s.find('i').removeClass('fas').addClass('far');
                }
            });
        }

        /**
         * Handle stars container leave
         */
        handleStarsLeave(e) {
            const $container = $(e.currentTarget);
            const selectedRating = parseInt($container.siblings('input[name="rating"]').val(), 10) || 0;

            $container.find('.apollo-review-form__star').each(function(index) {
                const $s = $(this);
                const starIndex = index + 1;

                $s.removeClass('is-hovered');

                if (starIndex <= selectedRating) {
                    $s.find('i').removeClass('far').addClass('fas');
                } else {
                    $s.find('i').removeClass('fas').addClass('far');
                }
            });
        }

        /**
         * Handle form submit
         */
        handleFormSubmit(e) {
            e.preventDefault();

            if (!this.config.isLoggedIn) {
                this.showMessage(e.target, 'error', this.config.i18n.loginRequired);
                return;
            }

            const $form = $(e.target);
            const $container = $form.closest('.apollo-review-form');
            const $submit = $form.find('.apollo-review-form__submit');
            const eventId = $container.data('event-id');

            const rating = $form.find('input[name="rating"]').val();
            const content = $form.find('textarea[name="content"]').val();

            if (!rating || rating < 1) {
                this.showMessage(e.target, 'error', this.config.i18n.selectRating);
                return;
            }

            // Disable submit
            $submit
                .prop('disabled', true)
                .addClass('is-loading')
                .find('i')
                .removeClass('fa-paper-plane')
                .addClass('fa-spinner');

            $submit.find('i').after('<span class="submit-text">' + this.config.i18n.submitting + '</span>');

            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'apollo_submit_review',
                    nonce: this.config.nonce,
                    event_id: eventId,
                    rating: rating,
                    content: content
                },
                success: (response) => {
                    if (response.success) {
                        this.showMessage(e.target, 'success', this.config.i18n.success);

                        // Hide form and show success message
                        setTimeout(() => {
                            $container.slideUp(300, function() {
                                $(this).replaceWith(
                                    '<div class="apollo-reviews__already-reviewed">' +
                                    '<i class="fas fa-check-circle"></i> ' +
                                    'Você já avaliou este evento.' +
                                    '</div>'
                                );
                            });

                            // Reload page to show new review
                            setTimeout(() => window.location.reload(), 1000);
                        }, 1500);
                    } else {
                        this.showMessage(e.target, 'error', response.data.message || this.config.i18n.error);
                        this.resetSubmitButton($submit);
                    }
                },
                error: () => {
                    this.showMessage(e.target, 'error', this.config.i18n.error);
                    this.resetSubmitButton($submit);
                }
            });
        }

        /**
         * Reset submit button state
         */
        resetSubmitButton($submit) {
            $submit
                .prop('disabled', false)
                .removeClass('is-loading')
                .find('i')
                .removeClass('fa-spinner')
                .addClass('fa-paper-plane');

            $submit.find('.submit-text').remove();
        }

        /**
         * Show message
         */
        showMessage(form, type, message) {
            const $form = $(form);
            let $message = $form.find('.apollo-review-form__message');

            if (!$message.length) {
                $message = $('<div class="apollo-review-form__message"></div>');
                $form.prepend($message);
            }

            $message
                .removeClass('apollo-review-form__message--success apollo-review-form__message--error is-visible')
                .addClass('apollo-review-form__message--' + type)
                .text(message)
                .addClass('is-visible');

            if (type === 'success') {
                setTimeout(() => {
                    $message.removeClass('is-visible');
                }, 5000);
            }
        }

        /**
         * Handle helpful click
         */
        handleHelpfulClick(e) {
            e.preventDefault();

            const $button = $(e.currentTarget);
            const reviewId = $button.data('review-id');
            const $review = $button.closest('.apollo-review');
            const eventId = $review.closest('.apollo-reviews').data('event-id');

            if ($button.hasClass('is-marked')) {
                return;
            }

            $button.addClass('is-marked');

            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'apollo_helpful_review',
                    event_id: eventId,
                    review_id: reviewId
                },
                success: (response) => {
                    if (response.success) {
                        const $count = $button.find('.apollo-review__helpful-count');

                        if ($count.length) {
                            $count.text('(' + response.data.helpful + ')');
                        } else {
                            $button.append('<span class="apollo-review__helpful-count">(' + response.data.helpful + ')</span>');
                        }

                        $button.find('i').removeClass('far').addClass('fas');
                    }
                },
                error: () => {
                    $button.removeClass('is-marked');
                }
            });

            // Store in local storage to prevent multiple clicks
            const helpfulKey = 'apollo_helpful_' + reviewId;
            localStorage.setItem(helpfulKey, 'true');
        }

        /**
         * Handle sort change
         */
        handleSortChange(e) {
            const $select = $(e.currentTarget);
            const eventId = $select.data('event-id');
            const sort = $select.val();

            // Update URL and reload
            const url = new URL(window.location.href);
            url.searchParams.set('sort_reviews', sort);
            window.location.href = url.toString();
        }
    }

    /**
     * Rating Input Component
     * For use in other forms
     */
    class ApolloRatingInput {
        constructor(container) {
            this.$container = $(container);
            this.$input = this.$container.find('input[type="hidden"]');
            this.init();
        }

        init() {
            this.createStars();
            this.bindEvents();
        }

        createStars() {
            const $stars = $('<div class="apollo-rating-input__stars"></div>');

            for (let i = 1; i <= 5; i++) {
                $stars.append(
                    '<button type="button" class="apollo-rating-input__star" data-value="' + i + '">' +
                    '<i class="far fa-star"></i>' +
                    '</button>'
                );
            }

            this.$container.append($stars);
            this.$stars = $stars;
        }

        bindEvents() {
            this.$stars.on('click', '.apollo-rating-input__star', (e) => {
                const value = $(e.currentTarget).data('value');
                this.setValue(value);
            });

            this.$stars.on('mouseenter', '.apollo-rating-input__star', (e) => {
                const value = $(e.currentTarget).data('value');
                this.preview(value);
            });

            this.$stars.on('mouseleave', () => {
                this.preview(parseInt(this.$input.val(), 10) || 0);
            });
        }

        setValue(value) {
            this.$input.val(value).trigger('change');
            this.preview(value);
        }

        preview(value) {
            this.$stars.find('.apollo-rating-input__star').each(function(index) {
                const $star = $(this);
                const starValue = index + 1;

                if (starValue <= value) {
                    $star.find('i').removeClass('far').addClass('fas');
                } else {
                    $star.find('i').removeClass('fas').addClass('far');
                }
            });
        }
    }

    /**
     * Review Stats Component
     */
    class ApolloReviewStats {
        constructor(container) {
            this.$container = $(container);
            this.animateBars();
        }

        animateBars() {
            const $bars = this.$container.find('.apollo-rating-summary__bar-fill');

            $bars.each(function() {
                const $bar = $(this);
                const width = $bar.css('width');

                $bar.css('width', 0);

                setTimeout(() => {
                    $bar.css('width', width);
                }, 100);
            });
        }
    }

    /**
     * Check previously marked helpful
     */
    function checkHelpfulState() {
        $('.apollo-review__helpful').each(function() {
            const $button = $(this);
            const reviewId = $button.data('review-id');
            const helpfulKey = 'apollo_helpful_' + reviewId;

            if (localStorage.getItem(helpfulKey) === 'true') {
                $button.addClass('is-marked');
                $button.find('i').removeClass('far').addClass('fas');
            }
        });
    }

    /**
     * Initialize on document ready
     */
    $(function() {
        // Initialize main reviews handler
        new ApolloReviews();

        // Initialize review stats animations
        $('.apollo-rating-summary').each(function() {
            new ApolloReviewStats(this);
        });

        // Initialize custom rating inputs
        $('.apollo-rating-input').each(function() {
            new ApolloRatingInput(this);
        });

        // Check helpful state from localStorage
        checkHelpfulState();
    });

    // Export to global scope
    window.ApolloReviews = ApolloReviews;
    window.ApolloRatingInput = ApolloRatingInput;
    window.ApolloReviewStats = ApolloReviewStats;

})(jQuery);
