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
  | `categoria_tap` | `nombre` | `.cat-chip[data-categoria]` del panel de categorías del chat |
  | `tag_tap` | `nombre` | `.tag-chip[data-tag]` del panel "Filtrar por Tags" del chat |

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
- **Sugerencias que no tiran fruta (hecho).** `suggestions()` se rige por tres reglas, y
  conviene respetarlas al sumar una nueva:
  1. **Cada sugerencia dice su número.** "Varios clientes preguntan por delivery" se ignora;
     "6 personas preguntaron por envíos" se lee y el comercio juzga solo si le importa.
  2. **Con poco volumen no se habla** (`MIN_MENSAJES = 12`, `MIN_VISITAS = 15`, umbral de
     intención `max(4, 15%)`). El umbral viejo era 3 menciones: afirmaba "hay demanda de X"
     con tres consultas en un mes, que es ruido.
  3. **Ordenadas por lo que valen y cortadas en `MAX_SUGERENCIAS = 5`.** Primero lo que le
     piden y no tiene (la única que puede terminar en un plato nuevo en la carta, y que antes
     ni existía), después los problemas, al final la información que falta en el menú.

  Por eso `demandaInsatisfecha()` se calcula **antes** en el controller y se pasa como
  parámetro: es la primera sugerencia y así no se consulta dos veces.
  Cubierto por `tests/Feature/SugerenciasTest.php`.
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
- **"Qué categorías miran" (hecho).** El tap de categoría del chat manda `categoria_tap` con
  el nombre **original del menú**, no el traducido: con el `nombre_visible` la misma categoría
  vista en inglés habría aparecido como una segunda fila. Se descartó sacarlo del texto del
  mensaje (`"Mostrame TODAS las opciones de la categoría X"`, que ya se guarda con
  `origen = 'categoria'`): el parseo se rompe al cambiar la frase o el idioma. Se cuenta por
  **tap**, no por visita — volver dos veces a postres es justamente la señal.
- **"Qué etiquetas filtran" (hecho).** `tag_tap` desde el panel de tags del chat. Es lo único
  del panel que contesta **qué restricciones alimentarias tienen los clientes** del comercio, y
  antes se tiraba. Los nombres se normalizan al agrupar (`SIN_TACC`, `sin-tacc` y `sin tacc` son
  la misma fila) porque el menú los trae cargados de cualquier forma.
- **"Sin responder" ahora se entera de verdad (hecho).** `chatStream()` pasaba
  `used_openai = true` **hardcodeado**, así que la tarjeta marcaba 0 mientras OpenAI rechazaba
  todas las llamadas por falta de crédito — justo la métrica que se dejó en el panel porque
  "avisa cuando algo falla". Ahora va una bandera `$usoIA` que se apaga en el `catch` (solo si
  no llegó ningún token: un stream cortado a mitad igual respondió). Con el mismo flag,
  **`registrarUsoIA()` ya no corre cuando OpenAI falla**: el comercio perdía cuota de su plan
  por consultas que nunca se atendieron. Fijado en `tests/Feature/ChatbotCaidaTest.php`.
  El chat sin streaming ya lo hacía bien; el bug era solo del stream, que es el que usa el front.
- **Los atajos no se hacen pasar por clientes (hecho).** Hay cuatro botones que arman mensajes
  y los cuatro declaran su `origen`: `categoria`, `ver_mas`, `chip` (atajos de la portada y
  sugerencias de arranque del chat) y `tag` (filtros del chat). Todo lo que no sea `escrito`
  queda fuera de los widgets de TEXTO (`escritas()`), no de los de volumen. Antes eran 21 de
  103 mensajes contados como tipeados, y una sola frase de chip ocupaba **cuatro** casilleros
  del top de palabras ("promociones", "que promociones", "promociones tienen", "tienen hoy"
  eran el mismo click). Al sumar un botón nuevo: mandar `tipo` **y** agregarlo al `in:` de la
  validación de `ChatbotController`, o el chat responde 400.
