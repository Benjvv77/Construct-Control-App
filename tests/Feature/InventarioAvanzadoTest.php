<?php

namespace Tests\Feature;

use App\Models\Gasto;
use App\Models\ItemPresupuesto;
use App\Models\Material;
use App\Models\MovimientoInventario;
use App\Models\Proyecto;
use App\Services\ComparativoInventario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Materiales creados a mano, stock mínimo, conteo físico y HU14 (inventario vs. presupuesto).
 */
class InventarioAvanzadoTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    private Proyecto $proyecto;

    protected function setUp(): void
    {
        parent::setUp();

        $this->proyecto = Proyecto::factory()->create();
        $this->actingAs($this->proyecto->usuario);
    }

    public function test_crea_un_material_a_mano_con_su_entrada_inicial(): void
    {
        $item = ItemPresupuesto::factory()->materiales('Fierro 12 mm', 50, 6000)->create(['id_proyecto' => $this->proyecto->id_proyecto]);

        $this->post(route('inventario.materiales.store', $this->proyecto), [
            'nombre_material' => 'Fierro 12mm',
            'unidad_material' => 'barras',
            'cantidad_inicial' => 40,
            'stock_minimo' => 10,
            'costo_estimado_material' => 200000,
            'id_item_presupuesto' => $item->id_item_presupuesto,
        ])->assertSessionHasNoErrors();

        $material = $this->proyecto->materiales()->sole();
        $this->assertSame('40.00', $material->stock_actual);
        $this->assertSame($item->id_item_presupuesto, $material->id_item_presupuesto);
        $this->assertDatabaseHas('mglpbc_movimiento_inventario', ['id_material' => $material->id_material, 'tipo_movimiento' => 'ENTRADA', 'descripcion_movimiento' => 'Stock inicial']);
    }

    public function test_no_permite_dos_materiales_activos_con_el_mismo_nombre(): void
    {
        Material::factory()->for($this->proyecto)->create(['nombre_material' => 'Cemento']);

        $this->post(route('inventario.materiales.store', $this->proyecto), [
            'nombre_material' => 'Cemento',
            'unidad_material' => 'sacos',
            'stock_minimo' => 0,
        ])->assertSessionHasErrors('nombre_material');
    }

    public function test_el_administrador_edita_un_material_sin_tocar_el_stock(): void
    {
        $material = Material::factory()->for($this->proyecto)->create(['stock_actual' => 10]);

        $this->actingAs($this->administrador())->put(route('inventario.materiales.update', [$this->proyecto, $material]), [
            'nombre_material' => 'Cemento especial',
            'unidad_material' => 'bolsas',
            'stock_minimo' => 3,
            'costo_estimado_material' => 1000,
            'stock_actual' => 999,
        ])->assertSessionHasNoErrors();

        $material->refresh();
        $this->assertSame('Cemento especial', $material->nombre_material);
        $this->assertSame('3.00', $material->stock_minimo);
        $this->assertSame('10.00', $material->stock_actual);
    }

    public function test_el_estado_de_stock_usa_el_stock_minimo(): void
    {
        $material = Material::factory()->for($this->proyecto)->create(['stock_actual' => 8, 'stock_inicial' => 10, 'stock_minimo' => 8]);
        $this->assertSame('Bajo', $material->nivelStock()['texto']);

        $material->stock_minimo = 2;
        $this->assertSame('Alto', $material->nivelStock()['texto']);
    }

    public function test_el_conteo_fisico_registra_la_diferencia_como_ajuste(): void
    {
        $material = Material::factory()->for($this->proyecto)->create(['stock_actual' => 10]);
        $conteo = fn (float $contado) => $this->post(route('inventario.conteo.store', $this->proyecto), [
            'id_material' => $material->id_material,
            'cantidad_contada' => $contado,
            'fecha_movimiento' => '2026-03-01',
        ]);

        $conteo(7)->assertSessionHas('exito', fn ($m) => str_contains($m, 'faltan 3'));
        $this->assertSame('7.00', $material->refresh()->stock_actual);
        $this->assertDatabaseHas('mglpbc_movimiento_inventario', ['tipo_movimiento' => MovimientoInventario::AJUSTE, 'cantidad_movimiento' => -3]);

        // Si el conteo coincide, no se registra nada
        $conteo(7)->assertSessionHas('exito', fn ($m) => str_contains($m, 'coincide'));
        $this->assertDatabaseCount('mglpbc_movimiento_inventario', 1);
    }

    public function test_compara_el_inventario_con_lo_presupuestado(): void
    {
        $cemento = ItemPresupuesto::factory()->materiales('Cemento 25 kg', 100, 5000)->create(['id_proyecto' => $this->proyecto->id_proyecto]);

        // Se compran 120 sacos en dos gastos: 20 más de lo presupuestado
        $this->actingAs($this->administrador());
        foreach ([80, 40] as $cantidad) {
            $this->post(route('gastos.store', $this->proyecto), [
                'clase_gasto' => 'presupuestado', 'id_item_presupuesto' => $cemento->id_item_presupuesto, 'nombre_gasto' => 'Cemento',
                'monto_gasto' => $cantidad * 5000, 'fecha_gasto' => '2026-02-01', 'cantidad_gasto' => $cantidad, 'unidad_gasto' => 'sacos',
                'documento_gasto' => $this->boleta(),
            ])->assertSessionHasNoErrors();
        }

        $material = $this->proyecto->materiales()->sole();
        $material->registrarMovimiento('SALIDA', 50, '2026-02-10', 'Radier');
        $material->registrarConteo(65, '2026-02-15', null);   // deberían quedar 70: faltan 5
        Material::factory()->for($this->proyecto)->create(['nombre_material' => 'Clavos']);

        [$filaCemento, $filaClavos] = app(ComparativoInventario::class)->calcular($this->proyecto);

        $this->assertEquals(100, $filaCemento['presupuestado']);
        $this->assertEquals(120, $filaCemento['comprado']);
        $this->assertEquals(50, $filaCemento['consumido']);
        $this->assertEquals(-5, $filaCemento['ajustes']);
        $this->assertEquals(65, $filaCemento['stock']);
        $this->assertCount(2, $filaCemento['alertas']);
        $this->assertNull($filaClavos['presupuestado']);

        $this->get(route('inventario.index', $this->proyecto))
            ->assertSee('más de lo presupuestado')
            ->assertSee('Pérdida de 5 sacos')
            ->assertSee('No presupuestado');
    }

    public function test_un_gasto_pendiente_no_mueve_el_inventario(): void
    {
        $cemento = ItemPresupuesto::factory()->materiales()->create(['id_proyecto' => $this->proyecto->id_proyecto]);
        Gasto::factory()->for($this->proyecto)->pendiente()->create(['id_item_presupuesto' => $cemento->id_item_presupuesto, 'id_tipo_gasto' => 1, 'cantidad_gasto' => 10]);

        $this->assertDatabaseCount('mglpbc_material', 0);
        $this->assertDatabaseCount('mglpbc_movimiento_inventario', 0);
    }
}
