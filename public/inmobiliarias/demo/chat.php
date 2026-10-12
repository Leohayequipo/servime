<?php

/**
 * Chat de la demo para inmobiliarias (servime.net/inmobiliarias/demo/).
 *
 * Es la única pieza con servidor de esta carpeta, y la carpeta entera es una PRUEBA: no hay
 * ruta, controller ni config de Laravel que la mencione. Laravel se arranca acá solo para
 * LEER la configuración que ya existe (API key, modelo del chat de la landing, WhatsApp de
 * soporte), así ninguna clave queda escrita en un archivo versionado.
 *
 *   GET  chat.php  → { whatsapp }                      número de soporte para el botón
 *   POST chat.php  { message, history[], slug? } → { response, propiedades[] }
 *
 * Para borrar la demo alcanza con borrar la carpeta public/inmobiliarias/. Ojo: el deploy
 * copia public/ a public_html pero nunca borra, así que si ya se subió hay que borrarla
 * también en el servidor.
 */

declare(strict_types=1);

const MAX_MENSAJE = 500;
const MAX_TURNO_HISTORIAL = 2000;
const MAX_HISTORIAL = 10;
const MAX_CUERPO = 40000;

// El chat es público y no tiene un comercio con cuota detrás: estos dos topes son lo único
// que separa a la demo de una factura de OpenAI sin techo.
const TOPE_POR_MINUTO_IP = 10;
const TOPE_DIARIO_TOTAL = 300;

const MSG_ERROR = 'Uy, no pude responder ahora. Probá de nuevo en un ratito o escribinos por WhatsApp.';
const MSG_TOPE_MINUTO = 'Vas muy rápido 🙂 Esperá un minuto y seguimos.';
const MSG_TOPE_DIA = 'La demo llegó a su límite de consultas por hoy. Si querés verla en acción, escribinos por WhatsApp y te la mostramos.';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Robots-Tag: noindex, nofollow');

