<?php
/**
 * Template Part: Analytics Tab Sidebar Content
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="sidebar-tab-content is-hidden" data-tab="analytics">

  <div class="stats-grid">
    <div class="stat-item">
      <div class="stat-num">1.2K</div>
      <div class="stat-lbl">Visualizações</div>
    </div>
    <div class="stat-item">
      <div class="stat-num">87</div>
      <div class="stat-lbl">Cliques</div>
    </div>
  </div>

  <div class="sidebar-group-label">Últimos 7 dias</div>

  <div class="chart-box">
    <?php for ( $i = 0; $i < 7; $i++ ) : ?>
      <div class="chart-bar" style="height:<?php echo rand( 20, 100 ); ?>%"></div>
    <?php endfor; ?>
  </div>

  <p class="help" style="text-align:center; margin-top:1rem; color:var(--muted-foreground);">
    <i class="ri-bar-chart-line"></i>
    Em breve: análise completa de cliques e visualizações por bloco.
  </p>

</div>
