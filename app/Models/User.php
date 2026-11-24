<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens;

    public const ADMINISTRATOR = 'ADMINISTRADOR';
    public const DRIVER = 'CONDUCTOR';
    public const PASSENGER = 'PASAJERO';
    public const MANAGED_ROLES = [self::PASSENGER, self::DRIVER];
    public const AUTH_VERSION_SESSION_KEY = 'account.auth_version';

    protected $table = 'usuarios';

    protected $primaryKey = 'id_usuario';
    public $incrementing = false;
    protected $keyType = 'string';

    public $timestamps = false;

    protected $attributes = ['activo' => true, 'rol' => self::PASSENGER];

    protected $fillable = [
        'name',
        'nombre',
        'email',
        'password',
        'telefono',
    ];

    protected static function booted(): void
    {
        static::creating(function (User $user) {
            $user->id_usuario ??= (string) Str::uuid();
        });
    }

    protected function name(): Attribute
    {
        return Attribute::make(get: fn ($value, $attributes) => $attributes['nombre'] ?? '',
            set: fn ($value) => ['nombre' => $value]);
    }

    public function getIdAttribute(): ?string
    {
        return $this->attributes['id_usuario'] ?? null;
    }

    protected $hidden = [
        'password',
        'remember_token',
        'auth_version',
    ];

    public function isAdministrator(): bool
    {
        return $this->rol === self::ADMINISTRATOR;
    }

    public function isActive(): bool
    {
        return $this->activo === true;
    }

    public function revokeAccess(): void
    {
        $previousEmail = $this->getRawOriginal('email');
        $this->auth_version = (string) Str::uuid();
        $this->setRememberToken(Str::random(60));
        $this->save();
        $this->tokens()->delete();

        if ($previousEmail !== null) {
            DB::table(config('auth.passwords.users.table'))->where('email', $previousEmail)->delete();
        }
    }

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'email_verified_at' => 'datetime',
            'activo' => 'boolean',
        ];
    }
}
