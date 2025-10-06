<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Menu;
use App\Models\Comercio;
use App\Models\QuestionCategory;
use App\Services\MenuProcessorService;
use Illuminate\Support\Facades\Storage;

class MenuController extends Controller
{
    /**
     * Display all menus for the authenticated user.
     */
    public function all()
    {
        $menus = Menu::whereHas('comercio', function($query) {
            $query->where('user_id', auth()->id());
        })->with('comercio')->orderBy('created_at', 'desc')->get();
        
        return view('menus.all', compact('menus'));
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Comercio $comercio)
    {
        $user = auth()->user();
        
        // Validar permisos: Super Admin puede ver cualquiera, usuario común solo sus comercios
        if (!$user->isSuperAdmin() && $comercio->user_id !== $user->id) {
            abort(403, 'No tienes permiso para ver los menús de este comercio.');
        }
        
        // Cargar relación de usuario
        $comercio->load('user');
        
        $menus = $comercio->menus()->orderBy('created_at', 'desc')->get();
        
        return view('menus.index', compact('comercio', 'menus'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Comercio $comercio)
    {
        $user = auth()->user();
        
        // Validar permisos: Super Admin puede ver cualquiera, usuario común solo sus comercios
        if (!$user->isSuperAdmin() && $comercio->user_id !== $user->id) {
            abort(403, 'No tienes permiso para crear menús en este comercio.');
        }
        
        // Cargar relación de usuario
        $comercio->load('user');
        
        $categories = QuestionCategory::active()->ordered()->get();
        $menu = null; // No hay menú para crear
        $isEdit = false;
        return view('menus.create', compact('comercio', 'categories', 'menu', 'isEdit'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, Comercio $comercio)
    {
        $user = auth()->user();
        
        // Validar permisos: Super Admin puede crear en cualquiera, usuario común solo en sus comercios
        if (!$user->isSuperAdmin() && $comercio->user_id !== $user->id) {
            abort(403, 'No tienes permiso para crear menús en este comercio.');
        }
        
        $validated = $request->validate([
            'nombre' => 'nullable|string|max:255',
            'archivo' => 'required|file|mimes:pdf,jpeg,png,jpg,gif|max:10240', // 10MB max
        ]);

        // Subir archivo
        $archivoPath = $request->file('archivo')->store('menus', 'public');
        
        // Crear menú
        $menu = new Menu();
        $menu->comercio_id = $comercio->id;
        $menu->nombre = $request->nombre;
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
        try {
            $processor = new MenuProcessorService();
            $processed = $processor->processMenu($menu);
        } catch (\Exception $e) {
            dd('Error al procesar:', $e->getMessage(), $e->getTraceAsString());
        }

        if ($processed) {
            return redirect()->route('menus.index', $comercio)
                ->with('success', 'Menú subido y procesado exitosamente con IA.');
        } else {
            return redirect()->route('menus.index', $comercio)
                ->with('warning', 'Menú subido pero hubo un problema en el procesamiento.');
        }
    }

    /**
     * Display the specified resource.
     * Redirige a la página de edición ya que la funcionalidad está integrada ahí.
     */
    public function show(Comercio $comercio, Menu $menu)
    {
        $user = auth()->user();
        
        // Validar permisos y que el menú pertenezca al comercio
        if (!$user->isSuperAdmin() && $comercio->user_id !== $user->id) {
            abort(403, 'No tienes permiso para ver este menú.');
        }
        
        if ($menu->comercio_id !== $comercio->id) {
            abort(404, 'Menú no encontrado en este comercio.');
        }
        
        // Redirigir a la página de edición donde está integrada toda la funcionalidad
        return redirect()->route('menus.edit', [$comercio, $menu]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Comercio $comercio, Menu $menu)
    {
        $user = auth()->user();
        
        // Validar permisos y que el menú pertenezca al comercio
        if (!$user->isSuperAdmin() && $comercio->user_id !== $user->id) {
            abort(403, 'No tienes permiso para editar este menú.');
        }
        
        if ($menu->comercio_id !== $comercio->id) {
            abort(404, 'Menú no encontrado en este comercio.');
        }
        
        $categories = QuestionCategory::active()->ordered()->get();
        $isEdit = true;
        
        return view('menus.create', compact('comercio', 'menu', 'categories', 'isEdit'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Comercio $comercio, Menu $menu)
    {
        $user = auth()->user();
        
        // Validar permisos y que el menú pertenezca al comercio
        if (!$user->isSuperAdmin() && $comercio->user_id !== $user->id) {
            abort(403, 'No tienes permiso para actualizar este menú.');
        }
        
        if ($menu->comercio_id !== $comercio->id) {
            abort(404, 'Menú no encontrado en este comercio.');
        }
        
        $validated = $request->validate([
            'nombre' => 'nullable|string|max:255',
            'archivo' => 'nullable|file|mimes:pdf,jpeg,png,jpg,gif|max:10240',
            'formato' => 'nullable|in:pdf,image',
            // Campos del chatbot
            'chatbot_name' => 'nullable|string|max:50',
            'welcome_message' => 'nullable|string|max:500',
            // Campos de colores
            'primary_color' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'secondary_color' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'accent_color' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'background_color' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/'
        ]);


        

        // Si se sube un nuevo archivo
        if ($request->hasFile('archivo')) {
            // Eliminar archivo anterior
            if ($menu->archivo_original && Storage::disk('public')->exists($menu->archivo_original)) {
                Storage::disk('public')->delete($menu->archivo_original);
            }
            
            $archivoPath = $request->file('archivo')->store('menus', 'public');
            $menu->archivo_original = $archivoPath;
            
            // Detectar formato automáticamente
            $extension = $request->file('archivo')->getClientOriginalExtension();
            $menu->formato = in_array(strtolower($extension), ['pdf']) ? 'pdf' : 'image';
            $menu->procesado = false; // Marcar como no procesado
        }

        $menu->nombre = $request->nombre;
        
        // Solo actualizar formato si se envía
        if ($request->has('formato') && $request->formato) {
            $menu->formato = $validated['formato'];
            $menu->procesado = false; // Marcar como no procesado si se actualiza
        }
        
        $menu->save();

        // Actualizar campos del chatbot y colores en el comercio
        $needsUpdate = false;
        
        // Campos del chatbot
        if ($request->has('chatbot_name')) {
            $comercio->chatbot_name = $request->chatbot_name;
            $needsUpdate = true;
        }
        if ($request->has('welcome_message')) {
            $comercio->welcome_message = $request->welcome_message;
            $needsUpdate = true;
        }
        
        // Campos de colores
        if ($request->has('primary_color')) {
            $comercio->primary_color = $request->primary_color;
            $needsUpdate = true;
        }
        if ($request->has('secondary_color')) {
            $comercio->secondary_color = $request->secondary_color;
            $needsUpdate = true;
        }
        if ($request->has('accent_color')) {
            $comercio->accent_color = $request->accent_color;
            $needsUpdate = true;
        }
        if ($request->has('background_color')) {
            $comercio->background_color = $request->background_color;
            $needsUpdate = true;
        }
        
        if ($needsUpdate) {
            \Log::info('Actualizando campos del comercio', [
                'chatbot_name' => $request->input('chatbot_name'),
                'welcome_message' => $request->input('welcome_message'),
                'primary_color' => $request->input('primary_color'),
                'secondary_color' => $request->input('secondary_color'),
                'accent_color' => $request->input('accent_color'),
                'background_color' => $request->input('background_color')
            ]);
            
            $comercio->save();
            \Log::info('Campos del comercio guardados exitosamente');
        }

        // Reprocesar con OpenAI
        $processor = new MenuProcessorService();
        $processed = $processor->processMenu($menu);

        if ($processed) {
            return redirect()->route('menus.index', $comercio)
                ->with('success', 'Menú actualizado y reprocesado exitosamente con IA.');
        } else {
            return redirect()->route('menus.index', $comercio)
                ->with('warning', 'Menú actualizado pero hubo un problema en el reprocesamiento.');
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Comercio $comercio, Menu $menu)
    {
        $user = auth()->user();
        
        // Validar permisos y que el menú pertenezca al comercio
        if (!$user->isSuperAdmin() && $comercio->user_id !== $user->id) {
            abort(403, 'No tienes permiso para eliminar este menú.');
        }
        
        if ($menu->comercio_id !== $comercio->id) {
            abort(404, 'Menú no encontrado en este comercio.');
        }
        
        // Eliminar archivo del storage
        if ($menu->archivo_original && Storage::disk('public')->exists($menu->archivo_original)) {
            Storage::disk('public')->delete($menu->archivo_original);
        }
        
        $menu->delete();
        
        return redirect()->route('menus.index', $comercio)
            ->with('success', 'Menú eliminado exitosamente.');
    }

    /**
     * Activar un menú (desactivar otros del mismo comercio)
     */
    public function activar(Comercio $comercio, Menu $menu)
    {
        $user = auth()->user();
        
        // Validar permisos y que el menú pertenezca al comercio
        if (!$user->isSuperAdmin() && $comercio->user_id !== $user->id) {
            abort(403, 'No tienes permiso para activar este menú.');
        }
        
        if ($menu->comercio_id !== $comercio->id) {
            abort(404, 'Menú no encontrado en este comercio.');
        }
        
        // Desactivar todos los menús del comercio
        $comercio->menus()->update(['activo' => false]);
        
        // Activar el menú seleccionado
        $menu->update(['activo' => true]);
        
        return redirect()->route('menus.index', $comercio)
            ->with('success', 'Menú activado exitosamente.');
    }
}
