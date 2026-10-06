# ✅ Checklist de cierre para producción — ServiME

Estado de las tareas para dejar el sistema listo y estable en producción.
Marcar `[x]` a medida que se completan. Prioridad: 🔴 alta · 🟡 media · 🟢 baja.

---

## 🔴 1. Tests de flujos críticos

- [x] **Baseline verde restaurado** — había 7 tests de Breeze fallando (registro/reset/
      borrado) por reflejar comportamiento viejo. Reescritos para validar lo intencional
      (registro cerrado, notificación custom, borrado deshabilitado). 24/24 ✅.
- [x] **Aislamiento por comercio**: `ComercioIsolationTest` — usuario común no puede
      ver/editar/actualizar/eliminar comercios ajenos (403); super admin sí. 6 tests ✅.
- [x] **Permisos admin**: `AdminAccessTest` — guest→login, usuario común→403,
      super admin→200. 3 tests ✅.
- [x] **Menús**: `MenuManagementTest` — activar deja uno solo activo, baja lógica
      (soft-delete), no se puede activar menú ajeno (403). 3 tests ✅.
- [x] **Carrito público**: `CartTest` — alta/total, remove/clear, rechazo si pedidos
      deshabilitados, aislamiento por sesión. 4 tests ✅.
- [ ] (Opcional) Rutas públicas por slug y chatbot/import con APIs de IA mockeadas.

> Infra de tests agregada: `ComercioFactory`, `MenuFactory`, estado
> `UserFactory::superAdmin()`, trait `HasFactory` en `Comercio`, `Menu`.
> Total actual: **40 tests verdes**.

## 🔴 2. Hardening de producción

**Hecho en código:**
- [x] Forzar HTTPS en producción — `URL::forceScheme('https')` en `AppServiceProvider`
      (solo cuando `APP_ENV=production`).
- [x] `php artisan config:cache && route:cache && view:cache` en el deploy
      — ya presente en `deploy-actualiza.sh` (pasos 10-11).
- [x] Sin secrets versionados — `.env*` ignorado en `.gitignore`; solo `.env.example` trackeado.
- [x] `.env.example` completado con claves de IA (OpenAI/Gemini) y nota de producción.

**Pendiente en el servidor (lo aplica el usuario en el `.env` de prod):**
- [ ] `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://servime.net`.
- [ ] `LOG_LEVEL=error` (hoy en local está en `debug`).
- [ ] Permisos `storage/` y `bootstrap/cache/` correctos; `.env` en `chmod 600`.
- [ ] **Backups automáticos de BD** programados (hPanel o cron `mysqldump`).
- [ ] Tras editar el `.env`: `php artisan config:clear && php artisan config:cache`.

## 🟡 3. Control de costos / resiliencia de IA

**Hecho en código:**
- [x] Fallback amable si la IA falla/tarda — verificado en `ChatbotService` y
      `LandingChatController` (devuelven mensaje al cliente, nunca un 500).
- [x] Timeouts cara al cliente reducidos a 20s (eran 60s chatbot / 30s landing)
      para proteger UX y workers de PHP.
- [x] Costo por llamada acotado: `max_tokens` + modelo `gpt-4o-mini` + rate limiting.

**Cómo está el "corte" HOY (importante):**
El sistema NO tiene un tope monetario (USD). Lo que acota el costo hoy es un corte
por *volumen y tamaño*, todo en código:

| Mecanismo | Valor | Dónde |
|---|---|---|
| Rate limit chat IA | 20/min por dispositivo + 60/min por IP | `throttle:chat-ia` (`AppServiceProvider`) |
| Rate limit carrito | 60 + 300 | `throttle:carrito` |
| Rate limit tracking | 120 + 600 | `throttle:tracking` |
| Rate limit landing chat | 20 req/min por IP | `routes/web.php` |
| Rate limit crear menú (OCR) | 5 req/min | `routes/web.php:113` |
| Modelo chatbot | **según el plan** del comercio | `Comercio::modeloIA()` |
| Máx. tokens respuesta chatbot | **según el plan** | `Comercio::maxTokensChat()` |
| Modelo OCR/carga menú | `gpt-4o` | `.env` → `OPENAI_MODEL` |
| Modelo PDFs | `gemini-3.6-flash` | `.env` → `GEMINI_MODEL` |

→ Esto hace el gasto *predecible*, pero ante un pico de tráfico nada lo frena en
dólares. El tope monetario es lo que falta configurar en los paneles (abajo).

**Tope por comercio (planes):** además existe una **cuota mensual de mensajes IA por
plan** (Básico/Pro/Premium, editable en `/admin/plans`). Al agotarse, el chatbot deja de
llamar a OpenAI y responde un mensaje amable. Es el control de costo más directo y mapea
a la facturación. El super admin puede resetear el uso de un comercio (se cobra aparte).

