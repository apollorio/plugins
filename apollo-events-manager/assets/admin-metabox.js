/**
 * Apollo Events Manager - Admin Metabox JavaScript
 * Dynamic timetable & add DJ/Local dialogs
 */

(function($) {
    'use strict';
    
    // Store current timetable data
    let timetableData = [];
    
    // Initialize
    $(document).ready(function() {
        initDialogs();
        initTimetable();
        bindEvents();
        
        // Load existing timetable on page load
        loadExistingTimetable();
    });
    
    /**
     * Initialize jQuery UI Dialogs
     */
    function initDialogs() {
        // DJ Dialog
        $('#apollo_add_dj_dialog').dialog({
            autoOpen: false,
            modal: true,
            width: 500,
            buttons: [
                {
                    text: 'Adicionar',
                    class: 'button button-primary',
                    click: function() {
                        submitNewDJ();
                    }
                },
                {
                    text: 'Cancelar',
                    class: 'button',
                    click: function() {
                        $(this).dialog('close');
                        clearDJForm();
                    }
                }
            ]
        });
        
        // Local Dialog
        $('#apollo_add_local_dialog').dialog({
            autoOpen: false,
            modal: true,
            width: 500,
            buttons: [
                {
                    text: 'Adicionar',
                    class: 'button button-primary',
                    click: function() {
                        submitNewLocal();
                    }
                },
                {
                    text: 'Cancelar',
                    class: 'button',
                    click: function() {
                        $(this).dialog('close');
                        clearLocalForm();
                    }
                }
            ]
        });
    }
    
    /**
     * Bind events
     */
    function bindEvents() {
        // Open DJ dialog
        $('#apollo_add_new_dj').on('click', function(e) {
            e.preventDefault();
            $('#apollo_add_dj_dialog').dialog('open');
        });
        
        // Open Local dialog
        $('#apollo_add_new_local').on('click', function(e) {
            e.preventDefault();
            $('#apollo_add_local_dialog').dialog('open');
        });
        
        // DJ select change -> rebuild timetable
        $('#apollo_event_djs').on('change', function() {
            rebuildTimetable();
        });
        
        // Refresh timetable button
        $('#apollo_refresh_timetable').on('click', function(e) {
            e.preventDefault();
            rebuildTimetable();
        });
        
        // Before form submit -> save timetable JSON
        $('form#post').on('submit', function() {
            saveTimetableToHidden();
        });
    }
    
    /**
     * Submit new DJ via AJAX
     */
    function submitNewDJ() {
        const name = $('#new_dj_name').val().trim();
        const $msg = $('#apollo_dj_form_message');
        
        if (!name) {
            showMessage($msg, apolloAdmin.i18n.enter_name, 'error');
            return;
        }
        
        // Show loading
        showMessage($msg, 'Verificando...', 'info');
        
        $.ajax({
            url: apolloAdmin.ajax_url,
            type: 'POST',
            data: {
                action: 'apollo_add_new_dj',
                nonce: apolloAdmin.nonce,
                name: name
            },
            success: function(response) {
                if (response.success) {
                    // Add to select
                    const option = $('<option>', {
                        value: response.data.id,
                        text: response.data.name,
                        selected: true
                    });
                    $('#apollo_event_djs').append(option);
                    
                    // Close dialog
                    $('#apollo_add_dj_dialog').dialog('close');
                    clearDJForm();
                    
                    // Rebuild timetable
                    rebuildTimetable();
                    
                    // Success message
                    alert('DJ ' + response.data.name + ' adicionado com sucesso!');
                } else {
                    showMessage($msg, response.data, 'error');
                }
            },
            error: function() {
                showMessage($msg, 'Erro de conexão', 'error');
            }
        });
    }
    
    /**
     * Submit new Local via AJAX
     */
    function submitNewLocal() {
        const name = $('#new_local_name').val().trim();
        const address = $('#new_local_address').val().trim();
        const city = $('#new_local_city').val().trim();
        const $msg = $('#apollo_local_form_message');
        
        if (!name) {
            showMessage($msg, apolloAdmin.i18n.enter_name, 'error');
            return;
        }
        
        showMessage($msg, 'Verificando...', 'info');
        
        $.ajax({
            url: apolloAdmin.ajax_url,
            type: 'POST',
            data: {
                action: 'apollo_add_new_local',
                nonce: apolloAdmin.nonce,
                name: name,
                address: address,
                city: city
            },
            success: function(response) {
                if (response.success) {
                    // Add to select
                    const option = $('<option>', {
                        value: response.data.id,
                        text: response.data.name,
                        selected: true
                    });
                    $('#apollo_event_local').append(option);
                    
                    // Close dialog
                    $('#apollo_add_local_dialog').dialog('close');
                    clearLocalForm();
                    
                    // Success message
                    alert('Local ' + response.data.name + ' adicionado com sucesso!\n' +
                          (city ? 'Geocoding será feito automaticamente.' : ''));
                } else {
                    showMessage($msg, response.data, 'error');
                }
            },
            error: function() {
                showMessage($msg, 'Erro de conexão', 'error');
            }
        });
    }
    
    /**
     * Initialize timetable
     */
    function initTimetable() {
        // Delegate events for dynamic rows
        $('#apollo_timetable_rows').on('change', 'input[type="time"]', function() {
            updateTimetableData();
        });
    }
    
    /**
     * Load existing timetable from PHP
     */
    function loadExistingTimetable() {
        // Check if there's existing data in hidden field
        const existingJSON = $('#apollo_event_timetable').val();
        if (existingJSON) {
            try {
                timetableData = JSON.parse(existingJSON);
            } catch(e) {
                timetableData = [];
            }
        }

        if (!Array.isArray(timetableData)) {
            timetableData = [];
        }
        
        // Rebuild with existing data
        rebuildTimetable();
    }
    
    /**
     * Rebuild timetable rows based on selected DJs
     */
    function rebuildTimetable() {
        const selectedDJs = $('#apollo_event_djs').val() || [];
        const $rows = $('#apollo_timetable_rows');
        const $table = $('#apollo_timetable_table');
        const $empty = $('#apollo_timetable_empty');
        
        $rows.empty();
        
        if (selectedDJs.length === 0) {
            $table.hide();
            $empty.show();
            timetableData = [];
            return;
        }
        
        $table.show();
        $empty.hide();
        
        // Create row for each selected DJ
        selectedDJs.forEach(function(djID) {
            const $option = $('#apollo_event_djs option[value="' + djID + '"]');
            const djName = $option.text();
            
            // Find existing time data
            let existingStart = '';
            let existingEnd = '';
            const existing = timetableData.find(item => item.dj == djID);
            if (existing) {
                existingStart = existing.from || existing.start || '';
                existingEnd = existing.to || existing.end || '';
            }
            
            const row = $('<tr>', { 'data-dj-id': djID });
            
            row.append(
                $('<td>').html('<strong>' + djName + '</strong>')
            );
            
            row.append(
                $('<td>').html(
                    '<input type="time" class="timetable-start" data-dj-id="' + djID + '" value="' + existingStart + '">'
                )
            );
            
            row.append(
                $('<td>').html(
                    '<input type="time" class="timetable-end" data-dj-id="' + djID + '" value="' + existingEnd + '">'
                )
            );
            
            $rows.append(row);
        });
        
        // Update data
        updateTimetableData();
    }
    
    /**
     * Update timetable data from inputs
     */
    function updateTimetableData() {
        timetableData = [];
        
        $('#apollo_timetable_rows tr').each(function() {
            const djID = $(this).data('dj-id');
            const start = $(this).find('.timetable-start').val();
            const end = $(this).find('.timetable-end').val();
            
            if (start) { // Only add if has start time
                timetableData.push({
                    dj: parseInt(djID, 10),
                    from: start,
                    to: end || start // Fallback to start if no end
                });
            }
        });
    }
    
    /**
     * Save timetable data to hidden field before submit
     */
    function saveTimetableToHidden() {
        updateTimetableData();
        $('#apollo_event_timetable').val(JSON.stringify(timetableData));
    }
    
    /**
     * Show message in form
     */
    function showMessage($el, msg, type) {
        const classes = {
            'error': 'notice notice-error',
            'success': 'notice notice-success',
            'info': 'notice notice-info'
        };
        
        $el.removeClass('notice-error notice-success notice-info')
           .addClass(classes[type] || 'notice')
           .html('<p>' + msg + '</p>')
           .show();
    }
    
    /**
     * Clear DJ form
     */
    function clearDJForm() {
        $('#new_dj_name').val('');
        $('#apollo_dj_form_message').hide();
    }
    
    /**
     * Clear Local form
     */
    function clearLocalForm() {
        $('#new_local_name, #new_local_address, #new_local_city').val('');
        $('#apollo_local_form_message').hide();
    }
    
})(jQuery);




