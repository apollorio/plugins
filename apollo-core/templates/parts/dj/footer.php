<?php
/**
 * Template Part: DJ Single - Footer
 * ==================================
 * Path: apollo-core/templates/parts/dj/footer.php
 *
 * @package Apollo_Core
 * @version 2.0.0
 */

defined( 'ABSPATH' ) || exit;
?>

<footer class="dj-footer">
	<span>
		<?php echo esc_html( apply_filters( 'apollo_dj_footer_brand', 'Apollo::rio' ) ); ?><br>
		<?php esc_html_e( 'Roster preview', 'apollo-core' ); ?>
	</span>
	<span>
		<?php esc_html_e( 'Para bookers,', 'apollo-core' ); ?><br>
		<?php esc_html_e( 'selos e clubes', 'apollo-core' ); ?>
	</span>
</footer>
