<!DOCTYPE html>
<html <?php language_attributes(); ?>>

<head>
	<meta charset="<?php bloginfo('charset'); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="generator" content="Apollo Social Canvas Mode">
	<?php wp_head(); ?>
</head>

<body <?php body_class('apollo-canvas'); ?>>
	<div id="apollo-canvas-wrapper">
		<main id="apollo-main">
			<?php
            // Template content loaded by Canvas Mode router
            do_action('apollo_canvas_before_content');

// Main content area
if (function_exists('apollo_canvas_content')) {
    call_user_func('apollo_canvas_content');
}

do_action('apollo_canvas_after_content');
?>
		</main>
	</div>
	<?php wp_footer(); ?>
</body>

</html>