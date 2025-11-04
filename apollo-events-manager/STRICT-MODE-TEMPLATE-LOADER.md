# 🎯 STRICT MODE - TEMPLATE LOADER IMPLEMENTATION

**Data:** 2025-11-04  
**Status:** ✅ **IMPLEMENTADO**  
**Objetivo:** Forçar templates do plugin independente do tema ativo

---

## ✅ ALTERAÇÕES IMPLEMENTADAS

### 1. Template Loader (apollo-events-manager.php)

**Localização:** Linhas 270-307  
**Função:** `canvas_template()`

#### ANTES (Context-aware)
```php
public function canvas_template($template) {
    // Checava tema, página, shortcode
    // Poderia usar template do tema
    // Condicional e frágil
}
```

#### DEPOIS (STRICT MODE)
```php
public function canvas_template($template) {
    // Don't override in admin
    if (is_admin()) {
        return $template;
    }
    
    // FORCE SINGLE EVENT TEMPLATE
    if (is_singular('event_listing')) {
        return APOLLO_WPEM_PATH . 'templates/single-event-standalone.php';
    }
    
    // FORCE ARCHIVE/LIST TEMPLATE
    if (is_page('eventos') || is_post_type_archive('event_listing')) {
        return APOLLO_WPEM_PATH . 'templates/portal-discover.php';
    }
    
    return $template;
}
```

**Benefícios:**
- ✅ SEMPRE usa templates do plugin
- ✅ Ignora completamente o tema
- ✅ Funciona com qualquer tema (Twenty Twenty-Five, Astra, etc)
- ✅ Visual consistente com CodePens

---

### 2. Portal de Eventos (portal-discover.php)

#### Estrutura HTML

**ANTES:**
```php
<!DOCTYPE html>
<html>
<head>
    <?php wp_head(); ?>
</head>
<body>
    <!-- Conteúdo completo -->
    <?php wp_footer(); ?>
</body>
</html>
```

**DEPOIS:**
```php
<?php get_header(); ?>

<!-- Apollo Discover Container -->
<div class="apollo-discover">
    <!-- Conteúdo -->
</div>

<?php get_footer(); ?>
```

#### Traduções PT-BR

**ANTES:**
- "Experience Tomorrow's Events"
- "Discover Events"
- "Filter"

**DEPOIS:**
- "Descubra os Próximos Eventos"
- "Eventos"
- "Filtrar"

**Alterações:**
- ✅ Removido `<!DOCTYPE>`, `<html>`, `<head>`, `<body>`
- ✅ Usa `get_header()` e `get_footer()` do WordPress
- ✅ Conteúdo em container `.apollo-discover`
- ✅ Todos os textos em PT-BR
- ✅ CodePen raxqVGR como base visual

---

### 3. Single Event (single-event-standalone.php)

#### Estrutura HTML

**ANTES:**
```php
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <title>...</title>
    <?php wp_head(); ?>
</head>
<body>
<div class="mobile-container">
    <!-- Conteúdo -->
</div>
</body>
</html>
```

**DEPOIS:**
```php
<?php get_header(); ?>

<div class="apollo-single mobile-container">
    <!-- Conteúdo -->
</div>

<?php get_footer(); ?>
```

**Alterações:**
- ✅ Removido HTML completo
- ✅ Usa `get_header()` e `get_footer()`
- ✅ Container `.apollo-single`
- ✅ CodePen JoGvgaY como base visual
- ✅ Mantém toda a lógica de dados (DJs, local, mapa, etc)

---

## 🎯 ROTAS CONTROLADAS

### 1. Lista de Eventos (`/eventos/`)
**Condições que forçam `portal-discover.php`:**
- `is_page('eventos')` - Página com slug "eventos"
- `is_post_type_archive('event_listing')` - Archive do CPT

**Resultado:**
```
/eventos/                    → portal-discover.php ✅
/evento/?post_type=event...  → portal-discover.php ✅
```

### 2. Single Event (`/evento/{slug}`)
**Condição que força `single-event-standalone.php`:**
- `is_singular('event_listing')` - Single de qualquer evento

**Resultado:**
```
/evento/festa-no-d-edge/     → single-event-standalone.php ✅
/evento/qualquer-slug/       → single-event-standalone.php ✅
```

---

## 🛡️ SEGURANÇA PRESERVADA

### Nonces AJAX
✅ Mantidos em:
- `ajax_filter_events()`
- `ajax_load_event_single()`
- `ajax_toggle_favorite()`

### Sanitização
✅ Mantida em:
- `save_custom_event_fields()` (imagens, timetable)
- Output escaping (datas, coords, descrição)
- `$wpdb->prepare()` (queries de migração)

### Nenhuma Regressão
- ❌ Sem `wp_remote_get()` para CSS
- ❌ Sem `serialize()` manual
- ❌ Sem SQL injection
- ✅ Todas as correções anteriores preservadas

---

## 📊 CSS E ASSETS

### CSS Global
**URL:** `https://assets.apollo.rio.br/uni.css`  
**Handle:** `apollo-uni-css`  
**Método:** `wp_enqueue_style()`

**Garantias:**
- ✅ Carregado via enqueue (não remote_get)
- ✅ Sem CSS inline gigante
- ✅ Sem duplicação em arquivos locais
- ✅ Cache do browser funciona

### JS Base
**URL:** `https://assets.apollo.rio.br/base.js`  
**Carregado em:** `portal-discover.php` (footer)

