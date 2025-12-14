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

		// Add text button.
		if (R.addTextBtn) {
			R.addTextBtn.addEventListener('click', () => {
				const text = new fabric.IText('Apollo Rio', {
					left: 80,
					top: 100,
					fontSize: 28,
					fill: '#000',
					fontFamily: 'system-ui',
					fontWeight: 500
				});
				canvas.add(text);
				canvas.setActiveObject(text);
				canvas.renderAll();
				if (R.buildPanel) R.buildPanel.classList.remove('active');
			});
		}

		// Add box button.
		if (R.addBoxBtn) {
			R.addBoxBtn.addEventListener('click', () => {
				const rect = new fabric.Rect({
					left: 80,
					top: 150,
					width: 150,
					height: 150,
					fill: '#ffffff',
					rx: 0,
					ry: 0
				});
				canvas.add(rect);
				canvas.setActiveObject(rect);
				canvas.renderAll();
				if (R.buildPanel) R.buildPanel.classList.remove('active');
			});
		}

		// Add image button.
		if (R.addImageBtn && R.imageUpload) {
			R.addImageBtn.addEventListener('click', () => R.imageUpload.click());
			R.imageUpload.addEventListener('change', (e) => {
				if (e.target.files && e.target.files[0]) {
					const reader = new FileReader();
					reader.onload = (ev) => {
						fabric.Image.fromURL(ev.target.result, (img) => {
							img.scaleToWidth(150);
							img.set({ left: 60, top: 200 });
							canvas.add(img);
							canvas.setActiveObject(img);
							canvas.renderAll();
						});
					};
					reader.readAsDataURL(e.target.files[0]);
				}
				if (R.buildPanel) R.buildPanel.classList.remove('active');
			});
		}

		// Delete button.
		if (R.deleteBtn) {
			R.deleteBtn.addEventListener('click', () => {
				const obj = canvas.getActiveObject();
				if (obj) {
					canvas.remove(obj);
					canvas.renderAll();
				}
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
					}, 100);
				} catch (error) {
					console.error('Export failed:', error);
					if (transparentBgEnabled && originalBg) {
						canvas.setBackgroundColor(originalBg, canvas.renderAll.bind(canvas));
					}
					R.downloadBtn.innerHTML = originalIcon;
					R.downloadBtn.disabled = false;
					alert('Erro ao exportar imagem. Tente novamente.');
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

		console.log('[Apollo Plano] Editor inicializado.');
	});

})();

