<?php

namespace Tests\Feature;

use App\Models\Material;
use App\Models\Proyecto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * HU12 (entradas) y HU13 (salidas) de materiales.
 */
class InventarioTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    private Proyecto $proyecto;

    private Material $material;

    protected function setUp(): void
    {
        parent::setUp();

        $this->proyecto = Proyecto::factory()->create();
        $this->material = Material::factory()->for($this->proyecto)->create(['stock_actual' => 10, 'stock_inicial' => 10]);
        $this->actingAs($this->proyecto->usuario);
    }

    private function movimiento(string $tipo, float $cantidad, ?Proyecto $proyecto = null)
    {
        return $this->post(route('inventario.movimientos.store', $proyecto ?? $this->proyecto), [
            'id_material' => $this->material->id_material,
            'tipo_movimiento' => $tipo,
            'cantidad_movimiento' => $cantidad,
            'fecha_movimiento' => '2026-02-02',
            'descripcion_movimiento' => 'Uso en obra',
        ]);
    }

    public function test_el_supervisor_registra_salidas_y_entradas(): void
    {
        $this->movimiento('SALIDA', 4)->assertSessionHasNoErrors();
        $this->movimiento('ENTRADA', 2)->assertSessionHasNoErrors();

        $this->assertSame('8.00', $this->material->refresh()->stock_actual);
        $this->assertDatabaseCount('mglpbc_movimiento_inventario', 2);
        $this->get(route('inventario.index', $this->proyecto))->assertSee('ENTRADA')->assertSee('SALIDA');
    }

    public function test_una_salida_mayor_al_stock_se_rechaza(): void
    {
        $this->movimiento('SALIDA', 99)->assertSessionHasErrors('cantidad_movimiento');

        $this->assertSame('10.00', $this->material->refresh()->stock_actual);
        $this->assertDatabaseCount('mglpbc_movimiento_inventario', 0);
    }

    public function test_rechaza_tipos_de_movimiento_desconocidos(): void
    {
        $this->movimiento('ROBO', 1)->assertSessionHasErrors('tipo_movimiento');
        $this->movimiento('AJUSTE', 1)->assertSessionHasErrors('tipo_movimiento');
    }

    public function test_no_mueve_material_de_otro_proyecto(): void
    {
        $otroProyecto = Proyecto::factory()->for($this->proyecto->usuario)->create();

        $this->movimiento('SALIDA', 1, $otroProyecto)->assertSessionHasErrors('id_material');

        $this->assertSame('10.00', $this->material->refresh()->stock_actual);
    }

    public function test_solo_el_administrador_edita_o_elimina_materiales(): void
    {
        $this->delete(route('inventario.materiales.destroy', [$this->proyecto, $this->material]))->assertForbidden();
        $this->assertModelExists($this->material);

        $this->movimiento('SALIDA', 1);
        $this->actingAs($this->administrador())
            ->delete(route('inventario.materiales.destroy', [$this->proyecto, $this->material]))
            ->assertRedirect(route('inventario.index', $this->proyecto));

        $this->assertModelMissing($this->material);
        $this->assertDatabaseCount('mglpbc_movimiento_inventario', 0);
    }

    public function test_no_elimina_material_a_traves_de_otro_proyecto(): void
    {
        $otroProyecto = Proyecto::factory()->create();

        $this->actingAs($this->administrador())
            ->delete(route('inventario.materiales.destroy', [$otroProyecto, $this->material]))
            ->assertNotFound();

        $this->assertModelExists($this->material);
    }
}
