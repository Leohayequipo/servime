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
     * Cómo se presenta el comercio cuando nos escribe por WhatsApp o por mail.
     *
     * Los pedidos llegaban como "quiero pasar al plan Pro" desde un número desconocido:
     * sin el nombre, el mail y el comercio no había forma de saber qué cuenta tocar. Va el
     * plan actual también, que es lo primero que habría que ir a buscar al panel.
     *
     * @param  Comercio|null  $comercio  el del pedido; si no, se nombran todos los suyos
     */
    public function presentacionParaSoporte(?Comercio $comercio = null): string
    {
        $quien = "soy {$this->name} ({$this->email})";

        $comercios = $comercio ? collect([$comercio]) : $this->comercios;

        if ($comercios->isEmpty()) {
            return $quien;
        }

        $nombres = $comercios
            ->map(fn (Comercio $c) => $c->nombre.($c->planEfectivo() ? " (plan {$c->planEfectivo()->nombre})" : ''))
            ->join(', ', ' y ');

        return $comercios->count() === 1
            ? "{$quien}, de {$nombres}"
            : "{$quien}. Mis comercios: {$nombres}";
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
     * Contraseña con la que se crean las cuentas nuevas.
     *
     * Se configura con AUTH_PASSWORD_INICIAL en el .env (por defecto "admin"). Es un
     * método y no una constante porque las constantes no pueden leer configuración.
     *
     * ⚠️ Es la MISMA para todas las cuentas. Se eligió así para poder darle el acceso
     * a alguien por teléfono sin depender del mail de bienvenida. El costo es que,
     * mientras no la cambien, cualquiera que adivine el usuario (se deriva del email)
     * entra a esa cuenta.
     */
    public static function passwordInicial(): string
    {
        // Sin default acá a propósito: el valor por defecto vive en config/auth.php.
        // Duplicarlo hacía que los dos se desincronizaran al cambiar uno solo.
        return (string) config('auth.password_inicial');
    }

    /** Alias histórico de passwordInicial(). */
    public static function generarPasswordInicial(): string
    {
        return static::passwordInicial();
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
     * Los comercios del usuario común, para armarle la navegación.
     *
     * Devuelve una colección y no "el" comercio a propósito: un usuario puede tener
     * más de uno. El límite de uno solo aplica nada más al alta que hace el propio
     * usuario (ver ComercioController@create y @store); el super admin le puede
     * asignar los que quiera, y cuando lo hacía, el link "Menús" del nav —que salía
     * de un `comercios()->first()`— se quedaba clavado en el primero y a los demás
     * sólo se llegaba por Comercios → Ver menús.
     *
     * Para el super admin va vacía: su nav apunta a las pantallas de /admin, no a
     * un comercio propio.
     */
    public function misComercios()
    {
        return $this->isRegularUser() ? $this->comercios : collect();
    }
}
