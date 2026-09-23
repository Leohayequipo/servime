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
- Plurales y acentos por raíz; `SINONIMOS` traduce jerga rioplatense (birra→cerveza,
  celíaco→tacc); `PALABRAS_VACIAS` saca muletillas **y adjetivos de opinión**.

> ⚠️ Los adjetivos de opinión están en `PALABRAS_VACIAS` por una razón concreta: los comercios
> les ponen esas palabras a los platos. Preguntando *"¿con cuál queda mejor?"* se buscaba
> "mejor", aparecía un desayuno llamado **"Des. El Mejor!"** y el asistente lo ofrecía para
> acompañar un lomito.

> ⚠️ El bloque dice explícitamente que es **referencia, no una lista para recitar**, y que
> ignore lo que claramente no venga al caso. Sin eso el modelo nombraba los seis lomitos que
> le pasábamos y al cliente le caían ocho tarjetas de golpe.

## Los marcadores son el carrito

La respuesta cierra con `[[PLATOS: Nombre exacto | Otro]]`, que **nunca ve el cliente**: de ahí
salen las tarjetas con foto, precio y botón "Lo quiero". Si el modelo se olvida del marcador,
el cliente lee un plato que no puede pedir. Es lo más frágil del sistema y lo primero a mirar
si se cambia de modelo o se reordena el prompt.

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
- **Respuestas breves.** Nunca le habíamos dicho que la app muestra abajo una tarjeta con foto,
  ingredientes y precio, así que repetía todo eso en la prosa. Ahora usa el texto para lo que
  la tarjeta no puede decir (por qué se lo recomienda). Igual se relaja en respuestas largas.

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

## Cuando NO hay IA contesta el recomendador

`RecomendadorService` calcula **una vez por carta** —al procesar el menú, vía
`GenerarRecomendacionesJob`— con qué va bien cada plato, y lo guarda en
`menus.recomendaciones`. Con eso, el chat sigue funcionando sin llamar a OpenAI:

- **Cuota agotada o asistente apagado**: las dos rutas del chat (`chat` y `chatStream`)
  llaman a `respuestaSinIA()`, que busca con `ChatbotService::buscarEnElMenu()` —la misma
  búsqueda en PHP que se le sirve al modelo— y suma los complementos ya calculados.
  Devuelve tarjetas con foto y precio, no consume cuota y se loguea `used_openai = false`.
  Antes tiraba un cartel fijo ("por el momento no está disponible") y en la ruta sin
  stream ni eso: la IA deshabilitada lanzaba una excepción y caía en el texto genérico.
- **Dos listas por plato, y la distinción importa**: `con` (se suma al pedido) y
  `parecidos` (alternativas). Quién va en cuál **lo decide la categoría de la carta, no el
  modelo**: otra hamburguesa bajo "Va bien con" se lee como "pedite dos".
- **`validar()` descarta lo que no existe en la carta.** Es lo que lo hace confiable: el
  modelo inventa nombres y acá no llegan a la mesa.
- **Un pedido genérico** ("algo para tomar", "un postre") se resuelve por el **rol** de la
  categoría (principal/guarnicion/bebida/postre), que se clasifica en una llamada aparte.
- Regenerar: `php artisan menu:recomendaciones {id}` o `--todos`. Hace falta cuando el
  comercio edita los platos: las recomendaciones apuntan a nombres de la carta.

**El modelo sale de `config('servime.modelo_recomendaciones')`, NO del plan**: corre una
vez por carta y lo pagamos igual para todos, así que va el que mejor la entienda.
Medido contra la carta de Azopardo (151 platos): `gpt-4o-mini` cubrió 123 con errores de
criterio; **`gpt-5.6-luna` cubrió 149**, en 90 s. Al cambiarlo, ojo con el body: los GPT-5
rechazan `temperature` y `max_tokens`, y por eso la llamada pasa por el trait
`ArmaPayloadOpenAI`. El techo va **alto (6000)**: en un modelo de razonamiento los tokens
que piensa salen de ahí, y si se queda corto vuelve sin JSON.

## Modelo: qué se probó y qué se descartó

| Opción | Banco | Dietas | Costo (1k charlas) | Veredicto |
|---|---|---|---|---|
| **gpt-5.6-luna** | ok | **3/3** | **$3,61 – $4,05** | **el elegido**; ya está en Premium (ago-2026) |
| gpt-4o-mini | 22/22 *(con la búsqueda)* | **0/3** | $4,15 – $5,25 | Básico y Pro; falla en dietas |
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
- **`chatStream()` es el que usa el front.** Pasaba `used_openai = true` hardcodeado, así que
  "Sin responder" del panel marcaba 0 con OpenAI caído, y `registrarUsoIA()` le comía la cuota
  del plan al comercio por consultas que nunca se atendieron. Va con la bandera `$usoIA`, que
  se apaga solo si **no llegó ningún token** (un stream cortado a mitad igual respondió).
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
