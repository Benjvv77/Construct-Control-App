<?php
$esAdmin = auth()->user()->esAdministrador();
$alertasInventario = count(array_filter($comparativo, fn ($fila) => $fila['alertas']));
?>
<?= view('encabezado', ['titulo' => 'Inventario — ConstructControl', 'estilos' => ['inventario']]) ?>

<main class="main">

    <section class="hero-detail">
        <div>
            <div class="breadcrumb">
                Proyectos › <span><?= e($proyecto->nombre_proyecto) ?></span> › Inventario
            </div>

            <span class="eyebrow">Inventario de materiales</span>

            <h1>Inventario del proyecto</h1>
        </div>

        <div class="hero-actions">
            <button type="button" class="btn-back" onclick="abrirModal('modalNuevoMaterial')">
                + Nuevo material
            </button>
            <a href="<?= e(route('proyectos.show', $proyecto)) ?>" class="btn-back">
                ← Volver al proyecto
            </a>
        </div>
    </section>

    <?= view('alertas') ?>

    <section class="kpis">
        <div class="kpi-card">
            <div class="kpi-icon">📦</div>
            <div>
                <p>Total materiales</p>
                <h2><?= e($materiales->count()) ?></h2>
                <span>Registrados en el proyecto</span>
            </div>
        </div>

        <div class="kpi-card">
            <div class="kpi-icon danger">⚠️</div>
            <div>
                <p>Stock bajo</p>
                <h2><?= e($stockBajo) ?></h2>
                <span>En o bajo el stock mínimo</span>
            </div>
        </div>

        <div class="kpi-card">
            <div class="kpi-icon warn">⬇️</div>
            <div>
                <p>Salidas</p>
                <h2><?= e($totalSalidas) ?></h2>
                <span>Descuentos registrados</span>
            </div>
        </div>

        <div class="kpi-card">
            <div class="kpi-icon green">🧮</div>
            <div>
                <p>Alertas vs. presupuesto</p>
                <h2><?= $alertasInventario ?></h2>
                <span>Compras de más o pérdidas</span>
            </div>
        </div>
    </section>

    <section class="inventory-card">

        <div class="section-header">
            <div>
                <h2>Materiales del proyecto</h2>
                <p>La barra representa el porcentaje restante respecto al stock inicial; el estado se compara con el stock mínimo.</p>
            </div>
        </div>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Material</th>
                        <th>Costo estimado</th>
                        <th>Stock actual</th>
                        <th>Nivel de stock</th>
                        <th>Estado</th>
                        <th>Movimiento</th>
                        <th>Acciones</th>
                    </tr>
                </thead>

                <tbody>
                    <?php if (count($materiales) > 0): ?>
                    <?php foreach ($materiales as $m): ?>
                        <?php
                            $porcentajeStock = $m->porcentajeStock();
                            $nivel = $m->nivelStock();
                            $datosMaterial = [
                                'id_material' => $m->id_material,
                                'nombre_material' => $m->nombre_material,
                                'unidad_material' => $m->unidad_material,
                                'stock_actual' => (float) $m->stock_actual,
                                'stock_minimo' => (float) $m->stock_minimo,
                                'costo_estimado_material' => (float) $m->costo_estimado_material,
                                'id_item_presupuesto' => $m->id_item_presupuesto,
                                'url' => route('inventario.materiales.update', [$proyecto, $m]),
                            ];
                        ?>

                        <tr>
                            <td>
                                <div class="material-info">
                                    <div class="material-icon">🧱</div>
                                    <div>
                                        <strong><?= e($m->nombre_material) ?></strong>
                                        <span>Unidad: <?= e($m->unidad_material) ?></span>
                                        <span><?= $m->item ? 'Ítem: '.e($m->item->descripcion_item) : 'No presupuestado' ?></span>
                                    </div>
                                </div>
                            </td>

                            <td class="money-cell">
                                <?= pesos($m->costo_estimado_material) ?>
                            </td>

                            <td>
                                <strong>
                                    <?= numero($m->stock_actual) ?>
                                    <?= e($m->unidad_material) ?>
                                </strong>
                                <span>Inicial: <?= numero($m->stock_inicial) ?> · Mínimo: <?= numero($m->stock_minimo) ?></span>
                            </td>

                            <td>
                                <div class="stock-progress">
                                    <div class="stock-bar">
                                        <div
                                            class="<?= e($nivel['clase']) ?>"
                                            style="width: <?= e($porcentajeStock) ?>%;">
                                        </div>
                                    </div>
                                    <span><?= e($porcentajeStock) ?>%</span>
                                </div>
                            </td>

                            <td>
                                <span class="badge <?= e($nivel['clase']) ?>">
                                    <?= e($nivel['texto']) ?>
                                </span>
                            </td>

                            <td>
                                <div class="acciones-fila">
                                    <button
                                        type="button"
                                        class="btn-move"
                                        data-material="<?= e(json_encode($datosMaterial)) ?>"
                                        onclick="abrirMovimiento(JSON.parse(this.dataset.material), 'SALIDA')">
                                        Descontar
                                    </button>
                                    <button
                                        type="button"
                                        class="btn-move"
                                        data-material="<?= e(json_encode($datosMaterial)) ?>"
                                        onclick="abrirMovimiento(JSON.parse(this.dataset.material), 'ENTRADA')">
                                        Agregar
                                    </button>
                                    <button
                                        type="button"
                                        class="btn-move"
                                        data-material="<?= e(json_encode($datosMaterial)) ?>"
                                        onclick="abrirConteo(JSON.parse(this.dataset.material))">
                                        Conteo
                                    </button>
                                </div>
                            </td>

                            <td>
                                <?php if ($esAdmin): ?>
                                <div class="acciones-fila">
                                    <button
                                        type="button"
                                        class="btn-move"
                                        data-material="<?= e(json_encode($datosMaterial)) ?>"
                                        onclick="abrirEditarMaterial(JSON.parse(this.dataset.material))">
                                        Editar
                                    </button>
                                    <form
                                        action="<?= e(route('inventario.materiales.destroy', [$proyecto, $m])) ?>"
                                        method="POST"
                                        class="form-inline"
                                        onsubmit="return confirm('¿Seguro que deseas eliminar este material del inventario? También se borrará su historial de movimientos.')">
                                        <?= csrf_field() ?>
                                        <?= method_field('DELETE') ?>
                                        <button type="submit" class="btn-eliminar">Eliminar</button>
                                    </form>
                                </div>
                                <?php else: ?>
                                    <small>—</small>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="empty">
                                Aún no hay materiales registrados. Agrega un gasto de tipo “Materiales” o usa “+ Nuevo material”.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    </section>

    <!-- HU14: inventario real frente a lo presupuestado -->
    <section class="history-card">

        <div class="section-header">
            <div>
                <h2>Inventario vs. presupuesto</h2>
                <p>Lo comprado, usado y contado de cada material frente a la cantidad presupuestada. Stock = comprado − usado + ajustes de conteo.</p>
            </div>
        </div>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Material</th>
                        <th>Presupuestado</th>
                        <th>Comprado</th>
                        <th>Usado</th>
                        <th>Ajustes de conteo</th>
                        <th>Stock</th>
                        <th>Observaciones</th>
                    </tr>
                </thead>

                <tbody>
                    <?php if (count($comparativo) > 0): ?>
                    <?php foreach ($comparativo as $fila): ?>
                        <tr>
                            <td><strong><?= e($fila['nombre']) ?></strong></td>
                            <td><?= $fila['presupuestado'] === null ? '<small>No presupuestado</small>' : numero($fila['presupuestado']).' '.e($fila['unidad']) ?></td>
                            <td><?= numero($fila['comprado']) ?> <?= e($fila['unidad']) ?></td>
                            <td><?= numero($fila['consumido']) ?> <?= e($fila['unidad']) ?></td>
                            <td class="<?= $fila['ajustes'] < 0 ? 'texto-rojo' : '' ?>"><?= $fila['ajustes'] > 0 ? '+' : '' ?><?= numero($fila['ajustes']) ?></td>
                            <td><?= numero($fila['stock']) ?> <?= e($fila['unidad']) ?></td>
                            <td>
                                <?php if ($fila['alertas']): ?>
                                    <?php foreach ($fila['alertas'] as $alerta): ?>
                                        <span class="alerta-inventario">⚠️ <?= e($alerta) ?></span>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <small class="texto-verde">Sin observaciones</small>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="empty">
                                Aún no hay materiales ni ítems de materiales en el presupuesto.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    </section>

    <section class="history-card">

        <div class="section-header">
            <div>
                <h2>Historial de movimientos</h2>
                <p>Entradas y salidas de material registradas en el proyecto.</p>
            </div>
        </div>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Material</th>
                        <th>Movimiento</th>
                        <th>Cantidad</th>
                        <th>Fecha</th>
                        <th>Descripción</th>
                    </tr>
                </thead>

                <tbody>
                    <?php if (count($movimientos) > 0): ?>
                    <?php foreach ($movimientos as $mov): ?>
                        <tr>
                            <td>
                                <strong><?= e($mov->material->nombre_material) ?></strong>
                                <span><?= e($mov->material->unidad_material) ?></span>
                            </td>

                            <td>
                                <span class="badge <?= e(['ENTRADA' => 'success', 'SALIDA' => 'danger', 'AJUSTE' => 'warn'][$mov->tipo_movimiento]) ?>">
                                    <?= e($mov->tipo_movimiento) ?>
                                </span>
                            </td>

                            <td>
                                <?= $mov->tipo_movimiento === 'AJUSTE' && $mov->cantidad_movimiento > 0 ? '+' : '' ?><?= numero($mov->cantidad_movimiento) ?>
                            </td>

                            <td>
                                <?= e($mov->fecha_movimiento?->format('Y-m-d')) ?>
                            </td>

                            <td>
                                <?= e($mov->descripcion_movimiento ?: 'Sin descripción') ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="empty">
                                Aún no hay movimientos registrados.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    </section>

