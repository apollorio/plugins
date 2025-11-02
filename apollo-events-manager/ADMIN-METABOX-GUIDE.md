# 🎛️ Apollo Events - Admin Metabox Guide

**Data:** November 2, 2025  
**Versão:** 1.0.0  
**Status:** ✅ Implementado e Funcional

---

## 📋 RESUMO

Metabox admin melhorado para edição de eventos com:
- ✅ Select múltiplo de DJs
- ✅ Botão "Adicionar novo DJ" com popup
- ✅ Check de duplicata case-insensitive
- ✅ Timetable dinâmico baseado em DJs selecionados
- ✅ Select de Local (não "venue")
- ✅ Botão "Adicionar novo Local" com popup
- ✅ Ordenação automática por horário
- ✅ AJAX para adicionar DJs/Locais

---

## 🏗️ ESTRUTURA CORRETA DO PLUGIN

### CPTs (Custom Post Types)
```php
'event_listing' // Eventos
'event_dj'      // DJs
'event_local'   // Locais (NÃO "venue", NÃO "organizer")
```

### Meta Keys Corretos
```php
// Evento
'_event_dj_ids'      => serialized array ["92","71"] (strings!)
'_event_local_ids'   => numeric (95)
'_event_timetable'   => array [
    ['dj' => 92, 'start' => '22:00', 'end' => '23:00'],
    ['dj' => 71, 'start' => '23:00', 'end' => '00:00']
]
'_event_video_url'   => string (URL)
```

---

## 📁 ARQUIVOS CRIADOS

### 1. `includes/admin-metaboxes.php`
**Função:** Classe principal do metabox admin

**Features:**
- Registra meta box "Apollo Event Details"
- Renderiza campos de DJ, Local e Timetable
- AJAX handlers para adicionar DJ/Local
- Salvamento com validação

**Hooks:**
```php
add_action('add_meta_boxes', 'register_metaboxes');
add_action('save_post_event_listing', 'save_metabox_data');
add_action('admin_enqueue_scripts', 'enqueue_admin_scripts');
add_action('wp_ajax_apollo_add_new_dj', 'ajax_add_new_dj');
add_action('wp_ajax_apollo_add_new_local', 'ajax_add_new_local');
```

### 2. `assets/admin-metabox.js`
**Função:** Lógica JavaScript do admin

**Features:**
- jQuery UI Dialogs para adicionar DJ/Local
- Timetable dinâmico (rebuild quando DJs mudam)
- AJAX para checagem de duplicatas
- Salvar timetable como JSON antes do submit
- Atualização automática dos selects

**Funções principais:**
```javascript
initDialogs()           // Inicializa popups
submitNewDJ()           // AJAX adicionar DJ
submitNewLocal()        // AJAX adicionar Local
rebuildTimetable()      // Reconstrói rows do timetable
saveTimetableToHidden() // Serializa para JSON
```

### 3. `assets/admin-metabox.css`
**Função:** Estilos do admin metabox

**Estilos:**
- Layout limpo e responsivo
- Tabela de timetable estilizada
- Dialogs com boa UX
- Mensagens de erro/sucesso
- Mobile-friendly

---

## 🎯 COMO USAR

### 1. Editar um Evento

1. Vá para **Eventos → Todos os Eventos**
2. Clique em um evento ou **Adicionar novo**
3. Role até **"Apollo Event Details"** metabox

### 2. Adicionar DJs

**Opção A: Selecionar existentes**
1. No campo "DJs", segure **Ctrl/Cmd**
2. Clique em múltiplos DJs
3. Eles aparecem selecionados

**Opção B: Adicionar novo**
1. Clique em **"Adicionar novo DJ"**
2. Digite o nome (ex: "Marta Supernova")
3. Clique em **"Adicionar"**
4. Sistema checa duplicatas (ignora maiúsculas)
5. Se OK: DJ é criado e selecionado automaticamente
6. Se duplicado: Mostra mensagem com slug existente

### 3. Configurar Timetable

1. Selecione os DJs desejados
2. Clique em **"Atualizar Timetable"** (ou espere atualização automática)
3. Tabela mostra:
   - Coluna 1: Nome do DJ (read-only)
   - Coluna 2: **Começa às** (input type="time")
   - Coluna 3: **Termina às** (input type="time")
