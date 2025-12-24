<?php
/**
 * Supplier Card Partial Template
 * This template is used to render individual supplier cards in the suppliers list.
 *
 * @package Apollo_Social
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Render a supplier card.
 *
 * @param int $supplier_id The ID of the supplier.
 * @param string $supplier_name The name of the supplier.
 * @param string $supplier_contact The contact information of the supplier.
 * @param string $supplier_avatar The URL of the supplier's avatar.
 * @param string $supplier_link The link to the supplier's single page.
 *
 * @return void
 */
function render_supplier_card( $supplier_id, $supplier_name, $supplier_contact, $supplier_avatar, $supplier_link ) {
	?>
	<div class="supplier-card">
		<a href="<?php echo esc_url( $supplier_link ); ?>" class="supplier-card-link">
			<div class="supplier-avatar">
				<img src="<?php echo esc_url( $supplier_avatar ); ?>" alt="<?php echo esc_attr( $supplier_name ); ?>" class="rounded-full">
			</div>
			<div class="supplier-info">
				<h3 class="supplier-name"><?php echo esc_html( $supplier_name ); ?></h3>
				<p class="supplier-contact"><?php echo esc_html( $supplier_contact ); ?></p>
			</div>
		</a>
	</div>
	<?php
}
?>