# CLAUDE.md — ServiME

Guía para Claude Code al trabajar en este repo. Mantener conciso y actualizado.

## Qué es

SaaS de **menús digitales QR** para comercios gastronómicos. Un comercio sube su menú
(PDF/imagen), la IA extrae los platos, y los clientes acceden por QR a un menú con
chatbot, carrito de pre-pedido y analytics. Multi-tenant por comercio, multi-rol.

- **Stack:** Laravel 12, PHP 8.2, MySQL, Blade + Tailwind (Vite), Breeze (auth).
- **IA:** OpenAI (`gpt-4o`, chatbot/OCR) y Gemini (`gemini-2.5-flash`, procesa PDFs).
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
  Chatbot, LandingChat, Cart, Customization, Analytics, Dashboard, QuestionCategory.
- **Models** (`app/Models/`): User, Comercio, Menu, Cart, CartItem,
  ChatConversation, ChatLanding, QuestionCategory.
- **Services** (`app/Services/`): ChatbotService, MenuProcessorService,
  PdfProcessorService, PdfToImageService, GeminiPdfProcessorService.
- **Rutas** (`routes/web.php`):
  - Públicas (sin auth, por `slug`): `/menu/{comercio:slug}` (choose/chat/classic),
    API chat `/api/chat/{slug}`, API carrito `/api/cart/*` (por sesión).
  - Auth: gestión de comercios y menús.
  - Admin (`super_admin` + prefijo `/admin`): usuarios, comercios, menús, restore.
- **Roles:** campo `role` en `users`. Middleware alias `super_admin`
  (`App\Http\Middleware\SuperAdminMiddleware`) registrado en `bootstrap/app.php`.

## Seguridad / convenciones ya establecidas

- Rate limiting en endpoints calientes: chat IA `throttle:20,1`, carrito `60,1`,
  crear menú `5,1`, landing-chat `20,1`.
- Rutas públicas resuelven comercio por `slug` (binding `{comercio:slug}`), no por id.
- Servir logos valida path-traversal con `realpath` + prefijo permitido (ver `web.php`).
- Menús con SoftDeletes; índices en `menus`; FK en `carts`.

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
