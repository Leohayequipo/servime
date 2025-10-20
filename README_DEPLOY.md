# 🚀 Cómo hacer Deploy de Servime en Hostinger

## 📚 **DOCUMENTACIÓN DISPONIBLE**

Este proyecto incluye documentación completa para el deploy en Hostinger:

| Archivo | Descripción | ¿Cuándo usar? |
|---------|-------------|---------------|
| **QUICK_START.md** | ⚡ Guía rápida (5 min) | Primera vez, quiero ir directo |
| **DEPLOY_HOSTINGER.md** | 📖 Guía completa y detallada | Necesito entender todo el proceso |
| **hostinger-config.txt** | ⚙️ Configuraciones y comandos | Referencia rápida de comandos |
| **deploy-hostinger.sh** | 🤖 Script automatizado | Despliegues futuros (actualizaciones) |

---

## ⚡ **INICIO RÁPIDO (Primera Vez)**

### **Opción 1: Guía Visual Paso a Paso**

```
1. Lee → QUICK_START.md
2. Sigue los pasos (copiar/pegar)
3. ¡Listo en 5 minutos!
```

### **Opción 2: Resumido Aquí**

#### **Paso 1: Preparar Hostinger**
- ✅ Activar SSH en hPanel
- ✅ Crear base de datos MySQL
- ✅ Anotar credenciales

#### **Paso 2: Conectar y Subir**
```bash
ssh u123456@tu-dominio.com
git clone https://github.com/TU-USUARIO/servime.git servime
cd servime
composer install --no-dev
```

#### **Paso 3: Configurar**
```bash
cp .env.example .env
nano .env  # Editar con tus datos
php artisan key:generate
php artisan migrate --force
```

#### **Paso 4: Public**
```bash
cd ~/public_html
rm -rf *
cp -r ~/servime/public/* .
nano index.php  # Cambiar rutas a ../servime/
```

#### **Paso 5: Optimizar**
```bash
cd ~/servime
php artisan config:cache
php artisan route:cache
php artisan storage:link
chmod -R 755 storage bootstrap/cache
```

#### **Paso 6: ¡Probar!**
```
https://tu-dominio.com
```

---

## 🔄 **ACTUALIZACIONES FUTURAS**

Una vez que tu proyecto esté en producción, para subir cambios:

### **Opción A: Script Automatizado** (Recomendado)

```bash
# Conectar por SSH
ssh u123456@tu-dominio.com

# Ejecutar script
cd ~/servime
bash deploy-hostinger.sh
```

### **Opción B: Manual**

```bash
cd ~/servime
php artisan down
git pull origin main
composer install --no-dev
php artisan migrate --force
php artisan cache:clear
php artisan config:cache
cp -r public/* ~/public_html/
php artisan up
```

---

## 📊 **FLUJO VISUAL DEL DEPLOY**

```
┌─────────────────────────────────────────────────────────────┐
│                    TU PC (Desarrollo)                        │
│                                                               │
│  1. Desarrollas cambios                                      │
│  2. git add . && git commit -m "..."                         │
│  3. git push origin main                                     │
└────────────────────────┬─────────────────────────────────────┘
                         │
                         │ Push a GitHub
                         ▼
┌─────────────────────────────────────────────────────────────┐
│                    GITHUB / GITLAB                           │
│                                                               │
│  Repositorio con el código actualizado                       │
└────────────────────────┬─────────────────────────────────────┘
                         │
                         │ git pull
                         ▼
┌─────────────────────────────────────────────────────────────┐
│                  HOSTINGER (Producción)                      │
│                                                               │
│  /home/u123456/                                              │
│  ├── servime/              ← Código Laravel                  │
│  │   ├── app/                                                │
│  │   ├── config/                                             │
│  │   ├── .env            ← Configuración producción         │
│  │   └── ...                                                 │
│  └── public_html/         ← Archivos públicos               │
│      ├── index.php       ← Apunta a ../servime/             │
│      ├── css/                                                │
│      └── js/                                                 │
│                                                               │
│  Base de Datos MySQL: u123456_servime                       │
│  PHP: 8.1+                                                   │
│  SSL: Activo                                                 │
└────────────────────────┬─────────────────────────────────────┘
                         │
                         │ HTTPS
                         ▼
┌─────────────────────────────────────────────────────────────┐
│                  USUARIOS / CLIENTES                         │
│                                                               │
│  Acceden a: https://tu-dominio.com                          │
│  ✅ Chat con IA funcionando                                  │
│  ✅ Menús con imágenes                                       │
│  ✅ Sistema completo                                         │
└─────────────────────────────────────────────────────────────┘
```

