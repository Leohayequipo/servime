<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class MakeSuperAdmin extends Command
{
    protected $signature = 'user:make-super-admin {email}';
    protected $description = 'Convertir un usuario a Super Administrador';

    public function handle()
    {
        $email = $this->argument('email');
        
        $user = User::where('email', $email)->first();
        
        if (!$user) {
            $this->error("Usuario con email '{$email}' no encontrado.");
            return 1;
        }
        
        $user->update(['role' => 'super_admin']);
        
        $this->info("Usuario '{$user->name}' ({$user->email}) convertido a Super Administrador exitosamente.");
        
        return 0;
    }
}