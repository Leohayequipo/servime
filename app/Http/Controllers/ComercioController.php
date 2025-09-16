<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Comercio;
class ComercioController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $comercios = Comercio::where('user_id', auth()->id())->get();
        return view('comercios.index', compact('comercios'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('comercios.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
            // Validar los datos
        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'direccion' => 'nullable|string|max:255',
            'horario' => 'nullable|string|max:255',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        // Crear el comercio
        $comercio = new Comercio();
        $comercio->user_id = auth()->id();
        $comercio->nombre = $validated['nombre'];
        $comercio->slug = \Str::slug($validated['nombre']);
        $comercio->descripcion = $validated['descripcion'];
        $comercio->direccion = $validated['direccion'];
        $comercio->horario = $validated['horario'];

        // Procesar logo si se subió
        if ($request->hasFile('logo')) {
            $logoPath = $request->file('logo')->store('logos', 'public');
            $comercio->logo_url = $logoPath;
        }

        $comercio->save();

        // Redirigir con mensaje de éxito
        return redirect()->route('dashboard')->with('success', 'Comercio creado exitosamente');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $comercio = Comercio::where('user_id', auth()->id())->findOrFail($id);
        return view('comercios.edit', compact('comercio'));
    }
    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $comercio = Comercio::where('user_id', auth()->id())->findOrFail($id);
        
        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'direccion' => 'nullable|string|max:255',
            'horario' => 'nullable|string|max:255',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        $comercio->nombre = $validated['nombre'];
        $comercio->slug = \Str::slug($validated['nombre']);
        $comercio->descripcion = $validated['descripcion'];
        $comercio->direccion = $validated['direccion'];
        $comercio->horario = $validated['horario'];

        // Manejar eliminación de logo
        if ($request->has('delete_logo') && $request->delete_logo == '1') {
            // Eliminar archivo del storage si existe
            if ($comercio->logo_url && \Storage::disk('public')->exists($comercio->logo_url)) {
                \Storage::disk('public')->delete($comercio->logo_url);
            }
            $comercio->logo_url = null;
        }
        // Manejar subida de nuevo logo
        elseif ($request->hasFile('logo')) {
            // Eliminar logo anterior si existe
            if ($comercio->logo_url && \Storage::disk('public')->exists($comercio->logo_url)) {
                \Storage::disk('public')->delete($comercio->logo_url);
            }
            $logoPath = $request->file('logo')->store('logos', 'public');
            $comercio->logo_url = $logoPath;
        }

        $comercio->save();

        return redirect()->route('comercios.index')->with('success', 'Comercio actualizado exitosamente');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $comercio = Comercio::where('user_id', auth()->id())->findOrFail($id);
        
        // Eliminar logo del storage si existe
        if ($comercio->logo_url && \Storage::disk('public')->exists($comercio->logo_url)) {
            \Storage::disk('public')->delete($comercio->logo_url);
        }
        
        // Eliminar el comercio (esto también eliminará los menús asociados por la relación)
        $comercio->delete();
        
        return redirect()->route('comercios.index')->with('success', 'Comercio eliminado exitosamente');
    }
}
