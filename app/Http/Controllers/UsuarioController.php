<?php

namespace App\Http\Controllers;

use App\Models\Usuario;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class UsuarioController extends Controller
{
    public function index(): View
    {
        return view('usuarios', [
            'usuarios' => Usuario::withCount('proyectos')
                ->orderByDesc('estado_usuario')
                ->orderBy('nombre_usuario')
                ->paginate(15),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $datos = $request->validate($this->reglas() + [
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        Usuario::create($this->mapear($datos));

        return redirect()->route('usuarios.index')->with('exito', 'Usuario creado correctamente.');
    }

    public function update(Request $request, Usuario $usuario): RedirectResponse
    {
        $datos = $request->validate($this->reglas($usuario) + [
            'password' => ['nullable', 'confirmed', Password::min(8)],
        ]);

        // Evita que el administrador se quite el acceso a sí mismo
        if ($usuario->is($request->user()) && ($datos['rol_usuario'] !== Usuario::ROL_ADMINISTRADOR || (int) $datos['estado_usuario'] !== 1)) {
            return back()->withErrors(['rol_usuario' => 'No puedes quitarte el rol de Administrador ni desactivar tu propia cuenta.']);
        }

        $usuario->update($this->mapear($datos));

        return redirect()->route('usuarios.index')->with('exito', 'Usuario actualizado correctamente.');
    }

    private function reglas(?Usuario $usuario = null): array
    {
        return [
            'nombre_usuario' => ['required', 'string', 'max:100'],
            'apellido_paterno' => ['required', 'string', 'max:100'],
            'apellido_materno' => ['nullable', 'string', 'max:100'],
            'correo_usuario' => [
                'required', 'email', 'max:255',
                Rule::unique('mglpbc_usuario', 'correo_usuario')->ignore($usuario?->id_usuario, 'id_usuario'),
            ],
            'rol_usuario' => ['required', Rule::in(Usuario::ROLES)],
            'estado_usuario' => ['required', Rule::in([0, 1])],
        ];
    }

    /**
     * La contraseña solo se cambia si se escribió una nueva (el modelo la guarda con hash).
     */
    private function mapear(array $datos): array
    {
        $password = $datos['password'] ?? null;
        unset($datos['password']);

        return $password ? [...$datos, 'password_usuario' => $password] : $datos;
    }
}
