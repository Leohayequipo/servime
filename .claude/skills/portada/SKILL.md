---
name: portada
description: Portada del comercio y tema derivado del menú público — la foto que sube el comercio genera fondo, hero, miniatura y paleta (BrandThemeService). Incluye la URL pública del menú y sus alias. Usar al tocar la pantalla de elección del menú, la subida de imágenes del comercio, los colores del menú público, o el slug/URL de un comercio.
---

# Portada y tema de marca — ServiME

El menú público **no se pinta con colores elegidos por nosotros**: se pinta con los de la foto
que el comercio subió para representarse. De una sola imagen salen el fondo, el hero, la
miniatura y toda la paleta.

## Modelo de datos

**`comercios`** (migración `2026_08_08_120000_add_portada_to_comercios_table`):

| Campo | Para qué |
|---|---|
| `portada_url` | el original, **tal cual lo subieron** (no se recorta) |
| `portada_fondo_url` | desenfoque generado (~5 KB) |
| `portada_hero_url` | recorte nítido 16:11 a 780px (15–60 KB) |
| `portada_thumb_url` | cuadrado 180px para la tarjeta del menú |
| `tema_derivado` (json) | base, velo, superficies, acento sugerido, contraste, medidas, `baja_calidad`, hash |
| `portada_estado` | `vacio` / `procesando` / `listo` / `error` |

Más `tagline` (frase corta, migración `..._130000`) y la feature de plan **`customizacion`**.

**Va aparte de `logo_url` a propósito.** Antes había un solo campo de imagen y todo pasaba por
`ComercioController::saveSquareLogo()`, que recorta a 512×512: una foto del local llegaba
cuadrada y recortada. Son dos cosas distintas — el logo es el isotipo, la portada es una foto.

## Qué se le hace a la imagen (`App\Services\BrandThemeService`)

**1 · Fondo desenfocado.** Se achica a **40px de ancho** — ese achicado *es* el desenfoque, cada
píxel promedia un cuadrado enorme del original. Después se agranda en pasos (80 → 160 → 320 →
560) con **7 gaussianos en cada escalón** para borrar las costuras de la interpolación.

> No simplificar a "achicar y estirar de un salto": el escalado bilineal de 10x deja rampas
> rectas y rombos, se ve pixelado. Los pasos intermedios son el punto.

Al final se corrige el brillo contra un objetivo fijo (`FONDO_LUM_OBJETIVO = 78`). Una foto
nocturna se aclara, una sobreexpuesta se oscurece: todas caen en el mismo tono.

**2 · Hero a 780px** y **3 · Miniatura a 220px**, las dos **enteras, sin recortar para
encuadrar** (`paraEncuadrar()`). Sólo se recortan si la foto es más alta que 4:3, y ahí es para
no mandar píxeles que ninguna pantalla va a mostrar.

## El encuadre lo hace el CSS, no el servidor

`portada_focal_x` / `portada_focal_y` (0–100, migración `2026_08_09_130000`) van directo a
`object-position` vía `Comercio::focoPortada()`. Tres cosas que esto compra:

- **Moverlo no regenera nada** — son dos números, no pasa por la cola. Hay un test que lo fija.
- **Se adapta a cada ancho de pantalla** en vez de quedar congelado en un recorte.
- **Un solo punto sirve para el hero y para la miniatura.**

> No volver a recortar el hero en el servidor "para que pese menos". Antes salía de un
> `$cy = 0.38` hardcodeado elegido a ojo, y encima el navegador recortaba otra vez por su cuenta
> (`object-fit: cover` en una caja 1.58:1 contra un recorte 1.45:1): dos recortes encadenados y
> el comercio no controlaba ninguno. El hero pasó de ~51 KB a ~95 KB y es el precio de eso.

En el panel: vista previa con la proporción real del celular + la foto entera con un marcador
que se clickea o arrastra (`pointerdown/move`, anda con mouse y con dedo). Se persiste al
**soltar**, no en cada `pointermove`: arrastrar dispara decenas de eventos por segundo.

## Dónde se configura: en la pantalla del MENÚ, no en la del comercio

