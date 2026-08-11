<?php

namespace App\Services;

use App\Models\ChatConversation;
use App\Models\Comercio;

/**
 * Insights derivados del chat: intención de las consultas (rule-based) y
 * sugerencias accionables para el comercio. Sin costo de IA.
 */
class AnalyticsInsightsService
{
    /**
     * Categorías de intención -> palabras clave (normalizadas, sin acentos).
     */
    private const INTENTS = [
        'Precio' => ['precio', 'precios', 'cuesta', 'cuestan', 'vale', 'valen', 'cuanto', 'cuanta', 'valor', 'sale', 'salen'],
        'Horario' => ['horario', 'horarios', 'abren', 'cierran', 'abierto', 'cerrado', 'hora', 'atienden', 'atencion'],
        'Envío / Delivery' => ['envio', 'envios', 'delivery', 'domicilio', 'reparto', 'llevan', 'envian', 'mandan'],
        'Reservas' => ['reserva', 'reservas', 'reservar', 'mesa', 'mesas', 'turno', 'turnos'],
        'Medios de pago' => ['tarjeta', 'efectivo', 'mercadopago', 'transferencia', 'debito', 'credito', 'pago', 'pagar', 'pagos'],
        'Dieta / Restricciones' => ['tacc', 'celiaco', 'celiaca', 'celiacos', 'vegano', 'vegana', 'veganos', 'vegetariano', 'vegetariana', 'gluten', 'lactosa', 'diabetico', 'apto', 'aptos'],
        // El voseo va explícito: la búsqueda exige palabra completa, así que "recomendás"
        // no matcheaba con "recomenda" y "¿qué me recomendás?" —la consulta más común de
        // todas por acá— caía en "Otras".
        'Recomendación' => ['recomenda', 'recomendas', 'recomendame', 'recomiendas', 'recomienda', 'recomiendan', 'sugeri', 'sugeris', 'sugiere', 'conviene', 'mejor', 'rico', 'rica'],
        'Ubicación' => ['donde', 'direccion', 'ubicados', 'ubicacion', 'llego', 'mapa', 'quedan', 'estan'],
    ];

    /**
     * Preguntas por COMIDA. Va aparte y se evalúa al final, como red de contención.
     *
     * Es la consulta más frecuente de un menú digital —"tienen rabas", "qué hay dulce",
     * "listame fritos"— y no tenía categoría: el 84% de los mensajes caía en "Otras" y el
     * widget no servía para nada. No está en INTENTS porque sus palabras son tan generales
     * ("hay", "tienen", "quiero") que le ganarían a categorías más específicas: alguien que
     * escribe "¿tienen delivery?" pregunta por el envío, no por un plato.
     */
    private const INTENT_COMIDA = 'Buscan algo para comer';

    private const KEYWORDS_COMIDA = [
        'tienen', 'tenes', 'tenés', 'hay', 'quiero', 'quisiera', 'dame', 'traeme', 'busco',
        'listame', 'mostrame', 'muestrame', 'comer', 'tomar', 'plato', 'platos', 'comida',
        'dulce', 'salado', 'postre', 'postres', 'bebida', 'bebidas', 'picar', 'compartir',
        'desayuno', 'merienda', 'almuerzo', 'cena', 'menu', 'carta',
    ];

    /**
     * Categorías válidas de intención (para el clasificador con IA).
     *
     * @return array<int, string>
     */
    public static function categorias(): array
    {
        return [...array_keys(self::INTENTS), self::INTENT_COMIDA, 'Otras'];
    }

