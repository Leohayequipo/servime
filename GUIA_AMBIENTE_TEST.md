# 🧪 GUÍA PARA CREAR AMBIENTE DE TEST

## 📋 **RESUMEN**

Guía completa para crear un ambiente de test limpio y funcional para ServiME.

## 🎯 **OBJETIVOS**

- Crear ambiente de test aislado
- Configurar auto-deploy para test
- Mantener producción estable
- Facilitar desarrollo y pruebas

## 🏗️ **ESTRUCTURA RECOMENDADA**

### **Repositorios:**
- `servime` (main) - Producción
- `servime-test` - Ambiente de test
- `servime-dev` - Desarrollo local

### **Servidores:**
- **Producción**: `servime.net` (Hostinger)
- **Test**: `test.servime.net` (Hostinger)
- **Local**: `localhost:8000` (Laravel)

## 🚀 **PASOS PARA CREAR AMBIENTE DE TEST**

### **1. Crear Repositorio de Test**
```bash
# Clonar repositorio principal
git clone https://github.com/Leohayequipo/servime.git servime-test
cd servime-test

# Crear nueva rama para test
git checkout -b test-environment
git push -u origin test-environment
```

### **2. Configurar Servidor de Test**
```bash
# Conectar al servidor Hostinger
ssh -p 65002 u560898880@45.152.46.147

# Crear directorio de test
mkdir -p ~/servime-test
cd ~/servime-test

# Clonar repositorio de test
git clone https://github.com/Leohayequipo/servime.git .
git checkout test-environment
```

### **3. Configurar Subdominio de Test**
- **Panel Hostinger**: Crear subdominio `test.servime.net`
- **DNS**: Apuntar a mismo servidor
- **Directorio**: `~/domains/test.servime.net/public_html/`

### **4. Configurar Auto-Deploy para Test**
```yaml
# .github/workflows/auto-deploy-test.yml
name: 🧪 Auto Deploy Test Environment

on:
  push:
    branches: [ test-environment ]
  workflow_dispatch:

jobs:
  deploy-test:
    name: 🧪 Deploy Test Environment
    runs-on: ubuntu-latest
    
    steps:
    - name: 📥 Checkout código
      uses: actions/checkout@v4
      
    - name: 🔐 Configurar SSH
      run: |
        mkdir -p ~/.ssh
        echo "${{ secrets.HOSTINGER_SSH_KEY }}" > ~/.ssh/id_rsa
        chmod 600 ~/.ssh/id_rsa
        ssh-keyscan -H -p 65002 45.152.46.147 >> ~/.ssh/known_hosts
        
    - name: 📤 Subir script de deploy
      run: |
        scp -i ~/.ssh/id_rsa -P 65002 deploy-test.sh u560898880@45.152.46.147:~/servime-test/
        
    - name: 🚀 Ejecutar deploy de test
      run: |
        ssh -i ~/.ssh/id_rsa -p 65002 u560898880@45.152.46.147 "cd ~/servime-test && bash deploy-test.sh"
```

### **5. Crear Script de Deploy para Test**
```bash
#!/bin/bash
# deploy-test.sh

echo "🧪 DEPLOY TEST ENVIRONMENT - ServiME"

# Variables
PROJECT_DIR="/home/u560898880/servime-test"
PUBLIC_HTML_DIR="/home/u560898880/domains/test.servime.net/public_html"

# Colores
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

echo -e "${YELLOW}📋 Configuración:${NC}"
echo "   • Proyecto: $PROJECT_DIR"
echo "   • Public HTML: $PUBLIC_HTML_DIR"

# 1. Actualizar código
echo -e "${YELLOW}📦 Actualizando código...${NC}"
git pull origin test-environment

# 2. Instalar dependencias
echo -e "${YELLOW}📚 Instalando dependencias...${NC}"
composer install --no-dev --optimize-autoloader

# 3. Copiar archivos públicos
echo -e "${YELLOW}📁 Copiando archivos públicos...${NC}"
cp -r "$PROJECT_DIR/public/"* "$PUBLIC_HTML_DIR/"

# 4. Configurar index.php para test
echo -e "${YELLOW}🔧 Configurando index.php...${NC}"
sed -i "s|require __DIR__.'/../vendor/autoload.php';|require __DIR__.'/../../../servime-test/vendor/autoload.php';|g" "$PUBLIC_HTML_DIR/index.php"
sed -i "s|\$app = require_once __DIR__.'/../bootstrap/app.php';|\$app = require_once __DIR__.'/../../../servime-test/bootstrap/app.php';|g" "$PUBLIC_HTML_DIR/index.php"

# 5. Configurar storage
echo -e "${YELLOW}💿 Configurando storage...${NC}"
rm -rf "$PUBLIC_HTML_DIR/storage"
mkdir -p "$PUBLIC_HTML_DIR/storage"
if [ -d "$PROJECT_DIR/storage/app/public" ]; then
    cp -r "$PROJECT_DIR/storage/app/public/"* "$PUBLIC_HTML_DIR/storage/"
fi

# 6. Limpiar cachés
echo -e "${YELLOW}🧽 Limpiando cachés...${NC}"
cd "$PROJECT_DIR"
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# 7. Optimizar
echo -e "${YELLOW}⚡ Optimizando...${NC}"
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 8. Verificar
echo -e "${YELLOW}🔍 Verificando...${NC}"
php "$PUBLIC_HTML_DIR/index.php" || {
    echo -e "${RED}❌ Error en deploy de test${NC}"
    exit 1
}

echo -e "${GREEN}✅ Deploy de test exitoso!${NC}"
echo -e "${GREEN}🌐 Test URL: https://test.servime.net${NC}"
```

## 🔄 **FLUJO DE TRABAJO RECOMENDADO**

### **Desarrollo:**
1. **Trabajar en local** con `php artisan serve`
2. **Probar cambios** en ambiente local
3. **Commit** a rama `test-environment`
4. **Push** para deploy automático a test
5. **Verificar** en `https://test.servime.net`

### **Producción:**
1. **Merge** de `test-environment` a `main`
2. **Deploy automático** a producción
3. **Verificar** en `https://servime.net`

## 🛠️ **COMANDOS ÚTILES**

### **Crear Ambiente de Test:**
```bash
# Clonar y configurar
git clone https://github.com/Leohayequipo/servime.git servime-test
cd servime-test
git checkout -b test-environment

# Configurar auto-deploy
cp .github/workflows/auto-deploy.yml .github/workflows/auto-deploy-test.yml
# Editar para usar test-environment branch

# Crear script de deploy
cp deploy-actualiza.sh deploy-test.sh
# Editar para usar directorios de test
```

### **Sincronizar con Producción:**
```bash
# Desde test-environment
git checkout main
git pull origin main
git checkout test-environment
git merge main
git push origin test-environment
```

## 📊 **VENTAJAS DEL AMBIENTE DE TEST**

- ✅ **Aislamiento**: Test sin afectar producción
- ✅ **Auto-deploy**: Deploy automático para test
- ✅ **Desarrollo seguro**: Probar cambios sin riesgo
- ✅ **CI/CD**: Flujo completo de integración continua
- ✅ **Rollback**: Fácil reversión de cambios

## 🎯 **PRÓXIMOS PASOS**

1. **Crear repositorio de test**
2. **Configurar subdominio**
3. **Implementar auto-deploy**
4. **Probar flujo completo**
5. **Documentar proceso**

---

**Estado**: 📋 Listo para implementar
**Complejidad**: 🟡 Media
**Tiempo estimado**: 2-3 horas
