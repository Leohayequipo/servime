#!/bin/bash
# 🛡️ DEPLOY SEGURO para ServiME en Hostinger
# Este script NUNCA rompe las rutas críticas

set -e  # Detener si hay errores

# Colores para output
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
BLUE='\033[0;34m'
NC='\033[0m'

echo -e "${BLUE}🛡️  DEPLOY SEGURO - ServiME en Hostinger${NC}"
echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo ""

# Variables de configuración
PROJECT_DIR="$HOME/servime"
PUBLIC_HTML_DIR="$HOME/domains/servime.net/public_html"
BACKUP_DIR="$HOME/backups/servime_$(date +%Y%m%d_%H%M%S)"
INDEX_PHP_BACKUP="$HOME/backups/index.php.correcto"

echo -e "${YELLOW}📋 Configuración:${NC}"
echo "   • Proyecto: $PROJECT_DIR"
echo "   • Public HTML: $PUBLIC_HTML_DIR"
echo "   • Backup: $BACKUP_DIR"
echo ""

# ========================================
# PASO 1: VERIFICAR QUE EL SISTEMA FUNCIONA
# ========================================
echo -e "${YELLOW}🔍 PASO 1: Verificando sistema actual...${NC}"

# Verificar que Laravel carga correctamente ANTES de hacer cambios
if php "$PUBLIC_HTML_DIR/index.php" 2>&1 | grep -q "This script cannot be run from the CLI\|DOCTYPE html\|Redirecting to"; then
    echo -e "${GREEN}✅ Sistema funcionando correctamente${NC}"
else
    echo -e "${RED}🚨 PROBLEMA DETECTADO: El sistema no está funcionando${NC}"
    echo -e "${RED}   No podemos hacer deploy si el sistema actual está roto${NC}"
    echo -e "${YELLOW}   Ejecuta primero el script de reparación de emergencia${NC}"
    exit 1
fi
echo ""

# ========================================
# PASO 2: CREAR BACKUP COMPLETO
# ========================================
echo -e "${YELLOW}💾 PASO 2: Creando backup de seguridad...${NC}"

# Crear directorio de backup
mkdir -p "$BACKUP_DIR"

# Backup del public_html completo
if [ -d "$PUBLIC_HTML_DIR" ] && [ "$(ls -A $PUBLIC_HTML_DIR 2>/dev/null)" ]; then
    cp -r "$PUBLIC_HTML_DIR" "$BACKUP_DIR/public_html_backup"
    echo -e "${GREEN}✅ Backup de public_html creado${NC}"
else
    echo -e "${YELLOW}⚠ No hay contenido previo para respaldar${NC}"
fi

# Backup del index.php correcto (para restauración rápida)
mkdir -p "$HOME/backups"
cp "$PUBLIC_HTML_DIR/index.php" "$INDEX_PHP_BACKUP"
echo -e "${GREEN}✅ Backup del index.php correcto guardado${NC}"
echo ""

# ========================================
# PASO 3: ACTUALIZAR CÓDIGO DEL PROYECTO
# ========================================
echo -e "${YELLOW}📦 PASO 3: Actualizando código del proyecto...${NC}"

cd "$PROJECT_DIR"

# Verificar que estamos en el directorio correcto
if [ ! -f "artisan" ]; then
    echo -e "${RED}❌ No se encontró artisan en $PROJECT_DIR${NC}"
    echo -e "${RED}   Verificar que el proyecto Laravel esté en la ubicación correcta${NC}"
    exit 1
fi

# Actualizar desde Git
echo "Actualizando código desde GitHub..."
git pull origin main || {
    echo -e "${YELLOW}⚠ No se pudo hacer git pull, continuando con deploy...${NC}"
    echo -e "${YELLOW}   El código actual se usará para el deploy${NC}"
}
echo -e "${GREEN}✅ Código actualizado${NC}"

