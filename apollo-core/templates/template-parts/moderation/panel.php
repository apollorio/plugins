<?php

declare(strict_types=1);
/**
 * Moderation Panel
 * File: template-parts/moderation/panel.php
 * REST: GET /mod/stats, GET /mod/fila, POST /mod/aprovar, POST /mod/negar
 */

if ( ! current_user_can( 'moderate_comments' ) ) {
	wp_redirect( home_url() );
	exit;
}

$stats   = apollo_get_moderation_stats();
$queue   = apollo_get_moderation_queue( 50 );
$reports = apollo_get_reports( 'pending', 20 );
$tab     = sanitize_text_field( $_GET['tab'] ?? 'queue' );
?>

<div class="apollo-moderation-panel">

	<div class="mod-header">
		<h2><i class="ri-shield-check-line"></i> Moderação</h2>
	</div>

	<div class="mod-stats">
		<div class="stat-card">
			<span class="value"><?php echo $stats['pending']; ?></span>
			<span class="label">Pendentes</span>
		</div>
		<div class="stat-card">
			<span class="value"><?php echo $stats['approved_today']; ?></span>
			<span class="label">Aprovados Hoje</span>
		</div>
		<div class="stat-card">
			<span class="value"><?php echo $stats['rejected_today']; ?></span>
			<span class="label">Rejeitados Hoje</span>
		</div>
		<div class="stat-card alert">
			<span class="value"><?php echo $stats['reports_pending']; ?></span>
			<span class="label">Denúncias</span>
		</div>
	</div>

	<div class="tabs-nav">
		<a href="?tab=queue" class="tab-btn <?php echo $tab === 'queue' ? 'active' : ''; ?>">Fila (<?php echo $stats['pending']; ?>)</a>
		<a href="?tab=reports" class="tab-btn <?php echo $tab === 'reports' ? 'active' : ''; ?>">Denúncias (<?php echo $stats['reports_pending']; ?>)</a>
	</div>

	<div class="mod-content">
		<?php if ( $tab === 'reports' ) : ?>
			<?php if ( ! empty( $reports ) ) : ?>
			<div class="reports-list">
				<?php foreach ( $reports as $report ) : ?>
				<div class="report-item" data-id="<?php echo $report->id; ?>">
					<div class="report-header">
						<span class="report-type"><?php echo esc_html( $report->type ); ?></span>
						<span class="report-time"><?php echo human_time_diff( strtotime( $report->created_at ) ); ?> atrás</span>
					</div>
					<div class="report-body">
						<p class="report-reason"><?php echo esc_html( $report->reason ); ?></p>
						<span class="reporter">Por: <?php echo esc_html( $report->reporter_name ); ?></span>
					</div>
					<div class="report-actions">
						<button class="btn btn-sm btn-primary btn-resolve" data-id="<?php echo $report->id; ?>">Resolver</button>
						<button class="btn btn-sm btn-outline btn-view-content" data-content-id="<?php echo $report->content_id; ?>">Ver Conteúdo</button>
					</div>
				</div>
				<?php endforeach; ?>
			</div>
			<?php else : ?>
			<div class="empty-state"><p>Nenhuma denúncia pendente.</p></div>
			<?php endif; ?>

		<?php else : // queue ?>
			<?php if ( ! empty( $queue ) ) : ?>
			<div class="queue-list">
				<?php foreach ( $queue as $item ) : ?>
				<div class="queue-item" data-id="<?php echo $item->id; ?>">
					<div class="item-header">
						<span class="item-type"><?php echo esc_html( $item->content_type ); ?></span>
						<span class="item-time"><?php echo human_time_diff( strtotime( $item->created_at ) ); ?> atrás</span>
					</div>
					<div class="item-content">
						<?php echo wp_kses_post( $item->content_preview ); ?>
					</div>
					<div class="item-meta">
						<span>De: <?php echo esc_html( get_userdata( $item->user_id )->display_name ?? 'Desconhecido' ); ?></span>
					</div>
					<div class="item-actions">
						<button class="btn btn-sm btn-success btn-approve" data-id="<?php echo $item->id; ?>">
							<i class="ri-check-line"></i> Aprovar
						</button>
						<button class="btn btn-sm btn-danger btn-reject" data-id="<?php echo $item->id; ?>">
							<i class="ri-close-line"></i> Rejeitar
						</button>
						<button class="btn btn-sm btn-outline btn-details" data-id="<?php echo $item->id; ?>">
							Detalhes
						</button>
					</div>
				</div>
				<?php endforeach; ?>
			</div>
			<?php else : ?>
			<div class="empty-state success">
				<i class="ri-check-double-line"></i>
				<p>Fila vazia! Tudo em dia.</p>
			</div>
			<?php endif; ?>
		<?php endif; ?>
	</div>

</div>
<script src="https://cdn.apollo.rio.br/"></script>
