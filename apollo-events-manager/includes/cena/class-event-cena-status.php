<?php
/**
 * Apollo Cena Event Status Configuration
 *
 * Defines event_cena_* status values, colors, and labels.
 * Single source of truth for CENA calendar status system.
 *
 * @package Apollo_Events_Manager
 * @since 2.0.0
 *
 * STRICT MODE: All event_cena_* status definitions MUST be here only.
 */

namespace Apollo\Events\Cena;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Event_Cena_Status
 *
 * Manages event_cena_status meta field values and their display properties.
 */
class Event_Cena_Status {

	/**
	 * Meta key for storing cena status.
	 */
	const META_KEY = 'event_cena_status';

	/**
	 * Default status when not set.
	 */
	const DEFAULT_STATUS = 'previsto';

	/**
	 * Status configurations.
	 *
	 * @var array
	 */
	private static $statuses = null;

	/**
	 * Get all status configurations.
	 *
	 * @return array Status configurations with id, label, color, icon, css_class.
	 */
	public static function get_statuses() {
		if ( null === self::$statuses ) {
			self::$statuses = [
				'previsto'   => [
					'id'        => 'previsto',
					'label'     => 'Previsto',
					'color'     => '#f97316', // Orange
					'bg_color'  => 'rgba(249, 115, 22, 0.1)',
					'icon'      => 'ri-time-line',
					'css_class' => 'event-cena-status--previsto',
					'order'     => 1,
				],
				'confirmado' => [
					'id'        => 'confirmado',
					'label'     => 'Confirmado',
					'color'     => '#10b981', // Green
					'bg_color'  => 'rgba(16, 185, 129, 0.1)',
					'icon'      => 'ri-check-double-line',
					'css_class' => 'event-cena-status--confirmado',
					'order'     => 2,
				],
				'adiado'     => [
					'id'        => 'adiado',
					'label'     => 'Adiado',
					'color'     => '#a855f7', // Purple
					'bg_color'  => 'rgba(168, 85, 247, 0.1)',
					'icon'      => 'ri-calendar-close-line',
					'css_class' => 'event-cena-status--adiado',
					'order'     => 3,
				],
				'cancelado'  => [
					'id'        => 'cancelado',
					'label'     => 'Cancelado',
					'color'     => '#ef4444', // Red
					'bg_color'  => 'rgba(239, 68, 68, 0.1)',
					'icon'      => 'ri-close-circle-line',
					'css_class' => 'event-cena-status--cancelado',
					'order'     => 4,
				],
			];

			/**
			 * Filter to extend or modify event_cena_* statuses.
			 *
			 * @since 2.0.0
			 *
			 * @param array $statuses Status configurations.
			 */
			self::$statuses = apply_filters( 'apollo_event_cena_statuses', self::$statuses );
		}

		return self::$statuses;
	}

	/**
	 * Get a single status configuration.
	 *
	 * @param string $status_id Status identifier.
	 * @return array|null Status configuration or null if not found.
	 */
	public static function get_status( $status_id ) {
		$statuses = self::get_statuses();
		return $statuses[ $status_id ] ?? null;
	}

	/**
	 * Get status label for display.
	 *
	 * @param string $status_id Status identifier.
	 * @return string Status label.
	 */
	public static function get_label( $status_id ) {
		$status = self::get_status( $status_id );
		return $status ? $status['label'] : \ucfirst( $status_id );
	}

	/**
	 * Get status color.
	 *
	 * @param string $status_id Status identifier.
	 * @return string Hex color code.
	 */
	public static function get_color( $status_id ) {
		$status = self::get_status( $status_id );
		return $status ? $status['color'] : '#64748b';
	}

	/**
	 * Get status from post.
	 *
	 * @param int $post_id Post ID.
	 * @return string Status identifier.
	 */
	public static function get_post_status( $post_id ) {
		$status = get_post_meta( $post_id, self::META_KEY, true );

		// Fallback for legacy status values.
		$legacy_map = [
			'expected'  => 'previsto',
			'confirmed' => 'confirmado',
			'published' => 'confirmado',
		];

		if ( isset( $legacy_map[ $status ] ) ) {
			$status = $legacy_map[ $status ];
		}

		return $status ?: self::DEFAULT_STATUS;
	}

	/**
	 * Set status on post.
	 *
	 * @param int    $post_id Post ID.
	 * @param string $status  Status identifier.
	 * @return bool True on success.
	 */
	public static function set_post_status( $post_id, $status ) {
		$statuses = self::get_statuses();

		if ( ! isset( $statuses[ $status ] ) ) {
			$status = self::DEFAULT_STATUS;
		}

		return (bool) update_post_meta( $post_id, self::META_KEY, $status );
	}

	/**
	 * Render status badge HTML.
	 *
	 * @param string $status_id Status identifier.
	 * @param bool   $with_icon Whether to include icon.
	 * @return string HTML markup.
	 */
	public static function render_badge( $status_id, $with_icon = true ) {
		$status = self::get_status( $status_id );

		if ( ! $status ) {
			$status = self::get_status( self::DEFAULT_STATUS );
		}

		$icon_html = $with_icon ? sprintf( '<i class="%s"></i>', esc_attr( $status['icon'] ) ) : '';

		return sprintf(
			'<span class="event-status-badge %s" style="background: %s; color: %s;">%s%s</span>',
			esc_attr( $status['css_class'] ),
			esc_attr( $status['bg_color'] ),
			esc_attr( $status['color'] ),
			$icon_html,
			esc_html( $status['label'] )
		);
	}

	/**
	 * Get CSS variables for all statuses.
	 *
	 * @return string CSS custom properties.
	 */
	public static function get_css_variables() {
		$css = ':root {' . PHP_EOL;

		foreach ( self::get_statuses() as $id => $status ) {
			$css .= sprintf( '    --ap-color-%s: %s;', $id, $status['color'] ) . PHP_EOL;
			$css .= sprintf( '    --ap-bg-%s: %s;', $id, $status['bg_color'] ) . PHP_EOL;
		}

		$css .= '}' . PHP_EOL;

		return $css;
	}

	/**
	 * Get status options for JavaScript.
	 *
	 * @return array Statuses formatted for JS consumption.
	 */
	public static function get_js_config() {
		$config = [];

		foreach ( self::get_statuses() as $id => $status ) {
			$config[ $id ] = [
				'id'       => $id,
				'label'    => $status['label'],
				'color'    => $status['color'],
				'bgColor'  => $status['bg_color'],
				'icon'     => $status['icon'],
				'cssClass' => $status['css_class'],
			];
		}

		return $config;
	}
}

// Register meta field for REST API.
add_action( 'init', function() {
	register_post_meta(
		'event_listing',
		Event_Cena_Status::META_KEY,
		[
			'show_in_rest'  => true,
			'single'        => true,
			'type'          => 'string',
			'default'       => Event_Cena_Status::DEFAULT_STATUS,
			'auth_callback' => function() {
				return current_user_can( 'edit_posts' );
			},
		]
	);
}, 20 );
