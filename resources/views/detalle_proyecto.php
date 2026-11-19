<?php
/**
 * @var \App\Models\Proyecto $proyecto
 * @var \Illuminate\Support\Collection $gastos
 * @var \Illuminate\Support\Collection $items
 * @var \Illuminate\Support\Collection $tiposGasto
 * @var array $comparativo  ver App\Services\ResumenPresupuesto
 */
$usuario = auth()->user();
$esAdmin = $usuario->esAdministrador();
$estadosItem = \App\Services\ResumenPresupuesto::ESTADOS;
?>
<?= view('encabezado', ['titulo' => 'ConstructControl — Detalle proyecto', 'estilos' => ['gastos']]) ?>

<main class="main">

    <section class="hero-detail">
        <div>
            <div class="breadcrumb">
                Proyectos › <span><?= e($proyecto->nombre_proyecto) ?></span> › Presupuesto y gastos
            </div>

            <span class="eyebrow">Control presupuestario</span>

            <h1><?= e($proyecto->nombre_proyecto) ?></h1>

            <p>
                <?= e($proyecto->descripcion_proyecto ?: 'Gestiona el presupuesto, los gastos y sus documentos.') ?>
            </p>

            <div class="hero-meta">
                <span>Inicio: <?= e($proyecto->fecha_inicio_proyecto?->format('Y-m-d')) ?></span>
                <span>Término: <?= e($proyecto->fecha_termino_proyecto?->format('Y-m-d') ?? 'No asignada') ?></span>
                <span>Responsable: <?= e($proyecto->usuario->nombreCompleto()) ?></span>
                <span class="status <?= e($proyecto->estadoClase()) ?>"><?= e($proyecto->estadoTexto()) ?></span>
            </div>
        </div>

        <div class="hero-actions-wrap">
            <a href="<?= e(route('proyectos.reporte', $proyecto)) ?>" class="btn-back">📊 Reporte y Curva S</a>
            <a href="<?= e(route('reportes.adicionales', $proyecto)) ?>" class="btn-back">🧾 Gastos adicionales</a>
            <a href="<?= e(route('inventario.index', $proyecto)) ?>" class="btn-back">📦 Inventario</a>
            <a href="<?= e(route('proyectos.index')) ?>" class="btn-back">← Proyectos</a>
        </div>
    </section>

    <?= view('alertas') ?>

    <?php if ($alertaPresupuesto === 'excedido'): ?>
        <div class="alert error">
            ⚠️ Presupuesto excedido: los gastos aprobados superan el presupuesto en <?= pesos(abs($disponible)) ?>.
        </div>
    <?php elseif ($alertaPresupuesto === 'critico'): ?>
        <div class="alert error">
            ⚠️ Desviación presupuestaria: ya se usó el <?= e(round($porcentajeUsado, 1)) ?>% del presupuesto (umbral de alerta: <?= \App\Models\Proyecto::UMBRAL_ALERTA ?>%).
        </div>
    <?php endif; ?>

    <section class="kpis">
        <div class="kpi-card">
            <div class="kpi-icon">💰</div>
            <div>
                <p>Presupuesto</p>
                <h2><?= pesos($comparativo['presupuesto']) ?></h2>
                <span><?= count($items) ?> <?= count($items) === 1 ? 'ítem' : 'ítems' ?></span>
            </div>
        </div>

        <div class="kpi-card">
            <div class="kpi-icon warn">🧾</div>
            <div>
                <p>Gastado (aprobado)</p>
                <h2><?= pesos($comparativo['gastado']) ?></h2>
                <span><?= e(round($comparativo['porcentaje'], 1)) ?>% utilizado</span>
            </div>
        </div>

        <div class="kpi-card">
            <div class="kpi-icon green">✅</div>
            <div>
                <p>Disponible</p>
                <h2><?= pesos(-$comparativo['desviacion']) ?></h2>
                <span><?= $comparativo['pendiente'] > 0 ? pesos($comparativo['pendiente']).' por aprobar' : 'Sin gastos por aprobar' ?></span>
            </div>
        </div>

        <div class="kpi-card">
            <div class="kpi-icon danger">⚠️</div>
            <div>
                <p>Gastos adicionales</p>
                <h2><?= pesos($comparativo['adicionales']['gastado']) ?></h2>
                <span>No presupuestados</span>
            </div>
        </div>
    </section>

    <!-- HU07 y HU11: ítems del presupuesto comparados con lo gastado -->
    <section class="expenses-card seccion-presupuesto" id="presupuesto">
        <div class="section-header">
            <div>
                <h2>Presupuesto por ítem</h2>
                <p>Lo presupuestado frente a los gastos aprobados de cada ítem. Desviación positiva = sobrecosto.</p>
            </div>

            <?php if ($esAdmin): ?>
                <button type="button" class="btn-doc" onclick="abrirItem()">+ Agregar ítem</button>
            <?php endif; ?>
        </div>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Ítem</th>
                        <th>Cantidad</th>
                        <th>Precio unitario</th>
                        <th>Presupuestado</th>
                        <th>Gastado</th>
                        <th>Avance</th>
                        <th>Desviación</th>
                        <th>Estado</th>
                        <?php if ($esAdmin): ?><th>Acciones</th><?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                <?php if (count($comparativo['filas']) > 0): ?>
                    <?php foreach ($comparativo['filas'] as $fila): ?>
                        <?php $item = $fila['item']; ?>
                        <tr>
                            <td>
                                <strong><?= e($item->descripcion_item) ?></strong>
                                <span><?= e($item->tipoGasto->nombre_tipo_gasto) ?></span>
                            </td>
                            <td><?= numero($item->cantidad) ?> <?= e($item->unidad_item) ?></td>
                            <td class="money-cell"><?= pesos($item->precio_unitario) ?></td>
                            <td class="money-cell"><?= pesos($fila['presupuestado']) ?></td>
                            <td class="money-cell">
                                <?= pesos($fila['gastado']) ?>
                                <?php if ($fila['pendiente'] > 0): ?>
                                    <br><small>+ <?= pesos($fila['pendiente']) ?> por aprobar</small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="barra-mini"><div class="<?= e($fila['estado']) ?>" style="width: <?= (int) min(100, round($fila['porcentaje'])) ?>%;"></div></div>
                                <small><?= e(round($fila['porcentaje'], 1)) ?>%</small>
                            </td>
                            <td class="money-cell <?= $fila['desviacion'] > 0 ? 'texto-rojo' : 'texto-verde' ?>">
                                <?= $fila['desviacion'] > 0 ? '+' : '' ?><?= pesos($fila['desviacion']) ?>
                            </td>
                            <td><span class="status <?= e($estadosItem[$fila['estado']]['clase']) ?>"><?= e($estadosItem[$fila['estado']]['texto']) ?></span></td>
                            <?php if ($esAdmin): ?>
                                <td>
                                    <div class="acciones-fila">
                                        <button type="button" class="btn-doc"
                                            data-item="<?= e(json_encode([
                                                'url' => route('presupuesto.update', [$proyecto, $item]),
                                                'id_tipo_gasto' => $item->id_tipo_gasto,
                                                'descripcion_item' => $item->descripcion_item,
                                                'cantidad' => (float) $item->cantidad,
                                                'unidad_item' => $item->unidad_item,
                                                'precio_unitario' => (float) $item->precio_unitario,
                                            ])) ?>"
                                            onclick="abrirItem(JSON.parse(this.dataset.item))">Editar</button>
                                        <form action="<?= e(route('presupuesto.destroy', [$proyecto, $item])) ?>" method="POST" class="form-inline"
                                            onsubmit="return confirm('¿Eliminar este ítem del presupuesto?')">
                                            <?= csrf_field() ?>
                                            <?= method_field('DELETE') ?>
                                            <button type="submit" class="btn-doc">Eliminar</button>
                                        </form>
                                    </div>
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="9" class="empty">
                            <?= $esAdmin ? 'El proyecto aún no tiene presupuesto: agrega su primer ítem con "+ Agregar ítem".' : 'El administrador aún no ha cargado el presupuesto de este proyecto.' ?>
                        </td>
                    </tr>
                <?php endif; ?>

                <?php if ($comparativo['sin_item']['gastado'] > 0 || $comparativo['sin_item']['pendiente'] > 0): ?>
                    <tr>
                        <td><strong>Presupuestados sin ítem</strong><span>Gastos anteriores a los ítems</span></td>
                        <td>—</td><td>—</td><td>—</td>
                        <td class="money-cell"><?= pesos($comparativo['sin_item']['gastado']) ?></td>
                        <td colspan="<?= $esAdmin ? 4 : 3 ?>"></td>
                    </tr>
                <?php endif; ?>

                <tr>
                    <td><strong>Gastos adicionales</strong><span>No contemplados en el presupuesto</span></td>
                    <td>—</td><td>—</td><td>—</td>
                    <td class="money-cell">
                        <?= pesos($comparativo['adicionales']['gastado']) ?>
                        <?php if ($comparativo['adicionales']['pendiente'] > 0): ?>
                            <br><small>+ <?= pesos($comparativo['adicionales']['pendiente']) ?> por aprobar</small>
                        <?php endif; ?>
                    </td>
                    <td></td>
                    <td class="money-cell <?= $comparativo['adicionales']['gastado'] > 0 ? 'texto-rojo' : '' ?>">
                        <?= $comparativo['adicionales']['gastado'] > 0 ? '+'.pesos($comparativo['adicionales']['gastado']) : pesos(0) ?>
                    </td>
                    <td colspan="<?= $esAdmin ? 2 : 1 ?>"></td>
                </tr>

                <tr class="fila-total">
                    <td><strong>Total del proyecto</strong></td>
                    <td></td><td></td>
                    <td class="money-cell"><?= pesos($comparativo['presupuesto']) ?></td>
                    <td class="money-cell"><?= pesos($comparativo['gastado']) ?></td>
                    <td><small><?= e(round($comparativo['porcentaje'], 1)) ?>%</small></td>
                    <td class="money-cell <?= $comparativo['desviacion'] > 0 ? 'texto-rojo' : 'texto-verde' ?>">
                        <?= $comparativo['desviacion'] > 0 ? '+' : '' ?><?= pesos($comparativo['desviacion']) ?>
                    </td>
                    <td><span class="status <?= e($estadosItem[$comparativo['estado']]['clase']) ?>"><?= e($estadosItem[$comparativo['estado']]['texto']) ?></span></td>
                    <?php if ($esAdmin): ?><td></td><?php endif; ?>
                </tr>
                </tbody>
            </table>
        </div>
    </section>

    <section class="layout">

        <div class="expenses-card">
            <div class="section-header">
                <div>
                    <h2>Historial de gastos</h2>
                    <p><?= e($gastos->count()) ?> <?= e($filtroCosto ? ($filtroCosto === 'extra' ? 'gastos adicionales' : 'gastos presupuestados') : 'gastos registrados') ?></p>
                </div>

                <div class="filters">
                    <?php foreach (['' => 'Todos', 'normal' => 'Presupuestados', 'extra' => 'Adicionales'] as $valor => $etiqueta): ?>
                        <a
                            href="<?= e(route('proyectos.show', array_filter(['proyecto' => $proyecto->id_proyecto, 'costo' => $valor ?: null]))) ?>"
                            class="filter <?= e(($filtroCosto ?? '') === $valor ? 'active' : '') ?>">
                            <?= e($etiqueta) ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Acciones</th>
                            <th>Gasto</th>
                            <th>Ítem / tipo</th>
                            <th>Estado</th>
                            <th>Fecha</th>
                            <th>Monto</th>
                            <th>Documento</th>
                        </tr>
                    </thead>

                    <tbody>
                    <?php if (count($gastos) > 0): ?>
                    <?php foreach ($gastos as $g): ?>
                        <tr>
                            <td>
                                <div class="acciones-fila">
                                    <?php if ($esAdmin && $g->estaPendiente()): ?>
                                        <form action="<?= e(route('gastos.aprobar', $g)) ?>" method="POST" class="form-inline">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="btn-doc btn-aprobar">✓ Aprobar</button>
                                        </form>
                                        <button type="button" class="btn-doc"
                                            onclick="abrirRechazo('<?= e(route('gastos.rechazar', $g)) ?>', <?= e(json_encode($g->nombre_gasto)) ?>)">✕ Rechazar</button>
                                    <?php endif; ?>

                                    <?php if ($g->puedeModificar($usuario)): ?>
                                        <button
                                            type="button"
                                            class="btn-doc"
                                            data-gasto="<?= e(json_encode([
                                                'url' => route('gastos.update', [$proyecto, $g]),
                                                'aprobado' => $g->estaAprobado(),
                                                'clase' => $g->esExtra() ? 'adicional' : 'presupuestado',
                                                'id_item_presupuesto' => $g->id_item_presupuesto,
                                                'id_tipo_gasto' => $g->id_tipo_gasto,
                                                'nombre_gasto' => $g->nombre_gasto,
                                                'descripcion_gasto' => $g->descripcion_gasto,
                                                'monto_gasto' => (float) $g->monto_gasto,
                                                'fecha_gasto' => $g->fecha_gasto?->format('Y-m-d'),
                                                'cantidad_gasto' => (float) $g->cantidad_gasto ?: null,
                                                'unidad_gasto' => $g->unidad_gasto,
                                                'tiene_documento' => (bool) $g->documento_gasto,
                                            ])) ?>"
                                            onclick="abrirEditarGasto(JSON.parse(this.dataset.gasto))">
                                            Editar
                                        </button>
                                        <form
                                            action="<?= e(route('gastos.destroy', [$proyecto, $g])) ?>"
                                            method="POST"
                                            class="form-inline"
                                            onsubmit="return confirm('¿Seguro que deseas eliminar este gasto?')">
                                            <?= csrf_field() ?>
                                            <?= method_field('DELETE') ?>
                                            <button type="submit" class="btn-doc">Eliminar</button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <div class="expense-info">
                                    <div class="expense-icon"><?= $g->esExtra() ? '⚠️' : '🧱' ?></div>
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
                                <?php if ($g->esExtra()): ?>
                                    <span class="badge badge-extra">Adicional</span>
                                    <br><small><?= e($g->tipoGasto?->nombre_tipo_gasto) ?></small>
                                <?php else: ?>
                                    <span class="badge badge-normal">Presupuestado</span>
                                    <br><small><?= e($g->item?->descripcion_item ?? $g->tipoGasto?->nombre_tipo_gasto) ?></small>
                                <?php endif; ?>
                            </td>

                            <td>
                                <span class="status <?= e($g->estadoClase()) ?>"><?= e($g->estadoTexto()) ?></span>
                                <br><small>Registró: <?= e($g->registradoPor?->nombreCompleto() ?? '—') ?></small>
                                <?php if ($g->estado_gasto === \App\Models\Gasto::RECHAZADO && $g->motivo_rechazo): ?>
                                    <br><small class="texto-rojo">Motivo: <?= e($g->motivo_rechazo) ?></small>
                                <?php endif; ?>
                            </td>

                            <td><?= e($g->fecha_gasto?->format('Y-m-d')) ?></td>

                            <td class="money-cell">
                                <?= pesos($g->monto_gasto) ?>
                            </td>

                            <td>
                                <?php if ($g->documento_gasto): ?>
                                    <button
                                        type="button"
                                        class="btn-doc"
                                        data-url="<?= e(route('gastos.documento', [$proyecto, $g])) ?>"
                                        data-extension="<?= e(strtolower(pathinfo($g->documento_gasto, PATHINFO_EXTENSION))) ?>"
                                        onclick="abrirDocumento(this.dataset.url, this.dataset.extension)">
                                        📄 Ver
                                    </button>
                                <?php else: ?>
                                    <span class="sin-doc">Sin boleta</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="empty">
                                Aún no hay gastos registrados.
                            </td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <aside class="form-card">
            <div class="form-header">
                <h2>Nuevo gasto</h2>
                <p><?= $esAdmin
                    ? 'Los gastos que registras quedan aprobados de inmediato.'
                    : 'El gasto quedará pendiente hasta que un administrador lo apruebe.' ?></p>
            </div>

            <form action="<?= e(route('gastos.store', $proyecto)) ?>" method="POST" enctype="multipart/form-data" id="formNuevoGasto">
                <?= csrf_field() ?>

                <?= view('formulario_gasto', ['prefijo' => 'nuevo', 'items' => $items, 'tiposGasto' => $tiposGasto, 'conOld' => true]) ?>

                <button class="btn-submit">
                    Registrar gasto
                </button>
            </form>
        </aside>

    </section>

