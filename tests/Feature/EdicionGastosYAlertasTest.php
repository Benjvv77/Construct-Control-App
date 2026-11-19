<?php

namespace Tests\Feature;

use App\Models\Gasto;
use App\Models\ItemPresupuesto;
use App\Models\Proyecto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Edición de gastos aprobados (solo el administrador) y alertas automáticas de presupuesto.
 */
class EdicionGastosYAlertasTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    private Proyecto $proyecto;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake();
        $this->proyecto = Proyecto::factory()->create(['presupuesto_proyecto' => 100000]);
        $this->actingAs($this->administrador());
    }

    private function adicional(array $cambios = []): array
    {
        return [
            'clase_gasto' => 'adicional',
            'id_tipo_gasto' => 2,
            'nombre_gasto' => 'Maestro',
            'descripcion_gasto' => 'Refuerzo no previsto',
            'monto_gasto' => 20000,
            'fecha_gasto' => '2026-02-01',
            ...$cambios,
        ];
    }

    public function test_el_administrador_corrige_un_gasto_aprobado(): void
    {
        $gasto = Gasto::factory()->for($this->proyecto)->adicional()->create(['id_tipo_gasto' => 2]);

        $this->put(route('gastos.update', [$this->proyecto, $gasto]), [
            'nombre_gasto' => 'Arriendo retroexcavadora',
            'descripcion_gasto' => 'Excavación extra',
            'monto_gasto' => 65000,
            'fecha_gasto' => '2026-02-05',
            'id_tipo_gasto' => 1,   // un gasto aprobado no cambia de tipo: se ignora
        ])->assertSessionHasNoErrors();

        $gasto->refresh();
        $this->assertSame('Arriendo retroexcavadora', $gasto->nombre_gasto);
        $this->assertSame('65000.00', $gasto->monto_gasto);
        $this->assertSame(2, $gasto->id_tipo_gasto);
        $this->assertSame(Gasto::APROBADO, $gasto->estado_gasto);
    }

    public function test_editar_el_monto_de_un_gasto_de_materiales_aprobado_ajusta_el_costo_del_material(): void
    {
        $item = ItemPresupuesto::factory()->materiales()->create(['id_proyecto' => $this->proyecto->id_proyecto]);

        $this->post(route('gastos.store', $this->proyecto), [
            'clase_gasto' => 'presupuestado', 'id_item_presupuesto' => $item->id_item_presupuesto, 'nombre_gasto' => 'Cemento',
            'monto_gasto' => 30000, 'fecha_gasto' => '2026-02-01', 'cantidad_gasto' => 10, 'unidad_gasto' => 'sacos', 'documento_gasto' => $this->boleta(),
        ])->assertSessionHasNoErrors();
        $gasto = Gasto::sole();

        $this->put(route('gastos.update', [$this->proyecto, $gasto]), [
            'monto_gasto' => 35000,
            'fecha_gasto' => '2026-02-03',
            'nombre_gasto' => 'Otro nombre',   // ligado al inventario: no cambia
        ])->assertSessionHasNoErrors();

        $this->assertSame('Cemento', $gasto->refresh()->nombre_gasto);
        $this->assertSame('35000.00', $this->proyecto->materiales()->sole()->costo_estimado_material);
    }

    public function test_reemplazar_el_documento_borra_el_anterior(): void
    {
        $this->post(route('gastos.store', $this->proyecto), $this->adicional(['documento_gasto' => $this->boleta()]));
        $gasto = Gasto::sole();
        $anterior = $gasto->documento_gasto;

        $this->put(route('gastos.update', [$this->proyecto, $gasto]), [
            ...$this->adicional(),
            'documento_gasto' => UploadedFile::fake()->image('b.png'),
        ])->assertSessionHasNoErrors();

        Storage::assertMissing($anterior);
        Storage::assertExists($gasto->refresh()->documento_gasto);
    }

    public function test_al_llegar_al_umbral_el_proyecto_pasa_a_alerta(): void
    {
        $this->post(route('gastos.store', $this->proyecto), $this->adicional(['monto_gasto' => 50000]));
        $this->assertSame(1, $this->proyecto->refresh()->estado_proyecto);

        $this->post(route('gastos.store', $this->proyecto), $this->adicional(['monto_gasto' => 45000]))
            ->assertSessionHas('exito', fn ($mensaje) => str_contains($mensaje, 'pasó a estado "Alerta"'));

        $this->assertSame(2, $this->proyecto->refresh()->estado_proyecto);
        $this->get(route('proyectos.show', $this->proyecto))->assertSee('Desviación presupuestaria');
    }

    public function test_avisa_cuando_el_presupuesto_se_excede(): void
    {
        Gasto::factory()->for($this->proyecto)->create(['monto_gasto' => 120000]);

        $this->get(route('proyectos.show', $this->proyecto))->assertSee('Presupuesto excedido')->assertSee('$20.000');
    }

    public function test_no_cambia_proyectos_pausados_o_finalizados(): void
    {
        $this->proyecto->update(['estado_proyecto' => 4]);

        $this->post(route('gastos.store', $this->proyecto), $this->adicional(['monto_gasto' => 99000]));

        $this->assertSame(4, $this->proyecto->refresh()->estado_proyecto);
    }
}
