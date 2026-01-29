/**
 * Cena::Rio Page JavaScript
 * Funcionalidades específicas da página Cena::Rio
 */

(function($) {
    'use strict';
    
    $(document).ready(function() {
        // Mobile sidebar toggle
        if ($(window).width() <= 768) {
            // Criar botão toggle se não existir
            if ($('#mobileSidebarToggle').length === 0) {
                $('body').prepend('<button id="mobileSidebarToggle" class="fixed top-4 left-4 z-50 p-2 bg-card border border-border rounded-md"><i class="ri-menu-line"></i></button>');
            }
            
            $('#mobileSidebarToggle').on('click', function() {
                $('.sidebar').toggleClass('open');
                if ($('.sidebar-overlay').length === 0) {
                    $('body').append('<div class="sidebar-overlay"></div>');
                }
            });
            
            $('.sidebar-overlay').on('click', function() {
                $('.sidebar').removeClass('open');
                $(this).remove();
            });
        }
        
        // Notifications modal
        $('#logoButton').on('click', function() {
            $('#notificationsModal').removeClass('hidden').addClass('flex');
        });
        
        $('#closeNotifications').on('click', function() {
            $('#notificationsModal').addClass('hidden').removeClass('flex');
        });
        
        // Fechar modal ao clicar fora
        $('#notificationsModal').on('click', function(e) {
            if ($(e.target).is('#notificationsModal')) {
                $(this).addClass('hidden').removeClass('flex');
            }
        });
    });
})(jQuery);

