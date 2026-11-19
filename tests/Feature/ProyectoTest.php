<?php

namespace Tests\Feature;

use App\Models\Gasto;
use App\Models\ItemPresupuesto;
use App\Models\Material;
use App\Models\Proyecto;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * HU03 a HU06: crear, editar, listar y eliminar proyectos.
 */
class ProyectoTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    private function datosProyecto(array $cambios = []): array
    {
        return [
            'nombre_proyecto' => 'Edificio Central',
            'descripcion_proyecto' => 'Obra gruesa',
            'fecha_inicio_proyecto' => '2026-01-01',
            'fecha_termino_proyecto' => '2026-12-31',
            'estado_proyecto' => 1,
            ...$cambios,
        ];
    }

    public function test_el_administrador_crea_un_proyecto_y_elige_al_responsable(): void
    {
        $supervisor = Usuario::factory()->create();

        $this->actingAs($this->administrador())
            ->post('/proyectos', $this->datosProyecto(['id_usuario' => $supervisor->id_usuario]))
            ->assertRedirect(route('proyectos.index'));

        // El presupuesto parte en 0: se arma después con los ítems (HU07)
        $this->assertDatabaseHas('mglpbc_proyecto', [
            'nombre_proyecto' => 'Edificio Central',
            'id_usuario' => $supervisor->id_usuario,
            'presupuesto_proyecto' => 0,
        ]);
    }

    public function test_un_supervisor_no_puede_crear_editar_ni_eliminar_proyectos(): void
    {
        $proyecto = Proyecto::factory()->create();
        $this->actingAs($proyecto->usuario);

        $this->post('/proyectos', $this->datosProyecto())->assertForbidden();
        $this->put(route('proyectos.update', $proyecto), $this->datosProyecto(['nombre_proyecto' => 'Otro']))->assertForbidden();
        $this->delete(route('proyectos.destroy', $proyecto))->assertForbidden();

        $this->assertSame($proyecto->nombre_proyecto, $proyecto->fresh()->nombre_proyecto);
        $this->get('/proyectos')->assertDontSee('+ Nuevo proyecto')->assertDontSee('✏️ Editar');
    }

    public function test_valida_los_datos_del_proyecto(): void
    {
        $this->actingAs($this->administrador())
            ->post('/proyectos', $this->datosProyecto([
                'nombre_proyecto' => '',
                'fecha_termino_proyecto' => '2025-01-01',
                'estado_proyecto' => 9,
            ]))
            ->assertSessionHasErrors(['nombre_proyecto', 'fecha_termino_proyecto', 'estado_proyecto']);

        $this->assertDatabaseCount('mglpbc_proyecto', 0);
    }

    public function test_el_responsable_ve_todas_las_pantallas_de_su_proyecto(): void
    {
        $proyecto = Proyecto::factory()->create();
        Gasto::factory()->for($proyecto)->create(['fecha_gasto' => '2026-03-01']);
        Material::factory()->for($proyecto)->create();

        $this->actingAs($proyecto->usuario);

        $this->get('/proyectos')->assertOk()->assertSee($proyecto->nombre_proyecto);
        $this->get(route('proyectos.show', $proyecto))->assertOk()->assertSee('Presupuesto por ítem')->assertSee('Historial de gastos');
        $this->get(route('proyectos.reporte', $proyecto))->assertOk()->assertSee('Curva S')->assertSee('Desviación por ítem');
        $this->get(route('inventario.index', $proyecto))->assertOk()->assertSee('Cemento')->assertSee('Inventario vs. presupuesto');
        $this->get(route('reportes.adicionales', $proyecto))->assertOk();
        $this->get(route('reportes.desviacion'))->assertOk()->assertSee($proyecto->nombre_proyecto);
    }

    public function test_el_administrador_edita_un_proyecto_sin_tocar_su_presupuesto(): void
    {
        $proyecto = Proyecto::factory()->create(['presupuesto_proyecto' => 750000]);

        $this->actingAs($this->administrador())
            ->put(route('proyectos.update', $proyecto), $this->datosProyecto(['nombre_proyecto' => 'Nuevo nombre', 'estado_proyecto' => 2]))
            ->assertRedirect(route('proyectos.index'));

        $proyecto->refresh();
        $this->assertSame('Nuevo nombre', $proyecto->nombre_proyecto);
        $this->assertSame(2, $proyecto->estado_proyecto);
        $this->assertSame('750000.00', $proyecto->presupuesto_proyecto);
    }

    public function test_borrar_un_proyecto_borra_todo_lo_asociado(): void
    {
        Storage::fake();

        $item = ItemPresupuesto::factory()->materiales()->create();
        $proyecto = $item->proyecto;
        $this->actingAs($this->administrador());

        $this->post(route('gastos.store', $proyecto), [
            'clase_gasto' => 'presupuestado',
            'id_item_presupuesto' => $item->id_item_presupuesto,
            'nombre_gasto' => 'Cemento',
            'monto_gasto' => 50000,
            'fecha_gasto' => '2026-02-01',
            'cantidad_gasto' => 10,
            'unidad_gasto' => 'sacos',
            'documento_gasto' => $this->boleta(),
        ])->assertSessionHasNoErrors();

        $documento = $proyecto->gastos()->sole()->documento_gasto;
        $proyecto->materiales()->sole()->registrarMovimiento('SALIDA', 2, '2026-02-02', null);
        Storage::assertExists($documento);

        $this->delete(route('proyectos.destroy', $proyecto))->assertRedirect(route('proyectos.index'));

        $this->assertModelMissing($proyecto);
        foreach (['mglpbc_gastos', 'mglpbc_material', 'mglpbc_movimiento_inventario', 'mglpbc_item_presupuesto'] as $tabla) {
            $this->assertDatabaseCount($tabla, 0);
        }
        Storage::assertMissing($documento);
    }
}
