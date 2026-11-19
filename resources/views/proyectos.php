<?php $esAdmin = auth()->user()->esAdministrador(); ?>
<?= view('encabezado', ['titulo' => 'ConstructControl — Proyectos', 'estilos' => ['proyectos']]) ?>

<main class="main">

    <section class="hero">
        <div class="hero-content">
            <span class="eyebrow">Panel de administración</span>
            <h1>Gestión de proyectos</h1>
            <p>
                Administra y monitorea presupuestos de construcción de todas tus obras desde un solo lugar.
            </p>
        </div>

        <?php if ($esAdmin): ?>
            <button class="btn-hero" onclick="abrirModal()">
                + Nuevo proyecto
            </button>
        <?php endif; ?>
    </section>

    <?= view('alertas') ?>

    <section class="kpis">
        <div class="kpi-card">
            <div class="kpi-icon">📁</div>
            <div>
                <p>Total proyectos</p>
                <h2><?= e($totalProyectos) ?></h2>
                <span>Registrados</span>
            </div>
        </div>

        <div class="kpi-card">
            <div class="kpi-icon green">📈</div>
            <div>
                <p>Proyectos activos</p>
                <h2><?= e($proyectosActivos) ?></h2>
                <span>
                    <?= e($totalProyectos > 0 ? round(($proyectosActivos / $totalProyectos) * 100, 1) : 0) ?>% del total
                </span>
            </div>
        </div>

        <div class="kpi-card">
            <div class="kpi-icon warn">⚠️</div>
            <div>
                <p>Con alerta</p>
                <h2><?= e($proyectosAlerta) ?></h2>
                <span>
                    <?= e($totalProyectos > 0 ? round(($proyectosAlerta / $totalProyectos) * 100, 1) : 0) ?>% del total
                </span>
            </div>
        </div>

        <div class="kpi-card">
            <div class="kpi-icon money">💰</div>
            <div>
                <p>Presupuesto total</p>
                <h2 class="money-text"><?= pesos($presupuestoTotal) ?></h2>
                <span>CLP</span>
            </div>
        </div>
    </section>

    <section class="projects-card">

        <div class="toolbar">
            <form method="GET" action="<?= e(route('proyectos.index')) ?>" class="search-box" role="search">
                <button type="submit" aria-label="Buscar">🔍</button>
                <input type="search" name="buscar" value="<?= e($filtros['buscar'] ?? '') ?>" placeholder="Buscar proyecto...">
                <?php if (! empty($filtros['estado'])): ?>
                    <input type="hidden" name="estado" value="<?= e($filtros['estado']) ?>">
                <?php endif; ?>
            </form>

            <div class="filters">
                <?php foreach (['' => 'Todos', 'activos' => 'Activos', 'alertas' => 'Alertas', 'pausados' => 'Pausados', 'finalizados' => 'Finalizados'] as $valor => $etiqueta): ?>
                    <a
                        href="<?= e(route('proyectos.index', array_filter(['buscar' => $filtros['buscar'] ?? null, 'estado' => $valor ?: null]))) ?>"
                        class="filter <?= e(($filtros['estado'] ?? '') === $valor ? 'active' : '') ?>">
                        <?= e($etiqueta) ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Proyecto</th>
                        <th>Inicio</th>
                        <th>Término</th>
                        <th>Presupuesto</th>
                        <th>Usado</th>
                        <th>Progreso</th>
                        <th>Estado</th>
                        <th>Responsable</th>
                        <th>Acciones</th>
                    </tr>
                </thead>

                <tbody>
                <?php if (count($proyectos) > 0): ?>
                <?php foreach ($proyectos as $p): ?>
                    <?php
                        $gastado = (float) ($p->total_gastado ?? 0);
                        $progreso = (int) min(100, round($p->porcentajeUsado($gastado)));
                        $alerta = $p->alertaPresupuesto($gastado);
                    ?>

                    <tr>
                        <td>
                            <div class="project-info">
                                <div class="project-icon">🚧</div>
                                <div>
                                    <strong><?= e($p->nombre_proyecto) ?></strong>
                                    <span><?= e($p->descripcion_proyecto ?: 'Sin descripción') ?></span>
                                </div>
                            </div>
                        </td>

                        <td><?= e($p->fecha_inicio_proyecto?->format('Y-m-d')) ?></td>

                        <td><?= e($p->fecha_termino_proyecto?->format('Y-m-d') ?? 'No asignada') ?></td>

                        <td class="money-cell">
                            <?= pesos($p->presupuesto_proyecto) ?>
                        </td>

                        <td class="money-cell">
                            <?= pesos($gastado) ?>
                            <small>(<?= e($progreso) ?>%)</small>
                        </td>

                        <td>
                            <div class="progress">
                                <div class="progress-bar">
                                    <div class="progress-fill <?= e($progreso >= 90 ? 'danger' : ($progreso >= 70 ? 'warn' : '')) ?>" style="width: <?= e($progreso) ?>%;"></div>
                                </div>
                                <span><?= e($progreso) ?>%</span>
                            </div>
                        </td>

                        <td>
                            <span class="status <?= e($p->estadoClase()) ?>">
                                <?= e($p->estadoTexto()) ?>
                            </span>
                            <?php if ($alerta): ?>
                                <br><span class="alerta-presupuesto"><?= e($alerta === 'excedido' ? 'Presupuesto excedido' : 'Sobre '.\App\Models\Proyecto::UMBRAL_ALERTA.'% del presupuesto') ?></span>
                            <?php endif; ?>
                        </td>

                        <td>
                            <?= e($p->usuario->nombreCompleto()) ?>
                        </td>

                        <td>
                            <div class="action-group">

                                <a href="<?= e(route('proyectos.show', $p)) ?>" class="btn-actions">
                                    👁️ Ver
                                </a>

                                <?php if ($esAdmin): ?>
                                <button
                                    type="button"
                                    class="btn-actions btn-edit"
                                    data-proyecto="<?= e(json_encode([
                                        'url' => route('proyectos.update', $p),
                                        'nombre_proyecto' => $p->nombre_proyecto,
                                        'descripcion_proyecto' => $p->descripcion_proyecto,
                                        'fecha_inicio_proyecto' => $p->fecha_inicio_proyecto?->format('Y-m-d'),
                                        'fecha_termino_proyecto' => $p->fecha_termino_proyecto?->format('Y-m-d'),
                                        'estado_proyecto' => $p->estado_proyecto,
                                        'id_usuario' => $p->id_usuario,
                                    ])) ?>"
                                    onclick="abrirEditarProyecto(JSON.parse(this.dataset.proyecto))">
                                    ✏️ Editar
                                </button>

                                <form
                                    action="<?= e(route('proyectos.destroy', $p)) ?>"
                                    method="POST"
                                    class="form-inline"
                                    onsubmit="return confirm('¿Seguro que deseas eliminar este proyecto? Se borrarán también sus gastos e inventario. Esta acción no se puede deshacer.')">
                                    <?= csrf_field() ?>
                                    <?= method_field('DELETE') ?>
                                    <button type="submit" class="btn-actions btn-delete">❌ Eliminar</button>
                                </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="9" class="empty">
                            <?= e(empty($filtros) ? 'No hay proyectos registrados.' : 'No hay proyectos que coincidan con la búsqueda.') ?>
                        </td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="pagination">
            <?= view('paginacion', ['paginador' => $proyectos, 'nombre' => 'proyectos']) ?>
        </div>

    </section>

