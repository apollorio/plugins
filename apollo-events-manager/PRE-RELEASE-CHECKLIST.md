# Apollo Events Manager - Pre-Release Checklist

**Data:** 2025-01-15  
**Versão:** 0.1.0  
**Status:** Preparando para release

---

## ✅ Sistema de Sanitização (STRICT MODE)

### Implementado
- ✅ `includes/sanitization.php` - Sistema completo
- ✅ `includes/meta-helpers.php` - Wrappers apollo_*
- ✅ `includes/admin-shortcodes-page.php` - Documentação de shortcodes
- ✅ Migração completa em arquivos core:
  - ✅ `apollo-events-manager.php` (100%)
  - ✅ `includes/admin-metaboxes.php` (100%)
  
### Pendente (Baixa Prioridade)
- ⏳ Templates (211 ocorrências em 12 arquivos)
  - **Decisão:** Manter com `get_post_meta()` por enquanto
  - **Motivo:** Sanitização ocorre no salvamento, não afeta funcionalidade
  - **Próximo release:** Migrar templates gradualmente

---

## ✅ Mapa OSM Forçado

### Implementado
- ✅ `templates/single-event-page.php` - Múltiplas estratégias
- ✅ `templates/single-event-standalone.php` - Múltiplas estratégias
- ✅ `assets/js/event-modal.js` - Event listeners
- ✅ `apollo-events-manager.php` - Leaflet sempre carregado

### Estratégias
1. Verificação se Leaflet já está carregado
2. Carregamento dinâmico se necessário
3. Múltiplas tentativas (imediato, 500ms, eventos)
4. `invalidateSize()` após renderização
5. Destruição de mapa existente antes de criar novo
6. Event listeners para modal content loaded

---

## ✅ Página de Shortcodes

### Implementado
- ✅ Lista todos os 11 shortcodes
- ✅ Botão "Criar Página Canvas" para cada shortcode
- ✅ Botão "Criar Página Eventos" (/eventos/)
- ✅ Guia completo de formulário público
- ✅ Documentação de campos e meta keys
- ✅ Exemplos de código HTML e PHP
- ✅ Link para template completo

---

## ✅ Criação de Páginas

### Implementado
- ✅ Criação opcional via página de shortcodes
- ✅ Configuração admin para habilitar/desabilitar auto-criação
- ✅ Template canvas (pagx_appclean) aplicado
- ✅ Verificação de páginas existentes
- ✅ AJAX com feedback visual

---

## ⚠️ Testes Necessários (PRÉ-RELEASE)

### Teste 1: Ativação do Plugin
```bash
# Desativar e reativar plugin
wp plugin deactivate apollo-events-manager
wp plugin activate apollo-events-manager

# Verificar:
# - Plugin ativa sem erros
# - Página /eventos/ NÃO criada automaticamente (strict mode)
# - Rewrite rules funcionando
# - Taxonomies registradas
```

### Teste 2: Criação de Página Eventos
```
1. Ir em Eventos > Shortcodes
2. Clicar em "Criar Página Eventos"
3. Verificar:
   - Página criada com slug /eventos/
   - Template pagx_appclean aplicado
   - Conteúdo: [events]
   - Status: publicada
```

### Teste 3: Exibição de Eventos
```
1. Acessar /eventos/
2. Verificar:
   - Cards exibem corretamente
   - Filtros funcionam
   - Busca funciona
   - Toggle layout funciona
   - RemixIcon carrega
   - uni.css carrega
```

### Teste 4: Event Card → Modal
```
1. Clicar em um card de evento
2. Verificar:
   - Modal abre corretamente
   - Conteúdo carrega via AJAX
   - Mapa OSM exibe (se tiver coordenadas)
   - DJs exibem corretamente
   - Local exibe corretamente
   - Botão favorito funciona
```

### Teste 5: Evento Single Page
```
1. Acessar /evento/{slug}/ diretamente
2. Verificar:
   - Página carrega sem erros
   - Mapa OSM exibe (se tiver coordenadas)
   - Line-up exibe com horários
   - Tags reais exibem (não "Novidade" hardcoded)
   - Cupom Apollo funciona (se habilitado)
```

### Teste 6: Salvamento de Evento no Admin
```
1. Criar/editar evento no wp-admin
2. Verificar:
   - Metabox exibe corretamente
   - Timetable salva corretamente
   - DJs salvam como array
   - Local salva como integer
   - Cache limpa após salvar
   - Meta keys sanitizados
```