`menus/create.blade.php` (la pantalla grande de gestión del menú, que se llama `create` pero
también edita), tarjeta **`#bloque-portada`**. Ahí están la foto, el encuadre, el tagline, la
paleta detectada y el botón del acento sugerido.

**No está en `comercios/edit`**, que es donde vivía hasta sep-2026. Los campos siguen siendo de
`comercios` —igual que `primary_color`, `chips` y las reseñas, que ya se editaban desde acá—,
pero lo que se configura es *la pantalla que el cliente ve al escanear el QR*, así que va con el
resto de eso. Repartido en dos pantallas, dejar una sola cosa lista obligaba a ir y volver: el
caso testigo era el interruptor de reseñas, que mostraba un cartel mandando a «los datos del
comercio» a buscar el link que lo habilita.

Guarda por **`POST /comercios/{slug}/portada`** (`ComercioController::portadaGuardar`,
`comercios.portada.guardar`, throttle 30/min), no con el formulario de la pantalla. Es **parcial
a propósito**, al revés que `update()`: cada campo se escribe sólo si vino, porque cada pedazo
tiene su propio botón. El endpoint vive en `ComercioController` y no en `MenuController` porque
toda la maquinaria de la portada ya está ahí, igual que `portadaEstado` y `portadaReintentar`.

> ⚠️ **Al sacar un campo del formulario del comercio, revisar `update()`.** Sus campos se
> escriben con `$validated['x'] ?? null`, así que un campo que el form ya no manda se **borra
> solo** en cada guardado. Le pasó a `tagline` al mudarlo; ahora va con guarda `$request->has()`,
> como `google_review_url` y `pedido_habilitado`, que ya habían tropezado con lo mismo. Hay un
> test que lo fija.

**4 · Paleta:**
- **Base** = promedio de *todos* los píxeles, con el **tono intacto** (es lo único que el
  promedio conserva de la marca), el croma acotado a [0.16, 0.30] (promediar arrastra al gris)
  y la luminancia llevada a `BASE_LUM_OBJETIVO = 74` (el brillo del promedio dice a qué hora
  sacaron la foto, no cómo es la marca).
- **Velo** = un solo color en tres pasos (100%, 84%, 65%), sobre el fondo desenfocado.
- **Superficies** = versión oscurecida de la base + filo claro arriba. **No usar blanco
  translúcido fijo**: sobre un fondo medio, sumar 4% de blanco no se ve.
- **Acento sugerido** = color saturado más votado (ignorando negros, blancos y grises),
  normalizado en HSL y aclarado de a 1% hasta pasar **4.5:1 de contraste WCAG** contra la base.

## El acento es una SUGERENCIA, no se aplica solo

La extracción por frecuencia **devuelve la comida y la madera, no la marca**: sobre una fachada
gana la vegetación, sobre una carta llena de fotos de pizza gana el marrón. Verificado con tres
fuentes distintas — las tres dieron marrón crudo.

Por eso `primary_color` (lo que eligió el comerciante, ver `Comercio::colorAcento()`) sigue
mandando, y el panel sólo ofrece un botón *"Usar #XXXXXX como acento"*.

## Dónde se procesa: en caliente o en cola

`ComercioController::generarTemaDePortada()` decide por tamaño:

- **≤ 14 MP → en el request.** La cola es la pieza más frágil (worker por cron en el server);
  cuanto menos dependa el caso común, mejor. El corte es 14 y no 12 porque la foto estándar de
  iPhone es 4032×3024 = **12,2 MP** y con 12 quedaba justo afuera.
- **> 14 MP → `GenerateBrandThemeJob`**, con la barra de progreso del panel.
- Si falla en caliente, cae a la cola (puede haber sido algo puntual del request).

Medido en local: costo fijo ~1,1 s + ~0,18 s por megapíxel.
`1,9 MP → 1,4 s` · `7,7 MP → 2,4 s` · `12,2 MP → 3,2 s`

## Guardas que NO hay que sacar

**Memoria.** GD descomprime a bitmap: la memoria es `ancho × alto × 4 bytes`, **el peso del
archivo no dice nada**. Un JPEG de **1 MB** en 8000×6000 son 192 MB en RAM y tira un *fatal
error inatrapable* que mata el proceso. Por eso `verificarQueEntraEnMemoria()` corta con
`getimagesize()` (que no descomprime) antes de cargar, y la validación tiene
`dimensions:max_width=8000,max_height=8000`.

