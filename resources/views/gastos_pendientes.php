<?php
/**
 * HU10: gastos que esperan la revisión del Administrador.
 *
 * @var \Illuminate\Pagination\LengthAwarePaginator $gastos
 */
?>
<?= view('encabezado', ['titulo' => 'ConstructControl — Gastos por aprobar', 'estilos' => ['proyectos']]) ?>

<main class="main">

    <section class="hero">
        <div class="hero-content">
            <span class="eyebrow">Revisión</span>
            <h1>Gastos por aprobar</h1>
            <p>
                Los gastos que registran los supervisores no afectan el presupuesto ni el inventario hasta que los apruebas.
                Si rechazas uno, indica el motivo para que puedan corregirlo.
            </p>
        </div>
    </section>

    <?= view('alertas') ?>

    <section class="projects-card">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Gasto</th>
                        <th>Proyecto</th>
                        <th>Ítem / tipo</th>
                        <th>Monto</th>
                        <th>Registrado</th>
                        <th>Boleta</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (count($gastos) > 0): ?>
                    <?php foreach ($gastos as $g): ?>
                        <tr>
                            <td>
                                <div class="project-info">
                                    <div class="project-icon"><?= $g->esExtra() ? '⚠️' : '🧱' ?></div>
                                    <div>
                                        <strong><?= e($g->nombre_gasto) ?></strong>
                                        <span><?= e($g->descripcion_gasto ?: 'Sin descripción') ?></span>
                                        <?php if ($g->esDeMateriales() && (float) $g->cantidad_gasto > 0): ?>
                                            <span><?= numero($g->cantidad_gasto) ?> <?= e($g->unidad_gasto) ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <a href="<?= e(route('proyectos.show', $g->proyecto)) ?>" class="enlace"><?= e($g->proyecto->nombre_proyecto) ?></a>
                            </td>
                            <td>
                                <?php if ($g->esExtra()): ?>
                                    <span class="status alerta">Adicional</span>
                                    <br><small><?= e($g->tipoGasto?->nombre_tipo_gasto) ?></small>
                                <?php else: ?>
                                    <span class="status activo">Presupuestado</span>
                                    <br><small><?= e($g->item?->descripcion_item) ?></small>
                                <?php endif; ?>
                            </td>
                            <td class="money-cell"><?= pesos($g->monto_gasto) ?></td>
                            <td>
                                <?= e($g->fecha_gasto?->format('Y-m-d')) ?>
                                <br><small><?= e($g->registradoPor?->nombreCompleto() ?? '—') ?></small>
                            </td>
                            <td>
                                <?php if ($g->documento_gasto): ?>
                                    <a href="<?= e(route('gastos.documento', [$g->proyecto, $g])) ?>" target="_blank" rel="noopener" class="btn-actions">📄 Ver</a>
                                <?php else: ?>
                                    <small>Sin boleta</small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="acciones-fila">
                                    <form action="<?= e(route('gastos.aprobar', $g)) ?>" method="POST" class="form-inline">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="btn-actions">✅ Aprobar</button>
                                    </form>
                                    <button type="button" class="btn-actions btn-delete"
                                        onclick="abrirRechazo('<?= e(route('gastos.rechazar', $g)) ?>', <?= e(json_encode($g->nombre_gasto)) ?>)">
                                        ✖ Rechazar
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" class="empty">No hay gastos esperando aprobación. 🎉</td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="pagination">
            <?= view('paginacion', ['paginador' => $gastos, 'nombre' => 'gastos pendientes']) ?>
        </div>
    </section>

</main>

<div class="modal-overlay" id="modalRechazo">
    <div class="modal-box">
        <div class="modal-header">
            <div>
                <h2 id="rechazoTitulo">Rechazar gasto</h2>
                <p>Quien lo registró verá el motivo y podrá corregirlo.</p>
            </div>
            <button class="close-btn" onclick="cerrarRechazo()">✕</button>
        </div>

        <form method="POST" id="formRechazo">
            <?= csrf_field() ?>
            <div class="form-grid">
                <div class="form-group full">
                    <label>Motivo del rechazo</label>
                    <textarea name="motivo_rechazo" rows="3" maxlength="1000" required></textarea>
                </div>
            </div>

            <div class="modal-actions">
                <button type="button" class="btn-secondary" onclick="cerrarRechazo()">Cancelar</button>
                <button type="submit" class="btn-primary">Rechazar gasto</button>
            </div>
        </form>
    </div>
</div>

<script>
function abrirRechazo(url, nombre) {
    document.getElementById("formRechazo").action = url;
    document.getElementById("rechazoTitulo").textContent = "Rechazar: " + nombre;
    document.getElementById("modalRechazo").classList.add("show");
}

function cerrarRechazo() {
    document.getElementById("modalRechazo").classList.remove("show");
}

document.getElementById("modalRechazo").addEventListener("click", function (e) {
    if (e.target === this) cerrarRechazo();
});
</script>

<?= view('pie') ?>
