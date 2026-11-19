<?php

namespace Tests\Feature;

use App\Models\Gasto;
use App\Models\Proyecto;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BusquedaYFiltrosTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    private Usuario $usuario;

    protected function setUp(): void
    {
        parent::setUp();

        $this->usuario = Usuario::factory()->create();
        $this->actingAs($this->usuario);
    }

    public function test_busca_proyectos_por_nombre_o_descripcion_sin_importar_mayusculas(): void
    {
        Proyecto::factory()->for($this->usuario)->create(['nombre_proyecto' => 'Edificio Las Rosas', 'descripcion_proyecto' => null]);
        Proyecto::factory()->for($this->usuario)->create(['nombre_proyecto' => 'Casa Norte', 'descripcion_proyecto' => 'Ampliación de rosas']);
        Proyecto::factory()->for($this->usuario)->create(['nombre_proyecto' => 'Bodega Sur', 'descripcion_proyecto' => null]);

        $this->get('/proyectos?buscar=ROSAS')
            ->assertSee('Edificio Las Rosas')
            ->assertSee('Casa Norte')
            ->assertDontSee('Bodega Sur');

        // Los comodines de SQL se buscan como texto literal
        $this->get('/proyectos?buscar=%25')->assertSee('No hay proyectos que coincidan');
    }

    public function test_filtra_por_estado(): void
    {
        Proyecto::factory()->for($this->usuario)->create(['nombre_proyecto' => 'Obra activa', 'estado_proyecto' => 1]);
        Proyecto::factory()->for($this->usuario)->create(['nombre_proyecto' => 'Obra pausada', 'estado_proyecto' => 3]);

        $this->get('/proyectos?estado=pausados')->assertSee('Obra pausada')->assertDontSee('Obra activa');
        $this->get('/proyectos?estado=activos')->assertSee('Obra activa')->assertDontSee('Obra pausada');
        $this->get('/proyectos?estado=inventado')->assertSessionHasErrors('estado');
    }

    public function test_el_filtro_de_alertas_incluye_proyectos_que_superan_el_umbral(): void
    {
        $sobreUmbral = Proyecto::factory()->for($this->usuario)->create(['nombre_proyecto' => 'Gasto alto', 'presupuesto_proyecto' => 1000]);
        Gasto::factory()->for($sobreUmbral)->create(['monto_gasto' => 950]);
        Proyecto::factory()->for($this->usuario)->create(['nombre_proyecto' => 'Marcado alerta', 'estado_proyecto' => 2]);
        Proyecto::factory()->for($this->usuario)->create(['nombre_proyecto' => 'Tranquilo']);

        $this->get('/proyectos?estado=alertas')
            ->assertSee('Gasto alto')
            ->assertSee('Marcado alerta')
            ->assertDontSee('Tranquilo')
            ->assertSee('Sobre 90% del presupuesto');
    }

    public function test_pagina_la_lista_de_proyectos(): void
    {
        Proyecto::factory()->for($this->usuario)->count(12)->create();

        $this->get('/proyectos')->assertSee('Mostrando 1–10 de 12 proyectos');
        $this->get('/proyectos?page=2')->assertSee('Mostrando 11–12 de 12 proyectos');
    }

    public function test_filtra_gastos_normales_y_extra(): void
    {
        $proyecto = Proyecto::factory()->for($this->usuario)->create();
        Gasto::factory()->for($proyecto)->create(['nombre_gasto' => 'Gasto normal', 'id_tipo_costo' => 1]);
        Gasto::factory()->for($proyecto)->create(['nombre_gasto' => 'Gasto extra', 'id_tipo_costo' => 2]);

        $this->get(route('proyectos.show', [$proyecto, 'costo' => 'extra']))->assertSee('Gasto extra')->assertDontSee('Gasto normal');
        $this->get(route('proyectos.show', [$proyecto, 'costo' => 'normal']))->assertSee('Gasto normal')->assertDontSee('Gasto extra');
    }
}
