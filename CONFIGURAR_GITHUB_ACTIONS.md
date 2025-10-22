# 🚀 CONFIGURAR AUTO-DEPLOY CON GITHUB ACTIONS
## ⚡ Deploy automático cada vez que haces push

---

## 🎯 **OBJETIVO:**
Configurar GitHub Actions para que **automáticamente** haga deploy a Hostinger cada vez que haces `git push` a la rama `main`.

---

## 🔧 **PASO 1: Configurar SSH en GitHub**

### **1.1 Generar clave SSH (si no tienes una):**
```bash
# En tu PC
ssh-keygen -t rsa -b 4096 -C "github-actions-servime"
# Presiona Enter para usar ubicación por defecto
# NO pongas contraseña (presiona Enter dos veces)
```

### **1.2 Obtener la clave pública:**
```bash
# En Windows (PowerShell)
Get-Content ~/.ssh/id_rsa.pub

# En Linux/Mac
cat ~/.ssh/id_rsa.pub
```

### **1.3 Agregar clave al servidor Hostinger:**
```bash
# Conectar al servidor
ssh u560898880@br-asc-web803.main-hosting.eu

# Crear directorio .ssh si no existe
mkdir -p ~/.ssh

# Agregar tu clave pública
echo "TU_CLAVE_PUBLICA_AQUI" >> ~/.ssh/authorized_keys

# Configurar permisos
chmod 700 ~/.ssh
chmod 600 ~/.ssh/authorized_keys
```

### **1.4 Probar conexión SSH:**
```bash
# Desde tu PC
ssh u560898880@br-asc-web803.main-hosting.eu
# Debe conectarse sin pedir contraseña
```

---

## 🔐 **PASO 2: Configurar Secretos en GitHub**

### **2.1 Ir a tu repositorio en GitHub:**
1. Ve a `https://github.com/Leohayequipo/servime`
2. Click en **"Settings"** (pestaña superior)
3. Click en **"Secrets and variables"** → **"Actions"**

### **2.2 Agregar secreto SSH:**
1. Click en **"New repository secret"**
2. **Name:** `HOSTINGER_SSH_KEY`
3. **Secret:** (pegar el contenido de tu clave privada)

```bash
# Obtener clave privada (en tu PC)
Get-Content ~/.ssh/id_rsa
```

**⚠️ IMPORTANTE:** Copia TODO el contenido incluyendo `-----BEGIN OPENSSH PRIVATE KEY-----` y `-----END OPENSSH PRIVATE KEY-----`

---

## 📁 **PASO 3: Subir archivos al repositorio**

### **3.1 Hacer commit de los archivos:**
```bash
# En tu PC
cd C:\GitHub\servime

# Agregar archivos
git add .github/workflows/auto-deploy.yml
git add deploy-seguro-hostinger.sh
git add reparar-rutas-emergencia.sh
git add PROBLEMA_CRITICO_RUTAS.md
git add CONFIGURAR_GITHUB_ACTIONS.md

# Commit
git commit -m "feat: Auto-deploy con GitHub Actions"

# Push
git push origin main
```

---

## 🚀 **PASO 4: Probar el auto-deploy**

### **4.1 Hacer un cambio de prueba:**
```bash
# Hacer un cambio pequeño
echo "<!-- Auto-deploy test - $(date) -->" >> resources/views/welcome.blade.php

# Commit y push
git add .
git commit -m "test: Prueba de auto-deploy"
git push origin main
```

### **4.2 Verificar en GitHub:**
1. Ve a tu repositorio en GitHub
2. Click en **"Actions"** (pestaña superior)
3. Deberías ver el workflow ejecutándose
4. Click en el workflow para ver los logs

### **4.3 Verificar en el servidor:**
```bash
# Conectar al servidor
ssh u560898880@br-asc-web803.main-hosting.eu

# Verificar que el sitio funciona
php ~/domains/servime.net/public_html/index.php
# Debe mostrar: "This script cannot be run from the CLI."

# Verificar el sitio web
curl -I https://servime.net
```

---

## 🔍 **PASO 5: Monitoreo y Logs**

