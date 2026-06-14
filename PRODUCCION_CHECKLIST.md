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
- [x] **Permisos admin**: `AdminAccessTest` — guest→login, usuario común→403,
      super admin→200. 3 tests ✅.
- [x] **Menús**: `MenuManagementTest` — activar deja uno solo activo, baja lógica
      (soft-delete), no se puede activar menú ajeno (403). 3 tests ✅.
- [x] **Carrito público**: `CartTest` — alta/total, remove/clear, rechazo si pedidos
      deshabilitados, aislamiento por sesión. 4 tests ✅.
- [ ] (Opcional) Rutas públicas por slug y chatbot/import con APIs de IA mockeadas.

> Infra de tests agregada: `ComercioFactory`, `MenuFactory`, estado
> `UserFactory::superAdmin()`, trait `HasFactory` en `Comercio`, `Menu`.
> Total actual: **40 tests verdes**.

## 🔴 2. Hardening de producción

**Hecho en código:**
- [x] Forzar HTTPS en producción — `URL::forceScheme('https')` en `AppServiceProvider`
      (solo cuando `APP_ENV=production`).
- [x] `php artisan config:cache && route:cache && view:cache` en el deploy
      — ya presente en `deploy-actualiza.sh` (pasos 10-11).
- [x] Sin secrets versionados — `.env*` ignorado en `.gitignore`; solo `.env.example` trackeado.
- [x] `.env.example` completado con claves de IA (OpenAI/Gemini) y nota de producción.

**Pendiente en el servidor (lo aplica el usuario en el `.env` de prod):**
- [ ] `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://servime.net`.
- [ ] `LOG_LEVEL=error` (hoy en local está en `debug`).
- [ ] Permisos `storage/` y `bootstrap/cache/` correctos; `.env` en `chmod 600`.
- [ ] **Backups automáticos de BD** programados (hPanel o cron `mysqldump`).
- [ ] Tras editar el `.env`: `php artisan config:clear && php artisan config:cache`.

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
