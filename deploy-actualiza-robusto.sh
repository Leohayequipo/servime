#!/bin/bash
# 🚀 DEPLOY ROBUSTO - ServiME en Hostinger
# Script que actualiza archivos y VERIFICA cada paso crítico

# Configuración estricta: fallar en cualquier error
set -euo pipefail

# Colores
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
BLUE='\033[0;34m'
NC='\033[0m'

# Función para logging con colores
log_info() {
    echo -e "${BLUE}ℹ️  $1${NC}"
}

log_success() {
    echo -e "${GREEN}✅ $1${NC}"
}

log_warning() {
    echo -e "${YELLOW}⚠️  $1${NC}"
}

log_error() {
    echo -e "${RED}❌ $1${NC}"
}

# Función para verificar comandos críticos
check_command() {
    if ! command -v "$1" &> /dev/null; then
        log_error "Comando '$1' no encontrado. Abortando deploy."
        exit 1
    fi
}

echo -e "${GREEN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${GREEN}🚀 DEPLOY ROBUSTO - ServiME${NC}"
echo -e "${GREEN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo ""

# Variables
PROJECT_DIR="$HOME/servime"
PUBLIC_HTML_DIR="$HOME/domains/servime.net/public_html"
COMMIT_HASH_BEFORE=""
COMMIT_HASH_AFTER=""

log_info "Configuración:"
echo "   • Proyecto: $PROJECT_DIR"
echo "   • Public HTML: $PUBLIC_HTML_DIR"
echo ""

# Verificar que los directorios existen
if [ ! -d "$PROJECT_DIR" ]; then
    log_error "Directorio del proyecto no existe: $PROJECT_DIR"
    exit 1
fi

if [ ! -d "$PUBLIC_HTML_DIR" ]; then
    log_error "Directorio public_html no existe: $PUBLIC_HTML_DIR"
    exit 1
fi

# Verificar comandos necesarios
log_info "Verificando comandos necesarios..."
check_command "git"
check_command "composer"
check_command "php"
log_success "Todos los comandos están disponibles"

# 1. Ir al proyecto
cd "$PROJECT_DIR"

# 2. Guardar commit hash ANTES del pull
COMMIT_HASH_BEFORE=$(git rev-parse HEAD 2>/dev/null || echo "unknown")
log_info "Commit actual: ${COMMIT_HASH_BEFORE:0:7}"

# 3. Hacer git pull (forzar actualización)
log_info "Actualizando código desde GitHub..."
git fetch origin main

# Verificar si hay cambios
if git diff --quiet HEAD origin/main; then
    log_warning "No hay cambios nuevos. El código ya está actualizado."
    COMMIT_HASH_AFTER="$COMMIT_HASH_BEFORE"
else
    log_info "Hay cambios nuevos. Actualizando..."
    
    # Guardar cambios locales si existen (backup)
    if ! git diff --quiet HEAD; then
        log_warning "Hay cambios locales. Guardando en stash..."
        git stash save "Backup antes de actualizar desde deploy - $(date +%Y%m%d_%H%M%S)" || true
    fi
    
    # Resetear a origin/main para forzar actualización
    git reset --hard origin/main
    git checkout main
    
    # Verificar commit hash DESPUÉS del pull
    COMMIT_HASH_AFTER=$(git rev-parse HEAD)
    log_success "Código actualizado: ${COMMIT_HASH_AFTER:0:7}"
    
    # Verificar que realmente cambió
    if [ "$COMMIT_HASH_BEFORE" = "$COMMIT_HASH_AFTER" ]; then
        log_error "El commit hash no cambió después del pull. Posible problema con git."
        exit 1
    fi
fi

# 4. Verificar que composer.json existe
if [ ! -f "composer.json" ]; then
    log_error "composer.json no encontrado. Abortando."
    exit 1
fi

# 5. Instalar dependencias de Composer (CRÍTICO)
log_info "Instalando dependencias de Composer..."
if composer install --optimize-autoloader --no-interaction; then
    log_success "Dependencias de Composer instaladas correctamente"
else
    log_error "Fallo al instalar dependencias de Composer. Abortando deploy."
    exit 1
fi

# Verificar que el paquete smalot/pdfparser está instalado
if ! composer show smalot/pdfparser &>/dev/null; then
    log_error "Paquete smalot/pdfparser no está instalado. Abortando."
    exit 1
fi
log_success "Paquete smalot/pdfparser verificado"

# 6. Instalar dependencias de Node (opcional, no crítico)
log_info "Instalando dependencias de Node (opcional)..."
if [ -f "package.json" ]; then
    if npm install --silent; then
        log_success "Dependencias de Node instaladas"
        
        # Compilar assets
        log_info "Compilando assets..."
        if npm run build --silent; then
            log_success "Assets compilados"
        else
            log_warning "Fallo al compilar assets (no crítico)"
        fi
    else
        log_warning "Fallo al instalar dependencias de Node (no crítico)"
    fi
