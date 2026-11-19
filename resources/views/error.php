<?php
/**
 * Página de error para todos los códigos HTTP (HU20).
 *
 * @var int $codigo
 * @var string|null $detalle  mensaje propio de la aplicación, si lo hay
 */
$errores = [
    403 => ['No tienes permiso', 'Tu usuario no tiene permiso para ver o hacer esto. Si crees que deberías tenerlo, pídeselo a un administrador.'],
    404 => ['No encontramos lo que buscas', 'La página o el registro no existe, o fue eliminado. Revisa la dirección o vuelve al listado.'],
    405 => ['Acción no permitida', 'Esa acción no se puede hacer de esta forma. Vuelve atrás y usa los botones de la página.'],
    419 => ['Tu sesión expiró', 'Pasó demasiado tiempo desde que abriste la página. Vuelve atrás, recarga la página e inténtalo de nuevo.'],
    429 => ['Demasiados intentos', 'Hiciste demasiados intentos seguidos. Espera un minuto y vuelve a intentarlo.'],
    500 => ['Ocurrió un error inesperado', 'Algo falló en el sistema y ya quedó registrado. Inténtalo de nuevo; si se repite, avisa al administrador.'],
    503 => ['Sistema en mantención', 'Estamos haciendo mejoras. Vuelve a intentarlo en unos minutos.'],
];
[$titulo, $explicacion] = $errores[$codigo] ?? ['Algo salió mal', 'No se pudo completar la acción. Vuelve atrás e inténtalo de nuevo.'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($titulo) ?> — ConstructControl</title>
    <link rel="stylesheet" href="<?= asset('css/comun.css') ?>">
</head>
<body class="pagina-error">

<main class="caja-error">
    <img src="<?= asset('images/logo.png') ?>" alt="ConstructControl" class="logo-error">
    <span class="codigo-error">Error <?= (int) $codigo ?></span>
    <h1><?= e($titulo) ?></h1>
    <p><?= e($explicacion) ?></p>

    <?php if ($detalle): ?>
        <p class="detalle-error"><?= e($detalle) ?></p>
    <?php endif; ?>

    <div class="acciones-error">
        <a href="javascript:history.back()" class="boton-error secundario">← Volver</a>
        <a href="<?= auth()->check() ? route('proyectos.index') : route('login') ?>" class="boton-error">
            <?= auth()->check() ? 'Ir a proyectos' : 'Iniciar sesión' ?>
        </a>
    </div>
</main>

</body>
</html>
