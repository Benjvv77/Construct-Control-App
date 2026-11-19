<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gastos adicionales — <?= e($proyecto->nombre_proyecto) ?></title>
    <?= view('pdf_estilos') ?>
</head>
<body>

<table class="cabecera" width="100%">
    <tr>
        <td>
            <div class="muted">REPORTE DE GASTOS ADICIONALES</div>
            <h1><?= e($proyecto->nombre_proyecto) ?></h1>
            <div class="muted">Costos no contemplados en el presupuesto. Solo gastos aprobados.</div>
        </td>
        <td style="text-align:right;">
            <div class="muted">Fecha de emisión</div>
            <strong><?= now()->format('d/m/Y') ?></strong>
        </td>
    </tr>
</table>

<table class="kpis">
    <tr>
        <td><div class="muted">Total adicional</div><div class="valor"><?= pesos($total) ?></div></td>
        <td><div class="muted">Sobre el presupuesto</div><div class="valor"><?= e(round($porcentaje, 1)) ?>%</div></td>
        <td><div class="muted">Cantidad de gastos</div><div class="valor"><?= count($gastos) ?></div></td>
        <td><div class="muted">Por aprobar</div><div class="valor"><?= $pendientes ?></div></td>
    </tr>
</table>

<h2>Por tipo de gasto</h2>
<table class="datos">
    <thead><tr><th>Tipo</th><th class="monto">Gastos</th><th class="monto">Total</th><th class="monto">% del adicional</th></tr></thead>
    <tbody>
        <?php foreach ($porTipo as $tipo): ?>
            <tr>
                <td><?= e($tipo['tipo']) ?></td>
                <td class="monto"><?= $tipo['cantidad'] ?></td>
                <td class="monto"><?= pesos($tipo['total']) ?></td>
                <td class="monto"><?= $total > 0 ? e(round($tipo['total'] / $total * 100, 1)) : 0 ?>%</td>
            </tr>
        <?php endforeach; ?>
        <?php if (count($porTipo) === 0): ?>
            <tr><td colspan="4" class="muted">El proyecto no tiene gastos adicionales aprobados.</td></tr>
        <?php endif; ?>
    </tbody>
</table>

<h2>Detalle</h2>
<table class="datos">
    <thead><tr><th>Fecha</th><th>Gasto</th><th>Justificación</th><th>Tipo</th><th class="monto">Monto</th></tr></thead>
    <tbody>
        <?php foreach ($gastos as $g): ?>
            <tr>
                <td><?= e($g->fecha_gasto?->format('d/m/Y')) ?></td>
                <td><strong><?= e($g->nombre_gasto) ?></strong></td>
                <td><?= e($g->descripcion_gasto ?: '—') ?></td>
                <td><?= e($g->tipoGasto?->nombre_tipo_gasto) ?></td>
                <td class="monto"><?= pesos($g->monto_gasto) ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<div class="pie">ConstructControl · Reporte generado el <?= now()->format('d/m/Y H:i') ?></div>

</body>
</html>
