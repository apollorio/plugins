/**
 * ============================================
 * FILE: assets/js/pwa-detect.js
 * PWA Detection & Display Mode Management
 * ============================================
 */

(function() {
    'use strict';
    
    /**
     * Detect if running in PWA mode (standalone)
     */
    function isPWA() {
        // Method 1: Display mode
        const isStandalone = window.matchMedia('(display-mode: standalone)').matches;
        
        // Method 2: iOS specific
        const isIOSStandalone = window.navigator.standalone === true;
        
        // Method 3: Android/Chrome specific
        const isAndroidStandalone = document.referrer.includes('android-app://');
        
        return isStandalone || isIOSStandalone || isAndroidStandalone;
    }
    
    /**
     * Set cookie to persist PWA state
     */
    function setPWACookie(value) {
        const days = 365;
        const date = new Date();
        date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
        const expires = "expires=" + date.toUTCString();
        document.cookie = "apollo_display_mode=" + value + ";" + expires + ";path=/;SameSite=Lax";
    }
    
    /**
     * Initialize PWA detection
     */
    function initPWADetection() {
        const pwaMode = isPWA();
        
        // Set cookie for server-side detection
        setPWACookie(pwaMode ? 'standalone' : 'browser');
        
        // Add body class
        if (pwaMode) {
            document.body.classList.add('is-pwa');
        } else {
            document.body.classList.add('is-browser');
        }
        
        // Store in sessionStorage for quick access
        sessionStorage.setItem('apollo_is_pwa', pwaMode ? '1' : '0');
        
        // Trigger custom event
        window.dispatchEvent(new CustomEvent('apolloPWADetected', {
            detail: { isPWA: pwaMode }
        }));
        
        console.log('Apollo PWA Detection:', pwaMode ? 'Running as PWA' : 'Running in browser');
    }
    
    /**
     * Handle PWA install prompt
     */
    let deferredPrompt;
    
    window.addEventListener('beforeinstallprompt', (e) => {
        console.log('PWA install prompt available');
        e.preventDefault();
        deferredPrompt = e;
        
        // Show custom install button if exists
        const installBtn = document.getElementById('apollo-pwa-install-btn');
        if (installBtn) {
            installBtn.style.display = 'block';
            
            installBtn.addEventListener('click', async () => {
                if (deferredPrompt) {
                    deferredPrompt.prompt();
                    const { outcome } = await deferredPrompt.userChoice;
                    console.log('PWA install outcome:', outcome);
                    deferredPrompt = null;
                    installBtn.style.display = 'none';
                }
            });
        }
    });
    
    /**
     * Redirect mobile browser users to PWA install page
     * (Only for pagx_app and pagx_appclean templates)
     */
    function handlePWARedirect() {
        if (typeof apolloPWA === 'undefined') {
            return;
        }
        
        // Check if template requires PWA
        const requiresPWA = apolloPWA.template === 'pagx_app.php' || apolloPWA.template === 'pagx_appclean.php';
        
        if (!requiresPWA) {
            return;
        }
        
        // Check if mobile
        const isMobile = apolloPWA.isMobile || /iPhone|iPad|iPod|Android/i.test(navigator.userAgent);
        
        if (!isMobile) {
            return; // Desktop users always see content
        }
        
        // Check if already in PWA
        if (isPWA()) {
            return; // Already in PWA, show content
        }
        
        // Mobile browser + not PWA = Should see install page
        // This is handled by PHP, but we add visual feedback
        document.body.classList.add('requires-pwa-install');
    }
    
    /**
     * Monitor display mode changes
     */
    function monitorDisplayMode() {
        const mediaQuery = window.matchMedia('(display-mode: standalone)');
        
        mediaQuery.addEventListener('change', (e) => {
            const newMode = e.matches ? 'standalone' : 'browser';
            console.log('Display mode changed to:', newMode);
            setPWACookie(newMode);
            
            // Reload page to update content
            window.location.reload();
        });
    }
    
    /**
     * Initialize on DOM ready
     */
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            initPWADetection();
            handlePWARedirect();
            monitorDisplayMode();
        });
    } else {
        initPWADetection();
        handlePWARedirect();
        monitorDisplayMode();
    }
    
    /**
     * Export to window for external access
     */
    window.apolloPWA = window.apolloPWA || {};
    window.apolloPWA.isPWA = isPWA;
    window.apolloPWA.checkStatus = initPWADetection;
    
})();

