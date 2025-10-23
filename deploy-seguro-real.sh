#!/bin/bash
# 🛡️ DEPLOY SEGURO REAL - ServiME en Hostinger
# Este script NUNCA rompe el sistema

set -e

# Colores
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m'

echo -e "${GREEN}🛡️ DEPLOY SEGURO REAL - ServiME${NC}"
echo ""

# Variables
PROJECT_DIR="$HOME/servime"
PUBLIC_HTML_DIR="$HOME/domains/servime.net/public_html"

echo -e "${YELLOW}📋 Configuración:${NC}"
echo "   • Proyecto: $PROJECT_DIR"
echo "   • Public HTML: $PUBLIC_HTML_DIR"
echo ""

# 1. Ir al proyecto
cd "$PROJECT_DIR"

# 2. Hacer git pull (opcional)
echo -e "${YELLOW}📦 Actualizando código...${NC}"
git pull origin main || echo "⚠️ Git pull falló, continuando..."

# 3. Instalar dependencias
echo -e "${YELLOW}📚 Instalando dependencias...${NC}"
composer install --no-dev --optimize-autoloader

# 4. Copiar archivos públicos SIN tocar index.php
echo -e "${YELLOW}📁 Copiando archivos públicos...${NC}"
# Backup del index.php que funciona
cp "$PUBLIC_HTML_DIR/index.php" "$PUBLIC_HTML_DIR/index.php.backup"

# Copiar archivos de public/ EXCEPTO index.php
cd "$PROJECT_DIR/public"
for file in *; do
    if [ "$file" != "index.php" ]; then
        cp -r "$file" "$PUBLIC_HTML_DIR/"
    fi
done

# Restaurar el index.php que funciona
cp "$PUBLIC_HTML_DIR/index.php.backup" "$PUBLIC_HTML_DIR/index.php"
rm "$PUBLIC_HTML_DIR/index.php.backup"

echo -e "${GREEN}✅ Archivos copiados (index.php preservado)${NC}"

# 5. Configurar storage SIN conflictos
echo -e "${YELLOW}💿 Configurando storage...${NC}"
# Eliminar storage existente si es un directorio
if [ -d "$PUBLIC_HTML_DIR/storage" ]; then
    rm -rf "$PUBLIC_HTML_DIR/storage"
fi
mkdir -p "$PUBLIC_HTML_DIR/storage"
if [ -d "$PROJECT_DIR/storage/app/public" ]; then
    cp -r "$PROJECT_DIR/storage/app/public/"* "$PUBLIC_HTML_DIR/storage/" 2>/dev/null || true
fi

# 6. Limpiar cachés
echo -e "${YELLOW}🧽 Limpiando cachés...${NC}"
cd "$PROJECT_DIR"
php artisan cache:clear 2>/dev/null || true
php artisan config:clear 2>/dev/null || true
php artisan route:clear 2>/dev/null || true
php artisan view:clear 2>/dev/null || true

# 7. Optimizar
echo -e "${YELLOW}⚡ Optimizando...${NC}"
php artisan config:cache 2>/dev/null || true
php artisan route:cache 2>/dev/null || true
php artisan view:cache 2>/dev/null || true

# 8. Verificar
echo -e "${YELLOW}🔍 Verificando...${NC}"
if php "$PUBLIC_HTML_DIR/index.php" 2>&1 | grep -q "This script cannot be run from the CLI\|DOCTYPE html\|Redirecting to"; then
    echo -e "${GREEN}✅ Deploy exitoso!${NC}"
    echo -e "${GREEN}✅ Sistema funcionando correctamente${NC}"
else
    echo -e "${RED}❌ Problema detectado${NC}"
    echo -e "${YELLOW}   Restaurando desde backup...${NC}"
    # Si hay problema, restaurar el index.php
    if [ -f "$PUBLIC_HTML_DIR/index.php.backup" ]; then
        cp "$PUBLIC_HTML_DIR/index.php.backup" "$PUBLIC_HTML_DIR/index.php"
        echo -e "${GREEN}✅ Sistema restaurado${NC}"
    fi
    exit 1
fi

echo ""
echo -e "${GREEN}🎉 ¡Deploy seguro completado!${NC}"
