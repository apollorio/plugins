/**
 * Apollo Speakers/DJs JavaScript
 * Slider, schedule tabs, and live status
 *
 * @package Apollo_Events_Manager
 * @since 2.0.0
 */

(function($) {
    'use strict';

    /**
     * DJ Slider
     */
    class ApolloDJSlider {
        constructor(element) {
            this.$slider = $(element);
            this.$track = this.$slider.find('.apollo-dj-slider__track');
            this.$slides = this.$slider.find('.apollo-dj-slider__slide');
            this.$prevBtn = this.$slider.find('.apollo-dj-slider__nav--prev');
            this.$nextBtn = this.$slider.find('.apollo-dj-slider__nav--next');

            this.currentIndex = 0;
            this.slidesPerView = this.getSlidesPerView();
            this.totalSlides = this.$slides.length;
            this.maxIndex = Math.max(0, this.totalSlides - this.slidesPerView);
            this.autoplay = this.$slider.data('autoplay') === 'true';
            this.autoplayInterval = null;

            this.init();
        }

        init() {
            if (this.totalSlides <= this.slidesPerView) {
                this.$prevBtn.hide();
                this.$nextBtn.hide();
                return;
            }

            this.bindEvents();
            this.updateNavigation();

            if (this.autoplay) {
                this.startAutoplay();
            }
        }

        getSlidesPerView() {
            const width = window.innerWidth;
            if (width <= 480) return 1;
            if (width <= 768) return 2;
            if (width <= 1024) return 3;
            return 4;
        }

        bindEvents() {
            this.$prevBtn.on('click', () => this.prev());
            this.$nextBtn.on('click', () => this.next());

            // Update on resize
            $(window).on('resize', this.debounce(() => {
                this.slidesPerView = this.getSlidesPerView();
                this.maxIndex = Math.max(0, this.totalSlides - this.slidesPerView);
                if (this.currentIndex > this.maxIndex) {
                    this.currentIndex = this.maxIndex;
                }
                this.updatePosition();
                this.updateNavigation();
            }, 250));

            // Pause on hover
            this.$slider.on('mouseenter', () => this.stopAutoplay());
            this.$slider.on('mouseleave', () => {
                if (this.autoplay) this.startAutoplay();
            });

            // Touch support
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
                        this.next();
                    } else {
                        this.prev();
                    }
                }
            });
        }

        prev() {
            if (this.currentIndex > 0) {
                this.currentIndex--;
                this.updatePosition();
                this.updateNavigation();
            }
        }

        next() {
            if (this.currentIndex < this.maxIndex) {
                this.currentIndex++;
                this.updatePosition();
                this.updateNavigation();
            }
        }

        updatePosition() {
            const slideWidth = 100 / this.slidesPerView;
            const offset = this.currentIndex * slideWidth;
            this.$track.css('transform', `translateX(-${offset}%)`);
        }

        updateNavigation() {
            this.$prevBtn.prop('disabled', this.currentIndex === 0);
            this.$nextBtn.prop('disabled', this.currentIndex >= this.maxIndex);
        }

        startAutoplay() {
            this.stopAutoplay();
            this.autoplayInterval = setInterval(() => {
                if (this.currentIndex >= this.maxIndex) {
                    this.currentIndex = 0;
                } else {
                    this.currentIndex++;
                }
                this.updatePosition();
                this.updateNavigation();
            }, 4000);
        }

        stopAutoplay() {
            if (this.autoplayInterval) {
                clearInterval(this.autoplayInterval);
                this.autoplayInterval = null;
            }
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
     * Schedule Tabs
     */
    class ApolloScheduleTabs {
        constructor(element) {
            this.$schedule = $(element);
            this.$tabs = this.$schedule.find('.apollo-schedule__tab');
            this.$panels = this.$schedule.find('.apollo-schedule__panel');

            this.init();
        }

        init() {
            this.$tabs.on('click', (e) => {
                const $tab = $(e.currentTarget);
                const stage = $tab.data('stage');

                this.$tabs.removeClass('is-active');
                $tab.addClass('is-active');

                this.$panels.removeClass('is-active');
                this.$panels.filter(`[data-stage="${stage}"]`).addClass('is-active');
            });
        }
    }

    /**
     * Live Timetable Status
     */
    class ApolloLiveTimetable {
        constructor(element) {
            this.$timetable = $(element);
            this.$slots = this.$timetable.find('.apollo-timetable__slot');
            this.$liveIndicator = this.$timetable.find('.apollo-timetable__live-indicator');
            this.eventDate = this.$timetable.data('event-date');

            this.init();
        }

        init() {
            if (!this.eventDate) return;

            this.updateStatus();
            setInterval(() => this.updateStatus(), 60000); // Update every minute
        }

        updateStatus() {
            const now = new Date();
            let hasLive = false;

            this.$slots.each((i, slot) => {
                const $slot = $(slot);
                const startTime = $slot.data('start');
                const endTime = $slot.data('end');

                if (!startTime) return;

                const slotStart = this.parseTime(this.eventDate, startTime);
                const slotEnd = endTime ? this.parseTime(this.eventDate, endTime) : new Date(slotStart.getTime() + 3600000);

                // Remove all status classes
                $slot.removeClass('apollo-timetable__slot--playing apollo-timetable__slot--finished apollo-timetable__slot--upcoming');

                if (now >= slotStart && now < slotEnd) {
                    $slot.addClass('apollo-timetable__slot--playing');
                    hasLive = true;

                    // Add or update live indicator
                    let $status = $slot.find('.apollo-timetable__status');
                    if (!$status.length) {
                        $status = $('<span class="apollo-timetable__status apollo-timetable__status--live"><span class="apollo-pulse"></span>Tocando Agora</span>');
                        $slot.find('.apollo-timetable__content').append($status);
                    }
                } else if (now >= slotEnd) {
                    $slot.addClass('apollo-timetable__slot--finished');
                    $slot.find('.apollo-timetable__status--live').remove();
                } else {
                    $slot.addClass('apollo-timetable__slot--upcoming');
                    $slot.find('.apollo-timetable__status--live').remove();
                }
            });

            // Update header indicator
            if (hasLive) {
                this.$liveIndicator.removeClass('is-hidden');
            } else {
                this.$liveIndicator.addClass('is-hidden');
            }
        }

        parseTime(dateStr, timeStr) {
            const [hours, minutes] = timeStr.split(':').map(Number);
            const date = new Date(dateStr);
            date.setHours(hours, minutes, 0, 0);
            return date;
        }
    }

    /**
     * DJ Card Hover Effects
     */
    function initCardEffects() {
        $('.apollo-dj-card').each(function() {
            const $card = $(this);
            const $social = $card.find('.apollo-dj-card__social');

            // Prevent social link clicks from navigating to profile
            $social.on('click', 'a', function(e) {
                e.stopPropagation();
            });
        });
    }

    /**
     * Smooth scroll to current playing slot
     */
    function scrollToCurrentSlot() {
        const $playing = $('.apollo-timetable__slot--playing');
        if ($playing.length) {
            const $timetable = $playing.closest('.apollo-timetable');
            const offset = $playing.offset().top - $timetable.offset().top;
            $timetable.find('.apollo-timetable__slots').animate({
                scrollTop: offset
            }, 500);
        }
    }

    /**
     * Initialize on document ready
     */
    $(document).ready(function() {
        // Initialize DJ sliders
        $('.apollo-dj-slider').each(function() {
            new ApolloDJSlider(this);
        });

        // Initialize schedule tabs
        $('.apollo-schedule').each(function() {
            new ApolloScheduleTabs(this);
        });

        // Initialize live timetable
        $('.apollo-timetable').each(function() {
            new ApolloLiveTimetable(this);
        });

        // Initialize card effects
        initCardEffects();

        // Scroll to current slot on page load
        setTimeout(scrollToCurrentSlot, 500);
    });

    // Make classes available globally
    window.ApolloDJSlider = ApolloDJSlider;
    window.ApolloScheduleTabs = ApolloScheduleTabs;
    window.ApolloLiveTimetable = ApolloLiveTimetable;

})(jQuery);
