# 🚀 Apollo Plugins - WordPress Event Management System

**GitHub:** https://github.com/apollorio/plugins  
**Version:** 2.0.0  
**Last Updated:** 2025-01-15  

---

## 📦 PLUGINS NESTE REPOSITÓRIO

### 🎯 apollo-events-manager (PRINCIPAL)
**Status:** ✅ Production Ready  
**Version:** 2.0.0

Sistema completo de gerenciamento de eventos integrado com Apollo.rio.br.

**Funcionalidades Principais:**
- 🎫 Custom Post Types (Events, DJs, Locals)
- 🎨 Force load assets de assets.apollo.rio.br
- 🗺️ Mapas com Leaflet.js + Auto-geocoding
- ⭐ Sistema de favoritos com animações
- 🔍 Filtros em tempo real
- 📱 Design responsivo
- 🌙 Dark mode
- ⚡ AJAX para filtros e lightbox
- 📊 Analytics e dashboards
- 🎨 Canvas Mode (isolamento de tema)

**Documentação:** Ver `apollo-events-manager/README.md`

---

### 📱 apollo-social
**Status:** ✅ Production Ready  
**Version:** 2.0.0

Plugin principal do sistema Apollo que fornece funcionalidades sociais e de Canvas Mode.

**Funcionalidades Principais:**
- 🎨 Canvas Mode: Sistema de renderização isolada
- 👥 Sistema de Grupos: Comunidades e núcleos
- 📄 Sistema de Documentos: Gestão e assinatura digital
- 📊 Analytics: Integração com Plausible Analytics
- 🔐 PWA: Funcionalidades de Progressive Web App
- 🌐 API REST: Endpoints para integração móvel
- 👤 User Pages: Páginas personalizáveis `/id/{userID}`

**Documentação:** Ver `apollo-social/README.md`

---

### 🌐 apollo-rio
**Status:** ✅ Production Ready  
**Version:** 2.0.0

Sistema de templates PWA e Canvas Mode para Apollo::Rio.

**Funcionalidades Principais:**
- 📱 PWA Detection: Detecção automática de modo PWA
- 🎨 Page Builders: Templates Site::rio, App::rio, App::rio clean
- 🚫 Theme Blocking: Previne interferência do tema
- 📄 Templates PWA: Offline support

**Documentação:** Ver `apollo-rio/README.md`

---

## 🛠️ WORKSPACE SETUP

### Abrir Workspace no VSCode/Cursor

**Método 1: Workspace File (Recomendado)**
```
Duplo-clique em: apollo-plugins.code-workspace
```

**Método 2: Open Folder**
```
File > Open Folder...
Selecionar: C:\Users\rafae\Local Sites\1212\app\public\wp-content\plugins
```

---

## 🎯 DESENVOLVIMENTO

### Estrutura Recomendada

```
plugins/
├── .git/                       # Git repository
├── apollo-plugins.code-workspace # VSCode workspace
├── README.md                   # Este arquivo
│
├── apollo-events-manager/      # ← TRABALHAR AQUI
│   ├── apollo-events-manager.php
│   ├── includes/
│   ├── templates/
│   ├── assets/
│   └── README.md
│
├── apollo-social/              # Plugin social
│   ├── apollo-social.php
│   ├── src/
│   ├── templates/
│   └── README.md
│
└── apollo-rio/                 # Plugin PWA
    ├── apollo-rio.php
    ├── includes/
    ├── templates/
    └── README.md
```

---

## 📝 CONVENÇÕES DE CÓDIGO

### PHP (PSR-12 + WordPress Standards)
```php
// Classes: PascalCase com prefixo
class Apollo_Events_Manager_Plugin {}

// Methods: camelCase
public function saveCustomEventFields() {}

// Hooks: snake_case com prefixo
add_action('apollo_events_ajax', ...);

// Constants: UPPER_SNAKE_CASE
define('APOLLO_DEBUG', true);
```

### JavaScript (ES6+)
```javascript
// Functions: camelCase
function toggleLayout(el) {}

// Variables: camelCase
const displayDate = new Date();

// Constants: UPPER_SNAKE_CASE
const MONTH_NAMES = ['jan', 'fev', ...];
```

---

## 🧪 COMANDOS ÚTEIS

