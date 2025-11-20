"use strict";
jQuery( document ).ready( function() {
	if ( typeof elementorFrontend == 'undefined' ) {
		return;
	}
	// Add animation for the SVG paths after the timeout to allow the animation script breaks the text into words or chars
	setTimeout( function() {
		if ( ! elementorFrontend.isEditMode() ) {
			// Paint in Frontend only after the element comes into the view
			jQuery( '.elementor-widget-trx_elm_marquee.trx-addons-animate .trx-addons-marquee').each( function() {
				var $self = jQuery( this ),
					delay = $self.data( 'delay' ) || 0;
				$self.find( '.trx-addons-svg-wrapper path,.trx-addons-marquee-item.trx-addons-marquee-color' ).each( function( idx ) {
					var $path = jQuery( this );
					var handler = function() {
						if ( ! $path.hasClass( 'trx-addons-animate-complete' ) ) {
							setTimeout( function() {
								$path.addClass( 'trx-addons-animate-complete' );
								if ( $path.is( 'path' ) ) {
									$path.css( 'animation-play-state', 'running' );
								}
							}, 300 * idx + 400 + parseInt( delay ) );
						}
					};
					if ( 'undefined' !== typeof elementorFrontend.waypoint ) {
						elementorFrontend.waypoint( $path.get(0), handler, { offset: '90%', triggerOnce: true } );					
					} else {
						trx_addons_intersection_observer_add( $path, function( item, enter ) {
							if ( enter ) {
								trx_addons_intersection_observer_remove( item );
								handler();
							}
						} );
					}
				} );
			} );
		} else {
			// Repaint after the elementor is changed in the Editor
			elementorFrontend.hooks.addAction( 'frontend/element_ready/global', function( $cont ) {
				$cont.find( '.trx-addons-marquee' ).each( function() {
					var $self = jQuery( this ),
						delay = $self.data( 'delay' ) || 0;
					$self.find( '.trx-addons-svg-wrapper path,.trx-addons-marquee-item.trx-addons-marquee-color' ).each( function( idx ) {
						var $path = jQuery( this );
						if ( $path.hasClass( 'trx-addons-animate-complete' ) ) {
							$path.removeClass( 'trx-addons-animate-complete' );
						}
						setTimeout( function() {
							$path.addClass( 'trx-addons-animate-complete' );
							if ( $path.is( 'path' ) ) {
								$path.css( 'animation-play-state', 'running' );
							}
						}, 300 * idx + 400 + parseInt( delay ) );
					} );
				} );
			} );
		}
	}, 100 );

	// Remove wrappers .sc_item_animated_block and .sc_item_word around svg inside .trx-addons-svg-wrapper
	trx_addons_add_filter( 'trx_addons_filter_animation_wrap_items', function( html ) {
		if ( html.indexOf( 'class="trx-addons-svg-wrapper' ) >= 0 ) {
			var $obj = jQuery( html );
			$obj.find( '.trx-addons-svg-wrapper' ).each( function() {
				var $wrap = jQuery( this );
				if ( $wrap.find( '.sc_item_animated_block' ).length > 0 || $wrap.find( '.sc_item_word' ).length > 0 ) {
					var $svg = $wrap.find( 'svg' );
					if ( $svg.length ) {
						html = html.replace( $wrap.html(), $svg.get(0).outerHTML );
					}
				}
			} );
		}
		return html;
	} );

	// Start marquee animation
	jQuery( document ).on( 'action.init_hidden_elements', function( e, $cont ) {
		if ( $cont === undefined ) $cont = jQuery( 'body' );
		$cont.find( '.trx-addons-marquee-wrap:not(.trx-addons-marquee-inited):not(.trx_addons_in_preview_mode)' ).each( function() {
			var $self = jQuery( this ).addClass( 'trx-addons-marquee-inited' );
			var rtl = jQuery( 'body' ).hasClass( 'rtl' );
			var data = $self.data( 'marquee' ) || {
													'dir': rtl ? 1 : -1,
													'speed': 5,
													'hover': false,
													'accelerate': false,
													'accelerate_coef': 8
												};
			if ( window.trx_addons_elementor_marquee ) trx_addons_elementor_marquee( jQuery( this ), data['dir'], data['speed'], true, 'none', data['hover'], data['accelerate'], data['accelerate_coef'] );
		} );
	} );

	// Swap images inside the items with the type 'gallery'
	jQuery( document ).on( 'action.init_hidden_elements', function( e, $cont ) {
		if ( $cont === undefined ) container = jQuery( 'body' );
		$cont.find( '.trx-addons-marquee-item-gallery:not(.trx-addons-marquee-item-gallery-inited)' ).each( function() {
			var $self = jQuery( this ).addClass( 'trx-addons-marquee-item-gallery-inited' ),
				$images = $self.find( 'img' ),
				interval = $self.data( 'gallery-interval' ) || 4000,
				delay = $self.data( 'gallery-delay' ) || 0,
				timer = $self.data( 'gallery-timer' ) || 0;
			if ( timer ) {
				clearInterval( timer );
			}
			setTimeout( function() {
				setInterval( function() {
					var idx = $self.data( 'gallery-index' ) || 0;
					$images.eq( idx ).hide();
					idx = ( idx + 1 ) % $images.length;
					$self.data( 'gallery-index', idx );
					$images.eq( idx ).show();
				}, interval );
			}, delay );
		} );
	} );

} );