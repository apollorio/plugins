/**
 * Hold to Confirm Button System
 * 
 * Security feature: Requires user to hold button for specified duration
 * Uses Motion library for smooth visual effects
 * Acts as hidden CAPTCHA-like protection
 */

(function() {
    'use strict';

    // Check if Motion is available
    if (typeof window.Motion === 'undefined') {
        console.warn('Apollo: Motion library not loaded. Hold-to-confirm requires Motion.');
        return;
    }

    const { motionValue, styleEffect, animate, transformValue } = window.Motion;

    /**
     * HoldToConfirmButton Class
     */
    class HoldToConfirmButton {
        constructor(button, options = {}) {
            this.button = button;
            this.options = {
                holdDuration: options.holdDuration || 2000, // 2 seconds default
                progressColor: options.progressColor || '#3b82f6',
                successColor: options.successColor || '#10b981',
                errorColor: options.errorColor || '#ef4444',
                onComplete: options.onComplete || null,
                onCancel: options.onCancel || null,
                disabled: options.disabled || false,
                ...options
            };

            this.isHolding = false;
            this.holdStartTime = null;
            this.holdProgress = motionValue(0);
            this.scale = motionValue(1);
            this.blur = motionValue(0);
            this.glow = motionValue(0);
            this.rotation = motionValue(0);

            this.init();
        }

        init() {
            // Create progress bar element
            this.createProgressBar();

            // Setup motion values and effects
            this.setupMotionEffects();

            // Bind events
            this.bindEvents();

            // Initialize button state
            this.updateButtonState();
        }

        createProgressBar() {
            // Create progress bar container
            const progressBar = document.createElement('div');
            progressBar.className = 'apollo-hold-progress';
            progressBar.innerHTML = '<div class="apollo-hold-progress-fill"></div>';
            
            // Insert progress bar into button
            this.button.appendChild(progressBar);
            this.progressBar = progressBar;
            this.progressFill = progressBar.querySelector('.apollo-hold-progress-fill');
        }

        setupMotionEffects() {
            // Transform progress to percentage
            const progressPercent = transformValue(() => `${this.holdProgress.get() * 100}%`);
            const progressOpacity = transformValue(() => Math.max(0.3, this.holdProgress.get()));
            const scaleValue = transformValue(() => 1 + (this.holdProgress.get() * 0.1));
            const blurValue = transformValue(() => this.blur.get());
            const glowValue = transformValue(() => this.glow.get());
            const rotationValue = transformValue(() => this.rotation.get());

            // Apply effects to progress fill
            styleEffect(this.progressFill, {
                width: progressPercent,
                opacity: progressOpacity,
            });

            // Apply effects to button
            styleEffect(this.button, {
                scale: scaleValue,
                filter: transformValue(() => `blur(${blurValue.get()}px) brightness(${1 + glowValue.get() * 0.5})`),
                transform: transformValue(() => `rotate(${rotationValue.get()}deg)`),
            });

            // Update progress bar color based on progress
            this.holdProgress.on('change', (value) => {
                const percent = value * 100;
                if (percent < 100) {
                    this.progressFill.style.backgroundColor = this.options.progressColor;
                } else {
                    this.progressFill.style.backgroundColor = this.options.successColor;
                }
            });
        }

        bindEvents() {
            // Mouse events
            this.button.addEventListener('mousedown', (e) => this.startHold(e));
            this.button.addEventListener('mouseup', () => this.endHold());
            this.button.addEventListener('mouseleave', () => this.cancelHold());

            // Touch events
            this.button.addEventListener('touchstart', (e) => {
                e.preventDefault();
                this.startHold(e);
            });
            this.button.addEventListener('touchend', () => this.endHold());
            this.button.addEventListener('touchcancel', () => this.cancelHold());

            // Prevent form submission on regular click
            this.button.addEventListener('click', (e) => {
                if (!this.isComplete) {
                    e.preventDefault();
                    e.stopPropagation();
                }
            });
        }

        startHold(e) {
            if (this.options.disabled || this.isComplete) {
                return;
            }

            e.preventDefault();
            this.isHolding = true;
            this.holdStartTime = Date.now();
            this.button.classList.add('apollo-holding');

            // Start progress animation
            this.animateProgress();

            // Start visual effects
            this.startVisualEffects();
        }

        animateProgress() {
            const startTime = Date.now();
            const duration = this.options.holdDuration;

            const updateProgress = () => {
                if (!this.isHolding) {
                    return;
                }

                const elapsed = Date.now() - startTime;
                const progress = Math.min(elapsed / duration, 1);

                this.holdProgress.set(progress);

                if (progress < 1) {
                    requestAnimationFrame(updateProgress);
                } else {
                    this.complete();
                }
            };

            requestAnimationFrame(updateProgress);
        }

        startVisualEffects() {
            // Subtle scale animation
            animate(this.scale, 1.05, {
                duration: 0.2,
                easing: 'ease-out'
            });

            // Pulsing glow effect
            const pulseGlow = () => {
                if (!this.isHolding) return;
                
                animate(this.glow, 0.3, {
                    duration: 0.5,
                    easing: 'ease-in-out',
                    onComplete: () => {
                        if (this.isHolding) {
                            animate(this.glow, 0, {
                                duration: 0.5,
                                easing: 'ease-in-out',
                                onComplete: pulseGlow
                            });
                        }
                    }
                });
            };
            pulseGlow();

            // Subtle rotation wobble
            const wobble = () => {
                if (!this.isHolding) return;
                
                animate(this.rotation, 2, {
                    duration: 0.1,
                    easing: 'ease-in-out',
                    onComplete: () => {
                        if (this.isHolding) {
                            animate(this.rotation, -2, {
                                duration: 0.2,
                                easing: 'ease-in-out',
                                onComplete: () => {
                                    if (this.isHolding) {
                                        animate(this.rotation, 0, {
                                            duration: 0.1,
                                            easing: 'ease-in-out',
                                            onComplete: wobble
                                        });
                                    }
                                }
                            });
                        }
                    }
                });
            };
            wobble();
        }

        endHold() {
            if (!this.isHolding) return;

            this.isHolding = false;
            this.button.classList.remove('apollo-holding');

            // Reset visual effects
            animate(this.scale, 1, { duration: 0.2 });
            animate(this.blur, 0, { duration: 0.2 });
            animate(this.glow, 0, { duration: 0.2 });
            animate(this.rotation, 0, { duration: 0.2 });

            // If not complete, reset progress
            if (!this.isComplete) {
                animate(this.holdProgress, 0, { duration: 0.3 });
            }
        }

        cancelHold() {
            this.endHold();
            
            if (this.options.onCancel) {
                this.options.onCancel();
            }
        }

        complete() {
            this.isComplete = true;
            this.isHolding = false;
            this.button.classList.add('apollo-complete');
            this.button.classList.remove('apollo-holding');

            // Success animation
            animate(this.scale, 1.1, {
                duration: 0.2,
                easing: 'ease-out',
                onComplete: () => {
                    animate(this.scale, 1, { duration: 0.2 });
                }
            });

            animate(this.glow, 1, { duration: 0.3 });

            // Update button text/content
            const originalText = this.button.dataset.originalText || this.button.textContent;
            this.button.dataset.originalText = originalText;
            this.button.textContent = this.button.dataset.confirmText || '✓ Confirmado';

            // Trigger completion callback
            if (this.options.onComplete) {
                this.options.onComplete();
            }

            // Submit form if button is in a form
            const form = this.button.closest('form');
            if (form && this.button.type === 'submit') {
                setTimeout(() => {
                    form.submit();
                }, 300);
            }
        }

        updateButtonState() {
            if (this.options.disabled) {
                this.button.disabled = true;
                this.button.classList.add('apollo-disabled');
            }
        }

        reset() {
            this.isComplete = false;
            this.isHolding = false;
            this.holdProgress.set(0);
            this.scale.set(1);
            this.blur.set(0);
            this.glow.set(0);
            this.rotation.set(0);
            this.button.classList.remove('apollo-complete', 'apollo-holding');
            
            if (this.button.dataset.originalText) {
                this.button.textContent = this.button.dataset.originalText;
            }
        }

        destroy() {
            // Cleanup motion effects
            this.holdProgress.destroy?.();
            this.scale.destroy?.();
            this.blur.destroy?.();
            this.glow.destroy?.();
            this.rotation.destroy?.();

            // Remove event listeners
            this.button.replaceWith(this.button.cloneNode(true));
        }
    }

    /**
     * Initialize all hold-to-confirm buttons
     */
    function initHoldToConfirmButtons() {
        // Find all buttons with data-hold-to-confirm attribute
        const buttons = document.querySelectorAll('[data-hold-to-confirm]');
        
        buttons.forEach(button => {
            const options = {
                holdDuration: parseInt(button.dataset.holdDuration) || 2000,
                progressColor: button.dataset.progressColor || '#3b82f6',
                successColor: button.dataset.successColor || '#10b981',
                errorColor: button.dataset.errorColor || '#ef4444',
                onComplete: () => {
                    // Custom completion handler if specified
                    const handlerName = button.dataset.onComplete;
                    if (handlerName && window[handlerName]) {
                        window[handlerName](button);
                    }
                },
                onCancel: () => {
                    const handlerName = button.dataset.onCancel;
                    if (handlerName && window[handlerName]) {
                        window[handlerName](button);
                    }
                }
            };

            new HoldToConfirmButton(button, options);
        });
    }

    /**
     * Create hold-to-confirm button programmatically
     */
    window.createHoldToConfirmButton = function(button, options) {
        return new HoldToConfirmButton(button, options);
    };

    // Initialize on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initHoldToConfirmButtons);
    } else {
        initHoldToConfirmButtons();
    }

    // Re-initialize on dynamic content load
    if (typeof jQuery !== 'undefined') {
        jQuery(document).on('apollo:contentLoaded', initHoldToConfirmButtons);
    }

})();

