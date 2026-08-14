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
| Rate limit chat IA | 20/min por dispositivo + 60/min por IP | `throttle:chat-ia` (`AppServiceProvider`) |
| Rate limit carrito | 60 + 300 | `throttle:carrito` |
| Rate limit tracking | 120 + 600 | `throttle:tracking` |
| Rate limit landing chat | 20 req/min por IP | `routes/web.php` |
| Rate limit crear menú (OCR) | 5 req/min | `routes/web.php:113` |
| Modelo chatbot | **según el plan** del comercio | `Comercio::modeloIA()` |
| Máx. tokens respuesta chatbot | **según el plan** | `Comercio::maxTokensChat()` |
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

## 🔴 5. Con fecha de vencimiento

- [ ] **Migrar `gemini-2.5-flash` antes del 16/10/2026.** Es el modelo que procesa los
      PDFs de los menús (`GeminiPdfProcessorService`) y ese día se discontinúa: si no se
      migra, los comercios dejan de poder subir menús en PDF. En la cuenta ya están
      disponibles `gemini-3.5-flash` y `gemini-3.6-flash`. Es cambiar `GEMINI_MODEL` y
      probar con PDFs reales de distinto peso. **No dejarlo para octubre.**

## 🔴 6. Trabajo que existe solo en la máquina local

- [ ] **La rama `feat/portada-menu-publico` nunca se pusheó ni se mergeó a `main`.**
      Al 14/08/2026 son ~29 commits sin upstream: los atajos configurables, el panel de
      analytics completo, el consumo de tokens del chatbot, los fixes de precios e
      imágenes de menús y el round-trip del Excel. Si se pierde el disco, se pierde todo.
      Es el riesgo más grande abierto y el más barato de cerrar.

## 🟡 7. Bugs conocidos, anotados y sin arreglar

- [ ] **El stream del chat cobra cuota aunque OpenAI falle.** En `ChatbotController@chatStream`
      el `try/catch` de `streamMessage()` es interno: si la llamada explota y se responde con
      `generateFallbackResponse()`, igual corre `registrarUsoIA()`. No se gastó un token y el
      comercio pierde un mensaje de su cuota. La ruta sin stream **no** lo tiene — y la que
      usa el front es justo la que cobra de más. Detalle en la skill `planes`.
- [ ] **El aviso de cuota llega tarde.** El dashboard avisa recién cuando se agotó, no al
      acercarse. Al 80% le daría margen al comercio para pedir el upgrade antes de quedarse
      sin asistente en pleno servicio.
- [ ] **Decisión pendiente:** igualar `max_tokens_chat` en ~1500 para los tres planes desde
      `/admin/plans` (sin deploy). Medido: ninguna respuesta real pasó de 497 tokens, así que
      diferenciarlo por plan no le cambia nada al comercio. Ver skill `planes`.

## 🟢 8. Limpieza y cierre

- [x] Limpieza de archivos `.md` redundantes (12 → 4).
- [x] CLAUDE.md creado.
- [x] **Rotar la API key de Gemini** expuesta — rotada por el usuario (local + servidor).
- [x] `.env.example` actualizado con todas las claves necesarias.
- [ ] Push de los commits locales pendientes (esperando que Hostinger vuelva).

---

## Estado general (14/08/2026)

Tareas 1, 2, 3 y 4 cerradas en lo que toca al código; la suite está en **201 tests verdes**.

Lo abierto, por orden de urgencia:

1. 🔴 **Pushear la rama** — ~29 commits que solo existen en local (sección 6).
2. 🔴 **Migrar Gemini antes del 16/10/2026** — quedan ~2 meses (sección 5).
3. 🔴 **Topes de gasto** en los paneles de OpenAI y Google AI (sección 3).
4. 🔴 **Backups automáticos de BD** y `.env` de producción (sección 2).
5. 🟡 Bugs anotados: cuota cobrada de más en el stream, aviso de cuota tardío (sección 7).

Ideas sin arrancar (no son deuda, son features nuevas):

- **Pedir reseña en Google.** Hoy el sistema no puede: no capta datos de contacto del
  comensal (el carrito lo identifica por `session_id` anónimo y no hay checkout), no hay
  canal de notificación hacia él, no hay campo donde guardar el link, y el chat no
  renderiza enlaces (`parseMarkdown` solo hace negrita, itálica, `[TAGS:]` e `[IMAGEN:]`).
  Camino corto: campo `google_review_url` + un marcador propio tipo `[[RESEÑA]]` que el
  front convierta en botón, reusando la cañería de `[[PLATOS]]`. **No** habilitar links
  markdown genéricos: dejaría entrar como enlace clickeable cualquier URL alucinada por el
  modelo o colada en el texto de un menú importado.
- **Disponibilidad / sin stock de un plato.** Hoy no existe el concepto: un plato está en
  el menú o no está, y el asistente lo recomienda igual aunque en la cocina se haya
  terminado. Es el próximo tema a diseñar.
</content>
