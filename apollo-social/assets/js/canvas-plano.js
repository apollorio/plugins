/**
 * Apollo Plano Editor - Main JavaScript
 *
 * Fabric.js-based image editor for Apollo Creative Studio
 * NO TAILWIND - Uses Apollo design system
 *
 * @package Apollo_Social
 */

(function() {
	'use strict';

	// Wait for DOM and Fabric.js to be ready.
	document.addEventListener('DOMContentLoaded', function() {
		if (typeof fabric === 'undefined') {
			console.error('[Apollo Plano] Fabric.js não carregado.');
			return;
		}

		// Prevent gesture zoom on mobile.
		document.addEventListener('gesturestart', e => e.preventDefault());
		document.addEventListener('gesturechange', e => e.preventDefault());

		// Configure Fabric.js default object styles.
		fabric.Object.prototype.set({
			borderColor: '#ff8c00',
			cornerColor: '#ff8c00',
			cornerSize: 12,
			transparentCorners: false,
			cornerStyle: 'circle'
		});

		// Initialize canvas.
		const canvas = new fabric.Canvas('ap-plano-canvas', {
			width: 320,
			height: 568,
			backgroundColor: '#f9a748',
			preserveObjectStacking: true
		});

		// Custom properties for objects.
		fabric.Object.prototype.customBlur = 0;
		fabric.Object.prototype.customBlendMode = 'normal';

		// DOM References (using Apollo class prefixes).
		const R = {
			canvasWrapper: document.getElementById('ap-plano-canvas-wrapper'),
			downloadBtn: document.getElementById('ap-plano-download-btn'),
			balDropper: document.getElementById('ap-plano-bal-dropper'),
			buildBtn: document.getElementById('ap-plano-build-btn'),
			buildPanel: document.getElementById('ap-plano-build-panel'),
			templatesBtn: document.getElementById('ap-plano-templates-btn'),
			templatesModal: document.getElementById('ap-plano-templates-modal'),
			closeTemplates: document.getElementById('ap-plano-close-templates'),
			templatesGrid: document.getElementById('ap-plano-templates-grid'),
			ratioBtn: document.getElementById('ap-plano-ratio-btn'),
			gradientBtn: document.getElementById('ap-plano-gradient-btn'),
			gradientModal: document.getElementById('ap-plano-gradient-modal'),
			closeGradient: document.getElementById('ap-plano-close-gradient'),
			gradientGrid: document.getElementById('ap-plano-gradient-grid'),
			deleteBtn: document.getElementById('ap-plano-delete-btn'),
			addTextBtn: document.getElementById('ap-plano-add-text-btn'),
			addImageBtn: document.getElementById('ap-plano-add-image-btn'),
			addBoxBtn: document.getElementById('ap-plano-add-box-btn'),
			imageUpload: document.getElementById('ap-plano-image-upload'),
			elementControls: document.getElementById('ap-plano-element-controls'),
			elemColorInput: document.getElementById('ap-plano-elem-color-input'),
			elemColorCircle: document.getElementById('ap-plano-elem-color-circle'),
			elemBorderBtn: document.getElementById('ap-plano-elem-border-btn'),
			borderPopup: document.getElementById('ap-plano-border-popup'),
			borderSlider: document.getElementById('ap-plano-border-slider'),
			borderValue: document.getElementById('ap-plano-border-value'),
			elemOpacityBtn: document.getElementById('ap-plano-elem-opacity-btn'),
			opacityPopup: document.getElementById('ap-plano-opacity-popup'),
			opacitySlider: document.getElementById('ap-plano-opacity-slider'),
			opacityValue: document.getElementById('ap-plano-opacity-value'),
			elemBlurBtn: document.getElementById('ap-plano-elem-blur-btn'),
			blurPopup: document.getElementById('ap-plano-blur-popup'),
			blurSlider: document.getElementById('ap-plano-blur-slider'),
			blurValue: document.getElementById('ap-plano-blur-value'),
			elemBlendBtn: document.getElementById('ap-plano-elem-blend-btn'),
			blendPopup: document.getElementById('ap-plano-blend-popup'),
			blendSelect: document.getElementById('ap-plano-blend-select'),
			elemFontBtn: document.getElementById('ap-plano-elem-font-btn'),
			fontPopup: document.getElementById('ap-plano-font-popup'),
			fontSelect: document.getElementById('ap-plano-font-select'),
			elemTexttypeBtn: document.getElementById('ap-plano-elem-texttype-btn'),
			texttypePopup: document.getElementById('ap-plano-texttype-popup'),
			elemFontweightBtn: document.getElementById('ap-plano-elem-fontweight-btn'),
			elemFontsizeBtn: document.getElementById('ap-plano-elem-fontsize-btn'),
			fontsizePopup: document.getElementById('ap-plano-fontsize-popup'),
			fontsizeSlider: document.getElementById('ap-plano-fontsize-slider'),
			fontsizeValue: document.getElementById('ap-plano-fontsize-value'),
			alignBtn: document.getElementById('ap-plano-align-btn'),
			layerUpBtn: document.getElementById('ap-plano-layer-up-btn'),
			layerDownBtn: document.getElementById('ap-plano-layer-down-btn'),
			bgColor: document.getElementById('ap-plano-bg-color'),
			transparentBgToggle: document.getElementById('ap-plano-transparent-bg-toggle'),
			undoBtn: document.getElementById('ap-plano-undo-btn'),
			redoBtn: document.getElementById('ap-plano-redo-btn')
		};

		// History Manager (Undo/Redo).
		const HistoryManager = {
			undoStack: [],
			redoStack: [],
			maxHistory: 50,
			isRestoring: false,

			init(canvas) {
				this.canvas = canvas;
				this.saveState();
				canvas.on('object:added', () => this.onCanvasChange());
				canvas.on('object:modified', () => this.onCanvasChange());
				canvas.on('object:removed', () => this.onCanvasChange());
			},

			onCanvasChange() {
				if (!this.isRestoring) {
					this.saveState();
				}
			},

			saveState() {
				const json = this.canvas.toJSON(['customBlur', 'customBlendMode', 'customTextType']);
				this.undoStack.push(JSON.stringify(json));
				if (this.undoStack.length > this.maxHistory) {
					this.undoStack.shift();
				}
				this.redoStack = [];
				this.updateButtons();
			},

			undo() {
				if (this.undoStack.length <= 1) return;
				const current = this.undoStack.pop();
				this.redoStack.push(current);
				const previous = this.undoStack[this.undoStack.length - 1];
				this.restoreState(previous);
				this.updateButtons();
			},

			redo() {
				if (this.redoStack.length === 0) return;
				const next = this.redoStack.pop();
				this.undoStack.push(next);
				this.restoreState(next);
				this.updateButtons();
			},

			restoreState(stateJson) {
				this.isRestoring = true;
				this.canvas.loadFromJSON(JSON.parse(stateJson), () => {
					this.canvas.renderAll();
					this.isRestoring = false;
				});
			},

			updateButtons() {
				if (R.undoBtn) R.undoBtn.disabled = this.undoStack.length <= 1;
				if (R.redoBtn) R.redoBtn.disabled = this.redoStack.length === 0;
			}
		};

		// Initialize history.
		HistoryManager.init(canvas);

		// Undo/Redo handlers.
		if (R.undoBtn) {
			R.undoBtn.addEventListener('click', () => HistoryManager.undo());
		}
		if (R.redoBtn) {
			R.redoBtn.addEventListener('click', () => HistoryManager.redo());
		}

		// Initialize CanvasTools (modular tool panel)
		let canvasTools = null;
		if (typeof CanvasTools !== 'undefined') {
			canvasTools = new CanvasTools({
				canvas: canvas,
				container: document.querySelector('.ap-plano-editor')
			});
		}

		// Canvas event handlers.
		canvas.on('selection:created', updateControls);
		canvas.on('selection:updated', updateControls);
		canvas.on('selection:cleared', hideControls);
		canvas.on('object:modified', applyEffects);

		// Apply effects to active object.
		function applyEffects() {
			const obj = canvas.getActiveObject();
			if (obj) {
				const blur = obj.customBlur || 0;
				const blend = obj.customBlendMode || 'normal';
				obj.set({
					filter: blur > 0 ? new fabric.Image.filters.Blur({ blur: blur / 10 }) : null,
					globalCompositeOperation: blend
				});
				canvas.requestRenderAll();
			}
		}

		// Apply filters to active object
		function applyFilters() {
			const obj = canvas.getActiveObject();
			if (!obj || obj.type !== 'image') return;

			const filters = [];
			const brightness = parseFloat(document.getElementById('ap-plano-filter-brightness')?.value || 0);
			const contrast = parseFloat(document.getElementById('ap-plano-filter-contrast')?.value || 0);
			const saturation = parseFloat(document.getElementById('ap-plano-filter-saturation')?.value || 0);
			const hue = parseFloat(document.getElementById('ap-plano-filter-hue')?.value || 0);

			if (brightness !== 0) {
				filters.push(new fabric.Image.filters.Brightness({ brightness: brightness }));
			}
			if (contrast !== 0) {
				filters.push(new fabric.Image.filters.Contrast({ contrast: contrast }));
			}
			if (saturation !== 0) {
				filters.push(new fabric.Image.filters.Saturation({ saturation: saturation }));
			}
			if (hue !== 0) {
				filters.push(new fabric.Image.filters.HueRotation({ rotation: hue / 180 }));
			}

			obj.filters = filters;
			obj.applyFilters();
			canvas.renderAll();
		}

		// Filters modal
		const filtersBtn = document.getElementById('ap-plano-elem-filters-btn');
		const filtersModal = document.getElementById('ap-plano-filters-modal');
		const closeFilters = document.getElementById('ap-plano-close-filters');

		if (filtersBtn && filtersModal) {
			filtersBtn.addEventListener('click', () => {
				const obj = canvas.getActiveObject();
				if (obj && obj.type === 'image') {
					filtersModal.classList.remove('hidden');
				}
			});
		}

		if (closeFilters && filtersModal) {
			closeFilters.addEventListener('click', () => {
				filtersModal.classList.add('hidden');
			});
		}

		// Filter sliders
		const filterControls = ['brightness', 'contrast', 'saturation', 'hue'];
		filterControls.forEach(control => {
			const slider = document.getElementById(`ap-plano-filter-${control}`);
			const valueSpan = document.getElementById(`ap-plano-filter-${control}-value`);
			
			if (slider && valueSpan) {
				slider.addEventListener('input', (e) => {
					valueSpan.textContent = e.target.value;
					applyFilters();
				});
			}
		});

		// Filter presets
		document.querySelectorAll('.ap-plano-filter-preset').forEach(preset => {
			preset.addEventListener('click', () => {
				const presetType = preset.dataset.preset;
				const brightnessSlider = document.getElementById('ap-plano-filter-brightness');
				const contrastSlider = document.getElementById('ap-plano-filter-contrast');
				const saturationSlider = document.getElementById('ap-plano-filter-saturation');
				const hueSlider = document.getElementById('ap-plano-filter-hue');

				switch (presetType) {
					case 'warm':
						if (brightnessSlider) brightnessSlider.value = 0.2;
						if (saturationSlider) saturationSlider.value = 0.3;
						if (hueSlider) hueSlider.value = 10;
						break;
					case 'cool':
						if (contrastSlider) contrastSlider.value = 0.2;
						if (hueSlider) hueSlider.value = -10;
						break;
					case 'bw':
						if (saturationSlider) saturationSlider.value = -1;
						break;
					case 'reset':
						if (brightnessSlider) brightnessSlider.value = 0;
						if (contrastSlider) contrastSlider.value = 0;
						if (saturationSlider) saturationSlider.value = 0;
						if (hueSlider) hueSlider.value = 0;
						break;
				}

				// Update value displays
				filterControls.forEach(control => {
					const slider = document.getElementById(`ap-plano-filter-${control}`);
					const valueSpan = document.getElementById(`ap-plano-filter-${control}-value`);
					if (slider && valueSpan) {
						valueSpan.textContent = slider.value;
					}
				});

				applyFilters();
			});
		});

		// Update controls when object is selected.
		function updateControls() {
			const obj = canvas.getActiveObject();
			if (!obj) return;

			if (R.elementControls) {
				R.elementControls.classList.add('active');
			}

			const isText = obj.type === 'i-text' || obj.type === 'textbox';
			const isImage = obj.type === 'image';
			const isShape = obj.type === 'rect' || obj.type === 'circle';

			// Remove all type classes.
			if (R.elementControls) {
				R.elementControls.classList.remove('text-active', 'box-active', 'image-active');
			}

			if (isText) {
				if (R.elementControls) R.elementControls.classList.add('text-active');
				if (R.elemColorInput) R.elemColorInput.value = obj.fill || '#000000';
				if (R.fontsizeSlider) {
					R.fontsizeSlider.value = obj.fontSize || 24;
					if (R.fontsizeValue) R.fontsizeValue.textContent = obj.fontSize || 24;
				}
			} else if (isImage) {
				if (R.elementControls) R.elementControls.classList.add('image-active');
				if (R.elemColorInput) R.elemColorInput.value = '#ffffff';
			} else {
				if (R.elementControls) R.elementControls.classList.add('box-active');
				if (R.elemColorInput) R.elemColorInput.value = obj.fill || '#ffffff';
				if (R.borderSlider) {
					R.borderSlider.value = obj.rx || 0;
					if (R.borderValue) R.borderValue.textContent = obj.rx || 0;
				}
			}

			if (R.opacitySlider) {
				R.opacitySlider.value = Math.round((obj.opacity || 1) * 100);
				if (R.opacityValue) R.opacityValue.textContent = R.opacitySlider.value;
			}

			if (R.blurSlider) {
				R.blurSlider.value = obj.customBlur || 0;
				if (R.blurValue) R.blurValue.textContent = obj.customBlur || 0;
			}

			if (R.blendSelect) {
				R.blendSelect.value = obj.customBlendMode || 'normal';
			}

			if (R.elemColorCircle && R.elemColorInput) {
				R.elemColorCircle.style.background = R.elemColorInput.value;
			}
		}

		// CanvasTools handles layer controls and delete, but we keep updateControls here
		// for compatibility with existing popup controls

		// Hide controls when selection is cleared.
		function hideControls() {
			if (R.elementControls) {
				R.elementControls.classList.remove('active', 'text-active', 'box-active', 'image-active');
			}
			closeAllPopups();
		}

		// Close all popups.
		function closeAllPopups() {
			const popups = [
				R.borderPopup,
				R.opacityPopup,
				R.blurPopup,
				R.blendPopup,
				R.fontPopup,
				R.fontsizePopup,
				R.texttypePopup
			];
			popups.forEach(p => {
				if (p) p.classList.remove('active');
			});
		}

		// Toggle popup.
		function togglePopup(popup, btn) {
			if (!popup || !btn) return;
			const was = popup.classList.contains('active');
			closeAllPopups();
			if (!was) {
				popup.classList.add('active');
				const rect = btn.getBoundingClientRect();
				popup.style.top = `${rect.top}px`;
				popup.style.left = `${rect.right + 10}px`;
			}
		}

		// Tool buttons are now handled by CanvasTools
		// Close build panel when tools are used
		if (R.addTextBtn) {
			R.addTextBtn.addEventListener('click', () => {
				if (R.buildPanel) R.buildPanel.classList.remove('active');
			});
		}
		if (R.addBoxBtn) {
			R.addBoxBtn.addEventListener('click', () => {
				if (R.buildPanel) R.buildPanel.classList.remove('active');
			});
		}
		if (R.addImageBtn) {
			R.addImageBtn.addEventListener('click', () => {
				if (R.buildPanel) R.buildPanel.classList.remove('active');
			});
		}

		// Background color.
		if (R.bgColor) {
			R.bgColor.addEventListener('input', (e) => {
				canvas.setBackgroundColor(e.target.value, canvas.renderAll.bind(canvas));
				if (e.target.parentElement) {
					e.target.parentElement.style.background = e.target.value;
				}
			});
		}

		// Element color.
		if (R.elemColorInput) {
			R.elemColorInput.addEventListener('input', (e) => {
				const obj = canvas.getActiveObject();
				if (obj) {
					obj.set('fill', e.target.value);
					canvas.renderAll();
					if (R.elemColorCircle) {
						R.elemColorCircle.style.background = e.target.value;
					}
				}
			});
		}

		// Opacity slider.
		if (R.opacitySlider) {
			R.opacitySlider.addEventListener('input', (e) => {
				const obj = canvas.getActiveObject();
				if (obj) {
					const opacity = parseInt(e.target.value) / 100;
					obj.set('opacity', opacity);
					canvas.renderAll();
					if (R.opacityValue) R.opacityValue.textContent = e.target.value;
				}
			});
		}

		// Border slider.
		if (R.borderSlider) {
			R.borderSlider.addEventListener('input', (e) => {
				const obj = canvas.getActiveObject();
				if (obj && (obj.type === 'rect' || obj.type === 'image')) {
					const radius = parseInt(e.target.value);
					if (obj.type === 'rect') {
						obj.set({ rx: radius, ry: radius });
					} else if (obj.type === 'image') {
						obj.set({
							clipPath: new fabric.Rect({
								width: obj.width,
								height: obj.height,
								rx: radius / obj.scaleX,
								ry: radius / obj.scaleY,
								originX: 'center',
								originY: 'center'
							})
						});
					}
					canvas.renderAll();
					if (R.borderValue) R.borderValue.textContent = radius;
				}
			});
		}

		// Blur slider.
		if (R.blurSlider) {
			R.blurSlider.addEventListener('input', (e) => {
				const obj = canvas.getActiveObject();
				if (obj) {
					const blur = parseInt(e.target.value);
					obj.customBlur = blur;
					if (blur > 0) {
						const blurFilter = new fabric.Image.filters.Blur({ blur: blur / 10 });
						obj.filters = [blurFilter];
						obj.applyFilters();
					} else {
						obj.filters = [];
						obj.applyFilters();
					}
					canvas.renderAll();
					if (R.blurValue) R.blurValue.textContent = blur;
				}
			});
		}

		// Blend select.
		if (R.blendSelect) {
			R.blendSelect.addEventListener('change', (e) => {
				const obj = canvas.getActiveObject();
				if (obj) {
					obj.customBlendMode = e.target.value;
					obj.globalCompositeOperation = e.target.value;
					canvas.renderAll();
				}
			});
		}

		// Font size slider.
		if (R.fontsizeSlider) {
			R.fontsizeSlider.addEventListener('input', (e) => {
				const obj = canvas.getActiveObject();
				if (obj && (obj.type === 'i-text' || obj.type === 'textbox')) {
					obj.set('fontSize', parseInt(e.target.value));
					canvas.renderAll();
					if (R.fontsizeValue) R.fontsizeValue.textContent = e.target.value;
				}
			});
		}

		// Build panel toggle.
		if (R.buildBtn) {
			R.buildBtn.addEventListener('click', () => {
				closeAllPopups();
				if (R.buildPanel) {
					R.buildPanel.classList.toggle('active');
				}
			});
		}

		// Ratio toggle.
		if (R.ratioBtn) {
			R.ratioBtn.addEventListener('click', () => {
				if (R.canvasWrapper) {
					R.canvasWrapper.classList.toggle('ratio-square');
					const isSquare = R.canvasWrapper.classList.contains('ratio-square');
					canvas.setDimensions({
						width: isSquare ? 400 : 320,
						height: isSquare ? 400 : 568
					});
				}
			});
		}

		// Transparent background toggle.
		let transparentBgEnabled = false;
		if (R.transparentBgToggle) {
			R.transparentBgToggle.addEventListener('click', () => {
				transparentBgEnabled = !transparentBgEnabled;
				R.transparentBgToggle.classList.toggle('beach-toggle--active', transparentBgEnabled);
				if (R.canvasWrapper) {
					if (transparentBgEnabled) {
						R.canvasWrapper.style.backgroundImage = 'linear-gradient(45deg, #ccc 25%, transparent 25%), linear-gradient(-45deg, #ccc 25%, transparent 25%), linear-gradient(45deg, transparent 75%, #ccc 75%), linear-gradient(-45deg, transparent 75%, #ccc 75%)';
						R.canvasWrapper.style.backgroundSize = '20px 20px';
						R.canvasWrapper.style.backgroundPosition = '0 0, 0 10px, 10px -10px, -10px 0px';
					} else {
						R.canvasWrapper.style.backgroundImage = 'none';
					}
				}
			});
		}

		// Download button.
		if (R.downloadBtn) {
			R.downloadBtn.addEventListener('click', () => {
				canvas.discardActiveObject();
				canvas.renderAll();

				R.downloadBtn.disabled = true;
				const originalIcon = R.downloadBtn.innerHTML;
				R.downloadBtn.innerHTML = '<i class="ri-loader-4-line" style="animation: spin 1s linear infinite;"></i>';

				const originalBg = canvas.backgroundColor;

				try {
					if (transparentBgEnabled) {
						canvas.setBackgroundColor(null, canvas.renderAll.bind(canvas));
					}

					setTimeout(() => {
						const dataURL = canvas.toDataURL({
							format: 'png',
							quality: 1,
							multiplier: 6,
							enableRetinaScaling: true
						});

						// Save to server via AJAX
						if (window.planoRest && window.planoRest.ajax_url) {
							const formData = new FormData();
							formData.append('action', 'apollo_save_canvas');
							formData.append('nonce', planoRest.nonce);
							formData.append('data_url', dataURL);

							fetch(planoRest.ajax_url, {
								method: 'POST',
								body: formData
							})
								.then(res => res.json())
								.then(data => {
									if (data.success) {
										// Also download locally
										const link = document.createElement('a');
										link.href = dataURL;
										link.download = transparentBgEnabled
											? `apollo-transparent-4k-${Date.now()}.png`
											: `apollo-4k-${Date.now()}.png`;
										document.body.appendChild(link);
										link.click();
										document.body.removeChild(link);

										// Show success message
										if (window.plano_i18n && window.plano_i18n.saved) {
											alert(window.plano_i18n.saved);
										} else {
											alert('Canvas salvo com sucesso!');
										}
									} else {
										throw new Error(data.data?.message || 'Erro ao salvar');
									}
								})
								.catch(err => {
									console.error('Save failed:', err);
									// Fallback to local download only
									const link = document.createElement('a');
									link.href = dataURL;
									link.download = transparentBgEnabled
										? `apollo-transparent-4k-${Date.now()}.png`
										: `apollo-4k-${Date.now()}.png`;
									document.body.appendChild(link);
									link.click();
									document.body.removeChild(link);
								})
								.finally(() => {
									if (transparentBgEnabled && originalBg) {
										canvas.setBackgroundColor(originalBg, canvas.renderAll.bind(canvas));
									}
									R.downloadBtn.innerHTML = originalIcon;
									R.downloadBtn.disabled = false;
								});
						} else {
							// Fallback to local download only
							const link = document.createElement('a');
							link.href = dataURL;
							link.download = transparentBgEnabled
								? `apollo-transparent-4k-${Date.now()}.png`
								: `apollo-4k-${Date.now()}.png`;
							document.body.appendChild(link);
							link.click();
							document.body.removeChild(link);

							if (transparentBgEnabled && originalBg) {
								canvas.setBackgroundColor(originalBg, canvas.renderAll.bind(canvas));
							}

							R.downloadBtn.innerHTML = originalIcon;
							R.downloadBtn.disabled = false;
						}
					}, 100);
				} catch (error) {
					console.error('Export failed:', error);
					if (transparentBgEnabled && originalBg) {
						canvas.setBackgroundColor(originalBg, canvas.renderAll.bind(canvas));
					}
					R.downloadBtn.innerHTML = originalIcon;
					R.downloadBtn.disabled = false;
					if (window.plano_i18n && window.plano_i18n.error) {
						alert(window.plano_i18n.error);
					} else {
						alert('Erro ao exportar imagem. Tente novamente.');
					}
				}
			});
		}

		// Keyboard shortcuts.
		document.addEventListener('keydown', function(e) {
			// Undo (Ctrl+Z)
			if (e.ctrlKey && e.key === 'z' && !e.shiftKey) {
				e.preventDefault();
				HistoryManager.undo();
				return;
			}
			// Redo (Ctrl+Y or Ctrl+Shift+Z)
			if ((e.ctrlKey && e.key === 'y') || (e.ctrlKey && e.shiftKey && e.key === 'z')) {
				e.preventDefault();
				HistoryManager.redo();
				return;
			}
			// Delete
			if (e.key === 'Delete') {
				const activeObject = canvas.getActiveObject();
				if (activeObject) {
					canvas.remove(activeObject);
					canvas.renderAll();
				}
			}
		});

		// Close popups on outside click.
		document.addEventListener('click', (e) => {
			const inside = e.target.closest('.ap-plano-side-menu') ||
				e.target.closest('.ap-plano-slide-panel') ||
				e.target.closest('.ap-plano-element-controls') ||
				e.target.closest('.ap-plano-control-popup') ||
				e.target.closest('.ap-plano-modal') ||
				e.target.closest('.ap-plano-top-menu') ||
				e.target.closest('canvas');
			if (!inside) {
				if (R.buildPanel) R.buildPanel.classList.remove('active');
				closeAllPopups();
			}
		});

		// Library modal
		const libraryBtn = document.getElementById('ap-plano-library-btn');
		const libraryModal = document.getElementById('ap-plano-library-modal');
		const closeLibrary = document.getElementById('ap-plano-close-library');

		if (libraryBtn && libraryModal) {
			libraryBtn.addEventListener('click', () => {
				libraryModal.classList.remove('hidden');
				loadLibraryContent();
			});
		}

		if (closeLibrary && libraryModal) {
			closeLibrary.addEventListener('click', () => {
				libraryModal.classList.add('hidden');
			});
		}

		// Library tab switching
		const libraryTabs = document.querySelectorAll('.ap-plano-library-tab');
		libraryTabs.forEach(tab => {
			tab.addEventListener('click', () => {
				const targetPanel = tab.dataset.tab;
				
				// Update tab states
				libraryTabs.forEach(t => t.classList.remove('active'));
				tab.classList.add('active');
				
				// Update panel states
				document.querySelectorAll('.ap-plano-library-tab-panel').forEach(p => {
					p.classList.remove('active');
				});
				const panel = document.querySelector(`.ap-plano-library-tab-panel[data-panel="${targetPanel}"]`);
				if (panel) {
					panel.classList.add('active');
					loadLibrarySubtab(targetPanel, panel.querySelector('.ap-plano-library-subtab.active')?.dataset.subtab || '');
				}
			});
		});

		// Library subtab switching
		document.querySelectorAll('.ap-plano-library-subtab').forEach(subtab => {
			subtab.addEventListener('click', () => {
				const panel = subtab.closest('.ap-plano-library-tab-panel');
				const targetSubtab = subtab.dataset.subtab;
				
				// Update subtab states
				panel.querySelectorAll('.ap-plano-library-subtab').forEach(st => st.classList.remove('active'));
				subtab.classList.add('active');
				
				// Load content
				const panelType = panel.dataset.panel;
				loadLibrarySubtab(panelType, targetSubtab);
			});
		});

		// Library functions
		function loadLibraryContent() {
			const activePanel = document.querySelector('.ap-plano-library-tab-panel.active');
			if (activePanel) {
				const panelType = activePanel.dataset.panel;
				const activeSubtab = activePanel.querySelector('.ap-plano-library-subtab.active')?.dataset.subtab || '';
				loadLibrarySubtab(panelType, activeSubtab);
			}
		}

		function loadLibrarySubtab(panelType, subtab) {
			if (!window.planoRest) return;

			switch (panelType) {
				case 'bg':
					if (subtab === 'gradient') {
						loadGradients();
					} else if (subtab === 'texture') {
						loadTextures('bg');
					}
					break;
				case 'elements':
					if (subtab === 'stickers') {
						loadStickers();
					} else if (subtab === 'texture') {
						loadTextures('elements');
					}
					break;
				case 'effects':
					if (subtab === 'texture') {
						loadTextures('effects');
					}
					break;
			}
		}

		function loadTextures(context) {
			if (!window.planoRest) return;

			const container = document.getElementById('ap-plano-effects-content');
			if (!container) return;

			fetch(`${planoRest.rest_url}textures`)
				.then(res => res.json())
				.then(data => {
					container.innerHTML = '';
					const grid = document.createElement('div');
					grid.className = 'ap-plano-texture-grid';

					if (data.textures && data.textures.length > 0) {
						data.textures.forEach(file => {
							const item = document.createElement('div');
							item.className = 'ap-plano-texture-item';
							const img = document.createElement('img');
							img.src = `${data.base_url}${file}`;
							img.alt = file;
							img.style.aspectRatio = '9/16';
							img.style.transform = 'scale(0.2)';
							img.style.width = '100%';
							img.style.height = 'auto';
							
							const preview = document.createElement('div');
							preview.className = 'ap-plano-texture-preview';
							preview.textContent = 'Clique para aplicar';
							
							item.appendChild(img);
							item.appendChild(preview);
							
							item.addEventListener('click', () => {
								applyTexture(`${data.base_url}${file}`);
							});
							
							grid.appendChild(item);
						});
					} else {
						grid.innerHTML = '<p style="grid-column: 1/-1; text-align: center; color: #999;">Nenhuma textura encontrada</p>';
					}

					container.appendChild(grid);
				})
				.catch(err => {
					console.error('Erro ao carregar texturas:', err);
				});
		}

		function loadStickers() {
			if (!window.planoRest) return;

			const container = document.getElementById('ap-plano-elements-content');
			if (!container) return;

			fetch(`${planoRest.rest_url}stickers`)
				.then(res => res.json())
				.then(data => {
					container.innerHTML = '';
					if (data.count === 0) {
						container.innerHTML = '<p style="text-align: center; color: #999; padding: 40px;">Biblioteca de Adesivos vazia (0 itens)</p>';
					}
				})
				.catch(err => {
					console.error('Erro ao carregar adesivos:', err);
				});
		}

		function loadGradients() {
			const container = document.getElementById('ap-plano-bg-content');
			if (!container) return;

			const gradients = [
				{ name: 'Sunset', value: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)' },
				{ name: 'Ocean', value: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)' },
				{ name: 'Forest', value: 'linear-gradient(135deg, #11998e 0%, #38ef7d 100%)' },
				{ name: 'Warm', value: 'linear-gradient(135deg, #f093fb 0%, #f5576c 100%)' },
				{ name: 'Cool', value: 'linear-gradient(135deg, #4facfe 0%, #00f2fe 100%)' },
			];

			container.innerHTML = '';
			const grid = document.createElement('div');
			grid.className = 'ap-plano-texture-grid';

			gradients.forEach(grad => {
				const item = document.createElement('div');
				item.className = 'ap-plano-gradient-item';
				item.style.background = grad.value;
				item.title = grad.name;
				item.addEventListener('click', () => {
					canvas.setBackgroundColor(grad.value, canvas.renderAll.bind(canvas));
					if (libraryModal) libraryModal.classList.add('hidden');
				});
				grid.appendChild(item);
			});

			container.appendChild(grid);
		}

		function applyTexture(url) {
			fabric.Image.fromURL(url, img => {
				// Remove previous texture overlay
				const objects = canvas.getObjects();
				objects.forEach(obj => {
					if (obj.isTextureOverlay) {
						canvas.remove(obj);
					}
				});

				img.set({
					opacity: 0.5,
					globalCompositeOperation: 'multiply',
					isTextureOverlay: true,
					selectable: false,
					evented: false
				});
				img.scaleToWidth(canvas.width);
				img.scaleToHeight(canvas.height);
				canvas.add(img);
				canvas.sendToBack(img);
				canvas.renderAll();
				
				if (libraryModal) libraryModal.classList.add('hidden');
			});
		}

		// Load post by ID
		const loadPostBtn = document.getElementById('ap-plano-load-post-btn');
		const postIdInput = document.getElementById('ap-plano-post-id-input');
		const postPreview = document.getElementById('ap-plano-post-preview');

		if (loadPostBtn && postIdInput) {
			loadPostBtn.addEventListener('click', () => {
				const postId = parseInt(postIdInput.value);
				const activeSubtab = document.querySelector('#ap-plano-posts-content .ap-plano-library-subtab.active')?.dataset.subtab;
				
				if (!postId || !activeSubtab) return;

				const postTypeMap = {
					'classifieds': 'anuncio',
					'events': 'event_listing',
					'dj': 'event_dj',
					'local': 'event_local'
				};

				const postType = postTypeMap[activeSubtab];
				if (!postType) return;

				if (!window.planoRest) return;

				fetch(`${planoRest.rest_url}posts/${postType}/${postId}`, {
					headers: {
						'X-WP-Nonce': planoRest.nonce
					}
				})
					.then(res => res.json())
					.then(data => {
						if (data.error) {
							postPreview.innerHTML = `<p style="color: red;">${data.message || 'Erro ao carregar post'}</p>`;
							return;
						}

						// Render post card
						let cardHtml = '<div class="ap-plano-post-card" style="border: 1px solid #ddd; border-radius: 8px; padding: 16px; margin-top: 12px;">';
						cardHtml += `<h4>${data.title}</h4>`;
						if (data.description) cardHtml += `<p>${data.description}</p>`;
						if (data.image) cardHtml += `<img src="${data.image}" style="max-width: 100%; border-radius: 4px; margin-top: 8px;">`;
						cardHtml += '<button class="ap-plano-btn-primary" style="margin-top: 12px;">Aplicar ao Canvas</button>';
						cardHtml += '</div>';

						postPreview.innerHTML = cardHtml;

						// Add apply button handler
						const applyBtn = postPreview.querySelector('button');
						if (applyBtn) {
							applyBtn.addEventListener('click', () => {
								// TODO: Render post as card on canvas
								console.log('Aplicar post ao canvas:', data);
								if (libraryModal) libraryModal.classList.add('hidden');
							});
						}
					})
					.catch(err => {
						console.error('Erro ao carregar post:', err);
						postPreview.innerHTML = '<p style="color: red;">Erro ao carregar post</p>';
					});
			});
		}

		console.log('[Apollo Plano] Editor inicializado.');
	});

})();

