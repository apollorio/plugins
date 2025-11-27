/**
 * Apollo Social – Quill Rich Text Editor with Custom Image Upload Handler
 *
 * This module initializes a Quill editor for the Apollo Social document editor
 * and integrates a custom image upload flow that:
 *   1. Opens a native file picker when the image button is clicked.
 *   2. Validates file type (JPEG, PNG, GIF, WebP) and size (max 5 MB).
 *   3. Uploads the selected file to WordPress via AJAX (admin-ajax.php).
 *   4. Displays upload progress feedback to the user.
 *   5. On success, inserts the returned image URL into the editor.
 *   6. On failure, shows a localized error message.
 *
 * Security considerations implemented:
 *   - Nonce verification on all AJAX requests (wp_nonce).
 *   - Server-side validation of file type, size, and user capability.
 *   - Client-side pre-validation to give immediate feedback before upload.
 *   - Sanitized file names on the server (handled by WordPress media library).
 *
 * Architectural intent:
 *   - This editor is part of the Apollo Documents System (/doc/, /pla/).
 *   - It replaces the plain <textarea> with a full WYSIWYG experience.
 *   - Images are stored in the WordPress Media Library for consistency.
 *   - The module is dependency-injected with configuration via apolloQuillConfig.
 *
 * @package ApolloSocial
 * @since   1.1.0
 * @author  Apollo Team
 */

