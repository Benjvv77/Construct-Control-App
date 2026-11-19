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
        Schema::create('mglpbc_material', function (Blueprint $table) {
            $table->id('id_material');
            $table->foreignId('id_proyecto')->constrained('mglpbc_proyecto', 'id_proyecto')->cascadeOnDelete();
            $table->foreignId('id_gasto')->nullable()->constrained('mglpbc_gastos', 'id_gasto')->nullOnDelete();
            $table->string('nombre_material');
            $table->string('unidad_material', 50);
            $table->decimal('stock_actual', 12, 2)->default(0);
            $table->decimal('stock_inicial', 12, 2)->default(0);
            $table->decimal('stock_minimo', 12, 2)->default(0);
            $table->decimal('costo_estimado_material', 15, 2)->default(0);
            $table->smallInteger('estado_material')->default(1);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mglpbc_material');
    }
};
