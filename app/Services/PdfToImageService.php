<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class PdfToImageService
{
    /**
     * Convierte un PDF a imagen (primera página)
     */
    public function convertPdfToImage(string $pdfPath): ?string
    {
        try {
            // Verificar que el archivo existe
            if (!file_exists($pdfPath)) {
                Log::error('PDF no encontrado: ' . $pdfPath);
                return null;
            }

            // Generar nombre único para la imagen
            $imageName = 'pdf_converted_' . uniqid() . '.png';
            $imagePath = storage_path('app/temp/' . $imageName);
            
            // Crear directorio temp si no existe
            if (!is_dir(storage_path('app/temp'))) {
                mkdir(storage_path('app/temp'), 0755, true);
            }

            // Intentar conversión con ImageMagick (si está disponible)
            if ($this->isImageMagickAvailable()) {
                return $this->convertWithImageMagick($pdfPath, $imagePath);
            }

            // Fallback: intentar con Ghostscript
            if ($this->isGhostscriptAvailable()) {
                return $this->convertWithGhostscript($pdfPath, $imagePath);
            }

            // SOLUCIÓN ALTERNATIVA: Informar al usuario sobre la limitación
            Log::warning('No hay herramientas de conversión PDF disponibles. PDFs escaneados no pueden ser procesados.');
            throw new \Exception('PDFs escaneados requieren conversión a imagen. Por favor, convierte el PDF a imagen (PNG/JPG) y súbelo como imagen.');

        } catch (\Exception $e) {
            Log::error('Error convirtiendo PDF a imagen: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Verificar si ImageMagick está disponible
     */
    private function isImageMagickAvailable(): bool
    {
        try {
            return class_exists('Imagick');
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Verificar si Ghostscript está disponible
     */
    private function isGhostscriptAvailable(): bool
    {
        $output = [];
        $returnCode = 0;
        exec('gs --version 2>&1', $output, $returnCode);
        return $returnCode === 0;
    }

    /**
     * Convertir PDF a imagen usando ImageMagick
     */
    private function convertWithImageMagick(string $pdfPath, string $imagePath): ?string
    {
        try {
            $imagick = new \Imagick();
            
            // Configurar para evitar problemas con Ghostscript
            $imagick->setResolution(150, 150); // Resolución para mejor calidad
            
            // Intentar leer el PDF
            try {
                $imagick->readImage($pdfPath . '[0]'); // Solo la primera página
            } catch (\Exception $readError) {
                Log::error('ImageMagick requiere Ghostscript para procesar PDFs: ' . $readError->getMessage());
                
                // Si falla, retornar null para que intente el siguiente método
                $imagick->clear();
                $imagick->destroy();
                return null;
            }
            
            $imagick->setImageFormat('png');
            $imagick->setImageCompressionQuality(90);
            $imagick->writeImage($imagePath);
            $imagick->clear();
            $imagick->destroy();

            if (file_exists($imagePath) && filesize($imagePath) > 0) {
                Log::info('PDF convertido exitosamente con ImageMagick: ' . $imagePath);
                return $imagePath;
            } else {
                Log::error('ImageMagick no generó un archivo válido');
                return null;
            }

        } catch (\Exception $e) {
            Log::error('Error con ImageMagick: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Convertir PDF a imagen usando Ghostscript
     */
    private function convertWithGhostscript(string $pdfPath, string $imagePath): ?string
    {
        try {
            $command = sprintf(
                'gs -dNOPAUSE -dBATCH -sDEVICE=png16m -r150 -dFirstPage=1 -dLastPage=1 -sOutputFile=%s %s 2>&1',
                escapeshellarg($imagePath),
                escapeshellarg($pdfPath)
            );

            $output = [];
            $returnCode = 0;
            exec($command, $output, $returnCode);

            if ($returnCode === 0 && file_exists($imagePath)) {
                Log::info('PDF convertido exitosamente con Ghostscript: ' . $imagePath);
                return $imagePath;
            } else {
                Log::error('Error con Ghostscript: ' . implode(' ', $output));
                return null;
            }

        } catch (\Exception $e) {
            Log::error('Error con Ghostscript: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Procesar PDF directamente como imagen (sin conversión)
     */
    private function processPdfAsImage(string $pdfPath): ?string
    {
        try {
            // Leer el PDF como datos binarios
            $pdfData = file_get_contents($pdfPath);
            $base64Pdf = base64_encode($pdfData);
            
            // Crear un archivo temporal con extensión .pdf pero que funcione como imagen
            $tempImagePath = storage_path('app/temp/pdf_as_image_' . uniqid() . '.pdf');
            file_put_contents($tempImagePath, $pdfData);
            
            Log::info('PDF preparado para procesamiento directo: ' . $tempImagePath);
            return $tempImagePath;
            
        } catch (\Exception $e) {
            Log::error('Error procesando PDF como imagen: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Limpiar archivos temporales
     */
    public function cleanupTempFile(string $filePath): void
    {
        try {
            if (file_exists($filePath)) {
                unlink($filePath);
                Log::info('Archivo temporal eliminado: ' . $filePath);
            }
        } catch (\Exception $e) {
            Log::warning('No se pudo eliminar archivo temporal: ' . $filePath);
        }
    }
}
