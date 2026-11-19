<?php

namespace Database\Seeders;

use App\Models\Gasto;
use App\Models\ItemPresupuesto;
use App\Models\Material;
use App\Models\MovimientoInventario;
use App\Models\Proyecto;
use App\Models\TipoCosto;
use App\Models\TipoGasto;
use App\Models\Usuario;
use Illuminate\Database\Seeder;

/**
 * Datos conocidos para las pruebas de Cypress. Solo se usa en la base constructcontrol_cypress.
 */
class CypressSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(CatalogoSeeder::class);

        $administrador = Usuario::create([
            'nombre_usuario' => 'Ana',
            'apellido_paterno' => 'Admin',
            'correo_usuario' => 'admin@prueba.test',
            'password_usuario' => 'Prueba1234',
            'rol_usuario' => Usuario::ROL_ADMINISTRADOR,
            'estado_usuario' => 1,
        ]);

        $supervisor = Usuario::create([
            'nombre_usuario' => 'Sara',
            'apellido_paterno' => 'Supervisora',
            'correo_usuario' => 'supervisor@prueba.test',
            'password_usuario' => 'Prueba1234',
            'rol_usuario' => 'Supervisor',
            'estado_usuario' => 1,
        ]);

        Proyecto::create([
            'id_usuario' => $administrador->id_usuario,
            'nombre_proyecto' => 'Obra del administrador',
            'presupuesto_proyecto' => 0,
            'fecha_inicio_proyecto' => '2026-11-01',
            'estado_proyecto' => 1,
        ]);

        $obra = Proyecto::create([
            'id_usuario' => $supervisor->id_usuario,
            'nombre_proyecto' => 'Obra del supervisor',
            'presupuesto_proyecto' => 0,
            'fecha_inicio_proyecto' => '2026-11-01',
            'estado_proyecto' => 1,
        ]);

        // Presupuesto de la obra del supervisor: $1.700.000
        $cemento = $this->item($obra, TipoGasto::MATERIALES, 'Cemento 25 kg', 100, 'sacos', 5000);
        $electricidad = $this->item($obra, 7, 'Instalación eléctrica', 1, 'global', 1000000);
        $this->item($obra, 9, 'Ítem sin gastos', 1, 'global', 200000);

        // CP06: un ítem con gastos no se puede eliminar
        $this->gasto($obra, $cemento, $supervisor, 'Compra inicial de cemento', 50000, [
            'cantidad_gasto' => 10,
            'unidad_gasto' => 'sacos',
            'estado_gasto' => Gasto::APROBADO,
            'id_usuario_revisor' => $administrador->id_usuario,
            'fecha_revision' => '2026-11-02',
        ]);

        // CP11 y CP12: gasto que espera la revisión del Administrador
        $this->gasto($obra, $electricidad, $supervisor, 'Gasto por revisar', 30000, [
            'estado_gasto' => Gasto::PENDIENTE,
        ]);

        // CP13: material con 20 m3 en bodega
        Material::create([
            'id_proyecto' => $obra->id_proyecto,
            'nombre_material' => 'Arena',
            'unidad_material' => 'm3',
            'stock_actual' => 0,
            'stock_inicial' => 0,
            'stock_minimo' => 5,
            'costo_estimado_material' => 0,
            'estado_material' => 1,
        ])->registrarMovimiento(MovimientoInventario::ENTRADA, 20, '2026-11-01', 'Saldo inicial');
    }

    private function item(Proyecto $obra, int $tipo, string $descripcion, float $cantidad, string $unidad, float $precio): ItemPresupuesto
    {
        return ItemPresupuesto::create([
            'id_proyecto' => $obra->id_proyecto,
            'id_tipo_gasto' => $tipo,
            'descripcion_item' => $descripcion,
            'cantidad' => $cantidad,
            'unidad_item' => $unidad,
            'precio_unitario' => $precio,
        ]);
    }

    private function gasto(Proyecto $obra, ItemPresupuesto $item, Usuario $registrador, string $nombre, float $monto, array $datos): void
    {
        Gasto::create([
            'id_proyecto' => $obra->id_proyecto,
            'id_item_presupuesto' => $item->id_item_presupuesto,
            'id_tipo_costo' => TipoCosto::PRESUPUESTADO,
            'id_tipo_gasto' => $item->id_tipo_gasto,
            'nombre_gasto' => $nombre,
            'monto_gasto' => $monto,
            'fecha_gasto' => '2026-11-02',
            'cantidad_gasto' => 0,
            'id_usuario_registro' => $registrador->id_usuario,
            ...$datos,
        ]);
    }
}