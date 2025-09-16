<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Menu;
use App\Models\Comercio;
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
        return view('menus.create', compact('comercio'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, $comercioId)
    {
        $comercio = Comercio::where('user_id', auth()->id())->findOrFail($comercioId);
        
        $validated = $request->validate([
            'archivo' => 'required|file|mimes:pdf,jpeg,png,jpg,gif|max:10240', // 10MB max
            'formato' => 'required|in:pdf,image'
        ]);

        // Subir archivo
        $archivoPath = $request->file('archivo')->store('menus', 'public');
        
        // Crear menú
        $menu = new Menu();
        $menu->comercio_id = $comercio->id;
        $menu->archivo_original = $archivoPath;
        $menu->formato = $validated['formato'];
        $menu->procesado = false;
        $menu->save();

        // TODO: Aquí procesaremos con GPT en el siguiente paso
        // Por ahora solo guardamos el archivo

        return redirect()->route('menus.index', $comercio)
            ->with('success', 'Menú subido exitosamente. Se procesará automáticamente.');
    }

    /**
     * Display the specified resource.
     */
    public function show($comercioId, $menuId)
    {
        $comercio = Comercio::where('user_id', auth()->id())->findOrFail($comercioId);
        $menu = $comercio->menus()->findOrFail($menuId);
        
        return view('menus.show', compact('comercio', 'menu'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($comercioId, $menuId)
    {
        $comercio = Comercio::where('user_id', auth()->id())->findOrFail($comercioId);
        $menu = $comercio->menus()->findOrFail($menuId);
        
        return view('menus.edit', compact('comercio', 'menu'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $comercioId, $menuId)
    {
        $comercio = Comercio::where('user_id', auth()->id())->findOrFail($comercioId);
        $menu = $comercio->menus()->findOrFail($menuId);
        
        $validated = $request->validate([
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

        $menu->formato = $validated['formato'];
        $menu->procesado = false; // Marcar como no procesado si se actualiza
        $menu->save();

        return redirect()->route('menus.index', $comercio)
            ->with('success', 'Menú actualizado exitosamente.');
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
}
