<?php

namespace Tests\Feature;

use App\Models\Gasto;
use App\Models\Material;
use App\Models\Proyecto;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccesoProyectosTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    public function test_un_usuario_solo_ve_sus_proyectos(): void
    {
        $propio = Proyecto::factory()->create(['nombre_proyecto' => 'Proyecto propio']);
        Proyecto::factory()->create(['nombre_proyecto' => 'Proyecto ajeno']);

        $this->actingAs($propio->usuario)
            ->get('/proyectos')
            ->assertSee('Proyecto propio')
            ->assertDontSee('Proyecto ajeno');
    }

    public function test_el_administrador_ve_todos_los_proyectos(): void
    {
        Proyecto::factory()->create(['nombre_proyecto' => 'Proyecto A']);
        Proyecto::factory()->create(['nombre_proyecto' => 'Proyecto B']);

        $this->actingAs(Usuario::factory()->administrador()->create())
            ->get('/proyectos')
            ->assertSee('Proyecto A')
            ->assertSee('Proyecto B');
    }

    public function test_un_usuario_no_puede_tocar_proyectos_ajenos(): void
    {
        $ajeno = Proyecto::factory()->create();
        $gasto = Gasto::factory()->for($ajeno)->create();
        $material = Material::factory()->for($ajeno)->create();

        $this->actingAs(Usuario::factory()->create());

        $this->get(route('proyectos.show', $ajeno))->assertForbidden();
        $this->get(route('proyectos.reporte', $ajeno))->assertForbidden();
        $this->get(route('inventario.index', $ajeno))->assertForbidden();
        $this->put(route('proyectos.update', $ajeno), ['nombre_proyecto' => 'Hackeado'])->assertForbidden();
        $this->post(route('gastos.store', $ajeno), [])->assertForbidden();
        $this->delete(route('gastos.destroy', [$ajeno, $gasto]))->assertForbidden();
        $this->delete(route('inventario.materiales.destroy', [$ajeno, $material]))->assertForbidden();
        $this->delete(route('proyectos.destroy', $ajeno))->assertForbidden();

        $this->assertModelExists($ajeno);
        $this->assertModelExists($gasto);
        $this->assertModelExists($material);
    }
}