</main>

<div class="modal-overlay" id="modalMovimiento">
    <div class="modal-box">

        <div class="modal-header">
            <div>
                <h2 id="mov_titulo">Descontar material</h2>
                <p id="mov_subtitulo">Registra el uso de material en el proyecto.</p>
            </div>

            <button class="close-btn" onclick="cerrarModal('modalMovimiento')">✕</button>
        </div>

        <form action="<?= e(route('inventario.movimientos.store', $proyecto)) ?>" method="POST">
            <?= csrf_field() ?>
            <input type="hidden" name="id_material" id="mov_id_material">
            <input type="hidden" name="tipo_movimiento" id="mov_tipo">

            <div class="selected-material">
                <span>Material seleccionado</span>
                <strong id="mov_nombre_material">Material</strong>
                <small id="mov_stock_material">Stock actual: 0</small>
            </div>

            <div class="form-grid">

                <div class="form-group">
                    <label id="mov_label_cantidad">Cantidad a descontar</label>
                    <input type="number" step="0.01" min="0.01" name="cantidad_movimiento" required>
                </div>

                <div class="form-group">
                    <label>Fecha</label>
                    <input type="date" name="fecha_movimiento" value="<?= e(now()->toDateString()) ?>" required>
                </div>

                <div class="form-group full">
                    <label>Descripción</label>
                    <textarea name="descripcion_movimiento" id="mov_descripcion" placeholder="Ej: uso en obra gruesa"></textarea>
                </div>

            </div>

            <div class="modal-actions">
                <button type="button" class="btn-secondary" onclick="cerrarModal('modalMovimiento')">Cancelar</button>
                <button type="submit" class="btn-primary" id="mov_boton">Guardar salida</button>
            </div>

        </form>

    </div>
