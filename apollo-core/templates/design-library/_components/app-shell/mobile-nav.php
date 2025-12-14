<?php
/**
 * Bottom mobile navigation with optional floating action button.
 *
 * @var array $items
 * @var array $fab
 */

$items = $items ?? [
	[
		'label' => 'Agenda',
		'icon'  => 'ri-calendar-line',
		'href'  => '#',
	],
	[
		'label'  => 'Pro',
		'icon'   => 'ri-bar-chart-grouped-line',
		'href'   => '#',
		'active' => true,
	],
	[
		'label' => 'Docs',
		'icon'  => 'ri-file-text-line',
		'href'  => '#',
	],
	[
		'label' => 'Perfil',
		'icon'  => 'ri-user-3-line',
		'href'  => '#',
	],
];
$fab   = wp_parse_args(
	$fab ?? [],
	[
		'icon'     => 'ri-add-line',
		'href'     => '#',
		'disabled' => false,
	]
);
?>
<div class="ap-mobile-nav">
	<div class="ap-mobile-nav-inner">
		<?php
		$half = (int) ceil( count( $items ) / 2 );
		foreach ( $items as $index => $item ) :
			$classes = 'ap-mobile-nav-item' . ( ! empty( $item['active'] ) ? ' ap-active' : '' );
			?>
			<?php if ( $index === $half ) : ?>
				<div class="ap-mobile-nav-fab">
					<a class="ap-fab-button<?php echo $fab['disabled'] ? ' is-disabled' : ''; ?>" href="<?php echo esc_url( $fab['href'] ); ?>">
						<i class="<?php echo esc_attr( $fab['icon'] ); ?>"></i>
					</a>
				</div>
			<?php endif; ?>
			<a class="<?php echo esc_attr( $classes ); ?>" href="<?php echo esc_url( $item['href'] ?? '#' ); ?>">
				<i class="<?php echo esc_attr( $item['icon'] ?? 'ri-checkbox-blank-circle-line' ); ?>"></i>
				<span class="ap-mobile-nav-label"><?php echo esc_html( $item['label'] ?? '' ); ?></span>
			</a>
		<?php endforeach; ?>
	</div>
</div>
