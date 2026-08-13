<?php

namespace App\Support;

use App\Models\ChatConversation;

/**
 * Un atajo que el cliente toca en vez de escribir: la frase que ve y la pregunta que dispara.
 *
 *   { "id": "9f2a1c7d", "emoji": "🥐",
 *     "label": "Meriendas para compartir",              <- lo que ve el cliente
 *     "q": "¿qué tienen para merendar entre dos?" }     <- lo que se le manda al asistente
 *
 * La separación ya existía en el front (`sendMessage(message, tipo, scope, esReconsulta,
 * etiqueta)` muestra `etiqueta` y envía `message`); lo que faltaba era poder editarla.
 */
class Chip
{
    /**
     * Cuántos entran en cada pantalla.
     *
     * El panel deja configurar exactamente los que se van a ver: si dejara cargar más de los
     * que la pantalla muestra, el comercio configuraría atajos que nadie ve nunca.
     *
     * Los dos layouts se acomodan solos —la portada es un grid `auto-fit` de mínimo 96px y
     * los del chat tienen `flex-wrap`—, así que el tope no lo pone el diseño sino la
     * atención: más de seis botones dejan de leerse de un vistazo, y la medición de cada uno
     * se diluye entre demasiadas opciones. En la portada, además, compiten con los dos
     * accesos principales (menú con IA / menú clásico).
     */
    public const VISIBLES = ['portada' => 6, 'chat' => 6];

    /** Tope duro para validar, sin depender de la superficie. */
    public const MAXIMO = 6;

    /** Las dos superficies donde aparecen. */
    public const SUPERFICIES = ['portada', 'chat'];

    /** Cuántos atajos admite una superficie. */
    public static function visibles(string $superficie): int
    {
        return self::VISIBLES[$superficie] ?? self::MAXIMO;
    }

    /**
     * El id sale del CONTENIDO, no de un random.
     *
     * Así, editar el texto o la pregunta genera un chip nuevo automáticamente, que es lo
     * que hace comparable la analítica: si un mismo id acumulara tres textos distintos en
     * la misma semana, el total no diría nada y taparía justo lo que se quiere ver —cuál
     * de las dos versiones funcionó mejor—. Y si el comercio vuelve al texto anterior,
     * vuelve el id anterior y la serie histórica se reanuda sola.
     *
     * El emoji y el orden NO entran: son presentación y cortarían la serie sin motivo.
     */
    public static function id(string $label, string $pregunta): string
    {
        return substr(md5(trim($label).'|'.trim($pregunta)), 0, 8);
    }

    /**
     * Normaliza un chip que llega del panel.
     *
     * @param  array<string, mixed>  $chip
     * @return array{id:string, emoji:string, label:string, q:string}|null
     */
    public static function normalizar(array $chip): ?array
    {
        $label = trim((string) ($chip['label'] ?? ''));
        $pregunta = trim((string) ($chip['q'] ?? ''));

        // Sin texto o sin pregunta no es un chip: el botón no diría nada o no haría nada.
        if ($label === '' || $pregunta === '') {
            return null;
        }

        return [
            'id' => self::id($label, $pregunta),
            'emoji' => mb_substr(trim((string) ($chip['emoji'] ?? '')), 0, 4),
            'label' => mb_substr($label, 0, 40),
            'q' => mb_substr($pregunta, 0, 200),
        ];
    }

    /**
     * Limpia una lista: descarta los inválidos y saca repetidos.
     *
     * El tope NO se aplica acá: depende de la pantalla (ver `visibles()`) y lo recorta
     * quien la consume, para no tener el número duplicado en dos lados.
     *
     * @param  array<int, mixed>  $chips
     * @return array<int, array{id:string, emoji:string, label:string, q:string}>
     */
    public static function normalizarLista(array $chips): array
    {
        $limpios = [];

        foreach ($chips as $chip) {
            if (! is_array($chip) || ! ($normalizado = self::normalizar($chip))) {
                continue;
            }
            // Dos chips con el mismo texto y pregunta comparten id: uno solo.
            $limpios[$normalizado['id']] = $normalizado;
        }

        return array_values($limpios);
    }

