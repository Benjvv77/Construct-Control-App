<?php

/**
 * Funciones de formato que usan las vistas.
 */

/**
 * Monto en pesos chilenos: pesos(1500000) -> $1.500.000, pesos(-20000) -> -$20.000
 */
function pesos(mixed $monto): string
{
    $monto = (float) $monto;

    return ($monto < 0 ? '-' : '').'$'.number_format(abs($monto), 0, ',', '.');
}

/**
 * Cantidad con decimales solo si los tiene: numero(12.5) -> 12,5; numero(10) -> 10
 */
function numero(mixed $valor): string
{
    return rtrim(rtrim(number_format((float) $valor, 2, ',', '.'), '0'), ',');
}
