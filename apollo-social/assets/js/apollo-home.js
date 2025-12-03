/**
 * Apollo Home - Frontend View JavaScript
 * 
 * Handles interactions on apollo_home frontend view:
 * - Depoimento (guestbook) submission
 * - Badge tooltips
 * - Trax player interactions
 * 
 * @package Apollo_Social
 * @since 1.4.0
 */

(function($) {
    'use strict';

    const config = window.apolloHomeConfig || {};

    function init() {
        bindDepoimentoForm();
        bindBadgeTooltips();
    }

    /**
     * Bind depoimento (guestbook) form submission
     */
    function bindDepoimentoForm() {
        $('.depoimento-form').on('submit', function(e) {
            e.preventDefault();
            
            const $form = $(this);
            const $submit = $form.find('.depoimento-submit');
            const originalText = $submit.html();
            
            $submit.prop('disabled', true).html('<span class="dashicons dashicons-update spinning"></span>');
            
            $.ajax({
                url: config.ajaxUrl,
                method: 'POST',
                data: {
                    action: 'apollo_builder_add_depoimento',
                    _wpnonce: config.nonce,
                    post_id: $form.find('[name="post_id"]').val(),
                    content: $form.find('[name="content"]').val(),
                    author: $form.find('[name="author"]').val() || ''
                },
                success: function(response) {
                    if (response.success && response.data) {
                        // Add new depoimento to list
                        const depo = response.data;
                        const $item = $(`
                            <div class="depoimento-item new">
                                <div class="depoimento-avatar">
                                    <img src="${depo.avatar}" alt="">
                                </div>
                                <div class="depoimento-content">
                                    <span class="depoimento-author">${escapeHtml(depo.author)}</span>
                                    <span class="depoimento-date">agora</span>
                                    <p class="depoimento-text">${escapeHtml(depo.content)}</p>
                                </div>
                            </div>
                        `);
                        
                        const $list = $form.closest('.apollo-widget-guestbook').find('.depoimentos-list');
                        $list.find('.depoimentos-empty').remove();
                        $list.prepend($item);
                        
                        // Update count
                        const $count = $form.closest('.apollo-widget-guestbook').find('.comment-count');
                        const currentCount = parseInt($count.text().replace(/[()]/g, '')) || 0;
                        $count.text(`(${currentCount + 1})`);
                        
                        // Clear form
                        $form.find('[name="content"]').val('');
                        
                        // Pending approval notice
                        if (!depo.approved) {
                            $item.append('<small class="pending-notice">Aguardando aprovação</small>');
                        }
                    } else {
                        alert(response.data?.message || 'Erro ao enviar');
                    }
                },
                error: function() {
                    alert('Erro ao enviar depoimento');
                },
                complete: function() {
                    $submit.prop('disabled', false).html(originalText);
                }
            });
        });
    }

    /**
     * Bind badge tooltips
     */
    function bindBadgeTooltips() {
        $('.badge-item').on('click', function() {
            const title = $(this).attr('title');
            if (title) {
                // Simple alert for now - could be a modal
                alert(title);
            }
        });
    }

    /**
     * Escape HTML
     */
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Init on ready
    $(document).ready(init);

})(jQuery);

