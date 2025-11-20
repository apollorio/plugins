<?php
defined('ABSPATH') || exit;

// Ensure user is logged in
if (!is_user_logged_in()) {
    auth_redirect();
    exit;
}

$current_user = wp_get_current_user();
$user_id = $current_user->ID;
$avatar_url = get_avatar_url($user_id, ['size' => 200]);
$display_name = $current_user->display_name;
$user_login = $current_user->user_login;
?>
<!DOCTYPE html>
<html lang="pt-BR" class="h-full bg-white">
<head>
  <meta charset="UTF-8" />
  <title>Apollo :: Perfil Social</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <!-- Tailwind -->
  <script src="https://cdn.tailwindcss.com"></script>
  <!-- Remix Icons -->
  <link rel="stylesheet" href="https://assets.apollo.rio.br/uni.css" />
  <!-- Motion.dev -->
  <script src="https://unpkg.com/@motionone/dom/dist/motion-one.umd.js"></script>
  <style>
    .aprioEXP-body { font-family: system-ui, -apple-system, BlinkMacSystemFont, "SF Pro Text", Inter, sans-serif; }
    .aprioEXP-card-shell { border-radius: 0.75rem; border: 1px solid rgba(148, 163, 184, 0.4); background-color: rgba(255, 255, 255, 0.9); box-shadow: 0 14px 40px rgba(15, 23, 42, 0.09); backdrop-filter: blur(10px); }
    .aprioEXP-metric-chip { display: inline-flex; align-items: center; gap: 0.25rem; border-radius: 999px; background: #f3f4f6; padding: 0.1rem 0.55rem; font-size: 0.7rem; color: #4b5563; white-space: nowrap; }
    .aprioEXP-badge-private { display: inline-flex; align-items: center; gap: 0.25rem; border-radius: 999px; padding: 0.15rem 0.55rem; font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.05em; font-weight: 600; background: #02061799; color: #f9fafb; }
    .aprioEXP-badge-public { display: inline-flex; align-items: center; gap: 0.25rem; border-radius: 999px; padding: 0.15rem 0.55rem; font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.05em; font-weight: 600; background: #ecfdf3; color: #15803d; }
    .aprioEXP-tab-btn { display: inline-flex; align-items: center; gap: 0.35rem; padding: 0.3rem 0.8rem; border-radius: 999px; font-size: 0.75rem; font-weight: 500; color: #6b7280; border: none; background: transparent; cursor: pointer; transition: background-color 0.15s ease, color 0.15s ease, box-shadow 0.15s ease; }
    .aprioEXP-tab-btn:hover { background-color: #e5e7eb; color: #111827; }
    .aprioEXP-tab-btn[data-active="true"] { background: #02061799; backdrop-filter: blur(6px); color: #f9fafb; font-weight: 300; box-shadow: 0 10px 44px rgba(15, 23, 42, 0.03); filter: brightness(1.3); }
    .bg-slate-90099 { background: #02061799; }
    .bg-slate-90099:hover { background: #020617CC; transform: translateY(-2px); transition: all ease-in-out 0.35s; }
    .sidebar-shell { background: #f9fafb; border-right: 1px solid #e5e7eb; }
    /* aprioEXP Custom CSS */
    .aprioEXP-profile-header-row { display: flex; flex-direction: column; gap: 1rem; }
    @media (min-width: 768px) { .aprioEXP-profile-header-row { flex-direction: row; align-items: center; } }
    .aprioEXP-user-data-section { display: flex; align-items: center; gap: 0.75rem; width: 100%; }
    @media (min-width: 768px) { .aprioEXP-user-data-section { width: 60%; } }
    .aprioEXP-stats-section { display: flex; flex-direction: column; align-items: flex-start; gap: 0.5rem; width: 100%; }
    @media (min-width: 768px) { .aprioEXP-stats-section { width: 40%; align-items: flex-end; } }
    .aprioEXP-cards-container { display: flex; flex-wrap: wrap; gap: 0.5rem; width: 100%; }
    @media (min-width: 768px) { .aprioEXP-cards-container { justify-content: flex-end; } }
    .aprioEXP-stat-card { display: inline-flex; flex-direction: column; align-items: center; justify-content: center; border-radius: 0.5rem; background-color: #f1f5f9; padding: 0.15rem 0.35rem; min-width: 3rem; width: fit-content; }
    .aprioEXP-card-numbers-title { font-size: 8px; text-transform: uppercase; letter-spacing: 0.12em; color: #64748b; }
    .aprioEXP-card-numbers-numbers { font-size: 12px; font-weight: 500; color: #0f172a; }
    .aprioEXP-card-numbers-listing { font-size: 9.5px; font-weight: 600; text-align: center; line-height: 1.2; color: #334155; }
    .aprioEXP-edit-btn { display: inline-flex; align-items: center; justify-content: center; gap: 0.25rem; border-radius: 0.375rem; border: 1px solid #e2e8f0; background-color: white; padding: 0.375rem 0.75rem; font-size: 11px; font-weight: 500; color: #334155; width: 100%; cursor: pointer; transition: background-color 0.2s; }
    .aprioEXP-edit-btn:hover { background-color: #f8fafc; }
    @media (min-width: 768px) { .aprioEXP-edit-btn { width: auto; } }
  </style>
</head>
<body class="h-full bg-slate-50 text-slate-900">
<section class="aprioEXP-body">
 <div class="min-h-screen flex flex-col">
  <!-- Top header -->
  <header class="h-14 flex items-center justify-between border-b bg-white/80 backdrop-blur px-3 md:px-6">
   <div class="flex items-center gap-3">
    <button class="inline-flex h-8 w-8 items-center justify-center menutags bg-slate-90099 text-white">
     <i class="ri-slack-line text-[18px]"></i>
    </button>
    <div class="flex flex-col">
     <span class="text-[10px] uppercase tracking-[0.12em] text-slate-400">Rede Social CULTURAl Carioca</span>
     <span class="text-sm font-semibold">@<?php echo esc_html($user_login); ?> · Apollo::rio</span>
    </div>
   </div>
   <div class="flex items-center gap-2 text-[11px]">
    <a href="/clubber/<?php echo $user_id; ?>" class="hidden md:inline-flex items-center gap-1 rounded-md border border-slate-200 bg-white px-2.5 py-1.5 font-medium text-slate-700 hover:bg-slate-50" >
     <i class="ri-eye-line text-xs"></i> <span>Ver como visitante</span>
    </a>
    <a href="#" class="inline-flex items-center gap-1 rounded-md bg-slate-90099 px-3 py-1.5 font-medium text-white" >
     <i class="ri-external-link-line text-xs"></i> <span>Abrir página pública</span>
    </a>
    <button class="inline-flex h-8 w-8 items-center justify-center rounded-full hover:bg-slate-100">
     <div class="h-7 w-7 overflow-hidden rounded-full bg-slate-200">
      <img src="<?php echo esc_url($avatar_url); ?>" alt="<?php echo esc_attr($display_name); ?>" class="h-full w-full object-cover" />
     </div>
    </button>
   </div>
  </header>
  <!-- Main -->
  <main class="flex-1 flex justify-center px-3 md:px-6 py-4 md:py-6">
   <div class="w-full max-w-6xl grid lg:grid-cols-[minmax(0,2.5fr)_minmax(0,1fr)] gap-4">
    <!-- LEFT COLUMN: Profile + Tabs -->
    <div class="space-y-4">
     <!-- Profile card -->
     <section class="aprioEXP-card-shell p-4 md:p-5">
      <div class="aprioEXP-profile-header-row">
       <!-- USER DATA SECTION -->
       <div class="aprioEXP-user-data-section">
        <div class="relative shrink-0">
         <div class="h-16 w-16 md:h-20 md:w-20 overflow-hidden rounded-full bg-gradient-to-tr from-orange-500 via-rose-500 to-amber-400 aspect-square">
          <img src="<?php echo esc_url($avatar_url); ?>" alt="Avatar" class="h-full w-full object-cover mix-blend-luminosity" />
         </div>
         <span class="absolute bottom-0 right-0 inline-flex h-5 w-5 items-center justify-center rounded-full bg-emerald-500 text-[11px] text-white ring-2 ring-white z-10" >
          <i class="ri-flashlight-fill"></i>
         </span>
        </div>
        <div class="min-w-0">
         <div class="flex flex-wrap items-center gap-2">
          <h1 class="truncate text-base md:text-lg font-semibold"><?php echo esc_html($display_name); ?> · Apollo::rio</h1>
          <span class="aprioEXP-metric-chip"> <i class="ri-music-2-line text-xs"></i> <span>Produtor &amp; DJ</span> </span>
         </div>
         <p class="mt-1 text-[11px] md:text-[12px] text-slate-600 line-clamp-2">
          Conectando eventos, comunidades e dados da cena eletrônica do Rio.
         </p>
         <div class="mt-2 flex flex-wrap gap-2 text-[10px] md:text-[11px] text-slate-500">
          <span class="aprioEXP-metric-chip"> <i class="ri-map-pin-line text-xs"></i> Copacabana · RJ </span>
          <span class="aprioEXP-metric-chip"> <i class="ri-vip-crown-2-line text-xs"></i> Industry access </span>
          <span class="aprioEXP-metric-chip"> <i class="ri-group-line text-xs"></i> 3 núcleos · 8 comunidades </span>
         </div>
        </div>
       </div>
       <!-- STATS SECTION -->
       <div class="aprioEXP-stats-section">
        <div class="aprioEXP-cards-container">
         <div class="aprioEXP-stat-card"> <span class="aprioEXP-card-numbers-title">Producer</span> <span class="aprioEXP-card-numbers-numbers">3</span> </div>
         <div class="aprioEXP-stat-card"> <span class="aprioEXP-card-numbers-title">Favoritado</span> <span class="aprioEXP-card-numbers-numbers">11</span> </div>
         <div class="aprioEXP-stat-card"> <span class="aprioEXP-card-numbers-title">Posts</span> <span class="aprioEXP-card-numbers-numbers">5</span> </div>
         <div class="aprioEXP-stat-card"> <span class="aprioEXP-card-numbers-title">Comments</span> <span class="aprioEXP-card-numbers-numbers">37</span> </div>
         <div class="aprioEXP-stat-card"> <span class="aprioEXP-card-numbers-title">Liked</span> <span class="aprioEXP-card-numbers-numbers">26</span> </div>
         <div class="aprioEXP-stat-card"> <span class="aprioEXP-card-numbers-title">Memberships</span> <span class="aprioEXP-card-numbers-listing"> DJ ◦ Producer<br/>Hostess ◦ Apollo </span> </div>
        </div>
        <button class="aprioEXP-edit-btn"> <i class="ri-pencil-line text-xs"></i> <span>Editar perfil interno</span> </button>
       </div>
      </div>
     </section>
     <!-- Tabs + content -->
     <section class="aprioEXP-card-shell p-3 md:p-4">
      <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-200 pb-2">
       <div class="flex flex-wrap gap-1 md:gap-2" role="tablist" aria-label="Navegação do perfil">
        <button class="aprioEXP-tab-btn" type="button" data-tab-target="events" data-active="true" role="tab" aria-selected="true" > <i class="ri-heart-3-line text-[13px]"></i> <span>Eventos favoritos</span> </button>
        <button class="aprioEXP-tab-btn" type="button" data-tab-target="metrics" role="tab" aria-selected="false" > <i class="ri-bar-chart-2-line text-[13px]"></i> <span>Meus números</span> </button>
        <button class="aprioEXP-tab-btn" type="button" data-tab-target="nucleo" role="tab" aria-selected="false" > <i class="ri-lock-2-line text-[13px]"></i> <span>Núcleo (privado)</span> </button>
        <button class="aprioEXP-tab-btn" type="button" data-tab-target="communities" role="tab" aria-selected="false" > <i class="ri-community-line text-[13px]"></i> <span>Comunidades</span> </button>
        <button class="aprioEXP-tab-btn" type="button" data-tab-target="docs" role="tab" aria-selected="false" > <i class="ri-file-text-line text-[13px]"></i> <span>Documentos</span> </button>
       </div>
       <div class="flex items-center gap-2 text-[11px] text-slate-500">
        <span class="hidden sm:inline">Fluxo interno Apollo Social</span>
        <span class="h-4 w-px bg-slate-200"></span>
        <span class="aprioEXP-metric-chip"> <i class="ri-shining-fill text-[12px]"></i> <span>Motion tabs</span> </span>
       </div>
      </div>
      <div class="mt-3 space-y-4">
       <!-- TAB: Eventos favoritos -->
       <div data-tab-panel="events" role="tabpanel" class="space-y-3">
        <div class="flex flex-col md:flex-row md:items-center gap-3">
         <div class="flex-1">
          <h2 class="text-sm font-semibold">Eventos favoritados</h2>
          <p class="text-[12px] text-slate-600"> Eventos que você marcou como <b>Ir</b>, <b>Talvez</b> ou salvou para acompanhar. </p>
         </div>
         <button class="inline-flex items-center gap-1 rounded-md border border-slate-200 bg-white px-3 py-1.5 text-[11px] font-medium text-slate-700 hover:bg-slate-50" > <i class="ri-filter-3-line text-xs"></i> <span>Filtrar por data</span> </button>
        </div>
        <div class="grid gap-3 md:grid-cols-2 text-[12px]">
         <!-- Event 1 -->
         <article class="aprioEXP-card-shell p-3 flex flex-col justify-between">
          <div class="flex items-start justify-between gap-2">
           <div> <h3 class="text-sm font-semibold">Dismantle · Puro Suco do Caos</h3> <p class="text-[11px] text-slate-600">Copacabana · 22:00 · sexta</p> <p class="mt-1 text-[11px] text-slate-600 line-clamp-2"> Noite longa de techno, house e caos carioca, hosted by Valle &amp; amigxs. </p> </div>
           <div class="flex flex-col items-end text-[11px]"> <span class="rounded-md bg-slate-90099 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-[0.12em] text-white" > Ir </span> <span class="mt-1 text-slate-500">+143 pessoas</span> </div>
          </div>
          <div class="mt-3 flex items-center justify-between text-[11px] text-slate-500">
           <div class="flex flex-wrap items-center gap-2"> <span class="aprioEXP-metric-chip"> <i class="ri-moon-clear-line text-xs"></i> After cadastrado </span> <span class="aprioEXP-metric-chip"> <i class="ri-map-pin-2-line text-xs"></i> Copacabana </span> </div>
           <button class="inline-flex items-center gap-1 rounded-md px-2 py-1 hover:bg-slate-100" > <i class="ri-external-link-line text-xs"></i> <span>Ver evento</span> </button>
          </div>
         </article>
         <!-- Event 2 -->
         <article class="aprioEXP-card-shell p-3 flex flex-col justify-between">
          <div class="flex items-start justify-between gap-2">
           <div> <h3 class="text-sm font-semibold">Afters em Botafogo · Apollo edition</h3> <p class="text-[11px] text-slate-600">Botafogo · 04:30 · domingo</p> <p class="mt-1 text-[11px] text-slate-600 line-clamp-2"> Pós-festa com grooves mais leves, disco, house e encontros improváveis. </p> </div>
           <div class="flex flex-col items-end text-[11px]"> <span class="rounded-md bg-amber-100 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-[0.12em] text-amber-900" > Talvez </span> <span class="mt-1 text-slate-500">+57 pessoas</span> </div>
          </div>
          <div class="mt-3 flex items-center justify-between text-[11px] text-slate-500">
           <div class="flex flex-wrap items-center gap-2"> <span class="aprioEXP-metric-chip"> <i class="ri-vip-diamond-line text-xs"></i> Lista amiga </span> <span class="aprioEXP-metric-chip"> <i class="ri-route-line text-xs"></i> A 800m de você </span> </div>
           <button class="inline-flex items-center gap-1 rounded-md px-2 py-1 hover:bg-slate-100" > <i class="ri-external-link-line text-xs"></i> <span>Ver evento</span> </button>
          </div>
         </article>
        </div>
       </div>
       <!-- TAB: Meus números -->
       <div data-tab-panel="metrics" role="tabpanel" class="hidden space-y-3">
        <div class="flex items-center justify-center h-32 text-slate-400 text-sm">
         <p>Dados de performance sendo calculados...</p>
        </div>
       </div>
       <!-- TAB: Núcleo (privado) -->
       <div data-tab-panel="nucleo" role="tabpanel" class="hidden space-y-3">
        <div class="flex flex-col md:flex-row md:items-center gap-3">
         <div class="flex-1"> <h2 class="text-sm font-semibold">Núcleos privados</h2> <p class="text-[12px] text-slate-600"> Espaços de trabalho e coordenação fechados, visíveis apenas para quem tem convite. </p> </div>
         <div class="flex flex-wrap gap-2 text-[11px]"> <button class="inline-flex items-center gap-1 rounded-md bg-slate-90099 px-3 py-1.5 font-medium text-white" > <i class="ri-team-line text-xs"></i> <span>Criar novo núcleo</span> </button> <button class="inline-flex items-center gap-1 rounded-md border border-slate-200 bg-white px-3 py-1.5 font-medium text-slate-700 hover:bg-slate-50" > <i class="ri-shield-keyhole-line text-xs"></i> <span>Gerenciar acessos</span> </button> </div>
        </div>
        <div class="grid gap-3 md:grid-cols-2 text-[12px]">
         <article class="aprioEXP-card-shell p-3 flex flex-col justify-between">
          <div class="flex items-start justify-between gap-2"> <div> <div class="flex items-center gap-2 mb-1"> <h3 class="text-sm font-semibold">Núcleo Cena::rio</h3> <span class="aprioEXP-badge-private"> <i class="ri-lock-2-line text-[11px]"></i> Privado </span> </div> <p class="text-slate-600 text-[11px] line-clamp-2"> Registro vivo da cena eletrônica carioca, curadoria de eventos e dados. </p> </div> <div class="text-[11px] text-slate-500 text-right"> <p>12 membros</p> <p>3 eventos ativos</p> </div> </div>
          <div class="mt-3 flex items-center justify-between text-[11px] text-slate-500"> <div class="flex flex-wrap items-center gap-2"> <span class="aprioEXP-metric-chip"> <i class="ri-database-2-line text-xs"></i> Registro::rio sync </span> <span class="aprioEXP-metric-chip"> <i class="ri-bar-chart-grouped-line text-xs"></i> Dashboard ativo </span> </div> <button class="inline-flex items-center gap-1 rounded-md px-2 py-1 hover:bg-slate-100" > <i class="ri-arrow-right-line text-xs"></i> <span>Entrar no núcleo</span> </button> </div>
         </article>
         <article class="aprioEXP-card-shell p-3 flex flex-col justify-between">
          <div class="flex items-start justify-between gap-2"> <div> <div class="flex items-center gap-2 mb-1"> <h3 class="text-sm font-semibold">Núcleo Produção &amp; Tech</h3> <span class="aprioEXP-badge-private"> <i class="ri-lock-2-line text-[11px]"></i> Privado </span> </div> <p class="text-slate-600 text-[11px] line-clamp-2"> Automação de planilhas, formulários, Gestor Apollo e integrações. </p> </div> <div class="text-[11px] text-slate-500 text-right"> <p>7 membros</p> <p>2 projetos abertos</p> </div> </div>
          <div class="mt-3 flex items-center justify-between text-[11px] text-slate-500"> <div class="flex flex-wrap items-center gap-2"> <span class="aprioEXP-metric-chip"> <i class="ri-code-s-slash-line text-xs"></i> Stack Apollo </span> <span class="aprioEXP-metric-chip"> <i class="ri-folder-2-line text-xs"></i> Docs internos </span> </div> <button class="inline-flex items-center gap-1 rounded-md px-2 py-1 hover:bg-slate-100" > <i class="ri-arrow-right-line text-xs"></i> <span>Abrir board</span> </button> </div>
         </article>
        </div>
       </div>
       <!-- TAB: Comunidades -->
       <div data-tab-panel="communities" role="tabpanel" class="hidden space-y-3">
        <div class="flex flex-col md:flex-row md:items-center gap-3">
         <div class="flex-1"> <h2 class="text-sm font-semibold">Comunidades públicas</h2> <p class="text-[12px] text-slate-600"> Grupos abertos para a cena, onde qualquer pessoa pode participar ou seguir. </p> </div> <button class="inline-flex items-center gap-1 rounded-md bg-slate-90099 px-3 py-1.5 text-[11px] font-medium text-white" > <i class="ri-community-line text-xs"></i> <span>Criar nova comunidade</span> </button>
        </div>
        <div class="grid gap-3 md:grid-cols-3 text-[12px]">
         <article class="aprioEXP-card-shell p-3 flex flex-col justify-between">
          <div> <div class="flex items-center gap-2 mb-1"> <h3 class="text-sm font-semibold">Tropicalis :: RJ</h3> <span class="aprioEXP-badge-public"> <i class="ri-sun-line text-[11px]"></i> Aberta </span> </div> <p class="text-slate-600 text-[11px] line-clamp-2"> Guia vivo de festas eletrônicas do Rio, com cupons, reviews e achados. </p> </div>
          <div class="mt-3 flex items-center justify-between text-[11px] text-slate-500"> <span>943 membros · 27 eventos listados</span> <button class="inline-flex items-center gap-1 rounded-md px-2 py-1 hover:bg-slate-100" > <i class="ri-arrow-right-line text-xs"></i> <span>Ver comunidade</span> </button> </div>
         </article>
         <article class="aprioEXP-card-shell p-3 flex flex-col justify-between">
          <div> <div class="flex items-center gap-2 mb-1"> <h3 class="text-sm font-semibold">After Lovers · Zona Sul</h3> <span class="aprioEXP-badge-public"> <i class="ri-sparkling-2-line text-[11px]"></i> Aberta </span> </div> <p class="text-slate-600 text-[11px] line-clamp-2"> Encontros, afters e rolês de pista estendida pela madrugada. </p> </div>
          <div class="mt-3 flex items-center justify-between text-[11px] text-slate-500"> <span>312 membros · 8 afters ativos</span> <button class="inline-flex items-center gap-1 rounded-md px-2 py-1 hover:bg-slate-100" > <i class="ri-arrow-right-line text-xs"></i> <span>Ver comunidade</span> </button> </div>
         </article>
         <article class="aprioEXP-card-shell p-3 flex flex-col justify-between">
          <div> <div class="flex items-center gap-2 mb-1"> <h3 class="text-sm font-semibold">Produtores &amp; DJs BR</h3> <span class="aprioEXP-badge-public"> <i class="ri-global-line text-[11px]"></i> Aberta </span> </div> <p class="text-slate-600 text-[11px] line-clamp-2"> Fórum de discussão sobre produção musical, equipamentos e mercado. </p> </div>
          <div class="mt-3 flex items-center justify-between text-[11px] text-slate-500"> <span>1.2k membros · 15 tópicos hoje</span> <button class="inline-flex items-center gap-1 rounded-md px-2 py-1 hover:bg-slate-100" > <i class="ri-arrow-right-line text-xs"></i> <span>Ver comunidade</span> </button> </div>
         </article>
        </div>
       </div>
       <!-- TAB: Documentos -->
       <div data-tab-panel="docs" role="tabpanel" class="hidden space-y-3">
        <div class="flex flex-col md:flex-row md:items-center gap-3">
         <div class="flex-1"> <h2 class="text-sm font-semibold">Documentos para assinar ou criar</h2> <p class="text-[12px] text-slate-600"> Fluxo rápido para contratos de DJ, staff, núcleos e parcerias de eventos. </p> </div> <div class="flex flex-wrap gap-2 text-[11px]"> <button class="inline-flex items-center gap-1 rounded-md bg-slate-90099 px-3 py-1.5 font-medium text-white" > <i class="ri-file-add-line text-xs"></i> <span>Novo documento</span> </button> <button class="inline-flex items-center gap-1 rounded-md border border-slate-200 bg-white px-3 py-1.5 font-medium text-slate-700 hover:bg-slate-50" > <i class="ri-ink-bottle-line text-xs"></i> <span>Assinar .doc em fila</span> </button> </div>
        </div>
        <div class="grid gap-3 md:grid-cols-3 text-[12px]">
         <article class="aprioEXP-card-shell p-3">
          <div class="flex items-start justify-between gap-2 mb-1"> <span class="font-semibold">Contrato DJ · Dismantle</span> <span class="inline-flex items-center gap-1 rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-semibold text-amber-900" > <span class="inline-block h-1.5 w-1.5 rounded-full bg-amber-500"></span> Pendente </span> </div>
          <p class="text-slate-600 text-[11px] mb-2 line-clamp-2"> Instrumento de prestação de serviços para set principal · 04h de duração, Copacabana. </p>
          <div class="flex items-center justify-between text-[11px] text-slate-500"> <span>Última atualização: hoje · 13:22</span> <button class="inline-flex items-center gap-1 rounded-md px-2 py-1 hover:bg-slate-100" > <i class="ri-edit-box-line text-xs"></i> <span>Revisar</span> </button> </div>
         </article>
         <article class="aprioEXP-card-shell p-3">
          <div class="flex items-start justify-between gap-2 mb-1"> <span class="font-semibold">Acordo Núcleo Cena::rio</span> <span class="inline-flex items-center gap-1 rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-semibold text-emerald-900" > <span class="inline-block h-1.5 w-1.5 rounded-full bg-emerald-500"></span> Assinado </span> </div>
          <p class="text-slate-600 text-[11px] mb-2 line-clamp-2"> Termos internos de coordenação do núcleo de curadoria e registro da cena. </p>
          <div class="flex items-center justify-between text-[11px] text-slate-500"> <span>Assinado: 03/11/2025</span> <button class="inline-flex items-center gap-1 rounded-md px-2 py-1 hover:bg-slate-100" > <i class="ri-download-2-line text-xs"></i> <span>Baixar PDF</span> </button> </div>
         </article>
         <article class="aprioEXP-card-shell p-3">
          <div class="flex items-start justify-between gap-2 mb-1"> <span class="font-semibold">Ficha técnica · Staff</span> <span class="inline-flex items-center gap-1 rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-semibold text-slate-700" > Rascunho </span> </div>
          <p class="text-slate-600 text-[11px] mb-2 line-clamp-2"> Formulário para mapear funções, horários e pagamentos da equipe de apoio. </p>
          <div class="flex items-center justify-between text-[11px] text-slate-500"> <span>Rascunho salvo: 28/10/2025</span> <button class="inline-flex items-center gap-1 rounded-md px-2 py-1 hover:bg-slate-100" > <i class="ri-arrow-right-up-line text-xs"></i> <span>Abrir ficha</span> </button> </div>
         </article>
        </div>
       </div>
      </div>
     </section>
    </div>
    <!-- RIGHT COLUMN: Sidebar (Restored) -->
    <div class="space-y-4">
     <!-- Resumo rápido Card -->
     <section class="aprioEXP-card-shell p-4">
      <h3 class="text-sm font-semibold mb-3">Resumo rápido</h3>
      <ul class="space-y-3 text-[12px] text-slate-600">
       <li class="flex gap-2"> <i class="ri-calendar-event-line text-slate-400 mt-0.5"></i> <div> <span class="block font-medium text-slate-900">Próximo compromisso</span> <span>Dismantle · Copacabana · sexta, 22:00</span> </div> </li>
       <li class="flex gap-2"> <i class="ri-file-text-line text-slate-400 mt-0.5"></i> <div> <span class="block font-medium text-slate-900">Docs pendentes</span> <span>1 contrato DJ · 2 fichas staff</span> </div> </li>
       <li class="flex gap-2"> <i class="ri-message-3-line text-slate-400 mt-0.5"></i> <div> <span class="block font-medium text-slate-900">Mensagens não lidas</span> <span>4 conversas</span> </div> </li>
      </ul>
      <button class="mt-4 w-full flex items-center justify-center gap-2 rounded-md bg-slate-90099 py-2 text-[11px] font-medium text-white transition-colors"> <span>Abrir Gestor Apollo</span> <i class="ri-arrow-right-line"></i> </button>
     </section>
     <!-- Status social Card -->
     <section class="aprioEXP-card-shell p-4">
      <h3 class="text-sm font-semibold mb-3">Status social</h3>
      <div class="space-y-3 text-[12px]">
       <div> <p class="font-medium text-slate-900 mb-1">Núcleos ativos</p> <div class="flex flex-wrap gap-1"> <span class="inline-flex items-center rounded-md bg-slate-100 px-2 py-0.5 text-slate-600">Cena::rio</span> <span class="inline-flex items-center rounded-md bg-slate-100 px-2 py-0.5 text-slate-600">Produção & Tech</span> <span class="inline-flex items-center rounded-md bg-slate-100 px-2 py-0.5 text-slate-600">Tropicalis Core</span> </div> </div>
       <div> <p class="font-medium text-slate-900 mb-1">Comunidades em destaque</p> <div class="flex flex-wrap gap-1"> <span class="inline-flex items-center rounded-md bg-emerald-50 px-2 py-0.5 text-emerald-700 border border-emerald-100">Tropicalis :: RJ</span> <span class="inline-flex items-center rounded-md bg-purple-50 px-2 py-0.5 text-purple-700 border border-purple-100">After Lovers</span> <span class="inline-flex items-center rounded-md bg-blue-50 px-2 py-0.5 text-blue-700 border border-blue-100">Produtores & DJs BR</span> </div> </div>
      </div>
     </section>
    </div>
   </div>
  </main>
 </div>
 <script>
  // Simple Tab Logic
  document.addEventListener("DOMContentLoaded", () => {
   const tabs = document.querySelectorAll('[role="tab"]');
   const panels = document.querySelectorAll('[role="tabpanel"]');
   tabs.forEach((tab) => {
    tab.addEventListener("click", () => {
     // Deselect all
     tabs.forEach((t) => { t.setAttribute("aria-selected", "false"); t.setAttribute("data-active", "false"); });
     panels.forEach((p) => p.classList.add("hidden"));
     // Select clicked
     tab.setAttribute("aria-selected", "true");
     tab.setAttribute("data-active", "true");
     const target = tab.getAttribute("data-tab-target");
     document.querySelector(`[data-tab-panel="${target}"]`).classList.remove("hidden");
    });
   });
  });
 </script>
</section>
</body>
</html>

