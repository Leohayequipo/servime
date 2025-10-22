# 🚨 PROBLEMA CRÍTICO - RUTAS EN HOSTINGER
## ⚠️ ESTO CAUSÓ 5 HORAS DE CAÍDA DEL SISTEMA

---

## 🔥 **EL PROBLEMA QUE ROMPIÓ TODO:**

### **❌ Rutas INCORRECTAS en `index.php`:**
```php
// ESTO ESTABA MAL:
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
```

### **✅ Rutas CORRECTAS:**
```php
// ESTO ES LO CORRECTO:
require __DIR__.'/../../../servime/vendor/autoload.php';
$app = require_once __DIR__.'/../../../servime/bootstrap/app.php';
```

---

## 🏗️ **¿POR QUÉ PASÓ ESTO?**

### **Estructura del hosting de Hostinger:**
```
/home/u560898880/
 ├── servime/                    ← Proyecto Laravel completo
 │    ├── app/
 │    ├── vendor/               ← Aquí están las dependencias
 │    ├── bootstrap/            ← Aquí está bootstrap/app.php
 │    └── public/               ← Carpeta pública de Laravel
 └── domains/
      └── servime.net/
           └── public_html/     ← Carpeta pública del dominio
                └── index.php   ← Archivo que carga Laravel
```

### **El problema:**
- Laravel normalmente usa `/public` como raíz
- Pero en Hostinger la raíz es `/domains/servime.net/public_html/`
- Desde `public_html` hay que subir **3 niveles** para llegar a `servime/`

---

## 🎯 **SOLUCIÓN DEFINITIVA:**

### **1. Ubicación del archivo:**
```
~/domains/servime.net/public_html/index.php
```

### **2. Contenido CORRECTO del index.php:**
```php
<?php

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

/*
|--------------------------------------------------------------------------
| Check If The Application Is Under Maintenance
|--------------------------------------------------------------------------
|
| If the application is in maintenance / demo mode via the "down" command
| we will load this file so that any pre-rendered content can be shown
| instead of starting the framework, which could cause an exception.
|
*/

if (file_exists($maintenance = __DIR__.'/../../../servime/storage/framework/maintenance.php')) {
    require $maintenance;
}

/*
|--------------------------------------------------------------------------
| Register The Auto Loader
|--------------------------------------------------------------------------
|
| Composer provides a convenient, automatically generated class loader for
| this application. We just need to utilize it! We'll simply require it
| into the script here so we don't need to manually load our classes.
|
*/

require __DIR__.'/../../../servime/vendor/autoload.php';

/*
|--------------------------------------------------------------------------
| Run The Application
|--------------------------------------------------------------------------
|
| Once we have the application, we can handle the incoming request using
| the application's HTTP kernel. Then, we will send the response back
| to this client's browser, allowing them to enjoy our application.
|
*/

$app = require_once __DIR__.'/../../../servime/bootstrap/app.php';

$kernel = $app->make(Kernel::class);

$response = $kernel->handle(
    $request = Request::capture()
)->send();

$kernel->terminate($request, $response);
```

---

## 🚨 **SEÑALES DE QUE ESTÁ MAL:**

### **Síntomas:**
- ❌ Error 500 en el sitio web
- ❌ Página en blanco
- ❌ "This script cannot be run from the CLI" (cuando ejecutas `php index.php`)
- ❌ Logs de Laravel no aparecen errores específicos

### **Verificación rápida:**
```bash
# En el servidor SSH:
php ~/domains/servime.net/public_html/index.php

# Si está MAL, verás:
# Fatal error: require(): Failed opening required '/home/u560898880/domains/servime.net/public_html/../vendor/autoload.php'

# Si está BIEN, verás:
# This script cannot be run from the CLI.
```

---

## 🔧 **COMANDOS DE EMERGENCIA:**

