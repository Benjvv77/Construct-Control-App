<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Tipos de costo y de gasto con los mismos IDs que el sistema original
 * (el formulario de gastos depende de que "Materiales" sea el 1).
 */
class CatalogoSeeder extends Seeder
{
    public const TIPOS_COSTO = [
        1 => 'Normal',
        2 => 'Extra',
    ];

    public const TIPOS_GASTO = [
        1 => 'Materiales',
        2 => 'Mano de Obra',
        3 => 'Maquinaria',
        4 => 'Transporte',
        5 => 'Herramientas',
        6 => 'Permisos',
        7 => 'Servicios',
        8 => 'Subcontratos',
        9 => 'Otros',
    ];

    public function run(): void
    {
        DB::table('mglpbc_tipo_costo')->upsert(
            collect(self::TIPOS_COSTO)->map(fn ($nombre, $id) => ['id_tipo_costo' => $id, 'nombre_tipo_costo' => $nombre])->values()->all(),
            ['id_tipo_costo'],
            ['nombre_tipo_costo']
        );

        DB::table('mglpbc_tipo_gastos')->upsert(
            collect(self::TIPOS_GASTO)->map(fn ($nombre, $id) => ['id_tipo_gasto' => $id, 'nombre_tipo_gasto' => $nombre])->values()->all(),
            ['id_tipo_gasto'],
            ['nombre_tipo_gasto']
        );

        foreach (['mglpbc_tipo_costo' => 'id_tipo_costo', 'mglpbc_tipo_gastos' => 'id_tipo_gasto'] as $tabla => $clave) {
            DB::statement("SELECT setval(pg_get_serial_sequence('$tabla', '$clave'), (SELECT MAX($clave) FROM $tabla))");
        }
    }
}
