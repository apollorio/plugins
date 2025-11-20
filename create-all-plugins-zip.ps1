# Script Master - Cria ZIPs de todos os plugins Apollo
# Execute este script na pasta wp-content/plugins/

Write-Host "🚀 Preparando TODOS os Plugins Apollo para Produção..." -ForegroundColor Green
Write-Host ""

# Verificar se estamos na pasta correta
if (-not (Test-Path "apollo-social\apollo-social.php") -or 
    -not (Test-Path "apollo-events-manager\apollo-events-manager.php") -or 
    -not (Test-Path "apollo-rio\apollo-rio.php")) {
    Write-Host "❌ ERRO: Execute este script na pasta wp-content/plugins/" -ForegroundColor Red
    Write-Host "   Certifique-se de que todos os três plugins estão presentes." -ForegroundColor Yellow
    exit 1
}

Write-Host "📦 Criando ZIPs para todos os plugins Apollo..." -ForegroundColor Yellow
Write-Host ""

# Executar scripts individuais
$scripts = @(
    "apollo-social\create-production-zip.ps1",
    "apollo-events-manager\create-production-zip.ps1",
    "apollo-rio\create-production-zip.ps1"
)

$successCount = 0
$failCount = 0

foreach ($script in $scripts) {
    if (Test-Path $script) {
        Write-Host "▶️  Executando: $script" -ForegroundColor Cyan
        Write-Host ""
        
        try {
            & $script
            $successCount++
            Write-Host ""
            Write-Host "✅ $script concluído com sucesso" -ForegroundColor Green
            Write-Host ""
        } catch {
            $failCount++
            Write-Host ""
            Write-Host "❌ ERRO ao executar $script : $_" -ForegroundColor Red
            Write-Host ""
        }
    } else {
        Write-Host "⚠️  Script não encontrado: $script" -ForegroundColor Yellow
        $failCount++
    }
}

Write-Host ""
Write-Host "═══════════════════════════════════════════════════════" -ForegroundColor Cyan
Write-Host "📊 RESUMO FINAL" -ForegroundColor Yellow
Write-Host "═══════════════════════════════════════════════════════" -ForegroundColor Cyan
Write-Host ""
Write-Host "✅ Sucessos: $successCount" -ForegroundColor Green
Write-Host "❌ Falhas: $failCount" -ForegroundColor $(if ($failCount -gt 0) { "Red" } else { "Green" })
Write-Host ""

# Listar ZIPs criados
Write-Host "📦 Arquivos ZIP criados:" -ForegroundColor Cyan
$zips = Get-ChildItem -Path "." -Filter "*.zip" | Where-Object { $_.Name -like "apollo-*-v*-production.zip" }
foreach ($zip in $zips) {
    $sizeMB = [math]::Round($zip.Length / 1MB, 2)
    Write-Host "   ✅ $($zip.Name) ($sizeMB MB)" -ForegroundColor Green
}

Write-Host ""
if ($failCount -eq 0) {
    Write-Host "🎉 TODOS OS PLUGINS PRONTOS PARA DEPLOY!" -ForegroundColor Green
    Write-Host ""
    Write-Host "Próximos passos:" -ForegroundColor Yellow
    Write-Host "1. Fazer backup completo do site" -ForegroundColor White
    Write-Host "2. Fazer upload dos ZIPs para o servidor" -ForegroundColor White
    Write-Host "3. Descompactar cada plugin" -ForegroundColor White
    Write-Host "4. Ativar plugins na ordem:" -ForegroundColor White
    Write-Host "   a) apollo-rio" -ForegroundColor Gray
    Write-Host "   b) apollo-social" -ForegroundColor Gray
    Write-Host "   c) apollo-events-manager" -ForegroundColor Gray
    Write-Host "5. Testar todas as funcionalidades" -ForegroundColor White
    Write-Host "6. Verificar rewrite rules foram flushadas" -ForegroundColor White
} else {
    Write-Host "⚠️  ALGUNS PLUGINS FALHARAM!" -ForegroundColor Red
    Write-Host "   Revise os erros acima antes do deploy." -ForegroundColor Yellow
}

Write-Host ""
Write-Host "═══════════════════════════════════════════════════════" -ForegroundColor Cyan

