<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Menu extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'comercio_id',
        'nombre',
        'archivo_original',
        'texto_extraido',
        'contexto_gpt',
        'formato',
        'procesado',
        'estado',
        'activo',
        'recomendaciones',
    ];

    protected $casts = [
        'procesado' => 'boolean',
        'activo' => 'boolean',
        'recomendaciones' => 'array',
    ];

    // Estados de procesamiento del menú
    public const ESTADO_PROCESANDO = 'procesando';

    public const ESTADO_LISTO = 'listo';

    public const ESTADO_ERROR = 'error';

    public function comercio()
    {
        return $this->belongsTo(Comercio::class);
    }

    public function estaListo(): bool
    {
        return $this->estado === self::ESTADO_LISTO;
    }

    public function tuvoError(): bool
    {
        return $this->estado === self::ESTADO_ERROR;
    }

    /**
     * Los platos del menú ya procesado, aplanados y sin sus categorías.
     *
     * Devuelve vacío si el JSON no se puede leer: esto alimenta avisos y contadores, y
     * un menú con el formato raro no puede tirar abajo la pantalla que lo muestre.
     */
    public function platos(): array
    {
        if (blank($this->contexto_gpt)) {
            return [];
        }

        // La IA a veces devuelve el JSON envuelto en un bloque markdown.
        $json = trim(preg_replace(['/```json\s*/', '/```\s*$/'], '', trim($this->contexto_gpt)));
        $data = json_decode($json, true);

        if (! is_array($data) || ! is_array($data['categorias'] ?? null)) {
            return [];
        }

        $platos = [];

        foreach ($data['categorias'] as $categoria) {
            foreach ($categoria['platos'] ?? [] as $plato) {
                if (is_array($plato)) {
                    // La categoría viaja con el plato: aplanado y sin ella, no hay forma de
                    // saber si algo es una bebida o un principal, que es de lo que dependen
                    // las recomendaciones.
                    $platos[] = $plato + ['categoria' => trim((string) ($categoria['nombre'] ?? ''))];
                }
            }
        }

        return $platos;
    }

    /**
     * Cuántos platos quedaron sin precio al procesar la carta (precio borroso, partido
     * en dos renglones, o que directamente no estaba).
     */
    public function platosSinPrecio(): int
    {
        return count(array_filter(
            $this->platos(),
            fn ($plato) => trim((string) ($plato['precio'] ?? '')) === ''
        ));
    }
}
