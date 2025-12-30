/**
 * Apollo Photos JavaScript
 * Lightbox, slider, upload handling
 *
 * @package Apollo_Events_Manager
 * @since 2.0.0
 */

(function($) {
    'use strict';

    /**
     * Photo Lightbox
     */
    class ApolloLightbox {
        constructor() {
            this.$lightbox = null;
            this.images = [];
            this.currentIndex = 0;
            this.isOpen = false;

            this.init();
        }

        init() {
            this.createLightbox();
            this.bindEvents();
        }

        createLightbox() {
            if ($('.apollo-lightbox').length) return;

            const html = `
                <div class="apollo-lightbox">
                    <div class="apollo-lightbox__content">
                        <button type="button" class="apollo-lightbox__close" aria-label="${apolloPhotos.i18n?.close || 'Fechar'}">
                            <i class="fas fa-times"></i>
                        </button>
                        <img class="apollo-lightbox__image" src="" alt="">
                        <button type="button" class="apollo-lightbox__nav apollo-lightbox__nav--prev" aria-label="${apolloPhotos.i18n?.prev || 'Anterior'}">
                            <i class="fas fa-chevron-left"></i>
                        </button>
                        <button type="button" class="apollo-lightbox__nav apollo-lightbox__nav--next" aria-label="${apolloPhotos.i18n?.next || 'Próximo'}">
                            <i class="fas fa-chevron-right"></i>
                        </button>
                        <div class="apollo-lightbox__counter"></div>
                    </div>
                </div>
            `;

            $('body').append(html);
            this.$lightbox = $('.apollo-lightbox');
        }

        bindEvents() {
            // Open lightbox
            $(document).on('click', '.apollo-photo-gallery__link, .apollo-photo-masonry__link, .apollo-photo-grid__link', (e) => {
                e.preventDefault();
                const $link = $(e.currentTarget);
                const $gallery = $link.closest('.apollo-photo-gallery, .apollo-photo-masonry, .apollo-photo-grid');

                if ($gallery.data('lightbox') === false) return;

                this.images = [];
                $gallery.find('a[data-index]').each((i, el) => {
                    this.images.push({
                        src: $(el).attr('href'),
                        title: $(el).data('title') || ''
                    });
                });

                this.currentIndex = parseInt($link.data('index')) || 0;
                this.open();
            });

            // Close lightbox
            this.$lightbox.on('click', '.apollo-lightbox__close', () => this.close());
            this.$lightbox.on('click', (e) => {
                if ($(e.target).is('.apollo-lightbox')) {
                    this.close();
                }
            });

            // Navigation
            this.$lightbox.on('click', '.apollo-lightbox__nav--prev', () => this.prev());
            this.$lightbox.on('click', '.apollo-lightbox__nav--next', () => this.next());

            // Keyboard navigation
            $(document).on('keydown', (e) => {
                if (!this.isOpen) return;

                switch (e.key) {
                    case 'Escape':
                        this.close();
                        break;
                    case 'ArrowLeft':
                        this.prev();
                        break;
                    case 'ArrowRight':
                        this.next();
                        break;
                }
            });

            // Touch/swipe support
            let touchStartX = 0;
            this.$lightbox.on('touchstart', (e) => {
                touchStartX = e.originalEvent.touches[0].clientX;
            });

            this.$lightbox.on('touchend', (e) => {
                const touchEndX = e.originalEvent.changedTouches[0].clientX;
                const diff = touchStartX - touchEndX;

                if (Math.abs(diff) > 50) {
                    if (diff > 0) {
                        this.next();
                    } else {
                        this.prev();
                    }
                }
            });
        }

        open() {
            this.isOpen = true;
            this.showImage();
            this.$lightbox.addClass('is-active');
            $('body').css('overflow', 'hidden');
        }

        close() {
            this.isOpen = false;
            this.$lightbox.removeClass('is-active');
            $('body').css('overflow', '');
        }

        showImage() {
            if (!this.images[this.currentIndex]) return;

            const image = this.images[this.currentIndex];
            const $img = this.$lightbox.find('.apollo-lightbox__image');

            $img.attr('src', image.src).attr('alt', image.title);

            this.$lightbox.find('.apollo-lightbox__counter').text(
                `${this.currentIndex + 1} / ${this.images.length}`
            );

            // Show/hide navigation
            const showNav = this.images.length > 1;
            this.$lightbox.find('.apollo-lightbox__nav').toggle(showNav);
        }

        prev() {
            this.currentIndex = this.currentIndex > 0 ? this.currentIndex - 1 : this.images.length - 1;
            this.showImage();
        }

        next() {
            this.currentIndex = this.currentIndex < this.images.length - 1 ? this.currentIndex + 1 : 0;
            this.showImage();
        }
    }

    /**
     * Photo Slider
     */
    class ApolloPhotoSlider {
        constructor(element) {
            this.$slider = $(element);
            this.$track = this.$slider.find('.apollo-photo-slider__track');
            this.$slides = this.$slider.find('.apollo-photo-slider__slide');
            this.$dots = this.$slider.find('.apollo-photo-slider__dot');
            this.$prevBtn = this.$slider.find('.apollo-photo-slider__nav--prev');
            this.$nextBtn = this.$slider.find('.apollo-photo-slider__nav--next');

            this.currentSlide = 0;
            this.slideCount = this.$slides.length;
            this.autoplay = this.$slider.data('autoplay') === 'true';
            this.autoplayInterval = null;

            this.init();
        }

        init() {
            if (this.slideCount <= 1) return;

            this.bindEvents();

            if (this.autoplay) {
                this.startAutoplay();
            }
        }

        bindEvents() {
            this.$prevBtn.on('click', () => this.prev());
            this.$nextBtn.on('click', () => this.next());
            this.$dots.on('click', (e) => this.goTo($(e.currentTarget).data('index')));

            this.$slider.on('mouseenter', () => this.stopAutoplay());
            this.$slider.on('mouseleave', () => {
                if (this.autoplay) this.startAutoplay();
            });

            // Touch support
            let touchStartX = 0;
            this.$slider.on('touchstart', (e) => {
                touchStartX = e.originalEvent.touches[0].clientX;
            });

            this.$slider.on('touchend', (e) => {
                const touchEndX = e.originalEvent.changedTouches[0].clientX;
                const diff = touchStartX - touchEndX;

                if (Math.abs(diff) > 50) {
                    if (diff > 0) {
                        this.next();
                    } else {
                        this.prev();
                    }
                }
            });
        }

        goTo(index) {
            if (index < 0) index = this.slideCount - 1;
            if (index >= this.slideCount) index = 0;

            this.currentSlide = index;
            this.$track.css('transform', `translateX(-${index * 100}%)`);

            this.$dots.removeClass('is-active');
            this.$dots.eq(index).addClass('is-active');
        }

        prev() {
            this.goTo(this.currentSlide - 1);
        }

        next() {
            this.goTo(this.currentSlide + 1);
        }

        startAutoplay() {
            this.stopAutoplay();
            this.autoplayInterval = setInterval(() => this.next(), 5000);
        }

        stopAutoplay() {
            if (this.autoplayInterval) {
                clearInterval(this.autoplayInterval);
                this.autoplayInterval = null;
            }
        }
    }

    /**
     * Photo Upload Handler
     */
    class ApolloPhotoUpload {
        constructor(element) {
            this.$container = $(element);
            this.$input = this.$container.find('.apollo-photo-upload__input');
            this.$dropzone = this.$container.find('.apollo-photo-upload__dropzone');
            this.$preview = this.$container.find('.apollo-photo-upload__preview');
            this.$progress = this.$container.find('.apollo-photo-upload__progress');
            this.$submit = this.$container.find('.apollo-photo-upload__submit');

            this.eventId = this.$container.data('event-id');
            this.maxFiles = this.$container.data('max-files') || 5;
            this.files = [];

            this.init();
        }

        init() {
            this.bindEvents();
        }

        bindEvents() {
            // File input change
            this.$input.on('change', (e) => {
                this.handleFiles(e.target.files);
            });

            // Drag and drop
            this.$dropzone.on('dragover dragenter', (e) => {
                e.preventDefault();
                this.$dropzone.addClass('is-dragover');
            });

            this.$dropzone.on('dragleave dragend drop', (e) => {
                e.preventDefault();
                this.$dropzone.removeClass('is-dragover');
            });

            this.$dropzone.on('drop', (e) => {
                e.preventDefault();
                const files = e.originalEvent.dataTransfer.files;
                this.handleFiles(files);
            });

            // Submit
            this.$submit.on('click', () => this.uploadFiles());

            // Remove preview
            this.$preview.on('click', '.apollo-photo-upload__preview-remove', (e) => {
                const index = $(e.currentTarget).parent().index();
                this.removeFile(index);
            });
        }

        handleFiles(fileList) {
            const validFiles = [];
            const allowedTypes = apolloPhotos.allowedTypes || ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
            const maxSize = apolloPhotos.maxFileSize || 5 * 1024 * 1024;

            Array.from(fileList).forEach(file => {
                if (!allowedTypes.includes(file.type)) {
                    this.showError(apolloPhotos.i18n?.invalidType || 'Tipo de arquivo não permitido.');
                    return;
                }

                if (file.size > maxSize) {
                    this.showError(apolloPhotos.i18n?.fileTooLarge || 'Arquivo muito grande.');
                    return;
                }

                if (this.files.length >= this.maxFiles) {
                    return;
                }

                validFiles.push(file);
            });

            this.files = [...this.files, ...validFiles].slice(0, this.maxFiles);
            this.updatePreview();
        }

        updatePreview() {
            this.$preview.empty();

            this.files.forEach((file, index) => {
                const reader = new FileReader();
                reader.onload = (e) => {
                    const html = `
                        <div class="apollo-photo-upload__preview-item">
                            <img src="${e.target.result}" alt="">
                            <button type="button" class="apollo-photo-upload__preview-remove">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    `;
                    this.$preview.append(html);
                };
                reader.readAsDataURL(file);
            });

            this.$submit.toggle(this.files.length > 0);
        }

        removeFile(index) {
            this.files.splice(index, 1);
            this.updatePreview();
        }

        async uploadFiles() {
            if (this.files.length === 0) return;

            this.$submit.prop('disabled', true);
            this.$progress.show();

            const totalFiles = this.files.length;
            let uploadedFiles = 0;

            for (const file of this.files) {
                const formData = new FormData();
                formData.append('action', 'apollo_upload_event_photo');
                formData.append('nonce', apolloPhotos.nonce);
                formData.append('event_id', this.eventId);
                formData.append('photo', file);

                try {
                    await $.ajax({
                        url: apolloPhotos.ajaxUrl,
                        type: 'POST',
                        data: formData,
                        processData: false,
                        contentType: false,
                        xhr: () => {
                            const xhr = new XMLHttpRequest();
                            xhr.upload.addEventListener('progress', (e) => {
                                if (e.lengthComputable) {
                                    const percent = (e.loaded / e.total) * 100;
                                    this.updateProgress(percent, uploadedFiles, totalFiles);
                                }
                            });
                            return xhr;
                        }
                    });

                    uploadedFiles++;
                    this.updateProgress(100, uploadedFiles, totalFiles);
                } catch (error) {
                    console.error('Upload error:', error);
                    this.showError(apolloPhotos.i18n?.uploadError || 'Erro ao enviar foto.');
                }
            }

            if (uploadedFiles === totalFiles) {
                this.showSuccess(apolloPhotos.i18n?.uploadSuccess || 'Fotos enviadas com sucesso!');
                this.files = [];
                this.updatePreview();

                // Reload community photos if present
                setTimeout(() => {
                    window.location.reload();
                }, 1500);
            }

            this.$submit.prop('disabled', false);
            setTimeout(() => {
                this.$progress.hide();
            }, 2000);
        }

        updateProgress(filePercent, completed, total) {
            const totalPercent = ((completed - 1) / total * 100) + (filePercent / total);
            this.$progress.find('.apollo-photo-upload__progress-fill').css('width', `${totalPercent}%`);
            this.$progress.find('.apollo-photo-upload__progress-text').text(
                `${apolloPhotos.i18n?.uploading || 'Enviando...'} ${completed}/${total}`
            );
        }

        showError(message) {
            if (typeof window.apolloToast === 'function') {
                window.apolloToast.error(message);
            } else {
                alert(message);
            }
        }

        showSuccess(message) {
            if (typeof window.apolloToast === 'function') {
                window.apolloToast.success(message);
            }
        }
    }

    /**
     * Initialize on document ready
     */
    $(document).ready(function() {
        // Initialize lightbox
        if ($('.apollo-photo-gallery, .apollo-photo-masonry, .apollo-photo-grid').length) {
            new ApolloLightbox();
        }

        // Initialize sliders
        $('.apollo-photo-slider').each(function() {
            new ApolloPhotoSlider(this);
        });

        // Initialize upload handlers
        $('.apollo-photo-upload').each(function() {
            new ApolloPhotoUpload(this);
        });

        // Upload trigger button
        $(document).on('click', '.apollo-photo-upload-trigger', function() {
            const eventId = $(this).data('event-id');
            const $upload = $(`.apollo-photo-upload[data-event-id="${eventId}"]`);
            if ($upload.length) {
                $upload.find('.apollo-photo-upload__input').click();
            }
        });
    });

    // Make classes available globally
    window.ApolloLightbox = ApolloLightbox;
    window.ApolloPhotoSlider = ApolloPhotoSlider;
    window.ApolloPhotoUpload = ApolloPhotoUpload;

})(jQuery);