### WP-CLI
```bash
# Listar plugins
wp plugin list

# Ativar apollo-events-manager
wp plugin activate apollo-events-manager

# Flush rewrite rules
wp rewrite flush

# Listar CPTs
wp post-type list | grep event

# Contar eventos
wp post list --post_type=event_listing --format=count
```

### Git
```bash
# Status
git status

# Ver mudanças
git diff

# Commit
git add .
git commit -m "feat: Nova funcionalidade"

# Push
git push origin main

# Pull
git pull origin main
```

### Debug
```bash
# Ver últimos erros
tail -20 ../wp-content/debug.log | grep -i "error\|fatal"

# Monitorar log em tempo real
tail -f ../wp-content/debug.log
```

---

## 🔧 CONFIGURAÇÃO

### Debug Mode
```php
// wp-config.php
define('APOLLO_DEBUG', true);
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

### Assets Externos
O apollo-events-manager carrega automaticamente:
- `https://assets.apollo.rio.br/uni.css`
- `https://assets.apollo.rio.br/base.js`
- `https://assets.apollo.rio.br/event-page.js`

---

## 📊 META KEYS REFERENCE

### Events (event_listing)
```php
'_event_dj_ids'        => 'a:2:{i:0;s:2:"92";i:1;s:2:"71";}' // Serialized array
'_event_local_ids'     => 95                                  // Int
'_event_timetable'     => array(...)                          // Array
'_event_banner'        => 'http://...'                        // URL string
'_event_start_date'    => '2025-11-03 22:00:00'              // DateTime
'_favorites_count'     => 42                                  // Int
```

### Locals (event_local)
```php
'_local_latitude'      => -22.9068                            // Float
'_local_longitude'     => -43.1729                            // Float
'_local_address'       => 'Rua X, 123, Rio de Janeiro'       // String
```

---

## 🚫 NÃO USAR

### Removido em v2.0.0
- ❌ `event_organizer` CPT
- ❌ `_event_organizer` meta key
- ❌ `_event_venue` meta key (usar `_event_local_ids`)
- ❌ `_event_djs` meta key (usar `_event_dj_ids`)
- ❌ Terminologia "Venue" (usar "Local")
- ❌ Terminologia "Organizer"

---

## 📚 DOCUMENTAÇÃO

### Documentação por Plugin
- **apollo-events-manager:** Ver `apollo-events-manager/README.md`
- **apollo-social:** Ver `apollo-social/README.md`
- **apollo-rio:** Ver `apollo-rio/README.md`

### Guias de Desenvolvimento
- **DEVELOPMENT.md** - Guia completo de desenvolvimento
- **DEPLOYMENT.md** - Guia de deploy e produção

---

## 🔗 LINKS ÚTEIS

- **Apollo Assets:** https://assets.apollo.rio.br/
- **GitHub Repo:** https://github.com/apollorio/plugins
- **WordPress Codex:** https://developer.wordpress.org/
- **Leaflet Docs:** https://leafletjs.com/
- **RemixIcon:** https://remixicon.com/

---

## 🎯 ROADMAP

### v2.0.0 (ATUAL)
- ✅ Force load assets externos
- ✅ Carregamento condicional de JS
- ✅ Meta keys corrigidos
- ✅ Migration validator
- ✅ Backward compatibility
- ✅ Canvas Mode
- ✅ Analytics e dashboards

### v2.1.0 (PRÓXIMO)
- [ ] Integrar funcionalidades do wpem-bookmarks
- [ ] Integrar funcionalidades do wpem-rest-api
- [ ] Remover dependência do wp-event-manager
- [ ] Adicionar testes automatizados

### v3.0.0 (FUTURO)
- [ ] BuddyPress integration completa
- [ ] Multi-site support
- [ ] Advanced analytics
- [ ] Mobile app API completa

---

## 🆘 SUPORTE

### Issues
Reportar bugs e sugestões: https://github.com/apollorio/plugins/issues

### Desenvolvimento
- **Lead Developer:** Apollo.rio.br Team
- **Contributors:** Ver GitHub

---

## 📄 LICENÇA

GPL v2 or later - https://www.gnu.org/licenses/gpl-2.0.html

---

**Status:** ✅ Production Ready  
**Última Atualização:** 2025-01-15  
**Próxima Release:** v2.1.0  

🚀 **Happy Coding!**
