<?php

namespace App\Http\Controllers;

use App\Jobs\GenerateBrandThemeJob;
use App\Models\Comercio;
use App\Models\Plan;
use App\Models\User;
use App\Services\BrandThemeService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ComercioController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = auth()->user();

        if ($user->isSuperAdmin()) {
            $query = Comercio::with(['user', 'menus', 'chatConversations'])->latest();
            $filterUser = null;

            if ($request->filled('user_id')) {
                $query->where('user_id', $request->user_id);
                $filterUser = User::find($request->user_id);
            }

            $comercios = $query->get();
        } else {
            $comercios = Comercio::with(['menus', 'chatConversations'])
                ->where('user_id', $user->id)->get();
            $filterUser = null;
        }

        return view('comercios.index', compact('comercios', 'filterUser'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $user = auth()->user();

        if ($user->isSuperAdmin()) {
            $users = User::where('role', 'user')->get();
            $plans = Plan::where('activo', true)->orderBy('orden')->get();
            $forUserId = request('for_user');

            return view('comercios.create', compact('users', 'plans', 'forUserId'));
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
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ];

        if ($user->isSuperAdmin()) {
            $validationRules['user_id'] = 'required|exists:users,id';
            $validationRules['plan_id'] = 'nullable|exists:plans,id';
        }

        $validated = $request->validate($validationRules);

        if ($user->isSuperAdmin()) {
            // Super Admin puede crear comercios para cualquier usuario
            $targetUser = User::findOrFail($validated['user_id']);

            $comercio = new Comercio;
            $comercio->user_id = $validated['user_id'];
        } else {
            // Usuario común: verificar si ya tiene un comercio
            if ($user->comercios()->count() > 0) {
                return redirect()->route('comercios.index')
                    ->with('warning', 'Ya tienes un comercio. Solo puedes tener uno.');
            }

            $comercio = new Comercio;
            $comercio->user_id = auth()->id();
        }
        $comercio->nombre = $validated['nombre'];
        $comercio->slug = Comercio::generarSlugUnico($validated['nombre']);
        $comercio->descripcion = $validated['descripcion'];
        $comercio->direccion = $validated['direccion'];
        $comercio->horario = $validated['horario'];

        // Asignar plan: el elegido por admin, o Pro por defecto
        if ($user->isSuperAdmin() && ! empty($validated['plan_id'])) {
            $comercio->plan_id = $validated['plan_id'];
        } else {
            $planPro = Plan::where('key', 'pro')->first();
            if ($planPro) {
                $comercio->plan_id = $planPro->id;
            }
        }

        $comercio->save();

        // Crear estructura de carpetas para el comercio
        $this->createComercioDirectories($comercio->id);

        // Procesar logo si se subió (después de crear carpetas)
        if ($request->hasFile('logo')) {
            $comercio->logo_url = $this->saveSquareLogo($request->file('logo'), 512, $comercio->id);
            $comercio->save();
        }

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
        if (! $user->isSuperAdmin() && $comercio->user_id !== $user->id) {
            abort(403, 'No tienes permiso para acceder a este comercio.');
        }

        // Cargar relación de usuario
        $comercio->load('user');

        return view('comercios.edit', compact('comercio'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Comercio $comercio)
    {
        $user = auth()->user();

        // Validar permisos: Super Admin puede editar cualquiera, usuario común solo sus comercios
        if (! $user->isSuperAdmin() && $comercio->user_id !== $user->id) {
            abort(403, 'No tienes permiso para editar este comercio.');
        }

        $validated = $request->validate([
            // Información básica del comercio
            'nombre' => 'required|string|max:255',

            // URL pública. Sólo la aplica el super admin (ver update()); si la
            // manda un usuario común, se ignora.
            'slug' => 'nullable|string|max:255|regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',

            'descripcion' => 'nullable|string',
            'tagline' => 'nullable|string|max:80',
            'direccion' => 'nullable|string|max:255',
            'horario' => 'nullable|string|max:255',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',

            // Portada: la foto que representa al comercio y de la que se
            // deriva el tema del menú público. NO pasa por saveSquareLogo():
            // ese recorte a 512x512 es para isotipos, no para fotos.
            //
            // Sin mínimo de dimensiones a propósito: una foto chica se procesa
            // igual y sale borrosa, pero rechazarla dejaba al comercio sin
            // portada. Se acepta y se le avisa (tema_derivado.baja_calidad).
            //
            // El máximo sí es duro, y no es por el peso: GD descomprime a
            // bitmap, así que la memoria es ancho × alto × 4 bytes. Un JPEG de
            // 1 MB en 8000×6000 son 192 MB en RAM y tumba el proceso con un
            // fatal error inatrapable. 8000×8000 = 64 MP es el techo.
            'portada' => 'nullable|image|max:8192|dimensions:max_width=8000,max_height=8000',

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
            'pedido_habilitado' => 'nullable|boolean',
        ]);

        $comercio->nombre = $validated['nombre'];

        // ── URL pública ──────────────────────────────────────────────────
        // El slug está impreso en los QR de las mesas, así que cambiarlo sin
        // más rompía todo en silencio. Ahora cada slug anterior queda como
        // alias y sigue resolviendo, con lo cual el cambio es seguro:
        //
        //   - El admin la puede fijar a mano, y eso la deja "manual".
        //   - Renombrar la actualiza sola, salvo que sea manual: si el admin
        //     eligió una URL a propósito, un renombrado no se la pisa.
        if ($user->isSuperAdmin() && filled($validated['slug'] ?? null)) {
            if (! $comercio->cambiarSlug($validated['slug'], manual: true)) {
                return back()->withInput()->withErrors([
                    'slug' => 'Esa URL ya la usa otro comercio (o la usó antes).',
                ]);
            }
        } elseif (blank($comercio->slug)) {
            $comercio->slug = Comercio::generarSlugUnico($validated['nombre'], $comercio->id);
        } elseif (! $comercio->slug_manual) {
            $comercio->cambiarSlug(Comercio::generarSlugUnico($validated['nombre'], $comercio->id));
        }

        // Los campos `nullable` pueden no venir en el request: leerlos directo
        // tira "Undefined array key" y devuelve un 500. El formulario los manda
        // siempre, pero cualquier request parcial (AJAX, integración) rompía.
        $comercio->descripcion = $validated['descripcion'] ?? null;
        $comercio->tagline = $validated['tagline'] ?? null;
        $comercio->direccion = $validated['direccion'] ?? null;
        $comercio->horario = $validated['horario'] ?? null;

        // Actualizar configuración de personalización
        $comercio->primary_color = $validated['primary_color'];
        $comercio->secondary_color = $validated['secondary_color'];
        $comercio->accent_color = $validated['accent_color'];
        $comercio->background_color = $validated['background_color'];
        $comercio->welcome_message = $validated['welcome_message'] ?? null;
        $comercio->menu_description = $validated['menu_description'] ?? null;
        $comercio->contact_info = $validated['contact_info'] ?? null;
        $comercio->chatbot_name = $validated['chatbot_name'];
        $comercio->show_contact_info = $validated['show_contact_info'] ?? false;
        $comercio->enable_ai_responses = $validated['enable_ai_responses'] ?? false;
        $comercio->pedido_habilitado = $request->boolean('pedido_habilitado');
        $comercio->page_title = $validated['page_title'] ?? null;
        $comercio->page_description = $validated['page_description'] ?? null;
        $comercio->favicon_url = $validated['favicon_url'] ?? null;

        // Manejar eliminación de logo
        if ($request->has('delete_logo') && $request->delete_logo == '1') {
            $this->deleteLogoSafely($comercio->logo_url);
            $comercio->logo_url = null;
        }
        // Manejar subida de nuevo logo
        elseif ($request->hasFile('logo')) {
            // Eliminar logo anterior si existe
            $this->deleteLogoSafely($comercio->logo_url);
            $comercio->logo_url = $this->saveSquareLogo($request->file('logo'), 512, $comercio->id);
        }

        // Manejar eliminación de portada. No se bloquea por plan: si un comercio
        // baja de plan tiene que poder sacar su portada igual.
        if ($request->boolean('delete_portada')) {
            $this->limpiarPortada($comercio);
        }
        // Manejar subida de nueva portada.
        elseif ($request->hasFile('portada')) {
            // Gating inline, no por middleware: update() hace muchas otras cosas
            // que no dependen del plan, así que no se puede cortar la ruta entera.
            // Mismo criterio que analytics en AnalyticsController.
            if (! $user->isSuperAdmin() && ! $comercio->puedePersonalizarPortada()) {
                abort(403, 'La portada personalizada no está disponible en el plan actual del comercio.');
            }

            $this->guardarPortada($comercio, $request->file('portada'));
        }

        $comercio->save();

        // Después del save() para que la portada_url ya esté persistida.
        if ($comercio->portada_estado === 'procesando') {
            $this->generarTemaDePortada($comercio);
        }

        // Si es una petición AJAX, devolver JSON
        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Comercio actualizado exitosamente',
            ]);
        }

        // Redirigir según el tipo de usuario
        $user = auth()->user();
        if ($user->isSuperAdmin()) {
            // Para Super Admin, usar la ruta de admin sin el prefijo admin.
            return redirect()->route('dashboard')->with('success', 'Comercio actualizado exitosamente');
        } else {
            return redirect()->route('comercios.index')->with('success', 'Comercio actualizado exitosamente');
        }
    }

    /**
     * Vista previa del comercio
     */

    /**
     * Crear estructura de carpetas para un comercio
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
            \Log::warning('Error al crear directorios del comercio', [
                'comercio_id' => $comercioId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Guardar el logo en un tamaño cuadrado fijo (recorte centrado + resize)
     */
    private function saveSquareLogo(\Illuminate\Http\UploadedFile $file, int $size = 512, ?int $comercioId = null): string
    {
        // Si no se pasa comercio_id, intentar obtenerlo del request
        if (! $comercioId && request()->route('comercio')) {
            $comercioId = request()->route('comercio')->id;
        }

        // Si aún no tenemos comercio_id, usar estructura antigua (compatibilidad)
        if (! $comercioId) {
            $path = storage_path('app/public/logos');
            $logoPath = 'logos';
        } else {
            // Asegurar que existan las carpetas del comercio
            $this->createComercioDirectories($comercioId);
            $path = storage_path("app/public/comercios/{$comercioId}/logos");
            $logoPath = "comercios/{$comercioId}/logos";
        }

        if (! is_dir($path)) {
            mkdir($path, 0775, true);
            chmod($path, 0775);
        } else {
            // Asegurar permisos de escritura aunque el directorio exista
            chmod($path, 0775);
        }

        // Si no está disponible la librería GD, hacer fallback al almacenamiento directo
        if (! function_exists('imagecreatetruecolor')) {
            if ($comercioId) {
                $this->createComercioDirectories($comercioId);

                return $file->store("comercios/{$comercioId}/logos", 'public');
            }

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
                if ($comercioId) {
                    $this->createComercioDirectories($comercioId);

                    return $file->store("comercios/{$comercioId}/logos", 'public');
                }

                return $file->store('logos', 'public');
        }

        // Recorte cuadrado centrado
        $side = min($w, $h);
        $srcX = (int) (($w - $side) / 2);
        $srcY = (int) (($h - $side) / 2);

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
        $filename = 'logo_'.Str::random(16).'.png';
        $full = $path.DIRECTORY_SEPARATOR.$filename;
        \imagepng($dst, $full, 6);

        // Asegurar permisos de lectura/escritura del archivo
        if (file_exists($full)) {
            chmod($full, 0664);
        }

        // SIEMPRE intentar copiar a public_html/storage (incluso si file_exists falló)
        // Esto asegura que se ejecute siempre
        $this->copyLogoToPublicHtml($full, $filename, $comercioId);

        \imagedestroy($src);
        \imagedestroy($dst);

        return $logoPath.'/'.$filename;
    }

    /**
     * Copiar logo a public_html/storage automáticamente
     */
    private function copyLogoToPublicHtml(string $sourceFile, string $filename, ?int $comercioId = null): void
    {
        // Nueva estructura: comercios/{id}/logos o estructura antigua logos/
        if ($comercioId) {
            $publicHtmlPath = "/home/u560898880/domains/servime.net/public_html/storage/comercios/{$comercioId}/logos";
        } else {
            $publicHtmlPath = '/home/u560898880/domains/servime.net/public_html/storage/logos';
        }

        // Verificar si estamos en producción (Hostinger)
        if (! file_exists('/home/u560898880/domains')) {
            // No estamos en producción, salir silenciosamente
            return;
        }

        try {
            // Crear directorio si no existe
            if (! is_dir($publicHtmlPath)) {
                if (! mkdir($publicHtmlPath, 0775, true)) {
                    \Log::error('No se pudo crear directorio en public_html', ['path' => $publicHtmlPath]);

                    return;
                }
                chmod($publicHtmlPath, 0775);
            }

            // Copiar el archivo
            $targetFile = $publicHtmlPath.'/'.$filename;

            // Intentar copiar
            if (! copy($sourceFile, $targetFile)) {
                $error = error_get_last();
                \Log::error('Error al copiar logo', [
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

            \Log::info('Logo copiado exitosamente a public_html', [
                'file' => $filename,
                'target' => $targetFile,
                'size' => filesize($targetFile),
            ]);

        } catch (\Exception $e) {
            \Log::error('Excepción al copiar logo', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Eliminar logo de forma segura (tanto de storage como de public_html)
     */
    /**
     * Genera el tema: en el momento si la foto es de tamaño normal, en la cola
     * si es enorme.
     *
     * La cola es la pieza más frágil de la infraestructura (worker por cron en
     * el server), así que cuanto menos dependa de ella el caso común, mejor.
     * Una foto de celular estándar se resuelve en ~3 s y el comerciante ve su
     * portada al guardar, sin barras ni esperas. Las gigantes sí van a la
     * cola: tardan de más para un request y ahí la espera se justifica.
     */
    private function generarTemaDePortada(Comercio $comercio): void
    {
        $temas = app(BrandThemeService::class);
        $ruta = \Storage::disk('public')->path($comercio->portada_url);

        if (! $temas->puedeProcesarInline($ruta)) {
            GenerateBrandThemeJob::dispatch($comercio);

            return;
        }

        try {
            $comercio->update($temas->generar($comercio, $ruta) + ['portada_estado' => 'listo']);
        } catch (\Throwable $e) {
            \Log::error('Fallo al generar el tema en caliente: '.$e->getMessage(), [
                'comercio_id' => $comercio->id,
            ]);

            // Puede haber sido algo puntual del request (memoria ya ocupada por
            // otra cosa). En la cola arranca con el proceso limpio; si vuelve a
            // fallar, el job deja el estado en 'error' y el panel lo muestra.
            GenerateBrandThemeJob::dispatch($comercio);
        }
    }

    /**
     * Estado del procesado de la portada, para que el panel lo consulte solo.
     *
     * Sin esto el comerciante veía "generando…" indefinidamente: si la cola
     * estaba caída, no había ninguna señal de que algo andaba mal.
     */
    public function portadaEstado(Comercio $comercio)
    {
        $this->autorizarComercio($comercio);

        return response()->json([
            'estado' => $comercio->portada_estado,
            'listo' => $comercio->tienePortada(),
            'baja_calidad' => (bool) ($comercio->tema_derivado['baja_calidad'] ?? false),
        ]);
    }

    /** Vuelve a encolar la generación del tema. */
    public function portadaReintentar(Comercio $comercio)
    {
        $this->autorizarComercio($comercio);

        if (blank($comercio->portada_url)) {
            return response()->json(['ok' => false, 'motivo' => 'sin_portada'], 422);
        }

        $comercio->update(['portada_estado' => 'procesando']);
        GenerateBrandThemeJob::dispatch($comercio);

        return response()->json(['ok' => true]);
    }

    /** Super admin con cualquiera; el resto, sólo con lo suyo. */
    private function autorizarComercio(Comercio $comercio): void
    {
        $user = auth()->user();

        if (! $user->isSuperAdmin() && $comercio->user_id !== $user->id) {
            abort(403, 'No tienes permiso para acceder a este comercio.');
        }
    }

    /**
     * Guarda la portada tal cual la subieron y deja el comercio "procesando".
     *
     * A diferencia del logo, acá no se recorta ni se cuadra nada: el recorte
     * lo decide después BrandThemeService, que necesita la imagen entera para
     * medir el promedio y sacar el hero en 16:11.
     */
    private function guardarPortada(Comercio $comercio, \Illuminate\Http\UploadedFile $file): void
    {
        $this->createComercioDirectories($comercio->id);
        $this->borrarDerivadosPortada($comercio);

        $comercio->portada_url = $file->store("comercios/{$comercio->id}/portada", 'public');
        $comercio->portada_estado = 'procesando';
    }

    /** Borra la portada y todo lo derivado de ella. */
    private function limpiarPortada(Comercio $comercio): void
    {
        $this->borrarDerivadosPortada($comercio);
        $this->deleteLogoSafely($comercio->portada_url);

        $comercio->portada_url = null;
        $comercio->tema_derivado = null;
        $comercio->portada_estado = 'vacio';
    }

    /** Borra sólo los archivos generados, no el original. */
    private function borrarDerivadosPortada(Comercio $comercio): void
    {
        foreach (['portada_fondo_url', 'portada_hero_url', 'portada_thumb_url'] as $campo) {
            $this->deleteLogoSafely($comercio->{$campo});
            $comercio->{$campo} = null;
        }
    }

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
        $domainsDir = $homeDir.'/domains';

        // Buscar el dominio correcto
        if (is_dir($domainsDir)) {
            $domains = scandir($domainsDir);
            foreach ($domains as $domain) {
                if ($domain === '.' || $domain === '..') {
                    continue;
                }
                $publicHtmlFile = $domainsDir.'/'.$domain.'/public_html/storage/'.$logoUrl;
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
        if (! $user->isSuperAdmin() && $comercio->user_id !== $user->id) {
            abort(403, 'No tienes permiso para eliminar este comercio.');
        }

        // Eliminar logo del storage si existe
        $this->deleteLogoSafely($comercio->logo_url);

        // Eliminar el comercio (esto también eliminará los menús asociados por la relación)
        $comercio->delete();

        return redirect()->route('comercios.index')->with('success', 'Comercio eliminado exitosamente');
    }
}
