<?php
/**
 * Template Part: Infra Section
 * Location: template-parts/home/infra.php
 * * Description: "Infraestrutura Cultural Digital" hero block with video.
 * Compatibility: Elementor-safe (styles are scoped) and Gutenberg-friendly structure.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}
?>

<link href="https://fonts.cdnfonts.com/css/alimony" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Anton&display=swap" rel="stylesheet">


<section class="hero container apollo-infra-scope" id="infra-section" style="margin-top:150px; background:#fff">
	<div class="hero-grid">
		<div>
			<div class="reveal-up">
				<span class="hero-badge">Infraestrutura Cultural Digital</span>
			</div>
			<h1 class="reveal-up delay-100">
				Patrimônio Imaterial.<br>
				<span class="hero-text-gray">Futuro Digital.</span>
			</h1>
		</div>

		<div class="reveal-up delay-200">
			<p class="hero-description">
				Estruturando a cadeia produtiva da cultura carioca através de tecnologia modular e memória viva.
			</p>
		</div>
	</div>

	<div class="hero-video reveal-up delay-300" style="margin-top: 3rem;">
		<iframe src="https://plano.apollo.rio.br/ij.html" title="Apollo Plano Visualizer" allowfullscreen
			loading="lazy"></iframe>
	</div>
	<br>

	<a href="https://plano.apollo.rio.br/" target="_blank">
		<div class="hub-link hub-link-primary" style=“width:55px!important”>
			<span style="width:100%;justify-content:flex-end;align-text:right;">
				<b>Plano.px</b> — Apollo's Creative Studio </span>
			<i class="ri-arrow-right-up-long-line"></i>
		</div>
	</a>


	<br>

	<div class="reveal-up delay-300">
		<a href="https://plano.apollo.rio.br/" target="_blank" style="text-decoration: none;">
			<div class="hub-link hub-link-primary"
				style="width: auto !important; min-width: 280px; display: inline-flex;">
				<span style="flex-grow: 1; text-align: left;">
					<b>Plano.px</b> — Apollo's Creative Studio
				</span>
				<i class="ri-arrow-right-up-long-line"></i>
			</div>
		</a>
	</div>
</section>
<br>
<script>
(function() {
	// Safe Icon Loader
	var s = document.createElement('script');
	s.src = 'https://cdn.apollo.rio.br/';
	s.async = true; // Non-blocking
	s.onerror = function() {
		var f = document.createElement('script');
		f.src = 'https://assets.apollo.rio.br/icon.js';
		document.head.appendChild(f);
	};
	document.head.appendChild(s);
})();
</script>