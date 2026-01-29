<?php
/**
 * Groups Directory Renderer
 *
 * Renders the groups listing
 *
 * @package Apollo_Social
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Groups Directory Renderer Class
 */
class Apollo_Groups_Directory_Renderer {

	/**
	 * Render the groups directory
	 */
	public static function render_directory() {
		ob_start();
		?>
		<div class="apollo-groups-directory">
			<h2>Groups</h2>
			<div class="groups-list">
				<!-- Placeholder for groups -->
				<p>Groups will be listed here.</p>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}
}
