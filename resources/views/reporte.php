<?php ob_start(); ?>
    <a href="<?= e(route('proyectos.show', $proyecto)) ?>" class="btn-secondary">
        ← Volver
    </a>

    <button onclick="window.print()" class="btn-secondary">
        🖨️ Imprimir
    </button>

    <a href="<?= e(route('proyectos.reporte.pdf', $proyecto)) ?>" class="btn-primary">
        📄 Exportar PDF
    </a>
<?php $acciones = ob_get_clean(); ?>
<?= view('encabezado', ['titulo' => 'Reporte Proyecto', 'estilos' => ['reportes'], 'claseTopbar' => 'no-print', 'acciones' => $acciones]) ?>

<main class="main">

    <?= view('alertas') ?>

    <section class="report-hero">
        <div>
            <span class="eyebrow">Reporte financiero del proyecto</span>

            <h1><?= e($proyecto->nombre_proyecto) ?></h1>

            <p>
                Resumen de presupuesto, gastos registrados, costos extra y Curva S acumulada.
            </p>
        </div>

        <div class="report-date">
            <span>Fecha de emisión</span>
            <strong><?= e(now()->format('d/m/Y')) ?></strong>
        </div>
    </section>

    <section class="kpis">
        <div class="kpi-card">
            <p>Presupuesto inicial</p>
            <h2><?= pesos($presupuesto) ?></h2>
        </div>

        <div class="kpi-card">
            <p>Total gastado</p>
            <h2><?= pesos($totalGastado) ?></h2>
        </div>

        <div class="kpi-card">
            <p>Disponible</p>
            <h2><?= pesos($disponible) ?></h2>
        </div>

        <div class="kpi-card">
            <p>Costos extra</p>
            <h2><?= pesos($totalExtra) ?></h2>
        </div>
    </section>

    <section class="report-card">
        <div class="section-header">
            <div>
                <h2>Avance presupuestario</h2>
                <p>Porcentaje utilizado del presupuesto total.</p>
            </div>

            <strong><?= e(round($porcentajeUsado, 1)) ?>%</strong>
        </div>

        <div class="budget-progress">
            <div style="width: <?= e($porcentajeBarra) ?>%;"></div>
        </div>
    </section>

    <!-- HU16: desviación presupuestaria por ítem -->
    <section class="report-card">
        <div class="section-header">
            <div>
                <h2>Desviación por ítem</h2>
                <p>Gastos aprobados frente a lo presupuestado en cada ítem. Desviación positiva = sobrecosto.</p>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Ítem</th>
                    <th>Presupuestado</th>
                    <th>Gastado</th>
                    <th>Avance</th>
                    <th>Desviación</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($comparativo['filas'] as $fila): ?>
                    <tr>
                        <td><strong><?= e($fila['item']->descripcion_item) ?></strong><span><?= e($fila['item']->tipoGasto->nombre_tipo_gasto) ?></span></td>
                        <td class="money"><?= pesos($fila['presupuestado']) ?></td>
                        <td class="money"><?= pesos($fila['gastado']) ?></td>
                        <td><?= e(round($fila['porcentaje'], 1)) ?>%</td>
                        <td class="money <?= $fila['desviacion'] > 0 ? 'texto-rojo' : 'texto-verde' ?>"><?= $fila['desviacion'] > 0 ? '+' : '' ?><?= pesos($fila['desviacion']) ?></td>
                        <td><?= e(\App\Services\ResumenPresupuesto::ESTADOS[$fila['estado']]['texto']) ?></td>
                    </tr>
                <?php endforeach; ?>
                <tr>
                    <td><strong>Gastos adicionales</strong><span>No presupuestados</span></td>
                    <td class="money">—</td>
                    <td class="money"><?= pesos($comparativo['adicionales']['gastado']) ?></td>
                    <td>—</td>
                    <td class="money <?= $comparativo['adicionales']['gastado'] > 0 ? 'texto-rojo' : '' ?>">+<?= pesos($comparativo['adicionales']['gastado']) ?></td>
                    <td>—</td>
                </tr>
                <tr class="fila-total">
                    <td><strong>Total</strong></td>
                    <td class="money"><?= pesos($comparativo['presupuesto']) ?></td>
                    <td class="money"><?= pesos($comparativo['gastado']) ?></td>
                    <td><?= e(round($comparativo['porcentaje'], 1)) ?>%</td>
                    <td class="money <?= $comparativo['desviacion'] > 0 ? 'texto-rojo' : 'texto-verde' ?>"><?= $comparativo['desviacion'] > 0 ? '+' : '' ?><?= pesos($comparativo['desviacion']) ?></td>
                    <td><?= e(\App\Services\ResumenPresupuesto::ESTADOS[$comparativo['estado']]['texto']) ?></td>
                </tr>
            </tbody>
        </table>
    </section>

    <section class="report-card">
        <div class="section-header">
            <div>
                <h2>Curva S</h2>
                <p>
                    Gasto real acumulado frente al planificado.
                    <?= e($curva['plan_registrado']
                        ? 'El plan sale de los puntos registrados abajo.'
                        : 'Sin puntos registrados, el plan usa una curva en S típica entre el inicio y el término del proyecto.') ?>
                </p>
            </div>
        </div>

        <div class="grafico-curva">
            <?= view('curva_s', ['puntos' => $curva['puntos']]) ?>
        </div>
    </section>

    <section class="report-card">
        <div class="section-header">
            <div>
                <h2>Plan de la Curva S</h2>
                <p>Cuánto se planifica tener gastado a cada fecha, comparado con lo realmente gastado hasta ese día.</p>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Planificado acumulado</th>
                    <th>Real acumulado</th>
                    <th>Desviación</th>
                    <?php if (auth()->user()->can('update', $proyecto)): ?>
                        <th class="no-print"></th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php if (count($evaluaciones) > 0): ?>
                <?php foreach ($evaluaciones as $evaluacion): ?>
                    <tr>
                        <td><?= e($evaluacion->fecha_evaluacion->format('Y-m-d')) ?></td>
                        <td class="money"><?= pesos($evaluacion->monto_planificado_acumulado) ?></td>
                        <?php if ($evaluacion->realAcumulado() === null): ?>
                            <td class="money">Pendiente</td>
                            <td class="money">—</td>
                        <?php else: ?>
                            <td class="money"><?= pesos($evaluacion->realAcumulado()) ?></td>
                            <td class="money" style="color: <?= e($evaluacion->desviacion() > 0 ? '#8B2A1A' : '#2F6B27') ?>;">
                                <?= e($evaluacion->desviacion() > 0 ? '+' : '') ?><?= pesos($evaluacion->desviacion()) ?>
                            </td>
                        <?php endif; ?>
                        <?php if (auth()->user()->can('update', $proyecto)): ?>
                            <td class="no-print">
                                <form action="<?= e(route('curva-s.destroy', [$proyecto, $evaluacion->id_curva])) ?>" method="POST" class="form-inline"
                                      onsubmit="return confirm('¿Eliminar este punto del plan?')">
                                    <?= csrf_field() ?>
                                    <?= method_field('DELETE') ?>
                                    <button type="submit" class="btn-eliminar">Eliminar</button>
                                </form>
                            </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="empty">Aún no hay puntos de plan registrados.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <?php if (auth()->user()->can('update', $proyecto)): ?>
            <form action="<?= e(route('curva-s.store', $proyecto)) ?>" method="POST" class="form-curva no-print">
                <?= csrf_field() ?>
                <label>
                    Fecha
                    <input type="date" name="fecha_evaluacion" value="<?= e(old('fecha_evaluacion')) ?>" min="<?= e($proyecto->fecha_inicio_proyecto->format('Y-m-d')) ?>" required>
                </label>
                <label>
                    Planificado acumulado ($)
                    <input type="number" name="monto_planificado_acumulado" min="0" step="1" value="<?= e(old('monto_planificado_acumulado')) ?>" required>
                </label>
                <button type="submit" class="btn-primary">Agregar punto</button>
            </form>
        <?php endif; ?>
    </section>

    <section class="report-card">
        <div class="section-header">
            <div>
                <h2>Detalle de gastos aprobados</h2>
                <p><?= e($gastos->count()) ?> registros asociados al proyecto.</p>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Gasto</th>
                    <th>Tipo gasto</th>
                    <th>Clase</th>
                    <th>Fecha</th>
                    <th>Monto</th>
                </tr>
            </thead>

            <tbody>
                <?php if (count($gastos) > 0): ?>
                <?php foreach ($gastos as $g): ?>
                    <tr>
                        <td>
                            <strong><?= e($g->nombre_gasto) ?></strong>
                            <span><?= e($g->descripcion_gasto ?: 'Sin descripción') ?></span>
                        </td>

                        <td><?= e($g->tipoGasto?->nombre_tipo_gasto ?? 'Sin gasto') ?></td>

                        <td><?= $g->esExtra() ? 'Adicional' : 'Presupuestado' ?></td>

                        <td><?= e($g->fecha_gasto?->format('Y-m-d')) ?></td>

                        <td class="money">
                            <?= pesos($g->monto_gasto) ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="empty">
                            No existen gastos registrados.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </section>

</main>

<?= view('pie') ?>
