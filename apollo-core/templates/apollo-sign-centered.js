/**
 * Apollo Sign Centered - JavaScript
 * File: assets/js/apollo-sign-centered.js
 */

(function($) {
    'use strict';

    const ApolloSignCentered = {
        init() {
            this.bindSignButton();
            this.bindRefuseButton();
        },

        bindSignButton() {
            $('#btn-sign-doc').on('click', function(e) {
                e.preventDefault();
                
                const $btn = $(this);
                const docId = $btn.data('doc-id');
                const originalHtml = $btn.html();
                
                // Confirm action
                if (!confirm('Confirma a assinatura deste documento?')) {
                    return;
                }
                
                // Show loading
                $btn.prop('disabled', true).html('<i class="ri-loader-4-line animate-spin text-[18px]"></i> Processando...');
                
                // Submit signature
                $.ajax({
                    url: apolloSignData.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'apollo_sign_document',
                        nonce: apolloSignData.nonce,
                        document_id: docId,
                        provider: 'govbr'
                    },
                    success: function(response) {
                        if (response.success) {
                            // Show success and reload
                            alert('Documento assinado com sucesso!');
                            location.reload();
                        } else {
                            alert(response.data.message || 'Erro ao assinar documento');
                            $btn.prop('disabled', false).html(originalHtml);
                        }
                    },
                    error: function() {
                        alert('Erro de conexão. Tente novamente.');
                        $btn.prop('disabled', false).html(originalHtml);
                    }
                });
            });
        },

        bindRefuseButton() {
            $('#btn-refuse-doc').on('click', function(e) {
                e.preventDefault();
                
                if (!confirm('Tem certeza que deseja recusar a assinatura deste documento?')) {
                    return;
                }
                
                alert('Assinatura recusada. Você será redirecionado.');
                window.location.href = apolloSignData.homeUrl + '/documents';
            });
        }
    };

    $(document).ready(() => ApolloSignCentered.init());

})(jQuery);
