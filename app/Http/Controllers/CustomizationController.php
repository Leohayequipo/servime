<?php

namespace App\Http\Controllers;

use App\Models\Comercio;
use Illuminate\Http\Request;

class CustomizationController extends Controller
{
    /**
     * Mostrar formulario de personalización
     */
    public function show($comercioId)
    {
        $comercio = Comercio::findOrFail($comercioId);
        return view('comercios.customize', compact('comercio'));
    }

    /**
     * Actualizar configuración de personalización
     */
    public function update(Request $request, $comercioId)
    {
        $comercio = Comercio::findOrFail($comercioId);
        
        $request->validate([
            'primary_color' => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'secondary_color' => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'accent_color' => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'background_color' => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'welcome_message' => 'nullable|string|max:500',
            'menu_description' => 'nullable|string|max:1000',
            'contact_info' => 'nullable|string|max:500',
            'chatbot_name' => 'required|string|max:50',
            'show_contact_info' => 'boolean',
            'enable_ai_responses' => 'boolean',
            'page_title' => 'nullable|string|max:100',
            'page_description' => 'nullable|string|max:500',
            'favicon_url' => 'nullable|string|max:255'
        ]);

        $comercio->update($request->all());

        return redirect()
            ->route('comercios.customize', $comercio)
            ->with('success', 'Configuración de personalización actualizada correctamente.');
    }

    /**
     * Vista previa de la página pública
     */
    public function preview($comercioId)
    {
        $comercio = Comercio::findOrFail($comercioId);
        $menus = $comercio->menus()->where('procesado', true)->get();
        
        return view('menu.public', compact('comercio', 'menus'))->with('preview', true);
    }

    /**
     * Resetear configuración a valores por defecto
     */
    public function reset($comercioId)
    {
        $comercio = Comercio::findOrFail($comercioId);
        
        $comercio->update([
            'primary_color' => '#3B82F6',
            'secondary_color' => '#6B7280',
            'accent_color' => '#10B981',
            'background_color' => '#FFFFFF',
            'welcome_message' => null,
            'menu_description' => null,
            'contact_info' => null,
            'chatbot_name' => 'Asistente IA',
            'show_contact_info' => true,
            'enable_ai_responses' => true,
            'page_title' => null,
            'page_description' => null,
            'favicon_url' => null
        ]);

        return redirect()
            ->route('comercios.customize', $comercio)
            ->with('success', 'Configuración reseteada a valores por defecto.');
    }
}
