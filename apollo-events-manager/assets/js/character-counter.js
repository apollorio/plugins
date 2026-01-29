/**
 * Character Counter with Animation
 * 
 * Contador de caracteres estilo Motion.dev com animações
 * 
 * @package Apollo_Events_Manager
 * @version 0.1.0
 */

(function() {
    'use strict';

    /**
     * Initialize character counters
     */
    function initCharacterCounters() {
        const inputs = document.querySelectorAll('[data-char-counter]');
        
        inputs.forEach(function(input) {
            const maxLength = parseInt(input.getAttribute('data-char-counter'), 10) || 281;
            const counterId = input.id + '-counter';
            
            // Create counter element if it doesn't exist
            let counter = document.getElementById(counterId);
            if (!counter) {
                counter = document.createElement('div');
                counter.id = counterId;
                counter.className = 'char-counter';
                counter.style.cssText = `
                    font-size: 0.875rem;
                    color: #666;
                    margin-top: 0.5rem;
                    transition: color 0.2s ease;
                `;
                input.parentNode.appendChild(counter);
            }
            
            /**
             * Update counter
             */
            function updateCounter() {
                const length = input.value.length;
                const remaining = maxLength - length;
                
                counter.textContent = remaining + ' caracteres restantes';
                
                // Color feedback
                if (remaining < 0) {
                    counter.style.color = '#dc2626'; // Red
                    input.style.borderColor = '#dc2626';
                } else if (remaining < 20) {
                    counter.style.color = '#f59e0b'; // Orange
                    input.style.borderColor = '#f59e0b';
                } else {
                    counter.style.color = '#666';
                    input.style.borderColor = '';
                }
                
                // Animation when reaching limit
                if (remaining === 0) {
                    counter.style.animation = 'pulse 0.3s ease-out';
                    setTimeout(() => {
                        counter.style.animation = '';
                    }, 300);
                }
            }
            
            // Add event listeners
            input.addEventListener('input', updateCounter);
            input.addEventListener('keyup', updateCounter);
            input.addEventListener('paste', function() {
                setTimeout(updateCounter, 10);
            });
            
            // Initialize
            updateCounter();
        });
    }

    /**
     * Add animation styles
     */
    function addStyles() {
        const style = document.createElement('style');
        style.textContent = `
            @keyframes pulse {
                0%, 100% {
                    transform: scale(1);
                }
                50% {
                    transform: scale(1.05);
                }
            }
            
            .char-counter {
                font-weight: 500;
                font-variant-numeric: tabular-nums;
            }
        `;
        document.head.appendChild(style);
    }

    /**
     * Initialize when DOM is ready
     */
    function init() {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', function() {
                addStyles();
                initCharacterCounters();
            });
        } else {
            addStyles();
            initCharacterCounters();
        }
        
        // Re-initialize for dynamic content
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.addedNodes.length > 0) {
                    setTimeout(initCharacterCounters, 100);
                }
            });
        });
        
        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
    }

    init();
})();