---

## 🎯 **¿QUÉ ARCHIVO LEO PRIMERO?**

### **Si es tu PRIMERA VEZ:**
```
📖 Lee: QUICK_START.md
```
- Pasos simples y directos
- Copiar y pegar comandos
- Tiempo: ~5 minutos

### **Si quieres ENTENDER TODO:**
```
📖 Lee: DEPLOY_HOSTINGER.md
```
- Explicación detallada
- Troubleshooting completo
- Conceptos de Laravel en Hostinger

### **Si ya hiciste deploy y necesitas ACTUALIZAR:**
```
🤖 Usa: deploy-hostinger.sh
```
- Script automatizado
- Deploy en 1 comando
- Rollback fácil si falla

### **Si necesitas una REFERENCIA RÁPIDA:**
```
⚙️ Lee: hostinger-config.txt
```
- Comandos útiles
- Configuraciones
- Solución de problemas

---

## 🆘 **AYUDA Y SOPORTE**

### **Problemas Comunes:**

| Error | Solución |
|-------|----------|
| Error 500 | Ver logs: `tail -f ~/servime/storage/logs/laravel.log` |
| BD no conecta | Verificar `.env` y credenciales en hPanel |
| CSS no carga | Copiar public: `cp -r ~/servime/public/* ~/public_html/` |
| "No app key" | `php artisan key:generate` |
| Permisos | `chmod -R 755 ~/servime/storage` |

### **Dónde Buscar Más Ayuda:**

1. **Logs de Laravel:**
   ```bash
   tail -f ~/servime/storage/logs/laravel.log
   ```

2. **Soporte de Hostinger:**
   - Chat 24/7 en hPanel (esquina inferior derecha)
   - https://support.hostinger.com

3. **Documentación Laravel:**
   - https://laravel.com/docs/10.x/deployment

---

## ✅ **CHECKLIST ANTES DE DEPLOY**

Antes de hacer el deploy, verifica que tienes:

### **En Hostinger:**
- [ ] Hosting Premium activo
- [ ] SSH habilitado
- [ ] Base de datos MySQL creada
- [ ] PHP 8.1+ configurado
- [ ] Dominio apuntando al hosting (o usar temporal)

### **En tu Código:**
- [ ] Proyecto en GitHub/GitLab
- [ ] `.env.example` actualizado (sin datos sensibles)
- [ ] Migraciones funcionan
- [ ] API de OpenAI configurada
- [ ] Todo commiteado y pusheado

### **Credenciales Listas:**
- [ ] Usuario y password SSH
- [ ] Nombre de base de datos
- [ ] Usuario y password de MySQL
- [ ] API Key de OpenAI
- [ ] URL del dominio

---

## 📞 **CONTACTO**

Si algo no funciona:
1. Revisa los logs (ver arriba)
2. Consulta `DEPLOY_HOSTINGER.md` sección "Troubleshooting"
3. Contacta soporte de Hostinger
4. Revisa la documentación de Laravel

---

## 🎉 **¡ÉXITO CON TU DEPLOY!**

Una vez completado el deploy, tu aplicación Servime estará disponible en:

```
🌐 https://tu-dominio.com
```

### **Características funcionando:**
- ✅ Chat con IA (OpenAI)
- ✅ Procesamiento de menús PDF
- ✅ Imágenes de platos
- ✅ Sistema completo de comercios
- ✅ SSL/HTTPS seguro
- ✅ Optimización de Laravel

---

## 🚀 **PRÓXIMOS PASOS**

Después del deploy:

1. **Probar todas las funcionalidades**
   - Crear un comercio
   - Subir un menú PDF
   - Probar el chatbot
   - Subir imágenes de platos

2. **Configurar backups automáticos**
   - hPanel → Backups → Configurar

3. **Monitorear el uso**
   - hPanel → Statistics → Ver métricas

4. **Optimizar para producción**
   - Activar CDN de Hostinger
   - Configurar caching adicional
   - Revisar logs periódicamente

---

**Versión:** 1.0  
**Fecha:** Octubre 2025  
**Proyecto:** Servime - Sistema de Menús con IA  
**Hosting:** Hostinger Premium  

¡Buen deploy! 🎉

