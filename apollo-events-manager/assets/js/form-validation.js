/**
 * Form Validation with Motion.dev Animations
 * 
 * Validação em tempo real com animações suaves
 * 
 * @package Apollo_Events_Manager
 * @version 0.1.0
 */

(function() {
    'use strict';

    /**
     * Initialize form validation
     */
    function initFormValidation() {
        const forms = document.querySelectorAll('[data-apollo-form]');
        
        forms.forEach(function(form) {
            const inputs = form.querySelectorAll('input[required], textarea[required], select[required]');
            
            inputs.forEach(function(input) {
                // Add validation on blur
                input.addEventListener('blur', function() {
                    validateField(this);
                });
                
                // Add validation on input (for better UX)
                input.addEventListener('input', function() {
                    if (this.classList.contains('error')) {
                        validateField(this);
                    }
                });
            });
            
            // Add form submit handler
            form.addEventListener('submit', function(e) {
                let isValid = true;
                
                inputs.forEach(function(input) {
                    if (!validateField(input)) {
                        isValid = false;
                    }
                });
                
                if (!isValid) {
                    e.preventDefault();
                    showFormError(form, 'Por favor, corrija os erros antes de enviar.');
                } else {
                    showLoadingState(form);
                }
            });
        });
    }

    /**
     * Validate individual field
     */
    function validateField(field) {
        const value = field.value.trim();
        const type = field.type;
        const required = field.hasAttribute('required');
        
        // Remove previous error
        removeFieldError(field);
        
        // Check if required field is empty
        if (required && value === '') {
            showFieldError(field, 'Este campo é obrigatório');
            return false;
        }
        
        // Email validation
        if (type === 'email' && value !== '') {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(value)) {
                showFieldError(field, 'Email inválido');
                return false;
            }
        }
        
        // URL validation
        if (type === 'url' && value !== '') {
            try {
                new URL(value);
            } catch {
                showFieldError(field, 'URL inválida');
                return false;
            }
        }
        
        // Max length validation
        const maxLength = field.getAttribute('maxlength');
        if (maxLength && value.length > parseInt(maxLength, 10)) {
            showFieldError(field, 'Texto muito longo');
            return false;
        }
        
        return true;
    }

    /**
     * Show field error with animation
     */
    function showFieldError(field, message) {
        field.classList.add('error');
        field.style.borderColor = '#dc2626';
        field.style.animation = 'shake 0.3s ease-out';
        
        setTimeout(() => {
            field.style.animation = '';
        }, 300);
        
        // Create/update error message
        let errorEl = field.parentNode.querySelector('.field-error');
        if (!errorEl) {
            errorEl = document.createElement('div');
            errorEl.className = 'field-error';
            errorEl.style.cssText = `
                color: #dc2626;
                font-size: 0.875rem;
                margin-top: 0.25rem;
                animation: fadeInDown 0.2s ease-out;
            `;
            field.parentNode.appendChild(errorEl);
        }
        errorEl.textContent = message;
    }

    /**
     * Remove field error
     */
    function removeFieldError(field) {
        field.classList.remove('error');
        field.style.borderColor = '';
        
        const errorEl = field.parentNode.querySelector('.field-error');
        if (errorEl) {
            errorEl.style.animation = 'fadeOutUp 0.2s ease-out';
            setTimeout(() => errorEl.remove(), 200);
        }
    }

    /**
     * Show form-level error
     */
    function showFormError(form, message) {
        let errorBox = form.querySelector('.form-error-box');
        if (!errorBox) {
            errorBox = document.createElement('div');
            errorBox.className = 'form-error-box';
            errorBox.style.cssText = `
                background: #fee;
                border: 1px solid #dc2626;
                color: #dc2626;
                padding: 1rem;
                border-radius: 8px;
                margin-bottom: 1rem;
                animation: slideInDown 0.3s ease-out;
            `;
            form.insertBefore(errorBox, form.firstChild);
        }
        errorBox.textContent = message;
    }

    /**
     * Show loading state on form submission
     */
    function showLoadingState(form) {
        const submitBtn = form.querySelector('[type="submit"]');
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.style.opacity = '0.6';
            submitBtn.style.cursor = 'not-allowed';
            
            const originalText = submitBtn.textContent;
            submitBtn.setAttribute('data-original-text', originalText);
            submitBtn.innerHTML = '<span class="spinner"></span> Enviando...';
        }
    }

    /**
     * Add animation styles
     */
    function addStyles() {
        const style = document.createElement('style');
        style.textContent = `
            @keyframes shake {
                0%, 100% { transform: translateX(0); }
                25% { transform: translateX(-5px); }
                75% { transform: translateX(5px); }
            }
            
            @keyframes fadeInDown {
                from {
                    opacity: 0;
                    transform: translateY(-10px);
                }
                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }
            
            @keyframes fadeOutUp {
                from {
                    opacity: 1;
                    transform: translateY(0);
                }
                to {
                    opacity: 0;
                    transform: translateY(-10px);
                }
            }
            
            @keyframes slideInDown {
                from {
                    opacity: 0;
                    transform: translateY(-20px);
                }
                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }
            
            .spinner {
                display: inline-block;
                width: 16px;
                height: 16px;
                border: 2px solid rgba(255,255,255,0.3);
                border-top-color: #fff;
                border-radius: 50%;
                animation: spin 0.6s linear infinite;
                vertical-align: middle;
                margin-right: 0.5rem;
            }
            
            @keyframes spin {
                to { transform: rotate(360deg); }
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
                initFormValidation();
            });
        } else {
            addStyles();
            initFormValidation();
        }
    }

    init();
})();

