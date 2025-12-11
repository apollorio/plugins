<?php
/**
 * Collection of dropdown menus (notifications, apps grid, profile menu).
 *
 * @var array $notifications
 * @var array $app_grid
 * @var array $messages
 * @var array $profile_links
 */

$notifications = $notifications ?? array(
	array(
		'icon_class' => 'ap-bg-red',
		'icon'       => '<i class="ri-alarm-warning-line"></i>',
		'title'      => 'Sistema',
		'desc'       => 'Backup do servidor finalizado.',
		'time'       => '2m atrás',
	),
	array(
		'icon_class' => 'ap-bg-blue',
		'icon'       => '<i class="ri-user-follow-line"></i>',
		'title'      => 'Novo Seguidor',
		'desc'       => 'Spectra Visuals começou a seguir você.',
		'time'       => '1h atrás',
	),
	array(
		'icon_class' => 'ap-bg-green',
		'icon'       => '<i class="ri-check-double-line"></i>',
		'title'      => 'Pagamento Aprovado',
		'desc'       => 'Orçamento #4523 foi aprovado.',
		'time'       => '3h atrás',
	),
);

$app_grid = $app_grid ?? array(
	array('label' => 'Excel', 'abbr' => 'XL', 'class' => 'ap-bg-green'),
	array('label' => 'Word', 'abbr' => 'WD', 'class' => 'ap-bg-blue'),
	array('label' => 'Slide', 'abbr' => 'PP', 'class' => 'ap-bg-orange'),
	array('label' => 'Teams', 'abbr' => 'TM', 'class' => 'ap-bg-purple'),
	array('label' => 'Leitor', 'abbr' => 'PDF', 'class' => 'ap-bg-red'),
	array('label' => 'Drive', 'abbr' => 'DR', 'class' => 'ap-bg-gray'),
	array('label' => 'Meet', 'abbr' => 'MT', 'class' => 'ap-bg-blue'),
	array('label' => 'Add', 'abbr' => '+', 'class' => 'ap-bg-gray'),
);

$messages = $messages ?? array(
	array('avatar' => 'M', 'color' => 'ap-bg-gray', 'name' => 'Matheus', 'time' => 'Agora', 'preview' => 'Cara, você viu o novo layout? Ficou insano!'),
	array('avatar' => 'B', 'color' => 'ap-bg-purple', 'name' => 'Bruna', 'time' => '5m atrás', 'preview' => 'Reunião adiada para as 16h.'),
	array('avatar' => 'L', 'color' => 'ap-bg-blue', 'name' => 'Lucas', 'time' => '20m atrás', 'preview' => 'Enviou os documentos do projeto.'),
);

$profile_links = $profile_links ?? array(
	array('label' => 'Perfil', 'href' => '#'),
	array('label' => 'Ajustes', 'href' => '#'),
	array('label' => 'Sair', 'href' => '#', 'class' => 'ap-danger'),
);
?>
<div id="ap-menu-notif" class="ap-dropdown">
	<div class="ap-section-title">
		<span>Notificações</span>
		<a href="#" class="ap-see-all">Ver todas &gt;</a>
	</div>
	<div class="ap-notif-list" id="ap-notif-list">
		<?php foreach ( $notifications as $notif ) : ?>
			<a href="#" class="ap-notif-item">
				<div class="ap-notif-icon <?php echo esc_attr( $notif['icon_class'] ?? '' ); ?>"><?php echo wp_kses_post( $notif['icon'] ?? '' ); ?></div>
				<div class="ap-notif-content">
					<span class="ap-notif-title"><?php echo esc_html( $notif['title'] ?? '' ); ?></span>
					<span class="ap-notif-desc"><?php echo esc_html( $notif['desc'] ?? '' ); ?></span>
					<span class="ap-notif-time"><?php echo esc_html( $notif['time'] ?? '' ); ?></span>
				</div>
			</a>
		<?php endforeach; ?>
	</div>
	<div class="ap-load-more" id="ap-load-more-notif">Carregar mais notificações</div>
</div>

<div id="ap-menu-app" class="ap-dropdown">
	<div class="ap-section-title">Aplicativos</div>
	<div class="ap-apps-grid">
		<?php foreach ( $app_grid as $app ) : ?>
			<a href="#" class="ap-app-item">
				<div class="ap-app-icon <?php echo esc_attr( $app['class'] ?? '' ); ?>"><?php echo esc_html( $app['abbr'] ?? '' ); ?></div>
				<span class="ap-app-label"><?php echo esc_html( $app['label'] ?? '' ); ?></span>
			</a>
		<?php endforeach; ?>
	</div>

	<div class="ap-section-title">
		<span>Mensagens Recentes</span>
		<a href="#" class="ap-see-all">Abrir Messenger &gt;</a>
	</div>
	<div class="ap-scroll-row" id="ap-chat-scroller">
		<?php foreach ( $messages as $message ) : ?>
			<a href="#" class="ap-msg-card">
				<div class="ap-msg-header">
					<div class="ap-msg-avatar <?php echo esc_attr( $message['color'] ?? 'ap-bg-gray' ); ?>"><?php echo esc_html( $message['avatar'] ?? '' ); ?></div>
					<div class="ap-msg-info">
						<span class="ap-msg-name"><?php echo esc_html( $message['name'] ?? '' ); ?></span>
						<span class="ap-msg-time"><?php echo esc_html( $message['time'] ?? '' ); ?></span>
					</div>
				</div>
				<div class="ap-msg-preview"><?php echo esc_html( $message['preview'] ?? '' ); ?></div>
			</a>
		<?php endforeach; ?>
		<div class="ap-msg-card ap-load-more-card" id="ap-load-more-msgs">
			<span class="ap-load-more-text">+ Carregar Mais</span>
		</div>
	</div>

	<div class="ap-section-title">
		<span>Notificações</span>
		<a href="#" class="ap-see-all">Ver todas &gt;</a>
	</div>
	<div class="ap-scroll-row">
		<a href="#" class="ap-msg-card">
			<div class="ap-msg-header">
				<div class="ap-msg-avatar ap-bg-red">A</div>
				<div class="ap-msg-info">
					<span class="ap-msg-name">Alerta Sistema</span>
					<span class="ap-msg-time">2m atrás</span>
				</div>
			</div>
			<div class="ap-msg-preview">Backup concluído com sucesso.</div>
		</a>
		<a href="#" class="ap-msg-card">
			<div class="ap-msg-header">
				<div class="ap-msg-avatar ap-bg-green">J</div>
				<div class="ap-msg-info">
					<span class="ap-msg-name">Júlia M.</span>
					<span class="ap-msg-time">15m atrás</span>
				</div>
			</div>
			<div class="ap-msg-preview">Marcou você em "Revisão UI".</div>
		</a>
	</div>
</div>

<div id="ap-menu-profile" class="ap-dropdown">
	<?php foreach ( $profile_links as $index => $link ) : ?>
		<?php if ( 2 === $index ) : ?>
			<div class="ap-profile-divider"></div>
			<div class="ap-darkmode-wrapper">
				<div class="ap-tdnn"><div class="ap-moon"></div></div>
			</div>
		<?php endif; ?>
		<a href="<?php echo esc_url( $link['href'] ?? '#' ); ?>" class="ap-profile-link <?php echo esc_attr( $link['class'] ?? '' ); ?>">
			<?php echo esc_html( $link['label'] ?? '' ); ?>
		</a>
	<?php endforeach; ?>
</div>
