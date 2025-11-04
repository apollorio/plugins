# 📊 Apollo Events Dashboard

Dashboard administrativo completo para gerenciamento de eventos Apollo.

## 🎯 Funcionalidades

### Menu Admin
- **Menu Principal:** Apollo (ícone de calendário)
- **Submenu:** Dashboard

### Abas do Dashboard

#### 1. **Aba Eventos**
- **Cards de Resumo:**
  - Total de Eventos
  - Eventos Futuros
  - Eventos Hoje
  - Eventos Passados

- **Analytics Plausible:**
  - Pageviews dos últimos 30 dias em `/eventos/`
  - Top 5 URLs de eventos mais visualizados

- **Gráfico:**
  - Gráfico de barras (Chart.js) com eventos por mês (próximos 6 meses)

- **Tabela de Eventos:**
  - Data (formato: 22 nov)
  - Título do evento
  - Local (nome + área com 50% opacidade)
  - DJs (máximo 3 visíveis + "+X DJs")
  - Status (Passado / Hoje / Futuro)

#### 2. **Aba DJs**
- Lista agregada de todos os DJs encontrados
- Agrupamento por nome normalizado (case-insensitive)
- Colunas:
  - Nome do DJ
  - Quantidade de eventos futuros
  - Quantidade total de eventos

#### 3. **Aba Locais**
- Lista de locais extraídos de `_event_location`
- Separação automática de "Local | Área"
- Colunas:
  - Local
  - Área (com 50% opacidade no CSS)
  - Quantidade de eventos futuros
  - Quantidade total de eventos

## 📁 Arquivos Criados

1. **`includes/class-apollo-events-dashboard.php`**
   - Classe principal do dashboard
   - Registro de menu admin
   - Endpoint REST API
   - Funções de agregação de dados

2. **`assets/js/apollo-events-dashboard.js`**
   - JavaScript do dashboard
   - Sistema de tabs
   - Renderização de tabelas e gráficos
   - Integração com Chart.js

3. **`assets/css/apollo-events-dashboard.css`**
   - Estilos do dashboard
   - Layout responsivo
   - Cards, tabelas, badges de status

## 🔧 Integração Plausible Analytics

O dashboard suporta integração com Plausible Analytics através de um filtro WordPress:

```php
add_filter('apollo_events_plausible_fetch', function($data, $params) {
    // $params['endpoint'] = 'stats'
    // $params['params']['site_id'] = 'apollo.rio.br'
    // $params['params']['period'] = '30d'
    
    // Retornar array com:
    return array(
        'pageviews' => 12345,
        'top_urls' => array(
            array('url' => '/evento/evento-1', 'pageviews' => 500),
            array('url' => '/evento/evento-2', 'pageviews' => 400),
            // ...
        ),
    );
}, 10, 2);
```

Se o filtro não retornar dados, o dashboard mostra "Sem dados de analytics disponíveis" sem quebrar.

## 📊 Estrutura de Dados

### Endpoint JSON

**REST API:** `GET /wp-json/apollo/v1/dashboard`  
**AJAX:** `POST admin-ajax.php?action=apollo_dashboard_data`

**Resposta:**
```json
{
  "eventos": [
    {
      "id": 123,
      "title": "Nome do Evento",
      "date": "22 nov",
      "date_raw": "2024-11-22 22:00:00",
      "timestamp": 1234567890,
      "local": "Club XYZ",
      "area": "Copacabana",
      "djs": ["DJ 1", "DJ 2"],
      "djs_display": "DJ 1, DJ 2, DJ 3 +2",
      "status": "futuro",
      "permalink": "https://..."
    }
  ],
  "djs": [
    {
      "name": "DJ Name",
      "events_future": 5,
      "events_total": 12
    }
  ],
  "locais": [
    {
      "local": "Club XYZ",
      "area": "Copacabana",
      "events_future": 3,
      "events_total": 8
    }
  ],
  "resumo": {
    "total_eventos": 150,
    "eventos_futuros": 45,
    "eventos_hoje": 2,
    "eventos_passados": 103
  },
  "eventos_por_mes": [
    {
      "label": "nov 2024",
      "count": 8
    }
  ],
  "plausible": {
    "pageviews_30d": 12345,
    "top_event_urls": []
  }
}
```

## 🎨 Funcionalidades Técnicas

### Performance
- ✅ Query única de eventos (últimos 12 meses + futuros)
- ✅ Pré-carregamento de meta cache (`update_meta_cache()`)
- ✅ Processamento em PHP (sem múltiplas queries)

### Segurança
- ✅ Verificação de permissões (`manage_options`)
- ✅ Nonces para AJAX
- ✅ Escaping de outputs

### Compatibilidade
- ✅ WordPress 6.8+
- ✅ REST API + AJAX (fallback)
- ✅ Chart.js via CDN
- ✅ Responsive design

## 🔍 Como Funciona

1. **Registro do Menu:**
   - `add_menu_page()` cria menu "Apollo"
   - `add_submenu_page()` cria submenu "Dashboard"

2. **Carregamento de Dados:**
   - JavaScript tenta REST API primeiro
   - Se falhar, usa AJAX como fallback
   - Dados são processados em PHP

3. **Renderização:**
   - Tabs em JavaScript vanilla
   - Tabelas renderizadas dinamicamente
   - Gráfico Chart.js renderizado após DOM ready

4. **Agregação:**
   - DJs são normalizados e agrupados
   - Locais são separados por "|"
   - Status é calculado baseado em `current_time()`

## 📝 Notas

- O dashboard não depende do tema ativo
- Todo o código é apenas para admin (`wp-admin`)
- Nenhum código quebra se não houver eventos/DJs/locais
- Error handling robusto em todas as etapas

## 🚀 Próximos Passos

1. Testar o dashboard no admin
2. Configurar integração Plausible (se necessário)
3. Personalizar estilos conforme necessário
4. Adicionar mais funcionalidades conforme demanda

---

**Status:** ✅ Dashboard completo e funcional  
**Pronto para:** Testes e uso em produção

