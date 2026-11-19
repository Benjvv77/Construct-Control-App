<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Database\Factories\GastoFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Un gasto es presupuestado (asociado a un ítem del presupuesto, HU08) o adicional (tipo de costo
 * "Extra", sin ítem, HU09). Solo los gastos aprobados afectan el presupuesto y el inventario (HU10).
 */
class Gasto extends Model
{
    /** @use HasFactory<GastoFactory> */
    use Auditable, HasFactory;

    public const ENTIDAD = 'Gasto';

    public const PENDIENTE = 'pendiente';

    public const APROBADO = 'aprobado';

    public const RECHAZADO = 'rechazado';

    public const ESTADOS = [
        self::PENDIENTE => ['texto' => 'Pendiente', 'clase' => 'alerta'],
        self::APROBADO => ['texto' => 'Aprobado', 'clase' => 'activo'],
        self::RECHAZADO => ['texto' => 'Rechazado', 'clase' => 'pausado'],
    ];

    protected $table = 'mglpbc_gastos';

    protected $primaryKey = 'id_gasto';

    public $timestamps = false;

    protected $fillable = [
        'id_proyecto',
        'id_item_presupuesto',
        'id_tipo_costo',
        'id_tipo_gasto',
        'nombre_gasto',
        'descripcion_gasto',
        'monto_gasto',
        'fecha_gasto',
        'documento_gasto',
        'cantidad_gasto',
        'unidad_gasto',
        'estado_gasto',
        'id_usuario_registro',
        'id_usuario_revisor',
        'fecha_revision',
        'motivo_rechazo',
    ];

    protected function casts(): array
    {
        return [
            'id_proyecto' => 'integer',
            'id_item_presupuesto' => 'integer',
            'id_tipo_costo' => 'integer',
            'id_tipo_gasto' => 'integer',
            'id_usuario_registro' => 'integer',
            'monto_gasto' => 'decimal:2',
            'fecha_gasto' => 'date:Y-m-d',
            'cantidad_gasto' => 'decimal:2',
            'fecha_revision' => 'datetime',
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

    public function tipoGasto(): BelongsTo
    {
        return $this->belongsTo(TipoGasto::class, 'id_tipo_gasto', 'id_tipo_gasto');
    }

    public function tipoCosto(): BelongsTo
    {
        return $this->belongsTo(TipoCosto::class, 'id_tipo_costo', 'id_tipo_costo');
    }

    public function registradoPor(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'id_usuario_registro', 'id_usuario');
    }

    public function revisadoPor(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'id_usuario_revisor', 'id_usuario');
    }

    /** Los únicos gastos que cuentan en totales, reportes, alertas e inventario. */
    public function scopeAprobados(Builder $query): void
    {
        $query->where('estado_gasto', self::APROBADO);
    }

    public function scopePendientes(Builder $query): void
    {
        $query->where('estado_gasto', self::PENDIENTE);
    }

    /** Gastos adicionales: no contemplados en el presupuesto (HU09). */
    public function scopeAdicionales(Builder $query): void
    {
        $query->where('id_tipo_costo', TipoCosto::ADICIONAL);
    }

    public function scopePresupuestados(Builder $query): void
    {
        $query->where(fn (Builder $q) => $q->whereNull('id_tipo_costo')->orWhere('id_tipo_costo', '<>', TipoCosto::ADICIONAL));
    }

    public function esExtra(): bool
    {
        return $this->id_tipo_costo === TipoCosto::ADICIONAL;
    }

    public function esDeMateriales(): bool
    {
        return $this->id_tipo_gasto === TipoGasto::MATERIALES;
    }

    public function estaPendiente(): bool
    {
        return $this->estado_gasto === self::PENDIENTE;
    }

    public function estaAprobado(): bool
    {
        return $this->estado_gasto === self::APROBADO;
    }

    public function estadoTexto(): string
    {
        return self::ESTADOS[$this->estado_gasto]['texto'];
    }

    public function estadoClase(): string
    {
        return self::ESTADOS[$this->estado_gasto]['clase'];
    }

    /**
     * Quién puede modificar o eliminar el gasto: el administrador siempre; quien lo registró,
     * mientras no esté aprobado (si lo rechazaron, puede corregirlo y vuelve a quedar pendiente).
     */
    public function puedeModificar(Usuario $usuario): bool
    {
        return $usuario->esAdministrador()
            || (! $this->estaAprobado() && $this->id_usuario_registro === $usuario->id_usuario);
    }

    public function descripcionAuditoria(): string
    {
        return 'Gasto "'.$this->nombre_gasto.'" por '.pesos($this->monto_gasto);
    }

    public function accionAuditoria(array $cambios): string
    {
        return in_array($cambios['estado_gasto'] ?? null, [self::APROBADO, self::RECHAZADO], true)
            ? $cambios['estado_gasto']
            : 'modificado';
    }
}
