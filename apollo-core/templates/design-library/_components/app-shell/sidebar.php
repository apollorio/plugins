<?php
/**
 * Fixed desktop sidebar navigation.
 *
 * @var array $sections
 * @var array $user
 */

$sections = $sections ?? array(
	array(
		'title' => 'Navegação',
		'items' => array(
			array(
				'icon'  => 'ri-building-3-line',
				'label' => 'Feed',
				'href'  => '#',
			),
			array(
				'icon'  => 'ri-calendar-event-line',
				'label' => 'Eventos',
				'href'  => '#',
			),
			array(
				'icon'  => 'ri-user-community-fill',
				'label' => 'Comunidades',
				'href'  => '#',
			),
			array(
				'icon'  => 'ri-team-fill',
				'label' => 'Núcleos',
				'href'  => '#',
			),
			array(
				'icon'  => 'ri-megaphone-line',
				'label' => 'Classificados',
				'href'  => '#',
			),
			array(
				'icon'  => 'ri-file-text-line',
				'label' => 'Docs & Contratos',
				'href'  => '#',
			),
			array(
				'icon'  => 'ri-user-smile-fill',
				'label' => 'Perfil',
				'href'  => '#',
			),
		),
	),
	array(
		'title' => 'Cena::rio',
		'items' => array(
			array(
				'icon'  => 'ri-calendar-line',
				'label' => 'Agenda',
				'href'  => '#',
			),
			array(
				'icon'    => 'ri-bar-chart-grouped-line',
				'label'   => 'Fornecedores',
				'href'    => '#',
				'current' => true,
			),
			array(
				'icon'  => 'ri-file-text-line',
				'label' => 'Documentos',
				'href'  => '#',
			),
		),
	),
	array(
		'title' => 'Acesso Rápido',
		'items' => array(
			array(
				'icon'  => 'ri-settings-6-line',
				'label' => 'Ajustes',
				'href'  => '#',
			),
		),
	),
);

$user = wp_parse_args(
	$user ?? array(),
	array(
		'name'   => 'Valle',
		'role'   => 'Produtor',
		'avatar' => 'https://ui-avatars.com/api/?name=Valle&background=f97316&color=fff',
	)
);
?>
<aside class="ap-sidebar">
	<nav class="ap-sidebar-nav ap-no-scrollbar">
		<?php foreach ( $sections as $section ) : ?>
			<div class="ap-nav-section-title"><?php echo esc_html( $section['title'] ); ?></div>
			<?php
			foreach ( $section['items'] as $item ) :
				$current = ! empty( $item['current'] );
				?>
				<a
					href="<?php echo esc_url( $item['href'] ?? '#' ); ?>"
					class="ap-sidebar-link"
					<?php echo $current ? 'aria-current="page"' : ''; ?>
				>
					<i class="<?php echo esc_attr( $item['icon'] ?? 'ri-circle-line' ); ?>"></i>
					<span><?php echo esc_html( $item['label'] ?? '' ); ?></span>
				</a>
			<?php endforeach; ?>
		<?php endforeach; ?>
	</nav>
	<div class="ap-sidebar-user">
		<div class="ap-user-avatar"><img src="<?php echo esc_url( $user['avatar'] ); ?>" alt="<?php echo esc_attr( $user['name'] ); ?>"></div>
		<div class="ap-user-info">
			<span class="ap-user-name"><?php echo esc_html( $user['name'] ); ?></span>
			<span class="ap-user-role"><?php echo esc_html( $user['role'] ); ?></span>
		</div>
	</div>
</aside>
