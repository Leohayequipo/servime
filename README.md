# 🍽️ Servime - Sistema de Menús Digital con IA

**Sistema completo de gestión de menús digitales con chatbot inteligente para restaurantes y bares.**

## ✨ Características Principales

- 🤖 **Chatbot con OpenAI** - Respuestas inteligentes sobre el menú
- 📱 **Vista móvil optimizada** - Experiencia perfecta en smartphones
- 🎯 **QR Code automático** - Acceso instantáneo desde códigos QR
- 🧠 **Procesamiento de IA** - Extracción automática de contenido de menús
- 🎨 **Branding personalizado** - Logo y colores del comercio
- 📊 **Panel de administración** - Gestión completa de menús y comercios
- 🔄 **Sistema de activación** - Solo un menú activo por comercio
- 💾 **Guardado inteligente** - Botón flotante para guardar cambios

## 🚀 Funcionalidades Implementadas

### ✅ **FASE 1: Estructura Básica**
- Rutas públicas sin autenticación
- Vista móvil adaptada
- Generación de códigos QR
- Integración con OpenAI

### ✅ **FASE 2: Chatbot Inteligente**
- Respuestas contextuales con IA
- Fallback automático
- Interfaz de chat optimizada
- Procesamiento en tiempo real

### ✅ **FASE 3: Gestión Avanzada de Menús**
- Sistema de activación de menús (uno activo por comercio)
- Indicadores visuales de menú activo
- Botón flotante "Guardar Cambios"
- Mensajes de éxito/error modernos
- Logo por defecto para comercios sin logo
- Campo de nombre para menús
- Integración de "Contenido Extraído" en create/edit

### 🔄 **FASE 4: Análisis y Métricas** (En desarrollo)
- Historial de conversaciones
- Preguntas más frecuentes
- Estadísticas de uso
- Dashboard de métricas

## 🛠️ Requisitos Técnicos

- **PHP** >= 8.2
- **MySQL** / MariaDB
- **Node.js** >= 16
- **Composer**
- **Laravel** 12.x
- **OpenAI API Key**

## ⚙️ Instalación

### 1. Clonar repositorio
```bash
git clone https://github.com/tu-usuario/servime-menu.git
cd servime-menu
```

### 2. Instalar dependencias
```bash
composer install
npm install && npm run dev
```

### 3. Configurar entorno
```bash
cp .env.example .env
php artisan key:generate
```

### 4. Configurar base de datos
```env
DB_DATABASE=servime_menu
DB_USERNAME=root
DB_PASSWORD=
```

### 5. Configurar OpenAI
```env
OPENAI_API_KEY=tu_api_key_aqui
OPENAI_MODEL=gpt-4o-mini
```

### 6. Ejecutar migraciones
```bash
php artisan migrate
php artisan storage:link
```

### 7. Iniciar servidor
```bash
php artisan serve
```

## 📱 Uso del Sistema

### **Para Administradores:**
1. **Crear comercio** - Registrar restaurante/bar con logo personalizado
2. **Subir menús** - PDF o imágenes con nombre personalizado
3. **Activar menús** - Solo un menú activo por comercio
4. **Procesar con IA** - Extracción automática de contenido
5. **Generar QR** - Código para acceso público
6. **Personalizar chatbot** - Colores, FAQ, configuraciones
7. **Ver métricas** - Estadísticas de uso

### **Para Clientes:**
1. **Escanear QR** - Acceso directo al menú
2. **Navegar menús** - Ver platos y precios
3. **Chatear con IA** - Preguntar sobre el menú
4. **Experiencia móvil** - Optimizada para smartphones

## 🔧 Configuración Avanzada

### **Acceso desde móvil:**
```bash
php artisan serve --host=TU_IP_LOCAL --port=8000
```

### **Generar QR para comercio:**
- Ir a panel de administración
- Seleccionar comercio
- Hacer clic en "Generar QR"
- Descargar código QR

### **Gestión de Menús:**
- **Crear menú**: Subir archivo + nombre personalizado
- **Activar menú**: Hacer clic en "⭐ Activar Menú"
- **Editar menú**: Modificar nombre, archivo, personalización
- **Guardar cambios**: Botón flotante azul en esquina inferior derecha

## 📊 Estructura del Proyecto

```
app/
├── Http/Controllers/
│   ├── ComercioController.php
│   ├── MenuController.php
│   ├── PublicMenuController.php
│   └── ChatbotController.php
├── Models/
│   ├── Comercio.php
│   ├── Menu.php (con campos: nombre, activo)
│   └── User.php
└── Services/
    ├── MenuProcessorService.php
    └── ChatbotService.php

resources/views/
├── comercios/
│   ├── index.blade.php (con mensajes modernos)
│   ├── create.blade.php
│   ├── edit.blade.php
│   └── customize.blade.php
├── menus/
│   ├── index.blade.php (con indicadores de activo)
│   ├── create.blade.php (con botón flotante)
│   ├── edit.blade.php (integrado con contenido extraído)
│   └── all.blade.php
└── menu/
    └── public.blade.php

database/migrations/
├── add_nombre_to_menus_table.php
└── add_activo_to_menus_table.php
```

## 🎨 Características de UI/UX

### **Mensajes Modernos:**
- Animaciones CSS personalizadas
- Gradientes y efectos glassmorphism
- Indicadores de progreso animados
- Diseño responsivo

### **Botón Flotante:**
- Posición fija en esquina inferior derecha
- Efectos hover y transformaciones
- Guarda todos los cambios del formulario

### **Indicadores Visuales:**
- Badge verde para menú activo
- Botón "⭐ Activar Menú" para menús inactivos
- Logo por defecto cuando no hay logo personalizado

## 🎯 Próximas Funcionalidades

- **PWA (Progressive Web App)** - Instalable como app
- **Notificaciones push** - Ofertas y promociones
- **Integración WhatsApp** - Chat directo
- **Análisis avanzado** - Métricas de conversación
- **Temas personalizados** - Branding específico
- **Procesamiento de PDFs** - Mejoras en extracción de texto

## 🤝 Contribución

1. Fork el proyecto
2. Crear rama para feature (`git checkout -b feature/nueva-funcionalidad`)
3. Commit cambios (`git commit -m 'Agregar nueva funcionalidad'`)
4. Push a la rama (`git push origin feature/nueva-funcionalidad`)
5. Abrir Pull Request

## 📄 Licencia

Este proyecto está bajo la Licencia MIT. Ver `LICENSE` para más detalles.

---

**Desarrollado con ❤️ para revolucionar la experiencia gastronómica digital**