</main>

<div class="doc-modal" id="modalEditarGasto">
    <div class="doc-modal-box">

        <div class="doc-modal-header">
            <h3>Editar gasto</h3>
            <button type="button" onclick="cerrarModal('modalEditarGasto')">✕</button>
        </div>

        <form method="POST" enctype="multipart/form-data" id="formEditarGasto" class="form-editar-gasto">
            <?= csrf_field() ?>
            <?= method_field('PUT') ?>

            <?= view('formulario_gasto', ['prefijo' => 'editar', 'items' => $items, 'tiposGasto' => $tiposGasto, 'conOld' => false]) ?>

            <button class="btn-submit">Guardar cambios</button>
        </form>

    </div>
</div>

<?php if ($esAdmin): ?>
<div class="doc-modal" id="modalItem">
    <div class="doc-modal-box">

        <div class="doc-modal-header">
            <h3 id="itemTitulo">Agregar ítem al presupuesto</h3>
            <button type="button" onclick="cerrarModal('modalItem')">✕</button>
        </div>

        <form method="POST" action="<?= e(route('presupuesto.store', $proyecto)) ?>" id="formItem" class="form-editar-gasto">
            <?= csrf_field() ?>
            <input type="hidden" name="_method" id="itemMetodo" value="POST">

            <div class="form-group">
                <label>Descripción</label>
                <input type="text" name="descripcion_item" id="item_descripcion_item" maxlength="255" placeholder="Ej: Cemento 25 kg" required>
            </div>

            <div class="form-group">
                <label>Tipo de gasto</label>
                <select name="id_tipo_gasto" id="item_id_tipo_gasto" required>
                    <?php foreach ($tiposGasto as $tipo): ?>
                        <option value="<?= $tipo->id_tipo_gasto ?>"><?= e($tipo->nombre_tipo_gasto) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Cantidad</label>
                <input type="number" step="0.01" min="0.01" name="cantidad" id="item_cantidad" required>
            </div>

            <div class="form-group">
                <label>Unidad</label>
                <input type="text" name="unidad_item" id="item_unidad_item" maxlength="30" placeholder="Ej: sacos, m2, horas, global">
            </div>

            <div class="form-group">
                <label>Precio unitario ($)</label>
                <input type="number" step="1" min="1" name="precio_unitario" id="item_precio_unitario" required>
            </div>

            <p class="ayuda-campo" id="item_total">Total del ítem: $0</p>

            <button class="btn-submit" id="itemBoton">Agregar ítem</button>
        </form>

    </div>
