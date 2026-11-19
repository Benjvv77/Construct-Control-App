<?php

namespace Tests\Feature;

use App\Models\Proyecto;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UsuariosTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    private function datosUsuario(array $cambios = []): array
    {
        return [
            'nombre_usuario' => 'Pedro',
            'apellido_paterno' => 'Soto',
            'apellido_materno' => '',
            'correo_usuario' => 'pedro@test.cl',
            'rol_usuario' => 'Supervisor',
            'estado_usuario' => 1,
            'password' => 'clave-segura-1',
            'password_confirmation' => 'clave-segura-1',
            ...$cambios,
        ];
    }

    public function test_solo_el_administrador_gestiona_usuarios(): void
    {
        $this->actingAs(Usuario::factory()->create());

        $this->get('/usuarios')->assertForbidden();
        $this->post('/usuarios', $this->datosUsuario())->assertForbidden();
    }

    public function test_el_administrador_crea_un_usuario_que_puede_iniciar_sesion(): void
    {
        $this->actingAs(Usuario::factory()->administrador()->create());

        $this->get('/usuarios')->assertOk();
        $this->post('/usuarios', $this->datosUsuario())->assertSessionHasNoErrors();

        $this->post('/logout');
        $this->post('/login', ['email' => 'pedro@test.cl', 'password' => 'clave-segura-1'])->assertRedirect(route('inicio'));
    }

    public function test_no_repite_correos(): void
    {
        $this->actingAs(Usuario::factory()->administrador()->create(['correo_usuario' => 'admin@test.cl']));

        $this->post('/usuarios', $this->datosUsuario(['correo_usuario' => 'admin@test.cl']))->assertSessionHasErrors('correo_usuario');
    }

    public function test_editar_sin_contrasena_conserva_la_actual(): void
    {
        $this->actingAs(Usuario::factory()->administrador()->create());
        $usuario = Usuario::factory()->create(['correo_usuario' => 'pedro@test.cl']);

        $this->put(route('usuarios.update', $usuario), $this->datosUsuario([
            'rol_usuario' => 'Administrador', 'password' => '', 'password_confirmation' => '',
        ]))->assertSessionHasNoErrors();

        $usuario->refresh();
        $this->assertTrue($usuario->esAdministrador());
        $this->assertTrue(password_verify('password', $usuario->getRawOriginal('password_usuario')));
    }

    public function test_el_administrador_no_puede_quitarse_el_acceso(): void
    {
        $admin = Usuario::factory()->administrador()->create(['correo_usuario' => 'admin@test.cl']);
        $this->actingAs($admin);

        $this->put(route('usuarios.update', $admin), $this->datosUsuario([
            'correo_usuario' => 'admin@test.cl', 'rol_usuario' => 'Supervisor', 'password' => '', 'password_confirmation' => '',
        ]))->assertSessionHasErrors('rol_usuario');

        $this->assertTrue($admin->refresh()->esAdministrador());
    }

    public function test_un_usuario_desactivado_pierde_la_sesion(): void
    {
        $usuario = Usuario::factory()->create();
        $this->actingAs($usuario)->get('/proyectos')->assertOk();

        $usuario->update(['estado_usuario' => 0]);

        $this->get('/proyectos')->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_el_administrador_asigna_el_responsable_de_un_proyecto(): void
    {
        $admin = Usuario::factory()->administrador()->create();
        $supervisor = Usuario::factory()->create();
        $this->actingAs($admin);

        $this->post('/proyectos', [
            'id_usuario' => $supervisor->id_usuario,
            'nombre_proyecto' => 'Obra asignada',
            'fecha_inicio_proyecto' => '2026-01-01',
            'estado_proyecto' => 1,
        ])->assertSessionHasNoErrors();

        $this->assertSame($supervisor->id_usuario, Proyecto::sole()->id_usuario);

        // El supervisor ahora la ve en su lista
        $this->actingAs($supervisor)->get('/proyectos')->assertSee('Obra asignada');
    }

    public function test_un_supervisor_no_puede_crear_proyectos(): void
    {
        $this->actingAs(Usuario::factory()->create())->post('/proyectos', [
            'nombre_proyecto' => 'Obra',
            'fecha_inicio_proyecto' => '2026-01-01',
            'estado_proyecto' => 1,
        ])->assertForbidden();

        $this->assertDatabaseCount('mglpbc_proyecto', 0);
    }
}
