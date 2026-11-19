<?php

namespace App\Http\Controllers;

use App\Models\EvaluacionCurvaS;
use App\Models\Proyecto;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CurvaSController extends Controller
{
    public function store(Request $request, Proyecto $proyecto): RedirectResponse
    {
        $datos = $request->validate([
            'fecha_evaluacion' => ['required', 'date', 'after_or_equal:'.$proyecto->fecha_inicio_proyecto->toDateString()],
            'monto_planificado_acumulado' => ['required', 'numeric', 'min:0'],
        ]);

        // Guarda también cuánto se llevaba gastado a esa fecha, para comparar plan y realidad
        $proyecto->evaluacionesCurvaS()->updateOrCreate(
            ['fecha_evaluacion' => $datos['fecha_evaluacion']],
            [
                'monto_planificado_acumulado' => $datos['monto_planificado_acumulado'],
                'monto_real_acumulado' => $proyecto->gastadoHasta(CarbonImmutable::parse($datos['fecha_evaluacion'])),
            ]
        );

        return redirect()->route('proyectos.reporte', $proyecto)->with('exito', 'Punto de la Curva S guardado.');
    }

    public function destroy(Proyecto $proyecto, EvaluacionCurvaS $evaluacion): RedirectResponse
    {
        abort_unless($evaluacion->id_proyecto === $proyecto->id_proyecto, 404);

        $evaluacion->delete();

        return redirect()->route('proyectos.reporte', $proyecto)->with('exito', 'Punto de la Curva S eliminado.');
    }
}