**Rotación EXIF.** GD no la lee: sin `imagerotate()` en `cargar()`, las fotos verticales de
celular salen acostadas. Requiere `ext-exif` (declarada en `composer.json`).

**Sin mínimo de dimensiones.** Una foto chica se procesa igual y se marca
`tema_derivado.baja_calidad`; el panel avisa pero **nunca bloquea**. Rechazarla dejaba al
comercio sin portada.

## La vista pública

`PublicMenuController::choose()` elige según `Comercio::tienePortada()`:
- con portada → `menu.portada` (diseño derivado)
- sin portada → `menu.choose` (el anterior) — **el fallback no se saca**, hay comercios sin foto

`menu/portada.blade.php` no tiene un solo color escrito: todo sale de `tema_derivado`.

## Los chips de la portada

Los tres botones de abajo del hero (✨ ¿Qué me recomendás? · 🔥 Ver promos · 🌾 Sin TACC) los
arma **`Comercio::getChipsSugeridos($max = 3)`** a partir del **menú real**, y los pasa a las dos
vistas desde `PublicMenuController::choose()`. No hay nada hardcodeado por comercio ni
configurable: la lista de candidatos se arma en este orden y se cortan los 3 primeros.

| # | Paso | Cuándo entra |
|---|---|---|
| 1 | ✨ Recomendación | siempre |
| 2 | 🔥 Ver promos | hay una categoría con `es_promocion` |
| 3 | Tags dietéticos | el tag normalizado es **exactamente** `SIN_TACC`, `VEGANO`, `VEGETARIANO`, `SIN_LACTOSA` o `SIN_AZUCAR` |
| 4 | Categorías del menú | una por categoría, en el orden del menú |
| 5 | 🍽️ ¿Qué tienen? | fallback |

Cada chip no filtra: **manda una pregunta al chat** (`/chat?q=...`), y el front la dispara como
un mensaje normal.

> ⚠️ **De qué menú salen, cuando hay varios.** Un comercio puede tener N menús pero sólo uno
> **activo** (el primero nace activo, los demás no; `activar()` desactiva al resto). La regla
> para resolver cuál se muestra es **`Comercio::menuVigente()`**: el activo, o **el último
> procesado si ninguno quedó activo**.
>
> Ese plan B estaba copiado en cuatro lugares y a uno le faltaba: `getCategoriasDelMenu()` y
> `getTagsDelMenu()` exigían `activo` y devolvían vacío. Se veía como que la carta y el chat
> funcionaban bien pero los chips caían al genérico 🍽️ «¿Qué tienen?», sin nada en el log.
> Ahora la regla vive en un solo método (`tests/Feature/MenuVigenteTest.php`).
>
> El desempate es `created_at` y después **`id`**: dos menús subidos en el mismo segundo
> comparten `created_at` y con sólo `latest()` el "último" podía ser el anterior.
>
> `ChatbotService::menusParaContexto()` sigue con su propia versión, porque devuelve una
> colección y además filtra por `contexto_gpt`. Tiene el mismo plan B.

Dos cosas medidas en Azopardo (26 candidatos, 22 de ellos categorías):

- **Las categorías nunca entran** si el comercio tiene promos y un tag: los 3 lugares se llenan
  con los pasos 1–3. Y si entraran, ganaría la primera del PDF, no la más pedida.
- **El match de tags es exacto y se pierde lo obvio**: un menú con `veg` y `veggie` no dispara
  🥗 Vegetarianas, porque la regla busca `VEGETARIANO`.

**Ojo con `.dot-open`**: el punto de "abierto ahora" va en verde **por semántica**, no con el
acento. Si tomara el color de marca, un comercio con acento rojo tendría un punto rojo diciendo
que está abierto. (El `choose.blade.php` viejo todavía tiene ese bug: `background: var(--accent)`
con la animación en verde hardcodeado.)

## Guía de bienvenida (tour)

`menu/partials/guia.blade.php`, incluida en `portada` y `chat` (no en `choose`, la portada
sin foto, ni en la carta clásica). Es un tour con **Driver.js 1.3.1 por CDN**, que se descarga
sólo cuando hay que mostrarlo. La prende o apaga cada comercio en el editor ("Tu asistente" →
"Guía para tus clientes", campo `comercios.guia_clientes`, **arranca prendida**).

