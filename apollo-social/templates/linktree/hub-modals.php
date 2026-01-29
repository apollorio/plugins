<?php
/**
 * Template Part: Modals (Icon Picker, Style Customization, etc.)
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<!-- Icon Picker Modal -->
<div class="modal-overlay" id="iconPickerModal">
  <div class="modal-card">
    <div class="modal-header">
      <div class="modal-title">Escolha um Ícone</div>
      <button class="modal-close-btn" onclick="HUB.closeIconPicker()">
        <i class="ri-close-line"></i>
      </button>
    </div>
    <div class="modal-body" style="display:block;">
      <div class="icon-grid">
        <?php
        $icons = [
          ['ri-home-line', 'Home'],
          ['ri-user-line', 'Usuário'],
          ['ri-heart-line', 'Coração'],
          ['ri-star-line', 'Estrela'],
          ['ri-fire-line', 'Fogo'],
          ['ri-trophy-line', 'Troféu'],
          ['ri-book-line', 'Livro'],
          ['ri-music-line', 'Música'],
          ['ri-film-line', 'Filme'],
          ['ri-camera-line', 'Câmera'],
          ['ri-shopping-cart-line', 'Carrinho'],
          ['ri-gift-line', 'Presente'],
          ['ri-plane-line', 'Avião'],
          ['ri-map-pin-line', 'Local'],
          ['ri-calendar-line', 'Calendário'],
          ['ri-mail-line', 'Email'],
          ['ri-phone-line', 'Telefone'],
          ['ri-message-line', 'Mensagem'],
          ['ri-notification-line', 'Notificação'],
          ['ri-settings-line', 'Config'],
          ['ri-link', 'Link'],
          ['ri-external-link-line', 'Link Ext'],
          ['ri-share-line', 'Compartilhar'],
          ['ri-download-line', 'Download'],
          ['ri-upload-line', 'Upload'],
          ['ri-search-line', 'Busca'],
          ['ri-add-line', 'Adicionar'],
          ['ri-subtract-line', 'Remover'],
          ['ri-check-line', 'Check'],
          ['ri-close-line', 'Fechar'],
          ['ri-arrow-right-line', 'Seta Dir'],
          ['ri-arrow-left-line', 'Seta Esq'],
          ['ri-arrow-up-line', 'Seta Cima'],
          ['ri-arrow-down-line', 'Seta Baixo'],
          ['ri-menu-line', 'Menu'],
          ['ri-more-line', 'Mais'],
          ['ri-lightbulb-line', 'Lâmpada'],
          ['ri-rocket-line', 'Foguete'],
          ['ri-flag-line', 'Bandeira'],
          ['ri-globe-line', 'Globo'],
          ['ri-instagram-line', 'Instagram'],
          ['ri-twitter-x-line', 'Twitter/X'],
          ['ri-facebook-line', 'Facebook'],
          ['ri-youtube-line', 'YouTube'],
          ['ri-tiktok-line', 'TikTok'],
          ['ri-whatsapp-line', 'WhatsApp'],
          ['ri-telegram-line', 'Telegram'],
          ['ri-linkedin-line', 'LinkedIn'],
          ['ri-spotify-line', 'Spotify'],
          ['ri-soundcloud-line', 'SoundCloud'],
        ];

        foreach ( $icons as $icon ) :
          ?>
          <div class="icon-choice" onclick="HUB.selectIcon('<?php echo esc_js( $icon[0] ); ?>')">
            <i class="<?php echo esc_attr( $icon[0] ); ?>"></i>
            <span><?php echo esc_html( $icon[1] ); ?></span>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</div>

<!-- Style Customization Modal -->
<div class="modal-overlay" id="styleModal">
  <div class="modal-card" style="max-width:540px;">
    <div class="modal-header">
      <div class="modal-title">
        <i class="ri-palette-line mr-2"></i>Customizar Widget
      </div>
      <button class="modal-close-btn" onclick="HUB.closeStyleModal()">
        <i class="ri-close-line"></i>
      </button>
    </div>
    <div class="modal-body">
      <!-- Border Radius -->
      <div class="field">
        <label class="label">Borda Arredondada</label>
        <div class="range-input-wrapper">
          <input type="range" id="styleBorderRadius" min="0" max="50" value="12"
            oninput="HUB.applyStyle('borderRadius', parseInt(this.value)); document.getElementById('borderRadiusValue').textContent = this.value + 'px'">
          <span class="range-value" id="borderRadiusValue">12px</span>
        </div>
      </div>

      <!-- Opacity -->
      <div class="field">
        <label class="label">Opacidade</label>
        <div class="range-input-wrapper">
          <input type="range" id="styleOpacity" min="10" max="100" value="100"
            oninput="HUB.applyStyle('opacity', parseInt(this.value)); document.getElementById('opacityValue').textContent = this.value + '%'">
          <span class="range-value" id="opacityValue">100%</span>
        </div>
      </div>

      <!-- Text Color -->
      <div class="field">
        <label class="label">Cor do Texto</label>
        <div class="color-input-wrapper">
          <input type="color" id="styleColor" value="#18181b"
            oninput="HUB.applyStyle('color', this.value); document.getElementById('styleColorText').value = this.value">
          <input type="text" id="styleColorText" class="input" value="#18181b" placeholder="#18181b"
            oninput="HUB.applyStyle('color', this.value); document.getElementById('styleColor').value = this.value">
        </div>
      </div>

      <!-- Background Color -->
      <div class="field">
        <label class="label">Cor de Fundo</label>
        <div class="color-input-wrapper">
          <input type="color" id="styleBgColor" value="#ffffff"
            oninput="HUB.applyStyle('bgColor', this.value); document.getElementById('styleBgColorText').value = this.value">
          <input type="text" id="styleBgColorText" class="input" value="#ffffff" placeholder="#ffffff"
            oninput="HUB.applyStyle('bgColor', this.value); document.getElementById('styleBgColor').value = this.value">
        </div>
      </div>

      <!-- Background Gradient -->
      <div class="field field-full">
        <label class="label">Gradiente de Fundo</label>
        <input type="text" id="styleBgGradient" class="input" placeholder="linear-gradient(135deg, #667eea, #764ba2)"
          oninput="HUB.applyStyle('bgGradient', this.value)">
        <span class="help">Ex: linear-gradient(135deg, #667eea, #764ba2)</span>
      </div>

      <!-- Background Image URL -->
      <div class="field field-full">
        <label class="label">Imagem de Fundo (URL)</label>
        <input type="url" id="styleBgImage" class="input" placeholder="https://..."
          oninput="HUB.applyStyle('bgImage', this.value)">
      </div>

      <!-- Padding -->
      <div class="field">
        <label class="label">Espaçamento Interno</label>
        <div class="range-input-wrapper">
          <input type="range" id="stylePadding" min="0" max="48" value="16"
            oninput="HUB.applyStyle('padding', parseInt(this.value)); document.getElementById('paddingValue').textContent = this.value + 'px'">
          <span class="range-value" id="paddingValue">16px</span>
        </div>
      </div>

      <!-- Font Size -->
      <div class="field">
        <label class="label">Tamanho da Fonte</label>
        <div class="range-input-wrapper">
          <input type="range" id="styleFontSize" min="10" max="24" value="14"
            oninput="HUB.applyStyle('fontSize', parseInt(this.value)); document.getElementById('fontSizeValue').textContent = this.value + 'px'">
          <span class="range-value" id="fontSizeValue">14px</span>
        </div>
      </div>
    </div>
    <div class="modal-footer">
      <button class="button" onclick="HUB.closeStyleModal()">Fechar</button>
    </div>
  </div>
</div>

<!-- Social Links Editor Modal -->
<div class="modal-overlay" id="socialEditorModal">
  <div class="modal-card">
    <div class="modal-header">
      <div class="modal-title">
        <i class="ri-share-line mr-2"></i>Configurar Redes Sociais
      </div>
      <button class="modal-close-btn" onclick="HUB.closeSocialEditor()">
        <i class="ri-close-line"></i>
      </button>
    </div>
    <div class="modal-body" style="display:block;">
      <div id="socialLinksContainer"></div>
      <button class="button is-light is-fullwidth" style="margin-top:1rem;" onclick="HUB.addSocialLink()">
        <i class="ri-add-line mr-2"></i>Adicionar Rede
      </button>
    </div>
    <div class="modal-footer">
      <button class="button is-primary" onclick="HUB.saveSocialLinks()">Salvar</button>
    </div>
  </div>
</div>

<!-- Testimonials Editor Modal -->
<div class="modal-overlay" id="testimonialEditorModal">
  <div class="modal-card" style="max-width:600px;">
    <div class="modal-header">
      <div class="modal-title">
        <i class="ri-chat-quote-line mr-2"></i>Editar Depoimentos
      </div>
      <button class="modal-close-btn" onclick="HUB.closeTestimonialEditor()">
        <i class="ri-close-line"></i>
      </button>
    </div>
    <div class="modal-body" style="display:block; max-height:60vh; overflow-y:auto;">
      <div id="testimonialsContainer"></div>
      <button class="button is-light is-fullwidth" style="margin-top:1rem;" onclick="HUB.addTestimonial()">
        <i class="ri-add-line mr-2"></i>Adicionar Depoimento
      </button>
    </div>
    <div class="modal-footer">
      <button class="button is-primary" onclick="HUB.saveTestimonials()">Salvar</button>
    </div>
  </div>
</div>