    /**
     * Rendimiento de los atajos, cruzando las DOS cosas que se pueden medir de cada uno.
     *
     * Antes esto vivía en dos widgets separados que parecían contradecirse:
     *
     *   1. `tracking_events` / `menu_choice` → el TOQUE en el botón de la portada.
     *   2. `chat_conversations`              → la CONSULTA que ese toque generó.
     *
     * No son lo mismo: en la portada el cliente puede tocar el atajo y abandonar antes de
     * que el asistente responda, y esa diferencia —"tocaron pero no llegaron a preguntar"—
     * es justo lo que ninguno de los dos widgets mostraba. En el chat no hay paso
     * intermedio: el toque ES la consulta, así que ahí clicks y preguntas coinciden.
     *
     * Se agrupa por el `id` del chip (texto + pregunta): editar cualquiera de los dos genera
     * un id nuevo, así dos redacciones aparecen separadas y se pueden comparar. Los clicks
     * viejos, anteriores a los atajos configurables, no tienen id y caen agrupados por texto
     * para no perderlos.
     *
     * @return array<int, array<string, mixed>> ordenado por clicks, de mayor a menor
     */
    public static function rendimiento(int $comercioId, int $dias = 30): array
    {
        $desde = $dias > 0 ? now()->subDays($dias) : null;
        $filas = [];

        // La clave lleva la SUPERFICIE además del id: el mismo atajo puede estar en la
        // portada y en el chat, y ahí comparte id porque el id sale del texto + la pregunta.
        // Agrupándolos juntos, un atajo que anda bien en una pantalla tapaba que en la otra
        // no lo toca nadie, que es justo lo que hay que poder ver.
        $clave = fn (?string $id, string $label, string $superficie) => $superficie.'|'.($id ?: 'txt:'.mb_strtolower(trim($label)));
        $nueva = fn (?string $id, string $label, string $superficie) => [
            'id' => $id, 'label' => $label, 'superficie' => $superficie,
            'clicks' => 0, 'preguntas' => 0, 'arranques' => 0, 'con_platos' => 0,
        ];

        // 1) Toques en los atajos de la portada.
        $toques = \Illuminate\Support\Facades\DB::table('tracking_events')
            ->where('comercio_id', $comercioId)
            ->where('event', 'menu_choice')
            ->when($desde, fn ($q) => $q->where('created_at', '>=', $desde))
            ->pluck('data');

        foreach ($toques as $data) {
            $d = json_decode((string) $data, true);
            if (($d['desde'] ?? null) !== 'chip') {
                continue;
            }
            $label = trim((string) ($d['label'] ?? ''));
            if ($label === '') {
                continue;
            }
            $k = $clave($d['chip'] ?? null, $label, 'portada');
            $filas[$k] ??= $nueva($d['chip'] ?? null, $label, 'portada');
            $filas[$k]['clicks']++;
        }

        // 2) Consultas que llegaron al asistente desde un atajo.
        $consultas = ChatConversation::forComercio($comercioId)
            ->whereIn('origen', ['chip', 'chip_portada', 'chip_chat'])
            ->when($desde, fn ($q) => $q->where('created_at', '>=', $desde))
            ->get(['user_message', 'origen', 'platos_sugeridos', 'metadata', 'id']);

        // Primer mensaje de cada sesión: dice si el atajo rompió el hielo o si lo tocaron
        // después de haber escrito, que son cosas distintas.
        $primeros = $consultas->isEmpty() ? collect() : ChatConversation::forComercio($comercioId)
            ->selectRaw('MIN(id) as id')->groupBy('session_id')->pluck('id')->flip();

        foreach ($consultas as $c) {
            $chip = $c->metadata['chip'] ?? null;
            $label = $chip['label'] ?? $c->user_message;

            $superficie = match ($c->origen) {
                'chip_portada' => 'portada',
                'chip_chat' => 'chat',
                default => 'anterior',
            };

            $k = $clave($chip['id'] ?? null, $label, $superficie);
            $filas[$k] ??= $nueva($chip['id'] ?? null, $label, $superficie);
            $filas[$k]['preguntas']++;
            $filas[$k]['arranques'] += $primeros->has($c->id) ? 1 : 0;
            $filas[$k]['con_platos'] += ($c->platos_sugeridos > 0) ? 1 : 0;

            // En el chat el toque no deja evento de tracking: el click ES la consulta.
            if ($superficie !== 'portada') {
                $filas[$k]['clicks']++;
            }
        }

        // 3) Los atajos que están configurados HOY y que nadie tocó todavía. Sin esto el
        // widget decía "el que nadie toca conviene cambiarlo" pero justamente ese no
        // aparecía en la lista: solo salían los que ya tenían algún click.
        $comercio = \App\Models\Comercio::find($comercioId);
        foreach (self::SUPERFICIES as $superficie) {
            foreach ($comercio?->chips[$superficie] ?? [] as $chip) {
                $k = $clave($chip['id'] ?? null, $chip['label'] ?? '', $superficie);
                $filas[$k] ??= $nueva($chip['id'] ?? null, $chip['label'] ?? '', $superficie);
            }
        }

        foreach ($filas as &$fila) {
            // Si alguien tocó en la portada y además preguntó, el toque ya está contado; pero
            // si el tracking se perdió (bloqueador, JS caído), las preguntas mandan.
            $fila['clicks'] = max($fila['clicks'], $fila['preguntas']);
            $fila['abandonos'] = $fila['clicks'] - $fila['preguntas'];
            $fila['efectividad'] = (int) round($fila['con_platos'] * 100 / max($fila['clicks'], 1));
        }
        unset($fila);

        usort($filas, fn ($a, $b) => $b['clicks'] <=> $a['clicks']);

        return $filas;
    }
}
