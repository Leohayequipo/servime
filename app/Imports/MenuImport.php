<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class MenuImport implements ToCollection, WithHeadingRow, WithValidation
{
    protected $comercioId;

    protected $errors = [];

    protected $data = [];

    public function __construct($comercioId)
    {
        $this->comercioId = $comercioId;
    }

    public function collection(Collection $rows)
    {
        // Agrupar por categoría
        $categorias = [];

        foreach ($rows as $row) {
            // WithHeadingRow normaliza los nombres: espacios y caracteres especiales -> guiones bajos
            // También convierte a minúsculas y elimina acentos en algunos casos
            $platoId = isset($row['id']) && ! empty($row['id']) ? trim($row['id']) : null;
            $categoriaNombre = trim($row['categoria'] ?? $row['categoría'] ?? '');
            $nombrePlato = trim($row['nombre_del_plato'] ?? $row['nombre_del_plato'] ?? '');
            $precio = trim($row['precio'] ?? '');
            $descripcion = trim($row['descripcion'] ?? $row['descripción'] ?? '');
            $tags = trim($row['tags'] ?? '');
            $imagen = trim($row['url_imagen'] ?? $row['url_imagen'] ?? '');
            // El plato se rearma desde cero con lo que trae la planilla, así que todo lo que
            // no venga en una columna se pierde. La estrella viajaba solo en el JSON del
            // menú: exportar y reimportar la borraba sin avisar.
            $estrella = mb_strtolower(trim((string) ($row['estrella'] ?? '')));

            // Validar que tenga al menos nombre y categoría
            if (empty($categoriaNombre) || empty($nombrePlato)) {
                continue;
            }

            // Si la categoría no existe, crearla
            if (! isset($categorias[$categoriaNombre])) {
                $categorias[$categoriaNombre] = [
                    'nombre' => $categoriaNombre,
                    'platos' => [],
                ];
            }

            // Procesar tags (separados por coma)
            $tagsArray = [];
            if (! empty($tags)) {
                $tagsArray = array_map('trim', explode(',', $tags));
                $tagsArray = array_filter($tagsArray); // Eliminar vacíos
            }

            // Agregar plato a la categoría
            $plato = [
                'nombre' => $nombrePlato,
                // Mismo criterio que el panel: un número pelado ("4500") se formatea a
                // "$4.500"; si ya trae separadores o es texto, se respeta. Sin esto, un
                // Excel con precios sin formato quedaba distinto a lo que carga el panel.
                'precio' => \App\Support\Precio::formatear($precio),
                'descripcion' => $descripcion ?: null,
                'tags' => $tagsArray,
                'imagen' => $imagen ?: null,
            ];

            // Cualquier cosa que no sea vacío marca la estrella: el export escribe "Sí",
            // pero el comercio puede haber puesto "si", "x" o un 1 a mano en la planilla.
            if ($estrella !== '' && ! in_array($estrella, ['no', '0', 'false'], true)) {
                $plato['estrella'] = true;
            }

            // Preservar ID si existe (importante para mantener imágenes)
            if ($platoId) {
                // Validar que sea un número válido
                if (is_numeric($platoId)) {
                    $plato['id'] = (int) $platoId;
                }
            }

            $categorias[$categoriaNombre]['platos'][] = $plato;
        }

        // Convertir a array indexado
        $this->data = [
            'categorias' => array_values($categorias),
        ];
    }

    /**
     * Obtener los datos procesados
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * Validar estructura del Excel
     */
    public function rules(): array
    {
        return [
            '*.categoria' => 'required|string',
            '*.nombre_del_plato' => 'required|string',
            '*.precio' => 'nullable|string',
            '*.descripcion' => 'nullable|string',
            '*.tags' => 'nullable|string',
            // Puede venir como "Sí", "si", "x" o un 1: se interpreta en collection().
            '*.estrella' => 'nullable|string',
            '*.url_imagen' => 'nullable|string',
        ];
    }

    /**
     * Validar que el Excel tenga las columnas correctas
     */
    public static function validateStructure(Collection $rows): array
    {
        $errors = [];
        $requiredColumns = ['categoria', 'nombre_del_plato'];

        if ($rows->isEmpty()) {
            return ['El archivo Excel está vacío'];
        }

        // Obtener la primera fila para verificar columnas
        $firstRow = $rows->first();

        if (! $firstRow) {
            return ['El archivo Excel no tiene datos válidos'];
        }

        // Obtener todas las claves de la primera fila (normalizadas por WithHeadingRow)
        $availableKeys = array_map(function ($key) {
            return strtolower(str_replace([' ', '_', 'á', 'é', 'í', 'ó', 'ú'], ['', '', 'a', 'e', 'i', 'o', 'u'], $key));
        }, $firstRow->keys()->toArray());

        // Verificar columnas requeridas
        foreach ($requiredColumns as $column) {
            $normalizedColumn = strtolower(str_replace([' ', '_'], '', $column));
            $found = false;

            foreach ($availableKeys as $key) {
                if ($key === $normalizedColumn || str_contains($key, $normalizedColumn)) {
                    $found = true;
                    break;
                }
            }

            if (! $found) {
                $errors[] = "Columna requerida no encontrada: '{$column}'. Asegúrate de que el Excel tenga las columnas: Categoría, Nombre del Plato";
            }
        }

        return $errors;
    }
}
