# PRÓXIMOS PASSOS — APOLLO SOCIAL 2.3.0

**Após Deployment Bem-Sucedido**

---

## ⏱️ TIMELINE

### Imediatamente (30-60 min pós-deploy)

```bash
# 1. Monitoring ativo
tail -f wp-content/debug.log | grep "Apollo"

# 2. Health checks
wp apollo schema:status
wp db check

# 3. Verificar rate limits em ação
wp transient list | grep rate_limit

# 4. Confirm grupo distribution
wp db query "SELECT group_type, COUNT(*) FROM wp_apollo_groups GROUP BY group_type;"
```

### Primeiras 24 horas

- ☐ Monitor error logs hourly
- ☐ Track API response times
- ☐ Watch rate limit metrics
- ☐ Check for user complaints
- ☐ Verify database backups created

### 48-72 horas

- ☐ Performance baseline established
- ☐ No data corruption detected
- ☐ Rate limiting appropriate
- ☐ Security no incidents

---

## 🔄 FEEDBACK LOOP

### Métricas a Acompanhar

**Database Performance**
```bash
# Query latency (should be <100ms)
wp eval "
\$start = microtime(true);
\$result = \$GLOBALS['wpdb']->get_results('
  SELECT * FROM wp_apollo_groups
  WHERE group_type = \"nucleo\"
  LIMIT 100'
);
echo 'Query time: ' . round((microtime(true) - \$start)*1000, 2) . 'ms';
"
```

**API Response Time**
```bash
# REST endpoint performance
time curl -s http://site.local/wp-json/apollo/v1/comunas > /dev/null
```

**Error Rate**
```bash
# Count errors in last hour
grep -c "error\|Error\|ERROR" wp-content/debug.log | tail -20
```

**Rate Limiting**
```bash
# Check active rate-limit transients
wp transient list | grep -c "rate_limit"
```

---

## 📊 DECISION POINTS

### Se Encontrar Problemas

#### ❌ Schema Migration Failed
→ Veja DEPLOYMENT-RUNBOOK-2-3-0.md§Rollback§Schema Upgrade Failed

#### ❌ Rate Limiting Too Aggressive
→ Veja DEPLOYMENT-RUNBOOK-2-3-0.md§Troubleshooting§Rate Limiting

#### ❌ Nonce Validation Errors
→ Veja DEPLOYMENT-RUNBOOK-2-3-0.md§Troubleshooting§403 Unauthorized

#### ❌ Group Data Inconsistent
→ Execute: `wp apollo groups:reconcile`

---

## 🎯 PRÓXIMA FASE: Load Testing

**Quando**: 1-2 semanas pós-deploy
**Duração**: 2-4 horas
**Objetivo**: Validar comportamento sob carga

### Teste 1: Concurrent Users
```bash
# Simule 100 usuários simultaneamente
for i in {1..100}; do
  curl -s http://site.local/wp-json/apollo/v1/comunas &
done
wait
echo "✅ Test complete"
```

### Teste 2: Group Creation Burst
```bash
# Crie 50 grupos rapidamente
for i in {1..50}; do
  wp eval "
    \$group_id = \$GLOBALS['apollo_groups']->create([
      'name' => 'Test Group ' . time() . \$i,
      'type' => (rand(0,1) == 0) ? 'comuna' : 'nucleo',
    ]);
    if (\$group_id) echo \"✅ \$group_id\";
  " &
done
wait
```

### Teste 3: Rate Limiting
```bash
# Trigger rate limits intentionally
for i in {1..20}; do
  curl -s -X POST http://site.local/wp-json/apollo/v1/comunas/1/join \
    -H "X-WP-Nonce: valid" \
    -H "Cookie: logged_in=session" 2>&1 &
done
wait

# Last requests should return 429
```

---

## 🚀 PRÓXIMA FASE: Full Regression Testing

**Quando**: 2-3 semanas pós-deploy
**Duração**: 1-2 dias
**Objetivo**: Validar compatibilidade com todo Apollo

### Escopo
- [ ] Todos os módulos funcionam
- [ ] Integração com Apollo Core
- [ ] Third-party plugins
- [ ] WordPress edge cases
- [ ] Performance under normal load

### Commands
```bash
# Run comprehensive test suite (if available)
wp apollo test:all --verbose

# Or manual tests
wp apollo schema:status
wp apollo groups:reconcile --dry-run
wp plugin list
wp db check
```

---

## 📈 PRÓXIMA FASE: Monitoring Dashboard

**Quando**: 3-4 semanas pós-deploy
**Duração**: 1-2 dias
**Objetivo**: Real-time observability

