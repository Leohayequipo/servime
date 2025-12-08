#!/bin/bash

# Script para copiar imágenes de menu-items a public_html
# Ejecutar en el servidor: bash copiar-imagenes-public-html.sh

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "📸 Copiando imágenes a public_html"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""

cd ~/servime || {
    echo "❌ No se encontró ~/servime"
    exit 1
}

# Directorio base de storage
STORAGE_BASE="storage/app/public/comercios"
PUBLIC_HTML_BASE="/home/u560898880/domains/servime.net/public_html/storage/comercios"

# Verificar que existe el directorio de storage
if [ ! -d "$STORAGE_BASE" ]; then
    echo "❌ No se encontró el directorio $STORAGE_BASE"
    exit 1
fi

# Contador de archivos copiados
COPIADOS=0
ERRORES=0

# Buscar todos los directorios de comercios
for comercio_dir in "$STORAGE_BASE"/*; do
    if [ -d "$comercio_dir" ]; then
        comercio_id=$(basename "$comercio_dir")
        menu_items_dir="$comercio_dir/menu-items"
        
        if [ -d "$menu_items_dir" ]; then
            echo "📁 Procesando comercio ID: $comercio_id"
            
            # Crear directorio en public_html si no existe
            public_html_dir="$PUBLIC_HTML_BASE/$comercio_id/menu-items"
            if [ ! -d "$public_html_dir" ]; then
                mkdir -p "$public_html_dir"
                chmod 775 "$public_html_dir"
                echo "   ✅ Creado directorio: $public_html_dir"
            fi
            
            # Copiar cada imagen
            for imagen in "$menu_items_dir"/*; do
                if [ -f "$imagen" ]; then
                    filename=$(basename "$imagen")
                    target="$public_html_dir/$filename"
                    
                    # Solo copiar si no existe o si es más reciente
                    if [ ! -f "$target" ] || [ "$imagen" -nt "$target" ]; then
                        if cp "$imagen" "$target"; then
                            chmod 664 "$target"
                            echo "   ✅ Copiado: $filename"
                            COPIADOS=$((COPIADOS + 1))
                        else
                            echo "   ❌ Error copiando: $filename"
                            ERRORES=$((ERRORES + 1))
                        fi
                    else
                        echo "   ⏭️  Ya existe (más reciente): $filename"
                    fi
                fi
            done
        fi
    fi
done

echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "✅ Proceso completado"
echo "   📸 Archivos copiados: $COPIADOS"
if [ $ERRORES -gt 0 ]; then
    echo "   ❌ Errores: $ERRORES"
fi
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

