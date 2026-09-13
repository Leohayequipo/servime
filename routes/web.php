<?php

// Auto-deploy test

use App\Http\Controllers\ComercioController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (auth()->check()) {
        return redirect('/dashboard');
    }
    $planes = \App\Models\Plan::where('activo', true)->where('orden', '>', 0)->orderBy('orden')->get();
    $stats = \App\Models\SiteVisit::stats();

    // Comercio demo para que un prospecto pruebe el producto sin tener cuenta.
    // Se resuelve una sola vez y se cachea; si no existe, la landing lo omite.
    //
    // El log no sobra: cuando esto devuelve null la landing simplemente deja de
    // mostrar la demo, sin error en ningún lado. Ya pasó en producción con el slug
    // escrito `live_demo` en vez de `live-demo` —el nombre del comercio en vez del
    // slug— y el null quedó cacheado 6 horas, así que ni corregir el .env lo
    // destapaba. Distinguir los dos motivos ahorra el diagnóstico entero.
    $demoSlug = \Illuminate\Support\Facades\Cache::remember('landing_demo_slug', now()->addHours(6), function () {
        $slug = config('servime.demo_slug');

        if (! $slug) {
            return null;                       // sin demo configurada: es una decisión, no un error
        }

        $comercio = \App\Models\Comercio::where('slug', $slug)->first();

        if (! $comercio) {
            \Illuminate\Support\Facades\Log::warning(
                "Landing: DEMO_MENU_SLUG apunta a '{$slug}' y no existe ningún comercio con ese slug. ".
                'Ojo que el slug no es el nombre del comercio. La landing va a mostrar el bloque de contacto en vez de la demo.'
            );

            return null;
        }

        $slugConMenu = $comercio->menus()->where('procesado', true)->exists() ? $comercio->slug : null;

        if (! $slugConMenu) {
            \Illuminate\Support\Facades\Log::warning(
                "Landing: el comercio demo '{$slug}' existe pero no tiene ningún menú procesado. ".
                'Hay que procesarle un menú desde el panel para que la demo aparezca.'
            );
        }

        return $slugConMenu;
    });

    return view('marketing.landing', compact('planes', 'stats', 'demoSlug'));
})->middleware(\App\Http\Middleware\TrackSiteVisit::class);

// Ruta para servir logos directamente
Route::get('/logos/{filename}', function ($filename) {
    if (! preg_match('/^[a-zA-Z0-9._-]+$/', $filename)) {
        abort(404);
    }

    $allowedDir = realpath(storage_path('app/public/logos'));
    $realPath = realpath($allowedDir.DIRECTORY_SEPARATOR.$filename);

    if (! $realPath || ! str_starts_with($realPath, $allowedDir) || ! is_file($realPath)) {
        abort(404);
    }

    return response()->file($realPath);
})->name('logos.show');

