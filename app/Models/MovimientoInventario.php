<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MovimientoInventario extends Model
{
    use Auditable;

    public const ENTIDAD = 'Movimiento de inventario';

    public const ENTRADA = 'ENTRADA';

    public const SALIDA = 'SALIDA';

    // Diferencia de un conteo físico: negativa si falta material, positiva si sobra
    public const AJUSTE = 'AJUSTE';

    protected $table = 'mglpbc_movimiento_inventario';

    protected $primaryKey = 'id_movimiento';

    public $timestamps = false;

    protected $fillable = [
        'id_proyecto',
        'id_material',
        'tipo_movimiento',
        'cantidad_movimiento',
        'fecha_movimiento',
        'descripcion_movimiento',
    ];

    protected function casts(): array
    {
        return [
            'id_proyecto' => 'integer',
            'id_material' => 'integer',
            'cantidad_movimiento' => 'decimal:2',
            'fecha_movimiento' => 'date:Y-m-d',
        ];
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class, 'id_material', 'id_material');
    }

    public function descripcionAuditoria(): string
    {
        return $this->tipo_movimiento.' de '.numero($this->cantidad_movimiento).' en material #'.$this->id_material;
    }
}
