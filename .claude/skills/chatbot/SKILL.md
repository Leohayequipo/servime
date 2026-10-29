---
name: chatbot
description: El asistente del menú público — cómo se arma el prompt, la búsqueda del menú que hace PHP antes de llamar al modelo, los marcadores [[PLATOS]]/[[FALTA]] que generan las tarjetas, y qué modelo usar. Usar al tocar ChatbotService, el prompt, las respuestas del chat, el streaming, o al evaluar cambiar de modelo o de proveedor de IA.
---

# El asistente del menú — ServiME

Todo vive en **`App\Services\ChatbotService`** (~1.300 líneas). El controller
(`ChatbotController`) no arma prompt: llama al servicio y guarda la conversación.

| Qué | Dónde |
|---|---|
| Reglas fijas + el menú | `promptSistema()` |
| Lo que cambia en cada mensaje | `promptUsuario()` |
| Búsqueda del menú hecha con PHP | `platosQueCoinciden()` |
| Texto del menú para el prompt | `buildMenuContext()` · lista estructurada: `platosDelMenu()` |
| Lectura de `[[PLATOS]]` / `[[FALTA]]` | `separarMarcador()` |
| Orden de los mensajes que se mandan | `armarMensajes()` |
| Entradas | `processMessage()` (sin stream) y `streamMessage()` (la que usa el front) |

## Las dos reglas de oro

**1. El MENÚ va PRIMERO en el `system`, antes de las reglas.**

Con el menú al final, el asistente **negaba platos que tenía delante**: a "tienen rabas?"
contestaba que no, con `- Rabas — $26.000` en el contexto. No era el modelo — pasaba también
con gpt-4o, y mandándole el menú **solo**, sin reglas, lo encontraba y decía el precio. Lo
rompían 6.000 caracteres de reglas defensivas leídas antes del dato, entre ellas una que le
daba el molde armado para negar (*"decilo claro: ese plato no está disponible"*), hoy borrada.

Eso cuesta el prefijo compartido entre comercios para el prompt caching, y está bien pagado:
el menú es estable por comercio, así que cada uno sigue cacheando el suyo, que es lo grande.

**2. Lo fijo va en el `system`, lo variable en el `user`, y el `user` se mantiene CORTO.**

El `user` llegó a tener 780 tokens de checklist y reglas de formato idénticas siempre que,
por ir al final, quedaban fuera del prefijo cacheado y se pagaban enteras en cada consulta.
Hoy son ~73 (o ~223 si la búsqueda encontró platos). Al mover algo al `system`, **dejar en el
mensaje un recordatorio de una línea**: lo último que lee el modelo pesa más.

Medido en Azopardo: `system` ~4.800 tokens (de los cuales el menú son ~2.700), historial ~477.

## La búsqueda la hace PHP, no el modelo

`platosQueCoinciden($comercio, $mensaje, $max = 5)` busca las palabras del cliente en el menú
**antes** de llamar a OpenAI y se las pasa encontradas:

```
✅ BÚSQUEDA YA HECHA EN EL MENÚ — estos platos coinciden con lo que escribió:
- Rabas — $26.000
```

Existe porque **gpt-4o-mini no encuentra de forma confiable un plato suelto en un menú largo**
(limitación conocida del modelo; gpt-4o sí acierta). Con esto, mini pasó de 6/8 a **22/22** en
el banco, y eso es lo que hace viable el modelo 17 veces más barato.

- Busca en el **nombre y en la categoría**: el cliente pide "cerveza" y el plato se llama
  "Corona Sin Alcohol", dentro de "Cervezas 0.0%".
- **El nombre pesa más que la categoría.** Sin eso, ocho medialunas de "Panadería y tortas"
  tapaban las tortas de verdad.
- **Y busca en los tags** (oct-2026), con peso entre el nombre y la categoría: un plato con tag
  "veggie" que no lo decía en el nombre no aparecía preguntando por veggie. "veggie" también
  trae el tag `veg` (3 letras: sólo entra como sinónimo, exacto).
- Plurales y acentos por raíz; `SINONIMOS` traduce jerga rioplatense (birra→cerveza,
  celíaco→tacc); `PALABRAS_VACIAS` saca muletillas **y adjetivos de opinión**.
