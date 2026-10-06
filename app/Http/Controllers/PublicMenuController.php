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
        // Sin asistente (planes sin IA, o el comercio lo apagó) no hay nada que elegir: la
        // portada existe para ofrecer "hablale al asistente" o "mirá la carta". Se entra
        // directo a la carta. La mesa del QR ya quedó guardada en la sesión (ResolverMesa
        // corre antes), así que no se pierde con el redirect.
        if (! $comercio->tieneAsistente()) {
            return redirect()->route('menu.classic', $comercio);
        }

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
        // Un link viejo al chat (compartido, guardado) no puede abrir un asistente que el
        // plan no trae: lleva a la carta, que es lo que ese comercio ofrece.
        if (! $comercio->tieneAsistente()) {
            return redirect()->route('menu.classic', $comercio);
        }

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

            // "Lo quiero" y "Mi pedido": la casilla del plan más el interruptor del comercio,
            // igual que en la carta. Mirando sólo el interruptor, un plan sin la casilla
            // mostraba el botón "Pedido" con una lista a la que no se podía sumar nada.
            $aceptaLista = $comercio->aceptaListaDePedido();

            // El botón "Pedir" sólo existe si hay a qué comanda mandar el pedido: hace
            // falta que el comercio tenga la feature y que el cliente haya escaneado el
            // QR de una mesa. Sin eso, el menú es exactamente el de siempre.
            $puedePedir = $aceptaLista
                && $comercio->tieneFeature('comandas')
                && Mesa::idDeSesion($comercio->id) !== null;

            return view('menu.chat', compact('comercio', 'menus', 'categorias', 'tags', 'promociones', 'chips', 'aceptaLista', 'puedePedir'));
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
                'aceptaLista' => false,
                'puedePedir' => false,
            ]);
        }
    }

    /**
     * Mostrar menú clásico (PDF/imagen directo)
     */
    public function classic(Comercio $comercio)
    {
        // El activo, o el último procesado si ninguno quedó activo. La regla vive en
        // `Comercio::menuVigente()`: estaba copiada en varios lugares y en uno de ellos
        // le faltaba el caso de "ninguno activo".
        $menuActivo = $comercio->menuVigente();

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
            // Sin asistente, la carta abre en LISTA: es la única versión con buscador,
            // atajos y "Lo quiero". Si abriera en el PDF, el cliente no se entera de que
            // existen. El original queda a un toque ("ver la carta original").
            : ($comercio->tieneAsistente()
                ? $comercio->vistaMenuClasico($archivoDisponible)
                : Comercio::VISTA_LISTA);

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

        // "Lo quiero" y "Mi pedido": la lista privada del cliente, para mostrársela al mozo.
        $aceptaLista = $comercio->aceptaListaDePedido();

        // Que el pedido LE LLEGUE al mozo necesita saber a qué mesa llevarlo: la función
        // de comandas y haber entrado por el QR de una mesa. Sin mesa, la lista sigue
        // sirviendo para mostrársela. Misma regla que el chat.
        $puedePedir = $aceptaLista
            && $comercio->tieneFeature('comandas')
            && Mesa::idDeSesion($comercio->id) !== null;

        return view('menu.classic', compact(
            'comercio', 'menuActivo', 'archivoDisponible', 'vista', 'platosPorCategoria',
            'aceptaLista', 'puedePedir'
        ));
    }
}
