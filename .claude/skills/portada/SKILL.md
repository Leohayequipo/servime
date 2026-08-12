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
que se clickea o arrastra (`pointerdown/move`, anda con mouse y con dedo).

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

Dos cosas medidas en Azopardo (26 candidatos, 22 de ellos categorías):

- **Las categorías nunca entran** si el comercio tiene promos y un tag: los 3 lugares se llenan
  con los pasos 1–3. Y si entraran, ganaría la primera del PDF, no la más pedida.
- **El match de tags es exacto y se pierde lo obvio**: un menú con `veg` y `veggie` no dispara
  🥗 Vegetarianas, porque la regla busca `VEGETARIANO`.

**Ojo con `.dot-open`**: el punto de "abierto ahora" va en verde **por semántica**, no con el
acento. Si tomara el color de marca, un comercio con acento rojo tendría un punto rojo diciendo
que está abierto. (El `choose.blade.php` viejo todavía tiene ese bug: `background: var(--accent)`
con la animación en verde hardcodeado.)

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

## Las imágenes de PLATOS y PROMOS no reciben este tratamiento

La portada y el logo se procesan (resize + webp). Las de platos y promociones **se guardan
tal cual las sube el comercio**: `uploadPlatoImagen` y `uploadPromocionImagen` validan y hacen
`store()`, nada más. Medido en la base:

```
3120x4160 px — 1.024 KB    ← foto de celular sin tocar
3120x4160 px — 1.090 KB
3120x4160 px — 1.090 KB
```

**La tarjeta del menú las muestra en 80×80 px.** Un cliente en el celular se baja un mega para
ver una miniatura, y el menú público es justamente lo que se abre desde el teléfono en la mesa.

Y cada lugar valida distinto, sin criterio común:

| Sube | Límite | Formatos | ¿Procesa? |
|---|---|---|---|
| plato / promo | 2 MB | jpeg png jpg gif | **no** |
| logo | 2 MB | jpeg png jpg gif | sí (cuadrado fijo) |
| portada | 8 MB | cualquier imagen | sí (fondo/hero/thumb webp) |
| menú (PDF o foto) | 10 MB | pdf jpeg png jpg gif | — |

El límite de 2 MB **molesta de verdad**: una foto de celular actual lo pasa fácil y el comercio
no puede subirla. Y ninguno de los dos acepta **webp**, que es el formato al que la portada ya
convierte todo.

Si se unifica: el camino es un servicio que procese al subir (achicar al lado mayor razonable
+ webp), como ya hace `BrandThemeService` con GD nativo — sin dependencias nuevas. Con eso el
límite de entrada puede subir tranquilo, porque lo que se guarda siempre queda chico.

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
- `store()` no maneja portada: se configura después de crear, desde editar (decisión, no olvido).