</div>

<div class="doc-modal" id="modalRechazo">
    <div class="doc-modal-box">

        <div class="doc-modal-header">
            <h3 id="rechazoTitulo">Rechazar gasto</h3>
            <button type="button" onclick="cerrarModal('modalRechazo')">✕</button>
        </div>

        <form method="POST" id="formRechazo" class="form-editar-gasto">
            <?= csrf_field() ?>
            <div class="form-group">
                <label>Motivo del rechazo</label>
                <textarea name="motivo_rechazo" maxlength="1000" required
                    placeholder="Explica qué falta o qué hay que corregir, para que quien lo registró pueda arreglarlo"></textarea>
            </div>
            <button class="btn-submit">Rechazar gasto</button>
        </form>

    </div>
</div>
<?php endif; ?>

<div class="doc-modal" id="docModal">
    <div class="doc-modal-box">

        <div class="doc-modal-header">
            <h3>Previsualización de boleta</h3>
            <button type="button" onclick="cerrarDocumento()">✕</button>
        </div>

        <div class="doc-preview" id="docPreview"></div>

    </div>
</div>

<script>
const MATERIALES = "<?= \App\Models\TipoGasto::MATERIALES ?>";

function abrirModal(id) {
    document.getElementById(id).classList.add("show");
}

function cerrarModal(id) {
    document.getElementById(id).classList.remove("show");
}

