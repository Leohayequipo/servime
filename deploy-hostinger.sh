#!/bin/bash

# ========================================
# Script de Deploy para Hostinger
# Servime - Sistema de Menús con IA
# ========================================

echo "🚀 Iniciando deploy en Hostinger..."

# Colores
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

# ========================================
# 1. VERIFICAR REQUISITOS
# ========================================
echo -e "\n${YELLOW}📋 Verificando requisitos...${NC}"

# Verificar PHP
PHP_VERSION=$(php -v | head -n 1 | cut -d " " -f 2 | cut -d "." -f 1,2)
echo "   ✅ PHP version: $PHP_VERSION"

if (( $(echo "$PHP_VERSION < 8.1" | bc -l) )); then
    echo -e "   ${RED}❌ Error: PHP 8.1 o superior requerido${NC}"
    exit 1
fi

# Verificar Composer
if ! command -v composer &> /dev/null; then
    echo -e "   ${RED}❌ Error: Composer no está instalado${NC}"
    exit 1
fi
echo "   ✅ Composer instalado"

# Verificar Git
if ! command -v git &> /dev/null; then
    echo -e "   ${RED}❌ Error: Git no está instalado${NC}"
    exit 1
fi
echo "   ✅ Git instalado"

# ========================================
# 2. MODO MANTENIMIENTO
# ========================================
echo -e "\n${YELLOW}🔧 Activando modo mantenimiento...${NC}"
php artisan down --retry=60 || true

# ========================================
# 3. ACTUALIZAR CÓDIGO
# ========================================
echo -e "\n${YELLOW}📦 Actualizando código desde Git...${NC}"
git pull origin main

if [ $? -ne 0 ]; then
    echo -e "${RED}❌ Error al actualizar código${NC}"
    php artisan up
    exit 1
fi

# ========================================
# 4. INSTALAR DEPENDENCIAS
# ========================================
echo -e "\n${YELLOW}📚 Instalando dependencias de Composer...${NC}"
composer install --no-dev --optimize-autoloader --no-interaction

if [ $? -ne 0 ]; then
    echo -e "${RED}❌ Error al instalar dependencias${NC}"
    php artisan up
    exit 1
fi

# ========================================
# 5. LIMPIAR CACHÉS VIEJOS
# ========================================
echo -e "\n${YELLOW}🧹 Limpiando cachés...${NC}"
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# ========================================
# 6. EJECUTAR MIGRACIONES
# ========================================
echo -e "\n${YELLOW}🗄️  Ejecutando migraciones...${NC}"
php artisan migrate --force

if [ $? -ne 0 ]; then
    echo -e "${RED}❌ Error en migraciones${NC}"
    php artisan up
    exit 1
fi

# ========================================
# 7. OPTIMIZAR APLICACIÓN
# ========================================
echo -e "\n${YELLOW}⚡ Optimizando aplicación...${NC}"
php artisan config:cache
php artisan route:cache
php artisan view:cache

# ========================================
# 8. CREAR SYMLINK DE STORAGE
# ========================================
echo -e "\n${YELLOW}🔗 Creando symlink de storage...${NC}"
php artisan storage:link || true

# ========================================
# 9. ACTUALIZAR PUBLIC_HTML
# ========================================
echo -e "\n${YELLOW}📂 Actualizando public_html...${NC}"

# Copiar archivos públicos (CSS, JS, imágenes)
if [ -d "../public_html" ]; then
    echo "   Copiando archivos estáticos..."
    cp -r public/css ../public_html/ 2>/dev/null || true
    cp -r public/js ../public_html/ 2>/dev/null || true
    cp -r public/images ../public_html/ 2>/dev/null || true
    cp public/.htaccess ../public_html/ 2>/dev/null || true
    echo "   ✅ Archivos públicos actualizados"
else
    echo "   ⚠️  Directorio public_html no encontrado (omitiendo)"
fi

# ========================================
# 10. REINICIAR QUEUES
# ========================================
echo -e "\n${YELLOW}🔄 Reiniciando queues...${NC}"
php artisan queue:restart || true

# ========================================
# 11. VERIFICAR PERMISOS
# ========================================
echo -e "\n${YELLOW}🔐 Verificando permisos...${NC}"
chmod -R 755 storage bootstrap/cache
echo "   ✅ Permisos configurados"

# ========================================
# 12. SALIR DE MODO MANTENIMIENTO
# ========================================
echo -e "\n${YELLOW}✅ Desactivando modo mantenimiento...${NC}"
php artisan up

# ========================================
# RESUMEN
# ========================================
echo -e "\n${GREEN}========================================${NC}"
echo -e "${GREEN}🎉 ¡Deploy completado exitosamente!${NC}"
echo -e "${GREEN}========================================${NC}"
echo -e "\n${YELLOW}📊 Resumen:${NC}"
echo "   ✅ Código actualizado desde Git"
echo "   ✅ Dependencias instaladas"
echo "   ✅ Migraciones ejecutadas"
echo "   ✅ Cachés optimizados"
echo "   ✅ Aplicación en línea"
echo ""
echo -e "${GREEN}Tu aplicación está lista en: $(php artisan route:list --path=/ --method=GET | head -n 1)${NC}"
echo ""

# Mostrar logs recientes
echo -e "\n${YELLOW}📝 Últimas líneas del log:${NC}"
tail -n 5 storage/logs/laravel.log 2>/dev/null || echo "   (sin logs recientes)"

echo -e "\n${GREEN}✨ Deploy finalizado!${NC}\n"

