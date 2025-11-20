# 🚀 Apollo Rio - Guia de Lançamento

## ✅ Pré-requisitos Confirmados

Todos os plugins Apollo estão otimizados e prontos para produção!

---

## 🔧 Configuração Pós-Deploy

### 1. Acessar Painel de Configurações

1. WordPress Admin → **Apollo Events** → **Configurações**
2. Configure:
   - **URL do Banner Fallback:** URL da imagem padrão para eventos sem banner
   - **Usar Animação de Loading:** ✅ Recomendado (deixar ativado)
3. Clique em **Salvar Configurações**

---

### 2. Verificar Páginas Criadas

As seguintes páginas foram criadas automaticamente na ativação:

| Slug | Título | Template | Função |
|------|--------|----------|--------|
| `eventos` | Eventos | `portal-discover.php` | Lista todos eventos |
| `cenario-new-event` | Criar Evento | `page-cenario-new-event.php` | Submissão pública |
| `mod-events` | Moderação | `page-mod-events.php` | Aprovar/rejeitar drafts |
| `event-dashboard` | Dashboard | `page-event-dashboard.php` | Overview de eventos |

**Verificar:** WordPress Admin → Páginas

---

### 3. Limpar Cache

Execute **UMA VEZ** após deploy:

```bash
wp cache flush
wp rewrite flush
```

Ou via PHP:

```php
wp_cache_flush();
flush_rewrite_rules();
```

---

## 🎨 Recursos Implementados

### 🚀 Rocket Favorite Button

**Onde:** Todos os event cards (topo direito)

**Como funciona:**
1. Usuário clica no 🚀 rocket icon
2. Sistema salva evento como "interessado" (favorito)
3. Ícone muda de `ri-rocket-line` (vazio) para `ri-rocket-fill` (cheio)
4. Animação de pulse confirma ação

**Dados salvos:**
- Meta do user: `apollo_favorites` (array de IDs)
- Meta do event: `_favorites_count` (contador)

---

### ⏳ Loading Animation

**Onde:** Ao carregar imagens de eventos

**Configurável em:** Apollo Events → Configurações

**Opções:**
1. **Animação (Padrão):** 3 anéis rotacionando + pulse central
2. **Imagem Fallback:** URL configurável no admin

**Design baseado em:** [CodePen bNpRoPe](https://codepen.io/Rafael-Valle-the-looper/pen/bNpRoPe)

---

### 📋 Formulários de Submissão

#### Formulário Completo (`/cenario-new-event`)

**Campos disponíveis:**
- Título do Evento *
- Descrição
- Data Início *
- Hora Início
- Data Fim
- Hora Fim
- DJs (seleção múltipla)
- Local (seleção)
- Banner (upload)
- Vídeo Teaser (URL)
- Link de Ingressos (URL)
- Cupom de Desconto
- Categorias
- Genres/Sounds

**Ação:** Salva como **draft** para moderação

#### Painel de Moderação (`/mod-events`)

**Funcionalidades:**
- Lista todos eventos em **draft** futuros
- Botão **Aprovar** (verde) → publica evento
- Botão **Rejeitar** (vermelho) → mantém como draft
- Link **Editar** → vai para admin

**Permissões:** Apenas editores e admins

---

## 🎯 Shortcodes Disponíveis

### `[events]`
Exibe lista de eventos usando `event-card.php`

```php
[events limit="10"]
[events category="techno"]
[events local="dedge"]
```

### `[apollo_events]`
Mesma função, usa `event-card.php` também

```php
[apollo_events limit="20"]
```

---

## 🔍 Troubleshooting

### Event cards não aparecem?

1. Verifique se eventos estão **publicados** (não draft)
2. Verifique se data de início está no **futuro**
3. Limpe cache: `wp cache flush`

### Rocket button não funciona?

1. Verifique se usuário está **logado**
2. Abra DevTools → Console e veja erros
3. Verifique se `apollo-favorites.js` está carregado

### Imagens não carregam?

1. Verifique meta key `_event_banner` (deve ser URL, não ID)
2. Configure fallback em: Apollo Events → Configurações
3. Habilite "Usar Animação de Loading"

### SiteOrigin dependency error?

✅ **JÁ RESOLVIDO!** SiteOrigin agora é **OPCIONAL**

O Builder funciona sem SiteOrigin usando `renderAbsoluteLayout()`

---

## 📈 Performance Tips

### Cache

O sistema usa 3 níveis de cache:

1. **Object Cache** (grupo `apollo_events`)
2. **Transients** (queries complexas)
3. **Post Cache** (WordPress core)

**Auto-clear:** Cache é limpo automaticamente ao salvar/deletar eventos

### Loading Optimization

- ✅ `loading="lazy"` em todas imagens
- ✅ CSS inline para above-the-fold
- ✅ JS carregado no footer
- ✅ RemixIcons CDN (cache distribuído)
- ✅ uni.css CDN (cache global)

---

## 🎊 Próximas Features (Roadmap)

### Fase 2 (Pós-lançamento)

- [ ] PWA completo com service worker
- [ ] Notificações push para novos eventos
- [ ] Chat/messaging system (ShadCN sidebar-09)
- [ ] Documentos assinados digitalmente (GOV.BR full integration)
- [ ] Analytics dashboard (Plausible integration)

---

## 🆘 Suporte

**Documentação completa:** Ver arquivos `.md` em cada plugin

**Principais arquivos de referência:**
- `APOLLO-FINAL-RELEASE-NOTES.md` - Este arquivo
- `PLUGIN-SUMMARY.md` - Resumo do apollo-events-manager
- `SHORTCODES-GUIDE.md` - Guia de shortcodes
- `RESUMO-CORRECOES-COMPLETAS.md` - Correções aplicadas

---

**Apollo::Rio** - Powered by WordPress ❤️ ShadCN ❤️ Tailwind ❤️ RemixIcon

🎉 **BOA SORTE NO LANÇAMENTO!** 🎉

