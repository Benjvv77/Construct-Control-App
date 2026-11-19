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
        Schema::create('mglpbc_gastos', function (Blueprint $table) {
            $table->id('id_gasto');
            $table->foreignId('id_proyecto')->constrained('mglpbc_proyecto', 'id_proyecto')->cascadeOnDelete();
            $table->foreignId('id_tipo_gasto')->constrained('mglpbc_tipo_gastos', 'id_tipo_gasto');
            $table->foreignId('id_tipo_costo')->nullable()->constrained('mglpbc_tipo_costo', 'id_tipo_costo');
            $table->string('nombre_gasto');
            $table->text('descripcion_gasto')->nullable();
            $table->decimal('monto_gasto', 15, 2);
            $table->date('fecha_gasto');
            $table->string('documento_gasto')->nullable();
            $table->decimal('cantidad_gasto', 12, 2)->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mglpbc_gastos');
    }
};