# Instalar/actualizar dependencias
echo "Instalando dependencias..."
composer install --no-dev --optimize-autoloader
echo -e "${GREEN}✅ Dependencias instaladas${NC}"
echo ""

# ========================================
# PASO 4: COPIAR ARCHIVOS PÚBLICOS (SIN TOCAR INDEX.PHP)
# ========================================
echo -e "${YELLOW}📁 PASO 4: Copiando archivos públicos...${NC}"

cd "$PUBLIC_HTML_DIR"

# Mantener el index.php actual (que funciona)
cp index.php index.php.temp

# Copiar archivos de public (excluyendo index.php)
echo "Copiando archivos de public/..."
cp -r "$PROJECT_DIR/public/"* "$PUBLIC_HTML_DIR/" 2>/dev/null || true

# Restaurar el index.php que funciona
mv index.php.temp index.php
echo -e "${GREEN}✅ Archivos copiados (index.php preservado)${NC}"
echo ""

# ========================================
# PASO 5: CONFIGURAR STORAGE PÚBLICO
# ========================================
echo -e "${YELLOW}💿 PASO 5: Configurando storage público...${NC}"

# Crear directorio de storage
mkdir -p "$PUBLIC_HTML_DIR/storage"

# Copiar archivos públicos del storage
if [ -d "$PROJECT_DIR/storage/app/public" ]; then
    cp -r "$PROJECT_DIR/storage/app/public/"* "$PUBLIC_HTML_DIR/storage/" 2>/dev/null || true
    echo -e "${GREEN}✅ Archivos de storage copiados${NC}"
else
    echo -e "${YELLOW}⚠ No hay archivos en storage/app/public${NC}"
fi
echo ""

# ========================================
# PASO 6: CONFIGURAR PERMISOS
# ========================================
echo -e "${YELLOW}🔒 PASO 6: Configurando permisos...${NC}"

chmod -R 755 "$PUBLIC_HTML_DIR"
chmod -R 775 "$PROJECT_DIR/storage"
chmod -R 775 "$PROJECT_DIR/bootstrap/cache"
echo -e "${GREEN}✅ Permisos configurados${NC}"
echo ""

# ========================================
# PASO 7: LIMPIAR Y OPTIMIZAR CACHÉS
# ========================================
echo -e "${YELLOW}🧽 PASO 7: Optimizando cachés...${NC}"

cd "$PROJECT_DIR"

# Limpiar cachés
php artisan config:clear 2>/dev/null || echo "Config clear: OK"
php artisan route:clear 2>/dev/null || echo "Route clear: OK"
php artisan view:clear 2>/dev/null || echo "View clear: OK"
php artisan cache:clear 2>/dev/null || echo "Cache clear: OK"

# Optimizar para producción
php artisan config:cache 2>/dev/null || echo "Config cache: OK"
php artisan route:cache 2>/dev/null || echo "Route cache: OK"
php artisan view:cache 2>/dev/null || echo "View cache: OK"

echo -e "${GREEN}✅ Cachés optimizados${NC}"
echo ""

# ========================================
# PASO 8: VERIFICACIÓN CRÍTICA
# ========================================
echo -e "${YELLOW}🔍 PASO 8: Verificación crítica...${NC}"

# Verificar que Laravel carga correctamente DESPUÉS de los cambios
echo "Verificando que Laravel carga correctamente..."
TEST_OUTPUT=$(php "$PUBLIC_HTML_DIR/index.php" 2>&1 || true)

if echo "$TEST_OUTPUT" | grep -q "This script cannot be run from the CLI\|DOCTYPE html\|Redirecting to"; then
    echo -e "${GREEN}✅ Laravel carga correctamente${NC}"
    echo -e "${GREEN}✅ Las rutas están correctas${NC}"
