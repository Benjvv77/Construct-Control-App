<?php

namespace App\Services;

use App\Models\EvaluacionCurvaS;
use App\Models\Proyecto;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Calcula la Curva S de un proyecto: gasto planificado acumulado frente a gasto real acumulado.
 *
 * La curva planificada sale de las evaluaciones registradas (mglpbc_curva_s). Si el proyecto
 * no tiene ninguna, se usa la forma en S típica de una obra repartida entre el inicio y el término.
 */
class CurvaS
{
    private const MAX_PUNTOS = 24;

    /**
     * @return array{puntos: list<array{fecha: string, planificado: float, real: ?float}>, plan_registrado: bool}
     */
    public function calcular(Proyecto $proyecto): array
    {
        // Solo los gastos aprobados cuentan como gasto real
        $gastos = $proyecto->gastos()
            ->aprobados()
            ->orderBy('fecha_gasto')
            ->get(['fecha_gasto', 'monto_gasto']);

        $evaluaciones = $proyecto->evaluacionesCurvaS()->orderBy('fecha_evaluacion')->get();

        $hoy = CarbonImmutable::today();
        $inicio = CarbonImmutable::parse($proyecto->fecha_inicio_proyecto);
        $termino = $proyecto->fecha_termino_proyecto
            ? CarbonImmutable::parse($proyecto->fecha_termino_proyecto)
            : collect([$hoy, $gastos->last()?->fecha_gasto, $evaluaciones->last()?->fecha_evaluacion])
                ->filter()
                ->map(fn ($fecha) => CarbonImmutable::parse($fecha))
                ->max();

        if ($termino->lessThanOrEqualTo($inicio)) {
            $termino = $inicio->addMonth();
        }

        $puntos = $this->fechas($inicio, $termino)->map(fn (CarbonImmutable $fecha) => [
            'fecha' => $fecha->toDateString(),
            'planificado' => round($evaluaciones->isNotEmpty()
                ? $this->interpolarPlan($evaluaciones, $inicio, $fecha)
                : $this->planEnS((float) $proyecto->presupuesto_proyecto, $inicio, $termino, $fecha), 2),
            // El gasto real solo se dibuja hasta hoy
            'real' => $fecha->greaterThan($hoy)
                ? null
                : (float) $gastos->filter(fn ($g) => $g->fecha_gasto->lessThanOrEqualTo($fecha))->sum('monto_gasto'),
        ]);

        return [
            'puntos' => $puntos->values()->all(),
            'plan_registrado' => $evaluaciones->isNotEmpty(),
        ];
    }

    /**
     * Fechas equiespaciadas entre el inicio y el término (como máximo MAX_PUNTOS).
     */
    private function fechas(CarbonImmutable $inicio, CarbonImmutable $termino): Collection
    {
        $meses = max(1, (int) ceil($inicio->diffInMonths($termino)));
        $cantidad = min(self::MAX_PUNTOS, max(6, $meses + 1));
        $dias = $inicio->diffInDays($termino);

        return collect(range(0, $cantidad - 1))
            ->map(fn (int $i) => $inicio->addDays((int) round($dias * $i / ($cantidad - 1))))
            ->unique(fn (CarbonImmutable $fecha) => $fecha->toDateString());
    }

    /**
     * Curva en S (suave al inicio y al final, más rápida a mitad de obra).
     */
    private function planEnS(float $presupuesto, CarbonImmutable $inicio, CarbonImmutable $termino, CarbonImmutable $fecha): float
    {
        $t = min(1, max(0, $inicio->diffInDays($fecha) / max(1, $inicio->diffInDays($termino))));

        return $presupuesto * ($t * $t * (3 - 2 * $t));
    }

    /**
     * Interpola linealmente entre las evaluaciones registradas, partiendo de 0 al inicio del proyecto.
     */
    private function interpolarPlan(Collection $evaluaciones, CarbonImmutable $inicio, CarbonImmutable $fecha): float
    {
        $anterior = ['fecha' => $inicio, 'monto' => 0.0];

        foreach ($evaluaciones as $evaluacion) {
            /** @var EvaluacionCurvaS $evaluacion */
            $actual = [
                'fecha' => CarbonImmutable::parse($evaluacion->fecha_evaluacion),
                'monto' => (float) $evaluacion->monto_planificado_acumulado,
            ];

            if ($fecha->lessThanOrEqualTo($actual['fecha'])) {
                $tramo = max(1, $anterior['fecha']->diffInDays($actual['fecha']));
                $avance = max(0, $anterior['fecha']->diffInDays($fecha, false)) / $tramo;

                return $anterior['monto'] + ($actual['monto'] - $anterior['monto']) * min(1, $avance);
            }

            $anterior = $actual;
        }

        // Después de la última evaluación el plan se mantiene
        return $anterior['monto'];
    }
}
