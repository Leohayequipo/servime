<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'username',
        'email',
        'whatsapp',
        'password',
        'role',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function comercios()
    {
        return $this->hasMany(Comercio::class);
    }

    /**
     * Send the password reset notification.
     */
    public function sendPasswordResetNotification($token)
    {
        $this->notify(new ResetPasswordNotification($token));
    }

    /**
     * Genera un nombre de usuario libre a partir del email (o del nombre si no hay email).
     *
     * "juan.perez@gmail.com" → "juan.perez", y si ya existe, "juan.perez2".
     */
    public static function generarUsername(?string $email, ?string $nombre = null): string
    {
        $base = Str::slug(Str::before((string) $email, '@'), '.');
        if ($base === '') {
            $base = Str::slug((string) $nombre, '.');
        }
        $base = Str::limit($base, 40, '') ?: 'usuario';

        $candidato = $base;
        $n = 1;
        while (static::where('username', $candidato)->exists()) {
            $candidato = $base.(++$n);
        }

        return $candidato;
    }

    /**
     * Contraseña inicial legible, para que el admin se la pueda pasar por teléfono.
     *
     * Se evitan los caracteres que se confunden al dictar o al copiar a mano
     * (O/0, I/l/1) y se mezclan mayúscula, minúscula y dígitos para que pase
     * cualquier política razonable.
     */
    public static function generarPasswordInicial(): string
    {
        $consonantes = 'bcdfghjkmnpqrstvwxyz';
        $vocales = 'aeuy';

        $silaba = fn () => $consonantes[random_int(0, strlen($consonantes) - 1)]
            .$vocales[random_int(0, strlen($vocales) - 1)];

        return ucfirst($silaba().$silaba()).'-'.$silaba().$silaba().'-'.random_int(23, 99);
    }

    /**
     * Verificar si el usuario es Super Administrador
     */
    public function isSuperAdmin(): bool
    {
        return $this->role === 'super_admin';
    }

    /**
     * Verificar si el usuario es usuario común
     */
    public function isRegularUser(): bool
    {
        return $this->role === 'user';
    }

    /**
     * Obtener el comercio del usuario (solo para usuarios comunes)
     */
    public function getComercio()
    {
        if ($this->isRegularUser()) {
            return $this->comercios()->first();
        }

        return null;
    }
}
