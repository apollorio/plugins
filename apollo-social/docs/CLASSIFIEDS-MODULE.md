# Apollo Classificados Module

## Visão Geral

O módulo de Classificados do Apollo Social implementa um sistema de anúncios focado em **segurança** e **confiança**, com duas categorias principais:

- **Ingressos** - Revenda e procura de ingressos para eventos
- **Acomodação** - Ofertas e buscas de hospedagem

## Princípios de Design

1. **Sem pagamentos na plataforma** - Conexão apenas via chat interno
2. **Safety-first UX** - Modal de segurança obrigatório antes do contato
3. **Trust-forward messaging** - Mensagens contextualizadas com referência ao anúncio
4. **UI em pt-BR** - Interface totalmente em português

## Estrutura de Arquivos

```
src/Modules/Classifieds/
├── ClassifiedsModule.php          # CPT, taxonomias, REST API
├── ClassifiedsServiceProvider.php # Bootstrap do módulo

templates/classifieds/
├── single.php                     # Página de detalhe do anúncio
├── archive.php                    # Diretório de anúncios
├── shortcode-directory.php        # Shortcode de listagem
├── shortcode-form.php             # Shortcode de formulário
└── partials/
    ├── card.php                   # Card de anúncio
    └── safety-modal.php           # Modal de segurança

assets/
├── css/classifieds.css            # Estilos
└── js/classifieds.js              # Interações
```

## Custom Post Type

### `apollo_classified`

- **Rewrite slug**: `/anuncio`
- **Archive slug**: `/anuncios`
- **Supports**: title, editor, author, thumbnail, excerpt, custom-fields

## Taxonomias

### `classified_domain` (Categoria)
- `ingressos` - Ingressos
- `acomodacao` - Acomodação

### `classified_intent` (Tipo de Anúncio)
- `ofereco` - Ofereço
- `procuro` - Procuro

## Meta Fields

| Key | Tipo | Descrição |
|-----|------|-----------|
| `_classified_price` | string | Preço em reais |
| `_classified_currency` | string | Moeda (BRL) |
| `_classified_location` | string | Local/cidade |
| `_classified_event_date` | string | Data do evento (YYYYMMDD) |
| `_classified_event_title` | string | Nome do evento |
| `_classified_start_date` | string | Check-in (YYYYMMDD) |
| `_classified_end_date` | string | Check-out (YYYYMMDD) |
| `_classified_capacity` | int | Capacidade de pessoas |
| `_classified_views` | int | Contador de views |
| `_classified_gallery` | array | IDs das imagens da galeria |

## REST API Endpoints

Base: `/wp-json/apollo/v1`

### Listagem
```
GET /classificados
```

**Parâmetros:**
- `page` (int) - Página atual
- `per_page` (int) - Itens por página (máx 50)
- `domain` (string) - ingressos | acomodacao
- `intent` (string) - ofereco | procuro
- `search` (string) - Busca por texto
- `location` (string) - Filtro por local
- `date_from` (string) - Data inicial (YYYY-MM-DD)
- `date_to` (string) - Data final (YYYY-MM-DD)

### Detalhe
```
GET /classificados/{id}
```

### Criar
```
POST /classificados
```
**Requer autenticação**

**Body:**
```json
{
  "title": "2 ingressos Lollapalooza",
  "description": "Vendo 2 ingressos pista...",
  "domain": "ingressos",
  "intent": "ofereco",
  "price": "500.00",
  "location": "São Paulo, SP",
  "event_date": "2025-03-28",
  "event_title": "Lollapalooza 2025"
}
```

### Atualizar
```
PUT /classificados/{id}
```
**Requer autenticação + ser autor**

### Excluir
```
DELETE /classificados/{id}
```
**Requer autenticação + ser autor**

### Denunciar
```
POST /classificados/{id}/reportar
```
**Requer autenticação**

**Body:**
```json
{
  "reason": "fraude",
  "details": "Descrição opcional"
}
```

### Safety Acknowledgement
```
POST /classificados/{id}/safety-ack
```
**Requer autenticação**

Registra que o usuário visualizou o modal de segurança.

## Shortcodes

### Diretório
```
[apollo_classifieds domain="" intent="" per_page="12"]
```

### Formulário de Criação
```
[apollo_classified_form]
```

## Integração com Chat

O módulo se integra com o Apollo Chat para iniciar conversas contextualizadas:

```javascript
window.ApolloChat.openConversation({
  recipientId: authorId,
  contextType: 'classified',
  contextId: postId,
  prefilledMessage: 'Olá! Vi seu anúncio...'
});
```

## Rate Limiting

- Máximo de **5 anúncios por dia** por usuário
- Anúncios com **3+ denúncias** são movidos para pendente

## Segurança

### Safety Modal

Antes de iniciar contato, usuários devem visualizar e confirmar o modal de segurança que inclui:

1. Dicas de prevenção a golpes
2. Orientações sobre encontros presenciais
3. Aviso de que a Apollo não processa pagamentos
4. Incentivo ao uso do chat interno

### Tracking

- Views são contabilizadas em `_classified_views`
- Acknowledgements são armazenados no user meta `_classified_safety_ack`
- Denúncias em `_classified_reports`

## Exemplo de Uso

### Criar página de classificados

1. Crie uma página no WordPress
2. Adicione o shortcode `[apollo_classifieds]`

### Criar página para anunciar

1. Crie uma página "Criar Anúncio"
2. Adicione o shortcode `[apollo_classified_form]`

### Acessar via URL direta

- Listagem: `/anuncios/`
- Por categoria: `/anuncios/?domain=ingressos`
- Anúncio: `/anuncio/meu-anuncio/`

## Changelog

### 2.2.0
- Implementação inicial do módulo
- CPT `apollo_classified` com taxonomias domain/intent
- REST API completa
- Templates com safety modal
- Integração com Apollo Chat
