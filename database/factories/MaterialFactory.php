<?php

namespace Database\Factories;

use App\Models\Material;
use App\Models\Proyecto;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Material>
 */
class MaterialFactory extends Factory
{
    public function definition(): array
    {
        return [
            'id_proyecto' => Proyecto::factory(),
            'nombre_material' => 'Cemento',
            'unidad_material' => 'sacos',
            'stock_actual' => 10,
            'stock_inicial' => 10,
            'stock_minimo' => 5,
            'costo_estimado_material' => 50000,
            'estado_material' => 1,
        ];
    }
}
