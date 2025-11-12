# Apollo Events Manager - Shortcodes Documentation

## 📚 Guia Completo de Shortcodes

### ✅ STATUS ATUAL
- **Total de Shortcodes:** 17
- **Funcionando:** 15
- **A Implementar:** 2 (formulários frontend)

---

## 🎉 EVENTOS

### `[events]` - Lista de Eventos
Exibe uma listagem de eventos com filtros.

**Atributos:**
```php
[events 
    per_page="12"           // Número de eventos por página (padrão: 10)
    orderby="meta_value"    // Ordenar por (title, date, meta_value)
    order="ASC"             // ASC ou DESC
    meta_key="_event_start_date"  // Meta key para ordenação
    show_pagination="true"  // Mostrar paginação (true/false)
    categories="festa,show" // Slugs de categorias (separados por vírgula)
    featured="true"         // Apenas eventos em destaque (true/false)
    cancelled="false"       // Incluir cancelados (true/false)
]
```

**Exemplos:**
```php
// Próximas festas, 12 por página
[events per_page="12" categories="festa" orderby="meta_value" order="ASC"]

// Eventos em destaque
[events featured="true" per_page="6"]

// Todas as categorias com paginação
[events show_pagination="true"]
```

**Template usado:** `event-card.php`

---

### `[past_events]` - Eventos Passados
Exibe eventos que já aconteceram.

**Atributos:**
```php
[past_events
    per_page="10"      // Número de eventos (padrão: 10)
    order="DESC"       // ASC ou DESC (padrão: DESC = mais recente primeiro)
    orderby="event_start_date"  // Ordenar por data do evento
]
```

**Exemplo:**
```php
// Últimos 20 eventos passados
[past_events per_page="20" order="DESC"]
```

**Query:** Filtra `_event_start_date < hoje`

---

### `[upcoming_events]` - Próximos Eventos
Exibe eventos futuros.

**Atributos:**
```php
[upcoming_events
    per_page="10"      // Número de eventos (padrão: 10)
    order="ASC"        // ASC ou DESC (padrão: ASC = próximo primeiro)
    orderby="event_start_date"
]
```

**Exemplo:**
```php
// Próximos 6 eventos
[upcoming_events per_page="6"]
```

---

### `[related_events]` - Eventos Relacionados
Exibe eventos relacionados baseado em categorias e tags.

**Atributos:**
```php
[related_events
    id="123"           // ID do evento (padrão: evento atual)
    per_page="5"       // Quantos eventos mostrar (padrão: 5)
]
```

**Exemplo:**
```php
// No single event
[related_events per_page="4"]

// Para evento específico
[related_events id="456" per_page="3"]
```

---

### `[event_register]` - Formulário de Registro
Formulário para usuário se registrar em um evento.

**Atributos:**
```php
[event_register
    id="123"           // ID do evento (padrão: evento atual)
]
```

---

### `[event_dashboard]` - Dashboard do Usuário
Dashboard para usuário gerenciar seus eventos.

**Status:** ⚠️ Básico implementado
**Requer:** Usuário logado

```php
[event_dashboard]
```

---

### `[submit_event_form]` - Formulário de Submissão
**Status:** ❌ A implementar
**Requer:** Usuário logado

```php
[submit_event_form]
```

---

## 🎧 DJs

### `[event_djs]` - Lista de DJs ✨ NOVO COM SHADCN
Exibe listagem de DJs com cards modernos.

**Atributos:**
```php
[event_djs
    event_id="123"     // ID do evento (opcional, mostra DJs daquele evento)
    per_page="12"      // Número de DJs (padrão: 12)
    orderby="title"    // title, date, rand
    order="ASC"        // ASC ou DESC
    show_bio="true"    // Mostrar biografia (true/false)
    layout="grid"      // grid, list, slider
]
```

**Exemplos:**
```php
// Todos os DJs em grid
[event_djs per_page="12" layout="grid"]

// DJs de um evento específico
[event_djs event_id="456" show_bio="true"]

// Lista com biografias
[event_djs layout="list" show_bio="true"]
```

**Template:** `dj-card.php` (ShadCN inspired)

**Features:**
- ✅ Avatar circular com foto
- ✅ Badge de eventos upcoming
- ✅ Gêneros musicais
- ✅ Links sociais (Instagram, SoundCloud)
- ✅ Animações hover
- ✅ Dark mode support
- ✅ Responsive

---

### `[event_dj]` / `[single_event_dj]` - Single DJ
Exibe página completa de um DJ.

**Atributos:**
```php
[event_dj
    id="123"           // ID do DJ (padrão: DJ atual)
    show_events="true" // Mostrar eventos do DJ (true/false)
    show_bio="true"    // Mostrar biografia (true/false)
    show_social="true" // Mostrar links sociais (true/false)
]
```

**Exemplo:**
```php
// No single DJ
[single_event_dj]

// DJ específico
[event_dj id="789" show_events="true"]
```

---

### `[dj_dashboard]` - Dashboard DJ
**Status:** ❌ A implementar
**Requer:** Usuário logado como DJ

```php
[dj_dashboard]
```

