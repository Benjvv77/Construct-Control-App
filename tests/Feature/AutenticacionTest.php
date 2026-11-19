<?php

namespace Tests\Feature;

use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AutenticacionTest extends TestCase
{
    use RefreshDatabase;

    public function test_muestra_el_login_y_la_pagina_de_inicio(): void
    {
        $this->get('/')->assertOk()->assertSee('ConstructControl');
        $this->get('/login')->assertOk()->assertSee('Iniciar sesión');
    }

    public function test_inicia_sesion_con_credenciales_validas(): void
    {
        $usuario = Usuario::factory()->create(['correo_usuario' => 'admin@test.cl']);

        $this->post('/login', ['email' => 'admin@test.cl', 'password' => 'password'])
            ->assertRedirect(route('inicio'));

        $this->assertAuthenticatedAs($usuario);
    }

    public function test_rechaza_clave_incorrecta(): void
    {
        Usuario::factory()->create(['correo_usuario' => 'admin@test.cl']);

        $this->from('/login')
            ->post('/login', ['email' => 'admin@test.cl', 'password' => 'otra'])
            ->assertRedirect('/login')
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_rechaza_usuario_inactivo(): void
    {
        Usuario::factory()->inactivo()->create(['correo_usuario' => 'inactivo@test.cl']);

        $this->post('/login', ['email' => 'inactivo@test.cl', 'password' => 'password'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_cierra_sesion_solo_por_post(): void
    {
        $this->actingAs(Usuario::factory()->create());

        $this->get('/logout')->assertMethodNotAllowed();

        $this->post('/logout')->assertRedirect(route('inicio'));
        $this->assertGuest();
    }

    public function test_las_paginas_internas_exigen_sesion(): void
    {
        $this->get('/proyectos')->assertRedirect('/login');
    }

    public function test_la_clave_se_guarda_con_hash(): void
    {
        $usuario = Usuario::factory()->create(['password_usuario' => 'secreta']);

        $this->assertNotSame('secreta', $usuario->getRawOriginal('password_usuario'));
        $this->assertTrue(password_verify('secreta', $usuario->getRawOriginal('password_usuario')));
    }
}