**Pendiente del usuario (paneles, no código):**
- [ ] **OpenAI** — tope real de gasto. platform.openai.com → Settings → Billing → Limits:
      definir *Hard limit* mensual (corta la API al alcanzarlo) + *Soft limit* (email de
      aviso al ~75%). Recomendado además: Billing → Payment → **desactivar auto-recharge**
      y cargar créditos fijos (cuando se agotan, la API deja de responder = tope más duro).
- [ ] **Google AI / Gemini** — OJO: los Budgets de Google Cloud solo **avisan, no cortan**.
      - Mínimo: console.cloud.google.com → Billing → **Budgets & alerts** → Create budget
        (monto mensual + alertas 50/90/100% por email).
      - Recomendado (cap indirecto real): APIs & Services → Generative Language API →
        **Quotas** → limitar requests por minuto/día.
      - Tope duro de verdad (overkill para esta escala): budget → Pub/Sub → Cloud Function
        que deshabilita el billing del proyecto.

**Nota / a evaluar aparte:**
- Los timeouts de procesamiento de menús (OCR) en `MenuProcessorService` y
  `PdfProcessorService` son largos a propósito (180–600s). Son admin-side; bajarlos
  podría romper la carga de PDFs grandes. Revisar solo si dan problemas.
- [ ] (Opcional) Cachear respuestas frecuentes del chatbot para bajar costo.

## 🟡 4. Verificar que los incendios viejos estén resueltos de raíz

- [x] **Error 500 — RESUELTO DE RAÍZ.** `smalot/pdfparser` estaba en `composer.json`
      pero NO en `composer.lock` (curita: se instaló a mano en prod). El deploy usa
      `composer install` (respeta el lock) → en un deploy limpio el 500 volvía.
      Arreglado: `composer update smalot/pdfparser` sincronizó el lock. Además se
      removió `barryvdh/laravel-dompdf`, un paquete huérfano sin uso en el código.
- [x] **Imágenes 404 — resuelto de raíz.** `uploadPlatoImagen` llama a
      `copyMenuItemToPublicHtml()` tras guardar; las imágenes nuevas se copian solas.
- [x] **Parse error en `ChatbotService` — RESUELTO.** El prompt de `createPrompt()` tenía
      comillas dobles sin escapar (`"  [IMAGEN:url]"`) dentro del string del prompt: cerraban
      el string antes de tiempo y PHP no podía compilar el archivo (fatal no-capturable).
      El chatbot devolvía 500 en cualquier consulta (salvo que OPcache sirviera una copia
      vieja). Escapadas las comillas en las 3 líneas afectadas. Cubierto con test.

> Mejora futura (no bloqueante): la copia a `public_html` usa una ruta hardcodeada
> del servidor. Lo ideal sería `php artisan storage:link` (symlink) en vez de duplicar
> archivos. Refactor para más adelante.

## 🔴 5. Con fecha de vencimiento

- [ ] **Migrar `gemini-2.5-flash` antes del 16/10/2026.** Es el modelo que procesa las
      cartas (`GeminiPdfProcessorService`) y ese día se discontinúa. Si se apaga, no da
      error: `MenuProcessorService` pasa en silencio a OpenAI Vision, que inventa precios.
      - [x] Elegido y probado en local (oct-2026): `gemini-3.6-flash`. Mismos platos y
            precios que el 2.5 en cuatro cartas reales (127, 34, 34 y 86 platos), dos
            corridas. Cuesta ~US$0,04 una carta de 127 platos (~US$0,08 desde enero de
            2027, cuando termina el precio de lanzamiento). Ya es el valor por defecto en
            `config/services.php`.
      - [ ] **En el `.env` de producción:** si tiene `GEMINI_MODEL=gemini-2.5-flash`, ese
            valor manda sobre el del código. Cambiarlo a `gemini-3.6-flash` (o borrar la
            línea) y correr `php artisan config:clear && php artisan config:cache`.
      - [ ] Después del deploy, subir una carta de prueba y revisar platos y precios.

## 🔴 6. Trabajo que existe solo en la máquina local

- [ ] **La rama `feat/portada-menu-publico` nunca se pusheó ni se mergeó a `main`.**
      Al 14/08/2026 son ~29 commits sin upstream: los atajos configurables, el panel de
      analytics completo, el consumo de tokens del chatbot, los fixes de precios e
      imágenes de menús y el round-trip del Excel. Si se pierde el disco, se pierde todo.
      Es el riesgo más grande abierto y el más barato de cerrar.

