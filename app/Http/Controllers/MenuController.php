<?php

namespace App\Http\Controllers;

use App\Exports\MenuExport;
use App\Imports\MenuImport;
use App\Models\Comercio;
use App\Models\Menu;
use App\Services\ChatbotService;
use App\Services\ImagenMenuService;
use App\Services\MenuProcessorService;
use App\Support\Chip;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Excel as ExcelType;
use Maatwebsite\Excel\Facades\Excel;

class MenuController extends Controller
{
    /**
     * Cuántos platos estrella puede marcar un comercio.
     *
     * Con tope porque marcarlos todos equivale a no marcar ninguno: "recomendá primero
     * estos 151 platos" no le dice nada al modelo y suma 812 tokens al prompt de CADA
     * consulta. El límite obliga a elegir, que es de lo que se trata.
     */
    public const MAX_ESTRELLAS = 6;

    /**
     * Display a listing of the resource.
     */
    public function index(Comercio $comercio)
    {
        $user = auth()->user();

        // Validar permisos: Super Admin puede ver cualquiera, usuario común solo sus comercios
        if (! $user->isSuperAdmin() && $comercio->user_id !== $user->id) {
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
        if (! $user->isSuperAdmin() && $comercio->user_id !== $user->id) {
            abort(403, 'No tienes permiso para crear menús en este comercio.');
        }

        // Cargar relación de usuario
        $comercio->load('user');

        $menu = null; // No hay menú para crear
        $isEdit = false;

        return view('menus.create', compact('comercio', 'menu', 'isEdit'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, Comercio $comercio)
    {

        $user = auth()->user();

        // Validar permisos: Super Admin puede crear en cualquiera, usuario común solo en sus comercios
        if (! $user->isSuperAdmin() && $comercio->user_id !== $user->id) {
            abort(403, 'No tienes permiso para crear menús en este comercio.');
        }

        // DEBUG: Ver información del archivo recibido
        if (! $request->hasFile('archivo')) {
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
            'has_file' => $request->hasFile('archivo'),
        ]);

        $validated = $request->validate([
            'nombre' => 'nullable|string|max:255',
            'archivo' => 'required|file|mimes:pdf,jpeg,png,jpg,gif|max:10240', // 10MB max
        ]);

        // Subir archivo preservando la extensión original
        $archivo = $request->file('archivo');
        // Asegurar que existan las carpetas del comercio
        $this->createComercioDirectories($comercio->id);

        $extension = $archivo->getClientOriginalExtension();
        $nombreArchivo = uniqid().'.'.$extension;
        $archivoPath = $archivo->storeAs("comercios/{$comercio->id}/menus", $nombreArchivo, 'public');

        Log::info('📤 ARCHIVO SUBIDO', [
            'archivo_path' => $archivoPath,
            'nombre_original' => $request->file('archivo')->getClientOriginalName(),
            'extension_original' => $request->file('archivo')->getClientOriginalExtension(),
            'extension_guardada' => $extension,
            'tamaño' => $request->file('archivo')->getSize(),
            'mime_type' => $request->file('archivo')->getMimeType(),
            'comercio_id' => $comercio->id,
            'comercio_nombre' => $comercio->nombre,
        ]);

        // Copiar archivo a public_html en producción (con manejo de errores)
        try {
            $this->copyMenuToPublicHtml($archivoPath, $comercio->id);
        } catch (\Exception $e) {
            // No fallar si hay error al copiar a public_html (solo es para producción)
            Log::warning('Error al copiar archivo a public_html (no crítico)', [
                'error' => $e->getMessage(),
                'archivo_path' => $archivoPath,
            ]);
        }

        // Crear menú
        $menu = new Menu;
        $menu->comercio_id = $comercio->id;
        $menu->nombre = $request->nombre;
        $menu->archivo_original = $archivoPath;

        Log::info('💾 MENÚ CREADO EN BD', [
            'menu_id' => 'NUEVO (aún no guardado)',
            'archivo_original' => $archivoPath,
            'comercio_id' => $comercio->id,
        ]);

        // Detectar formato automáticamente basándose en MIME type y extensión
        $mimeType = $request->file('archivo')->getMimeType();
        $extension = strtolower($request->file('archivo')->getClientOriginalExtension());

        Log::info('DEBUG - Detectando formato:', [
            'mime_type' => $mimeType,
            'extension' => $extension,
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
            'archivo_path_completo' => storage_path('app/public/'.$menu->archivo_original),
            'archivo_existe' => file_exists(storage_path('app/public/'.$menu->archivo_original)),
            'tamaño_archivo' => file_exists(storage_path('app/public/'.$menu->archivo_original)) ? filesize(storage_path('app/public/'.$menu->archivo_original)) : 'N/A',
        ]);

        // Marcar como "procesando" antes de encolar (cubre subida nueva y re-subida)
        $menu->update(['estado' => Menu::ESTADO_PROCESANDO, 'procesado' => false]);

        // Despachar procesamiento a la queue para no bloquear el request
        \App\Jobs\ProcessMenuJob::dispatch($menu);

        Log::info('📋 Procesamiento encolado', ['menu_id' => $menu->id]);

        return redirect()->route('menus.index', $comercio)
            ->with('success', 'Menú subido correctamente. La IA lo está procesando en segundo plano — en unos minutos estará listo.');
    }

    /**
     * Display the specified resource.
     * Redirige a la página de edición ya que la funcionalidad está integrada ahí.
     */
    public function show(Comercio $comercio, Menu $menu)
    {
        $user = auth()->user();

        // Validar permisos y que el menú pertenezca al comercio
        if (! $user->isSuperAdmin() && $comercio->user_id !== $user->id) {
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
        if (! $user->isSuperAdmin() && $comercio->user_id !== $user->id) {
            abort(403, 'No tienes permiso para editar este menú.');
        }

        if ($menu->comercio_id !== $comercio->id) {
            abort(404, 'Menú no encontrado en este comercio.');
        }

        // Asegurar que el archivo del menú esté disponible en public_html (si existe)
        if ($menu->archivo_original) {
            $this->ensureMenuFileInPublicHtml($menu->archivo_original, $comercio->id);
        }

        $isEdit = true;

        return view('menus.create', compact('comercio', 'menu', 'isEdit'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Comercio $comercio, Menu $menu)
    {
        $user = auth()->user();

        // Validar permisos y que el menú pertenezca al comercio
        if (! $user->isSuperAdmin() && $comercio->user_id !== $user->id) {
            abort(403, 'No tienes permiso para actualizar este menú.');
        }

        if ($menu->comercio_id !== $comercio->id) {
            abort(404, 'Menú no encontrado en este comercio.');
        }

        // Preparar reglas de validación
        $rules = [
            'nombre' => 'nullable|string|max:255',
            'archivo' => 'nullable|file|mimes:pdf,jpeg,png,jpg,gif|max:10240',
            // Formato solo se valida si se envía y no está vacío
            'formato' => 'nullable|string|in:pdf,image',
            // Campos del chatbot
            'chatbot_name' => 'nullable|string|max:50',
            'welcome_message' => 'nullable|string|max:500',
            // Campos de colores
            'primary_color' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'secondary_color' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'accent_color' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'background_color' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'pedido_habilitado' => 'nullable|boolean',
        ];

        // Si formato está vacío o no es válido, convertirlo a null
        if ($request->has('formato') && (empty($request->input('formato')) || ! in_array($request->input('formato'), ['pdf', 'image']))) {
            $request->merge(['formato' => null]);
        }

        $validated = $request->validate($rules);

        $nuevoArchivo = $request->hasFile('archivo');
        $necesitaReprocesar = false;

        // Si se sube un nuevo archivo
        if ($nuevoArchivo) {
            // Eliminar archivo anterior de ambos lugares
            $this->deleteMenuSafely($menu->archivo_original, $comercio->id);

            // Asegurar que existan las carpetas del comercio
            $this->createComercioDirectories($comercio->id);

            // Preservar extensión original al actualizar
            $archivo = $request->file('archivo');
            $extension = $archivo->getClientOriginalExtension();
            $nombreArchivo = uniqid().'.'.$extension;
            $archivoPath = $archivo->storeAs("comercios/{$comercio->id}/menus", $nombreArchivo, 'public');

            // Copiar archivo a public_html en producción
            $this->copyMenuToPublicHtml($archivoPath, $comercio->id);

            $menu->archivo_original = $archivoPath;

            // Detectar formato automáticamente
            $extension = $request->file('archivo')->getClientOriginalExtension();
            $menu->formato = in_array(strtolower($extension), ['pdf']) ? 'pdf' : 'image';
            $menu->procesado = false; // Marcar como no procesado para reprocesar después
            $necesitaReprocesar = true; // Se subió archivo nuevo, necesita reprocesar
        } else {
            // Si no hay archivo nuevo, verificar si el menú está marcado como no procesado
            $necesitaReprocesar = ! $menu->procesado;
        }

        // Actualizar nombre del menú
        if ($request->has('nombre')) {
            $menu->nombre = $request->nombre;
        }

        // Solo actualizar formato si se envía explícitamente y NO hay archivo nuevo
        if (! $nuevoArchivo && $request->has('formato') && $request->formato) {
            $menu->formato = $validated['formato'];
        }

        // IMPORTANTE: Guardar el menú ANTES de verificar si necesita reprocesar
        // para que el estado de 'procesado' se actualice en la BD
        $menu->save();

        // Refrescar el menú desde la BD para obtener el estado actualizado
        $menu->refresh();

        // Actualizar campos del chatbot y colores en el comercio
        $needsUpdate = false;

        // Log para depurar
        Log::info('MenuController@update - Datos recibidos del chatbot', [
            'chatbot_name' => $request->input('chatbot_name'),
            'welcome_message' => $request->input('welcome_message'),
            'has_chatbot_name' => $request->has('chatbot_name'),
            'has_welcome_message' => $request->has('welcome_message'),
            'all_request_keys' => array_keys($request->all()),
        ]);

        // Campos del chatbot - actualizar siempre que estén presentes en el request
        if ($request->has('chatbot_name')) {
            $comercio->chatbot_name = $request->chatbot_name ?? null;
            $needsUpdate = true;
            Log::info('Chatbot name actualizado', [
                'valor_anterior' => $comercio->getOriginal('chatbot_name'),
                'valor_nuevo' => $request->chatbot_name,
            ]);
        }
        if ($request->has('welcome_message')) {
            $comercio->welcome_message = $request->welcome_message ?? null;
            $needsUpdate = true;
            Log::info('Welcome message actualizado', [
                'valor_anterior' => $comercio->getOriginal('welcome_message'),
                'valor_nuevo' => substr($request->welcome_message ?? '', 0, 50).'...',
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

        // SOLO reprocesar si se subió un nuevo archivo O si el menú está marcado como no procesado
        // NO reprocesar si solo se actualizaron otros campos (preserva imágenes)

        if (! $necesitaReprocesar) {
            // Determinar la ruta de retorno según si viene de admin o comercio
            $editRoute = request()->is('admin/*')
                ? route('admin.menus.edit', $menu)
                : route('menus.edit', [$comercio, $menu]);

            return redirect($editRoute)
                ->with('success', 'Cambios guardados exitosamente.');
        }

        // Se subió un nuevo archivo o el menú está marcado para reprocesar: preservar imágenes antes de reprocesar
        // Indexar por ID (método más confiable) y también por nombre para compatibilidad
        $imagenesExistentes = [];
        $idsExistentes = []; // Para preservar IDs existentes
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
                                // Si tiene ID, usarlo; si no, generar uno (migración)
                                $platoId = isset($plato['id']) ? $plato['id'] : null;
                                if (! $platoId) {
                                    // Generar ID único si no existe (migración para items antiguos)
                                    $platoId = time() * 1000 + count($idsExistentes);
                                }
                                $idsExistentes[] = $platoId;

                                // Indexar imagen por ID (método principal)
                                if (isset($plato['imagen']) && ! empty($plato['imagen'])) {
                                    $imagenesExistentes[$platoId] = $plato['imagen'];

                                    // También indexar por nombre para compatibilidad con items antiguos
                                    if (isset($plato['nombre']) && ! empty($plato['nombre'])) {
                                        $nombreNormalizado = mb_strtolower(trim($plato['nombre']));
                                        $imagenesExistentes[$nombreNormalizado] = $plato['imagen'];
                                    }
                                }
                            }
                        }
                    }
                }
            } catch (\Exception $e) {
                \Log::warning('Error al extraer imágenes existentes antes de reprocesar: '.$e->getMessage());
            }
        }

        // Reprocesar con OpenAI
        try {
            $processor = new MenuProcessorService;
            $processed = $processor->processMenu($menu);

            // Restaurar las imágenes y asignar IDs después del reprocesamiento
            if ($processed && $menu->contexto_gpt) {
                try {
                    $jsonContent = $menu->contexto_gpt;
                    if (strpos($jsonContent, '```json') !== false) {
                        $jsonContent = preg_replace('/```json\s*/', '', $jsonContent);
                        $jsonContent = preg_replace('/```\s*$/', '', $jsonContent);
                    }
                    $contenidoNuevo = json_decode(trim($jsonContent), true);
                    if ($contenidoNuevo && isset($contenidoNuevo['categorias']) && is_array($contenidoNuevo['categorias'])) {
                        $modified = false;
                        $proximoId = ! empty($idsExistentes) ? max($idsExistentes) + 1 : (time() * 1000);

                        foreach ($contenidoNuevo['categorias'] as &$categoria) {
                            if (isset($categoria['platos']) && is_array($categoria['platos'])) {
                                foreach ($categoria['platos'] as &$plato) {
                                    // Asignar ID si no existe
                                    if (! isset($plato['id'])) {
                                        $plato['id'] = $proximoId++;
                                        $modified = true;
                                    }

                                    $platoId = $plato['id'];

                                    // Restaurar imagen: buscar por ID primero, luego por nombre (compatibilidad)
                                    if (! isset($plato['imagen']) || empty($plato['imagen'])) {
                                        if (isset($imagenesExistentes[$platoId]) && ! empty($imagenesExistentes[$platoId])) {
                                            // Buscar por ID (método más confiable)
                                            $plato['imagen'] = $imagenesExistentes[$platoId];
                                            $modified = true;
                                        } elseif (isset($plato['nombre']) && ! empty($plato['nombre'])) {
                                            // Buscar por nombre (compatibilidad con items antiguos)
                                            $nombreNormalizado = mb_strtolower(trim($plato['nombre']));
                                            if (isset($imagenesExistentes[$nombreNormalizado]) && ! empty($imagenesExistentes[$nombreNormalizado])) {
                                                $plato['imagen'] = $imagenesExistentes[$nombreNormalizado];
                                                $modified = true;
                                            }
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
                    \Log::warning('Error al restaurar imágenes después del reprocesamiento: '.$e->getMessage());
                }
            }

            // Determinar la ruta de retorno según si viene de admin o comercio
            $indexRoute = request()->is('admin/*')
                ? route('admin.menus.index')
                : route('menus.index', $comercio);

            $editRoute = request()->is('admin/*')
                ? route('admin.menus.edit', $menu)
                : route('menus.edit', [$comercio, $menu]);

            if ($processed) {
                return redirect($indexRoute)
                    ->with('success', 'Menú actualizado y reprocesado exitosamente con IA.');
            } else {
                return redirect($indexRoute)
                    ->with('warning', 'Menú actualizado pero hubo un problema en el reprocesamiento.');
            }
        } catch (\Exception $e) {
            \Log::error('Error al reprocesar menú: '.$e->getMessage());

            return redirect($editRoute)
                ->with('error', 'Error al reprocesar el menú: '.$e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Comercio $comercio, Menu $menu)
    {
        $user = auth()->user();

        // Validar permisos y que el menú pertenezca al comercio
        if (! $user->isSuperAdmin() && $comercio->user_id !== $user->id) {
            abort(403, 'No tienes permiso para eliminar este menú.');
        }

        if ($menu->comercio_id !== $comercio->id) {
            abort(404, 'Menú no encontrado en este comercio.');
        }

        // BAJA LÓGICA: Soft delete (no eliminar archivos ni imágenes)
        // Las imágenes se mantienen para poder reutilizarlas al exportar/importar
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
        if (! $user->isSuperAdmin() && $comercio->user_id !== $user->id) {
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
        if (! $user->isSuperAdmin() && $comercio->user_id !== $user->id) {
            return response()->json(['error' => 'No tienes permiso para actualizar este menú.'], 403);
        }

        if ($menu->comercio_id !== $comercio->id) {
            return response()->json(['error' => 'Menú no encontrado en este comercio.'], 404);
        }

        $validated = $request->validate([
            // El tope sale del service (10 MB): como la imagen se achica al guardarla, el
            // límite de entrada puede ser alto. Con 2 MB una foto de celular no entraba.
            'imagen' => ImagenMenuService::reglaDeValidacion(),
            'plato_id' => 'nullable|integer', // ID único (método preferido)
            'categoria_index' => 'nullable|integer', // Mantener para compatibilidad
            'plato_index' => 'nullable|integer', // Mantener para compatibilidad
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

            if (! $contenido || ! isset($contenido['categorias'])) {
                return response()->json(['error' => 'Formato de menú inválido.'], 400);
            }

            // Buscar plato por ID (método preferido) o por índices (compatibilidad)
            $platoId = $validated['plato_id'] ?? null;
            $categoriaIndex = $validated['categoria_index'] ?? null;
            $platoIndex = $validated['plato_index'] ?? null;
            $plato = null;
            $categoriaArrayIndex = null;
            $platoArrayIndex = null;

            // Buscar por ID primero
            if ($platoId) {
                foreach ($contenido['categorias'] as $catIdx => $categoria) {
                    if (isset($categoria['platos']) && is_array($categoria['platos'])) {
                        foreach ($categoria['platos'] as $platIdx => $platoItem) {
                            if (isset($platoItem['id']) && $platoItem['id'] == $platoId) {
                                $plato = &$contenido['categorias'][$catIdx]['platos'][$platIdx];
                                $categoriaArrayIndex = $catIdx;
                                $platoArrayIndex = $platIdx;
                                break 2;
                            }
                        }
                    }
                }
            }

            // Si no se encontró por ID, buscar por índices (compatibilidad)
            if (! $plato && $categoriaIndex !== null && $platoIndex !== null) {
                if (isset($contenido['categorias'][$categoriaIndex]['platos'][$platoIndex])) {
                    $plato = &$contenido['categorias'][$categoriaIndex]['platos'][$platoIndex];
                    $categoriaArrayIndex = $categoriaIndex;
                    $platoArrayIndex = $platoIndex;
                }
            }

            if (! $plato) {
                return response()->json(['error' => 'Plato no encontrado.'], 404);
            }

            // Asignar ID si no existe
            if (! isset($plato['id'])) {
                $plato['id'] = $this->proximoIdDePlato($contenido);
            }

            // Asegurar que existan las carpetas del comercio
            $this->createComercioDirectories($comercio->id);

            // Se achica a 1200px y se convierte a webp antes de guardar: la tarjeta del menú
            // público se muestra a 448px y se estaban guardando fotos de celular de 1 MB.
            $imagenPath = app(ImagenMenuService::class)
                ->procesar($request->file('imagen'), "comercios/{$comercio->id}/menu-items");

            // Preparar referencia a la imagen anterior (si existe)
            $oldImageRelativePath = null;
            if (isset($plato['imagen']) && ! empty($plato['imagen'])) {
                $oldImageRelativePath = str_replace('/storage/', '', $plato['imagen']);
            }

            // Actualizar JSON con la nueva ruta de imagen
            $plato['imagen'] = '/storage/'.$imagenPath;

            // Guardar JSON actualizado PRIMERO (esto es lo crítico)
            $jsonResult = json_encode($contenido, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

            if ($jsonResult === false) {
                return response()->json(['error' => 'Error al actualizar el menú: '.json_last_error_msg()], 500);
            }

            $menu->contexto_gpt = $jsonResult;
            $menu->save();

            // Copiar y eliminar después del guardado (no crítico, no debe bloquear)
            // Solo en producción, y con manejo silencioso de errores
            $this->copyMenuItemToPublicHtml($imagenPath, $comercio->id);

            if ($oldImageRelativePath) {
                $this->deleteMenuItemSafely($oldImageRelativePath, $comercio->id);
            }

            return response()->json([
                'success' => true,
                'imagen_url' => asset('storage/'.$imagenPath),
                'imagen_path' => $plato['imagen'],
            ]);

        } catch (\Exception $e) {
            \Log::error('Error al subir imagen de plato: '.$e->getMessage());

            return response()->json([
                'error' => 'Error al procesar la imagen: '.$e->getMessage(),
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
        if (! $user->isSuperAdmin() && $comercio->user_id !== $user->id) {
            return response()->json(['error' => 'No tienes permiso para actualizar este menú.'], 403);
        }

        if ($menu->comercio_id !== $comercio->id) {
            return response()->json(['error' => 'Menú no encontrado en este comercio.'], 404);
        }

        $validated = $request->validate([
            'plato_id' => 'nullable|integer', // ID único (método preferido)
            'categoria_index' => 'nullable|integer', // Mantener para compatibilidad
            'plato_index' => 'nullable|integer', // Mantener para compatibilidad
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

            if (! $contenido || ! isset($contenido['categorias'])) {
                return response()->json(['error' => 'Formato de menú inválido.'], 400);
            }

            // Buscar plato por ID (método preferido) o por índices (compatibilidad)
            $platoId = $validated['plato_id'] ?? null;
            $categoriaIndex = $validated['categoria_index'] ?? null;
            $platoIndex = $validated['plato_index'] ?? null;
            $plato = null;

            // Buscar por ID primero
            if ($platoId) {
                foreach ($contenido['categorias'] as $catIdx => $categoria) {
                    if (isset($categoria['platos']) && is_array($categoria['platos'])) {
                        foreach ($categoria['platos'] as $platIdx => $platoItem) {
                            if (isset($platoItem['id']) && $platoItem['id'] == $platoId) {
                                $plato = &$contenido['categorias'][$catIdx]['platos'][$platIdx];
                                break 2;
                            }
                        }
                    }
                }
            }

            // Si no se encontró por ID, buscar por índices (compatibilidad)
            if (! $plato && $categoriaIndex !== null && $platoIndex !== null) {
                if (isset($contenido['categorias'][$categoriaIndex]['platos'][$platoIndex])) {
                    $plato = &$contenido['categorias'][$categoriaIndex]['platos'][$platoIndex];
                }
            }

            if (! $plato) {
                return response()->json(['error' => 'Plato no encontrado.'], 404);
            }

            // Guardar ruta de imagen anterior para eliminarla
            $oldImageRelativePath = null;
            if (isset($plato['imagen']) && ! empty($plato['imagen'])) {
                $oldImageRelativePath = str_replace('/storage/', '', $plato['imagen']);

                // Eliminar imagen anterior en ambos lugares
                $this->deleteMenuItemSafely($oldImageRelativePath);
            }

            // Eliminar referencia a la imagen en el JSON
            unset($plato['imagen']);

            // Guardar JSON actualizado
            $jsonResult = json_encode($contenido, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

            if ($jsonResult === false) {
                return response()->json(['error' => 'Error al actualizar el menú: '.json_last_error_msg()], 500);
            }

            $menu->contexto_gpt = $jsonResult;
            $menu->save();

            return response()->json([
                'success' => true,
                'message' => 'Imagen eliminada exitosamente',
            ]);

        } catch (\Exception $e) {
            \Log::error('Error al eliminar imagen de plato: '.$e->getMessage());

            return response()->json([
                'error' => 'Error al eliminar la imagen: '.$e->getMessage(),
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
        if (! $user->isSuperAdmin() && $comercio->user_id !== $user->id) {
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

            if (! isset($contenido['categorias'][$categoriaIndex]['platos'][$platoIndex])) {
                return response()->json(['error' => 'Índices de plato inválidos.'], 400);
            }

            $plato = &$contenido['categorias'][$categoriaIndex]['platos'][$platoIndex];
            // Formatea solo si vino un número pelado ("4500" -> "$4.500"). Si el comercio
            // escribió algo con separadores ya decidió el formato y no se le toca: entran
            // menús de cualquier país y el punto puede ser miles o decimales.
            $plato['precio'] = \App\Support\Precio::formatear($validated['precio'] ?? '');

            $this->saveMenuContent($menu, $contenido);

            return response()->json([
                'success' => true,
                'precio' => $plato['precio'],
                'message' => 'Precio actualizado exitosamente',
            ]);

        } catch (\Exception $e) {
            \Log::error('Error al actualizar precio de plato: '.$e->getMessage());

            return response()->json([
                'error' => 'Error al actualizar el precio: '.$e->getMessage(),
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
        if (! $user->isSuperAdmin() && $comercio->user_id !== $user->id) {
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

            if (! isset($contenido['categorias'][$categoriaIndex]['platos'][$platoIndex])) {
                return response()->json(['error' => 'Índices de plato inválidos.'], 400);
            }

            $plato = &$contenido['categorias'][$categoriaIndex]['platos'][$platoIndex];
            $plato['nombre'] = trim($validated['nombre']);

            $this->saveMenuContent($menu, $contenido);

            return response()->json([
                'success' => true,
                'nombre' => $plato['nombre'],
                'message' => 'Nombre actualizado exitosamente',
            ]);

        } catch (\Exception $e) {
            \Log::error('Error al actualizar nombre de plato: '.$e->getMessage());

            return response()->json([
                'error' => 'Error al actualizar el nombre: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Actualizar descripción de plato
     */
    public function updatePlatoDescripcion(Request $request, Comercio $comercio, Menu $menu)
    {
        $user = auth()->user();

        if (! $user->isSuperAdmin() && $comercio->user_id !== $user->id) {
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

            if (! isset($contenido['categorias'][$categoriaIndex]['platos'][$platoIndex])) {
                return response()->json(['error' => 'Índices de plato inválidos.'], 400);
            }

            $plato = &$contenido['categorias'][$categoriaIndex]['platos'][$platoIndex];
            $plato['descripcion'] = trim($validated['descripcion'] ?? '');

            $this->saveMenuContent($menu, $contenido);

            return response()->json([
                'success' => true,
                'descripcion' => $plato['descripcion'],
                'message' => 'Descripción actualizada exitosamente',
            ]);

        } catch (\Exception $e) {
            \Log::error('Error al actualizar descripción de plato: '.$e->getMessage());

            return response()->json([
                'error' => 'Error al actualizar la descripción: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Actualizar tags de plato
     */
    public function updatePlatoTags(Request $request, Comercio $comercio, Menu $menu)
    {
        $user = auth()->user();

        if (! $user->isSuperAdmin() && $comercio->user_id !== $user->id) {
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

            if (! isset($contenido['categorias'][$categoriaIndex]['platos'][$platoIndex])) {
                return response()->json(['error' => 'Índices de plato inválidos.'], 400);
            }

            $plato = &$contenido['categorias'][$categoriaIndex]['platos'][$platoIndex];

            // Filtrar tags vacíos y normalizar
            $tags = array_filter(array_map('trim', $validated['tags'] ?? []), function ($tag) {
                return ! empty($tag);
            });

            $plato['tags'] = array_values($tags); // Reindexar array

            $this->saveMenuContent($menu, $contenido);

            return response()->json([
                'success' => true,
                'tags' => $plato['tags'],
                'message' => 'Tags actualizados exitosamente',
            ]);

        } catch (\Exception $e) {
            \Log::error('Error al actualizar tags de plato: '.$e->getMessage());

            return response()->json([
                'error' => 'Error al actualizar los tags: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Marca o desmarca un plato como "estrella" (el asistente lo recomienda primero).
     *
     * La estrella vive en el PLATO, dentro del JSON del menú, y no en una lista de nombres
     * aparte en `chatbot_config`: así viaja con él. Antes, renombrar un plato dejaba la
     * estrella apuntando a un nombre que ya no existía y el prompt le pedía al modelo
     * recomendar algo que no estaba en la carta; borrarlo dejaba un fantasma para siempre.
     */
    public function updatePlatoEstrella(Request $request, Comercio $comercio, Menu $menu)
    {
        $user = auth()->user();

        if (! $user->isSuperAdmin() && $comercio->user_id !== $user->id) {
            return response()->json(['error' => 'No tienes permiso para actualizar este menú.'], 403);
        }

        if ($menu->comercio_id !== $comercio->id) {
            return response()->json(['error' => 'Menú no encontrado en este comercio.'], 404);
        }

        $validated = $request->validate([
            'estrella' => 'required|boolean',
            'categoria_index' => 'required|integer',
            'plato_index' => 'required|integer',
        ]);

        try {
            $contenido = $this->getMenuContent($menu);
            $categoriaIndex = $validated['categoria_index'];
            $platoIndex = $validated['plato_index'];

            if (! isset($contenido['categorias'][$categoriaIndex]['platos'][$platoIndex])) {
                return response()->json(['error' => 'Índices de plato inválidos.'], 400);
            }

            $marcar = $request->boolean('estrella');

            if ($marcar && $this->contarEstrellas($contenido) >= self::MAX_ESTRELLAS) {
                return response()->json([
                    'error' => 'Ya tenés '.self::MAX_ESTRELLAS.' platos estrella. Sacá uno para marcar otro.',
                ], 422);
            }

            $plato = &$contenido['categorias'][$categoriaIndex]['platos'][$platoIndex];

            if ($marcar) {
                $plato['estrella'] = true;
            } else {
                // Se saca la clave en vez de guardar `false`: así los menús sin estrellas
                // quedan igual que antes y el JSON no se llena de ruido.
                unset($plato['estrella']);
            }

            $this->saveMenuContent($menu, $contenido);

            return response()->json([
                'success' => true,
                'estrella' => $marcar,
                'total' => $this->contarEstrellas($contenido),
                'maximo' => self::MAX_ESTRELLAS,
            ]);

        } catch (\Exception $e) {
            \Log::error('Error al marcar plato estrella: '.$e->getMessage());

            return response()->json(['error' => 'Error al marcar el plato: '.$e->getMessage()], 500);
        }
    }

    /**
     * Siguiente id libre de plato dentro del menú.
     *
     * @param  array<string, mixed>  $contenido
     */
    private function proximoIdDePlato(array $contenido): int
    {
        $proximoId = 1;

        foreach ($contenido['categorias'] ?? [] as $categoria) {
            foreach ($categoria['platos'] ?? [] as $plato) {
                if (isset($plato['id']) && $plato['id'] >= $proximoId) {
                    $proximoId = $plato['id'] + 1;
                }
            }
        }

        return $proximoId;
    }

    /** @param  array<string, mixed>  $contenido */
    private function contarEstrellas(array $contenido): int
    {
        $total = 0;
        foreach ($contenido['categorias'] ?? [] as $categoria) {
            foreach ($categoria['platos'] ?? [] as $plato) {
                if (! empty($plato['estrella'])) {
                    $total++;
                }
            }
        }

        return $total;
    }

    /**
     * Agrega un producto nuevo al final de una categoría.
     *
     * El plato entra con las mismas claves que los que extrae la IA (nombre, descripción,
     * precio, tags) para que el menú público, el asistente y el resto de los endpoints de
     * edición no tengan que distinguir de dónde salió.
     */
    public function agregarPlato(Request $request, Comercio $comercio, Menu $menu)
    {
        $user = auth()->user();

        if (! $user->isSuperAdmin() && $comercio->user_id !== $user->id) {
            return response()->json(['error' => 'No tienes permiso para actualizar este menú.'], 403);
        }

        if ($menu->comercio_id !== $comercio->id) {
            return response()->json(['error' => 'Menú no encontrado en este comercio.'], 404);
        }

        $validated = $request->validate([
            'categoria_index' => 'required|integer',
            'nombre' => 'required|string|max:255',
            'precio' => 'nullable|string|max:50',
            'descripcion' => 'nullable|string|max:1000',
        ]);

        try {
            $contenido = $this->getMenuContent($menu);

            $categoriaIndex = $validated['categoria_index'];

            if (! isset($contenido['categorias'][$categoriaIndex])) {
                return response()->json(['error' => 'Categoría inválida.'], 400);
            }

            $categoria = &$contenido['categorias'][$categoriaIndex];

            if (! isset($categoria['platos']) || ! is_array($categoria['platos'])) {
                $categoria['platos'] = [];
            }

            $categoria['platos'][] = [
                // El id no es decorativo: con él se le devuelve la foto al plato cuando se
                // reprocesa el menú y se lo reconoce al reimportar el Excel.
                'id' => $this->proximoIdDePlato($contenido),
                'nombre' => trim($validated['nombre']),
                'descripcion' => trim($validated['descripcion'] ?? ''),
                // Mismo criterio que al editar el precio: formatea solo el número pelado.
                'precio' => \App\Support\Precio::formatear($validated['precio'] ?? ''),
                'tags' => [],
            ];

            $platoIndex = count($categoria['platos']) - 1;

            $this->saveMenuContent($menu, $contenido);

            return response()->json([
                'success' => true,
                'plato_index' => $platoIndex,
                'message' => 'Producto agregado exitosamente',
            ]);

        } catch (\Exception $e) {
            \Log::error('Error al agregar plato: '.$e->getMessage());

            return response()->json([
                'error' => 'Error al agregar el producto: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Elimina un producto de una categoría.
     */
    public function deletePlato(Request $request, Comercio $comercio, Menu $menu)
    {
        $user = auth()->user();

        if (! $user->isSuperAdmin() && $comercio->user_id !== $user->id) {
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
            $contenido = $this->getMenuContent($menu);

            $categoriaIndex = $validated['categoria_index'];
            $platoIndex = $validated['plato_index'];

            if (! isset($contenido['categorias'][$categoriaIndex]['platos'][$platoIndex])) {
                return response()->json(['error' => 'Índices de plato inválidos.'], 400);
            }

            $plato = $contenido['categorias'][$categoriaIndex]['platos'][$platoIndex];

            // La imagen se va con el plato: si no, queda un archivo que ya nadie referencia
            // ocupando el storage del comercio para siempre.
            if (! empty($plato['imagen'])) {
                $this->deleteMenuItemSafely(str_replace('/storage/', '', $plato['imagen']));
            }

            unset($contenido['categorias'][$categoriaIndex]['platos'][$platoIndex]);

            // Reindexar: el índice ES la identidad del plato para todos los endpoints de
            // edición, así que un array con huecos dejaría al resto apuntando a otro plato.
            $contenido['categorias'][$categoriaIndex]['platos'] = array_values(
                $contenido['categorias'][$categoriaIndex]['platos']
            );

            $this->saveMenuContent($menu, $contenido);

            return response()->json([
                'success' => true,
                'message' => 'Producto eliminado exitosamente',
            ]);

        } catch (\Exception $e) {
            \Log::error('Error al eliminar plato: '.$e->getMessage());

            return response()->json([
                'error' => 'Error al eliminar el producto: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Reordenar platos dentro de una categoría
     */
    public function reordenarPlatos(Request $request, Comercio $comercio, Menu $menu)
    {
        $user = auth()->user();

        if (! $user->isSuperAdmin() && $comercio->user_id !== $user->id) {
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

            if (! isset($contenido['categorias'][$categoriaIndex])) {
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
                'message' => 'Platos reordenados exitosamente',
            ]);

        } catch (\Exception $e) {
            \Log::error('Error al reordenar platos: '.$e->getMessage());

            return response()->json([
                'error' => 'Error al reordenar los platos: '.$e->getMessage(),
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

        if (! $contenido || ! isset($contenido['categorias'])) {
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
            throw new \Exception('Error al actualizar el menú: '.json_last_error_msg());
        }

        $menu->contexto_gpt = $jsonResult;
        $menu->save();
    }

    /**
     * Crear estructura de carpetas para un comercio (menu-items, menus, logos)
     * Esto asegura que las carpetas existan tanto en storage como en public_html
     */
    private function createComercioDirectories(int $comercioId): void
    {
        try {
            // Crear en storage/app/public
            $basePath = "comercios/{$comercioId}";
            $directories = [
                "{$basePath}/menu-items",
                "{$basePath}/menus",
                "{$basePath}/logos",
            ];

            foreach ($directories as $dir) {
                $fullPath = storage_path("app/public/{$dir}");
                if (! is_dir($fullPath)) {
                    @mkdir($fullPath, 0775, true);
                    @chmod($fullPath, 0775);
                }
            }

            // Crear en public_html si estamos en producción
            if (file_exists('/home/u560898880/domains')) {
                $publicHtmlBase = "/home/u560898880/domains/servime.net/public_html/storage/comercios/{$comercioId}";
                $publicHtmlDirs = [
                    "{$publicHtmlBase}/menu-items",
                    "{$publicHtmlBase}/menus",
                    "{$publicHtmlBase}/logos",
                ];

                foreach ($publicHtmlDirs as $dir) {
                    if (! is_dir($dir)) {
                        @mkdir($dir, 0775, true);
                        @chmod($dir, 0775);
                    }
                }
            }
        } catch (\Exception $e) {
            // No fallar si hay error, solo loguear
            Log::warning('Error al crear directorios del comercio', [
                'comercio_id' => $comercioId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Asegurar que el archivo del menú esté en public_html/storage (sin errores si falla)
     */
    private function ensureMenuFileInPublicHtml(string $archivoPath, int $comercioId): void
    {
        // Solo verificar/copiar si estamos en producción
        if (! file_exists('/home/u560898880/domains')) {
            return;
        }

        $filename = basename($archivoPath);
        $targetFile = "/home/u560898880/domains/servime.net/public_html/storage/comercios/{$comercioId}/menus/".$filename;

        // Si ya existe en public_html, no hacer nada
        if (file_exists($targetFile)) {
            return;
        }

        // Si no existe, copiarlo automáticamente
        $this->copyMenuToPublicHtml($archivoPath, $comercioId);
    }

    /**
     * Copiar imagen de plato/promoción a public_html/storage/comercios/{id}/menu-items automáticamente
     */
    private function copyMenuItemToPublicHtml(string $archivoPath, int $comercioId): void
    {
        if (empty($archivoPath)) {
            return;
        }

        // Verificar entorno de producción - salir rápido si no estamos en producción
        if (! file_exists('/home/u560898880/domains')) {
            return;
        }

        // Nueva estructura: comercios/{id}/menu-items
        $publicHtmlPath = "/home/u560898880/domains/servime.net/public_html/storage/comercios/{$comercioId}/menu-items";
        $sourceFile = storage_path('app/public/'.$archivoPath);

        // Verificaciones rápidas sin logs excesivos
        if (! file_exists($sourceFile)) {
            return;
        }

        // Crear directorio si no existe (sin logs)
        if (! is_dir($publicHtmlPath)) {
            @mkdir($publicHtmlPath, 0775, true);
            @chmod($publicHtmlPath, 0775);
        }

        // Verificar permisos rápidamente
        if (! is_writable($publicHtmlPath)) {
            return;
        }

        // Copiar archivo
        $filename = basename($archivoPath);
        $targetFile = $publicHtmlPath.'/'.$filename;

        if (@copy($sourceFile, $targetFile)) {
            @chmod($targetFile, 0664);
        }
    }

    /**
     * Copiar archivo de menú a public_html/storage/comercios/{id}/menus automáticamente
     */
    private function copyMenuToPublicHtml(string $archivoPath, int $comercioId): void
    {
        // Nueva estructura: comercios/{id}/menus
        $publicHtmlPath = "/home/u560898880/domains/servime.net/public_html/storage/comercios/{$comercioId}/menus";

        // Verificar si estamos en producción (Hostinger)
        if (! file_exists('/home/u560898880/domains')) {
            // No estamos en producción, salir silenciosamente
            return;
        }

        try {
            // Ruta del archivo fuente
            $sourceFile = storage_path('app/public/'.$archivoPath);

            if (! file_exists($sourceFile)) {
                Log::error('Archivo de menú no encontrado para copiar', ['path' => $sourceFile]);

                return;
            }

            // Crear directorio si no existe
            if (! is_dir($publicHtmlPath)) {
                if (! mkdir($publicHtmlPath, 0775, true)) {
                    Log::error('No se pudo crear directorio de menús en public_html', ['path' => $publicHtmlPath]);

                    return;
                }
                chmod($publicHtmlPath, 0775);
            }

            // Extraer nombre del archivo
            $filename = basename($archivoPath);
            $targetFile = $publicHtmlPath.'/'.$filename;

            // Intentar copiar
            if (! copy($sourceFile, $targetFile)) {
                $error = error_get_last();
                Log::error('Error al copiar archivo de menú', [
                    'source' => $sourceFile,
                    'target' => $targetFile,
                    'error' => $error,
                    'source_exists' => file_exists($sourceFile),
                    'target_dir_writable' => is_writable($publicHtmlPath),
                ]);

                return;
            }

            // Asegurar permisos
            chmod($targetFile, 0664);

            Log::info('Archivo de menú copiado exitosamente a public_html', [
                'file' => $filename,
                'target' => $targetFile,
                'size' => filesize($targetFile),
            ]);

        } catch (\Exception $e) {
            Log::error('Excepción al copiar archivo de menú', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
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

        // Extraer comercio_id de la ruta (formato: comercios/{id}/menus/filename)
        $comercioId = null;
        if (preg_match('/comercios\/(\d+)\/menus\//', $archivoPath, $matches)) {
            $comercioId = $matches[1];
        }

        // Eliminar también de public_html/storage si existe (producción)
        if ($comercioId && file_exists('/home/u560898880/domains')) {
            $filename = basename($archivoPath);
            $publicHtmlFile = "/home/u560898880/domains/servime.net/public_html/storage/comercios/{$comercioId}/menus/{$filename}";
            if (file_exists($publicHtmlFile)) {
                @unlink($publicHtmlFile);
                Log::info('Archivo de menú eliminado de public_html', ['file' => $publicHtmlFile]);
            }
        }
    }

    /**
     * Eliminar imagen de plato/promoción de forma segura (storage + public_html)
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

        // Extraer comercio_id de la ruta (formato: comercios/{id}/menu-items/filename)
        $comercioId = null;
        if (preg_match('/comercios\/(\d+)\/menu-items\//', $archivoPath, $matches)) {
            $comercioId = $matches[1];
        }

        // Eliminar también de public_html/storage si existe (producción)
        if ($comercioId && file_exists('/home/u560898880/domains')) {
            $filename = basename($archivoPath);
            $publicHtmlFile = "/home/u560898880/domains/servime.net/public_html/storage/comercios/{$comercioId}/menu-items/{$filename}";
            if (file_exists($publicHtmlFile)) {
                @unlink($publicHtmlFile);
                Log::info('Imagen de plato/promoción eliminada de public_html', ['file' => $publicHtmlFile]);
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
        if (! $user->isSuperAdmin() && $comercio->user_id !== $user->id) {
            return response()->json(['error' => 'No tienes permiso para actualizar este menú.'], 403);
        }

        if ($menu->comercio_id !== $comercio->id) {
            return response()->json(['error' => 'Menú no encontrado en este comercio.'], 404);
        }

        $validated = $request->validate([
            // El tope sale del service (10 MB): como la imagen se achica al guardarla, el
            // límite de entrada puede ser alto. Con 2 MB una foto de celular no entraba.
            'imagen' => ImagenMenuService::reglaDeValidacion(),
            'promo_id' => 'required|integer', // Cambiar a ID en lugar de índice
            'promo_index' => 'nullable|integer', // Mantener para compatibilidad
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

            if (! $contenido || ! isset($contenido['categorias'])) {
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
                    'platos' => [],
                ];
            }

            // Asegurar que el array de platos existe
            if (! isset($contenido['categorias'][$promocionesCategoriaIndex]['platos'])) {
                $contenido['categorias'][$promocionesCategoriaIndex]['platos'] = [];
            }

            // Buscar promoción por ID (método preferido) o por índice (compatibilidad)
            $promoId = $validated['promo_id'] ?? null;
            $promoIndex = $validated['promo_index'] ?? null;
            $promo = null;
            $promoArrayIndex = null;

            // Buscar por ID primero
            if ($promoId && isset($contenido['categorias'][$promocionesCategoriaIndex]['platos'])) {
                foreach ($contenido['categorias'][$promocionesCategoriaIndex]['platos'] as $idx => $plato) {
                    if (isset($plato['id']) && $plato['id'] == $promoId) {
                        $promo = &$contenido['categorias'][$promocionesCategoriaIndex]['platos'][$idx];
                        $promoArrayIndex = $idx;
                        break;
                    }
                }
            }

            // Si no se encontró por ID, buscar por índice (compatibilidad)
            if (! $promo && $promoIndex !== null && isset($contenido['categorias'][$promocionesCategoriaIndex]['platos'][$promoIndex])) {
                $promo = &$contenido['categorias'][$promocionesCategoriaIndex]['platos'][$promoIndex];
                $promoArrayIndex = $promoIndex;
            }

            // NO crear placeholder vacío - solo actualizar si ya existe
            // La promoción solo se guardará en el JSON cuando tenga al menos nombre o precio
            if (! $promo) {
                // La promoción no existe aún - no crear registro vacío
                // Solo guardar la imagen en storage y devolver la URL
                // El registro se creará cuando se guarden los datos de texto vía savePromociones()
                // Asegurar que existan las carpetas del comercio
                $this->createComercioDirectories($comercio->id);
                $imagenPath = app(ImagenMenuService::class)
                    ->procesar($request->file('imagen'), "comercios/{$comercio->id}/menu-items");

                // Copiar a public_html
                $this->copyMenuItemToPublicHtml($imagenPath, $comercio->id);

                return response()->json([
                    'success' => true,
                    'imagen_url' => asset('storage/'.$imagenPath),
                    'imagen_path' => '/storage/'.$imagenPath,
                    'message' => 'Imagen subida. Completa los datos (nombre o precio) y guarda la promoción para que se guarde en el JSON.',
                ]);
            }

            // Asegurar que existan las carpetas del comercio
            $this->createComercioDirectories($comercio->id);

            // Se achica a 1200px y se convierte a webp antes de guardar: la tarjeta del menú
            // público se muestra a 448px y se estaban guardando fotos de celular de 1 MB.
            $imagenPath = app(ImagenMenuService::class)
                ->procesar($request->file('imagen'), "comercios/{$comercio->id}/menu-items");

            // Preparar referencia a la imagen anterior (si existe)
            $oldImageRelativePath = null;
            if (isset($promo['imagen']) && ! empty($promo['imagen'])) {
                $oldImageRelativePath = str_replace('/storage/', '', $promo['imagen']);
            }

            // Actualizar JSON con la nueva ruta de imagen
            $promo['imagen'] = '/storage/'.$imagenPath;

            // Guardar JSON actualizado
            $jsonResult = json_encode($contenido, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

            if ($jsonResult === false) {
                return response()->json(['error' => 'Error al actualizar el menú: '.json_last_error_msg()], 500);
            }

            $menu->contexto_gpt = $jsonResult;
            $menu->save();

            // Copiar y eliminar después del guardado
            $this->copyMenuItemToPublicHtml($imagenPath, $comercio->id);

            if ($oldImageRelativePath) {
                $this->deleteMenuItemSafely($oldImageRelativePath);
            }

            return response()->json([
                'success' => true,
                'imagen_url' => asset('storage/'.$imagenPath),
                'imagen_path' => $promo['imagen'],
            ]);

        } catch (\Exception $e) {
            \Log::error('Error al subir imagen de promoción: '.$e->getMessage());

            return response()->json([
                'error' => 'Error al procesar la imagen: '.$e->getMessage(),
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
            $promoId = $request->input('promo_id');
            $promoIndex = $request->input('promo_index'); // Mantener para compatibilidad

            if ($promoId === null && $promoIndex === null) {
                return response()->json(['success' => false, 'error' => 'ID o índice de promoción no proporcionado'], 400);
            }

            if (! $menu->contexto_gpt) {
                return response()->json(['success' => false, 'error' => 'El menú no tiene contexto GPT'], 400);
            }

            // Parsear JSON
            $jsonContent = $menu->contexto_gpt;
            if (strpos($jsonContent, '```json') !== false) {
                $jsonContent = preg_replace('/```json\s*/', '', $jsonContent);
                $jsonContent = preg_replace('/```\s*$/', '', $jsonContent);
            }

            $contenido = json_decode(trim($jsonContent), true);

            if (! $contenido || ! isset($contenido['categorias'])) {
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

            // Buscar y eliminar la promoción por ID o índice
            $promoEncontrada = false;
            if (isset($contenido['categorias'][$promocionesIndex]['platos'])) {
                foreach ($contenido['categorias'][$promocionesIndex]['platos'] as $idx => $plato) {
                    // Buscar por ID primero
                    if ($promoId && isset($plato['id']) && $plato['id'] == $promoId) {
                        unset($contenido['categorias'][$promocionesIndex]['platos'][$idx]);
                        $promoEncontrada = true;
                        break;
                    }
                    // Buscar por índice como fallback
                    if ($promoIndex !== null && $idx == $promoIndex) {
                        unset($contenido['categorias'][$promocionesIndex]['platos'][$idx]);
                        $promoEncontrada = true;
                        break;
                    }
                }

                if ($promoEncontrada) {
                    // Reindexar el array
                    $contenido['categorias'][$promocionesIndex]['platos'] = array_values($contenido['categorias'][$promocionesIndex]['platos']);

                    // Si no quedan promociones, eliminar la categoría completa
                    if (empty($contenido['categorias'][$promocionesIndex]['platos'])) {
                        unset($contenido['categorias'][$promocionesIndex]);
                        $contenido['categorias'] = array_values($contenido['categorias']);
                    }
                }

                // Guardar JSON actualizado
                $jsonResult = json_encode($contenido, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
                $menu->contexto_gpt = $jsonResult;
                $menu->save();

                Log::info('Promoción eliminada del JSON', [
                    'menu_id' => $menu->id,
                    'promo_index' => $promoIndex,
                    'promociones_restantes' => isset($contenido['categorias'][$promocionesIndex]) ? count($contenido['categorias'][$promocionesIndex]['platos']) : 0,
                ]);

                return response()->json(['success' => true, 'message' => 'Promoción eliminada exitosamente']);
            } else {
                return response()->json(['success' => false, 'error' => 'Promoción no encontrada'], 404);
            }

        } catch (\Exception $e) {
            Log::error('Error al eliminar promoción: '.$e->getMessage());

            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function deletePromocionImagen(Request $request, Comercio $comercio, Menu $menu)
    {
        $user = auth()->user();

        // Validar permisos
        if (! $user->isSuperAdmin() && $comercio->user_id !== $user->id) {
            return response()->json(['error' => 'No tienes permiso para actualizar este menú.'], 403);
        }

        if ($menu->comercio_id !== $comercio->id) {
            return response()->json(['error' => 'Menú no encontrado en este comercio.'], 404);
        }

        $validated = $request->validate([
            'promo_id' => 'required|integer', // Cambiar a ID en lugar de índice
            'promo_index' => 'nullable|integer', // Mantener para compatibilidad
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

            if (! $contenido || ! isset($contenido['categorias'])) {
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

            // Buscar promoción por ID (método preferido) o por índice (compatibilidad)
            $promoId = $validated['promo_id'];
            $promoIndex = $validated['promo_index'] ?? null;
            $promo = null;
            $promoArrayIndex = null;

            // Buscar por ID primero
            if (isset($contenido['categorias'][$promocionesCategoriaIndex]['platos'])) {
                foreach ($contenido['categorias'][$promocionesCategoriaIndex]['platos'] as $idx => $plato) {
                    if (isset($plato['id']) && $plato['id'] == $promoId) {
                        $promo = &$contenido['categorias'][$promocionesCategoriaIndex]['platos'][$idx];
                        $promoArrayIndex = $idx;
                        break;
                    }
                }
            }

            // Si no se encontró por ID, buscar por índice (compatibilidad)
            if (! $promo && $promoIndex !== null && isset($contenido['categorias'][$promocionesCategoriaIndex]['platos'][$promoIndex])) {
                $promo = &$contenido['categorias'][$promocionesCategoriaIndex]['platos'][$promoIndex];
                $promoArrayIndex = $promoIndex;

                // IMPORTANTE: Verificar que el ID coincide si se encontró por índice
                if (isset($promo['id']) && $promo['id'] != $promoId) {
                    Log::warning('⚠️ ADVERTENCIA: Promoción encontrada por índice pero el ID no coincide', [
                        'promo_id_buscado' => $promoId,
                        'promo_id_encontrado' => $promo['id'] ?? 'NO TIENE',
                        'promo_index' => $promoIndex,
                        'imagen_path_enviado' => $request->input('imagen_path'),
                    ]);

                    // NO eliminar si el ID no coincide - esto previene eliminar la imagen de otra promoción
                    return response()->json(['error' => 'El ID de la promoción no coincide. No se eliminará la imagen para prevenir errores.'], 400);
                }
            }

            if (! $promo) {
                Log::warning('⚠️ Promoción no encontrada al intentar eliminar imagen', [
                    'promo_id' => $promoId,
                    'promo_index' => $promoIndex,
                    'imagen_path_enviado' => $request->input('imagen_path'),
                ]);

                return response()->json(['error' => 'Promoción no encontrada.'], 404);
            }

            // Verificar que el ID de la promoción encontrada coincide con el buscado
            if (isset($promo['id']) && $promo['id'] != $promoId) {
                Log::error('❌ ERROR CRÍTICO: El ID de la promoción encontrada NO coincide con el buscado', [
                    'promo_id_buscado' => $promoId,
                    'promo_id_encontrado' => $promo['id'],
                    'promo_index' => $promoIndex,
                    'imagen_path_enviado' => $request->input('imagen_path'),
                    'imagen_en_promo' => $promo['imagen'] ?? 'NO TIENE',
                ]);

                return response()->json(['error' => 'El ID de la promoción no coincide. No se eliminará la imagen para prevenir errores.'], 400);
            }

            // Guardar ruta de imagen anterior para eliminarla
            $oldImagePath = null;
            if (isset($promo['imagen']) && ! empty($promo['imagen'])) {
                $oldImagePath = str_replace('/storage/', '', $promo['imagen']);

                // IMPORTANTE: Verificar que la imagen que se va a eliminar coincide con la de la promoción
                $imagenPathEnviado = $request->input('imagen_path');
                if ($imagenPathEnviado) {
                    $imagenPathEnviadoClean = str_replace('/storage/', '', $imagenPathEnviado);
                    if ($oldImagePath !== $imagenPathEnviadoClean) {
                        Log::warning('⚠️ ADVERTENCIA: La imagen que se quiere eliminar NO coincide con la de la promoción', [
                            'promo_id' => $promoId,
                            'imagen_en_promo' => $promo['imagen'],
                            'imagen_enviada' => $imagenPathEnviado,
                            'imagen_en_promo_clean' => $oldImagePath,
                            'imagen_enviada_clean' => $imagenPathEnviadoClean,
                        ]);

                        // NO eliminar si no coincide - esto previene eliminar la imagen de otra promoción
                        return response()->json(['error' => 'La imagen que se quiere eliminar no coincide con la de la promoción. No se eliminará para prevenir errores.'], 400);
                    }
                }

                // Eliminar la imagen del JSON
                unset($promo['imagen']);
            } else {
                return response()->json(['error' => 'Esta promoción no tiene imagen.'], 400);
            }

            // Guardar JSON actualizado
            $jsonResult = json_encode($contenido, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

            if ($jsonResult === false) {
                return response()->json(['error' => 'Error al actualizar el menú: '.json_last_error_msg()], 500);
            }

            $menu->contexto_gpt = $jsonResult;
            $menu->save();

            // Eliminar imagen del storage
            if ($oldImagePath) {
                $this->deleteMenuItemSafely($oldImagePath);
            }

            return response()->json([
                'success' => true,
                'message' => 'Imagen eliminada exitosamente',
            ]);

        } catch (\Exception $e) {
            \Log::error('Error al eliminar imagen de promoción: '.$e->getMessage());

            return response()->json([
                'error' => 'Error al eliminar la imagen: '.$e->getMessage(),
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
        if (! $user->isSuperAdmin() && $comercio->user_id !== $user->id) {
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
        if (! is_array($promocionesData)) {
            Log::error('savePromocionesAjax: datos inválidos', [
                'promociones_data' => $promocionesData,
                'request_all' => $request->all(),
                'request_content' => $request->getContent(),
            ]);

            return response()->json(['success' => false, 'error' => 'Datos de promociones inválidos'], 400);
        }

        try {
            // Recibir logs del frontend si vienen
            $frontendLogs = $jsonBody['frontend_logs'] ?? [];
            if (! empty($frontendLogs)) {
                Log::info('📱 ========== LOGS DEL FRONTEND (collectPromocionesData) ==========');
                foreach ($frontendLogs as $log) {
                    Log::info('📱 '.$log);
                }
                Log::info('📱 ========== FIN LOGS DEL FRONTEND ==========');
            }

            Log::info('savePromocionesAjax llamado', [
                'menu_id' => $menu->id,
                'promociones_count' => count($promocionesData),
                'promociones_recibidas' => $promocionesData,
                'request_all' => $request->all(),
                'request_content_type' => $request->header('Content-Type'),
            ]);

            // Llamar al método privado savePromociones
            $this->savePromociones($menu, $promocionesData);

            return response()->json([
                'success' => true,
                'message' => 'Promociones guardadas exitosamente',
                'count' => count($promocionesData),
            ]);

        } catch (\Exception $e) {
            Log::error('Error al guardar promociones vía AJAX: '.$e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Error al guardar las promociones: '.$e->getMessage(),
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
                'count' => count($promociones),
            ]);

            // Solo guardar promociones que tengan al menos nombre o precio (además de imagen)
            // No crear registros vacíos solo con imagen
            $promocionesFiltradas = array_filter($promociones, function ($promo) {
                $tieneNombre = isset($promo['nombre']) && trim($promo['nombre']) !== '';
                $tienePrecio = isset($promo['precio']) && trim($promo['precio']) !== '';

                // Debe tener al menos nombre O precio para guardarse en el JSON
                return $tieneNombre || $tienePrecio;
            });

            // Reindexar array para eliminar índices vacíos
            $promocionesFiltradas = array_values($promocionesFiltradas);

            Log::info('Promociones filtradas (con algún dato)', [
                'count' => count($promocionesFiltradas),
                'promociones' => $promocionesFiltradas,
            ]);

            // Leer contexto_gpt actual
            $jsonContent = $menu->contexto_gpt;
            if (! $jsonContent) {
                // Si no hay contexto_gpt, crear estructura básica
                $contenido = [
                    'categorias' => [],
                ];
            } else {
                // Limpiar markdown si existe
                $clean = trim($jsonContent);
                if (strpos($clean, '```json') !== false) {
                    $clean = preg_replace('/```json\s*/', '', $clean);
                    $clean = preg_replace('/```\s*$/', '', $clean);
                }
                $contenido = json_decode(trim($clean), true);

                if (! $contenido || ! is_array($contenido)) {
                    $contenido = ['categorias' => []];
                }

                if (! isset($contenido['categorias']) || ! is_array($contenido['categorias'])) {
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
            // Indexar imágenes existentes por ID (más confiable que por índice/nombre)
            $imagenesExistentes = [];
            $idsExistentes = []; // Para generar IDs únicos
            if ($promocionesIndex >= 0 && isset($contenido['categorias'][$promocionesIndex]['platos'])) {
                $platosExistentes = $contenido['categorias'][$promocionesIndex]['platos'];
                Log::info('📋 Indexando imágenes existentes del JSON', [
                    'total_platos_en_json' => count($platosExistentes),
                    'promociones_recibidas' => count($promociones),
                ]);
                foreach ($platosExistentes as $idx => $platoExistente) {
                    // Si tiene ID, usarlo; si no, generar uno nuevo (migración)
                    $idExistente = isset($platoExistente['id']) ? $platoExistente['id'] : null;
                    if (! $idExistente) {
                        // Generar ID único si no existe (migración para items antiguos)
                        // Usar un contador incremental basado en el máximo existente
                        $idExistente = ! empty($idsExistentes) ? max($idsExistentes) + 1 + $idx : ($idx + 1);
                    }
                    $idsExistentes[] = $idExistente;

                    // Indexar imagen SOLO por ID (método más confiable y seguro)
                    // NO indexar por nombre/precio para evitar asignaciones incorrectas
                    if (isset($platoExistente['imagen']) && ! empty($platoExistente['imagen'])) {
                        $imagenesExistentes[$idExistente] = $platoExistente['imagen'];
                        Log::info('Imagen indexada por ID para preservación', [
                            'id' => $idExistente,
                            'imagen' => $platoExistente['imagen'],
                        ]);
                    } else {
                        Log::info("⚠️ Plato ID {$idExistente} NO tiene imagen en JSON", [
                            'nombre' => $platoExistente['nombre'] ?? 'SIN NOMBRE',
                            'tiene_imagen' => isset($platoExistente['imagen']) && ! empty($platoExistente['imagen']),
                        ]);
                    }
                }
                Log::info('📊 Resumen de indexación', [
                    'total_imagenes_indexadas' => count($imagenesExistentes),
                    'ids_con_imagen' => array_keys($imagenesExistentes),
                    'ids_sin_imagen' => array_diff($idsExistentes, array_keys($imagenesExistentes)),
                ]);
            } else {
                Log::warning('⚠️ No se encontró categoría de promociones en JSON', [
                    'promocionesIndex' => $promocionesIndex,
                    'tiene_categorias' => isset($contenido['categorias']),
                ]);
            }

            // Preparar array de platos para promociones, preservando imágenes e IDs
            $platosPromociones = [];

            Log::info('🔍 ========== INICIO savePromociones ==========', [
                'total_recibidas' => count($promocionesFiltradas),
                'total_filtradas' => count($promocionesFiltradas),
                'promociones_recibidas' => $promocionesFiltradas,
                'imagenes_existentes_indexadas' => array_keys($imagenesExistentes),
                'total_imagenes_existentes' => count($imagenesExistentes),
            ]);

            // Generar próximo ID único (basado en el máximo existente + 1)
            $proximoId = ! empty($idsExistentes) ? max($idsExistentes) + 1 : 1;

            // Usar promocionesFiltradas (ya filtradas para tener al menos un dato)
            foreach ($promocionesFiltradas as $idx => $promo) {
                Log::info("📦 Procesando promoción índice $idx", [
                    'promo_recibida' => $promo,
                    'tiene_campo_imagen' => isset($promo['imagen']),
                    'valor_imagen' => isset($promo['imagen']) ? ($promo['imagen'] ?: 'VACÍA') : 'NO ENVIADA',
                    'tiene_id' => isset($promo['id']),
                    'id_valor' => $promo['id'] ?? 'NO TIENE',
                ]);

                // Asegurar que siempre tengamos los campos básicos (aunque estén vacíos)
                if (! isset($promo['nombre'])) {
                    $promo['nombre'] = '';
                }
                if (! isset($promo['precio'])) {
                    $promo['precio'] = '';
                }
                if (! isset($promo['descripcion'])) {
                    $promo['descripcion'] = '';
                }

                // Obtener o generar ID único
                $idPromocion = null;
                if (isset($promo['id']) && ! empty($promo['id'])) {
                    // Si viene con ID, preservarlo
                    $idPromocion = $promo['id'];
                    Log::info("  ✅ ID preservado: $idPromocion");
                } else {
                    // Si no tiene ID, generar uno nuevo
                    $idPromocion = $proximoId++;
                    Log::info("  🆕 ID generado: $idPromocion");
                }

                $nombreNormalizado = mb_strtolower(trim($promo['nombre']));

                $plato = [
                    'id' => $idPromocion, // ID único e inmutable
                    'nombre' => trim($promo['nombre'] ?? ''),
                    'precio' => trim($promo['precio'] ?? ''),
                    'descripcion' => trim($promo['descripcion'] ?? ''),
                ];

                // LÓGICA ULTRA SIMPLE DE IMÁGENES (RESPETAR LO QUE ENVÍA EL FRONTEND):
                // 1. Si viene imagen NO vacía en el request → usar esa (el usuario la agregó/modificó)
                // 2. Si viene imagen VACÍA ('') en el request → NO asignar ninguna (el usuario la borró)
                // 3. Si NO viene el campo 'imagen' en el request → preservar SOLO si existe por ID y físicamente

                $imagenEnRequest = isset($promo['imagen']) ? trim($promo['imagen']) : null;

                Log::info("  🔍 Analizando imagen para promoción ID $idPromocion:", [
                    'imagen_en_request' => $imagenEnRequest !== null ? ($imagenEnRequest ?: 'VACÍA') : 'NO ENVIADA',
                    'id_existe_en_imagenes_existentes' => isset($imagenesExistentes[$idPromocion]),
                    'imagen_en_existentes' => isset($imagenesExistentes[$idPromocion]) ? $imagenesExistentes[$idPromocion] : 'NO EXISTE',
                ]);

                if ($imagenEnRequest !== null) {
                    // El campo 'imagen' fue enviado explícitamente en el request
                    if (! empty($imagenEnRequest)) {
                        // Caso 1: Viene con valor → validar que existe físicamente y usar esa (prioridad máxima)
                        // IMPORTANTE: Si el frontend envía una imagen, SIEMPRE usarla, incluso si no está en el JSON actual
                        $imagenStoragePath = str_replace('/storage/', '', $imagenEnRequest);
                        $fullPath = storage_path('app/public/'.$imagenStoragePath);

                        // IMPORTANTE: Si el frontend envía una imagen, confiar en ella aunque no esté en el JSON
                        // El frontend solo envía imágenes que están en el DOM, así que si la envía, debe existir
                        // Pero validar que el archivo existe físicamente antes de asignarla
                        if (file_exists($fullPath)) {
                            $plato['imagen'] = $imagenEnRequest;
                            Log::info("✅ Imagen asignada desde request para promoción ID $idPromocion (existe físicamente)", [
                                'imagen' => $plato['imagen'],
                                'nombre' => $plato['nombre'],
                                'full_path' => $fullPath,
                                'existe' => true,
                                'estaba_en_json' => isset($imagenesExistentes[$idPromocion]) ? 'SÍ' : 'NO',
                                'imagen_en_json' => $imagenesExistentes[$idPromocion] ?? 'NO EXISTE',
                            ]);
                        } else {
                            // La imagen no existe físicamente → verificar si hay una alternativa en el JSON
                            // Pero si el frontend la envía, es porque está en el DOM, así que puede ser un problema de ruta
                            // Intentar buscar el archivo con diferentes variaciones de la ruta
                            $alternativas = [
                                $fullPath,
                                storage_path('app/public/'.ltrim($imagenEnRequest, '/')),
                                public_path($imagenEnRequest),
                                public_path('storage/'.str_replace('/storage/', '', $imagenEnRequest)),
                            ];

                            $imagenEncontrada = false;
                            foreach ($alternativas as $altPath) {
                                if (file_exists($altPath)) {
                                    $plato['imagen'] = $imagenEnRequest; // Usar la ruta original del request
                                    Log::warning("✅ Imagen encontrada en ruta alternativa para promoción ID $idPromocion", [
                                        'imagen_request' => $imagenEnRequest,
                                        'ruta_original' => $fullPath,
                                        'ruta_encontrada' => $altPath,
                                        'existe' => true,
                                    ]);
                                    $imagenEncontrada = true;
                                    break;
                                }
                            }

                            if (! $imagenEncontrada) {
                                // Si no existe físicamente, intentar preservar desde existentes si existe
                                if (isset($imagenesExistentes[$idPromocion]) && ! empty($imagenesExistentes[$idPromocion])) {
                                    $imagenExistente = $imagenesExistentes[$idPromocion];
                                    $imagenExistenteStoragePath = str_replace('/storage/', '', $imagenExistente);
                                    $imagenExistenteFullPath = storage_path('app/public/'.$imagenExistenteStoragePath);

                                    if (file_exists($imagenExistenteFullPath)) {
                                        $plato['imagen'] = $imagenExistente;
                                        Log::warning("⚠️ Imagen del request no existe, pero se preservó desde JSON para promoción ID $idPromocion", [
                                            'imagen_request' => $imagenEnRequest,
                                            'imagen_preservada' => $imagenExistente,
                                            'full_path_request' => $fullPath,
                                            'full_path_preservada' => $imagenExistenteFullPath,
                                        ]);
                                    } else {
                                        Log::warning("⚠️ Imagen del request no existe físicamente para promoción ID $idPromocion - NO se asignará", [
                                            'imagen_path' => $imagenEnRequest,
                                            'full_path' => $fullPath,
                                            'existe' => file_exists($fullPath),
                                            'razon' => 'Archivo físico no encontrado aunque viene en request',
                                            'rutas_verificadas' => $alternativas,
                                        ]);
                                    }
                                } else {
                                    // La imagen no existe físicamente y no hay alternativa en el JSON
                                    Log::warning("⚠️ Imagen del request no existe físicamente para promoción ID $idPromocion - NO se asignará", [
                                        'imagen_path' => $imagenEnRequest,
                                        'full_path' => $fullPath,
                                        'existe' => file_exists($fullPath),
                                        'razon' => 'Archivo físico no encontrado aunque viene en request',
                                        'rutas_verificadas' => $alternativas,
                                    ]);
                                }
                            }
                        }
                    } else {
                        // Caso 2: Viene vacía ('') → el usuario la borró, NO asignar ninguna
                        // NO preservar aunque exista en existentes
                        Log::info("🗑️ Imagen explícitamente vacía en request para promoción ID $idPromocion - usuario la borró, NO preservar", [
                            'nombre' => $plato['nombre'],
                            'precio' => $plato['precio'],
                        ]);
                    }
                } elseif (isset($imagenesExistentes[$idPromocion]) && ! empty($imagenesExistentes[$idPromocion])) {
                    // Caso 3: NO viene el campo 'imagen' en el request → preservar si existe por ID y físicamente
                    $imagenPath = $imagenesExistentes[$idPromocion];
                    $imagenStoragePath = str_replace('/storage/', '', $imagenPath);
                    $fullPath = storage_path('app/public/'.$imagenStoragePath);

                    // SOLO preservar si la imagen existe físicamente
                    if (file_exists($fullPath)) {
                        $plato['imagen'] = $imagenPath;
                        Log::info("✅ Imagen preservada por ID para promoción ID $idPromocion (no venía en request, existe físicamente)", [
                            'imagen' => $imagenPath,
                            'nombre' => $plato['nombre'],
                            'full_path' => $fullPath,
                            'existe' => true,
                        ]);
                    } else {
                        // La imagen no existe físicamente → no preservar
                        Log::warning("⚠️ Imagen no existe físicamente para promoción ID $idPromocion - no se preservará", [
                            'imagen_path' => $imagenPath,
                            'full_path' => $fullPath,
                            'existe' => file_exists($fullPath),
                            'razon' => 'Archivo físico no encontrado',
                        ]);
                    }
                } else {
                    // Caso 4: No hay imagen en request ni en existentes
                    // IMPORTANTE: Si el ID existe pero no está en existentes, podría ser que se eliminó del JSON
                    // pero el frontend debería haberla enviado si existe en el DOM
                    Log::info("ℹ️ Promoción ID $idPromocion sin imagen - no se asignará ninguna", [
                        'nombre' => $plato['nombre'],
                        'precio' => $plato['precio'],
                        'imagen_en_request' => $imagenEnRequest !== null ? ($imagenEnRequest ?: 'VACÍA') : 'NO ENVIADA',
                        'id_existe_en_existentes' => isset($imagenesExistentes[$idPromocion]) ? 'SÍ' : 'NO',
                        'total_imagenes_existentes' => count($imagenesExistentes),
                        'ids_existentes' => array_keys($imagenesExistentes),
                    ]);
                }
                // NO buscar por nombre/precio para evitar asignaciones incorrectas de imágenes

                Log::info('  ✅ Promoción procesada:', [
                    'id' => $idPromocion,
                    'nombre' => $plato['nombre'],
                    'precio' => $plato['precio'],
                    'tiene_imagen' => isset($plato['imagen']),
                    'imagen' => $plato['imagen'] ?? 'NO ASIGNADA',
                ]);

                $platosPromociones[] = $plato;
            }

            Log::info('🔍 ========== FIN savePromociones ==========', [
                'total_procesadas' => count($platosPromociones),
                'promociones_finales' => $platosPromociones,
            ]);

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
                        'platos' => $platosPromociones,
                    ];
                }
            }

            // Guardar JSON actualizado (solo si hay cambios)
            $jsonResult = json_encode($contenido, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

            if ($jsonResult === false) {
                Log::error('Error al codificar JSON de promociones: '.json_last_error_msg());

                return;
            }

            $menu->contexto_gpt = $jsonResult;
            $menu->save();

            Log::info('Promociones guardadas exitosamente', [
                'menu_id' => $menu->id,
                'cantidad' => count($platosPromociones),
                'json_length' => strlen($jsonResult),
                'platos' => $platosPromociones,
            ]);

        } catch (\Exception $e) {
            Log::error('Error al guardar promociones: '.$e->getMessage());
        }
    }

    /**
     * Exportar menú a Excel
     */
    public function export(Comercio $comercio, Menu $menu)
    {
        $user = auth()->user();

        // Validar permisos
        if (! $user->isSuperAdmin() && $comercio->user_id !== $user->id) {
            abort(403, 'No tienes permiso para exportar este menú.');
        }

        if ($menu->comercio_id !== $comercio->id) {
            abort(404, 'Menú no encontrado en este comercio.');
        }

        $nombreBase = ($menu->nombre ?: 'menu').'_'.date('Y-m-d');
        $nombreBase = preg_replace('/[^a-zA-Z0-9_-]/', '_', $nombreBase);
        $nombreArchivo = $nombreBase.'.xlsx';

        return Excel::download(new MenuExport($menu), $nombreArchivo, ExcelType::XLSX);
    }

    /**
     * Previsualizar importación de Excel
     */
    public function previewImport(Request $request, Comercio $comercio)
    {
        $user = auth()->user();

        // Validar permisos
        if (! $user->isSuperAdmin() && $comercio->user_id !== $user->id) {
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
            if (! empty($structureErrors)) {
                $redirectRoute = $request->has('menu_id')
                    ? route('menus.edit', [$comercio, $request->input('menu_id')])
                    : route('menus.index', $comercio);

                return redirect($redirectRoute)
                    ->with('error', 'Error en la estructura del archivo: '.implode(', ', $structureErrors));
            }

            // Procesar datos
            $import->collection($rows);
            $data = $import->getData();

            // Guardar datos procesados en sesión en lugar de archivo temporal
            $sessionKey = 'menu_import_data_'.$comercio->id.'_'.time();
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
            Log::error('Error al previsualizar importación: '.$e->getMessage());
            $redirectRoute = $request->has('menu_id')
                ? route('menus.edit', [$comercio, $request->input('menu_id')])
                : route('menus.index', $comercio);

            return redirect($redirectRoute)
                ->with('error', 'Error al leer el archivo Excel: '.$e->getMessage());
        }
    }

    /**
     * Importar menú desde Excel
     */
    public function import(Request $request, Comercio $comercio)
    {
        $user = auth()->user();

        // Validar permisos
        if (! $user->isSuperAdmin() && $comercio->user_id !== $user->id) {
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

            if (! $data || ! is_array($data)) {
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

                // El Excel solo trae categorías y platos, y acá se pisa el JSON entero: lo que
                // vive fuera de esas dos claves se perdía. `restaurante` (nombre y descripción
                // del local) lo lee ChatbotService para armar el contexto del asistente.
                $anterior = json_decode((string) $menu->contexto_gpt, true);
                if (is_array($anterior)) {
                    $data = array_merge(array_diff_key($anterior, ['categorias' => null]), $data);
                }

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
                $menu = new Menu;
                $menu->comercio_id = $comercio->id;
                $menu->nombre = $request->input('nombre') ?: 'Menú Importado '.date('Y-m-d');
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
            Log::error('Error al importar menú: '.$e->getMessage());

            return redirect()->route('menus.index', $comercio)
                ->with('error', 'Error al importar el menú: '.$e->getMessage());
        }
    }

    /**
     * Actualizar colores del tema automáticamente
     */
    public function updateThemeColors(Request $request, Comercio $comercio)
    {
        $user = auth()->user();

        // Validar permisos
        if (! $user->isSuperAdmin() && $comercio->user_id !== $user->id) {
            return response()->json(['error' => 'No tienes permiso para actualizar los colores.'], 403);
        }

        try {
            // El menú digital usa UN solo color (el de marca). Los otros tres quedan
            // aceptados como opcionales por compatibilidad, pero ya no se usan al pintar.
            $validated = $request->validate([
                'primary_color' => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',
                'secondary_color' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
                'accent_color' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
                'background_color' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
            ]);

            $comercio->primary_color = $validated['primary_color'];
            foreach (['secondary_color', 'accent_color', 'background_color'] as $campo) {
                if (! empty($validated[$campo])) {
                    $comercio->$campo = $validated[$campo];
                }
            }
            $comercio->save();

            return response()->json([
                'success' => true,
                'message' => 'Colores actualizados correctamente',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Error de validación al actualizar colores: '.json_encode($e->errors()));

            return response()->json([
                'error' => 'Error de validación',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error al actualizar colores: '.$e->getMessage());

            return response()->json(['error' => 'Error al actualizar los colores: '.$e->getMessage()], 500);
        }
    }

    /**
     * Actualizar configuración del chatbot y pedidos automáticamente
     */
    /**
     * Guarda los chips que el comercio configuró para una superficie ('portada' o 'chat').
     *
     * El `id` no viaja desde el navegador: lo calcula `Support\Chip` a partir del texto y
     * la pregunta. Así, editar cualquiera de los dos genera un chip nuevo y la analítica
     * puede comparar versiones en vez de sumar peras con manzanas.
     */
    public function updateChips(Request $request, Comercio $comercio)
    {
        $user = auth()->user();

        if (! $user->isSuperAdmin() && $comercio->user_id !== $user->id) {
            return response()->json(['error' => 'No tienes permiso para actualizar la configuración.'], 403);
        }

        $superficie = (string) $request->input('superficie');

        $validated = $request->validate([
            'superficie' => 'required|string|in:'.implode(',', Chip::SUPERFICIES),
            // El tope depende de la pantalla: la portada entra en una grilla de tres.
            'chips' => 'nullable|array|max:'.Chip::visibles($superficie),
            'chips.*.emoji' => 'nullable|string|max:8',
            'chips.*.label' => 'required|string|max:40',
            'chips.*.q' => 'required|string|max:200',
        ]);

        try {
            $chips = $comercio->chips ?? [];
            $limpios = Chip::normalizarLista($validated['chips'] ?? []);

            // Lista vacía = volver a los automáticos del menú, no quedarse sin chips.
            if (empty($limpios)) {
                unset($chips[$validated['superficie']]);
            } else {
                $chips[$validated['superficie']] = $limpios;
            }

            $comercio->chips = empty($chips) ? null : $chips;
            $comercio->save();

            return response()->json([
                'success' => true,
                'chips' => $comercio->chipsDe($validated['superficie']),
                'automaticos' => empty($limpios),
            ]);
        } catch (\Exception $e) {
            Log::error('Error al guardar chips: '.$e->getMessage());

            return response()->json(['error' => 'Error al guardar los atajos: '.$e->getMessage()], 500);
        }
    }

    /**
     * Prende o apaga el pedido de reseña en Google.
     *
     * El link se carga en los datos del comercio; acá solo se decide si se muestra. Sin
     * link no se puede activar: el botón abriría la nada, y el comercio quedaría creyendo
     * que está pidiendo reseñas.
     */
    /**
     * Con qué abre el "menú clásico": el archivo del comercio o la lista de platos.
     *
     * Se guarda la preferencia aunque no haya archivo; el modelo resuelve ese caso
     * mostrando la lista igual (ver `Comercio::vistaMenuClasico`).
     */
    public function updateVistaClasica(Request $request, Comercio $comercio)
    {
        $user = auth()->user();

        if (! $user->isSuperAdmin() && $comercio->user_id !== $user->id) {
            return response()->json(['error' => 'No tienes permiso para actualizar la configuración.'], 403);
        }

        $validated = $request->validate([
            'vista' => 'required|in:'.Comercio::VISTA_ARCHIVO.','.Comercio::VISTA_LISTA,
        ]);

        $comercio->update(['menu_clasico_vista' => $validated['vista']]);

        return response()->json(['success' => true, 'vista' => $validated['vista']]);
    }

    public function updateResenas(Request $request, Comercio $comercio)
    {
        $user = auth()->user();

        if (! $user->isSuperAdmin() && $comercio->user_id !== $user->id) {
            return response()->json(['error' => 'No tienes permiso para actualizar la configuración.'], 403);
        }

        // Parcial a propósito: el interruptor manda `activo` y los textos van por su
        // propio botón. Cada uno pisa solo lo suyo.
        $validated = $request->validate([
            'activo' => 'sometimes|boolean',
            'titulo' => 'sometimes|nullable|string|max:80',
            'texto' => 'sometimes|nullable|string|max:200',
        ]);

        if (($validated['activo'] ?? false) && blank($comercio->google_review_url)) {
            return response()->json([
                'error' => 'Primero cargá el link de reseñas en los datos del comercio.',
            ], 422);
        }

        try {
            if ($request->has('activo')) {
                $comercio->resenas_activo = $validated['activo'];
            }

            // Vacío guarda NULL, no cadena vacía: es lo que hace que vuelva el texto de
            // fábrica en vez de mostrarle al cliente un bloque sin título.
            if ($request->has('titulo')) {
                $comercio->resena_titulo = filled($validated['titulo'] ?? null) ? trim($validated['titulo']) : null;
            }
            if ($request->has('texto')) {
                $comercio->resena_texto = filled($validated['texto'] ?? null) ? trim($validated['texto']) : null;
            }

            $comercio->save();

            return response()->json([
                'success' => true,
                'activo' => $comercio->resenas_activo,
                'por_defecto' => blank($comercio->resena_titulo) && blank($comercio->resena_texto),
            ]);
        } catch (\Exception $e) {
            Log::error('Error al guardar reseñas: '.$e->getMessage());

            return response()->json(['error' => 'Error al guardar la configuración.'], 500);
        }
    }

    public function updateChatbotConfig(Request $request, Comercio $comercio)
    {
        $user = auth()->user();

        // Validar permisos
        if (! $user->isSuperAdmin() && $comercio->user_id !== $user->id) {
            return response()->json(['error' => 'No tienes permiso para actualizar la configuración.'], 403);
        }

        try {
            $validated = $request->validate([
                'chatbot_name' => 'nullable|string|max:50',
                'welcome_message' => 'nullable|string|max:500',
                'pedido_habilitado' => 'nullable|boolean',
                // Personalidad del asistente
                'chatbot_config' => 'nullable|array',
                'chatbot_config.tono' => 'nullable|string|in:cercano_divertido,calido_atento,formal_elegante,canchero',
                'chatbot_config.trato' => 'nullable|string|in:voseo,tuteo,neutro',
                'chatbot_config.emojis' => 'nullable|string|in:moderado,minimo,ninguno',
                'chatbot_config.focos' => 'nullable|array',
                // La lista sale de ChatbotService::FOCOS y no de un `in:` escrito a mano:
                // si se desincronizaban, el auto-guardado se comía un 422 sin avisar nada.
                'chatbot_config.focos.*' => 'string|in:'.implode(',', array_keys(ChatbotService::FOCOS)),
                'chatbot_config.estrellas' => 'nullable|array',
                'chatbot_config.estrellas.*' => 'string|max:120',
                'chatbot_config.detalles' => 'nullable|string|max:600',
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
            if ($request->has('chatbot_config')) {
                $comercio->chatbot_config = $validated['chatbot_config'] ?? null;
            }

            $comercio->save();

            return response()->json([
                'success' => true,
                'message' => 'Configuración actualizada correctamente',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Error de validación al actualizar configuración: '.json_encode($e->errors()));

            return response()->json([
                'error' => 'Error de validación',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error al actualizar configuración: '.$e->getMessage());

            return response()->json(['error' => 'Error al actualizar la configuración: '.$e->getMessage()], 500);
        }
    }
}
