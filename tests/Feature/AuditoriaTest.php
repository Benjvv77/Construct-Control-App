<?php

namespace Tests\Feature;

use App\Models\Auditoria;
use App\Models\Gasto;
use App\Models\Proyecto;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * HU19: registro de quién cambió qué y cuándo.
 */
class AuditoriaTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    public function test_registra_quien_crea_modifica_y_elimina_un_proyecto(): void
    {
        $admin = $this->administrador();
        $this->actingAs($admin);

        $this->post('/proyectos', [
            'nombre_proyecto' => 'Bodega Norte',
            'fecha_inicio_proyecto' => '2026-01-01',
            'estado_proyecto' => 1,
        ]);
        $proyecto = Proyecto::sole();

        $this->put(route('proyectos.update', $proyecto), [
            'nombre_proyecto' => 'Bodega Sur',
            'fecha_inicio_proyecto' => '2026-01-01',
            'estado_proyecto' => 3,
        ]);

        $this->delete(route('proyectos.destroy', $proyecto));

        $registros = Auditoria::where('entidad', 'Proyecto')->orderBy('id_auditoria')->get();
        $this->assertSame(['creado', 'modificado', 'eliminado'], $registros->pluck('accion')->all());
        $this->assertTrue($registros->every(fn ($r) => $r->id_usuario === $admin->id_usuario && $r->fecha !== null));

        $cambio = $registros[1]->cambios;
        $this->assertSame('Bodega Norte', $cambio['antes']['nombre_proyecto']);
        $this->assertSame('Bodega Sur', $cambio['despues']['nombre_proyecto']);
        $this->assertSame(3, $cambio['despues']['estado_proyecto']);
    }

    public function test_registra_la_aprobacion_de_un_gasto(): void
    {
        $proyecto = Proyecto::factory()->create();
        $gasto = Gasto::factory()->for($proyecto)->adicional()->pendiente()->create(['nombre_gasto' => 'Flete']);

        $admin = $this->administrador();
        $this->actingAs($admin)->post(route('gastos.aprobar', $gasto));

        $registro = Auditoria::where('entidad', 'Gasto')->where('accion', 'aprobado')->sole();
        $this->assertSame($admin->id_usuario, $registro->id_usuario);
        $this->assertSame($proyecto->id_proyecto, $registro->id_proyecto);
        $this->assertSame('pendiente', $registro->cambios['antes']['estado_gasto']);
    }

    public function test_no_guarda_contrasenas(): void
    {
        $admin = $this->administrador();
        $usuario = Usuario::factory()->create(['correo_usuario' => 'pedro@test.cl']);

        $this->actingAs($admin)->put(route('usuarios.update', $usuario), [
            'nombre_usuario' => 'Pedro',
            'apellido_paterno' => 'Soto',
            'correo_usuario' => 'pedro@test.cl',
            'rol_usuario' => 'Supervisor',
            'estado_usuario' => 1,
            'password' => 'clave-nueva-123',
            'password_confirmation' => 'clave-nueva-123',
        ])->assertSessionHasNoErrors();

        foreach (Auditoria::where('entidad', 'Usuario')->get() as $registro) {
            $this->assertStringNotContainsString('password', json_encode($registro->cambios));
        }
    }

    public function test_solo_el_administrador_ve_la_auditoria_y_puede_filtrarla(): void
    {
        $proyecto = Proyecto::factory()->create(['nombre_proyecto' => 'Obra Auditada']);

        $this->actingAs($proyecto->usuario)->get(route('auditoria.index'))->assertForbidden();

        $this->actingAs($this->administrador())
            ->get(route('auditoria.index', ['entidad' => 'Proyecto']))
            ->assertOk()
            ->assertSee('Obra Auditada')
            ->assertDontSee('Ítem "Presupuesto general"', false);
    }
}