- Acepta el **arranque de una palabra** (`hamburg` → Hamburguesa) y **un error de tipeo, dos en
  palabras largas** (`hamburgesas`, `empandas`), y `SINONIMOS` tiene inglés básico (burger,
  fries, beer). Abajo de 5 letras exige exacto: una letra ya es otra palabra ("coca" y "boca").
  Con el modelo encendido esto casi no se nota —tiene la carta entera delante y resuelve el
  tipeo solo—; lo hizo necesario el **buscador de la carta**, que usa esta misma búsqueda sin
  modelo (`buscarEnElMenu()`).

> ⚠️ Los adjetivos de opinión están en `PALABRAS_VACIAS` por una razón concreta: los comercios
> les ponen esas palabras a los platos. Preguntando *"¿con cuál queda mejor?"* se buscaba
> "mejor", aparecía un desayuno llamado **"Des. El Mejor!"** y el asistente lo ofrecía para
> acompañar un lomito.

> ⚠️ El bloque dice explícitamente que es **referencia, no una lista para recitar**, y que
> ignore lo que claramente no venga al caso. Sin eso el modelo nombraba los seis lomitos que
> le pasábamos y al cliente le caían ocho tarjetas de golpe.

> ⚠️ **Los verbos también son muletillas.** "Ver más" vuelve a buscar con las palabras de la
> pregunta original: tras "quiero una pinta pero no sé con qué **combinar**" ofreció dos
> licuados "**Combina**do" (el error de tipeo tolerado los emparejaba). Por eso `combinar`,
> `maridar` y `pero` (que por el arranque traería una "Peroni") están en `PALABRAS_VACIAS`.
> `acompañar` **no**, a propósito: encuentra la categoría "Acompañamientos", que es lo buscado.
>
> Y en el "ver más" de un pedido de acompañamiento (`esPedidoDeAcompanamiento()`) no se pasa
> la lista: la búsqueda trae lo que el cliente **ya eligió** (la cerveza), y presentada como
> "ESTO ES TODO" el modelo la ofrecía como "la opción que faltaba".
>
> Y ojo: la lista se compara con la palabra **ya sin acentos** (`acompaña` llega como
> `acompana`). Una muletilla con tilde o ñ escrita así nunca filtra nada: va sin acento.

El chat la llama con tope **6**, lo máximo que muestra un pedido específico, y "ver más" con
25. Se probó con 8 para darle margen de elección y gpt-4o-mini metía las 8 en el marcador: lo
que recibe en esta lista lo recita entero. El `$max = 5` por defecto queda para el buscador.

## Los marcadores son el carrito

La respuesta cierra con `[[PLATOS: Nombre exacto | Otro]]`, que **nunca ve el cliente**: de ahí
salen las tarjetas con foto, precio y botón "Lo quiero". Si el modelo se olvida del marcador,
el cliente lee un plato que no puede pedir. Es lo más frágil del sistema y lo primero a mirar
si se cambia de modelo o se reordena el prompt.

Desde que el texto ya no enumera los platos, el plan B sin marcador (`detectarPorTexto`, que
busca nombres en la prosa) rescata como mucho **uno**: antes rescataba todos porque el texto
los nombraba. O sea que el marcador pesa todavía más que antes. En el banco de preguntas,
contar que aparezca en **cada** corrida.

`[[FALTA: sushi]]` es lo que pidió y no está: alimenta "Te lo pidieron y no lo tenés" del panel
(ver la skill `estadisticas`). Va declarado por el modelo y no inferido de "no recomendó nada",
porque el prompt le pide ofrecer alternativas y siempre termina recomendando algo.

**Preguntar por algo SÍ es demanda**, aunque no lo esté pidiendo formalmente: "¿tenés pollo
agridulce?" significa que lo esperaba encontrar. No filtrar eso.

**Pero el modelo declara faltantes que SÍ están en la carta** (anotó "rabas" seis veces
teniéndolas): el panel los descarta pasándolos por `tieneEnElMenu()` y los muestra aparte
como falla nuestra. O sea que `[[FALTA]]` fallando ya no ensucia al comercio — pero conviene
mirar ese bloque de admin, porque es el termómetro de si el prompt sigue funcionando.

