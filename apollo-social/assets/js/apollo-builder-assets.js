/**
 * Apollo Builder - Backgrounds & Stickers Module
 *
 * Extends the base builder with:
 * - Background selection modal
 * - Stickers palette and canvas placement
 * - Drag & drop stickers
 * - REST API integration for assets
 *
 * @package Apollo_Social
 * @since 1.5.0
 */

(function ($) {
  'use strict';

  // ───────────────────────────────────────────────────────────────────────────
  // Module State
  // ───────────────────────────────────────────────────────────────────────────

  const ApolloAssets = {
    backgrounds: [],
    backgroundCategories: {},
    stickers: [],
    stickerCategories: {},
    isLoaded: false,

    // Current layout state (extended)
    currentBackground: null,
    currentStickers: [],
    selectedBackgroundId: null,
    selectedCategory: 'all',

    // DOM cache
    $modal: null,
    $canvas: null,
    $stickersLayer: null,
    $stickersPanel: null
  };

  // REST config
  const restConfig = {
    namespace: '/wp-json/apollo-social/v1/builder',
    nonce: window.apolloBuilderConfig?.restNonce || window.wpApiSettings?.nonce || ''
  };

  // ───────────────────────────────────────────────────────────────────────────
  // Initialization
  // ───────────────────────────────────────────────────────────────────────────

  function init() {
    // Cache DOM elements
    ApolloAssets.$modal = $('#background-modal');
    ApolloAssets.$canvas = $('#canvas-board');
    ApolloAssets.$stickersLayer = $('#canvas-stickers-layer');
    ApolloAssets.$stickersPanel = $('#stickers-palette');

    // Load assets from REST API
    loadAssets().then(() => {
      renderBackgroundCategoryTabs();
      renderBackgroundsGrid();
      renderStickerCategoryTabs();
      renderStickersPanel();
      loadCurrentLayout();
    });

    // Bind events
    bindBackgroundEvents();
    bindStickerEvents();
    bindModalEvents();
  }

  // ───────────────────────────────────────────────────────────────────────────
  // REST API
  // ───────────────────────────────────────────────────────────────────────────

  function loadAssets() {
    return fetch(`${restConfig.namespace}/assets`, {
      headers: {
        'X-WP-Nonce': restConfig.nonce
      }
    })
      .then(response => response.json())
      .then(data => {
        ApolloAssets.backgrounds = data.backgrounds || [];
        ApolloAssets.backgroundCategories = data.background_categories || {};
        ApolloAssets.stickers = data.stickers || [];
        ApolloAssets.stickerCategories = data.sticker_categories || {};
        ApolloAssets.isLoaded = true;
        console.log('[Apollo Assets] Loaded:', data);
      })
      .catch(err => {
        console.error('[Apollo Assets] Failed to load:', err);
      });
  }

  function loadCurrentLayout() {
    const userId = $('#apollo-builder-root').data('user-id');
    if (!userId) return;

    fetch(`${restConfig.namespace}/layout?user_id=${userId}`, {
      headers: {
        'X-WP-Nonce': restConfig.nonce
      }
    })
      .then(response => response.json())
      .then(data => {
        if (data.layout) {
          // Set background
          if (data.layout.background?.id) {
            ApolloAssets.currentBackground = data.layout.background.id;
            applyBackgroundToCanvas(data.layout.background.id);
            updateCurrentBackgroundPreview(data.layout.background.id);
          }

          // Set stickers
          if (data.layout.stickers?.length) {
            ApolloAssets.currentStickers = data.layout.stickers;
            renderCanvasStickers();
          }
        }
      })
      .catch(err => {
        console.error('[Apollo Assets] Failed to load layout:', err);
      });
  }

  function saveBackground(backgroundId) {
    const userId = $('#apollo-builder-root').data('user-id');

    return fetch(`${restConfig.namespace}/background`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-WP-Nonce': restConfig.nonce
      },
      body: JSON.stringify({
        background_id: backgroundId,
        user_id: userId
      })
    })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          ApolloAssets.currentBackground = backgroundId;
          console.log('[Apollo Assets] Background saved:', backgroundId);
        }
        return data;
      });
  }

  function addStickerToLayout(assetId, x, y) {
    const userId = $('#apollo-builder-root').data('user-id');

    return fetch(`${restConfig.namespace}/stickers`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-WP-Nonce': restConfig.nonce
      },
      body: JSON.stringify({
        asset: assetId,
        x: x,
        y: y,
        scale: 1.0,
        user_id: userId
      })
    })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          ApolloAssets.currentStickers = data.stickers || [];
          renderCanvasStickers();
        }
        return data;
      });
  }

  function updateStickerPosition(instanceId, x, y) {
    const userId = $('#apollo-builder-root').data('user-id');

    return fetch(`${restConfig.namespace}/stickers/${instanceId}`, {
      method: 'PATCH',
      headers: {
        'Content-Type': 'application/json',
        'X-WP-Nonce': restConfig.nonce
      },
      body: JSON.stringify({
        x: x,
        y: y,
        user_id: userId
      })
    })
      .then(response => response.json());
  }

  function removeSticker(instanceId) {
    const userId = $('#apollo-builder-root').data('user-id');

    return fetch(`${restConfig.namespace}/stickers/${instanceId}?user_id=${userId}`, {
      method: 'DELETE',
      headers: {
        'X-WP-Nonce': restConfig.nonce
      }
    })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          ApolloAssets.currentStickers = data.stickers || [];
          renderCanvasStickers();
        }
        return data;
      });
  }

  // ───────────────────────────────────────────────────────────────────────────
  // Background Modal
  // ───────────────────────────────────────────────────────────────────────────

  function openBackgroundModal() {
    ApolloAssets.$modal.show().addClass('is-open');
    ApolloAssets.selectedBackgroundId = ApolloAssets.currentBackground;
    highlightSelectedBackground();
    document.body.classList.add('modal-open');
  }

  function closeBackgroundModal() {
    ApolloAssets.$modal.hide().removeClass('is-open');
    document.body.classList.remove('modal-open');
  }

  function applySelectedBackground() {
    if (ApolloAssets.selectedBackgroundId) {
      applyBackgroundToCanvas(ApolloAssets.selectedBackgroundId);
      saveBackground(ApolloAssets.selectedBackgroundId);
      updateCurrentBackgroundPreview(ApolloAssets.selectedBackgroundId);
    }
    closeBackgroundModal();
  }

  function renderBackgroundCategoryTabs() {
    const $tabs = $('#background-category-tabs');
    $tabs.empty();

    // Add "All" tab
    $tabs.append(`
      <button type="button"
              class="category-tab category-tab--active"
              data-category="all"
              role="tab"
              aria-selected="true">
        Todos
      </button>
    `);

    // Add category tabs
    Object.entries(ApolloAssets.backgroundCategories).forEach(([slug, label]) => {
      $tabs.append(`
        <button type="button"
                class="category-tab"
                data-category="${slug}"
                role="tab"
                aria-selected="false">
          ${escapeHtml(label)}
        </button>
      `);
    });
  }

  function renderBackgroundsGrid(category = 'all') {
    const $grid = $('#backgrounds-grid');
    $grid.empty();

    let backgrounds = ApolloAssets.backgrounds;
    if (category !== 'all') {
      backgrounds = backgrounds.filter(bg => bg.category === category);
    }

    if (!backgrounds.length) {
      $grid.html('<p class="no-results">Nenhum background encontrado.</p>');
      return;
    }

    backgrounds.forEach(bg => {
      const isSelected = bg.id === ApolloAssets.selectedBackgroundId;
      const previewStyle = getBackgroundPreviewStyle(bg);

      const $card = $(`
        <div class="background-card ${isSelected ? 'background-card--selected' : ''}"
             data-background-id="${bg.id}"
             role="option"
             aria-selected="${isSelected}"
             tabindex="0">
          <div class="background-preview" style="${previewStyle}">
            ${bg.preview_url ? `<img src="${escapeHtml(bg.preview_url)}" alt="${escapeHtml(bg.label)}" loading="lazy" />` : ''}
          </div>
          <span class="background-label">${escapeHtml(bg.label)}</span>
          ${bg.is_limited ? '<span class="background-badge background-badge--limited">Limitado</span>' : ''}
        </div>
      `);

      $grid.append($card);
    });
  }

  function getBackgroundPreviewStyle(bg) {
    const cssValue = bg.css_value || '';
    if (cssValue.startsWith('linear-gradient') || cssValue.startsWith('radial-gradient')) {
      return `background: ${cssValue};`;
    }
    if (cssValue.startsWith('#') || cssValue.startsWith('rgb')) {
      return `background-color: ${cssValue};`;
    }
    return `background: ${cssValue};`;
  }

  function highlightSelectedBackground() {
    $('.background-card').removeClass('background-card--selected');
    if (ApolloAssets.selectedBackgroundId) {
      $(`.background-card[data-background-id="${ApolloAssets.selectedBackgroundId}"]`)
        .addClass('background-card--selected');
    }
  }

  function applyBackgroundToCanvas(backgroundId) {
    const bg = ApolloAssets.backgrounds.find(b => b.id === backgroundId);
    if (!bg) return;

    const $canvas = ApolloAssets.$canvas;

    // Remove all background classes
    $canvas[0].className = $canvas[0].className.replace(/apollo-canvas--bg-[\w-]+/g, '');

    // Apply new background
    $canvas.addClass(`apollo-canvas--bg-${backgroundId}`);

    const cssValue = bg.css_value || '';
    let style = '';

    if (cssValue.startsWith('linear-gradient') || cssValue.startsWith('radial-gradient')) {
      style = `background: ${cssValue};`;
    } else if (cssValue.startsWith('#') || cssValue.startsWith('rgb')) {
      style = `background-color: ${cssValue};`;
    } else {
      style = `background: ${cssValue};`;
    }

    if (bg.css_size) {
      style += ` background-size: ${bg.css_size};`;
    }

    $canvas.attr('style', style);
  }

  function updateCurrentBackgroundPreview(backgroundId) {
    const bg = ApolloAssets.backgrounds.find(b => b.id === backgroundId);
    const $preview = $('#current-background-preview');

    if (!bg) {
      $preview.empty();
      return;
    }

    const previewStyle = getBackgroundPreviewStyle(bg);
    $preview.html(`
      <div class="current-bg-thumb" style="${previewStyle}">
        ${bg.preview_url ? `<img src="${escapeHtml(bg.preview_url)}" alt="" />` : ''}
      </div>
      <span class="current-bg-label">${escapeHtml(bg.label)}</span>
    `);
  }

  // ───────────────────────────────────────────────────────────────────────────
  // Stickers Panel
  // ───────────────────────────────────────────────────────────────────────────

  function renderStickerCategoryTabs() {
    const $tabs = $('#stickers-category-tabs');
    $tabs.empty();

    // Add "All" tab
    $tabs.append(`
      <button type="button"
              class="category-tab category-tab--active"
              data-category="all"
              role="tab"
              aria-selected="true">
        Todos
      </button>
    `);

    Object.entries(ApolloAssets.stickerCategories).forEach(([slug, label]) => {
      $tabs.append(`
        <button type="button"
                class="category-tab"
                data-category="${slug}"
                role="tab"
                aria-selected="false">
          ${escapeHtml(label)}
        </button>
      `);
    });
  }

  function renderStickersPanel(category = 'all') {
    const $panel = ApolloAssets.$stickersPanel;
    $panel.empty();

    let stickers = ApolloAssets.stickers;
    if (category !== 'all') {
      stickers = stickers.filter(s => s.category === category);
    }

    if (!stickers.length) {
      $panel.html('<p class="no-results">Nenhum sticker encontrado.</p>');
      return;
    }

    stickers.forEach(sticker => {
      const $item = $(`
        <div class="sticker-palette-item"
             data-sticker-id="${sticker.id}"
             draggable="true"
             title="${escapeHtml(sticker.label)}">
          <img src="${escapeHtml(sticker.preview_url)}"
               alt="${escapeHtml(sticker.label)}"
               class="sticker-preview-img"
               loading="lazy" />
          ${sticker.is_limited ? '<span class="sticker-badge sticker-badge--limited">Limitado</span>' : ''}
        </div>
      `);

      $panel.append($item);
    });
  }

  function renderCanvasStickers() {
    const $layer = ApolloAssets.$stickersLayer;
    $layer.empty();

    ApolloAssets.currentStickers.forEach(sticker => {
      const asset = ApolloAssets.stickers.find(s => s.id === sticker.asset);
      if (!asset) return; // Skip if asset no longer exists

      const x = sticker.x || 0;
      const y = sticker.y || 0;
      const scale = sticker.scale || 1;
      const rotation = sticker.rotation || 0;
      const zIndex = sticker.z_index || asset.z_index_hint || 50;

      const transform = `translate(${x}px, ${y}px) scale(${scale}) rotate(${rotation}deg)`;

      const $sticker = $(`
        <div class="canvas-sticker"
             data-sticker-id="${sticker.asset}"
             data-instance-id="${sticker.id}"
             style="transform: ${transform}; z-index: ${zIndex};">
          <img src="${escapeHtml(asset.image_url)}"
               alt="${escapeHtml(asset.label)}"
               width="${asset.width}"
               height="${asset.height}"
               draggable="false" />
          <button type="button" class="sticker-delete" title="Remover sticker">
            <span class="dashicons dashicons-no-alt"></span>
          </button>
        </div>
      `);

      $layer.append($sticker);
    });
  }

  // ───────────────────────────────────────────────────────────────────────────
  // Event Binding
  // ───────────────────────────────────────────────────────────────────────────

  function bindBackgroundEvents() {
    // Open modal
    $('#open-background-modal').on('click', openBackgroundModal);

    // Category tabs
    $(document).on('click', '#background-category-tabs .category-tab', function () {
      const category = $(this).data('category');

      $('#background-category-tabs .category-tab')
        .removeClass('category-tab--active')
        .attr('aria-selected', 'false');
      $(this).addClass('category-tab--active').attr('aria-selected', 'true');

      renderBackgroundsGrid(category);
    });

    // Select background
    $(document).on('click', '.background-card', function () {
      ApolloAssets.selectedBackgroundId = $(this).data('background-id');
      highlightSelectedBackground();

      // Live preview
      applyBackgroundToCanvas(ApolloAssets.selectedBackgroundId);
    });

    // Apply button
    $('#apply-background').on('click', applySelectedBackground);
  }

  function bindStickerEvents() {
    // Category tabs
    $(document).on('click', '#stickers-category-tabs .category-tab', function () {
      const category = $(this).data('category');

      $('#stickers-category-tabs .category-tab')
        .removeClass('category-tab--active')
        .attr('aria-selected', 'false');
      $(this).addClass('category-tab--active').attr('aria-selected', 'true');

      renderStickersPanel(category);
    });

    // Drag start from palette
    $(document).on('dragstart', '.sticker-palette-item', function (e) {
      const stickerId = $(this).data('sticker-id');
      e.originalEvent.dataTransfer.setData('application/apollo-sticker', stickerId);
      e.originalEvent.dataTransfer.effectAllowed = 'copy';
      $(this).addClass('dragging');
    });

    $(document).on('dragend', '.sticker-palette-item', function () {
      $(this).removeClass('dragging');
    });

    // Drop on canvas
    const $canvas = ApolloAssets.$canvas;

    $canvas.on('dragover', function (e) {
      const stickerId = e.originalEvent.dataTransfer.types.includes('application/apollo-sticker');
      if (stickerId) {
        e.preventDefault();
        e.originalEvent.dataTransfer.dropEffect = 'copy';
        $(this).addClass('sticker-drag-over');
      }
    });

    $canvas.on('dragleave', function () {
      $(this).removeClass('sticker-drag-over');
    });

    $canvas.on('drop', function (e) {
      e.preventDefault();
      $(this).removeClass('sticker-drag-over');

      const stickerId = e.originalEvent.dataTransfer.getData('application/apollo-sticker');
      if (!stickerId) return;

      const offset = $canvas.offset();
      const x = Math.round(e.clientX - offset.left);
      const y = Math.round(e.clientY - offset.top);

      addStickerToLayout(stickerId, x, y);
    });

    // Sticker drag on canvas (repositioning)
    let $dragSticker = null;
    let dragOffsetX = 0;
    let dragOffsetY = 0;

    $(document).on('mousedown', '.canvas-sticker', function (e) {
      if ($(e.target).closest('.sticker-delete').length) return;

      $dragSticker = $(this);
      const transform = $dragSticker.css('transform');
      const matrix = new DOMMatrix(transform);

      dragOffsetX = e.clientX - matrix.m41;
      dragOffsetY = e.clientY - matrix.m42;

      $dragSticker.addClass('dragging');
      e.preventDefault();
    });

    $(document).on('mousemove', function (e) {
      if (!$dragSticker) return;

      const canvasOffset = ApolloAssets.$canvas.offset();
      const canvasWidth = ApolloAssets.$canvas.width();
      const canvasHeight = ApolloAssets.$canvas.height();

      let x = e.clientX - dragOffsetX;
      let y = e.clientY - dragOffsetY;

      // Bounds check
      x = Math.max(0, Math.min(x, canvasWidth - 50));
      y = Math.max(0, Math.min(y, canvasHeight - 50));

      const transform = $dragSticker.css('transform');
      const matrix = new DOMMatrix(transform);

      $dragSticker.css('transform',
        `translate(${x}px, ${y}px) scale(${matrix.a}) rotate(${Math.atan2(matrix.b, matrix.a) * 180 / Math.PI}deg)`
      );
    });

    $(document).on('mouseup', function () {
      if (!$dragSticker) return;

      $dragSticker.removeClass('dragging');

      const instanceId = $dragSticker.data('instance-id');
      const transform = $dragSticker.css('transform');
      const matrix = new DOMMatrix(transform);

      updateStickerPosition(instanceId, Math.round(matrix.m41), Math.round(matrix.m42));

      $dragSticker = null;
    });

    // Delete sticker
    $(document).on('click', '.canvas-sticker .sticker-delete', function (e) {
      e.stopPropagation();
      const instanceId = $(this).closest('.canvas-sticker').data('instance-id');

      if (confirm('Remover este sticker?')) {
        removeSticker(instanceId);
      }
    });
  }

  function bindModalEvents() {
    // Close modal
    $(document).on('click', '[data-close-modal]', closeBackgroundModal);

    // Escape key
    $(document).on('keydown', function (e) {
      if (e.key === 'Escape' && ApolloAssets.$modal.hasClass('is-open')) {
        closeBackgroundModal();
      }
    });
  }

  // ───────────────────────────────────────────────────────────────────────────
  // Helpers
  // ───────────────────────────────────────────────────────────────────────────

  function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
  }

  // ───────────────────────────────────────────────────────────────────────────
  // Export & Init
  // ───────────────────────────────────────────────────────────────────────────

  // Expose for external use
  window.ApolloAssets = ApolloAssets;

  // Initialize when DOM ready
  $(document).ready(init);

})(jQuery);
