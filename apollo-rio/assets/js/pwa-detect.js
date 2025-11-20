/**
 * Apollo PWA Detection - iOS/iPhone Compatible
 * Detects standalone mode on ALL platforms including iOS
 */
(function() {
    'use strict';
    
    /**
     * Detecta se está rodando como PWA (standalone mode)
     * Funciona em iOS, Android, Desktop
     */
    function isPWA() {
        // 1. iOS/Safari: window.navigator.standalone
        if (window.navigator.standalone === true) {
            return true;
        }
        
        // 2. Android/Chrome: display-mode
        if (window.matchMedia('(display-mode: standalone)').matches) {
            return true;
        }
        
        // 3. Fallback: cookie existente
        if (document.cookie.indexOf('apollo_display_mode=standalone') !== -1) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Salva o estado do PWA em cookie
     */
    function savePWAState() {
        const isPWAMode = isPWA();
        
        if (isPWAMode) {
            // Salva cookie por 365 dias
            const expires = new Date();
            expires.setFullYear(expires.getFullYear() + 1);
            document.cookie = `apollo_display_mode=standalone; expires=${expires.toUTCString()}; path=/; SameSite=Lax`;
        }
        
        return isPWAMode;
    }
    
    /**
     * Adiciona classe no body para CSS
     */
    function updateBodyClass(isPWAMode) {
        if (isPWAMode) {
            document.documentElement.classList.add('apollo-is-pwa');
            document.body.classList.add('apollo-is-pwa');
        } else {
            document.documentElement.classList.add('apollo-is-browser');
            document.body.classList.add('apollo-is-browser');
        }
    }
    
    /**
     * Força reload se necessário (quando PWA detectado mas página ainda em modo browser)
     */
    function checkAndReload() {
        const isPWAMode = isPWA();
        const urlParams = new URLSearchParams(window.location.search);
        const hasReloaded = urlParams.get('pwa_reload') === '1';
        
        // Se detectou PWA e ainda não recarregou
        if (isPWAMode && !hasReloaded) {
            // Verifica se página está mostrando conteúdo ou install page
            const hasInstallPage = document.querySelector('.apollo-pwa-install-page');
            
            if (hasInstallPage) {
                // Salva cookie e recarrega
                savePWAState();
                
                // Recarrega SEM parâmetro para evitar loop
                window.location.href = window.location.pathname;
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Inicialização
     */
    function init() {
        // Verifica se precisa recarregar
        if (checkAndReload()) {
            return; // Para execução se vai recarregar
        }
        
        // Salva estado atual
        const isPWAMode = savePWAState();
        
        // Atualiza classes
        updateBodyClass(isPWAMode);
        
        // Disponibiliza API global
        window.apolloPWA = window.apolloPWA || {};
        window.apolloPWA.isPWA = isPWA;
        window.apolloPWA.detected = isPWAMode;
        
        // Log para debug
        if (typeof console !== 'undefined' && console.log) {
            console.log('Apollo PWA Detection:', {
                isPWA: isPWAMode,
                standalone: window.navigator.standalone,
                displayMode: window.matchMedia('(display-mode: standalone)').matches
            });
        }
    }
    
    // Executa imediatamente (antes do DOM)
    init();
    
    // Reexecuta após DOM carregar (por segurança)
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    }
    
})();