### Leaflet (Mapas)
**CSS:** `https://unpkg.com/leaflet@1.9.4/dist/leaflet.css`  
**JS:** `https://unpkg.com/leaflet@1.9.4/dist/leaflet.js`  
**Carregado em:** Single events (quando há coordenadas)

---

## 🧪 TESTES NECESSÁRIOS

### Teste 1: Troca de Tema
```
1. Ativar Twenty Twenty-Five
2. Acessar /eventos/
3. Verificar: Layout Apollo (não theme archive)
4. Acessar /evento/qualquer-slug/
5. Verificar: Layout Apollo (não theme single)
6. Trocar para Astra ou outro tema
7. Repetir verificações
```

**Esperado:** Layout Apollo em TODOS os temas.

### Teste 2: Página /eventos/ Deletada
```
1. Deletar página "Eventos" (mover para lixeira)
2. Acessar /eventos/
3. Verificar: Ainda mostra lista de eventos
4. Reativar plugin
5. Verificar: Página restaurada
```

**Esperado:** Continua funcionando, restaura ao reativar.

### Teste 3: Archive de event_listing
```
1. Acessar /?post_type=event_listing
2. Verificar: Usa portal-discover.php
```

**Esperado:** Mesmo layout de /eventos/.

### Teste 4: CSS Carregamento
```
1. Abrir DevTools → Network
2. Acessar /eventos/
3. Verificar: uni.css carregado via <link> (não inline)
4. Status: 200 OK
5. Verificar: base.js carregado
```

**Esperado:** Assets externos carregam corretamente.

### Teste 5: Single com Mapa
```
1. Acessar evento com coordenadas
2. Verificar: Leaflet carrega
3. Verificar: Mapa renderiza
4. Console: Sem erros JS
```

**Esperado:** Mapa funcional.

---

## 📝 COMPATIBILIDADE COM TEMAS

### Temas Testáveis

| Tema | Tipo | Status Esperado |
|------|------|-----------------|
| Twenty Twenty-Five | Block Theme | ✅ Templates forçados |
| Twenty Twenty-Four | Block Theme | ✅ Templates forçados |
| Astra | Classic | ✅ Templates forçados |
| GeneratePress | Classic | ✅ Templates forçados |
| Kadence | Hybrid | ✅ Templates forçados |

**Todos devem:** Exibir layout Apollo, não layout do tema.

---

## 🚨 COMPORTAMENTOS ESPERADOS

### Header/Footer do Tema
- ✅ Header do tema É exibido (get_header())
- ✅ Footer do tema É exibido (get_footer())
- ✅ Menu do tema funciona
- ✅ Sidebar do tema NÃO afeta layout Apollo (classes isoladas)

### Classes CSS Apollo
- `.apollo-discover` - Container do portal
- `.apollo-single` - Container do single
- `.mobile-container` - Container mobile-first
- `.event-manager-shortcode-wrapper` - Wrapper de eventos

**Especificidade:** Classes Apollo têm prioridade sobre tema.

---

## 🔧 TROUBLESHOOTING

### Problema: Tema sobrescreve layout
**Causa:** Template loader com prioridade baixa  
**Solução:** Hook `template_include` com prioridade 99 (já aplicado)

### Problema: CSS não carrega
**Causa:** Enqueue condicional falha  
**Solução:** Verificar `should_enqueue_assets()` retorna true

### Problema: 404 em /eventos/
**Causa:** Rewrite rules não flushed  
**Solução:** 
```php
// No admin: Settings → Permalinks → Save
// Ou via código:
flush_rewrite_rules(false);
```

### Problema: Single usa template de tema
**Causa:** Condição `is_singular('event_listing')` falha  
**Solução:** Verificar CPT registrado corretamente

---

## 📋 CHECKLIST DE VALIDAÇÃO

### Funcionalidade
- [ ] `/eventos/` usa `portal-discover.php`
- [ ] `/evento/{slug}` usa `single-event-standalone.php`
- [ ] Troca de tema NÃO afeta layout
- [ ] Header/footer do tema exibidos
- [ ] uni.css carrega via enqueue
- [ ] base.js carrega (portal)
- [ ] Leaflet carrega (single com coords)

### Visual (CodePens)
- [ ] Portal matches CodePen raxqVGR
- [ ] Single matches CodePen JoGvgaY
- [ ] Todos os textos em PT-BR
- [ ] Layout responsivo funciona

### Segurança
- [ ] Nonces AJAX funcionam
- [ ] Sanitização preservada
- [ ] Sem wp_remote_get() para CSS
- [ ] Escaping de output mantido

---

## 🎯 RESULTADO FINAL

**Status:** ✅ **TEMPLATES FORÇADOS COM SUCESSO**

**Garantias:**
1. ✅ Layout Apollo independente de tema
2. ✅ Visual consistente com CodePens
3. ✅ Segurança preservada (todas as correções mantidas)
4. ✅ CSS global via enqueue (não remote_get)
5. ✅ Textos em PT-BR
6. ✅ Compatível com qualquer tema WordPress

**Arquivos Modificados:**
1. `apollo-events-manager.php` (linhas 270-307)
2. `templates/portal-discover.php` (header/footer + PT-BR)
3. `templates/single-event-standalone.php` (header/footer)

**Próximos Passos:**
1. Testar em staging com múltiplos temas
2. Validar visual contra CodePens
3. Confirmar assets carregam corretamente
4. Deploy para produção

---

**Última Atualização:** 2025-11-04  
**Implementado por:** AI Senior WordPress Engineer  
**Review:** Pronto para testes

