/**
 * Cena::Rio Calendar JavaScript
 *
 * Manages calendar display, event fetching from WordPress REST API,
 * and map integration with Leaflet.
 *
 * FLOW:
 * - Events start as "expected" (orange) - internal industry planning
 * - When industry confirms -> "confirmed" (green) -> goes to MOD queue
 * - When MOD approves -> "published" (blue) -> public calendar
 *
 * @package Apollo_Core
 * @since 3.1.0
 */

(function() {
  'use strict';

  /* ======= Config from PHP ======= */
  const { restUrl, nonce, canModerate, currentUser } = window.apolloCenaRio || {};

  /* ======= Data ======= */
  let events = {};
  let allEvents = [];

  /* ======= Calendar state ======= */
  const weekDays = ["Dom","Seg","Ter","Qua","Qui","Sex","Sáb"];
  const months = ["jan","fev","mar","abr","mai","jun","jul","ago","set","out","nov","dez"];
  const today = new Date();
  let viewYear = today.getFullYear();
  let viewMonth = today.getMonth();
  let selectedDate = null;

  /* ======= DOM refs ======= */
  const gridEl = document.getElementById('calendar-grid');
  const monthLabelEl = document.getElementById('month-label');
  const selectedDayEl = document.getElementById('selected-day');
  const eventsGridEl = document.getElementById('events-grid');
  const prevMonthBtn = document.getElementById('prev-month');
  const nextMonthBtn = document.getElementById('next-month');
  const btnAdd = document.getElementById('btn-add-event');
  const footerMapEl = document.getElementById('footer-map');

  /* ======= Map setup ======= */
  let map, markersLayer;

  function initMap(){
    try {
      map = L.map('footer-map', { zoomControl: true }).setView([-22.9068, -43.1729], 12);
      map.createPane('tilesPane'); map.getPane('tilesPane').style.zIndex = 200;
      map.createPane('eventsPane'); map.getPane('eventsPane').style.zIndex = 600;
      // STRICT MODE: Use central tileset provider
      if (window.ApolloMapTileset) {
        window.ApolloMapTileset.apply(map, { pane: 'tilesPane' });
        window.ApolloMapTileset.ensureAttribution(map);
      } else {
        console.warn('[Apollo] ApolloMapTileset not loaded, using fallback');
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { pane: 'tilesPane', attribution: '&copy; OSM' }).addTo(map);
      }
      markersLayer = L.layerGroup([], { pane: 'eventsPane' }).addTo(map);
    } catch(e){
      console.warn('Leaflet init failed', e);
      if (footerMapEl) footerMapEl.style.display = 'none';
    }
  }

  function updateMapMarkers(){
    if(!markersLayer) return;
    markersLayer.clearLayers();

    const toShow = [];
    if (selectedDate) {
      toShow.push(...(events[selectedDate] || []));
    } else {
      Object.keys(events).forEach(k => toShow.push(...(events[k] || [])));
    }

    const coords = [];
    toShow.forEach(ev => {
      if(ev.lat && ev.lng){
        const color = ev.status === 'published' ? '#3b82f6' : ev.status === 'confirmed' ? '#10b981' : '#f97316';
        const circle = L.circleMarker([parseFloat(ev.lat), parseFloat(ev.lng)], {
          radius: ev.status === 'published' ? 8 : ev.status === 'confirmed' ? 7 : 5,
          color: color,
          fillColor: color,
          fillOpacity: 0.95,
          pane: 'eventsPane'
        });
        circle.bindPopup(`<strong>${escapeHtml(ev.title)}</strong><br>${escapeHtml(ev.venue || '')}<br><em>${getStatusLabel(ev.status)}</em>`);
        circle.addTo(markersLayer);
        coords.push([parseFloat(ev.lat), parseFloat(ev.lng)]);
      }
    });

    if(coords.length) {
      map.fitBounds(L.latLngBounds(coords).pad(0.5));
    }
  }

  function getStatusLabel(status) {
    const labels = {
      'expected': '🟠 Esperado',
      'confirmed': '🟢 Confirmado (aguardando MOD)',
      'published': '🔵 Publicado'
    };
    return labels[status] || status;
  }

  /* ======= Calendar rendering ======= */
  function pad(n){ return String(n).padStart(2,'0'); }
  function isoDate(y,m,d){ return `${y}-${pad(m)}-${pad(d)}`; }
  function daysInMonth(year, monthIndex){ return new Date(year, monthIndex + 1, 0).getDate(); }
  function firstWeekdayOfMonth(year, monthIndex){ return new Date(year, monthIndex, 1).getDay(); }

  function formatSelectedLabel(iso){
    if(!iso) return 'Todos os eventos';
    const d = new Date(iso + 'T12:00:00');
    if(isNaN(d)) return 'Dia selecionado';
    return `${weekDays[d.getDay()]} · ${pad(d.getDate())} ${months[d.getMonth()]} ${d.getFullYear()}`;
  }

  function clearChildren(el){ while(el && el.firstChild) el.removeChild(el.firstChild); }

  function renderCalendar(){
    if (!gridEl) return;
    clearChildren(gridEl);

    const year = viewYear;
    const month = viewMonth;
    const totalDays = daysInMonth(year, month);
    const firstWeekday = firstWeekdayOfMonth(year, month);

    if (monthLabelEl) monthLabelEl.textContent = `${months[month].toUpperCase()} ${year}`;

    // Leading days
    const prevMonthIndex = month - 1 < 0 ? 11 : month - 1;
    const prevMonthYear = month - 1 < 0 ? year - 1 : year;
    const prevMonthDays = daysInMonth(prevMonthYear, prevMonthIndex);

    for(let i = firstWeekday - 1; i >= 0; i--){
      gridEl.appendChild(createDayButton({day: prevMonthDays - i, disabled: true}));
    }

    // Current month
    for(let d = 1; d <= totalDays; d++){
      const iso = isoDate(year, month + 1, d);
      const dayEvents = events[iso] || [];
      const hasExpected = dayEvents.some(e => e.status === 'expected');
      const hasConfirmed = dayEvents.some(e => e.status === 'confirmed' || e.status === 'published');

      gridEl.appendChild(createDayButton({
        day: d,
        iso,
        hasExpected,
        hasConfirmed,
        selected: iso === selectedDate
      }));
    }

    // Trailing days
    const totalCells = firstWeekday + totalDays;
    const trailing = (7 - (totalCells % 7)) % 7;
    for(let t = 1; t <= trailing; t++){
      gridEl.appendChild(createDayButton({day: t, disabled: true}));
    }
  }

  function createDayButton({day, iso, disabled=false, hasExpected=false, hasConfirmed=false, selected=false}){
    const btn = document.createElement('button');
    btn.className = 'day-btn';
    btn.type = 'button';
    btn.textContent = day;

    if(disabled){
      btn.classList.add('disabled');
      btn.disabled = true;
    } else {
      btn.dataset.date = iso;
      if(selected) btn.classList.add('selected');

      if(hasExpected || hasConfirmed) {
        const dot = document.createElement('span');
        dot.className = 'day-dot ' + (hasConfirmed ? 'confirmed' : 'expected');
        btn.appendChild(dot);
      }

      btn.addEventListener('click', onDayClick);
    }

    return btn;
  }

  function onDayClick(e){
    const iso = e.currentTarget.dataset.date;
    if(!iso) return;

    selectedDate = selectedDate === iso ? null : iso;

    document.querySelectorAll('.day-btn').forEach(b => b.classList.remove('selected'));
    if(selectedDate) {
      const sel = document.querySelector(`[data-date="${selectedDate}"]`);
      if(sel) sel.classList.add('selected');
    }

    renderEventGrid();
    updateMapMarkers();
    if (selectedDayEl) selectedDayEl.textContent = formatSelectedLabel(selectedDate);
  }

  /* ======= Events list rendering ======= */
  function renderEventGrid(){
    if (!eventsGridEl) return;
    clearChildren(eventsGridEl);
    if (selectedDayEl) selectedDayEl.textContent = formatSelectedLabel(selectedDate);

    // Collect and sort events
    const items = [];
    Object.keys(events).forEach(dateKey => {
      (events[dateKey] || []).forEach(ev => items.push({...ev, dateKey}));
    });

    items.sort((a,b) => {
      const order = { published: 0, confirmed: 1, expected: 2 };
      if(order[a.status] !== order[b.status]) return order[a.status] - order[b.status];
      return a.dateKey < b.dateKey ? -1 : 1;
    });

    const toRender = selectedDate ? items.filter(i => i.dateKey === selectedDate) : items;

    if(toRender.length === 0){
      eventsGridEl.innerHTML = `
        <div class="event-card" style="justify-content:center;flex-direction:column;align-items:center;gap:8px;text-align:center;color:#94a3b8">
          <i class="ri-calendar-line" style="font-size:24px"></i>
          <span>Nenhum evento para exibir</span>
        </div>`;
      return;
    }

    toRender.forEach(ev => {
      const card = document.createElement('article');
      const borderColor = ev.status === 'published' ? '#3b82f6' : ev.status === 'confirmed' ? '#10b981' : '#f97316';
      card.className = 'event-card';
      card.style.borderLeft = `4px solid ${borderColor}`;
      card.dataset.id = ev.id;

      const statusBadge = getStatusBadge(ev.status);
      const canConfirm = ev.status === 'expected' && (ev.author_id === currentUser?.id || canModerate);
      const canUnconfirm = ev.status === 'confirmed' && !ev.is_public && (ev.author_id === currentUser?.id || canModerate);

      card.innerHTML = `
        <div class="event-info">
          <div class="event-title">${escapeHtml(ev.title)}</div>
          <div class="event-meta">
            <span style="font-weight:600;color:${borderColor}">${ev.dateKey.slice(5)}</span>
            · ${ev.time || 'Horário não definido'}
            · ${ev.venue || 'Local não definido'}
          </div>
          ${ev.awaiting_mod ? '<div style="font-size:12px;color:#6b7280;margin-top:4px">⏳ Aguardando aprovação MOD</div>' : ''}
        </div>
        <div class="event-controls">
          ${statusBadge}
          <div class="event-actions">
            ${canConfirm ? `<button class="btn small" onclick="confirmEvent(${ev.id})" style="background:#10b981;border-color:#10b981"><i class="ri-check-line"></i> Confirmar</button>` : ''}
            ${canUnconfirm ? `<button class="btn small ghost" onclick="unconfirmEvent(${ev.id})"><i class="ri-arrow-go-back-line"></i></button>` : ''}
            ${(canModerate || ev.author_id === currentUser?.id) ? `<button class="btn small ghost" onclick="openEventModal(${ev.id})"><i class="ri-edit-line"></i></button>` : ''}
          </div>
        </div>
      `;

      card.addEventListener('click', (e) => {
        if(e.target.tagName === 'BUTTON' || e.target.closest('button')) return;
        if(ev.lat && ev.lng && map) map.setView([parseFloat(ev.lat), parseFloat(ev.lng)], 15, { animate: true });
      });

      eventsGridEl.appendChild(card);
    });
  }

  function getStatusBadge(status) {
    const badges = {
      'expected': '<span style="display:inline-flex;align-items:center;gap:4px;padding:4px 10px;border-radius:6px;background:rgba(249,115,22,0.1);color:#f97316;font-size:12px;font-weight:700"><i class="ri-radar-fill"></i> Esperado</span>',
      'confirmed': '<span style="display:inline-flex;align-items:center;gap:4px;padding:4px 10px;border-radius:6px;background:rgba(16,185,129,0.1);color:#10b981;font-size:12px;font-weight:700"><i class="ri-check-double-line"></i> Confirmado</span>',
      'published': '<span style="display:inline-flex;align-items:center;gap:4px;padding:4px 10px;border-radius:6px;background:rgba(59,130,246,0.1);color:#3b82f6;font-size:12px;font-weight:700"><i class="ri-global-line"></i> Público</span>'
    };
    return badges[status] || '';
  }

  /* ======= Fetch events from API ======= */
  async function fetchEventsFromAPI() {
    try {
      const response = await fetch(`${restUrl}cena-rio/events`, {
        headers: { 'X-WP-Nonce': nonce }
      });

      if (!response.ok) {
        console.warn('Failed to fetch events');
        return;
      }

      const data = await response.json();
      allEvents = data.events || [];

      // Group by date
      events = {};
      allEvents.forEach(event => {
        const date = event.start_date || event.dateKey;
        if (!date) return;
        if (!events[date]) events[date] = [];
        events[date].push({
          id: event.id,
          title: event.title,
          venue: event.venue || '',
          time: event.start_time || '',
          status: event.status, // expected | confirmed | published
          lat: event.lat,
          lng: event.lng,
          author_id: event.author_id,
          dateKey: date,
          is_public: event.is_public,
          awaiting_mod: event.awaiting_mod
        });
      });

      renderCalendar();
      renderEventGrid();
      updateMapMarkers();
    } catch (error) {
      console.error('Error fetching events:', error);
    }
  }

  /* ======= Confirm event (industry internal) ======= */
  window.confirmEvent = async function(eventId) {
    if (!confirm('Confirmar este evento?\n\nIsso enviará o evento para aprovação do MOD antes de ir para o calendário público.')) {
      return;
    }

    try {
      const response = await fetch(`${restUrl}cena-rio/confirm/${eventId}`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-WP-Nonce': nonce
        }
      });

      const result = await response.json();

      if (result.success) {
        alert('✅ ' + result.message);
        fetchEventsFromAPI();
      } else {
        alert('❌ Erro: ' + (result.message || 'Falha ao confirmar'));
      }
    } catch (error) {
      console.error('Error confirming event:', error);
      alert('Erro ao confirmar evento');
    }
  };

  /* ======= Unconfirm event ======= */
  window.unconfirmEvent = async function(eventId) {
    if (!confirm('Reverter para "Esperado"?\n\nIsso removerá o evento da fila de aprovação MOD.')) {
      return;
    }

    try {
      const response = await fetch(`${restUrl}cena-rio/unconfirm/${eventId}`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-WP-Nonce': nonce
        }
      });

      const result = await response.json();

      if (result.success) {
        alert('✅ ' + result.message);
        fetchEventsFromAPI();
      } else {
        alert('❌ Erro: ' + (result.message || 'Falha ao reverter'));
      }
    } catch (error) {
      console.error('Error unconfirming event:', error);
      alert('Erro ao reverter evento');
    }
  };

  /* ======= Modal create/edit ======= */
  window.openEventModal = function(eventId) {
    const modalRoot = document.getElementById('modal-root');
    if (!modalRoot) return;

    modalRoot.style.display = 'flex';
    modalRoot.innerHTML = '';

    const backdrop = document.createElement('div');
    backdrop.style.cssText = 'position:fixed;inset:0;background:rgba(2,6,23,0.45);display:flex;align-items:center;justify-content:center;z-index:9999';
    backdrop.addEventListener('click', (e) => { if(e.target === backdrop) closeModal(); });

    const modal = document.createElement('div');
    modal.style.cssText = 'width:min(500px,95%);background:#fff;border-radius:12px;padding:16px;box-shadow:0 12px 40px rgba(2,6,23,0.2)';

    const isEdit = !!eventId;
    modal.innerHTML = `
      <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px">
        <div style="font-weight:800">${isEdit ? 'Editar Evento' : 'Novo Evento'}</div>
        <button onclick="closeModal()" class="btn ghost small"><i class="ri-close-line"></i></button>
      </div>
      <form id="event-form" style="display:flex;flex-direction:column;gap:12px">
        <div>
          <label class="text-xs font-bold text-slate-500 uppercase">Nome *</label>
          <input id="ev-title" class="w-full border rounded px-3 py-2 mt-1" required />
        </div>
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="text-xs font-bold text-slate-500 uppercase">Data *</label>
            <input id="ev-date" type="date" class="w-full border rounded px-3 py-2 mt-1" required />
          </div>
          <div>
            <label class="text-xs font-bold text-slate-500 uppercase">Horário</label>
            <input id="ev-time" class="w-full border rounded px-3 py-2 mt-1" />
          </div>
        </div>
        <div>
          <label class="text-xs font-bold text-slate-500 uppercase">Local</label>
          <input id="ev-venue" class="w-full border rounded px-3 py-2 mt-1" />
        </div>
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="text-xs font-bold text-slate-500 uppercase">Lat</label>
            <input id="ev-lat" class="w-full border rounded px-3 py-2 mt-1" />
          </div>
          <div>
            <label class="text-xs font-bold text-slate-500 uppercase">Lng</label>
            <input id="ev-lng" class="w-full border rounded px-3 py-2 mt-1" />
          </div>
        </div>
        <div style="background:#fffbeb;border:1px solid #fcd34d;border-radius:8px;padding:12px;font-size:13px">
          <strong>📋 Fluxo de aprovação:</strong><br>
          <span style="color:#92400e">1. Evento criado como "Esperado"<br>
          2. Você confirma → vai para MOD<br>
          3. MOD aprova → calendário público</span>
        </div>
        <div style="display:flex;gap:8px;justify-content:flex-end;margin-top:8px">
          <button type="button" onclick="saveEvent(${eventId || 'null'})" class="btn">
            <i class="ri-save-line"></i> Salvar
          </button>
        </div>
      </form>
    `;

    backdrop.appendChild(modal);
    modalRoot.appendChild(backdrop);

    // Fill form if editing
    if(isEdit){
      let found = null;
      Object.keys(events).forEach(k => {
        (events[k] || []).forEach(ev => { if(ev.id === eventId) found = {...ev, dateKey: k}; });
      });
      if(found){
        document.getElementById('ev-date').value = found.dateKey;
        document.getElementById('ev-title').value = found.title;
        document.getElementById('ev-venue').value = found.venue;
        document.getElementById('ev-time').value = found.time;
        document.getElementById('ev-lat').value = found.lat || '';
        document.getElementById('ev-lng').value = found.lng || '';
      }
    } else {
      document.getElementById('ev-date').value = selectedDate || new Date().toISOString().slice(0,10);
    }
  };

  window.closeModal = function() {
    const modalRoot = document.getElementById('modal-root');
    if (modalRoot) {
      modalRoot.style.display = 'none';
      modalRoot.innerHTML = '';
    }
  };

  window.saveEvent = async function(eventId) {
    const title = document.getElementById('ev-title').value.trim();
    const date = document.getElementById('ev-date').value;
    const venue = document.getElementById('ev-venue').value.trim();
    const time = document.getElementById('ev-time').value.trim();
    const lat = parseFloat(document.getElementById('ev-lat').value) || null;
    const lng = parseFloat(document.getElementById('ev-lng').value) || null;

    if(!date || !title) {
      alert('Preencha data e nome');
      return;
    }

    try {
      const response = await fetch(`${restUrl}cena-rio/submit`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-WP-Nonce': nonce
        },
        body: JSON.stringify({
          event_title: title,
          event_description: '',
          event_start_date: date,
          event_start_time: time,
          event_venue: venue,
          event_lat: lat,
          event_lng: lng
        })
      });

      const result = await response.json();

      if (result.success) {
        alert('✅ Evento criado como "Esperado"!\n\nConfirme quando a informação estiver correta para enviar ao MOD.');
        closeModal();
        fetchEventsFromAPI();
      } else {
        alert('❌ Erro: ' + (result.message || 'Desconhecido'));
      }
    } catch (error) {
      console.error('Error saving event:', error);
      alert('Erro ao salvar evento');
    }
  };

  window.openQuickAdd = function() {
    openEventModal(null);
  };

  /* ======= Utilities ======= */
  function escapeHtml(str){
    if(!str) return '';
    return String(str).replace(/[&<>"']/g, s => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[s]));
  }

  /* ======= Init ======= */
  if (prevMonthBtn) {
    prevMonthBtn.addEventListener('click', () => {
      viewMonth--;
      if(viewMonth < 0){ viewMonth = 11; viewYear--; }
      renderCalendar();
    });
  }

  if (nextMonthBtn) {
    nextMonthBtn.addEventListener('click', () => {
      viewMonth++;
      if(viewMonth > 11){ viewMonth = 0; viewYear++; }
      renderCalendar();
    });
  }

  if (btnAdd) {
    btnAdd.addEventListener('click', () => openEventModal(null));
  }

  // Initialize
  renderCalendar();
  initMap();
  fetchEventsFromAPI();

  // Expose API
  window._CENARIO = { events, renderCalendar, renderEventGrid, updateMapMarkers, fetchEvents: fetchEventsFromAPI };
})();