Los platos declarados también se **guardan** (`chat_conversations.platos_ofrecidos`), porque
el marcador se borra de la respuesta antes de persistirla y sin eso el historial del panel no
permitía ver qué ofreció el asistente.

## Reglas de conducta que costaron encontrarse

- **Tiene permiso para decir que no.** Le pedíamos ser entusiasta y vender, nunca desaconsejar:
  a *"un lomito con café con leche es buena idea?"* respondía *"¡definitivamente!"*. Un mozo
  con criterio vende más a largo plazo que uno que aplaude todo.
- **El pedido es DE LA MESA, no de una persona.** Intentaba que lo nuevo combinara con lo que
  ya había en el carrito y salían engendros ("las empanadas acompañan tu café y tu cena"). Con
  una familia es peor: un café, un flan y una porción de queso son tres personas, no una mala
  combinación. **Nunca opinar sobre lo ya pedido.**
- **Si el menú no lo dice, no lo sabe.** Inventó que las empanadas eran "de tamaño individual"
  —y gpt-4o hacía lo mismo, o sea era del prompt—. Tamaño, porciones, cocción y **alérgenos**
  se contestan con "no figura en la carta, consultalo con el local". Improvisar un tamaño
  genera un reclamo en la mesa; improvisar un alérgeno es otra cosa.
- **El texto no es la lista (oct-2026).** Nunca le habíamos dicho que la app muestra abajo una
  tarjeta con foto, ingredientes y precio, así que repetía todo eso en la prosa. Y aun después
  de eso, el prompt le pedía nombrar cada plato **dentro** del texto (herencia de cuando las
  tarjetas salían de buscar nombres en la prosa): el cliente leía *"podés elegir Cafe con leche o
  la PROMO…"* y abajo veía las mismas dos tarjetas. Ahora el texto es una intro de 1 o 2 frases
  que nombra **como mucho un** plato (el que destaca, con el porqué) y la lista vive solo en el
  marcador.
- **Cuántos platos, según el pedido.** Antes era "2 o 3" para todo, y pedías café y salía uno.
  Ahora (bloque "CUÁNTOS PLATOS" del `system`): pedido específico → todos los que respondan,
  **hasta 6** (6 es también el máximo con el que el front muestra el botón "Ver más"); abierto o
  de grupo → 3 o 4; opinión → 1; "todos" → todos; saludo → ninguno. Las tarjetas no se
  achicaron: con 6 queda una columna larga, y fue a propósito.
- **"¿Con qué acompaño X?" no es una opinión.** Al principio caía en "opinión → 1" y a "quiero
  una pinta pero no sé con qué combinar" le daba una sola picada. Es un pedido de ideas: van
  **3 opciones distintas entre sí** (algo para picar, algo frito, algo contundente). La opinión
  de verdad ("¿la pinta va bien con papas?") sigue con 1.
