/**
 * Apollo Navbar Apps Admin JavaScript
 *
 * Admin panel functionality for managing navbar apps.
 *
 * @package Apollo_Core
 * @since 1.9.0
 */

(function($) {
  'use strict';

  var config = window.apolloNavbarApps || {};
  var apps = [];
  var currentEditId = null;

  /**
   * Initialize
   */
  function init() {
    loadApps();
    initSortable();
    initEventListeners();
    updatePreview();
    startPreviewClock();
  }

  /**
   * Load apps from DOM data attributes
   */
  function loadApps() {
    apps = [];
    $('.apollo-app-item').each(function() {
      var appData = $(this).data('app');
      if (appData) {
        apps.push(appData);
      }
    });
  }

  /**
   * Initialize sortable list
   */
  function initSortable() {
    $('#apollo-apps-sortable').sortable({
      handle: '.apollo-app-handle',
      placeholder: 'apollo-app-item ui-sortable-placeholder',
      tolerance: 'pointer',
      update: function(event, ui) {
        // Update order in apps array
        var newOrder = [];
        $('#apollo-apps-sortable .apollo-app-item').each(function(index) {
          var appId = $(this).data('app-id');
          var app = apps.find(function(a) { return a.id === appId; });
          if (app) {
            app.order = index + 1;
            newOrder.push(app);
          }
        });
        apps = newOrder;
        updatePreview();
      }
    });
  }

  /**
   * Initialize event listeners
   */
  function initEventListeners() {
    // Add new app
    $('#apollo-add-app').on('click', function() {
      currentEditId = null;
      resetForm();
      $('#apollo-modal-title').text('Novo App');
      openModal();
    });

    // Edit app
    $(document).on('click', '.apollo-edit-app', function() {
      var $item = $(this).closest('.apollo-app-item');
      var app = $item.data('app');
      currentEditId = app.id;
      populateForm(app);
      $('#apollo-modal-title').text('Editar App');
      openModal();
    });

    // Delete app
    $(document).on('click', '.apollo-delete-app', function() {
      var $item = $(this).closest('.apollo-app-item');
      var app = $item.data('app');

      if (app.is_default) {
        showToast('Apps padrão não podem ser excluídos.', 'error');
        return;
      }

      if (confirm(config.strings.confirmDelete)) {
        deleteApp(app.id, $item);
      }
    });

    // Save all apps
    $('#apollo-save-apps').on('click', function() {
      saveAllApps();
    });

    // Reset to defaults
    $('#apollo-reset-defaults').on('click', function() {
      if (confirm('Restaurar todos os apps para os valores padrão?')) {
        apps = JSON.parse(JSON.stringify(config.defaultApps));
        renderAppsList();
        saveAllApps();
      }
    });

    // Modal close
    $('.apollo-modal-close, .apollo-modal-overlay, #apollo-modal-cancel').on('click', function() {
      closeModal();
    });

    // Modal save
    $('#apollo-modal-save').on('click', function() {
      saveCurrentApp();
    });

    // Background type toggle
    $('input[name="background_type"]').on('change', function() {
      var type = $(this).val();
      if (type === 'gradient') {
        $('#gradient-options').show();
        $('#image-options').hide();
      } else {
        $('#gradient-options').hide();
        $('#image-options').show();
      }
    });

    // Gradient preset selection
    $(document).on('click', '.gradient-preset', function() {
      $('.gradient-preset').removeClass('selected');
      $(this).addClass('selected');
      $('#gradient_custom').val($(this).data('gradient'));
    });

    // Icon select change
    $('#app_icon').on('change', function() {
      var icon = $(this).val();
      $('#icon-preview-element').attr('class', icon);
    });

    // Image upload
    $('#upload-image-btn').on('click', function() {
      var frame = wp.media({
        title: config.strings.selectImage,
        button: { text: config.strings.useImage },
        multiple: false
      });

      frame.on('select', function() {
        var attachment = frame.state().get('selection').first().toJSON();
        $('#background_image').val(attachment.url);
        $('#image-preview').html('<img src="' + attachment.url + '" alt="">');
        $('#remove-image-btn').show();
      });

      frame.open();
    });

    // Image URL change
    $('#image_url').on('change blur', function() {
      var url = $(this).val().trim();
      if (url) {
        $('#background_image').val(url);
        $('#image-preview').html('<img src="' + url + '" alt="">');
        $('#remove-image-btn').show();
      }
    });

    // Remove image
    $('#remove-image-btn').on('click', function() {
      $('#background_image').val('');
      $('#image_url').val('');
      $('#image-preview').html('<span class="placeholder">Nenhuma imagem</span>');
      $(this).hide();
    });

    // Prevent modal close on content click
    $('.apollo-modal-content').on('click', function(e) {
      e.stopPropagation();
    });

    // ESC to close modal
    $(document).on('keydown', function(e) {
      if (e.key === 'Escape' && $('#apollo-edit-modal').is(':visible')) {
        closeModal();
      }
    });
  }

  /**
   * Open modal
   */
  function openModal() {
    $('#apollo-edit-modal').fadeIn(200);
    $('body').css('overflow', 'hidden');
  }

  /**
   * Close modal
   */
  function closeModal() {
    $('#apollo-edit-modal').fadeOut(200);
    $('body').css('overflow', '');
  }

  /**
   * Reset form
   */
  function resetForm() {
    $('#apollo-app-form')[0].reset();
    $('#app_id').val('');
    $('#background_image').val('');
    $('#image-preview').html('<span class="placeholder">Nenhuma imagem</span>');
    $('#remove-image-btn').hide();
    $('#gradient-options').show();
    $('#image-options').hide();
    $('input[name="background_type"][value="gradient"]').prop('checked', true);
    $('.gradient-preset').removeClass('selected');
    $('#icon-preview-element').attr('class', 'ri-calendar-event-fill');
  }

  /**
   * Populate form with app data
   */
  function populateForm(app) {
    $('#app_id').val(app.id);
    $('#app_label').val(app.label);
    $('#app_icon_text').val(app.icon_text || '');
    $('#app_icon').val(app.icon || 'ri-calendar-event-fill').trigger('change');
    $('#app_url').val(app.url);
    $('#app_target').val(app.target || '_self');
    $('#app_active').prop('checked', app.active !== false);

    // Background type
    var bgType = app.background_type || 'gradient';
    $('input[name="background_type"][value="' + bgType + '"]').prop('checked', true).trigger('change');

    if (bgType === 'gradient') {
      $('#gradient_custom').val(app.background_gradient || '');
      // Select matching preset
      $('.gradient-preset').each(function() {
        if ($(this).data('gradient') === app.background_gradient) {
          $(this).addClass('selected');
        }
      });
    } else {
      if (app.background_image) {
        $('#background_image').val(app.background_image);
        $('#image_url').val(app.background_image);
        $('#image-preview').html('<img src="' + app.background_image + '" alt="">');
        $('#remove-image-btn').show();
      }
      $('#overlay_gradient').val(app.overlay_gradient || '');
    }
  }

  /**
   * Save current app (from modal)
   */
  function saveCurrentApp() {
    var formData = {
      id: $('#app_id').val() || 'app_' + Date.now(),
      label: $('#app_label').val().trim(),
      icon: $('#app_icon').val(),
      icon_text: $('#app_icon_text').val().trim() || $('#app_label').val().substring(0, 2).toUpperCase(),
      background_type: $('input[name="background_type"]:checked').val(),
      background_gradient: $('#gradient_custom').val(),
      background_image: $('#background_image').val(),
      overlay_gradient: $('#overlay_gradient').val(),
      url: $('#app_url').val().trim(),
      target: $('#app_target').val(),
      active: $('#app_active').is(':checked'),
      is_default: false
    };

    // Validation
    if (!formData.label) {
      showToast('Nome do app é obrigatório', 'error');
      return;
    }
    if (!formData.url) {
      showToast('URL é obrigatória', 'error');
      return;
    }

    // Update or add
    var existingIndex = apps.findIndex(function(a) { return a.id === currentEditId; });

    if (existingIndex > -1) {
      // Preserve order and is_default
      formData.order = apps[existingIndex].order;
      formData.is_default = apps[existingIndex].is_default;
      apps[existingIndex] = formData;
    } else {
      formData.order = apps.length + 1;
      apps.push(formData);
    }

    renderAppsList();
    updatePreview();
    closeModal();
  }

  /**
   * Delete app
   */
  function deleteApp(appId, $item) {
    apps = apps.filter(function(a) { return a.id !== appId; });
    $item.slideUp(200, function() {
      $(this).remove();
      updatePreview();
    });
  }

  /**
   * Save all apps to server
   */
  function saveAllApps() {
    var $btn = $('#apollo-save-apps');
    $btn.prop('disabled', true).text('Salvando...');

    $.ajax({
      url: config.ajaxUrl,
      method: 'POST',
      data: {
        action: 'apollo_save_navbar_apps',
        nonce: config.nonce,
        apps: apps
      },
      success: function(response) {
        if (response.success) {
          showToast(config.strings.saved, 'success');
          if (response.data && response.data.apps) {
            apps = response.data.apps;
            renderAppsList();
          }
        } else {
          showToast(response.data ? response.data.message : config.strings.error, 'error');
        }
      },
      error: function() {
        showToast(config.strings.error, 'error');
      },
      complete: function() {
        $btn.prop('disabled', false).html('<span class="dashicons dashicons-saved"></span> Salvar Alterações');
      }
    });
  }

  /**
   * Render apps list
   */
  function renderAppsList() {
    var $list = $('#apollo-apps-sortable');
    $list.empty();

    apps.sort(function(a, b) { return (a.order || 0) - (b.order || 0); });

    apps.forEach(function(app) {
      var bgStyle = getBackgroundStyle(app);
      var html = '<div class="apollo-app-item" data-app-id="' + app.id + '" data-app=\'' + JSON.stringify(app).replace(/'/g, '&#39;') + '\'>' +
        '<div class="apollo-app-handle"><span class="dashicons dashicons-menu"></span></div>' +
        '<div class="apollo-app-icon" style="' + bgStyle + '">' +
          (app.icon ? '<i class="' + app.icon + '"></i>' : '<span>' + (app.icon_text || app.label.substring(0, 2)) + '</span>') +
        '</div>' +
        '<div class="apollo-app-info">' +
          '<strong>' + escapeHtml(app.label) + '</strong>' +
          '<span class="apollo-app-url">' + escapeHtml(app.url) + '</span>' +
          (app.target === '_blank' ? '<span class="apollo-app-target dashicons dashicons-external"></span>' : '') +
        '</div>' +
        '<div class="apollo-app-actions">' +
          '<button type="button" class="button apollo-edit-app" title="Editar"><span class="dashicons dashicons-edit"></span></button>' +
          (app.is_default ? '' : '<button type="button" class="button apollo-delete-app" title="Excluir"><span class="dashicons dashicons-trash"></span></button>') +
        '</div>' +
        (app.is_default ? '<span class="apollo-app-badge">Padrão</span>' : '') +
      '</div>';

      $list.append(html);
    });

    // Re-init sortable
    $list.sortable('refresh');
  }

  /**
   * Update preview panel
   */
  function updatePreview() {
    var $grid = $('#apollo-preview-grid');
    $grid.empty();

    apps.filter(function(a) { return a.active !== false; }).slice(0, 8).forEach(function(app) {
      var bgStyle = getBackgroundStyle(app);
      var html = '<div class="preview-app-item">' +
        '<div class="preview-app-icon" style="' + bgStyle + '">' +
          (app.icon ? '<i class="' + app.icon + '"></i>' : (app.icon_text || app.label.substring(0, 2))) +
        '</div>' +
        '<span class="preview-app-label">' + escapeHtml(app.label) + '</span>' +
      '</div>';
      $grid.append(html);
    });
  }

  /**
   * Get background style for app icon
   */
  function getBackgroundStyle(app) {
    if (app.background_type === 'image' && app.background_image) {
      if (app.overlay_gradient) {
        return 'background: ' + app.overlay_gradient + ', url(' + app.background_image + '); background-size: cover; background-position: center;';
      }
      return 'background-image: url(' + app.background_image + '); background-size: cover; background-position: center;';
    }
    if (app.background_gradient) {
      return 'background: ' + app.background_gradient + ';';
    }
    return 'background: linear-gradient(135deg, #64748b, #475569);';
  }

  /**
   * Start preview clock
   */
  function startPreviewClock() {
    function update() {
      var now = new Date();
      $('.preview-clock').text(now.toLocaleTimeString('pt-BR', { hour12: false }));
    }
    update();
    setInterval(update, 1000);
  }

  /**
   * Show toast notification
   */
  function showToast(message, type) {
    var $toast = $('#apollo-toast');
    $toast.text(message)
      .removeClass('success error')
      .addClass(type || '')
      .addClass('show');

    setTimeout(function() {
      $toast.removeClass('show');
    }, 3000);
  }

  /**
   * Escape HTML
   */
  function escapeHtml(str) {
    if (!str) return '';
    return str.replace(/[&<>"']/g, function(m) {
      return {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#39;'
      }[m];
    });
  }

  // Initialize on DOM ready
  $(document).ready(init);

})(jQuery);
