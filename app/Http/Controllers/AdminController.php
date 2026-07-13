<?php

namespace App\Http\Controllers;

use App\Mail\WelcomeEmail;
use App\Models\ChatLanding;
use App\Models\Comercio;
use App\Models\Menu;
use App\Models\SiteVisit;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;

class AdminController extends Controller
{
    /**
     * Lista de todos los usuarios
     */
    public function users(Request $request)
    {
        $filterUser = null;

        if ($request->filled('user_id')) {
            $filterUser = User::with('comercios')->find($request->user_id);
            $users = User::with('comercios')
                ->where('id', $request->user_id)
                ->paginate(20);
        } else {
            $users = User::with('comercios')->latest()->paginate(20);
        }

        return view('admin.users.index', compact('users', 'filterUser'));
    }

    /**
     * Ver detalles de un usuario
     */
    public function showUser(User $user)
    {
        return redirect()->route('admin.users.edit', $user);
    }

    /**
     * Mostrar formulario para crear usuario
     */
    public function createUser()
    {
        return view('admin.users.create');
    }

    /**
     * Crear usuario
     */
    public function storeUser(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'whatsapp' => 'nullable|string|max:20',
            'role' => 'required|in:super_admin,user',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'whatsapp' => $validated['whatsapp'],
            'password' => bcrypt(\Illuminate\Support\Str::random(32)),
            'role' => $validated['role'],
            'email_verified_at' => now(),
        ]);

        // Enviar email de bienvenida con link para establecer contraseña
        try {
            $resetToken = Password::createToken($user);
            $resetUrl = route('password.reset', ['token' => $resetToken, 'email' => $user->email]);
            Mail::to($user->email)->queue(new WelcomeEmail($user, $resetUrl));
        } catch (\Exception $e) {
            Log::error('Error enviando email de bienvenida: '.$e->getMessage());
        }

        return redirect()->route('admin.users.show', $user)
            ->with('success', 'Usuario creado exitosamente.');
    }

    /**
     * Editar usuario
     */
    public function editUser(User $user)
    {
        return view('admin.users.edit', compact('user'));
    }

    /**
     * Actualizar usuario
     */
    public function updateUser(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,'.$user->id,
            'whatsapp' => 'nullable|string|max:20',
            'role' => 'required|in:super_admin,user',
        ]);

        $user->update($validated);

        return redirect()->route('admin.users.show', $user)
            ->with('success', 'Usuario actualizado exitosamente.');
    }

    /**
     * Lista de todos los comercios
     */
    public function comercios()
    {
        $comercios = Comercio::with('user')->withCount('menus')->latest()->paginate(20);

        return view('admin.comercios.index', compact('comercios'));
    }

    /**
     * Ver detalles de un comercio
     */
    public function showComercio(Comercio $comercio)
    {
        return redirect()->route('menus.index', $comercio);
    }

    public function updateComercio(Request $request, Comercio $comercio)
    {
        // Redirigir al método update del ComercioController con los parámetros correctos
        return redirect()->route('comercios.update', $comercio);
    }

    /**
     * Lista de todos los menús
     */
    public function menus()
    {
        $menus = Menu::with(['comercio.user'])->latest()->paginate(20);

        return view('admin.menus.index', compact('menus'));
    }

    /**
     * Lista de menús eliminados (baja lógica)
     */
    public function menusDeleted()
    {
        $menus = Menu::onlyTrashed()
            ->with(['comercio.user'])
            ->latest('deleted_at')
            ->paginate(20);

        return view('admin.menus.deleted', compact('menus'));
    }

    /**
     * Restaurar un menú eliminado
     */
    public function restoreMenu($menuId)
    {
        // Buscar el menú incluso si está eliminado
        $menu = Menu::withTrashed()->findOrFail($menuId);

        // Verificar que el menú esté eliminado
        if (! $menu->trashed()) {
            return redirect()->route('admin.menus.index')
                ->with('error', 'Este menú no está eliminado.');
        }

        $menu->restore();

        return redirect()->route('admin.menus.deleted')
            ->with('success', 'Menú restaurado exitosamente.');
    }

    /**
     * Ver detalles de un menú
     */
    public function showMenu(Menu $menu)
    {
        $menu->load(['comercio.user']);

        return view('admin.menus.show', compact('menu'));
    }

    public function editMenu(Menu $menu)
    {
        // Cargar las relaciones necesarias
        $menu->load(['comercio.user']);

        // Asegurar que el archivo del menú esté disponible en public_html (si existe)
        if ($menu->archivo_original) {
            $this->ensureMenuFileInPublicHtml($menu->archivo_original);
        }

        return view('menus.create', [
            'comercio' => $menu->comercio,
            'menu' => $menu,
            'isEdit' => true,
        ]);
    }

    public function updateMenu(Request $request, Menu $menu)
    {
        // Cargar la relación comercio si no está cargada
        if (! $menu->relationLoaded('comercio')) {
            $menu->load('comercio');
        }

        // Verificar que el comercio existe
        if (! $menu->comercio) {
            return redirect()->back()->with('error', 'No se encontró el comercio asociado al menú.');
        }

        // Llamar directamente al método update del MenuController
        $menuController = new \App\Http\Controllers\MenuController;

        return $menuController->update($request, $menu->comercio, $menu);
    }

    /**
     * Eliminar comercio (Super Admin)
     */
    public function destroyComercio(Comercio $comercio)
    {
        // Eliminar logo del storage si existe
        if ($comercio->logo_url && \Storage::disk('public')->exists($comercio->logo_url)) {
            \Storage::disk('public')->delete($comercio->logo_url);
        }

        // Eliminar el comercio (esto también eliminará los menús asociados por la relación)
        $comercio->delete();

        return redirect()->route('admin.comercios.index')->with('success', 'Comercio eliminado exitosamente');
    }

    /**
     * Eliminar usuario (Super Admin)
     */
    public function destroyUser(string $id)
    {
        $user = User::findOrFail($id);

        // No permitir eliminar al usuario actual
        if ($user->id === auth()->id()) {
            return redirect()->route('admin.users.index')->with('error', 'No puedes eliminar tu propia cuenta');
        }

        // Si el usuario tiene comercios, eliminarlos también (esto eliminará los menús asociados)
        if ($user->comercios->count() > 0) {
            foreach ($user->comercios as $comercio) {
                // Eliminar logo del storage si existe
                if ($comercio->logo_url && \Storage::disk('public')->exists($comercio->logo_url)) {
                    \Storage::disk('public')->delete($comercio->logo_url);
                }
                $comercio->delete();
            }
        }

        // Eliminar el usuario
        $user->delete();

        return redirect()->route('admin.users.index')->with('success', 'Usuario eliminado exitosamente');
    }

    /**
     * Asegurar que el archivo del menú esté en public_html/storage (sin errores si falla)
     */
    private function ensureMenuFileInPublicHtml(string $archivoPath): void
    {
        // Solo verificar/copiar si estamos en producción
        if (! file_exists('/home/u560898880/domains')) {
            return;
        }

        $filename = basename($archivoPath);
        $targetFile = '/home/u560898880/domains/servime.net/public_html/storage/menus/'.$filename;

        // Si ya existe en public_html, no hacer nada
        if (file_exists($targetFile)) {
            return;
        }

        // Si no existe, copiarlo automáticamente
        $this->copyMenuToPublicHtml($archivoPath);
    }

    /**
     * Copiar archivo de menú a public_html/storage automáticamente
     */
    private function copyMenuToPublicHtml(string $archivoPath): void
    {
        // Ruta directa conocida para servime.net en producción
        $publicHtmlPath = '/home/u560898880/domains/servime.net/public_html/storage/menus';

        // Verificar si estamos en producción (Hostinger)
        if (! file_exists('/home/u560898880/domains')) {
            // No estamos en producción, salir silenciosamente
            return;
        }

        try {
            // Ruta del archivo fuente
            $sourceFile = storage_path('app/public/'.$archivoPath);

            if (! file_exists($sourceFile)) {
                Log::error('Archivo de menú no encontrado para copiar', ['path' => $sourceFile]);

                return;
            }

            // Crear directorio si no existe
            if (! is_dir($publicHtmlPath)) {
                if (! mkdir($publicHtmlPath, 0775, true)) {
                    Log::error('No se pudo crear directorio de menús en public_html', ['path' => $publicHtmlPath]);

                    return;
                }
                chmod($publicHtmlPath, 0775);
            }

            // Extraer nombre del archivo
            $filename = basename($archivoPath);
            $targetFile = $publicHtmlPath.'/'.$filename;

            // Intentar copiar
            if (! copy($sourceFile, $targetFile)) {
                $error = error_get_last();
                Log::error('Error al copiar archivo de menú', [
                    'source' => $sourceFile,
                    'target' => $targetFile,
                    'error' => $error,
                    'source_exists' => file_exists($sourceFile),
                    'target_dir_writable' => is_writable($publicHtmlPath),
                ]);

                return;
            }

            // Asegurar permisos
            chmod($targetFile, 0664);

            Log::info('Archivo de menú copiado exitosamente a public_html', [
                'file' => $filename,
                'target' => $targetFile,
                'size' => filesize($targetFile),
            ]);

        } catch (\Exception $e) {
            Log::error('Excepción al copiar archivo de menú', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    public function landingChat(Request $request)
    {
        $query = ChatLanding::latest();

        if ($request->filled('session_id')) {
            $query->where('session_id', 'like', '%'.$request->session_id.'%');
        }

        $mensajes = $query->paginate(50)->withQueryString();

        $totalMensajes = ChatLanding::count();
        $totalSesiones = ChatLanding::distinct('session_id')->count('session_id');
        $hoy = ChatLanding::whereDate('created_at', today())->count();

        return view('admin.landing-chat.index', compact('mensajes', 'totalMensajes', 'totalSesiones', 'hoy'));
    }

    /**
     * Analytics de la web pública: de dónde entran (canal, país/ciudad) y con qué dispositivo.
     */
    public function webAnalytics(Request $request)
    {
        $dias = (int) $request->input('dias', 30);
        $dias = in_array($dias, [7, 30, 90, 365], true) ? $dias : 30;
        $desde = now()->subDays($dias);

        $visitas = SiteVisit::where('created_at', '>=', $desde)->count();
        $visitantes = SiteVisit::where('created_at', '>=', $desde)
            ->distinct('visitor_id')->count('visitor_id');

        // Ranking por columna, ya normalizado a {label, total} para que la vista sea tonta
        $ranking = function (string $columna, int $limite = 10) use ($desde) {
            return SiteVisit::where('created_at', '>=', $desde)
                ->whereNotNull($columna)
                ->selectRaw("{$columna} as valor, COUNT(*) as total")
                ->groupBy($columna)
                ->orderByDesc('total')
                ->limit($limite)
                ->get()
                ->map(fn ($f) => ['label' => (string) $f->valor, 'total' => (int) $f->total])
                ->all();
        };

        $porCanal = $ranking('source');
        $porDispositivo = $ranking('device', 5);
        $porCiudad = $ranking('city', 10);

        $porPais = SiteVisit::where('created_at', '>=', $desde)
            ->whereNotNull('country_code')
            ->selectRaw('country_code, country, COUNT(*) as total')
            ->groupBy('country_code', 'country')
            ->orderByDesc('total')
            ->limit(10)
            ->get()
            ->map(fn ($f) => [
                'label' => $f->country ?: $f->country_code,
                'code' => $f->country_code,
                'total' => (int) $f->total,
            ])
            ->all();

        // Visitas que todavía no se geolocalizaron (cola pendiente, IP local o fallo del proveedor)
        $sinGeo = SiteVisit::where('created_at', '>=', $desde)
            ->whereNull('country_code')->count();

        $canalTop = $porCanal[0]['label'] ?? '—';

        return view('admin.web-analytics.index', compact(
            'dias', 'visitas', 'visitantes', 'porCanal', 'porDispositivo',
            'porPais', 'porCiudad', 'sinGeo', 'canalTop'
        ));
    }
}
