<?php

namespace App\Http\Controllers;

use App\Models\Gasto;
use App\Models\Proyecto;
use App\Services\RegistroGastos;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * HU10: el Administrador revisa los gastos pendientes y los aprueba o rechaza.
 */
class AprobacionGastoController extends Controller
{
    public function index(): View
    {
        return view('gastos_pendientes', [
            'gastos' => Gasto::pendientes()
                ->with(['proyecto', 'item', 'tipoGasto', 'registradoPor'])
                ->orderBy('fecha_gasto')
                ->orderBy('id_gasto')
                ->paginate(20),
        ]);
    }

    public function aprobar(Request $request, Gasto $gasto, RegistroGastos $registro): RedirectResponse
    {
        $registro->aprobar($gasto, $request->user());

        $mensaje = 'Gasto "'.$gasto->nombre_gasto.'" aprobado.';

        if ($gasto->proyecto->actualizarAlertaAutomatica()) {
            $mensaje .= ' El proyecto "'.$gasto->proyecto->nombre_proyecto.'" pasó a estado "Alerta" porque los gastos alcanzaron el '
                .Proyecto::UMBRAL_ALERTA.'% del presupuesto.';
        }

        return back()->with('exito', $mensaje);
    }

    public function rechazar(Request $request, Gasto $gasto, RegistroGastos $registro): RedirectResponse
    {
        $datos = $request->validate(
            ['motivo_rechazo' => ['required', 'string', 'max:1000']],
            ['motivo_rechazo.required' => 'Indica el motivo del rechazo, para que quien lo registró pueda corregirlo.'],
        );

        $registro->rechazar($gasto, $request->user(), $datos['motivo_rechazo']);

        return back()->with('exito', 'Gasto "'.$gasto->nombre_gasto.'" rechazado.');
    }
}
