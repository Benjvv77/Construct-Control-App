<?php

namespace App\Http\Controllers;

use App\Models\Proyecto;
use App\Models\TipoGasto;
use App\Services\ResumenPresupuesto;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Reportes de desviación presupuestaria (HU16) y de gastos adicionales (HU17), en pantalla y PDF (HU18).
 */
class ReporteController extends Controller
{
    public function desviacion(Request $request, ResumenPresupuesto $resumen): View
    {
        return view('reporte_desviacion', $this->datosDesviacion($request, $resumen));
    }

    public function desviacionPdf(Request $request, ResumenPresupuesto $resumen): Response
    {
        return Pdf::loadView('reporte_desviacion_pdf', $this->datosDesviacion($request, $resumen))
            ->setPaper('a4', 'landscape')
            ->download('desviacion-presupuestaria-'.now()->format('Y-m-d').'.pdf');
    }

    public function adicionales(Proyecto $proyecto): View
    {
        return view('reporte_adicionales', $this->datosAdicionales($proyecto));
    }

    public function adicionalesPdf(Proyecto $proyecto): Response
    {
        return Pdf::loadView('reporte_adicionales_pdf', $this->datosAdicionales($proyecto))
            ->setPaper('a4')
            ->download('gastos-adicionales-'.Str::slug($proyecto->nombre_proyecto).'-'.now()->format('Y-m-d').'.pdf');
    }

    /**
     * Todos los proyectos visibles con su desviación, del mayor sobrecosto al menor.
     */
    private function datosDesviacion(Request $request, ResumenPresupuesto $resumen): array
    {
        $filas = Proyecto::visiblesPara($request->user())
            ->orderBy('nombre_proyecto')
            ->get()
            ->map(fn (Proyecto $proyecto) => ['proyecto' => $proyecto, ...$resumen->calcular($proyecto)])
            ->sortByDesc('porcentaje')
            ->values();

        return [
            'filas' => $filas,
            'presupuesto' => $filas->sum('presupuesto'),
            'gastado' => $filas->sum('gastado'),
            'adicional' => $filas->sum(fn ($f) => $f['adicionales']['gastado']),
            'conSobrecosto' => $filas->where('estado', ResumenPresupuesto::SOBRECOSTO)->count(),
            'enRiesgo' => $filas->where('estado', ResumenPresupuesto::RIESGO)->count(),
        ];
    }

    /**
     * Gastos adicionales aprobados del proyecto, con su peso sobre el presupuesto.
     */
    private function datosAdicionales(Proyecto $proyecto): array
    {
        $gastos = $proyecto->gastos()
            ->aprobados()
            ->adicionales()
            ->with(['tipoGasto', 'registradoPor', 'revisadoPor'])
            ->orderBy('fecha_gasto')
            ->get();

        $total = (float) $gastos->sum('monto_gasto');
        $presupuesto = (float) $proyecto->presupuesto_proyecto;
        $tipos = TipoGasto::pluck('nombre_tipo_gasto', 'id_tipo_gasto');

        return [
            'proyecto' => $proyecto,
            'gastos' => $gastos,
            'total' => $total,
            'presupuesto' => $presupuesto,
            'porcentaje' => $presupuesto > 0 ? $total / $presupuesto * 100 : 0,
            'totalGastado' => $proyecto->totalGastado(),
            'porTipo' => $gastos->groupBy('id_tipo_gasto')
                ->map(fn ($grupo, $id) => ['tipo' => $tipos[$id] ?? 'Sin tipo', 'cantidad' => $grupo->count(), 'total' => (float) $grupo->sum('monto_gasto')])
                ->sortByDesc('total')
                ->values(),
            'pendientes' => $proyecto->gastos()->pendientes()->adicionales()->count(),
        ];
    }
}
