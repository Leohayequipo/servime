#!/bin/bash
# 🚀 Script para configurar auto-deploy con GitHub Actions
# Ejecutar en el servidor Hostinger

set -e

# Colores
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

echo -e "${BLUE}🚀 CONFIGURANDO AUTO-DEPLOY CON GITHUB ACTIONS${NC}"
echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo ""

# Variables
PROJECT_DIR="$HOME/servime"
GITHUB_USER="Leohayequipo"
REPO_NAME="servime"

echo -e "${YELLOW}📋 Configuración:${NC}"
echo "   • Proyecto: $PROJECT_DIR"
echo "   • GitHub: $GITHUB_USER/$REPO_NAME"
echo ""

# Verificar que estamos en el directorio correcto
if [ ! -f "$PROJECT_DIR/artisan" ]; then
    echo -e "${RED}❌ No se encontró artisan en $PROJECT_DIR${NC}"
    echo -e "${RED}   Verificar que el proyecto Laravel esté en la ubicación correcta${NC}"
    exit 1
fi

# Crear directorio .ssh si no existe
echo -e "${YELLOW}🔐 Configurando SSH...${NC}"
mkdir -p ~/.ssh
chmod 700 ~/.ssh

# Verificar si ya existe authorized_keys
if [ -f ~/.ssh/authorized_keys ]; then
    echo -e "${GREEN}✅ authorized_keys ya existe${NC}"
else
    echo -e "${YELLOW}⚠ Creando authorized_keys...${NC}"
    touch ~/.ssh/authorized_keys
    chmod 600 ~/.ssh/authorized_keys
fi

echo -e "${YELLOW}📝 Instrucciones para configurar GitHub Actions:${NC}"
echo ""
echo -e "${BLUE}1. GENERAR CLAVE SSH EN TU PC:${NC}"
echo "   ssh-keygen -t rsa -b 4096 -C \"github-actions-servime\""
echo "   (Presiona Enter para usar ubicación por defecto)"
echo "   (NO pongas contraseña - presiona Enter dos veces)"
echo ""
echo -e "${BLUE}2. OBTENER CLAVE PÚBLICA:${NC}"
echo "   Get-Content ~/.ssh/id_rsa.pub"
echo "   (Copia TODO el contenido)"
echo ""
echo -e "${BLUE}3. AGREGAR CLAVE AL SERVIDOR:${NC}"
echo "   echo \"TU_CLAVE_PUBLICA_AQUI\" >> ~/.ssh/authorized_keys"
echo ""
echo -e "${BLUE}4. OBTENER CLAVE PRIVADA:${NC}"
echo "   Get-Content ~/.ssh/id_rsa"
echo "   (Copia TODO el contenido incluyendo -----BEGIN y -----END)"
echo ""
echo -e "${BLUE}5. CONFIGURAR EN GITHUB:${NC}"
echo "   • Ve a: https://github.com/$GITHUB_USER/$REPO_NAME/settings/secrets/actions"
echo "   • Click en 'New repository secret'"
echo "   • Name: HOSTINGER_SSH_KEY"
echo "   • Secret: (pegar la clave privada)"
echo ""
echo -e "${BLUE}6. PROBAR CONEXIÓN:${NC}"
echo "   ssh u560898880@br-asc-web803.main-hosting.eu"
echo "   (Debe conectar sin pedir contraseña)"
echo ""

# Verificar que los scripts de deploy existen
echo -e "${YELLOW}🔍 Verificando scripts de deploy...${NC}"

if [ -f "$PROJECT_DIR/deploy-seguro-hostinger.sh" ]; then
    echo -e "${GREEN}✅ deploy-seguro-hostinger.sh encontrado${NC}"
    chmod +x "$PROJECT_DIR/deploy-seguro-hostinger.sh"
else
    echo -e "${YELLOW}⚠ deploy-seguro-hostinger.sh no encontrado${NC}"
    echo -e "${YELLOW}   Subir desde tu PC: scp deploy-seguro-hostinger.sh u560898880@br-asc-web803.main-hosting.eu:~/servime/${NC}"
fi

if [ -f "$PROJECT_DIR/reparar-rutas-emergencia.sh" ]; then
    echo -e "${GREEN}✅ reparar-rutas-emergencia.sh encontrado${NC}"
    chmod +x "$PROJECT_DIR/reparar-rutas-emergencia.sh"
else
    echo -e "${YELLOW}⚠ reparar-rutas-emergencia.sh no encontrado${NC}"
    echo -e "${YELLOW}   Subir desde tu PC: scp reparar-rutas-emergencia.sh u560898880@br-asc-web803.main-hosting.eu:~/servime/${NC}"
fi

# Verificar que el sistema funciona
echo -e "${YELLOW}🔍 Verificando sistema actual...${NC}"
if php ~/domains/servime.net/public_html/index.php 2>&1 | grep -q "This script cannot be run from the CLI"; then
    echo -e "${GREEN}✅ Sistema funcionando correctamente${NC}"
else
    echo -e "${YELLOW}⚠ Sistema no está funcionando - ejecutar reparación${NC}"
    if [ -f "$PROJECT_DIR/reparar-rutas-emergencia.sh" ]; then
        echo -e "${YELLOW}🔧 Ejecutando reparación...${NC}"
        bash "$PROJECT_DIR/reparar-rutas-emergencia.sh"
    fi
fi

echo ""
echo -e "${GREEN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${GREEN}✅ CONFIGURACIÓN PREPARADA${NC}"
echo -e "${GREEN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo ""
echo -e "${BLUE}📋 Próximos pasos:${NC}"
echo "1. Seguir las instrucciones de arriba para configurar SSH"
echo "2. Subir archivos al repositorio GitHub"
echo "3. Hacer push para probar el auto-deploy"
echo ""
echo -e "${BLUE}🌐 URLs importantes:${NC}"
echo "• Repositorio: https://github.com/$GITHUB_USER/$REPO_NAME"
echo "• Actions: https://github.com/$GITHUB_USER/$REPO_NAME/actions"
echo "• Secrets: https://github.com/$GITHUB_USER/$REPO_NAME/settings/secrets/actions"
echo "• Sitio: https://servime.net"
echo ""
echo -e "${GREEN}🎉 ¡Configuración lista!${NC}"
