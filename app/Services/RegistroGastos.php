<?php

namespace App\Services;

use App\Models\Gasto;
use App\Models\Material;
use App\Models\Proyecto;
use App\Models\Usuario;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Ciclo de vida de un gasto (HU08–HU10):
 * - Lo registra un supervisor → queda pendiente y no afecta el presupuesto ni el inventario.
 * - Lo registra el Administrador → queda aprobado de inmediato.
 * - Al aprobarse, si es de materiales, el material entra al inventario.
 */
class RegistroGastos
{
    /**
     * @param  array  $datos  campos de mglpbc_gastos
     */
    public function registrar(Proyecto $proyecto, array $datos, Usuario $registrador, ?UploadedFile $documento = null): Gasto
    {
        $ruta = $documento?->store('gastos');

        try {
            return DB::transaction(function () use ($proyecto, $datos, $registrador, $ruta) {
                $gasto = $proyecto->gastos()->create([
                    ...collect($datos)->except('documento_gasto')->all(),
                    'documento_gasto' => $ruta,
                    'cantidad_gasto' => $datos['cantidad_gasto'] ?? 0,
                    'estado_gasto' => Gasto::PENDIENTE,
                    'id_usuario_registro' => $registrador->id_usuario,
                ]);

                if ($registrador->esAdministrador()) {
                    $this->aplicarAprobacion($gasto, $registrador);
                }

                return $gasto;
            });
        } catch (Throwable $e) {
            if ($ruta) {
                Storage::delete($ruta);
            }

            throw $e;
        }
    }

    /**
     * @throws ValidationException si el gasto ya fue revisado
     */
    public function aprobar(Gasto $gasto, Usuario $revisor): Gasto
    {
        return DB::transaction(function () use ($gasto, $revisor) {
            $gasto = $this->bloquearPendiente($gasto);
            $this->aplicarAprobacion($gasto, $revisor);

            return $gasto;
        });
    }

    /**
     * @throws ValidationException si el gasto ya fue revisado
     */
    public function rechazar(Gasto $gasto, Usuario $revisor, string $motivo): Gasto
    {
        return DB::transaction(function () use ($gasto, $revisor, $motivo) {
            $gasto = $this->bloquearPendiente($gasto);

            $gasto->update([
                'estado_gasto' => Gasto::RECHAZADO,
                'id_usuario_revisor' => $revisor->id_usuario,
                'fecha_revision' => now(),
                'motivo_rechazo' => $motivo,
            ]);

            return $gasto;
        });
    }

    private function aplicarAprobacion(Gasto $gasto, Usuario $revisor): void
    {
        $gasto->update([
            'estado_gasto' => Gasto::APROBADO,
            'id_usuario_revisor' => $revisor->id_usuario,
            'fecha_revision' => now(),
            'motivo_rechazo' => null,
        ]);

        if ($gasto->esDeMateriales() && (float) $gasto->cantidad_gasto > 0) {
            Material::registrarDesdeGasto($gasto, (float) $gasto->cantidad_gasto, $gasto->unidad_gasto ?? $gasto->item?->unidad_item ?? 'unidades');
        }
    }

    /** Bloquea el gasto para que dos revisiones simultáneas no lo apliquen dos veces. */
    private function bloquearPendiente(Gasto $gasto): Gasto
    {
        $gasto = Gasto::whereKey($gasto->id_gasto)->lockForUpdate()->firstOrFail();

        if (! $gasto->estaPendiente()) {
            throw ValidationException::withMessages(['gasto' => 'Este gasto ya fue revisado.']);
        }

        return $gasto;
    }
}
