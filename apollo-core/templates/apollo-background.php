<?php
/**
 * Apollo Animated Background
 * File: template-parts/apollo-background.php
 */
?>

<div class="ap-bg">
	<!-- Animated SVG shapes -->
	<div class="ap-bg-shape">
		<svg viewBox="0 0 400 400" xmlns="http://www.w3.org/2000/svg" fill="none">
			<g filter="url(#blur1)">
				<path d="M200,50 Q300,100 350,200 Q300,300 200,350 Q100,300 50,200 Q100,100 200,50 Z" 
						fill="url(#grad1)" opacity="0.4"/>
			</g>
			<defs>
				<linearGradient id="grad1" x1="0%" y1="0%" x2="100%" y2="100%">
					<stop offset="0%" style="stop-color:rgb(255,237,213);stop-opacity:0.3" />
					<stop offset="100%" style="stop-color:rgb(254,243,199);stop-opacity:0.2" />
				</linearGradient>
				<filter id="blur1" x="-50%" y="-50%" width="200%" height="200%">
					<feGaussianBlur in="SourceGraphic" stdDeviation="40" />
				</filter>
			</defs>
		</svg>
	</div>

	<div class="ap-bg-shape">
		<svg viewBox="0 0 400 400" xmlns="http://www.w3.org/2000/svg" fill="none">
			<g filter="url(#blur2)">
				<circle cx="200" cy="200" r="150" fill="url(#grad2)" opacity="0.05"/>
			</g>
			<defs>
				<radialGradient id="grad2">
					<stop offset="0%" style="stop-color:rgb(255,245,235);stop-opacity:0.04" />
					<stop offset="100%" style="stop-color:rgb(255,250,245);stop-opacity:0.05" />
				</radialGradient>
				<filter id="blur2" x="-50%" y="-50%" width="200%" height="200%">
					<feGaussianBlur in="SourceGraphic" stdDeviation="50" />
				</filter>
			</defs>
		</svg>
	</div>

	<div class="ap-bg-shape">
		<svg viewBox="0 0 400 400" xmlns="http://www.w3.org/2000/svg" fill="none">
			<g filter="url(#blur3)">
				<ellipse cx="200" cy="200" rx="180" ry="140" fill="url(#grad3)" opacity="0.03"/>
			</g>
			<defs>
				<linearGradient id="grad3" x1="0%" y1="0%" x2="100%" y2="0%">
					<stop offset="0%" style="stop-color:rgb(255,251,235);stop-opacity:0.03" />
					<stop offset="100%" style="stop-color:rgb(255,255,255);stop-opacity:0.1" />
				</linearGradient>
				<filter id="blur3" x="-50%" y="-50%" width="200%" height="200%">
					<feGaussianBlur in="SourceGraphic" stdDeviation="45" />
				</filter>
			</defs>
		</svg>
	</div>

	<div class="ap-bg-shape">
		<svg viewBox="0 0 400 400" xmlns="http://www.w3.org/2000/svg" fill="none">
			<g filter="url(#blur4)">
				<path d="M200,100 L280,200 L200,300 L120,200 Z" 
						fill="url(#grad4)" opacity="0.25"/>
			</g>
			<defs>
				<linearGradient id="grad4" x1="50%" y1="0%" x2="50%" y2="100%">
					<stop offset="0%" style="stop-color:rgb(254,243,199);stop-opacity:0.07" />
					<stop offset="100%" style="stop-color:rgb(255,255,255);stop-opacity:0.05" />
				</linearGradient>
				<filter id="blur4" x="-50%" y="-50%" width="200%" height="200%">
					<feGaussianBlur in="SourceGraphic" stdDeviation="35" />
				</filter>
			</defs>
		</svg>
	</div>
</div>
