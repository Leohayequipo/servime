<?php

namespace App\Jobs;

use App\Models\Comercio;
use App\Services\IntentClassifierService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ClassifyIntentsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 300;

    public function __construct(public Comercio $comercio) {}

    public function handle(IntentClassifierService $classifier): void
    {
        $classifier->classifyPending($this->comercio);
    }
}
