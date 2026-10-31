<?php

namespace App\Support;

use App\Models\Comercio;

/**
 * El menú público mostrado adentro del editor, en el celular de la vista previa.
 *
 * Son las pantallas REALES (`menu.portada`, `menu.choose`, `menu.chat`, `menu.classic`) y no
 * un dibujo aparte: un dibujo se desincroniza la primera vez que alguien mejora el menú
 * público y nadie se acuerda de actualizarlo.
 *
 * Pero cargadas tal cual, cada vez que el comercio edita algo se sumarían visitas, clicks y
 * elecciones falsas a sus estadísticas, y probar el chat le gastaría la cuota de IA. En modo
 * vista previa no se registra nada y el chat no manda mensajes.
 *
 * Se pide con `?preview=1`, pero sólo vale para el dueño del comercio o un super admin: si
 * alcanzara con el parámetro, cualquier cliente podría agregarlo a mano y quedar afuera de
 * las estadísticas del local.
 */
class VistaPrevia
{
    /**
     * El menú que se está editando, cuando la vista previa pide uno con `?menu=ID`.
     *
     * Sin esto el celular mostraba el menú VIGENTE del comercio, no el del editor: editando
     * un menú todavía no publicado se veía otro, y la vista previa mentía. Las pantallas
     * públicas eligen el menú en dos lugares distintos (`Comercio::menuVigente()` y
     * `ChatbotService::menusParaContexto()`), y los dos preguntan acá primero.
     *
     * Devuelve null fuera de la vista previa, así el menú de los clientes no cambia nunca.
     */
    public static function menu(Comercio $comercio): ?\App\Models\Menu
    {
        if (! request()->filled('menu') || ! self::activa($comercio)) {
            return null;
        }

        return $comercio->menus()
            ->whereKey((int) request('menu'))
            ->where('procesado', true)
            ->first();
    }

    public static function activa(Comercio $comercio): bool
    {
        if (! request()->boolean('preview')) {
            return false;
        }

        $user = auth()->user();

        return $user !== null
            && ($user->isSuperAdmin() || $user->id === $comercio->user_id);
    }
}