</div>

<div class="modal-overlay" id="modalNuevoMaterial">
    <div class="modal-box">

        <div class="modal-header">
            <div>
                <h2>Nuevo material</h2>
                <p>Agrega un material al inventario sin registrar un gasto (por ejemplo, material que ya estaba en obra).</p>
            </div>

            <button class="close-btn" onclick="cerrarModal('modalNuevoMaterial')">✕</button>
        </div>

        <form action="<?= e(route('inventario.materiales.store', $proyecto)) ?>" method="POST">
            <?= csrf_field() ?>

            <div class="form-grid">
                <div class="form-group full">
                    <label>Nombre del material</label>
                    <input type="text" name="nombre_material" value="<?= e(old('nombre_material')) ?>" required>
                </div>

                <div class="form-group">
                    <label>Unidad</label>
                    <input type="text" name="unidad_material" value="<?= e(old('unidad_material')) ?>" placeholder="Ej: sacos, m3, barras" required>
                </div>

                <div class="form-group">
                    <label>Cantidad inicial</label>
                    <input type="number" step="0.01" min="0" name="cantidad_inicial" value="<?= e(old('cantidad_inicial', 0)) ?>">
                </div>

                <div class="form-group">
                    <label>Stock mínimo</label>
                    <input type="number" step="0.01" min="0" name="stock_minimo" value="<?= e(old('stock_minimo', 0)) ?>" required>
                </div>

                <div class="form-group">
                    <label>Costo estimado ($)</label>
                    <input type="number" step="1" min="0" name="costo_estimado_material" value="<?= e(old('costo_estimado_material', 0)) ?>">
                </div>
                <div class="form-group full">
                    <label>Ítem del presupuesto</label>
                    <select name="id_item_presupuesto">
                        <option value="">No está en el presupuesto</option>
                        <?php foreach ($itemsMateriales as $item): ?>
                            <option value="<?= $item->id_item_presupuesto ?>" <?= (int) old('id_item_presupuesto') === $item->id_item_presupuesto ? 'selected' : '' ?>><?= e($item->descripcion_item) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="modal-actions">
                <button type="button" class="btn-secondary" onclick="cerrarModal('modalNuevoMaterial')">Cancelar</button>
                <button type="submit" class="btn-primary">Agregar material</button>
            </div>
        </form>

    </div>
