/**
 * ============================================================================
 * APOLLO BASE.JS – GLOBAL ORCHESTRATOR
 * ============================================================================
 * 
 * Self-contained orchestrator for Apollo Design System.
 * Injects uni.css, manages dark mode, provides global utilities.
 * 
 * CDN: https://assets.apollo.rio.br/base.js
 * 
 * Features:
 * - Auto-injects uni.css (canonical stylesheet)
 * - Dark mode toggle with localStorage persistence
 * - Live clock utility
 * - Conditional page-specific script loading
 * - SPA/PWA-safe with MutationObserver
 * 
 * Usage:
 *   <script src="https://assets.apollo.rio.br/base.js" defer></script>
 * 
 * ============================================================================
 */

(function () {
  'use strict';

  const CDN = 'https://assets.apollo.rio.br/';
  const STORAGE_KEY = 'apollo-dark-mode';

  // ══════════════════════════════════════════════════════════════════════════
  // INTERNAL STATE
  // ══════════════════════════════════════════════════════════════════════════
  const loadedScripts = new Set();
  const processedNodes = new WeakSet();
  let observer = null;
  let batch = [];
  let rafId = null;

  // ══════════════════════════════════════════════════════════════════════════
  // 1. UNI.CSS INJECTOR
  // ══════════════════════════════════════════════════════════════════════════
  function ensureUniCss() {
    if (document.querySelector('link[data-apollo-uni]')) return;
    const link = document.createElement('link');
    link.rel = 'stylesheet';
    link.href = CDN + 'uni.css';
    link.setAttribute('data-apollo-uni', '');
    document.head.insertBefore(link, document.head.firstChild);
  }

  // ══════════════════════════════════════════════════════════════════════════
  // 2. DARK MODE MANAGER
  // ══════════════════════════════════════════════════════════════════════════
  function initDarkMode() {
    // Check saved preference or system preference
    const saved = localStorage.getItem(STORAGE_KEY);
    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
    const isDark = saved === 'true' || (saved === null && prefersDark);

    if (isDark) {
      document.body.classList.add('dark-mode');
    }

    // Listen for system preference changes
    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
      if (localStorage.getItem(STORAGE_KEY) === null) {
        document.body.classList.toggle('dark-mode', e.matches);
      }
    });
  }

  function toggleDarkMode() {
    const isDark = document.body.classList.toggle('dark-mode');
    localStorage.setItem(STORAGE_KEY, isDark);
    return isDark;
  }

  function bindDarkModeToggles() {
    document.querySelectorAll('[data-apollo-dark-toggle]').forEach(el => {
      if (el.dataset.apolloBound) return;
      el.dataset.apolloBound = 'true';
      el.addEventListener('click', (e) => {
        e.preventDefault();
        const isDark = toggleDarkMode();
        // Update icon if present
        const icon = el.querySelector('i');
        if (icon) {
          icon.className = isDark ? 'ri-sun-line' : 'ri-moon-line';
        }
      });
    });
  }

  // ══════════════════════════════════════════════════════════════════════════
  // 3. LIVE CLOCK UTILITY
  // ══════════════════════════════════════════════════════════════════════════
  function initClocks() {
    const clocks = document.querySelectorAll('[data-apollo-clock]');
    if (!clocks.length) return;

    function updateClocks() {
      const now = new Date();
      clocks.forEach(el => {
        const format = el.dataset.apolloClock || 'time'; // 'time', 'date', 'full'
        let str = '';
        if (format === 'date') {
          str = now.toLocaleDateString('pt-BR', { day: '2-digit', month: 'short' });
        } else if (format === 'full') {
          str = now.toLocaleDateString('pt-BR', { weekday: 'short', day: '2-digit', month: 'short' }) +
            ' ' + now.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
        } else {
          str = now.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
        }
        el.textContent = str;
      });
    }

    updateClocks();
    setInterval(updateClocks, 1000);
  }

  // ══════════════════════════════════════════════════════════════════════════
  // 4. SCRIPT LOADER (for page-specific scripts)
  // ══════════════════════════════════════════════════════════════════════════
  function loadScriptOnce(url) {
    if (loadedScripts.has(url)) return;
    loadedScripts.add(url);
    const script = document.createElement('script');
    script.src = url;
    script.defer = true;
    document.head.appendChild(script);
  }

  function loadPageScripts() {
    // Load Remix Icon if not present
    if (!document.querySelector('link[href*="remixicon"]')) {
      const iconLink = document.createElement('link');
      iconLink.rel = 'stylesheet';
      iconLink.href = 'https://cdn.jsdelivr.net/npm/remixicon@4.5.0/fonts/remixicon.min.css';
      document.head.appendChild(iconLink);
    }

    // Event pages
    if (document.querySelector('[data-apollo-event-card]') ||
      document.body.classList.contains('ap-page-event') ||
      document.body.classList.contains('ap-page-dj') ||
      document.body.classList.contains('ap-page-local') ||
      document.body.classList.contains('single-evento') ||
      document.body.classList.contains('single-dj') ||
      document.body.classList.contains('single-local')) {
      loadScriptOnce(CDN + 'event.js');
    }

    // Explorer/feed pages
    if (document.querySelector('.ap-explorer-feed') ||
      document.querySelector('[data-apollo-role="explorer"]')) {
      loadScriptOnce(CDN + 'explorer.js');
    }
  }

  // ══════════════════════════════════════════════════════════════════════════
  // 5. GLOBAL UTILITIES (exposed to window.Apollo)
  // ══════════════════════════════════════════════════════════════════════════
  const Apollo = {
    toggleDarkMode,
    isDarkMode: () => document.body.classList.contains('dark-mode'),
    loadScript: loadScriptOnce,
    version: '2.0.0'
  };

  // ══════════════════════════════════════════════════════════════════════════
  // 6. MUTATION OBSERVER (SPA safety)
  // ══════════════════════════════════════════════════════════════════════════
  function processBatch() {
    batch.forEach(node => processedNodes.add(node));
    batch = [];

    // Re-bind toggles on DOM changes
    bindDarkModeToggles();
    loadPageScripts();
  }

  function onMutation(mutations) {
    mutations.forEach(mutation => {
      mutation.addedNodes.forEach(node => {
        if (node.nodeType === Node.ELEMENT_NODE && !processedNodes.has(node)) {
          batch.push(node);
        }
      });
    });

    if (batch.length && !rafId) {
      rafId = requestAnimationFrame(() => {
        processBatch();
        rafId = null;
      });
    }
  }

  // ══════════════════════════════════════════════════════════════════════════
  // 7. INITIALIZATION
  // ══════════════════════════════════════════════════════════════════════════
  function init() {
    // 1. Inject uni.css first (highest priority)
    ensureUniCss();

    // 2. Initialize dark mode
    initDarkMode();

    // 3. Bind dark mode toggles
    bindDarkModeToggles();

    // 4. Initialize clocks
    initClocks();

    // 5. Load page-specific scripts
    loadPageScripts();

    // 6. Start MutationObserver for SPA support
    observer = new MutationObserver(onMutation);
    observer.observe(document.body, { childList: true, subtree: true });

    // 7. Expose global API
    window.Apollo = Apollo;
  }

  // Run when DOM is ready
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init, { once: true });
  } else {
    init();
  }
})();