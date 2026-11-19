<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Database\Factories\MaterialFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class Material extends Model
{
    /** @use HasFactory<MaterialFactory> */
    use Auditable, HasFactory;

    public const ENTIDAD = 'Material';

    protected $table = 'mglpbc_material';

    protected $primaryKey = 'id_material';

    public $timestamps = false;

    protected $fillable = [
        'id_proyecto',
        'id_gasto',
        'id_item_presupuesto',
        'nombre_material',
        'unidad_material',
        'stock_actual',
        'stock_inicial',
        'stock_minimo',
        'costo_estimado_material',
        'estado_material',
    ];

    protected function casts(): array
    {
        return [
            'id_proyecto' => 'integer',
            'id_gasto' => 'integer',
            'id_item_presupuesto' => 'integer',
            'stock_actual' => 'decimal:2',
            'stock_inicial' => 'decimal:2',
            'stock_minimo' => 'decimal:2',
            'costo_estimado_material' => 'decimal:2',
            'estado_material' => 'integer',
        ];
    }

    public function proyecto(): BelongsTo
    {
        return $this->belongsTo(Proyecto::class, 'id_proyecto', 'id_proyecto');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(ItemPresupuesto::class, 'id_item_presupuesto', 'id_item_presupuesto');
    }

    public function movimientos(): HasMany
    {
        return $this->hasMany(MovimientoInventario::class, 'id_material', 'id_material');
    }

    /**
     * Suma al inventario el material de un gasto aprobado, como una ENTRADA en el historial.
     *
     * Si el gasto es de un ítem del presupuesto, el material es el de ese ítem; si es un gasto
     * adicional, se busca un material activo con el mismo nombre. Si no existe, se crea.
     */
    public static function registrarDesdeGasto(Gasto $gasto, float $cantidad, string $unidad): self
    {
        $item = $gasto->item;

        $material = self::where('id_proyecto', $gasto->id_proyecto)
            ->where('estado_material', 1)
            ->when($item,
                fn ($q) => $q->where('id_item_presupuesto', $item->id_item_presupuesto),
                fn ($q) => $q->where('nombre_material', $gasto->nombre_gasto))
            ->lockForUpdate()
            ->first();

        $material ??= self::create([
            'id_proyecto' => $gasto->id_proyecto,
            'id_gasto' => $gasto->id_gasto,
            'id_item_presupuesto' => $item?->id_item_presupuesto,
            'nombre_material' => $item?->descripcion_item ?? $gasto->nombre_gasto,
            'unidad_material' => $unidad,
            'stock_actual' => 0,
            'stock_inicial' => 0,
            // Por defecto se avisa cuando queda un cuarto de lo comprado; el administrador puede ajustarlo
            'stock_minimo' => round($cantidad * 0.25, 2),
            'costo_estimado_material' => 0,
            'estado_material' => 1,
        ]);

        $material->increment('costo_estimado_material', (float) $gasto->monto_gasto);
        $material->registrarMovimiento(
            MovimientoInventario::ENTRADA,
            $cantidad,
            $gasto->fecha_gasto->toDateString(),
            'Gasto #'.$gasto->id_gasto.': '.$gasto->nombre_gasto,
        );

        return $material->refresh();
    }

    /**
     * Registra un conteo físico (HU14): el stock pasa a ser lo contado y la diferencia queda como
     * un movimiento AJUSTE (negativo si falta material, positivo si sobra). Si coincide, no registra nada.
     */
    public function registrarConteo(float $contado, string $fecha, ?string $descripcion): ?MovimientoInventario
    {
        return DB::transaction(function () use ($contado, $fecha, $descripcion) {
            $material = self::whereKey($this->id_material)->lockForUpdate()->firstOrFail();
            $diferencia = round($contado - (float) $material->stock_actual, 2);

            if ($diferencia == 0) {
                return null;
            }

            $material->stock_actual = $contado;
            $material->save();

            return $material->movimientos()->create([
                'id_proyecto' => $material->id_proyecto,
                'tipo_movimiento' => MovimientoInventario::AJUSTE,
                'cantidad_movimiento' => $diferencia,
                'fecha_movimiento' => $fecha,
                'descripcion_movimiento' => $descripcion ?: ($diferencia < 0 ? 'Conteo físico: falta material' : 'Conteo físico: sobra material'),
            ]);
        });
    }

    /**
     * Registra una entrada o salida y actualiza el stock en la misma transacción.
     *
     * @throws ValidationException si una salida supera el stock disponible
     */
    public function registrarMovimiento(string $tipo, float $cantidad, string $fecha, ?string $descripcion): MovimientoInventario
    {
        return DB::transaction(function () use ($tipo, $cantidad, $fecha, $descripcion) {
            $material = self::whereKey($this->id_material)->lockForUpdate()->firstOrFail();

            if ($tipo === MovimientoInventario::SALIDA && $cantidad > (float) $material->stock_actual) {
                throw ValidationException::withMessages([
                    'cantidad_movimiento' => 'Stock insuficiente para registrar la salida.',
                ]);
            }

            if ($tipo === MovimientoInventario::SALIDA) {
                $material->stock_actual -= $cantidad;
            } else {
                $material->stock_actual += $cantidad;
                $material->stock_inicial = max((float) $material->stock_inicial, (float) $material->stock_actual);
            }

            $material->save();

            return $material->movimientos()->create([
                'id_proyecto' => $material->id_proyecto,
                'tipo_movimiento' => $tipo,
                'cantidad_movimiento' => $cantidad,
                'fecha_movimiento' => $fecha,
                'descripcion_movimiento' => $descripcion,
            ]);
        });
    }

    /**
     * Porcentaje de stock restante respecto al stock inicial, entre 0 y 100.
     */
    public function porcentajeStock(): int
    {
        $inicial = (float) $this->stock_inicial;

        if ($inicial <= 0) {
            return 0;
        }

        return (int) max(0, min(100, round(((float) $this->stock_actual / $inicial) * 100)));
    }

    public function bajoStockMinimo(): bool
    {
        $actual = (float) $this->stock_actual;

        return $actual <= 0 || $actual <= (float) $this->stock_minimo;
    }

    public function nivelStock(): array
    {
        if ($this->bajoStockMinimo()) {
            return ['texto' => 'Bajo', 'clase' => 'danger'];
        }

        if ($this->porcentajeStock() <= 60) {
            return ['texto' => 'Intermedio', 'clase' => 'warn'];
        }

        return ['texto' => 'Alto', 'clase' => 'success'];
    }

    public function descripcionAuditoria(): string
    {
        return 'Material "'.$this->nombre_material.'"';
    }
}
