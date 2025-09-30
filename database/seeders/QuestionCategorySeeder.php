<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\QuestionCategory;

class QuestionCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Menú',
                'slug' => 'menu',
                'color' => '#00C4FF',
                'sort_order' => 1
            ],
            [
                'name' => 'Precios',
                'slug' => 'precios',
                'color' => '#00FF00',
                'sort_order' => 2
            ],
            [
                'name' => 'Bebidas',
                'slug' => 'bebidas',
                'color' => '#FF6B35',
                'sort_order' => 3
            ],
            [
                'name' => 'Postres',
                'slug' => 'postres',
                'color' => '#FF69B4',
                'sort_order' => 4
            ],
            [
                'name' => 'Horarios',
                'slug' => 'horarios',
                'color' => '#9370DB',
                'sort_order' => 5
            ],
            [
                'name' => 'Promociones',
                'slug' => 'promociones',
                'color' => '#FFD700',
                'sort_order' => 6
            ],
            [
                'name' => 'Delivery',
                'slug' => 'delivery',
                'color' => '#FF4500',
                'sort_order' => 7
            ],
            [
                'name' => 'Ubicación',
                'slug' => 'ubicacion',
                'color' => '#32CD32',
                'sort_order' => 8
            ],
            [
                'name' => 'Contacto',
                'slug' => 'contacto',
                'color' => '#1E90FF',
                'sort_order' => 9
            ],
            [
                'name' => 'General',
                'slug' => 'general',
                'color' => '#808080',
                'sort_order' => 10
            ]
        ];

        foreach ($categories as $category) {
            QuestionCategory::create($category);
        }
    }
}
