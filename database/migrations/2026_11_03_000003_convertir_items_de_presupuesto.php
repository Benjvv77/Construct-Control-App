<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * HU07: los ítems del presupuesto dejan de ser "presupuesto por material" y pasan a ser
 * partidas libres del proyecto (descripción, tipo de gasto, cantidad y precio unitario).
 * El presupuesto del proyecto pasa a ser la suma de sus ítems.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Los ítems por material no tienen equivalente directo en el nuevo modelo
        DB::table('mglpbc_item_presupuesto')->delete();

        Schema::table('mglpbc_item_presupuesto', function (Blueprint $table) {
            $table->dropForeign(['id_material']);
            $table->dropColumn(['id_material', 'cantidad_estimada', 'costo_unitario_estimado']);
        });

        Schema::table('mglpbc_item_presupuesto', function (Blueprint $table) {
            $table->foreignId('id_tipo_gasto')->constrained('mglpbc_tipo_gastos', 'id_tipo_gasto');
            $table->string('descripcion_item');
            $table->decimal('cantidad', 12, 2);
            $table->string('unidad_item', 30)->nullable();
            $table->decimal('precio_unitario', 15, 2);
        });

        // Cada proyecto con presupuesto conserva el monto como un ítem "Presupuesto general"
        $otros = DB::table('mglpbc_tipo_gastos')->where('nombre_tipo_gasto', 'Otros')->value('id_tipo_gasto');

        if ($otros) {
            foreach (DB::table('mglpbc_proyecto')->where('presupuesto_proyecto', '>', 0)->get() as $proyecto) {
                DB::table('mglpbc_item_presupuesto')->insert([
                    'id_proyecto' => $proyecto->id_proyecto,
                    'id_tipo_gasto' => $otros,
                    'descripcion_item' => 'Presupuesto general',
                    'cantidad' => 1,
                    'unidad_item' => 'global',
                    'precio_unitario' => $proyecto->presupuesto_proyecto,
                ]);
            }
        }

        DB::table('mglpbc_proyecto')->where('presupuesto_proyecto', '<=', 0)->update(['presupuesto_proyecto' => 0]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('mglpbc_item_presupuesto')->delete();

        Schema::table('mglpbc_item_presupuesto', function (Blueprint $table) {
            $table->dropForeign(['id_tipo_gasto']);
            $table->dropColumn(['id_tipo_gasto', 'descripcion_item', 'cantidad', 'unidad_item', 'precio_unitario']);
        });

        Schema::table('mglpbc_item_presupuesto', function (Blueprint $table) {
            $table->foreignId('id_material')->constrained('mglpbc_material', 'id_material')->cascadeOnDelete();
            $table->decimal('cantidad_estimada', 12, 2);
            $table->decimal('costo_unitario_estimado', 15, 2);
        });
    }
};
