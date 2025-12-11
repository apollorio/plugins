<?php
/**
 * Global app navbar component.
 *
 * @var array $logo
 * @var bool  $show_clock
 * @var string $clock_id
 * @var array $buttons
 */

$logo       = wp_parse_args(
	$logo ?? array(),
	array(
		'icon'     => 'ri-slack-fill',
		'title'    => 'Apollo::rio',
		'subtitle' => 'plataforma',
	)
);
$show_clock = isset( $show_clock ) ? (bool) $show_clock : true;
$clock_id   = $clock_id ?? 'ap-digital-clock';
$buttons    = $buttons ?? array(
	array(
		'id'       => 'ap-btn-notif',
		'label'    => 'Notificações',
		'icon'     => 'ri-gps-line',
		'browser'  => 'button',
		'class'    => '',
		'badge'    => true,
	),
	array(
		'id'    => 'ap-btn-apps',
		'label' => 'Aplicativos',
		'icon'  => 'ri-grid-fill',
	),
	array(
		'id'      => 'ap-btn-profile',
		'label'   => 'Perfil',
		'content' => 'V',
		'class'   => 'ap-profile-btn',
	),
);
?>
<nav class="ap-navbar">
	<div class="ap-logo-wrapper">
		<div class="ap-logo-desktop">
			<div class="ap-logo-icon"><i class="<?php echo esc_attr( $logo['icon'] ); ?>"></i></div>
			<div class="ap-logo-text">
				<span class="ap-logo-title"><?php echo esc_html( $logo['title'] ); ?></span>
				<span class="ap-logo-subtitle"><?php echo esc_html( $logo['subtitle'] ); ?></span>
			</div>
		</div>
		<div class="ap-logo-mobile">
			<div class="ap-logo-icon"><i class="<?php echo esc_attr( $logo['icon'] ); ?>"></i></div>
		</div>
	</div>
	<div class="ap-nav-controls">
		<?php if ( $show_clock ) : ?>
			<div class="ap-clock" id="<?php echo esc_attr( $clock_id ); ?>">00:00:00</div>
		<?php endif; ?>
		<?php foreach ( $buttons as $button ) :
			$button_id    = $button['id'] ?? '';
			$button_label = $button['label'] ?? '';
			$button_icon  = $button['icon'] ?? '';
			$button_text  = $button['content'] ?? '';
			$button_class = 'ap-nav-btn ' . trim( (string) ( $button['class'] ?? '' ) );
			?>
			<button
				<?php if ( $button_id ) : ?>id="<?php echo esc_attr( $button_id ); ?>"<?php endif; ?>
				class="<?php echo esc_attr( $button_class ); ?>"
				aria-label="<?php echo esc_attr( $button_label ); ?>"
				aria-expanded="false"
				type="button"
			>
				<?php if ( ! empty( $button['badge'] ) ) : ?>
					<div class="ap-badge" data-notif="<?php echo esc_attr( $button['badge'] ? 'true' : 'false' ); ?>"></div>
				<?php endif; ?>
				<?php if ( $button_icon ) : ?>
					<i class="<?php echo esc_attr( $button_icon ); ?>"></i>
				<?php else : ?>
					<?php echo esc_html( $button_text ); ?>
				<?php endif; ?>
			</button>
		<?php endforeach; ?>
	</div>
</nav>
