<?php

namespace App\Http\Controllers;

use App\Models\Comercio;
use App\Models\Mesa;
use App\Services\ChatbotService;
use App\Services\MenuTranslatorService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class PublicMenuController extends Controller
{
    /**
     * Pantalla de selección: elegir entre menú con IA o menú clásico
     */
    public function choose(Comercio $comercio)
    {
        // Los que configuró el comercio, o los automáticos del menú si nunca los tocó.
        $chips = $comercio->chipsDe('portada');

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

            // Atajos de arranque del chat: los del comercio, o los del menú si no configuró.
            $chips = $comercio->chipsDe('chat');

            // El botón "Pedir" sólo existe si hay a qué comanda mandar el pedido: hace
            // falta que el comercio tenga la feature y que el cliente haya escaneado el
            // QR de una mesa. Sin eso, el menú es exactamente el de siempre.
            $puedePedir = $comercio->tieneFeature('comandas') && Mesa::idDeSesion($comercio->id) !== null;

            return view('menu.chat', compact('comercio', 'menus', 'categorias', 'tags', 'promociones', 'chips', 'puedePedir'));
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
                'chips' => [],
                'puedePedir' => false,
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

        // Que el campo tenga una ruta NO quiere decir que el archivo esté. La vista lo mete
        // en un <iframe>, así que con el archivo faltante el cliente sentado en la mesa ve
        // dentro del menú lo que sea que devuelva esa URL: un 403, un 404, o la pantalla de
        // login. Comprobado en local, y el fallback amable ("Menú no disponible") no se
        // activaba porque solo miraba el campo de la base.
        $archivoDisponible = $menuActivo
            && filled($menuActivo->archivo_original)
            && Storage::disk('public')->exists($menuActivo->archivo_original);

        if ($menuActivo && filled($menuActivo->archivo_original) && ! $archivoDisponible) {
            // Que quede registrado: la base apunta a un archivo que no está, y eso solo se
            // arregla volviendo a subir el menú. Se loguea en cada visita a propósito —es un
            // menú roto de cara al cliente—; si algún local con mucho tráfico ensucia el log,
            // ahí se piensa en agrupar.
            Log::warning('El menú clásico apunta a un archivo que no existe', [
                'comercio' => $comercio->slug,
                'menu_id' => $menuActivo->id,
                'archivo' => $menuActivo->archivo_original,
            ]);
        }

        // Qué se muestra: el archivo o la lista de platos. Manda lo que configuró el
        // comercio, salvo que el cliente pida la otra con ?vista= (el botón para alternar).
        $vista = in_array(request('vista'), [Comercio::VISTA_LISTA, Comercio::VISTA_ARCHIVO], true)
            ? request('vista')
            : $comercio->vistaMenuClasico($archivoDisponible);

        // Sin archivo no hay nada que alternar.
        if (! $archivoDisponible) {
            $vista = Comercio::VISTA_LISTA;
        }

        // Los platos se cargan sólo si se van a mostrar: parsear el JSON del menú entero
        // para después renderizar un iframe sería trabajo tirado en cada visita.
        $platosPorCategoria = $vista === Comercio::VISTA_LISTA
            ? collect(app(ChatbotService::class)->platosDelMenu($comercio))
                ->groupBy(fn ($p) => $p['categoria'] ?: 'Otros')
            : collect();

        return view('menu.classic', compact(
            'comercio', 'menuActivo', 'archivoDisponible', 'vista', 'platosPorCategoria'
        ));
    }
}