- **Una vez por teléfono y por pantalla, para todos los comercios** (`servime_guia_{pantalla}_v1`
  en localStorage, sin id de comercio: la app es la misma). Se marca **al mostrarse**, no al
  terminarse. Subir a `_v2` la vuelve a mostrar a todos.
- En el chat no sale si llegó con `?q` (`window.servimeLlegoConPregunta`) ni si ya charló en la
  visita (espera a `historialListo`). Sin localStorage, no sale nunca.
- `?guia=1` la fuerza sin marcar: lo usan "Ver en la portada / en el chat" del editor, y por
  eso anda apagada y dentro de la vista previa. La vista previa normal **no** la muestra.
- El "?" de la cabecera del chat (`#guiaAyuda`) la repite.
- Eventos: `guia_vista`, `guia_completa`, `guia_cerrada` (con el paso en que la cerró).
  Los repasos con "?" no cuentan como completa/cerrada.

## Abierto / cerrado

`Comercio::estaAbierto()` sale de **`horarios`** (json, migración `2026_08_09_120000`):
`{ "1": [["11:00","20:00"]], ... }` — clave = día ISO (1 = lunes), lista de turnos por día,
lista vacía o ausente = cerrado.

**Devuelve `null` cuando no hay horarios cargados, y la vista no muestra la píldora.** No es lo
mismo "cerrado" que "no sé": antes era un `ABIERTO AHORA` fijo que mentía a cualquier hora.

- **Cruza medianoche**: si el cierre es ≤ la apertura, el turno sigue al día siguiente. Por eso
  `estaAbierto()` mira **hoy y ayer** — a la 1 AM del martes sigue corriendo el turno del lunes.
- **Turnos partidos**: hasta 2 por día (mediodía y noche).
- `proximaApertura()` devuelve `"11:00"` (mismo día) o `"Lunes 11:00"` (otro día).

El campo viejo **`horario` (texto libre) se mantiene** y es sólo para mostrar. No se parsea: los
datos reales tenían de todo — `"de 19 a 2AM"`, `"12  a 23"`, letras Unicode de Instagram, emojis.
Un parser que acierte el 80% le dice al 20% restante que el local está abierto cuando está
cerrado, que es peor que no decir nada.

En el panel, `armarHorarios()` **descarta los turnos incompletos** en vez de rechazar el
formulario: quien cargó sólo la apertura no tiene por qué perder el resto de los cambios.

> Pendiente: el chatbot todavía deflecta las preguntas de horario
> (`ChatbotController:252`) aunque el dato ya exista.

## ⚠️ Guardar NO es publicar (Hostinger)

Son **dos lugares**: Laravel guarda en `storage/app/public`, pero el servidor web sirve desde
`public_html/storage`, que es un **directorio con copias, no un symlink** — el symlink se probó
en Hostinger y falló, no volver a proponerlo. Una imagen existe para el navegador sólo si está
en el segundo.

**Incidente 15/08/2026.** La portada era la única imagen sin copia propia al subirse (el logo,
las fotos de platos y el archivo del menú ya la tenían). Llegaba a `public_html` sólo por el
`cp -r` masivo del paso 8 del deploy, que además hace `rm -rf` antes. Esa copia salió
incompleta, se tragó el error con `2>/dev/null`, y desapareció: quedó `comercios/14/` en pie y
`comercios/14/portada/` no. Se veía como *"no puedo ver la portada **ni moverla**"* — sin foto
no hay nada sobre lo que arrastrar el marcador de encuadre.

Ahora se publica al generar el tema, con **`App\Support\PublicHtml::publicarVarias()`** y los
**cuatro** archivos (`Comercio::archivosPortada()`), el original incluido: ese es el que el
panel muestra para elegir el encuadre.

**Va en los DOS caminos y tiene que seguir así**: `ComercioController::generarTemaDePortada()`
y `GenerateBrandThemeJob::handle()`. Sólo en el controlador, las fotos de más de 14 MP —las que
caen en la cola— seguirían sin verse.

