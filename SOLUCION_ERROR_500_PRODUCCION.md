# 🔧 Solución Error 500 en Producción

## Problemas identificados:

1. ❌ **GEMINI_API_KEY no se lee** - Aunque está en .env, la caché de configuración no está actualizada
2. ❌ **Falta el paquete `smalot/pdfparser`** - No está instalado en producción

## ✅ Solución Completa:

### Paso 1: Conectarse al servidor

```bash
ssh u560898880@br-asc-web803.main-hosting.eu
# O si no funciona:
ssh -p 65002 u560898880@45.152.46.147
```

### Paso 2: Instalar el paquete faltante

```bash
cd ~/servime
composer require smalot/pdfparser --no-dev --optimize-autoloader
```

### Paso 3: Limpiar y regenerar cachés (IMPORTANTE)

```bash
# Limpiar todas las cachés
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# Regenerar caché de configuración (esto lee el .env actualizado)
php artisan config:cache
```

### Paso 4: Verificar que funciona

```bash
# Verificar que el paquete está instalado
composer show smalot/pdfparser

# Verificar que la clave se lee correctamente
php artisan tinker
>>> config('services.gemini.api_key')
# Debe mostrar: "AIzaSyBat2jsFDQRYiSuHowMPEPt9aUgayECH9Y"
>>> exit
```

## 📋 Comandos Completos (Copiar y Pegar):

```bash
# 1. Conectarse
ssh u560898880@br-asc-web803.main-hosting.eu

# 2. Ir al proyecto
cd ~/servime

# 3. Instalar paquete faltante
composer require smalot/pdfparser --optimize-autoloader

# 4. Limpiar y regenerar cachés (CRÍTICO)
php artisan config:clear
php artisan cache:clear
php artisan config:cache

# 5. Verificar
composer show smalot/pdfparser
php artisan tinker
>>> config('services.gemini.api_key')
>>> exit
```

## ⚠️ IMPORTANTE:

El problema de la API key es que Laravel usa `config:cache` en producción, que guarda la configuración en un archivo cache. Si cambias el `.env`, **DEBES** ejecutar `php artisan config:clear` y luego `php artisan config:cache` para que los cambios surtan efecto.

## ✅ Después de estos pasos:

1. El error 500 debería desaparecer
2. Gemini debería poder procesar PDFs
3. El procesamiento de menús debería funcionar

