<?php

use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');
        $middleware->redirectUsersTo(fn () => route('inicio'));
        $middleware->appendToGroup('web', \App\Http\Middleware\CerrarSesionInactiva::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // HU20: páginas de error en español, con una explicación y qué hacer.
        // Las validaciones y la falta de sesión siguen su camino normal (volver al formulario / al login).
        $exceptions->render(function (Throwable $e, Request $request) {
            if ($e instanceof ValidationException || $e instanceof AuthenticationException
                || $e instanceof HttpResponseException || $request->expectsJson()) {
                return null;
            }

            if ($e instanceof HttpExceptionInterface) {
                $codigo = $e->getStatusCode();
            } elseif (config('app.debug')) {
                return null;   // en desarrollo se muestra el detalle del error
            } else {
                $codigo = 500;
            }

            // Solo se muestran los mensajes escritos en la app (en español, todos llevan tildes);
            // los internos de Laravel están en inglés y no le sirven al usuario
            $mensaje = $e instanceof HttpExceptionInterface && preg_match('/[áéíóúñ¿]/u', $e->getMessage())
                ? $e->getMessage()
                : null;

            return response()->view('error', ['codigo' => $codigo, 'detalle' => $mensaje], $codigo,
                $e instanceof HttpExceptionInterface ? $e->getHeaders() : []);
        });
    })->create();
