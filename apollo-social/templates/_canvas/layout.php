<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <?php
    /**
     * Canvas Mode layout (stub)
     * 
     * Main layout template that replaces theme's get_header/get_footer.
     * TODO: Implement complete Canvas Mode layout with Apollo-specific structure.
     */
    
    // TODO: Load head.php partial
    // TODO: Enqueue Canvas Mode assets
    // TODO: Apply Canvas Mode configuration
    ?>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php wp_head(); ?>
</head>
<body <?php body_class('apollo-canvas'); ?>>
    <div id="apollo-canvas-wrapper">
        <?php
        // TODO: Load notices partial
        // TODO: Load toolbar-dev partial if in dev mode
        ?>
        
        <main id="apollo-main">
            <?php
            // TODO: Load template content here
            // This is where individual page templates will be included
            ?>
        </main>
        
        <?php
        // TODO: Load foot.php partial
        ?>
    </div>
    <?php wp_footer(); ?>
</body>
</html>