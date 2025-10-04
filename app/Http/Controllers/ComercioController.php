<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Comercio;
use App\Models\User;
use App\Models\QuestionCategory;
use Illuminate\Support\Str;
class ComercioController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $user = auth()->user();
        
        if ($user->isSuperAdmin()) {
            // Super Admin ve todos los comercios
            $comercios = Comercio::with('user')->latest()->get();
        } else {
            // Usuario común solo ve sus comercios
            $comercios = Comercio::where('user_id', $user->id)->get();
        }
        
        return view('comercios.index', compact('comercios'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $user = auth()->user();
        
        if ($user->isSuperAdmin()) {
            // Super Admin puede crear comercios para cualquier usuario
            $users = User::where('role', 'user')->get();
            return view('comercios.create', compact('users'));
        } else {
            // Usuario común: verificar si ya tiene un comercio
            if ($user->comercios()->count() > 0) {
                return redirect()->route('comercios.index')
                    ->with('warning', 'Ya tienes un comercio. Solo puedes tener uno.');
            }
            
            return view('comercios.create');
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $user = auth()->user();
        
        // Validar los datos
        $validationRules = [
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'direccion' => 'nullable|string|max:255',
            'horario' => 'nullable|string|max:255',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048'
        ];
        
        if ($user->isSuperAdmin()) {
            // Super Admin puede asignar el comercio a cualquier usuario
            $validationRules['user_id'] = 'required|exists:users,id';
        }
        
        $validated = $request->validate($validationRules);
        
        if ($user->isSuperAdmin()) {
            // Super Admin puede crear comercios para cualquier usuario
            $targetUser = User::findOrFail($validated['user_id']);
            
            // Verificar si el usuario ya tiene un comercio
            if ($targetUser->comercios()->count() > 0) {
                return redirect()->back()
                    ->withInput()
                    ->with('error', 'El usuario ya tiene un comercio. Solo puede tener uno.');
            }
            
            $comercio = new Comercio();
            $comercio->user_id = $validated['user_id'];
        } else {
            // Usuario común: verificar si ya tiene un comercio
            if ($user->comercios()->count() > 0) {
                return redirect()->route('comercios.index')
                    ->with('warning', 'Ya tienes un comercio. Solo puedes tener uno.');
            }
            
            $comercio = new Comercio();
            $comercio->user_id = auth()->id();
        }
        $comercio->nombre = $validated['nombre'];
        $comercio->slug = Str::slug($validated['nombre']);
        $comercio->descripcion = $validated['descripcion'];
        $comercio->direccion = $validated['direccion'];
        $comercio->horario = $validated['horario'];

        // Procesar logo si se subió
        if ($request->hasFile('logo')) {
            $comercio->logo_url = $this->saveSquareLogo($request->file('logo'), 512);
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
        $user = auth()->user();
        
        if ($user->isSuperAdmin()) {
            // Super Admin puede editar cualquier comercio
            $comercio = Comercio::with('user')->findOrFail($id);
        } else {
            // Usuario común solo puede editar sus comercios
            $comercio = Comercio::with('user')->where('user_id', $user->id)->findOrFail($id);
        }
        
        // Obtener categorías de preguntas
        $categories = QuestionCategory::active()->ordered()->get();
        
        // Si no hay categorías, crear una colección vacía para evitar errores
        if ($categories->isEmpty()) {
            $categories = collect();
        }
        
        return view('comercios.edit', compact('comercio', 'categories'));
    }
    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $comercio = Comercio::where('user_id', auth()->id())->findOrFail($id);
        
        $validated = $request->validate([
            // Información básica del comercio
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'direccion' => 'nullable|string|max:255',
            'horario' => 'nullable|string|max:255',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            
            // Configuración de personalización
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
            'favicon_url' => 'nullable|string|max:255',
            'quick_questions' => 'nullable|array',
            'quick_questions.*.icon' => 'required|string|max:10',
            'quick_questions.*.question' => 'required|string|max:255',
            'quick_questions.*.category' => 'required|string|in:menu,precios,horarios,ubicacion,contacto,general',
            'quick_questions.*.answer' => 'nullable|string|max:1000',
            'quick_questions.*.auto_generated' => 'boolean'
        ]);

        // Actualizar información básica
        $comercio->nombre = $validated['nombre'];
        $comercio->slug = Str::slug($validated['nombre']);
        $comercio->descripcion = $validated['descripcion'];
        $comercio->direccion = $validated['direccion'];
        $comercio->horario = $validated['horario'];

        // Actualizar configuración de personalización
        $comercio->primary_color = $validated['primary_color'];
        $comercio->secondary_color = $validated['secondary_color'];
        $comercio->accent_color = $validated['accent_color'];
        $comercio->background_color = $validated['background_color'];
        $comercio->welcome_message = $validated['welcome_message'];
        $comercio->menu_description = $validated['menu_description'];
        $comercio->contact_info = $validated['contact_info'];
        $comercio->chatbot_name = $validated['chatbot_name'];
        $comercio->show_contact_info = $validated['show_contact_info'] ?? false;
        $comercio->enable_ai_responses = $validated['enable_ai_responses'] ?? false;
        $comercio->page_title = $validated['page_title'];
        $comercio->page_description = $validated['page_description'];
        $comercio->favicon_url = $validated['favicon_url'];
        $comercio->quick_questions = $validated['quick_questions'] ?? [];

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
            $comercio->logo_url = $this->saveSquareLogo($request->file('logo'), 512);
        }

        $comercio->save();

        return redirect()->route('comercios.index')->with('success', 'Comercio actualizado exitosamente');
    }

    /**
     * Generar preguntas frecuentes con IA
     */
    public function generateQuestions(Request $request, string $id)
    {
        $comercio = Comercio::where('user_id', auth()->id())->findOrFail($id);
        
        // Llamar al método del CustomizationController
        $customizationController = new \App\Http\Controllers\CustomizationController();
        return $customizationController->generateQuestions($request, $id);
    }

    /**
     * Mejorar respuesta con IA
     */
    public function improveAnswer(Request $request, string $id)
    {
        $comercio = Comercio::where('user_id', auth()->id())->findOrFail($id);
        
        // Llamar al método del CustomizationController
        $customizationController = new \App\Http\Controllers\CustomizationController();
        return $customizationController->improveAnswer($request, $id);
    }

    /**
     * Vista previa del comercio
     */
    public function preview(string $id)
    {
        $user = auth()->user();
        
        if ($user->isSuperAdmin()) {
            // Super Admin puede ver cualquier comercio
            $comercio = Comercio::findOrFail($id);
        } else {
            // Usuario común solo puede ver sus comercios
            $comercio = Comercio::where('user_id', $user->id)->findOrFail($id);
        }
        
        // Llamar al método del CustomizationController
        $customizationController = new \App\Http\Controllers\CustomizationController();
        return $customizationController->preview($id);
    }

    /**
     * Guardar el logo en un tamaño cuadrado fijo (recorte centrado + resize)
     */
    private function saveSquareLogo(\Illuminate\Http\UploadedFile $file, int $size = 512): string
    {
        $path = storage_path('app/public/logos');
        if (!is_dir($path)) {
            mkdir($path, 0755, true);
        }

        // Si no está disponible la librería GD, hacer fallback al almacenamiento directo
        if (!function_exists('imagecreatetruecolor')) {
            return $file->store('logos', 'public');
        }

        // Determinar tipo y crear resource
        $srcPath = $file->getRealPath();
        [$w, $h, $type] = \getimagesize($srcPath);
        switch ($type) {
            case IMAGETYPE_JPEG:
                $src = \imagecreatefromjpeg($srcPath);
                break;
            case IMAGETYPE_PNG:
                $src = \imagecreatefrompng($srcPath);
                break;
            case IMAGETYPE_GIF:
                $src = \imagecreatefromgif($srcPath);
                break;
            default:
                // fallback: usar el almacenamiento directo
                return $file->store('logos', 'public');
        }

        // Recorte cuadrado centrado
        $side = min($w, $h);
        $srcX = (int)(($w - $side) / 2);
        $srcY = (int)(($h - $side) / 2);

        // Lienzo destino
        $dst = \imagecreatetruecolor($size, $size);
        // Preservar transparencia para PNG/GIF
        if (in_array($type, [IMAGETYPE_PNG, IMAGETYPE_GIF])) {
            \imagealphablending($dst, false);
            \imagesavealpha($dst, true);
            $transparent = \imagecolorallocatealpha($dst, 0, 0, 0, 127);
            \imagefilledrectangle($dst, 0, 0, $size, $size, $transparent);
        } else {
            $white = \imagecolorallocate($dst, 255, 255, 255);
            \imagefilledrectangle($dst, 0, 0, $size, $size, $white);
        }

        \imagecopyresampled($dst, $src, 0, 0, $srcX, $srcY, $size, $size, $side, $side);

        // Guardar siempre como PNG para consistencia
        $filename = 'logo_' . Str::random(16) . '.png';
        $full = $path . DIRECTORY_SEPARATOR . $filename;
        \imagepng($dst, $full, 6);

        \imagedestroy($src);
        \imagedestroy($dst);

        return 'logos/' . $filename;
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
