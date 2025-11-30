<!doctype html>
<html lang="pt-BR" class="h-full">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover" />
  <title>Cena::rio · Calendário Avançado</title>

  <!-- Tailwind for quick styling -->
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://cdn.jsdelivr.net/npm/remixicon@4.3.0/fonts/remixicon.css" rel="stylesheet" />

  <!-- Leaflet -->
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

  <style>
    :root{
      --bg:#f8fafc;
      --muted:#64748b;
      --accent:#f97316;
      --accent-strong:#ea580c;
      --confirmed:#10b981;
      --published:#3b82f6; /* nova cor de “Público” */
      --card:#ffffff;
      --border:#e6eef6;
      --shadow: 0 8px 30px rgba(15,23,42,0.06);
    }
    html,body{height:100%;margin:0;background:var(--bg);font-family:Inter,system-ui,Arial;}

    /* Layout */
    .app { display:flex; min-height:100vh; gap:0; }
    aside.leftbar { width:14rem; background:#fff; border-right:1px solid #eef2f7; display:flex; flex-direction:column; z-index:40; }
    @media (max-width: 980px){ aside.leftbar{display:none} }

    main.content { flex:1; display:flex; flex-direction:column; min-height:100vh; }

    /* Top header */
    header.topbar {
      display:flex; align-items:center; justify-content:space-between; gap:12px;
      padding:12px 20px; background:rgba(255,255,255,0.9);
      border-bottom:1px solid #eef2f7; position:sticky; top:0; z-index:60;
      backdrop-filter: blur(6px);
    }

    /* Workspace GRID:
       Desktop: row 1 = calendário + mapa; row 2 = eventos
       Mobile: cal -> eventos -> mapa
    */
    .workspace {
      display:grid;
      grid-template-columns: minmax(0,320px) minmax(0,1fr);
      grid-template-rows: auto auto;
      grid-template-areas:
        "calendar map"
        "events   events";
      gap:20px;
      padding:20px;
      max-width:1400px;
      margin:0 auto;
      width:100%;
      box-sizing:border-box;
    }
    @media (max-width:1100px){
      .workspace {
        grid-template-columns: 1fr;
        grid-template-rows: auto auto auto;
        grid-template-areas:
          "calendar"
          "events"
          "map";
        padding-bottom: 0;
      }
    }

    /* Calendar card */
    .calendar-card {
      grid-area: calendar;
      background:var(--card);
      border:1px solid var(--border);
      border-radius:12px;
      padding:12px;
      box-shadow:var(--shadow);
    }

    .weekdays{
      display:grid;
      grid-template-columns:repeat(7,1fr);
      gap:6px;
      text-align:center;
      color:var(--muted);
      font-weight:700;
      font-size:12px;
      margin-top:8px;
    }
    .grid{
      display:grid;
      grid-template-columns:repeat(7,1fr);
      gap:6px;
      margin-top:8px;
    }
    .day-btn{
      height:38px;
      border-radius:10px;
      border:1px solid transparent;
      background:transparent;
      display:flex;
      align-items:center;
      justify-content:center;
      font-weight:400;
      font-size:12px;
      color:#0f172a;
      cursor:pointer;
      position:relative;
    }
    .day-btn.disabled{opacity:0.35;cursor:default}
    .day-btn.selected{
      background:#0f172a;
      color:#fff;
      box-shadow:0 8px 24px rgba(15,23,42,0.12);
    }

    /* Múltiplas bolinhas por dia (um dot por evento) */
    .day-dots{
      position:absolute;
      top:4px;
      left:50%;
      transform:translateX(-50%);
      display:flex;
      gap:3px;
    }
    .day-dot{
      width:7px;
      height:7px;
      border-radius:999px;
      opacity:0.95;
      background:var(--accent);
    }
    .day-dot.confirmed{ background:var(--confirmed); }
    .day-dot.published{ background:var(--published); }

    /* --- REFACTORED: Events List --- */
    .events-panel {
      grid-area: events;
      min-height:400px;
    }

    .events-grid {
      display: flex;
      flex-direction: column;
      gap: 12px;
      width: 100%;
    }

    .event-card {
      display: flex;
      flex-direction: row;
      align-items: center;
      justify-content: space-between;
      width: 100%;
      background: var(--card);
      border: 1px solid var(--border);
      border-radius: 12px;
      padding: 16px 20px;
      box-shadow: var(--shadow);
      transition: transform .12s ease, box-shadow .12s ease;
      gap: 16px;
    }

    @media (max-width: 640px) {
      .event-card {
        flex-direction: column;
        align-items: flex-start;
        gap: 12px;
      }
      .event-card .event-controls {
        width: 100%;
        justify-content: space-between;
        margin-top: 8px;
        padding-top: 8px;
        border-top: 1px solid #f1f5f9;
      }
    }

    .event-card.expected { border-left: 4px solid var(--accent); }
    .event-card.confirmed { border-left: 4px solid var(--confirmed); }

    .event-info { flex: 1; min-width: 0; }
    .event-controls { display: flex; align-items: center; gap: 16px; flex-shrink: 0; }

    .event-title { font-weight: 700; font-size: 16px; color: #0f172a; }
    .event-meta {
      font-size: 14px;
      color: #64748b;
      margin-top: 4px;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .event-actions { display: flex; gap: 8px; align-items: center; }

    /* MAP CARD (entra no grid, não mais footer fixo) */
    .map-card{
      grid-area: map;
      background:var(--card);
      border:1px solid var(--border);
      border-radius:12px;
      padding:0;
      box-shadow:var(--shadow);
      overflow:hidden;
      min-height:260px;
    }
    #footer-map{
      width:100%;
      height:100%;
      min-height:260px;
    }
    @media (max-width:640px){
      #footer-map{ min-height:220px; }
    }

    /* Bottom nav mobile only */
    .bottom-nav {
      position:fixed;
      left:0; right:0;
      bottom: env(safe-area-inset-bottom, 0);
      display:flex; justify-content:space-around; align-items:center;
      height:64px; padding:8px 12px;
      background: linear-gradient(180deg, rgba(255,255,255,0.96), rgba(255,255,255,0.98));
      backdrop-filter: blur(8px);
      z-index:1200;
      box-shadow: 0 -6px 20px rgba(0,0,0,0.06);
    }
    .bottom-nav .nav-btn {
      display:flex; flex-direction:column; align-items:center; justify-content:center;
      gap:2px; font-size:11px; color:#64748b; width:56px;
    }
    .bottom-nav .nav-btn.active { color:#0f172a; }
    @media (min-width: 980px){ .bottom-nav { display:none } }

    /* small helpers */
    .muted{color:var(--muted)}

    /* LEGENDA COM “BOLINHAS” (mesma lógica visual do calendário) */
    .legend{
      display:flex;
      gap:12px;
      align-items:center;
      color:#475569;
      font-size:13px;
      flex-wrap:wrap;
    }
    .legend-item{
      display:flex;
      align-items:center;
      gap:6px;
      white-space:nowrap;
    }
    .legend-dots{
      display:flex;
      gap:3px;
    }
    .legend-dot{
      width:7px;
      height:7px;
      border-radius:999px;
      background:var(--accent);
    }
    .legend-dot.confirmed{ background:var(--confirmed); }
    .legend-dot.published{ background:var(--published); }

    .btn {
      display:inline-flex;
      align-items:center;
      gap:8px;
      padding:8px 16px;
      border-radius:8px;
      border:1px solid transparent;
      background:#111827;
      color:#fff;
      font-weight:600;
      cursor:pointer;
      font-size: 13px;
      transition: opacity 0.2s;
    }
    .btn:hover { opacity: 0.9; }
    .btn.ghost { background:transparent; color:#475569; border-color:var(--border) }
    .btn.ghost:hover { background: #f1f5f9; color: #0f172a; }
    .btn.small { padding: 6px 12px; font-size: 12px; }

    :focus { outline: 3px solid rgba(99,102,241,0.12); outline-offset: 2px; }
  </style>
</head>
<body class="h-full">

  <div class="app">

    <!-- LEFT SIDEBAR -->
    <aside class="leftbar">
      <div class="h-16 flex items-center gap-3 px-6 border-b border-slate-100">
        <div class="h-8 w-8 rounded-full bg-slate-900 flex items-center justify-center text-white">
          <i class="ri-command-fill text-lg"></i>
        </div>
        <span class="font-bold text-slate-900 tracking-tight text-lg">Cena::Rio</span>
      </div>

      <nav class="flex-1 px-4 space-y-1 py-6 overflow-y-auto">
        <div class="px-2 mb-2 text-[9.5px] font-regular text-slate-400 uppercase tracking-wider">Menu</div>
        <a href="#" class="flex items-center gap-3 px-3 py-2.5 text-slate-600 rounded-lg hover:bg-slate-50 group">
          <i class="ri-calendar-line text-lg"></i><span class="font-medium text-sm">Agenda</span>
        </a>
        <a href="#" class="flex items-center gap-3 px-3 py-2.5 bg-slate-100 text-slate-900 rounded-lg font-semibold">
          <i class="ri-bar-chart-grouped-line text-lg"></i><span class="text-sm">Fornecedores</span>
        </a>
        <a href="#" class="flex items-center gap-3 px-3 py-2.5 text-slate-600 rounded-lg hover:bg-slate-50 group">
          <i class="ri-file-text-line text-lg"></i><span class="font-medium text-sm">Documentos</span>
        </a>
      </nav>

      <div class="p-3 border-t border-slate-100">
        <div class="flex items-center gap-3 px-2">
          <div class="h-8 w-8 rounded-full bg-orange-100 flex items-center justify-center text-xs font-bold text-orange-600">AD</div>
          <div class="flex flex-col leading-tight">
            <span class="text-xs font-bold text-slate-900">Valle</span>
            <span class="text-[10px] text-slate-500">valle@email.com</span>
          </div>
          <button class="ml-auto text-slate-400 hover:text-slate-600" title="Logout">
            <i class="ri-logout-box-r-line text-lg"></i>
          </button>
        </div>
      </div>
    </aside>

    <!-- MAIN -->
    <main class="content">

      <!-- Topbar -->
      <header class="topbar">
        <div style="display:flex;align-items:center;gap:12px">
          <div>
            <div class="text-lg font-bold">Calendário Mensal</div>
            <div class="text-sm muted">Planejamento da cena · Cena::rio</div>
          </div>
        </div>

        <div style="display:flex;gap:8px;align-items:center">
          <button id="prev-month" class="btn ghost small" aria-label="Mês anterior">
            <i class="ri-arrow-left-s-line"></i>
          </button>
          <div id="month-label" style="font-weight:800;min-width:140px;text-align:center"></div>
          <button id="next-month" class="btn ghost small" aria-label="Próximo mês">
            <i class="ri-arrow-right-s-line"></i>
          </button>
        </div>
      </header>

      <!-- Workspace: calendar + map (row 1 desktop) + events (row 2) -->
      <section class="workspace">

        <!-- Calendar -->
        <aside class="calendar-card" aria-label="Calendário">
          <div style="display:flex;justify-content:space-between;align-items:center;gap:12px">
            <div style="font-weight:700">Calendário</div>

            <!-- LEGENDA REFATORADA -->
            <div class="legend">
              <div class="legend-item">
                <div class="legend-dots">
                  <span class="legend-dot"></span>
                  </div>
                <span class="text-[10px]">Previsto</span>
              </div>
              <div class="legend-item">
                <div class="legend-dots">
                  <span class="legend-dot confirmed"></span>
                 </div>
                <span class="text-[10px]">Confirmado</span>
              </div>
              <div class="legend-item">
                <div class="legend-dots">
                  <span class="legend-dot published"></span>
                </div>
                <span class="text-[10px]">Público</span>
              </div>
            </div>
          </div>

          <div class="weekdays" aria-hidden="true">
            <div>Dom</div><div>Seg</div><div>Ter</div><div>Qua</div><div>Qui</div><div>Sex</div><div>Sáb</div>
          </div>

          <div id="calendar-grid" class="grid" role="grid" aria-label="Calendário mensal"></div>

          <div style="margin-top:12px;display:flex;gap:8px;justify-content:space-between;align-items:center">
            <button id="btn-add-event" class="btn"><i class="ri-add-line"></i> Novo Evento</button>
            <div style="font-size:10px;color:var(--muted)">Toque em um dia</div>
          </div>
        </aside>

        <!-- MAPA (no grid, ao lado do calendário no desktop, embaixo no mobile) -->
        <div class="map-card" aria-label="Mapa de eventos">
          <div id="footer-map"></div>
        </div>

        <!-- Events List -->
        <section class="events-panel">
          <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px">
            <div>
              <div id="selected-day" style="font-weight:800; font-size: 1.1rem;">Todos os eventos</div>
              <div class="muted">Lista de produções</div>
            </div>
            <div style="display:flex;gap:8px;align-items:center">
              <button id="toggle-map" class="btn ghost small">
                <i class="ri-map-pin-line"></i> Centralizar no Mapa
              </button>
            </div>
          </div>

          <div id="events-grid" class="events-grid" aria-live="polite">
            <!-- cards injected here -->
          </div>
        </section>

      </section>

      <!-- Mobile bottom nav -->
      <div class="bottom-nav" role="navigation" aria-label="Navegação inferior">
        <div class="nav-btn" onclick="location.href='#agenda'">
          <i class="ri-calendar-line"></i><span>Agenda</span>
        </div>
        <div class="nav-btn active" onclick="location.href='#fornecedores'">
          <i class="ri-bar-chart-grouped-line"></i><span>Pro</span>
        </div>
        <div style="position:relative;top:-18px">
          <button onclick="openQuickAdd()" class="h-14 w-14 rounded-full bg-slate-900 text-white flex items-center justify-center shadow-lg">
            <i class="ri-add-line text-3xl"></i>
          </button>
        </div>
        <div class="nav-btn" onclick="location.href='#docs'">
          <i class="ri-file-text-line"></i><span>Docs</span>
        </div>
        <div class="nav-btn" onclick="location.href='#settings'">
          <i class="ri-settings-3-line"></i><span>Ajustes</span>
        </div>
      </div>

    </main>
  </div>

  <!-- Modal root -->
  <div id="modal-root" style="display:none"></div>

  <script>
    /* ======= Data and persistence ======= */
    const STORAGE_KEY = 'cenario_events_v3';
    const DEFAULT_EVENTS = {
      "2025-11-09": [
        { id: "e1", title: "Dismantle · Puro Suco do Caos", venue: "Copacabana", time: "22:00 · sáb", tag: "Techno/House", status: "expected", lat: -22.9711, lng: -43.1822 },
        { id: "e2", title: "After Lovers (pós-Dismantle)", venue: "Botafogo", time: "04:30 · dom", tag: "After/Groove", status: "expected", lat: -22.9486, lng: -43.1800 }
      ],
      "2025-11-16": [
        { id: "e3", title: "Festival Miscelanea :: RJ", venue: "Zona Portuária", time: "18:00 · sáb", tag: "Festival", status: "confirmed", lat: -22.8968, lng: -43.1805 }
      ],
      "2025-11-23": [
        { id: "e4", title: "Cena::rio · Encontro Produtores", venue: "Centro", time: "15:00 · sáb", tag: "Encontro", status: "expected", lat: -22.9068, lng: -43.1729 }
      ]
    };

    function loadEvents(){
      try {
        const raw = localStorage.getItem(STORAGE_KEY);
        if(!raw) return JSON.parse(JSON.stringify(DEFAULT_EVENTS));
        return JSON.parse(raw);
      } catch(e){ return JSON.parse(JSON.stringify(DEFAULT_EVENTS)); }
    }
    function saveEvents(obj){
      try { localStorage.setItem(STORAGE_KEY, JSON.stringify(obj)); } catch(e){}
    }

    let events = loadEvents();

    /* ======= Calendar state ======= */
    const weekDays = ["Dom","Seg","Ter","Qua","Qui","Sex","Sáb"];
    const months = ["jan","fev","mar","abr","mai","jun","jul","ago","set","out","nov","dez"];
    let viewYear = 2025;
    let viewMonth = 10; // November
    let selectedDate = null; // null = todos eventos

    /* ======= DOM refs ======= */
    const gridEl = document.getElementById('calendar-grid');
    const monthLabelEl = document.getElementById('month-label');
    const selectedDayEl = document.getElementById('selected-day');
    const eventsGridEl = document.getElementById('events-grid');
    const prevMonthBtn = document.getElementById('prev-month');
    const nextMonthBtn = document.getElementById('next-month');
    const btnAdd = document.getElementById('btn-add-event');
    const footerMapEl = document.getElementById('footer-map');
    const toggleMapBtn = document.getElementById('toggle-map');

    /* ======= Map setup ======= */
    let map, markersLayer;
    function initMap(){
      try {
        map = L.map('footer-map', { zoomControl: true }).setView([-22.9068, -43.1729], 12);

        map.createPane('tilesPane'); map.getPane('tilesPane').style.zIndex = 200;
        map.createPane('eventsPane'); map.getPane('eventsPane').style.zIndex = 600;
        map.createPane('uiPane'); map.getPane('uiPane').style.zIndex = 1200;

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
          pane: 'tilesPane',
          attribution: '&copy; OpenStreetMap contributors'
        }).addTo(map);

        markersLayer = L.layerGroup([], { pane: 'eventsPane' }).addTo(map);
      } catch(e){
        console.warn('Leaflet init failed', e);
        footerMapEl.style.display = 'none';
      }
    }

    function updateMapMarkers(){
      if(!markersLayer) return;
      markersLayer.clearLayers();
      const keys = Object.keys(events);
      const toShow = [];
      keys.forEach(k => {
        (events[k] || []).forEach(ev => {
          if(!selectedDate || selectedDate === k) toShow.push(ev);
        });
      });
      const coords = [];
      toShow.forEach(ev => {
        if(ev.lat && ev.lng){
          const circle = L.circleMarker([ev.lat, ev.lng], {
            radius: ev.status === 'confirmed' ? 8 : 6,
            color: ev.status === 'confirmed'
              ? getComputedStyle(document.documentElement).getPropertyValue('--confirmed').trim()
              : getComputedStyle(document.documentElement).getPropertyValue('--accent').trim(),
            fillColor: ev.status === 'confirmed' ? '#10b981' : '#f97316',
            fillOpacity: 0.95,
            pane: 'eventsPane'
          });
          circle.bindPopup(
            `<strong>${escapeHtml(ev.title)}</strong><br>${escapeHtml(ev.venue)}<br>${escapeHtml(ev.time)}`,
            { autoPan: true, className: 'popup-ev' }
          );
          circle.addTo(markersLayer);
          coords.push([ev.lat, ev.lng]);
        }
      });
      if(coords.length) {
        const bounds = L.latLngBounds(coords);
        map.fitBounds(bounds.pad(0.5));
      }
    }

    /* ======= Calendar rendering ======= */
    function pad(n){ return String(n).padStart(2,'0'); }
    function isoDate(y,m,d){ return `${y}-${pad(m)}-${pad(d)}`; } // m 1-based
    function daysInMonth(year, monthIndex){ return new Date(year, monthIndex + 1, 0).getDate(); }
    function firstWeekdayOfMonth(year, monthIndex){ return new Date(year, monthIndex, 1).getDay(); }
    function formatSelectedLabel(iso){
      if(!iso) return 'Todos os eventos';
      const d = new Date(iso + 'T12:00:00');
      if(isNaN(d)) return 'Dia selecionado';
      const dayName = weekDays[d.getDay()];
      const dayNum = pad(d.getDate());
      const monthName = months[d.getMonth()];
      const year = d.getFullYear();
      return `${dayName} · ${dayNum} ${monthName} ${year}`;
    }

    function clearChildren(el){ while(el.firstChild) el.removeChild(el.firstChild); }

    function renderCalendar(){
      clearChildren(gridEl);
      const year = viewYear;
      const month = viewMonth;
      const totalDays = daysInMonth(year, month);
      const firstWeekday = firstWeekdayOfMonth(year, month);
      monthLabelEl.textContent = `${months[month].toUpperCase()} ${year}`;

      // leading days (mês anterior)
      const prevMonthIndex = month - 1 < 0 ? 11 : month - 1;
      const prevMonthYear = month - 1 < 0 ? year - 1 : year;
      const prevMonthDays = daysInMonth(prevMonthYear, prevMonthIndex);
      const leadingCount = firstWeekday;
      for(let i = leadingCount - 1; i >= 0; i--){
        const dayNum = prevMonthDays - i;
        const iso = isoDate(prevMonthYear, prevMonthIndex + 1, dayNum);
        const btn = createDayButton({day: dayNum, iso, disabled:true, events:[]});
        gridEl.appendChild(btn);
      }

      // current month
      for(let d = 1; d <= totalDays; d++){
        const iso = isoDate(year, month + 1, d);
        const dayEvents = events[iso] || [];
        const isSelected = iso === selectedDate;
        const btn = createDayButton({day:d, iso, events:dayEvents, selected:isSelected});
        gridEl.appendChild(btn);
      }

      // trailing days (próximo mês)
      const totalCells = leadingCount + totalDays;
      const trailing = (7 - (totalCells % 7)) % 7;
      const nextMonthIndex = month + 1 > 11 ? 0 : month + 1;
      const nextMonthYear = month + 1 > 11 ? year + 1 : year;
      for(let t = 1; t <= trailing; t++){
        const iso = isoDate(nextMonthYear, nextMonthIndex + 1, t);
        const btn = createDayButton({day:t, iso, disabled:true, events:[]});
        gridEl.appendChild(btn);
      }
    }

    /* Botão de dia com múltiplas bolinhas (um por evento) */
    function createDayButton({day, iso, disabled=false, events=[], selected=false}){
      const btn = document.createElement('button');
      btn.className = 'day-btn';
      btn.type = 'button';
      btn.setAttribute('role','gridcell');
      btn.setAttribute('aria-label', `${day}`);
      btn.textContent = day;

      if(disabled){
        btn.classList.add('disabled');
        btn.disabled = true;
        return btn;
      }

      btn.dataset.date = iso;
      btn.tabIndex = 0;
      if(selected) btn.classList.add('selected');

      if(events && events.length){
        const wrapper = document.createElement('div');
        wrapper.className = 'day-dots';

        // um dot para cada evento
        events.forEach(ev => {
          const dot = document.createElement('span');
          dot.className = 'day-dot';
          if(ev.status === 'confirmed') dot.classList.add('confirmed');
          else if(ev.status === 'published') dot.classList.add('published');
          // default = expected cor laranja
          wrapper.appendChild(dot);
        });

        btn.appendChild(wrapper);
      }

      btn.addEventListener('click', onDayClick);
      btn.addEventListener('keydown', (ev) => {
        if(ev.key === 'Enter' || ev.key === ' ') { ev.preventDefault(); btn.click(); }
      });

      return btn;
    }

    function onDayClick(e){
      const btn = e.currentTarget;
      const iso = btn.dataset.date;
      if(!iso) return;

      if(selectedDate === iso) selectedDate = null;
      else selectedDate = iso;

      gridEl.querySelectorAll('.day-btn').forEach(b => b.classList.remove('selected'));
      if(selectedDate){
        const sel = gridEl.querySelector(`[data-date="${selectedDate}"]`);
        if(sel) sel.classList.add('selected');
      }

      renderEventGrid();
      updateMapMarkers();
      selectedDayEl.textContent = formatSelectedLabel(selectedDate);
    }

    /* ======= Events list rendering ======= */
    function renderEventGrid(){
      clearChildren(eventsGridEl);
      selectedDayEl.textContent = formatSelectedLabel(selectedDate);

      const items = [];
      Object.keys(events).forEach(dateKey => {
        (events[dateKey] || []).forEach(ev => {
          items.push(Object.assign({}, ev, { dateKey }));
        });
      });

      items.sort((a,b) => {
        if(a.status === b.status) return a.dateKey < b.dateKey ? -1 : 1;
        return a.status === 'confirmed' ? -1 : 1;
      });

      const toRender = selectedDate ? items.filter(i => i.dateKey === selectedDate) : items;

      if(toRender.length === 0){
        const empty = document.createElement('div');
        empty.className = 'event-card';
        empty.style.justifyContent = 'center';
        empty.style.flexDirection = 'column';
        empty.style.alignItems = 'center';
        empty.style.gap = '8px';
        empty.style.textAlign = 'center';
        empty.style.color = '#94a3b8';
        empty.innerHTML = `<i class="ri-calendar-line" style="font-size:24px"></i><span>Nenhum evento para exibir</span>`;
        eventsGridEl.appendChild(empty);
        return;
      }

      toRender.forEach(ev => {
        const card = document.createElement('article');
        card.className = 'event-card ' + (ev.status === 'expected' ? 'expected' : 'confirmed');
        card.dataset.id = ev.id;

        const infoDiv = document.createElement('div');
        infoDiv.className = 'event-info';

        const title = document.createElement('div');
        title.className = 'event-title';
        title.textContent = ev.title;

        const meta = document.createElement('div');
        meta.className = 'event-meta';
        meta.innerHTML =
          `<span style="font-weight:600;color:var(--accent-strong)">${ev.dateKey.slice(5)}</span>` +
          ` · <i class="ri-time-line"></i> ${ev.time} · ` +
          `<i class="ri-map-pin-line"></i> ${ev.venue}`;

        infoDiv.appendChild(title);
        infoDiv.appendChild(meta);

        const controlsDiv = document.createElement('div');
        controlsDiv.className = 'event-controls';

        const badge = document.createElement('div');
        if(ev.status === 'expected'){
          badge.innerHTML =
            `<span style="display:inline-flex;align-items:center;gap:6px;padding:4px 8px;border-radius:6px;background:rgba(249,115,22,0.1);color:var(--accent);font-size:12px;font-weight:700"><i class="ri-radar-fill"></i> Previsto</span>`;
        } else {
          badge.innerHTML =
            `<span style="display:inline-flex;align-items:center;gap:6px;padding:4px 8px;border-radius:6px;background:rgba(16,185,129,0.1);color:var(--confirmed);font-size:12px;font-weight:700"><i class="ri-check-double-line"></i> Confirmado</span>`;
        }

        const actions = document.createElement('div');
        actions.className = 'event-actions';

        const editBtn = document.createElement('button');
        editBtn.className = 'btn small ghost';
        editBtn.innerHTML = '<i class="ri-edit-line"></i>';
        editBtn.title = "Editar";
        editBtn.addEventListener('click', (e) => { e.stopPropagation(); openEventModal(ev.id); });

        const deleteBtn = document.createElement('button');
        deleteBtn.className = 'btn small ghost';
        deleteBtn.innerHTML = '<i class="ri-delete-bin-line"></i>';
        deleteBtn.title = "Remover";
        deleteBtn.addEventListener('click', (e) => { e.stopPropagation(); onRemoveEvent(ev.id, ev.dateKey); });

        if(ev.status === 'expected'){
          const confirmBtn = document.createElement('button');
          confirmBtn.className = 'btn small';
          confirmBtn.innerHTML = 'Confirmar';
          confirmBtn.style.padding = '4px 8px';
          confirmBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            onConfirmEvent(ev.id, ev.dateKey);
          });
          actions.appendChild(confirmBtn);
        }

        actions.appendChild(editBtn);
        actions.appendChild(deleteBtn);

        controlsDiv.appendChild(badge);
        controlsDiv.appendChild(actions);

        card.appendChild(infoDiv);
        card.appendChild(controlsDiv);

        card.addEventListener('click', (e) => {
          if(e.target.tagName.toLowerCase() === 'button' || e.target.closest('button')) return;
          if(ev.lat && ev.lng && map) map.setView([ev.lat, ev.lng], 15, { animate: true });
        });

        eventsGridEl.appendChild(card);
      });
    }

    /* ======= Actions ======= */
    function onConfirmEvent(id, dateKey){
      const dayEvents = events[dateKey] || [];
      const ev = dayEvents.find(x => x.id === id);
      if(!ev) return;
      ev.status = 'confirmed';
      saveEvents(events);
      renderCalendar();
      renderEventGrid();
      updateMapMarkers();
    }

    function onRemoveEvent(id, dateKey){
      if(!confirm('Remover este evento?')) return;
      const dayEvents = events[dateKey] || [];
      const idx = dayEvents.findIndex(x => x.id === id);
      if(idx === -1) return;
      dayEvents.splice(idx,1);
      if(dayEvents.length === 0) delete events[dateKey];
      saveEvents(events);
      renderCalendar();
      renderEventGrid();
      updateMapMarkers();
    }

    /* ======= Modal create/edit ======= */
    function openEventModal(eventId){
      const modalRoot = document.getElementById('modal-root');
      modalRoot.style.display = 'flex';
      modalRoot.innerHTML = '';

      const backdrop = document.createElement('div');
      backdrop.style.position = 'fixed';
      backdrop.style.inset = '0';
      backdrop.style.background = 'rgba(2,6,23,0.45)';
      backdrop.style.display = 'flex';
      backdrop.style.alignItems = 'center';
      backdrop.style.justifyContent = 'center';
      backdrop.style.zIndex = '9999';
      backdrop.addEventListener('click', (e) => { if(e.target === backdrop) closeModal(); });

      const modal = document.createElement('div');
      modal.style.width = 'min(720px,95%)';
      modal.style.background = '#fff';
      modal.style.borderRadius = '12px';
      modal.style.padding = '16px';
      modal.style.boxShadow = '0 12px 40px rgba(2,6,23,0.2)';

      const isEdit = !!eventId;
      modal.innerHTML = `
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px">
          <div style="font-weight:800">${isEdit ? 'Editar Evento' : 'Novo Evento'}</div>
          <button id="modal-close" class="btn ghost small"><i class="ri-close-line"></i></button>
        </div>
        <form id="event-form" style="display:flex;flex-direction:column;gap:8px">
          <input id="ev-date" type="date" class="w-full border rounded px-3 py-2" required />
          <input id="ev-title" placeholder="Nome do evento" class="w-full border rounded px-3 py-2" required />
          <div style="display:flex;gap:8px">
            <input id="ev-venue" placeholder="Local" class="flex-1 border rounded px-3 py-2" />
            <input id="ev-time" placeholder="Horário" class="w-32 border rounded px-3 py-2" />
          </div>
          <div style="display:flex;gap:8px">
            <input id="ev-lat" placeholder="Lat" class="flex-1 border rounded px-3 py-2" />
            <input id="ev-lng" placeholder="Lng" class="flex-1 border rounded px-3 py-2" />
          </div>
          <div style="display:flex;gap:8px;justify-content:flex-end">
            <button type="button" id="save-ev" class="btn">Salvar</button>
            <button type="button" id="cancel-ev" class="btn ghost">Cancelar</button>
          </div>
        </form>
      `;
      backdrop.appendChild(modal);
      modalRoot.appendChild(backdrop);

      document.getElementById('modal-close').addEventListener('click', closeModal);
      document.getElementById('cancel-ev').addEventListener('click', closeModal);

      if(isEdit){
        let found = null; let foundDate = null;
        Object.keys(events).forEach(k => {
          (events[k] || []).forEach(ev => { if(ev.id === eventId){ found = ev; foundDate = k; }});
        });
        if(found){
          document.getElementById('ev-date').value = foundDate;
          document.getElementById('ev-title').value = found.title;
          document.getElementById('ev-venue').value = found.venue;
          document.getElementById('ev-time').value = found.time;
          document.getElementById('ev-lat').value = found.lat || '';
          document.getElementById('ev-lng').value = found.lng || '';
        }
      } else {
        const d = selectedDate || new Date().toISOString().slice(0,10);
        document.getElementById('ev-date').value = d;
      }

      document.getElementById('save-ev').addEventListener('click', () => {
        const date = document.getElementById('ev-date').value;
        const title = document.getElementById('ev-title').value.trim();
        const venue = document.getElementById('ev-venue').value.trim();
        const time = document.getElementById('ev-time').value.trim();
        const lat = parseFloat(document.getElementById('ev-lat').value) || null;
        const lng = parseFloat(document.getElementById('ev-lng').value) || null;
        if(!date || !title) { alert('Preencha data e nome'); return; }

        if(isEdit){
          let found = null; let foundDate = null;
          Object.keys(events).forEach(k => {
            (events[k] || []).forEach(ev => { if(ev.id === eventId){ found = ev; foundDate = k; }});
          });
          if(found){
            found.title = title;
            found.venue = venue;
            found.time = time;
            found.lat = lat;
            found.lng = lng;
            if(foundDate !== date){
              events[foundDate] = events[foundDate].filter(x => x.id !== eventId);
              if(events[foundDate].length === 0) delete events[foundDate];
              events[date] = events[date] || [];
              events[date].push(found);
            }
          }
        } else {
          const id = 'e' + Math.random().toString(36).slice(2,9);
          const newEv = { id, title, venue, time, tag:'', status:'expected', lat, lng };
          events[date] = events[date] || [];
          events[date].push(newEv);
        }
        saveEvents(events);
        closeModal();
        renderCalendar();
        renderEventGrid();
        updateMapMarkers();
      });
    }

    function closeModal(){
      const modalRoot = document.getElementById('modal-root');
      modalRoot.style.display = 'none';
      modalRoot.innerHTML = '';
    }

    function openQuickAdd(){
      openEventModal(null);
    }

    function escapeHtml(str){
      if(!str) return '';
      return String(str).replace(/[&<>"']/g, s =>
        ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[s])
      );
    }

    /* ======= Init ======= */
    prevMonthBtn.addEventListener('click', () => {
      viewMonth--;
      if(viewMonth < 0){ viewMonth = 11; viewYear--; }
      renderCalendar();
    });
    nextMonthBtn.addEventListener('click', () => {
      viewMonth++;
      if(viewMonth > 11){ viewMonth = 0; viewYear++; }
      renderCalendar();
    });
    btnAdd.addEventListener('click', () => openEventModal(null));
    toggleMapBtn.addEventListener('click', () => updateMapMarkers());

    renderCalendar();
    renderEventGrid();
    initMap();
    updateMapMarkers();

    window._CENARIO = { events, renderCalendar, renderEventGrid, updateMapMarkers };
  </script>
</body>
</html>
