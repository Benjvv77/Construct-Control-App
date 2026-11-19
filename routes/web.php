<?php

use App\Http\Controllers\AprobacionGastoController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\AuditoriaController;
use App\Http\Controllers\Auth\RecuperarPasswordController;
use App\Http\Controllers\CurvaSController;
use App\Http\Controllers\GastoController;
use App\Http\Controllers\InventarioController;
use App\Http\Controllers\PresupuestoController;
use App\Http\Controllers\ProyectoController;
use App\Http\Controllers\ReporteController;
use App\Http\Controllers\UsuarioController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'inicio')->name('inicio');

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->middleware('throttle:5,1')->name('login.store');

    Route::controller(RecuperarPasswordController::class)->group(function () {
        Route::get('/olvide-contrasena', 'create')->name('password.request');
        Route::post('/olvide-contrasena', 'store')->middleware('throttle:5,1')->name('password.email');
        Route::get('/restablecer-contrasena/{token}', 'edit')->name('password.reset');
        Route::post('/restablecer-contrasena', 'update')->name('password.update');
    });
});

Route::post('/logout', [LoginController::class, 'destroy'])->middleware('auth')->name('logout');

Route::middleware('auth')->group(function () {
    // Permisos: "view" y "registrar" = responsable o Administrador; "update", "delete" y "administrar" = solo Administrador
    Route::controller(ProyectoController::class)->group(function () {
        Route::get('/proyectos', 'index')->name('proyectos.index');
        Route::post('/proyectos', 'store')->middleware('can:administrar')->name('proyectos.store');
        Route::get('/proyectos/{proyecto}', 'show')->can('view', 'proyecto')->name('proyectos.show');
        Route::put('/proyectos/{proyecto}', 'update')->can('update', 'proyecto')->name('proyectos.update');
        Route::delete('/proyectos/{proyecto}', 'destroy')->can('delete', 'proyecto')->name('proyectos.destroy');
        Route::get('/proyectos/{proyecto}/reporte', 'reporte')->can('view', 'proyecto')->name('proyectos.reporte');
        Route::get('/proyectos/{proyecto}/reporte/pdf', 'reportePdf')->can('view', 'proyecto')->name('proyectos.reporte.pdf');
    });

    Route::controller(PresupuestoController::class)->group(function () {
        Route::post('/proyectos/{proyecto}/presupuesto', 'store')->can('update', 'proyecto')->name('presupuesto.store');
        Route::put('/proyectos/{proyecto}/presupuesto/{item}', 'update')->can('update', 'proyecto')->name('presupuesto.update');
        Route::delete('/proyectos/{proyecto}/presupuesto/{item}', 'destroy')->can('update', 'proyecto')->name('presupuesto.destroy');
    });

    Route::controller(CurvaSController::class)->group(function () {
        Route::post('/proyectos/{proyecto}/curva-s', 'store')->can('update', 'proyecto')->name('curva-s.store');
        Route::delete('/proyectos/{proyecto}/curva-s/{evaluacion}', 'destroy')->can('update', 'proyecto')->name('curva-s.destroy');
    });

    Route::controller(GastoController::class)->group(function () {
        Route::post('/proyectos/{proyecto}/gastos', 'store')->can('registrar', 'proyecto')->name('gastos.store');
        Route::put('/proyectos/{proyecto}/gastos/{gasto}', 'update')->can('registrar', 'proyecto')->name('gastos.update');
        Route::delete('/proyectos/{proyecto}/gastos/{gasto}', 'destroy')->can('registrar', 'proyecto')->name('gastos.destroy');
        Route::get('/proyectos/{proyecto}/gastos/{gasto}/documento', 'documento')->can('view', 'proyecto')->name('gastos.documento');
    });

    Route::controller(AprobacionGastoController::class)->middleware('can:administrar')->group(function () {
        Route::get('/gastos/pendientes', 'index')->name('gastos.pendientes');
        Route::post('/gastos/{gasto}/aprobar', 'aprobar')->name('gastos.aprobar');
        Route::post('/gastos/{gasto}/rechazar', 'rechazar')->name('gastos.rechazar');
    });

    Route::controller(InventarioController::class)->group(function () {
        Route::get('/proyectos/{proyecto}/inventario', 'index')->can('view', 'proyecto')->name('inventario.index');
        Route::post('/proyectos/{proyecto}/inventario/movimientos', 'storeMovimiento')->can('registrar', 'proyecto')->name('inventario.movimientos.store');
        Route::post('/proyectos/{proyecto}/inventario/conteo', 'storeConteo')->can('registrar', 'proyecto')->name('inventario.conteo.store');
        Route::post('/proyectos/{proyecto}/materiales', 'storeMaterial')->can('registrar', 'proyecto')->name('inventario.materiales.store');
        Route::put('/proyectos/{proyecto}/materiales/{material}', 'updateMaterial')->can('update', 'proyecto')->name('inventario.materiales.update');
        Route::delete('/proyectos/{proyecto}/materiales/{material}', 'destroyMaterial')->can('update', 'proyecto')->name('inventario.materiales.destroy');
    });

    Route::controller(ReporteController::class)->group(function () {
        Route::get('/reportes/desviacion', 'desviacion')->name('reportes.desviacion');
        Route::get('/reportes/desviacion/pdf', 'desviacionPdf')->name('reportes.desviacion.pdf');
        Route::get('/proyectos/{proyecto}/reportes/adicionales', 'adicionales')->can('view', 'proyecto')->name('reportes.adicionales');
        Route::get('/proyectos/{proyecto}/reportes/adicionales/pdf', 'adicionalesPdf')->can('view', 'proyecto')->name('reportes.adicionales.pdf');
    });

    Route::get('/auditoria', [AuditoriaController::class, 'index'])->middleware('can:administrar')->name('auditoria.index');

    Route::controller(UsuarioController::class)->middleware('can:administrar')->group(function () {
        Route::get('/usuarios', 'index')->name('usuarios.index');
        Route::post('/usuarios', 'store')->name('usuarios.store');
        Route::put('/usuarios/{usuario}', 'update')->name('usuarios.update');
    });
});
