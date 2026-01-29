# Script de Criação de ZIP para Produção - Apollo Social
# Execute este script na pasta wp-content/plugins/

Write-Host "🚀 Preparando Apollo Social para Produção..." -ForegroundColor Green
Write-Host ""

# Verificar se estamos na pasta correta
if (-not (Test-Path "apollo-social\apollo-social.php")) {
    Write-Host "❌ ERRO: Execute este script na pasta wp-content/plugins/" -ForegroundColor Red
    exit 1
}

# Nome do arquivo ZIP
$version = "0.0.1"
$zipName = "apollo-social-v$version-production.zip"
$pluginDir = "apollo-social"

Write-Host "📦 Criando ZIP: $zipName" -ForegroundColor Yellow
Write-Host ""

# Remover ZIP anterior se existir
if (Test-Path $zipName) {
    Remove-Item $zipName -Force
    Write-Host "✅ ZIP anterior removido" -ForegroundColor Green
}

# Lista de arquivos/pastas a excluir
$excludePatterns = @(
    "*.log",
    "*.tmp",
    "test-*.php",
    "debug-*.php",
    "node_modules",
    ".git",
    ".gitignore",
    ".DS_Store",
    "Thumbs.db"
)

Write-Host "📋 Arquivos a excluir:" -ForegroundColor Cyan
foreach ($pattern in $excludePatterns) {
    Write-Host "   - $pattern" -ForegroundColor Gray
}
Write-Host ""

# Criar ZIP
try {
    # Obter todos os arquivos da pasta
    $files = Get-ChildItem -Path $pluginDir -Recurse -File | Where-Object {
        $shouldExclude = $false
        foreach ($pattern in $excludePatterns) {
            if ($_.Name -like $pattern -or $_.FullName -like "*\$pattern\*") {
                $shouldExclude = $true
                break
            }
        }
        return -not $shouldExclude
    }

    # Criar ZIP usando .NET
    Add-Type -AssemblyName System.IO.Compression.FileSystem
    $zip = [System.IO.Compression.ZipFile]::Open($zipName, [System.IO.Compression.ZipArchiveMode]::Create)

    foreach ($file in $files) {
        $relativePath = $file.FullName.Substring((Resolve-Path $pluginDir).Path.Length + 1)
        [System.IO.Compression.ZipFileExtensions]::CreateEntryFromFile($zip, $file.FullName, $relativePath) | Out-Null
        Write-Host "   ✓ $relativePath" -ForegroundColor DarkGray
    }

    $zip.Dispose()

    Write-Host ""
    Write-Host "✅ ZIP criado com sucesso: $zipName" -ForegroundColor Green
    Write-Host ""
    
    # Informações do ZIP
    $zipInfo = Get-Item $zipName
    $sizeMB = [math]::Round($zipInfo.Length / 1MB, 2)
    Write-Host "📊 Tamanho: $sizeMB MB" -ForegroundColor Cyan
    Write-Host "📁 Localização: $($zipInfo.FullName)" -ForegroundColor Cyan
    Write-Host ""
    
    # Verificar conteúdo crítico
    Write-Host "🔍 Verificando conteúdo crítico..." -ForegroundColor Yellow
    
    $requiredFiles = @(
        "apollo-social.php",
        "src\Plugin.php",
        "src\helpers.php",
        "src\Infrastructure\Http\Routes.php"
    )
    
    $zipContent = [System.IO.Compression.ZipFile]::OpenRead($zipName)
    $entries = $zipContent.Entries | Select-Object -ExpandProperty FullName
    
    $allPresent = $true
    foreach ($required in $requiredFiles) {
        $found = $entries | Where-Object { $_ -like "*$required" }
        if ($found) {
            Write-Host "   ✅ $required" -ForegroundColor Green
        } else {
            Write-Host "   ❌ $required - FALTANDO!" -ForegroundColor Red
            $allPresent = $false
        }
    }
    
    $zipContent.Dispose()
    
    Write-Host ""
    if ($allPresent) {
        Write-Host "✅ Todos os arquivos críticos presentes!" -ForegroundColor Green
        Write-Host ""
        Write-Host "🎉 PRONTO PARA DEPLOY!" -ForegroundColor Green
        Write-Host ""
        Write-Host "Próximos passos:" -ForegroundColor Yellow
        Write-Host "1. Fazer backup do site" -ForegroundColor White
        Write-Host "2. Fazer upload do ZIP: $zipName" -ForegroundColor White
        Write-Host "3. Descompactar no servidor" -ForegroundColor White
        Write-Host "4. Ativar plugin" -ForegroundColor White
        Write-Host "5. Testar rotas do Apollo Social" -ForegroundColor White
    } else {
        Write-Host "⚠️  ATENÇÃO: Alguns arquivos críticos estão faltando!" -ForegroundColor Red
        Write-Host "   Revise o conteúdo do ZIP antes do deploy." -ForegroundColor Yellow
    }
    
} catch {
    Write-Host ""
    Write-Host "❌ ERRO ao criar ZIP: $_" -ForegroundColor Red
    exit 1
}

Write-Host ""
Write-Host "📝 Consulte DEPLOY-CHECKLIST.md para instruções completas" -ForegroundColor Cyan

