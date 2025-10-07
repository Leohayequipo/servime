<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\PdfProcessorService;

class TestPdfProcessor extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pdf:test {file_path : Ruta al archivo PDF para probar}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Probar el procesamiento de PDFs con extracción de texto';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $filePath = $this->argument('file_path');
        
        if (!file_exists($filePath)) {
            $this->error("El archivo no existe: {$filePath}");
            return 1;
        }

        $this->info("Probando procesamiento de PDF: {$filePath}");
        
        try {
            $processor = new PdfProcessorService();
            
            // Verificar si es PDF válido
            if (!$processor->isValidPdf($filePath)) {
                $this->error("El archivo no es un PDF válido");
                return 1;
            }
            
            $this->info("✓ PDF válido detectado");
            
            // Procesar PDF
            $result = $processor->processPdfToMenu($filePath);
            
            if ($result['success']) {
                $this->info("✓ PDF procesado exitosamente");
                $this->line("");
                $this->line("=== MENÚ EXTRAÍDO ===");
                $this->line($result['menu_data']);
                $this->line("");
                $this->line("=== TEXTO BRUTO EXTRAÍDO ===");
                $this->line(substr($result['extracted_text'], 0, 500) . "...");
            } else {
                $this->error("✗ Error procesando PDF: " . $result['message']);
                if (isset($result['suggestion'])) {
                    $this->line("Sugerencia: " . $result['suggestion']);
                }
                return 1;
            }
            
        } catch (\Exception $e) {
            $this->error("✗ Error: " . $e->getMessage());
            return 1;
        }
        
        return 0;
    }
}
