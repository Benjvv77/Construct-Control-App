<?php

namespace App\Http\Controllers;

use App\Models\ItemPresupuesto;
use App\Models\Proyecto;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * HU07: el Administrador detalla el presupuesto del proyecto en ítems.
 * El presupuesto total del proyecto es la suma de los ítems.
 */
class PresupuestoController extends Controller
{
    public function store(Request $request, Proyecto $proyecto): RedirectResponse
    {
        $proyecto->itemsPresupuesto()->create($this->validar($request));

        return redirect()->route('proyectos.show', $proyecto)->withFragment('presupuesto')
            ->with('exito', 'Ítem agregado al presupuesto.');
    }

    public function update(Request $request, Proyecto $proyecto, ItemPresupuesto $item): RedirectResponse
    {
        abort_unless($item->id_proyecto === $proyecto->id_proyecto, 404);

        $item->update($this->validar($request));

        return redirect()->route('proyectos.show', $proyecto)->withFragment('presupuesto')
            ->with('exito', 'Ítem del presupuesto actualizado.');
    }

    public function destroy(Proyecto $proyecto, ItemPresupuesto $item): RedirectResponse
    {
        abort_unless($item->id_proyecto === $proyecto->id_proyecto, 404);

        $gastos = $item->gastos()->count();

        if ($gastos > 0) {
            return back()->withErrors(['item' => "No se puede eliminar \"{$item->descripcion_item}\": tiene $gastos "
                .($gastos === 1 ? 'gasto asociado' : 'gastos asociados').'. Elimina o corrige esos gastos primero.']);
        }

        $item->delete();

        return redirect()->route('proyectos.show', $proyecto)->withFragment('presupuesto')
            ->with('exito', 'Ítem eliminado del presupuesto.');
    }

    private function validar(Request $request): array
    {
        return $request->validate([
            'id_tipo_gasto' => ['required', 'integer', 'exists:mglpbc_tipo_gastos,id_tipo_gasto'],
            'descripcion_item' => ['required', 'string', 'max:255'],
            'cantidad' => ['required', 'numeric', 'gt:0'],
            'unidad_item' => ['nullable', 'string', 'max:30'],
            'precio_unitario' => ['required', 'numeric', 'gt:0'],
        ]);
    }
}
