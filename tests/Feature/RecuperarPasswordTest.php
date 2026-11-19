<?php

namespace Tests\Feature;

use App\Models\Usuario;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class RecuperarPasswordTest extends TestCase
{
    use RefreshDatabase;

    public function test_envia_el_enlace_y_permite_cambiar_la_contrasena(): void
    {
        Notification::fake();
        $usuario = Usuario::factory()->create(['correo_usuario' => 'ana@test.cl']);

        $this->get('/login')->assertSee(route('password.request'));
        $this->get(route('password.request'))->assertOk();

        $this->post(route('password.email'), ['email' => 'ana@test.cl'])->assertSessionHas('exito');

        $token = null;
        Notification::assertSentTo($usuario, ResetPassword::class, function ($notificacion) use (&$token) {
            $token = $notificacion->token;

            return true;
        });

        $this->get(route('password.reset', ['token' => $token, 'email' => 'ana@test.cl']))->assertOk();

        $this->post(route('password.update'), [
            'token' => $token,
            'email' => 'ana@test.cl',
            'password' => 'nueva-clave-segura',
            'password_confirmation' => 'nueva-clave-segura',
        ])->assertRedirect(route('login'));

        $this->post('/login', ['email' => 'ana@test.cl', 'password' => 'nueva-clave-segura'])->assertRedirect(route('inicio'));
        $this->assertAuthenticatedAs($usuario);
    }

    public function test_no_revela_si_el_correo_existe(): void
    {
        Notification::fake();

        $this->post(route('password.email'), ['email' => 'nadie@test.cl'])
            ->assertSessionHas('exito')
            ->assertSessionHasNoErrors();

        Notification::assertNothingSent();
    }

    public function test_rechaza_un_token_invalido(): void
    {
        Usuario::factory()->create(['correo_usuario' => 'ana@test.cl']);

        $this->post(route('password.update'), [
            'token' => 'inventado',
            'email' => 'ana@test.cl',
            'password' => 'nueva-clave-segura',
            'password_confirmation' => 'nueva-clave-segura',
        ])->assertSessionHasErrors('email');
    }
}
