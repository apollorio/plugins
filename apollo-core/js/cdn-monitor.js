/**
 * Apollo CDN Performance Monitor JavaScript
 * Tracks CDN asset loading performance and Core Web Vitals
 */

(function () {
    'use strict';

    const ApolloCDNMonitor = {
        config: window.apolloCDNMonitor || {},
        performanceData: [],

        init: function () {
            this.setupPerformanceObserver();
            this.setupWebVitalsTracking();
            this.setupUnloadHandler();
        },

        setupPerformanceObserver: function () {
            if (!window.PerformanceObserver) {
                console.warn('PerformanceObserver not supported');
                return;
            }

            try {
                const observer = new PerformanceObserver((list) => {
                    const entries = list.getEntries();
                    entries.forEach((entry) => {
                        if (entry.name.includes(this.config.cdn_base)) {
                            this.performanceData.push({
                                url: entry.name,
                                loadTime: entry.duration,
                                size: entry.transferSize || 0,
                                type: entry.initiatorType,
                                timestamp: Date.now()
                            });
                        }
                    });
                });

                observer.observe({ entryTypes: ['resource'] });
            } catch (error) {
                console.error('Failed to setup performance observer:', error);
            }
        },

        setupWebVitalsTracking: function () {
            // Check if web-vitals library is available
            if (typeof webVitals !== 'undefined') {
                try {
                    webVitals.getCLS((metric) => this.trackWebVital('CLS', metric));
                    webVitals.getFID((metric) => this.trackWebVital('FID', metric));
                    webVitals.getFCP((metric) => this.trackWebVital('FCP', metric));
                    webVitals.getLCP((metric) => this.trackWebVital('LCP', metric));
                    webVitals.getTTFB((metric) => this.trackWebVital('TTFB', metric));
                } catch (error) {
                    console.warn('Web Vitals tracking failed:', error);
                }
            }
        },

        trackWebVital: function (name, metric) {
            const vitalData = {
                name: name,
                value: metric.value,
                rating: metric.rating,
                timestamp: Date.now(),
                url: window.location.href
            };

            // Store locally and send to server
            this.storeWebVital(vitalData);
            this.sendWebVitalToServer(vitalData);
        },

        storeWebVital: function (data) {
            const vitals = JSON.parse(localStorage.getItem('apollo_web_vitals') || '[]');
            vitals.push(data);

            // Keep only last 20 vitals
            if (vitals.length > 20) {
                vitals.splice(0, vitals.length - 20);
            }

            localStorage.setItem('apollo_web_vitals', JSON.stringify(vitals));
        },

        sendWebVitalToServer: function (data) {
            if (navigator.sendBeacon) {
                const blob = new Blob([JSON.stringify({
                    action: 'apollo_web_vitals',
                    nonce: this.config.nonce,
                    data: data
                })], { type: 'application/json' });

                navigator.sendBeacon(this.config.ajax_url, blob);
            }
        },

        setupUnloadHandler: function () {
            window.addEventListener('beforeunload', () => {
                if (this.performanceData.length > 0) {
                    this.sendPerformanceData();
                }
            });

            // Also send data periodically for long sessions
            setInterval(() => {
                if (this.performanceData.length > 0) {
                    this.sendPerformanceData();
                }
            }, 30000); // Every 30 seconds
        },

        sendPerformanceData: function () {
            if (navigator.sendBeacon && this.performanceData.length > 0) {
                const data = {
                    action: 'apollo_cdn_performance_data',
                    nonce: this.config.nonce,
                    data: this.performanceData
                };

                const blob = new Blob([JSON.stringify(data)], { type: 'application/json' });
                navigator.sendBeacon(this.config.ajax_url, blob);

                // Clear sent data
                this.performanceData = [];
            }
        },

        // Utility method to check if asset is slow loading
        checkAssetPerformance: function (url) {
            const asset = this.performanceData.find(item => item.url === url);
            if (asset) {
                return {
                    isSlow: asset.loadTime > this.config.threshold,
                    loadTime: asset.loadTime,
                    size: asset.size
                };
            }
            return null;
        },

        // Get performance summary
        getPerformanceSummary: function () {
            const summary = {
                totalAssets: this.performanceData.length,
                slowAssets: 0,
                averageLoadTime: 0,
                totalSize: 0
            };

            if (this.performanceData.length === 0) {
                return summary;
            }

            let totalTime = 0;
            this.performanceData.forEach(asset => {
                totalTime += asset.loadTime;
                summary.totalSize += asset.size;

                if (asset.loadTime > this.config.threshold) {
                    summary.slowAssets++;
                }
            });

            summary.averageLoadTime = totalTime / this.performanceData.length;

            return summary;
        }
    };

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => ApolloCDNMonitor.init());
    } else {
        ApolloCDNMonitor.init();
    }

    // Expose for debugging
    window.ApolloCDNMonitor = ApolloCDNMonitor;

})();
