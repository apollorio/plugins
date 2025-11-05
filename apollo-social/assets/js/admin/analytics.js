/**
 * Apollo Analytics Admin JavaScript
 */

(function($) {
    'use strict';

    // Analytics Admin Class
    var ApolloAnalyticsAdmin = {
        
        init: function() {
            this.bindEvents();
            this.loadAnalyticsStats();
            this.setupAutoRefresh();
        },
        
        bindEvents: function() {
            // Driver selection change
            $('#analytics_driver').on('change', this.toggleDriverConfig);
            
            // Test connection button
            $(document).on('click', '.test-connection-btn', this.testConnection);
            
            // Refresh stats button
            $(document).on('click', '.refresh-stats-btn', this.loadAnalyticsStats);
            
            // Form validation
            $('form[action*="apollo_save_analytics_config"]').on('submit', this.validateForm);
        },
        
        toggleDriverConfig: function() {
            var selectedDriver = $(this).val();
            
            // Hide all driver configs
            $('.plausible-config, .matomo-config, .umami-config').hide();
            
            // Show selected driver config
            $('.' + selectedDriver + '-config').show();
        },
        
        loadAnalyticsStats: function() {
            var $grid = $('#analytics-stats-grid');
            
            // Show loading state
            $grid.html('<div class="loading">Carregando estatísticas...</div>');
            
            $.ajax({
                url: apolloAnalyticsAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'apollo_analytics_stats',
                    _ajax_nonce: apolloAnalyticsAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        ApolloAnalyticsAdmin.renderStats(response.data);
                    } else {
                        ApolloAnalyticsAdmin.showError($grid, 'Erro ao carregar estatísticas: ' + (response.data || 'Erro desconhecido'));
                    }
                },
                error: function(xhr, status, error) {
                    ApolloAnalyticsAdmin.showError($grid, 'Erro na conexão: ' + error);
                }
            });
        },
        
        renderStats: function(data) {
            var $grid = $('#analytics-stats-grid');
            
            if (data.error) {
                this.showError($grid, data.error);
                return;
            }
            
            var html = '';
            
            if (data.stats && Object.keys(data.stats).length > 0) {
                $.each(data.stats, function(key, stat) {
                    html += '<div class="stat-card">';
                    html += '<span class="stat-icon">' + stat.icon + '</span>';
                    html += '<div class="stat-value">' + stat.value + '</div>';
                    html += '<div class="stat-label">' + stat.label + '</div>';
                    html += '</div>';
                });
            } else {
                html = '<div class="loading">Nenhum dado disponível. Verifique a configuração da API.</div>';
            }
            
            $grid.html(html);
            
            // Update last refresh time
            this.updateLastRefreshTime();
        },
        
        showError: function($container, message) {
            var html = '<div class="notice notice-error"><p>' + message + '</p></div>';
            $container.html(html);
        },
        
        updateLastRefreshTime: function() {
            var now = new Date();
            var timeString = now.toLocaleTimeString();
            
            $('.last-refresh').remove();
            $('#analytics-stats-grid').after('<p class="last-refresh text-muted">Última atualização: ' + timeString + '</p>');
        },
        
        setupAutoRefresh: function() {
            // Auto-refresh every 5 minutes
            setInterval(function() {
                ApolloAnalyticsAdmin.loadAnalyticsStats();
            }, 300000); // 5 minutes
        },
        
        testConnection: function(e) {
            e.preventDefault();
            
            var $btn = $(this);
            var originalText = $btn.text();
            
            $btn.text('Testando...').prop('disabled', true);
            
            // Get current form values
            var domain = $('#plausible_domain').val();
            var apiKey = $('#plausible_api_key').val();
            var apiBase = $('#plausible_api_base').val();
            
            if (!domain) {
                alert('Por favor, preencha o domínio primeiro.');
                $btn.text(originalText).prop('disabled', false);
                return;
            }
            
            $.ajax({
                url: apolloAnalyticsAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'apollo_test_analytics_connection',
                    _ajax_nonce: apolloAnalyticsAdmin.nonce,
                    domain: domain,
                    api_key: apiKey,
                    api_base: apiBase
                },
                success: function(response) {
                    if (response.success) {
                        alert('Conexão testada com sucesso! ✅');
                    } else {
                        alert('Erro na conexão: ' + (response.data || 'Erro desconhecido'));
                    }
                },
                error: function(xhr, status, error) {
                    alert('Erro na requisição: ' + error);
                },
                complete: function() {
                    $btn.text(originalText).prop('disabled', false);
                }
            });
        },
        
        validateForm: function(e) {
            var isEnabled = $('#analytics_enabled').is(':checked');
            
            if (isEnabled) {
                var domain = $('#plausible_domain').val();
                
                if (!domain) {
                    alert('Por favor, preencha o domínio do site para habilitar o analytics.');
                    e.preventDefault();
                    return false;
                }
                
                // Validate domain format
                var domainRegex = /^[a-zA-Z0-9][a-zA-Z0-9-]{1,61}[a-zA-Z0-9]\.[a-zA-Z]{2,}$/;
                if (!domainRegex.test(domain)) {
                    alert('Por favor, insira um domínio válido (ex: exemplo.com).');
                    e.preventDefault();
                    return false;
                }
            }
            
            return true;
        }
    };
    
    // Event Tracking Helper Functions
    window.apolloAnalyticsAdminHelpers = {
        
        // Simulate event tracking for testing
        simulateEvent: function(eventName, props) {
            console.log('Simulating event:', eventName, props);
            
            // Show notification
            this.showNotification('Evento simulado: ' + eventName, 'success');
        },
        
        showNotification: function(message, type) {
            type = type || 'info';
            
            var $notification = $('<div class="notice notice-' + type + ' is-dismissible"><p>' + message + '</p></div>');
            
            $('.apollo-analytics-admin').prepend($notification);
            
            // Auto-remove after 3 seconds
            setTimeout(function() {
                $notification.fadeOut(function() {
                    $(this).remove();
                });
            }, 3000);
        },
        
        // Update event count in real-time (for demo purposes)
        updateEventCount: function(eventName, count) {
            var $eventCount = $('.event-count[data-event="' + eventName + '"]');
            if ($eventCount.length) {
                $eventCount.text(count);
            }
        }
    };
    
    // Configuration Helper
    var ConfigHelper = {
        
        // Generate shared dashboard URL helper
        generateSharedDashboardUrl: function() {
            var domain = $('#plausible_domain').val();
            var apiBase = $('#plausible_api_base').val() || 'https://plausible.io';
            
            if (domain) {
                var shareUrl = apiBase + '/share/' + domain;
                $('#plausible_shared_dashboard').val(shareUrl);
                
                // Show helper message
                apolloAnalyticsAdminHelpers.showNotification(
                    'URL do dashboard gerada. Verifique se o dashboard está configurado como público no Plausible.',
                    'info'
                );
            } else {
                alert('Por favor, preencha o domínio primeiro.');
            }
        },
        
        // Copy configuration for sharing
        copyConfig: function() {
            var config = {
                domain: $('#plausible_domain').val(),
                api_base: $('#plausible_api_base').val(),
                script_url: $('#plausible_script_url').val(),
                shared_dashboard_url: $('#plausible_shared_dashboard').val()
            };
            
            // Copy to clipboard
            var configText = JSON.stringify(config, null, 2);
            
            if (navigator.clipboard) {
                navigator.clipboard.writeText(configText).then(function() {
                    apolloAnalyticsAdminHelpers.showNotification('Configuração copiada para a área de transferência!', 'success');
                });
            } else {
                // Fallback for older browsers
                var $temp = $('<textarea>');
                $('body').append($temp);
                $temp.val(configText).select();
                document.execCommand('copy');
                $temp.remove();
                
                apolloAnalyticsAdminHelpers.showNotification('Configuração copiada para a área de transferência!', 'success');
            }
        }
    };
    
    // Make ConfigHelper available globally
    window.apolloConfigHelper = ConfigHelper;
    
    // Initialize when document is ready
    $(document).ready(function() {
        ApolloAnalyticsAdmin.init();
        
        // Initialize driver config visibility
        $('#analytics_driver').trigger('change');
        
        // Add helper buttons
        if ($('#plausible_shared_dashboard').length) {
            $('<button type="button" class="button" onclick="apolloConfigHelper.generateSharedDashboardUrl()">Gerar URL</button>')
                .insertAfter('#plausible_shared_dashboard');
        }
        
        if ($('#plausible_api_key').length) {
            $('<button type="button" class="button test-connection-btn">Testar Conexão</button>')
                .insertAfter('#plausible_api_key');
        }
    });

})(jQuery);