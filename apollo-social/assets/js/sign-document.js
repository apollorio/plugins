/**
 * Apollo Sign Document Module
 *
 * Handles document signing workflow with REST API integration.
 * Uses data-ap-* hooks for DOM binding and uni.css classes.
 *
 * @package Apollo\Modules\Documents
 * @since   2.0.0
 */

(function () {
    'use strict';

    /**
     * SignatureModal class
     * Manages the signature modal UI and interactions
     */
    class SignatureModal {
        /**
         * Constructor
         *
         * @param {Object} config Configuration from server
         */
        constructor(config) {
            this.config = config;
            this.modal = document.getElementById('apollo-signature-modal');
            this.termsChecked = false;
            this.isSubmitting = false;

            if (!this.modal) {
                console.warn('SignatureModal: Modal element not found');
                return;
            }

            this.bindElements();
            this.bindEvents();
            this.loadBackends();
        }

        /**
         * Bind DOM elements
         */
        bindElements() {
            this.elements = {
                closeBtn: this.modal.querySelector('[data-ap-close-modal]'),
                termCheckboxes: this.modal.querySelectorAll('[data-ap-sign-term]'),
                providerButtons: this.modal.querySelectorAll('[data-ap-sign-provider]'),
                errorAlert: this.modal.querySelector('#sign-error-alert'),
                errorMessage: this.modal.querySelector('#sign-error-message'),
                successCard: this.modal.querySelector('#sign-success-card'),
                termsSection: this.modal.querySelector('#sign-terms-section'),
                providersSection: this.modal.querySelector('#sign-providers-section'),
                statusBadge: this.modal.querySelector('#sign-status-badge'),
                resultTimestamp: this.modal.querySelector('#sign-result-timestamp'),
                resultCode: this.modal.querySelector('#sign-result-code'),
                resultHash: this.modal.querySelector('#sign-result-hash'),
                docTitle: this.modal.querySelector('#sign-doc-title')
            };
        }

        /**
         * Bind event listeners
         */
        bindEvents() {
            // Close button
            if (this.elements.closeBtn) {
                this.elements.closeBtn.addEventListener('click', () => this.close());
            }

            // Overlay click to close
            this.modal.addEventListener('click', (e) => {
                if (e.target === this.modal) {
                    this.close();
                }
            });

            // Keyboard navigation
            document.addEventListener('keydown', (e) => {
                if (!this.isOpen()) return;

                switch (e.key) {
                    case 'Escape':
                        e.preventDefault();
                        this.close();
                        break;
                    case 'Tab':
                        this.trapFocus(e);
                        break;
                }
            });

            // Term checkboxes
            this.elements.termCheckboxes.forEach(checkbox => {
                checkbox.addEventListener('change', () => this.validateTerms());
            });

            // Provider buttons
            this.elements.providerButtons.forEach(btn => {
                btn.addEventListener('click', () => {
                    const provider = btn.dataset.apSignProvider;
                    if (provider) {
                        this.sign(provider);
                    }
                });

                // Keyboard accessibility for buttons
                btn.addEventListener('keydown', (e) => {
                    if (e.key === 'Enter' || e.key === ' ') {
                        e.preventDefault();
                        btn.click();
                    }
                });
            });
        }

        /**
         * Trap focus within modal for accessibility
         *
         * @param {KeyboardEvent} e Keyboard event
         */
        trapFocus(e) {
            const focusableElements = this.modal.querySelectorAll(
                'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
            );
            const focusable = Array.from(focusableElements).filter(
                el => !el.disabled && el.offsetParent !== null
            );

            if (focusable.length === 0) return;

            const firstFocusable = focusable[0];
            const lastFocusable = focusable[focusable.length - 1];

            if (e.shiftKey) {
                if (document.activeElement === firstFocusable) {
                    e.preventDefault();
                    lastFocusable.focus();
                }
            } else {
                if (document.activeElement === lastFocusable) {
                    e.preventDefault();
                    firstFocusable.focus();
                }
            }
        }        /**
         * Load available backends from REST API
         */
        async loadBackends() {
            if (!this.config.backendsUrl) return;

            try {
                const response = await fetch(this.config.backendsUrl, {
                    method: 'GET',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-WP-Nonce': this.config.nonce
                    }
                });

                if (!response.ok) {
                    throw new Error('Failed to load backends');
                }

                const data = await response.json();

                if (data.backends) {
                    this.updateBackendAvailability(data.backends);
                }
            } catch (error) {
                console.warn('SignatureModal: Could not load backends', error);
            }
        }

        /**
         * Update button states based on backend availability
         *
         * @param {Array} backends Available backends
         */
        updateBackendAvailability(backends) {
            const availableIds = backends
                .filter(b => b.available)
                .map(b => b.id);

            this.elements.providerButtons.forEach(btn => {
                const provider = btn.dataset.apSignProvider;

                // Map button providers to backend IDs
                const backendMap = {
                    'govbr': 'govbr',
                    'icp': 'demoiselle',
                    'local-stub': 'local-stub'
                };

                const backendId = backendMap[provider] || provider;
                const isAvailable = availableIds.includes(backendId);

                if (isAvailable) {
                    btn.classList.add('ap-btn--available');
                } else {
                    btn.classList.add('ap-btn--unavailable');
                }
            });
        }

        /**
         * Validate term checkboxes
         */
        validateTerms() {
            const allChecked = Array.from(this.elements.termCheckboxes)
                .every(cb => cb.checked);

            this.termsChecked = allChecked;

            // Update button states
            this.elements.providerButtons.forEach(btn => {
                btn.disabled = !allChecked || this.isSubmitting;
            });

            // Hide error if terms are now valid
            if (allChecked && this.elements.errorAlert) {
                this.elements.errorAlert.style.display = 'none';
            }
        }

        /**
         * Show error message
         *
         * @param {string} message Error message to display
         */
        showError(message) {
            if (this.elements.errorAlert && this.elements.errorMessage) {
                this.elements.errorMessage.textContent = message;
                this.elements.errorAlert.style.display = 'flex';
            }
        }

        /**
         * Hide error message
         */
        hideError() {
            if (this.elements.errorAlert) {
                this.elements.errorAlert.style.display = 'none';
            }
        }

        /**
         * Perform signature via REST API
         *
         * @param {string} provider Provider identifier
         */
        async sign(provider) {
            // Validate terms first
            if (!this.termsChecked) {
                this.showError(this.config.i18n.checkTerms);
                return;
            }

            // Prevent double submission
            if (this.isSubmitting) return;

            this.isSubmitting = true;
            this.hideError();

            // Disable all buttons and show loading state
            this.elements.providerButtons.forEach(btn => {
                btn.disabled = true;
                if (btn.dataset.apSignProvider === provider) {
                    btn.classList.add('ap-btn--loading');
                    const originalText = btn.querySelector('span');
                    if (originalText) {
                        btn.dataset.originalText = originalText.textContent;
                        originalText.textContent = this.config.i18n.signing;
                    }
                }
            });

            try {
                const response = await fetch(this.config.restUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-WP-Nonce': this.config.nonce
                    },
                    body: JSON.stringify({
                        backend: provider === 'icp' ? 'demoiselle' : provider,
                        certificate_data: {} // Would be populated by actual certificate selection
                    })
                });

                const data = await response.json();

                if (response.ok && data.signature) {
                    this.handleSuccess(data);
                } else {
                    throw new Error(data.message || this.config.i18n.error);
                }
            } catch (error) {
                console.error('SignatureModal: Sign error', error);
                this.showError(error.message || this.config.i18n.connectionError);
                this.resetButtons();
            }
        }

        /**
         * Handle successful signature
         *
         * @param {Object} data Response data
         */
        handleSuccess(data) {
            // Hide terms and providers
            if (this.elements.termsSection) {
                this.elements.termsSection.style.display = 'none';
            }
            if (this.elements.providersSection) {
                this.elements.providersSection.style.display = 'none';
            }

            // Show success card
            if (this.elements.successCard) {
                this.elements.successCard.style.display = 'block';
            }

            // Update result fields
            if (this.elements.resultTimestamp) {
                const date = new Date(data.signature.signed_at);
                this.elements.resultTimestamp.textContent = date.toLocaleString('pt-BR');
            }

            if (this.elements.resultCode && data.signature.id) {
                const code = data.signature.backend.toUpperCase() + '-' +
                    String(data.signature.id).padStart(6, '0');
                this.elements.resultCode.textContent = code;
            }

            if (this.elements.resultHash && data.signature.signature_hash) {
                this.elements.resultHash.textContent = data.signature.signature_hash;
            }

            // Update status badge
            if (this.elements.statusBadge) {
                this.elements.statusBadge.className = 'ap-badge ap-badge--success ap-badge--sm';
                this.elements.statusBadge.innerHTML = '<i class="ri-check-line"></i> ' +
                    this.config.i18n.signed;
            }

            // Dispatch custom event
            document.dispatchEvent(new CustomEvent('apollo:document:signed', {
                detail: data
            }));

            // Update page UI if needed
            this.updatePageUI(data);
        }

        /**
         * Update page UI after successful signature
         *
         * @param {Object} data Signature data
         */
        updatePageUI(data) {
            // Update status badge in editor navbar if exists
            const navbarBadge = document.querySelector('.navbar-actions .ap-badge');
            if (navbarBadge) {
                navbarBadge.className = 'ap-badge ap-badge--success';
                navbarBadge.innerHTML = '<span class="material-symbols-rounded" style="font-size: 14px;">verified</span> Assinado';
            }

            // Disable sign button
            const signBtn = document.getElementById('btn-prepare-sign');
            if (signBtn) {
                signBtn.disabled = true;
                signBtn.classList.remove('success');
                signBtn.classList.add('ap-btn--disabled');
                const btnText = signBtn.querySelector('span');
                if (btnText) {
                    btnText.textContent = 'Assinado';
                }
            }

            // Update save status
            const saveStatus = document.getElementById('save-status');
            if (saveStatus) {
                saveStatus.classList.remove('saving', 'error');
                saveStatus.classList.add('saved');
                const iconEl = saveStatus.querySelector('.material-symbols-rounded');
                const textEl = document.getElementById('save-text');
                if (iconEl) iconEl.textContent = 'verified';
                if (textEl) textEl.textContent = 'Documento assinado';
            }
        }

        /**
         * Reset button states after error
         */
        resetButtons() {
            this.isSubmitting = false;

            this.elements.providerButtons.forEach(btn => {
                btn.disabled = !this.termsChecked;
                btn.classList.remove('ap-btn--loading');

                if (btn.dataset.originalText) {
                    const textEl = btn.querySelector('span');
                    if (textEl) {
                        textEl.textContent = btn.dataset.originalText;
                    }
                    delete btn.dataset.originalText;
                }
            });
        }

        /**
         * Open the modal
         *
         * @param {Object} options Optional overrides
         */
        open(options = {}) {
            if (options.documentId) {
                this.config.documentId = options.documentId;
                this.config.restUrl = options.restUrl || this.config.restUrl;
            }

            if (options.title && this.elements.docTitle) {
                this.elements.docTitle.textContent = options.title;
            }

            // Reset state
            this.termsChecked = false;
            this.isSubmitting = false;
            this.hideError();

            // Reset checkboxes
            this.elements.termCheckboxes.forEach(cb => {
                cb.checked = false;
            });

            // Reset buttons
            this.elements.providerButtons.forEach(btn => {
                btn.disabled = true;
            });

            // Show/hide sections
            if (this.elements.termsSection) {
                this.elements.termsSection.style.display = '';
            }
            if (this.elements.providersSection) {
                this.elements.providersSection.style.display = '';
            }
            if (this.elements.successCard) {
                this.elements.successCard.style.display = 'none';
            }

            // Show modal
            this.modal.style.display = 'flex';
            requestAnimationFrame(() => {
                this.modal.classList.add('ap-is-open');
            });

            // Focus first checkbox
            const firstCheckbox = this.elements.termCheckboxes[0];
            if (firstCheckbox) {
                setTimeout(() => firstCheckbox.focus(), 100);
            }

            // Prevent body scroll
            document.body.style.overflow = 'hidden';
        }

        /**
         * Close the modal
         */
        close() {
            this.modal.classList.remove('ap-is-open');

            setTimeout(() => {
                this.modal.style.display = 'none';
                document.body.style.overflow = '';
            }, 200);
        }

        /**
         * Check if modal is open
         *
         * @returns {boolean}
         */
        isOpen() {
            return this.modal.classList.contains('ap-is-open');
        }
    }

    /**
     * Initialize on DOM ready
     */
    function init() {
        // Get config from embedded JSON
        const configEl = document.getElementById('apollo-signature-config');
        if (!configEl) {
            console.warn('SignatureModal: Configuration not found');
            return;
        }

        let config;
        try {
            config = JSON.parse(configEl.textContent);
        } catch (e) {
            console.error('SignatureModal: Invalid configuration', e);
            return;
        }

        // Create modal instance
        const modal = new SignatureModal(config);

        // Expose to global for external access
        window.ApolloSignatureModal = modal;

        // Bind trigger buttons
        const triggerButtons = document.querySelectorAll('[data-ap-open-signature-modal], #btn-prepare-sign');
        triggerButtons.forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();

                // Get document info from button data or page context
                const documentId = btn.dataset.documentId ||
                    document.getElementById('document-id')?.value ||
                    config.documentId;

                const title = btn.dataset.documentTitle ||
                    document.getElementById('document-title')?.textContent?.trim() ||
                    '';

                if (!documentId || documentId === '0') {
                    alert('Salve o documento antes de assinar.');
                    return;
                }

                modal.open({
                    documentId: documentId,
                    title: title,
                    restUrl: `/wp-json/apollo-social/v1/documents/${documentId}/sign`
                });
            });
        });
    }

    // Auto-init on DOMContentLoaded
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    // Export for module systems
    if (typeof module !== 'undefined' && module.exports) {
        module.exports = { SignatureModal };
    }
})();