Route::get('/dashboard', [DashboardController::class, 'index'])->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Rutas de Administración (Solo Super Administradores)
    Route::middleware('super_admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/dashboard', function () {
            return redirect()->route('dashboard');
        })->name('dashboard');

        // Gestión de Usuarios
        Route::get('/users', [App\Http\Controllers\AdminController::class, 'users'])->name('users.index');
        Route::get('/users/create', [App\Http\Controllers\AdminController::class, 'createUser'])->name('users.create');
        Route::post('/users', [App\Http\Controllers\AdminController::class, 'storeUser'])->name('users.store');
        Route::get('/users/{user}', [App\Http\Controllers\AdminController::class, 'showUser'])->name('users.show');
        Route::get('/users/{user}/edit', [App\Http\Controllers\AdminController::class, 'editUser'])->name('users.edit');
        Route::put('/users/{user}', [App\Http\Controllers\AdminController::class, 'updateUser'])->name('users.update');
        Route::delete('/users/{user}', [App\Http\Controllers\AdminController::class, 'destroyUser'])->name('users.destroy');

        // Gestión de Planes y asignación a comercios
        Route::get('/plans', [App\Http\Controllers\PlanController::class, 'index'])->name('plans.index');
        Route::get('/plans/create', [App\Http\Controllers\PlanController::class, 'create'])->name('plans.create');
        Route::post('/plans', [App\Http\Controllers\PlanController::class, 'store'])->name('plans.store');
        Route::get('/plans/{plan}/edit', [App\Http\Controllers\PlanController::class, 'edit'])->name('plans.edit');
        Route::put('/plans/{plan}', [App\Http\Controllers\PlanController::class, 'update'])->name('plans.update');
        Route::delete('/plans/{plan}', [App\Http\Controllers\PlanController::class, 'destroy'])->name('plans.destroy');
        Route::put('/comercios/{comercio:slug}/plan', [App\Http\Controllers\PlanController::class, 'assignToComercio'])->name('comercios.assign-plan');
        Route::post('/comercios/{comercio:slug}/reset-ia', [App\Http\Controllers\PlanController::class, 'resetIaUsage'])->name('comercios.reset-ia');

        // Gestión de Comercios (usando slug).
        // La lista de comercios del admin vive en `/comercios` (ComercioController@index, que
        // para super admin trae todos): no hay una copia acá. Lo que sigue son las acciones
        // que sólo el admin puede hacer sobre un comercio.

        // Borra la ACTIVIDAD del comercio (conversaciones, tracking, carritos, intents) y
        // le pone la cuota de IA en cero. No toca la configuración ni el menú. Es
        // irreversible y sólo para super admin: ver AdminController@resetActividadComercio.
        Route::post('/comercios/{comercio:slug}/reset-actividad', [App\Http\Controllers\AdminController::class, 'resetActividadComercio'])->name('comercios.reset-actividad');

        Route::delete('/comercios/{comercio:slug}', [App\Http\Controllers\AdminController::class, 'destroyComercio'])->name('comercios.destroy');

        // Gestión de Menús
        Route::get('/menus', [App\Http\Controllers\AdminController::class, 'menus'])->name('menus.index');
        Route::get('/menus/deleted', [App\Http\Controllers\AdminController::class, 'menusDeleted'])->name('menus.deleted');
        Route::post('/menus/{menuId}/restore', [App\Http\Controllers\AdminController::class, 'restoreMenu'])->name('menus.restore');
        Route::get('/menus/{menu}', [App\Http\Controllers\AdminController::class, 'showMenu'])->name('menus.show');
        Route::get('/menus/{menu}/edit', [App\Http\Controllers\AdminController::class, 'editMenu'])->name('menus.edit');
        Route::put('/menus/{menu}', [App\Http\Controllers\AdminController::class, 'updateMenu'])->name('menus.update');

        // Chat del landing
        Route::get('/landing-chat', [App\Http\Controllers\AdminController::class, 'landingChat'])->name('landing-chat.index');
    });

    // Rutas de Comercios (usando slug)
    Route::get('/comercios/create', [ComercioController::class, 'create'])->name('comercios.create');
    Route::post('/comercios', [ComercioController::class, 'store'])->name('comercios.store');
    Route::get('/comercios', [ComercioController::class, 'index'])->name('comercios.index');
    Route::get('/comercios/{comercio:slug}/edit', [ComercioController::class, 'edit'])->name('comercios.edit');
    Route::put('/comercios/{comercio:slug}', [ComercioController::class, 'update'])->name('comercios.update');
    Route::delete('/comercios/{comercio:slug}', [ComercioController::class, 'destroy'])->name('comercios.destroy');

    // Estado del procesado de la portada: el panel lo consulta mientras genera.
    Route::get('/comercios/{comercio:slug}/portada/estado', [ComercioController::class, 'portadaEstado'])->name('comercios.portada.estado');
    Route::post('/comercios/{comercio:slug}/portada/reintentar', [ComercioController::class, 'portadaReintentar'])->name('comercios.portada.reintentar')->middleware('throttle:10,1');

    // La portada se configura desde la pantalla del menú, que guarda por AJAX de a
    // pedazos. El throttle es más alto que el del reintento porque acá entra también
    // el encuadre, que se guarda cada vez que sueltan el marcador.
    Route::post('/comercios/{comercio:slug}/portada', [ComercioController::class, 'portadaGuardar'])->name('comercios.portada.guardar')->middleware('throttle:30,1');

    // Mesas del local y sus QR. Van con la feature `mesas`: el comercio que no la tiene
    // sigue usando el QR único de siempre, que no se toca.
    Route::middleware('plan.feature:mesas')->group(function () {
        Route::get('/comercios/{comercio:slug}/mesas', [App\Http\Controllers\MesaController::class, 'index'])->name('mesas.index');
        Route::post('/comercios/{comercio:slug}/mesas/sincronizar', [App\Http\Controllers\MesaController::class, 'sincronizar'])->name('mesas.sincronizar');
        Route::get('/comercios/{comercio:slug}/mesas/imprimir', [App\Http\Controllers\MesaController::class, 'imprimir'])->name('mesas.imprimir');
        Route::put('/comercios/{comercio:slug}/mesas/{mesa}', [App\Http\Controllers\MesaController::class, 'update'])->name('mesas.update');
        Route::post('/comercios/{comercio:slug}/mesas/{mesa}/rotar', [App\Http\Controllers\MesaController::class, 'rotarToken'])->name('mesas.rotar');
        Route::post('/comercios/{comercio:slug}/mesas/{mesa}/toggle', [App\Http\Controllers\MesaController::class, 'toggleActiva'])->name('mesas.toggle');
        Route::get('/comercios/{comercio:slug}/mesas/{mesa}/qr.png', [App\Http\Controllers\MesaController::class, 'qr'])->name('mesas.qr');
    });

    // Acceso a la pantalla del mozo: lo administra el dueño desde la pantalla de mesas,
    // pero es parte de las comandas, así que va con esa feature.
    Route::middleware('plan.feature:comandas')->group(function () {
        Route::post('/comercios/{comercio:slug}/mozo/activar', [App\Http\Controllers\MesaController::class, 'activarMozo'])->name('mozo.activar');
        Route::post('/comercios/{comercio:slug}/mozo/rotar', [App\Http\Controllers\MesaController::class, 'rotarLinkMozo'])->name('mozo.rotar');
        Route::post('/comercios/{comercio:slug}/mozo/desactivar', [App\Http\Controllers\MesaController::class, 'desactivarMozo'])->name('mozo.desactivar');
        Route::get('/comercios/{comercio:slug}/mozo/qr.png', [App\Http\Controllers\MesaController::class, 'qrMozo'])->name('mozo.qr');
    });

    // Rutas de Menús (usando slug para comercio)
    Route::get('/comercios/{comercio:slug}/menus', [App\Http\Controllers\MenuController::class, 'index'])->name('menus.index');
    Route::get('/comercios/{comercio:slug}/menus/create', [App\Http\Controllers\MenuController::class, 'create'])->name('menus.create');
    Route::post('/comercios/{comercio:slug}/menus', [App\Http\Controllers\MenuController::class, 'store'])->name('menus.store')->middleware('throttle:5,1');
    Route::get('/comercios/{comercio:slug}/menus/{menu}', [App\Http\Controllers\MenuController::class, 'show'])->name('menus.show');
    Route::get('/comercios/{comercio:slug}/menus/{menu}/edit', [App\Http\Controllers\MenuController::class, 'edit'])->name('menus.edit');
    Route::put('/comercios/{comercio:slug}/menus/{menu}', [App\Http\Controllers\MenuController::class, 'update'])->name('menus.update');
    Route::delete('/comercios/{comercio:slug}/menus/{menu}', [App\Http\Controllers\MenuController::class, 'destroy'])->name('menus.destroy');
    Route::post('/comercios/{comercio:slug}/menus/{menu}/activar', [App\Http\Controllers\MenuController::class, 'activar'])->name('menus.activar');
    Route::post('/comercios/{comercio:slug}/menus/{menu}/upload-plato-imagen', [App\Http\Controllers\MenuController::class, 'uploadPlatoImagen'])->name('menus.upload.plato.imagen');
    Route::delete('/comercios/{comercio:slug}/menus/{menu}/delete-plato-imagen', [App\Http\Controllers\MenuController::class, 'deletePlatoImagen'])->name('menus.delete.plato.imagen');
    Route::post('/comercios/{comercio:slug}/menus/{menu}/update-plato-precio', [App\Http\Controllers\MenuController::class, 'updatePlatoPrecio'])->name('menus.update.plato.precio');
    Route::post('/comercios/{comercio:slug}/menus/{menu}/update-plato-nombre', [App\Http\Controllers\MenuController::class, 'updatePlatoNombre'])->name('menus.update.plato.nombre');
    Route::post('/comercios/{comercio:slug}/menus/{menu}/update-plato-descripcion', [App\Http\Controllers\MenuController::class, 'updatePlatoDescripcion'])->name('menus.update.plato.descripcion');
    Route::post('/comercios/{comercio:slug}/menus/{menu}/update-plato-tags', [App\Http\Controllers\MenuController::class, 'updatePlatoTags'])->name('menus.update.plato.tags');
    Route::post('/comercios/{comercio:slug}/menus/{menu}/update-plato-estrella', [App\Http\Controllers\MenuController::class, 'updatePlatoEstrella'])->name('menus.update.plato.estrella');
    Route::post('/comercios/{comercio:slug}/menus/{menu}/agregar-plato', [App\Http\Controllers\MenuController::class, 'agregarPlato'])->name('menus.agregar.plato');
    Route::delete('/comercios/{comercio:slug}/menus/{menu}/delete-plato', [App\Http\Controllers\MenuController::class, 'deletePlato'])->name('menus.delete.plato');
    Route::post('/comercios/{comercio:slug}/menus/{menu}/reordenar-platos', [App\Http\Controllers\MenuController::class, 'reordenarPlatos'])->name('menus.reordenar.platos');
    Route::post('/comercios/{comercio:slug}/menus/{menu}/upload-promo-imagen', [App\Http\Controllers\MenuController::class, 'uploadPromocionImagen'])->name('menus.upload.promo.imagen')->middleware('plan.feature:promociones');
    Route::get('/comercios/{comercio:slug}/menus/{menu}/export', [App\Http\Controllers\MenuController::class, 'export'])->name('menus.export')->middleware('plan.feature:excel');
    Route::post('/comercios/{comercio:slug}/menus/preview-import', [App\Http\Controllers\MenuController::class, 'previewImport'])->name('menus.preview.import')->middleware('plan.feature:excel');
    Route::post('/comercios/{comercio:slug}/menus/import', [App\Http\Controllers\MenuController::class, 'import'])->name('menus.import')->middleware('plan.feature:excel');
    Route::post('/comercios/{comercio:slug}/menus/update-theme-colors', [App\Http\Controllers\MenuController::class, 'updateThemeColors'])->name('menus.update.theme.colors');
    Route::post('/comercios/{comercio:slug}/menus/update-chatbot-config', [App\Http\Controllers\MenuController::class, 'updateChatbotConfig'])->name('menus.update.chatbot.config');
    // Va con `analytics` porque administrar los chips sin ver cuál funciona es a ciegas.
    Route::post('/comercios/{comercio:slug}/menus/update-chips', [App\Http\Controllers\MenuController::class, 'updateChips'])->name('menus.update.chips')->middleware('plan.feature:analytics');

    // Reseñas de Google: va con `analytics` igual que los chips, y por el mismo motivo
    // (activarlas sin poder ver cuántos las tocan es a ciegas).
    Route::post('/comercios/{comercio:slug}/menus/update-resenas', [App\Http\Controllers\MenuController::class, 'updateResenas'])->name('menus.update.resenas')->middleware('plan.feature:analytics');

    // El nombre del menú se guarda solo, sin depender del botón que dispara el
    // procesamiento con IA.
    Route::post('/comercios/{comercio:slug}/menus/{menu}/nombre', [App\Http\Controllers\MenuController::class, 'updateNombre'])->name('menus.update.nombre');

    // Con qué abre el menú clásico. Sin feature: es cómo se muestra el menú que el
    // comercio ya tiene, no una función aparte.
    Route::post('/comercios/{comercio:slug}/menus/update-vista-clasica', [App\Http\Controllers\MenuController::class, 'updateVistaClasica'])->name('menus.update.vista.clasica');
    Route::delete('/comercios/{comercio:slug}/menus/{menu}/delete-promo-imagen', [App\Http\Controllers\MenuController::class, 'deletePromocionImagen'])->name('menus.delete.promo.imagen')->middleware('plan.feature:promociones');
    Route::delete('/comercios/{comercio:slug}/menus/{menu}/delete-promocion', [App\Http\Controllers\MenuController::class, 'deletePromocion'])->name('menus.delete.promocion')->middleware('plan.feature:promociones');
    Route::post('/comercios/{comercio:slug}/menus/{menu}/save-promociones', [App\Http\Controllers\MenuController::class, 'savePromocionesAjax'])->name('menus.save.promociones')->middleware('plan.feature:promociones');

    // Rutas de personalización (ahora integradas en editar, usando slug)
});

