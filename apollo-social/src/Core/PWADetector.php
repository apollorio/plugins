<?php
namespace Apollo\Core;

/**
 * PWA Detector
 * Detects if apollo-rio is installed and handles PWA verification
 * Provides PWA installation instructions for iOS/Android
 */
class PWADetector {

	private $is_apollo_rio_active = false;
	private $pwa_mode             = false;

	public function __construct() {
		$this->checkApolloRio();
		$this->detectPWAMode();
	}

	/**
	 * Check if apollo-rio plugin is active
	 */
	private function checkApolloRio() {
		if ( function_exists( 'apollo_is_pwa' ) ) {
			$this->is_apollo_rio_active = true;
		} else {
			// Check if plugin is active via WordPress functions
			if ( function_exists( 'is_plugin_active' ) ) {
				$this->is_apollo_rio_active = is_plugin_active( 'apollo-rio/apollo-rio.php' );
			}
		}
	}

	/**
	 * Detect if current request is from PWA
	 */
	private function detectPWAMode() {
		if ( ! $this->is_apollo_rio_active ) {
			return;
		}

		// Check cookie
		if ( isset( $_COOKIE['apollo_display_mode'] ) ) {
			$mode           = sanitize_text_field( wp_unslash( $_COOKIE['apollo_display_mode'] ) );
			$this->pwa_mode = ( $mode === 'pwa' );
		}

		// Check header
		if ( isset( $_SERVER['HTTP_X_APOLLO_PWA'] ) ) {
			$header         = sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_APOLLO_PWA'] ) );
			$this->pwa_mode = ( $header === '1' || strtolower( $header ) === 'true' );
		}

		// Use apollo-rio function if available
		if ( $this->is_apollo_rio_active && function_exists( 'apollo_is_pwa' ) ) {
			$this->pwa_mode = apollo_is_pwa();
		}

		// Check if standalone mode (iOS PWA)
		if ( isset( $_SERVER['HTTP_USER_AGENT'] ) ) {
			$ua = sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) );
			// iOS standalone mode detection
			if ( strpos( $ua, 'iPhone' ) !== false || strpos( $ua, 'iPad' ) !== false ) {
				if ( isset( $_SERVER['HTTP_X_REQUESTED_WITH'] ) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'apollo-pwa' ) {
					$this->pwa_mode = true;
				}
			}
		}
	}

	/**
	 * Check if PWA mode is active
	 */
	public function isPWAMode() {
		return $this->pwa_mode;
	}

	/**
	 * Check if apollo-rio is installed
	 */
	public function isApolloRioActive() {
		return $this->is_apollo_rio_active;
	}

	/**
	 * Get PWA installation instructions
	 */
	public function getPWAInstructions() {
		if ( ! $this->is_apollo_rio_active ) {
			return null;
		}

		return array(
			'ios'     => array(
				'title' => 'Instalar no iPhone',
				'steps' => array(
					'1. Toque no botão de compartilhar (ícone de compartilhamento)',
					'2. Role até encontrar "Adicionar à Tela de Início"',
					'3. Toque em "Adicionar"',
					'4. O ícone do Apollo aparecerá na sua tela inicial',
				),
				'icon'  => 'ri-iphone-line',
			),
			'android' => array(
				'title'        => 'Baixar para Android',
				'steps'        => array(
					'1. Toque no menu (três pontos) no navegador',
					'2. Selecione "Adicionar à tela inicial" ou "Instalar app"',
					'3. Confirme a instalação',
					'4. O app Apollo será instalado no seu dispositivo',
				),
				'download_url' => function_exists( 'apollo_get_android_app_url' )
					? apollo_get_android_app_url()
					: null,
				'icon'         => 'ri-android-line',
			),
		);
	}

	/**
	 * Should show Apollo header (app::rio mode)
	 */
	public function shouldShowApolloHeader() {
		// Show header if apollo-rio is active and NOT in clean mode
		return $this->is_apollo_rio_active && ! $this->isCleanMode();
	}

	/**
	 * Check if clean mode (apollo::rio clean - no header)
	 */
	public function isCleanMode() {
		// Check for clean mode parameter or setting
		if ( isset( $_GET['clean'] ) && sanitize_text_field( wp_unslash( $_GET['clean'] ) ) === '1' ) {
			return true;
		}

		// Check option
		$clean_mode = get_option( 'apollo_rio_clean_mode', false );
		return (bool) $clean_mode;
	}

	/**
	 * Get Android download URL
	 */
	private function getAndroidDownloadUrl() {
		if ( function_exists( 'apollo_get_android_app_url' ) ) {
			return apollo_get_android_app_url();
		}

		// Fallback to option
		$url = get_option( 'apollo_android_app_url', '' );
		return ! empty( $url ) ? esc_url_raw( $url ) : null;
	}
}