- **"Te lo pidieron y no lo tenés" descarta lo que SÍ está en la carta (hecho).** El faltante
  lo declara el modelo y el modelo se equivoca: anotó "rabas" seis veces con las rabas a
  $26.000 en el menú. `demandaInsatisfecha()` pasa cada uno por `ChatbotService::tieneEnElMenu()`
  y lo que sí está va a `faltantesQueSiEstan()`, un bloque **solo para super admin**: no es
  demanda del cliente, es el asistente fallando. Sirve de termómetro — si esa línea crece,
  algo se rompió en el prompt o en el modelo.
- **"¿Para qué consultan?": faltaba la categoría más obvia (hecho).** El 84% caía en "Otras"
  porque no existía "Buscan algo para comer", que en un menú digital es la consulta principal.
  Se evalúa **al final** (si fuera primero, "¿tienen delivery?" caería en comida por el
  "tienen") y hay una última red: si el texto coincide con la carta (`tieneEnElMenu()`), es
  comida — mucha gente escribe sólo "rabas", sin verbo que reconocer. También se sumó el
  voseo, que faltaba: "recomendás" no matcheaba con "recomenda" por el word-boundary.
- **Los consejos saben qué tiene cargado el comercio (hecho).** "Cargá tu horario" se lo
  decíamos a comercios que ya lo tenían. Ahora cada mensaje tiene dos versiones y una condición
  (`! empty($c->horarios)`, `! empty($c->direccion)`): si el dato está, el consejo cambia de
  sentido (te lo siguen preguntando, no se ve). `intents()` devuelve además `sinResolver` por
  tema, y de ahí sale la mejor sugerencia que tenemos: *"de 6 consultas sobre dieta, en 5 no
  encontró nada"* señala una carta sin etiquetar mucho mejor que un porcentaje general.
- **Ayudas en dos niveles (hecho).** `x-ayuda` tiene `texto` (para el comercio: qué es y qué
  hacer, en criollo) y `admin` (cómo se calcula y qué límites tiene). Son dos lectores
  distintos: al comerciante le sirve "cuántos volvieron a mirar tu menú"; que el id viva en el
  localStorage y que Safari lo borre a los 7 días es problema nuestro.
- **"Preguntas más frecuentes" muestra sólo lo que se repite (hecho).** Con 68 preguntas
  distintas en 80 mensajes, el top se llenaba de consultas únicas: el comercio escribía algo
  nuevo, no lo veía y creía que el panel se lo comía. Ahora exige 2+ y avisa cuántas quedaron,
  con link al historial (`#historial`), que es donde se lee todo.
- **El historial muestra la fecha del ÚLTIMO mensaje** (se ordena por ahí) y **qué platos
  ofreció** cada respuesta (columna `platos_ofrecidos`): el marcador `[[PLATOS]]` se borra
  antes de guardar, así que sin eso no había forma de ver si el asistente recomendó cualquier
  cosa. Las conversaciones viejas quedan en NULL, ese dato no se puede reconstruir.
- **Los widgets vacíos NO se esconden.** Si un widget desaparece cuando no hay datos, el
  comercio nunca se entera de que la función existe — y "Atajos más usados" es justo el que le
  diría qué botones cambiar. Todos llevan su `@empty` con un mensaje, y hay un test que lo fija
  para un comercio sin un solo evento.
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

1. **No se puede medir cuánto se queda la gente, y por eso se sacó la tarjeta.** La duración
   era `max - min` de los eventos, pero **no hay evento al salir**: el rato entre la última
   acción y el cierre de la pestaña se pierde siempre, y la visita de un solo evento mide 0 s.
   Medido: mediana **6 s** contra un promedio de **7 minutos** — el promedio no le pasaba a
   casi ninguna visita. Se descartó cruzarlo con `chat_conversations` (mismo `session_id`):
   probado, mueve 18 segundos y rescata una sola visita, porque el que chatea igual toca cosas.
   Para que vuelva: evento de salida (`pagehide`/`visibilitychange` con `sendBeacon`, el mismo
   mecanismo de `smTrack`) **y** mostrar la mediana, no el promedio.
