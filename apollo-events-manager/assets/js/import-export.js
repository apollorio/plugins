/**
 * Import/Export Module JavaScript
 *
 * @package Apollo_Events_Manager
 * @since 2.0.0
 */

(function($) {
    'use strict';

    /**
     * Import Handler
     */
    class ApolloImporter {
        constructor() {
            this.form = $('#apollo-import-form');
            this.dropzone = $('#import-dropzone');
            this.fileInput = $('#import_file');
            this.preview = $('#import-preview');
            this.progress = $('#import-progress');
            this.options = $('.apollo-ie-options');
            this.submitBtn = $('#import-submit');

            this.file = null;

            this.init();
        }

        init() {
            this.bindEvents();
        }

        bindEvents() {
            // Dropzone click
            this.dropzone.on('click', () => this.fileInput.trigger('click'));

            // File selection
            this.fileInput.on('change', (e) => this.handleFileSelect(e));

            // Drag and drop
            this.dropzone.on('dragover dragenter', (e) => {
                e.preventDefault();
                e.stopPropagation();
                this.dropzone.addClass('dragover');
            });

            this.dropzone.on('dragleave dragend drop', (e) => {
                e.preventDefault();
                e.stopPropagation();
                this.dropzone.removeClass('dragover');
            });

            this.dropzone.on('drop', (e) => {
                const files = e.originalEvent.dataTransfer.files;
                if (files.length) {
                    this.fileInput[0].files = files;
                    this.handleFileSelect({ target: this.fileInput[0] });
                }
            });

            // Form submit
            this.form.on('submit', (e) => this.handleSubmit(e));
        }

        handleFileSelect(e) {
            const file = e.target.files[0];

            if (!file) {
                return;
            }

            const ext = file.name.split('.').pop().toLowerCase();

            if (!['csv', 'json'].includes(ext)) {
                alert('Formato não suportado. Use CSV ou JSON.');
                return;
            }

            this.file = file;
            this.dropzone.addClass('has-file');
            this.dropzone.find('.apollo-ie-dropzone__content').html(`
                <i class="dashicons dashicons-yes-alt"></i>
                <p>${file.name}</p>
                <span>${this.formatFileSize(file.size)}</span>
            `);

            this.options.slideDown();
            this.submitBtn.slideDown();

            // Preview file
            this.previewFile(file);
        }

        previewFile(file) {
            const reader = new FileReader();

            reader.onload = (e) => {
                const content = e.target.result;
                const ext = file.name.split('.').pop().toLowerCase();

                let previewHtml = '<h4>Prévia do arquivo:</h4>';

                if (ext === 'csv') {
                    previewHtml += this.previewCSV(content);
                } else if (ext === 'json') {
                    previewHtml += this.previewJSON(content);
                }

                this.preview.html(previewHtml).slideDown();
            };

            reader.readAsText(file);
        }

        previewCSV(content) {
            const lines = content.split('\n');
            const headers = lines[0].split(';').map(h => h.replace(/"/g, '').trim());

            let html = '<table><thead><tr>';
            headers.forEach(h => {
                html += `<th>${this.escapeHtml(h)}</th>`;
            });
            html += '</tr></thead><tbody>';

            // Show first 5 rows
            for (let i = 1; i < Math.min(6, lines.length); i++) {
                if (!lines[i].trim()) continue;

                const cols = lines[i].split(';').map(c => c.replace(/"/g, '').trim());
                html += '<tr>';
                cols.forEach(c => {
                    html += `<td>${this.escapeHtml(c.substring(0, 50))}${c.length > 50 ? '...' : ''}</td>`;
                });
                html += '</tr>';
            }

            html += '</tbody></table>';

            if (lines.length > 6) {
                html += `<p style="margin-top:10px;color:#666;font-size:12px;">... e mais ${lines.length - 6} linhas</p>`;
            }

            return html;
        }

        previewJSON(content) {
            try {
                const data = JSON.parse(content);
                const items = Array.isArray(data) ? data : [data];

                if (!items.length) {
                    return '<p>Nenhum evento encontrado.</p>';
                }

                const keys = Object.keys(items[0]).slice(0, 6);

                let html = '<table><thead><tr>';
                keys.forEach(k => {
                    html += `<th>${this.escapeHtml(k)}</th>`;
                });
                html += '</tr></thead><tbody>';

                items.slice(0, 5).forEach(item => {
                    html += '<tr>';
                    keys.forEach(k => {
                        const val = String(item[k] || '');
                        html += `<td>${this.escapeHtml(val.substring(0, 50))}${val.length > 50 ? '...' : ''}</td>`;
                    });
                    html += '</tr>';
                });

                html += '</tbody></table>';

                if (items.length > 5) {
                    html += `<p style="margin-top:10px;color:#666;font-size:12px;">... e mais ${items.length - 5} eventos</p>`;
                }

                return html;
            } catch (e) {
                return '<p style="color:#d63638;">Erro ao ler JSON: ' + e.message + '</p>';
            }
        }

        handleSubmit(e) {
            e.preventDefault();

            if (!this.file) {
                alert('Selecione um arquivo para importar.');
                return;
            }

            if (!confirm(apolloImportExport.i18n.confirm)) {
                return;
            }

            const formData = new FormData();
            formData.append('action', 'apollo_import_events');
            formData.append('nonce', apolloImportExport.nonce);
            formData.append('import_file', this.file);
            formData.append('import_status', $('#import_status').val());
            formData.append('skip_existing', $('input[name="skip_existing"]').is(':checked') ? '1' : '0');

            this.showProgress();

            $.ajax({
                url: apolloImportExport.ajaxUrl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                xhr: () => {
                    const xhr = new XMLHttpRequest();
                    xhr.upload.addEventListener('progress', (e) => {
                        if (e.lengthComputable) {
                            const pct = Math.round((e.loaded / e.total) * 50);
                            this.updateProgress(pct);
                        }
                    });
                    return xhr;
                },
                success: (response) => {
                    this.updateProgress(100);

                    if (response.success) {
                        this.showResults(response.data);
                    } else {
                        this.showError(response.data.message);
                    }
                },
                error: (xhr, status, error) => {
                    this.showError(error);
                }
            });
        }

        showProgress() {
            this.preview.slideUp();
            this.submitBtn.slideUp();
            this.progress.slideDown();
            this.progress.find('.apollo-ie-progress__text').text(apolloImportExport.i18n.importing);
        }

        updateProgress(pct) {
            this.progress.find('.apollo-ie-progress__fill').css('width', pct + '%');
        }

        showResults(data) {
            this.progress.addClass('apollo-ie-progress--complete');
            this.progress.find('.apollo-ie-progress__text').text(apolloImportExport.i18n.success);

            let html = `
                <div class="apollo-ie-results apollo-ie-results--success">
                    <strong>${apolloImportExport.i18n.success}</strong>
                    <div class="apollo-ie-results__stats">
                        <div class="apollo-ie-results__stat apollo-ie-results__stat--imported">
                            <span class="apollo-ie-results__stat-value">${data.imported}</span>
                            <span class="apollo-ie-results__stat-label">Importados</span>
                        </div>
                        <div class="apollo-ie-results__stat apollo-ie-results__stat--skipped">
                            <span class="apollo-ie-results__stat-value">${data.skipped}</span>
                            <span class="apollo-ie-results__stat-label">Ignorados</span>
                        </div>
                        <div class="apollo-ie-results__stat apollo-ie-results__stat--errors">
                            <span class="apollo-ie-results__stat-value">${data.errors}</span>
                            <span class="apollo-ie-results__stat-label">Erros</span>
                        </div>
                    </div>
                </div>
            `;

            this.progress.after(html);

            // Reset form after 2 seconds
            setTimeout(() => {
                this.reset();
            }, 2000);
        }

        showError(message) {
            this.progress.addClass('apollo-ie-progress--error');
            this.progress.find('.apollo-ie-progress__text').text(apolloImportExport.i18n.error + ': ' + message);
        }

        reset() {
            this.file = null;
            this.fileInput.val('');
            this.dropzone.removeClass('has-file');
            this.dropzone.find('.apollo-ie-dropzone__content').html(`
                <i class="dashicons dashicons-upload"></i>
                <p>Arraste um arquivo ou clique para selecionar</p>
                <span>CSV ou JSON</span>
            `);
            this.options.slideUp();
            this.preview.slideUp().empty();
            this.progress.slideUp().removeClass('apollo-ie-progress--complete apollo-ie-progress--error');
            this.progress.find('.apollo-ie-progress__fill').css('width', '0');
            this.submitBtn.slideUp();
        }

        formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }

        escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    }

    /**
     * Copy URL Handler
     */
    class ApolloCopyUrl {
        constructor() {
            this.init();
        }

        init() {
            $(document).on('click', '.apollo-copy-url', (e) => {
                e.preventDefault();
                const btn = $(e.currentTarget);
                const targetId = btn.data('target');
                const url = $('#' + targetId).text();

                this.copyToClipboard(url, btn);
            });
        }

        async copyToClipboard(text, btn) {
            try {
                await navigator.clipboard.writeText(text);

                const originalText = btn.text();
                btn.addClass('copied').text('Copiado!');

                setTimeout(() => {
                    btn.removeClass('copied').text(originalText);
                }, 2000);
            } catch (err) {
                // Fallback for older browsers
                const textarea = document.createElement('textarea');
                textarea.value = text;
                document.body.appendChild(textarea);
                textarea.select();
                document.execCommand('copy');
                document.body.removeChild(textarea);

                const originalText = btn.text();
                btn.addClass('copied').text('Copiado!');

                setTimeout(() => {
                    btn.removeClass('copied').text(originalText);
                }, 2000);
            }
        }
    }

    /**
     * Export Form Handler
     */
    class ApolloExporter {
        constructor() {
            this.form = $('#export-form');
            this.init();
        }

        init() {
            // Add loading state on submit
            $('form[action*="admin-post.php"]').on('submit', function() {
                $(this).find('.button-primary')
                    .prop('disabled', true)
                    .prepend('<span class="spinner is-active" style="float:none;margin:0 5px 0 0;"></span>');

                // Re-enable after download
                setTimeout(() => {
                    $(this).find('.button-primary')
                        .prop('disabled', false)
                        .find('.spinner').remove();
                }, 3000);
            });
        }
    }

    // Initialize on DOM ready
    $(document).ready(function() {
        if ($('.apollo-import-export').length) {
            new ApolloImporter();
            new ApolloCopyUrl();
            new ApolloExporter();
        }
    });

})(jQuery);
