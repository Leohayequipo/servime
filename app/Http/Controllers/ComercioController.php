<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Comercio;
use Illuminate\Support\Str;
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
        $comercio->slug = Str::slug($validated['nombre']);
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
            $comercio->logo_url = $this->saveSquareLogo($request->file('logo'), 512);
        }

        $comercio->save();

        return redirect()->route('comercios.index')->with('success', 'Comercio actualizado exitosamente');
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
