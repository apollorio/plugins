/**
 * Apollo Map Tileset - JavaScript Helper
 *
 * Single source of truth for Leaflet tileset configuration.
 * All Apollo plugins should use this instead of hardcoded tile URLs.
 *
 * @package Apollo_Core
 * @since 3.2.0
 *
 * STRICT MODE: Do NOT hardcode tile URLs anywhere else.
 * Use window.ApolloMapTileset.apply(map) or window.apolloMapTileset.url
 */

(function(window) {
    'use strict';

    /**
     * Tileset configuration (populated via wp_localize_script).
     * Structure:
     * {
     *   id: 'osm-standard',
     *   url: 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
     *   options: { subdomains: 'abc', maxZoom: 19, attribution: '...' },
     *   attribution: '...'
     * }
     */
    var config = window.apolloMapTileset || {
        id: 'osm-standard',
        url: 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
        options: {
            subdomains: 'abc',
            maxZoom: 19,
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
        },
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
    };

    /**
     * ApolloMapTileset helper object.
     */
    window.ApolloMapTileset = {

        /**
         * Get the tileset URL.
         * @returns {string} Tile URL template.
         */
        getUrl: function() {
            return config.url;
        },

        /**
         * Get tileset options for L.tileLayer.
         * @returns {object} Options object (subdomains, maxZoom, attribution).
         */
        getOptions: function() {
            return config.options || {};
        },

        /**
         * Get attribution string.
         * @returns {string} Attribution HTML.
         */
        getAttribution: function() {
            return config.attribution || config.options.attribution || '';
        },

        /**
         * Get tileset ID.
         * @returns {string} Tileset identifier (osm-standard, carto-positron, carto-dark).
         */
        getId: function() {
            return config.id || 'osm-standard';
        },

        /**
         * Apply tileset to a Leaflet map.
         * This is the RECOMMENDED way to add tiles to any Apollo map.
         *
         * @param {L.Map} map - Leaflet map instance.
         * @param {object} overrides - Optional overrides for tile options.
         * @param {string} pane - Optional pane name for the tile layer.
         * @returns {L.TileLayer} The created tile layer.
         *
         * @example
         * var map = L.map('myMap').setView([-22.9068, -43.1729], 12);
         * ApolloMapTileset.apply(map);
         *
         * @example With custom pane
         * map.createPane('tilesPane');
         * ApolloMapTileset.apply(map, {}, 'tilesPane');
         */
        apply: function(map, overrides, pane) {
            if (typeof L === 'undefined') {
                console.error('[ApolloMapTileset] Leaflet not loaded');
                return null;
            }

            if (!map || typeof map.addLayer !== 'function') {
                console.error('[ApolloMapTileset] Invalid map instance');
                return null;
            }

            var options = Object.assign({}, this.getOptions(), overrides || {});

            // Add pane if specified.
            if (pane) {
                options.pane = pane;
            }

            var tileLayer = L.tileLayer(this.getUrl(), options);
            tileLayer.addTo(map);

            return tileLayer;
        },

        /**
         * Create a tile layer without adding to map.
         * Use when you need more control over layer management.
         *
         * @param {object} overrides - Optional overrides for tile options.
         * @returns {L.TileLayer} The created tile layer (not added to map).
         */
        create: function(overrides) {
            if (typeof L === 'undefined') {
                console.error('[ApolloMapTileset] Leaflet not loaded');
                return null;
            }

            var options = Object.assign({}, this.getOptions(), overrides || {});
            return L.tileLayer(this.getUrl(), options);
        },

        /**
         * Render attribution in a custom element.
         * Use when attributionControl is disabled but you still need compliance.
         *
         * @param {string|HTMLElement} target - Selector or element to render attribution into.
         */
        renderAttribution: function(target) {
            var element = typeof target === 'string' ? document.querySelector(target) : target;
            if (element) {
                element.innerHTML = this.getAttribution();
            }
        },

        /**
         * Ensure attribution is visible on the map.
         * If attributionControl is false, creates a small attribution element.
         *
         * @param {L.Map} map - Leaflet map instance.
         * @param {string} position - Position: 'bottomright', 'bottomleft', etc.
         */
        ensureAttribution: function(map, position) {
            if (!map) return;

            // Check if attribution control exists.
            var hasAttribution = false;
            map.eachLayer(function(layer) {
                if (layer.getAttribution) {
                    hasAttribution = true;
                }
            });

            // If no attribution control, add custom element.
            var mapContainer = map.getContainer();
            if (mapContainer && !mapContainer.querySelector('.apollo-map-attribution')) {
                var attrDiv = document.createElement('div');
                attrDiv.className = 'apollo-map-attribution';
                attrDiv.style.cssText = 'position:absolute;bottom:2px;right:4px;z-index:1000;font-size:10px;color:#666;background:rgba(255,255,255,0.7);padding:1px 4px;border-radius:2px;pointer-events:auto;';
                attrDiv.innerHTML = this.getAttribution();
                mapContainer.style.position = 'relative';
                mapContainer.appendChild(attrDiv);
            }
        }
    };

    // Freeze to prevent accidental modification.
    if (Object.freeze) {
        Object.freeze(window.ApolloMapTileset);
    }

})(window);
