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
- **Whitelist de eventos** (`TrackingController::ALLOWED_EVENTS`), los de fuera se ignoran
  silenciosamente (204):

  | Evento | data | Se dispara en |
  |---|---|---|
  | `page_view` | `view: choose\|chat\|classic` | al cargar cada vista |
  | `menu_choice` | `choice: ia\|classic`, `desde: cta\|chip\|completo`, `label` | `[data-track-choice]` |
  | `promo_click` | `promo` | `.cat-chip-promo` |
  | `dish_want` | `nombre`, `price` | `.btn-lo-quiero` |
  | `order_summary` | `items` | abrir el "Resumen para el mozo" (`#fabWish`) |
  | `mic_start` | — | `recognition.onstart` del dictado por voz |

- **Frontend:** partial `resources/views/menu/partials/tracking.blade.php`, incluido en
  `menu/portada|choose|chat|classic` con `['trackView' => '...']`. Define `window.smTrack(event, data)`
  (usa `navigator.sendBeacon`), dispara `page_view` al cargar y delega los clicks de la tabla.
  Reusa el `session_id` del chat (`servime_chat_session_{id}`), así los eventos correlacionan
  con las conversaciones.
- **`order_summary` va solo al ABRIR** el panel (es un toggle): si se mandara también al cerrar,
  el indeciso valdría el doble. Y `mic_start` existe porque el dictado termina en el input y se
  manda como texto: sin evento propio es indistinguible de escribir.

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
- **`session_id` es un id de VISITANTE, no de sesión.** Vive en el localStorage del navegador
  y no vence nunca, así que el mismo id vuelve a aparecer días o semanas después.
  `getEventMetrics()` lo parte en **visitas** con `agruparVisitas()`: eventos del mismo
  `session_id` separados por más de `GAP_VISITA_MINUTOS` (30) son visitas distintas.
  Al sumar una métrica, decidí si se cuenta **por visita** (embudo, duración) o
  **por visitante** (dispositivos, recurrencia). No uses `session_id` crudo para nada temporal.
- Los eventos con `session_id` nulo (la columna es nullable) quedan fuera de las visitas.

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
- **Métricas extra (hechas, sin IA):** dispositivo (móvil/desktop/tablet desde `user_agent`),
  visitantes que vuelven (visitante con ≥2 visitas) y tiempo promedio **por visita**.
  Calculadas en `getEventMetrics()` desde `tracking_events`. Claves que van a la vista:
  `funnel` (por visita), `totalVisitas`, `devices`/`visitors`/`totalVisitantes`
  (por visitante).
- **Qué mira el comercio y qué miramos nosotros (decidido, hecho a medias).** Las métricas de
  infraestructura no van en el panel del comercio: no puede hacer nada con ellas y las
  mostrábamos en ámbar como si fueran culpa suya. El **tiempo de respuesta** salió de la fila de
  tarjetas (queda como nota al pie visible solo a super admin) y de `suggestions()`. **Sin
  responder se queda**, porque avisa cuando se agota la cuota del plan, que sí es accionable.
  En su lugar entró **Personas** (`totalVisitantes`). Toda tarjeta lleva su período en la nota.
  Las que no se explican solas llevan un `?` (prop `ayuda` de `x-stat` → helper `showInfo`;
  ojo: SweetAlert2 no respeta saltos de línea en `text`, un párrafo por ayuda).
- **Unidad de medida: por visita salvo que la pregunta sea otra (hecho).** El embudo,
  `menuChoice` y el micrófono cuentan **visitas**; "¿Por dónde entran?" y "Atajos más usados"
  cuentan **clicks a propósito** (ahí la pregunta es qué botón se usa, no cuánta gente entró) y
  lo dicen en su ayuda. `menuChoice` toma la **primera** elección de cada visita, no todas: así
  cada visita vale uno y la suma cierra exacto con el escalón "eligieron" del embudo. Antes
  contaba clicks y mostraba 25/6 al lado de un embudo que decía 13 — una sola visita había
  elegido 11 veces. Quien probó los dos modos va aparte en `menuChoice['ambos']` (nota al pie
  del widget), porque no puede sumar de ambos lados.
