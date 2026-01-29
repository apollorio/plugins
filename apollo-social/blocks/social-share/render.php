<?php
/**
 * Apollo Social Share Block - Server-Side Render
 *
 * Renders social sharing buttons for various networks.
 *
 * @package Apollo_Social
 * @subpackage Blocks
 * @since 2.0.0
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Block content.
 * @var WP_Block $block      Block instance.
 */

declare(strict_types=1);

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Extract attributes with defaults.
$networks     = $attributes['networks'] ?? array( 'facebook', 'twitter', 'whatsapp', 'linkedin', 'telegram' );
$style        = $attributes['style'] ?? 'icons';
$size         = $attributes['size'] ?? 'medium';
$shape        = $attributes['shape'] ?? 'rounded';
$show_labels  = $attributes['showLabels'] ?? false;
$show_counts  = $attributes['showCounts'] ?? false;
$alignment    = $attributes['alignment'] ?? 'left';
$custom_url   = $attributes['customUrl'] ?? '';
$custom_title = $attributes['customTitle'] ?? '';
$class_name   = $attributes['className'] ?? '';

// Determine share URL and title.
$share_url   = ! empty( $custom_url ) ? $custom_url : get_permalink();
$share_title = ! empty( $custom_title ) ? $custom_title : get_the_title();
$share_text  = rawurlencode( $share_title );
$encoded_url = rawurlencode( $share_url );

// Network configurations.
$network_config = array(
	'facebook'  => array(
		'label' => 'Facebook',
		'icon'  => 'ri-facebook-fill',
		'color' => '#1877f2',
		'url'   => "https://www.facebook.com/sharer/sharer.php?u={$encoded_url}",
	),
	'twitter'   => array(
		'label' => 'X (Twitter)',
		'icon'  => 'ri-twitter-x-fill',
		'color' => '#000000',
		'url'   => "https://twitter.com/intent/tweet?url={$encoded_url}&text={$share_text}",
	),
	'whatsapp'  => array(
		'label' => 'WhatsApp',
		'icon'  => 'ri-whatsapp-fill',
		'color' => '#25d366',
		'url'   => "https://api.whatsapp.com/send?text={$share_text}%20{$encoded_url}",
	),
	'linkedin'  => array(
		'label' => 'LinkedIn',
		'icon'  => 'ri-linkedin-fill',
		'color' => '#0a66c2',
		'url'   => "https://www.linkedin.com/sharing/share-offsite/?url={$encoded_url}",
	),
	'telegram'  => array(
		'label' => 'Telegram',
		'icon'  => 'ri-telegram-fill',
		'color' => '#0088cc',
		'url'   => "https://telegram.me/share/url?url={$encoded_url}&text={$share_text}",
	),
	'pinterest' => array(
		'label' => 'Pinterest',
		'icon'  => 'ri-pinterest-fill',
		'color' => '#e60023',
		'url'   => "https://pinterest.com/pin/create/button/?url={$encoded_url}&description={$share_text}",
	),
	'email'     => array(
		'label' => __( 'Email', 'apollo-social' ),
		'icon'  => 'ri-mail-fill',
		'color' => '#64748b',
		'url'   => "mailto:?subject={$share_text}&body={$encoded_url}",
	),
	'copy'      => array(
		'label' => __( 'Copiar Link', 'apollo-social' ),
		'icon'  => 'ri-link',
		'color' => '#475569',
		'url'   => '#',
	),
);

// Generate unique ID.
$share_id = 'apollo-share-' . wp_unique_id();

// Build wrapper classes.
$wrapper_classes = array(
	'apollo-social-share-block',
	'apollo-social-share',
	"apollo-social-share--{$style}",
	"apollo-social-share--{$size}",
	"apollo-social-share--{$shape}",
	"apollo-social-share--align-{$alignment}",
);

if ( ! empty( $class_name ) ) {
	$wrapper_classes[] = $class_name;
}

// Get block wrapper attributes.
$wrapper_attributes = get_block_wrapper_attributes(
	array(
		'class' => implode( ' ', $wrapper_classes ),
		'id'    => $share_id,
	)
);

// Filter to only selected networks.
$active_networks = array_filter(
	$network_config,
	fn( $key ) => in_array( $key, $networks, true ),
	ARRAY_FILTER_USE_KEY
);

if ( empty( $active_networks ) ) {
	return;
}
?>

<div <?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<?php foreach ( $active_networks as $network_key => $config ) : ?>
		<?php
		$button_classes = array(
			'apollo-share-button',
			"apollo-share-button--{$network_key}",
		);

		$button_style = '';
		if ( 'colored' === $style ) {
			$button_style = "background-color: {$config['color']}; color: #fff;";
		} elseif ( 'outline' === $style ) {
			$button_style = "border-color: {$config['color']}; color: {$config['color']};";
		}

		$is_copy_link = 'copy' === $network_key;
		?>

		<a
			href="<?php echo esc_url( $config['url'] ); ?>"
			class="<?php echo esc_attr( implode( ' ', $button_classes ) ); ?>"
			style="<?php echo esc_attr( $button_style ); ?>"
			<?php if ( ! $is_copy_link ) : ?>
				target="_blank"
				rel="noopener noreferrer"
			<?php else : ?>
				data-copy-url="<?php echo esc_url( $share_url ); ?>"
			<?php endif; ?>
			title="<?php echo esc_attr( $config['label'] ); ?>"
			aria-label="
			<?php
				printf(
					/* translators: %s: network name */
					esc_attr__( 'Compartilhar no %s', 'apollo-social' ),
					$config['label']
				);
			?>
			"
		>
			<i class="<?php echo esc_attr( $config['icon'] ); ?>"></i>
			<?php if ( $show_labels ) : ?>
				<span class="apollo-share-button__label"><?php echo esc_html( $config['label'] ); ?></span>
			<?php endif; ?>
		</a>
	<?php endforeach; ?>