- **El texto vende.** Con "1 o 2 frases, un solo plato" quedó correcto pero frío ("te recomiendo
  X, que es una buena opción"). Ahora: la frase del destacado **evoca** con lo que dice la carta
  ("una picada bien completa para ir picando entre trago y trago") sin listar ingredientes ni
  inventar; "la más pedida" **solo** para platos estrella; y cierra, si viene al caso, con **una
  pregunta** que empuja el siguiente pedido ("¿Le sumamos algo dulce para cerrar?"), respetando
  la REGLA #1 (en un pedido específico no ofrece otro tipo).

## Notas de voz (estilo WhatsApp, oct-2026)

El navegador **graba** (MediaRecorder) y el servidor **transcribe** (`TranscripcionService`,
`POST /api/chat/{slug}/transcribir`, modelo `services.openai.transcribe_model`, por defecto
`gpt-4o-mini-transcribe`). El texto vuelve al front y se manda como un mensaje normal con
`voz: true` (queda en `metadata.voz`; el `origen` sigue siendo `escrito` porque son palabras
del cliente). **La cuota se descuenta una vez, en la respuesta**: transcribir no cuenta.

- Antes era el dictado del navegador (Web Speech API): en Firefox, Samsung, Xiaomi y el
  navegador de Instagram **el botón ni aparecía**. Y si una vez negaban el permiso, un flag
  en localStorage lo escondía para siempre. No volver a eso.
- Se le pasa la carta como `prompt` para que escriba los platos como están ("Capresse").
- El audio **no se guarda**. En el historial vuelve como texto con 🎤.
- Gestos: mantener = graba, soltar = envía, deslizar a la izquierda = cancela, hacia arriba
  = traba (aparecen tacho y enviar). Toque corto = aviso de cómo se usa. Tope 30 s.
- Permiso: la primera vez sólo se pide (el cartel corta el "mantener") y se avisa que ya puede
  grabar. Negado: ningún navegador deja volver a preguntar, así que `#ayudaMic` explica cómo
  habilitarlo según el teléfono (Android, iPhone, navegador de una app).
- Probado con Chrome real y micrófono simulado (`--use-file-for-fake-audio-capture`).

## El asistente sabe qué hora es

`ahoraMismo()` inyecta en el bloque variable el día, la hora y —si el comercio cargó
horarios— si está **abierto o cerrado**, con la próxima apertura. Sin eso recomendaba una
cena a las 10 de la mañana y esquivaba las preguntas de horario **teniendo el dato cargado**,
porque no lo veía por ningún lado. `estaAbierto()` devuelve `null` cuando no hay horarios y
ahí no se dice nada: mejor callarse que afirmar que está abierto sin saberlo.

Va en el `user` y no en el `system` **a propósito**: cambia en cada request y rompería el
prefijo cacheado. El mensaje de respaldo (`generateFallbackResponse`) usa lo mismo, en vez de
mandar a "contactar al comercio" teniendo la información.

## Atajos de la interfaz: `tipo` y `scope`

El front puede mandar `tipo` = `categoria`, `ver_mas`, `chip` o `tag`, con `scope` (la
categoría, o la consulta original). Eso marca el `origen` en la conversación, para que los
widgets de texto del panel no cuenten esos mensajes como escritos por el cliente.

> ⚠️ **`tipo` hace dos cosas**: marca el origen Y cambia el prompt. Sólo `categoria` y
> `ver_mas` alteran cómo responde el modelo; `chip` y `tag` son inertes para el asistente y
> existen sólo para el panel. **Al sumar un valor nuevo hay que agregarlo al `in:` de las DOS
> validaciones de `ChatbotController`** (`chat` y `chatStream`), o el request falla y el chat
> deja de responder. Está cubierto en `tests/Feature/OrigenDeLosMensajesTest.php`.

**"Ver más"** devolvía lo mismo más uno, con el mismo párrafo de introducción: arrancaba de
cero y volvía a elegir los mejores. Ahora recibe la lista **completa** desde PHP (hasta 25,
buscando por `scope` y no por el texto sintético del botón), muestra solo lo que falta —también
en el marcador, si no le reaparecen las mismas tarjetas— y si no queda nada lo dice en una
frase y ofrece otra categoría real, en vez de dejar la charla muerta.

> El front ya muestra el botón **una sola vez por consulta** (`!esReconsulta` en
> `chat.blade.php`), así que no hace falta resolver el tercer "ver más" seguido: no existe.

## Dos cosas que se probaron y se descartaron (sep–oct 2026)

**1. "Chat sin IA".** Con cuota agotada o asistente apagado, que el chat contestara
buscando en la carta. Sin modelo, el chat entiende palabras y no frases, y un chat que no
entiende se siente roto: quema la imagen del asistente pago. Un buscador que no encuentra,
en cambio, es normal. Por eso sin IA el chat sigue con el cartel de "no disponible" y la
experiencia sin IA es el **buscador de la carta** (`BuscadorMenuController`, que usa
`buscarEnElMenu()`).

**2. Sugerencias "va bien con" precalculadas.** La IA leía la carta una vez y guardaba, por
plato, con qué acompañarlo y qué platos parecidos había; la carta las mostraba como chips.
Funcionaba (con `gpt-5.6-luna` cubría 149 de 151 platos de Azopardo; `gpt-4o-mini`, 123 y con
errores de criterio), pero se sacó entera antes de llegar a producción:

- **No había forma de saber si servían**: no se medía si alguien tocaba una sugerencia ni si
  la sumaba al pedido.
- **La calidad era correcta, no buena**: muchas eran "va bien con: Agua", que no vende nada.
  Hicieron falta reglas para que no ofreciera otra hamburguesa como acompañamiento de una
  hamburguesa, ni metiera papas fritas con un budín.
- **"Parecidos" repetía lo que ya estaba al lado**, en la misma categoría de la carta.
- **El comercio no podía corregirlas**, y la reacción natural era apagarlas todas.

Si se retoma, el código está en el historial de la rama `feat/recomendaciones-automaticas`
(`RecomendadorService`). Lo que vale rescatar: generar una vez por carta y no por consulta,
validar todo contra la carta real antes de guardarlo, y que la categoría (no el modelo)
decida qué es acompañamiento y qué es alternativa. Y medir antes de anunciarlo.

## Modelo: qué se probó y qué se descartó

| Opción | Banco | Dietas | Costo (1k charlas) | Veredicto |
|---|---|---|---|---|
| **gpt-5.6-luna** | ok | **3/3** | **$3,61 – $4,05** | **el elegido**; en producción lo usan **todos** los planes (confirmado oct-2026) |
| gpt-4o-mini | 22/22 *(con la búsqueda)* | **0/3** | $4,15 – $5,25 | ya no se usa en prod (sí en Básico y Pro de la base **local**); falla en dietas |

> Un cambio de prompt se valida con **luna**, que es lo que ve el cliente. En local, Básico y
> Pro siguen con `gpt-4o-mini`: probar con un comercio Premium (Azopardo local) o cambiar el
> plan en memoria. En oct-2026 se perdió media prueba ajustando el prompt para mini, que sigue
> las reglas de formato a medias (nombra varios platos en el texto aunque se le pida uno).
| gpt-5-nano | ok | 3/3 | $11,69 | razona 4.692 tokens: 3× más caro |
| gpt-5-mini | ok | 3/3 | $32,56 | razona 2.534 tokens: 8× |
| gpt-4.1 | ok | 3/3 | $40,22 | 9× |
| gpt-4o | 22/22 | **1/2** | $71,87 | 17× **y falla**: la peor opción |
| gemini-3.5-flash-lite | 7/8 | — | ~$2,28 | más rápido, no respeta bien marcadores |
| gemini-2.5-flash | 4/8 | — | ~$3,10 | se deprecia 16/10/2026 |
| Vector store (file_search) | 5/5 | — | más caro | **descartado** |

**"Dietas" es el banco que importa y no existía antes.** `gpt-4o-mini` le ofrece a un vegano
el "Sandw. Veggie" **describiendo el queso en la misma frase**: no es falta de dato —está
escrito en la descripción— es que no saca la conclusión. No lo arreglan ni una regla en el
prompt (0/3, y encima **rompió el caso sin TACC**: empezó a inventar "Empanada de J y Q en
versión sin TACC") ni etiquetar mejor los platos (A/B con tags explícitos: sin cambio).
Los modelos que razonan aciertan sin tocar nada.

**Los GPT-5 con razonamiento salen caros aunque el token valga menos**: nano cuesta 15× menos
por token que mini y gasta 45× más pensando. `luna` razona poco (383 tokens) y por eso gana.

**`luna` sale MÁS BARATO que el modelo actual** porque su input cacheado vale $0,02/1M contra
$0,075/1M, y **el 91% del consumo es caché** (medido). Cuanto más grande el menú, más rinde:
−13% con 151 platos, −23% con 291.

**Contra:** tarda ~1,6 s más en el primer token (razona antes). El tiempo total es igual.

- **Gemini cuenta ~7.000 tokens donde OpenAI cuenta 5.000** con el mismo texto, y no respeta
  bien los marcadores (2.5-flash falló 3 de 8): sin marcador no hay tarjetas ni carrito.
- **El vector store se descartó con datos**, no por prejuicio: el menú son 2.700 tokens
  estructurados en la base, muy por debajo del umbral donde RAG conviene. Consumía **8.530**
  tokens de entrada (más que mandar el menú entero), tardaba 7,1 s, obligaría a mantener y
  resincronizar un store por comercio, y sobre todo **rompe los nombres exactos**: el modelo
  leería el PDF en vez de la base y las tarjetas no matchearían. Sí serviría para lo que no
  está en ninguna tabla (historia del local, políticas, carta de vinos con notas de cata).

El modelo sale del plan (`Comercio::modeloIA()` / `maxTokensChat()`), se lee en cada consulta:
cambiarlo en `/admin/plans` pega desde el mensaje siguiente, sin limpiar caché.

### ⚠️ El body no es el mismo para todas las familias

Los **GPT-5 (y los `o*`) rechazan con HTTP 400 cuatro parámetros** que los GPT-4 aceptan:
`max_tokens` (piden `max_completion_tokens`), `temperature` distinta del default,
`presence_penalty` y `frequency_penalty`. Lo arma `payloadOpenAI()`, que decide por el nombre
del modelo (`esModeloDeRazonamiento()`).

**Sin eso, cambiar el modelo del plan deja el chat devolviendo 400 en cada consulta.** Al
migrar producción el orden es: **primero deployar el código, después tocar `/admin/plans`.**
Al revés, el comercio se queda sin chatbot sin que nadie haya tocado el repo.

Los tres parámetros que se pierden estaban para variar el vocabulario; en las pruebas contra
menús reales las respuestas siguieron saliendo bien sin ellos.

**El razonamiento sale del mismo tope que la respuesta.** `max_completion_tokens` cuenta lo
que el modelo piensa más lo que escribe. En oct-2026 luna razonó los 1.200 tokens de Premium
con "somos 4, qué pedimos?" y devolvió **una respuesta vacía, sin marcador**. Por eso
`ArmaPayloadOpenAI` le suma `RESERVA_RAZONAMIENTO` (1.500) al límite del plan en los modelos
que razonan: `max_tokens_chat` sigue midiendo la respuesta. Solo se cobra lo usado (mediana
~300 tokens), así que no encarece el caso normal. Medido: los pedidos de grupo pasan los 600
seguido, que es el tope que tenían Básico (600) y Pro (900) en la base local.

## Consumo: qué gastó cada consulta

Cada respuesta de OpenAI trae un `usage` que **antes se descartaba**. Ahora se guarda en
`chat_conversations.metadata` bajo la clave `consumo` (JSON, sin migración):

```json
"consumo": { "modelo": "...", "tokens_entrada": 7320, "tokens_cacheados": 6940,
             "tokens_salida": 237, "tokens_razonamiento": 159 }
```

- **En streaming hay que pedirlo**: `stream_options.include_usage`. Sin eso el stream no
  informa nada, y por ahí van casi todas las charlas reales.
- **`tokens_cacheados` es el único modo de saber si el prompt caching rinde en producción.**
  Verificado a mano: 2ª llamada seguida = 6.912 de 6.954 tokens cacheados (99,4%).
- **La cuota cuenta MENSAJES, no consumo**: un menú de 291 platos gasta el doble que uno de
  42 y descuenta lo mismo. Este dato es lo que permite ver esa diferencia.
- Se guardan **tokens, no pesos**: los precios cambian y un costo congelado envejece mal.
- **La base es MariaDB 10.4: no soporta `->>`.** Para consultar el JSON va
  `JSON_UNQUOTE(JSON_EXTRACT(metadata,'$.consumo.modelo'))`. Sin índice: con decenas de miles
  de filas habrá que pasarlo a columnas propias.

## Otros gotchas

- **Historial: 5 consultas** (`HISTORIAL_MENSAJES`). Son dos entradas cada una (pregunta +
  respuesta completa): con 10 medimos ~1.275 tokens extra en **cada** mensaje, con 5 son ~634.
- **El historial es de la VISITA, no del dispositivo** (`ChatConversation::deLaVisita()`,
  corte a las `HORAS_VISITA` = 3 sin escribir). El `session_id` vive en localStorage y no vence:
  antes el asistente recordaba lo de la semana pasada mientras la pantalla arrancaba vacía.
  Ahora la misma regla decide lo que recuerda el modelo y lo que el chat vuelve a dibujar al
  reabrirlo (`GET /api/chat/{slug}/historial`, sólo con `X-Session-ID`: adivinar por IP mostraría
  la charla de otro cliente del mismo wifi). La burbuja usa `metadata.etiqueta` (lo que mostró un
  botón) y no el texto interno; las tarjetas se rearman contra el menú actual.
- **La respuesta del historial lleva su `[[PLATOS]]` rearmado** desde `platos_ofrecidos`. Se
  guarda sin marcador y el texto ya no nombra los platos: sin rearmarlo, el modelo no sabía qué
  había mostrado y "Ver más" repitió las nueve tortas que acababa de dar (luna, 2 de 3 veces).
- **`chatStream()` es el que usa el front.** Pasaba `used_openai = true` hardcodeado, así que
  "Sin responder" del panel marcaba 0 con OpenAI caído, y `registrarUsoIA()` le comía la cuota
  del plan al comercio por consultas que nunca se atendieron. Va con la bandera `$usoIA`, que
  se apaga solo si **no llegó ningún token** (un stream cortado a mitad igual respondió).
  También se apaga si el modelo termina **bien pero sin texto** (un GPT-5 que se gastó el
  límite pensando: OpenAI no lo reporta como error). Las dos entradas (`chat` y `chatStream`)
  responden con `generateFallbackResponse()` en vez de una burbuja vacía, y no cobran cuota.
- **El idioma se resuelve por código y se dice explícito** (`SetLocale::NOMBRES`/`ANCLAJES`):
  como todo el prompt está en español, inferirlo funcionaba 1 de cada 4 veces. **En consola no
  corre el middleware** y contesta en inglés: al probar por tinker, `app()->setLocale('es')`.
- El contexto se recorta a 48.000 caracteres por seguridad (`trimContext`), pero un menú real
  ronda los 11.000: nunca se activa.

## Cómo probar un cambio de prompt

No hay tests automáticos de la calidad de las respuestas (dependen de OpenAI y la misma
pregunta puede dar distinto). Lo que funcionó: un **banco de preguntas de control** corrido
antes y después, con el comercio de prueba local. Cubrir:

- platos que **sí** están, con plural, nombre compuesto, jerga y por categoría;
- dos o tres que **no** están (tienen que declarar `[[FALTA]]`);
- un pedido por tipo ("algo dulce"), uno abierto ("qué me recomendás") y una **pregunta de
  opinión** ("¿con cuál queda mejor?", que no debe traer platos);
- **dieta y alergia** ("soy vegano", "¿tienen sin TACC?"): que no cruce las dos ni prometa
  lo que la carta no dice.

Verificar siempre que **el marcador salga**, no solo que el texto se lea bien.

**Corré cada caso 3 veces.** Una sola corrida no alcanza para nada: `gpt-4o` acertó el caso
del vegano en la primera y falló en la segunda, y estuve por recomendarlo con esa muestra.

**Y usá preguntas reales de `chat_conversations`, no inventadas.** Los clientes escriben 41
caracteres de promedio ("algo dulce", "avocados"); un banco de preguntas bien redactadas mide
otra cosa. Con las reales medí que `luna` gasta 285 tokens de salida, no los 383 que daba el
caso de laboratorio — y de ahí salió que era más barato, no más caro.

## Tests

| Archivo | Qué fija |
|---|---|
| `tests/Feature/BusquedaEnMenuTest.php` (9) | la búsqueda de PHP: nombre, plural, categoría, jerga, que las muletillas no traigan medio menú, que "mejor" no traiga el desayuno, prioridad del nombre sobre la categoría |
| `tests/Feature/ChatbotContextTest.php` | el carrito entra al prompt, y un plato del carrito no vuelve a salir como tarjeta |
| `tests/Feature/ChatbotCaidaTest.php` (2) | con OpenAI caído la consulta queda como `used_openai = false` y no consume cuota |
| `tests/Feature/ChatbotConfigTest.php` (6) | configuración del asistente por comercio; que `veganas` y `sin_tacc` sean focos distintos y no se crucen |
| `tests/Feature/ModeloOpenAiPayloadTest.php` (5) | qué parámetros lleva el body según la familia del modelo |
| `tests/Feature/ConsumoDelModeloTest.php` (4) | que el `usage` se capture y que el stream lo pida |
