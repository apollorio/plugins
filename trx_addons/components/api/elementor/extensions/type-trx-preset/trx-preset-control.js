/*
global: elementor, jQuery;
*/

( function() {

	var justInserted = false;

	// Init control
	window.addEventListener( 'elementor/init', function() {

		var TrxAddonsPresetItemView = elementor.modules.controls.Select.extend( {

			onReady: function () {
				this.updateCustomSelect( this.$el );
			},

			onRender: function () {
				this.constructor.__super__.onRender.apply(this, arguments);
			},

			ui: function () {
				let selector = this.constructor.__super__.ui.call(this);

				selector.save_preset_button   = '.trx-addons-button-save-preset';
				selector.save_preset_ok       = '.trx-addons-save-preset-button-ok';
				selector.save_preset_cancel   = '.trx-addons-save-preset-button-cancel';

				selector.update_preset_button = '.trx-addons-button-update-preset';
				selector.update_preset_ok     = '.trx-addons-update-preset-button-ok';
				selector.update_preset_cancel = '.trx-addons-update-preset-button-cancel';

				selector.delete_preset_button = '.trx-addons-button-delete-preset';
				selector.delete_preset_ok     = '.trx-addons-delete-preset-button-ok';
				selector.delete_preset_cancel = '.trx-addons-delete-preset-button-cancel';

				selector.reset_style_button   = '.trx-addons-button-reset-style';

				return selector;
			},

			events: function() {
				let allEvents = this.constructor.__super__.events.call(this);

				allEvents["click @ui.save_preset_button"]   = "onSavePresetDialog";
				allEvents["click @ui.save_preset_ok"]       = "onSavePresetOk";
				allEvents["click @ui.save_preset_cancel"]   = "onSavePresetCancel";

				allEvents["click @ui.update_preset_button"] = "onUpdatePresetDialog";
				allEvents["click @ui.update_preset_ok"]     = "onUpdatePresetOk";
				allEvents["click @ui.update_preset_cancel"] = "onSavePresetCancel";

				allEvents["click @ui.delete_preset_button"] = "onDeletePresetDialog";
				allEvents["click @ui.delete_preset_ok"]     = "onDeletePresetOk";
				allEvents["click @ui.delete_preset_cancel"] = "onDeletePresetCancel";

				allEvents["click @ui.reset_style_button"]   = "onResetStyle";

				return allEvents;
			},

			// Fill <select> with presets
			updateCustomSelect: function( $wrap ) {
				let $select = $wrap.find('select[data-setting]');
				let param = $select.data('setting');
				let currentValue = this.getElementSettingsModel().attributes[ param ];
				let widget_type = this.getElementSettingsModel().attributes.elType == 'widget' ? this.getElementSettingsModel().attributes.widgetType : this.getElementSettingsModel().attributes.elType;
				let opts = [
					{
						value: '',
						label: trx_addons_elementor_control_preset_vars.select_preset_title,
						default: 0
					}
					// ,
					// {
					// 	value: 'custom',
					// 	label: trx_addons_elementor_control_preset_vars.custom_preset_title
					// }
				];

				for ( let preset_id in trx_addons_elementor_control_preset_vars.presets ) {
					if ( trx_addons_elementor_control_preset_vars.presets[ preset_id ].widget_type != widget_type ) {
						continue;
					}
					opts.push( {
						value: preset_id,
						label: trx_addons_elementor_control_preset_vars.presets[preset_id].title,
						default: trx_addons_elementor_control_preset_vars.presets[preset_id].default > 0 ? 1 : 0
					} );
				}
				// Sort options by label
				opts.sort( function(a, b) {
					if ( ! a.value ) {
						return -1;
					}
					if ( ! b.value ) {
						return 1;
					}
					return a.label.localeCompare( b.label, 'standard', { numeric:true } );
				} );
				// Clear current select
				$select.html('');
				// Add options to the select
				for ( let i = 0; i < opts.length; i++ ) {
					let opt = opts[i];
					$select.append( '<option value="' + opt.value + '"' + ( opt.value == currentValue ? ' selected' : '' ) + ( opt.default ? ' class="default"' : '' )  + '>' + opt.label + '</option>' );
				}
				// Set current value
				// $select.val( currentValue );
				// Set current value in elementor object
				// this.setSettingsModel( currentValue );
			},

			// A new preset selected - apply it settings to the current element
			onBaseInputChange: function( e ) {
				if ( this.insideChangeEvent ) {
					return;
				}
				// Prevent infinite loop
				this.insideChangeEvent = true;
				// Get current select value and preset data
				let $select = jQuery( e.currentTarget );
				let val = $select.val();
				if ( this.applyPresetSettings( val ) ) {
					// Restore current select value because it can be changed by the listener while style settings changed
					$select.val( val );
					this.setSettingsModel( val );
				}
				// Clear insideChangeEvent flag
				this.insideChangeEvent = false;
			},

			// Apply settings from preset to the current widget
			applyPresetSettings: function( val ) {
				return applyPresetSettings( this.container, val );
			},

			// check control if we need it 
			isStyleTransferControl: function( el ) {
				return isStyleTransferControl( el );
			},

			// settings of the current opened panel
			getElementSettingsModel: function() {
				return this.container.settings;
			},

			// Set new settings for the current element
			setElementSettings: function( data ) {
				setElementSettings( this.container, data );
			},

			getSettingsToSave: function() {
				let options = Object.assign( {}, this.getChangedSettings() );
				let option_name = '';
				let controls = this.getElementSettingsModel().controls;

				// exclude some settings
				if ( trx_addons_elementor_control_preset_vars.excluded_options ) {
					for ( option_name in options ) {
						if ( trx_addons_elementor_control_preset_vars.excluded_options.indexOf( option_name ) >= 0 ) {
							delete options[ option_name ];
						}
					}
				}

				// exclude not style controls
				for ( option_name in options ) {
					if ( ! controls[option_name] ) {
						continue;
					}
					// remove values inside repeaters if control is not style transfer
					if ( controls[option_name].is_repeater ) {
						if ( controls[option_name].fields ) {
							let model = this.getElementSettingsModel().get( option_name );
							if ( typeof model == 'object' && ! Array.isArray( model ) ) {
								let group = model.clone();
								let group_data = [];
								group.each( function( groupEl, index ) {
									group_data.push( [] );
									jQuery.each( groupEl.controls, function( grElIndex, El ){
										if ( this.isStyleTransferControl( El ) ) {
											if ( typeof options[option_name][index] !== 'undefined' && typeof options[option_name][index][grElIndex] !== 'undefined' ) {
												group_data[index][grElIndex] = options[option_name][index][grElIndex];
											}
										}
									}.bind(this) );
								}.bind(this) );
								if ( group_data.length > 0 && group_data[0].length > 0 ) {
									options[option_name] = group_data;
								} else {
									delete options[option_name];
								}
							}
						}
					} else if ( ! this.isStyleTransferControl( controls[option_name] ) ) {
						delete options[option_name];
					}
				}
				return JSON.stringify( options );
			},

			getAllSettings: function() {
				return this.getElementSettingsModel().attributes;
			},

			getChangedSettings: function( return_default ) {
				return getChangedSettings( this.container, return_default );
			},


			// SAVE PRESET
			//--------------------------------------------

			onSavePresetDialog: function(e) {
				let $button = jQuery(e.target);

				// elementor bug
				if ( ! $button.hasClass('elementor-button') ) {
					$button = $button.closest('.elementor-button');
				}

				let $wrap = $button.closest('.elementor-control');

				//let $select = $wrap.find('select');
				//$select.val('');

				$wrap.find('.trx-addons-create-preset-name').val('');
				setTimeout( function() {
					$wrap.find('.trx-addons-create-preset-name').get(0).focus();
				}, 100 );
				$wrap.find('.trx-addons-preset-message').html('');

				$wrap.find('.trx-addons-panel-popup--save-preset').show();

				return false;
			},

			onSavePresetOk: function(e){
				let $button = jQuery(e.target);

				// elementor bug
				if ( ! $button.hasClass('elementor-button') ) {
					$button = $button.closest('.elementor-button');
				}

				let $wrap = $button.closest('.elementor-control-field');
				let $input = $wrap.find('.trx-addons-create-preset-name');
				let $select = $wrap.find('select');
				let that = this;
				let $message = $wrap.find('.trx-addons-preset-message');
				let widget_type = this.getElementSettingsModel().attributes.elType == 'widget' ? this.getElementSettingsModel().attributes.widgetType : this.getElementSettingsModel().attributes.elType;

				// check field name not empty
				if ( ! $input.val() ) {
					$input.addClass('trx-addons-new-preset-name--error');
					setTimeout( function() {
						$input.removeClass('trx-addons-new-preset-name--error');
					}, 2000);
					return;
				}

				let settings = this.getSettingsToSave();

				let data = {
					action: 'trx_addons_action_create_preset',
					nonce: trx_addons_elementor_control_preset_vars.ajax_nonce,
					options: settings,
					preset_name: $input.val(),
					widget_type: widget_type,
					preset_id: '',
					default: $wrap.find('.trx-addons-create-preset-default:checked').length == 1 ? 1 : 0
				};

				jQuery.ajax( {
					type: 'POST',
					dataType: 'json',
					url: trx_addons_elementor_control_preset_vars.ajax_url,
					data: data,
					beforeSend: function( xhr ) {
						$button.find('.trx-addons-button-state-icon').show();
						$button.find('.trx-addons-button-label').hide();
						$message.html('');
					}
				} ).done( function( response ) {

					if ( ! response.error ) {
						$message.html( `<div class="trx-addons-preset-message--success">${response.success}</div>`);

						setTimeout( function () {
							$wrap.find('.trx-addons-panel-popup--save-preset').hide();
							$message.html('');
						}, 2000 );

						// update options and redraw
						if ( response.preset.default ) {
							// clear a flag 'default' for all presets
							for ( let preset_id in trx_addons_elementor_control_preset_vars.presets ) {
								if ( trx_addons_elementor_control_preset_vars.presets[preset_id].widget_type == widget_type ) {
									trx_addons_elementor_control_preset_vars.presets[preset_id].default = 0;
								}
							}
						}
						trx_addons_elementor_control_preset_vars.presets[ response.id ] = response.preset;
						that.updateCustomSelect( $wrap );
						$select.val( response.id );
						$select.trigger( 'change' );
					} else {
						$wrap.find('.trx-addons-preset-message').html( `<div class="trx-addons-preset-message--error">${response.error}</div>` );
					}
				} ).always( function () {
					$button.find('.trx-addons-button-state-icon').hide();
					$button.find('.trx-addons-button-label').show();
				} );
			},

			onSavePresetCancel: function(e){
				let $button = jQuery(e.target);

				// elementor bug
				if ( ! $button.hasClass('elementor-button') ) {
					$button = $button.closest('.elementor-button');
				}

				$button.closest('.trx-addons-panel-popup').hide();
				return false;
			},


			// UPDATE PRESET
			//--------------------------------------------

			onUpdatePresetDialog: function(e){
				let $button = jQuery(e.target);

				// elementor bug
				if ( ! $button.hasClass('elementor-button') ) {
					$button = $button.closest('.elementor-button');
				}

				let $wrap = $button.closest('.elementor-control');
				let $select = $wrap.find('select');
				let preset_id = $select.val();

				// clean checkboxes
				$wrap.find('.trx-addons-update-preset-confirm').prop('checked', false);
				$wrap.find('.trx-addons-update-preset-default').prop('checked', trx_addons_elementor_control_preset_vars.presets[preset_id].default > 0);

				// check field select not empty
				if ( ! preset_id || preset_id == 'custom' ) {
					$select.addClass('trx-addons-new-preset-name--error');
					setTimeout(function(){
						$select.removeClass('trx-addons-new-preset-name--error');
					}, 2000);
					return;
				}

				$wrap.find('.trx-addons-update-preset-name').val( $select.find('option[value='+preset_id+']').text() );
				setTimeout( function() {
					$wrap.find('.trx-addons-update-preset-name').get(0).focus();
				}, 100 );
				$wrap.find('.trx-addons-preset-message').html('');

				$wrap.find('.trx-addons-panel-popup--update-preset').show();

				return false;
			},

			onUpdatePresetOk: function(e){
				let $button = jQuery(e.target);

				// elementor bug
				if ( ! $button.hasClass('elementor-button') ) {
					$button = $button.closest('.elementor-button');
				}

				let $wrap = $button.closest('.elementor-control-field');
				let $input = $wrap.find('.trx-addons-update-preset-name');
				let $select = $wrap.find('select');
				let that = this;
				let $message = $wrap.find('.trx-addons-update-preset-message');
				let widget_type = this.getElementSettingsModel().attributes.elType == 'widget' ? this.getElementSettingsModel().attributes.widgetType : this.getElementSettingsModel().attributes.elType;
				let preset_id = $select.val();

				// check field name not empty
				if ( ! $input.val() ) {
					$input.addClass('trx-addons-new-preset-name--error');
					setTimeout(function(){
						$input.removeClass('trx-addons-new-preset-name--error');
					}, 2000);
					return;
				}

				let settings = this.getSettingsToSave();

				let data = {
					action: 'trx_addons_action_update_preset',
					nonce: trx_addons_elementor_control_preset_vars.ajax_nonce,
					options: $wrap.find('.trx-addons-update-preset-confirm:checked').length == 0 ? '' : settings,
					preset_name: $input.val(),
					widget_type: widget_type,
					preset_id: preset_id,
					default: $wrap.find('.trx-addons-update-preset-default:checked').length == 1 ? 1 : 0
				};

				jQuery.ajax( {
					type: 'POST',
					dataType: 'json',
					url: trx_addons_elementor_control_preset_vars.ajax_url,
					data: data,
					beforeSend: function( xhr ) {
						$button.find('.trx-addons-button-state-icon').show();
						$button.find('.trx-addons-button-label').hide();
						$message.html('');
					}
				} ).done( function( response ) {

					if ( ! response.error ) {
						$message.html( `<div class="trx-addons-preset-message--success">${response.success}</div>` );

						setTimeout( function() {
							$wrap.find('.trx-addons-panel-popup--update-preset').hide();
							$message.html('');
						}, 2000 );

						// update options and redraw
						if ( response.preset.default ) {
							// clear a flag 'default' for all presets
							for ( let preset_id in trx_addons_elementor_control_preset_vars.presets ) {
								if ( trx_addons_elementor_control_preset_vars.presets[preset_id].widget_type == widget_type ) {
									trx_addons_elementor_control_preset_vars.presets[preset_id].default = 0;
								}
							}
						}
						if ( $wrap.find('.trx-addons-update-preset-default:checked').length && response.preset.default ) {
							trx_addons_elementor_control_preset_vars.presets[ preset_id ].default = 1;
						}
						if ( $wrap.find('.trx-addons-update-preset-confirm:checked').length && data.options ) {
							trx_addons_elementor_control_preset_vars.presets[ preset_id ].options = JSON.parse( data.options );
							$select.trigger('change');
						}
						trx_addons_elementor_control_preset_vars.presets[ preset_id ].title = response.preset.title;
						that.updateCustomSelect( $wrap );
					} else {
						$wrap.find('.trx-addons-update-preset-message').html( `<div class="trx-addons-update-preset-message--error">${response.error}</div>` );
					}
				} ).always( function() {
					$button.find('.trx-addons-button-state-icon').hide();
					$button.find('.trx-addons-button-label').show();
				} );
			},



			// DELETE PRESET
			//--------------------------------------------
			onDeletePresetDialog: function(e){
				let $button = jQuery(e.target);

				// elementor bug
				if ( ! $button.hasClass('elementor-button') ) {
					$button = $button.closest('.elementor-button');
				}

				let $wrap = $button.closest('.elementor-control-field');

				$wrap.find('.trx-addons-panel-popup--delete-preset').show();
				return false;
			},

			onDeletePresetOk: function(e){
				let $button = jQuery(e.target);

				// elementor bug
				if ( ! $button.hasClass('elementor-button') ) {
					$button = $button.closest('.elementor-button');
				}

				let $wrap = $button.closest('.elementor-control-field');
				let $select = $wrap.find('select');
				let that = this;
				let $message = $wrap.find('.trx-addons-preset-message');
				let preset_id = $select.val();

				let data = {
					action: 'trx_addons_action_delete_preset',
					nonce: trx_addons_elementor_control_preset_vars.ajax_nonce,
					preset_id: preset_id
				};

				jQuery.ajax( {
					type: 'POST',
					dataType: 'json',
					url: trx_addons_elementor_control_preset_vars.ajax_url,
					data: data,
					beforeSend: function( xhr ) {
						$button.find('.trx-addons-button-state-icon').show();
						$button.find('.trx-addons-button-label').hide();
						$message.html('');
					}
				} ).done( function( response ) {

					if ( ! response.error ) {
						$message.html( `<div class="trx-addons-preset-message--success">${response.success}</div>` );

						setTimeout(function(){
							$wrap.find('.trx-addons-panel-popup--delete-preset').hide();
							$message.html('');

							// update options and redraw
							delete trx_addons_elementor_control_preset_vars.presets[ preset_id ];
							that.updateCustomSelect( $wrap );
							$select.val('');
							$select.trigger('change');
						}, 2000 );
					} else {
						$wrap.find( '.trx-addons-preset-message').html( `<div class="trx-addons-preset-message--error">${response.error}</div>` );
					}
				} ).always( function() {
					$button.find('.trx-addons-button-state-icon').hide();
					$button.find('.trx-addons-button-label').show();
				} );
			},

			onDeletePresetCancel: function(e){
				let $button = jQuery(e.target);

				// elementor bug
				if ( ! $button.hasClass('elementor-button') ) {
					$button = $button.closest('.elementor-button');
				}

				let $wrap = $button.closest('.elementor-control-field');

				$wrap.find('.trx-addons-panel-popup--delete-preset').hide();
				return false;
			},


			// RESET STYLE
			//--------------------------------------------

			onResetStyle: function(e) {
				let $button = jQuery(e.target);

				// elementor bug
				if ( ! $button.hasClass('elementor-button') ) {
					$button = $button.closest('.elementor-button');
				}
				let $wrap = $button.closest('.elementor-control-field');
				let $select = $wrap.find('select');

				// Old way: Trigger elementor reset for current openned panel.
				// let opt = elementor.getPanelView().getCurrentPageView().getOption( 'editedElementView' );
				// $e.run( "document/elements/reset-style", { container: opt.getContainer() } );

				// New way: Reset current element settings with a default values
				this.setElementSettings( this.getChangedSettings( true ) );
					
				// Reset select 
				$select.val( '' );

				// Reset select in elementor object 
				this.setSettingsModel( '' );
			}

		} );

		// Add functions to the select control  https://developers.elementor.com/creating-a-new-control/
		// Here we change elementor class to elementor+our functions, add some our events to Elementor Select input 
		elementor.addControlView( 'trx_preset', TrxAddonsPresetItemView );


		// Listen for the 'add' event on the each container model
		elementor.on( 'document:loaded', ( document ) => {
			const listener = (model) => {
				justInserted = true;
				setTimeout( function() {
					justInserted = false;
				}, 1000 );
				addListeners( model.get('elements') );
			};
			const addListeners = (collection) => {
				if ( collection && collection.on ) { 
					collection.on( 'add', listener );
					if  ( collection.length ) {
						collection.each( (el) => {
							var elements = el.get('elements');
							if ( elements && elements.length > 0 ) {
								addListeners( elements );
							}
						} );
					}
				}
			};
			addListeners( document.container.model.get('elements') );
		} );

		// Set a default preset if a current value is empty and the widget is just inserted
		elementor.hooks.addAction( 'panel/open_editor/widget', onOpenEditor );
		elementor.hooks.addAction( 'panel/open_editor/container', onOpenEditor );
		elementor.hooks.addAction( 'panel/open_editor/section', onOpenEditor );
		elementor.hooks.addAction( 'panel/open_editor/column', onOpenEditor );
		
		function onOpenEditor( panel, model, view ) {

			if ( ! model.has( 'settings' ) || ! justInserted ) {
				return;
			}

			const settings = model.get( 'settings' );

			// Check if the widget has the 'trx_addons_preset' setting
			if ( ! settings.controls || ! settings.controls.hasOwnProperty( 'trx_addons_preset' ) ) {
				return;
			}

			// Set a default preset if a current value is empty
			let widget_type = model.get( 'elType' ) == 'widget' ? model.get( 'widgetType' ) : model.get( 'elType' );
			let current_value = settings.get( 'trx_addons_preset' );
			for ( let preset_id in trx_addons_elementor_control_preset_vars.presets ) {
				if ( trx_addons_elementor_control_preset_vars.presets[ preset_id ].widget_type != widget_type ) {
					continue;
				}
				if ( ! current_value && trx_addons_elementor_control_preset_vars.presets[preset_id].default ) {
					settings.set( 'trx_addons_preset', preset_id );
					applyPresetSettings( view.container, preset_id );
					break;
				}
			}
		}

		// Set the field 'trx_addons_preset' to the 'custom' on any styling setting are changed
		/*
		elementor.hooks.addAction( 'panel/open_editor/widget', function( panel, model, view ) {

			if ( ! model.has( 'settings' ) ) {
				return;
			}

			const settings = model.get( 'settings' );

			// Check if the widget has the 'trx_addons_preset' setting
			if ( ! settings.controls || ! settings.controls.hasOwnProperty( 'trx_addons_preset' ) ) {
				return;
			}

			settings.on( 'change', function( changedModel, value, options ) {
				const changedAttr = Object.keys( changedModel.changed )[0];
				if ( changedAttr !== 'trx_addons_preset' && isStyleTransferControl( settings.controls[ changedAttr ] ) ) {
					settings.set( 'trx_addons_preset', 'custom' );
				}
			} );
		} );
		*/

		// Apply settings from preset to the current widget
		function applyPresetSettings( widget, preset_id ) {
			let data = typeof trx_addons_elementor_control_preset_vars.presets[ preset_id ] !== 'undefined' ? trx_addons_elementor_control_preset_vars.presets[ preset_id ].options : '';
			// If no preset data - exit
			if ( typeof data !== 'undefined' && data ) {
				// Reset current element settings with a default values (to avoid situations when some settings are not set in the preset)
				setElementSettings( widget, getChangedSettings( widget, true ) );
				// Set new settings for the current element
				setElementSettings( widget, data );
				return true;
			}
			return false;
		}

		// Set new settings for the widget
		function setElementSettings( widget, data ) {
			let controls = widget.controls;
			let newOptions = {};

			jQuery.each( controls, function( name, el ) {
				if ( 'trx_addons_preset' !== name && ( typeof data[name] !== 'undefined' ) ) {
					if ( el.is_repeater ) {
						let el_model = widget.model.get( name );
						if ( typeof el_model == 'object' && ! Array.isArray( el_model ) ) {
							let group = el_model.clone();
							group.each( function( groupEl, index ) {
								var data_index = Math.min( index, data[name].length - 1 );
								if ( typeof data[name][data_index] !== 'undefined' ) {
									jQuery.each( groupEl.controls, function( grElIndex, El ){
										if ( isStyleTransferControl( El ) && typeof data[name][data_index][grElIndex] !== 'undefined' && grElIndex != '_id' ) {
											group.at( index ).set( grElIndex, data[name][data_index][grElIndex] );
										}
									} );
								}
							} );
							newOptions[name] = group;
						}
					} else if ( isStyleTransferControl( el ) ) {
						newOptions[name] = data[name];
					}
				}
			} );

			// update widget settings if need (newOptions not empty)
			if ( Object.keys( newOptions ).length > 0 ) {
				$e.run( 'document/elements/settings', {
					container: widget,
					settings: newOptions,
					options: {
						external: true,
						render: false
					}
				} );
				/*
				// reset global settings
				$e.run( 'document/globals/settings', {
					container: widget,
					settings: {},
					options: {
						external: true,
						render: false
					}
				} );

				// apply new globals if need
				if ( typeof data.__globals__ != 'undefined' && ( ! Array.isArray( data.__globals__ ) || data.__globals__.length > 0 ) ) {
					$e.run( 'document/globals/settings', {
						container: widget,
						settings: data.__globals__,
						options: {
							external: true,
							render: false
						}
					} );
				}
				*/
				// trigger update screen 
				widget.render();

				// make a button 'Publish/Update Document' active
				$e.components.get('document/save').footerSaver.activateSaveButtons( document, true );
			}
		}

		function getChangedSettings( widget, return_default ) {
			let settings = {};
			let currentSettings = widget.settings.attributes;
			let defaultSettings = widget.settings.defaults;

			for( let name in currentSettings ) {
				if ( _.isEqual( currentSettings[name], defaultSettings[name] ) ) {
					continue;
				}
				if ( return_default ) {
					if ( typeof defaultSettings[name] !== 'undefined' ) {   // skip if default value not exists
						settings[name] = defaultSettings[name];
					}
				} else {
					settings[name] = currentSettings[name];
				}
			}

			return settings;
		};


		// Check if the control element is a style transfer control
		function isStyleTransferControl( el ) {
			return ( typeof el !== 'undefined' ) && el.style_transfer
					? el.style_transfer
					: "content" !== el.tab || el.selectors || el.prefix_class || el.for_preset;
		}

	} );

} )();
