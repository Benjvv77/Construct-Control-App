<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * HU08–HU10: un gasto presupuestado se asocia a un ítem del presupuesto, y los gastos
 * pasan por aprobación antes de afectar el presupuesto.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('mglpbc_gastos', function (Blueprint $table) {
            $table->foreignId('id_item_presupuesto')->nullable()
                ->constrained('mglpbc_item_presupuesto', 'id_item_presupuesto')->restrictOnDelete();
            $table->string('unidad_gasto', 30)->nullable();
            $table->enum('estado_gasto', ['pendiente', 'aprobado', 'rechazado'])->default('pendiente');
            $table->foreignId('id_usuario_registro')->nullable()->constrained('mglpbc_usuario', 'id_usuario');
            $table->foreignId('id_usuario_revisor')->nullable()->constrained('mglpbc_usuario', 'id_usuario');
            $table->timestamp('fecha_revision')->nullable();
            $table->text('motivo_rechazo')->nullable();
        });

        // Los gastos que ya existían estaban vigentes: quedan aprobados y a nombre del responsable
        DB::statement("
            UPDATE mglpbc_gastos g
            SET estado_gasto = 'aprobado',
                id_usuario_registro = p.id_usuario,
                id_usuario_revisor = p.id_usuario
            FROM mglpbc_proyecto p
            WHERE p.id_proyecto = g.id_proyecto
        ");

        // Los gastos presupuestados que ya existían pasan al ítem "Presupuesto general" de su proyecto
        DB::statement("
            UPDATE mglpbc_gastos g
            SET id_item_presupuesto = i.id_item_presupuesto
            FROM mglpbc_item_presupuesto i
            WHERE i.id_proyecto = g.id_proyecto
              AND i.descripcion_item = 'Presupuesto general'
              AND g.id_item_presupuesto IS NULL
              AND COALESCE(g.id_tipo_costo, 1) <> 2
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mglpbc_gastos', function (Blueprint $table) {
            $table->dropForeign(['id_item_presupuesto']);
            $table->dropForeign(['id_usuario_registro']);
            $table->dropForeign(['id_usuario_revisor']);
            $table->dropColumn(['id_item_presupuesto', 'unidad_gasto', 'estado_gasto', 'id_usuario_registro', 'id_usuario_revisor', 'fecha_revision', 'motivo_rechazo']);
        });
    }
};
