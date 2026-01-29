/**
 * Apollo Builder Assets - Admin JavaScript
 * 
 * Handles media frame and repeater functionality for assets admin page.
 * 
 * @package Apollo_Social
 * @since 1.4.0
 */

(function($) {
    'use strict';

    const config = window.apolloBuilderAssetsAdmin || {};

    function init() {
        bindAddButtons();
        bindRemoveButtons();
        bindImageSelectors();
        bindFormSubmit();
    }

    /**
     * Bind "Add" buttons
     */
    function bindAddButtons() {
        $('.apollo-add-asset').on('click', function() {
            const type = $(this).data('type');
            const $repeater = $(`#${type}s-repeater`);
            const index = $repeater.find('.apollo-asset-row').length;
            const newId = type + '_' + Date.now();
            
            const $row = $(`
                <div class="apollo-asset-row" data-index="${index}">
                    <div class="asset-preview">
                        <span class="dashicons dashicons-format-image"></span>
                    </div>
                    
                    <div class="asset-fields">
                        <input type="hidden" name="${type}s[${index}][id]" value="${newId}" class="asset-id">
                        <input type="hidden" name="${type}s[${index}][image_id]" value="" class="asset-image-id">
                        
                        <input type="text" 
                               name="${type}s[${index}][label]" 
                               value="" 
                               placeholder="${config.i18n?.label || 'Label'}"
                               class="regular-text asset-label">
                    </div>
                    
                    <div class="asset-actions">
                        <button type="button" class="button select-image">
                            <span class="dashicons dashicons-format-image"></span>
                            ${config.i18n?.selectImage || 'Select'}
                        </button>
                        <button type="button" class="button button-link-delete remove-row">
                            <span class="dashicons dashicons-trash"></span>
                        </button>
                    </div>
                </div>
            `);
            
            $repeater.append($row);
        });
    }

    /**
     * Bind remove row buttons
     */
    function bindRemoveButtons() {
        $(document).on('click', '.remove-row', function() {
            if (confirm(config.i18n?.confirmRemove || 'Remove this item?')) {
                $(this).closest('.apollo-asset-row').fadeOut(200, function() {
                    $(this).remove();
                    reindexRows();
                });
            }
        });
    }

    /**
     * Re-index rows after removal
     */
    function reindexRows() {
        $('.apollo-asset-repeater').each(function() {
            const type = $(this).data('type');
            $(this).find('.apollo-asset-row').each(function(index) {
                $(this).attr('data-index', index);
                $(this).find('[name]').each(function() {
                    const name = $(this).attr('name');
                    const newName = name.replace(/\[\d+\]/, `[${index}]`);
                    $(this).attr('name', newName);
                });
            });
        });
    }

    /**
     * Bind image selector buttons
     */
    function bindImageSelectors() {
        $(document).on('click', '.select-image', function(e) {
            e.preventDefault();
            
            const $row = $(this).closest('.apollo-asset-row');
            const $imageIdInput = $row.find('.asset-image-id');
            const $preview = $row.find('.asset-preview');
            
            // Create media frame
            const frame = wp.media({
                title: config.i18n?.selectImage || 'Select Image',
                button: {
                    text: config.i18n?.useImage || 'Use Image'
                },
                multiple: false,
                library: {
                    type: 'image'
                }
            });
            
            frame.on('select', function() {
                const attachment = frame.state().get('selection').first().toJSON();
                
                $imageIdInput.val(attachment.id);
                
                const thumbUrl = attachment.sizes?.thumbnail?.url || attachment.url;
                $preview.html(`<img src="${thumbUrl}" alt="">`);
            });
            
            frame.open();
        });
    }

    /**
     * Bind form submit
     */
    function bindFormSubmit() {
        $('#apollo-builder-assets-form').on('submit', function(e) {
            e.preventDefault();
            
            const $form = $(this);
            const $btn = $('#save-assets');
            const $spinner = $form.find('.spinner');
            const $status = $form.find('.save-status');
            
            $btn.prop('disabled', true);
            $spinner.addClass('is-active');
            $status.text(config.i18n?.saving || 'Saving...');
            
            // Collect data
            const formData = {
                action: 'apollo_builder_save_assets',
                nonce: config.nonce,
                stickers: [],
                textures: []
            };
            
            // Collect stickers
            $('#stickers-repeater .apollo-asset-row').each(function() {
                const id = $(this).find('.asset-id').val();
                const label = $(this).find('.asset-label').val();
                const imageId = $(this).find('.asset-image-id').val();
                
                if (imageId) {
                    formData.stickers.push({ id, label, image_id: imageId });
                }
            });
            
            // Collect textures
            $('#textures-repeater .apollo-asset-row').each(function() {
                const id = $(this).find('.asset-id').val();
                const label = $(this).find('.asset-label').val();
                const imageId = $(this).find('.asset-image-id').val();
                
                if (imageId) {
                    formData.textures.push({ id, label, image_id: imageId });
                }
            });
            
            $.ajax({
                url: config.ajaxUrl,
                method: 'POST',
                data: formData,
                success: function(response) {
                    if (response.success) {
                        $status.text(config.i18n?.saved || 'Saved!').css('color', 'green');
                    } else {
                        $status.text(response.data?.message || config.i18n?.error || 'Error').css('color', 'red');
                    }
                },
                error: function() {
                    $status.text(config.i18n?.error || 'Error saving').css('color', 'red');
                },
                complete: function() {
                    $btn.prop('disabled', false);
                    $spinner.removeClass('is-active');
                    
                    setTimeout(() => {
                        $status.text('');
                    }, 3000);
                }
            });
        });
    }

    // Init
    $(document).ready(init);

})(jQuery);

