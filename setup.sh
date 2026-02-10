#!/bin/bash

# ============================================================================
# SCRIPT DE SETUP INICIAL DO PROJETO NOVO SISTEMA
# ============================================================================
# Este script cria a estrutura de diretórios e faz as verificações iniciais
# Uso: bash setup.sh ou chmod +x setup.sh && ./setup.sh
# ============================================================================

set -e

# Cores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# ============================================================================
# FUNÇÕES
# ============================================================================

print_header() {
    echo -e "${BLUE}╔════════════════════════════════════════════════════════════════╗${NC}"
    echo -e "${BLUE}║${NC} $1"
    echo -e "${BLUE}╚════════════════════════════════════════════════════════════════╝${NC}"
}

print_section() {
    echo -e "\n${YELLOW}▶ $1${NC}"
}

print_success() {
    echo -e "${GREEN}✓${NC} $1"
}

print_error() {
    echo -e "${RED}✗${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}⚠${NC} $1"
}

print_info() {
    echo -e "${BLUE}ℹ${NC} $1"
}

# ============================================================================
# INÍCIO
# ============================================================================

print_header "SETUP DO PROJETO NOVO SISTEMA"

# ============================================================================
# VERIFICAÇÕES PRÉ-REQUISITOS
# ============================================================================

print_section "Verificando pré-requisitos..."

# Verificar PHP
if command -v php &> /dev/null; then
    PHP_VERSION=$(php -v | head -n 1)
    print_success "PHP instalado: $PHP_VERSION"
else
    print_error "PHP não está instalado"
    exit 1
fi

# Verificar Apache
if command -v apache2 &> /dev/null; then
    print_success "Apache instalado"
else
    if command -v httpd &> /dev/null; then
        print_success "Apache (httpd) instalado"
    else
        print_warning "Apache não detectado"
    fi
fi

# Verificar mod_rewrite
if apache2ctl -M 2>/dev/null | grep -q rewrite_module; then
    print_success "Módulo mod_rewrite está ativo"
else
    print_warning "mod_rewrite não está ativo"
    echo -e "   Para ativar, execute:"
    echo -e "   ${YELLOW}sudo a2enmod rewrite${NC}"
    echo -e "   ${YELLOW}sudo systemctl restart apache2${NC}"
fi

# ============================================================================
# CRIAR ESTRUTURA DE DIRETÓRIOS
# ============================================================================

print_section "Criando estrutura de diretórios..."

DIRS=(
    "app/admin"
    "app/tecnico"
    "app/cliente"
    "app/api"
    "config"
    "logs"
    "public_html/assets/css"
    "public_html/assets/js"
    "public_html/assets/images"
)

for dir in "${DIRS[@]}"; do
    if [ -d "$dir" ]; then
        print_info "Diretório já existe: $dir"
    else
        mkdir -p "$dir"
        print_success "Criado: $dir"
    fi
done

# ============================================================================
# CONFIGURAR PERMISSÕES
# ============================================================================

print_section "Configurando permissões..."

# Permissão de escrita para logs
if [ -d "logs" ]; then
    chmod 755 logs
    print_success "Permissões de logs configuradas"
fi

# Permissão de leitura/escrita para config
if [ -d "config" ]; then
    chmod 755 config
    print_success "Permissões de config configuradas"
fi

# ============================================================================
# CRIAR ARQUIVOS BÁSICOS
# ============================================================================

print_section "Criando arquivos básicos..."

# Admin index.php
if [ ! -f "app/admin/index.php" ]; then
    cat > app/admin/index.php << 'EOF'
<?php
/**
 * Painel Administrativo
 * 
 * TODO: Implementar interface administrativa
 */

// Verificar autenticação
if (!isset($_SESSION['usuario_tipo']) || $_SESSION['usuario_tipo'] !== 'admin') {
    header('Location: /login.html');
    exit;
}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Painel Administrativo</title>
</head>
<body>
    <h1>Painel Administrativo</h1>
    <p>Bem-vindo, <?php echo htmlspecialchars($_SESSION['usuario_email'] ?? 'Usuário'); ?>!</p>
    <a href="/logout">Sair</a>
</body>
</html>
EOF
    print_success "Criado: app/admin/index.php"
else
    print_info "app/admin/index.php já existe"
fi

# Tecnico index.php
if [ ! -f "app/tecnico/index.php" ]; then
    cat > app/tecnico/index.php << 'EOF'
<?php
/**
 * Painel Técnico
 * 
 * TODO: Implementar interface técnica
 */

