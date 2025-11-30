<!DOCTYPE html>
<html lang="pt-BR" class="h-full w-full bg-slate-50 antialiased selection:bg-neutral-500 selection:text-white">
<head>
  <meta charset="UTF-8" />
  <title>Apollo :: Docs & Contratos</title>
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=0" />

  <!-- Tailwind CSS -->
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          fontFamily: {
            sans: ['Inter', 'sans-serif'],
          },
          colors: {
            slate: {
              850: '#1e293b',
            }
          }
        }
      }
    }
  </script>

  <!-- Design system Apollo (Tailwind + tokens) -->
  <link rel="stylesheet" href="https://assets.apollo.rio.br/uni.css" />
  <!-- JS base Apollo (remixicon, toggle tema, etc.) -->
  <script src="https://assets.apollo.rio.br/base.js" defer></script>

  <!-- Remixicon (fallback caso base.js não injete) -->
  <link href="https://cdn.jsdelivr.net/npm/remixicon@4.3.0/fonts/remixicon.css" rel="stylesheet" />

  <style>
    :root {
      --font-primary: "Urbanist", system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Oxygen, Ubuntu, Cantarell, 'Helvetica Neue', sans-serif;
      --text-size: 14.75px!important;
      --text-regular: 12.75px!important;
      --text-small: 10.75px!important;
      --text-smaller: 7.75px!important;

      --radius-sec: 20px;
      --radius-main: 12px;
      --radius-card: 16px;

      --transition-main: all 0.75s cubic-bezier(0.25, 0.8, 0.25, 1);
      --fly-border: rgba(148, 163, 184, 0.32);
      --fly-shadow: 0 20px 50px rgba(15, 23, 42, 0.05);

      --bg-main: #ffffff;
      --bg-main-translucent: rgba(255, 255, 255, .68);
      --bg-surface: #f5f5f5;
      --header-blur-bg: linear-gradient(to bottom, rgb(253 253 253 / .35) 0%, rgb(253 253 253 / .1) 50%, #fff0 100%);

      --text-main: rgba(19, 21, 23, .6);
      --text-primary: rgba(19, 21, 23, .85);
      --border-color: #e0e2e4;
      --border-color-2: #e5e7eb;

      --glass-radius: var(--radius-sec);
      --glass-blur: var(--radius-main);
      --glass-border-thick: 1px;
      --glass-border-color1: rgba(230, 230, 230, 0.4);
      --glass-border-color2: rgba(230, 230, 230, 0.1);
      --glass-color: rgba(255, 255, 255, 0.4);
    }

    body.dark-mode {
      --bg-main: #131517;
      --bg-main-translucent: rgba(19, 21, 23, 0.68);
      --header-blur-bg: linear-gradient(to bottom, rgb(19 21 23 / .35) 0%, rgb(19 21 23 / .1) 50%, #13151700 100%);
      --text-main: #ffffff91;
      --text-primary: #fdfdfdfa;
      --border-color: #333537;
      --border-color-2: #374151;
      --glass-border-color1: rgba(48, 48, 48, 0.4);
      --glass-border-color2: rgba(110, 110, 110, 0.1);
      --glass-color: rgba(15, 23, 42, 0.8);
    }

    .glass {
      position: relative;
      isolation: isolate;
      border-radius: var(--glass-radius);
      background: var(--glass-color);
      backdrop-filter: blur(var(--glass-blur));
    }

    * {
      -webkit-tap-highlight-color: transparent;
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }

    html, body {
      color: var(--text-main);
      font-family: var(--font-primary);
      font-size: 14.75px;
      font-weight: 400;
      line-height: 1.1rem;
      letter-spacing: 0.7px;
      background-color: var(--bg-main);
      transition: background-color 0.5s ease, color 0.5s ease;
      overflow-x: hidden !important;
      scroll-behavior: smooth;
      -webkit-user-select: none;
      -ms-user-select: none;
      user-select: none;
      min-height: 100%;
    }

    .no-scrollbar::-webkit-scrollbar { display: none; }
    .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }

    .pb-safe-area { padding-bottom: env(safe-area-inset-bottom, 20px); }

    @media only screen and (max-width: 868px) {
      body, html {
        max-width: 550px;
        margin: 0 auto;
        display: block;
      }
    }

    .menutag {
      display: inline-flex;
      align-items: center;
      gap: 0.35rem;
      padding: 0.35rem 0.75rem;
      border-radius: 999px;
      border: 1px solid #e2e8f0;
      font-size: 11px;
      font-weight: 500;
      color: #64748b;
      background-color: #ffffff;
      white-space: nowrap;
      transition: background-color 0.18s ease, color 0.18s ease, border-color 0.18s ease;
    }
    .menutag[data-active="true"] {
      background-color: #0f172a;
      color: #ffffff;
      border-color: #0f172a;
    }

    .aprioEXP-card-shell {
      background-color: #ffffff;
      border-radius: var(--radius-card);
      border: 1px solid var(--border-color-2);
      box-shadow: 0 8px 28px rgba(15, 23, 42, 0.04);
      transition: transform 0.18s ease, box-shadow 0.18s ease, border-color 0.18s ease, background-color 0.18s ease;
    }
    .aprioEXP-card-shell:hover {
      transform: translateY(-1px);
      box-shadow: 0 14px 40px rgba(15, 23, 42, 0.06);
      border-color: #cbd5f5;
    }

    body.dark-mode .aprioEXP-card-shell {
      background-color: #020617;
      border-color: #1f2937;
      box-shadow: 0 10px 32px rgba(0,0,0,0.6);
    }

    .aprio-sidebar-nav a {
      display: flex;
      align-items: center;
      gap: 0.75rem;
      padding: 0.55rem 0.75rem;
      margin-bottom: 0.1rem;
      border-radius: 10px;
      border-left: 2px solid transparent;
      font-size: 13px;
      color: #64748b;
      text-decoration: none;
      transition: background-color 0.18s ease, color 0.18s ease, border-color 0.18s ease;
    }

    .aprio-sidebar-nav a i {
      font-size: 18px;
    }

    .aprio-sidebar-nav a:hover {
      background-color: #f8fafc;
      color: #0f172a;
      border-left-color: #e5e7eb;
    }

    .aprio-sidebar-nav a[aria-current="page"] {
      background-color: #f1f5f9;
      color: #0f172a;
      border-left-color: #0f172a;
      font-weight: 600;
    }

    body.dark-mode .aprio-sidebar-nav a {
      color: #94a3b8;
    }

    body.dark-mode .aprio-sidebar-nav a:hover {
      background-color: #020617;
      border-left-color: #1f2937;
      color: #e5e7eb;
    }

    body.dark-mode .aprio-sidebar-nav a[aria-current="page"] {
      background-color: #020617;
      border-left-color: #f97316;
      color: #f9fafb;
    }

    .aprio-mini-pill {
      font-size: 11px;
      text-transform: uppercase;
      letter-spacing: 0.06em;
      color: #94a3b8;
    }
    .aprio-mini-link {
      font-size: 11px;
      color: #64748b;
      display: inline-flex;
      align-items: center;
      gap: 0.25rem;
    }
    .aprio-mini-link:hover {
      color: #0f172a;
    }

    .aprio-doc-row {
      display: flex;
      align-items: center;
      justify-content: space-between;
      width: 100%;
      padding: 0.5rem 0.75rem;
      border-radius: 10px;
      border: 1px solid transparent;
      transition: background-color 0.16s ease, border-color 0.16s ease;
    }
    .aprio-doc-row:hover {
      background-color: #f8fafc;
      border-color: #e5e7eb;
    }
    body.dark-mode .aprio-doc-row:hover {
      background-color: #020617;
      border-color: #1f2937;
    }
  </style>
</head>
<body class="min-h-screen">
  <div class="min-h-screen flex bg-slate-50">

    <!-- SIDEBAR DESKTOP -->
   
  <!-- SIDEBAR DESKTOP: APOLLO SOCIAL -->
    <aside class="hidden md:flex md:flex-col w-64 border-r border-slate-200 bg-white/95 backdrop-blur-xl">
      <!-- Logo / topo -->
      <div class="h-16 flex items-center gap-3 px-6 border-b border-slate-100">
        <div class="h-9 w-9 rounded-[8px] bg-slate-900 flex items-center justify-center text-white">
          <i class="ri-command-fill text-lg"></i>
        </div>
        <div class="flex flex-col leading-tight">
          <span class="text-[9.5px] font-regular text-slate-400 uppercase tracking-[0.18em]">plataforma</span>
          <span class="text-[15px] font-extrabold text-slate-900">Apollo::rio</span>
        </div>
      </div>

      <!-- Navegação -->
      <nav class="aprio-sidebar-nav flex-1 px-4 pt-4 pb-2 overflow-y-auto no-scrollbar text-[13px]">
        <div class="px-1 mb-2 text-[9.5px] font-regular text-slate-400 uppercase tracking-wider">Navegação</div>

        <a href="#">
          <i class="ri-building-3-line"></i>
          <span>Feed</span>
        </a>

        <a href="#">
          <i class="ri-calendar-event-line"></i>
          <span>Eventos</span>
        </a>

        <a href="#" aria-current="page">
          <i class="ri-user-community-fill"></i>
          <span>Comunidades</span>
        </a>

        <a href="#">
          <i class="ri-team-fill"></i>
          <span>Núcleos</span>
        </a>

        <a href="#">
          <i class="ri-megaphone-line"></i>
          <span>Classificados</span>
        </a>

        <a href="#">
         <i class="ri-file-text-line"></i>
          <span>Docs & Contratos</span>
        </a>

        <a href="#">
          <i class="ri-user-smile-fill"></i>
          <span>Perfil</span>
        </a>

        <div class="mt-4 px-1 mb-1 text-[9.5px] font-regular text-slate-400 uppercase tracking-wider">Configurações</div>
        <a href="#">
          <i class="ri-settings-6-line"></i>
          <span>Ajustes</span>
        </a>
      </nav>

      <!-- User / footer sidebar -->
      <div class="border-t border-slate-100 px-4 py-3">
        <div class="flex items-center gap-3">
          <div class="h-8 w-8 rounded-full overflow-hidden bg-slate-100">
            <img
              src="https://api.dicebear.com/7.x/avataaars/svg?seed=Valle&backgroundColor=e5e7eb"
              class="h-full w-full object-cover"
              alt="Valle"
            />
          </div>
          <div class="flex flex-col leading-tight">
            <span class="text-[12px] font-semibold text-slate-900">Valle</span>
            <span class="text-[10px] text-slate-500">@valle</span>
          </div>
          <button class="ml-auto text-slate-400 hover:text-slate-700">
            <i class="ri-logout-circle-r-line text-base"></i>
          </button>
        </div>
      </div>
    </aside>

    <!-- MAIN -->
    <div class="flex-1 flex flex-col min-h-screen">

      <!-- HEADER -->
      <header class="sticky top-0 z-40 h-14 flex items-center justify-between border-b border-slate-200 bg-white/90 backdrop-blur-md px-4 md:px-6">
        <div class="flex items-center gap-3">
          <div class="h-9 w-9 rounded-[6px] bg-slate-900 flex items-center justify-center md:hidden text-white">
            <i class="ri-command-fill text-[20px]"></i>
          </div>

          <div class="flex flex-col leading-none">
            <h1 class="text-[15px] font-extrabold mt-2 text-slate-900">
              Docs & contratos
            </h1>
            <p class="text-[12px] text-slate-500">
              3 documentos ativos · organizados por modelo da cena
            </p>
          </div>
        </div>

        <div class="hidden md:flex items-center gap-3 text-[12px]">
          <button class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full border border-slate-200 text-slate-700 hover:bg-slate-50">
            <i class="ri-search-line text-xs"></i>
            <span>Buscar doc...</span>
          </button>
          <a href="docs-editor.html" class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-slate-900 text-white text-[12px] font-medium hover:bg-slate-800">
            <i class="ri-add-line text-sm"></i>
            Novo documento
          </a>
        </div>

        <!-- Mobile actions -->
        <div class="flex md:hidden items-center gap-2">
          <button class="text-slate-500 hover:text-slate-900">
            <i class="ri-search-line text-xl"></i>
          </button>
          <a href="docs-editor.html" class="text-slate-900">
            <i class="ri-add-circle-fill text-2xl"></i>
          </a>
        </div>
      </header>

      <!-- CONTENT -->
      <main class="flex-1 flex justify-center px-0 md:px-6 py-0 md:py-6 pb-24 md:pb-6">
        <div class="w-full max-w-6xl grid grid-cols-1 lg:grid-cols-[minmax(0,2fr)_minmax(0,1fr)] gap-6">

          <!-- LEFT: LISTA DE DOCS -->
          <div class="space-y-4">

            <!-- Filtros e ação -->
            <section class="aprioEXP-card-shell p-4">
              <div class="flex items-center justify-between mb-3">
                <div class="flex flex-col gap-1">
                  <span class="text-[12px] font-semibold text-slate-700">Meus documentos</span>
                  <span class="text-[11px] text-slate-500">Central de contratos, termos e checklists da cena</span>
                </div>
                <a href="docs-editor.html" class="hidden sm:inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-slate-900 text-white text-[12px] font-medium hover:bg-slate-800">
                  <i class="ri-add-line text-sm"></i>
                  Novo documento
                </a>
              </div>

              <div class="flex flex-wrap gap-2 mb-4">
                <button class="menutag" data-active="true">
                  <i class="ri-apps-2-line text-xs"></i> Todos
                </button>
                <button class="menutag">
                  <i class="ri-edit-box-line text-xs"></i> Em rascunho
                </button>
                <button class="menutag">
                  <i class="ri-file-shield-2-line text-xs"></i> Aguardando assinatura
                </button>
                <button class="menutag">
                  <i class="ri-check-double-line text-xs"></i> Assinados
                </button>
              </div>

              <!-- LISTA DE 3 DOCS FAKE -->
              <div class="space-y-1.5 text-[12px]">
                <!-- DOC 1 -->
                <button class="aprio-doc-row text-left">
                  <div class="flex items-center gap-3 min-w-0">
                    <div class="h-9 w-9 rounded-lg border border-slate-200 flex items-center justify-center bg-slate-50 text-slate-700">
                      <i class="ri-file-text-line text-base"></i>
                    </div>
                    <div class="min-w-0">
                      <div class="flex items-center gap-2">
                        <span class="font-semibold text-slate-800 truncate">Contrato · DJ Dismantle · 27/01</span>
                        <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-full bg-emerald-50 text-emerald-700 text-[10px]">
                          <i class="ri-shield-check-line text-[10px]"></i> Assinado
                        </span>
                      </div>
                      <div class="flex flex-wrap items-center gap-2 text-[11px] text-slate-500 mt-0.5">
                        <span>Evento: Dismantle · Pindorama</span>
                        <span>·</span>
                        <span>Última atualização: 12/11/2025 · 18:42</span>
                      </div>
                    </div>
                  </div>
                  <div class="flex flex-col items-end gap-1 text-[11px] text-slate-500 pl-3">
                    <span>2 assinaturas concluídas</span>
                    <span class="inline-flex items-center gap-1 text-slate-400">
                      Detalhes <i class="ri-arrow-right-s-line text-xs"></i>
                    </span>
                  </div>
                </button>

                <!-- DOC 2 -->
                <button class="aprio-doc-row text-left">
                  <div class="flex items-center gap-3 min-w-0">
                    <div class="h-9 w-9 rounded-lg border border-slate-200 flex items-center justify-center bg-slate-50 text-slate-700">
                      <i class="ri-clipboard-line text-base"></i>
                    </div>
                    <div class="min-w-0">
                      <div class="flex items-center gap-2">
                        <span class="font-semibold text-slate-800 truncate">Checklist Operacional · Festa Rara</span>
                        <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-full bg-amber-50 text-amber-700 text-[10px]">
                          <i class="ri-time-line text-[10px]"></i> Em rascunho
                        </span>
                      </div>
                      <div class="flex flex-wrap items-center gap-2 text-[11px] text-slate-500 mt-0.5">
                        <span>Responsável: Valle</span>
                        <span>·</span>
                        <span>Última edição: 25/11/2025 · 10:13</span>
                      </div>
                    </div>
                  </div>
                  <div class="flex flex-col items-end gap-1 text-[11px] text-slate-500 pl-3">
                    <span>0 assinaturas</span>
                    <span class="inline-flex items-center gap-1 text-slate-400">
                      Continuar <i class="ri-arrow-right-s-line text-xs"></i>
                    </span>
                  </div>
                </button>

                <!-- DOC 3 -->
                <button class="aprio-doc-row text-left">
                  <div class="flex items-center gap-3 min-w-0">
                    <div class="h-9 w-9 rounded-lg border border-slate-200 flex items-center justify-center bg-slate-50 text-slate-700">
                      <i class="ri-file-lock-line text-base"></i>
                    </div>
                    <div class="min-w-0">
                      <div class="flex items-center gap-2">
                        <span class="font-semibold text-slate-800 truncate">Termo de Comunidade · Tropicalis</span>
                        <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-full bg-sky-50 text-sky-700 text-[10px]">
                          <i class="ri-mail-send-line text-[10px]"></i> Aguardando assinatura
                        </span>
                      </div>
                      <div class="flex flex-wrap items-center gap-2 text-[11px] text-slate-500 mt-0.5">
                        <span>Participantes: 3 pessoas</span>
                        <span>·</span>
                        <span>Enviado em: 20/11/2025 · 21:09</span>
                      </div>
                    </div>
                  </div>
                  <div class="flex flex-col items-end gap-1 text-[11px] text-slate-500 pl-3">
                    <span>1/3 assinaturas concluídas</span>
                    <span class="inline-flex items-center gap-1 text-slate-400">
                      Acompanhar <i class="ri-arrow-right-s-line text-xs"></i>
                    </span>
                  </div>
                </button>
              </div>
            </section>

          </div>

          <!-- RIGHT: RESUMO / ATALHOS -->
          <aside class="hidden lg:block space-y-4">

            <div class="aprioEXP-card-shell p-4">
              <div class="flex items-center justify-between mb-2">
                <h2 class="text-[12px] font-bold uppercase tracking-wider text-slate-500">Resumo de assinaturas</h2>
                <span class="text-[10px] text-slate-400">Últimos 30 dias</span>
              </div>

              <div class="space-y-3 text-[12px]">
                <div class="flex items-center justify-between">
                  <span class="text-slate-600">Documentos criados</span>
                  <span class="font-semibold text-slate-900">7</span>
                </div>
                <div class="flex items-center justify-between">
                  <span class="text-slate-600">Assinaturas concluídas</span>
                  <span class="font-semibold text-emerald-600">12</span>
                </div>
                <div class="flex items-center justify-between">
                  <span class="text-slate-600">Pendurados aguardando gov.br</span>
                  <span class="font-semibold text-amber-600">3</span>
                </div>
              </div>

              <hr class="my-3 border-slate-100" />

              <div class="space-y-2 text-[11px]">
                <div class="flex items-center justify-between">
                  <span class="aprio-mini-pill">Modelos rápidos</span>
                </div>
                <div class="flex flex-wrap gap-1.5">
                  <button class="px-2 py-1 rounded-full border border-slate-200 text-slate-600 hover:bg-slate-50">
                    Contrato padrão DJ
                  </button>
                  <button class="px-2 py-1 rounded-full border border-slate-200 text-slate-600 hover:bg-slate-50">
                    Termo de Comunidade
                  </button>
                  <button class="px-2 py-1 rounded-full border border-slate-200 text-slate-600 hover:bg-slate-50">
                    Checklist Operacional
                  </button>
                </div>

              </div>
            </div>

            <div class="aprioEXP-card-shell p-4">
              <h2 class="text-[12px] font-bold uppercase tracking-wider text-slate-500 mb-3">Ações rápidas</h2>
              <div class="space-y-2 text-[12px]">
                <a href="docs-editor.html" class="flex items-center justify-between px-2 py-1.5 rounded-lg hover:bg-slate-50">
                  <span class="flex items-center gap-2">
                    <i class="ri-add-line text-slate-500 text-sm"></i>
                    <span>Novo documento em branco</span>
                  </span>
                  <i class="ri-arrow-right-s-line text-slate-400 text-xs"></i>
                </a>
                <a href="#" class="flex items-center justify-between px-2 py-1.5 rounded-lg hover:bg-slate-50">
                  <span class="flex items-center gap-2">
                    <i class="ri-upload-2-line text-slate-500 text-sm"></i>
                    <span>Subir PDF para assinatura</span>
                  </span>
                  <i class="ri-arrow-right-s-line text-slate-400 text-xs"></i>
                </a>
                <a href="#" class="flex items-center justify-between px-2 py-1.5 rounded-lg hover:bg-slate-50">
                  <span class="flex items-center gap-2">
                    <i class="ri-government-line text-slate-500 text-sm"></i>
                    <span>Ver integração gov.br</span>
                  </span>
                  <i class="ri-arrow-right-s-line text-slate-400 text-xs"></i>
                </a>
              </div>
            </div>

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

      <!-- BOTTOM NAV MOBILE -->
      <nav class="md:hidden fixed bottom-0 left-0 right-0 bg-white border-t border-slate-200 pb-safe-area z-50">
        <div class="grid grid-cols-5 h-14">
          <button class="flex flex-col items-center justify-center text-slate-400 hover:text-slate-900 gap-1">
            <i class="ri-home-5-line text-xl"></i>
          </button>
          <button class="flex flex-col items-center justify-center text-slate-400 hover:text-slate-600 gap-1">
            <i class="ri-search-2-line text-xl"></i>
          </button>
          <a href="docs-editor.html" class="flex flex-col items-center justify-center text-slate-900 gap-1 -mt-6">
            <div class="h-12 w-12 bg-slate-900 text-white rounded-full flex items-center justify-center shadow-lg shadow-slate-900/20">
              <i class="ri-add-line text-2xl"></i>
            </div>
          </a>
          <button class="flex flex-col items-center justify-center text-slate-400 hover:text-slate-600 gap-1">
            <i class="ri-calendar-event-line text-xl"></i>
          </button>
          <button class="flex flex-col items-center justify-center text-slate-400 hover:text-slate-600 gap-1">
            <i class="ri-user-3-line text-xl"></i>
          </button>
        </div>
      </nav>

    </div>
  </div>
</body>
</html>
