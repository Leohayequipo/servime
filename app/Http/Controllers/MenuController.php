<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Menu;
use App\Models\Comercio;
use App\Models\QuestionCategory;
use App\Services\MenuProcessorService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

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
        $file = $request->file('archivo');
        Log::info('DEBUG - Archivo recibido en store:', [
            'original_name' => $file->getClientOriginalName(),
            'extension' => $file->getClientOriginalExtension(),
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'is_valid' => $file->isValid()
        ]);
        
        $validated = $request->validate([
            'nombre' => 'nullable|string|max:255',
            'archivo' => 'required|file|mimes:pdf,jpeg,png,jpg,gif|max:10240', // 10MB max
        ]);

        // Subir archivo
        $archivoPath = $request->file('archivo')->store('menus', 'public');
        
        // Copiar archivo a public_html en producción
        $this->copyMenuToPublicHtml($archivoPath);
        
        // Crear menú
        $menu = new Menu();
        $menu->comercio_id = $comercio->id;
        $menu->nombre = $request->nombre;
        $menu->archivo_original = $archivoPath;
        
        // Detectar formato automáticamente basándose en MIME type (más confiable)
        $mimeType = $request->file('archivo')->getMimeType();
        $extension = $request->file('archivo')->getClientOriginalExtension();
        
        Log::info('DEBUG - Detectando formato:', [
            'mime_type' => $mimeType,
            'extension' => $extension
        ]);
        
        // Usar MIME type en lugar de extensión para mayor precisión
        $menu->formato = ($mimeType === 'application/pdf') ? 'pdf' : 'image';
        
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
            Log::error('Error al procesar menú: ' . $e->getMessage());
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
            
            $archivoPath = $request->file('archivo')->store('menus', 'public');
            
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
            $comercio->save();
        }

        // Actualizar estado de pedidos
        $nuevoEstadoPedidos = $request->boolean('pedido_habilitado');
        if ($comercio->pedido_habilitado !== $nuevoEstadoPedidos) {
            $comercio->pedido_habilitado = $nuevoEstadoPedidos;
            $comercio->save();
        }

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
}
