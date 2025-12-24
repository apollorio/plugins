<?php

/**
 * Apollo Builder - Habbo-style Page Builder for Clubber Homes
 *
 * Based on patterns extracted from:
 * - WOW Page Builder (GPLv3): Layout JSON storage, addon system, AJAX save
 * - Live Composer (GPL): Capability checks, editor activation, module architecture
 *
 * @package Apollo_Social
 * @since 1.4.0
 */

defined( 'ABSPATH' ) || exit;

// Define Builder constants
define( 'APOLLO_BUILDER_VERSION', '1.0.0' );
define( 'APOLLO_BUILDER_DIR', plugin_dir_path( __FILE__ ) );
define( 'APOLLO_BUILDER_URL', plugin_dir_url( __FILE__ ) );

// Capability required to use builder (from Live Composer pattern)
define( 'APOLLO_BUILDER_CAPABILITY', 'edit_posts' );

// Post meta key for layout storage (from WOW pattern: _wow_content)
define( 'APOLLO_BUILDER_META_CONTENT', '_apollo_builder_content' );
define( 'APOLLO_BUILDER_META_CSS', '_apollo_builder_css' );
define( 'APOLLO_BUILDER_META_BACKGROUND', '_apollo_background_texture' );
define( 'APOLLO_BUILDER_META_TRAX', '_apollo_trax_url' );

// Load builder files
require_once APOLLO_BUILDER_DIR . 'class-apollo-home-cpt.php';
require_once APOLLO_BUILDER_DIR . 'class-apollo-builder-ajax.php';
require_once APOLLO_BUILDER_DIR . 'class-apollo-builder-frontend.php';
require_once APOLLO_BUILDER_DIR . 'class-apollo-builder-assets.php';
require_once APOLLO_BUILDER_DIR . 'class-apollo-builder-admin.php';
require_once APOLLO_BUILDER_DIR . 'class-apollo-builder-themes.php';
require_once APOLLO_BUILDER_DIR . 'class-apollo-builder-custom-styles.php';

// Load widget addons
require_once APOLLO_BUILDER_DIR . 'widgets/class-widget-base.php';
require_once APOLLO_BUILDER_DIR . 'widgets/class-widget-profile-card.php';
require_once APOLLO_BUILDER_DIR . 'widgets/class-widget-badges.php';
require_once APOLLO_BUILDER_DIR . 'widgets/class-widget-groups.php';
require_once APOLLO_BUILDER_DIR . 'widgets/class-widget-guestbook.php';
require_once APOLLO_BUILDER_DIR . 'widgets/class-widget-trax-player.php';
require_once APOLLO_BUILDER_DIR . 'widgets/class-widget-sticker.php';
require_once APOLLO_BUILDER_DIR . 'widgets/class-widget-note.php';

/**
 * Initialize Apollo Builder
 */
function apollo_builder_init() {
	// Initialize components
	Apollo_Home_CPT::init();
	Apollo_Builder_Ajax::init();
	Apollo_Builder_Frontend::init();

	// Admin only
	if ( is_admin() ) {
		Apollo_Builder_Assets_Admin::init();
		Apollo_Builder_Admin::init();
	}
}
add_action( 'plugins_loaded', 'apollo_builder_init', 20 );

/**
 * Get registered widgets (from WOW pattern: get_addon_classes)
 *
 * @return array Array of widget class names
 */
function apollo_builder_get_widgets() {
	static $widgets = null;

	if ( $widgets === null ) {
		$widgets = apply_filters(
			'apollo_builder_widgets',
			array(
				'Apollo_Widget_Profile_Card',
				'Apollo_Widget_Badges',
				'Apollo_Widget_Groups',
				'Apollo_Widget_Guestbook',
				'Apollo_Widget_Trax_Player',
				'Apollo_Widget_Sticker',
				'Apollo_Widget_Note',
			)
		);
	}

	return $widgets;
}

/**
 * Get widget instance by name
 *
 * @param string $name Widget name (e.g., 'profile-card')
 * @return Apollo_Widget_Base|null
 */
