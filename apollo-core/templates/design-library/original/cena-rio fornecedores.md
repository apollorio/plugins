<!DOCTYPE html>
<html lang="pt-BR" class="h-full">
<head>
  <meta charset="UTF-8" />
  <title>Cena::rio · Catálogo de Fornecedores</title>
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" />

  <!-- TAILWIND -->
  <script src="https://cdn.tailwindcss.com"></script>

  <!-- REMIXICON -->
  <link href="https://cdn.jsdelivr.net/npm/remixicon@4.2.0/fonts/remixicon.css" rel="stylesheet" />

  <!-- MOTION ONE -->
  <script src="https://unpkg.com/@motionone/dom@10.16.4/dist/index.js"></script>

  <!-- GOOGLE FONTS: URBANIST -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Urbanist:wght@400;500;600;700;800&display=swap" rel="stylesheet">

  <!-- UNI.CSS GLOBAL TOKENS -->
  <style>
    :root {
      --bg-surface: #f8fafc;
      --primary: #0f172a;
      --accent: #f97316;
      --accent-strong: #ea580c;
      --radius-xl: 1rem;
      --radius-2xl: 1.5rem;
      --radius-3xl: 2rem;
      --sidebar-width: 16rem;
      --text-muted: #94a3b8;
      --border-subtle: #e2e8f0;
      --shadow-soft: 0 4px 24px -6px rgba(0,0,0,0.08);
      --shadow-hard: 0 8px 28px -8px rgba(15,23,42,0.45);
    }

    body {
      font-family: 'Urbanist', sans-serif;
      background-color: var(--bg-surface);
      -webkit-tap-highlight-color: transparent;
      overflow: hidden;
    }

    ::-webkit-scrollbar { width: 4px; }
    ::-webkit-scrollbar-track { background: transparent; }
    ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }

    .hide-scroll::-webkit-scrollbar { display: none; }
    .hide-scroll { -ms-overflow-style: none; scrollbar-width: none; }

    .nav-btn { display:flex; flex-direction:column; align-items:center; justify-content:center; gap:2px; font-size:9px; color:#94a3b8; }
    .nav-btn.active { color:#0f172a; }
    .nav-btn i { font-size:1.4rem; }
    .filter-chip { padding:6px 14px; background:rgba(15,23,42,0.05); border-radius:20px; font-weight:600; font-size:.8rem; }
    .filter-chip.active { background:#0f172a; color:white; }

    /* RIGHT SIDEBAR MINI CALENDAR / EVENTS (READ-ONLY) */
    .mini-card {
      background:#ffffff;
      border:1px solid var(--border-subtle);
      border-radius:1rem;
      padding:1rem;
      box-shadow:var(--shadow-soft);
    }
    .mini-card-title {
      font-size:0.85rem;
      font-weight:600;
      color:#0f172a;
      display:flex;
      align-items:center;
      justify-content:space-between;
      gap:.25rem;
    }
    .mini-month-label {
      font-size:0.75rem;
      font-weight:500;
      color:var(--text-muted);
      text-align:left;
      margin-top:.15rem;
    }
    .mini-weekdays {
      display:grid;
      grid-template-columns:repeat(7,1fr);
      gap:4px;
      margin-top:.5rem;
      font-size:0.65rem;
      text-align:center;
      color:#94a3b8;
    }
    .mini-calendar-grid {
      display:grid;
      grid-template-columns:repeat(7,1fr);
      gap:4px;
      margin-top:.35rem;
      font-size:0.7rem;
    }
    .mini-day {
      position:relative;
      text-align:center;
      padding:3px 0;
      border-radius:999px;
      color:#0f172a;
    }
    .mini-day.has-events {
      font-weight:600;
      background:rgba(15,23,42,0.03);
    }
    .mini-day-dot {
      position:absolute;
      bottom:2px;
      left:50%;
      transform:translateX(-50%);
      width:5px;
      height:5px;
      border-radius:999px;
      background:var(--accent);
    }
    .mini-day-dot.confirmed { background:#10b981; }
    .mini-day-dot.published { background:#3b82f6; }

    .mini-legend {
      display:flex;
      flex-wrap:wrap;
      gap:8px;
      margin-top:0.6rem;
    }
    .mini-legend-item {
      display:flex;
      align-items:center;
      gap:4px;
      font-size:0.68rem;
      color:#64748b;
      white-space:nowrap;
    }
    .mini-legend-dot {
      width:6px;
      height:6px;
      border-radius:999px;
      background:var(--accent);
    }
    .mini-legend-dot.confirmed { background:#10b981; }
    .mini-legend-dot.published { background:#3b82f6; }

    .mini-events-list {
      margin-top:0.5rem;
      display:flex;
      flex-direction:column;
      gap:0.4rem;
    }
    .mini-event-item {
      border-radius:0.75rem;
      padding:0.5rem 0.6rem;
      background:#f8fafc;
      border:1px solid #e2e8f0;
      font-size:0.7rem;
    }
    .mini-event-title {
      font-weight:600;
      color:#0f172a;
      display:flex;
      justify-content:space-between;
      gap:.25rem;
    }
    .mini-event-meta {
      margin-top:0.2rem;
      color:#64748b;
      font-size:0.7rem;
      display:flex;
      flex-wrap:wrap;
      gap:0.3rem;
      align-items:center;
    }
    .mini-event-chip {
      border-radius:999px;
      padding:0.1rem 0.45rem;
      background:rgba(15,23,42,0.03);
      font-size:0.65rem;
      color:#0f172a;
    }
  </style>
</head>

<body class="h-full flex overflow-hidden bg-slate-50">

  <!-- SIDEBAR ESQUERDA (DESKTOP) -->
  <aside class="hidden md:flex w-64 h-screen flex-col border-r border-slate-200 bg-white z-40">

    <!-- HEADER SIDEBAR -->
    <div class="h-16 flex items-center gap-3 px-6 border-b border-slate-100">
      <div class="h-8 w-8 rounded-full bg-slate-900 flex items-center justify-center text-white">
        <i class="ri-command-fill text-lg"></i>
      </div>
      <span class="font-bold text-slate-900 tracking-tight text-lg">Cena::Rio</span>
    </div>

    <!-- NAVIGATION -->
    <nav class="flex-1 px-4 space-y-1 py-6 overflow-y-auto custom-scrollbar">
      <div class="px-2 mb-2 text-[10px] font-bold text-slate-400 uppercase tracking-wider">
        Menu Principal
      </div>

      <a href="https://apollo.rio.br/cena-rio/"
         class="flex items-center gap-3 px-3 py-2.5 text-slate-600 rounded-lg hover:bg-slate-50 group">
        <i class="ri-calendar-line text-lg group-hover:text-slate-900"></i>
        <span class="font-medium text-sm group-hover:text-slate-900">Agenda</span>
      </a>

      <a href="#"
         class="flex items-center gap-3 px-3 py-2.5 bg-slate-100 text-slate-900 rounded-lg font-semibold">
        <i class="ri-bar-chart-grouped-line text-lg"></i>
        <span class="text-sm">Fornecedores</span>
      </a>

      <a href="https://apollo.rio.br/assinar/index.html"
         class="flex items-center gap-3 px-3 py-2.5 text-slate-600 rounded-lg hover:bg-slate-50 group">
        <i class="ri-file-text-line text-lg"></i>
        <span class="font-medium text-sm">Documentos</span>
      </a>
    </nav>

    <!-- FOOTER -->
    <div class="p-0 border-t border-slate-100">
      <div class="relative flex w-full min-w-0 flex-col px-3">

        <div class="w-full text-sm">
          <ul class="flex w-full min-w-0 flex-col gap-0 ">
            <li class="relative">
              <a href="#"
                 class="flex w-full items-center gap-0 px-2 text-left text-slate-600
                        hover:bg-slate-100 text-[13px]">
                <span>Sobre</span>
              </a>
            </li>
            <li class="relative">
              <a href="#"
                 class="flex w-full items-center gap-0 overflow-hidden rounded-md px-2
                        text-left text-slate-600 hover:bg-slate-100 text-[13px]">
                <span>Ajustes</span>
              </a>
            </li>
            <li class="relative">
              <a href="#"
                 class="flex w-full items-center gap-0 px-2 text-left text-red-600
                        hover:bg-slate-100 text-[13px]">
                <span>Denúncia</span>
              </a>
            </li>
          </ul>
        </div>

        <!-- BLOCO DO USUÁRIO -->
        <div class="flex flex-col gap-2 p-2 mt-2 border-t border-slate-200">
          <div class="flex items-center gap-3 px-2">
            <div class="h-8 w-8 rounded-full bg-orange-100 flex items-center justify-center text-xs font-bold text-orange-600">
              AD
            </div>

            <div class="flex flex-col leading-tight">
              <span class="text-xs font-bold text-slate-900">Valle</span>
              <span class="text-[10px] text-slate-500">valle@email.com</span>
            </div>

            <button class="ml-auto text-slate-400 hover:text-slate-600" title="Logout">
              <i class="ri-logout-box-r-line text-lg"></i>
            </button>
          </div>
        </div>

      </div>
    </div>
  </aside>

  <!-- MAIN CONTENT WRAPPER -->
  <div class="flex-1 flex flex-col h-full relative overflow-hidden bg-slate-50/50">

    <!-- HEADER (Mobile branding + desktop context) -->
    <header class="flex-none bg-white/80 backdrop-blur-xl border-b border-slate-200/60 z-30 relative">
      <div class="px-4 h-16 flex items-center justify-between max-w-5xl mx-auto w-full">

        <!-- MOBILE HEADER -->
        <div class="flex items-center gap-3 md:invisible">
          <div class="h-10 w-10 rounded-full bg-slate-900 flex items-center justify-center text-white shadow-lg shadow-slate-900/20">
            <i class="ri-command-fill text-xl"></i>
          </div>
          <div class="flex flex-col leading-tight">
            <h1 class="text-lg font-bold text-slate-900 tracking-tight">Fornecedores</h1>
            <span class="text-[11px] font-semibold text-orange-500 tracking-widest uppercase">Cena::Rio Official</span>
          </div>
        </div>

        <!-- DESKTOP TITLE -->
        <div class="hidden md:flex flex-col leading-tight">
          <h1 class="text-xl font-bold text-slate-900">Catálogo de Fornecedores</h1>
        </div>

        <!-- ACTIONS -->
        <div class="flex items-center gap-2">
          <button class="h-9 w-9 rounded-full bg-slate-50 border border-slate-100 flex items-center justify-center text-slate-600 hover:bg-slate-100 transition-colors" title="Novo Fornecedor" alt="Novo Fornecedor">
            +
          </button>
        </div>
      </div>
    </header>

    <!-- SEARCH & FILTERS -->
    <div class="flex-none bg-white z-20 shadow-[0_4px_20px_-12px_rgba(0,0,0,0.1)]">
      <div class="max-w-5xl mx-auto w-full px-4 py-4 space-y-4">

        <!-- Search input -->
        <div class="relative group">
          <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
            <i class="ri-search-2-line text-slate-400 group-focus-within:text-orange-500 transition-colors"></i>
          </div>
          <input
            type="text"
            id="searchInput"
            placeholder="Buscar nome, equipamento ou serviço..."
            class="block w-full pl-10 pr-4 py-2.5 bg-slate-50 border-none rounded-xl text-sm font-medium text-slate-900 placeholder:text-slate-400 focus:ring-2 focus:ring-orange-500/20 focus:bg-white transition-all shadow-inner"
          >
        </div>

        <!-- Popular searches -->
        <div class="popular-searches md:justify-start flex flex-wrap gap-2">
          <button class="filter-chip active" data-cat="all">Todos</button>
          <button class="filter-chip" data-cat="sound">Áudio & Som</button>
          <button class="filter-chip" data-cat="light">Iluminação</button>
          <button class="filter-chip" data-cat="security">Segurança</button>
          <button class="filter-chip" data-cat="bar">Bar & Bebidas</button>
          <button class="filter-chip" data-cat="visuals">Cenografia</button>
          <button class="filter-chip" data-cat="staff">Staff & Recep</button>
        </div>

      </div>
    </div>

    <!-- MAIN LIST CONTENT -->
    <main class="flex-1 overflow-y-auto bg-slate-50/50 scroll-smooth relative" id="mainContainer">
      <div class="max-w-5xl mx-auto w-full px-4 py-6 pb-24">

        <!-- List Header -->
        <div class="flex items-center justify-between mb-4">
          <h2 class="text-sm font-bold text-slate-400 uppercase tracking-wider">Catálogo Verificado</h2>
          <span id="countLabel" class="text-xs font-medium text-slate-400 bg-slate-100 px-2 py-0.5 rounded-md">-- resultados</span>
        </div>

        <!-- Grid (Responsive: 1 col mobile, 2 cols lg) -->
        <div id="suppliersGrid" class="grid grid-cols-1 lg:grid-cols-2 gap-3">
          <!-- Cards injected via JS -->
        </div>

        <!-- Empty State -->
        <div id="emptyState" class="hidden flex-col items-center justify-center py-12 text-center opacity-0">
          <div class="h-20 w-20 bg-slate-100 rounded-full flex items-center justify-center mb-4 text-slate-300">
            <i class="ri-ghost-line text-4xl"></i>
          </div>
          <h3 class="text-slate-900 font-bold text-lg">Nada encontrado</h3>
          <p class="text-slate-500 text-sm max-w-[200px]">Tente buscar por outro termo ou limpe os filtros.</p>
          <button onclick="resetFilters()" class="mt-4 text-orange-600 font-semibold text-sm hover:underline">Limpar filtros</button>
        </div>

      </div>
    </main>

    <!-- BOTTOM NAVIGATION (MOBILE ONLY: md:hidden) -->
    <div class="md:hidden fixed bottom-0 left-0 right-0 z-40 bg-white/95 backdrop-blur-xl border-t border-slate-200/50 pb-safe">
      <div class="max-w-2xl mx-auto w-full px-4 py-2 flex items-end justify-between h-[60px]">

        <!-- Agenda -> Cena-rio -->
        <div class="nav-btn w-14 pb-1" onclick="window.location.href='https://apollo.rio.br/cena-rio/'">
          <i class="ri-calendar-line"></i>
          <span>Agenda</span>
        </div>

        <!-- Pro -> Fornecedores (Active) -->
        <div class="nav-btn active w-14 pb-1" onclick="window.location.href='https://apollo.rio.br/fornecedores'">
          <i class="ri-bar-chart-grouped-line"></i>
          <span>Pro</span>
        </div>

        <!-- Add Button (Floating) -->
        <div class="relative -top-5">
          <button onclick="openModal('addSupplier')" class="h-14 w-14 rounded-full bg-slate-900 text-white flex items-center justify-center shadow-[0_8px_20px_-6px_rgba(15,23,42,0.6)] hover:scale-105 active:scale-95 transition-transform duration-200">
            <i class="ri-add-line text-3xl"></i>
          </button>
        </div>

        <!-- Docs -> Assinar -->
        <div class="nav-btn w-14 pb-1" onclick="window.location.href='https://apollo.rio.br/assinar/index.html'">
          <i class="ri-file-text-line"></i>
          <span>Docs</span>
        </div>

        <!-- Settings (Placeholder) -->
        <div class="nav-btn w-14 pb-1">
          <i class="ri-settings-3-line"></i>
          <span>Ajustes</span>
        </div>

      </div>
    </div>

    <!-- SUPPLIER DETAILS MODAL -->
    <div id="detailModal" class="fixed inset-0 z-50 hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
      <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity opacity-0" id="detailBackdrop"></div>
      <div class="fixed inset-x-0 bottom-0 z-10 w-full max-w-2xl mx-auto overflow-hidden transform transition-all translate-y-full" id="detailPanel">
        <div class="bg-white rounded-t-[32px] shadow-2xl overflow-hidden h-[85vh] flex flex-col relative">

          <!-- Drag Handle -->
          <div class="w-full h-6 flex items-center justify-center absolute top-0 z-20 bg-gradient-to-b from-black/5 to-transparent pointer-events-none">
            <div class="w-12 h-1.5 bg-white/50 rounded-full mt-2 shadow-sm"></div>
          </div>

          <!-- Header Image Area -->
          <div class="h-48 bg-slate-900 relative shrink-0">
            <img id="m_banner" src="" class="w-full h-full object-cover opacity-60">
            <button onclick="closeModal()" class="absolute top-4 right-4 h-8 w-8 bg-black/20 backdrop-blur-md rounded-full text-white flex items-center justify-center hover:bg-black/40 transition">
              <i class="ri-close-line text-xl"></i>
            </button>
            <div class="absolute -bottom-10 left-6">
              <div class="h-24 w-24 rounded-2xl border-4 border-white shadow-lg bg-white overflow-hidden">
                <img id="m_logo" src="" class="w-full h-full object-contain p-1">
              </div>
            </div>
            <!-- Category Badge -->
            <div class="absolute bottom-4 right-4 flex gap-2">
              <span id="m_price" class="px-3 py-1 rounded-full bg-black/40 backdrop-blur-md border border-white/20 text-white text-xs font-bold tracking-wide shadow-sm">
                $$$
              </span>
              <span id="m_category" class="px-3 py-1 rounded-full bg-white/20 backdrop-blur-md border border-white/30 text-white text-xs font-bold uppercase tracking-wide shadow-sm">
                Audio
              </span>
            </div>
          </div>

          <!-- Body Content -->
          <div class="px-6 pt-12 pb-6 overflow-y-auto flex-1 bg-white">
            <div class="flex items-start justify-between mb-1">
              <h2 id="m_title" class="text-2xl font-bold text-slate-900 leading-tight">Supplier Name</h2>
              <div class="flex items-center gap-1 text-green-600 bg-green-50 px-2 py-1 rounded-md text-[11px] font-bold uppercase tracking-wider">
                <i class="ri-check-double-line"></i> Verificado
              </div>
            </div>

            <div class="flex items-center gap-3 mb-4">
              <div class="flex items-center text-yellow-400 text-lg" id="m_stars_container">
                <!-- stars injected -->
              </div>
              <span id="m_rating_text" class="text-slate-500 text-sm font-semibold">4.9/5</span>
            </div>

            <div id="m_tags" class="flex flex-wrap gap-2 mb-6">
              <!-- tags injected -->
            </div>

            <div class="mb-6">
              <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider mb-2">Sobre o Fornecedor</h3>
              <p id="m_desc" class="text-slate-600 text-[15px] leading-relaxed">
                Description.
              </p>
            </div>

            <div class="grid grid-cols-2 gap-3 mb-6">
              <div class="p-3 bg-slate-50 rounded-xl border border-slate-100">
                <div class="text-slate-400 text-xs font-semibold uppercase mb-1">Eventos</div>
                <div class="text-slate-900 font-bold text-lg flex items-center gap-1">
                  <i class="ri-calendar-check-line text-orange-500"></i> +150
                </div>
              </div>
              <div class="p-3 bg-slate-50 rounded-xl border border-slate-100">
                <div class="text-slate-400 text-xs font-semibold uppercase mb-1">Satisfação</div>
                <div class="text-slate-900 font-bold text-lg flex items-center gap-1">
                  <i class="ri-thumb-up-line text-blue-500"></i> 98%
                </div>
              </div>
            </div>

            <!-- Gallery -->
            <div class="mb-24">
              <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider mb-3">Portfólio Recente</h3>
              <div class="flex gap-3 overflow-x-auto hide-scroll pb-2 -mx-6 px-6">
                <div class="h-32 w-48 shrink-0 rounded-lg bg-slate-200 overflow-hidden">
                  <img src="https://images.unsplash.com/photo-1470225620780-dba8ba36b745?q=80&w=400&auto=format&fit=crop" class="h-full w-full object-cover">
                </div>
                <div class="h-32 w-48 shrink-0 rounded-lg bg-slate-200 overflow-hidden">
                  <img src="https://images.unsplash.com/photo-1533174072545-e8d4aa97edf9?q=80&w=400&auto=format&fit=crop" class="h-full w-full object-cover">
                </div>
                <div class="h-32 w-48 shrink-0 rounded-lg bg-slate-200 overflow-hidden">
                  <img src="https://images.unsplash.com/photo-1514525253440-b393452e233e?q=80&w=400&auto=format&fit=crop" class="h-full w-full object-cover">
                </div>
              </div>
            </div>
          </div>

          <div class="absolute bottom-0 left-0 right-0 p-4 bg-white border-t border-slate-100 flex gap-3">
            <button class="flex-1 bg-slate-900 text-white font-bold py-3.5 rounded-xl shadow-lg shadow-slate-900/20 active:scale-95 transition-transform flex items-center justify-center gap-2">
              <i class="ri-whatsapp-line text-xl"></i>
              Orçamento Rápido
            </button>
            <button class="h-14 w-14 flex items-center justify-center rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50 active:scale-95 transition-transform">
              <i class="ri-share-forward-line text-xl"></i>
            </button>
          </div>

        </div>
      </div>
    </div>

  </div>

  <!-- RIGHT SIDEBAR (DESKTOP) - READ-ONLY CALENDAR + EVENTS OVERVIEW -->
  <aside class="hidden lg:flex flex-col w-[360px] border-l border-slate-200 bg-white h-full overflow-y-auto">
    <div class="flex-1 p-4 space-y-4">

      <!-- MINI CALENDAR (OVERVIEW ONLY, NO ADD) -->
      <div class="mini-card">
        <div class="mini-card-title">
          <span>Calendário da Cena</span>
          <span class="text-[10px] px-2 py-0.5 rounded-full bg-slate-100 text-slate-500 font-semibold">
            Visão geral
          </span>
        </div>
        <div id="mini-month-label" class="mini-month-label">Mês</div>

        <div class="mini-weekdays">
          <span>Dom</span><span>Seg</span><span>Ter</span>
          <span>Qua</span><span>Qui</span><span>Sex</span><span>Sáb</span>
        </div>

        <div id="mini-calendar-grid" class="mini-calendar-grid">
          <!-- days injected via JS -->
        </div>

        <div class="mini-legend">
          <div class="mini-legend-item">
            <span class="mini-legend-dot"></span>
            <span>Previsto</span>
          </div>
          <div class="mini-legend-item">
            <span class="mini-legend-dot confirmed"></span>
            <span>Confirmado</span>
          </div>
          <div class="mini-legend-item">
            <span class="mini-legend-dot published"></span>
            <span>Público</span>
          </div>
        </div>
      </div>

      <!-- MINI EVENTS (OVERVIEW ONLY, NO ACTIONS) -->
      <div class="mini-card">
        <div class="mini-card-title">
          <span>Eventos deste mês</span>
          <span id="mini-events-count" class="text-[10px] text-slate-500 font-medium">–</span>
        </div>

        <div id="mini-events-list" class="mini-events-list">
          <!-- events injected via JS -->
        </div>
      </div>

    </div>
  </aside>

  <!-- GLOBAL SCRIPTS -->
  <script>
    // --- DATA SUPPLIERS ---
    const suppliers = [
      {
        id: 1,
        name: "Loudness Som",
        type: "sound",
        categoryDisplay: "Audio e PA",
        tags: ["Funktion-One", "Raves", "Tecnicos"],
        desc: "Especialistas em sonorização de grande porte para festivais eletrônicos e raves. Representantes oficiais Funktion-One no Rio.",
        logo: "https://ui-avatars.com/api/?name=Loudness&background=0f172a&color=fff&size=128",
        banner: "https://images.unsplash.com/photo-1520166012956-add9ba083599?q=80&w=800&auto=format&fit=crop",
        verified: true,
        price: 3,
        rating: 4.9
      },
      {
        id: 2,
        name: "Spectra Visuals",
        type: "visuals",
        categoryDisplay: "Cenografia",
        tags: ["Video Mapping", "VJ", "Palcos 3D"],
        desc: "Criação de experiências visuais imersivas. Palcos com LED, projeção mapeada em prédios históricos e VJs residentes.",
        logo: "https://ui-avatars.com/api/?name=Spectra&background=7c3aed&color=fff&size=128",
        banner: "https://images.unsplash.com/photo-1550989460-0adf9ea622e2?q=80&w=800&auto=format&fit=crop",
        verified: true,
        price: 3,
        rating: 5.0
      },
      {
        id: 3,
        name: "Guerreiros Security",
        type: "security",
        categoryDisplay: "Seguranca",
        tags: ["Controle de Acesso", "Vigilancia", "Staff"],
        desc: "Equipe treinada para lidar com grandes multidões em eventos noturnos. Foco em redução de danos e cordialidade.",
        logo: "https://ui-avatars.com/api/?name=GS&background=1e293b&color=fff&size=128",
        banner: "https://images.unsplash.com/photo-1555699898-19d298099684?q=80&w=800&auto=format&fit=crop",
        verified: true,
        price: 2,
        rating: 4.7
      },
      {
        id: 4,
        name: "DrinkLab Rio",
        type: "bar",
        categoryDisplay: "Bar e Bebidas",
        tags: ["Cocktails", "Bar Operation", "Logistica"],
        desc: "Operação completa de bar para festas. Do gelo ao drink final. Equipe de bartenders performáticos e gestão de estoque.",
        logo: "https://ui-avatars.com/api/?name=DrinkLab&background=f59e0b&color=fff&size=128",
        banner: "https://images.unsplash.com/photo-1514362545857-3bc16c4c7d1b?q=80&w=800&auto=format&fit=crop",
        verified: true,
        price: 2,
        rating: 4.8
      },
      {
        id: 5,
        name: "LedStar Iluminação",
        type: "light",
        categoryDisplay: "Iluminacao Cenica",
        tags: ["Moving Heads", "Lasers", "DMX"],
        desc: "Locação de equipamentos de iluminação de ponta. Lasers de alta potência e design de luz para pistas de dança.",
        logo: "https://ui-avatars.com/api/?name=LedStar&background=0ea5e9&color=fff&size=128",
        banner: "https://images.unsplash.com/photo-1492684223066-81342ee5ff30?q=80&w=800&auto=format&fit=crop",
        verified: true,
        price: 2,
        rating: 4.9
      },
      {
        id: 6,
        name: "Staff Pro Eventos",
        type: "staff",
        categoryDisplay: "Recepcao e Apoio",
        tags: ["Hostess", "Limpeza", "Producao"],
        desc: "Mão de obra qualificada para o seu evento. Recepcionistas bilíngues, equipe de limpeza pós-evento e assistentes de produção.",
        logo: "https://ui-avatars.com/api/?name=StaffPro&background=ec4899&color=fff&size=128",
        banner: "https://images.unsplash.com/photo-1511578314322-379afb476865?q=80&w=800&auto=format&fit=crop",
        verified: false,
        price: 1,
        rating: 4.5
      },
      {
        id: 7,
        name: "Heavy Bass Audio",
        type: "sound",
        categoryDisplay: "Audio e PA",
        tags: ["Subwoofers", "Line Array", "Monitoramento"],
        desc: "Sistemas de som focados em frequências graves. Ideal para Dubstep, Techno e Drum & Bass.",
        logo: "https://ui-avatars.com/api/?name=HB&background=334155&color=fff&size=128",
        banner: "https://images.unsplash.com/photo-1470225620780-dba8ba36b745?q=80&w=800&auto=format&fit=crop",
        verified: true,
        price: 2,
        rating: 4.8
      },
      {
        id: 8,
        name: "Flash Mob VJs",
        type: "visuals",
        categoryDisplay: "Visuals",
        tags: ["Live VJ", "Conteudo 3D"],
        desc: "Coletivo de VJs para criar a atmosfera perfeita em tempo real. Sincronia total com o DJ.",
        logo: "https://ui-avatars.com/api/?name=FM&background=8b5cf6&color=fff&size=128",
        banner: "https://images.unsplash.com/photo-1550989460-0adf9ea622e2?q=80&w=800&auto=format&fit=crop",
        verified: false,
        price: 1,
        rating: 4.6
      }
    ];

    // --- DOM ELEMENTS SUPPLIERS ---
    const grid = document.getElementById('suppliersGrid');
    const searchInput = document.getElementById('searchInput');
    const countLabel = document.getElementById('countLabel');
    const chips = document.querySelectorAll('.filter-chip');
    const emptyState = document.getElementById('emptyState');

    // --- HELPERS SUPPLIERS ---
    function getPriceHtml(priceLevel) {
      let html = '';
      for (let i = 1; i <= 3; i++) {
        if (i <= priceLevel) {
          html += '<span class="text-slate-700 font-bold">$</span>';
        } else {
          html += '<span class="text-slate-300 font-normal">$</span>';
        }
      }
      return html;
    }

    function getStarsHtml(rating) {
      return `<i class="ri-star-fill text-yellow-400 text-xs mr-0.5"></i><span class="text-xs font-bold text-slate-700">${rating}</span>`;
    }

    // --- RENDER SUPPLIERS ---
    function renderCards(data) {
      grid.innerHTML = '';

      if (data.length === 0) {
        emptyState.classList.remove('hidden');
        emptyState.classList.add('flex');

        if (window.Motion) {
          window.Motion.animate(emptyState, { opacity: [0, 1], y: [10, 0] }, { duration: 0.3 });
        }

        countLabel.innerText = "0 resultados";
        return;
      }

      emptyState.classList.add('hidden');
      emptyState.classList.remove('flex');
      countLabel.innerText = `${data.length} resultados`;

      data.forEach((supplier, index) => {
        const card = document.createElement('div');
        card.className = "bg-white rounded-2xl p-4 shadow-sm border border-slate-100 flex items-start gap-4 active:scale-[0.98] transition-transform cursor-pointer overflow-hidden relative group";
        card.onclick = () => openDetails(supplier);

        const tagsHtml = supplier.tags.slice(0, 2).map(t =>
          `<span class="text-[10px] font-semibold text-slate-500 bg-slate-100 px-2 py-0.5 rounded-md uppercase tracking-wider">${t}</span>`
        ).join('');

        const priceHtml = getPriceHtml(supplier.price);
        const starsHtml = getStarsHtml(supplier.rating);

        card.innerHTML = `
          <div class="h-16 w-16 shrink-0 rounded-xl bg-slate-50 border border-slate-100 overflow-hidden relative">
            <img src="${supplier.logo}" class="h-full w-full object-cover" alt="${supplier.name}">
            ${supplier.verified ? '<div class="absolute bottom-0 right-0 bg-green-500 h-4 w-4 rounded-tl-lg flex items-center justify-center"><i class="ri-check-line text-white text-[10px]"></i></div>' : ''}
          </div>

          <div class="flex-1 min-w-0">
            <div class="flex justify-between items-start">
              <div>
                <h3 class="text-[15px] font-bold text-slate-900 leading-tight mb-0.5 truncate">${supplier.name}</h3>
                <div class="flex items-center gap-2 mb-1.5">
                  <span class="text-[11px] font-medium text-orange-600 uppercase tracking-wide truncate">${supplier.categoryDisplay}</span>
                  <span class="text-[8px] text-slate-300">●</span>
                  <div class="flex items-center">${starsHtml}</div>
                  <span class="text-[8px] text-slate-300">●</span>
                  <div class="flex items-baseline text-[11px]">${priceHtml}</div>
                </div>
              </div>
              <div class="h-8 w-8 rounded-full bg-slate-50 flex items-center justify-center text-slate-400 group-hover:bg-orange-50 group-hover:text-orange-500 transition-colors">
                <i class="ri-arrow-right-s-line text-lg"></i>
              </div>
            </div>

            <div class="flex flex-wrap gap-1.5 mb-2">
              ${tagsHtml}
            </div>

            <p class="text-[12px] text-slate-400 line-clamp-1 leading-relaxed">
              ${supplier.desc}
            </p>
          </div>

          <div class="absolute top-0 right-0 p-1">
            <div class="h-1.5 w-1.5 rounded-full bg-slate-200"></div>
          </div>
        `;

        grid.appendChild(card);

        if (window.Motion) {
          window.Motion.animate(
            card,
            { opacity: [0, 1], y: [20, 0] },
            { duration: 0.3, delay: index * 0.05, easing: "ease-out" }
          );
        }
      });
    }

    // --- FILTER LOGIC SUPPLIERS ---
    let activeCategory = 'all';
    let searchTerm = '';

    function filterData() {
      const filtered = suppliers.filter(s => {
        const matchesCat = activeCategory === 'all' || s.type === activeCategory;
        const matchesSearch = s.name.toLowerCase().includes(searchTerm) ||
          s.tags.some(t => t.toLowerCase().includes(searchTerm)) ||
          s.categoryDisplay.toLowerCase().includes(searchTerm);
        return matchesCat && matchesSearch;
      });
      renderCards(filtered);
    }

    chips.forEach(chip => {
      chip.addEventListener('click', () => {
        chips.forEach(c => c.classList.remove('active'));
        chip.classList.add('active');
        activeCategory = chip.dataset.cat;
        filterData();
      });
    });

    searchInput.addEventListener('input', (e) => {
      searchTerm = e.target.value.toLowerCase();
      filterData();
    });

    function resetFilters() {
      activeCategory = 'all';
      searchTerm = '';
      searchInput.value = '';
      chips.forEach(c => c.classList.remove('active'));
      chips[0].classList.add('active');
      filterData();
    }

    // --- MODAL SUPPLIERS ---
    const modal = document.getElementById('detailModal');
    const backdrop = document.getElementById('detailBackdrop');
    const panel = document.getElementById('detailPanel');

    const m_title = document.getElementById('m_title');
    const m_desc = document.getElementById('m_desc');
    const m_logo = document.getElementById('m_logo');
    const m_banner = document.getElementById('m_banner');
    const m_tags = document.getElementById('m_tags');
    const m_category = document.getElementById('m_category');
    const m_price = document.getElementById('m_price');
    const m_stars_container = document.getElementById('m_stars_container');
    const m_rating_text = document.getElementById('m_rating_text');

    function openDetails(supplier) {
      m_title.innerText = supplier.name;
      m_desc.innerText = supplier.desc + " Atendemos em toda a região metropolitana do Rio de Janeiro com logística própria. Equipamentos revisados e equipe técnica incluída.";
      m_logo.src = supplier.logo;
      m_banner.src = supplier.banner;
      m_category.innerText = supplier.categoryDisplay;

      let priceText = "";
      if (supplier.price === 1) priceText = "$ Econômico";
      if (supplier.price === 2) priceText = "$$ Moderado";
      if (supplier.price === 3) priceText = "$$$ Premium";
      m_price.innerText = priceText;

      m_stars_container.innerHTML = "";
      for (let i = 1; i <= 5; i++) {
        const starClass = i <= Math.round(supplier.rating) ? "ri-star-fill" : "ri-star-line";
        m_stars_container.innerHTML += `<i class="${starClass}"></i>`;
      }
      m_rating_text.innerText = supplier.rating + "/5";

      m_tags.innerHTML = supplier.tags.map(t =>
        `<span class="px-3 py-1 rounded-lg bg-slate-100 text-slate-600 text-xs font-semibold uppercase tracking-wider border border-slate-200">${t}</span>`
      ).join('');

      modal.classList.remove('hidden');

      requestAnimationFrame(() => {
        backdrop.classList.remove('opacity-0');
        panel.classList.remove('translate-y-full');
      });

      document.body.style.overflow = 'hidden';
    }

    function closeModal() {
      backdrop.classList.add('opacity-0');
      panel.classList.add('translate-y-full');

      setTimeout(() => {
        modal.classList.add('hidden');
        document.body.style.overflow = '';
      }, 300);
    }

    backdrop.addEventListener('click', closeModal);

    window.openModal = function(type) {
      if (type === 'addSupplier') {
        alert("Fluxo de cadastro de fornecedor (Admin Only)");
      }
    }

    document.addEventListener('DOMContentLoaded', () => {
      renderCards(suppliers);
    });
  </script>

  <!-- MINI CALENDAR + EVENTS OVERVIEW SCRIPT (READ-ONLY) -->
  <script>
    const MINI_STORAGE_KEY = 'cenario_events_v3';
    const MINI_DEFAULT_EVENTS = {
      "2025-11-09": [
        { id: "e1", title: "Dismantle · Puro Suco do Caos", venue: "Copacabana", time: "22:00 · sáb", tag: "Techno/House", status: "expected" },
        { id: "e2", title: "After Lovers (pós-Dismantle)", venue: "Botafogo", time: "04:30 · dom", tag: "After/Groove", status: "expected" }
      ],
      "2025-11-16": [
        { id: "e3", title: "Festival Miscelanea :: RJ", venue: "Zona Portuária", time: "18:00 · sáb", tag: "Festival", status: "confirmed" }
      ],
      "2025-11-23": [
        { id: "e4", title: "Cena::rio · Encontro Produtores", venue: "Centro", time: "15:00 · sáb", tag: "Encontro", status: "expected" }
      ]
    };

    function loadMiniEvents() {
      try {
        const raw = localStorage.getItem(MINI_STORAGE_KEY);
        if (!raw) return JSON.parse(JSON.stringify(MINI_DEFAULT_EVENTS));
        return JSON.parse(raw);
      } catch (e) {
        return JSON.parse(JSON.stringify(MINI_DEFAULT_EVENTS));
      }
    }

    const miniEventsObj = loadMiniEvents();

    const miniMonthLabelEl = document.getElementById('mini-month-label');
    const miniCalendarGridEl = document.getElementById('mini-calendar-grid');
    const miniEventsListEl = document.getElementById('mini-events-list');
    const miniEventsCountEl = document.getElementById('mini-events-count');

    const weekDaysMini = ["Dom","Seg","Ter","Qua","Qui","Sex","Sáb"];
    const monthsMini = ["jan","fev","mar","abr","mai","jun","jul","ago","set","out","nov","dez"];

    function pad2(n) { return String(n).padStart(2,'0'); }
    function isoDateMini(y,m,d) { return `${y}-${pad2(m)}-${pad2(d)}`; }
    function daysInMonthMini(year, monthIndex){ return new Date(year, monthIndex + 1, 0).getDate(); }
    function firstWeekdayOfMonthMini(year, monthIndex){ return new Date(year, monthIndex, 1).getDay(); }

    const todayMini = new Date();
    let miniYear = todayMini.getFullYear();
    let miniMonth = todayMini.getMonth(); // 0-based

    function renderMiniCalendar() {
      if (!miniCalendarGridEl) return;
      miniCalendarGridEl.innerHTML = '';

      const totalDays = daysInMonthMini(miniYear, miniMonth);
      const firstWeekday = firstWeekdayOfMonthMini(miniYear, miniMonth);

      miniMonthLabelEl.textContent = `${monthsMini[miniMonth].toUpperCase()} ${miniYear}`;

      const prevMonthIndex = miniMonth - 1 < 0 ? 11 : miniMonth - 1;
      const prevMonthYear = miniMonth - 1 < 0 ? miniYear - 1 : miniYear;
      const prevMonthDays = daysInMonthMini(prevMonthYear, prevMonthIndex);
      const leadingCount = firstWeekday;

      // Leading blanks
      for (let i = leadingCount - 1; i >= 0; i--) {
        const span = document.createElement('span');
        span.className = 'mini-day';
        span.textContent = '';
        miniCalendarGridEl.appendChild(span);
      }

      // Current month days
      for (let d = 1; d <= totalDays; d++) {
        const iso = isoDateMini(miniYear, miniMonth + 1, d);
        const dayEvents = miniEventsObj[iso] || [];
        const dayEl = document.createElement('div');
        dayEl.className = 'mini-day';
        dayEl.textContent = d;

        if (dayEvents.length > 0) {
          dayEl.classList.add('has-events');
          const dot = document.createElement('span');
          dot.className = 'mini-day-dot';

          // If any confirmed/published event exists, color accordingly
          const hasConfirmed = dayEvents.some(ev => ev.status === 'confirmed');
          const hasPublished = dayEvents.some(ev => ev.status === 'published');

          if (hasPublished) dot.classList.add('published');
          else if (hasConfirmed) dot.classList.add('confirmed');

          dayEl.appendChild(dot);
        }

        miniCalendarGridEl.appendChild(dayEl);
      }

      // Trailing blanks to complete grid line
      const totalCells = leadingCount + totalDays;
      const trailing = (7 - (totalCells % 7)) % 7;
      for (let t = 0; t < trailing; t++) {
        const span = document.createElement('span');
        span.className = 'mini-day';
        span.textContent = '';
        miniCalendarGridEl.appendChild(span);
      }
    }

    function renderMiniEvents() {
      if (!miniEventsListEl) return;
      miniEventsListEl.innerHTML = '';

      const items = [];
      Object.keys(miniEventsObj).forEach(dateKey => {
        (miniEventsObj[dateKey] || []).forEach(ev => {
          items.push(Object.assign({}, ev, { dateKey }));
        });
      });

      // Filter events for current month/year
      const toRender = items.filter(ev => {
        const d = new Date(ev.dateKey + 'T12:00:00');
        return d.getFullYear() === miniYear && d.getMonth() === miniMonth;
      });

      // Sort by date + status (confirmed first)
      toRender.sort((a,b) => {
        if (a.dateKey === b.dateKey) {
          if (a.status === b.status) return 0;
          return a.status === 'confirmed' ? -1 : 1;
        }
        return a.dateKey < b.dateKey ? -1 : 1;
      });

      miniEventsCountEl.textContent = toRender.length ? `${toRender.length} eventos` : 'Sem eventos';

      if (toRender.length === 0) {
        const emptyEl = document.createElement('div');
        emptyEl.className = 'text-[0.7rem] text-slate-400 mt-2';
        emptyEl.textContent = 'Nenhum evento cadastrado para este mês.';
        miniEventsListEl.appendChild(emptyEl);
        return;
      }

      toRender.forEach(ev => {
        const wrap = document.createElement('div');
        wrap.className = 'mini-event-item';

        const titleRow = document.createElement('div');
        titleRow.className = 'mini-event-title';

        const titleSpan = document.createElement('span');
        titleSpan.textContent = ev.title;

        const statusSpan = document.createElement('span');
        statusSpan.className = 'mini-event-chip';
        statusSpan.textContent = ev.status === 'confirmed' ? 'Confirmado' : (ev.status === 'published' ? 'Público' : 'Previsto');

        titleRow.appendChild(titleSpan);
        titleRow.appendChild(statusSpan);

        const metaRow = document.createElement('div');
        metaRow.className = 'mini-event-meta';
        const dateLabel = `${ev.dateKey.slice(8,10)}/${ev.dateKey.slice(5,7)}`;
        const venue = ev.venue || '';
        const time = ev.time || '';

        const dateSpan = document.createElement('span');
        dateSpan.textContent = dateLabel;

        const dotSpan = document.createElement('span');
        dotSpan.textContent = '•';
        dotSpan.style.opacity = '0.4';

        const venueSpan = document.createElement('span');
        venueSpan.textContent = venue;

        const timeSpan = document.createElement('span');
        timeSpan.textContent = time;

        metaRow.appendChild(dateSpan);
        if (venue) {
          metaRow.appendChild(dotSpan.cloneNode(true));
          metaRow.appendChild(venueSpan);
        }
        if (time) {
          metaRow.appendChild(dotSpan.cloneNode(true));
          metaRow.appendChild(timeSpan);
        }

        if (ev.tag) {
          const tagChip = document.createElement('span');
          tagChip.className = 'mini-event-chip';
          tagChip.textContent = ev.tag;
          metaRow.appendChild(tagChip);
        }

        wrap.appendChild(titleRow);
        wrap.appendChild(metaRow);
        miniEventsListEl.appendChild(wrap);
      });
    }

    // INIT MINI
    renderMiniCalendar();
    renderMiniEvents();
  </script>

</body>
</html>
