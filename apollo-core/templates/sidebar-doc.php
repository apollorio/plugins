<?php
/**
 * Template Part: Sidebar - Documents
 * File: template-parts/sidebar-doc.php
 */

$current_user = wp_get_current_user();
$user_initial = strtoupper( substr( $current_user->display_name, 0, 1 ) );
?>

<aside class="hidden md:flex md:flex-col w-64">
	<!-- Logo / Header -->
	<div class="h-16 flex items-center gap-3 pr-6 pl-10">
		<div class="h-9 w-9 rounded-xl bg-gradient-to-br from-orange-400 to-orange-600 flex items-center justify-center shadow-md shadow-orange-500/60">
			<i class="ri-slack-fill text-white text-[22px]"></i>
		</div>
		<div class="flex flex-col leading-tight">
			<span class="text-[9.5px] font-regular text-slate-400 uppercase tracking-[0.18em]">plataforma</span>
			<span class="text-[15px] font-extrabold text-slate-900">Apollo::rio</span>
		</div>
	</div>

	<!-- Navigation -->
	<nav class="aprio-sidebar-nav flex-1 px-4 pt-4 pb-2 overflow-y-auto no-scrollbar text-[13px]">
		<div class="px-1 mb-2 text-[9.5px] font-regular text-slate-400 uppercase tracking-wider">Navegação</div>

		<a href="<?php echo home_url( '/feed' ); ?>">
			<i class="ri-building-3-line"></i>
			<span>Feed</span>
		</a>

		<a href="<?php echo home_url( '/events' ); ?>">
			<i class="ri-calendar-event-line"></i>
			<span>Eventos</span>
		</a>

		<a href="<?php echo home_url( '/communities' ); ?>">
			<i class="ri-user-community-fill"></i>
			<span>Comunidades</span>
		</a>

		<a href="<?php echo home_url( '/nucleos' ); ?>">
			<i class="ri-team-fill"></i>
			<span>Núcleos</span>
		</a>

		<a href="<?php echo home_url( '/classifieds' ); ?>">
			<i class="ri-megaphone-line"></i>
			<span>Classificados</span>
		</a>

		<a href="<?php echo home_url( '/documents' ); ?>" aria-current="page">
			<i class="ri-file-text-line"></i>
			<span>Docs & Contratos</span>
		</a>

		<a href="<?php echo home_url( '/profile' ); ?>">
			<i class="ri-user-smile-fill"></i>
			<span>Perfil</span>
		</a>

		<?php if ( current_user_can( 'cena-rio' ) || current_user_can( 'administrator' ) ) : ?>
		<div class="mt-4 px-1 mb-0 text-[9.5px] font-regular text-slate-400 uppercase tracking-wider">Cena::rio</div>

		<a href="<?php echo home_url( '/cena-rio/agenda' ); ?>">
			<i class="ri-calendar-line"></i>
			<span>Agenda</span>
		</a>

		<a href="<?php echo home_url( '/cena-rio/suppliers' ); ?>">
			<i class="ri-bar-chart-grouped-line"></i>
			<span>Fornecedores</span>
		</a>

		<a href="<?php echo home_url( '/cena-rio/documents' ); ?>">
			<i class="ri-file-text-line"></i>
			<span>Documentos</span>
		</a>
		<?php endif; ?>

		<div class="mt-4 px-1 mb-0 text-[9.5px] font-regular text-slate-400 uppercase tracking-wider">Acesso Rápido</div>
		
		<a href="<?php echo home_url( '/settings' ); ?>">
			<i class="ri-settings-6-line"></i>
			<span>Ajustes</span>
		</a>
	</nav>

	<!-- User Footer -->
	<div class="p-0 rounded-lg group mb-4">
		<div class="relative flex w-full min-w-0 flex-col px-3">
			<!-- Footer Links -->
			<div class="w-full text-sm">
				<ul class="flex w-full min-w-0 flex-col gap-0">
					<li class="relative">
						<a href="<?php echo home_url( '/about' ); ?>" class="flex w-full items-center gap-0 px-3 text-left text-slate-600 hover:bg-slate-100 text-[12px]">
							<span>Sobre</span>
						</a>
					</li>
					<li class="relative">
						<a href="<?php echo home_url( '/settings' ); ?>" class="flex w-full items-center gap-0 overflow-hidden rounded-md px-3 text-left text-slate-600 hover:bg-slate-100 text-[12px]">
							<span>Ajustes</span>
						</a>
					</li>
					<li class="relative">
						<a href="<?php echo home_url( '/report' ); ?>" class="flex w-full items-center gap-0 px-3 text-left text-red-600 hover:bg-red-100 text-[12px]">
							<span>Denúncia</span>
						</a>
					</li>
				</ul>
			</div>

			<!-- User Block -->
			<div class="flex flex-col gap-2 p-2 mt-3 border-t border-slate-200">
				<div class="flex items-center gap-3 px-2 mt-2">
					<div class="h-8 w-8 rounded-full bg-orange-100 flex items-center justify-center text-xs font-bold text-orange-600">
						<?php echo $user_initial; ?>
					</div>
					<div class="flex flex-col leading-tight">
						<span class="text-[14px] font-bold text-slate-900"><?php echo esc_html( $current_user->display_name ); ?></span>
						<span class="text-[12px] text-slate-500"><?php echo esc_html( $current_user->user_email ); ?></span>
					</div>
					<a href="<?php echo wp_logout_url(); ?>" class="ml-auto text-slate-400 hover:text-slate-600" title="Logout">
						<i class="ri-logout-box-r-line text-[18px]"></i>
					</a>
				</div>
			</div>
		</div>
	</div>
</aside>
