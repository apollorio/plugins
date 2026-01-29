/**
 * Apollo User Dashboard JavaScript
 * File: assets/js/apollo-dashboard.js
 */

(function($) {
    'use strict';

    const ApolloDashboard = {
        init() {
            this.bindTabSwitching();
            this.bindSettingsForm();
            this.restoreActiveTab();
        },

        bindTabSwitching() {
            $('.tab-btn').on('click', function() {
                const tabId = $(this).data('tab');
                ApolloDashboard.switchTab(tabId, this);
            });
        },

        switchTab(tabId, btn) {
            // Hide all sections
            $('.content-section').removeClass('active');
            // Show target section
            $('#tab-' + tabId).addClass('active');
            
            // Update buttons
            $('.tab-btn').removeClass('active');
            $(btn).addClass('active');
            
            // Save to localStorage
            localStorage.setItem('apollo_active_tab', tabId);
        },

        restoreActiveTab() {
            const savedTab = localStorage.getItem('apollo_active_tab');
            if (savedTab) {
                const $btn = $('.tab-btn[data-tab="' + savedTab + '"]');
                if ($btn.length) {
                    this.switchTab(savedTab, $btn[0]);
                }
            }
        },

        bindSettingsForm() {
            $('#user-settings-form').on('submit', function(e) {
                e.preventDefault();
                
                const $form = $(this);
                const $btn = $form.find('button[type="submit"]');
                const $message = $('#settings-message');
                const originalText = $btn.html();
                
                // Validate password fields
                const newPass = $('#new_password').val();
                const confirmPass = $('#confirm_password').val();
                
                if (newPass && newPass !== confirmPass) {
                    $message.removeClass('success').addClass('error').text('As senhas não coincidem').show();
                    return;
                }
                
                // Show loading
                $btn.prop('disabled', true).html('<i class="ri-loader-4-line"></i> Salvando...');
                $message.hide();
                
                // Prepare form data
                const formData = new FormData(this);
                
                // Submit
                $.ajax({
                    url: $form.attr('action'),
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        if (response.success) {
                            $message.removeClass('error').addClass('success').text(response.data.message || 'Alterações salvas com sucesso!').show();
                            
                            // Clear password fields
                            $('#current_password, #new_password, #confirm_password').val('');
                            
                            // Reload if display name changed
                            if (response.data.reload) {
                                setTimeout(() => location.reload(), 1500);
                            }
                        } else {
                            $message.removeClass('success').addClass('error').text(response.data.message || 'Erro ao salvar alterações').show();
                        }
                    },
                    error: function() {
                        $message.removeClass('success').addClass('error').text('Erro de conexão. Tente novamente.').show();
                    },
                    complete: function() {
                        $btn.prop('disabled', false).html(originalText);
                    }
                });
            });
        }
    };

    $(document).ready(() => ApolloDashboard.init());

})(jQuery);
