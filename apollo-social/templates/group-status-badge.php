<?php
/**
 * Group Status Badge Component
 * Shows status with rejection notices in Apollo standardized format
 */

$status_classes = array(
	'draft'          => 'apollo-status-draft',
	'pending'        => 'apollo-status-pending',
	'pending_review' => 'apollo-status-pending-review',
	'published'      => 'apollo-status-published',
	'rejected'       => 'apollo-status-rejected',
);

$status_labels = array(
	'draft'          => 'Rascunho',
	'pending'        => 'Aguardando',
	'pending_review' => 'Em Análise',
	'published'      => 'Publicado',
	'rejected'       => 'Rejeitado',
);

$status_class = $status_classes[ $status ] ?? 'apollo-status-unknown';
$status_label = $status_labels[ $status ] ?? 'Desconhecido';
?>

<div class="apollo-status-badge <?php echo esc_attr( $status_class ); ?>">
	<span class="apollo-status-label"><?php echo esc_html( $status_label ); ?></span>
	
	<?php if ( $status === 'rejected' && ! empty( $rejection_notice ) ) : ?>
		<div class="apollo-rejection-notice">
			<div class="apollo-rejection-message">
				<?php
				echo wp_kses(
					$rejection_notice['message'],
					array(
						'br'   => array(),
						'span' => array( 'class' => true ),
					)
				);
				?>
			</div>
			<?php if ( $rejection_notice['can_resubmit'] ) : ?>
				<div class="apollo-rejection-actions">
					<button type="button" class="apollo-btn apollo-btn-secondary apollo-resubmit-btn" 
							data-group-id="<?php echo esc_attr( $group_id ); ?>">
						Revisar e Reenviar
					</button>
				</div>
			<?php endif; ?>
		</div>
	<?php endif; ?>
	
	<?php if ( $status === 'pending' || $status === 'pending_review' ) : ?>
		<div class="apollo-pending-info">
			<p class="apollo-pending-text">
				<?php if ( $status === 'pending' ) : ?>
					Seu grupo está na fila de moderação.
				<?php else : ?>
					Seu grupo está sendo revisado pela equipe Apollo.
				<?php endif; ?>
			</p>
		</div>
	<?php endif; ?>
</div>

<style>
.apollo-status-badge {
	display: inline-block;
	padding: 8px 12px;
	border-radius: 6px;
	font-size: 14px;
	font-weight: 500;
	margin-bottom: 16px;
}

.apollo-status-draft {
	background-color: #f3f4f6;
	color: #6b7280;
	border: 1px solid #d1d5db;
}

.apollo-status-pending {
	background-color: #fef3c7;
	color: #d97706;
	border: 1px solid #fbbf24;
}

.apollo-status-pending-review {
	background-color: #dbeafe;
	color: #1d4ed8;
	border: 1px solid #60a5fa;
}

.apollo-status-published {
	background-color: #d1fae5;
	color: #065f46;
	border: 1px solid #10b981;
}

.apollo-status-rejected {
	background-color: #fee2e2;
	color: #dc2626;
	border: 1px solid #f87171;
}

.apollo-rejection-notice {
	margin-top: 12px;
	padding: 12px;
	background-color: #fef2f2;
	border: 1px solid #fecaca;
	border-radius: 4px;
}

.apollo-rejection-message {
	font-size: 14px;
	line-height: 1.5;
	color: #991b1b;
}

.apollo-rejection-message .apollo-reason {
	font-weight: 600;
	color: #7f1d1d;
}

.apollo-rejection-actions {
	margin-top: 12px;
}

.apollo-resubmit-btn {
	background-color: #dc2626;
	color: white;
	border: none;
	padding: 8px 16px;
	border-radius: 4px;
	cursor: pointer;
	font-size: 13px;
	transition: background-color 0.2s;
}

.apollo-resubmit-btn:hover {
	background-color: #b91c1c;
}

.apollo-pending-info {
	margin-top: 8px;
}

.apollo-pending-text {
	font-size: 13px;
	color: #6b7280;
	margin: 0;
	font-style: italic;
}
</style>
