# 🚀 Apollo Plugins - WordPress Event Management System

**GitHub:** https://github.com/apollorio/plugins  
**Version:** 2.0.0  
**Last Updated:** 2025-11-03  

---

## 📦 PLUGINS NESTE REPOSITÓRIO

### 🎯 apollo-events-manager (PRINCIPAL)
**Status:** ✅ Active Development  
**Version:** 2.0.0  

Sistema completo de gerenciamento de eventos integrado com Apollo.rio.br.

**Funcionalidades:**
- 🎫 Custom Post Types (Events, DJs, Locais)
- 🎨 Force load assets de assets.apollo.rio.br
- 🗺️ Mapas com Leaflet.js + Auto-geocoding
- ⭐ Sistema de favoritos com animações
- 🔍 Filtros em tempo real
- 📱 Design responsivo
- 🌙 Dark mode
- ⚡ AJAX para filtros e lightbox

**Documentação:** `apollo-events-manager/docs/`

---

### 📚 wp-event-manager
**Status:** ⚠️ Being Replaced  
**Version:** 3.1.x  

Plugin base que está sendo substituído pelo apollo-events-manager.

**Nota:** Mantido apenas para backward compatibility temporária.

---

### 🔖 wpem-bookmarks
**Status:** 🔄 Dependency  
**Version:** 1.x  

Extensão do WP Event Manager para sistema de bookmarks.

**Nota:** Funcionalidade será integrada ao apollo-events-manager.

---

### 🌐 wpem-rest-api
**Status:** 🔄 Dependency  
**Version:** 1.x  

API REST para WP Event Manager.

**Nota:** Funcionalidade será integrada ao apollo-events-manager.

---

## 🛠️ WORKSPACE SETUP

### Abrir Workspace no VSCode/Cursor

**Método 1: Workspace File**
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
├── .copilot-instructions.md    # Copilot context
├── apollo-plugins.code-workspace # VSCode workspace
├── README.md                   # Este arquivo
│
├── apollo-events-manager/      # ← TRABALHAR AQUI
│   ├── apollo-events-manager.php
│   ├── includes/
│   ├── templates/
│   ├── assets/
│   └── docs/
│
├── wp-event-manager/           # Legacy
├── wpem-bookmarks/             # Legacy
└── wpem-rest-api/              # Legacy
```

---

## 📝 CONVENÇÕES DE CÓDIGO

### PHP (PSR-12)
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

### Apollo Events Manager
- [Migration Plan](apollo-events-manager/docs/MIGRATION-PLAN-V2-FINAL.md)
- [Assets Verification](apollo-events-manager/docs/ASSETS-VERIFICATION-REPORT.md)
- [Copilot Context](apollo-events-manager/docs/COPILOT-CONTEXT.md)
- [Quick Start](apollo-events-manager/docs/QUICK-START-MIGRATION.md)

### Workspace
- [Copilot Instructions](.copilot-instructions.md)
- [Workspace File](apollo-plugins.code-workspace)

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

### v2.1.0 (PRÓXIMO)
- [ ] Integrar funcionalidades do wpem-bookmarks
- [ ] Integrar funcionalidades do wpem-rest-api
- [ ] Remover dependência do wp-event-manager
- [ ] Adicionar testes automatizados

### v3.0.0 (FUTURO)
- [ ] BuddyPress integration
- [ ] Multi-site support
- [ ] Advanced analytics
- [ ] Mobile app API

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
**Última Atualização:** 2025-11-03  
**Próxima Release:** v2.1.0  

🚀 **Happy Coding!**


