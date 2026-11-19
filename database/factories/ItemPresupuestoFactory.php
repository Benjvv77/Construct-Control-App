<?php

namespace Database\Factories;

use App\Models\ItemPresupuesto;
use App\Models\Proyecto;
use App\Models\TipoGasto;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ItemPresupuesto>
 */
class ItemPresupuestoFactory extends Factory
{
    public function definition(): array
    {
        return [
            'id_proyecto' => Proyecto::factory()->sinPresupuesto(),
            'id_tipo_gasto' => 2,
            'descripcion_item' => 'Mano de obra',
            'cantidad' => 10,
            'unidad_item' => 'días',
            'precio_unitario' => 50000,
        ];
    }

    public function materiales(string $descripcion = 'Cemento 25 kg', float $cantidad = 100, float $precio = 5000): static
    {
        return $this->state(fn () => [
            'id_tipo_gasto' => TipoGasto::MATERIALES,
            'descripcion_item' => $descripcion,
            'cantidad' => $cantidad,
            'unidad_item' => 'sacos',
            'precio_unitario' => $precio,
        ]);
    }
}
