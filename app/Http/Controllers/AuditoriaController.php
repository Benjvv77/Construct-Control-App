<?php

namespace App\Http\Controllers;

use App\Models\Auditoria;
use App\Models\Proyecto;
use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * HU19: historial de quién creó, modificó o eliminó cada dato, y cuándo.
 */
class AuditoriaController extends Controller
{
    public function index(Request $request): View
    {
        $filtros = $request->validate([
            'entidad' => ['nullable', 'string', 'max:50'],
            'accion' => ['nullable', Rule::in(array_keys(Auditoria::ACCIONES))],
            'usuario' => ['nullable', 'integer'],
            'proyecto' => ['nullable', 'integer'],
            'desde' => ['nullable', 'date'],
            'hasta' => ['nullable', 'date', 'after_or_equal:desde'],
        ]);

        $registros = Auditoria::with('usuario')
            ->when($filtros['entidad'] ?? null, fn ($q, $v) => $q->where('entidad', $v))
            ->when($filtros['accion'] ?? null, fn ($q, $v) => $q->where('accion', $v))
            ->when($filtros['usuario'] ?? null, fn ($q, $v) => $q->where('id_usuario', $v))
            ->when($filtros['proyecto'] ?? null, fn ($q, $v) => $q->where('id_proyecto', $v))
            ->when($filtros['desde'] ?? null, fn ($q, $v) => $q->whereDate('fecha', '>=', $v))
            ->when($filtros['hasta'] ?? null, fn ($q, $v) => $q->whereDate('fecha', '<=', $v))
            ->orderByDesc('fecha')
            ->orderByDesc('id_auditoria')
            ->paginate(25)
            ->withQueryString();

        return view('auditoria', [
            'registros' => $registros,
            'filtros' => $filtros,
            'entidades' => Auditoria::distinct()->orderBy('entidad')->pluck('entidad'),
            'usuarios' => Usuario::orderBy('nombre_usuario')->get(),
            'proyectos' => Proyecto::orderBy('nombre_proyecto')->pluck('nombre_proyecto', 'id_proyecto'),
        ]);
    }
}
