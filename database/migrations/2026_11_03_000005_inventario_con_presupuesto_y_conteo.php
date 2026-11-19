<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * HU12–HU14: los materiales se ligan al ítem del presupuesto que los cubre, el conteo físico
 * se registra como movimiento de tipo AJUSTE, y todo el stock queda explicado por movimientos.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('mglpbc_material', function (Blueprint $table) {
            $table->foreignId('id_item_presupuesto')->nullable()
                ->constrained('mglpbc_item_presupuesto', 'id_item_presupuesto')->nullOnDelete();
        });

        // AJUSTE guarda la diferencia del conteo físico: negativa si falta material, positiva si sobra
        DB::statement('ALTER TABLE mglpbc_movimiento_inventario DROP CONSTRAINT mglpbc_movimiento_inventario_tipo_movimiento_check');
        DB::statement("ALTER TABLE mglpbc_movimiento_inventario ADD CONSTRAINT mglpbc_movimiento_inventario_tipo_movimiento_check
            CHECK (tipo_movimiento IN ('ENTRADA', 'SALIDA', 'AJUSTE'))");

        // El stock que entró antes de existir el historial (desde gastos) queda como una entrada inicial,
        // para que siempre se cumpla: stock = entradas − salidas + ajustes
        $materiales = DB::select("
            SELECT m.id_material, m.id_proyecto, m.stock_actual,
                   COALESCE(SUM(CASE mi.tipo_movimiento WHEN 'ENTRADA' THEN mi.cantidad_movimiento
                                                        WHEN 'SALIDA' THEN -mi.cantidad_movimiento
                                                        ELSE mi.cantidad_movimiento END), 0) AS explicado,
                   COALESCE((SELECT MIN(fecha_gasto) FROM mglpbc_gastos g WHERE g.id_gasto = m.id_gasto),
                            (SELECT fecha_inicio_proyecto FROM mglpbc_proyecto p WHERE p.id_proyecto = m.id_proyecto)) AS fecha
            FROM mglpbc_material m
            LEFT JOIN mglpbc_movimiento_inventario mi ON mi.id_material = m.id_material
            GROUP BY m.id_material
        ");

        foreach ($materiales as $material) {
            $faltante = (float) $material->stock_actual - (float) $material->explicado;

            if ($faltante > 0) {
                DB::table('mglpbc_movimiento_inventario')->insert([
                    'id_proyecto' => $material->id_proyecto,
                    'id_material' => $material->id_material,
                    'tipo_movimiento' => 'ENTRADA',
                    'cantidad_movimiento' => $faltante,
                    'fecha_movimiento' => $material->fecha,
                    'descripcion_movimiento' => 'Saldo inicial (ingresado antes del historial de movimientos)',
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('mglpbc_movimiento_inventario')->where('tipo_movimiento', 'AJUSTE')->delete();
        DB::statement('ALTER TABLE mglpbc_movimiento_inventario DROP CONSTRAINT mglpbc_movimiento_inventario_tipo_movimiento_check');
        DB::statement("ALTER TABLE mglpbc_movimiento_inventario ADD CONSTRAINT mglpbc_movimiento_inventario_tipo_movimiento_check
            CHECK (tipo_movimiento IN ('ENTRADA', 'SALIDA'))");

        Schema::table('mglpbc_material', function (Blueprint $table) {
            $table->dropForeign(['id_item_presupuesto']);
            $table->dropColumn('id_item_presupuesto');
        });
    }
};
