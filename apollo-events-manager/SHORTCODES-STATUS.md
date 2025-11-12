# Apollo Events Manager - Shortcodes Status Report

## ✅ Funcionando Corretamente

### 1. `[events]` - Lista de Eventos
- **Status:** ✅ Implementado e funcionando
- **Atributos:** `per_page`, `orderby`, `order`, `meta_key`, `show_pagination`, `categories`, `event_types`, `featured`, `cancelled`
- **Template:** `event-card.php`
- **Exemplo:** `[events per_page="12" categories="festa,show"]`

### 2. `[past_events]` - Eventos Passados
- **Status:** ✅ Implementado e funcionando
- **Atributos:** `per_page`, `order`, `orderby`
- **Query:** Filtra por `_event_start_date < hoje`
- **Exemplo:** `[past_events per_page="10" order="DESC"]`

### 3. `[upcoming_events]` - Próximos Eventos
- **Status:** ✅ Implementado e funcionando
- **Atributos:** `per_page`, `order`, `orderby`
- **Query:** Filtra por `_event_start_date >= hoje`
- **Exemplo:** `[upcoming_events per_page="6"]`

### 4. `[related_events]` - Eventos Relacionados
- **Status:** ✅ Implementado e funcionando
- **Atributos:** `id`, `per_page`
- **Lógica:** Baseado em categorias e tags do evento
- **Exemplo:** `[related_events id="123" per_page="5"]`

### 5. `[event_register]` - Formulário de Registro
- **Status:** ✅ Implementado (básico)
- **Atributos:** `id`
- **Exemplo:** `[event_register id="123"]`

### 6. `[submit_event_form]` - Submissão de Eventos (Frontend)
- **Status:** ✅ Implementado (requer login)
- **Comportamento:** Cria `event_listing` como `pending`, salva DJs/Locais, aceita upload de banner
- **Atributos:** *(sem atributos adicionais)*
- **Exemplo:** `[submit_event_form]`

## ⚠️ Parcialmente Implementados (TODOs)

### 6. `[event_dashboard]` - Dashboard do Usuário
- **Status:** ⚠️ Estrutura existe, mas básico
- **Problema:** Listagem simples de eventos do usuário
- **Necessita:** Filtros, estatísticas, ações rápidas

## ❌ Não Implementados / Vazios

### 8. `[event_djs]` - Lista de DJs
- **Status:** ❌ Retorna vazio
- **Linha:** 724
- **Comentário:** "Already implemented in main plugin file" (mas não está)
- **Necessita:** Implementação completa

### 9. `[event_dj]` - Single DJ
- **Status:** ❌ Retorna vazio
- **Linha:** 729
- **Comentário:** "TODO: Implement single DJ output"
- **Necessita:** Template single-dj.php

### 10. `[single_event_dj]` - Página Single DJ
- **Status:** ❌ Retorna vazio
- **Linha:** 734
- **Comentário:** "Already implemented in main plugin file" (mas não está)
- **Necessita:** Template completo

### 11. `[submit_dj_form]` - Formulário DJ
- **Status:** ❌ Retorna "coming soon"
- **Linha:** 95
- **Necessita:** Formulário frontend completo

### 12. `[dj_dashboard]` - Dashboard DJ
- **Status:** ❌ Retorna "coming soon"
- **Linha:** 718
- **Necessita:** Dashboard completo para DJs

### 13. `[event_locals]` - Lista de Locais
- **Status:** ❌ Retorna vazio
- **Linha:** 756
- **Comentário:** "Already implemented in main plugin file" (mas não está)
- **Necessita:** Listagem de venues

### 14. `[event_local]` - Single Local
- **Status:** ❌ Retorna vazio
- **Linha:** 761
- **Comentário:** "TODO: Implement single Local output"
- **Necessita:** Template single-local.php

### 15. `[single_event_local]` - Página Single Local
- **Status:** ❌ Retorna vazio
- **Linha:** 767
- **Necessita:** Template completo

### 16. `[local_dashboard]` - Dashboard Local
- **Status:** ❌ Retorna "coming soon"
- **Linha:** 744
- **Necessita:** Dashboard completo para venues

### 17. `[submit_local_form]` - Formulário Local
- **Status:** ❌ Placeholder
- **Necessita:** Formulário frontend completo

## 🎯 Prioridades de Implementação

### Urgente (Impacta usuário final)
1. **[event_djs]** - Listagem com ShadCN UI
2. **[single_event_dj]** - Página completa do DJ
3. **[event_locals]** - Listagem de venues com próximos eventos
4. **[past_events]** - Já funciona, mas verificar query (você disse que não mostra)

### Importante (Gestão de conteúdo)
5. **[submit_dj_form]** - Formulário frontend
6. **[dj_dashboard]** - Dashboard para DJs gerenciarem perfil
7. **[local_dashboard]** - Dashboard para venues

### Médio (Enhancement)
8. **[event_locals]** com next events integration
9. **[event_dashboard]** - Melhorias

## 🔧 Ações Recomendadas

### 1. Verificar `[past_events]`
O código parece correto, mas você mencionou que não funciona. Vamos debugar:
- Verificar se existem eventos com `_event_start_date` no passado
- Verificar formato da data no banco
- Adicionar debug log

### 2. Implementar `[event_djs]` com ShadCN
```php
// Listar todos os DJs ou DJs de um evento específico
[event_djs event_id="123" per_page="12"]
[event_djs] // Todos os DJs
```

### 3. Criar templates faltantes
- `templates/single-dj.php`
- `templates/dj-card.php`
- `templates/local-card.php`
- `templates/single-local.php`

### 4. Integrar com ShadCN UI
Usar componentes para:
- Cards de DJs
- Cards de Locais
- Formulários de submissão
- Dashboards

## 📋 Shortcodes por Categoria

### Eventos
- ✅ [events]
- ✅ [past_events]
- ✅ [upcoming_events]
- ✅ [related_events]
- ✅ [event_register]
- ⚠️ [event_dashboard]
- ✅ [submit_event_form]
- ❌ [event_summary] (não encontrado)

### DJs
- ❌ [event_djs]
- ❌ [event_dj]
- ❌ [single_event_dj]
- ❌ [submit_dj_form]
- ❌ [dj_dashboard]

### Locais/Venues
- ❌ [event_locals]
- ❌ [event_local]
- ❌ [single_event_local]
- ❌ [local_dashboard]
- ❌ [submit_local_form]

---

**Data:** 2025-11-12
**Autor:** Apollo Development Team
