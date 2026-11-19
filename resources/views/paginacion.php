<?php
/**
 * Paginación simple con el estilo de la app.
 *
 * @var \Illuminate\Pagination\LengthAwarePaginator $paginador
 * @var string $nombre  qué se está paginando ("proyectos", "órdenes"...)
 */
?>
<div class="paginacion">
    <span>
        <?php if ($paginador->total() > 0): ?>
            Mostrando <?= $paginador->firstItem() ?>–<?= $paginador->lastItem() ?> de <?= $paginador->total() ?> <?= e($nombre) ?>
        <?php else: ?>
            Sin <?= e($nombre) ?> para mostrar
        <?php endif; ?>
    </span>

    <?php if ($paginador->hasPages()): ?>
        <nav class="paginacion-links" aria-label="Paginación">
            <?php if ($paginador->onFirstPage()): ?>
                <span class="deshabilitado">‹</span>
            <?php else: ?>
                <a href="<?= e($paginador->previousPageUrl()) ?>" rel="prev">‹</a>
            <?php endif; ?>

            <?php foreach ($paginador->getUrlRange(1, $paginador->lastPage()) as $pagina => $url): ?>
                <?php if ($pagina === $paginador->currentPage()): ?>
                    <span class="actual" aria-current="page"><?= $pagina ?></span>
                <?php else: ?>
                    <a href="<?= e($url) ?>"><?= $pagina ?></a>
                <?php endif; ?>
            <?php endforeach; ?>

            <?php if ($paginador->hasMorePages()): ?>
                <a href="<?= e($paginador->nextPageUrl()) ?>" rel="next">›</a>
            <?php else: ?>
                <span class="deshabilitado">›</span>
            <?php endif; ?>
        </nav>
    <?php endif; ?>
</div>
