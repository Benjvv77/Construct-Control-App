<?php
/**
 * Inicio de las pantallas de acceso (login y recuperación de contraseña).
 *
 * @var string $titulo
 */
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($titulo) ?></title>

    <link href="<?= asset('css/login.css') ?>" rel="stylesheet">
</head>

<body>

<div class="wrapper">

    <!-- PANEL IZQUIERDO -->
    <div class="left-panel">

        <div class="left-logo">
            <div class="left-logo-icon">
                <svg viewBox="0 0 16 16">
                    <rect x="2" y="8" width="5" height="6"/>
                    <rect x="9" y="4" width="5" height="10"/>
                    <line x1="2" y1="8" x2="14" y2="8"/>
                </svg>
            </div>

            <span class="left-logo-text">
                ConstructControl
            </span>
        </div>

        <div class="left-body">

            <h2 class="left-tagline">
                Control total de tus proyectos de construcción
            </h2>

            <p class="left-desc">
                Gestiona presupuestos, gastos, inventario y genera reportes con Curva S en tiempo real.
            </p>

        </div>

        <div class="left-features">

            <div class="feature">
                <div class="feature-dot"></div>
                Alertas automáticas de desviación presupuestaria
            </div>

            <div class="feature">
                <div class="feature-dot"></div>
                Órdenes de compra con aprobación digital
            </div>

            <div class="feature">
                <div class="feature-dot"></div>
                Curva S actualizada en tiempo real
            </div>

            <div class="feature">
                <div class="feature-dot"></div>
                Control de inventario por proyecto
            </div>

        </div>

    </div>

    <!-- PANEL DERECHO -->
    <div class="right-panel">