/**
 * ============================================
 * ADDITIONAL: Mobile menu toggle
 * ============================================
 */
document.addEventListener('DOMContentLoaded', function() {
    const toggle = document.getElementById('apollo-mobile-toggle');
    const nav = document.getElementById('apollo-navigation');
    
    if (toggle && nav) {
        toggle.addEventListener('click', function() {
            nav.classList.toggle('active');
            toggle.classList.toggle('active');
            document.body.classList.toggle('menu-open');
        });
        
        // Close on outside click
        document.addEventListener('click', function(e) {
            if (!e.target.closest('#apollo-navigation') && !e.target.closest('#apollo-mobile-toggle')) {
                nav.classList.remove('active');
                toggle.classList.remove('active');
                document.body.classList.remove('menu-open');
            }
        });
    }
});

/**
 * ============================================
 * CSS FILE: assets/css/pwa-templates.css
 * ============================================
 */

/* Reset & Base ============================================ */
.apollo-html {
font-size: 14px;
-webkit-text-size-adjust: 100%;
}
.apollo-body {
margin: 0;
font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Oxygen, Ubuntu, Cantarell, 'Helvetica Neue', sans-serif;
line-height: 1.6;
color: rgba(19, 21, 23, .85);
background: #f5f5f5;
}
.apollo-site-wrapper {
display: flex;
flex-direction: column;
min-height: 100vh;
}
/* Full Header (pagx_site, pagx_app) ============================================ */
.apollo-header-full {
background: #fff;
box-shadow: 0 2px 8px rgba(0,0,0,0.08);
position: sticky;
top: 0;
z-index: 1000;
}
.apollo-header-container {
max-width: 1200px;
margin: 0 auto;
padding: 1rem 1.5rem;
display: flex;
align-items: center;
justify-content: space-between;
gap: 2rem;
}
.apollo-branding a, .apollo-branding-minimal a {
text-decoration: none;
color: #FF6425;
font-size: 1.5rem;
font-weight: 700;
}
.apollo-navigation {
flex: 1;
}
.apollo-menu {
list-style: none;
margin: 0;
padding: 0;
display: flex;
gap: 2rem;
}
.apollo-menu a {
text-decoration: none;
color: rgba(19, 21, 23, .6);
font-weight: 500;
transition: color 0.3s;
padding: 0.5rem 0;
}
.apollo-menu a:hover, .apollo-menu .current-menu-item a {
color: #FF6425;
}
.apollo-header-actions {
display: flex;
align-items: center;
gap: 1rem;
}
.apollo-username {
font-size: 0.9rem;
color: rgba(19, 21, 23, .6);
}
.apollo-avatar {
border-radius: 50%;
border: 2px solid #FF6425;
}
.apollo-login-btn, .apollo-register-btn, .apollo-logout-btn {
padding: 0.5rem 1rem;
border-radius: 4px;
text-decoration: none;
font-weight: 500;
transition: all 0.3s;
}
.apollo-login-btn {
background: transparent;
color: #FF6425;
border: 1px solid #FF6425;
}
.apollo-register-btn, .apollo-logout-btn {
background: #FF6425;
color: #fff;
border: 1px solid #FF6425;
}
.apollo-login-btn:hover {
background: #FF6425;
color: #fff;
}
.apollo-register-btn:hover, .apollo-logout-btn:hover {
background: #E55A20;
}
/* Minimal Header (pagx_appclean) ============================================ */
.apollo-header-minimal {
background: #fff;
border-bottom: 1px solid #e0e2e4;
padding: 0.75rem 0;
}
.apollo-header-minimal .apollo-header-container {
padding: 0.5rem 1.5rem;
}
.apollo-branding-minimal a {
font-size: 1.25rem;
}
.apollo-back-btn {
background: transparent;
border: none;
color: #FF6425;
cursor: pointer;
padding: 0.5rem;
border-radius: 4px;
transition: background 0.3s;
}
.apollo-back-btn:hover {
background: #f0f0f0;
}
/* Mobile Toggle ============================================ */
.apollo-mobile-toggle {
display: none;
background: transparent;
border: none;
cursor: pointer;
padding: 0.5rem;
}
.apollo-mobile-toggle span {
display: block;
width: 24px;
height: 2px;
background: rgba(19, 21, 23, .85);
margin: 4px 0;
transition: 0.3s;
}
.apollo-mobile-toggle.active span:nth-child(1) {
transform: rotate(-45deg) translate(-5px, 6px);
}
.apollo-mobile-toggle.active span:nth-child(2) {
opacity: 0;
}
.apollo-mobile-toggle.active span:nth-child(3) {
transform: rotate(45deg) translate(-5px, -6px);
}
/* Main Content ============================================ */
.apollo-main-content {
flex: 1;
padding: 2rem 0;
}
.apollo-container {
max-width: 1200px;
margin: 0 auto;
padding: 0 1.5rem;
}
.apollo-content-wrapper {
background: #fff;
border-radius: 8px;
box-shadow: 0 2px 12px rgba(0,0,0,0.08);
padding: 2rem;
min-height: 400px;
}
.apollo-entry-header {
margin-bottom: 2rem;
padding-bottom: 1rem;
border-bottom: 2px solid #f0f0f0;
}
.apollo-entry-title {
font-size: 2.5rem;
margin: 0;
color: rgba(19, 21, 23, .85);
font-weight: 700;
}
.apollo-entry-content {
font-size: 1.125rem;
line-height: 1.8;
}
/* Clean Template Adjustments ============================================ */
.pagx-appclean .apollo-entry-header {
border: none;
padding-bottom: 0.5rem;
}
.pagx-appclean .apollo-entry-title {
font-size: 2rem;
}
/* Footer ============================================ */
.apollo-footer-full {
background: #131517;
color: #fff;
margin-top: auto;
}
.apollo-footer-container {
max-width: 1200px;
margin: 0 auto;
padding: 3rem 1.5rem 2rem;
}
.apollo-footer-widgets {
display: grid;
grid-template-columns: repeat(3, 1fr);
gap: 2rem;
margin-bottom: 2rem;
}
.apollo-footer-info {
padding-top: 2rem;
border-top: 1px solid rgba(255,255,255,0.1);
text-align: center;
}
.apollo-copyright {
margin: 0 0 1rem;
font-size: 0.875rem;
opacity: 0.8;
}
.apollo-copyright a {
color: #fff;
text-decoration: none;
}
.apollo-footer-menu {
list-style: none;
margin: 0;
padding: 0;
display: flex;
justify-content: center;
gap: 2rem;
font-size: 0.875rem;
}
.apollo-footer-menu a {
color: #fff;
text-decoration: none;
opacity: 0.8;
transition: opacity 0.3s;
}
.apollo-footer-menu a:hover {
opacity: 1;
}
/* Minimal Footer ============================================ */
.apollo-footer-minimal {
background: #f5f5f5;
border-top: 1px solid #e0e2e4;
padding: 1rem 0;
}
.apollo-footer-info-minimal {
text-align: center;
}
.apollo-copyright-minimal {
margin: 0;
font-size: 0.75rem;
color: rgba(19, 21, 23, .6);
}
/* PWA Install Page ============================================ */
.apollo-pwa-install-page {
min-height: 60vh;
display: flex;
align-items: center;
justify-content: center;
padding: 3rem 1rem;
}
.apollo-pwa-container {
max-width: 600px;
text-align: center;
}
.apollo-pwa-icon {
color: #FF6425;
margin-bottom: 2rem;
}
.apollo-pwa-title {
font-size: 2rem;
margin: 0 0 1rem;
color: rgba(19, 21, 23, .85);
}
.apollo-pwa-subtitle {
font-size: 1.125rem;
color: rgba(19, 21, 23, .6);
margin: 0 0 2rem;
}
.apollo-pwa-link {
color: #FF6425;
text-decoration: underline;
cursor: pointer;
font-weight: 600;
}
.apollo-pwa-accordion {
margin-top: 2rem;
}
.apollo-pwa-accordion-item {
margin-bottom: 1rem;
border: 1px solid #e0e2e4;
border-radius: 8px;
overflow: hidden;
}
.apollo-pwa-accordion-button {
width: 100%;
display: flex;
align-items: center;
gap: 1rem;
padding: 1rem 1.5rem;
background: #fff;
border: none;
font-size: 1.125rem;
font-weight: 600;
cursor: pointer;
transition: background 0.3s;
text-align: left;
}
.apollo-pwa-accordion-button:hover {
background: #f5f5f5;
}
.apollo-pwa-platform-icon {
color: #FF6425;
}
.apollo-pwa-accordion-icon {
margin-left: auto;
font-size: 0.875rem;
transition: transform 0.3s;
}
.apollo-pwa-accordion-content {
padding: 1.5rem;
background: #f5f5f5;
display: none;
}
.apollo-pwa-download-btn {
display: inline-flex;
align-items: center;
gap: 0.75rem;
padding: 1rem 2rem;
background: #FF6425;
color: #fff;
text-decoration: none;
border-radius: 8px;
font-weight: 600;
transition: all 0.3s;
}
.apollo-pwa-download-btn:hover {
background: #E55A20;
transform: translateY(-2px);
box-shadow: 0 4px 12px rgba(255,100,37,0.3);
}
.apollo-pwa-ios-instructions {
text-align: left;
}
.apollo-pwa-ios-instructions h3 {
margin: 0 0 1rem;
color: rgba(19, 21, 23, .85);
}
.apollo-pwa-ios-instructions ol {
padding-left: 1.5rem;
}
.apollo-pwa-ios-instructions li {
margin-bottom: 1.5rem;
}
.apollo-pwa-ios-instructions strong {
color: #FF6425;
}
.apollo-pwa-icon-demo {
display: inline-flex;
padding: 0.5rem;
background: #fff;
border: 1px solid #e0e2e4;
border-radius: 4px;
margin: 0.5rem 0;
}
.apollo-pwa-video-hint {
margin-top: 2rem;
padding: 1rem;
background: #fff;
border-left: 4px solid #FF6425;
border-radius: 4px;
}
.apollo-pwa-video-hint p {
margin: 0;
display: flex;
align-items: center;
gap: 0.5rem;
color: #FF6425;
}
/* Responsive ============================================ */
@media (max-width: 768px) {
.apollo-header-container {
flex-wrap: wrap;
}
.apollo-navigation {
position: fixed;
top: 0;
left: -100%;
width: 80%;
max-width: 300px;
height: 100vh;
background: #fff;
box-shadow: 2px 0 10px rgba(0,0,0,0.1);
padding: 4rem 1.5rem 1.5rem;
transition: left 0.3s;
z-index: 999;
}
.apollo-navigation.active {
left: 0;
}
.apollo-menu {
flex-direction: column;
gap: 1rem;
}
.apollo-mobile-toggle {
display: block;
}
.apollo-footer-widgets {
grid-template-columns: 1fr;
}
.apollo-footer-menu {
flex-direction: column;
gap: 0.5rem;
}
.apollo-entry-title {
font-size: 1.75rem;
}
}
/* ========================================

======= PWA-specific Styles =======
========================================= */
.is-pwa .apollo-header {
padding-top: env(safe-area-inset-top);
}

.is-pwa .apollo-main-content {
padding-bottom: calc(2rem + env(safe-area-inset-bottom));
}
/* Loading State ============================================ */
.apollo-loading {
text-align: center;
padding: 3rem;
}
.apollo-loading::after {
content: '';
display: inline-block;
width: 40px;
height: 40px;
border: 3px solid #f0f0f0;
border-top-color: #FF6425;
border-radius: 50%;
animation: spin 1s linear infinite;
}
@keyframes spin {
    to { transform: rotate(360deg); }
}
*/