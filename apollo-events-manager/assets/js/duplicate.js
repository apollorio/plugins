/**
 * Apollo Events Manager - Duplicate Module JavaScript
 *
 * @package Apollo_Events_Manager
 * @since 2.0.0
 */

(function($) {
    'use strict';

    /**
     * Apollo Duplicate Manager
     */
    class ApolloDuplicate {
        constructor() {
            this.config = window.apolloDuplicate || {};
            this.init();
        }

        init() {
            this.bindEvents();
        }

        bindEvents() {
            $(document)
                .on('click', '.apollo-create-recurring', this.handleCreateRecurring.bind(this))
                .on('click', '.apollo-quick-duplicate', this.handleQuickDuplicate.bind(this));
        }

        /**
         * Handle create recurring events
         */
        handleCreateRecurring(e) {
            e.preventDefault();

            const $btn = $(e.currentTarget);
            const postId = $btn.data('post-id');
            const $form = $btn.closest('.apollo-recurring-form');
            const $progress = $form.find('.apollo-recurring-progress');

            const count = parseInt($form.find('#apollo_recurring_count').val(), 10) || 4;
            const interval = $form.find('#apollo_recurring_interval').val() || 'week';

            // Show progress
            $btn.prop('disabled', true);
            $progress.show().find('.progress-text').text(this.config.i18n.creating);

            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'apollo_create_recurring',
                    nonce: this.config.nonce,
                    post_id: postId,
                    count: count,
                    interval: interval
                },
                success: (response) => {
                    if (response.success) {
                        this.showCreatedList($form, response.data.created);
                        $progress.find('.progress-text').text(
                            response.data.created.length + ' ' + this.config.i18n.created
                        );
                    } else {
                        this.showError($progress, response.data.message);
                    }
                },
                error: () => {
                    this.showError($progress, this.config.i18n.error);
                },
                complete: () => {
                    $btn.prop('disabled', false);
                    $progress.find('.spinner').removeClass('is-active');
                }
            });
        }

        /**
         * Show created events list
         */
        showCreatedList($form, events) {
            let $list = $form.find('.apollo-created-events');

            if (!$list.length) {
                $list = $('<div class="apollo-created-events"><h5>Eventos criados:</h5><ul></ul></div>');
                $form.append($list);
            }

            const $ul = $list.find('ul').empty();

            events.forEach(event => {
                $ul.append(`<li><a href="${event.link}" target="_blank">${event.title}</a></li>`);
            });
        }

        /**
         * Show error message
         */
        showError($container, message) {
            $container.find('.progress-text')
                      .text(message)
                      .css('color', '#d63638');
        }

        /**
         * Handle quick duplicate (from row actions)
         */
        handleQuickDuplicate(e) {
            // Let it proceed normally as a link
            const $link = $(e.currentTarget);
            $link.addClass('is-loading');
        }
    }

    /**
     * Duplicate Confirmation Modal
     */
    class DuplicateModal {
        constructor() {
            this.$modal = null;
            this.callback = null;
        }

        show(options) {
            const defaults = {
                title: 'Confirmar',
                message: 'Tem certeza?',
                confirmText: 'Confirmar',
                cancelText: 'Cancelar',
                icon: 'dashicons-warning',
                type: 'warning'
            };

            const settings = $.extend({}, defaults, options);

            this.createModal(settings);
            this.bindModalEvents();

            setTimeout(() => this.$modal.addClass('is-visible'), 10);
        }

        createModal(settings) {
            this.$modal = $(`
                <div class="apollo-confirm-modal">
                    <div class="apollo-confirm-modal__content">
                        <div class="apollo-confirm-modal__icon">
                            <span class="dashicons ${settings.icon}"></span>
                        </div>
                        <h3 class="apollo-confirm-modal__title">${settings.title}</h3>
                        <p class="apollo-confirm-modal__text">${settings.message}</p>
                        <div class="apollo-confirm-modal__actions">
                            <button type="button" class="button apollo-modal-cancel">
                                ${settings.cancelText}
                            </button>
                            <button type="button" class="button button-primary apollo-modal-confirm">
                                ${settings.confirmText}
                            </button>
                        </div>
                    </div>
                </div>
            `);

            $('body').append(this.$modal);
        }

        bindModalEvents() {
            this.$modal.on('click', '.apollo-modal-cancel', () => this.close());
            this.$modal.on('click', '.apollo-modal-confirm', () => {
                if (typeof this.callback === 'function') {
                    this.callback();
                }
                this.close();
            });
            this.$modal.on('click', (e) => {
                if ($(e.target).is('.apollo-confirm-modal')) {
                    this.close();
                }
            });
        }

        close() {
            this.$modal.removeClass('is-visible');
            setTimeout(() => this.$modal.remove(), 200);
        }

        confirm(callback) {
            this.callback = callback;
            return this;
        }
    }

    /**
     * Bulk Duplicate Handler
     */
    class BulkDuplicateHandler {
        constructor() {
            this.init();
        }

        init() {
            // Highlight duplicated rows
            const urlParams = new URLSearchParams(window.location.search);
            const duplicated = urlParams.get('apollo_duplicated');

            if (duplicated) {
                this.showSuccessNotice(parseInt(duplicated, 10));
            }
        }

        showSuccessNotice(count) {
            // The notice is handled by PHP, but we can add animation
            $('.notice.notice-success').addClass('apollo-duplicate-notice');
        }
    }

    /**
     * Template Manager
     */
    class TemplateManager {
        constructor() {
            this.templates = [];
            this.init();
        }

        init() {
            this.loadTemplates();
            this.bindEvents();
        }

        loadTemplates() {
            // Templates would be loaded from server
            // For now, check localStorage for any saved templates
            const saved = localStorage.getItem('apollo_event_templates');
            if (saved) {
                try {
                    this.templates = JSON.parse(saved);
                } catch (e) {
                    this.templates = [];
                }
            }
        }

        bindEvents() {
            $(document).on('click', '.apollo-use-template', (e) => {
                e.preventDefault();
                const templateId = $(e.currentTarget).data('template-id');
                this.useTemplate(templateId);
            });
        }

        useTemplate(templateId) {
            const template = this.templates.find(t => t.id === templateId);
            if (!template) return;

            // Redirect to create new event from template
            window.location.href = `${window.ajaxurl.replace('admin-ajax.php', 'admin.php')}?action=apollo_duplicate_event&post=${template.source_id}`;
        }

        saveTemplate(name, sourceId) {
            const template = {
                id: 'template_' + Date.now(),
                name: name,
                source_id: sourceId,
                created: new Date().toISOString()
            };

            this.templates.push(template);
            localStorage.setItem('apollo_event_templates', JSON.stringify(this.templates));

            return template;
        }

        deleteTemplate(templateId) {
            this.templates = this.templates.filter(t => t.id !== templateId);
            localStorage.setItem('apollo_event_templates', JSON.stringify(this.templates));
        }
    }

    /**
     * Date Picker Enhancement
     */
    class DatePickerEnhancement {
        constructor() {
            this.init();
        }

        init() {
            if ($.fn.datepicker) {
                $('.apollo-date-input').datepicker({
                    dateFormat: 'yy-mm-dd',
                    firstDay: 0,
                    showOtherMonths: true,
                    selectOtherMonths: true
                });
            }
        }
    }

    /**
     * Initialize on document ready
     */
    $(function() {
        // Only initialize on admin pages
        if (typeof pagenow === 'undefined' || pagenow !== 'event_listing') {
            // Still check for tools page
            if ($('.apollo-tools-grid').length === 0 && $('.apollo-duplicate-box').length === 0) {
                return;
            }
        }

        // Initialize main duplicate handler
        new ApolloDuplicate();

        // Initialize bulk handler
        new BulkDuplicateHandler();

        // Initialize template manager
        window.apolloTemplateManager = new TemplateManager();

        // Initialize date picker
        new DatePickerEnhancement();
    });

    // Export to global scope
    window.ApolloDuplicate = ApolloDuplicate;
    window.DuplicateModal = DuplicateModal;

})(jQuery);
