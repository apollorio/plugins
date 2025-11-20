<?php
/**
 * Admin Social Dashboard Template
 * TODO 123-129: Admin dashboard with statistics, graphs, animations
 * 
 * @package Apollo_Events_Manager
 * @version 0.1.0
 */

defined('ABSPATH') || exit;

// TODO 123: Admin dashboard all users stats
// TODO 124: Event and CPT performance
// TODO 125: Co-authors own stats
// TODO 126: Number incrementing animations
// TODO 127: Views over time graph
// TODO 128: Engagement graph
// TODO 129: Events by category graph

?>
<div class="apollo-admin-social-dashboard" data-shadcn-enhanced="true">
    <h1>Estatísticas Apollo Social</h1>
    
    <!-- TODO 126: Animated counters -->
    <div class="stats-grid">
        <div class="stat-card" data-counter-animation="true" data-target="<?php echo esc_attr($total_users); ?>">
            <h3>Total de Usuários</h3>
            <div class="stat-value">0</div>
        </div>
        
        <div class="stat-card" data-counter-animation="true" data-target="<?php echo esc_attr($total_events); ?>">
            <h3>Total de Eventos</h3>
            <div class="stat-value">0</div>
        </div>
        
        <div class="stat-card" data-counter-animation="true" data-target="<?php echo esc_attr($total_engagement); ?>">
            <h3>Engajamento Total</h3>
            <div class="stat-value">0</div>
        </div>
    </div>
    
    <!-- TODO 127: Views over time graph -->
    <div class="graph-container">
        <h2>Visualizações ao Longo do Tempo</h2>
        <div id="views-over-time-graph" class="line-graph"></div>
    </div>
    
    <!-- TODO 128: Engagement graph -->
    <div class="graph-container">
        <h2>Engajamento</h2>
        <div id="engagement-graph" class="line-graph"></div>
    </div>
    
    <!-- TODO 129: Events by category graph -->
    <div class="graph-container">
        <h2>Eventos por Categoria</h2>
        <div id="category-graph" class="bar-graph"></div>
    </div>
</div>

