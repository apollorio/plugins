<?php
/**
 * P0-10: CENA::rio Template - Apollo Social
 * 
 * Renders CENA RIO page with monthly calendar and event management.
 * 
 * @package Apollo_Social
 * @version 2.0.0
 */

if (!defined('ABSPATH')) exit;

$user = $view['data']['user'] ?? [];
$calendar = $view['data']['calendar'] ?? [];
$pending_events = $view['data']['pending_events'] ?? [];
$event_plans = $view['data']['event_plans'] ?? [];

$current_month = $calendar['current_month'] ?? date('Y-m');
$events_by_date = $calendar['events'] ?? [];

$ajax_url = admin_url('admin-ajax.php');
$rest_url = rest_url('apollo/v1');
$nonce = wp_create_nonce('wp_rest');
$is_mod = $user['is_mod'] ?? false;
$has_cena_rio_role = $user['has_cena_rio_role'] ?? false;
?>

<div class="apollo-cena-page aprioEXP-body" id="apollo-cena-root">
    
    <!-- Header -->
    <header class="apollo-cena-header sticky top-0 z-50 bg-white/90 backdrop-blur border-b border-slate-200">
        <div class="max-w-7xl mx-auto px-4 md:px-6 h-16 flex items-center justify-between">
            <div class="flex items-center gap-4">
                <div class="h-9 w-9 rounded-full bg-gradient-to-br from-orange-400 to-orange-600 flex items-center justify-center">
                    <i class="ri-slack-fill text-white text-[28px]"></i>
                </div>
                <div>
                    <h1 class="text-[16px] font-extrabold text-slate-900">Cena::rio</h1>
                    <p class="text-[14px] text-slate-500">Curadoria e planejamento</p>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <a href="/feed/" class="text-sm font-medium text-slate-600 hover:text-slate-900">Feed</a>
                <a href="/painel/" class="text-sm font-medium text-slate-600 hover:text-slate-900">Painel</a>
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
                <p class="text-slate-600">Calendário mensal e planejamento de eventos da cena eletrônica carioca.</p>
            </section>

            <div class="grid lg:grid-cols-3 gap-6">
                
                <!-- LEFT COLUMN: Calendar -->
                <div class="lg:col-span-2 space-y-6">
                    
                    <!-- Calendar Card -->
                    <section class="aprioEXP-card-shell p-6">
                        <div class="flex items-center justify-between mb-4">
                            <div>
                                <h3 class="text-xl font-semibold text-slate-900">Calendário Mensal</h3>
                                <p class="text-sm text-slate-600">Eventos previstos e confirmados</p>
                            </div>
                            <div class="flex items-center gap-2">
                                <button id="prev-month" class="h-8 w-8 flex items-center justify-center rounded-full hover:bg-slate-100">
                                    <i class="ri-arrow-left-s-line text-slate-600"></i>
                                </button>
                                <span id="month-label" class="text-sm font-semibold text-slate-900 px-3">
                                    <?php echo date_i18n('F Y', strtotime($current_month . '-01')); ?>
                                </span>
                                <button id="next-month" class="h-8 w-8 flex items-center justify-center rounded-full hover:bg-slate-100">
                                    <i class="ri-arrow-right-s-line text-slate-600"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Calendar Grid -->
                        <div class="bg-slate-50 rounded-xl p-3 border border-slate-100">
                            <div class="grid grid-cols-7 gap-1 text-xs text-slate-500 text-center mb-2 font-medium">
                                <div>Dom</div>
                                <div>Seg</div>
                                <div>Ter</div>
                                <div>Qua</div>
                                <div>Qui</div>
                                <div>Sex</div>
                                <div>Sáb</div>
                            </div>
                            <div id="calendar-grid" class="grid grid-cols-7 gap-1">
                                <!-- Calendar days rendered by JavaScript -->
                            </div>
                        </div>

                        <!-- Legend -->
                        <div class="flex items-center gap-4 mt-4 text-xs text-slate-600">
                            <div class="flex items-center gap-2">
                                <div class="w-3 h-3 rounded-full bg-orange-500"></div>
                                <span>Evento confirmado</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <div class="w-3 h-3 rounded-full bg-slate-300"></div>
                                <span>Evento previsto</span>
                            </div>
                        </div>
                    </section>

                    <!-- Events List for Selected Date -->
                    <section id="events-card" class="aprioEXP-card-shell p-6">
                        <h3 id="selected-day-label" class="text-lg font-semibold text-slate-900 mb-4">
                            Selecione uma data no calendário
                        </h3>
                        <div id="events-list" class="space-y-3">
                            <!-- Events rendered by JavaScript -->
                        </div>
                    </section>

                </div>

                <!-- RIGHT COLUMN: Actions & Pending -->
                <div class="space-y-6">
                    
                    <!-- Add Event Form (cena-rio role only) -->
                    <?php if ($has_cena_rio_role): ?>
                    <section class="aprioEXP-card-shell p-6">
                        <h3 class="text-lg font-semibold text-slate-900 mb-4">Adicionar Evento Previsto</h3>
                        <form id="cena-add-event-form" class="space-y-4">
                            <?php wp_nonce_field('wp_rest'); ?>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">Título do Evento</label>
                                <input type="text" name="title" required 
                                       class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">Data</label>
                                <input type="date" name="date" required 
                                       class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">Hora</label>
                                <input type="time" name="time" value="20:00" 
                                       class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">URL de Ingressos (opcional)</label>
                                <input type="url" name="ticket_url" 
                                       placeholder="https://..."
                                       class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent">
                            </div>
                            <button type="submit" class="w-full px-4 py-2 bg-orange-600 text-white rounded-lg font-medium hover:bg-orange-700 transition-colors">
                                Adicionar Evento Previsto
                            </button>
                        </form>
                    </section>
                    <?php endif; ?>

                    <!-- Pending Events (MOD/ADMIN only) -->
                    <?php if ($is_mod && !empty($pending_events)): ?>
                    <section class="aprioEXP-card-shell p-6">
                        <h3 class="text-lg font-semibold text-slate-900 mb-4">Eventos Pendentes</h3>
                        <div class="space-y-3">
                            <?php foreach ($pending_events as $event): ?>
                                <div class="p-3 bg-slate-50 rounded-lg border border-slate-200">
                                    <div class="flex items-start justify-between mb-2">
                                        <div>
                                            <h4 class="font-semibold text-sm text-slate-900"><?php echo esc_html($event['title']); ?></h4>
                                            <p class="text-xs text-slate-600"><?php echo esc_html($event['date']); ?></p>
                                            <p class="text-xs text-slate-500">Por: <?php echo esc_html($event['author']['name']); ?></p>
                                        </div>
                                        <span class="px-2 py-1 text-xs font-medium rounded-full bg-amber-100 text-amber-900">
                                            <?php echo esc_html(ucfirst($event['status'])); ?>
                                        </span>
                                    </div>
                                    <?php if ($event['ticket_url']): ?>
                                        <p class="text-xs text-slate-600 mb-2">
                                            <i class="ri-ticket-line"></i> 
                                            <a href="<?php echo esc_url($event['ticket_url']); ?>" target="_blank" class="text-orange-600 hover:underline">
                                                Ver ingressos
                                            </a>
                                        </p>
                                    <?php endif; ?>
                                    <button class="approve-event-btn w-full px-3 py-1.5 bg-emerald-600 text-white text-xs font-medium rounded-lg hover:bg-emerald-700 transition-colors"
                                            data-event-id="<?php echo esc_attr($event['id']); ?>">
                                        Aprovar e Publicar
                                    </button>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </section>
                    <?php endif; ?>

                </div>

            </div>

        </div>
    </main>
</div>

<script>
window.apolloCenaData = {
    ajaxUrl: <?php echo json_encode($ajax_url); ?>,
    restUrl: <?php echo json_encode($rest_url); ?>,
    nonce: <?php echo json_encode($nonce); ?>,
    currentMonth: <?php echo json_encode($current_month); ?>,
    events: <?php echo json_encode($events_by_date); ?>,
    userId: <?php echo absint($user['id'] ?? 0); ?>,
    hasCenaRioRole: <?php echo $has_cena_rio_role ? 'true' : 'false'; ?>,
    isMod: <?php echo $is_mod ? 'true' : 'false'; ?>
};
</script>
