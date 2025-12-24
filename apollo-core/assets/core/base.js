// ============================================================================
// APOLLO BASE.JS - Consolidated Interactive Behaviors
// ============================================================================
// Version: 5.2.0
// Updated: 2025-12-01
// CDN: https://assets.apollo.rio.br/base.js
//
// FEATURES:
// - Dark mode toggle with localStorage persistence
// - User menu dropdown functionality
// - Live clock display
// - Global event filtering and search
// - Calendar navigation and date picker
// - Responsive navigation
// - Accessibility enhancements
// ============================================================================

(function() {
    'use strict';

    // --- Dark Mode Toggle ---
    function initDarkMode() {
        const darkModeToggle = document.getElementById('darkModeToggle');
        const body = document.body;

        if (!darkModeToggle) return;

        // Check for saved preference
        if (localStorage.getItem('theme') === 'dark') {
            body.classList.add('dark-mode');
        }

        darkModeToggle.addEventListener('click', () => {
            body.classList.toggle('dark-mode');
            // Save preference
            if (body.classList.contains('dark-mode')) {
                localStorage.setItem('theme', 'dark');
            } else {
                localStorage.setItem('theme', 'light');
            }
        });
    }

    // --- User Menu Dropdown ---
    function initUserMenu() {
        const userMenuTrigger = document.getElementById('userMenuTrigger');
        if (!userMenuTrigger) return;

        const userMenu = userMenuTrigger.parentElement;

        userMenuTrigger.addEventListener('click', (e) => {
            e.stopPropagation();
            userMenu.classList.toggle('open');
        });

        // Close on click outside
        document.addEventListener('click', (e) => {
            if (!userMenu.contains(e.target)) {
                userMenu.classList.remove('open');
            }
        });
    }

    // --- Header Clock ---
    function initClock() {
        const agoraH = document.getElementById('agoraH');
        if (!agoraH) return;

        function updateClock() {
            const now = new Date();
            const hours = String(now.getHours()).padStart(2, '0');
            const minutes = String(now.getMinutes()).padStart(2, '0');
            agoraH.textContent = `${hours}:${minutes}`;
        }

        updateClock();
        setInterval(updateClock, 10000); // Update every 10 seconds
    }

    // --- Event Filtering Logic (for events listing pages) ---
    function initEventFiltering() {
        // State variables
        let activeCategory = 'all';
        let searchQuery = '';
        let displayDate = new Date();

        // Selectors
        const categoryButtons = document.querySelectorAll('.event-category');
        const searchInput = document.getElementById('eventSearchInput');
        const searchForm = document.getElementById('eventSearchForm');
        const allEvents = document.querySelectorAll('.event_listing');
        const dateDisplay = document.getElementById('dateDisplay');
        const datePrev = document.getElementById('datePrev');
        const dateNext = document.getElementById('dateNext');

        if (!categoryButtons.length && !searchInput && !allEvents.length) return;

        // Month maps
        const monthShortNames = ['jan', 'fev', 'mar', 'abr', 'mai', 'jun', 'jul', 'ago', 'set', 'out', 'nov', 'dez'];
        const monthMap = {
            'jan': 0, 'fev': 1, 'mar': 2, 'abr': 3, 'mai': 4, 'jun': 5,
            'jul': 6, 'ago': 7, 'set': 8, 'out': 9, 'nov': 10, 'dez': 11
        };

        // Main filter function
        function filterEvents() {
            const displayMonth = displayDate.getMonth();
            const displayYear = displayDate.getFullYear();

            allEvents.forEach(event => {
                const category = event.dataset.category;
                const monthStr = event.dataset.monthStr;
                const eventMonth = monthMap[monthStr];

                const showByDate = eventMonth === displayMonth;
                const showByCategory = activeCategory === 'all' || category === activeCategory;

                const textContent = event.textContent.toLowerCase();
                const showBySearch = searchQuery === '' || textContent.includes(searchQuery);

                if (showByDate && showByCategory && showBySearch) {
                    event.classList.remove('hidden');
                    event.style.display = 'block';
                } else {
                    event.classList.add('hidden');
                    event.style.display = 'none';
                }
            });
        }

        // Datepicker logic
        function updateDatepicker() {
            const monthName = monthShortNames[displayDate.getMonth()];
            const year = displayDate.getFullYear();
            if (dateDisplay) dateDisplay.textContent = `${monthName} ${year}`;
            filterEvents();
        }

        if (datePrev) {
            datePrev.addEventListener('click', () => {
                displayDate.setMonth(displayDate.getMonth() - 1);
                updateDatepicker();
            });
        }

        if (dateNext) {
            dateNext.addEventListener('click', () => {
                displayDate.setMonth(displayDate.getMonth() + 1);
                updateDatepicker();
            });
        }

        // Category filter logic
        categoryButtons.forEach(button => {
            button.addEventListener('click', () => {
                categoryButtons.forEach(btn => btn.classList.remove('active'));
                button.classList.add('active');

                activeCategory = button.dataset.slug;
                filterEvents();
            });
        });

        // Search filter logic
        if (searchForm) {
            searchForm.addEventListener('submit', (e) => e.preventDefault());
        }

        if (searchInput) {
            searchInput.addEventListener('input', () => {
                searchQuery = searchInput.value.toLowerCase();
                filterEvents();
            });
        }

        // Initial load
        updateDatepicker();
    }

    // --- Calendar Navigation (for calendar pages) ---
    function initCalendar() {
        const calendarGrid = document.getElementById('calendar-grid');
        const monthLabel = document.getElementById('month-label');
        const selectedDay = document.getElementById('selected-day');
        const eventsGrid = document.getElementById('events-grid');

        if (!calendarGrid || !monthLabel) return;

        let currentDate = new Date();
        let selectedDate = null;

        const monthNames = ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho',
                           'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];
        const dayNames = ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'];

        function renderCalendar() {
            const year = currentDate.getFullYear();
            const month = currentDate.getMonth();

            monthLabel.textContent = `${monthNames[month]} ${year}`;

            // Clear previous calendar
            calendarGrid.innerHTML = '';

            // Get first day of month and last day
            const firstDay = new Date(year, month, 1);
            const lastDay = new Date(year, month + 1, 0);
            const startDate = new Date(firstDay);
            startDate.setDate(startDate.getDate() - firstDay.getDay());

            // Render 6 weeks
            for (let i = 0; i < 42; i++) {
                const dayElement = document.createElement('button');
                const dayDate = new Date(startDate);
                dayDate.setDate(startDate.getDate() + i);

                dayElement.className = 'day-btn';
                dayElement.textContent = dayDate.getDate();

                if (dayDate.getMonth() !== month) {
                    dayElement.classList.add('disabled');
                }

                if (selectedDate && dayDate.toDateString() === selectedDate.toDateString()) {
                    dayElement.classList.add('selected');
                }

                dayElement.addEventListener('click', () => {
                    if (dayDate.getMonth() === month) {
                        selectedDate = dayDate;
                        document.querySelectorAll('.day-btn').forEach(btn => btn.classList.remove('selected'));
                        dayElement.classList.add('selected');

                        if (selectedDay) {
                            selectedDay.textContent = `Eventos de ${dayDate.getDate()} de ${monthNames[month]}`;
                        }

                        // Filter events by selected date
                        filterEventsByDate(dayDate);
                    }
                });

                calendarGrid.appendChild(dayElement);
            }
        }

        function filterEventsByDate(date) {
            if (!eventsGrid) return;

            // This would be implemented based on your event data structure
            // For now, just show all events
            const eventCards = eventsGrid.querySelectorAll('.event-card');
            eventCards.forEach(card => {
                card.style.display = 'flex';
            });
        }

        // Navigation buttons
        const prevMonth = document.getElementById('prev-month');
        const nextMonth = document.getElementById('next-month');

        if (prevMonth) {
            prevMonth.addEventListener('click', () => {
                currentDate.setMonth(currentDate.getMonth() - 1);
                renderCalendar();
            });
        }

        if (nextMonth) {
            nextMonth.addEventListener('click', () => {
                currentDate.setMonth(currentDate.getMonth() + 1);
                renderCalendar();
            });
        }

        renderCalendar();
    }

    // --- Responsive Navigation ---
    function initResponsiveNav() {
        // Handle mobile menu toggles if needed
        // This can be extended based on specific navigation requirements
    }

    // --- Accessibility Enhancements ---
    function initAccessibility() {
        // Add focus management and keyboard navigation
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                // Close any open menus
                document.querySelectorAll('.menu-h-lista.open').forEach(menu => {
                    menu.classList.remove('open');
                });
            }
        });
    }

    // --- Initialize all features ---
    function init() {
        initDarkMode();
        initUserMenu();
        initClock();
        initEventFiltering();
        initCalendar();
        initResponsiveNav();
        initAccessibility();
    }

    // Run initialization
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

})();

