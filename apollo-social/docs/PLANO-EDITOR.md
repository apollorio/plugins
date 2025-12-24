# Apollo Plano Editor - Documentação

## Visão Geral

O Apollo Plano Editor é um editor de imagens baseado em Fabric.js que permite criar designs personalizados para perfis de usuário, posts e outros elementos da plataforma Apollo.

## Estrutura de Arquivos

```
apollo-social/
├── templates/
│   └── plano/
│       └── editor.php          # Template principal do editor
├── assets/
│   ├── js/
│   │   ├── canvas-plano.js     # Lógica principal do editor
│   │   └── CanvasTools.js      # Módulo modular de ferramentas
│   ├── css/
│   │   └── canvas-plano.css    # Estilos do editor
│   └── img/
│       ├── textures/            # 191 texturas PNG para mix-blend
│       └── stickers/            # Adesivos (vazio por enquanto)
├── includes/
│   ├── class-plano-editor-assets.php  # Gerenciamento de assets
│   └── class-plano-save-handler.php   # Handler de salvamento
└── src/
    └── API/
        ├── Textures.php         # API REST para texturas
        └── Posts.php            # API REST para posts
```

## Rotas

O editor está disponível nas seguintes rotas:

- `/studio/` - Editor standalone
- `/plano/` - Alias para o editor
- `/id/{user_id}/?action=edit` - Editor integrado na página do usuário

## Bibliotecas

### Biblioteca de Fundos (BG)

- **Gradiente**: Gradientes CSS predefinidos
- **Animação**: Upload de GIF/MP4 para background animado
- **Vídeo URL**: URL do YouTube (armazenado, não embedado)
- **Widgets**: Snippets HTML de `templates/widgets/`

### Biblioteca de Elementos

- **Imagem**: Upload via WordPress Media Library
- **Caixa**: Formas predefinidas (Rect, Circle, Polygon) ou SVG
- **Texto**: Ferramenta de texto com controles avançados
- **Adesivos**: Biblioteca vazia (reservada para futuro)

### Biblioteca de Posts

- **Anúncios (anuncio)**: Busca por ID via meta_key
- **Eventos (event_listing)**: Busca por post ID
- **DJ (event_dj)**: Busca por post ID
- **Local (event_local)**: Busca por post ID

### Biblioteca de Efeitos

- **Cor Customizável**: Sliders para Hue, Saturation, Brightness, Contrast
- **Animação .mov**: Upload de vídeo curto (loop)
- **Movimento Parcial**: Simulação de parallax
- **Padrão Listrado**: Gradiente CSS com espaçamento progressivo
- **Textura**: 191 PNGs para mix-blend

### Biblioteca de Vídeo

- **YouTube**: URL do YouTube (validação via wp_oembed_get)
- **Instagram**: URL de Stories/Reels (tentativa de embed via oEmbed)

## API REST

### Endpoints

#### GET `/wp-json/apollo/v1/textures`

Retorna lista de todas as texturas disponíveis.

**Resposta:**
```json
{
  "textures": ["texture1.png", "texture2.png", ...],
  "count": 191,
  "base_url": "https://example.com/wp-content/plugins/apollo-social/assets/img/textures/"
}
```

#### GET `/wp-json/apollo/v1/textures/search?q=termo`

Busca texturas por nome de arquivo.

#### GET `/wp-json/apollo/v1/stickers`

Retorna lista de adesivos (vazio por enquanto).

#### GET `/wp-json/apollo/v1/posts/{type}/{id}`

Retorna dados de um post específico.

**Tipos suportados:**
- `anuncio` - Anúncios classificados
- `event_listing` - Eventos
- `event_dj` - DJs
- `event_local` - Locais

**Autenticação:** Requer usuário logado

### AJAX

#### POST `admin-ajax.php?action=apollo_save_canvas`

Salva canvas como attachment WordPress.

**Parâmetros:**
- `nonce`: Nonce do WordPress REST API
- `data_url`: Data URL da imagem (base64)

**Resposta:**
```json
{
  "success": true,
  "data": {
    "attachment_id": 123,
    "attachment_url": "https://example.com/wp-content/uploads/...",
    "message": "Canvas salvo com sucesso!"
  }
}
```

## Filtros Fabric.js

O editor suporta os seguintes filtros:

- **Brightness**: -1 a 1
- **Contrast**: -1 a 1
- **Saturation**: -1 a 1
- **HueRotation**: -180 a 180 graus

### Presets

- **Warm**: Brilho +0.2, Saturação +0.3, Matiz +10
- **Cool**: Contraste +0.2, Matiz -10
- **B&W**: Saturação -1
- **Reset**: Todos os valores em 0

## Como Adicionar Novas Bibliotecas

1. Adicione o endpoint REST em `src/API/` se necessário
2. Adicione a tab no template `templates/plano/editor.php`
3. Implemente a função de carregamento em `assets/js/canvas-plano.js`
4. Adicione estilos em `assets/css/canvas-plano.css`

## Como Adicionar Novos Filtros

1. Adicione o controle no modal de filtros em `templates/plano/editor.php`
2. Implemente a aplicação do filtro em `assets/js/canvas-plano.js`
3. Use `fabric.Image.filters.{FilterName}` para criar o filtro

## Segurança

- Todos os inputs são sanitizados com `sanitize_text_field()`, `sanitize_key()`, `absint()`
- Todos os outputs são escapados com `esc_html()`, `esc_attr()`, `esc_url()`
- Nonces são verificados em todas as requisições AJAX
- REST API endpoints têm `permission_callback` adequado
- Strings são traduzíveis via `esc_html__()` e `__()`

## Assets Locais

Todos os assets são servidos localmente:

- Fabric.js 5.3.0 → `assets/js/fabric.min.js`
- html2canvas 1.4.1 → `assets/js/html2canvas.min.js`
- Sortable.js 1.15.0 → `assets/js/sortable.min.js`
- Remixicon 2.5.0 → `assets/fonts/remixicon.css` + fontes woff2

**Nenhuma dependência de CDN** - o editor funciona offline.

## Modularização

O módulo `CanvasTools.js` é reutilizável e pode ser usado em outros contextos:

```javascript
const tools = new CanvasTools({
  canvas: fabricCanvas,
  container: document.querySelector('.editor-container')
});
```

## Texturas vs Adesivos

- **Texturas**: 191 imagens PNG em `assets/img/textures/` - usadas para mix-blend mode
- **Adesivos**: Biblioteca vazia em `assets/img/stickers/` - reservada para adesivos futuros (papel, fita, etc.)

## Compatibilidade

- PHP 8.3+
- WordPress 6.0+
- Navegadores modernos (Chrome, Firefox, Safari, Edge)
- Design system Apollo (uni.css) - sem Tailwind

## Notas Importantes

- O editor não remove funcionalidades existentes - apenas adiciona
- Todas as classes usam prefixos Apollo (`ap-*`, `ario-*`)
- O editor é totalmente responsivo e funciona em mobile
- Export PNG suporta fundo transparente
- Save handler cria attachment WordPress automaticamente

