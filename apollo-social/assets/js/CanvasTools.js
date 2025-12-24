/**
 * Apollo Canvas Tools - Modular Tool Panel
 *
 * Reusable tool panel for Fabric.js canvas editors
 * Can be used in Plano editor, profile builder, HUB, etc.
 *
 * @package Apollo_Social
 */

(function() {
	'use strict';

	/**
	 * CanvasTools Class
	 * Manages tool panels and controls for Fabric.js canvas
	 */
	class CanvasTools {
		/**
		 * Constructor
		 *
		 * @param {Object} options Configuration options
		 * @param {fabric.Canvas} options.canvas Fabric.js canvas instance
		 * @param {HTMLElement} options.container Container element for tools
		 */
		constructor(options) {
			if (!options.canvas || !options.container) {
				throw new Error('CanvasTools requires canvas and container options');
			}

			this.canvas = options.canvas;
			this.container = options.container;
			this.activeObject = null;

			this.init();
		}

		/**
		 * Initialize all tools
		 */
		init() {
			this.initCanvasEvents();
			this.initTextTool();
			this.initImageTool();
			this.initShapeTool();
			this.initFilterTool();
			this.initLayerControls();
			this.initDeleteControl();
		}

		/**
		 * Initialize canvas event listeners
		 */
		initCanvasEvents() {
			this.canvas.on('selection:created', () => this.onSelectionChanged());
			this.canvas.on('selection:updated', () => this.onSelectionChanged());
			this.canvas.on('selection:cleared', () => this.onSelectionCleared());
			this.canvas.on('object:modified', () => this.onObjectModified());
		}

		/**
		 * Handle selection changed
		 */
		onSelectionChanged() {
			this.activeObject = this.canvas.getActiveObject();
			if (this.activeObject) {
				this.updateControls();
			}
		}

		/**
		 * Handle selection cleared
		 */
		onSelectionCleared() {
			this.activeObject = null;
			this.hideControls();
		}

		/**
		 * Handle object modified
		 */
		onObjectModified() {
			if (this.activeObject) {
				this.updateControls();
			}
		}

		/**
		 * Initialize text tool
		 */
		initTextTool() {
			const addTextBtn = document.getElementById('ap-plano-add-text-btn');
			if (addTextBtn) {
				addTextBtn.addEventListener('click', () => {
					const text = new fabric.IText('Apollo Rio', {
						left: 80,
						top: 100,
						fontSize: 28,
						fill: '#000',
						fontFamily: 'system-ui',
						fontWeight: 500
					});
					this.canvas.add(text);
					this.canvas.setActiveObject(text);
					this.canvas.renderAll();
				});
			}
		}

		/**
		 * Initialize image tool
		 */
		initImageTool() {
			const addImageBtn = document.getElementById('ap-plano-add-image-btn');
			const imageUpload = document.getElementById('ap-plano-image-upload');

			if (addImageBtn && imageUpload) {
				addImageBtn.addEventListener('click', () => imageUpload.click());
				imageUpload.addEventListener('change', (e) => {
					if (e.target.files && e.target.files[0]) {
						const reader = new FileReader();
						reader.onload = (ev) => {
							fabric.Image.fromURL(ev.target.result, (img) => {
								img.scaleToWidth(150);
								img.set({ left: 60, top: 200 });
								this.canvas.add(img);
								this.canvas.setActiveObject(img);
								this.canvas.renderAll();
							});
						};
						reader.readAsDataURL(e.target.files[0]);
					}
				});
			}
		}

		/**
		 * Initialize shape tool
		 */
		initShapeTool() {
			const addBoxBtn = document.getElementById('ap-plano-add-box-btn');
			if (addBoxBtn) {
				addBoxBtn.addEventListener('click', () => {
					const rect = new fabric.Rect({
						left: 80,
						top: 150,
						width: 150,
						height: 150,
						fill: '#ffffff',
						rx: 0,
						ry: 0
					});
					this.canvas.add(rect);
					this.canvas.setActiveObject(rect);
					this.canvas.renderAll();
				});
			}
		}

		/**
		 * Initialize filter tool
		 */
		initFilterTool() {
			// Filters will be implemented in Phase 4
			// Placeholder for now
		}

		/**
		 * Initialize layer controls
		 */
		initLayerControls() {
			const layerUpBtn = document.getElementById('ap-plano-layer-up-btn');
			const layerDownBtn = document.getElementById('ap-plano-layer-down-btn');

			if (layerUpBtn) {
				layerUpBtn.addEventListener('click', () => {
					const obj = this.canvas.getActiveObject();
					if (obj) {
						this.canvas.bringForward(obj);
						this.canvas.renderAll();
					}
				});
			}

			if (layerDownBtn) {
				layerDownBtn.addEventListener('click', () => {
					const obj = this.canvas.getActiveObject();
					if (obj) {
						this.canvas.sendBackwards(obj);
						this.canvas.renderAll();
					}
				});
			}
		}

		/**
		 * Initialize delete control
		 */
		initDeleteControl() {
			const deleteBtn = document.getElementById('ap-plano-delete-btn');
			if (deleteBtn) {
				deleteBtn.addEventListener('click', () => {
					const obj = this.canvas.getActiveObject();
					if (obj) {
						this.canvas.remove(obj);
						this.canvas.renderAll();
					}
				});
			}
		}

		/**
		 * Update controls based on active object
		 */
		updateControls() {
			const obj = this.activeObject;
			if (!obj) return;

			const elementControls = document.getElementById('ap-plano-element-controls');
			if (elementControls) {
				elementControls.classList.add('active');
			}

			const isText = obj.type === 'i-text' || obj.type === 'textbox';
			const isImage = obj.type === 'image';
			const isShape = obj.type === 'rect' || obj.type === 'circle';

			// Remove all type classes
			if (elementControls) {
				elementControls.classList.remove('text-active', 'box-active', 'image-active');
			}

			if (isText) {
				if (elementControls) elementControls.classList.add('text-active');
			} else if (isImage) {
				if (elementControls) elementControls.classList.add('image-active');
			} else {
				if (elementControls) elementControls.classList.add('box-active');
			}
		}

		/**
		 * Hide controls
		 */
		hideControls() {
			const elementControls = document.getElementById('ap-plano-element-controls');
			if (elementControls) {
				elementControls.classList.remove('active', 'text-active', 'box-active', 'image-active');
			}
		}
	}

	// Export for global use
	window.CanvasTools = CanvasTools;

})();