- La ruta de Hostinger sale de **`config('servime.public_html')`** (antes estaba hardcodeada en
  seis funciones). En local no existe y todo es un no-op silencioso.
- Para las portadas ya subidas: **`php artisan portadas:publicar`**. Distingue "no se pudo
  copiar" de "el archivo no está en storage".
- **Sigue pendiente**: el paso 8 del deploy borra y vuelve a copiar en cada corrida, así que una
  copia a medias deja el sitio sin imágenes otra vez. Sin symlink, la salida es que **no borre**
  (`cp -ru` encima en vez de `rm -rf` primero).
- Las otras seis funciones de copia siguen sin unificarse con el helper nuevo. Andan.

## Seguimiento del procesado

`GET /comercios/{slug}/portada/estado` y `POST .../portada/reintentar` (throttle 10,1), las dos
con chequeo de propiedad (`autorizarComercio()`). El panel consulta cada 2 s y a los **30 s**
deja de prometer que ya viene y ofrece reintentar — casi siempre significa que **el worker no
está corriendo**.

> En local: `composer dev` levanta server + `queue:listen` + logs + vite. Con sólo
> `php artisan serve` la cola no corre y los jobs quedan esperando para siempre.

---

# URL pública del menú (slug)

El slug es `/menu/{slug}` y **está impreso en los QR de las mesas**.

- **Se genera al crear** con `Comercio::generarSlugUnico()`: sufijo `-2`, `-3`… si está ocupado.
  Dos comercios pueden llamarse igual y ser distintos, así que el nombre repetido no se rechaza.
- **Al renombrar se actualiza**, y el anterior queda archivado en `comercio_slugs`.
- **`Comercio::resolveRouteBinding()` resuelve también por los alias**, así que un QR impreso
  antes del renombrado sigue entrando. Va en el modelo y no en cada ruta porque hay **41 rutas**
  con `{comercio:slug}`.
- **`slug_manual`**: si el super admin fija la URL a mano, un renombrado del comerciante ya no
  se la pisa.
- **No se puede tomar un slug que otro comercio usó**, ni siquiera uno viejo: mandaría los QR
  del primero al menú del segundo, que es peor que un 404.

Sirve el menú directamente (200), **no redirige con 301**. Las vistas emiten `<link rel=canonical>`
apuntando a la URL actual.

## Dependencias

Declaradas en `composer.json` como **`ext-gd`** y **`ext-exif`**. No estaban antes: sin
declararlas, un server sin GD pasa el `composer install` sin decir nada y la feature revienta
recién cuando un comercio sube una foto, con un error críptico en el log. `ext-exif` es la que
endereza las fotos verticales de celular (el código tiene guarda, pero sin ella salen acostadas).

Todo el procesado es **GD pelado**. Dos cosas para no perder tiempo redescubriéndolas:

- **`intervention/image` está en las dependencias pero no se usa en ningún lado** del proyecto.
  Se dejó "por las dudas". Envuelve GD igual, y las dos cosas raras que hace este servicio —la
  pirámide de desenfoque y el conteo de colores píxel por píxel— no las resuelve mejor.
- **Imagick no está instalado** (aparece la advertencia `Unable to load dynamic library 'imagick'`
  en cada comando). Tiene desenfoque gaussiano de radio configurable y lee HEIC; si algún día
  se instala, la pirámide de `desenfocar()` se puede reemplazar por una sola llamada.

**HEIC** (formato por defecto de iPhone) **no se puede abrir con GD**: va a llegar y hoy termina
en `portada_estado = 'error'` con un mensaje genérico.

## Tests

| Archivo | Qué fija |
|---|---|
| `tests/Feature/PortadaTest.php` (6) | estado consultable sólo por el dueño, reintento encola, **mover el punto focal NO encola nada** |
| `tests/Feature/HorariosTest.php` (6) | sin horarios devuelve `null`, cruce de medianoche, turno partido, próxima apertura |
| `tests/Feature/ComercioSlugTest.php` (7) | nombre repetido no rompe, renombrar archiva el slug, la URL vieja sigue entrando |

## Las imágenes de PLATOS y PROMOS: ya se procesan (`ImagenMenuService`)