</div>

<style>
#<?php echo esc_attr( $share_id ); ?> {
	display: flex;
	flex-wrap: wrap;
	gap: 0.5rem;
}

#<?php echo esc_attr( $share_id ); ?>.apollo-social-share--align-center {
	justify-content: center;
}

#<?php echo esc_attr( $share_id ); ?>.apollo-social-share--align-right {
	justify-content: flex-end;
}

#<?php echo esc_attr( $share_id ); ?> .apollo-share-button {
	display: inline-flex;
	align-items: center;
	justify-content: center;
	gap: 0.375rem;
	text-decoration: none;
	transition: all 0.2s ease;
	border: 2px solid transparent;
}

/* Size variations */
#<?php echo esc_attr( $share_id ); ?>.apollo-social-share--small .apollo-share-button {
	width: 32px;
	height: 32px;
	font-size: 0.875rem;
}

#<?php echo esc_attr( $share_id ); ?>.apollo-social-share--small .apollo-share-button:has(.apollo-share-button__label) {
	width: auto;
	padding: 0 0.75rem;
}

#<?php echo esc_attr( $share_id ); ?>.apollo-social-share--medium .apollo-share-button {
	width: 40px;
	height: 40px;
	font-size: 1.125rem;
}

#<?php echo esc_attr( $share_id ); ?>.apollo-social-share--medium .apollo-share-button:has(.apollo-share-button__label) {
	width: auto;
	padding: 0 1rem;
}

#<?php echo esc_attr( $share_id ); ?>.apollo-social-share--large .apollo-share-button {
	width: 48px;
	height: 48px;
	font-size: 1.375rem;
}

#<?php echo esc_attr( $share_id ); ?>.apollo-social-share--large .apollo-share-button:has(.apollo-share-button__label) {
	width: auto;
	padding: 0 1.25rem;
}

/* Shape variations */
#<?php echo esc_attr( $share_id ); ?>.apollo-social-share--rounded .apollo-share-button {
	border-radius: 8px;
}

#<?php echo esc_attr( $share_id ); ?>.apollo-social-share--circle .apollo-share-button {
	border-radius: 50%;
}

#<?php echo esc_attr( $share_id ); ?>.apollo-social-share--square .apollo-share-button {
	border-radius: 0;
}

/* Style variations */
#<?php echo esc_attr( $share_id ); ?>.apollo-social-share--icons .apollo-share-button {
	background: #f1f5f9;
	color: inherit;
}

#<?php echo esc_attr( $share_id ); ?>.apollo-social-share--icons .apollo-share-button:hover {
	background: #e2e8f0;
	transform: scale(1.1);
}

#<?php echo esc_attr( $share_id ); ?>.apollo-social-share--colored .apollo-share-button:hover {
	opacity: 0.9;
	transform: scale(1.1);
}

#<?php echo esc_attr( $share_id ); ?>.apollo-social-share--outline .apollo-share-button {
	background: transparent;
}

#<?php echo esc_attr( $share_id ); ?>.apollo-social-share--outline .apollo-share-button:hover {
	transform: scale(1.1);
}

#<?php echo esc_attr( $share_id ); ?>.apollo-social-share--minimal .apollo-share-button {
	background: transparent;
	color: #64748b;
}

#<?php echo esc_attr( $share_id ); ?>.apollo-social-share--minimal .apollo-share-button:hover {
	color: #1e293b;
}

/* Network colors for icons style */
#<?php echo esc_attr( $share_id ); ?>.apollo-social-share--icons .apollo-share-button--facebook { color: #1877f2; }
#<?php echo esc_attr( $share_id ); ?>.apollo-social-share--icons .apollo-share-button--twitter { color: #000000; }
#<?php echo esc_attr( $share_id ); ?>.apollo-social-share--icons .apollo-share-button--whatsapp { color: #25d366; }
#<?php echo esc_attr( $share_id ); ?>.apollo-social-share--icons .apollo-share-button--linkedin { color: #0a66c2; }
#<?php echo esc_attr( $share_id ); ?>.apollo-social-share--icons .apollo-share-button--telegram { color: #0088cc; }
#<?php echo esc_attr( $share_id ); ?>.apollo-social-share--icons .apollo-share-button--pinterest { color: #e60023; }
#<?php echo esc_attr( $share_id ); ?>.apollo-social-share--icons .apollo-share-button--email { color: #64748b; }
#<?php echo esc_attr( $share_id ); ?>.apollo-social-share--icons .apollo-share-button--copy { color: #475569; }

/* Label styling */
#<?php echo esc_attr( $share_id ); ?> .apollo-share-button__label {
	font-size: 0.875rem;
	font-weight: 500;
}
</style>

<script>
(function() {
	const container = document.getElementById('<?php echo esc_js( $share_id ); ?>');
	if (!container) return;

	// Handle copy link button.
	const copyButtons = container.querySelectorAll('[data-copy-url]');
	copyButtons.forEach(btn => {
		btn.addEventListener('click', function(e) {
			e.preventDefault();
			const url = this.dataset.copyUrl;

			if (navigator.clipboard) {
				navigator.clipboard.writeText(url).then(() => {
					// Show feedback.
					const icon = this.querySelector('i');
					const originalClass = icon.className;
					icon.className = 'ri-check-line';

					setTimeout(() => {
						icon.className = originalClass;
					}, 2000);
				});
			} else {
				// Fallback for older browsers.
				const textArea = document.createElement('textarea');
				textArea.value = url;
				document.body.appendChild(textArea);
				textArea.select();
				document.execCommand('copy');
				document.body.removeChild(textArea);
			}
		});
	});
})();
</script>