### **Si se rompe otra vez:**
```bash
# 1. Conectar por SSH
ssh u560898880@br-asc-web803.main-hosting.eu

# 2. Ir a la carpeta pública
cd ~/domains/servime.net/public_html/

# 3. Editar index.php
nano index.php

# 4. Buscar estas líneas:
# require __DIR__.'/../vendor/autoload.php';
# $app = require_once __DIR__.'/../bootstrap/app.php';

# 5. Cambiar por:
# require __DIR__.'/../../../servime/vendor/autoload.php';
# $app = require_once __DIR__.'/../../../servime/bootstrap/app.php';

# 6. Guardar: Ctrl+X → Y → Enter

# 7. Verificar:
php index.php
# Debe mostrar: "This script cannot be run from the CLI."
```

---

## 📝 **SCRIPT DE REPARACIÓN AUTOMÁTICA:**

```bash
#!/bin/bash
# Script de emergencia para reparar rutas

echo "🚨 Reparando rutas de emergencia..."

cd ~/domains/servime.net/public_html/

# Backup del index.php actual
cp index.php index.php.backup.$(date +%Y%m%d_%H%M%S)

# Reparar rutas
sed -i "s|require __DIR__.'/../vendor/autoload.php';|require __DIR__.'/../../../servime/vendor/autoload.php';|g" index.php
sed -i "s|\$app = require_once __DIR__.'/../bootstrap/app.php';|\$app = require_once __DIR__.'/../../../servime/bootstrap/app.php';|g" index.php

echo "✅ Rutas reparadas"
echo "🔍 Verificando..."

# Verificar
if php index.php 2>&1 | grep -q "This script cannot be run from the CLI"; then
    echo "✅ ¡REPARADO! Laravel carga correctamente"
else
    echo "❌ Aún hay problemas, revisar manualmente"
fi
```

---

## 🎯 **LECCIONES APRENDIDAS:**

### **1. NO es problema de Hostinger**
- Es la estructura específica de hosting compartido
- Laravel está en una carpeta, el dominio en otra

### **2. Siempre verificar las rutas**
- Después de cualquier cambio en el servidor
- Después de actualizaciones
- Después de restauraciones

### **3. Tener backup del index.php correcto**
- Guardar una copia del index.php que funciona
- Para restaurar rápidamente si se rompe

### **4. Documentar TODO**
- Este archivo debe estar siempre disponible
- Para cualquier desarrollador que trabaje en el proyecto

---

## 🚀 **PREVENCIÓN FUTURA:**

### **1. Script de verificación diaria:**
```bash
#!/bin/bash
# Verificar que las rutas estén correctas

cd ~/domains/servime.net/public_html/

if php index.php 2>&1 | grep -q "This script cannot be run from the CLI"; then
    echo "✅ $(date): Laravel funcionando correctamente"
else
    echo "🚨 $(date): PROBLEMA DETECTADO - Ejecutar reparación"
    # Aquí podrías enviar un email de alerta
fi
```

### **2. Monitoreo automático:**
- Configurar alertas si el sitio no responde
- Verificar las rutas después de cada deploy
- Tener siempre un plan de contingencia

---

## 📞 **DATOS DE EMERGENCIA:**

**Servidor:** `br-asc-web803.main-hosting.eu`  
**Usuario:** `u560898880`  
**Archivo crítico:** `~/domains/servime.net/public_html/index.php`  
**Proyecto Laravel:** `~/servime/`  

**Comando de verificación rápida:**
```bash
php ~/domains/servime.net/public_html/index.php
```

---

## ⚠️ **RECORDATORIO CRÍTICO:**

**ESTE PROBLEMA CAUSÓ 5 HORAS DE CAÍDA**  
**SIEMPRE VERIFICAR LAS RUTAS DESPUÉS DE CUALQUIER CAMBIO**  
**TENER ESTE ARCHIVO SIEMPRE A MANO**  

---

**💡 Guarda este archivo en tu PC y en el servidor para emergencias futuras.**
