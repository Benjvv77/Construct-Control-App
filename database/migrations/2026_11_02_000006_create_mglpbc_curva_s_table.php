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
        Schema::create('mglpbc_curva_s', function (Blueprint $table) {
            $table->id('id_curva');
            $table->foreignId('id_proyecto')->constrained('mglpbc_proyecto', 'id_proyecto')->cascadeOnDelete();
            $table->date('fecha_evaluacion');
            $table->decimal('monto_planificado_acumulado', 15, 2);
            $table->decimal('monto_real_acumulado', 15, 2);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mglpbc_curva_s');
    }
};