    /**
     * Distribución de intención de los mensajes (últimos 30 días).
     */
    public function intents(Comercio $comercio): array
    {
        // Solo lo que tipeó el cliente: los atajos de la interfaz mandan un texto armado
        // que no representa una intención propia y sesgaba el conteo.
        $convs = ChatConversation::forComercio($comercio->id)
            ->recent(30)
            ->escritas()
            ->get(['user_message', 'intent', 'platos_sugeridos']);

        $chatbot = app(ChatbotService::class);

        $counts = array_fill_keys(self::categorias(), 0);
        // Cuántas de cada tema quedaron sin nada que ofrecer. Un tema con muchas consultas
        // y muchos ceros es una carta incompleta o mal etiquetada, y es más accionable que
        // el conteo a secas: "de 10 consultas por dieta, en 7 no encontró nada".
        $sinResolver = array_fill_keys(self::categorias(), 0);
        $total = 0;
        $ia = 0; // cuántos fueron clasificados con IA (intent guardado)

        foreach ($convs as $c) {
            if (empty($c->user_message)) {
                continue;
            }
            $total++;

            // Si ya tiene intención guardada (IA) la usamos; si no, rule-based en vivo.
            if (! empty($c->intent) && isset($counts[$c->intent])) {
                $intent = $c->intent;
                $ia++;
            } else {
                $intent = $this->ruleIntent($this->normalizar((string) $c->user_message));

                // Última red: muchos clientes escriben SOLO el nombre del plato ("rabas",
                // "budines", "colita de rana"), sin ningún verbo que matchear. Si el texto
                // coincide con algo de la carta, obviamente están preguntando por comida.
                if ($intent === 'Otras' && $chatbot->tieneEnElMenu($comercio, (string) $c->user_message)) {
                    $intent = self::INTENT_COMIDA;
                }
            }

            $counts[$intent]++;
            if ($c->platos_sugeridos === 0) {
                $sinResolver[$intent]++;
            }
        }

        arsort($counts);

        return ['total' => $total, 'counts' => $counts, 'ia' => $ia, 'sinResolver' => $sinResolver];
    }

    /**
     * Intención rule-based: primera categoría cuyas keywords matchean (una por mensaje).
     */
    private function ruleIntent(string $norm): string
    {
        foreach (self::INTENTS as $name => $kws) {
            if (preg_match('/\b('.implode('|', $kws).')\b/', $norm)) {
                return $name;
            }
        }

        // Al final y no antes: "¿tienen delivery?" es una consulta de envío, no de comida,
        // aunque empiece con "tienen".
        if (preg_match('/\b('.implode('|', self::KEYWORDS_COMIDA).')\b/', $norm)) {
            return self::INTENT_COMIDA;
        }

        return 'Otras';
    }

    /**
     * Consultas donde el bot NO pudo recomendar ningún plato: lo que le piden al comercio
     * y no tiene, o no está en la carta.
     *
     * El dato sale del marcador [[PLATOS: ...]] que el modelo declara en cada respuesta
     * (guardado en `platos_sugeridos`), así que funciona igual en cualquier idioma: no
     * depende de buscar frases como "no está disponible".
     *
     * Solo mira mensajes ESCRITOS por el cliente y con el contador ya cargado
     * (las filas viejas tienen NULL = no sabemos, y no cuentan).
     *
     * El filtro `escritas()` no es cosmético: al tocar "Ver más opciones" la interfaz
     * reenvía la misma consulta y el modelo vuelve a declarar el mismo faltante, así
     * que sin el filtro un solo pedido del cliente contaba dos veces.
     *
     * **Se descarta lo que SÍ está en el menú.** El faltante lo declara el modelo, y el
     * modelo se equivoca: llegó a anotar "rabas" seis veces teniendo "- Rabas — $26.000" en
     * la carta. Mostrarle eso al comercio es peor que no mostrarle nada — el panel le decía
     * "quizás valga la pena sumarlo" sobre algo que ya vende. Se filtra con la misma
     * búsqueda que usa el chat, y lo descartado no se pierde: es una falla NUESTRA y se
     * consulta con `faltantesQueSiEstan()` para el panel de admin.
     *
     * @return array<int, array{consulta:string, veces:int}>
     */
    public function demandaInsatisfecha(Comercio $comercio, int $limit = 10): array
    {
        return $this->faltantes($comercio, $limit, enElMenu: false);
    }

    /**
     * Lo contrario: faltantes que el asistente declaró pero que SÍ están en la carta.
     *
     * No es demanda del cliente, es el asistente fallando. Va al panel del super admin como
     * métrica de calidad: si esto crece, algo se rompió en el prompt o en el modelo.
     *
     * @return array<int, array{consulta:string, veces:int}>
     */
    public function faltantesQueSiEstan(Comercio $comercio, int $limit = 10): array
    {
        return $this->faltantes($comercio, $limit, enElMenu: true);
    }

