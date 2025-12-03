<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title><?php echo esc_html( $title ); ?></title>
	<?php wp_head(); ?>
</head>
<body class="apollo-canvas apollo-social-canvas">
	<div id="apollo-canvas-wrapper">
		<header class="apollo-header">
			<nav class="apollo-nav">
				<div class="apollo-brand">
					<a href="/">Apollo Social</a>
				</div>
				<ul class="apollo-menu">
					<li><a href="/comunidade/">Comunidade</a></li>
					<li><a href="/nucleo/">Núcleo</a></li>
					<li><a href="/season/">Season</a></li>
					<li><a href="/membership/">Membership</a></li>
					<li><a href="/uniao/">União</a></li>
					<li><a href="/anuncio/">Anúncios</a></li>
				</ul>
			</nav>
		</header>

		<main id="apollo-main" class="apollo-content">
			<?php if ( isset( $breadcrumbs ) && ! empty( $breadcrumbs ) ) : ?>
			<nav class="apollo-breadcrumbs">
				<?php foreach ( $breadcrumbs as $index => $crumb ) : ?>
					<?php
					if ( $index > 0 ) :
						?>
						&gt; <?php endif; ?>
					<span><?php echo esc_html( $crumb ); ?></span>
				<?php endforeach; ?>
			</nav>
			<?php endif; ?>

			<div class="apollo-page-content">
				<?php echo $content; ?>
			</div>
		</main>

		<footer class="apollo-footer">
			<p>&copy; <?php echo date( 'Y' ); ?> Apollo Social. Todos os direitos reservados.</p>
		</footer>
	</div>
	<?php wp_footer(); ?>
</body>
</html>
