/**
 * Apollo Lists JavaScript
 * Slider functionality and interactions
 *
 * @package Apollo_Events_Manager
 * @since 2.0.0
 */

(function($) {
    'use strict';

    /**
     * Apollo Events Slider
     */
    class ApolloEventsSlider {
        constructor(element) {
            this.$slider = $(element);
            this.$track = this.$slider.find('.apollo-events-slider__track');
            this.$slides = this.$slider.find('.apollo-events-slider__slide');
            this.$dots = this.$slider.find('.apollo-events-slider__dot');
            this.$prevBtn = this.$slider.find('.apollo-events-slider__nav--prev');
            this.$nextBtn = this.$slider.find('.apollo-events-slider__nav--next');

            this.currentSlide = 0;
            this.slideCount = this.$slides.length;
            this.autoplay = this.$slider.data('autoplay') !== false;
            this.autoplayInterval = null;
            this.autoplayDelay = 5000;

            this.init();
        }

        init() {
            if (this.slideCount <= 1) {
                this.$prevBtn.hide();
                this.$nextBtn.hide();
                return;
            }

            this.bindEvents();

            if (this.autoplay) {
                this.startAutoplay();
            }
        }

        bindEvents() {
            this.$prevBtn.on('click', () => this.prevSlide());
            this.$nextBtn.on('click', () => this.nextSlide());
            this.$dots.on('click', (e) => this.goToSlide($(e.currentTarget).index()));

            // Pause autoplay on hover
            this.$slider.on('mouseenter', () => this.stopAutoplay());
            this.$slider.on('mouseleave', () => {
                if (this.autoplay) {
                    this.startAutoplay();
                }
            });

            // Touch/swipe support
            let touchStartX = 0;
            let touchEndX = 0;

            this.$slider.on('touchstart', (e) => {
                touchStartX = e.originalEvent.touches[0].clientX;
            });

            this.$slider.on('touchend', (e) => {
                touchEndX = e.originalEvent.changedTouches[0].clientX;
                const diff = touchStartX - touchEndX;

                if (Math.abs(diff) > 50) {
                    if (diff > 0) {
                        this.nextSlide();
                    } else {
                        this.prevSlide();
                    }
                }
            });

            // Keyboard navigation
            this.$slider.attr('tabindex', '0');
            this.$slider.on('keydown', (e) => {
                if (e.key === 'ArrowLeft') {
                    this.prevSlide();
                } else if (e.key === 'ArrowRight') {
                    this.nextSlide();
                }
            });
        }

        goToSlide(index) {
            if (index < 0) {
                index = this.slideCount - 1;
            } else if (index >= this.slideCount) {
                index = 0;
            }

            this.currentSlide = index;
            this.$track.css('transform', `translateX(-${index * 100}%)`);

            this.$dots.removeClass('is-active');
            this.$dots.eq(index).addClass('is-active');
        }

        nextSlide() {
            this.goToSlide(this.currentSlide + 1);
        }

        prevSlide() {
            this.goToSlide(this.currentSlide - 1);
        }

        startAutoplay() {
            this.stopAutoplay();
            this.autoplayInterval = setInterval(() => {
                this.nextSlide();
            }, this.autoplayDelay);
        }

        stopAutoplay() {
            if (this.autoplayInterval) {
                clearInterval(this.autoplayInterval);
                this.autoplayInterval = null;
            }
        }
    }

    /**
     * Lazy loading for images
     */
    class LazyLoader {
        constructor() {
            this.observer = null;
            this.init();
        }

        init() {
            if ('IntersectionObserver' in window) {
                this.observer = new IntersectionObserver((entries) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            this.loadImage(entry.target);
                            this.observer.unobserve(entry.target);
                        }
                    });
                }, {
                    rootMargin: '50px 0px'
                });

                document.querySelectorAll('.apollo-lazy').forEach(img => {
                    this.observer.observe(img);
                });
            } else {
                // Fallback for older browsers
                document.querySelectorAll('.apollo-lazy').forEach(img => {
                    this.loadImage(img);
                });
            }
        }

        loadImage(img) {
            const src = img.dataset.src;
            if (src) {
                img.src = src;
                img.classList.remove('apollo-lazy');
                img.classList.add('apollo-lazy-loaded');
            }
        }
    }

    /**
     * Grid infinite scroll
     */
    class InfiniteScroll {
        constructor(container) {
            this.$container = $(container);
            this.page = 1;
            this.loading = false;
            this.hasMore = true;
            this.endpoint = this.$container.data('endpoint') || '';
            this.filters = this.$container.data('filters') || {};

            if (this.endpoint) {
                this.init();
            }
        }

        init() {
            this.observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting && !this.loading && this.hasMore) {
                        this.loadMore();
                    }
                });
            }, {
                rootMargin: '200px 0px'
            });

            // Create sentinel element
            this.$sentinel = $('<div class="apollo-scroll-sentinel"></div>');
            this.$container.after(this.$sentinel);
            this.observer.observe(this.$sentinel[0]);
        }

        loadMore() {
            this.loading = true;
            this.page++;

            const params = {
                ...this.filters,
                page: this.page,
                per_page: 12
            };

            $.ajax({
                url: this.endpoint,
                data: params,
                beforeSend: () => {
                    this.$container.after('<div class="apollo-loading-more"><i class="fas fa-spinner fa-spin"></i></div>');
                },
                success: (response) => {
                    if (response.html) {
                        this.$container.append(response.html);
                    }

                    if (!response.hasMore) {
                        this.hasMore = false;
                        this.observer.disconnect();
                    }
                },
                complete: () => {
                    this.loading = false;
                    $('.apollo-loading-more').remove();
                }
            });
        }
    }

    /**
     * Masonry layout
     */
    class MasonryGrid {
        constructor(container) {
            this.$container = $(container);
            this.items = [];

            if (this.$container.hasClass('apollo-events-grid--masonry')) {
                this.init();
            }
        }

        init() {
            // Wait for images to load
            const images = this.$container.find('img');
            let loadedImages = 0;

            if (images.length === 0) {
                this.layout();
            } else {
                images.each((i, img) => {
                    if (img.complete) {
                        loadedImages++;
                    } else {
                        img.addEventListener('load', () => {
                            loadedImages++;
                            if (loadedImages === images.length) {
                                this.layout();
                            }
                        });
                    }
                });

                if (loadedImages === images.length) {
                    this.layout();
                }
            }

            // Relayout on window resize
            $(window).on('resize', this.debounce(() => {
                this.layout();
            }, 250));
        }

        layout() {
            const $items = this.$container.children();
            const containerWidth = this.$container.width();
            const itemWidth = $items.first().outerWidth();
            const columns = Math.floor(containerWidth / itemWidth) || 1;
            const columnHeights = new Array(columns).fill(0);

            $items.each((i, item) => {
                const $item = $(item);
                const minHeight = Math.min(...columnHeights);
                const columnIndex = columnHeights.indexOf(minHeight);

                $item.css({
                    position: 'absolute',
                    left: columnIndex * itemWidth,
                    top: minHeight
                });

                columnHeights[columnIndex] += $item.outerHeight(true);
            });

            this.$container.css('height', Math.max(...columnHeights));
            this.$container.css('position', 'relative');
        }

        debounce(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        }
    }

    /**
     * Initialize on document ready
     */
    $(document).ready(function() {
        // Initialize sliders
        $('.apollo-events-slider').each(function() {
            new ApolloEventsSlider(this);
        });

        // Initialize lazy loader
        new LazyLoader();

        // Initialize infinite scroll
        $('.apollo-events-grid[data-infinite]').each(function() {
            new InfiniteScroll(this);
        });

        // Initialize masonry
        $('.apollo-events-grid--masonry').each(function() {
            new MasonryGrid(this);
        });

        // Card hover effects
        $('.apollo-event-card').on('mouseenter', function() {
            $(this).addClass('is-hovered');
        }).on('mouseleave', function() {
            $(this).removeClass('is-hovered');
        });

        // Row click handler (if not clicking a link)
        $('.apollo-event-row').on('click', function(e) {
            if (!$(e.target).is('a, button')) {
                const link = $(this).find('.apollo-event-row__title a');
                if (link.length) {
                    window.location.href = link.attr('href');
                }
            }
        });

        // Table row click handler
        $('.apollo-events-table tbody tr').on('click', function(e) {
            if (!$(e.target).is('a, button')) {
                const link = $(this).find('.apollo-events-table__title a');
                if (link.length) {
                    window.location.href = link.attr('href');
                }
            }
        }).css('cursor', 'pointer');
    });

    // Make classes available globally
    window.ApolloEventsSlider = ApolloEventsSlider;
    window.ApolloLazyLoader = LazyLoader;
    window.ApolloInfiniteScroll = InfiniteScroll;

})(jQuery);
