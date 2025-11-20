/* global jQuery, TRX_ADDONS_STORAGE */

jQuery(document).on('action.ready_trx_addons', function() {
	"use strict";

	// How to handle fix/unfix rows:
	// true  - Intersection observers are used
	// false - a scroll event handler is used
	var USE_OBSERVERS = trx_addons_apply_filters( 'trx_addons_filter_use_observers_to_fix_rows', true )
							&& typeof IntersectionObserver != 'undefined';

	// Common objects
	var $document = jQuery( document ),
		$body     = jQuery( 'body' );

	// Return the row 'id' or data-id (if 'id' is empty)
	function get_id( row ) {
		return row.attr('id') ? row.attr('id') : row.attr('data-id');
	}

	// Handle fixed rows
	//---------------------------------------------------------
	if ( ! TRX_ADDONS_STORAGE['pagebuilder_preview_mode'] && ! $body.hasClass( 'sc_layouts_row_fixed_inited' ) ) {
		var rows = jQuery('.sc_layouts_row_fixed'),
			rows_always = rows.filter('.sc_layouts_row_fixed_always'),
			rows_delayed = rows.filter('.sc_layouts_row_delay_fixed'),
			last_scroll_offset = -1;
		// If page contain fixed rows
		if ( rows.length > 0 ) {
			rows.each( function( idx ) {
				// Add placeholders after each row
				var row = rows.eq( idx );
				if ( row.hasClass('sc_layouts_row_hide_unfixed' ) ) {
					if ( ! row.prev().hasClass('sc_layouts_row_unfixed_placeholder') ) {
						row.before( '<div class="sc_layouts_row_unfixed_placeholder">'
										+ ( USE_OBSERVERS ? '<div class="sc_layouts_row_fixed_marker_on"></div>' : '' )
									+ '</div>');
					}
				}
				if ( ! row.next().hasClass('sc_layouts_row_fixed_placeholder') ) {
					row.after('<div class="sc_layouts_row_fixed_placeholder" style="background-color:'+row.css('background-color')+';"><div class="sc_layouts_row_fixed_marker_off"></div></div>');
					if ( USE_OBSERVERS && ! row.hasClass('sc_layouts_row_hide_unfixed' ) ) {
						row.append('<div class="sc_layouts_row_fixed_marker_on"></div>');
					}
				}
				// Add class 'sc_layouts_row_fixed_with_fullscreen' to the row if it contains the element with class 'search_style_fullscreen'
				// if ( row.find( trx_addons_apply_filters( 'trx_addons_filter_fullscreen_elements', '.search_style_fullscreen,.search_modern' ) ).length > 0 ) {
				// 	row.addClass( 'sc_layouts_row_fixed_with_fullscreen' );
				// }
			} );
			// Add handlers to fix/unfix rows
			var timedFix = trx_addons_throttle( function() {
				trx_addons_cpt_layouts_fix_rows( {
							rows: rows,
							rows_always: rows_always
						} );
				last_scroll_offset = trx_addons_window_scroll_top();
			}, 150 );
			$document
				.on('action.scroll_trx_addons', function() {
					trx_addons_cpt_layouts_hide_fixed_rows();
					if ( ! USE_OBSERVERS ) {
						trx_addons_cpt_layouts_fix_rows( {
							rows: rows,
							rows_always: rows_always
						} );
						last_scroll_offset = trx_addons_window_scroll_top();
					} else {
						timedFix();
					}
				})
				.on('action.resize_trx_addons', function() {
					// Update global values
					trx_addons_cpt_layouts_check_mobile_breakpoint();
					trx_addons_cpt_layouts_hide_fixed_rows();
					trx_addons_cpt_layouts_fix_rows( {
						rows: rows,
						rows_always: rows_always,
						resize: true
					} );
				});
			// Add observer to fix/unfix rows
			if ( USE_OBSERVERS ) {
				var fixed_rows_observe = function() {
					rows.each( function( idx ) {
						var row = rows.eq( idx );
						var delay = trx_addons_cpt_layouts_fix_rows_delay( row );
						var marker_off = row.next().find( '>.sc_layouts_row_fixed_marker_off' );
						var marker_on  = row.hasClass( 'sc_layouts_row_hide_unfixed' )
											? row.prev().find( '>.sc_layouts_row_fixed_marker_on' )
											: row.find( '>.sc_layouts_row_fixed_marker_on' );
						var observer = row.data( 'trx-addons-fixed-observer' );
						if ( observer ) {
							if ( marker_off.length ) observer.unobserve( marker_off.get(0) );
							if ( marker_on.length ) observer.unobserve( marker_on.get(0) );
							observer.disconnect();
							observer = null;
						}
						// Skip hidden rows without a class 'sc_layouts_row_hide_unfixed'
						if ( row.css('display') == 'none' && ! row.hasClass('sc_layouts_row_hide_unfixed' ) ) {
							return;
						}
						// On mobile devices skip rows without a class 'sc_layouts_row_fixed_always'
						if ( trx_addons_window_width() < TRX_ADDONS_STORAGE['mobile_breakpoint_fixedrows_off']
							&& ! row.hasClass( 'sc_layouts_row_fixed_always' )
						) {
							return;
						}
						var row_height = row.hasClass( 'sc_layouts_row_hide_unfixed' ) ? 0 : row.outerHeight();
						// Create an observer for each row (to pay account an individual settings for delay)
						observer = new IntersectionObserver( function( entries ) {
								var time = new Date().getTime();
								entries.forEach( function( entry ) {
									var marker = jQuery( entry.target ),
										// marker_offset = marker.offset().top,
										parent = marker.parent(),
										row    = parent;
									if ( parent.hasClass( 'sc_layouts_row_unfixed_placeholder' ) ) {
										row = row.next();
									} else if ( parent.hasClass( 'sc_layouts_row_fixed_placeholder' ) ) {
										row = row.prev();
									}
									var animation_off_timeout = trx_addons_cpt_layouts_fix_rows_off_timeout( delay, row );
									var last_fixed_time = row.data('trx-addons-last-fixed-time') || 0;
									// marker_off is come in to the viewport
									if ( row.hasClass( 'sc_layouts_row_fixed_on' ) && entry.isIntersecting ) {
										if ( time - last_fixed_time > animation_off_timeout
											&& entry.boundingClientRect
											&& entry.rootBounds
											&& entry.boundingClientRect.top >= entry.rootBounds.top
										) {
											if ( ! row.hasClass( 'sc_layouts_row_fixed_animation_off' ) ) {
												trx_addons_cpt_layouts_fix_rows( {
													rows: rows,
													rows_always: rows_always,
													force_row: row,
													force_state: 'off'
												} );
											}
										}

									// marker_on is go out from the viewport
									} else if ( ! row.hasClass( 'sc_layouts_row_fixed_on' ) && ! entry.isIntersecting ) {
										if ( time - last_fixed_time > animation_off_timeout
											&& entry.boundingClientRect
											&& entry.rootBounds
											&& entry.boundingClientRect.bottom < entry.rootBounds.top
										) {
											trx_addons_cpt_layouts_fix_rows( {
												rows: rows,
												rows_always: rows_always,
												force_row: row,
												force_state: 'on'
											} );
										}
									}
								} );
							}, {
								root: null,
								rootMargin: ( delay - trx_addons_fixed_rows_height() + ( ! $body.hasClass('hide_fixed_rows') && row.hasClass( 'sc_layouts_row_fixed_on' ) ? row_height : 0 ) ) + 'px 0px 0px 0px',
								threshold: 0
							}
						);
						var marker = row.hasClass( 'sc_layouts_row_fixed_on' ) ? marker_off : marker_on;
						observer.observe( marker.get(0) );
						row.data( 'trx-addons-fixed-observer', observer );
					} );
				};
				$document.on('action.sc_layouts_row_fixed_on action.sc_layouts_row_fixed_off', fixed_rows_observe );
				fixed_rows_observe();

			}
			// Add a class to the body
			$body.addClass( 'sc_layouts_row_fixed_inited' );
		}
	}

	function trx_addons_cpt_layouts_fix_rows_delay( row ) {
		var delay = 0;
		if ( row.hasClass( 'sc_layouts_row_delay_fixed' ) ) {
			var coef = row.data( 'fixed-row-delay' );
			if ( coef && ('' + coef).indexOf('px') > 0 ) {
				delay = Math.max( 0, parseInt( coef, 10 ) );
			} else {
				if ( ! coef || isNaN( coef ) || coef <= 0 ) {
					coef = 3 / 4;
				}
				delay = Math.max( 0, trx_addons_window_height() * coef );
			}
		}
		return trx_addons_apply_filters( 'trx_addons_filter_fixed_rows_delay', delay );
	}

	function trx_addons_cpt_layouts_fix_rows_off_timeout( delay, $row ) {
		var timeout = delay > 0 ? getComputedStyle( $row.get(0) ).getPropertyValue('--trx-addons-fixed-rows-duration') || undefined : 0;
		if ( timeout == undefined ) {
			timeout = 300;	// Timeout must be equal to the css-animation time (see layouts.scss)
		}
		if ( ('' + timeout).indexOf('ms') > 0 ) {
			timeout = parseInt( timeout, 10 );
		} else if ( ('' + timeout).indexOf('s') > 0 ) {
			timeout = parseFloat( timeout ) * 1000;
		}
		return trx_addons_apply_filters( 'trx_addons_filter_sc_layouts_row_fixed_off_timeout',
					delay > 0 ? timeout	: 0,
					delay
				);
	}


	// Hide fixed rows on scroll down
	function trx_addons_cpt_layouts_hide_fixed_rows() {
		if ( TRX_ADDONS_STORAGE['hide_fixed_rows'] > 0 && ! window.trx_addons_document_animate_to_busy ) {
			var scroll_delta = 50;
			var scroll_offset = trx_addons_window_scroll_top();
			if ( last_scroll_offset >= 0 ) {
				var event = '';
				// Scroll down
				if ( scroll_offset > last_scroll_offset + scroll_delta ) {
					if ( scroll_offset > trx_addons_window_height() * ( rows_delayed.length > 0 ? 1.5 : 0.6667 ) && ! $body.hasClass( 'hide_fixed_rows' ) ) {
						$body.addClass( 'hide_fixed_rows' );
						event = 'off';
					}
				// Scroll up
				} else if ( scroll_offset < last_scroll_offset - scroll_delta || last_scroll_offset > 0 && scroll_offset == 0) {
					if ( $body.hasClass( 'hide_fixed_rows' ) ) {
						$body.removeClass( 'hide_fixed_rows' );
						event = 'on';
					}
				}
				// Trigger event
				if ( event ) {
					$document.trigger( 'action.sc_layouts_row_fixed_' + event, [ rows.filter('.sc_layouts_row_fixed_on') ] );
				}
			}
		}
	}

	// Break fixing on mobile devices (except rows with class 'sc_layouts_row_fixed_always')
	function trx_addons_cpt_layouts_check_mobile_breakpoint() {
		if ( trx_addons_window_width() < TRX_ADDONS_STORAGE['mobile_breakpoint_fixedrows_off'] ) {
			rows.each( function( idx ) {
				var row = rows.eq( idx );
				if ( ! row.hasClass( 'sc_layouts_row_fixed_always' ) ) {
					row.removeClass( 'sc_layouts_row_fixed_on' ).css( { 'top': 'auto' } );
				}
			});
		}
	}

	// Fix/unfix rows
	function trx_addons_cpt_layouts_fix_rows( args ) {

		var rows = args.rows,
			rows_always = args.rows_always,
			resize = args.resize || false,
			force_row = args.force_row || null,
			force_state = args.force_state || '';

		// Break fixing on mobile devices (except rows with class 'sc_layouts_row_fixed_always')
		if ( trx_addons_window_width() < TRX_ADDONS_STORAGE['mobile_breakpoint_fixedrows_off'] ) {
			if ( rows_always.length === 0 ) {
				return;
			} else {
				rows = rows_always;
			}
		}

		var scroll_offset = trx_addons_window_scroll_top(),
			scroll_intent = last_scroll_offset >= 0 ? last_scroll_offset - scroll_offset : 0;
		var rows_offset = trx_addons_adminbar_height();

		rows.each( function( idx ) {
			var row = rows.eq( idx );
			var placeholder = row.next();
			var h = row.outerHeight();
			if ( ( row.css('display') == 'none' || h === 0 ) && ! row.hasClass('sc_layouts_row_hide_unfixed' ) ) {
				placeholder.height(0);
				return;
			}
			var ph = row.hasClass( 'sc_layouts_row_fixed_on' ) ? placeholder.outerHeight() : 0;
			var row_unfixed_placeholder = row.hasClass('sc_layouts_row_hide_unfixed' ) ? row.prev() : false;
			var delay  = trx_addons_cpt_layouts_fix_rows_delay( row );
			var animation_off_timeout = trx_addons_cpt_layouts_fix_rows_off_timeout( delay, row );
			var time = new Date().getTime();
			var last_fixed_time = row.data('trx-addons-last-fixed-time') || 0;
			if ( time - last_fixed_time < animation_off_timeout ) {
				return;
			}
			var offset = parseInt( row.hasClass( 'sc_layouts_row_fixed_on' ) || row.hasClass( 'sc_layouts_row_fixed_on_pre' )
										? placeholder.offset().top
										: ( row.hasClass('sc_layouts_row_hide_unfixed' )
											? row_unfixed_placeholder.offset().top
											: row.offset().top
											),
									10 );
			if ( isNaN( offset ) ) {
				offset = 0;
			}

			// Unfix row
			if ( scroll_intent > 1
				&& ( ( force_state == 'off' && row.is( force_row ) )
					|| ( ! force_state
						&& ( scroll_offset + rows_offset <= offset + delay
							|| ( scroll_offset == 0 && last_scroll_offset > 0 )
							|| h - ph < -2
							)
						&& ( ! row.hasClass( 'sc_layouts_row_fixed_from_start' ) || scroll_offset > 0 )
						)
					)
			) {
				if ( row.hasClass( 'sc_layouts_row_fixed_on' ) ) {
					row.data( 'trx-addons-last-fixed-time', time );
					if ( animation_off_timeout > 0 ) {
						row.addClass( 'sc_layouts_row_fixed_animation_off' );
					}
					setTimeout( function() {
						var ph = placeholder.outerHeight();
						row
							.removeClass( 'sc_layouts_row_fixed_on'
											+ ( animation_off_timeout > 0 ? ' sc_layouts_row_fixed_animation_off' : '' ) )
							.css( { 'top': 'auto' } );
						var h = row.outerHeight();
						if ( ph != h && ! row.hasClass( 'sc_layouts_row_hide_unfixed' ) ) {
							ph = h;
							placeholder.height( h );
						}
						$document.trigger( 'action.sc_layouts_row_fixed_off', [ row ] );
					}, animation_off_timeout );
				}

			// Fix row
			} else {
				if ( ! row.hasClass( 'sc_layouts_row_fixed_on' ) ) {
					if ( scroll_intent <= 0		//< -1 || ( row.hasClass( 'sc_layouts_row_fixed_from_start' ) && scroll_intent <= 0 )
						&& ( ( force_state == 'on' && row.is( force_row ) )
							|| scroll_offset + rows_offset > offset + delay
							)
					) {
						var fixed_class = 'sc_layouts_row_fixed_on' + ( ! row.hasClass( 'sc_layouts_row_fixed_from_start' ) && scroll_offset == 0 && scroll_intent == 0 ? '_pre' : '' );
						if ( ! row.hasClass( fixed_class ) ) {
							if ( ! row.hasClass( 'sc_layouts_row_hide_unfixed' ) ) {
								ph = h;
								placeholder.height( ph );
							}
							row.addClass( 'sc_layouts_row_fixed_on' + ( ! row.hasClass( 'sc_layouts_row_fixed_from_start' ) && scroll_offset == 0 && scroll_intent == 0 ? '_pre' : '' ) )
								.css( { 'top': rows_offset + 'px' } );
							h = row.outerHeight();
							if ( ph != h && ! row.hasClass( 'sc_layouts_row_hide_unfixed' ) ) {
								ph = h;
								placeholder.height( h );
							}
							if ( fixed_class == 'sc_layouts_row_fixed_on' ) {
								row.data( 'trx-addons-last-fixed-time', time );
								$document.trigger( 'action.sc_layouts_row_fixed_on', [ row ] );
							}
						}
					} else if ( resize ) {
						h = row.outerHeight();
						if ( ph != h && ! row.hasClass( 'sc_layouts_row_hide_unfixed' ) ) {
							ph = h;
							placeholder.height( h );
						}
					}
				} else if ( resize && row.hasClass( 'sc_layouts_row_fixed_on' ) && row.offset().top != rows_offset ) {
					row.css( { 'top': rows_offset + 'px' } );
				}
				if ( row.css('display') != 'none' ) {
					rows_offset += h;
				}
			}
			if ( force_state && row.is( force_row ) ) {
				force_state = '';
				force_row = null;
			}
		});
	}

} );
