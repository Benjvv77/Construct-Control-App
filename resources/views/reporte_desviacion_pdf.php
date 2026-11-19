<?php $estados = \App\Services\ResumenPresupuesto::ESTADOS; ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Desviación presupuestaria</title>
    <?= view('pdf_estilos') ?>
</head>
<body>

<table class="cabecera" width="100%">
    <tr>
        <td>
            <div class="muted">REPORTE DE DESVIACIÓN PRESUPUESTARIA</div>
            <h1>Todas las obras</h1>
            <div class="muted">Solo cuentan los gastos aprobados.</div>
        </td>
        <td style="text-align:right;">
            <div class="muted">Fecha de emisión</div>
            <strong><?= now()->format('d/m/Y') ?></strong>
        </td>
    </tr>
</table>

<table class="kpis">
    <tr>
        <td><div class="muted">Presupuesto total</div><div class="valor"><?= pesos($presupuesto) ?></div></td>
        <td><div class="muted">Gastado total</div><div class="valor"><?= pesos($gastado) ?></div></td>
        <td><div class="muted">Obras en riesgo</div><div class="valor"><?= $enRiesgo ?></div></td>
        <td><div class="muted">Obras con sobrecosto</div><div class="valor"><?= $conSobrecosto ?></div></td>
    </tr>
</table>

<h2>Detalle por obra</h2>
<table class="datos">
    <thead>
        <tr>
            <th>Obra</th><th class="monto">Presupuesto</th><th class="monto">Gastado</th><th class="monto">Adicionales</th>
            <th class="monto">Avance</th><th class="monto">Desviación</th><th>Ítems con problemas</th><th>Estado</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($filas as $fila): ?>
            <tr>
                <td><strong><?= e($fila['proyecto']->nombre_proyecto) ?></strong></td>
                <td class="monto"><?= pesos($fila['presupuesto']) ?></td>
                <td class="monto"><?= pesos($fila['gastado']) ?></td>
                <td class="monto"><?= pesos($fila['adicionales']['gastado']) ?></td>
                <td class="monto"><?= e(round($fila['porcentaje'], 1)) ?>%</td>
                <td class="monto"><?= $fila['desviacion'] > 0 ? '+' : '' ?><?= pesos($fila['desviacion']) ?></td>
                <td><?= $fila['items_con_problemas'] ?> de <?= count($fila['filas']) ?></td>
                <td><?= e($estados[$fila['estado']]['texto']) ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<?php foreach ($filas as $fila): ?>
    <?php if ($fila['items_con_problemas'] > 0): ?>
        <h2><?= e($fila['proyecto']->nombre_proyecto) ?>: ítems en riesgo o con sobrecosto</h2>
        <table class="datos">
            <thead>
                <tr><th>Ítem</th><th class="monto">Presupuestado</th><th class="monto">Gastado</th><th class="monto">Desviación</th><th>Estado</th></tr>
            </thead>
            <tbody>
                <?php foreach ($fila['filas'] as $item): ?>
                    <?php if ($item['estado'] !== 'ok'): ?>
                        <tr>
                            <td><?= e($item['item']->descripcion_item) ?></td>
                            <td class="monto"><?= pesos($item['presupuestado']) ?></td>
                            <td class="monto"><?= pesos($item['gastado']) ?></td>
                            <td class="monto"><?= $item['desviacion'] > 0 ? '+' : '' ?><?= pesos($item['desviacion']) ?></td>
                            <td><?= e($estados[$item['estado']]['texto']) ?></td>
                        </tr>
                    <?php endif; ?>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
<?php endforeach; ?>

<div class="pie">ConstructControl · Reporte generado el <?= now()->format('d/m/Y H:i') ?></div>

</body>
</html>
