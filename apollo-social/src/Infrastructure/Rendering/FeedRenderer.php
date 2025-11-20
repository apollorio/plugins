<?php
namespace Apollo\Infrastructure\Rendering;

/**
 * Feed Renderer
 * Renders the main feed page
 */
class FeedRenderer
{
    public function render($template_data)
    {
        // Get current user
        $current_user = wp_get_current_user();
        
        // Get feed posts (customize query as needed)
        $feed_posts = $this->getFeedPosts();
        
        return [
            'title' => 'Feed',
            'content' => '', // Will be rendered by template
            'breadcrumbs' => ['Apollo Social', 'Feed'],
            'data' => [
                'posts' => $feed_posts,
                'current_user' => [
                    'id' => $current_user->ID,
                    'name' => $current_user->display_name,
                    'avatar' => get_avatar_url($current_user->ID),
                ],
            ],
        ];
    }

    private function getFeedPosts()
    {
        $args = [
            'post_type' => 'post',
            'posts_per_page' => 20,
            'post_status' => 'publish',
            'orderby' => 'date',
            'order' => 'DESC',
        ];

        $query = new \WP_Query($args);
        $posts = [];

        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $posts[] = [
                    'id' => get_the_ID(),
                    'title' => get_the_title(),
                    'content' => get_the_content(),
                    'excerpt' => get_the_excerpt(),
                    'author' => [
                        'id' => get_the_author_meta('ID'),
                        'name' => get_the_author(),
                        'avatar' => get_avatar_url(get_the_author_meta('ID')),
                    ],
                    'date' => get_the_date('c'),
                    'permalink' => get_permalink(),
                    'thumbnail' => get_the_post_thumbnail_url(get_the_ID(), 'medium'),
                ];
            }
            wp_reset_postdata();
        }

        return $posts;
    }
}

