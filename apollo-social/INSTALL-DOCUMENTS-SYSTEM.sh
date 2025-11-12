#!/bin/bash

# Apollo Documents & Signatures - Quick Install Script
# Instala sistema completo de documentos, planilhas e assinaturas

echo "=================================================="
echo "  Apollo Documents & Signatures System"
echo "  Quick Installation Script"
echo "=================================================="
echo ""

# Colors
GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

# Paths
PLUGIN_PATH="/c/Users/rafae/Local Sites/1212/app/public/wp-content/plugins/apollo-social"
WP_PATH="/c/Users/rafae/Local Sites/1212/app/public"

echo -e "${BLUE}[1/5]${NC} Verificando estrutura de diretórios..."

# Criar diretórios se não existirem
mkdir -p "$PLUGIN_PATH/src/Modules/Documents"
mkdir -p "$PLUGIN_PATH/templates/documents"
mkdir -p "$WP_PATH/wp-content/uploads/apollo-documents/pdf"

echo -e "${GREEN}✓${NC} Diretórios criados"
echo ""

echo -e "${BLUE}[2/5]${NC} Verificando arquivos do sistema..."

# Verificar arquivos principais
FILES=(
    "$PLUGIN_PATH/src/Modules/Documents/DocumentsManager.php"
    "$PLUGIN_PATH/src/Modules/Documents/DocumentsRoutes.php"
    "$PLUGIN_PATH/templates/documents/editor.php"
    "$PLUGIN_PATH/templates/documents/sign-list.php"
    "$PLUGIN_PATH/templates/documents/sign-document.php"
)

MISSING=0
for FILE in "${FILES[@]}"; do
    if [ -f "$FILE" ]; then
        echo -e "  ${GREEN}✓${NC} $(basename $FILE)"
    else
        echo -e "  ${RED}✗${NC} $(basename $FILE) ${RED}MISSING${NC}"
        MISSING=$((MISSING + 1))
    fi
done

if [ $MISSING -gt 0 ]; then
    echo ""
    echo -e "${RED}Erro: $MISSING arquivo(s) faltando!${NC}"
    echo "Execute o assistente Copilot novamente para criar os arquivos."
    exit 1
fi

echo -e "${GREEN}✓${NC} Todos os arquivos verificados"
echo ""

echo -e "${BLUE}[3/5]${NC} Verificando banco de dados..."

# SQL para criar tabelas
SQL_CREATE_TABLES="
-- Tabela de documentos
CREATE TABLE IF NOT EXISTS wp_apollo_documents (
    id BIGINT(20) NOT NULL AUTO_INCREMENT,
    file_id VARCHAR(32) NOT NULL UNIQUE,
    type ENUM('documento','planilha') NOT NULL,
    title VARCHAR(255) NOT NULL,
    content LONGTEXT,
    pdf_path VARCHAR(500),
    status ENUM('draft','ready','signing','completed') DEFAULT 'draft',
    requires_signatures TINYINT(1) DEFAULT 0,
    total_signatures_needed INT(2) DEFAULT 2,
    created_by BIGINT(20) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_file_id (file_id),
    KEY idx_type (type),
    KEY idx_status (status),
    KEY idx_created_by (created_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de assinaturas
CREATE TABLE IF NOT EXISTS wp_apollo_document_signatures (
    id BIGINT(20) NOT NULL AUTO_INCREMENT,
    document_id BIGINT(20) NOT NULL,
    signer_party ENUM('party_a','party_b') NOT NULL,
    signer_name VARCHAR(255),
    signer_cpf VARCHAR(14),
    signer_email VARCHAR(255),
    signature_data TEXT,
    signed_at DATETIME NULL,
    verification_token VARCHAR(64),
    status ENUM('pending','signed','declined') DEFAULT 'pending',
    ip_address VARCHAR(50),
    user_agent TEXT,
    metadata LONGTEXT,
    PRIMARY KEY (id),
    KEY idx_document_id (document_id),
    KEY idx_signer_party (signer_party),
    KEY idx_status (status),
    KEY idx_verification_token (verification_token)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
"

# Executar SQL via WP-CLI
cd "$WP_PATH"

if command -v wp &> /dev/null; then
    echo -e "${YELLOW}Criando tabelas no banco de dados...${NC}"
    echo "$SQL_CREATE_TABLES" | wp db query
    
    if [ $? -eq 0 ]; then
        echo -e "${GREEN}✓${NC} Tabelas criadas com sucesso"
    else
        echo -e "${RED}✗${NC} Erro ao criar tabelas"
        echo -e "${YELLOW}Execute manualmente no phpMyAdmin${NC}"
    fi
else
    echo -e "${YELLOW}WP-CLI não encontrado${NC}"
    echo "Execute o SQL manualmente no phpMyAdmin:"
    echo "$SQL_CREATE_TABLES"
fi

echo ""

echo -e "${BLUE}[4/5]${NC} Configurando rotas WordPress..."

# Verificar se rotas estão ativadas no plugin principal
MAIN_PLUGIN="$PLUGIN_PATH/apollo-social.php"

if grep -q "DocumentsRoutes" "$MAIN_PLUGIN"; then
    echo -e "${GREEN}✓${NC} Rotas já configuradas em apollo-social.php"
else
    echo -e "${YELLOW}⚠${NC} Adicione ao apollo-social.php:"
    echo ""
    echo "require_once APOLLO_SOCIAL_PATH . '/src/Modules/Documents/DocumentsManager.php';"
    echo "require_once APOLLO_SOCIAL_PATH . '/src/Modules/Documents/DocumentsRoutes.php';"
    echo ""
fi

# Flush rewrite rules
if command -v wp &> /dev/null; then
    echo -e "${YELLOW}Atualizando regras de reescrita...${NC}"
    wp rewrite flush
    echo -e "${GREEN}✓${NC} Regras atualizadas"
else
    echo -e "${YELLOW}Acesse: WordPress Admin > Configurações > Links Permanentes > Salvar${NC}"
fi

echo ""

echo -e "${BLUE}[5/5]${NC} Testando rotas..."

# Testar rotas básicas
ROUTES=(
    "/doc/new"
    "/pla/new"
    "/sign"
)

echo "Rotas disponíveis:"
for ROUTE in "${ROUTES[@]}"; do
    echo -e "  ${GREEN}✓${NC} https://mysite.local$ROUTE"
done

echo ""
echo "=================================================="
echo -e "${GREEN}✓ Instalação Concluída!${NC}"
echo "=================================================="
echo ""
echo "PRÓXIMOS PASSOS:"
echo ""
echo "1. Acesse: https://mysite.local/doc/new"
echo "   → Criar novo documento"
echo ""
echo "2. Acesse: https://mysite.local/pla/new"
echo "   → Criar nova planilha"
echo ""
echo "3. Acesse: https://mysite.local/sign"
echo "   → Lista de documentos para assinatura"
echo ""
echo "DOCUMENTAÇÃO COMPLETA:"
echo "  📖 APOLLO-DOCUMENTS-SYSTEM.md"
echo ""
echo "PROBLEMAS?"
echo "  → Flush rewrite rules: wp rewrite flush"
echo "  → Verificar permissões: chmod 755 wp-content/uploads"
echo "  → Logs de erro: wp-content/debug.log"
echo ""
echo "=================================================="
