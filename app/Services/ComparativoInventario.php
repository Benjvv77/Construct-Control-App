<?php

namespace App\Services;

use App\Models\Material;
use App\Models\MovimientoInventario;
use App\Models\Proyecto;
use App\Models\TipoGasto;

/**
 * HU14: compara el inventario real con lo presupuestado, para detectar compras por sobre lo
 * planificado (posibles compras duplicadas) y pérdidas de material (faltantes en el conteo físico).
 */
class ComparativoInventario
{
    /**
     * @return list<array{nombre: string, unidad: string, presupuestado: ?float, comprado: float, consumido: float,
     *                    ajustes: float, stock: float, alertas: list<string>}>
     */
    public function calcular(Proyecto $proyecto): array
    {
        $totales = MovimientoInventario::where('id_proyecto', $proyecto->id_proyecto)
            ->selectRaw("id_material,
                SUM(CASE WHEN tipo_movimiento = 'ENTRADA' THEN cantidad_movimiento ELSE 0 END) AS comprado,
                SUM(CASE WHEN tipo_movimiento = 'SALIDA' THEN cantidad_movimiento ELSE 0 END) AS consumido,
                SUM(CASE WHEN tipo_movimiento = 'AJUSTE' THEN cantidad_movimiento ELSE 0 END) AS ajustes")
            ->groupBy('id_material')
            ->get()
            ->keyBy('id_material');

        $materiales = $proyecto->materiales()->where('estado_material', 1)->orderBy('nombre_material')->get();
        $filas = [];

        // Ítems de materiales del presupuesto, con lo que se compró y usó de cada uno
        $items = $proyecto->itemsPresupuesto()->where('id_tipo_gasto', TipoGasto::MATERIALES)->orderBy('descripcion_item')->get();

        foreach ($items as $item) {
            $propios = $materiales->where('id_item_presupuesto', $item->id_item_presupuesto);
            $filas[] = $this->fila(
                $item->descripcion_item,
                $item->unidad_item ?? $propios->first()?->unidad_material ?? '',
                (float) $item->cantidad,
                $propios,
                $totales,
            );
        }

        // Materiales que no están en el presupuesto
        foreach ($materiales->whereNull('id_item_presupuesto') as $material) {
            $filas[] = $this->fila($material->nombre_material, $material->unidad_material, null, collect([$material]), $totales);
        }

        return $filas;
    }

    private function fila(string $nombre, string $unidad, ?float $presupuestado, $materiales, $totales): array
    {
        $suma = fn (string $campo) => (float) $materiales->sum(fn (Material $m) => $totales[$m->id_material]->$campo ?? 0);

        $comprado = $suma('comprado');
        $ajustes = $suma('ajustes');
        $alertas = [];

        if ($presupuestado === null && $comprado > 0) {
            $alertas[] = 'No está en el presupuesto';
        } elseif ($presupuestado !== null && $comprado > $presupuestado) {
            $alertas[] = 'Se compró '.numero($comprado - $presupuestado).' '.$unidad.' más de lo presupuestado (¿compra duplicada?)';
        }

        if ($ajustes < 0) {
            $alertas[] = 'Pérdida de '.numero(abs($ajustes)).' '.$unidad.' detectada en conteos físicos';
        }

        return [
            'nombre' => $nombre,
            'unidad' => $unidad,
            'presupuestado' => $presupuestado,
            'comprado' => $comprado,
            'consumido' => $suma('consumido'),
            'ajustes' => $ajustes,
            'stock' => (float) $materiales->sum('stock_actual'),
            'alertas' => $alertas,
        ];
    }
}
