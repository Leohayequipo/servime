---
name: planes
description: Sistema de planes/suscripción de ServiME (Básico/Pro/Premium) — cuota mensual de mensajes IA por comercio, modelo OpenAI por plan, feature gating y panel admin. Usar al tocar precios/planes, cuota de IA, límites por comercio, gating de analytics/excel/promociones, o el chatbot.
---

# Sistema de Planes — ServiME

SaaS multi-tenant por `Comercio`. Cada comercio tiene un **Plan** que define cuánto y cómo
puede usar la IA. Los planes son editables desde la web por el super admin (viven en DB).

## Modelo de datos

- **Tabla `plans`** (migración `2026_06_20_120000_create_plans_table`): se siembra en la
  **propia migración** (no en el seeder, porque `DatabaseSeeder` no corre en producción).
  Campos: `key` (estable: basico/pro/premium), `nombre` (marketinero, editable), `descripcion`,
  `precio` (informativo, no hay cobro online), `mensajes_ia_mes` (**null = ilimitado**),
  `modelo_ia`, `max_tokens_chat`, `features` (JSON: `analytics`, `excel`, `promociones`, `customizacion`), `orden`, `activo`.
- **`comercios`** (migración `..._120100_add_plan_to_comercios_table`): `plan_id` (FK nullOnDelete),
  `ia_mensajes_periodo` (`YYYY-MM`), `ia_mensajes_usados` (int). Los comercios existentes se migran a Básico.
- **Modelos:** `App\Models\Plan`, helpers en `App\Models\Comercio`.

## Planes por defecto (valores borrador, editables en `/admin/plans`)

| Plan    | key     | mensajes_ia_mes | modelo_ia    | features                       |
|---------|---------|-----------------|--------------|--------------------------------|
| Básico  | basico  | 500             | gpt-4o-mini  | —                              |
| Pro     | pro     | 5000            | gpt-4o-mini  | analytics, excel, promociones  |
| Premium | premium | null (ilimitado)| gpt-4o       | analytics, excel, promociones, customizacion |

⚠️ Esta tabla es lo que **siembra la migración**, no necesariamente lo que hay en la DB: los
planes se editan por web y ya divergieron (en local, Pro usa `gpt-4o` y Básico tiene
`promociones`). Antes de afirmar algo sobre un plan, **consultá la DB**, no esta tabla.

## Helpers en `Comercio` (fuente de verdad de la lógica)

- `planEfectivo()` — el plan asignado, o `Plan::default()` (Básico) como fallback.
- `puedeUsarIA()` — `true` si el plan es ilimitado o `usados < mensajes_ia_mes`.
- `mensajesIaUsados()` — devuelve 0 si el período guardado != mes actual (**reset automático sin cron**).
- `registrarUsoIA()` — suma 1 al contador; reinicia si cambió el mes.
- `resetUsoIA()` — pone el contador en 0 (reset manual del super admin; "se cobra aparte").
- `modeloIA()` / `maxTokensChat()` — salen del plan (con fallback seguro).
- `tieneFeature(string $f)` — para el gating.

## Dónde se aplican los límites

- **Chatbot** (`ChatbotController@chat` y `@chatStream`): antes de llamar a la IA chequea `puedeUsarIA()`.
  Si se agotó → responde un mensaje amable (`success: true`, `used_openai: false`), **no** llama a OpenAI.
  Tras una respuesta OK → `registrarUsoIA()`. El modelo y `max_tokens` los toma `ChatbotService`
  desde `$comercio->modeloIA()` / `maxTokensChat()` (ya **no** están hardcodeados).
  **Verificado end-to-end (2026-08-10):** con la cuota al tope, las dos rutas devuelven el mensaje
  amable en 18-25ms (contra ~2700ms de una llamada real, o sea que no toca la red), el contador
  no se incrementa, y queda logueado con `used_openai=false` — así el comercio ve en analytics la
  demanda que no pudo atender. El reset mensual sin cron también anda: un comercio con
  `ia_mensajes_periodo` de un mes anterior devuelve 0 en `mensajesIaUsados()`.
