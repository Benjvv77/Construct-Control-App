<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Auditoria extends Model
{
    public const ACCIONES = [
        'creado' => 'Creó',
        'modificado' => 'Modificó',
        'eliminado' => 'Eliminó',
        'aprobado' => 'Aprobó',
        'rechazado' => 'Rechazó',
    ];

    protected $table = 'mglpbc_auditoria';

    protected $primaryKey = 'id_auditoria';

    public $timestamps = false;

    protected $fillable = [
        'id_usuario',
        'accion',
        'entidad',
        'id_registro',
        'id_proyecto',
        'descripcion',
        'cambios',
        'ip',
        'fecha',
    ];

    protected function casts(): array
    {
        return [
            'cambios' => 'array',
            'fecha' => 'datetime',
        ];
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'id_usuario', 'id_usuario');
    }

    public function accionTexto(): string
    {
        return self::ACCIONES[$this->accion] ?? ucfirst($this->accion);
    }
}