## 🟡 7. Bugs conocidos, anotados y sin arreglar

- [ ] **El stream del chat cobra cuota aunque OpenAI falle.** En `ChatbotController@chatStream`
      el `try/catch` de `streamMessage()` es interno: si la llamada explota y se responde con
      `generateFallbackResponse()`, igual corre `registrarUsoIA()`. No se gastó un token y el
      comercio pierde un mensaje de su cuota. La ruta sin stream **no** lo tiene — y la que
      usa el front es justo la que cobra de más. Detalle en la skill `planes`.
- [ ] **El aviso de cuota llega tarde.** El dashboard avisa recién cuando se agotó, no al
      acercarse. Al 80% le daría margen al comercio para pedir el upgrade antes de quedarse
      sin asistente en pleno servicio.
- [ ] **Decisión pendiente:** igualar `max_tokens_chat` en ~1500 para los tres planes desde
      `/admin/plans` (sin deploy). Medido: ninguna respuesta real pasó de 497 tokens, así que
      diferenciarlo por plan no le cambia nada al comercio. Ver skill `planes`.
- [ ] **A `/admin/comercios` se llega, pero no se ve que se llega.** Los dos accesos desde el
      dashboard son una tarjeta de métrica (🏪 Comercios, el número) y un "Ver todos →" chico
      dentro de "Comercios Recientes": los dos parecen datos, no navegación, y se termina
      tipeando la URL de memoria. Ahora importa más que antes, porque ahí vive el botón de
      resetear la actividad de un comercio. Falta una entrada explícita en el menú de admin.
- [ ] **`cart_items` no cascadea desde `carts`.** La tabla guarda `cart_id` suelto, sin foreign
      key (ver la migración que la crea). Como `carts` **sí** cascadea desde `comercios`, al
      borrar un comercio se van sus carritos y los ítems quedan colgados para siempre: en la
      base local hay 13 filas huérfanas del 06/07/2026. No rompe nada, es basura que se
      acumula. Se arregla con una migración que agregue la FK con `cascadeOnDelete` y limpie
      lo viejo. El reset de actividad ya los borra a mano para no depender de esto.
- [ ] **El QR de una mesa sirve para pedir desde cualquier lado, para siempre.** El `token`
      de `mesas` es la única credencial y no vence: quien lo fotografíe o guarde la URL
      (`/menu/{slug}?m=<token>`) puede volver desde su casa y abrirle una comanda a esa
      mesa. No hay cobro, así que no es un robo — el daño es que el mozo ve entrar pedidos
      que nadie hizo y **deja de confiar en la pantalla**, que es lo único que no se puede
      permitir. Hoy lo único que lo frena es el limitador `pedir` (6/min por dispositivo,
      40/min por IP), que acota el volumen pero no impide el pedido; y como paliativo
      reactivo, el mozo puede borrar la ronda y el comercio rotar el token de esa mesa
      desde el panel.
      **Defensas evaluadas** (ninguna implementada, decisión del usuario 07/09/2026):
      *(a)* no dejar abrir comanda con el local cerrado, usando `Comercio::estaAbierto()`
      —corta el caso de la madrugada sin fricción para el cliente legítimo, pero no el
      del que jode en horario—; *(b)* un switch por comercio "las mesas las abre el mozo",
      que lo cierra del todo a cambio de perder lo que da valor al producto: que el cliente
      pida sin esperar a nadie. **Descartadas:** rotar tokens automáticamente (rompe todos
      los carteles impresos) y filtrar por la IP del local (los clientes usan datos
      móviles, no el wifi: falsos positivos masivos).

## 🟢 8. Limpieza y cierre

- [x] Limpieza de archivos `.md` redundantes (12 → 4).
- [x] CLAUDE.md creado.
- [x] **Rotar la API key de Gemini** expuesta — rotada por el usuario (local + servidor).
- [x] `.env.example` actualizado con todas las claves necesarias.
- [ ] Push de los commits locales pendientes (esperando que Hostinger vuelva).

---

## Estado general (14/08/2026)

Tareas 1, 2, 3 y 4 cerradas en lo que toca al código; la suite está en **201 tests verdes**.

Lo abierto, por orden de urgencia:

1. 🔴 **Pushear la rama** — ~29 commits que solo existen en local (sección 6).
2. 🔴 **Migrar Gemini antes del 16/10/2026** — quedan ~2 meses (sección 5).
3. 🔴 **Topes de gasto** en los paneles de OpenAI y Google AI (sección 3).
4. 🔴 **Backups automáticos de BD** y `.env` de producción (sección 2).
5. 🟡 Bugs anotados: cuota cobrada de más en el stream, aviso de cuota tardío (sección 7).