</div>

<div class="modal-overlay" id="modalEditarMaterial">
    <div class="modal-box">

        <div class="modal-header">
            <div>
                <h2>Editar material</h2>
                <p>El stock se modifica con entradas y salidas para que quede en el historial.</p>
            </div>

            <button class="close-btn" onclick="cerrarModal('modalEditarMaterial')">✕</button>
        </div>

        <form method="POST" id="formEditarMaterial">
            <?= csrf_field() ?>
            <?= method_field('PUT') ?>

            <div class="form-grid">
                <div class="form-group full">
                    <label>Nombre del material</label>
                    <input type="text" name="nombre_material" id="edit_nombre_material" required>
                </div>

                <div class="form-group">
                    <label>Unidad</label>
                    <input type="text" name="unidad_material" id="edit_unidad_material" required>
                </div>

                <div class="form-group">
                    <label>Stock mínimo</label>
                    <input type="number" step="0.01" min="0" name="stock_minimo" id="edit_stock_minimo" required>
                </div>

                <div class="form-group">
                    <label>Costo estimado ($)</label>
                    <input type="number" step="1" min="0" name="costo_estimado_material" id="edit_costo_estimado_material" required>
                </div>
                <div class="form-group full">
                    <label>Ítem del presupuesto</label>
                    <select name="id_item_presupuesto" id="edit_id_item_presupuesto">
                        <option value="">No está en el presupuesto</option>
                        <?php foreach ($itemsMateriales as $item): ?>
                            <option value="<?= $item->id_item_presupuesto ?>"><?= e($item->descripcion_item) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="modal-actions">
                <button type="button" class="btn-secondary" onclick="cerrarModal('modalEditarMaterial')">Cancelar</button>
                <button type="submit" class="btn-primary">Guardar cambios</button>
            </div>
        </form>

    </div>
</div>

