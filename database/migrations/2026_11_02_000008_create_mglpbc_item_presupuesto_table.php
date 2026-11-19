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
        Schema::create('mglpbc_item_presupuesto', function (Blueprint $table) {
            $table->id('id_item_presupuesto');
            $table->foreignId('id_proyecto')->constrained('mglpbc_proyecto', 'id_proyecto')->cascadeOnDelete();
            $table->foreignId('id_material')->constrained('mglpbc_material', 'id_material')->cascadeOnDelete();
            $table->decimal('cantidad_estimada', 12, 2);
            $table->decimal('costo_unitario_estimado', 15, 2);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mglpbc_item_presupuesto');
    }
};
