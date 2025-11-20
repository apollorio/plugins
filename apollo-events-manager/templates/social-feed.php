<?php
/**
 * Social Feed Template
 * TODO 106-109: Apollo Social Feed with App Store style cards
 * 
 * @package Apollo_Events_Manager
 * @version 0.1.0
 */

defined('ABSPATH') || exit;

// TODO 106: App Store style cards
// TODO 107: Swipe actions
// TODO 108: Stagger animations
// TODO 109: ShadCN layout

?>
<div class="apollo-social-feed" data-shadcn-enhanced="true" data-motion-feed="true">
    <div class="feed-container">
        <?php
        // TODO: Query social posts
        $posts = array(); // Placeholder
        
        foreach ($posts as $index => $post) :
            ?>
            <div class="social-card" 
                 data-motion-card="true" 
                 data-motion-index="<?php echo esc_attr($index); ?>"
                 data-swipe-action="true">
                <!-- TODO 106: App Store style card content -->
                <div class="card-preview">
                    <img src="<?php echo esc_url($post['image']); ?>" alt="<?php echo esc_attr($post['title']); ?>">
                </div>
                <div class="card-content">
                    <h3><?php echo esc_html($post['title']); ?></h3>
                    <p><?php echo esc_html($post['excerpt']); ?></p>
                </div>
                <!-- TODO 107: Swipe actions -->
                <div class="swipe-actions">
                    <button class="swipe-like" data-action="like">
                        <i class="ri-heart-line"></i>
                    </button>
                    <button class="swipe-share" data-action="share">
                        <i class="ri-share-line"></i>
                    </button>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

