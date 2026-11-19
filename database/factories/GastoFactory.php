<?php

namespace Database\Factories;

use App\Models\Gasto;
use App\Models\Proyecto;
use App\Models\TipoCosto;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Gasto>
 */
class GastoFactory extends Factory
{
    public function definition(): array
    {
        return [
            'id_proyecto' => Proyecto::factory(),
            'id_tipo_costo' => 1,
            'id_tipo_gasto' => 2,
            'nombre_gasto' => fake()->words(2, true),
            'descripcion_gasto' => null,
            'monto_gasto' => 50000,
            'fecha_gasto' => '2026-02-01',
            'documento_gasto' => null,
            'cantidad_gasto' => 0,
            'estado_gasto' => Gasto::APROBADO,
        ];
    }

    public function pendiente(): static
    {
        return $this->state(fn () => ['estado_gasto' => Gasto::PENDIENTE]);
    }

    public function adicional(): static
    {
        return $this->state(fn () => ['id_tipo_costo' => TipoCosto::ADICIONAL, 'descripcion_gasto' => 'Imprevisto']);
    }
}
