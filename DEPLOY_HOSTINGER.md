# 🚀 Guía de Deploy en Hostinger - Servime

## 📋 **PREREQUISITOS**

Antes de comenzar, asegúrate de tener:
- ✅ Cuenta de Hostinger Premium activa
- ✅ Acceso al hPanel (panel de control)
- ✅ Dominio configurado (opcional, puedes usar subdominio de Hostinger)
- ✅ Repositorio Git (GitHub/GitLab/Bitbucket)

---

## 🔧 **PARTE 1: CONFIGURACIÓN EN HOSTINGER**

### **1.1 Acceder al hPanel**

1. Ingresa a https://hpanel.hostinger.com
2. Selecciona tu hosting Premium
3. Ve a la sección **"Files"** → **"File Manager"**

### **1.2 Configurar PHP**

1. En hPanel → **"Advanced"** → **"PHP Configuration"**
2. Selecciona **PHP 8.1** o superior
3. Habilita estas extensiones (si no están):
   ```
   ✅ pdo
   ✅ nd_pdo_mysql         (En Hostinger se llama "nd_pdo_mysql", no "pdo_mysql")
   ✅ mbstring
   ✅ openssl
   ✅ tokenizer
   ✅ xml
   ✅ ctype
   ✅ json
   ✅ bcmath
   ✅ fileinfo
   ✅ curl
   ✅ gd                   (Útil para procesamiento de imágenes)
   ✅ zip                  (Necesario para Composer)
   ```

### **1.3 Crear Base de Datos MySQL**

1. En hPanel → **"Databases"** → **"MySQL Databases"**
2. Clic en **"Create New Database"**
3. Guarda estos datos (los necesitarás):
   ```
   Database Name: u123456_servime
   Database User: u123456_servime_user
   Database Password: [tu contraseña segura]
   Database Host: localhost
   ```

---

## 📁 **PARTE 2: ESTRUCTURA DE ARCHIVOS EN HOSTINGER**

### **2.1 Estructura Correcta**

Laravel requiere una estructura especial en hosting compartido:

```
/home/u123456/
├── public_html/          ← Aquí va el contenido de /public
│   ├── .htaccess
│   ├── index.php
│   ├── css/
│   ├── js/
│   └── storage/         (symlink)
└── servime/             ← Aquí va TODO el proyecto Laravel
    ├── app/
    ├── bootstrap/
    ├── config/
    ├── database/
    ├── public/          ← Este contenido se mueve a public_html
    ├── resources/
    ├── routes/
    ├── storage/
    ├── .env
    ├── artisan
    ├── composer.json
    └── ...
```

---

## 🔐 **PARTE 3: SUBIR EL PROYECTO**

### **Opción A: Via SSH (RECOMENDADO) 🚀**

#### **3.1 Obtener acceso SSH**

1. hPanel → **"Advanced"** → **"SSH Access"**
2. Habilita el acceso SSH
3. Copia los datos:
   ```
   Host: tu-dominio.com (o ssh.hostinger.com)
   Port: 22 (o el puerto que te den)
   Username: u123456
   Password: [tu contraseña de hPanel]
   ```

#### **3.2 Conectarte por SSH**

Desde tu terminal (PowerShell):

```powershell
# Conectar
ssh u123456@tu-dominio.com

# Si te pide confirmar fingerprint, escribe: yes
```

#### **3.3 Clonar el repositorio**

```bash
# Ir a tu home
cd ~

# Clonar el proyecto
git clone https://github.com/Leohayequipo/servime.git servime

# Entrar al proyecto
cd servime

# Instalar dependencias de Composer
composer install --no-dev --optimize-autoloader

# Copiar el archivo .env
cp .env.example .env

# Editar el .env (usar nano o vim)
nano .env
```

#### **3.4 Configurar el .env**

Edita estos valores en el `.env`:

```env
APP_NAME=Servime
APP_ENV=production
APP_KEY=     # Lo generamos después
APP_DEBUG=false
APP_URL=https://tu-dominio.com

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=u123456_servime
DB_USERNAME=u123456_servime_user
DB_PASSWORD=tu_password_de_mysql

OPENAI_API_KEY=tu_clave_de_openai

MAIL_MAILER=smtp
# ... resto de configuración de mail
```

Guardar: `Ctrl+X` → `Y` → `Enter`

#### **3.5 Generar la APP_KEY**

```bash
php artisan key:generate
```

#### **3.6 Ejecutar migraciones**

```bash
php artisan migrate --force
```

#### **3.7 Crear symlink de storage**

```bash
php artisan storage:link
```

#### **3.8 Optimizar Laravel**

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

#### **3.9 Configurar permisos**

```bash
chmod -R 755 storage bootstrap/cache
```

#### **3.10 Mover public a public_html**

```bash
# Limpiar public_html (si tiene algo)
cd ~/public_html
rm -rf *

# Copiar contenido de public
cp -r ~/servime/public/* ~/public_html/

# Editar index.php
nano ~/public_html/index.php
```

Edita estas líneas en `index.php`:

```php
// Cambiar de:
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';

// A:
require __DIR__.'/../servime/vendor/autoload.php';
$app = require_once __DIR__.'/../servime/bootstrap/app.php';
```

#### **3.11 Crear/Editar .htaccess**

```bash
nano ~/public_html/.htaccess
```

Contenido:

```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^ index.php [L]
</IfModule>
```

