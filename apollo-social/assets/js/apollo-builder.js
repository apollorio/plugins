/**
 * Apollo Builder - Main JavaScript
 *
 * Pattern source: WOW Page Builder engine.js + Live Composer builder.frontend.all.min.js
 *
 * Features:
 * - Drag & drop widget placement
 * - Grid snapping (24px)
 * - Widget resize
 * - AJAX save layout
 * - Undo/Redo support
 *
 * @package Apollo_Social
 * @since 1.4.0
 */

(function($) {
    'use strict';

    // Config from PHP
    const config = window.apolloBuilderConfig || {};

    // State
    let layout = config.currentLayout || { widgets: [] };
    let selectedWidgetId = null;
    let isDragging = false;
    let isResizing = false;
    let hasUnsavedChanges = false;

    // Undo/Redo history
    const history = {
        states: [],
        currentIndex: -1,
        maxStates: 30,

        push(state) {
            // Remove future states
            if (this.currentIndex < this.states.length - 1) {
                this.states = this.states.slice(0, this.currentIndex + 1);
            }

            // Add new state
            this.states.push(JSON.parse(JSON.stringify(state)));

            // Limit history
            if (this.states.length > this.maxStates) {
                this.states.shift();
            } else {
                this.currentIndex++;
            }

            this.updateButtons();
        },

        undo() {
            if (this.currentIndex > 0) {
                this.currentIndex--;
                return JSON.parse(JSON.stringify(this.states[this.currentIndex]));
            }
            return null;
        },

        redo() {
            if (this.currentIndex < this.states.length - 1) {
                this.currentIndex++;
                return JSON.parse(JSON.stringify(this.states[this.currentIndex]));
            }
            return null;
        },

        updateButtons() {
            $('.btn-undo').prop('disabled', this.currentIndex <= 0);
            $('.btn-redo').prop('disabled', this.currentIndex >= this.states.length - 1);
        }
    };

    // Grid snapping
    const GRID_SIZE = config.gridSize || 24;

    function snapToGrid(value) {
        return Math.round(value / GRID_SIZE) * GRID_SIZE;
    }

    // Initialize
    function init() {
        if (!config.homePostId) {
            console.error('Apollo Builder: No home post ID');
            return;
        }

        // Save initial state
        history.push(layout);

        // Render
        renderWidgetPalette();
        renderCanvas();
        renderTextures();
        renderStickers();
        renderStickersOnCanvas(); // Render existing stickers from layout

        // Events
        bindEvents();

        // Set initial background
        if (config.currentBg) {
            setCanvasBackground(config.currentBg);
        }

        // Set initial trax URL
        if (config.currentTrax) {
            $('#trax-url-input').val(config.currentTrax);
        }

        console.log('Apollo Builder initialized', config);
    }

    // Render widget palette
    function renderWidgetPalette() {
        const $palette = $('#widgets-palette');
        $palette.empty();

        // Widget types (will come from AJAX in production)
        const widgetTypes = [
            { name: 'profile-card', title: config.i18n?.profileCard || 'Profile Card', icon: 'dashicons dashicons-admin-users', tooltip: 'Your profile info', canDelete: false },
            { name: 'badges', title: config.i18n?.badges || 'Badges', icon: 'dashicons dashicons-awards', tooltip: 'Membership badges' },
            { name: 'groups', title: config.i18n?.groups || 'Groups', icon: 'dashicons dashicons-groups', tooltip: 'Your communities' },
            { name: 'guestbook', title: config.i18n?.guestbook || 'Depoimentos', icon: 'dashicons dashicons-format-status', tooltip: 'Visitor messages' },
            { name: 'trax-player', title: config.i18n?.traxPlayer || 'Trax Player', icon: 'dashicons dashicons-format-audio', tooltip: 'Music player' },
            { name: 'note', title: config.i18n?.note || 'Sticky Note', icon: 'dashicons dashicons-edit', tooltip: 'Text note' },
        ];

        // Add stickers section
        if (config.stickers && config.stickers.length) {
            widgetTypes.push({
                name: 'sticker',
                title: config.i18n?.sticker || 'Sticker',
                icon: 'dashicons dashicons-smiley',
                tooltip: 'Decorative sticker',
                hasVariants: true
            });
        }

        widgetTypes.forEach(widget => {
            const $item = $(`
                <div class="widget-palette-item"
                     data-widget-type="${widget.name}"
                     draggable="true"
                     title="${widget.tooltip || ''}">
                    <span class="widget-icon ${widget.icon}"></span>
                    <span class="widget-label">${widget.title}</span>
                </div>
            `);

            $palette.append($item);
        });
    }

    // Render canvas with widgets
    function renderCanvas() {
        const $canvas = $('#canvas-board');
        $canvas.empty();

        if (!layout.widgets || !layout.widgets.length) {
            $canvas.append(`<div class="canvas-empty">${config.i18n?.empty || 'Drag widgets here'}</div>`);
            return;
        }

        layout.widgets.forEach(widget => {
            const $widget = createWidgetElement(widget);
            $canvas.append($widget);
        });
    }

    // Create widget DOM element
    function createWidgetElement(widget) {
        const widgetInfo = getWidgetInfo(widget.type);
        const canDelete = widget.type !== 'profile-card';

        const $widget = $(`
            <div class="canvas-widget ${selectedWidgetId === widget.id ? 'selected' : ''}"
                 data-widget-id="${widget.id}"
                 data-widget-type="${widget.type}"
                 style="left:${widget.x}px;top:${widget.y}px;width:${widget.width}px;height:${widget.height}px;z-index:${widget.zIndex};">
                <div class="widget-header">
                    <span class="widget-title">${widgetInfo.title}</span>
                    ${canDelete ? `<button type="button" class="widget-delete" title="${config.i18n?.delete || 'Remove'}"><span class="dashicons dashicons-trash"></span></button>` : ''}
                </div>
                <div class="widget-content">
                    <span class="widget-icon ${widgetInfo.icon}"></span>
                    <span class="widget-preview">${widgetInfo.title}</span>
                </div>
                <div class="widget-resize-handle"></div>
            </div>
        `);

        return $widget;
    }

    // Get widget info
    function getWidgetInfo(type) {
        const info = {
            'profile-card': { title: 'Profile Card', icon: 'dashicons dashicons-admin-users' },
            'badges': { title: 'Badges', icon: 'dashicons dashicons-awards' },
            'groups': { title: 'Groups', icon: 'dashicons dashicons-groups' },
            'guestbook': { title: 'Depoimentos', icon: 'dashicons dashicons-format-status' },
            'trax-player': { title: 'Trax Player', icon: 'dashicons dashicons-format-audio' },
            'sticker': { title: 'Sticker', icon: 'dashicons dashicons-smiley' },
            'note': { title: 'Note', icon: 'dashicons dashicons-edit' },
        };

        return info[type] || { title: type, icon: 'dashicons dashicons-admin-generic' };
    }

    // Render texture selector
    function renderTextures() {
        const $grid = $('#texture-grid');
        $grid.empty();

        // Add "none" option
        $grid.append(`
            <div class="texture-item ${!config.currentBg ? 'selected' : ''}" data-texture-id="" title="No background">
                <span class="dashicons dashicons-no"></span>
            </div>
        `);

        if (config.textures && config.textures.length) {
            config.textures.forEach(texture => {
                const isSelected = config.currentBg === texture.id;
                $grid.append(`
                    <div class="texture-item ${isSelected ? 'selected' : ''}"
                         data-texture-id="${texture.id}"
                         title="${texture.label || ''}">
                        <img src="${texture.thumbUrl || texture.imageUrl}" alt="${texture.label || ''}">
                    </div>
                `);
            });
        }
    }

    // Render stickers palette
    function renderStickers() {
        const $palette = $('#stickers-palette');
        const $tabs = $('#stickers-category-tabs');

        if (!config.stickers || !config.stickers.length) {
            $palette.html('<p class="empty-message">' + (config.i18n?.noStickers || 'No stickers available') + '</p>');
            return;
        }

        // Group stickers by category
        const categories = {};
        config.stickers.forEach(sticker => {
            const cat = sticker.category || 'general';
            if (!categories[cat]) {
                categories[cat] = [];
            }
            categories[cat].push(sticker);
        });

        // Render category tabs
        $tabs.empty();
        const catKeys = Object.keys(categories);
        catKeys.forEach((cat, index) => {
            const isActive = index === 0;
            $tabs.append(`
                <button type="button"
                        class="sticker-tab ${isActive ? 'active' : ''}"
                        data-category="${cat}"
                        role="tab"
                        aria-selected="${isActive}">
                    ${cat.charAt(0).toUpperCase() + cat.slice(1)}
                </button>
            `);
        });

        // Render stickers grid
        $palette.empty();
        const firstCat = catKeys[0];
        renderStickerCategory(categories[firstCat] || []);

        // Tab switching
        $tabs.off('click', '.sticker-tab').on('click', '.sticker-tab', function() {
            const cat = $(this).data('category');
            $tabs.find('.sticker-tab').removeClass('active').attr('aria-selected', 'false');
            $(this).addClass('active').attr('aria-selected', 'true');
            renderStickerCategory(categories[cat] || []);
        });
    }

    // Render stickers for a specific category
    function renderStickerCategory(stickers) {
        const $palette = $('#stickers-palette');
        $palette.empty();

        stickers.forEach(sticker => {
            $palette.append(`
                <div class="sticker-item"
                     data-sticker-id="${sticker.id}"
                     data-sticker-url="${sticker.url}"
                     draggable="true"
                     title="${sticker.label || sticker.name || ''}">
                    <img src="${sticker.thumb || sticker.url}" alt="${sticker.label || ''}" loading="lazy">
                </div>
            `);
        });

        // Bind drag events for stickers
        $palette.find('.sticker-item').on('dragstart', function(e) {
            const stickerUrl = $(this).data('sticker-url');
            const stickerId = $(this).data('sticker-id');
            e.originalEvent.dataTransfer.setData('application/x-sticker', JSON.stringify({
                id: stickerId,
                url: stickerUrl,
                type: 'sticker'
            }));
            $(this).addClass('dragging');
        }).on('dragend', function() {
            $(this).removeClass('dragging');
        });
    }

    // Set canvas background
    function setCanvasBackground(textureId) {
        const $canvas = $('#canvas-board');

        if (!textureId) {
            $canvas.css('background-image', 'none');
            return;
        }

        const texture = config.textures?.find(t => t.id === textureId);
        if (texture) {
            $canvas.css('background-image', `url(${texture.imageUrl})`);
        }
    }

    // Bind events
    function bindEvents() {
        // Palette drag start
        $('#widgets-palette').on('dragstart', '.widget-palette-item', function(e) {
            e.originalEvent.dataTransfer.setData('text/plain', $(this).data('widget-type'));
            $(this).addClass('dragging');
        });

        $('#widgets-palette').on('dragend', '.widget-palette-item', function() {
            $(this).removeClass('dragging');
        });

        // Canvas drop
        const $canvas = $('#canvas-board');

        $canvas.on('dragover', function(e) {
            e.preventDefault();
            $(this).addClass('drag-over');
        });

        $canvas.on('dragleave', function() {
            $(this).removeClass('drag-over');
        });

        $canvas.on('drop', function(e) {
            e.preventDefault();
            $(this).removeClass('drag-over');

            const offset = $canvas.offset();
            const x = snapToGrid(e.pageX - offset.left);
            const y = snapToGrid(e.pageY - offset.top);

            // Check for sticker drop
            const stickerData = e.originalEvent.dataTransfer.getData('application/x-sticker');
            if (stickerData) {
                try {
                    const sticker = JSON.parse(stickerData);
                    addSticker(sticker, x, y);
                    return;
                } catch (err) {
                    console.error('Error parsing sticker data:', err);
                }
            }

            // Regular widget drop
            const type = e.originalEvent.dataTransfer.getData('text/plain');
            if (!type) return;

            addWidget(type, x, y);
        });

        // Widget selection
        $canvas.on('click', '.canvas-widget', function(e) {
            e.stopPropagation();
            selectWidget($(this).data('widget-id'));
        });

        // Canvas click deselect
        $canvas.on('click', function(e) {
            if ($(e.target).is($canvas) || $(e.target).hasClass('canvas-empty')) {
                selectWidget(null);
            }
        });

        // Widget delete
        $canvas.on('click', '.widget-delete', function(e) {
            e.stopPropagation();
            const widgetId = $(this).closest('.canvas-widget').data('widget-id');
            if (confirm(config.i18n?.confirm_delete || 'Remove this widget?')) {
                removeWidget(widgetId);
            }
        });

        // Widget drag (move)
        let dragOffsetX, dragOffsetY, $dragWidget;

        $canvas.on('mousedown', '.canvas-widget', function(e) {
            if ($(e.target).hasClass('widget-delete') || $(e.target).closest('.widget-delete').length) return;
            if ($(e.target).hasClass('widget-resize-handle')) return;

            e.preventDefault(); // Prevent text selection during drag

            $dragWidget = $(this);
            isDragging = true;

            // Get widget's offset relative to canvas
            const canvasOffset = $canvas.offset();
            const widgetOffset = $dragWidget.offset();

            // Calculate where mouse clicked relative to widget's top-left corner
            dragOffsetX = e.pageX - widgetOffset.left;
            dragOffsetY = e.pageY - widgetOffset.top;

            $dragWidget.addClass('dragging');
            selectWidget($dragWidget.data('widget-id'));
        });

        $(document).on('mousemove', function(e) {
            if (!isDragging || !$dragWidget) return;

            const canvasOffset = $canvas.offset();

            // Calculate new position relative to canvas
            let x = e.pageX - canvasOffset.left - dragOffsetX;
            let y = e.pageY - canvasOffset.top - dragOffsetY;

            // Bounds
            x = Math.max(0, Math.min(x, $canvas.width() - $dragWidget.outerWidth()));
            y = Math.max(0, Math.min(y, $canvas.height() - $dragWidget.outerHeight()));

            // Snap
            x = snapToGrid(x);
            y = snapToGrid(y);

            $dragWidget.css({ left: x, top: y });
        });

        $(document).on('mouseup', function() {
            if (isDragging && $dragWidget) {
                $dragWidget.removeClass('dragging');

                const widgetId = $dragWidget.data('widget-id');
                const newX = parseInt($dragWidget.css('left'));
                const newY = parseInt($dragWidget.css('top'));

                updateWidgetPosition(widgetId, newX, newY);
            }

            isDragging = false;
            $dragWidget = null;
        });

        // Widget resize
        let $resizeWidget, resizeStartX, resizeStartY, resizeStartW, resizeStartH;

        $canvas.on('mousedown', '.widget-resize-handle', function(e) {
            e.stopPropagation();

            $resizeWidget = $(this).closest('.canvas-widget');
            isResizing = true;

            resizeStartX = e.clientX;
            resizeStartY = e.clientY;
            resizeStartW = $resizeWidget.width();
            resizeStartH = $resizeWidget.height();

            $resizeWidget.addClass('resizing');
        });

        $(document).on('mousemove.resize', function(e) {
            if (!isResizing || !$resizeWidget) return;

            let newW = resizeStartW + (e.clientX - resizeStartX);
            let newH = resizeStartH + (e.clientY - resizeStartY);

            // Min/max
            newW = Math.max(80, Math.min(newW, 600));
            newH = Math.max(60, Math.min(newH, 500));

            // Snap
            newW = snapToGrid(newW);
            newH = snapToGrid(newH);

            $resizeWidget.css({ width: newW, height: newH });
        });

        $(document).on('mouseup.resize', function() {
            if (isResizing && $resizeWidget) {
                $resizeWidget.removeClass('resizing');

                const widgetId = $resizeWidget.data('widget-id');
                const newW = $resizeWidget.width();
                const newH = $resizeWidget.height();

                updateWidgetSize(widgetId, newW, newH);
            }

            isResizing = false;
            $resizeWidget = null;
        });

        // Texture selection
        $('#texture-grid').on('click', '.texture-item', function() {
            const textureId = $(this).data('texture-id');

            $('.texture-item').removeClass('selected');
            $(this).addClass('selected');

            setCanvasBackground(textureId);
            saveBackground(textureId);
        });

        // Trax URL save
        $('#save-trax').on('click', function() {
            const url = $('#trax-url-input').val().trim();
            saveTraxUrl(url);
        });

        // Save button
        $('#save-layout').on('click', saveLayout);

        // Undo/Redo
        $('.btn-undo').on('click', function() {
            const state = history.undo();
            if (state) {
                layout = state;
                renderCanvas();
            }
        });

        $('.btn-redo').on('click', function() {
            const state = history.redo();
            if (state) {
                layout = state;
                renderCanvas();
            }
        });

        // Keyboard shortcuts
        $(document).on('keydown', function(e) {
            // Ctrl+S to save
            if (e.ctrlKey && e.key === 's') {
                e.preventDefault();
                saveLayout();
            }

            // Ctrl+Z undo
            if (e.ctrlKey && e.key === 'z' && !e.shiftKey) {
                e.preventDefault();
                $('.btn-undo').click();
            }

            // Ctrl+Y or Ctrl+Shift+Z redo
            if ((e.ctrlKey && e.key === 'y') || (e.ctrlKey && e.shiftKey && e.key === 'z')) {
                e.preventDefault();
                $('.btn-redo').click();
            }

            // Delete selected widget
            if (e.key === 'Delete' && selectedWidgetId) {
                const widget = layout.widgets.find(w => w.id === selectedWidgetId);
                if (widget && widget.type !== 'profile-card') {
                    if (confirm(config.i18n?.confirm_delete || 'Remove this widget?')) {
                        removeWidget(selectedWidgetId);
                    }
                }
            }
        });

        // Warn before leaving with unsaved changes
        $(window).on('beforeunload', function() {
            if (hasUnsavedChanges) {
                return 'You have unsaved changes!';
            }
        });
    }

    // Add widget
    function addWidget(type, x, y) {
        const widgetInfo = getWidgetInfo(type);

        const widget = {
            id: 'widget_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9),
            type: type,
            x: x,
            y: y,
            width: 200,
            height: 150,
            zIndex: layout.widgets.length + 1,
            config: {}
        };

        // Default sizes per type
        const defaultSizes = {
            'profile-card': { width: 280, height: 200 },
            'badges': { width: 250, height: 80 },
            'groups': { width: 200, height: 180 },
            'guestbook': { width: 300, height: 350 },
            'trax-player': { width: 300, height: 160 },
            'sticker': { width: 80, height: 80 },
            'note': { width: 150, height: 150 },
        };

        if (defaultSizes[type]) {
            widget.width = defaultSizes[type].width;
            widget.height = defaultSizes[type].height;
        }

        layout.widgets.push(widget);
        history.push(layout);
        hasUnsavedChanges = true;

        renderCanvas();
        selectWidget(widget.id);
    }

    // Add sticker to canvas
    function addSticker(stickerData, x, y) {
        const stickerId = 'sticker_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);

        // Initialize stickers array if not exists
        if (!layout.stickers) {
            layout.stickers = [];
        }

        const sticker = {
            id: stickerId,
            sourceId: stickerData.id,
            url: stickerData.url,
            x: x,
            y: y,
            width: 80,
            height: 80,
            rotation: 0,
            zIndex: layout.stickers.length + 100 // Stickers on top
        };

        layout.stickers.push(sticker);
        history.push(layout);
        hasUnsavedChanges = true;

        renderStickersOnCanvas();
    }

    // Render stickers on canvas
    function renderStickersOnCanvas() {
        const $layer = $('#canvas-stickers-layer');
        $layer.empty();

        if (!layout.stickers || !layout.stickers.length) {
            return;
        }

        layout.stickers.forEach(sticker => {
            const $sticker = $(`
                <div class="canvas-sticker"
                     data-sticker-id="${sticker.id}"
                     style="left:${sticker.x}px;top:${sticker.y}px;width:${sticker.width}px;height:${sticker.height}px;z-index:${sticker.zIndex};transform:rotate(${sticker.rotation || 0}deg);">
                    <img src="${sticker.url}" alt="" draggable="false">
                    <button type="button" class="sticker-delete" title="${config.i18n?.delete || 'Remove'}">
                        <span class="dashicons dashicons-no-alt"></span>
                    </button>
                </div>
            `);
            $layer.append($sticker);
        });

        // Bind sticker events
        bindStickerEvents();
    }

    // Bind sticker drag/delete events
    function bindStickerEvents() {
        const $layer = $('#canvas-stickers-layer');
        let $dragSticker = null;
        let stickerOffsetX = 0;
        let stickerOffsetY = 0;

        // Sticker delete
        $layer.off('click', '.sticker-delete').on('click', '.sticker-delete', function(e) {
            e.stopPropagation();
            const stickerId = $(this).closest('.canvas-sticker').data('sticker-id');
            layout.stickers = layout.stickers.filter(s => s.id !== stickerId);
            history.push(layout);
            hasUnsavedChanges = true;
            renderStickersOnCanvas();
        });

        // Sticker drag
        $layer.off('mousedown', '.canvas-sticker').on('mousedown', '.canvas-sticker', function(e) {
            if ($(e.target).hasClass('sticker-delete') || $(e.target).closest('.sticker-delete').length) return;

            e.preventDefault();
            $dragSticker = $(this);

            const stickerOffset = $dragSticker.offset();
            stickerOffsetX = e.pageX - stickerOffset.left;
            stickerOffsetY = e.pageY - stickerOffset.top;

            $dragSticker.addClass('dragging');
        });

        $(document).off('mousemove.stickerDrag').on('mousemove.stickerDrag', function(e) {
            if (!$dragSticker) return;

            const $canvas = $('#canvas-board');
            const canvasOffset = $canvas.offset();

            let x = e.pageX - canvasOffset.left - stickerOffsetX;
            let y = e.pageY - canvasOffset.top - stickerOffsetY;

            // Bounds
            x = Math.max(0, Math.min(x, $canvas.width() - $dragSticker.outerWidth()));
            y = Math.max(0, Math.min(y, $canvas.height() - $dragSticker.outerHeight()));

            // Snap
            x = snapToGrid(x);
            y = snapToGrid(y);

            $dragSticker.css({ left: x, top: y });
        });

        $(document).off('mouseup.stickerDrag').on('mouseup.stickerDrag', function() {
            if (!$dragSticker) return;

            $dragSticker.removeClass('dragging');

            const stickerId = $dragSticker.data('sticker-id');
            const newX = parseInt($dragSticker.css('left'));
            const newY = parseInt($dragSticker.css('top'));

            // Update sticker position in layout
            const sticker = layout.stickers.find(s => s.id === stickerId);
            if (sticker) {
                sticker.x = newX;
                sticker.y = newY;
                history.push(layout);
                hasUnsavedChanges = true;
            }

            $dragSticker = null;
        });
    }

    // Remove widget
    function removeWidget(widgetId) {
        layout.widgets = layout.widgets.filter(w => w.id !== widgetId);
        history.push(layout);
        hasUnsavedChanges = true;

        if (selectedWidgetId === widgetId) {
            selectWidget(null);
        }

        renderCanvas();
    }

    // Update widget position
    function updateWidgetPosition(widgetId, x, y) {
        const widget = layout.widgets.find(w => w.id === widgetId);
        if (widget) {
            widget.x = x;
            widget.y = y;
            history.push(layout);
            hasUnsavedChanges = true;
        }
    }

    // Update widget size
    function updateWidgetSize(widgetId, width, height) {
        const widget = layout.widgets.find(w => w.id === widgetId);
        if (widget) {
            widget.width = width;
            widget.height = height;
            history.push(layout);
            hasUnsavedChanges = true;
        }
    }

    // Select widget
    function selectWidget(widgetId) {
        selectedWidgetId = widgetId;

        $('.canvas-widget').removeClass('selected');

        if (widgetId) {
            $(`.canvas-widget[data-widget-id="${widgetId}"]`).addClass('selected');
            showWidgetSettings(widgetId);
        } else {
            $('#section-widget').hide();
        }
    }

    // Show widget settings
    function showWidgetSettings(widgetId) {
        const widget = layout.widgets.find(w => w.id === widgetId);
        if (!widget) return;

        const widgetInfo = getWidgetInfo(widget.type);

        $('#section-widget .widget-title').text(widgetInfo.title);
        $('#section-widget').show();

        // Build settings form (simplified)
        const $form = $('#widget-settings-form');
        $form.html(`
            <p class="widget-type"><strong>Type:</strong> ${widget.type}</p>
            <p class="widget-pos"><strong>Position:</strong> ${widget.x}, ${widget.y}</p>
            <p class="widget-size"><strong>Size:</strong> ${widget.width} × ${widget.height}</p>
        `);
    }

    // Save layout via AJAX
    function saveLayout() {
        const $btn = $('#save-layout');
        const originalText = $btn.find('.btn-text').text();

        $btn.prop('disabled', true).find('.btn-text').text(config.i18n?.saving || 'Saving...');

        $.ajax({
            url: config.ajaxUrl,
            method: 'POST',
            data: {
                action: 'apollo_builder_save',
                _wpnonce: config.nonceSave || config.nonce,
                post_id: config.homePostId,
                layout: JSON.stringify(layout)
            },
            success: function(response) {
                if (response.success) {
                    hasUnsavedChanges = false;
                    $btn.find('.btn-text').text(config.i18n?.saved || 'Saved!');
                    setTimeout(() => {
                        $btn.find('.btn-text').text(originalText);
                    }, 2000);
                } else {
                    alert(response.data?.message || config.i18n?.error || 'Error saving');
                    $btn.find('.btn-text').text(originalText);
                }
            },
            error: function() {
                alert(config.i18n?.error || 'Error saving');
                $btn.find('.btn-text').text(originalText);
            },
            complete: function() {
                $btn.prop('disabled', false);
            }
        });
    }

    // Save background
    function saveBackground(textureId) {
        $.ajax({
            url: config.ajaxUrl,
            method: 'POST',
            data: {
                action: 'apollo_builder_update_bg',
                _wpnonce: config.nonceUpdateBg || config.nonce,
                post_id: config.homePostId,
                texture_id: textureId
            }
        });
    }

    // Save trax URL
    function saveTraxUrl(url) {
        $.ajax({
            url: config.ajaxUrl,
            method: 'POST',
            data: {
                action: 'apollo_builder_update_trax',
                _wpnonce: config.nonceUpdateTrax || config.nonce,
                post_id: config.homePostId,
                trax_url: url
            },
            success: function(response) {
                if (response.success) {
                    alert('Music updated!');
                } else {
                    alert(response.data?.message || 'Error');
                }
            }
        });
    }

    // DOM Ready
    $(document).ready(init);

})(jQuery);

