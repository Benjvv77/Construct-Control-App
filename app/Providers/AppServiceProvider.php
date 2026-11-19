<?php

namespace App\Providers;

use App\Models\Gasto;
use App\Models\Usuario;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Gestión de usuarios, aprobación de gastos, creación de proyectos y auditoría
        Gate::define('administrar', fn (Usuario $usuario) => $usuario->esAdministrador());

        // El contador de la barra superior avisa al Administrador de los gastos por aprobar (HU10)
        View::composer('topbar', function ($view) {
            $usuario = auth()->user();

            $view->with('gastosPendientes', $usuario?->esAdministrador() ? Gasto::pendientes()->count() : 0);
        });
    }
}