// Chat de ventas de la landing (público, con throttle)
Route::post('/api/landing-chat', [App\Http\Controllers\LandingChatController::class, 'chat'])
    ->middleware('throttle:20,1')
    ->name('landing.chat');

// Sitemap dinámico: la landing + el menú público de cada comercio que tenga menú
// procesado. Se genera solo, así nunca queda desactualizado ni apunta a URLs rotas.
Route::get('/sitemap.xml', function () {
    $xml = \Illuminate\Support\Facades\Cache::remember('sitemap_xml', now()->addHour(), function () {
        $urls = [[
            'loc' => url('/'),
            'changefreq' => 'weekly',
            'priority' => '1.0',
        ]];

        \App\Models\Comercio::whereHas('menus', fn ($q) => $q->where('procesado', true))
            ->orderBy('slug')
            ->chunk(200, function ($comercios) use (&$urls) {
                foreach ($comercios as $comercio) {
                    $urls[] = [
                        'loc' => route('menu.public', $comercio->slug),
                        'lastmod' => optional($comercio->updated_at)->toAtomString(),
                        'changefreq' => 'weekly',
                        'priority' => '0.8',
                    ];
                }
            });

        $out = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
        $out .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";
        foreach ($urls as $url) {
            $out .= "    <url>\n";
            $out .= '        <loc>'.htmlspecialchars($url['loc'], ENT_XML1).'</loc>'."\n";
            if (! empty($url['lastmod'])) {
                $out .= '        <lastmod>'.$url['lastmod'].'</lastmod>'."\n";
            }
            $out .= '        <changefreq>'.$url['changefreq'].'</changefreq>'."\n";
            $out .= '        <priority>'.$url['priority'].'</priority>'."\n";
            $out .= "    </url>\n";
        }

        return $out.'</urlset>';
    });

    return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
})->name('sitemap');