---

### `[submit_dj_form]` - Formulário DJ
**Status:** ❌ A implementar
**Requer:** Usuário logado

```php
[submit_dj_form]
```

---

## 📍 LOCAIS/VENUES

### `[event_locals]` - Lista de Locais ✨ NOVO COM SHADCN
Exibe listagem de venues com próximos eventos.

**Atributos:**
```php
[event_locals
    per_page="12"           // Número de locais (padrão: 12)
    orderby="title"         // title, date, rand
    order="ASC"             // ASC ou DESC
    show_next_events="true" // Mostrar próximos eventos (true/false)
    region="Rio de Janeiro" // Filtrar por região
    layout="grid"           // grid, list, map
]
```

**Exemplos:**
```php
// Todos os locais com próximos eventos
[event_locals per_page="12" show_next_events="true"]

// Locais no Rio
[event_locals region="Rio de Janeiro" layout="grid"]

// Lista sem eventos
[event_locals layout="list" show_next_events="false"]
```

**Template:** `local-card.php` (ShadCN inspired)

**Features:**
- ✅ Foto grande do local
- ✅ Região e endereço
- ✅ Capacidade
- ✅ Lista de próximos 3 eventos
- ✅ Links diretos para eventos
- ✅ Animações hover
- ✅ Dark mode support
- ✅ Responsive

---

### `[event_local]` / `[single_event_local]` - Single Local
Exibe página completa de um local/venue.

**Atributos:**
```php
[event_local
    id="123"               // ID do local (padrão: local atual)
    show_events="true"     // Mostrar eventos futuros (true/false)
    show_description="true"// Mostrar descrição (true/false)
    show_map="true"        // Mostrar mapa (true/false)
]
```

**Exemplo:**
```php
// No single local
[single_event_local]

// Local específico com todos os detalhes
[event_local id="456" show_events="true" show_map="true"]
```

---

### `[local_dashboard]` - Dashboard Local
**Status:** ❌ A implementar
**Requer:** Usuário logado como venue manager

```php
[local_dashboard]
```

---

### `[submit_local_form]` - Formulário Local
**Status:** ❌ A implementar
**Requer:** Usuário logado

```php
[submit_local_form]
```

---

## 🎨 ESTILOS SHADCN

Todos os novos cards (`dj-card.php` e `local-card.php`) usam:

### CSS Variables (customizáveis)
```css
--apollo-card-bg: #ffffff
--apollo-border: #e5e7eb
--apollo-text: #1f2937
--apollo-text-muted: #6b7280
--apollo-text-secondary: #4b5563
--apollo-card-footer: #f9fafb
--apollo-bg: #ffffff
--apollo-primary: #3b82f6
```

### Layouts Disponíveis

**Grid (padrão):**
- Responsive columns
- Auto-fill minmax
- Gap consistente

**List:**
- Horizontal layout
- Melhor para desktop
- Mais informação visível

**Slider (futuro):**
- Carrossel
- Touch/swipe

---

## 🚀 POPUP MODAL - NOVO!

**Status:** ✅ Implementado no `portal-discover.php`

Todos os cards de eventos agora abrem em popup modal com:
- ✅ Overlay blur backdrop
- ✅ Iframe para conteúdo completo
- ✅ Botão close
- ✅ ESC para fechar
- ✅ Click fora fecha
- ✅ Analytics tracking
- ✅ Smooth animations
- ✅ Responsive

**Como funciona:**
```javascript
// Automático em todos os .event_listing[href]
// Permite Ctrl+Click para nova aba
// Track via window.ApolloAnalytics
```

---

## 📊 PRIORIDADES FUTURAS

### Urgente
1. ✅ `[event_djs]` - FEITO!
2. ✅ `[event_locals]` com next events - FEITO!
3. ✅ Popup modal - FEITO!

### Importante
4. `[submit_dj_form]` - Frontend DJ submission
5. `[dj_dashboard]` - DJ management
6. `[local_dashboard]` - Venue management

### Nice to Have
7. `[submit_event_form]` - Melhorar
8. `[event_dashboard]` - Adicionar stats/analytics
9. Map view para `[event_locals layout="map"]`

---

## 🔍 DEBUG & TESTE

Para testar os shortcodes:

```php
// Em qualquer página/post
[event_djs per_page="6"]
[event_locals show_next_events="true" per_page="4"]
[events categories="festa" per_page="8"]
```

**Debug Admin:**
- Cada card mostra ID do post
- Content density indicators
- Analytics tracking no console

---

## 📝 NOTAS TÉCNICAS

### Compatibilidade
- ✅ WordPress 5.8+
- ✅ PHP 7.4+
- ✅ Remix Icons required
- ✅ Apollo CSS variables

### Performance
- Lazy loading images
- Efficient WP_Query
- Cached post meta
- Minimal DB queries

### Acessibilidade
- ARIA labels
- Semantic HTML
- Keyboard navigation
- Screen reader friendly

---

**Última atualização:** <?php echo date('Y-m-d H:i:s'); ?>
**Versão:** 2.0.0
**Autor:** Apollo Development Team 🚀
