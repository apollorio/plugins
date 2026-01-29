<?php
/**
 * Sidebar Summary
 * File: template-parts/user/sidebar-summary.php
 */

$user_id         = get_current_user_id();
$next_event      = apollo_get_user_next_event( $user_id );
$pending_docs    = apollo_count_pending_documents( $user_id );
$unread_messages = apollo_count_unread_messages( $user_id );
?>

<div class="sidebar-block">
	<div class="sidebar-title">
		Resumo Rápido
		<i class="ri-flashlight-line" style="color: #ffb347;"></i>
	</div>
	<div class="summary-list">
		<?php if ( $next_event ) : ?>
		<div class="summary-item">
			<i class="ri-calendar-event-line summary-icon"></i>
			<div class="summary-content">
				<strong><?php echo esc_html( $next_event->post_title ); ?></strong>
				<span>
				<?php
				echo apollo_format_event_datetime(
					get_post_meta( $next_event->ID, 'event_date', true ),
					get_post_meta( $next_event->ID, 'event_time', true )
				);
				?>
				</span>
			</div>
		</div>
		<?php endif; ?>
		
		<?php if ( $pending_docs > 0 ) : ?>
		<div class="summary-item">
			<i class="ri-file-text-line summary-icon"></i>
			<div class="summary-content">
				<strong><?php echo $pending_docs; ?> Contrato<?php echo $pending_docs > 1 ? 's' : ''; ?> Pendente<?php echo $pending_docs > 1 ? 's' : ''; ?></strong>
				<span>Assinatura digital requerida</span>
			</div>
		</div>
		<?php endif; ?>
		
		<?php if ( $unread_messages > 0 ) : ?>
		<div class="summary-item">
			<i class="ri-message-3-line summary-icon"></i>
			<div class="summary-content">
				<strong><?php echo $unread_messages; ?> Mensagem<?php echo $unread_messages > 1 ? 's' : ''; ?></strong>
				<span>Não lida<?php echo $unread_messages > 1 ? 's' : ''; ?> no inbox</span>
			</div>
		</div>
		<?php endif; ?>
	</div>
	<a href="<?php echo home_url( '/dashboard/manager' ); ?>" class="btn-full">
		Abrir Gestor Apollo
	</a>
</div>
