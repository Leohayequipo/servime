#!/bin/bash
# 🚨 SCRIPT DE EMERGENCIA - Reparar rutas rotas
# Usar SOLO cuando el sitio esté caído

set -e

# Colores
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

echo -e "${RED}🚨 SCRIPT DE EMERGENCIA - REPARACIÓN DE RUTAS${NC}"
echo -e "${RED}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo ""

# Variables
PUBLIC_HTML_DIR="$HOME/domains/servime.net/public_html"
BACKUP_DIR="$HOME/backups/emergencia_$(date +%Y%m%d_%H%M%S)"

echo -e "${YELLOW}🔍 Verificando problema actual...${NC}"

# Verificar si hay problema
if php "$PUBLIC_HTML_DIR/index.php" 2>&1 | grep -q "This script cannot be run from the CLI"; then
    echo -e "${GREEN}✅ El sistema YA está funcionando correctamente${NC}"
    echo -e "${GREEN}   No se necesita reparación${NC}"
    exit 0
fi

echo -e "${RED}🚨 PROBLEMA CONFIRMADO - Iniciando reparación...${NC}"
echo ""

# Crear backup de emergencia
echo -e "${YELLOW}💾 Creando backup de emergencia...${NC}"
mkdir -p "$BACKUP_DIR"
cp "$PUBLIC_HTML_DIR/index.php" "$BACKUP_DIR/index.php.roto"
echo -e "${GREEN}✅ Backup creado: $BACKUP_DIR/index.php.roto${NC}"
echo ""

# Reparar rutas automáticamente
echo -e "${YELLOW}🔧 Reparando rutas automáticamente...${NC}"

cd "$PUBLIC_HTML_DIR"

# Reparar línea 1: vendor/autoload.php
sed -i "s|require __DIR__.'/../vendor/autoload.php';|require __DIR__.'/../../../servime/vendor/autoload.php';|g" index.php

# Reparar línea 2: bootstrap/app.php
sed -i "s|\$app = require_once __DIR__.'/../bootstrap/app.php';|\$app = require_once __DIR__.'/../../../servime/bootstrap/app.php';|g" index.php

echo -e "${GREEN}✅ Rutas reparadas${NC}"
echo ""

# Verificar reparación
echo -e "${YELLOW}🔍 Verificando reparación...${NC}"

if php "$PUBLIC_HTML_DIR/index.php" 2>&1 | grep -q "This script cannot be run from the CLI"; then
    echo -e "${GREEN}✅ ¡REPARACIÓN EXITOSA!${NC}"
    echo -e "${GREEN}✅ Laravel carga correctamente${NC}"
    echo -e "${GREEN}✅ El sitio debe estar funcionando${NC}"
    echo ""
    echo -e "${BLUE}🌐 Verificar en: https://servime.net${NC}"
else
    echo -e "${RED}❌ REPARACIÓN FALLÓ${NC}"
    echo -e "${RED}   Revisar manualmente el archivo index.php${NC}"
    echo -e "${YELLOW}   Backup disponible en: $BACKUP_DIR/index.php.roto${NC}"
    exit 1
fi

echo ""
echo -e "${GREEN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${GREEN}✅ EMERGENCIA RESUELTA${NC}"
echo -e "${GREEN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo ""
echo -e "${BLUE}💡 Para prevenir futuros problemas:${NC}"
echo "   • Usar: bash deploy-seguro-hostinger.sh"
echo "   • Verificar rutas después de cada cambio"
echo "   • Mantener backups actualizados"
echo ""
echo -e "${GREEN}🎉 ¡Sistema restaurado!${NC}"
