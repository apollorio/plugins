/**
 * Apollo Document Signature Handler
 * File: assets/js/apollo-document-sign.js
 */

(function($) {
    'use strict';

    const ApolloDocSign = {
        init() {
            this.bindEvents();
            this.checkLocalStorage();
        },

        bindEvents() {
            $('#btn-sign-govbr, #btn-sign-icp').on('click', (e) => this.handleSign(e));
            $('#chk-terms, #chk-rep').on('change', () => this.validateTerms());
        },

        validateTerms() {
            const termsChecked = $('#chk-terms').is(':checked');
            const repChecked = $('#chk-rep').is(':checked');
            const allChecked = termsChecked && repChecked;

            $('#btn-sign-govbr, #btn-sign-icp').prop('disabled', !allChecked);
            $('#sign-error').toggleClass('hidden', allChecked);

            return allChecked;
        },

        async handleSign(e) {
            e.preventDefault();

            if (!this.validateTerms()) return;

            const provider = $(e.currentTarget).data('provider');
            const $btn = $(e.currentTarget);
            const originalText = $btn.html();

            // Disable buttons
            $('#btn-sign-govbr, #btn-sign-icp').prop('disabled', true);
            $btn.html('<i class="ri-loader-4-line animate-spin"></i> Processando...');

            try {
                const response = await $.ajax({
                    url: apolloDocData.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'apollo_sign_document',
                        nonce: apolloDocData.nonce,
                        document_id: apolloDocData.documentId,
                        provider: provider
                    }
                });

                if (response.success) {
                    this.showSuccess(response.data);
                    this.updateUI(response.data);
                } else {
                    this.showError(response.data.message);
                    $('#btn-sign-govbr, #btn-sign-icp').prop('disabled', false);
                    $btn.html(originalText);
                }
            } catch (error) {
                this.showError('Erro ao processar assinatura. Tente novamente.');
                $('#btn-sign-govbr, #btn-sign-icp').prop('disabled', false);
                $btn.html(originalText);
            }
        },

        showSuccess(data) {
            $('#signed-at').text('Data: ' + data.signed_at);
            $('#signed-code').text('Cod: ' + data.code);
            $('#signed-hash').text('Hash: ' + data.hash.substring(0, 40) + '...');
            $('#sign-result').removeClass('hidden');

            // Store in localStorage for reload persistence
            localStorage.setItem('doc_signed_' + apolloDocData.documentId, JSON.stringify(data));
        },

        showError(message) {
            alert(message || 'Erro ao assinar documento');
        },

        updateUI(data) {
            // Update header status
            const $headerPill = $('#doc-status-pill-header');
            $headerPill.removeClass('border-amber-200 bg-amber-50 text-amber-700');
            $headerPill.addClass('border-emerald-200 bg-emerald-50 text-emerald-700');
            $headerPill.html('<span class="inline-flex h-2 w-2 rounded-full bg-emerald-500"></span> Assinado');

            // Update signer card
            const $signerCard = $('#signer-you-card');
            $signerCard.removeClass('bg-amber-50 border-amber-100');
            $signerCard.addClass('bg-emerald-50 border-emerald-100');

            const $signerStatus = $('#signer-you-status');
            $signerStatus.removeClass('text-amber-600');
            $signerStatus.addClass('text-emerald-700');
            $signerStatus.html('<i class="ri-check-line"></i> Assinado');

            // Update count
            const currentCount = $('#sign-count-label').text();
            const [signed, total] = currentCount.split('/').map(Number);
            $('#sign-count-label').text((signed + 1) + '/' + total);

            // Update stepper
            $('[data-step="2"]').removeClass('bg-slate-200').addClass('bg-slate-900');
            $('[data-step="3"]').removeClass('bg-slate-200').addClass('bg-slate-900');
        },

        checkLocalStorage() {
            const stored = localStorage.getItem('doc_signed_' + apolloDocData.documentId);
            if (stored) {
                const data = JSON.parse(stored);
                this.showSuccess(data);
                $('#btn-sign-govbr, #btn-sign-icp').prop('disabled', true);
            }
        }
    };

    $(document).ready(() => ApolloDocSign.init());

})(jQuery);