4. Preencha os horários
5. Ao salvar: Horários são ordenados automaticamente

### 4. Selecionar Local

**Opção A: Selecionar existente**
1. No campo "Local", escolha da lista

**Opção B: Adicionar novo**
1. Clique em **"Adicionar novo Local"**
2. Preencha:
   - Nome do Local (obrigatório)
   - Endereço (opcional, mas recomendado)
   - Cidade (opcional, necessário para geocoding)
3. Clique em **"Adicionar"**
4. Sistema checa duplicatas
5. Se tiver cidade: Auto-geocoding via Nominatim

### 5. Adicionar Vídeo

1. Campo "Event Video URL"
2. Cole URL do YouTube/Vimeo
3. Será exibido no hero da página do evento

### 6. Salvar

1. Clique em **"Publicar"** ou **"Atualizar"**
2. Sistema salva:
   - DJs como array serializado em `_event_dj_ids`
   - Local como ID numérico em `_event_local_ids`
   - Timetable como array ordenado em `_event_timetable`
   - Video URL em `_event_video_url`

---

## ✅ VALIDAÇÕES IMPLEMENTADAS

### DJ Duplicado
```php
// Normaliza
$normalized = mb_strtolower(trim($name), 'UTF-8');

// Compara com existentes
foreach ($existing as $dj) {
    if (mb_strtolower($dj->post_title) === $normalized) {
        wp_send_json_error('DJ já existe');
    }
}
```

### Local Duplicado
```php
// Mesmo processo do DJ
// Case-insensitive
// Checa title e meta _local_name
```

### Timetable
```php
// Ordena por horário de início
usort($timetable, function($a, $b) {
    return strcmp($a['start'], $b['start']);
});
```

---

## 🔄 FLUXO DE DADOS

### Salvamento
```
Admin Form
    ↓
apollo_event_djs[] (POST) → serialize() → _event_dj_ids (meta)
apollo_event_local (POST) → intval() → _event_local_ids (meta)
apollo_event_timetable (JSON) → json_decode() + usort() → _event_timetable (meta)
```

### Carregamento
```
_event_dj_ids (meta) → maybe_unserialize() → array de inteiros
_event_local_ids (meta) → intval() → ID do post
_event_timetable (meta) → array direto
```

---

## 🐛 DEBUG

### Verificar se DJs foram salvos
```php
$dj_ids = get_post_meta($event_id, '_event_dj_ids', true);
$dj_ids = maybe_unserialize($dj_ids);
print_r($dj_ids); // ["92", "71"]
```

### Verificar Timetable
```php
$timetable = get_post_meta($event_id, '_event_timetable', true);
print_r($timetable);
/* [
    ['dj' => 92, 'start' => '22:00', 'end' => '23:00'],
    ['dj' => 71, 'start' => '23:00', 'end' => '00:00']
] */
```

### Console do navegador
```javascript
// Abra DevTools (F12) ao editar evento
// Veja logs de AJAX
// Veja dados do timetable antes de salvar
```

---

## ⚠️ IMPORTANTE

### ❌ O QUE FOI REMOVIDO
- **"Organizer"** - Não é um CPT, foi removido completamente
- **"Venue"** - Substituído por "Local" em toda UI

### ✅ O QUE PERMANECE
- **3 CPTs apenas:** event_listing, event_dj, event_local
- **Meta keys corretos:** `_event_dj_ids`, `_event_local_ids`, `_event_timetable`
- **Formato do banco:** Serialized arrays, não JSON

---

## 📝 PRÓXIMOS PASSOS (Futuro)

- [ ] Upload de foto do DJ no popup
- [ ] Preview do timetable visual
- [ ] Drag & drop para reordenar DJs
- [ ] Geocoding preview no popup do Local
- [ ] Validação de horários (não sobrepor)

---

## 🎉 STATUS FINAL

✅ **Metabox admin completamente funcional**  
✅ **Sem referências a "organizer" ou "venue"**  
✅ **Duplicatas checadas (case-insensitive)**  
✅ **Timetable dinâmico e ordenado**  
✅ **AJAX funcionando**  
✅ **Código limpo e documentado**

**Pronto para uso em produção!** 🚀

