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

**2 · Hero 16:11 a 780px**, encuadrado arriba del centro (ahí suele estar el cartel).
**3 · Miniatura 180×180.**

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

## Tests

`tests/Feature/ComercioSlugTest.php` (7) y `tests/Feature/PortadaTest.php` (5).
