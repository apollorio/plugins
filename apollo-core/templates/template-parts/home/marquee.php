<?php
/**
 * Apollo Home Marquee Section
 *
 * Scrolling text marquee with reveal animation
 *
 * @package Apollo_Core
 * @since 3.0.0
 */

defined( 'ABSPATH' ) || exit;

$args = $args ?? array();

// Default marquee words.
$words = $args['words'] ?? array(
	__( 'Cultura', 'apollo-core' ),
	__( 'Memória', 'apollo-core' ),
	__( 'Inteligência', 'apollo-core' ),
	__( 'Conexão', 'apollo-core' ),
);

// Duplicate for seamless loop.
$all_words = array_merge( $words, $words, $words );
?>


<div class="marquee-wrapper">
	<div class="marquee-content">
		<?php foreach ( $all_words as $word ) : ?>
			<span class="marquee-text"><?php echo esc_html( $word ); ?></span>
		<?php endforeach; ?>
	</div>
</div>
