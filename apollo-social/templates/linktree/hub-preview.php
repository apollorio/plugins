<?php
/**
 * Template Part: Phone Preview Frame
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$user_id = get_current_user_id();
$avatar_style = get_user_meta( $user_id, '_apollo_hub_avatar_style', true ) ?: 'rounded';
$avatar_url = get_user_meta( $user_id, '_apollo_hub_avatar', true ) ?: 'https://assets.apollo.rio.br/i/default-avatar.png';
$avatar_border = get_user_meta( $user_id, '_apollo_hub_avatar_border', true ) === '1';
$avatar_border_width = get_user_meta( $user_id, '_apollo_hub_avatar_border_width', true ) ?: '4';
$avatar_border_color = get_user_meta( $user_id, '_apollo_hub_avatar_border_color', true ) ?: '#ffffff';
?>
<div class="phone-frame">
  <div class="dynamic-island"></div>

  <div class="phone-screen">
    <!-- Background blur layer -->
    <div class="p-bg-layer" id="previewBgLayer"></div>

    <!-- Texture overlay -->
    <div class="texture-overlay" id="textureOverlay"></div>

    <!-- Content -->
    <div class="p-container">

      <!-- Header/Profile -->
      <div class="p-header">
        <!-- Dynamic Avatar Container -->
        <div id="previewAvatarContainer">
          <?php if ( $avatar_style === 'hero' ) : ?>
            <div class="avatar-hero">
              <div class="avatar-hero-box">
                <div class="avatar-hero-spin">
                  <div class="avatar-hero-shape">
                    <div class="avatar-hero-image" style="background-image:url(<?php echo esc_url( $avatar_url ); ?>)"></div>
                  </div>
                </div>
              </div>
            </div>
          <?php else : ?>
            <?php
            $border_style = $avatar_border
              ? 'border: ' . intval( $avatar_border_width ) . 'px solid ' . esc_attr( $avatar_border_color ) . ';'
              : '';
            ?>
            <img
              id="previewAvatar"
              class="p-avatar"
              src="<?php echo esc_url( $avatar_url ); ?>"
              alt="Avatar"
              style="<?php echo esc_attr( $border_style ); ?>"
            />
          <?php endif; ?>
        </div>
        <div id="previewName" class="p-name">@seunome</div>
        <div id="previewBio" class="p-bio">Sua bio aparece aqui</div>
      </div>

      <!-- Blocks -->
      <div id="previewBlocks" class="p-blocks">
        <!-- Dynamically rendered by JS -->
      </div>

    </div>
  </div>
</div>
