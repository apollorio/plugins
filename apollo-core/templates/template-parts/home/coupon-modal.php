<?php
/**
 * Apollo Coupon Modal
 *
 * Popup modal for Apollo coupon codes on events
 *
 * @package Apollo_Core
 * @since 3.0.0
 */

defined( 'ABSPATH' ) || exit;

$args = $args ?? array();

$modal_title       = $args['title'] ?? __( 'Cupom Apollo disponível!', 'apollo-core' );
$modal_description = $args['description'] ?? __( 'Use o código abaixo para obter desconto na compra do seu ingresso.', 'apollo-core' );
?>


<div class="coupon-modal-overlay" id="couponModal">
	<div class="coupon-modal">
		<button class="coupon-modal-close" id="closeCouponModal" aria-label="<?php esc_attr_e( 'Fechar', 'apollo-core' ); ?>">
			<i class="ri-close-line"></i>
		</button>

		<div class="coupon-modal-icon">
			<i class="ri-coupon-3-line"></i>
		</div>

		<h3 class="coupon-modal-title"><?php echo esc_html( $modal_title ); ?></h3>
		<p class="coupon-modal-description"><?php echo esc_html( $modal_description ); ?></p>

		<div class="coupon-event-info" id="couponEventInfo" style="display:none;">
			<img src="" alt="" class="coupon-event-thumb" id="couponEventThumb">
			<div class="coupon-event-details">
				<div class="coupon-event-name" id="couponEventName"></div>
				<div class="coupon-event-date" id="couponEventDate"></div>
			</div>
		</div>

		<div class="coupon-code-box">
			<span class="coupon-code" id="couponCode">APOLLO10</span>
			<button class="coupon-copy-btn" id="copyCouponBtn" aria-label="<?php esc_attr_e( 'Copiar código', 'apollo-core' ); ?>">
				<i class="ri-file-copy-line"></i>
			</button>
		</div>

		<div class="coupon-modal-footer">
			<a href="#" class="coupon-btn-primary" id="couponBuyLink" target="_blank" rel="noopener">
				<i class="ri-ticket-line"></i>
				<?php esc_html_e( 'Comprar ingresso', 'apollo-core' ); ?>
			</a>
			<a href="#" class="coupon-btn-secondary" id="closeCouponModalSecondary">
				<?php esc_html_e( 'Continuar navegando', 'apollo-core' ); ?>
			</a>
		</div>
	</div>
</div>

<script>
(function(){
	var modal=document.getElementById('couponModal');
	var closeBtn=document.getElementById('closeCouponModal');
	var closeSecondary=document.getElementById('closeCouponModalSecondary');
	var copyBtn=document.getElementById('copyCouponBtn');
	var codeEl=document.getElementById('couponCode');

	function openCouponModal(data){
		if(data){
			if(data.code) codeEl.textContent=data.code;
			if(data.eventName){
				document.getElementById('couponEventInfo').style.display='flex';
				document.getElementById('couponEventName').textContent=data.eventName;
				document.getElementById('couponEventDate').textContent=data.eventDate||'';
				if(data.eventThumb) document.getElementById('couponEventThumb').src=data.eventThumb;
			}
			if(data.buyUrl) document.getElementById('couponBuyLink').href=data.buyUrl;
		}
		modal.classList.add('active');
		document.body.style.overflow='hidden';
	}

	function closeCouponModal(){
		modal.classList.remove('active');
		document.body.style.overflow='';
	}

	closeBtn.onclick=closeCouponModal;
	closeSecondary.onclick=function(e){e.preventDefault();closeCouponModal();};
	modal.onclick=function(e){if(e.target===modal)closeCouponModal();};

	copyBtn.onclick=function(){
		navigator.clipboard.writeText(codeEl.textContent).then(function(){
			copyBtn.classList.add('copied');
			copyBtn.innerHTML='<i class="ri-check-line"></i>';
			setTimeout(function(){
				copyBtn.classList.remove('copied');
				copyBtn.innerHTML='<i class="ri-file-copy-line"></i>';
			},2000);
		});
	};

	// Expose global function for event cards
	window.apolloOpenCoupon=openCouponModal;

	// Listen for event card clicks with coupon data
	document.addEventListener('click',function(e){
		var card=e.target.closest('[data-apollo-coupon]');
		if(card){
			e.preventDefault();
			var couponData=JSON.parse(card.dataset.apolloCoupon||'{}');
			openCouponModal(couponData);
		}
	});
})();
</script>
