# ServiME

**Menús digitales con QR y un asistente con IA para restaurantes y bares.**

El comercio sube su carta en PDF o foto, la IA extrae los platos y los precios, y el cliente
escanea un QR en la mesa: ve la carta, le pregunta al asistente qué pedir y arma el pedido.

**Demo en vivo:** [servime.net](https://servime.net) · [menú de demostración](https://servime.net/menu/live-demo)

SaaS en producción, multi-tenant (cada comercio con su plan) y multi-rol.

---

## Qué hace

**Para el cliente en la mesa**
- Carta con fotos, buscador y filtros por etiqueta (veggie, sin TACC…).
- Asistente con IA que recomienda platos de la carta real, con tarjetas para sumar al pedido.
- Notas de voz: se mantiene apretado el micrófono, se habla y el asistente responde.
- Pedido por mesa (el QR identifica la mesa) y guía de uso la primera vez.
- Varios idiomas.

**Para el comercio**
- Carga de la carta desde PDF o fotos: la IA la lee y la convierte en categorías y platos editables.
- Editor de platos, precios, fotos, etiquetas, promociones y "platos estrella".
- Portada propia: la foto que sube el comercio genera los colores del menú.
- Estadísticas: qué preguntan los clientes, qué piden que no está en la carta, clics y pedidos.
- QR por mesa, importación y exportación por Excel.

**Para el administrador**
- Planes editables (cuota mensual de mensajes de IA, funciones habilitadas, modelo de IA).
- Gestión de usuarios, comercios y menús.

## Stack

| Capa | Tecnología |
|---|---|
| Backend | PHP 8.2, Laravel 12, MySQL/MariaDB, colas de Laravel |
| Frontend | Blade, Tailwind CSS, Alpine.js, Vite |
| IA | OpenAI (chat, transcripción de voz) · Google Gemini (lectura de cartas) |
| Otros | Intervention Image, Laravel Excel, endroid/qr-code, smalot/pdfparser |
| Deploy | GitHub Actions → Hostinger por SSH |

## Decisiones de diseño

Algunas de las decisiones que más pesaron, todas medidas antes de tomarlas:

- **La búsqueda la hace PHP, no el modelo.** Antes de llamar a la IA, el backend busca en la
  carta las palabras del cliente (plurales, tildes, errores de tipeo, jerga como "birra")
  y le pasa los platos ya encontrados. Un modelo chico no encuentra bien un plato suelto en un
  menú largo; con esto pasó de 6/8 a 22/22 aciertos en el banco de pruebas.
- **El orden del prompt importa.** Con la carta al final del prompt, el asistente negaba
  platos que tenía delante. La carta va primero, las reglas después, y lo que cambia en cada
  mensaje va aparte, para aprovechar el cacheo de prompts (más del 90% de los tokens de
  entrada salen de caché).
- **Las tarjetas salen de un marcador, no del texto.** El modelo cierra cada respuesta con
  `[[PLATOS: …]]`; el backend lo valida contra la carta y arma las tarjetas con precio y foto.
  Así el cliente nunca recibe un plato que no existe o con un precio inventado.
- **El modelo se eligió con un banco de preguntas reales de clientes**, corriendo cada caso
  3 veces. El más caro (gpt-4o) fallaba en dietas; el elegido acierta y cuesta menos gracias
  al caché.
- **Límites por dispositivo y no solo por IP.** Todos los clientes de un local salen por el
  mismo wifi: un límite por IP los bloqueaba entre sí. Hay un segundo límite por IP como red
  de seguridad, y los robots no llegan a la IA (cada consulta cuesta).
- **La lectura de cartas va por cola**, con estados (procesando / listo / error) y reintento.

## Calidad

- **633 tests** (PHPUnit): flujos de pedido, aislamiento entre comercios, cuotas,
  búsqueda en la carta, límites, planes y permisos.
- Formato con Laravel Pint (PSR-12).

```bash
php artisan test
```

## Correrlo en local

Requisitos: PHP 8.2+, Composer, Node 18+, y una base MySQL/MariaDB (o SQLite).

```bash
git clone https://github.com/Leohayequipo/servime.git
cd servime
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan storage:link
composer dev          # servidor + cola + logs + Vite
```

Variables a completar en `.env`:

| Variable | Para qué |
|---|---|
| `OPENAI_API_KEY` | El asistente del chat y la transcripción de voz |
| `GEMINI_API_KEY` | La lectura de cartas en PDF o foto |
| `AUTH_PASSWORD_INICIAL` | Contraseña con la que se crean las cuentas desde el admin (obligatoria) |
| `DB_*`, `MAIL_*` | Base de datos y correo |

Sin las claves de IA la app funciona igual, pero sin asistente ni lectura automática de cartas.

## Estructura

```
app/
  Http/Controllers/   Menú público, chat, carrito, panel del comercio, admin, estadísticas
  Services/           ChatbotService (prompt y búsqueda), MenuProcessorService (lectura de
                      cartas), TranscripcionService (voz), BrandThemeService (colores), …
  Models/             Comercio, Menu, Plan, ChatConversation, Comanda, Mesa, …
resources/views/      Blade: menú público, chat, editor del comercio, panel
tests/Feature/        Tests por funcionalidad
```

## Deuda técnica conocida

- `resources/views/menus/create.blade.php` (el editor del menú) concentra demasiado: vista
  y JavaScript en un solo archivo grande. El próximo paso es separarlo en componentes.
- Parte del historial de commits es anterior a la convención actual (`feat(...)`, `fix(...)`).
