<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;

class MenuImport implements ToCollection, WithHeadingRow, WithValidation
{
    protected $comercioId;
    protected $errors = [];
    protected $data = [];

    public function __construct($comercioId)
    {
        $this->comercioId = $comercioId;
    }

    /**
     * @param Collection $rows
     */
    public function collection(Collection $rows)
    {
        // Agrupar por categoría
        $categorias = [];
        
        foreach ($rows as $row) {
            // WithHeadingRow normaliza los nombres: espacios y caracteres especiales -> guiones bajos
            // También convierte a minúsculas y elimina acentos en algunos casos
            $categoriaNombre = trim($row['categoria'] ?? $row['categoría'] ?? '');
            $nombrePlato = trim($row['nombre_del_plato'] ?? $row['nombre_del_plato'] ?? '');
            $precio = trim($row['precio'] ?? '');
            $descripcion = trim($row['descripcion'] ?? $row['descripción'] ?? '');
            $tags = trim($row['tags'] ?? '');
            $imagen = trim($row['url_imagen'] ?? $row['url_imagen'] ?? '');

            // Validar que tenga al menos nombre y categoría
            if (empty($categoriaNombre) || empty($nombrePlato)) {
                continue;
            }

            // Si la categoría no existe, crearla
            if (!isset($categorias[$categoriaNombre])) {
                $categorias[$categoriaNombre] = [
                    'nombre' => $categoriaNombre,
                    'platos' => []
                ];
            }

            // Procesar tags (separados por coma)
            $tagsArray = [];
            if (!empty($tags)) {
                $tagsArray = array_map('trim', explode(',', $tags));
                $tagsArray = array_filter($tagsArray); // Eliminar vacíos
            }

            // Agregar plato a la categoría
            $categorias[$categoriaNombre]['platos'][] = [
                'nombre' => $nombrePlato,
                'precio' => $precio,
                'descripcion' => $descripcion ?: null,
                'tags' => $tagsArray,
                'imagen' => $imagen ?: null
            ];
        }

        // Convertir a array indexado
        $this->data = [
            'categorias' => array_values($categorias)
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
        
        if (!$firstRow) {
            return ['El archivo Excel no tiene datos válidos'];
        }
        
        // Obtener todas las claves de la primera fila (normalizadas por WithHeadingRow)
        $availableKeys = array_map(function($key) {
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
            
            if (!$found) {
                $errors[] = "Columna requerida no encontrada: '{$column}'. Asegúrate de que el Excel tenga las columnas: Categoría, Nombre del Plato";
            }
        }

        return $errors;
    }
}