<div class="modal-overlay" id="modalConteo">
    <div class="modal-box">

        <div class="modal-header">
            <div>
                <h2>Conteo físico</h2>
                <p>Ingresa lo que contaste en obra. La diferencia con el sistema queda registrada como ajuste.</p>
            </div>

            <button class="close-btn" onclick="cerrarModal('modalConteo')">✕</button>
        </div>

        <form action="<?= e(route('inventario.conteo.store', $proyecto)) ?>" method="POST">
            <?= csrf_field() ?>
            <input type="hidden" name="id_material" id="conteo_id_material">

            <div class="selected-material">
                <span>Material</span>
                <strong id="conteo_nombre_material">Material</strong>
                <small id="conteo_stock_material">Stock en el sistema: 0</small>
            </div>

            <div class="form-grid">
                <div class="form-group">
                    <label>Cantidad contada</label>
                    <input type="number" step="0.01" min="0" name="cantidad_contada" required>
                </div>

                <div class="form-group">
                    <label>Fecha</label>
                    <input type="date" name="fecha_movimiento" value="<?= now()->toDateString() ?>" required>
                </div>

                <div class="form-group full">
                    <label>Observación</label>
                    <textarea name="descripcion_movimiento" maxlength="1000" placeholder="Ej: conteo semanal de bodega"></textarea>
                </div>
            </div>

            <div class="modal-actions">
                <button type="button" class="btn-secondary" onclick="cerrarModal('modalConteo')">Cancelar</button>
                <button type="submit" class="btn-primary">Registrar conteo</button>
            </div>
        </form>

    </div>
</div>

<script>
function abrirModal(id) {
    document.getElementById(id).classList.add("show");
}

function cerrarModal(id) {
    document.getElementById(id).classList.remove("show");
}

function abrirMovimiento(material, tipo) {
    const esSalida = tipo === "SALIDA";

    document.getElementById("mov_id_material").value = material.id_material;
    document.getElementById("mov_tipo").value = tipo;
    document.getElementById("mov_titulo").textContent = esSalida ? "Descontar material" : "Agregar stock";
    document.getElementById("mov_subtitulo").textContent = esSalida
        ? "Registra el uso de material en el proyecto."
        : "Registra material que ingresa a la obra (compra, devolución o traslado).";
    document.getElementById("mov_label_cantidad").textContent = esSalida ? "Cantidad a descontar" : "Cantidad que ingresa";
    document.getElementById("mov_descripcion").placeholder = esSalida ? "Ej: uso en obra gruesa" : "Ej: devolución de bodega";
    document.getElementById("mov_boton").textContent = esSalida ? "Guardar salida" : "Guardar entrada";
    document.getElementById("mov_nombre_material").textContent = material.nombre_material;
    document.getElementById("mov_stock_material").textContent =
        "Stock actual: " + material.stock_actual + " " + material.unidad_material;

    abrirModal("modalMovimiento");
}

function abrirEditarMaterial(material) {
    document.getElementById("formEditarMaterial").action = material.url;
    document.getElementById("edit_nombre_material").value = material.nombre_material;
    document.getElementById("edit_unidad_material").value = material.unidad_material;
    document.getElementById("edit_stock_minimo").value = material.stock_minimo;
    document.getElementById("edit_costo_estimado_material").value = material.costo_estimado_material;
    document.getElementById("edit_id_item_presupuesto").value = material.id_item_presupuesto ?? "";

    abrirModal("modalEditarMaterial");
}

function abrirConteo(material) {
    document.getElementById("conteo_id_material").value = material.id_material;
    document.getElementById("conteo_nombre_material").textContent = material.nombre_material;
    document.getElementById("conteo_stock_material").textContent =
        "Stock en el sistema: " + material.stock_actual + " " + material.unidad_material;

    abrirModal("modalConteo");
}

document.querySelectorAll(".modal-overlay").forEach(function (modal) {
    modal.addEventListener("click", function (e) {
        if (e.target === this) {
            cerrarModal(this.id);
        }
    });
});

<?php if ($errors->hasAny(['nombre_material', 'unidad_material', 'cantidad_inicial', 'stock_minimo']) && old('_method') !== 'PUT'): ?>
    abrirModal("modalNuevoMaterial");
<?php endif; ?>
</script>

<?= view('pie') ?>
