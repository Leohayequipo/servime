<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chat_conversations', function (Blueprint $table) {
            $table->string('intent')->nullable()->after('used_openai');
            $table->string('intent_source', 10)->nullable()->after('intent'); // rule | ia
            $table->index(['comercio_id', 'intent']);
        });
    }

    public function down(): void
    {
        Schema::table('chat_conversations', function (Blueprint $table) {
            $table->dropIndex(['comercio_id', 'intent']);
            $table->dropColumn(['intent', 'intent_source']);
        });
    }
};
