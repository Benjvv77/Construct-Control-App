<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Carbon\CarbonInterface;
use Database\Factories\ProyectoFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class Proyecto extends Model
{
    /** @use HasFactory<ProyectoFactory> */
    use Auditable, HasFactory;

    public const ENTIDAD = 'Proyecto';

    public const ESTADOS = [
        1 => ['texto' => 'Activo', 'clase' => 'activo'],
        2 => ['texto' => 'Alerta', 'clase' => 'alerta'],
        3 => ['texto' => 'Pausado', 'clase' => 'pausado'],
        4 => ['texto' => 'Finalizado', 'clase' => 'finalizado'],
    ];

    public const ESTADO_ACTIVO = 1;

    public const ESTADO_ALERTA = 2;

    // Porcentaje del presupuesto gastado a partir del cual el proyecto queda en alerta
    public const UMBRAL_ALERTA = 90;

    // Filtros de la lista de proyectos => estado que representan
    public const FILTROS = [
        'activos' => 1,
        'alertas' => 2,
        'pausados' => 3,
        'finalizados' => 4,
    ];

    protected $table = 'mglpbc_proyecto';

    protected $primaryKey = 'id_proyecto';

    public $timestamps = false;

    protected $fillable = [
        'id_usuario',
        'nombre_proyecto',
        'descripcion_proyecto',
        'fecha_inicio_proyecto',
        'fecha_termino_proyecto',
        'estado_proyecto',
        'presupuesto_proyecto',
    ];

    protected function casts(): array
    {
        return [
            'id_usuario' => 'integer',
            'fecha_inicio_proyecto' => 'date:Y-m-d',
            'fecha_termino_proyecto' => 'date:Y-m-d',
            'estado_proyecto' => 'integer',
            'presupuesto_proyecto' => 'decimal:2',
        ];
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'id_usuario', 'id_usuario');
    }

    public function gastos(): HasMany
    {
        return $this->hasMany(Gasto::class, 'id_proyecto', 'id_proyecto');
    }

    public function materiales(): HasMany
    {
        return $this->hasMany(Material::class, 'id_proyecto', 'id_proyecto');
    }

    public function movimientos(): HasMany
    {
        return $this->hasMany(MovimientoInventario::class, 'id_proyecto', 'id_proyecto');
    }

    public function evaluacionesCurvaS(): HasMany
    {
        return $this->hasMany(EvaluacionCurvaS::class, 'id_proyecto', 'id_proyecto');
    }

    public function itemsPresupuesto(): HasMany
    {
        return $this->hasMany(ItemPresupuesto::class, 'id_proyecto', 'id_proyecto');
    }

    /**
     * Proyectos que el usuario puede ver: todos si es administrador, si no solo los suyos.
     */
    public function scopeVisiblesPara(Builder $query, Usuario $usuario): void
    {
        if (! $usuario->esAdministrador()) {
            $query->where('id_usuario', $usuario->id_usuario);
        }
    }

    /**
     * Proyectos en alerta: marcados a mano como "Alerta" o que ya gastaron el umbral del presupuesto.
     */
    public function scopeConAlerta(Builder $query): void
    {
        $query->where(fn (Builder $q) => $q
            ->where('estado_proyecto', self::ESTADO_ALERTA)
            ->orWhereRaw(
                "(SELECT COALESCE(SUM(g.monto_gasto), 0) FROM mglpbc_gastos g WHERE g.id_proyecto = mglpbc_proyecto.id_proyecto AND g.estado_gasto = 'aprobado') >= presupuesto_proyecto * ?",
                [self::UMBRAL_ALERTA / 100]
            ));
    }

    public function scopeBuscar(Builder $query, ?string $texto): void
    {
        if (blank($texto)) {
            return;
        }

        $patron = '%'.addcslashes($texto, '%_\\').'%';

        $query->whereAny(['nombre_proyecto', 'descripcion_proyecto'], 'ilike', $patron);
    }

    public function estadoTexto(): string
    {
        return self::ESTADOS[$this->estado_proyecto]['texto'] ?? 'Activo';
    }

    public function estadoClase(): string
    {
        return self::ESTADOS[$this->estado_proyecto]['clase'] ?? 'activo';
    }

    /**
     * El presupuesto es la suma de los ítems (HU07). Se guarda en presupuesto_proyecto para poder
     * ordenar, filtrar y calcular alertas en SQL sin sumar los ítems en cada consulta.
     */
    public function recalcularPresupuesto(): void
    {
        $this->update([
            'presupuesto_proyecto' => (float) $this->itemsPresupuesto()->sum(DB::raw('cantidad * precio_unitario')),
        ]);
    }

    /** Solo los gastos aprobados cuentan como gastados (HU10). */
    public function totalGastado(): float
    {
        return (float) $this->gastos()->aprobados()->sum('monto_gasto');
    }

    public function gastadoHasta(CarbonInterface $fecha): float
    {
        return (float) $this->gastos()->aprobados()->whereDate('fecha_gasto', '<=', $fecha)->sum('monto_gasto');
    }

    public function porcentajeUsado(?float $gastado = null): float
    {
        $presupuesto = (float) $this->presupuesto_proyecto;
        $gastado ??= $this->totalGastado();

        return $presupuesto > 0 ? ($gastado / $presupuesto) * 100 : 0;
    }

    /**
     * Alerta de desviación presupuestaria: 'excedido' sobre el 100%, 'critico' desde el umbral, o null.
     */
    public function alertaPresupuesto(?float $gastado = null): ?string
    {
        $porcentaje = $this->porcentajeUsado($gastado);

        return match (true) {
            $porcentaje > 100 => 'excedido',
            $porcentaje >= self::UMBRAL_ALERTA => 'critico',
            default => null,
        };
    }

    /**
     * Pasa un proyecto activo a "Alerta" cuando sus gastos alcanzan el umbral.
     * No lo devuelve a "Activo" solo: ese cambio queda en manos del responsable.
     */
    public function actualizarAlertaAutomatica(): bool
    {
        if ($this->estado_proyecto !== self::ESTADO_ACTIVO || $this->alertaPresupuesto() === null) {
            return false;
        }

        $this->update(['estado_proyecto' => self::ESTADO_ALERTA]);

        return true;
    }

    public function totalExtra(): float
    {
        return (float) $this->gastos()->aprobados()->adicionales()->sum('monto_gasto');
    }

    public function descripcionAuditoria(): string
    {
        return 'Proyecto "'.$this->nombre_proyecto.'"';
    }
}