Ideas sin arrancar (no son deuda, son features nuevas):

- **Pedir reseña en Google.** Hoy el sistema no puede: no capta datos de contacto del
  comensal (el carrito lo identifica por `session_id` anónimo y no hay checkout), no hay
  canal de notificación hacia él, no hay campo donde guardar el link, y el chat no
  renderiza enlaces (`parseMarkdown` solo hace negrita, itálica, `[TAGS:]` e `[IMAGEN:]`).
  Camino corto: campo `google_review_url` + un marcador propio tipo `[[RESEÑA]]` que el
  front convierta en botón, reusando la cañería de `[[PLATOS]]`. **No** habilitar links
  markdown genéricos: dejaría entrar como enlace clickeable cualquier URL alucinada por el
  modelo o colada en el texto de un menú importado.
- **Disponibilidad / sin stock de un plato.** Diseñado el 14/08/2026, **pendiente de
  implementar**. Ver el detalle abajo.

---

## 📋 Pendiente diseñado: disponibilidad de platos ("hoy no hay")

Decidido el 14/08/2026, sin escribir una línea de código todavía. Se anota completo para
no volver a discutirlo desde cero.

**El problema.** Hoy no existe el concepto: un plato está en el menú o no está. El
asistente recomienda con total seguridad algo que en la cocina se terminó hace dos horas
—y si es plato estrella, lo recomienda **primero**—, con foto y precio, que le da al
cliente la sensación de que está garantizado. La IA vende lo que el local no tiene.

**1. El dato NO va en el JSON del menú.** El import de Excel rearma cada plato desde cero
y borra lo que no viaje en una columna (así se perdían las estrellas, arreglado en
`5eb1676`). Acá sería peor: este dato cambia varias veces por día. Va en tabla aparte,
`plato_disponibilidad` (`comercio_id`, `plato_id`, `disponible_hasta`, `motivo`), colgada
del id numérico del plato que ya asigna `MenuController` (~L479). Al reimportar hay que
**limpiar las marcas huérfanas**: si la planilla vino sin la columna ID los ids se
reasignan, y es mejor perder la marca que apuntar al plato equivocado.

**2. Tiene que apagarse solo.** Por defecto vence al cierre del servicio. Si "sin stock"
dura hasta que alguien lo desmarque, en dos semanas media carta está marcada, nadie confía
y el comercio deja de usarlo. Un dato que miente con confianza es peor que no tener el
dato: es el mismo problema de vuelta.

**3. Se marca desde el celular, en dos toques.** Quien sabe que se acabaron las milanesas
es el mozo a las 21:30, no el dueño en la compu. Lista con switch y buscador arriba. Si
tarda más que unos segundos no lo usa nadie y la data se pudre.

**4. El asistente tiene que SABERLO, no ignorarlo.** Si el plato simplemente se saca de
`platosDelMenu()`, el bot responde "no tenemos milanesas" y el cliente entiende que el
local **nunca** tiene: peor que el problema original. El plato entra al prompt **marcado**,
con instrucción de convertirlo en venta ("hoy nos quedamos sin milanesas, pero tengo un
lomo que va parecido"). Ahí está el valor: el "no" pasa a ser una recomendación.
- Tocar también `platosQueCoinciden()`, que valida el marcador `[[FALTA]]`: si un plato sin
  stock deja de matchear, se cuenta como *demanda insatisfecha*. "No lo vendo" y "hoy se
  acabó" son cosas distintas, y mezclarlas ensucia el widget de sugerencias que ya anda.
- Las tarjetas de plato no deberían salir con botón de agregar.

**5. Menú clásico y carrito.** Atenuado con "hoy no disponible", **no escondido**: si se
esconde, el cliente que lo vino a buscar scrollea y no entiende nada. En el carrito,
bloquear el alta y avisarle al cliente si ya lo tenía adentro — nunca borrárselo por atrás.

**6. De regalo, dato de compras.** "Cuántas veces te pidieron algo que no tenías" es
información de proveedor (si las milanesas se acaban 4 de 5 viernes, se está comprando
mal). Se engancha con `AnalyticsInsightsService`, que ya hace demanda insatisfecha.

**❌ Lo que se descartó: stock con cantidades reales.** El carrito es de *pre-pedido*:
nadie confirma desde la cocina y no hay integración con el punto de venta. Un contador que
arranca en 12 milanesas se desincroniza el primer día y queda un número que miente con
precisión. Binario + vencimiento automático es lo honesto y lo que se va a usar.

**Primer corte que ya sirve:** tabla + switch en el panel + plato marcado en el prompt y en
el menú clásico + auto-reset diario. Carrito y analytics en una segunda vuelta.
</content>