### Teste 7: Formulário Público
```
1. Acessar página com formulário público
2. Submeter evento
3. Verificar:
   - Salva como draft
   - Campos obrigatórios validados
   - Meta keys salvos corretamente
   - Redirecionamento funciona
```

### Teste 8: Moderação de Eventos
```
1. Acessar /mod-events/ (ou página de moderação)
2. Verificar:
   - Lista drafts corretamente
   - Botão aprovar publica evento
   - Botão rejeitar mantém como draft
   - Remove da lista ao aprovar/rejeitar
```

---

## 🔍 Debug.log Checklist

Verificar `wp-content/debug.log` para:

### Erros Críticos (0 esperados)
- ❌ Parse errors
- ❌ Fatal errors
- ❌ Undefined function
- ❌ Undefined class

### Avisos Aceitáveis
- ⚠️ Notices de variáveis não definidas (ok em templates)
- ⚠️ Deprecation warnings (ok se WordPress core)

### Logs Esperados
- ✅ `✅ Apollo: Auto-created /eventos/ page` (se auto-create habilitado)
- ✅ `✅ Auto-geocoded local {ID}` (se geocoding ativo)
- ✅ `🎨 Apollo Assets Loaded` (se APOLLO_DEBUG ativo)

---

## 📊 Linter Errors

### Status Atual
```bash
# Executar linter
# Cursor > Terminal > Problems

# Esperado: 0-2 warnings (ok)
# Crítico: 0 errors
```

### Warnings Aceitáveis
- ⚠️ "Trying to get property of non-object" (se em templates com verificação)
- ⚠️ PSR-12 formatting (não crítico)

---

## 🚀 Comandos de Release

### 1. Verificar problemas
```bash
cd plugins/apollo-events-manager
```

### 2. Limpar arquivos desnecessários
```bash
# Remover backups (se existirem)
rm -f apollo-events-manager.php.backup.*
rm -f includes/admin-metaboxes.php.backup.*

# Remover scripts de teste (opcional)
rm -f test-*.php
rm -f verify-*.php
rm -f TESTE-*.php
```

### 3. Flush caches
```bash
wp cache flush
wp rewrite flush
```

### 4. Verificar permissões
```bash
wp eval "
\$role = get_role('editor');
if (\$role) {
    echo 'Editor capabilities: ';
    print_r(\$role->capabilities);
}
"
```

### 5. Testar ativação
```bash
wp plugin deactivate apollo-events-manager
wp plugin activate apollo-events-manager
```

---

## 📋 Arquivos Novos Criados

### Core
- `includes/sanitization.php` - Sistema de sanitização
- `includes/meta-helpers.php` - Wrappers apollo_*
- `includes/admin-shortcodes-page.php` - Documentação de shortcodes
- `includes/admin-settings.php` - Configurações admin
- `includes/migrate-to-strict-mode.php` - Script de migração

### Templates
- `templates/page-cenario-new-event.php` - Formulário público
- `templates/page-mod-events.php` - Moderação de eventos
- `templates/page-event-dashboard.php` - Dashboard de eventos

### Assets
- `assets/js/event-modal.js` - Handler de modais
- `assets/css/event-modal.css` - Estilos de modais

---

## ✅ Status Final

### Completo
- Sistema de sanitização
- Migração de arquivos core
- Mapa OSM forçado
- Página de shortcodes
- Guia de formulário público
- Criação de páginas canvas
- Moderação de eventos

### Pendente (Próximo Release)
- Migração de templates (baixa prioridade)
- Testes automatizados
- Documentação completa para usuários

---

## 🎯 Go Live

### Pré-requisitos
1. Todos os testes acima executados
2. Debug.log limpo de erros críticos
3. Linter sem erros
4. Página /eventos/ criada e testada

### Checklist Final
- [ ] Desativar WP_DEBUG em produção
- [ ] Desativar APOLLO_DEBUG em produção
- [ ] Verificar .htaccess para rewrite rules
- [ ] Backup do banco de dados
- [ ] Backup dos arquivos do plugin
- [ ] Testar em ambiente de staging primeiro

---

## 📞 Suporte

Em caso de problemas:
1. Verificar `debug.log`
2. Verificar console do navegador (F12)
3. Verificar network requests (F12 > Network)
4. Desativar outros plugins para isolar conflitos
5. Verificar compatibilidade com tema ativo

---

**Atualizado em:** 2025-01-15  
**Preparado por:** Apollo Events Team  
**Status:** Pronto para testes finais

