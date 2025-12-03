<?php
/**
 * Partial: Community Hero Card
 * STRICT MODE: UNI.CSS compliance
 * Reusable hero/cover component for community or núcleo
 *
 * @var string $title Group name
 * @var string $description Short description
 * @var string $cover_url Cover image URL
 * @var string $avatar_url Avatar/logo URL
 * @var int $members_count Member count
 * @var array $tags Hashtags array
 * @var bool $is_active Active status (last activity < 24h)
 * @var bool $is_verified Verified status (nucleos only)
 * @var string $type 'comunidade' | 'nucleo'
 *
 * @package Apollo_Social
 * @version 2.1.0
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$title         = $title ?? '';
$description   = $description ?? '';
$cover_url     = $cover_url ?? '';
$avatar_url    = $avatar_url ?? '';
$members_count = (int) ( $members_count ?? 0 );
$tags          = is_array( $tags ?? null ) ? $tags : array();
$is_active     = (bool) ( $is_active ?? false );
$is_verified   = (bool) ( $is_verified ?? false );
$type          = $type ?? 'comunidade';

// Colors based on type
$accent_color = $type === 'nucleo' ? 'orange' : 'purple';
$badge_icon   = $type === 'nucleo' ? 'ri-fire-fill' : 'ri-vip-crown-2-line';
?>
<div class="ap-hero-card ap-rounded-2xl ap-overflow-hidden ap-bg-dark ap-border ap-border-dark ap-relative">
	<div class="ap-hero-card-cover ap-h-32">
		<?php if ( $cover_url ) : ?>
		<img src="<?php echo esc_url( $cover_url ); ?>" 
			alt="" 
			class="ap-w-full ap-h-full ap-object-cover ap-opacity-90" 
			loading="lazy" />
		<?php else : ?>
		<div class="ap-w-full ap-h-full ap-bg-<?php echo esc_attr( $accent_color ); ?>-gradient ap-opacity-80"></div>
		<?php endif; ?>
	</div>
	
	<div class="ap-hero-card-content ap-absolute ap-inset-0 ap-flex ap-flex-col ap-justify-end ap-p-4 ap-bg-gradient-overlay">
		<div class="ap-flex ap-items-center ap-gap-2 ap-mb-2">
			<span class="ap-avatar ap-avatar-sm ap-bg-glass ap-border ap-border-white-20">
				<?php if ( $avatar_url ) : ?>
				<img src="<?php echo esc_url( $avatar_url ); ?>" 
					alt="" 
					class="<?php echo $type === 'comunidade' ? 'ap-rounded-full' : ''; ?>" />
				<?php else : ?>
				<i class="<?php echo esc_attr( $badge_icon ); ?> ap-text-<?php echo esc_attr( $accent_color ); ?>-400"></i>
				<?php endif; ?>
			</span>
			
			<?php if ( $is_verified ) : ?>
			<span class="ap-badge ap-badge-primary ap-badge-sm">
				<i class="ri-verified-badge-fill"></i>
				<?php esc_html_e( 'Verificado', 'apollo-social' ); ?>
			</span>
			<?php elseif ( $is_active ) : ?>
			<span class="ap-badge ap-badge-glass ap-badge-sm">
				<span class="ap-badge-dot ap-badge-dot-success ap-animate-pulse"></span>
				<?php echo $type === 'nucleo' ? esc_html__( 'Ativo', 'apollo-social' ) : esc_html__( 'Comunidade ativa', 'apollo-social' ); ?>
			</span>
			<?php endif; ?>
		</div>
		
		<h2 class="ap-heading-lg ap-text-white ap-leading-snug">
			<?php echo esc_html( $title ); ?>
		</h2>
		
		<?php if ( $description ) : ?>
		<p class="ap-text-xs ap-text-white-80 ap-mt-1">
			<?php echo esc_html( wp_trim_words( $description, 20 ) ); ?>
		</p>
		<?php endif; ?>
		
		<div class="ap-flex ap-items-center ap-gap-3 ap-mt-3 ap-text-xs ap-text-white-70">
			<span class="ap-flex ap-items-center ap-gap-1" data-ap-tooltip="<?php esc_attr_e( 'Membros', 'apollo-social' ); ?>">
				<i class="ri-user-3-line"></i> 
				<?php echo esc_html( number_format_i18n( $members_count ) ); ?> <?php esc_html_e( 'membros', 'apollo-social' ); ?>
			</span>
			<?php if ( ! empty( $tags ) ) : ?>
			<span class="ap-flex ap-items-center ap-gap-1">
				<i class="ri-hashtag"></i> 
				#<?php echo esc_html( implode( ' #', array_slice( $tags, 0, 3 ) ) ); ?>
			</span>
			<?php endif; ?>
		</div>
	</div>
</div>
