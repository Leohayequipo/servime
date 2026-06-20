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
  `modelo_ia`, `max_tokens_chat`, `features` (JSON: `analytics`, `excel`, `promociones`), `orden`, `activo`.
- **`comercios`** (migración `..._120100_add_plan_to_comercios_table`): `plan_id` (FK nullOnDelete),
  `ia_mensajes_periodo` (`YYYY-MM`), `ia_mensajes_usados` (int). Los comercios existentes se migran a Básico.
- **Modelos:** `App\Models\Plan`, helpers en `App\Models\Comercio`.

## Planes por defecto (valores borrador, editables en `/admin/plans`)

| Plan    | key     | mensajes_ia_mes | modelo_ia    | features                       |
|---------|---------|-----------------|--------------|--------------------------------|
| Básico  | basico  | 500             | gpt-4o-mini  | —                              |
| Pro     | pro     | 5000            | gpt-4o-mini  | analytics, excel, promociones  |
| Premium | premium | null (ilimitado)| gpt-4o       | analytics, excel, promociones  |

## Helpers en `Comercio` (fuente de verdad de la lógica)

- `planEfectivo()` — el plan asignado, o `Plan::default()` (Básico) como fallback.
- `puedeUsarIA()` — `true` si el plan es ilimitado o `usados < mensajes_ia_mes`.
- `mensajesIaUsados()` — devuelve 0 si el período guardado != mes actual (**reset automático sin cron**).
- `registrarUsoIA()` — suma 1 al contador; reinicia si cambió el mes.
- `resetUsoIA()` — pone el contador en 0 (reset manual del super admin; "se cobra aparte").
- `modeloIA()` / `maxTokensChat()` — salen del plan (con fallback seguro).
- `tieneFeature(string $f)` — para el gating.

## Dónde se aplican los límites

- **Chatbot** (`ChatbotController@chat`): antes de llamar a la IA chequea `puedeUsarIA()`.
  Si se agotó → responde un mensaje amable (`success: true`, `used_openai: false`), **no** llama a OpenAI.
  Tras una respuesta OK → `registrarUsoIA()`. El modelo y `max_tokens` los toma `ChatbotService`
  desde `$comercio->modeloIA()` / `maxTokensChat()` (ya **no** están hardcodeados).
- **Feature gating** (super admin siempre pasa):
  - `analytics` → inline en `AnalyticsController` (vista, export): aborta 403 sin la feature.
  - `excel` y `promociones` → vía **middleware `plan.feature:<feature>`** (`App\Http\Middleware\EnsurePlanFeature`,
    alias en `bootstrap/app.php`) aplicado a las rutas de export/import y de promos en `routes/web.php`.
    Además se ocultan los botones en `resources/views/menus/create.blade.php`.
- **Dashboard**: `DashboardController` pasa `$comerciosSinCuotaIA`; la vista muestra un aviso amarillo
  cuando un comercio llegó al límite.

## Panel admin (super admin, prefijo `/admin`)

`App\Http\Controllers\PlanController` + rutas en `routes/web.php` (dentro del grupo `super_admin`):

- `/admin/plans` (`plans.index`) — tarjetas de planes + tabla "plan por comercio" (asignar + barra de uso + **botón Resetear uso IA**).
- `plans.create/store/edit/update/destroy` — CRUD de planes (no se puede borrar un plan con comercios).
- `comercios/{slug}/plan` (`comercios.assign-plan`) — asignar plan a un comercio.
- `comercios/{slug}/reset-ia` (`comercios.reset-ia`) — reset manual del uso de IA.

Vistas: `resources/views/admin/plans/index.blade.php` y `edit.blade.php`.

## ⚠️ Gotchas (aprendidos a los golpes)

- **La barra de navegación del panel está inline en `resources/views/layouts/app.blade.php`**
  (NO en `layouts/navigation.blade.php`, que es un partial de Breeze muerto). El link "Planes"
  va ahí, dentro del `@if(Auth::user()->isSuperAdmin())`. Editar el otro archivo no tiene efecto.
- Esa barra solo se ve en pantalla `md:` para arriba; no hay menú hamburguesa de reemplazo.
- **Sembrar planes en la migración**, no en `DatabaseSeeder` (que retorna temprano en producción).
- `mensajes_ia_mes = null` significa **ilimitado** en todos lados (no 0).
- El `throttle:20,1` por IP del chat es anti-abuso, **independiente** del plan; no tocarlo por planes.
- Tras tocar rutas/vistas en local: `php artisan optimize:clear` (o `view:clear`) + Ctrl+F5.

## Cómo extender

- **Nueva feature gateable:** agregarla a `features.*` en `PlanController::validatePlan()` (regla `Rule::in`),
  al checkbox en `admin/plans/edit.blade.php`, y chequear `$comercio->tieneFeature('x')` donde corresponda.
- **Cambiar límites/precios/nombres:** desde `/admin/plans` (no requiere deploy).
- **Cobro online (futuro):** hoy la asignación es manual por super admin; integrar pasarela
  (MercadoPago/Stripe) sería un paso aparte con webhooks y estados de suscripción.

## Tests

`tests/Feature/PlanTest.php` cubre: seed de planes, cuota/reset/reset-mensual, chatbot sin cuota
(mensaje amable + `used_openai=false`), gating de excel/promociones (403 + bypass super admin), acceso al panel (403/200),
asignar plan, resetear uso, editar plan.
