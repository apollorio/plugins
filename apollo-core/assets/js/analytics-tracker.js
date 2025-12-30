/**
 * Apollo Advanced Analytics Tracker
 *
 * Self-hosted tracking script - NO external dependencies.
 * Tracks: pageviews, scroll depth, clicks, mouse movements, form interactions.
 *
 * @package Apollo_Core
 * @since 3.0.0
 */

(function(window, document) {
    'use strict';

    // Config from PHP
    const config = window.apolloAnalytics || {};
    if (!config.ajaxUrl || !config.nonce) {
        console.warn('[Apollo Analytics] Missing configuration');
        return;
    }

    // State
    const state = {
        sessionId: config.sessionId || '',
        userId: config.userId || 0,
        pageviewId: null,
        pageType: config.pageType || 'page',
        postId: config.postId || 0,
        startTime: Date.now(),
        maxScrollDepth: 0,
        scrollMilestones: [],
        interactionQueue: [],
        heatmapQueue: [],
        isVisible: true,
        lastActivity: Date.now(),
        pageviewsCount: 1
    };

    // =========================================================================
    // UTILITY FUNCTIONS
    // =========================================================================

    function debounce(func, wait) {
        let timeout;
        return function(...args) {
            clearTimeout(timeout);
            timeout = setTimeout(() => func.apply(this, args), wait);
        };
    }

    function throttle(func, limit) {
        let inThrottle;
        return function(...args) {
            if (!inThrottle) {
                func.apply(this, args);
                inThrottle = true;
                setTimeout(() => inThrottle = false, limit);
            }
        };
    }

    function getScrollDepth() {
        const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
        const docHeight = Math.max(
            document.body.scrollHeight,
            document.documentElement.scrollHeight
        );
        const winHeight = window.innerHeight;
        const scrollableHeight = docHeight - winHeight;

        if (scrollableHeight <= 0) return 100;
        return Math.round((scrollTop / scrollableHeight) * 100);
    }

    function getElementInfo(element) {
        if (!element || element === document) return {};

        return {
            tag: element.tagName ? element.tagName.toLowerCase() : '',
            id: element.id || '',
            class: element.className && typeof element.className === 'string'
                ? element.className.split(' ').slice(0, 5).join(' ')
                : '',
            text: element.textContent
                ? element.textContent.trim().substring(0, 100)
                : '',
            href: element.href || element.closest('a')?.href || ''
        };
    }

    function sendAjax(action, data, callback) {
        const formData = new FormData();
        formData.append('action', action);
        formData.append('nonce', config.nonce);

        for (const key in data) {
            if (typeof data[key] === 'object') {
                formData.append(key, JSON.stringify(data[key]));
            } else {
                formData.append(key, data[key]);
            }
        }

        fetch(config.ajaxUrl, {
            method: 'POST',
            credentials: 'same-origin',
            body: formData
        })
        .then(response => response.json())
        .then(result => {
            if (callback) callback(result);
            if (config.isDebug) {
                console.log('[Apollo Analytics]', action, result);
            }
        })
        .catch(error => {
            if (config.isDebug) {
                console.error('[Apollo Analytics]', action, error);
            }
        });
    }

    // =========================================================================
    // PAGEVIEW TRACKING
    // =========================================================================

    function trackPageview() {
        const data = {
            session_id: state.sessionId,
            user_id: state.userId,
            page_url: window.location.href,
            page_title: document.title,
            page_type: state.pageType,
            post_id: state.postId,
            referrer: document.referrer,
            screen_width: window.screen.width,
            screen_height: window.screen.height,
            viewport_width: window.innerWidth,
            viewport_height: window.innerHeight
        };

        sendAjax('apollo_track_pageview', data, function(result) {
            if (result.success && result.data) {
                state.pageviewId = result.data.pageview_id;
            }
        });
    }

    // =========================================================================
    // SCROLL TRACKING
    // =========================================================================

    function initScrollTracking() {
        if (!config.trackScrollDepth) return;

        const thresholds = [25, 50, 75, 100];
        let lastDirection = null;
        let lastScrollTop = 0;

        const onScroll = throttle(function() {
            const depth = getScrollDepth();
            const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
            const direction = scrollTop > lastScrollTop ? 'down' : 'up';

            // Update max scroll
            if (depth > state.maxScrollDepth) {
                state.maxScrollDepth = depth;
            }

            // Track milestones
            thresholds.forEach(threshold => {
                if (depth >= threshold && !state.scrollMilestones.includes(threshold)) {
                    state.scrollMilestones.push(threshold);
                    queueInteraction({
                        type: 'scroll',
                        scroll_depth: threshold,
                        scroll_direction: direction,
                        viewport_percent: depth,
                        time_on_page: Math.round((Date.now() - state.startTime) / 1000)
                    });
                }
            });

            lastScrollTop = scrollTop;
            lastDirection = direction;
            state.lastActivity = Date.now();
        }, 250);

        window.addEventListener('scroll', onScroll, { passive: true });
    }

    // =========================================================================
    // CLICK TRACKING
    // =========================================================================

    function initClickTracking() {
        if (!config.trackClicks) return;

        document.addEventListener('click', function(e) {
            const target = e.target.closest('a, button, [role="button"], input[type="submit"], .clickable');
            const element = target || e.target;
            const info = getElementInfo(element);

            // Determine click type
            let clickType = 'click';
            if (info.href) {
                if (info.href.startsWith('mailto:')) {
                    clickType = 'email_click';
                } else if (info.href.startsWith('tel:')) {
                    clickType = 'phone_click';
                } else if (!info.href.includes(window.location.hostname)) {
                    clickType = 'outbound_link';
                }
            }
            if (info.href && info.href.match(/\.(pdf|doc|docx|xls|xlsx|zip|rar)$/i)) {
                clickType = 'download';
            }

            queueInteraction({
                type: clickType,
                element_tag: info.tag,
                element_id: info.id,
                element_class: info.class,
                element_text: info.text,
                element_href: info.href,
                position_x: e.clientX,
                position_y: e.clientY,
                time_on_page: Math.round((Date.now() - state.startTime) / 1000)
            });

            // Queue heatmap point
            if (config.trackMouseMove) {
                queueHeatmapPoint(e.clientX, e.clientY, 'click');
            }

            state.lastActivity = Date.now();
        }, { passive: true });
    }

    // =========================================================================
    // MOUSE MOVEMENT TRACKING (HEATMAP)
    // =========================================================================

    function initMouseTracking() {
        if (!config.trackMouseMove) return;

        let lastX = 0, lastY = 0;
        let moveTimeout;

        const onMouseMove = throttle(function(e) {
            lastX = e.clientX;
            lastY = e.clientY;

            // Clear previous stop timeout
            clearTimeout(moveTimeout);

            // Track "stop" positions (where mouse pauses)
            moveTimeout = setTimeout(function() {
                queueHeatmapPoint(lastX, lastY, 'hover');
            }, 1000);

            state.lastActivity = Date.now();
        }, 100);

        document.addEventListener('mousemove', onMouseMove, { passive: true });
    }

    function queueHeatmapPoint(x, y, type) {
        const docWidth = document.documentElement.scrollWidth;
        const docHeight = document.documentElement.scrollHeight;
        const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
        const scrollLeft = window.pageXOffset || document.documentElement.scrollLeft;

        state.heatmapQueue.push({
            x_percent: ((x + scrollLeft) / docWidth) * 100,
            y_percent: ((y + scrollTop) / docHeight) * 100,
            type: type
        });

        // Flush when queue is large
        if (state.heatmapQueue.length >= 50) {
            flushHeatmapQueue();
        }
    }

    function flushHeatmapQueue() {
        if (state.heatmapQueue.length === 0) return;

        const points = [...state.heatmapQueue];
        state.heatmapQueue = [];

        sendAjax('apollo_track_heatmap', {
            page_url: window.location.href,
            points: points
        });
    }

    // =========================================================================
    // FORM TRACKING
    // =========================================================================

    function initFormTracking() {
        if (!config.trackFormFocus) return;

        // Track form field focus
        document.addEventListener('focusin', function(e) {
            if (e.target.matches('input, textarea, select')) {
                const info = getElementInfo(e.target);
                queueInteraction({
                    type: 'form_focus',
                    element_tag: info.tag,
                    element_id: info.id,
                    element_class: info.class,
                    extra_data: {
                        field_name: e.target.name || '',
                        field_type: e.target.type || ''
                    },
                    time_on_page: Math.round((Date.now() - state.startTime) / 1000)
                });
            }
            state.lastActivity = Date.now();
        }, { passive: true });

        // Track form submissions
        document.addEventListener('submit', function(e) {
            const form = e.target;
            const info = getElementInfo(form);

            queueInteraction({
                type: 'form_submit',
                element_tag: 'form',
                element_id: info.id,
                element_class: info.class,
                extra_data: {
                    form_action: form.action || '',
                    form_method: form.method || 'get'
                },
                time_on_page: Math.round((Date.now() - state.startTime) / 1000)
            });

            // Flush immediately for form submits
            flushInteractionQueue();
            state.lastActivity = Date.now();
        }, { passive: true });
    }

    // =========================================================================
    // VIDEO TRACKING
    // =========================================================================

    function initVideoTracking() {
        // Track HTML5 video events
        document.addEventListener('play', function(e) {
            if (e.target.tagName === 'VIDEO') {
                queueInteraction({
                    type: 'video_play',
                    element_id: e.target.id,
                    extra_data: {
                        video_src: e.target.currentSrc || '',
                        video_duration: e.target.duration || 0,
                        video_currentTime: e.target.currentTime || 0
                    },
                    time_on_page: Math.round((Date.now() - state.startTime) / 1000)
                });
            }
            state.lastActivity = Date.now();
        }, true);

        document.addEventListener('pause', function(e) {
            if (e.target.tagName === 'VIDEO') {
                queueInteraction({
                    type: 'video_pause',
                    element_id: e.target.id,
                    extra_data: {
                        video_currentTime: e.target.currentTime || 0,
                        video_duration: e.target.duration || 0
                    },
                    time_on_page: Math.round((Date.now() - state.startTime) / 1000)
                });
            }
            state.lastActivity = Date.now();
        }, true);
    }

    // =========================================================================
    // INTERACTION QUEUE
    // =========================================================================

    function queueInteraction(data) {
        data.session_id = state.sessionId;
        data.pageview_id = state.pageviewId;
        data.user_id = state.userId;

        state.interactionQueue.push(data);

        // Flush when queue reaches batch size
        if (state.interactionQueue.length >= config.batchSize) {
            flushInteractionQueue();
        }
    }

    function flushInteractionQueue() {
        if (state.interactionQueue.length === 0) return;

        const interactions = [...state.interactionQueue];
        state.interactionQueue = [];

        sendAjax('apollo_track_interaction', {
            session_id: state.sessionId,
            interactions: interactions
        });
    }

    // =========================================================================
    // VISIBILITY & HEARTBEAT
    // =========================================================================

    function initVisibilityTracking() {
        document.addEventListener('visibilitychange', function() {
            state.isVisible = !document.hidden;

            if (!state.isVisible) {
                // Tab hidden - pause tracking
                flushInteractionQueue();
                flushHeatmapQueue();
            }
        });
    }

    function initHeartbeat() {
        setInterval(function() {
            // Only if page is visible and there's been recent activity
            if (state.isVisible && (Date.now() - state.lastActivity) < 60000) {
                // Periodic flush of queues
                flushInteractionQueue();
            }
        }, config.heartbeatInterval * 1000);
    }

    // =========================================================================
    // SESSION END TRACKING
    // =========================================================================

    function trackSessionEnd() {
        const data = {
            session_id: state.sessionId,
            exit_page: window.location.href,
            total_time: Math.round((Date.now() - state.startTime) / 1000),
            max_scroll: state.maxScrollDepth,
            pageviews_count: state.pageviewsCount
        };

        // Use sendBeacon for reliability on page unload
        if (navigator.sendBeacon) {
            const formData = new FormData();
            formData.append('action', 'apollo_track_session_end');
            formData.append('nonce', config.nonce);
            for (const key in data) {
                formData.append(key, data[key]);
            }
            navigator.sendBeacon(config.ajaxUrl, formData);
        } else {
            // Fallback
            sendAjax('apollo_track_session_end', data);
        }

        // Also flush any remaining data
        flushInteractionQueue();
        flushHeatmapQueue();
    }

    function initUnloadTracking() {
        window.addEventListener('beforeunload', trackSessionEnd);
        window.addEventListener('pagehide', trackSessionEnd);
    }

    // =========================================================================
    // SPA NAVIGATION SUPPORT
    // =========================================================================

    function initSpaSupport() {
        // Track history changes for SPA navigation
        const originalPushState = history.pushState;
        const originalReplaceState = history.replaceState;

        history.pushState = function() {
            originalPushState.apply(this, arguments);
            onNavigate();
        };

        history.replaceState = function() {
            originalReplaceState.apply(this, arguments);
            onNavigate();
        };

        window.addEventListener('popstate', onNavigate);
    }

    function onNavigate() {
        // Flush current page data
        flushInteractionQueue();
        flushHeatmapQueue();

        // Reset state for new page
        state.startTime = Date.now();
        state.maxScrollDepth = 0;
        state.scrollMilestones = [];
        state.pageviewsCount++;

        // Track new pageview
        setTimeout(trackPageview, 100);
    }

    // =========================================================================
    // CUSTOM EVENT API
    // =========================================================================

    window.apolloTrack = function(eventType, data) {
        queueInteraction({
            type: 'custom',
            extra_data: {
                custom_event_type: eventType,
                ...data
            },
            time_on_page: Math.round((Date.now() - state.startTime) / 1000)
        });
    };

    // =========================================================================
    // INITIALIZATION
    // =========================================================================

    function init() {
        // Track initial pageview
        trackPageview();

        // Initialize trackers
        initScrollTracking();
        initClickTracking();
        initMouseTracking();
        initFormTracking();
        initVideoTracking();
        initVisibilityTracking();
        initHeartbeat();
        initUnloadTracking();
        initSpaSupport();

        if (config.isDebug) {
            console.log('[Apollo Analytics] Initialized', {
                sessionId: state.sessionId,
                userId: state.userId,
                config: config
            });
        }
    }

    // Start when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

})(window, document);
