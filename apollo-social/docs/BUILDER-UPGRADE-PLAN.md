STRICT MODE · FASE 6 · AUDIT FINAL + DEBUG + GIT SYNC (APOLLORIO)

Contexto:
- WordPress plugins: apollo-core, apollo-events-manager, apollo-social, apollo-rio, apollorio (e qualquer outro com “apollo” no nome).
- Ambiente com:
  - PHPCS (WordPress + WordPress-Extra + WordPress-VIP-Go, text-domain correto).
  - Intelephense Premium.
  - EditorConfig.
- Fases 0–5 já executadas para DOC/EDITOR/SIGN:
  - CPT apollo_document + REST CRUD.
  - Editor + HTML + PDF (mPDF).
  - Camada de assinatura + UI/UX + adapter DMS local.
  - UNI.CSS + base.js como sistema visual global.

OBJETIVO DA FASE 6:
- Fazer uma ÚNICA rodada de:
  - Audit final estático (Intelephense + PHPCS).
  - Limpeza de segurança e remoção de debug residual.
  - Verificação de integração básica dos fluxos DOC/EDITOR/SIGN.
  - Garantir 0 erros Intelephense e 0 erros PHPCS nos arquivos tocados.
  - Preparar e executar o fluxo GIT completo (add/commit/fetch/pull/push) SOMENTE depois de tudo limpo.
- Sem mudanças de feature, apenas correções e polimento.

REGRAS GERAIS:
- NÃO introduzir novas features, apenas:
  - Corrigir erros reais.
  - Ajustar segurança.
  - Padronizar estilo onde necessário.
- NÃO reformatar o repositório inteiro:
  - Limitar PHPCBF/ajustes de estilo aos arquivos alterados nas Fases 0–5 + dependências diretas.
- Prefixo apollo_ em todas funções globais e helpers novos (se precisar criar algum).
- Manter compatibilidade com WordPress VIP e .org (PHPCS limpo).
- Não usar git push --force por padrão; se sugerir, apenas documentar o comando, não executar automaticamente.
- Nesta fase NÃO alterar versão dos plugins (version bump fica para a fase de deploy).

TAREFAS (PASSO A PASSO)

1) Mapa dos ARQUIVOS ALTERADOS (base para o audit)
- A partir do root do repo, obter a lista de arquivos modificados em relação ao branch remoto (por ex. origin/main ou origin/develop):
  - Usar `git status --short` e/ou `git diff --name-only origin/<branch>...` (assuma <branch> configurável, ex.: main).
- Filtrar essa lista para arquivos em:
  - apollo-core
  - apollo-social
  - apollo-events-manager
  - apollo-rio
  - apollorio
- Essa lista define o ESCOPO da Fase 6.
  - Não sair desse escopo salvo dependência óbvia (ex.: um helper central que o código tocado depende).

2) AUDIT INTELEPHENSE (TIPAGEM/UNDEFINED)
- Rodar análise Intelephense focada nos arquivos modificados do escopo.
- Para cada erro REAL de:
  - tipo indefinido (class/function not found),
  - parâmetros inconsistentes,
  - uso de símbolos inexistentes,
  corrigir de forma minimalista:
  - Adicionar `use` statements necessários (Ex.: `use Exception;`).
  - Ajustar tipos em docblocks (/** @param int $id */ etc.).
  - Substituir chamadas diretas por `call_user_func()`/`constant()` se for necessário para evitar “undefined” em tempo de análise (padrão já usado no Apollo).
- Não mexer em arquivos sem erro Intelephense.
- Objetivo: 0 erros Intelephense em TODOS os arquivos tocados.

3) AUDIT PHPCS (WORDPRESS + VIP-GO)
- Rodar PHPCS APENAS nos arquivos tocados (usar a lista da etapa 1 para limitar o escopo).
- Categorizar problemas:
  - a) Estilo puro (espaços, vírgulas, indentação) → usar PHPCBF apenas nesses arquivos e apenas onde for claramente seguro.
  - b) Escaping / sanitização / segurança → corrigir manualmente:
    - Saídas → `esc_html()`, `esc_attr()`, `esc_url()`, `wp_kses_post()`, etc.
    - Entradas → `sanitize_text_field()`, `intval()`, `absint()`, `sanitize_key()`, etc.
  - c) Text-domain → corrigir para o correto de cada plugin.
- Onde uma regra conflitar com necessidade real e não houver jeito sem quebrar lógica:
  - Usar `// phpcs:ignore <rule> // motivo curto e objetivo`.
- Objetivo: 0 erros PHPCS nos arquivos tocados (warnings só se realmente inevitáveis e, de preferência, também tratados).

