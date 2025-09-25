# 🍽️ Servime - Sistema de Menús Digital con IA

**Sistema completo de gestión de menús digitales con chatbot inteligente para restaurantes y bares.**

## ✨ Características Principales

- 🤖 **Chatbot con OpenAI** - Respuestas inteligentes sobre el menú
- 📱 **Vista móvil optimizada** - Experiencia perfecta en smartphones
- 🎯 **QR Code automático** - Acceso instantáneo desde códigos QR
- 🧠 **Procesamiento de IA** - Extracción automática de contenido de menús
- 🎨 **Branding personalizado** - Logo y colores del comercio
- 📊 **Panel de administración** - Gestión completa de menús y comercios

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

### 🔄 **FASE 3: Análisis y Métricas** (En desarrollo)
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
OPENAI_BASE_URL=https://api.openai.com/v1
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
1. **Crear comercio** - Registrar restaurante/bar
2. **Subir menús** - PDF o imágenes
3. **Procesar con IA** - Extracción automática de contenido
4. **Generar QR** - Código para acceso público
5. **Ver métricas** - Estadísticas de uso

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
│   ├── Menu.php
│   └── User.php
└── Services/
    ├── MenuProcessorService.php
    └── ChatbotService.php

resources/views/
├── comercios/
├── menus/
└── menu/
    └── public.blade.php
```

## 🎯 Próximas Funcionalidades

- **PWA (Progressive Web App)** - Instalable como app
- **Notificaciones push** - Ofertas y promociones
- **Integración WhatsApp** - Chat directo
- **Análisis avanzado** - Métricas de conversación
- **Temas personalizados** - Branding específico

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