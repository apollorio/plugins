<?php
/**
 * PWA Detector.
 *
 * Detects if apollo-rio is installed and handles PWA verification.
 * Provides PWA installation instructions for iOS/Android.
 *
 * @package Apollo\Core
 *
 * phpcs:disable WordPress.Files.FileName.InvalidClassFileName
 * phpcs:disable WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid
 * phpcs:disable WordPress.PHP.YodaConditions.NotYoda
 */

namespace Apollo\Core;

/**
 * PwaDetector Class.
 *
 * Bridges apollo-social with apollo-rio for PWA functionality.
 */
class PwaDetector {

	/**
	 * Whether apollo-rio plugin is active.
	 *
	 * @var bool
	 */
	private $is_apollo_rio_active = false;

	/**
	 * Whether current request is in PWA mode.
	 *
	 * @var bool
	 */
	private $pwa_mode = false;

	/**
	 * Constructor.
	 *
	 * Initializes detection on instantiation.
	 */
	public function __construct() {
		$this->checkApolloRio();
		$this->detectPWAMode();
	}

	/**
	 * Check if apollo-rio plugin is active.
	 *
	 * @return void
	 */
	private function checkApolloRio() {
		if ( function_exists( 'apollo_is_pwa' ) ) {
			$this->is_apollo_rio_active = true;
		} elseif ( function_exists( 'is_plugin_active' ) ) {
			// Check if plugin is active via WordPress functions.
			$this->is_apollo_rio_active = is_plugin_active( 'apollo-rio/apollo-rio.php' );
		}
	}

	/**
	 * Detect if current request is from PWA.
	 *
	 * Checks cookies, headers, and user agent for PWA indicators.
	 *
	 * @return void
	 */
	private function detectPWAMode() {
		if ( ! $this->is_apollo_rio_active ) {
			return;
		}

		// Check cookie for display mode.
		if ( isset( $_COOKIE['apollo_display_mode'] ) ) {
			$mode           = sanitize_text_field( wp_unslash( $_COOKIE['apollo_display_mode'] ) );
			$this->pwa_mode = ( 'pwa' === $mode );
		}

		// Check custom header for PWA mode.
		if ( isset( $_SERVER['HTTP_X_APOLLO_PWA'] ) ) {
			$header         = sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_APOLLO_PWA'] ) );
			$this->pwa_mode = ( '1' === $header || 'true' === strtolower( $header ) );
		}

		// Use apollo-rio function if available.
		if ( $this->is_apollo_rio_active && function_exists( 'apollo_is_pwa' ) ) {
			$this->pwa_mode = apollo_is_pwa();
		}

		// Check if standalone mode for iOS PWA.
		if ( isset( $_SERVER['HTTP_USER_AGENT'] ) ) {
			$ua = sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) );
			// iOS standalone mode detection.
			if ( false !== strpos( $ua, 'iPhone' ) || false !== strpos( $ua, 'iPad' ) ) {
				if ( isset( $_SERVER['HTTP_X_REQUESTED_WITH'] ) && 'apollo-pwa' === $_SERVER['HTTP_X_REQUESTED_WITH'] ) {
					$this->pwa_mode = true;
				}
			}
		}
	}

	/**
	 * Check if PWA mode is active.
	 *
	 * @return bool True if in PWA mode.
	 */
	public function isPWAMode() {
		return $this->pwa_mode;
	}

	/**
	 * Check if apollo-rio is installed and active.
	 *
	 * @return bool True if apollo-rio is active.
	 */
	public function isApolloRioActive() {
		return $this->is_apollo_rio_active;
	}

	/**
	 * Get PWA installation instructions for iOS and Android.
	 *
	 * @return array|null Instructions array or null if rio not active.
	 */
	public function getPWAInstructions() {
		if ( ! $this->is_apollo_rio_active ) {
			return null;
		}

		return [
			'ios'     => [
				'title' => 'Instalar no iPhone',
				'steps' => [
					'1. Toque no botão de compartilhar (ícone de compartilhamento)',
					'2. Role até encontrar "Adicionar à Tela de Início"',
					'3. Toque em "Adicionar"',
					'4. O ícone do Apollo aparecerá na sua tela inicial',
				],
				'icon'  => 'ri-iphone-line',
			],
			'android' => [
				'title'        => 'Baixar para Android',
				'steps'        => [
					'1. Toque no menu (três pontos) no navegador',
					'2. Selecione "Adicionar à tela inicial" ou "Instalar app"',
					'3. Confirme a instalação',
					'4. O app Apollo será instalado no seu dispositivo',
				],
				'download_url' => $this->getAndroidDownloadUrl(),
				'icon'         => 'ri-android-line',
			],
		];
	}

	/**
	 * Determine if Apollo header should be shown.
	 *
	 * Shows header in app::rio mode when not clean.
	 *
	 * @return bool True if header should be displayed.
	 */
	public function shouldShowApolloHeader() {
		// Show header if apollo-rio is active and NOT in clean mode.
		return $this->is_apollo_rio_active && ! $this->isCleanMode();
	}

	/**
	 * Check if clean mode is enabled.
	 *
	 * Clean mode hides the apollo::rio header.
	 *
	 * @return bool True if clean mode is active.
	 */
	public function isCleanMode() {
		// Check for clean mode parameter or setting.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only display check.
		if ( isset( $_GET['clean'] ) && '1' === sanitize_text_field( wp_unslash( $_GET['clean'] ) ) ) {
			return true;
		}

		// Check option for persistent clean mode.
		$clean_mode = get_option( 'apollo_rio_clean_mode', false );
		return (bool) $clean_mode;
	}

	/**
	 * Get Android app download URL.
	 *
	 * @return string|null The download URL or null if not configured.
	 */
	private function getAndroidDownloadUrl() {
		// Check if apollo-rio provides the function.
		if ( $this->is_apollo_rio_active && function_exists( 'apollo_get_android_app_url' ) ) {
			$url = call_user_func( 'apollo_get_android_app_url' );
			if ( ! empty( $url ) ) {
				return esc_url_raw( $url );
			}
		}

		// Fallback to stored option.
		$url = get_option( 'apollo_android_app_url', '' );
		return ! empty( $url ) ? esc_url_raw( $url ) : null;
	}
}
