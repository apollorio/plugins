<!DOCTYPE html>
<html lang="pt-BR" class="h-full w-full bg-slate-50 antialiased selection:bg-neutral-500 selection:text-white">
<head>
  <meta charset="UTF-8" />
  <title>Apollo :: Feed Social</title>
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=0" />
  
  <!-- Tailwind CSS -->
  <script src="https://cdn.tailwindcss.com"></script>
  
 <script src="https://assets.apollo.rio.br/base.js"></script>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          fontFamily: {
            sans: ['Inter', 'sans-serif'],
          },
          colors: {
            slate: {
              850: '#1e293b', // Custom darker slate
            }
          }
        }
      }
    }
  </script>

  <style>
    
    /* CSS Document */
/* ==========================================================================
           1. ROOT VARIABLES & THEME SETUP
========================================================================== */
:root {
	--font-primary:             "Urbanist", system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Oxygen, Ubuntu, Cantarell, 'Helvetica Neue', sans-serif;
	--text-size:                14.75px!important;
	--text-regular:             12.75px!important;
	--text-small:               10.75px!important;
	--text-smaller:             7.75px!important;
	
	--radius-sec:               20px;
	--radius-main:              12px;
    --radius-card:              16px;
    
	--transition-main:          all 0.75s cubic-bezier(0.25, 0.8, 0.25, 1);
	--transition-two:           all 1.75s cubic-bezier(0.25, 0.8, 0.25, 1);
	--transition:               all 2.50s cubic-bezier(0.25, 0.8, 0.25, 1);
	
	--fly-border:               rgba(148, 163, 184, 0.32);
    --fly-shadow:               0 20px 50px rgba(15, 23, 42, 0.05);
    --fly-muted:                #6b7280;
    --fly-strong:               #020617;
	 
	 
	/* Light Mode Palette */
	--bg-main:                  #fff;
	--bg-main-translucent:      rgba(255, 255, 255, .68);
	--bg-surface:               #f5f5f5;
	--header-blur-bg:           linear-gradient(to bottom, rgb(253 253 253 / .35) 0%, rgb(253 253 253 / .1) 50%, #fff0 100%);
	
	--text-secondary:           var(--text-main);
	--text-main:                rgba(19, 21, 23, .6);
	--text-primary:             rgba(19, 21, 23, .85);
	--text-zero:                rgba(19, 21, 23, 1);
	
	--text-dif:                 #f9f9f9fa!important;
	--border-color:             #e0e2e4;
	--border-color-2:           #e0e2e454;
	--card-border-light:        rgba(0, 0, 0, 0.05);
	--card-shadow-light:        rgba(0, 0, 0, 0.025);
	
	--glass-radius:             var(--radius-sec);
    --glass-blur:               var(--radius-main);
    --glass-border-thick:       1px;
    --glass-border-color1:      rgba(230, 230, 230, 0.4);
    --glass-border-color2:      rgba(230, 230, 230, 0.1);
    --glass-color:              rgba(255, 255, 255, 0.15);

	--accent-color:             #FFA17F;
	--vermelho-light:           #fe786d;
	--vermelho:                 #fd5c02;
	--laranja:                  #FFA17F;
	
  --orange-0:    #FFF6F0;  /* quase branco com toque laranja */
  --orange-100:  #FFE1CC;
  --orange-200:  #FFC299;
  --orange-300:  #FFA066;
  --orange-400:  #FF8640;
  --orange-500:  #FF6925;  /* base */
  --orange-600:  #E55A1E;
  --orange-700:  #C7491A;
  --orange-800:  #9E3713;
  --orange-900:  #70250D;
  --orange-1000: #1A0C04;  /* preto alaranjado */
}

/* === Backgrounds === */
.bg-orange-0    { background-color: var(--orange-0)!important; }
.bg-orange-100  { background-color: var(--orange-100)!important; }
.bg-orange-200  { background-color: var(--orange-200)!important; }
.bg-orange-300  { background-color: var(--orange-300)!important; }
.bg-orange-400  { background-color: var(--orange-400)!important; }
.bg-orange-500  { background-color: var(--orange-500)!important; }
.bg-orange-600  { background-color: var(--orange-600)!important; }
.bg-orange-700  { background-color: var(--orange-700)!important; }
.bg-orange-800  { background-color: var(--orange-800)!important; }
.bg-orange-900  { background-color: var(--orange-900)!important; }
.bg-orange-1000 { background-color: var(--orange-1000)!important; }

/* === Texts === */
.text-orange-0    { color: var(--orange-0)!important; }
.text-orange-100  { color: var(--orange-100)!important; }
.text-orange-200  { color: var(--orange-200)!important; }
.text-orange-300  { color: var(--orange-300)!important; }
.text-orange-400  { color: var(--orange-400)!important; }
.text-orange-500  { color: var(--orange-500)!important; }
.text-orange-600  { color: var(--orange-600)!important; }
.text-orange-700  { color: var(--orange-700)!important; }
.text-orange-800  { color: var(--orange-800)!important; }
.text-orange-900  { color: var(--orange-900)!important; }
.text-orange-1000 { color: var(--orange-1000)!important; }

/* === Borders === */
.border-orange-0    { border-color: var(--orange-0)!important; }
.border-orange-100  { border-color: var(--orange-100)!important; }
.border-orange-200  { border-color: var(--orange-200)!important; }
.border-orange-300  { border-color: var(--orange-300)!important; }
.border-orange-400  { border-color: var(--orange-400)!important; }
.border-orange-500  { border-color: var(--orange-500)!important; }
.border-orange-600  { border-color: var(--orange-600)!important; }
.border-orange-700  { border-color: var(--orange-700)!important; }
.border-orange-800  { border-color: var(--orange-800)!important; }
.border-orange-900  { border-color: var(--orange-900)!important; }
.border-orange-1000 { border-color: var(--orange-1000)!important; }

/* === Group & Hover Variants === */
.group-hover\:border-orange-400:hover,
.group-hover\:card\:border-orange-400:hover { border-color: var(--orange-400)!important; }

.group-hover\:border-orange-500:hover,
.group-hover\:card\:border-orange-500:hover { border-color: var(--orange-500)!important; }

.group-hover\:bg-orange-400:hover,
.group-hover\:card\:bg-orange-400:hover { background-color: var(--orange-400)!important; }

/* === Selection (text highlight) === */
::selection { background-color: var(--orange-400)!important; color: #fff!important; }
}

/* Dark Mode Palette */
body.dark-mode {
	--bg-main:                  #131517;
	--bg-main-translucent:      rgba(19, 21, 23, 0.68);
	--header-blur-bg:           linear-gradient(to bottom, rgb(19 21 23 / .35) 0%, rgb(19 21 23 / .1) 50%, #13151700 100%);
	--text-main:                #ffffff91;
	--text-primary:             #fdfdfdfa;
	--text-secondary:           #ffffff91;
	--text-dif:                 rgba(19, 21, 23, .7) !important;
	--border-color:             #333537;
	--border-color-2:           #e0e2e40a;
	--card-border-light:        rgba(255, 255, 255, 0.1);
	--card-shadow-light:        rgba(0, 0, 0, 0.2);
	--glass-border-color1:      rgba(48, 48, 48, 0.4);
    --glass-border-color2:      rgba(110, 110, 110, 0.1);
    --glass-color:              rgba(0, 0, 0, 0.15);

  --orange-0:       #1A0C04;  /* preto alaranjado */
  --orange-100:     #70250D;
  --orange-200:     #9E3713;
  --orange-300:     #C7491A;
  --orange-400:     #E55A1E;
  --orange-500:     #FF6925;  /* base */
  --orange-600:     #FF8640;
  --orange-700:     #FFA066;
  --orange-800:     #FFC299;
  --orange-900:     #FFE1CC;
  --orange-1000:    #FFF6F0;  /* quase branco com toque laranja */
  --color-white:    #ffffff;
  --color-black:    #000000;
  --color-gray-50:  #6b7280;
  --color-gray-100: #f3f4f6;
  --color-gray-200: #e5e7eb;
  --color-gray-300: #d1d5db;
  --color-gray-800: #1f2937;
  --color-gray-900: #111827;
}
}


/* ==========================================================================
           1.5 GLASSMORPHISM UTILITIES
========================================================================== */
.glass {
    position: relative;
    isolation: isolate;
    border-radius: var(--glass-radius);
    background: var(--glass-color);
    backdrop-filter: blur(var(--glass-blur));

    &::before {
        content: "";
        position: absolute;
        inset: 0;
        border: var(--glass-border-thick) solid transparent;
        background: linear-gradient(
            45deg,
            var(--glass-border-color2),
            var(--glass-border-color1),
            var(--glass-border-color2)
        ) border-box;
        mask: linear-gradient(black, black) border-box,
            linear-gradient(black, black) padding-box;
        mask-composite: subtract;
        border-radius: inherit;
        z-index: -1;
    }
}

.apollo-event-modal-overlay{
    background:transparent!important;
    background-color:transparent!important;
    background:linear-gradient(25deg, #eeeeee40 0%, transparent 100%)!important;
    backdrop-filter:blur(5px)!important;
    z-index:6666!important;
}

.is-open {
    z-index:8888!important;
}

/* ==========================================================================
           2. BASE & RESET STYLES
========================================================================== */
* { 
    -webkit-tap-highlight-color: transparent; corner-shape:squircle;
    box-sizing: border-box; 
    margin: 0; 
    padding: 0; 
}
.text-white,.t-white,.txt-white{color:var(--bg-surface)!important;
    & :hover {color:var(--bg-main)!important;}
}

html,
body {
	color: var(--text-main);
	font-family: var(--font-primary);
	font-size: 14.75px;
	font-weight: 400;
	line-height: 1.1rem;
	letter-spacing: 0.7px;
	background-color: var(--bg-main);
	transition: background-color 1s ease, color 1s ease;
	overflow-x: hidden !important;
	scroll-behavior: smooth;
	-webkit-user-select: none;
	-ms-user-select: none;
	user-select: none;
}
     /* Hide Scrollbar but keep functionality */
    .no-scrollbar::-webkit-scrollbar {
      display: none;
    }
    .no-scrollbar {
      -ms-overflow-style: none;
      scrollbar-width: none;
    }

    /* Mobile Nav padding */
    .pb-safe-area {
      padding-bottom: env(safe-area-inset-bottom, 20px);
    }
  </style>
</head>
<body>

  <!-- TOP HEADER (Sticky) -->
  <header class="sticky top-0 z-40 h-14 flex items-center justify-between border-b border-slate-200 bg-white/90 backdrop-blur-md px-4 md:px-6">
    <div class="flex items-center gap-3">
     <div class="h-9 w-9 rounded-[4px] bg-gradient-to-br from-orange-400 to-orange-600 flex items-center justify-center">
            <i class="ri-slack-fill text-white text-[28px]"></i>
          </div>
      <div class="flex flex-col leading-none">
        <h1 class="text-[16px] font-extrabold mt-2 text-slate-900"><span id="user_ID_FEED">Valle, <span id="check-time-for-greetings">boa tarde</span>!</h1>
            <p class="text-[13px] text-slate-500">@valle <span class="text-[10px]" id="membershipds">DJ PRODUCER PROMOTER</span></p>
      </div>
    </div>
    
    <!-- Desktop Search & Actions -->
    <div class="hidden md:flex items-center gap-3 text-[12px]">
      <div class="relative group">
        <i class="ri-search-line text-slate-400 absolute left-3 top-1.5 text-xs group-focus-within:text-slate-600"></i>
        <input
          type="text"
          placeholder="Buscar na cena..."
          class="pl-8 pr-3 py-1.5 rounded-full border border-slate-200 bg-slate-50 text-[12px] w-64 focus:outline-none focus:ring-2 focus:ring-slate-900/10 focus:bg-white transition-all"
        />
      </div>
      <div class="h-4 w-px bg-slate-200 mx-1"></div>
      <a href="#" class="font-medium text-slate-600 hover:text-slate-900 transition-colors">Eventos</a>
      <a href="#" class="font-medium text-slate-600 hover:text-slate-900 transition-colors">Comunidades</a>
      
      <!-- User Avatar -->
      <button class="ml-2 inline-flex h-8 w-8 items-center justify-center rounded-full hover:ring-2 hover:ring-slate-200 transition-all">
        <img
          src="https://api.dicebear.com/7.x/avataaars/svg?seed=Valle&backgroundColor=e5e7eb"
          alt="Valle"
          class="h-full w-full rounded-full object-cover"
        />
      </button>
    </div>

    <!-- Mobile Header Actions -->
    <div class="flex md:hidden items-center gap-3">
      <button class="text-slate-500 hover:text-slate-900">
        <i class="ri-search-line text-xl"></i>
      </button>
      <button class="text-slate-500 hover:text-slate-900">
        <i class="ri-notification-3-line text-xl"></i>
      </button>
    </div>
  </header>

  <!-- MAIN CONTENT LAYOUT -->
  <main class="flex justify-center px-0 md:px-6 py-0 md:py-6 pb-24 md:pb-6">
    <div class="w-full max-w-6xl grid grid-cols-1 lg:grid-cols-[minmax(0,2fr)_minmax(0,1fr)] gap-6">
      
      <!-- LEFT COLUMN: FEED STREAM -->
      <div class="space-y-4">
        
        <!-- Feed Controls / Create Post -->
        <section class="bg-white md:rounded-xl border-b md:border border-slate-200 p-4 sticky md:static top-14 z-30">
          <div class="flex items-center gap-3 mb-4">
            <div class="h-10 w-10 rounded-full bg-slate-100 overflow-hidden shrink-0">
               <img src="https://api.dicebear.com/7.x/avataaars/svg?seed=Valle&backgroundColor=e5e7eb" class="h-full w-full" />
            </div>
            <button class="flex-1 text-left bg-slate-50 hover:bg-slate-100 border border-slate-200 text-slate-500 text-[13px] px-4 py-2.5 rounded-full transition-colors">
              No que você está pensando, Valle?
            </button>
          </div>

          <!-- Tabs Filter -->
          <div class="flex items-center justify-between overflow-x-auto no-scrollbar pb-1">
            <div class="flex gap-2" id="feed-tabs">
              <button class="menutag" data-target="all" data-active="true">
                <i class="ri-apps-line"></i> Tudo
              </button>
              <button class="menutag" data-target="events">
                <i class="ri-calendar-event-line"></i> Eventos
              </button>
              <button class="menutag" data-target="community">
                <i class="ri-discuss-line"></i> Comunidades
              </button>
              <button class="menutag" data-target="market">
                <i class="ri-ticket-line"></i> Classificados
              </button>
            </div>
          </div>
        </section>

        <!-- FEED STREAM -->
        <div id="feed-container" class="space-y-4 px-2 md:px-0">

          <!-- POST 1: DISMANTLE (Type: Update + Event Link) -->
          <article class="aprioEXP-card-shell p-4 flex gap-3 feed-item" data-type="events">
            <div class="shrink-0">
              <div class="h-10 w-10 rounded-full overflow-hidden border border-slate-200">
                <img src="https://api.dicebear.com/7.x/identicon/svg?seed=Dismantle" alt="Dismantle" class="h-full w-full object-cover" />
              </div>
            </div>
            <div class="min-w-0 flex-1">
              <!-- Header -->
              <div class="flex justify-between items-start">
                <div>
                  <div class="flex items-center gap-1">
                    <span class="font-bold text-[13px] text-slate-900">Dismantle</span>
                    <i class="ri-verified-badge-fill text-orange-500 text-[12px]"></i>
                  </div>
                  <div class="text-[11px] text-slate-500">@dismantle.rio · 2h</div>
                </div>
                <button class="text-slate-400 hover:text-slate-800"><i class="ri-more-fill"></i></button>
              </div>
              
              <!-- Content -->
              <p class="mt-2 text-[13px] leading-relaxed text-slate-800">
                ⚠️ <span class="font-semibold">Atualização de Logística:</span> O acesso para o evento de hoje será exclusivamente pela entrada lateral da Rua Siqueira Campos. Cheguem cedo para evitar filas grandes no check-in da Apollo.
              </p>

              <!-- Event Attachment -->
              <div class="mt-3 border border-slate-200 rounded-lg overflow-hidden bg-slate-50 flex hover:bg-slate-100 cursor-pointer transition-colors">
                <div class="w-16 bg-slate-900 text-white flex flex-col items-center justify-center p-2 shrink-0">
                  <span class="text-[10px] uppercase font-bold tracking-wider">HOJE</span>
                  <span class="text-xl font-bold leading-none">22</span>
                </div>
                <div class="p-3 flex-1 min-w-0">
                  <h3 class="font-bold text-[13px] truncate">Dismantle · O Caos Urbano</h3>
                  <p class="text-[11px] text-slate-500 flex items-center gap-1 mt-1">
                    <i class="ri-map-pin-line"></i> Bunker Copacabana · 23:00
                  </p>
                </div>
                <div class="flex items-center pr-3">
                  <i class="ri-arrow-right-s-line text-slate-400"></i>
                </div>
              </div>

              <!-- Actions -->
              <div class="mt-3 flex items-center justify-between pt-2 border-t border-slate-100">
                 <div class="flex gap-6">
                   <button class="group flex items-center gap-1.5 text-[12px] text-slate-500 hover:text-pink-600 transition-colors">
                     <i class="ri-heart-3-line text-base group-hover:scale-110 transition-transform"></i> <span>241</span>
                   </button>
                   <button class="group flex items-center gap-1.5 text-[12px] text-slate-500 hover:text-orange-600 transition-colors">
                     <i class="ri-chat-3-line text-base group-hover:scale-110 transition-transform"></i> <span>32</span>
                   </button>
                 </div>
                 <button class="text-slate-400 hover:text-slate-900"><i class="ri-bookmark-line text-base"></i></button>
              </div>
            </div>
          </article>

          <!-- POST 2: USER DISCUSSION (Type: Community) -->
          <article class="aprioEXP-card-shell p-4 flex gap-3 feed-item" data-type="community">
            <div class="shrink-0">
              <div class="h-10 w-10 rounded-full overflow-hidden bg-neutral-100">
                <img src="https://api.dicebear.com/7.x/avataaars/svg?seed=Julia" alt="Julia" class="h-full w-full object-cover" />
              </div>
            </div>
            <div class="min-w-0 flex-1">
               <div class="flex justify-between items-start">
                <div>
                  <div class="flex items-center gap-1">
                    <span class="font-bold text-[13px] text-slate-900">Julia M.</span>
                    <span class="text-[11px] text-slate-400 px-1">em</span>
                    <span class="font-semibold text-[12px] text-neutral-600">After Lovers ZS</span>
                  </div>
                  <div class="text-[11px] text-slate-500">há 4h</div>
                </div>
              </div>
              <p class="mt-2 text-[13px] leading-relaxed text-slate-800">
                Alguém sabe se o after na Barata Ribeiro ainda tá rolando ou a polícia bateu lá? Estamos saindo da Fosfobox agora.
              </p>
              
              <!-- Tags -->
              <div class="mt-3 flex flex-wrap gap-2">
                <span class="px-2 py-1 rounded bg-neutral-50 text-neutral-700 text-[10px] font-medium uppercase tracking-wide">#Copacabana</span>
                <span class="px-2 py-1 rounded bg-neutral-50 text-neutral-700 text-[10px] font-medium uppercase tracking-wide">#Ajuda</span>
              </div>

              <div class="mt-3 flex items-center justify-between pt-2 border-t border-slate-100">
                 <div class="flex gap-6">
                   <button class="group flex items-center gap-1.5 text-[12px] text-slate-500 hover:text-pink-600 transition-colors">
                     <i class="ri-heart-3-line text-base"></i> <span>12</span>
                   </button>
                   <button class="group flex items-center gap-1.5 text-[12px] text-slate-500 hover:text-orange-600 transition-colors">
                     <i class="ri-chat-3-line text-base"></i> <span>8 respostas</span>
                   </button>
                 </div>
              </div>
            </div>
          </article>

          <!-- POST 3: CLASSIFIED (Type: Market) -->
          <article class="aprioEXP-card-shell p-4 flex gap-3 feed-item border-l-4 border-l-emerald-500" data-type="market">
            <div class="shrink-0">
              <div class="h-10 w-10 rounded-full bg-emerald-100 flex items-center justify-center text-emerald-700">
                <i class="ri-ticket-2-fill text-lg"></i>
              </div>
            </div>
            <div class="min-w-0 flex-1">
              <div class="flex justify-between items-start">
                <div>
                  <div class="flex items-center gap-1">
                    <span class="font-bold text-[13px] text-slate-900">Classificados Apollo</span>
                    <span class="bg-emerald-100 text-emerald-800 text-[9px] px-1.5 py-0.5 rounded font-bold uppercase ml-2">Revenda Verificada</span>
                  </div>
                  <div class="text-[11px] text-slate-500">via WPAdverts · há 30min</div>
                </div>
              </div>

              <div class="mt-2 p-3 bg-slate-50 border border-slate-200 rounded-lg">
                <div class="flex justify-between items-start mb-2">
                   <h3 class="font-bold text-slate-800 text-sm">1x Ingresso - Rituals (Lote 2)</h3>
                   <span class="font-bold text-emerald-600 text-sm">R$ 80,00</span>
                </div>
                <p class="text-[12px] text-slate-600 mb-3">Motivo: Doença. Transfiro via shotgun ou QR Code Apollo.</p>
                <button class="w-full py-1.5 bg-slate-900 hover:bg-slate-800 text-white text-[12px] font-medium rounded shadow-sm transition-colors">
                  Entrar em contato com vendedor
                </button>
              </div>
              
              <div class="mt-2 flex items-center gap-2 text-[10px] text-slate-400">
                 <i class="ri-shield-check-line text-emerald-500"></i> Vendedor com reputação alta
              </div>
            </div>
          </article>
          
           <!-- POST 4: TROPICALIS (Type: Event) -->
           <article class="aprioEXP-card-shell p-4 flex gap-3 feed-item" data-type="events">
            <div class="shrink-0">
              <div class="h-10 w-10 rounded-full overflow-hidden bg-gradient-to-br from-yellow-400 to-orange-500 p-0.5">
                <div class="h-full w-full bg-white rounded-full overflow-hidden">
                   <img src="https://api.dicebear.com/7.x/identicon/svg?seed=Tropicalis" class="h-full w-full object-cover" />
                </div>
              </div>
            </div>
            <div class="min-w-0 flex-1">
              <div class="flex justify-between items-start">
                <div>
                  <div class="flex items-center gap-1">
                    <span class="font-bold text-[13px] text-slate-900">Tropicalis :: RJ</span>
                  </div>
                  <div class="text-[11px] text-slate-500">@tropicalis · 5h</div>
                </div>
              </div>
              
              <p class="mt-2 text-[13px] leading-relaxed text-slate-800">
                O guia do fim de semana saiu! Mapeamos 27 festas de eletrônico ativas. Qual vai ser a rota de vocês?
              </p>
              
              <!-- Interactive Poll Mockup -->
              <div class="mt-3 space-y-1.5">
                <div class="relative h-8 w-full bg-slate-100 rounded overflow-hidden flex items-center px-3 group cursor-pointer">
                   <div class="absolute left-0 top-0 bottom-0 bg-slate-200 w-[70%] transition-all group-hover:bg-slate-300"></div>
                   <span class="relative z-10 text-[11px] font-medium text-slate-700 flex justify-between w-full">
                     <span>Zona Sul / Copacabana</span>
                     <span>70%</span>
                   </span>
                </div>
                <div class="relative h-8 w-full bg-slate-100 rounded overflow-hidden flex items-center px-3 group cursor-pointer">
                   <div class="absolute left-0 top-0 bottom-0 bg-slate-200 w-[30%] transition-all group-hover:bg-slate-300"></div>
                   <span class="relative z-10 text-[11px] font-medium text-slate-700 flex justify-between w-full">
                     <span>Centro / Porto</span>
                     <span>30%</span>
                   </span>
                </div>
              </div>

              <div class="mt-3 flex items-center justify-between pt-2 border-t border-slate-100">
                 <div class="flex gap-6">
                   <button class="group flex items-center gap-1.5 text-[12px] text-slate-500 hover:text-pink-600 transition-colors">
                     <i class="ri-heart-3-line text-base"></i> <span>892</span>
                   </button>
                   <button class="group flex items-center gap-1.5 text-[12px] text-slate-500 hover:text-orange-600 transition-colors">
                     <i class="ri-chat-3-line text-base"></i> <span>140</span>
                   </button>
                 </div>
              </div>
            </div>
          </article>

          <!-- Loading Indicator -->
          <div class="py-6 flex justify-center">
            <div class="flex items-center gap-2 text-slate-400 text-[12px]">
               <i class="ri-loader-4-line animate-spin"></i> Carregando mais caos carioca...
            </div>
          </div>

        </div>
      </div>

      <!-- RIGHT COLUMN: SIDEBAR (Sticky) -->
      <aside class="hidden lg:block space-y-4">
        
        <!-- Calendar Widget -->
        <div class="aprioEXP-card-shell p-4">
          <div class="flex items-center justify-between mb-3">
            <h2 class="text-[12px] font-bold uppercase tracking-wider text-slate-500">Próximos 7 dias</h2>
            <a href="#" class="text-[11px] text-neutral-600 hover:underline">Ver agenda</a>
          </div>
          <div class="space-y-2">
            <a href="#" class="flex gap-3 items-center p-2 hover:bg-slate-50 rounded-lg transition-colors group">
              <div class="h-10 w-10 rounded-lg border border-slate-200 flex flex-col items-center justify-center bg-white text-slate-900 group-hover:border-slate-400 transition-colors">
                <span class="text-[9px] uppercase font-bold">SEX</span>
                <span class="text-[13px] font-bold">22</span>
              </div>
              <div class="min-w-0">
                <div class="text-[12px] font-bold truncate">Dismantle Mainstage</div>
                <div class="text-[10px] text-slate-500">14 amigos vão</div>
              </div>
            </a>
            <a href="#" class="flex gap-3 items-center p-2 hover:bg-slate-50 rounded-lg transition-colors group">
              <div class="h-10 w-10 rounded-lg border border-slate-200 flex flex-col items-center justify-center bg-white text-slate-900 group-hover:border-slate-400 transition-colors">
                <span class="text-[9px] uppercase font-bold">SÁB</span>
                <span class="text-[13px] font-bold">23</span>
              </div>
              <div class="min-w-0">
                <div class="text-[12px] font-bold truncate">Gop Tun Showcase</div>
                <div class="text-[10px] text-slate-500">32 amigos vão</div>
              </div>
            </a>
          </div>
        </div>

        <!-- Trending Communities -->
        <div class="aprioEXP-card-shell p-4">
           <h2 class="text-[12px] font-bold uppercase tracking-wider text-slate-500 mb-3">Comunidades em alta</h2>
           <div class="space-y-3">
             <div class="flex items-center gap-3">
               <div class="h-8 w-8 rounded bg-purple-100 text-purple-600 flex items-center justify-center"><i class="ri-group-line"></i></div>
               <div class="flex-1">
                 <div class="text-[12px] font-bold">After Lovers ZS</div>
                 <div class="text-[10px] text-slate-500">+120 msg hoje</div>
               </div>
               <button class="text-[11px] font-semibold text-neutral-600">Entrar</button>
             </div>
             <div class="flex items-center gap-3">
               <div class="h-8 w-8 rounded bg-pink-100 text-pink-600 flex items-center justify-center"><i class="ri-music-2-line"></i></div>
               <div class="flex-1">
                 <div class="text-[12px] font-bold">Produtores RJ</div>
                 <div class="text-[10px] text-slate-500">Troca de samples</div>
               </div>
               <button class="text-[11px] font-semibold text-neutral-600">Entrar</button>
             </div>
           </div>
        </div>

        <!-- Footer Links -->
        <div class="flex flex-wrap gap-2 text-[10px] text-slate-400 px-2">
          <a href="#" class="hover:underline">Privacidade</a>
          <span>·</span>
          <a href="#" class="hover:underline">Termos</a>
          <span>·</span>
          <a href="#" class="hover:underline">Apollo Business</a>
          <span>·</span>
          <span>© 2024 Apollo Rio</span>
        </div>

      </aside>
    </div>
  </main>

  <!-- MOBILE BOTTOM NAV (Fixed) -->
  <nav class="md:hidden fixed bottom-0 left-0 right-0 bg-white border-t border-slate-200 pb-safe-area z-50">
    <div class="grid grid-cols-5 h-14">
      <button class="flex flex-col items-center justify-center text-slate-900 gap-1">
        <i class="ri-home-5-fill text-xl"></i>
      </button>
      <button class="flex flex-col items-center justify-center text-slate-400 hover:text-slate-600 gap-1">
        <i class="ri-search-2-line text-xl"></i>
      </button>
      <button class="flex flex-col items-center justify-center text-slate-900 gap-1 -mt-6">
        <div class="h-12 w-12 bg-slate-900 text-white rounded-full flex items-center justify-center shadow-lg shadow-slate-900/20">
          <i class="ri-add-line text-2xl"></i>
        </div>
      </button>
      <button class="flex flex-col items-center justify-center text-slate-400 hover:text-slate-600 gap-1">
        <i class="ri-calendar-event-line text-xl"></i>
      </button>
      <button class="flex flex-col items-center justify-center text-slate-400 hover:text-slate-600 gap-1">
        <i class="ri-user-3-line text-xl"></i>
      </button>
    </div>
  </nav>

  <!-- JavaScript Logic -->
  <script type="module">
    // Use ES Module import for reliable loading
    import { animate, stagger } from "https://cdn.jsdelivr.net/npm/motion@10.17.0/dist/motion.js";

    // 1. Entry Animation for Feed Items
    animate(
      ".feed-item",
      { opacity: [0, 1], y: [20, 0] },
      { delay: stagger(0.1), duration: 0.4, easing: "ease-out" }
    );

    // 2. Tabs Logic
    const tabs = document.querySelectorAll('#feed-tabs button');
    const feedItems = document.querySelectorAll('.feed-item');

    tabs.forEach(tab => {
      tab.addEventListener('click', () => {
        // Reset Active State
        tabs.forEach(t => {
          t.setAttribute('data-active', 'false');
          t.style.backgroundColor = 'white';
          t.style.color = '#475569';
          t.style.borderColor = '#e2e8f0';
        });
        
        // Set New Active State
        tab.setAttribute('data-active', 'true');
        tab.style.backgroundColor = '#0f172a';
        tab.style.color = 'white';
        tab.style.borderColor = '#0f172a';

        const target = tab.getAttribute('data-target');

        // Filter Items with animation
        feedItems.forEach(item => {
          const itemType = item.getAttribute('data-type');
          
          if (target === 'all' || itemType === target) {
            item.style.display = 'flex';
            // Re-animate entrance for filter change
            animate(item, { opacity: [0, 1], scale: [0.98, 1] }, { duration: 0.2 });
          } else {
            item.style.display = 'none';
          }
        });
      });
    });

    // 3. Like Button Animation
    const likeButtons = document.querySelectorAll('.ri-heart-3-line');
    likeButtons.forEach(icon => {
      icon.parentElement.addEventListener('click', function() {
        const isLiked = icon.classList.contains('ri-heart-3-fill');
        if (isLiked) {
          icon.classList.replace('ri-heart-3-fill', 'ri-heart-3-line');
          icon.classList.remove('text-pink-600');
          this.querySelector('span').classList.remove('text-pink-600');
        } else {
          icon.classList.replace('ri-heart-3-line', 'ri-heart-3-fill');
          icon.classList.add('text-pink-600');
          this.querySelector('span').classList.add('text-pink-600');
          
          // Pop animation
          animate(icon, { scale: [1, 1.4, 1] }, { duration: 0.3 });
        }
      });
    });
  </script>
 
</body>
</html>