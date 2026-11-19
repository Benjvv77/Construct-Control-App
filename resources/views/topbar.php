<?php
/**
 * @var int $gastosPendientes  lo agrega el View::composer de AppServiceProvider
 * @var string $claseTopbar
 * @var string|null $acciones
 */
$usuario = auth()->user();
?>
<header class="topbar <?= e($claseTopbar) ?>">
    <div class="brand">
        <a href="<?= route('proyectos.index') ?>">
            <img src="<?= asset('images/logo.png') ?>" alt="ConstructControl" class="brand-logo">
        </a>

        <div class="brand-divider"></div>

        <div class="brand-slogan">
            <span class="helmet">👷</span>
            <p>Construimos control,<br><strong>entregamos resultados.</strong></p>
        </div>
    </div>

    <button type="button" class="menu-toggle" aria-label="Abrir menú" aria-expanded="false" aria-controls="menu-movil"
            onclick="var t=this.closest('.topbar'),a=t.classList.toggle('abierto');this.setAttribute('aria-expanded',a);this.setAttribute('aria-label',a?'Cerrar menú':'Abrir menú');">
        <span></span><span></span><span></span>
        <?php if ($usuario->can('administrar') && $gastosPendientes > 0): ?>
            <i class="menu-toggle-aviso" aria-hidden="true"></i>
        <?php endif; ?>
    </button>

    <div class="menu-movil" id="menu-movil">
    <nav class="nav-principal" aria-label="Navegación principal">
        <a href="<?= route('proyectos.index') ?>" class="<?= request()->routeIs('proyectos.*', 'inventario.*') ? 'activo' : '' ?>">Proyectos</a>
        <?php if ($usuario->can('administrar')): ?>
            <a href="<?= route('gastos.pendientes') ?>" class="<?= request()->routeIs('gastos.pendientes') ? 'activo' : '' ?>">
                Gastos por aprobar
                <?php if ($gastosPendientes > 0): ?>
                    <span class="contador" title="Gastos esperando aprobación"><?= $gastosPendientes ?></span>
                <?php endif; ?>
            </a>
        <?php endif; ?>
        <a href="<?= route('reportes.desviacion') ?>" class="<?= request()->routeIs('reportes.*') ? 'activo' : '' ?>">Reportes</a>
        <?php if ($usuario->can('administrar')): ?>
            <a href="<?= route('auditoria.index') ?>" class="<?= request()->routeIs('auditoria.*') ? 'activo' : '' ?>">Auditoría</a>
            <a href="<?= route('usuarios.index') ?>" class="<?= request()->routeIs('usuarios.*') ? 'activo' : '' ?>">Usuarios</a>
        <?php endif; ?>
    </nav>

    <div class="top-actions">
        <?php if ($acciones): ?>
            <?= $acciones ?>
        <?php else: ?>
            <div class="user-avatar">
                <?= e(mb_strtoupper(mb_substr($usuario->nombre_usuario ?? 'U', 0, 1))) ?>
            </div>

            <div class="user-info">
                <strong><?= e($usuario->correo_usuario) ?></strong>
                <span><?= e($usuario->rol_usuario) ?></span>
            </div>

            <form method="POST" action="<?= route('logout') ?>" class="form-inline">
                <?= csrf_field() ?>
                <button type="submit" class="logout-btn">⏎ Cerrar sesión</button>
            </form>
        <?php endif; ?>
    </div>
    </div>
</header>
