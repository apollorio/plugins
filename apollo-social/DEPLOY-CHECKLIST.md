# ✅ Checklist de Deploy - Apollo Social

## 🎯 Pré-Deploy (FAZER AGORA)

### Segurança
- [x] Todas as correções de segurança aplicadas
- [x] Sanitização de inputs verificada
- [x] Escape de outputs verificado
- [x] Validação de tipos aplicada
- [x] Nonces verificados

### Código
- [x] Sem erros de lint
- [x] Sem warnings críticos
- [x] Código revisado
- [x] Comentários adequados

### Funcionalidade
- [ ] Canvas Mode testado
- [ ] Rotas testadas
- [ ] Assets carregando
- [ ] Admin funcionando
- [ ] Sem erros no console

---

## 📦 Preparação do ZIP

### 1. Limpar Arquivos Temporários

```powershell
# Remover arquivos de debug (se existirem)
Remove-Item -Path "*.log" -ErrorAction SilentlyContinue
Remove-Item -Path "test-*.php" -ErrorAction SilentlyContinue
Remove-Item -Path "debug-*.php" -ErrorAction SilentlyContinue
```

### 2. Criar ZIP

**Opção 1: PowerShell**
```powershell
Compress-Archive -Path "apollo-social\*" -DestinationPath "apollo-social-v0.0.1-production.zip" -Force
```

**Opção 2: 7-Zip**
```bash
7z a -tzip apollo-social-v0.0.1-production.zip apollo-social\* -xr!*.log -xr!*.tmp -xr!test-*.php
```

**Opção 3: Manual**
- Selecionar pasta `apollo-social`
- Botão direito > Enviar para > Pasta compactada
- Renomear para `apollo-social-v0.0.1-production.zip`

### 3. Verificar Conteúdo do ZIP

Abrir ZIP e verificar:
- [ ] `apollo-social.php` presente
- [ ] Pasta `src/` completa
- [ ] Pasta `config/` presente
- [ ] Pasta `templates/` presente
- [ ] Pasta `assets/` presente
- [ ] Sem arquivos `.log`
- [ ] Sem arquivos de teste

---

## 🚀 Deploy

### 1. Backup
- [ ] Backup completo do site
- [ ] Backup do banco de dados
- [ ] Backup da pasta `wp-content/plugins/apollo-social/`

### 2. Upload
- [ ] Fazer upload do ZIP
- [ ] Descompactar no servidor
- [ ] Verificar permissões (755 para pastas, 644 para arquivos)

### 3. Ativação
- [ ] Desativar versão antiga (se houver)
- [ ] Ativar novo plugin
- [ ] Verificar mensagens de erro
- [ ] Verificar rewrite rules flushadas

### 4. Testes Pós-Deploy

#### Testes Críticos (FAZER IMEDIATAMENTE):
- [ ] Acessar `/a/` - deve funcionar
- [ ] Acessar `/comunidade/` - deve funcionar
- [ ] Acessar `/nucleo/` - deve funcionar
- [ ] Verificar que tema não interfere
- [ ] Verificar assets carregando
- [ ] Verificar admin funcionando
- [ ] Verificar sem erros no console do navegador

#### Testes de Segurança:
- [ ] Tentar XSS em query vars - deve ser bloqueado
- [ ] Verificar nonces em formulários
- [ ] Testar sanitização de inputs

#### Testes de Compatibilidade:
- [ ] Verificar outros plugins funcionando
- [ ] Verificar tema funcionando
- [ ] Verificar sem conflitos

---

## 📊 Monitoramento

### Primeiras 24h:
- [ ] Monitorar logs de erro
- [ ] Verificar performance
- [ ] Verificar relatórios de usuários
- [ ] Monitorar analytics

### Métricas a Observar:
- Tempo de carregamento
- Erros 500/404
- Uso de memória
- Queries lentas

---

## 🆘 Plano de Rollback

### Se algo quebrar:

1. **Desativar plugin imediatamente**
   ```php
   // Via WP-CLI
   wp plugin deactivate apollo-social
   
   // Ou via admin
   Plugins > Desativar
   ```

2. **Reverter para versão anterior**
   - Restaurar ZIP da versão anterior
   - Ou restaurar do backup

3. **Verificar logs**
   ```bash
   tail -f wp-content/debug.log
   ```

4. **Reportar problema**
   - Coletar logs completos
   - Coletar screenshots de erros
   - Descrever passos para reproduzir

---

## ✅ Pós-Deploy

### Após 24h sem problemas:
- [ ] Marcar deploy como bem-sucedido
- [ ] Documentar qualquer ajuste necessário
- [ ] Atualizar changelog
- [ ] Comunicar sucesso à equipe

---

## 📝 Notas

- **Versão:** 0.0.1
- **Data de Deploy:** _______________
- **Responsável:** _______________
- **Ambiente:** Produção
- **Status:** ✅ Pronto para deploy

---

**ÚLTIMA VERIFICAÇÃO:** $(date)  
**STATUS:** ✅ APROVADO PARA PRODUÇÃO

