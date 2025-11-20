jQuery(document).ready(function() {
	'use strict';

	trx_addons_add_filter( 'trx_addons_filter_mediamanager_selector', function( selector ) {
		return selector + ',.ai_helper_blog_generator_form_field_media:not(.inited)';
	} );

	window.trx_addons_ai_helper_blog_generator = function( action, data, $button ) {
		trx_addons_msgbox_dialog(
			'<div class="ai_helper_blog_generator_form_row">'
				+ '<div class="ai_helper_blog_generator_form_label">'
					+ TRX_ADDONS_STORAGE['elm_ai_blog_generator_label_posts']
				+ '</div>'
			+ '</div>'
			+ '<div class="ai_helper_blog_generator_form_row">'
				+ '<div class="ai_helper_blog_generator_form_field">'
					+ '<label for="ai_helper_blog_generator_posts_total">' + TRX_ADDONS_STORAGE['elm_ai_blog_generator_total'] + '</label>'
					+ '<input type="number" min="1" max="100" value="9" step="1" id="ai_helper_blog_generator_posts_total" >'
				+ '</div>'
				+ '<div class="ai_helper_blog_generator_form_field">'
					+ '<label for="trx_addons_ai_helper_blog_generator_title_case_title">' + TRX_ADDONS_STORAGE['elm_ai_blog_generator_title_case'] + '</label>'
					+ '<select id="ai_helper_blog_generator_title_case_title">'
						+ '<option value="title">' + TRX_ADDONS_STORAGE['elm_ai_blog_generator_title_case_title'] + '</option>'
						+ '<option value="sentence" selected>' + TRX_ADDONS_STORAGE['elm_ai_blog_generator_title_case_sentence'] + '</option>'
					+ '</select>'
				+ '</div>'
			+ '</div>'
			+ '<div class="ai_helper_blog_generator_form_row">'
				+ '<div class="ai_helper_blog_generator_form_label">'
					+ TRX_ADDONS_STORAGE['elm_ai_blog_generator_label_cats']
				+ '</div>'
			+ '</div>'
			+ '<div class="ai_helper_blog_generator_form_row">'
				+ '<div class="ai_helper_blog_generator_form_field">'
					+ '<label for="ai_helper_blog_generator_cats_total">' + TRX_ADDONS_STORAGE['elm_ai_blog_generator_total'] + '</label>'
					+ '<input type="number" min="0" max="20" value="6" step="1" id="ai_helper_blog_generator_cats_total" >'
				+ '</div>'
				+ '<div class="ai_helper_blog_generator_form_field">'
					+ '<label for="ai_helper_blog_generator_cats_per_post">' + TRX_ADDONS_STORAGE['elm_ai_blog_generator_per_post'] + '</label>'
					+ '<input type="number" min="0" max="10" value="1" step="1" id="ai_helper_blog_generator_cats_per_post" >'
				+ '</div>'
			+ '</div>'
			+ '<div class="ai_helper_blog_generator_form_row">'
				+ '<div class="ai_helper_blog_generator_form_label">'
					+ TRX_ADDONS_STORAGE['elm_ai_blog_generator_label_tags']
				+ '</div>'
			+ '</div>'
			+ '<div class="ai_helper_blog_generator_form_row">'
				+ '<div class="ai_helper_blog_generator_form_field">'
					+ '<label for="ai_helper_blog_generator_tags_total">' + TRX_ADDONS_STORAGE['elm_ai_blog_generator_total'] + '</label>'
					+ '<input type="number" min="0" max="20" value="9" step="1" id="ai_helper_blog_generator_tags_total" >'
				+ '</div>'
				+ '<div class="ai_helper_blog_generator_form_field">'
					+ '<label for="ai_helper_blog_generator_tags_per_post">' + TRX_ADDONS_STORAGE['elm_ai_blog_generator_per_post'] + '</label>'
					+ '<input type="number" min="0" max="10" value="3" step="1" id="ai_helper_blog_generator_tags_per_post" >'
				+ '</div>'
			+ '</div>'
			+ '<div class="ai_helper_blog_generator_form_row">'
				+ '<div class="ai_helper_blog_generator_form_label">'
					+ TRX_ADDONS_STORAGE['elm_ai_blog_generator_label_comments']
				+ '</div>'
			+ '</div>'
			+ '<div class="ai_helper_blog_generator_form_row">'
				+ '<div class="ai_helper_blog_generator_form_field">'
					+ '<label for="ai_helper_blog_generator_comments_every_post">' + TRX_ADDONS_STORAGE['elm_ai_blog_generator_every_post'] + '</label>'
					+ '<input type="number" min="1" max="10" value="2" step="1" id="ai_helper_blog_generator_comments_every_post" >'
				+ '</div>'
				+ '<div class="ai_helper_blog_generator_form_field">'
					+ '<label for="ai_helper_blog_generator_comments_per_post">' + TRX_ADDONS_STORAGE['elm_ai_blog_generator_per_post'] + '</label>'
					+ '<input type="number" min="0" max="10" value="2" step="1" id="ai_helper_blog_generator_comments_per_post" >'
				+ '</div>'
			+ '</div>'
			+ '<div class="ai_helper_blog_generator_form_row">'
				+ '<div class="ai_helper_blog_generator_form_label">'
					+ TRX_ADDONS_STORAGE['elm_ai_blog_generator_label_images']
				+ '</div>'
			+ '</div>'
			+ '<div class="ai_helper_blog_generator_form_row">'
				+ '<div class="ai_helper_blog_generator_form_field">'
					+ '<label for="ai_helper_blog_generator_set_featured_images">' + TRX_ADDONS_STORAGE['elm_ai_blog_generator_set_featured_images'] + '</label>'
					+ '<input type="checkbox" value="1" id="ai_helper_blog_generator_set_featured_images" >'
				+ '</div>'
				+ '<div class="ai_helper_blog_generator_form_field">'
					+ '<label for="ai_helper_blog_generator_set_content_images">' + TRX_ADDONS_STORAGE['elm_ai_blog_generator_set_content_images'] + '</label>'
					+ '<input type="checkbox" value="1" id="ai_helper_blog_generator_set_content_images" >'
				+ '</div>'
			+ '</div>'
			+ '<div class="ai_helper_blog_generator_form_row ai_helper_blog_generator_form_row_media trx_addons_hidden">'
				+ '<div class="ai_helper_blog_generator_form_field">'
				+ '</div>'
				+ '<div class="ai_helper_blog_generator_form_field ai_helper_blog_generator_form_field_media">'
					+ '<input type="hidden" id="ai_helper_blog_generator_content_image">'
					+ '<span class="trx_addons_media_selector_preview trx_addons_media_selector_preview_single trx_addons_media_selector_preview_with_image"></span>'
					+ '<input type="button" id="ai_helper_blog_generator_content_image_button" class="button mediamanager trx_addons_media_selector"'
						+ ' data-linked-field="ai_helper_blog_generator_content_image"'
						// + ' value="' + TRX_ADDONS_STORAGE['elm_ai_blog_generator_choose_image'] + '"'
						+ ' data-choose="' + TRX_ADDONS_STORAGE['elm_ai_blog_generator_choose_image'] + '"'
						+ ' data-update="' + TRX_ADDONS_STORAGE['elm_ai_blog_generator_choose_image'] + '"'
						+ ' data-multiple="0"'
						+ ' data-type="image"'
						+ '>'
				+ '</div>'
			+ '</div>',
			TRX_ADDONS_STORAGE[ 'elm_ai_blog_generator_dialog_caption' ],
			function( box ) {
				jQuery( document ).trigger( 'action.init_hidden_elements', [ box ] );
				box.find( '#ai_helper_blog_generator_set_content_images' ).on( 'change', function( e ) {
					box.find( '.ai_helper_blog_generator_form_row_media' ).toggleClass( 'trx_addons_hidden', ! jQuery( this ).is( ':checked' ) );
				} );
			},
			function( btn, box ) {
				if ( btn !== 1 ) {
					return;
				}

				// Add form data to the request
				data.posts_total = box.find( '#ai_helper_blog_generator_posts_total' ).val();
				if ( data.posts_total < 1 ) {
					return;
				}
				data.title_case = box.find( '#ai_helper_blog_generator_title_case_title' ).val();
				data.cats_total = box.find( '#ai_helper_blog_generator_cats_total' ).val();
				data.cats_per_post = box.find( '#ai_helper_blog_generator_cats_per_post' ).val();
				data.tags_total = box.find( '#ai_helper_blog_generator_tags_total' ).val();
				data.tags_per_post = box.find( '#ai_helper_blog_generator_tags_per_post' ).val();
				data.comments_per_post = box.find( '#ai_helper_blog_generator_comments_per_post' ).val();
				data.comments_every_post = box.find( '#ai_helper_blog_generator_comments_every_post' ).val();
				data.set_featured_images = box.find( '#ai_helper_blog_generator_set_featured_images' ).is( ':checked' ) ? 1 : 0;
				data.set_content_images = box.find( '#ai_helper_blog_generator_set_content_images' ).is( ':checked' ) ? 1 : 0;
				data.content_image = box.find( '#ai_helper_blog_generator_content_image' ).val();

				// Send data to the server
				$button.addClass('trx_addons_loading');
				jQuery.post( TRX_ADDONS_STORAGE['ajax_url'], data ).done( function( response ) {
					$button.removeClass('trx_addons_loading');
					var rez = trx_addons_parse_ajax_response( response );
					if ( rez.error !== '' ) {
						alert( rez.error );
					} else if ( typeof rez.data == 'undefined' || typeof rez.data.posts == 'undefined' ) {
						alert( TRX_ADDONS_STORAGE['elm_ai_company_generator_bad_data'] );
					} else {
						if ( rez.data.posts.length == 0 ) {
							alert( TRX_ADDONS_STORAGE['elm_ai_blog_generator_no_posts'] );
						} else {
							alert( TRX_ADDONS_STORAGE['elm_ai_blog_generator_posts_inserted'].replace( '%d', rez.data.posts.length ) );
						}
					}
				} );
			}
		);
	};

} );