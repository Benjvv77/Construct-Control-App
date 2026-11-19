<?php

namespace Tests\Feature;

use App\Models\Gasto;
use App\Models\Proyecto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * HU20: mensajes de error claros y en español.
 */
class ErroresTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    public function test_la_pagina_no_encontrada_explica_que_paso(): void
    {
        $this->actingAs($this->administrador())
            ->get('/proyectos/999999')
            ->assertNotFound()
            ->assertSee('No encontramos lo que buscas')
            ->assertDontSee('No query results');
    }

    public function test_la_falta_de_permisos_se_explica_en_espanol(): void
    {
        $ajeno = Proyecto::factory()->create();

        $this->actingAs(Proyecto::factory()->create()->usuario)
            ->get(route('proyectos.show', $ajeno))
            ->assertForbidden()
            ->assertSee('No tienes permiso')
            ->assertDontSee('This action is unauthorized');
    }

    public function test_muestra_el_motivo_concreto_cuando_la_app_lo_da(): void
    {
        $proyecto = Proyecto::factory()->create();
        $gasto = Gasto::factory()->for($proyecto)->create(['id_usuario_registro' => $proyecto->id_usuario]);

        $this->actingAs($proyecto->usuario)
            ->delete(route('gastos.destroy', [$proyecto, $gasto]))
            ->assertForbidden()
            ->assertSee('Solo puedes eliminar tus gastos mientras no estén aprobados.');
    }

    public function test_un_metodo_no_permitido_tambien_se_explica(): void
    {
        $this->actingAs($this->administrador())
            ->get('/logout')
            ->assertStatus(405)
            ->assertSee('Acción no permitida');
    }

    public function test_los_errores_de_validacion_vuelven_al_formulario_en_espanol(): void
    {
        $this->actingAs($this->administrador())
            ->from('/proyectos')
            ->post('/proyectos', [])
            ->assertRedirect('/proyectos')
            ->assertSessionHasErrors(['nombre_proyecto' => 'El campo nombre del proyecto es obligatorio.']);
    }
}