---

### **Opción B: Via FTP (Alternativa) 📦**

#### **3.1 Obtener credenciales FTP**

1. hPanel → **"Files"** → **"FTP Accounts"**
2. Usa las credenciales del usuario principal

#### **3.2 Usar FileZilla**

```
Host: ftp.tu-dominio.com
Username: u123456
Password: [tu contraseña]
Port: 21
```

#### **3.3 Subir archivos**

1. **Primero:** Sube TODO el proyecto a `/servime/`
2. **Después:** Copia el contenido de `/servime/public/` a `/public_html/`
3. Edita `/public_html/index.php` (igual que SSH)

#### **3.4 Ejecutar comandos (via File Manager)**

Como no tienes terminal, usa el **Terminal Web** de hPanel:
1. hPanel → **"Advanced"** → **"Web Terminal"**
2. Ejecuta los comandos de los pasos 3.4 al 3.9

---

## 🗄️ **PARTE 4: IMPORTAR BASE DE DATOS (Opcional)**

Si tienes datos existentes:

### **4.1 Exportar desde tu local**

```powershell
# En tu PC
php artisan db:export servime_backup.sql
# O usar phpMyAdmin
```

### **4.2 Importar en Hostinger**

1. hPanel → **"Databases"** → **"phpMyAdmin"**
2. Selecciona tu base de datos
3. Tab **"Import"**
4. Selecciona el archivo `.sql`
5. Clic en **"Go"**

---

## ✅ **PARTE 5: VERIFICACIÓN**

### **5.1 Probar el sitio**

```
https://tu-dominio.com
```

Si ves errores:

### **5.2 Revisar logs**

```bash
# Via SSH
tail -f ~/servime/storage/logs/laravel.log

# O descargar via FTP
/servime/storage/logs/laravel.log
```

### **5.3 Problemas comunes**

**Error 500:**
```bash
# Verificar permisos
chmod -R 755 ~/servime/storage ~/servime/bootstrap/cache

# Limpiar cachés
php artisan cache:clear
php artisan config:clear
```

**No encuentra archivos:**
```bash
# Verificar index.php
cat ~/public_html/index.php
# Debe apuntar a ../servime/...
```

**Imágenes no se ven:**
```bash
# Recrear symlink
php artisan storage:link

# Verificar permisos
chmod -R 755 ~/servime/storage/app/public
```

---

## 🔄 **PARTE 6: FUTURAS ACTUALIZACIONES**

### **6.1 Via SSH (Rápido)**

```bash
# Conectar
ssh u123456@tu-dominio.com

# Ir al proyecto
cd ~/servime

# Modo mantenimiento
php artisan down

# Actualizar código
git pull origin main

# Actualizar dependencias
composer install --no-dev

# Migraciones
php artisan migrate --force

# Limpiar cachés
php artisan cache:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Copiar cambios de public (si hubo)
cp -r public/* ~/public_html/

# Salir de mantenimiento
php artisan up
```

### **6.2 Automatizar con GitHub Actions**

Ver archivo `.github/workflows/deploy.yml` (si decides usarlo)

---

## 🐛 **DEBUGGING**

### **Ver errores de Laravel:**

```bash
# Activar debug temporalmente
nano ~/servime/.env
# Cambiar: APP_DEBUG=true

# Ver logs en tiempo real
tail -f ~/servime/storage/logs/laravel.log
```

### **Ver errores de PHP:**

```bash
# Ver error log de PHP
tail -f ~/logs/error_log
```

---

## 🔐 **SEGURIDAD**

### **Checklist de Seguridad:**

- [ ] `APP_DEBUG=false` en producción
- [ ] `APP_ENV=production`
- [ ] `.env` fuera de `public_html`
- [ ] SSL/HTTPS activado (Hostinger lo da gratis)
- [ ] Permisos correctos (755 para storage)
- [ ] `.git` fuera de `public_html`
- [ ] Claves API en `.env`, no en código
- [ ] Firewall de Hostinger activado

---

## 📞 **SOPORTE**

### **Si algo falla:**

1. **Revisar logs:** `storage/logs/laravel.log`
2. **Soporte de Hostinger:** Chat 24/7 en hPanel
3. **Verificar versión PHP:** `php -v` (debe ser 8.1+)
4. **Verificar extensiones:** `php -m`

---

## ✅ **CHECKLIST FINAL**

- [ ] PHP 8.1+ configurado
- [ ] Base de datos creada e importada
- [ ] Proyecto clonado en `~/servime/`
- [ ] `.env` configurado correctamente
- [ ] `APP_KEY` generada
- [ ] Migraciones ejecutadas
- [ ] Storage link creado
- [ ] Cachés optimizados
- [ ] `public/` copiado a `public_html/`
- [ ] `index.php` editado correctamente
- [ ] `.htaccess` configurado
- [ ] Permisos correctos (755)
- [ ] Sitio accesible en el navegador
- [ ] SSL/HTTPS funcionando
- [ ] Imágenes cargando correctamente
- [ ] Chatbot funcionando

---

## 🎉 **¡LISTO!**

Tu proyecto Servime debería estar funcionando en:
**https://tu-dominio.com**

Si necesitas ayuda adicional, contacta al soporte de Hostinger o revisa los logs.

---

**Fecha de creación:** Octubre 2025  
**Versión:** 1.0  
**Proyecto:** Servime - Sistema de menús con IA

