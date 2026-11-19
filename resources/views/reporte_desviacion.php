<?php
/**
 * HU16: desviación presupuestaria de todas las obras visibles para el usuario.
 *
 * @var \Illuminate\Support\Collection $filas  cada una: ['proyecto' => Proyecto, …ResumenPresupuesto::calcular()]
 */
$estados = \App\Services\ResumenPresupuesto::ESTADOS;
?>
<?= view('encabezado', ['titulo' => 'ConstructControl — Desviación presupuestaria', 'estilos' => ['proyectos']]) ?>

<main class="main">

    <section class="hero">
        <div class="hero-content">
            <span class="eyebrow">Reportes</span>
            <h1>Desviación presupuestaria</h1>
            <p>
                Cuánto se ha gastado en cada obra frente a su presupuesto, para detectar a tiempo los sobrecostos.
                Solo cuentan los gastos aprobados.
            </p>
        </div>

        <a href="<?= e(route('reportes.desviacion.pdf')) ?>" class="btn-hero">📄 Exportar PDF</a>
    </section>

    <section class="kpis">
        <div class="kpi-card">
            <div class="kpi-icon money">💰</div>
            <div>
                <p>Presupuesto total</p>
                <h2 class="money-text"><?= pesos($presupuesto) ?></h2>
                <span><?= count($filas) ?> obras</span>
            </div>
        </div>
        <div class="kpi-card">
            <div class="kpi-icon">🧾</div>
            <div>
                <p>Gastado total</p>
                <h2 class="money-text"><?= pesos($gastado) ?></h2>
                <span><?= $presupuesto > 0 ? e(round($gastado / $presupuesto * 100, 1)) : 0 ?>% del presupuesto</span>
            </div>
        </div>
        <div class="kpi-card">
            <div class="kpi-icon warn">⚠️</div>
            <div>
                <p>Obras en riesgo</p>
                <h2><?= $enRiesgo ?></h2>
                <span>Sobre el <?= \App\Models\Proyecto::UMBRAL_ALERTA ?>% del presupuesto</span>
            </div>
        </div>
        <div class="kpi-card">
            <div class="kpi-icon">🔥</div>
            <div>
                <p>Obras con sobrecosto</p>
                <h2><?= $conSobrecosto ?></h2>
                <span><?= pesos($adicional) ?> en gastos adicionales</span>
            </div>
        </div>
    </section>

    <section class="projects-card">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Obra</th>
                        <th>Presupuesto</th>
                        <th>Gastado</th>
                        <th>Adicionales</th>
                        <th>Avance</th>
                        <th>Desviación</th>
                        <th>Ítems con problemas</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (count($filas) > 0): ?>
                    <?php foreach ($filas as $fila): ?>
                        <tr>
                            <td>
                                <a href="<?= e(route('proyectos.reporte', $fila['proyecto'])) ?>" class="enlace"><?= e($fila['proyecto']->nombre_proyecto) ?></a>
                                <br><small><?= e($fila['proyecto']->estadoTexto()) ?></small>
                            </td>
                            <td class="money-cell"><?= pesos($fila['presupuesto']) ?></td>
                            <td class="money-cell"><?= pesos($fila['gastado']) ?></td>
                            <td class="money-cell"><?= pesos($fila['adicionales']['gastado']) ?></td>
                            <td>
                                <div class="barra-mini"><div class="<?= e($fila['estado']) ?>" style="width: <?= (int) min(100, round($fila['porcentaje'])) ?>%;"></div></div>
                                <small><?= e(round($fila['porcentaje'], 1)) ?>%</small>
                            </td>
                            <td class="money-cell <?= $fila['desviacion'] > 0 ? 'texto-rojo' : 'texto-verde' ?>">
                                <?= $fila['desviacion'] > 0 ? '+' : '' ?><?= pesos($fila['desviacion']) ?>
                            </td>
                            <td><?= $fila['items_con_problemas'] ?> de <?= count($fila['filas']) ?></td>
                            <td><span class="status <?= e($estados[$fila['estado']]['clase']) ?>"><?= e($estados[$fila['estado']]['texto']) ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="8" class="empty">No hay obras para mostrar.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

</main>

<?= view('pie') ?>
