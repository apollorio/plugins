/**
 * Apollo Events Manager - Admin Metabox JavaScript
 * Strict Mode timetable (auto times), auto date rollover, geocoding assist,
 * dialogs for DJ/Local creation, and enhanced selects.
 */

(function ($) {
  'use strict';

  let timetableData = [];
  let geoTimer = null;

  $(document).ready(function () {
    initDefaults();
    initDialogs();
    initTimetable();
    initEnhancedSelects();
    initImageUploads();
    bindEvents();
    loadExistingTimetable();

    if (typeof window.motion !== 'undefined' || typeof window.Motion !== 'undefined') {
      initMotionAnimations();
    }
  });

  /**
   * Strict-mode defaults for start/end time if empty.
   * STRICT MODE: Default start 23:00, end 08:00 (next day)
   */
  function initDefaults() {
    // Support both ID patterns (with or without apollo_ prefix)
    const $start = $('#apollo_event_start_time, #_event_start_time').first();
    const $end = $('#apollo_event_end_time, #_event_end_time').first();

    if ($start.length && !$start.val()) {
      $start.val('23:00');
    }

    if ($end.length && !$end.val()) {
      $end.val('08:00');
    }

    // Auto-set end date to start date + 1 day if end time is before start (next day logic)
    const $startDate = $('#apollo_event_start_date');
    const $endDate = $('#apollo_event_end_date');

    if ($startDate.length && $startDate.val() && $endDate.length && !$endDate.val()) {
      const dt = new Date($startDate.val());
      dt.setDate(dt.getDate() + 1);
      const y = dt.getFullYear();
      const m = String(dt.getMonth() + 1).padStart(2, '0');
      const d = String(dt.getDate()).padStart(2, '0');
      $endDate.val(y + '-' + m + '-' + d);
    }
  }

  /**
   * jQuery UI dialogs for adding DJs and Locals.
   */
  function initDialogs() {
    $('#apollo_add_dj_dialog').dialog({
      autoOpen: false,
      modal: true,
      width: 500,
      buttons: [
        {
          text: 'Adicionar',
          class: 'button button-primary',
          click: function () {
            submitNewDJ();
          }
        },
        {
          text: 'Cancelar',
          class: 'button',
          click: function () {
            $(this).dialog('close');
            clearDJForm();
          }
        }
      ]
    });

    $('#apollo_add_local_dialog').dialog({
      autoOpen: false,
      modal: true,
      width: 500,
      buttons: [
        {
          text: 'Adicionar',
          class: 'button button-primary',
          click: function () {
            submitNewLocal();
          }
        },
        {
          text: 'Cancelar',
          class: 'button',
          click: function () {
            $(this).dialog('close');
            clearLocalForm();
          }
        }
      ]
    });
  }

  /**
   * Global event bindings.
   */
  function bindEvents() {
    $('#apollo_add_new_dj').on('click', function (e) {
      e.preventDefault();
      $('#apollo_add_dj_dialog').dialog('open');
    });

    $('#apollo_add_new_local').on('click', function (e) {
      e.preventDefault();
      $('#apollo_add_local_dialog').dialog('open');
    });

    $('#apollo_event_djs').on('change', function () {
      rebuildTimetable();
    });

    // Strict mode: manual refresh not needed.
    $('#apollo_refresh_timetable').prop('disabled', true).hide();

    $('form#post').on('submit', function () {
      saveTimetableToHidden();
      syncAllSelects();
    });

    // Auto end date = start date + 1 day.
    $('#apollo_event_start_date, #_event_start_date').on('change', function () {
      const val = $(this).val();
      if (!val) return;
      const dt = new Date(val);
      dt.setDate(dt.getDate() + 1);
      const y = dt.getFullYear();
      const m = String(dt.getMonth() + 1).padStart(2, '0');
      const d = String(dt.getDate()).padStart(2, '0');
      $('#apollo_event_end_date, #_event_end_date').first().val(y + '-' + m + '-' + d);
    });

    // Start time change -> recalc chain.
    $('#apollo_event_start_time, #_event_start_time').on('change', function () {
      recalculateStrictTimeline();
      saveTimetableToHidden();
    });

    // Debounced geocoding for new local.
    $('#new_local_address').on('input', function () {
      const address = $(this).val();
      clearTimeout(geoTimer);
      $('.apollo-geo-spinner').show();
      geoTimer = setTimeout(function () {
        fetchCoordinates(address);
      }, 1500);
    });
  }

  /**
   * Debounced fetch of coordinates using Nominatim.
   */
  function fetchCoordinates(address) {
    if (!address || address.length < 5) {
      $('.apollo-geo-spinner').hide();
      return;
    }

    $.ajax({
      url: 'https://nominatim.openstreetmap.org/search?format=json&q=' + encodeURIComponent(address) + '&limit=1',
      method: 'GET',
      headers: { 'User-Agent': 'ApolloEvents/1.0' },
      success: function (data) {
        if (Array.isArray(data) && data.length > 0) {
          $('#new_local_lat').val(data[0].lat);
          $('#new_local_lng').val(data[0].lon);
          $('#new_local_address').css('border-color', '#46b450');
        }
      },
      complete: function () {
        $('.apollo-geo-spinner').hide();
      }
    });
  }

  /**
   * AJAX: create new DJ.
   */
  function submitNewDJ() {
    const name = $('#new_dj_name').val().trim();
    const $msg = $('#apollo_dj_form_message');

    if (!name) {
      showMessage($msg, apolloAdmin.i18n.enter_name || 'Nome obrigatório', 'error');
      return;
    }

    showMessage($msg, 'Verificando...', 'info');

    $.ajax({
      url: apolloAdmin.ajax_url,
      type: 'POST',
      data: {
        action: 'apollo_add_new_dj',
        nonce: apolloAdmin.nonce,
        name: name
      },
      success: function (response) {
        if (response.success) {
          const option = $('<option>', { value: response.data.id, text: response.data.name, selected: true });
          $('#apollo_event_djs').append(option);
          $('#apollo_add_dj_dialog').dialog('close');
          clearDJForm();
          rebuildTimetable();
          alert('DJ ' + response.data.name + ' adicionado!');
        } else {
          showMessage($msg, response.data, 'error');
        }
      },
      error: function () {
        showMessage($msg, 'Erro de conexão', 'error');
      }
    });
  }

  /**
   * AJAX: create new Local with optional coords.
   */
  function submitNewLocal() {
    const name = $('#new_local_name').val().trim();
    const address = $('#new_local_address').val().trim();
    const city = $('#new_local_city').val().trim();
    const lat = $('#new_local_lat').val();
    const lng = $('#new_local_lng').val();
    const $msg = $('#apollo_local_form_message');

    if (!name) {
      showMessage($msg, apolloAdmin.i18n.enter_name || 'Nome obrigatório', 'error');
      return;
    }

    showMessage($msg, 'Salvando...', 'info');

    $.ajax({
      url: apolloAdmin.ajax_url,
      type: 'POST',
      data: {
        action: 'apollo_add_new_local',
        nonce: apolloAdmin.nonce,
        name: name,
        address: address,
        city: city,
        lat: lat,
        lng: lng
      },
      success: function (response) {
        if (response.success) {
          const option = $('<option>', { value: response.data.id, text: response.data.name, selected: true });
          $('#apollo_event_local').append(option);
          $('#apollo_add_local_dialog').dialog('close');
          clearLocalForm();
          initLocalSelect();
          $('#apollo_event_local').val(response.data.id);
          alert('Local ' + response.data.name + ' adicionado!');
        } else {
          showMessage($msg, response.data, 'error');
        }
      },
      error: function () {
        showMessage($msg, 'Erro de conexão', 'error');
      }
    });
  }

  /**
   * Timetable: bind row events.
   */
  function initTimetable() {
    $('#apollo_timetable_rows').on('change', 'input[type="time"]', function () {
      recalculateStrictTimeline();
      saveTimetableToHidden();
    });

    $('#apollo_timetable_rows').on('click', '.apollo-move-up', function (e) {
      e.preventDefault();
      moveRowUp($(this).closest('tr'));
    });

    $('#apollo_timetable_rows').on('click', '.apollo-move-down', function (e) {
      e.preventDefault();
      moveRowDown($(this).closest('tr'));
    });
  }

  /**
   * Load saved timetable from hidden input.
   */
  function loadExistingTimetable() {
    const existingJSON = $('#apollo_event_timetable').val();
    if (existingJSON) {
      try {
        timetableData = JSON.parse(existingJSON) || [];
      } catch (e) {
        timetableData = [];
      }
    }
    if (!Array.isArray(timetableData)) {
      timetableData = [];
    }
    rebuildTimetable();
  }

  /**
   * Rebuild timetable rows for selected DJs (strict mode).
   */
  function rebuildTimetable() {
    // Prefer hidden select; fallback to checked checkboxes if browser blocks programmatic change
    const selectedDJs = $('#apollo_event_djs').val() || $('#apollo_dj_list input[type="checkbox"]:checked').map(function () {
      return $(this).val();
    }).get();
    const $rows = $('#apollo_timetable_rows');
    const $table = $('#apollo_timetable_table');
    const $empty = $('#apollo_timetable_empty');

    // Snapshot current input values to preserve manual edits on rebuild
    const existingTimes = {};
    $rows.find('tr').each(function () {
      const id = String($(this).data('dj-id'));
      existingTimes[id] = {
        start: $(this).find('.timetable-start').val(),
        end: $(this).find('.timetable-end').val()
      };
    });

    $rows.empty();

    if (selectedDJs.length === 0) {
      $table.hide();
      $empty.show().text('((( NO SELECTION AVAILABLE )))');
      timetableData = [];
      saveTimetableToHidden();
      return;
    }

    $table.show();
    $empty.hide();

    // Preserve saved order when possible.
    const savedOrder = [];
    timetableData.forEach(function (item) {
      if (item && item.dj && item.order !== undefined) {
        savedOrder.push({ dj: String(item.dj), order: parseInt(item.order, 10) });
      }
    });
    savedOrder.sort(function (a, b) { return a.order - b.order; });

    let orderedDJs = [];
    if (savedOrder.length > 0) {
      savedOrder.forEach(function (item) {
        if (selectedDJs.indexOf(String(item.dj)) !== -1) {
          orderedDJs.push(String(item.dj));
        }
      });
      selectedDJs.forEach(function (djID) {
        if (orderedDJs.indexOf(String(djID)) === -1) {
          orderedDJs.push(String(djID));
        }
      });
    } else {
      orderedDJs = selectedDJs.map(String);
    }

    orderedDJs.forEach(function (djID, idx) {
      const $opt = $('#apollo_event_djs option[value="' + djID + '"]');
      const djName = $opt.text();
      const existing = timetableData.find(function (item) { return String(item.dj) === String(djID); }) || {};
      const preserved = existingTimes[djID] || {};

      const row = $('<tr>', {
        'data-dj-id': djID,
        'data-order': existing.order || idx + 1,
        'class': 'apollo-timetable-row'
      });

      row.append($('<td>').html('<span class="apollo-order-number">#' + (idx + 1) + '</span>'));

      const orderControls = $('<div>', { 'class': 'apollo-order-controls' });
      orderControls.append($('<span>', { 'class': 'apollo-drag-handle dashicons dashicons-menu', 'title': 'Arrastar para reordenar' }));
      orderControls.append($('<button>', { type: 'button', 'class': 'apollo-move-up button button-small', title: 'Mover para cima', html: '<span class="dashicons dashicons-arrow-up-alt"></span>' }));
      orderControls.append($('<button>', { type: 'button', 'class': 'apollo-move-down button button-small', title: 'Mover para baixo', html: '<span class="dashicons dashicons-arrow-down-alt"></span>' }));
      row.append($('<td>').html(orderControls));

      row.append($('<td>').html('<strong>' + djName + '</strong>'));

      row.append($('<td>').html('<input type="time" class="timetable-start" name="dj_time_in_' + djID + '" value="' + (preserved.start || '') + '" readonly tabindex="-1">'));
      row.append($('<td>').html('<input type="time" class="timetable-end" name="dj_time_out_' + djID + '" value="' + (preserved.end || existing.to || existing.end || '') + '">'));

      row.append($('<td>'));
      $rows.append(row);
    });

    initTimetableSortable();
    updateOrderNumbers();
    recalculateStrictTimeline();
    saveTimetableToHidden();
  }

  /**
   * Strict mode recalculation: start of first row = event start, each next starts at previous end.
   * Default slot = +2h, handles midnight rollover.
   */
  function recalculateStrictTimeline() {
    const $rows = $('#apollo_timetable_rows tr');
    const eventStartTime = $('#apollo_event_start_time, #_event_start_time').first().val() || '23:00';

    const getDateFromTimeStr = function (str) {
      const parts = String(str).split(':');
      const h = parseInt(parts[0], 10) || 0;
      const m = parseInt(parts[1], 10) || 0;
      const d = new Date();
      d.setHours(h, m, 0, 0);
      return d;
    };

    const getTimeStrFromDate = function (d) {
      return String(d.getHours()).padStart(2, '0') + ':' + String(d.getMinutes()).padStart(2, '0');
    };

    let currentTime = getDateFromTimeStr(eventStartTime);

    $rows.each(function () {
      const $startInput = $(this).find('.timetable-start');
      const $endInput = $(this).find('.timetable-end');

      $startInput.val(getTimeStrFromDate(currentTime));

      let endTime;
      const userEndVal = $endInput.val();

      if (userEndVal) {
        let userEndDate = getDateFromTimeStr(userEndVal);
        if (userEndDate.getHours() < currentTime.getHours() && currentTime.getHours() > 12) {
          userEndDate.setDate(userEndDate.getDate() + 1);
        }
        if (userEndDate <= currentTime) {
          currentTime.setHours(currentTime.getHours() + 2);
          endTime = new Date(currentTime);
          $endInput.val(getTimeStrFromDate(endTime));
        } else {
          endTime = userEndDate;
          currentTime = userEndDate;
        }
      } else {
        currentTime.setHours(currentTime.getHours() + 2);
        endTime = new Date(currentTime);
        $endInput.val(getTimeStrFromDate(endTime));
      }
    });
  }

  /**
   * Sortable.js hookup with strict recalculation on reorder.
   */
  function initTimetableSortable() {
    if ($('#apollo_timetable_rows').data('sortable')) {
      $('#apollo_timetable_rows').sortable('destroy');
    }

    if (typeof Sortable === 'undefined') {
      const script = document.createElement('script');
      script.src = 'https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js';
      script.onload = function () {
        createSortableInstance();
      };
      document.head.appendChild(script);
    } else {
      createSortableInstance();
    }

    function createSortableInstance() {
      const sortable = new Sortable(document.getElementById('apollo_timetable_rows'), {
        handle: '.apollo-drag-handle',
        animation: 150,
        ghostClass: 'apollo-sortable-ghost',
        chosenClass: 'apollo-sortable-chosen',
        dragClass: 'apollo-sortable-drag',
        onEnd: function () {
          updateOrderNumbers();
          recalculateStrictTimeline();
          saveTimetableToHidden();
        }
      });
      $('#apollo_timetable_rows').data('sortable', sortable);
    }
  }

  function updateOrderNumbers() {
    $('#apollo_timetable_rows tr').each(function (index) {
      const order = index + 1;
      $(this).find('.apollo-order-number').text('#' + order);
      $(this).attr('data-order', order);
    });
  }

  function moveRowUp($row) {
    const $prev = $row.prev();
    if ($prev.length) {
      $row.insertBefore($prev);
      updateOrderNumbers();
      recalculateStrictTimeline();
      saveTimetableToHidden();
    }
  }

  function moveRowDown($row) {
    const $next = $row.next();
    if ($next.length) {
      $row.insertAfter($next);
      updateOrderNumbers();
      recalculateStrictTimeline();
      saveTimetableToHidden();
    }
  }

  function updateTimetableData() {
    timetableData = [];
    $('#apollo_timetable_rows tr').each(function (index) {
      const djID = $(this).data('dj-id');
      const start = $(this).find('.timetable-start').val();
      const end = $(this).find('.timetable-end').val();
      const order = index + 1;

      timetableData.push({
        dj: parseInt(djID, 10),
        order: order,
        from: start,
        to: end
      });
    });
  }

  function saveTimetableToHidden() {
    updateTimetableData();
    $('#apollo_event_timetable').val(JSON.stringify(timetableData));
  }

  function showMessage($el, msg, type) {
    const classes = { error: 'notice notice-error', success: 'notice notice-success', info: 'notice notice-info' };
    $el.removeClass('notice-error notice-success notice-info')
      .addClass(classes[type] || 'notice')
      .html('<p>' + msg + '</p>')
      .show();
  }

  function clearDJForm() {
    $('#new_dj_name').val('');
    $('#apollo_dj_form_message').hide();
  }

  function clearLocalForm() {
    $('#new_local_name, #new_local_address, #new_local_city, #new_local_lat, #new_local_lng').val('');
    $('#apollo_local_form_message').hide();
    $('#new_local_address').css('border-color', '');
  }

  function syncAllSelects() {
    const $djList = $('#apollo_dj_list');
    const $djHidden = $('#apollo_event_djs');
    $djHidden.find('option').prop('selected', false);
    $djList.find('input[type="checkbox"]:checked').each(function () {
      const value = $(this).val();
      $djHidden.find('option[value="' + value + '"]').prop('selected', true);
    });

    const $localList = $('#apollo_local_list');
    const $localHidden = $('#apollo_event_local');
    const selectedLocal = $localList.find('input[type="radio"]:checked').val() || '';
    if (selectedLocal) {
      $localHidden.val(selectedLocal);
    }
  }

  function initEnhancedSelects() {
    initDJSelect();
    initLocalSelect();
  }

  function initDJSelect() {
    const $search = $('#apollo_dj_search');
    const $list = $('#apollo_dj_list');
    const $hiddenSelect = $('#apollo_event_djs');
    const $count = $('#apollo_dj_selected_count');

    $search.on('input keyup', function () {
      const query = $(this).val().toLowerCase().trim();
      $list.find('.apollo-select-item').each(function () {
        const $item = $(this);
        const name = ($item.data('dj-name') || '').toLowerCase();
        const matches = query === '' || name.indexOf(query) !== -1;
        const isSelected = $item.find('input[type="checkbox"]').is(':checked');
        if (matches || isSelected) {
          $item.show();
        } else {
          $item.hide();
        }
      });
    });

    $list.on('change', 'input[type="checkbox"]', function () {
      const $checkbox = $(this);
      const $item = $checkbox.closest('.apollo-select-item');
      const isChecked = $checkbox.is(':checked');

      if (isChecked) {
        $item.addClass('selected');
        if (!$item.find('.apollo-check-icon').length) {
          $item.append('<i class="ri-check-line apollo-check-icon"></i>');
        }
      } else {
        $item.removeClass('selected');
        $item.find('.apollo-check-icon').remove();
      }

      syncDJHidden();
      updateDJCount();
      // Fallback: rebuild immediately in case browser suppresses change on hidden select
      rebuildTimetable();
    });

    $hiddenSelect.on('change', function () {
      updateDJCount();
      rebuildTimetable();
    });

    function syncDJHidden() {
      $hiddenSelect.find('option').prop('selected', false);
      $list.find('input[type="checkbox"]:checked').each(function () {
        const value = $(this).val();
        $hiddenSelect.find('option[value="' + value + '"]').prop('selected', true);
      });
      // Force change event so timetable rebuilds when UI checkboxes toggle.
      $hiddenSelect.trigger('change');
    }

    function updateDJCount() {
      const count = $list.find('input[type="checkbox"]:checked').length;
      $count.text(count + ' selecionado' + (count !== 1 ? 's' : ''));
    }

    setTimeout(function () {
      $hiddenSelect.find('option:selected').each(function () {
        const value = $(this).val();
        const $checkbox = $list.find('input[type="checkbox"][value="' + value + '"]');
        if ($checkbox.length) {
          $checkbox.prop('checked', true);
          $checkbox.closest('.apollo-select-item').addClass('selected');
          if (!$checkbox.closest('.apollo-select-item').find('.apollo-check-icon').length) {
            $checkbox.closest('.apollo-select-item').append('<i class="ri-check-line apollo-check-icon"></i>');
          }
        }
      });
      updateDJCount();
    }, 100);
  }

  function initLocalSelect() {
    const $search = $('#apollo_local_search');
    const $list = $('#apollo_local_list');
    const $hiddenSelect = $('#apollo_event_local');

    $search.on('input keyup', function () {
      const query = $(this).val().toLowerCase().trim();
      $list.find('.apollo-select-item').each(function () {
        const $item = $(this);
        const name = ($item.data('local-name') || '').toLowerCase();
        const matches = query === '' || name.indexOf(query) !== -1;
        const isSelected = $item.find('input[type="radio"]').is(':checked');
        if (matches || isSelected) {
          $item.show();
        } else {
          $item.hide();
        }
      });
    });

    $list.on('change', 'input[type="radio"]', function () {
      const value = $(this).val();
      $list.find('.apollo-select-item').removeClass('selected').find('.apollo-check-icon').remove();
      if (value !== '') {
        const $item = $(this).closest('.apollo-select-item');
        $item.addClass('selected');
        if (!$item.find('.apollo-check-icon').length) {
          $item.append('<i class="ri-check-line apollo-check-icon"></i>');
        }
      }
      $hiddenSelect.val(value);
    });

    setTimeout(function () {
      const selectedValue = $hiddenSelect.val();
      if (selectedValue) {
        const $radio = $list.find('input[type="radio"][value="' + selectedValue + '"]');
        if ($radio.length) {
          $radio.prop('checked', true);
          $radio.closest('.apollo-select-item').addClass('selected');
          if (!$radio.closest('.apollo-select-item').find('.apollo-check-icon').length) {
            $radio.closest('.apollo-select-item').append('<i class="ri-check-line apollo-check-icon"></i>');
          }
        }
      }
    }, 100);
  }

  function initImageUploads() {
    $(document).on('click', '.apollo-upload-image-btn', function (e) {
      e.preventDefault();
      const $btn = $(this);
      const targetInput = $btn.data('target') || $btn.closest('.apollo-image-input-row').find('input[type="url"]');
      const $input = typeof targetInput === 'string' ? $('#' + targetInput) : $(targetInput);

      const frame = wp.media({
        title: 'Selecionar Imagem',
        button: { text: 'Usar esta imagem' },
        multiple: false
      });

      frame.on('select', function () {
        const attachment = frame.state().get('selection').first().toJSON();
        $input.val(attachment.url);
        const $row = $input.closest('.apollo-image-input-row, .apollo-field-controls');
        if (!$row.find('.apollo-preview-image-btn').length) {
          const $previewBtn = $('<button>', { type: 'button', class: 'button apollo-preview-image-btn', html: '<span class="dashicons dashicons-visibility"></span>' });
          $previewBtn.insertAfter($btn);
        }
      });

      frame.open();
    });

    $(document).on('click', '.apollo-preview-image-btn', function (e) {
      e.preventDefault();
      const url = $(this).data('url') || $(this).closest('.apollo-image-input-row, .apollo-field-controls').find('input[type="url"]').val();
      if (!url) return;
      showImagePreview(url);
    });
  }

  function showImagePreview(url) {
    let $modal = $('#apollo-image-preview-modal');
    if (!$modal.length) {
      $modal = $('<div>', { id: 'apollo-image-preview-modal', class: 'apollo-image-preview-modal' });
      $modal.html('<div class="apollo-image-preview-content"><button class="apollo-image-preview-close">&times;</button><img src="" alt="Preview"></div>');
      $('body').append($modal);
      $modal.on('click', function (e) {
        if ($(e.target).is('.apollo-image-preview-modal, .apollo-image-preview-close')) {
          $modal.removeClass('active');
          setTimeout(function () { $modal.remove(); }, 200);
        }
      });
    }

    $modal.find('img').attr('src', url);
    $modal.addClass('active');

    const motion = window.motion || window.Motion;
    if (motion && motion.animate) {
      motion.animate($modal[0], { opacity: [0, 1] }, { duration: 0.2 });
    }
  }

  function initMotionAnimations() {
    const motion = window.motion || window.Motion;
    if (!motion || !motion.animate) return;

    $('[data-motion-group="true"]').each(function (index) {
      motion.animate(this, { opacity: [0, 1], transform: ['translateY(10px)', 'translateY(0px)'] }, { duration: 0.3, delay: index * 0.1, easing: 'ease-out' });
    });

    $('[data-motion-select="true"]').each(function () {
      motion.animate(this, { opacity: [0, 1] }, { duration: 0.2, easing: 'ease-out' });
    });

    $('[data-motion-input="true"] input').on('focus', function () {
      const $row = $(this).closest('[data-motion-input="true"]');
      motion.animate($row[0], { scale: [1, 1.01] }, { duration: 0.2, easing: 'ease-out' });
    }).on('blur', function () {
      const $row = $(this).closest('[data-motion-input="true"]');
      motion.animate($row[0], { scale: [1.01, 1] }, { duration: 0.2, easing: 'ease-out' });
    });
  }

})(jQuery);
