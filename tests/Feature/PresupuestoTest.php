<?php

namespace Tests\Feature;

use App\Models\Gasto;
use App\Models\ItemPresupuesto;
use App\Models\Proyecto;
use App\Services\ResumenPresupuesto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * HU07 (ítems del presupuesto) y HU11 (comparativo presupuesto vs. gasto real).
 */
class PresupuestoTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    private Proyecto $proyecto;

    protected function setUp(): void
    {
        parent::setUp();

        $this->proyecto = Proyecto::factory()->sinPresupuesto()->create();
    }

    private function item(array $cambios = []): array
    {
        return [
            'descripcion_item' => 'Mano de obra',
            'id_tipo_gasto' => 2,
            'cantidad' => 10,
            'unidad_item' => 'días',
            'precio_unitario' => 50000,
            ...$cambios,
        ];
    }

    public function test_el_presupuesto_del_proyecto_es_la_suma_de_sus_items(): void
    {
        $this->actingAs($this->administrador());

        $this->post(route('presupuesto.store', $this->proyecto), $this->item())->assertSessionHasNoErrors();
        $this->post(route('presupuesto.store', $this->proyecto), $this->item([
            'descripcion_item' => 'Cemento', 'id_tipo_gasto' => 1, 'cantidad' => 100, 'unidad_item' => 'sacos', 'precio_unitario' => 5000,
        ]))->assertSessionHasNoErrors();

        $this->assertSame('1000000.00', $this->proyecto->fresh()->presupuesto_proyecto);

        $mano = $this->proyecto->itemsPresupuesto()->where('descripcion_item', 'Mano de obra')->sole();
        $this->put(route('presupuesto.update', [$this->proyecto, $mano]), $this->item(['cantidad' => 4]))->assertSessionHasNoErrors();
        $this->assertSame('700000.00', $this->proyecto->fresh()->presupuesto_proyecto);

        $this->delete(route('presupuesto.destroy', [$this->proyecto, $mano]))->assertSessionHasNoErrors();
        $this->assertSame('500000.00', $this->proyecto->fresh()->presupuesto_proyecto);
    }

    public function test_valida_los_items(): void
    {
        $this->actingAs($this->administrador())
            ->post(route('presupuesto.store', $this->proyecto), $this->item(['descripcion_item' => '', 'cantidad' => 0, 'precio_unitario' => -5, 'id_tipo_gasto' => 99]))
            ->assertSessionHasErrors(['descripcion_item', 'cantidad', 'precio_unitario', 'id_tipo_gasto']);

        $this->assertDatabaseCount('mglpbc_item_presupuesto', 0);
    }

    public function test_un_item_con_gastos_no_se_puede_eliminar(): void
    {
        $item = ItemPresupuesto::factory()->create(['id_proyecto' => $this->proyecto->id_proyecto]);
        Gasto::factory()->for($this->proyecto)->create(['id_item_presupuesto' => $item->id_item_presupuesto]);

        $this->actingAs($this->administrador())
            ->delete(route('presupuesto.destroy', [$this->proyecto, $item]))
            ->assertSessionHasErrors('item');

        $this->assertModelExists($item);
    }

    public function test_el_supervisor_no_gestiona_el_presupuesto(): void
    {
        $item = ItemPresupuesto::factory()->create(['id_proyecto' => $this->proyecto->id_proyecto]);
        $this->actingAs($this->proyecto->usuario);

        $this->post(route('presupuesto.store', $this->proyecto), $this->item())->assertForbidden();
        $this->put(route('presupuesto.update', [$this->proyecto, $item]), $this->item())->assertForbidden();
        $this->delete(route('presupuesto.destroy', [$this->proyecto, $item]))->assertForbidden();
        $this->get(route('proyectos.show', $this->proyecto))->assertDontSee('+ Agregar ítem');
    }

    public function test_compara_lo_presupuestado_con_lo_gastado_por_item(): void
    {
        $obra = ItemPresupuesto::factory()->create(['id_proyecto' => $this->proyecto->id_proyecto, 'descripcion_item' => 'Obra gruesa', 'cantidad' => 1, 'precio_unitario' => 500000]);
        $terminaciones = ItemPresupuesto::factory()->create(['id_proyecto' => $this->proyecto->id_proyecto, 'descripcion_item' => 'Terminaciones', 'cantidad' => 1, 'precio_unitario' => 500000]);

        Gasto::factory()->for($this->proyecto)->create(['id_item_presupuesto' => $obra->id_item_presupuesto, 'monto_gasto' => 300000]);
        Gasto::factory()->for($this->proyecto)->pendiente()->create(['id_item_presupuesto' => $obra->id_item_presupuesto, 'monto_gasto' => 100000]);
        Gasto::factory()->for($this->proyecto)->create(['id_item_presupuesto' => $terminaciones->id_item_presupuesto, 'monto_gasto' => 600000]);
        Gasto::factory()->for($this->proyecto)->adicional()->create(['monto_gasto' => 50000]);

        $resumen = app(ResumenPresupuesto::class)->calcular($this->proyecto);
        [$filaObra, $filaTerminaciones] = $resumen['filas'];

        $this->assertEquals(300000, $filaObra['gastado']);
        $this->assertEquals(100000, $filaObra['pendiente']);
        $this->assertSame('ok', $filaObra['estado']);
        $this->assertEquals(100000, $filaTerminaciones['desviacion']);
        $this->assertSame(ResumenPresupuesto::SOBRECOSTO, $filaTerminaciones['estado']);
        $this->assertEquals(50000, $resumen['adicionales']['gastado']);
        $this->assertEquals(950000, $resumen['gastado']);
        $this->assertSame(1, $resumen['items_con_problemas']);

        $this->actingAs($this->proyecto->usuario)
            ->get(route('proyectos.show', $this->proyecto))
            ->assertSee('Sobrecosto')
            ->assertSee('+$100.000', false)
            ->assertSee('$100.000 por aprobar', false);
    }
}
