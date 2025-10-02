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
    public function index($comercioId)
    {
        $comercio = Comercio::where('user_id', auth()->id())->findOrFail($comercioId);
        $menus = $comercio->menus()->orderBy('created_at', 'desc')->get();
        
        return view('menus.index', compact('comercio', 'menus'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create($comercioId)
    {
        $comercio = Comercio::where('user_id', auth()->id())->findOrFail($comercioId);
        $categories = QuestionCategory::active()->ordered()->get();
        $menu = null; // No hay menú para crear
        $isEdit = false;
        return view('menus.create', compact('comercio', 'categories', 'menu', 'isEdit'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, $comercioId)
    {
        $comercio = Comercio::where('user_id', auth()->id())->findOrFail($comercioId);
        
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
    public function show($comercioId, $menuId)
    {
        $comercio = Comercio::where('user_id', auth()->id())->findOrFail($comercioId);
        $menu = $comercio->menus()->findOrFail($menuId);
        
        // Redirigir a la página de edición donde está integrada toda la funcionalidad
        return redirect()->route('menus.edit', [$comercio, $menu]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($comercioId, $menuId)
    {
        $comercio = Comercio::where('user_id', auth()->id())->findOrFail($comercioId);
        $menu = $comercio->menus()->findOrFail($menuId);
        $categories = QuestionCategory::active()->ordered()->get();
        $isEdit = true;
        
        return view('menus.create', compact('comercio', 'menu', 'categories', 'isEdit'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $comercioId, $menuId)
    {
        $comercio = Comercio::where('user_id', auth()->id())->findOrFail($comercioId);
        $menu = $comercio->menus()->findOrFail($menuId);
        
        $validated = $request->validate([
            'nombre' => 'nullable|string|max:255',
            'archivo' => 'nullable|file|mimes:pdf,jpeg,png,jpg,gif|max:10240',
            'formato' => 'required|in:pdf,image'
        ]);

        // Si se sube un nuevo archivo
        if ($request->hasFile('archivo')) {
            // Eliminar archivo anterior
            if ($menu->archivo_original && Storage::disk('public')->exists($menu->archivo_original)) {
                Storage::disk('public')->delete($menu->archivo_original);
            }
            
            $archivoPath = $request->file('archivo')->store('menus', 'public');
            $menu->archivo_original = $archivoPath;
        }

        $menu->nombre = $request->nombre;
        $menu->formato = $validated['formato'];
        $menu->procesado = false; // Marcar como no procesado si se actualiza
        $menu->save();

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
    public function destroy($comercioId, $menuId)
    {
        $comercio = Comercio::where('user_id', auth()->id())->findOrFail($comercioId);
        $menu = $comercio->menus()->findOrFail($menuId);
        
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
    public function activar($comercioId, $menuId)
    {
        $comercio = Comercio::where('user_id', auth()->id())->findOrFail($comercioId);
        $menu = $comercio->menus()->findOrFail($menuId);
        
        // Desactivar todos los menús del comercio
        $comercio->menus()->update(['activo' => false]);
        
        // Activar el menú seleccionado
        $menu->update(['activo' => true]);
        
        return redirect()->route('menus.index', $comercio)
            ->with('success', 'Menú activado exitosamente.');
    }
}