function responder(array $datos, int $status = 200): never
{
    http_response_code($status);
    echo json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

/**
 * Laravel vive en lugares distintos según dónde se sirva la carpeta:
 *  - en local, dentro del proyecto: public/inmobiliarias/demo → tres niveles arriba;
 *  - en Hostinger, public_html está en ~/domains/servime.net/public_html y el proyecto en
 *    ~/servime (el mismo camino que usa el index.php de public_html, dos niveles más abajo).
 */
function basePathLaravel(): ?string
{
    foreach ([__DIR__.'/../../..', __DIR__.'/../../../../../servime'] as $candidato) {
        if (is_file($candidato.'/bootstrap/app.php') && is_file($candidato.'/vendor/autoload.php')) {
            return realpath($candidato) ?: null;
        }
    }

    return null;
}

/** Un pedido desde otro sitio no tiene por qué gastar la API de la demo. */
function origenPermitido(): bool
{
    $origen = $_SERVER['HTTP_ORIGIN'] ?? '';

    if ($origen === '') {
        return true;
    }

    return parse_url($origen, PHP_URL_HOST) === parse_url('http://'.($_SERVER['HTTP_HOST'] ?? ''), PHP_URL_HOST);
}

function formatoPrecio(?float $monto, string $moneda): string
{
    if ($monto === null) {
        return 'a consultar';
    }

    $numero = number_format($monto, 0, ',', '.');

    return $moneda === 'USD' ? "USD {$numero}" : "\${$numero}";
}

/** Una línea por propiedad: el modelo encuentra mejor un dato en una lista que en párrafos. */
function lineaPropiedad(array $p): string
{
    $operaciones = ['venta' => 'Venta', 'alquiler' => 'Alquiler', 'temporario' => 'Alquiler temporario'];
    $tipos = ['departamento' => 'Departamento', 'casa' => 'Casa', 'ph' => 'PH'];
    $comodidades = [
        'pileta' => 'pileta', 'parrilla' => 'parrilla', 'balcon' => 'balcón', 'patio' => 'patio',
        'terraza' => 'terraza', 'jardin' => 'jardín', 'gimnasio' => 'gimnasio',
        'seguridad' => 'seguridad 24 h', 'sum' => 'SUM', 'lavadero' => 'lavadero',
    ];
    $c = $p['caracteristicas'] ?? [];
    $aptoCredito = ($c['apto_credito'] ?? false) ? 'sí' : 'no';
    $mascotas = ($c['mascotas'] ?? false) ? 'sí' : 'no';
    $amoblado = ($c['amoblado'] ?? false) ? 'sí' : 'no';

    $precio = formatoPrecio($p['precio'] ?? null, $p['moneda'] ?? 'ARS')
        .(($p['precio_periodo'] ?? null) === 'mes' ? ' por mes' : '');
    $expensas = ($p['expensas'] ?? null) ? 'Expensas '.formatoPrecio((float) $p['expensas'], 'ARS').' por mes' : 'Sin expensas aparte';
    $ambientes = $p['ambientes'] === 1 ? 'monoambiente' : "{$p['ambientes']} ambientes";
    $antiguedad = $p['antiguedad'] === 0 ? 'a estrenar' : "{$p['antiguedad']} años de antigüedad";
    $lista = implode(', ', array_map(fn ($a) => $comodidades[$a] ?? $a, $p['amenities'] ?? [])) ?: 'ninguna';

    return "[{$p['slug']}] {$p['titulo']}"
        ." | {$operaciones[$p['operacion']]} | {$tipos[$p['tipo']]}"
        ." | {$p['barrio']}, {$p['localidad']}"
        ." | Precio: {$precio} | {$expensas}"
        .(($p['nota_precio'] ?? null) ? " | Nota: {$p['nota_precio']}" : '')
        ." | {$ambientes}, {$p['dormitorios']} dormitorios, {$p['banos']} baños, {$p['cocheras']} cocheras"
        ." | {$p['m2_cubiertos']} m² cubiertos, {$p['m2_totales']} m² totales | {$antiguedad}"
        ." | Apto crédito: {$aptoCredito} | Acepta mascotas: {$mascotas}"
        ." | Amoblado: {$amoblado} | Comodidades: {$lista}"
        ." | Código: {$p['codigo']}"
        ." | Descripción: {$p['descripcion']}";
}

/**
 * Las propiedades van PRIMERO y las reglas después: es la lección del asistente de menús
 * (ver la skill `chatbot`). Con el dato al final, el modelo negaba cosas que tenía delante.
 */
function promptSistema(array $datos, ?array $mirando): string
{
    $inmo = $datos['inmobiliaria'];
    $lineas = implode("\n", array_map('lineaPropiedad', $datos['propiedades']));

    $contexto = $mirando
        ? "\n\nLA PERSONA ESTÁ MIRANDO AHORA LA FICHA DE: [{$mirando['slug']}] {$mirando['titulo']}.\n"
            .'Si pregunta "cuánto sale", "tiene cochera", "cuántos m² tiene", etc., se refiere a esta propiedad.'
        : '';

    return <<<PROMPT
Sos {$inmo['asistente']}, la asistente virtual de {$inmo['nombre']} ({$inmo['tagline']}). Atendés a personas que miran las propiedades desde el celular; muchas llegaron escaneando el QR del cartel de una propiedad.

PROPIEDADES DISPONIBLES (son TODAS las que hay, no existen otras):
{$lineas}{$contexto}

REGLAS
- Respondé solo con los datos de la lista. Si te preguntan algo que no figura (si se puede negociar el precio, qué garantías aceptan además de las indicadas, horarios de visita, estado del edificio, impuestos), decí que eso lo confirma un asesor y ofrecé escribir por WhatsApp. Nunca inventes un dato.
- Los precios van tal cual figuran, con su moneda y su período. No conviertas monedas ni calcules cuotas.
- Si buscan algo que no hay (otro barrio, otro presupuesto, más ambientes), decilo claro y sugerí la más parecida explicando en qué se diferencia.
- La dirección exacta no se publica: se pasa al coordinar la visita.
- Si quieren visitar, reservar u ofertar, invitá a escribir por WhatsApp con el botón verde.
- Si preguntan algo que no tiene que ver con propiedades, respondé en una línea que solo podés ayudar con las propiedades de {$inmo['nombre']}.
- Español rioplatense (vos), cálido y profesional. Respuestas cortas: 2 a 4 oraciones. Sin títulos, listas largas ni tablas. Podés usar **negrita** para precios y datos clave.
- Al final de CADA respuesta, en una línea aparte, escribí [[PROPS: slug1, slug2]] con las propiedades que mencionaste o recomendaste (máximo 3). Si no mencionaste ninguna, escribí [[PROPS:]].
PROMPT;
}

/** Saca el marcador del texto y devuelve solo los slugs que existen. */
function separarMarcador(string $texto, array $slugsValidos): array
{
    $slugs = [];

    if (preg_match('/\[\[\s*PROPS\s*:([^\]]*)\]\]/i', $texto, $m)) {
        foreach (preg_split('/[\s,]+/', $m[1], -1, PREG_SPLIT_NO_EMPTY) as $slug) {
            if (in_array($slug, $slugsValidos, true) && ! in_array($slug, $slugs, true)) {
                $slugs[] = $slug;
            }
        }
    }

    // También se borra un marcador incompleto o mal escrito: no puede llegar a la pantalla.
    $limpio = trim(preg_replace('/\[\[\s*PROPS.*$/is', '', $texto));

    return [$limpio, array_slice($slugs, 0, 3)];
}

// ── Arranque ──────────────────────────────────────────────────────────────────

$metodo = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if (! in_array($metodo, ['GET', 'POST'], true)) {
    responder(['error' => 'Método no permitido'], 405);
}

$base = basePathLaravel();

if ($base === null) {
    responder(['response' => MSG_ERROR, 'propiedades' => []], 503);
}

require $base.'/vendor/autoload.php';
$app = require $base.'/bootstrap/app.php';
$app->instance('request', Illuminate\Http\Request::capture());
$app->make(Illuminate\Contracts\Http\Kernel::class)->bootstrap();

if ($metodo === 'GET') {
    $whatsapp = preg_replace('/\D/', '', (string) config('services.support.whatsapp'));
    responder(['whatsapp' => $whatsapp !== '' ? $whatsapp : null]);
}

// ── POST: una consulta al chat ────────────────────────────────────────────────

if (! origenPermitido()) {
    responder(['error' => 'Origen no permitido'], 403);
}

$cuerpo = file_get_contents('php://input', false, null, 0, MAX_CUERPO + 1);

if ($cuerpo === false || strlen($cuerpo) > MAX_CUERPO) {
    responder(['error' => 'Pedido demasiado grande'], 413);
}

$entrada = json_decode($cuerpo, true);
$mensaje = is_array($entrada) ? trim((string) ($entrada['message'] ?? '')) : '';

if ($mensaje === '' || mb_strlen($mensaje) > MAX_MENSAJE) {
    responder(['response' => 'Escribime tu consulta (hasta 500 caracteres).', 'propiedades' => []], 422);
}

$datos = json_decode((string) file_get_contents(__DIR__.'/propiedades.json'), true);

if (! is_array($datos) || empty($datos['propiedades'])) {
    responder(['response' => MSG_ERROR, 'propiedades' => []], 500);
}

$porSlug = array_column($datos['propiedades'], null, 'slug');
$mirando = $porSlug[(string) ($entrada['slug'] ?? '')] ?? null;

// Los topes van en la caché de archivos y no en la de la base: la demo no toca la base.
// RateLimiter respeta el vencimiento de cada clave, así que los contadores se borran solos.
$limitador = new Illuminate\Cache\RateLimiter(Illuminate\Support\Facades\Cache::store('file'));
$claveIp = 'demo-inmobiliaria:ip:'.sha1((string) ($_SERVER['REMOTE_ADDR'] ?? ''));
$claveDia = 'demo-inmobiliaria:dia:'.date('Y-m-d');

if ($limitador->tooManyAttempts($claveIp, TOPE_POR_MINUTO_IP)) {
    responder(['response' => MSG_TOPE_MINUTO, 'propiedades' => []], 429);
}

if ($limitador->tooManyAttempts($claveDia, TOPE_DIARIO_TOTAL)) {
    responder(['response' => MSG_TOPE_DIA, 'propiedades' => [], 'tope' => true]);
}

$apiKey = config('services.openai.api_key');

if (empty($apiKey)) {
    responder(['response' => MSG_ERROR, 'propiedades' => []], 503);
}

$limitador->hit($claveIp, 60);
$limitador->hit($claveDia, 60 * 60 * 26);

$mensajes = [['role' => 'system', 'content' => promptSistema($datos, $mirando)]];

$historial = is_array($entrada['history'] ?? null) ? array_slice($entrada['history'], -MAX_HISTORIAL) : [];
foreach ($historial as $turno) {
    $rol = $turno['role'] ?? null;
    $contenido = is_string($turno['content'] ?? null) ? mb_substr($turno['content'], 0, MAX_TURNO_HISTORIAL) : '';

    if (in_array($rol, ['user', 'assistant'], true) && $contenido !== '') {
        $mensajes[] = ['role' => $rol, 'content' => $contenido];
    }
}

// Recordatorio de una línea al final: lo último que lee el modelo es lo que más pesa.
$mensajes[] = [
    'role' => 'user',
    'content' => $mensaje."\n\n(Recordatorio interno: solo datos de la lista; cerrá con [[PROPS: ...]].)",
];

// El body cambia según la familia del modelo (GPT-5 rechaza los parámetros de GPT-4). Se usa
// el trait que ya comparten el chat del menú y el de la landing, sin modificarlo.
$armador = new class
{
    use App\Support\ArmaPayloadOpenAI;

    public function armar(string $modelo, array $mensajes, int $maxTokens): array
    {
        return $this->payloadOpenAI($modelo, $mensajes, $maxTokens, false, ['temperature' => 0.4]);
    }
};

$modelo = (string) config('services.openai.landing_model');
$payload = $armador->armar($modelo, $mensajes, (int) config('services.openai.landing_max_tokens', 900));

try {
    $respuesta = Illuminate\Support\Facades\Http::withToken($apiKey)
        ->timeout(25)
        ->post(rtrim((string) config('services.openai.base_url', 'https://api.openai.com/v1'), '/').'/chat/completions', $payload);

    if (! $respuesta->successful()) {
        Illuminate\Support\Facades\Log::warning('[demo-inmobiliaria] OpenAI respondió con error', [
            'status' => $respuesta->status(),
            'body' => substr($respuesta->body(), 0, 500),
        ]);
        responder(['response' => MSG_ERROR, 'propiedades' => []]);
    }

    $texto = trim((string) $respuesta->json('choices.0.message.content', ''));

    if ($texto === '') {
        responder(['response' => MSG_ERROR, 'propiedades' => []]);
    }

    [$limpio, $slugs] = separarMarcador($texto, array_keys($porSlug));

    responder(['response' => $limpio, 'propiedades' => $slugs]);
} catch (Throwable $e) {
    Illuminate\Support\Facades\Log::warning('[demo-inmobiliaria] '.$e->getMessage());
    responder(['response' => MSG_ERROR, 'propiedades' => []]);
}
