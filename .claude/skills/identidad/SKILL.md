---
name: identidad
description: La identidad visual de ServiME — verde profundo, crema y terracota — y el sistema de tokens que la sostiene en la landing, el login y el panel. Incluye los contrastes verificados, qué NO se toca (el menú público, el QR, los colores del comercio) y cómo cambiar un color sin editar cientos de clases. Usar al tocar colores, estilos, la landing, las pantallas de auth, el panel, los emails o los assets de marca.
---

# Identidad visual — ServiME

La marca es **verde profundo + crema + terracota**. Reemplazó a una etapa de negro con
ámbar que se veía como "startup de IA genérica" y no como gastronomía.

| Dónde | Cómo se ve |
|---|---|
| Landing y pantallas de auth | Claro: fondo crema, texto verde, CTA terracota |
| Panel | Oscuro: fondo verde profundo, texto crema, acento terracota |
| Emails | Claro, con el mismo criterio que la landing |
| **Menú público del comercio** | **Ni una cosa ni la otra — ver "Lo que no se toca"** |

El panel quedó oscuro a propósito: es cómodo para trabajar muchas horas. Lo que cambió
no es la luminosidad sino el color — de negro con ámbar a verde con terracota.

## La regla de oro: nada de colores escritos a mano

Todo el color pasa por variables CSS declaradas en `resources/css/app.css`, que
`tailwind.config.js` expone como tokens de Tailwind.

```css
:root {
    --c-accent:      232 146 122;  /* #E8927A terracota */
    --c-accent-deep: 192  86  58;  /* #C0563A terracota oscuro */
    --c-ok:           16 185 129;  /* verde de éxito — semántico */
    --c-danger:      239  68  68;  /* rojo de error — semántico */
    --c-warn:        234 179   8;  /* ámbar de atención — semántico */
    --c-line:         61  92  75;  /* bordes */
    --c-elev-1:       27  44  35;  /* superficie base */
    --c-elev-2:       36  58  46;  /* tarjetas */
    --c-ink:         242 237 227;  /* crema */
    --c-ink-soft:    167 188 168;  /* salvia claro */
    --c-bg:           27  44  35;  /* fondo */
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

| Sobre el fondo del panel `#1B2C23` | |
|---|---|
| Crema `#F2EDE3` | 12,58:1 |
| Salvia `#A7BCA8` | 7,27:1 |
| Terracota `#E8927A` | 6,17:1 |
| Ámbar de aviso `#EAB308` | 7,65:1 |
| Texto oscuro sobre terracota | 7,45:1 |

> ⚠️ **El terracota de la paleta de marca (`#E07A5F`) NO sirve para texto ni para el fondo
> de un botón**: da 2,53:1 sobre crema y 2,95:1 con texto blanco, contra el mínimo de 4,5.
> Por eso hay tres terracotas y no uno: `#E07A5F` decorativo, `#C0563A` para fondos de
> botón (4,53:1 con blanco) y `#B4462A` para texto sobre crema (4,67:1).

Mismo caso con el salvia: `#6B8C7A` da 3,18:1 sobre crema, así que para texto va `#55705F`.

## Sin degradados

La identidad no los usa. Se sacaron del nav activo, los avatares, los modales de
SweetAlert, botones e íconos. El único que queda es el *shimmer* de las animaciones de
carga, que no es decorativo: indica que algo está pasando.

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