    /**
     * @return array<int, array{consulta:string, veces:int}>
     */
    private function faltantes(Comercio $comercio, int $limit, bool $enElMenu): array
    {
        $declarados = ChatConversation::forComercio($comercio->id)
            ->recent(30)
            ->escritas()
            ->whereNotNull('falta')
            ->pluck('falta')
            ->filter();

        // Se agrupa por concepto normalizado ("sin tacc" agrupa "Sin TACC" y "sin tacc")
        // pero se muestra la forma más frecuente.
        $conteo = [];
        $formas = [];
        foreach ($declarados as $f) {
            $texto = trim((string) $f);
            if (mb_strlen($texto) < 2) {
                continue;
            }
            $clave = $this->normalizar($texto);
            $conteo[$clave] = ($conteo[$clave] ?? 0) + 1;
            $formas[$clave][$texto] = ($formas[$clave][$texto] ?? 0) + 1;
        }

        arsort($conteo);

        $chatbot = app(ChatbotService::class);
        $salida = [];
        foreach ($conteo as $clave => $veces) {
            arsort($formas[$clave]);
            $consulta = (string) array_key_first($formas[$clave]);

            // ¿El menú lo tiene? Si sí, no es demanda: es el asistente que no lo encontró.
            if ($chatbot->tieneEnElMenu($comercio, $consulta) !== $enElMenu) {
                continue;
            }

            $salida[] = ['consulta' => $consulta, 'veces' => $veces];
            if (count($salida) >= $limit) {
                break;
            }
        }

        return $salida;
    }

    /** No se afirma nada con menos de esto: por debajo, dos o tres consultas son casualidad. */
    private const MIN_MENSAJES = 12;

    /** Ídem para lo que sale de los eventos del menú. */
    private const MIN_VISITAS = 15;

    /** Cuántas sugerencias se muestran: una lista larga no se lee y todo pesa lo mismo. */
    private const MAX_SUGERENCIAS = 5;

