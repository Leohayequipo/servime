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
- El panel HOY visualiza solo métricas de `chat_conversations`. Los `tracking_events` ya se
  **capturan** pero todavía **no se grafican** (eso es Fase 2).
- Tracking nunca debe romper la página: `smTrack` está envuelto en try/catch y usa sendBeacon.

## Datos de demo (local)

`php artisan db:seed --class=DemoAnalyticsSeeder` — genera ~120 sesiones por comercio con
eventos + conversaciones realistas (picos de almuerzo/cena, 62% elige IA). No corre en producción.

## Roadmap

- **Fase 2 (pendiente):** visualizaciones en el panel — IA vs clásico, promos clickeadas,
  platos top (dish_want), heatmap por hora/día, embudo de carrito, top palabras.
- **Fase 3 (pendiente):** clasificación de intención (rule-based → IA) y "sugerencias" para el comercio.

## Tests

`tests/Feature/AnalyticsTrackingTest.php`: endpoint (evento válido, whitelist, validación),
panel (binding por slug, scope, gating por feature, aislamiento entre comercios).
