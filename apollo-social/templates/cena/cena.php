<?php
/**
 * Cena::rio Template - Apollo Social
 * Based on CodePen: https://codepen.io/Rafael-Valle-the-looper/pen/ogxeJyz
 * 
 * Renders Cena::rio page with events, communities, and scene data
 */

if (!defined('ABSPATH')) exit;

$user = $view['data']['user'] ?? [];
$cena = $view['data']['cena'] ?? [];
$events = $cena['events'] ?? [];
$communities = $cena['communities'] ?? [];
$nucleos = $cena['nucleos'] ?? [];
?>

<div class="apollo-cena-page" id="apollo-cena-root">
    
    <!-- Header -->
    <header class="apollo-cena-header sticky top-0 z-50 bg-white/90 backdrop-blur border-b border-slate-200">
        <div class="max-w-7xl mx-auto px-4 md:px-6 h-16 flex items-center justify-between">
            <div class="flex items-center gap-4">
                <div class="h-9 w-9 rounded-full bg-gradient-to-br from-orange-400 to-orange-600 flex items-center justify-center">
                    <i class="ri-slack-fill text-white text-[28px]"></i>
                </div>
                <div>
                    <h1 class="text-[16px] font-extrabold text-slate-900">Cena::rio</h1>
                    <p class="text-[14px] text-slate-500">Cena eletrônica carioca</p>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <button class="h-9 w-9 rounded-full overflow-hidden ring-2 ring-white shadow-sm">
                    <img src="<?php echo esc_url($user['avatar'] ?? ''); ?>" alt="<?php echo esc_attr($user['name'] ?? ''); ?>" class="h-full w-full object-cover" />
                </button>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="flex-1 px-4 md:px-6 py-8">
        <div class="max-w-7xl mx-auto">
            
            <!-- Hero Section -->
            <section class="mb-8">
                <h2 class="text-3xl font-bold text-slate-900 mb-2">Cena::rio</h2>
                <p class="text-slate-600">Curadoria de eventos, dados e registros da cena eletrônica carioca.</p>
            </section>

            <!-- Stats Grid -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
                <div class="bg-white rounded-xl p-6 border border-slate-200">
                    <div class="text-3xl font-bold text-slate-900"><?php echo count($events); ?></div>
                    <div class="text-sm text-slate-600 mt-1">Eventos ativos</div>
                </div>
                <div class="bg-white rounded-xl p-6 border border-slate-200">
                    <div class="text-3xl font-bold text-slate-900"><?php echo count($communities); ?></div>
                    <div class="text-sm text-slate-600 mt-1">Comunidades</div>
                </div>
                <div class="bg-white rounded-xl p-6 border border-slate-200">
                    <div class="text-3xl font-bold text-slate-900"><?php echo count($nucleos); ?></div>
                    <div class="text-sm text-slate-600 mt-1">Núcleos</div>
                </div>
            </div>

            <!-- Events Section -->
            <section class="mb-8">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-xl font-semibold text-slate-900">Próximos Eventos</h3>
                    <a href="/feed/" class="text-sm text-orange-600 hover:text-orange-700">Ver todos</a>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <?php if (empty($events)): ?>
                        <div class="col-span-full text-center py-8 text-slate-400">
                            Nenhum evento cadastrado ainda
                        </div>
                    <?php else: ?>
                        <?php foreach (array_slice($events, 0, 6) as $event): ?>
                            <article class="bg-white rounded-xl border border-slate-200 overflow-hidden hover:shadow-md transition-all">
                                <div class="p-4">
                                    <h4 class="font-semibold text-slate-900 mb-2"><?php echo esc_html($event['title'] ?? 'Evento'); ?></h4>
                                    <p class="text-sm text-slate-600 mb-3"><?php echo esc_html($event['date'] ?? ''); ?></p>
                                    <p class="text-sm text-slate-500 line-clamp-2"><?php echo esc_html($event['description'] ?? ''); ?></p>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </section>

            <!-- Communities Section -->
            <section class="mb-8">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-xl font-semibold text-slate-900">Comunidades</h3>
                    <a href="/comunidade/" class="text-sm text-orange-600 hover:text-orange-700">Ver todas</a>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <?php if (empty($communities)): ?>
                        <div class="col-span-full text-center py-8 text-slate-400">
                            Nenhuma comunidade cadastrada ainda
                        </div>
                    <?php else: ?>
                        <?php foreach (array_slice($communities, 0, 4) as $community): ?>
                            <article class="bg-white rounded-xl border border-slate-200 p-4 hover:shadow-md transition-all">
                                <h4 class="font-semibold text-slate-900 mb-2"><?php echo esc_html($community['title'] ?? 'Comunidade'); ?></h4>
                                <p class="text-sm text-slate-600"><?php echo esc_html($community['member_count'] ?? 0); ?> membros</p>
                            </article>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </section>

            <!-- Nucleos Section -->
            <section>
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-xl font-semibold text-slate-900">Núcleos Privados</h3>
                    <a href="/nucleo/" class="text-sm text-orange-600 hover:text-orange-700">Ver todos</a>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <?php if (empty($nucleos)): ?>
                        <div class="col-span-full text-center py-8 text-slate-400">
                            Nenhum núcleo cadastrado ainda
                        </div>
                    <?php else: ?>
                        <?php foreach (array_slice($nucleos, 0, 3) as $nucleo): ?>
                            <article class="bg-white rounded-xl border border-slate-200 p-4 hover:shadow-md transition-all">
                                <div class="flex items-center gap-2 mb-2">
                                    <i class="ri-lock-2-line text-slate-400"></i>
                                    <h4 class="font-semibold text-slate-900"><?php echo esc_html($nucleo['title'] ?? 'Núcleo'); ?></h4>
                                </div>
                                <p class="text-sm text-slate-600"><?php echo esc_html($nucleo['member_count'] ?? 0); ?> membros</p>
                            </article>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </section>

        </div>
    </main>
</div>

