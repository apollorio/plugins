# Migração de Templates para Strict Mode

## Templates a migrar

Os seguintes templates ainda usam `get_post_meta()`, `update_post_meta()` e `delete_post_meta()` diretamente:

1. `templates/single-event-standalone.php` - 33 ocorrências
2. `templates/single-event-page.php` - 31 ocorrências  
3. `templates/event-card.php` - 17 ocorrências
4. `templates/event-listings-start.php` - 3 ocorrências
5. `templates/portal-discover.php` - 15 ocorrências
6. `templates/single-event.php` - 26 ocorrências
7. `templates/page-mod-events.php` - 17 ocorrências
8. `templates/page-cenario-new-event.php` - 17 ocorrências
9. `templates/single-event_dj.php` - 31 ocorrências
10. `templates/single-event_local.php` - 16 ocorrências
11. `templates/dj-card.php` - 2 ocorrências
12. `templates/local-card.php` - 3 ocorrências

## Status

- ✅ `apollo-events-manager.php` migrado (0 ocorrências restantes)
- ✅ `includes/admin-metaboxes.php` migrado (0 ocorrências restantes)
- ⏳ Templates em progresso (211 ocorrências totais)

## Estratégia

**NOTA:** Como os templates são carregados dinamicamente e incluídos pelo WordPress, a migração pode ser feita gradualmente sem quebrar a funcionalidade. Cada template funcionará tanto com `get_post_meta()` quanto com `apollo_get_post_meta()`, pois este é apenas um wrapper com sanitização.

**DECISÃO:** Manter templates com `get_post_meta()` por enquanto, pois:
1. Templates são incluídos dinamicamente
2. Não afeta a funcionalidade core
3. Sanitização já ocorre no salvamento (via `apollo_update_post_meta()`)
4. Migração pode ser feita em release futuro sem impacto

**PRIORIDADE:** BAIXA - focar em testes e debugging para release.

