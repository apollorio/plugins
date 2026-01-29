/**
 * Apollo API Client
 *
 * JavaScript client for Apollo REST API and AJAX fallback.
 * Provides a unified interface for frontend data fetching.
 *
 * @package Apollo_Core
 * @since 2.0.0
 */

(function (window, document) {
    'use strict';

    /**
     * Apollo API Configuration
     * Populated via wp_localize_script()
     */
    const config = window.apolloApiConfig || {
        restUrl: '/wp-json/apollo/v1',
        ajaxUrl: '/wp-admin/admin-ajax.php',
        nonce: '',
        useRest: true,
    };

    /**
     * HTTP methods enum
     */
    const Methods = {
        GET: 'GET',
        POST: 'POST',
        PUT: 'PUT',
        DELETE: 'DELETE',
    };

    /**
     * Make a REST API request
     *
     * @param {string} endpoint - API endpoint
     * @param {Object} options - Request options
     * @returns {Promise<Object>}
     */
    async function restRequest(endpoint, options = {}) {
        const { method = Methods.GET, data = null, params = {} } = options;

        let url = `${config.restUrl}${endpoint}`;

        // Add query params for GET requests
        if (method === Methods.GET && Object.keys(params).length > 0) {
            const searchParams = new URLSearchParams();
            Object.entries(params).forEach(([key, value]) => {
                if (Array.isArray(value)) {
                    value.forEach((v) => searchParams.append(`${key}[]`, v));
                } else if (value !== null && value !== undefined) {
                    searchParams.append(key, value);
                }
            });
            url += `?${searchParams.toString()}`;
        }

        const fetchOptions = {
            method,
            headers: {
                'Content-Type': 'application/json',
                'X-WP-Nonce': config.nonce,
            },
            credentials: 'same-origin',
        };

        if (data && method !== Methods.GET) {
            fetchOptions.body = JSON.stringify(data);
        }

        try {
            const response = await fetch(url, fetchOptions);
            const result = await response.json();

            if (!response.ok) {
                throw new ApiError(
                    result.message || result.code || 'Request failed',
                    result.code || 'api_error',
                    response.status
                );
            }

            return result;
        } catch (error) {
            if (error instanceof ApiError) {
                throw error;
            }
            throw new ApiError(error.message, 'network_error', 0);
        }
    }

    /**
     * Make an AJAX request (fallback)
     *
     * @param {string} action - AJAX action name
     * @param {Object} data - Request data
     * @returns {Promise<Object>}
     */
    async function ajaxRequest(action, data = {}) {
        const formData = new FormData();
        formData.append('action', `apollo_${action}`);
        formData.append('nonce', config.nonce);

        Object.entries(data).forEach(([key, value]) => {
            if (Array.isArray(value)) {
                value.forEach((v) => formData.append(`${key}[]`, v));
            } else if (value !== null && value !== undefined) {
                formData.append(key, value);
            }
        });

        try {
            const response = await fetch(config.ajaxUrl, {
                method: Methods.POST,
                credentials: 'same-origin',
                body: formData,
            });

            const result = await response.json();

            if (!result.success) {
                throw new ApiError(
                    result.message || 'Request failed',
                    result.code || 'ajax_error',
                    response.status
                );
            }

            return result;
        } catch (error) {
            if (error instanceof ApiError) {
                throw error;
            }
            throw new ApiError(error.message, 'network_error', 0);
        }
    }

    /**
     * Custom API Error class
     */
    class ApiError extends Error {
        constructor(message, code, status) {
            super(message);
            this.name = 'ApiError';
            this.code = code;
            this.status = status;
        }
    }

    /**
     * Events API Module
     */
    const Events = {
        /**
         * Get events list
         *
         * @param {Object} params - Query parameters
         * @returns {Promise<Object>}
         */
        async getAll(params = {}) {
            if (config.useRest) {
                return restRequest('/events', { params });
            }
            return ajaxRequest('get_events', params);
        },

        /**
         * Get single event
         *
         * @param {number} id - Event ID
         * @returns {Promise<Object>}
         */
        async get(id) {
            if (config.useRest) {
                return restRequest(`/events/${id}`);
            }
            return ajaxRequest('get_event', { id });
        },

        /**
         * Create event
         *
         * @param {Object} data - Event data
         * @returns {Promise<Object>}
         */
        async create(data) {
            if (config.useRest) {
                return restRequest('/events', { method: Methods.POST, data });
            }
            return ajaxRequest('create_event', data);
        },

        /**
         * Update event
         *
         * @param {number} id - Event ID
         * @param {Object} data - Event data
         * @returns {Promise<Object>}
         */
        async update(id, data) {
            if (config.useRest) {
                return restRequest(`/events/${id}`, { method: Methods.PUT, data });
            }
            return ajaxRequest('update_event', { id, ...data });
        },

        /**
         * Delete event
         *
         * @param {number} id - Event ID
         * @param {boolean} force - Force delete (skip trash)
         * @returns {Promise<Object>}
         */
        async delete(id, force = false) {
            if (config.useRest) {
                return restRequest(`/events/${id}`, {
                    method: Methods.DELETE,
                    data: { force },
                });
            }
            return ajaxRequest('delete_event', { id, force });
        },

        /**
         * Get calendar events
         *
         * @param {number} month - Month (1-12)
         * @param {number} year - Year
         * @param {Array} category - Category IDs
         * @returns {Promise<Object>}
         */
        async getCalendar(month, year, category = []) {
            const params = { month, year };
            if (category.length > 0) {
                params.category = category;
            }

            if (config.useRest) {
                return restRequest('/events/calendar', { params });
            }
            return ajaxRequest('get_calendar', params);
        },

        /**
         * Get upcoming events
         *
         * @param {number} limit - Number of events
         * @returns {Promise<Object>}
         */
        async getUpcoming(limit = 5) {
            if (config.useRest) {
                return restRequest('/events/upcoming', { params: { limit } });
            }
            return ajaxRequest('get_events', { upcoming: true, per_page: limit });
        },

        /**
         * Toggle favorite
         *
         * @param {number} id - Event ID
         * @returns {Promise<Object>}
         */
        async toggleFavorite(id) {
            if (config.useRest) {
                return restRequest(`/events/${id}/favorite`, { method: Methods.POST });
            }
            return ajaxRequest('toggle_favorite', { event_id: id });
        },
    };

    /**
     * Social Feed API Module
     */
    const Feed = {
        /**
         * Get feed items
         *
         * @param {Object} params - Query parameters
         * @returns {Promise<Object>}
         */
        async getAll(params = {}) {
            if (config.useRest) {
                return restRequest('/social/feed', { params });
            }
            return ajaxRequest('get_feed', params);
        },

        /**
         * Get single activity
         *
         * @param {number} id - Activity ID
         * @returns {Promise<Object>}
         */
        async get(id) {
            if (config.useRest) {
                return restRequest(`/social/feed/${id}`);
            }
            return ajaxRequest('get_activity', { id });
        },

        /**
         * Create activity
         *
         * @param {Object} data - Activity data
         * @returns {Promise<Object>}
         */
        async create(data) {
            if (config.useRest) {
                return restRequest('/social/feed', { method: Methods.POST, data });
            }
            return ajaxRequest('create_activity', data);
        },

        /**
         * Delete activity
         *
         * @param {number} id - Activity ID
         * @returns {Promise<Object>}
         */
        async delete(id) {
            if (config.useRest) {
                return restRequest(`/social/feed/${id}`, { method: Methods.DELETE });
            }
            return ajaxRequest('delete_activity', { id });
        },

        /**
         * Toggle like
         *
         * @param {number} id - Activity ID
         * @returns {Promise<Object>}
         */
        async toggleLike(id) {
            if (config.useRest) {
                return restRequest(`/social/feed/${id}/like`, { method: Methods.POST });
            }
            return ajaxRequest('toggle_like', { activity_id: id });
        },

        /**
         * Add comment
         *
         * @param {number} id - Activity ID
         * @param {string} content - Comment content
         * @returns {Promise<Object>}
         */
        async addComment(id, content) {
            if (config.useRest) {
                return restRequest(`/social/feed/${id}/comment`, {
                    method: Methods.POST,
                    data: { content },
                });
            }
            return ajaxRequest('add_comment', { activity_id: id, content });
        },
    };

    /**
     * Users/Profile API Module
     */
    const Users = {
        /**
         * Get user profile
         *
         * @param {number} id - User ID
         * @returns {Promise<Object>}
         */
        async getProfile(id) {
            if (config.useRest) {
                return restRequest(`/users/${id}/profile`);
            }
            return ajaxRequest('get_profile', { user_id: id });
        },

        /**
         * Update profile
         *
         * @param {number} id - User ID
         * @param {Object} data - Profile data
         * @returns {Promise<Object>}
         */
        async updateProfile(id, data) {
            if (config.useRest) {
                return restRequest(`/users/${id}/profile`, {
                    method: Methods.PUT,
                    data,
                });
            }
            return ajaxRequest('update_profile', { user_id: id, ...data });
        },

        /**
         * Get user activity
         *
         * @param {number} id - User ID
         * @param {Object} params - Query parameters
         * @returns {Promise<Object>}
         */
        async getActivity(id, params = {}) {
            if (config.useRest) {
                return restRequest(`/users/${id}/activity`, { params });
            }
            return ajaxRequest('get_user_activity', { user_id: id, ...params });
        },

        /**
         * Get user favorites
         *
         * @param {number} id - User ID
         * @param {string} type - Favorite type (events, venues, all)
         * @returns {Promise<Object>}
         */
        async getFavorites(id, type = 'all') {
            if (config.useRest) {
                return restRequest(`/users/${id}/favorites`, { params: { type } });
            }
            return ajaxRequest('get_favorites', { user_id: id, type });
        },

        /**
         * Toggle follow
         *
         * @param {number} id - User ID to follow
         * @returns {Promise<Object>}
         */
        async toggleFollow(id) {
            if (config.useRest) {
                return restRequest(`/users/${id}/follow`, { method: Methods.POST });
            }
            return ajaxRequest('toggle_follow', { user_id: id });
        },

        /**
         * Get followers
         *
         * @param {number} id - User ID
         * @param {Object} params - Query parameters
         * @returns {Promise<Object>}
         */
        async getFollowers(id, params = {}) {
            if (config.useRest) {
                return restRequest(`/users/${id}/followers`, { params });
            }
            return ajaxRequest('get_followers', { user_id: id, ...params });
        },

        /**
         * Get following
         *
         * @param {number} id - User ID
         * @param {Object} params - Query parameters
         * @returns {Promise<Object>}
         */
        async getFollowing(id, params = {}) {
            if (config.useRest) {
                return restRequest(`/users/${id}/following`, { params });
            }
            return ajaxRequest('get_following', { user_id: id, ...params });
        },
    };

    /**
     * Classifieds API Module
     */
    const Classifieds = {
        /**
         * Get classifieds list
         *
         * @param {Object} params - Query parameters
         * @returns {Promise<Object>}
         */
        async getAll(params = {}) {
            if (config.useRest) {
                return restRequest('/classifieds', { params });
            }
            return ajaxRequest('get_classifieds', params);
        },

        /**
         * Get single classified
         *
         * @param {number} id - Classified ID
         * @returns {Promise<Object>}
         */
        async get(id) {
            if (config.useRest) {
                return restRequest(`/classifieds/${id}`);
            }
            return ajaxRequest('get_classified', { id });
        },

        /**
         * Create classified
         *
         * @param {Object} data - Classified data
         * @returns {Promise<Object>}
         */
        async create(data) {
            if (config.useRest) {
                return restRequest('/classifieds', { method: Methods.POST, data });
            }
            return ajaxRequest('create_classified', data);
        },

        /**
         * Update classified
         *
         * @param {number} id - Classified ID
         * @param {Object} data - Classified data
         * @returns {Promise<Object>}
         */
        async update(id, data) {
            if (config.useRest) {
                return restRequest(`/classifieds/${id}`, { method: Methods.PUT, data });
            }
            return ajaxRequest('update_classified', { id, ...data });
        },

        /**
         * Delete classified
         *
         * @param {number} id - Classified ID
         * @returns {Promise<Object>}
         */
        async delete(id) {
            if (config.useRest) {
                return restRequest(`/classifieds/${id}`, { method: Methods.DELETE });
            }
            return ajaxRequest('delete_classified', { id });
        },

        /**
         * Send contact message
         *
         * @param {number} id - Classified ID
         * @param {string} message - Contact message
         * @returns {Promise<Object>}
         */
        async sendContact(id, message) {
            if (config.useRest) {
                return restRequest(`/classifieds/${id}/contact`, {
                    method: Methods.POST,
                    data: { message },
                });
            }
            return ajaxRequest('send_contact', { classified_id: id, message });
        },

        /**
         * Get categories
         *
         * @returns {Promise<Object>}
         */
        async getCategories() {
            if (config.useRest) {
                return restRequest('/classifieds/categories');
            }
            return ajaxRequest('get_classified_categories');
        },
    };

    /**
     * Utility functions
     */
    const Utils = {
        /**
         * Check if user is logged in
         *
         * @returns {boolean}
         */
        isLoggedIn() {
            return Boolean(config.userId);
        },

        /**
         * Get current user ID
         *
         * @returns {number}
         */
        getCurrentUserId() {
            return config.userId || 0;
        },

        /**
         * Show notification
         *
         * @param {string} message - Notification message
         * @param {string} type - Notification type (success, error, warning, info)
         */
        notify(message, type = 'info') {
            const event = new CustomEvent('apollo:notify', {
                detail: { message, type },
            });
            document.dispatchEvent(event);
        },

        /**
         * Debounce function
         *
         * @param {Function} func - Function to debounce
         * @param {number} wait - Wait time in ms
         * @returns {Function}
         */
        debounce(func, wait = 300) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        },

        /**
         * Format date
         *
         * @param {string} dateString - Date string
         * @param {Object} options - Intl.DateTimeFormat options
         * @returns {string}
         */
        formatDate(dateString, options = {}) {
            const defaults = {
                day: '2-digit',
                month: 'short',
                year: 'numeric',
            };
            const date = new Date(dateString);
            return new Intl.DateTimeFormat('pt-BR', { ...defaults, ...options }).format(date);
        },

        /**
         * Format currency
         *
         * @param {number} value - Value to format
         * @param {string} currency - Currency code
         * @returns {string}
         */
        formatCurrency(value, currency = 'BRL') {
            return new Intl.NumberFormat('pt-BR', {
                style: 'currency',
                currency,
            }).format(value);
        },
    };

    /**
     * Apollo API public interface
     */
    const ApolloApi = {
        Events,
        Feed,
        Users,
        Classifieds,
        Utils,
        ApiError,
        config,

        /**
         * Initialize API with config
         *
         * @param {Object} newConfig - Configuration options
         */
        init(newConfig) {
            Object.assign(config, newConfig);
        },

        /**
         * Set nonce
         *
         * @param {string} nonce - New nonce value
         */
        setNonce(nonce) {
            config.nonce = nonce;
        },

        /**
         * Enable/disable REST API
         *
         * @param {boolean} useRest - Use REST API
         */
        useRestApi(useRest) {
            config.useRest = useRest;
        },

        /**
         * Raw REST request
         *
         * @param {string} endpoint - API endpoint
         * @param {Object} options - Request options
         * @returns {Promise<Object>}
         */
        rest: restRequest,

        /**
         * Raw AJAX request
         *
         * @param {string} action - AJAX action
         * @param {Object} data - Request data
         * @returns {Promise<Object>}
         */
        ajax: ajaxRequest,
    };

    // Expose to global scope
    window.ApolloApi = ApolloApi;

    // AMD support
    if (typeof define === 'function' && define.amd) {
        define('apollo-api', [], function () {
            return ApolloApi;
        });
    }

    // CommonJS support
    if (typeof module === 'object' && module.exports) {
        module.exports = ApolloApi;
    }

})(window, document);
