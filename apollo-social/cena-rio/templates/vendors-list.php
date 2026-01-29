<?php
/**
 * Template: Cena::Rio - Vendors List (Fornecedores)
 * PHASE 5: Gated industry access with WP_User_Query
 *
 * @package Apollo_Social
 * @subpackage CenaRio
 * @version 2.0.0
 * @uses UNI.CSS v5.2.0 - Card & List components
 *
 * SECURITY:
 * - Parent template (page-cena-rio.php) already validates view_industry_content capability
 * - This file should NEVER be accessed directly
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Query vendors with 'fornecedor' role
$vendors_query = new WP_User_Query(
	array(
		'role'    => 'fornecedor',
		'orderby' => 'display_name',
		'order'   => 'ASC',
		'number'  => 50,
		'fields'  => array( 'ID', 'display_name', 'user_email', 'user_registered' ),
	)
);

$vendors       = $vendors_query->get_results();
$vendors_count = $vendors_query->get_total();
?>

<div class="ap-content-section">
	<header class="ap-section-header">
		<div class="ap-section-title-group">
			<h2 class="ap-heading-3">
				<i class="ri-store-2-line"></i>
				<?php esc_html_e( 'Fornecedores Cadastrados', 'apollo-social' ); ?>
			</h2>
			<p class="ap-text-muted">
				<?php
				printf(
					/* translators: %d: number of vendors */
					esc_html( _n( '%d fornecedor encontrado', '%d fornecedores encontrados', $vendors_count, 'apollo-social' ) ),
					$vendors_count
				);
				?>
			</p>
		</div>
	</header>

	<?php if ( empty( $vendors ) ) : ?>
		<div class="ap-empty-state">
			<i class="ri-store-2-line"></i>
			<h3><?php esc_html_e( 'Nenhum fornecedor cadastrado', 'apollo-social' ); ?></h3>
			<p><?php esc_html_e( 'Ainda não há fornecedores registrados no sistema.', 'apollo-social' ); ?></p>
		</div>
	<?php else : ?>
		<div class="ap-cards-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: var(--ap-space-4, 1rem);">
			<?php foreach ( $vendors as $vendor ) : ?>
				<?php
				$vendor_id       = absint( $vendor->ID );
				$vendor_name     = esc_html( $vendor->display_name );
				$vendor_email    = esc_html( $vendor->user_email );
				$vendor_initials = strtoupper( substr( $vendor->display_name, 0, 2 ) );
				$vendor_since    = wp_date( 'd/m/Y', strtotime( $vendor->user_registered ) );
				$vendor_url      = home_url( '/id/' . esc_attr( get_userdata( $vendor_id )->user_login ) );

				// Get vendor meta
				$vendor_phone    = get_user_meta( $vendor_id, 'phone', true );
				$vendor_company  = get_user_meta( $vendor_id, 'company', true );
				$vendor_category = get_user_meta( $vendor_id, 'vendor_category', true );
				?>
				<article class="ap-card ap-card-interactive" data-vendor-id="<?php echo $vendor_id; ?>">
					<div class="ap-card-body" style="display: flex; gap: var(--ap-space-3, 0.75rem); align-items: flex-start;">
						<div class="ap-avatar ap-avatar-md" style="background: linear-gradient(135deg, var(--ap-slate-600, #475569), var(--ap-slate-700, #334155)); flex-shrink: 0;">
							<span><?php echo esc_html( $vendor_initials ); ?></span>
						</div>
						<div class="ap-card-content" style="flex: 1; min-width: 0;">
							<h3 class="ap-card-title" style="margin-bottom: var(--ap-space-1, 0.25rem);">
								<a href="<?php echo esc_url( $vendor_url ); ?>" class="ap-link-unstyled">
									<?php echo $vendor_name; ?>
								</a>
							</h3>
							<?php if ( $vendor_company ) : ?>
								<p class="ap-text-sm ap-text-muted" style="margin-bottom: var(--ap-space-1, 0.25rem);">
									<i class="ri-building-line"></i>
									<?php echo esc_html( $vendor_company ); ?>
								</p>
							<?php endif; ?>
							<?php if ( $vendor_category ) : ?>
								<span class="ap-badge ap-badge-sm ap-badge-outline" style="margin-bottom: var(--ap-space-2, 0.5rem);">
									<?php echo esc_html( $vendor_category ); ?>
								</span>
							<?php endif; ?>
							<p class="ap-text-xs ap-text-muted">
								<i class="ri-calendar-line"></i>
								<?php
								printf(
									/* translators: %s: registration date */
									esc_html__( 'Desde %s', 'apollo-social' ),
									$vendor_since
								);
								?>
							</p>
						</div>
					</div>
					<div class="ap-card-footer" style="display: flex; gap: var(--ap-space-2, 0.5rem); justify-content: flex-end;">
						<?php if ( $vendor_phone ) : ?>
							<a href="tel:<?php echo esc_attr( preg_replace( '/[^0-9+]/', '', $vendor_phone ) ); ?>"
								class="ap-btn ap-btn-ghost ap-btn-sm"
								data-ap-tooltip="<?php esc_attr_e( 'Ligar', 'apollo-social' ); ?>">
								<i class="ri-phone-line"></i>
							</a>
						<?php endif; ?>
						<a href="mailto:<?php echo esc_attr( $vendor_email ); ?>"
							class="ap-btn ap-btn-ghost ap-btn-sm"
							data-ap-tooltip="<?php esc_attr_e( 'Enviar e-mail', 'apollo-social' ); ?>">
							<i class="ri-mail-line"></i>
						</a>
						<a href="<?php echo esc_url( $vendor_url ); ?>"
							class="ap-btn ap-btn-outline ap-btn-sm"
							data-ap-tooltip="<?php esc_attr_e( 'Ver perfil completo', 'apollo-social' ); ?>">
							<i class="ri-user-line"></i>
							<span><?php esc_html_e( 'Perfil', 'apollo-social' ); ?></span>
						</a>
					</div>
				</article>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>
</div>
