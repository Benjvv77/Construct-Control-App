<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('mglpbc_movimiento_inventario', function (Blueprint $table) {
            $table->id('id_movimiento');
            $table->foreignId('id_proyecto')->constrained('mglpbc_proyecto', 'id_proyecto')->cascadeOnDelete();
            $table->foreignId('id_material')->constrained('mglpbc_material', 'id_material')->cascadeOnDelete();
            $table->enum('tipo_movimiento', ['ENTRADA', 'SALIDA']);
            $table->decimal('cantidad_movimiento', 12, 2);
            $table->date('fecha_movimiento');
            $table->text('descripcion_movimiento')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mglpbc_movimiento_inventario');
    }
};
