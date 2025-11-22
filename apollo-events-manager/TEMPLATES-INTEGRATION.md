# 🎨 Templates Tailwind Integrados - Apollo Events Manager

## ✅ FASE 1 CONCLUÍDA

Todos os 4 templates estáticos foram convertidos em shortcodes WordPress funcionais.

---

## 📋 Shortcodes Disponíveis

### 1. `[apollo_dj_profile dj_id="123"]`
**Template:** `templates/shortcode-dj-profile.php`  
**Origem:** PAGE-FOR-CPT DJ  
**Uso:** Exibe perfil completo de um DJ com player SoundCloud, bio, links e projetos.

**Atributos:**
- `dj_id` (opcional): ID do post do DJ. Se não fornecido, usa o post atual.

**Meta Keys Utilizadas:**
- `_dj_name` - Nome do DJ
- `_dj_tagline` - Tagline
- `_dj_roles` - Roles (DJ, Producer, etc)
- `_bio_excerpt` - Bio resumida
- `_bio_full` - Bio completa
- `_soundcloud_track` - URL do track SoundCloud
- `_track_title` - Título do track
- `_dj_projects` - Array ou string separada por vírgulas
- `_dj_music_links` - Array de links de música
- `_dj_social_links` - Array de links sociais
- `_dj_asset_links` - Array de links de assets
- `_mediakit_url` - URL do media kit
- `_more_platforms` - String com outras plataformas

**Assets Carregados:**
- Tailwind CSS (CDN)
- Motion One (animations)
- SoundCloud API
- Apollo Base.js
- UNI.css

---

### 2. `[apollo_user_dashboard]`
**Template:** `templates/shortcode-user-dashboard.php`  
**Origem:** PAGE-PRIVATE-PROFILE-PAGE-TAB  
**Uso:** Dashboard privado do usuário com tabs, estatísticas e eventos favoritos.

**Requer:** Usuário logado (redireciona para login se não autenticado)

**Dados Exibidos:**
- Perfil do usuário (avatar, nome, bio, localização)
- Estatísticas (eventos criados, favoritados, co-autorados)
- Tab: Eventos favoritos
- Tab: Meus números (métricas)
- Tab: Núcleo (privado) - placeholder
- Tab: Comunidades - placeholder
- Tab: Documentos - placeholder

**User Meta Utilizados:**
- `bio_full` - Bio completa do usuário
- `location` - Localização
- `membership` - Status de membro
- `roles_display` - Roles para exibição
- `apollo_favorites` - Array de IDs de eventos favoritados

**AJAX:**
- `apollo_save_profile` - Atualizar dados do perfil (bio, location, roles)

---

### 3. `[apollo_social_feed]`
**Template:** `templates/shortcode-social-feed.php`  
**Origem:** PAGE-FOR-FEED-SOCIAL  
**Uso:** Feed social com eventos recentes, filtros e sidebar.

**Funcionalidades:**
- Feed de eventos recentes
- Filtros por tipo (Tudo, Eventos, Comunidades)
- Sidebar com próximos eventos
- Navegação mobile bottom bar
- Animações com Motion.js

**Dados Exibidos:**
- Eventos recentes (últimos 10)
- Informações do evento (título, local, data/hora)
- Avatar do autor
- Ações (like, comentar, bookmark)

**Assets Carregados:**
- Tailwind CSS
- Motion.js (ES Module)
- UNI.css
- Apollo Base.js

---

### 4. `[apollo_cena_rio]`
**Template:** `templates/shortcode-cena-rio.php`  
**Origem:** PAGE-FOR-CENA-RIO  
**Uso:** Calendário mensal da cena com eventos marcados por data.

**Funcionalidades:**
- Calendário mensal interativo
- Navegação entre meses
- Eventos marcados por data
- Lista de eventos do dia selecionado
- Status: Confirmado / Previsto
- Links para ingressos

**Dados Exibidos:**
- Todos os eventos publicados com `_event_start_date`
- Agrupados por data (Y-m-d)
- Informações: título, local, horário, status
- Link de ingressos (se disponível)

**Assets Carregados:**
- Tailwind CSS
- Motion One
- UNI.css

---

## 🚀 Como Usar

### Criar Páginas WordPress

1. **Página de Perfil DJ:**
   ```
   Título: Perfil DJ
   Slug: dj-profile
   Conteúdo: [apollo_dj_profile]
   ```

