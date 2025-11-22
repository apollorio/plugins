<!-- /** 
* FILE apollo-events-manager/templates/event-listings-end.php
*
**/ -->
    
    </div><!-- .event_listings -->
    
    <!-- Banner Section (Latest Blog Post) -->
    <?php
    $latest_post = get_posts([
        'post_type' => 'post',
        'posts_per_page' => 1,
        'post_status' => 'publish'
    ]);
    
    if ($latest_post):
        $post = $latest_post[0];
        $banner_img = get_the_post_thumbnail_url($post->ID, 'full');
        if (!$banner_img) {
            $banner_img = 'https://images.unsplash.com/photo-1506157786151-b8491531f063?q=80&w=2070&auto=format&fit=crop';
        }
    ?>
    <section class="banner-ario-1-wrapper" style="margin-top:80px">
        <img src="<?php echo esc_url($banner_img); ?>" 
             alt="<?php echo esc_attr($post->post_title); ?>" 
             class="ban-ario-1-img">
        <div class="ban-ario-1-content">
            <h3 class="ban-ario-1-subtit">Extra! Extra!</h3>
            <h2 class="ban-ario-1-titl"><?php echo esc_html($post->post_title); ?></h2>
            <p class="ban-ario-1-txt">
                <?php echo esc_html(wp_trim_words($post->post_content, 40, '...')); ?>
            </p>
            <a class="ban-ario-1-btn" href="<?php echo get_permalink($post->ID); ?>">
                Saiba Mais <i class="ri-arrow-right-long-line"></i>
            </a>
        </div>
    </section>
    <?php endif; ?>
    
</div><!-- .discover-events-now-shortcode -->

<!-- Dark Mode Toggle -->
<div class="dark-mode-toggle" id="darkModeToggle" aria-label="Toggle dark mode" role="button">
    <i class="ri-sun-line"></i>
    <i class="ri-moon-line"></i>
</div>