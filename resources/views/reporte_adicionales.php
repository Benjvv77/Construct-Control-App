<?php
/**
 * HU17: gastos adicionales (no presupuestados) aprobados de un proyecto.
 *
 * @var \App\Models\Proyecto $proyecto
 * @var \Illuminate\Support\Collection $gastos
 * @var \Illuminate\Support\Collection $porTipo
 */
?>
<?= view('encabezado', ['titulo' => 'ConstructControl — Gastos adicionales', 'estilos' => ['proyectos']]) ?>

<main class="main">

    <section class="hero">
        <div class="hero-content">
            <span class="eyebrow">Reportes · <?= e($proyecto->nombre_proyecto) ?></span>
            <h1>Gastos adicionales</h1>
            <p>Los costos imprevistos que no estaban en el presupuesto y cuánto pesan en la obra. Solo gastos aprobados.</p>
        </div>

        <div class="acciones-fila">
            <a href="<?= e(route('proyectos.show', $proyecto)) ?>" class="btn-hero">← Volver al proyecto</a>
            <a href="<?= e(route('reportes.adicionales.pdf', $proyecto)) ?>" class="btn-hero">📄 Exportar PDF</a>
        </div>
    </section>

    <section class="kpis">
        <div class="kpi-card">
            <div class="kpi-icon warn">⚠️</div>
            <div>
                <p>Total adicional</p>
                <h2 class="money-text"><?= pesos($total) ?></h2>
                <span><?= count($gastos) ?> gastos</span>
            </div>
        </div>
        <div class="kpi-card">
            <div class="kpi-icon money">💰</div>
            <div>
                <p>Sobre el presupuesto</p>
                <h2><?= e(round($porcentaje, 1)) ?>%</h2>
                <span>de <?= pesos($presupuesto) ?></span>
            </div>
        </div>
        <div class="kpi-card">
            <div class="kpi-icon">🧾</div>
            <div>
                <p>Sobre lo gastado</p>
                <h2><?= $totalGastado > 0 ? e(round($total / $totalGastado * 100, 1)) : 0 ?>%</h2>
                <span>de <?= pesos($totalGastado) ?></span>
            </div>
        </div>
        <div class="kpi-card">
            <div class="kpi-icon green">⏳</div>
            <div>
                <p>Por aprobar</p>
                <h2><?= $pendientes ?></h2>
                <span>Adicionales pendientes</span>
            </div>
        </div>
    </section>

    <section class="projects-card">
        <div class="toolbar"><strong>Por tipo de gasto</strong></div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Tipo</th><th>Gastos</th><th>Total</th><th>% del adicional</th></tr></thead>
                <tbody>
                <?php if (count($porTipo) > 0): ?>
                    <?php foreach ($porTipo as $tipo): ?>
                        <tr>
                            <td><?= e($tipo['tipo']) ?></td>
                            <td><?= $tipo['cantidad'] ?></td>
                            <td class="money-cell"><?= pesos($tipo['total']) ?></td>
                            <td><?= $total > 0 ? e(round($tipo['total'] / $total * 100, 1)) : 0 ?>%</td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="4" class="empty">El proyecto no tiene gastos adicionales aprobados.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

    <section class="projects-card" style="margin-top:20px;">
        <div class="toolbar"><strong>Detalle</strong></div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Fecha</th><th>Gasto</th><th>Justificación</th><th>Tipo</th><th>Monto</th><th>Registró / aprobó</th></tr></thead>
                <tbody>
                <?php if (count($gastos) > 0): ?>
                    <?php foreach ($gastos as $g): ?>
                        <tr>
                            <td><?= e($g->fecha_gasto?->format('Y-m-d')) ?></td>
                            <td><strong><?= e($g->nombre_gasto) ?></strong></td>
                            <td><?= e($g->descripcion_gasto ?: '—') ?></td>
                            <td><?= e($g->tipoGasto?->nombre_tipo_gasto) ?></td>
                            <td class="money-cell"><?= pesos($g->monto_gasto) ?></td>
                            <td><small><?= e($g->registradoPor?->nombreCompleto() ?? '—') ?> / <?= e($g->revisadoPor?->nombreCompleto() ?? '—') ?></small></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="6" class="empty">Sin gastos adicionales aprobados.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

</main>

<?= view('pie') ?>