- **El embudo cierra en `order_summary` (hecho).** "Lo quiero" es un click sobre un plato
  suelto; abrir el pedido para revisarlo es lo más cerca de una venta que podemos medir. El
  escalón **exige `items > 0`**: el panel se puede abrir vacío y sin esa condición el último
  escalón salía más grande que el anterior, que en un embudo no puede pasar.
- **Rate limiting (hecho):** las rutas públicas ya no cuentan por IP sino por dispositivo
  (limitadores `chat-ia`, `carrito` y `tracking` en `AppServiceProvider`), así que un local
  lleno no se come el cupo entre todos y deja de perder eventos por 429. Ver CLAUDE.md.
  Verificado que **no hay Cloudflare** adelante: `$request->ip()` es la IP real del cliente.

## Pendientes

Los números son de la base local de Azopardo, medidos al auditar el panel contra la base.

1. **Los chips de la portada cuentan como texto escrito.** El chip lleva a `/chat?q=...` y el
   front lo dispara sin `tipo`, así que se guarda con `origen = 'escrito'` (15% de los
   mensajes). Infla palabras top, preguntas frecuentes e intención. Falta un `origen = 'chip'`,
   y para eso hay que separar "de dónde vino el mensaje" de "cómo responde el modelo": hoy el
   campo `tipo` hace las dos cosas y tocarlo cambia el prompt. Detalle en la skill `portada`.
2. **No se puede medir cuánto se queda la gente, y por eso se sacó la tarjeta.** La duración
   era `max - min` de los eventos, pero **no hay evento al salir**: el rato entre la última
   acción y el cierre de la pestaña se pierde siempre, y la visita de un solo evento mide 0 s.
   Medido: mediana **6 s** contra un promedio de **7 minutos** — el promedio no le pasaba a
   casi ninguna visita. Se descartó cruzarlo con `chat_conversations` (mismo `session_id`):
   probado, mueve 18 segundos y rescata una sola visita, porque el que chatea igual toca cosas.
   Para que vuelva: evento de salida (`pagehide`/`visibilitychange` con `sendBeacon`, el mismo
   mecanismo de `smTrack`) **y** mostrar la mediana, no el promedio.
3. **La sugerencia del embudo no dice el número real.** El texto es fijo ("menos de 5 de cada
   10") y sale igual sea 4,9 o 0,2. La métrica **sí es correcta** (de 19 visitas que vieron la
   pantalla inicial y no eligieron, 17 se fueron sin ver el menú), pero conviene decir "17 de
   32 personas" y nombrar la pantalla como la ve el cliente.
4. **Falta el widget de categorías del chat.** El dato ya está: los taps de categoría se
   guardan con `origen = 'categoria'` y el mensaje dice cuál. Es trabajo de panel, no de
   tracking.
5. **Reconocer al que vuelve en iPhone.** Safari (ITP) borra el localStorage a los **7 días**
   sin interacción, así que el visitante recurrente de iOS se cuenta como nuevo y la
   recurrencia queda subestimada. Se arregla mandando el id en una cookie del servidor
   (`Set-Cookie` con `Max-Age` largo), al estilo de `sv_vid` en `TrackSiteVisit`.
6. **Pantalla para configurar los chips de la portada** — ver la skill `portada`. "Atajos más
   usados" ya da el insumo para decidir cuáles dejar.

## Tests

`tests/Feature/AnalyticsTrackingTest.php`: endpoint (evento válido, whitelist, validación),
panel (binding por slug, scope, gating por feature, aislamiento entre comercios), visitas
(corte por inactividad, recurrencia, promedio por visita, eventos sin `session_id`) y las
unidades de medida: el desglose de `desde`/`label`, que el embudo exige `items > 0` para
cerrar, el micrófono por visita y que un indeciso con 5 clicks vale **una** elección.

`tests/Feature/DemandaInsatisfechaTest.php`: "Te lo pidieron y no lo tenés" solo cuenta lo que
el cliente **tipeó** — el "Ver más" reenvía la consulta y duplicaba el faltante.