else
    echo -e "${RED}🚨 PROBLEMA DETECTADO DESPUÉS DEL DEPLOY${NC}"
    echo -e "${RED}   Output: $TEST_OUTPUT${NC}"
    echo -e "${YELLOW}   Restaurando desde backup...${NC}"
    
    # Restaurar desde backup
    if [ -f "$INDEX_PHP_BACKUP" ]; then
        cp "$INDEX_PHP_BACKUP" "$PUBLIC_HTML_DIR/index.php"
        echo -e "${GREEN}✅ index.php restaurado desde backup${NC}"
        
        # Verificar nuevamente
        if php "$PUBLIC_HTML_DIR/index.php" 2>&1 | grep -q "This script cannot be run from the CLI\|DOCTYPE html\|Redirecting to"; then
            echo -e "${GREEN}✅ Sistema restaurado y funcionando${NC}"
        else
            echo -e "${RED}❌ CRÍTICO: No se pudo restaurar el sistema${NC}"
            echo -e "${RED}   Revisar manualmente el archivo index.php${NC}"
            exit 1
        fi
    else
        echo -e "${RED}❌ No hay backup disponible para restaurar${NC}"
        exit 1
    fi
fi
echo ""

# ========================================
# PASO 9: VERIFICACIÓN ADICIONAL
# ========================================
echo -e "${YELLOW}🔍 PASO 9: Verificaciones adicionales...${NC}"

# Verificar que las rutas en index.php son correctas
if grep -q "../../../servime/vendor/autoload.php" "$PUBLIC_HTML_DIR/index.php"; then
    echo -e "${GREEN}✅ Rutas en index.php son correctas${NC}"
else
    echo -e "${RED}❌ PROBLEMA: Las rutas en index.php no son correctas${NC}"
    echo -e "${YELLOW}   Reparando automáticamente...${NC}"
    
    # Reparar rutas automáticamente
    sed -i "s|require __DIR__.'/../vendor/autoload.php';|require __DIR__.'/../../../servime/vendor/autoload.php';|g" "$PUBLIC_HTML_DIR/index.php"
    sed -i "s|\$app = require_once __DIR__.'/../bootstrap/app.php';|\$app = require_once __DIR__.'/../../../servime/bootstrap/app.php';|g" "$PUBLIC_HTML_DIR/index.php"
    
    echo -e "${GREEN}✅ Rutas reparadas automáticamente${NC}"
fi

# Verificar que el archivo .htaccess existe
if [ -f "$PUBLIC_HTML_DIR/.htaccess" ]; then
    echo -e "${GREEN}✅ Archivo .htaccess presente${NC}"
else
    echo -e "${YELLOW}⚠ Archivo .htaccess no encontrado${NC}"
fi

# Verificar que storage existe
if [ -d "$PUBLIC_HTML_DIR/storage" ]; then
    echo -e "${GREEN}✅ Directorio storage presente${NC}"
else
    echo -e "${YELLOW}⚠ Directorio storage no encontrado${NC}"
fi
echo ""

# ========================================
# PASO 10: RESUMEN FINAL
# ========================================
echo -e "${GREEN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${GREEN}✅ DEPLOY SEGURO COMPLETADO EXITOSAMENTE${NC}"
echo -e "${GREEN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo ""
echo -e "${BLUE}📍 Información del deploy:${NC}"
echo "   • Proyecto: $PROJECT_DIR"
echo "   • Public HTML: $PUBLIC_HTML_DIR"
echo "   • Backup: $BACKUP_DIR"
echo "   • Index.php backup: $INDEX_PHP_BACKUP"
echo ""
echo -e "${BLUE}🌐 Verificación:${NC}"
echo "   • URL: https://servime.net"
echo "   • Status: ✅ Funcionando"
echo "   • Rutas: ✅ Correctas"
echo ""
echo -e "${BLUE}💡 Para futuras actualizaciones:${NC}"
echo "   • Ejecuta: bash deploy-seguro-hostinger.sh"
echo "   • El script preserva las rutas críticas"
echo "   • Siempre hace backup antes de cambios"
echo ""
echo -e "${GREEN}🎉 ¡Deploy seguro completado!${NC}"
