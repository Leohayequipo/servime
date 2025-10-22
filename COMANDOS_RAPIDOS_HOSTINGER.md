# ⚡ Comandos Rápidos - Deploy ServiME en Hostinger

## 🔐 Conectar por SSH

```bash
ssh u560898880@br-asc-web803.main-hosting.eu
```

---

## 🚀 Deploy Automático (RECOMENDADO)

```bash
# 1. Ir al proyecto
cd ~/servime

# 2. Actualizar código desde GitHub
git pull origin main

# 3. Ejecutar deploy automático
bash deploy-hostinger-auto.sh
```

**Eso es todo!** El script hace todo automáticamente 🎉

---

## 🔧 Configuración Inicial (Primera vez)

### 1. Clonar proyecto
```bash
cd ~
git clone https://github.com/Leohayequipo/servime.git servime
cd servime
```

### 2. Instalar dependencias
```bash
composer install --no-dev --optimize-autoloader
```

### 3. Configurar .env
```bash
cp .env.example .env
nano .env
```

Pegar la configuración de producción (ver DEPLOY_HOSTINGER.md)

### 4. Generar APP_KEY
```bash
php artisan key:generate
```

### 5. Ejecutar migraciones
```bash
php artisan migrate --force
```

### 6. Ejecutar el script de deploy
```bash
bash deploy-hostinger-auto.sh
```

---

## 📝 Comandos Útiles del Día a Día

### Ver estructura del hosting
```bash
tree -L 2 ~/
```

### Limpiar cachés
```bash
cd ~/servime
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

### Optimizar para producción
```bash
cd ~/servime
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Ver logs de Laravel
```bash
tail -f ~/servime/storage/logs/laravel.log
```

### Ver últimas 50 líneas de logs
```bash
tail -n 50 ~/servime/storage/logs/laravel.log
```

### Verificar permisos
```bash
ls -la ~/servime/storage/
ls -la ~/servime/bootstrap/cache/
```

### Ajustar permisos (si es necesario)
```bash
chmod -R 775 ~/servime/storage
chmod -R 775 ~/servime/bootstrap/cache
```

---

## 🗄️ Base de Datos

### Ver tablas
```bash
mysql -u u560898880_servime -p u560898880_servime -e "SHOW TABLES;"
```

### Exportar base de datos
```bash
mysqldump -u u560898880_servime -p u560898880_servime > backup_$(date +%Y%m%d).sql
```

### Importar base de datos
```bash
mysql -u u560898880_servime -p u560898880_servime < backup.sql
```

### Ejecutar migraciones
```bash
cd ~/servime
php artisan migrate --force
```

### Rollback última migración
```bash
cd ~/servime
php artisan migrate:rollback --force
```

---

## 🔄 Actualizar Proyecto (Después de Push a GitHub)

### Opción 1: Con script automático (RECOMENDADO)
```bash
cd ~/servime
git pull origin main
bash deploy-hostinger-auto.sh
```

### Opción 2: Manual
```bash
cd ~/servime
git pull origin main
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
cp -r ~/servime/public/* ~/domains/servime.net/public_html/
```

---

## 📦 Gestión de Storage

### Copiar nuevos archivos públicos
```bash
cp -r ~/servime/storage/app/public/* ~/domains/servime.net/public_html/storage/
```

### Ver archivos en storage público
```bash
ls -la ~/domains/servime.net/public_html/storage/
```

---

## 🧪 Verificación y Testing

### Verificar que Laravel carga
```bash
php ~/domains/servime.net/public_html/index.php
```

**Respuesta esperada:** `This script cannot be run from the CLI.`

### Ver versión de PHP
```bash
php -v
```

### Ver extensiones PHP instaladas
```bash
php -m
```

### Verificar Composer
```bash
composer --version
```

### Probar conexión a base de datos
```bash
cd ~/servime
php artisan tinker
# Dentro de tinker:
DB::connection()->getPdo();
exit
```

---

## 🆘 Solución de Problemas

### Error 500 - Ver logs
```bash
tail -n 100 ~/servime/storage/logs/laravel.log
```

### Error de permisos
```bash
chmod -R 775 ~/servime/storage ~/servime/bootstrap/cache
```

### Error de .env
```bash
cd ~/servime
php artisan config:clear
php artisan config:cache
```

### Regenerar APP_KEY
```bash
cd ~/servime
php artisan key:generate --force
```

### Limpiar TODO y regenerar
```bash
cd ~/servime
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## 📊 Información del Sistema

### Espacio en disco
```bash
df -h
```

### Memoria disponible
```bash
free -h
```

### Procesos de PHP activos
```bash
ps aux | grep php
```

### Ver archivos más grandes
```bash
du -h ~/servime | sort -rh | head -20
```

---

## 🔐 Seguridad

### Ver contenido de .env (cuidado!)
```bash
cat ~/servime/.env
```

### Cambiar permisos de .env (más restrictivo)
```bash
chmod 600 ~/servime/.env
```

### Ver quién tiene acceso al servidor
```bash
w
```

---

## 💾 Backups

### Crear backup completo
```bash
# Crear directorio de backups
mkdir -p ~/backups

# Backup del código
tar -czf ~/backups/servime_$(date +%Y%m%d_%H%M%S).tar.gz ~/servime

# Backup de la base de datos
mysqldump -u u560898880_servime -p u560898880_servime > ~/backups/db_$(date +%Y%m%d_%H%M%S).sql
```

### Listar backups
```bash
ls -lh ~/backups/
```

### Restaurar desde backup
```bash
# Restaurar código
tar -xzf ~/backups/servime_FECHA.tar.gz -C ~/

# Restaurar base de datos
mysql -u u560898880_servime -p u560898880_servime < ~/backups/db_FECHA.sql
```

---

## 🎯 Workflow Típico de Actualización

```bash
# 1. Conectar
ssh u560898880@br-asc-web803.main-hosting.eu

# 2. Hacer backup
mysqldump -u u560898880_servime -p u560898880_servime > ~/backups/db_$(date +%Y%m%d_%H%M%S).sql

# 3. Actualizar código
cd ~/servime
git pull origin main

# 4. Deploy automático
bash deploy-hostinger-auto.sh

# 5. Verificar
curl -I https://servime.net

# 6. Ver logs si hay problemas
tail -f ~/servime/storage/logs/laravel.log
```

---

## 📞 Datos de Conexión

**SSH:**
- Host: `br-asc-web803.main-hosting.eu`
- Usuario: `u560898880`
- Proyecto: `/home/u560898880/servime`
- Public: `/home/u560898880/domains/servime.net/public_html`

**Base de Datos:**
- Host: `localhost`
- Base de datos: `u560898880_servime`
- Usuario: `u560898880_servime`
- Puerto: `3306`

**Sitio Web:**
- URL: https://servime.net

---

**💡 Tip:** Guarda este archivo en tu PC para tener siempre a mano los comandos más importantes.

