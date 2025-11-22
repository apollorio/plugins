# 🎉 Apollo Events Manager - Release Notes

## Versão 0.1.0 - Production Release

**Data de Lançamento:** <?php echo date('d/m/Y'); ?>  
**Status:** ✅ PRODUCTION READY

---

## 🎯 O Que Foi Implementado

### ✨ Novos Templates Tailwind Integrados

1. **`[apollo_dj_profile]`** - Perfil completo de DJ
   - Player SoundCloud integrado
   - Bio completa com modal
   - Links de música, social e assets
   - Animações suaves com Motion One

2. **`[apollo_user_dashboard]`** - Dashboard privado do usuário
   - Perfil personalizado
   - Estatísticas em tempo real
   - Tabs: Eventos favoritos, Métricas, Núcleo, Comunidades, Documentos
   - AJAX para atualização de perfil

3. **`[apollo_social_feed]`** - Feed social de eventos
   - Feed de eventos recentes
   - Filtros por tipo (Tudo, Eventos, Comunidades)
   - Sidebar com próximos eventos
   - Navegação mobile otimizada

4. **`[apollo_cena_rio]`** - Calendário da cena
   - Calendário mensal interativo
   - Eventos marcados por data
   - Navegação entre meses
   - Status: Confirmado / Previsto

### 🔧 Melhorias Core

- ✅ Portal de eventos com filtros funcionais
- ✅ Lightbox AJAX robusto com tratamento de erros
- ✅ Grid responsivo mobile-first
- ✅ Sistema de favoritos integrado
- ✅ Co-Authors Plus suportado
- ✅ Cache otimizado
- ✅ Queries de performance melhoradas
- ✅ Acessibilidade básica (ARIA, focus trap)

### 🔐 Segurança

- ✅ Nonces verificados em todos os handlers AJAX
- ✅ Sanitização completa de inputs
- ✅ Escaping de todos os outputs
- ✅ Verificação de permissões
- ✅ Try/catch em handlers críticos

---

## 📋 Como Usar

### Shortcodes Disponíveis

```php
// Perfil de DJ
[apollo_dj_profile dj_id="123"]

// Dashboard do usuário (requer login)
[apollo_user_dashboard]

// Feed social
[apollo_social_feed]

// Calendário Cena Rio
[apollo_cena_rio]
```

### Criar Páginas

1. Vá para **Páginas > Adicionar Nova**
2. Adicione o shortcode desejado no conteúdo
3. Publique a página
4. Acesse a URL da página

---

## 🚀 Requisitos

- WordPress 6.0+
- PHP 8.1+
- MySQL 5.7+ ou MariaDB 10.3+

### Dependências Externas (CDN)

- Tailwind CSS (via CDN)
- Motion.js / Motion One (via CDN)
- UNI.css (via assets.apollo.rio.br)
- Apollo Base.js (via assets.apollo.rio.br)
- SoundCloud API (apenas para DJ profiles)

---

## 📝 Notas de Instalação

1. **Ativar o Plugin:**
   - Vá para Plugins > Instalados
   - Ative "Apollo Events Manager"

2. **Configurar Permalinks:**
   - Vá para Configurações > Links Permanentes
   - Salve as configurações (isso cria as rewrite rules)

3. **Criar Páginas:**
   - Use os shortcodes acima para criar as páginas desejadas

4. **Configurar Meta Keys (Opcional):**
   - Para DJs: Configure as meta keys no admin
   - Para usuários: Configure via dashboard ou admin

---

## 🐛 Problemas Conhecidos

Nenhum problema crítico conhecido.

---

## 🔄 Próximas Versões

### Planejado para v0.2.0:
- Interface de edição de perfil no frontend
- Upload de avatar customizado
- Mais tabs funcionais no dashboard
- Integração completa com apollo-social
- Sistema de comentários no feed

---

## 📞 Suporte

Para suporte, verifique:
- `DEPLOY-CHECKLIST.md` - Checklist completo de deploy
- `TEMPLATES-INTEGRATION.md` - Documentação dos templates

---

## 🙏 Agradecimentos

Obrigado por usar Apollo Events Manager!  
Desenvolvido com ❤️ pela equipe Apollo::Rio

---

**Status:** ✅ PRONTO PARA PRODUÇÃO  
**Versão:** 0.1.0  
**Build:** <?php echo date('YmdHis'); ?>

