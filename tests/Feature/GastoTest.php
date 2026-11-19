<?php

namespace Tests\Feature;

use App\Models\Gasto;
use App\Models\ItemPresupuesto;
use App\Models\Proyecto;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Boletas y totales de los gastos (HU08, HU09, HU11).
 */
class GastoTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    private Proyecto $proyecto;

    private ItemPresupuesto $item;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake();
        $this->item = ItemPresupuesto::factory()->create(['cantidad' => 20, 'precio_unitario' => 50000]);
        $this->proyecto = $this->item->proyecto;
    }

    private function presupuestado(array $cambios = []): array
    {
        return [
            'clase_gasto' => 'presupuestado',
            'id_item_presupuesto' => $this->item->id_item_presupuesto,
            'nombre_gasto' => 'Maestro albañil',
            'monto_gasto' => 50000,
            'fecha_gasto' => '2026-02-01',
            'documento_gasto' => $this->boleta(),
            ...$cambios,
        ];
    }

    public function test_la_boleta_se_guarda_en_privado_y_solo_la_ven_quienes_tienen_acceso(): void
    {
        $this->actingAs($this->proyecto->usuario)
            ->post(route('gastos.store', $this->proyecto), $this->presupuestado())
            ->assertSessionHasNoErrors();

        $gasto = Gasto::sole();
        Storage::assertExists($gasto->documento_gasto);
        $this->get(route('gastos.documento', [$this->proyecto, $gasto]))->assertOk();

        $this->actingAs(Usuario::factory()->create())
            ->get(route('gastos.documento', [$this->proyecto, $gasto]))
            ->assertForbidden();
    }

    public function test_rechaza_archivos_que_no_son_pdf_ni_imagen(): void
    {
        $this->actingAs($this->proyecto->usuario);

        $this->post(route('gastos.store', $this->proyecto), $this->presupuestado([
            'documento_gasto' => UploadedFile::fake()->createWithContent('script.php', '<?php echo 1;'),
        ]))->assertSessionHasErrors('documento_gasto');

        $this->post(route('gastos.store', $this->proyecto), $this->presupuestado([
            'documento_gasto' => UploadedFile::fake()->create('grande.pdf', 6000, 'application/pdf'),
        ]))->assertSessionHasErrors('documento_gasto');

        $this->assertDatabaseCount('mglpbc_gastos', 0);
    }

    public function test_los_totales_solo_cuentan_gastos_aprobados(): void
    {
        Gasto::factory()->for($this->proyecto)->create(['monto_gasto' => 100000, 'id_item_presupuesto' => $this->item->id_item_presupuesto]);
        Gasto::factory()->for($this->proyecto)->adicional()->create(['monto_gasto' => 30000]);
        Gasto::factory()->for($this->proyecto)->pendiente()->create(['monto_gasto' => 50000]);
        Gasto::factory()->for($this->proyecto)->create(['monto_gasto' => 70000, 'estado_gasto' => Gasto::RECHAZADO]);

        $this->assertSame(130000.0, $this->proyecto->totalGastado());
        $this->assertSame(30000.0, $this->proyecto->totalExtra());

        $this->actingAs($this->proyecto->usuario)
            ->get(route('proyectos.show', $this->proyecto))
            ->assertSee('$130.000')
            ->assertSee('$870.000')
            ->assertSee('$50.000 por aprobar', false);
    }

    public function test_no_elimina_un_gasto_a_traves_de_otro_proyecto(): void
    {
        $otroProyecto = Proyecto::factory()->create();
        $gasto = Gasto::factory()->for($this->proyecto)->pendiente()->create();

        $this->actingAs($this->administrador())
            ->delete(route('gastos.destroy', [$otroProyecto, $gasto]))
            ->assertNotFound();

        $this->assertModelExists($gasto);
    }

    public function test_el_administrador_elimina_un_gasto_y_su_boleta(): void
    {
        $this->actingAs($this->administrador())
            ->post(route('gastos.store', $this->proyecto), $this->presupuestado())
            ->assertSessionHasNoErrors();

        $gasto = Gasto::sole();

        $this->delete(route('gastos.destroy', [$this->proyecto, $gasto]))
            ->assertRedirect(route('proyectos.show', $this->proyecto));

        $this->assertModelMissing($gasto);
        Storage::assertMissing($gasto->documento_gasto);
    }
}
