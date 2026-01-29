/**
 * Apollo Loading Animation
 * Baseado em https://codepen.io/Rafael-Valle-the-looper/pen/bNpRoPe
 */
(function () {
	'use strict';

	/**
	 * Cria elemento de loading animation
	 */
	function createLoadingAnimation() {
		const loader     = document.createElement( 'div' );
		loader.className = 'apollo-loader-container';
		loader.setAttribute( 'aria-live', 'polite' );
		loader.setAttribute( 'aria-busy', 'true' );
		loader.innerHTML    = `
			< div class     = "apollo-loader" >
				< div class = "apollo-loader-ring" > < / div >
				< div class = "apollo-loader-ring" > < / div >
				< div class = "apollo-loader-ring" > < / div >
				< div class = "apollo-loader-pulse" > < / div >
			< / div >
			< p class       = "apollo-loader-text" > Carregando... < / p >
		`;
		return loader;
	}

	/**
	 * Mostra loading
	 */
	window.apolloShowLoading = function (container) {
		const target   = container || document.body;
		const existing = target.querySelector( '.apollo-loader-container' );

		if (existing) {
			return existing;
		}

		const loader = createLoadingAnimation();
		target.appendChild( loader );

		// Add class to body to prevent scrolling
		document.body.classList.add( 'apollo-loading-active' );

		return loader;
	};

	/**
	 * Esconde loading
	 */
	window.apolloHideLoading = function (container) {
		const target = container || document.body;
		const loader = target.querySelector( '.apollo-loader-container' );

		if (loader) {
			loader.classList.add( 'fade-out' );
			setTimeout(
				function () {
					loader.remove();
					document.body.classList.remove( 'apollo-loading-active' );
				},
				300
			);
		}
	};

	/**
	 * Loading para imagens de eventos
	 */
	window.apolloImageLoader = function (imgElement) {
		if ( ! imgElement || imgElement.complete) {
			return Promise.resolve();
		}

		// Adiciona classe de loading
		const wrapper = imgElement.closest( '.picture' ) || imgElement.parentElement;
		if (wrapper) {
			wrapper.classList.add( 'apollo-image-loading' );
		}

		return new Promise(
			function (resolve, reject) {
				imgElement.addEventListener(
					'load',
					function () {
						if (wrapper) {
							wrapper.classList.remove( 'apollo-image-loading' );
							wrapper.classList.add( 'apollo-image-loaded' );
						}
						resolve();
					}
				);

				imgElement.addEventListener(
					'error',
					function () {
						if (wrapper) {
							wrapper.classList.remove( 'apollo-image-loading' );
							wrapper.classList.add( 'apollo-image-error' );
						}
						reject( new Error( 'Image load failed' ) );
					}
				);
			}
		);
	};

	/**
	 * Auto-aplicar loading em todas as imagens de eventos
	 */
	function initImageLoaders() {
		const eventImages = document.querySelectorAll( '.event_listing .picture img' );
		eventImages.forEach(
			function (img) {
				apolloImageLoader( img );
			}
		);
	}

	// Inicializar
	if (document.readyState === 'loading') {
		document.addEventListener( 'DOMContentLoaded', initImageLoaders );
	} else {
		initImageLoaders();
	}
})();
