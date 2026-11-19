<?php

namespace App\Services;

use App\Models\Gasto;
use App\Models\ItemPresupuesto;
use App\Models\Proyecto;

/**
 * Comparativo entre lo presupuestado y lo gastado, ítem por ítem (HU11, HU16).
 * Solo cuentan los gastos aprobados; los pendientes se muestran aparte.
 */
class ResumenPresupuesto
{
    public const RIESGO = 'riesgo';

    public const SOBRECOSTO = 'sobrecosto';

    public const ESTADOS = [
        'ok' => ['texto' => 'En presupuesto', 'clase' => 'activo'],
        self::RIESGO => ['texto' => 'En riesgo', 'clase' => 'alerta'],
        self::SOBRECOSTO => ['texto' => 'Sobrecosto', 'clase' => 'pausado'],
    ];

    /**
     * @return array{filas: list<array>, adicionales: array, sin_item: array, presupuesto: float, gastado: float,
     *               pendiente: float, desviacion: float, porcentaje: float, estado: string, items_con_problemas: int}
     */
    public function calcular(Proyecto $proyecto): array
    {
        $items = $proyecto->itemsPresupuesto()
            ->with('tipoGasto')
            ->withSum(['gastos as gastado' => fn ($q) => $q->aprobados()], 'monto_gasto')
            ->withSum(['gastos as pendiente' => fn ($q) => $q->pendientes()], 'monto_gasto')
            ->orderBy('id_item_presupuesto')
            ->get();

        $filas = $items->map(fn (ItemPresupuesto $item) => [
            'item' => $item,
            ...$this->comparar($item->total(), (float) $item->gastado, (float) $item->pendiente),
        ])->all();

        $sinItem = fn (bool $adicional, string $estado) => (float) $proyecto->gastos()
            ->where('estado_gasto', $estado)
            ->whereNull('id_item_presupuesto')
            ->when($adicional, fn ($q) => $q->adicionales(), fn ($q) => $q->presupuestados())
            ->sum('monto_gasto');

        $adicionales = ['gastado' => $sinItem(true, Gasto::APROBADO), 'pendiente' => $sinItem(true, Gasto::PENDIENTE)];
        $huerfanos = ['gastado' => $sinItem(false, Gasto::APROBADO), 'pendiente' => $sinItem(false, Gasto::PENDIENTE)];

        $presupuesto = (float) $items->sum(fn ($i) => $i->total());
        $gastado = (float) $items->sum('gastado') + $adicionales['gastado'] + $huerfanos['gastado'];
        $pendiente = (float) $items->sum('pendiente') + $adicionales['pendiente'] + $huerfanos['pendiente'];

        return [
            'filas' => $filas,
            'adicionales' => $adicionales,
            'sin_item' => $huerfanos,
            'presupuesto' => $presupuesto,
            'gastado' => $gastado,
            'pendiente' => $pendiente,
            'items_con_problemas' => count(array_filter($filas, fn ($f) => $f['estado'] !== 'ok')),
            ...$this->comparar($presupuesto, $gastado, $pendiente),
        ];
    }

    /**
     * Desviación = gastado − presupuestado: positiva es sobrecosto, negativa es lo que queda disponible.
     */
    private function comparar(float $presupuestado, float $gastado, float $pendiente): array
    {
        $porcentaje = $presupuestado > 0 ? $gastado / $presupuestado * 100 : ($gastado > 0 ? 100.0 : 0.0);

        return [
            'presupuestado' => $presupuestado,
            'gastado' => $gastado,
            'pendiente' => $pendiente,
            'desviacion' => $gastado - $presupuestado,
            'porcentaje' => $porcentaje,
            'estado' => match (true) {
                $gastado > $presupuestado => self::SOBRECOSTO,
                $porcentaje >= Proyecto::UMBRAL_ALERTA => self::RIESGO,
                default => 'ok',
            },
        ];
    }
}
