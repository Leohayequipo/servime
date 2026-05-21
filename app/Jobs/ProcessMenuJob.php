<?php

namespace App\Jobs;

use App\Models\Menu;
use App\Services\MenuProcessorService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ProcessMenuJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 700;
    public int $tries   = 2;

    public function __construct(public Menu $menu) {}

    public function handle(): void
    {
        (new MenuProcessorService())->processMenu($this->menu);
    }

    public function failed(\Throwable $e): void
    {
        Log::error("ProcessMenuJob: falló para menú #{$this->menu->id}: " . $e->getMessage());
        $this->menu->update(['procesado' => false]);
    }
}
