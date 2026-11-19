<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte — <?= e($proyecto->nombre_proyecto) ?></title>
    <?= view('pdf_estilos') ?>
</head>
<body>

<table class="cabecera" width="100%">
    <tr>
        <td>
            <div class="muted">REPORTE FINANCIERO DEL PROYECTO</div>
            <h1><?= e($proyecto->nombre_proyecto) ?></h1>
            <div class="muted">
                Inicio: <?= e($proyecto->fecha_inicio_proyecto?->format('d/m/Y')) ?>
                · Término: <?= e($proyecto->fecha_termino_proyecto?->format('d/m/Y') ?? 'No asignada') ?>
                · Estado: <?= e($proyecto->estadoTexto()) ?>
            </div>
        </td>
        <td style="text-align:right;">
            <div class="muted">Fecha de emisión</div>
            <strong><?= e(now()->format('d/m/Y')) ?></strong>
        </td>
    </tr>
</table>

<table class="kpis">
    <tr>
        <td><div class="muted">Presupuesto inicial</div><div class="valor"><?= pesos($presupuesto) ?></div></td>
        <td><div class="muted">Total gastado</div><div class="valor"><?= pesos($totalGastado) ?></div></td>
        <td><div class="muted">Disponible</div><div class="valor"><?= pesos($disponible) ?></div></td>
        <td><div class="muted">Costos extra</div><div class="valor"><?= pesos($totalExtra) ?></div></td>
    </tr>
</table>

<h2>Avance presupuestario: <?= e(round($porcentajeUsado, 1)) ?>%</h2>
<div class="barra"><div style="width: <?= e($porcentajeBarra) ?>%;"></div></div>

<?php if ($alertaPresupuesto === 'excedido'): ?>
    <div class="alerta">Presupuesto excedido en <?= pesos(abs($disponible)) ?>.</div>
<?php elseif ($alertaPresupuesto === 'critico'): ?>
    <div class="alerta">Desviación presupuestaria: se usó el <?= e(round($porcentajeUsado, 1)) ?>% del presupuesto.</div>
<?php endif; ?>

<h2>Desviación por ítem</h2>
<table class="datos">
    <thead>
        <tr><th>Ítem</th><th class="monto">Presupuestado</th><th class="monto">Gastado</th><th class="monto">Avance</th><th class="monto">Desviación</th><th>Estado</th></tr>
    </thead>
    <tbody>
        <?php foreach ($comparativo['filas'] as $fila): ?>
            <tr>
                <td><?= e($fila['item']->descripcion_item) ?></td>
                <td class="monto"><?= pesos($fila['presupuestado']) ?></td>
                <td class="monto"><?= pesos($fila['gastado']) ?></td>
                <td class="monto"><?= e(round($fila['porcentaje'], 1)) ?>%</td>
                <td class="monto"><?= $fila['desviacion'] > 0 ? '+' : '' ?><?= pesos($fila['desviacion']) ?></td>
                <td><?= e(\App\Services\ResumenPresupuesto::ESTADOS[$fila['estado']]['texto']) ?></td>
            </tr>
        <?php endforeach; ?>
        <tr>
            <td>Gastos adicionales</td>
            <td class="monto">—</td>
            <td class="monto"><?= pesos($comparativo['adicionales']['gastado']) ?></td>
            <td class="monto">—</td>
            <td class="monto">+<?= pesos($comparativo['adicionales']['gastado']) ?></td>
            <td>—</td>
        </tr>
        <tr>
            <td><strong>Total</strong></td>
            <td class="monto"><strong><?= pesos($comparativo['presupuesto']) ?></strong></td>
            <td class="monto"><strong><?= pesos($comparativo['gastado']) ?></strong></td>
            <td class="monto"><?= e(round($comparativo['porcentaje'], 1)) ?>%</td>
            <td class="monto"><strong><?= $comparativo['desviacion'] > 0 ? '+' : '' ?><?= pesos($comparativo['desviacion']) ?></strong></td>
            <td><?= e(\App\Services\ResumenPresupuesto::ESTADOS[$comparativo['estado']]['texto']) ?></td>
        </tr>
    </tbody>
</table>

<h2>Curva S</h2>
<img src="<?= e($grafico) ?>" style="width: 100%;" alt="Curva S">
<div class="muted">
    <?= e($curva['plan_registrado'] ? 'Plan según los puntos registrados.' : 'Plan estimado con una curva en S entre el inicio y el término del proyecto.') ?>
</div>

<?php if ($evaluaciones->isNotEmpty()): ?>
    <h2>Plan de la Curva S</h2>
    <table class="datos">
        <thead>
            <tr><th>Fecha</th><th class="monto">Planificado</th><th class="monto">Real</th><th class="monto">Desviación</th></tr>
        </thead>
        <tbody>
            <?php foreach ($evaluaciones as $evaluacion): ?>
                <tr>
                    <td><?= e($evaluacion->fecha_evaluacion->format('d/m/Y')) ?></td>
                    <td class="monto"><?= pesos($evaluacion->monto_planificado_acumulado) ?></td>
                    <?php if ($evaluacion->realAcumulado() === null): ?>
                        <td class="monto">Pendiente</td>
                        <td class="monto">—</td>
                    <?php else: ?>
                        <td class="monto"><?= pesos($evaluacion->realAcumulado()) ?></td>
                        <td class="monto"><?= e($evaluacion->desviacion() > 0 ? '+' : '') ?><?= pesos($evaluacion->desviacion()) ?></td>
                    <?php endif; ?>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<h2>Detalle de gastos aprobados (<?= e($gastos->count()) ?>)</h2>
<table class="datos">
    <thead>
        <tr><th>Gasto</th><th>Tipo gasto</th><th>Clase</th><th>Fecha</th><th class="monto">Monto</th></tr>
    </thead>
    <tbody>
        <?php if (count($gastos) > 0): ?>
        <?php foreach ($gastos as $g): ?>
            <tr>
                <td><strong><?= e($g->nombre_gasto) ?></strong><?php if ($g->descripcion_gasto): ?><br><span class="muted"><?= e($g->descripcion_gasto) ?></span><?php endif; ?></td>
                <td><?= e($g->tipoGasto?->nombre_tipo_gasto ?? 'Sin gasto') ?></td>
                <td><?= $g->esExtra() ? 'Adicional' : 'Presupuestado' ?></td>
                <td><?= e($g->fecha_gasto?->format('d/m/Y')) ?></td>
                <td class="monto"><?= pesos($g->monto_gasto) ?></td>
            </tr>
        <?php endforeach; ?>
        <?php else: ?>
            <tr><td colspan="5" class="muted">No existen gastos registrados.</td></tr>
        <?php endif; ?>
    </tbody>
</table>

<div class="pie">ConstructControl · Reporte generado el <?= e(now()->format('d/m/Y H:i')) ?></div>

</body>
</html>