### Setup
1. NewRelic / DataDog / Google Cloud Monitoring
2. Configure alerts para:
   - Error rate > 5 per minute
   - Query latency > 500ms
   - Rate limit blocks > 100/hour
   - Schema mismatch detected

3. Dashboard showing:
   - Request count by endpoint
   - Error breakdown by type
   - Query latency percentiles
   - Rate limit statistics

### Example Alert Rules
```
// Alert if > 10 errors in 5 minutes
if (errors_5min > 10) {
  alert("Apollo Social error spike detected");
  escalate("backend-on-call");
}

// Alert if nonce validation > 5 failures/hour
if (nonce_failures_1h > 5) {
  alert("Nonce validation issues - check client headers");
}

// Alert if rate limiting blocking >50%
if (rate_limit_ratio > 0.5) {
  alert("Possible legitimate users blocked - review limits");
}
```

---

## 🔧 MAINTENANCE TASKS

### Weekly
- [ ] Review error logs
- [ ] Check database size (group tables)
- [ ] Verify backups complete
- [ ] Monitor rate limit trends

### Monthly
- [ ] Database optimization: `OPTIMIZE TABLE wp_apollo_*`
- [ ] Review slow query logs
- [ ] Audit user roles in groups
- [ ] Check for orphaned group records

### Quarterly
- [ ] Performance review
- [ ] Security audit
- [ ] Capacity planning
- [ ] Feature request triage

---

## 💡 KNOWN LIMITATIONS & TODO

### Out of Scope (Future Phases)
- [ ] Documents module migration to /apollo/ prefix
- [ ] Suppliers module routing cleanup
- [ ] Chat module completion
- [ ] Full regression testing suite
- [ ] WP-CLI dry-run support

### Deferred Features
- [ ] Bulk group operations (WP-CLI)
- [ ] Advanced rate limiting UI
- [ ] Custom group types
- [ ] Nested group hierarchy

---

## 📞 ESCALATION MATRIX

| Severity | Who | Method | SLA |
|----------|-----|--------|-----|
| P0 (Down) | DevOps Lead | Slack + Phone | 15 min |
| P1 (Broken) | Backend Lead | Slack | 1 hour |
| P2 (Slow) | Tech Lead | Ticket | 24 hours |
| P3 (Minor) | Team | Ticket | 5 days |

---

## ✅ SIGN-OFF TEMPLATE

```markdown
## Apollo Social 2.3.0 - Post-Deployment Sign-Off

Date: ____________
Deployed By: ____________
Verified By: ____________

### Checklist
- [ ] Schema upgrade successful
- [ ] All REST endpoints responding
- [ ] Nonce validation working
- [ ] Rate limiting active
- [ ] WordPress feeds intact
- [ ] No data corruption
- [ ] Performance acceptable
- [ ] Monitoring enabled

### Issues Found
None: ___
Minor: ___
Severity: ____________
Status: ____________

### Sign-Off
Approved for production: ☐ Yes ☐ No
Ready for full release: ☐ Yes ☐ No
```

---

## 📚 REFERENCE DOCUMENTS

1. **DEPLOYMENT-RUNBOOK-2-3-0.md** — How to deploy
2. **PRE-DEPLOYMENT-GREP-CHECKLIST.md** — Validation
3. **API-USAGE-GUIDE.md** — Consumer guide
4. **PHASE-2-3-IMPLEMENTATION.md** — Technical details
5. **FASES-0-6-SUMMARY-EXECUTIVO.md** — Overview

---

## 🎓 TRAINING

### Frontend Team
- [ ] Review API-USAGE-GUIDE.md
- [ ] Update client code to use /comunas, /nucleos
- [ ] Handle new status codes (202, 429)
- [ ] Implement nonce in requests

### Backend Team
- [ ] Review PHASE-2-3-IMPLEMENTATION.md
- [ ] Understand GroupsBusinessRules logic
- [ ] Study RestSecurity pattern
- [ ] Learn new WP-CLI commands

### DevOps Team
- [ ] Review DEPLOYMENT-RUNBOOK-2-3-0.md
- [ ] Test rollback procedure
- [ ] Set up monitoring
- [ ] Configure alerts

---

## 🎉 SUCCESS CRITERIA

After 72 hours, if all conditions met → ✅ **SHIP IT**

- [ ] 0 P0 incidents
- [ ] <5 P1 issues
- [ ] Query latency <100ms (baseline)
- [ ] Error rate <0.1%
- [ ] Rate limiting working
- [ ] No data issues
- [ ] All tests green

If any condition fails → Continue monitoring, escalate as needed

---

**Document Created**: 30/12/2025
**For**: Apollo Social 2.3.0
**Status**: DEPLOYMENT APPROVED ✅

Boa sorte! 🚀

