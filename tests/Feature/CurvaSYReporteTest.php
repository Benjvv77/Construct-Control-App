<?php

namespace Tests\Feature;

use App\Models\Gasto;
use App\Models\Proyecto;
use App\Services\CurvaS;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CurvaSYReporteTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    private Proyecto $proyecto;

    protected function setUp(): void
    {
        parent::setUp();

        $this->proyecto = Proyecto::factory()->create([
            'presupuesto_proyecto' => 1200000,
            'fecha_inicio_proyecto' => '2026-01-01',
            'fecha_termino_proyecto' => '2026-12-31',
        ]);
        $this->actingAs($this->proyecto->usuario);
    }

    public function test_sin_plan_registrado_usa_una_curva_en_s_entre_inicio_y_termino(): void
    {
        $puntos = app(CurvaS::class)->calcular($this->proyecto)['puntos'];

        $this->assertSame('2026-01-01', $puntos[0]['fecha']);
        $this->assertSame('2026-12-31', end($puntos)['fecha']);
        $this->assertEquals(0, $puntos[0]['planificado']);
        $this->assertEquals(1200000, end($puntos)['planificado']);

        // Forma en S: a mitad de plazo va cerca de la mitad, y avanza más lento al principio
        $mitad = $puntos[intdiv(count($puntos), 2)]['planificado'];
        $this->assertEqualsWithDelta(600000, $mitad, 150000);
        $this->assertLessThan(1200000 / (count($puntos) - 1), $puntos[1]['planificado']);
    }

    public function test_el_gasto_real_es_acumulado(): void
    {
        Gasto::factory()->for($this->proyecto)->create(['fecha_gasto' => '2026-01-10', 'monto_gasto' => 100000]);
        Gasto::factory()->for($this->proyecto)->create(['fecha_gasto' => '2026-03-10', 'monto_gasto' => 50000]);

        $puntos = collect(app(CurvaS::class)->calcular($this->proyecto)['puntos']);
        $antesDelSegundoGasto = $puntos->last(fn ($p) => $p['fecha'] < '2026-03-10');
        $despuesDelSegundoGasto = $puntos->first(fn ($p) => $p['fecha'] >= '2026-03-10');

        $this->assertEquals(100000, $antesDelSegundoGasto['real']);
        $this->assertEquals(150000, $despuesDelSegundoGasto['real']);
    }

    public function test_registra_puntos_del_plan_con_el_gasto_real_a_esa_fecha(): void
    {
        $this->actingAs($this->administrador());

        Gasto::factory()->for($this->proyecto)->create(['fecha_gasto' => '2026-02-01', 'monto_gasto' => 70000]);

        $this->post(route('curva-s.store', $this->proyecto), [
            'fecha_evaluacion' => '2026-03-01',
            'monto_planificado_acumulado' => 100000,
        ])->assertSessionHasNoErrors();

        $evaluacion = $this->proyecto->evaluacionesCurvaS()->sole();
        $this->assertSame('70000.00', $evaluacion->monto_real_acumulado);
        $this->assertEquals(-30000, $evaluacion->desviacion());

        // Un gasto registrado después, con fecha anterior al punto, cuenta en el real
        Gasto::factory()->for($this->proyecto)->create(['fecha_gasto' => '2026-02-15', 'monto_gasto' => 10000]);
        $this->assertEquals(-20000, $evaluacion->fresh()->desviacion());

        $resultado = app(CurvaS::class)->calcular($this->proyecto);
        $this->assertTrue($resultado['plan_registrado']);
        $this->assertEquals(100000, end($resultado['puntos'])['planificado']);

        $this->get(route('proyectos.reporte', $this->proyecto))->assertOk()->assertSee('<svg', false)->assertSee('$100.000');
    }

    public function test_un_punto_futuro_queda_pendiente(): void
    {
        $this->actingAs($this->administrador());

        $this->post(route('curva-s.store', $this->proyecto), [
            'fecha_evaluacion' => now()->addYear()->toDateString(),
            'monto_planificado_acumulado' => 500000,
        ])->assertSessionHasNoErrors();

        $this->assertNull($this->proyecto->evaluacionesCurvaS()->sole()->desviacion());
        $this->get(route('proyectos.reporte', $this->proyecto))->assertSee('Pendiente');
    }

    public function test_no_acepta_puntos_antes_del_inicio(): void
    {
        $this->actingAs($this->administrador());

        $this->post(route('curva-s.store', $this->proyecto), [
            'fecha_evaluacion' => '2025-12-01',
            'monto_planificado_acumulado' => 1,
        ])->assertSessionHasErrors('fecha_evaluacion');
    }

    public function test_el_supervisor_ve_la_curva_s_pero_no_cambia_el_plan(): void
    {
        $this->get(route('proyectos.reporte', $this->proyecto))->assertOk()->assertSee('<svg', false);

        $this->post(route('curva-s.store', $this->proyecto), [
            'fecha_evaluacion' => '2026-03-01',
            'monto_planificado_acumulado' => 1,
        ])->assertForbidden();
    }

    public function test_exporta_el_reporte_en_pdf(): void
    {
        Gasto::factory()->for($this->proyecto)->create(['fecha_gasto' => '2026-02-01']);

        $respuesta = $this->get(route('proyectos.reporte.pdf', $this->proyecto))->assertOk();

        $this->assertSame('application/pdf', $respuesta->headers->get('Content-Type'));
        $this->assertStringStartsWith('%PDF', $respuesta->getContent());
        $this->assertStringContainsString('attachment', $respuesta->headers->get('Content-Disposition'));
    }
}