function apollo_builder_get_widget( $name ) {
	$widgets = apollo_builder_get_widgets();

	foreach ( $widgets as $class ) {
		if ( ! class_exists( $class ) ) {
			continue;
		}

		$instance = new $class();
		if ( $instance->get_name() === $name ) {
			return $instance;
		}
	}

	return null;
}

/**
 * Render widget output
 *
 * @param array $widget_data Widget data from layout JSON
 * @param int   $post_id The apollo_home post ID
 * @return string HTML output
 */
function apollo_builder_render_widget( $widget_data, $post_id = 0 ) {
	$name     = $widget_data['type'] ?? '';
	$instance = apollo_builder_get_widget( $name );

	if ( ! $instance ) {
		return '<!-- Unknown widget: ' . esc_html( $name ) . ' -->';
	}

	return $instance->render(
		array(
			'settings'  => $widget_data['config'] ?? array(),
			'post_id'   => $post_id,
			'widget_id' => $widget_data['id'] ?? '',
			'x'         => $widget_data['x'] ?? 0,
			'y'         => $widget_data['y'] ?? 0,
			'width'     => $widget_data['width'] ?? 200,
			'height'    => $widget_data['height'] ?? 150,
			'zIndex'    => $widget_data['zIndex'] ?? 1,
		)
	);
}

/**
 * Security: Verify builder nonce (from Live Composer pattern)
 *
 * @param string $nonce_value The nonce value
 * @param string $action The nonce action
 * @return bool
 */
function apollo_builder_verify_nonce( $nonce_value, $action = 'apollo-builder-nonce' ) {
	return wp_verify_nonce( $nonce_value, $action );
}

/**
 * Helper: Render tooltip HTML for form fields
 *
 * Tooltip: All DB-bound inputs MUST have a tooltip explaining their purpose
 * Pattern: Consistent tooltip UI across Apollo ecosystem
 *
 * @param string $text    Tooltip text
 * @param string $context 'admin' for wp-admin, 'frontend' for builder UI
 * @return string HTML for tooltip
 */
function apollo_builder_tooltip( $text, $context = 'admin' ) {
	$text = esc_attr( $text );

	if ( $context === 'admin' ) {
		return sprintf(
			'<span class="apollo-tooltip dashicons dashicons-editor-help" title="%s" data-tooltip="%s"></span>',
			$text,
			$text
		);
	}

	// Frontend builder context
	return sprintf(
		'<span class="builder-tooltip" data-tooltip="%s"><svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 17h-2v-2h2v2zm2.07-7.75l-.9.92C13.45 12.9 13 13.5 13 15h-2v-.5c0-1.1.45-2.1 1.17-2.83l1.24-1.26c.37-.36.59-.86.59-1.41 0-1.1-.9-2-2-2s-2 .9-2 2H8c0-2.21 1.79-4 4-4s4 1.79 4 4c0 .88-.36 1.68-.93 2.25z"/></svg></span>',
		$text
	);
}

/**
 * Helper: Render form field with tooltip
 *
 * @param string $type    Field type: text, url, select, switch
 * @param string $name    Field name attribute
 * @param string $label   Field label
 * @param string $tooltip Tooltip text
 * @param mixed  $value   Current value
 * @param array  $options Extra options (for select)
 * @return string HTML
 */
