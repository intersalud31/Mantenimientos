<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * Tabla legacy de usuarios autenticables.
     *
     * @var string
     */
    protected $table = 'usuarios_sistema';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'nombre',
        'correo',
        'rol',
        'estado',
        'password_hash',
        'ultimo_acceso',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password_hash',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'estado' => 'integer',
        'email_verified_at' => 'datetime',
        'ultimo_acceso' => 'datetime',
    ];

    /**
     * Laravel Auth usa password para algunas operaciones internas; en la
     * tabla legacy el campo real es password_hash.
     */
    public function getAuthPassword()
    {
        return $this->password_hash;
    }

    public function getNameAttribute(): string
    {
        return (string) ($this->attributes['nombre'] ?? '');
    }

    public function getEmailAttribute(): string
    {
        return (string) ($this->attributes['correo'] ?? '');
    }
}
