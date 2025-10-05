<?php

namespace App\Http\Controllers;

use App\Mail\WelcomeEmail;
use App\Models\User;
use App\Models\Comercio;
use App\Models\Menu;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class AdminController extends Controller
{
    /**
     * Panel principal del Super Administrador
     */
    public function dashboard()
    {
        $stats = [
            'total_users' => User::count(),
            'total_comercios' => Comercio::count(),
            'total_menus' => Menu::count(),
            'super_admins' => User::where('role', 'super_admin')->count(),
            'regular_users' => User::where('role', 'user')->count(),
        ];

        $recent_users = User::with('comercios')->latest()->take(10)->get();
        $recent_comercios = Comercio::with('user')->latest()->take(10)->get();

        return view('admin.dashboard', compact('stats', 'recent_users', 'recent_comercios'));
    }

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
        $comercios = Comercio::with(['user', 'menus'])->latest()->paginate(20);
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
        // Redirigir al método update del MenuController con los parámetros correctos
        return redirect()->route('menus.update', [
            'comercio' => $menu->comercio,
            'menu' => $menu
        ]);
    }

    /**
     * Mostrar formulario para crear comercio
     */
    public function createComercio()
    {
        // Solo mostrar usuarios que NO tengan comercio asignado
        $users = User::where('role', 'user')
                    ->whereDoesntHave('comercios')
                    ->get();
        return view('admin.comercios.create', compact('users'));
    }

    /**
     * Crear comercio
     */
    public function storeComercio(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'direccion' => 'nullable|string|max:255',
            'horario' => 'nullable|string|max:255',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        $targetUser = User::findOrFail($validated['user_id']);

        $comercio = new Comercio();
        $comercio->user_id = $validated['user_id'];
        $comercio->nombre = $validated['nombre'];
        $comercio->slug = \Illuminate\Support\Str::slug($validated['nombre']);
        $comercio->descripcion = $validated['descripcion'];
        $comercio->direccion = $validated['direccion'];
        $comercio->horario = $validated['horario'];

        // Manejar logo si se sube
        if ($request->hasFile('logo')) {
            $logoPath = $request->file('logo')->store('logos', 'public');
            $comercio->logo_url = $logoPath;
        }

        $comercio->save();

        return redirect()->route('admin.comercios.show', $comercio)
            ->with('success', 'Comercio creado exitosamente.');
    }

    /**
     * Mostrar formulario para crear menú
     */
    public function createMenu()
    {
        $comercios = Comercio::with('user')->get();
        return view('admin.menus.create', compact('comercios'));
    }

    /**
     * Crear menú
     */
    public function storeMenu(Request $request)
    {
        $validated = $request->validate([
            'comercio_id' => 'required|exists:comercios,id',
            'nombre' => 'nullable|string|max:255',
            'archivo' => 'required|file|mimes:pdf,jpeg,png,jpg,gif|max:10240'
        ]);

        $comercio = Comercio::findOrFail($validated['comercio_id']);

        $menu = new Menu();
        $menu->comercio_id = $comercio->id;
        $menu->nombre = $validated['nombre'];

        // Guardar archivo
        $archivoPath = $request->file('archivo')->store('menus', 'public');
        $menu->archivo_original = $archivoPath;

        // Detectar formato automáticamente
        $extension = $request->file('archivo')->getClientOriginalExtension();
        $menu->formato = in_array(strtolower($extension), ['pdf']) ? 'pdf' : 'image';
        $menu->procesado = false;

        // Si es el primer menú del comercio, marcarlo como activo
        if ($comercio->menus()->count() === 0) {
            $menu->activo = true;
        }

        $menu->save();

        // Procesar con OpenAI
        $processor = new \App\Services\MenuProcessorService();
        $processed = $processor->processMenu($menu);

        if ($processed) {
            return redirect()->route('admin.menus.show', $menu)
                ->with('success', 'Menú creado y procesado exitosamente con IA.');
        } else {
            return redirect()->route('admin.menus.show', $menu)
                ->with('warning', 'Menú creado pero hubo un problema en el procesamiento.');
        }
    }

    /**
     * Generar preguntas frecuentes con IA para Super Admin
     */
    public function generateQuestions(Request $request, $comercioId)
    {
        // Super Admin puede generar preguntas para cualquier comercio
        $comercio = Comercio::findOrFail($comercioId);
        
        // Llamar al método del ComercioController
        $comercioController = new \App\Http\Controllers\ComercioController();
        return $comercioController->generateQuestions($request, $comercioId);
    }

    /**
     * Eliminar comercio (Super Admin)
     */
    public function destroyComercio(string $id)
    {
        $comercio = Comercio::findOrFail($id);
        
        // Eliminar logo del storage si existe
        if ($comercio->logo_url && \Storage::disk('public')->exists($comercio->logo_url)) {
            \Storage::disk('public')->delete($comercio->logo_url);
        }
        
        // Eliminar el comercio (esto también eliminará los menús asociados por la relación)
        $comercio->delete();
        
        return redirect()->route('admin.comercios.index')->with('success', 'Comercio eliminado exitosamente');
    }
}