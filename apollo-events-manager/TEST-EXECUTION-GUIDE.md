# 🧪 Guia de Execução de Testes - Apollo Events Manager

## 🚀 Execução Rápida

### 1. Teste Completo (Debug Test)
**URL:** `http://localhost:10004/wp-content/plugins/apollo-events-manager/tests/debug-test.php`

Este teste verifica:
- ✅ Conexão com banco de dados
- ✅ Custom Post Types
- ✅ Shortcodes
- ✅ Meta keys
- ✅ AJAX handlers
- ✅ Templates
- ✅ Assets
- ✅ User roles

**Resultado:** Relatório HTML completo com status de cada teste

---

### 2. Teste de Banco de Dados
**URL:** `http://localhost:10004/wp-content/plugins/apollo-events-manager/tests/db-test.php`

Este teste verifica:
- ✅ Conexão direta com MySQL (localhost:10005)
- ✅ Tabelas do WordPress
- ✅ Posts por CPT
- ✅ Meta keys canônicas
- ✅ Meta keys legadas (para migração)

**Configuração:**
- Host: `localhost:10005`
- Database: `local`
- User: `root`
- Pass: `root`

---

### 3. Verificação de Páginas
**URL:** `http://localhost:10004/wp-content/plugins/apollo-events-manager/tests/page-verification.php`

Este teste verifica:
- ✅ Shortcodes e seus outputs
- ✅ Páginas de Custom Post Types
- ✅ Páginas de arquivo
- ✅ Arquivos de template

**Resultado:** Lista completa com links para testar cada página

---

## 🔧 Configuração Xdebug

### Verificar Status:
Acesse qualquer arquivo de teste e verifique se aparece:
```
Xdebug: ✅ Ativo
```

### Configurações Ativas:
```
xdebug.mode: debug,develop
xdebug.start_with_request: yes
xdebug.max_nesting_level: 256
xdebug.max_stack_frames: -1
xdebug.output_dir: C:\Windows\Temp
```

---

## 📊 Interpretando Resultados

### Status dos Testes:
- 🟢 **PASS** - Teste passou com sucesso
- 🔴 **FAIL** - Teste falhou, ação necessária
- 🟡 **WARNING** - Aviso, pode ser normal
- 🔵 **INFO** - Informação adicional

### Taxa de Sucesso Esperada:
- **100%** - Sistema pronto para produção ✅
- **90-99%** - Pequenos ajustes necessários ⚠️
- **< 90%** - Revisão necessária antes do deploy ❌

---

## ✅ Checklist de Validação

Execute todos os testes e verifique:

### Teste 1: Debug Test
- [ ] Conexão com banco: ✅ PASS
- [ ] Todos os CPTs: ✅ PASS
- [ ] Todos os shortcodes: ✅ PASS
- [ ] Meta keys canônicas: ✅ PASS
- [ ] AJAX handlers: ✅ PASS
- [ ] Templates: ✅ PASS
- [ ] Assets: ✅ PASS
- [ ] User role clubber: ✅ PASS

### Teste 2: Database Test
- [ ] Conexão MySQL: ✅ PASS
- [ ] Tabelas WordPress: ✅ OK
- [ ] Posts por CPT: ✅ OK
- [ ] Meta keys canônicas: ✅ OK
- [ ] Meta keys legadas: ⚠️ Verificar migração

### Teste 3: Page Verification
- [ ] Todos os shortcodes: ✅ REGISTRADO
- [ ] Outputs de shortcodes: ✅ OK
- [ ] Páginas de CPTs: ✅ OK
- [ ] Templates: ✅ EXISTE

---

## 🐛 Troubleshooting

### Erro: "WordPress not found"
**Solução:** Execute os testes via browser, não via CLI

### Erro: "Connection failed"
**Solução:** Verifique as credenciais do banco em `db-test.php`

### Erro: "Shortcode not registered"
**Solução:** Certifique-se de que o plugin está ativo

### Xdebug não aparece
**Solução:** Verifique `php.ini` e reinicie o servidor

---

## 📝 Próximos Passos Após Testes

1. ✅ Se todos os testes passarem → **Pronto para deploy**
2. ⚠️ Se alguns testes falharem → **Revisar e corrigir**
3. 🔄 Executar testes novamente após correções
4. 🚀 Fazer deploy em produção

---

**Última Execução:** Execute os testes antes de cada deploy

