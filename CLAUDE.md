# CLAUDE.md — ServiME

Guía para Claude Code al trabajar en este repo. Mantener conciso y actualizado.

## Qué es

SaaS de **menús digitales QR** para comercios gastronómicos. Un comercio sube su menú
(PDF/imagen), la IA extrae los platos, y los clientes acceden por QR a un menú con
chatbot, carrito de pre-pedido y analytics. Multi-tenant por comercio, multi-rol.

- **Stack:** Laravel 12, PHP 8.2, MySQL, Blade + Tailwind (Vite), Breeze (auth).
- **IA:** OpenAI para el chatbot y OCR (el modelo sale del **plan** del comercio, hoy
  `gpt-4o-mini`) y Gemini (`gemini-3.6-flash`) para procesar las cartas. Al tocar el chat, ver la
  skill `chatbot`: el prompt tiene decisiones que costaron encontrarse.
  El 3.6 reemplaza al `gemini-2.5-flash` (se discontinúa el 16/10/2026). Se eligió en oct-2026
  procesando las mismas cartas con 2.5, 3.6, 3.8 y 3.5-lite: el 3.6 sacó los mismos platos y
  precios que el 2.5, dos veces seguidas. El 3.8 también sirve (es el plan B). El 3.5-lite
  quedó descartado porque inventó una carta a partir de un PDF que no era un menú y juntó
  variantes. ⚠️ Si Gemini falla, `MenuProcessorService` pasa **en silencio** a OpenAI Vision,
  que inventa precios: un modelo apagado no da error, da cartas peores.
- **Hosting:** Hostinger (`servime.net`), auto-deploy por GitHub Actions.

## Reglas de trabajo (IMPORTANTE)

- **Nunca commitear sin probar en local primero.** Nunca commitear/pushear de forma
  automática: dejar los cambios listos y que el usuario revise y commitee.
- Un push a `main` dispara deploy a producción automáticamente. Tratar `main` con cuidado.
- Idioma del proyecto y de la comunicación: **español**.
- Secrets (API keys, passwords, datos de servidor) **nunca** en archivos versionados.

## Comandos

```bash
# Desarrollo (server + queue + logs + vite en paralelo)
composer dev

# Tests
php artisan test
composer test            # limpia config y corre tests

# Lint / formato
./vendor/bin/pint        # Laravel Pint (PSR-12)

# Build assets
npm run dev   /   npm run build
```

## Arquitectura

- **Controllers** (`app/Http/Controllers/`): Admin, Comercio, Menu, PublicMenu,
  Chatbot, LandingChat, Cart, Analytics, Dashboard, QuestionCategory, Plan.
- **Planes (multi-tenant):** cada `Comercio` tiene un `Plan` (Free/Mínimo/Básico/Pro/Premium,
  editables en DB desde `/admin/plans`; ninguno está escrito en el código). El plan define cuota
  mensual de mensajes IA (**0 = sin asistente**: el menú entra directo a la carta), tope de
  cartas procesadas, modelo OpenAI, `max_tokens` del chat y features (`Plan::FEATURES`). Free y
  Mínimo se crean **inactivos**: lanzarlos es una decisión (ver la skill `planes`). El super admin asigna
  plan y puede resetear el uso de IA de un comercio (se cobra aparte). La cuota se chequea
  en `ChatbotController` y los helpers viven en `Comercio` (`puedeUsarIA`, `registrarUsoIA`...).
  Feature gating: `analytics` inline en `AnalyticsController`; `excel`/`promociones` vía
  middleware `plan.feature:<feature>` (`EnsurePlanFeature`) en las rutas de menús.
- **Models** (`app/Models/`): User, Comercio, Menu, Cart, CartItem,
  ChatConversation, ChatLanding, QuestionCategory, Plan, Procesamiento.
- **Services** (`app/Services/`): ChatbotService, MenuProcessorService,
  PdfProcessorService, PdfToImageService, GeminiPdfProcessorService.
- **Rutas** (`routes/web.php`):
  - Públicas (sin auth, por `slug`): `/menu/{comercio:slug}` (choose/chat/classic),
    API chat `/api/chat/{slug}`, API carrito `/api/cart/*` (por sesión), buscador de la
    carta `/api/buscar/{slug}`.
  - Auth: gestión de comercios y menús.
  - Admin (`super_admin` + prefijo `/admin`): usuarios, comercios, menús, restore.
- **Roles:** campo `role` en `users`. Middleware alias `super_admin`
  (`App\Http\Middleware\SuperAdminMiddleware`) registrado en `bootstrap/app.php`.

## Seguridad / convenciones ya establecidas

- **Rate limiting.** Las rutas públicas del menú usan limitadores con nombre definidos en
  `AppServiceProvider::configurarRateLimiters()`: `chat-ia` (20/min por dispositivo + 60/min
  por IP), `carrito` (60 + 300) y `tracking` (120 + 600). Van **por dispositivo** (el
  `session_id` del front, por header `X-Session-ID` o por body) porque todos los clientes de
  un local salen por el mismo wifi y con el throttle por IP se comían el cupo entre todos.
  El segundo límite por IP no sobra: el `session_id` lo elige el cliente y se puede rotar.
  Al sumar una ruta pública nueva, usar estos limitadores y no `throttle:N,1`.
  Resto: crear menú `5,1`, landing-chat `20,1` (esas sí son por IP, y está bien).
- **No hay proxy adelante** (verificado: Hostinger/LiteSpeed directo, sin Cloudflare), así que
  `$request->ip()` es la IP real del cliente. No configurar `trustProxies`: haría que la IP
  se pueda falsear por header y con eso se saltearían los límites del chat, que cuesta plata.
- Rutas públicas resuelven comercio por `slug` (binding `{comercio:slug}`), no por id.
- Servir logos valida path-traversal con `realpath` + prefijo permitido (ver `web.php`).
- Menús con SoftDeletes; índices en `menus`; FK en `carts`.
- **Colores (UI):** NUNCA escribir un color a mano ni usar la paleta genérica de Tailwind
  (`amber-500`, `indigo-600`...). Todo pasa por los tokens de `tailwind.config.js`, cuyos
  valores viven en el `:root` de `resources/css/app.css`. Cambiar un color del panel es
  editar una línea ahí. Al tocar estilos, ver la skill `identidad`: tiene los contrastes
  verificados, por qué hay tres terracotas y qué NO se toca (el menú público, el QR y los
  colores del comercio).
- **Confirmaciones/alertas (UI):** usar SIEMPRE los helpers SweetAlert2 definidos en
  `resources/views/layouts/app.blade.php` (`showConfirm`, `showConfirmWithLoader`,
  `showSuccess`, `showError`), NUNCA el `confirm()`/`alert()` nativo del navegador (no matchea
  la estética). Patrón para forms con confirmación:
  `onsubmit="event.preventDefault(); const f=this; showConfirmWithLoader('¿...?', r=>{ if(r) f.submit(); }, 'Procesando...');"`

## Deploy

- Workflow: `.github/workflows/auto-deploy.yml` → ejecuta `deploy-actualiza.sh` por SSH.
- Estructura en servidor: proyecto en `~/servime`, público en
  `~/domains/servime.net/public_html` (su `index.php` apunta a `../../../servime`).
- Tras cambiar `.env` en prod: `php artisan config:clear && php artisan config:cache`.
- Detalle y comandos: ver `DEPLOY_HOSTINGER.md` y `COMANDOS_RAPIDOS_HOSTINGER.md`.

## Producción — pendientes

Checklist y estado de cierre para producción: ver `PRODUCCION_CHECKLIST.md`.
</content>
</invoke>
