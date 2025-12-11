Fase 1 – Ler e entender todos os HTMLs aprovados

Objetivo: mapear tudo que existe de UI, sem mexer em nenhum plugin ainda.

Arquivos que você já tem em apollo-core/templates/design-library/:

body_eventos ----list all.html

body_evento_eventoID ----single page.html

body_dj_djID ----single page.html

body_docs_editor.html

layout_fornecedores ----list all.html

main_cena-rio_agenda.html

main_docs ----list all.html

main_doc_sign ----single page.html

main_groups ----list all.html

main_groups_groupID ----single page.html

etc.

O que fazer:

Abrir todos esses HTMLs e anotar (nem que seja em um TXT):

Navbar padrão apollo-social (top bar).

Sidebar esquerda padrão.

Bottom mobile nav.

Dropdowns (notif, apps, profile).

Componentes: cards de evento, card de fornecedor, card de grupo, linhas de docs, chips, filtros, etc.

Scripts usados: dark mode, relógio, dropdowns, scroll de mensagens, filtros de fornecedores, search, etc.

Identificar duplicações:
Ex.: mesmo CSS ou mesmo JS repetido em vários arquivos.

Nada de código ainda, só entendimento.

“Liste todos os padrões visuais e blocos de UI recorrentes neste HTML (navbar, sidebar, mobile nav, cards, filtros, dropdowns, etc.) e descreva-os por nome + seletor principal + onde aparecem.”
