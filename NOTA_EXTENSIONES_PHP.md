# ⚠️ IMPORTANTE: Extensiones PHP en Hostinger

## 🔧 **Nombre Correcto de las Extensiones**

### **En Hostinger, la extensión para MySQL se llama:**

```
✅ nd_pdo_mysql
```

**NO** se llama `pdo_mysql` (ese es el nombre genérico/antiguo)

---

## 📋 **Lista Completa de Extensiones para Servime**

Cuando configures PHP en Hostinger (**hPanel → Advanced → PHP Configuration**), activa estas:

| Extensión | ¿Requerida? | Propósito |
|-----------|-------------|-----------|
| **pdo** | ✅ Obligatoria | Base para conexiones a bases de datos |
| **nd_pdo_mysql** | ✅ Obligatoria | Conexión a MySQL (Native Driver) |
| **mbstring** | ✅ Obligatoria | Manejo de strings multibyte (UTF-8) |
| **openssl** | ✅ Obligatoria | Encriptación y HTTPS |
| **tokenizer** | ✅ Obligatoria | Parser de PHP para Laravel |
| **xml** | ✅ Obligatoria | Procesamiento XML |
| **ctype** | ✅ Obligatoria | Validación de tipos de caracteres |
| **json** | ✅ Obligatoria | Manejo de JSON |
| **bcmath** | ✅ Obligatoria | Matemática de precisión arbitraria |
| **fileinfo** | ✅ Obligatoria | Detección de tipos MIME |
| **curl** | ✅ Obligatoria | Peticiones HTTP (para OpenAI API) |
| **gd** | ⚠️ Recomendada | Procesamiento de imágenes |
| **zip** | ⚠️ Recomendada | Descompresión (Composer) |
| **imagick** | ❌ Opcional | Procesamiento avanzado de imágenes (no necesario) |

---

## 💡 **¿Por qué "nd_pdo_mysql" y no "pdo_mysql"?**

### **"nd" = Native Driver (Driver Nativo)**

**Ventajas:**
- ✅ Más rápido (optimizado para PHP)
- ✅ Usa menos memoria
- ✅ Mejor rendimiento
- ✅ Soporta características modernas de MySQL
- ✅ Es la versión recomendada por MySQL

**Diferencia técnica:**
```
pdo_mysql      → Driver antiguo basado en libmysqlclient
nd_pdo_mysql   → Driver nativo de MySQL (mysqlnd)
```

**Para Laravel:** Ambas funcionan, pero `nd_pdo_mysql` es mejor y más moderna.

---

## 🔍 **Cómo Verificar que Están Activas**

Una vez que las actives en hPanel, puedes verificar por SSH:

```bash
# Ver todas las extensiones instaladas
php -m

# Buscar extensiones específicas
php -m | grep pdo

# Debería mostrar:
# pdo
# pdo_mysql  (o nd_pdo_mysql)
```

**Nota:** Puede aparecer como `pdo_mysql` en la lista de `php -m`, pero internamente está usando el Native Driver si lo activaste en hPanel.

---

## ⚙️ **Configuración Recomendada de PHP**

Además de las extensiones, configura estos valores en **hPanel → PHP Configuration → Options**:

```ini
upload_max_filesize = 20M      # Para subir PDFs de menús
post_max_size = 25M            # Debe ser mayor que upload_max_filesize
max_execution_time = 300       # 5 minutos (para procesamiento de PDFs)
memory_limit = 256M            # Memoria suficiente para Laravel
max_input_vars = 3000          # Para formularios grandes
```

---

## 🆘 **Solución de Problemas**

### **Error: "could not find driver"**

**Causa:** La extensión `nd_pdo_mysql` no está activa.

**Solución:**
1. Ve a hPanel → Advanced → PHP Configuration
2. Busca `nd_pdo_mysql` (o `pdo_mysql`)
3. Actívala (toggle ON)
4. Guarda cambios
5. Espera 1-2 minutos para que aplique

### **Error: "Class 'DOMDocument' not found"**

**Causa:** La extensión `xml` no está activa.

**Solución:**
1. Activa `xml` en PHP Configuration
2. Guarda cambios

### **Error: "Call to undefined function imagecreatetruecolor()"**

**Causa:** La extensión `gd` no está activa.

**Solución:**
1. Activa `gd` en PHP Configuration
2. Guarda cambios

---

## ✅ **Checklist de Extensiones**

Marca las que ya activaste en Hostinger:

```
[ ] pdo
[ ] nd_pdo_mysql       ← La más importante
[ ] mbstring
[ ] openssl
[ ] tokenizer
[ ] xml
[ ] ctype
[ ] json
[ ] bcmath
[ ] fileinfo
[ ] curl
[ ] gd
[ ] zip
```

---

## 📝 **Nota Final**

**No te preocupes** si ves nombres ligeramente diferentes en el panel de Hostinger. Lo importante es que:

1. ✅ **PDO** esté activo
2. ✅ **nd_pdo_mysql** (o cualquier variante de PDO MySQL) esté activo
3. ✅ Las demás extensiones de la lista estén activas

Si tienes dudas, el soporte de Hostinger puede confirmar que las extensiones correctas están activas.

---

**Actualizado:** Octubre 2025  
**Proyecto:** Servime - Sistema de Menús con IA