document.querySelectorAll(".doc-modal").forEach(function (modal) {
    modal.addEventListener("click", function (e) {
        if (e.target === this) {
            this.id === "docModal" ? cerrarDocumento() : cerrarModal(this.id);
        }
    });
});

function abrirDocumento(url, extension) {
    const preview = document.getElementById("docPreview");
    const visor = document.createElement(extension === "pdf" ? "iframe" : "img");

    visor.src = url;

    if (extension === "pdf") {
        visor.width = "100%";
        visor.height = "520px";
    } else {
        visor.alt = "Boleta adjunta";
    }

    preview.replaceChildren(visor);
    abrirModal("docModal");
}

function cerrarDocumento() {
    cerrarModal("docModal");
    document.getElementById("docPreview").replaceChildren();
}

/*
 * Formulario de gasto: muestra los campos según sea presupuestado o adicional, y si es de materiales.
 * "bloqueado" se usa al editar un gasto ya aprobado: los campos ligados al presupuesto o al
 * inventario se deshabilitan (y como no se envían, el servidor los conserva).
 */
function configurarGasto(prefijo, opciones = {}) {
    const campo = nombre => document.getElementById(prefijo + "_" + nombre);
    const adicional = campo("clase_adicional").checked;
    const itemElegido = campo("id_item_presupuesto").selectedOptions[0];
    const tipo = adicional ? campo("id_tipo_gasto").value : (itemElegido?.dataset.tipo ?? "");
    const esMaterial = tipo === MATERIALES;
    const bloqueado = Boolean(opciones.bloqueado);

    campo("campo_item").style.display = adicional ? "none" : "";
    campo("campo_tipo").style.display = adicional ? "" : "none";
    campo("campo_cantidad").style.display = esMaterial ? "" : "none";
    campo("campo_unidad").style.display = esMaterial ? "" : "none";

    campo("id_item_presupuesto").required = !adicional && !bloqueado;
    campo("id_tipo_gasto").required = adicional && !bloqueado;
    campo("cantidad_gasto").required = esMaterial && !bloqueado;
    campo("unidad_gasto").required = esMaterial && !bloqueado;
    campo("descripcion_gasto").required = adicional;
    campo("documento_gasto").required = !adicional && !opciones.tieneDocumento && !bloqueado;

    campo("label_descripcion").textContent = adicional ? "Justificación (obligatoria)" : "Descripción";
    campo("label_documento").textContent = adicional
        ? "Boleta o factura (opcional)"
        : (opciones.tieneDocumento ? "Reemplazar boleta o factura (opcional)" : "Boleta o factura (obligatoria)");

    if (!adicional && esMaterial && itemElegido?.dataset.unidad && !campo("unidad_gasto").value) {
        campo("unidad_gasto").value = itemElegido.dataset.unidad;
    }

    ["clase_presupuestado", "clase_adicional", "id_item_presupuesto", "id_tipo_gasto", "cantidad_gasto", "unidad_gasto"]
        .forEach(nombre => campo(nombre).disabled = bloqueado || (nombre === "clase_presupuestado" && campo(nombre).dataset.sinItems === "1"));
    campo("nombre_gasto").disabled = bloqueado && esMaterial;
    campo("nota_aprobado").style.display = bloqueado ? "block" : "none";
}