2. **Sugerencias con IA (idea aprobada, para más adelante).** Las reglas de `suggestions()`
   solo ven las métricas; no ven **el menú**. Una IA sí puede cruzarlos y decir lo que ninguna
   regla puede: *"te preguntaron 5 veces por opciones sin TACC y tenés 3 platos que lo son
   pero sin etiquetar"*. La infraestructura ya está (`IntentClassifierService`, `gpt-4o-mini`,
   job + registro de corridas, feature de plan).

   **La guarda que no hay que perder: la IA redacta y prioriza, no calcula.** Los números los
   sigue sacando PHP y se le pasan ya resueltos; si la dejamos contar, va a inventar cifras y
   platos que no existen — y una sugerencia con un número falso es peor que ninguna. Guardar
   el resultado como los `intents` (no generar en cada carga del panel) y mantener el piso de
   volumen: con 6 consultas en el mes no hay nada que sugerir, ni con reglas ni con IA.
3. **`demandaInsatisfecha()` no singulariza.** Agrupa sin acentos y en minúsculas, pero
   "colita de rana" y "colita de ranas" salen como dos líneas distintas (visto en datos
   reales). `getTopWords()` ya resuelve esto con `singularizar()`; falta aplicarlo también
   acá, palabra por palabra sobre la clave normalizada.
4. **Reconocer al que vuelve en iPhone.** Safari (ITP) borra el localStorage a los **7 días**
   sin interacción, así que el visitante recurrente de iOS se cuenta como nuevo y la
   recurrencia queda subestimada. Se arregla mandando el id en una cookie del servidor
   (`Set-Cookie` con `Max-Age` largo), al estilo de `sv_vid` en `TrackSiteVisit`.
5. **Pantalla para configurar los chips de la portada** — ver la skill `portada`. "Atajos más
   usados" ya da el insumo para decidir cuáles dejar.

> Sobre el pendiente 2: la clasificación con reglas ya mejoró bastante (28% en "Otras" contra
> el 84% de antes), así que la IA tiene menos para ganar de lo que parecía. Antes de gastar
> tokens, mirar qué queda realmente en "Otras".

## Tests

`tests/Feature/AnalyticsTrackingTest.php`: endpoint (evento válido, whitelist, validación),
panel (binding por slug, scope, gating por feature, aislamiento entre comercios), visitas
(corte por inactividad, recurrencia, promedio por visita, eventos sin `session_id`) y las
unidades de medida: el desglose de `desde`/`label`, que el embudo exige `items > 0` para
cerrar, el micrófono por visitante y que un indeciso con 5 clicks vale **una** elección.

También que un comercio **sin datos** vea los widgets vacíos (si desaparecen, nadie se entera
de que existen) y que las **sugerencias sean solo para super admin**.

`tests/Feature/DemandaInsatisfechaTest.php`: "Te lo pidieron y no lo tenés" solo cuenta lo que
el cliente **tipeó** — el "Ver más" reenvía la consulta y duplicaba el faltante — y descarta
lo que sí está en la carta, que va a `faltantesQueSiEstan()`.

`tests/Feature/SugerenciasTest.php`: que no tire fruta. Sobre todo lo que **no** tiene que
aparecer: una consulta suelta, poco volumen, más de cinco. Y que el consejo cambie según el
comercio ya tenga cargado el dato.

`tests/Feature/OrigenDeLosMensajesTest.php`: cada atajo responde 200 y guarda su `origen` —
el riesgo real era que la validación no aceptara el tipo y el chat dejara de responder.
