<?php

namespace Tests\Feature;

use App\Models\Gasto;
use App\Models\ItemPresupuesto;
use App\Models\MovimientoInventario;
use App\Models\Proyecto;
use App\Models\TipoCosto;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * HU08 (gasto presupuestado con boleta), HU09 (gasto adicional) y HU10 (aprobación).
 */
class AprobacionGastosTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    private Proyecto $proyecto;

    private Usuario $supervisor;

    private ItemPresupuesto $cemento;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake();
        $this->cemento = ItemPresupuesto::factory()->materiales('Cemento 25 kg', 100, 5000)->create();
        $this->proyecto = $this->cemento->proyecto;
        $this->supervisor = $this->proyecto->usuario;
    }

    private function gastoCemento(array $cambios = []): array
    {
        return [
            'clase_gasto' => 'presupuestado',
            'id_item_presupuesto' => $this->cemento->id_item_presupuesto,
            'nombre_gasto' => 'Compra de cemento',
            'monto_gasto' => 100000,
            'fecha_gasto' => '2026-02-01',
            'cantidad_gasto' => 20,
            'unidad_gasto' => 'sacos',
            'documento_gasto' => $this->boleta(),
            ...$cambios,
        ];
    }

    private function gastoAdicional(array $cambios = []): array
    {
        return [
            'clase_gasto' => 'adicional',
            'id_tipo_gasto' => 4,
            'nombre_gasto' => 'Flete urgente',
            'descripcion_gasto' => 'El proveedor no despachó a tiempo',
            'monto_gasto' => 40000,
            'fecha_gasto' => '2026-02-03',
            ...$cambios,
        ];
    }

    public function test_el_gasto_del_supervisor_queda_pendiente_y_no_afecta_nada(): void
    {
        $this->actingAs($this->supervisor)
            ->post(route('gastos.store', $this->proyecto), $this->gastoCemento())
            ->assertSessionHas('exito', fn ($m) => str_contains($m, 'pendiente'));

        $gasto = Gasto::sole();
        $this->assertSame(Gasto::PENDIENTE, $gasto->estado_gasto);
        $this->assertSame($this->supervisor->id_usuario, $gasto->id_usuario_registro);
        $this->assertSame(0.0, $this->proyecto->totalGastado());
        $this->assertDatabaseCount('mglpbc_material', 0);
    }

    public function test_un_gasto_presupuestado_exige_boleta_y_un_item_del_proyecto(): void
    {
        $ajeno = ItemPresupuesto::factory()->create();
        $this->actingAs($this->supervisor);

        $this->post(route('gastos.store', $this->proyecto), $this->gastoCemento(['documento_gasto' => null]))
            ->assertSessionHasErrors(['documento_gasto' => 'Adjunta la boleta o factura: es obligatoria en los gastos presupuestados.']);

        $this->post(route('gastos.store', $this->proyecto), $this->gastoCemento(['id_item_presupuesto' => $ajeno->id_item_presupuesto]))
            ->assertSessionHasErrors('id_item_presupuesto');

        $this->assertDatabaseCount('mglpbc_gastos', 0);
    }

    public function test_un_gasto_adicional_exige_justificacion_pero_no_boleta(): void
    {
        $this->actingAs($this->supervisor);

        $this->post(route('gastos.store', $this->proyecto), $this->gastoAdicional(['descripcion_gasto' => '']))
            ->assertSessionHasErrors(['descripcion_gasto' => 'Explica por qué se necesita este gasto adicional.']);

        $this->post(route('gastos.store', $this->proyecto), $this->gastoAdicional())->assertSessionHasNoErrors();

        $gasto = Gasto::sole();
        $this->assertSame(TipoCosto::ADICIONAL, $gasto->id_tipo_costo);
        $this->assertNull($gasto->id_item_presupuesto);
        $this->assertTrue($gasto->esExtra());
    }

    public function test_el_gasto_del_administrador_queda_aprobado_y_el_material_entra_al_inventario(): void
    {
        $admin = $this->administrador();

        $this->actingAs($admin)->post(route('gastos.store', $this->proyecto), $this->gastoCemento())->assertSessionHasNoErrors();

        $gasto = Gasto::sole();
        $this->assertSame(Gasto::APROBADO, $gasto->estado_gasto);
        $this->assertSame(100000.0, $this->proyecto->totalGastado());

        $material = $this->proyecto->materiales()->sole();
        $this->assertSame($this->cemento->id_item_presupuesto, $material->id_item_presupuesto);
        $this->assertSame('Cemento 25 kg', $material->nombre_material);
        $this->assertSame('20.00', $material->stock_actual);
        $this->assertDatabaseHas('mglpbc_movimiento_inventario', [
            'id_material' => $material->id_material,
            'tipo_movimiento' => MovimientoInventario::ENTRADA,
            'cantidad_movimiento' => 20,
        ]);
    }

    public function test_el_administrador_aprueba_un_gasto_pendiente(): void
    {
        $this->actingAs($this->supervisor)->post(route('gastos.store', $this->proyecto), $this->gastoCemento());
        $gasto = Gasto::sole();

        $admin = $this->administrador();
        $this->actingAs($admin)->get(route('gastos.pendientes'))->assertOk()->assertSee('Compra de cemento');
        $this->get('/proyectos')->assertSee('Gastos esperando aprobación', false);

        $this->post(route('gastos.aprobar', $gasto))->assertSessionHasNoErrors();

        $gasto->refresh();
        $this->assertSame(Gasto::APROBADO, $gasto->estado_gasto);
        $this->assertSame($admin->id_usuario, $gasto->revisadoPor->id_usuario);
        $this->assertNotNull($gasto->fecha_revision);
        $this->assertSame('20.00', $this->proyecto->materiales()->sole()->stock_actual);

        // No se puede aprobar dos veces (el material entraría dos veces)
        $this->post(route('gastos.aprobar', $gasto))->assertSessionHasErrors('gasto');
        $this->assertSame('20.00', $this->proyecto->materiales()->sole()->stock_actual);
    }

    public function test_el_administrador_rechaza_con_motivo_y_el_supervisor_corrige(): void
    {
        $this->actingAs($this->supervisor)->post(route('gastos.store', $this->proyecto), $this->gastoAdicional());
        $gasto = Gasto::sole();

        $this->actingAs($this->administrador());
        $this->post(route('gastos.rechazar', $gasto))->assertSessionHasErrors('motivo_rechazo');
        $this->post(route('gastos.rechazar', $gasto), ['motivo_rechazo' => 'Falta la boleta del flete'])->assertSessionHasNoErrors();

        $this->assertSame(Gasto::RECHAZADO, $gasto->fresh()->estado_gasto);
        $this->assertSame(0.0, $this->proyecto->totalGastado());

        // El supervisor ve el motivo, corrige y el gasto vuelve a quedar pendiente
        $this->actingAs($this->supervisor)->get(route('proyectos.show', $this->proyecto))->assertSee('Falta la boleta del flete');
        $this->put(route('gastos.update', [$this->proyecto, $gasto]), $this->gastoAdicional(['documento_gasto' => $this->boleta()]))
            ->assertSessionHasNoErrors();

        $gasto->refresh();
        $this->assertSame(Gasto::PENDIENTE, $gasto->estado_gasto);
        $this->assertNull($gasto->motivo_rechazo);
        $this->assertNotNull($gasto->documento_gasto);
    }

    public function test_el_supervisor_no_aprueba_ni_modifica_gastos_aprobados(): void
    {
        $aprobado = Gasto::factory()->for($this->proyecto)->create(['id_usuario_registro' => $this->supervisor->id_usuario]);
        $pendiente = Gasto::factory()->for($this->proyecto)->pendiente()->create(['id_usuario_registro' => $this->supervisor->id_usuario]);

        $this->actingAs($this->supervisor);

        $this->get(route('gastos.pendientes'))->assertForbidden();
        $this->post(route('gastos.aprobar', $pendiente))->assertForbidden();
        $this->put(route('gastos.update', [$this->proyecto, $aprobado]), $this->gastoAdicional())->assertForbidden();
        $this->delete(route('gastos.destroy', [$this->proyecto, $aprobado]))->assertForbidden();

        // Su propio gasto pendiente sí lo puede eliminar
        $this->delete(route('gastos.destroy', [$this->proyecto, $pendiente]))->assertRedirect();
        $this->assertModelMissing($pendiente);
        $this->assertModelExists($aprobado);
    }

    public function test_aprobar_un_gasto_puede_dejar_el_proyecto_en_alerta(): void
    {
        // Presupuesto: 100 sacos × $5.000 = $500.000; el gasto lo lleva al 96 %
        $this->actingAs($this->supervisor)->post(route('gastos.store', $this->proyecto), $this->gastoCemento(['monto_gasto' => 480000]));

        $this->actingAs($this->administrador())
            ->post(route('gastos.aprobar', Gasto::sole()))
            ->assertSessionHas('exito', fn ($m) => str_contains($m, 'pasó a estado "Alerta"'));

        $this->assertSame(Proyecto::ESTADO_ALERTA, $this->proyecto->fresh()->estado_proyecto);
    }
}