["nuevo", "editar"].forEach(function (prefijo) {
    const sinItems = document.getElementById(prefijo + "_clase_presupuestado").disabled;
    document.getElementById(prefijo + "_clase_presupuestado").dataset.sinItems = sinItems ? "1" : "0";

    ["clase_presupuestado", "clase_adicional", "id_item_presupuesto", "id_tipo_gasto"].forEach(function (nombre) {
        document.getElementById(prefijo + "_" + nombre).addEventListener("change", () => configurarGasto(prefijo, opcionesEdicion[prefijo] ?? {}));
    });
});

const opcionesEdicion = {};
configurarGasto("nuevo");

function abrirEditarGasto(gasto) {
    const campo = nombre => document.getElementById("editar_" + nombre);

    document.getElementById("formEditarGasto").action = gasto.url;
    campo(gasto.clase === "adicional" ? "clase_adicional" : "clase_presupuestado").checked = true;
    campo("id_item_presupuesto").value = gasto.id_item_presupuesto ?? "";
    campo("id_tipo_gasto").value = gasto.id_tipo_gasto ?? "";
    campo("nombre_gasto").value = gasto.nombre_gasto;
    campo("monto_gasto").value = gasto.monto_gasto;
    campo("fecha_gasto").value = gasto.fecha_gasto;
    campo("cantidad_gasto").value = gasto.cantidad_gasto ?? "";
    campo("unidad_gasto").value = gasto.unidad_gasto ?? "";
    campo("descripcion_gasto").value = gasto.descripcion_gasto ?? "";
    campo("documento_gasto").value = "";

    opcionesEdicion.editar = { bloqueado: gasto.aprobado, tieneDocumento: gasto.tiene_documento };
    configurarGasto("editar", opcionesEdicion.editar);
    abrirModal("modalEditarGasto");
}

