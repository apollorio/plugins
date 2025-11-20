/**
 * Widget Audio
 *
 * @package ThemeREX Addons
 * @since v1.0
 */

/* global jQuery */

(function() {
	"use strict";

	jQuery(document).on( 'action.init_hidden_elements', trx_addons_init_audio );

	jQuery( window ).on( 'load', function() {
		trx_addons_init_audio();
	} );

	jQuery( window ).on( 'resize', function(){
		trx_addons_audio_height();
	} );

	jQuery( window ).on( 'elementor/frontend/init', function() {
		if ( typeof window.elementorFrontend !== 'undefined' && typeof window.elementorFrontend.hooks !== 'undefined' ) {
			if ( elementorFrontend.isEditMode() ) {
				// Resize players after creation
				elementorFrontend.hooks.addAction( 'frontend/element_ready/global', function( $cont ) {
					if ( $cont.data( 'widget_type' ) && $cont.data( 'widget_type' ).indexOf( 'trx_widget_audio' ) >= 0 ) {
						trx_addons_audio_height( $cont );
					}
				} );
			}
		}
	} );

	// Init audio
	function trx_addons_init_audio() {		
		jQuery( '.trx_addons_audio_wrap:not(.inited)' ).addClass( 'inited' ).each( function() {

			var audio_wrap = jQuery( this );
			if ( ! audio_wrap.hasClass( 'list' ) ) {
				return;
			}

			var total = audio_wrap.find( '.trx_addons_audio_player' ).length,
				current = null;

			var setCurrentItem = function( idx ) {
				var item, volume;
				if ( current ) {
					volume = current.find( '.mejs-horizontal-volume-slider' ).attr( 'aria-valuenow' );
					current.find( 'audio' ).get(0).pause();
					audio_wrap.find( '.trx_addons_audio_player' ).removeClass( 'current' );
					if ( audio_wrap.hasClass( 'with_playlist' ) ) {
						audio_wrap.find( '.trx_addons_audio_playlist_item' ).removeClass( 'current' );
					}
					if ( audio_wrap.hasClass( 'cover_left' ) || audio_wrap.hasClass( 'cover_right' ) ) {
						audio_wrap.find( '.trx_addons_audio_cover_item' ).removeClass( 'current' );
					}
				}

				current = audio_wrap.find( '.trx_addons_audio_player' ).eq( idx < 0 ? total - 1 : idx % total );
				current.addClass( 'current' );
				if ( current.hasClass( 'with_cover' ) ) {
					audio_wrap.addClass( 'with_cover' );
				} else {
					audio_wrap.removeClass( 'with_cover' );
				}
				if ( audio_wrap.hasClass( 'with_playlist' ) ) {
					audio_wrap.find( '.trx_addons_audio_playlist_item' ).eq( idx < 0 ? total - 1 : idx % total ).addClass( 'current' );
				}
				if ( audio_wrap.hasClass( 'cover_left' ) || audio_wrap.hasClass( 'cover_right' ) ) {
					audio_wrap.find( '.trx_addons_audio_cover_item' ).eq( idx < 0 ? total - 1 : idx % total ).addClass( 'current' );
				}

				item = current.find( 'audio' ).get(0);
				if ( item ) {
					// If player has status "Play" than start to play current media item
					if ( audio_wrap.hasClass( 'play' ) ) {
						item.play();
					}
					// Change player status to "Mute/Unmute"
					if ( audio_wrap.hasClass( 'mute' ) ) {
						item.setMuted( true );
					} else if ( audio_wrap.hasClass( 'unmute' ) ) {
						item.setMuted( false );
					}
					// Change volume
					if ( volume > 0 ) {
						var current_volume = current.find( '.mejs-horizontal-volume-slider' ).attr( 'aria-valuenow' );
						if ( current_volume != volume ) {
							item.setVolume( volume / 100 );
						}
					}
				}
			};

			setCurrentItem( 0 );

			// Preload all item
			audio_wrap.find( 'audio' ).each(
				function(){
					var id   = jQuery( this ).attr( 'id' );
					var item = document.getElementById( id );
					if (item) {
						item.load();
						item.pause();
					}
				}
			);

			// Change player status to "Play/Pause"
			audio_wrap.on( 'click', '.mejs-playpause-button', function(){
				if (jQuery( this ).hasClass( 'mejs-play' )) {
					audio_wrap.addClass( 'play' ).removeClass( 'pause' );
				} else {
					audio_wrap.addClass( 'pause' ).removeClass( 'play' );
				}
			} );

			// Change player status to "Mute/Unmute"
			audio_wrap.on( 'click', '.mejs-volume-button', function(){
				if (jQuery( this ).hasClass( 'mejs-mute' )) {
					audio_wrap.addClass( 'unmute' ).removeClass( 'mute' );
				} else {
					audio_wrap.addClass( 'mute' ).removeClass( 'unmute' );
				}
			} );

			// Change player status to "Unmute"
			audio_wrap.on( 'click', '.mejs-horizontal-volume-slider', function() {
				if (audio_wrap.hasClass( 'mute' )) {
					audio_wrap.addClass( 'unmute' ).removeClass( 'mute' );
				}
			} );

			// Change audio track/radio station on click the nav button
			audio_wrap.find( '.trx_addons_audio_navigation' ).on( 'click', '.nav_btn', function(e) {
				setCurrentItem( current.index() + ( jQuery( this ).hasClass( 'next' ) ? 1 : -1 ) );
				e.preventDefault();
			} );

			// Change audio track/radio station on click the playlist item
			audio_wrap.find( '.trx_addons_audio_playlist_item' ).on( 'click', function(e) {
				var item = jQuery( this );
				if ( ! item.hasClass( 'current' ) && ! item.hasClass( 'disabled' ) ) {
					setCurrentItem( item.index() );
				}
				e.preventDefault();
			} );
		} );

		trx_addons_audio_height();
	}

	function trx_addons_audio_height( $cont) {

		// Do not resize audio players in Elementor editor
		// if ( window.elementor !== undefined ) {
		// 	return;
		// }

		if ( $cont === undefined ) {
			$cont = jQuery( 'body' );
		}

		$cont.find( '.trx_addons_audio_wrap' ).each( function() {
			var $self = jQuery( this );
			$self.removeClass( 'resized' );
			if ( $self.hasClass( 'list' ) ) {
				var height = 0;
				$self.find( '.trx_addons_audio_player' ).each( function() {
					var item_h = jQuery( this ).outerHeight();
					if ( item_h > height ) {
						height = item_h;
					}
				} );
				$self.find( '.trx_addons_audio_list' ).height( height );
			}
			$self.addClass( 'resized' );
		} );
	}

})();
