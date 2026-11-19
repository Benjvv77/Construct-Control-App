<?php
/**
 * Campos de un gasto, compartidos por el formulario "Nuevo gasto" y el modal "Editar gasto".
 *
 * Un gasto es presupuestado (se elige un ítem del presupuesto y la boleta es obligatoria, HU08)
 * o adicional (se elige el tipo de gasto y se justifica, HU09). El JavaScript de detalle_proyecto.php
 * muestra u oculta los campos según la opción elegida.
 *
 * @var string $prefijo  "nuevo" o "editar": distingue los id de los dos formularios
 * @var \Illuminate\Support\Collection $items
 * @var \Illuminate\Support\Collection $tiposGasto
 * @var bool $conOld  true en el formulario nuevo: recupera lo escrito si falló la validación
 */
$valor = fn (string $campo, mixed $porDefecto = '') => $conOld ? old($campo, $porDefecto) : $porDefecto;
$clase = $valor('clase_gasto', count($items) > 0 ? 'presupuestado' : 'adicional');
?>
<p class="nota-material" id="<?= $prefijo ?>_nota_aprobado">
    Este gasto ya está aprobado y afectó el presupuesto: solo se corrigen el monto, la fecha, la descripción
    y el documento (y el nombre, si no es de materiales).
</p>

<div class="form-group">
    <label>Tipo de gasto</label>
    <div class="opciones-clase">
        <label>
            <input type="radio" name="clase_gasto" value="presupuestado" id="<?= $prefijo ?>_clase_presupuestado"
                <?= $clase === 'presupuestado' ? 'checked' : '' ?> <?= count($items) === 0 ? 'disabled' : '' ?>>
            Presupuestado
        </label>
        <label>
            <input type="radio" name="clase_gasto" value="adicional" id="<?= $prefijo ?>_clase_adicional"
                <?= $clase === 'adicional' ? 'checked' : '' ?>>
            Adicional (imprevisto)
        </label>
    </div>
    <?php if (count($items) === 0): ?>
        <small class="ayuda-campo">El proyecto aún no tiene ítems de presupuesto: por ahora solo se pueden registrar gastos adicionales.</small>
    <?php endif; ?>
</div>

<div class="form-group" id="<?= $prefijo ?>_campo_item">
    <label>Ítem del presupuesto</label>
    <select name="id_item_presupuesto" id="<?= $prefijo ?>_id_item_presupuesto">
        <option value="">Seleccione</option>
        <?php foreach ($items as $item): ?>
            <option value="<?= $item->id_item_presupuesto ?>"
                data-tipo="<?= $item->id_tipo_gasto ?>"
                data-unidad="<?= e($item->unidad_item) ?>"
                <?= (int) $valor('id_item_presupuesto') === $item->id_item_presupuesto ? 'selected' : '' ?>>
                <?= e($item->descripcion_item) ?> — <?= e($item->tipoGasto->nombre_tipo_gasto) ?>
            </option>
        <?php endforeach; ?>
    </select>
</div>

<div class="form-group" id="<?= $prefijo ?>_campo_tipo">
    <label>Tipo de gasto adicional</label>
    <select name="id_tipo_gasto" id="<?= $prefijo ?>_id_tipo_gasto">
        <option value="">Seleccione</option>
        <?php foreach ($tiposGasto as $tipo): ?>
            <option value="<?= $tipo->id_tipo_gasto ?>" <?= (int) $valor('id_tipo_gasto') === $tipo->id_tipo_gasto ? 'selected' : '' ?>>
                <?= e($tipo->nombre_tipo_gasto) ?>
            </option>
        <?php endforeach; ?>
    </select>
</div>

<div class="form-group">
    <label>Nombre del gasto</label>
    <input type="text" name="nombre_gasto" id="<?= $prefijo ?>_nombre_gasto" value="<?= e($valor('nombre_gasto')) ?>"
        placeholder="Ej: Cemento para radier" maxlength="255" required>
</div>

<div class="form-group">
    <label>Monto ($)</label>
    <input type="number" name="monto_gasto" id="<?= $prefijo ?>_monto_gasto" value="<?= e($valor('monto_gasto')) ?>" placeholder="0" min="1" required>
</div>

<div class="form-group" id="<?= $prefijo ?>_campo_cantidad">
    <label>Cantidad de material</label>
    <input type="number" step="0.01" min="0.01" name="cantidad_gasto" id="<?= $prefijo ?>_cantidad_gasto" value="<?= e($valor('cantidad_gasto')) ?>">
</div>

<div class="form-group" id="<?= $prefijo ?>_campo_unidad">
    <label>Unidad</label>
    <input type="text" name="unidad_gasto" id="<?= $prefijo ?>_unidad_gasto" value="<?= e($valor('unidad_gasto')) ?>"
        placeholder="Ej: sacos, m3, barras" maxlength="30">
</div>

<div class="form-group">
    <label>Fecha</label>
    <input type="date" name="fecha_gasto" id="<?= $prefijo ?>_fecha_gasto" value="<?= e($valor('fecha_gasto', now()->toDateString())) ?>" required>
</div>

<div class="form-group">
    <label id="<?= $prefijo ?>_label_descripcion">Descripción</label>
    <textarea name="descripcion_gasto" id="<?= $prefijo ?>_descripcion_gasto" maxlength="2000"
        placeholder="Detalle del gasto"><?= e($valor('descripcion_gasto')) ?></textarea>
</div>

<div class="form-group">
    <label id="<?= $prefijo ?>_label_documento">Boleta o factura</label>
    <input type="file" name="documento_gasto" id="<?= $prefijo ?>_documento_gasto" accept=".pdf,.jpg,.jpeg,.png">
    <small class="ayuda-campo">PDF, JPG o PNG de hasta 5 MB.</small>
</div>

