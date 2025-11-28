<!DOCTYPE html>
<html lang="pt-BR" class="h-full">

<head>
  <meta charset="UTF-8" />
  <title>Cena::rio · Calendário</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://assets.apollo.rio.br/uni.css" />
  <script src="https://unpkg.com/@motionone/dom@10.16.4/dist/index.js"></script>
</head>

<body class="aprioEXP-body h-full" style="background: var(--bg-surface);">

  <div class="min-h-screen flex flex-col">
    <!-- Header -->
    <header class="sticky top-0 z-50 h-14 bg-white/80 backdrop-blur-xl border-b border-slate-200/50">
      <div class="h-full px-3 flex items-center justify-between">
        <div class="flex items-center gap-2.5">
          <button class="md:hidden h-9 w-9 flex items-center justify-center rounded-full hover: bg-neutral-100">
            <i class="ri-arrow-left-line text-slate-700"></i>
          </button>
          <div class="h-9 w-9 rounded-full  bg-neutral-900 flex items-center justify-center">
            <i class="ri-slack-fill text-white text-[21px]"></i>
          </div>
          <div>
            <h1 class="text-[18px] font-bold text-slate-900">Cena::rio</h1>
            <p class="text-[12px] text-slate-500">Calendário da Indústria de Eventos</p>
          </div>
        </div>
        <div class="flex items-center gap-2">
          <span class="hidden sm:inline-flex items-center gap-1 px-3 py-1 rounded-full bg-amber-50 text-amber-700 text-[9px] font-semibold">
          
            <span>
              <font class="font-black">CENA<i class="ri-command-fill text-[8px]"></i>RIO</font>
            </span>
          </span>
          <button class="h-9 w-9 flex items-center justify-center rounded-full hover: bg-neutral-100">
            <i class="ri-settings-3-line text-slate-600"></i>
          </button>
        </div>
      </div>
    </header>

    <!-- Main -->
    <main class="flex-1 px-3 py-4 overflow-y-auto">
      <div class="max-w-2xl mx-auto space-y-4">

        <!-- Calendar -->
        <section id="calendar-card" class="bg-white rounded-2xl shadow-sm border border-slate-200/50 p-4">
          <div class="flex items-start justify-between mb-4">
            <div>
              <h2 class="text-[19px] font-bold text-slate-900 mt-2.5">Calendário Mensal</h2>
              <p class="text-[12.5px] text-slate-500 mt-1.5">Apollo, por uma cena unida e conectada!</p>
            </div>
            <div class="flex items-center gap-0 px-0 py-0.5 rounded-full border border-slate-200  bg-neutral-50">
              <button class="h-6 w-6 flex items-center justify-center rounded-full hover:bg-neutral-100">
                <i class="ri-arrow-left-s-line text-slate-600"></i>
              </button>
              <span id="month-label" class="text-[9px] uppercase font-semibold text-slate-900 px-0">Nov 2025</span>
              <button class="h-6 w-6 flex items-center justify-center rounded-full hover:bg-neutral-100">
                <i class="ri-arrow-right-s-line text-slate-600"></i>
              </button>
            </div>
          </div>

          <!-- Calendar Grid -->
          <div class=" bg-neutral-50 rounded-xl p-3 border border-slate-100">
            <div class="grid grid-cols-7 gap-1 text-[10px] text-slate-500 text-center mb-2 font-medium">
              <div>Dom</div>
              <div>Seg</div>
              <div>Ter</div>
              <div>Qua</div>
              <div>Qui</div>
              <div>Sex</div>
              <div>Sáb</div>
            </div>
            <div id="calendar-grid" class="grid grid-cols-7 gap-1">
              <!-- JS renders days -->
            </div>
          </div>

          <!-- Legend -->
          <div class="flex items-center justify-between mt-3 text-[10px]">
            <div class="flex items-center gap-3 text-slate-500">
              <span class="flex items-center gap-1">
                <span class="w-3 h-1.5 rounded-full bg-orange-300 opacity-50"></span>
                Previsto
              </span>
              <span class="flex items-center gap-1">
                <span class="w-3 h-1.5 rounded-full bg-orange-500"></span>
                Oficial
              </span>
            </div>
            <button id="btn-add-event" class="px-3 py-1.5 bg-stone-900 text-white rounded-full text-[11px] font-medium hover:bg-stone-800">
              <i class="ri-add-line mr-1"></i>Novo Evento
            </button>
          </div>
        </section>

        <!-- Events List -->
        <section id="events-card" class="bg-white rounded-2xl shadow-sm border border-slate-200/50 p-4">
          <div class="flex items-start justify-between mb-4">
            <div>
              <h2 id="selected-day" class="text-[17px] font-bold text-slate-900">Dia selecionado</h2>
              <p class="text-[13px] text-slate-500 mt-0.5">Eventos previstos e confirmados</p>
            </div>
            <button class="hidden sm:flex items-center gap-1 px-2.5 py-1 rounded-full border border-slate-200 text-[12px] text-slate-600 hover: bg-neutral-50">
              <i class="ri-download-line"></i>
              Exportar
            </button>
          </div>
          <div id="events-list" class="space-y-3">
            <!-- JS renders events -->
          </div>
        </section>
      </div>
    </main>

    <!-- Bottom Toolbar (Mobile) -->
    <div class="md:hidden sticky bottom-0 bg-white/90 backdrop-blur-xl border-t border-slate-200/50 px-3 py-2 flex items-center justify-around">
      <button class="flex flex-col items-center gap-0.5 text-slate-600">
        <i class="ri-calendar-line text-xl"></i>
        <span class="text-[9px]">Calendário</span>
      </button>
      <button class="flex flex-col items-center gap-0.5 text-slate-400">
        <i class="ri-bar-chart-line text-xl"></i>
        <span class="text-[9px]">Stats</span>
      </button>
      <button id="btn-add-mobile" class="h-12 w-12 -mt-8 rounded-full  bg-neutral-900 text-white flex items-center justify-center shadow-lg">
        <i class="ri-add-line text-2xl"></i>
      </button>
      <button class="flex flex-col items-center gap-0.5 text-slate-400">
        <i class="ri-team-line text-xl"></i>
        <span class="text-[9px]">Cena</span>
      </button>
      <button class="flex flex-col items-center gap-0.5 text-slate-400">
        <i class="ri-settings-3-line text-xl"></i>
        <span class="text-[9px]">Config</span>
      </button>
    </div>
  </div>

  <script>
    const events = {
      "2025-11-09": [{
          id: "e1",
          title: "Dismantle · Puro Suco do Caos",
          venue: "Copacabana",
          time: "22:00 · sáb",
          tag: "Techno/House",
          status: "expected",
          ticket: ""
        },
        {
          id: "e2",
          title: "After Lovers (pós-Dismantle)",
          venue: "Botafogo",
          time: "04:30 · dom",
          tag: "After/Groove",
          status: "expected",
          ticket: ""
        }
      ],
      "2025-11-16": [{
        id: "e3",
        title: "Festival Tropicalis :: RJ",
        venue: "Zona Portuária",
        time: "18:00 · sáb",
        tag: "Festival",
        status: "confirmed",
        ticket: "https://exemplo.com/tropicalis"
      }],
      "2025-11-23": [{
        id: "e4",
        title: "Cena::rio · Encontro Produtores",
        venue: "Centro",
        time: "15:00 · sáb",
        tag: "Encontro",
        status: "expected",
        ticket: ""
      }]
    };
    let selectedDate = "2025-11-09";
    const weekDays = ["Dom", "Seg", "Ter", "Qua", "Qui", "Sex", "Sáb"];
    const months = ["jan", "fev", "mar", "abr", "mai", "jun", "jul", "ago", "set", "out", "nov", "dez"];

    function formatDate(dateStr) {
      const d = new Date(dateStr + "T12:00:00");
      const day = weekDays[d.getDay()];
      const num = String(d.getDate()).padStart(2, "0");
      const month = months[d.getMonth()];
      return `${day} · ${num} ${month} ${d.getFullYear()}`;
    }

    function renderCalendar() {
      const grid = document.getElementById("calendar-grid");
      const days = Array.from({
        length: 30
      }, (_, i) => i + 1);
      const leading = [29, 30, 31]; // Oct days
      let html = "";
      leading.forEach(d => {
        html += `<button class="day-btn opacity-30" data-date="2025-10-${d}">${d}</button>`;
      });
      days.forEach(d => {
        const date = `2025-11-${String(d).padStart(2, "0")}`;
        const hasEvents = events[date] && events[date].length > 0;
        const isSelected = date === selectedDate;
        const hasConfirmed = hasEvents && events[date].some(e => e.status === "confirmed");
        html += `
        <button 
          class="day-btn ${isSelected ? "selected" : ""} ${hasEvents ? "has-events" : ""} ${hasConfirmed ? "has-confirmed" : ""}" 
          data-date="${date}"
        >
          ${d}
        </button>
      `;
      });
      grid.innerHTML = html;
      grid.querySelectorAll(".day-btn").forEach(btn => {
        btn.addEventListener("click", () => {
          selectedDate = btn.dataset.date;
          renderCalendar();
          renderEvents();
          animateCard("events-card");
        });
      });
    }

    function renderEvents() {
      const container = document.getElementById("events-list");
      const label = document.getElementById("selected-day");
      label.textContent = formatDate(selectedDate);
      const dayEvents = events[selectedDate] || [];
      if (dayEvents.length === 0) {
        container.innerHTML = `
        <div class="pb-6 pt-8 gap-[10px] rounded-xl  bg-neutral-50 border border-dashed border-slate-200 text-center">
          <i class="ri-calendar-line  text-[42px] text-slate-300 mt-4 mb-4"></i>
          <p class="text-[16px] text-slate-600 font-semibold">Vamo agitar?!</p>
          <p class="text-[12px] pt-5 text-slate-600 font-medium">Nenhum registro até o momento para este dia..</p>
          <p class="text-[11px] text-slate-500 mt-1">Use <b>"+ Novo Evento"</b> para marcar datas previstas</p>
        </div>
      `;
        return;
      }
      const confirmed = dayEvents.filter(e => e.status === "confirmed");
      const expected = dayEvents.filter(e => e.status === "expected");
      let html = "";
      if (confirmed.length) {
        html += `<div class="text-[13px] font-semibold text-slate-800 mb-2 flex items-center gap-1">
        <i class="ri-check-double-line text-emerald-600"></i>Evento[s] Confirmado[s]
      </div>`;
        confirmed.forEach(e => html += renderEventCard(e, false));
      }
      if (expected.length) {
        if (confirmed.length) html += `<div class="h-px  bg-neutral-100 my-3"></div>`;
        html += `<div class="text-[13px] font-semibold text-slate-800 mb-2 flex items-center gap-1">
        <i class="ri-timer-line text-orange-500"></i>Evento[s] Previsto[s] 
      </div>`;
        expected.forEach(e => html += renderEventCard(e, true));
      }
      container.innerHTML = html;
      wireEventActions();
    }

    function renderEventCard(ev, isExpected) {
      const opacity = isExpected ? "opacity-50" : "";
      const border = isExpected ? "border-dashed border-orange-300 bg-orange-50/30" : "border-orange-400 bg-orange-50/50";
      const badge = isExpected ?
        `<span class="px-2 py-0.5 rounded-full border border-dashed border-orange-400 bg-orange-100 text-orange-800 text-[10px] font-regular uppercase"><i class="ri-radar-fill  align-sub text-[21px]"></i> Previsto</span>` :
        `<span class="px-2 py-0.5 rounded-full  bg-neutral-900 text-white text-[10px] font-regular uppercase flex items-center gap-1"><i class="ri-wireless-charging-fill align-sub text-[21px]"></i>Confirmado</span>`;
      const action = (() => {
        if (isExpected) {
          return `<button class="btn-confirm px-3 py-1.5 bg-neutral-900 text-white rounded-full text-[11px] font-medium hover:bg-neutral-600" data-id="${ev.id}">
          <i class="ri-check-line mr-1"></i>Confirmar
        </button>`;
        }
        if (!ev.ticket) {
          return `<button class="btn-ticket px-3 py-1.5 border border-slate-300 rounded-full text-[13px] font-medium hover: bg-neutral-50" data-id="${ev.id}">
          <i class="ri-link mr-1"></i>Incluir Link para Ingressos
        </button>`;
        }
        return `<a href="${ev.ticket}" target="_blank" class="px-3 py-1.5  bg-neutral-900 text-white rounded-full text-[13px] font-medium hover: bg-neutral-800 inline-flex items-center">
        <i class="ri-ticket-2-line mr-1"></i>Ingressos
      </a>`;
      })();
      return `
      <article class="p-3 rounded-xl border ${border} ${opacity}" data-id="${ev.id}">
        <div class="flex items-start justify-between gap-2 mb-2">
          <div class="flex-1 min-w-0">
            <h3 class="text-[13px] font-semibold text-slate-900 truncate">${ev.title}</h3>
            <p class="text-[12px] text-slate-600 mt-0.5">${ev.venue} · ${ev.time}</p>
            <p class="text-[11px] text-slate-500 mt-1">${ev.tag}</p>
          </div>
          ${badge}
        </div>
        <div class="flex items-center justify-between gap-2 pt-2 border-t border-slate-100">
          <span class="text-[12px] text-slate-400 flex items-center gap-1">
            <i class="ri-eye-off-line"></i>Apenas cena::rio
          </span>
          ${action}
        </div>
      </article>
    `;
    }

    function wireEventActions() {
      document.querySelectorAll(".btn-confirm").forEach(btn => {
        btn.addEventListener("click", () => {
          const id = btn.dataset.id;
          const dayEvents = events[selectedDate];
          const ev = dayEvents.find(e => e.id === id);
          if (!ev) return;
          const url = prompt("Link de ingressos (opcional):", "");
          ev.status = "confirmed";
          if (url && url.trim()) ev.ticket = url.trim();
          renderCalendar();
          renderEvents();
          animateCard("events-card");
        });
      });
      document.querySelectorAll(".btn-ticket").forEach(btn => {
        btn.addEventListener("click", () => {
          const id = btn.dataset.id;
          const dayEvents = events[selectedDate];
          const ev = dayEvents.find(e => e.id === id);
          if (!ev) return;
          const url = prompt("Link de ingressos:", ev.ticket || "");
          if (url && url.trim()) {
            ev.ticket = url.trim();
            renderEvents();
          }
        });
      });
    }

    function addEvent() {
      const title = prompt("Nome do evento para " + formatDate(selectedDate) + ":", "");
      if (!title || !title.trim()) return;
      const venue = prompt("Local:", "Secreto");
      const time = prompt("Horário:", "23:00");
      const tag = prompt("Sonoridades:", "House");
      if (!events[selectedDate]) events[selectedDate] = [];
      events[selectedDate].push({
        id: "new_" + Date.now(),
        title: title.trim(),
        venue: venue || "A definir",
        time: time || "A definir",
        tag: tag || "A definir",
        status: "expected",
        ticket: ""
      });
      renderCalendar();
      renderEvents();
      animateCard("events-card");
    }

    function animateCard(id) {
      const {
        animate
      } = window.Motion;
      const card = document.getElementById(id);
      if (!card) return;
      animate(card, {
        opacity: [0.9, 1],
        transform: ["translateY(4px)", "translateY(0)"]
      }, {
        duration: 0.2,
        easing: "ease-out"
      });
    }
    document.addEventListener("DOMContentLoaded", () => {
      renderCalendar();
      renderEvents();
      document.getElementById("btn-add-event").addEventListener("click", addEvent);
      document.getElementById("btn-add-mobile").addEventListener("click", addEvent);
      // Entrance animations
      const {
        animate
      } = window.Motion;
      ["calendar-card", "events-card"].forEach((id, i) => {
        const el = document.getElementById(id);
        animate(el, {
          opacity: [0, 1],
          transform: ["translateY(20px)", "translateY(0)"]
        }, {
          duration: 0.4,
          delay: i * 0.1,
          easing: [0.25, 0.8, 0.25, 1]
        });
      });
    });
  </script>

  <style>
    .day-btn {
      @apply relative h-10 rounded-full flex items-center justify-center text-[12px] font-medium text-slate-700 hover:  bg-neutral-100 transition-all;
    }

    .day-btn.selected {
      @apply  bg-neutral-900 text-white ring-2 ring-slate-900 ring-offset-2;
    }

    .day-btn.has-events::after {
      content: "";
      @apply absolute bottom-1 left-1/2 -translate-x-1/2 w-1 h-1 rounded-full bg-orange-400 opacity-50;
    }

    .day-btn.has-confirmed::after {
      @apply bg-orange-500 opacity-100 w-1.5 h-1.5;
    }

    @media (min-width: 768px) {
      .day-btn {
        @apply h-11;
      }
    }
  </style>

</body>

</html>