> Esta sección decía lo contrario hasta ago-2026. **Ya no**: se achican y se convierten a
> webp al subirse. Se deja el antes/después porque el ahorro justifica no volver atrás.

Antes se guardaba el archivo tal cual. Medido en la base: fotos de **3120×4160 y ~1 MB** para
una tarjeta que el menú público muestra a 448 px. El cliente en la mesa se bajaba un mega por
foto, con datos móviles.

`App\Services\ImagenMenuService::procesar()` hace, con GD nativo:

1. Carga (y endereza por EXIF, si no las verticales de celular salen acostadas).
2. Si el lado mayor pasa **`LADO_MAYOR = 1200`**, reescala proporcionalmente. **No recorta**:
   el recorte cuadrado de la tarjeta lo hace el CSS.
3. Guarda **webp `CALIDAD = 85`** con nombre aleatorio de 40 caracteres.

Resultado sobre una foto real de 1.024 KB: **~112 KB, un 89% menos.**

**El original NO se conserva**: queda sólo el webp de 1200 px. Por eso 1200 y no 800 — la
tarjeta pide ~900 px reales en retina y así hay margen para agrandarla sin reprocesar; la
diferencia son 50 KB. Si se necesita más resolución, no hay de dónde sacarla.

**Si GD falla** (formato que no abre, sin soporte webp, imagen que no entra en memoria) guarda
el original tal cual en vez de tirar error: que el comercio pueda subir su foto importa más que
ahorrar kilobytes.

| Sube | Límite | Formatos | ¿Procesa? |
|---|---|---|---|
| plato / promo | **10 MB** | jpeg png jpg gif **webp** | **sí** (1200 px + webp) |
| logo | 2 MB | jpeg png jpg gif | sí (cuadrado fijo) |
| portada | 8 MB | cualquier imagen | sí (fondo/hero/thumb webp) |
| menú (PDF o foto) | 10 MB | pdf jpeg png jpg gif | — |

El tope de plato/promo sale de `ImagenMenuService::reglaDeValidacion()`, no escrito a mano en
el controlador. Pudo subir de 2 a 10 MB justamente porque lo que se guarda queda chico igual:
con 2 MB una foto de celular actual no entraba.

**Queda sin unificar el logo**, que sigue en 2 MB, sin webp y con su propio recorte cuadrado.

## ⚠️ La ruta de la imagen queda con el dominio pegado si pasa por Excel

`MenuExport` escribe la columna «URL Imagen» con `asset(...)`, o sea la URL **absoluta**
(`https://servime.net/storage/...`). `MenuImport` la guarda tal cual. Después de un ciclo
exportar→importar, el JSON del menú tiene URLs absolutas en vez de las relativas
(`/storage/...`) que escribe la subida normal.

Hoy **no rompe** —`asset()` y `ChatbotService::resolverImagenUrl()` detectan la URL absoluta y
la devuelven sin tocar—, pero: bajar esa base a local deja las imágenes apuntando a producción,
y un cambio de dominio obliga a reescribir el JSON de todos los menús importados.

**Pendiente (una línea):** que `MenuImport` normalice a ruta relativa al leer la columna.

Nota relacionada: borrar un menú es **baja lógica y no toca archivos** (comentario explícito en
`MenuController::destroy`). Si se exporta, se borra el menú y se importa uno nuevo, el menú
nuevo apunta **a los mismos archivos**: no se duplica nada y no se pierde nada. Pero quedan dos
menús sobre el mismo archivo, así que reemplazar esa foto en el menú nuevo deja al viejo con la
referencia rota — invisible mientras esté dado de baja, visible si alguien lo **restaura**.

## Imágenes huérfanas: borrar un comercio deja los archivos

`ComercioController::destroy()` borra el logo (`deleteLogoSafely`) y los registros de la base,
pero **nunca toca `menu-items/`**. Los archivos de los platos quedan en el disco para siempre,
sin nada que los referencie.

Medido: **34 archivos, 9,3 MB de tres comercios borrados (31, 32, 33) — el 98% de todo lo que
ocupan las imágenes**. Las válidas eran 4 archivos, 0,18 MB.

Lo que **sí** está sano (verificado uno por uno contra la base y el disco):

