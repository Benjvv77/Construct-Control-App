<?php

namespace App\Http\Controllers;

use App\Models\Gasto;
use App\Models\Material;
use App\Models\Proyecto;
use App\Models\TipoCosto;
use App\Models\TipoGasto;
use App\Services\RegistroGastos;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class GastoController extends Controller
{
    public function store(Request $request, Proyecto $proyecto, RegistroGastos $registro): RedirectResponse
    {
        $datos = $this->validarGasto($request, $proyecto, null);

        $gasto = $registro->registrar($proyecto, $datos, $request->user(), $request->file('documento_gasto'));

        $mensaje = $gasto->estaAprobado()
            ? $this->mensajeConAlerta($proyecto, 'Gasto registrado y aprobado.')
            : 'Gasto registrado. Queda pendiente hasta que un administrador lo apruebe.';

        return redirect()->route('proyectos.show', $proyecto)->with('exito', $mensaje);
    }

    public function update(Request $request, Proyecto $proyecto, Gasto $gasto): RedirectResponse
    {
        abort_unless($gasto->id_proyecto === $proyecto->id_proyecto, 404);
        abort_unless($gasto->puedeModificar($request->user()), 403, 'Solo puedes modificar tus gastos mientras no estén aprobados.');

        $datos = $gasto->estaAprobado()
            ? $this->validarGastoAprobado($request, $gasto)
            : [...$this->validarGasto($request, $proyecto, $gasto), 'estado_gasto' => Gasto::PENDIENTE, 'motivo_rechazo' => null];

        $documentoAnterior = $gasto->documento_gasto;
        $documentoNuevo = $request->file('documento_gasto')?->store('gastos');

        try {
            DB::transaction(function () use ($gasto, $datos, $documentoNuevo) {
                // Un gasto de materiales aprobado ya está en el inventario: su costo sigue al monto
                $diferenciaMonto = (float) $datos['monto_gasto'] - (float) $gasto->monto_gasto;

                if ($gasto->estaAprobado() && $gasto->esDeMateriales() && $diferenciaMonto != 0) {
                    Material::where('id_proyecto', $gasto->id_proyecto)
                        ->where('estado_material', 1)
                        ->when($gasto->id_item_presupuesto,
                            fn ($q) => $q->where('id_item_presupuesto', $gasto->id_item_presupuesto),
                            fn ($q) => $q->where('nombre_material', $gasto->nombre_gasto))
                        ->lockForUpdate()
                        ->first()
                        ?->increment('costo_estimado_material', $diferenciaMonto);
                }

                $gasto->update([
                    ...collect($datos)->except('documento_gasto')->all(),
                    ...($documentoNuevo ? ['documento_gasto' => $documentoNuevo] : []),
                ]);
            });
        } catch (Throwable $e) {
            if ($documentoNuevo) {
                Storage::delete($documentoNuevo);
            }

            throw $e;
        }

        if ($documentoNuevo && $documentoAnterior) {
            Storage::delete($documentoAnterior);
        }

        $mensaje = $gasto->estaAprobado()
            ? $this->mensajeConAlerta($proyecto, 'Gasto actualizado correctamente.')
            : 'Gasto actualizado. Queda pendiente de aprobación.';

        return redirect()->route('proyectos.show', $proyecto)->with('exito', $mensaje);
    }

    public function destroy(Request $request, Proyecto $proyecto, Gasto $gasto): RedirectResponse
    {
        abort_unless($gasto->id_proyecto === $proyecto->id_proyecto, 404);
        abort_unless($gasto->puedeModificar($request->user()), 403, 'Solo puedes eliminar tus gastos mientras no estén aprobados.');

        $documento = $gasto->documento_gasto;

        $gasto->delete();

        if ($documento) {
            Storage::delete($documento);
        }

        return redirect()->route('proyectos.show', $proyecto)->with('exito', 'Gasto eliminado correctamente.');
    }

    public function documento(Proyecto $proyecto, Gasto $gasto): StreamedResponse
    {
        abort_unless($gasto->id_proyecto === $proyecto->id_proyecto, 404);
        abort_unless($gasto->documento_gasto && Storage::exists($gasto->documento_gasto), 404, 'El documento de este gasto ya no está disponible.');

        return Storage::response($gasto->documento_gasto);
    }

    /**
     * Reglas de un gasto nuevo o todavía no aprobado:
     * - Presupuestado (HU08): se elige un ítem del presupuesto y la boleta o factura es obligatoria.
     * - Adicional (HU09): se elige el tipo de gasto y hay que justificarlo.
     */
    private function validarGasto(Request $request, Proyecto $proyecto, ?Gasto $gasto): array
    {
        $adicional = $request->input('clase_gasto') === 'adicional';
        $item = $adicional ? null : $proyecto->itemsPresupuesto()->find($request->integer('id_item_presupuesto'));
        $tipoGasto = $item?->id_tipo_gasto ?? $request->integer('id_tipo_gasto');
        $esMaterial = $tipoGasto === TipoGasto::MATERIALES;
        $tieneDocumento = (bool) $gasto?->documento_gasto;

        $datos = $request->validate([
            'clase_gasto' => ['required', Rule::in(['presupuestado', 'adicional'])],
            'id_item_presupuesto' => $adicional ? ['exclude'] : ['required', 'integer',
                Rule::exists('mglpbc_item_presupuesto', 'id_item_presupuesto')->where('id_proyecto', $proyecto->id_proyecto)],
            'id_tipo_gasto' => $adicional ? ['required', 'integer', 'exists:mglpbc_tipo_gastos,id_tipo_gasto'] : ['exclude'],
            'nombre_gasto' => ['required', 'string', 'max:255'],
            'descripcion_gasto' => $adicional ? ['required', 'string', 'max:2000'] : ['nullable', 'string', 'max:2000'],
            'monto_gasto' => ['required', 'numeric', 'gt:0'],
            'fecha_gasto' => ['required', 'date'],
            'cantidad_gasto' => $esMaterial ? ['required', 'numeric', 'gt:0'] : ['exclude'],
            'unidad_gasto' => $esMaterial ? ['required', 'string', 'max:30'] : ['exclude'],
            'documento_gasto' => [! $adicional && ! $tieneDocumento ? 'required' : 'nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ], [
            'documento_gasto.required' => 'Adjunta la boleta o factura: es obligatoria en los gastos presupuestados.',
            'descripcion_gasto.required' => 'Explica por qué se necesita este gasto adicional.',
            'id_item_presupuesto.required' => 'Elige el ítem del presupuesto al que corresponde el gasto.',
        ]);

        unset($datos['clase_gasto']);

        return [
            ...$datos,
            'id_tipo_costo' => $adicional ? TipoCosto::ADICIONAL : TipoCosto::PRESUPUESTADO,
            'id_tipo_gasto' => $tipoGasto,
            'id_item_presupuesto' => $item?->id_item_presupuesto,
            'cantidad_gasto' => $datos['cantidad_gasto'] ?? 0,
            'unidad_gasto' => $datos['unidad_gasto'] ?? null,
        ];
    }

    /**
     * Un gasto aprobado ya afectó el presupuesto (y el inventario si es de materiales): solo se corrigen
     * monto, fecha, descripción, documento y, si no es de materiales, el nombre.
     */
    private function validarGastoAprobado(Request $request, Gasto $gasto): array
    {
        $reglas = [
            'descripcion_gasto' => [$gasto->esExtra() ? 'required' : 'nullable', 'string', 'max:2000'],
            'monto_gasto' => ['required', 'numeric', 'gt:0'],
            'fecha_gasto' => ['required', 'date'],
            'documento_gasto' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ];

        if (! $gasto->esDeMateriales()) {
            $reglas['nombre_gasto'] = ['required', 'string', 'max:255'];
        }

        return $request->validate($reglas, [
            'descripcion_gasto.required' => 'Explica por qué se necesita este gasto adicional.',
        ]);
    }

    private function mensajeConAlerta(Proyecto $proyecto, string $mensaje): string
    {
        if ($proyecto->actualizarAlertaAutomatica()) {
            $mensaje .= ' El proyecto pasó a estado "Alerta" porque los gastos alcanzaron el '.Proyecto::UMBRAL_ALERTA.'% del presupuesto.';
        }

        return $mensaje;
    }
}
