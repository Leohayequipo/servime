---
name: identidad
description: La identidad visual de ServiME — verde profundo, crema y terracota — y el sistema de tokens que la sostiene en la landing, el login y el panel. Incluye los contrastes verificados, qué NO se toca (el menú público, el QR, los colores del comercio) y cómo cambiar un color sin editar cientos de clases. Usar al tocar colores, estilos, la landing, las pantallas de auth, el panel, los emails o los assets de marca.
---

# Identidad visual — ServiME

La marca es **verde profundo + crema + terracota**. Reemplazó a una etapa de negro con
ámbar que se veía como "startup de IA genérica" y no como gastronomía.

| Dónde | Cómo se ve |
|---|---|
| Landing y pantallas de auth | Fondo crema, texto verde profundo, CTA terracota |
| Panel | Lo mismo: crema, verde y terracota |
| Emails | Lo mismo, con hex en vez de tokens |
| **Menú público del comercio** | **Ni una cosa ni la otra — ver "Lo que no se toca"** |

**El panel es claro, igual que la landing.** Hubo una etapa intermedia en verde profundo
que se descartó: en pantallas de trabajo el fondo de color tiraba a app temática y le
restaba seriedad. Los SaaS que se leen como herramienta seria son claros.

## La regla de oro: nada de colores escritos a mano

Todo el color pasa por variables CSS declaradas en `resources/css/app.css`, que
`tailwind.config.js` expone como tokens de Tailwind.

```css
:root {
    --c-accent:      192  86  58;  /* #C0563A terracota                    */
    --c-accent-deep: 168  70  44;  /* #A8462C terracota oscuro (hover)     */
    --c-ok:           11 122  87;  /* #0B7A57 éxito       — semántico      */
    --c-danger:      179  38  30;  /* #B3261E error       — semántico      */
    --c-warn:        138 101   8;  /* #8A6508 atención    — semántico      */
    --c-line:        225 217 201;  /* #E1D9C9 bordes                       */
    --c-elev-1:      242 237 227;  /* #F2EDE3 superficie base              */
    --c-elev-2:      251 249 245;  /* #FBF9F5 papel: tarjetas y modales    */
    --c-ink:          40  66  54;  /* #284236 verde profundo               */
    --c-ink-soft:     85 112  95;  /* #55705F salvia oscuro                */
    --c-bg:          242 237 227;  /* #F2EDE3 crema                        */
}
```

**Cambiar cualquier color del panel es editar una línea de ahí.** Antes de este sistema
había ~1.900 usos con nombre más 400 escritos a mano; ahora no hay ninguno suelto.

### Por qué los valores están en RGB suelto y no en hex

Porque hay **344 usos con opacidad** (`bg-accent-blue/20`, `border-accent-gray/50`).
Tailwind necesita los componentes separados para inyectar el alfa:

```js
'accent-blue': 'rgb(var(--c-accent) / <alpha-value>)'
```

Con un `var()` plano esas 344 clases se rompen sin aviso: el color simplemente no aplica.

### Los nombres de los tokens mienten

`accent-blue` no es azul: es el acento de marca, hoy terracota. Viene de una etapa
anterior y se conservó para no tocar cientos de clases. `accent-green` sí es verde, pero
es el **semántico de éxito**, no el verde de la marca.

Al leer una clase, guiarse por el rol, no por el nombre.

### Semánticos vs de marca

`warning`, `danger` y `accent-green` existen para que un aviso siga leyéndose como aviso
aunque cambie la identidad. **No son de marca y no deben seguir al acento.** El
`--c-warn` está a 32° de tono del terracota justamente para que un aviso no se confunda
con el color de marca.

### Una pantalla puede tener su propia paleta

Redefiniendo las variables en un `:root` local, sin recompilar ni afectar al resto:

```blade
<x-app-layout>
    <style>:root { --c-bg: 27 44 35; --c-accent: 232 146 122; }</style>
```

Así se pilotó la identidad en el dashboard antes de aplicarla a las 49 vistas.

## Contrastes verificados (WCAG AA)

No son estimaciones: están medidos con la fórmula de luminancia relativa.

| Sobre el crema `#F2EDE3` | |
|---|---|
| Verde profundo `#284236` | 9,36:1 |
| Salvia oscuro `#55705F` | 4,65:1 |
| Terracota `#C0563A` con texto blanco | 4,53:1 |
| Éxito `#0B7A57` | 4,57:1 |
| Error `#B3261E` | 5,60:1 |
| Atención `#8A6508` | 4,56:1 |

> ⚠️ **Los semánticos NO son los mismos que en una paleta oscura.** Sobre crema, el
> emerald `#10B981` da 2,17:1 y el ámbar `#EAB308` 1,64:1: invisibles. Un color que
> funciona sobre fondo oscuro casi nunca sirve sobre claro, y al revés. Si algún día se
> vuelve a una paleta oscura, hay que reemplazar los tres, no sólo el fondo.