- borrar la imagen de un plato → borra archivo y referencia;
- borrar la de una promo → idem, y aborta si `imagen_path` no coincide con la de la promo;
- **reemplazar** una imagen → borra la anterior (probado subiendo dos al mismo plato).

O sea que el uso diario no genera basura: el único agujero es el borrado de comercio. Falta
(a) que `destroy()` borre la carpeta del comercio y (b) un comando para limpiar los 9 MB que
ya están, con `--force` para que no borre nada sin pedirlo.

## Pendientes conocidos

- **Pantalla para elegir los chips de la portada** (idea aprobada, sin implementar). Hoy son
  100% automáticos y el comercio no controla ni cuáles de sus categorías aparecen. La forma
  pensada: un campo json `chips_portada` en `comercios` + un bloque en editar comercio para
  marcar hasta 3 de los sugeridos, con `getChipsSugeridos()` como fallback si no eligió nada.
  Sin decidir: si se permiten chips escritos a mano (riesgo: prometer algo que el menú no tiene)
  y si es feature de plan. Resuelve de raíz los dos defectos de la regla automática.
- ~~Los chips ensucian las estadísticas de texto~~ **(resuelto).** El chip lleva a
  `/chat?q=...` y se disparaba sin `tipo`, así que la conversación quedaba como tipeada por el
  cliente (8 de 54 mensajes). Ahora manda `tipo = 'chip'` y los widgets de texto lo ignoran.
  Ojo al sumar un atajo nuevo: el valor tiene que estar en el `in:` de las dos validaciones de
  `ChatbotController`, o el chat responde 400. Ver la skill `chatbot`.
- ~~El chatbot no usa los horarios~~ **(resuelto).** `ChatbotService::ahoraMismo()` le pasa el
  día, la hora y si el local está abierto, y el mensaje de respaldo también los usa.
- **No hay aviso de cola frenada**: si el worker está caído, se descubre a mano.
- El **cron del worker en producción no está en el repo**, vive sólo en el hPanel de Hostinger.
- `store()` no maneja portada: se configura después de crear, desde la pantalla del menú
  (decisión, no olvido). No podría manejarla aunque se quisiera: el archivo se guarda en
  `comercios/{id}/portada`, el encuadre se elige sobre la imagen **ya procesada** y el polling
  de estado necesita el slug. Nada de eso existe antes del `save()`.
- ~~Crear un comercio no lleva a ningún lado~~ **(resuelto).** `store()` ahora redirige a
  `menus.index` del comercio nuevo, y ahí lo espera **`x-comercios.pendientes`** con el estado
  de la configuración. La lista sale de **`Comercio::pasosConfiguracion()`** —menú, portada,
  horarios, logo—, que devuelve **los cuatro pasos siempre** con un flag `completo`;
  `pasosPendientes()` es el filtro de los que faltan. La portada sólo se ofrece si el plan la
  incluye, y cada paso lleva ancla (`#bloque-portada`, `#horariosGrilla`) porque si no hay dos
  links al mismo lado y el segundo parece un error.

  **El bloque se muestra siempre, en dos estados.** Con algo pendiente, el checklist entero:
  lo hecho tildado y tachado, lo que falta con su link. Con todo hecho, una sola línea
  («Tu menú está configurado»). Se probaron las dos puntas y ninguna servía sola: esconderlo
  al terminar borraba el progreso y hacía que la lista cambiara de tamaño sola, y dejar los
  cuatro renglones tildados para siempre es ruido permanente en una pantalla de trabajo.
- ~~Los 5 campos comunes de crear y editar están duplicados~~ **(resuelto).** Van en
  **`x-comercios.datos-basicos`** (nombre, descripción, dirección, horario) y
  **`x-comercios.logo`**, los dos con `$comercio` null al crear. Las reglas salen de
  **`ComercioController::reglasBasicas()`**, que usan `store()` y `update()`.
  El markup de editar repetía a mano las diez clases que `.input-field` ya agrupa.

  > Al unificar apareció un bug de `store()`: leía `$validated['descripcion']` sin `?? null`,
  > así que un alta con sólo el nombre tiraba **500**. No se notaba porque el formulario
  > manda los campos siempre, vacíos pero presentes. Mismo patrón que ya había mordido en
  > `update()` con `tagline` y `google_review_url`.