<?php if ($esAdmin): ?>
function abrirItem(item) {
    const editando = Boolean(item);
    const campo = nombre => document.getElementById("item_" + nombre);

    document.getElementById("formItem").action = editando ? item.url : "<?= e(route('presupuesto.store', $proyecto)) ?>";
    document.getElementById("itemMetodo").value = editando ? "PUT" : "POST";
    document.getElementById("itemTitulo").textContent = editando ? "Editar ítem del presupuesto" : "Agregar ítem al presupuesto";
    document.getElementById("itemBoton").textContent = editando ? "Guardar cambios" : "Agregar ítem";

    ["descripcion_item", "id_tipo_gasto", "cantidad", "unidad_item", "precio_unitario"].forEach(function (nombre) {
        campo(nombre).value = editando ? (item[nombre] ?? "") : (nombre === "id_tipo_gasto" ? MATERIALES : "");
    });

    actualizarTotalItem();
    abrirModal("modalItem");
}

function actualizarTotalItem() {
    const total = (parseFloat(document.getElementById("item_cantidad").value) || 0)
        * (parseFloat(document.getElementById("item_precio_unitario").value) || 0);
    document.getElementById("item_total").textContent = "Total del ítem: $" + Math.round(total).toLocaleString("es-CL");
}

document.getElementById("item_cantidad").addEventListener("input", actualizarTotalItem);
document.getElementById("item_precio_unitario").addEventListener("input", actualizarTotalItem);

function abrirRechazo(url, nombre) {
    document.getElementById("formRechazo").action = url;
    document.getElementById("rechazoTitulo").textContent = "Rechazar gasto: " + nombre;
    abrirModal("modalRechazo");
}

<?php if ($errors->hasAny(['descripcion_item', 'cantidad', 'precio_unitario', 'unidad_item'])): ?>
    abrirItem();
<?php endif; ?>
<?php endif; ?>
</script>

<?= view('pie') ?>