- 🐛 **Bug conocido — el stream cobra cuota aunque OpenAI falle.** En `chatStream()` el
  `try/catch` alrededor de `streamMessage()` es interno, así que si la llamada explota y se
  responde con `generateFallbackResponse()`, igual se ejecuta `registrarUsoIA()`: no se gastó
  un token y el comercio pierde un mensaje de su cuota. La ruta sin stream **no** tiene el
  problema (ahí la excepción sale al catch de afuera y nunca contabiliza). Son dos
  comportamientos distintos para lo mismo, y el que usa el front es el que cobra de más.
- **Feature gating** (super admin siempre pasa):
  - `analytics` → inline en `AnalyticsController` (vista, export): aborta 403 sin la feature.
  - `excel` y `promociones` → vía **middleware `plan.feature:<feature>`** (`App\Http\Middleware\EnsurePlanFeature`,
    alias en `bootstrap/app.php`) aplicado a las rutas de export/import y de promos en `routes/web.php`.
    Además se ocultan los botones en `resources/views/menus/create.blade.php`.
  - `customizacion` (portada del menú público) → inline en `ComercioController::update()`, porque
    `update()` hace muchas otras cosas que no dependen del plan y no se puede cortar la ruta
    entera. **Borrar la portada nunca se bloquea**: si un comercio baja de plan tiene que poder
    sacarla igual. Ver la skill `portada`.
- **Dashboard**: `DashboardController` pasa `$comerciosSinCuotaIA`; la vista muestra un aviso amarillo
  cuando un comercio llegó al límite. **Pendiente:** el aviso aparece recién cuando ya se agotó,
  no al acercarse. Avisar al 80% le daría margen al comercio para pedir el upgrade antes de
  quedarse sin asistente en pleno servicio.

## `max_tokens_chat` — medido, no sirve como diferenciador

Topea **la respuesta** del bot, no el prompt. Llega a OpenAI desde `ChatbotService`
(línea ~80 en la ruta normal, ~1065 en el stream). Validado 50-4000 en `PlanController`.

**El techo nunca se alcanza.** Medido sobre 137 respuestas reales con `used_openai=true`:

| mediana | p90 | máximo | respuestas que rozan un tope de 600 |
|---------|-----|--------|--------------------------------------|
| 131 tok | 191 | 497    | 0                                    |

Con topes de 600/900/1200 por plan, ni una sola respuesta se acercó. Consecuencias:

- **Subirlo es gratis**: `max_tokens` es un techo, no una reserva; solo se paga lo generado.
- **Como feature de plan es humo**: vender "respuestas más largas" cuando nada pasa de 497
  tokens no le cambia nada al comercio. Los diferenciadores reales son `modelo_ia` y
  `mensajes_ia_mes`.
- **Igual conviene NO dejarlo ilimitado**: es el único freno contra una generación desbocada
  (`gpt-4o` puede emitir ~16k tokens de salida, que son los caros) y contra una respuesta
  ilegible en un celular. Mantenerlo cuesta cero porque nunca se activa.
- **Recomendación pendiente de decisión del usuario:** igualar los tres planes en ~1500 desde
  `/admin/plans` (sin deploy) y dejar de diferenciarlo. Al 2026-08-10 no está aplicado.

## El modelo del plan: dónde está la lista y el bug que ya se pagó

La lista del desplegable vive en **`Plan::MODELOS_IA`** (antes estaba escrita a mano dentro
del Blade). Orden de recomendación, `gpt-5.6-luna` primero.

**No es una validación**: `modelo_ia` acepta cualquier string, a propósito, para poder cargar
un modelo nuevo por SQL antes de que exista en el código.

⚠️ **Por eso existe `Plan::modelosParaElegir()`, y no hay que sacarlo.** Mete siempre el modelo
que el plan YA tiene, esté o no entre los ofrecidos. Sin eso, un modelo cargado por SQL dejaba
el desplegable posicionado en la primera opción, y **guardar cualquier otro campo del plan lo
revertía en silencio a `gpt-4o-mini`**. Se descubre semanas después, cuando la factura no baja.
Cubierto por `tests/Feature/ModeloDelPlanTest.php`.

