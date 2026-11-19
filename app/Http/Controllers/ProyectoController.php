<?php

namespace App\Http\Controllers;

use App\Models\Proyecto;
use App\Models\TipoGasto;
use App\Models\Usuario;
use App\Services\CurvaS;
use App\Services\ResumenPresupuesto;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProyectoController extends Controller
{
    public function index(Request $request): View
    {
        $filtros = $request->validate([
            'buscar' => ['nullable', 'string', 'max:100'],
            'estado' => ['nullable', Rule::in(array_keys(Proyecto::FILTROS))],
        ]);

        $visibles = Proyecto::visiblesPara($request->user());

        $proyectos = (clone $visibles)
            ->with('usuario')
            ->withSum(['gastos as total_gastado' => fn ($q) => $q->aprobados()], 'monto_gasto')
            ->buscar($filtros['buscar'] ?? null)
            ->when($filtros['estado'] ?? null, function ($query, $estado) {
                $estado === 'alertas'
                    ? $query->conAlerta()
                    : $query->where('estado_proyecto', Proyecto::FILTROS[$estado]);
            })
            ->orderByDesc('id_proyecto')
            ->paginate(10)
            ->withQueryString();

        return view('proyectos', [
            'proyectos' => $proyectos,
            'filtros' => $filtros,
            'totalProyectos' => (clone $visibles)->count(),
            'proyectosActivos' => (clone $visibles)->where('estado_proyecto', Proyecto::ESTADO_ACTIVO)->count(),
            'proyectosAlerta' => (clone $visibles)->conAlerta()->count(),
            'presupuestoTotal' => (clone $visibles)->sum('presupuesto_proyecto'),
            'responsables' => $this->responsablesDisponibles($request),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Proyecto::create([
            'id_usuario' => $request->user()->id_usuario,
            'presupuesto_proyecto' => 0,
            ...$this->validar($request),
        ]);

        return redirect()->route('proyectos.index')
            ->with('exito', 'Proyecto creado. Ahora agrega los ítems de su presupuesto desde el detalle del proyecto.');
    }

    public function show(Request $request, Proyecto $proyecto, ResumenPresupuesto $resumen): View
    {
        $costo = $request->validate([
            'costo' => ['nullable', Rule::in(['normal', 'extra'])],
        ])['costo'] ?? null;

        $gastos = $proyecto->gastos()
            ->with(['item', 'tipoGasto', 'tipoCosto', 'registradoPor', 'revisadoPor'])
            ->when($costo, fn ($query, $costo) => $costo === 'extra' ? $query->adicionales() : $query->presupuestados())
            ->orderByDesc('fecha_gasto')
            ->orderByDesc('id_gasto')
            ->get();

        return view('detalle_proyecto', [
            'proyecto' => $proyecto,
            'gastos' => $gastos,
            'filtroCosto' => $costo,
            'items' => $proyecto->itemsPresupuesto()->with('tipoGasto')->orderBy('id_item_presupuesto')->get(),
            'tiposGasto' => TipoGasto::orderBy('id_tipo_gasto')->get(),
            'comparativo' => $resumen->calcular($proyecto),
            ...$this->resumenPresupuesto($proyecto),
        ]);
    }

    public function update(Request $request, Proyecto $proyecto): RedirectResponse
    {
        $proyecto->update($this->validar($request));

        return redirect()->route('proyectos.index')->with('exito', 'Proyecto actualizado exitosamente.');
    }

    public function destroy(Proyecto $proyecto): RedirectResponse
    {
        $documentos = $proyecto->gastos()->whereNotNull('documento_gasto')->pluck('documento_gasto');

        // Gastos, materiales, movimientos e ítems se borran en cascada desde la base de datos
        $proyecto->delete();

        Storage::delete($documentos->all());

        return redirect()->route('proyectos.index')->with('exito', 'Proyecto eliminado exitosamente.');
    }

    public function reporte(Proyecto $proyecto, CurvaS $curvaS, ResumenPresupuesto $resumen): View
    {
        return view('reporte', $this->datosReporte($proyecto, $curvaS, $resumen));
    }

    public function reportePdf(Proyecto $proyecto, CurvaS $curvaS, ResumenPresupuesto $resumen): Response
    {
        $datos = $this->datosReporte($proyecto, $curvaS, $resumen);
        $datos['grafico'] = 'data:image/svg+xml;base64,'.base64_encode(view('curva_s', ['puntos' => $datos['curva']['puntos']])->render());

        $nombre = 'reporte-'.Str::slug($proyecto->nombre_proyecto).'-'.now()->format('Y-m-d').'.pdf';

        return Pdf::loadView('reporte_pdf', $datos)
            ->setPaper('a4')
            ->download($nombre);
    }

    private function datosReporte(Proyecto $proyecto, CurvaS $curvaS, ResumenPresupuesto $resumen): array
    {
        return [
            'proyecto' => $proyecto,
            'gastos' => $proyecto->gastos()
                ->aprobados()
                ->with(['tipoGasto', 'tipoCosto', 'item'])
                ->orderByDesc('fecha_gasto')
                ->get(),
            'comparativo' => $resumen->calcular($proyecto),
            'curva' => $curvaS->calcular($proyecto),
            'evaluaciones' => $proyecto->evaluacionesCurvaS()->orderBy('fecha_evaluacion')->get(),
            ...$this->resumenPresupuesto($proyecto),
        ];
    }

    private function validar(Request $request): array
    {
        // Solo el Administrador crea y edita proyectos (HU03, HU04). Elige al responsable;
        // si no lo indica, queda él mismo al crear o no cambia al editar.
        // El presupuesto no se escribe aquí: es la suma de los ítems (HU07).
        return $request->validate([
            'id_usuario' => ['sometimes', 'integer', Rule::exists('mglpbc_usuario', 'id_usuario')->where('estado_usuario', 1)],
            'nombre_proyecto' => ['required', 'string', 'max:255'],
            'descripcion_proyecto' => ['nullable', 'string'],
            'fecha_inicio_proyecto' => ['required', 'date'],
            'fecha_termino_proyecto' => ['nullable', 'date', 'after_or_equal:fecha_inicio_proyecto'],
            'estado_proyecto' => ['required', 'integer', Rule::in(array_keys(Proyecto::ESTADOS))],
        ]);
    }

    private function responsablesDisponibles(Request $request): Collection
    {
        if (! $request->user()->esAdministrador()) {
            return collect();
        }

        return Usuario::activos()->orderBy('nombre_usuario')->get();
    }

    private function resumenPresupuesto(Proyecto $proyecto): array
    {
        $presupuesto = (float) $proyecto->presupuesto_proyecto;
        $totalGastado = $proyecto->totalGastado();
        $porcentajeUsado = $presupuesto > 0 ? ($totalGastado / $presupuesto) * 100 : 0;

        return [
            'presupuesto' => $presupuesto,
            'totalGastado' => $totalGastado,
            'totalExtra' => $proyecto->totalExtra(),
            'disponible' => $presupuesto - $totalGastado,
            'porcentajeUsado' => $porcentajeUsado,
            'porcentajeBarra' => (int) min(100, round($porcentajeUsado)),
            'alertaPresupuesto' => $proyecto->alertaPresupuesto($totalGastado),
        ];
    }
}
