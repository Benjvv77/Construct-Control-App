<?php

namespace Database\Factories;

use App\Models\Usuario;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends Factory<Usuario>
 */
class UsuarioFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'nombre_usuario' => fake()->firstName(),
            'apellido_paterno' => fake()->lastName(),
            'apellido_materno' => fake()->lastName(),
            'correo_usuario' => fake()->unique()->safeEmail(),
            'password_usuario' => static::$password ??= Hash::make('password'),
            'rol_usuario' => 'Supervisor',
            'estado_usuario' => 1,
        ];
    }

    public function administrador(): static
    {
        return $this->state(fn () => ['rol_usuario' => Usuario::ROL_ADMINISTRADOR]);
    }

    public function inactivo(): static
    {
        return $this->state(fn () => ['estado_usuario' => 0]);
    }
}
