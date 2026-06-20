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

**Hecho en código:**
- [x] Fallback amable si la IA falla/tarda — verificado en `ChatbotService` y
      `LandingChatController` (devuelven mensaje al cliente, nunca un 500).
- [x] Timeouts cara al cliente reducidos a 20s (eran 60s chatbot / 30s landing)
      para proteger UX y workers de PHP.
- [x] Costo por llamada acotado: `max_tokens` + modelo `gpt-4o-mini` + rate limiting.

**Cómo está el "corte" HOY (importante):**
El sistema NO tiene un tope monetario (USD). Lo que acota el costo hoy es un corte
por *volumen y tamaño*, todo en código:

| Mecanismo | Valor | Dónde |
|---|---|---|
| Rate limit chat IA | 20 req/min/IP | `routes/web.php:114` (`throttle:20,1`) |
| Rate limit landing chat | 20 req/min | `routes/web.php` |
| Rate limit crear menú (OCR) | 5 req/min | `routes/web.php:86` |
| Modelo chatbot (mayor volumen) | `gpt-4o-mini` | `ChatbotService.php:522` (hardcode) |
| Máx. tokens respuesta chatbot | 900 | `ChatbotService.php:524` |
| Modelo OCR/carga menú | `gpt-4o` | `.env` → `OPENAI_MODEL` |
| Modelo PDFs | `gemini-2.5-flash` | `.env` → `GEMINI_MODEL` |

→ Esto hace el gasto *predecible*, pero ante un pico de tráfico nada lo frena en
dólares. El tope monetario es lo que falta configurar en los paneles (abajo).

**Tope por comercio (planes):** además existe una **cuota mensual de mensajes IA por
plan** (Básico/Pro/Premium, editable en `/admin/plans`). Al agotarse, el chatbot deja de
llamar a OpenAI y responde un mensaje amable. Es el control de costo más directo y mapea
a la facturación. El super admin puede resetear el uso de un comercio (se cobra aparte).

**Pendiente del usuario (paneles, no código):**
- [ ] **OpenAI** — tope real de gasto. platform.openai.com → Settings → Billing → Limits:
      definir *Hard limit* mensual (corta la API al alcanzarlo) + *Soft limit* (email de
      aviso al ~75%). Recomendado además: Billing → Payment → **desactivar auto-recharge**
      y cargar créditos fijos (cuando se agotan, la API deja de responder = tope más duro).
- [ ] **Google AI / Gemini** — OJO: los Budgets de Google Cloud solo **avisan, no cortan**.
      - Mínimo: console.cloud.google.com → Billing → **Budgets & alerts** → Create budget
        (monto mensual + alertas 50/90/100% por email).
      - Recomendado (cap indirecto real): APIs & Services → Generative Language API →
        **Quotas** → limitar requests por minuto/día.
      - Tope duro de verdad (overkill para esta escala): budget → Pub/Sub → Cloud Function
        que deshabilita el billing del proyecto.

**Nota / a evaluar aparte:**
- Los timeouts de procesamiento de menús (OCR) en `MenuProcessorService` y
  `PdfProcessorService` son largos a propósito (180–600s). Son admin-side; bajarlos
  podría romper la carga de PDFs grandes. Revisar solo si dan problemas.
- [ ] (Opcional) Cachear respuestas frecuentes del chatbot para bajar costo.

## 🟡 4. Verificar que los incendios viejos estén resueltos de raíz

- [x] **Error 500 — RESUELTO DE RAÍZ.** `smalot/pdfparser` estaba en `composer.json`
      pero NO en `composer.lock` (curita: se instaló a mano en prod). El deploy usa
      `composer install` (respeta el lock) → en un deploy limpio el 500 volvía.
      Arreglado: `composer update smalot/pdfparser` sincronizó el lock. Además se
      removió `barryvdh/laravel-dompdf`, un paquete huérfano sin uso en el código.
- [x] **Imágenes 404 — resuelto de raíz.** `uploadPlatoImagen` llama a
      `copyMenuItemToPublicHtml()` tras guardar; las imágenes nuevas se copian solas.
- [x] **Parse error en `ChatbotService` — RESUELTO.** El prompt de `createPrompt()` tenía
      comillas dobles sin escapar (`"  [IMAGEN:url]"`) dentro del string del prompt: cerraban
      el string antes de tiempo y PHP no podía compilar el archivo (fatal no-capturable).
      El chatbot devolvía 500 en cualquier consulta (salvo que OPcache sirviera una copia
      vieja). Escapadas las comillas en las 3 líneas afectadas. Cubierto con test.

> Mejora futura (no bloqueante): la copia a `public_html` usa una ruta hardcodeada
> del servidor. Lo ideal sería `php artisan storage:link` (symlink) en vez de duplicar
> archivos. Refactor para más adelante.

## 🟢 5. Limpieza y cierre

- [x] Limpieza de archivos `.md` redundantes (12 → 4).
- [x] CLAUDE.md creado.
- [x] **Rotar la API key de Gemini** expuesta — rotada por el usuario (local + servidor).
- [x] `.env.example` actualizado con todas las claves necesarias.
- [ ] Push de los commits locales pendientes (esperando que Hostinger vuelva).

---

## Estado general

Tareas 1, 2, 3 y 4 cerradas en lo que toca al código. Pendiente del usuario:
límite de gasto en paneles de IA y push + deploy cuando Hostinger esté online.
</content>
