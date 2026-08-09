<?php

namespace App\Http\Controllers;

use App\Models\Comercio;
use App\Services\MenuTranslatorService;

class PublicMenuController extends Controller
{
    /**
     * Pantalla de selección: elegir entre menú con IA o menú clásico
     */
    public function choose(Comercio $comercio)
    {
        $chips = $comercio->getChipsSugeridos();

        // Con portada procesada se sirve el diseño derivado de su propia foto;
        // sin ella, el diseño anterior. Así ningún comercio queda roto por no
        // haber subido nada todavía.
        $vista = $comercio->tienePortada() ? 'menu.portada' : 'menu.choose';

        return view($vista, compact('comercio', 'chips'));
    }

    /**
     * Mostrar menú con IA (chatbot) - versión anterior
     */
    public function chat(Comercio $comercio)
    {
        try {
            $menus = $comercio->menus()
                ->where('procesado', true)
                ->orderBy('created_at', 'desc')
                ->get();

            $categorias = $comercio->getCategoriasDelMenu();
            $tags = $comercio->getTagsDelMenu();
            $promociones = $comercio->getPromocionesDelMenu();

            // Nombre de categoría traducido al idioma del cliente para MOSTRAR, pero el
            // 'nombre' original se conserva porque es lo que se le manda al bot: el modo
            // categoría del prompt lo busca tal cual figura en el menú.
            $traducciones = app(MenuTranslatorService::class)
                ->categorias($comercio, array_column($categorias, 'nombre'));
            foreach ($categorias as &$categoria) {
                $categoria['nombre_visible'] = $traducciones[$categoria['nombre']] ?? $categoria['nombre'];
            }
            unset($categoria);

            return view('menu.chat', compact('comercio', 'menus', 'categorias', 'tags', 'promociones'));
        } catch (\Exception $e) {
            \Log::error('Error en PublicMenuController@chat: '.$e->getMessage(), [
                'comercio_id' => $comercio->id,
                'exception' => $e,
            ]);

            // Retornar vista con datos vacíos en caso de error
            return view('menu.chat', [
                'comercio' => $comercio,
                'menus' => collect([]),
                'categorias' => [],
                'tags' => [],
                'promociones' => [],
            ]);
        }
    }

    /**
     * Mostrar menú clásico (PDF/imagen directo)
     */
    public function classic(Comercio $comercio)
    {
        $menuActivo = $comercio->menus()
            ->where('activo', true)
            ->where('procesado', true)
            ->first();

        // Si no hay menú activo, buscar el último procesado
        if (! $menuActivo) {
            $menuActivo = $comercio->menus()
                ->where('procesado', true)
                ->orderBy('created_at', 'desc')
                ->first();
        }

        return view('menu.classic', compact('comercio', 'menuActivo'));
    }
}