</main>

<div class="modal-overlay" id="modalNuevoProyecto">
    <div class="modal-box">

        <div class="modal-header">
            <div>
                <h2>Nuevo Proyecto</h2>
                <p>Ingresa la información del presupuesto del proyecto</p>
            </div>

            <button class="close-btn" onclick="cerrarModal()">✕</button>
        </div>

        <form action="<?= e(route('proyectos.store')) ?>" method="POST">
            <?= csrf_field() ?>

            <div class="form-grid">

                <div class="form-group full">
                    <label>Nombre del Proyecto</label>
                    <input type="text" name="nombre_proyecto" value="<?= e(old('nombre_proyecto')) ?>" required>
                </div>

                <div class="form-group">
                    <label>Fecha Inicio</label>
                    <input type="date" name="fecha_inicio_proyecto" value="<?= e(old('fecha_inicio_proyecto')) ?>" required>
                </div>

                <div class="form-group">
                    <label>Fecha Término</label>
                    <input type="date" name="fecha_termino_proyecto" value="<?= e(old('fecha_termino_proyecto')) ?>">
                </div>

                <div class="form-group">
                    <label>Estado</label>
                    <select name="estado_proyecto">
                        <?php foreach (\App\Models\Proyecto::ESTADOS as $valor => $estado): ?>
                            <option value="<?= e($valor) ?>" <?= (old('estado_proyecto', 1) == $valor) ? 'selected' : '' ?>><?= e($estado['texto']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <p class="ayuda-campo form-group full">El presupuesto se arma después, agregando sus ítems desde el detalle del proyecto.</p>

                <?php if ($responsables->isNotEmpty()): ?>
                    <div class="form-group">
                        <label>Responsable</label>
                        <select name="id_usuario">
                            <?php foreach ($responsables as $responsable): ?>
                                <option value="<?= e($responsable->id_usuario) ?>" <?= (old('id_usuario', auth()->id()) == $responsable->id_usuario) ? 'selected' : '' ?>><?= e($responsable->nombreCompleto()) ?> — <?= e($responsable->rol_usuario) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endif; ?>

                <div class="form-group full">
                    <label>Descripción</label>
                    <textarea name="descripcion_proyecto" rows="4"><?= e(old('descripcion_proyecto')) ?></textarea>
                </div>

            </div>

            <div class="modal-actions">
                <button type="button" class="btn-secondary" onclick="cerrarModal()">Cancelar</button>
                <button type="submit" class="btn-primary">Guardar Proyecto</button>
            </div>

        </form>
    </div>
</div>

<div class="modal-overlay" id="modalEditarProyecto">
    <div class="modal-box">

        <div class="modal-header">
            <div>
                <h2>Editar Proyecto</h2>
                <p>Actualiza la información del proyecto seleccionado</p>
            </div>

            <button class="close-btn" onclick="cerrarEditarProyecto()">✕</button>
        </div>

        <form method="POST" id="formEditarProyecto">
            <?= csrf_field() ?>
            <?= method_field('PUT') ?>

            <div class="form-grid">

                <div class="form-group full">
                    <label>Nombre del Proyecto</label>
                    <input type="text" name="nombre_proyecto" id="edit_nombre_proyecto" required>
                </div>

                <div class="form-group full">
                    <label>Descripción</label>
                    <textarea name="descripcion_proyecto" id="edit_descripcion_proyecto" rows="4"></textarea>
                </div>

                <div class="form-group">
                    <label>Fecha Inicio</label>
                    <input type="date" name="fecha_inicio_proyecto" id="edit_fecha_inicio_proyecto" required>
                </div>

                <div class="form-group">
                    <label>Fecha Término</label>
                    <input type="date" name="fecha_termino_proyecto" id="edit_fecha_termino_proyecto">
                </div>

                <div class="form-group">
                    <label>Estado</label>
                    <select name="estado_proyecto" id="edit_estado_proyecto">
                        <?php foreach (\App\Models\Proyecto::ESTADOS as $valor => $estado): ?>
                            <option value="<?= e($valor) ?>"><?= e($estado['texto']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>


                <?php if ($responsables->isNotEmpty()): ?>
                    <div class="form-group full">
                        <label>Responsable</label>
                        <select name="id_usuario" id="edit_id_usuario">
                            <?php foreach ($responsables as $responsable): ?>
                                <option value="<?= e($responsable->id_usuario) ?>"><?= e($responsable->nombreCompleto()) ?> — <?= e($responsable->rol_usuario) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endif; ?>

            </div>

            <div class="modal-actions">
                <button type="button" class="btn-secondary" onclick="cerrarEditarProyecto()">Cancelar</button>
                <button type="submit" class="btn-primary">Actualizar Proyecto</button>
            </div>

        </form>
    </div>
</div>

<script>
function abrirModal() {
    document.getElementById("modalNuevoProyecto").classList.add("show");
}

function cerrarModal() {
    document.getElementById("modalNuevoProyecto").classList.remove("show");
}

function abrirEditarProyecto(proyecto) {
    document.getElementById("formEditarProyecto").action = proyecto.url;
    document.getElementById("edit_nombre_proyecto").value = proyecto.nombre_proyecto ?? "";
    document.getElementById("edit_descripcion_proyecto").value = proyecto.descripcion_proyecto ?? "";
    document.getElementById("edit_fecha_inicio_proyecto").value = proyecto.fecha_inicio_proyecto ?? "";
    document.getElementById("edit_fecha_termino_proyecto").value = proyecto.fecha_termino_proyecto ?? "";
    document.getElementById("edit_estado_proyecto").value = proyecto.estado_proyecto ?? 1;

    const responsable = document.getElementById("edit_id_usuario");
    if (responsable) {
        responsable.value = proyecto.id_usuario;
    }

    document.getElementById("modalEditarProyecto").classList.add("show");
}

function cerrarEditarProyecto() {
    document.getElementById("modalEditarProyecto").classList.remove("show");
}

document.getElementById("modalNuevoProyecto").addEventListener("click", function(e) {
    if (e.target === this) cerrarModal();
});

document.getElementById("modalEditarProyecto").addEventListener("click", function(e) {
    if (e.target === this) cerrarEditarProyecto();
});

<?php if ($esAdmin && $errors->any() && old('_method') !== 'PUT'): ?>
    abrirModal();
<?php endif; ?>
</script>

<?= view('pie') ?>
