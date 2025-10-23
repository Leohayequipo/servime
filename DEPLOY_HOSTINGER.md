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
   Database User: u123456_servime
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

### **Opción A: Via SSH (RECOMENDADO) 🚀****

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
APP_NAME=ServiME
APP_ENV=production
APP_KEY=     # Lo generamos después con php artisan key:generate
APP_DEBUG=false
APP_URL=https://servime.net

APP_LOCALE=en
APP_FALLBACK_LOCALE=en
APP_FAKER_LOCALE=en_US

APP_MAINTENANCE_DRIVER=file

BCRYPT_ROUNDS=12

LOG_CHANNEL=stack
LOG_STACK=single
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=error

# Base de datos - ACTUALIZAR CON TUS CREDENCIALES
DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=u560898880_servime
DB_USERNAME=u560898880_servime
DB_PASSWORD=TU_PASSWORD_MYSQL_AQUI

SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_PATH=/
SESSION_DOMAIN=null

BROADCAST_CONNECTION=log
FILESYSTEM_DISK=local
QUEUE_CONNECTION=database

CACHE_STORE=database

# Correo electrónico
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=info.motos.aconcagua@gmail.com
MAIL_PASSWORD=dwthftbibqkqhciz
MAIL_FROM_ADDRESS=info.motos.aconcagua@gmail.com
MAIL_FROM_NAME="${APP_NAME}"
MAIL_ENCRYPTION=tls

# OpenAI API
OPENAI_API_KEY=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
OPENAI_MODEL=gpt-4o-mini

# Gmail (opcional, para funcionalidades adicionales)
GMAIL_USER=info.motos.acon@gmail.com
GMAIL_APP_PASSWORD=IrkuxoyynwrdIhgo
```

**⚠️ IMPORTANTE - Valores a actualizar:**
- `DB_PASSWORD`: Tu contraseña de MySQL de Hostinger
- `DB_DATABASE` y `DB_USERNAME`: Verifica en hPanel → Databases
- `APP_URL`: Tu dominio real (https://servime.net)

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
/////////////////

💣 Qué pasó

Laravel usa la función nativa exec() de PHP para crear el symlink (storage:link),
pero Hostinger (en sus planes compartidos) la tiene deshabilitada por seguridad.
Por eso te tira:

Call to undefined function Illuminate\Filesystem\exec()

💡 Solución rápida

No podés usar php artisan storage:link,
pero podés crear el enlace simbólico manualmente desde la consola:

ln -s /home/u560898880/servime/storage/app/public /home/u560898880/servime/public/storage


Eso hace exactamente lo mismo que el comando de Laravel.
Después verificá con:

ls -l /home/u560898880/servime/public/


Deberías ver algo así:

storage -> ../storage/app/public

///////
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



🧾 Resumen: Cómo hicimos que Laravel funcione en servime.net (hosting compartido)
🧩 1. Estructura del hosting

Tu hosting tenía esta estructura:

/home/u560898880/
 ├── servime/               ← Carpeta del proyecto Laravel completo
 │    ├── app/
 │    ├── vendor/
 │    ├── bootstrap/
 │    └── ...
 └── domains/
      └── servime.net/
           └── public_html/  ← Carpeta pública del dominio


Laravel normalmente usa /public como raíz pública,
pero en tu hosting la raíz pública es /domains/servime.net/public_html/.

⚙️ 2. Movimos el contenido de public a public_html

Borramos el contenido viejo (si lo había) y copiamos los archivos del public de Laravel:

cd ~/domains/servime.net/public_html
rm -rf *
cp -r ~/servime/public/* ./


Así, index.php, favicon.ico, robots.txt, etc. quedaron en public_html/.

🪶 3. Editamos las rutas internas de index.php

Como el proyecto Laravel real está en /home/u560898880/servime/,
ajustamos las rutas de carga de Laravel para que apunten ahí.

Editamos:

nano ~/domains/servime.net/public_html/index.php


Y reemplazamos las líneas:

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';


por estas 👇

require __DIR__.'/../../../servime/vendor/autoload.php';
$app = require_once __DIR__.'/../../../servime/bootstrap/app.php';


💡 (Subimos tres niveles porque public_html está dentro de domains/servime.net/.)

🔒 4. Configuramos permisos y estructura del storage

Creamos la carpeta de almacenamiento público:

mkdir -p ~/domains/servime.net/public_html/storage


Y copiamos los archivos públicos de Laravel allí:

cp -r ~/servime/storage/app/public/* ~/domains/servime.net/public_html/storage/


Luego movimos su contenido (para evitar una carpeta storage/public anidada):

mv ~/domains/servime.net/public_html/storage/public/* ~/domains/servime.net/public_html/storage/
rm -rf ~/domains/servime.net/public_html/storage/public

🚫 5. Evitamos el error exec() deshabilitado

Como el hosting no permite exec() (necesario para php artisan storage:link),
no usamos el comando storage:link y en su lugar copiamos manualmente los archivos del storage a public_html/storage/.

🧰 6. Verificamos desde consola

Probamos Laravel con:

php ~/domains/servime.net/public_html/index.php


Y el resultado esperado fue:

This script cannot be run from the CLI.


✅ Eso significa que Laravel ya carga correctamente.

🌐 7. Probamos en el navegador

Finalmente abrimos:

https://servime.net


Y el sitio funcionó correctamente sin error 500 🎉

💾 En resumen
Paso	Acción	Comando / Archivo clave
1	Confirmar estructura del hosting	ls /home/u560898880/
2	Copiar /public → /public_html	cp -r ~/servime/public/* ~/domains/servime.net/public_html/
3	Editar index.php	Usar ../../../servime/...
4	Crear y llenar storage	mkdir -p + cp -r
5	No usar artisan storage:link	Hosting bloquea exec()
6	Probar en consola	php ~/domains/servime.net/public_html/index.php
7	Abrir en navegador	https://servime.net

## 🤖 Script Automático de Deploy

✨ **¡Ahora disponible!** Usa el script `deploy-hostinger-auto.sh` que automatiza todo el proceso:

```bash
# 1. Subir el script al servidor (desde tu PC)
scp deploy-hostinger-auto.sh u560898880@br-asc-web803.main-hosting.eu:~/servime/

# 2. En el servidor SSH, darle permisos
chmod +x ~/servime/deploy-hostinger-auto.sh

# 3. Ejecutar el deploy automático
bash ~/servime/deploy-hostinger-auto.sh
```

**El script hace automáticamente:**
- ✅ Backup del `public_html` actual
- ✅ Copia archivos de `public/` a `public_html/`
- ✅ Modifica `index.php` con rutas correctas
- ✅ Configura storage público (sin symlinks)
- ✅ Ajusta permisos
- ✅ Instala/actualiza dependencias
- ✅ Limpia y optimiza cachés
- ✅ Verifica que Laravel funcione

**Futuras actualizaciones:** Solo ejecuta el script nuevamente después de `git pull` 🚀

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

