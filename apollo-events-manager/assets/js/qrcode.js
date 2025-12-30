/**
 * Apollo Events Manager - QR Code Module
 *
 * @package Apollo_Events_Manager
 * @since 2.0.0
 */

(function($) {
    'use strict';

    /**
     * QR Code Generator using qrcode.js library.
     */
    class ApolloQRGenerator {
        constructor() {
            this.init();
        }

        init() {
            this.initQRCodes();
            this.initDownloadButtons();
        }

        /**
         * Initialize QR codes that should be generated client-side.
         */
        initQRCodes() {
            $('[data-qr-generate]').each(function() {
                const $container = $(this);
                const data = $container.data('qr-data');
                const size = $container.data('size') || 200;
                const color = $container.data('color') || '000000';
                const bg = $container.data('bg') || 'ffffff';

                if (!data || typeof QRCode === 'undefined') return;

                // Clear existing content
                $container.empty();

                // Create canvas element
                const canvas = document.createElement('canvas');
                $container.append(canvas);

                // Generate QR code
                QRCode.toCanvas(canvas, data, {
                    width: size,
                    margin: 2,
                    color: {
                        dark: '#' + color,
                        light: '#' + bg
                    }
                }, function(error) {
                    if (error) {
                        console.error('QR Code generation failed:', error);
                    }
                });
            });
        }

        /**
         * Initialize download buttons.
         */
        initDownloadButtons() {
            $(document).on('click', '.apollo-qr-download-btn', (e) => {
                e.preventDefault();
                const $btn = $(e.currentTarget);
                this.handleDownload($btn);
            });
        }

        /**
         * Handle QR download.
         */
        handleDownload($btn) {
            const data = $btn.data('qr-data');
            const size = $btn.data('size') || 500;
            const filename = $btn.data('filename') || 'qrcode';
            const format = $btn.data('format') || 'png';

            if (!data) return;

            $btn.addClass('is-loading');

            if (typeof QRCode === 'undefined') {
                // Fallback to Google Charts API
                this.downloadFromGoogle(data, size, filename);
                $btn.removeClass('is-loading');
                return;
            }

            // Generate high-res QR code
            const canvas = document.createElement('canvas');

            QRCode.toCanvas(canvas, data, {
                width: size,
                margin: 2,
                color: {
                    dark: '#000000',
                    light: '#ffffff'
                }
            }, (error) => {
                if (error) {
                    console.error('QR generation failed:', error);
                    $btn.removeClass('is-loading');
                    return;
                }

                // Create download link
                const link = document.createElement('a');
                link.download = `${filename}.${format}`;
                link.href = canvas.toDataURL(`image/${format}`, 1.0);
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);

                $btn.removeClass('is-loading');
            });
        }

        /**
         * Download QR from Google Charts API.
         */
        downloadFromGoogle(data, size, filename) {
            const url = `https://chart.googleapis.com/chart?cht=qr&chs=${size}x${size}&chl=${encodeURIComponent(data)}&choe=UTF-8`;

            // Create temporary link
            const link = document.createElement('a');
            link.href = url;
            link.download = `${filename}.png`;
            link.target = '_blank';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }

        /**
         * Generate QR code and return canvas.
         */
        static generate(data, options = {}) {
            return new Promise((resolve, reject) => {
                if (typeof QRCode === 'undefined') {
                    reject(new Error('QRCode library not loaded'));
                    return;
                }

                const defaults = {
                    width: 200,
                    margin: 2,
                    color: {
                        dark: '#000000',
                        light: '#ffffff'
                    }
                };

                const config = { ...defaults, ...options };
                const canvas = document.createElement('canvas');

                QRCode.toCanvas(canvas, data, config, (error) => {
                    if (error) {
                        reject(error);
                    } else {
                        resolve(canvas);
                    }
                });
            });
        }
    }

    /**
     * QR Code Modal.
     */
    class ApolloQRModal {
        constructor() {
            this.$modal = null;
            this.init();
        }

        init() {
            $(document).on('click', '[data-qr-modal]', (e) => {
                e.preventDefault();
                const eventId = $(e.currentTarget).data('event-id');
                const qrData = $(e.currentTarget).data('qr-data');
                this.open(eventId, qrData);
            });
        }

        open(eventId, qrData) {
            if (!this.$modal) {
                this.createModal();
            }

            this.$modal.data('event-id', eventId);
            this.generateQR(qrData || window.location.href);
            this.$modal.addClass('is-open');
            $('body').css('overflow', 'hidden');
        }

        close() {
            this.$modal.removeClass('is-open');
            $('body').css('overflow', '');
        }

        createModal() {
            this.$modal = $(`
                <div class="apollo-qr-modal">
                    <div class="apollo-qr-modal__content">
                        <button type="button" class="apollo-qr-modal__close">
                            <i class="fas fa-times"></i>
                        </button>
                        <h3 class="apollo-qr-modal__title">QR Code do Evento</h3>
                        <div class="apollo-qr-modal__qr"></div>
                        <div class="apollo-qr-modal__actions">
                            <button type="button" class="apollo-qr-download-btn" data-size="500">
                                <i class="fas fa-download"></i>
                                <span>Baixar PNG</span>
                            </button>
                        </div>
                    </div>
                </div>
            `);

            $('body').append(this.$modal);
            this.bindEvents();
        }

        bindEvents() {
            this.$modal.find('.apollo-qr-modal__close').on('click', () => this.close());

            this.$modal.on('click', (e) => {
                if ($(e.target).hasClass('apollo-qr-modal')) {
                    this.close();
                }
            });

            $(document).on('keydown', (e) => {
                if (e.key === 'Escape' && this.$modal.hasClass('is-open')) {
                    this.close();
                }
            });
        }

        generateQR(data) {
            const $container = this.$modal.find('.apollo-qr-modal__qr');
            $container.empty();

            if (typeof QRCode === 'undefined') {
                // Fallback to Google Charts
                const url = `https://chart.googleapis.com/chart?cht=qr&chs=250x250&chl=${encodeURIComponent(data)}`;
                $container.html(`<img src="${url}" alt="QR Code" width="250" height="250">`);
                return;
            }

            const canvas = document.createElement('canvas');
            $container.append(canvas);

            QRCode.toCanvas(canvas, data, {
                width: 250,
                margin: 2
            });

            // Update download button
            this.$modal.find('.apollo-qr-download-btn')
                .data('qr-data', data)
                .data('filename', 'evento-qrcode');
        }
    }

    /**
     * QR Code Printer.
     */
    class ApolloQRPrinter {
        constructor() {
            this.init();
        }

        init() {
            $(document).on('click', '[data-qr-print]', (e) => {
                e.preventDefault();
                const $btn = $(e.currentTarget);
                const eventId = $btn.data('event-id');
                const qrData = $btn.data('qr-data') || window.location.href;
                this.print(eventId, qrData);
            });
        }

        print(eventId, qrData) {
            const title = document.title;
            const url = window.location.href;

            // Create print window
            const printWindow = window.open('', '_blank', 'width=600,height=800');

            printWindow.document.write(`
                <!DOCTYPE html>
                <html>
                <head>
                    <title>QR Code - ${title}</title>
                    <style>
                        body {
                            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                            display: flex;
                            flex-direction: column;
                            align-items: center;
                            justify-content: center;
                            min-height: 100vh;
                            margin: 0;
                            padding: 2rem;
                            box-sizing: border-box;
                        }
                        .qr-container {
                            text-align: center;
                            padding: 2rem;
                            border: 2px solid #000;
                            border-radius: 12px;
                        }
                        .qr-title {
                            margin: 0 0 1rem;
                            font-size: 1.25rem;
                        }
                        .qr-image {
                            margin: 1rem 0;
                        }
                        .qr-url {
                            font-size: 0.75rem;
                            color: #666;
                            word-break: break-all;
                        }
                        .qr-scan {
                            margin-top: 1rem;
                            font-size: 0.875rem;
                            color: #999;
                        }
                        @media print {
                            .qr-container {
                                border: 1px solid #000;
                            }
                        }
                    </style>
                </head>
                <body>
                    <div class="qr-container">
                        <h1 class="qr-title">${title}</h1>
                        <div class="qr-image">
                            <img src="https://chart.googleapis.com/chart?cht=qr&chs=300x300&chl=${encodeURIComponent(qrData)}"
                                 alt="QR Code"
                                 width="300"
                                 height="300">
                        </div>
                        <p class="qr-url">${url}</p>
                        <p class="qr-scan">Aponte a câmera do celular para acessar</p>
                    </div>
                    <script>
                        window.onload = function() {
                            window.print();
                            window.close();
                        };
                    </script>
                </body>
                </html>
            `);

            printWindow.document.close();
        }
    }

    /**
     * Initialize on DOM ready.
     */
    $(function() {
        new ApolloQRGenerator();
        new ApolloQRModal();
        new ApolloQRPrinter();
    });

    // Expose classes globally
    window.ApolloQRGenerator = ApolloQRGenerator;
    window.ApolloQRModal = ApolloQRModal;
    window.ApolloQRPrinter = ApolloQRPrinter;

})(jQuery);
