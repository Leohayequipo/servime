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
            // Super Admin puede crear comercios para cualquier usuario (solo los que no tengan comercio)
            $users = User::where('role', 'user')
                        ->whereDoesntHave('comercios')
                        ->get();
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
    public function edit(Comercio $comercio)
    {
        $user = auth()->user();
        
        // Validar permisos: Super Admin puede ver cualquiera, usuario común solo sus comercios
        if (!$user->isSuperAdmin() && $comercio->user_id !== $user->id) {
            abort(403, 'No tienes permiso para acceder a este comercio.');
        }
        
        // Cargar relación de usuario
        $comercio->load('user');
        
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
    public function update(Request $request, Comercio $comercio)
    {
        $user = auth()->user();
        
        // Validar permisos: Super Admin puede editar cualquiera, usuario común solo sus comercios
        if (!$user->isSuperAdmin() && $comercio->user_id !== $user->id) {
            abort(403, 'No tienes permiso para editar este comercio.');
        }

        
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
            $this->deleteLogoSafely($comercio->logo_url);
            $comercio->logo_url = null;
        }
        // Manejar subida de nuevo logo
        elseif ($request->hasFile('logo')) {
            // Eliminar logo anterior si existe
            $this->deleteLogoSafely($comercio->logo_url);
            $comercio->logo_url = $this->saveSquareLogo($request->file('logo'), 512);
        }

        $comercio->save();

        // Si es una petición AJAX, devolver JSON
        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Comercio actualizado exitosamente'
            ]);
        }

        // Redirigir según el tipo de usuario
        $user = auth()->user();
        if ($user->isSuperAdmin()) {
            // Para Super Admin, usar la ruta de admin sin el prefijo admin.
            return redirect()->route('admin.dashboard')->with('success', 'Comercio actualizado exitosamente');
        } else {
            return redirect()->route('comercios.index')->with('success', 'Comercio actualizado exitosamente');
        }
    }

    /**
     * Generar preguntas frecuentes con IA
     */
    public function generateQuestions(Request $request, Comercio $comercio)
    {
        $user = auth()->user();
        
        // Validar permisos: Super Admin puede ver cualquiera, usuario común solo sus comercios
        if (!$user->isSuperAdmin() && $comercio->user_id !== $user->id) {
            abort(403, 'No tienes permiso para generar preguntas para este comercio.');
        }
        
        // Llamar al método del CustomizationController
        $customizationController = new \App\Http\Controllers\CustomizationController();
        return $customizationController->generateQuestions($request, $comercio->id);
    }

    /**
     * Mejorar respuesta con IA
     */
    public function improveAnswer(Request $request, Comercio $comercio)
    {
        $user = auth()->user();
        
        // Validar permisos: Super Admin puede ver cualquiera, usuario común solo sus comercios
        if (!$user->isSuperAdmin() && $comercio->user_id !== $user->id) {
            abort(403, 'No tienes permiso para mejorar respuestas en este comercio.');
        }
        
        // Llamar al método del CustomizationController
        $customizationController = new \App\Http\Controllers\CustomizationController();
        return $customizationController->improveAnswer($request, $comercio->id);
    }

    /**
     * Vista previa del comercio
     */
    public function preview(Comercio $comercio)
    {
        $user = auth()->user();
        
        // Validar permisos: Super Admin puede ver cualquiera, usuario común solo sus comercios
        if (!$user->isSuperAdmin() && $comercio->user_id !== $user->id) {
            abort(403, 'No tienes permiso para ver la vista previa de este comercio.');
        }
        
        // Llamar al método del CustomizationController
        $customizationController = new \App\Http\Controllers\CustomizationController();
        return $customizationController->preview($comercio->id);
    }

    /**
     * Guardar el logo en un tamaño cuadrado fijo (recorte centrado + resize)
     */
    private function saveSquareLogo(\Illuminate\Http\UploadedFile $file, int $size = 512): string
    {
        $path = storage_path('app/public/logos');
        if (!is_dir($path)) {
            mkdir($path, 0775, true);
            chmod($path, 0775);
        } else {
            // Asegurar permisos de escritura aunque el directorio exista
            chmod($path, 0775);
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
        
        // Asegurar permisos de lectura/escritura del archivo
        if (file_exists($full)) {
            chmod($full, 0664);
            
            // Copiar automáticamente a public_html/storage si estamos en producción
            $this->copyLogoToPublicHtml($full, $filename);
        }

        \imagedestroy($src);
        \imagedestroy($dst);

        return 'logos/' . $filename;
    }

    /**
     * Copiar logo a public_html/storage automáticamente
     */
    private function copyLogoToPublicHtml(string $sourceFile, string $filename): void
    {
        try {
            $homeDir = getenv('HOME') ?: (getenv('USERPROFILE') ?: '/tmp');
            $domainsDir = $homeDir . '/domains';
            
            // Buscar el dominio correcto (puede ser servime.net aunque APP_URL diga servime.com)
            $publicHtmlPath = null;
            if (is_dir($domainsDir)) {
                $domains = @scandir($domainsDir);
                if ($domains !== false) {
                    foreach ($domains as $domain) {
                        if ($domain === '.' || $domain === '..') continue;
                        $potentialPath = $domainsDir . '/' . $domain . '/public_html/storage/logos';
                        if (@is_dir(dirname($potentialPath))) {
                            $publicHtmlPath = $potentialPath;
                            break; // Usar el primer dominio encontrado
                        }
                    }
                }
            }
            
            // Solo intentar copiar si encontramos un directorio válido
            if ($publicHtmlPath && @is_dir(dirname($publicHtmlPath))) {
                // Crear directorio de logos en public_html si no existe
                if (!@is_dir($publicHtmlPath)) {
                    if (@mkdir($publicHtmlPath, 0775, true)) {
                        @chmod($publicHtmlPath, 0775);
                        \Log::info('Directorio de logos creado en public_html', ['path' => $publicHtmlPath]);
                    }
                }
                
                // Copiar el archivo
                $targetFile = $publicHtmlPath . '/' . $filename;
                $copied = @copy($sourceFile, $targetFile);
                
                if ($copied && @file_exists($targetFile)) {
                    @chmod($targetFile, 0664);
                    \Log::info('Logo copiado a public_html exitosamente', [
                        'file' => $filename,
                        'target' => $targetFile,
                        'size' => @filesize($targetFile)
                    ]);
                } else {
                    // Segundo intento sin @ para capturar el error
                    $error = @error_get_last();
                    \Log::warning('Primer intento de copia falló, intentando de nuevo', [
                        'source' => $sourceFile,
                        'target' => $targetFile,
                        'error' => $error
                    ]);
                    
                    // Limpiar error anterior
                    @error_clear_last();
                    
                    // Intentar de nuevo
                    if (@copy($sourceFile, $targetFile)) {
                        @chmod($targetFile, 0664);
                        \Log::info('Logo copiado en segundo intento exitosamente');
                    } else {
                        $finalError = @error_get_last();
                        \Log::error('Error al copiar logo a public_html', [
                            'source' => $sourceFile,
                            'target' => $targetFile,
                            'error' => $finalError,
                            'source_exists' => @file_exists($sourceFile),
                            'target_dir_exists' => @is_dir(dirname($targetFile)),
                            'target_dir_writable' => @is_writable(dirname($targetFile))
                        ]);
                    }
                }
            } else {
                \Log::debug('No se encontró directorio public_html para copiar logo', [
                    'domainsDir' => $domainsDir,
                    'domainsDir_exists' => @is_dir($domainsDir),
                    'publicHtmlPath' => $publicHtmlPath
                ]);
            }
        } catch (\Exception $e) {
            \Log::error('Excepción al copiar logo a public_html', [
                'message' => $e->getMessage(),
                'file' => $filename
            ]);
        }
    }

    /**
     * Eliminar logo de forma segura (tanto de storage como de public_html)
     */
    private function deleteLogoSafely(?string $logoUrl): void
    {
        if (empty($logoUrl)) {
            return;
        }
        
        // Eliminar de storage/app/public
        if (\Storage::disk('public')->exists($logoUrl)) {
            \Storage::disk('public')->delete($logoUrl);
        }
        
        // Eliminar también de public_html/storage si existe (producción)
        $homeDir = getenv('HOME') ?: (getenv('USERPROFILE') ?: '/tmp');
        $domainsDir = $homeDir . '/domains';
        
        // Buscar el dominio correcto
        if (is_dir($domainsDir)) {
            $domains = scandir($domainsDir);
            foreach ($domains as $domain) {
                if ($domain === '.' || $domain === '..') continue;
                $publicHtmlFile = $domainsDir . '/' . $domain . '/public_html/storage/' . $logoUrl;
                if (file_exists($publicHtmlFile)) {
                    @unlink($publicHtmlFile);
                    break; // Ya lo eliminamos, no necesitamos seguir buscando
                }
            }
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Comercio $comercio)
    {
        $user = auth()->user();
        
        // Validar permisos: Super Admin puede eliminar cualquiera, usuario común solo sus comercios
        if (!$user->isSuperAdmin() && $comercio->user_id !== $user->id) {
            abort(403, 'No tienes permiso para eliminar este comercio.');
        }
        
        // Eliminar logo del storage si existe
        $this->deleteLogoSafely($comercio->logo_url);
        
        // Eliminar el comercio (esto también eliminará los menús asociados por la relación)
        $comercio->delete();
        
        return redirect()->route('comercios.index')->with('success', 'Comercio eliminado exitosamente');
    }
}
