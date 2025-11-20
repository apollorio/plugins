/**
 * Social Post Form JavaScript
 * TODO 110-114: Character counter, validation, submit animation
 * 
 * @package Apollo_Events_Manager
 * @version 0.1.0
 */

(function() {
    'use strict';

    const MAX_CHARS = 281;

    // TODO 112: Animated counter
    function initCharCounter() {
        const textarea = document.getElementById('social-post-content');
        const counter = document.querySelector('[data-char-counter-display="true"]');
        const charCount = counter?.querySelector('.char-count');
        
        if (!textarea || !charCount) return;
        
        function updateCounter() {
            const count = textarea.value.length;
            charCount.textContent = count;
            
            // Change color based on limit
            if (count > MAX_CHARS * 0.9) {
                counter.classList.add('warning');
            } else {
                counter.classList.remove('warning');
            }
            
            if (count >= MAX_CHARS) {
                counter.classList.add('error');
            } else {
                counter.classList.remove('error');
            }
        }
        
        textarea.addEventListener('input', updateCounter);
        updateCounter();
    }

    // TODO 113: Real-time validation
    function initValidation() {
        const textarea = document.getElementById('social-post-content');
        const messageEl = document.querySelector('[data-validation-message="true"]');
        
        if (!textarea || !messageEl) return;
        
        function validate() {
            const value = textarea.value.trim();
            const length = value.length;
            
            if (length === 0) {
                messageEl.textContent = '';
                textarea.classList.remove('error');
                return false;
            }
            
            if (length > MAX_CHARS) {
                messageEl.textContent = `Limite de ${MAX_CHARS} caracteres excedido!`;
                textarea.classList.add('error');
                return false;
            }
            
            messageEl.textContent = '';
            textarea.classList.remove('error');
            return true;
        }
        
        textarea.addEventListener('input', validate);
    }

    // TODO 114: Submit animation
    function initSubmitAnimation() {
        const form = document.querySelector('[data-apollo-form="true"]');
        const submitBtn = form?.querySelector('[data-submit-animation="true"]');
        const btnText = submitBtn?.querySelector('.btn-text');
        const btnLoader = submitBtn?.querySelector('.btn-loader');
        
        if (!form || !submitBtn) return;
        
        form.addEventListener('submit', function(e) {
            const textarea = document.getElementById('social-post-content');
            if (textarea.value.trim().length > MAX_CHARS) {
                e.preventDefault();
                return false;
            }
            
            // Show loading state
            submitBtn.disabled = true;
            if (btnText) btnText.style.display = 'none';
            if (btnLoader) btnLoader.style.display = 'inline-block';
            
            submitBtn.classList.add('loading');
        });
    }

    // Initialize
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            initCharCounter();
            initValidation();
            initSubmitAnimation();
        });
    } else {
        initCharCounter();
        initValidation();
        initSubmitAnimation();
    }
})();

