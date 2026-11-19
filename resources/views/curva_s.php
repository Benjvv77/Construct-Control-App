<?php /*
    Curva S en SVG: se usa igual en la página del reporte y en el PDF.
    Recibe $puntos: lista de ['fecha' => 'Y-m-d', 'planificado' => float, 'real' => ?float]. */ ?>
<?php
    $ancho = 900;
    $alto = 340;
    $izq = 78;
    $der = 20;
    $arriba = 44;
    $abajo = 42;
    $areaAncho = $ancho - $izq - $der;
    $areaAlto = $alto - $arriba - $abajo;

    $n = count($puntos);
    $maximo = max(1, ...array_map(fn ($p) => max($p['planificado'], $p['real'] ?? 0), $puntos));

    $x = fn (int $i) => round($izq + ($n > 1 ? $areaAncho * $i / ($n - 1) : 0), 1);
    $y = fn (float $v) => round($arriba + $areaAlto * (1 - $v / $maximo), 1);

    $abreviar = function (float $monto): string {
        return match (true) {
            $monto >= 1_000_000 => '$'.rtrim(rtrim(number_format($monto / 1_000_000, 1, ',', '.'), '0'), ',').'M',
            $monto >= 1_000 => '$'.number_format($monto / 1_000, 0, ',', '.').'K',
            default => '$'.number_format($monto, 0, ',', '.'),
        };
    };

    $lineaPlan = collect($puntos)->map(fn ($p, $i) => $x($i).','.$y($p['planificado']))->implode(' ');
    $lineaReal = collect($puntos)->filter(fn ($p) => $p['real'] !== null)->map(fn ($p, $i) => $x($i).','.$y($p['real']))->implode(' ');
    $saltoEtiquetas = max(1, (int) ceil($n / 8));
?>
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 <?= e($ancho) ?> <?= e($alto) ?>" width="<?= e($ancho) ?>" height="<?= e($alto) ?>" role="img" aria-label="Curva S: gasto planificado y real acumulado" style="max-width:100%;height:auto;font-family:Arial, sans-serif;">
    <?php for ($i = 0; $i <= 4; $i++): ?>
        <?php $valor = $maximo * (4 - $i) / 4; $yy = $y($valor);
        ?>
        <line x1="<?= e($izq) ?>" y1="<?= e($yy) ?>" x2="<?= e($ancho - $der) ?>" y2="<?= e($yy) ?>" stroke="#E8D9C8" stroke-width="1" />
        <text x="<?= e($izq - 8) ?>" y="<?= e($yy + 4) ?>" font-size="11" fill="#7A5C3E" text-anchor="end"><?= e($abreviar($valor)) ?></text>
    <?php endfor; ?>

    <?php foreach ($puntos as $i => $punto): ?>
        <?php /* La última fecha siempre se muestra; la anterior se omite si quedaría encima */ ?>
        <?php if ($i === $n - 1 || ($i % $saltoEtiquetas === 0 && $n - 1 - $i >= $saltoEtiquetas)): ?>
            <text x="<?= e($x($i)) ?>" y="<?= e($alto - 14) ?>" font-size="11" fill="#7A5C3E" text-anchor="middle"><?= e(\Carbon\Carbon::parse($punto['fecha'])->format('d/m/y')) ?></text>
        <?php endif; ?>
    <?php endforeach; ?>

    <polyline points="<?= e($lineaPlan) ?>" fill="none" stroke="#C5A07A" stroke-width="3" stroke-dasharray="8 5" />

    <?php if ($lineaReal !== ''): ?>
        <polyline points="<?= e($lineaReal) ?>" fill="none" stroke="#6E4620" stroke-width="3" />
        <?php foreach ($puntos as $i => $punto): ?>
            <?php if ($punto['real'] !== null): ?>
                <circle cx="<?= e($x($i)) ?>" cy="<?= e($y($punto['real'])) ?>" r="3.5" fill="#6E4620" />
            <?php endif; ?>
        <?php endforeach; ?>
    <?php endif; ?>

    <rect x="<?= e($izq) ?>" y="12" width="14" height="4" fill="#6E4620" />
    <text x="<?= e($izq + 20) ?>" y="18" font-size="12" fill="#4F2F0E">Real</text>
    <rect x="<?= e($izq + 80) ?>" y="12" width="14" height="4" fill="#C5A07A" />
    <text x="<?= e($izq + 100) ?>" y="18" font-size="12" fill="#4F2F0E">Planificado</text>
</svg>
