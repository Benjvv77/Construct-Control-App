<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Database\Factories\ItemPresupuestoFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Partida del presupuesto de un proyecto (HU07): en qué se planea gastar y cuánto.
 */
class ItemPresupuesto extends Model
{
    /** @use HasFactory<ItemPresupuestoFactory> */
    use Auditable, HasFactory;

    public const ENTIDAD = 'Ítem de presupuesto';

    protected $table = 'mglpbc_item_presupuesto';

    protected $primaryKey = 'id_item_presupuesto';

    public $timestamps = false;

    protected $fillable = [
        'id_proyecto',
        'id_tipo_gasto',
        'descripcion_item',
        'cantidad',
        'unidad_item',
        'precio_unitario',
    ];

    protected function casts(): array
    {
        return [
            'id_proyecto' => 'integer',
            'id_tipo_gasto' => 'integer',
            'cantidad' => 'decimal:2',
            'precio_unitario' => 'decimal:2',
        ];
    }

    /**
     * El presupuesto del proyecto es la suma de sus ítems: se recalcula con cada cambio.
     */
    protected static function booted(): void
    {
        static::saved(fn (ItemPresupuesto $item) => $item->proyecto->recalcularPresupuesto());
        static::deleted(fn (ItemPresupuesto $item) => $item->proyecto->recalcularPresupuesto());
    }

    public function proyecto(): BelongsTo
    {
        return $this->belongsTo(Proyecto::class, 'id_proyecto', 'id_proyecto');
    }

    public function tipoGasto(): BelongsTo
    {
        return $this->belongsTo(TipoGasto::class, 'id_tipo_gasto', 'id_tipo_gasto');
    }

    public function gastos(): HasMany
    {
        return $this->hasMany(Gasto::class, 'id_item_presupuesto', 'id_item_presupuesto');
    }

    public function materiales(): HasMany
    {
        return $this->hasMany(Material::class, 'id_item_presupuesto', 'id_item_presupuesto');
    }

    public function total(): float
    {
        return round((float) $this->cantidad * (float) $this->precio_unitario, 2);
    }

    public function esDeMateriales(): bool
    {
        return $this->id_tipo_gasto === TipoGasto::MATERIALES;
    }

    public function descripcionAuditoria(): string
    {
        return 'Ítem "'.$this->descripcion_item.'"';
    }
}
