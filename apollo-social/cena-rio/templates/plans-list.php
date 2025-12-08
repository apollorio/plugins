<?php
/**
 * Plans List - Cena::Rio
 * STRICT MODE: 100% UNI.CSS conformance
 *
 * @package Apollo_Social
 * @subpackage CenaRio
 * @version 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$user_id    = get_current_user_id();
$user_plans = get_user_meta( $user_id, 'cena_rio_event_plans', true );
if ( ! is_array( $user_plans ) ) {
	$user_plans = [];
}
?>

<div class="ap-section">
	<!-- Header -->
	<div class="ap-section-header">
		<div class="ap-section-title-group">
			<h2 class="ap-heading-2">Planos de Evento</h2>
			<p class="ap-text-muted">Gerencie seus planos e cronogramas de evento</p>
		</div>
		<div class="ap-section-actions">
			<a href="<?php echo esc_url( home_url( '/pla/new' ) ); ?>"
				class="ap-btn ap-btn-primary"
				data-ap-tooltip="Criar um novo plano de evento">
				<i class="ri-add-line"></i>
				Novo Plano
			</a>
		</div>
	</div>

	<!-- Plans Grid -->
	<?php if ( empty( $user_plans ) ) : ?>
	<div class="ap-card ap-empty-state-card">
		<div class="ap-empty-state">
			<i class="ri-calendar-line"></i>
			<h3>Nenhum plano criado</h3>
			<p>Crie seu primeiro plano de evento para organizar seus projetos</p>
			<a href="<?php echo esc_url( home_url( '/pla/new' ) ); ?>"
				class="ap-btn ap-btn-primary"
				data-ap-tooltip="Criar seu primeiro plano">
				<i class="ri-add-line"></i>
				Criar Plano
			</a>
		</div>
	</div>
	<?php else : ?>
	<div class="ap-grid ap-grid-3">
		<?php
		foreach ( $user_plans as $plan ) :
			$plan_id     = $plan['id'] ?? '';
			$plan_title  = $plan['title'] ?? 'Plano sem título';
			$plan_status = $plan['status'] ?? 'draft';
			$plan_date   = $plan['date'] ?? '';

			$status_config = [
				'active'    => [
					'label'   => 'Ativo',
					'class'   => 'ap-badge-success',
					'tooltip' => __( 'Plano ativo e em execução', 'apollo-social' ),
				],
				'draft'     => [
					'label'   => 'Rascunho',
					'class'   => 'ap-badge-warning',
					'tooltip' => __( 'Plano em elaboração', 'apollo-social' ),
				],
				'completed' => [
					'label'   => 'Concluído',
					'class'   => 'ap-badge-info',
					'tooltip' => __( 'Plano finalizado com sucesso', 'apollo-social' ),
				],
				'archived'  => [
					'label'   => 'Arquivado',
					'class'   => 'ap-badge-secondary',
					'tooltip' => __( 'Plano arquivado', 'apollo-social' ),
				],
			];
			$status        = $status_config[ $plan_status ] ?? [
				'label'   => ucfirst( $plan_status ),
				'class'   => 'ap-badge-secondary',
				'tooltip' => sprintf( __( 'Status: %s', 'apollo-social' ), ucfirst( $plan_status ) ),
			];
			?>
		<div class="ap-card ap-card-hover" data-ap-tooltip="<?php esc_attr_e( 'Clique para gerenciar este plano', 'apollo-social' ); ?>">
			<div class="ap-card-header">
				<div class="ap-card-icon" style="background: linear-gradient(135deg, #a855f7, #c084fc);" data-ap-tooltip="<?php esc_attr_e( 'Tipo: Plano de evento', 'apollo-social' ); ?>">
					<i class="ri-calendar-check-line" style="color: white;"></i>
				</div>
				<span class="ap-badge <?php echo esc_attr( $status['class'] ); ?>" data-ap-tooltip="<?php echo esc_attr( $status['tooltip'] ); ?>">
					<?php echo esc_html( $status['label'] ); ?>
				</span>
			</div>
			<div class="ap-card-body">
				<h3 class="ap-card-title"><?php echo esc_html( $plan_title ); ?></h3>
				<?php if ( ! empty( $plan['description'] ) ) : ?>
				<p class="ap-card-text"><?php echo esc_html( wp_trim_words( $plan['description'], 15 ) ); ?></p>
				<?php endif; ?>
			</div>
			<div class="ap-card-footer">
				<?php if ( $plan_date ) : ?>
				<div class="ap-card-meta">
					<i class="ri-calendar-line"></i>
					<span><?php echo esc_html( date_i18n( 'd/m/Y', strtotime( $plan_date ) ) ); ?></span>
				</div>
				<?php endif; ?>
				<div class="ap-card-actions">
					<button class="ap-btn-icon-sm" data-ap-tooltip="Editar plano">
						<i class="ri-edit-line"></i>
					</button>
					<button class="ap-btn-icon-sm" data-ap-tooltip="Ver detalhes">
						<i class="ri-eye-line"></i>
					</button>
				</div>
			</div>
		</div>
		<?php endforeach; ?>
	</div>
	<?php endif; ?>
</div>
