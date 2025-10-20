# 🚀 Quick Start - Deploy en Hostinger

## ⚡ **VERSIÓN RÁPIDA (5 Minutos)**

### **1️⃣ Obtener Credenciales SSH**

1. Entra a https://hpanel.hostinger.com
2. Ve a **"Advanced"** → **"SSH Access"**
3. Anota:
   ```
   Host: tu-dominio.com
   User: u123456
   Port: 22
   Password: [tu contraseña de hPanel]
   ```

---

### **2️⃣ Conectar por SSH**

Desde PowerShell en tu PC:

```powershell
ssh u123456@tu-dominio.com
```

**Nota:** Si te pide confirmar (fingerprint), escribe `yes`

---

### **3️⃣ Clonar y Configurar (Copiar y Pegar)**

Una vez conectado por SSH, ejecuta estos comandos:

```bash
# ========================================
# PASO 1: Clonar proyecto
# ========================================
cd ~
git clone https://github.com/TU-USUARIO/servime.git servime
cd servime

# ========================================
# PASO 2: Instalar dependencias
# ========================================
composer install --no-dev --optimize-autoloader

# ========================================
# PASO 3: Configurar .env
# ========================================
cp .env.example .env
nano .env
```

**IMPORTANTE:** Edita el `.env` con estos valores:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://tu-dominio.com

DB_CONNECTION=mysql
DB_HOST=localhost
DB_DATABASE=u123456_servime          # 👈 Cambiar
DB_USERNAME=u123456_servime_user     # 👈 Cambiar
DB_PASSWORD=tu_password_mysql        # 👈 Cambiar

OPENAI_API_KEY=sk-proj-xxxxx         # 👈 Tu API Key
```

**Guardar:** `Ctrl+X` → `Y` → `Enter`

```bash
# ========================================
# PASO 4: Configurar Laravel
# ========================================
php artisan key:generate
php artisan migrate --force
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache
chmod -R 755 storage bootstrap/cache

# ========================================
# PASO 5: Configurar public_html
# ========================================
cd ~/public_html
rm -rf *
cp -r ~/servime/public/* .
nano index.php
```

**IMPORTANTE:** Edita estas 2 líneas en `index.php`:

```php
// Busca estas líneas (están cerca de la línea 30)

// ANTES:
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';

// CAMBIAR A:
require __DIR__.'/../servime/vendor/autoload.php';
$app = require_once __DIR__.'/../servime/bootstrap/app.php';
```

**Guardar:** `Ctrl+X` → `Y` → `Enter`

```bash
# ========================================
# PASO 6: Verificar .htaccess
# ========================================
nano .htaccess
```

Asegúrate que tenga esto:

```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^ index.php [L]
</IfModule>
```

**Guardar:** `Ctrl+X` → `Y` → `Enter`

---

### **4️⃣ Verificar**

Abre tu navegador:
```
https://tu-dominio.com
```

¡Debería funcionar! 🎉

---

## 🐛 **Si Ves Error 500**

```bash
# Conectar por SSH
ssh u123456@tu-dominio.com

# Ver el log
cd ~/servime
tail -f storage/logs/laravel.log
```

**Problemas comunes:**

1. **"No application encryption key"**
   ```bash
   cd ~/servime
   php artisan key:generate
   ```

2. **"Database connection failed"**
   - Verifica el `.env` tiene los datos correctos de MySQL
   - Verifica que la base de datos existe en hPanel

3. **"Permission denied"**
   ```bash
   chmod -R 755 ~/servime/storage ~/servime/bootstrap/cache
   ```

4. **"File not found" al cargar CSS/JS**
   ```bash
   cd ~/public_html
   ls -la
   # Debe mostrar: css/, js/, index.php, .htaccess
   ```

---

## 🔄 **Actualizar después (Despliegues Futuros)**

Cuando hagas cambios en tu código:

```bash
# Conectar por SSH
ssh u123456@tu-dominio.com

# Ejecutar el script de deploy
cd ~/servime
bash deploy-hostinger.sh
```

**O manualmente:**

```bash
cd ~/servime
php artisan down
git pull origin main
composer install --no-dev
php artisan migrate --force
php artisan cache:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
cp -r public/* ~/public_html/
php artisan up
```

---

## ✅ **Checklist Rápido**

Antes de empezar, asegúrate de tener:

- [ ] Hosting Hostinger Premium activo
- [ ] Dominio configurado (o usar temporal de Hostinger)
- [ ] Base de datos MySQL creada en hPanel
- [ ] API Key de OpenAI
- [ ] Acceso SSH habilitado
- [ ] Proyecto en GitHub/GitLab (público o privado)

---

## 📞 **Ayuda**

**Si algo falla:**
1. Revisa los logs: `tail -f ~/servime/storage/logs/laravel.log`
2. Contacta soporte de Hostinger (chat 24/7)
3. Verifica la documentación completa: `DEPLOY_HOSTINGER.md`

---

## 🎯 **Comandos Útiles**

```bash
# Ver logs en tiempo real
tail -f ~/servime/storage/logs/laravel.log

# Ver versión de PHP
php -v

# Ver extensiones instaladas
php -m

# Limpiar todos los cachés
cd ~/servime
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Recrear storage link
php artisan storage:link

# Ver estado de la base de datos
php artisan migrate:status

# Verificar permisos
ls -la ~/servime/storage

# Ver espacio disponible
df -h

# Ver uso de archivos (inodos)
df -i
```

---

## 🚀 **¡Listo!**

Con estos pasos tu proyecto Servime estará en producción en ~5 minutos.

**URL de tu aplicación:**
```
https://tu-dominio.com
```

¡Éxito con tu deploy! 🎉

