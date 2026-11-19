<?php
/**
 * Inicio de las páginas internas: <head>, hojas de estilo y barra superior.
 *
 * @var string $titulo
 * @var string[] $estilos  hojas de public/css propias de la página (sin .css)
 * @var string|null $claseTopbar
 * @var string|null $acciones  HTML que reemplaza los datos de usuario en la barra superior
 */
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($titulo) ?></title>
    <?php foreach ($estilos as $estilo): ?>
    <link rel="stylesheet" href="<?= asset('css/'.$estilo.'.css') ?>">
    <?php endforeach; ?>
    <link rel="stylesheet" href="<?= asset('css/comun.css') ?>">
</head>

<body>

<?= view('topbar', ['claseTopbar' => $claseTopbar ?? '', 'acciones' => $acciones ?? null]) ?>
