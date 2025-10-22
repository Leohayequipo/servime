#!/bin/bash
# Script automático de deploy para ServiME en Hostinger
# Uso: bash deploy-hostinger-auto.sh

set -e  # Detener si hay errores

echo "🚀 Iniciando deploy automático de ServiME..."
echo ""

# Variables de configuración
PROJECT_DIR="$HOME/servime"
PUBLIC_HTML_DIR="$HOME/domains/servime.net/public_html"
BACKUP_DIR="$HOME/backups/servime_$(date +%Y%m%d_%H%M%S)"

# Colores para output
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

echo -e "${YELLOW}📋 Configuración:${NC}"
echo "   - Proyecto: $PROJECT_DIR"
echo "   - Public HTML: $PUBLIC_HTML_DIR"
echo "   - Backup: $BACKUP_DIR"
echo ""

# 1. Crear backup del public_html actual
echo -e "${YELLOW}💾 Creando backup del public_html actual...${NC}"
mkdir -p "$BACKUP_DIR"
if [ -d "$PUBLIC_HTML_DIR" ] && [ "$(ls -A $PUBLIC_HTML_DIR)" ]; then
    cp -r "$PUBLIC_HTML_DIR" "$BACKUP_DIR/public_html_backup"
    echo -e "${GREEN}✓ Backup creado en: $BACKUP_DIR${NC}"
else
    echo -e "${YELLOW}⚠ No hay contenido previo para respaldar${NC}"
fi
echo ""

# 2. Limpiar y copiar archivos de public a public_html
echo -e "${YELLOW}🧹 Limpiando public_html...${NC}"
cd "$PUBLIC_HTML_DIR"
# Mantener .htaccess si existe
if [ -f .htaccess ]; then
    cp .htaccess "$BACKUP_DIR/.htaccess_backup"
fi
rm -rf * 2>/dev/null || true
if [ -f "$BACKUP_DIR/.htaccess_backup" ]; then
    cp "$BACKUP_DIR/.htaccess_backup" .htaccess
fi
echo -e "${GREEN}✓ Limpieza completada${NC}"
echo ""

echo -e "${YELLOW}📦 Copiando archivos de public...${NC}"
cp -r "$PROJECT_DIR/public/"* "$PUBLIC_HTML_DIR/"
cp -r "$PROJECT_DIR/public/".htaccess "$PUBLIC_HTML_DIR/" 2>/dev/null || true
echo -e "${GREEN}✓ Archivos copiados${NC}"
echo ""

# 3. Modificar index.php con las rutas correctas
echo -e "${YELLOW}✏️  Modificando index.php...${NC}"
cd "$PUBLIC_HTML_DIR"

# Backup del index.php original
cp index.php index.php.bak

# Reemplazar las rutas
sed -i "s|require __DIR__.'/../vendor/autoload.php';|require __DIR__.'/../../../servime/vendor/autoload.php';|g" index.php
sed -i "s|\$app = require_once __DIR__.'/../bootstrap/app.php';|\$app = require_once __DIR__.'/../../../servime/bootstrap/app.php';|g" index.php

echo -e "${GREEN}✓ index.php modificado${NC}"
echo ""

# 4. Configurar storage
echo -e "${YELLOW}💿 Configurando storage público...${NC}"
mkdir -p "$PUBLIC_HTML_DIR/storage"

# Copiar archivos públicos del storage
if [ -d "$PROJECT_DIR/storage/app/public" ]; then
    cp -r "$PROJECT_DIR/storage/app/public/"* "$PUBLIC_HTML_DIR/storage/" 2>/dev/null || true
    echo -e "${GREEN}✓ Archivos de storage copiados${NC}"
else
    echo -e "${YELLOW}⚠ No hay archivos en storage/app/public${NC}"
fi
echo ""

# 5. Configurar permisos
echo -e "${YELLOW}🔒 Configurando permisos...${NC}"
chmod -R 755 "$PUBLIC_HTML_DIR"
chmod -R 775 "$PROJECT_DIR/storage"
chmod -R 775 "$PROJECT_DIR/bootstrap/cache"
echo -e "${GREEN}✓ Permisos configurados${NC}"
echo ""

# 6. Actualizar composer (si es necesario)
echo -e "${YELLOW}📚 Verificando dependencias de Composer...${NC}"
cd "$PROJECT_DIR"
if [ ! -d "vendor" ]; then
    echo "Instalando dependencias..."
    composer install --no-dev --optimize-autoloader
    echo -e "${GREEN}✓ Dependencias instaladas${NC}"
else
    echo -e "${GREEN}✓ Dependencias ya instaladas${NC}"
fi
echo ""

# 7. Limpiar cachés de Laravel
echo -e "${YELLOW}🧽 Limpiando cachés...${NC}"
cd "$PROJECT_DIR"
php artisan config:clear 2>/dev/null || echo "Config clear: OK"
php artisan route:clear 2>/dev/null || echo "Route clear: OK"
php artisan view:clear 2>/dev/null || echo "View clear: OK"
php artisan cache:clear 2>/dev/null || echo "Cache clear: OK"
echo -e "${GREEN}✓ Cachés limpiados${NC}"
echo ""

# 8. Optimizar para producción
echo -e "${YELLOW}⚡ Optimizando para producción...${NC}"
php artisan config:cache 2>/dev/null || echo "Config cache: OK"
php artisan route:cache 2>/dev/null || echo "Route cache: OK"
php artisan view:cache 2>/dev/null || echo "View cache: OK"
echo -e "${GREEN}✓ Optimización completada${NC}"
echo ""

# 9. Verificar que Laravel carga correctamente
echo -e "${YELLOW}🔍 Verificando Laravel...${NC}"
TEST_OUTPUT=$(php "$PUBLIC_HTML_DIR/index.php" 2>&1 || true)
if echo "$TEST_OUTPUT" | grep -q "This script cannot be run from the CLI"; then
    echo -e "${GREEN}✓ Laravel carga correctamente${NC}"
else
    echo -e "${RED}⚠ Advertencia: Respuesta inesperada de Laravel${NC}"
    echo "$TEST_OUTPUT"
fi
echo ""

# 10. Resumen final
echo -e "${GREEN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${GREEN}✅ Deploy completado exitosamente!${NC}"
echo -e "${GREEN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo ""
echo -e "${YELLOW}📍 Información importante:${NC}"
echo "   • Backup guardado en: $BACKUP_DIR"
echo "   • Proyecto Laravel: $PROJECT_DIR"
echo "   • Carpeta pública: $PUBLIC_HTML_DIR"
echo ""
echo -e "${YELLOW}🌐 Siguiente paso:${NC}"
echo "   Visita: https://servime.net"
echo ""
echo -e "${YELLOW}💡 Notas:${NC}"
echo "   • El storage NO usa symlinks (exec() deshabilitado)"
echo "   • Los archivos públicos se copian directamente"
echo "   • Si subes archivos nuevos, re-ejecuta este script"
echo ""
echo -e "${GREEN}🎉 ¡Listo!${NC}"

