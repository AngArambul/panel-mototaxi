<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens;

    // 1. Configurarmos el nombre de la tabla
    protected $table = 'usuarios';

    // 2. Configurarmos la clave primaria y su tipo
    protected $primaryKey = 'id_usuario';
    public $incrementing = false;
    protected $keyType = 'string';

    // 3. Desactivamos las marcas de tiempo si no las estás utilizando
    public $timestamps = false;

    // 4. Configuramos los campos que se pueden asignar masivamente
    protected $fillable = [
        'name',
        'id_usuario',
        'nombre',
        'email',
        'password',
        'telefono',
        'rol',
    ];

    protected static function booted(): void
    {
        static::creating(function (User $user) {
            $user->id_usuario ??= (string) Str::uuid();
            $user->rol ??= 'PASAJERO';
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
    ];
    // 5. Configuramos los casts de los atributos
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'email_verified_at' => 'datetime',
        ];
    }
}
