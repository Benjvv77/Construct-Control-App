<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Usuario;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as ReglaPassword;
use Illuminate\View\View;

class RecuperarPasswordController extends Controller
{
    public function create(): View
    {
        return view('olvide_password');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate(['email' => ['required', 'email']]);

        $estado = Password::sendResetLink(['correo_usuario' => $request->input('email')]);

        if ($estado === Password::RESET_THROTTLED) {
            return back()->withInput()->withErrors(['email' => 'Ya pediste un enlace hace poco. Espera un momento antes de intentarlo de nuevo.']);
        }

        // Mismo mensaje exista o no el correo, para no revelar qué cuentas están registradas
        return back()->with('exito', 'Si el correo está registrado, te enviamos un enlace para restablecer la contraseña.');
    }

    public function edit(Request $request, string $token): View
    {
        return view('restablecer_password', [
            'token' => $token,
            'email' => $request->query('email'),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', ReglaPassword::min(8)],
        ]);

        $estado = Password::reset(
            [
                'correo_usuario' => $request->input('email'),
                'password' => $request->input('password'),
                'password_confirmation' => $request->input('password_confirmation'),
                'token' => $request->input('token'),
            ],
            function (Usuario $usuario, string $password) {
                $usuario->forceFill(['password_usuario' => $password])
                    ->setRememberToken(Str::random(60));

                $usuario->save();

                event(new PasswordReset($usuario));
            }
        );

        if ($estado !== Password::PASSWORD_RESET) {
            return back()->withInput($request->only('email'))
                ->withErrors(['email' => 'El enlace no es válido o ya expiró. Pide uno nuevo.']);
        }

        return redirect()->route('login')->with('exito', 'Tu contraseña se actualizó. Ya puedes iniciar sesión.');
    }
}
