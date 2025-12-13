/**
 * Apollo Admin Cabin JavaScript
 *
 * @package Apollo_Core
 * @since 4.0.0
 */

(function ($) {
    'use strict';

    const ApolloCabin = {
        /**
         * Initialize
         */
        init: function () {
            this.bindEvents();
        },

        /**
         * Bind event handlers
         */
        bindEvents: function () {
            // Toggle details in logs
            $(document).on('click', '.toggle-details', this.toggleLogDetails);

            // Unblock IP button
            $(document).on('click', '.unblock-ip-btn', this.handleUnblockIP);

            // Module toggle visual update
            $(document).on('change', '.apollo-toggle input', this.updateToggleLabel);

            // Confirm dangerous actions
            $(document).on('submit', '.block-ip-form', this.confirmBlockIP);
        },

        /**
         * Toggle log details visibility
         */
        toggleLogDetails: function (e) {
            e.preventDefault();
            const $btn = $(this);
            const $details = $btn.siblings('.log-details');

            $details.toggleClass('hidden');
            $btn.text($details.hasClass('hidden') ? apolloCabin.i18n.ver || 'Ver' : 'Ocultar');
        },

        /**
         * Handle unblock IP
         */
        handleUnblockIP: function (e) {
            e.preventDefault();

            if (!confirm(apolloCabin.i18n.confirmBlockIP || 'Tem certeza?')) {
                return;
            }

            const $btn = $(this);
            const hash = $btn.data('hash');
            const $row = $btn.closest('tr');

            $btn.prop('disabled', true).text('...');

            $.ajax({
                url: apolloCabin.restUrl + '/mod/unblock-ip',
                method: 'POST',
                beforeSend: function (xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', apolloCabin.nonce);
                },
                data: {
                    ip_hash: hash
                },
                success: function (response) {
                    if (response.success) {
                        $row.fadeOut(300, function () {
                            $(this).remove();
                        });
                    } else {
                        alert(response.message || apolloCabin.i18n.error);
                        $btn.prop('disabled', false).text('Desbloquear');
                    }
                },
                error: function () {
                    alert(apolloCabin.i18n.error);
                    $btn.prop('disabled', false).text('Desbloquear');
                }
            });
        },

        /**
         * Update toggle label when changed
         */
        updateToggleLabel: function () {
            const $input = $(this);
            const $card = $input.closest('.apollo-cabin-card');
            const $label = $input.closest('.apollo-toggle').find('.toggle-label');

            if ($input.is(':checked')) {
                $card.removeClass('is-disabled').addClass('is-enabled');
                $label.text('Ativo');
            } else {
                $card.removeClass('is-enabled').addClass('is-disabled');
                $label.text('Inativo');
            }
        },

        /**
         * Confirm block IP action
         */
        confirmBlockIP: function (e) {
            const ip = $(this).find('input[name="block_ip"]').val();

            if (!ip) {
                alert('Por favor, insira um endereço IP.');
                e.preventDefault();
                return false;
            }

            if (!confirm(apolloCabin.i18n.confirmBlockIP || 'Tem certeza que deseja bloquear este IP?')) {
                e.preventDefault();
                return false;
            }
        }
    };

    // Initialize on document ready
    $(document).ready(function () {
        ApolloCabin.init();
    });

})(jQuery);
