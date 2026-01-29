/**
 * HUB::rio - LUXURY GRADE Linktree Editor
 * Complete state management, drag-and-drop, 20+ block types
 * @version 2.0.0
 */

const HUB = {
  state: {
    profile: {
      avatar: '',
      avatarStyle: 'rounded', // 'rounded' or 'hero'
      avatarBorder: false,
      avatarBorderWidth: 4,
      avatarBorderColor: '#ffffff',
      name: '',
      bio: '',
      bg: '',
      texture: 'none'
    },
    blocks: []
  },

  currentBlockId: null,
  currentStyleBlockId: null,
  eventsCache: [],
  postsCache: [],

  // Block type labels for display
  blockLabels: {
    title_block: 'Título',
    paragraph_block: 'Parágrafo',
    bio_block: 'Bio',
    card_simple: 'Card Simples',
    card_icon: 'Card com Ícone',
    image_link: 'Imagem + Link',
    image_overlay: 'Imagem Overlay',
    event_internal: 'Evento Apollo',
    event_external: 'Evento Externo',
    events_interested: 'Interesses',
    youtube: 'YouTube',
    soundcloud: 'SoundCloud',
    spotify: 'Spotify',
    testimonials: 'Depoimentos',
    rating_stars: 'Avaliação',
    orkut_rate: 'Orkut Rate',
    social_links: 'Redes Sociais',
    share_page: 'Compartilhar',
    user_bubble: 'Bubble',
    user_coauthors: 'Co-autores',
    latest_news: 'Notícias',
    marquee: 'Marquee',
    text_block: 'Texto',
    divider: 'Divisor'
  },

  // --- INITIALIZATION ---
  init() {
    this.loadState();
    this.loadEvents();
    this.loadPosts();
    this.setupTabSwitching();
    this.setupDragAndDrop();
    this.render();
    this.setupMobilePreview();
    this.setupTextureNavigation();
  },

  // --- STATE MANAGEMENT ---
  loadState() {
    const data = new FormData();
    data.append('action', 'apollo_hub_get_state');
    data.append('nonce', apolloHubVars.nonce);

    fetch(apolloHubVars.ajaxUrl, {
      method: 'POST',
      body: data
    })
    .then(r => r.json())
    .then(json => {
      if (json.success && json.data) {
        this.state = json.data;
        this.render();
      }
    })
    .catch(err => console.error('Error loading state:', err));
  },

  loadEvents() {
    const data = new FormData();
    data.append('action', 'apollo_hub_get_events');
    data.append('nonce', apolloHubVars.nonce);

    fetch(apolloHubVars.ajaxUrl, {
      method: 'POST',
      body: data
    })
    .then(r => r.json())
    .then(json => {
      if (json.success) {
        this.eventsCache = json.data || [];
      }
    })
    .catch(() => {});
  },

  loadPosts() {
    const data = new FormData();
    data.append('action', 'apollo_hub_get_posts');
    data.append('nonce', apolloHubVars.nonce);

    fetch(apolloHubVars.ajaxUrl, {
      method: 'POST',
      body: data
    })
    .then(r => r.json())
    .then(json => {
      if (json.success) {
        this.postsCache = json.data || [];
      }
    })
    .catch(() => {});
  },

  saveState() {
    const data = new FormData();
    data.append('action', 'apollo_hub_save_state');
    data.append('nonce', apolloHubVars.nonce);
    data.append('state', JSON.stringify(this.state));

    fetch(apolloHubVars.ajaxUrl, {
      method: 'POST',
      body: data
    })
    .then(r => r.json())
    .then(json => {
      if (json.success) {
        this.showToast('✓ Salvo!', 'success');
      } else {
        this.showToast('Erro ao salvar', 'error');
      }
    })
    .catch(() => this.showToast('Erro de conexão', 'error'));
  },

  showToast(msg, type = 'info') {
    const toast = document.createElement('div');
    toast.className = `hub-toast hub-toast--${type}`;
    toast.textContent = msg;
    document.body.appendChild(toast);
    setTimeout(() => toast.classList.add('show'), 10);
    setTimeout(() => {
      toast.classList.remove('show');
      setTimeout(() => toast.remove(), 300);
    }, 2000);
  },

  // --- PROFILE UPDATES ---
  updateProfile(key, value) {
    this.state.profile[key] = value;
    this.render();
    this.saveState();
  },

  // --- BLOCK PICKER ---
  toggleBlockPicker() {
    const panel = document.getElementById('blockPickerPanel');
    const overlay = document.getElementById('blockPickerOverlay');
    const btn = document.getElementById('addBlockBtn');

    if (panel && overlay) {
      panel.classList.toggle('is-open');
      overlay.classList.toggle('is-open');
      btn?.classList.toggle('is-active');
    }
  },

  closeBlockPicker() {
    const panel = document.getElementById('blockPickerPanel');
    const overlay = document.getElementById('blockPickerOverlay');
    const btn = document.getElementById('addBlockBtn');

    panel?.classList.remove('is-open');
    overlay?.classList.remove('is-open');
    btn?.classList.remove('is-active');
  },

  filterBlocks(query) {
    const q = query.toLowerCase().trim();
    document.querySelectorAll('.block-option').forEach(opt => {
      const title = opt.querySelector('.block-option-title')?.textContent.toLowerCase() || '';
      const desc = opt.querySelector('.block-option-desc')?.textContent.toLowerCase() || '';
      opt.style.display = (title.includes(q) || desc.includes(q)) ? 'flex' : 'none';
    });
  },

  // --- BLOCK MANAGEMENT ---
  addBlock(type) {
    const newBlock = {
      id: Date.now(),
      type: type,
      visible: true,
      style: this.getDefaultStyle(),
      ...this.getBlockDefaults(type)
    };

    this.state.blocks.push(newBlock);
    this.closeBlockPicker();
    this.render();
    this.saveState();
  },

  getDefaultStyle() {
    return {
      borderRadius: 12,
      opacity: 100,
      color: '#18181b',
      bgColor: '#ffffff',
      bgGradient: '',
      bgImage: '',
      padding: 16,
      fontSize: 14
    };
  },

  getBlockDefaults(type) {
    const defaults = {
      // TEXT BLOCKS
      title_block: {
        text: this.state.profile.name || 'Seu Nome',
        align: 'center',
        size: 'large'
      },
      paragraph_block: {
        text: 'Digite seu parágrafo aqui...',
        align: 'center'
      },
      bio_block: {
        text: this.state.profile.bio || 'Sua bio aparece aqui...',
        align: 'center'
      },

      // CARDS
      card_simple: {
        title: 'Meu Link',
        url: '#'
      },
      card_icon: {
        icon: 'ri-link',
        iconPosition: 'left',
        title: 'Link com Ícone',
        url: '#'
      },
      image_link: {
        imageUrl: 'https://cdn.apollo.rio.br/img/placeholder.jpg',
        url: '#',
        alt: 'Imagem'
      },
      image_overlay: {
        imageUrl: 'https://cdn.apollo.rio.br/img/placeholder.jpg',
        title: 'Título Overlay',
        url: '#'
      },

      // EVENTS
      event_internal: {
        eventId: 0,
        eventTitle: 'Selecione um evento'
      },
      event_external: {
        day: '15',
        month: 'JAN',
        title: 'Evento Externo',
        url: '#'
      },
      events_interested: {
        count: 3
      },

      // MEDIA
      youtube: {
        videoId: 'dQw4w9WgXcQ',
        url: 'https://www.youtube.com/watch?v=dQw4w9WgXcQ'
      },
      soundcloud: {
        trackUrl: '',
        embedCode: ''
      },
      spotify: {
        playlistUrl: '',
        embedUri: ''
      },

      // SOCIAL
      testimonials: {
        items: [
          { author: 'Maria', avatar: '', text: 'Incrível!', rating: 5 },
          { author: 'João', avatar: '', text: 'Muito bom!', rating: 4 }
        ]
      },
      rating_stars: {
        value: 4,
        maxValue: 5,
        label: 'Avaliação'
      },
      orkut_rate: {
        smile: 0,
        ice: 0,
        heart: 0
      },
      social_links: {
        links: [
          { platform: 'instagram', url: '#', icon: 'ri-instagram-line' },
          { platform: 'twitter', url: '#', icon: 'ri-twitter-x-line' },
          { platform: 'tiktok', url: '#', icon: 'ri-tiktok-line' }
        ]
      },
      share_page: {
        platforms: ['whatsapp', 'telegram', 'twitter', 'facebook', 'copy']
      },

      // USERS
      user_bubble: {
        userId: 0,
        showName: true,
        size: 'medium'
      },
      user_coauthors: {
        userIds: [],
        layout: 'horizontal'
      },

      // CONTENT
      latest_news: {
        count: 3,
        category: ''
      },
      marquee: {
        text: 'TEXTO EM MOVIMENTO • ANÚNCIO IMPORTANTE • ',
        speed: 'normal'
      },
      text_block: {
        content: 'Digite seu texto aqui...'
      },
      divider: {
        style: 'solid',
        thickness: 1
      }
    };

    return defaults[type] || {};
  },

  updateBlock(id, key, value) {
    const block = this.state.blocks.find(b => b.id === id);
    if (block) {
      block[key] = value;
      this.render();
      this.saveState();
    }
  },

  updateBlockStyle(id, key, value) {
    const block = this.state.blocks.find(b => b.id === id);
    if (block) {
      if (!block.style) block.style = this.getDefaultStyle();
      block.style[key] = value;
      this.render();
      this.saveState();
    }
  },

  deleteBlock(id) {
    if (!confirm('Remover este widget?')) return;
    this.state.blocks = this.state.blocks.filter(b => b.id !== id);
    this.render();
    this.saveState();
  },

  toggleBlockVisibility(id) {
    const block = this.state.blocks.find(b => b.id === id);
    if (block) {
      block.visible = !block.visible;
      this.render();
      this.saveState();
    }
  },

  // --- STYLE MODAL ---
  openStyleModal(blockId) {
    this.currentStyleBlockId = blockId;
    const block = this.state.blocks.find(b => b.id === blockId);
    if (!block) return;

    const modal = document.getElementById('styleModal');
    if (!modal) return;

    const style = block.style || this.getDefaultStyle();

    // Populate fields
    document.getElementById('styleBorderRadius').value = style.borderRadius;
    document.getElementById('styleOpacity').value = style.opacity;
    document.getElementById('styleColor').value = style.color;
    document.getElementById('styleBgColor').value = style.bgColor;
    document.getElementById('styleBgGradient').value = style.bgGradient || '';
    document.getElementById('styleBgImage').value = style.bgImage || '';
    document.getElementById('stylePadding').value = style.padding;
    document.getElementById('styleFontSize').value = style.fontSize;

    modal.classList.add('is-open');
  },

  closeStyleModal() {
    const modal = document.getElementById('styleModal');
    modal?.classList.remove('is-open');
    this.currentStyleBlockId = null;
  },

  applyStyle(key, value) {
    if (this.currentStyleBlockId) {
      this.updateBlockStyle(this.currentStyleBlockId, key, value);
    }
  },

  // --- RENDERING ---
  render() {
    this.renderSidebar();
    this.renderPreview();
  },

  renderSidebar() {
    const container = document.getElementById('blocksList');
    if (!container) return;

    if (this.state.blocks.length === 0) {
      container.innerHTML = `
        <div class="empty-state">
          <i class="ri-apps-2-line"></i>
          <p>Nenhum widget ainda</p>
          <span>Clique no + acima para adicionar</span>
        </div>
      `;
      return;
    }

    container.innerHTML = this.state.blocks.map(block => this.renderEditorCard(block)).join('');
  },

  renderEditorCard(block) {
    const visIcon = block.visible ? 'ri-eye-line' : 'ri-eye-off-line';
    const label = this.blockLabels[block.type] || block.type.toUpperCase();

    let content = this.renderEditorContent(block);

    return `
      <div class="editor-card" draggable="true" data-block-id="${block.id}">
        <div class="editor-card-header">
          <div style="display:flex; align-items:center; gap:8px;">
            <i class="ri-draggable drag-handle"></i>
            <span class="editor-type-badge">${label}</span>
          </div>
          <div class="editor-card-actions">
            <button class="btn-icon" onclick="HUB.openStyleModal(${block.id})" title="Customizar">
              <i class="ri-palette-line"></i>
            </button>
            <button class="btn-icon" onclick="HUB.toggleBlockVisibility(${block.id})" title="Visibilidade">
              <i class="${visIcon}"></i>
            </button>
            <button class="btn-icon btn-danger" onclick="HUB.deleteBlock(${block.id})" title="Remover">
              <i class="ri-delete-bin-line"></i>
            </button>
          </div>
        </div>
        <div class="editor-card-body">
          ${content}
        </div>
      </div>
    `;
  },

  renderEditorContent(block) {
    const id = block.id;

    switch (block.type) {
      case 'title_block':
        return `
          <div class="field">
            <label class="label">Texto do Título</label>
            <input class="input" type="text" value="${this.esc(block.text)}"
              oninput="HUB.updateBlock(${id}, 'text', this.value)" />
          </div>
          <div class="field">
            <label class="label">Tamanho</label>
            <div class="btn-group">
              <button class="button is-small ${block.size === 'small' ? 'is-primary' : ''}"
                onclick="HUB.updateBlock(${id}, 'size', 'small')">P</button>
              <button class="button is-small ${block.size === 'medium' ? 'is-primary' : ''}"
                onclick="HUB.updateBlock(${id}, 'size', 'medium')">M</button>
              <button class="button is-small ${block.size === 'large' ? 'is-primary' : ''}"
                onclick="HUB.updateBlock(${id}, 'size', 'large')">G</button>
            </div>
          </div>
          <div class="field">
            <label class="label">Alinhamento</label>
            <div class="btn-group">
              <button class="button is-small ${block.align === 'left' ? 'is-primary' : ''}"
                onclick="HUB.updateBlock(${id}, 'align', 'left')"><i class="ri-align-left"></i></button>
              <button class="button is-small ${block.align === 'center' ? 'is-primary' : ''}"
                onclick="HUB.updateBlock(${id}, 'align', 'center')"><i class="ri-align-center"></i></button>
              <button class="button is-small ${block.align === 'right' ? 'is-primary' : ''}"
                onclick="HUB.updateBlock(${id}, 'align', 'right')"><i class="ri-align-right"></i></button>
            </div>
          </div>
        `;

      case 'paragraph_block':
        return `
          <div class="field">
            <label class="label">Texto</label>
            <textarea class="textarea" rows="3"
              oninput="HUB.updateBlock(${id}, 'text', this.value)">${this.esc(block.text)}</textarea>
          </div>
          <div class="field">
            <label class="label">Alinhamento</label>
            <div class="btn-group">
              <button class="button is-small ${block.align === 'left' ? 'is-primary' : ''}"
                onclick="HUB.updateBlock(${id}, 'align', 'left')"><i class="ri-align-left"></i></button>
              <button class="button is-small ${block.align === 'center' ? 'is-primary' : ''}"
                onclick="HUB.updateBlock(${id}, 'align', 'center')"><i class="ri-align-center"></i></button>
              <button class="button is-small ${block.align === 'right' ? 'is-primary' : ''}"
                onclick="HUB.updateBlock(${id}, 'align', 'right')"><i class="ri-align-right"></i></button>
            </div>
          </div>
        `;

      case 'bio_block':
        return `
          <div class="field">
            <label class="label">Bio</label>
            <textarea class="textarea" rows="3"
              oninput="HUB.updateBlock(${id}, 'text', this.value)">${this.esc(block.text)}</textarea>
          </div>
          <div class="field">
            <label class="label">Alinhamento</label>
            <div class="btn-group">
              <button class="button is-small ${block.align === 'left' ? 'is-primary' : ''}"
                onclick="HUB.updateBlock(${id}, 'align', 'left')"><i class="ri-align-left"></i></button>
              <button class="button is-small ${block.align === 'center' ? 'is-primary' : ''}"
                onclick="HUB.updateBlock(${id}, 'align', 'center')"><i class="ri-align-center"></i></button>
              <button class="button is-small ${block.align === 'right' ? 'is-primary' : ''}"
                onclick="HUB.updateBlock(${id}, 'align', 'right')"><i class="ri-align-right"></i></button>
            </div>
          </div>
        `;

      case 'card_simple':
        return `
          <div class="field">
            <label class="label">Título</label>
            <input class="input" type="text" value="${this.esc(block.title)}"
              oninput="HUB.updateBlock(${id}, 'title', this.value)" />
          </div>
          <div class="field">
            <label class="label">URL</label>
            <input class="input" type="url" value="${this.esc(block.url)}"
              oninput="HUB.updateBlock(${id}, 'url', this.value)" />
          </div>
        `;

      case 'card_icon':
        return `
          <div class="field">
            <label class="label">Ícone</label>
            <button class="button is-light is-fullwidth" onclick="HUB.openIconPicker(${id})">
              <i class="${block.icon} mr-2"></i> Trocar Ícone
            </button>
          </div>
          <div class="field">
            <label class="label">Posição</label>
            <div class="btn-group">
              <button class="button is-small ${block.iconPosition === 'left' ? 'is-primary' : ''}"
                onclick="HUB.updateBlock(${id}, 'iconPosition', 'left')">Esquerda</button>
              <button class="button is-small ${block.iconPosition === 'right' ? 'is-primary' : ''}"
                onclick="HUB.updateBlock(${id}, 'iconPosition', 'right')">Direita</button>
            </div>
          </div>
          <div class="field">
            <label class="label">Título</label>
            <input class="input" type="text" value="${this.esc(block.title)}"
              oninput="HUB.updateBlock(${id}, 'title', this.value)" />
          </div>
          <div class="field">
            <label class="label">URL</label>
            <input class="input" type="url" value="${this.esc(block.url)}"
              oninput="HUB.updateBlock(${id}, 'url', this.value)" />
          </div>
        `;

      case 'image_link':
      case 'image_overlay':
        return `
          <div class="field">
            <label class="label">URL da Imagem</label>
            <input class="input" type="url" value="${this.esc(block.imageUrl)}"
              oninput="HUB.updateBlock(${id}, 'imageUrl', this.value)" />
          </div>
          ${block.type === 'image_overlay' ? `
          <div class="field">
            <label class="label">Título</label>
            <input class="input" type="text" value="${this.esc(block.title || '')}"
              oninput="HUB.updateBlock(${id}, 'title', this.value)" />
          </div>
          ` : ''}
          <div class="field">
            <label class="label">URL destino</label>
            <input class="input" type="url" value="${this.esc(block.url)}"
              oninput="HUB.updateBlock(${id}, 'url', this.value)" />
          </div>
        `;

      case 'event_internal':
        const evtOptions = this.eventsCache.map(e =>
          `<option value="${e.id}" ${block.eventId == e.id ? 'selected' : ''}>${this.esc(e.title)}</option>`
        ).join('');
        return `
          <div class="field">
            <label class="label">Evento Apollo</label>
            <select class="input" onchange="HUB.updateBlock(${id}, 'eventId', this.value)">
              <option value="0">Selecione...</option>
              ${evtOptions}
            </select>
          </div>
        `;

      case 'event_external':
        return `
          <div class="columns is-mobile">
            <div class="column">
              <label class="label">Dia</label>
              <input class="input" type="text" value="${this.esc(block.day)}"
                oninput="HUB.updateBlock(${id}, 'day', this.value)" maxlength="2" />
            </div>
            <div class="column">
              <label class="label">Mês</label>
              <input class="input" type="text" value="${this.esc(block.month)}"
                oninput="HUB.updateBlock(${id}, 'month', this.value)" maxlength="3" />
            </div>
          </div>
          <div class="field">
            <label class="label">Título</label>
            <input class="input" type="text" value="${this.esc(block.title)}"
              oninput="HUB.updateBlock(${id}, 'title', this.value)" />
          </div>
          <div class="field">
            <label class="label">URL</label>
            <input class="input" type="url" value="${this.esc(block.url)}"
              oninput="HUB.updateBlock(${id}, 'url', this.value)" />
          </div>
        `;

      case 'events_interested':
        return `
          <div class="field">
            <label class="label">Quantidade</label>
            <input class="input" type="number" min="1" max="10" value="${block.count || 3}"
              oninput="HUB.updateBlock(${id}, 'count', parseInt(this.value))" />
          </div>
          <p class="help">Mostra eventos que você marcou interesse.</p>
        `;

      case 'youtube':
        return `
          <div class="field">
            <label class="label">URL do YouTube</label>
            <input class="input" type="url" value="${this.esc(block.url)}"
              oninput="HUB.updateBlock(${id}, 'url', this.value); HUB.parseYouTube(${id}, this.value)" />
          </div>
          <p class="help">Cole o link do vídeo (ex: youtube.com/watch?v=...)</p>
        `;

      case 'soundcloud':
        return `
          <div class="field">
            <label class="label">URL do SoundCloud</label>
            <input class="input" type="url" value="${this.esc(block.trackUrl)}"
              oninput="HUB.updateBlock(${id}, 'trackUrl', this.value)" />
          </div>
        `;

      case 'spotify':
        return `
          <div class="field">
            <label class="label">URL do Spotify</label>
            <input class="input" type="url" value="${this.esc(block.playlistUrl)}"
              oninput="HUB.updateBlock(${id}, 'playlistUrl', this.value); HUB.parseSpotify(${id}, this.value)" />
          </div>
        `;

      case 'testimonials':
        return `
          <p class="help">${(block.items || []).length} depoimento(s)</p>
          <button class="button is-light is-small" onclick="HUB.openTestimonialEditor(${id})">
            <i class="ri-edit-line mr-2"></i>Editar Depoimentos
          </button>
        `;

      case 'rating_stars':
        return `
          <div class="field">
            <label class="label">Avaliação (0-5)</label>
            <input class="input" type="number" min="0" max="5" step="0.5" value="${block.value}"
              oninput="HUB.updateBlock(${id}, 'value', parseFloat(this.value))" />
          </div>
          <div class="field">
            <label class="label">Label</label>
            <input class="input" type="text" value="${this.esc(block.label)}"
              oninput="HUB.updateBlock(${id}, 'label', this.value)" />
          </div>
        `;

      case 'orkut_rate':
        return `
          <div class="orkut-rate-display">
            <div class="orkut-item">
              <span>😊 Confiável</span>
              <strong>${block.smile || 0}</strong>
            </div>
            <div class="orkut-item">
              <span>🧊 Legal</span>
              <strong>${block.ice || 0}</strong>
            </div>
            <div class="orkut-item">
              <span>❤️ Sexy</span>
              <strong>${block.heart || 0}</strong>
            </div>
          </div>
          <p class="help">Visitantes podem votar.</p>
        `;

      case 'social_links':
        return `
          <p class="help">${(block.links || []).length} rede(s) configurada(s)</p>
          <button class="button is-light is-small" onclick="HUB.openSocialEditor(${id})">
            <i class="ri-settings-line mr-2"></i>Configurar Redes
          </button>
        `;

      case 'share_page':
        return `
          <p class="help">Botões: ${(block.platforms || []).join(', ')}</p>
        `;

      case 'user_bubble':
        return `
          <div class="field">
            <label class="label">Tamanho</label>
            <select class="input" onchange="HUB.updateBlock(${id}, 'size', this.value)">
              <option value="small" ${block.size === 'small' ? 'selected' : ''}>Pequeno</option>
              <option value="medium" ${block.size === 'medium' ? 'selected' : ''}>Médio</option>
              <option value="large" ${block.size === 'large' ? 'selected' : ''}>Grande</option>
            </select>
          </div>
          <label class="checkbox-label">
            <input type="checkbox" ${block.showName ? 'checked' : ''}
              onchange="HUB.updateBlock(${id}, 'showName', this.checked)" />
            Mostrar nome
          </label>
        `;

      case 'user_coauthors':
        return `
          <p class="help">Mostra seus co-autores de eventos.</p>
        `;

      case 'latest_news':
        return `
          <div class="field">
            <label class="label">Quantidade</label>
            <input class="input" type="number" min="1" max="10" value="${block.count || 3}"
              oninput="HUB.updateBlock(${id}, 'count', parseInt(this.value))" />
          </div>
        `;

      case 'marquee':
        return `
          <div class="field">
            <label class="label">Texto</label>
            <input class="input" type="text" value="${this.esc(block.text)}"
              oninput="HUB.updateBlock(${id}, 'text', this.value)" />
          </div>
          <div class="field">
            <label class="label">Velocidade</label>
            <select class="input" onchange="HUB.updateBlock(${id}, 'speed', this.value)">
              <option value="slow" ${block.speed === 'slow' ? 'selected' : ''}>Lento</option>
              <option value="normal" ${block.speed === 'normal' ? 'selected' : ''}>Normal</option>
              <option value="fast" ${block.speed === 'fast' ? 'selected' : ''}>Rápido</option>
            </select>
          </div>
        `;

      case 'text_block':
        return `
          <div class="field">
            <label class="label">Conteúdo</label>
            <textarea class="textarea" rows="3"
              oninput="HUB.updateBlock(${id}, 'content', this.value)">${this.esc(block.content)}</textarea>
          </div>
        `;

      case 'divider':
        return `
          <div class="field">
            <label class="label">Estilo</label>
            <select class="input" onchange="HUB.updateBlock(${id}, 'style', this.value)">
              <option value="solid" ${block.style === 'solid' ? 'selected' : ''}>Sólido</option>
              <option value="dashed" ${block.style === 'dashed' ? 'selected' : ''}>Tracejado</option>
              <option value="dotted" ${block.style === 'dotted' ? 'selected' : ''}>Pontilhado</option>
            </select>
          </div>
        `;

      default:
        return `<p class="help">Configuração não disponível.</p>`;
    }
  },

  // --- PREVIEW RENDERING ---
  renderPreview() {
    const { profile } = this.state;

    // Handle avatar rendering based on style
    const avatarContainer = document.getElementById('previewAvatarContainer');
    if (avatarContainer) {
      const avatarUrl = profile.avatar || 'https://assets.apollo.rio.br/i/default-avatar.png';

      if (profile.avatarStyle === 'hero') {
        // Hero animated avatar
        avatarContainer.innerHTML = `
          <div class="avatar-hero">
            <div class="avatar-hero-box">
              <div class="avatar-hero-spin">
                <div class="avatar-hero-shape">
                  <div class="avatar-hero-image" style="background-image:url(${this.esc(avatarUrl)})"></div>
                </div>
              </div>
            </div>
          </div>
        `;
      } else {
        // Rounded avatar with optional border
        const borderStyle = profile.avatarBorder
          ? `border: ${profile.avatarBorderWidth || 4}px solid ${profile.avatarBorderColor || '#ffffff'};`
          : '';
        avatarContainer.innerHTML = `
          <img
            id="previewAvatar"
            class="p-avatar"
            src="${this.esc(avatarUrl)}"
            alt="Avatar"
            style="${borderStyle}"
          />
        `;
      }
    }

    const nameEl = document.getElementById('previewName');
    const bioEl = document.getElementById('previewBio');
    const bgEl = document.getElementById('previewBgLayer');
    const textureEl = document.getElementById('textureOverlay');

    if (nameEl) nameEl.textContent = profile.name || '@seunome';
    if (bioEl) bioEl.textContent = profile.bio || 'Sua bio aparece aqui';
    if (bgEl) bgEl.style.backgroundImage = profile.bg ? `url(${profile.bg})` : 'none';

    if (textureEl) {
      const textures = {
        none: '',
        dots: 'https://cdn.apollo.rio.br/img/textures/dots.png',
        waves: 'https://cdn.apollo.rio.br/img/textures/waves.png',
        grid: 'https://cdn.apollo.rio.br/img/textures/grid.png',
        noise: 'https://cdn.apollo.rio.br/img/textures/noise.png',
        confetti: 'https://cdn.apollo.rio.br/img/textures/confetti.png'
      };
      textureEl.style.backgroundImage = textures[profile.texture] ? `url(${textures[profile.texture]})` : 'none';
    }

    const blocksContainer = document.getElementById('previewBlocks');
    if (!blocksContainer) return;

    blocksContainer.innerHTML = this.state.blocks
      .filter(b => b.visible)
      .map(b => this.renderPreviewBlock(b))
      .join('');
  },

  renderPreviewBlock(block) {
    const style = block.style || {};
    const customStyle = this.buildCustomStyle(style);

    switch (block.type) {
      case 'title_block':
        const titleSize = {
          small: '1rem',
          medium: '1.25rem',
          large: '1.5rem'
        }[block.size] || '1.25rem';
        return `
          <div class="p-block p-title-block" style="text-align:${block.align || 'center'}; font-size:${titleSize}; font-weight:700; ${customStyle}">
            ${this.esc(block.text)}
          </div>
        `;

      case 'paragraph_block':
        return `
          <div class="p-block p-paragraph-block" style="text-align:${block.align || 'center'}; ${customStyle}">
            ${this.esc(block.text)}
          </div>
        `;

      case 'bio_block':
        return `
          <div class="p-block p-bio-block" style="text-align:${block.align || 'center'}; font-style:italic; color:var(--muted-foreground); ${customStyle}">
            ${this.esc(block.text)}
          </div>
        `;

      case 'card_simple':
        return `
          <a class="p-block p-card-simple" href="${this.esc(block.url)}" target="_blank" style="${customStyle}">
            <span class="p-card-title">${this.esc(block.title)}</span>
            <i class="ri-arrow-right-line"></i>
          </a>
        `;

      case 'card_icon':
        const iconLeft = block.iconPosition !== 'right';
        return `
          <a class="p-block p-card-icon ${iconLeft ? '' : 'icon-right'}" href="${this.esc(block.url)}" target="_blank" style="${customStyle}">
            ${iconLeft ? `<div class="p-icon-box"><i class="${block.icon}"></i></div>` : ''}
            <span class="p-card-title">${this.esc(block.title)}</span>
            ${!iconLeft ? `<div class="p-icon-box"><i class="${block.icon}"></i></div>` : ''}
          </a>
        `;

      case 'image_link':
        return `
          <a class="p-block p-image-link" href="${this.esc(block.url)}" target="_blank" style="${customStyle}">
            <img src="${this.esc(block.imageUrl)}" alt="${this.esc(block.alt || '')}" />
          </a>
        `;

      case 'image_overlay':
        return `
          <a class="p-block p-image-overlay" href="${this.esc(block.url)}" target="_blank" style="${customStyle}">
            <img src="${this.esc(block.imageUrl)}" alt="" />
            <div class="p-overlay-title">${this.esc(block.title)}</div>
          </a>
        `;

      case 'event_internal':
        const evt = this.eventsCache.find(e => e.id == block.eventId);
        if (!evt) return '';
        return `
          <a class="p-block p-event-internal" href="${this.esc(evt.url || '#')}" target="_blank" style="${customStyle}">
            <div class="p-event-thumb" style="background-image:url(${this.esc(evt.thumb || '')})"></div>
            <div class="p-event-info">
              <div class="p-event-date">${this.esc(evt.date || '')}</div>
              <div class="p-event-title">${this.esc(evt.title)}</div>
            </div>
          </a>
        `;

      case 'event_external':
        return `
          <a class="p-block p-event-external" href="${this.esc(block.url)}" target="_blank" style="${customStyle}">
            <div class="p-date-box">
              <div class="p-day">${this.esc(block.day)}</div>
              <div class="p-month">${this.esc(block.month)}</div>
            </div>
            <div class="p-event-title">${this.esc(block.title)}</div>
          </a>
        `;

      case 'events_interested':
        return `
          <div class="p-block p-events-interested" style="${customStyle}">
            <div class="p-section-title">Meus Interesses</div>
            <div class="p-events-placeholder">Carregando eventos...</div>
          </div>
        `;

      case 'youtube':
        const ytId = this.extractYouTubeId(block.url);
        return `
          <div class="p-block p-youtube" style="${customStyle}">
            <iframe src="https://www.youtube.com/embed/${ytId}" frameborder="0" allowfullscreen></iframe>
          </div>
        `;

      case 'soundcloud':
        return `
          <div class="p-block p-soundcloud" style="${customStyle}">
            <iframe width="100%" height="166" scrolling="no" frameborder="no"
              src="https://w.soundcloud.com/player/?url=${encodeURIComponent(block.trackUrl)}&color=%23ff5500&auto_play=false"></iframe>
          </div>
        `;

      case 'spotify':
        const spotifyUri = this.extractSpotifyUri(block.playlistUrl);
        return `
          <div class="p-block p-spotify" style="${customStyle}">
            <iframe src="https://open.spotify.com/embed/${spotifyUri}" width="100%" height="152" frameborder="0"
              allow="encrypted-media"></iframe>
          </div>
        `;

      case 'testimonials':
        const items = block.items || [];
        return `
          <div class="p-block p-testimonials" style="${customStyle}">
            <div class="p-section-title">Depoimentos</div>
            ${items.map(t => `
              <div class="p-testimonial">
                <div class="p-testimonial-stars">${'★'.repeat(t.rating)}${'☆'.repeat(5 - t.rating)}</div>
                <div class="p-testimonial-text">"${this.esc(t.text)}"</div>
                <div class="p-testimonial-author">— ${this.esc(t.author)}</div>
              </div>
            `).join('')}
          </div>
        `;

      case 'rating_stars':
        const fullStars = Math.floor(block.value);
        const halfStar = block.value % 1 >= 0.5;
        return `
          <div class="p-block p-rating" style="${customStyle}">
            <div class="p-rating-label">${this.esc(block.label)}</div>
            <div class="p-rating-stars">
              ${'★'.repeat(fullStars)}${halfStar ? '½' : ''}${'☆'.repeat(5 - fullStars - (halfStar ? 1 : 0))}
            </div>
            <div class="p-rating-value">${block.value}/5</div>
          </div>
        `;

      case 'orkut_rate':
        return `
          <div class="p-block p-orkut-rate" style="${customStyle}">
            <div class="p-orkut-item" onclick="HUB.voteOrkut(${block.id}, 'smile')">
              <div class="p-orkut-icons">😊😊😊</div>
              <div class="p-orkut-count">${block.smile || 0}</div>
              <div class="p-orkut-label">Confiável</div>
            </div>
            <div class="p-orkut-item" onclick="HUB.voteOrkut(${block.id}, 'ice')">
              <div class="p-orkut-icons">🧊🧊🧊</div>
              <div class="p-orkut-count">${block.ice || 0}</div>
              <div class="p-orkut-label">Legal</div>
            </div>
            <div class="p-orkut-item" onclick="HUB.voteOrkut(${block.id}, 'heart')">
              <div class="p-orkut-icons">❤️❤️❤️</div>
              <div class="p-orkut-count">${block.heart || 0}</div>
              <div class="p-orkut-label">Sexy</div>
            </div>
          </div>
        `;

      case 'social_links':
        const links = block.links || [];
        return `
          <div class="p-block p-social-links" style="${customStyle}">
            ${links.map(l => `
              <a class="p-social-icon" href="${this.esc(l.url)}" target="_blank">
                <i class="${l.icon}"></i>
              </a>
            `).join('')}
          </div>
        `;

      case 'share_page':
        return `
          <div class="p-block p-share" style="${customStyle}">
            <div class="p-share-label">Compartilhar</div>
            <div class="p-share-buttons">
              <button onclick="HUB.share('whatsapp')"><i class="ri-whatsapp-line"></i></button>
              <button onclick="HUB.share('telegram')"><i class="ri-telegram-line"></i></button>
              <button onclick="HUB.share('twitter')"><i class="ri-twitter-x-line"></i></button>
              <button onclick="HUB.share('facebook')"><i class="ri-facebook-line"></i></button>
              <button onclick="HUB.share('copy')"><i class="ri-file-copy-line"></i></button>
            </div>
          </div>
        `;

      case 'user_bubble':
        const sizeClass = `bubble-${block.size || 'medium'}`;
        return `
          <div class="p-block p-user-bubble ${sizeClass}" style="${customStyle}">
            <img src="${this.state.profile.avatar || 'https://assets.apollo.rio.br/i/default-avatar.png'}" />
            ${block.showName ? `<span>${this.esc(this.state.profile.name)}</span>` : ''}
          </div>
        `;

      case 'user_coauthors':
        return `
          <div class="p-block p-coauthors" style="${customStyle}">
            <div class="p-section-title">Parceiros</div>
            <div class="p-coauthors-list">Carregando...</div>
          </div>
        `;

      case 'latest_news':
        return `
          <div class="p-block p-latest-news" style="${customStyle}">
            <div class="p-section-title">Últimas Notícias</div>
            <div class="p-news-list">Carregando...</div>
          </div>
        `;

      case 'marquee':
        const speedClass = `marquee-${block.speed || 'normal'}`;
        return `
          <div class="p-block p-marquee ${speedClass}" style="${customStyle}">
            <div class="p-marquee-track">
              <span class="p-marquee-text">${this.esc(block.text)}</span>
            </div>
          </div>
        `;

      case 'text_block':
        return `
          <div class="p-block p-text" style="${customStyle}">
            ${this.esc(block.content)}
          </div>
        `;

      case 'divider':
        return `
          <hr class="p-divider" style="border-style:${block.style || 'solid'}; ${customStyle}" />
        `;

      default:
        return '';
    }
  },

  buildCustomStyle(style) {
    let css = '';
    if (style.borderRadius) css += `border-radius:${style.borderRadius}px;`;
    if (style.opacity && style.opacity < 100) css += `opacity:${style.opacity / 100};`;
    if (style.color) css += `color:${style.color};`;
    if (style.bgGradient) {
      css += `background:${style.bgGradient};`;
    } else if (style.bgImage) {
      css += `background-image:url(${style.bgImage}); background-size:cover;`;
    } else if (style.bgColor) {
      css += `background-color:${style.bgColor};`;
    }
    if (style.padding) css += `padding:${style.padding}px;`;
    if (style.fontSize) css += `font-size:${style.fontSize}px;`;
    return css;
  },

  // --- HELPERS ---
  extractYouTubeId(url) {
    const match = url.match(/(?:youtube\.com\/watch\?v=|youtu\.be\/)([^&\s]+)/);
    return match ? match[1] : '';
  },

  extractSpotifyUri(url) {
    const match = url.match(/spotify\.com\/(track|album|playlist)\/([a-zA-Z0-9]+)/);
    return match ? `${match[1]}/${match[2]}` : '';
  },

  parseYouTube(id, url) {
    const videoId = this.extractYouTubeId(url);
    this.updateBlock(id, 'videoId', videoId);
  },

  parseSpotify(id, url) {
    const uri = this.extractSpotifyUri(url);
    this.updateBlock(id, 'embedUri', uri);
  },

  voteOrkut(blockId, type) {
    const block = this.state.blocks.find(b => b.id === blockId);
    if (block) {
      block[type] = (block[type] || 0) + 1;
      this.render();
      // In real app, save vote to server
    }
  },

  share(platform) {
    const url = encodeURIComponent(window.location.href);
    const text = encodeURIComponent(document.title);
    const links = {
      whatsapp: `https://wa.me/?text=${text}%20${url}`,
      telegram: `https://t.me/share/url?url=${url}&text=${text}`,
      twitter: `https://twitter.com/intent/tweet?url=${url}&text=${text}`,
      facebook: `https://www.facebook.com/sharer/sharer.php?u=${url}`,
      copy: null
    };

    if (platform === 'copy') {
      navigator.clipboard.writeText(window.location.href);
      this.showToast('Link copiado!', 'success');
    } else if (links[platform]) {
      window.open(links[platform], '_blank');
    }
  },

  // --- TAB SWITCHING ---
  setupTabSwitching() {
    const navItems = document.querySelectorAll('.nav-item');
    navItems.forEach(item => {
      item.addEventListener('click', () => {
        const tab = item.dataset.tab;
        navItems.forEach(n => n.classList.remove('is-active'));
        item.classList.add('is-active');
        document.querySelectorAll('.sidebar-tab-content').forEach(content => {
          content.classList.add('is-hidden');
        });
        const target = document.querySelector(`.sidebar-tab-content[data-tab="${tab}"]`);
        if (target) target.classList.remove('is-hidden');
      });
    });
  },

  // --- DRAG AND DROP ---
  setupDragAndDrop() {
    const container = document.getElementById('blocksList');
    if (!container) return;

    container.addEventListener('dragstart', (e) => {
      if (e.target.classList.contains('editor-card')) {
        e.target.classList.add('dragging');
        e.dataTransfer.effectAllowed = 'move';
      }
    });

    container.addEventListener('dragend', (e) => {
      if (e.target.classList.contains('editor-card')) {
        e.target.classList.remove('dragging');
      }
    });

    container.addEventListener('dragover', (e) => {
      e.preventDefault();
      const dragging = container.querySelector('.dragging');
      if (!dragging) return;

      const afterElement = this.getDragAfterElement(container, e.clientY);
      if (afterElement == null) {
        container.appendChild(dragging);
      } else {
        container.insertBefore(dragging, afterElement);
      }
    });

    container.addEventListener('drop', (e) => {
      e.preventDefault();
      this.reorderBlocks();
    });
  },

  getDragAfterElement(container, y) {
    const draggableElements = [...container.querySelectorAll('.editor-card:not(.dragging)')];
    return draggableElements.reduce((closest, child) => {
      const box = child.getBoundingClientRect();
      const offset = y - box.top - box.height / 2;
      if (offset < 0 && offset > closest.offset) {
        return { offset: offset, element: child };
      } else {
        return closest;
      }
    }, { offset: Number.NEGATIVE_INFINITY }).element;
  },

  reorderBlocks() {
    const cards = document.querySelectorAll('#blocksList .editor-card');
    const newOrder = [];
    cards.forEach(card => {
      const id = parseInt(card.dataset.blockId);
      const block = this.state.blocks.find(b => b.id === id);
      if (block) newOrder.push(block);
    });
    this.state.blocks = newOrder;
    this.render();
    this.saveState();
  },

  // --- ICON PICKER ---
  openIconPicker(blockId) {
    this.currentBlockId = blockId;
    const modal = document.getElementById('iconPickerModal');
    if (modal) modal.classList.add('is-open');
  },

  closeIconPicker() {
    const modal = document.getElementById('iconPickerModal');
    if (modal) modal.classList.remove('is-open');
    this.currentBlockId = null;
  },

  selectIcon(iconClass) {
    if (this.currentBlockId) {
      this.updateBlock(this.currentBlockId, 'icon', iconClass);
    }
    this.closeIconPicker();
  },

  // --- MOBILE PREVIEW ---
  setupMobilePreview() {
    const btn = document.getElementById('mobilePreviewBtn');
    if (!btn) return;
    btn.addEventListener('click', () => {
      document.body.classList.toggle('mobile-preview-mode');
    });
  },

  // --- TEXTURE NAVIGATION ---
  setupTextureNavigation() {
    const prevBtn = document.getElementById('texturePrevBtn');
    const nextBtn = document.getElementById('textureNextBtn');
    if (!prevBtn || !nextBtn) return;

    const textures = ['none', 'dots', 'waves', 'grid', 'noise', 'confetti'];

    prevBtn.addEventListener('click', () => {
      const current = this.state.profile.texture || 'none';
      const idx = textures.indexOf(current);
      const prev = textures[(idx - 1 + textures.length) % textures.length];
      this.updateProfile('texture', prev);
    });

    nextBtn.addEventListener('click', () => {
      const current = this.state.profile.texture || 'none';
      const idx = textures.indexOf(current);
      const next = textures[(idx + 1) % textures.length];
      this.updateProfile('texture', next);
    });
  },

  // --- UTILITIES ---
  esc(str) {
    if (typeof str !== 'string') return '';
    return str
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  },

  // --- SOCIAL LINKS EDITOR ---
  openSocialEditor(blockId) {
    this.currentBlockId = blockId;
    const block = this.state.blocks.find(b => b.id === blockId);
    if (!block) return;

    const modal = document.getElementById('socialEditorModal');
    const container = document.getElementById('socialLinksContainer');
    if (!modal || !container) return;

    const links = block.links || [];
    container.innerHTML = links.map((l, i) => `
      <div class="social-link-row" data-index="${i}">
        <div class="field">
          <label class="label">Plataforma</label>
          <select class="input social-platform" data-index="${i}">
            <option value="instagram" ${l.platform === 'instagram' ? 'selected' : ''}>Instagram</option>
            <option value="twitter" ${l.platform === 'twitter' ? 'selected' : ''}>Twitter/X</option>
            <option value="facebook" ${l.platform === 'facebook' ? 'selected' : ''}>Facebook</option>
            <option value="youtube" ${l.platform === 'youtube' ? 'selected' : ''}>YouTube</option>
            <option value="tiktok" ${l.platform === 'tiktok' ? 'selected' : ''}>TikTok</option>
            <option value="linkedin" ${l.platform === 'linkedin' ? 'selected' : ''}>LinkedIn</option>
            <option value="whatsapp" ${l.platform === 'whatsapp' ? 'selected' : ''}>WhatsApp</option>
            <option value="telegram" ${l.platform === 'telegram' ? 'selected' : ''}>Telegram</option>
            <option value="spotify" ${l.platform === 'spotify' ? 'selected' : ''}>Spotify</option>
            <option value="soundcloud" ${l.platform === 'soundcloud' ? 'selected' : ''}>SoundCloud</option>
          </select>
        </div>
        <div class="field">
          <label class="label">URL</label>
          <input class="input social-url" type="url" value="${this.esc(l.url)}" data-index="${i}" />
        </div>
        <button class="button is-small btn-danger" onclick="HUB.removeSocialLink(${i})">
          <i class="ri-delete-bin-line"></i>
        </button>
      </div>
    `).join('') || '<p class="help">Nenhuma rede adicionada.</p>';

    modal.classList.add('is-open');
  },

  closeSocialEditor() {
    document.getElementById('socialEditorModal')?.classList.remove('is-open');
    this.currentBlockId = null;
  },

  addSocialLink() {
    if (!this.currentBlockId) return;
    const block = this.state.blocks.find(b => b.id === this.currentBlockId);
    if (!block) return;

    if (!block.links) block.links = [];
    block.links.push({ platform: 'instagram', url: '#', icon: 'ri-instagram-line' });
    this.openSocialEditor(this.currentBlockId);
  },

  removeSocialLink(index) {
    if (!this.currentBlockId) return;
    const block = this.state.blocks.find(b => b.id === this.currentBlockId);
    if (!block || !block.links) return;

    block.links.splice(index, 1);
    this.openSocialEditor(this.currentBlockId);
  },

  saveSocialLinks() {
    if (!this.currentBlockId) return;
    const block = this.state.blocks.find(b => b.id === this.currentBlockId);
    if (!block) return;

    const platformIcons = {
      instagram: 'ri-instagram-line',
      twitter: 'ri-twitter-x-line',
      facebook: 'ri-facebook-line',
      youtube: 'ri-youtube-line',
      tiktok: 'ri-tiktok-line',
      linkedin: 'ri-linkedin-line',
      whatsapp: 'ri-whatsapp-line',
      telegram: 'ri-telegram-line',
      spotify: 'ri-spotify-line',
      soundcloud: 'ri-soundcloud-line'
    };

    const newLinks = [];
    document.querySelectorAll('#socialLinksContainer .social-link-row').forEach(row => {
      const platform = row.querySelector('.social-platform').value;
      const url = row.querySelector('.social-url').value;
      newLinks.push({
        platform,
        url,
        icon: platformIcons[platform] || 'ri-link'
      });
    });

    block.links = newLinks;
    this.closeSocialEditor();
    this.render();
    this.saveState();
  },

  // --- TESTIMONIALS EDITOR ---
  openTestimonialEditor(blockId) {
    this.currentBlockId = blockId;
    const block = this.state.blocks.find(b => b.id === blockId);
    if (!block) return;

    const modal = document.getElementById('testimonialEditorModal');
    const container = document.getElementById('testimonialsContainer');
    if (!modal || !container) return;

    const items = block.items || [];
    container.innerHTML = items.map((t, i) => `
      <div class="testimonial-row" style="padding:1rem; background:var(--secondary); border-radius:8px; margin-bottom:0.75rem;">
        <div class="columns is-mobile" style="margin-bottom:0.5rem;">
          <div class="column">
            <label class="label">Autor</label>
            <input class="input testimonial-author" type="text" value="${this.esc(t.author)}" data-index="${i}" />
          </div>
          <div class="column" style="flex:0 0 80px;">
            <label class="label">Nota</label>
            <input class="input testimonial-rating" type="number" min="1" max="5" value="${t.rating || 5}" data-index="${i}" />
          </div>
        </div>
        <div class="field">
          <label class="label">Depoimento</label>
          <textarea class="textarea testimonial-text" rows="2" data-index="${i}">${this.esc(t.text)}</textarea>
        </div>
        <button class="button is-small btn-danger" style="margin-top:0.5rem;" onclick="HUB.removeTestimonial(${i})">
          <i class="ri-delete-bin-line mr-2"></i>Remover
        </button>
      </div>
    `).join('') || '<p class="help">Nenhum depoimento adicionado.</p>';

    modal.classList.add('is-open');
  },

  closeTestimonialEditor() {
    document.getElementById('testimonialEditorModal')?.classList.remove('is-open');
    this.currentBlockId = null;
  },

  addTestimonial() {
    if (!this.currentBlockId) return;
    const block = this.state.blocks.find(b => b.id === this.currentBlockId);
    if (!block) return;

    if (!block.items) block.items = [];
    block.items.push({ author: 'Novo', avatar: '', text: 'Escreva aqui...', rating: 5 });
    this.openTestimonialEditor(this.currentBlockId);
  },

  removeTestimonial(index) {
    if (!this.currentBlockId) return;
    const block = this.state.blocks.find(b => b.id === this.currentBlockId);
    if (!block || !block.items) return;

    block.items.splice(index, 1);
    this.openTestimonialEditor(this.currentBlockId);
  },

  saveTestimonials() {
    if (!this.currentBlockId) return;
    const block = this.state.blocks.find(b => b.id === this.currentBlockId);
    if (!block) return;

    const newItems = [];
    document.querySelectorAll('#testimonialsContainer .testimonial-row').forEach(row => {
      const author = row.querySelector('.testimonial-author').value;
      const text = row.querySelector('.testimonial-text').value;
      const rating = parseInt(row.querySelector('.testimonial-rating').value) || 5;
      newItems.push({ author, avatar: '', text, rating });
    });

    block.items = newItems;
    this.closeTestimonialEditor();
    this.render();
    this.saveState();
  }
};

// Initialize on load
document.addEventListener('DOMContentLoaded', () => {
  HUB.init();
});