    /**
     * Sugerencias accionables a partir de la intención + métricas de comportamiento.
     *
     * Tres reglas que se ganaron a los golpes:
     *
     * 1. **Cada sugerencia dice su número.** "Varios clientes preguntan por delivery" se
     *    ignora; "6 personas preguntaron por delivery" se lee. Y si el número está, el
     *    comercio puede juzgar solo si le importa.
     * 2. **Con poco volumen no se habla.** El umbral viejo (3 menciones) afirmaba "hay
     *    demanda de X" con tres consultas en un mes, que es ruido.
     * 3. **Se ordenan por lo que valen y se cortan.** Primero la plata (lo que piden y no
     *    tenés), después los problemas, al final las mejoras de información.
     *
     * @param  array<int, array{consulta:string, veces:int}>  $demanda  de demandaInsatisfecha()
     */
    public function suggestions(Comercio $comercio, array $metrics, array $intents, array $demanda = []): array
    {
        $sug = [];
        $total = $intents['total'];
        $counts = $intents['counts'];
        $f = $metrics['funnel'];

        // 1. Lo que le piden y no tiene: es la única sugerencia que puede terminar en un
        // plato nuevo en la carta, así que va primera. Solo las que se repiten.
        foreach (array_slice(array_filter($demanda, fn ($d) => $d['veces'] >= 2), 0, 2) as $d) {
            $sug[] = ['icon' => 'alert', 'text' => $d['veces'].' clientes preguntaron por "'.$d['consulta'].'" y el asistente no encontró nada. Si lo tenés, cargalo bien en el menú; si no, quizás valga la pena sumarlo.'];
        }

        // 2. El asistente no pudo responder: se pierde la consulta de un cliente real.
        if ($total >= self::MIN_MENSAJES) {
            $base = ChatConversation::forComercio($comercio->id)->recent(30);
            $tot = (clone $base)->count();
            $fb = (clone $base)->where('used_openai', false)->count();
            if ($tot > 0 && $fb / $tot >= 0.25) {
                $sug[] = ['icon' => 'alert', 'text' => 'El asistente no pudo responderle a '.$fb.' de '.$tot.' consultas. Suele ser que se agotó la cuota de tu plan: escribinos.'];
            }
            // El tiempo de respuesta no va acá: es infraestructura nuestra (modelo, red,
            // largo de la respuesta), el comercio no puede hacer nada con ese dato y no
            // sugería ninguna acción. Queda como nota para el super admin en el panel.
        }

        // 3. Entran y se van de la primera pantalla.
        //
        // El ratio va contra 'vieron_eleccion', no contra 'visitas': quien entra directo al
        // chat nunca ve esa pantalla, así que no puede elegir y no corresponde contarlo.
        // Antes se dividía por el total de visitas y el aviso salía casi siempre.
        $vieron = $f['vieron_eleccion'] ?? 0;
        $seFueron = $vieron - $f['eligieron'];
        if ($vieron >= self::MIN_VISITAS && $f['eligieron'] / max(1, $vieron) < 0.5) {
            $sug[] = ['icon' => 'funnel', 'text' => $seFueron.' de las '.$vieron.' personas que abrieron tu menú se fueron de la primera pantalla sin elegir cómo verlo. Revisá que se entienda qué tienen que tocar ahí.'];
        }

        // 3.b En qué TEMA se queda sin respuestas. Un tema muy consultado donde casi nunca
        // encuentra nada es una carta incompleta o sin etiquetar, y señala dónde mirar:
        // "de 10 consultas por dieta, en 7 no encontró nada" es mucho más útil que el
        // porcentaje general de fallas.
        $porTema = $intents['sinResolver'] ?? [];
        foreach ($porTema as $tema => $fallos) {
            $deEsteTema = $counts[$tema] ?? 0;
            if ($tema === 'Otras' || $deEsteTema < 4 || $fallos / max(1, $deEsteTema) < 0.5) {
                continue;
            }
            $sug[] = ['icon' => 'alert', 'text' => 'De '.$deEsteTema.' consultas sobre "'.mb_strtolower($tema).'", en '.$fallos
                .' el asistente no encontró nada para ofrecer. Suele ser que falten platos de ese tipo o que estén sin etiquetar.'];
            break; // el peor alcanza: no llenamos la lista con variantes del mismo problema
        }

        // 4. Promos que se miran y no enganchan.
        if ($metrics['topPromos']->isNotEmpty() && $f['interesados'] === 0) {
            $sug[] = ['icon' => 'flame', 'text' => 'Tus promos recibieron '.$metrics['topPromos']->sum().' clicks pero nadie llegó a tocar "Lo quiero". Quizás la oferta o el precio no terminan de cerrar.'];
        }

        // 5. Información que les falta y te la vienen a preguntar al chat.
        //
        // Cada una sabe si el comercio YA cargó ese dato. Antes decía "cargá tu horario" a
        // alguien que lo tenía cargado hace meses: un consejo que ya cumpliste te hace
        // desconfiar del resto. Cuando el dato está, el consejo cambia de sentido —si te lo
        // siguen preguntando teniéndolo, el problema es que no se ve.
        $mensajes = [
            'Horario' => [
                'falta' => 'preguntaron por el horario y no lo tenés cargado. Cargalo en tu perfil y el menú lo va a mostrar solo.',
                'tiene' => 'preguntaron por el horario aunque lo tenés cargado. Puede que no lo estén viendo: revisá que se muestre en el menú.',
                'cargado' => fn (Comercio $c) => ! empty($c->horarios),
            ],
            'Ubicación' => [
                'falta' => 'preguntaron dónde estás y no tenés la dirección cargada. Sumala en tu perfil.',
                'tiene' => 'preguntaron dónde estás aunque la dirección está cargada. Quizás convenga mostrarla más visible.',
                'cargado' => fn (Comercio $c) => ! empty($c->direccion),
            ],
            'Envío / Delivery' => ['falta' => 'preguntaron por envíos. Aclará en el menú si hacés delivery y hasta dónde llegás.'],
            'Medios de pago' => ['falta' => 'preguntaron cómo se paga. Aclará si tomás tarjeta, transferencia o solo efectivo.'],
            'Reservas' => ['falta' => 'preguntaron por reservas. Sumá al menú cómo reservar una mesa.'],
            'Dieta / Restricciones' => ['falta' => 'preguntaron por opciones aptas (sin TACC, vegano). Etiquetá esos platos para que el asistente los encuentre.'],
        ];
        if ($total >= self::MIN_MENSAJES) {
            $umbral = max(4, (int) ceil($total * 0.15));
            foreach ($mensajes as $intent => $conf) {
                $n = $counts[$intent] ?? 0;
                if ($n < $umbral) {
                    continue;
                }
                $yaLoTiene = isset($conf['cargado']) && ($conf['cargado'])($comercio);
                $sug[] = ['icon' => 'idea', 'text' => $n.' personas '.($yaLoTiene ? $conf['tiene'] : $conf['falta'])];
            }
        }

        return array_slice($sug, 0, self::MAX_SUGERENCIAS);
    }

    private function normalizar(string $texto): string
    {
        $texto = mb_strtolower($texto, 'UTF-8');
        $texto = strtr($texto, [
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n',
        ]);

        $texto = (string) preg_replace('/[^a-z0-9 ]+/', ' ', $texto);

        return trim((string) preg_replace('/\s+/', ' ', $texto));
    }
}
