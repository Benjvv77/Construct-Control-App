<?php

namespace App\Models\Concerns;

use App\Models\Auditoria;
use Illuminate\Database\Eloquent\Model;

/**
 * Registra en mglpbc_auditoria cada vez que un registro se crea, modifica o elimina (HU19):
 * quién lo hizo, cuándo y qué valores cambiaron.
 *
 * El modelo que lo usa define ENTIDAD (nombre para mostrar) y puede sobrescribir
 * descripcionAuditoria() y accionAuditoria().
 */
trait Auditable
{
    public static function bootAuditable(): void
    {
        static::created(fn (Model $modelo) => $modelo->auditar('creado', ['despues' => $modelo->valoresAuditables($modelo->getAttributes())]));

        static::updated(function (Model $modelo) {
            $cambios = $modelo->valoresAuditables($modelo->getChanges());

            if ($cambios === []) {
                return;
            }

            $antes = array_intersect_key($modelo->valoresAuditables($modelo->getOriginal()), $cambios);
            $modelo->auditar($modelo->accionAuditoria($cambios), ['antes' => $antes, 'despues' => $cambios]);
        });

        static::deleted(fn (Model $modelo) => $modelo->auditar('eliminado', ['antes' => $modelo->valoresAuditables($modelo->getOriginal())]));
    }

    public function descripcionAuditoria(): string
    {
        return static::ENTIDAD.' #'.$this->getKey();
    }

    /** Nombre de la acción para una modificación; los modelos lo sobrescriben para acciones con significado propio. */
    public function accionAuditoria(array $cambios): string
    {
        return 'modificado';
    }

    protected function auditar(string $accion, array $cambios): void
    {
        Auditoria::create([
            'id_usuario' => auth()->id(),
            'accion' => $accion,
            'entidad' => static::ENTIDAD,
            'id_registro' => $this->getKey(),
            'id_proyecto' => $this->getAttribute('id_proyecto'),
            'descripcion' => mb_substr($this->descripcionAuditoria(), 0, 255),
            'cambios' => $cambios,
            'ip' => app()->runningInConsole() ? null : request()->ip(),
            'fecha' => now(),
        ]);
    }

    /** Quita datos sensibles o sin valor para la auditoría (contraseñas, tokens). */
    protected function valoresAuditables(array $valores): array
    {
        return array_diff_key($valores, array_flip(['password_usuario', 'remember_token', $this->getKeyName()]));
    }
}
