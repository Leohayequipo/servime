---
name: estadisticas
description: Sistema de analytics/tracking de ServiME — conversaciones del chatbot + eventos del menú público (tracking_events). Usar al tocar el panel de analytics, métricas, tracking de clicks/elecciones, o al sumar visualizaciones (IA vs clásico, promos, heatmap, embudo). Relacionado con [[planes]] (la feature `analytics`).
---

# Analytics / Tracking — ServiME

Dos fuentes de datos alimentan las estadísticas por comercio:

1. **`chat_conversations`** (server-side, ya existía): cada interacción del chatbot
   (mensaje, respuesta, `used_openai`, `response_time_ms`, ip, user_agent, `session_id`, `metadata`).
   De acá salen: palabras/intención, IA vs fallback, tiempo de respuesta, preguntas frecuentes.
2. **`tracking_events`** (client-side, nuevo): eventos del menú público. Columns:
   `comercio_id`, `session_id`, `event`, `data` (JSON), `user_ip`, `user_agent`, `created_at`.
   Modelo `App\Models\TrackingEvent` (scopes `forComercio`, `event`, `recent`).

## Tracking de eventos (Fase 1)

- **Endpoint:** `POST /api/track/{comercio:slug}` (`TrackingController@store`, ruta `track`),
  `throttle:120,1`, sin auth, **excluido de CSRF** (`VerifyCsrfToken::$except` → `api/track/*`).
- **Whitelist de eventos** (`TrackingController::ALLOWED_EVENTS`): `page_view` (data.view: choose|chat|classic),
  `menu_choice` (data.choice: ia|classic), `promo_click` (data.promo), `dish_want` (data.nombre, data.price).
  Eventos fuera de la lista se ignoran silenciosamente (204).
- **Frontend:** partial `resources/views/menu/partials/tracking.blade.php`, incluido en
  `menu/choose|chat|classic` con `['trackView' => '...']`. Define `window.smTrack(event, data)`
  (usa `navigator.sendBeacon`), dispara `page_view` al cargar y delega clicks:
  `[data-track-choice]` (cards de choose) → menu_choice; `.cat-chip-promo` → promo_click;
  `.btn-lo-quiero` → dish_want. Reusa el `session_id` del chat (`servime_chat_session_{id}`),
  así los eventos correlacionan con las conversaciones.

## Panel (`AnalyticsController`, rutas `analytics.*`, gateadas por feature `analytics`)

- `dashboard()` — overview. **Scopeado**: super admin ve todo; el comercio común solo lo suyo
  (antes había fuga de datos). Usuario común necesita la feature `analytics`.
- `comercio(Comercio $comercio)` — detalle por comercio (binding por **slug**; antes hacía
  `findOrFail($slug)` → 404, ya arreglado). Ownership + feature `analytics`.
- `exportConversations(Comercio $comercio)` — CSV.
- Link en la nav (`layouts/app.blade.php`): visible a super admin o a comercios con `analytics`.

## ⚠️ Gotchas

- Las rutas de analytics bindean por **slug**: pasá el modelo `$comercio` a `route()`, no `$comercio->id`.
- El panel por comercio (`analytics.comercio`) ya grafica los `tracking_events` (Fase 2):
  IA vs clásico, embudo, promos top, platos top ("Lo quiero"), top palabras y heatmap hora×día.
  Las métricas se calculan en `AnalyticsController::getEventMetrics()` y `getTopWords()`
  (se traen los eventos `recent(30)` una vez y se agrupan en PHP). Gráficos con CSS (sin libs).
- `layouts/app.blade.php` NO renderiza el slot `header`: el encabezado va dentro del body (`{{ $slot }}`).
- Tracking nunca debe romper la página: `smTrack` está envuelto en try/catch y usa sendBeacon.

## Datos de demo (local)

`php artisan db:seed --class=DemoAnalyticsSeeder` — genera ~120 sesiones por comercio con
eventos + conversaciones realistas (picos de almuerzo/cena, 62% elige IA). No corre en producción.

## Roadmap

- **Fase 2 (hecha):** visualizaciones en `analytics.comercio` — IA vs clásico, embudo,
  promos clickeadas, platos top (dish_want), top palabras y heatmap por hora/día.
- **Fase 3 (hecha):** intención de las consultas + sugerencias accionables, en
  `App\Services\AnalyticsInsightsService` (`intents()` usa el `intent` guardado si existe,
  si no rule-based por keywords con word-boundary, **una categoría por mensaje**;
  `suggestions()` arma tips según intención + métricas).
- **Intención con IA (hecha, solo Premium):** feature `intencion_ia`. Botón "Reclasificar con IA"
  en `analytics.comercio` (ruta `analytics.reclassify`, middleware `plan.feature:intencion_ia`)
  → encola `ClassifyIntentsJob` → `App\Services\IntentClassifierService::classifyPending()`:
  toma mensajes recientes **sin `intent`** (incremental), los manda **en lotes de 40** a
  `gpt-4o-mini` (JSON), guarda `intent`/`intent_source='ia'` en `chat_conversations`.
  Límite **blando** (corre cuando quieras; como es incremental el costo está acotado).
  Cada corrida se registra en `intent_classification_runs` y el **super admin** ve el uso
  en el dashboard de analytics (tabla + total de mensajes clasificados).
  ⚠️ `intent`/`intent_source` deben estar en `$fillable` de `ChatConversation` (modo estricto).
- **Siguiente (pendiente):** métricas extra sin IA (dispositivo desde user_agent,
  visitantes que vuelven, tiempo de sesión real).

## Tests

`tests/Feature/AnalyticsTrackingTest.php`: endpoint (evento válido, whitelist, validación),
panel (binding por slug, scope, gating por feature, aislamiento entre comercios).