// Contador en vivo del footer de la landing (stats cacheadas 30s)
Route::get('/api/site-stats', fn () => response()->json(\App\Models\SiteVisit::stats()))
    ->middleware('throttle:60,1')
    ->name('site.stats');

// El navegador confirma que hay una persona real (los bots no ejecutan JS, así que nunca llegan acá).
// Sin esta confirmación la visita queda guardada pero NO suma en ninguna métrica.
Route::post('/api/visit-confirm', function (\Illuminate\Http\Request $request) {
    $visitorId = $request->cookie('sv_vid');

    if (is_string($visitorId) && $visitorId !== '') {
        $confirmadas = \App\Models\SiteVisit::where('visitor_id', $visitorId)
            ->where('humano', false)
            ->where('created_at', '>=', now()->subMinutes(30))
            ->update(['humano' => true]);

        if ($confirmadas > 0) {
            \Illuminate\Support\Facades\Cache::forget('site_visits_stats');
        }
    }

    return response()->noContent();
})->middleware('throttle:30,1')->name('visit.confirm');

// Rutas públicas (sin autenticación, usando slug).
//
// Todas pasan por ResolverMesa: si el request trae el `?m=<token>` del QR de una mesa,
// la mesa queda guardada en la sesión y de ahí la leen el tracking, el carrito y el chat
// sin que el front tenga que mandarla nunca.
Route::middleware(\App\Http\Middleware\ResolverMesa::class)->group(function () {
    Route::get('/menu/{comercio:slug}', [App\Http\Controllers\PublicMenuController::class, 'choose'])->name('menu.public');
    Route::get('/menu/{comercio:slug}/chat', [App\Http\Controllers\PublicMenuController::class, 'chat'])->name('menu.chat');
    Route::get('/menu/{comercio:slug}/classic', [App\Http\Controllers\PublicMenuController::class, 'classic'])->name('menu.classic');
    // Chat con IA. Los límites 'chat-ia'/'tracking'/'carrito' van por dispositivo y no por IP:
    // todos los clientes de un local salen por el mismo wifi (ver AppServiceProvider).
    Route::post('/api/chat/{comercio:slug}', [App\Http\Controllers\ChatbotController::class, 'chat'])
        ->middleware('throttle:chat-ia')->name('chat.api');
    Route::post('/api/chat/{comercio:slug}/stream', [App\Http\Controllers\ChatbotController::class, 'chatStream'])
        ->middleware('throttle:chat-ia')->name('chat.stream');

    // Tracking de eventos del menú público (sin auth, por sesión)
    Route::post('/api/track/{comercio:slug}', [App\Http\Controllers\TrackingController::class, 'store'])
        ->middleware('throttle:tracking')->name('track');
});