2. **Dashboard do Usuário:**
   ```
   Título: Meu Apollo
   Slug: my-apollo
   Conteúdo: [apollo_user_dashboard]
   ```

3. **Feed Social:**
   ```
   Título: Feed Social
   Slug: feed
   Conteúdo: [apollo_social_feed]
   ```

4. **Calendário Cena Rio:**
   ```
   Título: Cena Rio
   Slug: cena-rio
   Conteúdo: [apollo_cena_rio]
   ```

### Usar em Templates PHP

```php
<?php echo do_shortcode('[apollo_dj_profile dj_id="123"]'); ?>
<?php echo do_shortcode('[apollo_user_dashboard]'); ?>
<?php echo do_shortcode('[apollo_social_feed]'); ?>
<?php echo do_shortcode('[apollo_cena_rio]'); ?>
```

---

## 🔧 Integração com WordPress

### Meta Keys de DJ (event_dj CPT)

Os templates esperam as seguintes meta keys no post type `event_dj`:

```php
// Textos simples
update_post_meta($dj_id, '_dj_name', 'Nome do DJ');
update_post_meta($dj_id, '_dj_tagline', 'Tagline do DJ');
update_post_meta($dj_id, '_dj_roles', 'DJ · Producer · Live Selector');
update_post_meta($dj_id, '_bio_excerpt', 'Bio resumida...');
update_post_meta($dj_id, '_bio_full', 'Bio completa...');
update_post_meta($dj_id, '_soundcloud_track', 'https://soundcloud.com/...');
update_post_meta($dj_id, '_track_title', 'Título do Track');
update_post_meta($dj_id, '_mediakit_url', 'https://drive.google.com/...');
update_post_meta($dj_id, '_more_platforms', 'Mixcloud · Beatport · ...');

// Arrays
update_post_meta($dj_id, '_dj_projects', array('Apollo::rio', 'Dismantle'));
update_post_meta($dj_id, '_dj_music_links', array(
    array('label' => 'SoundCloud', 'icon' => 'ri-soundcloud-line', 'url' => '...', 'active' => true),
    array('label' => 'Spotify', 'icon' => 'ri-spotify-line', 'url' => '...', 'active' => false)
));
update_post_meta($dj_id, '_dj_social_links', array(...));
update_post_meta($dj_id, '_dj_asset_links', array(...));
```

### User Meta

```php
update_user_meta($user_id, 'bio_full', 'Bio do usuário...');
update_user_meta($user_id, 'location', 'Copacabana · RJ');
update_user_meta($user_id, 'membership', 'Industry access');
update_user_meta($user_id, 'roles_display', 'Produtor & DJ');
```

---

## 🎨 Assets e Dependências

Todos os templates carregam automaticamente:

- **Tailwind CSS** (via CDN)
- **UNI.css** (via assets.apollo.rio.br)
- **Motion One** ou **Motion.js** (animations)
- **Apollo Base.js** (funcionalidades base)
- **RemixIcon** (via UNI.css)

Os assets são enfileirados apenas quando o shortcode é usado (conditional loading).

---

## 🔐 Segurança

- ✅ Nonces verificados em AJAX handlers
- ✅ Sanitização de inputs (`sanitize_text_field`, `sanitize_textarea_field`)
- ✅ Escaping de outputs (`esc_html`, `esc_url`, `esc_attr`)
- ✅ Verificação de permissões (`is_user_logged_in`, `current_user_can`)
- ✅ Validação de tipos de post (`post_type === 'event_dj'`)

---

## 📝 Próximos Passos (FASE 2+)

- [ ] Adicionar metaboxes no admin para DJ meta fields
- [ ] Criar interface de edição de perfil no frontend
- [ ] Implementar upload de avatar customizado
- [ ] Adicionar mais tabs funcionais no dashboard (Núcleo, Comunidades, Docs)
- [ ] Integrar com sistema de posts sociais (apollo-social)
- [ ] Adicionar sistema de comentários nos eventos do feed
- [ ] Implementar sistema de likes/favoritos no feed

---

## ✅ Status

**FASE 1:** ✅ COMPLETA  
**Templates Criados:** 4/4  
**Shortcodes Registrados:** 4/4  
**Integração WordPress:** ✅  
**Assets Enfileirados:** ✅  
**AJAX Handlers:** ✅  

**Pronto para deploy!** 🚀