// Verificar autenticação
if (!isset($_SESSION['usuario_tipo']) || $_SESSION['usuario_tipo'] !== 'tecnico') {
    header('Location: /login.html');
    exit;
}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Painel Técnico</title>
</head>
<body>
    <h1>Painel Técnico</h1>
    <p>Bem-vindo, <?php echo htmlspecialchars($_SESSION['usuario_email'] ?? 'Usuário'); ?>!</p>
    <a href="/logout">Sair</a>
</body>
</html>
EOF
    print_success "Criado: app/tecnico/index.php"
else
    print_info "app/tecnico/index.php já existe"
fi

# Cliente index.php
if [ ! -f "app/cliente/index.php" ]; then
    cat > app/cliente/index.php << 'EOF'
<?php
/**
 * Site Público / Portal do Cliente
 * 
 * TODO: Implementar página inicial
 */

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Novo Sistema - Bem-vindo</title>
</head>
<body>
    <h1>Bem-vindo ao Novo Sistema</h1>
    <p><a href="/login.html">Fazer Login</a></p>
</body>
</html>
EOF
    print_success "Criado: app/cliente/index.php"
else
    print_info "app/cliente/index.php já existe"
fi

# API index.php
if [ ! -f "app/api/index.php" ]; then
    cat > app/api/index.php << 'EOF'
<?php
/**
 * API Index
 * 
 * Endpoint base da API
 */

header('Content-Type: application/json; charset=utf-8');

http_response_code(200);
echo json_encode([
    'sucesso' => true,
    'mensagem' => 'API do Novo Sistema',
    'versao' => '1.0.0',
    'endpoints' => [
        '/api/auth' => 'Autenticação'
    ]
], JSON_UNESCAPED_UNICODE);
?>
EOF
    print_success "Criado: app/api/index.php"
else
    print_info "app/api/index.php já existe"
fi

# ============================================================================
# CRIAR ARQUIVO .gitignore
# ============================================================================

print_section "Criando .gitignore..."

if [ ! -f ".gitignore" ]; then
    cat > .gitignore << 'EOF'
# Dependências
/vendor/
node_modules/

# Configurações
.env
.env.local
.env.*.local

# Logs
logs/
*.log

# IDE
.vscode/
.idea/
*.swp
*.swo
*~

# OS
.DS_Store
Thumbs.db
desktop.ini

# Arquivos temporários
*.tmp
*.temp
*.backup
.cache/

# Teste
test-results/
coverage/

# Build
build/
dist/
EOF
    print_success "Criado: .gitignore"
else
    print_info ".gitignore já existe"
fi

# ============================================================================
# VERIFICAÇÕES FINAIS
# ============================================================================

print_section "Verificações finais..."

# Verificar se index.php existe
if [ -f "public_html/index.php" ]; then
    print_success "index.php encontrado"
else
    print_error "index.php não encontrado em public_html/"
fi

# Verificar se .htaccess existe
if [ -f "public_html/.htaccess" ]; then
    print_success ".htaccess encontrado"
else
    print_error ".htaccess não encontrado em public_html/"
fi

# Verificar se login.html existe
if [ -f "public_html/login.html" ]; then
    print_success "login.html encontrado"
else
    print_error "login.html não encontrado em public_html/"
fi

# ============================================================================
# RESUMO
# ============================================================================

echo ""
print_header "SETUP CONCLUÍDO"

echo -e "\n${GREEN}✓ Estrutura do projeto foi inicializada com sucesso!${NC}\n"

echo "Próximos passos:"
echo ""
echo "1. ${YELLOW}Ativar mod_rewrite (se ainda não está ativo):${NC}"
echo "   sudo a2enmod rewrite"
echo "   sudo systemctl restart apache2"
echo ""
echo "2. ${YELLOW}Configurar Apache Virtual Host:${NC}"
echo "   Certifique-se que AllowOverride All está configurado"
echo ""
echo "3. ${YELLOW}Criar API de autenticação:${NC}"
echo "   Copie exemplo-auth-api.php para app/api/auth.php"
echo "   e ajuste para seu banco de dados"
echo ""
echo "4. ${YELLOW}Implementar banco de dados:${NC}"
echo "   Configure conexão em config/database.php"
echo ""
echo "5. ${YELLOW}Testar aplicação:${NC}"
echo "   Acesse http://localhost/login.html"
echo ""
echo "Documentação:"
echo "  - DOCUMENTACAO.md - Guia técnico completo"
echo "  - GUIA_RAPIDO.md - Referência rápida"
echo ""
print_info "Para dúvidas, consulte a documentação incluída"

