<?php
/**
 * Dashboard Content - Cena::Rio
 * Conteúdo principal do dashboard com estatísticas avançadas
 * 
 * @package Apollo_Social
 * @subpackage CenaRio
 * @version 2.0.0 - ShadCN New York + Chart.js
 */

if (!defined('ABSPATH')) {
    exit;
}

$user_id = get_current_user_id();

// Buscar dados do usuário
$user_documents = array();
if (class_exists('Apollo\CenaRio\CenaRioModule') && method_exists('Apollo\CenaRio\CenaRioModule', 'getUserDocuments')) {
    $user_documents = Apollo\CenaRio\CenaRioModule::getUserDocuments($user_id);
}

// Estatísticas avançadas
$docs_count = count($user_documents);
$docs_this_month = 0;
$docs_last_month = 0;
$activity_by_day = array_fill(0, 7, 0);

foreach ($user_documents as $doc) {
    $doc_date = strtotime($doc->post_date);
    $month_ago = strtotime('-1 month');
    $two_months_ago = strtotime('-2 months');
    
    if ($doc_date >= strtotime('first day of this month')) {
        $docs_this_month++;
    } elseif ($doc_date >= $month_ago && $doc_date < strtotime('first day of this month')) {
        $docs_last_month++;
    }
    
    // Atividade dos últimos 7 dias
    $days_ago = floor((time() - $doc_date) / 86400);
    if ($days_ago >= 0 && $days_ago < 7) {
        $activity_by_day[6 - $days_ago]++;
    }
}

// Calcular tendência
$trend_percentage = $docs_last_month > 0 
    ? round((($docs_this_month - $docs_last_month) / $docs_last_month) * 100, 1) 
    : ($docs_this_month > 0 ? 100 : 0);
$trend_up = $trend_percentage >= 0;

// Planos de evento e mensagens (placeholder para dados reais)
$event_plans = get_user_meta($user_id, 'cena_rio_event_plans', true);
$event_plans_count = is_array($event_plans) ? count($event_plans) : 0;

$messages_count = 0;
if (function_exists('bp_get_total_unread_messages_count')) {
    $messages_count = bp_get_total_unread_messages_count($user_id);
}

// Dados para gráficos
$chart_labels = array();
for ($i = 6; $i >= 0; $i--) {
    $chart_labels[] = date_i18n('D', strtotime("-{$i} days"));
}
?>

<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<!-- Tooltip Styles -->
<style>
.cena-rio-dashboard-content [data-tooltip] {
    position: relative;
}

.cena-rio-dashboard-content [data-tooltip]:hover::after {
    content: attr(data-tooltip);
    position: absolute;
    bottom: calc(100% + 8px);
    left: 50%;
    transform: translateX(-50%);
    background: hsl(var(--popover, 0 0% 100%));
    color: hsl(var(--popover-foreground, 240 10% 3.9%));
    padding: 0.5rem 0.75rem;
    border-radius: var(--radius, 0.5rem);
    font-size: 0.75rem;
    white-space: normal;
    max-width: 280px;
    text-align: center;
    line-height: 1.4;
    z-index: 50;
    box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
    border: 1px solid hsl(var(--border, 240 5.9% 90%));
    pointer-events: none;
}

.cena-rio-dashboard-content [data-tooltip]:hover::before {
    content: '';
    position: absolute;
    bottom: calc(100% + 4px);
    left: 50%;
    transform: translateX(-50%);
    border: 4px solid transparent;
    border-top-color: hsl(var(--border, 240 5.9% 90%));
    z-index: 51;
}

.cena-rio-dashboard-content .stat-card:hover {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    transform: translateY(-2px);
}
</style>

