<?php

namespace Database\Factories;

use App\Models\Proyecto;
use App\Models\TipoGasto;
use App\Models\Usuario;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Proyecto>
 */
class ProyectoFactory extends Factory
{
    public function definition(): array
    {
        return [
            'id_usuario' => Usuario::factory(),
            'nombre_proyecto' => 'Obra '.fake()->streetName(),
            'descripcion_proyecto' => fake()->sentence(),
            'fecha_inicio_proyecto' => '2026-01-01',
            'fecha_termino_proyecto' => '2026-12-31',
            'estado_proyecto' => 1,
            'presupuesto_proyecto' => 1000000,
        ];
    }

    /**
     * El presupuesto es la suma de los ítems: el monto indicado queda como ítem "Presupuesto general".
     */
    public function configure(): static
    {
        return $this->afterCreating(function (Proyecto $proyecto) {
            if ((float) $proyecto->presupuesto_proyecto > 0 && ! $proyecto->itemsPresupuesto()->exists()) {
                $proyecto->itemsPresupuesto()->create([
                    'id_tipo_gasto' => TipoGasto::firstOrCreate(['nombre_tipo_gasto' => 'Otros'])->id_tipo_gasto,
                    'descripcion_item' => 'Presupuesto general',
                    'cantidad' => 1,
                    'unidad_item' => 'global',
                    'precio_unitario' => $proyecto->presupuesto_proyecto,
                ]);
            }
        });
    }

    public function sinPresupuesto(): static
    {
        return $this->state(fn () => ['presupuesto_proyecto' => 0]);
    }
}
