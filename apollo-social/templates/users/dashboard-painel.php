<?php
/**
 * User Dashboard Template - Private Dashboard (/painel/)
 * Based on CodePen: https://codepen.io/Rafael-Valle-the-looper/pen/qEZXyRQ
 * 
 * Renders private dashboard with tabs (Events, Metrics, Nucleo, Communities, Docs)
 */

if (!defined('ABSPATH')) exit;

$user = $view['data']['user'] ?? [];
$tabs = $view['data']['tabs'] ?? [];
$is_own_dashboard = $view['data']['is_own_dashboard'] ?? false;
$ajax_url = admin_url('admin-ajax.php');
$nonce = wp_create_nonce('apollo_dashboard');
?>

<div class="apollo-dashboard-painel aprioEXP-body">
    <div class="min-h-screen flex flex-col">
        
        <!-- Top header -->
        <header class="h-14 flex items-center justify-between border-b bg-white/80 backdrop-blur px-3 md:px-6">
            <div class="flex items-center gap-3">
                <button class="inline-flex h-8 w-8 items-center justify-center menutags bg-slate-90099 text-white">
                    <i class="ri-slack-line text-[18px]"></i>
                </button>
                <div class="flex flex-col">
                    <span class="text-[10px] uppercase tracking-[0.12em] text-slate-400">Rede Social CULTURAl Carioca</span>
                    <span class="text-sm font-semibold">@<?php echo esc_html($user['login'] ?? 'user'); ?> · Apollo::rio</span>
                </div>
            </div>
            <div class="flex items-center gap-2 text-[11px]">
                <button class="hidden md:inline-flex items-center gap-1 rounded-md border border-slate-200 bg-white px-2.5 py-1.5 font-medium text-slate-700 hover:bg-slate-50">
                    <i class="ri-eye-line text-xs"></i>
                    <span>Ver como visitante</span>
                </button>
                <a href="/id/<?php echo absint($user['id'] ?? 0); ?>/" class="inline-flex items-center gap-1 rounded-md bg-slate-90099 px-3 py-1.5 font-medium text-white">
                    <i class="ri-external-link-line text-xs"></i>
                    <span>Abrir página pública</span>
                </a>
                <button class="inline-flex h-8 w-8 items-center justify-center rounded-full hover:bg-slate-100">
                    <div class="h-7 w-7 overflow-hidden rounded-full bg-slate-200">
                        <img src="<?php echo esc_url($user['avatar'] ?? ''); ?>" alt="<?php echo esc_attr($user['name'] ?? ''); ?>" class="h-full w-full object-cover" />
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
                                        <img src="<?php echo esc_url($user['avatar'] ?? ''); ?>" alt="<?php echo esc_attr($user['name'] ?? ''); ?>" class="h-full w-full object-cover mix-blend-luminosity" />
                                    </div>
                                    <span class="absolute bottom-0 right-0 inline-flex h-5 w-5 items-center justify-center rounded-full bg-emerald-500 text-[11px] text-white ring-2 ring-white z-10">
                                        <i class="ri-flashlight-fill"></i>
                                    </span>
                                </div>
                                <div class="min-w-0">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <h1 class="truncate text-base md:text-lg font-semibold"><?php echo esc_html($user['name'] ?? 'Usuário'); ?> · Apollo::rio</h1>
                                        <span class="aprioEXP-metric-chip">
                                            <i class="ri-music-2-line text-xs"></i>
                                            <span>Produtor &amp; DJ</span>
                                        </span>
                                    </div>
                                    <p class="mt-1 text-[11px] md:text-[12px] text-slate-600 line-clamp-2">
                                        <?php echo esc_html($user['bio'] ?? 'Conectando eventos, comunidades e dados da cena eletrônica do Rio.'); ?>
                                    </p>
                                    <div class="mt-2 flex flex-wrap gap-2 text-[10px] md:text-[11px] text-slate-500">
                                        <span class="aprioEXP-metric-chip">
                                            <i class="ri-map-pin-line text-xs"></i>
                                            Copacabana · RJ
                                        </span>
                                        <span class="aprioEXP-metric-chip">
                                            <i class="ri-vip-crown-2-line text-xs"></i>
                                            Industry access
                                        </span>
                                        <span class="aprioEXP-metric-chip">
                                            <i class="ri-group-line text-xs"></i>
                                            <?php echo count($tabs['nucleo']['data'] ?? []); ?> núcleos · <?php echo count($tabs['communities']['data'] ?? []); ?> comunidades
                                        </span>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- STATS SECTION -->
                            <div class="aprioEXP-stats-section">
                                <div class="aprioEXP-cards-container">
                                    <div class="aprioEXP-stat-card">
                                        <span class="aprioEXP-card-numbers-title">Producer</span>
                                        <span class="aprioEXP-card-numbers-numbers"><?php echo count($tabs['events']['data'] ?? []); ?></span>
                                    </div>
                                    <div class="aprioEXP-stat-card">
                                        <span class="aprioEXP-card-numbers-title">Favoritado</span>
                                        <span class="aprioEXP-card-numbers-numbers"><?php echo $tabs['metrics']['data']['favorites'] ?? 0; ?></span>
                                    </div>
                                    <div class="aprioEXP-stat-card">
                                        <span class="aprioEXP-card-numbers-title">Posts</span>
                                        <span class="aprioEXP-card-numbers-numbers"><?php echo $tabs['metrics']['data']['posts'] ?? 0; ?></span>
                                    </div>
                                    <div class="aprioEXP-stat-card">
                                        <span class="aprioEXP-card-numbers-title">Comments</span>
                                        <span class="aprioEXP-card-numbers-numbers"><?php echo $tabs['metrics']['data']['comments'] ?? 0; ?></span>
                                    </div>
                                    <div class="aprioEXP-stat-card">
                                        <span class="aprioEXP-card-numbers-title">Liked</span>
                                        <span class="aprioEXP-card-numbers-numbers"><?php echo $tabs['metrics']['data']['liked'] ?? 0; ?></span>
                                    </div>
                                    <div class="aprioEXP-stat-card">
                                        <span class="aprioEXP-card-numbers-title">Memberships</span>
                                        <span class="aprioEXP-card-numbers-listing">
                                            DJ ◦ Producer<br/>Hostess ◦ Apollo
                                        </span>
                                    </div>
                                </div>
                                <button class="aprioEXP-edit-btn">
                                    <i class="ri-pencil-line text-xs"></i>
                                    <span>Editar perfil interno</span>
                                </button>
                            </div>
                        </div>
                    </section>

                    <!-- Tabs + content -->
                    <section class="aprioEXP-card-shell p-3 md:p-4">
                        <!-- Tabs header -->
                        <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-200 pb-2">
                            <div class="flex flex-wrap gap-1 md:gap-2" role="tablist" aria-label="Navegação do perfil">
                                <!-- 1. Eventos favoritos (ACTIVE) -->
                                <button class="aprioEXP-tab-btn" type="button" data-tab-target="events" data-active="true" role="tab" aria-selected="true">
                                    <i class="ri-heart-3-line text-[13px]"></i>
                                    <span><?php echo esc_html($tabs['events']['title'] ?? 'Eventos favoritos'); ?></span>
                                </button>
                                <!-- 2. Meus eventos -->
                                <?php if (isset($tabs['my_events'])): ?>
                                <button class="aprioEXP-tab-btn" type="button" data-tab-target="my_events" role="tab" aria-selected="false">
                                    <i class="ri-calendar-event-line text-[13px]"></i>
                                    <span><?php echo esc_html($tabs['my_events']['title'] ?? 'Meus eventos'); ?></span>
                                </button>
                                <?php endif; ?>
                                <!-- 3. Meus números -->
                                <button class="aprioEXP-tab-btn" type="button" data-tab-target="metrics" role="tab" aria-selected="false">
                                    <i class="ri-bar-chart-2-line text-[13px]"></i>
                                    <span><?php echo esc_html($tabs['metrics']['title'] ?? 'Meus números'); ?></span>
                                </button>
                                <!-- 4. Núcleo (privado) -->
                                <button class="aprioEXP-tab-btn" type="button" data-tab-target="nucleo" role="tab" aria-selected="false">
                                    <i class="ri-lock-2-line text-[13px]"></i>
                                    <span><?php echo esc_html($tabs['nucleo']['title'] ?? 'Núcleo (privado)'); ?></span>
                                </button>
                                <!-- 4. Comunidades -->
                                <button class="aprioEXP-tab-btn" type="button" data-tab-target="communities" role="tab" aria-selected="false">
                                    <i class="ri-community-line text-[13px]"></i>
                                    <span><?php echo esc_html($tabs['communities']['title'] ?? 'Comunidades'); ?></span>
                                </button>
                                <!-- 5. Documentos -->
                                <button class="aprioEXP-tab-btn" type="button" data-tab-target="docs" role="tab" aria-selected="false">
                                    <i class="ri-file-text-line text-[13px]"></i>
                                    <span><?php echo esc_html($tabs['docs']['title'] ?? 'Documentos'); ?></span>
                                </button>
                            </div>
                            <div class="flex items-center gap-2 text-[11px] text-slate-500">
                                <span class="hidden sm:inline">Fluxo interno Apollo Social</span>
                                <span class="h-4 w-px bg-slate-200"></span>
                                <span class="aprioEXP-metric-chip">
                                    <i class="ri-shining-fill text-[12px]"></i>
                                    <span>Motion tabs</span>
                                </span>
                            </div>
                        </div>

                        <!-- Tabs content -->
                        <div class="mt-3 space-y-4">
                            
                            <!-- TAB: Eventos favoritos -->
                            <div data-tab-panel="events" role="tabpanel" class="space-y-3">
                                <div class="flex flex-col md:flex-row md:items-center gap-3">
                                    <div class="flex-1">
                                        <h2 class="text-sm font-semibold">Eventos favoritados</h2>
                                        <p class="text-[12px] text-slate-600">
                                            Eventos que você marcou como <b>Ir</b>, <b>Talvez</b> ou salvou para acompanhar.
                                        </p>
                                    </div>
                                    <button class="inline-flex items-center gap-1 rounded-md border border-slate-200 bg-white px-3 py-1.5 text-[11px] font-medium text-slate-700 hover:bg-slate-50">
                                        <i class="ri-filter-3-line text-xs"></i>
                                        <span>Filtrar por data</span>
                                    </button>
                                </div>
                                <div class="grid gap-3 md:grid-cols-2 text-[12px]">
                                    <?php if (empty($tabs['events']['data'])): ?>
                                        <div class="col-span-full text-center py-8 text-slate-400">
                                            Nenhum evento favoritado ainda
                                        </div>
                                    <?php else: ?>
                                        <?php foreach ($tabs['events']['data'] as $event): ?>
                                            <article class="aprioEXP-card-shell p-3 flex flex-col justify-between">
                                                <div class="flex items-start justify-between gap-2">
                                                    <div>
                                                        <h3 class="text-sm font-semibold"><?php echo esc_html($event['title'] ?? 'Evento'); ?></h3>
                                                        <p class="text-[11px] text-slate-600"><?php echo esc_html($event['date'] ?? ''); ?></p>
                                                    </div>
                                                </div>
                                                <div class="mt-3 flex items-center justify-between text-[11px] text-slate-500">
                                                    <button class="inline-flex items-center gap-1 rounded-md px-2 py-1 hover:bg-slate-100">
                                                        <i class="ri-external-link-line text-xs"></i>
                                                        <span>Ver evento</span>
                                                    </button>
                                                </div>
                                            </article>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- TAB: Meus eventos -->
                            <?php if (isset($tabs['my_events'])): ?>
                            <div data-tab-panel="my_events" role="tabpanel" class="hidden space-y-3">
                                <div class="flex flex-col md:flex-row md:items-center gap-3">
                                    <div class="flex-1">
                                        <h2 class="text-sm font-semibold">Meus eventos</h2>
                                        <p class="text-[12px] text-slate-600">
                                            Eventos que você criou ou é co-autor.
                                        </p>
                                    </div>
                                    <a href="/enviar/" class="inline-flex items-center gap-1 rounded-md bg-slate-90099 px-3 py-1.5 text-[11px] font-medium text-white">
                                        <i class="ri-add-line text-xs"></i>
                                        <span>Criar novo evento</span>
                                    </a>
                                </div>
                                <div class="grid gap-3 md:grid-cols-2 text-[12px]">
                                    <?php if (empty($tabs['my_events']['data'])): ?>
                                        <div class="col-span-full text-center py-8 text-slate-400">
                                            Nenhum evento criado ainda
                                        </div>
                                    <?php else: ?>
                                        <?php foreach ($tabs['my_events']['data'] as $event): ?>
                                            <article class="aprioEXP-card-shell p-3 flex flex-col justify-between">
                                                <div class="flex items-start justify-between gap-2">
                                                    <div>
                                                        <div class="flex items-center gap-2 mb-1">
                                                            <h3 class="text-sm font-semibold"><?php echo esc_html($event['title'] ?? 'Evento'); ?></h3>
                                                            <?php if ($event['status'] !== 'publish'): ?>
                                                                <span class="aprioEXP-badge-private">
                                                                    <?php echo esc_html(ucfirst($event['status'])); ?>
                                                                </span>
                                                            <?php endif; ?>
                                                        </div>
                                                        <p class="text-[11px] text-slate-600"><?php echo esc_html($event['date'] ?? ''); ?></p>
                                                        <?php if ($event['is_coauthor']): ?>
                                                            <p class="text-[10px] text-slate-500 mt-1">Co-autor</p>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                                <div class="mt-3 flex items-center justify-between text-[11px] text-slate-500">
                                                    <a href="<?php echo esc_url($event['permalink'] ?? '#'); ?>" class="inline-flex items-center gap-1 rounded-md px-2 py-1 hover:bg-slate-100">
                                                        <i class="ri-external-link-line text-xs"></i>
                                                        <span>Ver evento</span>
                                                    </a>
                                                </div>
                                            </article>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endif; ?>

                            <!-- TAB: Meus números -->
                            <div data-tab-panel="metrics" role="tabpanel" class="hidden space-y-3">
                                <div class="grid gap-3 md:grid-cols-3 text-[12px]">
                                    <div class="aprioEXP-card-shell p-4 text-center">
                                        <div class="text-2xl font-bold text-slate-900"><?php echo esc_html($tabs['metrics']['data']['posts'] ?? 0); ?></div>
                                        <div class="text-[11px] text-slate-600 mt-1">Posts</div>
                                    </div>
                                    <div class="aprioEXP-card-shell p-4 text-center">
                                        <div class="text-2xl font-bold text-slate-900"><?php echo esc_html($tabs['metrics']['data']['events'] ?? 0); ?></div>
                                        <div class="text-[11px] text-slate-600 mt-1">Eventos</div>
                                    </div>
                                    <div class="aprioEXP-card-shell p-4 text-center">
                                        <div class="text-2xl font-bold text-slate-900"><?php echo esc_html($tabs['metrics']['data']['favorites'] ?? 0); ?></div>
                                        <div class="text-[11px] text-slate-600 mt-1">Favoritos</div>
                                    </div>
                                    <div class="aprioEXP-card-shell p-4 text-center">
                                        <div class="text-2xl font-bold text-slate-900"><?php echo esc_html($tabs['metrics']['data']['comments'] ?? 0); ?></div>
                                        <div class="text-[11px] text-slate-600 mt-1">Comentários</div>
                                    </div>
                                    <div class="aprioEXP-card-shell p-4 text-center">
                                        <div class="text-2xl font-bold text-slate-900"><?php echo esc_html($tabs['metrics']['data']['likes_given'] ?? 0); ?></div>
                                        <div class="text-[11px] text-slate-600 mt-1">Curtidas</div>
                                    </div>
                                    <div class="aprioEXP-card-shell p-4 text-center">
                                        <div class="text-2xl font-bold text-slate-900"><?php echo esc_html($tabs['metrics']['data']['communities'] ?? 0); ?></div>
                                        <div class="text-[11px] text-slate-600 mt-1">Comunidades</div>
                                    </div>
                                </div>
                            </div>

                            <!-- TAB: Núcleo (privado) -->
                            <div data-tab-panel="nucleo" role="tabpanel" class="hidden space-y-3">
                                <div class="flex flex-col md:flex-row md:items-center gap-3">
                                    <div class="flex-1">
                                        <h2 class="text-sm font-semibold">Núcleos privados</h2>
                                        <p class="text-[12px] text-slate-600">
                                            Espaços de trabalho e coordenação fechados, visíveis apenas para quem tem convite.
                                        </p>
                                    </div>
                                    <div class="flex flex-wrap gap-2 text-[11px]">
                                        <button class="inline-flex items-center gap-1 rounded-md bg-slate-90099 px-3 py-1.5 font-medium text-white">
                                            <i class="ri-team-line text-xs"></i>
                                            <span>Criar novo núcleo</span>
                                        </button>
                                    </div>
                                </div>
                                <div class="grid gap-3 md:grid-cols-2 text-[12px]">
                                    <?php if (empty($tabs['nucleo']['data'])): ?>
                                        <div class="col-span-full text-center py-8 text-slate-400">
                                            Nenhum núcleo ainda
                                        </div>
                                    <?php else: ?>
                                        <?php foreach ($tabs['nucleo']['data'] as $nucleo): ?>
                                            <article class="aprioEXP-card-shell p-3 flex flex-col justify-between">
                                                <div class="flex items-start justify-between gap-2">
                                                    <div>
                                                        <div class="flex items-center gap-2 mb-1">
                                                            <h3 class="text-sm font-semibold"><?php echo esc_html($nucleo['title'] ?? 'Núcleo'); ?></h3>
                                                            <span class="aprioEXP-badge-private">
                                                                <i class="ri-lock-2-line text-[11px]"></i>
                                                                Privado
                                                            </span>
                                                        </div>
                                                        <p class="text-slate-600 text-[11px] line-clamp-2"><?php echo esc_html($nucleo['description'] ?? ''); ?></p>
                                                    </div>
                                                </div>
                                            </article>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- TAB: Comunidades -->
                            <div data-tab-panel="communities" role="tabpanel" class="hidden space-y-3">
                                <div class="flex flex-col md:flex-row md:items-center gap-3">
                                    <div class="flex-1">
                                        <h2 class="text-sm font-semibold">Comunidades públicas</h2>
                                        <p class="text-[12px] text-slate-600">
                                            Grupos abertos para a cena, onde qualquer pessoa pode participar ou seguir.
                                        </p>
                                    </div>
                                    <button class="inline-flex items-center gap-1 rounded-md bg-slate-90099 px-3 py-1.5 text-[11px] font-medium text-white">
                                        <i class="ri-community-line text-xs"></i>
                                        <span>Criar nova comunidade</span>
                                    </button>
                                </div>
                                <div class="grid gap-3 md:grid-cols-3 text-[12px]">
                                    <?php if (empty($tabs['communities']['data'])): ?>
                                        <div class="col-span-full text-center py-8 text-slate-400">
                                            Nenhuma comunidade ainda
                                        </div>
                                    <?php else: ?>
                                        <?php foreach ($tabs['communities']['data'] as $community): ?>
                                            <article class="aprioEXP-card-shell p-3 flex flex-col justify-between">
                                                <div>
                                                    <div class="flex items-center gap-2 mb-1">
                                                        <h3 class="text-sm font-semibold"><?php echo esc_html($community['title'] ?? 'Comunidade'); ?></h3>
                                                        <span class="aprioEXP-badge-public">
                                                            <i class="ri-sun-line text-[11px]"></i>
                                                            Aberta
                                                        </span>
                                                    </div>
                                                    <p class="text-slate-600 text-[11px] line-clamp-2"><?php echo esc_html($community['description'] ?? ''); ?></p>
                                                </div>
                                            </article>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- TAB: Documentos -->
                            <div data-tab-panel="docs" role="tabpanel" class="hidden space-y-3">
                                <div class="flex flex-col md:flex-row md:items-center gap-3">
                                    <div class="flex-1">
                                        <h2 class="text-sm font-semibold">Documentos para assinar ou criar</h2>
                                        <p class="text-[12px] text-slate-600">
                                            Fluxo rápido para contratos de DJ, staff, núcleos e parcerias de eventos.
                                        </p>
                                    </div>
                                </div>
                                <div class="grid gap-3 md:grid-cols-3 text-[12px]">
                                    <?php if (empty($tabs['docs']['data'])): ?>
                                        <div class="col-span-full text-center py-8 text-slate-400">
                                            Nenhum documento ainda
                                        </div>
                                    <?php else: ?>
                                        <?php foreach ($tabs['docs']['data'] as $doc): ?>
                                            <article class="aprioEXP-card-shell p-3">
                                                <div class="flex items-start justify-between gap-2 mb-1">
                                                    <span class="font-semibold"><?php echo esc_html($doc['title'] ?? 'Documento'); ?></span>
                                                    <span class="inline-flex items-center gap-1 rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-semibold text-amber-900">
                                                        <span class="inline-block h-1.5 w-1.5 rounded-full bg-amber-500"></span>
                                                        <?php echo esc_html($doc['status'] ?? 'Pendente'); ?>
                                                    </span>
                                                </div>
                                            </article>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>

                        </div>
                    </section>
                </div>

                <!-- RIGHT COLUMN: Sidebar -->
                <div class="space-y-4">
                    <!-- Resumo rápido Card -->
                    <section class="aprioEXP-card-shell p-4">
                        <h3 class="text-sm font-semibold mb-3">Resumo rápido</h3>
                        <ul class="space-y-3 text-[12px] text-slate-600">
                            <li class="flex gap-2">
                                <i class="ri-calendar-event-line text-slate-400 mt-0.5"></i>
                                <div>
                                    <span class="block font-medium text-slate-900">Próximo compromisso</span>
                                    <span>Ver eventos favoritos</span>
                                </div>
                            </li>
                            <li class="flex gap-2">
                                <i class="ri-file-text-line text-slate-400 mt-0.5"></i>
                                <div>
                                    <span class="block font-medium text-slate-900">Docs pendentes</span>
                                    <span><?php echo count($tabs['docs']['data'] ?? []); ?> documento(s)</span>
                                </div>
                            </li>
                        </ul>
                    </section>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
window.apolloDashboardData = {
    ajaxUrl: <?php echo json_encode($ajax_url); ?>,
    nonce: <?php echo json_encode($nonce); ?>,
    userId: <?php echo absint($user['id'] ?? 0); ?>,
    tabs: <?php echo json_encode($tabs); ?>
};
</script>