(function (window, document) {
    'use strict';

    /* =========================================================================
     * CONFIGURATION & CONSTANTS
     * =========================================================================
     * apolloQuillConfig is injected by PHP via wp_localize_script and contains:
     *   - ajaxUrl: WordPress AJAX endpoint (admin-ajax.php)
     *   - uploadAction: AJAX action name for image upload
     *   - nonce: Security nonce for verification
     *   - maxFileSize: Maximum allowed file size in bytes (default 5MB)
     *   - allowedTypes: Array of allowed MIME types
     *   - i18n: Localized strings for user messages
     */
    const config = window.apolloQuillConfig || {
        ajaxUrl: '/wp-admin/admin-ajax.php',
        uploadAction: 'apollo_upload_editor_image',
        nonce: '',
        maxFileSize: 5 * 1024 * 1024, // 5 MB
        allowedTypes: ['image/jpeg', 'image/png', 'image/gif', 'image/webp'],
        i18n: {
            uploading: 'Enviando imagem...',
            uploadSuccess: 'Imagem inserida com sucesso!',
            uploadError: 'Erro ao enviar imagem. Tente novamente.',
            invalidType: 'Tipo de arquivo não permitido. Use JPEG, PNG, GIF ou WebP.',
            fileTooLarge: 'Arquivo muito grande. Máximo: 5 MB.',
            selectImage: 'Selecionar imagem',
            networkError: 'Erro de rede. Verifique sua conexão.',
            serverError: 'Erro no servidor. Tente novamente mais tarde.',
            permissionDenied: 'Você não tem permissão para enviar imagens.'
        }
    };

    // File size limit in bytes (5 MB default, configurable via PHP)
    const MAX_FILE_SIZE = config.maxFileSize;

    // Allowed MIME types for image uploads
    const ALLOWED_MIME_TYPES = config.allowedTypes;

    /* =========================================================================
     * UTILITY FUNCTIONS
     * ========================================================================= */

    /**
     * Format file size in human-readable format.
     *
     * @param {number} bytes - File size in bytes.
     * @returns {string} Formatted size (e.g., "2.5 MB").
     */
    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }

    /**
     * Validate file before upload.
     *
     * Performs client-side validation to give immediate user feedback before
     * attempting the upload. This reduces server load and improves UX.
     *
     * @param {File} file - The file object from the file input.
     * @returns {{valid: boolean, error: string|null}} Validation result.
     */
    function validateFile(file) {
        // Check 1: File type validation
        // We validate against a whitelist of allowed MIME types to prevent
        // uploading potentially dangerous files (e.g., PHP, SVG with scripts).
        if (!ALLOWED_MIME_TYPES.includes(file.type)) {
            return {
                valid: false,
                error: config.i18n.invalidType
            };
        }

        // Check 2: File size validation
        // Large files can cause server timeouts and consume bandwidth.
        // The limit is configurable via PHP (default 5 MB).
        if (file.size > MAX_FILE_SIZE) {
            return {
                valid: false,
                error: config.i18n.fileTooLarge.replace('5 MB', formatFileSize(MAX_FILE_SIZE))
            };
        }

        return { valid: true, error: null };
    }

    /**
     * Show a toast notification to the user.
     *
     * Creates a temporary notification element that auto-dismisses.
     * Used for upload progress, success, and error messages.
     *
     * @param {string} message - The message to display.
     * @param {string} type - Notification type: 'info', 'success', 'error'.
     * @param {number} duration - How long to show the notification (ms).
     * @returns {HTMLElement} The notification element (for manual removal).
     */
    function showNotification(message, type = 'info', duration = 3000) {
        // Create notification container if it doesn't exist
        let container = document.getElementById('apollo-notifications');
        if (!container) {
            container = document.createElement('div');
            container.id = 'apollo-notifications';
            container.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                z-index: 10000;
                display: flex;
                flex-direction: column;
                gap: 10px;
                pointer-events: none;
            `;
            document.body.appendChild(container);
        }

        // Create notification element
        const notification = document.createElement('div');
        notification.className = `apollo-notification apollo-notification--${type}`;
        notification.style.cssText = `
            padding: 12px 20px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            pointer-events: auto;
            animation: slideIn 0.3s ease-out;
            display: flex;
            align-items: center;
            gap: 10px;
            max-width: 350px;
        `;

        // Type-specific styling
        const styles = {
            info: { bg: '#3b82f6', color: '#fff', icon: '⏳' },
            success: { bg: '#10b981', color: '#fff', icon: '✅' },
            error: { bg: '#ef4444', color: '#fff', icon: '❌' }
        };
        const style = styles[type] || styles.info;
        notification.style.backgroundColor = style.bg;
        notification.style.color = style.color;
        notification.innerHTML = `<span>${style.icon}</span><span>${message}</span>`;

        container.appendChild(notification);

        // Auto-remove after duration (unless duration is 0 for persistent)
        if (duration > 0) {
            setTimeout(() => {
                notification.style.animation = 'slideOut 0.3s ease-in';
                setTimeout(() => notification.remove(), 300);
            }, duration);
        }

        return notification;
    }

    /* =========================================================================
     * IMAGE UPLOAD HANDLER
     * =========================================================================
     * This is the core image upload logic. It:
     *   1. Creates a FormData object with the file and security nonce.
     *   2. Sends the file to WordPress via XMLHttpRequest (for progress events).
     *   3. Handles progress, success, and error states.
     *   4. Returns a Promise that resolves with the image URL or rejects with error.
     */

    /**
     * Upload an image file to WordPress Media Library.
     *
     * Uses XMLHttpRequest instead of fetch() to support upload progress events.
     * The server-side handler (apollo_upload_editor_image) validates the file,
     * saves it to the Media Library, and returns the attachment URL.
     *
     * Security flow:
     *   1. Client validates file type and size (immediate feedback).
     *   2. Request includes nonce for CSRF protection.
     *   3. Server validates nonce, user capability (upload_files), and file.
     *   4. WordPress handles file sanitization and storage.
     *
     * @param {File} file - The image file to upload.
     * @param {function} onProgress - Callback for progress updates (0-100).
     * @returns {Promise<string>} Resolves with the image URL on success.
     */
    function uploadImage(file, onProgress) {
        return new Promise((resolve, reject) => {
            // Step 1: Client-side validation (immediate feedback)
            const validation = validateFile(file);
            if (!validation.valid) {
                reject(new Error(validation.error));
                return;
            }

            // Step 2: Prepare FormData with file and security nonce
            // The nonce is generated by WordPress and validated on the server.
            // This prevents CSRF attacks where a malicious site tricks a logged-in
            // user into uploading files without their knowledge.
            const formData = new FormData();
            formData.append('action', config.uploadAction);
            formData.append('nonce', config.nonce);
            formData.append('image', file);

            // Step 3: Create XMLHttpRequest for progress support
            // We use XHR instead of fetch() because fetch() doesn't support
            // upload progress events, which are important for UX with large files.
            const xhr = new XMLHttpRequest();

            // Step 4: Set up progress event handler
            // This fires periodically during upload and provides percentage complete.
            xhr.upload.addEventListener('progress', (event) => {
                if (event.lengthComputable && typeof onProgress === 'function') {
                    const percentComplete = Math.round((event.loaded / event.total) * 100);
                    onProgress(percentComplete);
                }
            });

            // Step 5: Handle successful upload completion
            xhr.addEventListener('load', () => {
                // HTTP 200 doesn't mean success – we need to check the response body
                if (xhr.status >= 200 && xhr.status < 300) {
                    try {
                        const response = JSON.parse(xhr.responseText);

                        if (response.success && response.data && response.data.url) {
                            // Success: resolve with the image URL
                            resolve(response.data.url);
                        } else {
                            // Server returned success: false or missing URL
                            const errorMsg = response.data && response.data.message
                                ? response.data.message
                                : config.i18n.serverError;
                            reject(new Error(errorMsg));
                        }
                    } catch (parseError) {
                        // Response wasn't valid JSON
                        console.error('[Apollo Quill] JSON parse error:', parseError);
                        reject(new Error(config.i18n.serverError));
                    }
                } else if (xhr.status === 403) {
                    // Permission denied (nonce failure or capability check)
                    reject(new Error(config.i18n.permissionDenied));
                } else {
                    // Other HTTP errors (500, 502, etc.)
                    reject(new Error(config.i18n.serverError));
                }
            });

            // Step 6: Handle network errors
            // This catches cases where the request couldn't be sent at all
            // (offline, DNS failure, CORS issues, etc.)
            xhr.addEventListener('error', () => {
                console.error('[Apollo Quill] Network error during upload');
                reject(new Error(config.i18n.networkError));
            });

            // Step 7: Handle timeout (optional, but good for large files)
            xhr.addEventListener('timeout', () => {
                console.error('[Apollo Quill] Upload timed out');
                reject(new Error(config.i18n.networkError));
            });

            // Step 8: Send the request
            // POST to admin-ajax.php with FormData (multipart/form-data)
            xhr.open('POST', config.ajaxUrl, true);
            xhr.timeout = 60000; // 60 second timeout for large files
            xhr.send(formData);
        });
    }

    /* =========================================================================
     * QUILL CUSTOM IMAGE HANDLER
     * =========================================================================
     * This function is called when the user clicks the image button in Quill's
     * toolbar. It overrides Quill's default behavior (which just prompts for URL)
     * with our custom file upload flow.
     */

    /**
     * Custom image handler for Quill toolbar.
     *
     * Flow:
     *   1. Create a hidden file input element.
     *   2. Open the native file picker dialog.
     *   3. When user selects a file, validate and upload it.
     *   4. Show progress notification during upload.
     *   5. On success, insert the image into the editor at cursor position.
     *   6. On failure, show error notification.
     *
     * This approach is better than Quill's default URL prompt because:
     *   - Users can select local files instead of pasting URLs.
     *   - Images are stored in WordPress Media Library for consistency.
     *   - We can show upload progress and validate files.
     *
     * @param {Quill} quill - The Quill editor instance.
     */
    function createImageHandler(quill) {
        return function imageHandler() {
            // Step 1: Create a temporary file input element
            // We create this dynamically and remove it after use to keep the DOM clean.
            const fileInput = document.createElement('input');
            fileInput.type = 'file';
            fileInput.accept = 'image/jpeg,image/png,image/gif,image/webp';
            fileInput.style.display = 'none';

            // Step 2: Handle file selection
            fileInput.addEventListener('change', async (event) => {
                const file = event.target.files[0];
                if (!file) {
                    fileInput.remove();
                    return;
                }

                // Step 3: Show upload progress notification
                // We keep a reference to update/remove it as the upload progresses.
                let progressNotification = showNotification(
                    `${config.i18n.uploading} 0%`,
                    'info',
                    0 // Persistent until we remove it
                );

                try {
                    // Step 4: Upload the file with progress tracking
                    const imageUrl = await uploadImage(file, (percent) => {
                        // Update progress notification with current percentage
                        const messageSpan = progressNotification.querySelector('span:last-child');
                        if (messageSpan) {
                            messageSpan.textContent = `${config.i18n.uploading} ${percent}%`;
                        }
                    });

                    // Step 5: Remove progress notification
                    progressNotification.remove();

                    // Step 6: Insert the image into the editor at current cursor position
                    // Quill's getSelection() returns the current selection/cursor range.
                    // We use insertEmbed to add the image as a block-level element.
                    const range = quill.getSelection(true);
                    quill.insertEmbed(range.index, 'image', imageUrl, 'user');

                    // Step 7: Move cursor after the image
                    // This provides better UX – user can continue typing after the image.
                    quill.setSelection(range.index + 1, 0);

                    // Step 8: Show success notification
                    showNotification(config.i18n.uploadSuccess, 'success', 3000);

                    // Step 9: Trigger change event for auto-save
                    // The editor's change listener will pick this up and auto-save.
                    quill.root.dispatchEvent(new Event('input', { bubbles: true }));

                } catch (error) {
                    // Step 10: Handle upload errors
                    progressNotification.remove();
                    showNotification(error.message || config.i18n.uploadError, 'error', 5000);
                    console.error('[Apollo Quill] Image upload failed:', error);
                }

                // Clean up the temporary file input
                fileInput.remove();
            });

            // Step 3: Trigger the file picker dialog
            // We append to body temporarily to ensure the click works in all browsers.
            document.body.appendChild(fileInput);
            fileInput.click();
        };
    }

    /* =========================================================================
     * QUILL INITIALIZATION
     * =========================================================================
     * Main initialization function that sets up Quill with our custom configuration.
     */

    /**
     * Initialize Quill editor with custom image upload handler.
     *
     * This function:
     *   1. Finds the target container element.
     *   2. Configures Quill with appropriate modules and toolbar.
     *   3. Registers our custom image handler.
     *   4. Sets up two-way binding with a hidden input for form submission.
     *   5. Configures auto-save integration.
     *
     * @param {string|HTMLElement} container - Container element or selector.
     * @param {object} options - Additional Quill options.
     * @returns {Quill|null} The Quill instance, or null if initialization failed.
     */
    function initApolloQuill(container, options = {}) {
        // Step 1: Resolve container element
        const containerEl = typeof container === 'string'
            ? document.querySelector(container)
            : container;

        if (!containerEl) {
            console.error('[Apollo Quill] Container element not found:', container);
            return null;
        }

        // Step 2: Check if Quill is available
        if (typeof Quill === 'undefined') {
            console.error('[Apollo Quill] Quill library not loaded. Please include quill.min.js');
            return null;
        }

        // Step 3: Configure Quill toolbar
        // We include common formatting options plus our custom image button.
        const toolbarOptions = options.toolbar || [
            // Text formatting
            [{ 'header': [1, 2, 3, false] }],
            ['bold', 'italic', 'underline', 'strike'],
            
            // Colors and background
            [{ 'color': [] }, { 'background': [] }],
            
            // Lists and alignment
            [{ 'list': 'ordered' }, { 'list': 'bullet' }],
            [{ 'align': [] }],
            
            // Media and links
            ['link', 'image'],
            
            // Block elements
            ['blockquote', 'code-block'],
            
            // Clear formatting
            ['clean']
        ];

        // Step 4: Create Quill instance
        const quill = new Quill(containerEl, {
            theme: options.theme || 'snow',
            placeholder: options.placeholder || 'Comece a escrever seu documento...',
            modules: {
                toolbar: {
                    container: toolbarOptions,
                    handlers: {
                        // Override the default image handler with our custom one
                        image: createImageHandler(null) // Will be updated after creation
                    }
                }
            },
            ...options
        });

        // Step 5: Update image handler with correct Quill reference
        // We need to do this after creation because the handler needs the instance.
        const toolbar = quill.getModule('toolbar');
        toolbar.addHandler('image', createImageHandler(quill));

        // Step 6: Set up hidden input for form submission
        // If there's an existing textarea, we convert it to a hidden input and
        // sync Quill's content to it on every change.
        const hiddenInput = options.hiddenInput
            ? document.querySelector(options.hiddenInput)
            : containerEl.parentElement.querySelector('input[type="hidden"]');

        if (hiddenInput) {
            // Sync Quill content to hidden input on every change
            quill.on('text-change', () => {
                hiddenInput.value = quill.root.innerHTML;
            });

            // Load initial content from hidden input
            if (hiddenInput.value) {
                quill.clipboard.dangerouslyPasteHTML(hiddenInput.value);
            }
        }

        // Step 7: Dispatch custom event for integration with other scripts
        const initEvent = new CustomEvent('apolloQuillReady', {
            detail: { quill, container: containerEl }
        });
        document.dispatchEvent(initEvent);

        console.log('[Apollo Quill] Editor initialized successfully');
        return quill;
    }

    /* =========================================================================
     * CSS INJECTION FOR NOTIFICATIONS
     * ========================================================================= */

    /**
     * Inject notification animation CSS.
     * We do this via JavaScript to keep the component self-contained.
     */
    function injectStyles() {
        if (document.getElementById('apollo-quill-styles')) return;

        const style = document.createElement('style');
        style.id = 'apollo-quill-styles';
        style.textContent = `
            @keyframes slideIn {
                from {
                    transform: translateX(100%);
                    opacity: 0;
                }
                to {
                    transform: translateX(0);
                    opacity: 1;
                }
            }

            @keyframes slideOut {
                from {
                    transform: translateX(0);
                    opacity: 1;
                }
                to {
                    transform: translateX(100%);
                    opacity: 0;
                }
            }

            /* Quill editor container styling for Apollo */
            .apollo-quill-container {
                background: white;
                border-radius: 8px;
                box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
                overflow: hidden;
            }

            .apollo-quill-container .ql-toolbar {
                border: none;
                border-bottom: 2px solid #e2e8f0;
                background: #f7fafc;
            }

            .apollo-quill-container .ql-container {
                border: none;
                font-family: Georgia, 'Times New Roman', serif;
                font-size: 16px;
                line-height: 1.6;
            }

            .apollo-quill-container .ql-editor {
                min-height: 400px;
                padding: 30px 40px;
            }

            .apollo-quill-container .ql-editor.ql-blank::before {
                color: #a0aec0;
                font-style: normal;
            }

            /* Image loading state */
            .apollo-quill-container .ql-editor img {
                max-width: 100%;
                height: auto;
                border-radius: 4px;
            }

            .apollo-quill-container .ql-editor img[data-loading="true"] {
                opacity: 0.5;
                filter: grayscale(100%);
            }
        `;
        document.head.appendChild(style);
    }

    /* =========================================================================
     * AUTO-INITIALIZATION
     * =========================================================================
     * When the DOM is ready, we look for elements with [data-apollo-quill]
     * and automatically initialize them.
     */

    document.addEventListener('DOMContentLoaded', () => {
        // Inject component styles
        injectStyles();

        // Find and initialize all Quill containers
        const containers = document.querySelectorAll('[data-apollo-quill]');
        containers.forEach((container) => {
            // Parse options from data attributes
            const options = {
                placeholder: container.dataset.placeholder,
                hiddenInput: container.dataset.hiddenInput,
                theme: container.dataset.theme || 'snow'
            };

            initApolloQuill(container, options);
        });
    });

    /* =========================================================================
     * PUBLIC API
     * =========================================================================
     * Export functions for external use (e.g., manual initialization).
     */

    window.ApolloQuill = {
        init: initApolloQuill,
        uploadImage: uploadImage,
        showNotification: showNotification,
        config: config
    };

})(window, document);
