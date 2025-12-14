<?php

namespace App\Exports;

use App\Models\Menu;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class MenuExport implements FromCollection, WithHeadings, WithMapping, WithTitle, WithStyles, WithColumnWidths
{
    protected $menu;
    protected $comercio;

    public function __construct(Menu $menu)
    {
        $this->menu = $menu;
        $this->comercio = $menu->comercio;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        // Obtener datos del menú desde contexto_gpt
        $data = [];
        
        if ($this->menu->contexto_gpt) {
            try {
                $jsonContent = trim($this->menu->contexto_gpt);
                
                // Remover markdown si existe
                if (strpos($jsonContent, '```json') !== false) {
                    $jsonContent = preg_replace('/```json\s*/', '', $jsonContent);
                    $jsonContent = preg_replace('/```\s*$/', '', $jsonContent);
                }
                
                $menuData = json_decode($jsonContent, true);
                
                if (is_array($menuData) && isset($menuData['categorias'])) {
                    foreach ($menuData['categorias'] as $categoria) {
                        $categoriaNombre = $categoria['nombre'] ?? 'Sin categoría';
                        $platos = $categoria['platos'] ?? [];
                        
                        foreach ($platos as $plato) {
                            $data[] = [
                                'id' => $plato['id'] ?? '', // ID único del plato
                                'categoria' => $categoriaNombre,
                                'nombre' => $plato['nombre'] ?? '',
                                'precio' => $plato['precio'] ?? '',
                                'descripcion' => $plato['descripcion'] ?? '',
                                'tags' => isset($plato['tags']) && is_array($plato['tags']) 
                                    ? implode(', ', $plato['tags']) 
                                    : '',
                                'imagen' => isset($plato['imagen']) && !empty($plato['imagen'])
                                    ? asset($plato['imagen'])
                                    : '',
                            ];
                        }
                    }
                }
            } catch (\Exception $e) {
                // Si hay error, retornar colección vacía
            }
        }
        
        return collect($data);
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        return [
            'ID',
            'Categoría',
            'Nombre del Plato',
            'Precio',
            'Descripción',
            'Tags',
            'URL Imagen',
        ];
    }

    public function headingRow(): int
    {
        return 1;
    }

    /**
     * @param mixed $row
     * @return array
     */
    public function map($row): array
    {
        return [
            $row['id'],
            $row['categoria'],
            $row['nombre'],
            $row['precio'],
            $row['descripcion'],
            $row['tags'],
            $row['imagen'],
        ];
    }

    /**
     * @return string
     */
    public function title(): string
    {
        return $this->menu->nombre ?: 'Menú';
    }

    /**
     * @param Worksheet $sheet
     * @return array
     */
    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '3B82F6'] // Azul
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ],
        ];
    }

    /**
     * @return array
     */
    public function columnWidths(): array
    {
        return [
            'A' => 15,  // ID
            'B' => 20,  // Categoría
            'C' => 30,  // Nombre
            'D' => 15,  // Precio
            'E' => 50,  // Descripción
            'F' => 25,  // Tags
            'G' => 40,  // URL Imagen
        ];
    }
}