function apollo_builder_field( $type, $name, $label, $tooltip, $value = '', $options = array() ) {
	$tooltip_html = apollo_builder_tooltip( $tooltip, 'admin' );
	$id           = sanitize_key( $name );
	$value        = is_string( $value ) ? esc_attr( $value ) : $value;

	$html  = '<div class="apollo-field apollo-field--' . esc_attr( $type ) . '">';
	$html .= '<label for="' . $id . '">' . esc_html( $label ) . ' ' . $tooltip_html . '</label>';

	switch ( $type ) {
		case 'text':
		case 'url':
			$html .= '<input type="' . $type . '" id="' . $id . '" name="' . esc_attr( $name ) . '" value="' . $value . '" class="regular-text">';

			break;

		case 'textarea':
			$html .= '<textarea id="' . $id . '" name="' . esc_attr( $name ) . '" rows="4" class="large-text">' . esc_textarea( $value ) . '</textarea>';

			break;

		case 'select':
			$html .= '<select id="' . $id . '" name="' . esc_attr( $name ) . '">';
			foreach ( $options as $opt_value => $opt_label ) {
				$selected = selected( $value, $opt_value, false );
				$html    .= '<option value="' . esc_attr( $opt_value ) . '"' . $selected . '>' . esc_html( $opt_label ) . '</option>';
			}
			$html .= '</select>';

			break;

		case 'switch':
			$checked = $value ? 'checked' : '';
			$html   .= '<label class="apollo-switch">';
			$html   .= '<input type="checkbox" id="' . $id . '" name="' . esc_attr( $name ) . '" value="1" ' . $checked . '>';
			$html   .= '<span class="apollo-switch-slider"></span>';
			$html   .= '</label>';

			break;
	}//end switch

	$html .= '</div>';

	return $html;
}

/**
 * Security: Check if current user can use builder (from Live Composer pattern)
 *
 * @return bool
 */
function apollo_builder_user_can_edit() {
	return is_user_logged_in() && current_user_can( APOLLO_BUILDER_CAPABILITY );
}

/**
 * Helper: Sanitize layout JSON (security measure)
 *
 * @param string $json Raw JSON string
 * @return string Sanitized JSON
 */
function apollo_builder_sanitize_layout( $json ) {
	$data = json_decode( $json, true );

	if ( json_last_error() !== JSON_ERROR_NONE || ! is_array( $data ) ) {
		return wp_json_encode( array( 'widgets' => array() ) );
	}

	if ( ! isset( $data['widgets'] ) || ! is_array( $data['widgets'] ) ) {
		$data['widgets'] = array();
	}

	$allowed_types     = array( 'profile-card', 'badges', 'groups', 'guestbook', 'trax-player', 'sticker', 'note' );
	$sanitized_widgets = array();

	foreach ( $data['widgets'] as $widget ) {
		if ( ! is_array( $widget ) ) {
			continue;
		}
		if ( empty( $widget['id'] ) || empty( $widget['type'] ) ) {
			continue;
		}
		if ( ! in_array( $widget['type'], $allowed_types, true ) ) {
			continue;
		}

		$sanitized = array(
			'id'     => sanitize_key( $widget['id'] ),
			'type'   => sanitize_key( $widget['type'] ),
			'x'      => max( 0, intval( $widget['x'] ?? 0 ) ),
			'y'      => max( 0, intval( $widget['y'] ?? 0 ) ),
			'width'  => max( 48, min( 800, intval( $widget['width'] ?? 200 ) ) ),
			'height' => max( 48, min( 600, intval( $widget['height'] ?? 150 ) ) ),
			'zIndex' => max( 1, min( 100, intval( $widget['zIndex'] ?? 1 ) ) ),
			'config' => array(),
		);

		// Sanitize config based on type
		if ( isset( $widget['config'] ) && is_array( $widget['config'] ) ) {
			$sanitized['config'] = apollo_builder_sanitize_widget_config( $widget['type'], $widget['config'] );
		}

		$sanitized_widgets[] = $sanitized;
	}//end foreach

	// Max 50 widgets
	$sanitized_widgets = array_slice( $sanitized_widgets, 0, 50 );

	return wp_json_encode( array( 'widgets' => $sanitized_widgets ) );
}

/**
 * Sanitize widget config based on type
 *
 * @param string $type Widget type
 * @param array  $config Config array
 * @return array Sanitized config
 */
