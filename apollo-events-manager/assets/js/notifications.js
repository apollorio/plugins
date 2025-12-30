/**
 * Apollo Events Manager - Notifications Module
 *
 * @package Apollo_Events_Manager
 * @since 2.0.0
 */

(function($) {
    'use strict';

    /**
     * Toast notification system.
     */
    class ApolloToast {
        constructor() {
            this.container = null;
            this.init();
        }

        init() {
            this.container = $('<div class="apollo-toast-container"></div>');
            $('body').append(this.container);
        }

        show(message, type = 'info', duration = 4000) {
            const icons = {
                success: 'fa-check',
                error: 'fa-times',
                info: 'fa-bell'
            };

            const toast = $(`
                <div class="apollo-toast apollo-toast--${type}">
                    <div class="apollo-toast__icon">
                        <i class="fas ${icons[type] || icons.info}"></i>
                    </div>
                    <div class="apollo-toast__message">${message}</div>
                    <button type="button" class="apollo-toast__close">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            `);

            this.container.append(toast);

            toast.find('.apollo-toast__close').on('click', () => {
                this.dismiss(toast);
            });

            if (duration > 0) {
                setTimeout(() => {
                    this.dismiss(toast);
                }, duration);
            }

            return toast;
        }

        dismiss(toast) {
            toast.addClass('is-exiting');
            setTimeout(() => {
                toast.remove();
            }, 300);
        }

        success(message, duration) {
            return this.show(message, 'success', duration);
        }

        error(message, duration) {
            return this.show(message, 'error', duration);
        }

        info(message, duration) {
            return this.show(message, 'info', duration);
        }
    }

    /**
     * Notification button handler.
     */
    class ApolloNotifyButton {
        constructor(element) {
            this.$wrapper = $(element);
            this.$button = this.$wrapper.find('.apollo-notify-btn');
            this.$form = this.$wrapper.find('.apollo-notify-form');
            this.eventId = this.$wrapper.data('event-id');

            this.init();
        }

        init() {
            if (this.$form.length) {
                this.$form.on('submit', (e) => this.handleFormSubmit(e));
            } else {
                this.$button.on('click', () => this.handleButtonClick());
            }
        }

        handleButtonClick() {
            if (this.$button.hasClass('is-loading')) {
                return;
            }

            this.$button.addClass('is-loading');
            this.$button.find('i').removeClass('fa-bell fa-bell-slash').addClass('fa-spinner');

            $.ajax({
                url: apolloNotifications.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'apollo_subscribe_notifications',
                    nonce: apolloNotifications.nonce,
                    event_id: this.eventId
                },
                success: (response) => {
                    this.$button.removeClass('is-loading');

                    if (response.success) {
                        const isSubscribed = response.data.subscribed;

                        this.$button
                            .toggleClass('is-subscribed', isSubscribed)
                            .find('i')
                            .removeClass('fa-spinner')
                            .addClass(isSubscribed ? 'fa-bell-slash' : 'fa-bell');

                        this.$button.find('span').text(
                            isSubscribed
                                ? apolloNotifications.i18n.subscribed
                                : apolloNotifications.i18n.unsubscribed
                        );

                        window.apolloToast?.success(response.data.message);
                    } else {
                        this.resetButton();
                        window.apolloToast?.error(response.data?.message || apolloNotifications.i18n.error);
                    }
                },
                error: () => {
                    this.resetButton();
                    window.apolloToast?.error(apolloNotifications.i18n.error);
                }
            });
        }

        handleFormSubmit(e) {
            e.preventDefault();

            const email = this.$form.find('input[type="email"]').val();

            if (!email) {
                return;
            }

            this.$button.addClass('is-loading');
            this.$button.find('i').removeClass('fa-bell').addClass('fa-spinner');

            $.ajax({
                url: apolloNotifications.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'apollo_subscribe_notifications',
                    nonce: apolloNotifications.nonce,
                    event_id: this.eventId,
                    email: email
                },
                success: (response) => {
                    this.$button.removeClass('is-loading');

                    if (response.success) {
                        this.$form.find('input').val('').prop('disabled', true);
                        this.$button
                            .addClass('is-subscribed')
                            .find('i')
                            .removeClass('fa-spinner')
                            .addClass('fa-check');
                        this.$button.find('span').text(apolloNotifications.i18n.subscribed);

                        window.apolloToast?.success(response.data.message);
                    } else {
                        this.$button.find('i').removeClass('fa-spinner').addClass('fa-bell');
                        window.apolloToast?.error(response.data?.message || apolloNotifications.i18n.error);
                    }
                },
                error: () => {
                    this.$button.removeClass('is-loading');
                    this.$button.find('i').removeClass('fa-spinner').addClass('fa-bell');
                    window.apolloToast?.error(apolloNotifications.i18n.error);
                }
            });
        }

        resetButton() {
            const isSubscribed = this.$button.hasClass('is-subscribed');
            this.$button.find('i')
                .removeClass('fa-spinner')
                .addClass(isSubscribed ? 'fa-bell-slash' : 'fa-bell');
        }
    }

    /**
     * Notification preferences handler.
     */
    class ApolloNotificationPreferences {
        constructor(element) {
            this.$form = $(element);
            this.init();
        }

        init() {
            this.$form.on('submit', (e) => this.handleSubmit(e));
        }

        handleSubmit(e) {
            e.preventDefault();

            const $submitBtn = this.$form.find('button[type="submit"]');
            const originalText = $submitBtn.text();

            $submitBtn.prop('disabled', true).text('Salvando...');

            $.ajax({
                url: apolloNotifications.ajaxUrl,
                type: 'POST',
                data: this.$form.serialize() + '&action=apollo_save_notification_preferences&nonce=' + apolloNotifications.nonce,
                success: (response) => {
                    $submitBtn.prop('disabled', false).text(originalText);

                    if (response.success) {
                        window.apolloToast?.success(response.data.message || 'Preferências salvas!');
                    } else {
                        window.apolloToast?.error(response.data?.message || apolloNotifications.i18n.error);
                    }
                },
                error: () => {
                    $submitBtn.prop('disabled', false).text(originalText);
                    window.apolloToast?.error(apolloNotifications.i18n.error);
                }
            });
        }
    }

    /**
     * Notification Center panel.
     */
    class ApolloNotificationCenter {
        constructor() {
            this.$panel = null;
            this.$trigger = null;
            this.isOpen = false;

            this.init();
        }

        init() {
            this.$trigger = $('.apollo-notification-trigger');

            if (!this.$trigger.length) {
                return;
            }

            this.createPanel();
            this.bindEvents();
            this.loadNotifications();
        }

        createPanel() {
            this.$panel = $(`
                <div class="apollo-notification-center">
                    <div class="apollo-notification-center__header">
                        <h3 class="apollo-notification-center__title">Notificações</h3>
                        <button type="button" class="apollo-notification-center__close">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <div class="apollo-notification-center__body">
                        <div class="apollo-notification-center__empty">
                            <i class="fas fa-bell-slash"></i>
                            <p>Nenhuma notificação</p>
                        </div>
                    </div>
                </div>
            `);

            $('body').append(this.$panel);
        }

        bindEvents() {
            this.$trigger.on('click', () => this.toggle());
            this.$panel.find('.apollo-notification-center__close').on('click', () => this.close());

            $(document).on('click', (e) => {
                if (this.isOpen &&
                    !$(e.target).closest('.apollo-notification-center').length &&
                    !$(e.target).closest('.apollo-notification-trigger').length) {
                    this.close();
                }
            });

            $(document).on('keydown', (e) => {
                if (e.key === 'Escape' && this.isOpen) {
                    this.close();
                }
            });
        }

        toggle() {
            this.isOpen ? this.close() : this.open();
        }

        open() {
            this.$panel.addClass('is-open');
            this.isOpen = true;
            $('body').css('overflow', 'hidden');
        }

        close() {
            this.$panel.removeClass('is-open');
            this.isOpen = false;
            $('body').css('overflow', '');
        }

        loadNotifications() {
            // Could load from server via AJAX
        }

        addNotification(notification) {
            const $body = this.$panel.find('.apollo-notification-center__body');
            const $empty = $body.find('.apollo-notification-center__empty');

            if ($empty.length) {
                $empty.remove();
            }

            const $item = $(`
                <div class="apollo-notification-item ${notification.unread ? 'is-unread' : ''}">
                    <div class="apollo-notification-item__icon">
                        <i class="fas ${notification.icon || 'fa-bell'}"></i>
                    </div>
                    <div class="apollo-notification-item__content">
                        <h4 class="apollo-notification-item__title">${notification.title}</h4>
                        <p class="apollo-notification-item__message">${notification.message}</p>
                    </div>
                    <div class="apollo-notification-item__time">${notification.time}</div>
                </div>
            `);

            $body.prepend($item);
            this.updateBadge();
        }

        updateBadge() {
            const count = this.$panel.find('.apollo-notification-item.is-unread').length;
            const $badge = this.$trigger.find('.apollo-notification-badge__count');

            if ($badge.length) {
                $badge.text(count > 0 ? count : '');
            }
        }
    }

    /**
     * Initialize on DOM ready.
     */
    $(function() {
        window.apolloToast = new ApolloToast();

        $('.apollo-notify-wrapper').each(function() {
            new ApolloNotifyButton(this);
        });

        $('.apollo-preferences-form').each(function() {
            new ApolloNotificationPreferences(this);
        });

        window.apolloNotificationCenter = new ApolloNotificationCenter();
    });

    window.ApolloToast = ApolloToast;
    window.ApolloNotifyButton = ApolloNotifyButton;
    window.ApolloNotificationPreferences = ApolloNotificationPreferences;
    window.ApolloNotificationCenter = ApolloNotificationCenter;

})(jQuery);