// ============================================================================
// LEGACY ASSET LOADER (for backward compatibility)
// ============================================================================
(function () { 'use strict'; var d = document, w = window, cdn = 'https://assets.apollo.rio.br/'; var loaded = new Set(), seen = new WeakSet(), frame = null, observerStarted = false; function ensureUni() { if (d.querySelector('link[data-apollo-uni]')) return; var l = d.createElement('link'); l.rel = 'stylesheet'; l.href = cdn + 'uni.css'; l.setAttribute('data-apollo-uni', ''); var h = d.head || d.getElementsByTagName('head')[0]; if (h) h.insertBefore(l, h.firstChild || null); } function getBase() { var s = d.currentScript; if (!s) { var a = d.getElementsByTagName('script'); s = a[a.length - 1] || null; } if (!s || !s.src) return cdn; var p = s.src.split('/'); p.pop(); return p.join('/') + '/'; } var base = getBase(); function url(name) { if (name === 'icon') return cdn + 'icon.js'; return base + name; } function loadOnce(u) { if (loaded.has(u)) return; loaded.add(u); var s = d.createElement('script'); s.src = u; s.defer = true; (d.head || d.getElementsByTagName('head')[0]).appendChild(s); } function needEvent() { if (loaded.has(url('event.js'))) return false; var b = d.body; return b && (b.classList.contains('ap-page-event') || b.classList.contains('ap-page-dj') || b.classList.contains('ap-page-local') || b.classList.contains('single-evento') || b.classList.contains('single-dj') || b.classList.contains('single-local') || d.querySelector('[data-apollo-event-card]')); } function needExplorer() { if (loaded.has(url('explorer.js'))) return false; return d.querySelector('.ap-explorer-feed,[data-apollo-role="explorer"]'); } function detect() { if (needEvent()) loadOnce(url('event.js')); if (needExplorer()) loadOnce(url('explorer.js')); } function onMutations(muts) { var changed = false; for (var i = 0; i < muts.length; i++) { var nodes = muts[i].addedNodes; for (var j = 0; j < nodes.length; j++) { var n = nodes[j]; if (n && n.nodeType === 1 && !seen.has(n)) { seen.add(n); changed = true; } } } if (!changed) return; if (frame) w.cancelAnimationFrame(frame); frame = w.requestAnimationFrame(detect); } function startObserver() { if (observerStarted || !d.body) return; observerStarted = true; var o = new MutationObserver(onMutations); o.observe(d.body, { childList: true, subtree: true }); } function init() { ensureUni(); loadOnce(url('icon')); loadOnce(url('dark-mode.js')); loadOnce(url('clock.js')); if (d.body) seen.add(d.body); detect(); startObserver(); } if (d.readyState === 'loading') { d.addEventListener('DOMContentLoaded', init, { once: true }); } else { init(); } })();






