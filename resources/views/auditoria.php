<?php
/**
 * HU19: historial de cambios.
 *
 * @var \Illuminate\Pagination\LengthAwarePaginator $registros
 */
$valorLegible = function (mixed $valor): string {
    return match (true) {
        $valor === null => '—',
        is_bool($valor) => $valor ? 'sí' : 'no',
        is_array($valor) => json_encode($valor, JSON_UNESCAPED_UNICODE),
        default => mb_strimwidth((string) $valor, 0, 80, '…'),
    };
};
?>
<?= view('encabezado', ['titulo' => 'ConstructControl — Auditoría', 'estilos' => ['proyectos']]) ?>

<main class="main">

    <section class="hero">
        <div class="hero-content">
            <span class="eyebrow">Administración</span>
            <h1>Registro de cambios</h1>
            <p>Quién creó, modificó, aprobó o eliminó cada dato, y cuándo.</p>
        </div>
    </section>

    <?= view('alertas') ?>

    <section class="projects-card">
        <form method="GET" action="<?= e(route('auditoria.index')) ?>" class="toolbar filtros-auditoria">
            <select name="entidad">
                <option value="">Todo tipo de dato</option>
                <?php foreach ($entidades as $entidad): ?>
                    <option value="<?= e($entidad) ?>" <?= ($filtros['entidad'] ?? '') === $entidad ? 'selected' : '' ?>><?= e($entidad) ?></option>
                <?php endforeach; ?>
            </select>
            <select name="accion">
                <option value="">Toda acción</option>
                <?php foreach (\App\Models\Auditoria::ACCIONES as $clave => $texto): ?>
                    <option value="<?= e($clave) ?>" <?= ($filtros['accion'] ?? '') === $clave ? 'selected' : '' ?>><?= e($texto) ?></option>
                <?php endforeach; ?>
            </select>
            <select name="usuario">
                <option value="">Todo usuario</option>
                <?php foreach ($usuarios as $u): ?>
                    <option value="<?= $u->id_usuario ?>" <?= (int) ($filtros['usuario'] ?? 0) === $u->id_usuario ? 'selected' : '' ?>><?= e($u->nombreCompleto()) ?></option>
                <?php endforeach; ?>
            </select>
            <select name="proyecto">
                <option value="">Todo proyecto</option>
                <?php foreach ($proyectos as $id => $nombre): ?>
                    <option value="<?= $id ?>" <?= (int) ($filtros['proyecto'] ?? 0) === $id ? 'selected' : '' ?>><?= e($nombre) ?></option>
                <?php endforeach; ?>
            </select>
            <input type="date" name="desde" value="<?= e($filtros['desde'] ?? '') ?>" title="Desde">
            <input type="date" name="hasta" value="<?= e($filtros['hasta'] ?? '') ?>" title="Hasta">
            <button type="submit" class="filter active">Filtrar</button>
            <a href="<?= e(route('auditoria.index')) ?>" class="filter">Limpiar</a>
        </form>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Usuario</th>
                        <th>Acción</th>
                        <th>Dato</th>
                        <th>Cambios</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (count($registros) > 0): ?>
                    <?php foreach ($registros as $r): ?>
                        <tr>
                            <td><?= e($r->fecha?->format('Y-m-d H:i')) ?><br><small><?= e($r->ip ?? '') ?></small></td>
                            <td><?= e($r->usuario?->nombreCompleto() ?? 'Sistema') ?></td>
                            <td><span class="status <?= e(['eliminado' => 'pausado', 'rechazado' => 'pausado', 'creado' => 'activo', 'aprobado' => 'activo'][$r->accion] ?? 'alerta') ?>"><?= e($r->accionTexto()) ?></span></td>
                            <td><strong><?= e($r->descripcion) ?></strong><br><small><?= e($r->entidad) ?> #<?= e($r->id_registro) ?></small></td>
                            <td class="celda-cambios">
                                <?php $cambios = $r->cambios ?? []; ?>
                                <?php if (isset($cambios['antes'], $cambios['despues'])): ?>
                                    <?php foreach ($cambios['despues'] as $campo => $nuevo): ?>
                                        <div><code><?= e($campo) ?></code>: <?= e($valorLegible($cambios['antes'][$campo] ?? null)) ?> → <strong><?= e($valorLegible($nuevo)) ?></strong></div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <details>
                                        <summary><?= $r->accion === 'creado' ? 'Valores iniciales' : 'Valores al eliminar' ?></summary>
                                        <?php foreach (($cambios['despues'] ?? $cambios['antes'] ?? []) as $campo => $valor): ?>
                                            <div><code><?= e($campo) ?></code>: <?= e($valorLegible($valor)) ?></div>
                                        <?php endforeach; ?>
                                    </details>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="5" class="empty">No hay cambios registrados con estos filtros.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="pagination">
            <?= view('paginacion', ['paginador' => $registros, 'nombre' => 'registros']) ?>
        </div>
    </section>

</main>

<?= view('pie') ?>