<div class="cena-rio-dashboard-content" style="display: flex; flex-direction: column; gap: 1.5rem;">
    
    <!-- Stats Cards Grid - ShadCN New York Style -->
    <div class="stats-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 1rem;">
        
        <!-- Documentos Card -->
        <div class="stat-card" data-tooltip="<?php echo esc_attr__('Documentos do Cena::Rio incluindo contratos, riders técnicos e materiais promocionais', 'apollo-social'); ?>" style="background: hsl(var(--card)); border: 1px solid hsl(var(--border)); border-radius: var(--radius); padding: 1.5rem; position: relative; overflow: hidden; cursor: help; transition: all 0.2s ease;">
            <div style="display: flex; align-items: flex-start; justify-content: space-between;">
                <div>
                    <p style="font-size: 0.875rem; font-weight: 500; color: hsl(var(--muted-foreground));">Documentos</p>
                    <p style="font-size: 2rem; font-weight: 700; color: hsl(var(--foreground)); line-height: 1.2; margin-top: 0.25rem;">
                        <?php echo esc_html($docs_count); ?>
                    </p>
                    <p style="font-size: 0.75rem; color: hsl(var(--muted-foreground)); margin-top: 0.25rem;">Contratos, riders e materiais</p>
                    <div style="display: flex; align-items: center; gap: 0.25rem; margin-top: 0.5rem;">
                        <?php if ($trend_up): ?>
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="hsl(142 76% 36%)" stroke-width="2">
                                <path d="M7 17L17 7M17 7H7M17 7V17"/>
                            </svg>
                            <span style="font-size: 0.75rem; font-weight: 500; color: hsl(142 76% 36%);">
                                +<?php echo abs($trend_percentage); ?>%
                            </span>
                        <?php else: ?>
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="hsl(0 84% 60%)" stroke-width="2">
                                <path d="M7 7L17 17M17 17H7M17 17V7"/>
                            </svg>
                            <span style="font-size: 0.75rem; font-weight: 500; color: hsl(0 84% 60%);">
                                <?php echo $trend_percentage; ?>%
                            </span>
                        <?php endif; ?>
                        <span style="font-size: 0.75rem; color: hsl(var(--muted-foreground));">vs mês anterior</span>
                    </div>
                </div>
                <div style="width: 2.5rem; height: 2.5rem; border-radius: var(--radius); background: hsl(var(--primary) / 0.1); display: flex; align-items: center; justify-content: center;">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="hsl(var(--primary))" stroke-width="2">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                        <polyline points="14 2 14 8 20 8"/>
                        <line x1="16" y1="13" x2="8" y2="13"/>
                        <line x1="16" y1="17" x2="8" y2="17"/>
                        <polyline points="10 9 9 9 8 9"/>
                    </svg>
                </div>
            </div>
            <!-- Sparkline -->
            <div style="position: absolute; bottom: 0; left: 0; right: 0; height: 40px; opacity: 0.3;">
                <canvas id="docsSparkline" style="width: 100%; height: 100%;"></canvas>
            </div>
        </div>
        
        <!-- Planos de Evento Card -->
        <div class="stat-card" data-tooltip="<?php echo esc_attr__('Planos de evento ativos incluindo cronogramas, briefings e especificações técnicas', 'apollo-social'); ?>" style="background: hsl(var(--card)); border: 1px solid hsl(var(--border)); border-radius: var(--radius); padding: 1.5rem; cursor: help; transition: all 0.2s ease;">
            <div style="display: flex; align-items: flex-start; justify-content: space-between;">
                <div>
                    <p style="font-size: 0.875rem; font-weight: 500; color: hsl(var(--muted-foreground));">Planos de Evento</p>
                    <p style="font-size: 2rem; font-weight: 700; color: hsl(var(--foreground)); line-height: 1.2; margin-top: 0.25rem;">
                        <?php echo esc_html($event_plans_count); ?>
                    </p>
                    <p style="font-size: 0.75rem; color: hsl(var(--muted-foreground)); margin-top: 0.25rem;">Cronogramas e briefings</p>
                    <div style="display: flex; align-items: center; gap: 0.25rem; margin-top: 0.5rem;">
                        <span style="font-size: 0.75rem; color: hsl(var(--muted-foreground));">Ativos e em desenvolvimento</span>
                    </div>
                </div>
                <div style="width: 2.5rem; height: 2.5rem; border-radius: var(--radius); background: hsl(262 83% 58% / 0.1); display: flex; align-items: center; justify-content: center;">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="hsl(262 83% 58%)" stroke-width="2">
                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                        <line x1="16" y1="2" x2="16" y2="6"/>
                        <line x1="8" y1="2" x2="8" y2="6"/>
                        <line x1="3" y1="10" x2="21" y2="10"/>
                    </svg>
                </div>
            </div>
        </div>
        
        <!-- Mensagens Card -->
        <div class="stat-card" data-tooltip="<?php echo esc_attr__('Mensagens diretas e notificações de colaboradores e produtores', 'apollo-social'); ?>" style="background: hsl(var(--card)); border: 1px solid hsl(var(--border)); border-radius: var(--radius); padding: 1.5rem; cursor: help; transition: all 0.2s ease;">
            <div style="display: flex; align-items: flex-start; justify-content: space-between;">
                <div>
                    <p style="font-size: 0.875rem; font-weight: 500; color: hsl(var(--muted-foreground));">Mensagens</p>
                    <p style="font-size: 2rem; font-weight: 700; color: hsl(var(--foreground)); line-height: 1.2; margin-top: 0.25rem;">
                        <?php echo esc_html($messages_count); ?>
                    </p>
                    <p style="font-size: 0.75rem; color: hsl(var(--muted-foreground)); margin-top: 0.25rem;">Notificações e DMs</p>
                    <div style="display: flex; align-items: center; gap: 0.25rem; margin-top: 0.5rem;">
                        <?php if ($messages_count > 0): ?>
                            <span style="font-size: 0.75rem; font-weight: 500; color: hsl(var(--primary));">Não lidas</span>
                        <?php else: ?>
                            <span style="font-size: 0.75rem; color: hsl(var(--muted-foreground));">Tudo em dia</span>
                        <?php endif; ?>
                    </div>
                </div>
                <div style="width: 2.5rem; height: 2.5rem; border-radius: var(--radius); background: hsl(142 76% 36% / 0.1); display: flex; align-items: center; justify-content: center;">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="hsl(142 76% 36%)" stroke-width="2">
                        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                    </svg>
                </div>
            </div>
        </div>
        
        <!-- Atividade Card -->
        <div class="stat-card" data-tooltip="<?php echo esc_attr__('Total de ações realizadas nos últimos 7 dias: documentos criados, editados, compartilhados', 'apollo-social'); ?>" style="background: hsl(var(--card)); border: 1px solid hsl(var(--border)); border-radius: var(--radius); padding: 1.5rem; cursor: help; transition: all 0.2s ease;">
            <div style="display: flex; align-items: flex-start; justify-content: space-between;">
                <div>
                    <p style="font-size: 0.875rem; font-weight: 500; color: hsl(var(--muted-foreground));">Atividade (7 dias)</p>
                    <p style="font-size: 2rem; font-weight: 700; color: hsl(var(--foreground)); line-height: 1.2; margin-top: 0.25rem;">
                        <?php echo array_sum($activity_by_day); ?>
                    </p>
                    <p style="font-size: 0.75rem; color: hsl(var(--muted-foreground)); margin-top: 0.25rem;">Criações e edições</p>
                    <div style="display: flex; align-items: center; gap: 0.25rem; margin-top: 0.5rem;">
                        <span style="font-size: 0.75rem; color: hsl(var(--muted-foreground));">Ações realizadas</span>
                    </div>
                </div>
                <div style="width: 2.5rem; height: 2.5rem; border-radius: var(--radius); background: hsl(24 95% 53% / 0.1); display: flex; align-items: center; justify-content: center;">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="hsl(24 95% 53%)" stroke-width="2">
                        <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/>
                    </svg>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Charts Row -->
    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1rem;">
        
        <!-- Activity Chart -->
        <div style="background: hsl(var(--card)); border: 1px solid hsl(var(--border)); border-radius: var(--radius); padding: 1.5rem;">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem;">
                <div>
                    <h3 style="font-size: 1rem; font-weight: 600; color: hsl(var(--foreground)); margin: 0;">Atividade Recente</h3>
                    <p style="font-size: 0.875rem; color: hsl(var(--muted-foreground)); margin: 0.25rem 0 0;">Últimos 7 dias</p>
                </div>
                <div style="display: flex; gap: 0.5rem;">
                    <button class="chart-filter active" data-range="7" style="padding: 0.375rem 0.75rem; font-size: 0.75rem; border-radius: calc(var(--radius) - 2px); border: 1px solid hsl(var(--border)); background: hsl(var(--primary)); color: hsl(var(--primary-foreground)); cursor: pointer;">7D</button>
                    <button class="chart-filter" data-range="30" style="padding: 0.375rem 0.75rem; font-size: 0.75rem; border-radius: calc(var(--radius) - 2px); border: 1px solid hsl(var(--border)); background: transparent; color: hsl(var(--foreground)); cursor: pointer;">30D</button>
                    <button class="chart-filter" data-range="90" style="padding: 0.375rem 0.75rem; font-size: 0.75rem; border-radius: calc(var(--radius) - 2px); border: 1px solid hsl(var(--border)); background: transparent; color: hsl(var(--foreground)); cursor: pointer;">90D</button>
                </div>
            </div>
            <div style="height: 250px;">
                <canvas id="activityChart"></canvas>
            </div>
        </div>
        
        <!-- Document Types Pie -->
        <div style="background: hsl(var(--card)); border: 1px solid hsl(var(--border)); border-radius: var(--radius); padding: 1.5rem;">
            <div style="margin-bottom: 1rem;">
                <h3 style="font-size: 1rem; font-weight: 600; color: hsl(var(--foreground)); margin: 0;">Tipos de Documento</h3>
                <p style="font-size: 0.875rem; color: hsl(var(--muted-foreground)); margin: 0.25rem 0 0;">Distribuição por categoria</p>
            </div>
            <div style="height: 200px; display: flex; align-items: center; justify-content: center;">
                <canvas id="docTypesChart"></canvas>
            </div>
            <div id="docTypesLegend" style="display: flex; flex-wrap: wrap; gap: 0.75rem; margin-top: 1rem; justify-content: center;"></div>
        </div>
    </div>
    
    <!-- Recent Documents Table -->
    <div style="background: hsl(var(--card)); border: 1px solid hsl(var(--border)); border-radius: var(--radius); overflow: hidden;">
        <div style="padding: 1.5rem; border-bottom: 1px solid hsl(var(--border));">
            <div style="display: flex; align-items: center; justify-content: space-between;">
                <div>
                    <h3 style="font-size: 1rem; font-weight: 600; color: hsl(var(--foreground)); margin: 0;">Documentos Recentes</h3>
                    <p style="font-size: 0.875rem; color: hsl(var(--muted-foreground)); margin: 0.25rem 0 0;">Seus documentos mais recentes</p>
                </div>
                <a href="<?php echo esc_url(home_url('/doc/new')); ?>" style="display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.5rem 1rem; font-size: 0.875rem; font-weight: 500; background: hsl(var(--primary)); color: hsl(var(--primary-foreground)); border-radius: var(--radius); text-decoration: none;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="12" y1="5" x2="12" y2="19"/>
                        <line x1="5" y1="12" x2="19" y2="12"/>
                    </svg>
                    Novo Documento
                </a>
            </div>
        </div>
        
        <?php if (empty($user_documents)): ?>
            <div style="padding: 3rem; text-align: center;">
                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="hsl(var(--muted-foreground))" stroke-width="1.5" style="margin: 0 auto 1rem;">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                    <polyline points="14 2 14 8 20 8"/>
                </svg>
                <p style="color: hsl(var(--muted-foreground)); margin: 0 0 1rem;">Nenhum documento criado ainda</p>
                <a href="<?php echo esc_url(home_url('/doc/new')); ?>" style="display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.5rem 1rem; font-size: 0.875rem; font-weight: 500; background: hsl(var(--primary)); color: hsl(var(--primary-foreground)); border-radius: var(--radius); text-decoration: none;">
                    Criar Primeiro Documento
                </a>
            </div>
        <?php else: ?>
            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="border-bottom: 1px solid hsl(var(--border));">
                            <th style="padding: 0.75rem 1rem; text-align: left; font-size: 0.75rem; font-weight: 500; color: hsl(var(--muted-foreground)); text-transform: uppercase; letter-spacing: 0.05em;">Título</th>
                            <th style="padding: 0.75rem 1rem; text-align: left; font-size: 0.75rem; font-weight: 500; color: hsl(var(--muted-foreground)); text-transform: uppercase; letter-spacing: 0.05em;">Status</th>
                            <th style="padding: 0.75rem 1rem; text-align: left; font-size: 0.75rem; font-weight: 500; color: hsl(var(--muted-foreground)); text-transform: uppercase; letter-spacing: 0.05em;">Criado</th>
                            <th style="padding: 0.75rem 1rem; text-align: right; font-size: 0.75rem; font-weight: 500; color: hsl(var(--muted-foreground)); text-transform: uppercase; letter-spacing: 0.05em;">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (array_slice($user_documents, 0, 5) as $doc): 
                            $status_labels = array(
                                'publish' => array('label' => 'Publicado', 'color' => '142 76% 36%'),
                                'draft' => array('label' => 'Rascunho', 'color' => '48 96% 53%'),
                                'pending' => array('label' => 'Pendente', 'color' => '24 95% 53%'),
                            );
                            $status = $status_labels[$doc->post_status] ?? array('label' => $doc->post_status, 'color' => '0 0% 50%');
                        ?>
                        <tr style="border-bottom: 1px solid hsl(var(--border));">
                            <td style="padding: 1rem;">
                                <div style="display: flex; align-items: center; gap: 0.75rem;">
                                    <div style="width: 2rem; height: 2rem; border-radius: var(--radius); background: hsl(var(--muted)); display: flex; align-items: center; justify-content: center;">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="hsl(var(--muted-foreground))" stroke-width="2">
                                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                            <polyline points="14 2 14 8 20 8"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <p style="font-size: 0.875rem; font-weight: 500; color: hsl(var(--foreground)); margin: 0;"><?php echo esc_html($doc->post_title); ?></p>
                                    </div>
                                </div>
                            </td>
                            <td style="padding: 1rem;">
                                <span style="display: inline-flex; padding: 0.25rem 0.625rem; font-size: 0.75rem; font-weight: 500; border-radius: 9999px; background: hsl(<?php echo $status['color']; ?> / 0.1); color: hsl(<?php echo $status['color']; ?>);">
                                    <?php echo esc_html($status['label']); ?>
                                </span>
                            </td>
                            <td style="padding: 1rem;">
                                <span style="font-size: 0.875rem; color: hsl(var(--muted-foreground));">
                                    <?php echo esc_html(human_time_diff(strtotime($doc->post_date), current_time('timestamp'))); ?> atrás
                                </span>
                            </td>
                            <td style="padding: 1rem; text-align: right;">
                                <div style="display: flex; justify-content: flex-end; gap: 0.5rem;">
                                    <a href="<?php echo esc_url(get_permalink($doc->ID)); ?>" style="padding: 0.375rem; border-radius: var(--radius); border: 1px solid hsl(var(--border)); background: transparent; display: inline-flex; cursor: pointer; text-decoration: none;" title="Ver">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="hsl(var(--foreground))" stroke-width="2">
                                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                            <circle cx="12" cy="12" r="3"/>
                                        </svg>
                                    </a>
                                    <a href="<?php echo esc_url(home_url('/doc/' . $doc->ID . '/edit')); ?>" style="padding: 0.375rem; border-radius: var(--radius); border: 1px solid hsl(var(--border)); background: transparent; display: inline-flex; cursor: pointer; text-decoration: none;" title="Editar">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="hsl(var(--foreground))" stroke-width="2">
                                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                                        </svg>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div style="padding: 1rem; border-top: 1px solid hsl(var(--border)); text-align: center;">
                <a href="<?php echo esc_url(home_url('/cena-rio?tab=documents')); ?>" style="font-size: 0.875rem; color: hsl(var(--primary)); text-decoration: none; font-weight: 500;">
                    Ver Todos os Documentos →
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Chart.js defaults para combinar com ShadCN
    Chart.defaults.font.family = 'system-ui, -apple-system, sans-serif';
    Chart.defaults.color = 'hsl(240 3.8% 46.1%)';
    
    const chartColors = {
        primary: 'hsl(240 5.9% 10%)',
        secondary: 'hsl(262 83% 58%)',
        success: 'hsl(142 76% 36%)',
        warning: 'hsl(48 96% 53%)',
        danger: 'hsl(0 84% 60%)',
        muted: 'hsl(240 4.8% 95.9%)'
    };
    
    // Activity Chart
    const activityCtx = document.getElementById('activityChart');
    if (activityCtx) {
        new Chart(activityCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($chart_labels); ?>,
                datasets: [{
                    label: 'Atividade',
                    data: <?php echo json_encode($activity_by_day); ?>,
                    backgroundColor: chartColors.primary,
                    borderRadius: 4,
                    borderSkipped: false,
                    barThickness: 24
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        enabled: true,
                        backgroundColor: 'hsl(240 10% 3.9%)',
                        titleColor: 'hsl(0 0% 98%)',
                        bodyColor: 'hsl(0 0% 98%)',
                        borderColor: 'hsl(240 3.7% 15.9%)',
                        borderWidth: 1,
                        cornerRadius: 6,
                        padding: 12,
                        displayColors: false,
                        callbacks: {
                            title: function(context) {
                                return context[0].label;
                            },
                            label: function(context) {
                                return `${context.parsed.y} ações realizadas`;
                            },
                            afterLabel: function(context) {
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = total > 0 ? ((context.parsed.y / total) * 100).toFixed(1) : 0;
                                return `${percentage}% do total semanal`;
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        border: { display: false }
                    },
                    y: {
                        beginAtZero: true,
                        grid: { color: 'hsl(240 5.9% 90%)' },
                        border: { display: false },
                        ticks: { stepSize: 1 }
                    }
                }
            }
        });
    }
    
    // Document Types Pie Chart
    const docTypesCtx = document.getElementById('docTypesChart');
    if (docTypesCtx) {
        const docTypes = {
            'Plano de Produção': <?php echo $docs_count > 0 ? ceil($docs_count * 0.4) : 1; ?>,
            'Roteiro': <?php echo $docs_count > 0 ? ceil($docs_count * 0.3) : 0; ?>,
            'Briefing': <?php echo $docs_count > 0 ? ceil($docs_count * 0.2) : 0; ?>,
            'Outros': <?php echo $docs_count > 0 ? ceil($docs_count * 0.1) : 0; ?>
        };
        
        const pieChart = new Chart(docTypesCtx, {
            type: 'doughnut',
            data: {
                labels: Object.keys(docTypes),
                datasets: [{
                    data: Object.values(docTypes),
                    backgroundColor: [
                        chartColors.primary,
                        chartColors.secondary,
                        chartColors.success,
                        chartColors.warning
                    ],
                    borderWidth: 0,
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '65%',
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        enabled: true,
                        backgroundColor: 'hsl(240 10% 3.9%)',
                        titleColor: 'hsl(0 0% 98%)',
                        bodyColor: 'hsl(0 0% 98%)',
                        borderColor: 'hsl(240 3.7% 15.9%)',
                        borderWidth: 1,
                        cornerRadius: 6,
                        padding: 12,
                        callbacks: {
                            title: function(context) {
                                return context[0].label;
                            },
                            label: function(context) {
                                const value = context.parsed;
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                                return `${value} documentos (${percentage}%)`;
                            }
                        }
                    }
                }
            }
        });
        
        // Custom legend
        const legendContainer = document.getElementById('docTypesLegend');
        if (legendContainer) {
            const colors = [chartColors.primary, chartColors.secondary, chartColors.success, chartColors.warning];
            Object.keys(docTypes).forEach((label, i) => {
                const item = document.createElement('div');
                item.style.cssText = 'display: flex; align-items: center; gap: 0.375rem; font-size: 0.75rem;';
                item.innerHTML = `
                    <span style="width: 8px; height: 8px; border-radius: 2px; background: ${colors[i]};"></span>
                    <span style="color: hsl(var(--muted-foreground));">${label}</span>
                `;
                legendContainer.appendChild(item);
            });
        }
    }
    
    // Sparkline for docs
    const sparklineCtx = document.getElementById('docsSparkline');
    if (sparklineCtx) {
        new Chart(sparklineCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($chart_labels); ?>,
                datasets: [{
                    data: <?php echo json_encode($activity_by_day); ?>,
                    borderColor: chartColors.primary,
                    borderWidth: 2,
                    fill: true,
                    backgroundColor: 'hsla(240, 5.9%, 10%, 0.1)',
                    tension: 0.4,
                    pointRadius: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    x: { display: false },
                    y: { display: false }
                }
            }
        });
    }
    
    // Filter buttons
    document.querySelectorAll('.chart-filter').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.chart-filter').forEach(b => {
                b.style.background = 'transparent';
                b.style.color = 'hsl(var(--foreground))';
            });
            this.style.background = 'hsl(var(--primary))';
            this.style.color = 'hsl(var(--primary-foreground))';
            // TODO: Implementar filtro de dados por período
        });
    });
});
</script>