> ⚠️ **El terracota de la paleta de marca (`#E07A5F`) NO sirve para texto ni para el fondo
> de un botón**: da 2,53:1 sobre crema y 2,95:1 con texto blanco, contra el mínimo de 4,5.
> Por eso hay tres terracotas y no uno: `#E07A5F` decorativo, `#C0563A` para fondos de
> botón (4,53:1 con blanco) y `#B4462A` para texto sobre crema (4,67:1).

Mismo caso con el salvia: `#6B8C7A` da 3,18:1 sobre crema, así que para texto va `#55705F`.

## Sin degradados

La identidad no los usa. Se sacaron del nav activo, los avatares, los modales de
SweetAlert, botones e íconos. El único que queda es el *shimmer* de las animaciones de
carga, que no es decorativo: indica que algo está pasando.

### El panel no tiene modo oscuro

`layouts/app` forzaba `class="dark"` y cargaba `dark-mode.js`, lo que activaba ~112
clases `dark:` de Breeze. Al pasar a claro se quitó el forzado y se limpiaron esas
clases, que quedaban inertes y engañaban al leer el código. **No volver a agregar
variantes `dark:`**: no hay modo oscuro y no aplicarían.

### Cuidado con el CSS local con `!important`

`menus/create` tenía en su `<style>` una regla que pintaba **todos** los inputs de la
pantalla de gris oscuro con `!important`. Sobrevivió a varias pasadas de migración
porque no era ni una clase de Tailwind ni un token: era CSS local pisando todo. Al
buscar restos de una paleta vieja, revisar también los bloques `<style>` de cada vista,
no sólo las clases.

## Lo que no se toca

- **El menú público del comercio** (`resources/views/menu/`). El tema sale de la foto que
  sube cada comercio vía `BrandThemeService`: ahí la marca es la del cliente, no la
  nuestra. Es lo que sostiene el argumento de "tu marca, no una plantilla" que vende la
  landing. Ver la skill `portada`.
- **El canvas del QR descargable** (`menus/index.blade.php`). Es blanco y negro puros
  porque un QR necesita contraste máximo para escanearse.
- **Los presets de color del comercio** (`comercios/edit.blade.php`): `professional`,
  `natural`, `elegant`. Son los colores que el comercio elige para su propio menú.

## Colores fuera del alcance de los tokens

Tres lugares donde un token no llega y hay que escribir el color:

1. **Los emails.** Los clientes de correo no cargan hojas externas ni soportan variables
   CSS: todo va inline y con hex. Además hay que usar **tablas** para el layout — Outlook
   no soporta flexbox ni grid.
2. **La página de error** (`errors/layout.blade.php`), que es standalone y no carga
   `app.css`.
3. **SweetAlert**, que recibe los colores como opciones de JavaScript. Ahí sí funciona
   `'rgb(var(--c-accent))'`, porque lo aplica como estilo inline y el navegador lo
   resuelve contra `:root`.

## Assets de marca

| Archivo | Para qué |
|---|---|
| `resources/brand/logo_servime_1254.png` | **Master.** No se sirve nunca; de acá salen los demás |
| `resources/brand/isotipo_verde_512.png` | La taza sobre círculo verde, para redes o cartelería |
| `public/images/logo_servime.png` · `.webp` | 400×400. El WebP (16 KB) es el que sirve la web |
| `public/images/servime_ia.ico` · `favicon-32.png` · `apple-touch-icon.png` | Favicons |
| `public/images/og-image.png` | Preview al compartir el link |

**El favicon es la taza sola, no el logo completo**: a 32 px el texto del logo circular es
una mancha ilegible.

**Regenerar el og-image:** `php scripts/generar-og-image.php`. Usa GD con las fuentes del
sistema (Georgia como sustituta de Fraunces). Dos cosas que ya se probaron y no funcionan:
un degradado radial sale en bandas escalonadas, y el logo como marca de agua con
`imagecopymerge` deja un rectángulo visible porque esa función ignora el canal alfa.

> ⚠️ El `.ico` lleva **PNG comprimido adentro**, no BMP. El anterior pesaba 361 KB porque
> guardaba seis mapas de bits sin comprimir, uno de 256×256 — y se carga en 12 vistas.

## Tipografía

**Fraunces** para títulos, **Nunito Sans** para texto, ambas desde `fonts.bunny.net`.

Fraunces se conservó a propósito en contra del manual de marca, que proponía Poppins:
Poppins es la tipografía más usada de internet y habría reintroducido el problema que el
rediseño vino a resolver. En los emails, donde no se pueden cargar fuentes, Georgia hace
de sustituta.

## Cómo probar un cambio de color

Los **264 tests no detectan un color ilegible** — verifican lógica. La verificación es a
ojo, y conviene cubrir:

- El recorrido completo del comercio: landing → login → recuperar contraseña → panel.
- Las pantallas del panel con más color: `analytics/comercio`, `menus/create`,
  `comercios/edit`, `dashboard`.
- **Los estados, que son los que se olvidan**: un input con error, una alerta de cuota
  agotada, un modal de confirmación, la página 404.
- Los emails, renderizándolos con `view('emails.welcome', [...])->render()`.

Para los emails no alcanza con el render: el cliente de correo tiene la última palabra.
