<?php

namespace App\Http\Controllers;

use App\Mail\WelcomeEmail;
use App\Models\User;
use App\Models\Comercio;
use App\Models\Menu;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class AdminController extends Controller
{
    /**
     * Lista de todos los usuarios
     */
    public function users()
    {
        $users = User::with('comercios')->latest()->paginate(20);
        return view('admin.users.index', compact('users'));
    }

    /**
     * Ver detalles de un usuario
     */
    public function showUser(User $user)
    {
        $user->load('comercios.menus');
        return view('admin.users.show', compact('user'));
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
            'password' => 'required|string|min:8|confirmed',
            'role' => 'required|in:super_admin,user'
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'whatsapp' => $validated['whatsapp'],
            'password' => bcrypt($validated['password']),
            'role' => $validated['role'],
            'email_verified_at' => now()
        ]);

        // Enviar email de bienvenida con contraseña
        try {
            Mail::to($user->email)->send(new WelcomeEmail($user, $validated['password']));
        } catch (\Exception $e) {
            // Log del error pero no interrumpir la creación
            \Log::error('Error enviando email de bienvenida: ' . $e->getMessage());
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
            'email' => 'required|email|unique:users,email,' . $user->id,
            'whatsapp' => 'nullable|string|max:20',
            'role' => 'required|in:super_admin,user'
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
        $comercio->load(['user', 'menus']);
        return view('admin.comercios.show', compact('comercio'));
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
        if (!$menu->trashed()) {
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
        
        // Obtener categorías de preguntas como en el MenuController
        $categories = \App\Models\QuestionCategory::active()->ordered()->get();
        
        // Si no hay categorías, crear una colección vacía para evitar errores
        if ($categories->isEmpty()) {
            $categories = collect();
        }
        
        return view('menus.create', [
            'comercio' => $menu->comercio, 
            'menu' => $menu,
            'isEdit' => true,
            'categories' => $categories
        ]);
    }

    public function updateMenu(Request $request, Menu $menu)
    {
        // Cargar la relación comercio si no está cargada
        if (!$menu->relationLoaded('comercio')) {
            $menu->load('comercio');
        }
        
        // Verificar que el comercio existe
        if (!$menu->comercio) {
            return redirect()->back()->with('error', 'No se encontró el comercio asociado al menú.');
        }
        
        // Llamar directamente al método update del MenuController
        $menuController = new \App\Http\Controllers\MenuController();
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
        if (!file_exists('/home/u560898880/domains')) {
            return;
        }
        
        $filename = basename($archivoPath);
        $targetFile = '/home/u560898880/domains/servime.net/public_html/storage/menus/' . $filename;
        
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
        if (!file_exists('/home/u560898880/domains')) {
            // No estamos en producción, salir silenciosamente
            return;
        }
        
        try {
            // Ruta del archivo fuente
            $sourceFile = storage_path('app/public/' . $archivoPath);
            
            if (!file_exists($sourceFile)) {
                Log::error('Archivo de menú no encontrado para copiar', ['path' => $sourceFile]);
                return;
            }
            
            // Crear directorio si no existe
            if (!is_dir($publicHtmlPath)) {
                if (!mkdir($publicHtmlPath, 0775, true)) {
                    Log::error('No se pudo crear directorio de menús en public_html', ['path' => $publicHtmlPath]);
                    return;
                }
                chmod($publicHtmlPath, 0775);
            }
            
            // Extraer nombre del archivo
            $filename = basename($archivoPath);
            $targetFile = $publicHtmlPath . '/' . $filename;
            
            // Intentar copiar
            if (!copy($sourceFile, $targetFile)) {
                $error = error_get_last();
                Log::error('Error al copiar archivo de menú', [
                    'source' => $sourceFile,
                    'target' => $targetFile,
                    'error' => $error,
                    'source_exists' => file_exists($sourceFile),
                    'target_dir_writable' => is_writable($publicHtmlPath)
                ]);
                return;
            }
            
            // Asegurar permisos
            chmod($targetFile, 0664);
            
            Log::info('Archivo de menú copiado exitosamente a public_html', [
                'file' => $filename,
                'target' => $targetFile,
                'size' => filesize($targetFile)
            ]);
            
        } catch (\Exception $e) {
            Log::error('Excepción al copiar archivo de menú', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}