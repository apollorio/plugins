/**
 * Apollo Events Date Picker
 * Handles month navigation for event filtering
 */
(function() {
    'use strict';

    // Portuguese month abbreviations
    const monthsPT = ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];
    
    // Current selected date
    let currentDate = new Date();
    let selectedMonth = currentDate.getMonth();
    let selectedYear = currentDate.getFullYear();

    function initDatePicker() {
        const datePrev = document.getElementById('datePrev');
        const dateNext = document.getElementById('dateNext');
        const dateDisplay = document.getElementById('dateDisplay');

        if (!datePrev || !dateNext || !dateDisplay) {
            return; // Elements not found
        }

        // Initialize with current month
        updateDisplay();

        // Previous month
        datePrev.addEventListener('click', function(e) {
            e.preventDefault();
            selectedMonth--;
            if (selectedMonth < 0) {
                selectedMonth = 11;
                selectedYear--;
            }
            updateDisplay();
            filterEventsByDate();
        });

        // Next month
        dateNext.addEventListener('click', function(e) {
            e.preventDefault();
            selectedMonth++;
            if (selectedMonth > 11) {
                selectedMonth = 0;
                selectedYear++;
            }
            updateDisplay();
            filterEventsByDate();
        });
    }

    function updateDisplay() {
        const dateDisplay = document.getElementById('dateDisplay');
        if (dateDisplay) {
            dateDisplay.textContent = monthsPT[selectedMonth];
            dateDisplay.setAttribute('data-month', selectedMonth + 1);
            dateDisplay.setAttribute('data-year', selectedYear);
        }
    }

    function filterEventsByDate() {
        const eventCards = document.querySelectorAll('.event_listing');
        const targetMonth = selectedMonth + 1; // 1-based
        const targetYear = selectedYear;

        let visibleCount = 0;

        eventCards.forEach(function(card) {
            const startDate = card.getAttribute('data-event-start-date');
            
            if (!startDate) {
                card.style.display = 'none';
                return;
            }

            // Parse date (expecting Y-m-d or Y-m-d H:i:s)
            const dateParts = startDate.split(/[\s-]/);
            const eventYear = parseInt(dateParts[0], 10);
            const eventMonth = parseInt(dateParts[1], 10);

            if (eventYear === targetYear && eventMonth === targetMonth) {
                card.style.display = '';
                visibleCount++;
            } else {
                card.style.display = 'none';
            }
        });

        // Show/hide "no events" message
        const noEventsMsg = document.querySelector('.no-events-found');
        if (noEventsMsg) {
            noEventsMsg.style.display = visibleCount === 0 ? 'block' : 'none';
        }

        // Update URL parameter without reload
        if (window.history && window.history.pushState) {
            const url = new URL(window.location);
            url.searchParams.set('month', selectedMonth + 1);
            url.searchParams.set('year', selectedYear);
            window.history.pushState({}, '', url);
        }
    }

    // Initialize on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initDatePicker);
    } else {
        initDatePicker();
    }

    // Check for month parameter in URL
    function checkURLParams() {
        const url = new URL(window.location);
        const monthParam = url.searchParams.get('month');
        const yearParam = url.searchParams.get('year');

        if (monthParam) {
            selectedMonth = parseInt(monthParam, 10) - 1;
            if (selectedMonth < 0) selectedMonth = 0;
            if (selectedMonth > 11) selectedMonth = 11;
        }

        if (yearParam) {
            selectedYear = parseInt(yearParam, 10);
        }

        updateDisplay();
        filterEventsByDate();
    }

    // Check URL params after init
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(checkURLParams, 100);
        });
    } else {
        setTimeout(checkURLParams, 100);
    }

})();