// API ligera para carrito (por sesión, sin auth)
Route::prefix('api/cart')->middleware('throttle:carrito')->group(function () {
    Route::post('/show', [App\Http\Controllers\CartController::class, 'show'])->name('api.cart.show');
    Route::post('/incr', [App\Http\Controllers\CartController::class, 'incr'])->name('api.cart.incr');
    Route::post('/remove', [App\Http\Controllers\CartController::class, 'remove'])->name('api.cart.remove');
    Route::post('/clear', [App\Http\Controllers\CartController::class, 'clear'])->name('api.cart.clear');
});

// Confirmar el pedido: es el paso que lo hace visible para el mozo, así que va con su
// propio limitador (mucho más ajustado que el del carrito, ver AppServiceProvider).
Route::post('/api/comanda/pedir', [App\Http\Controllers\ComandaClienteController::class, 'pedir'])
    ->middleware('throttle:pedir')->name('api.comanda.pedir');
Route::post('/api/comanda/mia', [App\Http\Controllers\ComandaClienteController::class, 'mia'])
    ->middleware('throttle:carrito')->name('api.comanda.mia');

// Pantalla del mozo. No usa el login de Laravel: entra por un link secreto del comercio
// más un PIN, porque en un local la tablet la usa el que esté en el turno y dar de alta
// un usuario por empleado, con la rotación que hay, termina en una cuenta compartida
// igual pero con más trabajo. Ver App\Http\Middleware\MozoAutenticado.
Route::prefix('mozo/{token}')->name('mozo.')->group(function () {
    // 'abierto': las únicas dos rutas a las que se llega sin haber puesto el PIN.
    Route::get('/', [App\Http\Controllers\MozoController::class, 'pin'])
        ->middleware('mozo:abierto')->name('pin');
    Route::post('/pin', [App\Http\Controllers\MozoController::class, 'validarPin'])
        ->middleware(['mozo:abierto', 'throttle:20,1'])->name('pin.validar');

    Route::middleware(['mozo', 'throttle:mozo'])->group(function () {
        Route::get('/tablero', [App\Http\Controllers\MozoController::class, 'tablero'])->name('tablero');
        Route::get('/estado', [App\Http\Controllers\MozoController::class, 'estado'])->name('estado');
        Route::post('/salir', [App\Http\Controllers\MozoController::class, 'salir'])->name('salir');
        Route::post('/mesa/{mesa}/abrir', [App\Http\Controllers\MozoController::class, 'abrirMesa'])->name('mesa.abrir');
        Route::post('/comanda/{comanda}/item', [App\Http\Controllers\MozoController::class, 'agregarItem'])->name('item.agregar');
        Route::patch('/comanda/{comanda}/item/{item}', [App\Http\Controllers\MozoController::class, 'cambiarCantidad'])->name('item.cantidad');
        Route::delete('/comanda/{comanda}/item/{item}', [App\Http\Controllers\MozoController::class, 'borrarItem'])->name('item.borrar');
        Route::post('/comanda/{comanda}/cerrar', [App\Http\Controllers\MozoController::class, 'cerrar'])->name('comanda.cerrar');
        Route::post('/comanda/{comanda}/reabrir', [App\Http\Controllers\MozoController::class, 'reabrir'])->name('comanda.reabrir');
        Route::get('/cerradas', [App\Http\Controllers\MozoController::class, 'cerradas'])->name('cerradas');
    });
});

// Rutas de Analytics (requieren autenticación, usando slug)
Route::middleware(['auth'])->group(function () {
    Route::prefix('analytics')->name('analytics.')->group(function () {
        Route::get('/dashboard', [App\Http\Controllers\AnalyticsController::class, 'dashboard'])->name('dashboard');
        Route::get('/comercio/{comercio:slug}', [App\Http\Controllers\AnalyticsController::class, 'comercio'])->name('comercio');
        Route::get('/export/{comercio:slug}', [App\Http\Controllers\AnalyticsController::class, 'exportConversations'])->name('export');
        Route::post('/comercio/{comercio:slug}/reclassify', [App\Http\Controllers\AnalyticsController::class, 'reclassifyIntents'])
            ->name('reclassify')->middleware('plan.feature:intencion_ia');
    });
});

require __DIR__.'/auth.php';
