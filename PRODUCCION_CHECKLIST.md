# ✅ Checklist de cierre para producción — ServiME

Estado de las tareas para dejar el sistema listo y estable en producción.
Marcar `[x]` a medida que se completan. Prioridad: 🔴 alta · 🟡 media · 🟢 baja.

---

## 🔴 1. Tests de flujos críticos

- [x] **Baseline verde restaurado** — había 7 tests de Breeze fallando (registro/reset/
      borrado) por reflejar comportamiento viejo. Reescritos para validar lo intencional
      (registro cerrado, notificación custom, borrado deshabilitado). 24/24 ✅.
- [x] **Aislamiento por comercio**: `ComercioIsolationTest` — usuario común no puede
      ver/editar/actualizar/eliminar comercios ajenos (403); super admin sí. 6 tests ✅.
- [ ] Permisos admin: rutas `/admin/*` rechazan a usuarios sin rol `super_admin`.
- [ ] Menús: crear, activar (solo uno activo por comercio), soft-delete y restore.
- [ ] Carrito público: incrementar/eliminar/limpiar por sesión sin auth.
- [ ] Rutas públicas por slug responden y no exponen comercios inactivos/ajenos.
- [ ] (Opcional) Chatbot e import/export con las APIs de IA mockeadas.

> Infra de tests agregada: `ComercioFactory`, estado `UserFactory::superAdmin()`,
> trait `HasFactory` en el modelo `Comercio`. Total actual: **30 tests verdes**.

## 🔴 2. Hardening de producción

- [ ] `.env` de prod: `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://servime.net`.
- [ ] `LOG_LEVEL=error` (hoy en local está en `debug`).
- [ ] Forzar HTTPS (redirección + `URL::forceScheme('https')` o a nivel servidor).
- [ ] `php artisan config:cache && route:cache && view:cache` en cada deploy.
- [ ] Permisos `storage/` y `bootstrap/cache/` correctos; `.env` en `chmod 600`.
- [ ] **Backups automáticos de BD** programados (hPanel o cron `mysqldump`).
- [ ] Verificar que NO queden secrets en archivos versionados (ver punto 5).

## 🟡 3. Control de costos / resiliencia de IA

- [ ] Definir límite de gasto / alertas en OpenAI y Gemini.
- [ ] Fallback claro si la API de IA falla o tarda (no romper la vista del cliente).
- [ ] Revisar timeouts y reintentos en `ChatbotService` / procesadores de PDF.
- [ ] (Opcional) Cachear respuestas frecuentes del chatbot para bajar costo.

## 🟡 4. Verificar que los incendios viejos estén resueltos de raíz

Documentos `SOLUCION_*` borrados por estar resueltos; confirmar en código:

- [ ] Error 500: `smalot/pdfparser` está en `composer.json` ✅ — confirmar instalado en prod.
- [ ] Imágenes 404: confirmar que las nuevas imágenes se copian solas a `public_html`
      (el fix de código ya existe; validar en un alta real).

## 🟢 5. Limpieza y cierre

- [x] Limpieza de archivos `.md` redundantes (12 → 4).
- [x] CLAUDE.md creado.
- [ ] **Rotar la API key de Gemini** que estaba expuesta en `SOLUCION_ERROR_500_PRODUCCION.md`
      (sigue en el historial de git aunque el archivo se borró). ⚠️ Importante.
- [ ] Commitear los cambios pendientes (`menu/classic.blade.php`, limpieza de docs).
- [ ] Revisar/actualizar `.env.example` para que refleje todas las claves necesarias.

---

**Sugerencia de orden:** 5 (rotar key) → 2 (hardening) → 1 (tests) → 3 (IA) → 4 (verificación).
</content>
