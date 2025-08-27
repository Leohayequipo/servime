# 🧠 Servime Menu AI

Sistema en Laravel 12 donde comercios pueden cargar su carta (PDF o imagen) y generar una URL pública con un agente GPT que responde preguntas sobre su menú.

---

## 🚀 Requisitos

- PHP >= 8.2
- MySQL / MariaDB
- Node.js >= 16
- Composer
- Laravel 12.x
- XAMPP / Valet / Laravel Sail

---

## ⚙️ Instalación (desde cero)

### 1. Clonar el repositorio

```bash
git clone https://github.com/tu-usuario/servime-menu.git
cd servime-menu
2. Instalar dependencias PHP
bash
Copiar
Editar
composer install
3. Instalar Laravel Breeze
Si aún no está instalado:

bash
Copiar
Editar
composer require laravel/breeze --dev
php artisan breeze:install blade
4. Instalar dependencias de frontend
bash
Copiar
Editar
npm install && npm run dev
5. Configurar archivo .env
Copiar el archivo .env.example como .env:

bash
Copiar
Editar
cp .env.example .env
Modificar las variables de conexión a base de datos:

env
Copiar
Editar
DB_DATABASE=servime_menu
DB_USERNAME=root
DB_PASSWORD=
6. Generar la key de la app

php artisan key:generate
7. Crear base de datos MySQL
Crear una base llamada servime_menu en tu gestor favorito o consola:


CREATE DATABASE servime_menu CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
8. Ejecutar migraciones

php artisan migrate
🧪 Servidor local

php artisan serve
Visitar: http://127.0.0.1:8000