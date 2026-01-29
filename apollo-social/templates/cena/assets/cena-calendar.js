/**
 * APOLLO CENA CALENDAR - ULTRA MODERN JS
 *
 * Rio Culture Industry Calendar Logic
 * localStorage demo, REST API production
 *
 * @package Apollo_Social
 * @since 2.1.0
 */

(function() {
    'use strict';

    // Config from PHP
    const config = window.apolloCenaConfig || {
        restUrl: '/wp-json/apollo/v1/cena-events',
        restNonce: '',
        geocodeUrl: '/wp-json/apollo/v1/cena-geocode',
        today: new Date().toISOString().split('T')[0],
        currentYear: new Date().getFullYear(),
        currentMonth: new Date().getMonth() + 1,
        user: {
            id: 0,
            username: '',
            canEdit: false,
            canDelete: false
        }
    };

    // State
    let events = {};
    let viewYear = config.currentYear;
    let viewMonth = config.currentMonth - 1;
    let selectedDate = null;
    let activeFilter = 'all';
    let map = null;
    let markersLayer = null;

    const months = ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];
    const monthsShort = ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];

    // Utils
    const saveEvents = () => {}; // No-op: data saved via REST API
    const generateId = () => Date.now();

    // Check if user can edit/delete event (admin OR coauthor)
    const canEditEvent = (evt) => {
        if (!evt) return false;
        // Admin can edit all
        if (config.user.canEdit && config.user.canDelete) return true;
        // Coauthor can edit their own events
        return evt.author === config.user.username || evt.coauthor === config.user.username;
    };
    const escapeHtml = (str) => {
        if (!str) return '';
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    };

    const isToday = (dateStr) => {
        const today = new Date();
        const [y, m, d] = dateStr.split('-').map(Number);
        return today.getFullYear() === y && today.getMonth() + 1 === m && today.getDate() === d;
    };

    const formatDateShort = (dateStr) => {
        const [y, m, d] = dateStr.split('-');
        return { day: parseInt(d), month: monthsShort[parseInt(m) - 1] };
    };

    // Stats calculation
    function getStats() {
        let confirmado = 0, previsto = 0, adiado = 0;
        Object.values(events).forEach(dayEvents => {
            dayEvents.forEach(evt => {
                if (evt.status === 'confirmado') confirmado++;
                else if (evt.status === 'previsto') previsto++;
                else if (evt.status === 'adiado') adiado++;
            });
        });
        return { confirmado, previsto, adiado, total: confirmado + previsto + adiado };
    }

    function updateStats() {
        const stats = getStats();
        const confirmadoEl = document.querySelector('[data-stat="confirmado"]');
        const previstoEl = document.querySelector('[data-stat="previsto"]');
        const adiadoEl = document.querySelector('[data-stat="adiado"]');

        if (confirmadoEl) confirmadoEl.textContent = stats.confirmado;
        if (previstoEl) previstoEl.textContent = stats.previsto;
        if (adiadoEl) adiadoEl.textContent = stats.adiado;
    }

    // Calendar rendering
    function renderCalendar() {
        const grid = document.getElementById('calendar-days');
        const monthEl = document.getElementById('current-month');
        if (!grid || !monthEl) return;

        monthEl.textContent = `${months[viewMonth]} ${viewYear}`;
        grid.innerHTML = '';

        const firstDay = new Date(viewYear, viewMonth, 1).getDay();
        const daysInMonth = new Date(viewYear, viewMonth + 1, 0).getDate();

        // Empty cells for alignment
        for (let i = 0; i < firstDay; i++) {
            const empty = document.createElement('div');
            empty.className = 'cena-day empty';
            grid.appendChild(empty);
        }

        // Day cells
        for (let day = 1; day <= daysInMonth; day++) {
            const dateStr = `${viewYear}-${String(viewMonth + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
            const dayEvents = events[dateStr] || [];
            const div = document.createElement('div');

            let classes = 'cena-day';
            if (isToday(dateStr)) classes += ' today';
            if (dayEvents.length > 0) classes += ' has-events';
            if (selectedDate === dateStr) classes += ' selected';

            div.className = classes;
            div.textContent = day;
            div.onclick = () => selectDate(dateStr);
            grid.appendChild(div);
        }
    }

    function selectDate(dateStr) {
        selectedDate = selectedDate === dateStr ? null : dateStr;
        renderCalendar();
        renderEvents();
        renderUpcoming();
        updateMapMarkers();
    }

    // Upcoming events in sidebar
    function renderUpcoming() {
        const list = document.getElementById('upcoming-list');
        if (!list) return;

        // Get all events and sort by date
        let allEvents = [];
        Object.keys(events).forEach(dateKey => {
            events[dateKey].forEach(evt => {
                allEvents.push({ ...evt, dateKey });
            });
        });

        // Sort by date
        allEvents.sort((a, b) => a.dateKey.localeCompare(b.dateKey));

        // Take next 5
        const upcoming = allEvents.slice(0, 5);

        if (upcoming.length === 0) {
            list.innerHTML = '<div class="cena-empty-text">Nenhum evento</div>';
            return;
        }

        list.innerHTML = upcoming.map(evt => {
            const { day, month } = formatDateShort(evt.dateKey);
            return `
                <div class="cena-upcoming-item reveal-up" data-date="${evt.dateKey}">
                    <div class="cena-upcoming-date">
                        <div class="cena-upcoming-day">${day}</div>
                        <div class="cena-upcoming-month">${month}</div>
                    </div>
                    <div class="cena-upcoming-info">
                        <div class="cena-upcoming-title">${escapeHtml(evt.title)}</div>
                        <div class="cena-upcoming-meta">
                            <span class="cena-upcoming-status ${evt.status}"></span>
                            <span>${evt.time || '--:--'}</span>
                            <span>•</span>
                            <span>${escapeHtml(evt.location)}</span>
                        </div>
                    </div>
                </div>
            `;
        }).join('');

        // Click handlers
        list.querySelectorAll('.cena-upcoming-item').forEach(item => {
            item.onclick = () => selectDate(item.dataset.date);
        });
    }

    // Events grid
    function renderEvents() {
        const grid = document.getElementById('events-grid');
        const titleEl = document.getElementById('events-title');
        if (!grid) return;

        // Collect events
        let allEvents = [];
        Object.keys(events).forEach(dateKey => {
            if (!selectedDate || selectedDate === dateKey) {
                events[dateKey].forEach(evt => {
                    if (activeFilter === 'all' || evt.status === activeFilter) {
                        allEvents.push({ ...evt, dateKey });
                    }
                });
            }
        });

        // Update title
        if (titleEl) {
            const { day, month } = selectedDate ? formatDateShort(selectedDate) : { day: '', month: '' };
            titleEl.innerHTML = selectedDate
                ? `Eventos ${day} ${month} <span class="cena-events-count">(${allEvents.length})</span>`
                : `Todos os Eventos <span class="cena-events-count">(${allEvents.length})</span>`;
        }

        if (allEvents.length === 0) {
            grid.innerHTML = `
                <div class="cena-empty">
                    <i class="ri-calendar-line"></i>
                    <p class="cena-empty-text">Nenhum evento encontrado</p>
                </div>
            `;
            return;
        }

        grid.innerHTML = allEvents.map(evt => {
            const { day, month } = formatDateShort(evt.dateKey);
            const sounds = evt.tags && evt.tags.length > 0 ? evt.tags.join(', ') : '';
            return `
                <div class="cena-event-card reveal-up">
                    <div class="cena-event-header">
                        <h3 class="cena-event-title">${escapeHtml(evt.title)}</h3>
                        <span class="cena-event-badge ${evt.status}">${evt.status}</span>
                    </div>
                    <div class="cena-event-body">
                        <div class="cena-event-meta">
                            <div class="cena-event-row">
                                <i class="ri-calendar-line"></i>
                                <span>${day} ${month}</span>
                                <i class="ri-time-line" style="margin-left: 8px;"></i>
                                <span>${evt.time || '--:--'}</span>
                            </div>
                            <div class="cena-event-row">
                                <i class="ri-map-pin-line"></i>
                                <span>${escapeHtml(evt.location)}</span>
                                ${sounds ? `
                                <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="currentColor" style="flex-shrink: 0; margin-left: 8px;">
                                    <path d="M12 13.5351V3H20V5H14V17C14 19.2091 12.2091 21 10 21C7.79086 21 6 19.2091 6 17C6 14.7909 7.79086 13 10 13C10.7286 13 11.4117 13.1948 12 13.5351ZM10 19C11.1046 19 12 18.1046 12 17C12 15.8954 11.1046 15 10 15C8.89543 15 8 15.8954 8 17C8 18.1046 8.89543 19 10 19Z"></path>
                                </svg>
                                <span style="opacity: 0.8;">${escapeHtml(sounds)}</span>
                                ` : ''}
                            </div>
                        </div>
                    </div>
                    <div class="cena-event-footer">
                        <div class="cena-event-author">
                            <i class="ri-pencil-ruler-2-fill" style="transform:scale(1.3);"></i>
                            <span>@${escapeHtml(evt.author)}</span>
                            ${evt.coauthor ? `<span style="opacity: 0.5; margin: 0 3px;">+</span>@${escapeHtml(evt.coauthor)}` : ''}
                        </div>
                        ${canEditEvent(evt) ? `
                        <div class="cena-event-actions">
                            <button class="cena-action-btn" data-edit="${evt.id}" data-date="${evt.dateKey}" title="Editar">
                                <i class="ri-edit-line"></i>
                            </button>
                            <button class="cena-action-btn delete" data-delete="${evt.id}" data-date="${evt.dateKey}" title="Excluir">
                                <i class="ri-delete-bin-line"></i>
                            </button>
                        </div>
                        ` : ''}
                    </div>
                </div>
            `;
        }).join('');

        // Event handlers
        grid.querySelectorAll('[data-edit]').forEach(btn => {
            btn.onclick = () => openEditModal(btn.dataset.edit, btn.dataset.date);
        });
        grid.querySelectorAll('[data-delete]').forEach(btn => {
            btn.onclick = () => deleteEvent(btn.dataset.delete, btn.dataset.date);
        });
    }

    // Map
    function initMap() {
        const container = document.getElementById('event-map');
        if (!container || !window.L) return;

       map = L.map('event-map', {
    center: [-22.925269, -43.195315],
    zoom: 11.5,
    zoomControl: false,
    attributionControl: false
});

        L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
            maxZoom: 19
        }).addTo(map);

        markersLayer = L.layerGroup().addTo(map);
        updateMapMarkers();
    }

    function updateMapMarkers() {
        if (!map || !markersLayer) return;
        markersLayer.clearLayers();

        const statusColors = {
            confirmado: '#10b981',
            previsto: '#f97316',
            adiado: '#a855f7',
            cancelado: '#ef4444'
        };

        Object.keys(events).forEach(dateKey => {
            if (!selectedDate || selectedDate === dateKey) {
                events[dateKey].forEach(evt => {
                    if (evt.lat && evt.lng && (activeFilter === 'all' || evt.status === activeFilter)) {
                        const marker = L.circleMarker([evt.lat, evt.lng], {
                            radius: 8,
                            fillColor: statusColors[evt.status] || '#94a3b8',
                            color: '#ffffff',
                            weight: 2,
                            opacity: 1,
                            fillOpacity: 0.9
                        });

                        marker.bindPopup(`
                            <div class="cena-popup">
                                <div class="cena-popup-title">${escapeHtml(evt.title)}</div>
                                <div class="cena-popup-meta">
                                    <span>${evt.time || '--:--'}</span>
                                    <span>•</span>
                                    <span>${escapeHtml(evt.location)}</span>
                                </div>
                            </div>
                        `);

                        markersLayer.addLayer(marker);
                    }
                });
            }
        });
    }

    // Geocoding
    async function geocode(address) {
        try {
            const response = await fetch(`https://nominatim.openstreetmap.org/search?format=json&limit=1&q=${encodeURIComponent(address + ', Rio de Janeiro, Brazil')}`);
            const data = await response.json();
            if (data && data[0]) {
                return { lat: parseFloat(data[0].lat), lng: parseFloat(data[0].lon) };
            }
        } catch (e) {
            console.error('Geocoding failed:', e);
        }
        return null;
    }

    // Modal
    function openModal(eventData = null) {
        const overlay = document.getElementById('modal-overlay');
        const title = document.getElementById('modal-title');
        const form = document.getElementById('event-form');

        if (!overlay || !form) return;

        // Reset form
        form.reset();
        document.querySelectorAll('.cena-status-option').forEach(opt => opt.classList.remove('selected'));
        document.querySelector('.cena-status-option.previsto')?.classList.add('selected');
        document.getElementById('ev-status').value = 'previsto';
        document.getElementById('ev-id').value = '';
        document.getElementById('ev-lat').value = '';
        document.getElementById('ev-lng').value = '';

        if (eventData) {
            title.textContent = 'Editar Evento';
            document.getElementById('ev-id').value = eventData.id;
            document.getElementById('ev-title').value = eventData.title || '';
            document.getElementById('ev-date').value = eventData.dateKey || '';
            document.getElementById('ev-time').value = eventData.time || '';
            document.getElementById('ev-location').value = eventData.location || '';
            document.getElementById('ev-type').value = eventData.type || '';
            document.getElementById('ev-author').value = eventData.author || '';
            document.getElementById('ev-coauthor').value = eventData.coauthor || '';
            document.getElementById('ev-tags').value = (eventData.tags || []).join(', ');
            document.getElementById('ev-status').value = eventData.status || 'previsto';
            document.getElementById('ev-lat').value = eventData.lat || '';
            document.getElementById('ev-lng').value = eventData.lng || '';

            document.querySelectorAll('.cena-status-option').forEach(opt => {
                opt.classList.toggle('selected', opt.dataset.status === eventData.status);
            });
        } else {
            title.textContent = 'Novo Evento';
            if (selectedDate) {
                document.getElementById('ev-date').value = selectedDate;
            }
        }

        overlay.classList.add('active');
    }

    function closeModal() {
        document.getElementById('modal-overlay')?.classList.remove('active');
    }

    function openEditModal(id, dateKey) {
        const dayEvents = events[dateKey] || [];
        const evt = dayEvents.find(e => e.id == id);
        if (evt) {
            openModal({ ...evt, dateKey });
        }
    }

    // Load events from REST API
    async function loadEvents(year, month) {
        try {
            const response = await fetch(`${config.restUrl}?year=${year}&month=${month}`, {
                headers: {
                    'X-WP-Nonce': config.restNonce
                }
            });

            if (!response.ok) throw new Error('Failed to load events');

            const data = await response.json();
            events = data;
            renderAll();
        } catch (error) {
            console.error('Error loading events:', error);
            events = {};
            renderAll();
        }
    }

    async function handleFormSubmit(e) {
        e.preventDefault();

        const id = document.getElementById('ev-id').value;
        const dateKey = document.getElementById('ev-date').value;
        const title = document.getElementById('ev-title').value.trim();
        const time = document.getElementById('ev-time').value;
        const location = document.getElementById('ev-location').value.trim();
        const type = document.getElementById('ev-type').value.trim();
        const author = document.getElementById('ev-author').value.trim().replace('@', '') || config.user.username;
        const coauthor = document.getElementById('ev-coauthor').value.trim().replace('@', '');
        const tags = document.getElementById('ev-tags').value.split(',').map(t => t.trim()).filter(Boolean);
        const status = document.getElementById('ev-status').value;
        let lat = parseFloat(document.getElementById('ev-lat').value) || null;
        let lng = parseFloat(document.getElementById('ev-lng').value) || null;

        if (!title || !dateKey) {
            alert('Título e data são obrigatórios');
            return;
        }

        // Geocode if no coords
        if (!lat && location) {
            const coords = await geocode(location);
            if (coords) {
                lat = coords.lat;
                lng = coords.lng;
            }
        }

        const eventData = {
            title,
            date: dateKey,
            time,
            location,
            type,
            author,
            coauthor,
            tags,
            status,
            lat,
            lng
        };

        try {
            let response;
            if (id) {
                // Update existing event
                response = await fetch(`${config.restUrl}/${id}`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-WP-Nonce': config.restNonce
                    },
                    body: JSON.stringify(eventData)
                });
            } else {
                // Create new event
                response = await fetch(config.restUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-WP-Nonce': config.restNonce
                    },
                    body: JSON.stringify(eventData)
                });
            }

            if (!response.ok) throw new Error('Failed to save event');

            closeModal();
            await loadEvents(viewYear, viewMonth + 1);
        } catch (error) {
            console.error('Error saving event:', error);
            alert('Erro ao salvar evento. Tente novamente.');
        }
    }

    async function deleteEvent(id, dateKey) {
        if (!confirm('Excluir este evento?')) return;

        try {
            const response = await fetch(`${config.restUrl}/${id}`, {
                method: 'DELETE',
                headers: {
                    'X-WP-Nonce': config.restNonce
                }
            });

            if (!response.ok) throw new Error('Failed to delete event');

            await loadEvents(viewYear, viewMonth + 1);
        } catch (error) {
            console.error('Error deleting event:', error);
            alert('Erro ao excluir evento. Tente novamente.');
        }
    }

    // Filter handling
    function setupFilters() {
        document.querySelectorAll('.cena-filter-pill').forEach(pill => {
            pill.onclick = () => {
                activeFilter = pill.dataset.filter;
                document.querySelectorAll('.cena-filter-pill').forEach(p => p.classList.remove('active'));
                pill.classList.add('active');
                renderEvents();
                updateMapMarkers();
            };
        });
    }

    // Status selector
    function setupStatusSelector() {
        document.querySelectorAll('.cena-status-option').forEach(opt => {
            opt.onclick = () => {
                document.querySelectorAll('.cena-status-option').forEach(o => o.classList.remove('selected'));
                opt.classList.add('selected');
                document.getElementById('ev-status').value = opt.dataset.status;
            };
        });
    }

    // Render all
    function renderAll() {
        renderCalendar();
        renderUpcoming();
        renderEvents();
        updateMapMarkers();
        updateStats();
    }

    // Init
    function init() {
        // Month navigation
        document.getElementById('prev-month')?.addEventListener('click', async () => {
            viewMonth--;
            if (viewMonth < 0) { viewMonth = 11; viewYear--; }
            renderCalendar();
            await loadEvents(viewYear, viewMonth + 1);
        });

        document.getElementById('next-month')?.addEventListener('click', async () => {
            viewMonth++;
            if (viewMonth > 11) { viewMonth = 0; viewYear++; }
            renderCalendar();
            await loadEvents(viewYear, viewMonth + 1);
        });

        // Add event button (multiple instances)
        document.getElementById('btn-add-event')?.addEventListener('click', () => openModal());
        document.getElementById('btn-add-event-filter')?.addEventListener('click', () => openModal());

        // Modal close
        document.getElementById('modal-close')?.addEventListener('click', closeModal);
        document.getElementById('modal-overlay')?.addEventListener('click', (e) => {
            if (e.target.id === 'modal-overlay') closeModal();
        });

        // Form submit
        document.getElementById('event-form')?.addEventListener('submit', handleFormSubmit);

        // Setup filters and status selector
        setupFilters();
        setupStatusSelector();

        // Map zoom controls
        document.getElementById('map-zoom-in')?.addEventListener('click', () => map?.zoomIn());
        document.getElementById('map-zoom-out')?.addEventListener('click', () => map?.zoomOut());
        document.getElementById('map-reset')?.addEventListener('click', () => {
            map?.setView([-22.9068, -43.1729], 12);
        });

        // Initial render
        renderCalendar();
        initMap();

        // Load events for current month
        loadEvents(viewYear, viewMonth + 1);

        // Keyboard
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') closeModal();
        });
    }

    // Start
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
