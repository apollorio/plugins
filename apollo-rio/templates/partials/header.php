<?php
/**
 * ============================================
 * FILE: templates/partials/header.php
 * FULL HEADER with navigation
 * Used by: pagx_site, pagx_app
 * ============================================
 */

if (!defined('ABSPATH')) exit;
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?> class="apollo-html">
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover,maximum-scale=1,user-scalable=no">

    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="theme-color" content="#FFFFFF">
    
    <!-- PWA Manifest - served from apollo-social plugin -->
    <?php wp_head(); ?>
</head>

<body <?php body_class('apollo-body'); ?>>
<?php wp_body_open(); ?>

<div id="apollo-wrapper" class="apollo-site-wrapper">
    
    <!-- Full Apollo Header -->
    <header id="apollo-header" class="apollo-header apollo-header-full">
        <div class="apollo-header-container">
            
            <!-- Logo -->
            <div class="apollo-branding">
                <?php if (has_custom_logo()) : ?>
                    <?php the_custom_logo(); ?>
                <?php else : ?>
                    <a href="<?php echo esc_url(home_url('/')); ?>" class="apollo-site-title">
                        Apollo::Rio
                    </a>
                <?php endif; ?>
            </div>
            
            <!-- Main Navigation -->
            <nav id="apollo-navigation" class="apollo-navigation">
                <?php
                if (has_nav_menu('apollo_primary')) {
                    wp_nav_menu([
                        'theme_location' => 'apollo_primary',
                        'menu_id' => 'apollo-primary-menu',
                        'menu_class' => 'apollo-menu',
                        'container' => false,
                    ]);
                }
                ?>
            </nav>
            
            <!-- User Actions -->
            <div class="apollo-header-actions">
                <?php if (is_user_logged_in()) : ?>
                    <span class="apollo-username">
                        <?php echo esc_html(wp_get_current_user()->display_name); ?>
                    </span>
                    <?php
                    echo get_avatar(get_current_user_id(), 32, '', '', ['class' => 'apollo-avatar']);
                    ?>
                    <a href="<?php echo esc_url(wp_logout_url()); ?>" class="apollo-logout-btn">
                        <?php _e('Logout', 'apollo-rio'); ?>
                    </a>
                <?php else : ?>
                    <a href="<?php echo esc_url(wp_login_url()); ?>" class="apollo-login-btn">
                        <?php _e('Login', 'apollo-rio'); ?>
                    </a>
                    <a href="<?php echo esc_url(wp_registration_url()); ?>" class="apollo-register-btn">
                        <?php _e('Register', 'apollo-rio'); ?>
                    </a>
                <?php endif; ?>
            </div>
            
            <!-- Mobile Toggle -->
            <button id="apollo-mobile-toggle" class="apollo-mobile-toggle" aria-label="Menu">
                <span></span>
                <span></span>
                <span></span>
            </button>
            
        </div>
    </header>
    
    <!-- Main Content Start -->
    <main id="apollo-content" class="apollo-main-content">