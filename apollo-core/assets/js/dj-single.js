/**
 * DJ Single Page JavaScript
 * =========================
 * Path: apollo-core/assets/js/dj-single.js
 * Template: core-dj-single.php
 *
 * Handles:
 * - SoundCloud widget integration
 * - Vinyl player visual states
 * - Bio modal open/close
 *
 * @package Apollo_Core
 * @version 2.0.0
 */

(function() {
  'use strict';

  // =========================================================================
  // STATE
  // =========================================================================

  let scWidget = null;
  let widgetReady = false;

  // =========================================================================
  // VINYL PLAYER STATE MANAGEMENT
  // =========================================================================

  /**
   * Set vinyl player visual state
   * @param {boolean} isPlaying - Whether the player is currently playing
   */
  function setVinylState(isPlaying) {
    const vinylPlayer = document.getElementById('vinylPlayer');
    const icon = document.getElementById('vinylIcon');

    if (!vinylPlayer || !icon) return;

    if (isPlaying) {
      vinylPlayer.classList.add('is-playing');
      vinylPlayer.classList.remove('is-paused');
      icon.classList.remove('ri-play-fill');
      icon.classList.add('ri-pause-fill');
    } else {
      vinylPlayer.classList.remove('is-playing');
      vinylPlayer.classList.add('is-paused');
      icon.classList.remove('ri-pause-fill');
      icon.classList.add('ri-play-fill');
    }
  }

  /**
   * Toggle vinyl playback state
   */
  function toggleVinylPlayback() {
    if (!widgetReady || !scWidget) {
      console.warn('[Apollo DJ] SoundCloud widget not ready');
      return;
    }

    scWidget.isPaused(function(paused) {
      if (paused) {
        scWidget.play();
      } else {
        scWidget.pause();
      }
    });
  }

  // =========================================================================
  // SOUNDCLOUD WIDGET INITIALIZATION
  // =========================================================================

  /**
   * Initialize SoundCloud widget
   */
  function initSoundCloud() {
    const iframe = document.getElementById('scPlayer');

    if (!iframe) {
      console.log('[Apollo DJ] No SoundCloud player found');
      return;
    }

    if (!window.SC || typeof SC.Widget !== 'function') {
      console.warn('[Apollo DJ] SoundCloud API not loaded');
      return;
    }

    try {
      scWidget = SC.Widget(iframe);

      scWidget.bind(SC.Widget.Events.READY, function() {
        widgetReady = true;
        console.log('[Apollo DJ] SoundCloud widget ready');
      });

      scWidget.bind(SC.Widget.Events.PLAY, function() {
        setVinylState(true);
      });

      scWidget.bind(SC.Widget.Events.PAUSE, function() {
        setVinylState(false);
      });

      scWidget.bind(SC.Widget.Events.FINISH, function() {
        setVinylState(false);
      });

      scWidget.bind(SC.Widget.Events.ERROR, function(error) {
        console.error('[Apollo DJ] SoundCloud error:', error);
        setVinylState(false);
      });

    } catch (error) {
      console.error('[Apollo DJ] Failed to initialize SoundCloud widget:', error);
    }
  }

  // =========================================================================
  // VINYL PLAYER CLICK HANDLERS
  // =========================================================================

  /**
   * Initialize vinyl player click handlers
   */
  function initVinylPlayer() {
    const vinylPlayer = document.getElementById('vinylPlayer');
    const vinylToggle = document.getElementById('vinylToggle');

    [vinylPlayer, vinylToggle].forEach(function(el) {
      if (!el) return;

      el.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        toggleVinylPlayback();
      });
    });
  }

  // =========================================================================
  // BIO MODAL
  // =========================================================================

  /**
   * Initialize bio modal functionality
   */
  function initBioModal() {
    const bioToggleBtn = document.getElementById('bioToggle');
    const bioBackdrop = document.getElementById('bioBackdrop');
    const bioClose = document.getElementById('bioClose');
    const headerName = document.getElementById('dj-header-name');
    const modalTitle = document.getElementById('dj-bio-modal-title');

    // Update modal title with DJ name
    if (modalTitle && headerName) {
      const djName = headerName.textContent.trim();
      modalTitle.textContent = 'Bio completa · ' + djName;
    }

    /**
     * Open bio modal
     */
    function openBio() {
      if (!bioBackdrop) return;

      bioBackdrop.setAttribute('data-open', 'true');
      document.body.style.overflow = 'hidden';

      // Focus trap for accessibility
      const closeBtn = bioBackdrop.querySelector('.dj-bio-modal-close');
      if (closeBtn) {
        setTimeout(function() { closeBtn.focus(); }, 100);
      }
    }

    /**
     * Close bio modal
     */
    function closeBio() {
      if (!bioBackdrop) return;

      bioBackdrop.setAttribute('data-open', 'false');
      document.body.style.overflow = '';

      // Return focus to toggle button
      if (bioToggleBtn) {
        bioToggleBtn.focus();
      }
    }

    // Event bindings
    if (bioToggleBtn) {
      bioToggleBtn.addEventListener('click', openBio);
    }

    if (bioClose) {
      bioClose.addEventListener('click', closeBio);
    }

    if (bioBackdrop) {
      // Close on backdrop click
      bioBackdrop.addEventListener('click', function(e) {
        if (e.target === bioBackdrop) {
          closeBio();
        }
      });

      // Close on Escape key
      document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && bioBackdrop.getAttribute('data-open') === 'true') {
          closeBio();
        }
      });
    }
  }

  // =========================================================================
  // LINK TRACKING (Optional Apollo Analytics Integration)
  // =========================================================================

  /**
   * Track DJ link clicks for analytics
   */
  function initLinkTracking() {
    const trackableLinks = document.querySelectorAll('.dj-link-pill, .dj-pill-link');

    trackableLinks.forEach(function(link) {
      link.addEventListener('click', function(e) {
        const href = link.getAttribute('href');
        const label = link.querySelector('span')?.textContent || 'Unknown';

        // Apollo Analytics tracking (if available)
        if (window.apolloAnalytics && typeof window.apolloAnalytics.track === 'function') {
          window.apolloAnalytics.track('dj_link_click', {
            dj_id: document.querySelector('[data-dj-id]')?.dataset.djId || 0,
            link_type: label,
            link_url: href
          });
        }

        // Google Analytics 4 (if available)
        if (typeof gtag === 'function') {
          gtag('event', 'click', {
            event_category: 'DJ Profile',
            event_label: label,
            link_url: href
          });
        }
      });
    });
  }

  // =========================================================================
  // INITIALIZATION
  // =========================================================================

  /**
   * Main initialization function
   */
  function init() {
    console.log('[Apollo DJ] Initializing DJ Single page');

    initSoundCloud();
    initVinylPlayer();
    initBioModal();
    initLinkTracking();

    console.log('[Apollo DJ] Initialization complete');
  }

  // Run on DOM ready
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init, { once: true });
  } else {
    init();
  }

  // Export for potential external use
  window.ApolloDJPlayer = {
    togglePlayback: toggleVinylPlayback,
    setPlaying: function() { setVinylState(true); },
    setPaused: function() { setVinylState(false); }
  };

})();