Al sumar un modelo nuevo, verificar que `ChatbotService::esModeloDeRazonamiento()`
(`/^(gpt-5|o[1-9])/i`) lo clasifique bien: si es de razonamiento y no matchea, el payload sale
con `max_tokens` y la API devuelve **400 en todas las consultas** de ese plan.

## 🔴 Pendiente grande: el consumo se guarda y no se ve en ningún lado

Cada consulta guarda su gasto en `chat_conversations.metadata.consumo` (`modelo`,
`tokens_entrada`, `tokens_cacheados`, `tokens_salida`, `tokens_razonamiento`), y anda por las
dos vías del chat, streaming incluido. Está bien medido desde ago-2026.

**Falta todo lo que lo convierte en información:**

1. **No hay precios por modelo en ninguna parte del sistema.** Buscado: cero. Sin eso los
   tokens no se pueden convertir en plata. La forma recomendada es un array en
   `config/servime.php` (`modelos_ia` → `label`, `razonamiento`, `usd_entrada`, `usd_salida`),
   **no una tabla**: son 3 valores que cambian dos veces al año, en git queda la medición que
   justificó cada uno, y una tabla más divergiría entre local y prod como ya pasa con `plans`.
   El `razonamiento` como dato explícito, además, saca de encima la regex que adivina por
   el nombre.
2. **No hay pantalla.** Ninguna vista muestra el consumo — la única salida hoy es el Excel de
   conversaciones, que además se lo lleva el comercio, no el super admin. El lugar natural es
   `/analytics/dashboard`, que ya es la vista transversal.
   ⚠️ Ojo: ese grupo de rutas sólo pide `auth`, **no `super_admin`** (el gateo por comercio se
   hace inline en el controlador). Una vista de costos necesita el chequeo a mano.
3. **Lo que de verdad importa no es el total, es el margen**: costo real contra el precio del
   plan. Ahí se ve si el Premium que se vende a $X cuesta $Y, y qué comercio se come el margen
   solo. Es la decisión de negocio que hoy no se puede tomar con datos.

El dato ya está guardado hace semanas: esto es multiplicar y mostrar, no instrumentar.

⚠️ **El prompt de entrada pesa mucho más que la salida** y `max_tokens` no lo toca: medido,
el system prompt de un comercio con menú cargado da ~5.000 tokens (20k chars) y viaja **entero
en cada mensaje**, más el historial. El input tiene su propio tope, flojo y sin relación con el
plan: `trimContext($contexto, 48000)` en `ChatbotService`. Si alguna vez hay que bajar el costo
de IA de verdad, la palanca está ahí y en el modelo, no en `max_tokens_chat`.

⚠️ Cuando trunca, **corta a mitad de frase y se le muestra así al cliente**. El código contempla
que el marcador `[[PLATOS:...]]` quede partido (ver comentario en `ChatbotService::separarMarcador`)
pero no hace nada con la prosa. OpenAI devuelve `finish_reason: "length"` cuando truncó; hoy
ese dato no se lee.

## En el landing público (marketing)

Los planes también se muestran **públicamente** (no solo en el admin):

- **Sección de precios** en `resources/views/marketing/landing.blade.php`: la ruta `/`
  (`routes/web.php`) pasa `$planes = Plan::where('activo', true)->where('orden', '>', 0)->orderBy('orden')->get()`.
  Renderiza una card por plan con precio, descripción, cuota de IA y features. El plan de demo
  (`orden = 0`) queda **excluido** por el filtro `orden > 0`.
- **Precios en PESOS (ARS)**, no USD: se muestran como `$X/mes`; si el plan **no tiene precio**
  cargado → muestra **"A consultar"**. La `<meta>` description y el schema.org (`priceCurrency: ARS`)
  toman el precio mínimo (`$planes->min('precio')`, que ignora nulls).
- **Chatbot de ventas del landing** (`LandingChatController`): arma su system prompt con los planes
  vigentes desde DB (`buildSystemPrompt()`), no con un precio hardcodeado. Tiene `FEATURE_LABELS`
  (incluye `intencion_ia`) para nombrar las features en lenguaje humano.
- Cargar/editar precios: `/admin/plans` (no requiere deploy).

