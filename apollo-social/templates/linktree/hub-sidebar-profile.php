<?php
/**
 * Template Part: Profile Tab Sidebar Content
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$user_id = get_current_user_id();
$avatar_style = get_user_meta( $user_id, '_apollo_hub_avatar_style', true ) ?: 'rounded';
$avatar_border = get_user_meta( $user_id, '_apollo_hub_avatar_border', true ) === '1';
$avatar_border_width = get_user_meta( $user_id, '_apollo_hub_avatar_border_width', true ) ?: '4';
$avatar_border_color = get_user_meta( $user_id, '_apollo_hub_avatar_border_color', true ) ?: '#ffffff';
?>
<div class="sidebar-tab-content is-hidden" data-tab="perfil">

  <div class="field">
    <label class="label">Foto de Perfil (URL)</label>
    <input
      class="input"
      type="text"
      placeholder="https://exemplo.com/avatar.jpg"
      value="<?php echo esc_attr( get_user_meta( $user_id, '_apollo_hub_avatar', true ) ); ?>"
      oninput="HUB.updateProfile('avatar', this.value)"
    />
    <p class="help">Cole a URL da imagem do seu avatar.</p>
  </div>

  <!-- Avatar Style Selection -->
  <div class="sidebar-group-label">ESTILO DO AVATAR</div>

  <div class="field">
    <label class="label">Tipo de Avatar</label>
    <div class="btn-group" style="display:flex; gap:8px;">
      <button
        class="button is-small <?php echo $avatar_style === 'rounded' ? 'is-primary' : 'is-light'; ?>"
        onclick="HUB.updateProfile('avatarStyle', 'rounded')"
        style="flex:1;"
      >
        <i class="ri-user-line mr-2"></i>Arredondado
      </button>
      <button
        class="button is-small <?php echo $avatar_style === 'hero' ? 'is-primary' : 'is-light'; ?>"
        onclick="HUB.updateProfile('avatarStyle', 'hero')"
        style="flex:1;"
      >
        <i class="ri-magic-line mr-2"></i>Hero Animado
      </button>
    </div>
    <p class="help">Hero = Avatar animado com morphing dinâmico.</p>
  </div>

  <!-- Rounded Avatar Border Options (only visible when rounded) -->
  <div id="avatarBorderOptions" style="<?php echo $avatar_style === 'hero' ? 'display:none;' : ''; ?>">
    <div class="field">
      <label class="checkbox-label">
        <input
          type="checkbox"
          id="avatarBorderEnabled"
          <?php checked( $avatar_border ); ?>
          onchange="HUB.updateProfile('avatarBorder', this.checked)"
        />
        Mostrar borda ao redor do avatar
      </label>
    </div>

    <div id="avatarBorderSettings" style="<?php echo ! $avatar_border ? 'display:none;' : ''; ?>">
      <div class="columns is-mobile">
        <div class="column">
          <label class="label">Largura</label>
          <input
            class="input"
            type="number"
            min="1"
            max="20"
            value="<?php echo esc_attr( $avatar_border_width ); ?>"
            oninput="HUB.updateProfile('avatarBorderWidth', parseInt(this.value))"
          />
        </div>
        <div class="column">
          <label class="label">Cor</label>
          <input
            class="input"
            type="color"
            value="<?php echo esc_attr( $avatar_border_color ); ?>"
            oninput="HUB.updateProfile('avatarBorderColor', this.value)"
            style="height:38px; padding:4px;"
          />
        </div>
      </div>
    </div>
  </div>

  <hr style="margin:1.5rem 0; border:none; border-top:1px solid var(--border);" />

  <div class="field">
    <label class="label">Nome</label>
    <input
      class="input"
      type="text"
      placeholder="@seunome"
      value="<?php echo esc_attr( get_user_meta( $user_id, '_apollo_hub_name', true ) ); ?>"
      oninput="HUB.updateProfile('name', this.value)"
    />
  </div>

  <div class="field">
    <label class="label">Bio</label>
    <textarea
      class="textarea"
      rows="3"
      placeholder="Conte um pouco sobre você..."
      oninput="HUB.updateProfile('bio', this.value)"
    ><?php echo esc_textarea( get_user_meta( $user_id, '_apollo_hub_bio', true ) ); ?></textarea>
  </div>

  <hr style="margin:1.5rem 0; border:none; border-top:1px solid var(--border);" />

  <div class="sidebar-group-label">FUNDO & TEXTURA</div>

  <div class="field">
    <label class="label">Imagem de Fundo (URL)</label>
    <input
      class="input"
      type="text"
      placeholder="https://exemplo.com/bg.jpg"
      value="<?php echo esc_attr( get_user_meta( $user_id, '_apollo_hub_bg', true ) ); ?>"
      oninput="HUB.updateProfile('bg', this.value)"
    />
    <p class="help">Esta imagem ficará desfocada ao fundo.</p>
  </div>

  <div class="field">
    <label class="label">Textura</label>
    <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:8px;">
      <?php
      $textures = [
        'none'     => 'Nenhuma',
        'dots'     => 'Dots',
        'waves'    => 'Waves',
        'grid'     => 'Grid',
        'noise'    => 'Noise',
        'confetti' => 'Confetti',
      ];
      $current_texture = get_user_meta( $user_id, '_apollo_hub_texture', true ) ?: 'none';
      foreach ( $textures as $key => $label ) :
        $is_active = ( $current_texture === $key ) ? 'is-primary' : 'is-light';
        ?>
        <button
          class="button is-small <?php echo esc_attr( $is_active ); ?>"
          onclick="HUB.updateProfile('texture', '<?php echo esc_js( $key ); ?>')"
        >
          <?php echo esc_html( $label ); ?>
        </button>
      <?php endforeach; ?>
    </div>
  </div>

</div>

<script>
// Toggle border settings visibility
document.getElementById('avatarBorderEnabled')?.addEventListener('change', function() {
  document.getElementById('avatarBorderSettings').style.display = this.checked ? '' : 'none';
});

// Original HUB.updateProfile wrapper to handle avatar style changes
const originalUpdateProfile = HUB.updateProfile.bind(HUB);
HUB.updateProfile = function(key, value) {
  originalUpdateProfile(key, value);

  if (key === 'avatarStyle') {
    document.getElementById('avatarBorderOptions').style.display = value === 'hero' ? 'none' : '';
  }
};
</script>
