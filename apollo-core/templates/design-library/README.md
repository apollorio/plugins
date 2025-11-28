# Apollo Design Library

## Propósito

Esta é uma **biblioteca de referência de design** para o assistente Cursor/AI.
NÃO é um sistema automático de templates - é uma coleção de HTML aprovados que o assistente deve LER e usar como base para criar código PHP que reproduza fielmente o design.

## Como Funciona

1. **Arquivos `.html`** neste diretório são templates de design aprovados
2. Quando preciso criar uma página PHP, eu (assistente) LEIO o HTML de referência
3. Eu então ESCREVO código PHP que gera o mesmo output, mas com dados dinâmicos
4. Os placeholders no HTML (como `{{user_name}}`) indicam onde dados dinâmicos devem entrar

## Estrutura de Arquivos

```
design-library/
├── README.md                    # Este arquivo
├── _index.json                  # Índice de todos os templates
├── _tokens.css                  # Design tokens compartilhados
├── _components.html             # Componentes reutilizáveis
├── feed-social.html             # Template: Feed Social Apollo
├── cena-rio-calendar.html       # Template: Calendário CENA-RIO
├── event-card.html              # Componente: Card de evento
├── sidebar-nav.html             # Componente: Navegação lateral
└── ...
```

## Convenções

### Placeholders de Dados
```html
{{variable_name}}              <!-- Variável simples -->
{{#loop items}}...{{/loop}}    <!-- Loop/iteração -->
{{#if condition}}...{{/if}}    <!-- Condicional -->
{{user.name}}                  <!-- Propriedade de objeto -->
```

### Marcadores de Seção
```html
<!-- @section:header -->
<!-- @section:sidebar -->
<!-- @section:content -->
<!-- @section:footer -->
```

### Componentes Reutilizáveis
```html
<!-- @component:event-card -->
<!-- @component:user-avatar -->
```

## Uso pelo Assistente

Quando o usuário pedir para criar uma página com um design específico:

1. **LEIA** o arquivo HTML de referência correspondente
2. **IDENTIFIQUE** as seções, componentes e placeholders
3. **ESCREVA** código PHP que:
   - Use `wp_head()`, `wp_footer()` se for página WordPress
   - Busque dados reais do banco/API
   - Use `esc_html()`, `esc_attr()` para escapar output
   - Mantenha EXATAMENTE o mesmo CSS/classes/estrutura
4. **NUNCA** altere o design visual sem permissão explícita

## Exemplo de Conversão

### HTML de Referência
```html
<div class="event-card">
  <h3>{{event.title}}</h3>
  <span class="date">{{event.date}}</span>
</div>
```

### PHP Gerado
```php
<?php foreach ( $events as $event ) : ?>
<div class="event-card">
  <h3><?php echo esc_html( $event->post_title ); ?></h3>
  <span class="date"><?php echo esc_html( get_post_meta( $event->ID, '_event_date', true ) ); ?></span>
</div>
<?php endforeach; ?>
```

## Adicionando Novos Templates

Use o marcador `#@#@#@` seguido do nome do template:

```
#@#@#@nome-do-template descrição

<!DOCTYPE html>
...
```

O assistente irá extrair e salvar como `nome-do-template.html`.

---

**IMPORTANTE**: Esta biblioteca é para USO INTERNO do assistente.
Não há parsing automático - o assistente lê e interpreta manualmente.

