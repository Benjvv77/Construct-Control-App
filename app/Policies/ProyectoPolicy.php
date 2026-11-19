<?php

namespace App\Policies;

use App\Models\Proyecto;
use App\Models\Usuario;

/**
 * Permisos sobre un proyecto:
 * - El Administrador gestiona todo: datos del proyecto, presupuesto, Curva S y aprobación de gastos (HU03, HU04, HU06, HU07, HU10).
 * - El responsable (supervisor) ve su proyecto y registra gastos y movimientos de inventario (HU08, HU09, HU12, HU13).
 */
class ProyectoPolicy
{
    public function view(Usuario $usuario, Proyecto $proyecto): bool
    {
        return $this->esResponsableOAdministrador($usuario, $proyecto);
    }

    /** Registrar gastos y movimientos de inventario. */
    public function registrar(Usuario $usuario, Proyecto $proyecto): bool
    {
        return $this->esResponsableOAdministrador($usuario, $proyecto);
    }

    /** Editar los datos, el presupuesto y la Curva S del proyecto. */
    public function update(Usuario $usuario, Proyecto $proyecto): bool
    {
        return $usuario->esAdministrador();
    }

    public function delete(Usuario $usuario, Proyecto $proyecto): bool
    {
        return $usuario->esAdministrador();
    }

    private function esResponsableOAdministrador(Usuario $usuario, Proyecto $proyecto): bool
    {
        return $usuario->esAdministrador() || $proyecto->id_usuario === $usuario->id_usuario;
    }
}
