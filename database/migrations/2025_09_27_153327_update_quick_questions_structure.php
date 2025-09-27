<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Migrar datos existentes al nuevo formato
        DB::table('comercios')->whereNotNull('quick_questions')->get()->each(function ($comercio) {
            $oldQuestions = json_decode($comercio->quick_questions, true);
            $newQuestions = [];
            
            foreach ($oldQuestions as $question) {
                $newQuestions[] = [
                    'icon' => $question['icon'] ?? '❓',
                    'question' => $question['question'] ?? '',
                    'answer' => null, // Se generará después
                    'auto_generated' => true,
                    'category' => $this->detectCategory($question['question'] ?? '')
                ];
            }
            
            DB::table('comercios')
                ->where('id', $comercio->id)
                ->update(['quick_questions' => json_encode($newQuestions)]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revertir al formato anterior
        DB::table('comercios')->whereNotNull('quick_questions')->get()->each(function ($comercio) {
            $newQuestions = json_decode($comercio->quick_questions, true);
            $oldQuestions = [];
            
            foreach ($newQuestions as $question) {
                $oldQuestions[] = [
                    'icon' => $question['icon'],
                    'question' => $question['question']
                ];
            }
            
            DB::table('comercios')
                ->where('id', $comercio->id)
                ->update(['quick_questions' => json_encode($oldQuestions)]);
        });
    }

    /**
     * Detectar categoría basada en la pregunta
     */
    private function detectCategory($question)
    {
        $question = strtolower($question);
        
        if (strpos($question, 'menú') !== false || strpos($question, 'menu') !== false || strpos($question, 'comida') !== false) {
            return 'menu';
        }
        
        if (strpos($question, 'precio') !== false || strpos($question, 'cuesta') !== false || strpos($question, 'vale') !== false) {
            return 'precios';
        }
        
        if (strpos($question, 'horario') !== false || strpos($question, 'abierto') !== false || strpos($question, 'cierra') !== false) {
            return 'horarios';
        }
        
        if (strpos($question, 'ubicado') !== false || strpos($question, 'dirección') !== false || strpos($question, 'donde') !== false) {
            return 'ubicacion';
        }
        
        if (strpos($question, 'contacto') !== false || strpos($question, 'teléfono') !== false || strpos($question, 'llamar') !== false) {
            return 'contacto';
        }
        
        return 'general';
    }
};