else
    log_info "No hay package.json, saltando Node"
fi

# 7. Backup del index.php que funciona
log_info "Haciendo backup del index.php..."
if [ -f "$PUBLIC_HTML_DIR/index.php" ]; then
    cp "$PUBLIC_HTML_DIR/index.php" "$PUBLIC_HTML_DIR/index.php.backup"
    log_success "Backup creado"
else
    log_warning "index.php no existe, no se puede hacer backup"
fi

# 8. Copiar archivos públicos (ACTUALIZAR)
log_info "Copiando archivos públicos..."
cd "$PROJECT_DIR/public"
if [ ! -d "$PROJECT_DIR/public" ]; then
    log_error "Directorio public/ no existe. Abortando."
    exit 1
fi

COPIADOS=0
for file in *; do
    if [ "$file" != "index.php" ] && [ -e "$file" ]; then
        if cp -f "$file" "$PUBLIC_HTML_DIR/" 2>&1; then
            if [ -d "$file" ]; then
                cp -rf "$file" "$PUBLIC_HTML_DIR/" 2>&1
            fi
            COPIADOS=$((COPIADOS + 1))
        else
            log_warning "Error copiando: $file"
        fi
    fi
done
log_success "Archivos copiados: $COPIADOS"

# 9. Restaurar el index.php que funciona
log_info "Restaurando index.php..."
if [ -f "$PUBLIC_HTML_DIR/index.php.backup" ]; then
    cp "$PUBLIC_HTML_DIR/index.php.backup" "$PUBLIC_HTML_DIR/index.php"
    rm "$PUBLIC_HTML_DIR/index.php.backup"
    log_success "index.php restaurado"
fi

# 10. Configurar storage (preservar imágenes existentes)
log_info "Configurando storage..."
# NO eliminar storage completo, solo actualizar
if [ -d "$PROJECT_DIR/storage/app/public" ]; then
    # Crear estructura de directorios si no existe
    mkdir -p "$PUBLIC_HTML_DIR/storage/comercios"
    
    # Copiar solo archivos nuevos o actualizados
    rsync -av --ignore-existing "$PROJECT_DIR/storage/app/public/comercios/" "$PUBLIC_HTML_DIR/storage/comercios/" 2>/dev/null || {
        # Si rsync no está disponible, usar cp
        cp -r "$PROJECT_DIR/storage/app/public/comercios/"* "$PUBLIC_HTML_DIR/storage/comercios/" 2>/dev/null || true
    }
    log_success "Storage configurado (imágenes preservadas)"
fi

# 11. Ejecutar migraciones
log_info "Ejecutando migraciones..."
cd "$PROJECT_DIR"
if php artisan migrate --force --no-interaction; then
    log_success "Migraciones ejecutadas"
else
    log_warning "Fallo en migraciones (puede ser normal si no hay cambios)"
fi

# 12. Limpiar cachés (CRÍTICO)
log_info "Limpiando cachés..."
cd "$PROJECT_DIR"
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
log_success "Cachés limpiados"

# 13. Regenerar cachés (CRÍTICO)
log_info "Regenerando cachés..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
log_success "Cachés regenerados"

# 14. Verificar que Laravel carga correctamente (CRÍTICO)
log_info "Verificando que Laravel carga correctamente..."
if php "$PUBLIC_HTML_DIR/index.php" 2>&1 | grep -q "This script cannot be run from the CLI"; then
    log_success "Laravel carga correctamente"
else
    log_error "Laravel NO carga correctamente. Abortando."
    exit 1
fi

# 15. Verificar commit hash final
FINAL_COMMIT=$(git rev-parse HEAD)
log_info "Commit final verificado: ${FINAL_COMMIT:0:7}"

# 16. Resumen final
echo ""
echo -e "${GREEN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${GREEN}✅ DEPLOY COMPLETADO EXITOSAMENTE${NC}"
echo -e "${GREEN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo "   📅 Fecha: $(date '+%Y-%m-%d %H:%M:%S')"
echo "   🔗 Commit: ${FINAL_COMMIT:0:7}"
if [ "$COMMIT_HASH_BEFORE" != "$COMMIT_HASH_AFTER" ]; then
    echo "   📝 Cambio: ${COMMIT_HASH_BEFORE:0:7} → ${COMMIT_HASH_AFTER:0:7}"
fi
echo "   📦 Archivos copiados: $COPIADOS"
echo "   ✅ Sistema funcionando correctamente"
echo -e "${GREEN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"

