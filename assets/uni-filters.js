/*! Apollo::Uni Filters v1 — Não remova a IIFE */
(function(window, document) {
    if (window.__APOLLO_UNI_INIT__) return; // Evita dupla execução
    window.__APOLLO_UNI_INIT__ = true;

    /**
     * Remove accents from string
     */
    function removeAccents(str) {
        try {
            return (str || '').normalize('NFD').replace(/[\u0300-\u036f]/g, '');
        } catch (e) {
            return (str || '');
        }
    }

    /**
     * Normalize string for filtering
     */
    function norm(s) {
        return removeAccents(String(s || '')).toLowerCase().trim();
    }

    /**
     * Initialize Event Grid Filters
     */
    function initEventGrid() {
        const grid = document.querySelector('.apollo-events-grid');
        if (!grid) return;

        const q      = document.getElementById('apollo-q');
        const sounds = document.getElementById('apollo-sounds');
        const cats   = document.getElementById('apollo-cats');
        const cards  = Array.from(document.querySelectorAll('.apollo-event-card'));

        function apply() {
            const qv = norm(q && q.value);
            const sv = norm(sounds && sounds.selectedOptions && sounds.selectedOptions[0] && sounds.selectedOptions[0].text);
            const cv = norm(cats && cats.selectedOptions && cats.selectedOptions[0] && cats.selectedOptions[0].text);

            const allSounds = (sv === '' || sv === 'todos os sons' || sv === 'todos' || sv === 'all');
            const allCats   = (cv === '' || cv === 'todas categorias' || cv === 'todas' || cv === 'all');

            cards.forEach(el => {
                const hay = norm(el.textContent);
                const ds  = norm(el.dataset.sounds || '');
                const dc  = norm(el.dataset.cats || '');
                
                const okQ = !qv || hay.includes(qv);
                const okS = allSounds || ds.includes(sv);
                const okC = allCats || dc.includes(cv);
                
                el.style.display = (okQ && okS && okC) ? '' : 'none';
            });
        }

        q && q.addEventListener('input', apply);
        sounds && sounds.addEventListener('change', apply);
        cats && cats.addEventListener('change', apply);

        const toggle = document.getElementById('apollo-toggle');
        toggle && toggle.addEventListener('click', () => {
            grid.dataset.layout = (grid.dataset.layout === 'grid') ? 'list' : 'grid';
        });

        apply();
    }

    /**
     * Expose Leaflet helper in unique namespace
     */
    window.Apollo = window.Apollo || {};
    window.Apollo.initMap = function(id, lat, lng, zoom) {
        lat = parseFloat(lat);
        lng = parseFloat(lng);
        
        if (!window.L || isNaN(lat) || isNaN(lng)) return;
        
        const m = L.map(id).setView([lat, lng], zoom || 15);
        
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '© OpenStreetMap contributors'
        }).addTo(m);
        
        L.marker([lat, lng]).addTo(m);
        
        return m;
    };

    // Start on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initEventGrid, { once: true });
    } else {
        initEventGrid();
    }

})(window, document);

