<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Database\Factories\UsuarioFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Usuario extends Authenticatable
{
    /** @use HasFactory<UsuarioFactory> */
    use Auditable, HasFactory, Notifiable;

    public const ENTIDAD = 'Usuario';

    public const ROL_ADMINISTRADOR = 'Administrador';

    public const ROLES = [
        self::ROL_ADMINISTRADOR,
        'Supervisor',
    ];

    protected $table = 'mglpbc_usuario';

    protected $primaryKey = 'id_usuario';

    public $timestamps = false;

    protected $authPasswordName = 'password_usuario';

    protected $fillable = [
        'nombre_usuario',
        'apellido_paterno',
        'apellido_materno',
        'correo_usuario',
        'password_usuario',
        'rol_usuario',
        'estado_usuario',
    ];

    protected $hidden = [
        'password_usuario',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password_usuario' => 'hashed',
            'estado_usuario' => 'integer',
        ];
    }

    public function proyectos(): HasMany
    {
        return $this->hasMany(Proyecto::class, 'id_usuario', 'id_usuario');
    }

    public function getEmailForPasswordReset(): string
    {
        return $this->correo_usuario;
    }

    public function routeNotificationForMail(): string
    {
        return $this->correo_usuario;
    }

    public function scopeActivos($query): void
    {
        $query->where('estado_usuario', 1);
    }

    public function esAdministrador(): bool
    {
        return $this->rol_usuario === self::ROL_ADMINISTRADOR;
    }

    public function nombreCompleto(): string
    {
        return trim($this->nombre_usuario.' '.$this->apellido_paterno);
    }

    public function descripcionAuditoria(): string
    {
        return 'Usuario '.$this->correo_usuario;
    }
}
