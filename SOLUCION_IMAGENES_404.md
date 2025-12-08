# 🔧 Solución Error 404 en Imágenes

## Problema:
Las imágenes se guardan en `storage/app/public/comercios/{id}/menu-items/` pero no se copian a `public_html/storage/comercios/{id}/menu-items/`, causando error 404.

## ✅ Solución:

### Paso 1: Copiar imágenes existentes

```bash
# Conectarse al servidor
ssh u560898880@br-asc-web803.main-hosting.eu

# Subir el script
# (Desde tu PC local)
scp copiar-imagenes-public-html.sh u560898880@br-asc-web803.main-hosting.eu:~/servime/

# En el servidor, ejecutar:
cd ~/servime
chmod +x copiar-imagenes-public-html.sh
bash copiar-imagenes-public-html.sh
```

### Paso 2: Verificar permisos

```bash
# Verificar permisos de las carpetas
ls -la ~/domains/servime.net/public_html/storage/comercios/

# Ajustar permisos si es necesario
chmod -R 775 ~/domains/servime.net/public_html/storage/comercios/
find ~/domains/servime.net/public_html/storage/comercios/ -type f -exec chmod 664 {} \;
```

### Paso 3: Verificar que funciona

Abre en el navegador:
- `https://servime.net/storage/comercios/3/menu-items/eok59Wlk83cY5t70oVJojBnrmKwmenMVDfX5QJ5a.jpg`

Debería mostrar la imagen.

## 🔍 Verificar manualmente:

```bash
# Ver qué imágenes hay en storage
ls -la ~/servime/storage/app/public/comercios/3/menu-items/

# Ver qué imágenes hay en public_html
ls -la ~/domains/servime.net/public_html/storage/comercios/3/menu-items/

# Comparar (deben ser las mismas)
```

## ⚠️ Nota:

He corregido el código para que las nuevas imágenes se copien automáticamente. El script solo es necesario para copiar las imágenes que ya existían antes de la corrección.

