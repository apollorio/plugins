# Apollo Social User Pages

Sistema de páginas customizáveis de usuário com editor drag-and-drop, widgets modulares, depoimentos e UI mobile-first (Tailwind + shadcn + uni.css).

## Estrutura
- CPT `user_page` com comentários como "Depoimentos"
- Rotas públicas: `/id/{userID}` (view), `/id/{userID}?action=edit` (editor)
- Layout salvo em post meta como JSON
- Editor drag-and-drop (Muuri)
- Widgets: About, Depoimentos, Imagem, Canvas Plano
- Segurança: nonce, capability, sanitização
- SEO: canonical, OG tags, noindex para editor
- Build: Tailwind, shadcn tokens, uni.css

## Instalação
1. Ative o plugin Apollo Social
2. Flush rewrite rules (visite Configurações > Links Permanentes)
3. Acesse `/id/{userID}` para visualizar ou `/id/{userID}?action=edit` para editar

## Expansão
- Adicione widgets via filtro `apollo_userpage_widgets`
- Personalize templates em `/templates/user-page-view.php` e `/user-page-editor.php`
- Edite assets do editor em `/user-pages/editor-bundle.js`

## Segurança
- Apenas dono/admin pode editar
- Depoimentos com antispam
- Layout validado e limitado

## Build pipeline
- Recomenda-se usar Vite ou WP Scripts para Tailwind/shadcn local
- uni.css carregado de assets.apollo.rio.br

## Logs
- Edições e depoimentos registrados em user_meta para auditoria