4) AUDIT DE SEGURANÇA / DEBUG RESÍDUO
- Em TODO o escopo Apollo (plugins com “apollo” no nome), mas focando primeiro nos arquivos alterados:
  - Procurar por:
    - `error_log(`
    - `var_dump(`
    - `print_r(`
    - `die(`
    - `exit(`
    - `dd(`
  - Remover ou substituir por:
    - Logs controlados atrás de `WP_DEBUG` / `WP_DEBUG_LOG` se realmente necessários.
    - Comentário explicando e, se indispensável, `// phpcs:ignore` justificado.
- Revisar endpoints REST/AJAX tocados nas fases anteriores:
  - Confirmar que têm:
    - `check_ajax_referer()` ou nonce equivalente (para AJAX).
    - `current_user_can()` adequado ao tipo de operação (read, edit, delete, sign).
    - Sanitização de todos os parâmetros externos.
    - Resposta padronizada (ex.: `Apollo\API\Response` ou `WP_REST_Response`).
- Se houver qualquer ambiguidade de segurança:
  - Preferir a solução mais conservadora (bloquear acesso, apertar capability, exigir nonce).
  - Opcionalmente, deixar `// TODO: revisar regra de segurança X` com ponteiro para o arquivo/linha.

5) MINI SMOKE TEST LÓGICO (SEM RODAR NAVEGADOR)
- Por leitura de código (estática), validar se os fluxos críticos permanecem coerentes:

  - CPT `apollo_document`:
    - Registrado corretamente (`register_post_type`) e com `show_in_rest` ajustado.
    - Usado nas rotas REST e templates sem inconsistências de slug/ID.

  - Fluxo DOC → HTML → PDF:
    - Meta keys corretas e consistentes (`_apollo_document_delta`, `_apollo_document_sheet`, `_apollo_document_html`, `_apollo_document_status`, `_apollo_document_type`, `_apollo_document_version`).
    - Serviço de PDF (ex.: PdfService) é chamado via endpoint/botão, não duplicado em vários lugares.

  - Fluxo de ASSINATURA:
    - Serviço principal (ex.: SignaturesService) é o único ponto de verdade para assinatura.
    - Endpoint REST `/sign` (ou equivalente) chama esse serviço, não faz lógica bruta dentro do controller.
    - Log de assinatura é escrito de forma consistente (ex.: meta `_apollo_document_signatures` ou estrutura documentada).
    - `_apollo_document_status` é atualizado para `'signed'` apenas em sucesso.

  - UI:
    - Tooltips `data-ap-tooltip` presentes nos campos críticos (status, tipo, versão, assinatura, ações sensíveis).
    - Chamadas JS (sign-document.js, base.js, event scripts) usam `data-*` e classes `.ap-` de forma consistente com o padrão atual.

6) DOC RÁPIDA DE MUDANÇAS (OPCIONAL MAS RECOMENDADO)
- Se ainda não existir, garantir a presença de um doc curto:
  - `apollo-social/docs/doc-sign-flow.md`
- Atualizar se necessário com:
  - Resumo de fluxo:
    - Criar/editar documento.
    - Gerar PDF.
    - Assinar.
    - Ver status.
  - Principais endpoints REST (nomes + métodos) e qualquer capability importante.

7) GIT · PREPARAR COMMIT FINAL
- Do root do repo:
  - `git status`
  - Revisar a lista de arquivos modificados (apenas aqueles tocados de fato).
- Conferir se não há:
  - Arquivos temporários (.log, .tmp, .orig, etc.).
  - Dumps / logs / notas pessoais.
- Adicionar apenas o que faz sentido para o commit final:
  - `git add` nos plugins Apollo + docs relevantes.
- Criar uma mensagem de commit clara, por ex.:
  - `feat(docs): finalize apollo_document editor + sign flow`
  - ou
  - `chore(plugins): final audit + phpcs/intelephense cleanup`

8) GIT · FETCH / PULL / PUSH (SEM FORCE POR PADRÃO)
- Executar:
  - `git fetch origin`
  - `git pull --rebase origin <branch>`  (substituir `<branch>` por main/develop/etc.)
- Se houver conflitos:
  - Resolver mantendo SEMPRE a versão mais recente do Apollo, respeitando o que foi feito nas fases 0–6.
  - Após resolver conflitos:
    - Rodar novamente Intelephense + PHPCS nos arquivos afetados pelo merge.
- Quando tudo estiver limpo:
  - `git push origin <branch>`
- Apenas se existir uma necessidade real de sobrescrever histórico remoto (e após revisão humana manual):
  - Documentar a sugestão `git push --force-with-lease`, mas NÃO executar automaticamente.

SAÍDA ESPERADA:
- Todos os arquivos Apollo tocados nas fases anteriores com:
  - 0 erros Intelephense.
  - 0 erros PHPCS (standards WordPress + Extra + VIP-Go).
  - Sem debug residual (error_log, var_dump, die, etc.).
- Fluxos DOC/EDITOR/SIGN estáveis por leitura de código.
- Commit final criado e sincronizado com o branch remoto (fetch/pull/push concluídos).
- Nenhuma ação destrutiva de GIT feita automaticamente (no máximo sugerida e explicada).
