<?php

namespace Tests\Feature;

use App\Models\Gasto;
use App\Models\Proyecto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * HU16 (desviación presupuestaria), HU17 (gastos adicionales) y HU18 (exportar a PDF).
 */
class ReportesTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    public function test_el_reporte_de_desviacion_ordena_las_obras_por_avance(): void
    {
        $tranquila = Proyecto::factory()->create(['nombre_proyecto' => 'Obra Tranquila', 'presupuesto_proyecto' => 1000000]);
        $excedida = Proyecto::factory()->create(['nombre_proyecto' => 'Obra Excedida', 'presupuesto_proyecto' => 100000]);
        Gasto::factory()->for($tranquila)->create(['monto_gasto' => 100000]);
        Gasto::factory()->for($excedida)->create(['monto_gasto' => 150000]);

        $this->actingAs($this->administrador())
            ->get(route('reportes.desviacion'))
            ->assertOk()
            ->assertSeeInOrder(['Obra Excedida', 'Obra Tranquila'])
            ->assertSee('Sobrecosto')
            ->assertSee('+$50.000', false);
    }

    public function test_el_supervisor_solo_ve_sus_obras_en_el_reporte(): void
    {
        $propia = Proyecto::factory()->create(['nombre_proyecto' => 'Obra Propia']);
        Proyecto::factory()->create(['nombre_proyecto' => 'Obra Ajena']);

        $this->actingAs($propia->usuario)
            ->get(route('reportes.desviacion'))
            ->assertSee('Obra Propia')
            ->assertDontSee('Obra Ajena');
    }

    public function test_el_reporte_de_adicionales_muestra_solo_los_adicionales_aprobados(): void
    {
        $proyecto = Proyecto::factory()->create(['presupuesto_proyecto' => 1000000]);
        Gasto::factory()->for($proyecto)->adicional()->create(['nombre_gasto' => 'Flete urgente', 'monto_gasto' => 80000, 'descripcion_gasto' => 'Proveedor atrasado']);
        Gasto::factory()->for($proyecto)->adicional()->pendiente()->create(['nombre_gasto' => 'Adicional pendiente']);
        Gasto::factory()->for($proyecto)->create(['nombre_gasto' => 'Gasto presupuestado']);

        $this->actingAs($proyecto->usuario)
            ->get(route('reportes.adicionales', $proyecto))
            ->assertOk()
            ->assertSee('Flete urgente')
            ->assertSee('Proveedor atrasado')
            ->assertSee('$80.000')
            ->assertSee('8%')
            ->assertDontSee('Adicional pendiente')
            ->assertDontSee('Gasto presupuestado');
    }

    public function test_los_tres_reportes_se_exportan_a_pdf(): void
    {
        $proyecto = Proyecto::factory()->create();
        Gasto::factory()->for($proyecto)->adicional()->create();
        $this->actingAs($this->administrador());

        foreach ([
            route('proyectos.reporte.pdf', $proyecto),
            route('reportes.desviacion.pdf'),
            route('reportes.adicionales.pdf', $proyecto),
        ] as $url) {
            $respuesta = $this->get($url)->assertOk();
            $this->assertSame('application/pdf', $respuesta->headers->get('Content-Type'));
            $this->assertStringStartsWith('%PDF', $respuesta->getContent());
        }
    }

    public function test_no_se_ven_reportes_de_obras_ajenas(): void
    {
        $ajena = Proyecto::factory()->create();

        $this->actingAs(Proyecto::factory()->create()->usuario);

        $this->get(route('reportes.adicionales', $ajena))->assertForbidden();
        $this->get(route('reportes.adicionales.pdf', $ajena))->assertForbidden();
        $this->get(route('proyectos.reporte.pdf', $ajena))->assertForbidden();
    }
}
