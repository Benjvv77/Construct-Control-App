<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function create(): View
    {
        return view('login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credenciales = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $autenticado = Auth::attempt([
            'correo_usuario' => $credenciales['email'],
            'password' => $credenciales['password'],
            'estado_usuario' => 1,
        ], $request->boolean('recordar'));

        if (! $autenticado) {
            return back()
                ->withErrors(['email' => 'Credenciales incorrectas. Intenta nuevamente.'])
                ->onlyInput('email');
        }

        $request->session()->regenerate();

        return redirect()->intended(route('inicio'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('inicio');
    }
}
