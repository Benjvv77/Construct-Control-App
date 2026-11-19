<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;

/**
 * Punto de control de la Curva S: cuánto se planificaba tener gastado a una fecha
 * y cuánto se había gastado realmente al registrarlo.
 */
class EvaluacionCurvaS extends Model
{
    use Auditable;

    public const ENTIDAD = 'Curva S';

    private ?float $realCalculado = null;

    protected $table = 'mglpbc_curva_s';

    protected $primaryKey = 'id_curva';

    public $timestamps = false;

    protected $fillable = [
        'id_proyecto',
        'fecha_evaluacion',
        'monto_planificado_acumulado',
        'monto_real_acumulado',
    ];

    protected function casts(): array
    {
        return [
            'id_proyecto' => 'integer',
            'fecha_evaluacion' => 'date:Y-m-d',
            'monto_planificado_acumulado' => 'decimal:2',
            'monto_real_acumulado' => 'decimal:2',
        ];
    }

    /**
     * Gasto real acumulado a la fecha del punto, calculado con los gastos actuales
     * (incluye gastos registrados después con fecha anterior). Null si la fecha aún no llega.
     */
    public function realAcumulado(): ?float
    {
        if ($this->fecha_evaluacion->isFuture()) {
            return null;
        }

        return $this->realCalculado ??= (float) Gasto::where('id_proyecto', $this->id_proyecto)
            ->whereDate('fecha_gasto', '<=', $this->fecha_evaluacion)
            ->sum('monto_gasto');
    }

    public function desviacion(): ?float
    {
        $real = $this->realAcumulado();

        return $real === null ? null : $real - (float) $this->monto_planificado_acumulado;
    }

    public function descripcionAuditoria(): string
    {
        return 'Punto de la Curva S del '.$this->fecha_evaluacion?->format('d/m/Y');
    }
}
