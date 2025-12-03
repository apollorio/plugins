<?php

namespace Apollo\Modules\Pwa;

class PwaServiceProvider {

	public function register(): void {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue' ) );
		add_action( 'wp_head', array( $this, 'addManifestLink' ) );
	}

	public function enqueue(): void {
		$pluginFile = APOLLO_SOCIAL_PLUGIN_DIR . 'apollo-social.php';
		$swUrl      = plugins_url( 'public/service-worker.js', $pluginFile );

		wp_register_script(
			'apollo-social-pwa',
			'',
			array(),
			APOLLO_SOCIAL_VERSION,
			true
		);

		wp_enqueue_script( 'apollo-social-pwa' );

		$inline = sprintf(
			"if ('serviceWorker' in navigator) { window.addEventListener('load', function () { navigator.serviceWorker.register('%s').catch(function (error) { console.debug('Apollo SW', error); }); }); }",
			esc_url_raw( $swUrl )
		);

		wp_add_inline_script( 'apollo-social-pwa', $inline );
	}

	public function addManifestLink(): void {
		$pluginFile  = APOLLO_SOCIAL_PLUGIN_DIR . 'apollo-social.php';
		$manifestUrl = plugins_url( 'public/manifest.webmanifest', $pluginFile );

		echo '<link rel="manifest" href="' . esc_url( $manifestUrl ) . '" />' . "\n";
	}
}
