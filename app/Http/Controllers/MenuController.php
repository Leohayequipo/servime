<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Menu;
use App\Models\Comercio;
use App\Models\QuestionCategory;
use App\Services\MenuProcessorService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use App\Exports\MenuExport;
use App\Imports\MenuImport;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Excel as ExcelType;
use Illuminate\Support\Facades\Validator;

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
        
        // DEBUG: Ver información del archivo recibido
        if (!$request->hasFile('archivo')) {
            Log::error('❌ ERROR: No se recibió archivo en la petición');
            return redirect()->back()
                ->withInput()
                ->with('error', 'No se recibió ningún archivo. Por favor, intenta nuevamente.');
        }
        
        $file = $request->file('archivo');
        Log::info('DEBUG - Archivo recibido en store:', [
            'original_name' => $file->getClientOriginalName(),
            'extension' => $file->getClientOriginalExtension(),
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'is_valid' => $file->isValid(),
            'has_file' => $request->hasFile('archivo')
        ]);
        
        $validated = $request->validate([
            'nombre' => 'nullable|string|max:255',
            'archivo' => 'required|file|mimes:pdf,jpeg,png,jpg,gif|max:10240', // 10MB max
        ]);

        // Subir archivo preservando la extensión original
        $archivo = $request->file('archivo');
        $extension = $archivo->getClientOriginalExtension();
        $nombreArchivo = uniqid() . '.' . $extension;
        $archivoPath = $archivo->storeAs('menus', $nombreArchivo, 'public');
        
        Log::info('📤 ARCHIVO SUBIDO', [
            'archivo_path' => $archivoPath,
            'nombre_original' => $request->file('archivo')->getClientOriginalName(),
            'extension_original' => $request->file('archivo')->getClientOriginalExtension(),
            'extension_guardada' => $extension,
            'tamaño' => $request->file('archivo')->getSize(),
            'mime_type' => $request->file('archivo')->getMimeType(),
            'comercio_id' => $comercio->id,
            'comercio_nombre' => $comercio->nombre
        ]);
        
        // Copiar archivo a public_html en producción (con manejo de errores)
        try {
            $this->copyMenuToPublicHtml($archivoPath);
        } catch (\Exception $e) {
            // No fallar si hay error al copiar a public_html (solo es para producción)
            Log::warning('Error al copiar archivo a public_html (no crítico)', [
                'error' => $e->getMessage(),
                'archivo_path' => $archivoPath
            ]);
        }
        
        // Crear menú
        $menu = new Menu();
        $menu->comercio_id = $comercio->id;
        $menu->nombre = $request->nombre;
        $menu->archivo_original = $archivoPath;
        
        Log::info('💾 MENÚ CREADO EN BD', [
            'menu_id' => 'NUEVO (aún no guardado)',
            'archivo_original' => $archivoPath,
            'comercio_id' => $comercio->id
        ]);
        
        // Detectar formato automáticamente basándose en MIME type y extensión
        $mimeType = $request->file('archivo')->getMimeType();
        $extension = strtolower($request->file('archivo')->getClientOriginalExtension());
        
        Log::info('DEBUG - Detectando formato:', [
            'mime_type' => $mimeType,
            'extension' => $extension
        ]);
        
        // Usar MIME type O extensión para detectar PDF (algunos PDFs escaneados pueden tener MIME type incorrecto)
        $menu->formato = ($mimeType === 'application/pdf' || $extension === 'pdf') ? 'pdf' : 'image';
        
        $menu->procesado = false;
        
        // Si es el primer menú del comercio, marcarlo como activo
        if ($comercio->menus()->count() === 0) {
            $menu->activo = true;
        }
        
        $menu->save();
        
        // REFRESCAR el modelo desde la BD para asegurar que tenemos los datos más recientes
        $menu->refresh();
        
        Log::info('💾 MENÚ GUARDADO EN BD', [
            'menu_id' => $menu->id,
            'archivo_original' => $menu->archivo_original,
            'formato' => $menu->formato,
            'comercio_id' => $comercio->id,
            'archivo_path_completo' => storage_path('app/public/' . $menu->archivo_original),
            'archivo_existe' => file_exists(storage_path('app/public/' . $menu->archivo_original)),
            'tamaño_archivo' => file_exists(storage_path('app/public/' . $menu->archivo_original)) ? filesize(storage_path('app/public/' . $menu->archivo_original)) : 'N/A'
        ]);

        // Procesar con OpenAI
        try {
            $processor = new MenuProcessorService();
            Log::info('🚀 INICIANDO PROCESAMIENTO CON OpenAI', [
                'menu_id' => $menu->id,
                'archivo_original' => $menu->archivo_original,
                'archivo_path_completo' => storage_path('app/public/' . $menu->archivo_original),
                'archivo_existe' => file_exists(storage_path('app/public/' . $menu->archivo_original))
            ]);
            $processed = $processor->processMenu($menu);
        } catch (\Exception $e) {
            Log::error('❌ Error al procesar menú: ' . $e->getMessage(), [
                'menu_id' => $menu->id ?? 'N/A',
                'archivo_original' => $menu->archivo_original ?? 'N/A',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->route('menus.index', $comercio)
                ->with('error', 'Error al procesar el menú: ' . $e->getMessage());
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
        
        // Asegurar que el archivo del menú esté disponible en public_html (si existe)
        if ($menu->archivo_original) {
            $this->ensureMenuFileInPublicHtml($menu->archivo_original);
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
            'background_color' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'pedido_habilitado' => 'nullable|boolean'
        ]);


        

        $nuevoArchivo = $request->hasFile('archivo');
        
        // Si se sube un nuevo archivo
        if ($nuevoArchivo) {
            // Eliminar archivo anterior de ambos lugares
            $this->deleteMenuSafely($menu->archivo_original);
            
            // Preservar extensión original al actualizar
            $archivo = $request->file('archivo');
            $extension = $archivo->getClientOriginalExtension();
            $nombreArchivo = uniqid() . '.' . $extension;
            $archivoPath = $archivo->storeAs('menus', $nombreArchivo, 'public');
            
            // Copiar archivo a public_html en producción
            $this->copyMenuToPublicHtml($archivoPath);
            
            $menu->archivo_original = $archivoPath;
            
            // Detectar formato automáticamente
            $extension = $request->file('archivo')->getClientOriginalExtension();
            $menu->formato = in_array(strtolower($extension), ['pdf']) ? 'pdf' : 'image';
            $menu->procesado = false; // Marcar como no procesado para reprocesar después
        }

        // Actualizar nombre del menú
        if ($request->has('nombre')) {
            $menu->nombre = $request->nombre;
        }
        
        // Solo actualizar formato si se envía explícitamente y NO hay archivo nuevo
        if (!$nuevoArchivo && $request->has('formato') && $request->formato) {
            $menu->formato = $validated['formato'];
        }
        
        $menu->save();

        // Actualizar campos del chatbot y colores en el comercio
        $needsUpdate = false;
        
        // Log para depurar
        Log::info('MenuController@update - Datos recibidos del chatbot', [
            'chatbot_name' => $request->input('chatbot_name'),
            'welcome_message' => $request->input('welcome_message'),
            'has_chatbot_name' => $request->has('chatbot_name'),
            'has_welcome_message' => $request->has('welcome_message'),
            'all_request_keys' => array_keys($request->all())
        ]);
        
        // Campos del chatbot - actualizar siempre que estén presentes en el request
        if ($request->has('chatbot_name')) {
            $comercio->chatbot_name = $request->chatbot_name ?? null;
            $needsUpdate = true;
            Log::info('Chatbot name actualizado', [
                'valor_anterior' => $comercio->getOriginal('chatbot_name'),
                'valor_nuevo' => $request->chatbot_name
            ]);
        }
        if ($request->has('welcome_message')) {
            $comercio->welcome_message = $request->welcome_message ?? null;
            $needsUpdate = true;
            Log::info('Welcome message actualizado', [
                'valor_anterior' => $comercio->getOriginal('welcome_message'),
                'valor_nuevo' => substr($request->welcome_message ?? '', 0, 50) . '...'
            ]);
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
            $comercio->save();
        }

        // Actualizar estado de pedidos
        $nuevoEstadoPedidos = $request->boolean('pedido_habilitado');
        if ($comercio->pedido_habilitado !== $nuevoEstadoPedidos) {
            $comercio->pedido_habilitado = $nuevoEstadoPedidos;
            $comercio->save();
        }

        // SIEMPRE guardar promociones (incluso si el array está vacío, para eliminar las que fueron borradas)
        $promocionesData = $request->input('promociones', []);
        
        // Siempre llamar a savePromociones para que pueda eliminar las que fueron borradas del DOM
        // Si promocionesData está vacío o no es array, se pasará un array vacío
        $promocionesParaGuardar = is_array($promocionesData) ? $promocionesData : [];
        Log::info('Guardando promociones', ['count' => count($promocionesParaGuardar), 'promociones' => $promocionesParaGuardar]);
        $this->savePromociones($menu, $promocionesParaGuardar);

        // SOLO reprocesar si se subió un nuevo archivo
        // NO reprocesar si solo se actualizaron otros campos (preserva imágenes)
        if (!$nuevoArchivo) {
            return redirect()->route('menus.edit', [$comercio, $menu])
                ->with('success', 'Cambios guardados exitosamente.');
        }

        // Se subió un nuevo archivo: preservar imágenes antes de reprocesar
        $imagenesExistentes = [];
        if ($menu->contexto_gpt) {
            try {
                $jsonContent = $menu->contexto_gpt;
                if (strpos($jsonContent, '```json') !== false) {
                    $jsonContent = preg_replace('/```json\s*/', '', $jsonContent);
                    $jsonContent = preg_replace('/```\s*$/', '', $jsonContent);
                }
                $contenidoActual = json_decode(trim($jsonContent), true);
                if ($contenidoActual && isset($contenidoActual['categorias']) && is_array($contenidoActual['categorias'])) {
                    foreach ($contenidoActual['categorias'] as $categoria) {
                        if (isset($categoria['platos']) && is_array($categoria['platos'])) {
                            foreach ($categoria['platos'] as $plato) {
                                if (isset($plato['imagen'], $plato['nombre']) && !empty($plato['imagen']) && !empty($plato['nombre'])) {
                                    $nombreNormalizado = mb_strtolower(trim($plato['nombre']));
                                    $imagenesExistentes[$nombreNormalizado] = $plato['imagen'];
                                }
                            }
                        }
                    }
                }
            } catch (\Exception $e) {
                \Log::warning('Error al extraer imágenes existentes antes de reprocesar: ' . $e->getMessage());
            }
        }

        // Reprocesar con OpenAI
        try {
            $processor = new MenuProcessorService();
            $processed = $processor->processMenu($menu);

            // Restaurar las imágenes después del reprocesamiento
            if ($processed && !empty($imagenesExistentes) && $menu->contexto_gpt) {
                try {
                    $jsonContent = $menu->contexto_gpt;
                    if (strpos($jsonContent, '```json') !== false) {
                        $jsonContent = preg_replace('/```json\s*/', '', $jsonContent);
                        $jsonContent = preg_replace('/```\s*$/', '', $jsonContent);
                    }
                    $contenidoNuevo = json_decode(trim($jsonContent), true);
                    if ($contenidoNuevo && isset($contenidoNuevo['categorias']) && is_array($contenidoNuevo['categorias'])) {
                        $modified = false;
                        foreach ($contenidoNuevo['categorias'] as &$categoria) {
                            if (isset($categoria['platos']) && is_array($categoria['platos'])) {
                                foreach ($categoria['platos'] as &$plato) {
                                    if (isset($plato['nombre'])) {
                                        $nombreNormalizado = mb_strtolower(trim($plato['nombre']));
                                        if (isset($imagenesExistentes[$nombreNormalizado])) {
                                            $plato['imagen'] = $imagenesExistentes[$nombreNormalizado];
                                            $modified = true;
                                        }
                                    }
                                }
                            }
                        }
                        if ($modified) {
                            $menu->contexto_gpt = json_encode($contenidoNuevo, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
                            $menu->save();
                        }
                    }
                } catch (\Exception $e) {
                    \Log::warning('Error al restaurar imágenes después del reprocesamiento: ' . $e->getMessage());
                }
            }

            if ($processed) {
                return redirect()->route('menus.index', $comercio)
                    ->with('success', 'Menú actualizado y reprocesado exitosamente con IA.');
            } else {
                return redirect()->route('menus.index', $comercio)
                    ->with('warning', 'Menú actualizado pero hubo un problema en el reprocesamiento.');
            }
        } catch (\Exception $e) {
            \Log::error('Error al reprocesar menú: ' . $e->getMessage());
            return redirect()->route('menus.edit', [$comercio, $menu])
                ->with('error', 'Error al reprocesar el menú: ' . $e->getMessage());
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
        
        // Eliminar archivo de ambos lugares
        $this->deleteMenuSafely($menu->archivo_original);
        
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

    /**
     * Subir imagen de un plato específico y actualizar el JSON
     */
    public function uploadPlatoImagen(Request $request, Comercio $comercio, Menu $menu)
    {
        $user = auth()->user();
        
        // Validar permisos
        if (!$user->isSuperAdmin() && $comercio->user_id !== $user->id) {
            return response()->json(['error' => 'No tienes permiso para actualizar este menú.'], 403);
        }
        
        if ($menu->comercio_id !== $comercio->id) {
            return response()->json(['error' => 'Menú no encontrado en este comercio.'], 404);
        }

        $validated = $request->validate([
            'imagen' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048', // 2MB máximo
            'categoria_index' => 'required|integer',
            'plato_index' => 'required|integer',
        ]);

        try {
            // Obtener y decodificar JSON actual
            $rawContent = $menu->contexto_gpt ?? $menu->texto_extraido;
            
            if (empty($rawContent)) {
                return response()->json(['error' => 'No hay contenido procesado en este menú.'], 400);
            }

            // Limpiar el contenido JSON
            $jsonContent = $rawContent;
            if (strpos($jsonContent, '```json') !== false) {
                $jsonContent = preg_replace('/```json\s*/', '', $jsonContent);
                $jsonContent = preg_replace('/```\s*$/', '', $jsonContent);
            }
            
            $contenido = json_decode(trim($jsonContent), true);
            
            if (!$contenido || !isset($contenido['categorias'])) {
                return response()->json(['error' => 'Formato de menú inválido.'], 400);
            }

            // Validar índices
            $categoriaIndex = $validated['categoria_index'];
            $platoIndex = $validated['plato_index'];
            
            if (!isset($contenido['categorias'][$categoriaIndex]) || !isset($contenido['categorias'][$categoriaIndex]['platos'][$platoIndex])) {
                return response()->json(['error' => 'Índices de plato inválidos.'], 400);
            }

            // Guardar imagen
            $imagenPath = $request->file('imagen')->store('menu-items', 'public');
            
            // Preparar referencia a la imagen anterior (si existe)
            $plato = &$contenido['categorias'][$categoriaIndex]['platos'][$platoIndex];
            $oldImageRelativePath = null;
            if (isset($plato['imagen']) && !empty($plato['imagen'])) {
                $oldImageRelativePath = str_replace('/storage/', '', $plato['imagen']);
            }

            // Actualizar JSON con la nueva ruta de imagen
            $plato['imagen'] = '/storage/' . $imagenPath;

            // Guardar JSON actualizado PRIMERO (esto es lo crítico)
            $jsonResult = json_encode($contenido, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            
            if ($jsonResult === false) {
                return response()->json(['error' => 'Error al actualizar el menú: ' . json_last_error_msg()], 500);
            }
            
            $menu->contexto_gpt = $jsonResult;
            $menu->save();

            // Copiar y eliminar después del guardado (no crítico, no debe bloquear)
            // Solo en producción, y con manejo silencioso de errores
            $this->copyMenuItemToPublicHtml($imagenPath);
            
            if ($oldImageRelativePath) {
                $this->deleteMenuItemSafely($oldImageRelativePath);
            }

            return response()->json([
                'success' => true,
                'imagen_url' => asset('storage/' . $imagenPath),
                'imagen_path' => $plato['imagen']
            ]);

        } catch (\Exception $e) {
            \Log::error('Error al subir imagen de plato: ' . $e->getMessage());
            return response()->json([
                'error' => 'Error al procesar la imagen: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar imagen de plato
     */
    public function deletePlatoImagen(Request $request, Comercio $comercio, Menu $menu)
    {
        $user = auth()->user();
        
        // Validar permisos
        if (!$user->isSuperAdmin() && $comercio->user_id !== $user->id) {
            return response()->json(['error' => 'No tienes permiso para actualizar este menú.'], 403);
        }
        
        if ($menu->comercio_id !== $comercio->id) {
            return response()->json(['error' => 'Menú no encontrado en este comercio.'], 404);
        }

        $validated = $request->validate([
            'categoria_index' => 'required|integer',
            'plato_index' => 'required|integer',
        ]);

        try {
            // Obtener y decodificar JSON actual
            $rawContent = $menu->contexto_gpt ?? $menu->texto_extraido;
            
            if (empty($rawContent)) {
                return response()->json(['error' => 'No hay contenido procesado en este menú.'], 400);
            }

            // Limpiar el contenido JSON
            $jsonContent = $rawContent;
            if (strpos($jsonContent, '```json') !== false) {
                $jsonContent = preg_replace('/```json\s*/', '', $jsonContent);
                $jsonContent = preg_replace('/```\s*$/', '', $jsonContent);
            }
            
            $contenido = json_decode(trim($jsonContent), true);
            
            if (!$contenido || !isset($contenido['categorias'])) {
                return response()->json(['error' => 'Formato de menú inválido.'], 400);
            }

            // Validar índices
            $categoriaIndex = $validated['categoria_index'];
            $platoIndex = $validated['plato_index'];
            
            if (!isset($contenido['categorias'][$categoriaIndex]) || !isset($contenido['categorias'][$categoriaIndex]['platos'][$platoIndex])) {
                return response()->json(['error' => 'Índices de plato inválidos.'], 400);
            }

            // Obtener referencia al plato
            $plato = &$contenido['categorias'][$categoriaIndex]['platos'][$platoIndex];
            
            // Guardar ruta de imagen anterior para eliminarla
            $oldImageRelativePath = null;
            if (isset($plato['imagen']) && !empty($plato['imagen'])) {
                $oldImageRelativePath = str_replace('/storage/', '', $plato['imagen']);
                
                // Eliminar imagen anterior en ambos lugares
                $this->deleteMenuItemSafely($oldImageRelativePath);
            }

            // Eliminar referencia a la imagen en el JSON
            unset($plato['imagen']);

            // Guardar JSON actualizado
            $jsonResult = json_encode($contenido, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            
            if ($jsonResult === false) {
                return response()->json(['error' => 'Error al actualizar el menú: ' . json_last_error_msg()], 500);
            }
            
            $menu->contexto_gpt = $jsonResult;
            $menu->save();

            return response()->json([
                'success' => true,
                'message' => 'Imagen eliminada exitosamente'
            ]);

        } catch (\Exception $e) {
            \Log::error('Error al eliminar imagen de plato: ' . $e->getMessage());
            return response()->json([
                'error' => 'Error al eliminar la imagen: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Actualizar precio de plato
     */
    public function updatePlatoPrecio(Request $request, Comercio $comercio, Menu $menu)
    {
        $user = auth()->user();
        
        // Validar permisos
        if (!$user->isSuperAdmin() && $comercio->user_id !== $user->id) {
            return response()->json(['error' => 'No tienes permiso para actualizar este menú.'], 403);
        }
        
        if ($menu->comercio_id !== $comercio->id) {
            return response()->json(['error' => 'Menú no encontrado en este comercio.'], 404);
        }

        $validated = $request->validate([
            'precio' => 'nullable|string|max:50',
            'categoria_index' => 'required|integer',
            'plato_index' => 'required|integer',
        ]);

        try {
            $contenido = $this->getMenuContent($menu);
            
            $categoriaIndex = $validated['categoria_index'];
            $platoIndex = $validated['plato_index'];
            
            if (!isset($contenido['categorias'][$categoriaIndex]['platos'][$platoIndex])) {
                return response()->json(['error' => 'Índices de plato inválidos.'], 400);
            }

            $plato = &$contenido['categorias'][$categoriaIndex]['platos'][$platoIndex];
            $plato['precio'] = $validated['precio'] ?? '';

            $this->saveMenuContent($menu, $contenido);

            return response()->json([
                'success' => true,
                'precio' => $plato['precio'],
                'message' => 'Precio actualizado exitosamente'
            ]);

        } catch (\Exception $e) {
            \Log::error('Error al actualizar precio de plato: ' . $e->getMessage());
            return response()->json([
                'error' => 'Error al actualizar el precio: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Actualizar nombre de plato
     */
    public function updatePlatoNombre(Request $request, Comercio $comercio, Menu $menu)
    {
        $user = auth()->user();
        
        // Validar permisos
        if (!$user->isSuperAdmin() && $comercio->user_id !== $user->id) {
            return response()->json(['error' => 'No tienes permiso para actualizar este menú.'], 403);
        }
        
        if ($menu->comercio_id !== $comercio->id) {
            return response()->json(['error' => 'Menú no encontrado en este comercio.'], 404);
        }

        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'categoria_index' => 'required|integer',
            'plato_index' => 'required|integer',
        ]);

        try {
            $contenido = $this->getMenuContent($menu);
            
            $categoriaIndex = $validated['categoria_index'];
            $platoIndex = $validated['plato_index'];
            
            if (!isset($contenido['categorias'][$categoriaIndex]['platos'][$platoIndex])) {
                return response()->json(['error' => 'Índices de plato inválidos.'], 400);
            }

            $plato = &$contenido['categorias'][$categoriaIndex]['platos'][$platoIndex];
            $plato['nombre'] = trim($validated['nombre']);

            $this->saveMenuContent($menu, $contenido);

            return response()->json([
                'success' => true,
                'nombre' => $plato['nombre'],
                'message' => 'Nombre actualizado exitosamente'
            ]);

        } catch (\Exception $e) {
            \Log::error('Error al actualizar nombre de plato: ' . $e->getMessage());
            return response()->json([
                'error' => 'Error al actualizar el nombre: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Actualizar descripción de plato
     */
    public function updatePlatoDescripcion(Request $request, Comercio $comercio, Menu $menu)
    {
        $user = auth()->user();
        
        if (!$user->isSuperAdmin() && $comercio->user_id !== $user->id) {
            return response()->json(['error' => 'No tienes permiso para actualizar este menú.'], 403);
        }
        
        if ($menu->comercio_id !== $comercio->id) {
            return response()->json(['error' => 'Menú no encontrado en este comercio.'], 404);
        }

        $validated = $request->validate([
            'descripcion' => 'nullable|string',
            'categoria_index' => 'required|integer',
            'plato_index' => 'required|integer',
        ]);

        try {
            $contenido = $this->getMenuContent($menu);
            
            $categoriaIndex = $validated['categoria_index'];
            $platoIndex = $validated['plato_index'];
            
            if (!isset($contenido['categorias'][$categoriaIndex]['platos'][$platoIndex])) {
                return response()->json(['error' => 'Índices de plato inválidos.'], 400);
            }

            $plato = &$contenido['categorias'][$categoriaIndex]['platos'][$platoIndex];
            $plato['descripcion'] = trim($validated['descripcion'] ?? '');

            $this->saveMenuContent($menu, $contenido);

            return response()->json([
                'success' => true,
                'descripcion' => $plato['descripcion'],
                'message' => 'Descripción actualizada exitosamente'
            ]);

        } catch (\Exception $e) {
            \Log::error('Error al actualizar descripción de plato: ' . $e->getMessage());
            return response()->json([
                'error' => 'Error al actualizar la descripción: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Actualizar tags de plato
     */
    public function updatePlatoTags(Request $request, Comercio $comercio, Menu $menu)
    {
        $user = auth()->user();
        
        if (!$user->isSuperAdmin() && $comercio->user_id !== $user->id) {
            return response()->json(['error' => 'No tienes permiso para actualizar este menú.'], 403);
        }
        
        if ($menu->comercio_id !== $comercio->id) {
            return response()->json(['error' => 'Menú no encontrado en este comercio.'], 404);
        }

        $validated = $request->validate([
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:50',
            'categoria_index' => 'required|integer',
            'plato_index' => 'required|integer',
        ]);

        try {
            $contenido = $this->getMenuContent($menu);
            
            $categoriaIndex = $validated['categoria_index'];
            $platoIndex = $validated['plato_index'];
            
            if (!isset($contenido['categorias'][$categoriaIndex]['platos'][$platoIndex])) {
                return response()->json(['error' => 'Índices de plato inválidos.'], 400);
            }

            $plato = &$contenido['categorias'][$categoriaIndex]['platos'][$platoIndex];
            
            // Filtrar tags vacíos y normalizar
            $tags = array_filter(array_map('trim', $validated['tags'] ?? []), function($tag) {
                return !empty($tag);
            });
            
            $plato['tags'] = array_values($tags); // Reindexar array

            $this->saveMenuContent($menu, $contenido);

            return response()->json([
                'success' => true,
                'tags' => $plato['tags'],
                'message' => 'Tags actualizados exitosamente'
            ]);

        } catch (\Exception $e) {
            \Log::error('Error al actualizar tags de plato: ' . $e->getMessage());
            return response()->json([
                'error' => 'Error al actualizar los tags: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reordenar platos dentro de una categoría
     */
    public function reordenarPlatos(Request $request, Comercio $comercio, Menu $menu)
    {
        $user = auth()->user();
        
        if (!$user->isSuperAdmin() && $comercio->user_id !== $user->id) {
            return response()->json(['error' => 'No tienes permiso para actualizar este menú.'], 403);
        }
        
        if ($menu->comercio_id !== $comercio->id) {
            return response()->json(['error' => 'Menú no encontrado en este comercio.'], 404);
        }

        $validated = $request->validate([
            'categoria_index' => 'required|integer',
            'platos_order' => 'required|array',
            'platos_order.*' => 'integer',
        ]);

        try {
            $contenido = $this->getMenuContent($menu);
            
            $categoriaIndex = $validated['categoria_index'];
            
            if (!isset($contenido['categorias'][$categoriaIndex])) {
                return response()->json(['error' => 'Categoría inválida.'], 400);
            }

            $categoria = &$contenido['categorias'][$categoriaIndex];
            $platosOrden = $validated['platos_order'];
            
            // Validar que los índices coincidan con la cantidad de platos
            if (count($platosOrden) !== count($categoria['platos'])) {
                return response()->json(['error' => 'El orden no coincide con la cantidad de platos.'], 400);
            }

            // Reordenar platos según el nuevo orden
            $nuevosPlatos = [];
            foreach ($platosOrden as $indiceOriginal) {
                if (isset($categoria['platos'][$indiceOriginal])) {
                    $nuevosPlatos[] = $categoria['platos'][$indiceOriginal];
                }
            }
            
            $categoria['platos'] = $nuevosPlatos;

            $this->saveMenuContent($menu, $contenido);

            return response()->json([
                'success' => true,
                'message' => 'Platos reordenados exitosamente'
            ]);

        } catch (\Exception $e) {
            \Log::error('Error al reordenar platos: ' . $e->getMessage());
            return response()->json([
                'error' => 'Error al reordenar los platos: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Helper: Obtener y decodificar contenido del menú
     */
    private function getMenuContent(Menu $menu): array
    {
        $rawContent = $menu->contexto_gpt ?? $menu->texto_extraido;
        
        if (empty($rawContent)) {
            throw new \Exception('No hay contenido procesado en este menú.');
        }

        $jsonContent = $rawContent;
        if (strpos($jsonContent, '```json') !== false) {
            $jsonContent = preg_replace('/```json\s*/', '', $jsonContent);
            $jsonContent = preg_replace('/```\s*$/', '', $jsonContent);
        }
        
        $contenido = json_decode(trim($jsonContent), true);
        
        if (!$contenido || !isset($contenido['categorias'])) {
            throw new \Exception('Formato de menú inválido.');
        }

        return $contenido;
    }

    /**
     * Helper: Guardar contenido del menú
     */
    private function saveMenuContent(Menu $menu, array $contenido): void
    {
        $jsonResult = json_encode($contenido, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        
        if ($jsonResult === false) {
            throw new \Exception('Error al actualizar el menú: ' . json_last_error_msg());
        }
        
        $menu->contexto_gpt = $jsonResult;
        $menu->save();
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
     * Copiar imagen de plato a public_html/storage/menu-items automáticamente
     */
    private function copyMenuItemToPublicHtml(string $archivoPath): void
    {
        if (empty($archivoPath)) {
            return;
        }

        // Verificar entorno de producción - salir rápido si no estamos en producción
        if (!file_exists('/home/u560898880/domains')) {
            return;
        }

        $publicHtmlPath = '/home/u560898880/domains/servime.net/public_html/storage/menu-items';
        $sourceFile = storage_path('app/public/' . $archivoPath);
        
        // Verificaciones rápidas sin logs excesivos
        if (!file_exists($sourceFile)) {
            return;
        }

        // Crear directorio si no existe (sin logs)
        if (!is_dir($publicHtmlPath)) {
            @mkdir($publicHtmlPath, 0775, true);
            @chmod($publicHtmlPath, 0775);
        }

        // Verificar permisos rápidamente
        if (!is_writable($publicHtmlPath)) {
            return;
        }

        // Copiar archivo
        $filename = basename($archivoPath);
        $targetFile = $publicHtmlPath . '/' . $filename;

        if (@copy($sourceFile, $targetFile)) {
            @chmod($targetFile, 0664);
        }
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

    /**
     * Eliminar archivo de menú de forma segura (tanto de storage como de public_html)
     */
    private function deleteMenuSafely(?string $archivoPath): void
    {
        if (empty($archivoPath)) {
            return;
        }
        
        // Eliminar de storage/app/public
        if (Storage::disk('public')->exists($archivoPath)) {
            Storage::disk('public')->delete($archivoPath);
        }
        
        // Eliminar también de public_html/storage si existe (producción)
        $homeDir = getenv('HOME') ?: (getenv('USERPROFILE') ?: '/tmp');
        $domainsDir = $homeDir . '/domains';
        
        // Buscar el dominio correcto
        if (is_dir($domainsDir)) {
            $domains = scandir($domainsDir);
            foreach ($domains as $domain) {
                if ($domain === '.' || $domain === '..') continue;
                $filename = basename($archivoPath);
                $publicHtmlFile = $domainsDir . '/' . $domain . '/public_html/storage/menus/' . $filename;
                if (file_exists($publicHtmlFile)) {
                    @unlink($publicHtmlFile);
                    Log::info('Archivo de menú eliminado de public_html', ['file' => $publicHtmlFile]);
                    break; // Ya lo eliminamos, no necesitamos seguir buscando
                }
            }
        }
    }

    /**
     * Eliminar imagen de plato de forma segura (storage + public_html)
     */
    private function deleteMenuItemSafely(?string $archivoPath): void
    {
        if (empty($archivoPath)) {
            return;
        }

        // Eliminar de storage/app/public
        if (Storage::disk('public')->exists($archivoPath)) {
            Storage::disk('public')->delete($archivoPath);
        }

        $homeDir = getenv('HOME') ?: (getenv('USERPROFILE') ?: '/tmp');
        $domainsDir = $homeDir . '/domains';

        if (is_dir($domainsDir)) {
            $domains = scandir($domainsDir);
            foreach ($domains as $domain) {
                if ($domain === '.' || $domain === '..') {
                    continue;
                }
                $filename = basename($archivoPath);
                $publicHtmlFile = $domainsDir . '/' . $domain . '/public_html/storage/menu-items/' . $filename;
                if (file_exists($publicHtmlFile)) {
                    @unlink($publicHtmlFile);
                    Log::info('Imagen de plato eliminada de public_html', ['file' => $publicHtmlFile]);
                    break;
                }
            }
        }
    }

    /**
     * Subir imagen de una promoción específica y actualizar el JSON
     */
    public function uploadPromocionImagen(Request $request, Comercio $comercio, Menu $menu)
    {
        $user = auth()->user();
        
        // Validar permisos
        if (!$user->isSuperAdmin() && $comercio->user_id !== $user->id) {
            return response()->json(['error' => 'No tienes permiso para actualizar este menú.'], 403);
        }
        
        if ($menu->comercio_id !== $comercio->id) {
            return response()->json(['error' => 'Menú no encontrado en este comercio.'], 404);
        }

        $validated = $request->validate([
            'imagen' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048', // 2MB máximo
            'promo_index' => 'required|integer',
        ]);

        try {
            // Obtener y decodificar JSON actual
            $rawContent = $menu->contexto_gpt ?? $menu->texto_extraido;
            
            if (empty($rawContent)) {
                return response()->json(['error' => 'No hay contenido procesado en este menú.'], 400);
            }

            // Limpiar el contenido JSON
            $jsonContent = $rawContent;
            if (strpos($jsonContent, '```json') !== false) {
                $jsonContent = preg_replace('/```json\s*/', '', $jsonContent);
                $jsonContent = preg_replace('/```\s*$/', '', $jsonContent);
            }
            
            $contenido = json_decode(trim($jsonContent), true);
            
            if (!$contenido || !isset($contenido['categorias'])) {
                return response()->json(['error' => 'Formato de menú inválido.'], 400);
            }

            // Buscar la categoría "Promociones del Día"
            $promocionesCategoriaIndex = null;
            foreach ($contenido['categorias'] as $idx => $cat) {
                if (isset($cat['nombre']) && strtolower(trim($cat['nombre'])) === 'promociones del día') {
                    $promocionesCategoriaIndex = $idx;
                    break;
                }
            }

            if ($promocionesCategoriaIndex === null) {
                // Crear la categoría si no existe
                $promocionesCategoriaIndex = count($contenido['categorias']);
                $contenido['categorias'][] = [
                    'nombre' => 'Promociones del Día',
                    'platos' => []
                ];
            }

            // Asegurar que el array de platos existe
            if (!isset($contenido['categorias'][$promocionesCategoriaIndex]['platos'])) {
                $contenido['categorias'][$promocionesCategoriaIndex]['platos'] = [];
            }

            // Validar índice de promoción
            $promoIndex = $validated['promo_index'];
            
            // NO crear placeholder vacío - solo actualizar si ya existe
            // La promoción solo se guardará en el JSON cuando tenga al menos nombre o precio
            if (!isset($contenido['categorias'][$promocionesCategoriaIndex]['platos'][$promoIndex])) {
                // La promoción no existe aún - no crear registro vacío
                // Solo guardar la imagen en storage y devolver la URL
                // El registro se creará cuando se guarden los datos de texto vía savePromociones()
                $imagenPath = $request->file('imagen')->store('menu-items', 'public');
                
                // Copiar a public_html
                $this->copyMenuItemToPublicHtml($imagenPath);
                
                return response()->json([
                    'success' => true,
                    'imagen_url' => asset('storage/' . $imagenPath),
                    'imagen_path' => '/storage/' . $imagenPath,
                    'message' => 'Imagen subida. Completa los datos (nombre o precio) y guarda la promoción para que se guarde en el JSON.'
                ]);
            }

            // Guardar imagen
            $imagenPath = $request->file('imagen')->store('menu-items', 'public');
            
            // Preparar referencia a la imagen anterior (si existe)
            $promo = &$contenido['categorias'][$promocionesCategoriaIndex]['platos'][$promoIndex];
            $oldImageRelativePath = null;
            if (isset($promo['imagen']) && !empty($promo['imagen'])) {
                $oldImageRelativePath = str_replace('/storage/', '', $promo['imagen']);
            }

            // Actualizar JSON con la nueva ruta de imagen
            $promo['imagen'] = '/storage/' . $imagenPath;

            // Guardar JSON actualizado
            $jsonResult = json_encode($contenido, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            
            if ($jsonResult === false) {
                return response()->json(['error' => 'Error al actualizar el menú: ' . json_last_error_msg()], 500);
            }
            
            $menu->contexto_gpt = $jsonResult;
            $menu->save();

            // Copiar y eliminar después del guardado
            $this->copyMenuItemToPublicHtml($imagenPath);
            
            if ($oldImageRelativePath) {
                $this->deleteMenuItemSafely($oldImageRelativePath);
            }

            return response()->json([
                'success' => true,
                'imagen_url' => asset('storage/' . $imagenPath),
                'imagen_path' => $promo['imagen']
            ]);

        } catch (\Exception $e) {
            \Log::error('Error al subir imagen de promoción: ' . $e->getMessage());
            return response()->json([
                'error' => 'Error al procesar la imagen: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar imagen de promoción
     */
    /**
     * Eliminar una promoción del JSON inmediatamente
     */
    public function deletePromocion(Request $request, Comercio $comercio, Menu $menu)
    {
        try {
            $promoIndex = $request->input('promo_index');
            
            if ($promoIndex === null) {
                return response()->json(['success' => false, 'error' => 'Índice de promoción no proporcionado'], 400);
            }
            
            if (!$menu->contexto_gpt) {
                return response()->json(['success' => false, 'error' => 'El menú no tiene contexto GPT'], 400);
            }
            
            // Parsear JSON
            $jsonContent = $menu->contexto_gpt;
            if (strpos($jsonContent, '```json') !== false) {
                $jsonContent = preg_replace('/```json\s*/', '', $jsonContent);
                $jsonContent = preg_replace('/```\s*$/', '', $jsonContent);
            }
            
            $contenido = json_decode(trim($jsonContent), true);
            
            if (!$contenido || !isset($contenido['categorias'])) {
                return response()->json(['success' => false, 'error' => 'JSON inválido'], 400);
            }
            
            // Buscar categoría de promociones
            $promocionesIndex = -1;
            foreach ($contenido['categorias'] as $idx => $cat) {
                if (isset($cat['nombre']) && strtolower(trim($cat['nombre'])) === 'promociones del día') {
                    $promocionesIndex = $idx;
                    break;
                }
            }
            
            if ($promocionesIndex < 0) {
                return response()->json(['success' => false, 'error' => 'No se encontró la categoría de promociones'], 400);
            }
            
            // Eliminar la promoción del índice especificado
            if (isset($contenido['categorias'][$promocionesIndex]['platos'][$promoIndex])) {
                unset($contenido['categorias'][$promocionesIndex]['platos'][$promoIndex]);
                // Reindexar el array
                $contenido['categorias'][$promocionesIndex]['platos'] = array_values($contenido['categorias'][$promocionesIndex]['platos']);
                
                // Si no quedan promociones, eliminar la categoría completa
                if (empty($contenido['categorias'][$promocionesIndex]['platos'])) {
                    unset($contenido['categorias'][$promocionesIndex]);
                    $contenido['categorias'] = array_values($contenido['categorias']);
                }
                
                // Guardar JSON actualizado
                $jsonResult = json_encode($contenido, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
                $menu->contexto_gpt = $jsonResult;
                $menu->save();
                
                Log::info('Promoción eliminada del JSON', [
                    'menu_id' => $menu->id,
                    'promo_index' => $promoIndex,
                    'promociones_restantes' => isset($contenido['categorias'][$promocionesIndex]) ? count($contenido['categorias'][$promocionesIndex]['platos']) : 0
                ]);
                
                return response()->json(['success' => true, 'message' => 'Promoción eliminada exitosamente']);
            } else {
                return response()->json(['success' => false, 'error' => 'Promoción no encontrada'], 404);
            }
            
        } catch (\Exception $e) {
            Log::error('Error al eliminar promoción: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
    
    public function deletePromocionImagen(Request $request, Comercio $comercio, Menu $menu)
    {
        $user = auth()->user();
        
        // Validar permisos
        if (!$user->isSuperAdmin() && $comercio->user_id !== $user->id) {
            return response()->json(['error' => 'No tienes permiso para actualizar este menú.'], 403);
        }
        
        if ($menu->comercio_id !== $comercio->id) {
            return response()->json(['error' => 'Menú no encontrado en este comercio.'], 404);
        }

        $validated = $request->validate([
            'promo_index' => 'required|integer',
        ]);

        try {
            // Obtener y decodificar JSON actual
            $rawContent = $menu->contexto_gpt ?? $menu->texto_extraido;
            
            if (empty($rawContent)) {
                return response()->json(['error' => 'No hay contenido procesado en este menú.'], 400);
            }

            // Limpiar el contenido JSON
            $jsonContent = $rawContent;
            if (strpos($jsonContent, '```json') !== false) {
                $jsonContent = preg_replace('/```json\s*/', '', $jsonContent);
                $jsonContent = preg_replace('/```\s*$/', '', $jsonContent);
            }
            
            $contenido = json_decode(trim($jsonContent), true);
            
            if (!$contenido || !isset($contenido['categorias'])) {
                return response()->json(['error' => 'Formato de menú inválido.'], 400);
            }

            // Buscar la categoría "Promociones del Día"
            $promocionesCategoriaIndex = null;
            foreach ($contenido['categorias'] as $idx => $cat) {
                if (isset($cat['nombre']) && strtolower(trim($cat['nombre'])) === 'promociones del día') {
                    $promocionesCategoriaIndex = $idx;
                    break;
                }
            }

            if ($promocionesCategoriaIndex === null) {
                return response()->json(['error' => 'Categoría de promociones no encontrada.'], 404);
            }

            // Validar índice de promoción
            $promoIndex = $validated['promo_index'];
            
            if (!isset($contenido['categorias'][$promocionesCategoriaIndex]['platos'][$promoIndex])) {
                return response()->json(['error' => 'Índice de promoción inválido.'], 400);
            }

            // Obtener referencia a la promoción
            $promo = &$contenido['categorias'][$promocionesCategoriaIndex]['platos'][$promoIndex];
            
            // Guardar ruta de imagen anterior para eliminarla
            $oldImagePath = null;
            if (isset($promo['imagen']) && !empty($promo['imagen'])) {
                $oldImagePath = str_replace('/storage/', '', $promo['imagen']);
                // Eliminar la imagen del JSON
                unset($promo['imagen']);
            } else {
                return response()->json(['error' => 'Esta promoción no tiene imagen.'], 400);
            }

            // Guardar JSON actualizado
            $jsonResult = json_encode($contenido, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            
            if ($jsonResult === false) {
                return response()->json(['error' => 'Error al actualizar el menú: ' . json_last_error_msg()], 500);
            }
            
            $menu->contexto_gpt = $jsonResult;
            $menu->save();

            // Eliminar imagen del storage
            if ($oldImagePath) {
                $this->deleteMenuItemSafely($oldImagePath);
            }

            return response()->json([
                'success' => true,
                'message' => 'Imagen eliminada exitosamente'
            ]);

        } catch (\Exception $e) {
            \Log::error('Error al eliminar imagen de promoción: ' . $e->getMessage());
            return response()->json([
                'error' => 'Error al eliminar la imagen: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Guardar promociones vía AJAX (endpoint separado)
     */
    public function savePromocionesAjax(Request $request, Comercio $comercio, Menu $menu)
    {
        $user = auth()->user();
        
        // Validar permisos
        if (!$user->isSuperAdmin() && $comercio->user_id !== $user->id) {
            return response()->json(['success' => false, 'error' => 'No tienes permiso para actualizar este menú.'], 403);
        }
        
        if ($menu->comercio_id !== $comercio->id) {
            return response()->json(['success' => false, 'error' => 'Menú no encontrado en este comercio.'], 404);
        }
        
        // Obtener datos del body JSON
        // Cuando se envía con Content-Type: application/json, Laravel no lo procesa automáticamente
        $jsonBody = json_decode($request->getContent(), true);
        $promocionesData = $jsonBody['promociones'] ?? [];
        
        // Fallback: intentar con input() por si acaso viene como form-data
        if (empty($promocionesData)) {
            $promocionesData = $request->input('promociones', []);
        }
        
        // Validar que sea un array
        if (!is_array($promocionesData)) {
            Log::error('savePromocionesAjax: datos inválidos', [
                'promociones_data' => $promocionesData,
                'request_all' => $request->all(),
                'request_content' => $request->getContent()
            ]);
            return response()->json(['success' => false, 'error' => 'Datos de promociones inválidos'], 400);
        }
        
        try {
            Log::info('savePromocionesAjax llamado', [
                'menu_id' => $menu->id,
                'promociones_count' => count($promocionesData),
                'promociones_recibidas' => $promocionesData,
                'request_all' => $request->all(),
                'request_content_type' => $request->header('Content-Type')
            ]);
            
            // Llamar al método privado savePromociones
            $this->savePromociones($menu, $promocionesData);
            
            return response()->json([
                'success' => true,
                'message' => 'Promociones guardadas exitosamente',
                'count' => count($promocionesData)
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error al guardar promociones vía AJAX: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'error' => 'Error al guardar las promociones: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Guardar promociones en el contexto_gpt del menú
     */
    private function savePromociones(Menu $menu, array $promociones)
    {
        try {
            Log::info('savePromociones llamado', [
                'menu_id' => $menu->id,
                'promociones_recibidas' => $promociones,
                'count' => count($promociones)
            ]);
            
            // Solo guardar promociones que tengan al menos nombre o precio (además de imagen)
            // No crear registros vacíos solo con imagen
            $promocionesFiltradas = array_filter($promociones, function($promo) {
                $tieneNombre = isset($promo['nombre']) && trim($promo['nombre']) !== '';
                $tienePrecio = isset($promo['precio']) && trim($promo['precio']) !== '';
                
                // Debe tener al menos nombre O precio para guardarse en el JSON
                return $tieneNombre || $tienePrecio;
            });

            // Reindexar array para eliminar índices vacíos
            $promocionesFiltradas = array_values($promocionesFiltradas);
            
            Log::info('Promociones filtradas (con algún dato)', [
                'count' => count($promocionesFiltradas),
                'promociones' => $promocionesFiltradas
            ]);

            // Leer contexto_gpt actual
            $jsonContent = $menu->contexto_gpt;
            if (!$jsonContent) {
                // Si no hay contexto_gpt, crear estructura básica
                $contenido = [
                    'categorias' => []
                ];
            } else {
                // Limpiar markdown si existe
                $clean = trim($jsonContent);
                if (strpos($clean, '```json') !== false) {
                    $clean = preg_replace('/```json\s*/', '', $clean);
                    $clean = preg_replace('/```\s*$/', '', $clean);
                }
                $contenido = json_decode(trim($clean), true);
                
                if (!$contenido || !is_array($contenido)) {
                    $contenido = ['categorias' => []];
                }
                
                if (!isset($contenido['categorias']) || !is_array($contenido['categorias'])) {
                    $contenido['categorias'] = [];
                }
            }

            // Buscar o crear categoría "Promociones del Día"
            $promocionesIndex = -1;
            foreach ($contenido['categorias'] as $idx => $cat) {
                if (isset($cat['nombre']) && strtolower(trim($cat['nombre'])) === 'promociones del día') {
                    $promocionesIndex = $idx;
                    break;
                }
            }

            // Preparar array de platos para promociones
            // Primero, obtener las imágenes existentes indexadas por nombre (más confiable que por índice)
            $imagenesExistentes = [];
            if ($promocionesIndex >= 0 && isset($contenido['categorias'][$promocionesIndex]['platos'])) {
                $platosExistentes = $contenido['categorias'][$promocionesIndex]['platos'];
                foreach ($platosExistentes as $idx => $platoExistente) {
                    if (isset($platoExistente['imagen']) && !empty($platoExistente['imagen'])) {
                        // Indexar por nombre normalizado para buscar más fácilmente
                        $nombreNormalizado = isset($platoExistente['nombre']) ? mb_strtolower(trim($platoExistente['nombre'])) : '';
                        if ($nombreNormalizado) {
                            $imagenesExistentes[$nombreNormalizado] = $platoExistente['imagen'];
                        }
                        // También mantener por índice para compatibilidad
                        $imagenesExistentes[$idx] = $platoExistente['imagen'];
                    }
                }
            }

            // Preparar array de platos para promociones, preservando imágenes
            $platosPromociones = [];
            $indiceNuevo = 0; // Inicializar índice
            
            Log::info('Procesando promociones recibidas', [
                'total' => count($promocionesFiltradas),
                'promociones' => $promocionesFiltradas
            ]);
            
            // Usar promocionesFiltradas (ya filtradas para tener al menos un dato)
            foreach ($promocionesFiltradas as $idx => $promo) {
                Log::info("Procesando promoción índice $idx", ['promo' => $promo]);
                
                // Asegurar que siempre tengamos los campos básicos (aunque estén vacíos)
                if (!isset($promo['nombre'])) {
                    $promo['nombre'] = '';
                }
                if (!isset($promo['precio'])) {
                    $promo['precio'] = '';
                }
                if (!isset($promo['descripcion'])) {
                    $promo['descripcion'] = '';
                }
                
                $nombreNormalizado = mb_strtolower(trim($promo['nombre']));
                
                $plato = [
                    'nombre' => trim($promo['nombre'] ?? ''),
                    'precio' => trim($promo['precio'] ?? ''),
                    'descripcion' => trim($promo['descripcion'] ?? '')
                ];
                
                // Preservar imagen SOLO si viene explícitamente en el request o coincide por nombre
                // NO usar índice porque al filtrar promociones los índices cambian y se mezclan imágenes
                if (isset($promo['imagen']) && !empty($promo['imagen'])) {
                    // Si viene explícitamente en el request, usar esa
                    $plato['imagen'] = $promo['imagen'];
                    Log::info("Imagen asignada desde request para promoción $idx", ['imagen' => $promo['imagen']]);
                } elseif (!empty($nombreNormalizado) && isset($imagenesExistentes[$nombreNormalizado])) {
                    // Buscar por nombre normalizado SOLO si hay nombre (más confiable)
                    $plato['imagen'] = $imagenesExistentes[$nombreNormalizado];
                    Log::info("Imagen asignada por nombre para promoción $idx", ['nombre' => $nombreNormalizado, 'imagen' => $imagenesExistentes[$nombreNormalizado]]);
                } else {
                    // Si no viene imagen en el request y no coincide por nombre, NO asignar imagen
                    // Esto previene que se asigne incorrectamente una imagen de otra promoción
                    Log::info("Promoción $idx sin imagen - no se asignará ninguna");
                }
                
                Log::info('Agregando promoción', [
                    'indice_nuevo' => $indiceNuevo,
                    'indice_original' => $idx,
                    'plato' => $plato
                ]);
                
                $platosPromociones[] = $plato;
                $indiceNuevo++;
            }
            
            // Si no hay promociones válidas, eliminar la categoría completa
            if (empty($platosPromociones)) {
                if ($promocionesIndex >= 0) {
                    unset($contenido['categorias'][$promocionesIndex]);
                    $contenido['categorias'] = array_values($contenido['categorias']); // Reindexar
                    Log::info('Eliminando categoría de promociones vacía');
                }
            } else {
                // Actualizar o crear categoría de promociones
                if ($promocionesIndex >= 0) {
                    $contenido['categorias'][$promocionesIndex]['platos'] = $platosPromociones;
                } else {
                    $contenido['categorias'][] = [
                        'nombre' => 'Promociones del Día',
                        'platos' => $platosPromociones
                    ];
                }
            }

            // Guardar JSON actualizado (solo si hay cambios)
            $jsonResult = json_encode($contenido, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            
            if ($jsonResult === false) {
                Log::error('Error al codificar JSON de promociones: ' . json_last_error_msg());
                return;
            }

            $menu->contexto_gpt = $jsonResult;
            $menu->save();

            Log::info('Promociones guardadas exitosamente', [
                'menu_id' => $menu->id,
                'cantidad' => count($platosPromociones),
                'json_length' => strlen($jsonResult),
                'platos' => $platosPromociones
            ]);

        } catch (\Exception $e) {
            Log::error('Error al guardar promociones: ' . $e->getMessage());
        }
    }

    /**
     * Exportar menú a Excel
     */
    public function export(Comercio $comercio, Menu $menu)
    {
        $user = auth()->user();
        
        // Validar permisos
        if (!$user->isSuperAdmin() && $comercio->user_id !== $user->id) {
            abort(403, 'No tienes permiso para exportar este menú.');
        }
        
        if ($menu->comercio_id !== $comercio->id) {
            abort(404, 'Menú no encontrado en este comercio.');
        }
        
        $nombreBase = ($menu->nombre ?: 'menu') . '_' . date('Y-m-d');
        $nombreBase = preg_replace('/[^a-zA-Z0-9_-]/', '_', $nombreBase);
        $nombreArchivo = $nombreBase . '.xlsx';
        
        return Excel::download(new MenuExport($menu), $nombreArchivo, ExcelType::XLSX);
    }

    /**
     * Previsualizar importación de Excel
     */
    public function previewImport(Request $request, Comercio $comercio)
    {
        $user = auth()->user();
        
        // Validar permisos
        if (!$user->isSuperAdmin() && $comercio->user_id !== $user->id) {
            abort(403, 'No tienes permiso para importar menús en este comercio.');
        }

        $request->validate([
            'archivo' => 'required|file|mimes:xlsx,xls|max:10240',
            'menu_id' => 'nullable|exists:menus,id',
        ]);

        try {
            $file = $request->file('archivo');
            $import = new MenuImport($comercio->id);
            
            // Leer el archivo sin guardar
            $rows = Excel::toCollection($import, $file)->first();
            
            // Validar estructura
            $structureErrors = MenuImport::validateStructure($rows);
            if (!empty($structureErrors)) {
                $redirectRoute = $request->has('menu_id') 
                    ? route('menus.edit', [$comercio, $request->input('menu_id')])
                    : route('menus.index', $comercio);
                return redirect($redirectRoute)
                    ->with('error', 'Error en la estructura del archivo: ' . implode(', ', $structureErrors));
            }

            // Procesar datos
            $import->collection($rows);
            $data = $import->getData();

            // Guardar datos procesados en sesión en lugar de archivo temporal
            $sessionKey = 'menu_import_data_' . $comercio->id . '_' . time();
            session([$sessionKey => $data]);
            
            // Obtener menús existentes para seleccionar cuál actualizar
            $menusExistentes = $comercio->menus()->orderBy('created_at', 'desc')->get();
            
            // Si viene desde edición de un menú específico, usar ese menú
            $menuIdFromRequest = $request->input('menu_id');
            $menuEditando = null;
            if ($menuIdFromRequest) {
                $menuEditando = Menu::where('id', $menuIdFromRequest)
                    ->where('comercio_id', $comercio->id)
                    ->first();
            }
            
            return view('menus.preview-import', compact('comercio', 'data', 'sessionKey', 'menusExistentes', 'menuEditando'));
            
        } catch (\Exception $e) {
            Log::error('Error al previsualizar importación: ' . $e->getMessage());
            $redirectRoute = $request->has('menu_id') 
                ? route('menus.edit', [$comercio, $request->input('menu_id')])
                : route('menus.index', $comercio);
            return redirect($redirectRoute)
                ->with('error', 'Error al leer el archivo Excel: ' . $e->getMessage());
        }
    }

    /**
     * Importar menú desde Excel
     */
    public function import(Request $request, Comercio $comercio)
    {
        $user = auth()->user();
        
        // Validar permisos
        if (!$user->isSuperAdmin() && $comercio->user_id !== $user->id) {
            abort(403, 'No tienes permiso para importar menús en este comercio.');
        }

        $request->validate([
            'session_key' => 'required|string',
            'nombre' => 'nullable|string|max:255',
            'menu_id' => 'nullable|exists:menus,id',
            'accion' => 'required|in:actualizar,crear',
        ]);

        try {
            $sessionKey = $request->input('session_key');
            $data = session($sessionKey);
            
            if (!$data || !is_array($data)) {
                return redirect()->route('menus.index', $comercio)
                    ->with('error', 'Los datos de importación han expirado. Por favor, vuelve a subir el archivo.');
            }

            // Limpiar la sesión después de obtener los datos
            session()->forget($sessionKey);

            $accion = $request->input('accion');
            
            if ($accion === 'actualizar' && $request->has('menu_id')) {
                // Actualizar menú existente
                $menu = Menu::where('id', $request->input('menu_id'))
                    ->where('comercio_id', $comercio->id)
                    ->firstOrFail();
                
                // Guardar estado activo actual
                $estabaActivo = $menu->activo;
                
                // Actualizar datos
                if ($request->filled('nombre')) {
                    $menu->nombre = $request->input('nombre');
                }
                $menu->formato = 'excel';
                $menu->procesado = true;
                $menu->contexto_gpt = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
                $menu->activo = $estabaActivo; // Mantener estado activo
                $menu->save();
                
                $mensaje = 'Menú actualizado exitosamente desde Excel.';
            } else {
                // Crear nuevo menú
                $menu = new Menu();
                $menu->comercio_id = $comercio->id;
                $menu->nombre = $request->input('nombre') ?: 'Menú Importado ' . date('Y-m-d');
                $menu->formato = 'excel';
                $menu->procesado = true;
                $menu->contexto_gpt = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
                $menu->activo = false; // No activar automáticamente
                $menu->save();
                
                $mensaje = 'Menú importado exitosamente desde Excel. Puedes editarlo y activarlo cuando esté listo.';
            }

            return redirect()->route('menus.index', $comercio)
                ->with('success', $mensaje);
                
        } catch (\Exception $e) {
            Log::error('Error al importar menú: ' . $e->getMessage());
            return redirect()->route('menus.index', $comercio)
                ->with('error', 'Error al importar el menú: ' . $e->getMessage());
        }
    }

    /**
     * Actualizar colores del tema automáticamente
     */
    public function updateThemeColors(Request $request, Comercio $comercio)
    {
        $user = auth()->user();
        
        // Validar permisos
        if (!$user->isSuperAdmin() && $comercio->user_id !== $user->id) {
            return response()->json(['error' => 'No tienes permiso para actualizar los colores.'], 403);
        }

        try {
            $validated = $request->validate([
                'primary_color' => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',
                'secondary_color' => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',
                'accent_color' => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',
                'background_color' => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',
            ]);

            $comercio->primary_color = $validated['primary_color'];
            $comercio->secondary_color = $validated['secondary_color'];
            $comercio->accent_color = $validated['accent_color'];
            $comercio->background_color = $validated['background_color'];
            $comercio->save();

            return response()->json([
                'success' => true,
                'message' => 'Colores actualizados correctamente'
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Error de validación al actualizar colores: ' . json_encode($e->errors()));
            return response()->json([
                'error' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error al actualizar colores: ' . $e->getMessage());
            return response()->json(['error' => 'Error al actualizar los colores: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Actualizar configuración del chatbot y pedidos automáticamente
     */
    public function updateChatbotConfig(Request $request, Comercio $comercio)
    {
        $user = auth()->user();
        
        // Validar permisos
        if (!$user->isSuperAdmin() && $comercio->user_id !== $user->id) {
            return response()->json(['error' => 'No tienes permiso para actualizar la configuración.'], 403);
        }

        try {
            $validated = $request->validate([
                'chatbot_name' => 'nullable|string|max:50',
                'welcome_message' => 'nullable|string|max:500',
                'pedido_habilitado' => 'nullable|boolean',
            ]);

            if ($request->has('chatbot_name')) {
                $comercio->chatbot_name = $validated['chatbot_name'] ?? null;
            }
            if ($request->has('welcome_message')) {
                $comercio->welcome_message = $validated['welcome_message'] ?? null;
            }
            if ($request->has('pedido_habilitado')) {
                $comercio->pedido_habilitado = $request->boolean('pedido_habilitado');
            }
            
            $comercio->save();

            return response()->json([
                'success' => true,
                'message' => 'Configuración actualizada correctamente'
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Error de validación al actualizar configuración: ' . json_encode($e->errors()));
            return response()->json([
                'error' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error al actualizar configuración: ' . $e->getMessage());
            return response()->json(['error' => 'Error al actualizar la configuración: ' . $e->getMessage()], 500);
        }
    }
}