## Panel admin (super admin, prefijo `/admin`)

`App\Http\Controllers\PlanController` + rutas en `routes/web.php` (dentro del grupo `super_admin`):

- `/admin/plans` (`plans.index`) — tarjetas de planes + tabla "plan por comercio" (asignar + barra de uso + **botón Resetear uso IA**).
- `plans.create/store/edit/update/destroy` — CRUD de planes (no se puede borrar un plan con comercios).
- `comercios/{slug}/plan` (`comercios.assign-plan`) — asignar plan a un comercio.
- `comercios/{slug}/reset-ia` (`comercios.reset-ia`) — reset manual del uso de IA.

Vistas: `resources/views/admin/plans/index.blade.php` y `edit.blade.php`.

## ⚠️ Gotchas (aprendidos a los golpes)

- **Confirmaciones/alertas:** usar SIEMPRE los helpers SweetAlert2 de `layouts/app.blade.php`
  (`showConfirm`, `showConfirmWithLoader`, `showSuccess`, `showError`), NO `confirm()`/`alert()`
  nativos (no matchean la estética). Form con confirmación:
  `onsubmit="event.preventDefault(); const f=this; showConfirmWithLoader('¿...?', r=>{ if(r) f.submit(); }, 'Procesando...');"`

- **La barra de navegación del panel está inline en `resources/views/layouts/app.blade.php`**
  (NO en `layouts/navigation.blade.php`, que es un partial de Breeze muerto). El link "Planes"
  va ahí, dentro del `@if(Auth::user()->isSuperAdmin())`. Editar el otro archivo no tiene efecto.
- La barra de links se ve en `md:` para arriba; en mobile hay un **menú hamburguesa** (`#mobileMenu`,
  toggle por JS) en el mismo `app.blade.php` — si agregás un link al nav, agregalo en **ambos** (desktop + mobile).
- **Sembrar planes en la migración**, no en `DatabaseSeeder` (que retorna temprano en producción).
- `mensajes_ia_mes = null` significa **ilimitado** en todos lados (no 0).
- El throttle del chat (limitador `chat-ia`: 20/min por dispositivo + 60/min por IP, definido en
  `AppServiceProvider`) es anti-abuso y **independiente** del plan; no tocarlo por planes. Son dos
  capas distintas: el throttle protege el servidor y se puede esquivar rotando el `session_id`;
  la cuota protege la factura de OpenAI y es server-side, no se esquiva.
- Tras tocar rutas/vistas en local: `php artisan optimize:clear` (o `view:clear`) + Ctrl+F5.

## Cómo extender

- **Nueva feature gateable:** agregarla a `features.*` en `PlanController::validatePlan()` (regla `Rule::in`),
  al checkbox en `admin/plans/edit.blade.php`, y chequear `$comercio->tieneFeature('x')` donde corresponda.
- **Cambiar límites/precios/nombres:** desde `/admin/plans` (no requiere deploy).

> **Deuda técnica conocida (features hardcodeadas):** la lista de features posibles está
> **duplicada** en 2 lugares que hay que mantener en sync — los checkboxes de
> `admin/plans/edit.blade.php` y el `Rule::in([...])` de `PlanController::validatePlan()`.
> Para esta etapa (pocas features) está OK y es decisión deliberada. Si se agregan features
> seguido, conviene centralizar el catálogo en un `config/plan_features.php`
> (`'key' => 'Etiqueta'`) y que tanto el form (loop de checkboxes) como la validación
> (`Rule::in(array_keys(...))`) lean de ahí → agregar feature = 1 línea + su enforcement.
> No requiere tabla ni migración. (La lógica que *hace* la feature siempre hay que escribirla.)
- **Cobro online (futuro):** hoy la asignación es manual por super admin; integrar pasarela
  (MercadoPago/Stripe) sería un paso aparte con webhooks y estados de suscripción.

## Tests

`tests/Feature/PlanTest.php` cubre: seed de planes, cuota/reset/reset-mensual, chatbot sin cuota
(mensaje amable + `used_openai=false`), gating de excel/promociones (403 + bypass super admin), acceso al panel (403/200),
asignar plan, resetear uso, editar plan.
