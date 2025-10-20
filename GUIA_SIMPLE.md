# 🚀 GUÍA ULTRA SIMPLE - Deploy en Hostinger

## 📋 **ANTES DE EMPEZAR**

### ✅ **Prepara estos datos (los necesitarás):**

```
1. Usuario SSH: u________
2. Dominio: ___________________.com
3. Nombre BD: u________servime
4. Usuario BD: u________servime_user
5. Password BD: __________________
6. API OpenAI: sk-proj-__________________
```

---

## 🎯 **PASO A PASO (Copia y Pega)**

### **PASO 1: Conectar a Hostinger**

Abre PowerShell y ejecuta (reemplaza con TU usuario):

```powershell
ssh u123456@tu-dominio.com
```

Te pedirá la contraseña → Escríbela (no se ve, es normal) → Enter

---

### **PASO 2: Descargar tu Proyecto**

Copia y pega estos comandos (UNO POR UNO):

```bash
cd ~
git clone https://github.com/TU-USUARIO/servime.git servime
cd servime
composer install --no-dev --optimize-autoloader
```

**¿Qué hace esto?**
- Descarga tu código de GitHub
- Instala las dependencias de Laravel

---

### **PASO 3: Configurar la Base de Datos**

```bash
cp .env.example .env
nano .env
```

Se abrirá un editor. **EDITA estas líneas:**

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://TU-DOMINIO.com

DB_DATABASE=u123456_servime              ← Tu BD
DB_USERNAME=u123456_servime_user         ← Tu usuario BD
DB_PASSWORD=tu_password_aqui             ← Tu password BD

OPENAI_API_KEY=sk-proj-tu_key_aqui      ← Tu API Key
```

**Guardar:** `Ctrl+X` → `Y` → `Enter`

---

### **PASO 4: Preparar Laravel**

Copia y pega estos comandos (UNO POR UNO):

```bash
php artisan key:generate
php artisan migrate --force
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache
chmod -R 755 storage bootstrap/cache
```

**¿Qué hace esto?**
- Genera la clave de seguridad
- Crea las tablas en la base de datos
- Configura los permisos
- Optimiza Laravel

---

### **PASO 5: Copiar los Archivos Públicos**

```bash
cd ~/public_html
rm -rf *
cp -r ~/servime/public/* .
nano index.php
```

Se abrirá el archivo `index.php`. **BUSCA estas 2 líneas** (están cerca de la línea 30):

```php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
```

**CÁMBIALAS A:**

```php
require __DIR__.'/../servime/vendor/autoload.php';
$app = require_once __DIR__.'/../servime/bootstrap/app.php';
```

**Guardar:** `Ctrl+X` → `Y` → `Enter`

---

### **PASO 6: ¡PROBAR!**

Abre tu navegador:

```
https://tu-dominio.com
```

**¡Debería funcionar!** 🎉

---

## 🐛 **Si Ves Errores**

### **Error 500:**

```bash
cd ~/servime
tail -f storage/logs/laravel.log
```

Mira el log y busca el error. Luego:

```bash
php artisan cache:clear
chmod -R 755 storage bootstrap/cache
```

### **"No application encryption key":**

```bash
cd ~/servime
php artisan key:generate
```

### **"Database connection failed":**

Verifica el `.env`:

```bash
cd ~/servime
nano .env
```

Revisa que `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` sean correctos.

---

## 📊 **RESUMEN VISUAL**

```
┌─────────────────────────────────────────┐
│  1. Conectar por SSH                    │  ← PowerShell
│     ssh u123456@tu-dominio.com          │
└─────────────────────────────────────────┘
                  ▼
┌─────────────────────────────────────────┐
│  2. Clonar proyecto                     │  ← Git + Composer
│     git clone ...                       │
│     composer install                    │
└─────────────────────────────────────────┘
                  ▼
┌─────────────────────────────────────────┐
│  3. Configurar .env                     │  ← Editar archivo
│     nano .env                           │
└─────────────────────────────────────────┘
                  ▼
┌─────────────────────────────────────────┐
│  4. Preparar Laravel                    │  ← Comandos artisan
│     php artisan key:generate            │
│     php artisan migrate                 │
└─────────────────────────────────────────┘
                  ▼
┌─────────────────────────────────────────┐
│  5. Configurar public_html              │  ← Copiar y editar
│     cp -r ~/servime/public/* .          │
│     nano index.php                      │
└─────────────────────────────────────────┘
                  ▼
┌─────────────────────────────────────────┐
│  6. ¡LISTO!                            │  ← Visitar tu sitio
│     https://tu-dominio.com              │
└─────────────────────────────────────────┘
```

---

## 🎯 **LO MÁS IMPORTANTE:**

### **Solo necesitas editar 2 archivos:**

1. **`.env`** (Paso 3)
   - Datos de la base de datos
   - API Key de OpenAI

2. **`index.php`** en public_html (Paso 5)
   - Cambiar las 2 líneas que dicen `__DIR__.'/../` por `__DIR__.'/../servime/`

**¡Eso es todo!** El resto son comandos que copias y pegas.

---

## 📝 **CHECKLIST**

Marca lo que ya hiciste:

```
[ ] 1. Conectado por SSH
[ ] 2. Clonado el proyecto (git clone)
[ ] 3. Instalado dependencias (composer install)
[ ] 4. Editado el .env con datos correctos
[ ] 5. Generado APP_KEY (php artisan key:generate)
[ ] 6. Ejecutado migraciones (php artisan migrate)
[ ] 7. Optimizado Laravel (config:cache, route:cache)
[ ] 8. Copiado public a public_html
[ ] 9. Editado index.php en public_html
[ ] 10. Probado el sitio en el navegador
```

---

## 🆘 **¿Necesitas Más Ayuda?**

- **Guía completa:** `DEPLOY_HOSTINGER.md`
- **Guía rápida:** `QUICK_START.md`
- **Comandos útiles:** `hostinger-config.txt`
- **Soporte Hostinger:** Chat 24/7 en hPanel

---

**¡Éxito con tu deploy!** 🚀

---

**Tiempo estimado:** 10-15 minutos  
**Dificultad:** Fácil (solo copiar y pegar)  
**Proyecto:** Servime - Sistema de Menús con IA