### **5.1 Ver logs de GitHub Actions:**
1. Ve a `https://github.com/Leohayequipo/servime/actions`
2. Click en el último workflow
3. Click en cada paso para ver logs detallados

### **5.2 Ver logs del servidor:**
```bash
# Conectar al servidor
ssh u560898880@br-asc-web803.main-hosting.eu

# Ver logs de Laravel
tail -f ~/servime/storage/logs/laravel.log

# Ver logs del sistema
tail -f /var/log/apache2/error.log
```

---

## 🛡️ **CARACTERÍSTICAS DEL AUTO-DEPLOY:**

### **✅ Lo que hace automáticamente:**
- ✅ Detecta cambios en GitHub
- ✅ Verifica que el sistema funciona ANTES del deploy
- ✅ Crea backup completo
- ✅ Preserva el `index.php` que funciona
- ✅ Copia archivos sin romper rutas
- ✅ Configura storage público
- ✅ Optimiza cachés
- ✅ Verifica que todo funciona DESPUÉS
- ✅ Restaura automáticamente si algo falla
- ✅ Notifica el resultado

### **✅ Seguridad:**
- ✅ NUNCA rompe las rutas críticas
- ✅ Backup automático antes de cambios
- ✅ Verificación antes y después
- ✅ Restauración automática si falla
- ✅ Logs detallados de cada paso

---

## 🚨 **EN CASO DE PROBLEMAS:**

### **Si el auto-deploy falla:**
1. **Ver logs en GitHub Actions** para ver qué falló
2. **Conectar al servidor** y ejecutar reparación manual:
   ```bash
   ssh u560898880@br-asc-web803.main-hosting.eu
   cd ~/servime
   bash reparar-rutas-emergencia.sh
   ```

### **Si el sitio está caído:**
```bash
# Reparación de emergencia
ssh u560898880@br-asc-web803.main-hosting.eu
cd ~/servime
bash reparar-rutas-emergencia.sh
```

---

## 📊 **WORKFLOW COMPLETO:**

### **Desarrollo normal:**
1. **Haces cambios** en tu PC
2. **Haces commit y push** a GitHub
3. **GitHub Actions detecta** el cambio automáticamente
4. **Ejecuta deploy seguro** en el servidor
5. **Verifica** que todo funciona
6. **Notifica** el resultado

### **En caso de problemas:**
1. **GitHub Actions detecta** el problema
2. **Ejecuta reparación** automáticamente
3. **Verifica** que se solucionó
4. **Notifica** el resultado

---

## 🎯 **VENTAJAS:**

- ✅ **Deploy automático** en cada push
- ✅ **NUNCA rompe** las rutas críticas
- ✅ **Backup automático** antes de cambios
- ✅ **Verificación automática** antes y después
- ✅ **Restauración automática** si falla
- ✅ **Logs detallados** de cada paso
- ✅ **Notificaciones** del resultado
- ✅ **Seguridad total** del sistema

---

## 🔧 **COMANDOS ÚTILES:**

### **Ejecutar deploy manualmente:**
```bash
# En GitHub: Actions → "Auto Deploy ServiME" → "Run workflow"
```

### **Ver estado del último deploy:**
```bash
# En GitHub: Actions → Click en el último workflow
```

### **Verificar sistema manualmente:**
```bash
ssh u560898880@br-asc-web803.main-hosting.eu
php ~/domains/servime.net/public_html/index.php
```

---

## 📝 **CHECKLIST DE CONFIGURACIÓN:**

- [ ] Clave SSH generada y agregada al servidor
- [ ] Secreto `HOSTINGER_SSH_KEY` configurado en GitHub
- [ ] Archivos subidos al repositorio
- [ ] Primer push realizado
- [ ] GitHub Actions ejecutándose
- [ ] Deploy exitoso verificado
- [ ] Sitio web funcionando

---

## 🎉 **RESULTADO FINAL:**

**Con este sistema:**
- ✅ **Push a GitHub** = **Deploy automático**
- ✅ **NUNCA** más 5 horas de caída
- ✅ **Sistema 100% seguro**
- ✅ **Logs detallados**
- ✅ **Notificaciones automáticas**

---

**💡 Una vez configurado, solo necesitas hacer `git push` y el deploy se hace automáticamente!**
