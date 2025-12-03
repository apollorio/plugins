<?php
/**
 * Group Card Template
 * Displays group information with status badge and moderation notices
 */
?>

<div class="apollo-group-card" data-group-id="<?php echo esc_attr( $group['id'] ); ?>">
	<div class="apollo-group-header">
		<h3 class="apollo-group-title">
			<a href="/grupo/<?php echo esc_attr( $group['slug'] ); ?>/">
				<?php echo esc_html( $group['title'] ?? $group['name'] ); ?>
			</a>
		</h3>
		
		<?php
		// Include status badge
		require 'group-status-badge.php';
		?>
	</div>
	
	<div class="apollo-group-content">
		<?php if ( ! empty( $group['description'] ) ) : ?>
			<p class="apollo-group-description">
				<?php echo esc_html( wp_trim_words( $group['description'], 25 ) ); ?>
			</p>
		<?php endif; ?>
		
		<div class="apollo-group-meta">
			<span class="apollo-group-type">
				<?php echo esc_html( ucfirst( $group['type'] ?? 'group' ) ); ?>
			</span>
			
			<?php if ( ! empty( $group['created_at'] ) ) : ?>
				<span class="apollo-group-date">
					Criado em <?php echo esc_html( date( 'd/m/Y', strtotime( $group['created_at'] ) ) ); ?>
				</span>
			<?php endif; ?>
			
			<?php if ( ! empty( $group['workflow_meta']['submitted_at'] ) ) : ?>
				<span class="apollo-group-submitted">
					Enviado em <?php echo esc_html( date( 'd/m/Y H:i', strtotime( $group['workflow_meta']['submitted_at'] ) ) ); ?>
				</span>
			<?php endif; ?>
		</div>
	</div>
	
	<div class="apollo-group-actions">
		<?php if ( $status === 'draft' ) : ?>
			<a href="/grupo/editar/<?php echo esc_attr( $group['id'] ); ?>/" class="apollo-btn apollo-btn-primary">
				Continuar Editando
			</a>
			<button type="button" class="apollo-btn apollo-btn-secondary apollo-submit-btn" 
					data-group-id="<?php echo esc_attr( $group['id'] ); ?>">
				Enviar para Revisão
			</button>
		<?php elseif ( $status === 'rejected' ) : ?>
			<a href="/grupo/editar/<?php echo esc_attr( $group['id'] ); ?>/" class="apollo-btn apollo-btn-primary">
				Editar e Reenviar
			</a>
		<?php elseif ( $status === 'published' ) : ?>
			<a href="/grupo/<?php echo esc_attr( $group['slug'] ); ?>/" class="apollo-btn apollo-btn-success">
				Ver Grupo
			</a>
			<a href="/grupo/editar/<?php echo esc_attr( $group['id'] ); ?>/" class="apollo-btn apollo-btn-secondary">
				Editar
			</a>
		<?php endif; ?>
	</div>
</div>

<style>
.apollo-group-card {
	background: white;
	border: 1px solid #e5e7eb;
	border-radius: 8px;
	padding: 20px;
	margin-bottom: 16px;
	box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.apollo-group-header {
	margin-bottom: 16px;
}

.apollo-group-title {
	margin: 0 0 12px 0;
	font-size: 18px;
	font-weight: 600;
}

.apollo-group-title a {
	color: #1f2937;
	text-decoration: none;
}

.apollo-group-title a:hover {
	color: #3b82f6;
}

.apollo-group-content {
	margin-bottom: 16px;
}

.apollo-group-description {
	color: #6b7280;
	line-height: 1.6;
	margin-bottom: 12px;
}

.apollo-group-meta {
	display: flex;
	gap: 16px;
	flex-wrap: wrap;
	font-size: 13px;
	color: #9ca3af;
}

.apollo-group-meta span {
	display: flex;
	align-items: center;
}

.apollo-group-actions {
	display: flex;
	gap: 8px;
	flex-wrap: wrap;
}

.apollo-btn {
	padding: 8px 16px;
	border: none;
	border-radius: 4px;
	font-size: 14px;
	cursor: pointer;
	text-decoration: none;
	display: inline-flex;
	align-items: center;
	transition: all 0.2s;
}

.apollo-btn-primary {
	background-color: #3b82f6;
	color: white;
}

.apollo-btn-primary:hover {
	background-color: #2563eb;
}

.apollo-btn-secondary {
	background-color: #6b7280;
	color: white;
}

.apollo-btn-secondary:hover {
	background-color: #4b5563;
}

.apollo-btn-success {
	background-color: #10b981;
	color: white;
}

.apollo-btn-success:hover {
	background-color: #059669;
}

/* Responsive */
@media (max-width: 640px) {
	.apollo-group-meta {
		flex-direction: column;
		gap: 4px;
	}
	
	.apollo-group-actions {
		flex-direction: column;
	}
	
	.apollo-btn {
		justify-content: center;
	}
}
</style>
