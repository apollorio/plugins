<?php
// Tickets Block
?>
<section class="section" id="route_TICKETS">
	<h2 class="section-title">
	<i class="ri-ticket-2-line"></i> Acessos
	</h2>
	<div class="tickets-grid">
	<?php if ( $ticket_url ) : ?>
	<a href="<?php echo esc_url( $ticket_url ); ?>?ref=apollo.rio.br" class="ticket-card" target="_blank">
		<div class="ticket-icon"><i class="ri-ticket-line"></i></div>
		<div class="ticket-info">
		<h3 class="ticket-name"><span id="changingword">Biglietti</span></h3>
		<span class="ticket-cta">Acessar Bilheteria Digital →</span>
		</div>
	</a>
	<?php endif; ?>
	<div class="apollo-coupon-detail">
		<i class="ri-coupon-3-line"></i>
		<span>Verifique se o cupom <strong>APOLLO</strong> está ativo com desconto</span>
		<button class="copy-code-mini" onclick="copyPromoCode()">
		<i class="ri-file-copy-fill"></i>
		</button>
	</div>
	<div class="ticket-card disabled">
		<div class="ticket-icon"><i class="ri-list-check"></i></div>
		<div class="ticket-info">
		<h3 class="ticket-name">Lista Amiga</h3>
		<span class="ticket-cta">Ver Lista Amiga →</span>
		</div>
	</div>
	</div>
</section>
