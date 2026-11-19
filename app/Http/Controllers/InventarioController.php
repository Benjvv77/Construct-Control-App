<?php

namespace App\Http\Controllers;

use App\Models\Material;
use App\Models\MovimientoInventario;
use App\Models\Proyecto;
use App\Models\TipoGasto;
use App\Services\ComparativoInventario;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;
use Illuminate\Validation\Rules\Unique;
use Illuminate\View\View;

class InventarioController extends Controller
{
    public function index(Proyecto $proyecto, ComparativoInventario $comparativo): View
    {
        $materiales = $proyecto->materiales()
            ->with('item')
            ->where('estado_material', 1)
            ->orderBy('nombre_material')
            ->get();

        $movimientos = $proyecto->movimientos()
            ->with('material')
            ->orderByDesc('fecha_movimiento')
            ->orderByDesc('id_movimiento')
            ->get();

        return view('inventario', [
            'proyecto' => $proyecto,
            'materiales' => $materiales,
            'movimientos' => $movimientos,
            'comparativo' => $comparativo->calcular($proyecto),
            'itemsMateriales' => $this->itemsDeMateriales($proyecto),
            'stockBajo' => $materiales->filter(fn (Material $m) => $m->bajoStockMinimo())->count(),
            'totalSalidas' => $movimientos->where('tipo_movimiento', MovimientoInventario::SALIDA)->count(),
        ]);
    }

    public function storeMovimiento(Request $request, Proyecto $proyecto): RedirectResponse
    {
        $datos = $request->validate([
            'id_material' => ['required', 'integer', $this->materialDelProyecto($proyecto)],
            'tipo_movimiento' => ['required', Rule::in([MovimientoInventario::ENTRADA, MovimientoInventario::SALIDA])],
            'cantidad_movimiento' => ['required', 'numeric', 'gt:0'],
            'fecha_movimiento' => ['required', 'date'],
            'descripcion_movimiento' => ['nullable', 'string', 'max:1000'],
        ]);

        Material::findOrFail($datos['id_material'])->registrarMovimiento(
            $datos['tipo_movimiento'],
            (float) $datos['cantidad_movimiento'],
            $datos['fecha_movimiento'],
            $datos['descripcion_movimiento'] ?? null,
        );

        return redirect()->route('inventario.index', $proyecto)->with('exito', 'Movimiento registrado correctamente.');
    }

    /**
     * Conteo físico (HU14): el stock pasa a ser lo contado y la diferencia queda como AJUSTE.
     */
    public function storeConteo(Request $request, Proyecto $proyecto): RedirectResponse
    {
        $datos = $request->validate([
            'id_material' => ['required', 'integer', $this->materialDelProyecto($proyecto)],
            'cantidad_contada' => ['required', 'numeric', 'min:0'],
            'fecha_movimiento' => ['required', 'date'],
            'descripcion_movimiento' => ['nullable', 'string', 'max:1000'],
        ]);

        $material = Material::findOrFail($datos['id_material']);
        $ajuste = $material->registrarConteo((float) $datos['cantidad_contada'], $datos['fecha_movimiento'], $datos['descripcion_movimiento'] ?? null);

        $mensaje = match (true) {
            $ajuste === null => 'El conteo coincide con el stock registrado: no hay diferencias.',
            (float) $ajuste->cantidad_movimiento < 0 => 'Conteo registrado: faltan '.numero(abs($ajuste->cantidad_movimiento)).' '.$material->unidad_material.' de '.$material->nombre_material.'.',
            default => 'Conteo registrado: sobran '.numero($ajuste->cantidad_movimiento).' '.$material->unidad_material.' de '.$material->nombre_material.'.',
        };

        return redirect()->route('inventario.index', $proyecto)->with('exito', $mensaje);
    }

    public function storeMaterial(Request $request, Proyecto $proyecto): RedirectResponse
    {
        $datos = $request->validate([
            'nombre_material' => ['required', 'string', 'max:255', $this->nombreLibre($proyecto)],
            'unidad_material' => ['required', 'string', 'max:50'],
            'cantidad_inicial' => ['nullable', 'numeric', 'min:0'],
            'stock_minimo' => ['required', 'numeric', 'min:0'],
            'costo_estimado_material' => ['nullable', 'numeric', 'min:0'],
            'id_item_presupuesto' => ['nullable', 'integer', $this->itemDeMateriales($proyecto)],
        ]);

        DB::transaction(function () use ($proyecto, $datos) {
            $material = $proyecto->materiales()->create([
                'id_item_presupuesto' => $datos['id_item_presupuesto'] ?? null,
                'nombre_material' => $datos['nombre_material'],
                'unidad_material' => $datos['unidad_material'],
                'stock_minimo' => $datos['stock_minimo'],
                'costo_estimado_material' => $datos['costo_estimado_material'] ?? 0,
                'estado_material' => 1,
            ]);

            // El stock inicial queda registrado como una entrada en el historial
            if (($datos['cantidad_inicial'] ?? 0) > 0) {
                $material->registrarMovimiento(MovimientoInventario::ENTRADA, (float) $datos['cantidad_inicial'], now()->toDateString(), 'Stock inicial');
            }
        });

        return redirect()->route('inventario.index', $proyecto)->with('exito', 'Material agregado al inventario.');
    }

    public function updateMaterial(Request $request, Proyecto $proyecto, Material $material): RedirectResponse
    {
        abort_unless($material->id_proyecto === $proyecto->id_proyecto, 404);

        $material->update($request->validate([
            'nombre_material' => ['required', 'string', 'max:255', $this->nombreLibre($proyecto)->ignore($material->id_material, 'id_material')],
            'unidad_material' => ['required', 'string', 'max:50'],
            'stock_minimo' => ['required', 'numeric', 'min:0'],
            'costo_estimado_material' => ['required', 'numeric', 'min:0'],
            'id_item_presupuesto' => ['nullable', 'integer', $this->itemDeMateriales($proyecto)],
        ]));

        return redirect()->route('inventario.index', $proyecto)->with('exito', 'Material actualizado.');
    }

    public function destroyMaterial(Proyecto $proyecto, Material $material): RedirectResponse
    {
        abort_unless($material->id_proyecto === $proyecto->id_proyecto, 404);

        // Sus movimientos se borran en cascada
        $material->delete();

        return redirect()->route('inventario.index', $proyecto)->with('exito', 'Material eliminado correctamente.');
    }

    private function nombreLibre(Proyecto $proyecto): Unique
    {
        return Rule::unique('mglpbc_material', 'nombre_material')
            ->where('id_proyecto', $proyecto->id_proyecto)
            ->where('estado_material', 1);
    }

    private function itemDeMateriales(Proyecto $proyecto): Exists
    {
        return Rule::exists('mglpbc_item_presupuesto', 'id_item_presupuesto')
            ->where('id_proyecto', $proyecto->id_proyecto)
            ->where('id_tipo_gasto', TipoGasto::MATERIALES);
    }

    private function itemsDeMateriales(Proyecto $proyecto): Collection
    {
        return $proyecto->itemsPresupuesto()->where('id_tipo_gasto', TipoGasto::MATERIALES)->orderBy('descripcion_item')->get();
    }

    private function materialDelProyecto(Proyecto $proyecto): Exists
    {
        return Rule::exists('mglpbc_material', 'id_material')->where('id_proyecto', $proyecto->id_proyecto);
    }
}
