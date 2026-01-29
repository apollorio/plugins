<?php
/**
 * Bottom Bar Partial - Apollo Design System
 *
 * Displays a fixed bottom bar with exactly 2 buttons:
 * - Primary action button (Tickets/Acessos/etc.)
 * - Share button
 *
 * @param array $args {
 *     @type string $primary_text     Primary button text (default: 'Tickets')
 *     @type string $primary_url      Primary button URL (default: '#')
 *     @type string $primary_icon     Primary button icon class (default: 'ri-ticket-fill')
 *     @type string $share_text       Share button text (default: '')
 *     @type string $share_icon       Share button icon class (default: 'ri-share-forward-line')
 *     @type bool   $animate_primary  Whether to animate primary button text (default: false)
 * }
 */

// Set defaults.
$args = wp_parse_args(
	$args ?? array(),
	array(
		'primary_text'    => 'Tickets',
		'primary_url'     => '#',
		'primary_icon'    => 'ri-ticket-fill',
		'share_text'      => '',
		'share_icon'      => 'ri-share-forward-line',
		'animate_primary' => false,
	)
);

// Escape all outputs.
$primary_text = esc_html( $args['primary_text'] );
$primary_url  = esc_url( $args['primary_url'] );
$primary_icon = esc_attr( $args['primary_icon'] );
$share_text   = esc_html( $args['share_text'] );
$share_icon   = esc_attr( $args['share_icon'] );
?>

<!-- Bottom Bar - APOLLO STYLE -->
<div class="bottom-bar">
	<a href="<?php echo $primary_url; ?>" class="bottom-btn primary" id="bottomTicketBtn">
		<i class="<?php echo $primary_icon; ?>"></i>
		<span id="changingword" <?php echo $args['animate_primary'] ? '' : 'data-static="true"'; ?>>
			<?php echo $primary_text; ?>
		</span>
	</a>

	<button class="bottom-btn secondary" id="bottomShareBtn" type="button">
		<i class="<?php echo $share_icon; ?>"></i>
		<?php if ( $share_text ) : ?>
			<span><?php echo $share_text; ?></span>
		<?php endif; ?>
	</button>
</div>

<?php if ( $args['animate_primary'] ) : ?>
<script>
(function(){
	var words = [
		'Entradas',
		'Ingressos',
		'Billets',
		'Ticket',
		'Acessos',
		'Biglietti'
	], i = 0;
	var elem = document.getElementById('changingword');
	if (!elem || elem.dataset.static === 'true') return;

	// set initial word.
	elem.textContent = words[i];

	function fadeOut(el, duration, callback) {
		el.style.opacity = 1;
		var start = null;
		function step(timestamp) {
			if (!start) start = timestamp;
			var progress = timestamp - start;
			var fraction = progress / duration;
			if (fraction < 1) {
				el.style.opacity = 1 - fraction;
				window.requestAnimationFrame(step);
			} else {
				el.style.opacity = 0;
				if (callback) callback();
			}
		}
		window.requestAnimationFrame(step);
	}

	function fadeIn(el, duration, callback) {
		el.style.opacity = 0;
		el.style.display = '';
		var start = null;
		function step(timestamp) {
			if (!start) start = timestamp;
			var progress = timestamp - start;
			var fraction = progress / duration;
			if (fraction < 1) {
				el.style.opacity = fraction;
				window.requestAnimationFrame(step);
			} else {
				el.style.opacity = 1;
				if (callback) callback();
			}
		}
		window.requestAnimationFrame(step);
	}

	setInterval(function(){
		fadeOut(elem, 400, function(){
			i = (i+1) % words.length;
			elem.textContent = words[i];
			fadeIn(elem, 400);
		});
	}, 4000);
})();
</script>
<?php endif; ?>

<style>
/* Bottom Bar - APOLLO STYLE */
.bottom-bar {
	position: fixed;
	bottom: 0;
	left: 50%;
	transform: translateX(-50%);
	width: 100%;
	max-width: 500px;
	background: rgba(255, 255, 255, 0.95);
	backdrop-filter: blur(20px);
	padding: 1rem 1.5rem;
	box-shadow: 0 -4px 20px rgba(0,0,0,0.1);
	z-index: 100;
	display: flex;
	gap: 0.75rem;
	border-radius: 22px 22px 0 0;
}

.bottom-btn {
	flex: 1;
	padding: 1rem;
	border-radius: 12px;
	border: 1px solid #e0e2e4;
	font-weight: 700;
	font-size: 0.95rem;
	cursor: pointer;
	display: flex;
	align-items: center;
	justify-content: center;
	gap: 0.5rem;
	transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
	text-decoration: none;
	background: #fff;
	color: rgba(19, 21, 23, 0.85);
}

.bottom-btn:active {
	transform: scale(0.97);
}

.bottom-btn:hover {
	background: #f5f5f5;
	border-color: rgba(19, 21, 23, 0.7);
}

.bottom-btn.primary {
	background: rgba(19, 21, 23, 0.85);
	color: white;
	border-color: rgba(19, 21, 23, 0.85);
}

.bottom-btn.primary:hover {
	background: rgba(19, 21, 23, 0.7);
	border-color: rgba(19, 21, 23, 0.7);
}
</style>
