<?php
/**
 * Template Part: Editor Tab Sidebar Content
 * LUXURY GRADE Block Editor with Apollo Dropdown
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="sidebar-tab-content" data-tab="editor">

  <!-- Floating Add Block Button -->
  <div class="add-block-container">
    <button class="add-block-btn" id="addBlockBtn" onclick="HUB.toggleBlockPicker()">
      <i class="ri-add-line"></i>
    </button>

    <!-- Block Picker Mega Dropdown -->
    <div class="block-picker-overlay" id="blockPickerOverlay" onclick="HUB.closeBlockPicker()"></div>
    <div class="block-picker-panel" id="blockPickerPanel">
      <div class="block-picker-header">
        <div class="block-picker-search-wrapper">
          <i class="ri-search-line block-picker-search-icon"></i>
          <input type="text" class="block-picker-search" id="blockSearchInput" placeholder="Buscar widgets..." oninput="HUB.filterBlocks(this.value)" />
        </div>
      </div>

    <div class="block-picker-body">

      <!-- TEXT BLOCKS -->
      <div class="block-category">
        <div class="block-category-label">
          <i class="ri-text mr-2"></i>Texto & Títulos
        </div>
        <div class="block-grid">
          <div class="block-option" data-block="title_block" onclick="HUB.addBlock('title_block')">
            <div class="block-option-icon"><i class="ri-heading"></i></div>
            <div class="block-option-info">
              <div class="block-option-title">Título</div>
              <div class="block-option-desc">Nome do usuário</div>
            </div>
          </div>
          <div class="block-option" data-block="paragraph_block" onclick="HUB.addBlock('paragraph_block')">
            <div class="block-option-icon"><i class="ri-paragraph"></i></div>
            <div class="block-option-info">
              <div class="block-option-title">Parágrafo</div>
              <div class="block-option-desc">Texto livre</div>
            </div>
          </div>
          <div class="block-option" data-block="bio_block" onclick="HUB.addBlock('bio_block')">
            <div class="block-option-icon"><i class="ri-user-heart-line"></i></div>
            <div class="block-option-info">
              <div class="block-option-title">Bio</div>
              <div class="block-option-desc">Biografia curta</div>
            </div>
          </div>
        </div>
      </div>

      <!-- LINKS & CARDS -->
      <div class="block-category">
        <div class="block-category-label">
          <i class="ri-link mr-2"></i>Links & Cards
        </div>
        <div class="block-grid">
          <div class="block-option" data-block="card_simple" onclick="HUB.addBlock('card_simple')">
            <div class="block-option-icon"><i class="ri-layout-line"></i></div>
            <div class="block-option-info">
              <div class="block-option-title">Card Simples</div>
              <div class="block-option-desc">Título + URL</div>
            </div>
          </div>
          <div class="block-option" data-block="card_icon" onclick="HUB.addBlock('card_icon')">
            <div class="block-option-icon"><i class="ri-apps-line"></i></div>
            <div class="block-option-info">
              <div class="block-option-title">Card com Ícone</div>
              <div class="block-option-desc">Ícone + Título + URL</div>
            </div>
          </div>
          <div class="block-option" data-block="image_link" onclick="HUB.addBlock('image_link')">
            <div class="block-option-icon"><i class="ri-image-line"></i></div>
            <div class="block-option-info">
              <div class="block-option-title">Imagem + Link</div>
              <div class="block-option-desc">Imagem clicável</div>
            </div>
          </div>
          <div class="block-option" data-block="image_overlay" onclick="HUB.addBlock('image_overlay')">
            <div class="block-option-icon"><i class="ri-gallery-line"></i></div>
            <div class="block-option-info">
              <div class="block-option-title">Imagem Overlay</div>
              <div class="block-option-desc">Imagem + Título sobre</div>
            </div>
          </div>
        </div>
      </div>

      <!-- EVENTOS -->
      <div class="block-category">
        <div class="block-category-label">
          <i class="ri-calendar-event-line mr-2"></i>Eventos
        </div>
        <div class="block-grid">
          <div class="block-option" data-block="event_internal" onclick="HUB.addBlock('event_internal')">
            <div class="block-option-icon"><i class="ri-calendar-check-line"></i></div>
            <div class="block-option-info">
              <div class="block-option-title">Evento Apollo</div>
              <div class="block-option-desc">Selecionar do sistema</div>
            </div>
          </div>
          <div class="block-option" data-block="event_external" onclick="HUB.addBlock('event_external')">
            <div class="block-option-icon"><i class="ri-calendar-line"></i></div>
            <div class="block-option-info">
              <div class="block-option-title">Evento Externo</div>
              <div class="block-option-desc">Data + Título + URL</div>
            </div>
          </div>
          <div class="block-option" data-block="events_interested" onclick="HUB.addBlock('events_interested')">
            <div class="block-option-icon"><i class="ri-heart-line"></i></div>
            <div class="block-option-info">
              <div class="block-option-title">Eventos Interessados</div>
              <div class="block-option-desc">Seus interesses</div>
            </div>
          </div>
        </div>
      </div>

      <!-- MÍDIA -->
      <div class="block-category">
        <div class="block-category-label">
          <i class="ri-play-circle-line mr-2"></i>Mídia
        </div>
        <div class="block-grid">
          <div class="block-option" data-block="youtube" onclick="HUB.addBlock('youtube')">
            <div class="block-option-icon" style="color:#FF0000"><i class="ri-youtube-line"></i></div>
            <div class="block-option-info">
              <div class="block-option-title">YouTube</div>
              <div class="block-option-desc">Vídeo embed</div>
            </div>
          </div>
          <div class="block-option" data-block="soundcloud" onclick="HUB.addBlock('soundcloud')">
            <div class="block-option-icon" style="color:#FF5500"><i class="ri-soundcloud-line"></i></div>
            <div class="block-option-info">
              <div class="block-option-title">SoundCloud</div>
              <div class="block-option-desc">Track embed</div>
            </div>
          </div>
          <div class="block-option" data-block="spotify" onclick="HUB.addBlock('spotify')">
            <div class="block-option-icon" style="color:#1DB954"><i class="ri-spotify-line"></i></div>
            <div class="block-option-info">
              <div class="block-option-title">Spotify</div>
              <div class="block-option-desc">Playlist embed</div>
            </div>
          </div>
        </div>
      </div>

      <!-- SOCIAL & INTERAÇÃO -->
      <div class="block-category">
        <div class="block-category-label">
          <i class="ri-group-line mr-2"></i>Social & Interação
        </div>
        <div class="block-grid">
          <div class="block-option" data-block="testimonials" onclick="HUB.addBlock('testimonials')">
            <div class="block-option-icon"><i class="ri-chat-quote-line"></i></div>
            <div class="block-option-info">
              <div class="block-option-title">Depoimentos</div>
              <div class="block-option-desc">Comentários de usuários</div>
            </div>
          </div>
          <div class="block-option" data-block="rating_stars" onclick="HUB.addBlock('rating_stars')">
            <div class="block-option-icon" style="color:#FFD700"><i class="ri-star-fill"></i></div>
            <div class="block-option-info">
              <div class="block-option-title">Avaliação Estrelas</div>
              <div class="block-option-desc">Rating 0-5 estrelas</div>
            </div>
          </div>
          <div class="block-option" data-block="orkut_rate" onclick="HUB.addBlock('orkut_rate')">
            <div class="block-option-icon" style="color:#E91E63"><i class="ri-heart-3-line"></i></div>
            <div class="block-option-info">
              <div class="block-option-title">Orkut Rate</div>
              <div class="block-option-desc">Confiável, Legal, Sexy</div>
            </div>
          </div>
          <div class="block-option" data-block="social_links" onclick="HUB.addBlock('social_links')">
            <div class="block-option-icon"><i class="ri-share-line"></i></div>
            <div class="block-option-info">
              <div class="block-option-title">Redes Sociais</div>
              <div class="block-option-desc">Seus links sociais</div>
            </div>
          </div>
          <div class="block-option" data-block="share_page" onclick="HUB.addBlock('share_page')">
            <div class="block-option-icon"><i class="ri-share-forward-line"></i></div>
            <div class="block-option-info">
              <div class="block-option-title">Compartilhar Página</div>
              <div class="block-option-desc">Botões de share</div>
            </div>
          </div>
        </div>
      </div>

      <!-- USUÁRIOS -->
      <div class="block-category">
        <div class="block-category-label">
          <i class="ri-user-line mr-2"></i>Usuários & Autores
        </div>
        <div class="block-grid">
          <div class="block-option" data-block="user_bubble" onclick="HUB.addBlock('user_bubble')">
            <div class="block-option-icon"><i class="ri-account-circle-line"></i></div>
            <div class="block-option-info">
              <div class="block-option-title">Bubble Usuário</div>
              <div class="block-option-desc">Avatar circular</div>
            </div>
          </div>
          <div class="block-option" data-block="user_coauthors" onclick="HUB.addBlock('user_coauthors')">
            <div class="block-option-icon"><i class="ri-team-line"></i></div>
            <div class="block-option-info">
              <div class="block-option-title">Co-autores</div>
              <div class="block-option-desc">Lista de parceiros</div>
            </div>
          </div>
        </div>
      </div>

      <!-- CONTEÚDO -->
      <div class="block-category">
        <div class="block-category-label">
          <i class="ri-article-line mr-2"></i>Conteúdo
        </div>
        <div class="block-grid">
          <div class="block-option" data-block="latest_news" onclick="HUB.addBlock('latest_news')">
            <div class="block-option-icon"><i class="ri-newspaper-line"></i></div>
            <div class="block-option-info">
              <div class="block-option-title">Últimas Notícias</div>
              <div class="block-option-desc">Posts recentes Apollo</div>
            </div>
          </div>
          <div class="block-option" data-block="marquee" onclick="HUB.addBlock('marquee')">
            <div class="block-option-icon"><i class="ri-text"></i></div>
            <div class="block-option-info">
              <div class="block-option-title">Marquee</div>
              <div class="block-option-desc">Texto em movimento</div>
            </div>
          </div>
          <div class="block-option" data-block="text_block" onclick="HUB.addBlock('text_block')">
            <div class="block-option-icon"><i class="ri-file-text-line"></i></div>
            <div class="block-option-info">
              <div class="block-option-title">Bloco de Texto</div>
              <div class="block-option-desc">Texto livre</div>
            </div>
          </div>
          <div class="block-option" data-block="divider" onclick="HUB.addBlock('divider')">
            <div class="block-option-icon"><i class="ri-separator"></i></div>
            <div class="block-option-info">
              <div class="block-option-title">Divisor</div>
              <div class="block-option-desc">Linha separadora</div>
            </div>
          </div>
        </div>
      </div>

    </div><!-- Close block-picker-body -->
  </div><!-- Close block-picker-panel -->
  </div><!-- Close add-block-container -->

  <!-- Blocks List (draggable) -->
  <div class="sidebar-group-label">Seus Widgets</div>
  <div id="blocksList" class="blocks-list">
    <!-- Dynamically populated by JS -->
  </div>

</div>