function apollo_builder_sanitize_widget_config( $type, $config ) {
	$sanitized = array();

	switch ( $type ) {
		case 'sticker':
			if ( isset( $config['stickerId'] ) ) {
				$sanitized['stickerId'] = sanitize_key( $config['stickerId'] );
			}
			if ( isset( $config['rotation'] ) ) {
				$sanitized['rotation'] = max( -180, min( 180, intval( $config['rotation'] ) ) );
			}
			if ( isset( $config['flip'] ) ) {
				$sanitized['flip'] = ! empty( $config['flip'] );
			}

			break;

		case 'note':
			if ( isset( $config['text'] ) ) {
				$sanitized['text'] = sanitize_textarea_field( substr( $config['text'], 0, 500 ) );
			}
			if ( isset( $config['color'] ) ) {
				$sanitized['color'] = sanitize_hex_color( $config['color'] ) ?: '#ffff88';
			}
			if ( isset( $config['text_color'] ) ) {
				$sanitized['text_color'] = sanitize_hex_color( $config['text_color'] ) ?: '#333333';
			}
			if ( isset( $config['font_size'] ) ) {
				$sanitized['font_size'] = max( 10, min( 24, intval( $config['font_size'] ) ) );
			}
			if ( isset( $config['rotation'] ) ) {
				$sanitized['rotation'] = max( -15, min( 15, intval( $config['rotation'] ) ) );
			}

			break;

		case 'trax-player':
			if ( isset( $config['url'] ) ) {
				$url = esc_url_raw( $config['url'] );
				// Only allow SoundCloud/Spotify
				if ( strpos( $url, 'soundcloud.com' ) !== false || strpos( $url, 'spotify.com' ) !== false || strpos( $url, 'open.spotify.com' ) !== false ) {
					$sanitized['url'] = $url;
				}
			}
			if ( isset( $config['autoplay'] ) ) {
				$sanitized['autoplay'] = ! empty( $config['autoplay'] );
			}
			if ( isset( $config['show_artwork'] ) ) {
				$sanitized['show_artwork'] = ! empty( $config['show_artwork'] );
			}
			if ( isset( $config['color'] ) ) {
				$sanitized['color'] = sanitize_hex_color( $config['color'] ) ?: '#ff5500';
			}

			break;

		case 'profile-card':
			if ( isset( $config['label'] ) ) {
				$sanitized['label'] = sanitize_text_field( substr( $config['label'], 0, 50 ) );
			}
			if ( isset( $config['show_location'] ) ) {
				$sanitized['show_location'] = ! empty( $config['show_location'] );
			}
			if ( isset( $config['show_date'] ) ) {
				$sanitized['show_date'] = ! empty( $config['show_date'] );
			}
			if ( isset( $config['show_pronouns'] ) ) {
				$sanitized['show_pronouns'] = ! empty( $config['show_pronouns'] );
			}

			break;

		case 'badges':
			if ( isset( $config['layout'] ) ) {
				$sanitized['layout'] = in_array( $config['layout'], array( 'row', 'grid', 'stack' ) ) ? $config['layout'] : 'row';
			}
			if ( isset( $config['badge_size'] ) ) {
				$sanitized['badge_size'] = max( 24, min( 80, intval( $config['badge_size'] ) ) );
			}
			if ( isset( $config['show_titles'] ) ) {
				$sanitized['show_titles'] = ! empty( $config['show_titles'] );
			}

			break;

		case 'groups':
			if ( isset( $config['max_groups'] ) ) {
				$sanitized['max_groups'] = max( 1, min( 20, intval( $config['max_groups'] ) ) );
			}
			if ( isset( $config['layout'] ) ) {
				$sanitized['layout'] = in_array( $config['layout'], array( 'grid', 'list' ) ) ? $config['layout'] : 'grid';
			}
			if ( isset( $config['show_names'] ) ) {
				$sanitized['show_names'] = ! empty( $config['show_names'] );
			}

			break;

		case 'guestbook':
			if ( isset( $config['max_comments'] ) ) {
				$sanitized['max_comments'] = max( 1, min( 20, intval( $config['max_comments'] ) ) );
			}
			if ( isset( $config['show_form'] ) ) {
				$sanitized['show_form'] = ! empty( $config['show_form'] );
			}
			if ( isset( $config['show_avatars'] ) ) {
				$sanitized['show_avatars'] = ! empty( $config['show_avatars'] );
			}

			break;
	}//end switch

	return $sanitized;
}
