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
        'archivos',
        'texto_extraido',
        'contexto_gpt',
        'formato',
        'procesado',
        'estado',
        'activo',
    ];

    protected $casts = [
        'procesado' => 'boolean',
        'activo' => 'boolean',
        'archivos' => 'array',
    ];

    /**
     * Cuántas fotos se pueden subir para una misma carta.
     *
     * Se mandan todas juntas en un solo pedido a Gemini, que acepta hasta ~20 MB. Con diez
     * fotos achicadas a 2400 px entran con margen; con más, una carta muy cargada lo rozaría.
     */
    public const MAX_FOTOS = 10;

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
     * Las rutas de los archivos de la carta, en el orden en que se leen.
     *
     * Una carta de varias fotos las tiene en `archivos`; una de un solo archivo (PDF o foto),
     * y todas las de antes de que existiera esa columna, solo en `archivo_original`.
     */
    public function archivosEnOrden(): array
    {
        if (is_array($this->archivos) && count($this->archivos) > 0) {
            return array_values($this->archivos);
        }

        return filled($this->archivo_original) ? [$this->archivo_original] : [];
    }

    /** Si la carta se subió como varias fotos. */
    public function tieneVariasFotos(): bool
    {
        return $this->formato !== 'pdf' && count($this->archivosEnOrden()) > 1;
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
                    $platos[] = $plato;
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
