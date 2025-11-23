/**
 * P0-10: CENA RIO Calendar JavaScript
 * 
 * Handles calendar rendering, event creation, and approval.
 * 
 * @package Apollo_Social
 * @version 2.0.0
 */

(function() {
    'use strict';

    const apolloCenaData = window.apolloCenaData || {};
    const restUrl = apolloCenaData.restUrl || '';
    const nonce = apolloCenaData.nonce || '';
    const currentMonth = apolloCenaData.currentMonth || new Date().toISOString().slice(0, 7);
    const events = apolloCenaData.events || {};
    const hasCenaRioRole = apolloCenaData.hasCenaRioRole || false;
    const isMod = apolloCenaData.isMod || false;

    let selectedDate = null;
    let currentMonthState = currentMonth;

    /**
     * P0-10: Initialize calendar
     */
    function initCalendar() {
        renderCalendar();
        bindEventHandlers();
    }

    /**
     * P0-10: Render calendar grid
     */
    function renderCalendar() {
        const grid = document.getElementById('calendar-grid');
        const monthLabel = document.getElementById('month-label');
        
        if (!grid) return;

        const [year, month] = currentMonthState.split('-');
        const firstDay = new Date(year, month - 1, 1);
        const lastDay = new Date(year, month, 0);
        const daysInMonth = lastDay.getDate();
        const startDay = firstDay.getDay();

        let html = '';

        // Empty cells for days before month starts
        for (let i = 0; i < startDay; i++) {
            html += '<div class="day-cell opacity-30"></div>';
        }

        // Days of the month
        for (let d = 1; d <= daysInMonth; d++) {
            const date = `${year}-${String(month).padStart(2, '0')}-${String(d).padStart(2, '0')}`;
            const dayEvents = events[date] || [];
            const hasEvents = dayEvents.length > 0;
            const hasConfirmed = dayEvents.some(e => e.status === 'publish' || e.ticket_confirmed);
            const isSelected = date === selectedDate;

            html += `
                <button 
                    class="day-cell ${isSelected ? 'selected' : ''} ${hasEvents ? 'has-events' : ''} ${hasConfirmed ? 'has-confirmed' : ''}"
                    data-date="${date}"
                    type="button"
                >
                    ${d}
                    ${hasEvents ? `<span class="event-dot"></span>` : ''}
                </button>
            `;
        }

        grid.innerHTML = html;
        
        if (monthLabel) {
            const monthNames = ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 
                               'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];
            monthLabel.textContent = `${monthNames[month - 1]} ${year}`;
        }

        // Bind click handlers
        grid.querySelectorAll('.day-cell').forEach(btn => {
            btn.addEventListener('click', function() {
                selectedDate = this.dataset.date;
                renderCalendar();
                renderEvents();
            });
        });
    }

    /**
     * P0-10: Render events for selected date
     */
    function renderEvents() {
        const container = document.getElementById('events-list');
        const label = document.getElementById('selected-day-label');
        
        if (!container || !label) return;

        if (!selectedDate) {
            label.textContent = 'Selecione uma data no calendário';
            container.innerHTML = '';
            return;
        }

        const dayEvents = events[selectedDate] || [];
        const dateObj = new Date(selectedDate + 'T12:00:00');
        const weekDays = ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'];
        const months = ['jan', 'fev', 'mar', 'abr', 'mai', 'jun', 'jul', 'ago', 'set', 'out', 'nov', 'dez'];
        
        label.textContent = `${weekDays[dateObj.getDay()]} · ${String(dateObj.getDate()).padStart(2, '0')} ${months[dateObj.getMonth()]} ${dateObj.getFullYear()}`;

        if (dayEvents.length === 0) {
            container.innerHTML = `
                <div class="text-center py-8 text-slate-400">
                    <i class="ri-calendar-line text-4xl mb-2"></i>
                    <p class="text-sm">Nenhum evento registrado para este dia.</p>
                </div>
            `;
            return;
        }

        let html = '';
        dayEvents.forEach(event => {
            const statusClass = event.status === 'publish' ? 'bg-emerald-100 text-emerald-900' : 'bg-amber-100 text-amber-900';
            const statusText = event.status === 'publish' ? 'Confirmado' : 'Previsto';
            
            html += `
                <div class="p-4 bg-white rounded-lg border border-slate-200 hover:shadow-md transition-all">
                    <div class="flex items-start justify-between mb-2">
                        <div class="flex-1">
                            <h4 class="font-semibold text-slate-900 mb-1">${escapeHtml(event.title)}</h4>
                            <p class="text-sm text-slate-600">${event.time || '20:00'}</p>
                            ${event.local ? `<p class="text-xs text-slate-500 mt-1"><i class="ri-map-pin-line"></i> ${escapeHtml(event.local)}</p>` : ''}
                        </div>
                        <span class="px-2 py-1 text-xs font-medium rounded-full ${statusClass}">
                            ${statusText}
                        </span>
                    </div>
                    ${event.ticket_url ? `
                        <a href="${escapeHtml(event.ticket_url)}" target="_blank" 
                           class="inline-flex items-center gap-1 text-xs text-orange-600 hover:text-orange-700 mt-2">
                            <i class="ri-ticket-line"></i> Ver ingressos
                        </a>
                    ` : ''}
                    ${event.permalink ? `
                        <a href="${escapeHtml(event.permalink)}" 
                           class="inline-flex items-center gap-1 text-xs text-slate-600 hover:text-slate-900 mt-2 ml-3">
                            <i class="ri-external-link-line"></i> Ver evento
                        </a>
                    ` : ''}
                </div>
            `;
        });

        container.innerHTML = html;
    }

    /**
     * P0-10: Bind event handlers
     */
    function bindEventHandlers() {
        // Month navigation
        document.getElementById('prev-month')?.addEventListener('click', function() {
            const [year, month] = currentMonthState.split('-');
            const prevMonth = new Date(year, month - 2, 1);
            currentMonthState = prevMonth.toISOString().slice(0, 7);
            loadMonthEvents();
        });

        document.getElementById('next-month')?.addEventListener('click', function() {
            const [year, month] = currentMonthState.split('-');
            const nextMonth = new Date(year, month, 1);
            currentMonthState = nextMonth.toISOString().slice(0, 7);
            loadMonthEvents();
        });

        // Add event form (cena-rio role only)
        if (hasCenaRioRole) {
            const form = document.getElementById('cena-add-event-form');
            if (form) {
                form.addEventListener('submit', function(e) {
                    e.preventDefault();
                    createEvent();
                });
            }
        }

        // Approve event buttons (MOD only)
        if (isMod) {
            document.querySelectorAll('.approve-event-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const eventId = this.dataset.eventId;
                    approveEvent(eventId);
                });
            });
        }
    }

    /**
     * P0-10: Load events for current month
     */
    function loadMonthEvents() {
        // Reload page with new month parameter
        const url = new URL(window.location);
        url.searchParams.set('month', currentMonthState);
        window.location.href = url.toString();
    }

    /**
     * P0-10: Create event via REST API
     */
    function createEvent() {
        const form = document.getElementById('cena-add-event-form');
        if (!form) return;

        const formData = new FormData(form);
        const data = {
            title: formData.get('title'),
            date: formData.get('date'),
            time: formData.get('time'),
            ticket_url: formData.get('ticket_url'),
            description: '',
        };

        const btn = form.querySelector('button[type="submit"]');
        btn.disabled = true;
        btn.textContent = 'Criando...';

        fetch(restUrl + '/cena-rio/event', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-WP-Nonce': nonce,
            },
            body: JSON.stringify(data),
        })
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                alert('Evento criado com sucesso! Será revisado antes da publicação.');
                form.reset();
                // Reload page to show new event
                window.location.reload();
            } else {
                alert('Erro ao criar evento: ' + (result.message || 'Erro desconhecido'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Erro ao criar evento. Tente novamente.');
        })
        .finally(() => {
            btn.disabled = false;
            btn.textContent = 'Adicionar Evento Previsto';
        });
    }

    /**
     * P0-10: Approve event via REST API
     */
    function approveEvent(eventId) {
        if (!confirm('Deseja aprovar e publicar este evento?')) {
            return;
        }

        fetch(restUrl + '/cena-rio/event/' + eventId + '/approve', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-WP-Nonce': nonce,
            },
        })
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                alert('Evento aprovado e publicado!');
                window.location.reload();
            } else {
                alert('Erro ao aprovar evento: ' + (result.message || 'Erro desconhecido'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Erro ao aprovar evento. Tente novamente.');
        });
    }

    /**
     * Escape HTML
     */
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Initialize on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initCalendar);
    } else {
        initCalendar();
    }
})();

