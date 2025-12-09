Resumo e objetivos

Você deseja transformar o ecossistema Apollo (core + social + eventos + PWA) em algo modular e altamente configurável via um painel administrativo único. Isso inclui: limites globais por usuário (nº de eventos, comunas, posts, convites de bolha, etc.), papéis e permissões de moderadores em níveis (MOD 0/1/3), suspensão e banimento, bloqueio de IP (apenas admin), logs de auditoria mínimos, integração mais profunda entre eventos e social e priorização de conteúdos relevantes (bolha, eventos que vou, comunas). Além disso, pretende criar uma experiência coesa e agradável (“apaixonante”) para os usuários da Apollo.

Abaixo está um plano dividido em fases, com uma visão de alto nível, STRICT MODE EXECUTE BELOW PLAN:

Fase 1 – Núcleo de moderação e status de usuário

Meta: Criar fundações sólidas para suspensões, banimentos e níveis de moderador, além de um log crítico de auditoria. Isso permite controlar e rastrear todas as ações importantes de moderação no futuro.

Principais tarefas:

Centralizar meta apollo_status (active, suspended, banned) e apollo_suspension_until.

Criar funções: apollo_suspend_user, apollo_ban_user, apollo_unsuspend_user, apollo_is_user_suspended, apollo_can_user_perform.

Definir níveis de moderador (MOD 0: básico; MOD 1: avançado; MOD 3: pleno) e respectivas capacidades (apollo_moderate_basic, apollo_moderate_advanced, apollo_suspend_users, apollo_block_ip).

Implementar tabela simples de audit log (wp_apollo_audit_log) para registrar suspensões, banimentos e bloqueios de IP (sempre com hash do IP).

Adicionar opção apollo_ip_blocklist e hooks que negam acesso a IPs bloqueados, exceto para admins.


Fase 2 – Configuração global de módulos e limites

Meta: Introduzir um “núcleo de configuração” em apollo-core que centralize a ativação/desativação de módulos e os limites por tipo (nº de eventos por mês, nº de comunas, nº de membros na bolha, etc.). Outras funcionalidades (social, eventos, bolha) consultarão esse núcleo antes de criar qualquer recurso.

Principais tarefas:

Criar opção apollo_modules com flags (social, events, chat, docs, matchmaking, bolha, etc.) e helpers apollo_is_module_enabled().

Criar opção apollo_limits com chaves (max_events_per_user_month, max_comunas_per_user, max_bubble_members, max_social_posts_per_day, etc.) e helpers apollo_get_limit() e apollo_check_limit().

Integrar esses helpers nos pontos de criação de eventos, comunas, posts sociais, docs e convites de bolha.

Não criar UI ainda; apenas preparar código e defaults.



Fase 3 – Separar /mod/ (MOD Panel) de /apollo/ (Admin Cabin) e conectar controles

Meta: Consolidar painéis distintos: moderadores operam no /mod/ (sem estatísticas, sem IP e sem limites globais); administradores operam no /apollo/ (cabine de controle total), com permissões claras.

Principais tarefas:

Mapear páginas existentes de moderação e movê-las para /mod/, aplicando checagens de nível.

Criar ou reutilizar o menu /apollo/, adicionando abas para:

Módulos (ligar/desligar via apollo_is_module_enabled).

Limites (editar apollo_limits).

Moderadores (promover/rebaixar usuários entre níveis 0/1/3).

Segurança (IP blocklist e controle de lockdown).

Logs (exibir registros de apollo_audit_log).

Remover menus redundantes de cada plugin e redirecionar para /mod/ ou /apollo/.

Garantir que /mod/ nunca mostre estatísticas (analytics) nem IPs.

Testar com diferentes papéis (usuário, MOD 0/1/3, admin).


Fase 4 – Integração Social + Eventos + Bolha + Experiência

Meta: Amarrar os diversos módulos para que se reforcem mutuamente, criando um feed e um fluxo mais envolventes. Incluir a nova funcionalidade de bolha no peso do feed e permitir cross-post entre eventos e social.

Principais tarefas:

Em Social:

Priorizar posts de membros da bolha (3 posts de bolha : 1 post geral).
Ajustar endpoint /apollo/v1/explore para respeitar apollo_bolha e max_bubble_members.
Em Eventos:
Criar opção “publicar post social ao publicar evento”.
Criar tags/meta apollo_linked_event_id para associar posts a eventos.
Em Comuna:
Integrar eventos e docs (quando módulos ativos), listando itens relacionados.
Feed:
Inclua filtro opcional “Eventos que vou” (depende do usuário marcar presença).
Notificações:
Integrar com o sistema de notificações para pedidos de bolha, aceitações, convites de eventos e comunas.
Respeitar apollo_is_module_enabled() ao adicionar funcionalidades.


Fase 5 – QA final, estatísticas, auditoria de deploy
Meta: Garantir segurança, consistência e conformidade antes de lançar. Criar uma checklist GO/NO-GO.
Principais tarefas:
Revisar todos os register_rest_route e permission_callback (nenhuma rota sensível sem autenticação).
Checar forms/admin: nonces, current_user_can e feedback de erro.
Garantir limites e módulos respeitados em todos os fluxos.
Rodar PHPCS com WordPress e WP-VIP; corrigir todos os erros.
Criar checklist GO/NO-GO: cenários de suspensão, níveis de moderação, IP blocklist, logs funcionando.
Incluir testes manuais e, se possível, testes automatizados.
Verificar integração Social <> Eventos <> Bolha, com módulos ligados/desligados.


# Complemento final – ideais de experiência e “enamoramento”

Além das fases técnicas, vale pensar em features para tornar o Apollo mais acolhedor:
Gamificação sem ego: em vez de contadores públicos, usar conquistas privadas (ex.: “Criou sua primeira comuna!”) apenas para o usuário ver.
Tema dinâmico: permitir que organizadores de eventos escolham cores/avatars que reflitam o mood, integrando com a Design Library.
Descoberta amigável: feed inicial pode mostrar “pessoas da sua bolha curtiram…” e “eventos em comum”, promovendo conexões sem números.
Onboarding com trilha: após cadastro, sugerir eventos e comunas com base no bairro ou estilo musical; ajudar o usuário a montar sua bolha.
Essas sugestões não fazem parte das fases de código, mas podem ser adicionadas após a arquitetura modular estar pronta.
