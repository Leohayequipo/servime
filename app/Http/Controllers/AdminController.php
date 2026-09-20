<?php

namespace App\Http\Controllers;

use App\Mail\WelcomeEmail;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\ChatConversation;
use App\Models\ChatLanding;
use App\Models\Comercio;
use App\Models\IntentClassificationRun;
use App\Models\Menu;
use App\Models\TrackingEvent;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;

class AdminController extends Controller
{
    /**
     * Caracteres válidos en un username.
     *
     * No alcanza con `alpha_dash`: los usernames autogenerados llevan punto
     * (juan.perez), y esa regla los rechaza.
     */
    private const REGLA_USERNAME = 'regex:/^[A-Za-z0-9._-]+$/';

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
            'username' => ['nullable', 'string', 'max:50', self::REGLA_USERNAME, 'unique:users,username'],
            'whatsapp' => 'nullable|string|max:20',
            'role' => 'required|in:super_admin,user',
        ]);

        // Usuario y contraseña iniciales.
        //
        // Antes se guardaba una clave aleatoria de 32 caracteres, inservible, y la cuenta
        // dependía del mail de bienvenida para poder usarse. Ahora se genera una legible
        // y se le muestra al admin una sola vez, así puede entregarla en el momento sin
        // depender de que el mail llegue.
        $username = $validated['username'] ?: User::generarUsername($validated['email'], $validated['name']);
        $passwordInicial = User::generarPasswordInicial();

        $user = User::create([
            'name' => $validated['name'],
            'username' => $username,
            'email' => $validated['email'],
            'whatsapp' => $validated['whatsapp'],
            'password' => bcrypt($passwordInicial),
            'role' => $validated['role'],
            'email_verified_at' => now(),
        ]);

        // Enviar email de bienvenida con link para establecer contraseña
        try {
            // Broker 'bienvenida': el link dura 7 días en vez de 1 hora. El comerciante
            // no está esperando este mail y lo puede abrir al otro día; con el broker
            // por defecto le llegaba vencido y tenía que pedir otro.
            $resetToken = Password::broker('bienvenida')->createToken($user);
            $resetUrl = route('password.reset', [
                'token' => $resetToken,
                'email' => $user->email,
                'broker' => 'bienvenida',
            ]);
            Mail::to($user->email)->queue(new WelcomeEmail($user, $resetUrl));
        } catch (\Exception $e) {
            Log::error('Error enviando email de bienvenida: '.$e->getMessage());
        }

        // Las credenciales van por flash y se muestran UNA vez: la contraseña queda
        // hasheada en la base, así que después no hay forma de volver a mostrarla.
        //
        // Se redirige a `edit` y no a `show` a propósito: showUser() hace otro redirect
        // a edit, y el flash no sobrevive esa segunda vuelta.
        return redirect()->route('admin.users.edit', $user)
            ->with('success', 'Usuario creado exitosamente.')
            ->with('credenciales', [
                'usuario' => $user->username,
                'email' => $user->email,
                'password' => $passwordInicial,
            ]);
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
            'username' => ['nullable', 'string', 'max:50', self::REGLA_USERNAME, 'unique:users,username,'.$user->id],
            'whatsapp' => 'nullable|string|max:20',
            'role' => 'required|in:super_admin,user',
        ]);

        // Si lo dejan vacío se regenera: la cuenta nunca puede quedar sin usuario,
        // porque es una de las dos formas de entrar (usuario o email).
        $validated['username'] = $validated['username'] ?: User::generarUsername($validated['email'], $validated['name']);

        $user->update($validated);

        return redirect()->route('admin.users.show', $user)
            ->with('success', 'Usuario actualizado exitosamente.');
    }

    /**
     * Dejar al comercio "como nuevo": borra toda su actividad y le resetea la cuota de IA.
     *
     * Borra ACTIVIDAD, no CONFIGURACIÓN: el menú, los platos, las promociones, la portada,
     * los colores, el plan y los usuarios quedan intactos. Lo que se va es lo que el
     * comercio generó al ser usado, que es lo que ensucia analytics cuando estuvo en
     * pruebas y arranca de verdad.
     *
     * Es irreversible y no hay papelera: por eso vive sólo acá adentro, detrás del
     * middleware `super_admin`, y no en el panel del comercio.
     */
    public function resetActividadComercio(Comercio $comercio)
    {
        $borrado = DB::transaction(function () use ($comercio) {
            // Los carritos primero, y a mano: `cart_items` guarda `cart_id` sin foreign key
            // (ver la migración que la crea), así que borrar el carrito NO se lleva sus
            // ítems por cascada. Sin esto quedaban filas huérfanas para siempre.
            $idsCarritos = Cart::where('comercio_id', $comercio->id)->pluck('id');
            $items = CartItem::whereIn('cart_id', $idsCarritos)->delete();
            $carritos = Cart::whereKey($idsCarritos)->delete();

            $conteo = [
                'conversaciones' => ChatConversation::where('comercio_id', $comercio->id)->delete(),
                'eventos' => TrackingEvent::where('comercio_id', $comercio->id)->delete(),
                'intents' => IntentClassificationRun::where('comercio_id', $comercio->id)->delete(),
                'carritos' => $carritos,
                'items_de_carrito' => $items,
            ];

            // La cuota del mes también vuelve a cero. Es plata: por eso queda registrado
            // quién lo hizo y cuánto se borró.
            $comercio->resetUsoIA();

            return $conteo;
        });

        Log::warning('Reset de actividad de comercio', [
            'comercio_id' => $comercio->id,
            'comercio' => $comercio->nombre,
            'admin_id' => auth()->id(),
            'borrado' => $borrado,
        ]);

        return redirect()->route('comercios.index')
            ->with('success', "Se borró la actividad de «{$comercio->nombre}»: {$borrado['conversaciones']} conversación(es) y {$borrado['eventos']} evento(s). La cuota de IA quedó en cero.");
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

        // Igual que en MenuController@edit: mientras la IA lo lee, sólo el progreso. El
        // editor guarda sobre el mismo menú que el job está escribiendo.
        if ($menu->estado === Menu::ESTADO_PROCESANDO) {
            return view('menus.procesando', ['comercio' => $menu->comercio, 'menu' => $menu]);
        }

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

        return redirect()->route('comercios.index')->with('success', 'Comercio eliminado exitosamente');
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
}
