<?php
/**
 * Template Part: Mobile Bottom Navigation
 * File: template-parts/navigation-mobile.php
 */
?>

<div class="md:hidden fixed bottom-0 left-0 right-0 z-40 bg-white/95 backdrop-blur-xl border-t border-slate-200/50 pb-safe">
	<div class="max-w-2xl mx-auto w-full px-4 py-2 flex items-end justify-between h-[60px]">
		<a href="<?php echo home_url( '/events' ); ?>" class="nav-btn w-14 pb-1">
			<i class="ri-calendar-line"></i>
			<span>Agenda</span>
		</a>

		<a href="<?php echo home_url( '/explore' ); ?>" class="nav-btn w-14 pb-1">
			<i class="ri-compass-3-line"></i>
			<span>Explorar</span>
		</a>

		<div class="relative -top-5">
			<button class="h-14 w-14 rounded-full bg-slate-900 text-white flex items-center justify-center shadow-[0_8px_20px_-6px_rgba(15,23,42,0.6)] opacity-50 cursor-default">
				<i class="ri-add-line text-3xl"></i>
			</button>
		</div>

		<a href="<?php echo home_url( '/documents' ); ?>" class="nav-btn active w-14 pb-1">
			<i class="ri-file-text-line"></i>
			<span>Docs</span>
		</a>

		<a href="<?php echo home_url( '/profile' ); ?>" class="nav-btn w-14 pb-1">
			<i class="ri-user-3-line"></i>
			<span>Perfil</span>
		</a>
	</div>
</div>
