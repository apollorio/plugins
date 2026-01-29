/**
 * FASE 2: Feed JavaScript
 * Handles likes, comments, sharing, and infinite scroll
 *
 * Dependencies: jQuery (optional graceful degradation)
 * Global objects: window.apolloFeedData (optional)
 */
(function($) {
    'use strict';

    // Defensive check: ensure jQuery is available
    if (typeof $ === 'undefined') {
        console.warn('Apollo Feed: jQuery not available, skipping initialization');
        return;
    }

    const FeedManager = {
        restUrl: window.apolloFeedData?.restUrl || '/wp-json/apollo/v1',
        ajaxUrl: window.apolloFeedData?.ajaxUrl || '/wp-admin/admin-ajax.php',
        nonce: window.apolloFeedData?.nonce || '',
        currentPage: 1,
        loading: false,

        init: function() {
            this.bindLikeButtons();
            this.bindCommentButtons();
            this.bindShareButtons();
            this.bindFavoriteButtons();
            this.bindLoadMore();
            this.bindPublishPost();
        },

        /**
         * Handle like button clicks
         */
        bindLikeButtons: function() {
            $(document).on('click', '.apollo-feed-like-btn', function(e) {
                e.preventDefault();
                const $btn = $(this);
                const contentType = $btn.data('content-type');
                const contentId = $btn.data('content-id');
                const $icon = $btn.find('i');
                const $count = $btn.find('.apollo-like-count');

                if (!contentType || !contentId) return;

                // Disable button during request
                $btn.prop('disabled', true);

                $.ajax({
                    url: FeedManager.restUrl + '/wow',
                    method: 'POST',
                    data: {
                        content_type: contentType,
                        content_id: contentId,
                    },
                    beforeSend: function(xhr) {
                        xhr.setRequestHeader('X-WP-Nonce', FeedManager.nonce);
                    },
                    success: function(response) {
                        if (response.success) {
                            const liked = response.liked;
                            const count = response.like_count || 0;

                            // Update icon
                            if (liked) {
                                $icon.removeClass('ri-heart-3-line').addClass('ri-heart-3-fill');
                                $btn.addClass('text-orange-600');
                            } else {
                                $icon.removeClass('ri-heart-3-fill').addClass('ri-heart-3-line');
                                $btn.removeClass('text-orange-600');
                            }

                            // Update count
                            $count.text(count);

                            // Animation
                            if (window.Motion && window.Motion.animate) {
                                window.Motion.animate($icon[0],
                                    { scale: [1, 1.4, 1], rotate: [0, -20, 20, 0] },
                                    { duration: 0.6, easing: 'ease-in-out' }
                                );
                            }
                        }
                    },
                    error: function() {
                        alert('Erro ao processar like. Tente novamente.');
                    },
                    complete: function() {
                        $btn.prop('disabled', false);
                    }
                });
            });
        },

        /**
         * Handle comment button clicks
         */
        bindCommentButtons: function() {
            $(document).on('click', '.apollo-feed-comment-btn', function(e) {
                e.preventDefault();
                const $btn = $(this);
                const $card = $btn.closest('.apollo-feed-card');
                const $section = $card.find('.apollo-comments-section');

                $section.toggleClass('hidden');

                // Load comments if not loaded
                if (!$section.hasClass('loaded')) {
                    FeedManager.loadComments($card);
                }
            });

            // Submit comment form
            $(document).on('submit', '.apollo-comment-form', function(e) {
                e.preventDefault();
                const $form = $(this);
                const $card = $form.closest('.apollo-feed-card');
                const postId = $form.find('input[name="post_id"]').val();
                const comment = $form.find('input[name="comment"]').val();

                if (!comment.trim()) return;

                $.ajax({
                    url: FeedManager.ajaxUrl,
                    method: 'POST',
                    data: {
                        action: 'apollo_submit_comment',
                        post_id: postId,
                        comment: comment,
                        nonce: $form.find('input[name="apollo_comment_nonce"]').val(),
                    },
                    success: function(response) {
                        if (response.success) {
                            // Add comment to list
                            $card.find('.apollo-comments-list').append(response.data.html);

                            // Update count
                            $card.find('.apollo-comment-count').text(response.data.comment_count);

                            // Clear form
                            $form.find('input[name="comment"]').val('');
                        } else {
                            alert(response.data?.message || 'Erro ao enviar comentário.');
                        }
                    },
                    error: function() {
                        alert('Erro ao enviar comentário. Tente novamente.');
                    }
                });
            });
        },

        /**
         * Load comments for a post
         */
        loadComments: function($card) {
            const postId = $card.data('content-id');
            const contentType = $card.data('content-type');

            if (contentType !== 'apollo_social_post') return;

            // TODO: Implementar carregamento de comentários via REST API
            $card.find('.apollo-comments-section').addClass('loaded');
        },

        /**
         * Handle share button clicks
         */
        bindShareButtons: function() {
            $(document).on('click', '.apollo-feed-share-btn', function(e) {
                e.preventDefault();
                const $btn = $(this);
                const permalink = $btn.data('permalink');
                const title = $btn.data('title') || '';

                if (!permalink) return;

                // Show share dropdown
                FeedManager.showShareDropdown($btn, permalink, title);
            });
        },

        /**
         * Show share dropdown
         */
        showShareDropdown: function($btn, permalink, title) {
            const encodedUrl = encodeURIComponent(permalink);
            const encodedTitle = encodeURIComponent(title);
            const encodedText = encodeURIComponent(title + ' - Apollo::Rio');

            const shareOptions = `
                <div class="apollo-share-dropdown absolute bg-white rounded-lg shadow-lg border border-slate-200 p-2 z-50" style="min-width: 200px;">
                    <a href="https://www.facebook.com/sharer/sharer.php?u=${encodedUrl}"
                       target="_blank"
                       class="flex items-center gap-2 px-3 py-2 hover:bg-slate-100 rounded text-sm">
                        <i class="ri-facebook-fill text-blue-600"></i> Facebook
                    </a>
                    <a href="https://twitter.com/intent/tweet?url=${encodedUrl}&text=${encodedText}"
                       target="_blank"
                       class="flex items-center gap-2 px-3 py-2 hover:bg-slate-100 rounded text-sm">
                        <i class="ri-twitter-x-fill"></i> Twitter/X
                    </a>
                    <a href="https://wa.me/?text=${encodedText}%20${encodedUrl}"
                       target="_blank"
                       class="flex items-center gap-2 px-3 py-2 hover:bg-slate-100 rounded text-sm">
                        <i class="ri-whatsapp-fill text-green-600"></i> WhatsApp
                    </a>
                    <button onclick="navigator.clipboard.writeText('${permalink}'); alert('Link copiado!');"
                            class="w-full flex items-center gap-2 px-3 py-2 hover:bg-slate-100 rounded text-sm text-left">
                        <i class="ri-file-copy-line"></i> Copiar link
                    </button>
                </div>
            `;

            // Remove existing dropdown
            $('.apollo-share-dropdown').remove();

            // Add dropdown
            $btn.css('position', 'relative').append(shareOptions);

            // Close on click outside
            $(document).one('click', function() {
                $('.apollo-share-dropdown').remove();
            });
        },

        /**
         * P0-6: Handle favorite button clicks (unified REST API)
         */
        bindFavoriteButtons: function() {
            $(document).on('click', '.apollo-feed-favorite-btn', function(e) {
                e.preventDefault();
                const $btn = $(this);
                const eventId = $btn.data('event-id');
                const contentType = $btn.data('content-type') || 'event_listing';

                if (!eventId) return;

                // Disable button during request
                $btn.prop('disabled', true);

                $.ajax({
                    url: FeedManager.restUrl + '/favorites',
                    method: 'POST',
                    data: {
                        content_id: eventId,
                        content_type: contentType,
                    },
                    beforeSend: function(xhr) {
                        xhr.setRequestHeader('X-WP-Nonce', FeedManager.nonce);
                    },
                    success: function(response) {
                        if (response.success) {
                            const favorited = response.favorited;
                            const $icon = $btn.find('i');

                            // Update icon
                            if (favorited) {
                                $icon.removeClass('ri-star-line').addClass('ri-star-fill');
                                $btn.addClass('text-yellow-600');
                                $btn.data('favorited', 'true');
                            } else {
                                $icon.removeClass('ri-star-fill').addClass('ri-star-line');
                                $btn.removeClass('text-yellow-600');
                                $btn.data('favorited', 'false');
                            }

                            // Animation
                            if (window.Motion && window.Motion.animate) {
                                window.Motion.animate($icon[0],
                                    { scale: [1, 1.3, 1], rotate: [0, -15, 15, 0] },
                                    { duration: 0.5, easing: 'ease-in-out' }
                                );
                            }
                        }
                    },
                    error: function() {
                        alert('Erro ao salvar favorito. Tente novamente.');
                    },
                    complete: function() {
                        $btn.prop('disabled', false);
                    }
                });
            });
        },

        /**
         * P0-5: Handle load more button (REST API)
         */
        bindLoadMore: function() {
            $('#apollo-feed-load-more').on('click', function() {
                if (FeedManager.loading) return;

                FeedManager.loading = true;
                FeedManager.currentPage++;

                const $btn = $(this);
                $btn.prop('disabled', true).text('Carregando...');

                // Load more posts via REST API
                $.ajax({
                    url: FeedManager.restUrl + '/feed',
                    method: 'GET',
                    data: {
                        page: FeedManager.currentPage,
                        per_page: 20,
                    },
                    beforeSend: function(xhr) {
                        xhr.setRequestHeader('X-WP-Nonce', FeedManager.nonce);
                    },
                    success: function(response) {
                        if (response.success && response.data.posts.length > 0) {
                            // Append new posts to feed
                            FeedManager.appendPosts(response.data.posts);
                            $btn.prop('disabled', false).text('Carregar mais');
                        } else {
                            // No more posts
                            $btn.text('Não há mais posts').prop('disabled', true);
                        }
                    },
                    error: function() {
                        alert('Erro ao carregar mais posts.');
                        $btn.prop('disabled', false).text('Carregar mais');
                    },
                    complete: function() {
                        FeedManager.loading = false;
                    }
                });
            });
        },

        /**
         * P0-5: Append posts to feed
         */
        appendPosts: function(posts) {
            const $feedContainer = $('[data-tab-panel="feed-all"]');

            posts.forEach(function(post) {
                const postHtml = FeedManager.renderPost(post);
                $feedContainer.append(postHtml);
            });

            // Re-bind event handlers for new posts
            FeedManager.bindLikeButtons();
            FeedManager.bindCommentButtons();
            FeedManager.bindShareButtons();
            FeedManager.bindFavoriteButtons();
        },

        /**
         * P0-5: Render single post HTML
         */
        renderPost: function(post) {
            // This would generate HTML for a post
            // For now, return placeholder
            return '<div class="apollo-feed-card">Post HTML here</div>';
        },

        /**
         * Handle publish post
         */
        bindPublishPost: function() {
            $('.apollo-feed-publish-btn').on('click', function(e) {
                e.preventDefault();
                const $btn = $(this);
                const $input = $('#apollo-feed-composer-input');
                const content = $input.val().trim();

                if (!content) {
                    alert('Digite algo para publicar!');
                    return;
                }

                // TODO: Implementar publicação via AJAX
                alert('Publicação em desenvolvimento!');
            });
        }
    };

    // Initialize on DOM ready
    $(document).ready(function() {
        FeedManager.init();
    });

})(jQuery);

